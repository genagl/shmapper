<?php
/**
 * ShMapper Blocks
 *
 * @package teplitsa
 */
function shm_register_gutenberg_blocks() {
	require_once SHM_REAL_PATH . 'inc/editor/blocks/block-map.php';
}
add_action( 'init', 'shm_register_gutenberg_blocks');
