<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

require_once(PRIVPATH . 'inc/common_convert.inc.php');

try {
    /* ************
        conditions
       ************
    
       - evaluate $_REQUEST[lib & id & ds]   --> TODO: move from
       - evaluate download code
       - evaluate download logging (via login, cookie or form)
       => if conditions are not met, fallback to view module, with error notification
    */

    // evaluate download code
    $code = decode($_REQUEST["dl"]);
    if ($code == "") {
        throw new \Exception("Download failed: nothing to download");
    }

    $code = explode("=", $code);
    if (count($code) != 2) {
        throw new \Exception("Download failed: error in download code");
    }

    if ($code[0] == "bin") {
        $extension = strtolower(pathinfo($code[1], PATHINFO_EXTENSION));
        if (!file_exists($code[1])) { // search the binary file
            throw new \Exception("Download failed: binary file not found.");
        }
        elseif (!(in_array($extension, $LIBS[$showLib]["downloadbinary"]) or in_array("_ALL", $LIBS[$showLib]["downloadbinary"])) or in_array("_NONE", $LIBS[$showLib]["downloadbinary"])) {
            throw new \Exception("Download failed: binary download not allowed.");
        }
    } elseif ($code[0] == "conv") { // is conversion allowed in library file?  TODO: is allowed in conversion settings json?
        if (!in_array($code[1], $LIBS[$showLib]["downloadconverted"])) {
            throw new \Exception("Download failed: conversion to ". $code[1] ." not allowed.");
        }
        
        // separate metadata, data and export-options
        $meta = overrideMeta($data, $showDS);
        $data = $data["dataset"][$showDS]["data"];
        //$units

        if (isset($data["options"]["export"])) 
            $exportOptions = $data["options"]["export"];
        else
            $exportOptions = array(); 

        // remove things from meta that are not metadata (in a broad sense: keep units, type, id)
        unset($meta["annotations"], $meta["attachements"], $meta["options"], $meta["data"], $meta["linkeddata"]);

        // set/adjust license
        if (!isset($meta["license"])) { // if no license in data file, search license in library or system settings
            if (isset($LIBS[$showLib]["license"])) {
                $meta["license"] = $LIBS[$showLib]["license"];
            } elseif (!empty(\Core\Config\App::get("license_default"))) {
                $meta["license"] = \Core\Config\App::get("license_default");
            }
        }
        if (isset($meta["license"])) { // if the license is a predefined one, replace it with the textonly version
            $textonly = \Core\Config\Licenses::searchForNeedle($viewLicense, "textonly");
            if ($textonly) {
                $meta["license"] = $textonly;
            }
        }

    } else {
        throw new \Exception("Error in download code");
    }

    // evaluate download logging (via login, cookie or form)
    if (\Core\Config\App::get("downloads_log_enable")) {
        if ($isLoggedIn) {
            $downloadLogEntry = array($USERS[$isLoggedIn]["name"], $USERS[$isLoggedIn]["institution"], $USERS[$isLoggedIn]["email"], "login");
        } elseif (isset($_COOKIE[\Core\Config\App::get("downloads_cookie_name")])) {
            $cookie = verifycookie($_COOKIE[\Core\Config\App::get("downloads_cookie_name")]);
            if ($cookie) {
                $downloadLogEntry = $cookie;
                $downloadLogEntry[] = "cookie";
            } else {
                removecookie();   // remove invalid cookie
                throw new \Exception("Download failed: invalid cookie.");
            }
        } elseif (isset($_REQUEST["name"]) and isset($_REQUEST["institution"]) and isset($_REQUEST["email"])) {
            $cookie = verifycookie($_REQUEST["name"], $_REQUEST["institution"], $_REQUEST["email"]);
            if ($cookie) {
                $downloadLogEntry = array($_REQUEST["name"], $_REQUEST["institution"], $_REQUEST["email"], "form");
                if (isset($_REQUEST["cookie"])) {
                    $cookie = makecookie($cookie);
                } // set cookie, if the user checked the checkbox
            } else {
                throw new \Exception("Download failed: invalid name, institution or e-mail address.");
            }
        } else {
            throw new \Exception("Download failed: no identification.");
        }
    }

    
    /* ******************
        prepare download
       ****************** */
    
    // CONVERSION
    if ($code[0] == "conv") {
        // filename for the download (extension: the last part of $code[1])
        $temp = explode(":", $code[1]);
        $fileName = $showID . (($showDS == 'default') ? "" : "_" . $showDS) . "." . end($temp);

        // select convertor and assemble all export options
        $exportOptions = selectConvertorClass($EXPORT, findDataType($meta["type"], $DATATYPES), $code[1], $exportOptions);
                
        if (isset($exportOptions["convertor"])) {
            // create convertor        
            $className = "Convert\\Export\\" . ucfirst(strtolower($exportOptions["convertor"]));
            $export = new $className($showID, $data, $meta, $exportOptions, $DATATYPES);
            $fileHandle = $export->getFile();
            $error = $export->getError();

            if ($error or !$fileHandle) {
                throw new \Exception("Failed to create " . $fileName);
            }
        } else {
            throw new \Exception("Failed to create " . $fileName . ": no convertor found.");
        }
    
    
    // BINARY
    } elseif ($code[0] == "bin") {
        // filename for the download
        $fileName = basename($code[1]);
        $fileName = end(explode("__", $fileName));  //download the binary files with the original filename (= part following the last "__")

        // open the binary file as a handle
        $fileHandle = fopen($code[1], "r");
    
        if (!$fileHandle) {
            throw new \Exception("Failed to fetch " . $fileName);
        }
    }
    
    // file size from the open handle
    $stat = fstat($fileHandle);
    $fileSize = $stat["size"];

} catch (\Exception $e) {
    $errormsg = $e->getMessage();
    eventLog("ERROR", $errormsg  . " [download]");

    // FALLBACK TO VIEW MODULE
    require_once(PRIVPATH . 'modules/view/main.php');
}


/* ***************
    download log
   *************** */

// CSV columns: timestamp | name | institution | email | source | IP | library | measurement | dataset | conv/bin | format/target | RESERVED*
//  (*) reserved for (optional) conversion rules (eg TXT: tabulated, comma separated, eg SPC: old format), stored in $code[2]  --> TODO
if (\Core\Config\App::get("downloads_log_enable")) {
    array_unshift($downloadLogEntry, date('Y-m-d H:i'));
    array_push($downloadLogEntry, $_SERVER['REMOTE_ADDR'], $showLib, $showID, $showDS, $code[0], $code[1], "");

    // open or create download.csv and append line
    $logHandle = fopen(\Core\Config\App::get("downloads_log_file"), "a");
    
    if ($logHandle) {
        $success = fputcsv($logHandle, $downloadLogEntry);
    } else {
        $success = false;
    }
    
    fclose($logHandle);

    if (!$success) {
        eventLog("WARNING", "could not write to download log [download]", false, true);
    }
}


/* ********
output
******** */

// echo "DEBUG OUTPUT<br><br>";
// echo "filename = " . $fileName . "<br>";
// echo "filesize = " . $fileSize . "<br><br>";
// fpassthru($fileHandle);
// fclose($fileHandle);
// die();

require_once(__DIR__ . '/template.php');