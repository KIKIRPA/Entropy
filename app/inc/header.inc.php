<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}


/*
    This inc requires:
        $htmlHeaderStyles
        $htmlHeaderScripts

        $LIBS
        $isLoggedIn
        $user
        $showLib
        $showMod

    This inc creates:
        $navMenuLibs
        $isHiddenLib
        $navMenuMods
        $navMenuTitle
        $navMenuSubtitle
        $navMenuLogoBox
*/

// TODO temporary create $showLib and $showMod if not already set
if (!isset($showLib)) $showLib = null;
if (!isset($showMod)) $showMod = null;


// initialize variables to initial values
$navMenuLibs = array();
$isHiddenLib = false;
$navMenuMods = array();
$navMenuTitle = APP_NAME;
$navMenuSubtitle = APP_CATCHPHRASE;
$navMenuLogoBox = false;

// list accessible libraries
foreach (array_keys($LIBS) as $lib) {
    if (strtoupper($lib) != "_START") {
        // add libraries that are either public, or if logged in, to which this user has view-access
        if (    ( strtolower($LIBS[$lib]["view"]) == "public" ) 
             or ( $isLoggedIn and calcPermLib($user["permissions"], "list", $lib))
           ) {
            $navMenuLibs[$lib] = $LIBS[$lib]["navmenucaption"];
        }
    }
}

// if a $showLib is present: (1) check if hidden, (2) list submenu items for that library
if ($showLib) {
    if ($showLib != "_START") {
        // are we accessing a hidden library?
        if (!isset($navMenuLibs[$showLib])) {
            $isHiddenLib = $showLib;
            $navMenuLibs[$showLib] = $LIBS[$showLib]["navmenucaption"];
        }

        if ($isLoggedIn) {
            // list library modules with non-false "navMenuShow"
            foreach ($MODULES["lib"] as $id => $value) {
                if ($value["navMenuShow"]) {
                    $navMenuMods[$id] = $value["caption"];
                }
            }
            // fetch permissions for $showLib
            $perm = calcPermMod($user["permissions"], $showLib);
            if (is_array($perm)) {
                $perm = array_flip($perm);  //flips keys and values (mods are now keys)
                $navMenuMods = array_intersect_key($navMenuMods, $perm);
            } elseif (!$perm) {
                $navMenuMods = array();     //no permissions: empty menu!
            }          
        }
    }
}

// if an $showMod is an adm mod
if ($showMod and $isLoggedIn) {
    if (isset($MODULES["adm"][$showMod])) {
        // list admin modules with non-false "navMenuShow"
        foreach ($MODULES["adm"] as $id => $value) {
            if ($value["navMenuShow"]) {
                $navMenuMods[$id] = $value["caption"];
            }
        }
        // fetch permissions
        $perm = calcPermMod($user["permissions"]);
        if (is_array($perm)) {
            $perm = array_flip($perm);  //flips keys and values (mods are now keys)
            $navMenuMods = array_intersect_key($navMenuMods, $perm);
        } elseif (!$perm) {
            $navMenuMods = array();     //no permissions: empty menu!
        }
    }
}


// title, subtitle and logobox
if ($showLib) {
    if (isset($LIBS[$showLib]["name"])) {
        $navMenuTitle = $LIBS[$showLib]["name"];
    }
    if (isset($LIBS[$showLib]["catchphrase"])) {
        $navMenuSubtitle = $LIBS[$showLib]["catchphrase"];
    }
    if (isset($LIBS[$showLib]["logobox"])) {
        $navMenuLogoBox = $LIBS[$showLib]["logobox"];
    }
}

// cleanup variables
unset($lib, $id, $value, $perm);

  
  
  /* *****************
      the HTML header
     ****************** */
  
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">  
    <head>
        <title><?= APP_NAME ?></title>
        <meta charset="utf-8">		
        <meta http-equiv="Window-target" content="_top">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="keywords" content="<?= APP_KEYWORDS ?>">
        <link rel="shortcut icon" href="<?= APP_ICON ?>">
<?php foreach ($htmlHeaderStyles as $value): ?>
        <link rel="stylesheet" type="text/css" href="<?= $value ?>">
<?php endforeach; ?>
<?php foreach ($htmlHeaderScripts as $value): ?>
        <script type='text/javascript' src="<?= $value ?>"></script>
<?php endforeach; ?>
    </head>
  
    <body>
        <nav class="navbar <?= bulmaColorModifier(NAVBAR_COLOR, $COLORS, "white") ?>">
            <div class="container">
                <div class="navbar-brand">
                    <a class="navbar-item" href=".\">
                        <img src="<?= APP_LOGO ?>" alt="<?= APP_NAME?>" height="28">
                    </a>
                    <div class="navbar-burger burger" data-target="navMenu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            
                <div id="navMenu" class="navbar-menu">
                    <div class="navbar-start">
<?php                 foreach ($navMenuLibs as $id => $caption): ?>
                        <a class="navbar-item<?= ($showLib == $id) ? " is-active\"" : "" ?>" href="./index.php?lib=<?= $id ?>">
                            <?= $isHiddenLib == $id ? "<span class=\"icon\"><i class=\"fa fa-eye-slash\"></i></span>" : "" ?><?= $caption . "\n" ?>
                        </a>
<?php                 endforeach; ?>
                    </div>
<?php             if (IS_HTTPS and !IS_BLACKLISTED): ?>
                    <div class="navbar-end">
<?php                 if ($isLoggedIn): ?>
                        <div class="navbar-item">
                            <div class="field has-addons">
                                <p class="control">
                                    <a class="button <?= bulmaColorModifier(NAVBAR_COLOR, $COLORS, "white") ?> is-inverted is-outlined is-static"><?= $isLoggedIn ?></a>
                                </p>
                                <p class="control">
                                    <a class="button <?= bulmaColorModifier(NAVBAR_COLOR, $COLORS, "white") ?> is-inverted" href="./tools.php?mod=console">
                                        <span class="icon">
                                            <i class="fa fa-cog" aria-hidden="true"></i>
                                        </span>
                                    </a>
                                </p>
                                <p class="control">
                                    <a class="button <?= bulmaColorModifier(NAVBAR_COLOR, $COLORS, "white") ?> is-inverted" href="./auth.php?logout">
                                        <span class="icon">
                                            <i class="fa fa-sign-out" aria-hidden="true"></i>
                                        </span>
                                    </a>
                                </p>
                            </div>
                        </div>
<?php                 else: ?>
                        <div class="navbar-item">
                            <p class="control">
                                <a class="button <?= bulmaColorModifier(NAVBAR_COLOR, $COLORS, "white") ?> is-inverted" href="./auth.php">
                                    <span class="icon">
                                        <i class="fa fa-sign-in" aria-hidden="true"></i>
                                    </span>
                                    <span>Log in</span>
                                </a>
                            </p>
                        </div>
<?php                 endif; ?>
                    </div>
<?php             endif; ?>
                </div>
            </div>
        </nav>

        <section class="hero <?= bulmaColorModifier($showLib ? $LIBS[$showLib]["color"] : DEFAULT_COLOR, $COLORS, DEFAULT_COLOR) ?>">
            <div class="hero-body">
                <div class="container">

                    <div class="columns is-desktop is-vcentered">
                        <div class="column">
                            <h1 class="title"><?= $navMenuTitle ?></h1>
                            <h2 class="subtitle"><?= $navMenuSubtitle ?></h2>
                        </div>
<?php                 if ($navMenuLogoBox): ?>
                        <div class="column is-narrow">
                            <div class="content is-small has-text-left-touch has-text-right-desktop">
                                <?= $navMenuLogoBox ?>
                            </div>
                        </div>
<?php                 endif; ?>
                    </div>
                </div>
            </div>
<?php     if (!empty($navMenuMods)): ?>
            <div class="hero-foot">
                <nav class="tabs is-boxed">
                    <div class="container">
                        <ul>
<?php                     foreach ($navMenuMods as $id => $caption): ?>
                            <li<?= ($showMod == $id) ? " class=\"is-active\"" : "" ?>>
                                <a href="./<?= ($id == "list") ? "index.php?" : "tools.php?mod=".$id."&" ?><?= $showLib ? "lib=". $showLib : "" ?>">
                                    <?= $caption ?>
                                </a>
                            </li>
<?php                     endforeach; ?>
                        </ul>
                    </div>
                </nav>
            </div>
<?php     endif; ?>
        </section>
         
<?php 
if ($isExpired) {
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

unset($navMenuLibs, $navMenuMods, $isHiddenLib);
?>
