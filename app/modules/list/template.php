<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

/*
    requires $listTitle
             $listText
             $listNews (array)
             $listNewsColor
             $listContact
             $listReferences (array)



*/

?>

        <section class="section">
            <div class="container">
                <div class="columns is-desktop">
<?php             if ($listText): ?>
                    <div class="column">
                        <h1 class="title">About <?= $listTitle ?></h1>
                        <hr>
                        <div class="content">
                            <?= $listText ?> 
                        </div>
                    </div>
<?php             endif; ?>

<?php             if ($listNews or $listContact or $listReferences): ?>
                    <div class="column<?= $listText ? " is-narrow" : "" ?>">

<?php                 foreach ($listNews as $item): ?>
                        <div class="box <?= $listNewsColor ?>">
                            <div class="content">
                                <?= $item ?> 
                            </div>
                        </div>
<?php                 endforeach; ?>

<?php                 if ($listContact): ?>
                        <div class="box">
                            <h1 class="title">Contact</h1>
                            <hr>
                            <div class="content">
                                <?= $listContact ?> 
                            </div>
                        </div>
<?php                 endif; ?>

<?php                 if ($listReferences): ?>
                        <div class="box">
                            <h1 class="title">Related literature</h1>
                            <hr>
                            <div class="content">
                                <ul>
<?php                             foreach ($listReferences as $item): ?>
                                    <li><?= $listReferences ?></li>
<?php                             endforeach; ?>
                                </ul>   
                            </div>
                        </div>
<?php                 endif; ?>

                    <div>
<?php             endif; ?>
                </div>

<?php         if ($showLib != "_START"): ?>
                <script type="text/javascript" charset="utf-8">
                    $(document).ready(function() {
                        var oTable = $('#datatable').dataTable( {
                            //"sScrollY": "300px",
                            "bPaginate": false,
                            "bScrollCollapse": true,
                            "aoColumns": [ { "bSortable": false }, 
                                        <?php foreach ($columns as $column): echo "null, "; endforeach; ?> ]        
                        } );
                    new FixedHeader( oTable );
                    } );
                </script>

                <h1 class="title">Measurements in this library</h1>
                <hr>

                <table id="datatable" width="100%">
                    <thead>
                        <tr>
                            <th> </th>
<?php                     foreach ($listColumns as $name): ?>
                            <th><?= $name ?></th>
<?php                     endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
<?php                 foreach ($listData as $id => $line): ?>
                        <tr>
                            <td>
                                <a href="./index.php?lib=<?= $showLib ?>&id=<?= $id ?>">
                                    <span class="icon">
                                        <i class="fa fa-info-circle" aria-hidden="true"></i>
                                    </span>
                                </a>
                            </td>
<?php                     foreach ($line as $col => $value): ?>
                            <td><?= $value ?></td>
<?php                     endforeach; ?>
                        </tr>
<?php                 endforeach; ?>
                    </tbody>
                </table>

<?php         else:  // $showLib == "_START": START PAGE?>
                <h1 class="title">Available libraries</h1>
                <hr>
                <?= !$listLibs ? "<p>No libraries available</p>" : "" ?> 

<?php          foreach ($listLibs as $row => $libs): ?>
                <div class="tile is-ancestor">
<?php             foreach ($libs as $id => $lib): ?>
                    <div class="tile is-parent is-4">
                        <article class="tile is-child box <?= $lib[$color] ?>">
                            <p class="title"><?= $lib[$name] ?></p>
                            <p class="subtitle"><?= $lib[$catchphrase] ?></p>
                            <div class="content">
                                <?= $lib[$logobox] ?> 
                            </div>
                        </article>
                    </div>
<?php             endforeach; ?>
                </div>
<?php          endforeach; ?>
<?php         endif; ?>
            </div>
        </section>
