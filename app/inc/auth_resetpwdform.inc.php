<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

echo "      <h3>Reset password</h3>\n";

?>
  <script>
    function validate() {
        var pass1 = document.getElementById("newpwd").value;
        var pass2 = document.getElementById("verify").value;
        var ok = true;
        if (pass1 != pass2) {
            alert("Passwords Do not match");
            document.getElementById("newpwd").style.borderColor = "#E34234";
            document.getElementById("verify").style.borderColor = "#E34234";
            ok = false;
        }
        return ok;
    }
  </script>

  <form name='resetpwd' action='<?= $_SERVER["PHP_SELF"] ?>' method='post' onsumbit='return validate()'>
    <table cellspacing='8' style='width: 80%;'> 
      <tr>
        <td><label accesskey='o' for='oldpwd' class='label'>old password</label></td>
        <td><input type='password' id='oldpwd' name='oldpwd' maxlength='64'></td>
      </tr>
      <tr>
        <td><label accesskey='n' for='newpwd' class='label'>new password</label></td>
        <td><input type='password' id='newpwd' name='newpwd' maxlength='64'></td>
      </tr>
      <tr>
        <td><label accesskey='v' for='verify' class='label'>verify new password</label></td>
        <td><input type='password' id='verify' name='verify' maxlength='64'></td>
      </tr>
    </table>
    
    <br><br>
    <?= isset($msg) ? "<span style='color:red'>" . $msg . "</span><br>\n" : "" ?>
    <button type="submit">Verify!</button>
  </form>
  