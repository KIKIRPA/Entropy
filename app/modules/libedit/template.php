<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

?>
<?php if (!empty($notifications)): ?>
        <section class="section">
            <div class="container">
<?php         foreach ($notifications as $item): ?>
                <article class="message is-<?= $item[0] ?>">
                    <div class="message-body">
                        <?= $item[1] ?> 
                    </div>
                </article>
<?php         endforeach; ?>
            </div>
        </section>
<?php endif; ?>

        <script type="text/javascript">
            function addNews(divName){
                var newdiv = document.createElement('div');
                newdiv.innerHTML = "<textarea name='news[]' rows=2 class='textarea'></textarea><br>";
                document.getElementById(divName).appendChild(newdiv);
            }

            function addRef(divName){
                var newdiv = document.createElement('div');
                newdiv.innerHTML = "<textarea name='references[]' rows=2 class='textarea'></textarea><br>";
                document.getElementById(divName).appendChild(newdiv);
            }

            function addCol(divName){
                var newdiv = document.createElement('div');
                newdiv.innerHTML = "<input type='text' name='listcolumns[]' maxlength=128 class='input'><br><br>";
                document.getElementById(divName).appendChild(newdiv);
            }

            function addBin(divName){
                var newdiv = document.createElement('div');
                newdiv.innerHTML = "<input type='text' name='downloadbinary[]' maxlength=64 class='input'><br><br>";
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

        <section class="section">
            <div class="container">
                <h1 class="title"><?= $libeditTitle ?></h1>
                <hr>

                <form name="libedit" action="<?= $_SERVER["REQUEST_URI"] . "&set" ?>" method="post" onsubmit="return(validate());">
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label" for="le_lib">Unique identifier<span class="has-text-danger">*</span></label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">
                                    <input type="text" id="le_lib" name="lib" maxlength=16 value="<?= $libeditId ?>" class="input<?= ($startPage or !$libmk) ? " is-static\" readonly" : "\"" ?>>
                                </p>
                                <p class="help">
                                    A unique identifier for the library, which may (or may intentionally not) be a recognisable keyword for the library. 
                                    It is used mostly for internal use by the repository software, but will be a part of the shareable link for hidden libraries. 
                                    Use only lowercase characters, numbers and underscores. Maximum 16 characters. 
                                    <strong>Once set, it cannot be changed.</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                    <br>
<?php             if (!$startPage): ?>
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label" for="le_name">Name<span class="has-text-danger">*</span></label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">
                                    <input type="text" id="le_name" name="name" maxlength=100 class="input" value="<?= $preset["name"] ?>">
                                </p>
                                <p class="help">
                                    The full name of the library, as will be shown in titles and headers. Maximum 100 characters.
                                </p>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label" for="le_menu">Navigation menu caption<span class="has-text-danger">*</span></label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">
                                    <input type="text" id="le_menu" name="navmenucaption" maxlength=25 class="input" value="<?= $preset["navmenucaption"] ?>">
                                </p>
                                <p class="help">
                                    Short descriptive caption for the library, as will be shown in the navigation bar. 
                                    Maximum 100 characters, but using long library names in a multiple repository set-up might break the layout.
                                </p>
                            </div>
                        </div>
                    </div>
                    <br>
<?php             endif; ?>
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label" for="le_view">Viewability<span class="has-text-danger">*</span></label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">
<?php                             if (!$startPage): ?>
                                    <label class="radio"><input type="radio" id="le_view" name="view" value="locked"<?= ((empty($preset["view"]) or $preset["view"] == "locked") ? " checked" : "") ?>>Private</label>
                                    <label class="radio"><input type="radio" id="le_view" name="view" value="hidden"<?= (($preset["view"] == "hidden") ? " checked" : "") ?>>Hidden</label>
                                    <label class="radio"><input type="radio" id="le_view" name="view" value="public"<?= (($preset["view"] == "public") ? " checked" : "") ?>>Public</label>
<?php                             else: ?>
                                    <label class="radio"><input type="radio" id="le_view" name="view" value="hidden"<?= ((empty($preset["view"]) or $preset["view"] == "hidden") ? " checked" : "") ?>>Hidden</label>
                                    <label class="radio"><input type="radio" id="le_view" name="view" value="public"<?= (($preset["view"] == "public") ? " checked" : "") ?>>Public</label>
<?php                             endif; ?>
                                </p>
                                <p class="help">
<?php                             if (!$startPage): ?>
                                    This setting controls public viewability of the library (list, view, download). 
                                    A public library will be public and accessible through then avigation menu without logging in. 
                                    A hidden library is not listed in the navigation menu, but can be consulted without logging in by sharing its direct link. 
                                    A private library is only accessible for logged-in users with the appropriate permissions.
<?php                             else: ?>
                                    This setting enables or disables the starting page.
<?php                             endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label" for="le_color">Theme color</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">
<?php                             foreach ($COLORS as $i => $c): ?>
                                    <label class="radio">
                                        <input type="radio" id="le_color" name="color" value="<?= $i ?>" <?= (bulmaColorInt($preset["color"], $COLORS) == $i) ? "checked" : "" ?>>
                                        <span class="tag <?= bulmaColorModifier($i, $COLORS) ?>"><?= $i ?></span>
                                    </label>
<?php                             endforeach; ?>
                                </p>
                                <p class="help">
                                    Theme color of the navigation bar and controls.
                                </p>
                            </div>
                        </div>
                    </div>
                    <br>
<?php             if (!$startPage): ?>
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label" for="le_catchphrase">Subtitle / catchphrase</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">
                                    <input type="text" id="le_catchphrase" name="catchphrase" maxlength=200 class="input" value="<?= $preset["catchphrase"] ?>">
                                </p>
                                <p class="help">
                                    Short description of the repository or library that will be shown as subtitle in the header and in the library list on the start page. 
                                    Maximum 200 characters.
                                </p>
                            </div>
                        </div>
                    </div>
                    <br>
<?php             endif; ?>
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label" for="le_logobox">Logobox</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">
                                    <textarea id="le_logobox" name="logobox" rows=4 class="textarea"><?= $preset["logobox"] ?></textarea>
                                </p>
                                <p class="help">
                                    Area in the headers and in the library list on the start page, and can be used to put formatted text and small images, such as logos.  
                                    Basic HTML formatting can be used.
                                </p>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label" for="le_text">Large textbox</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">
                                    <textarea id="le_text" name="text" rows=8 class="textarea"><?= $preset["text"] ?></textarea>
                                </p>
                                <p class="help">
                                    Extensive description of the repository or library that will be displayed above the start page or the library list page.
                                    No length limits and basic HTML formatting can be used.
                                </p>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label" for="le_contact">Contact details box</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">
                                    <textarea id="le_contact" name="contact" rows=4 class="textarea"><?= $preset["contact"] ?></textarea><br>
                                </p>
                                <p class="help">
                                    Contents in this box will be displayed in box with "Contact details" header on the start page or the library list page. 
                                    Basic HTML formatting can be used.
                                </p>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label" for="le_news">News item boxes</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <div id="dynamicInputNews">
<?php                             foreach ($preset["news"] as $item): ?>
                                    <div class="control">
                                        <textarea id="le_news" name="news[]" rows=2 class="textarea"><?= $item ?></textarea><br>
                                    </div>
<?php                             endforeach; ?>
                                </div>
                                <p class="control">
                                    <button type="button" class="button is-small" onClick="addNews('dynamicInputNews');">
                                        <span class="icon is-small"><i class="fa fa-plus-circle" aria-hidden="true"></i></span>
                                        <span>Add item</span>
                                    </button>
                                </p>
                                <p class="help">
                                    Contents will be displayed in eye-catching boxes displayed on the start page or the library list page. 
                                    Ideal for news messages and notifications to the users. 
                                    Multiple boxes can be defined and will be displayed in the order in which they are defined here.
                                    To remove a box, remove all its contents. 
                                    Basic HTML formatting can be used.
                                </p>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label" for="le_ref">Literature references box</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <div id="dynamicInputRef">
<?php                             foreach ($preset["references"] as $item): ?>
                                    <div class="control">
                                        <textarea id="le_ref" name="references[]" rows=2 class="textarea"><?= $item ?></textarea><br>
                                    </div>
<?php                             endforeach; ?>
                                </div>
                                <p class="control">
                                    <button type="button" class="button is-small" onClick="addRef('dynamicInputRef');">
                                        <span class="icon is-small"><i class="fa fa-plus-circle" aria-hidden="true"></i></span>
                                        <span>Add item</span>
                                    </button>
                                </p>
                                <p class="help">
                                    Contents will be displayed in a box dedicated for literature references on the start page or the library list page.
                                    Multiple items can be defined and will be shown as a list of references in the order in which they are defined here.
                                    To remove a box, remove all its contents. 
                                    Basic HTML formatting can be used.
                                </p>
                            </div>
                        </div>
                    </div>
                    <br>
<?php             if (!$startPage): ?>
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label" for="le_col">Columns in list view</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <div id="dynamicInputCol">
<?php                             foreach ($preset["listcolumns"] as $item): ?>
                                    <div class="control">
                                        <input type="text" id="le_col" name="listcolumns[]" maxlength=128 class="input" value="<?= $item ?>"><br><br>
                                    </div>
<?php                             endforeach; ?>
                                </div>
                                <p class="control">
                                    <button type="button" class="button is-small" onClick="addCol('dynamicInputCol');">
                                        <span class="icon is-small"><i class="fa fa-plus-circle" aria-hidden="true"></i></span>
                                        <span>Add column</span>
                                    </button>
                                </p>
                                <p class="help">
                                    Defines the columns and the order in which they will be displayed on the library list page. 
                                    All common metadata fields and subfields as defined in the metadata scheme can be requested. 
                                    With the exception of the "metadata:" prefix, the complete hierarchical tree of a subfield has to be supplied e.g. "samplesource:0:sample identifier". 
                                    If a field is (in all or some data files) subdivided in subfield, requesting the parent field as a column will join all metadata in its subfields (e.g. it is possible to request "contributor", even if this field is subdivided in "contributor:analyst", "...:institution", "...:address" and more; all the subfield metadata will be shown in a single column, separated with semicolumns). 
                                    In case you would like to combine selected subfields of a subdivided field in a specific order, this can be achieved by combining them with a plus sign (e.g. "contributor:analyst+institution").
                                    Date/time fields can be formatted by adding a predefined format string following a caret; possible formats are "datetime", "longdate", "shortdate", "year" and "time" (e.g. "sample:age^year").<br>
                                    <strong>IMPORTANT NOTE:</strong> define the columns based on your metadata schema <strong>BEFORE DATA IS IMPORTED</strong>. 
                                    Based on the chosen columns, metadata will be indexed. This indexation cannot be changed afterwards by altering these columns.
                                </p>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label" for="le_conv">Allowed download of converted formats</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
<?php                         foreach ($EXPORT as $datatype => $value): ?>
<?php                          if ($i != "Annotations"): ?>
                                <p class="control">
                                <strong><?= $datatype ?>:</strong><br>
<?php                             foreach ($value["extensions"] as $extension => $temp): ?>
                                    <label class="checkbox">
                                        <input type="checkbox" id="le_conv" name="downloadconverted[]" value="<?= $datatype . ":". $extension ?>"<?= (in_array($i, $preset["downloadconverted"]) ? " checked" : "") ?>>
                                        <?= strtoupper($extension) ?>
                                    </label><br>
<?php                             endforeach; ?>
                                </p>
<?php                          endif; ?>
<?php                         endforeach; ?>                                   
                                <p class="help">
                                    Allow the user to download data files in the following formats with on-the-fly conversion.
                                </p>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label" for="le_bin">Allowed download of binary files</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <div id="dynamicInputBin">
<?php                             foreach ($preset["downloadbinary"] as $item): ?>
                                    <div class="control">
                                        <input type="text" id="le_bin" name="downloadbinary[]" maxlength=12 class="input" value="<?= $item ?>"><br><br>
                                    </div>
<?php                             endforeach; ?>
                                </div>
                                <p class="control">
                                    <button type="button" class="button is-small" onClick="addBin('dynamicInputBin');">
                                        <span class="icon is-small"><i class="fa fa-plus-circle" aria-hidden="true"></i></span>
                                        <span>Add item</span>
                                    </button>
                                </p>
                                <p class="help">
                                    Allow the user to download specific binary file formats (uploaded as such). The format of the file is determined by its file extension.
                                </p>
                            </div>
                        </div>
                    </div>
                    <br>
<?php             endif; ?>

                    <div class="field is-horizontal">
                        <div class="field-label"></div>
                        <div class="field-body">
                            <div class="field">
                                <button type="submit" class="button <?= $libeditColor ?>">
                                    <span class="icon is-small">
                                        <i class="fa fa-check"></i>
                                    </span>
                                    <span>Save</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>
