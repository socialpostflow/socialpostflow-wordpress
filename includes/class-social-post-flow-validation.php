<?php
/**
 * Validation class
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Provides several validation functions which the Plugin can run
 * to ensure features work as expected.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 1.0.0
 */
class Social_Post_Flow_Validation {

	/**
	 * Checks if an Access Token exists, meaning that the API service is connected
	 * to the Plugin.
	 *
	 * @since   1.0.0
	 *
	 * @return  bool    API Connected
	 */
	public function api_connected() {

		$access_token = social_post_flow()->get_class( 'settings' )->get_access_token();
		if ( empty( $access_token ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks if the WordPress timezone matches the given API Timezone,
	 * which could be a global API timezone or a profile-specific timezone.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $api_profile_timezone               API Timezone.
	 * @param   string $api_profile_name                   API Profile Name (e.g. @n7TestAcct).
	 * @param   string $api_profile_change_timezone_url    URL to API service where the user can change the timezone.
	 * @return  mixed   WP_Error | true
	 */
	public function timezones_match( $api_profile_timezone = false, $api_profile_name = '', $api_profile_change_timezone_url = '#' ) {

		// Pass test if we don't have API access.
		$api_connected = $this->api_connected();
		if ( ! $api_connected ) {
			return true;
		}

		// Fetch timezones for WordPress, Server and API.
		social_post_flow()->get_class( 'api' )->set_tokens(
			social_post_flow()->get_class( 'settings' )->get_access_token(),
			social_post_flow()->get_class( 'settings' )->get_refresh_token()
		);
		$wordpress_timezone = social_post_flow()->get_class( 'date' )->convert_wordpress_gmt_offset_to_offset_value( get_option( 'gmt_offset' ) );

		// Pass test if the API date couldn't be fetched.
		if ( ! $api_profile_timezone ) {
			return true;
		}

		// Fetch the current date and time, to the minute, for each of the timezones.
		try {
			$wordpress_date = new DateTime( 'now', new DateTimeZone( $wordpress_timezone ) );
			$api_date       = new DateTime( 'now', new DateTimeZone( $api_profile_timezone ) );
		} catch ( Exception $e ) {
			return new WP_Error( 'social_post_flow_date_time_zone_error', $e->getMessage() );
		}

		// If the three dates don't match, scheduling won't work as expected.
		$wordpress_date = $wordpress_date->format( 'Y-m-d H:i' );
		$api_date       = $api_date->format( 'Y-m-d H:i' );

		if ( $api_date !== $wordpress_date ) {
			return new WP_Error(
				'social_post_flow_timezones_invalid',
				sprintf(
					'%1$s<br /><br />%2$s<br />%3$s %4$s (%5$s) [<a href="%6$s" target="_blank">%7$s</a>]<br />%8$s Profile Timezone: %9$s (%10$s) [<a href="%11$s" target="_blank">%12$s</a>]',
					__( 'This Profile\'s Timezone does not match your WordPress timezone.  They must be the same, to ensure that statuses can be scheduled, and are scheduled at the correct time.', 'social-post-flow' ),
					__( 'Right now, your timezones are configured as:', 'social-post-flow' ),
					__( 'WordPress Timezone:', 'social-post-flow' ),
					esc_html( $wordpress_timezone ),
					esc_html( $wordpress_date ),
					admin_url( 'options-general.php#timezone_string' ),
					__( 'Fix', 'social-post-flow' ),
					esc_html( $api_profile_name ),
					esc_html( $api_profile_timezone ),
					esc_html( $api_date ),
					esc_html( $api_profile_change_timezone_url ),
					__( 'Fix', 'social-post-flow' )
				)
			);
		}

	}

}
