<?php
  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }


/* ***************
    I. conditions
   *************** 
   
   1. evaluate $_REQUEST
   2. evaluate download code 
   3. evaluate download logging (via login, cookie or form)
   => if conditions are not met, fallback to view module, with error notification
*/

  try
  {
    // 2. evaluate download code
    $code = decode($_REQUEST["dl"]);
    if ($code == "") throw new Exception("Download failed: nothing to download");
  
    $code = explode("=", $code);
    if (count($code) != 2) throw new Exception("Download failed: error in download code");

    if ($code[0] == "bin")
    { // search the binary file
      if (!file_exists($code[1])) throw new Exception("Download failed: binary file not found.");
    }
    elseif ($code[0] == "conv")
    { // is conversion allowed in library file?  TODO: is allowed in conversion settings json?
      if (!in_array($code[1], $LIBS[$showlib]["allowformat"])) throw new Exception("Download failed: conversion not allowed.");
    }
    else throw new Exception("Error in download code"); 

    // 3. evaluate download logging (via login, cookie or form)
    if (LOG_DL)
    {
      if (isset($_COOKIE[COOKIE_NAME]))
      {
        if (!verifycookie($_COOKIE[COOKIE_NAME]))
        {
          removecookie();   // remove invalid cookie
          throw new Exception("Download failed: invalid cookie.");
        }
      }
      elseif (    isset($_REQUEST["cookie"])
              and isset($_REQUEST["name"]) 
              and isset($_REQUEST["institution"]) 
              and isset($_REQUEST["email"]) )
      {
        $cookie = verifycookie($_REQUEST["name"], $_REQUEST["institution"], $_REQUEST["email"]);
        if ($cookie)
        {
          if (isset($_REQUEST["cookie"])) $cookie = makecookie($cookie); // set cookie, if the user checked the checkbox
        }
        else throw new Exception("Download failed: invalid name, institution or e-mail address.");
      }
      elseif (!$is_logged_in)                         
        throw new Exception("Download failed: no identification.");
    }
  }
  catch (Exception $e)
  {
    $errormsg = $e->getMessage();
    eventLog("WARNING", $errormsg  . " [download]");
    
    // FALLBACK TO VIEW MODULE
    require_once(INC_PATH . 'index_view.inc.php');
  }


  try 
  {
   
  }
  catch (Exception $e)
  {
    $errormsg = $e->getMessage();
    eventLog("WARNING", $errormsg  . " [index]");
    echo "    <span style='color:red'>WARNING: " . $errormsg . "</span><br><br>";
    $mode = "view";
  }









/* ******************
    II. calculations
   ****************** */

   if ($code[1] == "conv")
   {

   }
   elseif ($code[1] == "bin")
   {

   }


/* *************
    III. output
   ************* */





?>