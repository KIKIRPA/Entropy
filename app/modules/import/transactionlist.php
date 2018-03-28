<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

$listTransactions = array();
$i = 0;

foreach ($transactions as $trId => $tr) {
    $row = intdiv($i, 3);
    $listTransactions[$row][$id] = array();
    $listLibs[$row][$id]["name"] = $trId;

    $i++;
}


// HTML
//array_push($htmlHeaderStyles, \Core\Config\App::get("css_dt_bulma"));
//array_push($htmlHeaderScripts, \Core\Config\App::get("js_dt"), \Core\Config\App::get("js_dt_bulma")); 

require_once(PRIVPATH . 'inc/header.inc.php');
require_once(__DIR__ . '/transactionlist.template.php');
require_once(PRIVPATH . 'inc/footer.inc.php');

die();