<?php
/**
 * ShMapper Block - Map
 *
 * @package teplitsa
 */

function shm_block_map_attributes(){

	$maps = shm_get_map_posts();

	$map_id = 0;
	if ( shm_get_map_posts() ) {
		$map_obj = shm_get_map_posts();
		$map_id = $map_obj[0]->ID;
	}

	$attributes = array(
		'align'            => array(
			'type'    => 'string',
			'default' => '',
		),
		'className'        => array(
			'type'    => 'string',
			'default' => '',
		),
		'anchor'       => array(
			'type'    => 'string',
			'default' => '',
		),
		'mapId' => array(
			'type' => 'string',
			'default' => $map_id,
		),
		'preview'     => array(
			'type'    => 'boolean',
			'default' => false,
		),
		'mapType' => array(
			'type'    => 'string',
			'default' => 'classic',
		),
		'minHeight' => array(
			'type'    => 'string',
			'default' => '450px',
		),
		'isForm' => array(
			'type'    => 'boolean',
			'default' => false,
		),
		'formWidth' => array(
			'type'    => 'string',
			'default' => '',
		),
		'formAlign' => array(
			'type'    => 'string',
			'default' => 'left',
		),
		'formSpacing'  => array(
			'type'    => 'object',
			'default' => array(),
		),
	);

	return $attributes;

}

/**
 * Register Block Type Leyka Form
 */
register_block_type( 'shmapper/map', array(
	'render_callback' => 'shm_block_map_render_callback',
	'attributes'      => shm_block_map_attributes(),
) );

/**
 * Render Block Shmapper Form
 *
 * @param array $attr Block Attributes.
 */
function shm_block_map_render_callback( $attr, $content ) {

	$classes = ['block_class' => 'shm-block-map'];

	if ( isset( $attr['align'] ) && $attr['align'] ) {
		$classes['align'] = 'align' . $attr['align'];
	} else {
		$classes['align'] = 'alignnone';
	}

	if ( isset( $attr['className'] ) && $attr['className'] ) {
		$classes['class_name'] = $attr['className'];
	}

	$map_block_type = '';
	if ( isset( $attr['mapType'] ) && 'fullscreen' === $attr['mapType'] ) {
		$classes['map_type'] = 'is-map-type-fullscreen';
		$map_block_type = $attr['mapType'];
	}

	$min_height = isset( $attr['minHeight'] ) && $attr['minHeight'] ? $attr['minHeight'] : '';

	$form_width = isset( $attr['formWidth'] ) && $attr['formWidth'] ? $attr['formWidth'] : '';

	$form_spacing = isset( $attr['formSpacing'] ) && $attr['formSpacing'] ? $attr['formSpacing'] : array();

	$form_align = isset( $attr['formAlign'] ) && $attr['formAlign'] ? $attr['formAlign'] : 'left';

	$map_id = isset( $attr['mapId'] ) && $attr['mapId'] ? $attr['mapId'] : 0;

	$is_form = isset( $attr['isForm'] ) && $attr['isForm'] ? $attr['isForm'] : false;

	$is_editor = isset( $_GET['isEditor'] ) && $_GET['isEditor'] ? $_GET['isEditor'] : false;

	$is_legend = get_post_meta( $map_id, 'is_legend', true );

	if ( $is_legend ) {
		$classes['is_legend'] = 'is-map-legend';
	}

	$anchor = isset( $attr['anchor'] ) && $attr['anchor'] ? $attr['anchor'] : '';

	$args = array(
		'heigth'       => 450,
		'id'           => $map_id,
		'anchor'       => $anchor,
		'isForm'       => $is_form,
		'formWidth'    => $form_width,
		'formSpacing'  => $form_spacing,
		'formAlign'    => $form_align,
		'classes'      => $classes,
		'align'        => $attr['align'],
		'isblock'      => true,
		'isEditor'     => $is_editor,
		'mapBlockType' => $map_block_type,
	);

	if ( $min_height ) {
		$args['minheight'] = $min_height;
	}

	$html = shmMap($args);

	if ( true === $attr['preview'] ) {
		$html = '<img src="' . esc_url( SHM_URLPATH . 'assets/img/block-preview.jpg' ) . '" alt="" style="display:block;width:100%;height: auto;margin: 0 auto;">';
	}

	return $html;

}