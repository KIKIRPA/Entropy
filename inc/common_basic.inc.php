<?php

  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }
  

/* ****************************************************************************************************************************
      
      logout()                              close session
      
      sanitizeStr($string, $replaceby = "_", $others = False, $lowercase = False)
          sanitize string, removes all characters, html-tags... that should not be there
          eg. string to be used as a part of a filename, or for loose string comparisons
          $others is other characters to replace, e.g. "-+:^"
      
      calcPermMod($permtable, [$lib])
          returns the allowed modules for a given user. 
          requires a user permissions table (array, part of users.json)
          if a library id is given, it will output only the library-specific modules (array) to which a user has access (for that library)
          if no library is given, it will output only the admin-specific modules (array) to which a user has access
          !! if user is administrator it will return true (=access to all)
          !! if user has no permissions at all (for that library), it will return false
      
      calcPermLib($permtable, $mod, [$lib]) 
          returns for a given module which libraries are allowed
          requires a user permissions table (array, part of users.json)
          if no library is given: outputs true for all libs, false for no libs or an array with the lib ids
          if a library is given: outputs true or false
      
      readJSONfile($path, $dieOnError)
          reads json file and outputs as an array
          if something goes wrong (file does not exist, not readable or contains error)
          it will output an empty array (in order to create new file
          optional $dieOnError will in this case stop further code execution
                                             
      inflateArray($array, $mode = -1, $keysep = ":", $fieldsep = "|")
    
      flattenArray($array, $key = false, $mode = -1, $keysep = ":", $fieldsep = "|")
                                             
      getMeta($metadata, $get, $concatenate = "; ", $description = ": ")
          retrieve metadata-item from (inflated) metadata array
          $get is something like "sample:sample name", "samplesource:primary:identifier+source", "measurement:date^long";
          if multiple fields need to be concatenated, the concatenation symbol (default ;) can be supplied
          in the concatenated outputs descriptions can be added if a $description symbol (default :) is supplied
          (if set to false, a short notation will be used without descriptions)
      
      nameMeta($get)
          get a nice name for a metadata retrieve query string
          if $get is $get is something like "sample:sample name", "samplesource:0:identifier+source", "measurement:date^long"
          output will be resp. "Sample name", "Samplesource 1" and "Date"
      
      overrideMeta($metadata, $dataset = False)
          merge/override directly stored metadata, with metadata stored in
          "meta:", and optionally with metadata specific to a dataset
          returns merged metadata (analytical data is stripped)

      datatypeParent($type, $datatypes)
          if $type is an alias, it recursively looks for the parent (but checks for circular aliassing:
          e.g. "Raman 785nm" is alias for "Raman", which is an alias for "Raman 785nm"). 

      datatypeUnits($type, $datatypes, $altnameslist = Null)
          extracts a list of axis units for a given datatype ($type) from the $DATATYPES json resource.
          Includes searching for parent datatype (using datatypeParent())
          In absence of $altnameslist, the default (first listed) entries for each axis will be returned.
          $altnameslist is a list of one alternative name per axis to search for; if found, the function
          will return the corresponding axis name. If one of the altnames in $altnamelist is Null or 
          False, it will return the default axis name for that entry. 
      
      mdate($format, $microtime)            outputs timestamp with a optionally supplied $format (default 'Y-m-d H:i:s.u')
                                             optional takes another microtime
      
      eventLog($cat, $msg, $fatal = false, $mail = false)   
          writes an eventmessage ($msg) of category ($cat, eg ERROR, WARNING, ...) to the event log file
          when optional $fatal is true it will stop all further code execution.
          when optional $mail is true it will send an email to the sysadmin; or if set to an valid address to this address
          !! returns false (if not fatal)!!
    
   **************************************************************************************************************************/
  
  
  function logout()
  {
    $_SESSION = array();
    session_destroy();
  }
  
  
  function sanitizeStr($string, $replaceby = "_", $others = False, $lowercase = False)
  {
    $replace = str_split(" _!\"#$%&'()*,/;<=>?@[\\]`{}~");
    
    if ($others) $replace = array_merge($replace, str_split($others));

    $string = str_replace($replace, $replaceby, $string);
    if ($replaceby <> "")
      $string = preg_replace('/' . $replaceby . '{2,}/', $replaceby, $string);  // replace multiple underscores by a single
    $string = trim($string, "_");
    
    if ($lowercase) $string = strtolower($string);
    
    return filter_var($string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
  }


  function detect_bom_encoding($str)
  {
    if ($str[0] == chr(0xEF) && $str[1] == chr(0xBB) && $str[2] == chr(0xBF)) 
      return array(substr($str, 3), 'UTF-8');
    elseif ($str[0] == chr(0x00) && $str[1] == chr(0x00) && $str[2] == chr(0xFE) && $str[3] == chr(0xFF))
      return array(substr($str, 4), 'UTF-32BE');
    elseif ($str[0] == chr(0xFF) && $str[1] == chr(0xFE)) 
    {
      if ($str[2] == chr(0x00) && $str[3] == chr(0x00))
        return array(substr($str, 4), 'UTF-32LE');
      return array(substr($str, 2), 'UTF-16LE');
    }
    elseif ($str[0] == chr(0xFE) && $str[1] == chr(0xFF))
      return array(substr($str, 2), 'UTF-16BE');
    else
    {
      $enc = mb_detect_encoding($str, 'auto', true);
      if ($enc) return array($str, $enc);
      else return array($str, 'Windows-1252'); //fall-back
    }
  }
  
  
  function calcPermMod($permtable, $lib = false)
  {
    if ($permtable["admin"])
      return true;
    
    $arr = array();
    
    foreach ($permtable as $mod => $modperms)
    {
      // if library is supplied: only work on library modules
      // if NO library is supplied: only work on administration modules
      
      if ($lib and is_array($modperms))
      {
        //if in array $lib or _ALL, but not _NONE (the latter takes priority!!!): 
        if ( (in_array(strtolower($lib), $modperms) or in_array("_ALL", $modperms))
             and !in_array("_NONE", $modperms)
           )
          array_push($arr, $mod);
      }
      elseif (!$lib and !is_array($modperms))
      {
        if (filter_var($modperms, FILTER_VALIDATE_BOOLEAN))
          array_push($arr, $mod);
      }
    }
    
    if (!empty($arr)) return $arr;
    else return false;
  }
  
  
  function calcPermLib($permtable, $mod, $lib = false)
  {
    if ($permtable["admin"])
      return true;
      
    // $permtable[$mod]: is the module in the permission table?
    // - if it is an array, check for _NONE, _ALL, or the list of libs (in this priority)
    //    if a lib is supplied, just answer true or false
    // - if it is not an array, evaluate it as a boolean (admin modules)
    
    if (isset($permtable[$mod]))
    {
      if (is_array($permtable[$mod]))
      {
        if     (in_array("_NONE", $permtable[$mod])) return false;
        elseif (in_array("_ALL", $permtable[$mod]))  return true;
        elseif (empty($permtable[$mod]))             return false;
        elseif ($lib)                                return (in_array($lib, $permtable[$mod]));
        else                                         return $permtable[$mod];
      } 
      else return filter_var($permtable[$mod], FILTER_VALIDATE_BOOLEAN);
    }
    
    // if we are not returned allready, do it now (with no permissions)
    return false;
  }
  
  
  function readJSONfile($path, $dieOnError = false)
  {
    $array = array();
    if (file_exists($path))
    {
      $array = file_get_contents($path);
      $array = json_decode($array, true);
    }
    
    if (empty($array) and $dieOnError)
      eventLog("ERROR", "could not read " . pathinfo($path,  PATHINFO_FILENAME) . " file", true, true);

    return $array;
  }
  
  
  function inflateArray($array, $mode = -1, $keysep = ":", $fieldsep = "|", $i = 0)
  {
    $out = array();
    
    foreach ($array as $key => $value)
    {
      //make this function recursive and should work on partially inflated arrays:
      //if a $value itself is an array, dive into it
      //can only work sensibly on $mode -1 or 0 (full keyseparation)
      if (is_array($value) and (($mode < 1) or ($i < $mode)))
        $value = inflateArray($value, $mode, $keysep, $fieldsep, $i+1);
      
      //fieldseparation (eg. allowformats=spc|dx|txt)   
      elseif ($mode = -1)
        if (substr_count($value, $fieldsep) > 1)
          $value = explode($fieldsep, $value);
      
      //keyseparation and key/value-combination
      if ($mode > 0) $key = explode($keysep, $key, $mode);
      else           $key = explode($keysep, $key);
      
      if (count($key) > 1)
      {
        foreach (array_reverse($key) as $part)
        {
          $arr = array();
          $arr[$part] = $value;
          $value = $arr;
        }
        $out = array_replace_recursive($out, $value);
      }
      else 
        $out[$key[0]] = $value;
      
    }
    return $out;
  }
   

   
  function flattenArray($array, $multirecords = false, $mode = -1, $keysep = ":", $fieldsep = "|")
  {
    /*
      flatten array: outputs array of records in which these records have a flat structure
        - $multirecords (default false): first level keys are record id's and should not be flattened      
        - $mode: 0            --> completely flat structure with keyseparation
                 -1 (default) --> completely flat structure with keyseparation and fieldseparation if integer keys on the deepest level
                 1, 2...      --> (possibly incomplete) keyseparation for 1, 2... iterations only 
        - keyseparation: <samplename:C.I. number>   --> $keysep default ":"
        - fieldseparation: <allowformats>spc|dx|txt --> $fieldsep default "|"
    */
    
    $i = 0;
      
    do
    {
      $proceed = false;
      if ($multirecords)
      {  
        foreach ($array as $id => $record)
        {    
          foreach ($record as $key1 => $field1)
          {
            if (is_array($field1))
            {
              $proceed = true;
              foreach ($field1 as $key2 => $field2)
              {
                if (is_int($key2) and !(is_array($field2)) and ($mode == -1))
                  $array[$id][$key1] = implode($fieldsep, $field1);
                else
                {
                  $array[$id][$key1 .$keysep . $key2] = $field2;
                  unset($array[$id][$key1]);
        } } } } }
      }
      else
      {
        foreach ($array as $key1 => $field1)
        {
          if (is_array($field1))
          {
            $proceed = true;
            foreach ($field1 as $key2 => $field2)
            {
              if (is_int($key2) and !(is_array($field2)) and ($mode == -1))
                $array[$key1] = implode($fieldsep, $field1);
              else
              {
                $array[$key1 .$keysep . $key2] = $field2;
                unset($array[$key1]);
        } } } } }
      
      if (($mode > 0) and (++$i >= $mode)) $proceed = false;  // stop after $mode iterations if $mode is set to positive.
    }
    while ($proceed);
    
    return $array;
  }
  

  function getMeta($metadata, $get, $concatenate = "; ", $description = ": ")
  {
    //split get-code into hierachical tree
    $tree = explode(":", $get);
    $n = count($tree);
    
    //split the "leaves" from the tree: the actual fields to be retrieved
    $leaves = explode("+", $tree[$n - 1]);
    unset($tree[$n - 1]);
    
    //split the notation from the fields to be retrieved
    foreach ($leaves as $id => $leave)
    {
      $arr = explode("^", $leave);
      if (count($arr) > 1)
      {
        $leaves[$id] = $arr[0];
        $shapes[$arr[0]] = $arr[1]; //if $arr[2+] exist we'll neglect it; we can only have one notation
      }
      else $shapes[$id] = NULL;
    }
    
    //reduce the metadata step by step
    //first the branch in the tree
    if (count($tree) > 0)
      foreach ($tree as $branch)
        $metadata = $metadata[$branch];
    
    //next the leaves
    $arr = array();
    foreach ($leaves as $leave)
      if (isset($metadata[$leave])) $arr[$leave] = $metadata[$leave];
      else                          $arr[$leave] = null;
    
    //flatten the resulting thing, even if the "leaves" itselve are arrays
    $metadata = flattenArray($arr, false, 0);
    
    //formatting: add descriptions (eg "age: 1900") and formatting options (eg. date^year)
    foreach ($metadata as $id => $value)
    {
      $id2 = explode(":", $id);    //break down the flattened description, and only keep the last part
      $id2 = $id[count($id2) - 1];  // eg. "sample:age" --> "age"

      // formatting options, eg for timestamps
      if (array_key_exists(strtolower($id2), $shapes) and !is_null($value))
        if (strtolower($id2) === "timestamp")
        {
          $ts = new DateTime($value);
          switch ($shapes[strtolower($id)])
          {
            case "long":
            case "longdate":
              $value = $ts->format('Y/m/d');
              break;
            case "short":
            case "shortdate":
              $value = $ts->format('y/m/d');
              break;
            case "year":
              $value = $ts->format('Y');
              break;
            case "time":
              $value = $ts->format('H:i:s');
              break;
            default:
              $value = $ts->format('Y/m/d H:i:s');
          }
        }
    
      // descriptions
      if ($description != False)
        $value = ucfirst($id2) . $description . $value;
      
      $metadata[$id] = $value;
    }
    
    //and implode into a single string
    return implode($concatenate, $metadata);
  }
  
  
  function nameMeta($get)
  {
    //split get-code into hierachical tree
    $tree = explode(":", $get);
    
    //last item in the tree is the name, except if it contains a "+"
    $name = array_pop($tree);
    if (strpos($name, "+") !== false)
      $name = array_pop($tree);    
    
    //remove formatting parts 
    if (strpos("^", $name) === false)
    {
      $temp = explode("^", $name );
      $name = $temp[0];
    }
    
    //if last item in the tree is numeric
    //samplesource:0 --> samplesource 1
    if (is_numeric($name))
    {
      $temp = (int)$name + 1;
      $name = array_pop($tree) . " " . $temp;
    }
    
    // some fancier hard-coded names for columns can be defined here
    $name = str_replace("samplesource", "Sample source", $name); 
    
    //make it nice: replace underscores with spaces, first letter uppercase
    $name = str_replace('_', ' ', $name);
    return ucfirst($name);
  }
  
  
  function overrideMeta($metadata, $dataset = False)
  {
    // get metadata stored in :meta, that needs to override direct metadata
    if (isset($metadata["meta"]))
      $metameta = $metadata["meta"];
    else
      $metameta = array();
    
    // get dataset-specific metadata, that needs to override all other metadata
    if ($dataset and isset($metadata[$dataset]["meta"]))
      $dsmeta = $metadata[$dataset]["meta"];
    else 
      $dsmeta = array();

    // remove meta and dataset things from metadata --> only direct metadata
    unset($metadata["meta"], $metadata["dataset"]);
    
    return array_replace_recursive($metadata, $metameta, $dsmeta);
  }


  function datatypeParent($type, $datatypes)
  {
    // types can be aliassed against a parent, which can itself be aliassed against another type.
    // potentially we can end up in a loop, which would cause the recursive parent lookup to end up in an eternal loop.
    // a list of child types will be made to prevent this from happening.
    $children = array();

    if (!isset($DATATYPES[$type]))  // should be checked and autocorrected before; just in case
      eventLog("ERROR", "Unknown data type: " . $type . " [datatypeParent()]", True);

    // recursively find parent datatype
    while (1)
    {
      if (isset($DATATYPES[$type]["alias"]))
      {
        if (!in_array($type))
        {
          $children[] = $type;
          $type = $DATATYPES[$type]["alias"];
        }
        else
          eventLog("ERROR", "Circular aliassing detected in the Datatypes settings: " . $type . " [datatypeParent()]", True);
      }
      else
        return $type;
    }
  }
  

  function datatypeUnits($type, $datatypes, $altnameslist = Null)
  {
    // find parent type:
    $type = datatypeParent($type, $datatypes);

    // find axis names
    $results = array();
    $i = 0;
    foreach ($DATATYPES[$type]["axis"] as $axid => $axis)
    {
      if (is_array($altnameslist))
        if (!empty($altnameslist[$i]))
          foreach ($axis as $axname => $axvalues)
            if ((strtolower($axname) == strtolower($altnameslist[$i])) or in_array(strtolower($altnameslist[$i]), $axvalues["altnames"]))
            {
              $results[$i] = $axname;
              break;
            }
      
      if (!isset($results[$i])) // if no altname is supplied for this axis, or altname was not found; take the first axis (first is default)
      {
        reset($axis);
        $results[$i] = key($axis);
      }
    }

    return $results;
  }
  
  
  function mdate($format = 'Y-m-d H:i:s.u', $microtime = null) 
  {
    $microtime = explode(' ', ($microtime ? $microtime : microtime()));
    if (count($microtime) != 2) return false;
    $microtime[0] = $microtime[0] * 1000000;
    $format = str_replace('u', $microtime[0], $format);
    
    return date($format, $microtime[1]);
  }
  
  
  
  function eventLog($cat, $msg, $fatal = false, $mail = false)
  {
    //global $logdir, $evlog, $adminMail;
    
    $event = array( "timestamp"   => mdate(), 
                    "category"    => strtoupper($cat), 
                    "message"     => $msg, 
                    "fatal"       => $fatal,
                    "IP"          => $_SERVER['REMOTE_ADDR'] );
    
    // open or create event log file and append line
    $handle = fopen(LOG_EV_FILE, "a");
    if ($handle) $success = fputcsv($handle, $event);
    else 	 $success = false;
    if ($success) $success = fclose($handle);
    
    // mail: if asked ($mail=true), and if failed to write log ($success=false)
    if ($mail or !$success)
    {
      $title = APP_SHORT . " event " . $cat;
      $body = "Automated mail from " . gethostname() . ":\r\n\r\n";
      $headers = "From: " . MAIL_ADMIN . "\r\n" 
               . "Reply-To: " . MAIL_ADMIN . "\r\n" 
               . "X-Mailer: PHP/" . phpversion();
      
      if (!$success)
      {
        $title .= " - failed to log!";
        $body .= "FAILED TO WRITE TO " . LOG_EV_FILE ."\r\n\r\n";
      }
      
      foreach ($event as $key => $value) $body .= $key . ": " . $value . "\r\n";
      
      mail(MAIL_ADMIN, $title, $body, $headers);
    }
  
    // proceed or die if fatal error
    if ($fatal) die(strtoupper($cat).": ".$msg);
    return false;
  }
