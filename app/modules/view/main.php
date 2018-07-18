<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

// add javascript specific for this page to the list of js-files to load in the page header
array_push($htmlHeaderScripts, "./js/view.js");  

/* ***************
    main metadata
    *************** */

// data from the measurement list json file
// --> transaction
$transaction = $measurementListItem["_transaction"];

// --> tags
$tags = array("id", "type");
if (isset($LIBS[$_REQUEST["lib"]]["listcolumns"])) {
    if (!empty($LIBS[$_REQUEST["lib"]]["listcolumns"])) {
        $tags = $LIBS[$_REQUEST["lib"]]["listcolumns"];
    }     
}

$viewTags = array();
foreach ($tags as $tag) {
    if (isset($measurementListItem[$tag])) {
        $value = $measurementListItem[$tag];

        // taglist: don't include tagss starting with _ (e.g. _transaction) or having empty values
        if ((substr($tag, 0, 1) != "_") and (!empty($value))) {
            $value = strip_tags($value);
            if (strlen($value) > 30) {
                $value = substr($value, 0, 27) . "...";
            }
            $viewTags[nameMeta($tag)] = $value;
        }
    }
}

$viewColor = isset($LIBS[$showLib]["color"]) ? bulmaColorModifier($LIBS[$showLib]["color"], $COLORS, \Core\Config\App::get("app_color_default")) : bulmaColorModifier(\Core\Config\App::get("app_color_default"), $COLORS);

// datasets
if (is_array($measurement["datasets"])) {
    // make a list of datasets
    $datasetList = array_keys($measurement["datasets"]);

    // collapse measurement to only the chosen dataset
    $measurement = collapseMeasurement($measurement, $showDS);
} else {
    $datasetList = array();
    $measurement = $measurement;
}

unset($key, $value);


/* ***********
    downloads
    *********** */

// 1. is downloading allowed? (for logged-in users or guests)
// 2. what can be downloaded (we don't need to do any efforts if nothing can be downloaded)
// 3. is download logging enabled
// 4. do we have downloader name, institution and email stored in either login or downloadcookie?
// 5. if no: include download form


if (    (!$isLoggedIn and $MODULES["lib"]["download"]["public"])
     or ($isLoggedIn and calcPermLib($user["permissions"], "download", $showLib))) {
    
    // $viewShowModal boolean (show download form)
    if (\Core\Config\App::get("downloads_log_enable")) {
        if ($isLoggedIn) {
            $viewShowModal = false;
        } elseif (isset($_COOKIE[\Core\Config\App::get("downloads_cookie_name")])) {
            $cookie = verifycookie($_COOKIE[\Core\Config\App::get("downloads_cookie_name")]); // in case of false data, this will output False
            if (is_array($cookie)) {
                $viewShowModal = !makecookie($cookie);
            }  // if not false: update cookie; makecookie always outputs TRUE, $viewShowModal needs to be FALSE
            else {
                $viewShowModal = removecookie();
            }        // remove invalid cookie; removecookie always outputs TRUE
            //TODO: invalid cookie notification!
        } else {
            $viewShowModal = true;
        }
    } else {
        $viewShowModal = false;
    }
    
    $viewDownloadButtons = array();
    $prefix = \Core\Config\App::get("downloads_storage_path");
    $dlc_conditions = array("lib" => $showLib,
                            "id"  => $showID,
                            "ds"  => $showDS
                           );
    $allowedExtensions = getAllowedExtensions($LIBS[$showLib]["downloadbinary"]);
    
    // DATA -> downloadbuttons with conversion
    if (isset($measurement["data"])) {
        foreach ($LIBS[$showLib]["downloadconverted"] as $format) {
            // check if this format "[convertor:[datatype:]]extension" is allowed for this datatype (returns array if found, false if not found)
            $datatype = findDataType($measurement["type"], $DATATYPES);
            $result = selectConvertorClass($EXPORT, $datatype, $format);
            if ($result) {
                $buttonText = strtoupper($result["convertor"]) . " (." . strtolower(end(explode(":", $format, 3))) . ")";
                $dlc = new \Core\Service\DownloadCode();
                if ($dlc->setConversion($format, $dlc_conditions)) {
                    if (!is_null($dlc->store())) {
                        $viewDownloadButtons[] = $dlc->makeButtonCode($buttonText, $viewColor, $viewShowModal);
                    }
                }
            }
        }
    }

    // DATALINK -> downloadbutton with path/url
    // note: the Entropy data file specifications disallow to have both "data" and "datalink" in the same dataset, but if both are present anyway, we'll show both
    if (isset($measurement["datalink"])) {
        // datalink is not supplied directly but through redirect in download module (ensures download popup, logging and hides direct link) 
        $dlc = new \Core\Service\DownloadCode();
        if ($dlc->setPath($measurement["datalink"], $prefix, $dlc_conditions) > 0) {
            if (!is_null($dlc->store())) {
                // the button text is hardcoded "Download" at the moment. Would it be better to give it the file name (which may be very long)?
                $viewDownloadButtons[] = $dlc->makeButtonCode("Download", $viewColor, $viewShowModal);
            }
        }
    }

    // ATTACHMENTS -> downloadbuttons with path/url, inverted colors
    if (isset($measurement["attachments"]) and (bool)$LIBS[$showLib]["downloadbinary"]) {
        if (is_array($measurement["attachments"])) {
            foreach ($measurement["attachments"] as $key => $attachment) {
                // try to use the key as $buttonText in case it is an associative array, else the filename
                if (!is_numeric($key)) {
                    $buttonText = $key;
                } else {
                    $buttonText = basename($attachment);
                }
                
                $dlc = new \Core\Service\DownloadCode();
                if ($dlc->setPath($attachment, $prefix, $dlc_conditions, $allowedExtensions) > 0) {
                    if (!is_null($dlc->store())) {
                        $viewDownloadButtons[] = $dlc->makeButtonCode($buttonText, $viewColor, $viewShowModal, true, "paperclip");
                    }
                }
            }
        }
    }

    // BINARY FILES -> downloadbuttons with path/url, inverted colors
    // note: uploading binary files is no longer supported in Entropy 1.1 and higher
    //       this code is here to support data files created with Entropy 1.0, and will be removed at some point
    $binPath = \Core\Config\App::get("libraries_path") . $showLib . "/" . $transaction . "/" . $showID . (($showDS == 'default')?"":"__".$showDS)  . "__";
    $binFiles = glob($binpath . "*");  //by using "__*" we exclude the original (converted) data files, json data files and annotations
    foreach ($binFiles as $file) {
        $dlc = new \Core\Service\DownloadCode();
        if ($dlc->setPath($file, null, $dlc_conditions, $allowedExtensions) > 0) {
            if (!is_null($dlc->store())) {
                $buttonText = str_replace($binPath, "", $file); //remove the path, measurement id and dataset stuff from the file name (revert it to the original file name)
                $viewDownloadButtons[] = $dlc->makeButtonCode($buttonText, $viewColor, $viewShowModal, true, "paperclip");
            }
        }
    } 
}

unset ($cookie, $code, $format, $prefix, $binfiles);


/* ********
  license
  ******** */

$viewLicense = false;

// search license in data file, library or system settings
if (isset($measurement["license"]))                       $viewLicense = $measurement["license"];
elseif (isset($LIBS[$showLib]["license"]))                $viewLicense = $LIBS[$showLib]["license"];
elseif (!empty(\Core\Config\App::get("license_default"))) $viewLicense = \Core\Config\App::get("license_default");

// if the license is a predefined one, replace it with the html version
if ($viewLicense) {
    $viewLicenseHtml = \Core\Config\Licenses::searchForNeedle($viewLicense, "html");
    if ($viewLicenseHtml) $viewLicense = $viewLicenseHtml;
}

/* ********
  viewer
  ******** */

$parenttype = findDataType($measurement["type"], $DATATYPES);
$viewer = $DATATYPES[$parenttype]["viewer"];
$units = findDataTypeUnits($parenttype, $DATATYPES, 'html', $measurement["units"]);

if (isset($measurement["annotations"])) {
    if (is_array($measurement["annotations"])) {
        $anno = $measurement["annotations"];
        foreach ($anno as $i => $a) {
            $anno[$i]["series"] = reset($viewTags);
        }
        $anno = json_encode($anno);
    }
}


/* **********
    metadata
   ********** */

$viewMetadata = array();
$i = 0;
foreach ($measurement["meta"] as $key => $item) {
    $row = intdiv($i, 3);   // display 3 categories per row
    $header = nameMeta($key);
    if (is_array($item)) {
        // add _description fields to the header
        if (isset($item["_description"])) {
            if (is_array($item["_description"])) $item["_description"] = implode("<br>", $item["_description"]);
            $item["_description"] = \Core\Service\MailHider::search($item["_description"], ($isLoggedIn ? false : true));
            $item["_description"] = trim($item["_description"]);
            if (!empty($item["_description"])) $header .= "<br><div class=\"is-size-7 has-text-weight-light is-italic has-text-left\">" . $item["_description"] . "</div>";
            unset($item["_description"]);
        }
        foreach ($item as $subkey => $subitem) {
            $subitem = getMeta($measurement, "meta:" . $key . ":" . $subkey, "; ", false);
            $subitem = \Core\Service\MailHider::search($subitem, ($isLoggedIn ? false : true));
            $subkey = nameMeta("meta:" . $key . ":" . $subkey);
            $viewMetadata[$row][$header][$subkey] = $subitem;
        }
    } else {
        $item = \Core\Service\MailHider::search($item, ($isLoggedIn ? false : true));
        $viewMetadata[$row][$header] = $item;
    }
    $i++;
}

unset($i, $row, $header, $key, $item, $subkey, $subitem);


/* ******
    HTML
   ****** */

// HEADER + NAVBAR
array_push($htmlHeaderStyles, \Core\Config\App::get("css_dygraphs"));
array_push($htmlHeaderScripts, \Core\Config\App::get("js_dygraphs"));
array_push($htmlHeaderStyles, \Core\Config\App::get("css_flickity"));
array_push($htmlHeaderScripts, \Core\Config\App::get("js_flickity"));  
include(PRIVPATH . 'inc/header.inc.php');

// MAIN
if ($error) {
    echo $error . "<br><br>\n";
}
require_once(__DIR__ . '/template.php');

// MODAL
if (!empty($viewDownloadButtons) and $viewShowModal) {
    require_once(__DIR__ . '/modal.template.php');
}

// FOOTER
include(PRIVPATH . 'inc/footer.inc.php');