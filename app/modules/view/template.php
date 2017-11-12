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
                            <a href="<?= $_SERVER["PHP_SELF"]; ?>?lib=<?= $showLib ?>&id=<?= $showID ?>&ds=<?= $showDS ?>"><?= $key ?></a>
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
