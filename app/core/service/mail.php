<?php

namespace Core\Service;

/* This class uses the following configuration items: 
    - app_name
    - mail_backend (system|sendgrid|none)
    - mail_sendgrid_apikey
    - mail_from_address
    - mail_admin (=mail_from_address) DEPRECIATED
    - mail_from_name
*/


class Mail
{
    public static function send($to, $message, $subject = "Notification") 
    {
        // e-mail subject
        $subject = "[" . \Core\Config\App::get("app_name") . "] " . $subject;

        // messages in plain and html text
        $message_plain = strip_tags($message);
        $sendAsHtml = ($message != $message_plain); 

        // from-address (if none set: return false!)
        if (\Core\Config\App::get("mail_from_address")) $from_address = \Core\Config\App::get("mail_from_address");
        elseif (\Core\Config\App::get("mail_admin")) $from_address = \Core\Config\App::get("mail_admin");
        else return false;
        $from_name = \Core\Config\App::get("mail_from_name");

        // e-mail back-ends:
        // 1. system: uses the sendmail configuration configured in the operating system
        // 2. sendgrid: uses SendGrid (note: this requires a valid API KEY)
        // 3. none: no e-mails will be sent        
        $backend = strtolower(\Core\Config\App::get("mail_backend"));

        // SYSTEM
        if ($backend === "system") {
            // Unique boundary
            $boundary = md5(uniqid().microtime());
            
            // Headers
            if ($from_name) $headers = "From: " . $from_name . " <" . $from_address . ">\r\n";
            else            $headers = "From: " . $from_address . "\r\n";
            
            $headers .= "MIME-Version: 1.0\r\n";

            // multipart (plain + html)
            if ($sendAsHtml) {
                $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n\r\n";
                
                // Plain text version of message
                $body = "--$boundary\r\n"
                    . "Content-Type: text/plain; charset=ISO-8859-1\r\n" 
                    . "Content-Transfer-Encoding: base64\r\n\r\n"
                    . chunk_split(base64_encode($message_plain));
                
                // HTML version of message
                $body .= "--$boundary\r\n" 
                    . "Content-Type: text/html; charset=ISO-8859-1\r\n" 
                    . "Content-Transfer-Encoding: base64\r\n\r\n"
                    . chunk_split(base64_encode($message))
                    . "--$boundary--";
            }         
            
            // Send Email
            mail($to, $subject, $body, $headers);
            return true;
        }

        // SENDGRID
        if ($backend === "sendgrid") {    
            require 'vendor/autoload.php';

            $apikey = \Core\Config\App::get("mail_sendgrid_apikey");
            
            $email = new \SendGrid\Mail\Mail(); 
            $email->setFrom($from_address, $from_name);
            $email->setSubject($subject);
            $email->addTo($to);
            $email->addContent("text/plain", $message_plain);
            if ($sendAsHtml)
                $email->addContent("text/html", $message);

            $sendgrid = new \SendGrid($apikey);
            try {
                $response = $sendgrid->send($email);
                if ($response->statusCode() == 202) return true;
                //TODO add eventlog()
                echo 'Statuscode: ' . $response->statusCode() . "\n"; // DEBUG
                //print_r($response->headers());
                //print $response->body() . "\n";
            } catch (Exception $e) {
                //TODO add eventlog()
                echo 'Caught exception: '. $e->getMessage() ."\n"; // DEBUG
            }
        }

        // else: do nothing; no mail!       
        return false;
    }
}