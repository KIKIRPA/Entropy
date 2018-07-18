<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

?>
        <div class="modal" id="dlmodal">
            <div class="modal-background"></div>
            <div class="modal-card">
                <form name="dlform" id="dlform" action="<?= $_SERVER["PHP_SELF"] ?>?lib=<?= $showLib ?>&id=<?= $showID ?>&ds=<?= $showDS ?>" method="post">
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
                                    I agree to the terms and conditions of the license:
                                </label>
                            </div>
                            <div class="is-size-7 has-text-centered"><?= $viewLicense ?></div>
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
