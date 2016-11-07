<?php
  //error_reporting(E_ALL);
  //ini_set('display_errors', '1');
  
  require_once('./_config/config.inc.php');
  require_once('./inc/common_cookie.inc.php');
  require_once('./inc/convert_basic.inc.php');
  require_once('./inc/common_mailhide.inc.php');
  
/*
replacement for index.php
 - without arguments                             --> open start page
 - $_REQUEST["lib"]                              --> open list (if lib exists, if we have rights)
 - $_REQUEST["lib"] + ["id"] (+ ["ds"])          --> open data viewer (if lib exists, if we have rights)
 - $_REQUEST["lib"] + ["id"] (+ ["ds"]) + ["dl"] --> open dl (if lib exists, if we have rights)

 if some argument does not exist or we have no rights: errormsg + followed by higher level
 so first check lib, then idl, then ds, then format
*/

  $mode = False;
  $error = False;

  // EVALUATE $_REQUEST["lib"]
  if (isset($_REQUEST["lib"]))
  {
    $showlib = strtolower($_REQUEST['lib']);
    
    // library exists?
    if (!isset($libs[$showlib]))
      $error = "The requested library does not exist";
    
    // we have access to this lib?
    if (($libs[$showlib]["view"] == "locked") or !isset($libs[$showlib]["view"]))
    {
      if (!$is_logged_in)
        $error = "Access to this library is restricted. Please log in";
      elseif (!calcPermLib($user["permissions"], "view", $showlib)) 
        $error = "User " . $is_logged_in . " has no authorisation to access this library.";
    }

    if ($error) $mode = "start";
  }
  else $mode = "start";
  
  // EVALUATE $_REQUEST["id"]
  if (!$mode)
  {
    // read measurements list file
    $measurements = readJSONfile(LIB_DIR . $showlib . "/measurements.json", False);
    
    if (isset($_REQUEST["id"]))
    {
      $showid = $_REQUEST['id'];
      
      // id exists?
      if (!isset($measurements[$showid]))
        $error = "The requested measurement does not exist";
      
      // does the measurment have an _operation field?
      if (isset($measurements[$showid]["_operation"]))
       $datapath = LIB_DIR . $showlib . "/" . $measurements[$showid]["_operation"] . "/" . $showid;
      else
        $error = "The requested measurement has no operation id";
      
      // find the data file in the operation LIB_DIR
      $data = readJSONfile($datapath . ".json", True);
      if (count($data) == 0)
        $error = "The requested measurement was not found or was empty";
      
      if ($error) 
      {
        $mode = "list";
        unset($datapath, $data);
      }
    }
    else $mode = "list";
  }
  
  // EVALUATE $_REQUEST["ds"]
  if (!$mode)
  {
    // reduce $measurements to just the measurement we need
    $measurements = $measurements[$showid];
    
    if (isset($_REQUEST["ds"]))
    {
      if (isset($measurements["dataset"][$_REQUEST["ds"]])) 
        $showds = $_REQUEST["ds"];
      else 
      {
        $error = "The requested dataset does not exist";
        $mode = "view";
        $ds = "default";
      }
    }
    else 
    {
      $mode = "view";
      $ds = "default";
    }
  }
  
  // EVALUATE $_REQUEST["dl"]
  if (!$mode)
  {
    if (isset($_REQUEST["dl"]))
    {
      //TODO if we can convert it                 --> $mode = "dl";
      //TODO elseif it is an existing binary file --> $mode = "dl";
      //TODO else                                 --> error + $mode = "view";
      //TODO it may be better to split "dl" up into "convert" and "bin"; 
      //     we don't want to check this 3times (on view page, here and in dl page)
      $mode = "dl";
    }
    else $mode = "view";
  }
  
  
  //
  switch ($mode) 
  {
    case "dl":
      require_once('./inc/index_dl.inc.php');
      break;
    case "view":
      require_once('./inc/index_view.inc.php');
      break;
    case "list":
      require_once('./inc/index_list.inc.php');
      break;
    default:
    case "start":
      require_once('./inc/index_start.inc.php');
      break;
  }
  
?>