<?php

class ShMapper 
{
	static function activate()
	{
		global $wpdb;
		$wpdb->query("CREATE TABLE IF NOT EXISTS `shmp_point_map` (
			`ID` int(255) unsigned NOT NULL AUTO_INCREMENT,
			`point_id` int(255) unsigned NOT NULL,
			`map_id` int(255) unsigned NOT NULL,
			`date` int(31) unsigned NOT NULL,
			`session_id` int(255) unsigned NOT NULL DEFAULT '1',
			`approved_date` int(31) unsigned NOT NULL DEFAULT '1',
			`approve_user_id` int(255) unsigned NOT NULL,
			PRIMARY KEY (`ID`)
		) ENGINE=MyISAM DEFAULT CHARSET=cp1251 AUTO_INCREMENT=1 ;");
				update_option(SHMAPPER,[
			"map_api"	=> 1,
			"shm_map_is_crowdsourced"	=> 0,
			"shm_map_marker_premoderation"	=> 0,
			"wizzard" => 1
		]);
	}
	static function deactivate()
	{
		
	}
	static $options;
	static $instance;
	static function get_instance()
	{
		if(!static::$instance)
			static::$instance = new static;
		return static::$instance;
	}
	static function update_options()
	{
		update_option( SHMAPPER, static::$options );
		static::$options = get_option(SHMAPPER);
	}
	function __construct()
	{	
		static::$options = get_option(SHMAPPER);
		add_action( "init", 						[__CLASS__, "add_shortcodes"], 80);
		add_action( "wp_head",						[__CLASS__, "set_styles"]);
		add_filter( "smc_add_post_types",	 		[__CLASS__, "init_obj"], 10);
		add_action( 'admin_menu',					[__CLASS__, 'admin_page_handler'], 9);
		add_action( 'admin_enqueue_scripts', 		[__CLASS__, 'add_admin_js_script'], 99 );
		add_action( 'wp_enqueue_scripts', 			[__CLASS__, 'add_frons_js_script'], 99 );
		add_action( "admin_footer", 				[__CLASS__, "add_wizzard"]);
	}
	static function init_obj($init_object)
	{
		if(!is_array($init_object)) $init_object = [];
		$point						= [];
		$point['t']					= ['type'=>'post'];	
		$point['location']			= ['type' => 'string', "name" => __("Location", SHMAPPER)];
		$point['latitude']			= ['type'=>'string', "name" => __("Latitude", SHMAPPER)];
		$point['longitude']			= ['type'=>'string', "name" => __("Longitude", SHMAPPER)];
		$point['zoom']				= ['type'=>'number', "name" => __("Zoom", SHMAPPER)];
		$init_object[SHM_POINT]		= $point;
		
		$map						= [];
		$map['t']					= ['type'=>'post'];	
		$map['latitude']			= ['type'=>'string', "distination" => "map", "name" => __("Latitude", SHMAPPER)];
		$map['longitude']			= ['type'=>'string', "distination" => "map", "name" => __("Longitude", SHMAPPER)];
		$map['zoom']				= ['type'=>'number', "distination" => "map", "name" => __("Zoom", SHMAPPER)];
		$map['is_legend']			= ['type'=>'boolean', "distination" => "map", "name" => __("Legend exists", SHMAPPER)];
		$map['is_filtered']			= ['type'=>'boolean', "distination" => "map", "name" => __("Filters exists", SHMAPPER)];
		$map['is_csv']				= ['type'=>'boolean', "distination" => "map", "name" => __("Export csv", SHMAPPER)];	
		$map['is_lock']				= ['type'=>'boolean', "distination" => "map", "name" => __("Lock zoom and drag", SHMAPPER)];	
		$map['is_clustered']		= ['type'=>'boolean', "distination" => "map", "name" => __("Formating Marker to cluster", SHMAPPER)];	
		$map['is_form']				= ['type'=>'boolean', "distination" => "form", "name" => __("Form exists", SHMAPPER)];
		$map['notify_owner']		= ['type'=>'boolean', "distination" => "form", "name" => __("Notify owner of Map", SHMAPPER)];
		$map['form_title']			= ['type'=>'string',  "distination" => "form", "name" => __("Form Title", SHMAPPER)];
		$map['form_forms']			= ['type'=>'form_editor',  "distination" => "form", "name" => __("Form generator", SHMAPPER)];
		//$location['location_type'] = ['type'=>'id', 	'object'=>"location_type"];
		$init_object[SHM_MAP]		= $map;
		
		
		$req						= [];
		$req['t']					= ['type' => 'post'];
		$req['map']					= ['type' => 'post', "object" => SHM_REQUEST, "color"=> "#5880a2", "name" => __("Map", SHMAPPER)];	
		$req['title']				= ['type' => 'string', "name" => __("Title")];	
		$req['description']			= ['type' => 'string', "name" => __("Description", SHMAPPER)];	
		$req['latitude']			= ['type' => 'string', "name" => __("Latitude", SHMAPPER)];	
		$req['longitude']			= ['type' => 'string', "name" => __("Longitude", SHMAPPER)];
		$req['location']			= ['type' => 'string', "name" => __("Location", SHMAPPER)];
		$req['type']				= ['type' => 'taxonomy', "object" => SHM_POINT_TYPE, "name" => __("Type", SHMAPPER)];
		$req['session']				= ['type' => 'id', "object" => "session", "name" => __("Session", SHMAPPER)];
		$req['author']				= ['type' => 'string', "name" => __("Author")];
		$req['contacts']			= ['type' => 'array', "name" => __("Contacts", SHMAPPER)];
		$req['notified']			= ['type' => 'boolean', "name" => __("Aproved", SHMAPPER)];
		$req['notify_date']			= ['type' => 'number', "name" => __("Aprove date", SHMAPPER)];	
		$req['notify_user']			= ['type' => 'id', "object" => "user", "name" => __("Accessed User", SHMAPPER)];	
		$init_object[SHM_REQUEST]	= $req;
	
		return $init_object;
		
	}
	
	static function add_shortcodes()
	{		
		require_once(SHM_REAL_PATH.'shortcode/shmMap.shortcode.php');
		add_shortcode('shmMap',		'shmMap'); 
	}
	
	static function add_admin_js_script()
	{	
		//css
		wp_register_style("ShMapper", SHM_URLPATH . 'assets/css/ShMapper.css', array());
		wp_enqueue_style( "ShMapper");
		//js
		wp_register_script("inline", get_bloginfo("url").'/wp-admin/js/inline-edit-post.js', array());
		wp_enqueue_script("inline");
		wp_register_script("ShMapper", plugins_url( '../assets/js/ShMapper.js', __FILE__ ), array());
		wp_enqueue_script("ShMapper");
		wp_register_script("ShMapper.admin", plugins_url( '../assets/js/ShMapper.admin.js', __FILE__ ), array());
		wp_enqueue_script("ShMapper.admin");
		if( static::$options['map_api'] == 1 )
		{
			wp_register_script("api-maps", "https://api-maps.yandex.ru/2.1/?load=package.full&lang=ru_RU", array());
			wp_enqueue_script("api-maps");	
		}
		else if(  static::$options['map_api'] == 2 )
		{
			//css
			wp_register_style("leaflet", "https://unpkg.com/leaflet@1.3.4/dist/leaflet.css", array());
			wp_enqueue_style( "leaflet");
			//js
			wp_register_script("leaflet", "https://unpkg.com/leaflet@1.3.4/dist/leaflet.js", array());
			wp_enqueue_script("leaflet");
		}
		wp_localize_script( "jquery", "map_type", static::$options['map_api'] );
		
		// load media library scripts
		wp_enqueue_media();
		//ajax
		wp_localize_script( 
			'jquery', 
			'myajax', 
			array(
				'url' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('myajax-nonce')
			)
		);	
		wp_localize_script( 
			'jquery', 
			'myajax2', 
			array(
				'url' => admin_url('admin-ajax.php')
			)
		);	
		
		wp_localize_script( 'jquery', 'shm_maps', [] );
		wp_localize_script( 
			'jquery', 
			'voc', 
			array(
				'Attantion' => __( "Attantion", SHMAPPER ),
				'Send' => __( "Send" ),
				'Close' => __( "Close" ),
			)
		);	
	}
	static function add_frons_js_script()
	{	
		
		//css
		wp_register_style("ShMapper", SHM_URLPATH . 'assets/css/ShMapper.css', array());
		wp_enqueue_style( "ShMapper");
		
		//js			
		wp_register_script("jquery-ui", "https://code.jquery.com/ui/1.12.1/jquery-ui.js", array());
		wp_enqueue_script("jquery-ui");	
		wp_register_script("ShMapper", plugins_url( '../assets/js/ShMapper.js', __FILE__ ), array());
		wp_enqueue_script("ShMapper");	
		wp_register_script("ShMapper.front", plugins_url( '../assets/js/ShMapper.front.js', __FILE__ ), array());
		wp_enqueue_script("ShMapper.front");	
		if( static::$options['map_api'] == 1 )
		{
			wp_register_script("api-maps", "https://api-maps.yandex.ru/2.1/?load=package.full&lang=ru_RU", array());
			wp_enqueue_script("api-maps");				
			//wp_register_script("ShMapper.yandex", plugins_url( '../assets/js/ShMapper.yandex.js', __FILE__ ), array());
			//wp_enqueue_script("ShMapper.yandex");
		}
		else if(  static::$options['map_api'] == 2 )
		{
			//css
			//wp_register_style("leaflet", "https://unpkg.com/leaflet@1.3.4/dist/leaflet.css", array());
			//wp_enqueue_style( "leaflet");
			//js
			//wp_register_script("leaflet", "https://unpkg.com/leaflet@1.3.4/dist/leaflet.js", array());
			//wp_enqueue_script("leaflet");	
			
		}
		wp_localize_script( "jquery", "map_type", static::$options['map_api'] );
		//ajax
		wp_localize_script( 
			'jquery', 
			'myajax', 
			array(
				'url' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('myajax-nonce')
			)
		);
		wp_localize_script( 
			'jquery', 
			'myajax2', 
			array(
				'url' => admin_url('admin-ajax.php')
			)
		);		
		wp_localize_script( 
			'jquery', 
			'shm_set_req', 
			array(
				'url' => admin_url('admin-ajax.php')
			)
		);	
		wp_localize_script( 'jquery', 'shm_maps', [] );
	}
	static function set_styles()
	{
		if(  static::$options['map_api'] == 2 )
		{
			echo "
			<link rel='stylesheet' href='https://unpkg.com/leaflet@1.3.4/dist/leaflet.css' integrity='sha512-puBpdR0798OZvTTbP4A8Ix/l+A4dHDD0DGqYW6RQ+9jxkRFclaxxQb/SJAWZfWAkuyeQUytO7+7N4QKrDh+drA==' crossorigin=''/>	
			<!-- Make sure you put this AFTER Leaflet's CSS -->
			<script src='https://unpkg.com/leaflet@1.3.4/dist/leaflet.js' integrity='sha512-nMMmRyTVoLYqjP9hrbed9S+FzjZHW5gY1TWCHA5ckwXZBadntCNs8kEqAWdrb9O7rxbCaA4lKTIWjDXZxflOcA==' crossorigin=''>
			</script>";
		}
	}
	static function admin_page_handler()
	{
		add_menu_page( 
			__('Shmapper', SHMAPPER), 
			__('Shmapper', SHMAPPER),
			'manage_options', 
			'shm_page', 
			[ __CLASS__, 'setting_pages' ], 
			"dashicons-admin-site", // icon url  
			'19.123456'
		);
		return;
		add_submenu_page(
			'shm_page',
			__("Settings"),
			__("Settings"),
			'manage_options',
			'shm_settings_page',
			[ __CLASS__, 'setting_pages' ]
		);
	}
	static function setting_pages()
	{
		//var_dump(static::$options);
		echo "<div class='shm-container shm-padding-20'>
			<div class='shm-row'>
				<h1 class='wp-heading-inline shm-color-grey'>".
					__("Settings") .
				"</h1>
			</div>
			<div class='spacer-30'></div>
			<ul class='shm-card'>
				<li>
					<div class='shm-row map_api_cont'>
						<div class='shm-2 shm-color-grey sh-right sh-align-middle shm-title-3'>".
							__("Map API", SHMAPPER) . 
						"</div>
						<div class='shm-10'>
							<input type='radio' class='radio' value='1' name='map_api' id='radio_Yandex'" . 
								checked(1, (int)static::$options['map_api'], 0) . 
							"/>
							<label for='radio_Yandex'>".__("Yandex Map", SHMAPPER) ."</label>
						
							<input type='radio' class='radio' value='2' name='map_api' id='radio_OSM'" . 
								checked(2, (int)static::$options['map_api'], 0) . 
							"/>
							<label for='radio_OSM'>".__("Open Street Map", SHMAPPER) ."</label>
							<div class='spacer-10'></div>
							<div><small class='shm-color-grey'>Short description label</small></div>
						</div>
					</div>
				</li>
				<li>
					<div class='shm-row' id='shm_map_is_crowdsourced_cont'>
						<div class='shm-2 shm-color-grey sh-right sh-align-middle shm-title-3'>".
							__("Interactive", SHMAPPER) .
						"</div>
						<div class='shm-9'>
							<p>
								<input type='checkbox' class='checkbox' value='1' id='shm_map_is_crowdsourced' " . 
									checked(1, (int)static::$options['shm_map_is_crowdsourced'], 0) . 
								"/>
								<label for='shm_map_is_crowdsourced'>".
									__("Включить глобальный режим неинтерактивных карт", SHMAPPER) .
								"</label> 
								<br>
								<span class='shm-color-grey'><small>".
									__("пользователи не смогут добавлять сообщения ни к одной карте. Если галочка включена у карт даже не появляется блок интерактивности.", SHMAPPER). 
								"</small></span>
							</p>
							<p>
								<input type='checkbox' class='checkbox' value='1' id='shm_map_marker_premoderation' " . 
									checked(1, (int)static::$options['shm_map_marker_premoderation'], 0) . 
								"/>
								<label for='shm_map_marker_premoderation'>".
									__("Pre-modertion from Map owner.", SHMAPPER) .
								"</label> 
								<br>
								<span class='shm-color-grey'><small>".
									__("все сообщения будут добавляться в статусе «Черновик»", SHMAPPER). 
								"</small></span>
								<br>
								<span class='shm-color-alert'><small>". 
										__("ВНИМАНИЕ: отключайте эту опцию только на ваш страх и риск, т.к. существует угроза спам-атаки", SHMAPPER). 
								"</small></span>
							</p>
						</div>	
						<div class='shm-1'>
							
						</div>	
					</div>				
				</li>
				<li>
					<div class='shm-row' id='shm_settings_captcha_cont'>
						<div class='shm-2 shm-color-grey sh-right sh-align-middle shm-title-3'>".
							__("Protection", SHMAPPER) .
						"</div>
						<div class='shm-9'>
							<input type='checkbox' class='checkbox' value='1' id='shm_settings_captcha' " . 
								checked(1, (int)static::$options['shm_settings_captcha'], 0) . 
							"/>
							<label for='shm_settings_captcha'>".
								__("Includes captcha in the form", SHMAPPER) .
							"</label> 
							<p>
							<div><small class='shm-color-grey'>Google reCAPTCHA site key</small></div>
							<input class='sh-form' name='shm_captcha_siteKey' value='". static::$options['shm_captcha_siteKey'] .  "' />
							<p>
							<div><small class='shm-color-grey'>Google reCAPTCHA secret key</small></div>
							<input class='sh-form' name='shm_captcha_secretKey' value='". static::$options['shm_captcha_secretKey'] .  "' />
							<small class='shm-color-grey'>".
								sprintf(__("What is Google reCAPTCHA? How recived keys for your site? See %sthis instruction%s.", SHMAPPER), "<a href='https://webdesign.tutsplus.com/" . substr(get_bloginfo("language"), 0, 2) . "/tutorials/how-to-integrate-no-captcha-recaptcha-in-your-website--cms-23024'>", "</a>") .
							"</small>
							<div class='" . (!static::$options['shm_captcha_siteKey'] || !static::$options['shm_captcha_secretKey'] ? "" : "hidden") . "'>
								<small class='shm-color-danger'>".
									__("Your reCAPTCH is no work now! Take valid keys from Goggle. Please", SHMAPPER).
								"</small>
							</div>
						</div>	
						<div class='shm-1'>
							
						</div>	
					</div>				
				</li>	
				<li>
					<div class='shm-row' id='shm_vocabulary_cont'>
						<div class='shm-2 shm-color-grey sh-right sh-align-middle shm-title-3 '>".
							__("Vocabulary", SHMAPPER) .
						"</div>
						<div class='shm-9' id='shm_voc'>
							<div><small class='shm-color-grey '>".
								__("Save personal data garantee", SHMAPPER) .
							"</small></div>
							<input class='sh-form admin_voc' name='shm_personal_text' value='".static::$options['shm_personal_text']."'/>
							
							<p>							
							<div><small class='shm-color-grey'>".
								__("Successful send map request", SHMAPPER) .
							"</small></div>							
							<input class='sh-form admin_voc' name='shm_succ_request_text' value='".static::$options['shm_succ_request_text']. "'/>
							
							<p>							
							<div><small class='shm-color-grey'>".
								__("Error send map request", SHMAPPER) .
							"</small></div>							
							<input class='sh-form admin_voc' name='shm_error_request_text' value='".static::$options['shm_error_request_text']. "'/>
						</div>	
						<div class='shm-1'>
							
						</div>	
					</div>				
				</li>	
				<li>
					<div class='shm-row' id='shm_vocabulary_cont'>
						<div class='shm-2 shm-color-grey sh-right sh-align-middle shm-title-3 '>".
							__("Wizzard", SHMAPPER) .
						"</div>
						<div class='shm-9' id='shm_voc'>
							<div class='button' id='shm_settings_wizzard' >" . __("Restart wizzard", SHMAPPER) . "</div>
						</div>	
						<div class='shm-1'>
							
						</div>	
					</div>				
				</li>	
			</ul>
		</div>";
	}
	static function add_wizzard()
	{
		if(!static::$options['wizzard']) return;
		//update_option("shm_wizard_step", 0);
		$step	= (int)get_option("shm_wizard_step");
		$stepData = static::get_wizzard_lst()[$step];
		$i =0;
		foreach(static::get_wizzard_lst() as $st)
		{
			$i++;
			$active = $i == $step+1 ? "active" : "";
			$steps_line .= "
			<div class='$active'><div>$i</div></div>";
		}
		
		$title  = $stepData['title'];
		$text  	= $stepData['text'];
		$html 	= "
		<div class='shm_wizzard' id='shm_wizzard'>
			<div class='shm_wizzard_close' onclick='shm_close_wizz()'>
				<span class='dashicons dashicons-visibility'></span>
			</div>
			<div class='shm_wizzard_line'>
				$steps_line
			</div>
			<div class='shm_wizzard_title'>
				$title
			</div>
			<div class='shm_wizzard_body'>
				$text
			</div>
			<div class='shm_wizzard_footer'>
				<a class='dashicons dashicons-controls-back' title='" . __("Prevous step", SHMAPPER) . "'></a>
				<a class='dashicons dashicons-edit' title='" . __("Go to current page", SHMAPPER) . "'></a>
				<a class='dashicons dashicons-controls-play' title='" . __("Next step", SHMAPPER) . "' name='shm_wnext'></a>
				<a class='dashicons dashicons-no' title='" . __("Close wizzard", SHMAPPER) . "' name='shm_wclose'></a>
			</div>
		</div>
		<div class='shm_wizzard_closed' id='shm_wizzard_closed' onclick='shm_show_wizz()'>
		
		</div>
		<script>
			function shm_close_wizz()
			{
				jQuery('#shm_wizzard').hide();
				jQuery('#shm_wizzard_closed').fadeIn('slow');
			}
			function shm_show_wizz()
			{
				jQuery('#shm_wizzard_closed').hide();
				jQuery('#shm_wizzard').fadeIn('slow');
			}
			jQuery(document).ready(function($)
			{	
				console.log('" . $stepData["selector"] . "');
				jQuery('" . $stepData["selector"] . "').addClass('shm_wizzard_current');
				var loc = jQuery('" . $stepData["selector"] . "').offset();
				if( loc.top < 0 )
				{
					loc = jQuery('" . $stepData["parent_selector"] . "').offset();
				}
				jQuery('#shm_wizzard').appendTo('#adminmenu').hide().fadeIn('slow').css({top: loc.top - 15});
				jQuery('#shm_wizzard_closed').appendTo('#adminmenu').hide().css({top: loc.top - 28});
			});
		</script>";
		echo $html;
	}
	static function get_wizzard_lst()
	{
		return [
			[
				"title"				=> "Приветствуем Вас в Мастере конфигурации Shmapper",
				"text"				=> "Сначала необходимо указать общие настройки. Нажмите на кнопку <span class='dashicons dashicons-controls-play'></span> чтобы перейти в нужный раздел",
				"selector"			=> 'a[href="admin.php?page=shm_page"]',
				"parent_selector"	=> '#toplevel_page_shm_page',
				"href"				=> "/wp-admin/admin.php?page=shm_page"
			],
			[
				"title"				=> "Настройте Shmapper",
				"text"				=> "Измените настройки, которые Вас не устраивают. Для подключения reCAPTCHA необходимо создать учётную запись на Google.com",
				"selector"			=> 'a[href="admin.php?page=shm_page"]',
				"parent_selector"	=> '.toplevel_page_shm_page',
				"href"				=> '/wp-admin/admin.php?page=shm_page',
			],
			[
				"title"				=> "Создайте вашу первую карту",
				"text"				=> "Нажмите кнопку  \"Добавить карту\" в самом верху страницы",
				"selector"			=> 'a[href=\"edit.php?post_type=shm_map\"]',
				"parent_selector"	=> '.toplevel_page_shm_page',
				"href"				=> '/wp-admin/edit.php?post_type=shm_map',
			],
			[
				"title"				=> "Новая карта",
				"text"				=> "На карте выберите видимую область. <p> Создайте первый Маркер указав на нужное место карты правой кнопкой мыши. Заполните поля и нажмите \"Создать\". ",
				"selector"			=> '#adminmenuwrap a[href=\"edit.php?post_type=shm_map\"]',
				"parent_selector"	=> '#adminmenuwrap .toplevel_page_shm_page',
				"href"				=> '/wp-admin/post-new.php?post_type=shm_map',
			],
			[
				"title"				=> "Новая карта",
				"text"				=> "Последовательно заполните предлаженные поля. В разделе \"Форма запроса\" создайте простейшую форму обратной связи, по которой Посетители смогут информировать Вас о предлагаемых Вам новых Маркерах. По окончании нажмите кнопку \"Опубликовать\"",
				"selector"			=> '#adminmenuwrap a[href=\"edit.php?post_type=shm_map\"]',
				"parent_selector"	=> '#adminmenuwrap .toplevel_page_shm_page',
				"href"				=> '/wp-admin/post-new.php?post_type=shm_map',
			],
		];
	}
}