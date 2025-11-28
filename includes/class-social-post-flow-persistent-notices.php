<?php
/**
 * Persistent Notices class.
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
 */

/**
 * Persists success, warning and error messages
 * across Admin Screens.
 *
 * @package   Social_Post_Flow
 * @author    Social Post Flow
 */
class Social_Post_Flow_Persistent_Notices {

	/**
	 * The key prefix to use for stored notices
	 *
	 * @since   1.1.3
	 *
	 * @var     string
	 */
	private $key_prefix = 'social-post-flow-persistent-notices';

	/**
	 * Register output function to display persistent notices
	 * in the WordPress Administration, if any exist.
	 *
	 * @since   1.1.3
	 */
	public function __construct() {

		add_action( 'admin_notices', array( $this, 'output' ) );

	}

	/**
	 * Output persistent notices in the WordPress Administration
	 *
	 * @since   1.1.3
	 */
	public function output() {

		// Bail if no notices exist.
		$notices = get_option( $this->key_prefix );
		if ( ! $notices ) {
			return;
		}

		// Output notices.
		foreach ( $notices as $notice ) {
			$output = '';

			switch ( $notice ) {
				case 'no_subscription':
					$output = sprintf(
						'%s <a href="https://app.socialpostflow.com/billing" target="_blank">%s</a> %s',
						__( 'Your trial to Social Post Flow has ended. Posts will not send from WordPress.', 'social-post-flow' ),
						__( 'Select a plan', 'social-post-flow' ),
						__( 'to resume posting to social media.', 'social-post-flow' )
					);
					break;
			}

			// If no output defined, skip.
			if ( empty( $output ) ) {
				continue;
			}
			?>

			<div class="notice notice-error">
				<p>
					<?php
					echo wp_kses(
						$output,
						wp_kses_allowed_html( 'post' )
					);
					?>
				</p>
			</div>
			<?php
		}

	}

	/**
	 * Add a persistent notice for output in the WordPress Administration.
	 *
	 * @since   1.1.3
	 *
	 * @param   string $notice     Notice name.
	 * @return  bool                Notice saved successfully
	 */
	public function add( $notice ) {

		// If no other persistent notices exist, add one now.
		if ( ! $this->exist() ) {
			return update_option( $this->key_prefix, array( $notice ) );
		}

		// Fetch existing persistent notices.
		$notices = $this->get();

		// Add notice to existing notices.
		$notices[] = $notice;

		// Remove any duplicate notices.
		$notices = array_values( array_unique( $notices ) );

		// Update and return.
		return update_option( $this->key_prefix, $notices );

	}

	/**
	 * Returns all notices stored in the options table.
	 *
	 * @since   1.1.3
	 *
	 * @return  array
	 */
	public function get() {

		// Fetch all notices from the options table.
		return get_option( $this->key_prefix );

	}

	/**
	 * Whether any persistent notices are stored in the option table.
	 *
	 * @since   1.1.3
	 *
	 * @return  bool
	 */
	public function exist() {

		if ( ! $this->get() ) {
			return false;
		}

		return true;

	}

	/**
	 * Delete all persistent notices.
	 *
	 * @since   1.1.3
	 *
	 * @param   string $notice     Notice name.
	 * @return  bool                Success
	 */
	public function delete( $notice ) {

		// If no persistent notices exist, there's nothing to delete.
		if ( ! $this->exist() ) {
			return false;
		}

		// Fetch existing persistent notices.
		$notices = $this->get();

		// Remove notice from existing notices.
		$index = array_search( $notice, $notices, true );
		if ( $index !== false ) {
			unset( $notices[ $index ] );
		}

		// Update and return.
		return update_option( $this->key_prefix, $notices );

	}

}
