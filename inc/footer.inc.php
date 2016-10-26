<?php
  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }
  
  if ($is_logged_in)
    $email = "<a href=\"mailto:wim.fremout@kikirpa.be\">Wim Fremout <img src=\"./images/freecons/28_white.png\" alt=\"e-mail\" height=\"11\" width=\"16\"></a>";
  else
    $email = mailhide( "wim.fremout@kikirpa.be",
                      "Wim Fremout <img src=\"./images/freecons/28_white.png\" alt=\"e-mail\" height=\"11\" width=\"16\">"
                    );
  
?>
    </div><!-- main -->
  </div><!-- wrapper -->
  
  <div class="footer">
    <div id="logo">
      <a href="http://www.kikirpa.be">
        <img src="./images/kikirpalogo.png" width="40" height="40" alt="KIK/IRPA">
      </a>
    </div>
    <div id="left">
      &copy;2012-<?php echo date("Y");?> <a href="http://www.kikirpa.be">Royal Institute for Cultural Heritage (KIK/IRPA)</a>
    </div>
    <div id="right">Website developed by <?php echo $email; ?></div>
  </div>
</body>
</html>

