<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

if ($isLoggedIn) {
    $email = '<a href="mailto:wim.fremout@kikirpa.be">Wim Fremout <img src="./img/freecons/28_white.png" alt="e-mail" height="11" width="16"></a>';
} else {
    $email = mailhide(
      'wim.fremout@kikirpa.be',
                      'Wim Fremout <img src="./img/freecons/28_white.png" alt="e-mail" height="11" width="16">'
                    );
}
  
?>
    </div><!-- main -->
  </div><!-- wrapper -->
  
  <div class="footer">
    <div id="left">
      Powered by <a href="https://github.com/KIKIRPA/Entropy">Entropy</a><br>
      &copy;2012-<?= date("Y") . " " . $email ?> and contributors 
    </div>
    <div id="right">
      Development was supported by 
      <a href="http://www.kikirpa.be"><img src="./img/kikirpa.png" width="40" height="40"></a>
      <a href="http://iperionch.eu"><img src="./img/iperion.png" width="40" height="40"></a>
    </div>
  </div>
</body>
</html>