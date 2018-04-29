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
    // expand if path contains * or ?
    if ((strpos($imgPath, '*') !== false) or (strpos($imgPath, '?') !== false)) {
        //prepare path
        $imgPath = str_replace("file://", "", $imgPath);
        $imgPath = ltrim($imgPath, "./\\"); //prevent escaping from our "jail" using ../
        $imgPath = str_replace("..", "", $imgPath);
        $imgPath = $prefix . "/" . $imgPath;
        $array = glob($imgPath);

        foreach ($array as $i => $item) {
            $code = \Core\Service\DownloadCode::storePath($imgPath, $prefix);

            if ($code) {
                $images[$imgAlt . " ($i)"] = $code;
            }
        }
    } else {
        $code = \Core\Service\DownloadCode::storePath($imgPath, $prefix);
    
        if ($code) {
            $images[$imgAlt] = $code;
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
