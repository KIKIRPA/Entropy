<?php
  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }
  
/* ******************
      HTML HEADER PART
     ****************** */
  
  $htmltitle = APP_SHORT;
  $htmlkeywords = APP_KEYWORDS;
  $pagetitle = APP_LONG;
  //$pagesubtitle = ""; //previously set
  $style   = "";
  $scripts = "";
  
  include(HEADER_FILE);  
  
  if ($error)
    echo $error . "<br><br>\n";
  
  
  /* *****************
      HTML MAIN PART
     ***************** */
  
  // MAIN: info
  echo "        <div class=\"nonboxed\">\n",
       "          <h3>About</h3>\n",
       "          <p>about speclib.kikirpa.be</p>\n",
       "        </div>\n",
       "        <div class=\"boxed\" id=\"greybox\">\n",
       "          <p>New website!</p>\n",
       "        </div>\n",
       "        <div class=\"boxed\">\n",
       "          <h3>Contact</h3>\n",
       "          <p>New website!</p>\n",
       "        </div>\n";
    
  // MAIN: library list
  echo "        <div class=\"fullwidth\">\n",
       "          <h3>Available spectral libraries</h3>\n";
       
  foreach ($libs as $libid => $lib)
  {
    if (strtolower($lib["view"]) == "public")
      echo "          <div class=\"boxed\" id=\"libbox\">\n",
           "            <h4>".$lib["name"]."</h4>\n",
           "            <p>".$lib["shortdescription"]."</p>\n",
           "            <a class=\"libboxlink\" href=\"./index.php?lib=" . $libid . "\">>> visit library</a>\n",
           "          </div>\n";
  }
  echo "        </div>\n";


  /* ******************
      HTML FOOTER PART
     ****************** */
     
  include(FOOTER_FILE);

?>