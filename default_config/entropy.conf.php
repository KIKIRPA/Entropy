<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

// paths
const CONFIG_PATH    = PRIVPATH . "data/config/";
const LIB_PATH       = PRIVPATH . "data/libs/";
const LOG_PATH       = PRIVPATH . "data/logs/";

// webapp properties
const APP_NAME      = "Entropy";
const APP_CATCHPHRASE       = "A repository tailored for analytical data";
const APP_KEYWORDS   = "";
const APP_LOGO       = "./img/entropy_turquoise.png";
const APP_ICON       = "./img/favicon_turquoise.png";
const NAVBAR_COLOR   = "white";
const DEFAULT_COLOR  = "primary";
const HEADER_FILE    = PRIVPATH . 'inc/header.inc.php';
const FOOTER_FILE    = PRIVPATH . 'inc/footer.inc.php';
const FOOTER_BOX     = "<p><strong>Footer text box</strong></p><p>Link to privacy policy</p>";
const MAIL_ADMIN     = "";

// json configuration files
const USERS_FILE     = CONFIG_PATH . "users.json";
const BLACKLIST_FILE = CONFIG_PATH . "blacklist.json";
const MODULES_FILE   = CONFIG_PATH . "modules.json";
const DATATYPES_FILE = CONFIG_PATH . "datatypes.json";
const COLORS_FILE    = CONFIG_PATH . "colors.json";
const IMPORT_FILE    = CONFIG_PATH . "import.json";
const EXPORT_FILE    = CONFIG_PATH . "export.json";

// repository storage
const LIB_FILE       = LIB_PATH . "libraries.json";

// log files
const LOG_DL_FILE    = LOG_PATH . "download.csv";
const LOG_EV_FILE    = LOG_PATH . "event.csv";

// style and javascript sources
const CSS_FA         = "https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css";
const CSS_BULMA      = "https://cdnjs.cloudflare.com/ajax/libs/bulma/0.6.0/css/bulma.min.css";
const CSS_DT         = "https://cdn.datatables.net/v/dt/dt-1.10.15/fc-3.2.2/fh-3.1.2/r-2.1.1/datatables.min.css";
const CSS_DT_BULMA   = "https://raw.githubusercontent.com/JDilleen/datatables-bulma/master/css/dataTables.bulma.min.css";
const CSS_DYGRAPH    = "https://cdnjs.cloudflare.com/ajax/libs/dygraph/2.0.0/dygraph.min.css";
const JS_JQUERY      = "https://code.jquery.com/jquery-2.2.4.min.js";
const JS_DT          = "https://cdn.datatables.net/v/dt/dt-1.10.15/fc-3.2.2/fh-3.1.2/r-2.1.1/datatables.min.js";
const JS_DT_BULMA    = "https://raw.githubusercontent.com/JDilleen/datatables-bulma/master/js/dataTables.bulma.min.js";
const JS_DYGRAPH     = "https://cdnjs.cloudflare.com/ajax/libs/dygraph/2.0.0/dygraph.min.js";

// session properties
const EXPIRE         = 7200;           //keep session opened for xxx seconds after last activity
const MAXTRIES_PW    = 3;              //# of consecutive tries for a given username: disable account
const MAXTRIES_IP    = 8;              //# of tries within a session: blacklist
const TWOPASS_ENABLE = false;          //enable the use of twopass verification (e-mail trustcodes to unkown IPs)

// cookie properties
const LOG_DL         = true;           //log downloads (if true, downloads require name/institution/e-mail via form, downloadcookie or login)
const COOKIE_NAME    = "downloadid";   //cookie name.  Warning: changing this will void all existing cookies!
const COOKIE_EXPIRE  = 31536000;       //60*60*24*365.  use time() + COOKIE_EXPIRE
const CRYPT_KEY      = "Tqh1QXGc";     //key to encode "dl" and "cookie".  Warning: changing this will void all existing cookies!

// mailhide properties
const MAILHIDE_PUB   = '';             //get your own mailhide API-key on https://www.google.com/recaptcha/mailhide/apikey
const MAILHIDE_PRIV  = '';
