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
	private $api_endpoint = 'https://social-post-scheduler.local/api/';

	/**
	 * API Key
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	public $api_key = '';

	/**
	 * Constructor
	 *
	 * @since   3.4.7
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct() {

		add_action( 'social_post_flow_output_auth', array( $this, 'output_api_key_field' ) );

	}

	/**
	 * Outputs an API Key field on Settings > General, when the Plugin needs to be authenticated.
	 *
	 * @since   4.2.0
	 */
	public function output_api_key_field() {

		?>
		<div class="wpzinc-option">
			<div class="full">
				API Key Field Here.
			</div>
		</div>
		<?php

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
			set_transient( 'social_post_flow_api_profiless', $profiles, $transient_expiration_time );
		}

		// Return results.
		return $profiles;

	}

	/**
	 * Creates a Post
	 *
	 * @since   1.0.0
	 *
	 * @param   array $params     Params.
	 * @return  mixed               WP_Error | Update object
	 */
	public function updates_create( $params ) {

		// Send request.
		$result = $this->post( 'posts', $params );

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
			'scheduled_at'            => ( isset( $result->updates[0]->scheduled_at ) ? $result->updates[0]->scheduled_at : '0000-00-00 00:00:00' ),
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
			return new WP_Error( 'missing_access_token', __( 'No API Key was specified', 'social-post-flow' ) );
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

		// Request via WordPress functions.
		return $this->request_wordpress( $url, $method, $params, $timeout );

	}

	/**
	 * Performs POST and GET requests through WordPress wp_remote_post() and
	 * wp_remote_get() functions
	 *
	 * @since   1.0.0
	 *
	 * @param   string $url        URL.
	 * @param   string $method     Method (post|get).
	 * @param   array  $params     Parameters.
	 * @param   int    $timeout    Timeout, in seconds (default: 10).
	 * @return  mixed               WP_Error | object
	 */
	private function request_wordpress( $url, $method, $params, $timeout = 20 ) {

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

		// Parse the response, to return the JSON data or an WP_Error object.
		return $this->parse_response( $response, $http_code, $params );

	}

	/**
	 * Parses the response body and HTTP code, returning either
	 * a WP_Error object or the JSON decoded response body.
	 *
	 * @since   3.9.8
	 *
	 * @param   string $response   Response Body.
	 * @param   int    $http_code  HTTP Code.
	 * @param   array  $params     Request Parameters.
	 * @return  mixed               WP_Error | object
	 */
	private function parse_response( $response, $http_code, $params ) {

		// Decode response.
		$body = json_decode( $response );

		// Return body if HTTP code is 200.
		if ( $http_code === 200 ) {
			return $body;
		}

		// Return basic WP_Error if we don't have any more information.
		if ( is_null( $body ) ) {
			return new WP_Error(
				$http_code,
				sprintf(
					/* translators: HTTP Response Code */
					__( 'Buffer API Error: HTTP Code %s. Sorry, we don\'t have any more information about this error. Please try again.', 'social-post-flow' ),
					$http_code
				)
			);
		}

		// Return detailed WP_Error.
		// Define the error message.
		$message = array();
		if ( isset( $body->error ) ) {
			$message[] = $body->error;
		}
		if ( isset( $body->message ) ) {
			$message[] = $body->message;
		}

		// For certain error codes, we can provide better error messages to the user, detailing
		// the steps they should take to resolve the issue.
		switch ( $body->code ) {

			/**
			 * Unauthorized.
			 * Permission Denied.
			 * Access Token Required.
			 */
			case 401:
			case 403:
			case 1001:
				$message[] = __( 'Click the "Deauthorize Plugin" button, and then the "Authorize Plugin" button on the Plugin\'s Settings screen', 'social-post-flow' );
				break;

			/**
			 * Parameter not recognized (invalid image url parameter supplied).
			 */
			case 1003:
				$message[] = __( 'Run this Plugin on a publicly accessible domain that does not have password protection.', 'social-post-flow' );
				break;

			/**
			 * Featured Image Missing.
			 * Message too long.
			 */
			case 1004:
				if ( strpos( $body->message, 'image' ) !== false ) {
					$message = array(
						__( 'A Featured Image is required for this status to be sent.', 'social-post-flow' ),
					);
				} else {
					$message = array(
						$body->message,
					);
				}
				break;

			/**
			 * No authorization to access profile.
			 */
			case 1011:
				$message[] = sprintf(
					/* translators: %1$s: Social Media Account Service/Type (e.g. Facebook, Twitter), %2$s: Social Media Account Name */
					__( 'Pinterest: Choose a Pinterest board in the status settings.  Otherwise, reconnect the %1$s Account %2$s in Buffer.', 'social-post-flow' ),
					$profile['formatted_service'],
					$profile['formatted_username']
				);
				break;

			/**
			 * Queue limit reached.
			 */
			case 1023:
				$message[] = sprintf(
					'<a href="https://buffer.com/pricing">%s</a> %s',
					__( 'Upgrade your Buffer plan', 'social-post-flow' ),
					__( 'or change status schedule = Post Immediately in the Plugin status settings.', 'social-post-flow' )
				);
				break;

			/**
			 * Duplicate update.
			 */
			case 1025:
				$message[] = __( 'Change the status text using the Per-Post Settings, to ensure it is slightly different from the last status.', 'social-post-flow' );
				break;

			/**
			 * Media filetype not supported (...)
			 * The provided image does not appear to be valid i.e. is a localhost URL or invalid dimensions
			 * Whoops, so sorry! It looks like we had some trouble with your Facebook Page mentions. Would you be up for trying again?
			 */
			case 1030:
				if ( strpos( $body->message, 'Facebook Page mentions' ) !== false ) {
					$message = array(
						$body->message,
					);
				}

				if ( strpos( $body->message, 'image' ) !== false ) {
					if ( $this->is_local_host() ) {
						$message = array(
							sprintf(
								/* translators: Image URL */
								__( 'Buffer could not fetch the image %s because your site is running on a local host and not web accessible. Please run the Plugin on a publicly accessible domain.', 'social-post-flow' ),
								( isset( $params['media']['picture'] ) ? $params['media']['picture'] : '' )
							),
						);
					} else {
						$message = array(
							sprintf(
								/* translators: Image URL */
								__( 'Buffer could not fetch the image `%1$s`.  Check:', 'social-post-flow' ),
								( isset( $params['media']['picture'] ) ? $params['media']['picture'] : '' )
							),
							sprintf(
								/* translators: Link to Cloudflare Docs */
								__( '- For Cloudflare, that %s, or configured to allow the required image(s) to be fetched by Buffer', 'social-post-flow' ),
								'<a href="https://developers.cloudflare.com/waf/tools/scrape-shield/hotlink-protection/" target="_blank">' . __( 'Hotlink Protection is disabled', 'social-post-flow' ) . '</a>'
							),
							__( '- The Feature Image URL is directly accessible through your web browser, as a non-logged in WordPress User, with no force login, HTTP basic auth or fiewall / bot limiter preventing access.', 'social-post-flow' ),
							sprintf(
								/* translators: Link to Media File Renamer Plugin */
								__( '- Install %s to automatically remove spaces, accented or special characters in image filenames.', 'social-post-flow' ),
								'<a href="https://wordpress.org/plugins/media-file-renamer/" target="_blank">' . __( 'Media File Renamer Plugin', 'social-post-flow' ) . '</a>'
							),
							sprintf(
								/* translators: Link to SSL Labs Checker */
								__( '- %s, with no warnings of chain issues / intermediate certificate failures', 'social-post-flow' ),
								'<a href="https://www.ssllabs.com/ssltest/" target="_blank">' . __( 'Your site passes SSL tests', 'social-post-flow' ) . '</a>'
							),
							__( 'If the issue persists, please work with Buffer to resolve.', 'social-post-flow' ),
						);
					}
				}
				break;

			/**
			 * Cannot schedule updates in the past
			 */
			case 1034:
				if ( isset( $params['scheduled_at'] ) ) {
					$message[] = sprintf(
						/* translators: Scheduled Date and Time */
						__( 'The Custom Time (based on Custom Field / Post Meta Value) field cannot be %s, which is a date in the past.', 'social-post-flow' ),
						$params['scheduled_at']
					);
				} else {
					$message[] = sprintf(
						/* translators: %1$s: Link to WordPress General Settings, %2$s: Link to Social Media Scheduling Timezone Settings Screen */
						__( '<a href="%1$s">WordPress</a> and <a href="%2$s">Social Post Flow</a> timezones must match.', 'social-post-flow' ),
						admin_url( 'options-general.php' ),
						$this->get_timezone_settings_url( $params['profile_ids'][0] ),
					);
				}
				break;

		}

		// Return WP_Error.
		return new WP_Error(
			$body->code,
			sprintf(
				/* translators: %1$s: API Error Code, %2$s: API Error Message */
				__( 'Buffer API Error: #%1$s: %2$s', 'social-post-flow' ),
				$body->code,
				implode( "\n", $message )
			)
		);

	}

	/**
	 * Determines if the WordPress URL is a local, non-web accessible URL.
	 *
	 * @since   4.1.9
	 *
	 * @return  bool    Locally Hosted Site
	 */
	private function is_local_host() {

		// Get URL of site and its information.
		$url = wp_parse_url( get_bloginfo( 'url' ) );

		// Iterate through local host addresses to check if they exist
		// in part of the site's URL host.
		foreach ( $this->get_local_hosts() as $local_host ) {
			if ( strpos( $url['host'], $local_host ) !== false ) {
				return true;
			}
		}

		// If here, we're not on a local host.
		return false;

	}

	/**
	 * Returns an array of domains and IP addresses that are non-web accessible
	 *
	 * @since   4.1.9
	 *
	 * @return  array   Non-web accessible Domains and IP addresses
	 */
	private function get_local_hosts() {

		// If domain is 127.0.0.1, localhost or .dev, don't count it towards the domain limit
		// The user has a valid license key if they're here, so that's enough.
		// See: https://www.sqa.org.uk/e-learning/WebTech01CD/page_12.htm.
		$local_hosts = array(
			'localhost',
			'127.0.0.1',
			'10.0.',
			'192.168.',
			'.dev',
			'.local',
			'.localhost',
			'.test',
		);

		// Add 172.16.0.* to 172.16.31.*.
		for ( $i = 0; $i <= 31; $i++ ) {
			$local_hosts[] = '172.16.' . $i . '.';
		}

		return $local_hosts;

	}

}
