#!/usr/bin/env php

<?php

  //----------------------//
  //   DEFAULT CONFIG     //
  //----------------------//

  // default values
  $cfg = Array( "forceinstall"   => False,
                "privpath"       => "/var/www/entropy/",
                "pubpath"        => "/var/www/entropy/public_html/",
                "htgroup"        => "www-data",
                "app_short"      => "Entropy",
                "app_long"       => "Entropy repository for analytical data",
                "twopass_enable" => False,
                "admin_login"    => "admin",
                "admin_name"     => "Administrator user",
                "admin_passwd"   => "entropy"
              );

  // questions for installation
  $qinstall = Array( "forceinstall" => "Force a clean install, even if a previous installation is found. This removes all data and settings.",
                     "privpath" => "Main installation path outside webroot (where files will be stored that should remain inaccessible from the web).",
                     "pubpath"  => "Webroot path (accessible for the web server).",
                     "htgroup"  => "Group name of the Web server"
                   );

  // questions for config files 
  $qconfig = Array( "app_short"      => "Short website name.",
                    "app_long"       => "Long, descriptive website name.",
                    "app_keywords"   => "Website keywords used to improve indexing by seach engines (eg google). Separate keywords with commas.",
                    "mail_admin"     => "E-mail address of the system administrator.",
                    "mailhide_pub"   => "Mailhide is a service that protects e-mail addresses on a website from spam. The addresses are obfuscated until a (human) visitor solves a reCAPTCHA. If you want to use this feature in Entropy, get your public and private API keys on https://www.google.com/recaptcha/mailhide/apikey. Fill in the public key here.",
                    "mailhide_priv"  => "Mailhide private key",
                    "twopass_enable" => "Enable twopass verification for unknown IPs. Please make sure to have a working sendmail configuration.",
                    "admin_name"     => "Full name for the administrator user.",
                    "admin_inst"     => "Institution or department of the the administrator user.",
                    "admin_email"    => "E-mail address of the administrator user.",
                    "admin_login"    => "Username (login name) for the administrator user.",
                    "admin_passwd"   => "Passphrase or password for the administrator user."
                  );

  // used for type evaluation and casting. possible values: boolean, integer, string (default), e-mail, path
  $types = Array( "forceinstall"   => "boolean",
                  "privpath"       => "path",
                  "pubpath"        => "path",
                  "mail_admin"     => "e-mail",
                  "twopass_enable" => "boolean",
                  "expire"         => "integer",
                  "maxtries_pw"    => "integer",
                  "maxtries_ip"    => "integer",
                  "cookie_expire"  => "integer",
                  "admin_email"    => "e-mail",
                  "inc_path"       => "path",
                  "config_path"    => "path",
                  "lib_path"       => "path",
                  "log_path"       => "path"
                );


  //--------------------------//
  //   COMMAND LINE OPTIONS   //
  //--------------------------//

  $short = "huidc:";
  $long  = Array("help", "update", "install", "defaults", "config:");
  $options = getopt($short, $long);

  echo "ENTROPY installation and update script\n";
  if (isset($options["h"]) or isset($options["help"]))
  {
    echo "\nUsage: setup.php [options]\n"
       . "Options:\n"
       . "  -u       --update       Update Entropy. Preserves configuration files and data. (default)\n"
       . "  -i       --install      Force clean install. REMOVES/RESETS CONFIGURATION FILES AND DATA!\n"
       . "  -d       --defaults     Non-interactive mode; use the default values.\n"
       . "  -c FILE  --config FILE  Use configuration file (JSON format).\n"
       . "  -h       --help         Shows this help message.\n\n";
    exit(0);
  }

  // invalid option combinations
  if (     (isset($options["u"]) or isset($options["update"])) 
       and (isset($options["i"]) or isset($options["install"])) )
  {
    echo "\nERROR: contradicting options: clean install vs update only. Aborting...\n";
    exit(1);
  }

  // config file
  if (isset($options["c"]))      $cfgfile = $options["c"];
  if (isset($options["config"])) $cfgfile = $options["config"];

  if (isset($cfgfile))
  {
    // read file
    if (file_exists($cfgfile))
    {
      $cfgfile = file_get_contents($cfgfile);
      $cfgfile = json_decode($cfgfile, true);
    }
    else
    {
      echo "\nERROR: configuration file not found. Aborting...\n";
      exit(3);
    }
    
    // valid json?
    if (empty($cfgfile))
    {
      echo "\nERROR: invalid or empty configuration file. Aborting...\n";
      exit(4);
    }

    // update $cfg with the values in $cfgfile
    $cfg = array_change_key_case($cfg);
    $cfg = array_replace($cfg, $cfgfile);
  }

  // evaluate $cfg values
  foreach ($cfg as $id => $value)
  {
    list($eval, $value) = cfgeval($id, $value);

    if ($eval)
      $cfg[$id] = $value; //corrected value
    else
    {
      echo "\nERROR: configuration item " . $id . " not a valid " . gettype2($id) . "\n";
      exit(5);
    }
  }
  
  // override $cfg (and $cfgfile) when -i or -u are given, or ask if in interactive mode
  if     (isset($options["u"]) or isset($options["update"]))  $cfg["forceinstall"] = False;
  elseif (isset($options["i"]) or isset($options["install"])) $cfg["forceinstall"] = True;

  // interactive mode
  if ( !isset($options["d"]) and !isset($options["defaults"]) )
  {    
    foreach ($qinstall as $id => $value)
    {
      // if update/install options specified on command line, do'nt ask again
      if ( !($id === "forceinstall" and (isset($options["u"]) or isset($options["update"]) or isset($options["i"]) or isset($options["install"]))) )
      {
        echo "\n" . $value;
        echo "\nEnter value or accept default [" . (isset($cfg[$id]) ? $cfg[$id] : "") . "]: \n";
        
        while (1)
        {
          $response = trim(fgets(STDIN));
          if (!empty($response))
          {
            list($eval, $response) = cfgeval($id, $response);
            if ($eval)
            {
              $cfg[$id] = trim($response);
              break;
            }
            else
              echo "Invalid response; please answer with a " . gettype2($id) . ":\n";
          }
          else break;
        }
      }
    }
  }


  //----------------------//
  //   INSTALL / UPDATE   //
  //----------------------//


  // determine if we do a clean install or an update
  if (!file_exists($cfg["privpath"] . "entropy.conf.php") or $cfg["forceinstall"])
  {
    $cleaninstall = True;
    echo "Clean installation...\n\n";
  }
  else
  {
    $cleaninstall = False;
    echo "Update...\n\n";
  }

  // get current username
  $currentUser = posix_getpwuid(posix_geteuid());
  $currentUser = $currentUser['name'];

  // common tasks for both clean install and update
  echo "Copying files.\n\n";
  if (!file_exists($cfg["privpath"])) 
  {
    mkdir($cfg["privpath"], 0750, true);
    chgrp($cfg["privpath"], $cfg["htgroup"]);
  }
  if (!file_exists($cfg["pubpath"]))
  {
    mkdir($cfg["pubpath"], 0750, true);
    chgrp($cfg["pubpath"], $cfg["htgroup"]);
  }

  rcopy("./inc/",                  $cfg["privpath"] . "inc/",      $cfg["htgroup"]);
  rcopy("./LICENSE",               $cfg["privpath"] . "LICENSE",   $cfg["htgroup"]);
  rcopy("./README.md",             $cfg["privpath"] . "README.md", $cfg["htgroup"]);
  
  rcopy("./public_html/css/",      $cfg["pubpath"] . "css/",       $cfg["htgroup"]);
  rcopy("./public_html/img/",      $cfg["pubpath"] . "img/",       $cfg["htgroup"]);
  rcopy("./public_html/js/",       $cfg["pubpath"] . "js/",        $cfg["htgroup"]);
  rcopy("./public_html/auth.php",  $cfg["pubpath"] . "auth.php",   $cfg["htgroup"]);
  rcopy("./public_html/tools.php", $cfg["pubpath"] . "tools.php",  $cfg["htgroup"]);
  rcopy("./public_html/index.php", $cfg["pubpath"] . "index.php",  $cfg["htgroup"]);

  // clean install only
  if ($cleaninstall)
  {
    // copy data folder (writable for htgroup)
    rcopy("./data/", $cfg["privpath"] . "data/", $cfg["htgroup"], True);

    // interactive mode: ask config file questions
    if ( !isset($options["d"]) and !isset($options["defaults"]) )
    {    
      foreach ($qconfig as $id => $value)
      {
        echo "\n" . $value;
        echo "\nEnter value or accept default [" . (isset($cfg[$id]) ? $cfg[$id] : "") . "]: \n";

        while (1)
        {
          $response = trim(fgets(STDIN));
          if (!empty($response))
          {
            list($eval, $response) = cfgeval($id, $response);
            if ($eval)
            {
              $cfg[$id] = trim($response);
              break;
            }
            else
              echo "Invalid response; please answer with a " . gettype2($id) . ":\n";
          }
          else break;
        }
      }
    }

    // build entropy.conf.php
    echo "Build configuration file: entropy.conf.php\n";
    $in = fopen("./entropy.conf.php", "r");
    $out = fopen($cfg["privpath"] . "entropy.conf.php", "w");
    mkconf($in, $out, $cfg);

    // build install.conf.php
    echo "Build configuration file: install.conf.php\n";
    $in = fopen("./public_html/install.conf.php", "r");
    $out = fopen($cfg["pubpath"] . "install.conf.php", "w");
    mkconf($in, $out, $cfg);

    // build users.json
    echo "Build configuration file: users.json\n";
    if (!isset($cfg["users_file"]) and !isset($cfg["config_path"]))
    { 
      $usercfg = Array( $cfg["admin_login"] => Array( "name"        => $cfg["admin_name"],
                                                      "institution" => (isset($cfg["admin_inst"])  ? $cfg["admin_inst"]  : ""),
                                                      "email"       => (isset($cfg["admin_email"]) ? $cfg["admin_email"] : ""),
                                                      "hash"        => password_hash($cfg["admin_passwd"], PASSWORD_DEFAULT),
                                                      "date"        => "",
                                                      "reset"       => ($cfg["admin_passwd"] == "entropy" ? True : False),
                                                      "tries"       => Array(),
                                                      "trusted"     => Array(),
                                                      "permissions" => Array("admin" => True),
                                                      "lastlogin"   => ""
                                                    )
                      );
      $usercfg = json_encode($usercfg, JSON_PRETTY_PRINT);
      file_put_contents($cfg["privpath"] . "data/config/users.json" , $usercfg);
    }
    else
      echo "WARNING: cannot make the users.json file if the USERS_FILE or CONFIG_PATH constants were changed from default values!";
  }

  echo "\nFinished.\n\n";





  //------------------------//
  //   INTERNAL FUNCTIONS   //
  //------------------------//

  // removes files and non-empty directories
  function rrmdir($dir)
  {
    if (is_dir($dir))
    {
      $files = scandir($dir);
      foreach ($files as $file)
        if ($file != "." && $file != "..")
          rrmdir("$dir/$file");
      rmdir($dir);
    }
    else
      if (file_exists($dir))
        unlink($dir);
  }

  // copies files and non-empty directories
  function rcopy($src, $dst, $group, $writable = False)
  {
    if (file_exists($dst))
      rrmdir($dst);
    if (is_dir($src))
    {
      mkdir($dst);
      chmod($dst, ($writable ? 0770 : 0750));        
      chgrp($dst, $group);
      $files = scandir($src);
      foreach ($files as $file)
        if ($file != "." && $file != "..")
          rcopy("$src/$file", "$dst/$file", $group, $writable);
    }
    else
      if (file_exists($src))
      {
        copy($src, $dst);
        chmod($dst, ($writable ? 0660 : 0640));
        chgrp($dst, $group);
      }
  }

  function mkconf($in, $out, $cfg)
  {
    if ($in) 
    {
      while (($line = fgets($in)) !== false) 
      {
        $lineparts = explode("//", trim($line), 2);  // only consider the part of the line before eventual comments

        if (substr(strtolower($lineparts[0]), 0, 5) == 'const')
        {
          $static = explode('=', $lineparts[0], 2)[0];  // only consider the part before "="
          $static = trim(str_replace("CONST", "", strtoupper($static)));
          
          foreach ($cfg as $item => $value)
          {
            if ($static == strtoupper($item))   // found cfg-item in line -> replace it
            {
               $lineparts[0] = '  const ' . strtoupper($item) . ' = ' . putvalue($item, $value) . '; ';
              $line = implode(" //", $lineparts) . "\n";
              echo "  - set " . strtoupper($item) . " -> " . $value . "\n";
            }
          }
        }
        
        // write (original or updated) line to config file
        fputs($out, $line);
      }

      fclose($in);
      fclose($out);
    }
  }


  function cfgeval($item, $value)
  {
    switch (gettype2($item))
    {
      case "boolean":
        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        $eval = True;  //True, "true", "on", "yes", "1" are True, all others are False, thus evaluation always succeeds by design
        break;
      case "integer":
        $value = filter_var($value, FILTER_VALIDATE_INT);
        $eval = is_int($value);
        break;
      case "e-mail":
        $value = filter_var($value, FILTER_VALIDATE_EMAIL);
        $eval = is_string($value);
        break;
      case "path":
        $value = (string) $value;
        if (strlen($value) > 0)
          if (substr($value, -1) != "/") 
            $value .= "/";
        $eval = (bool) $value;
        break;
      case "string":
      default:
        $value = (string) $value;
        $eval = True; //accept empty strings?
        break;
    }

    return Array($eval, $value);
  }


  function gettype2($item)
  {
    // get expected variable type from global $types, or set to default (=string)
    global $types;
    return (isset($types[$item]) ? $types[$item] : "string");
  }


  function putvalue($item, $value)
  {
    switch (gettype2($item))
    {
      case "boolean":
        return ($value ? "True" : "False");
        break;
      case "integer":
        return (string)$value;
        break;
      default:
        return '"' . $value . '"';
        break;
    }
  }

?>