var myMap, track_id, shm_placemark, new_mark_coords, track_points = [], tPoints=[], tMarkers=[], MyIconContentLayout;
jQuery(document).ready(function($)
{
	$(".shm_notice_close").on("click", e =>
	{ 
		$(e.currentTarget).parents(".shm_notice").fadeOut("slow");
	})
	/*
	*	open modal to see GPX source
	*/
	jQuery('[gpx-src-id]').on('click', function(evt)
	{ 
		shm_add_modal({
			title: 		__("GPX data"),
			content: 	'<textarea rows=14 style=\'width:100%;\'>'+ jQuery(evt.currentTarget).find('pre').html() +'</textarea>'
			
		});
	}); 
	jQuery('[gpx-dnld-file]').on('click', function(evt)
	{
		var text = jQuery(evt.currentTarget).parent( ).find('[gpx-src-id] pre').html();
		var encodedUri = 'data:application/xml;charset=utf-8,' + encodeURIComponent( text );
		var file = new Blob([text], {type: 'data:application/xml;charset=utf-8'} );
		url = URL.createObjectURL( file );
		var link = document.createElement( "a" );
		link.setAttribute( "href", url );
		link.setAttribute( "download",  jQuery(this).attr("gpx-data-title") + ".gpx" );
		link.innerHTML= "Download";
		document.body.appendChild( link );
		link.click();
		setInterval(() =>
		{
			link.parentNode.removeChild( link );
			window.URL.revokeObjectURL(url);
		})			
	})
	/*
	* form field
	*/
	var showHideGPXParams = function()
	{
		if( $("[type_id='shmapper_track_draw'] #gpx3").is(":checked") )
		{
			$("[type_id='shmapper_track_draw'] #gpx3").parents(".shm-row").find("[type_id='shmapper_track_draw'] .shm-title-label").slideDown("slow" );
			$("[type_id='shmapper_track_draw'] #gpx3").parents(".shm-row").find("[type_id='shmapper_track_draw'] .shm-placeholder-label").slideDown("slow" );
			$("[type_id='shmapper_track_draw'] #gpx3").parents(".shm-row").find("[type_id='shmapper_track_draw'] .shm-description-label").slideDown("slow" );
			$("[type_id='shmapper_track_draw'] #gpx3").parents(".shm-row").find("[type_id='shmapper_track_draw'] .shm-file_require-label").slideDown("slow" );
		}
		else
		{
			$("[type_id='shmapper_track_draw'] #gpx3").parents(".shm-row").find("[type_id='shmapper_track_draw'] .shm-title-label").slideUp("slow" );
			$("[type_id='shmapper_track_draw'] #gpx3").parents(".shm-row").find("[type_id='shmapper_track_draw'] .shm-placeholder-label").slideUp("slow" );
			$("[type_id='shmapper_track_draw'] #gpx3").parents(".shm-row").find("[type_id='shmapper_track_draw'] .shm-description-label").slideUp("slow" );
			$("[type_id='shmapper_track_draw'] #gpx3").parents(".shm-row").find("[type_id='shmapper_track_draw'] .shm-file_require-label").slideUp("slow" );
		}
	}
	$("[type_id='shmapper_track_draw'] #gpx3").on("change", evt =>
	{
		showHideGPXParams();
	});
	showHideGPXParams();

	/*
	 *	Edit track
	 */
	if ( typeof ymaps !== 'undefined' ){

		ymaps.ready( function()
		{
			if($("#shm_map").length == 0) return;
			myMap = new ymaps.Map('shm_map', {
				center: [55.73, 37.75],
				zoom: 8
			}, {
				searchControlProvider: 'yandex#search'
			});
			
			//console.log(tMarkers);
			var myPolyline = new ymaps.Polyline(
				tPoints,
				{ }, 
				{ 
					editorDrawingCursor: 'crosshair',
					strokeWidth: shmTrackWidth,
					strokeColor: shmTrackColor,
					editorMenuManager: function (items) 
					{
						
						return items;
					}
				}
			);
			myMap.geoObjects.add(myPolyline);
			myPolyline.editor.startEditing();
			myPolyline.geometry.events.add('change', function(evt) {
				var points = myPolyline.geometry.getCoordinates().map( function( e ) {
					return e.filter( function( el ) { //return e.join(',')
						return el != null;
					});
				});
				var pointsVal = '[[' + points.join( '],[' ) + ']]';
				jQuery( '[name=track]' ).val( pointsVal );
			});
			var markers = tMarkers.map(function(elem)
			{ 
				var pointt = "<div shm_type_id='" + elem.shm_point_type + "'  shm_clr='" + elem.shm_clr + "' class='shm-type-icon-1 ui-draggable ui-draggable-handle' style='background-image: url(" + elem.icon + "); position: relative;' shm_type_id='" + elem.shm_point_type + "' data-icon-width='24' data-icon-height='24', post_title=\"" + elem.post_title + "\", post_content=\"" + elem.post_content + "\" coorde='" +elem.coords.join(",") + "' marker_id='" + elem.marker_id + "'></div>";
				$("body").append(pointt);
				return shmapperPlaceMarkerOnMapByCoords(
					myMap,
					elem.coords,
					$(pointt)
				);
				$(pointt);
			});
			myMap.setBounds(myPolyline.geometry.getBounds());   
		
			if(jQuery('.shm-type-icon-1').length)
			{ 
				jQuery('.shm-type-icon-1').draggable(
				{
					revert: false,
					start: (evt, ui) => 
					{
						
					},
					stop: (evt, ui) =>
					{
						var _this = $(ui.helper); 
						_this.addClass('shmapperDragged');
						shmapperPlaceMarkerOnMap(evt, ui, true );
						$('.shm-type-icon-1.shmapperMarkerSelected').removeClass('shmapperMarkerSelected');
					}
				});	
				
			}
			function shmapperPlaceMarkerOnMap(evt, ui, isNew )
			{ 
				var globalPixelPoint = myMap.converter.pageToGlobal( [evt.clientX, evt.clientY + window.scrollY] );
				new_mark_coords = myMap.options.get('projection').fromGlobalPixels(globalPixelPoint, myMap.getZoom());
				shmapperPlaceMarkerOnMapByCoords(myMap, new_mark_coords, $(ui.helper), isNew);
			}
			function shmapperPlaceMarkerOnMapByCoords(map, new_mark_coords, _markerIcon, isNew=false) 
			{
				MyIconContentLayout = ymaps.templateLayoutFactory.createClass(
					'<div style="background-color: #FFFFFF; font-weight: bold;">$[properties.iconContent]</div>'
				);
				var bg = _markerIcon.css('background-image');
				if( bg !== 'none')
				{
					bg = bg.replace('url(','').replace(')','').replace(/\"/gi, '');
					shm_paramet = {
						balloonMaxWidth: 425,
						hideIconOnBalloonOpen: false,
						iconLayout: 'default#imageWithContent',
						iconShadow:true,
						iconImageHref: bg,
						iconImageSize:[22,22], 
						iconImageOffset: [-11, -22],
						draggable:true,
						term_id:_markerIcon.attr('shm_type_id'),
						type:'point',
						fill:true,
						fillColor: _markerIcon.attr('shm_clr') ? _markerIcon.attr('shm_clr'):'#FF0000',
						iconContentLayout: MyIconContentLayout,
						opacity:0.22,
						marker_id:  _markerIcon.attr("marker_id"),
						hasBalloon:false
						
					};
				}
				else
				{
					shm_paramet = {
						balloonMaxWidth: 425,
						hideIconOnBalloonOpen: true,
						iconColor: _markerIcon.attr('shm_clr') ? _markerIcon.attr('shm_clr'):'#FF0000',
						preset: 'islands#dotIcon',
						draggable:true,
						term_id:_markerIcon.attr('shm_type_id'),
						type:'point',
						fill:true,
						fillColor: '#FF0000',
						iconShadow:true,
						opacity:0.22,
						marker_id:  _markerIcon.attr("marker_id"),
						hasBalloon:false
					}
				}
				
				shm_placemark = new ymaps.GeoObject(
					{ 
						options:
						{
							hideIconOnBalloonOpen: true, 
							draggable: true
							
						},
						properties:
						{
							hideIconOnBalloonOpen: true,
							// Temporarily disable marker background color
							//iconContent:"<div style='width:32px; height:32px; background-color:" +_markerIcon.attr("shm_clr")+ "; z-index:-1; position:absolute;top:-4px; left:-4px;'></div>",
							iconContent:"<div style='width:32px; height:32px; z-index:-1; position:absolute;top:-4px; left:-4px;'></div>",
							balloonContentHeader: '<input type=\"text\" name=\"shm_marker_post_title\" class=\"min-width-420\"  placeholder=\"Put new Place\'s title\"value="' + _markerIcon.attr("post_title") + '">',
							balloonContentBody: '<div><textarea class=\"min-width-420\"  name=\"shm_marker_post_content\" placeholder=\"Put new Place\'s content\" rows=4 style=\"width:100%;\">' + _markerIcon.attr("post_content") + '</textarea> <div class=\"spacer-5\"></div> <div> <div class=\"button place-del-btn\" >Delete Marker</div> </div> <div class=\"spacer-10\"></div>  </div>',
							marker_id:  	_markerIcon.attr("marker_id"),
							shm_type_id: 	_markerIcon.attr("shm_type_id"),
							post_title:		_markerIcon.attr("post_title"),
							post_content:	_markerIcon.attr("post_content")
						},
						geometry: 
						{
							type: 'Point',
							coordinates: new_mark_coords
						},
					} , 
					shm_paramet
				);			
				if(isNew)
				{			
					addAdress( _markerIcon, new_mark_coords );
					_markerIcon.css({left:0, top:0}).hide().fadeIn('slow');
					_markerIcon.parents('.shm-form-placemarks').removeAttr('required').removeClass('shm-alert');		
					_markerIcon.data('straight_geocoding', '');
					addTrack(shm_placemark, _markerIcon.attr('shm_type_id'));
				}
				shm_placemark.events.add('dragend', evt =>
				{
					var pos = evt.get('position');
					var targ = evt.get("target");
					var globalPixelPoint = map.converter.pageToGlobal( [pos[0], pos[1]] );
					new_mark_coords = map.options.get('projection').fromGlobalPixels(globalPixelPoint, map.getZoom());
					addAdress( _markerIcon, new_mark_coords ); 
					shm_send([
						"shm_chande_track_point", 
						{
							marker_id : targ.properties._data.marker_id,
							post_title: targ.properties._data.post_title,
							post_content: targ.properties._data.post_content,
							shm_type_id : targ.properties._data.shm_type_id,
							point_type : "point_type", 
							coordinates : new_mark_coords//targ.geometry._coordinates
							
						} 
					])
				});
				map.geoObjects.add(shm_placemark); 
				
				shm_placemark.events.add( "click", e =>
				{
					var tg = e.get("target"); 
					console.log( tg );
					var post_title = tg.properties._data.post_title;
					var post_content = tg.properties._data.post_content;
					shm_track_place = shm_placemark;
					addSubDialog({ 
						title:"<input type='text' name='shm_track_marker_post_title' placeholder=\"Put new Place's title\" value='" + post_title + "'/>", 
						content:"<textarea name='shm_track_marker_post_content' placeholder=\"Put new Place's content\" rows=4 style='width:100%;height:100%;'>" + post_content + "</textarea>",
						footer : "<button class='update_placemark button'>" + shmapper_track.updatePlacemark + "</button><button class='remove_placemark button'>" +  shmapper_track.removePlacemark + "</button>" 
					});		 
					jQuery("[name=shm_track_marker_post_title]").on( "change", e =>
						{
							tg.properties.set({
								post_title : e.currentTarget.value
							});		
							//update_track_placmarks_json( tg );		
						})  
						jQuery("[name=shm_track_marker_post_content]").on( "change", e =>
						{
							tg.properties.set({
								post_content : e.currentTarget.value
							})
							//update_track_placmarks_json( tg ); 
						})  
						jQuery(".update_placemark").on( "click", e =>
						{ 
							update_track_placmarks_json( tg );	
							removeSubDialog(); 
						}) 
						jQuery(".remove_placemark").on( "click", e =>
						{
							if( confirm( __( "Remove placemark?" ) ) )
							{ 
								tg.setParent();						
								shm_send([
									"shm_remove_track_point", 
									tg.properties._data.marker_id
								])
								removeSubDialog();
							}
						}) 
				
				});
				
			}
			function addAdress( _markerIcon, new_mark_coords )
			{
				
			}
			function addTrack( shm_placemark, shm_type_id )
			{
				track_points.push( shm_placemark );
				shm_send([
					"shm_add_track_point", 
					{
						track_id : track_id,
						shm_type_id : shm_type_id,
						point_type : "point_type", 
						geometry : shm_placemark.geometry._coordinates
					} 
				])
			}
			function removeTrack(shm_placemark)
			{
				
			}
			$('.place-del-btn').on('click', function(evt)
			{
				
			});
			var update_placemark = function( mark_id )
			{
				
			} 
		} );

	}

	var setUpdateTrackPoints = function( )
	{ 
		jQuery("[name='shm_marker_post_title']").off("change",  e =>
		{
			updTrackPoint(e);
		});
		jQuery("[name='shm_marker_post_title']").on("change",  e =>
		{
			updTrackPoint(e);
		});
	}
	var updTrackPoint = function( evt )
	{
		console.log( jQuery( evt.currentTarget ).val() );
		
	}
	
	document.documentElement.addEventListener("_shm_send_", function(evt) 
	{
		var dat = evt.detail;
		var command	= dat[0];
		var datas	= dat[1];
		
		switch(command)
		{
			case "shm_add_track_point":			
				console.log( dat );
				break;
			case "shm_chande_track_point":		
				console.log( dat );
				break;
			case "shm_remove_track_point":
				
				break;
				
		}
	})
})
var update_track_placmarks_json = function( target )
{
	console.log(target);
	shm_send([
		"shm_chande_track_point", 
		{
			marker_id : target.properties._data.marker_id,
			shm_type_id : target.properties._data.shm_type_id, 
			coordinates : target.geometry._coordinates,
			post_title: target.properties._data.post_title,
			post_content: target.properties._data.post_content
		} 
	])
}
var addSubDialog = function(params)
{ 
	jQuery("html").append("<div class='subdialog wp-core-ui'></div>");
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

