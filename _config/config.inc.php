<?php
  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }
  
  const APP_SHORT      = "Entropy";
  const APP_LONG       = "Entropy repository for analytical data";
  const APP_KEYWORDS   = "";
  const HEADER_FILE    = "./inc/header.inc.php";
  const FOOTER_FILE    = "./inc/footer.inc.php";
  
  const USERS_FILE     = "./_config/users.json";
  const BLACKLIST_FILE = "./_config/blacklist.json";
  const FORMAT_FILE    = "./_config/specformat.json";
  
  const LIB_DIR        = "./libs/";
  const LIB_FILE       = "libraries.json";
  const LIB_DEFAULT    = "sop";                      // depreciated 
  
  const LOG_DIR        = "./logs/";
  const LOG_DL_FILE    = "download.csv";
  const LOG_EV_FILE    = "event.csv";
  const MAIL_ADMIN     = "wim.fremout@kikirpa.be";  
  
  const EXPIRE         = 7200;      //keep session opened for xxx seconds after last activity
  const MAXTRIES_PW    = 3;         //# of consecutive tries for a given username: disable account
  const MAXTRIES_IP    = 8;         //# of tries within a session: blacklist
  
  const COOKIE_NAME    = "rememberme";                //cookie name.  Warning: changing this will void all existing cookies! 
  const COOKIE_EXPIRE  = 31536000;                    //60*60*24*365.  use time() + COOKIE_EXPIRE
  const CRYPT_KEY      = "Tqh1QXGc";                  //key to encode "dl" and "cookie".  Warning: changing this will void all existing cookies!

  const MAILHIDE_PUB   = '01vRWE2RFb_jk2EUoILaLQTg==';  //
  const MAILHIDE_PRIV  = 'b54d4997580efd912db83dfc4a7a1c64';
  
  
  // lists of modules (per library and administrative);
  // if the value is false: will not be shown in the menu bar (but accessible from within another module)
  $modules_lib = array( "view"       => array("View library", true), 
                        "libedit"    => array("Edit library", true),
                        "libren"     => array("Rename library", false),
                        "libperm"    => array("Set library permissions", false),
                        "libdel"     => array("Delete library", false),
                        "import"     => array("Import data", true), 
                        "download"   => array("Download tool", false),
                        "compare"    => array("Compare tool", false), 
                        "peaksearch" => array("Peaksearch tool", false),
                        "dllog"      => array("View download log", false)
                      );
  $modules_adm = array( "libmgmt"    => array("Library management", true),
                        "libmk"      => array("Create library", false),
                        "usermgmt"   => array("User management", true),
                        "log"        => array("Log tool", false),
                        "config"     => array("Configuration", true), 
                        "specformat" => array("Spectrum format", false),
                        "user"       => array("My details", true),
                        "admin"      => array("Administration rights", false)
                      );
  
?>