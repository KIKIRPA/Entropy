<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

?>
  <h3>Library management</h3>
  <script type="text/javascript" charset="utf-8">
    $(document).ready(function() {
      var oTable = $('#datatable').dataTable( {
        //"sScrollY": "300px",
        "bPaginate": false,
        "bScrollCollapse": true,      
      } );
      new FixedHeader( oTable );
    } );

    function createlib(){
      var newlib = prompt("Please enter a short and descriptive library name or acronym (no spaces, no capitals)");
      if (newlib != null) {
        window.location='<?= $_SERVER["REQUEST_URI"] ?>&create=' + newlib;
      }
    }
  </script>
  
  <div class='fullwidth'>
    <table id="datatable" width="100%">
      <thead>
        <tr>
          <th>id</th>
          <th>name</th>
          <th>view</th>
          <th>colour</th>
          <th>actions
            <?= calcPermLib($user["permissions"], "libmk") ? " <a href='" . $_SERVER["PHP_SELF"] . "?mod=libmk'>&#10010</a>" : "" ?>
          </th>
        </tr>
      </thead>
      <tbody>
<?php

  foreach ($LIBS as $id => $lib) {
      if (($lib["view"] == "public") or calcPermLib($user["permissions"], "view", $id)) {
          if ($libid != "_landingpage") {
              echo "        <tr>\n",
                  "          <td>" . $id . "</td>\n",
                  "          <td>" . $lib["name"] . "</td>\n",
                  "          <td>" . $lib["view"]. "</td>\n",
                  "          <td><span style='color:" . $lib["colour"] . "'>&#9724;</span></td>\n",
                  "          <td>\n";
        
              if (calcPermLib($user["permissions"], "libedit", $id)) {
                  echo "            <a href=\"" . $_SERVER["PHP_SELF"] . "?mod=libedit&lib=" . $id . "\"'>&#9998;</a> \n";
              }
        
              if (calcPermLib($user["permissions"], "libperm", $id)) {
                  echo "            <a href=\"" . $_SERVER["PHP_SELF"] . "?mod=libperm&lib=" . $id . "\"'>&#9786;</a> \n";
              }
          
              if (calcPermLib($user["permissions"], "libdel", $id)) {
                  echo "            <a href=\"" . $_SERVER["PHP_SELF"] . "?mod=libdel&lib=" . $id . "&del \"' onclick=\"return confirm('Delete this library?')\">&#10006;</a>\n";
              }
        
              echo "          </td>\n",
                  "        </tr>\n";
          }
      }
  }
  
?>
      </tbody>
    </table>
  </div>