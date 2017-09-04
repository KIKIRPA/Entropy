<?php
require_once('install.conf.php');
require_once(PRIVPATH . 'inc/entropy.conf.php');
require_once(PRIVPATH . 'inc/init.inc.php');
require_once(PRIVPATH . 'inc/common_basic.inc.php');
require_once(PRIVPATH . 'inc/common_mailhide.inc.php');
require_once(PRIVPATH . 'inc/common_writefile.inc.php');


// security measures!

if (!IS_HTTPS) {
    $module = "empty";
    $msg = "A https connection is required for this page.";
}

if (IS_BLACKLISTED) {
    $module = "empty";
    $msg = "This IP has been blacklisted due to too many failed login attempts. Please contact the system administrator.";
}

if ($is_logged_in) {
    if (isset($_REQUEST["mod"])) {
        if (isset($MODULES["adm"][$_REQUEST["mod"]])) {
            if (calcPermLib($user["permissions"], $_REQUEST["mod"])) {
                $error = false;
            } else {
                $error = "User " . $is_logged_in . " is not authorised to use module " . $_REQUEST["mod"] . "!";
            }
        } elseif (isset($MODULES["lib"][$_REQUEST["mod"]])) {
            if (isset($_REQUEST["lib"])) {
                if (calcPermLib($user["permissions"], $_REQUEST["mod"], $_REQUEST["lib"])) {
                    $error = false;
                } else {
                    $error = "User " . $is_logged_in . " is not authorised to use module " . $_REQUEST["mod"] . " for library " . $_REQUEST["lib"] . "!";
                }
            } else {
                $error = "Could not process your request: module " . $_REQUEST["mod"] . " requires a library to run!";
            }
        } else {
            $error = "Could not process your request: module " . $_REQUEST["mod"] . " was not found!";
        }
    } else {
        $error = "Could not process your request: supply a module to run!";
    }
} else {
    $error = "Log in to view this page!";
}


//HEADER
$LIBS = json_decode(file_get_contents(LIB_FILE), true);
$htmltitle = APP_SHORT . ": administration";
$htmlkeywords = APP_KEYWORDS;
$pagetitle = APP_LONG;
$pagesubtitle = "Library tools";
$style   = "    <link rel='stylesheet' type='text/css' href='" . CSS_DT_BULMA . "'>\n";
$scripts = "    <script type='text/javascript' src='" . JS_DT . "' async></script>\n"
        . "    <script type='text/javascript' src='" . JS_DT_BULMA . "' async></script>\n";

include(HEADER_FILE);
if (!$error) {
    include(PRIVPATH . 'inc/module_' . $_REQUEST["mod"] . '.inc.php');
} else {
    echo $error;
}
include(FOOTER_FILE);
