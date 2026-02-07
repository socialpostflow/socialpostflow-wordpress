<?php
/**
 * User access class.
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
 */

/**
 * Stores a flag indicating whether the user has access to Social Post Flow,
 * and displays a notice if not.
 *
 * @package   Social_Post_Flow
 * @author    Social Post Flow
 */
class Social_Post_Flow_User_Access {

	/**
	 * Settings key name.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	public $settings_name = 'social-post-flow-user-no-access';

	/**
	 * Constructor.
	 *
	 * @since   1.1.7
	 */
	public function __construct() {

		add_action( 'after_plugin_row_social-post-flow/social-post-flow.php', array( $this, 'add_user_no_access_notice_to_plugins_screen' ), 10, 1 );
		add_filter( 'social_post_flow_notices_get_notices', array( $this, 'add_user_no_access_notice' ) );

	}

	/**
	 * Adds a notice in the Plugins screen if the user does not have access to Social Post Flow,
	 * linking them to the checkout page or the billing page.
	 *
	 * @since   1.2.1
	 */
	public function add_user_no_access_notice_to_plugins_screen() {

		// Bail if the user has access.
		if ( ! $this->user_has_no_access() ) {
			return;
		}

		// Output the notice.
		printf(
			'<tr class="plugin-update-tr active" id="social-post-flow" data-slug="social-post-flow" data-plugin="social-post-flow/social-post-flow.php"><td colspan="4" class="plugin-update colspanchange"><div class="update-message notice inline notice-error notice-alt"><p>%s</p></div></td></tr>',
			wp_kses_post( $this->get_notice() )
		);

	}

	/**
	 * Adds a notice in the WordPress Administration if the user does not have access to Social Post Flow,
	 * linking them to the checkout page or the billing page.
	 *
	 * @since   1.1.7
	 *
	 * @param   array $notices    Notices.
	 * @return  array   Notices.
	 */
	public function add_user_no_access_notice( $notices ) {

		// Bail if the user has access.
		if ( ! $this->user_has_no_access() ) {
			return $notices;
		}

		// Add the notice.
		$notices['error'][] = $this->get_notice();

		// Return notices.
		return $notices;

	}

	/**
	 * Updates the user access flag.
	 *
	 * @since   1.1.7
	 */
	public function update_user_access_flag() {

		// If the Plugin isn't connected to the API, bail.
		if ( ! social_post_flow()->get_class( 'validation' )->api_connected() ) {
			return;
		}

		// Setup API.
		social_post_flow()->get_class( 'api' )->set_tokens( social_post_flow()->get_class( 'settings' )->get_access_token() );

		// Get user details.
		$user = social_post_flow()->get_class( 'api' )->user();

		// Bail if an error occurred.
		if ( is_wp_error( $user ) ) {
			return;
		}

		// Update whether the user has access.
		if ( ! (bool) $user['has_access'] ) {
			$this->create_user_no_access_flag();
		} else {
			$this->delete_user_no_access_flag();
		}

	}

	/**
	 * Creates the flag denoting the user does not have access to Social Post Flow
	 * i.e. their trial ended and no paid plan is active.
	 *
	 * @since   1.1.7
	 */
	public function create_user_no_access_flag() {

		update_option( $this->settings_name, true );

	}

	/**
	 * Checks if the user does not have access to Social Post Flow
	 * i.e. their trial ended and no paid plan is active.
	 *
	 * @since   1.1.7
	 *
	 * @return  bool
	 */
	public function user_has_no_access() {

		return (bool) get_option( $this->settings_name );

	}

	/**
	 * Deletes the flag denoting the user does not have access to Social Post Flow
	 * i.e. they are now in a trial or a paid plan.
	 *
	 * @since   1.1.7
	 */
	public function delete_user_no_access_flag() {

		delete_option( $this->settings_name );

	}

	/**
	 * Returns the notice text.
	 *
	 * @since   1.2.2
	 *
	 * @return  string
	 */
	private function get_notice() {

		return sprintf(
			'<strong>%s:</strong> %s <a href="%s" target="_blank">%s</a> %s<br /><a href="%s">%s</a>',
			__( 'Social Post Flow', 'social-post-flow' ),
			__( 'Your trial has ended. A paid subscription is required for continued use.', 'social-post-flow' ),
			social_post_flow()->get_class( 'api' )->get_billing_url(),
			__( 'Purchase a plan', 'social-post-flow' ),
			__( 'to resume posting to social media.', 'social-post-flow' ),
			admin_url( 'admin.php?page=social-post-flow' ),
			__( 'I\'ve already done this', 'social-post-flow' )
		);

	}

}
