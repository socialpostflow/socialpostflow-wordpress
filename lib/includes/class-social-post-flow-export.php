<?php
/**
 * Export class.
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Exports settings to a JSON or ZIP file, for use on other
 * Plugin installations.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 4.2.2
 */
class Social_Post_Flow_Export {

	/**
	 * Holds the base object.
	 *
	 * @since   4.2.2
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor.
	 *
	 * @since   4.2.2
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct() {

		

		// Import.
		add_action( 'social_post_flow_export', array( $this, 'export' ) );

	}

	/**
	 * Export data
	 *
	 * @since   4.2.2
	 *
	 * @param   array $data   Export Data.
	 * @return  array           Export Data
	 */
	public function export( $data ) {

		return array_merge( $data, social_post_flow()->get_class( 'settings' )->get_all() );

	}

}
