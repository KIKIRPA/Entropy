<?php

// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

require_once(PRIVPATH . 'inc/common_basic.inc.php');

if (\Core\Config\App::get("debug_display_errors")) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

/***********************************************************************************
  1. READ CONFIGURATION FILES
    - config.json     --> $CONFIG (must be first)
    - blacklist.json  --> $BLACKLIST
    - users.json      --> $USERS
    - modules.json    --> $MODULES
    - libraries.json  --> $LIBS
    - datatypes.json  --> $DATATYPES
  ***********************************************************************************/

$BLACKLIST = readJSONfile(\Core\Config\App::get("config_blacklist_file"));
$USERS     = readJSONfile(\Core\Config\App::get("config_users_file"));

$MODULES   = readJSONfile(\Core\Config\App::get("config_modules_file"), true);
$LIBS      = readJSONfile(\Core\Config\App::get("config_libraries_file"), true);
$DATATYPES = readJSONfile(\Core\Config\App::get("config_datatypes_file"), true);
$COLORS    = readJSONfile(\Core\Config\App::get("config_colors_file"), true);

$IMPORT    = readJSONfile(\Core\Config\App::get("config_import_file"), true);
$EXPORT    = readJSONfile(\Core\Config\App::get("config_export_file"), true);


/***********************************************************************************
  2. SESSION MANAGEMENT
    - constant IS_HTTPS
    - constant IS_BLACKLISTED
    - constant BLACKLIST_COUNT
    - SESSION management: start, renew or expire
    - variable $isLoggedIn (username or false) and $isExpired (true or false)
    - variables $BLACKLIST, $user
  ***********************************************************************************/
  


// IS_HTTPS

define("IS_HTTPS", (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443);
                        
// IS_BLACKLISTED

$temp = 0;

if (isset($BLACKLIST[$_SERVER['REMOTE_ADDR']])) {
    $temp = count($BLACKLIST[$_SERVER['REMOTE_ADDR']]);
}

define("BLACKLIST_COUNT", $temp);
define("IS_BLACKLISTED", ($temp >= \Core\Config\App::get("login_ip_attempts")));

// SESSION MANAGEMENT
// open session; in case of the (storage) API, close it instantaneously  (many concurrent connections destroy the session!!!)
if (isset($_REQUEST["do"])) session_start(['read_and_close' => true]);
else session_start();

$isLoggedIn = $isExpired = false;

if (isset($_SESSION['username']) and $_SESSION['trusted'] and $_SESSION['pwdok']) {
    $isLoggedIn = $_SESSION['username'];

    //set $user
    if (isset($USERS[$isLoggedIn])) {
        $user = $USERS[$isLoggedIn];
    } else { //set $user and $isLoggedIn to false and log
        $user = $isLoggedIn = false;
        eventLog("WARNING", "Non-existant username stored in session: " . $isLoggedIn, false, true);
    }
}

// set or renew session
if (!isset($_SESSION['ts'])) {      // set timestamp to auto-close sessions after a certain time
    $_SESSION['ts'] = time();
} elseif (time() - $_SESSION['ts'] < \Core\Config\App::get("login_session_expire")) {  //last activity is less than $expire ago: stay logged in
    session_regenerate_id(true);    // change session ID for the current session and invalidate old session ID (protects against session fixation attack)
    $_SESSION['ts'] = time();       // update timestamp
} else {                              // auto log off
    if ($isLoggedIn) {
        $isLoggedIn = false;
        $isExpired = true;
    }
    logout();
}


/***********************************************************************************
  3. HTML header things
    - variable (array) $htmlHeaderStyles
    - variable (array) $htmlHeaderScripts
  ***********************************************************************************/

$htmlHeaderStyles = Array(
    \Core\Config\App::get("css_fa"),
    \Core\Config\App::get("css_bulma"),
    "./css/jquery.notifyBar.css"
);

$htmlHeaderScripts = Array(
    \Core\Config\App::get("js_jquery"),
    "./js/jquery.notifyBar.js",
    "./js/main.js"
);
