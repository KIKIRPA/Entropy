<?php
  //error_reporting(E_ALL);
  //ini_set('display_errors', '1');
  
  require_once('./inc//init.inc.php');
  require_once('./inc/common_basic.inc.php');
  require_once('./inc/common_mailhide.inc.php');
  require_once('./inc/common_writefile.inc.php');
  
  
  // security measures!
   
  if (!IS_HTTPS or IS_BLACKLISTED)
    include("./inc/error_404.inc.php");
  
  if ($is_logged_in)
  {
    if (isset($_REQUEST["mod"]))
    {
      if (isset($MODULES["adm"][$_REQUEST["mod"]]))
      {
        if (calcPermLib($user["permissions"], $_REQUEST["mod"]))
          $error = false;
        else
          $error = "User " . $is_logged_in . " is not authorised to use module " . $_REQUEST["mod"] . "!";
      }
      elseif (isset($MODULES["lib"][$_REQUEST["mod"]]))
      {
        if (isset($_REQUEST["lib"]))
        {
          if (calcPermLib($user["permissions"], $_REQUEST["mod"], $_REQUEST["lib"]))
            $error = false;
          else 
            $error = "User " . $is_logged_in . " is not authorised to use module " . $_REQUEST["mod"] . " for library " . $_REQUEST["lib"] . "!";
        }
        else
          $error = "Could not process your request: module " . $_REQUEST["mod"] . " requires a library to run!";
      }
      else $error = "Could not process your request: module " . $_REQUEST["mod"] . " was not found!";
    }
    else $error = "Could not process your request: supply a module to run!";
  }
  else $error = "Log in to view this page!";
  
   
  //HEADER
  $LIBS = json_decode(file_get_contents(LIB_FILE), true);
  $htmltitle = APP_SHORT . ": administration";
  $htmlkeywords = APP_KEYWORDS;
  $pagetitle = APP_LONG;
  $pagesubtitle = "Library tools";
  $style   = "    <link rel='stylesheet' type='text/css' href='./javascript/dygraphs/dygraph.css'>\n";
  $scripts = "    <!--[if IE]><script type='text/javascript' charset='utf8' src='./javascript/excanvas.compiled.js'></script><![endif]-->\n"
           . "    <script type='text/javascript' charset='utf8' src='./javascript/dygraphs/dygraph.min.js'></script>\n";
  
  include(HEADER_FILE); 
  if (!$error) include("./inc/module_" . $_REQUEST["mod"] . ".inc.php");
  else         echo $error;
  include(FOOTER_FILE);
  
?>
