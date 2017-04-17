#!/usr/bin/env php

<?php

  //----------------------//
  //   DEFAULT CONFIG     //
  //----------------------//

  $cfg = Array( "forceinstall"  => "n",
                "privpath"      => "/var/www/entropy/",
                "pubpath"       => "/var/www/entropy/public_html/",
                "htgroup"       => "www-data",
                "mail_admin"    => "",
                "mailhide_pub"  => "",
                "mailhide_priv" => ""
              );

  $hlp = Array( "forceinstall"  => "Force a clean install, even if a previous installation is found. This removes all data and settings.",
                "privpath"      => "Main installation path outside webroot (where files will be stored that should remain inaccessible from the web). Paths require a trailing slash '/'.",
                "pubpath"       => "Webroot path (accessible for the web server). Paths require a trailing slash '/'.",
                "htgroup"       => "Group name of the Web server",
                "mail_admin"    => "E-mail address of the system administrator",
                "mailhide_pub"  => "Mailhide is a service that protects e-mail addresses on a website from spam. The addresses are obfuscated until a (human) visitor solves a reCAPTCHA. If you want to use this feature in Entropy, get your public and private API keys on https://www.google.com/recaptcha/mailhide/apikey. Fill in the public key here.",
                "mailhide_priv" => "Mailhide private key"
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
    $cfg = array_replace($cfg, $cfgfile);
  }
  
  // override $cfg (and $cfgfile) when -i or -u are given
  if (isset($options["u"]) or isset($options["update"]))   $cfg["forceinstall"] = "n";
  if (isset($options["i"]) or isset($options["install"]))  $cfg["forceinstall"] = "y";

  // interactive mode
  if ( !isset($options["d"]) and !isset($options["defaults"]) )
  {
    foreach ($hlp as $id => $value)
    {
      echo "\n" . $value;
      echo "\nEnter value or accept default [" . (isset($cfg[$id]) ? $cfg[$id] : "") . "]: \n";
      $line = trim(fgets(STDIN));
      if (!empty($line)) $cfg[$id] = trim($line);
    }
  }


  //----------------------//
  //   INSTALL / UPDATE   //
  //----------------------//

  // determine if we do a clean install or an update
  if (!file_exists($cfg["privpath"] . "entropy.conf.php") or strtolower($cfg["forceinstall"][0]) == "y")
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
  rcopy("./inc/", $cfg["privpath"] . "inc/", $cfg["htgroup"]);
  rcopy("./LICENSE", $cfg["privpath"] . "LICENSE", $cfg["htgroup"]);
  rcopy("./README.md", $cfg["privpath"] . "README.md", $cfg["htgroup"]);
  rcopy("./public_html/", $cfg["pubpath"], $cfg["htgroup"]);

  // clean install only
  if ($cleaninstall)
  {
    // copy data folder (writable for htgroup)
    rcopy("./data/", $cfg["privpath"] . "data/", $cfg["htgroup"], True);

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
              if (is_string($value)) $lineparts[0] = '  const ' . strtoupper($item) . ' = "' . $value . '"; ';
              else                   $lineparts[0] = '  const ' . strtoupper($item) . ' = ' . $value . '; ';
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

?>