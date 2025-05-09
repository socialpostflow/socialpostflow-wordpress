<?php
/**
 * Outputs Settings View when no Profiles are connected to the API
 *
 * @since    3.0.0
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */

?>
<div class="postbox">
	<div class="wpzinc-option">
		<p class="description">
			<?php
			esc_html_e__( 'You must connect at least one social media account in Social Post Flow for this Plugin to send status updates to it.', 'social-post-flow' );
			?>
		</p>
		<p class="description">
			<?php esc_html_e( 'Once complete, refresh this page to enable and configure statuses for each social media account.', 'social-post-flow' ); ?>
		</p>
	</div>
	<div class="wpzinc-option">
		<a href="<?php echo esc_attr( social_post_flow()->get_class( 'api' )->get_connect_profiles_url() ); ?>" target="_blank" rel="nofollow noopener" class="button button-primary">
			<?php esc_html_e( 'Connect Profiles', 'social-post-flow' ); ?>
		</a>
	</div>
</div>
