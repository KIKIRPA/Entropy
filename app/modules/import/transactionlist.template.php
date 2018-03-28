<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}
?>


<?php if (!empty($notifications)): ?>
        <section class="section">
            <div class="container">
<?php         foreach ($notifications as $item): ?>
                <article class="message is-<?= $item[0] ?>">
                    <div class="message-body">
                        <?= $item[1] ?> 
                    </div>
                </article>
<?php         endforeach; ?>
            </div>
        </section>
<?php endif; ?>



        <section class="section">
            <div class="container">
                <div class="tabs">
                    <ul>
                        <li>
                            <a href="<?= $_SERVER["PHP_SELF"]; ?>?lib=<?= $showLib ?>&mod=import&task=massupload">Mass upload (CSV)</a>
                        </li>
                        <li class="is-active">
                            <a href="<?= $_SERVER["PHP_SELF"]; ?>?lib=<?= $showLib ?>&mod=import&task=transactionlist">Unfinished transactions</a>
                        </li>
                    </ul>
                </div>
            </div>
        </section>


        <section class="section">
            <div class="container">
                <h1 class="title">Unfinished transactions</h1>
                <hr>


                <?= !$listTransactions ? "<p>No unfinished transactions</p><br><br>" : "" ?> 

<?php         foreach ($listTransactions as $row => $rowTransactions): ?>
                <div class="tile is-ancestor">
<?php             foreach ($rowTransactions as $id => $tr): ?>
                    <div class="tile is-parent is-4">
                        <article class="tile notification is-child <?= $themeColor ?>">


                            <p class="title is-5"><?= $tr["name"] ?></p>
                            <p class="subtitle is-5"><?= $tr["catchphrase"] ?></p>
                            <div class="content is-small">
                                <?= $tr["logobox"] ?> 
                            </div>
                            <a href="./index.php?lib=<?= $id ?>" class="button <?= $themeColor ?> is-inverted is-outlined is-pulled-right">
                                <span class="icon is-small">
                                    <i class="fa fa-plus-circle" aria-hidden="true"></i>
                                </span>
                                <span>Visit...</span>
                            </a>
                        </article>
                    </div>
<?php             endforeach; ?>
                </div>
<?php          endforeach; ?>
            </div>
        </section>






<div  style="border:1px dotted black;">
        <h4>Continue an unfinished transaction</h4>
        <script type="text/javascript" charset="utf-8">
          $(document).ready(function() {
            var oTable = $('#datatable').dataTable( {
              //"sScrollY": "300px",
              "bPaginate": false,
              "bScrollCollapse": true,  
            } );
          } );
        </script>
    
        <table id="_datatable" style="padding: 10px;">
          <thead>
            <tr>
              <th style='background: #ddd; text-align: left; padding: 5px;'>date</th>
              <th style='background: #ddd; text-align: left; padding: 5px;'>user</th>
              <th style='background: #ddd; text-align: left; padding: 5px;'>action</th>
              <th style='background: #ddd; text-align: left; padding: 5px;'></th>
            </tr>
          </thead>
          <tbody>
            <?php
                $n = 0;
                
                foreach ($transactions as $id => $transaction) {
                    //test if the logged in user has made this transaction or is admin
                    if (($transaction["user"] == $isLoggedIn) or $user["permissions"]["admin"]) {
                        if (in_array($transaction["action"], array("append", "update", "replace"))) {
                            $a = $transaction["action"];
                            $u = $transaction["user"];
                            $d = substr($id, 0, 4) . "/" . substr($id, 4, 2) . "/" . substr($id, 6, 2) . " "
                               . substr($id, 8, 2) . ":" . substr($id, 10, 2) . ":" . substr($id, 12, 2);
                            $bg = ($n++ & 1 ? "#eee" : "#fff");
                      
                            echo "              <tr>\n";
                            echo "                <td style='background: $bg; padding: 5px;'>$d</td>\n";
                            echo "                <td style='background: $bg; padding: 5px;'>$u</td>\n";
                            echo "                <td style='background: $bg; padding: 5px;'>$a</td>\n";
                            echo "                <td style='background: $bg; padding: 5px;'>";
                            if ($transactions[$tr]["step"] != 10) {
                                echo "<a href='" . $_SERVER["SCRIPT_NAME"] . "?mod=import&lib=" . $_REQUEST["lib"] . "&tr=" . $id. "'>&#9998;</a> ";
                            }
                            if ($transactions[$tr]["step"] != 10) {
                                echo "<a href='" . $_SERVER["SCRIPT_NAME"] . "?mod=import&lib=" . $_REQUEST["lib"] . "&tr=" . $id. "&del' onclick=\"return confirm('Delete this unfinished transaction?')\">&#10006;</a></td>\n";
                            }
                            echo "              </tr>\n";
                        }
                    }
                }
            ?>
          </tbody>
        </table>

      </div>