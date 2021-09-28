var shm_track_map, 
	myPolyline, 
	trackData, 
	trkpt, 
	trackPoliline=[], 
	updatePolyline = function(myMap){}, 
	editPolyline = function(){}, 
	isTrackEdit=false,
	shm_track_place, 
	start_draw_track=function(){}, 
	finish_draw_trak = function(){}, 
	update_track_placmarks_json = function( map ){}, 
	$_this,
	map_id, 
	map__id,
	newTrackVertexes = [],
	vertex_data;
jQuery(document).ready(function($)
{ 	 
	$("[shm-track-dnld-gpx]").on( "click", evt =>
	{
		shm_send( [ "shm-trac-dnld-gpx", $(evt.currentTarget).attr("shm-track-dnld-gpx") ] );
	});
	/*
	*	set *.gpx file to input
	*/
	$(".shm-form-track input[type='file']").on(
		"change", 
		function(evt)
		{
			var file = evt.target.files[0];

			//console.log( file.name, $(evt.currentTarget).parent().find( "label.shm_nowrap" ) );
			//console.log( file.name );
			if( !file )	return;
			//
			var form_forms = $(evt.currentTarget).parents("#form_forms");
			var reader = new FileReader();
			reader.onload = (function(event) 
			{ 
				$( ".shm-form-request[form_id='" + map_id + "'] .shmapper_tracks_edit").fadeOut();
				$(evt.currentTarget).parents(".shm-track-upload-cont").removeClass('shm-track-has-slider');
				$(evt.currentTarget).parents(".shm-track-upload-cont").find(".shm-track-pult").fadeOut();
				$(evt.currentTarget).parents(".shm-track-upload-cont").find(".shm-track-error").fadeOut();
				try
				{ 
					trackData = parseXml(event.target.result).gpx;
					if(!trackData)
					{
						 throw new Error(__("Not correct gpx format"))
					}
					if(!trackData.trk)
					{
						 throw new Error(__("Not exists track data"))
					}
					if(!trackData.trk.trkseg)
					{
						 throw new Error(__("Not exists correct track's segment data"))
					}
					if(!trackData.trk.trkseg.trkpt)
					{
						 throw new Error(__("Not exists correct track segment's data"))
					}
					
					var name = trackData.trk.name ? trackData.trk.name['#text'] : null;
					if( name )
					{
						form_forms.find(".form-field-9 input.sh-form").val( name );
					}
					desc = trackData.trk.desc ?  trackData.trk.desc['#text'] : null; 
					if( desc )
					{
						var val = form_forms.find(".form-field-6 textarea.sh-form").val();
						form_forms.find(".form-field-6 textarea.sh-form").val( val + "<p>" + desc + "</p>" );
					}

					//form_forms.find(".shm-form-track").css("width", "50%");

					form_forms.find(".shm-range").on({
						change: function(evt2)
						{
							var val = evt2.currentTarget.value ;
							$('.shm-form-slider').find('.shm-range-label').text( val );
							trackPoliline = trkpt.filter(function(elem, index)
							{
								return index % val == 0;
							}).map(function(elem)
							{
								return [ parseFloat(elem.lat,5), parseFloat(elem.lon, 5)];
							});							
							var myMap = shm_maps[ $(evt.target).parents("[form_id]").attr("form_id") ];
							updatePolyline( myMap );
						}
					});
					
					form_forms.find(".shm-track-upload-cont").addClass('shm-track-has-slider');
					setInterval(function()
					{
						form_forms.find(".shm-track-pult").fadeIn("slow")
					}, 500);
					trkpt = trackData.trk.trkseg.trkpt;
					
					var val = form_forms.find(".shm-range").val() ;
					$('.shm-form-slider').find('.shm-range-label').text( val );
					trackPoliline = trkpt.filter(function(elem, index)
					{
						return index % val == 0;
					}).map(function(elem)
					{
						return [ parseFloat(elem.lat,5), parseFloat(elem.lon, 5)];
					});

					map_id 		= $(evt.target).parents("[form_id]").attr("map_id");
					var myMap 	= shm_maps[ $(evt.target).parents("[form_id]").attr("form_id") ];		
					$_this		= $(evt.target);		
					updatePolyline( myMap );	
					addPolylineClick();		
					isTrackEdit ? editPolyline() : stopEditPolyline();
				}
				catch(e)
				{

					var message = __("Uncorrect gpx-file: ") + e.message; 
					//form_forms.find(".shm-form-track").css("width", "25%");
					setTimeout(function(message)
					{

						$(evt.currentTarget).parents(".shm-track-upload-cont").find(".shm-track-error")
							.fadeIn("slow")
								.text( message );
					}, 500, message);
					evt.target.files = null;
				}
			});
			reader.readAsText(file);
			//
			var lbl = $(evt.currentTarget).parent('.shm-form-file').find( "label.button" );

			setTimeout( function(){
				lbl.html( file.name );
			},50);
			lbl.addClass("shm-color-alert");

		}
	);
	
	/* 
	*	Hide places's engine in request form 
	*/
	if( $("[switched_enabled_markers='1']").length > 0 )
	{ 
		$("[switched_enabled_markers='1']").each(function(index)
		{
			var map_id = $( this ).parents( ".shm-form-request" ).attr("map_id"); 
			$( this ).parents( ".shm-form-request" ).find(".form-field-8").hide();
			$( this ).parents( ".shm-form-request" ).find(".shm-form-placemarks").removeAttr("required");
		});
		
	}
	/* 
	*	Button choose track in Map
	*/
	$(".shm-track-list-btn").on({click: function(evt)
		{
			var targ		= evt.currentTarget
			var track_id 	= parseInt(targ.getAttribute("track_id"));
			var uniq 		= $(targ).parents("[form_id]").attr("form_id");
			var map_id 		= $(targ).parents("[map_id]" ).attr("map_id" ); 
			var sel 		= targ.getAttribute("sel");
			if( sel == "1" )
			{
				if(shm_maps[ uniq ])
				{
					shm_maps[ uniq ].geoObjects.each(function(gO)
					{
						gO.options.set({ opacity: 1 }); 
					});
				}
				$(targ).attr("sel", "0");
				let defCoords = shm_maps[ uniq ].container.getParentElement().getAttribute("coords").split(",").map( e => parseFloat(e) );

				shm_maps[ uniq ].setCenter(
					defCoords, 
					defCoords[2],
					{
						flying: true,
						duration: 1000 
					}
				);
			}
			else
			{
				if(shm_maps[ uniq ])
				{
					shm_maps[ uniq ].geoObjects.each(function(gO)
					{

						if(gO.options._options.track_id == track_id)
						{
							shm_maps[ uniq ].setBounds( gO.geometry.getBounds(), { duration : 1000 } ); 
							shm_maps[ uniq ].geoObjects.each(function(g)
							{
								g.options.set({ opacity: .25 });	 
							});
							gO.options.set({ opacity: 1 });
						}
					});
				}				
				$(targ).attr("sel", "1");
			}
			
			evt.preventDefault();
		}
	})
	/* 
	*	Button "start draw track" to Map 
	*/
	$(".shmapper_tracks_edit").each(function(index)
	{
		map_id = $( this ).parents("[form_id]").attr("form_id");
		map__id = $( this ).parents("[form_id]").attr("map_id");
		$_this = $( this );		
		$_this.on("click", evt =>
		{		 	
			var boo = !myPolyline && !isTrackEdit;				
			boo = myPolyline ? confirm(__("Delete prevous track?")) : true;
			if( boo )
			{
				start_draw_track();		
				isTrackEdit = false;
				var destination = $(".shm_container[id='" + map_id + "']").offset().top - 140;
				$('body, html').animate({ scrollTop: destination }, 1100)
				var myMap = shm_maps[ $_this.parents("[form_id]").attr("form_id") ];
				myMap.geoObjects.remove(myPolyline);
				myPolyline = new ymaps.Polyline(
					[ ],
					{ }, 
					{ 
						editorDrawingCursor: "crosshair",
						strokeWidth: 4,
						// Adding a new item to the context menu that allows deleting the polyline.
						/*
						editorMenuManager: function (items, vertex) 
						{ 
							var vd = getVertexData(vertex); 
							console.log(  vd );
							let icon = vd[2] && vd[2].type ? vd[2].type : "";
							let ttl = icon + ( vd[2] && vd[2].title ? vd[2].title : __("Add marker") );
							 
							items.push({
								title:  ttl,
								onClick: function () 
								{ 
									start_update_vertex(vertex);
								}
							});
							return items;
						},
						*/
						mapId: map_id
					}
				);

				myMap.geoObjects.add(myPolyline);
				myPolyline.geometry.events.add("change", function(evt)
				{
					var points = myPolyline.geometry
						.getCoordinates();  
					updateNewTrackData( map__id, points );
					
				});
				
				myPolyline.editor.events.add("drawingstart", function(evt)
				{   
								
				});
				
				myPolyline.editor.events.add("drawingstop", function(evt)
				{ 
					finish_draw_trak(  );
					
					var points = myPolyline.geometry
						.getCoordinates();  
					updateNewTrackData( map__id, points );
				});
				myPolyline.editor.startDrawing();
				!isTrackEdit ? editPolyline() : stopEditPolyline();
				addPolylineClick();
			}
		});
	});
	start_draw_track = function(  )
	{		
		//$( ".shm-form-request[form_id='" + map_id + "'] .shmapper_tracks_upld").fadeOut("slow");
		$( ".shm-form-request[form_id='" + map_id + "'] .shmapper_tracks_edit").addClass("active");
		newTrackVertexes = [];
		isTrackEdit = true;	
	}
	finish_draw_trak = function(  )
	{
		isTrackEdit = false;
		$( ".shm-form-request[form_id='" + map_id + "'] .shmapper_tracks_edit").removeClass("active")
	}
	$(".shm-track-edit").on("click", function()
	{
		!isTrackEdit ? editPolyline() : stopEditPolyline();
	})
	
	$(".shm-track-update").on("click", function(evt)
	{
		var myMap = shm_maps[ $(evt.target).parents("[form_id]").attr("form_id") ];
		updatePolyline(myMap)
	})
	
	editPolyline = function()
	{
		start_draw_track()
		myPolyline.editor.startEditing();
	}
	
	stopEditPolyline = function()
	{
		finish_draw_trak();
		myPolyline.editor.stopEditing();
	}
	updatePolyline = function(myMap)
	{
		map_id = myMap.container.getParentElement().getAttribute("shm_map_id");
		myMap.geoObjects.remove(myPolyline);			
		myPolyline = new ymaps.Polyline(
			trackPoliline,
			{ }, 
			{ 
				editorDrawingCursor: "crosshair",
				strokeWidth: 4, 
				// Adding a new item to the context menu that allows deleting the polyline.
				editorMenuManager: function (items, vertex) 
				{
					// console.log(vertex);
					// console.log(myMap.container.getParentElement());
					items.push({
						title: "Delete line",
						onClick: function () {
							myMap.geoObjects.remove(myPolyline);
						}
					});
					return items;
				} 
			}
		); 
		myMap.geoObjects.add(myPolyline);
		// 	 
		myPolyline.geometry.events.add("change", function(evt)
		{
			var points = myPolyline.geometry
				.getCoordinates(); 	
			updateNewTrackData( map_id, points );			
		});	 
		myPolyline.editor.events.add("drawingstart", function(evt)
		{
			var input = $( "[map_id='" + map_id + "'].shm-form-request .form-field-shmapper_track_draw input" )
			//$("[shm_map_id='" + map_id + "'] .shmapper_tracks_edit").hide();
		});
		myPolyline.editor.events.add("drawingstop", function(evt)
		{
			//$("[shm_map_id='" + map_id + "'] .shmapper_tracks_edit").fadeIn("slow");
		});
		myMap.setBounds(myPolyline.geometry.getBounds()); 		
		isTrackEdit ? editPolyline() : stopEditPolyline(); 
		updateNewTrackData( map_id, trackPoliline )
	} 
	
	
	
	var addPolylineClick = function()
	{
		myPolyline.events.add("click", function(event)
		{			
			var newTitle = $(".form-field-9").length > 0 ? $(".form-field-9").find(".sh-form").val() : __("New track");
			var newDescr = $(".form-field-6").length > 0 ? $(".form-field-6").find(".sh-form").val() : __("New descr");
			shm_add_modal({
				class: "shm-max",
				title:  newTitle,
				content:"<div class='shm-row shm-h-100'><div class='shm-9 shm-md-12 shm-h-100 sh-align-middle'><div id='shm-track-modal-map' class='shm-h-100'></div><div class='' id='shm-track-modal-map-description'></div><div> <input name='shm-track-modal-markers' class='shm-w-100' value='' type='hidden'/></div></div><div class='shm-3 shm-md-12' id='shm-track-modal-descr'><div>" + newDescr + "</div></div></div>",
				footer:"<button class='upd_track_places' >" + __("Update new track") + "<button>",
			});
			$( ".upd_track_places" ).on( "click", function( event )
			{				
				var shm_map_id 	= $_this.parents( "[shm_map_id]" ).attr( "id" ); 
				var json2 		= jQuery( "[name=shm-track-modal-markers]" ).val();
				shm_close_modal(); 
				var input 		= getNewTrackInput(map_id );
				var json 		= getNewTrackData( map_id );
				json.markers 	= JSON.parse( json2 );
				input.val( JSON.stringify( json ) );  
			})
			var customEvent = new CustomEvent(
				"_shm_track_map_", 
				{
					bubbles : true, 
					cancelable : true, 
					detail : {
						track: {
							markers: getNewTrackData( map_id ).markers,
							color : "#111111",
							post_title : newTitle,
							post_content : "",
							track : myPolyline.geometry.getCoordinates(),
							shm_track_type : "3",
							shm_track_type_name : "new ",
							term_id :  -1,
							track_id : -1
						},
						isNew : true,
						mapId : map_id
					}
				}
			);
			document.documentElement.dispatchEvent(customEvent);
		})
	}
	
	
	/*
	*
	*/
	document.documentElement.addEventListener(
		"clear_form", 
		function(evt)
		{
			var lbl = $( "[map_id='" + evt.detail[2] + "'] .shm-form-track label.shm_nowrap" )
			lbl.removeClass("shm-color-alert")
					.html("<span class='dashicons dashicons-upload'></span>" + lbl.attr("flop"));
			// clear work track
			var uniq = $( "[map_id='" + evt.detail[2]).attr("form_id");
			if( shm_maps[ uniq ] )
			{
				if ( shm_maps[ uniq ].geoObjects ) {
					shm_maps[ uniq ].geoObjects.remove(myPolyline);
				}
			}
		}
	);
	
	
	/*
	*	
	*/
	document.documentElement.addEventListener("_shm_track_map_", function(evt) 
	{

		shm_track_map = new ymaps.Map(
			"shm-track-modal-map", 
			{
			  center: [55.73, 37.75],
				zoom:8,
				controls: [ ],
			}, 
			{
				searchControlProvider: 'yandex#search'
			} 
		);
		var mapPolyline = new ymaps.Polyline(
			evt.detail.track.track,
			{ }, 
			{ 
				hasBalloon: false,
				strokeWidth: 4,
				strokeColor: evt.detail.track.color ? evt.detail.track.color  : "#0066ff",
			} 
		);		
		shm_track_map.geoObjects.add(mapPolyline);
		
		if(evt.detail.track.markers)
		{
			var clusterer = new ymaps.Clusterer({	
					gridSize: 64,
					hasHint: true,
					minClusterSize: 2,
					clusterIconLayout: 'default#pieChart',
					clusterIconPieChartRadius: 40,
					clusterIconPieChartCoreRadius: 30,
					clusterIconPieChartStrokeWidth: 0,
					clusterNumbers: [10], 
					clusterBalloonContentLayout: 'cluster#balloonCarousel',
					//clusterBalloonItemContentLayout: customItemContentLayout,
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
			shm_track_map.geoObjects.add(clusterer);
			evt.detail.track.markers.forEach( marker =>
			{ 
				var height 	= marker.height ? parseInt(marker.height) 	: 30;
				var width 	= marker.width 	? parseInt(marker.width) 	: height;
				var myPlacemark = drawPlacemark(
					shm_track_map, 
					marker.latitude ? [marker.latitude, marker.longitude] : marker.coordinates , 
					{
						background_image 	: marker.icon,
						width				: width,
						height				: height,
						shm_type_id			: marker.term_id,
						shm_clr				: marker.color ? marker.color : '#FF0000',
						post_title			: marker.post_title,
						post_content		: marker.post_content
					}, 
					{
						draggable			: false,
						isEdited			: false
					}
				)
				clusterer.add(myPlacemark);
			});
		}
		shm_track_map.setBounds( mapPolyline.geometry.getBounds( ) ); 
		if(evt.detail.isNew)
		{
			shm_send(["shm_track_new", evt.detail.mapId ])
		}
	})
	
	/*
	*	
	*/
	document.documentElement.addEventListener("_shm_track_after_add_markers_panel_", function(evt) 
	{		
		var $this;	
		if(jQuery('.shm-type-icon-1').length)
		{ 
			jQuery('.shm-type-icon-1').draggable(
			{
				revert: false,
				start: (evtent, ui) => 
				{
					$this = $(ui.helper);
					var $map_id = $this.parents("form.shm-form-request").attr("form_id");
				},
				drag: (evtent, ui) =>
				{
					// console.log( evtent, ui );
				},
				stop: (evtent, ui) =>
				{
					$this.addClass('shmapperDragged');
					shmapperPlaceMarkerOnMap( evtent, ui ); 
				}
			});	
			
		}
		function shmapperPlaceMarkerOnMap( evtent, ui )
		{ 
			var globalPixelPoint 	= shm_track_map.converter.pageToGlobal( [evtent.clientX, evtent.clientY + window.scrollY] );
			new_mark_coords 		= shm_track_map.options.get('projection').fromGlobalPixels(globalPixelPoint, shm_track_map.getZoom()); 
			shmapperPlaceMarkerOnMapByCoords(shm_track_map, new_mark_coords, $(ui.helper) );
		}
		
		function shmapperPlaceMarkerOnMapByCoords(map, new_mark_coords, _markerIcon ) 
		{
			var markData = {
				background_image : _markerIcon.css( "background-image" ),
				shm_type_id : _markerIcon.attr( "shm_type_id" ),
				shm_clr 	: _markerIcon.attr( "shm_clr" ),
			};
			var params = {draggable:true, isEdited:true };
			var shm_placemark = drawPlacemark(map, new_mark_coords, markData, params );
			
			map.geoObjects.add(shm_placemark);  
			shm_placemark.events.add("dragend", e =>
			{
				update_track_placmarks_json( map );
			});
			
			_markerIcon.css({left:0, top:0}).hide().fadeIn("slow");
			
			var customEvent = new CustomEvent(
				'shm-track-modal-add-markers', 
				{
					bubbles : true, 
					cancelable : true, 
					detail : { 
						map				: map,
						_markerIcon		: _markerIcon,
						shm_placemark	: shm_placemark
					}
				}
			);
			document.documentElement.dispatchEvent(customEvent);	 
		}
	});
	

	/*
	*	
	*/
	update_track_placmarks_json = function( map )
	{
		var dd = [];
		map.geoObjects.each((e, i) => {
			if(e.geometry && e.geometry.getType() == "Point" )
			{ 
				var pnt = map.geoObjects.get(i); 
				dd.push({
					shm_type_id 	: pnt.properties._data.term_id,
					icon			: pnt.options._options.iconImageHref,
					color			: pnt.options._options.fillColor,
					post_title		: pnt.properties._data.post_title,
					post_content	: pnt.properties._data.post_content,
					//coordinates	: pnt.geometry.getCoordinates()
					coordinates		: newTrackVertexes[i]
				});
			}
		});
		/*
		myPolyline.options.set({
			editorMenuManager: function (items, vertex) 
			{ 
				var vd = getVertexData(vertex); 			
				console.log(  vd );
				let icon = vd[2] && vd[2].type ? vd[2].type : "";
				let ttl = "<span class='shm-mr-2'>" + icon + '</span>' + ( vd[2] && vd[2].title ? vd[2].title : __("Add marker") );
				items.push({
					title:  ttl,
					onClick: function () 
					{ 
						start_update_vertex(vertex);
					}
				});
				return items;
			}
		})
		*/
		jQuery("[name=shm-track-modal-markers]").val( JSON.stringify( dd ) ); 
	}
	
	
	/*
	*	Add track marker in modal
	*/
	document.documentElement.addEventListener('shm-track-modal-add-markers', function(evt) 
	{ 
		update_track_placmarks_json( evt.detail.map );
	});
	
	
	/*
	*	
	*/
	document.documentElement.addEventListener("finish_draw_map", function(evt) 
	{ 
		shm_send(["shm_get_map_tracks", evt.detail.data ])
	})
	
	/* 
		shmapper ajax result 
	*/
	document.documentElement.addEventListener("_shm_send_", function(evt) 
	{
		var dat = evt.detail;
		var command	= dat[0];
		var datas	= dat[1];

		switch(command)
		{
			case "shm_get_map_tracks":

				datas.tracks.map(function(elem)
				{ 

					var customItemContentLayout = ymaps.templateLayoutFactory.createClass(
						// The 'raw' flag means the data is inserted 'as is' without html escaping.
						'<div class=ballon_header>{{ properties.balloonContentHeader|raw }}</div>' +
						'<div class=ballon_body>{{ properties.balloonContentBody|raw }}</div>'
					);
					var line = new ymaps.Polyline(
						elem.track,
						{ 
							hintContent: elem.shm_track_type_name + " " + elem.post_title, 
							balloonMaxWidth: 250,
							balloonContent: elem.post_content,
							balloonContentHeader: elem.post_title,
							balloonContentBody: elem.post_content,
							balloonItemContentLayout: customItemContentLayout,
						}, 
						{ 				
							hasBalloon: false,
							strokeWidth: elem.width,
							strokeColor: elem.color ? elem.color  : "#0066ff",
							type:'track',
							term_id: elem.term_id,
							track_id: elem.track_id,
							markers: elem.markers,
							mapId: datas.map_id
						}
					);
					line.events.add("click", function(evt)
					{ 
						shm_add_modal({
							class: "shm-max",
							title:  evt.get("target").properties._data.hintContent,
							content:"<div class='shm-row shm-h-100'><div class='shm-9 shm-md-12 shm-h-100' id='shm-track-modal-map' ></div><div class='shm-3 shm-md-12'>" + evt.get("target").properties._data.balloonContentBody + "</div></div>",
							footer: "<button class='shm-trac-dnld-gpx' shm-trac-dnld-gpx='" + elem.track_id + "'>" + shmapper_track.downloadGpx + "</button>"
						});
						$("[shm-trac-dnld-gpx]").on( "click", evt =>
						{
							shm_send( [ "shm-trac-dnld-gpx", $(evt.currentTarget).attr("shm-trac-dnld-gpx") ] );
						});
						var customEvent = new CustomEvent("_shm_track_map_", {bubbles : true, cancelable : true, detail : {track: elem, isNew:false}})
						document.documentElement.dispatchEvent(customEvent);
					});
					
					shm_maps[datas.uniq].geoObjects.add(line);
				});				
				break;
			case "shm_track_new":  
				$("#shm-track-modal-map-description")
					.empty()
						.prepend( datas.form );
				var customEvent = new CustomEvent( 
					"_shm_track_after_add_markers_panel_", 
					{ 
						bubbles : true, 
						cancelable : true, 
						detail : { } 
					} 
				);
				document.documentElement.dispatchEvent(customEvent);
				break; 
			case "shm_track_vertex":  
				$("#shm-track-modal-map-description")
					.empty()
						.prepend( datas.form );
				$("[name='vertex_type']").on("change", evt =>
				{ 
					vertex_data.type = evt.currentTarget.value;
					jQuery("[name='vertex_data']").val( JSON.stringify( vertex_data ) );
				})
				$("[name='vertex_title']").on("change", evt =>
				{ 
					vertex_data.title = evt.currentTarget.value;
					jQuery("[name='vertex_data']").val( JSON.stringify( vertex_data ) );
				})
				$("[name='vertex_content']").on("change", evt =>
				{ 
					vertex_data.content = evt.currentTarget.value;
					jQuery("[name='vertex_data']").val( JSON.stringify( vertex_data ) );
				})
				break;
			case "shm-trac-dnld-gpx":
				var text = datas.text;
				var name = datas.name;
				var encodedUri = 'data:application/xml;charset=utf-8,' + encodeURIComponent( text );
				var file = new Blob([text], {type: 'data:application/xml;charset=utf-8'} );
				url = URL.createObjectURL( file );
				var link = document.createElement( "a" );
				link.setAttribute( "href", url );
				link.setAttribute( "download",  name + ".gpx");
				link.innerHTML= "Download";
				document.body.appendChild( link );
				link.click();
				setInterval(() =>
				{
					link.parentNode.removeChild( link );
					window.URL.revokeObjectURL(url);
				})	
				// shm_close_modal();
				break;	
		}
	})
}) 

var getVertexData = function(vertex)
{

	let coords = vertex.geometry.getCoordinates();
	var vertexData = newTrackVertexes.filter(e =>
	{
		return e[0] == coords[0] && e[1] == coords[1]
	})[0];

	return vertexData ? vertexData : [];
}
var start_update_vertex = function(vertex)	
{
	var vertexData = getVertexData(vertex);
	let coords = vertex.geometry.getCoordinates();
	let markerData = vertexData[2] ? vertexData[2] : { };
	var content = jQuery("<div><div class='shm-row shm-h-100'></div></div>");
	content.children("div").append("<div class='shm-4 shm-md-12'>"+ __("Title") + "</div>");
	content.children("div").append("<div class='shm-8 shm-md-12'><input value='" + (markerData.title ? markerData.title : "") + "' name='vertex_title' class='sh-form'></div>");
	content.children("div").append("<div class='shm-4 shm-md-12'>"+ __("Content") + "</div>");
	content.children("div").append("<div class='shm-8 shm-md-12'><textarea rows='12' name='vertex_content' class='sh-form'>" + (markerData.content ? markerData.content : "" ) + "</textarea></div>");
	content.children("div").append("<div class='shm-4 shm-md-12'>"+ __("Type") + "</div>");
	content.children("div").append("<div class='shm-8 shm-md-12' id='shm-track-modal-map-description'></div>");
	content.children("div").append("<div class='shm-12'><input type='hidden' name='vertex_data' class=' sh-form'></div>"); 
	
	shm_add_modal({
		class: "",
		title:  __("Edit vertex"),
		content: content.html(),
		footer:"<button class='upd_track_vertex' >" + __("Update vertex") + "<button>",
	});
	vertex_data = { ...vertex_data, coordinates: coords };

	jQuery("[name='vertex_data']").val( JSON.stringify( vertex_data ) );
	jQuery(".upd_track_vertex").on("click", evt =>	
	{
		update_vertex();
		shm_close_modal();
	});
	/*
	myPolyline.options.set({
		editorMenuManager: function (items, vertex) 
		{ 
			var vd = getVertexData(vertex); 			
			console.log(  vd );
			let icon = vd[2] && vd[2].type ? vd[2].type : -1;
			icon = shmapper.shm_point_type.filter(e => e.id == icon)[0];
			console.log(icon);
			icon = icon ? icon.icon[0] : "";
			icon = icon ? "<span class='shm-mr-2' style='width:20px'><img src='" + icon + "' style='height:17px;'></span>" : "";
			let ttl = "<div class='shm-flex'>" + icon + ( vd[2] && vd[2].title ? vd[2].title : __("Add marker") ) + "</div>";
			items.push({
				title:  ttl,
				onClick: function () 
				{ 
					start_update_vertex(vertex);
				}
			});
			return items;
		}
	})
	*/
	shm_send([ "shm_track_vertex", map__id, vertexData]);
}
/*
*	Синхронизация матрицы данных точек нового пути ( newTrackVertexes ) и данных отредактированной точки ( vertex_data )
*/
var update_vertex = function()
{ 
	newTrackVertexes.forEach((e, i) =>
	{ 
		if(e[0] == vertex_data.coordinates[0] && e[1] == vertex_data.coordinates[1])
		{ 
			newTrackVertexes[i][2] = Object.assign({}, vertex_data) ; 
			delete newTrackVertexes[i][2].coordinates;
			var input 		= getNewTrackInput( map__id );
			var json 		= getNewTrackData( map__id );
			json.coords 	= newTrackVertexes; 
			input.val( JSON.stringify( json ) ); 
		}
	})
}
/*
*	
*/
var getNewTrackInput = function( map_id )
{
	return  jQuery( ".shm-form-request[map_id='" + map_id + "'] .form-field-shmapper_track_draw input.sh-form" );
}

	
/*
*	
*/
var getNewTrackData = function( map_id )
{
	var input 	= getNewTrackInput(map_id);	
	var obj		= input.val() ? JSON.parse( input.val()) : { coords:[], markers:[] }; 
	if(!obj.markers || !Array.isArray(obj.markers) )
	{
		obj.markers = [];
	}
	return obj;
}
	
/*
*	
*/
var updateNewTrackData = function( map_id, coord_array )
{ 
	// vertexes update
	if(!Array.isArray(newTrackVertexes)) 
	{
		newTrackVertexes = [];
	} 
	coord_array.forEach((e, i) =>
	{
		if(!Array.isArray(newTrackVertexes[i]))
		{
			newTrackVertexes[i] = [];
		}			
		newTrackVertexes[i][0] = coord_array[i][0]; // lat
		newTrackVertexes[i][1] = coord_array[i][1]; // lon
	})

	var input 		= getNewTrackInput( map_id );
	var json 		= getNewTrackData( map_id );
	json.coords 	= newTrackVertexes; 
	input.val( JSON.stringify( json ) ); 
}

/*
	@map - yandwx map object
	@new_mark_coords - array float [lat, lon]
	@markerData
		background_image
		width
		height
		shm_type_id
		shm_clr
		post_title
		post_content
	@params
		draggable
		isEdited		
*/
var drawPlacemark = function (map, new_mark_coords, markerData, params )
{
	var MyIconContentLayout = ymaps.templateLayoutFactory.createClass(
		'<div style="background-color: #FFFFFF; font-weight: bold;">$[properties.iconContent]</div>'
	);
	var bg = markerData['background_image'];
	if( bg && bg !== 'none')
	{
		bg 			= bg ? bg.replace('url(','').replace(')','').replace(/\"/gi, '') : "";
		var width 	= markerData.width 	? markerData.width 	: 22;
		var height 	= markerData.height ? markerData.height : 22;
		shm_paramet = {
			balloonMaxWidth: 425,
			hideIconOnBalloonOpen: true,
			iconLayout: 'default#imageWithContent',
			iconShadow:true,
			iconImageHref: bg,
			iconImageSize:[ width, height ], 
			iconImageOffset: [ -width / 2, -height ],
			draggable:params.draggable,
			term_id:markerData['shm_type_id'],
			type:'point',
			fill:true,
			fillColor: markerData['shm_clr'] ? markerData['shm_clr'] : '#FF0000',
			iconContentLayout: MyIconContentLayout,
			opacity:0.22,
			hasBalloon:false				
		};
	}
	else
	{
		shm_paramet = {
			balloonMaxWidth: 425,
			hideIconOnBalloonOpen: true,
			iconColor: markerData['shm_clr'] ? markerData['shm_clr']:'#FF0000',
			preset: 'islands#dotIcon',
			draggable:params.draggable,
			term_id:markerData['shm_type_id'],
			type:'point',
			fill:true,
			fillColor: '#FF0000',
			iconShadow:true,
			opacity:0.22,
			hasBalloon:false
		}
	}
	var post_title = markerData["post_title"] 		? markerData["post_title"] 	: "";
	// Temporary remove point background color
	markerData[ "shm_clr" ] = '';
	var shm_placemark = new ymaps.GeoObject(
		{ 
			options:
			{ 
				hideIconOnBalloonOpen: true, 
				draggable: params.draggable
				
			},
			properties:
			{ 
				shm_type_id		: markerData["shm_type_id"],
				term_id			: markerData["shm_type_id"],
				post_title 		: post_title,
				post_content 	: markerData["post_content"]	? markerData["post_content"]: "",
				hideIconOnBalloonOpen: true,
				iconContent		: "<div class='shm-track-marker-icon' style='width:" + ( width + 8 ) + "px;'><div class='shm-track-marker-icon-img' style='width:" + ( width + 8 ) + "px; height:" + ( height + 8 ) + "px; background-color:" + markerData[ "shm_clr" ] + ";'></div><div class='shm-track-marker-icon-descr' style='margin-top:" + ( height + 8 ) + "px; width:" + ( width + 8 ) + "px; '>" + post_title + "</div></div>",
				balloonContentHeader: '<input type=\"text\" name=\"shm_track_marker_post_title\" class=\"min-width-420\"  placeholder=\"Put new Place\'s title\" value=" ">',
				balloonContentBody: '<div><textarea class=\"min-width-420\"  name=\"shm_track_marker_post_content\" placeholder=\"Put new Place\'s content\" rows=4 style=\"width:100%;\"> </textarea> <div class=\"spacer-5\"></div> <div> <div class=\"button place-del-btn\" >Delete Marker</div> </div> <div class=\"spacer-10\"></div>  </div>'
			},
			geometry: 
			{
				type: 'Point',
				coordinates: new_mark_coords
			},
		} , 
		shm_paramet
	);
	shm_placemark.events.add("click", e =>
	{
		var tg = e.get("target"); 
		var post_title = tg.properties._data.post_title;
		var post_content = tg.properties._data.post_content;
		shm_track_place = shm_placemark;
		if(params.isEdited)
		{
			addSubDialog({ 
				title:"<input type='text' name='shm_track_marker_post_title' placeholder=\"Put new Place's title\" value='" + post_title + "'/>", 
				content:"<textarea name='shm_track_marker_post_content' placeholder=\"Put new Place's content\" rows=4 style='width:100%;height:100%;'>" + post_content + "</textarea>",
				footer : "<button class='update_placemark'>" + __("Update Placemark") + "</button><button class='remove_placemark'>" + __("Remove Placemark") + "</button>" 
			});		 
			jQuery("[name=shm_track_marker_post_title]").on( "change", e =>
				{
					shm_placemark.properties.set({
						post_title : e.currentTarget.value
					});				
				})  
				jQuery("[name=shm_track_marker_post_content]").on( "change", e =>
				{
					shm_placemark.properties.set({
						post_content : e.currentTarget.value
					})
					//update_track_placmarks_json( map ); 
				})  
				jQuery(".update_placemark").on( "click", e =>
				{ 
					update_track_placmarks_json( map );	
					removeSubDialog(); 
				}) 
				jQuery(".remove_placemark").on( "click", e =>
				{
					if( confirm( __( "Remove placemark?" ) ) )
					{
						shm_placemark.setParent();						
						update_track_placmarks_json( map );
						removeSubDialog();
					}
				}) 
		}
		else
		{
			addSubDialog({ 
				title:"<div name='shm_track_marker_post_title'>" + post_title + "</div>", 
				content:"<div name='shm_track_marker_post_content' style='width:100%;height:100%;'>" + post_content + "</div>",
				footer : "<button class='close_placemark'>" + __("Close") + "</button>",
				isLock:true
			});	
			jQuery("button.close_placemark").on("click", e =>
			{
				removeSubDialog();
			});
		}
	})
	return shm_placemark;
} 
	
/*
*
*/
var addSubDialog = function(params)
{ 
	jQuery("html").append("<div class='subdialog'></div>");
	jQuery(".subdialog").append("<div class='subdialog-matter'></div>");
	jQuery(".subdialog").append("<div class='subdialog-body'></div>");  
	jQuery(".subdialog-body").append("<div class='subdialog-close'>x</div>"); 
	jQuery(".subdialog-matter, .subdialog-body .subdialog-close").click(() => 	removeSubDialog() );
	jQuery(".subdialog-body").append("<div class='subdialog-title'>" + params.title + "</div>"); 
	jQuery(".subdialog-body").append("<div class='subdialog-content'>" + params.content + "</div>"); 
	jQuery(".subdialog-body").append("<div class='subdialog-footer'></div>"); 
	jQuery(".subdialog-footer").append(params.footer); 
}
var removeSubDialog = function()
{
	jQuery(".subdialog").detach();
} 

	
/*
*
*/
var parseXml = function(xml, arrayTags)
{
    var dom = null;
    if (window.DOMParser)
    {
        dom = (new DOMParser()).parseFromString(xml, "text/xml");
    }
    else if (window.ActiveXObject)
    {
        dom = new ActiveXObject('Microsoft.XMLDOM');
        dom.async = false;
        if (!dom.loadXML(xml))
        {
            throw dom.parseError.reason + " " + dom.parseError.srcText;
        }
    }
    else
    {
        throw "cannot parse xml string!";
    }

    function isArray(o)
    {
        return Object.prototype.toString.apply(o) === '[object Array]';
    }

    function parseNode(xmlNode, result)
    {
        if (xmlNode.nodeName == "#text") {
            var v = xmlNode.nodeValue;
            if (v.trim()) {
               result['#text'] = v;
            }
            return;
        }

        var jsonNode = {};
        var existing = result[xmlNode.nodeName];
        if(existing)
        {
            if(!isArray(existing))
            {
                result[xmlNode.nodeName] = [existing, jsonNode];
            }
            else
            {
                result[xmlNode.nodeName].push(jsonNode);
            }
        }
        else
        {
            if(arrayTags && arrayTags.indexOf(xmlNode.nodeName) != -1)
            {
                result[xmlNode.nodeName] = [jsonNode];
            }
            else
            {
                result[xmlNode.nodeName] = jsonNode;
            }
        }

        if(xmlNode.attributes)
        {
            var length = xmlNode.attributes.length;
            for(var i = 0; i < length; i++)
            {
                var attribute = xmlNode.attributes[i];
                jsonNode[attribute.nodeName] = attribute.nodeValue;
            }
        }

        var length = xmlNode.childNodes.length;
        for(var i = 0; i < length; i++)
        {
            parseNode(xmlNode.childNodes[i], jsonNode);
        }
    }

    var result = {};
    for (let i = 0; i < dom.childNodes.length; i++)
    {
        parseNode(dom.childNodes[i], result);
    }
	return result;
}