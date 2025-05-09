<?php
/**
 * Log Table class.
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Controls the Log WP_List_Table.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 3.9.6
 */
class Social_Post_Flow_Log_Table extends WP_List_Table {

	/**
	 * Constructor.
	 *
	 * @since   3.9.6
	 */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => 'wp-to-social-log',  // Singular label.
				'plural'   => 'wp-to-social-log',  // plural label, also this well be one of the table css class.
				'ajax'     => false,                // We won't support Ajax for this table.
			)
		);

	}

	/**
	 * Display dropdowns for Bulk Actions and Filtering.
	 *
	 * @since   3.9.6
	 *
	 * @param   string $which  The location of the bulk actions: 'top' or 'bottom'.
	 *                         This is designated as optional for backward compatibility.
	 */
	protected function bulk_actions( $which = '' ) {

		// Get Bulk Actions.
		$this->_actions = $this->get_bulk_actions();

		// Define <select> name.
		$bulk_actions_name = 'bulk_action' . ( $which !== 'top' ? '2' : '' );
		?>
		<label for="bulk-action-selector-<?php echo esc_attr( $which ); ?>" class="screen-reader-text">
			<?php esc_html_e( 'Select bulk action', 'social-post-flow' ); ?>
		</label>
		<select name="<?php echo esc_attr( $bulk_actions_name ); ?>" id="bulk-action-selector-<?php echo esc_attr( $which ); ?>" size="1">
			<option value="-1"><?php esc_attr_e( 'Bulk Actions', 'social-post-flow' ); ?></option>

			<?php
			foreach ( $this->_actions as $name => $title ) {
				?>
				<option value="<?php echo esc_attr( $name ); ?>"><?php echo esc_attr( $title ); ?></option>
				<?php
			}
			?>
		</select>

		<?php
		// Output our custom filters to the top only.
		if ( $which === 'top' ) {
			$profiles = social_post_flow()->get_class( 'log' )->get_profile_id_names();
			?>
			<!-- Custom Filters -->
			<select name="action" size="1">
				<option value=""<?php selected( $this->get_action(), '' ); ?>><?php esc_attr_e( 'Filter by Action', 'social-post-flow' ); ?></option>
				<?php
				foreach ( social_post_flow()->get_class( 'common' )->get_post_actions() as $action => $label ) {
					?>
					<option value="<?php echo esc_attr( $action ); ?>"<?php selected( $this->get_action(), $action ); ?>><?php echo esc_attr( $label ); ?></option>
					<?php
				}
				?>
			</select>
			<select name="profile_id" size="1">
				<option value=""<?php selected( $this->get_profile(), '' ); ?>><?php esc_attr_e( 'Filter by Profile', 'social-post-flow' ); ?></option>
				<?php
				foreach ( $profiles as $profile_id => $label ) {
					?>
					<option value="<?php echo esc_attr( $profile_id ); ?>"<?php selected( $this->get_profile_id(), $profile_id ); ?>><?php echo esc_attr( $label ); ?></option>
					<?php
				}
				?>
			</select>
			<select name="result" size="1">
				<option value=""<?php selected( $this->get_result(), '' ); ?>><?php esc_attr_e( 'Filter by Result', 'social-post-flow' ); ?></option>
				<?php
				foreach ( social_post_flow()->get_class( 'log' )->get_result_options() as $result_option => $label ) {
					?>
					<option value="<?php echo esc_attr( $result_option ); ?>"<?php selected( $this->get_result(), $result_option ); ?>>
						<?php echo esc_attr( $label ); ?>
					</option>
					<?php
				}
				?>
			</select>

			<input type="date" name="request_sent_start_date" value="<?php echo esc_attr( $this->get_request_sent_start_date() ); ?>" />
			-
			<input type="date" name="request_sent_end_date" value="<?php echo esc_attr( $this->get_request_sent_end_date() ); ?>"/>
			<?php
		}

		submit_button( __( 'Apply', 'social-post-flow' ), 'action', '', false, array( 'id' => 'doaction' ) );

		$clear_logs_url = add_query_arg(
			array(
				'page'         => 'social-post-flow-log',
				'bulk_action3' => 'delete_all',
				'_wpnonce'     => wp_create_nonce( 'bulk-wp-to-social-log' ),
			),
			admin_url( 'admin.php' )
		);
		?>

		<a href="<?php echo esc_url( $clear_logs_url ); ?>" class="<?php echo esc_attr( 'social-post-flow' ); ?>-clear-log button wpzinc-button-red" data-message="<?php esc_html_e( 'Are you sure you want to clear ALL logs?', 'social-post-flow' ); ?>">
			<?php esc_html_e( 'Clear Log', 'social-post-flow' ); ?>
		</a>
		<?php

	}

	/**
	 * Defines the message to display when no items exist in the table
	 *
	 * @since   3.9.6
	 */
	public function no_items() {

		esc_html_e( 'No log entries found based on the given search and filter criteria.', 'social-post-flow' );

	}

	/**
	 * Displays the search box.
	 *
	 * @since   3.1.0
	 *
	 * @param   string $text        The 'submit' button label.
	 * @param   string $input_id    ID attribute value for the search input field.
	 */
	public function search_box( $text, $input_id ) {

		$input_id = $input_id . '-search-input';

		// Preserve Filters by storing any defined as hidden form values.
		foreach ( social_post_flow()->get_class( 'common' )->get_log_filters() as $filter ) {
			if ( ! empty( $_REQUEST[ $filter ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				?>
				<input type="hidden" name="<?php echo esc_attr( $filter ); ?>" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_REQUEST[ $filter ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification ?>" />
				<?php
			}
		}
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_attr( $text ); ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" placeholder="<?php esc_attr_e( 'Post ID or Title', 'social-post-flow' ); ?>" />
			<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
		</p>
		<?php
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @since   3.9.6
	 *
	 * @return  array   Columns to use with the table
	 */
	public function get_columns() {

		return array(
			'cb'                => '<input type="checkbox" class="toggle" />',
			'post_id'           => __( 'Post ID', 'social-post-flow' ),
			'request_sent'      => __( 'Request Sent', 'social-post-flow' ),
			'action'            => __( 'Action', 'social-post-flow' ),
			'profile_name'      => __( 'Profile', 'social-post-flow' ),
			'status_text'       => __( 'Status Text', 'social-post-flow' ),
			'result'            => __( 'Result', 'social-post-flow' ),
			'result_message'    => __( 'Response', 'social-post-flow' ),
			'status_created_at' => __( 'Social Post Flow: Status Created At', 'social-post-flow' ),
			'status_scheduled_at'     => __( 'Social Post Flow: Status Scheduled For', 'social-post-flow' ),
		);

	}

	/**
	 * Decide which columns to activate the sorting functionality on
	 *
	 * @since   3.9.6
	 *
	 * @return  array   Columns that can be sorted by the user
	 */
	public function get_sortable_columns() {

		return array(
			'post_id'           => array( 'post_id', true ),
			'request_sent'      => array( 'request_sent', true ),
			'action'            => array( 'action', true ),
			'profile_name'      => array( 'profile_name', true ),
			'status_text'       => array( 'status_text', true ),
			'result'            => array( 'result', true ),
			'result_message'    => array( 'result_message', true ),
			'status_created_at' => array( 'status_created_at', true ),
			'status_scheduled_at'     => array( 'status_scheduled_at', true ),
		);

	}

	/**
	 * Overrides the list of bulk actions in the select dropdowns above and below the table
	 *
	 * @since   3.9.6
	 *
	 * @return  array   Bulk Actions
	 */
	public function get_bulk_actions() {

		return array(
			'delete' => __( 'Delete', 'social-post-flow' ),
		);

	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 *
	 * @since   3.9.6
	 */
	public function prepare_items() {

		global $_wp_column_headers;

		$screen = get_current_screen();

		// Get params.
		$params   = $this->get_search_params();
		$order_by = $this->get_order_by();
		$order    = $this->get_order();
		$page     = $this->get_page();
		$per_page = $this->get_items_per_page( 'social_post_flow_logs_per_page', 20 );

		// Get total records for this query.
		$total = social_post_flow()->get_class( 'log' )->total( $params );

		// Define pagination.
		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'total_pages' => ceil( $total / $per_page ),
				'per_page'    => $per_page,
			)
		);

		// Set column headers.
		$this->_column_headers = $this->get_column_info();

		// Set rows.
		$this->items = social_post_flow()->get_class( 'log' )->search( $order_by, $order, $page, $per_page, $params );

	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since   3.1.0
	 *
	 * @param   string $which  The location of the bulk actions: 'top' or 'bottom'.
	 *                         This is designated as optional for backward compatibility.
	 */
	protected function display_tablenav( $which ) {

		if ( 'top' === $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
			</div>
			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

			<br class="clear" />
		</div>
		<?php

	}

	/**
	 * Display the rows of records in the table
	 *
	 * @since   3.9.6
	 */
	public function display_rows() {

		echo social_post_flow()->get_class( 'log' )->build_log_table_output( $this->items, true, $this->_column_headers ); // phpcs:ignore WordPress.Security.EscapeOutput

	}

	/**
	 * Get search parameters.
	 *
	 * @since   3.9.6
	 *
	 * @return  array   Search Parameters
	 */
	private function get_search_params() {

		// Build search params.
		$params = array(
			'action'                  => $this->get_action(),
			'profile_id'              => $this->get_profile_id(),
			'result'                  => $this->get_result(),
			'request_sent_start_date' => $this->get_request_sent_start_date(),
			'request_sent_end_date'   => $this->get_request_sent_end_date(),
		);

		// Return params if freeform search isn't supplied.
		if ( ! isset( $_REQUEST['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return $params;
		}
		if ( empty( $_REQUEST['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return $params;
		}

		// If search is a number, add it as the Post ID and return.
		$search = esc_html( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( is_numeric( $search ) ) {
			$params['post_id'] = absint( $search );
			return $params;
		}

		// Add it as the Post Title and return.
		$params['post_title'] = $search;

		return $params;

	}

	/**
	 * Returns whether a search has been performed on the table.
	 *
	 * @since   3.9.0
	 *
	 * @return  bool    Search has been performed.
	 */
	public function is_search() {

		return ( isset( $_REQUEST['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

	}

	/**
	 * Get the Search requested by the user
	 *
	 * @since   3.9.0
	 *
	 * @return  string
	 */
	public function get_search() {

		return ( isset( $_REQUEST['s'] ) ? urldecode( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification

	}

	/**
	 * Get the Action Filter requested by the user
	 *
	 * @since   3.9.7
	 *
	 * @return  string
	 */
	private function get_action() {

		return ( isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '' );  // phpcs:ignore WordPress.Security.NonceVerification

	}

	/**
	 * Get the Profile ID Filter requested by the user
	 *
	 * @since   3.9.7
	 *
	 * @return  string
	 */
	private function get_profile_id() {

		return ( isset( $_REQUEST['profile_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['profile_id'] ) ) : '' );  // phpcs:ignore WordPress.Security.NonceVerification

	}

	/**
	 * Get the Status Result Filter requested by the user
	 *
	 * @since   3.9.7
	 *
	 * @return  string
	 */
	private function get_result() {

		return ( isset( $_REQUEST['result'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['result'] ) ) : '' );  // phpcs:ignore WordPress.Security.NonceVerification

	}

	/**
	 * Get the Request Sent Start Date Filter requested by the user
	 *
	 * @since   3.9.8
	 *
	 * @return  string
	 */
	private function get_request_sent_start_date() {

		return ( isset( $_REQUEST['request_sent_start_date'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['request_sent_start_date'] ) ) : '' );  // phpcs:ignore WordPress.Security.NonceVerification

	}

	/**
	 * Get the Request Sent End Date Filter requested by the user
	 *
	 * @since   3.9.8
	 *
	 * @return  string
	 */
	private function get_request_sent_end_date() {

		return ( isset( $_REQUEST['request_sent_end_date'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['request_sent_end_date'] ) ) : '' );  // phpcs:ignore WordPress.Security.NonceVerification

	}

	/**
	 * Get the Order By requested by the user
	 *
	 * @since   3.9.7
	 *
	 * @return  string
	 */
	private function get_order_by() {

		return ( isset( $_GET['orderby'] ) ? sanitize_sql_orderby( wp_unslash( $_GET['orderby'] ) ) : 'request_sent' );  // phpcs:ignore WordPress.Security.NonceVerification

	}

	/**
	 * Get the Order requested by the user
	 *
	 * @since   3.9.7
	 *
	 * @return  string
	 */
	private function get_order() {

		return ( isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC' );  // phpcs:ignore WordPress.Security.NonceVerification

	}

	/**
	 * Get the Pagination Page requested by the user
	 *
	 * @since   3.9.7
	 *
	 * @return  string
	 */
	private function get_page() {

		return ( ( isset( $_GET['paged'] ) && ! empty( $_GET['paged'] ) ) ? absint( $_GET['paged'] ) : 1 );  // phpcs:ignore WordPress.Security.NonceVerification

	}

}
