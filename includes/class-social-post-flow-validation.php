<?php
/**
 * Validation class
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
 */

/**
 * Provides several validation functions which the Plugin can run
 * to ensure features work as expected.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
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
	 * @param   string $api_timezone               API Timezone.
	 * @return  WP_Error|bool
	 */
	public function timezones_match( $api_timezone ) {

		// Get WordPress timezone, and convert API timezone to a valid DateTimeZone offset value.
		$wordpress_timezone = social_post_flow()->get_class( 'date' )->convert_wordpress_gmt_offset_to_offset_value( get_option( 'gmt_offset' ) );
		$api_timezone       = social_post_flow()->get_class( 'date' )->convert_timezone_or_utc_to_offset_value( $api_timezone );

		// Fetch the current date and time, to the minute, for each of the timezones.
		try {
			$wordpress_date = new DateTime( 'now', new DateTimeZone( $wordpress_timezone ) );
			$api_date       = new DateTime( 'now', new DateTimeZone( $api_timezone ) );
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
					'%1$s<br /><br />%2$s<br />%3$s %4$s (%5$s) [<a href="%6$s" target="_blank">%7$s</a>]<br />%8$s %9$s (%10$s) [<a href="%11$s" target="_blank">%12$s</a>]',
					__( 'Your account\'s Timezone does not match your WordPress timezone.  They must be the same, to ensure that statuses can be scheduled, and are scheduled at the correct time.', 'social-post-flow' ),
					__( 'Right now, your timezones are configured as:', 'social-post-flow' ),
					__( 'WordPress Timezone:', 'social-post-flow' ),
					esc_html( $wordpress_timezone ),
					esc_html( $wordpress_date ),
					admin_url( 'options-general.php#timezone_string' ),
					__( 'Fix', 'social-post-flow' ),
					__( 'Account Timezone:', 'social-post-flow' ),
					esc_html( $api_timezone ),
					esc_html( $api_date ),
					esc_html( social_post_flow()->get_class( 'api' )->get_profile_url() ),
					__( 'Fix', 'social-post-flow' )
				)
			);
		}

	}

}
