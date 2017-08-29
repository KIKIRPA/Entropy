<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

?>

          <div id="graphdiv" class="nonboxed" style="height:400px; float: left;"></div>          
          <script type="text/javascript">
            g = new Dygraph(
              document.getElementById("graphdiv"),
              <?= json_encode($data["dataset"][$showds]["data"]) ?>,
              { 
                labels: ["<?= isset($units[0]) ? $units[0] : "Undefined" ?>","<?= $idbox_head ?>"],
                xlabel: "<?= isset($units[0]) ? $units[0] : "Undefined" ?>", 
                ylabel: "<?= isset($units[1]) ? $units[1] : "Undefined" ?>",
                //drawYAxis: false,
                axisLabelFontSize: 10,
                yAxisLabelWidth: 70,
                colors: ["red", "black", "blue", "green"],
              }
            );

          <?= isset($anno) ? "g.ready(function() { g.setAnnotations(" . json_encode($anno) . "); });" : "" ?>
          </script>
<?php
