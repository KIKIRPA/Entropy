<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

?>
        <script>
            $(document).ready(function() {
                var validname = false;
                var validinst = false;
                var validemail = false;
                var validlic = false;

                $("#name").on('change', function () {
                    if( $("#name").val().length < 2 ) {
                        $("#name").removeClass('is-success');
                        $("#name").addClass('is-danger');
                        $("#namehelp").show();
                        $("#namehelp").text("Please provide a valid name.");
                        validname = false;
                    } else {
                        $("#name").removeClass('is-danger');
                        $("#name").addClass('is-success');
                        $("#namehelp").hide();
                        validname = true;
                    }
                    $("#btnsubmit").prop( "disabled", !(validname && validinst && validemail && validlic) );
                });

                $("#institution").on('change', function () {
                    if( $("#institution").val().length < 2 ) {
                        $("#institution").removeClass('is-success');
                        $("#institution").addClass('is-danger');
                        $("#insthelp").show();
                        $("#insthelp").text("Please provide a valid institution/university/company name.");
                        validinst = false;
                    } else {
                        $("#institution").removeClass('is-danger');
                        $("#institution").addClass('is-success');
                        $("#insthelp").hide();
                        validinst = true;
                    }
                    $("#btnsubmit").prop( "disabled", !(validname && validinst && validemail && validlic) );
                });

                $("#email").on('change', function () {
                    var atpos = $("#email").val().indexOf("@");
                    var dotpos = $("#email").val().lastIndexOf(".");
                    if( atpos < 1 || dotpos < atpos + 2 || dotpos + 2 >= $("#email").val().length ) {
                        $("#email").removeClass('is-success');
                        $("#email").addClass('is-danger');
                        $("#emailhelp").show();
                        $("#emailhelp").text("Please provide a valid e-mail address.");
                        validemail = false;
                    } else {
                        $("#email").removeClass('is-danger');
                        $("#email").addClass('is-success');
                        $("#emailhelp").hide();
                        validemail = true;
                    }
                    $("#btnsubmit").prop( "disabled", !(validname && validinst && validemail && validlic) );
                });

                $("#license").on('change', function () {
                    if( !$("#license").prop("checked") ) {
                        $("#lichelp").show();
                        $("#lichelp").text("Required.");
                        validlic = false;
                    } else {
                        $("#lichelp").hide();
                        validlic = true;
                    }
                    $("#btnsubmit").prop( "disabled", !(validname && validinst && validemail && validlic) );
                });
            }); 
        </script>

        <div class="modal" id="dlmodal">
            <div class="modal-background"></div>
            <div class="modal-card">
                <form name="dlform" action="<?= $_SERVER["PHP_SELF"] ?>?lib=<?= $showLib ?>&id=<?= $showID ?>&ds=<?= $showDS ?>" method="post">
                    <header class="modal-card-head">
                        <p class="modal-card-title">Tell us about you...</p>
                        <button class="delete" type="reset" aria-label="close"></button>
                    </header>

                    <section class="modal-card-body">
                        <div class="field">
                            <label class="label">Name</label>
                            <div class="control has-icons-left">
                                <input class="input" type="text" id="name" name="name" placeholder="Your name" maxlength="64">
                                <span class="icon is-small is-left"><i class="fa fa-user"></i></span>
                            </div>
                            <p class="help is-danger" id="namehelp">Required</p>
                        </div>

                        <div class="field">
                            <label class="label">Institution</label>
                            <div class="control has-icons-left">
                                <input class="input" type="text" id="institution" name="institution" placeholder="Your institution/university/company" maxlength="256">
                                <span class="icon is-small is-left"><i class="fa fa-institution"></i></span>
                            </div>
                            <p class="help is-danger" id="insthelp">Required</p>
                        </div>

                        <div class="field">
                            <label class="label">E-mail</label>
                            <div class="control has-icons-left">
                                <input class="input" type="email" id="email" name="email" placeholder="Your e-mail address" maxlength="128">
                                <span class="icon is-small is-left"><i class="fa fa-envelope"></i></span>
                            </div>
                            <p class="help is-danger" id="emailhelp">Required</p>
                        </div>

                        <div class="field">
                            <div class="control">
                                <label class="checkbox">
                                    <input type="checkbox" name="license" id="license" value="license">
                                    I agree to the terms and conditions of the license <a rel="license" href="https://creativecommons.org/licenses/by-nc-nd/3.0/" target="_parent" ><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-nc-nd/3.0/88x31.png" /></a>
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
                    </section>

                    <footer class="modal-card-foot">
                        <div class="field is-grouped is-grouped-right">
                            <button class="button <?= $viewColor ?>" type="submit" id="btnsubmit" disabled>
                                <span class="icon is-small"><i class="fa fa-download"></i></span>
                                <span>Download</span>
                            </button>
                        </div>
                    </footer>
                </form>
            </div>
        </div>
