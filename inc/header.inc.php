<?php
  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }

  if ($is_logged_in)
  { // menu items to show = those modules with a non-false description
    $menuitems_lib = array();
    $menuitems_adm = array();
    
    foreach ($modules_lib as $id => $value) if ($value[1]) $menuitems_lib[$id] = $value[0];
    foreach ($modules_adm as $id => $value) if ($value[1]) $menuitems_adm[$id] = $value[0];
  }
  
  
  /* *****************
      the HTML header
     ****************** */
  
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">    
  <head>
    <title><?php echo $htmltitle; ?></title>
    <!--meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" -->
    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7; IE=EmulateIE9"> 
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>		
    <meta http-equiv="Window-target" content="_top" />
    <meta name="keywords" content="<?php echo $htmlkeywords; ?>"/>
    <link rel="shortcut icon" href="./images/specliblogo.png" type="image/x-icon" />
    <link rel="stylesheet" type="text/css" href="./css/general.css">
    <link rel="stylesheet" type="text/css" href="./css/navigation.css">
    <?php echo $style; ?>
    <script type='text/javascript' charset='utf8' src='./javascript/jquery.min.js'></script>
    <?php echo $scripts; ?>
    <?php if ($is_expired)
          {
            echo "    <script type='text/javascript' charset='utf8' src='./javascript/jquery.notifyBar.js'></script>\n"
               . "    <link rel='stylesheet' type='text/css' href='./css/jquery.notifyBar.css'>\n";
          }  
    ?>
  </head>
  
  <body>
    <div class="wrapper">
      <div class="header">
        <div id="logo">
          <img src="./images/specliblogo.png" title="Entropy" alt="Entropy">
        </div>
        <div id="title">
          <h1><?php echo $pagetitle; ?></h1>
          <h2><?php echo $pagesubtitle; ?></h2>
        </div>
      </div>
    
      <nav id="menu">
        <ul> 
<?php        
  // landing page
  if (isset($libs["_landingpage"]))
    if (strtolower($libs["_landingpage"]["view"]) == "public")
      echo "          <li>\n"
         . "            <a href='./'>" . $libs["_landingpage"]["menu"] . "</a>\n"
         . "          </li>\n";
  
  // lib menu entries
  foreach ($libs as $libid => $lib)
    if (strtolower($libid) != "_landingpage")
    {
      $perm = false;
      
      if ($is_logged_in) 
      {
        $perm = calcPermMod($user["permissions"], $libid);
        
        if (is_array($perm)) $perm = array_intersect($perm, array_keys($menuitems_lib));
        elseif ($perm)       $perm = array_keys($menuitems_lib);
        // else: $perm==false => do nothing ($perm remains false)
      }
        
      if (!$perm)
      {
        if (strtolower($lib["view"]) == "public")
          echo "          <li><a href=\"./index.php?lib=" . $libid . "\">" . $lib["menu"] . "</a></li>\n";
        elseif ((strtolower($lib["view"]) == "hidden") and ($_REQUEST["lib"] == $libid))
          echo "          <li><a href=\"./index.php?lib=" . $libid . "\">" . $lib["menu"] . "&nbsp<img src='./images/freecons/70_white.png' height='10'></a></li>\n";
      }
      elseif (is_array($perm))
      {
        if     (strtolower($lib["view"]) == "hidden") $note = "&nbsp<img src='./images/freecons/70_white.png' height='10'>";
        elseif (strtolower($lib["view"]) == "public") $note = "";
        else                              $note = "&nbsp<img src='./images/freecons/32_white.png' height='10'>";
        
        if ((count($perm) == 1) and ($perm[0] == "view"))
          echo "          <li><a href=\"./index.php?lib=" . $libid . "\">" . $lib["menu"] . $note . "</a></li>\n";
        else 
        {
          echo "          <li><a href=\"#\">" . $lib["menu"] . $note . "</a>\n";
          echo "            <ul>\n";
          echo "              <li><a href=\"./index.php?lib=" . $libid . "\"> View</a></li>\n";
          foreach ($perm as $item)
          {
            ///////////////////////////////////////////////////
            // TODO REMARK tools.php?mod=view == index.php //
            ///////////////////////////////////////////////////
            if ($item != "view")
              echo "              <li><a href=\"./tools.php?mod=" . $item . "&lib=" . $libid . "\">" . $menuitems_lib[$item] . "</a></li>\n";
          }
          echo "            </ul>\n";
          echo "          </li>\n";
        }
      }
    }
  
  // admin login
  if (IS_HTTPS and !IS_BLACKLISTED)
  {
    if ($is_logged_in)
    {
      $perm = calcPermMod($user["permissions"]);
      
      if (is_array($perm)) $perm = array_intersect($perm, array_keys($menuitems_adm));
      elseif ($perm)       $perm = array_keys($menuitems_adm);
      // else: $perm==false => do nothing ($perm remains false)
      
      echo "          <li><a href=\"#\"><img src='./images/freecons/71_white.png' height='14' width='14'>&nbsp;" . $_SESSION['username'] . "</a>\n";
      echo "            <ul>\n";
      foreach ($perm as $item)
        echo "              <li><a href=\"./tools.php?mod=" . $item . "\">" . $menuitems_adm[$item] . "</a></li>\n";
      echo "              <li><a href=\"./auth.php?logout\">Log out</a></li>\n";
      echo "            </ul>\n";
      echo "          </li>\n";
    }
    else
      echo "          <li><a href=\"./auth.php\"><img src='./images/freecons/71_white.png' height='14' width='14'></a></li>\n";
  }
            
?>          
        </ul>
      </nav> 
      
<?php 
  if ($is_expired)
  {
    echo "      <script>\n"
        . "        $(function () {\n"
        . "          $.notifyBar({\n"
        . "            cssClass: \"warning\",\n"
        . "            html: \"Your login session has expired.\",\n"
        . "            closeOnClick: true,\n"
        . "            delay: 10000\n"
        . "          });\n"
        . "        });\n"
        . "      </script>\n";
  }
  
  unset($menuitems_lib, $menuitems_adm);
?>
      
      <div class ="main">
           
