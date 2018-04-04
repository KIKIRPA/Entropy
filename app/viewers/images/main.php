<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}


?>
                        <div class="main-carousel" style="width: 100%; height: 450px;">
<?php                     foreach ($measurement["data"] as $imgAlt => $imgURL): ?>
                            <div class="carousel-cell" style="width: 80%; height: 450px;">
                                <img src="<?= $imgURL ?>" alt="<?= $imgAlt ?>" style="height: 450px;">
                            </div>
<?php                     endforeach; ?>
                        </div>

                        <script type="text/javascript">
                            $('.main-carousel').flickity({
                            });
                        </script>
