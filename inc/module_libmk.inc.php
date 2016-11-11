<?php
  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }

  $libmk = true;
  
  include('./inc/module_libedit.inc.php');
  
  if (($f == "set") and ($output == false))
  {
    //----------------------------------------------//
    // give the creator access rights in users.json //
    //----------------------------------------------//
    
    $users = readJSONfile(USERS_FILE, true);
    
    if (isset($users[$is_logged_in]) and isset($users[$is_logged_in]["permissions"]))
    {
      foreach ($modules_lib as $mod => $value)
      { 
        if ( isset($users[$is_logged_in]["permissions"][$mod])
              and !in_array("_ALL", $users[$is_logged_in]["permissions"][$mod])
              and !in_array("_NONE", $users[$is_logged_in]["permissions"][$mod])
              and !in_array($id, $users[$is_logged_in]["permissions"][$mod])
           )
          array_push($users[$is_logged_in]["permissions"][$mod], $id);
        else 
          $users[$is_logged_in]["permissions"][$mod] = array($id);
      }
      
      $error = writeJSONfile(USERS_FILE, $users);      

      if ($error)
      {
        echo "<span style='color:red'>ERROR: " . $error . "!</span><br><br>\n";
        eventLog("ERROR", "Could not make library: " .$error . " [module_libmk]", false, false);
      }
      
    }
    else
    {
      echo "<span style='color:red'>ERROR: user permission error.</span><br><br>\n";
      eventLog("ERROR", "User permission error. [module_libmk]", false, false);
    }
    
    // create directory?
    if (!mkdir2(LIB_DIR . $id . "/"))
    {
      echo "<span style='color:red'>ERROR: could not create directory.</span><br><br>\n";
      eventLog("ERROR", "Could not create directory. [module_libmk]", false, true);
    }
    
  } 
  
?>

  
  
  
