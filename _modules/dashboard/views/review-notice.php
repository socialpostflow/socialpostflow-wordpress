<?php
/**
 * Outputs the review request notification.
 *
 * @package WPZincDashboardWidget
 * @author WP Zinc
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="notice notice-info is-dismissible wpzinc-review-<?php echo esc_attr( $this->plugin->name ); ?>">
	<?php
	if ( isset( $this->plugin->review_notice ) ) {
		?>
		<p>
			<?php echo esc_html( $this->plugin->review_notice ); ?>
		</p>
		<?php
	}
	?>
	<p>
		<?php
		printf(
			/* translators: Plugin Name */
			esc_html__( 'We\'d be super grateful if you could spread the word about %s and give it a 5 star rating on WordPress?', $this->plugin->name ), // phpcs:ignore WordPress.WP.I18n 
			esc_html( $this->plugin->displayName )
		);
		?>
	</p>
	<p>
		<a href="<?php echo esc_url( $this->get_review_url() ); ?>" class="button button-primary" target="_blank">
			<?php esc_html_e( 'Yes, Leave Review', $this->plugin->name ); // phpcs:ignore WordPress.WP.I18n ?>
		</a>
		<a href="<?php echo esc_url( $this->plugin->support_url ); ?>" class="button" rel="noopener" target="_blank">
			<?php
			printf(
				/* translators: Plugin Name */
				esc_html__( 'No, I\'m having issues with %s', $this->plugin->name ), // phpcs:ignore WordPress.WP.I18n
				esc_html( $this->plugin->displayName )
			);
			?>
		</a>
	</p>
</div>

