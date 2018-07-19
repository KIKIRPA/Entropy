<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

// TODO: this viewer needs to be splitted into code and html (or the html must be outputted in a string)


$prefix = \Core\Config\App::get("downloads_storage_path");
$images = array();

foreach ($measurement["data"] as $imgAlt => $imgPath) {
    $dlc = new \Core\Service\DownloadCode();
    $numberOfPaths = $dlc->setPath($imgPath, $prefix);
    $dlc->store();

    if ($numberOfPaths == 1) {
        $images[$imgAlt] = "./img.php?code=" . $dlc->code;
    } elseif ($numberOfPaths > 1) {
        for ($i = 0; $i < $numberOfPaths; ++$i) {
            $images[$imgAlt . " ($i)"] = "./img.php?code=$dlc->code&i=$i";
        }
    }
}


?>
                        <div class="main-carousel" style="width: 100%; height: 450px;">
<?php                     foreach ($images as $imgAlt => $imgURL): ?>
                            <div class="carousel-cell" style="width: 80%; height: 450px;">
                                <img src="<?= $imgURL ?>" alt="<?= $imgAlt ?>" style="height: 450px;">
                            </div>
<?php                     endforeach; ?>
                        </div>

                        <script type="text/javascript">
                            $('.main-carousel').flickity({
                            });
                        </script>
