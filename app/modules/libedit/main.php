<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}


$notifications = array();
$libeditColor = isset($LIBS[$showLib]["color"]) ? bulmaColorModifier($LIBS[$showLib]["color"], $COLORS, DEFAULT_COLOR) : bulmaColorModifier(DEFAULT_COLOR, $COLORS);


/* *********************************************************
    FUNCTION CHOOSER
   ********************************************************* */
  
if (!isset($libmk)) {
    $libmk = false;
}  // in module_libmk.inc.php this is set to true!
if (!isset($startPage)) {
    $startPage = false;
}   // in module_START this is set to true

$f = "error";

// conditions based on POST/GET values
if (isset($_REQUEST["set"])) {
    if (    !empty($_REQUEST["lib"])
        and !empty($_REQUEST["view"])
        and ($startPage or !empty(trim($_REQUEST["name"])))
        and ($startPage or !empty(trim($_REQUEST["navmenucaption"]))) 
       ) {
        $f="set";
    } else {
        $f="warning";
        $notifications[] = array("warning", "Some data is missing: (requires a unique identifier" . ($startPage ? "" : ", name, navigation menu caption") . " and viewabililty setting)");
        echo "lib " . $_REQUEST["lib"] . "<br>";
        echo "view " . $_REQUEST["view"] . "<br>";
    }
} else {
    $f="edit";
}

// conditions implied by the three modes (libedit, libmk, startpage)
if ($startPage) {
    $libeditId = "_START";
    $libeditTitle = "Modify start page";
} elseif ($libmk) {
    $libeditId = "";
    $libeditTitle = "Create library";
    
    // when setting up a library, check if it does not already exist
    if (isset($_REQUEST["lib"]) and isset($_REQUEST["set"])) {
        if (isset($LIBS[strtolower($_REQUEST["lib"])])) {
            $f="warning";
            $notifications[] = array("warning", "Library \"" . strtolower($_REQUEST["lib"]) . "\" already exists!");
        } else {
            $libeditId = strtolower($_REQUEST["lib"]);
        }
    }
} else {
    $libeditId = str_replace(" ", "", strtolower($_REQUEST["lib"]));
    $libeditTitle = "Edit library";

    if (!isset($LIBS[$libeditId])) {  // if not in libs.json
        // if libedit is invoked (not libmk) on a non-existing lib, we won't make one
        // because this could trick the permissions for libmk
        $f="error";
        $notifications[] = array("danger", "Library " . $libeditId . " does not exist.");
    }
}


/* *********************************************************
1. SET: add/update library action code
********************************************************* */

if ($f == "set") {
    // make array and fill it with (corrected) $_REQUEST parameters in logical order (1=strings, 2=colors, 3=arrays, 4=arrays that need sanitizeStr) 
    $newLib = array();
    $params = array( "name" => 1,
                     "navmenucaption" => 1,
                     "view" => 1,
                     "color" => 2,
                     "logobox" => 1,
                     "catchphrase" => 1,
                     "text" => 1,
                     "contact" => 1,
                     "news" => 3,
                     "references" => 3,
                     "listcolumns" => 4,
                     "downloadconverted" => 5,
                     "downloadbinary" => 5
                   );

    foreach ($params as $item => $category) {
        if (isset($_REQUEST[$item])) {
            switch ($category) {
                case 1: // strings
                    $value = trim($_REQUEST[$item]);
                    if (!empty($value)) {
                        $newLib[$item] = $value;
                    }
                    break;

                case 2: // colors
                    $value = bulmaColorInt(trim($_REQUEST[$item]), $COLORS, DEFAULT_COLOR);
                    if (isset($value)) {
                        $newLib[$item] = $value;
                    }
                    break;

                case 3: // arrays
                    if (is_array($_REQUEST[$item])) {
                        foreach ($_REQUEST[$item] as $i => $value) {
                            $value = trim($value);
                            if (!empty($value)) {
                                $newLib[$item][$i] = $value;
                            }
                        }
                    }
                    break;
                case 4: // arrays that need sanitizeStr, lowercase
                case 5: // arrays that need sanitizeStr, uppercase
                    if (!is_array($_REQUEST[$item])) {
                        $_REQUEST[$item] = explode("|", $_REQUEST[$item]);
                    }
                    foreach ($_REQUEST[$item] as $i => $value) {
                        //$value = str_replace(" ", "_", $value);
                        $value = sanitizeStr($value, "_", false, $category - 3);
                        if (!empty($value)) {
                            $newLib[$item][$i] = $value;
                        }
                    }
                    break;
            }
        }
    }

    //prepare file contents
    $LIBS[$libeditId] = $newLib;

    //and write file
    $output = writeJSONfile(LIB_FILE, $LIBS);

    if ($output == false) {
        $notifications[] = array("success", "Successfully created or updated library.");
    } else {
        $f = "warning";
        $notifications[] = array("danger", "Could not save the changes: " . $output . "!");
    }
}

/* *********************************************************
2. EDIT and SET and WARNING: library detail view; adding/updating libraries
********************************************************* */

if (($f == "edit") or ($f == "set") or ($f == "warning")) {
    $preset = array( "lib" => "",
                     "name" => "",
                     "navmenucaption" => "",
                     "view" => "",
                     "color" => "",
                     "logobox" => "",
                     "catchphrase" => "",
                     "text" => "",
                     "contact" => "",
                     "news" => array(),
                     "references" => array(),
                     "listcolumns" => array(),
                     "downloadconverted" => array(),
                     "downloadbinary" => array()
    );

    // load preset data from unsaved $_REQUEST[] in case of $f==warning
    // load preset data from existing data in $libs (in case of libedit or startpage)
    if ($f == "warning") {
        foreach ($preset as $key => $value) {
            if (isset($_REQUEST[$key])) {
                $preset[$key] = $_REQUEST[$key];
            }
        }
    }
    elseif (!$libmk) {
        foreach ($LIBS[$libeditId] as $i => $item) {
            $preset[$i] = $item;
        }
    }
}


/* *********************************************************
3. ERROR if some data are missing
********************************************************* */

if ($f == "error") {
    if (empty($notifications)) {      
        $notifications[] = array("danger", "Unknown error!");
    }

    echo $notifications[0][1];
    die();
    //TODO: fallback module (for)
    //TODO: errorlog
}

// HEADER + NAVBAR
array_push($htmlHeaderStyles, CSS_DT_BULMA);
array_push($htmlHeaderScripts, JS_DT, JS_DT_BULMA);  
include(HEADER_FILE);

// MAIN
require_once(__DIR__ . '/template.php');

//FOOTER
include(FOOTER_FILE);