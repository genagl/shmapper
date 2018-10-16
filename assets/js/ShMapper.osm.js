var init_map=function(){}, is_admin=function(){alert("AAAA")}, map, all_markers = [], $this, geocodeService, eclectMarker, eclectCoords, myMap;
var changeBasemap = function(){}, setBasemap=function(){}, layer, layerLabels, lG;

jQuery(document).ready(function($)
{
	//filter	
	document.documentElement.addEventListener("shm_filter", function(e) 
	{	
		var dat = e.detail;	
		all_markers[dat.uniq].forEach(elem =>
		{
			if(elem.options.term_id == dat.term_id )
			{
				if(dat.$this.is(":checked"))
					//elem._icon.classList.remove("hidden");
					$(elem._icon).css("opacity",1);
				else
					//elem._icon.classList.add("hidden");
					$(elem._icon).css("opacity", 0.125);
			}
		});
	});
	
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
			
					//set marker
					var bg = $this.css('background-image');
					if( bg !== "none")
					{
						bg = bg.replace('url(','').replace(')','').replace(/\"/gi, "");
						s_style = {draggable:true};
						s_style.icon = L.icon({
							iconUrl: bg,
							shadowUrl: '',
							iconSize:     [40, 40], // size of the icon
							iconAnchor:   [20, 40], // point of the icon which will correspond to marker's location
						});
					}
					else if($this.attr("shm_clr"))
					{
						var clr = $this.attr("shm_clr");
						var style = document.createElement('style');
						var iid = $this.attr("shm_type_id");
						style.type = 'text/css';
						style.innerHTML = '.__class'+ iid + ' { color:' + clr + '; }';
						document.getElementsByTagName('head')[0].appendChild(style);
						var classes = 'dashicons dashicons-location shm-size-40 __class'+ iid;
						var myIcon = L.divIcon({className: classes, iconSize:L.point(30, 40), iconAnchor: [20, 30] });//
						s_style = { draggable:true, icon: myIcon };
					}
					else
					{
						s_style = {draggable:true};
						
					}
					
					if(eclectMarker)
					{
						eclectMarker.remove(map);
					}
					eclectMarker = L.marker(eclectCoords,s_style).addTo(map);
					map.mp.disable();
					eclectMarker.on("dragend touchend", evt=>
					{
						//console.log(evt.target._latlng);
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
		if( mData.isMap )
		{
			if( mData.isAdmin ) 
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
						geocodeService.reverse().latlng(evt.latlng).run(function(error, result) 
						{
							shm_send( [
							'shm_add_point_prepaire', 
							[ mData.map_id, evt.latlng.lat.toPrecision(7), evt.latlng.lng .toPrecision(7), result.address.Match_addr] 
						] );	
						});
						
					}
				});
				L.Map.addInitHook('addHandler', 'tilt', L.ContextMenuClicker);	
			}		
			L.MousePosit = L.Handler.extend({
				addHooks: function() 
				{
					L.DomEvent.on(myMap, 'mousemove', this.onmousemove, this);
					L.DomEvent.on(myMap, 'touchmove', this.onmousemove, this);
					L.DomEvent.on(myMap, 'touchstart', this.ontouchstart, this);
				},
				removeHooks: function() 
				{
					L.DomEvent.off(myMap, 'mousemove', this.onmousemove, this);
					L.DomEvent.off(myMap, 'touchmove', this.onmousemove, this);
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
				},
				ontouchstart: evt=>
				{
					
				}
			});
			L.Map.addInitHook('addHandler', 'mp', L.MousePosit);	
		}
		var shmLayer1 = mData.mapType && "OpenStreetMap" !== mData.mapType 
			? L.esri.basemapLayer( mData.mapType) : 
			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', 
			{
				attribution: '<a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a>'
			});
		myMap = L.map(mData.uniq, 
		{
			layers: [shmLayer1],
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
		all_markers[mData.uniq]	= [];	
		
		//layer switcher 
		if(mData.isLayerSwitcher)
		{
			var layerControl = L.control.layerSwitcher({});
			layerControl.addTo(myMap);
		}
		
		if(mData.isMap) myMap.mp.disable();	
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
		
		if( mData.isMap) 
		{
			if(mData.isAdmin) 
				myMap.tilt.enable();
			myMap.mp.enable();
		}
		//clusters
		if( mData.isClausterer )
		{
			var markers = new L.MarkerClusterGroup();
			var dist = markers;//myMap;
			myMap.addLayer(dist);
		}
		else
			var dist = myMap;
		
		var icons = [], marker;
		points.forEach( elem =>
		{
			if(  elem.icon )
			{
				var h = parseInt(elem.height);
				var w = elem.width ? parseInt(elem.width) : h;
				if(!icons[elem.term_id])
				{
					icons[elem.term_id] = L.icon({
						iconUrl		: elem.icon,
						draggable	: elem.draggable,
						shadowUrl	: '',
						iconSize	: [w, h], // size of the icon
						shadowSize	: [w, h], // size of the shadow
						iconAnchor	: [w/2, h/2], // point of the icon which will correspond to marker's location
						shadowAnchor: [0, h],  // the same for the shadow
						popupAnchor	: [-w/4, -w/2] // point from which the popup should open relative to the iconAnchor
					});
				}
				
				if(elem.icon != '')
				{
					shoptions = { draggable: elem.draggable, icon: icons[elem.term_id], term_id: elem.term_id};
				}	
				else
				{
					shoptions = { term_id: elem.term_id };
				}	
				marker = L.marker([ elem.latitude, elem.longitude ], shoptions )
					.addTo(dist)
						.bindPopup('<div class=\"shml-title\">' + elem.post_title +'</div><div class=\"shml-body\">' + elem.post_content + '</div>');
				
			}					
			else if( mData.default_icon && !elem.color )
			{
				shoptions = {
					icon: L.icon({
						draggable : elem.draggable,
						iconUrl: mData.default_icon,
						shadowUrl: '',
						iconSize:     [40, 40], // size of the icon
						iconAnchor:   [20, 20], // point of the icon which will correspond to marker's location
						popupAnchor:  [-10, -20] // point from which the popup should open relative to the iconAnchor
					})
				};
				marker = L.marker([ elem.latitude, elem.longitude ], shoptions )
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
				marker = L.marker(
					[ elem.latitude, elem.longitude ], 
					{draggable	: elem.draggable,icon: myIcon, term_id: elem.term_id}
				)
				.addTo(dist)
					.bindPopup('<div class=\"shml-title\">' + elem.post_title +'</div><div class=\"shml-body\">' + elem.post_content + '</div>');
			}
			all_markers[mData.uniq].push(marker);
			if(elem.draggable)
			{
				marker.on('dragend', function (e) 
				{
					$('[name="latitude"]').val(marker.getLatLng().lat);
					$('[name="longitude"]').val(marker.getLatLng().lng);
					geocodeService.reverse().latlng(marker.getLatLng()).run(function(error, result)
					{
						$('[name="location"]').val(result.address.Match_addr);
					});
				});
			}		
		});
		
		
	}
});



  