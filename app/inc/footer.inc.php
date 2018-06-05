<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}
  
?>

        <footer class="footer">
            <div class="container">
                <div class="columns">
                    <div class="column is-8">
                        <div class="content is-size-6">
                            <?= \Core\Service\MailHider::search(\Core\Config\App::get("app_footer_box"), ($isLoggedIn ? false : true)) ?> 
                        </div>
                    </div>
                    <div class="column has-text-right">
                        <p class="is-size-6">Powered by <strong>Entropy</strong>
                            <a href="https://github.com/KIKIRPA/Entropy" target="_blank">
                                <span class="icon is-medium">
                                    <i class="fa fa-2x fa-github" aria-hidden="true"></i>
                                </span>
                            </a>
                        </p>
                        <p class="is-size-7">
                            A repository tailored for analytical data<br><br>
                            Developed by Wim Fremout<br>
                            Supported by:<br>
                            <a href="http://www.kikirpa.be" target="_blank"><img src="./img/footer_kikirpa.png" alt="KIK/IRPA" height="30"></a> 
                            <a href="http://iperionch.eu" target="_blank"><img src="./img/footer_iperion.png" alt="IPERION-CH" height="30"></a>
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </body>
</html>