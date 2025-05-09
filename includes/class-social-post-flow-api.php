<?php
/**
 * Buffer API class
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
	 * Holds the Proxy endpoint, which might be used to pass requests through
	 *
	 * @since   1.0.0
	 *
	 * @var     string.
	 */
	private $proxy_endpoint = 'https://proxy.wpzinc.net/';

	/**
	 * Holds the API endpoint
	 *
	 * @since   1.0.0
	 *
	 * @var     string.
	 */
	private $api_endpoint = 'https://socialpostflow.local/api/';

	/**
	 * Holds the API Key
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	public $api_key = '';

	/**
	 * Sets the API Key
	 *
	 * @since   1.0.0
	 *
	 * @param   string $api_key   API Key.
	 */
	public function set_api_key( $api_key ) {

		$this->api_key = $api_key;

	}

	/**
	 * Returns the Buffer URL where the user can register for a Buffer account
	 *
	 * @since   1.0.0
	 *
	 * @return  string  URL
	 */
	public function get_registration_url() {

		return 'https://app.socialpostflow.com/register';

	}

	/**
	 * Returns the URL where the user can connect their social media accounts
	 *
	 * @since   1.0.0
	 *
	 * @return  string  URL
	 */
	public function get_connect_profiles_url() {

		return 'https://app.socialpostflow.com/profiles';

	}

	/**
	 * Returns a list of Social Media Profiles.
	 *
	 * @since   1.0.0
	 *
	 * @param   bool $force                      Force API call (false = use WordPress transient).
	 * @param   int  $transient_expiration_time  Transient Expiration Time, in seconds (default: 12 hours).
	 * @return  mixed                               WP_Error | Profiles object
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
	 * @return  mixed               WP_Error | Update object
	 */
	public function create_post( $params ) {

		// Send request.
		$result = $this->post( 'post', $params );

		// Bail if the result is an error.
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Return array of just the data we need to send to the Plugin.
		// @TODO.
		return array(
			'profile_id'        => $result->updates[0]->profile_id,
			'message'           => $result->message,
			'status_text'       => $result->updates[0]->text,
			'status_created_at' => $result->updates[0]->created_at,
			'scheduled_at'      => ( isset( $result->updates[0]->scheduled_at ) ? $result->updates[0]->scheduled_at : '0000-00-00 00:00:00' ),
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
	 * Main function which handles sending requests to the Buffer API
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
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'social_post_flow_no_api_key', __( 'No API Key was specified', 'social-post-flow' ) );
		}

		// Build endpoint URL.
		$url = $this->api_endpoint . '/' . $cmd;

		// Define the timeout.
		$timeout = 20;

		/**
		 * Defines the number of seconds before timing out a request to the Buffer API.
		 *
		 * @since   3.0.0
		 *
		 * @param   int     $timeout    Timeout, in seconds
		 */
		$timeout = apply_filters( 'social_post_flow_api_request', $timeout );

		// If proxy is enabled, send the request to our proxy with the URL, method and parameters.
		if ( social_post_flow()->get_class( 'settings' )->get_option( 'proxy', false ) ) {
			$response = wp_remote_get(
				$this->proxy_endpoint,
				array(
					'body' => array(
						'url'    => $url,
						'method' => $method,
						'params' => http_build_query( $params ),
					),
				)
			);
		} else {
			// Send request.
			switch ( $method ) {
				/**
				 * GET
				 */
				case 'get':
					$response = wp_remote_get(
						$url,
						array(
							'headers' => array(
								'X-API-Key' => $this->api_key,
							),
							'body'    => $params,
							'timeout' => $timeout,
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
							'headers' => array(
								'X-API-Key' => $this->api_key,
							),
							'body'    => $params,
							'timeout' => $timeout,
						)
					);
					break;
			}
		}

		// If an error occured, return it now.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Fetch HTTP code and body.
		$http_code = wp_remote_retrieve_response_code( $response );
		$response  = wp_remote_retrieve_body( $response );

		// Decode response.
		$body = json_decode( $response );

		// If no errors, return the body.
		if ( ! isset( $body->errors ) ) {
			return $body;
		}

		// Return WP_Error.
		return new WP_Error(
			$body->code,
			sprintf(
				/* translators: %1$s: API Error Code, %2$s: API Error Message */
				__( 'Social Post Flow: API Error: #%1$s: %2$s', 'social-post-flow' ),
				$body->code,
				implode( "\n", $body->errors )
			)
		);

	}

}
