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
    echo "CODE: " . $code . "<br>";

    $code = explode("=", $code);
    if (count($code) != 2) {
        throw new Exception("Download failed: error in download code");
    }

    if ($code[0] == "bin") {
        $extension = strtolower(pathinfo($code[1], PATHINFO_EXTENSION));
        if (!file_exists($code[1])) { // search the binary file
            throw new Exception("Download failed: binary file not found.");
        }
        elseif (!(in_array($extension, $LIBS[$showLib]["downloadbinary"]) or in_array("_ALL", $LIBS[$showLib]["downloadbinary"])) or in_array("_NONE", $LIBS[$showLib]["downloadbinary"])) {
            throw new Exception("Download failed: binary download not allowed.");
        }
    } elseif ($code[0] == "conv") { // is conversion allowed in library file?  TODO: is allowed in conversion settings json?
        if (!in_array($code[1], $LIBS[$showLib]["downloadconverted"])) {
            throw new Exception("Download failed: conversion to ". $code[1] ." not allowed.");
        }
        
        // separate metadata, data and export-parameters
        $meta = overrideMeta($data, $showDS);
        //$data = $data["dataset"][$showDS]["data"];
        //$units

        if (isset($data["_export"])) $parameters = $meta["_export"];
        else                         $parameters = array(); 

        // remove metadata starting with underscore from meta

    } else {
        throw new Exception("Error in download code");
    }

    // 3. evaluate download logging (via login, cookie or form)
    if (LOG_DL) {
        if ($isLoggedIn) {
            $log = array($USERS[$isLoggedIn]["name"], $USERS[$isLoggedIn]["institution"], $USERS[$isLoggedIn]["email"], "login");
        } elseif (isset($_COOKIE[COOKIE_NAME])) {
            if (verifycookie($_COOKIE[COOKIE_NAME])) {
                $log = array($USERS[$isLoggedIn]["name"], $USERS[$isLoggedIn]["institution"], $USERS[$isLoggedIn]["email"], "cookie");
            } else {
                removecookie();   // remove invalid cookie
                throw new Exception("Download failed: invalid cookie.");
            }
        } elseif (isset($_REQUEST["name"]) and isset($_REQUEST["institution"]) and isset($_REQUEST["email"])) {
            $cookie = verifycookie($_REQUEST["name"], $_REQUEST["institution"], $_REQUEST["email"]);
            if ($cookie) {
                $log = array($_REQUEST["name"], $_REQUEST["institution"], $_REQUEST["email"], "form");
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
    echo "ERROR: " . $errormsg . "<br>"; //DEBUG

    // FALLBACK TO VIEW MODULE
    require_once(PRIVPATH . 'modules/view/main.php');
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
    array_push($log, $_SERVER['REMOTE_ADDR'], $showLib, $showID, $showDS, $code[0], $code[1], "");

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
    
    echo 'BUILD <br>'; 
    
    // select convertor and assemble all export parameters
    $parameters = selectConvertorClass($EXPORT, findDataType($data["type"], $DATATYPES), $code[1], $parameters);

    // echo "<br><pre>";
    // print_r($parameters);
    // echo "</pre><br><br>";

    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    if (isset($parameters["convertor"])) {
        echo "CONVERT: " . $parameters["convertor"]; //DEBUG

        // create convertor 
        $class = "Export" . sanitizeStr($parameters["convertor"], "", "-+:^", 2); // remove illegal symbols from class name (e.g. jcamp-dx -> JCAMPDX)
        unset($parameters["convertor"]);
        $export = new $class($data, $meta, $parameters);
        $filehandle = $export->getFile();
        
        if ($filehandle) {
            while (($line = fgets($filehandle)) !== false) {
                echo $line;
            }
        } else {
            echo "no output from export!<br>";
        }

        $error = $export->getError();
        if ($error) {
            //eventLog("WARNING", $error . " File: " . $_FILES["dataUp" . $key]['name'] . " [" . $class . "]");
            echo "FATAL ERROR: ". $error . "<br>"; //DEBUG
            die();
        }
    }






    // filename for the downloaded blob
    $code[1] = explode(":", $code[1]);
    $filename = $showID . (($showDS == 'default') ? "" : "_" . $showDS) . "." . end($code[1]);
    echo "FILENAME: " . $filename . "<br>"; //DEBUG
    die();


} elseif ($code[0] == "bin") {
    $filename = basename($code[1]);
    $filename = end(explode("__", $filename));  //download the binary files with the original filename (= part following the last "__")
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
    header('Content-Length: ' . strlen($file));
    echo $file;
}

exit;
