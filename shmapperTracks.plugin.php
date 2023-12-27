<?php
/**
 * ShMapper Tracks
 *
 * Description: Add-on for Shmapper by Teplitza plugin. Added paths, tracks and routes functionality
 * Author: KB of Protopia Home
 * Author URI: https://kb.protopia-home.ru
 */

// Paths.
define( 'SHMTRACKS_REAL_PATH', WP_PLUGIN_DIR . '/' . plugin_basename(dirname(__FILE__) ) . '/' );
define( 'SHMAPPER_TRACKS', 'shmapper-by-teplitsa' );
define( 'SHM_TRACK_TYPE', 'shmapper_track_type' );
define( 'SHMAPPER_TRACKS_TRACK', 'shmapper_track' );
define( 'SHMAPPER_TRACKS_POINT', 'shmapper_track_point' );
define( 'SHMAPPER_TRACKS_DRAW', 'shmapper_track_draw' );

require_once SHM_REAL_PATH . 'class/ShMapperTracks.class.php';
require_once SHM_REAL_PATH . 'class/ShMapperTracksAjax.class.php'; 
require_once SHM_REAL_PATH . 'class/ShMaperTrack.class.php'; 
require_once SHM_REAL_PATH . 'class/ShMapperTracksPoint.class.php'; 
require_once SHM_REAL_PATH . 'class/ShMapTrackType.class.php'; 

register_activation_hook( __FILE__, array( 'ShMapperTracks', 'activate' ) );

if ( function_exists( 'register_deactivation_hook' ) ) 
{
	register_deactivation_hook(__FILE__, array( 'ShMapperTracks', 'deactivate' ) );
}

/** Shamapper-tracks init */
function init_shmapperTracks() 
{
	ShMapperTracks::get_instance(); 
	ShMapperTracksAjax::init();
	ShMapTrackType::init();
	ShMaperTrack::init(); 
	ShMapperTracksPoint::init();  
}
add_action( 'init', 'init_shmapperTracks', 1 );
