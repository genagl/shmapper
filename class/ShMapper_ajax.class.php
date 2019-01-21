<?php

// Класс для записи в лог-файл тех исключений,
// которые не требуют моментальной реакции администратора
class ExceptionWriter extends Error
{
    public function Write()
    {
        // записываем содержимое ошибки в лог-файл
    }
}

class ShMapper_ajax
{
	static $instance;
	static function get_instance()
	{
		if(!static::$instance)
			static::$instance = new static;
		return static::$instance;
	}
	function __construct()
	{
		add_action('wp_ajax_nopriv_myajax',		array(__CLASS__, 'ajax_submit') );
		add_action('wp_ajax_myajax',			array(__CLASS__, 'ajax_submit') );
		add_action('wp_ajax_myajax-admin', 		array(__CLASS__, 'ajax_submit'));
		
		add_action('wp_ajax_nopriv_shm_set_req',	array(__CLASS__, 'shm_ajax3_submit') );
		add_action('wp_ajax_shm_set_req',			array(__CLASS__, 'shm_ajax3_submit') );
		add_action('wp_ajax_shm_set_req-admin', 	array(__CLASS__, 'shm_ajax3_submit'));
		
	}
	static function shm_ajax3_submit()
	{
		/**/
		$data = $_POST;
		$data['elem']	= explode(",", $data['elem']);
		if( ShMapper::$options['shm_settings_captcha'] )
		{
			require_once( SHM_REAL_PATH . "assets/recaptcha-php/recaptcha.class.php" );
			$reCaptcha = new ReCaptcha( ShMapper::$options['shm_captcha_secretKey'] );					
			$response = $reCaptcha->verifyResponse(
				$_SERVER["REMOTE_ADDR"],
				$data['cap']
			);
			switch( $response->success )
			{
				case(true):
					$res 	= ShMapperRequest::insert($data);
					$msg 	= ShMapper::$options['shm_succ_request_text'];
					break;
				default:
					$msg 	= ShMapper::$options['shm_error_request_text'] . " : " . $response->errorCodes->msg;
					break;
			}
			$grec = ShMapper_Assistants::shm_after_request_form("");
		}
		else
		{
			
			$res 	= ShMapperRequest::insert($data);
			$msg	= ShMapper::$options['shm_succ_request_text'];
		}
		//load image
		if( $res AND $res->id > 1 )		
		{
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			$overrides = array( 'test_form' => false );
			foreach( $_FILES as $file )
			{				
				$movefile = wp_handle_upload( $file, $overrides );
				if ( $movefile && empty($movefile['error']) ) 
				{
					$filename  		= $movefile['file'];
					$filetype 		= $movefile['type'];
					$filetype 		= wp_check_filetype( basename( $filename ), null );
					$url			= $movefile['url'];
					$parent_post_id = $res->id;
					$wp_upload_dir 	= wp_upload_dir();
					
					$attachment 	= [
						'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ), 
						'post_mime_type' => $filetype['type'],
						'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
						'post_content'   => '',
						'post_status'    => 'inherit'
					];
					// Вставляем запись в базу данных.
					$attach_id = wp_insert_attachment( $attachment, $filename, $parent_post_id );
					set_post_thumbnail($parent_post_id, $attach_id);
				} 
				else 
				{
					//echo "Возможны атаки при загрузке файла!\n";
				}
				
			}
		}
		$form = ShmForm::form( get_post_meta( $data['id'], "form_forms", true ), ShmMap::get_instance($data['id'])  );
		$answer = [
			'res'			=> $res,
			'data'			=> $data,
			"msg"			=> $msg,
			//"form"		=> $form,
			"grec"			=> $grec,
			//"attach_id"	=> $attach_id,
			'grecaptcha'	=> ShMapper::$options['shm_settings_captcha']
		];
		wp_die( json_encode( $answer ) );
	}
	static function ajax_submit()
	{
		try
		{
			static::myajax_submit();
		}
		catch(Error $ex)
		{
			$d = [	
				"Error",
				array(
					'msg'	=> $ex->getMessage (),
					'log'	=> $ex->getTrace ()
				  )
			];
			$d_obj		= json_encode( $d );				
			print $d_obj;
			wp_die();
		}
		wp_die();
	}
	static function myajax_submit()
	{
		global $wpdb;
		$nonce = $_POST['nonce'];
		if ( !wp_verify_nonce( $nonce, 'myajax-nonce' ) ) die ( $_POST['params'][0] );
		
		$params	= $_POST['params'];	
		$d		= array( $_POST['params'][0], array() );				
		switch($params[0])
		{				
			case "test":	
				$map_id = $params[1];
				$num = $params[2];
				$d = array(	
					$params[0],
					array( 
						"text"		=> 'testing',
					)
				);
				break;			
			case "shm_doubled":	
				$map_id = $params[1];
				$map	= ShmMap::get_instance( $map_id );
				$new_map = $map->doubled();
				$d = array(	
					$params[0],
					array( 
						"text"		=> 'shm_doubled',
					)
				);
				break;		
			case "shm_wnext":	
				$step	= (int)get_option("shm_wizard_step");
				$step++;
				if($step < count(ShMapper::get_wizzard_lst()))
				{
					$stepData 	= ShMapper::get_wizzard_lst()[$step];
					$messge		= __("Next step", SHMAPPER);
				}
				else
				{
					ShMapper::$options["wizzard"] = 0;
					ShMapper::update_options();
					$step = 0;
					$messge		= __("Congratulation! That's all!", SHMAPPER);
				}
				update_option("shm_wizard_step", $step);
				$d = array(	
					$params[0],
					array( 
						"href"		=> $stepData['href'],
						"msg"		=> $messge
					)
				);
				break;			
			case "shm_wclose":	
				ShMapper::$options["wizzard"] = 0;
				ShMapper::update_options();
				update_option("shm_wizard_step", 0);
				$d = array(	
					$params[0],
					array( 
						"msg"	=> __("Wizzard closed", SHMAPPER) ,
					)
				);
				break; 			
			case "shm_wrestart":	
				ShMapper::$options["wizzard"] = 1;
				ShMapper::update_options();
				update_option("shm_wizard_step", 0);
				$d = array(	
					$params[0],
					array( 
						"msg"	=> __("Wizzard restarted", SHMAPPER),
					)
				);
				break; 	
			case "shm_notify_req":	
				$req_id = $params[1];
				$req = ShMapperRequest::get_instance($req_id);
				$new_id = $req->notify();
				$d = array(	
					$params[0],
					array( 
						"text"		=> $req->get_notified_form(),
						"post_id"	=> $req_id,
						"newpointid"=> $new_id, 
						"msg"		=> __("Approve succesfully and insert new Map marker", SHMAPPER)
					)
				);
				break;		
			case "shm_trash_req":	
				$req_id = $params[1];
				$req = ShMapperRequest::get_instance($req_id);
				wp_trash_post( $req_id );
				$d = array(	
					$params[0],
					array( 
						"post_id"	=> $req_id,
						"msg"		=> __("Request put to Trash", SHMAPPER)
					)
				);
				break;		
			case "shm_add_before":
				$num = $params[1];
				$post_id = $params[2];
				$type_id = $params[3];				
				$d = array(	
					$params[0],
					array( 
						"text"		=> ShmForm::get_admin_element($num,["type" => $type_id]),
						"order"		=> $num,
						"type_id"	=> $type_id
					)
				);
				break;			
			case "shm_add_after":	
				$num = $params[1];
				$post_id = $params[2];
				$type_id = $params[3];						
				$d = array(	
					$params[0],
					array( 
						"text"		=> ShmForm::get_admin_element($num,["type" => $type_id]),
						"order"		=> $num,
						"type_id"	=> $type_id
					)
				);
				break;		
			case "shm_csv":	
				$map_id = $params[1];
				$map = ShmMap::get_instance($map_id);
				$link = $map->get_csv();
				$d = array(	
					$params[0],
					[ 
						"text"		=> $link,
						"name"		=> "map" //$map->get("post_title")
					]
				);
				break;		
			case "shm_set_req":	
				$data = $params[1];
				if( ShMapper::$options['shm_settings_captcha'] )
				{
					require_once( SHM_REAL_PATH . "assets/recaptcha-php/recaptcha.class.php" );
					$reCaptcha = new ReCaptcha( ShMapper::$options['shm_captcha_secretKey'] );					
					$response = $reCaptcha->verifyResponse(
						$_SERVER["REMOTE_ADDR"],
						$data['cap']
					);
					switch( $response->success )
					{
						case(true):
							$res 	= ShMapperRequest::insert($data);
							$msg 	= ShMapper::$options['shm_succ_request_text'];
							break;
						default:
							$msg 	= ShMapper::$options['shm_error_request_text'] . " : " . $response->errorCodes->msg;
							break;
					}
					$grec = ShMapper_Assistants::shm_after_request_form("");
					/**/
					//$msg = "msg: ". $data['cap'];
				}
				else
				{
					$res 	= ShMapperRequest::insert($data);
					$msg	= ShMapper::$options['shm_succ_request_text'];
				}
				
				$d = array(	
					$params[0],
					array( 
						"msg"	=> $msg,
						"res"	=> $res,
						//"grec"	=> $grec,
						//'grecaptcha' => ShMapper::$options['shm_settings_captcha']
					)
				);
				break;	
			case "shm_delete_map_hndl":		
				$data 		= $params[1];
				$id 		= $data["id"];
				$map 	= ShmMap::get_instance( $id );
				$res	= $map->shm_delete_map_hndl($data);
				$d = array(	
					$params[0],
					array( 
						"msg"		=> $res['message'],
						"res"		=> $res,
						"data"		=> $data,
						"id"		=> $id
					)
				);
				break;	
			case "shm_delete_map":	
				$id 	= $params[1];
				$href 	= $params[2];
				$map 	= ShmMap::get_instance( $id );
				$d = array(	
					$params[0],
					array( 
						"text"		=> [ 
							"title" 	=> sprintf(__("Delete %s", SHMAPPER), $map->get("post_title") ), 
							"content" 	=> $map->get_delete_form( $href ),
							"send" 		=> __("Delete"),
							"sendHandler" => "shm_delete_map_hand",
							"sendArgs" 	=> $id
						],
					)
				);
				break;
			case "shm_add_point_prepaire":	
				$map_id = $params[1][0];
				$x		= $params[1][1];
				$y		= $params[1][2];
				$ad		= $params[1][3];
				$d = array(	
					$params[0],
					array( 
						"text" => [
							'title' 	=> __("add Map Point", SHMAPPER),
							"content" 	=> ShmPoint::get_insert_form( $params[1] ),
							"send" 		=> __("Create"),
							"sendHandler" => "create_point"
						],
					)
				);
				break;		
			case "shm_create_map_point":
				$data = $params[1];
				$point = ShmPoint::insert($data);
				$type = get_term($data['type'], SHM_POINT_TYPE);
				$pointdata = [
					"post_title"	=> $data["post_title"],
					"post_content"	=> $data["post_content"],
					"latitude"		=> $data["latitude"],
					"longitude"		=> $data["longitude"],
					"location"		=> $data["location"],
					"color"			=> get_term_meta($type->term_id, "color", true),
					"height"		=> get_term_meta($type->term_id, "height", true),
					"icon"			=> ShMapPointType::get_icon_src($type->term_id)[0],
					"term_id"		=> $data['type'],
					"mapid"			=> "ShmMap".$data['map_id'].$data['map_id']
				];
				$d = array(	
					$params[0],
					array( 
						"id"		=> $point->id,
						"data"		=> $pointdata,
						"msg"		=> 'success',
					)
				);
				break;
			case "shm_voc":	
				$voc = $params[1];
				ShMapper::$options[$voc] = $params[2];
				ShMapper::update_options();
				$d = array(	
					$params[0],
					array( 
						"msg"	=> __("Change Vocabulaty: ", SHMAPPER) . $voc.": ".ShMapper::$options[$voc],
					)
				);
				break; 
			case "map_api":	
				ShMapper::$options['map_api'] = $params[1];
				ShMapper::update_options();
				$d = array(	
					$params[0],
					array( 
						"msg"	=> $params[1] == 1 ? "Yandex Map API" : "OpenStreet Map API",
					)
				);
				break; 
			case "shm_map_is_crowdsourced":	
				ShMapper::$options['shm_map_is_crowdsourced'] = $params[1];
				ShMapper::update_options();
				$d = array(	
					$params[0],
					array( 
						"msg"	=> __($params[1] ? "Users can add Placemarks" : "Users don't can add Placemarks", SHMAPPER),
					)
				);
				break; 
			case "shm_map_marker_premoderation":	
				ShMapper::$options['shm_map_marker_premoderation'] = $params[1];
				ShMapper::update_options();
				$d = array(	
					$params[0],
					array( 
						"msg"	=>  __($params[1] ?"Pre-moderation on" : "Pre-moderation off", SHMAPPER),
					)
				);
				break; 
			case "shm_settings_captcha":	
				ShMapper::$options['shm_settings_captcha'] = $params[1];
				ShMapper::update_options();
				$d = array(	
					$params[0],
					array( 
						"msg"	=> __($params[1] ? "captha added" : "captcha removed", SHMAPPER),
					)
				);
				break; 
			case "shm_captcha_siteKey":	
				ShMapper::$options['shm_captcha_siteKey'] = $params[1];
				ShMapper::update_options();
				$d = array(	
					$params[0],
					array( 
						"msg"	=> __( "Set key" , SHMAPPER),
					)
				);
				break; 
			case "shm_captcha_secretKey":	
				ShMapper::$options['shm_captcha_secretKey'] = $params[1];
				ShMapper::update_options();
				$d = array(	
					$params[0],
					array( 
						"msg"	=> __( "Set key" , SHMAPPER),
					)
				);
				break; 
			default:
				do_action("shm_ajax_submit", $params);
				break;
		}
		$d_obj		= json_encode(apply_filters("shm_ajax_data", $d, $params));				
		print $d_obj;
		wp_die();
	}
}