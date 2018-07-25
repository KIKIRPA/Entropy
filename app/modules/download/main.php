<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

require_once(PRIVPATH . 'inc/common_convert.inc.php');

try {
    /* 
     * 1. evaluate and get download code
     */
    $code = new \Core\Service\DownloadCode();
    if ($code->retrieve($_REQUEST["dl"]) === false) {
        throw new \Exception("Download failed: the requested downloadcode is invalid or expired");
    }

    $dlType = $code->getType();
    $i = ((isset($_REQUEST["i"])) ? $_REQUEST["i"] : null);
    $dlValue = $code->getValue($dlType, $i);
    if (is_null($dlValue)) {
        throw new \Exception("Download failed: the requested downloadcode is invalid");
    }
    
    /* 
     * 2. evaluate if the requested lib, id and ds (in $_REQUEST) correspond with those stored in the downloadcode
     */
    $dlConditions = $code->checkCondition();
    $ok = true;
    if (is_array($dlConditions)) {
        foreach ($dlConditions as $key => $value) {
            $ok = ($ok and ($_REQUEST[$key] == $value));
        }
    }
    if (!$ok) {
        throw new \Exception("Download failed: downloadcode mismatch");
    }


    /* 
     * 3. evaluate credentials supplied via login, cookie or form
     */
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
    
    /* 
     * 4. add an entry to the download log
     *
     * CSV columns: timestamp | name | institution | email | source | IP | library | measurement | dataset | conv/bin | format/target | RESERVED*
     *  (*) reserved for (optional) conversion rules (eg TXT: tabulated, comma separated, eg SPC: old format), stored in $code[2]  --> TODO
     */ 
    if (\Core\Config\App::get("downloads_log_enable")) {
        array_unshift($downloadLogEntry, date('Y-m-d H:i'));
        array_push($downloadLogEntry, $_SERVER['REMOTE_ADDR'], $showLib, $showID, $showDS, $dlType, $dlValue, "");

        // open or create download.csv and append line
        $logHandle = fopen(\Core\Config\App::get("downloads_log_file"), "a");
        $success = ($logHandle) ? fputcsv($logHandle, $downloadLogEntry) : false;
        
        fclose($logHandle);

        if (!$success) { //don't block the download if this occurs (no exception throwing), but send a mail to the system admin
            eventLog("WARNING", "could not write to download log [download]", false, true);
        }
    }

    /* 
     * 5. serve the file for download
     */
    switch ($dlType) {
        case "conv":
            // separate metadata, data and export-options
            $measurement = collapseMeasurement($measurement, $showDS);
            
            // set/adjust license
            if (!isset($measurement["license"])) { // if no license in data file, search license in library or system settings
                if (isset($LIBS[$showLib]["license"]))                    $measurement["license"] = $LIBS[$showLib]["license"];
                elseif (!empty(\Core\Config\App::get("license_default"))) $measurement["license"] = \Core\Config\App::get("license_default");
            }
            if (isset($measurement["license"])) { // if the license is a predefined one, replace it with the textonly version
                $textonly = \Core\Config\Licenses::searchForNeedle($measurement["license"], "textonly");
                if ($textonly) $measurement["license"] = $textonly;
            }
            
            // filename for the download (extension: the last part of $dlValue)
            $temp = explode(":", $dlValue);
            $fileName = $showID . (($showDS == 'default') ? "" : "_" . $showDS) . "." . end($temp);
        
            // select convertor and assemble all export options
            $exportOptions = selectConvertorClass($EXPORT,
                                                  findDataType($measurement["type"], $DATATYPES),
                                                  $dlValue, 
                                                  is_array($measurement["options"]["export"]) ? $measurement["options"]["export"] : $exportOptions = array()
                                                 );
                    
            if (isset($exportOptions["convertor"])) {
                // create convertor        
                $className = "Convert\\Export\\" . ucfirst(strtolower($exportOptions["convertor"]));
                $export = new $className($showID, $measurement, $exportOptions, $DATATYPES);
                $fileHandle = $export->getFile();
                $error = $export->getError();
        
                if ($error or !$fileHandle) {
                    throw new \Exception("Download failed: could not convert to " . $fileName);
                }
            } else {
                throw new \Exception("Download failed: no suitable convertor available for creating" . $fileName);
            }

            $result = \Core\Service\Download::handle($fileHandle, $fileName);
            if (!$result) throw new \Exception("Download failed: file not found or accessible");
            break;
            
        case "path":
        case "paths":
            $fileName = basename($dlValue);
            if (substr_count($dlValue, "__") > 0) {
                $fileName = end(explode("__", $fileName));  //support (depreciated) binary file naming convention: the original filename (= part following the last "__")
            }

            $result = \Core\Service\Download::path($dlValue, $fileName);
            if (!$result) throw new \Exception("Download failed: file not found or accessible");
            break;
            
        case "url":
            \Core\Service\Download::url($dlValue);
            if (!$result) throw new \Exception("Download failed: invalid URL");
            break;
    }
} catch (\Exception $e) {
    $errormsg = $e->getMessage();
    eventLog("ERROR", $errormsg  . " [download]");

    // FALLBACK TO VIEW MODULE
    require_once(PRIVPATH . 'modules/view/main.php');
}
