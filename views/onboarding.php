<?php
/**
 * Outputs the onboarding wizard, which walks the user through connecting profiles,
 * sharing Posts to them and finishing setup.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<header>
	<h1>
		<?php esc_html_e( 'Social Post Flow', 'social-post-flow' ); ?>

		<span>
			<?php esc_html_e( 'Setup', 'social-post-flow' ); ?>
		</span>
	</h1>
</header>

<div class="wrap">
	<div class="wrap-inner">
		<!-- Notices -->
		<hr class="wp-header-end" />
		<?php social_post_flow()->get_class( 'notices' )->output_notices(); ?>

		<div id="wpzinc-onboarding" class="social-post-flow-onboarding">
			<div id="wpzinc-onboarding-progress">
				<ol>
					<?php
					// Mark the current step, and those before it, as done.
					$social_post_flow_reached = true;
					foreach ( $steps as $social_post_flow_step => $social_post_flow_label ) {
						?>
						<li<?php echo $social_post_flow_reached ? ' class="done"' : ''; ?>><?php echo esc_html( $social_post_flow_label ); ?></li>
						<?php
						if ( $social_post_flow_step === $step ) {
							$social_post_flow_reached = false;
						}
					}
					?>
				</ol>
			</div>

			<div id="wpzinc-onboarding-form">
				<?php require SOCIAL_POST_FLOW_PLUGIN_PATH . 'views/onboarding-' . $step . '.php'; ?>
			</div>
		</div>
	</div>
</div>
