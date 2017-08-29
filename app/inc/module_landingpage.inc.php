<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

$lp = true;  // let module_libedit know which data it should expect
include(INC_PATH . 'module_libedit.inc.php');
?>

  
  
  
