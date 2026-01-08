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

		add_filter( 'social_post_flow_notices_get_notices', array( $this, 'add_user_no_access_notice' ) );

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

		// Get user transient, which was set when a call to user() was made via the API class.
		// This prevents querying the API on every request.
		$user = get_transient( 'social_post_flow_api_user' );

		// Get the checkout URL.
		// If it's included in the user transient, it'll be a direct checkout link to the minimum plan the user needs.
		// Otherwise, fall back to the billing page.
		$checkout_url = ( ( array_key_exists( 'checkout_url', $user ) && ! empty( $user['checkout_url'] ) ) ? $user['checkout_url'] : social_post_flow()->get_class( 'api' )->get_billing_url() );

		// Add the notice.
		$notices['error'][] = sprintf(
			'<strong>%s:</strong> %s <a href="%s" target="_blank">%s</a> %s<br /><a href="%s">%s</a>',
			__( 'Social Post Flow', 'social-post-flow' ),
			__( 'Your trial has ended.', 'social-post-flow' ),
			$checkout_url,
			__( 'Purchase a plan', 'social-post-flow' ),
			__( 'to resume posting to social media.', 'social-post-flow' ),
			admin_url( 'admin.php?page=social-post-flow' ),
			__( 'I\'ve already done this', 'social-post-flow' )
		);

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

}
