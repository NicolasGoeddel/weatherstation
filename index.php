<?php

include_once('database.php');
include_once('station.php');

include('header.php');

?>
<div class="chart-container container-fluid">
    <div class="row">
        <div class="col-sm-0 col-md-3 col-lg-4"></div>
        <div class="col-sm-12 col-md-6 col-lg-4">
            <table class="table">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Name</th>
                </tr>
            </thead>
            <tbody><?php
foreach(Station::getStations() as $id => $station) {
?>
                <th scope="row"><?= $id ?></th>
                <td><?= $station->getName() ?></td>
<?php
}
?>
            </tbody>
        </table>
        </div>
        <div class="col-sm-0 col-md-3 col-lg-4"></div>
    </div>
    <div class="row">
        <div class="chart-wrapper col-sm-12 col-md-12 col-lg-6">
            <canvas class="datachart" data-type="temperature" width="100%" height="100%" ></canvas>
        </div>
        <div class="chart-wrapper col-sm-12 col-md-12 col-lg-6">
            <canvas class="datachart" data-type="humidity" width="100%" height="100%" ></canvas>
        </div>
        <div class="chart-wrapper col-sm-12 col-md-12 col-lg-6">
            <canvas class="datachart" data-type="pressure" width="100%" height="100%" ></canvas>
        </div>
    </div>
</div>
<?php

include('footer.php');

?>