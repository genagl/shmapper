<?php

class ShMapper 
{
	static function activate()
	{
		global $wpdb;
		init_textdomain_shmapper();
		$wpdb->query("CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."point_map` (
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
			"shm_map_marker_premoderation"	=> 1,
			"shm_reload"	=> 1,
			"wizzard" => 1,
			"shm_personal_text" => __("Я даю свое согласие администратору сайта на обработку, в том числе автоматизированную, своих персональных данных в соответствии с Федеральным законом от 27.07.2006 N 152-ФЗ «О персональных данных».", SHMAPPER),
			"shm_succ_request_text" => __("Your request has been successfully registered.", SHMAPPER),
			"shm_error_request_text" => __("Unknown error.", SHMAPPER),
		]);
		$upload = wp_upload_dir();
		$upload_dir = $upload['basedir'];
		$upload_dir = $upload_dir . '/shmapper';
		wp_mkdir_p( $upload_dir );
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
		add_action( 'admin_menu',					[__CLASS__, 'admin_page_handler2'], 99);
		add_action( 'admin_enqueue_scripts', 		[__CLASS__, 'add_admin_js_script'], 99 );
		add_action( 'wp_enqueue_scripts', 			[__CLASS__, 'add_frons_js_script'], 99 );
		add_action( "admin_footer", 				[__CLASS__, "add_wizzard"]);
		add_action( 'wp_before_admin_bar_render', 	[__CLASS__, 'my_admin_bar_render'], 11);
	}
	
	

	static function my_admin_bar_render()
	{
		global $wp_admin_bar, $shm_all_maps;
		if(!current_user_can("manage_options")) return;
		
		$wp_admin_bar->add_menu( array(
			'parent' => false,
			'id' => 'shmapper_panel', 
			'title' => __('Shmapper', SHMAPPER), 
			'href' => "/wp-admin/admin.php?page=shm_settings_page" 	
		));
		$wp_admin_bar->add_menu( array(
			'parent' => 'shmapper_panel',
			'id' => 'shmapper_add_map', 
			'title' => __('add Map', SHMAPPER), 
			'href' => "/wp-admin/post-new.php?post_type=shm_map" 	
		));
		$wp_admin_bar->add_menu( array(
			'parent' => 'shmapper_panel',
			'id' => 'shmapper_maps', 
			'title' => __('Maps', SHMAPPER), 
			'href' => "/wp-admin/edit.php?post_type=shm_map" 	
		));
		$wp_admin_bar->add_menu( array(
			'parent' => 'shmapper_panel',
			'id' => 'shmapper_edit_maps', 
			'title' => __('edit Maps in page', SHMAPPER), 
			'href' => "#" 	
		));
		if(is_array($shm_all_maps))
		{
			foreach($shm_all_maps as $mid)
			{
				$map = ShmMap::get_instance($mid);
				$wp_admin_bar->add_menu( [
					'parent' => 'shmapper_edit_maps',
					'id' => 'shmapper_edit_map'.$mid, 
					'title' => $map->get("post_title"), 
					'href' => "/wp-admin/post.php?post=$mid&action=edit" 
				] );
			}
		}
		$wp_admin_bar->add_menu( array(
			'parent' => 'shmapper_panel',
			'id' => 'shmapper_map_req', 
			'title' => __("all Map Requests", SHMAPPER), 
			'href' => "/wp-admin/edit.php?post_type=shm_request" 	
		));
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
		$map['width']				= ['type'=>'number', "distination" => "map", "name" => __("Width")];	
		$map['height']				= ['type'=>'number', "distination" => "map", "name" => __("Height")];	
		$map['is_search']			= ['type'=>'boolean', "distination" => "map", "name" => __("Map search", SHMAPPER)];	
		$map['is_fullscreen']		= ['type'=>'boolean', "distination" => "map", "name" => __("Map full screen", SHMAPPER)];	
		$map['is_zoomer']			= ['type'=>'boolean', "distination" => "map", "name" => __("Map zoom slider", SHMAPPER)];	
		$map['is_layer_switcher']	= ['type'=>'boolean',"distination"=>"map","name"=>__("Map layer switcher",SHMAPPER)];	
		$map['is_lock']				= ['type'=>'boolean',"distination"=>"map","name"=>__("Lock zoom and drag",SHMAPPER)];	
		$map['is_clustered']		= ['type'=>'boolean',"distination"=>"map","name"=>__("Formating Marker to cluster", SHMAPPER)];	
		$map['default_icon_id']		= ['type'=>'boolean',"distination"=>"map","name"=>__("Default Marker icon", SHMAPPER)];	
		
		$map['is_form']				= ['type'=>'boolean', "distination" => "form", "name" => __("Form exists", SHMAPPER)];
		$map['notify_owner']		= ['type'=>'boolean', "distination" => "form", "name" => __("Notify owner of Map", SHMAPPER)];
		$map['form_title']			= ['type'=>'string',  "distination" => "form", "name" => __("Form Title", SHMAPPER)];
		$map['form_forms']			= ['type'=>'form_editor',  "distination" => "form", "name" => __("Form generator", SHMAPPER)];
		$map['is_personal_data']	= ['type'=>'boolean',  "distination" => "form", "name" => __("Users can leave their contact details for feedback.", SHMAPPER)];		
		$map['is_name_iclude']		= ['type'=>'boolean',  "distination" => "form", "name" => __("Unclude Personal name", SHMAPPER)];		
		$map['personal_name']		= ['type'=>'string',  "distination" => "form", "name" => __("Personal name", SHMAPPER)];		
		$map['is_name_required']	= ['type'=>'boolean',  "distination" => "form", "name" => __("Required Personal name", SHMAPPER)];		
		$map['is_email_iclude']		= ['type'=>'boolean',  "distination" => "form", "name" => __("Unclude Personal e-mail",SHMAPPER)];		
		$map['personal_email']		= ['type'=>'string',  "distination" => "form", "name" => __("Personal e-mail", SHMAPPER)];		
		$map['is_email_required']	= ['type'=>'boolean',  "distination" => "form", "name" => __("Required Personal e-mail", SHMAPPER)];		
		$map['is_phone_iclude']		= ['type'=>'boolean',  "distination" => "form", "name" => __("Unclude Personal phone", SHMAPPER)];		
		$map['personal_phone']		= ['type'=>'string',  "distination" => "form", "name" => __("Personal phone", SHMAPPER)];		
		$map['is_phone_required']	= ['type'=>'boolean',  "distination" => "form", "name" => __("Required Personal phone", SHMAPPER)];	
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
		wp_register_script("jquery", 'https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js' , array());
		wp_enqueue_script("jquery");
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
			wp_register_script("ShMapper.yandex", plugins_url( '../assets/js/ShMapper.yandex.js', __FILE__ ), array());
			wp_enqueue_script("ShMapper.yandex");
		}
		else if(  static::$options['map_api'] == 2 )
		{
			//css
			wp_register_style("easyGeocoder", SHM_URLPATH . 'assets/css/easyGeocoder.css', array());
			wp_enqueue_style( "easyGeocoder");
			wp_register_style("leaflet", "https://unpkg.com/leaflet@1.3.4/dist/leaflet.css", array());
			wp_enqueue_style( "leaflet");
			wp_register_style("layerSwitcher", SHM_URLPATH . 'assets/css/layerSwitcher.css', array());
			wp_enqueue_style( "layerSwitcher");
			wp_register_style("MarkerCluster", SHM_URLPATH . 'assets/css/MarkerCluster.css', array());
			wp_enqueue_style( "MarkerCluster");
			wp_register_style("MarkerClusterD", SHM_URLPATH . 'assets/css/MarkerCluster.Default.css', array());
			wp_enqueue_style( "MarkerClusterD");
			wp_register_style("esri-leaflet-geocoder", "https://unpkg.com/esri-leaflet-geocoder@2.2.13/dist/esri-leaflet-geocoder.css", array());
			wp_enqueue_style( "esri-leaflet-geocoder");
			//js
			wp_register_script("leaflet", "https://unpkg.com/leaflet@1.3.4/dist/leaflet.js", array());
			wp_enqueue_script("leaflet");
			wp_register_script("esri-leaflet", "https://unpkg.com/esri-leaflet@2.2.3/dist/esri-leaflet.js", array());
			wp_enqueue_script("esri-leaflet");
			wp_register_script("esri-leaflet-geocoder", "https://unpkg.com/esri-leaflet-geocoder@2.2.13/dist/esri-leaflet-geocoder.js", array());
			wp_enqueue_script("esri-leaflet-geocoder");	
			wp_register_script("leaflet.markercluster", plugins_url( '../assets/js/leaflet.markercluster-src.js', __FILE__ ), array());
			wp_enqueue_script("leaflet.markercluster");	
			wp_register_script("layerSwitcher", plugins_url( '../assets/js/Leaflet.layerSwitcher.js', __FILE__ ), array());
			wp_enqueue_script("layerSwitcher");	
			
			wp_register_script("easyGeocoder", plugins_url( '../assets/js/easyGeocoder.js', __FILE__ ), array());
			wp_enqueue_script("easyGeocoder");	
			
			wp_register_script("Leaflet.fs", plugins_url( '../assets/js/Leaflet.fullscreen.min.js', __FILE__ ), array());
			wp_enqueue_script("Leaflet.fs");
			wp_register_script("ShMapper.osm", plugins_url( '../assets/js/ShMapper_osm.js', __FILE__ ), array());
			wp_enqueue_script("ShMapper.osm");	
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
				'Error: no map' => __( "Error: the form is not associated with the card. To link a map and a form, there should be 2 shortcodes on one page (map - [shmMap id = '6' map = 'true' uniq = 'for example, 777'] and form - [shmMap id = '94' form = 'true' uniq = 'for example, 777']), in which the uniq parameter will match", SHMAPPER ),
				'Are you shure?' => __( "Are you shure?", SHMAPPER ),
			)
		);	
	}
	static function add_frons_js_script()
	{	
		
		//css
		wp_register_style("ShMapper", SHM_URLPATH . 'assets/css/ShMapper.css', array());
		wp_enqueue_style( "ShMapper");
		
		//js			
		wp_register_script("jquery", 'https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js' , array());
		wp_enqueue_script("jquery");
		//wp_register_script("jquery-ui", "https://code.jquery.com/ui/1.12.1/jquery-ui.js", array());
		wp_register_script("jquery-ui", plugins_url( '../assets/js/jquery-ui.min.js', __FILE__ ), array());
		wp_enqueue_script("jquery-ui");	
		wp_register_script("touchpunch", plugins_url( '../assets/js/touchpunch.js', __FILE__ ), array());
		wp_enqueue_script("touchpunch");	
		wp_register_script("ShMapper", plugins_url( '../assets/js/ShMapper.js', __FILE__ ), array());
		wp_enqueue_script("ShMapper");	
		wp_register_style("layerSwitcher", SHM_URLPATH . 'assets/css/layerSwitcher.css', array());
		wp_enqueue_style( "layerSwitcher");
		wp_register_script("ShMapper.front", plugins_url( '../assets/js/ShMapper.front.js', __FILE__ ), array());
		wp_enqueue_script("ShMapper.front");	
		if( static::$options['map_api'] == 1 )
		{
			wp_register_script("api-maps", "https://api-maps.yandex.ru/2.1/?load=package.full&lang=ru_RU", array());
			wp_enqueue_script("api-maps");			
			wp_register_script("ShMapper.yandex", plugins_url( '../assets/js/ShMapper.yandex.js', __FILE__ ), array());
			wp_enqueue_script("ShMapper.yandex");
		}
		else if(  static::$options['map_api'] == 2 )
		{
			//css
			wp_register_style("easyGeocoder", SHM_URLPATH . 'assets/css/easyGeocoder.css', array());
			wp_enqueue_style( "easyGeocoder");
			wp_register_style("MarkerCluster", SHM_URLPATH . 'assets/css/MarkerCluster.css', array());
			wp_enqueue_style( "MarkerCluster");
			wp_register_style("MarkerClusterD", SHM_URLPATH . 'assets/css/MarkerCluster.Default.css', array());
			wp_enqueue_style( "MarkerClusterD");
			wp_register_style("leaflet", "https://unpkg.com/leaflet@1.3.4/dist/leaflet.css", array());
			wp_enqueue_style( "leaflet");
			wp_register_style("esri-leaflet-geocoder", "https://unpkg.com/esri-leaflet-geocoder@2.2.13/dist/esri-leaflet-geocoder.css", array());
			wp_enqueue_style( "esri-leaflet-geocoder");
			//js
			wp_register_script("leaflet", "https://unpkg.com/leaflet@1.3.4/dist/leaflet.js", array());
			wp_enqueue_script("leaflet");	
			wp_register_script("esri-leaflet", "https://unpkg.com/esri-leaflet@2.2.3/dist/esri-leaflet.js", array());
			wp_enqueue_script("esri-leaflet");
			wp_register_script("esri-leaflet-geocoder", "https://unpkg.com/esri-leaflet-geocoder@2.2.13/dist/esri-leaflet-geocoder.js", array());
			wp_enqueue_script("esri-leaflet-geocoder");	
			wp_register_script("easyGeocoder", plugins_url( '../assets/js/easyGeocoder.js', __FILE__ ), array());
			wp_enqueue_script("easyGeocoder");	
			wp_register_script("leaflet.markercluster", plugins_url( '../assets/js/leaflet.markercluster-src.js', __FILE__ ), array());
			wp_enqueue_script("leaflet.markercluster");	
			wp_register_script("layerSwitcher", plugins_url( '../assets/js/Leaflet.layerSwitcher.js', __FILE__ ), array());
			wp_enqueue_script("layerSwitcher");	
			wp_register_script("Leaflet.fs", plugins_url( '../assets/js/Leaflet.fullscreen.min.js', __FILE__ ), array());
			wp_enqueue_script("Leaflet.fs");		
			wp_register_script("ShMapper.osm", plugins_url( '../assets/js/ShMapper_osm.js', __FILE__ ), array());
			wp_enqueue_script("ShMapper.osm");			
			
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
		wp_localize_script( 
			'jquery', 
			'voc', 
			array(
				'Attantion' => __( "Attantion", SHMAPPER ),
				'Send' => __( "Send" ),
				'Close' => __( "Close" ),
				'Error: no map' => __( "Error: the form is not associated with the card. To link a map and a form, there should be 2 shortcodes on one page (map - [shmMap id = '6' map = 'true' uniq = 'for example, 777'] and form - [shmMap id = '94' form = 'true' uniq = 'for example, 777']), in which the uniq parameter will match", SHMAPPER ),
			)
		);	
	}
	static function set_styles()
	{
		echo "<style>
			.dashicons, 
			.dashicons-before:before 
			{
				font-family: dashicons!important;
			}
		</style>";
	}
	static function admin_page_handler()
	{
		/**/
		add_menu_page( 
			__('Shmapper', SHMAPPER), 
			__('Shmapper', SHMAPPER),
			'manage_options', 
			'shm_page', 
			[ __CLASS__, 'setting_pages' ], 
			SHM_URLPATH . "assets/img/shmapper_32x32_white.svg",//"dashicons-admin-site", // icon url  
			'19.123456'
		);
	}
	static function admin_page_handler2()
	{
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
				<div class='shm-12'>
					<div class='shm_logo'></div>
					<h1 class='wp-heading-inline shm-color-grey shm_no_margin'>".
						__("Settings") .
					"</h1>
				</div>
			</div>
			<div class='spacer-30'></div>
			<ul class='shm-card'>
				<li>
					<div class='shm-row map_api_cont'>
						<div class='shm-2 shm-color-grey sh-right sh-align-middle shm-title-3'>".
							__("Map API", SHMAPPER) . 
						"</div>
						<div class='shm-10'>
							<div class='shm-admin-block'>
								<input type='radio' class='radio' value='1' name='map_api' id='radio_Yandex'" . 
									checked(1, (int)static::$options['map_api'], 0) . 
								"/>
								<label for='radio_Yandex'>".__("Yandex Map", SHMAPPER) ."</label>
							</div>
							<div class='shm-admin-block'>
								<input type='radio' class='radio' value='2' name='map_api' id='radio_OSM'" . 
									checked(2, (int)static::$options['map_api'], 0) . 
								"/>
								<label for='radio_OSM'>".__("Open Street Map", SHMAPPER) ."</label>
							</div>
							<div class='spacer-10'></div>
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
							<p>
								<input type='checkbox' class='checkbox' value='1' id='shm_reload' " . 
									checked(1, (int)static::$options['shm_reload'], 0) . 
								"/>
								<label for='shm_reload'>".
									__("Reload page after User send request.", SHMAPPER) .
								"</label> 
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
								__("Include captcha in form (plugin uses only reCAPTCHA v2 keys)", SHMAPPER) .
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
								<small class='shm-color-danger' id='recaptcha_danger'>".
									__("Your reCAPTCHA doesn't work yet. In order to make it work, please get the API keys at google.com/recaptcha", SHMAPPER).
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
				<a name='shm_wclose'>" . __("Close wizzard", SHMAPPER) . "</a>
				<a name='shm_wcurrent'>" . __("Go to current page", SHMAPPER) . "</a>".
				(
					$stepData["alt_selector"] ? "" : 
					"<a class='dashicons dashicons-controls-play' title='" . __("Next step", SHMAPPER) . "' name='shm_wnext'></a>"
				).
				"<!--a class='dashicons dashicons-controls-back' title='" . __("Prevous step", SHMAPPER) . "'></a>
				<a class='dashicons dashicons-edit' title='" . __("Go to current page", SHMAPPER) . "'></a>
				<a class='dashicons dashicons-no' title='" . __("Close wizzard", SHMAPPER) . "' name='shm_wclose'></a-->
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
				jQuery('" . $stepData["selector"] . "').addClass('shm_wizzard_current');
				var loc = jQuery('" . $stepData["selector"] . "').offset();
				if( loc.top < 0 )
				{
					loc = jQuery('" . $stepData["parent_selector"] . "').offset();
				}
				jQuery('#shm_wizzard').appendTo('#adminmenu').hide().fadeIn('slow').css({top: loc.top - 15});
				jQuery('#shm_wizzard_closed').appendTo('#adminmenu').hide().css({top: loc.top - 28});
				jQuery('" . $stepData["alt_selector"] . "').each((num, elem) => {
					var ofset = $(elem).offset();
					var poss = ofset.left < window.innerWidth/2 ? 1 : 2;
					var arr	= poss == 1 ? '<div class=\"shm_warrow\" id=\"shm_warrow'+ num +'\"></div>' : '<div class=\"shm_warrow2\" id=\"shm_warrow'+ num +'\"></div>';
					var lpos =  poss == 1 ? ofset.left +  $(elem).width() + 20 : ofset.left - 50;
					$(arr)
						.appendTo('body')
							.offset({top:ofset.top - 8, left:lpos})
					$(elem).live({click:evt =>
					{
						shm_send(['shm_wnext']);	
					}});
				});
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
				"selector"			=> ' a[href="admin.php?page=shm_page"].toplevel_page_shm_page',
				"parent_selector"	=> '#toplevel_page_shm_page',
				"href"				=> "/wp-admin/admin.php?page=shm_page"
			],
			[
				"title"				=> "Настройте Shmapper",
				"text"				=> "Измените настройки, которые Вас не устраивают. Для подключения reCAPTCHA необходимо создать учётную запись на Google.com",
				"selector"			=> ' a[href="admin.php?page=shm_page"].toplevel_page_shm_page',
				"parent_selector"	=> '#toplevel_page_shm_page',
				"href"				=> '/wp-admin/admin.php?page=shm_page',
			],
			[
				"title"				=> "Создайте вашу первую карту",
				"text"				=> "Нажмите кнопку  \"Добавить карту\" в самом верху страницы",
				"selector"			=> '#adminmenuwrap a[href=\"edit.php?post_type=shm_map\"]',
				"alt_selector"		=> "body.post-type-shm_map .page-title-action" ,
				"href"				=> '/wp-admin/edit.php?post_type=shm_map',
			],
			[
				"title"				=> "Новая карта",
				"text"				=> "На карте выберите видимую область. <p> Создайте первый Маркер указав на нужное место карты правой кнопкой мыши. <p> Заполните поля и нажмите \"Создать\". ",
				"selector"			=> '#adminmenuwrap a[href=\"edit.php?post_type=shm_map\"]',
				"alt_selector"		=> 'body.post-type-shm_map #publish',
				"parent_selector"	=> '#adminmenuwrap .toplevel_page_shm_page',
				"href"				=> '/wp-admin/post-new.php?post_type=shm_map',
			],
			[
				"title"				=> "Новая карта",
				"text"				=> "Последовательно заполните предлаженные поля. В разделе \"Форма запроса\" создайте простейшую форму обратной связи, по которой Посетители смогут информировать Вас о предлагаемых Вам новых Маркерах. По окончании нажмите кнопку \"Опубликовать\"",
				"selector"			=> '#adminmenuwrap a[href=\"edit.php?post_type=shm_map\"]',
				"parent_selector"	=> '#adminmenuwrap .toplevel_page_shm_page',
				"href"				=> '',
			],
		];
	}
}