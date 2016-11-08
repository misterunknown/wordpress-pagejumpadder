<?php
/**
 * @package PageJumpAdder
 * @version 1.0
 */
/*
Plugin Name: PageJumpAdder
Plugin URI: https://github.com/misterunknown/wordpress-pagejumpadder
Description: This plugin automatically adds the id attribute to headlines in your posts and pages, so you can easily link to different sections on a site.
Author: Marco Dickert
Version: 1.0
Author URI: https://misterunknown.de
*/

// prevent direct invocation
defined( 'ABSPATH' ) or die();

/*
   This function prepares the replacement
*/
function add_page_jump_callback( $match ) {
	// prepare the replacement. we don't want spaces or quotes in our id
	return "<h" . $match[1] . " id=\"" . str_replace( array(' ', '"', "'"), "-", $match[2] ) . "\">" . $match[2] . "</h";
}

/*
   This function gets the post content and adds the id attribute
*/
function add_page_jumps( $postid ) {
	// are we a revision? if yes, we change the parent post
	if ( $parentid = wp_is_post_revision( $postid ) ) {
		$postid = $parentid;
	}

	// this gets the post object
	$this_post = get_post( $postid );

	// replace the content
	$this_post->post_content = preg_replace_callback( ",<h([1-6])>(.*?)</h,", 'add_page_jump_callback', $this_post->post_content );

	// disable our action to prevent an infinite loop
	remove_action( 'save_post', 'add_page_jumps' );

	// update the post
	wp_update_post( $this_post );

	// reenable our action
	add_action( 'save_post', 'add_page_jumps' );
}

// register our action
add_action( 'save_post', 'add_page_jumps' );

?>
