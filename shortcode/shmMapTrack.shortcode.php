<?php

function shmMapTrack($args)
{	 
	$args = apply_filters(
		"shm_track_shortcode_args",
		shortcode_atts(
			[
				"id" 			=> -1,
				"height"		=> 420,
				"show_markers"	=> true,
				"no_title" => 0,
				"no_description" => 0,
				"no_map" => 0,
			], 
			$args, 
			'shmMapTrack' 
		),
		$args
	);  
	$id				= $args['id'];
	$track			= ShMaperTrack::get_instance( $args[ 'id' ] ); 
	$html			= $args["no_title"] ? "" : "<div class='shm-track-title shm-title'>" . $track->get("post_title") . "</div>";
	if (!$args["no_map"]) {
		$html			.= $track->draw($args);
		$html 			= apply_filters( "shm_after_map_front_track", $html, $args );
	}
	if (!$args["no_description"]) {
		$html			.= "<div class='shmp-track-content' track-id='$id'>".
			$track->get("post_content") .
		"</div>";
		$html 			= apply_filters( "shm_final_front_track", $html, $args );
	}
	return $html;
}