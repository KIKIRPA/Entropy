<?php
  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }


  // HEADER
  
  //$libs: already loaded
  $htmltitle = APP_SHORT . ": " . $libs[$showlib]["menu"];
  $htmlkeywords = APP_KEYWORDS;
  $pagetitle = APP_LONG;
  $pagesubtitle = $libs[$showlib]["name"];
  $style   = "<link rel='stylesheet' type='text/css' href='./css/jquery.dataTables.css'>\n";
  $scripts = "<!--[if IE]><script type='text/javascript' charset='utf8' src='./javascript/excanvas.compiled.js'></script><![endif]-->\n"
       . "    <script type='text/javascript' charset='utf8' src='./javascript/jquery.dataTables.min.js'></script>\n"
       . "    <script type='text/javascript' charset='utf8' src='./javascript/FixedHeader.js'></script>\n";
  
  include(HEADER_FILE); 
  
  if ($error)
    echo $error . "<br><br>\n";
  

  /* *********
      details
     ********* */
  
  if (count($libs[$showlib]) > 0) 
  {
    // longdescription
    if (!empty($libs[$showlib]["longdescription"])) 
      echo "        <div class='nonboxed'>\n",
           "          <h3>About " . ($showlib == "_landingpage")?$libs[$showlib]["name"]:"the library" . "</h3>\n",
           "          ".$libs[$showlib]["longdescription"]."\n",
           "        </div>\n";
    
    // news   
    if (!empty($libs[$showlib]["news"])) 
      foreach ($libs[$showlib]["news"] as $news)
        echo "        <div class='boxed' id='greybox'>\n",
             "          <p>$news</p>\n",
             "        </div>\n";
        
    // contacts 
    if (!empty($libs[$showlib]["contact"]))
    {
      echo "        <div class='boxed'>\n",
           "          <h3>Contact</h3>\n",
           "          <p>" . ($is_logged_in ? $libs[$showlib]["contact"] : searchmailhide($libs[$showlib]["contact"])) . "</p>\n",
           "        </div>\n";  
    }
    
    // refs
    if (!empty($libs[$showlib]["ref"])) 
    {
      echo "        <div class='boxed'>\n",
           "          <h3>Related literature</h3>\n";
      foreach ($libs[$showlib]["ref"] as $ref) echo "          <p>$ref</p>\n";
      echo "        </div>\n";  
    }
    
  } 
  else 
  {
    echo "        <div>\n",
         "          <p>No library details were found for $showlib</p>\n",
         "        </div>\n";
  }

  
/* ******************
    measurement list
   ****************** */  
  
  if ($showlib != "_landingpage") //normal library
  {
    // 1. which columns to show
    
    // the columns to show can be defined in libraries.json, otherwise take defaults
    if (!empty($libs[$showlib]["columns"]))
      $columns = $libs[$showlib]["columns"];
    else  //just take the columns that are certainly available in measurements.json
      $columns = array("id", "type");
    
    // column names to be displayed
    foreach ($columns as $i => $column)
      $columnnames[$i] = nameMeta($column);
      
    ?>
        <script type="text/javascript" charset="utf-8">
          $(document).ready(function() {
            var oTable = $('#datatable').dataTable( {
              //"sScrollY": "300px",
              "bPaginate": false,
              "bScrollCollapse": true,
              "aoColumns": [ { "bSortable": false }, <?php foreach ($columns as $column) echo "null, "; ?> ]        
            } );
            new FixedHeader( oTable );
          } );
        </script>
          
        <div class='fullwidth'>
          <h3>Spectral library contents</h3>
          <table id="datatable" width="100%">
            <thead>
              <tr>
                <th> </th>
    <?php 
    
    foreach ($columnnames as $name) 
      echo "                <th>" . $name . "</th>\n"; 
    
    ?>
              </tr>
            </thead>
            <tbody>
    <?php

    // 2. read measurements.json
    
    foreach ($measurements as $id => $m) 
    {   
      echo "              <tr>\n";
      // first column: link to open
      echo '                <td><a href="./index.php?lib=' . $showlib . '&id=' . $id . '"><img src="./images/freecons/06.png" alt="[open]"></a></td>' . "\n";
      foreach ($columns as $column)
      {
        if (strtolower($column) == "id")
          echo "                <td>" . $id . "</td>\n";
        else
          echo "                <td>" . $m[$column] . "</td>\n";
      }
      echo "              </tr>\n";
    }

    ?>
            </tbody>
          </table>
        </div>
    <?php    
  }
  
/* ******************
      library list
   ****************** */   
  
  else //$showlib == "_landingpage"
  {
    echo "        <div class=\"fullwidth\">\n",
         "          <h3>Available libraries</h3>\n";
       
    foreach ($libs as $libid => $lib)
    {
      if ($libid != "_landingpage")
      {
        if ((strtolower($lib["view"]) == "public") or calcPermLib($user["permissions"], "view", $libid))
          echo "          <div class=\"boxed\" id=\"libbox\">\n",
              "            <h4>".$lib["name"]."</h4>\n",
              "            <p>".$lib["shortdescription"]."</p>\n",
              "            <a class=\"libboxlink\" href=\"./index.php?lib=" . $libid . "\">>> visit library</a>\n",
              "          </div>\n";
      }
    }
    echo "        </div>\n";
  }
  
  

FOOTER:
  // FOOTER
  include(FOOTER_FILE);

?>
