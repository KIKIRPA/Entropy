<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

$startPage = true;  // let module_libedit know which data it should expect
require_once(PRIVPATH . 'modules/libedit/main.php');
?>

  
  
  
