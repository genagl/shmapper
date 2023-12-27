<?php
/**
 * ShMapper Gutenberg Blocks Assets
 *
 * @package teplitsa
 */

/**
 * Enqueue scripts for editor
 */
function shm_enqueue_block_editor_assets() {

	$dependencies = [
		'wp-blocks',
		'wp-plugins',
		'wp-element',
		'wp-components',
		'wp-editor',
		'wp-block-editor',
		'wp-edit-post',
		'wp-data',
		'wp-core-data',
		'wp-server-side-render',
		'wp-i18n'
	];

	wp_enqueue_script( 'shmapper-blocks', SHM_URLPATH . 'assets/js/blocks.js', $dependencies, filemtime( SHM_REAL_PATH . 'assets/js/blocks.js' ) );

	wp_enqueue_style( 'shmapper-gutenberg', SHM_URLPATH . 'assets/css/gutenberg.css', array(), filemtime( SHM_REAL_PATH . 'assets/css/gutenberg.css' ) );

	$shmBlock = array();

	$shmBlock['getMaps'] = shm_get_map_posts();
	$shmBlock['getMapKeys'] = shm_get_map_meta_keys();

	wp_localize_script(
		'shmapper-blocks',
		'shmBlock',
		$shmBlock
	);

	wp_enqueue_style( 'ShMapper', SHM_URLPATH . 'assets/css/ShMapper.css', array(), SHMAPPER_VERSION );

	wp_set_script_translations( 'shmapper-blocks', 'shmapper-by-teplitsa' );

}
add_action('enqueue_block_editor_assets', 'shm_enqueue_block_editor_assets');
