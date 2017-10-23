<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

$libmk = true;

include(PRIVPATH . 'inc/module_libedit.inc.php');

if (($f == "set") and ($output == false)) {
    //----------------------------------------------//
    // give the creator access rights in users.json //
    //----------------------------------------------//

    if (isset($USERS[$isLoggedIn]) and isset($USERS[$isLoggedIn]["permissions"])) {
        foreach ($MODULES["lib"] as $mod => $value) {
            if (isset($USERS[$isLoggedIn]["permissions"][$mod])
            and !in_array("_ALL", $USERS[$isLoggedIn]["permissions"][$mod])
            and !in_array("_NONE", $USERS[$isLoggedIn]["permissions"][$mod])
            and !in_array($id, $USERS[$isLoggedIn]["permissions"][$mod])
        ) {
                array_push($USERS[$isLoggedIn]["permissions"][$mod], $id);
            } else {
                $USERS[$isLoggedIn]["permissions"][$mod] = array($id);
            }
        }
    
        $error = writeJSONfile(USERS_FILE, $USERS);

        if ($error) {
            echo "<span style='color:red'>ERROR: " . $error . "!</span><br><br>\n";
            eventLog("ERROR", "Could not make library: " .$error . " [module_libmk]", false, false);
        }
    } else {
        echo "<span style='color:red'>ERROR: user permission error.</span><br><br>\n";
        eventLog("ERROR", "User permission error. [module_libmk]", false, false);
    }

    // create directory?
    if (!mkdir2(LIB_PATH . $id . "/")) {
        echo "<span style='color:red'>ERROR: could not create directory.</span><br><br>\n";
        eventLog("ERROR", "Could not create directory. [module_libmk]", false, true);
    }
}