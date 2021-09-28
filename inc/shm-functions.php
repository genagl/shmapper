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
