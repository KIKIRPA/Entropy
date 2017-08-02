<?php
  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }

  //TEST: possible to move the <script src=...datatables.js> from the header to here (so it only gets loaded when necessary?

?>

          <div id="graphdiv" class="nonboxed" style="height:400px; float: left;"></div>          
          <script type='text/javascript' src='https://cdnjs.cloudflare.com/ajax/libs/dygraph/2.0.0/dygraph.min.js' async></script>
          <script type="text/javascript">
            g = new Dygraph(
                              document.getElementById("graphdiv"),
                              <?php echo json_encode($data["dataset"][$ds]["data"]); ?>,
                              { 
                                labels: ["<?php echo $Units[0]; ?>","<?php echo $idbox_head; ?>"],
                                xlabel: "<?php echo $Units[0]; ?>", 
                                ylabel: "<?php echo $Units[1]; ?>",
                                //drawYAxis: false,
                                axisLabelFontSize: 10,
                                yAxisLabelWidth: 70,
                                colors: ["red", "black", "blue", "green"],
                              }
                            );

          <?php if (isset($anno)) echo "g.ready(function() { g.setAnnotations(" . json_encode($anno) . "); });"; ?>
          </script>
<?php
            


 



             