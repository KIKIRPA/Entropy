<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}


//HEADER
array_push($htmlHeaderStyles, CSS_DT_BULMA);
array_push($htmlHeaderScripts, JS_DT, JS_DT_BULMA);  
include(HEADER_FILE);

  
/* *********************************************************
    FUNCTION CHOOSER
   ********************************************************* */
  
if (!isset($libmk)) {
    $libmk = false;
}  // in module_libmk.inc.php this is set to true!
if (!isset($startPage)) {
    $startPage = false;
}     // in module_START this is set to true

if (!$startPage) {
    $id = str_replace(" ", "", strtolower($_REQUEST["lib"]));
} else {
    $id = "_START";
}

$new = !isset($LIBS[$id]);           // if not in libs.json

if ($startPage) {
    echo "      <h3>Modify start page</h3>\n";
} elseif ($new) {
    echo "      <h3>Create library</h3>\n";
} else {
    echo "      <h3>Edit library</h3>\n";
}

$f="error";

if (isset($_REQUEST["set"])) {
    if (    !empty($_REQUEST["lib"])
        and !empty($_REQUEST["view"])
        and ($startPage or !empty(trim($_REQUEST["name"])))
        and ($startPage or !empty(trim($_REQUEST["navmenucaption"]))) 
       ) {
        $f="set";
    } else {
        $errormsg = "Some data is missing: (requires id" . ($startPage ? "" : ", name, menu caption") . " and view)";
    }
} else {
    $f="edit";
}

// disallowed combinations of $new and $libmk
if (!$new and $libmk) {  // libmk: cannot make this lib, because the libID already exists!
$errormsg = "Cannot make this library: a library with this library ID already exists!";
    $f="error";
} elseif ($new and !$libmk) {  // libedit: cannot make a new lib with libedit
    $errormsg = "Cannot edit this library: library ID not found!";
    $f="error";
}


/* *********************************************************
1. EDIT: library detail view; adding/updating libraries
********************************************************* */

if ($f == "edit") {
    $preset = array( "lib" => "",
                     "name" => "",
                     "navmenucaption" => "",
                     "view" => "",
                     "color" => "",
                     "logobox" => "",
                     "catchphrase" => "",
                     "text" => "",
                     "contact" => "",
                     "news" => array(),
                     "references" => array(),
                     "listcolumns" => array(),
                     "downloadconverted" => array(),
                     "downloadbinary" => array()
    );

    if (!$new) {
        foreach ($LIBS[$id] as $i => $item) {
            $preset[$i] = $item;
        }
    }
?>

<script type="text/javascript">
    function addNews(divName){
        var newdiv = document.createElement('div');
        newdiv.innerHTML = "<textarea name='news[]' style='width: 100%;'></textarea><br>";
        document.getElementById(divName).appendChild(newdiv);
    }

    function addRef(divName){
        var newdiv = document.createElement('div');
        newdiv.innerHTML = "<textarea name='references[]' style='width: 100%;'></textarea><br>";
        document.getElementById(divName).appendChild(newdiv);
    }

    function addCol(divName){
        var newdiv = document.createElement('div');
        newdiv.innerHTML = "<textarea name='listcolumns[]' style='width: 100%;'></textarea><br>";
        document.getElementById(divName).appendChild(newdiv);
    }

    function addConv(divName){
        var newdiv = document.createElement('div');
        newdiv.innerHTML = "<textarea name='downloadconverted[]' style='width: 100%;'></textarea><br>";
        document.getElementById(divName).appendChild(newdiv);
    }

    function addBin(divName){
        var newdiv = document.createElement('div');
        newdiv.innerHTML = "<textarea name='downloadbinary[]' style='width: 100%;'></textarea><br>";
        document.getElementById(divName).appendChild(newdiv);
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
        return( true );
    }
    
</script>

<form name='libedit' action='<?= $_SERVER["REQUEST_URI"] . "&set" ?>' method='post' onsubmit='return(validate());'>
    <table cellspacing='8' style='width: 100%;'> 
        <tr>
            <td><label for='le_lib' class='label'>Library ID</label><span style='color:red'>*</span></td>
            <td><?php if ($startPage): ?>Start page<input type='hidden' id='le_lib' name='lib' value='_START'>
                <?php elseif ($new):   ?><input type='text' id='le_lib' name='lib' maxlength='12' value='<?= $id ?>' style='width: 100%;'>
                <?php else:            ?><?= $id ?><input type='hidden' id='le_lib' name='lib' value='<?= $id ?>'>
                <?php endif; ?>
            </td>
        </tr>
        <?php if (!$startPage): ?>
        <tr>
            <td><label for='le_name' class='label'>Name</label><span style='color:red'>*</span></td>
            <td><input type='text' id='le_name' name='name' maxlength='256' style='width: 100%;' value='<?= $preset["name"] ?>'></td>
        </tr>
        <tr>
            <td><label for='le_menu' class='label'>Navigation menu caption</label><span style='color:red'>*</span></td>
            <td><input type='text' id='le_menu' name='navmenucaption' maxlength='256' style='width: 100%;' value='<?= $preset["navmenucaption"] ?>'></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td><label for='le_view' class='label'>View</label><span style='color:red'>*</span></td>
            <td>
            <?php if (!$startPage): ?>
                <input type='radio' id='le_view' name='view' value='locked'<?= ((empty($preset["view"]) or $preset["view"] == "locked") ? " checked" : "") ?>>Locked (viewing this library requires logging in as a user with viewing rights)<br>
                <input type='radio' id='le_view' name='view' value='hidden'<?= (($preset["view"] == "hidden") ? " checked" : "") ?>>Hidden (viewing this library is open, but requires a non-disclosed link)<br>
                <input type='radio' id='le_view' name='view' value='public'<?= (($preset["view"] == "public") ? " checked" : "") ?>>Public (viewing this library is open, with direct access via the menu)
            <?php else: ?>
                <input type='radio' id='le_view' name='view' value='hidden'<?= ((empty($preset["view"]) or $preset["view"] == "hidden") ? " checked" : "") ?>>Hidden<br>
                <input type='radio' id='le_view' name='view' value='public'<?= (($preset["view"] == "public") ? " checked" : "") ?>>Public
            <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td><label for='le_color' class='label'>Theme color</label></td>
            <td>
                <div class="control">
                    <?php foreach ($COLORS as $i => $c): ?>
                    <label class="radio">
                        <input type="radio" name="color" value="<?= $i ?>" <?= (bulmaColorInt($preset["color"], $COLORS) == $i) ? "checked" : "" ?>>
                        <span class="tag <?= bulmaColorModifier($i, $COLORS) ?>"><?= $i ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </td>
        </tr>
        <?php if (!$startPage): ?>
            <tr>
                <td><label for='le_catchphrase' class='label'>Catchphrase</label></td>
                <td><textarea id='le_catchphrase' name='catchphrase' style='width: 100%;'><?= $preset["catchphrase"] ?></textarea></td>
            </tr>
        <?php endif; ?>
        <tr>
            <td><label for='le_logobox' class='label'>Logobox</label></td>
            <td><textarea id='le_logobox' name='logobox' style='width: 100%;'><?= $preset["logobox"] ?></textarea></td>
        </tr>
        <tr>
            <td><label for='le_text' class='label'>Large textbox</label></td>
            <td><textarea id='le_text' name='text' style='width: 100%;'><?= $preset["text"] ?></textarea></td>
        </tr>
        <tr>
            <td><label for='le_contact' class='label'>Contact details box</label></td>
            <td><textarea id='le_contact' name='contact' style='width: 100%;'><?= $preset["contact"] ?></textarea></td>
        </tr>
        <tr>
            <td><label for='le_news' class='label'>News items box</label><br><input type="button" value="+" onClick="addNews('dynamicInputNews');"></td>
            <td>
                <div id="dynamicInputNews">
                    <?php foreach ($preset["news"] as $item): ?><textarea id='le_news' name='news[]' style='width: 100%;'><?= $item ?></textarea><br>
                    <?php endforeach; ?>
                </div>
            </td>
        </tr>
        <tr>
            <td><label for='le_ref' class='label'>Literature references box</label><br><input type="button" value="+" onClick="addRef('dynamicInputRef');"></td>
            <td>
                <div id="dynamicInputRef">
                    <?php foreach ($preset["references"] as $item): ?><textarea id='le_ref' name='references[]' style='width: 100%;'><?= $item ?></textarea><br>
                    <?php endforeach; ?>
                </div>
            </td>
        </tr>
        <?php if (!$startPage): ?>
        <tr>
            <td><label for='le_col' class='label'>Columns in list view</label><br><input type="button" value="+" onClick="addCol('dynamicInputCol');"></td>
            <td>
                <div id="dynamicInputCol">
                    <?php foreach ($preset["listcolumns"] as $item): ?><textarea id='le_col' name='listcolumns[]' style='width: 100%;'><?= $item ?></textarea><br>
                    <?php endforeach; ?>
                </div>
            </td>
        </tr>
        <tr>
            <td><label for='le_conv' class='label'>Allow download of converted formats</label><br><input type="button" value="+" onClick="addConv('dynamicInputConv');"></td>
            <td>
                <div id="dynamicInputConv">
                    <?php foreach ($preset["downloadconverted"] as $item): ?><textarea id='le_conv' name='downloadconverted[]' style='width: 100%;'><?= $item ?></textarea><br>
                    <?php endforeach; ?>
                </div>
            </td>
        </tr>
        <tr>
            <td><label for='le_bin' class='label'>Allow download of binary formats</label><br><input type="button" value="+" onClick="addBin('dynamicInputBin');"></td>
            <td>
                <div id="dynamicInputBin">
                    <?php foreach ($preset["downloadbinary"] as $item): ?><textarea id='le_bin' name='downloadbinary[]' style='width: 100%;'><?= $item ?></textarea><br>
                    <?php endforeach; ?>
                </div>
            </td>
        </tr>
        <?php endif; ?>
    </table>

    <br><br>

    <button type="submit">Save!</button>
</form>
<?php
}

/* *********************************************************
2. SET: add/update library action code
********************************************************* */

elseif ($f == "set") {
    // make array and fill it with (corrected) $_REQUEST parameters in logical order (1=strings, 2=colors, 3=arrays, 4=arrays that need sanitizeStr) 
    $newLib = array();
    $params = array( "name" => 1,
                     "navmenucaption" => 1,
                     "view" => 1,
                     "color" => 2,
                     "logobox" => 1,
                     "catchphrase" => 1,
                     "text" => 1,
                     "contact" => 1,
                     "news" => 3,
                     "references" => 3,
                     "listcolumns" => 4,
                     "downloadconverted" => 5,
                     "downloadbinary" => 5
                   );

    foreach ($params as $item => $category) {
        if (isset($_REQUEST[$item])) {
            switch ($category) {
                case 1: // strings
                    $value = trim($_REQUEST[$item]);
                    if (!empty($value)) {
                        $newLib[$item] = $value;
                    }
                    break;

                case 2: // colors
                    $value = bulmaColorInt(trim($_REQUEST[$item]), $COLORS, DEFAULT_COLOR);
                    if (isset($value)) {
                        $newLib[$item] = $value;
                    }
                    break;

                case 3: // arrays
                    if (is_array($_REQUEST[$item])) {
                        foreach ($_REQUEST[$item] as $i => $value) {
                            $value = trim($value);
                            if (!empty($value)) {
                                $newLib[$item][$i] = $value;
                            }
                        }
                    }
                    break;
                case 4: // arrays that need sanitizeStr, lowercase
                case 5: // arrays that need sanitizeStr, uppercase
                    if (!is_array($_REQUEST[$item])) {
                        $_REQUEST[$item] = explode("|", $_REQUEST[$item]);
                    }
                    foreach ($_REQUEST[$item] as $i => $value) {
                        //$value = str_replace(" ", "_", $value);
                        $value = sanitizeStr($value, "_", false, $category - 3);
                        if (!empty($value)) {
                            $newLib[$item][$i] = $value;
                        }
                    }
                    break;
            }
        }
    }

    //prepare file contents
    $LIBS[$id] = $newLib;

    //and write file
    $output = writeJSONfile(LIB_FILE, $LIBS);

    if ($output == false) {
        echo "    <p>Successfully created or updated library.<br><br>\n";
    } else {
        echo "    <span style='color:red'>ERROR: " . $output . "!</span><br><br>\n";
    }
}


/* *********************************************************
3. ERROR if some data are missing
********************************************************* */

else {
    if (!isset($errormsg)) {
        $errormsg = "unknown error!";
    }

    echo "    <span style='color:red'>ERROR: " . $errormsg . "</span><br><br>";
    eventLog("ERROR", $errormsg . " [module_libedit]", false, false);
}


//FOOTER
include(FOOTER_FILE);