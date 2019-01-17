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
            tinymceInit('.tinymce');

            function tinymceInit(sel){
                tinymce.init({
                    selector: sel,
                    statusbar: false,
                    menubar: false,
                    plugins: 'code image autolink link textcolor colorpicker table lists',
                    toolbar: 'styleselect bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | outdent indent | bullist numlist table | link image | code',
                    // image_list: [
                    //     {title: 'My image 1', value: 'https://www.tinymce.com/my1.gif'},
                    //     {title: 'My image 2', value: 'http://www.moxiecode.com/my2.gif'}
                    // ],
                    // link_list:  [
                    //     {title: 'My image 1', value: 'https://www.tinymce.com/my1.gif'},
                    //     {title: 'My image 2', value: 'http://www.moxiecode.com/my2.gif'}
                    // ]
                });
            }

            function addNews(divName){
                var newdiv = document.createElement('div');
                var id = 'textarea' + document.querySelectorAll("textarea").length;
                newdiv.innerHTML = '<textarea name="news[]" rows=2 class="textarea tinymce" id="' + id + '"></textarea><br>';
                document.getElementById(divName).appendChild(newdiv);
                tinymceInit('#' + id);
            }

            function addRef(divName){
                var newdiv = document.createElement('div');
                var id = 'textarea' + document.querySelectorAll("textarea").length;
                newdiv.innerHTML = '<textarea name="references[]" rows=2 class="textarea tinymce" id="' + id + '"></textarea><br>';
                document.getElementById(divName).appendChild(newdiv);
                tinymceInit('#' + id);
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

            function addLicOther(){
                if( $("#license").val() == "_OTHER" ) {
                    $("#otherlicense").show();
                } else {
                    $("#otherlicense").hide();
                }
            }

            function validateString(element){
                if( $(element).val().length == 0 ) {
                    $(element).removeClass('is-success');
                    $(element).addClass('is-danger');
                    return false;
                } else {
                    $(element).removeClass('is-danger');
                    $(element).addClass('is-success');
                    return true;
                }
            }

            $(document).ready(function() {
                var validlib = validateString("#lib");
                var validname = <?= $startPage ? "true" : "validateString(\"#name\")" ?>;
                var validmenu = <?= $startPage ? "true" : "validateString(\"#navmenucaption\")" ?>;
                $("#btnsubmit").prop( "disabled", !(validlib && validname && validmenu) );

                $("#lib").on('change', function () {
                    validlib = validateString("#lib");
                    $("#btnsubmit").prop( "disabled", !(validlib && validname && validmenu) );
                });

<?php         if (!$startPage):     /* TODO: we are currently not testing if a view (radio) has been checked */ ?>
                $("#name").on('change', function () {
                    validname = validateString("#name");
                    $("#btnsubmit").prop( "disabled", !(validlib && validname && validmenu) );
                });

                $("#navmenucaption").on('change', function () {
                    validmenu = validateString("#navmenucaption");
                    $("#btnsubmit").prop( "disabled", !(validlib && validname && validmenu) );
                });

                addLicOther();
                $("#license").on('change', function () {
                    addLicOther();
                });
<?php         endif; ?>
            });
            
        </script>

        <section class="section">
            <div class="container">
                <h1 class="title"><?= $libeditTitle ?></h1>
                <hr>

                <form name="libedit" action="<?= $_SERVER["REQUEST_URI"] . "&set" ?>" method="post">
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label" for="lib">Unique identifier<span class="has-text-danger">*</span></label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">
                                    <input type="text" id="lib" name="lib" maxlength=16 value="<?= $libeditId ?>" class="input<?= ($startPage or !$libmk) ? " is-static\" readonly" : "\"" ?>>
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
                            <label class="label" for="name">Name<span class="has-text-danger">*</span></label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">
                                    <input type="text" id="name" name="name" maxlength=100 class="input" value="<?= $preset["name"] ?>">
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
                            <label class="label" for="navmenucaption">Navigation menu caption<span class="has-text-danger">*</span></label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">
                                    <input type="text" id="navmenucaption" name="navmenucaption" maxlength=25 class="input" value="<?= $preset["navmenucaption"] ?>">
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
                            <label class="label">Viewability<span class="has-text-danger">*</span></label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">
<?php                             if (!$startPage): ?>
                                    <label class="radio"><input type="radio" name="view" value="locked"<?= ((empty($preset["view"]) or $preset["view"] == "locked") ? " checked" : "") ?>>Private</label>
                                    <label class="radio"><input type="radio" name="view" value="hidden"<?= (($preset["view"] == "hidden") ? " checked" : "") ?>>Hidden</label>
                                    <label class="radio"><input type="radio" name="view" value="public"<?= (($preset["view"] == "public") ? " checked" : "") ?>>Public</label>
<?php                             else: ?>
                                    <label class="radio"><input type="radio" name="view" value="hidden"<?= ((empty($preset["view"]) or $preset["view"] == "hidden") ? " checked" : "") ?>>Hidden</label>
                                    <label class="radio"><input type="radio" name="view" value="public"<?= (($preset["view"] == "public") ? " checked" : "") ?>>Public</label>
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
                            <label class="label" for="color">Theme color</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">
<?php                             foreach ($COLORS as $i => $c): ?>
                                    <label class="radio">
                                        <input type="radio" id="color" name="color" value="<?= $i ?>" <?= (bulmaColorInt($preset["color"], $COLORS) == $i) ? "checked" : "" ?>>
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
                            <label class="label" for="catchphrase">Subtitle / catchphrase</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">
                                    <input type="text" id="catchphrase" name="catchphrase" maxlength=200 class="input" value="<?= $preset["catchphrase"] ?>">
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
                            <label class="label" for="logobox">Logobox</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">
                                    <textarea id="logobox" name="logobox" rows=4 class="textarea tinymce"><?= $preset["logobox"] ?></textarea>
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
                            <label class="label" for="text">Large textbox</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">
                                    <textarea id="text" name="text" rows=8 class="textarea tinymce"><?= $preset["text"] ?></textarea>
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
                            <label class="label" for="contact">Contact details box</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">
                                    <textarea id="contact" name="contact" rows=4 class="textarea tinymce"><?= $preset["contact"] ?></textarea><br>
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
                            <label class="label" for="news">News item boxes</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <div id="dynamicInputNews">
<?php                             foreach ($preset["news"] as $item): ?>
                                    <div class="control">
                                        <textarea id="news" name="news[]" rows=2 class="textarea tinymce"><?= $item ?></textarea><br>
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
                            <label class="label" for="references">Literature references box</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <div id="dynamicInputRef">
<?php                             foreach ($preset["references"] as $item): ?>
                                    <div class="control">
                                        <textarea id="references" name="references[]" rows=2 class="textarea tinymce"><?= $item ?></textarea><br>
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
                            <label class="label" for="listcolumns">Columns in list view</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <div id="dynamicInputCol">
<?php                             foreach ($preset["listcolumns"] as $item): ?>
                                    <div class="control">
                                        <input type="text" id="listcolumns" name="listcolumns[]" maxlength=128 class="input" value="<?= $item ?>"><br><br>
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
                                    The complete hierarchical tree of a subfield has to be supplied e.g. "meta:sample source:0:sample identifier". 
                                    If a field is (in all or some data files) subdivided in subfield, requesting the parent field as a column will join all metadata in its subfields (e.g. it is possible to request "meta:contributor", even if this field is subdivided in "meta:contributor:analyst", "...:institution", "...:address" and more; all the subfield metadata will be shown in a single column, separated with semicolumns). 
                                    In case you would like to combine selected subfields of a subdivided field in a specific order, this can be achieved by combining them with a plus sign (e.g. "meta:contributor:analyst+institution").
                                    Date/time fields can be formatted by adding a predefined format string following a caret; possible formats are "datetime", "longdate", "shortdate", "year" and "time" (e.g. "meta:sample:age^year").<br>
                                    <strong>IMPORTANT NOTE:</strong> define the columns based on your metadata schema <strong>BEFORE DATA IS IMPORTED</strong>. 
                                    Based on the chosen columns, metadata will be indexed. This indexation cannot be changed afterwards by altering these columns.
                                </p>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label" for="downloadconverted">Allowed download of converted formats</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
<?php                         foreach ($EXPORT as $datatype => $value): ?>
<?php                          if ($i != "Annotations"): ?>
                                <p class="control">
                                <strong><?= strtoupper($datatype) ?>:</strong><br>
<?php                             foreach ($value["extensions"] as $extension => $temp): ?>
<?php                               $format = sanitizeStr($datatype . ":". $extension, "_", false, 1);?>
                                    <label class="checkbox">
                                        <input type="checkbox" id="downloadconverted" name="downloadconverted[]" value="<?= $format ?>"<?= (in_array($format, $preset["downloadconverted"]) ? " checked" : "") ?>>
                                        .<?= $extension ?>
                                    </label><br>
<?php                             endforeach; ?>
                                </p>
<?php                          endif; ?>
<?php                         endforeach; ?>                                   
                                <p class="help">
                                    Allow the user to download data files in specific formats with on-the-fly conversion.
                                </p>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label" for="downloadbinary">Allowed download of binary files</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <div id="dynamicInputBin">
<?php                             foreach ($preset["downloadbinary"] as $item): ?>
                                    <div class="control">
                                        <input type="text" id="downloadbinary" name="downloadbinary[]" maxlength=12 class="input" value="<?= $item ?>"><br><br>
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
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <label class="label" for="license">Default license</label>
                        </div>
                        <div class="field-body">
                            <div class="field">
                                <p class="control">
                                    <div class="select">
                                        <select id="license" name="license">
<?php                                     foreach ($licenseList as $id => $item): ?>
                                            <option value="<?= $id ?>"<?= ($preset["license"] == $id) ? " selected" : "" ?>><?= !is_null($item) ? $item : $id ?></option>
<?php                                     endforeach; ?>
                                        <select>
                                    </div>
                                </p>
                                <p class="control">
                                    <br><textarea id="otherlicense" name="otherlicense" rows=2 class="textarea tinymce"><?= $preset["otherlicense"] ?></textarea>
                                </p>
                                <p class="help">
                                    License that will be applied to all measurements in this library. This will overrule the system-wide license, but will in turn be overruled if a license is defined in the measurment.
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
                                <button type="submit" id="btnsubmit" class="button <?= $themeColor ?>">
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
