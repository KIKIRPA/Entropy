<?php

namespace Core\Service;

class Randomizer
{
    private static $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
    
    public static function generateString($length = 6)
    {
        $string = "";
        $characters_length = strlen(self::$characters) - 1;

        for ($i = 0; $i < $length; $i++) {
          $string .= self::$characters[mt_rand(0, $characters_length)];
        }

        return $string;
    }
}