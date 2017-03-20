<?php
  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }

  // installation path
  const ENTROPY_PATH   = "../"; 

  // webapp properties  
  const APP_SHORT      = "Entropy";
  const APP_LONG       = "Entropy repository for analytical data";
  const APP_KEYWORDS   = "";
  const HEADER_FILE    = ENTROPY_PATH . "inc/header.inc.php";
  const FOOTER_FILE    = ENTROPY_PATH . "inc/footer.inc.php";
  const MAIL_ADMIN     = "wim.fremout@kikirpa.be";
  
  // json configuration files
  const CONFIG_PATH    = ENTROPY_PATH . "data/config/";
  const USERS_FILE     = CONFIG_PATH . "users.json";
  const BLACKLIST_FILE = CONFIG_PATH . "blacklist.json";
  const MODULES_FILE   = CONFIG_PATH . "modules.json";
  const DATATYPES_FILE = CONFIG_PATH . "datatypes.json";

  // repository storage
  const LIB_PATH       = ENTROPY_PATH . "data/libs/";
  const LIB_FILE       = LIB_PATH . "libraries.json";  

  // log files
  const LOG_PATH       = ENTROPY_PATH . "data/logs/";
  const LOG_DL_FILE    = LOG_PATH . "download.csv";
  const LOG_EV_FILE    = LOG_PATH . "event.csv";  
  
  // session properties
  const EXPIRE         = 7200;                         //keep session opened for xxx seconds after last activity
  const MAXTRIES_PW    = 3;                            //# of consecutive tries for a given username: disable account
  const MAXTRIES_IP    = 8;                            //# of tries within a session: blacklist
  
  // cookie properties
  const COOKIE_NAME    = "rememberme";                  //cookie name.  Warning: changing this will void all existing cookies! 
  const COOKIE_EXPIRE  = 31536000;                      //60*60*24*365.  use time() + COOKIE_EXPIRE
  const CRYPT_KEY      = "Tqh1QXGc";                    //key to encode "dl" and "cookie".  Warning: changing this will void all existing cookies!

  // mailhide properties
  const MAILHIDE_PUB   = '01vRWE2RFb_jk2EUoILaLQTg==';  //get your own mailhide API-key on https://www.google.be/recaptcha/mailhide/apikey
  const MAILHIDE_PRIV  = 'b54d4997580efd912db83dfc4a7a1c64';
  
?>
