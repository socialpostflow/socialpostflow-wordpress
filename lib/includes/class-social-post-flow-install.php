<?php
/**
 * Install class.
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Runs any steps required on plugin activation and upgrade.
 *
 * @package  Social_Post_Flow
 * @author   WP Zinc
 * @version  1.0.0
 */
class Social_Post_Flow_Install {

	/**
	 * Holds the base class object.
	 *
	 * @since   1.0.0
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   1.0.0
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct() {

		

	}

	/**
	 * Runs installation routines for first time users
	 *
	 * @since   1.0.0
	 */
	public function install() {

		// Enable logging by default.
		social_post_flow()->get_class( 'settings' )->update_option(
			'log',
			array(
				'enabled'          => 1,
				'display_on_posts' => 1,
				'preserve_days'    => 30,
				'log_level'        => array(
					'success',
					'test',
					'pending',
					'warning',
					'error',
				),
			)
		);

		// Create logging database table.
		social_post_flow()->get_class( 'log' )->activate();

		// Reschedule the cron events.
		social_post_flow()->get_class( 'cron' )->schedule_log_cleanup_event();
		social_post_flow()->get_class( 'cron' )->schedule_media_cleanup_event();
		social_post_flow()->get_class( 'cron' )->schedule_repost_event();

		// Bail if settings already exist.
		$settings = social_post_flow()->get_class( 'settings' )->get_settings( 'post' );
		if ( $settings !== false ) {
			return;
		}

		// Get default installation settings.
		$settings = social_post_flow()->get_class( 'settings' )->default_installation_settings( 'post' );
		social_post_flow()->get_class( 'settings' )->update_settings( 'post', $settings );

	}

	/**
	 * Runs uninstallation routines
	 *
	 * @since   1.0.0
	 */
	public function uninstall() {

		// Unschedule any CRON events.
		social_post_flow()->get_class( 'cron' )->unschedule_log_cleanup_event();
		social_post_flow()->get_class( 'cron' )->unschedule_media_cleanup_event();
		social_post_flow()->get_class( 'cron' )->unschedule_repost_event();

	}

}
