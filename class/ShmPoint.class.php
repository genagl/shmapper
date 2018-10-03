<?php
class ShmPoint extends SMC_Post
{
	static function init()
	{
		$typee = static::get_type();
		add_action('init',									array(__CLASS__, 'add_class'), 14 );
		add_action('admin_menu',							array(__CLASS__, 'owner_fields'), 20);
		//bulk-actions
		add_filter("bulk_actions-edit-{$typee}", 			array(__CLASS__, "register_my_bulk_actions"));
		add_filter("handle_bulk_actions-edit-{$typee}",  	array(__CLASS__, 'my_bulk_action_handler'), 10, 3 );
		add_action('admin_notices', 						array(__CLASS__, 'my_bulk_action_admin_notice' ));
		add_action("bulk_edit_custom_box", 					array(__CLASS__, 'my_bulk_edit_custom_box'), 2, 2 );
		add_action("wp_ajax_save_bulk_edit", 				array(__CLASS__, 'save_bulk_edit_book') );
		
		add_filter("the_content",							array(__CLASS__, "the_content"));
		parent::init();
	}
	static function get_type()
	{
		return SHM_POINT;
	}
	
	static function add_class()
	{
		$labels = array(
			'name' => __('Map marker', SHMAPPER),
			'singular_name' => __("Map marker", SHMAPPER),
			'add_new' => __("add Map marker", SHMAPPER),
			'add_new_item' => __("add Map marker", SHMAPPER),
			'edit_item' => __("edit Map marker", SHMAPPER),
			'new_item' => __("add Map marker", SHMAPPER),
			'all_items' => __("all Map markers", SHMAPPER),
			'view_item' => __("view Map marker", SHMAPPER),
			'search_items' => __("search Map marker", SHMAPPER),
			'not_found' =>  __("Map marker not found", SHMAPPER),
			'not_found_in_trash' => __("no found Map marker in trash", SHMAPPER),
			'menu_name' => __("Map markers", SHMAPPER)
		);
		$args = array(
			 'labels' => $labels
			,'public' => true
			,'show_ui' => true
			,'has_archive' => true 
			,'exclude_from_search' => false
			,'menu_position' => 18
			,'menu_icon' => "dashicons-location"
			,'show_in_menu' => "shm_page"
			,'show_in_rest' => true
			,'supports' => array(  'title', "editor", "thumbnail")
			,'capability_type' => 'post'
		);
		register_post_type(SHM_POINT, $args);
	}
	
	static function view_admin_edit($obj)
	{
		require_once(SHM_REAL_PATH."class/SMC_Object_type.php");
		$SMC_Object_type	= SMC_Object_Type::get_instance();
		$bb					= $SMC_Object_type->object [static::get_type()];
		foreach($bb as $key=>$value)
		{
			if($key == 't' || $key == 'class' ) continue;
			$meta = get_post_meta( $obj->id, $key, true);
			switch($key)
			{
				case "latitude":
					$meta 		= $meta ? $meta : 55.8;
					$opacity 	= " style='display:none;' " ;
					break;
				case "longitude":
					$meta 		= $meta ? $meta : 37.8;
					$opacity 	= " style='display:none;' " ;
					break;
				case "zoom":
					$meta 		= $meta ? $meta : 4;
					$opacity 	= " style='display:none;' " ;
					break;
				default:
					$opacity 	= " ";
			}
			$$key = $meta;
			switch( $value['type'] )
			{
				case "number":
					$h = "<input type='number' name='$key' id='$key' value='$meta' class='sh-form'/>";
					break;
				case "boolean":
					$h = "<input type='checkbox' class='checkbox' name='$key' id='$key' value='1' " . checked(1, $meta, 0) . "/><label for='$key'></label>";
					break;
				default:
					$h = "<input type='' name='$key' id='$key' value='$meta' class='sh-form'/>";
			}
			
			$html .="<div class='shm-row' $opacity>
				<div class='shm-3 sh-right sh-align-middle'>".$value['name'] . "</div>
				<div class='shm-9'>
					$h
				</div>
			</div>
			<div class='spacer-5'></div>";
		}
		//type switcher
		$tp  = wp_get_object_terms($obj->id, SHM_POINT_TYPE);
		$term = $tp[0];
		$term_id = $term ? $term->term_id : -1;
		$html .="<div class='shm-row'>
			<div class='shm-3 sh-right sh-align-middle'>".__("Map marker type", SHMAPPER). "</div>
			<div class='shm-9'>".
				$h = ShMapPointType::get_ganre_swicher([
					'selected' 	=> $term_id,
					'prefix'	=> "point_type",
					'col_width'	=> 3
				], 'radio' ).
			"</div>
		</div>
		<div class='spacer-5'></div>";
		$html 	.= "
			<div class='spacer-15'></div>
			<div class='shm-row'>
				<div class='shm-3 sh-right'>". __("Map", SHMAPPER). "</div>
				<div class='shm-9'>
					<div id='YMapID' style='width:100%;height:300px;border:3px solid darkgrey;'>
			
					</div>
				</div>
			</div>
			
			";
		$html 	.= "</div>			
		<section>
		<script type='text/javascript'>
		
		
			// console.log('coords:' , $latitude, $longitude);
			ymaps.ready(function () 
			{
				var myMap = new ymaps.Map('YMapID', 
				{
					center: [ $latitude, $longitude ],
					controls: [ ],
					zoom: $zoom
				});
			// center map
			myMap.events.add( 'boundschange', function(event)
			{
				 zoom = myMap.getZoom();
				 $('[name=zoom]').val(zoom);
			});
			// Создание геообъекта с типом точка (метка).
			var myPlacemark = new ymaps.GeoObject({
				geometry: 
				{
					type: 'Point', // тип геометрии - точка
					coordinates: [$latitude, $longitude] // координаты точки
				}
			});
			myMap.geoObjects.add(myPlacemark); 
			
			function MyBehavior() 
			{
				this.options = new ymaps.option.Manager(); // Менеджер опций
				this.events = new ymaps.event.Manager(); // Менеджер событий
			}
			MyBehavior.prototype = 
			{
				constructor: MyBehavior,
				enable: function () 
				{
					this._parent.getMap().events.add('click', this._onClick, this);
				},
				disable: function () 
				{
					this._parent.getMap().events.remove('click', this._onClick, this);
				},
				setParent: function (parent) 
				{
					this._parent = parent; 
				},
				getParent: function () 
				{ 
					return this._parent; 
				},
				_onClick: function (e) 
				{
					var coords = e.get('coords');
					//this._parent.getMap().setCenter(coords);
					myMap.geoObjects.remove(myPlacemark);
					myPlacemark = new ymaps.GeoObject({
						geometry: 
						{
							type: 'Point', // тип геометрии - точка
							coordinates: coords // координаты точки
						}
					});
					myMap.geoObjects.add(myPlacemark); 
					console.log(coords);
					$('[name=latitude]').val(parseInt(coords[0]*1000)/1000);
					$('[name=longitude]').val(parseInt(coords[1]*1000)/1000);
				}
			};

			// Помещаем созданный класс в хранилище поведений.
			// Далее данное поведение будет доступно по ключу 'mybehavior'.
			ymaps.behavior.storage.add('mybehavior', MyBehavior);
			// Включаем поведение
			myMap.behaviors.enable('mybehavior');
						
		  });
		  
		  
		</script>";
		echo $html;
	}
	static function update_map_owners($obj)
	{
		global $wpdb;
		$query = "DELETE FROM ".$wpdb->prefix."point_map 
		WHERE point_id=".$obj->id;
		$wpdb->query($query);
		$query = "INSERT INTO ".$wpdb->prefix."point_map 
		(`ID`, `point_id`, `map_id`, `date`, `session_id`, `approved_date`, `approve_user_id`) VALUES"; 
		$q = [];
		foreach($_POST['owner_id'] as $owner)
		{
			$q[] = " (NULL, '$obj->id', '$owner', '".time()."', '0', '1', '0')";
		}
		$query .= implode(",", $q);
		//var_dump( $query );
		//wp_die();
		$wpdb->query($query);
	}
	static function save_admin_edit($obj)
	{
		wp_set_object_terms($obj->id, (int)$_POST['point_type'], SHM_POINT_TYPE);
		static::update_map_owners($obj);
		return [
			"latitude"		=> $_POST['latitude'],
			"longitude"		=> $_POST['longitude'],
			"zoom"			=> $_POST['zoom'],
			"approved"		=> $_POST['approved'],
		];
	}
	static function owner_fields() 
	{
		add_meta_box( 'owner_fields', __('Map owner', SHMAPPER), [__CLASS__, 'owner_fields_box_func'], static::get_type(), 'side', 'low'  );
		
	}
	
	static function owner_fields_box_func( $post )
	{	
		$lt = static::get_instance( $post );
		echo static::owner_fields_edit($lt, "radio");			
	}
	static function owner_fields_edit($obj = -1, $type="checkbox")
	{
		global $wpdb;
		$id = $obj == -1 ? -1  : $obj->id;
		$query = "SELECT map_id FROM ".$wpdb->prefix."point_map WHERE point_id=".$id;
		$d = $wpdb->get_results($query);
		$selects = [];
		foreach($d as $dd)
			$selects[] = $dd->map_id;
		//var_dump( $selects );
		//wp_die();
		$all = ShmMap::get_all(-1, -1, 0, 'title', 'ASC' );
		$html = "
		<div class='categorydiv'>
			<div  class='tabs-panel'>
				<ul class='categorychecklist form-no-clear'>";
		foreach($all as $map)
		{
			$selected = in_array($map->ID, $selects) ? " checked " : "";
			$html .= "
				<li class='popular-category'>
					<label class='selectit'>
						<input value='$map->ID' type='$type' name='owner_id[]' $selected />
						$map->post_title
					</label>
				</li>
			";
		}		
		$html .= "
				</ul>
			</div>
		</div>";
		return $html;
	}
	static function bulk_owner_fields_edit( $type="checkbox")
	{
		$all = ShmMap::get_all(-1, -1, 0, 'title', 'ASC' );
		$html = "
		<ul class='cat-checklist form-no-clear'>";
		foreach($all as $map)
		{
			$selected = in_array($map->ID, $selects) ? " checked " : "";
			$html .= "
				<li class='popular-category'>
					<label class='selectit'>
						<input value='$map->ID' type=' $type' name='owner_id[]' $selected />
						$map->post_title
					</label>
				</li>
			";
		}		
		$html .= "
		</ul>";
		return $html;
	}
	
	static function add_views_column( $columns )
	{
		$columns = parent::add_views_column( $columns );
		unset( $columns['zoom'] );
		unset( $columns['latitude'] );
		unset( $columns['longitude'] );
		unset( $columns['approved'] );
		$columns = array_slice($columns, 0, 1, true) + ["ids"=>__("ID"), 'type' => __("Type")] + array_slice($columns, 1, count($columns) - 1, true) ;
		$columns['location'] 	= __("GEO location", SHMAPPER);
		$columns['thumb'] 		= "<div class='shm-camera' title='" . __("Image", SHMAPPER) ."'></div>";
		$columns['owner_map'] 	= __("Usage in Maps: ", SHMAPPER);
		return $columns;	
	}
	static function fill_views_column($column_name, $post_id) 
	{
		$obj = static::get_instance($post_id);
		switch($column_name)
		{
			case "ids":
				echo $post_id;
				break;
			case "location":
				echo __("Latitude", SHMAPPER).": <strong>" . $obj->get_meta("latitude") ."</strong>".
				"<br>".
				 __("Longitude", SHMAPPER).": <strong>" . $obj->get_meta("longitude") ."</strong>".
				"<br>".
				 __("Location", SHMAPPER).": <strong>" . $obj->get_meta("location") ."</strong>";
				break;
			case "owner_map":
				echo $obj->get_owner_list();
				break;
			case "type":
				$terms = get_the_terms( $post_id, SHM_POINT_TYPE );
				foreach($terms as $term)
				{
					//$term = get_term($obj->get_meta("type"), SHM_POINT_TYPE);
					echo ShMapPointType::get_icon($term);
				}
				//the_terms( $post_id, SHM_POINT_TYPE, "", ", ", "" );
				break;
			case "thumb":
				echo "<div class='shm_type_icon2' style='background-image:url(" . get_the_post_thumbnail_url( $post_id, [75, 75] ) .");'></div>" ;
				break;
			default:
				parent::fill_views_column($column_name, $post_id);
		}
	}
	
	static function get_insert_form( $data )
	{
		// 0 - map_id 
		// 1 - x
		// 2 - y
		$html = "
		<div class='shm-row'>
			<input type='hidden' name='shm_map_id' value='".$data[0]."' />
			<input type='hidden' name='shm_x' value='".$data[1]."' />
			<input type='hidden' name='shm_y' value='".$data[2]."' />
			<input type='hidden' name='shm_loc' value='".$data[2]."' />
			<div class='shm-12'>
				<label class='shm-form'>" . __("Title") . "</label>
				<input class='shm-form shm-title-4' name='shm-new-point-title' onclick='this.classList.remove(\"shm-alert\");' />
			</div>
			<div class='shm-12'>
				<label class='shm-form'>" . __("Description") . "</label>
				<textarea class='shm-form' rows='4' name='shm-new-point-content' onclick='this.classList.remove(\"shm-alert\");'></textarea>
			</div>
			<div class='shm-12' onclick='this.classList.remove(\"shm-alert\");'>
				<label class='shm-form'>" . __("Type", SHMAPPER) . "</label>".
				ShMapPointType::get_ganre_swicher( ["name"=>"shm-new-point-type", "prefix" => "shm-new-type"],  "radio" ).
			"</div>
		<div>
		";
		
		return $html;
	}
	static function insert($data)
	{
		$type = (int)$data['type'];
		$map_id = (int)$data['map_id'];
		unset( $data['type'] );
		unset( $data['map_id'] );
		$point = parent::insert($data);
		$query = $point->add_to_map( $map_id );
		wp_set_object_terms( $point->id, (int)$type, SHM_POINT_TYPE );
		return $point;
	}
	function add_to_map($map_id)
	{
		global $wpdb;
		$query = "DELETE FROM " . $wpdb->prefix . "point_map 
		WHERE map_id=$map_id AND point_id=$this->id;";
		$wpdb->query($query);
		$query = "INSERT INTO " . $wpdb->prefix . "point_map 
		(`ID`, `point_id`, `map_id`, `date`, `session_id`, `approved_date`, `approve_user_id`) VALUES 
		(NULL, $this->id, $map_id, " .time() . ", 1, 0, 1);";
		$wpdb->query($query);
		return [ $this->id, $query ];
	}
	
	function get_owners()
	{
		global $wpdb;
		$post_id = $this->id;
		$query = "SELECT p.ID, p.post_title FROM `".$wpdb->prefix."point_map` as mp
		left join ".$wpdb->prefix."posts as p on mp.map_id=p.ID
		where point_id=$post_id";
		$res = $wpdb->get_results($query);
		return $res;
	}
	function get_owner_list( $before = "", $separator = "<br>", $after = "")
	{
		$owners = $this->get_owners();
		$d = [];
		foreach($owners as $r)
		{
			$link = is_admin() ? "/wp-admin/post.php?post=".$r->ID."&action=edit" : get_permalink($r->ID);
			$d[] = "<a href='$link'>".$r->post_title."</a>";
		}

		return $before . implode($separator, $d) . $after;
	}
	function draw()
	{
		$str = ["
","

"];
		$types		= wp_get_object_terms($this->id, SHM_POINT_TYPE);
		$type		= $types[0];
		$post_title	= $this->get("post_title");
		$post_content = str_replace($str, " " , wpautop( wp_trim_words($this->get("post_content"), 20) )); 
		$latitude	= $this->get_meta("latitude");
		$latitude 	= $latitude ? $latitude : 55.8;
		$longitude	= $this->get_meta("longitude");
		$longitude 	= $longitude ? $longitude : 37.8;
		$zoom		= $this->get_meta("zoom");
		$zoom 		= $zoom ? $zoom : 11;
		$color 		= get_term_meta($type->term_id, "color", true);
		$height 	= get_term_meta($type->term_id, "height", true);
		$icon 		= ShMapPointType::get_icon_src( $type->term_id )[0];
		$width 		= ShMapPointType::get_icon_src( $type->term_id )[2]/ShMapPointType::get_icon_src( $type->term_id )[1] * $this->height ;
		//$type 		= $type->name;
		//$term_id 	= $type->term_id;
		
		$html 	.= "
			<div class='shm-row'>
				<div class='shm-12'>
					<div class='spacer-10'></div>
					<div id='YMapID' style='width:100%;height:300px;border:1px solid darkgrey;'>
			
					</div>
					<div class='spacer-10'></div>
				</div>	
			</div>		
			<script type='text/javascript'>
				
				ymaps.ready(function () 
				{
					var myMap = new ymaps.Map('YMapID', 
					{
					  center: [ $latitude, $longitude ],
					  controls: [ ],
					  zoom: $zoom
					});
					
					if( '$icon' )
					{
						paramet = {
							balloonMaxWidth: 250,
							hideIconOnBalloonOpen: false,
							iconColor:'$color',
							iconLayout: 'default#image',
							iconImageHref: '$icon',
							iconImageSize: [50,50], 
							iconImageOffset: [-5, -25],
						};
					}
					else
					{
						paramet = {
							balloonMaxWidth: 250,
							hideIconOnBalloonOpen: false,
							iconColor: '$color',
							preset: 'islands#dotIcon'
						}
					}
					
					
					
					// Создание геообъекта с типом точка (метка).
					var myPlacemark = new ymaps.Placemark([$latitude, $longitude] ,
						{
							geometry: 
							{
								type: 'Point', // тип геометрии - точка
								coordinates: [$latitude, $longitude] // координаты точки
							},
							balloonContentHeader: '$post_title',
							balloonContentBody: '$post_content',
							//balloonContentFooter: '$icon',
							hintContent: '$post_title'
						}, 
						paramet);
					myMap.geoObjects.add(myPlacemark); 
					myPlacemark.balloon.open(myMap.getCenter());
				});
			</script>";
		return $html;
	}
	static function the_content($content)
	{
		global $post;
		if($post->post_type == SHM_POINT && (is_single() || is_archive() ))
		{
			$point = static::get_instance($post);
			$t .= $point->draw();
			$t .= $point->get_owner_list( __("Usage in Maps: ", SHMAPPER), ", ", " "  ) .
			"<div class='spacer-30'></div>";
			return $t . $content;
		}
		return $content;
		
	}
	static function register_my_bulk_actions( $bulk_actions )
	{
		$bulk_actions['my_action'] = 'Моё действие';
		return $bulk_actions;
	}
	static  function my_bulk_action_handler( $redirect_to, $doaction, $post_ids )
	{
		// ничего не делаем если это не наше действие
		if( $doaction !== 'my_action' )
			return $redirect_to;
		foreach( $post_ids as $post_id )
		{			
			// действие для каждого поста
		}
		$redirect_to = add_query_arg( 'my_bulk_action_done', count( $post_ids ), $redirect_to );
		return $redirect_to;
	}
	static  function my_bulk_action_admin_notice()
	{
		if( empty( $_GET['my_bulk_action_done'] ) )		return;
		$data = $_GET['my_bulk_action_done'];
		$msg = sprintf( 'Моё действие обработало записей: %d.', intval($data) );
		echo '<div id="message" class="updated"><p>'. $msg .'</p></div>';
	}
	static function my_bulk_edit_custom_box( $column_name, $post_type ) 
	{ 
		if($post_type != static::get_type())	return;
		/*
		static $printNonce = TRUE;
		if ( $printNonce ) {
			$printNonce = FALSE;
			wp_nonce_field( plugin_basename( __FILE__ ), 'book_edit_nonce' );
		}
		*/
		?>
		<fieldset class="inline-edit-col-center inline-edit-shm_point">
		  <div class="inline-edit-col column-<?php echo $column_name; ?>">
			<?php 
			// Например здесь получить ID записи ... ?
			 switch ( $column_name ) {
			 case 'owner_map':
				 echo "<span class='title'>".__("Usage in Maps: ", SHMAPPER)."</span>". static::bulk_owner_fields_edit( );
				 break;
			default:
				break;
			 }
			?>
		  </div>
		</fieldset>
		<?php
	} 
	static function save_bulk_edit_book()
	{
		$post_ids	= ( ! empty( $_POST[ 'post_ids' ] ) ) ? $_POST[ 'post_ids' ] : array();
		$owner_id	= ( ! empty( $_POST[ 'owner_id' ] ) ) ? $_POST[ 'owner_id' ] : null;
		if ( ! empty( $post_ids ) && is_array( $post_ids )  && ! empty( $owner_id ) && is_array( $owner_id ) ) 
			foreach( $post_ids as $post_id ) 
			{
				$obj = static::get_instance((int)$post_id);
				static::update_map_owners($obj);
			}
		wp_die();
		/**/
	}
}