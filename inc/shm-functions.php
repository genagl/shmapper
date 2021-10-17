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
 * Get Default Marker
 */
function shm_get_default_marker( $hex = '#f43724'){
	$color = str_replace('#', '', $hex );
	$color_second = shm_colour_brightness($color, 0.6);
	$marker = array(
		'color'  => $hex,
		'height' => '34',
		'width'  => '40',
		'icon'   => "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='30px' height='36px' viewBox='13 11 30 36'%3E%3Cpath fill='%23" . $color_second . "' d='M42.929,24.838c-0.234-2.622-1.135-5.137-2.615-7.3c-1.48-2.163-3.489-3.899-5.829-5.039 c-2.341-1.14-4.933-1.644-7.523-1.463c-2.59,0.181-5.09,1.04-7.255,2.494c-1.854,1.257-3.411,2.915-4.558,4.855 c-1.147,1.94-1.856,4.113-2.077,6.364c-0.216,2.236,0.061,4.493,0.812,6.606s1.956,4.032,3.529,5.614l9.353,9.501 c0.164,0.168,0.359,0.301,0.574,0.392S27.785,47,28.018,47s0.464-0.047,0.679-0.138c0.215-0.091,0.41-0.224,0.574-0.392l9.317-9.501 c1.573-1.583,2.778-3.501,3.529-5.614c0.751-2.114,1.028-4.37,0.812-6.606V24.838z M36.117,34.447L28,42.677l-8.117-8.231 c-1.196-1.213-2.113-2.68-2.683-4.295c-0.571-1.615-0.781-3.338-0.617-5.045c0.166-1.734,0.709-3.408,1.591-4.903 c0.882-1.495,2.08-2.772,3.509-3.739c1.872-1.261,4.07-1.934,6.317-1.934s4.445,0.673,6.317,1.934 c1.424,0.964,2.62,2.235,3.502,3.723c0.882,1.488,1.428,3.156,1.598,4.883c0.17,1.713-0.038,3.443-0.609,5.065 C38.237,31.757,37.318,33.23,36.117,34.447z M36.117,34.447L28,42.677l-8.117-8.231c-1.196-1.213-2.113-2.68-2.683-4.295 c-0.571-1.615-0.781-3.338-0.617-5.045c0.166-1.734,0.709-3.408,1.591-4.903c0.882-1.495,2.08-2.772,3.509-3.739 c1.872-1.261,4.07-1.934,6.317-1.934s4.445,0.673,6.317,1.934c1.424,0.964,2.62,2.235,3.502,3.723 c0.882,1.488,1.428,3.156,1.598,4.883c0.17,1.713-0.038,3.443-0.609,5.065C38.237,31.757,37.318,33.23,36.117,34.447z'/%3E%3Cellipse fill='%23" . $color . "' cx='28' cy='26' rx='10.5' ry='10.5'/%3E%3C/svg%3E%0A",//SHM_URLPATH . 'assets/img/default-marker.svg',
	);
	return $marker;
};


function shm_colour_brightness( $hex, $percent ) {
	// Work out if hash given
	$hash = '';
	if ( stristr( $hex, '#' ) ) {
		$hex = str_replace('#', '', $hex );
		$hash = '#';
	}
	/// HEX TO RGB
	$rgb = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
	//// CALCULATE
	for ($i = 0; $i < 3; $i++) {
		// See if brighter or darker
		if ($percent > 0) {
			// Lighter
			$rgb[$i] = round($rgb[$i] * $percent) + round(255 * (1 - $percent));
		} else {
			// Darker
			$positivePercent = $percent - ($percent * 2);
			$rgb[$i] = round($rgb[$i] * (1 - $positivePercent)); // round($rgb[$i] * (1-$positivePercent));
		}
		// In case rounding up causes us to go to 256
		if ($rgb[$i] > 255) {
			$rgb[$i] = 255;
		}
	}
	//// RBG to Hex
	$hex = '';
	for ($i = 0; $i < 3; $i++) {
		// Convert the decimal digit to hex
		$hexDigit = dechex($rgb[$i]);
		// Add a leading zero if necessary
		if (strlen($hexDigit) == 1) {
			$hexDigit = "0" . $hexDigit;
		}
		// Append to the hex string
		$hex .= $hexDigit;
	}
	return $hash . $hex;
}

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
