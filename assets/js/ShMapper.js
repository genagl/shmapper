var media_uploader = null, setmsg, $pm_pars={}, shm_add_modal=function(){}, shm_close_modal=function(){}, create_point=function(){}, shm_delete_map_hand = function(){}, shm_map_add_point = function(data){}, shm_img=[];
var __ = function(text)
{
	return voc[text] ? voc[text] : text;
}
function open_media_uploader_image()
{
    media_uploader = wp.media({
        frame:    "post", 
        state:    "insert", 
        multiple: false
    });
    media_uploader.on("insert", function()
	{
        var json = media_uploader.state().get("selection").first().toJSON();

        var image_url = json.url;
        var image_caption = json.caption;
        var image_title = json.title;
		on_insert_media(json);
    });
    media_uploader.open();
}


jQuery(document).ready(function($)
{	
	// ajax
	$("[name='shm_wnext']").live({click:evt =>
	{
		shm_send(['shm_wnext']);	
	}});
	$(".shm_doubled[post_id]").live({click:evt =>
	{
		evt.preventDefault();
		shm_send(['shm_doubled', $(evt.currentTarget).attr("post_id")]);	
	}});
	$("[name='shm_wclose']").live({click:evt =>
	{
		shm_send(['shm_wclose']);	
	}});
	$("#shm_settings_wizzard").live({click:evt =>
	{
		shm_send(['shm_wrestart']);	
	}});
	$(".shm-change-input-change").live({click:evt =>
	{
		evt.preventDefault();
		var command = $(evt.currentTarget).attr("c");
		var num		= $(evt.currentTarget).parents("[shm-num]").attr("shm-num");
		var post_id	= $(evt.currentTarget).parents("section[post_id]").attr("post_id");
		shm_send([ command, num, post_id ]);	
	}});
	$(".admin_voc").live({change:evt =>
	{
		$("#shm_vocabulary_cont").css("opacity", 0.7);
		shm_send(["shm_voc", $(evt.currentTarget).attr("name"), $(evt.currentTarget).val()]);	
	}});
	$("[name='map_api']").live({click:evt =>
	{
		$(".map_api_cont").css("opacity", 0.7);
		shm_send(["map_api", $(evt.currentTarget).val()]);	
	}});
	$("#shm_map_is_crowdsourced").live({click:evt =>
	{
		$("#shm_map_is_crowdsourced_cont").css("opacity", 0.7);
		shm_send(["shm_map_is_crowdsourced", $(evt.currentTarget).is(":checked") ? 1 : 0]);	
	}});
	$("#shm_map_marker_premoderation").live({click:evt =>
	{
		$("#shm_map_is_crowdsourced_cont").css("opacity", 0.7);
		shm_send(["shm_map_marker_premoderation", $(evt.currentTarget).is(":checked") ? 1 : 0]);	
	}});
	$("#shm_settings_captcha").live({click:evt =>
	{
		$("#shm_settings_captcha_cont").css("opacity", 0.7);
		shm_send(["shm_settings_captcha", $(evt.currentTarget).is(":checked") ? 1 : 0]);	
	}});	
	$("[name=shm_captcha_siteKey]").live({change:evt =>
	{
		$("#shm_settings_captcha_cont").css("opacity", 0.7);
		shm_send(["shm_captcha_siteKey", $(evt.currentTarget).val() ]);	
	}});	
	$("[name=shm_captcha_secretKey]").live({change:evt =>
	{
		$("#shm_settings_captcha_cont").css("opacity", 0.7);
		shm_send(["shm_captcha_secretKey", $(evt.currentTarget).val() ]);	
	}});
	
	$("a.shm-csv-icon[map_id]").live({click:evt =>
	{
		evt.preventDefault();
		shm_send(['shm_csv', $(evt.currentTarget).attr("map_id")]);
	}});
	create_point = function()
	{
		$(".shm-alert").removeClass("shm-alert");
		var s = ["shm-new-point-title", "shm-new-point-content", "shm-new-point-type"];
		var alerting = [];
		s.forEach(elem => 
		{
			if($("[name='" + elem + "']").val() == "")
			{
				alerting.push(elem);
			}
		});
		if(alerting.length)
		{
			alerting.forEach(elem => $("[name='" + elem + "']").addClass("shm-alert"));
			return;
		}
		shm_send(['shm_create_map_point', {
			map_id: $("[name='shm_map_id']").val(),
			latitude: $("[name='shm_x']").val(), 
			longitude: $("[name='shm_y']").val(), 
			post_title: $("[name='shm-new-point-title']").val(), 
			post_content: $("[name='shm-new-point-content']").val(), 
			type: $("[name='shm-new-point-type']").val(),
			location: $("[name='shm-new-point-location']").val(),
		}]);
	}
	shm_delete_map_hand = id =>
	{
		var action = $('[name=shm_esc_points]:checked').val();
		var anover = $("#shm_esc_points_id").val();
		var id = $("[shm_delete_map_id]").attr("shm_delete_map_id");
		shm_send(['shm_delete_map_hndl', {
			action : action,
			anover: anover,
			id: id,
		}])
	}
	// map filter
	$(".shm-map-panel[for] .point_type_swicher>input[type='checkbox']").live({change:evt =>
	{
		var $this = $(evt.currentTarget);
		var uniq = $this.parents("[for]").attr("for");
		var map = shm_maps[uniq];
		var term_id = $this.attr("term_id");
		
		var dat = {
			uniq 	: uniq,
			term_id	: term_id,
			$this	: $this,
			map		: map
		}
		var customEvent = new CustomEvent("shm_filter", {bubbles : true, cancelable : true, detail : dat})
		document.documentElement.dispatchEvent(customEvent);		
		/*
		//yandex map doing	
		var geos = map.geoObjects;
		for(var ii = 0, ll = geos.getLength(); ii < ll; ii++)
		{
			switch(geos.get([ii]).options.get("type"))
			{
				case "clusterer":
					var clusterer  	= geos.get([ii]);
					var mrks 		= clusterer.getGeoObjects();
					for(var i=0, l = mrks.length; i<l; i++ )
					{
						if(term_id == mrks[i].options.get("term_id"))
							mrks[i].options.set({visible : $this.is(":checked")});
					}
					break;
				case "point":
				default:
					if(term_id == geos.get([ii]).options.get("term_id"))
							geos.get([ii]).options.set({visible : $this.is(":checked")});
					break;
			}
		}
		*/
		
	}});
	
	//admin map editor
	shm_map_add_point = function(elem)
	{
		if(map_type == 1)
		{
			var paramet;
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
						type: 'Point', 
						coordinates: [elem.latitude, elem.longitude]
					},
					balloonContentHeader: elem.post_title,
					balloonContentBody: elem.post_content,
					hintContent: elem.post_title
				}, paramet
			);
			shm_maps[elem.mapid].geoObjects.add(myPlacemark);
		}
		else
		{
			var icons=[];
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
				var shoptions = elem.icon != '' ? {icon: icons[elem.term_id]} : {};		
				var marker = L.marker([ elem.latitude, elem.longitude ], shoptions )
					.addTo(shm_maps[elem.mapid])
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
				L.marker([ elem.latitude, elem.longitude ], {icon: myIcon})
					.addTo(shm_maps[elem.mapid])
						.bindPopup('<div class=\"shml-title\">' + elem.post_title +'</div><div class=\"shml-body\">' + elem.post_content + '</div>');
			}
		}
	}
	
	//point_type_swicher
	$(".point_type_swicher .ganre_checkbox").live({click:evt =>
	{
		var types = [];
		var $e = $(evt.currentTarget).parents(".point_type_swicher").find(".ganre_checkbox");
		$e.each((num, elem) =>
		{
			if($(elem).is(":checked"))
				types.push($(elem).attr("term_id"));
		});
		$(evt.currentTarget).parents(".point_type_swicher").find("[point]").val(types.join(","));
	}});
	
	
	//admin form element chooser
	$("#form_editor select[selector]").live({change:evt =>
	{
		var $this = $(evt.currentTarget);
		var flds = $this.find("option:selected").attr("data-fields").split(",");
		var $num =  $this.parents(".shm-row").attr("shm-num");	
		$this.parents(".shm-row [shm-num="+$num + "]").find(".shm-t").hide();
		flds.forEach((elem, num) => {
			$this.parents(".shm-row [shm-num="+$num + "]").find(".shm--" + elem).show();
		});
	}});
	$("[name='tax_input[shm_point_type][]']").live({change:evt=>
	{
		var ch = $(evt.currentTarget).is(":checked");
		$("[name='tax_input[shm_point_type][]']:checked").each( (num, elem) => $(elem).prop('checked', false) );
		$(evt.currentTarget).attr("checked", ch);
	}});
	$("[name=shm_esc_points]").live({change:evt =>
	{
		if($(evt.currentTarget).val() == 3)
		{
			$("#shm_esc_points_id").show();
		}
		else
		{
			$("#shm_esc_points_id").hide();
		}
	}})
	//interface
	$(".shm-close-btn").live({click:evt =>
	{
		$(evt.currentTarget).parents(".shm-win").hide();	
	}})
	
	//
	//media loader
	var prefix;
	var cur_upload_id = 1;
	$( ".my_image_upload" ).click(function() 
	{
		var cur_upload_id = $(this).attr("image_id");
		prefix = $(this).attr("prefix");// "pic_example";
		var downloadingImage = new Image();
		downloadingImage.cur_upload_id = cur_upload_id;
		on_insert_media = function(json)
		{
			//alert(json.id);
			$( "#" + prefix +"_media_id" + cur_upload_id ).val(json.id);
			$( "#" + prefix +"_media_id" + cur_upload_id ).attr("value", json.id);
			downloadingImage.onload = function()
			{								
				$("#" + prefix + this.cur_upload_id).empty().append("<img src=\'"+this.src+"\' width='auto' height='68'>");
				$("#" + prefix + this.cur_upload_id).css({"height":"68px", "width":"68px"});
				
			};
			downloadingImage.src = json.url;		
			//
		}
		open_media_uploader_image();						
	});
	$( ".my_image_upload" ).each(function(num,elem)
	{
		prefix = $(this).attr("prefix");// "pic_example";
		$( elem ).height( $("#" + prefix  + $(elem).attr("image_id")).height() + 0);
	})
	$(".my_image_delete").click(evt=>
	{
		var $prefix = $(evt.currentTarget).attr("prefix");
		var $default = $(evt.currentTarget).attr("default");
		var $targ = $("#" + $prefix + " > img");
		var $input = $("#" + $prefix + "_media_id");
		$targ.attr("src", $default);
		$input.val("");
	});
	
	
	// input file
	$(".shm-form-file > input[type='file']").each((num, elem) =>
	{
		$(elem).live({change:evt =>
		{
			var file	= evt.target.files[0];	
			if(!file)	return;
			shm_img		= evt.target.files;
			var img 	= document.createElement("img");
			img.height	= 50;
			//img.id 		= this.props.prefix + 'imagex';
			img.style	= "height:50px; margin-right:5px;";
			img.alt 	= '';
			img.file 	= file;
			img.files	= evt.target.files;
			$(evt.currentTarget).parent().find("img").detach();
			$(evt.currentTarget).parent().find("label").text("");
			$(evt.currentTarget).parent().prepend(img);
			var reader = new FileReader();
			reader.g = this;
			reader.onload = (function(aImg) 
			{ 
				return function(e) 
				{ 
					aImg.src = e.target.result; 
					//console.log(aImg.src);
					//reader.g.setState({url:aImg.src});
					//reader.g.props.onChange( aImg.src, aImg.file,  );
				}; 
			})(img);
			reader.readAsDataURL(file);
		}})
	});
	//
	shm_add_modal = function (data)
	{
		if(typeof data == "string")
		{
			data={content: data};
		}
		if(!data.title) data.title = __("Attantion");
		$("html").append("<div class='shm_modal_container'></div>");
		$(".shm_modal_container").append("<div class='shm_modal'></div>");
		$(".shm_modal_container").append("<div class='shm_modal_screen wp-core-ui'></div>");
		$(".shm_modal_screen").append("<div class='shm_modal_header shm-color-grey'>" + data.title + "</div>");
		$(".shm_modal_header").append("<div class='shm_modal_close' onclick='shm_close_modal();'>x</div>");
		$(".shm_modal_screen").append("<div class='shm_modal_body'>" + data.content + "</div>");
		$(".shm_modal_screen").append("<div class='shm_modal_footer'></div>");
		if(data.send)
			$(".shm_modal_footer").append("<button class='button' onClick='" + data.sendHandler + "(" + data.sendArgs + ");'>"+ data.send + "</button>");
		$(".shm_modal_footer").append("<button class='button' onclick='shm_close_modal();'>"+__("Close") + "</button>");
		$(".shm_modal").click(evt =>
		{
			$(evt.currentTarget).parents(".shm_modal_container").detach();
		});
	}
	shm_close_modal = function()
	{
		$(".shm_modal_container").detach();
	}


});






var shm_send = (params, type=-1) =>
{
	console.log(params, type);
	jQuery.post	(
		myajax.url,
		{
			action	: 'myajax',
			nonce	: myajax.nonce,
			params	: params
		},
		function( response ) 
		{
			console.log(response);
			try
			{
				var dat = JSON.parse(response);
			}
			catch (e)
			{
				return;
			}
			//alert(dat);
			var command	= dat[0];
			var datas	= dat[1];
			//console.log(command);
			switch(command)
			{
				case "test":
					break;
				case "shm_wclose":
					$(".shm_wizzard").detach();
					$(".shm_wizzard_current").removeClass("shm_wizzard_current");
					break;
				case "shm_doubled":
					window.location.reload(window.location.href);
					break;
				case "shm_wrestart":
					window.location.reload(window.location.href);
					break;
				case "shm_csv":
					var encodedUri = 'data:application/csv;charset=utf-8,' + encodeURIComponent(datas['text']);
					var link = document.createElement("a");
					link.setAttribute("href", datas['text']);
					link.setAttribute("download", datas['name'] + ".csv");
					link.innerHTML= "Download";
					document.body.appendChild(link);
					link.click();
					//link.parentNode.removeChild(link);
					break;
				case "shm_set_req":
					if(datas['grecaptcha'] == 1)
						grecaptcha.reset();
					break;
				case "shm_add_before":
					$(datas['text'])
						.insertBefore("#form_editor li:eq(" + datas['order'] + ")")
							.hide()
								.slideDown("slow");
					$("#form_editor li").each(
						(num, elem) => {
							var prev_id = $(elem).attr("shm-num");
							$(elem).attr("shm-num", num);
							$(elem).find("[name^='form_forms[" + prev_id + "]']").each( (n, e) => {
								var name = $(e).attr("name").replace("form_forms[" + prev_id + "]", "form_forms[" + num + "]");
								$(e).attr("name", name);
							});
						}							
					);
					break;
				case "shm_add_after":
					$(datas['text'])
						.insertAfter("#form_editor li:eq(" + datas['order'] + ")")
							.hide()
								.slideDown("slow");
					$("#form_editor li").each(
						(num, elem) => {
							var prev_id = $(elem).attr("shm-num");
							$(elem).attr("shm-num", num);
							$(elem).find("[name^='form_forms[" + prev_id + "]']").each( (n, e) => {
								var name = $(e).attr("name").replace("form_forms[" + prev_id + "]", "form_forms[" + num + "]");
								$(e).attr("name", name);
							});
						}							
					);
					break;
				case "shm_wnext":
					if(datas['href'])
						window.location.href = datas['href'];
					$(".shm_wizzard").detach();
					$(".shm_wizzard_current").removeClass("shm_wizzard_current");
					break;	
				case "shm_delete_map_hndl":
					shm_close_modal();
					jQuery("#post-"+datas['id']).slideUp( 800 ).hide("slow");
					window.location.reload(window.location.href);
					break;	
				case "shm_create_map_point":
					shm_close_modal();
					shm_map_add_point(datas['data']);
					break;	
				case "shm_add_point_prepaire":
				case "shm_delete_map":
					shm_add_modal( datas['text'] );
					break;	
				case "shm_notify_req":
					$( "#post-" + datas['post_id'] + " .column-notified" ).empty().append(datas['text']);
					break;	
				case "shm_trash_req":
					$( "#post-" + datas['post_id'] + "" ).fadeOut("slow");
					window.location.reload(window.location.href);
					break;	
				case "map_api":
					$(".map_api_cont").css("opacity", 1);
				case "shm_voc":
					$("#shm_vocabulary_cont").css("opacity", 1);
					break;	
				case "shm_map_is_crowdsourced":
				case "shm_map_marker_premoderation":
					$("#shm_map_is_crowdsourced_cont").css("opacity", 1);
					break;	
				case "shm_settings_captcha":
				case "shm_captcha_siteKey":
				case "shm_captcha_secretKey":
					$("#shm_settings_captcha_cont").css("opacity", 1);
					break;				
				default:
					var customEvent = new CustomEvent("_shm_send_", {bubbles : true, cancelable : true, detail : dat})
					document.documentElement.dispatchEvent(customEvent);					
					break;
			}			
			if(datas['exec'] && datas['exec'] != "")
			{
				window[datas['exec']](datas['args']);
			}
			if(datas['a_alert'])
			{
				alert(datas['a_alert']);
			}
			if(datas.msg)
			{
				jQuery(".msg").detach();
				clearTimeout(setmsg);
				jQuery("<div class='msg'>" + datas.msg + "</div>").appendTo("body").hide().fadeIn("slow");
				setmsg = setTimeout( function() {
					jQuery(".msg").fadeOut(700, jQuery(".msg").detach());
				}, 6000);
			}
		}		
	);
} 