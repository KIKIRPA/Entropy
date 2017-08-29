<?php

// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}
  


function writeJSONfile($file, $content)
{
    // writes (and if necessary backups) a json file
    //   $file = complete path to file
    //   $content = array or json string to write
    //   returns errormsg, false on success

    //array -> json
    if (is_array($content)) {
        $content = json_encode($content, JSON_PRETTY_PRINT);
        if (empty($content)) {
            return "could not make json";
        }
    }

    //backup old file (if it exists)
    if (backupFile(pathinfo($file, PATHINFO_DIRNAME) . "/", pathinfo($file, PATHINFO_BASENAME))) {
        //(over)write file
    if (file_put_contents($file, $content)) {
        return false;
    }  // = no error!
    else {
        return "could not write file";
    }
    } else {
        return "failed to make backup";
    }
}


function backupFile($dir, $old, $new = false)
{
    //backups a file or directory (if present) as $dir/.backup/$new_date
    //  IN: $dir in which the file/dir is, including trailing "/"
    //      $old is the file/dir to backup
    //      $new (optional) if not set =$old
    //  OUT: true if successful, false on error
    //       note: if the file/dir is not found: proceed without error

    if (!$new) {
        $new = $old;
    }

    if (file_exists($dir . $old)) {
        if (mkdir2($dir . ".backup")) {
            return rename($dir . $old, $dir. ".backup/" . $new . "_" . date("YmdHis"));
        }
    }
    return true;
}


function mkdir2($dir)
{
    if (file_exists($dir)) {
        return true;
    } else {
        return (mkdir($dir, 0777) and file_put_contents($dir . ".htaccess", "Deny from all"));
    }
}


// delete directory with contents
function rmdir2($dirPath)
{
    if (is_dir($dirPath)) {
        $objects = scandir($dirPath);
        foreach ($objects as $object) {
            if ($object != "." && $object !="..") {
                if (filetype($dirPath . DIRECTORY_SEPARATOR . $object) == "dir") {
                    rmdir2($dirPath . DIRECTORY_SEPARATOR . $object);
                } else {
                    unlink($dirPath . DIRECTORY_SEPARATOR . $object);
                }
            }
        }
        reset($objects);
        rmdir($dirPath);
    }
}
