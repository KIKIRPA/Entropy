<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}


/* 
    requires $showLib
             $LIBS
             $htmlHeaderStyles
             $htmlHeaderScripts

    creates  $listTitle
             $listText
             $listNews (array)
             $listNewsColor
             $listContact
             $listReferences (array)

    TODO ERROR NOTIFICATIONS
*/


/*
    1. CONDITIONS
    --> fallback: ?
*/

// TODO load measurements.json


/*
    2. CODE
*/

// textbox (title + descriptive text of the library)
$listText = False;
if (isset($LIBS[$showLib]["text"])) {
    if (!empty($LIBS[$showLib]["text"])) {
        $listTitle = ($showLib == "_START") ? APP_NAME : $LIBS[$showLib]["name"];
        $listText  = $LIBS[$showLib]["text"];
    }
}

// newsboxes
$listNews = array();
$listNewsColor = "";
if (isset($LIBS[$showLib]["news"])) {
    foreach ($LIBS[$showLib]["news"] as $item) {
        if(!empty($item)) {
            array_push($listNews, $item);
        }
    }
}
if (isset($LIBS[$showLib]["color"])) {
    $listNewsColor = bulmaColorModifier($LIBS[$showLib]["color"], $COLORS, DEFAULT_COLOR);
}

// contacts
$listContact = "";
if (isset($LIBS[$showLib]["contact"])) {
    if (!empty($LIBS[$showLib]["contact"])) {
        $listContact =  $isLoggedIn ? $LIBS[$showLib]["contact"] : searchMailHide($LIBS[$showLib]["contact"]);
    }
}

// references
$listReferences = array();
if (isset($LIBS[$showLib]["references"])) {
    foreach ($LIBS[$showLib]["references"] as $item) {
        if(!empty($item)) {
            array_push($listReferences, $item);
        }
    }
}

// measurement list
if ($showLib != "_START") { //normal library
    // the columns to show can be defined in libraries.json, otherwise take defaults
    $listColumns = array();
    if (!empty($LIBS[$showLib]["columns"])) {
        $temp = $LIBS[$showLib]["columns"];
    } else {  
        $temp = array("id", "type"); // columns that are by definition available in measurements.json
    }
    // column names to be displayed
    foreach ($temp as $item) {
        $listColumns[$item] = nameMeta($item);
    }
    unset($temp, $item);
    // NOTE: as long as the data is stored databaseless, the data that will be filled in practice
    // will be limited to those that are stored in the measurements.json file

    // prepare the measurement data in an easy to process grid
    $listData = array();
    foreach ($measurements as $id => $measurement) {
        foreach ($listColumns as $col => $caption) {
            if (strtolower($col) == "id") {
                $listData[$id][$col] = $id;
            } elseif (isset($measurement[$col])) {
                $listData[$id][$col] = $measurement[$col];
            }
            else {  // this column is not set in measurements (for this measurement):
                $listData[$id][$col] = "";
            }
        }
    }
    unset($measurements, $id, $measurement, $col);

} else {    // startpage
    $listLibs = array();
    $row = 0;
    foreach ($LIBS as $id => $lib) {
        if ($id != "_START") {
            if ((strtolower($lib["view"]) == "public") or calcPermLib($user["permissions"], "view", $id)) {
                $listLibs[$row][$id] = array();
                $listLibs[$row][$id]["name"] = $lib["name"];
                $listLibs[$row][$id]["color"] = isset($lib["color"]) ? bulmaColorModifier($lib["color"], $COLORS, DEFAULT_COLOR) : bulmaColorModifier(DEFAULT_COLOR, $COLORS);
                $listLibs[$row][$id]["catchphrase"] = isset($lib["catchphrase"]) ? $lib["catchphrase"] : false;
                $listLibs[$row][$id]["logobox"] = isset($lib["logobox"]) ? $lib["logobox"] : false;
                if (count($listLibs[$row]) == 3) {
                    $row++;
                }
            }
        }
    }
    unset($row, $id, $lib);
}



/*
    3. HTML
*/

// HEADER + NAVBAR
array_push($htmlHeaderStyles, CSS_DT_BULMA);
array_push($htmlHeaderScripts, JS_DT, JS_DT_BULMA);            
include(HEADER_FILE);

// MAIN
if ($error) {
    echo $error . "<br><br>\n";
}
require_once(__DIR__ . '/template.php');

// FOOTER
include(FOOTER_FILE);
  