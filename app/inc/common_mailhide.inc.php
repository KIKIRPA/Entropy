<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}
  
/********************************************************
 *           MY VERY OWN WRAPPER FUNCTIONS              *
 ********************************************************/
 
function mailhide($email, $show = null, $hoover = "e-mail")
{
    //global $pubkey, $privkey;

    //if MAILHIDE_PUB, MAILHIDE_PRIV not set: don't use mailhide
    if (empty(MAILHIDE_PUB) or empty(MAILHIDE_PRIV)) {
        return "<a href='mailto:" . $email . "'  title=\"" . $hoover . "\">" . (is_null($show) ? $email : $show) . "</a>";
    }
    
    $emailparts = _recaptcha_mailhide_email_parts($email);
    $url = recaptcha_mailhide_url(MAILHIDE_PUB, MAILHIDE_PRIV, $email);
    
    if (is_null($show)) {
        return htmlentities($emailparts[0]) . "<a href='" . htmlentities($url) .
                "' onclick=\"window.open('" . htmlentities($url) .
                "', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;\" title=\"" .
                $hoover . "\">...</a>@" . htmlentities($emailparts[1]);
    } else {
        return "<a href='" . htmlentities($url) . "' onclick=\"window.open('" . htmlentities($url) .
                "', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;\" title=\"" .
                $hoover . "\">" . $show . "</a>";
    }
}
    
    
function searchmailhide($string)
{
    // find e-mail addresses in $string and store them in array $matches
    //$pattern = "/(?:[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*|\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/";
    //$pattern = "/[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})/i";
    //preg_match($pattern, $string, $matches);
    
    $matches = extractEmail($string);
    
    foreach ($matches as $match) {
        $replace = mailhide($match);
        $string = str_replace($match, $replace, $string);
    }
    
    return $string;
}


function extractEmail($string)
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
  
  
  
/********************************************************
 *  MAILHIDE RELATED STUFF TAKEN FROM RECAPTCHALIB.PHP  *
 ********************************************************
 *
 *
 * This is a PHP library that handles calling reCAPTCHA.
 *    - Documentation and latest version
 *          http://recaptcha.net/plugins/php/
 *    - Get a reCAPTCHA API Key
 *          https://www.google.com/recaptcha/admin/create
 *    - Discussion group
 *          http://groups.google.com/group/recaptcha
 *
 * Copyright (c) 2007 reCAPTCHA -- http://recaptcha.net
 * AUTHORS:
 *   Mike Crawford
 *   Ben Maurer
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

 
function _recaptcha_aes_pad($val)
{
    $block_size = 16;
    $numpad = $block_size - (strlen($val) % $block_size);
    return str_pad($val, strlen($val) + $numpad, chr($numpad));
}
 

function _recaptcha_aes_encrypt($val, $ky)
{
    if (! function_exists("mcrypt_encrypt")) {
        die("To use reCAPTCHA Mailhide, you need to have the mcrypt php module installed.");
    }
    $mode=MCRYPT_MODE_CBC;
    $enc=MCRYPT_RIJNDAEL_128;
    $val=_recaptcha_aes_pad($val);
    return mcrypt_encrypt($enc, $ky, $val, $mode, "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0");
}


function _recaptcha_mailhide_urlbase64($x)
{
    return strtr(base64_encode($x), '+/', '-_');
}


/* gets the reCAPTCHA Mailhide url for a given email, public key and private key */
function recaptcha_mailhide_url($pubkey, $privkey, $email)
{
    if ($pubkey == '' || $pubkey == null || $privkey == "" || $privkey == null) {
        die("To use reCAPTCHA Mailhide, you have to sign up for a public and private key, " .
                     "you can do so at <a href='http://www.google.com/recaptcha/mailhide/apikey'>http://www.google.com/recaptcha/mailhide/apikey</a>");
    }
        
    $ky = pack('H*', $privkey);
    $cryptmail = _recaptcha_aes_encrypt($email, $ky);
        
    return "http://www.google.com/recaptcha/mailhide/d?k=" . $pubkey . "&c=" . _recaptcha_mailhide_urlbase64($cryptmail);
}


/**
 * gets the parts of the email to expose to the user.
 * eg, given johndoe@example,com return ["john", "example.com"].
 * the email is then displayed as john...@example.com
 */
function _recaptcha_mailhide_email_parts($email)
{
    $arr = preg_split("/@/", $email);

    if (strlen($arr[0]) <= 4) {
        $arr[0] = substr($arr[0], 0, 1);
    } elseif (strlen($arr[0]) <= 5) {
        $arr[0] = substr($arr[0], 0, 2);
    } else {
        $arr[0] = substr($arr[0], 0, 3);
    }
        
    return $arr;
}
