var locale = window.navigator.userLanguage || window.navigator.language;

var graphs = {};

var allData = null;

function getData(config, success) {

  const timeFormat = 'YYYY-MM-DDTHH:mm:ss';

  data = {
    module: 'data',
    action: 'get'
  };
  if (!('station' in config)) {
    return false;
  }
  data.station = config.station;
  if (config.start instanceof Date) {
    data.start = moment(config.start).format(timeFormat);
  } else if (config.start instanceof moment) {
    data.start = config.start.format(timeFormat);
  }

  if (config.end instanceof Date) {
    data.end = moment(config.end).format(timeFormat);
  } else if (config.end instanceof moment) {
    data.end = config.end.format(timeFormat);
  }

  if (config.types instanceof Array) {
    data.types = config.types.join(',');
  } else if (typeof(config.types) === 'string') {
    data.types = config.types;
  }

  $.ajax({
    type: "GET",
    url: baseUrl + "/api.php",
    data: data,
    dataType: 'json',
    success: success,
    error: function (xhr, textStatus, errorThrown) {
      console.error(textStatus);
      console.error(errorThrown);
    }
  });
};

function updateChart({chart}) {

  let meta = chart.getDatasetMeta(0);
  let typename = chart.options.typename;

  let axis = chart.scales[meta.xAxisID];
  let timestamps = axis._timestamps.data;
  let data = {min: timestamps[0], max: timestamps[timestamps.length - 1]};
  
  if (axis.max > data.max) {
    // Es wurde nach links verschoben, d.h. rechts fehlen jetzt Daten.
    getData({
      station: 1,
      start: moment(data.max + 1000),
      end: moment(axis.max),
      types: typename
    }, function(data) {

      data['timestamp'].forEach(function(t, index) {
        chart.data.datasets[0].data.push({
          x: moment(t),
          y: data[typename].data[index]
        });
      });
      chart.data.datasets[0].data.sort((a, b) => a.x - b.x);
      chart.update();
    }
    );
  }
  if (axis.min < data.min) {
    // Es wurde nach rechts verschoben, d.h. links fehlen jetzt Daten.
    getData({
      station: 1,
      start: moment(axis.min),
      end: moment(data.min - 1000),
      types: typename
    }, function(data) {

      data['timestamp'].reverse().forEach(function(t, index, timestamps) {
        chart.data.datasets[0].data.unshift({
          x: moment(t),
          y: data[typename].data[timestamps.length - index - 1]
        });
      });
      chart.data.datasets[0].data.sort((a, b) => a.x - b.x);
      chart.update();
    }
    );
  }
}

function createGraphs() {
  $('.datachart').each(function(index, value) {
    var type = $(value).attr('data-type');
    var dataColor = $(value).attr('data-color') || "#3e95cd";

    graphs[type] = new Chart(value, {
      type: 'line',
      data: {
        datasets: [{
          data: allData['timestamp'].map(function(e, i) {
            return {'x': e, 'y': allData[type].data[i]};}),
          label: type + " in " + allData[type].unit,
          borderColor: dataColor,
          fill: 'origin',
          pointRadius: 0,
          lineTension: 0.3
        }]
      },
      options: {
        typename: type,
        animation: false,
        responsive: true,
        maintainAspectRatio: false,
        title: {
          display: false,
        },
        spanGaps: false,
        scales: {
          xAxes: [{
            type: 'time',
            distribution: 'linear',
            scaleLabel: {
              display: false,
              labelString: "Time"
            },
            ticks: {
              fontSize: 9,
              major: {
                enabled: true,
                fontStyle: 'bold',
                fontSize: 11,
                callback: function(value, index, values) {
                  let majorDiff = null;
                  for (i = index - 1; i >= 0; i--) {
                    if (values[i].major) {
                      majorDiff = (values[index].value - values[i].value) / 1000;
                      break
                    }
                  }

                  let m = moment(values[index].value);
                  if (m.hours() == 0 && m.minutes() == 0 && m.seconds() == 0) {
                    return m.format('L');
                  }
                  if (majorDiff === null) {
                    return m.format('L LT');
                  }
                  if (majorDiff < 60) {
                    return m.format('LTS');
                  }
                  if (majorDiff < 86400) {
                    return m.format('LT');
                  }
                  return m.format('LL');
                }
              },
              callback: function(value, index, values) {
                let m = moment(values[index].value);
                if (m.seconds() != 0) {
                  return m.format('LTS');
                }
                return m.format('LT');
              }
            }
          }],
          yAxes: [{
            position: 'left',
            scaleLabel: {
              display: true,
              labelString: allData[type].unit,
            }
          }]
        },
        tooltips: {
          enabled: true,
          mode: 'nearest',
          intersect: false,
          callbacks: {
            label: function(tooltipItems, data) {
              return tooltipItems.yLabel + " " + allData[type].unit;
            }
          }
        },
        plugins: {
          zoom: {
            pan: {
              enabled: true,
              mode: 'x',
              rangeMin: {
                x: null,
                y: null
              },
              rangeMax: {
                x: null,
                y: null
              },
              onPanComplete: updateChart
            },
            zoom: {
              enabled: true,
              mode: 'x',
              speed: 0.1,
              rangeMin: {
                x: null,
                y: null
              },
              rangeMax: {
                x: null,
                y: null
              },
              onZoomComplete: updateChart
            }
          }
        }
      }
    });    

  });
};

// https://demos.jquerymobile.com/1.3.2/faq/dom-ready-not-working.html

$(document).ready(function() {
  
  moment.locale(locale);

  getData({
    station: 1,
    start: moment().subtract(1, 'days'),
    end: moment().add(1, 'days')
  },
  function(data) {
    data['timestamp'] = data['timestamp'].map(x => new moment(x));
    allData = data;
    createGraphs();
  });

  setInterval(function() {
    for (var g in graphs) {
      updateChart(graphs[g]);
    }
  }, 20000);

  $(document).keydown(function(event) {
    if (event.shiftKey) {
      for (var g in graphs) {
        graphs[g].options.plugins.zoom.zoom.enabled = true;
        console.log('enabled');
      }
    }
  });

  $(document).keyup(function(event) {
    if (event.shiftKey) {
      for (var g in graphs) {
        graphs[g].options.plugins.zoom.zoom.enabled = false;
        console.log('disabled');
      }
    }
  });
});
