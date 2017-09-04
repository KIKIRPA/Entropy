<?php

// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

require_once(PRIVPATH . 'inc/common_basic.inc.php');

/***********************************************************************************
  1. READ CONFIGURATION FILES
    - config.json     --> $CONFIG (must be first)
    - blacklist.json  --> $BLACKLIST
    - users.json      --> $USERS
    - modules.json    --> $MODULES
    - libraries.json  --> $LIBS
    - datatypes.json  --> $DATATYPES
  ***********************************************************************************/

$BLACKLIST = readJSONfile(BLACKLIST_FILE, false);
$USERS     = readJSONfile(USERS_FILE);

$MODULES   = readJSONfile(MODULES_FILE, true);
$LIBS      = readJSONfile(LIB_FILE, true);
$DATATYPES = readJSONfile(DATATYPES_FILE, true);


/***********************************************************************************
  2. SESSION MANAGEMENT
    - constant IS_HTTPS
    - constant IS_BLACKLISTED
    - constant BLACKLIST_COUNT
    - SESSION management: start, renew or expire
    - variable $is_logged_in (username or false) and $is_expired (true or false)
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
define("IS_BLACKLISTED", ($temp >= MAXTRIES_IP));

// SESSION MANAGEMENT

$is_logged_in = $is_expired = false;

if (IS_HTTPS and !IS_BLACKLISTED) {
    session_start();
  
    if (isset($_SESSION['username']) and $_SESSION['trusted'] and $_SESSION['pwdok']) {
        $is_logged_in = $_SESSION['username'];
    
        //set $user
        if (isset($USERS[$is_logged_in])) {
            $user = $USERS[$is_logged_in];
        } else { //set $user and $is_logged_in to false and log
            $user = $is_logged_in = eventLog("WARNING", "Non-existant username stored in session: " . $is_logged_in, false, true);
        }
    }
  
    // set or renew session
    if (!isset($_SESSION['ts'])) {      // set timestamp to auto-close sessions after a certain time
        $_SESSION['ts'] = time();
    } elseif (time() - $_SESSION['ts'] < EXPIRE) {  //last activity is less than $expire ago: stay logged in
        session_regenerate_id(true);    // change session ID for the current session and invalidate old session ID (protects against session fixation attack)
        $_SESSION['ts'] = time();       // update timestamp
    } else {                              // auto log off
        if ($is_logged_in) {
            $is_logged_in = false;
            $is_expired = true;
        }
        logout();
    }
}
