
var place_new_mark = function(){}, addAdres = function(){}, $this,  new_mark_coords, shm_address, shm_placemark, map, shm_paramet;
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
			/**/
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
	/**/
	
	//send new request
	$("form.shm-form-request").live({ submit: evt =>
		{
			
			evt.preventDefault();
			console.log(shm_img[0]);
			
			var $this = $(evt.currentTarget);
			$this.find("[required]").each((num, elem) =>
			{
				$(elem).removeClass("shm-alert");
				if( $(elem).val() == "" )
				{
					$(elem).addClass("shm-alert");
				}
			});
			if( $this.find(".shm-alert").length )	return;
			var data = $this.serializeArray();
			var d = new FormData();
			data.forEach( evt => {
				if( evt.name=="g-recaptcha-response" ) d.append("cap", evt.value);
			});
			d.append("id", $this.attr("map_id"));
			d.append("shm_point_type", $this.find( "[name='shm_point_type']" ).val());
			d.append("action", "shm_set_req");
			d.append("shm_point_lat", $this.find( "[name='shm_point_lat']" ).val());
			d.append("shm_point_lon", $this.find( "[name='shm_point_lon']" ).val());
			d.append("shm_point_loc", $this.find( "[name='shm_point_loc']" ).val());
			d.append("elem", $this.find( "[name='elem[]']" ).map( (num,el) => el.value ).get() );
			$.each( shm_img, function( key, value ){
				d.append( key, value );
			});
			//d.append("files", shm_img[0]);
			//shm_send([ 'shm_set_req', d]);
			//console.log(d);
			// AJAX запрос
			$.ajax({
				url         : shm_set_req.url,
				type        : 'POST',
				cache       : false,
				dataType    : 'json',
				processData : false,
				contentType : false, 
				success     : function( response, status, jqXHR )
				{
					console.log(response);
					add_message(response.msg);
				},
				data: d,
				error: function( jqXHR, status, errorThrown )
				{
					console.log( 'ОШИБКА AJAX запроса: ', status, jqXHR );
				}

			});
		}		
	});
});
function add_message(msg)
{
	if(msg)
	{
		jQuery(".msg").detach();
		clearTimeout(setmsg);
		jQuery("<div class='msg'>" + msg + "</div>").appendTo("body").hide().fadeIn("slow");
		setmsg = setTimeout( function() {
			jQuery(".msg").fadeOut(700, jQuery(".msg").detach());
		}, 6000);
	}
}