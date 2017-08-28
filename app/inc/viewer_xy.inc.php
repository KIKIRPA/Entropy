<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

  //TEST: possible to move the <script src=...datatables.js> from the header to here (so it only gets loaded when necessary?

?>

          <div id="graphdiv" class="nonboxed" style="height:400px; float: left;"></div>          
          <script type='text/javascript' src='<?= JS_DYGRAPH ?>' async></script>
          <script type="text/javascript">
            g = new Dygraph(
              document.getElementById("graphdiv"),
              <?= json_encode($data["dataset"][$ds]["data"]) ?>,
              { 
                labels: ["<?= $Units[0] ?>","<?= $idbox_head ?>"],
                xlabel: "<?= $Units[0] ?>", 
                ylabel: "<?= $Units[1] ?>",
                //drawYAxis: false,
                axisLabelFontSize: 10,
                yAxisLabelWidth: 70,
                colors: ["red", "black", "blue", "green"],
              }
            );

          <?= isset($anno) ? "g.ready(function() { g.setAnnotations(" . json_encode($anno) . "); });" : "" ?>
          </script>
<?php