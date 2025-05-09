<?php
/**
 * Screen class
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Determines which Plugin Screen the User is on
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 1.0.0
 */
class Social_Post_Flow_Screen {

	/**
	 * Returns an array comprising of the Plugin Top Level Screen and Section
	 *
	 * For example:
	 * [
	 *  'screen' => 'settings',
	 *  'section' => 'page',
	 * ]
	 *
	 * Returns false if we're not on a Plugin screen
	 *
	 * @since   3.9.6
	 *
	 * @return  array   Screen and Section (if false, we're not on this Plugin's screens)
	 */
	public function get_current_screen() {

		// Assume we're not on a plugin screen.
		$result = array(
			'screen'  => false,
			'section' => false,
		);

		// Early detection of settings page so that early hooks e.g. init can detect if we're on the settings screen.
		if ( isset( $_REQUEST['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			if ( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) === 'social-post-flow-settings' ) { // phpcs:ignore WordPress.Security.NonceVerification
				return array(
					'screen'  => 'settings',
					'section' => ( isset( $_REQUEST['tab'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['tab'] ) ) : 'auth' ), // phpcs:ignore WordPress.Security.NonceVerification
				);
			}
		}

		// Bail if we can't determine this.
		if ( ! function_exists( 'get_current_screen' ) ) {
			return $result;
		}

		// Get screen.
		$screen = get_current_screen();

		// Get screen ID without Plugin Display Name.
		$screen_id = str_replace(
			array(
				'toplevel_page_',
				sanitize_title( 'social_post_flow_page_' ),
			),
			'',
			$screen->base
		);

		switch ( $screen_id ) {

			/**
			 * Post/Page/CPT WP_List_Table
			 */
			case 'edit':
				return array(
					'screen'  => 'post',
					'section' => 'wp_list_table',
				);

			/**
			 * Post/Page/CPT Add/Edit
			 */
			case 'post':
				return array(
					'screen'  => 'post',
					'section' => 'edit',
				);

			/**
			 * Settings
			 */
			case 'social-post-flow-settings':
				return array(
					'screen'  => 'settings',
					'section' => ( isset( $_REQUEST['tab'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['tab'] ) ) : 'auth' ), // phpcs:ignore WordPress.Security.NonceVerification
				);

			/**
			 * Bulk Publish
			 */
			case 'social-post-flow-bulk-publish':
				return array(
					'screen'  => 'bulk_publish',
					'section' => 'bulk_publish',
				);

			/**
			 * Log
			 */
			case 'social-post-flow-log':
				return array(
					'screen'  => 'log',
					'section' => 'log',
				);

			/**
			 * WordPress Screens
			 */
			case 'customize':
				return array(
					'screen'  => 'customize',
					'section' => 'customize',
				);

		}

		// If here, we couldn't determine the screen.
		return $result;

	}

}
