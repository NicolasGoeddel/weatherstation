<?php

include_once('database.php');
include_once('station.php');

include('header.php');

?>
<div class="wrapper">
    <div id="sidebar">
        <div class="sidebar-header">
            <h3>Weather station</h3>
        </div>

        <ul class="list-unstyled components">
            <p>Menu</p>
            <li class="active">
                <a href="#stationSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">Stations</a>
                <ul class="collapse list-unstyled" id="stationSubmenu"><?php
                    foreach(Station::getStations() as $id => $station) { ?>
                    <li>
                        <a href="#" data-station-id="<?= $id ?>"><?= $station->getName() ?></a>
                    </li><?php } ?>
                </ul>
            </li>
        </ul>

    </div>
    <div id="content">
        <button type="button" id="sidebarCollapse" class="btn btn-outline-light">≡</button>
        <div class="chart-container container-fluid">
            <div id="chart-options-header" class="row justify-content-center">
                <div class="chart-options col-auto">
                        Intervals:
                        <button type="button" data-interval="1w"   class="interval-select btn btn-light">1w</button>
                        <button type="button" data-interval="1d"   class="interval-select btn btn-light">1d</button>
                        <button type="button" data-interval="6h"   class="interval-select btn btn-light">6h</button>
                        <button type="button" data-interval="1h"   class="interval-select btn btn-light">1h</button>
                        <button type="button" data-interval="15m"  class="interval-select btn btn-light">15m</button>
                        <button type="button" data-interval="all"  class="interval-select btn btn-light active">all</button>
                        <button type="button" data-interval="auto" class="interval-select btn btn-light">auto</button>
                </div>
                <div class="chart-options col-auto">
                        Range:
                        <button type="button" data-range="1m" class="range-select btn btn-light">1m</button>
                        <button type="button" data-range="1w" class="range-select btn btn-light">1w</button>
                        <button type="button" data-range="1d" class="range-select btn btn-light">1d</button>
                        <button type="button" data-range="1h" class="range-select btn btn-light">1h</button>
                </div>
            </div>
            <div class="row">
                <div class="chart-wrapper col-11">
                    <canvas class="datachart" data-color="#ff5f5f" data-type="temperature" height="300px" ></canvas>
                </div>
                <div class="col-1"></div>
                <div class="chart-wrapper col-11">
                    <canvas class="datachart" data-color="#5f5fff" data-type="humidity" height="300px" ></canvas>
                </div>
                <div class="col-1"></div>
                <div class="chart-wrapper col-11">
                    <canvas class="datachart" data-color="#3fff3f" data-type="pressure"  height="300px" ></canvas>
                </div>
                <div class="col-1"></div>
            </div>
        </div>
    </div>
</div>
<?php

include('footer.php');

?>