#!/usr/bin/env php

<?php

  //----------------------//
  //   DEFAULT CONFIG     //
  //----------------------//

  $cfg = Array( "update"        => "y",
                "privpath"      => "/var/www/entropy/",
                "pubpath"       => "/var/www/entropy/public_html/",
                "htgroup"       => "www-data",
                "mail_admin"    => "",
                "mailhide_pub"  => "",
                "mailhide_priv" => ""
              );

  $hlp = Array( "update"        => "If a previous installation is found, keep all data and settings, only update new source files.",
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

  $short = "uidc:";
  $long  = Array("update", "cleaninstall", "defaults", "config:");
  $options = getopt($short, $long);

  // invalid option combinations
  if (     (isset($options["u"]) or isset($options["update"])) 
       and (isset($options["i"]) or isset($options["cleaninstall"])) )
  {
    echo "\nERROR: contradicting options: clean install vs update only. Aborting...\n";
    exit(1);
  }

  if (     (isset($options["d"]) or isset($options["defaults"])) 
       and (isset($options["c"]) or isset($options["config"])) )
  {
    echo "\nERROR: contradicting options: use configuration file vs use default values. Aborting...\n";
    exit(2);
  }

  // config file
  if (isset($options["c"]))      $cfgfile = $options["c"];
  if (isset($options["config"])) $cfgfile = $options["config"];

  if (isset($cfgfile))
  {
    // read file
    if (file_exists($path))
    {
      $cfgfile = file_get_contents($path);
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
  if (isset($options["u"]) or isset($options["update"]))       $cfg["update"] = "y";
  if (isset($options["i"]) or isset($options["cleaninstall"])) $cfg["update"] = "n";

  // interactive mode
  if ( !isset($options["d"]) and !isset($options["defaults"]) and !isset($options["c"]) and !isset($options["config"]) )
  {
    foreach ($hlp as $id => $value)
    {
      echo $value;
      echo "Enter value or accept default [" . (isset($cfg[$id]) ? $cfg[$id] : "") . "]";
      $line = trim(fget(STDIN));
      if (!empty(line)) $cfg[$id] = trim($line);
    }
  }


  //----------------------//
  //   INSTALL / UPDATE   //
  //----------------------//

  // determine if we do a clean install or an update
  if (!file_exists($cfg["privpath"] . "entropy.conf.php") or strtolower($cfg["update"][0]) == "y")
    $cleaninstall = True;
  else
    $cleaninstall = False;


  // get current username
  $currentUser = posix_getpwuid(posix_geteuid());
  $currentUser = $currentUser['name'];


  // common tasks for both clean install and update
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
    $in = fopen("./entropy.conf.php", "r");
    $out = fopen($cfg["privpath"] . "entropy.conf.php", "w");
    mkconf($in, $out, $cfg);

    // build install.conf.php
    $in = fopen("./public_html/install.conf.php", "r");
    $out = fopen($cfg["pubpath"] . "install.conf.php", "w");
    mkconf($in, $out, $cfg);
  }





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
          rcopy("$src/$file", "$dst/$file");
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
        $lineparts = explode("//", $line, 1);  // only consider the part of the line before eventual comments

        if (strpos($lineparts[0], 'const') !== false)
        {
          $lineparts[0] = explode("=", $lineparts[0], 1);  // only consider the part before "="
          
          foreach ($cfg as $item => $value)
          {
            if (strpos($lineparts[0][0], strtoupper($item)))   // found cfg-item in line -> replace it
            {
              if (is_string($value)) $lineparts[0] = '  const ' . strtoupper($item) . ' = "' . $value . '"; ';
              else                   $lineparts[0] = '  const ' . strtoupper($item) . ' = ' . $value . '; ';
              $line = implode(" //", $lineparts);
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