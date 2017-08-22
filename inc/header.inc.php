<?php
  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }

  if ($is_logged_in)
  { // menu items to show = those modules with a non-false "menu"
    $menuitems_lib = array();
    $menuitems_adm = array();
    
    foreach ($MODULES["lib"] as $id => $value) if ($value["menu"]) $menuitems_lib[$id] = $value["caption"];
    foreach ($MODULES["adm"] as $id => $value) if ($value["menu"]) $menuitems_adm[$id] = $value["caption"];
    
    /* TODO: the new modules.json has extra features that need to be implemented in the menu: the "public" variable
             decides what modules should be available to public and hidden libraries for those that are not logged in
             
             foreach lib:
              - if logged in: those modules that are (1) marked public AND library is public/hidden
                                                  OR (2) to which a user has been granted access
              - if not logged in: those modules that are marked public AND library is public/hidden
             for adm: 
              - if not logged in: those modules marked public
              - if logged in: those modules marked public OR to which a user has been granted access
              
             if for a particular lib or adm there is only a single mod to show: don't draw the submenu
             
             the [adm][auth] module may have to be an exception: when logged in, it should have the caption "log out" 
             and vice versa. 
      */
  }
  
  
  /* *****************
      the HTML header
     ****************** */
  
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">    
  <head>
    <title><?php echo $htmltitle; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>		
    <meta http-equiv="Window-target" content="_top" />
    <meta name="keywords" content="<?php echo $htmlkeywords; ?>"/>
    <link rel="shortcut icon" href="./img/entropy.png" type="image/x-icon" />
    <link rel="stylesheet" type='text/css' href='<?php echo CSS_FA; ?>'>
    <link rel="stylesheet" type='text/css' href='<?php echo CSS_BULMA; ?>'>
    <link rel='stylesheet' type='text/css' href='./css/general.css'>
    <link rel='stylesheet' type='text/css' href='./css/navigation.css'>
    <link rel='stylesheet' type='text/css' href='./css/jquery.notifyBar.css'>
    <?php echo $style; ?>
    <script type='text/javascript' src='<?php echo JS_JQUERY; ?>'></script>
    <script type='text/javascript' src='./js/jquery.notifyBar.js' async></script>
    <?php echo $scripts; ?>
  </head>
  
  <body>
    <div class="wrapper">
      <div class="header">
        <div id="logo">
          <img src="./img/entropy.png" title="Entropy" alt="Entropy">
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
  if (isset($LIBS["_landingpage"]))
    if (strtolower($LIBS["_landingpage"]["view"]) == "public")
      echo "          <li>\n"
         . "            <a href='./'>" . $LIBS["_landingpage"]["menu"] . "</a>\n"
         . "          </li>\n";
  
  // lib menu entries
  foreach ($LIBS as $libid => $lib)
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
          echo "          <li><a href=\"./index.php?lib=" . $libid . "\">" . $lib["menu"] . "&nbsp<img src='./img/freecons/70_white.png' height='10'></a></li>\n";
      }
      elseif (is_array($perm))
      {
        if     (strtolower($lib["view"]) == "hidden") $note = "&nbsp<img src='./img/freecons/70_white.png' height='10'>";
        elseif (strtolower($lib["view"]) == "public") $note = "";
        else                              $note = "&nbsp<img src='./img/freecons/32_white.png' height='10'>";
        
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
      
      echo "          <li><a href=\"#\"><img src='./img/freecons/71_white.png' height='14' width='14'>&nbsp;" . $_SESSION['username'] . "</a>\n";
      echo "            <ul>\n";
      foreach ($perm as $item)
        echo "              <li><a href=\"./tools.php?mod=" . $item . "\">" . $menuitems_adm[$item] . "</a></li>\n";
      echo "              <li><a href=\"./auth.php?logout\">Log out</a></li>\n";
      echo "            </ul>\n";
      echo "          </li>\n";
    }
    else
      echo "          <li><a href=\"./auth.php\"><img src='./img/freecons/71_white.png' height='14' width='14'></a></li>\n";
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
           
