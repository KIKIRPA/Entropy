<?php
  require_once('../entropy.conf.inc.php');
  require_once(ENTROPY_PATH . 'inc/init.inc.php');
  require_once(ENTROPY_PATH . 'inc/common_basic.inc.php');
  require_once(ENTROPY_PATH . 'inc/common_mailhide.inc.php');
  require_once(ENTROPY_PATH . 'inc/common_writefile.inc.php');
  
  
  // security measures!
   
  if (!IS_HTTPS)
  {
    $module = "empty";
    $msg = "A https connection is required for this page.";
  }
  
  if (IS_BLACKLISTED)
  {
    $module = "empty";
    $msg = "This IP has been blacklisted due to too many failed login attempts. Please contact the system administrator.";
  }
  
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
  $style   = "    <link rel='stylesheet' type='text/css' href='https://cdn.datatables.net/v/dt/dt-1.10.13/fc-3.2.2/fh-3.1.2/datatables.min.css'>\n"
           . "    <link rel='stylesheet' type='text/css' src='https://cdnjs.cloudflare.com/ajax/libs/dygraph/2.0.0/dygraph.min.css'>\n";
  $scripts = "    <script type='text/javascript' src='https://cdn.datatables.net/v/dt/dt-1.10.13/fc-3.2.2/fh-3.1.2/datatables.min.js'async></script>\n"
           . "    <script type='text/javascript' src='https://cdnjs.cloudflare.com/ajax/libs/dygraph/2.0.0/dygraph.min.js' async></script>\n";
  
  include(HEADER_FILE); 
  if (!$error) include(ENTROPY_PATH . 'inc/module_' . $_REQUEST["mod"] . '.inc.php');
  else         echo $error;
  include(FOOTER_FILE);
  
?>
