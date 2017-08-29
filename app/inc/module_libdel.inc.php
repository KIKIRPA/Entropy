<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

$id = str_replace(" ", "", strtolower($_REQUEST["lib"]));

echo "      <h3>Delete library</h3>\n";

if ($id == "_landingpage") {
    // the special 'library' _landingpage cannot be deleted; should be set to invisible instead!
    echo "      <span style='color:red'>ERROR: cannot remove the landing page!</span><br><br>\n";
} elseif (!isset($_REQUEST["del"]) and isset($LIBS[$id])) {
    echo "        <span style='color:red'>This will IRREVOCABLY DELETE LIBRARY " . $id . "!</span><br><br>\n"
       . "        <form name='myform' action='" . $_SERVER["REQUEST_URI"] . "' method='POST'>\n"
       . "          <input type='hidden' name='del' value='true'>\n"
       . "          <input type='submit' value='Delete'>\t"
       . "          <a href='.index.php'>Take me out of here!</a>\n"
       . "        </form>\n";
} elseif (isset($_REQUEST["del"]) and isset($LIBS[$id])) {
    // UPDATE JSON LIBRARIES FILE
    unset($LIBS[$id]);
    $error = writeJSONfile(LIB_FILE, $LIBS);
    if (!$error) {
        echo "      <span style='color:red'>Deleted library " . $id . "!</span><br><br>\n";
    } else {
        echo "      <span style='color:red'>ERROR: " . $error . "!</span><br><br>\n";
    }

    // BACKUP DATA DIR
    if (!backupFile(LIB_PATH, $id)) {
        echo "      <span style='color:red'>ERROR: failed to remove the data directory!</span><br><br>\n";
    }
} else {
    echo "      <span style='color:red'>Wouldn't you rather choose an existing library to delete?</span><br><br>\n";
}