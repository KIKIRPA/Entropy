<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

//HEADER
array_push($htmlHeaderStyles, \Core\Config\App::get("css_dt_bulma"));
array_push($htmlHeaderScripts, \Core\Config\App::get("js_dt"), \Core\Config\App::get("js_dt_bulma"));  
include(PRIVPATH . 'inc/header.inc.php');

//MOTD
$motd = \Core\Config\App::get("console_motd_file");
if (file_exists($motd)) {
    include_once($motd);
}

//FOOTER
include(PRIVPATH . 'inc/footer.inc.php');
