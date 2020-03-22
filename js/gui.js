
var selectedInterval = "1d";
var selectedStation = 1;

$(document).ready(function () {

    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
    });

    $('.interval-select').on('click', function() {
        selectedInterval = $(this).attr('data-interval');
        $('.interval-select').removeClass('active');
        $(this).addClass('active');
        for (var g in graphs) {
            reloadChart(graphs[g]);
        }
    });

});