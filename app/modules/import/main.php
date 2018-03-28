<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

require_once(PRIVPATH . 'inc/common_upload.inc.php');
require_once(PRIVPATH . 'inc/common_convert.inc.php');

$notifications = array();
$themeColor = isset($LIBS[$showLib]["color"]) ? bulmaColorModifier($LIBS[$showLib]["color"], $COLORS, \Core\Config\App::get("app_color_default")) : bulmaColorModifier(\Core\Config\App::get("app_color_default"), $COLORS);


/****************************
*                           *
*  COMMON TASKS             *
*                           *
****************************/


# check if the transaction already exists and set $transaction
# also check if the user has permission to this transaction

$action = false;

$libraryPath = \Core\Config\App::get("libraries_path") . $_REQUEST["lib"] . "/";

if (!file_exists($libraryPath)) {
    if (!mkdir2($libraryPath)) {
        echo "<span style='color:red'>ERROR: Could not make library directory for: " . $id . "!</span><br><br>\n";
        eventLog("ERROR", "Could not make library directory for: " .$id . " [module_import]", true, false);
    }
}

// if (file_exists($libraryPath . "transactions_open.json")) {
//     $transactions = readJSONfile($libraryPath . "transactions_open.json", false);
// } else {
//     $transactions = array();
// }

try {
    if (isset($_REQUEST["tr"])) { // open an existing transaction
        // check if it exists
        $transaction = new Core\Lib\Transaction($_REQUEST["lib"]);

        if ($transaction->search($_REQUEST["tr"])) {
            throw new \Exception('Invalid transaction ID: ' . $_REQUEST["tr"]);
        }
    
        // check if this user permission
        //if (!$user["permissions"]["admin"] and ($transaction->user] != $isLoggedIn)) {
        //    throw new \Exception('Unauthorised access to transaction ' . $transaction->id;);
        //}
    
        // check if the transaction dir exists
        $trPath = $libraryPath . $transaction->id . "/";
        if (!file_exists($trPath)) {
            throw new \Exception('Upload directory was not found for '. $transaction->id);
        }
    }
} catch (\Exception $e) {
    $errormsg = $e->getMessage();
    eventLog("ERROR", $errormsg  . " [module_import: COMMON]");
    echo "    <span style='color:red'>ERROR: " . $errormsg . "</span><br><br>";
    goto STEP1;
}


$action = (isset($_REQUEST["task"])) ? $_REQUEST["task"] : "massupload";
switch ($action) {
    case "transactionlist":
        include_once(__DIR__ . '/transactionlist.php');
        break;
    case "delete":
        break;
    case "massupload":
        include_once(__DIR__ . '/massupload.php');
        break;
    case "append":
    case "update":
    case "replace":
        break;
    case "list":
        break;
    case "dataupload":
        break;
    case "build":
        break;
    case "merge":
        break;
}

die();








  

/****************************
*                           *
*  2 PROCESS UPLOAD         *
*                           *
****************************/
  
STEP2:
{
    try {
        $action = $_REQUEST["task"];
      
        // check and move uploaded file
        $error = checkUpload('upfile', $trPath, "_1_" . $action . ".csv");
        if ($error) {
            throw new \Exception($error);
        }
      
        // read CSV file
        if (($handle = fopen($trPath . "_1_" . $action . ".csv", "r")) == false) {
            throw new \Exception('Could not open the CSV file.');
        }
      
        // interpret CSV header
        $line = fgets($handle);
        list($line, $enc) = detectBomEncoding($line);   // detect BOM encoding
        $line = mb_convert_encoding($line, "UTF-8", $enc);  // convert to UTF-8
        $delimiter = false;
        $delimiters = array(",", ";", "\t", "|");
        $required = array('id', 'type');    // required field in the header
        foreach ($delimiters as $d) {
            $header = str_getcsv($line, $d, '"', "\\");                       // try breaking it down with delimiter $d
            $header = array_map('trim', $header);                             // trim all items
            $temp = array_map('strtolower', $header);                         // lowercase all items
            if (count(array_intersect($temp, $required)) == count($required)) { // if all required items are in $test
                $delimiter = $d;
                foreach ($required as $r) {
                    $header[array_search($r, $temp)] = $r;
                }  // make sure the required fields are consequently written (lowercase)
                break;
            }
        }
        if (!$delimiter) {
            throw new \Exception('Could not interpret the CSV file (could not find all required fields: ' . implode(', ', $required) . ').');
        }

        // Read all measurements into an array
        $key = 'id';
        $measurments = array();
        while ($line = fgets($handle)) {
            if (substr($enc, 0, 6) == 'UTF-16') {
                $line = substr($line, 1);
            }  // fgets is probably confused by UTF-16 when reading newlines; this seems to do the trick
            $line = mb_convert_encoding($line, "UTF-8", $enc);            // convert to UTF-8
            $line = str_getcsv($line, $delimiter, '"', "\\");
            $temp = array();
            foreach ($line as $i => $value) {
                if ($header[$i] == $key) {
                    $id = $value;
                } elseif (!empty($header[$i])) {
                    $temp[$header[$i]] = $value;
                } //columns with empty header names (NULL or "") are removed
            }

            if (!empty($id)) {  // do not consider lines (rows) with empty id fields: can be an error (measurements without id), or empty lines. Just reject all of these without error msg
                if (!isset($measurements[$id])) {
                    $measurements[$id] = $temp;
                }   //add this line to the measurements
                else {
                    throw new \Exception('Error in the CSV file: ' . $key . ' "' . $id . '" occurs more than once.');
                }
            }
            unset($id);
        }

        if (isset($measurements[""])) {
            unset($measurements[""]);
        }

        // close CSV file
        fclose($handle);
      
        // Save .json
        $error = writeJSONfile($trPath . "_2_flat.json", $measurements);
        if ($error) {
            throw new \Exception($error);
        }
      
        // Update transactions_open.json
        $transactions[$tr]["action"] = $action;
        $transactions[$tr]["step"] = 3;
        $error = writeJSONfile($libraryPath . "transactions_open.json", $transactions);
        if ($error) {
            throw new \Exception($error);
        }
    } catch (\Exception $e) {
        $errormsg = $e->getMessage();
        eventLog("ERROR", $errormsg . " [module_import: STEP2]");
        echo "    <span style='color:red'>ERROR: " . $errormsg . "</span><br><br>";
        goto STEP1;
    }
}


/****************************
*                           *
*  3 LIST CHECK VIEW        *
*                           *
****************************/
  // two ways to reach step 3:
  //  - following step 2 (processing)  -> $tr, $action, $measurements set during step 2
  //  - opening a saved transaction    -> $tr, $action, $measurements set in the common tasks (derived from $_REQUEST["tr"])
  
STEP3:
{
    /* Data verification: warnings and errors (here or in step 3)??
      - id: present and unique
      - type: present (error) and known (warning)
      - main metadata categories: sample, samplesource, instrument, parameters, contributor, jcampdxtemplate, data
      - fields required by libs.json
      - empty fields (warnings)
    */

    $existing = readJSONfile($libraryPath . "measurements.json");  //if file does not exist: empty array
    $existing = array_keys($existing);
    
    //unset($measurements[""]);   //remove empty rows
    
    $a = 0; // automatically corrected issues (green bg)
    $b = 0; // non-blocking warnings (orange bg)
    $c = 0; // blocking errors (red bg)
    
    $fields = array_keys(current($measurements));
    //$fields = array_filter($fields);  //remove empty fields
    
    echo "    <h4>STEP 2: review the measurement list</h4>\n";
    
    echo "    <strong><br>\n"
       . "      library: " . $_REQUEST["lib"] . "<br>\n"
       . "      transaction: " . $tr . "<br>\n"
       . "      action: " . $action . "\n"
       . "    </strong><br><br>\n";
    
    ?>
    <script type="text/javascript" charset="utf-8">
      $(document).ready(function() {
        var oTable = $('#datatable').dataTable( {
          //"sScrollY": "300px",
          "bPaginate": false,
          "bScrollCollapse": true,  
        } );
      } );
    </script>
    
    <div style="overflow-x: scroll; font-size: 60%;">
    <table id="_datatable">
      <thead>
        <tr>
          <th>action</th>
          <th>id</th>
          <?php foreach ($fields as $th) {
        echo "          <th>" . str_replace(":", ":<br>", $th) . "</th>\n";
    } ?>
        </tr>
      </thead>
      <tbody>
    <?php 
   
    foreach ($measurements as $id => $measurement) {
        // start with adding missing required fields (defined in libs.json)
        unset($measurements[$id][""]);   //remove empty columns
        
        echo "        <tr>\n";
        $id_sani = sanitizeStr($id);
      
        // 1. CHECK ACTION
        switch ($action) {
            case "replace":
                $measurements[$id]["_action"] = "R";
                break;
            case "update":
                if (count($existing) > 0) {
                    if (in_array($id_sani, $existing)) {
                        $measurements[$id]["_action"] = "U";
                    } else {
                        $measurements[$id]["_action"] = "A";
                    }
                } else {
                    $measurements[$id]["_action"] = "A";
                }
                break;
            case "append":
            default:
                if (count($existing) > 0) {
                    if (in_array($id_sani, $existing)) {
                        $measurements[$id]["_action"] = "!";
                    } else {
                        $measurements[$id]["_action"] = "A";
                    }
                } else {
                    $measurements[$id]["_action"] = "A";
                }
        }
        if ($measurements[$id]["_action"] != "!") {
            echo "          <td>" . $measurements[$id]["_action"] . "</td>\n";
        } else {
            echo "          <td style='background-color: red;'><div tooltip='This measurement already exists in this library!'>!</div></td>\n";
            $c++;
        }
      
        // 2. CHECK ID
        if ($id != $id_sani) {
            //rename key $id in $measurements without changing the order
            $keys = array_keys($measurements);
            $keys[array_search($id, $keys)] = $id_sani;
            $measurements = array_combine($keys, $measurements);
            unset($keys);
        
            //code green! [removed illegal characters]
            echo "          <td style='background-color: green;'><div tooltip='Automatically removed illegal characters'>".$id_sani."</div></td>\n";
            $a++;
        } else {
            echo "          <td>".$id."</td>\n";
        }
   
        // 3. PARAMETER FIELDS
        foreach ($measurement as $param => $value) {
            $tt = array();
            $col = "";
            $param_sani = sanitizeStr($param, "", "-+^", 1);
        
            switch ($param_sani) {
                case "type":
                    //check if type is set and exists in datatypes.json!!
                    $val_sani = findDataType(sanitizeStr($value, "", "-+:^", 1), $DATATYPES, "alias");
                    if (!$val_sani) { // type in CSV does not exist! (findDataType() returned false)
                        $c++;
                        $col = "red";
                        array_push($tt, "[ERROR] Undefined data type");
                    } elseif ($val_sani != $value) { //if the value in the CSV is not identical to the one in dataformat.json, autocorrect
                        $a++;
                        $col = "green";
                        array_push($tt, "[NOTE] Automatically corrected type");
                        $measurements[$id][$param] = $value = $val_sani;
                    }
                    // no break
                default:
                    $val_sani = trim($value);
                    if ($val_sani != $value) {
                        $a++;
                        $col = "green";
                        array_push($tt, "[NOTE] Automatically removed trailing spaces");
                        $measurements[$id][$param] = $value = $val_sani;
                    }
                    
                    // check for fields required by LIBS.JSON
                    // NOTE: disabled because not equaly sanitized, and not taking into combined(+) and notations(^)
                    //if (empty($value) and in_array($value, $LIBS[$_REQUEST["lib"]]["columns"]))  //
                    //{
                    //  $b++;
                    //  $col = "orange";
                    //  array_push($tt, "[WARNING] Empty while required in the library settings");
                    //}
                    
                    // check for nonempty fields with nonempty subfields
                    if (!empty($value)) {
                        $val_sani = false;
                        foreach ($fields as $field) {
                            if (!empty(trim($measurement[$field])) and strstr($field, $param_sani . ":")) {
                                $val_sani = true;
                            }
                        }
                        if ($val_sani) {
                            $c++;
                            $col = "red";
                            array_push($tt, "[ERROR] Nonempty field with nonempty subfield");
                        }
                    }
                    break;
            }
        
            // check dataset fields jcampdxtemplate and type
            if (substr($param_sani, 0, 8) === "dataset:") {
                if (substr($param_sani, -10) === ":meta:type") {
                    $val_sani = findDataType(sanitizeStr($value, "", "-+:^", 1), $DATATYPES, "alias");
                    if (!$val_sani) { // type in CSV does not exist! (findDataType() returned false)
                        $c++;
                        $col = "red";
                        array_push($tt, "[ERROR] Undefined data type");
                    } elseif ($val_sani != $value) { //if the value in the CSV is not identical to the one in dataformat.json, autocorrect
                        $a++;
                        $col = "green";
                        array_push($tt, "[NOTE] Automatically corrected type");
                        $measurements[$id][$param] = $value = $val_sani;
                    }
                }
            }
        
            if (empty($value)) {
                $value = "[NULL]";
            }
        
            if ($col == "") {
                echo "          <td>". $value . "</td>\n";
            } else {
                echo "          <td style='background-color: " . $col . ";'><div tooltip='" . implode(" ; ", $tt) . "'>" . $value . "</td>\n";
            }
        }
      
        echo "        </tr>\n";
    }
    
    ?>
      </tbody>
    </table>
    </div>
    
    <div>
      <b>
        Autocorrected issues: <?= $a ?><br>
        Non-fatal warnings: <?= $b ?><br>
        Fatal errors: <?= $c ?>
      </b>
    </div><br><br>
    
    <?php

    $error = writeJSONfile($trPath . "_2_flat.json", $measurements);
    if ($error) {
        eventLog("ERROR", "could not save autocorrected json [module_import: STEP3]");
    }
        
    // CONTINUE BUTTON
    if ($c == 0) {
        echo "        <form action='" . $_SERVER["SCRIPT_NAME"] . "?mod=import&lib=" . $_REQUEST["lib"] . "&tr=" . $tr . "&step=4" . "' method='POST'>\n"
           . "          <input type='submit' value='Next >' />\n"
           . "        </form>\n";
    } else {
        echo "        There are errors. Please correct the original file and reupload. <a href='" . $_SERVER["SCRIPT_NAME"] . "?mod=import&lib=" . $_REQUEST["lib"] . "'>Return</a>\n";
    }


    //FOOTER
    include(PRIVPATH . 'inc/footer.inc.php');
    
    return;   // return to the main php page
  }

  
/****************************
*                           *
*  4 PROCESS LIST           *
*                           *
****************************/

  STEP4:
  {
    // transform the flat $measurements into a hierarchical array; safe it into inflated.json
    foreach ($measurements as $i => $measurement) {
        foreach ($measurement as $j => $field) {
            if (empty($field)) {
                unset($measurements[$i][$j]);
            }
        }
    }
    $measurements = inflateArray($measurements, 0);
    
    // If a dataset-field is missing, we need to add at least a "default" dataset
    //            dataset:default:meta (to contain dataset-specific metadata)
    //                NOTE any dataset-specific metadata will overrule the common metadata (array_replace|merge_recursive?)
    //                     when displayed on the website or exported to JCAMP-DX
    //            dataset:default:units
    //            dataset:default:data
    //            dataset:default:annotations
    
    // define the datasets
    // NOTE at the moment we only consider datasets defined in the CSV-file,
    //      or, if none are specified the "default"-dataset
    $datasets = array();
    foreach ($measurements as $id => $measurement) {
        if (!isset($measurement["datasets"])) {
            $measurements[$id]["datasets"]["default"] = array();
            if (!in_array("default", $datasets)) {
                array_push($datasets, "default");
            }
        } else {
            foreach ($measurement["datasets"] as $datasetid => $value) {
                if (!in_array($datasetid, $datasets)) {
                    array_push($datasets, $datasetid);
                }
            }
        }
    }
    $measurements["_datasets"] = $datasets;
    
    try {
        // Save inflated $measurements as .json
        $error = writeJSONfile($trPath . "_3_inflated.json", $measurements);
        if ($error) {
            throw new \Exception($error);
        }
      
        //make transaction $list with keys from $columns and values retrieved from $measurements
        $columns = $LIBS[$_REQUEST["lib"]]["listcolumns"];
        $list =  array();
        foreach ($measurements as $id => $measurement) {
            if ($id != "_datasets") {
                //$measurement = overrideMeta($measurement); //fold "meta:" metadata together with direct metadata
                foreach ($columns as $column) {
                    $list[$id][$column] = getMeta($measurement, $column, "; ", false);
                }       
            }
        }
          
        //save list
        $error = writeJSONfile($trPath . "_4_transaction.json", $list);
        if ($error) {
            throw new \Exception($error);
        }
      
        // Update overview of open transactions: transactions_open.json
        $transactions[$tr]["step"] = 5;
        $error = writeJSONfile($libraryPath . "transactions_open.json", $transactions);
        if ($error) {
            throw new \Exception($error);
        }
    } catch (\Exception $e) {
        $errormsg = $e->getMessage();
        eventLog("ERROR", $errormsg . " [module_import: STEP4]");
        echo "    <span style='color:red'>ERROR: " . $errormsg . "</span><br><br>";
        goto STEP1;
    }
}
  
  
















/****************************
*                           *
*  5 VIEW DATA LIST         *
*                           *
****************************/

STEP5:
{
    $needdata = 0; //number of data-files that need to be uploaded before we can finish
    $datasets = $measurements["_datasets"];
    unset($measurements["_datasets"]);
    
    echo "    <h4>STEP 3: add data</h4>\n";
    
    echo "    <strong><br>\n"
       . "      library: " . $_REQUEST["lib"] . "<br>\n"
       . "      transaction: " . $tr . "<br>\n"
       . "      action: " . $action . "\n"
       . "    </strong><br><br>\n";
    
    // create overview in html
    ?>
    <script type="text/javascript" charset="utf-8">
      $(document).ready(function() {
        var oTable = $('#datatable').dataTable( {
          //"sScrollY": "300px",
          "bPaginate": false,
          "bScrollCollapse": true,  
        } );
      } );
    </script>
    
    <div style="overflow-x: scroll; font-size: 60%;">
    <table style='border:1px dotted black; width:100%;'>
      <thead>
        <tr>
          <th>action</th>
          <th>id</th>
          <?php foreach ($datasets as $ds): ?>
          <th><?= $ds ?></th>
          <?php endforeach; ?>
          <th>built</th>
        </tr>
      </thead>
      <tbody>
    <?php 
    
    foreach ($measurements as $id => $measurement) {
        echo "        <tr>\n";
        echo "          <td>" . $measurement["_action"] . "</td>\n" ;
        echo "          <td>" . $id . "</td>\n" ;
      
        foreach ($datasets as $ds) {
            echo "          <td><em>";
        
            if (isset($measurement["datasets"][$ds])) {
                $ds = $measurement["datasets"][$ds];
        
                // (non-binary) data
                // $ds["_data"] is just a field where we store if a file was uploaded, not meant to
                // be saved into the resulting data json file (although it probably wouldn't harm)
                if (isset($ds["_data"])) {
                    echo "<font style='color: green;'>D </font>";
                } else {
                    echo "<font style='background-color: red;'>D </font>";
                    $needdata++;
                }
          
                // annotations
                echo "<font style='color: " . (isset($ds["_anno"])?"green":"red") . ";'>A </font>";

                // supplementary/binary data
                echo "<font style='color: " . (isset($ds["_bin"])?"green":"red") . ";'>S </font>";
                    
                // metadata
                echo "<font style='color: " . (isset($ds["meta"])?"green":"red") . ";'>M </font>";
          
                echo "          </em></td>\n";
            }
        }
      
        $link = "<a href='" . $_SERVER["SCRIPT_NAME"] . "?mod=import&lib=" . $_REQUEST["lib"] . "&tr=" . $tr . "&step=6&id=" . $id . "'>";
        echo "          <td>"
         . (isset($measurement["_built"])?"Yes ".$link."[change]":"No ".$link."[upload]")
         . "</a></td>\n"
         . "        </tr>\n";
    }

    echo "      </tbody>\n"
       . "    </table>\n"
       . "    </div><br><br>\n";
    
    if ($needdata == 0) {
        echo "    <div>All required data files have been uploaded. You can still upload optional data or replace data.<br>When finished, we are ready to publish (" . $action . ") this data to " . $_REQUEST["lib"] . ".</div><br><br>\n"
         . "    <form action='" . $_SERVER["SCRIPT_NAME"] . "?mod=import&lib=" . $_REQUEST["lib"] . "&tr=" . $tr . "&step=9' method='POST'>\n"
         . "      <input type='submit' value='Publish >' />\n"
         . "    </form>\n";
    } else {
        echo "    <div>Data files that need to be uploaded: " . $needdata . "</div><br><br>\n";
    }
    

    //FOOTER
    include(PRIVPATH . 'inc/footer.inc.php');

    return;
}

















  
/****************************
*                           *
*  6 UPLOAD DATA FORM       *
*                           *
****************************/

STEP6:
{
    echo "    <h4>STEP 3: add data</h4>\n";
    
    echo "    <strong><br>\n"
       . "      library: " . $_REQUEST["lib"] . "<br>\n"
       . "      transaction: " . $tr . "<br>\n"
       . "      action: " . $action . "<br>\n"
       . "      measurement: " . $_REQUEST["id"] . "\n"
       . "    </strong><br><br>\n\n";
    
    echo "    <form enctype='multipart/form-data' action='" . $_SERVER["SCRIPT_NAME"] . "?mod=import&lib=" . $_REQUEST["lib"] . "&tr=" . $tr . "&step=7&id=" . $_REQUEST["id"] . "' method='POST'>\n"
       . "      <input type='hidden' name='MAX_FILE_SIZE' value='2000000'>\n";
       
    $i = 0; //an index for datasets, which we can add to the POST parameters
    
    foreach ($measurements[$_REQUEST["id"]]["datasets"] as $dsid => $ds) {
        // examine the existing data
        // 1. data
        $hasData = isset($ds["_data"]);
        $hasAnno = isset($ds["_anno"]);
        $hasBin  = isset($ds["_bin"]); ?>
        <table style='border:1px dotted black; width:100%;'>
          <tr>
            <th colspan='3'>Dataset: <?= $dsid ?> </th>
          </tr>
          <tr>
            <td style='border:1px dotted black; width:34%;'>DATA FILE<br><br>
              <?= $hasData ? "<em>" . $ds["_data"] . "</em><br><br>" : "" ?>
              <input type='radio' name='dataUpRadio<?= $i ?>' value='keep' <?= $hasData?"checked":"disabled" ?>> Keep existing<br>
              <input type='radio' id='showDataUp<?= $i ?>' name='dataUpRadio<?= $i ?>' value='new' <?= $hasData?"":"checked" ?>> Upload new file<br>
              <div id='show1<?= $i ?>' <?= $hasData?"style='display:none'":"" ?>><input name="dataUp<?= $i ?>" type="file"></div>
              <script>
                $("input[name='dataUpRadio<?= $i ?>']").click(function () {
                    $('#show1<?= $i ?>').css('display', ($(this).val() === 'new') ? 'block':'none');
                });
              </script>
            </td>
            <td style='border:1px dotted black; width:33%;'>ANNOTATION FILE<br><br>
              <?= $hasAnno ? "<em>" . $ds["_anno"] . "</em><br><br>" : "" ?>
              <input type='radio' name='annoUpRadio<?= $i ?>' value='keep' <?= $hasAnno?"checked":"disabled" ?>> Keep existing<br>
              <input type='radio' id='showAnnoUp<?= $i ?>' name='annoUpRadio<?= $i ?>' value='new' <?= $hasAnno?"":"checked" ?>> Upload new file<br>
              <div id='show2<?= $i ?>' <?= $hasAnno?"style='display:none'":"" ?>><input name="annoUp<?= $i ?>" type="file"></div>
              <input type='radio' name='annoUpRadio<?= $i ?>' value='del' <?= $hasAnno?"":"disabled" ?>> Remove<br>
              <script>
                $("input[name='annoUpRadio<?= $i ?>']").click(function () {
                    $('#show2<?= $i ?>').css('display', ($(this).val() === 'new') ? 'block':'none');
                });
              </script>
            </td>
            <td style='border:1px dotted black; width:33%;'>SUPPLEMENTARY FILES<br><br>
              These files will be renamed into {measurementid}__{dataset}__{filename}.{ext}, so please use short descriptive names.
              New uploaded files with the same name will overwrite the old files with the same name.<br><br>
              <?php if ($hasBin): ?>
              <?php     foreach ($ds["_bin"] as $binfile):
                            $link = "<a href='" . $_SERVER["SCRIPT_NAME"] . "?mod=import&lib=" . $_REQUEST["lib"] . "&tr=" . $tr . "&step=8&id=" . $id . "&ds=" . $dsid . "&del=" . $binfile . "'>"; ?>
              <em><?= $binfile ?></em><?=  $link ?>[X]</a><br>
              <?php     endforeach; ?>
              <?php endif; ?>
              <br>
              Add file(s):
              <input name="binUp<?= $i ?>[]" type="file" multiple>
            </td>
          </tr>
        </table><br><br>
      <?php

      $i++;
    }
    ?>
        <?= '<a href="' . $_SERVER["SCRIPT_NAME"] . "?mod=import&lib=" . $_REQUEST["lib"] . "&tr=" . $tr . "&step=6" . '">< Overview</a>' ?>
        <input type="submit" value="Update >" />
      </form>
    <?php


    //FOOTER
    include(PRIVPATH . 'inc/footer.inc.php');

    return;
  }
  
  
  
/****************************
*                           *
*  7 PROCESS UPLOADS        *
*                           *
****************************/

STEP7:
{
    $datasets = array_keys($measurements[$_REQUEST["id"]]["datasets"]); //array of dataset names
    $build = false;  // rebuild a data json file

    try {
        // 1. check our information
        //    are we updating or creating a new file?
        if (!isset($measurements[$_REQUEST["id"]]["_built"])) { // 1.1 NEW FILE
            //we require a data file for each dataset
            foreach ($datasets as $key => $ds) {
                if (($_REQUEST["dataUpRadio" . $key] != "new") or (!is_uploaded_file($_FILES["dataUp" . $key]['tmp_name']))) {
                    throw new \Exception('No data file for dataset ' . $ds);
                }
            }
            
            // prepare $json
            $json = $measurements[$_REQUEST["id"]];
            unset($json["_action"], $json["_built"]);
            foreach ($datasets as $dsid => $ds) {
                unset($json["datasets"][$dsid]["_data"],
                    $json["datasets"][$dsid]["_anno"],
                    $json["datasets"][$dsid]["_bin"]);
            }
        } else { // 1.2 UPDATE EXISTING
            // open JSON data file
            if (!file_exists($trPath . $_REQUEST["id"] . ".json")) {
                throw new \Exception('JSON data file not found.');
            } else {
                $json = readJSONfile($trPath . $_REQUEST["id"] . ".json", false);
            }
        }
      
        // 2. process uploaded files
        foreach ($datasets as $key => $ds) {
            $fn = $_REQUEST["id"] . (($ds == 'default')?"":"__".$ds);
        
            // 2.1 process data files
            if (($_REQUEST["dataUpRadio" . $key] == "new") and (is_uploaded_file($_FILES["dataUp" . $key]['tmp_name']))) {
                // check and copy file
                $ext = "." . strtolower(pathinfo($_FILES["dataUp" . $key]['name'], PATHINFO_EXTENSION));
                $error = checkUpload("dataUp" . $key, $trPath, $fn . $ext);
                if ($error) {
                    throw new \Exception($error);
                }
                
                // fetch import options from the metadata (if any)
                $importOptions = array();
                if (isset($json["options"]["import"])) {
                    $importOptions = $json["options"]["import"];
                } 

                // convert file
                $data = false;
                $importOptions = selectConvertorClass($IMPORT, findDataType($json["type"], $DATATYPES), $ext, $importOptions);
                if (isset($importOptions["convertor"])) {
                    // create convertor        
                    $className = "Convert\\Import\\" . ucfirst(strtolower($importOptions["convertor"]));
                    $import = new $className($trPath . $fn . $ext, $importOptions);
                    $data = $import->getData();
                    $error = $import->getError();
                    if ($error) {
                        eventLog("WARNING", $error . " File: " . $_FILES["dataUp" . $key]['name'] . " [" . $class . "]");
                    }
                }
                if (!$data) {
                    throw new \Exception('Failed to convert ' . $_FILES["dataUp" . $key]['name'] . ': ' .$error);
                }
          
                // merge with metadata, update original $measurements and set $build
                if (!is_array($json["datasets"][$ds])) {
                    $json["datasets"][$ds] = array();
                }
                $json["datasets"][$ds]["data"] = $data;
                if (!is_array($measurements[$_REQUEST["id"]]["datasets"][$ds])) {
                    $measurements[$_REQUEST["id"]]["datasets"][$ds] = array();
                }
                $measurements[$_REQUEST["id"]]["datasets"][$ds]["_data"] = $_FILES["dataUp" . $key]['name'];
                $build = true;

                // set units: correct if supplied in csv, or take the default values
                // TODO: create a way to read those from the uploaded data (via the importfilters)
                // TODO: create a way to change them in the data upload form
                $json["datasets"][$ds]["units"] = findDataTypeUnits( $measurements[$_REQUEST["id"]]["type"], 
                                                                    $DATATYPES, 
                                                                    "json",
                                                                    isset($json["datasets"][$ds]["units"]) ? $json["datasets"][$ds]["units"] : null
                                                                  );
            }
        
            // 2.2 process annotations
            if (($_REQUEST["annoUpRadio" . $key] == "new") and (is_uploaded_file($_FILES["annoUp" . $key]['tmp_name']))) {
                // check and copy file
                $ext = ".anno";
                $error = checkUpload("annoUp" . $key, $trPath, $fn . $ext);
                if ($error) {
                    throw new \Exception($error);
                }

                // create import convertor class for annotations
                $viewer = $DATATYPES[findDataType($json["type"], $DATATYPES)]["viewer"];
                $import = new Convert\Import\Annotations($trPath . $fn . $ext, $viewer);
                $data = $import->getData();
                $error = $import->getError();

                if ($error) {
                    eventLog("WARNING", $error . " File: " . $_FILES["annoUp" . $key]['name'] . " [" . $class . "]");
                }
                if (!$data) {
                    throw new \Exception('Failed to convert ' . $_FILES["annoUp" . $key]['name'] . ': ' . $error);
                }
          
                // merge with metadata, $measurements and set $build
                $json["datasets"][$ds]["anno"] = $data;
                $measurements[$_REQUEST["id"]]["datasets"][$ds]["_anno"] = $_FILES["annoUp" . $key]['name'];
                $build = true;
            } elseif ($_REQUEST["annoUpRadio" . $key] == "del") {
                unlink($trPath . $fn . ".anno");
                unset($json["datasets"]["ds"]["anno"],
                $measurements[$_REQUEST["id"]]["datasets"][$ds]["_anno"]);
                $build = true;
            }
        
            // 2.3 process supplementary files
            //   these will be renamed {$id}__{$ds}__{$filename}.{$ext}
            if (is_uploaded_file($_FILES["binUp" . $key]['tmp_name'][0])) { //at least one file was uploaded
            // don't allow uploading .json or .anno files that would overwrite our uploaded data and annotations
            $arr = array("json", "anno");
                foreach ($_FILES["binUp" . $key]['name'] as $file) {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($ext, $arr)) {
                        throw new \Exception($ext . ' files cannot be uploaded as binary files.');
                    }
                }
            
                // copy files to our transaction data directory
                $error = checkMultiUpload("binUp" . $key, $trPath, $fn . "__");
                if (!$error) {  // update $measurements...[_bin]
                    foreach ($_FILES["binUp" . $key]['name'] as $file) {
                        if (!isset($measurements[$_REQUEST["id"]]["datasets"][$ds]["_bin"])) {
                            $measurements[$_REQUEST["id"]]["datasets"][$ds]["_bin"] = array();
                        }
                        if (!in_array($file, $measurements[$_REQUEST["id"]]["datasets"][$ds]["_bin"])) {
                            $measurements[$_REQUEST["id"]]["datasets"][$ds]["_bin"][] = $file;
                        }
                    }
                } else {
                    throw new \Exception($error);
                }
            
                $build = true;
            }
        }
      
        // 3. build and update _built
        if ($build) {
            // build JSON data file
            $error = writeJSONfile($trPath . $_REQUEST["id"] . ".json", $json);
            if ($error) {
                throw new \Exception($error);
            }
        
            // set _built field in $measurements
            if (isset($measurements[$_REQUEST["id"]]["_built"])) {
                $measurements[$_REQUEST["id"]]["_built"]++;
            } else {
                $measurements[$_REQUEST["id"]]["_built"] = 1;
            }
        
            // rebuild JSON measurements_inflated file
            $error = writeJSONfile($trPath . "_3_inflated.json", $measurements);
            if ($error) {
                throw new \Exception($error);
            }
        }
      
        goto STEP5;
    } catch (\Exception $e) {
        $errormsg = $e->getMessage();
        eventLog("ERROR", $errormsg  . " [module_import: STEP7]");
        echo "    <span style='color:red'>ERROR: " . $errormsg . "</span><br><br>";
        goto STEP6;
    }
}

  
  
/****************************
*                           *
*  8 DELETE BIN FILE        *
*                           *
****************************/

STEP8:
{
    try {
        $i = array_search($_REQUEST["f"], $measurements[$_REQUEST["id"]]["datasets"][$_REQUEST["ds"]]["_bin"]);
        if ($i) {
            // delete file
            $fn = $_REQUEST["id"] . (($ds == 'default')?"":"__".$ds) . "__" . $_REQUEST["f"];
            $success = unlink($trPath . $fn);
            if (!$success) {
                throw new \Exception("Could not remove " . $_REQUEST["f"]);
            }
        
            // log in inflated json
            unset($measurements[$_REQUEST["id"]]["datasets"][$ds]["_bin"][$i]);
            $error = writeJSONfile($trPath . "_3_inflated.json", $measurements);
            if ($error) {
                throw new \Exception($error);
            }
        }
        goto STEP6;
    } catch (\Exception $e) {
        $errormsg = $e->getMessage();
        eventLog("ERROR", $errormsg  . " [module_import: STEP8]");
        echo "    <span style='color:red'>ERROR: " . $errormsg . "</span><br><br>";
        goto STEP6;
    }
}

  
/****************************
*                           *
*  9 PUBLISH DATA           *
*                           *
****************************/

STEP9:
{
    echo "    <h4>STEP 4: publish data</h4>\n";
    
    echo "    <strong><br>\n"
       . "      library: " . $_REQUEST["lib"] . "<br>\n"
       . "      transaction: " . $tr . "<br>\n"
       . "      action: " . $action . "<br>\n"
       . "    </strong><br><br>\n\n";
    
    try {
        // open the library measurements.json (or a empty array in case of replace) as $result
        if (($action == "append") or ($action == "update")) {
            $result = readJSONfile($libraryPath . "measurements.json", false);
        } elseif ($action == "replace") {
            $result = array();
        } else {
            throw new \Exception("Unknown action " . $action . " in transaction " . $tr);
        }
      
        // walk through our measurements that need to be added or updated
        unset($measurements["_datasets"]);
        foreach ($measurements as $id => $measurement) {
            if (array_key_exists($id, $result) and ($action == "append")) {
                echo "<strong>WARNING</strong> " . $id . " already exists. Skipping.<br>\n";
            } else {
                $result[$id] = $measurement;
                $result[$id]["_transaction"] = $tr;
                echo "<strong>Merged</strong> " . $id . ".<br>\n";
            }
        }
      
        // make the resulting measurements in the transaction directory and make hardlink into the library directory (= publish it)
        $path = $trPath . "_5_result_" . date("YmdHis") . ".json";
        $error = writeJSONfile($path, $result);
        if ($error) {
            throw new \Exception($error);
        }
        if (file_exists($libraryPath . "measurements.json")) {
            unlink($libraryPath . "measurements.json");
        }
        $success = link($path, $libraryPath . "measurements.json");
        if ($success) {
            echo "<strong>Published</strong> library file<br><br>\n";
        } else {
            throw new \Exception("Could not publish the data into the library!");
        }
      
        // some administration in transactions_open and transactions_closed.json
        $transactions_closed = readJSONfile($libraryPath . "transactions_closed.json", false);
        $transactions_closed[$tr] = $transactions[$tr];
        $transactions_closed[$tr]["timestamp"] = mdate();
        unset($transactions[$tr]);
        writeJSONfile($libraryPath . "transactions_closed.json", $transactions_closed);
        writeJSONfile($libraryPath . "transactions_open.json", $transactions);
      
        echo "      <span style='color:red'>Data successfully merged into library ". $_REQUEST["lib"] ."</span><br>\n";
    } catch (\Exception $e) {
        $errormsg = $e->getMessage();
        $errormsg .= " PLEASE CHECK THE STATUS OF LIBRARY " . $_REQUEST["lib"] ;
        eventLog("ERROR", $errormsg  . " [module_import: STEP9]", false, true);  //don't exit, but send alertmail
        echo "    <span style='color:red'>ERROR: " . $errormsg ."</span><br><br>";
      
        // Update transactions_open.json
      $transactions[$tr]["step"] = 10;  // lock this transaction, so that a sysadmin can look into it
      $error = writeJSONfile($libraryPath . "transactions_open.json", $transactions);
    }

    //FOOTER
    include(PRIVPATH . 'inc/footer.inc.php');
}
