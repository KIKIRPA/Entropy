<?php

namespace Core\Service;

class Download
{
    public static function path($filePath, $filename = null, $dieOnError = false) 
    {
        // NOTE: handle/fpasstru fail on very large files!
        //$handle = fopen($filePath, "rb");
        //
        //if ($handle) {
        //   if (is_null($filename)) $filename = basename($filePath);
        //   self::handle($handle, $filename, $dieOnError);

        if (file_exists($filePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            ob_clean();
            ob_end_flush();
            $handle = fopen($filePath, "rb");
            while (!feof($handle)) {    // serve the file in small bits
                echo fread($handle, 1000);
            }
        } elseif (!is_file($filePath)) { // check if file exists, is file and is accessible
            eventLog("ERROR", "File not found: $filePath [Core\\Service\\Download]");
            if ($dieOnError) self::error("404 Not Found");
            else return false;
        } elseif (!is_readable($filePath)) {
            eventLog("ERROR", "File not accessible: $filePath [Core\\Service\\Download]");
            if ($dieOnError) self::error("403 Forbidden");
            return false;
        } else {
            eventLog("ERROR", "File could not be served: $filePath [Core\\Service\\Download]");
            if ($dieOnError) self::error("404 Not Found");
            return false;
        }
    }

    public static function handle($handle, $filename, $dieOnError = false)
    {
        // file size from the open handle
        $stat = fstat($handle);
        $size = $stat["size"];
        
        // serve file
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $size);
        fpassthru($handle);
        fclose($handle);
        die();
    }

    public static function url($url, $dieOnError = false) 
    {
        if ($dieOnError and (filter_var($url, FILTER_VALIDATE_URL) === false)) {
            self::error("404 Not Found");
        }
        
        ob_start();
        header('Location: ' . $url);
        ob_end_flush();
        die();
    }

    /**
     * error($msg)
     * 
     * examples for $msg:
     *  - "400 Bad request"
     *  - "403 Forbidden"
     *  - "404 Not Found"
     */
    public static function error($msg)
    {
        header("{$_SERVER['SERVER_PROTOCOL']} $msg");
        header("Status: $msg");
        echo $msg;
        die();
    }
}