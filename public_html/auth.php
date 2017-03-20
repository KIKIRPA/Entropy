<?php
  require_once('../entropy.conf.inc.php');
  require_once(ENTROPY_PATH . 'inc/init.inc.php');
  require_once(ENTROPY_PATH . 'inc/common_basic.inc.php');
  require_once(ENTROPY_PATH . 'inc/common_mailhide.inc.php');
  
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
   
  if (!IS_HTTPS)
  {
    $module = "empty";
    $msg = "A https connection is required for this page.";
  }
  
  if (IS_BLACKLISTED)
  {
    $module = "empty";
    $msg = "This IP has been blacklisted due to too many failed login attempts. Please contact the system administrator.";
  }

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
  
  //$USERS = readJSONfile(USERS_FILE, true);  --> already loaded in init.inc.php
  
  if (!isset($_SESSION['username']) and !isset($module))
  {
    if (isset($_POST['user']) and isset($_POST['pass']))
    { //login action
      // 1. check username or email address
      if (filter_var($_POST['user'], FILTER_VALIDATE_EMAIL))
      {
        foreach ($USERS as $id => $userarray)
          if ($userarray['email'] == $_POST['user']) 
            $user = $id;
      }
      else
        if (isset($USERS[$_POST['user']]))
          $user = $_POST['user'];
      
      if (!empty($user))        //only if the supplied username/email occurs in the users-table, this will be set
      {         
        // 2. check if not blocked (tries >= maxtries)
        if (count($USERS[$user]['tries']) >= MAXTRIES_PW)   // account blocked!
        {
          if (count($USERS[$user]['tries']) == MAXTRIES_PW)
            eventLog("AUTH", "Account " . $user .  " has been disabled due to too many failed login attempts.", false, true);
          
          $msg = "The account <i>" . $user . "</i> has been disabled due to too many failed login attempts. Contact the site administrator to re-enable it.";
          $module = "loginform";
          goto skip;
        }
        
        // 3. check password
        if (!password_verify($_POST['pass'], $USERS[$user]['hash']))
        { //wrong password!
          $USERS[$user]['tries'][$ts] = $ip;     // username based login tries  --> blocking account
          $BLACKLIST[$ip][$ts] = $_POST['user']; // ip based blacklist
          
          $msg = "Wrong password for " . $user . ".  Attempt " . (count($USERS[$user]['tries']) + 1) . " of " . MAXTRIES_PW ;  //add number of tries left?
          $module = "loginform";        // show login form (again)
          eventLog("AUTH", "Login " . $user .  " FAILED. Password count: " . count($USERS[$user]['tries']));
        }
        else
        { //correct password!
          if (!isset($USERS[$user]['tries'])) $USERS[$user]['tries'] = array();
          if (!isset($USERS[$user]['trusted'])) $USERS[$user]['trusted'] = array();
          $USERS[$user]['lastlogin'] = $ts . " from " . $ip;
          
          if (count($USERS[$user]['tries']) >= 1) //reset failed passwd counter
          {
            $USERS[$user]['tries'] = array();
            $BLACKLIST[$ip] = array();
          }
          
          eventLog("AUTH", "Login ". $user .  " SUCCESS.");
          //login successfull -> let the following procedure set $module
          unset($module, $msg);
          
          $_SESSION['username'] = $user;          // create session username
          $_SESSION['trusted'] = (in_array($ip, $USERS[$user]['trusted']) or in_array("*", $USERS[$user]['trusted']));
          $_SESSION['pwdok'] = empty($USERS[$user]['reset']);
        }
      }
      else
      { // wrong username/email!
        $BLACKLIST[$ip][$ts] = $_POST['user'];	// IP blacklisting
        
        eventLog("AUTH", "Login attempt for inexistent user ". $_POST['user'] . ". IP count: " . count($BLACKLIST[$ip]));
        $msg = "Username/password mismatch. Attempt " . count($BLACKLIST[$ip]);
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
  
  //if (empty($module) and !in_array($ip, $USERS[$user]['trusted']))  //isset($_SESSION('untrusted'))
  if (!$_SESSION['trusted'] 
        and !isset($module)
        and isset($_SESSION['username'])
     )
  {
    // if no trustcode is given (POST): create one and show form
    if (empty($_POST['trustcode']))
    {
      $_SESSION['trustcode'] = sprintf("%04d", mt_rand(0 , 9999));
      mail( $USERS[$user]['email'], 
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
        array_push($USERS[$user]['trusted'], $ip);
        unset($module, $msg);       // let $module be set later
        $BLACKLIST[$ip] = array();  //reset blacklist
      }
      else 
      { //incorrect
        $BLACKLIST[$ip][$ts] = $user . ": wrong trustcode";  // IP blacklisting
        $module = "trustform";
        $msg = "Try again!";
      }
    }   
  }

  
/* **************** 
    5 reset passwd
   **************** */
    
  //if (empty($module) and !empty($USERS[$user]['reset']))
  if (!$_SESSION['pwdok']         
        and !isset($module)
        and isset($_SESSION['username'])
     )
  {
    //reset pw form or action
    if (!empty($_POST['oldpwd']) and !empty($_POST['newpwd']) and !empty($_POST['verify']))
    { //password set action
      if (!password_verify($_POST['oldpwd'], $USERS[$user]['hash']))
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
        $USERS[$user]['hash'] = password_hash($_POST['newpwd'], PASSWORD_DEFAULT);
        $USERS[$user]['date'] = $ts;
        $USERS[$user]['reset'] = false;
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
  if (!file_put_contents(USERS_FILE, json_encode($USERS, JSON_PRETTY_PRINT))) 
    eventLog("WARNING", "Could not write to ". USERS_FILE);
  if (!file_put_contents(BLACKLIST_FILE, json_encode($BLACKLIST, JSON_PRETTY_PRINT))) 
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
    $user = $USERS[$is_logged_in];
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
  if ($module != "empty") include(ENTROPY_PATH . "inc/auth_" . $module . ".inc.php");
  else                    if (isset($msg)) echo $msg;
  include(FOOTER_FILE);
  
?>
