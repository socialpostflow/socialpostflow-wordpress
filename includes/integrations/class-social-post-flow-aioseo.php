<?php
/**
 * AIOSEO Plugin Class.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */

/**
 * Provides compatibility with All in One SEO
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */
class Social_Post_Flow_AIOSEO {

	/**
	 * Constructor
	 *
	 * @since   1.0.0
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
	 * @since   1.0.0
	 *
	 * @param   array $plugins    Plugins supporting OpenGraph.
	 * @return  array               Plugins
	 */
	public function register_opengraph_seo_plugins( $plugins ) {

		$plugins[] = 'all-in-one-seo-pack/all_in_one_seo_pack.php';
		$plugins[] = 'all-in-one-seo-pack-pro/all_in_one_seo_pack.php';
		return $plugins;

	}

	/**
	 * Defines Dynamic Status Tags that can be inserted into status(es) for the given Post Type.
	 * These tags are also added to any 'Insert Tag' dropdowns.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $tags       Tags.
	 * @return  array               Tags
	 */
	public function register_status_tags( $tags ) {

		// Bail if All in One SEO isn't active.
		if ( ! $this->is_active() ) {
			return $tags;
		}

		// Register Status Tags.
		return array_merge(
			$tags,
			array(
				'all_in_one_seo_pack' => array(
					'{aioseo_meta_title}'       => __( 'Meta Title', 'social-post-flow' ),
					'{aioseo_meta_description}' => __( 'Meta Description', 'social-post-flow' ),
				),
			)
		);

	}

	/**
	 * Registers any additional status message tags, and their Post data replacements, that are supported.
	 *
	 * @since   1.0.0
	 *
	 * @param   array   $searches_replacements  Registered Supported Tags and their Replacements.
	 * @param   WP_Post $post                   WordPress Post.
	 * @param   WP_User $author                 WordPress User (Author of the Post).
	 * @return  array                               Registered Supported Tags and their Replacements
	 */
	public function register_searches_replacements( $searches_replacements, $post, $author ) {

		// Bail if All in One SEO isn't active.
		if ( ! $this->is_active() ) {
			return $searches_replacements;
		}

		// Register Tags and their replacement values.
		return array_merge( $searches_replacements, $this->get_searches_replacements( $post, $author ) );

	}

	/**
	 * Returns tags and their Post data replacements, that are supported for AIOSEO.
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post                   WordPress Post.
	 * @param   WP_User $author                 WordPress User (Author of the Post).
	 * @return  array                               Registered Supported Tags and their Replacements
	 */
	private function get_searches_replacements( $post, $author ) {

		// Store Title and Description.
		$searches_replacements = array(
			'aioseo_meta_title'       => $this->get_title( $post ),
			'aioseo_meta_description' => $this->get_description( $post ),
		);

		/**
		 * Registers any additional status message tags, and their Post data replacements, that are supported
		 * for AIOSEO.
		 *
		 * @since   1.0.0
		 *
		 * @param   array       $searches_replacements  Registered Supported Tags and their Replacements.
		 * @param   WP_Post     $post                   WordPress Post.
		 * @param   WP_User     $author                 WordPress User (Author of the Post).
		 */
		$searches_replacements = apply_filters( 'social_post_flow_publish_register_aio_seo_searches_replacements', $searches_replacements, $post, $author );

		// Return filtered results.
		return $searches_replacements;

	}

	/**
	 * Return the Title
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post   WordPress Post.
	 * @return  string              AIOSEO Post Title
	 */
	private function get_title( $post ) {

		// Bail if helper function doesn't exist.
		if ( ! function_exists( 'aioseo' ) ) {
			return '';
		}

		return aioseo()->meta->title->getTitle( $post );

	}

	/**
	 * Return the Description
	 *
	 * @since   1.0.0
	 *
	 * @param   WP_Post $post   WordPress Post.
	 * @return  string              AIOSEO Post Description
	 */
	private function get_description( $post ) {

		// Bail if helper function doesn't exist.
		if ( ! function_exists( 'aioseo' ) ) {
			return '';
		}

		return aioseo()->meta->description->getDescription( $post );

	}

	/**
	 * Checks if the All in One SEO Plugin is active
	 *
	 * @since   1.0.0
	 *
	 * @return  bool    All in One SEO Plugin Active
	 */
	private function is_active() {

		return defined( 'AIOSEO_DIR' );

	}

}
