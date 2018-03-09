<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

//HEADER
array_push($htmlHeaderStyles, CSS_DT_BULMA);
array_push($htmlHeaderScripts, JS_DT, JS_DT_BULMA);  
include(HEADER_FILE);

//MOTD
if (file_exists(MOTD_FILE)) {
    include_once(MOTD_FILE);
}

//FOOTER
include(FOOTER_FILE);
