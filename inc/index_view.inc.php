<?php
  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }

  /* *****************
      cookie handling 
     ***************** */
 
  // option 1: cookie exists and is valid   --> $cookie = array(name, inst, email) + update cookie
  // option 2: cookie exists but is invalid --> $cookie = False
  // option 3: no cookie, but cookie form field is checked and data is valid 
  //                                        --> $cookie = array(name, inst, email) + make cookie
  // option 4: no cookie, and cookie form field is unchecked or data invalid
  //                                        --> $cookie = False
 
  if (isset($_COOKIE[COOKIE_NAME])) 
  {
    $cookie = verifycookie($_COOKIE[COOKIE_NAME]);   // in case of false data, this will output False
    if ($cookie) $cookie = makecookie($cookie);  // if not false: update cookie; will output True
    else removecookie();                        // remove invalid cookie
  }
  elseif (isset($_REQUEST["cookie"])
              and isset($_REQUEST["name"]) 
              and isset($_REQUEST["institution"]) 
              and isset($_REQUEST["email"])
          )
  { 
    $cookie = verifycookie($_REQUEST["name"], $_REQUEST["institution"], $_REQUEST["email"]);
    if ($cookie) $cookie = makecookie($cookie);
  }
  else 
    $cookie = false;

    
  /* *****************
      the HTML output
     ***************** */
  
  // HEADER
  
  $htmltitle = APP_SHORT . ": " . $LIBS[$showlib]["menu"];
  $htmlkeywords =APP_KEYWORDS;
  $pagetitle = APP_LONG;
  $pagesubtitle = $LIBS[$showlib]["name"];
  $style   = ""; //"    <link rel='stylesheet' type='text/css' src='https://cdnjs.cloudflare.com/ajax/libs/dygraph/2.0.0/dygraph.min.css'>\n";
  $scripts = ""; //"    <script type='text/javascript' src='https://cdnjs.cloudflare.com/ajax/libs/dygraph/2.0.0/dygraph.min.js' async></script>\n";
  
  include(HEADER_FILE);  
  
  // MAIN
  
  // data from the measurement json file
  $idbox_head = array_shift($measurement);
  $transaction = $measurement["_transaction"];
  unset($measurement["_transaction"]);


?>
        <div class='fullwidth'>
          <div class='boxed' id='redbox'>
            <h3><?php echo $idbox_head; ?></h3>
            <?php 
              foreach ($measurement as $a) echo "<p>" . $a . "\n";
            ?>
          </div>
<?php

  // dataset selection box
  if (count($measurement["dataset"]) > 1)
  {
    echo "          <div class='boxed' id='greybox'>\n";
    echo "            <h3>Datasets</h3>";
    foreach ($measurement["dataset"] as $dsid => $dsval)
    {
      if ($dsid == $showds) echo "<p><strong>" . $showds . "</strong>\n";
      else                  echo "<p><a href='" . $_SERVER["PHP_SELF"] . "?lib=" . $showlib . "&show=" . $showspectrum . "&ds=" . $dsid . "'>" . $dsid . "</a>\n";
    }
    echo "          </div>\n";
  }

  // metadata can be saved on different levels, override lesser priority metadata
  $meta = overrideMeta($data, $showds);

  // determine datatype and call the correct viewer module
  $parenttype = datatypeParent($measurement["type"], $DATATYPES);
  switch (strtolower($DATATYPES[$parenttype]["type"]))
  {
    case "xy":
      require_once(INC_PATH . 'viewer_xy.inc.php');
      break;
  }

  // downloadbuttons
  $dlList = array();

  // 1. convert from json data files
  //    TODO: integrate convert-framework (check if we are able to convert to $format, preferably replacing the switch by a function)
  foreach ($LIBS[$showlib]["allowformat"] as $format)
  {
    switch ($format) 
    {
      case "dx":
      case "jdx":
        if (isset($meta["jcamptemplate"]) AND file_exists(LIB_PATH . $showlib . "/templates/" . $meta["jcamptemplate"]))
          $dlList["JCAMP-DX"] = "conv=jcampdx";
        break;
      case "txt":
        $dlList["TXT"] = "conv=ascii";
        break;
    }
  }

  // 2. binary uploaded files
  $prefix = LIB_PATH . $showlib . "/" . $transaction . "/" . $showid . (($showds == 'default')?"":"__".$showds); 
  $binfiles = glob($prefix . "__*");  //by using "__*" we exclude the original (converted) data files, json data files and annotations
  foreach ($binfiles as $f)
  {
    // button caption = EXT (ORIG BIN FILENAME)
    $button = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    $button .= "(" . str_replace("_", " ", pathinfo(str_replace($prefix, '', $f), PATHINFO_FILENAME)) . ")";
    $dlList[$button] = "bin=" . $f;
  }

  // 3. create html
  if (!empty($dlList))
  {
    echo "          <div class='boxed'>\n"
       . "            <h3>Download</h3>\n"
       . "            <div style='text-align:center'>\n";

    foreach($dlList as $code => $caption)
    {
      $code = encode($code, CRYPT_KEY);
      // if cookie exists and is valid: open popup; else direct download
      if ($cookie)
        echo "              <button class=\"button\" type=\"button\" value=\"javascript:void(0)\" onclick=\"window.location.href='" . $_SERVER["PHP_SELF"] . "?lib=" . $showlib . "&show=" . $showspectrum . "&dl=" . $code . "'\">" . $caption . "</button>\n";
      else
        echo "              <button class=\"button\" type=\"button\" value=\"javascript:void(0)\" onclick=\"$('form input[name=dl]').val('" . $code . "');document.getElementById('light').style.display='block';document.getElementById('fade').style.display='block'\">" . $caption . "</button>\n";
    }
  
    echo "            </div>\n"
       . "            <p><i>The complete " . $LIBS[$showlib]["name"] . " can be requested by email.</i>\n"
       . "            <p><i>By downloading this file you agree to the terms described in the licence.</i>\n"
       . "          </div>\n";  
  }

?>
          
          <div class='boxed'>
            <div style="width: 50%; float: left;"><h3>Licence</h3></div>
            <div style="float: right; padding: 12px;"><a rel="license" href="http://creativecommons.org/licenses/by-nc-nd/3.0/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-nc-nd/3.0/88x31.png" /></a></div>
            <?php
              if (isset($meta["contributor"]))
              {
                $temp = $meta["contributor"];
                if (is_array($temp)) 
                {
                  if (isset($temp["analyst(s)"]) and isset($temp["institution"])) $lic = $temp["analyst(s)"]." from the ".$temp["institution"];
                  elseif (isset($temp["analyst(s)"])) $lic = $temp["analyst(s)"];
                  elseif (isset($temp["institution"])) $lic = $temp["institution"];
                  else $lic = getMeta($meta, "contributor", ";", False);
                }
                else $lic = $temp;
              } 
              else $lic = "the maker(s)";
            ?>
            <div style="width: 100%; float: left;"><span xmlns:dct="http://purl.org/dc/terms/" href="http://purl.org/dc/dcmitype/Dataset" property="dct:title" rel="dct:type">This spectrum</span> by <?php echo $lic; ?> is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-nc-nd/3.0/">Creative Commons Attribution-NonCommercial-NoDerivs 3.0 Unported License</a>.</div>
          </div>
        </div>

        <div id="one-true">
          <?php
            foreach ($meta as $tag => $sub)
            {
              if (($tag != "type") and ($tag != "jcamptemplate"))
              {
                echo "          <div class='col'>\n",
                     "            <h3>" . nameMeta($tag) . "</h3>\n";
                if (!is_array($sub))
                  echo "            <p>" . $sub . "\n";
                else
                {
                  echo "            <table class='alternate_color'>\n";
                  foreach ($sub as $subtag => $subsub)
                    echo "            <tr><td>" . nameMeta($tag . ":" . $subtag) . ":</td><td>" . getMeta($meta, $tag . ":" . $subtag, "; ", False) . "</td></tr>\n";
                  echo "            </table>\n";
                }
                echo "          </div>\n";
              }
            }
          ?>
        </div>

<?php

  // FOOTER
  include(FOOTER_FILE);

    
?>