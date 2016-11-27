<?php
  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }
  
  const APP_SHORT    = "Entropy";
  const APP_LONG     = "Entropy repository for analytical data";
  const APP_KEYWORDS = "";
  const HEADER_FILE  = "./inc/header.inc.php";
  const FOOTER_FILE  = "./inc/footer.inc.php";
  
  const USERS_FILE     = "./data/config/users.json";
  const BLACKLIST_FILE = "./data/config/blacklist.json";
  const MODULES_FILE   = "./data/config/modules.json";
  const DATATYPES_FILE = "./data/config/datatypes.json";
  const LIB_FILE       = "./data/libs/libraries.json";  
  const LIB_DIR        = "./data/libs/";
  const LOG_DL_FILE    = "./data/logs/download.csv";
  const LOG_EV_FILE    = "./data/logs/event.csv";
  
  const MAIL_ADMIN = "wim.fremout@kikirpa.be";  
  
  const EXPIRE      = 7200;      //keep session opened for xxx seconds after last activity
  const MAXTRIES_PW = 3;         //# of consecutive tries for a given username: disable account
  const MAXTRIES_IP = 8;         //# of tries within a session: blacklist
  
  const COOKIE_NAME   = "rememberme";                //cookie name.  Warning: changing this will void all existing cookies! 
  const COOKIE_EXPIRE = 31536000;                    //60*60*24*365.  use time() + COOKIE_EXPIRE
  const CRYPT_KEY     = "Tqh1QXGc";                  //key to encode "dl" and "cookie".  Warning: changing this will void all existing cookies!

  const MAILHIDE_PUB  = '01vRWE2RFb_jk2EUoILaLQTg==';  //
  const MAILHIDE_PRIV = 'b54d4997580efd912db83dfc4a7a1c64';
  
?>
