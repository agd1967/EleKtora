<?php
 
//set local datetime for php time
date_default_timezone_set('America/New_York');

/*Conectar la conexion a la base de datos*/
function conectarDB(){ 
	$servername = "localhost";
	$username = "root";
	$password = "";
	$dbname = "elektora";
    
	// Create connection
	$conn = mysqli_connect($servername, $username, $password, $dbname); 
    
	// Check connection
	if (!$conn) {
		die("Connection failed: " . mysqli_connect_error());
	}
	//echo "Connected successfully";
        
	//devolvemos el objeto de conexión para usarlo en las consultas  
    return $conn; 
}  

/*Desconectar la conexion a la base de datos*/
function cerrarDB($conn){
	
	//Cierra la conexión y guarda el estado de la operación en una variable
    $close = mysqli_close($conn); 
    
	//Comprobamos si se ha cerrado la conexión correctamente
	if (!$close) {
		die("Close connection failed: " . mysql_error());
	}
	//echo "Disconnected successfully";
	
    //devuelve el estado del cierre de conexión
    return $close;         
}

//Devuelve un array multidimensional con el resultado de la consulta
function getArraySQL($conexion,$sql){
    
	//generamos la consulta
    if(!$result = mysqli_query($conexion, $sql)) die();
	$rawdata = array();
    
    //guardamos en un array multidimensional todos los datos de la consulta
	$i=0;
    while($row = mysqli_fetch_array($result)) {               
		//guardamos en rawdata todos los vectores/filas que nos devuelve la consulta
        $rawdata[$i] = $row;
        $i++;
    }
    
	//devolvemos rawdata
    return $rawdata;
}

//Crea un archivo CSV con el resultado de la consulta
function getDataFile($conexion,$sql){
	
	$filename = 'C:/wamp/www/wpcalc/wp-content/uploads/data/datafile.csv';  
    
	if(!$result = mysqli_query($conexion, $sql)) die();
	$fp = fopen($filename, 'w');
	
    $header = true;
    while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        if ($header) {
            
			$hdr = array_keys($row);
			foreach ($hdr as $k => $val) {
				$hdr[$k] = $val."#@ @#";
			}	
			
			
			
			
			//fputcsv($fp, array_keys($row));
			fputcsv($fp, $hdr);
            $header = false;
        }
		
		 

		foreach ($row as $key => $value) {
			$row[$key] = $value."#@ @#";
		}
		
        fputcsv($fp, $row);
    }
    fclose($fp);
	$contents = file_get_contents($filename);
	$contents = str_replace("#@ @#", "", $contents);
	file_put_contents($filename, $contents);
}

//Devuelve un array multidimensional con el resultado de la lista de seleccion
function getSelection($selection){
	
	$res_sel_list = array('Select...'=>'s00',
						'Participation by Country'=>'s01',
						'Participation by Regional Organization'=>'s02',
						'Participation by Election Year - Registered Voters'=>'s03',
						'Participation by Election Year - Voting Age Population'=>'s04',
						'Electoral Inclusion Index (EII) by Election Year'=>'s05',
						'EleKtoral Map'=>'s06');  
		   	
	$options = '';
	while(list($tname, $tval)=each($res_sel_list)) {
		if($selection == $tval) {
			$options.='<option value="' .$tval .'" selected >' .$tname .'</option>';
		} else {
			$options.='<option value="' .$tval .'" >' .$tname .'</option>';
		}
	}

    return $options;
}

//Devuelve un array multidimensional con el resultado de media list
function getTypeChart($selected_typ){
	
	$res_type_list = array('All...'=>'ALL',
						   'Presidential Election'=>'PRE',
						   'Parliamentary Election'=>'PAR',
						   'Presidential and Parliamentary Elections'=>'TOD');
	
	$options = '';
	while(list($tname, $tval)=each($res_type_list)) {
		if($selected_typ == $tval) {
			$options.='<option value="' .$tval .'" selected >' .$tname .'</option>';
		} else {
			$options.='<option value="' .$tval .'" >' .$tname .'</option>';
		}
	}
	
    return $options;
}

//Devuelve un string con el SELECT statement according to selection criteria
function getSelectSQL($selection, $selected_typ, $selected_lev){

	$squery = '';
	if($selection == "s01") {
		//Bild query for country bar
		$squery = 'SELECT * FROM elektora.last_participation WHERE country_id > 0 ORDER BY country_id';
				
	} elseif($selection == "s02") {
		//Bild query for region bar
		$squery = 'SELECT * FROM elektora.last_participation WHERE country_id > 0 ORDER BY country_id';
		
	} elseif($selection == "s03") {
		//Bild query for reg participation chart
		$sql="SELECT Concat(participation.Country,(If(participation.Compulsory='Yes','*',''))) As Country ";
		$current_year = date("Y");
		for ($y = 1982; $y <= $current_year; $y++) {
			$sql .= ", max(IF(Year_Ele =" . $y . ",Registered_Participation, NULL)) AS '" . $y . "'";
		} 
		$sql .= " FROM elektora.participation ";
		if($selected_typ == "PRE" or $selected_typ == "PAR") { 
			sql .= " WHERE upper(substr(participation.Type, 1, 3)) = '" . $selected_typ . "'"; 
		} 
		$sql .= " GROUP BY Country_Id ORDER BY Country_Id, Year_ELE";
		
		$squery = $sql;
		
	} elseif($selection == "s04") {
		//Bild query for vap participation chart
		$sql="SELECT Concat(participation.Country,(If(participation.Compulsory='Yes','*',''))) As Country ";
		$current_year = date("Y");
		for ($y = 1982; $y <= $current_year; $y++) {
			$sql .= ", max(IF(Year_Ele =" . $y . ",Voting_Age_Participation, NULL)) AS '" . $y . "'";
		} 
		$sql .= " FROM elektora.participation ";
		if($selected_typ == "PRE" or $selected_typ == "PAR") { 
			sql .= " WHERE upper(substr(participation.Type, 1, 3)) = '" . $selected_typ . "'"; 
		} 
		$sql .= " GROUP BY Country_Id ORDER BY Country_Id, Year_ELE";
		
		$squery = $sql;

	} elseif($selection == "s05") {
		//Bild query for index inclusion chart			
		$sql="SELECT Concat(participation.Country,(If(participation.Compulsory='Yes','*',''))) As Country ";
		$current_year = date("Y");
		for ($y = 1982; $y <= $current_year; $y++) {
			$sql .= ", max(IF(Year_Ele =" . $y . ",Electoral_Inclusion_Index, NULL)) AS '" . $y . "'";
		} 
		$sql .= " FROM elektora.participation ";
		if($selected_typ == "PRE" or $selected_typ == "PAR") { 
			sql .= " WHERE upper(substr(participation.Type, 1, 3)) = '" . $selected_typ . "'"; 
		} 
		$sql .= " GROUP BY Country_Id ORDER BY Country_Id, Year_ELE";
		
		$squery = $sql;
		
	} elseif($selection == "s06") {
		//Bild query for electoral map				
		$reg_sql="SELECT Country, Siglas, upper(substr(participation.Type, 1, 3)) as Type, Country_Id ";
		$vap_sql="SELECT Country, Siglas, upper(substr(participation.Type, 1, 3)) as Type, Country_Id ";
		$idx_sql="SELECT Country, Siglas, upper(substr(participation.Type, 1, 3)) as Type, Country_Id ";
		$current_year = date("Y");
		for ($y = 1982; $y <= $current_year; $y++) {
				$reg_sql .= ", max(IF(Year_Ele =" . $y . ",Registered_Participation, NULL)) AS '" . $y . "'";
				$vap_sql .= ", max(IF(Year_Ele =" . $y . ",Voting_Age_Participation, NULL)) AS '" . $y . "'";
				$idx_sql .= ", max(IF(Year_Ele =" . $y . ",Electoral_Inclusion_Index, NULL)) AS '" . $y . "'";
		} 
		$reg_sql .= " FROM elektora.participation ";
		$vap_sql .= " FROM elektora.participation ";
		$idx_sql .= " FROM elektora.participation ";
			
		if($selected_typ == "PRE" or $selected_typ == "PAR") { 
			$reg_sql .= " WHERE upper(substr(participation.Type, 1, 3)) = '" . $selected_typ . "'"; 
			$vap_sql .= " WHERE upper(substr(participation.Type, 1, 3)) = '" . $selected_typ . "'";
			$idx_sql .= " WHERE upper(substr(participation.Type, 1, 3)) = '" . $selected_typ . "'";
		} 
		$reg_sql .= " GROUP BY Country_Id ORDER BY Country_Id, Year_ELE";
		$vap_sql .= " GROUP BY Country_Id ORDER BY Country_Id, Year_ELE";
		$idx_sql .= " GROUP BY Country_Id ORDER BY Country_Id, Year_ELE";
			
		$squery = $reg_sql;
		
	} else {
		$squery = 'SELECT NOW() FROM DUAL'; }
	
	return $squery;
}

		
	//Read Selected Chart
	$selection = "";
	if(isset($_POST['myselect'])) 
		$selection = $_POST['myselect'];  // Storing Selected Value In Variable

	//Read Selected Type of Election
	$selected_typ = "";
	if(isset($_POST['myeletyp'])) 
		$selected_typ = $_POST['myeletyp'];  // Storing Selected Value In Variable

	//creamos la conexión
    $conexion = conectarDB();
		
	// allow characters with accents
	mysqli_set_charset( $conexion, 'utf8');
	
	// data for charts and map
	$qry = getSelectSQL($selection, $selected_typ, "main");
	$res = getArraySQL($conexion, $qry);
	
	//create CSV file for map
	if($selection == "s06") {	
		// data for detailed map
		$dqry = getSelectSQL($selection, $selected_typ, "detailed");
		getDataFile($conexion, $dqry);	
	}
		
	//cerramos la base de datos
	//cerrarDB($conexion);
	//unset($conexion);
	
	//Determina selection criteria
	
     $mytitles = "";
     $mysubtit = "";
     $mytypech = "";
 
     $myfontsz = "";
     $mydatlab = true;
     $mydatbox = true;

    //title and others 
	if($selection == "s01") {
		$mytitles = "Participation by Country...";
        $mytypech = "column";
        $myfontsz = "14px";
        $mydatlab = true;
        $mydatbox = false;

    } elseif($selection === "s02") {
        $mytitles = "Participation by Regional Organization...";
        $mytypech = "column";
        $myfontsz = "14px";
        $mydatlab = true;
        $mydatbox = false;

    } elseif ($selection === "s03") {
        $mytitles = "Participation by Election Year - Voting Age Population...";
        $mytypech = "line";
        $myfontsz = "10px";
        $mydatlab = false;
        $mydatbox = true;

    } elseif($selection === "s04") {
        $mytitles = "Participation by Election Year - Voters Registration...";
        $mytypech = "line";
        $myfontsz = "10px";
        $mydatlab = false;
        $mydatbox = true;

    } elseif($selection === "s05") {
        $mytitles = "Electoral Inclusion Index (EII) by Election Year...";
        $mytypech = "line";
        $myfontsz = "10px";
        $mydatlab = false;
        $mydatbox = true;
	
	} elseif($selection === "s06") {
        $mytitles = "EleKtoral Map...";
        $mytypech = "Map";
        $myfontsz = "10px";
        $mydatlab = false;
        $mydatbox = true;

    } else {
        $mytitles = "No Chart was selected...";
    }

    //subtitle
    if($selected_typ === "PRE") { 
		$mysubtit = "( Presidential Election )"; 
	} elseif($selected_typ === "PAR") { 
		$mysubtit = "( Parliamentary Election )"; 
	} else { 
		$mysubtit = "( Presidential and Parliamentary Elections )";    
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>EleKtora</title>

	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
	<style type="text/css">
	
		#wrapper {
			height: 500px;
			width: 980px;
			margin: 50px auto;
			padding: 0;
		}
	
        #map {
            height: 680px;
            min-width: 310px;
            max-width: 800px;
			width: 70%;
            margin: 0 auto;
			float: left;
        }
		
		#info {
			width: 270px;
			padding-left: 20px;
			margin: 100px 0 0 0;
			border-left: 1px solid silver;
			float: right;
		}
		#info h2 {
			display: inline;
			font-size: 13pt;
		}
		#info .f32 .flag {
			vertical-align: bottom !important;
		}
		#info h4 {
			margin: 1em 0 0 0;
		}
    </style>
	
    <!-- Add the JavaScript to initialize the chart on document ready -->
    <script type="text/javascript">

	//Draw map if selected
	<?php if($selection == "s06") { ?>

	$(function () {
		
		$.getJSON('http://localhost/wpcalc/wp-content/uploads/data/jsonp.php?filename=datafile.csv&callback=?', function (csv) {
		//$.getJSON('https://www.highcharts.com/samples/data/jsonp.php?filename=world-population-history.csv&callback=?', function (csv) {
	

			// Very simple and case-specific CSV string splitting
			function CSVtoArray(text) {
				return text.replace(/^"/, '')
					.replace(/",$/, '')
					.split('","');
			}

			csv = csv.split(/\n/);

			var countries = {},
				mapChart,
				countryChart,
				numRegex = /^[0-9\.]+$/,
				quoteRegex = /\"/g,
				categories = CSVtoArray(csv[0]).slice(4);	
			
			// Parse the CSV into arrays, one array each country
			$.each(csv.slice(1), function (j, line) {
				var row = CSVtoArray(line),
					data = row.slice(4);

				$.each(data, function (i, val) {

					val = val.replace(quoteRegex, '');
					
					if (numRegex.test(val)) {
						val = parseInt(val, 10);
					} else if (!val) {
						val = null;
					}
					data[i] = val;
				
				});
				
				countries[row[1]] = {  //before 1
					name: row[0], 
					code3: row[1], 
					//hc-key: row[1], 
					data: data
				
				};
			});
			
			// For each country, use the latest value for current participation
			var data = [];
			
			for (var code3 in countries) {
				//if (countries.hasOwnProperty(code3)) {
				var value = null,
					year,
					itemData = countries[code3].data,
					i = itemData.length;
				while (i--) {
						
					if (typeof itemData[i] === 'number') {
							
						value = itemData[i];
						year = categories[i].substr(0, 4);					
						break;
					}
				}
				//alert(countries[code3].name);
				//alert(code3);
				//alert(value);
				if (i > 0) {	
					data.push({
						name: countries[code3].name,
						code3: code3,
						value: value,
						year: year
					});
				}
			//}
			}
			
			// Add lower case codes to the data set for inclusion in the tooltip.pointFormat
			var mapData = Highcharts.geojson(Highcharts.maps['custom/world']);
			$.each(mapData, function () {
				this.id = this.properties['hc-key']; // for Chart.get()
				this.flag = this.id.replace('UK', 'GB').toLowerCase();
			});
			
			// Wrap point.select to get to the total selected points
			Highcharts.wrap(Highcharts.Point.prototype, 'select', function (proceed) {
				
				proceed.apply(this, Array.prototype.slice.call(arguments, 1));
				
				var points = mapChart.getSelectedPoints();
				
				if (points.length) {
					if (points.length === 1) {
						$('#info #flag').attr('class', 'flag ' + points[0].flag);
						$('#info h2').html(points[0].name);
					} else {
						$('#info #flag').attr('class', 'flag');
						$('#info h2').html('Comparing countries');
					}
					$('#info .subheader').html('<h4>Participation of Registered Voters</h4><small><em>Shift + Click on map to compare countries</em></small>');
					
					if (!countryChart) {
						countryChart = $('#country-chart').highcharts({
							chart: {
								height: 250,
								spacingLeft: 0
							},
							credits: {
								enabled: false
							},
							title: {
								text: null
								},
							subtitle: {
								text: null
								},
							xAxis: {
								tickPixelInterval: 50,
								crosshair: true
							},
							yAxis: {
								title: null,
								opposite: true
							},
							tooltip: {
								shared: true
							},
							plotOptions: {
								series: {
									animation: {
										duration: 500
									},
									marker: {
										enabled: false
									},
									connectNulls: true,
									threshold: 0,
									pointStart: parseInt((categories[0].substr(0, 4)), 10)
								}
							}
						}).highcharts();
					}

					$.each(points, function (i) {
						// Update
						
						if (countryChart.series[i]) {
							/*$.each(countries[this.code3].data, function (pointI, value) {
								countryChart.series[i].points[pointI].update(value, false);
							});*/
							
							countryChart.series[i].update({
								name: this.name,
								data: countries[this.code3].data,
								type: points.length > 1 ? 'line' : 'area'
							}, false);
						} else {
							
							
							countryChart.addSeries({
								name: this.name,
								data: countries[this.code3].data,
								type: points.length > 1 ? 'line' : 'area'
							}, false);
						}
					});
					while (countryChart.series.length > points.length) {
						countryChart.series[countryChart.series.length - 1].remove(false);
					}
					countryChart.redraw();

				} else {
					$('#info #flag').attr('class', '');
					$('#info h2').html('');
					$('#info .subheader').html('');
					if (countryChart) {
						countryChart = countryChart.destroy();
					}
				}

			});
			
			// Initiate the map chart
			mapChart = $('#map').highcharts('Map', {

				title: {
					text: 'EleKtoral Map'
				},
				subtitle: {
					text: ''
				},
				credits: {
					enabled: false
				},
				exporting: {
					enabled: false
				},
				mapNavigation: {
					enabled: true,
					buttonOptions: {
						verticalAlign: 'bottom'
					}
				},

				colorAxis: {
					type: 'logarithmic',
					endOnTick: false,
					startOnTick: false,
					min: 50000
				},
				
				//tooltip: {
				//	footerFormat: '<span style="font-size: 10px">(Click for details)</span>'
				//},
				tooltip: {
					backgroundColor: 'none',
					borderWidth: 0,
					shadow: false,
					useHTML: true,
					padding: 0,
					pointFormat: '<span class="f32"><span class="flag {point.code3}"></span></span>' +
								' {point.name}: <br><b>{point.value:.2f}</b> % Registered Participation' +
								'<br><b>{point.value:.2f}</b> % Voting Age Participation',
					positioner: function () { 
						return { x: 0, y: 250 };
					}
				},		

				series : [{
					allAreas: false,
					data: data,
					mapData: mapData,
					joinBy: ['hc-key', 'code3'],
					name: 'EleKtoral Map',
					allowPointSelect: true,
					cursor: 'pointer',
					states: {
						select: {
							color: '#a4edba',
							borderColor: 'black',
							dashStyle: 'shortdot'
						}
					}
				}]
			}).highcharts();
			
			// Pre-select a country
			mapChart.get('ca').select();
		});				 
	});
	<?php } else { ?>
                           
		
	//allow highlighting of individual series while muting the others
        function showSeries(e) {
            var d;
         
            //enable and disable datalabels for line
            if (e.checked == true) {
                if (this.type == "line") {
                    this.update({ dataLabels: { enabled: e.checked } });
                    this.graph.attr("stroke", this.color);
                } else {
                    for (d = 0; d < this.data.length; d++) {
                        this.data[d].graphic.attr({ "fill": this.color });
                    }
                }
                this.group.toFront();
                if (this.visible == false) {
                    this.show();
                }

            } else {
                if (this.type == "line") {
                    this.update({ dataLabels: { enabled: e.checked } });
                    this.select();
                    this.graph.attr("stroke", "#e6e6e6");
                } else {
                    for (d = 0; d < this.data.length; d++) {
                        this.data[d].graphic.attr({ "fill": "#e6e6e6" });
                    }
                }                
            }
        }

        function highlightSer(chart) {
            var series = chart.series, i;
            for (i = 0; i < series.length; i++) {
                if (series[i].checkbox.checked) {
                    showSeries.call(series[i], { checked: true });
                }
            }
        }

        // Initiate the chart
        $(function () {
			
			$('#container').highcharts({
				
				<?php if($selection != "s01") { ?>
				chart: {
					renderTo: 'container'
					type: <?php echo $mytypech ?>,
                    defaultSeriesType: <?php echo $mytypech ?>,			
					gridLineColor: '#ddd',
					max: 500,
                    showAxes: true
                },
                <?php } ?>
				
				title: {
					text: <?php echo $mytitles ?> 
                },
                subtitle: {
					text: <?php echo $mysubtit ?>  
                },

                credits: {
                    enabled: false
                },

				legend: {
					layout: 'vertical',
                    align: 'right',
                    verticalAlign: 'top',
                    backgroundColor: '#fff',
                    borderColor: '#ccc',
                    borderWidth: .5,
                    y: 0,
                    x: 0,
                    itemWidth: 180,
                    itemStyle: {
						fontWeight: 'bold',
						fontSize: <?php echo $myfontsz ?> 
                    },
                    itemHiddenStyle: {
						fontWeight: 'bold',
                        fontSize: <?php echo $myfontsz ?> 
                    }
                },

                xAxis: {
                    lineColor: '#999',
                    lineWidth: 1,
                    tickColor: '#666',
                    tickLength: 3,
                    title: {
                        style: {
                            color: '#333'
                        }
                    },

					categories: ['Apples', 'Oranges', 'Pears', 'Bananas', 'Plums']
                },    

                yAxis: {
                    max: 100,
                    lineColor: '#999',
                    lineWidth: 1,
                    tickColor: '#666',
                    tickWidth: 1,
                    tickLength: 3,
                    gridLineColor: '#ddd',
                    title: {
                        text: '%',
                        rotation: 0,
                        margin: 20,
                        style: {
                            color: '#333'
						}
                    },
                    labels: {
                        style: {
                            fontSize: '10px',
                            color: '#333',
                        },
                        margin: 10,
                        formatter: function () {
                            var uom = '';
                            if (this.isLast) {
                                uom = ' %';
                            }
                            return this.value + uom;
                        }
                    },
                },

                plotOptions: {
                    series: {
                        dataLabels: {
                            enabled: true 
                        },

                        connectNulls: true,
                        lineColor: '#e6e6e6', 
                        showCheckbox: false,
                        shadow: false,
                        lineWidth: 3,
                        marker: {
                            symbol: 'circle',
                            radius: 0,
                            states: {
                                hover: {
                                    radius: 3
                                }
                            }
                        },
                        events: {
                            checkboxClick: showSeries
                        }
                    }
                },

                series: [{
					type: 'column',
					name: 'Jane',
					data: [3, 2, 1, 3, 4]
				}, {
					type: 'column',
					name: 'John',
					data: [2, 3, 5, 7, 6]
				}, {
					type: 'column',
					name: 'Joe',
					data: [4, 3, 3, 9, 0]
				}, {
					type: 'spline',
					name: 'Average',
					data: [3, 2.67, 3, 6.33, 3.33],
				marker: {
					lineWidth: 2,
					lineColor: Highcharts.getOptions().colors[3],
					fillColor: 'white'
				}
				
				}, {
					type: 'pie',
					name: 'Total consumption',
					data: [{
						name: 'Jane',
						y: 13,
						color: Highcharts.getOptions().colors[0] // Jane's color
					}, {
						name: 'John',
						y: 23,
						color: Highcharts.getOptions().colors[1] // John's color
					}, {
						name: 'Joe',
						y: 19,
						color: Highcharts.getOptions().colors[2] // Joe's color
					}],
					center: [100, 80],
					size: 100,
					showInLegend: false,
					dataLabels: {
						enabled: false
					}
                 }];

	

         

				//---------------------------------------------------------------------------
				// ---- show/hide/check/un-check all series
				$('#showAll').click(function () {
					for (i = 0; i < chart.series.length; i++) {
						chart.series[i].show();
					}
				});

				$('#hideAll').click(function () {
					for (i = 0; i < chart.series.length; i++) {
						chart.series[i].hide();
					}
				});

				$('#chckAll').click(function () {
					for (i = 0; i < chart.series.length; i++) {
						if (chart.series[i].selected == false) {
							chart.series[i].select();                   
							showSeries.call(chart.series[i], { checked: true });
						}
					}
				});

				$('#unckAll').click(function () {
					for (i = 0; i < chart.series.length; i++) {
						if (chart.series[i].selected == true) {
							chart.series[i].select();
							showSeries.call(chart.series[i], { checked: false });
						}
					}
				});

				//reset the chart to original specs
				$('#resetCh').click(function () {
					chart.destroy();
					hart = new Highcharts.Chart(options, highlightSer);
				});
				//---------------------------------------------------------------------------
			});
		});   
	<?php } ?>

	</script>
</head>
<body>

    <script src="http://code.highcharts.com/highcharts.js"></script>
	<script src="http://code.highcharts.com/highcharts-3d.js"></script>
    <script src="http://code.highcharts.com/maps/modules/map.js"></script>
	<script src="http://code.highcharts.com/mapdata/custom/world.js"></script>

	<!-- Flag sprites service provided by Martijn Lafeber, https://github.com/lafeber/world-flags-sprite/blob/master/LICENSE -->
	<link rel="stylesheet" type="text/css" href="//cloud.github.com/downloads/lafeber/world-flags-sprite/flags32.css"/>


    <!-- Add the Chart Options to a selection dropdown menu -->
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" style="display: inline-block;">
        Select Chart option:
        <select id="myselect" name="myselect" type="text" style="font-weight: bold">
			<?php echo getSelection($selection, $conexion); ?>
        </select>
        Type of Election:
        <select id="myeletyp" name="myeletyp" type="text" style="font-weight: bold">
            <?php echo getTypeChart($selected_typ, $conexion); ?>
        </select>
		
		<!-- Add the Submit Button -->
		<button type="button" style="display: inline-block; margin-left:10px" onclick="this.form.submit()">Submit Selection</button>
	</form>
   
	<!--<ul class="f32"> -->
	<!--	<li class="flag ar">Argentina</li> -->
	<!-- </ul> -->
	
	<!-- Defined Area for Mapo -->
	<?php if($selection == "s06") { ?>
	<!-- X. Add Map -->
	<div id="wrapper">	
		<div id="map"></div>
		<div id="info">
			<span class="f32"><span id="flag"></span></span>
			<h2></h2>
			<div class="subheader">Click countries to view participation history</div>
			<div id="country-chart"></div>
		</div>
		
	</div>
	
	
	<?php } else { ?>
	<!-- Defined Area for Charts -->
    <div id="container" style="width: 100%; height: 600px; margin: 0 auto"></div>
	<?php } ?>
	
	
	
	
</body>
</html>
