<?php
/**
 * Defines functions that are called by WordPress' Cron.
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Define the WP Cron function to send status updates via the API
 *
 * @since   1.0.0
 *
 * @param   int    $post_id    Post ID.
 * @param   string $action     Action.
 */
function social_post_flow_publish_cron( $post_id, $action ) {

	// Initialise Plugin.
	$social_post_flow = Social_Post_Flow::get_instance();
	$social_post_flow->initialize();

	// Call CRON Publish function.
	$social_post_flow->get_class( 'cron' )->publish( $post_id, $action );

	// Shutdown.
	unset( $social_post_flow );

}
add_action( 'social_post_flow_publish_cron', 'social_post_flow_publish_cron', 10, 2 );

/**
 * Define the WP Cron function to repost status updates via the API
 *
 * @since   1.0.0
 */
function social_post_flow_repost_cron() {

	// Initialise Plugin.
	$social_post_flow = Social_Post_Flow::get_instance();
	$social_post_flow->initialize();

	// Call CRON Repost function.
	$social_post_flow->get_class( 'cron' )->repost();

	// Shutdown.
	unset( $social_post_flow );

}
add_action( 'social_post_flow_repost_cron', 'social_post_flow_repost_cron' );

/**
 * Define the WP Cron function to perform the log cleanup
 *
 * @since   1.0.0
 */
function social_post_flow_log_cleanup_cron() {

	// Initialise Plugin.
	$social_post_flow = Social_Post_Flow::get_instance();
	$social_post_flow->initialize();

	// Call CRON Log Cleanup function.
	$social_post_flow->get_class( 'cron' )->log_cleanup();

	// Shutdown.
	unset( $social_post_flow );

}
add_action( 'social_post_flow_log_cleanup_cron', 'social_post_flow_log_cleanup_cron' );

/**
 * Define the WP Cron function to perform the Media Library cleanup
 * of Text to Image generations
 *
 * @since   1.0.0
 */
function social_post_flow_media_cleanup_cron() {

	// Initialise Plugin.
	$social_post_flow = Social_Post_Flow::get_instance();
	$social_post_flow->initialize();

	// Call Media Cleanup function.
	$social_post_flow->get_class( 'media_library' )->cleanup();

	// Shutdown.
	unset( $social_post_flow );

}
add_action( 'social_post_flow_media_cleanup_cron', 'social_post_flow_media_cleanup_cron' );
