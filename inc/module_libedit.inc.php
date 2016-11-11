<?php
  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }
   
  //TODO is the library parameter "header" still used somewhere? if not, can it be removed?
  
  
/* *********************************************************
    FUNCTION CHOOSER 
   ********************************************************* */ 
  
  if (!isset($libmk)) $libmk = false;  // in module_libmk.inc.php this is set to true!
  if (!isset($lp))    $lp = false;     // in module_landingpage this is set to true
  
  if (!$lp) $id = str_replace(" ", "", strtolower($_REQUEST["lib"]));
  else      $id = "_landingpage";
  
  $new = !isset($libs[$id]);           // if not in libs.json
  
  if     ($lp)  echo "      <h3>Modify landing page</h3>\n";
  elseif ($new) echo "      <h3>Create library</h3>\n";
  else          echo "      <h3>Edit library</h3>\n";
  
  $f="error";
  
  if (isset($_REQUEST["set"]))
  {
    if (     !empty($_REQUEST["lib"])
         and !empty($_REQUEST["name"])
         and !empty($_REQUEST["menu"])
         and !empty($_REQUEST["header"])
         and !empty($_REQUEST["view"])
        )
      $f="set";
    else 
      $errormsg = "Some data is missing! (library id, name, menu, header and view need to be set)";
  }
  else
    $f="edit";
  
  // disallowed combinations of $new and $libmk
  if (!$new and $libmk)  // libmk: cannot make this lib, because the libID already exists!
  {
    $errormsg = "Cannot make this library: a library with this library ID already exists!";
    $f="error";
  }
  elseif ($new and !$libmk)  // libedit: cannot make a new lib with libedit
  {
    $errormsg = "Cannot edit this library: library ID not found!";
    $f="error";
  }
  

/* *********************************************************
    1. EDIT: library detail view; adding/updating libraries 
   ********************************************************* */ 
   
  if ($f == "edit")
  {
    $preset = array( "lib" => "",
                    "name" => "",
                    "view" => "",
                    "menu" => "",
                    "header" => "",
                    "colour" => "",
                    "shortdescription" => "",
                    "longdescription" => "",
                    "contact" => "",
                    "news" => array(),
                    "ref" => array(),
                    "columns" => "",
                    "allowformat" => ""
                    );
    
    if (!$new)
    {
      foreach ($libs[$id] as $i => $item)
        $preset[$i] = $item;
      if (is_array($preset["columns"]))     $preset["columns"] = implode("|", $preset["columns"]);
      if (is_array($preset["allowformat"])) $preset["allowformat"] = implode("|", $preset["allowformat"]);
    }
    //else $id = "";
  
    ?>
    <script type="text/javascript">
      var cRef = 1;
      var lRef = 25;
      var cNews = 1;
      var lNews = 25;
      
      function addRef(divName){
          if (cRef == lRef)  {
                alert("Be reasonable, " + cRef + " references should be enough, no?");
          }
          else {
                var newdiv = document.createElement('div');
                newdiv.innerHTML = "<textarea name='ref[]' style='width: 100%;'></textarea><br>";
                document.getElementById(divName).appendChild(newdiv);
                cRef++;
          }
      }
      
      function addNews(divName){
          if (cNews == lNews)  {
                alert("Be reasonable, " + cNews + " newsitems should be enough, no?");
          }
          else {
                var newdiv = document.createElement('div');
                newdiv.innerHTML = "<textarea name='news[]' style='width: 100%;'></textarea><br>";
                document.getElementById(divName).appendChild(newdiv);
                cNews++;
          }
      }
      
      function validate()
      {
        if( document.libedit.lib.value == "" )
        {
          alert( "Library ID missing!" );
          document.libedit.lib.focus() ;
          return false;
        }
        if( document.libedit.name.value == "" )
        {
          alert( "Library NAME missing!" );
          document.libedit.name.focus() ;
          return false;
        }
        if( document.libedit.menu.value == "" )
        {
          alert( "Library MENU missing!" );
          document.libedit.menu.focus() ;
          return false;
        }
        if( document.libedit.header.value == "" )
        {
          alert( "Library HEADER missing!" );
          document.libedit.header.focus() ;
          return false;
        }
        return( true );
      }
      
    </script>

    <form name='libedit' action='<?php echo $_SERVER["REQUEST_URI"] . "&set"; ?>' method='post' onsubmit='return(validate());'>
      <table cellspacing='8' style='width: 100%;'> 
        <tr>
          <td><label accesskey='l' for='lib' class='label'>lib</label><span style='color:red'>*</span></td>
          <td><?php if ($lp)
                      echo "Landing page" . "<input type='hidden' id='lib' name='lib' value='_landingpage'></td>\n";
                    elseif ($new) 
                      echo "<input type='text' id='lib' name='lib' maxlength='12' value='" . $id . "' style='width: 100%;'></td>\n";
                    else 
                      echo $id . "<input type='hidden' id='lib' name='lib' value='" . $id . "'></td>\n"; 
                ?>
        </tr>
        <tr>
          <td><label accesskey='n' for='name' class='label'>name</label><span style='color:red'>*</span></td>
          <td><input type='text' id='name' name='name' maxlength='256' style='width: 100%;' value='<?php echo $preset["name"]; ?>'></td>
        </tr>
        <tr>
          <td><label accesskey='v' for='view' class='label'>view</label><span style='color:red'>*</span></td>
          <td><?php if (!$lp)
                      echo "            <input type='radio' id='view' name='view' value='locked'" . (empty($preset["view"]) or $preset["view"] == "locked")?" checked>":">" . "Locked (viewing this library requires logging in as a user with viewing rights)<br>\n"
                         . "            <input type='radio' id='view' name='view' value='hidden'" . ($preset["view"] == "hidden")?" checked>":">" . "Hidden (viewing this library is open, but requires a non-disclosed link)<br>\n"
                         . "            <input type='radio' id='view' name='view' value='public'" . ($preset["view"] == "public")?" checked>":">" . "Public (viewing this library is open, with direct access via the menu)\n";
                    else
                      echo "            <input type='radio' id='view' name='view' value='hidden'" . (empty($preset["view"]) or $preset["view"] == "hidden")?" checked>":">" . "Hidden<br>\n"
                         . "            <input type='radio' id='view' name='view' value='public'" . ($preset["view"] == "public")?" checked>":">" . "Public\n";                    
              ?>
          </td>
        </tr>
        <tr>
          <td><label accesskey='m' for='menu' class='label'>menu</label><span style='color:red'>*</span></td>
          <td><input type='text' id='menui' name='menu' maxlength='256' style='width: 100%;' value='<?php echo $preset["menu"]; ?>'></td>
        </tr>
        <tr>
          <td><label accesskey='h' for='header' class='label'>header</label><span style='color:red'>*</span></td>
          <td><textarea id='header' name='header' style='width: 100%;'><?php echo $preset["header"]; ?></textarea></td>
        </tr>
        <tr>
          <td><label accesskey='c' for='colour' class='label'>colour</label></td>
          <td><input type='color' id='colour' name='colour' style='width: 100%;' value='<?php echo $preset["colour"]; ?>'></td>
        </tr>
        <?php if (!$lp)
                echo "<tr>\n"
                   . "          <td><label accesskey='s' for='shortdescription' class='label'>short description</label></td>\n"
                   . "          <td><textarea id='shortdescription' name='shortdescription' style='width: 100%;'>" . $preset["shortdescription"] . "</textarea></td>\n"
                   . "        </tr>";
        ?>
        <tr>
          <td><label accesskey='d' for='longdescription' class='label'>long description</label></td>
          <td><textarea id='longdescription' name='longdescription' style='width: 100%;'><?php echo $preset["longdescription"]; ?></textarea></td>
        </tr>
        <tr>
          <td><label accesskey='o' for='contact' class='label'>contact</label></td>
          <td><textarea id='contact' name='contact' style='width: 100%;'><?php echo $preset["contact"]; ?></textarea></td>
        </tr>
        <tr>
          <td><label accesskey='e' for='news' class='label'>news</label><br><input type="button" value="+" onClick="addNews('dynamicInputNews');"></td>
          <td><div id="dynamicInputNews"><?php foreach ($preset["news"] as $news) echo "<textarea id='news' name='news[]' style='width: 100%;'>" .$news. "</textarea><br>"; ?></div></td>
        </tr>
        <tr>
          <td><label accesskey='r' for='ref' class='label'>references</label><br><input type="button" value="+" onClick="addRef('dynamicInputRef');"></td>
          <td><div id="dynamicInputRef"><?php foreach ($preset["ref"] as $ref) echo "<textarea id='ref' name='ref[]' style='width: 100%;'>" .$ref. "</textarea><br>"; ?></div></td>
        </tr>
        <?php if (!$lp)
                echo "<tr>\n"
                   . "          <td><label accesskey='u' for='columns' class='label'>columns</label></td>\n"
                   . "          <td><input type='text' id='columns' name='columns' style='width: 100%;' value='" . $preset["columns"] . "'></td>\n"
                   . "        </tr>\n"
                   . "        <tr>\n"
                   . "          <td><label accesskey='a' for='allowformat' class='label'>allow formats</label></td>\n"
                   . "          <td><input type='text' id='allowformat' name='allowformat' style='width: 100%;' value='" . $preset["allowformat"] . "'></td>\n"
                   . "        </tr>";
        ?>
      </table>
    
      <br><br>

      <button type="submit">Save!</button>
    </form>
    <?php
  }
  
/* *********************************************************
    2. SET: add/update library action code 
   ********************************************************* */  

  elseif ($f == "set")
  {     
    //take known and non-empty values from $_REQUEST
    $newlib = array( "name"     => trim($_REQUEST["name"]),
                     "menu"     => trim($_REQUEST["menu"]),
                     "header"   => $_REQUEST["header"],
                     "view"     => $_REQUEST["view"]
                    );
    
    if (empty($id) or empty($newlib["name"]) or empty($newlib["menu"]) or empty($newlib["header"]) or empty($newlib["view"]))
    {
      $output = "missing library ID, name, menu, header or view!";
    }
    else 
    {
      foreach (array("colour", "shortdescription", "longdescription", "contact") as $item)
        if (isset($_REQUEST[$item]) and ($_REQUEST[$item] != "")) 
          $newlib[$item] = $_REQUEST[$item];  
      
      $i = 0;
      if (isset($_REQUEST["news"]) and is_array($_REQUEST["news"]))
        foreach ($_REQUEST["news"] as $item)
          if ($item != "") 
            $newlib["news"][$i++] = $item;
      
      $i = 0;
      if (isset($_REQUEST["ref"]) and is_array($_REQUEST["ref"]))
        foreach ($_REQUEST["ref"] as $item)
          if ($item != "") 
            $newlib["ref"][$i++] = $item;
      
      foreach (array("columns", "allowformat") as $item)
        if (isset($_REQUEST[$item]) and ($_REQUEST[$item] != ""))
        {
          $arr = explode("|", str_replace(" ", "_", strtolower($_REQUEST[$item])));
          foreach ($arr as $i => $val)
            $newlib[$item][$i] = sanitizeStr($val, "_", False, False);
        }
      
      //prepare file contents
      $libs[$id] = $newlib;
      
      //and write file
      $output = writeJSONfile(LIB_DIR . LIB_FILE, $libs);
    }
    
    if ($output == false) 
      echo "    <p>Successfully created or updated library.<br><br>\n";
    else
      echo "    <span style='color:red'>ERROR: " . $output . "!</span><br><br>\n";
  }
  
  
/* *********************************************************
    3. ERROR if some data are missing 
   ********************************************************* */   

  else
  {
    if (!isset($errormsg)) $errormsg = "unknown error!";
    
    echo "    <span style='color:red'>ERROR: " . $errormsg . "</span><br><br>";
    eventLog("ERROR", $errormsg . " [module_libedit]", false, false);
  }
  
?>

  
  
  
