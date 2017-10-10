<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}


/* ***************
    I. conditions
   ***************

   1. evaluate $_REQUEST[lib & id & ds]   --> TODO: move from
   2. evaluate download code
   3. evaluate download logging (via login, cookie or form)
   => if conditions are not met, fallback to view module, with error notification
*/

try {
    // 2. evaluate download code
    $code = decode($_REQUEST["dl"]);
    if ($code == "") {
        throw new Exception("Download failed: nothing to download");
    }

    $code = explode("=", $code);
    if (count($code) != 2) {
        throw new Exception("Download failed: error in download code");
    }

    if ($code[0] == "bin") { // search the binary file
        if (!file_exists($code[1])) {
            throw new Exception("Download failed: binary file not found.");
        }
    } elseif ($code[0] == "conv") { // is conversion allowed in library file?  TODO: is allowed in conversion settings json?
        if (!in_array($code[1], $LIBS[$showlib]["allowformat"])) {
            throw new Exception("Download failed: conversion to ". $code[1] ." not allowed.");
        }
    } else {
        throw new Exception("Error in download code");
    }

    // 3. evaluate download logging (via login, cookie or form)
    if (LOG_DL) {
        if ($is_logged_in) {
            $log = array($USERS[$is_logged_in]["name"], $USERS[$is_logged_in]["institution"], $USERS[$is_logged_in]["email"], "login");
        } elseif (isset($_COOKIE[COOKIE_NAME])) {
            if (verifycookie($_COOKIE[COOKIE_NAME])) {
                $log = array($USERS[$is_logged_in]["name"], $USERS[$is_logged_in]["institution"], $USERS[$is_logged_in]["email"], "cookie");
            } else {
                removecookie();   // remove invalid cookie
                throw new Exception("Download failed: invalid cookie.");
            }
        } elseif (isset($_REQUEST["name"]) and isset($_REQUEST["institution"]) and isset($_REQUEST["email"])) {
            $cookie = verifycookie($_REQUEST["name"], $_REQUEST["institution"], $_REQUEST["email"]);
            if ($cookie) {
                $log = array($USERS[$is_logged_in]["name"], $USERS[$is_logged_in]["institution"], $USERS[$is_logged_in]["email"], "form");
                if (isset($_REQUEST["cookie"])) {
                    $cookie = makecookie($cookie);
                } // set cookie, if the user checked the checkbox
            } else {
                throw new Exception("Download failed: invalid name, institution or e-mail address.");
            }
        } else {
            throw new Exception("Download failed: no identification.");
        }
    }
} catch (Exception $e) {
    $errormsg = $e->getMessage();
    eventLog("WARNING", $errormsg  . " [download]");

    // FALLBACK TO VIEW MODULE
    require_once(PRIVPATH . 'inc/index_view.inc.php');
}



/* ******************
    II. calculations
   ******************

   1. download log (if enabled)
   2. prepare download
*/

// 1. download log (if enabled)
// CSV columns: timestamp | name | institution | email | source | IP | library | measurement | dataset | conv/bin | format/target | RESERVED*
//  (*) reserved for (optional) conversion rules (eg TXT: tabulated, comma separated, eg SPC: old format), stored in $code[2]  --> TODO
if (LOG_DL) {
    array_unshift($log, date('Y-m-d H:i'));
    array_push($log, $_SERVER['REMOTE_ADDR'], $showlib, $showid, $showds, $code[0], $code[1], "");

    // open or create download.csv and append line
    $handle = fopen(LOG_DL_FILE, "a");
    if ($handle) {
        $success = fputcsv($handle, $log);
    } else {
        $success = false;
    }
    if ($success) {
        $success = fclose($handle);
    }

    if (!$success) {
        eventLog("WARNING", "could not write to download log [download]", false, true);
    }
}

// 2. prepare download
if ($code[0] == "conv") {
    //TODO: integrate convert-framework (check if we are able to convert to $format, preferably replacing the switch by a function)
    //$export = export($data["dataset"][$ds], $code, $EXPORT)
    $filename = $showid . (($showds == 'default')?"":"__".$showds) . "." . $code[1];

    /*
    switch ($code[1]) {
        case "dx":
        case "jdx":
            $file = ;
            break;
        case "ascii":
        case "txt":

            break;
    }
    */
    echo "Conversion is not implemented yet!";
    die();
} elseif ($code[0] == "bin") {
    $filename = basename($code[1]);
}



/* *************
    III. output
   ************* */

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

if ($code[0] == "bin") {
    header('Content-Length: ' . filesize($code[1]));
    readfile($code[1]);
} elseif ($code[0] == "conv") {
    header('Content-Length: ' . strlen($export));
    echo $export;
}

exit;
