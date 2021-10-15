<?php
/**
 * ShMapper Functions
 *
 * @package teplitsa
 */

/**
 * Get options
 */
function shm_get_options(){
	return get_option( 'shmapper-by-teplitsa' );
}

/**
 * Get map type
 */
function shm_get_map_type(){
	$shm_options = shm_get_options();
	if ( isset( $shm_options['map_api'] ) && $shm_options['map_api'] ) {
		$map_type = $shm_options['map_api'];
	}
	return (int) $map_type;
}

/**
 * Is Local
 */
function shm_is_local(){
	$shm_is_local = false;
	$whitelist = array(
		'127.0.0.1',
		'::1'
	);
	if ( in_array( $_SERVER['REMOTE_ADDR'], $whitelist ) ) {
		$shm_is_local = true;
	}
	return $shm_is_local;
};

/**
 * Disable Gugenberg
 *
 * @param bool   $current_status Current gutenberg status. Default true.
 * @param string $post_type      The post type being checked.
 */
function shmapper_disable_gutenberg( $current_status, $post_type ) {
	if ( in_array( $post_type, array( SHM_POINT ), true ) ) {
		return false;
	}
	return $current_status;
}
add_filter( 'use_block_editor_for_post_type', 'shmapper_disable_gutenberg', 10, 2);

/**
 * Add support upload mime type files gpx and kml.
 */
function shm_upload_mimes( $mime_types ) {
	$mime_types['gpx'] = 'text/gpx';
	$mime_types['kml'] = 'text/xml';
	return $mime_types;
}
add_filter( 'upload_mimes', 'shm_upload_mimes' );

/**
 * Rewrite rules.
 */
function shm_flush_rewrite_rules(){
	ShmMap::add_class();
	ShMaperTrack::add_class();
	ShMapperRequest::add_class();
	ShMapperTracksPoint::add_class();
	ShmPoint::add_class();
	ShMapPointType::register_all();
	ShMapTrackType::register_all();
	flush_rewrite_rules();
}
