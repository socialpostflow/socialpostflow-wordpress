<?php
/**
 * General plugin functions.
 *
 * @package Social Post Flow
 * @author WP Zinc
 */

/**
 * Saves the new access token, refresh token and its expiry.
 *
 * @since   1.4.0
 *
 * @param   array $result New Access Token, Refresh Token and Expiry timestamp.
 */
function social_post_flow_update_credentials( $result ) {

	social_post_flow()->get_class( 'settings' )->update_tokens(
		$result['access_token'],
		$result['refresh_token'],
		time() + $result['expires_in']
	);

}

// Update Access Token when refreshed by the API class.
add_action( 'social_post_flow_api_refresh_token', 'social_post_flow_update_credentials', 10, 1 );

/**
 * Schedules the WordPress Cron event to refresh the access token before it expires.
 *
 * Runs whenever an access token is issued or refreshed, so each token schedules
 * the refresh of its successor.
 *
 * @since   1.4.0
 *
 * @param   array $result New Access Token, Refresh Token and Expiry timestamp.
 */
function social_post_flow_schedule_refresh_token_event( $result ) {

	social_post_flow()->get_class( 'cron' )->schedule_refresh_token_event( time() + $result['expires_in'] );

}

// Schedule the next token refresh, both when first connecting and on every refresh.
add_action( 'social_post_flow_api_get_access_token', 'social_post_flow_schedule_refresh_token_event', 20, 1 );
add_action( 'social_post_flow_api_refresh_token', 'social_post_flow_schedule_refresh_token_event', 20, 1 );
