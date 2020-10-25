
var gConfDefault = {
    station: 1,
    sync: true,
    interval: "15m"
};

var gConf = {
    start: null,
    end: null,
    station: gConfDefault.station,
    sync: gConfDefault.sync,
    interval: gConfDefault.interval
};

const timeFormat = 'YYYY-MM-DDTHH:mm:ss';

function validateConf() {
    if (gConf.start && gConf.end) {
        if (gConf.start == gConf.end) {
        gConf.end = gConf.start.clone();
        }
        if (gConf.end.unix() == gConf.start.unix()) {
        gConf.start.subract(12, 'hours');
        gConf.end.add(12, 'hours');
        } else if (gConf.end < gConf.start) {
        let t = gConf.end;
        gConf.end = gConf.start;
        gConf.start = t;
        }
    } else if (gConf.start) {
        gConf.end = gConf.start.clone().add(1, 'days');
    } else if (gConf.end) {
        gConf.start = gConf.end.clone().subtract(1, 'days');
    } else {
        let t = moment();
        gConf.start = t.clone().subtract(2, 'days');
        gConf.end = t.clone().add(1, 'days');
    }

    if (!Number.isInteger(gConf.station)) {
        gConf.station = gConfDefault.station;
    }
    if (!(typeof gConf.sync == 'boolean')) {
        gConf.sync = gConfDefault.sync;
    }
    if (gConf.interval == null) {
        gConf.interval = gConfDefault.interval;
    }
}

function parseHash() {
    param = new URLSearchParams(window.location.hash.substr(1));

    gConf.start = null
    if (param.get('start')) {
        gConf.start = moment(param.get('start'));
    }
    gConf.end = null
    if (param.get('end')) {
        gConf.end = moment(param.get('end'));
    }
    gConf.station = parseInt(param.get('station'));
    gConf.sync = param.get('sync');
    gConf.interval = param.get('interval');

    validateConf()
}

function updateHash() {
    param = new URLSearchParams();

    if (gConf.start) {
        param.set('start', gConf.start.format(timeFormat));
    }
    if (gConf.end) {
        param.set('end', gConf.end.format(timeFormat));
    }
    if (gConf.station != gConfDefault.station) {
        param.set('station', gConf.station);
    }
    if (gConf.sync != gConfDefault.sync) {
        param.set('sync', gConf.sync ? '1' : '0');
    }
    if (gConf.interval != gConfDefault.interval) {
        param.set('interval', gConf.interval);
    }

    window.location.hash = '#' + param.toString();
}

$(document).ready(function () {

    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
    });

    $('.interval-select').on('click', function() {
        gConf.interval = $(this).attr('data-interval');
        updateHash();
        $('.interval-select').removeClass('active');
        $(this).addClass('active');
        for (var g in graphs) {
            reloadChart(graphs[g]);
        }
    });

});