<?php
  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }

  // paths
  const INC_PATH       = PRIVPATH . "inc/";
  const CONFIG_PATH    = PRIVPATH . "data/config/";
  const LIB_PATH       = PRIVPATH . "data/libs/";
  const LOG_PATH       = PRIVPATH . "data/logs/";

  // webapp properties  
  const APP_SHORT      = "Entropy";
  const APP_LONG       = "Entropy repository for analytical data";
  const APP_KEYWORDS   = "";
  const HEADER_FILE    = INC_PATH . "header.inc.php";
  const FOOTER_FILE    = INC_PATH . "footer.inc.php";
  const MAIL_ADMIN     = "";

  
  // json configuration files
  const USERS_FILE     = CONFIG_PATH . "users.json";
  const BLACKLIST_FILE = CONFIG_PATH . "blacklist.json";
  const MODULES_FILE   = CONFIG_PATH . "modules.json";
  const DATATYPES_FILE = CONFIG_PATH . "datatypes.json";

  // repository storage
  const LIB_FILE       = LIB_PATH . "libraries.json";  

  // log files
  const LOG_DL_FILE    = LOG_PATH . "download.csv";
  const LOG_EV_FILE    = LOG_PATH . "event.csv";  
  
  // session properties
  const EXPIRE         = 7200;           //keep session opened for xxx seconds after last activity
  const MAXTRIES_PW    = 3;              //# of consecutive tries for a given username: disable account
  const MAXTRIES_IP    = 8;              //# of tries within a session: blacklist
  const TWOPASS_ENABLE = False;          //enable the use of twopass verification (e-mail trustcodes to unkown IPs)
  
  // cookie properties
  const LOG_DL         = True;           //log downloads (if true, downloads require name/institution/e-mail via form, downloadcookie or login)
  const COOKIE_NAME    = "downloadid";   //cookie name.  Warning: changing this will void all existing cookies! 
  const COOKIE_EXPIRE  = 31536000;       //60*60*24*365.  use time() + COOKIE_EXPIRE
  const CRYPT_KEY      = "Tqh1QXGc";     //key to encode "dl" and "cookie".  Warning: changing this will void all existing cookies!

  // mailhide properties
  const MAILHIDE_PUB   = '';             //get your own mailhide API-key on https://www.google.com/recaptcha/mailhide/apikey
  const MAILHIDE_PRIV  = '';
  
?>
