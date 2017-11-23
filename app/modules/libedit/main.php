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

if (!$startPage) {
    $id = str_replace(" ", "", strtolower($_REQUEST["lib"]));
} else {
    $id = "_START";
}

$new = !isset($LIBS[$id]);           // if not in libs.json

if ($startPage) {
    $libeditTitle = "Modify start page";
} elseif ($new) {
    $libeditTitle = "Create library";
} else {
    $libeditTitle = "Edit library";
}

$f="error";

if (isset($_REQUEST["set"])) {
    if (    !empty($_REQUEST["lib"])
        and !empty($_REQUEST["view"])
        and ($startPage or !empty(trim($_REQUEST["name"])))
        and ($startPage or !empty(trim($_REQUEST["navmenucaption"]))) 
       ) {
        $f="set";
    } else {
        $f="edit";
        $notifications[] = array("danger", "Some data is missing: (requires id" . ($startPage ? "" : ", name, menu caption") . " and view)");
    }
} else {
    $f="edit";
}

// disallowed combinations of $new and $libmk
if (!$new and $libmk) {  // libmk: cannot make this lib, because the libID already exists!
    $notifications[] = array("danger", "Cannot make this library: a library with this library ID already exists!");
    $f="error";
} elseif ($new and !$libmk) {  // libedit: cannot make a new lib with libedit
    $notifications[] = array("danger", "Cannot edit this library: library ID not found!");
    $f="error";
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
    $LIBS[$id] = $newLib;

    //and write file
    $output = writeJSONfile(LIB_FILE, $LIBS);

    if ($output == false) {
        $notifications[] = array("success", "Successfully created or updated library.");
    } else {
        $notifications[] = array("danger", "Could not save the changes: " . $output . "!");
    }
}

/* *********************************************************
2. EDIT and SET: library detail view; adding/updating libraries
********************************************************* */

if (($f == "edit") or ($f == "set")) {
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

    if (!$new) {
        foreach ($LIBS[$id] as $i => $item) {
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