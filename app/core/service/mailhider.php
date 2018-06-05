<?php

namespace Core\Service;

class MailHider
{
    public static function createLink($email, $linkText = false, $requireClick = true)
    {
        $encoded = str_rot13($email);
        $encoded = explode("@", $encoded);
        $replace = "";

        // if no $linkText is given, create one like "w...@gmail.com (unkhide)"
        if (!$linkText) {
            $linkText = substr($email, 0, 1) . "...@" . explode("@", $email)[1] . " (unhide)";
            $replace = ", true";
        }

        // generate a unique html id so we can change the right address using js
        $id = Randomizer::generateString(4);
        $command = "decrypt('". $encoded[0] . "', '". $encoded[1] . "', '" . $id . "'" . $replace . ");";

        if ($requireClick) {
            // require a mouse click to decrypt the address (run the js after clicking)
            return "<a id=\"" . $id . "\" href=\"JavaScript:$command\">" . $linkText . "</a>";
        } else {
            // automatically decrypt the e-mail address (run the js automatically)
            return "<a id=\"" . $id . "\"> $linkText</a><script type='text/javascript'>$command</script>";
        }
    }


    public static function search($string)
    {
        // find e-mail addresses in $string and store them in array $matches
        //$pattern = "/(?:[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*|\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/";
        //$pattern = "/[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})/i";
        //preg_match($pattern, $string, $matches);
        
        $matches = self::extractEmail($string);
        
        foreach ($matches as $match) {
            $replace = self::createLink($match);
            $string = str_replace($match, $replace, $string);
        }
        
        return $string;
    }


    private static function extractEmail($string)
    {
        $emails = array();
        $string = str_replace(array("<", ">", "\r\n", "\n"), ' ', $string);

        foreach (preg_split('/ /', $string) as $token) {
            $email = filter_var($token, FILTER_VALIDATE_EMAIL);
            if ($email !== false) {
                $emails[] = $email;
            }
        }
        
        return $emails;
    }
}