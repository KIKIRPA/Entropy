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
  $style   = "    <link rel='stylesheet' type='text/css' src='https://cdnjs.cloudflare.com/ajax/libs/dygraph/2.0.0/dygraph.min.css'>\n";
  $scripts = "    <script type='text/javascript' src='https://cdnjs.cloudflare.com/ajax/libs/dygraph/2.0.0/dygraph.min.js' async></script>\n";
  
  include(HEADER_FILE);  
  
  // MAIN
  
  $idbox = $measurements;
  $idbox_head = array_shift($idbox);
  unset($idbox["_operation"]);
  
  //TODO dataset!! type of data!!
  $meta = overrideMeta($data);
  $graph = json_encode($data["dataset"]["default"]["data"]);
  
?>
        <div class='fullwidth'>
          <div class='boxed' id='redbox'>
            <h3><?php echo $idbox_head; ?></h3>
            <?php foreach ($idbox as $a) echo "<p>" . $a . "\n"; ?>
          </div>  
    
          <div id="graphdiv" class="nonboxed" style="height:400px; float: left;"></div>
          <script type="text/javascript">
            g2 = new Dygraph(
              document.getElementById("graphdiv"),
              <?php echo $graph; ?>,
              { 
                labels: ["???","<?php echo $idbox_head; ?>"],
                xlabel: "Raman shift (cm<sup>-1</sup>)<!--?php echo $xaxis; ?-->", 
                ylabel: "Intensity (arbitrary units)<!--?php echo $yaxis; ?-->",
                //drawYAxis: false,
                axisLabelFontSize: 10,
                yAxisLabelWidth: 70,
                colors: ["red", "black", "blue", "green"],
              }
            );
          </script>   
    
          <div class='boxed'>
            <h3>Download</h3>
            <div style="text-align:center">
              <?php 
                /* *********************************
                    downloadbuttons to be displayed
                   ********************************* */  
                
                foreach ($LIBS[$showlib]["allowformat"] as $extension)
                {
                  $dlcode = $dlbutton = NULL;
                  switch ($extension) 
                  {
                    case "dx":
                    case "jdx":
                      if (isset($meta["jcamptemplate"]) AND file_exists(LIB_DIR . $showlib . "/templates/" . $meta["jcamptemplate"]))
                      {
                        $dlbutton = "JCAMP-DX";
                        $dlcode = "DX";
                      }
                      break;
                    case "txt": //ascii data (may need to be converted)
                      $dlbutton = strtoupper($extension);
                      $dlcode = "ASCII";
                      break;
                    default:    //binary
                      // check if the binary file exists!
                      // TODO I guess there will be problems when the file extension has uppercase symbols...
                      if (file_exists(LIB_DIR . $showlib . "/" . $measurements["_operation"] . "/" . $showid . "." . $extension))
                      {
                        $dlbutton = strtoupper($extension);
                        $dlcode = "BIN";
                      }
                      break;
                  }
                  
                  if ($dlcode != NULL)
                  {
                    $dlcode = encode($dlcode . "||" . $showid . "||" . $extension, CRYPT_KEY);
                    
                    // if cookie exists and is valid: open popup; else direct download
                    if ($cookie)
                      echo "              <button class=\"button\" type=\"button\" value=\"javascript:void(0)\" onclick=\"window.location.href='".$_SERVER["PHP_SELF"]."?lib=".$showlib."&show=".$showspectrum."&dl=".$dlcode."'\">".$dlbutton."</button>\n";
                    else
                      echo "              <button class=\"button\" type=\"button\" value=\"javascript:void(0)\" onclick=\"$('form input[name=dl]').val('".$dlcode."');document.getElementById('light').style.display='block';document.getElementById('fade').style.display='block'\">".$dlbutton."</button>\n";
                  }
                }             
              ?>
            </div>
            <p><i>The complete <?php echo $LIBS[$showlib]["name"]; ?> can be requested by email.</i>
            <p><i>By downloading this file you agree to the terms described in the licence.</i>
          </div>
          
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
  
  // DOWNLOAD POPUP
  if (!$cookie) include './inc/spectrum_popup.inc.php';

  // FOOTER
  include(FOOTER_FILE);

    
?>