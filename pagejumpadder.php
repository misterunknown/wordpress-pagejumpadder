<?php
/**
 * @package PageJumpAdder
 * @version 1.0
 */
/*
<?php
/*
Plugin Name: PageJumpAdder
Plugin URI:  https://github.com/misterunknown/wordpress-pagejumpadder
Description: This plugin automatically adds the id attribute to headlines in your posts and pages, so you can easily link to different sections on a site.
Version:     1.1
Author:      Marco Dickert
Author URI:  https://misterunknown.de
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages/
Text Domain: pagejumpadder
*/

// prevent direct invocation
defined( 'ABSPATH' ) or die();

/////////////////////////////////////////////////
// initialization
/////////////////////////////////////////////////

/*
   This function loads the textdomain for the current language
*/
function pagejumpadder_load_plugin_textdomain() {
    load_plugin_textdomain( 'pagejumpadder', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}

// register action to load languages
add_action( 'plugins_loaded', 'pagejumpadder_load_plugin_textdomain' );

/////////////////////////////////////////////////
// add page jumps for new posts
/////////////////////////////////////////////////

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
function add_page_jumps( $this_post, $is_post = false ) {
	// check, if $this_post is an ID or an actual post
	if ( ! $is_post ) {
		// are we a revision? if yes, we change the parent post
		if ( $parentid = wp_is_post_revision( $this_post ) ) {
			$this_post = $parentid;
		}

		// this gets the post object
		$this_post = get_post( $this_post );
	}

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

/////////////////////////////////////////////////
// administration area
/////////////////////////////////////////////////

/*
   This function creates the entry in the settings menu
*/
function add_page_jumps_menu() {
	add_options_page( 'Add Page Jumps', 'PageJumpAdder', 'edit_posts', 'add-page-jumps', 'add_page_jumps_options' );
}

/*
   This function creates the options page and does the work
*/
function add_page_jumps_options() {
	if ( !current_user_can( 'edit_posts' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	// get users posts
	$args = array(
			'numberposts'      => -1,
			'post_type'        => 'any',
			'post_status'      => 'publish',
			'suppress_filters' => true 	
			);
	$all_posts = get_posts( $args );

	// collect editable posts
	$editable_posts = array();
	while( $current_post = array_pop( $all_posts ) ) {
		if ( current_user_can( 'edit_post', $current_post->ID ) ) {
			array_push( $editable_posts, $current_post );
		}
	}

	echo '<div class="wrap">';
	echo '<h1>' . __( 'Page Jump Adder', 'pagejumpadder' ) . '</h1>';

	// shall we edit these posts?
	if( isset( $_POST['pagejumpadder'] ) && $_POST['pagejumpadder'] == "add_all_jumps" ) {
		foreach( $editable_posts as $post ) {
			add_page_jumps( $post, true );
		}
		echo '<p>' . __( 'All posts were successfully edited.', 'pagejumpadder' ) . '</p>';
	} else {
		// otherwise show the options page
		echo '<p>' . sprintf( __( 'You can edit %d posts. Do you want to add page jumps to all of them?', 'pagejumpadder' ), count( $editable_posts ) ) . '</p>';
		echo '<form action="" method="post">';
		echo '<button class="button button-primary button-large" type="submit" name="pagejumpadder" value="add_all_jumps">' . __( 'Yes, add page jumps to all posts!', 'pagejumpadder' ) . '</button>';
		echo '</form>';
	}

	echo '</div>';
}

// register our menu
add_action( 'admin_menu', 'add_page_jumps_menu' );

?>
