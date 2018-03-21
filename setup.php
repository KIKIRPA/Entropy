#!/usr/bin/env php

<?php

//----------------------//
//   DEFAULT CONFIG     //
//----------------------//

// default values
$defaults = array(
    "setup" => array(
        "forceinstall"          => false,
        "privpath"              => "/usr/local/share/entropy/",
        "pubpath"               => "/var/www/html/",
        "htgroup"               => "www-data"
    ),
    "app" => array(
        "app_name"              => "Entropy",
        "app_catchphrase"       => "A repository tailored for analytical data",
        "login_twopass_enable"  => false
    ),
    "users" => array(
        "login"                 => "admin",
        "name"                  => "Administrator user",
        "passwd"                => "entropy"
    )
);

$questions = array(
    "setup" => array(
        "forceinstall"          => "Force a clean install, even if a previous installation is found. This removes all data and settings.",
        "privpath"              => "Main installation path outside webroot (where files will be stored that should remain inaccessible from the web).",
        "pubpath"               => "Webroot path (accessible for the web server).",
        "htgroup"               => "Group name of the Web server"
    ),
    "app" => array(
        "app_name"              => "Short website name.",
        "app_catchphrase"       => "Longer website description.",
        "app_keywords"          => "Website keywords used to improve indexing by seach engines (eg google). Separate keywords with commas.",
        "mail_admin"            => "E-mail address of the system administrator.",
        "mailhide_key_pub"      => "Mailhide is a service that protects e-mail addresses on a website from spam. The addresses are obfuscated until a (human) visitor solves a reCAPTCHA. If you want to use this feature in Entropy, get your public and private API keys on https://www.google.com/recaptcha/mailhide/apikey. Fill in the public key here.",
        "mailhide_key_priv"     => "Mailhide private key",
        "login_twopass_enable"  => "Enable twopass verification for unknown IPs. Please make sure to have a working sendmail configuration."
    ),
    "users" => array(    
        "name"                  => "Full name for the administrator user.",
        "institution"           => "Institution or department of the the administrator user.",
        "email"                 => "E-mail address of the administrator user.",
        "login"                 => "Username (login name) for the administrator user.",
        "passwd"                => "Passphrase or password for the administrator user."
    )
);

// used for type evaluation and casting. possible values: boolean, integer, string (default), e-mail, path
$types = array(
    "forceinstall"              => "boolean",
    "privpath"                  => "path",
    "pubpath"                   => "path",
    "mail_admin"                => "e-mail",
    "login_twopass_enable"      => "boolean",
    "login_session_expire"      => "integer",
    "login_password_attempts"   => "integer",
    "login_ip_attempts"         => "integer",
    "downloads_log_enable"      => "boolean",
    "downloads_cookie_expire"   => "integer",
    "libraries_path"            => "path",
    "templates_path"            => "path",
    "email"                     => "e-mail"
);


//--------------------------//
//   COMMAND LINE OPTIONS   //
//--------------------------//

$short = "huidc:";
$long  = array("help", "update", "install", "defaults", "config:");
$options = getopt($short, $long);

echo "ENTROPY installation and update script\n";
if (isset($options["h"]) or isset($options["help"])) {
    echo "\nUsage: setup.php [options]\n"
    . "Options:\n"
    . "  -u       --update       Update Entropy. Preserves configuration files and data. (default)\n"
    . "  -i       --install      Force clean install. REMOVES/RESETS CONFIGURATION FILES AND DATA!\n"
    . "  -d       --defaults     Non-interactive mode; use the default values.\n"
    . "  -c FILE  --config FILE  Use configuration file (JSON format).\n"
    . "  -h       --help         Shows this help message.\n\n";
    exit(0);
}

// invalid option combinations
if ((isset($options["u"]) or isset($options["update"]))
    and (isset($options["i"]) or isset($options["install"]))) {
    echo "\nERROR: contradicting options: clean install vs update only. Aborting...\n";
    exit(1);
}

// config file
if (isset($options["c"])) {
    $cfgfile = $options["c"];
}
if (isset($options["config"])) {
    $cfgfile = $options["config"];
}

if (isset($cfgfile)) {
    // read file
    if (file_exists($cfgfile)) {
        $cfgfile = file_get_contents($cfgfile);
        $cfgfile = json_decode($cfgfile, true);
    } else {
        echo "\nERROR: configuration file not found. Aborting...\n";
        exit(3);
    }
    
    // valid json?
    if (empty($cfgfile)) {
        echo "\nERROR: invalid or empty configuration file. Aborting...\n";
        exit(4);
    }

    // update $defaults with the values in $cfgfile
    $defaults = array_replace_recursive($defaults, $cfgfile);
}

// evaluate $defaults values
foreach ($defaults as $subgroup) {
    foreach ($subgroup as $id => $value) {
        list($eval, $value) = cfgeval($id, $value);
    
        if ($eval) {
            $defaults[$id] = $value;
        } //corrected value
        else {
            echo "\nERROR: configuration item " . $id . " not a valid " . gettype2($id) . "\n";
            exit(5);
        }
    }
}

// override $defaults (and $cfgfile) when -i or -u are given, or ask if in interactive mode
if (isset($options["u"]) or isset($options["update"])) {
    $defaults["setup"]["forceinstall"] = false;
} elseif (isset($options["i"]) or isset($options["install"])) {
    $defaults["setup"]["forceinstall"] = true;
}

// interactive mode
if (!isset($options["d"]) and !isset($options["defaults"])) {
    foreach ($questions["setup"] as $id => $value) {
        // if update/install options specified on command line, do'nt ask again
        if (!($id === "forceinstall" and (isset($options["u"]) or isset($options["update"]) or isset($options["i"]) or isset($options["install"])))) {
            echo $value;
            echo "\nEnter value or accept default [" . (isset($defaults["setup"][$id]) ? $defaults["setup"][$id] : "") . "]: \n";
        
            while (1) {
                $response = trim(fgets(STDIN));
                if (!empty($response)) {
                    list($eval, $response) = cfgeval($id, trim($response));
                    if ($eval) {
                        $defaults["setup"][$id] = $response;
                        break;
                    } else {
                        echo "Invalid response; please answer with a " . gettype2($id) . ":\n";
                    }
                } else {
                    break;
                }
            }
        }
    }
}


//----------------------//
//   INSTALL / UPDATE   //
//----------------------//

$privpath = rtrim($defaults["setup"]["privpath"], "/") . "/"; // make sure that privpath ends with (a single) /
$pubpath = rtrim($defaults["setup"]["pubpath"], "/") . "/";

// determine if we do a clean install or an update
if (!file_exists($privpath . ".installed") or $defaults["setup"]["forceinstall"]) {
    $cleaninstall = true;
    echo "Clean installation...\n\n";
} else {
    $cleaninstall = false;
    echo "Update...\n\n";
}

// get current username
$currentUser = posix_getpwuid(posix_geteuid());
$currentUser = $currentUser['name'];

// common tasks for both clean install and update
echo "Copying files.\n";
if (!file_exists($privpath)) {
    mkdir($privpath, 0750, true);
    chgrp($privpath, $defaults["setup"]["htgroup"]);
}
if (!file_exists($pubpath)) {
    mkdir($pubpath, 0750, true);
    chgrp($pubpath, $defaults["setup"]["htgroup"]);
}

// recursively copy all subdirectories in ./app and ./public_html
foreach (scandir2("./app/") as $f) {
    if ($cleaninstall) rrmdir($privpath . $f);
}
rcopy("./app", $privpath, $defaults["setup"]["htgroup"]);
foreach (scandir2("./public_html/") as $f) {
    if ($cleaninstall) rrmdir($pubpath . $f);
}
rcopy("./public_html", $pubpath, $defaults["setup"]["htgroup"]);

// clean install only: data and config (writable for htgroup)
if ($cleaninstall) {
    rrmdir($privpath . "data/");
    rcopy("./data/", $privpath . "data/", $defaults["setup"]["htgroup"], true);

    rrmdir($privpath . "config/");
    rcopy("./config/", $privpath . "config/", $defaults["setup"]["htgroup"], true);
}


//----------------------//
//   CONFIG             //
//----------------------//

// make configuration files clean install only
if ($cleaninstall) {

    echo "\nBUILD CONFIGURATION FILE: install.conf.php\n";
    $install = '<?php' . "\n"
             . '// prevent direct access to this file (thus only when included)' . "\n"
             . 'if (count(get_included_files()) == 1) {' . "\n"
             . '    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");' . "\n"
             . '    header("Status: 404 Not Found");' . "\n"
             . '    exit("Direct access not permitted.");' . "\n"
             . '}' . "\n\n"
             . '// installation path' . "\n"
             . 'const PRIVPATH   = "' . $privpath . "\";\n";
    file_put_contents($pubpath . "install.conf.php", $install);
    chmod($pubpath . "install.conf.php", 0640);
    chgrp($pubpath . "install.conf.php", $defaults["setup"]["htgroup"]);

    echo "\nBUILD CONFIGURATION FILE: app.json\n";
    // interactive mode: ask config file questions
    if (!isset($options["d"]) and !isset($options["defaults"])) {
        foreach ($questions["app"] as $id => $value) {
            echo $value;
            echo "\nEnter value or accept default [" . (isset($defaults["app"][$id]) ? $defaults["app"][$id] : "") . "]: \n";
            while (1) {
                $response = trim(fgets(STDIN));
                if (!empty($response)) {
                    list($eval, $response) = cfgeval($id, trim($response));
                    if ($eval) {
                        $defaults["app"][$id] = $response;
                        break;
                    } else {
                        echo "Invalid response; please answer with a " . gettype2($id) . ":\n";
                    }
                } else {
                    break;
                }
            }
        }
    }
    // make file
    $app = json_encode($defaults["app"], JSON_PRETTY_PRINT);
    file_put_contents($privpath . "config/app.json", $app);
    chmod($privpath . "config/app.json", 0660);
    chgrp($privpath . "config/app.json", $defaults["setup"]["htgroup"]);
    

    echo "\nBUILD CONFIGURATION FILE: users.json\n";
    // interactive mode: ask config file questions
    if (!isset($options["d"]) and !isset($options["defaults"])) {
        foreach ($questions["users"] as $id => $value) {
            echo $value;
            echo "\nEnter value or accept default [" . (isset($defaults["users"][$id]) ? $defaults["users"][$id] : "") . "]: \n";
            while (1) {
                $response = trim(fgets(STDIN));
                if (!empty($response)) {
                    list($eval, $response) = cfgeval($id, trim($response));
                    if ($eval) {
                        $defaults["users"][$id] = $response;
                        break;
                    } else {
                        echo "Invalid response; please answer with a " . gettype2($id) . ":\n";
                    }
                } else {
                    break;
                }
            }
        }
    }
    // make file
    $users = array($defaults["users"]["login"] => array("name"        => $defaults["users"]["name"],
                                                        "institution" => (isset($defaults["users"]["institution"])  ? $defaults["users"]["institution"]  : ""),
                                                        "email"       => (isset($defaults["users"]["email"]) ? $defaults["users"]["email"] : ""),
                                                        "hash"        => password_hash($defaults["users"]["passwd"], PASSWORD_DEFAULT),
                                                        "date"        => "",
                                                        "reset"       => ($defaults["users"]["passwd"] == "entropy" ? true : false),
                                                        "tries"       => array(),
                                                        "trusted"     => array(),
                                                        "permissions" => array("admin" => true),
                                                        "lastlogin"   => ""
                                                        )
                  );
    $users = json_encode($users, JSON_PRETTY_PRINT);
    file_put_contents($privpath . "config/users.json", $users);
    chmod($privpath . "config/users.json", 0660);
    chgrp($privpath . "config/users.json", $defaults["setup"]["htgroup"]);

    // leave trail
    file_put_contents($privpath . ".installed", "");
}

// update only: compare other config files and propose to leave/merge/replace them if changed (md5?)
else {
    echo "\nUPDATE CONFIGURATION FILES\n";
    // scan all config files in the downloaded source
    foreach (scandir2("./config/", true) as $filename) {
        $src = "./config/" . $filename;
        $dst = $privpath . "config/" . $filename;

        //check if this file already exists in the installed instance
        if (!file_exists($dst)) {
            // new file: copy!
            echo "new config file " . $filename . "\n";
            copy($src, $dst);
            chmod($dst, 0660);
            chgrp($dst, $defaults["setup"]["htgroup"]);
        } else {
            // file already exists
            //don't touch app, blacklist, libraries, users
            $updateble = array("colors.json", "datatypes.json", "export.json", "import.json", "licenses.json", "modules.json");
            if (in_array($filename, $updateble)) {
                if (md5_file($src) != md5_file($dst)) {
                    // file is changed on one of the two ends (new version in src OR user has changed installed version!)
                    // three options: keep installed, update or try to merge

                    // interactive mode
                    if (!isset($options["d"]) and !isset($options["defaults"])) {
                        echo "\n" . $filename . " has been changed. Keep, update or merge? [merge] ";
                        while (1) {
                            $response = trim(fgets(STDIN));
                            if (!empty($response)) {
                                $response = strtolower($response[0]);
                                if (in_array($response, ["k", "u", "m"])) {
                                    break;
                                } else {
                                    echo "\nInvalid response; please answer with [k]eep, [u]pdate or [m]erge: ";
                                }
                            } else {
                                $response = "m";
                                break;
                            }
                        }
                    } else { //non-interactive mode: merge!
                        $response = "m";
                    }

                    switch ($response) {
                        case "update": // UPDATE
                        case "u":
                            echo " - update configuration file " . $filename . "\n";
                            copy($dst, $dst . "_setup" . date("YmdHis"));  //backup the installed version!
                            copy($src, $dst);
                            chmod($dst, 0660);
                            chgrp($dst, $defaults["setup"]["htgroup"]);
                            break;
                        case "keep": // KEEP = do nothing
                        case "k":
                            echo " - keep original file " . $filename . "\n";
                            break;
                        case "merge":
                        case "m":
                        default:
                            // MERGE
                            echo " - merge configuration file " . $filename . "\n";
                            copy($dst, $dst . "_setup" . date("YmdHis"));  //backup the installed version!
                            $orig = json_decode(file_get_contents($dst), true);
                            $new = json_decode(file_get_contents($src), true);
                            $orig = array_replace_recursive($new, $orig);
                            $orig = json_encode($orig, JSON_PRETTY_PRINT);
                            file_put_contents($dst, $orig);
                            break;
                    }
                }
            }
        }
    }
}

echo "\nFinished.\n\n";


//------------------------//
//   INTERNAL FUNCTIONS   //
//------------------------//


// array of files and subdirs (without . and ..) for a given dir
function scandir2($dir, $filesOnly = false)
{
    if (is_dir($dir)) {
        $files = scandir($dir);
        $files = array_diff($files, [".", ".."]);
        if ($filesOnly) {
            foreach ($files as $f) {
                if (is_dir($f)) $files = array_diff($files, [$f]);
            }
        }
        return $files;
    }
    return array();
}

// removes files and non-empty directories
function rrmdir($dir)
{
    if (is_dir($dir)) {
        $files = scandir2($dir);
        foreach ($files as $file) {
            //echo "  remove dir:  " . $dir . "\n";
            rrmdir("$dir/$file");
        }
        rmdir($dir);
    } else {
        if (file_exists($dir)) {
            //echo "  remove file: " . $dir . "\n";
            unlink($dir);
        }
    }
}


/**
 * Recursively copy files from one directory to another
 * 
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 */
function rcopy($src, $dest, $group, $writable = false){

    // If source is not a directory stop processing
    if(!is_dir($src)) return false;
    
    $src = rtrim($src, "/");
    $dest = rtrim($dest, "/");

    // If the destination directory does not exist create it
    if(!is_dir($dest)) { 
        if(!mkdir($dest)) {
            // If the destination directory could not be created stop processing
            return false;
        }
        //echo " - create DIR " . $dest . "\n";
    }
    chmod($dest, ($writable ? 0770 : 0750));
    chgrp($dest, $group);

    // Open the source directory to read in files
    $i = new DirectoryIterator($src);
    foreach($i as $f) {
        if($f->isFile()) {
            if (file_exists("$dest/" . $f->getFilename())) {
                if (md5_file($f->getRealPath()) != md5_file("$dest/" . $f->getFilename())) {
                    echo " - update FILE " . $f->getFilename() . "\n";
                    copy($f->getRealPath(), "$dest/" . $f->getFilename());
                }
            } else {
                echo " - create FILE " . $f->getFilename() . "\n";
                copy($f->getRealPath(), "$dest/" . $f->getFilename());
            }
            chmod("$dest/" . $f->getFilename(), ($writable ? 0660 : 0640));
            chgrp("$dest/" . $f->getFilename(), $group);
        } else if(!$f->isDot() && $f->isDir()) {
            rcopy($f->getRealPath(), "$dest/$f", $group, $writable);
        }
    }
}


function cfgeval($item, $value)
{
    switch (gettype2($item)) {
        case "boolean":
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            $eval = true;  //True, "true", "on", "yes", "1" are True, all others are False, thus evaluation always succeeds by design
            break;
        case "integer":
            $value = filter_var($value, FILTER_VALIDATE_INT);
            $eval = is_int($value);
            break;
        case "e-mail":
            $value = filter_var($value, FILTER_VALIDATE_EMAIL);
            $eval = is_string($value);
            break;
        case "path":
            $value = (string) $value;
            if (strlen($value) > 0) {
                if (substr($value, -1) != "/") {
                    $value .= "/";
                }
            }
            $eval = (bool) $value;
            break;
        case "string":
        default:
            $value = (string) $value;
            $eval = true; //accept empty strings?
            break;
    }

    return array($eval, $value);
}


function gettype2($item)
{
    // get expected variable type from global $types, or set to default (=string)
    global $types;
    return (isset($types[$item]) ? $types[$item] : "string");
}