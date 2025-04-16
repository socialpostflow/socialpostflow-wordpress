<?php
/**
 * WP Zinc Pinterest API class
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Calls WP Zinc's API to perform URL to Pinterest Board ID lookups.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 1.0.0
 */
class Social_Post_Flow_Pinterest_API extends Social_Post_Flow_API {

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
	public $api_endpoint = 'https://www.wpzinc.com/?pinterest_api=1';

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
	 * Returns the board ID for the given board URL
	 *
	 * @since   3.7.3
	 *
	 * @param   string $url                        Board URL.
	 * @param   int    $transient_expiration_time  Transient Expiration Time.
	 * @return  mixed                               WP_Error | int
	 */
	public function get_board_id_by_url( $url, $transient_expiration_time ) {

		// Get transient data.
		$board_ids_urls = get_transient( 'social_post_flow_pinterest_api_boards' );
		if ( ! is_array( $board_ids_urls ) ) {
			$board_ids_urls = array();
		}

		// If we have a board ID for this board URL, return the ID now.
		if ( is_array( $board_ids_urls ) && in_array( $url, $board_ids_urls, true ) ) {
			return array_search( $url, $board_ids_urls, true );
		}

		// Fetch Board ID.
		$board = $this->post( $url );

		// Bail if an error occured.
		if ( is_wp_error( $board ) ) {
			return $board;
		}

		// Store the Board URL and ID in the transient.
		$board_ids_urls[ $board->id ] = $url;
		set_transient( 'social_post_flow_pinterest_api_boards', $board_ids_urls, $transient_expiration_time );

		// Finally, return the board ID.
		return $board->id;

	}

}
