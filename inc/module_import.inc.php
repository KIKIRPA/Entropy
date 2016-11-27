<?php
  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }

  require_once('./inc/common_upload.inc.php');
  require_once('./inc/common_importfilters.inc.php');
 

/****************************
*                           *
*  COMMON TASKS             *
*                           *
****************************/  
  
  echo "      <h3>Library upload tool</h3>\n";
  
  # check if the operation already exists and set $op and $action 
  # also check if the user has permission to this operation
  
  $action = false;
  
  if (!file_exists(LIB_DIR . $_REQUEST["lib"]))
    if (!mkdir2(LIB_DIR . $_REQUEST["lib"] . "/"))
    {
      echo "<span style='color:red'>ERROR: Could not make library directory for: " . $id . "!</span><br><br>\n";
      eventLog("ERROR", "Could not make library directory for: " .$id . " [module_import]", true, false);
    }
  
  if (file_exists(LIB_DIR . $_REQUEST["lib"] . "/openops.json"))
    $operations = readJSONfile(LIB_DIR . $_REQUEST["lib"] . "/openops.json", false);
  else
    $operations = array();
  
  try 
  {
    if (isset($_REQUEST["op"])) // open an existing operation
    {
      // check if it exists
      if (isset($operations[$_REQUEST["op"]]))
        $op = $_REQUEST["op"];
      else
        throw new RuntimeException('Invalid operation ID ' . $op);
      
      // check if this user permission
      if (!$user["permissions"]["admin"] and ($operations[$op]["user"] != $is_logged_in))
        throw new RuntimeException('Unauthorised access to operation ' . $op);
      
      // check if the operation dir exists
      $opdir = LIB_DIR . $_REQUEST["lib"] . "/" . $op . "/";
      if (!file_exists($opdir))
        throw new RuntimeException('Upload directory was not found for '. $op);
        
      // delete
      if (isset($_REQUEST["del"]))
      {
        rmdir2($opdir);
        unset($operations[$op]);
        $error = writeJSONfile(LIB_DIR . $_REQUEST["lib"] . "/openops.json", $operations);
        if ($error) throw new RuntimeException($error);
        goto STEP1;
      }
      
      // set $action
      if (in_array($operations[$op]["action"], array("append", "update", "replace")))
        $action = $operations[$op]["action"];
      else throw new RuntimeException('No valid action for operation ' . $op);
    }
    else
    {
      // create a new operation
      $op = date("YmdHis");
      $operations[$op] = array("user"   => $is_logged_in,
                               "action" => "none",
                               "step"   => 1
                              );
      $opdir = LIB_DIR . $_REQUEST["lib"] . "/" . $op . "/";
      //don't write openops.json at this time, it will create an empty operation each
      //time STEP1 is opened
    }
  }
  catch (RuntimeException $e) 
  {
    $errormsg = $e->getMessage();
    eventLog("ERROR", $errormsg  . " [module_import: COMMON]");
    echo "    <span style='color:red'>ERROR: " . $errormsg . "</span><br><br>";
    goto STEP1;
  }
  

/* STEPS
  1* VIEW    upload file and choose action (append/update/replace)    [step=1]
  2  PROCESS check file (if ok, proceed with 3)                       [step=2, action=a/u/r, upfile]
  3* VIEW    data checks (errors and warnings): reupload or continue  [step=3, op]
  4  PROCESS make measurements.json (if ok, proceed with 5)           [step=4, op]                               
  5* VIEW    view list of uploaded data                               [step=5, op]
  6  VIEW    spectra upload form                                      [step=6, op, id]
  7  PROCESS convert and make jsons (on every upload!)                [step=7, op, id]                 
  8  PROCESS delete binary file                                       [step=8, op, id, ds, f]
  9  PROCESS publish data                                             [step=9, op]
  10* ERROR during publishing, nothing can be done to this upload; to be checked manually by sysadmin
*/

  if (isset($_REQUEST["step"]))
  {
    if     (($operations[$op]["step"] == 1) and ($_REQUEST["step"] > 2)) $step = 1;
    elseif (($operations[$op]["step"] == 3) and ($_REQUEST["step"] > 4)) $step = 3;
    elseif (($operations[$op]["step"] == 5) and ($_REQUEST["step"] > 9)) $step = 5;
    elseif  ($operations[$op]["step"] == 10)                             $step = 10;
    else                                                                $step = $_REQUEST["step"];
  }
  else $step = $operations[$op]["step"];     // fallback to the last reached milestone step (1,3,5,10)
  
        
  // read the right measurements file _2_flat.json or _inflated.json
  if     ($step <= 2) $measurements = array();
  elseif ($step <= 4) $measurements = readJSONfile($opdir . "_2_flat.json", false);
  elseif ($step <= 8) $measurements = readJSONfile($opdir . "_3_inflated.json", false);
  elseif ($step == 9) $measurements = readJSONfile($opdir . "_4_operation.json", false);
  //echo "DEBUG step: " . $step . "<br>";
  
  switch ($step)
  {
    default:
    case 1:
    case 10:
      break;
    case 2:
      if (isset($_REQUEST["action"]))
        if (in_array($_REQUEST["action"], array("append", "update", "replace")))
          goto STEP2;
      break;
    case 3:
      goto STEP3;
      break;
    case 4:
      goto STEP4;
      break;
    case 5:
      goto STEP5;
      break;
    case 6:
      if (isset($_REQUEST["id"])) goto STEP6;
      else goto STEP5;
      break;
    case 7:
      if (isset($_REQUEST["id"])) goto STEP7;
      else goto STEP5;
      break;
    case 8:
      if (isset($_REQUEST["id"]) and isset($_REQUEST["ds"]) and isset($_REQUEST["f"]))
        goto STEP8;
      else goto STEP5;
      break;
    case 9:
      goto STEP9;
      break;
  }
  
  
/****************************
*                           *
*  1 UPLOAD CSV / CONTINUE  *
*                           *
****************************/ 
  
  STEP1:
  {    
    ?>
      <div style="border:1px dotted black;">
        <h4>Uploading a new set of measurements (append, update or replace)</h4>
        <p>All measurements have to be described (metadata) in a table, saved as comma separated values (CSV). 
        <p>Rules for the CSV-table: <br>
        <ul>
          <li>The first line contain the column headers.</li>
          <li>Each measurement is written on a new line.</li>
          <li>There are two required columns: "<strong>id</strong>", a unique identifier for each measurement, and "<strong>type</strong>", defining the (supported) data type. If multiple datasets are contained within a single measurements, this column must be the data type of the primary dataset. Other datasets can have different data types, but will only be displayed when specifically asked by the user.</li>
          <li>If you want to enable downloading the files in JCAMP-DX format, a "<strong>jcamptemplate</strong>" column has to be present pointing to the uploaded dxt file.</li>
          <li>Not a strict requirement, but it is recommended to use the main column headers "<strong>meta:sample</strong>", "<strong>meta:samplesource</strong>", "<strong>meta:instrument</strong>", "<strong>meta:parameters</strong>", "<strong>meta:measurement</strong>" and "<strong>meta:contributor</strong>". The "meta"-prefix is optional, but advised. These and other fields can be recursively subdivided as required using a semicolon as separator, e.g. "meta:sample:CI number", "meta:samplesource:0:sample identifier". If a field is subdivided in subfields, the parent field should not be used (or: you can't have data in a "meta:sample" and a "meta:sample:CI name" column for a given measurement; and it is not advised to use both in the same operation).</li>
          <li>If each measurement only contains a single dataset, the system will create a "default" dataset. You can overrule this behaviour by defining an empty column e.g. "dataset:baseline corrected".</li>
          <li>If all or some measurements contain multiple datasets, the CSV table has to contain multiple datasets, e.g. "dataset:baseline corrected" and "dataset:original data". Dataset-specific metadata can be supplied as subfields of "dataset:original data:meta" and will overrule common metadata. It is advised to store common metadata as subfield of "meta", e.g. "meta:sample:CI number". Metadata in "dataset:x:meta" will overrule those in "meta:", which will in turn overrule those defined directly.</li>
        </ul>
        <form enctype="multipart/form-data" action="<?php echo $_SERVER["SCRIPT_NAME"] . "?mod=import&lib=" . $_REQUEST["lib"] . "&step=2"; ?>" method="POST">
          <input type="hidden" name="MAX_FILE_SIZE" value="2000000">
          Upload CSV file: <input name="upfile" type="file"><br><br>
          <input type='radio' id='action' name='action' value='append' checked> Append (add one or multiple measurements. You cannot overwrite existing measurements (with the same "id"))<br>
          <input type='radio' id='action' name='action' value='update'        > Update (add or update multiple measurements. Existing measurements (with the same "id") will be updated)<br>
          <input type='radio' id='action' name='action' value='replace'       > Replace (replace all measurements. This will <strong>wipe the existing library entirely!</strong>)<br><br>
          <input type="submit" value="Next >" />
        </form>
      </div>
      
      <br><br>
      <div  style="border:1px dotted black;">
        <h4>Continue an unfinished operation</h4>
        <script type="text/javascript" charset="utf-8">
          $(document).ready(function() {
            var oTable = $('#datatable').dataTable( {
              //"sScrollY": "300px",
              "bPaginate": false,
              "bScrollCollapse": true,  
            } );
          } );
        </script>
    
        <table id="_datatable" style="padding: 10px;">
          <thead>
            <tr>
              <th style='background: #ddd; text-align: left; padding: 5px;'>date</th>
              <th style='background: #ddd; text-align: left; padding: 5px;'>user</th>
              <th style='background: #ddd; text-align: left; padding: 5px;'>action</th>
              <th style='background: #ddd; text-align: left; padding: 5px;'></th>
            </tr>
          </thead>
          <tbody>
            <?php
                $n = 0;
                
                foreach ($operations as $id => $operation)
                {
                  //test if the logged in user has made this operation or is admin
                  if (($operation["user"] == $is_logged_in) or $user["permissions"]["admin"])
                  {
                    if (in_array($operation["action"], array("append", "update", "replace")))
                    {                    
                      $a = $operation["action"];
                      $u = $operation["user"];
                      $d = substr($id, 0, 4) . "/" . substr($id, 4, 2) . "/" . substr($id, 6, 2) . " "
                        . substr($id, 8, 2) . ":" . substr($id, 10, 2) . ":" . substr($id, 12, 2);
                      $bg = ( $n++ & 1 ? "#eee" : "#fff" );                  
                      
                      echo "              <tr>\n";
                      echo "                <td style='background: $bg; padding: 5px;'>$d</td>\n";
                      echo "                <td style='background: $bg; padding: 5px;'>$u</td>\n";
                      echo "                <td style='background: $bg; padding: 5px;'>$a</td>\n";
                      echo "                <td style='background: $bg; padding: 5px;'>";
                      if ($operations[$op]["step"] != 10)
                        echo "<a href='" . $_SERVER["SCRIPT_NAME"] . "?mod=import&lib=" . $_REQUEST["lib"] . "&op=" . $id. "'>&#9998;</a> ";
                      if ($operations[$op]["step"] != 10)
                        echo "<a href='" . $_SERVER["SCRIPT_NAME"] . "?mod=import&lib=" . $_REQUEST["lib"] . "&op=" . $id. "&del' onclick=\"return confirm('Delete this unfinished operation?')\">&#10006;</a></td>\n";
                      echo "              </tr>\n";
                    }
                  }
                }
            ?>
          </tbody>
        </table>

      </div>
    <?php
    
    return;   // return to the main php page
  }

  

/****************************
*                           *
*  2 PROCESS UPLOAD         *
*                           *
****************************/ 
  
  STEP2:
  {    
    try 
    {
      $action = $_REQUEST["action"];
      
      // check and move uploaded file
      $error = checkUpload('upfile', $opdir, "_1_" . $action . ".csv");
      if ($error) 
        throw new RuntimeException($error);
      
      // reread CSV file
      if (($handle = fopen($opdir . "_1_" . $action . ".csv", "r")) == false)
        throw new RuntimeException('Could not open the CSV file.');
      
      // Convert to array
      $measurements = csv2array($handle, "id", true); //outputs flat array or error string, keep emtpy fields
      fclose($handle);
      if (!is_array($measurements))
        throw new RuntimeException($measurements);      
      
      // Save .json
      $error = writeJSONfile($opdir . "_2_flat.json", $measurements);
      if ($error) throw new RuntimeException($error);
      
      // Update openops.json
      $operations[$op]["action"] = $action;
      $operations[$op]["step"] = 3;
      $error = writeJSONfile(LIB_DIR . $_REQUEST["lib"] . "/openops.json", $operations);
      if ($error) throw new RuntimeException($error);
    }
    catch (RuntimeException $e) 
    {
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
  //  - following step 2 (processing)  -> $op, $action, $measurements set during step 2
  //  - opening a saved operation         -> $op, $action, $measurements set in the common tasks (derived from $_REQUEST["op"])
  
  STEP3:
  { 
    /* Data verification: warnings and errors (here or in step 3)??        
      - id: present and unique
      - type: present (error) and known (warning)
      - main metadata categories: sample, samplesource, instrument, parameters, contributor, jcamptemplate, data
      - fields required by libs.json
      - empty fields (warnings)
    */

    $existing = readJSONfile(LIB_DIR . $_REQUEST["lib"] . "/measurements.json");  //if file does not exist: empty array
    $existing = array_keys($existing);
    
    foreach ($DATATYPES as $type => $tval) $types_sani[sanitizeStr($type, "", "-+:^", True)] = $type;
    
    unset($measurements[""]);   //remove empty rows
    
    $a = 0; // automatically corrected issues (green bg)
    $b = 0; // non-blocking warnings (orange bg)
    $c = 0; // blocking errors (red bg)
    
    $fields = array_keys(current($measurements));
    $fields = array_filter($fields);  //remove empty fields
    
    echo "    <h4>STEP 2: review the measurement list</h4>\n";
    
    echo "    <strong><br>\n"   
       . "      library: " . $_REQUEST["lib"] . "<br>\n"
       . "      operation: " . $op . "<br>\n"
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
          <?php foreach ($fields as $th) echo "          <th>" . str_replace(":", ":<br>", $th) . "</th>\n"; ?>
        </tr>
      </thead>
      <tbody>
    <?php    
   
    foreach ($measurements as $id => $measurement)
    {
      // start with adding missing required fields (defined in libs.json)
      unset($measurements[$id][""]);   //remove empty columns 
      
      echo "        <tr>\n";
      $id_sani = sanitizeStr($id);
      
      // 1. CHECK ACTION
      switch ($action) 
      {
        case "replace":
          $measurements[$id]["_action"] = "R";
          break;
        case "update":
          if (count($existing) > 0)
          {
            if (in_array($id_sani, $existing)) $measurements[$id]["_action"] = "U";
            else                               $measurements[$id]["_action"] = "A";
          }
          else $measurements[$id]["_action"] = "A";
          break;
        case "append":
        default:
          if (count($existing) > 0)
          {
            if (in_array($id_sani, $existing)) $measurements[$id]["_action"] = "!";
            else                               $measurements[$id]["_action"] = "A";
          }
          else $measurements[$id]["_action"] = "A";
      }
      if ($measurements[$id]["_action"] != "!") 
        echo "          <td>" . $measurements[$id]["_action"] . "</td>\n";
      else           
      {
        echo "          <td style='background-color: red;'><div tooltip='This measurement already exists in this library!'>!</div></td>\n";
        $c++;
      }
      
      // 2. CHECK ID
      if ($id != $id_sani)
      {
        //rename key $id in $measurements without changing the order
        $keys = array_keys($measurements);
        $keys[array_search($id, $keys)] = $id_sani;
        $measurements = array_combine($keys, $measurements);
        unset($keys);
        
        //code green! [removed illegal characters]
        echo "          <td style='background-color: green;'><div tooltip='Automatically removed illegal characters'>".$id_sani."</div></td>\n";
        $a++;
      }
      else echo "          <td>".$id."</td>\n";      
   
      // 3. PARAMETER FIELDS
      foreach ($measurement as $param => $value)
      {
        $tt = array();
        $col = "";       
        $param_sani = sanitizeStr($param, "", "-+^", True);
        
        switch ($param_sani)
        {
          case "jcamptemplate":
            // autocorrect filename
            $val_sani = sanitizeStr($value);
            if ($val_sani != $value)
            {
              $a++;
              $col = "green";
              array_push($tt, "[NOTE] Automatically removed illegal characters");
              $measurements[$id][$param] = $value = $val_sani;
            }
            
            if (empty($value) and (   in_array("dx", $LIBS[$_REQUEST["lib"]]["allowformat"]) 
                                   or in_array("jdx", $LIBS[$_REQUEST["lib"]]["allowformat"])))
            {
              $b++;
              $col = "orange";
              array_push($tt, "[WARNING] Empty: downloading JCAMP-DX will not be possible"); 
            }
            elseif (!file_exists(LIB_DIR . $_REQUEST["lib"] . "/jcamptemplates/" . $value))
            {
              $b++;
              $col = "orange";
              array_push($tt, "[WARNING] JCAMP-DX template file not found"); 
            }
            break;
          case "type":
            //check if type is set and exists in specformat.json!!
            $val_sani = sanitizeStr($value, "", "-+:^", True);
            if (isset($types_sani[$val_sani]))
            {
              //if the value in the CSV is not identical to the one in specformat, autocorrect
              if ($value != $types_sani[$val_sani]) 
              {
                $a++;
                $col = "green";
                array_push($tt, "[NOTE] Automatically corrected type");
                $measurements[$id][$param] = $value = $types_sani[$val_sani];
              }
            }
            else // type in CSV does not exist!
            {
              $c++;
              $col = "red";
              array_push($tt, "[ERROR] Undefined data type");
            }
          default:
            $val_sani = trim($value);
            if ($val_sani != $value)
            {
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
            if (!empty($value))
            {
              $val_sani = false;
              foreach ($fields as $field)
              {
                if (!empty(trim($measurement[$field])) and strstr($field, $param_sani . ":"))
                  $val_sani = true;
              }
              if ($val_sani)
              {
                $c++;
                $col = "red";
                array_push($tt, "[ERROR] Nonempty field with nonempty subfield");
              }
            }
            break;
        }
        
        // also check data types defined in dataset:*:meta:type
        if ((substr($param_sani, 0, 8 ) === "dataset:") and (substr($param_sani, -10 ) === ":meta:type"))
        {
          //check if type is set and exists in specformat.json!!
          $val_sani = sanitizeStr($value, "", "-+:^", True);
          if (isset($types_sani[$val_sani]))
          {
            //if the value in the CSV is not identical to the one in specformat, autocorrect
            if ($value != $types_sani[$val_sani]) 
            {
              $a++;
              $col = "green";
              array_push($tt, "[NOTE] Automatically corrected type");
              $measurements[$id][$param] = $value = $types_sani[$val_sani];
            }
          }
          else // type in CSV does not exist!
          {
            $c++;
            $col = "red";
            array_push($tt, "[ERROR] Undefined data type");
          }
        }
        
        if (empty($value)) $value = "[NULL]";
        
        if ($col == "")
          echo "          <td>". $value . "</td>\n";
        else
          echo "          <td style='background-color: " . $col . ";'><div tooltip='" . implode(" ; ", $tt) . "'>" . $value . "</td>\n";      
      }      
      
      echo "        </tr>\n";
    }
    
    ?>
      </tbody>
    </table>
    </div>
    
    <div>
      <b>
        Autocorrected issues: <?php echo $a ?><br>
        Non-fatal warnings: <?php echo $b ?><br>
        Fatal errors: <?php echo $c ?>
      </b>
    </div><br><br>
    
    <?php
    
    $error = writeJSONfile($opdir . "_2_flat.json", $measurements);
    if ($error) eventLog("ERROR", "could not save autocorrected json [module_import: STEP3]");
        
    // CONTINUE BUTTON
    if ($c == 0)
      echo "        <form action='" . $_SERVER["SCRIPT_NAME"] . "?mod=import&lib=" . $_REQUEST["lib"] . "&op=" . $op . "&step=4" . "' method='POST'>\n"
         . "          <input type='submit' value='Next >' />\n"
         . "        </form>\n";
    else 
      echo "        There are errors. Please correct the original file and reupload. <a href='" . $_SERVER["SCRIPT_NAME"] . "?mod=import&lib=" . $_REQUEST["lib"] . "'>Return</a>\n";
    
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
    foreach ($measurements as $i => $measurement)
      foreach ($measurement as $j => $field)
        if (empty($field))
          unset($measurements[$i][$j]);
    $measurements = inflateArray($measurements, 0);
    
    // If a dataset-field is missing, we need to add at least a "default" dataset
    //            dataset:default:meta (to contain dataset-specific metadata)
    //                NOTE any dataset-specific metadata will overrule the common metadata (array_replace|merge_recursive?)
    //                     when displayed on the website or exported to JCAMP-DX 
    //            dataset:default:data
    //            dataset:default:annotations
    
    // define the datasets
    // NOTE at the moment we only consider datasets defined in the CSV-file,
    //      or, if none are specified the "default"-dataset
    $datasets = array();
    foreach ($measurements as $id => $measurement)
    {
      if (!isset($measurement["dataset"]))
      {
        $measurements[$id]["dataset"]["default"] = array();
        if (!in_array("default", $datasets)) array_push($datasets, "default");
      }
      else 
        foreach ($measurement["dataset"] as $datasetid => $value)
          if (!in_array($datasetid, $datasets)) array_push($datasets, $datasetid);  
    }
    $measurements["_datasets"] = $datasets;
    
    try 
    {
      // Save inflated $measurements as .json
      $error = writeJSONfile($opdir . "_3_inflated.json", $measurements);
      if ($error) throw new RuntimeException($error);
      
      //make $list with keys from $columns and values retrieved from $measurements
      $columns = $LIBS[$_REQUEST["lib"]]["columns"];
      $list =  array();
      foreach ($measurements as $id => $measurement)
      {
        $measurement = overrideMeta($measurement); //fold "meta:" metadata together with direct metadata
        foreach ($columns as $column)
          $list[$id][$column] = getMeta($measurement, $column, "; ", false);
      }
          
      //save list
      $error = writeJSONfile($opdir . "_4_operation.json", $list);
      if ($error) throw new RuntimeException($error);
      
      // Update openops.json
      $operations[$op]["step"] = 5;
      $error = writeJSONfile(LIB_DIR . $_REQUEST["lib"] . "/openops.json", $operations);
      if ($error) throw new RuntimeException($error);
    }
    catch (RuntimeException $e) 
    {
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
       . "      operation: " . $op . "<br>\n"
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
          <?php foreach ($datasets as $ds) echo "          <th>" . $ds . "</th>\n"; ?>
          <th>built</th>
        </tr>
      </thead>
      <tbody>
    <?php 
    
    foreach ($measurements as $id => $measurement)
    {     
      echo "        <tr>\n";
      echo "          <td>" . $measurement["_action"] . "</td>\n" ;
      echo "          <td>" . $id . "</td>\n" ;
      
      foreach ($datasets as $ds)
      {
        echo "          <td><em>";
        
        if (isset($measurement["dataset"][$ds]))
        {
          $ds = $measurement["dataset"][$ds];
        
          // (non-binary) data
          // $ds["_data"] is just a field where we store if a file was uploaded, not meant to
          // be saved into the resulting data json file (although it probably wouldn't harm)
          if (isset($ds["_data"])) echo "<font style='color: green;'>D </font>";
          else
          {
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
      
      $link = "<a href='" . $_SERVER["SCRIPT_NAME"] . "?mod=import&lib=" . $_REQUEST["lib"] . "&op=" . $op . "&step=6&id=" . $id . "'>";
      echo "          <td>" 
         . (isset($measurement["_built"])?"Yes ".$link."[change]":"No ".$link."[upload]")
         . "</a></td>\n"
         . "        </tr>\n";
    }

    echo "      </tbody>\n"
       . "    </table>\n"
       . "    </div><br><br>\n";
    
    if ($needdata == 0)
    {
      echo "    <div>All required data files have been uploaded. You can still upload optional data or replace data.<br>When finished, we are ready to publish (" . $action . ") this data to " . $_REQUEST["lib"] . ".</div><br><br>\n"
         . "    <form action='" . $_SERVER["SCRIPT_NAME"] . "?mod=import&lib=" . $_REQUEST["lib"] . "&op=" . $op . "&step=9' method='POST'>\n"
         . "      <input type='submit' value='Publish >' />\n"
         . "    </form>\n";
    }
    else 
     echo "    <div>Data files that need to uploaded: " . $needdata . "</div><br><br>\n";
    
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
       . "      operation: " . $op . "<br>\n"
       . "      action: " . $action . "<br>\n"
       . "      measurement: " . $_REQUEST["id"] . "\n"
       . "    </strong><br><br>\n\n";
    
    echo "    <form enctype='multipart/form-data' action='" . $_SERVER["SCRIPT_NAME"] . "?mod=import&lib=" . $_REQUEST["lib"] . "&op=" . $op . "&step=7&id=" . $_REQUEST["id"] . "' method='POST'>\n"
       . "      <input type='hidden' name='MAX_FILE_SIZE' value='2000000'>\n";
       
    $i = 0; //an index for datasets, which we can add to the POST parameters
    
    foreach ($measurements[$_REQUEST["id"]]["dataset"] as $dsid => $ds)
    {
      // examine the existing data
      // 1. data
      $hasData = isset($ds["_data"]);
      $hasAnno = isset($ds["_anno"]);
      $hasBin  = isset($ds["_bin"]);
      
      ?>
        <table style='border:1px dotted black; width:100%;'>
          <tr>
            <th colspan='3'>Dataset: <?= $dsid ?> </th>
          </tr>
          <tr>
            <td style='border:1px dotted black; width:34%;'>DATA FILE<br><br>
              <?php if ($hasData) echo "<em>" . $ds["_data"] . "</em><br><br>"; ?>
              <input type='radio' name='dataUpRadio<?= $i ?>' value='keep' <?= $hasData?"checked":"disabled" ?>> Keep existing<br>
              <input type='radio' id='showDataUp<?= $i ?>' name='dataUpRadio<?= $i ?>' value='new' <?= $hasData?"":"checked" ?>> Upload new file<br>
              <div id='show1<?= $i ?>' <?= $hasData?"style='display:none'":"" ?>><input name="dataUp<?= $i ?>" type="file"></div>
              <script>
                $(document).ready(function() {
                    $('input[type="radio"]').click(function() {
                        if($(this).attr('id') == 'showDataUp<?= $i ?>') {
                                $('#show1<?= $i ?>').show();           
                        }
                        else {
                                $('#show1<?= $i ?>').hide();   
                        }
                    });
                });
              </script>
            </td>
            <td style='border:1px dotted black; width:33%;'>ANNOTATION FILE<br><br>
              <?php if ($hasAnno) echo "<em>" . $ds["_anno"] . "</em><br><br>"; ?>
              <input type='radio' name='annoUpRadio<?= $i ?>' value='keep' <?= $hasAnno?"checked":"disabled" ?>> Keep existing<br>
              <input type='radio' id='showAnnoUp<?= $i ?>' name='annoUpRadio<?= $i ?>' value='new' <?= $hasAnno?"":"checked" ?>> Upload new file<br>
              <div id='show2<?= $i ?>' <?= $hasData?"style='display:none'":"" ?>><input name="annoUp<?= $i ?>" type="file"></div>
              <input type='radio' name='annoUpRadio<?= $i ?>' value='del' <?= $hasAnno?"":"disabled" ?>> Remove<br>
              <script>
                $(document).ready(function() {
                    $('input[type="radio"]').click(function() {
                        if($(this).attr('id') == 'showAnnoUp<?= $i ?>') {
                                $('#show2<?= $i ?>').show();           
                        }
                        else {
                                $('#show2<?= $i ?>').hide();   
                        }
                    });
                });
              </script>
            </td>
            <td style='border:1px dotted black; width:33%;'>SUPPLEMENTARY FILES<br><br>
              These files will be renamed into {measurementid}__{dataset}__{filename}.{ext}, so please use short descriptive names.
              New uploaded files with the same name will overwrite the old files with the same name.<br><br>
              <?php 
                if ($hasBin)
                  foreach ($ds["_bin"] as $binfile)
                  {
                    $link = "<a href='" . $_SERVER["SCRIPT_NAME"] . "?mod=import&lib=" . $_REQUEST["lib"] . "&op=" . $op . "&step=8&id=" . $id . "&ds=" . $dsid . "&del=" . $binfile . "'>";
                    echo "<em>" . $binfile . "</em> " . $link . "[X]</a><br>";
                  }
              ?>
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
        <?php echo '<a href="' . $_SERVER["SCRIPT_NAME"] . "?mod=import&lib=" . $_REQUEST["lib"] . "&op=" . $op . "&step=6" . '">< Overview</a>'; ?>
        <input type="submit" value="Update >" />
      </form>
    <?php
    
    return;
  }
  
  
  
/****************************
*                           *
*  7 PROCESS UPLOADS        *
*                           *
****************************/ 

  STEP7:
  {
    $datasets = array_keys($measurements[$_REQUEST["id"]]["dataset"]); //array of dataset names
    $build = False;  // rebuild a data json file
    
    try 
    {
      // 1. check our information 
      //    are we updating or creating a new file?
      if (!isset($measurements[$_REQUEST["id"]]["_built"]))
      { // 1.1 NEW FILE
        //we require a data file for each dataset
        foreach ($datasets as $key => $ds)
          if (($_REQUEST["dataUpRadio" . $key] != "new") or (!is_uploaded_file($_FILES["dataUp" . $key]['tmp_name'])))
            throw new RuntimeException('No data file for dataset ' . $ds);
        
        // prepare $json
        $json = $measurements[$_REQUEST["id"]];
        unset($json["_action"], $json["_built"]);
        foreach ($datasets as $dsid => $ds) 
          unset($json["dataset"][$dsid]["_data"], 
                $json["dataset"][$dsid]["_anno"], 
                $json["dataset"][$dsid]["_bin"]);
      }
      else 
      { // 1.2 UPDATE EXISTING
        // open JSON data file
        if (!file_exists($opdir . $_REQUEST["id"] . ".json"))
          throw new RuntimeException('JSON data file not found.');
        else
          $json = readJSONfile($opdir . $_REQUEST["id"] . ".json", false);
      }
      
      // 2. process uploaded files
      foreach ($datasets as $key => $ds)
      {
        $fn = $_REQUEST["id"] . (($ds == 'default')?"":"__".$ds);
        
        // 2.1 process data files
        if (($_REQUEST["dataUpRadio" . $key] == "new") and (is_uploaded_file($_FILES["dataUp" . $key]['tmp_name'])))
        {
          // check and copy file
          $ext = "." . strtolower(pathinfo($_FILES["dataUp" . $key]['name'],PATHINFO_EXTENSION));
          $error = checkUpload("dataUp" . $key, $opdir, $fn . $ext);
          if ($error) throw new RuntimeException($error);
          
          // convert file
          $data = importfilter($opdir . $fn . $ext);
          if (!$data) throw new RuntimeException('Failed to convert ' . $fn . $ext . '.');
          
          // merge with metadata, updatd original $measurements and set $build
          if (!is_array($json["dataset"][$ds])) $json["dataset"][$ds] = array();
          $json["dataset"][$ds]["data"] = $data;
          if (!is_array($measurements[$_REQUEST["id"]]["dataset"][$ds])) $measurements[$_REQUEST["id"]]["dataset"][$ds] = array();
          $measurements[$_REQUEST["id"]]["dataset"][$ds]["_data"] = $_FILES["dataUp" . $key]['name'];
          $build = true;
        }
        
        // 2.2 process annotations
        if (($_REQUEST["annoUpRadio" . $key] == "new") and (is_uploaded_file($_FILES["annoUp" . $key]['tmp_name'])))
        {
          // check and copy file
          $ext = ".anno";
          $error = checkUpload("annoUp" . $key, $opdir, $fn . $ext);
          if ($error) throw new RuntimeException($error);
          
          // read file
          $data =  importfilter_anno($opdir . $fn . $ext);
          if (!$data) throw new RuntimeException('Failed to convert ' . $fn . $ext . '.');
          
          // merge with metadata, $measurements and set $build
          $json["dataset"][$ds]["anno"] = $data;
          $measurements[$_REQUEST["id"]]["dataset"][$ds]["_anno"] = $_FILES["annoUp" . $key]['name'];
          $build = True;
        }
        elseif ($_REQUEST["annoUpRadio" . $key] == "del")
        {
          unlink($opdir . $fn . ".anno");
          unset($json["dataset"]["ds"]["anno"],
                $measurements[$_REQUEST["id"]]["dataset"][$ds]["_anno"]);
          $build = True;
        }
        
        // 2.3 process supplementary files
        //   these will be renamed {$id}__{$ds}__{$filename}.{$ext}
        if (is_uploaded_file($_FILES["binUp" . $key]['tmp_name'][0])) //at least one file was uploaded
        {
          // don't allow uploading .json or .anno files that would overwrite our uploaded data and annotations
          $arr = array("json", "anno");
          foreach ($_FILES["binUp" . $key]['name'] as $file)
          {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, $arr)) 
              throw new RuntimeException($ext . ' files cannot be uploaded as binary files.');
          }
          
          // copy files to our operation data directory
          $error = checkMultiUpload("binUp" . $key, $opdir, $fn . "__");
          if (!$error)  // update $measurements...[_bin]
          {
            foreach ($_FILES["binUp" . $key]['name'] as $file)
            {
              if (!isset($measurements[$_REQUEST["id"]]["dataset"][$ds]["_bin"]))
                $measurements[$_REQUEST["id"]]["dataset"][$ds]["_bin"] = array();
              if (!in_array($file, $measurements[$_REQUEST["id"]]["dataset"][$ds]["_bin"]))
                $measurements[$_REQUEST["id"]]["dataset"][$ds]["_bin"][] = $file;
            }
          }
          else throw new RuntimeException($error);
          
          $build = True;
        }
      }
      
      // 3. build and update _built
      if ($build)
      {
        // build JSON data file
        $error = writeJSONfile($opdir . $_REQUEST["id"] . ".json", $json);
        if ($error) throw new RuntimeException($error);
        
        // set _built field in $measurements
        if (isset($measurements[$_REQUEST["id"]]["_built"])) $measurements[$_REQUEST["id"]]["_built"]++;
        else $measurements[$_REQUEST["id"]]["_built"] = 1;
        
        // rebuild JSON measurements_inflated file
        $error = writeJSONfile($opdir . "_3_inflated.json", $measurements);
        if ($error) throw new RuntimeException($error);
      }
      
      goto STEP5;
    }
    
    catch (RuntimeException $e) 
    {
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
    try
    {
      $i = array_search($_REQUEST["f"], $measurements[$_REQUEST["id"]]["dataset"][$_REQUEST["ds"]]["_bin"]);
      if ($i)
      {
        // delete file
        $fn = $_REQUEST["id"] . (($ds == 'default')?"":"__".$ds) . "__" . $_REQUEST["f"];
        $success = unlink($opdir . $fn);
        if (!$success) throw new RuntimeException("Could not remove " . $_REQUEST["f"]);
        
        // log in inflated json
        unset($measurements[$_REQUEST["id"]]["dataset"][$ds]["_bin"][$i]);
        $error = writeJSONfile($opdir . "_3_inflated.json", $measurements);
        if ($error) throw new RuntimeException($error);
      }
      goto STEP6;
    }
    catch (RuntimeException $e) 
    {
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
       . "      operation: " . $op . "<br>\n"
       . "      action: " . $action . "<br>\n"
       . "    </strong><br><br>\n\n";
    
    $libdir = LIB_DIR . $_REQUEST["lib"] . "/";
    
    try 
    {
      // open the library measurements.json (or a empty array in case of replace) as $result
      if (($action == "append") or ($action == "update"))
        $result = readJSONfile($libdir . "measurements.json", false);
      elseif ($action == "replace")
        $result = array();
      else 
        throw new RuntimeException("Unknown action " . $action . " in operation " . $op);
      
      // walk through our measurements that need to be added or updated
      unset($measurements["_datasets"]);
      foreach ($measurements as $id => $measurement)
      {
        if (array_key_exists($id, $result) and ($action == "append"))
          echo "<strong>WARNING</strong> " . $id . " already exists. Skipping.<br>\n";
        else
        {
          $result[$id] = $measurement;
          $result[$id]["_operation"] = $op;
          echo "<strong>Merged</strong> " . $id . ".<br>\n";
        }
      }
      
      // make the resulting measurements in the operation directory and make hardlink into the library directory (= publish it)
      $path = $opdir . "_5_result_" . date("YmdHis") . ".json";
      $error = writeJSONfile($path, $result);
      if ($error) throw new RuntimeException($error);
      unlink($libdir . "measurements.json");
      $success = link($path, $libdir . "measurements.json");
      if ($success) echo "<strong>Published</strong> library file<br><br>\n";
      else          throw new RuntimeException("Could not publish the data into the library!");
      
      // some administration in openops and closedops.json
      $closedops = readJSONfile($libdir . "closedops.json", false);
      $closedops[$op] = $operations[$op];
      $closedops[$op]["timestamp"] = mdate();
      unset($operations[$op]);
      writeJSONfile($libdir . "closedops.json", $closedops);
      writeJSONfile($libdir . "openops.json", $operations);
      
      echo "      <span style='color:red'>Data successfully merged into library ". $_REQUEST["lib"] ."</span><br>\n";
    }
    catch (RuntimeException $e) 
    { 
      $errormsg = $e->getMessage();
      $errormsg .= " PLEASE CHECK THE STATUS OF LIBRARY " . $_REQUEST["lib"] ;
      eventLog("ERROR", $errormsg  . " [module_import: STEP9]", false, true);  //don't exit, but send alertmail
      echo "    <span style='color:red'>ERROR: " . $errormsg ."</span><br><br>";
      
      // Update openops.json
      $operations[$op]["step"] = 10;  // lock this operation, so that a sysadmin can look into it
      $error = writeJSONfile(LIB_DIR . $_REQUEST["lib"] . "/openops.json", $operations);
    }
  }

  
  
THEEND:

?>
