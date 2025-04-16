<?php
/**
 * SEOPress Plugin Class.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */

/**
 * Provides compatibility with SEOPress
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 4.3.8
 */
class Social_Post_Flow_SEOPress {

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

		

		// Register Status Tags.
		add_filter( 'social_post_flow_get_tags', array( $this, 'register_status_tags' ), 10, 1 );

		// Replace Tags with Values.
		add_filter( 'social_post_flow_publish_get_all_possible_searches_replacements', array( $this, 'register_searches_replacements' ), 10, 3 );

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

		// Bail if SEOPress isn't active.
		if ( ! $this->is_active() ) {
			return $tags;
		}

		// Register Status Tags.
		return array_merge(
			$tags,
			array(
				'seopress' => array(
					'{seopress_meta_title}'       => __( 'Meta Title', 'social-post-flow' ),
					'{seopress_meta_description}' => __( 'Meta Description', 'social-post-flow' ),
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

		// Bail if SEOPress isn't active.
		if ( ! $this->is_active() ) {
			return $searches_replacements;
		}

		// Register Tags and their replacement values.
		return array_merge( $searches_replacements, $this->get_searches_replacements( $post, $author ) );

	}

	/**
	 * Returns tags and their Post data replacements, that are supported for SEOPress.
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
			'seopress_meta_title'       => $this->get_title( $post ),
			'seopress_meta_description' => $this->get_description( $post ),
		);

		/**
		 * Registers any additional status message tags, and their Post data replacements, that are supported
		 * for SEOPress.
		 *
		 * @since   3.8.1
		 *
		 * @param   array       $searches_replacements  Registered Supported Tags and their Replacements.
		 * @param   WP_Post     $post                   WordPress Post.
		 * @param   WP_User     $author                 WordPress User (Author of the Post).
		 */
		$searches_replacements = apply_filters( 'social_post_flow_publish_register_seopress_searches_replacements', $searches_replacements, $post, $author );

		// Return filtered results.
		return $searches_replacements;

	}

	/**
	 * Return the Title
	 *
	 * @since   4.3.8
	 *
	 * @param   WP_Post $post   WordPress Post.
	 * @return  string              SEOPress Post Title
	 */
	private function get_title( $post ) {

		// Get title.
		return get_post_meta( $post->ID, '_seopress_titles_title', true );

	}

	/**
	 * Return the Description
	 *
	 * @since   4.3.8
	 *
	 * @param   WP_Post $post   WordPress Post.
	 * @return  string              SEOPress Post Description
	 */
	private function get_description( $post ) {

		return get_post_meta( $post->ID, '_seopress_titles_desc', true );

	}

	/**
	 * Checks if the SEOPress Plugin is active
	 *
	 * @since   4.3.8
	 *
	 * @return  bool    SEOPress Plugin Active
	 */
	private function is_active() {

		return defined( 'SEOPRESS_VERSION' );

	}

}
