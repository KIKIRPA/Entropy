<?php
  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }
  
  echo "      <h3>Untrusted connection</h3>\n"; 

?>
  <p>This is (likely) the first time you log in from this computer/connection. 
     As an added security measure you have to prove your identity before continuing.  
     A 4-digit code has been sent to <?php echo $users[$user]['email'];?>.  Please copy this code below. 
     Once the code has been verified, this procedure should not be shown again on this computer.
  </p>
  <form name='trust' action='<?php echo $_SERVER["PHP_SELF"]; ?>' method='post'>
    <table cellspacing='8' style='width: 80%;'> 
      <tr>
        <td><label accesskey='p' for='trustcode' class='label'>code</label></td>
        <td><input type='password' id='trustcode' name='trustcode' maxlength='4'></td>
      </tr>
    </table>
    
    <br><br>
    <?php if (isset($msg)) echo "<span style='color:red'>" . $msg . "</span><br>\n"; ?>
    <button type="submit">Verify!</button>
  </form>
  