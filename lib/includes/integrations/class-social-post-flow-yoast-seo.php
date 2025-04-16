<?php
/**
 * Yoast SEO Plugin Class.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */

/**
 * Provides compatibility with Yoast SEO
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 4.3.8
 */
class Social_Post_Flow_Yoast_SEO {

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

		

		// Register this integration as supporting OpenGraph.
		add_filter( 'social_post_flow_get_opengraph_seo_plugins', array( $this, 'register_opengraph_seo_plugins' ) );

		// Register Status Tags.
		add_filter( 'social_post_flow_get_tags', array( $this, 'register_status_tags' ), 10, 1 );

		// Replace Tags with Values.
		add_filter( 'social_post_flow_publish_get_all_possible_searches_replacements', array( $this, 'register_searches_replacements' ), 10, 3 );

	}

	/**
	 * Register this integration as supporting OpenGraph, so that the Plugin
	 * can check if it's active, and if so offer the "Use OpenGraph Settings"
	 * image option on statuses.
	 *
	 * @since   4.8.4
	 *
	 * @param   array $plugins    Plugins supporting OpenGraph.
	 * @return  array               Plugins
	 */
	public function register_opengraph_seo_plugins( $plugins ) {

		$plugins[] = 'wordpress-seo/wp-seo.php';
		$plugins[] = 'wordpress-seo-premium/wp-seo-premium.php';
		return $plugins;

	}

	/**
	 * Defines Dynamic Status Tags that can be inserted into status(es) for the given Post Type.
	 * These tags are also added to any 'Insert Tag' dropdowns.
	 *
	 * @since   4.3.8
	 *
	 * @param   array $tags       Tags.
	 * @return  array               Tags
	 */
	public function register_status_tags( $tags ) {

		// Bail if Yoast SEO isn't active.
		if ( ! $this->is_active() ) {
			return $tags;
		}

		// Register Status Tags.
		return array_merge(
			$tags,
			array(
				'yoast_seo' => array(
					'{yoast_seo_meta_title}'             => __( 'Meta Title', 'social-post-flow' ),
					'{yoast_seo_meta_description}'       => __( 'Meta Description', 'social-post-flow' ),
					'{yoast_seo_twitter_title}'          => __( 'Twitter Title', 'social-post-flow' ),
					'{yoast_seo_twitter_description}'    => __( 'Twitter Description', 'social-post-flow' ),
					'{yoast_seo_open_graph_title}'       => __( 'Facebook Title', 'social-post-flow' ),
					'{yoast_seo_open_graph_description}' => __( 'Facebook Description', 'social-post-flow' ),
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

		// Bail if Yoast SEO isn't active.
		if ( ! $this->is_active() ) {
			return $searches_replacements;
		}

		// Register Tags and their replacement values.
		return array_merge( $searches_replacements, $this->get_searches_replacements( $post, $author ) );

	}

	/**
	 * Returns tags and their Post data replacements, that are supported for Yoast SEO.
	 *
	 * @since   4.3.8
	 *
	 * @param   WP_Post $post                   WordPress Post.
	 * @param   WP_User $author                 WordPress User (Author of the Post).
	 * @return  array                               Registered Supported Tags and their Replacements
	 */
	private function get_searches_replacements( $post, $author ) {

		// Store Title and Description.
		$searches_replacements = array(
			'yoast_seo_meta_title'             => $this->get_title( $post ),
			'yoast_seo_meta_description'       => $this->get_description( $post ),
			'yoast_seo_twitter_title'          => '',
			'yoast_seo_twitter_description'    => '',
			'yoast_seo_open_graph_title'       => '',
			'yoast_seo_open_graph_description' => '',
		);

		// Fetch Social Metadata from DB.
		global $wpdb;
		$social_meta_data = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT 
        		twitter_title AS yoast_seo_twitter_title,
        		twitter_description AS yoast_seo_twitter_description,
        		open_graph_title AS yoast_seo_open_graph_title,
        		open_graph_description AS yoast_seo_open_graph_description
        		FROM ' . $wpdb->prefix . "yoast_indexable
        		WHERE object_id = %d
        		AND object_type = 'post'
        		LIMIT 1",
				$post->ID
			),
			ARRAY_A
		);

		// Merge Social Metadata array if data exists.
		if ( ! is_null( $social_meta_data ) ) {
			$searches_replacements = array_merge( $searches_replacements, $social_meta_data );
		}

		/**
		 * Registers any additional status message tags, and their Post data replacements, that are supported
		 * for Yoast SEO.
		 *
		 * @since   3.8.1
		 *
		 * @param   array       $searches_replacements  Registered Supported Tags and their Replacements.
		 * @param   WP_Post     $post                   WordPress Post.
		 * @param   WP_User     $author                 WordPress User (Author of the Post).
		 */
		$searches_replacements = apply_filters( 'social_post_flow_publish_register_yoast_seo_searches_replacements', $searches_replacements, $post, $author );

		// Return filtered results.
		return $searches_replacements;

	}

	/**
	 * Return the Title
	 *
	 * @since   4.3.8
	 *
	 * @param   WP_Post $post   WordPress Post.
	 * @return  string              Yoast Post Title
	 */
	private function get_title( $post ) {

		// Get Title.
		$title = WPSEO_Meta::get_value( 'title', $post->ID );
		if ( empty( $title ) ) {
			// Get Title from Post Type Options.
			$title = str_replace( ' %%page%% ', ' ', WPSEO_Options::get( 'title-' . $post->post_type ) );
		}
		$title = wpseo_replace_vars( $title, $post );

		return $title;

	}

	/**
	 * Return the Description
	 *
	 * @since   4.3.8
	 *
	 * @param   WP_Post $post   WordPress Post.
	 * @return  string              Yoast Post Description
	 */
	private function get_description( $post ) {

		// Get Description.
		$description = WPSEO_Meta::get_value( 'metadesc', $post->ID );
		if ( empty( $description ) ) {
			// Get Description from Post Type Options.
			$description = WPSEO_Options::get( 'metadesc-' . $post->post_type );
		}
		$description = wpseo_replace_vars( $description, $post );

		return $description;

	}

	/**
	 * Checks if the Yoast SEO Plugin is active
	 *
	 * @since   4.3.8
	 *
	 * @return  bool    Yoast SEO Plugin Active
	 */
	private function is_active() {

		return defined( 'WPSEO_FILE' );

	}

}
