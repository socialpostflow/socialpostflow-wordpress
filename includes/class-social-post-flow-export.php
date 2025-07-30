<?php
/**
 * Export class.
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
 */

/**
 * Exports settings to a JSON or ZIP file, for use on other
 * Plugin installations.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 * @version 1.0.0
 */
class Social_Post_Flow_Export {

	/**
	 * Constructor.
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

		// Import.
		add_action( 'social_post_flow_export', array( $this, 'export' ) );

	}

	/**
	 * Export data
	 *
	 * @since   1.0.0
	 *
	 * @param   array $data   Export Data.
	 * @return  array           Export Data
	 */
	public function export( $data ) {

		return array_merge( $data, social_post_flow()->get_class( 'settings' )->get_all() );

	}

}
