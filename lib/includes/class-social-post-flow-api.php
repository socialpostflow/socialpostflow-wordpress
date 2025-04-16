<?php
/**
 * API class.
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Used by other classes which interact with APIs to perform POST and GET requests.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 1.0.0
 */
class Social_Post_Flow_API {

	/**
	 * Sanitizes API arguments, by removing false or empty
	 * arguments in the array.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $args   Arguments.
	 * @return  array           Sanitized Arguments
	 */
	public function sanitize_arguments( $args ) {

		foreach ( $args as $key => $value ) {
			if ( empty( $value ) || ! $value ) {
				unset( $args[ $key ] );
			}
		}

		return $args;

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
	public function get( $cmd, $params = array() ) {

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
	public function post( $cmd, $params = array() ) {

		return $this->request( $cmd, 'post', $params );

	}

	/**
	 * Main function which handles sending requests to an API
	 *
	 * @since   1.0.0
	 *
	 * @param   string $cmd        Command.
	 * @param   string $method     Method (get|post).
	 * @param   array  $params     Parameters (optional).
	 * @return  mixed               WP_Error | object
	 */
	private function request( $cmd, $method = 'get', $params = array() ) {

		// Set timeout.
		$timeout = 20;

		/**
		 * Defines the number of seconds before timing out a request to the remote API.
		 *
		 * @since   3.0.0
		 *
		 * @param   int     $timeout    Timeout, in seconds.
		 */
		$timeout = apply_filters( 'wp_to_social_pro_api_request_timeout', $timeout );

		// Send request.
		$result = $this->request_wordpress( $this->api_endpoint, $cmd, $method, $params, $timeout );

		// Result will be WP_Error or the data we expect.
		return $result;

	}

	/**
	 * Performs POST and GET requests.
	 *
	 * @since   1.7.1
	 *
	 * @param   string $url        URL.
	 * @param   string $cmd        API Command.
	 * @param   string $method     Method (post|get).
	 * @param   array  $params     Parameters.
	 * @param   int    $timeout    Timeout, in seconds (default: 10).
	 * @return  mixed               WP_Error | object
	 */
	private function request_wordpress( $url, $cmd, $method, $params, $timeout = 20 ) {

		// Send request.
		switch ( $method ) {
			/**
			 * GET
			 */
			case 'get':
			case 'GET':
				$response = wp_remote_get(
					$url . '&' . http_build_query(
						array(
							'endpoint' => $cmd,
							'params'   => $params,
						)
					),
					array(
						'timeout' => $timeout,
					)
				);
				break;

			/**
			 * POST
			 */
			case 'post':
			case 'POST':
				$response = wp_remote_post(
					$url,
					array(
						'body'    => array(
							'endpoint' => $cmd,
							'params'   => $params,
						),
						'timeout' => $timeout,
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

		// Decode the result.
		$result = json_decode( $response );

		// If the response is empty or missing the data payload, return a generic error.
		if ( is_null( $result ) || ! isset( $result->data ) ) {
			return new WP_Error(
				$http_code,
				'API Error: HTTP Code ' . $http_code . '. Sorry, we don\'t have any more information about this error. Please try again.'
			);
		}

		// If the response's success flag is false, return the data as an error.
		if ( ! $result->success ) {
			return new WP_Error( $http_code, $result->data );
		}

		// All OK - return the data.
		// This is from the originating API request, and we no longer need it.
		unset( $result->data->status );

		// object comprising of data, links + meta.
		return $result->data;

	}

}
