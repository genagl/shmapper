<?php
/**
 * ShMapper
 *
 * @package teplitsa
 */

function shmMap($args)
{
	/**/
	$args = shortcode_atts( array(
		'heigth'   => 450,
		"id"       => -1,
		"map"      => false,
		"form"     => false,
		"uniq"     => false,
	), $args, 'shmMap' );

	$id				= $args['id'];
	$args['uniq']	= $args['uniq'] ? $args['uniq'] : substr( MD5(rand(0, 100000000)), 0, 8 );
	$uniq			= $args['uniq'];
	$map 			= ShmMap::get_instance($args['id']);
	if(!$map->is_enabled() || $map->get("post_type") !== SHM_MAP)
	{
		return __("No map on ID ", SHMAPPER) . $args['id'];
	}
	$map_enb	= $args["map"]  || ( !$args["map"] && !$args["form"]) ? 1 : 0;
	$form_enb	= $args["form"] || ( !$args["map"] && !$args["form"]) ? 1 : 0;

	$html = '';

	$is_title = ( $map->get_meta( 'is_title' ) !== '' ) ? $map->get_meta( 'is_title' ) : '1';

	if ( $is_title ) {
		$html .= '<div class="shm-title-6 shm-map-title">' . esc_html( $map->get( 'post_title' ) ) . '</div>';
	}

	if($map_enb)
	{
		$html 	.= $map->draw($args);
	}
	if( $form_enb && $map->get_meta("is_form") && !ShMapper::$options['shm_map_is_crowdsourced'])
	{
		$form_title = $map->get_meta( 'form_title' );
		if ( $form_title ) {
			$form_title = '<div class="shm-form-title">' . esc_html( $form_title ) . '</div>';
		}
		$form_forms = $map->get_meta( 'form_forms' );
		$html .= '
		<div class="shm-form-container">
			<form class="shm-form-request" id="form' . esc_attr( $id ) . '" form_id="ShmMap' . esc_attr( $id . $uniq ) . '" map_id="' . esc_attr( $id ) . '">
				' . $form_title . '
				<div id="form_forms">' . ShmForm::form( $form_forms, $map ) . '</div>
				<div class="shm-form-element">
					<input type="submit" class="shm-form-submit shm-request" value="' . esc_attr__( 'Send request', SHMAPPER ) . '">
				</div>
			</form>
		</div>';
	}

	$html = '<div class="shm-map-block">' . $html . '</div>';

	$html = apply_filters("shm_final_after_front_map", $html, $args);
	return $html;
}
