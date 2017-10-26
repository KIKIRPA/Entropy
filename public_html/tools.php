<?php
require_once('install.conf.php');
require_once(PRIVPATH . 'entropy.conf.php');
require_once(PRIVPATH . 'inc/init.inc.php');
require_once(PRIVPATH . 'inc/common_basic.inc.php');
require_once(PRIVPATH . 'inc/common_mailhide.inc.php');
require_once(PRIVPATH . 'inc/common_writefile.inc.php');


// security measures!

if (!IS_HTTPS) {
    $showMod = "empty";
    $msg = "A https connection is required for this page.";
}

if (IS_BLACKLISTED) {
    $showMod = "empty";
    $msg = "This IP has been blacklisted due to too many failed login attempts. Please contact the system administrator.";
}

if ($isLoggedIn) {
    if (isset($_REQUEST["mod"])) {
        if (isset($MODULES["adm"][$_REQUEST["mod"]])) {
            if (calcPermLib($user["permissions"], $_REQUEST["mod"])) {
                $error = false;
            } else {
                $error = "User " . $isLoggedIn . " is not authorised to use module " . $_REQUEST["mod"] . "!";
            }
        } elseif (isset($MODULES["lib"][$_REQUEST["mod"]])) {
            if (isset($_REQUEST["lib"])) {
                if (isset($LIBS[$_REQUEST["lib"]])) {
                    $showLib = $_REQUEST["lib"];
                    if (calcPermLib($user["permissions"], $_REQUEST["mod"], $showLib)) {
                        $error = false;
                    } else {
                        $error = "User " . $isLoggedIn . " is not authorised to use module " . $_REQUEST["mod"] . " for library " . $_REQUEST["lib"] . "!";
                    }
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



$LIBS = json_decode(file_get_contents(LIB_FILE), true);

$showMod = $_REQUEST["mod"];

if (!$error) {
    require_once(PRIVPATH . 'modules/' . $showMod . '/main.php');
} else {
    echo $error;
}

