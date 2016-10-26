<?php

  //error_reporting(E_ALL);
  //ini_set('display_errors', '1');
  
  require_once('./_config/config.inc.php');
  require_once('./inc/common_basic.inc.php');
  require_once("./inc/common_mailhide.inc.php");
  
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
   
  if (!IS_HTTPS or IS_BLACKLISTED)
    include("./inc/error_404.inc.php");
    

  if ($is_expired)
  {
    $module = "loginform";
    $msg = "Your session has expired.  Please re-login.";
    goto skip;
  }
  
  
/* ************ 
    2. LOG OUT
   ************ */  
   
  
  if (isset($_REQUEST["logout"]) 
        and isset($_SESSION['username'])
        and !isset($module)
     )   //logout: destroy session first (in order to return to the login page in the next if)
  {
    eventLog("AUTH", "User " . $_SESSION['username'] . " has logged out.");
    logout();
    
    $is_logged_in = false;
    $msg = "You have successfully logged out.";
    $module = "loginform";  // show login form (again)
    goto skip;
  }
  
  
/* ************ 
    3. LOG IN
   ************ */  
  
  $users = readJSONfile(USERS_FILE, true);
  
  if (!isset($_SESSION['username']) and !isset($module))
  {
    if (isset($_POST['user']) and isset($_POST['pass']))
    { //login action
      // 1. check username or email address
      if (filter_var($_POST['user'], FILTER_VALIDATE_EMAIL))
      {
        foreach ($users as $id => $userarray)
          if ($userarray['email'] == $_POST['user']) 
            $user = $id;
      }
      else
        if (isset($users[$_POST['user']]))
          $user = $_POST['user'];
      
      if (!empty($user))        //only if the supplied username/email occurs in the users-table, this will be set
      {         
        // 2. check if not blocked (tries >= maxtries)
        if (count($users[$user]['tries']) >= MAXTRIES_PW)   // account blocked!
        {
          if (count($users[$user]['tries']) == MAXTRIES_PW)
            eventLog("AUTH", "Account " . $user .  " has been disabled due to too many failed login attempts.", false, true);
          
          $msg = "The account <i>" . $user . "</i> has been disabled due to too many failed login attempts. Contact the site administrator to re-enable it.";
          $module = "loginform";
          goto skip;
        }
        
        // 3. check password
        if (!password_verify($_POST['pass'], $users[$user]['hash']))
        { //wrong password!
          $users[$user]['tries'][$ts] = $ip;     // username based login tries  --> blocking account
          $blacklist[$ip][$ts] = $_POST['user']; // ip based blacklist
          
          $msg = "Wrong password for " . $user . ".  Attempt " . (count($users[$user]['tries']) + 1) . " of " . MAXTRIES_PW ;  //add number of tries left?
          $module = "loginform";        // show login form (again)
          eventLog("AUTH", "Login " . $user .  " FAILED. Password count: " . count($users[$user]['tries']));
        }
        else
        { //correct password!
          if (!isset($users[$user]['tries'])) $users[$user]['tries'] = array();
          if (!isset($users[$user]['trusted'])) $users[$user]['trusted'] = array();
          $users[$user]['lastlogin'] = $ts . " from " . $ip;
          
          if (count($users[$user]['tries']) >= 1) //reset failed passwd counter
          {
            $users[$user]['tries'] = array();
            $blacklist[$ip] = array();
          }
          
          eventLog("AUTH", "Login ". $user .  " SUCCESS.");
          //login successfull -> let the following procedure set $module
          unset($module, $msg);
          
          $_SESSION['username'] = $user;          // create session username
          $_SESSION['trusted'] = (in_array($ip, $users[$user]['trusted']) or in_array("*", $users[$user]['trusted']));
          $_SESSION['pwdok'] = empty($users[$user]['reset']);
        }
      }
      else
      { // wrong username/email!
        $blacklist[$ip][$ts] = $_POST['user'];	// IP blacklisting
        
        eventLog("AUTH", "Login attempt for inexistent user ". $_POST['user'] . ". IP count: " . count($blacklist[$ip]));
        $msg = "Username/password mismatch. Attempt " . count($blacklist[$ip]);
        $module = "loginform";
      }
    }
    else
    { // show login form!  --> display $msg if set!
      $module = "loginform";    //show loginform
    }
  }
  else
    $user = $_SESSION['username'];
    

/* *************************************** 
    4. two-factor auth for untrusted ip's
   *************************************** */
  
  //if (empty($module) and !in_array($ip, $users[$user]['trusted']))  //isset($_SESSION('untrusted'))
  if (!$_SESSION['trusted'] 
        and !isset($module)
        and isset($_SESSION['username'])
     )
  {
    // if no trustcode is given (POST): create one and show form
    if (empty($_POST['trustcode']))
    {
      $_SESSION['trustcode'] = sprintf("%04d", mt_rand(0 , 9999));
      mail( $users[$user]['email'], 
            "SpecLib authorisation code", 
            "Automated mail from speclib.kikirpa.be, do not reply.\r\n\r\nAuthorisation code : <b>" . $_SESSION['trustcode'] . "</b>\r\n\r\nCopy this code in the designated code box on speclib.kikirpa.be.\r\nThe code is time-limited and will expire after a given time.\r\n",
            "From: noreply@kikirpa.be\r\nReply-To: noreply@kikirpa.be\r\nX-Mailer: PHP/" . phpversion()
          );
      $module = "trustform";
      unset($msg);
    }
    // if trustcode is given (POST): evaluate
    else
    {
      if ($_POST['trustcode'] == $_SESSION['trustcode'])
      { //correct
        $_SESSION['trusted'] = true;
        array_push($users[$user]['trusted'], $ip);
        unset($module, $msg);       // let $module be set later
        $blacklist[$ip] = array();  //reset blacklist
      }
      else 
      { //incorrect
        $blacklist[$ip][$ts] = $user . ": wrong trustcode";  // IP blacklisting
        $module = "trustform";
        $msg = "Try again!";
      }
    }   
  }

  
/* **************** 
    5 reset passwd
   **************** */
    
  //if (empty($module) and !empty($users[$user]['reset']))
  if (!$_SESSION['pwdok']         
        and !isset($module)
        and isset($_SESSION['username'])
     )
  {
    //reset pw form or action
    if (!empty($_POST['oldpwd']) and !empty($_POST['newpwd']) and !empty($_POST['verify']))
    { //password set action
      if (!password_verify($_POST['oldpwd'], $users[$user]['hash']))
      {
        $module = "resetpwdform";
        $msg = "The old (original) password was wrong!";
      }
      elseif ($_POST['newpwd'] != $_POST['verify'])
      {
        $module = "resetpwdform";
        $msg = "The new passwords did not match!";
      }
      else
      {
        $_SESSION['pwdok'] = true;
        $users[$user]['hash'] = password_hash($_POST['newpwd'], PASSWORD_DEFAULT);
        $users[$user]['date'] = $ts;
        $users[$user]['reset'] = false;
        eventLog("AUTH", "Reset password for " . $user);
        unset($module, $msg);
      }
    }
    else
    { //passwd reset form
      $module = "resetpwdform";
      unset($msg);
    }
  }
  
  //all security checks are performed!
  if (!file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT))) 
    eventLog("WARNING", "Could not write to ". USERS_FILE);
  if (!file_put_contents(BLACKLIST_FILE, json_encode($blacklist, JSON_PRETTY_PRINT))) 
    eventLog("WARNING", "Could not write to ". BLACKLIST_FILE);
  
  
/* ***************** 
    6. login success
   ***************** */
  
  if (isset($_SESSION['username']) 
        and $_SESSION['trusted'] 
        and $_SESSION['pwdok']
        and !isset($module)
     )
  {
    $is_logged_in = $_SESSION['username'];
    $user = $users[$is_logged_in];
    $module = "empty";
    $msg = "Welcome " . $_SESSION['username'] . ".";
  }

  if (!isset($module))
  {
    $module = "empty";
    $msg = "Unknown error!";
  }

/* *********************** 
    7. OUTPUT HTML
   *********************** */ 

skip:
  
  $htmltitle = APP_SHORT;
  $htmlkeywords = APP_KEYWORDS;
  $pagetitle = APP_LONG;
  $pagesubtitle = "";
  $style   = "";
  $scripts = "";

  include(HEADER_FILE); 
  if ($module != "empty") include("./inc/auth_" . $module . ".inc.php");
  else                    if (isset($msg)) echo $msg;
  include(FOOTER_FILE);
  
?>
