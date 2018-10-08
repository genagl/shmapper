var init_map=function(){}, is_admin=function(){alert("AAAA")}, map, $this, geocodeService, eclectMarker, eclectCoords;

jQuery(document).ready(function($)
{
	//remove eclectMarker
	document.documentElement.addEventListener("clear_form", function(e) 
	{	
		var dat = e.detail;	
		var data = dat[0];
		var map = dat[1];		
		eclectMarker.remove(map);
	});
	
	if($(".shm-type-icon").size())
	{
		L.DomEvent.on(document, 'pushing', ev => 
		{
			L.DomEvent.stopPropagation(ev);	
		});
		//
		$(".shm-type-icon").draggable(
		{
			revert: false,
			start: (evt, ui) => 
			{
				$this = $(ui.helper);
				var $map_id = $this.parents("form.shm-form-request").attr("form_id");
				map = shm_maps[$map_id];	
				map.mp.enable();		
			},
			stop: (evt, ui) =>
			{
				$this = $(ui.helper);
				var $map_id = $this.parents("form.shm-form-request").attr("form_id");
				map = shm_maps[$map_id];
				//
				
				setTimeout(() => 
				{			
					
					//заполняем формы отправки 
					var lat = $("form.shm-form-request[form_id='" + $map_id + "']").find("[name=shm_point_lat]");
					var lon = $("form.shm-form-request[form_id='" + $map_id + "']").find("[name=shm_point_lon]");
					var type = $("form.shm-form-request[form_id='" + $map_id + "']").find("[name=shm_point_type]");
					var loc = $("form.shm-form-request[form_id='" + $map_id + "']").find("[name=shm_point_loc]");
					lat.val(eclectCoords[0]);
					lon.val(eclectCoords[1]);
					type.val($this.attr("shm_type_id"));
					//
					geocodeService.reverse().latlng(eclectCoords).run(function(error, result) {
						//console.log(result.address.Match_addr);
						loc.val(result.address.Match_addr).removeClass("hidden").hide().fadeIn("slow");
					});
			
					
					if(eclectMarker)
					{
						eclectMarker.remove(map);
					}
					eclectMarker = L.marker(eclectCoords,{draggable:true}).addTo(map);
					map.mp.disable();
					eclectMarker.on("dragend", evt=>
					{
						console.log(evt.target._latlng);
						eclectCoords = [evt.target._latlng.lat, evt.target._latlng.lng]
						//заполняем формы отправки 
						var lat = $("form.shm-form-request[form_id='" + $map_id + "']").find("[name=shm_point_lat]");
						var lon = $("form.shm-form-request[form_id='" + $map_id + "']").find("[name=shm_point_lon]");
						var type = $("form.shm-form-request[form_id='" + $map_id + "']").find("[name=shm_point_type]");
						var loc = $("form.shm-form-request[form_id='" + $map_id + "']").find("[name=shm_point_loc]");
						lat.val(eclectCoords[0]);
						lon.val(eclectCoords[1]);
						type.val($this.attr("shm_type_id"));
						//
						geocodeService.reverse().latlng(eclectCoords).run(function(error, result) {
							//console.log(result.address.Match_addr);
							loc.val(result.address.Match_addr).removeClass("hidden").hide().fadeIn("slow");
						});
					})
				}, 100);
				
				$this.css({left:0, top:0}).hide().fadeIn("slow");
				$this.parents(".shm-form-placemarks").removeAttr("required").removeClass("shm-alert");
			}
		});	
	}
	//
	init_map = function(mData, points)
	{	
		if(mData.isAdmin) 
		{
			L.ContextMenuClicker = L.Handler.extend({
				addHooks: function() 
				{
					L.DomEvent.on(myMap, 'contextmenu', this.onClicker, this);
				},
				removeHooks: function() 
				{
					L.DomEvent.off(myMap, 'contextmenu', this.onClicker, this);
				},
				onClicker: evt=>
				{
					shm_send( [
						'shm_add_point_prepaire', 
						[ mData.map_id, evt.latlng.lat.toPrecision(7), evt.latlng.lng .toPrecision(7)] 
					] );
				}
			});
			L.Map.addInitHook('addHandler', 'tilt', L.ContextMenuClicker);	
		}		
		L.MousePosit = L.Handler.extend({
			addHooks: function() 
			{
				L.DomEvent.on(myMap, 'mousemove', this.onmousemove, this);
			},
			removeHooks: function() 
			{
				L.DomEvent.off(myMap, 'mousemove', this.onmousemove, this);
			},
			onmousemove: evt=>
			{
				eclectCoords = [
					L.Util.formatNum(evt.latlng.lat, 7), 
					L.Util.formatNum(evt.latlng.lng, 7)
				];
					
				$("[name='latitude']").val( L.Util.formatNum(myMap.getCenter().lat ));
				$("[name='longitude']").val( L.Util.formatNum(myMap.getCenter().lng ));
				$("[name='zoom']").val( myMap.getZoom() );	
			}
		});
		L.Map.addInitHook('addHandler', 'mp', L.MousePosit);	
		
		var shmLayer1 = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', 
		{
			attribution: '<a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a>'
		});	
		
		if(mData.isLayerSwitcher)
		{	
			var shmLayer2 = L.tileLayer('http://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png', 
			{
				attribution: '<a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a>'
			});
			var shmLayer3 = L.tileLayer("http://{s}.sm.mapstack.stamen.com/" +
				"(toner-lite,$fff[difference],$fff[@23],$fff[hsl-saturation@20])/" +
				"{z}/{x}/{y}.png", 
			{
				attribution: '<a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a>'
			});
			var shmLayer4 = L.tileLayer('http://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png', {
				attribution: '<a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> | <a href="http://cartodb.com/attributions">CartoDB</a>',
				subdomains: 'abcd',
				maxZoom: 19
			});
		}
		var myMap = L.map(mData.uniq, 
		{
			layers: mData.isLayerSwitcher ? [ shmLayer1, shmLayer2, shmLayer3 , shmLayer4 ] : [shmLayer1],
			center: [mData.latitude, mData.longitude],
			zoom: mData.zoom,
			renderer: L.svg(),
			//attributionControl:false,
			fullscreenControl: mData.isFullscreen ? 
			{
				pseudoFullscreen: false
			} : false,
			zoomControl:mData.isZoomer,
			dragging:!mData.isDesabled,
			//boxZoom:true,
		});		
		shm_maps[mData.uniq] = myMap;	
		myMap.mp.disable();	
		//https://esri.github.io/esri-leaflet/examples/reverse-geocoding.html
		geocodeService = L.esri.Geocoding.geocodeService();
		//L.esri.basemapLayer("Topographic").addTo(myMap);
		
		if(mData.isDesabled)
			myMap.scrollWheelZoom.disable();
		else
			myMap.scrollWheelZoom.enabled();
		
		
		//search 
		if(mData.isSearch)
		{
			easyGeocoder({ map: myMap });
		}
		
		if(mData.isAdmin) 
			myMap.tilt.enable();
			myMap.mp.enable();
		
		//clusters
		if( mData.isClausterer )
		{
			var markers = new L.MarkerClusterGroup();
			var dist = markers;//myMap;
			myMap.addLayer(markers);
		}
		else
			var dist = myMap;
		
		var icons = [];
		points.forEach( elem =>
		{
			if(  elem.icon )
			{
				if(!icons[elem.term_id])
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
				
				if(elem.icon != '')
				{
					shoptions = {icon: icons[elem.term_id]};
				}	
				else
				{
					shoptions = {};
				}	
				var marker = L.marker([ elem.latitude, elem.longitude ], shoptions )
					.addTo(dist)
						.bindPopup('<div class=\"shml-title\">' + elem.post_title +'</div><div class=\"shml-body\">' + elem.post_content + '</div>');
			}					
			else if( mData.default_icon && !elem.color)
			{
				shoptions = {
					icon: L.icon({
						iconUrl: mData.default_icon,
						shadowUrl: '',
						iconSize:     [40, 40], // size of the icon
						iconAnchor:   [20, 20], // point of the icon which will correspond to marker's location
						popupAnchor:  [-10, -20] // point from which the popup should open relative to the iconAnchor
					})
				};
				var marker = L.marker([ elem.latitude, elem.longitude ], shoptions )
					.addTo(dist)
						.bindPopup('<div class=\"shml-title\">' + elem.post_title +'</div><div class=\"shml-body\">' + elem.post_content + '</div>');			
				
			}
			else
			{
				var clr = elem.color ? elem.color : '#FF0000'
				var style = document.createElement('style');
				style.type = 'text/css';
				style.innerHTML = '.__class'+ elem.post_id + ' { color:' + clr + '; }';
				document.getElementsByTagName('head')[0].appendChild(style);
				var classes = 'dashicons dashicons-location shm-size-40 __class'+ elem.post_id;
				var myIcon = L.divIcon({className: classes, iconSize:L.point(30, 40) });//
				var marker = L.marker([ elem.latitude, elem.longitude ], {icon: myIcon})
					.addTo(dist)
						.bindPopup('<div class=\"shml-title\">' + elem.post_title +'</div><div class=\"shml-body\">' + elem.post_content + '</div>');
			}
			
				
			
		});
		//layer switcher 
		if(mData.isLayerSwitcher)
		{
			var layerControl = L.control.layers({
				"standart" 	: shmLayer1,
				"light"		: shmLayer2,
				"grey"		: shmLayer3,
				"dark"		: shmLayer4
			});
			layerControl.addTo(myMap);
		}
	}
});