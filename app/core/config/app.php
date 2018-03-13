<?php

namespace Core\Config;

class App
{
    /**
     * Construct won't be called inside this class and is uncallable from
     * the outside. This prevents instantiating this class.
     * This is by purpose, because we want a static class.
     */
    private function __construct() {}
    private static $config = array();
    private static $initialized = false;
    
    private static function init($file = PRIVPATH . "config/app.json")
    {
        // load default settings
        self::$config = readJSONfile(__DIR__ . "/app_defaults.json");
        
        // load user-defined settings
        if (file_exists($file)) {
            $userSettings = readJSONfile($file);
            self::$config = array_merge(self::$config, $userSettings);
        }

        self::$initialized = true;

        if (empty(self::$config))
            eventLog("WARNING", "Failed to build app configuration");
    }

    public static function get($item)
    {
        self::init();

        if (isset(self::$config[$item])) {
            $value = self::$config[$item];
            $value = str_replace("%ENTROPY_PATH%", PRIVPATH, $value);
            return $value;
        } else {
            eventLog("WARNING", "Nonexistent item requested from app configuration: " . $item);
            return "";
        }
    }
}