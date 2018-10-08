var init_map=function(){}, is_admin=function(){}
jQuery(function () 
{
	ymaps.ready(init);
	
});

function init () 
{		
	
}
jQuery(document).ready(function($)
{
	//new point creating engine
	addAdress = function($this, new_mark_coords)
	{	
		ymaps.geocode(new_mark_coords).then(function (res) 
		{
			var firstGeoObject = res.geoObjects.get(0);
			var getAdministrativeAreas = firstGeoObject.getAdministrativeAreas().join(", ");
			var getLocalities = firstGeoObject.getLocalities().join(", ");
			var getThoroughfare = firstGeoObject.getThoroughfare();
			var getPremise = firstGeoObject.getPremise();
			var address = [
				getAdministrativeAreas,
				getLocalities,
				getThoroughfare
			];
			if(getPremise)
				address.push(getPremise);
			shm_address = address.join(", ");
			//заполняем формы отправки 
			var lat = $this.parents("form.shm-form-request").find("[name=shm_point_lat]");
			var lon = $this.parents("form.shm-form-request").find("[name=shm_point_lon]");
			var type = $this.parents("form.shm-form-request").find("[name=shm_point_type]");
			var loc = $this.parents("form.shm-form-request").find("[name=shm_point_loc]");
			lat.val(new_mark_coords[0]);
			lon.val(new_mark_coords[1]);
			loc.val(shm_address).removeClass("hidden").hide().fadeIn("slow");
			type.val($this.attr("shm_type_id"));
		})			
	}
	if($(".shm-type-icon").size())
	{
		$(".shm-type-icon").draggable(
		{
			revert: false,
			start: (evt, ui) => 
			{
				$this = $(ui.helper);
				var $map_id = $this.parents("form.shm-form-request").attr("form_id");
				
			},
			stop: (evt, ui) =>
			{
				$this = $(ui.helper);
				var $map_id = $this.parents("form.shm-form-request").attr("form_id");
				map = shm_maps[$map_id];
				//
				//console.log(evt.clientX, evt.clientY + window.scrollY);
				var globalPixelPoint = map.converter.pageToGlobal( [evt.clientX, evt.clientY + window.scrollY] );
				new_mark_coords = map.options.get('projection').fromGlobalPixels(globalPixelPoint, map.getZoom());
				map.geoObjects.remove(shm_placemark);
				var bg = $this.css('background-image');
				if( bg !== "none")
				{
					bg = bg.replace('url(','').replace(')','').replace(/\"/gi, "");
					shm_paramet = {
						balloonMaxWidth: 250,
						hideIconOnBalloonOpen: false,
						iconLayout: 'default#imageWithContent',
						iconShadow:true,
						iconImageHref: bg,
						iconImageSize:[50,50], 
						iconImageOffset: [-25, -25],
						draggable:true,
						term_id:$this.attr("shm_type_id"),
						type:'point',
						fill:true,
						fillColor: "#FF0000",
						opacity:0.22
					};
				}
				else
				{
					shm_paramet = {
						balloonMaxWidth: 250,
						hideIconOnBalloonOpen: false,
						iconColor: $this.attr("shm_clr") ? $this.attr("shm_clr"):'#FF0000',
						preset: 'islands#dotIcon',
						draggable:true,
						term_id:$this.attr("shm_type_id"),
						type:'point',
						fill:true,
						fillColor: "#FF0000",
						iconShadow:true,
						opacity:0.22
					}
				}
				
				shm_placemark = new ymaps.GeoObject({
					geometry: 
					{
						type: 'Point',
						coordinates: new_mark_coords,
					}
				} , 
				shm_paramet);
				
				shm_placemark.events.add("dragend", evt =>
				{
					var pos = evt.get("position");
					var globalPixelPoint = map.converter.pageToGlobal( [pos[0], pos[1]] );
					new_mark_coords = map.options.get('projection').fromGlobalPixels(globalPixelPoint, map.getZoom());
					//console.log(pos);
					//console.log( evt.originalEvent.target.options.get("type") );
					addAdress( $this, new_mark_coords );
				});
				addAdress( $this, new_mark_coords );
				map.geoObjects.add(shm_placemark); 
				$this.css({left:0, top:0}).hide().fadeIn("slow");
				$this.parents(".shm-form-placemarks").removeAttr("required").removeClass("shm-alert");
			}
		});	
	}
	//
	init_map = function(mData, points)
	{
		var i=0, paramet;
		var myMap = new ymaps.Map(mData.uniq, 
		{
		  center: [ mData.latitude, mData.longitude],
		  controls: [ ],
		  zoom: mData.zoom
		});
		
		//search 
		if(mData.isSearch)
		{
			var searchControl = new ymaps.control.SearchControl({
				options: {
					provider: 'yandex#search'
				}
			});
			myMap.controls.add(searchControl);
		}
		
		//fullscreen 
		if(mData.isFullscreen)
		{
			var fsControl = new ymaps.control.FullscreenControl({
				options: {
					provider: 'yandex#search'
				}
			});
			myMap.controls.add(fsControl);
		}
		
		//layer switcher 
		if(mData.isLayerSwitcher)
		{
			myMap.controls.add(new ymaps.control.TypeSelector(['yandex#map', 'yandex#hybrid']));
		}
		
		//zoom slider 
		if(mData.isZoomer)
		{
			myMap.controls.add('zoomControl', 
			{
				float: 'none'
			});
		}
		
		//config map behaviors
		if(mData.isDesabled)
		{
			myMap.behaviors.disable('scrollZoom');
			myMap.behaviors.disable('drag');
		}	
		// add to global array
		shm_maps[mData.uniq] = myMap;
		
		// Hand-made Boolon
		var customItemContentLayout = ymaps.templateLayoutFactory.createClass(
			// Флаг 'raw' означает, что данные вставляют 'как есть' без экранирования html.
			'<div class=ballon_header>{{ properties.balloonContentHeader|raw }}</div>' +
				'<div class=ballon_body>{{ properties.balloonContentBody|raw }}</div>' +
				'<div class="ballon_footer shm_ya_footer">{{ properties.balloonContentFooter|raw }}</div>'
		);
		if( mData.isClausterer )
		{
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
		}
		points.forEach( elem =>
		{
			if( elem.icon )
			{
				paramet = {
					balloonMaxWidth: 250,
					balloonItemContentLayout: customItemContentLayout,
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
			else if( mData.default_icon && !elem.color)
			{
				paramet = {
					balloonMaxWidth: 250,
					balloonItemContentLayout: customItemContentLayout,
					hideIconOnBalloonOpen: false,
					iconLayout: 'default#image',
					iconImageHref: mData.default_icon,
					iconImageSize: [40,40], 
					iconImageOffset: [-20, -20],
					term_id:-1,
					type:'point'
				};
				
			}
			else
			{
				paramet = {
					balloonMaxWidth: 250,
					balloonItemContentLayout: customItemContentLayout,
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
					balloonContentFooter: '',
					hintContent: elem.post_title
				}, 
				paramet
			);
			if( mData.isClausterer )
			{
				clusterer.add(myPlacemark);
			}			
			else
				myMap.geoObjects.add(myPlacemark);
		})
		if( mData.isClausterer )	myMap.geoObjects.add(clusterer);
		if(mData.isAdmin)
			is_admin(myMap, mData.map_id);	
	}
	is_admin = function(myMap, mapData_id)
	{
		myMap.events.add( 'boundschange', function(event)
		{
			 coords = myMap.getCenter();
			 zoom = myMap.getZoom();
			 $('[name=latitude]').val(coords[0].toPrecision(7));
			 $('[name=longitude]').val(coords[1].toPrecision(7));
			 $('[name=zoom]').val(zoom);
		});
		myMap.events.add('contextmenu', function (e) 
		{
			if (!myMap.balloon.isOpen()) 
			{
				var coords = e.get('coords');
				shm_send( ['shm_add_point_prepaire', [ mapData_id, coords[0].toPrecision(7), coords[1].toPrecision(7)] ] );
			}
			else 
			{
				myMap.balloon.close();
			}
		});
	}
})
	