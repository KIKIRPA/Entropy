<?php

  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }  
  
  header('HTTP/1.0 404 Not Found', true, 404);
  
  //TODO do not hardcode "speclib.kikirpa.be Port 80" !!!!
  
?>

<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL <?php echo $_SERVER["PHP_SELF"]; ?> was not found on this server.</p>
<hr>
<address>Apache/2.4.7 (Ubuntu) Server at speclib.kikirpa.be Port 80</address>
</body></html>

<?php  
  die();
?>