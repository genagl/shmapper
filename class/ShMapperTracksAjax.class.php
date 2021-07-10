<?php 

class ShMapperTracksAjax
{
	static function init()
	{	
		add_filter( "shm_ajax_data",	 			[__CLASS__, "shm_ajax_data"], 10, 2);
	} 
	// ajax
	
	static function shm_ajax_data($d, $params)
	{
		$action = sanitize_text_field($params[0]);
		switch($action)
		{				
			case "shm_get_map_tracks":	
				$map_id = (int)$params[1]["map_id"];
				$tracks = ShMaperTrack::get_all([ SHM_MAP => $map_id ]);
				$tr 	= [];
				foreach($tracks as $track)
				{
					$type = get_post_meta( $track->ID, SHM_TRACK_TYPE, true);
					$color = get_term_meta( $type, 'color', true);

					// Tracker type width.
					$tracker_width = 4;
					$width         = get_term_meta( $type, 'width', true );
					if ( $width ) {
						$tracker_width = $width;
					}

					$typeTrack = get_term($type, SHM_TRACK_TYPE);
					
					$points = ShMapperTracksPoint::get_all([ SHMAPPER_TRACKS_TRACK => $track->ID ]);
					$markers = [];
					foreach($points as $point) 
					{
						$shtp 		= ShMapperTracksPoint::get_instance($point);
						$term_id 	= $shtp->get_meta(SHM_POINT_TYPE);
						$typeT		= get_term($term_id, SHM_POINT_TYPE);
						$markers[] 	= [
							'latitude'		=> $shtp->get_meta("latitude"),
							'longitude'		=> $shtp->get_meta("longitude"),
							'post_title'	=> $shtp->get("post_title"),
							'post_content'	=> apply_filters("the_content", $shtp->get("post_content")), 
							"id"			=> $shtp->id,
							SHM_POINT_TYPE	=> $term_id,
							"shm_point_type_name" => $typeT->name ? $typeT->name: "",
							"color"			=> get_term_meta( $term_id, "color", true),
							"icon"			=> ShMapPointType::get_icon_src( $term_id )[0],
							"width"			=> get_term_meta( $term_id, "width", true ),
							"height"		=> get_term_meta( $term_id, "height", true )
						];
					}
					
					$tr[] = [
						"post_title"	=> $track->post_title,
						"post_content"	=> apply_filters("the_content", $track->post_content),
						"color"			=> $color ? $color : "#0066ff",
						"width"         => $tracker_width,
						SHM_TRACK_TYPE	=> $typeTrack->term_id,
						"track_id"		=> $track->ID,
						"shm_track_type_name" => $typeTrack->name ? $typeTrack->name : "",
						"term_id"		=> $type, 
						"track"			=> json_decode(get_post_meta($track->ID, "track", true)),
						"markers"		=> $markers
					];
				}
				$d = [	
					$action,
					[
						"tracks"	=> $tr,
						"map_id"	=> $map_id,
						"uniq"		=> $params[1]['uniq']
					]
				];
				break;	
			case "shm_add_track_point":	 
				
				$track_id 	= $params[1]['track_id']; 
				$type_id	= $params[1]['shm_type_id'];
				$geometry 	= $params[1]['geometry'];
				$track_point = ShMapperTracksPoint::insert([
					"post_title" 			=> __("Point", SHMAPPER_TRACKS),
					"post_content"			=> "",
					"latitude"				=> ( float )( (int)( $geometry[0] * 100000 ) ) / 100000,
					"longitude"				=> ( float )( (int)( $geometry[1] * 100000 ) ) / 100000,
					SHMAPPER_TRACKS_TRACK	=> $track_id,
					SHM_POINT_TYPE			=> $type_id
				]);
				$d = [	
					$action,
					[
						"track_id"	=> $track_id, 
						"type_id"	=> $type_id, 
						"geometry"	=> $geometry,
						"track_point"=> [
							"id"					=> $track_point->id,
							"latitude"				=> $track_point->get_meta("latitude"),
							"longitude"				=> $track_point->get_meta("longitude"),
							"post_title" 			=> $track_point->get("post_title"),
							"post_content"			=> $track_point->get("post_content"),
							"shm_clr"				=> get_term_meta($type_id, "color", true)
						]
					]
				];
				break;
			case "shm_track_new":
				$map_id = (int)$params[1];
				$map	= ShmMap::get_instance( $map_id );
				if($map->get_meta("is_form"))
				{
					$form_forms = $map->get_meta("form_forms");
					foreach($form_forms as $form)
					{
						if($form['type'] == SHMAPPER_MARK_TYPE_ID)
						{
							$fo = ShMapPointType::get_ganre_swicher(
								$form, 
								$map,
								false, 
								["icon_class" => "shm-type-icon-1", "container_class" => 'shm-form-placemarks shm-padding-0' ] 
							);
							break;
						}
					}
				}
				else
				{
					$fo = ShmForm::getTypeSwitcher([ "placemarks" => "" ], $map, false, [ "icon_class" => "shm-type-icon1" ] );
				}
				$d = [	
					$action,
					[ 
						"map_id"	=> $map_id,
						"form"		=> $fo
					]
				];
				break;
			case "shm_track_vertex":
				/*
					$params[1] - map data 
					$params[2] - vertex array data
						0 - lat
						1 - lon
						2 - { title, content, type }
				*/
				$map_id = (int)$params[1]["map_id"];
				$map	= ShmMap::get_instance( $map_id );
				if($map->get_meta("is_form"))
				{
					$form_forms = $map->get_meta("form_forms");
					foreach($form_forms as $form)
					{
						if($form['type'] == SHMAPPER_MARK_TYPE_ID)
						{
							$fo = ShMapPointType::get_ganre_swicher(
								[
									"row_class" => "shm-m-0", 
									'prefix' 	=> 'vertex_type' ,
									"selected"	=> $params[2][2]['type']
								], 
								'radio',
								'stroke-large' 
							);
							break;
						}
					}
				}
				else
				{
					$fo = ShmForm::getTypeSwitcher([ "placemarks" => "" ], $map, false, [ "icon_class" => "shm-type-icon1" ] );
				}
				$d = [	
					$action,
					[ 
						"map_id"	=> $map_id,
						"vertex"	=> $params[2],
						"selected"	=> $params[2][2]['type'],
						"form"		=> $fo
					]
				];
				break;
			case "shm_chande_track_point":
				$point = ShMapperTracksPoint::get_instance( $params[1]["marker_id"] );
				ShMapperTracksPoint::update(
					[
						"post_title" 			=> $params[1]['post_title'],
						"post_content"			=> $params[1]['post_content'],
						"coordinates"			=> [
							( float )( (int)( $params[1]['coordinates'][0] * 1000000000 ) ) / 1000000000,
							( float )( (int)( $params[1]['coordinates'][1] * 1000000000 ) ) / 1000000000
						],
						"latitude"				=> ( float )( (int)( $params[1]['coordinates'][0] * 1000000000 ) ) / 1000000000,
						"longitude"				=> ( float )( (int)( $params[1]['coordinates'][1] * 1000000000 ) ) / 1000000000,
						//SHM_TRACK_TYPE		=> $params[1]['shm_type_id']
					],
					$params[1]["marker_id"]
				);
				$d = [	
					$action,
					[ 
						"params"		=> $params[1],
						"coordinates"	=> $point->get_meta("coordinates"),
						"latitude"		=> $point->get_meta("latitude"),
						"longitude"		=> $point->get_meta("longitude")
						
					]
				];
				break;
			case "shm_remove_track_point": 
				ShMapperTracksPoint::delete( $params[1] );
				$d = [	
					$action,
					[ 
						"msg"			=> __( "Success removed tracks Marker", SHMAPPER_TRACKS )
						
					]
				];
			case "shm-trac-dnld-gpx":
				$track = ShMaperTrack::get_instance($params[1]);
				
				$d = [	
					$action,
					[ 
						"text"			=> $track->track_to_xml(),
						"name"			=> $track->get("post_title")						
					]
				];
				break;
		}
		return $d;
	}
}