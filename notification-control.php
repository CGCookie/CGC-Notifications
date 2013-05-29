<?php
/**
 * Plugin Name: CGC Notifications
 * Description: Notification actions from theme moved to plugin
 */
/**********************************************
* This file takes care of marking notices
* as read for users.
* Notice IDs are checked against the ID of the
* broadcasted post on CGC, then added to
* the user's meta.
**********************************************/

function cgc_notice_register_post_type() {

	$announcement_labels = array(
		'name' => _x( 'Announce', 'post type general name' ), // Tip: _x('') is used for localization
		'singular_name' => _x( 'Announcement', 'post type singular name' ),
		'add_new' => _x( 'Add New', 'Announcement' ),
		'add_new_item' => __( 'Add New Announcement' ),
		'edit_item' => __( 'Edit Announcement' ),
		'new_item' => __( 'New Announcement' ),
		'view_item' => __( 'View Announcement' ),
		'search_items' => __( 'Search Announcements' ),
		'not_found' =>  __( 'No Announcements found' ),
		'not_found_in_trash' => __( 'No Announcements found in Trash' ),
		'parent_item_colon' => ''
	);

	$annoucement_args = array(
		'labels' => $announcement_labels,
		'singular_label' => __( 'Announcement' ),
		'public' => true,
		'show_ui' => true,
		'capability_type' => 'post',
		'hierarchical' => false,
		'exclude_from_search' => true,
		'rewrite' => array( 'slug' => 'announcements' ),
		'supports' => array( 'title', 'editor' ),
	);
	register_post_type( 'notices', $annoucement_args );

}
add_action( 'init', 'cgc_notice_register_post_type' );

function cgc_check_notice_is_read( $post_id, $user_id ) {
	// this line was just for testing purposes
	//delete_user_meta(cgc_notice_get_user_id(), 'cgc_notice_posts');
	$user_meta = cgc_notice_get_user_meta( $user_id );
	$cgc_main_post_id = cgc_notice_get_main_post_id( $post_id );
	if ( $user_meta ) :
		$read_post_ids = explode( ',', $user_meta );
	foreach ( $read_post_ids as $read_post ) {
		if ( $read_post == $cgc_main_post_id ) {
			return true;
		}
	}
	endif;
	return false;
}

function cgc_notice_get_user_id() {
	global $current_user;
	get_currentuserinfo();
	return $current_user->ID;
}

function cgc_notice_add_to_usermeta( $post_id ) {
	$user_id = cgc_notice_get_user_id();
	$cgcn_read = cgc_notice_get_user_meta( $user_id );
	$cgcn_read .= ',' . intval( $post_id );
	if ( substr( $cgcn_read, 0, 1 ) == ',' ) {
		$cgcn_read = substr( $cgcn_read, 1, strlen( $cgcn_read )-1 );
	}
	cgc_notice_update_user_meta( $cgcn_read, $user_id );
}

function cgc_notice_update_user_meta( $arr, $user_id ) {
	return update_user_meta( $user_id, 'cgc_notice_posts', $arr );
}
function cgc_notice_get_user_meta( $user_id ) {
	return get_user_meta( $user_id, 'cgc_notice_posts', true );
}

function cgc_notice_get_main_post_id( $post_id ) {
	global $blog_id;

	$cgc_post_id = $post_id;

	if ( $blog_id != 1 ) {
		$post_title = get_the_title( $post_id );
		switch_to_blog( 1 );
		$cgc_notice = cgc_get_post_id_by_name( $post_title ) ;
		if ( ! empty( $cgc_notice ) ) {
			$cgc_post_id = $cgc_notice->ID;
		}
		restore_current_blog();
	}

	return $cgc_post_id;
}

function cgc_get_post_id_by_name( $page_title, $output = OBJECT ) {
	global $wpdb;
	$post = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type='notices'", $page_title ) );
	if ( $post )
		return get_post( $post, $output );

	return null;
}
function cgc_notice_mark_as_read() {
	if ( isset( $_POST["notice_read"] ) ) {
		$notice_id = intval( $_POST["notice_read"] );
		$cgc_notice_id = cgc_notice_get_main_post_id( $notice_id );
		$marked_as_read = cgc_notice_add_to_usermeta( $cgc_notice_id );
		die();
	}
}
add_action( 'wp_ajax_nopriv_mark_as_read', 'cgc_notice_mark_as_read' );
add_action( 'wp_ajax_mark_as_read', 'cgc_notice_mark_as_read' );
