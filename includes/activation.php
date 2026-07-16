<?php
/**
 * Defines activation functions, which are run when the Plugin is activated.
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs the installation and update routines when the plugin is activated.
 *
 * @since   1.0.0
 *
 * @param   bool $network_wide   Is network wide activation.
 */
function social_post_flow_activate( $network_wide ) {

	// Initialise Plugin.
	$social_post_flow = Social_Post_Flow::get_instance();
	$social_post_flow->initialize();

	// Check if we are on a multisite install, activating network wide, or a single install.
	if ( ! is_multisite() || ! $network_wide ) {
		// Single Site activation.
		$social_post_flow->get_class( 'install' )->install();
	} else {
		// Multisite network wide activation.
		$sites = get_sites(
			array(
				'number' => 0,
			)
		);
		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			$social_post_flow->get_class( 'install' )->install();
			restore_current_blog();
		}
	}

}

/**
 * Runs the installation and update routines when the plugin is activated
 * on a WPMU site.
 *
 * @since   1.0.0
 *
 * @param   mixed $site_or_blog_id    WP_Site or Blog ID.
 */
function social_post_flow_activate_new_site( $site_or_blog_id ) {

	// Check if $site_or_blog_id is a WP_Site or a blog ID.
	if ( is_a( $site_or_blog_id, 'WP_Site' ) ) {
		$site_or_blog_id = $site_or_blog_id->blog_id;
	}

	// Initialise Plugin.
	$social_post_flow = Social_Post_Flow::get_instance();
	$social_post_flow->initialize();

	// Run installation routine.
	switch_to_blog( $site_or_blog_id );
	$social_post_flow->get_class( 'install' )->install();
	restore_current_blog();

}
