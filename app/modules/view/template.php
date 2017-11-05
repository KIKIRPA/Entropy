<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

?>

        <section class="section">
            <div class="container">
                <h1 class="title"><?= reset($viewTags) ?></h1>
                <h2 class="subtitle"><?= $viewTags["Type"] ?></h2>
                <div class="field is-grouped is-grouped-multiline">
<?php             foreach ($viewTags as $key => $value): ?>
                    <div class="control">
                        <div class="tags has-addons">
                            <span class="tag"><?= $key ?></span>
                            <span class="tag <?= $viewColor ?>"><?= $value ?></span>
                        </div>
                    </div>
<?php             endforeach; ?>
                </div>
<?php         if (count($data["dataset"]) > 1): ?>
                <div class="tabs">
                    <ul>
<?php                 foreach ($data["dataset"] as $key => $value): ?>
                        <li<?= ($key == $showDS) ? " class=\"is-active\"" : "" ?>>
                            <a href="<?= $_SERVER["PHP_SELF"]; ?>?lib=<?= $showLib ?>&id=<?= $showID ?>&ds=<?= $showDS ?>"><?= $value ?></a>
                        </li>
<?php                 endforeach; ?>
                    </ul>
                </div>
<?php         else: ?>
                <hr>
<?php         endif; ?>

                <br>

                <div class="columns">
                    <div class="column is-three-quarters">
<?php                  require_once(PRIVPATH . "viewers/" .  $viewer . "/main.php"); ?>
                    </div>
                    <div class="column is-one-quarter">
                        <div class="box">
                            <h1 class="title is-5">License</h1>
                            <hr>
                            <div class="is-size-7 has-text-centered">
                                <span xmlns:dct="http://purl.org/dc/terms/" href="http://purl.org/dc/dcmitype/Dataset" property="dct:title" rel="dct:type">
                                    <a rel="license" href="http://creativecommons.org/licenses/by-nc-nd/4.0/"><img alt="Creative Commons License" style="border-width:0" src="https://i.creativecommons.org/l/by-nc-nd/4.0/88x31.png" /></a><br />This work is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-nc-nd/4.0/">Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License</a>.
                                </span>
                            </div>
                        </div>
<?php                 if ($viewDownloadEnabled): ?>
                        <div class="box">
                            <h1 class="title is-5">Download</h1>
                            <hr>
                            <div class="has-text-centered">
<?php                         foreach ($viewDownloadButtons as $key => $value): ?>
                                <a <?= $value ?>>
                                    <span class="icon is-small"><i class="fa fa-download"></i></span>
                                    <span><?= $key ?></span>
                                </a>
<?php                         endforeach; ?>
                            </div> 
                            <br>                       
                            <div class="is-size-7 has-text-centered">
                                <p><em>The complete <?= $LIBS[$showLib]["name"] ?> can be requested by email.</em></p>
                                <p><em>By downloading this file you agree to the terms described in the license.</em></p>
                            </div>
                        </div>
<?php                 endif; ?>
                    </div>
                </div>
            </div>
        </section>

<?php if ($viewMetadata): ?>
        <section>
            <div class="container">
<?php         foreach ($viewMetadata as $row): ?>
                <div class="columns is-centered">
<?php             foreach ($row as $header => $column): ?>
                    <div class="column is-4">
                        <table class="table is-fullwidth">
                            <thead>
                                <tr>
                                    <th colspan = "2" class="has-text-centered"><?= $header ?></th>
                                </tr>
                            </thead>
                            <tbody>
<?php                         if (is_array($column)): ?>
<?php                          foreach ($column as $key => $value): ?>
                                <tr>
                                    <td class="has-text-right has-text-weight-semibold"><?= $key ?></td>
                                    <td><?= $value ?></td>
                                </tr>
<?php                          endforeach; ?>
<?php                         else: ?>
                                <tr>
                                    <td colspan = "2" class="has-text-centered"><?= $column ?></td>
                                </tr>
<?php                         endif; ?>
                            </tbody>
                        </table>
                    </div>
<?php             endforeach; ?>
                </div>
<?php         endforeach; ?>
            </div>
        </section>
<?php endif; ?>

<?php if ($viewDownloadEnabled and $viewShowModal): ?>
        <script>
            document.addEventListener('click', function () {
                var $target;
                var validname = true;
                var validinst = true;
                var validemail = true;
                var validlic = true;
                var x = "";

                $target = document.getElementById("name");
                $help = document.getElementById("namehelp");
                if ($target) {
                    x = $target.value;
                    if( x.length < 2 ) {
                        $target.classList.remove('is-success');
                        $target.classList.add('is-danger');
                        $help.style.display = "";
                        $help.innerHTML = "Please provide a valid name.";
                        validname = false;
                    } else {
                        $target.classList.remove('is-danger');
                        $target.classList.add('is-success');
                        $help.style.display = "none";
                        validname = true;
                    }
                }

                $target = document.getElementById("institution");
                $help = document.getElementById("insthelp");
                if ($target) {
                    x = $target.value;
                    if( x.length < 2 ) {
                        $target.classList.remove('is-success');
                        $target.classList.add('is-danger');
                        $help.style.display = "";
                        $help.innerHTML = "Please provide a valid institution/university/company name.";
                        validinst = false;
                    } else {
                        $target.classList.remove('is-danger');
                        $target.classList.add('is-success');
                        $help.style.display = "none";
                        validinst = true;
                    }
                }

                $target = document.getElementById("email");
                $help = document.getElementById("emailhelp");
                if ($target) {
                    x = $target.value;
                    var atpos = x.indexOf("@");
                    var dotpos = x.lastIndexOf(".");
                    if (atpos < 1 || dotpos < atpos + 2 || dotpos + 2 >= x.length) {
                        $target.classList.remove('is-success');
                        $target.classList.add('is-danger');
                        $help.style.display = "";
                        $help.innerHTML = "Please provide a valid e-mail address.";
                        validemail = false;
                    } else {
                        $target.classList.remove('is-danger');
                        $target.classList.add('is-success');
                        $help.style.display = "none";
                        validemail = true;
                    }
                }

                $target = document.getElementById("license");
                $help = document.getElementById("lichelp");
                if ($target) {
                    if (!$target.checked) {	
                        $help.style.display = "";
                        $help.innerHTML = "Required";
                        $help.classList.remove('is-success');
                        validlic = false;
                    } else {
                        $help.style.display = 'none';
                        validlic = true;
                    }
                }

                document.getElementById("btnsubmit").disabled = !(validname && validinst && validemail && validlic);
            });
        </script>

        <div class="modal" id="dlmodal">
            <div class="modal-background"></div>
            <div class="modal-content">
                <form name="dlform" action="<?= $_SERVER["PHP_SELF"] ?>?lib=<?= $showLib ?>&id=<?= $showID ?>&ds=<?= $showDS ?>" method="post">

                    <div class="field">
                        <label class="label">Name</label>
                        <div class="control has-icon-left">
                            <input class="input" type="text" id="name" name="name" placeholder="Your name" maxlength="64">
                            <span class="icon is-small is-left"><i class="fa fa-user"></i></span>
                        </div>
                        <p class="help is-danger" id="namehelp">Required</p>
                    </div>

                    <div class="field">
                        <label class="label">Institution</label>
                        <div class="control has-icon-left">
                            <input class="input" type="text" id="institution" name="institution" placeholder="Your institution/university/company" maxlength="256">
                            <span class="icon is-small is-left"><i class="fa fa-institution"></i></span>
                        </div>
                        <p class="help is-danger" id="insthelp">Required</p>
                    </div>

                    <div class="field">
                        <label class="label">E-mail</label>
                        <div class="control has-icon-left">
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

                    <div class="field is-grouped is-grouped-right">
                        <button class="button is-primary" type="submit" id="btnsubmit" disabled>
                            <span class="icon is-small"><i class="fa fa-download"></i></span>
                            <span>Download</span>
                        </button>
                    </div>
                </form>
            </div>
            <button class="modal-close is-large" aria-label="close"></button>
        </div>
<?php endif; ?>
