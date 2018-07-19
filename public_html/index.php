<?php

require_once('install.conf.php');
//require_once(PRIVPATH . 'entropy.conf.php');
require_once(PRIVPATH . 'inc/autoloader.php');
require_once(PRIVPATH . 'inc/init.inc.php');
require_once(PRIVPATH . 'inc/common_basic.inc.php');
require_once(PRIVPATH . 'inc/common_cookie.inc.php');
require_once(PRIVPATH . 'inc/common_mailhide.inc.php');

/*
replacement for index.php
- without arguments                             --> open list, and will show landingspage or first lib
- $_REQUEST["lib"]                              --> open list (if lib exists, if we have rights)
- $_REQUEST["lib"] + ["id"] (+ ["ds"])          --> open data viewer (if lib exists, if we have rights)
- $_REQUEST["lib"] + ["id"] (+ ["ds"]) + ["dl"] --> open download (if lib exists, if we have rights)

if some argument does not exist or we have no rights: errormsg + followed by higher level
so first check lib, then idl, then ds, then format
*/

$showMod = false;
$error = false;

// EVALUATE $_REQUEST["lib"]
if (isset($_REQUEST["lib"])) {
    $showLib = strtolower($_REQUEST['lib']);
    
    // library exists?
    if (!isset($LIBS[$showLib])) {
        $error = "The requested library does not exist: " . $showLib;
    } else {
        // we have access to this lib?
        if (($LIBS[$showLib]["view"] == "locked") or !isset($LIBS[$showLib]["view"])) {
            if (!$isLoggedIn) {
                $error = "Access to " . $showLib . " library is restricted. Please log in";
            } elseif (!calcPermLib($user["permissions"], "view", $showLib)) {
                $error = "User " . $isLoggedIn . " has no authorisation to access library " . $showLib;
            }
        }
    }

    if ($error) {
        $showMod = "default";
    }
} else {
    $showMod = "default";
}

// EVALUATE LANDING PAGE OR FIRST ACCESSIBLE LIBRARY
if ($showMod == "default") {
    $showLib = false;
    // if the special _START "library" is active, show this by default
    if (isset($LIBS["_START"]) and strtolower($LIBS["_START"]["view"]) == "public") {
        $showMod = "list";
        $showLib = "_START";
    } else {
        foreach ($LIBS as $libid => $lib) {
            if (strtolower($libid) != "_START") {
                if (($isLoggedIn and calcPermLib($user["permissions"], "view", $libid))
            or (strtolower($lib["view"]) == "public")
            ) {
                    $showLib = $libid;
                }
            }
            //break from foreach if we found an accessible default library
            if ($showLib) {
                $showMod = "list";
                break;
            }
        }
    }
}

// if mode still is default, this means no landingpage or no accessible library was found!
// TODO maybe we should goto the login page instead, hoping there are private libraries??
if ($showMod == "default") {
    eventLog("ERROR", "No data to show [index]", true);
}

// EVALUATE $_REQUEST["id"]
if (!$showMod) {
    // read measurements list file
    $librariesPath = \Core\Config\App::get("libraries_path");
    $measurementList = readJSONfile($librariesPath . $showLib . "/measurements.json", false);
    
    if (isset($_REQUEST["id"])) {
        $showID = $_REQUEST['id'];
    
        // id exists?
        if (!isset($measurementList[$showID])) {
            $error = "The requested measurement does not exist";
        }
    
        // does the measurment have an _transaction field?
        if (isset($measurementList[$showID]["_transaction"])) {
            $dataPath = $librariesPath . $showLib . "/" . $measurementList[$showID]["_transaction"] . "/" . $showID;
        } else {
            $error = "The requested measurement has no transaction id";
        }
    
        // find the data file in the transaction directory
        $measurement = readJSONfile($dataPath . ".json", true);
        if (count($measurement) == 0) {
            $error = "The requested measurement was not found or was empty";
        }
    
        if ($error) {
            $showMod = "list";
            unset($dataPath, $measurement);
        }
    } else {
        $showMod = "list";
    }
}

// EVALUATE $_REQUEST["ds"]
if (!$showMod) {
    // reduce $measurementList to just the measurement we need
    $measurementListItem = $measurementList[$showID];
    unset($measurementList);
    
    if (isset($_REQUEST["ds"])) {
        if (isset($measurement["datasets"][$_REQUEST["ds"]])) {
            $showDS = $_REQUEST["ds"];
        } else {
            $error = "The requested dataset does not exist";
            $showMod = "view";
        }
    } else {
        $showMod = "view";
    }

    if (!isset($showDS)) {  //if at this point no dataset is set, either choose 'default', or the first
        if (isset($measurement["datasets"]["default"])) {
            $showDS = "default";
        } else {
            reset($measurement["datasets"]);
            $showDS = key($measurement["datasets"]);
        }
    }
}

// EVALUATE $_REQUEST["dl"]
if (!$showMod) {
    if (isset($_REQUEST["dl"])) {
        $showMod = "download";
    } else {
        $showMod = "view";
    }
}


// load the module
if (!in_array($showMod, Array("list", "view", "download"))) {
    $showMod = "list";
}
require_once(PRIVPATH . 'modules/' . $showMod . '/main.php');