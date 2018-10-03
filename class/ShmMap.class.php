<?php
class ShmMap extends SMC_Post
{
	static function init()
	{
		add_action('init',					array(__CLASS__, 'add_class'), 14 );
		add_action('admin_menu',			array(__CLASS__, 'my_form_fields'), 20);
		add_filter("the_content",			array(__CLASS__, "the_content"));
		parent::init();
	}
	static function get_type()
	{
		return SHM_MAP;
	}
	
	static function add_class()
	{
		$labels = array(
			'name' => __('Map', SHMAPPER),
			'singular_name' => __("Map", SHMAPPER),
			'add_new' => __("add Map", SHMAPPER),
			'add_new_item' => __("add Map", SHMAPPER),
			'edit_item' => __("edit Map", SHMAPPER),
			'new_item' => __("add Map", SHMAPPER),
			'all_items' => __("all Maps", SHMAPPER),
			'view_item' => __("view Map", SHMAPPER),
			'search_items' => __("search Map", SHMAPPER),
			'not_found' =>  __("Map not found", SHMAPPER),
			'not_found_in_trash' => __("no found Map in trash", SHMAPPER),
			'menu_name' => __("all Maps", SHMAPPER)
		);
		$args = array(
			 'labels' => $labels
			,'public' => true
			,'show_ui' => true // показывать интерфейс в админке
			,'has_archive' => true 
			,'exclude_from_search' => false
			,'menu_position' => 17
			,'menu_icon' => "dashicons-location-alt"
			,'show_in_menu' => "shm_page"
			,'show_in_rest' => true
			,'supports' => array(  'title', 'author' )
			,'capability_type' => 'post'
		);
		register_post_type(SHM_MAP, $args);
	}
	
	static function add_views_column( $columns )
	{
		$_columns["cb"]				= " ";
		$_columns['ids']			= __("ID", SHMAPPER );
		$_columns['title']			= __("Title" );
		$_columns['is_csv']	= "
		<span 
			class='dashicons dashicons-editor-justify shm-notify' 
			title='" . __("Export csv", SHMAPPER)."'>
		</span>";
		$_columns['is_legend']	= "
		<span 
			class='dashicons dashicons-image-filter shm-notify' 
			title='" . __("Legend exists", SHMAPPER)."'>
		</span>";
		$_columns['is_form']	= "
		<span 
			class='dashicons dashicons-clipboard shm-notify' 
			title='" . __("Form exists", SHMAPPER)."'>
		</span>";
		$_columns['notify_owner']	= "
		<span 
			class='dashicons dashicons-megaphone shm-notify' 
			title='" . __("Notify owner of Map", SHMAPPER)."' data-title='" . __("Notify owner of Map", SHMAPPER)."'>
		</span>";
		$_columns['shortcodes']		= __("shortcodes", SHMAPPER);
		$_columns['placemarks']		= __("Map markers", SHMAPPER);
		$_columns['author']			= __("Author");
		return $_columns;
	}
	static function fill_views_column($column_name, $post_id) 
	{
		$obj = static::get_instance($post_id);
		switch($column_name)
		{
			case "ids":
				echo $post_id;
				break;
			case "placemarks":
				echo "<div class='shm-title-2'>" . $obj->get_point_count() . "</div>";
				break;
			case "shortcodes":
				$html = "
				<div class='shm-row'>
					<div class='shm-12 shm-md-12 shm-color-lightgrey small'>".
						__("include all (map and request form)", SHMAPPER) .
					"</div>
					<div class='shm-12 shm-md-12'>
						<input type='text' disabled  class='sh-form' value='[shmMap id=\"" . $post_id . "\" ]' />
					</div>
				</div>
				<div class='shm-row'>
					<div class='shm-12 shm-md-12 shm-color-lightgrey small' >".
						__("only map", SHMAPPER) .
					"</div>
					<div class='shm-12 shm-md-12'>
						<input type='text' disabled  class='sh-form' value='[shmMap id=\"" . $post_id . "\" map]' />
					</div>
				</div>
				</div>
				<div class='shm-row'>
					<div class='shm-12 shm-md-12 shm-color-lightgrey small'>".
						__("only request form", SHMAPPER) .
					"</div>
					<div class='shm-12 shm-md-12'>
						<input type='text' disabled  class='sh-form' value='[shmMap id=\"" . $post_id . "\" form]' />
					</div>
				</div>
				";
				echo $html;
				break;
			default:
				parent::fill_views_column($column_name, $post_id);
		}
	}
	
	static function my_extra_fields() 
	{
		add_meta_box( 'map_fields', __('Map', SHMAPPER), [__CLASS__, 'extra_fields_box_func'], static::get_type(), 'normal', 'high'  );
		
	}
	static function my_form_fields() 
	{
		add_meta_box( 'form_fields', __('Request form', SHMAPPER), [__CLASS__, 'form_fields_box_func'], static::get_type(), 'normal', 'low'  );
		
	}
	static function form_fields_box_func( $post )
	{	
		$lt = static::get_instance( $post );
		echo static::view_form_fields_edit($lt);			
		//wp_nonce_field( basename( __FILE__ ), static::get_type().'_metabox_nonce' );
	}
	static function view_form_fields_edit($obj)
	{
		require_once(SHM_REAL_PATH."class/SMC_Object_type.php");
		$SMC_Object_type	= SMC_Object_Type::get_instance();
		$bb				= $SMC_Object_type->object [static::get_type()];
		$html = "<section post_id='$obj->id'><div>";
		foreach($bb as $key=>$value)
		{
			if($key == 't' || $key == 'class' || $value['distination'] != "form") continue;
			$meta = get_post_meta( $obj->id, $key, true);			
			$$key = $meta;
			switch( $value['type'] )
			{
				case "text":
					$h = "<textarea name='$key' id='$key' class='sh-form' rows='6' placeholder='".$value['placeholder']."'>$meta</textarea>";
					break;
				case "number":
					$h = "<input type='number' name='$key' id='$key' value='$meta' class='sh-form'  placeholder='".$value['placeholder']."'/>";
					break;
				case "form_editor":
					//ob_start();
					//var_dump($meta);
					//$v = ob_get_contents();
					//ob_end_clean();
					$meta = $meta ? $meta : ShmForm::get_default();
				
					$h = $v . static::formEditor( $meta );
					break;
				case "boolean":
					$h = "<input type='checkbox' class='checkbox' name='$key' id='$key' value='1' " . checked(1, $meta, 0) . "  /><label for='$key'></label>
					<div class='spacer-10'></div>";
					break;
				default:
					$h = "<input type='' name='$key' id='$key' value='$meta' class='sh-form'  placeholder='".$value['placeholder']."'/>";
			}
			$html .="<div class='shm-row' $opacity>
				<div class='shm-3 shm-md-12 sh-right sh-align-middle'>".$value['name'] . "</div>
				<div class='shm-9 shm-md-12'>
					$h
				</div>
			</div>
			<div class='spacer-5'></div>";
		}
		$html 	.= "</div>			
		</section>";
		echo $html;
	}
	static function view_admin_edit($obj)
	{
		require_once(SHM_REAL_PATH."class/SMC_Object_type.php");
		$SMC_Object_type	= SMC_Object_Type::get_instance();
		$bb				= $SMC_Object_type->object [static::get_type()];
		
		foreach($bb as $key=>$value)
		{
			if($key == 't' || $key == 'class' || $value['distination'] != "map") continue;
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
					$h = "<input type='checkbox' class='checkbox' name='$key' id='$key' value='1' " . checked(1, $meta, 0) . "/><label for='$key'></label>
					<div class='spacer-10'></div>";
					break;
				default:
					$h = "<input type='' name='$key' id='$key' value='$meta' class='sh-form'/>";
			}
			$html .="<div class='shm-row' $opacity>
				<div class='shm-3 shm-md-12 sh-right sh-align-middle'>".$value['name'] . "</div>
				<div class='shm-9 shm-md-12 '>
					$h
				</div>
			</div>
			<div class='spacer-5'></div>";
		}
		$html 	.= "
			<div class='spacer-15'></div>
			<div class='shm-row'>
				<div class='shm-3 shm-md-12 sh-right'>". __("Map", SHMAPPER). "</div>
				<div class='shm-9 shm-md-12'>".
					$obj->draw().
				"</div>
			</div>
			
			";
		$html 	.= "</div>";
		echo $html;
	}
	static function save_admin_edit($obj)
	{
		return [
			"latitude"		=> $_POST['latitude'],
			"longitude"		=> $_POST['longitude'],
			"zoom"			=> $_POST['zoom'],
			"is_legend"		=> $_POST['is_legend'] ? 1 : 0,
			"is_filtered"	=> $_POST['is_filtered'] ? 1 : 0,
			"is_csv"		=> $_POST['is_csv'] ? 1 : 0,
			"is_lock"		=> $_POST['is_lock'] ? 1 : 0,
			"is_clustered"	=> $_POST['is_clustered'] ? 1 : 0,
			
			"is_form"		=> $_POST['is_form'] ? 1 : 0,
			"form_title"	=> $_POST['form_title'],
			"form_contents"	=> $_POST['form_contents'],			
			"notify_owner"	=> $_POST['notify_owner'] ? 1: 0,			
			"form_forms"	=> $_POST['form_forms']			
		];
	}
	static function formEditor($data)
	{
		$html 	.= "
		<div style='display:block;  border:#888 1px solid; padding:0px;' id='form_editor'>
			<ul class='shm-card'>";
		//for( $i = 0; $i < 5; $i ++ )
		$i 		= 0;
		foreach($data as $dat)
		{
			$html .= ShmForm::get_admin_element( $i, $dat );
			$i++;
		}				
		$html .= ShmForm::wp_params_radio( -1, -1 ) . "
			</ul>
		</div>";
		return $html;
		
	}
	function get_include_types()
	{
		$form_forms = $this->get_meta("form_forms");
		foreach($form_forms as $element)
		{
			if( $element['type'] == 8 )
			{
				return explode(",", $element["placemarks"]);
			}
		}
		return false;
	}
	function get_csv()
	{
		$points		= $this->get_points();
		$csv 		= [implode(SHM_CSV_STROKE_SEPARATOR, [ "#", __("Title", SHMAPPER), __("Description", SHMAPPER),  __("Location", SHMAPPER) ])];
		$i = 0;
		foreach($points as $point)
		{
			$p 		= ShmPoint::get_instance($point);
			$csv[]	= implode(SHM_CSV_STROKE_SEPARATOR, [
				($i++) . ". ",
				'"' . str_replace(';', ",", $p->get("post_title") ). '"', 
				'"' . str_replace(';', ",", $p->get("post_content")) . '"',
				'"' . str_replace(';', ",", $p->get_meta("location") . " ( " . $p->get_meta("latitude") . "," . $p->get_meta("longitude") . " )" ) . '"'
			]);
		}
		$csv_data = iconv ("UTF-8", "cp1251",implode( SHM_CSV_ROW_SEPARATOR, $csv)); // ;
		file_put_contents(WP_CONTENT_DIR . "/uploads/shmapper/" . $p->get("post_title") . ".csv", $csv_data);
		return "/wp-content/uploads/shmapper/" . $p->get("post_title") . ".csv";
	}
	function get_points_args()
	{
		global $wpdb;
		return explode(",", $wpdb->get_var("SELECT GROUP_CONCAT( mp.point_id ) 
		FROM ".$wpdb->prefix."point_map as mp
		WHERE map_id=$this->id
		GROUP BY map_id"));
	}
	function get_points()
	{
		$args = [
			"post_type" 	=> SHM_POINT,
			"post_status"	=> "publish",
			"numberposts"	=> -1,
			"post__in"		=> $this->get_points_args()
		];
		return get_posts($args);
	}
	
	function get_point_count()
	{
		global $wpdb;
		return  $wpdb->get_var("SELECT COUNT(*)
		FROM ".$wpdb->prefix."point_map as mp
		WHERE map_id=$this->id;");		
	}
	function get_delete_form( $href )
	{
		$html ="
		<div class='shm-row' shm_delete_map_id='" . $this->id . "' >
			<div class='shm-12 small shm-color-grey'>" . 
				__("What do with placemarks of deleting Map?", SHMAPPER) . 
			"</div>
			<div class='shm-12'>
				<div class='spacer-10'></div>
				<input type='radio' class='radio' id='dd1' value='1'  name='shm_esc_points' checked /> 
				<label for='dd1'>" . __("Delete all Points", SHMAPPER) . "</label>
				<div class='spacer-10'></div>
			</div>
			<div class='shm-12'>
				<div class='spacer-10'></div>
				<input type='radio' class='radio' id='dd2' value='2' name='shm_esc_points' /> 
				<label for='dd2'>" . __("Escape all Points without Owner Map", SHMAPPER) . "</label>
				<div class='spacer-10'></div>
			</div>
			<div class='shm-12'>
				<div class='spacer-10'></div>
				<input type='radio' class='radio' id='dd3' value='3'  name='shm_esc_points' /> 
				<label for='dd3'>" . __("Switch all Points to anover Map", SHMAPPER) . "</label>
				<div class='spacer-10'></div>" .
					ShmMap::wp_dropdown([
						"class"		=> "shm-form",
						"id"		=> "shm_esc_points_id",
						"style"		=> "display:none;"
												
					]) . 
				"<div class='spacer-10'></div>
			</div>
		</div>
		<!--div class='shm-row'>
			<div class='shm-12'>
				<div class='spacer-10'></div>
				<a class='button' href='$href'>delete</a>
				<div class='spacer-10'></div>
			</div>
		</div-->";
		return $html;
	}
	function get_map_points()
	{
		$points = $this->get_points();
		$p = [];
		$str = ["
","

"];
		foreach($points as $point)
		{
			$pn = ShmPoint::get_instance($point);
			$types	= wp_get_object_terms($pn->id, SHM_POINT_TYPE);
			$type	= $types[0];
			$pnt 	= new StdClass;
			$pnt->ID			= $pn->id;
			$pnt->post_title	= $pn->get("post_title");
			$pnt->post_content	= str_replace( $str , " " , wp_trim_words($pn->get("post_content"), 20) ); 
			$pnt->latitude 		= $pn->get_meta("latitude");
			$pnt->longitude 	= $pn->get_meta("longitude");
			$pnt->location 		= $pn->get_meta("location");
			$pnt->color 		= get_term_meta($type->term_id, "color", true);
			$pnt->height 		= get_term_meta($type->term_id, "height", true);
			$pnt->height 		= $pnt->height 	? $pnt->height 	: 30;
			$pnt->type 			= $type->name;
			$pnt->term_id 		= $type->term_id;
			$pnt->icon 			= ShMapPointType::get_icon_src( $type->term_id )[0];
			//$pnt->width 		= ShMapPointType::get_icon_src( $type->term_id )[2]/ShMapPointType::get_icon_src( $type->term_id )[1] * $pnt->height ;
			//$pnt->width 		= $pnt->width ? $pnt->width : $pnt->height;
			$p[] 	= $pnt;
		}
		return $p;
	}
	function draw($args=-1)
	{
		if(!is_array($args)) $args = [ "height" => 450, "id" => $this->id ];
		require_once(SHM_REAL_PATH . "tpl/shmMap.php");
		return draw_shMap($this, $args);
	}
	
	/*
		final delete map and difference placemarks migration
	*/
	function shm_delete_map_hndl($data)
	{
		global $wpdb;
		$points = $this->get_points();
		switch($data['action'])
		{
			case 1:
				// search once usage points in deleted Map (only_once == 1)
				$query = "SELECT DISTINCT( p1.point_id ) AS point, COUNT(p1.map_id)=1 AS only_once, GROUP_CONCAT(p1.map_id) AS maps
				FROM " . $wpdb->prefix . "point_map AS p1
				LEFT JOIN " . $wpdb->prefix . "point_map AS p2 ON p1.point_id=p2.point_id
				WHERE p2.map_id=".$this->id."
				GROUP BY p1.point_id";
				$res = $wpdb->get_results($query);
				$i = 0;
				foreach($res as $point)
				{
					if($point->only_once == 1)
					{
						ShmPoint::delete($point->point);
						$i++;
					}
				}
				$message = sprintf(__("Succesfuly delete map width %s points", SHMAPPER), $i );
				break;
			case 2:
				$count = $wpdb->get_var("SELECT COUNT(point_id) FROM ".$wpdb->prefix."point_map WHERE map_id=".$this->id);	$query = "DELETE FROM " . $wpdb->prefix . "point_map WHERE map_id=".$this->id;
				$res = $wpdb->query($query);
				$message = sprintf(__("Succesfuly delete map and %s points are orphans now", SHMAPPER), $count );
				break;
			case 3:
				$count = $wpdb->get_var("SELECT COUNT(point_id) FROM ".$wpdb->prefix."point_map WHERE map_id=".$this->id);
				$query = "UPDATE " . $wpdb->prefix . "point_map SET map_id=".$data['anover']. " WHERE map_id=".$this->id;
				$res = $wpdb->query($query);
				$map2 = static::get_instance($data['anover']);
				$message = sprintf(__("Succesfuly delete map and %s points migrates to %s", SHMAPPER), $count, $map2->get("post_title") );
				break;
		}
		static::delete( $this->id );
		return ["query" => $query, "res" => $res, "message" => $message];
	}
	static function the_content($content)
	{
		global $post;
		$t = ($post->post_type == SHM_MAP && (is_single() || is_archive() )) ? '[shmMap id="' . $post->ID . '" map form ]'  : "";
		return $t . $content;
	}
}