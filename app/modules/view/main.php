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

// data from the measurement json file
$transaction = $measurement["_transaction"];
$viewTags = array();
foreach ($measurement as $key => $value) {
    // taglist: don't include keys starting with _ (e.g. _transaction) or having empty values
    if ((substr($key, 0, 1) != "_") and (!empty($value))) {
        $viewTags[nameMeta($key)] = $value;
    }
}

$viewColor = isset($LIBS[$showLib]["color"]) ? bulmaColorModifier($LIBS[$showLib]["color"], $COLORS, DEFAULT_COLOR) : bulmaColorModifier(DEFAULT_COLOR, $COLORS);

// metadata can be saved on different levels, override lesser priority metadata
$meta = overrideMeta($data, $showDS);

unset($measurement, $key, $value);


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
    if (LOG_DL) {
        if ($isLoggedIn) {
            $viewShowModal = false;
        } elseif (isset($_COOKIE[COOKIE_NAME])) {
            $cookie = verifycookie($_COOKIE[COOKIE_NAME]); // in case of false data, this will output False
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
    // convert from json data files
    foreach ($LIBS[$showLib]["downloadconverted"] as $format) {
        // check if this format "[convertor:[datatype:]]extension" is allowed for this datatype (returns array if found, false if not found)
        $result = selectConvertorClass($EXPORT, $viewTags["Type"], $format);
        if ($result) {
            $caption = strtoupper($result["convertor"]) . " (" . strtolower(end(explode(":", $format, 3))) . ")";
            $format = encode("conv=" . $format);

            if ($viewShowModal) {
                $viewDownloadButtons[$caption] = "class=\"button <?= $viewColor ?> modal-button\" data-target=\"dlmodal\" onclick=\"document.getElementById('dl').value = '" . $format . "';\"";
            } else {
                $viewDownloadButtons[$caption] = "class=\"button <?= $viewColor ?>\" href=\"" . $_SERVER["PHP_SELF"] . "?lib=". $showLib . "&id=" . $showID . "&ds=" . $showDS . "&dl=" . $format . "\"";
            }
        }
    }

    // binary uploaded files
    $prefix = LIB_PATH . $showLib . "/" . $transaction . "/" . $showID . (($showDS == 'default')?"":"__".$showDS);
    $binfiles = glob($prefix . "__*");  //by using "__*" we exclude the original (converted) data files, json data files and annotations
    foreach ($binfiles as $file) {
        // button caption = EXT (ORIG BIN FILENAME)
        $caption = strtoupper(pathinfo($file, PATHINFO_EXTENSION));
        if (    (in_array($format, $LIBS[$showLib]["downloadbinary"]) or in_array("_ALL", $LIBS[$showLib]["downloadbinary"]))
            and !in_array("_NONE", $LIBS[$showLib]["downloadbinary"])) {
                $caption .= "(" . str_replace("_", " ", pathinfo(str_replace($prefix, '', $file), PATHINFO_FILENAME)) . ")";
                // <a> tag
                if ($viewShowModal) {
                    $viewDownloadButtons[$caption] = "class=\"button <?= $viewColor ?> is-inverted modal-button\" data-target=\"dlmodal\" onclick=\"document.getElementById('dl').value = '" . encode("bin=" . $file) . "';\"";
                } else {
                    $viewDownloadButtons[$caption] = "class=\"button <?= $viewColor ?> is-inverted\" href=\"" . $_SERVER["PHP_SELF"] . "?lib=". $showLib . "&id=" . $showID . "&ds=" . $showDS . "&dl=" . encode("bin=" . $file) . "\"";
                }
        }
    }

    if (count($viewDownloadButtons) > 0) {
        $viewDownloadEnabled = true;
    }
}

unset ($cookie, $code, $format, $prefix, $binfiles);



/* ********
  viewer
  ******** */

$parenttype = findDataType($viewTags["Type"], $DATATYPES);
$viewer = $DATATYPES[$parenttype]["viewer"];
$units = findDataTypeUnits($parenttype, $DATATYPES, 'html', $data["dataset"][$showDS]["units"]);

if (isset($data["dataset"][$showDS]["anno"])) {
    if (is_array($data["dataset"][$showDS]["anno"])) {
        $anno = $data["dataset"][$showDS]["anno"];
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
$metaNoShow = array("type", "jcampdxtemplate");
$i = 0;
foreach ($meta as $key => $item) {
    if (!in_array($key, $metaNoShow)) {
        $row = intdiv($i, 3);   // display 3 categories per row
        $header = nameMeta($key);
        if (is_array($item)) {
            foreach ($item as $subkey => $subitem) {
                $subitem = getMeta($meta, $key . ":" . $subkey, "; ", false);
                if (!$isLoggedIn) {
                    $subitem = searchMailHide($subitem);
                }
                $subkey = nameMeta($key . ":" . $subkey);
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
}

unset($meta, $i, $row, $header, $key, $item, $subkey, $subitem);


/* ******
    HTML
   ****** */

// HEADER + NAVBAR
array_push($htmlHeaderStyles, CSS_DYGRAPH);
array_push($htmlHeaderScripts, JS_DYGRAPH);  
include(HEADER_FILE);

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
include(FOOTER_FILE);