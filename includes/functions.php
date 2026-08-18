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
 * @param   array  $result                  New Access Token, Refresh Token and Expiry timestamp.
 * @param   string $client_id               OAuth Client ID used for the Access and Refresh Tokens.
 * @param   string $existing_access_token   Existing Access Token.
 */
function social_post_flow_update_credentials( $result, $client_id, $existing_access_token ) {

	// Get Plugin instance.
	$social_post_flow = Social_Post_Flow::get_instance();

	// Get the account IDs based on the existing access token.
	$account_ids = $wp_to_buffer_pro->get_class( 'settings' )->get_account_ids_by_access_token( $existing_access_token );

	// Bail if no accounts are found.
	if ( count( $account_ids ) === 0 ) {
		return;
	}

	// Update the access and refresh tokens for each account.
	foreach ( $account_ids as $account_id ) {
		$wp_to_buffer_pro->get_class( 'settings' )->update_account_credentials(
			$result['access_token'],
			$result['refresh_token'],
			$result['token_expires'],
			$account_id
		);
	}

}

// Update Access Token when refreshed by the API class.
add_action( 'social_post_flow_api_refresh_token', 'social_post_flow_update_credentials', 10, 3 );
