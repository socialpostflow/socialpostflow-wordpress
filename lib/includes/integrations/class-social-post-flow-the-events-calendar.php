<?php
/**
 * The Events Calendar Plugin Class.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */

/**
 * Provides compatibility with The Events Calendar
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 4.3.8
 */
class Social_Post_Flow_The_Events_Calendar {

	/**
	 * Holds the base object.
	 *
	 * @since   4.3.8
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   4.3.8
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct() {

		

		// Register Schedule Options.
		add_filter( 'social_post_flow_get_schedule_options', array( $this, 'register_schedule_options' ), 10, 2 );

		// Output Schedule Options Form Fields.
		add_action( 'social_post_flow_output_schedule_options_form_fields', array( $this, 'output_schedule_options_form_fields' ) );

		// Output Status Row Schedule.
		add_filter( 'social_post_flow_settings_get_status_row_schedule', array( $this, 'get_status_row_schedule' ), 10, 5 );

		// Register Status Tags.
		add_filter( 'social_post_flow_get_tags', array( $this, 'register_status_tags' ), 10, 2 );

		// Replace Tags with Values.
		add_filter( 'social_post_flow_publish_get_all_possible_searches_replacements', array( $this, 'register_searches_replacements' ), 10, 3 );

		// Schedule Status based on Event Date.
		add_filter( 'social_post_flow_publish_builds_args_schedule__EventStartDate', array( $this, 'schedule_status_event_start_date' ), 10, 3 );
		add_filter( 'social_post_flow_publish_builds_args_schedule__EventEndDate', array( $this, 'schedule_status_event_end_date' ), 10, 3 );

		// Google Business Profile: Register Start and End Date options.
		add_filter( 'social_post_flow_get_google_business_start_date_options', array( $this, 'register_google_business_start_date_options' ), 10, 2 );
		add_filter( 'social_post_flow_get_google_business_end_date_options', array( $this, 'register_google_business_end_date_options' ), 10, 2 );

		// Google Business Profile: Define Start and End Date based on Event Date.
		add_filter( 'social_post_flow_publish_parse_google_business_start_date__EventStartDate', array( $this, 'schedule_google_business_start_date' ), 10, 4 );
		add_filter( 'social_post_flow_publish_parse_google_business_end_date__EventEndDate', array( $this, 'schedule_google_business_end_date' ), 10, 4 );

	}

	/**
	 * Defines the available schedule options for statuses
	 *
	 * @since   4.4.0
	 *
	 * @param   array  $schedule           Schedule Options.
	 * @param   string $post_type          Post Type.
	 */
	public function register_schedule_options( $schedule, $post_type ) {

		// Bail if The Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $schedule;
		}

		// Bail if this isn't an Event Post Type.
		if ( $post_type !== 'tribe_events' ) {
			return $schedule;
		}

		// Add schedule options and return.
		return array_merge(
			$schedule,
			array(
				'_EventStartDate' => __( 'The Events Calendar: Relative to Event Start Date', 'social-post-flow' ),
				'_EventEndDate'   => __( 'The Events Calendar: Relative to Event End Date', 'social-post-flow' ),
			)
		);

	}

	/**
	 * Outputs schedule option settings when a schedule option belonging to The Events Calendar
	 * has been selected
	 *
	 * @since   4.4.0
	 *
	 * @param   string $post_type  Post Type.
	 */
	public function output_schedule_options_form_fields( $post_type ) {

		// Bail if The Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return;
		}

		// Bail if this isn't an Event Post Type.
		if ( $post_type !== 'tribe_events' ) {
			return;
		}

		// Output The Events Calendar specific settings.
		?>
		<span class="the_events_calendar">
			<select name="<?php echo esc_attr( 'social-post-flow' ); ?>_schedule_tec_relation" size="1">
				<option value="before"><?php esc_attr_e( 'Before Event Date', 'social-post-flow' ); ?></option>
				<option value="after"><?php esc_attr_e( 'After Event Date', 'social-post-flow' ); ?></option>
			</select> 
		</span>
		<?php

	}

	/**
	 * Returns the text to display for a status' schedule setting in the table row.
	 *
	 * @since   4.4.0
	 *
	 * @param   string $output     Output.
	 * @param   array  $status     Status.
	 * @param   string $action     Action.
	 * @param   string $post_type  Post Type.
	 * @param   array  $schedule   Schedule Options.
	 * @return  string
	 */
	public function get_status_row_schedule( $output, $status, $action, $post_type, $schedule ) {  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Bail if The Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $output;
		}

		// Bail if this isn't an Event Post Type.
		if ( $post_type !== 'tribe_events' ) {
			return $output;
		}

		// Define labels.
		switch ( $status['schedule_tec_relation'] ) {
			case 'before':
				$relation = __( 'before', 'social-post-flow' );
				break;

			case 'after':
				$relation = __( 'after', 'social-post-flow' );
				break;
		}
		switch ( $status['schedule'] ) {
			case '_EventStartDate':
				$label = __( 'Event Start Date', 'social-post-flow' );
				break;

			case '_EventEndDate':
				$label = __( 'Event End Date', 'social-post-flow' );
				break;
		}

		// Output.
		return sprintf(
			/* translators: %1$s: Number of Days, %2$s: Number of Hours, %3$s: Number of Minutes, %4$s: Translated 'before' or 'after' string, %5$s: Translated 'Event Start Date' or 'Event End Date' string */
			__( '%1$s days, %2$s hours, %3$s minutes %4$s %5$s', 'social-post-flow' ),
			$status['days'],
			$status['hours'],
			$status['minutes'],
			$relation,
			$label
		);

	}

	/**
	 * Defines Dynamic Status Tags that can be inserted into status(es) for the given Post Type.
	 * These tags are also added to any 'Insert Tag' dropdowns.
	 *
	 * @since   4.3.8
	 *
	 * @param   array  $tags       Tags.
	 * @param   stirng $post_type  Post Type.
	 * @return  array               Tags
	 */
	public function register_status_tags( $tags, $post_type ) {

		// Bail if The Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $tags;
		}

		// Bail if this isn't an Event Post Type.
		if ( $post_type !== 'tribe_events' ) {
			return $tags;
		}

		// Register Status Tags.
		return array_merge(
			$tags,
			array(
				'the_events_calendar' => array(
					// Event.
					'{tec_event_start_date}'            => __( 'Event Start Date', 'social-post-flow' ),
					'{tec_event_start_time}'            => __( 'Event Start Time', 'social-post-flow' ),
					'{tec_event_end_date}'              => __( 'Event End Date', 'social-post-flow' ),
					'{tec_event_end_time}'              => __( 'Event End Time', 'social-post-flow' ),
					'{tec_event_cost}'                  => __( 'Event Cost', 'social-post-flow' ),
					'{tec_event_url}'                   => __( 'Event URL', 'social-post-flow' ),
					'{tec_event_map_url}'               => __( 'Event Map URL', 'social-post-flow' ),

					// Venue.
					'{tec_event_venue_name}'            => __( 'Event Venue Name', 'social-post-flow' ),
					'{tec_event_venue_address}'         => __( 'Event Venue Address (Full)', 'social-post-flow' ),
					'{tec_event_venue_address_only}'    => __( 'Event Venue Address', 'social-post-flow' ),
					'{tec_event_venue_city}'            => __( 'Event Venue City', 'social-post-flow' ),
					'{tec_event_venue_province}'        => __( 'Event Venue State or Province', 'social-post-flow' ),
					'{tec_event_venue_postal_code}'     => __( 'Event Venue Postal Code', 'social-post-flow' ),
					'{tec_event_venue_country}'         => __( 'Event Venue Country', 'social-post-flow' ),

					// Organizer.
					'{tec_event_organizer_name}'        => __( 'Event Organizer Name', 'social-post-flow' ),
					'{tec_event_organizer_phone}'       => __( 'Event Organizer Phone', 'social-post-flow' ),
					'{tec_event_organizer_email}'       => __( 'Event Organizer Email', 'social-post-flow' ),
					'{tec_event_organizer_website_url}' => __( 'Event Organizer Website URL', 'social-post-flow' ),
				),
			)
		);

	}

	/**
	 * Registers any additional status message tags, and their Post data replacements, that are supported.
	 *
	 * @since   4.3.8
	 *
	 * @param   array   $searches_replacements  Registered Supported Tags and their Replacements.
	 * @param   WP_Post $post                   WordPress Post.
	 * @param   WP_User $author                 WordPress User (Author of the Post).
	 * @return  array                               Registered Supported Tags and their Replacements
	 */
	public function register_searches_replacements( $searches_replacements, $post, $author ) {

		// Bail if The Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $searches_replacements;
		}

		// Bail if this Post isn't an Event.
		if ( $post->post_type !== 'tribe_events' ) {
			return $searches_replacements;
		}

		// Register Tags and their replacement values.
		return array_merge( $searches_replacements, $this->get_searches_replacements( $post, $author ) );

	}

	/**
	 * Returns tags and their Post data replacements, that are supported for The Events Calendar.
	 *
	 * @since   4.3.8
	 *
	 * @param   WP_Post $post                   WordPress Post.
	 * @param   WP_User $author                 WordPress User (Author of the Post).
	 * @return  array                               Registered Supported Tags and their Replacements
	 */
	private function get_searches_replacements( $post, $author ) {

		$searches_replacements = array(
			// Event.
			'tec_event_start_date'            => tribe_get_start_date( $post->ID, false ),
			'tec_event_start_time'            => tribe_get_start_time( $post->ID ),
			'tec_event_end_date'              => tribe_get_end_date( $post->ID, false ),
			'tec_event_end_time'              => tribe_get_end_time( $post->ID ),
			'tec_event_cost'                  => tribe_get_cost( $post->ID, true ),
			'tec_event_url'                   => tribe_get_event_website_url( $post->ID ),
			'tec_event_map_url'               => tribe_get_map_link( $post->ID ),

			// Venue.
			'tec_event_venue_name'            => tribe_get_venue( $post->ID ),
			'tec_event_venue_address'         => $this->get_event_venue_address( $post ),
			'tec_event_venue_address_only'    => tribe_get_address( $post->ID ),
			'tec_event_venue_city'            => tribe_get_city( $post->ID ),
			'tec_event_venue_province'        => tribe_get_province( $post->ID ),
			'tec_event_venue_postal_code'     => tribe_get_zip( $post->ID ),
			'tec_event_venue_country'         => tribe_get_country( $post->ID ),

			// Organizer.
			'tec_event_organizer_name'        => '',
			'tec_event_organizer_phone'       => '',
			'tec_event_organizer_email'       => '',
			'tec_event_organizer_website_url' => '',
		);

		// Get Event Organizer ID.
		$event_organizer_id = tribe_get_organizer_id( $post->ID );
		if ( $event_organizer_id ) {
			$searches_replacements = array_merge(
				$searches_replacements,
				array(
					'tec_event_organizer_name'        => tribe_get_organizer( $event_organizer_id ),
					'tec_event_organizer_phone'       => tribe_get_organizer_phone( $event_organizer_id ),
					'tec_event_organizer_email'       => get_post_meta( tribe_get_organizer_id( $event_organizer_id ), '_OrganizerEmail', true ),
					'tec_event_organizer_website_url' => get_post_meta( tribe_get_organizer_id( $event_organizer_id ), '_OrganizerWebsite', true ),
				)
			);
		}

		/**
		 * Registers any additional status message tags, and their Post data replacements, that are supported
		 * for The Events Calendar
		 *
		 * @since   4.1.2
		 *
		 * @param   array       $searches_replacements  Registered Supported Tags and their Replacements.
		 * @param   WP_Post     $post                   WordPress Post.
		 * @param   WP_User     $author                 WordPress User (Author of the Post).
		 */
		$searches_replacements = apply_filters( 'social_post_flow_publish_register_the_events_calendar_searches_replacements', $searches_replacements, $post, $author );

		return $searches_replacements;

	}

	/**
	 * Define the UTC date and time for the status to be published when the status' schedule is set to use the Event's Start Date
	 *
	 * @since   4.6.9
	 *
	 * @param   string  $scheduled_at   Schedule Status (yyyy-mm-dd hh:mm:ss format).
	 * @param   array   $status         Status.
	 * @param   WP_Post $post           WordPress Post.
	 * @return  string                  UTC Date and Time
	 */
	public function schedule_status_event_start_date( $scheduled_at, $status, $post ) {

		// Bail if The Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $scheduled_at;
		}

		// Get adjusted date and time.
		$date_time = social_post_flow()->get_class( 'date' )->adjust_date_time(
			get_post_meta( $post->ID, '_EventStartDate', true ),
			$status['schedule_tec_relation'],
			$status['days'],
			$status['hours'],
			$status['minutes']
		);

		// Return UTC date and time.
		return social_post_flow()->get_class( 'date' )->get_utc_date_time( $date_time );

	}

	/**
	 * Define the UTC date and time for the status to be published when the status' schedule is set to use the Event's End Date
	 *
	 * @since   4.6.9
	 *
	 * @param   string  $scheduled_at   Schedule Status (yyyy-mm-dd hh:mm:ss format).
	 * @param   array   $status         Status.
	 * @param   WP_Post $post           WordPress Post.
	 * @return  string                  UTC Date and Time
	 */
	public function schedule_status_event_end_date( $scheduled_at, $status, $post ) {

		// Bail if The Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $scheduled_at;
		}

		$date_time = social_post_flow()->get_class( 'date' )->adjust_date_time(
			get_post_meta( $post->ID, '_EventEndDate', true ),
			$status['schedule_tec_relation'],
			$status['days'],
			$status['hours'],
			$status['minutes']
		);

		// Return UTC date and time.
		return social_post_flow()->get_class( 'date' )->get_utc_date_time( $date_time );

	}

	/**
	 * Defines the available schedule options for statuses
	 *
	 * @since   4.9.0
	 *
	 * @param   array  $schedule           Schedule Options.
	 * @param   string $post_type          Post Type.
	 */
	public function register_google_business_start_date_options( $schedule, $post_type ) {

		// Bail if The Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $schedule;
		}

		// Bail if this isn't an Event Post Type.
		if ( $post_type !== 'tribe_events' ) {
			return $schedule;
		}

		// Add schedule options and return.
		return array_merge(
			$schedule,
			array(
				'_EventStartDate' => __( 'The Events Calendar: Start Date', 'social-post-flow' ),
			)
		);

	}

	/**
	 * Defines the available schedule options for statuses
	 *
	 * @since   4.9.0
	 *
	 * @param   array  $schedule           Schedule Options.
	 * @param   string $post_type          Post Type.
	 */
	public function register_google_business_end_date_options( $schedule, $post_type ) {

		// Bail if The Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $schedule;
		}

		// Bail if this isn't an Event Post Type.
		if ( $post_type !== 'tribe_events' ) {
			return $schedule;
		}

		// Add schedule options and return.
		return array_merge(
			$schedule,
			array(
				'_EventEndDate' => __( 'The Events Calendar: End Date', 'social-post-flow' ),
			)
		);

	}

	/**
	 * Define a Google Business Profile status' start date to be the Event's start date.
	 *
	 * @since   4.9.0
	 *
	 * @param   bool|string $date                   Date (yyyy-mm-dd hh:mm:ss format).
	 * @param   array       $google_business_args   Google Business specific arguments for status.
	 * @param   array       $status                 Status.
	 * @param   WP_Post     $post                   WordPress Post.
	 * @return  string                              Date
	 */
	public function schedule_google_business_start_date( $date, $google_business_args, $status, $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Bail if The Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $date;
		}

		return get_post_meta( $post->ID, '_EventStartDate', true );

	}

	/**
	 * Define a Google Business Profile status' end date to be the Event's start date.
	 *
	 * @since   4.9.0
	 *
	 * @param   bool|string $date                   Date (yyyy-mm-dd hh:mm:ss format).
	 * @param   array       $google_business_args   Google Business specific arguments for status.
	 * @param   array       $status                 Status.
	 * @param   WP_Post     $post                   WordPress Post.
	 * @return  string                              Date
	 */
	public function schedule_google_business_end_date( $date, $google_business_args, $status, $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Bail if The Events Calendar isn't active.
		if ( ! $this->is_active() ) {
			return $date;
		}

		return get_post_meta( $post->ID, '_EventEndDate', true );

	}

	/**
	 * Returns the Event's Venue's Address, converting from HTML to text
	 * comma separated
	 *
	 * @since   4.3.8
	 *
	 * @param   WP_Post $post   WordPress Post.
	 * @return  string              Address
	 */
	private function get_event_venue_address( $post ) {

		// Conver Venue Address from HTML to text, comma separated.
		$address = trim( wp_strip_all_tags( tribe_get_full_address( $post->ID ) ) );

		// Bail if no address exists.
		if ( empty( $address ) ) {
			return $address;
		}

		// Remove newlines and replace tabs with commas.
		$address = preg_replace( '/\n+/', '', $address );
		$address = preg_replace( '/\t+/', ',', $address );

		// Remove duplicated commas.
		$address = str_replace( ',,', ',', $address );

		// Add a space after each comma.
		$address = str_replace( ',', ', ', $address );

		// Return.
		return trim( $address );

	}

	/**
	 * Checks if the The Events Calendar Plugin is active
	 *
	 * @since   4.3.8
	 *
	 * @return  bool    The Events Calendar Plugin Active
	 */
	private function is_active() {

		return class_exists( 'Tribe__Events__Main' );

	}

}
