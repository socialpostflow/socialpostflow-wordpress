<?php
/**
 * Outputs a fixed overlay toast-style notification.
 *
 * @package WPZincDashboardWidget
 * @author WP Zinc
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div id="<?php echo esc_attr( 'social-post-flow' ); ?>-notification" class="wpzinc-notification"></div>
