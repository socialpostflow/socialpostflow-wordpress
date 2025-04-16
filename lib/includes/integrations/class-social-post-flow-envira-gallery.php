<?php
/**
 * Envira Gallery Plugin Class.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */

/**
 * Provides compatibility with All in One SEO
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 4.8.4
 */
class Social_Post_Flow_Envira_Gallery {

	/**
	 * Holds the base object.
	 *
	 * @since   4.8.4
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   4.8.4
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct() {

		

		add_filter( 'envira_gallery_metabox_ids', array( $this, 'permit_meta_boxes' ) );

	}

	/**
	 * Permits this Plugin's Post Metaboxes for display on Envira Galleries.
	 *
	 * @since   4.8.4
	 *
	 * @param   array $meta_box_ids   Meta Box ID names to display on Envira Galleries.
	 * @return  array                   Meta Box ID names to display on Envira Galleries
	 */
	public function permit_meta_boxes( $meta_box_ids ) {

		$meta_box_ids[] = 'social-post-flow';
		$meta_box_ids[] = 'social-post-flow-image';

		return $meta_box_ids;

	}

}
