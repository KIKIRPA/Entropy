<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

echo "      <h3>Log in</h3>\n";

?>
  <form name='login' action='<?= $_SERVER["PHP_SELF"] ?>' method='post'>
    <table cellspacing='8' style='width: 80%;'> 
      <tr>
        <td><label accesskey='u' for='user' class='label'>Username or email address</label></td>
        <td><input type='text' id='user' name='user' maxlength='64'></td>
      </tr>
      <tr>
        <td><label accesskey='p' for='pass' class='label'>password</label></td>
        <td><input type='password' id='pass' name='pass' maxlength='64'></td>
      </tr>
    </table>
      
    <br><br>
    <?= isset($msg) ? "<span style='color:red'>" . $msg . "</span><br>\n" : "" ?>
    <button type="submit">Log in!</button>
  </form>
  