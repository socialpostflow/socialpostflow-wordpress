<?php
/**
 * WP Zinc Facebook API class
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Calls WP Zinc's API to perform ID to username lookups.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 4.5.7
 */
class Social_Post_Flow_Facebook_API extends Social_Post_Flow_API {

	/**
	 * Holds the base class object.
	 *
	 * @since   4.5.7
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Holds the API endpoint
	 *
	 * @since   4.5.7
	 *
	 * @var     string
	 */
	public $api_endpoint = 'https://www.wpzinc.com/?facebook_api=1';

	/**
	 * Constructor
	 *
	 * @since   4.5.7
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct() {

		

	}

	/**
	 * Returns usernames for the given search term
	 *
	 * @since   4.5.7
	 *
	 * @param   string $search     Search Term.
	 * @return  mixed               WP_Error | array
	 */
	public function usernames_search( $search ) {

		return $this->post(
			'users_search',
			array(
				'input' => $search,
			)
		);

	}

}
