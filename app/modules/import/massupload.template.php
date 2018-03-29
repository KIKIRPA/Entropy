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
            $(document).ready(function() {
                $("#upfile").on('change', function () {
                    $("#btnsubmit").prop( "disabled", $("#upfile").val().length == 0 );
                });
            });          
        </script>

        <section class="section">
            <div class="container">
                <h1 class="title">Mass upload (CSV)</h1>
                <hr>
                <div class="notification">
                    <h1 class="title is-5">How to prepare a comma-separated values (CSV) file with metadata</h1>
                    <div class="content">
                        <ul>
                            <li>CSV files can be generated using Microsoft Excel, LibreOffice, Apache OpenOffice and similar programs. Save the file as CSV or Text format (recognised delimiters are commas, semicolons, tabs and | signs). If your text contains special characters (accents, umlauts...), consider to store the file as 'Unicode Text' in Excel.</li>
                            <li>The first line is the header, defining (sub)field names. Columns without a (sub)field name will be neglected.</li>
                            <li>Each measurement is written on a new line. Lines without (unique) "id" will be neglected.</li>
                            <li>There are two required columns: "<strong>id</strong>", a unique identifier for each measurement, and "<strong>type</strong>", defining the (supported) data type.</li>
                            <li>It is recommended to use the main column headers "<strong>meta:sample</strong>", "<strong>meta:samplesource</strong>", "<strong>meta:instrument</strong>", "<strong>meta:parameters</strong>", "<strong>meta:measurement</strong>" and "<strong>meta:contributor</strong>". These and other fields can be recursively subdivided as required using a semicolon as separator, e.g. "meta:sample:C.I. number", "meta:sample source:0:sample identifier". If a field is subdivided in subfields, the parent field should not be used (or: you can't have data in a "meta:sample" and a "meta:sample:C.I. name" column simultaneously for a given measurement; and it is not advised to use both in the same transaction).</li>
                            <li>If each measurement only contains a single dataset, the system will create a "default" dataset. You can overrule this behaviour by defining an empty column e.g. "datasets:baseline corrected".</li>
                            <li>If all or some measurements contain multiple datasets, the CSV table has to contain multiple datasets, e.g. "datasets:baseline corrected" and "datasets:original data". Dataset-specific metadata can be supplied as subfields of "datasets:original data:meta" and will overrule common metadata. It is advised to store common metadata as subfield of "meta", e.g. "meta:sample:CI number". Metadata in "datasets:x:meta" will overrule those in "meta:".</li>
                            <li>In case of multiple datasets within a single measurement, the "type" field must be the data type of the primary (first) dataset. Other datasets can have different data types, defined in "datasets:x:type".</li>
                        </ul>
                    </div>
                </div><br>

                <form enctype="multipart/form-data" action="<?= $_SERVER["SCRIPT_NAME"] ?>?mod=import&lib=<?= $_REQUEST["lib"] ?>&step=2" method="POST">
                    <input type="hidden" name="MAX_FILE_SIZE" value="2000000">
                    <div class="field">
                        <label class="label">Upload CSV file</label>
                        <div class="control">
                            <input id="upfile" name="upfile" type="file">
                        </div>
                    </div>

                    <div class="field">
                        <div class="control">
                            <label class="radio">
                                <input type="radio" name="action" value="append" checked>
                                Append: add one or more measurements; existing measurements (having the same "id") cannot be overwritten.
                            </label>
                        </div>
                        <div class="control">
                            <label class="radio">
                                <input type="radio" name="action" value="update">
                                Update: add or update one or more measurements; existing measurements (having the same "id") will be updated.
                            </label>
                        </div>
                        <div class="control">
                            <label class="radio">
                                <input type="radio" name="action" value="replace">
                                Replace: <strong>wipe all existing measurements</strong> and replace them with one or more new measurements.
                            </label>
                        </div>
                    </div><br>

                    <div class="field">
                        <div class="control">
                            <button type="submit" id="btnsubmit" class="button <?= $themeColor ?>" disabled>
                                <span class="icon is-small">
                                    <i class="fa fa-chevron-right"></i>
                                </span>
                                <span>Next</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
