<?php

//DEBUG
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

require_once('install.conf.php');
require_once(PRIVPATH . 'inc/autoloader.php');
require_once(PRIVPATH . 'inc/init.inc.php');
require_once(PRIVPATH . 'inc/common_basic.inc.php');

/* how do we prevent that this is used to get access to all files on "/"
    1. have all files that need to be accessible in a specific directory, set in app config
        --> at least restricts access to the files stored in this directory
        --> but does not prevent any user/guest to access all files!
    2. store file details in session; only those files can be accessed by the active user and as long as the session is active
        --> use random codes in session to hide the exact location (which is stored in the session)

   This way we delegate user access restriction to the main app, and we don't have to check this here. What do we need to do here?
    1. check if there is an active session
    2. check if this session is not expired
    3. check if the requested code exists in the session
    4. check if the file (that corresponds to this code) exist and is readable
    5. serve the file
*/


if (isset($_REQUEST["code"])) {
    $code = $_REQUEST["code"];
} else {
    $code = ""; //if no downloadcode is supplied, "" will serve as an invalid code, leading to a 400 error
}

\Core\Service\DownloadCode::download($code, true);

die();

