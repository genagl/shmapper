<?php
/**
 * ShMapperTracks
 *
 * 
 */

class ShMapperTracksPoint extends SMC_Post
{
	static function init() 
	{ 
		add_action('init',									array(__CLASS__, 'add_class'), 15 );
		//add_action('admin_menu',							array(__CLASS__, 'owner_fields'), 20);
		
		//bulk-actions
		//add_action("bulk_edit_custom_box", 				array(__CLASS__, 'my_bulk_edit_custom_box'), 2, 2 );
		//add_action("shmapper_bulk_before", 				array(__CLASS__, 'save_bulk_edit_point') );		
		//add_filter("the_content",							array(__CLASS__, "the_content"));
		parent::init();
	}
	static function get_type()
	{
		return SHMAPPER_TRACKS_POINT;
	}
	
	static function add_class()
	{
		$labels = array(
			'name' => __('Track marker', SHMAPPER_TRACKS),
			'singular_name' => __("Track marker", SHMAPPER_TRACKS),
			'add_new' => __("add Track marker", SHMAPPER_TRACKS),
			'add_new_item' => __("add Track marker", SHMAPPER_TRACKS),
			'edit_item' => __("edit Track marker", SHMAPPER_TRACKS),
			'new_item' => __("add Track marker", SHMAPPER_TRACKS),
			'all_items' => __("all Track markers", SHMAPPER_TRACKS),
			'view_item' => __("view Track marker", SHMAPPER_TRACKS),
			'search_items' => __("search Track marker", SHMAPPER_TRACKS),
			'not_found' =>  __("Track marker not found", SHMAPPER_TRACKS),
			'not_found_in_trash' => __("no found Track marker in trash", SHMAPPER_TRACKS),
			'menu_name' => __("Track markers", SHMAPPER_TRACKS)
		);
		$args = array(
			 'labels' => $labels
			,'public' => true
			,'show_ui' => true
			,'has_archive' => true 
			,'exclude_from_search' => false
			,'menu_position' => 19
			,'menu_icon' => "dashicons-location"
			,'show_in_menu' => false
			,'show_in_rest' => true
			,'supports' => array(  'title', "editor", "thumbnail")
			,'capability_type' => 'post'
		); 
		register_post_type(SHMAPPER_TRACKS_POINT, $args);
	}
}