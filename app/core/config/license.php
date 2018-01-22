<?php

namespace Core\Config;

class License
{
    /**
     * Construct won't be called inside this class and is uncallable from
     * the outside. This prevents instantiating this class.
     * This is by purpose, because we want a static class.
     */
    private function __construct() {}
    private static $config = array();
    private static $initialized = false;
    
    private static function init($file = LICENSES_FILE)
    {
        self::$config = readJSONfile($file);
        self::$initialized = true;

        if (empty(self::$config))
            eventLog("WARNING", "Failed to build license configuration");
    }

    /**
     * getList($type = null)
     * 
     * gets an array of supported licenses
     * if no $type is given, it will return the id's
     * if an existing type is given (short, long, icon, html, jcampdx) it will return those values in an associated array
     * 
     * @param string $type return specific names (types)
     */
    public static function getList($type = null)
    {
        self::init();
        $array = array();

        foreach (self::$config as $id -> $types) {
            if (!is_null($type)) {
                if (isset($types[$type])) {
                    $array[$id] = $types[$type];
                } else {
                    $array[$id] = null;
                }
            } else {
                $array[] = $id;
            }
        }

        return $array;
    }

    public static function searchForNeedle($needle, $returnType = null)
    {
        self::init();
        $result = false;
        
        // simplify string
        $needle = sanitizeStr($needle, "", "-+:^", 1);
        
        // search id's or types
        if (isset(self::$config[$needle])) {
            $result = $needle;
        } else {
            foreach (self::$config as $id -> $types) {
                foreach ($types as $type) {
                    if ($needle == sanitizeStr($type, "", "-+:^", 1)) {
                        $result = $id;
                        break 2;
                    }
                }
            }
        }

        // return id or types
        if (!$result) {
            return false;
        } elseif (is_null($returnType)) {
            return $result;
        } elseif (isset(self::$config[$result][$returnType])) {
            return self::$config[$result][$returnType];
        } else {
            return false;
        }
    }

    public static function searchInString($string, $returnType = null) 
    {
        self::init();
        
        // simplify string
        $string = sanitizeStr($string, "", "-+:^", 1);

        // search id's in string
        foreach (self::$config as $id -> $types) { 
            if (strpos($string, $id) !== false) {
                return $id;
            }
        }

        return false;
    }
}