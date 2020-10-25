var locale = window.navigator.userLanguage || window.navigator.language;

var graphs = {};

var allData = null;

function getData(config, success) {

  

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

  if (config.subtypes instanceof Array) {
    data.subtypes = config.subtypes.join(',');
  } else if (typeof(config.subtypes) === 'string') {
    data.subtypes = config.subtypes;
  }

  if (typeof(config.stepsize) === 'string') {
    data.stepsize = config.stepsize;
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

function reloadChart({chart}) {
  let typename = chart.options.typename;
  let typecolor = chart.options.typecolor;

  let axis = chart.scales.time;
  //let timestamps = axis._timestamps.data;

  let subtypes = ['data'];
  let stepsize = '1';
  if (gConf.interval === 'all' || gConf.interval === '') {
    stepsize = '1';
    subtypes = ['data'];
  } else if (gConf.interval === 'auto') {
    //todo
  } else {
    stepsize = gConf.interval;
    subtypes = ['data', 'min', 'max'];
  }

  oldAxis = {min: axis.min, max: axis.max};

  getData({
    station: gConf.station,
    start: moment(axis.min),
    end: moment(axis.max),
    types: typename,
    subtypes: subtypes,
    stepsize: gConf.interval
  }, function(data) {
    chart.data.datasets = subtypes.map(function(subtype, index) {
      let fill = false;
      if (subtype == 'max') {
        fill = subtypes.indexOf('min');
      } else if (subtype == 'data' && subtypes.length == 1) {
        fill = 'origin';
      }
      return {
        data: data['timestamp'].map(function(e, i) {
          return {
            x : e,
            y : data[typename][subtype][i]
          }
        }),
        pointRadius: subtype === 'data' && subtypes.length > 1 ? 3 : 0,
        lineTension: 0.3,
        borderColor: typecolor,
        borderWidth: subtype === 'data' ? 3 : 1,
        fill: fill,
        subtype: subtype
      }
    });
    chart.update();
    /*let meta = chart.getDatasetMeta(0);
    chart.scales[meta.xAxisID].min = oldAxis.min;
    chart.scales[meta.xAxisID].max = oldAxis.max;
    chart.update();
    console.log(oldAxis); */
  })
};

function syncChart({chart}) {
  return;
  if (gConf.sync) {
    for (let g in graphs) {
      if (graphs[g] != chart) {
        graphs[g].scales.time.min = chart.scales.time.min;
        graphs[g].scales.time.max = chart.scales.time.max;
        graphs[g].update();
        console.log('update ' + g + " with " + chart.scales.time.min + " and " + chart.scales.time.max);
      }
    }
  }
}

function updateChart({chart}) {

  reloadChart(chart);

  if (gConf.sync) {
    for (let g in graphs) {
      if (graphs[g] != chart) {
        graphs[g].scales.time.min = chart.scales.time.min;
        graphs[g].scales.time.max = chart.scales.time.max;
        reloadChart(graphs[g]);
      }
    } 
  }
  
  return;

  let typename = chart.options.typename;

  let axis = chart.scales.time
  let timestamps = axis._timestamps.data;
  let data = {min: timestamps[0], max: timestamps[timestamps.length - 1]};
  
  if (axis.max > data.max) {
    // Es wurde nach links verschoben, d.h. rechts fehlen jetzt Daten.
    getData({
      station: gConf.station,
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
        typecolor: dataColor,
        animation: false,
        responsive: true,
        maintainAspectRatio: false,
        legend: {
          display: false
        },
        title: {
          display: false,
        },
        spanGaps: false,
        scales: {
          xAxes: [{
            id: 'time',
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
            ticks: {
              suggestedMin: allData[type].typical_min,
              suggestedMax: allData[type].typical_max
            },
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
          axis: 'x',
          intersect: false,
          callbacks: {
            label: function(tooltipItem, data) {
              return data.datasets[tooltipItem.datasetIndex].subtype + ": " + tooltipItem.yLabel.toFixed(2) + " " + allData[type].unit;
            },
            title: function(tooltipItems, data) {
              return moment(tooltipItems[0].xLabel).format("LL LTS");
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
              onPan: syncChart,
              onPanComplete: updateChart
            },
            zoom: {
              enabled: false,
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
              onZoom: syncChart,
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

  parseHash()

  getData({
    station: gConf.station,
    start: gConf.start,
    end: gConf.end
  },
  function(data) {
    data['timestamp'] = data['timestamp'].map(x => new moment(x));
    allData = data;
    createGraphs();
  });

  /* Something is wrong here when no panning or zooming happend and it reloads
  setInterval(function() {
    for (var g in graphs) {
      updateChart(graphs[g]);
    }
  }, 20000);
  */

  $(document).keydown(function(event) {
    if (event.shiftKey) {
      for (let g in graphs) {
        graphs[g].options.plugins.zoom.zoom.enabled = true;
        graphs[g].update();
        console.log('enabled');
      }
    }
  });

  $(document).keyup(function(event) {
    if (event.shiftKey) {
      for (let g in graphs) {
        graphs[g].options.plugins.zoom.zoom.enabled = false;
        graphs[g].update();
        console.log('disabled');
      }
    }
  });

  $(window).on('hashchange', function(e) {
    console.log(e)
  })
});
