<?php
/**
 * Date class.
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
 */

/**
 * Helper functions for changing dates and returning time offsets
 * based on the WordPress configuration.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */
class Social_Post_Flow_Date {

	/**
	 * Helper method to return the adjusted date and time based on the given parameters
	 *
	 * @since   1.0.0
	 *
	 * @param   mixed  $date               Date.
	 * @param   string $before_or_after    Whether to subtract (before) or add (after) to the date.
	 * @param   int    $days               Day(s) to add or subtract.
	 * @param   int    $hours              Hour(s) to add or subtract.
	 * @param   int    $minutes            Minute(s) to add or subtract.
	 * @return  string                      Adjusted Date and Time (yyyy-mm-dd hh:ii:ss)
	 */
	public function adjust_date_time( $date, $before_or_after, $days, $hours, $minutes ) {

		// Bail if no date.
		if ( ! $date ) {
			return $date;
		}

		// Add or subtract days, hours and minutes from the date.
		switch ( $before_or_after ) {
			/**
			 * Subtract
			 */
			case 'before':
				$date = strtotime( '-' . $days . ' days -' . $hours . ' hours -' . $minutes . ' minutes', strtotime( $date ) );
				break;

			/**
			 * Add
			 */
			default:
				$date = strtotime( '+' . $days . ' days +' . $hours . ' hours +' . $minutes . ' minutes', strtotime( $date ) );
				break;
		}

		return gmdate( 'Y-m-d H:i:s', $date );
	}

	/**
	 * Returns the UTC Date and Time for the given Date and Time, based on WordPress' GMT Offset.
	 *
	 * When sending a specific date and time to schedule a status, the datetime that we send via the API must be in UTC.
	 * The social media service can then apply its timezone offset as defined by the user account's settings.
	 *
	 * For example, calling this function with 2018-09-01 13:00:00 in a UTC+1 timezone will return as 2018-09-01 12:00:00.
	 * The social media service will then schedule for 2018-09-01 13:00:00, because the social media services' timezone (UTC+1)
	 * will (in this case) add an hour back to the scheduled datetime.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $date_time  Date and Time (yyyy-mm-dd HH:ii:ss).
	 * @return  string              UTC Date and Time (yyyy-mm-dd HH:ii:ss)
	 */
	public function get_utc_date_time( $date_time ) {

		// If there is no offset, the date and time is already UTC.
		$gmt_offset = get_option( 'gmt_offset' );
		if ( ! $gmt_offset ) {
			return $date_time;
		}

		// Convert the GMT offset to an offset value e.g. +0300, -0530.
		$gmt_offset = $this->convert_wordpress_gmt_offset_to_offset_value( $gmt_offset );

		// Offset the date and time by the timezone.
		$date_object = date_create( $date_time, timezone_open( $gmt_offset ) );
		date_timezone_set( $date_object, timezone_open( 'UTC' ) );

		// Return adjusted date and time.
		return date_format( $date_object, 'Y-m-d H:i:s' );

	}

	/**
	 * Converts WordPress' GMT Offset (e.g. -5, +3.3) to an offset value compatible with
	 * WordPress' DateTime object (e.g. -0500, +0330)
	 *
	 * @since   1.0.0
	 *
	 * @param   float $gmt_offset     GMT Offset.
	 * @return  string                  GMT Offset Value
	 */
	public function convert_wordpress_gmt_offset_to_offset_value( $gmt_offset ) {

		// Don't do anything if the offset is zero.
		if ( $gmt_offset == 0 ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
			return '+0000';
		}

		// Define the GMT offset string e.g. +0100, -0300 etc.
		if ( $gmt_offset > 0 ) {
			if ( $gmt_offset < 10 ) {
				$gmt_offset = '0' . abs( $gmt_offset );
			} else {
				$gmt_offset = abs( $gmt_offset );
			}

			$gmt_offset = '+' . $gmt_offset;
		} elseif ( $gmt_offset < 0 ) {
			if ( $gmt_offset > -10 ) {
				$gmt_offset = '0' . abs( $gmt_offset );
			} else {
				$gmt_offset = abs( $gmt_offset );
			}

			$gmt_offset = '-' . $gmt_offset;
		}

		// If the GMT offset contains .5, change this to :30.
		// Otherwise pad the GMT offset.
		if ( strpos( $gmt_offset, '.5' ) !== false ) {
			$gmt_offset = str_replace( '.5', ':30', $gmt_offset );
		} else {
			$gmt_offset .= '00';
		}

		/**
		 * Converts WordPress' GMT Offset (e.g. -5, +3.3) to an offset value compatible with
		 * WordPress' DateTime object (e.g. -0500, +0330)
		 *
		 * @since   1.0.0
		 *
		 * @param   string      $gmt_offset   GMT Offset (e.g. -0500, +0330).
		 */
		$gmt_offset = apply_filters( 'social_post_flow_common_convert_wordpress_gmt_offset_to_offset_value', $gmt_offset );

		// Return.
		return $gmt_offset;

	}

	/**
	 * Returns a DateTimeZone compatible offset value for the given named or UTC offset:
	 * - Asia/Singapore --> Asia/Singapore
	 * - UTC-5 --> 05:00
	 *
	 * @since   1.1.9
	 *
	 * @param   string $timezone   Timezone or UTC offset (Asia/Singapore, UTC-5, etc).
	 * @return  string             DateTimeZone compatible offset value (e.g. +0500, -0500, etc)
	 */
	public function convert_timezone_or_utc_to_offset_value( $timezone ) {

		// If the timezone is 'UTC', don't need to convert.
		if ( $timezone === 'UTC' ) {
			return $timezone;
		}

		// If the timezone isn't a manual UTC offset, don't need to convert.
		if ( ! str_starts_with( $timezone, 'UTC' ) ) {
			return $timezone;
		}

		// Fetch the offset - "+3", "-5", "+5:30", etc.
		$offset = str_replace( 'UTC', '', $timezone );

		// Match optional fractional minutes.
		if ( preg_match( '/^([+-]?)(\d+)(?::(\d+))?$/', $offset, $matches ) ) {
			$sign    = ( $matches[1] !== '' ) ? $matches[1] : '+';
			$hours   = str_pad( $matches[2], 2, '0', STR_PAD_LEFT );
			$minutes = isset( $matches[3] ) ? str_pad( $matches[3], 2, '0', STR_PAD_LEFT ) : '00';

			return $sign . $hours . ':' . $minutes;
		}

		return $offset;

	}

}
