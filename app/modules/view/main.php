<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

/* ********
    HEADER
    ******** */

array_push($htmlHeaderStyles, CSS_DYGRAPH);
array_push($htmlHeaderScripts, JS_DYGRAPH);  
include(HEADER_FILE);


/* ***************
    main metadata
    *************** */

// data from the measurement json file
$idbox_head = array_shift($measurement);
$transaction = $measurement["_transaction"];
unset($measurement["_transaction"]);

// metadata can be saved on different levels, override lesser priority metadata
$meta = overrideMeta($data, $showDS);


/* ***********
    downloads
    *********** */

// 1. is downloading allowed? (for logged-in users or guests)
// 2. what can be downloaded (we don't need to do any efforts if nothing can be downloaded)
// 3. is download logging enabled
// 4. do we have downloader name, institution and email stored in either login or downloadcookie?
// 5. if no: include download form

// 1+2 $dl_ShowButtons boolean (show download box?) and $dl_List (array of buttons to show)
$dl_ShowButtons = false;
if ( (!$isLoggedIn and $MODULES["lib"]["download"]["public"])
     or ($isLoggedIn and calcPermLib($user["permissions"], "download", $showLib))) {
    $dl_List = array();

    // 1. convert from json data files
    //    TODO: integrate convert-framework (check if we are able to convert to $format, preferably replacing the switch by a function)
    foreach ($LIBS[$showLib]["allowformat"] as $format) {
        switch ($format) {
            case "dx":
            case "jdx":
                if (isset($meta["jcamptemplate"]) and file_exists(LIB_PATH . $showLib . "/templates/" . $meta["jcamptemplate"])) {
                    $dl_List["JCAMP-DX"] = encode("conv=" . $format);
                }
                break;
            case "ascii":
            case "txt":
                $dl_List["TXT"] = encode("conv=" . $format);
                break;
        }
    }

    // 2. binary uploaded files
    $prefix = LIB_PATH . $showLib . "/" . $transaction . "/" . $showID . (($showDS == 'default')?"":"__".$showDS);
    $binfiles = glob($prefix . "__*");  //by using "__*" we exclude the original (converted) data files, json data files and annotations
    foreach ($binfiles as $f) {
        // button caption = EXT (ORIG BIN FILENAME)
        $button = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        $button .= "(" . str_replace("_", " ", pathinfo(str_replace($prefix, '', $f), PATHINFO_FILENAME)) . ")";
        $dl_List[$button] = encode("bin=" . $f);
    }

    if ($dl_List != 0) {
        $dl_ShowButtons = true;
    }
}

// 3+4+5 $dl_ShowForm boolean (show download form)
if (LOG_DL) {
    if ($isLoggedIn) {
        $dl_ShowForm = false;
    } elseif (isset($_COOKIE[COOKIE_NAME])) {
        $cookie = verifycookie($_COOKIE[COOKIE_NAME]);               // in case of false data, this will output False
    if (is_array($cookie)) {
        $dl_ShowForm = !makecookie($cookie);
    }  // if not false: update cookie; makecookie always outputs TRUE, $dl_ShowForm needs to be FALSE
    else {
        $dl_ShowForm = removecookie();
    }        // remove invalid cookie; removecookie always outputs TRUE
    //TODO: invalid cookie notification!
    } else {
        $dl_ShowForm = true;
    }
} else {
    $dl_ShowForm = false;
}


/* ********
  viewer
  ******** */

$parenttype = datatypeParent($measurement["type"], $DATATYPES);
$viewer = $DATATYPES[$parenttype]["viewer"];
$units = datatypeUnits($parenttype, $DATATYPES, 'html', $data["dataset"][$showDS]["units"]);

if (isset($data["dataset"][$showDS]["anno"])) {
    if (is_array($data["dataset"][$showDS]["anno"])) {
        $anno = array();
        foreach ($data["dataset"][$showDS]["anno"] as $i => $a) {
            $anno[$i] = array();
            $anno[$i]["series"] = $idbox_head;
            $anno[$i] = array_merge($anno[$i], $a);
        }
    }
}



/* ***********
    HTML main
   *********** */

?>
        <div class='fullwidth'>
          <!-- ID box -->
          <div class='boxed' id='redbox'>
            <h3><?= $idbox_head ?></h3>
            <?php foreach ($measurement as $a): ?>
            <p><?= $a ?>
            <?php endforeach; ?>
          </div>

          <?php if (count($data["dataset"]) > 1): ?>
          <!-- dataset box -->
          <div class='boxed' id='greybox'>
            <h3>Datasets</h3>
            <?php foreach ($data["dataset"] as $dsid => $dsval): 
                      if ($dsid == $showDS): ?>
            <p><strong><?=  $showDS ?></strong>
            <?php     else: ?>
            <p><a href='<?= $_SERVER["PHP_SELF"]; ?>?lib=<?= $showLib ?>&id=<?= $showID ?>&ds=<?= $showDS ?>'><?= $dsid ?></a>
            <?php     endif;
                  endforeach; ?>
          </div>
          <?php endif; ?>

          <!-- viewer box -->
          <?php require_once(PRIVPATH . 'viewers/' .  $viewer . '/main.php'); ?>

          <?php if ($dl_ShowButtons):?>
          <!-- download box -->
          <div class='boxed'>
            <h3>Download</h3>
            <div style='text-align:center'>
              <?php foreach ($dl_List as $btncaption => $dl_Code):
                     if ($dl_ShowForm): ?>
              <a class="button is-primary modal-button" data-target="dlmodal" onclick="document.getElementById('dl').value = '<?= $dl_Code ?>';");>
                <span class="icon is-small"><i class="fa fa-download"></i></span>
                <span><?= $btncaption ?></span>
              </a>
              <?php   else: ?>
              <a class="button is-primary" href="<?= $_SERVER["PHP_SELF"] ?>?lib=<?= $showLib ?>&id=<?= $showID ?>&ds=<?= $showDS ?>&dl=<?= $dl_Code ?>">
                <span class="icon is-small"><i class="fa fa-download"></i></span>
                <span><?= $btncaption ?></span>
              </a>
              <?php   endif;
                    endforeach; ?>
            </div>
            <p><i>The complete <?= $LIBS[$showLib]["name"] ?> can be requested by email.</i>
            <p><i>By downloading this file you agree to the terms described in the license.</i>
          </div>
          <?php   endif;?>

          <!-- license box -->
          <div class='boxed'>
            <div style="width: 50%; float: left;"><h3>Licence</h3></div>
            <div style="float: right; padding: 12px;"><a rel="license" href="http://creativecommons.org/licenses/by-nc-nd/3.0/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-nc-nd/3.0/88x31.png" /></a></div>
            <?php
              if (isset($meta["contributor"])) {
                  $temp = $meta["contributor"];
                  if (is_array($temp)) {
                      if (isset($temp["analyst(s)"]) and isset($temp["institution"])) {
                          $lic = $temp["analyst(s)"]." from the ".$temp["institution"];
                      } elseif (isset($temp["analyst(s)"])) {
                          $lic = $temp["analyst(s)"];
                      } elseif (isset($temp["institution"])) {
                          $lic = $temp["institution"];
                      } else {
                          $lic = getMeta($meta, "contributor", ";", false);
                      }
                  } else {
                      $lic = $temp;
                  }
              } else {
                  $lic = "the maker(s)";
              }
            ?>
            <div style="width: 100%; float: left;"><span xmlns:dct="http://purl.org/dc/terms/" href="http://purl.org/dc/dcmitype/Dataset" property="dct:title" rel="dct:type">This spectrum</span> by <?= $lic; ?> is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-nc-nd/3.0/">Creative Commons Attribution-NonCommercial-NoDerivs 3.0 Unported License</a>.</div>
          </div>
    
        </div>

        <!-- metadata -->
        <div id="one-true">
          <?php
            foreach ($meta as $tag => $sub):
              if (($tag != "type") and ($tag != "jcamptemplate")):
          ?>
          <div class='col'>
            <h3><?= nameMeta($tag) ?></h3>
            <?php if (!is_array($sub)): ?><p><?= $sub ?>
            <?php else: ?>
            <table class='alternate_color'>
              <?php foreach ($sub as $subtag => $subsub): ?><tr><td><?= nameMeta($tag . ":" . $subtag) ?>:</td><td><?= getMeta($meta, $tag . ":" . $subtag, "; ", false) ?></td></tr>
            </table>
            <?php   endforeach;
                  endif;
            ?>
          </div>
        <?php endif;
            endforeach;
        ?>
        </div>

        <?php if ($dl_ShowButtons and $dl_ShowForm): ?>
        <script>
          document.addEventListener('click', function () {
            // form validation

            var $target;
            var validname = true;
            var validinst = true;
            var validemail = true;
            var validlic = true;
            var x = "";

            $target = document.getElementById("name");
            $help = document.getElementById("namehelp");
            if ($target) {
              x = $target.value;
              if( x.length < 2 ) {
                $target.classList.remove('is-success');
                $target.classList.add('is-danger');
                $help.style.display = "";
                $help.innerHTML = "Please provide a valid name.";
                validname = false;
              }
              else {
                $target.classList.remove('is-danger');
                $target.classList.add('is-success');
                $help.style.display = "none";
                validname = true;
              }
            }

            $target = document.getElementById("institution");
            $help = document.getElementById("insthelp");
            if ($target) {
              x = $target.value;
              if( x.length < 2 ) {
                $target.classList.remove('is-success');
                $target.classList.add('is-danger');
                $help.style.display = "";
                $help.innerHTML = "Please provide a valid institution/university/company name.";
                validinst = false;
              }
              else {
                $target.classList.remove('is-danger');
                $target.classList.add('is-success');
                $help.style.display = "none";
                validinst = true;
              }
            }

            $target = document.getElementById("email");
            $help = document.getElementById("emailhelp");
            if ($target) {
              x = $target.value;
              var atpos = x.indexOf("@");
              var dotpos = x.lastIndexOf(".");
              if (atpos < 1 || dotpos < atpos + 2 || dotpos + 2 >= x.length) {
                $target.classList.remove('is-success');
                $target.classList.add('is-danger');
                $help.style.display = "";
                $help.innerHTML = "Please provide a valid e-mail address.";
                validemail = false;
              }
              else {
                $target.classList.remove('is-danger');
                $target.classList.add('is-success');
                $help.style.display = "none";
                validemail = true;
              }
            }

            $target = document.getElementById("license");
            $help = document.getElementById("lichelp");
            if ($target) {
              if (!$target.checked) {	
                $help.style.display = "";
                $help.innerHTML = "Required";
                $help.classList.remove('is-success');
                validlic = false;
              }
              else {
                $help.style.display = 'none';
                validlic = true;
              }
            }

            document.getElementById("btnsubmit").disabled = !(validname && validinst && validemail && validlic);
          });
        </script>

        <!-- download modal -->
        <div class='modal' id='dlmodal'>
          <div class='modal-background'></div>
          <div class='modal-content'>
            <form name="dlform" action="<?= $_SERVER["PHP_SELF"] ?>?lib=<?= $showLib ?>&id=<?= $showID ?>&ds=<?= $showDS ?>" method="post">

              <div class="field">
                <label class="label">Name</label>
                <div class="control has-icon-left">
                  <input class="input" type="text" id="name" name="name" placeholder="Your name" maxlength="64">
                  <span class="icon is-small is-left"><i class="fa fa-user"></i></span>
                </div>
                <p class="help is-danger" id="namehelp">Required</p>
              </div>

              <div class="field">
                <label class="label">Institution</label>
                <div class="control has-icon-left">
                  <input class="input" type="text" id="institution" name="institution" placeholder="Your institution/university/company" maxlength="256">
                  <span class="icon is-small is-left"><i class="fa fa-institution"></i></span>
                </div>
                <p class="help is-danger" id="insthelp">Required</p>
              </div>

              <div class="field">
                <label class="label">E-mail</label>
                <div class="control has-icon-left">
                  <input class="input" type="email" id="email" name="email" placeholder="Your e-mail address" maxlength="128">
                  <span class="icon is-small is-left"><i class="fa fa-envelope"></i></span>
                </div>
                <p class="help is-danger" id="emailhelp">Required</p>
              </div>

              <div class="field">
                <div class="control">
                  <label class="checkbox">
                    <input type="checkbox" name="license" id="license" value="license">
                    I agree to the terms and conditions of the license <a rel="license" href="http://creativecommons.org/licenses/by-nc-nd/3.0/" target="_parent" ><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-nc-nd/3.0/88x31.png" /></a>
                  </label>
                </div>
                <p class="help is-danger" id="lichelp">Required</p>
              </div>

              <div class="field">
                <div class="control">
                  <label class="checkbox">
                    <input type="checkbox" name="cookie" value="cookie" checked>
                    Remember my data for subsequent downloads (this creates a cookie on your device)
                  </label>
                </div>
              </div>

              <input type="hidden" id="dl" name="dl">

              <div class="field is-grouped is-grouped-right">
                <button class='button is-primary' type="submit" id="btnsubmit" disabled>
                  <span class="icon is-small"><i class="fa fa-download"></i></span>
                  <span>Download</span>
                </button>
              </div>
            </form>
          </div>
          <button class="modal-close is-large" aria-label="close"></button>
        </div>
        <?php endif;

  // 8. FOOTER
  include(FOOTER_FILE);