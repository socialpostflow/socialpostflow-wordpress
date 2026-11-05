<?php
/**
 * Outputs the onboarding Share step, confirming the profiles that new Posts will be shared to.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div id="wpzinc-onboarding-content">
	<h1><?php esc_html_e( 'Share your Posts', 'social-post-flow' ); ?></h1>
	<?php
	if ( empty( $profiles ) ) {
		?>
		<p><?php esc_html_e( 'None of your profiles are set to share new Posts yet. Enable them in the Post settings.', 'social-post-flow' ); ?></p>
		<?php
	} else {
		?>
		<p><?php esc_html_e( 'When you publish a new Post, it\'ll be shared to these profiles automatically:', 'social-post-flow' ); ?></p>

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

	<p class="description">
		<?php
		printf(
			/* translators: Link to Post settings */
			esc_html__( 'Want to change the text, image or schedule? You can customise this in the %s at any time.', 'social-post-flow' ),
			'<a href="' . esc_url( admin_url( 'admin.php?page=social-post-flow&tab=post&type=post' ) ) . '">' . esc_html__( 'Post settings', 'social-post-flow' ) . '</a>'
		);
		?>
	</p>
</div>

<div id="wpzinc-onboarding-footer">
	<div class="left">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=social-post-flow&tab=post&type=post&onboarding=connect' ) ); ?>" class="button button-hero">
			<?php esc_html_e( 'Back', 'social-post-flow' ); ?>
		</a>
	</div>
	<div class="right">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=social-post-flow&tab=post&type=post&onboarding=done' ) ); ?>" class="button button-primary button-hero">
			<?php esc_html_e( 'Continue', 'social-post-flow' ); ?>
		</a>
	</div>
</div>
