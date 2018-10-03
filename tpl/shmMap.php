<?php

function draw_shMap($map, $args )
{
	$id 		= $map->id;
	$uniq		= "ShmMap$id".$args['uniq'];
	$title		= $map->get("post_title");
	$latitude	= $map->get_meta("latitude");
	$longitude	= $map->get_meta("longitude");
	$is_lock	= $map->get_meta("is_lock");
	$is_clustered= $map->get_meta("is_clustered");
	$is_legend 	= $map->get_meta("is_legend");
	$is_filtered = $map->get_meta("is_filtered");
	$zoom		= $map->get_meta("zoom");
	$latitude	= $latitude		? $latitude	 : 55;
	$longitude	= $longitude	? $longitude : 55;
	$zoom		=  $zoom ? $zoom : 4;
	if( $is_legend )
	{
		$include = $map->get_include_types();
		foreach($include as $term_id)
		{
			$term = get_term($term_id);
			$color = get_term_meta($term_id, "color", true);
			$leg .= "<div class='shm-icon' style='background-color:$color;'><img src='" . ShMapPointType:: get_icon_src ($term_id, 20)[0] . "' width='20' /></div> <span  class='shm-icon-name'>" . $term->name . "</span>";
		}
		$legend = "
		<div class='shm-legend'>
			$leg
		</div>";
	}
	if( $is_filtered )
	{
		$includes = $map->get_include_types();
		$filters = ShMapPointType::get_ganre_swicher([
			'prefix'		=> 'filtered'.$uniq, 
			'row_style'		=> "float:right;margin-left: 5px;margin-right: 0px;",
			"selected"		=> ShMapPointType::get_all_ids(),
			"includes"		=> $includes,
			"col_width"		=> 2
		], "checkbox",  "stroke" );
	}
	if($is_csv  = $map->get_meta("is_csv"))
	{
		//$csv	= "<a class='shm-csv-href small' href='' map_id='$id'>" . sprintf(__("download  %s.csv", SHMAPPER), " data", $map->get("post_title")) . "</a>";
		$csv	= "<a class='shm-csv-icon shm-hint' data-title='".sprintf(__("download  %s.csv", SHMAPPER), $title)."' href='' map_id='$id'></a>";
	}
	$points		= $map->get_map_points();
	if($is_filtered || $is_csv)
	{
		$html .="
			<div class='shm-map-panel' for='$uniq'>
				$filters $csv
			</div>";
	}
	$html 		.= "
	<div class='shm_container' id='$uniq' shm_map_id='$id' style='height:" . $args['height'] . "px;'>
		
	</div>$legend";
	$p = "";
		$str = ["
","

"];

//line javascript
	foreach($points as $point)
	{
		$p .= " 
			var p = {}; 
			p.post_title 	= '" . $point->post_title . "';
			p.post_content 	= '" . $point->post_content . " <a href=\"" .get_permalink($point->ID) . "\" class=\"shm-no-uline\"><span class=\"dashicons dashicons-location\"></span></a>';
			p.latitude 		= '" . $point->latitude . "'; 
			p.longitude 	= '" . $point->longitude . "'; 
			p.location 		= '" . $point->location . "'; 
			p.type 			= '" . $point->type . "'; 
			p.term_id 		= '" . $point->term_id . "'; 
			p.icon 			= '" . $point->icon . "'; 
			p.color 		= '" . $point->color . "'; 
			p.height 		= " . $point->height . "; 
			//p.width 		= " . $point->width . "; 
			points.push(p);
			";
	}
	$desabled = $is_lock ? "
					myMap.behaviors.disable('scrollZoom');
					myMap.behaviors.disable('drag');
	" : "";
	$is_admin = "";
	if(is_admin())
	{
		$is_admin = "
				myMap.controls.add('zoomControl', 
				{
					float: 'none',
					position: {
						right: 5,
						top: 5
					}
				});
				
				myMap.events.add( 'boundschange', function(event)
				{
					 coords = myMap.getCenter();
					 zoom = myMap.getZoom();
					 $('[name=latitude]').val(parseInt(coords[0]*1000)/1000);
					 $('[name=longitude]').val(parseInt(coords[1]*1000)/1000);
					 $('[name=zoom]').val(zoom);
				});
				myMap.events.add('contextmenu', function (e) 
				{
					if (!myMap.balloon.isOpen()) 
					{
						var coords = e.get('coords');
						shm_send(['shm_add_point_prepaire', [$map->id, coords[0].toPrecision(7), coords[1].toPrecision(7)]]);
					}
					else 
					{
						myMap.balloon.close();
					}
				});
				";
	}
	
	$html 		.= "
	<script type='text/javascript'>
		jQuery(document).ready(function($)
		{
			var points 		= []; 
			$p
			if(map_type == 1)
			{
				ymaps.ready(function () 
				{
					var myMap = new ymaps.Map('$uniq', 
					{
					  center: [ $latitude, $longitude],
					  controls: [ ],
					  zoom: $zoom
					});
					shm_maps['$uniq'] = myMap;
					
					$desabled
					
					
					//is admin	
					$is_admin					
					
					// Создаем собственный макет с информацией о выбранном геообъекте.
					var customItemContentLayout = ymaps.templateLayoutFactory.createClass(
						// Флаг 'raw' означает, что данные вставляют 'как есть' без экранирования html.
						'<div class=ballon_header>{{ properties.balloonContentHeader|raw }}</div>' +
							'<div class=ballon_body>{{ properties.balloonContentBody|raw }}</div>' +
							'<div class=ballon_footer>{{ properties.balloonContentFooter|raw }}</div>'
					);
					
					" .
				/**/
				( $is_clustered ?				
					"
					var clusterer = new ymaps.Clusterer({	
						gridSize: 128,
						hasHint: true,
						minClusterSize: 3,
						clusterIconLayout: 'default#pieChart',
						clusterIconPieChartRadius: 40,
						clusterIconPieChartCoreRadius: 30,
						clusterIconPieChartStrokeWidth: 0,
						clusterNumbers: [10],
						//clusterIconContentLayout: null,
						//groupByCoordinates: false,
						clusterBalloonContentLayout: 'cluster#balloonCarousel',
						clusterBalloonItemContentLayout: customItemContentLayout,
						clusterBalloonPanelMaxMapArea: 0,
						clusterBalloonContentLayoutWidth: 270,
						clusterBalloonContentLayoutHeight: 100,
						clusterBalloonPagerSize: 5,
						clusterOpenBalloonOnClick: true,
						clusterDisableClickZoom: true,
						clusterHideIconOnBalloonOpen: false,
						geoObjectHideIconOnBalloonOpen: false,
						type:'clusterer'
					});
					clusterer.hint = '';
					var clusters = [];
					var i=0, paramet;"
				: "" ) .	
				
					"
					points.forEach( elem =>
					{
						if( elem.icon )
						{
							paramet = {
								balloonMaxWidth: 250,
								hideIconOnBalloonOpen: false,
								iconColor:elem.color,
								iconLayout: 'default#image',
								iconImageHref: elem.icon,
								iconImageSize:[elem.height, elem.height], //[50,50], 
								iconImageOffset: [-elem.height/2, -elem.height/2],
								term_id:elem.term_id,
								type:'point'
							};
						}
						else
						{
							paramet = {
								balloonMaxWidth: 250,
								hideIconOnBalloonOpen: false,
								iconColor: elem.color ? elem.color : '#FF0000',
								preset: 'islands#dotIcon',
								term_id:elem.term_id,
								type:'point',
							}
						}
						
							var myPlacemark = new ymaps.Placemark(
								[elem.latitude, elem.longitude],
								{
									geometry: 
									{
										type: 'Point', // тип геометрии - точка
										coordinates: [elem.latitude, elem.longitude] // координаты точки
									},
									balloonContentHeader: elem.post_title,
									balloonContentBody: elem.post_content,
									balloonContentFooter: elem.location,
									hintContent: elem.post_title
								}, 
								paramet
							);
						" .
				( $is_clustered ?				
						"	
							if(!clusters[elem.term_id])
							{
								clusters[elem.term_id] =  new ymaps.Clusterer({						 
									clusterIconLayout: 'default#pieChart',
									clusterIconPieChartRadius: 50,
									clusterIconPieChartCoreRadius: 20,
									clusterIconPieChartStrokeWidth: 3,
									clusterNumbers: [10],
									clusterIconContentLayout: null,
									groupByCoordinates: false,
									clusterDisableClickZoom: true,
									clusterHideIconOnBalloonOpen: false,
									geoObjectHideIconOnBalloonOpen: false
								});	
							}
							//clusters[elem.term_id].add(myPlacemark);
							clusterer.add(myPlacemark);
							
						});
						//clusters.forEach(elem => myMap.geoObjects.add(elem));
						myMap.geoObjects.add(clusterer);
						"
						: "myMap.geoObjects.add(myPlacemark);
					})
				" 
				).
				"})
			}
			else if (map_type == 2)
			{
				var map = L.map('$uniq', 
				{
					center: [$latitude, $longitude],
					zoom: $zoom,
					 renderer: L.svg(),
					attributionControl:false,
					zoomControl:false,
					//dragging:false,
					boxZoom:false
				});
				
				L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
					attribution: '&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'
				}).addTo(map);
			
				var icons=[];
				
				//
				points.forEach( elem =>
				{
					/**/
					if( !icons[elem.term_id] && elem.icon )
					{
						icons[elem.term_id] = L.icon({
							iconUrl: elem.icon,
							shadowUrl: '',
							iconSize:     [elem.height, elem.height], // size of the icon
							shadowSize:   [elem.height, elem.height], // size of the shadow
							iconAnchor:   [elem.height/2, elem.height/2], // point of the icon which will correspond to marker's location
							shadowAnchor: [0, elem.height],  // the same for the shadow
							popupAnchor:  [-elem.height/4, -elem.height/2] // point from which the popup should open relative to the iconAnchor
						});
					}
					var shoptions = elem.icon != '' ? {icon: icons[elem.term_id]} : {};
					
					var marker = L.marker([ elem.latitude, elem.longitude ], shoptions )
						.addTo(map)
							.bindPopup('<div class=\"shml-title\">' + elem.post_title +'</div><div class=\"shml-body\">' + elem.post_content + '</div>');
				});
				//
				
			}
		});
	</script>";
	return $html;
}