<?php

namespace Core\Service;

class DownloadCode
{
    private static $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';

    /**
     * generateCode($length = 6)
     * 
     * generate a random downloadcode
     * (used mainly internally by storePath())
     */
    public static function generateCode($length = 6)
    {
        $code = "";
        $characters_length = strlen(self::$characters) - 1;

        for ($i = 0; $i < $length; $i++) {
          $code .= self::$characters[mt_rand(0, $characters_length)];
        }

        //extremely small chance, but check if this code does not yet exist
        if (isset($_SESSION['downloadcodes'][$code])) {
            self::generateCode($length);
        }

        return $code;
    }

    /**
     * storePath($path, $prefix = "", $codeLength = 6)
     * 
     * create and store in the session a downloadcode with a file path or an URL.
     * For file paths, a prefix (absolute storage path) will be prepended.
     */
    public static function storePath($path, $prefix = "", $codeLength = 6)
    {
        $code = self::generateCode($codeLength);

        try {
            if ((substr($path, 0, 7) === 'http://') or (substr($path, 0, 8) === 'https://')) {
                if (filter_var($path, FILTER_VALIDATE_URL) === FALSE) {
                    throw new \Exception("Invalid URL.");
                }
                $_SESSION['downloadcodes'][$code]['url'] = $path;
            } else {
                //make an absolute file path
                $path = str_replace("file://", "", $path);
                $path = ltrim($path, "./\\"); //prevent escaping from our "jail" using ../
                $path = str_replace("..", "", $path);
                $path = $prefix . "/" . $path;

                //check and correct path
                $path = realpath($path);
                if ($path === false) {
                    throw new \Exception("Invalid file path.");
                }
                $_SESSION['downloadcodes'][$code]['path'] = $path;
            }
            
            return $code;
        } catch (\Exception $e) {
            $errormsg = $e->getMessage();
            eventLog("ERROR", $errormsg  . " [downloadcode]");
        
            return false;
        }
    }

    /**
     * download($code, $dieOnError = false)
     * 
     * create a download http response for a download code that was previously stored in the session
     * on error, return false by default, or die with an error message (if $dieOnError == true)
     */
    public static function download($code, $dieOnError = false)
    {
        if (isset($_SESSION['downloadcodes'][$code])) {
            $dl = $_SESSION['downloadcodes'][$code];

            if (isset($dl['path'])) {
                if (!is_file($dl['path'])) {
                    eventLog("ERROR", "File not found: " . $dl['path'] . " [downloadcode]");
                    if ($dieOnError) self::_error("404 Not Found");
                    else return false;
                } elseif (!is_readable($dl['path'])) {
                    eventLog("ERROR", "File not accessible: " . $dl['path'] . " [downloadcode]");
                    if ($dieOnError) self::_error("403 Forbidden");
                    return false;
                } else {
                    self::_downloadPath($dl['path']);
                }
                
            } elseif (isset($dl['url'])) {
                self::_downloadPath($dl['url']);
            }

        } else {
            eventLog("ERROR", "Downloadcode $code does not exist or is expired [downloadcode]");
            if ($dieOnError) self::_error("400 Bad request");
            else return false;
        }
    }


    private static function _downloadPath($filePath) 
    {
        header('Content-Type: ' . mime_content_type($filePath));
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        die();
    }

    private static function _downloadURL($url) 
    {
        ob_start();
        header('Location: ' . $url);
        ob_end_flush();
        die();
    }

    /**
     * _error($msg)
     * 
     * examples for $msg:
     *  - "400 Bad request"
     *  - "403 Forbidden"
     *  - "404 Not Found"
     */
    private static function _error($msg)
    {
        header("{$_SERVER['SERVER_PROTOCOL']} $msg");
        header("Status: $msg");
        echo $msg;
        die();
    }
}