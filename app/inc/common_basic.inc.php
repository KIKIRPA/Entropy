<?php

// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}


/* FUNCTIONS:
    logout()
    sanitizeStr($string, $replaceby = "_", $others = False, $case = 0)
    detectBomEncoding($str)
    calcPermMod($permtable, $lib = false)
    calcPermLib($permtable, $mod, $lib = false)
    readJSONfile($path, $dieOnError)
    inflateArray($array, $mode = -1, $keysep = ":", $fieldsep = "|", $i = 0)
    flattenArray($array, $multirecords = false, $mode = -1, $keysep = ":", $fieldsep = "|")
    getMeta($metadata, $get, $concatenate = "; ", $description = ": ")
    nameMeta($get)
    overrideMeta($metadata, $dataset = False)   ___ DEPRECIATED ___
    collapseMeasurement($measurement, $dataset)
    findDataType($type, $datatypes, $key = false)
    findDataTypeUnits($type, $datatypes, $key = "json", $search = Null)
    orderData(&$data, $sortOrder = null)
    mdate($format, $microtime)
    eventLog($cat, $msg, $fatal = false, $mail = false)
    bulmaColorModifier($color, $colorList, $default = null)
    bulmaColorInt($color, $colorList, $default = null)
    selectConvertorClass($convertors, $datatype, $format, $options = array())
*/


/** 
 * logout() 
 * 
 * close session
 */
function logout()
{
    $_SESSION = array();
    session_destroy();
}


/**
 *     sanitizeStr($string, $replaceby = "_", $others = False, $case = 0)
 * 
 *     sanitize string, removes all characters, html-tags... that should not be there
 *     eg. string to be used as a part of a filename, or for loose string comparisons
 *     $others is other characters to replace, e.g. "-+:^"
*      $case: 1 lowercase, 2 uppercase, 0 and everything else: do nothing
 */
function sanitizeStr($string, $replaceby = "_", $others = false, $case = 0)
{
    $replace = str_split(" _!\"#$%&'()*,./;<=>?@[\\]`{}~");
    
    if ($others) {
        $replace = array_merge($replace, str_split($others));
    }

    $string = str_replace($replace, $replaceby, $string);
    if ($replaceby <> "") {
        $string = preg_replace('/' . $replaceby . '{2,}/', $replaceby, $string);
    }  // replace multiple underscores by a single
    $string = trim($string, "_");
    
    if ($case == 1) {
        $string = strtolower($string);
    }
    elseif ($case == 2) {
        $string = strtoupper($string);
    }
    
    return filter_var($string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
}


/** detect encoding using the UTF byte order mark, and return string without encoding */
function detectBomEncoding($str)
{
    if ($str[0] == chr(0xEF) && $str[1] == chr(0xBB) && $str[2] == chr(0xBF)) {
        return array(substr($str, 3), 'UTF-8');
    } elseif ($str[0] == chr(0x00) && $str[1] == chr(0x00) && $str[2] == chr(0xFE) && $str[3] == chr(0xFF)) {
        return array(substr($str, 4), 'UTF-32BE');
    } elseif ($str[0] == chr(0xFF) && $str[1] == chr(0xFE)) {
        if ($str[2] == chr(0x00) && $str[3] == chr(0x00)) {
            return array(substr($str, 4), 'UTF-32LE');
        }
        return array(substr($str, 2), 'UTF-16LE');
    } elseif ($str[0] == chr(0xFE) && $str[1] == chr(0xFF)) {
        return array(substr($str, 2), 'UTF-16BE');
    } else {
        $enc = mb_detect_encoding($str, 'auto', true);
        if ($enc) {
            return array($str, $enc);
        } else {
            return array($str, 'Windows-1252');
        } //fall-back
    }
}


/**
 * calcPermMod($permtable, $lib = false)
 * 
 * returns the allowed modules for a given user.
 * requires a user permissions table (array, part of users.json)
 * if a library id is given, it will output only the library-specific modules (array) to which a user has access (for that library)
 * if no library is given, it will output only the admin-specific modules (array) to which a user has access
 * 
 * !! if user is administrator it will return true (=access to all)
 * !! if user has no permissions at all (for that library), it will return false
 */
function calcPermMod($permtable, $lib = false)
{
    if ($permtable["admin"]) {
        return true;
    }
    
    $arr = array();
    
    foreach ($permtable as $mod => $modperms) {
        // if library is supplied: only work on library modules
        // if NO library is supplied: only work on administration modules
    
        if ($lib and is_array($modperms)) {
            //if in array $lib or _ALL, but not _NONE (the latter takes priority!!!):
            if ((in_array(strtolower($lib), $modperms) or in_array("_ALL", $modperms))
            and !in_array("_NONE", $modperms)
        ) {
                array_push($arr, $mod);
            }
        } elseif (!$lib and !is_array($modperms)) {
            if (filter_var($modperms, FILTER_VALIDATE_BOOLEAN)) {
                array_push($arr, $mod);
            }
        }
    }
    
    if (!empty($arr)) {
        return $arr;
    } else {
        return false;
    }
}


/**
 * calcPermLib($permtable, $mod, $lib = false)
 * 
 * returns for a given module which libraries are allowed
 * requires a user permissions table (array, part of users.json)
 * if no library is given: outputs true for all libs, false for no libs or an array with the lib ids
 * if a library is given: outputs true or false
 */
function calcPermLib($permtable, $mod, $lib = false)
{
    if ($permtable["admin"]) {
        return true;
    }
    
    // $permtable[$mod]: is the module in the permission table?
    // - if it is an array, check for _NONE, _ALL, or the list of libs (in this priority)
    //    if a lib is supplied, just answer true or false
    // - if it is not an array, evaluate it as a boolean (admin modules)
    
    if (isset($permtable[$mod])) {
        if (is_array($permtable[$mod])) {
            if (in_array("_NONE", $permtable[$mod])) {
                return false;
            } elseif (in_array("_ALL", $permtable[$mod])) {
                return true;
            } elseif (empty($permtable[$mod])) {
                return false;
            } elseif ($lib) {
                return (in_array($lib, $permtable[$mod]));
            } else {
                return $permtable[$mod];
            }
        } else {
            return filter_var($permtable[$mod], FILTER_VALIDATE_BOOLEAN);
        }
    }
    
    // if we are not returned allready, do it now (with no permissions)
    return false;
}


/**
 * readJSONfile($path, $dieOnError)
 * 
 * reads json file and outputs as an array
 * if something goes wrong (file does not exist, not readable or contains error)
 * it will output an empty array (in order to create new file
 * optional $dieOnError will in this case stop further code execution
 */
function readJSONfile($path, $dieOnError = false)
{
    $array = array();
    if (file_exists($path)) {
        $array = file_get_contents($path);
        $array = json_decode($array, true);
    }
    
    if (empty($array)) {
        eventLog("WARNING", "Could not read " . $path . " file", $dieOnError, $dieOnError);
    }

    return $array;
}


/**
 * inflateArray($array, $mode = -1, $keysep = ":", $fieldsep = "|", $i = 0)
 * 
 * transform a flat array structure into a multidimensial array
 * 
 * - $multirecords (default false): first level keys are record id's and should not be flattened
 * - $mode: 0            --> completely flat structure with keyseparation
 *          1 (default)  --> completely flat structure with keyseparation and fieldseparation if integer keys on the deepest level
 *          1, 2...      --> (possibly incomplete) keyseparation for 1, 2... iterations only
 * - keyseparation:   <samplename:C.I. number>   --> $keysep default ":"
 * - fieldseparation: <allowformats>spc|dx|txt   --> $fieldsep default "|"
*/
function inflateArray($array, $mode = -1, $keysep = ":", $fieldsep = "|", $i = 0)
{
    $out = array();
    
    foreach ($array as $key => $value) {
        //make this function recursive and should work on partially inflated arrays:
        //if a $value itself is an array, dive into it
        //can only work sensibly on $mode -1 or 0 (full keyseparation)
        if (is_array($value) and (($mode < 1) or ($i < $mode))) {
            $value = inflateArray($value, $mode, $keysep, $fieldsep, $i+1);
        }
    
        //fieldseparation (eg. allowformats=spc|dx|txt)
        elseif ($mode = -1) {
            if (substr_count($value, $fieldsep) > 1) {
                $value = explode($fieldsep, $value);
            }
        }
    
        //keyseparation and key/value-combination
        if ($mode > 0) {
            $key = explode($keysep, $key, $mode);
        } else {
            $key = explode($keysep, $key);
        }
    
        if (count($key) > 1) {
            foreach (array_reverse($key) as $part) {
                $arr = array();
                $arr[$part] = $value;
                $value = $arr;
            }
            $out = array_replace_recursive($out, $value);
        } else {
            $out[$key[0]] = $value;
        }
    }
    return $out;
}


/**
 * flattenArray($array, $multirecords = false, $mode = -1, $keysep = ":", $fieldsep = "|")
 * 
 * flatten array: outputs array of records in which these records have a flat structure
 * 
 * - $multirecords (default false): first level keys are record id's and should not be flattened
 * - $mode: 0            --> completely flat structure with keyseparation
 *          1 (default)  --> completely flat structure with keyseparation and fieldseparation if integer keys on the deepest level
 *          1, 2...      --> (possibly incomplete) keyseparation for 1, 2... iterations only
 * - keyseparation:   <samplename:C.I. number>   --> $keysep default ":"
 * - fieldseparation: <allowformats>spc|dx|txt   --> $fieldsep default "|"
*/
function flattenArray($array, $multirecords = false, $mode = -1, $keysep = ":", $fieldsep = "|")
{
    $i = 0;
    
    do {
        $proceed = false;
        if ($multirecords) {
            foreach ($array as $id => $record) {
                foreach ($record as $key1 => $field1) {
                    if (is_array($field1)) {
                        $proceed = true;
                        foreach ($field1 as $key2 => $field2) {
                            if (is_int($key2) and !(is_array($field2)) and ($mode == -1)) {
                                $array[$id][$key1] = implode($fieldsep, $field1);
                            } else {
                                $array[$id][$key1 .$keysep . $key2] = $field2;
                                unset($array[$id][$key1]);
                            }
                        }
                    }
                }
            }
        } else {
            foreach ($array as $key1 => $field1) {
                if (is_array($field1)) {
                    $proceed = true;
                    foreach ($field1 as $key2 => $field2) {
                        if (is_int($key2) and !(is_array($field2)) and ($mode == -1)) {
                            $array[$key1] = implode($fieldsep, $field1);
                        } else {
                            $array[$key1 .$keysep . $key2] = $field2;
                            unset($array[$key1]);
                        }
                    }
                }
            }
        }
    
        if (($mode > 0) and (++$i >= $mode)) {
            $proceed = false;
        }  // stop after $mode iterations if $mode is set to positive.
    } while ($proceed);
    
    return $array;
}


/** 
 * getMeta($metadata, $get, $concatenate = "; ", $description = ": ")
 * 
 * retrieve metadata-item from (inflated) metadata array
 * $get is something like "sample:sample name", "samplesource:0:identifier+source", "measurement:date^long";
 * if multiple fields need to be concatenated, the concatenation symbol (default ;) can be supplied
 * in the concatenated outputs descriptions can be added if a $description symbol (default :) is supplied
 * (if set to false, a short notation will be used without descriptions)
 */
function getMeta($metadata, $get, $concatenate = "; ", $description = ": ")
{
    //prepare $get for loose searching (lowercase, remove whitespaces and special characters, except :+^)
    $get = sanitizeStr($get, "", "-", 1);
    
    //split get-code into hierachical tree
    $tree = explode(":", $get);
    $n = count($tree);
    
    //split the "leaves" from the tree: the actual fields to be retrieved
    $leaves = explode("+", $tree[$n - 1]);
    unset($tree[$n - 1]);
    
    //split the notation from the fields to be retrieved
    foreach ($leaves as $id => $leaf) {
        $arr = explode("^", $leaf);
        if (count($arr) > 1) {
            $leaves[$id] = $arr[0];
            $formats[$arr[0]] = $arr[1]; //if $arr[2+] exist we'll neglect it; we can only have one notation
        } else {
            $formats[$id] = null;
        }
    }
    
    //reduce the metadata step by step
    //first the branch in the tree
    if (count($tree) > 0) {
        foreach ($tree as $branch) {
            // loose search if the branch is in the metadata
            foreach ($metadata as $id => $value) {
                if ($branch == sanitizeStr($id, "", "-", 1)) {
                    // if so, only keep this branch of the metadata
                    $metadata = $metadata[$id];
                    break;
                }
            }
        }
    }
    
    //next the leaves
    $arr = array();
    foreach ($leaves as $leaf) {
        $arr[$leaf] = null;
        // loose search if the leaf exists in the metadata
        foreach ($metadata as $id => $value) {
            if ($leaf == sanitizeStr($id, "", "-", 1)) {
                $arr[$leaf] = $metadata[$id];
            }
        }
    }
    
    //flatten the resulting thing, even if the "leaves" itselve are arrays
    $metadata = flattenArray($arr, false, 0);
    
    //create output: add descriptions (eg "age: 1900") and formatting options (eg. date^year)
    foreach ($metadata as $id => $value) {
        $id2 = explode(":", $id);    //break down the flattened description, and only keep the last part
        $id2 = end($id2);            // eg. "sample:age" --> "age"

        // formatting options, eg for timestamps
        if (array_key_exists(strtolower($id2), $formats) and !is_null($value)) {
            // try to convert the string into a DateTime type; will be false if not a valid timestamp
            $ts = strtotime($value);
            if ($ts === FALSE) {
                //php expects american dates with /, european dates wit - or .; if an error, try to convert
                $ts = strtotime(str_replace('/', '-', $value));
            }
            if ($ts !== FALSE) { //if still not good, give up on timestamp!
                $ts = new DateTime(date(DATE_RSS, $ts));
                switch ($formats[strtolower($id)]) {
                    case "long":
                    case "longdate":
                        $temp = $ts->format('Y/m/d');
                        break;
                    case "short":
                    case "shortdate":
                        $temp = $ts->format('y/m/d');
                        break;
                    case "year":
                        $temp = $ts->format('Y');
                        break;
                    case "time":
                        $temp = $ts->format('H:i:s');
                        break;
                    case "datetime":
                    case "timestamp":
                    default:
                        $temp = $ts->format('Y/m/d H:i:s');
                }
                if ($temp) {    // a valid timestamp
                    $value = $temp;
                }
            }
        }
    
        // descriptions
        if ($description != false) {
            $value = nameMeta($id) . $description . $value;
        }
    
        $metadata[$id] = $value;
    }
    
    //and implode into a single string
    return implode($concatenate, $metadata);
}


/** 
 * nameMeta($get)
 * 
 * get a nice name for a metadata retrieve query string
 * if $get is $get is something like "sample:sample name", "samplesource:0:identifier+source", "measurement:date^long"
 * output will be resp. "Sample name", "Samplesource 1" and "Date" 
 */
function nameMeta($get)
{
    //split get-code into hierachical tree
    $tree = explode(":", $get);
    
    //last item in the tree is the name, except if it contains a "+"
    $name = array_pop($tree);
    if (strpos($name, "+")) {
        $name = array_pop($tree);
    }
    
    //remove formatting parts
    if (strpos($name, "^")) {
        $temp = explode("^", $name);
        $name = $temp[0];
    }
    
    //if last item in the tree is numeric
    //samplesource:0 --> samplesource 1
    if (is_numeric($name)) {
        $temp = (int)$name + 1;
        $name = array_pop($tree) . " " . $temp;
    }

    //make it nice: replace underscores with spaces, first letter uppercase
    $name = str_replace('_', ' ', $name);
    $name = ucfirst($name);
        
    //by default first letter uppercase, and some fancier hard-coded names
    // $name = str_replace("samplesource", "Sample source", $name);
    // switch (strtolower($name)) {
    //     case "cinumber":
    //     case "ci number":
    //         $name = "CI number";
    //         break;
    //     case "casnumber":
    //     case "cas number":
    //         $name = "CAS number";
    //         break;
    //     default:
    //        $name = ucfirst($name);
    //         break;
    // }

    return $name;
}


/**
 * overrideMeta($metadata, $dataset = False)
 * 
 * DEPRECIATED --> use collapseMeasurement()
 * 
 * merge/override directly stored metadata, with metadata stored in
 * "meta:", and optionally with metadata specific to a dataset
  * returns merged metadata (analytical data is stripped)
 */
function overrideMeta($metadata, $dataset = false)
{
    // get metadata stored in :meta, that needs to override direct metadata
    if (isset($metadata["meta"])) {
        $metameta = $metadata["meta"];
    } else {
        $metameta = array();
    }
    
    // get dataset-specific metadata, that needs to override all other metadata
    if ($dataset and isset($metadata["datasets"][$dataset]["meta"])) {
        $dsmeta = $metadata["datasets"][$dataset]["meta"];
    } else {
        $dsmeta = array();
    }

    // remove meta and dataset things from metadata --> only direct metadata
    unset($metadata["meta"], $metadata["datasets"]);
    
    return array_replace_recursive($metadata, $metameta, $dsmeta);
}


/**
 * collapseMeasurement($measurement, $dataset)
 * 
 * merge/override common data/meta/units/options with the dataset-specific values of a given dataset 
 * returns merged, collapsed measurement (without datasets field), or false if the supplied dataset is not found
 */
function collapseMeasurement($measurement, $dataset)
{
    //different actions for different parts:
    $recursive = array("meta", "data", "units", "options");           // use array_replace_recursive()
    $overwrite = array("type", "license", "datalink", "annotations"); // use =
    $merge     = array("attachments");                                // use array_unique(array_merge())

    //check if dataset exists in the data
    if (is_array($measurement["datasets"][$dataset])) {
        $dataset = $measurement["datasets"][$dataset]; 
    } else {
        return false;
    }

    // 1. recursively replace things
    foreach ($recursive as $item) {
        if (is_array($measurement[$item]) and is_array($dataset[$item])) {
            $measurement[$item] = array_replace_recursive($measurement[$item], $dataset[$item]);
        } elseif (!is_array($measurement[$item]) and is_array($dataset[$item])) {
            $measurement[$item] = $dataset[$item];
        }
    }

    // 2. overwrite things
    foreach ($overwrite as $item) {
        if (isset($dataset[$item]))
            $measurement[$item] = $dataset[$item];
    }

    // 3. merge things (non-recursively)
    foreach ($merge as $item) {
        if (is_array($measurement[$item]) and is_array($dataset[$item])) {
            $measurement[$item] = array_unique(array_merge($measurement[$item], $dataset[$item]));
        } elseif (!is_array($measurement[$item]) and is_array($dataset[$item])) {
            $measurement[$item] = $dataset[$item];
        }
    }

    // remove datasets
    unset($measurement["datasets"]);

    return $measurement;
}



/**
 * findDataType($type, $datatypes, $key = false) 
 * 
 * finds the generic or key name for a datatype
 * (if $key=="alias", the corrected alias name will be returned)
 * returns false if not found
 */
function findDataType($type, $datatypes, $key = false) 
{
    $type = sanitizeStr($type, "", "+-:^", 1);
    
    foreach ($datatypes as $genericName => $datatypeArray) {
        // make a list of aliasses, add the generic name to it, and all predefined aliasses
        $aliasList = array($genericName);
        if (isset($datatypeArray["alias"])) {
            $aliasList = array_merge($aliasList, $datatypeArray["alias"]);
        }

        foreach ($aliasList as $alias) {
            $clean = sanitizeStr($alias, "", "+-:^", 1);
            
            if ($type == $clean) {
                if (strtolower($key) === "alias") {
                    return $alias;
                } elseif (isset($datatypeArray[strtolower($key)])) {
                    if (is_string($datatypeArray[strtolower($key)])) {
                        return $datatypeArray[strtolower($key)];
                    }
                }

                // if $key is false, or $key was not found/is no string:
                return $genericName;
            }
        }
    }

    // not found
    eventLog("WARNING", "Unknown data type: " . $type . " [findDataType()]", false);
    return false;
}


/**
 * findDataTypeUnits($type, $datatypes, $key = "json", $search = Null)
 * 
 * extracts a list of axis units/names for a given datatype ($type) from the $DATATYPES json resource.
 * Includes searching for parent datatype
 * By default it returns the json-names, other names can be requested with the $key.
 * In absence of $search, the default (first listed) entries for each axis will be returned.
 * $search is a list of one alternative name per axis to search for; if found, the function
 * will return the corresponding axis name. If one of the altnames in $search is Null or
 * False, it will return the default axis name for that entry.
 */
function findDataTypeUnits($type, $datatypes, $key = "json", $search = null)
{
    $results = array();
    $defaultkey = "json";  // return json-name if the requested key does not exist.

    // lowercase search array (keys and values)
    if (is_array($search)) {
        $search = array_change_key_case($search);
        $search = array_map('strtolower', $search);
    }
    
    // find generic datatype
    $type = findDataType($type, $datatypes);
    if ($type) {
        foreach ($datatypes[$type]["axis"] as $i => $axis) {  // iterate over x, y, z, t...
            $searchresult = false;

            // if a search array is given: search corresponding value for this axis
            if (is_array($search)) {
                if (isset($search[$i])) {
                    foreach ($axis as $namelist) {      // iterate over different options for each axis (e.g. absorption, transmission...)
                        // make a working copy of the namelist, where we can remove "invert" and convert to lowercase
                        $haystack = $namelist;
                        if (isset($haystack["invert"])) {
                            unset($haystack["invert"]);
                        }
                        $haystack = array_map('strtolower', $haystack);
                        // search in namelist
                        if (in_array($search[$i], $haystack)) {
                            $searchresult = $namelist;
                            break;
                        }
                    }
                }
            }

            // if no search-array was given, or search[$i] was not found: get the name from the first axis option (first namelist)
            if (!$searchresult) {
                $namelist = reset($axis);
            }

            // get the key-name if it exists, else the default key-name, or else the first name in the array
            if (isset($namelist[$key])) {
                $results[$i] = $namelist[$key];
            } elseif (isset($namelist[$defaultkey])) {
                $results[$i] = $namelist[$defaultkey];
            } else {
                $results[$i] = reset($namelist);
            }
        }
    }
            
    return $results;
}


/**
 * orderData(&$data, $sortOrder = null)
 * 
 * sorts a data array based on the first axis value
 * optional parameter $sortOrder can be set to constants SORT_ASC or SORT_DESC, if not set (null),
 * the order will be decided on the first and last x values.
 * 
 * the data array is passed by reference
 * the function returns true if the operation has succeeded, false if failed
 */
function orderData(&$data, $sortOrder = null) {
    
    if (($sortOrder !== SORT_DESC) and ($sortOrder !== SORT_ASC)) {
        $sortOrder = ($data[0][0] < $data[count($data) - 1][0]) ? SORT_ASC : SORT_DESC;
    }
    
    $xValues = array();
    foreach ($data as $couple) {
        $xValues[] = $couple[0];
    }

    return array_multisort($xValues, $sortOrder, SORT_NUMERIC, $data);
}


/**
 * mdate($format, $microtime)
 * 
 * outputs timestamp with a optionally supplied $format (default 'Y-m-d H:i:s.u')
 * optional takes another microtime
 */
function mdate($format = 'Y-m-d H:i:s.u', $microtime = null)
{
    $microtime = explode(' ', ($microtime ? $microtime : microtime()));
    if (count($microtime) != 2) {
        return false;
    }
    $microtime[0] = $microtime[0] * 1000000;
    $format = str_replace('u', $microtime[0], $format);
    
    return date($format, $microtime[1]);
}


/**
 * eventLog($cat, $msg, $fatal = false, $mail = false)
 * 
 * writes an eventmessage ($msg) of category ($cat, eg ERROR, WARNING, ...) to the event log file
 * when optional $fatal is true it will stop all further code execution.
 * when optional $mail is true it will send an email to the sysadmin; or if set to an valid address to this address
 * !! returns $msg !!
 */
function eventLog($cat, $msg, $fatal = false, $mail = false)
{
    //global $logdir, $evlog, $adminMail;
    
    $event = array( "timestamp"   => mdate(),
                    "category"    => strtoupper($cat),
                    "message"     => $msg,
                    "fatal"       => $fatal,
                    "IP"          => $_SERVER['REMOTE_ADDR'] );
    
    // open or create event log file and append line
    $handle = fopen(\Core\Config\App::get("events_log_file"), "a");
    if ($handle) {
        $success = fputcsv($handle, $event);
    } else {
        $success = false;
    }
    if ($success) {
        $success = fclose($handle);
    }
    
    // mail: if asked ($mail=true), and if failed to write log ($success=false)
    if ($mail or !$success) {
        $title = \Core\Config\App::get("app_name") . " event " . $cat;
        $body = "Automated mail from " . gethostname() . ":\r\n\r\n";
        $from = \Core\Config\App::get("mail_admin");
        $headers = "From: " . $from . "\r\n"
            . "Reply-To: " . $from . "\r\n"
            . "X-Mailer: PHP/" . phpversion();
    
        if (!$success) {
            $title .= " - failed to log!";
            $body .= "FAILED TO WRITE TO EVENT LOG FILE\r\n\r\n";
        }
    
        foreach ($event as $key => $value) {
            $body .= $key . ": " . $value . "\r\n";
        }
    
        mail($from, $title, $body, $headers);
    }

    // proceed or die if fatal error
    if ($fatal) {
        die(strtoupper($cat).": ".$msg);
    }
    return $msg;
}


/**
 * bulmaColorModifier($color, $colorList, $default = null)
 * 
 * Returns Bulma color modifier
 *
 * @param mixed $color Color name (without 'is-') or color number in colors.json 
 * @param array $colorList Array of bulma colors
 * @param mixed $default Default color if invalid (optional)
 * @return string Returns Bulma color modifier string (eg is-dark) 
 */
function bulmaColorModifier($color, $colorList, $default = null) 
{
    // seach for $color as number or name
    if (is_numeric($color)) {
        if (isset($colorList[intval($color)])) {
            return "is-" . $colorList[intval($color)];
        }
    }    
    elseif (in_array(strtolower($color), $colorList)) {
        return "is-" . strtolower($color);
    }
    
    // if no answer yet, research for $default color (if supplied)
    if (!is_null($default))
    {
        return bulmaColorModifier($default, $colorList); // no 3rd parameter, else infinite loop is possible
    }
    
    // non-existing color (and default color): no bulma color tag
    return "";
}


/**
 * bulmaColorInt($color, $colorList, $default = null)
 * 
 * Returns color number
 *
 * @param mixed $color Color name (without 'is-') or color number in colors.json 
 * @param array $colorList Array of bulma colors
 * @param mixed $default Default color if invalid (optional)
 * @return mixed Returns color number (or null in case no (valid) default)  
 */
 function bulmaColorInt($color, $colorList, $default = null) 
 {
     // seach for $color as number or name
     if (is_numeric($color)) {
         if (isset($colorList[intval($color)])) {
             return intval($color);
         }
     }    
     elseif (in_array(strtolower($color), $colorList)) {
         return array_search (strtolower($color), $colorList);
     }
     
     // if no answer yet, research for $default color (if supplied)
     if (!is_null($default))
     {
         return bulmaColorInt($default, $colorList); // no 3rd parameter, else infinite loop is possible
     }
     
     // non-existing color (and default color): null
     return null;
 }


 /**
 * selectConvertorClass($convertors, $datatype, $format, $options = array())
 * 
 * select an export or import convertor based on the supplied datatype and format
 * 
 * It reads the import.json or export.json configuration file and searches the listed convertors based on
 * the two criteria: datatype and format. Searching happens in the order the convertors
 * are listed in the file, and only the first hit will be reported.
 * 
 * The format can be a file extension, or a downloadconverted library setting item in the form of "[convertor:[datatype:]]extension".
 * 
 * The optional $options array contains convertor options, as can be supplied in the CSV
 * metadata files ("options:import:jcampdx:templatefile" --> $options = $measurement["options"]["import"]), and will be
 * supplemented with options defined in the import.json/export.json file for the given extension and datatype.
 * If the same parameter with different value is defined in multiple places, than the value defined
 * in the metadata wins over the value in extension, which in turn wins over the value in datatype.
 * 
 * If a convertor is found, this function returns an associative array; the first item (with key "convertor")
 * contains the name of the convertor. Next items are the options for this convertor 
 * (eg $options["templatefile"] = "Raman785.dxt").
 * If no convertor is found, an empty array is returned.
 */
function selectConvertorClass($convertors, $datatype, $format, $options = array())
{
    // cleanup parameters
    $datatype = trim(strtolower($datatype));
    $format = trim(trim(strtolower($format), "."));

    $format = explode(":", $format, 3);
    $format_ext = end($format);
    if (count($format) == 3) {
        $format_conv = $format[0];
        $format_type = $format[1];
    } elseif (count($format) == 2) {
        $format_conv = $format[0];
    }

    // search the convertors array until we find a convertor that fits the requirements (datatype and extension)
    foreach ($convertors as $key => $requirements) {
        $condition = true;
        if (isset($format_conv)) {
            $condition = ($condition and ($key == $format_conv));
        }
        if (isset($format_type)) {
            $condition = ($condition and ($datatype == $format_type));
        }
        if (isset($requirements["datatypes"])) {
            $condition = ($condition and isset($requirements["datatypes"][$datatype]));
        }  
        if (isset($requirements["extensions"])) {
            $condition = ($condition and isset($requirements["extensions"][$format_ext]));
        }
        if ($condition) {
            $convertor = $key;
            break;
        }
    }
    
    // evaluate convertor options. these can be supplied for specific datatypes, specific file extensions or supplied in the uploaded metadata
    // return array with first key "convertor", followed by the options for this convertor
    if (isset($convertor)) {
        $options = array_change_key_case($options, CASE_LOWER);
        if (isset($options[$convertor])) {
            // only keep the options for this convertor
            $options = $options[$convertor];
        }
        else {
            $options = array();
        }
        // add options from the extension (if they are not already set by the metadata)
        if (isset($convertors[$convertor]["extensions"][$format_ext])) {
            $options = array_merge($convertors[$convertor]["extensions"][$format_ext], $options);
        }
        // add options from the datatype (if they are not already set by the metadata or extension)
        if (isset($convertors[$convertor]["datatypes"][$datatype])) {
            $options = array_merge($convertors[$convertor]["datatypes"][$datatype], $options);
        }
        
        return array("convertor" => $convertor) + $options; 
    }
    else {
        // if no suitable convertor found: return false
        return false;
    }
}

/**
 * getAllowedExtensions($list)
 * 
 * reads a list from the library field "downloadbinary"
 * and outputs an array of allowed extensions, true (if _ALL is in the list), or false (if _NONE is in the list, the list is empty or not set)
 * 
 * NEEDS TO MOVE TO LIBRARY CLASS!!
 */
function getAllowedExtensions($list)
{
    if (isset($list)) {
        if (is_array($list)) {
            if (in_array("_NONE", $list) or empty($list)) {
                return false;
            } elseif (in_array("_ALL", $list)) {
                return true;
            } else {
                return $list;
            }
        }
    }

    // in all other cases
    return false;
}

