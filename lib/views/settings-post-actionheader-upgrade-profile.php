<?php
/**
 * Outputs an upgrade notice when accessing Instagram or Pinterest in the Free version of the plugin.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */

?>
<div class="wpzinc-option highlight">
	<div class="full">
		<h4><?php esc_html_e( 'Want to post to Instagram or Pinterest?', 'social-post-flow' ); ?></h4>

		<p>
			<?php
			echo esc_html(
				sprintf(
				/* translators: Plugin Name */
					__( '%s Pro allows you to post to Instagram (Stories and Posts) and Pinterest boards, with advanced controls for conditional publishing, tags and scheduling.', 'social-post-flow' ),
					$this->base->plugin->displayName
				)
			);
			?>
		</p>

		<a href="<?php echo esc_attr( $this->base->dashboard->get_upgrade_url( 'settings_inline_upgrade' ) ); ?>" class="button button-primary" target="_blank"><?php esc_html_e( 'Upgrade', 'social-post-flow' ); ?></a>
	</div>
</div>
