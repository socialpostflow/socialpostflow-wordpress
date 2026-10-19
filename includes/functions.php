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

/**
 * Deletes the stored tokens when a refresh returns invalid_grant, which is terminal.
 *
 * @since   1.4.4
 *
 * @param   WP_Error $result                   Error from API.
 * @param   string   $client_id                OAuth Client ID.
 * @param   string   $existing_access_token    Existing Access Token.
 * @param   string   $existing_refresh_token   Existing Refresh Token.
 */
function social_post_flow_delete_credentials( $result, $client_id, $existing_access_token, $existing_refresh_token ) {

	// Don't delete credentials if the error is not invalid_grant.
	if ( $result->get_error_code() !== 'invalid_grant' ) {
		return;
	}

	// Don't delete credentials if another request has already refreshed them.
	if ( social_post_flow()->get_class( 'settings' )->get_refresh_token() !== $existing_refresh_token ) {
		return;
	}

	// Delete tokens and unschedule the refresh, so the user is prompted to reconnect.
	social_post_flow()->get_class( 'settings' )->delete_tokens();
	social_post_flow()->get_class( 'cron' )->unschedule_refresh_token_event();

}

// Delete tokens when refreshing results in an invalid grant.
add_action( 'social_post_flow_api_refresh_token_error', 'social_post_flow_delete_credentials', 10, 4 );

// Schedule the next token refresh, both when first connecting and on every refresh.
add_action( 'social_post_flow_api_get_access_token', 'social_post_flow_schedule_refresh_token_event', 20, 1 );
add_action( 'social_post_flow_api_refresh_token', 'social_post_flow_schedule_refresh_token_event', 20, 1 );
