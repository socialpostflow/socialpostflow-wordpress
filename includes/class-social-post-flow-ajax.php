<?php
/**
 * AJAX class.
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
 */

/**
 * Registers AJAX actions for saving statuses, fetching usernames,
 * searching Taxonomy Terms etc.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */
class Social_Post_Flow_Ajax {

	/**
	 * Constructor
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

		add_action( 'wp_ajax_social_post_flow_usernames_search_facebook', array( $this, 'usernames_search_facebook' ) );
		add_action( 'wp_ajax_social_post_flow_save_statuses', array( $this, 'save_statuses' ) );
		add_action( 'wp_ajax_social_post_flow_save_statuses_post', array( $this, 'save_statuses_post' ) );
		add_action( 'wp_ajax_social_post_flow_get_status_row', array( $this, 'get_status_row' ) );
		add_action( 'wp_ajax_social_post_flow_character_count', array( $this, 'character_count' ) );
		add_action( 'wp_ajax_social_post_flow_get_log', array( $this, 'get_log' ) );
		add_action( 'wp_ajax_social_post_flow_clear_log', array( $this, 'clear_log' ) );
		add_action( 'wp_ajax_social_post_flow_search_terms', array( $this, 'search_terms' ) );
		add_action( 'wp_ajax_social_post_flow_search_authors', array( $this, 'search_authors' ) );
		add_action( 'wp_ajax_social_post_flow_search_roles', array( $this, 'search_roles' ) );
		add_action( 'wp_ajax_social_post_flow_bulk_publish', array( $this, 'bulk_publish' ) );
		add_action( 'wp_ajax_social_post_flow_repost_test', array( $this, 'repost_test' ) );

	}

	/**
	 * Searches for matching usernames on Facebook for the given search term,
	 * typically for Facebook autocomplete mentions.
	 *
	 * @since   1.0.0
	 */
	public function usernames_search_facebook() {

		// Run a security check first.
		check_ajax_referer( 'social-post-flow-usernames-search-facebook', 'nonce' );

		// Bail if no search term was provided.
		if ( ! isset( $_REQUEST['search'] ) ) {
			wp_send_json_error( __( 'No search term was provided.', 'social-post-flow' ) );
		}

		// Sanitize inputs.
		$search = sanitize_text_field( wp_unslash( $_REQUEST['search'] ) );

		// Run search.
		$results = social_post_flow()->get_class( 'api' )->facebook_usernames_search( $search );

		// Bail if an error occured.
		if ( is_wp_error( $results ) ) {
			wp_send_json_error( $results->get_error_message() );
		}

		// Add '@' before each username, and cast to a key/value array.
		$usernames = array();
		foreach ( $results->data as $index => $result ) {
			$usernames[ $index ] = array(
				'key'   => '@' . $result->name,
				'value' => '@' . $result->name . '[' . $result->id . ']',
			);
		}

		// Return usernames.
		wp_send_json_success( $usernames );

	}

	/**
	 * Saves statuses for the given Post Type in the Plugin's Settings section.
	 *
	 * @since   1.0.0
	 */
	public function save_statuses() {

		// Run a security check first.
		check_ajax_referer( 'social-post-flow-save-statuses', 'nonce' );

		// Bail if no post type was provided.
		if ( ! isset( $_REQUEST['post_type'] ) ) {
			wp_send_json_error( __( 'No post type was provided.', 'social-post-flow' ) );
		}
		if ( ! isset( $_REQUEST['statuses'] ) ) {
			wp_send_json_error( __( 'No statuses were provided.', 'social-post-flow' ) );
		}

		// Parse request.
		$post_type = sanitize_text_field( wp_unslash( $_REQUEST['post_type'] ) );
		$statuses  = json_decode( wp_unslash( $_REQUEST['statuses'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Get some other information.
		$post_type_object  = get_post_type_object( $post_type );
		$documentation_url = 'https://www.socialpostflow.com/documentation/wordpress-plugin/status-configuration-and-types/';

		// Save and return.
		$result = social_post_flow()->get_class( 'settings' )->update_settings( $post_type, $statuses );

		// Bail if an error occured.
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// Return success, with flag denoting if the Post Type is configured to send statuses.
		wp_send_json_success(
			array(
				'post_type_enabled' => social_post_flow()->get_class( 'settings' )->is_post_type_enabled( $post_type ),
			)
		);

	}

	/**
	 * Saves statuses for the given Post
	 *
	 * @since   1.0.0
	 */
	public function save_statuses_post() {

		// Run a security check first.
		check_ajax_referer( 'social-post-flow-save-statuses-post', 'nonce' );

		// Bail if no post ID was provided.
		if ( ! isset( $_REQUEST['post_id'] ) ) {
			wp_send_json_error( __( 'No post ID was provided.', 'social-post-flow' ) );
		}

		// Parse request to build Post compliant settings array.
		// Don't wp_unslash(); save_settings() does this, which would result in double unslashing and
		// errors with character encoding and newlines being lost.
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput
		$post_id  = absint( $_REQUEST['post_id'] );
		$settings = array(
			'featured_image'    => $_REQUEST['featured_image'],
			'additional_images' => $_REQUEST['additional_images'],
			'override'          => $_REQUEST['override'],
			'statuses'          => $_REQUEST['statuses'],
		);
		// phpcs:enable

		// Save and return.
		$result = social_post_flow()->get_class( 'post' )->save_settings( $post_id, $settings );

		// Bail if an error occured.
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// Return success, with flag denoting if the Post Type is configured to send statuses.
		wp_send_json_success(
			array(
				'post_type_enabled' => true,
			)
		);

	}

	/**
	 * Returns HTML markup that can be injected inside a <tr> to show the status' information
	 *
	 * @since   1.0.0
	 */
	public function get_status_row() {

		// Run a security check first.
		check_ajax_referer( 'social-post-flow-get-status-row', 'nonce' );

		// Bail if expect parameters were not was provided.
		if ( ! isset( $_REQUEST['status'] ) ) {
			wp_send_json_error( __( 'No status was provided.', 'social-post-flow' ) );
		}
		if ( ! isset( $_REQUEST['post_type'] ) ) {
			wp_send_json_error( __( 'No post type was provided.', 'social-post-flow' ) );
		}
		if ( ! isset( $_REQUEST['post_action'] ) ) {
			wp_send_json_error( __( 'No post action was provided.', 'social-post-flow' ) );
		}

		// Parse request.
		$status    = json_decode( wp_unslash( $_REQUEST['status'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$post_type = sanitize_text_field( wp_unslash( $_REQUEST['post_type'] ) );
		$action    = sanitize_text_field( wp_unslash( $_REQUEST['post_action'] ) );

		// Return array of row data (message, image, schedule).
		wp_send_json_success( social_post_flow()->get_class( 'settings' )->get_status_row( $status, $post_type, $action ) );

	}

	/**
	 * Renders the given status and Post to calculate the character count on a status
	 * when using the "Post using Manual Settings" option.
	 *
	 * @since   1.0.0
	 */
	public function character_count() {

		// Run a security check first.
		check_ajax_referer( 'social-post-flow-character-count', 'nonce' );

		// Bail if expected parameters were not provided.
		if ( ! isset( $_REQUEST['post_id'] ) ) {
			wp_send_json_error( __( 'No post ID was provided.', 'social-post-flow' ) );
		}
		if ( ! isset( $_REQUEST['status'] ) ) {
			wp_send_json_error( __( 'No status was provided.', 'social-post-flow' ) );
		}

		// Get post and status.
		$post   = get_post( absint( $_REQUEST['post_id'] ) );
		$status = sanitize_text_field( wp_unslash( $_REQUEST['status'] ) );

		// Parse status.
		$parsed_status = social_post_flow()->get_class( 'publish' )->parse_text( $post, $status );

		// Return parsed status and character count.
		wp_send_json_success(
			array(
				'status'          => $status,
				'parsed_status'   => $parsed_status,
				'character_count' => strlen( $parsed_status ),
			)
		);

	}

	/**
	 * Fetches the plugin log for the given Post ID, in HTML format
	 * compatible for insertion into the Log Table.
	 *
	 * @since   1.0.0
	 */
	public function get_log() {

		// Run a security check first.
		check_ajax_referer( 'social-post-flow-get-log', 'nonce' );

		// Bail if no post ID was provided.
		if ( ! isset( $_REQUEST['post'] ) ) {
			wp_send_json_error( __( 'No post ID was provided.', 'social-post-flow' ) );
		}

		// Get Post ID.
		$post_id = absint( $_REQUEST['post'] );

		// Return log table output.
		wp_send_json_success( social_post_flow()->get_class( 'log' )->build_log_table_output( social_post_flow()->get_class( 'log' )->get( $post_id ) ) );

	}

	/**
	 * Clears the plugin log for the given Post ID
	 *
	 * @since   1.0.0
	 */
	public function clear_log() {

		// Run a security check first.
		check_ajax_referer( 'social-post-flow-clear-log', 'nonce' );

		// Bail if no post ID was provided.
		if ( ! isset( $_REQUEST['post'] ) ) {
			wp_send_json_error( __( 'No post ID was provided.', 'social-post-flow' ) );
		}

		// Get Post ID.
		$post_id = absint( $_REQUEST['post'] );

		// Clear log.
		social_post_flow()->get_class( 'log' )->delete_by_post_id( $post_id );

		wp_send_json_success();

	}

	/**
	 * Searches for Taxonomy Terms for the given Taxonomy and freeform text
	 *
	 * @since   1.0.0
	 */
	public function search_terms() {

		// Run a security check first.
		check_ajax_referer( 'social-post-flow-search-terms', 'nonce' );

		// Bail if expected parameters were not provided.
		if ( ! isset( $_REQUEST['taxonomy'] ) ) {
			wp_send_json_error( __( 'No taxonomy was provided.', 'social-post-flow' ) );
		}
		if ( ! isset( $_REQUEST['q'] ) ) {
			wp_send_json_error( __( 'No search term was provided.', 'social-post-flow' ) );
		}

		// Get vars.
		$taxonomy = sanitize_text_field( wp_unslash( $_REQUEST['taxonomy'] ) );
		$search   = sanitize_text_field( wp_unslash( $_REQUEST['q'] ) );

		// Get results.
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'orderby'    => 'name',
				'order'      => 'ASC',
				'hide_empty' => 0,
				'number'     => 0,
				'fields'     => 'id=>name',
				'search'     => $search,
			)
		);

		// If an error occured, bail.
		if ( is_wp_error( $terms ) ) {
			return wp_send_json_error( $terms->get_error_message() );
		}

		// Build array.
		$terms_array = array();
		foreach ( $terms as $term_id => $name ) {
			$terms_array[] = array(
				'id'   => $term_id,
				'text' => $name,
			);
		}

		// Done.
		wp_send_json_success( $terms_array );

	}

	/**
	 * Searches for Authors for the given freeform text
	 *
	 * @since   1.0.0
	 */
	public function search_authors() {

		// Run a security check first.
		check_ajax_referer( 'social-post-flow-search-authors', 'nonce' );

		// Bail if expected parameters were not provided.
		if ( ! isset( $_REQUEST['q'] ) ) {
			return wp_send_json_error( __( 'No search term was provided.', 'social-post-flow' ) );
		}

		// Get vars.
		$query = sanitize_text_field( wp_unslash( $_REQUEST['q'] ) );

		// Get results.
		$users = new WP_User_Query(
			array(
				'search' => '*' . $query . '*',
			)
		);

		// If an error occured, bail.
		if ( is_wp_error( $users ) ) {
			return wp_send_json_error( $users->get_error_message() );
		}

		// Build array.
		$users_array = array();
		$results     = $users->get_results();
		if ( ! empty( $results ) ) {
			foreach ( $results as $user ) {
				$users_array[] = array(
					'id'   => $user->ID,
					'text' => $user->user_login,
				);
			}
		}

		// Done.
		wp_send_json_success( $users_array );

	}

	/**
	 * Searches for Roles for the given freeform text
	 *
	 * @since   1.0.0
	 */
	public function search_roles() {

		// Run a security check first.
		check_ajax_referer( 'social-post-flow-search-roles', 'nonce' );

		if ( ! isset( $_REQUEST['q'] ) ) {
			return wp_send_json_error( __( 'No search term was provided.', 'social-post-flow' ) );
		}

		// Get vars.
		$query = sanitize_text_field( wp_unslash( $_REQUEST['q'] ) );

		// Get results.
		$results = array();
		foreach ( wp_roles()->roles as $role => $permissions ) {
			if ( stripos( $role, $query ) !== false ) {
				$results[] = array(
					'id'   => $role,
					'text' => $role,
				);
			}
		}

		// Done.
		wp_send_json_success( $results );

	}

	/**
	 * Sends a publish request for the next Post ID in the index sequence.
	 * Used for bulk publishing
	 *
	 * @since   1.0.0
	 */
	public function bulk_publish() {

		// Run a security check first.
		check_ajax_referer( 'social-post-flow-bulk-publish', 'nonce' );

		// Check required POST variables have been set.
		if ( ! isset( $_POST['current_index'] ) ) {
			wp_send_json_error(
				'
                <tr><th colspan="8">' . __( 'Error', 'social-post-flow' ) . '</th></tr>
                <tr><td colspan="8">' . __( 'Error: current_index parameter missing from request.', 'social-post-flow' ) . '</td></tr>'
			);
		}
		if ( ! isset( $_POST['id'] ) ) {
			wp_send_json_error(
				'
                <tr><th colspan="8">' . __( 'Error', 'social-post-flow' ) . '</th></tr>
                <tr><td colspan="8">' . __( 'Error: id parameter missing from request.', 'social-post-flow' ) . '</td></tr>'
			);
		}
		if ( ! isset( $_POST['number_requests'] ) ) {
			wp_send_json_error(
				'
                <tr><th colspan="8">' . __( 'Error', 'social-post-flow' ) . '</th></tr>
                <tr><td colspan="8">' . __( 'Error: number_requests parameter missing from request.', 'social-post-flow' ) . '</td></tr>'
			);
		}

		// Get required POST variables.
		$current_index   = absint( $_POST['current_index'] );
		$post_id         = absint( $_POST['id'] );
		$number_requests = absint( $_POST['number_requests'] );

		// Get Test Mode Flag.
		$test_mode = social_post_flow()->get_class( 'settings' )->get_option( 'test_mode', false );

		// Publish statuses using the 'bulk_publish' action.
		$results = social_post_flow()->get_class( 'publish' )->publish( $post_id, 'bulk_publish', $test_mode );

		// If no results were returned, bail with an error.
		if ( ! isset( $results ) ) {
			wp_send_json_error(
				'
                <tr><th colspan="8">' . ( $current_index + 1 ) . '/' . $number_requests . ': ' . get_the_title( $post_id ) . '</th></tr>
                <tr><td colspan="8">' . __( 'Error: No response was received.', 'social-post-flow' ) . '</td></tr>'
			);
			die();
		}

		// If the overall result is a WP error, bail with an error.
		if ( is_wp_error( $results ) ) {
			// Build log.
			$log = array(
				'action'         => 'bulk_publish',
				'request_sent'   => date( 'Y-m-d H:i:s' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				'result'         => 'warning',
				'result_message' => $results->get_error_message(),
			);

			// If logging is enabled, log the warning.
			if ( social_post_flow()->get_class( 'settings' )->get_option( 'log', '[enabled]' ) ) {
				social_post_flow()->get_class( 'log' )->add( $post_id, $log );
			}

			// Return log.
			wp_send_json_error(
				'
                <tr><th colspan="8">' . ( $current_index + 1 ) . '/' . $number_requests . ': ' . get_the_title( $post_id ) . '</th></tr>' .
				social_post_flow()->get_class( 'log' )->build_log_table_output(
					array(
						$log,
					)
				)
			);
			die();
		}

		// Build table HTML log.
		wp_send_json_success(
			'<tr><th colspan="8">' . ( $current_index + 1 ) . '/' . $number_requests . ': ' . get_the_title( $post_id ) . '</th></tr>' .
			social_post_flow()->get_class( 'log' )->build_log_table_output( $results )
		);
		die();

	}

	/**
	 * Tests the Repost functionality as if it were triggered by WordPress' Cron now
	 *
	 * @since   1.0.0
	 */
	public function repost_test() {

		// Run a security check first.
		check_ajax_referer( 'social-post-flow-repost-test', 'nonce' );

		social_post_flow()->get_class( 'cron' )->repost( true );
		wp_send_json_success( social_post_flow()->get_class( 'log' )->get_debug_log() );
		die();

	}

}
