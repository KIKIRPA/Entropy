<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

// sort data
$sortOrder = in_array("inverted", $DATATYPES[$parenttype]["graph"]["xy"]) ? SORT_DESC : SORT_ASC;
orderData($data["datasets"][$showDS]["data"], $sortOrder);

?>
                        <div id="graph" style="width: 100%; height: 450px;"></div>          
                        <script type="text/javascript">
                            g = new Dygraph(
                                document.getElementById("graph"),
                                <?= json_encode($data["datasets"][$showDS]["data"]) ?>,
                                { 
                                    labels: ["<?= isset($units["x"]) ? $units["x"] : "Undefined" ?>","<?= reset($viewTags) ?>"],
                                    xlabel: "<?= isset($units["x"]) ? $units["x"] : "Undefined" ?>", 
                                    ylabel: "<?= isset($units["y"]) ? $units["y"] : "Undefined" ?>",
                                    //drawYAxis: false,
                                    axisLabelFontSize: 10,
                                    yAxisLabelWidth: 70,
                                    colors: ["red", "black", "blue", "green"],
                                }
                            );
                            <?= isset($anno) ? "g.ready(function() { g.setAnnotations(" . $anno . "); });" : "" ?>
                        </script>
<?php
