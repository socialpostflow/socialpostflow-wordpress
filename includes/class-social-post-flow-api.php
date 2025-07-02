<?php
/**
 * Social Post Flow API class
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */

/**
 * Provides functions for sending statuses and querying Social Post Flow's API.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 3.0.0
 */
class Social_Post_Flow_API {

	/**
	 * Holds the API endpoint
	 *
	 * @since   1.0.0
	 *
	 * @var     string.
	 */
	private $api_endpoint = 'https://app.socialpostflow.com/api/';

	/**
	 * Holds the OAuth Authorize URL
	 *
	 * @since   1.0.0
	 *
	 * @var     string.
	 */
	private $oauth_authorize_url = 'https://app.socialpostflow.com/oauth/authorize';

	/**
	 * Holds the OAuth Redirect URL
	 *
	 * @since   1.0.0
	 *
	 * @var     string.
	 */
	private $oauth_redirect_uri = 'https://app.socialpostflow.com/oauth/callback';

	/**
	 * Holds the OAuth Token URL
	 *
	 * @since   1.0.0
	 *
	 * @var     string.
	 */
	private $oauth_token_url = 'https://app.socialpostflow.com/oauth/token';

	/**
	 * Holds the OAuth Client ID
	 *
	 * @since   1.0.0
	 *
	 * @var     string.
	 */
	private $client_id = '01970fdc-2595-73ed-aebb-88398845bb5b'; // production.

	/**
	 * Access Token
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	public $access_token = '';

	/**
	 * Refresh Token
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	public $refresh_token = '';

	/**
	 * Returns the URL used to register a new account
	 *
	 * @since   1.0.0
	 *
	 * @return  string                 Registration URL
	 */
	public function get_registration_url() {

		return 'https://app.socialpostflow.com/register';

	}

	/**
	 * Returns the URL where the user can connect their social media accounts
	 * to Social Post Flow
	 *
	 * @since   1.0.0
	 *
	 * @return  string  URL
	 */
	public function get_connect_profiles_url() {

		return 'https://app.socialpostflow.com/profiles';

	}

	/**
	 * Returns the URL used to begin the OAuth process
	 *
	 * @since   1.0.0
	 *
	 * @param   string $return_url   Return URL.
	 * @return  string                 OAuth URL
	 */
	public function get_oauth_url( $return_url ) {

		// Generate and store code verifier and challenge.
		$code_verifier  = $this->generate_and_store_code_verifier();
		$code_challenge = $this->generate_code_challenge( $code_verifier );

		// Build args.
		$args = array(
			'client_id'             => $this->client_id,
			'response_type'         => 'code',
			'redirect_uri'          => rawurlencode( $this->oauth_redirect_uri ),
			'state'                 => rawurlencode( $return_url ),
			'code_challenge'        => $code_challenge,
			'code_challenge_method' => 'S256',
		);

		// Return OAuth URL.
		return add_query_arg(
			$args,
			$this->oauth_authorize_url
		);

	}

	/**
	 * Exchanges the given code for an access token, refresh token and other data.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $authorization_code     Authorization Code, returned from get_oauth_url() flow.
	 * @return  WP_Error|array
	 */
	public function get_access_token( $authorization_code ) {

		// Exchange the code for an access token, refresh token and other data.
		$response = wp_remote_post(
			$this->oauth_token_url,
			array(
				'headers'   => array(
					'Accept' => 'application/json',
				),
				'body'      => array(
					'client_id'     => $this->client_id,
					'grant_type'    => 'authorization_code',
					'code'          => $authorization_code,
					'redirect_uri'  => $this->oauth_redirect_uri,
					'code_verifier' => $this->get_code_verifier(),
				),
				'timeout'   => $this->get_timeout(),
				'sslverify' => $this->enable_ssl_verification(),
			)
		);

		// Delete code verifier, as it's no longer needed.
		// If the access token request fails, the user
		// will begin the process again, which generates a
		// new code verifier.
		$this->delete_code_verifier();

		// If an error occured, return it now.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Fetch and decode body.
		$response = wp_remote_retrieve_body( $response );
		$result   = json_decode( $response, true );

		// If an error occured, return it now.
		if ( isset( $result['error'] ) ) {
			return new WP_Error( $result['error'], $result['error_description'] );
		}

		/**
		 * Perform any actions with the new access token, such as saving it.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $result     access_token, refresh_token, token_type, expires_in
		 * @param   string  $client_id  OAuth Client ID.
		 */
		do_action( 'social_post_flow_api_get_access_token', $result, $this->client_id );

		// Return.
		return $result;

	}

	/**
	 * Fetches a new access token using the supplied refresh token.
	 *
	 * @since   1.0.0
	 *
	 * @return  WP_Error|array
	 */
	public function refresh_token() {

		// Exchange the code for an access token, refresh token and other data.
		$response = wp_remote_post(
			$this->oauth_token_url,
			array(
				'headers'   => array(
					'Accept' => 'application/json',
				),
				'body'      => array(
					'client_id'     => $this->client_id,
					'grant_type'    => 'refresh_token',
					'refresh_token' => $this->refresh_token,
				),
				'timeout'   => $this->get_timeout(),
				'sslverify' => $this->enable_ssl_verification(),
			)
		);

		// If an error occured, return it now.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Fetch and decode body.
		$response = wp_remote_retrieve_body( $response );
		$result   = json_decode( $response, true );

		// If an error occured, return it now.
		if ( isset( $result['error'] ) ) {
			return new WP_Error( $result['error'], $result['error_description'] );
		}

		// Store existing access and refresh tokens.
		$previous_access_token  = $this->access_token;
		$previous_refresh_token = $this->refresh_token;

		// Update the access and refresh tokens in this class.
		$this->access_token  = $result['access_token'];
		$this->refresh_token = $result['refresh_token'];

		/**
		 * Perform any actions with the new access token, such as saving it.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $result                  New access_token, refresh_token, token_type, expires_in
		 * @param   string  $client_id               OAuth Client ID.
		 * @param   string  $previous_access_token   Existing Access Token.
		 * @param   string  $previous_refresh_token  Existing Refresh Token.
		 */
		do_action( 'social_post_flow_api_refresh_token', $result, $this->client_id, $previous_access_token, $previous_refresh_token );

		// Return.
		return $result;

	}

	/**
	 * Generates and stores a code verifier for PKCE authentication flow.
	 *
	 * @since   1.0.0
	 *
	 * @return  string
	 */
	private function generate_and_store_code_verifier() {

		// If a code verifier already exists, use it.
		$code_verifier = $this->get_code_verifier();
		if ( $code_verifier ) {
			return $code_verifier;
		}

		// Generate a random string.
		$code_verifier = random_bytes( 64 );

		// Encode to Base64 string.
		$code_verifier = $this->base64_urlencode( $code_verifier );

		// Store in database for later use.
		update_option( 'social_post_flow_code_verifier', $code_verifier );

		// Return.
		return $code_verifier;

	}

	/**
	 * Base64URL the given code verifier, as PHP has no built in function for this.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $code_verifier  Code Verifier.
	 * @return  string                  Code Challenge.
	 */
	private function generate_code_challenge( $code_verifier ) {

		// Hash using S256.
		$code_challenge = hash( 'sha256', $code_verifier, true );

		// Encode to Base64 string.
		$code_challenge = base64_encode( $code_challenge ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions

		// Convert Base64 to Base64URL by replacing “+” with “-” and “/” with “_”.
		$code_challenge = strtr( $code_challenge, '+/', '-_' );

		// Remove padding character from the end of line.
		$code_challenge = rtrim( $code_challenge, '=' );

		// Return.
		return $code_challenge;

	}

	/**
	 * Returns the stored code verifier generated by generate_and_store_code_verifier().
	 *
	 * @since   1.0.0
	 *
	 * @return  bool|string
	 */
	private function get_code_verifier() {

		return get_option( 'social_post_flow_code_verifier' );

	}

	/**
	 * Deletes the stored code verifier generated by generate_code_verifier().
	 *
	 * @since   1.0.0
	 *
	 * @return  bool
	 */
	private function delete_code_verifier() {

		return delete_option( 'social_post_flow_code_verifier' );

	}

	/**
	 * Base64URL encode the given string.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $str    String to encode.
	 * @return  string         Encoded string.
	 */
	private function base64_urlencode( $str ) {

		// Encode to Base64 string.
		$str = base64_encode( $str ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions

		// Convert Base64 to Base64URL by replacing “+” with “-” and “/” with “_”.
		$str = strtr( $str, '+/', '-_' );

		// Remove padding character from the end of line.
		$str = rtrim( $str, '=' );

		return $str;

	}

	/**
	 * Sets this class' access and refresh tokens
	 *
	 * @since   1.0.0
	 *
	 * @param   string $access_token    Access Token.
	 * @param   string $refresh_token   Refresh Token.
	 */
	public function set_tokens( $access_token = '', $refresh_token = '' ) {

		$this->access_token  = $access_token;
		$this->refresh_token = $refresh_token;

	}

	/**
	 * Checks if an access token was set.  Called by any function which
	 * performs a call to the API
	 *
	 * @since   1.0.0
	 *
	 * @return  bool    Token Exists
	 */
	private function check_access_token_exists() {

		if ( empty( $this->access_token ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Checks if a refresh token was set.  Called by any function which
	 * performs a call to the API
	 *
	 * @since   1.0.0
	 *
	 * @return  bool    Token Exists
	 */
	private function check_refresh_token_exists() {

		if ( empty( $this->refresh_token ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Returns a list of Social Media Profiles.
	 *
	 * @since   1.0.0
	 *
	 * @param   bool $force                      Force API call (false = use WordPress transient).
	 * @param   int  $transient_expiration_time  Transient Expiration Time, in seconds (default: 12 hours).
	 * @return  WP_Error|array
	 */
	public function profiles( $force = false, $transient_expiration_time = 43200 ) {

		// Check if our WordPress transient already has this data.
		// This reduces the number of times we query the API.
		$profiles = get_transient( 'social_post_flow_api_profiles' );
		if ( $force || false === $profiles ) {
			// Setup profiles array.
			$profiles = array();

			// Get profiles.
			$results = $this->get( 'profiles' );

			// Check for errors.
			if ( is_wp_error( $results ) ) {
				return $results;
			}

			$profiles = $results['data'];

			// Store profiles in transient.
			set_transient( 'social_post_flow_api_profiles', $profiles, $transient_expiration_time );
		}

		// Return results.
		return $profiles;

	}

	/**
	 * Creates a Post on Social Post Flow.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $params     Params.
	 * @return  WP_Error|array
	 */
	public function create_post( $params ) {

		return $this->post( 'posts', $params );

	}

	/**
	 * Creates Posts on Social Post Flow.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $posts     Posts.
	 * @return  WP_Error|array
	 */
	public function create_posts( $posts ) {

		return $this->post(
			'posts/bulk',
			array(
				'posts' => $posts,
			)
		);

	}

	/**
	 * Private function to perform a GET request
	 *
	 * @since  1.0.0
	 *
	 * @param  string $cmd        Command (required).
	 * @param  array  $params     Params (optional).
	 * @return mixed               WP_Error | object
	 */
	private function get( $cmd, $params = array() ) {

		return $this->request( $cmd, 'get', $params );

	}

	/**
	 * Private function to perform a POST request
	 *
	 * @since  1.0.0
	 *
	 * @param  string $cmd        Command (required).
	 * @param  array  $params     Params (optional).
	 * @return mixed               WP_Error | object
	 */
	private function post( $cmd, $params = array() ) {

		return $this->request( $cmd, 'post', $params );

	}

	/**
	 * Main function which handles sending requests to the Social Post Flow API
	 *
	 * @since   1.0.0
	 *
	 * @param   string $cmd        Command.
	 * @param   string $method     Method (get|post).
	 * @param   array  $params     Parameters (optional).
	 * @return  mixed               WP_Error | object
	 */
	private function request( $cmd, $method = 'get', $params = array() ) {

		// Check required parameters exist.
		if ( empty( $this->access_token ) ) {
			return new WP_Error( 'social_post_flow_no_access_token', __( 'No access token was specified', 'social-post-flow' ) );
		}

		// Build endpoint URL.
		$url = $this->api_endpoint . $cmd;

		// Send request.
		switch ( $method ) {
			/**
			 * GET
			 */
			case 'get':
				$response = wp_remote_get(
					$url,
					array(
						'headers'   => array(
							'Authorization' => 'Bearer ' . $this->access_token,
							'Accept'        => 'application/json',
						),
						'body'      => $params,
						'timeout'   => $this->get_timeout(),
						'sslverify' => $this->enable_ssl_verification(),
					)
				);
				break;

			/**
			 * POST
			 */
			case 'post':
				$response = wp_remote_post(
					$url,
					array(
						'headers'   => array(
							'Authorization' => 'Bearer ' . $this->access_token,
							'Accept'        => 'application/json',
						),
						'body'      => $params,
						'timeout'   => $this->get_timeout(),
						'sslverify' => $this->enable_ssl_verification(),
					)
				);
				break;
		}

		// If an error occured, return it now.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Fetch HTTP code and body.
		$http_code = wp_remote_retrieve_response_code( $response );
		$response  = wp_remote_retrieve_body( $response );

		// Decode response.
		$body = json_decode( $response, true );

		// If the body contains a message, an error occured.
		if ( isset( $body['message'] ) ) {
			// OAuth and non-authenticated requests will just return a `message` key.
			// Authenticated requests will return a `message` key and an `errors` array.
			if ( isset( $body['errors'] ) ) {
				$error_message = array();
				foreach ( $body['errors'] as $error_key => $errors ) {
					$error_message = array_merge( $error_message, $errors );
				}
			} else {
				$error_message = array(
					$body['message'],
				);
			}

			return new WP_Error(
				$http_code,
				sprintf(
				/* translators: %1$s: API Error Code, %2$s: API Error Message(s) */
					__( 'Social Post Flow: API Error: #%1$s %2$s', 'social-post-flow' ),
					$http_code,
					implode( "\n", $error_message )
				)
			);
		}

		return $body;

	}

	/**
	 * Returns the timeout for the Social Post Flow API.
	 *
	 * @since   1.0.0
	 *
	 * @return  int
	 */
	private function get_timeout() {

		// Define the timeout.
		$timeout = 20;

		/**
		 * Defines the number of seconds before timing out a request to the Social Post Flow API.
		 *
		 * @since   1.0.0
		 *
		 * @param   int     $timeout    Timeout, in seconds
		 */
		$timeout = apply_filters( 'social_post_flow_api_get_timeout', $timeout );

		return $timeout;

	}

	/**
	 * Returns whether SSL verification is enabled for the Social Post Flow API.
	 *
	 * @since   1.0.0
	 *
	 * @return  bool
	 */
	private function enable_ssl_verification() {

		$enable_ssl_verification = true;

		/**
		 * Defines whether to enable SSL verification for the Social Post Flow API.
		 *
		 * @since   1.0.0
		 *
		 * @param   bool    $enable_ssl_verification    Enable SSL verification.
		 */
		$enable_ssl_verification = apply_filters( 'social_post_flow_api_enable_ssl_verification', $enable_ssl_verification );

		return $enable_ssl_verification;

	}

}
