<?php
/**
 * Log class.
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Handles logging and log output.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 3.0.0
 */
class Social_Post_Flow_Log {

	/**
	 * Holds the base class object.
	 *
	 * @since   3.2.0
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Holds the DB table name
	 *
	 * @since   3.9.6
	 *
	 * @var     string
	 */
	private $table;

	/**
	 * Holds items added to the debug log using add_to_debug_log(),
	 *
	 * @since   4.1.8
	 *
	 * @var     array
	 */
	private $debug_log = array();

	/**
	 * Constructor
	 *
	 * @since   3.0.0
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct() {

		

		// Define the database table name.
		$this->table = 'to_' . strtolower( $this->base->plugin->account ) . '_log';

		// Actions.
		add_filter( 'set-screen-option', array( $this, 'set_screen_options' ), 10, 3 );
		add_action( 'current_screen', array( $this, 'run_log_table_bulk_actions' ) );
		add_action( 'current_screen', array( $this, 'run_log_table_filters' ) );
		add_action( 'admin_menu', array( $this, 'admin_meta_boxes' ) );
		add_action( 'wp_loaded', array( $this, 'export' ) );

	}

	/**
	 * Activation routines for this Model
	 *
	 * @since   3.9.6
	 *
	 * @global  $wpdb   WordPress DB Object
	 */
	public function activate() {

		global $wpdb;

		// Enable error output if WP_DEBUG is enabled.
		$wpdb->show_errors = true;

		// Create database tables.
		$query = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . $this->table . " (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `post_id` int(11) NOT NULL,
            `action` enum('publish','update','repost','bulk_publish') DEFAULT NULL,
            `request_sent` datetime NOT NULL,
            `profile_id` varchar(191) NOT NULL,
            `profile_name` varchar(191) NOT NULL DEFAULT '',
            `result` enum('success','test','pending','warning','error') NOT NULL DEFAULT 'success',
            `result_message` text,
            `status_text` text,
            `status_created_at` datetime DEFAULT NULL,
            `status_due_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `post_id` (`post_id`),
            KEY `action` (`action`),
            KEY `result` (`result`),
            KEY `profile_id` (`profile_id`)
        ) " . $wpdb->get_charset_collate() . ' AUTO_INCREMENT=1';

		$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Checks if the log is enabled
	 *
	 * @since   4.2.0
	 *
	 * @return  bool    Logging Enabled
	 */
	public function is_enabled() {

		// Get Log Settings.
		$log_settings = social_post_flow()->get_class( 'settings' )->get_option( 'log', false );

		// Logging disabled if no settings.
		if ( ! $log_settings ) {
			return false;
		}

		// Logging disabled if no setting.
		if ( ! isset( $log_settings['enabled'] ) ) {
			return false;
		}

		// Return.
		return absint( $log_settings['enabled'] );

	}

	/**
	 * Sets values for options displayed in the Screen Options dropdown on the Logs
	 * WP_List_Table
	 *
	 * @since   4.3.0
	 *
	 * @param   mixed  $screen_option  The value to save instead of the option value. Default false (to skip saving the current option).
	 * @param   string $option         The option name.
	 * @param   string $value          The option value.
	 * @return  string                  The option value
	 */
	public function set_screen_options( $screen_option, $option, $value ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		return $value;

	}

	/**
	 * Defines options to display in the Screen Options dropdown on the Logs
	 * WP_List_Table
	 *
	 * @since   4.3.0
	 */
	public function add_screen_options() {

		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Log Entries per Page', 'social-post-flow' ),
				'default' => 20,
				'option'  => 'social_post_flow_logs_per_page',
			)
		);

		// Initialize Logs WP_List_Table, as this will trigger WP_List_Table to add column options.
		$log_table = new Social_Post_Flow_Log_Table( $this->base );

	}

	/**
	 * Run any bulk actions on the Log WP_List_Table
	 *
	 * @since   3.9.6
	 */
	public function run_log_table_bulk_actions() {

		// Get screen.
		$screen = social_post_flow()->get_class( 'screen' )->get_current_screen();

		// Bail if we're not on the Log screen.
		if ( $screen['screen'] !== 'log' ) {
			return;
		}

		// Bail if nonce is not valid.
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-wp-to-social-log' ) ) {
			return;
		}

		// Get bulk action from the fields that might contain it.
		$bulk_action = array_values(
			array_filter(
				array(
					( isset( $_REQUEST['bulk_action'] ) && $_REQUEST['bulk_action'] != -1 ? sanitize_text_field( wp_unslash( $_REQUEST['bulk_action'] ) ) : '' ),  // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual, WordPress.Security.NonceVerification
					( isset( $_REQUEST['bulk_action2'] ) && $_REQUEST['bulk_action2'] != -1 ? sanitize_text_field( wp_unslash( $_REQUEST['bulk_action2'] ) ) : '' ),  // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual, WordPress.Security.NonceVerification
					( isset( $_REQUEST['bulk_action3'] ) && ! empty( $_REQUEST['bulk_action3'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['bulk_action3'] ) ) : '' ), // phpcs:ignore WordPress.Security.NonceVerification
				)
			)
		);

		// Bail if no bulk action.
		if ( ! is_array( $bulk_action ) ) {
			return;
		}
		if ( ! count( $bulk_action ) ) {
			return;
		}

		// Setup notices class, enabling persistent storage.
		social_post_flow()->get_class( 'notices' )->enable_store();
		social_post_flow()->get_class( 'notices' )->set_key_prefix( 'social_post_flow_' . wp_get_current_user()->ID );

		// Perform Bulk Action.
		switch ( $bulk_action[0] ) {
			/**
			 * Delete Logs
			 */
			case 'delete':
				// Get Post IDs.
				if ( ! isset( $_REQUEST['ids'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					social_post_flow()->get_class( 'notices' )->add_error_notice(
						__( 'No logs were selected for deletion.', 'social-post-flow' )
					);
					break;
				}

				// Delete Logs by IDs.
				$this->delete_by_ids( array_values( wp_unslash( $_REQUEST['ids'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				// Add success notice.
				social_post_flow()->get_class( 'notices' )->add_success_notice(
					sprintf(
						/* translators: Number of log entries deleted */
						__( '%s Logs deleted.', 'social-post-flow' ),
						count( $_REQUEST['ids'] ) // phpcs:ignore WordPress.Security.NonceVerification
					)
				);
				break;

			/**
			 * Delete All Logs
			 */
			case 'delete_all':
				// Delete Logs.
				$this->delete_all();

				// Add success notice.
				social_post_flow()->get_class( 'notices' )->add_success_notice(
					__( 'All Logs deleted.', 'social-post-flow' )
				);
				break;

		}

		// Redirect.
		wp_safe_redirect( 'admin.php?page=social-post-flow-' . $screen['screen'] );
		die();

	}

	/**
	 * Redirect POST filters to a GET URL
	 *
	 * @since   3.9.6
	 */
	public function run_log_table_filters() {

		// Get screen.
		$screen = social_post_flow()->get_class( 'screen' )->get_current_screen();

		// Bail if we're not on the Log screen.
		if ( $screen['screen'] !== 'log' ) {
			return;
		}

		// Bail if nonce is not valid.
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-wp-to-social-log' ) ) {
			return;
		}

		$params = array();
		foreach ( social_post_flow()->get_class( 'common' )->get_log_filters() as $filter ) {
			if ( ! isset( $_POST[ $filter ] ) ) {
				continue;
			}
			if ( empty( $_POST[ $filter ] ) ) {
				continue;
			}

			$params[ $filter ] = sanitize_text_field( wp_unslash( $_POST[ $filter ] ) );
		}

		// If params don't exist, exit.
		if ( ! count( $params ) ) {
			return;
		}

		// Redirect.
		wp_safe_redirect( 'admin.php?page=social-post-flow-' . $screen['screen'] . '&' . http_build_query( $params ) );
		die();

	}

	/**
	 * Adds Metaboxes to Post Edit Screens
	 *
	 * @since   3.0.0
	 */
	public function admin_meta_boxes() {

		// Only load if Logging is enabled and Displayed on Posts.
		$log_enabled          = $this->is_enabled();
		$log_display_on_posts = social_post_flow()->get_class( 'settings' )->get_setting( 'log', '[display_on_posts]' );

		if ( ! $log_enabled ) {
			return;
		}
		if ( ! $log_display_on_posts ) {
			return;
		}

		// Check if we need to hide the meta box by the logged in User's role.
		$hide_meta_box = social_post_flow()->get_class( 'settings' )->get_setting( 'hide_meta_box_by_roles', '[' . wp_get_current_user()->roles[0] . ']' );

		// Bail if we're hiding the meta boxes for the logged in User's role.
		if ( $hide_meta_box ) {
			return;
		}

		// Get Post Types.
		$post_types = social_post_flow()->get_class( 'common' )->get_post_types();

		// Add meta boxes for each Post Type.
		foreach ( $post_types as $post_type => $post_type_obj ) {
			add_meta_box(
				'social-post-flow-log',
				sprintf(
					/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
					__( '%s Log', 'social-post-flow' ),
					$this->base->plugin->displayName
				),
				array( $this, 'output_post_log' ),
				$post_type,
				'normal',
				'low'
			);
		}

	}

	/**
	 * Outputs the plugin's log of existing status update calls made to the API
	 *
	 * @since   3.0.0
	 *
	 * @param   WP_Post $post   Post.
	 */
	public function output_post_log( $post ) {

		// Get log.
		$log = $this->get( $post->ID );

		// Load View.
		include_once $this->base->plugin->folder . 'lib/views/post-log.php';

	}

	/**
	 * Exports a Post's API log file in JSON format
	 *
	 * @since   3.0.0
	 */
	public function export() {

		// Check the user requested a log.
		if ( ! isset( $_GET[ 'social-post-flow-export-log' ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		// Bail if no post specified.
		if ( ! isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		// Check the user is logged in and can edit posts in order to access the log.
		if ( ! function_exists( 'current_user_can' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', absint( $_GET['post'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		// Get log.
		$log = $this->get( absint( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		// Build JSON.
		$json = wp_json_encode( $log );

		// Export.
		header( 'Content-type: application/x-msdownload' );
		header( 'Content-Disposition: attachment; filename=log.json' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput
		exit();

	}

	/**
	 * Adds a log entry for the given Post ID
	 *
	 * @since   3.9.6
	 *
	 * @param   int   $post_id    Post ID.
	 * @param   array $log        Log.
	 *    enum            $action             Action (publish,update,repost,bulk_publish).
	 *    datetime        $request_sent       Request Sent to API.
	 *    string          $profile_id         Profile ID.
	 *    string          $profile_name       Profile Name.
	 *    enum            $result             Result (success,test_mode,pending,error).
	 *    string          $result_message     Result Message.
	 *    string          $status_text        Status Text.
	 *    datetime        $status_created_at  Status Created At on API.
	 *    datetime        $status_due_at      Status Scheduled for Publication to Profile.
	 */
	public function add( $post_id, $log ) {

		global $wpdb;

		// Fetch Log Levels that are enabled in the Plugin Settings.
		$log_levels = social_post_flow()->get_class( 'settings' )->get_setting( 'log', '[log_level]' );

		// Bail if the Log Result doesn't match a level that we're saving to the log table.
		if ( ! in_array( $log['result'], $log_levels, true ) ) {
			return;
		}

		// Enable error output if WP_DEBUG is enabled.
		$wpdb->show_errors();

		// Add Post ID to log.
		$log['post_id'] = absint( $post_id );

		// Insert Log.
		$result = $wpdb->insert(
			$wpdb->prefix . $this->table,
			$log
		);

	}

	/**
	 * Retrieves the log for the given Post ID
	 *
	 * @since   3.0.0
	 *
	 * @param   int $post_id    Post ID.
	 * @return  array               Log
	 */
	public function get( $post_id ) {

		global $wpdb;

		// Get log.
		$log = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}{$this->table} WHERE post_id = %d ORDER BY id DESC", // phpcs:ignore WordPress.DB.PreparedSQL
				absint( $post_id )
			),
			ARRAY_A
		);

		/**
		 * Filters the log entries before output.
		 *
		 * @since   3.0.0
		 *
		 * @param   array   $log        Post Log.
		 * @param   int     $post_id    Post ID.
		 */
		$log = apply_filters( 'social_post_flow_get_log', $log, $post_id );

		// Return.
		return $log;

	}

	/**
	 * Returns key/value Profile ID and Name pairs based on all unique
	 * Profile IDs in the Log table
	 *
	 * @since   3.9.6
	 *
	 * @return  array
	 */
	public function get_profile_id_names() {

		global $wpdb;

		$results = $wpdb->get_results(
			"SELECT profile_id, profile_name FROM {$wpdb->prefix}{$this->table} GROUP BY profile_id ORDER BY profile_name DESC", // phpcs:ignore WordPress.DB.PreparedSQL
			ARRAY_A
		);

		if ( ! $results || ! count( $results ) ) {
			return array();
		}

		$profiles = array();
		foreach ( $results as $result ) {
			if ( empty( $result['profile_id'] ) ) {
				continue;
			}
			$profiles[ $result['profile_id'] ] = ( empty( $result['profile_name'] ) ? __( 'Unknown', 'social-post-flow' ) : $result['profile_name'] );
		}

		return $profiles;

	}

	/**
	 * Defines the available Log Result Options
	 *
	 * @since   4.2.0
	 *
	 * @return  array   Result Options (success,test,warning,error).
	 */
	public function get_result_options() {

		// Define log result options.
		$result_options = array(
			'success' => __( 'Success', 'social-post-flow' ),
			'test'    => __( 'Test', 'social-post-flow' ),
			'warning' => __( 'Warning', 'social-post-flow' ),
			'error'   => __( 'Error', 'social-post-flow' ),
		);

		/**
		 * Defines the available result options
		 *
		 * @since   4.2.0
		 *
		 * @param   array   $result_options   Result Options.
		 */
		$result_options = apply_filters( 'social_post_flow_log_get_result_options', $result_options );

		// Return filtered results.
		return $result_options;

	}

	/**
	 * Returns the available Log Levels
	 *
	 * @since   4.2.0
	 *
	 * @return  array   Log Levels
	 */
	public function get_level_options() {

		// Define log levels.
		$log_levels = array(
			'success' => __( 'Success', 'social-post-flow' ),
			'test'    => __( 'Tests', 'social-post-flow' ),
			'pending' => __( 'Pending', 'social-post-flow' ),
			'warning' => __( 'Warnings', 'social-post-flow' ),
			'error'   => __( 'Errors', 'social-post-flow' ),
		);

		/**
		 * Defines the available log levels
		 *
		 * @since   4.2.0
		 *
		 * @param   array   $log_levels   Log Levels.
		 */
		$log_levels = apply_filters( 'social_post_flow_log_get_log_levels', $log_levels );

		// Return filtered results.
		return $log_levels;

	}

	/**
	 * Searches logs by the given key/value pairs
	 *
	 * @since   3.9.6
	 *
	 * @param   string $order_by   Order Results By.
	 * @param   string $order      Order (asc|desc).
	 * @param   int    $page       Pagination Offset (default: 0).
	 * @param   int    $per_page   Number of Results to Return (default: 20).
	 * @param   mixed  $params     Query Parameters (false = all records).
	 * @return  array              Log entries
	 */
	public function search( $order_by, $order, $page = 0, $per_page = 20, $params = false ) {

		global $wpdb;

		// Build where clauses.
		$where = $this->build_where_clause( $params );

		// Prepare query.
		$query = '  SELECT * FROM ' . $wpdb->prefix . $this->table . '
                    LEFT JOIN ' . $wpdb->posts . '
                    ON ' . $wpdb->prefix . $this->table . '.post_id = ' . $wpdb->posts . '.ID';

		// Add where clauses.
		if ( $where !== false ) {
			$query .= ' WHERE ' . $where;
		}

		// Order.
		$query .= ' ORDER BY ' . $wpdb->prefix . $this->table . '.' . $order_by . ' ' . $order;

		// Limit.
		if ( $page > 0 && $per_page > 0 ) {
			$query .= $wpdb->prepare( ' LIMIT %d, %d', ( ( $page - 1 ) * $per_page ), $per_page );
		}

		// Run and return query results.
		return $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL

	}

	/**
	 * Gets the number of log records found for the given query parameters
	 *
	 * @since   3.9.6
	 *
	 * @param   mixed $params     Query Parameters (false = all records).
	 * @return  int                 Total Records
	 */
	public function total( $params = false ) {

		global $wpdb;

		// Build where clauses.
		$where = $this->build_where_clause( $params );

		// Prepare query.
		$query = '  SELECT COUNT(' . $wpdb->prefix . $this->table . '.id) FROM ' . $wpdb->prefix . $this->table . '
                    LEFT JOIN ' . $wpdb->posts . '
                    ON ' . $wpdb->prefix . $this->table . '.post_id = ' . $wpdb->posts . '.ID';

		// Add where clauses.
		if ( $where !== false ) {
			$query .= ' WHERE ' . $where;
		}

		// Run and return total records found.
		return $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL

	}

	/**
	 * Builds a WHERE SQL clause based on the given column key/values
	 *
	 * @since   3.9.6
	 *
	 * @param   array $params     Query Parameters (false = all records).
	 * @return  string              WHERE SQL clause
	 */
	private function build_where_clause( $params ) {

		// Bail if no params.
		if ( ! $params ) {
			return false;
		}

		// Build where clauses.
		$where = array();
		if ( $params !== false && is_array( $params ) && count( $params ) > 0 ) {
			foreach ( $params as $key => $value ) {
				// Skip blank params.
				if ( empty( $value ) ) {
					continue;
				}

				// Build condition based on the key.
				switch ( $key ) {
					case 'post_title':
						$where[] = $key . " LIKE '%" . $value . "%'";
						break;

					case 'request_sent_start_date':
						if ( ! empty( $params['request_sent_end_date'] ) && $params['request_sent_start_date'] > $params['request_sent_end_date'] ) {
							$where[] = "request_sent <= '" . $value . " 23:59:59'";
						} else {
							$where[] = "request_sent >= '" . $value . " 00:00:00'";
						}
						break;

					case 'request_sent_end_date':
						if ( ! empty( $params['request_sent_start_date'] ) && $params['request_sent_start_date'] > $params['request_sent_end_date'] ) {
							$where[] = "request_sent >= '" . $value . " 00:00:00'";
						} else {
							$where[] = "request_sent <= '" . $value . " 23:59:59'";
						}
						break;

					/**
					 * Post Title
					 */
					case 'post_title':
						$where[] = $key . " LIKE '%" . $value . "%'";
						break;

					default:
						$where[] = $key . " = '" . $value . "'";
						break;
				}
			}
		}

		if ( ! count( $where ) ) {
			return false;
		}

		return implode( ' AND ', $where );

	}

	/**
	 * Deletes a single Log entry for the given Log ID
	 *
	 * @since   3.9.6
	 *
	 * @param   array $id     Log ID.
	 * @return  bool            Success
	 */
	public function delete_by_id( $id ) {

		global $wpdb;

		return $wpdb->delete(
			$wpdb->prefix . $this->table,
			array(
				'id' => absint( $id ),
			)
		);

	}

	/**
	 * Deletes multiple Log entries for the given Log IDs
	 *
	 * @since   3.9.6
	 *
	 * @param   array $ids    Log IDs.
	 * @return  bool            Success
	 */
	public function delete_by_ids( $ids ) {

		global $wpdb;

		return $wpdb->query( "DELETE FROM {$wpdb->prefix}{$this->table} WHERE id IN (" . implode( ',', array_map( 'absint', $ids ) ) . ')' ); // phpcs:ignore WordPress.DB.PreparedSQL

	}

	/**
	 * Deletes Log entries for the given Post ID
	 *
	 * @since   3.7.9
	 *
	 * @param   int $post_id    Post ID.
	 * @return  bool            Success
	 */
	public function delete_by_post_id( $post_id ) {

		global $wpdb;

		return $wpdb->delete(
			$wpdb->prefix . $this->table,
			array(
				'post_id' => absint( $post_id ),
			)
		);

	}

	/**
	 * Deletes Log entries for the given Post ID and Pending Status
	 *
	 * @since   3.7.9
	 *
	 * @param   int $post_id    Post ID.
	 * @return  bool            Success
	 */
	public function delete_pending_by_post_id( $post_id ) {

		global $wpdb;

		return $wpdb->delete(
			$wpdb->prefix . $this->table,
			array(
				'post_id' => absint( $post_id ),
				'result'  => 'pending',
			)
		);
	}

	/**
	 * Deletes Log entries for the given Post ID and Action that have
	 * a pending Status
	 *
	 * @since   3.7.9
	 *
	 * @param   int    $post_id    Post ID.
	 * @param   string $action     Action.
	 * @return  bool                Success
	 */
	public function delete_pending_by_post_id_and_action( $post_id, $action = 'publish' ) {

		global $wpdb;

		return $wpdb->delete(
			$wpdb->prefix . $this->table,
			array(
				'post_id' => absint( $post_id ),
				'result'  => 'pending',
				'action'  => $action,
			)
		);
	}

	/**
	 * Deletes all Log entries older than the given date
	 *
	 * @since   3.9.8
	 *
	 * @param   datetime $date_time     Date and Time.
	 * @return  bool                    Success
	 */
	public function delete_by_request_sent_cutoff( $date_time ) {

		global $wpdb;

		// Run query.
		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}{$this->table} WHERE request_sent < %s", // phpcs:ignore WordPress.DB.PreparedSQL
				$date_time
			)
		);

	}

	/**
	 * Deletes all Log entries
	 *
	 * @since   3.9.6
	 *
	 * @return  bool    Success
	 */
	public function delete_all() {

		global $wpdb;

		return $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}{$this->table}" ); // phpcs:ignore WordPress.DB.PreparedSQL

	}

	/**
	 * Wrapper for PHP's error_log() function, which will only write
	 * to the error log if:
	 * - WP_DEBUG = true
	 * - WP_DEBUG_DISPLAY = false
	 * - WP_DEBUG_LOG = true
	 *
	 * This will ensure that the output goes to wp-content/debug.log
	 *
	 * @since   3.6.8
	 *
	 * @param   mixed $data          Data to log.
	 * @param   mixed $backtrace     Backtrace data from debug_backtrace().
	 */
	public function add_to_debug_log( $data = '', $backtrace = false ) {

		// Add the data to our class array for possible output in the UI.
		$this->debug_log[] = $data;

		// Bail if Logging isn't enabled in the Plugin.
		if ( ! $this->is_enabled() ) {
			return;
		}

		// Bail if no WP_DEBUG, or it's false.
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		// Bail if no WP_DEBUG_DISPLAY, or it's true.
		if ( ! defined( 'WP_DEBUG_DISPLAY' ) || WP_DEBUG_DISPLAY ) {
			return;
		}

		// Bail if no WP_DEBUG_LOG, or it's false.
		if ( ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
			return;
		}

		// If we need to fetch the class and function name to prefix to the log entry, do so now.
		$prefix_data = '';
		if ( $backtrace !== false ) {
			if ( isset( $backtrace[0] ) ) {
				if ( isset( $backtrace[0]['class'] ) ) {
					$prefix_data .= $backtrace[0]['class'];
				}
				if ( isset( $backtrace[0]['function'] ) ) {
					$prefix_data .= '::' . $backtrace[0]['function'] . '()';
				}
			}
		}

		// If the data is empty, change it to 'called'.
		if ( empty( $data ) ) {
			$data = 'Called';
		}

		// If the data is an array or object, convert it to a string.
		if ( is_array( $data ) || is_object( $data ) ) {
			$data = print_r( $data, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		}

		// If we're prefixing the log entry, do so now.
		if ( ! empty( $prefix_data ) ) {
			$data = $prefix_data . ': ' . $data;
		}

		// Add the data to the error log, which will appear in wp-content/debug.log.
		error_log( $data ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	}

	/**
	 * Returns contents of this class' debug_log array, which comprise of
	 * items added using add_to_debug_log() above.
	 *
	 * @since   4.1.8
	 *
	 * @return  array
	 */
	public function get_debug_log() {

		return $this->debug_log;

	}

	/**
	 * Takes a given array of log results, and builds HTML table row output
	 * that can be used by:
	 * - Posts > Log Meta Box
	 * - Bulk Publish > Results Screen
	 *
	 * @since   3.7.9
	 *
	 * @param   array $log                Log Results.
	 * @param   bool  $is_wp_list_table   Is Output for a WP_List_Table (adds checkbox and Post ID columns).
	 * @param   mixed $columns            Displayed, Hidden and Sortable Columns (false = display all).
	 * @return  string                      Table Rows HTML
	 */
	public function build_log_table_output( $log, $is_wp_list_table = false, $columns = false ) {

		// Define columns.
		if ( is_array( $columns ) ) {
			list( $columns, $hidden, $sortable, $primary ) = $columns;
			$colspan                                       = ( $is_wp_list_table ? 10 : 8 );
		} else {
			$columns = array();
			$hidden  = array();
			$colspan = ( $is_wp_list_table ? 10 : 8 );
		}

		// Define HTML output.
		$html = '';

		// If no results, return a single row.
		if ( ! $log || ! is_array( $log ) || count( $log ) === 0 ) {
			$html = '
                    <tr>
                        <td colspan="' . $colspan . '">' .
							sprintf(
								/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
								__( 'No log entries exist, or no status updates have been sent to %s.', 'social-post-flow' ),
								$this->base->plugin->account
							)
							.
						'</td>
                    </tr>';

			return $html;
		}

		// Get Post Actions.
		$post_actions = social_post_flow()->get_class( 'common' )->get_post_actions();

		// Build Table HTML.
		foreach ( $log as $count => $result ) {
			// If output is for a WP_List_Table, add checkbox and Post ID.
			if ( $is_wp_list_table ) {
				$checkbox_id = '<th scope="row" class="check-column">
                        <input type="checkbox" name="ids[' . $result['id'] . ']" value="' . $result['id'] . '" />
                    </th>
                    <td class="post_id column-post_id' . ( in_array( 'post_id', $hidden, true ) ? ' hidden' : '' ) . '">
                        <a href="' . admin_url( 'admin.php?page=social-post-flow-log&s=' . $result['post_id'] ) . '">' .
							$result['post_id'] . '
                        </a>
                    </td>';
			}

			// Add row to HTML.
			$html .= '
            <tr class="' . $result['result'] . ( ( $count % 2 > 0 ) ? ' alternate' : '' ) . '">
                ' . ( $is_wp_list_table ? $checkbox_id : '' ) . '
                <td class="request_sent column-request_sent' . ( in_array( 'request_sent', $hidden, true ) ? ' hidden' : '' ) . '">' . get_date_from_gmt( $result['request_sent'], get_option( 'date_format' ) . ' H:i:s' ) . '</td>
                <td class="action column-action' . ( in_array( 'action', $hidden, true ) ? ' hidden' : '' ) . '">' . ( isset( $post_actions[ $result['action'] ] ) ? $post_actions[ $result['action'] ] : '&nbsp;' ) . '</td>
                <td class="profile_name column-profile_name' . ( in_array( 'profile_name', $hidden, true ) ? ' hidden' : '' ) . '">' . ( empty( $result['profile_name'] ) ? __( 'N/A', 'social-post-flow' ) : $result['profile_name'] ) . '</td>
                <td class="status_text column-status_text' . ( in_array( 'status_text', $hidden, true ) ? ' hidden' : '' ) . '">' . ( empty( $result['status_text'] ) ? __( 'N/A', 'social-post-flow' ) : nl2br( $result['status_text'] ) ) . '</td>
                <td class="result column-result' . ( in_array( 'result', $hidden, true ) ? ' hidden' : '' ) . '">' . ucfirst( $result['result'] ) . '</td>';

			switch ( $result['result'] ) {
				case 'success':
					$html .= '  <td class="result_message column-result_message' . ( in_array( 'result_message', $hidden, true ) ? ' hidden' : '' ) . '">' . $result['result_message'] . '</td>
                                <td class="status_created_at column-status_created_at' . ( in_array( 'status_created_at', $hidden, true ) ? ' hidden' : '' ) . '">' . get_date_from_gmt( $result['status_created_at'], get_option( 'date_format' ) . ' H:i:s' ) . '</td>
                                <td class="status_due_at column-status_due_at' . ( in_array( 'status_due_at', $hidden, true ) ? ' hidden' : '' ) . '">' . ( ( $result['status_due_at'] !== '0000-00-00 00:00:00' ) ? get_date_from_gmt( $result['status_due_at'], get_option( 'date_format' ) . ' H:i:s' ) : '' ) . '</td>';
					break;

				case 'test':
					$html .= '  <td class="result_message column-result_message' . ( in_array( 'result_message', $hidden, true ) ? ' hidden' : '' ) . '">' . $result['result_message'] . '</td>
                                <td class="status_created_at column-status_created_at' . ( in_array( 'status_created_at', $hidden, true ) ? ' hidden' : '' ) . '">' . get_date_from_gmt( $result['status_created_at'], get_option( 'date_format' ) . ' H:i:s' ) . '</td>
                                <td class="status_due_at column-status_due_at' . ( in_array( 'status_due_at', $hidden, true ) ? ' hidden' : '' ) . '">' . ( ( $result['status_due_at'] !== '0000-00-00 00:00:00' ) ? get_date_from_gmt( $result['status_due_at'], get_option( 'date_format' ) . ' H:i:s' ) : '' ) . '</td>';
					break;

				default:
					$html .= '  <td class="result_message column-result_message' . ( in_array( 'result_message', $hidden, true ) ? ' hidden' : '' ) . '">' . nl2br( $result['result_message'] ) . '</td>
                                <td class="status_created_at column-status_created_at' . ( in_array( 'status_created_at', $hidden, true ) ? ' hidden' : '' ) . '">&nbsp;</td>
                                <td class="status_due_at column-status_due_at' . ( in_array( 'status_due_at', $hidden, true ) ? ' hidden' : '' ) . '">&nbsp;</td>';
					break;
			}

			$html .= '</tr>';
		}

		// Return.
		return $html;

	}

}
