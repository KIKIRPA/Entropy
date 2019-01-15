<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}



if(isset($_REQUEST['do'])) {

    /* ******
        JSON
       ****** */

    require_once(__DIR__ . '/filemanager.php');

} else {

    /* ******
        HTML
       ****** */
    
    // HEADER + NAVBAR
    array_push($htmlHeaderStyles, "./css/storage.css"); 
     
    include(PRIVPATH . 'inc/header.inc.php');
    
    // MAIN
    require_once(__DIR__ . '/filemanager.php');
    
    // FOOTER
    include(PRIVPATH . 'inc/footer.inc.php');
}

