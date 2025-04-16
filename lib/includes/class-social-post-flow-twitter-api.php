<?php
/**
 * WP Zinc Twitter API class
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * API class to handle returning a Twitter username for a given Twitter User ID,
 * checking the site's transient first before falling back to the WP Zinc API.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 1.0.0
 */
class Social_Post_Flow_Twitter_API extends Social_Post_Flow_API {

	/**
	 * Holds the base class object.
	 *
	 * @since   3.7.3
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Holds the API endpoint
	 *
	 * @since   3.7.3
	 *
	 * @var     string
	 */
	public $api_endpoint = 'https://www.wpzinc.com/?twitter_api=1';

	/**
	 * Constructor
	 *
	 * @since   3.7.3
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct() {

		

	}

	/**
	 * Returns the username for the given Twitter User ID.
	 *
	 * @since   3.7.3
	 *
	 * @param   int $user_id                    User ID.
	 * @param   int $transient_expiration_time  Transient Expiration Time.
	 * @return  WP_Error|string
	 */
	public function get_username_by_id( $user_id, $transient_expiration_time ) {

		// Return Twitter username from cache, if it exists.
		$twitter_username = $this->get_cached_username( $user_id );
		if ( $twitter_username ) {
			return $twitter_username;
		}

		// Fetch Twitter Username from API.
		$twitter_username = $this->post(
			'users_lookup',
			array(
				'input' => $user_id,
			)
		);

		// Bail if an error occured.
		if ( is_wp_error( $twitter_username ) ) {
			return $twitter_username;
		}

		// Cache the Twitter Username.
		$this->add_cached_username( $user_id, $twitter_username, $transient_expiration_time );

		// Finally, return the Twitter Username.
		return $twitter_username;

	}

	/**
	 * Sends the given User ID and Username mapping to the WP Zinc API, so it can be stored
	 * in the WP Zinc API for future lookups.
	 *
	 * As this is called via AJAX when using WebSockets to fetch a Twitter username
	 * by ID, we don't care about the WP Zinc API's response, because the user cannot
	 * do anything with it.
	 *
	 * @since   5.0.2
	 *
	 * @param   int    $user_id    User ID.
	 * @param   string $username   Username.
	 * @return  WP_Error|array
	 */
	public function username_save( $user_id, $username ) {

		return $this->post(
			'username_save',
			array(
				'user_id'  => $user_id,
				'username' => $username,
			)
		);

	}

	/**
	 * Returns all Twitter User ID to Username mappings stored in the transient.
	 *
	 * @since   5.0.2
	 *
	 * @return  array
	 */
	private function get_cached_usernames() {

		// Get transient data.
		$twitter_ids_usernames = get_transient( 'social_post_flow_twitter_api_usernames' );
		if ( ! is_array( $twitter_ids_usernames ) ) {
			return array();
		}

		return $twitter_ids_usernames;

	}

	/**
	 * Returns the cached Twitter Username stored in the transient for the given Twitter User ID.
	 *
	 * @since   5.0.2
	 *
	 * @param   int $user_id    User ID.
	 * @return  bool|string
	 */
	private function get_cached_username( $user_id ) {

		// Get transient data.
		$twitter_ids_usernames = $this->get_cached_usernames();

		// If we have a username for this user ID, return the ID now.
		if ( array_key_exists( $user_id, $twitter_ids_usernames ) ) {
			return $twitter_ids_usernames[ $user_id ];
		}

		return false;

	}

	/**
	 * Adds the given Twitter User ID and Username mapping to the transient.
	 *
	 * @since   5.0.2
	 *
	 * @param   int    $user_id                    User ID.
	 * @param   string $username                   Username.
	 * @param   int    $transient_expiration_time  Transient Expiration Time.
	 */
	private function add_cached_username( $user_id, $username, $transient_expiration_time ) {

		// Get transient.
		$twitter_ids_usernames = $this->get_cached_usernames();

		// Store the Twitter ID and Username in the transient.
		$twitter_ids_usernames[ $user_id ] = $username;

		// Update transient.
		set_transient( 'social_post_flow_twitter_api_usernames', $twitter_ids_usernames, $transient_expiration_time );

	}

}
