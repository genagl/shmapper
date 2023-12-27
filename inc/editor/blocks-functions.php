<?php
/**
 * ShMapper Blocks Functions
 *
 * @package teplitsa
 */

/**
 * Get map post meta keys
 */
function shm_get_map_meta_keys(){

	$keys = array(
		array(
			'key' => 'is_form',
			'label' => __( 'Form for submitting markers', 'shmapper-by-teplitsa' ),
		),
		array(
			'key' => 'is_filtered',
			'label' => __( 'Filters', 'shmapper-by-teplitsa' ),
		),
		array(
			'key' => 'is_csv',
			'label' => __( 'Export csv', 'shmapper-by-teplitsa' ),
		),
		array(
			'key' => 'is_legend',
			'label' => __( 'Legend', 'shmapper-by-teplitsa' ),
		),
		array(
			'key' => 'is_search',
			'label' => __( 'Map search', 'shmapper-by-teplitsa' ),
		),
		array(
			'key' => 'is_zoomer',
			'label' => __( 'Map zoom', 'shmapper-by-teplitsa' ),
		),
		array(
			'key' => 'is_layer_switcher',
			'label' => __( 'Map layer switcher', 'shmapper-by-teplitsa' ),
		),
		array(
			'key' => 'is_scroll_zoom',
			'label' => __( 'Scroll zoom', 'shmapper-by-teplitsa' ),
		),
		array(
			'key' => 'is_drag',
			'label' => __( 'Dragging', 'shmapper-by-teplitsa' ),
		),
	);

	return $keys;
}

/**
 * Get map posts
 */
function shm_get_map_posts(){

	$keys = shm_get_map_meta_keys();

	$maps = get_posts( array(
		'post_type' => 'shm_map',
		'numberposts' => -1,
	) );

	if ( $maps ) {
		foreach( $maps as $index => $map ) {
			$map_id = $map->ID;
			foreach ( $keys as $item ) {
				$key = $item['key'];
				$maps[ $index ]->$key = boolval( get_post_meta( $map_id, $key, true ) );
			}
		}
	}

	return $maps;
}
