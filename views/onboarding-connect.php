<?php
/**
 * Outputs the onboarding Connect step, listing connected profiles and the networks the user can connect.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div id="wpzinc-onboarding-content">
	<?php
	if ( empty( $profiles ) ) {
		?>
		<h1><?php esc_html_e( 'Where do you want to post?', 'social-post-flow' ); ?></h1>
		<p><?php esc_html_e( 'Pick a network to connect. You\'ll sign in to it, choose the page or profile to post to, then come back here.', 'social-post-flow' ); ?></p>
		<?php
	} else {
		?>
		<h1><?php esc_html_e( 'Connect more profiles, or continue', 'social-post-flow' ); ?></h1>
		<p><?php esc_html_e( 'Connect every profile you want to share your WordPress content to, then continue.', 'social-post-flow' ); ?></p>

		<ul class="social-post-flow-onboarding-profiles">
			<?php
			foreach ( $profiles as $social_post_flow_profile ) {
				?>
				<li>
					<span class="dashicons dashicons-yes"></span>
					<strong><?php echo esc_html( $social_post_flow_profile['provider_name'] ); ?>:</strong>
					<?php echo esc_html( $social_post_flow_profile['profile_name'] ); ?>
				</li>
				<?php
			}
			?>
		</ul>
		<?php
	}
	?>

	<div class="social-post-flow-connect-profiles-grid">
		<?php
		foreach ( $providers as $social_post_flow_provider => $social_post_flow_provider_data ) {
			?>
			<div class="social-post-flow-connect-profiles-provider">
				<img src="<?php echo esc_url( SOCIAL_POST_FLOW_PLUGIN_URL . $social_post_flow_provider_data['icon'] ); ?>" alt="<?php echo esc_attr( $social_post_flow_provider_data['name'] ); ?>" width="32" height="32" />
				<div class="social-post-flow-connect-profiles-provider-name">
					<strong><?php echo esc_html( $social_post_flow_provider_data['name'] ); ?></strong>
					<span><?php echo esc_html( $social_post_flow_provider_data['subtitle'] ); ?></span>
				</div>
				<a href="<?php echo esc_url( add_query_arg( 'return_to', rawurlencode( $return_url ), social_post_flow()->get_class( 'api' )->get_connect_profiles_url( $social_post_flow_provider ) ) ); ?>" class="button">
					<?php esc_html_e( 'Connect', 'social-post-flow' ); ?>
				</a>
			</div>
			<?php
		}
		?>
	</div>
</div>

<?php
if ( ! empty( $profiles ) ) {
	?>
	<div id="wpzinc-onboarding-footer">
		<div class="left"></div>
		<div class="right">
			<a href="<?php echo esc_url( add_query_arg( 'onboarding', 'share', $return_url ) ); ?>" class="button button-primary button-hero">
				<?php esc_html_e( 'Continue', 'social-post-flow' ); ?>
			</a>
		</div>
	</div>
	<?php
}
