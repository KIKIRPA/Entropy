<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}


/* ***************
    main metadata
    *************** */

// data from the measurement list json file
// --> transaction
$transaction = $measurementListItem["_transaction"];

// --> tags
$tags = array("id", "type");
if (isset($LIBS[$_REQUEST["lib"]]["listcolumns"])) {
    if (!emtpy($LIBS[$_REQUEST["lib"]]["listcolumns"])) {
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
            if (len($value) > 30) {
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


// $viewDownloadEnabled boolean (show download box?) and $viewDownloadButtons (array of buttons to show)
$viewDownloadEnabled = false;
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
    
    // datalink or data
    if (isset($measurement["datalink"])) {
        // datalink is not supplied directly but through redirect in download module (ensures download popup, logging and hides direct link)
        $caption = "Download";
        $format = encode("link=" . $measurement["datalink"]);

        if ($viewShowModal) {
            $viewDownloadButtons[$caption] = "class=\"button " . $viewColor . " modal-button\" data-target=\"dlmodal\" onclick=\"document.getElementById('dl').value = '" . $format . "';\"";
        } else {
            $viewDownloadButtons[$caption] = "class=\"button " . $viewColor . "\" href=\"" . $_SERVER["PHP_SELF"] . "?lib=". $showLib . "&id=" . $showID . "&ds=" . $showDS . "&dl=" . $format . "\"";
        }
    } else {
        // convert from json data files
        foreach ($LIBS[$showLib]["downloadconverted"] as $format) {
            // check if this format "[convertor:[datatype:]]extension" is allowed for this datatype (returns array if found, false if not found)
            $datatype = findDataType($measurement["type"], $DATATYPES);
            $result = selectConvertorClass($EXPORT, $datatype, $format);
            if ($result) {
                $temp = explode(":", $format, 3);
                $caption = strtoupper($result["convertor"]) . " (." . strtolower(end($temp)) . ")";
                $format = encode("conv=" . $format);

                if ($viewShowModal) {
                    $viewDownloadButtons[$caption] = "class=\"button " . $viewColor . " modal-button\" data-target=\"dlmodal\" onclick=\"document.getElementById('dl').value = '" . $format . "';\"";
                } else {
                    $viewDownloadButtons[$caption] = "class=\"button " . $viewColor . "\" href=\"" . $_SERVER["PHP_SELF"] . "?lib=". $showLib . "&id=" . $showID . "&ds=" . $showDS . "&dl=" . $format . "\"";
                }
            }
        }
    }

    // binary uploaded files
    $prefix = \Core\Config\App::get("libraries_path") . $showLib . "/" . $transaction . "/" . $showID . (($showDS == 'default')?"":"__".$showDS);
    $binfiles = glob($prefix . "__*");  //by using "__*" we exclude the original (converted) data files, json data files and annotations
    foreach ($binfiles as $file) {
        // button caption = EXT (ORIG BIN FILENAME)
        $caption = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (    (in_array($caption, $LIBS[$showLib]["downloadbinary"]) or in_array("_ALL", $LIBS[$showLib]["downloadbinary"]))
            and !in_array("_NONE", $LIBS[$showLib]["downloadbinary"])) {
                $tooltip = pathinfo(str_replace($prefix . "__", '', $file), PATHINFO_FILENAME) . "." . $caption;
                $caption = "OTHER (." . $caption . ")";
                $format = encode("bin=" . $file);

                if ($viewShowModal) {
                    $viewDownloadButtons[$caption] = "class=\"button " . $viewColor . " is-outlined modal-button\" data-target=\"dlmodal\" onclick=\"document.getElementById('dl').value = '" . $format . "';\" title=\"" . $tooltip . "\"";
                } else {
                    $viewDownloadButtons[$caption] = "class=\"button " . $viewColor . " is-outlined\" href=\"" . $_SERVER["PHP_SELF"] . "?lib=". $showLib . "&id=" . $showID . "&ds=" . $showDS . "&dl=" . $format . "\" title=\"" . $tooltip . "\"";
                }
        }
    }

    if (count($viewDownloadButtons) > 0) {
        $viewDownloadEnabled = true;
    }
}

unset ($cookie, $code, $format, $prefix, $binfiles);


/* ********
  license
  ******** */

$viewLicense = false;

// search license in data file, library or system settings
if (isset($measurement["license"])) {
    $viewLicense = $measurement["license"];
} elseif (isset($LIBS[$showLib]["license"])) {
    $viewLicense = $LIBS[$showLib]["license"];
} elseif (!empty(\Core\Config\App::get("license_default"))) {
    $viewLicense = \Core\Config\App::get("license_default");
}

// if the license is a predefined one, replace it with the html version
if ($viewLicense) {
    $viewLicenseHtml = \Core\Config\Licenses::searchForNeedle($viewLicense, "html");
    if ($viewLicenseHtml) {
        $viewLicense = $viewLicenseHtml;
    }
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
        foreach ($item as $subkey => $subitem) {
            $subitem = getMeta($measurement, "meta:" . $key . ":" . $subkey, "; ", false);
            if (!$isLoggedIn) {
                $subitem = searchMailHide($subitem);
            }
            $subkey = nameMeta("meta:" . $key . ":" . $subkey);
            $viewMetadata[$row][$header][$subkey] = $subitem;
        }
    } else {
        if (!$isLoggedIn) {
            $item = searchMailHide($item);
        }
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
if ($viewDownloadEnabled and $viewShowModal) {
    require_once(__DIR__ . '/modal.template.php');
}

// FOOTER
include(PRIVPATH . 'inc/footer.inc.php');