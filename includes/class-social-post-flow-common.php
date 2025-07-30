<?php
/**
 * Common class.
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
 */

/**
 * Common functions that don't fit into other classes.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 * @version 1.0.0
 */
class Social_Post_Flow_Common {

	/**
	 * Helper method to retrieve three character day names and their full names
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Days
	 */
	public function get_days() {

		// Define days.
		$days = array(
			'mon' => __( 'Monday', 'social-post-flow' ),
			'tue' => __( 'Tuesday', 'social-post-flow' ),
			'wed' => __( 'Wednesday', 'social-post-flow' ),
			'thu' => __( 'Thursday', 'social-post-flow' ),
			'fri' => __( 'Friday', 'social-post-flow' ),
			'sat' => __( 'Saturday', 'social-post-flow' ),
			'sun' => __( 'Sunday', 'social-post-flow' ),
		);

		/**
		 * Defines the available days.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $days   Days.
		 */
		$days = apply_filters( 'social_post_flow_get_days', $days );

		// Return filtered results.
		return $days;

	}

	/**
	 * Helper method to retrieve status post type options
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Status Post Type Options
	 */
	public function get_status_post_type_options() {

		// Build status post type options.
		$status_post_type_options = array(
			'text'  => array(
				'label'      => __( 'Text', 'social-post-flow' ),
				'conditions' => array(
					'provider' => array( 'facebook', 'x', 'linkedin', 'instagram' ),
				),
			),
			'link'  => array(
				'label'      => __( 'Link', 'social-post-flow' ),
				'conditions' => array(
					'provider' => array( 'facebook', 'x', 'linkedin' ),
				),
			),
			'image' => array(
				'label'      => __( 'Image', 'social-post-flow' ),
				'conditions' => array(
					'provider' => array( 'facebook', 'x', 'linkedin', 'instagram' ),
				),
			),
			'story' => array(
				'label'      => __( 'Story', 'social-post-flow' ),
				'conditions' => array(
					'provider' => array( 'instagram' ),
				),
			),
		);

		/**
		 * Defines the available status post type options.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $status_post_type_options   Status Post Type Options.
		 */
		$status_post_type_options = apply_filters( 'social_post_flow_get_status_post_type_options', $status_post_type_options );

		// Return filtered results.
		return $status_post_type_options;

	}

	/**
	 * Helper method to retrieve schedule options
	 *
	 * @since   1.0.0
	 *
	 * @param   mixed $post_type          Post Type (false | string).
	 * @param   bool  $is_post_screen     Displaying the Post Screen.
	 * @return  array                       Schedule Options
	 */
	public function get_schedule_options( $post_type = false, $is_post_screen = false ) {

		// Build schedule options.
		$schedule = array(
			'queue_end'       => __( 'Add to End of Social Post Flow Queue', 'social-post-flow' ),
			'queue_start'     => __( 'Add to Start of Social Post Flow Queue', 'social-post-flow' ),
			'immediate'       => __( 'Post Immediately', 'social-post-flow' ),
			'custom'          => __( 'Custom Time', 'social-post-flow' ),
			'custom_relative' => __( 'Custom Time (Relative Format)', 'social-post-flow' ),
			'custom_field'    => __( 'Custom Time (based on Custom Field / Post Meta Value)', 'social-post-flow' ),
		);

		// If we're on the Post Screen, add a specific option now.
		if ( $is_post_screen ) {
			$schedule['specific'] = __( 'Specific Date and Time', 'social-post-flow' );
		}

		/**
		 * Defines the available schedule options for each individual status.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $schedule           Schedule Options.
		 * @param   string  $post_type          Post Type.
		 * @param   bool    $is_post_screen     On Post Edit Screen.
		 */
		$schedule = apply_filters( 'social_post_flow_get_schedule_options', $schedule, $post_type, $is_post_screen );

		// Return filtered results.
		return $schedule;

	}

	/**
	 * Helper method to retrieve days used for the schedule relative days option
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Days
	 */
	public function get_schedule_relative_days() {

		// Build days.
		$days = array(
			'today'     => __( 'Today', 'social-post-flow' ),
			'tomorrow'  => __( 'Tomorrow', 'social-post-flow' ),
			'monday'    => __( 'Next Monday', 'social-post-flow' ),
			'tuesday'   => __( 'Next Tuesday', 'social-post-flow' ),
			'wednesday' => __( 'Next Wednesday', 'social-post-flow' ),
			'thursday'  => __( 'Next Thursday', 'social-post-flow' ),
			'friday'    => __( 'Next Friday', 'social-post-flow' ),
			'saturday'  => __( 'Next Saturday', 'social-post-flow' ),
			'sunday'    => __( 'Next Sunday', 'social-post-flow' ),
		);

		/**
		 * Defines the available days for a status' Custom Time (Relative Format) option.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $days   Days.
		 */
		$days = apply_filters( 'social_post_flow_get_schedule_relative_days', $days );

		// Return filtered results.
		return $days;

	}

	/**
	 * Helper method to retrieve schedule custom relation options
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Schedule Custom Relation Options
	 */
	public function get_schedule_custom_relation_options() {

		// Build schedule options.
		$schedule = array(
			'before' => __( 'Before Custom Field Value', 'social-post-flow' ),
			'after'  => __( 'After Custom Field Value', 'social-post-flow' ),
		);

		/**
		 * Defines the available schedule options, relative to a custom field, for each individual status.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $schedule   Schedule Options.
		 */
		$schedule = apply_filters( 'social_post_flow_get_schedule_custom_relation_options', $schedule );

		// Return filtered results.
		return $schedule;

	}

	/**
	 * Helper method to retrieve Google Business Start Date options
	 *
	 * @since   1.0.0
	 *
	 * @param   mixed $post_type          Post Type (false | string).
	 * @return  array   Start Date Options
	 */
	public function get_google_business_start_date_options( $post_type = false ) {

		// Build schedule options.
		$schedule = array(
			'custom' => __( 'Custom Field / Post Meta Value', 'social-post-flow' ),
		);

		/**
		 * Defines the available start date options for a Google Business Profile status.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $schedule   Schedule Options.
		 */
		$schedule = apply_filters( 'social_post_flow_get_google_business_start_date_options', $schedule, $post_type );

		// Return filtered results.
		return $schedule;

	}

	/**
	 * Helper method to retrieve Google Business Start Date options
	 *
	 * @since   1.0.0
	 *
	 * @param   mixed $post_type          Post Type (false | string).
	 * @return  array   End Date Options
	 */
	public function get_google_business_end_date_options( $post_type = false ) {

		// Build schedule options.
		$schedule = array(
			'custom' => __( 'Custom Field / Post Meta Value', 'social-post-flow' ),
		);

		/**
		 * Defines the available start date options for a Google Business Profile status.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $schedule   Schedule Options.
		 */
		$schedule = apply_filters( 'social_post_flow_get_google_business_end_date_options', $schedule, $post_type );

		// Return filtered results.
		return $schedule;

	}

	/**
	 * Helper method to retrieve public Post Types
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Public Post Types
	 */
	public function get_post_types() {

		// Get public Post Types.
		$types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		// Filter out excluded post types.
		$excluded_types = $this->get_excluded_post_types();
		if ( is_array( $excluded_types ) ) {
			foreach ( $excluded_types as $excluded_type ) {
				unset( $types[ $excluded_type ] );
			}
		}

		/**
		 * Defines the available Post Type Objects that can have statues defined and be sent to social media.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $types  Post Types.
		 */
		$types = apply_filters( 'social_post_flow_get_post_types', $types );

		// Return filtered results.
		return $types;

	}

	/**
	 * Helper method to retrieve excluded Post Types, which should not send
	 * statuses to the API
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Excluded Post Types
	 */
	public function get_excluded_post_types() {

		// Get excluded Post Types.
		$types = array(
			'attachment',
			'revision',
			'elementor_library',
		);

		/**
		 * Defines the Post Type Objects that cannot have statues defined and not be sent to social media.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $types  Post Types.
		 */
		$types = apply_filters( 'social_post_flow_get_excluded_post_types', $types );

		// Return filtered results.
		return $types;

	}

	/**
	 * Helper method to retrieve excluded Taxonomies
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Excluded Post Types
	 */
	public function get_excluded_taxonomies() {

		// Get excluded Post Types.
		$taxonomies = array(
			'post_format',
			'nav_menu',
		);

		/**
		 * Defines taxonomies to exclude from the Conditions: Taxonomies dropdowns for each individual status.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $taxonomies     Excluded Taxonomies.
		 */
		$taxonomies = apply_filters( 'social_post_flow_get_excluded_taxonomies', $taxonomies );

		// Return filtered results.
		return $taxonomies;

	}

	/**
	 * Helper method to retrieve a Post Type's taxonomies
	 *
	 * @since   1.0.0
	 *
	 * @param   string $post_type  Post Type.
	 * @return  array               Taxonomies
	 */
	public function get_taxonomies( $post_type ) {

		// Get Post Type Taxonomies.
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );

		// Get excluded Taxonomies.
		$excluded_taxonomies = $this->get_excluded_taxonomies();

		// If excluded taxonomies exist, remove them from the taxonomies array now.
		if ( is_array( $excluded_taxonomies ) && count( $excluded_taxonomies ) > 0 ) {
			foreach ( $excluded_taxonomies as $excluded_taxonomy ) {
				unset( $taxonomies[ $excluded_taxonomy ] );
			}
		}

		/**
		 * Defines available taxonomies for the given Post Type, which are used in the Conditions: Taxonomies dropdowns
		 * for each individual status.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $taxonomies             Taxonomies.
		 * @param   string  $post_type              Post Type.
		 */
		$taxonomies = apply_filters( 'social_post_flow_get_taxonomies', $taxonomies, $post_type );

		// Return filtered results.
		return $taxonomies;

	}

	/**
	 * Helper method to retrieve all taxonomies
	 *
	 * @since   1.0.0
	 *
	 * @return  array               Taxonomies
	 */
	public function get_all_taxonomies() {

		// Get Post Type Taxonomies.
		$taxonomies = get_taxonomies( false, 'objects' );

		// Get excluded Taxonomies.
		$excluded_taxonomies = $this->get_excluded_taxonomies();

		// If excluded taxonomies exist, remove them from the taxonomies array now.
		if ( is_array( $excluded_taxonomies ) && count( $excluded_taxonomies ) > 0 ) {
			foreach ( $excluded_taxonomies as $excluded_taxonomy ) {
				unset( $taxonomies[ $excluded_taxonomy ] );
			}
		}

		/**
		 * Defines available taxonomies, regardless of Post Type, which are used in the Conditions: Taxonomies dropdowns
		 * for each individual status.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $taxonomies             Taxonomies.
		 */
		$taxonomies = apply_filters( 'social_post_flow_get_all_taxonomies', $taxonomies );

		// Return filtered results.
		return $taxonomies;

	}

	/**
	 * Helper method to retrieve all WordPress Roles
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Roles
	 */
	public function get_user_roles() {

		// Define roles.
		$roles = get_editable_roles();

		// Remove excluded roles.
		$excluded_roles = $this->get_excluded_user_roles();
		foreach ( $roles as $role_name => $role ) {
			if ( in_array( $role_name, $excluded_roles, true ) ) {
				unset( $roles[ $role_name ] );
			}
		}

		/**
		 * Defines WordPress User Roles.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $roles  WordPress User Roles.
		 */
		$roles = apply_filters( 'social_post_flow_get_user_roles', $roles );

		// Return filtered results.
		return $roles;

	}

	/**
	 * Helper method to retrieve all excluded WordPress Roles
	 *
	 * These roles are implied to have full access
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Excluded Roles
	 */
	public function get_excluded_user_roles() {

		// Define excluded roles.
		$excluded_roles = array();

		/**
		 * Defines WordPress User Roles to exclude from Settings screens.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $excluded_roles     Excluded WordPress User Roles
		 */
		$excluded_roles = apply_filters( 'social_post_flow_get_excluded_user_roles', $excluded_roles );

		// Return filtered results.
		return $excluded_roles;

	}

	/**
	 * Helper method to retrieve all repost frequency units (days, weeks etc)
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Repost Frequency Units
	 */
	public function get_repost_frequency_units() {

		// Define units.
		$units = array(
			'days'   => __( 'Days', 'social-post-flow' ),
			'months' => __( 'Months', 'social-post-flow' ),
			'years'  => __( 'Years', 'social-post-flow' ),
		);

		/**
		 * Defines available Reposting frequency units when defining Repost status(es).
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $units  Repost Frequency Units.
		 */
		$units = apply_filters( 'social_post_flow_get_repost_frequency_units', $units );

		// Return filtered results.
		return $units;

	}

	/**
	 * Helper method to retrieve available tags for status updates
	 *
	 * @since   1.0.0
	 *
	 * @param   string $post_type  Post Type.
	 * @return  array               Tags
	 */
	public function get_tags( $post_type ) {

		// Get post type.
		$post_types = $this->get_post_types();

		// Build tags array.
		$tags = array(
			'post'   => array(
				'{sitename}'              => __( 'Site Name', 'social-post-flow' ),
				'{title}'                 => __( 'Post Title', 'social-post-flow' ),
				'{excerpt}'               => __( 'Post Excerpt (Full)', 'social-post-flow' ),
				'{excerpt:characters(?)}' => array(
					'question'      => __( 'Enter the maximum number of characters the Post Excerpt should display.', 'social-post-flow' ),
					'default_value' => '150',
					'replace'       => '?',
					'label'         => __( 'Post Excerpt (Character Limited)', 'social-post-flow' ),
				),
				'{excerpt:words(?)}'      => array(
					'question'      => __( 'Enter the maximum number of words the Post Excerpt should display.', 'social-post-flow' ),
					'default_value' => '55',
					'replace'       => '?',
					'label'         => __( 'Post Excerpt (Word Limited)', 'social-post-flow' ),
				),
				'{excerpt:sentences(?)}'  => array(
					'question'      => __( 'Enter the maximum number of sentences the Post Excerpt should display.', 'social-post-flow' ),
					'default_value' => '1',
					'replace'       => '?',
					'label'         => __( 'Post Excerpt (Sentence Limited)', 'social-post-flow' ),
				),
				'{content}'               => __( 'Post Content (Full)', 'social-post-flow' ),
				'{content_more_tag}'      => __( 'Post Content (Up to More Tag)', 'social-post-flow' ),
				'{content:characters(?)}' => array(
					'question'      => __( 'Enter the maximum number of characters the Post Content should display.', 'social-post-flow' ),
					'default_value' => '150',
					'replace'       => '?',
					'label'         => __( 'Post Content (Character Limited)', 'social-post-flow' ),
				),
				'{content:words(?)}'      => array(
					'question'      => __( 'Enter the maximum number of words the Post Content should display.', 'social-post-flow' ),
					'default_value' => '55',
					'replace'       => '?',
					'label'         => __( 'Post Content (Word Limited)', 'social-post-flow' ),
				),
				'{content:sentences(?)}'  => array(
					'question'      => __( 'Enter the maximum number of sentences the Post Content should display.', 'social-post-flow' ),
					'default_value' => '1',
					'replace'       => '?',
					'label'         => __( 'Post Content (Sentence Limited)', 'social-post-flow' ),
				),
				'{date}'                  => __( 'Post Date', 'social-post-flow' ),
				'{url}'                   => __( 'Post URL', 'social-post-flow' ),
				'{url_short}'             => __( 'Post URL, Shortened', 'social-post-flow' ),
				'{id}'                    => __( 'Post ID', 'social-post-flow' ),
			),

			'author' => array(
				'{author_user_login}'    => __( 'Author Login', 'social-post-flow' ),
				'{author_user_nicename}' => __( 'Author Nice Name', 'social-post-flow' ),
				'{author_user_email}'    => __( 'Author Email', 'social-post-flow' ),
				'{author_user_url}'      => __( 'Author URL', 'social-post-flow' ),
				'{author_display_name}'  => __( 'Author Display Name', 'social-post-flow' ),
				'{author_field_NAME}'    => __( 'Author Meta Field', 'social-post-flow' ),
			),
		);

		// Add any taxonomies for the given Post Type, if the Post Type exists.
		$taxonomies = array();
		if ( isset( $post_types[ $post_type ] ) ) {
			// Get taxonomies specific to the Post Type.
			$taxonomies = $this->get_taxonomies( $post_type );
		} else {
			// We're on the Bulk Publishing Settings, so return all Taxonomies.
			$taxonomies = $this->get_all_taxonomies();
		}

		if ( count( $taxonomies ) > 0 ) {
			$tags['taxonomy'] = array();

			foreach ( $taxonomies as $tax => $details ) {
				$tags['taxonomy'][ '{taxonomy_' . $tax . '}' ] = sprintf(
					/* translators: Taxonomy Name, Singular */
					__( 'Taxonomy: %s: Hashtag Format', 'social-post-flow' ),
					$details->labels->singular_name
				);
				$tags['taxonomy'][ '{taxonomy_' . $tax . '_hashtag_retain_case}' ] = sprintf(
					/* translators: Taxonomy Name, Singular */
					__( 'Taxonomy: %s: Hashtag Format, Retaining Case', 'social-post-flow' ),
					$details->labels->singular_name
				);
				$tags['taxonomy'][ '{taxonomy_' . $tax . '_hashtag_underscore}' ] = sprintf(
					/* translators: Taxonomy Name, Singular */
					__( 'Taxonomy: %s: Hashtag Format, Underscores', 'social-post-flow' ),
					$details->labels->singular_name
				);
				$tags['taxonomy'][ '{taxonomy_' . $tax . '_name}' ] = sprintf(
					/* translators: Taxonomy Name, Singular */
					__( 'Taxonomy: %s: Name Format', 'social-post-flow' ),
					$details->labels->singular_name
				);
			}
		}

		/**
		 * Defines Dynamic Status Tags that can be inserted into status(es) for the given Post Type.
		 * These tags are also added to any 'Insert Tag' dropdowns.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $tags       Dynamic Status Tags.
		 * @param   string  $post_type  Post Type.
		 */
		$tags = apply_filters( 'social_post_flow_get_tags', $tags, $post_type );

		// If there are any Custom Tags defined in the Plugin Settings for this Post Type,
		// add them now.
		$existing_custom_tags = social_post_flow()->get_class( 'settings' )->get_setting( 'custom_tags', $post_type, '' );
		if ( ! empty( $existing_custom_tags ) && is_array( $existing_custom_tags ) && isset( $existing_custom_tags['key'] ) ) {
			foreach ( $existing_custom_tags['key'] as $index => $existing_custom_tag ) {
				// Skip empty keys.
				if ( empty( $existing_custom_tag ) ) {
					continue;
				}

				// Add custom tag to array.
				$tags['post'][ '{custom_field_' . $existing_custom_tags['key'][ $index ] . '}' ] = $existing_custom_tags['label'][ $index ];
			}
		}

		// Finally, append the generic Post Custom Field tag.
		$tags['post']['{custom_field_NAME}'] = __( 'Post Meta Field', 'social-post-flow' );

		// Return filtered results.
		return $tags;

	}

	/**
	 * Helper method to retrieve available tags for status updates, in a flattened
	 * key/value array
	 *
	 * @since   1.0.0
	 *
	 * @param   string $post_type  Post Type.
	 * @return  array               Tags
	 */
	public function get_tags_flat( $post_type ) {

		$tags_flat = array();
		foreach ( $this->get_tags( $post_type ) as $tag_group => $tag_group_tags ) {
			foreach ( $tag_group_tags as $tag => $tag_attributes ) {
				$tags_flat[] = array(
					'key'   => $tag,
					'value' => $tag,
				);
			}
		}

		return $tags_flat;

	}

	/**
	 * Helper method to retrieve Post actions
	 *
	 * @since   1.0.0
	 *
	 * @return  array           Post Actions
	 */
	public function get_post_actions() {

		// Build post actions.
		$actions = array(
			'publish'      => __( 'Publish', 'social-post-flow' ),
			'update'       => __( 'Update', 'social-post-flow' ),
			'repost'       => __( 'Repost', 'social-post-flow' ),
			'bulk_publish' => __( 'Bulk Publish', 'social-post-flow' ),
		);

		/**
		 * Defines the Post actions which trigger status(es) to be sent to social media.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $actions    Post Actions.
		 */
		$actions = apply_filters( 'social_post_flow_get_post_actions', $actions );

		// Return filtered results.
		return $actions;

	}

	/**
	 * Helper method to retrieve Post actions, with labels in the past tense.
	 *
	 * @since   1.0.0
	 *
	 * @return  array           Post Actions
	 */
	public function get_post_actions_past_tense() {

		// Build post actions.
		$actions = array(
			'publish'      => __( 'Published', 'social-post-flow' ),
			'update'       => __( 'Updated', 'social-post-flow' ),
			'repost'       => __( 'automatically reposted by this Plugin', 'social-post-flow' ),
			'bulk_publish' => __( 'manually bulk published using this Plugin\'s Bulk Publish functionality', 'social-post-flow' ),
		);

		/**
		 * Defines the Post actions which trigger status(es) to be sent to social media,
		 * with labels set to the past tense.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $actions    Post Actions.
		 */
		$actions = apply_filters( 'social_post_flow_get_post_actions_past_tense', $actions );

		// Return filtered results.
		return $actions;

	}

	/**
	 * Helper method to retrieve Conditional Options
	 *
	 * @since   1.0.0
	 *
	 * @return  array           Condition Options
	 */
	public function get_condition_options() {

		// Build condition options.
		$options = array(
			''            => __( 'No Conditions', 'social-post-flow' ),
			'include_any' => __( 'Post(s) must include ANY Terms', 'social-post-flow' ),
			'include_all' => __( 'Post(s) must include ALL Terms', 'social-post-flow' ),
			'exclude_any' => __( 'Post(s) must exclude ANY Terms', 'social-post-flow' ),
		);

		/**
		 * Defines the available Options for Taxonomy Terms Conditionals.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $options    Condition Options.
		 */
		$options = apply_filters( 'social_post_flow_get_condition_options', $options );

		// Return filtered results.
		return $options;

	}

	/**
	 * Helper method to retrieve Post Override Options
	 *
	 * @since   1.0.0
	 *
	 * @return  array       Post Override Options
	 */
	public function get_override_options() {

		// Build condition options.
		$options = array(
			'-1' => __( 'Do NOT Post to Social Post Flow', 'social-post-flow' ),
			'0'  => __( 'Use Plugin Settings', 'social-post-flow' ),
			'1'  => __( 'Post to Social Post Flow using Manual Settings', 'social-post-flow' ),
		);

		/**
		 * Defines the available override options to display in the meta box for individual Posts.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $options    Condition Options.
		 */
		$options = apply_filters( 'social_post_flow_get_override_options', $options );

		// Return filtered results.
		return $options;

	}

	/**
	 * Helper method to retrieve Authors
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Authors
	 */
	public function get_authors() {

		// Filter arguments to get Authors.
		$args = apply_filters(
			'social_post_flow_get_authors_args',
			array(
				'role__not_in' => 'subscriber',
			)
		);

		// Run query.
		$user_query = new WP_User_Query( $args );

		// Get Authors.
		$authors = $user_query->results;

		/**
		 * Defines the available override options to display in the meta box for individual Posts.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $authors    WordPress Users.
		 */
		$authors = apply_filters( 'social_post_flow_get_authors', $authors );

		// Return filtered results.
		return $authors;

	}

	/**
	 * Helper method to retrieve Post comparison operators, used for Conditional Options on status(es).
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Meta Compare options
	 */
	public function get_comparison_operators() {

		// Define meta compare options.
		$comparison_operators = array(
			'='         => __( 'Equals', 'social-post-flow' ),
			'!='        => __( 'Does not Equal', 'social-post-flow' ),
			'>'         => __( 'Greater Than', 'social-post-flow' ),
			'>='        => __( 'Greater Than or Equal To', 'social-post-flow' ),
			'<'         => __( 'Less Than', 'social-post-flow' ),
			'<='        => __( 'Less Than or Equal To', 'social-post-flow' ),
			'IN'        => __( 'In (Comma Separated Values)', 'social-post-flow' ),
			'NOT IN'    => __( 'Not In (Comma Separated Values)', 'social-post-flow' ),
			'LIKE'      => __( 'Like', 'social-post-flow' ),
			'NOT LIKE'  => __( 'Not Like', 'social-post-flow' ),
			'EMPTY'     => __( 'Empty (Value Ignored)', 'social-post-flow' ),
			'NOT EMPTY' => __( 'Not Empty (Value Ignored)', 'social-post-flow' ),
		);

		/**
		 * Backward compatible filter; defines the available Post comparison operators, used for Conditional Options on status(es).
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $comparison_operators    Comparison Operators.
		 */
		$comparison_operators = apply_filters( 'social_post_flow_get_meta_compare', $comparison_operators );

		/**
		 * Defines the available Post comparison operators, used for Conditional Options on status(es).
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $comparison_operators    Comparison Operators.
		 */
		$comparison_operators = apply_filters( 'social_post_flow_get_comparison_operators', $comparison_operators );

		// Return filtered results.
		return $comparison_operators;

	}

	/**
	 * Helper method to retrieve Custom Field comparison operators, used for Conditional Options on status(es).
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Meta Compare options
	 */
	public function get_custom_field_comparison_operators() {

		// Define comparison operators.
		$comparison_operators = array(
			'='          => __( 'Equals', 'social-post-flow' ),
			'!='         => __( 'Does not Equal', 'social-post-flow' ),
			'>'          => __( 'Greater Than', 'social-post-flow' ),
			'>='         => __( 'Greater Than or Equal To', 'social-post-flow' ),
			'<'          => __( 'Less Than', 'social-post-flow' ),
			'<='         => __( 'Less Than or Equal To', 'social-post-flow' ),
			'IN'         => __( 'In (Comma Separated Values)', 'social-post-flow' ),
			'NOT IN'     => __( 'Not In (Comma Separated Values)', 'social-post-flow' ),
			'LIKE'       => __( 'Like', 'social-post-flow' ),
			'NOT LIKE'   => __( 'Not Like', 'social-post-flow' ),
			'EMPTY'      => __( 'Empty (Value Ignored)', 'social-post-flow' ),
			'NOT EMPTY'  => __( 'Not Empty (Value Ignored)', 'social-post-flow' ),
			'NOT EXISTS' => __( 'Not Exists (Value Ignored)', 'social-post-flow' ),
		);

		/**
		 * Defines the available Custom Field comparison operators, used for Conditional Options on status(es).
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $comparison_operators   Comparison Operators.
		 */
		$comparison_operators = apply_filters( 'social_post_flow_get_custom_field_comparison_operators', $comparison_operators );

		// Return filtered results.
		return $comparison_operators;

	}

	/**
	 * Helper method to retrieve order by options
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Order By
	 */
	public function get_order_by() {

		// Define order by.
		$order_by = array(
			'date'          => __( 'Published Date', 'social-post-flow' ),
			'ID'            => __( 'Post ID', 'social-post-flow' ),
			'author'        => __( 'Post Author', 'social-post-flow' ),
			'title'         => __( 'Title', 'social-post-flow' ),
			'name'          => __( 'Post Name', 'social-post-flow' ),
			'modified'      => __( 'Modified Date', 'social-post-flow' ),
			'rand'          => __( 'Random', 'social-post-flow' ),
			'comment_count' => __( 'Number of Comments', 'social-post-flow' ),
		);

		/**
		 * Defines the available WP_Query compatible order by options.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $order_by   Order By options.
		 */
		$order_by = apply_filters( 'social_post_flow_get_order_by', $order_by );

		// Return filtered results.
		return $order_by;

	}

	/**
	 * Helper method to retrieve order options
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Order
	 */
	public function get_order() {

		// Define order.
		$order = array(
			'DESC' => __( 'Descending (Z-A / Newest to Oldest)', 'social-post-flow' ),
			'ASC'  => __( 'Ascending (A-Z / Oldest to Newest)', 'social-post-flow' ),
		);

		/**
		 * Defines the available WP_Query compatible order options.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $order   Order options.
		 */
		$order = apply_filters( 'social_post_flow_get_order', $order );

		// Return filtered results.
		return $order;

	}

	/**
	 * Helper method to return template tags that cannot have a character limit applied to them.
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Tags.
	 */
	public function get_tags_excluded_from_character_limit() {

		$tags = array(
			'date',
			'url',
			'id',
			'author_user_email',
			'author_user_url',
		);

		/**
		 * Defines the tags that cannot have a character limit applied to them, as doing so would
		 * wrongly concatenate data (e.g. a URL would become malformed).
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $tags   Tags.
		 */
		$tags = apply_filters( 'social_post_flow_get_tags_excluded_from_character_limit', $tags );

		// Return filtered results.
		return $tags;

	}

	/**
	 * Helper method to retrieve available TTF fonts for use with Text to Image
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Fonts
	 */
	public function get_fonts() {

		$fonts = array(
			'Lato-Regular'         => __( 'Lato (Regular)', 'social-post-flow' ),
			'Merriweather-Regular' => __( 'Merriweather (Regular)', 'social-post-flow' ),
			'Montserrat-Regular'   => __( 'Montserrat (Regular)', 'social-post-flow' ),
			'NotoSans-Regular'     => __( 'Noto Sans (Regular)', 'social-post-flow' ),
			'OpenSans-Regular'     => sprintf(
				'%s%s',
				__( 'Open Sans (Regular', 'social-post-flow' ),
				extension_loaded( 'imagick' ) ? __( ', with Emoji Support)', 'social-post-flow' ) : ')'
			),
			'Oswald-Regular'       => __( 'Oswald (Regular)', 'social-post-flow' ),
			'Raleway-Regular'      => __( 'Raleway (Regular)', 'social-post-flow' ),
		);

		/**
		 * Defines the available TTF fonts for use with Text to Image
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $fonts  Fonts.
		 */
		$fonts = apply_filters( 'social_post_flow_get_fonts', $fonts );

		// Return filtered results.
		return $fonts;

	}

	/**
	 * Helper method to retrieve transient expiration time
	 *
	 * @since   1.0.0
	 *
	 * @return  int     Expiration Time (seconds)
	 */
	public function get_transient_expiration_time() {

		// Set expiration time for all transients = 12 hours.
		$expiration_time = ( 12 * HOUR_IN_SECONDS );

		/**
		 * Defines the number of seconds before expiring transients.
		 *
		 * @since   1.0.0
		 *
		 * @param   int     $expiration_time    Transient Expiration Time, in seconds.
		 */
		$expiration_time = apply_filters( 'social_post_flow_get_transient_expiration_time', $expiration_time );

		// Return filtered results.
		return $expiration_time;

	}

	/**
	 * Helper method to remove array keys that the given WordPress User Role doesn't have access to
	 *
	 * Checks if Restrict Roles is enabled
	 *
	 * The array can be either Post Type Settings or Post Settings, as the top level keys will always
	 * be profile_ids
	 *
	 * @since   1.0.0
	 *
	 * @param   array  $arr    Post Type or Post Settings.
	 * @param   string $role   Role.
	 * @return                  Post Type or Post Settings
	 */
	public function maybe_remove_profiles_by_role( $arr, $role ) {

		// Check if restrict roles is enabled.
		$restrict_roles = (bool) social_post_flow()->get_class( 'settings' )->get_option( 'restrict_roles', 0 );
		if ( ! $restrict_roles ) {
			return $arr;
		}

		// Iterate through profiles, checking if the role has access to the profile.
		foreach ( $arr as $profile_id => $data ) {
			// Always grant access to default.
			if ( $profile_id === 'default' ) {
				continue;
			}

			// Get access for this role and profile combination.
			$access = (bool) social_post_flow()->get_class( 'settings' )->get_setting( 'roles', '[' . $role . '][' . $profile_id . ']', 0 );

			// If no access, remove profile from array.
			if ( ! $access ) {
				unset( $arr[ $profile_id ] );
			}
		}

		/**
		 * Defines the number of seconds before expiring transients.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $arr    Post Type or Post Settings.
		 * @param   string  $role   WordPress Role Name.
		 */
		$arr = apply_filters( 'social_post_flow_maybe_remove_profiles_by_role', $arr, $role );

		// Return filtered results.
		return $arr;

	}

	/**
	 * Helper method to remove array keys that the given WordPress User Role doesn't have access to
	 *
	 * Checks if Restrict Post Types is enabled
	 *
	 * @since   1.0.0
	 *
	 * @param   array  $post_types  Post Types.
	 * @param   string $role        Role.
	 * @return  array               Post Types
	 */
	public function maybe_remove_post_types_by_role( $post_types, $role ) {

		// Check if restrict post types is enabled.
		$restrict_post_types = (bool) social_post_flow()->get_class( 'settings' )->get_option( 'restrict_post_types', 0 );
		if ( ! $restrict_post_types ) {
			return $post_types;
		}

		// Iterate through profiles, checking if the role has access to the profile.
		foreach ( $post_types as $post_type => $post_type_object ) {
			// Get access for this role and profile combination.
			$access = (bool) social_post_flow()->get_class( 'settings' )->get_setting( 'roles', '[' . $role . '][' . $post_type . ']', 0 );

			// If no access, remove profile from array.
			if ( ! $access ) {
				unset( $post_types[ $post_type ] );
			}
		}

		/**
		 * Defines the number of seconds before expiring transients.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $post_types     Post Types.
		 * @param   string  $role           WordPress Role Name.
		 */
		$post_types = apply_filters( 'social_post_flow_maybe_remove_post_types_by_role', $post_types, $role );

		// Return filtered results.
		return $post_types;

	}

	/**
	 * Defines the registered filters that can be used on the Log WP_List_Table
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Filters
	 */
	public function get_log_filters() {

		// Define filters.
		$filters = array(
			'action',
			'profile_id',
			'result',
			'request_sent_start_date',
			'request_sent_end_date',
			'orderby',
			'order',
		);

		/**
		 * Defines the registered filters that can be used on the Log WP_List_Tables.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $filters    Filters.
		 */
		$filters = apply_filters( 'social_post_flow_get_log_filters', $filters );

		// Return filtered results.
		return $filters;

	}

}
