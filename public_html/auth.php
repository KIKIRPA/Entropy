<?php

require_once('install.conf.php');
//require_once(PRIVPATH . 'entropy.conf.php');
require_once(PRIVPATH . 'inc/autoloader.php');
require_once(PRIVPATH . 'inc/init.inc.php');
require_once(PRIVPATH . 'inc/common_basic.inc.php');

$ts = mdate('Y-m-d H:i:s.u');
$ip = $_SERVER['REMOTE_ADDR'];

//  1. SECURITY: https, blacklist, expired session
//  2. logout
//  3. login
//  4. 2-factor auth
//  5. reset passwd
//  6. login complete
//  7. html output


/* **************
    1. SECURITY
************** */

if (!IS_HTTPS) {
    $showMod = "empty";
    $msg = "A https connection is required for this page.";
}

if (IS_BLACKLISTED) {
    $showMod = "empty";
    $msg = "This IP has been blacklisted due to too many failed login attempts. Please contact the system administrator.";
}

if ($isExpired) {
    $showMod = "loginform";
    $msg = "Your session has expired.  Please re-login.";
    goto skip;
}


/* ************
    2. LOG OUT
************ */


if (isset($_REQUEST["logout"])
        and isset($_SESSION['username'])
        and !isset($showMod)
    ) {   //logout: destroy session first (in order to return to the login page in the next if)
    eventLog("AUTH", "User " . $_SESSION['username'] . " has logged out.");
    logout();
    
    $isLoggedIn = false;
    $msg = "You have successfully logged out.";
    $showMod = "loginform";  // show login form (again)
    goto skip;
}


/* ************
    3. LOG IN
************ */

if (!isset($_SESSION['username']) and !isset($showMod)) {
    if (isset($_POST['user']) and isset($_POST['pass'])) { //login action
        // 1. check username or email address
        if (filter_var($_POST['user'], FILTER_VALIDATE_EMAIL)) {
            foreach ($USERS as $id => $userarray) {
                if ($userarray['email'] == $_POST['user']) {
                    $user = $id;
                }
            }
        } elseif (isset($USERS[$_POST['user']])) {
            $user = $_POST['user'];
        }
    
        if (!empty($user)) {        //only if the supplied username/email occurs in the users-table, this will be set
        // 2. check if not blocked (tries >= maxtries)
        $maxTries = \Core\Config\App::get("login_password_attempts");
        if (count($USERS[$user]['tries']) >= $maxTries) {   // account blocked!
            if (count($USERS[$user]['tries']) == $maxTries) {
                eventLog("AUTH", "Account " . $user .  " has been disabled due to too many failed login attempts.", false, true);
            }
        
            $msg = "The account <i>" . $user . "</i> has been disabled due to too many failed login attempts. Contact the site administrator to re-enable it.";
            $showMod = "loginform";
            goto skip;
        }
        
            // 3. check password
        if (!password_verify($_POST['pass'], $USERS[$user]['hash'])) { //wrong password!
        $USERS[$user]['tries'][$ts] = $ip;     // username based login tries  --> blocking account
        $BLACKLIST[$ip][$ts] = $_POST['user']; // ip based blacklist
        
        $msg = "Wrong password for " . $user . ".  Attempt " . (count($USERS[$user]['tries']) + 1) . " of " . \Core\Config\App::get("login_password_attempts") ;  //add number of tries left?
        $showMod = "loginform";        // show login form (again)
        eventLog("AUTH", "Login " . $user .  " FAILED. Password count: " . count($USERS[$user]['tries']));
        } else { //correct password!
            if (!isset($USERS[$user]['tries'])) {
                $USERS[$user]['tries'] = array();
            }
            if (!isset($USERS[$user]['trusted'])) {
                $USERS[$user]['trusted'] = array();
            }
            $USERS[$user]['lastlogin'] = $ts . " from " . $ip;
        
            if (count($USERS[$user]['tries']) >= 1) { //reset failed passwd counter
                $USERS[$user]['tries'] = array();
                $BLACKLIST[$ip] = array();
            }
        
            eventLog("AUTH", "Login ". $user .  " SUCCESS.");
            //login successfull -> let the following procedure set $showMod
            unset($showMod, $msg);
        
            $_SESSION['username'] = $user;          // create session username
            $_SESSION['trusted'] = (in_array($ip, $USERS[$user]['trusted']) or in_array("*", $USERS[$user]['trusted']));
            $_SESSION['pwdok'] = empty($USERS[$user]['reset']);
        }
        } else { // wrong username/email!
        $BLACKLIST[$ip][$ts] = $_POST['user'];    // IP blacklisting
        
        eventLog("AUTH", "Login attempt for inexistent user ". $_POST['user'] . ". IP count: " . count($BLACKLIST[$ip]));
            $msg = "Username/password mismatch. Attempt " . count($BLACKLIST[$ip]);
            $showMod = "loginform";
        }
    } else { // show login form!  --> display $msg if set!
    $showMod = "loginform";    //show loginform
    }
} else {
    $user = $_SESSION['username'];
}
    

/* ***************************************
    4. two-factor auth for untrusted ip's
*************************************** */

//if (empty($showMod) and !in_array($ip, $USERS[$user]['trusted']))  //isset($_SESSION('untrusted'))
if (\Core\Config\App::get("login_twopass_enable")) {
    if (!$_SESSION['trusted']
        and !isset($showMod)
        and isset($_SESSION['username'])
    ) {
        // if no trustcode is given (POST): create one and show form
        if (empty($_POST['trustcode'])) {
            $_SESSION['trustcode'] = sprintf("%04d", mt_rand(0, 9999));
            $from = \Core\Config\App::get("mail_admin");
            mail(
                $USERS[$user]['email'],
                APP_NAME . " authorisation code",
                "Automated mail from " . gethostname() . ", do not reply.\r\n\r\nAuthorisation code : <b>" . $_SESSION['trustcode'] . "</b>\r\n\r\nCopy this code in the designated code box on " . gethostname() . ".\r\nThe code is time-limited and will expire after a given time.\r\n",
                "From:  " . $from . "\r\nReply-To:  " . $from . "\r\nX-Mailer: PHP/" . phpversion()
            );
            $showMod = "trustform";
            unset($msg);
        }
        // if trustcode is given (POST): evaluate
        else {
            if ($_POST['trustcode'] == $_SESSION['trustcode']) { //correct
                $_SESSION['trusted'] = true;
                array_push($USERS[$user]['trusted'], $ip);
                unset($showMod, $msg);       // let $showMod be set later
        $BLACKLIST[$ip] = array();  //reset blacklist
            } else { //incorrect
        $BLACKLIST[$ip][$ts] = $user . ": wrong trustcode";  // IP blacklisting
        $showMod = "trustform";
                $msg = "Try again!";
            }
        }
    }
} else {
    $_SESSION['trusted'] = true;
}


/* ****************
    5 reset passwd
**************** */
    
//if (empty($showMod) and !empty($USERS[$user]['reset']))
if (!$_SESSION['pwdok']
        and !isset($showMod)
        and isset($_SESSION['username'])
    ) {
    //reset pw form or action
    if (!empty($_POST['oldpwd']) and !empty($_POST['newpwd']) and !empty($_POST['verify'])) { //password set action
    if (!password_verify($_POST['oldpwd'], $USERS[$user]['hash'])) {
        $showMod = "resetpwdform";
        $msg = "The old (original) password was wrong!";
    } elseif ($_POST['newpwd'] != $_POST['verify']) {
        $showMod = "resetpwdform";
        $msg = "The new passwords did not match!";
    } else {
        $_SESSION['pwdok'] = true;
        $USERS[$user]['hash'] = password_hash($_POST['newpwd'], PASSWORD_DEFAULT);
        $USERS[$user]['date'] = $ts;
        $USERS[$user]['reset'] = false;
        eventLog("AUTH", "Reset password for " . $user);
        unset($showMod, $msg);
    }
    } else { //passwd reset form
        $showMod = "resetpwdform";
        unset($msg);
    }
}

//all security checks are performed!
if (!file_put_contents(\Core\Config\App::get("config_users_file"), json_encode($USERS, JSON_PRETTY_PRINT))) {
    eventLog("WARNING", "Could not write to users configuration file");
}
if (!file_put_contents(\Core\Config\App::get("config_blacklist_file"), json_encode($BLACKLIST, JSON_PRETTY_PRINT))) {
    eventLog("WARNING", "Could not write to blacklist file");
}


/* *****************
    6. login success
***************** */

if (isset($_SESSION['username'])
        and $_SESSION['trusted']
        and $_SESSION['pwdok']
        and !isset($showMod)
    ) {
    $isLoggedIn = $_SESSION['username'];
    $user = $USERS[$isLoggedIn];
    $showMod = "empty";
    $msg = "Welcome " . $_SESSION['username'] . ".";
}

if (!isset($showMod)) {
    $showMod = "empty";
    $msg = "Unknown error!";
}

/* ***********************
    7. OUTPUT HTML
*********************** */

skip:

include(PRIVPATH . 'inc/header.inc.php');

if ($showMod != "empty") {
    include(PRIVPATH . 'inc/auth_' . $showMod . '.inc.php');
} elseif (isset($msg)) {
    echo $msg;
}
include(PRIVPATH . 'inc/footer.inc.php');
