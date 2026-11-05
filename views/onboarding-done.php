<?php
/**
 * Outputs the onboarding Done step.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div id="wpzinc-onboarding-content">
	<h1><?php esc_html_e( 'You\'re all set!', 'social-post-flow' ); ?></h1>
	<p><?php esc_html_e( 'Publish a Post, and Social Post Flow will share it to your profiles.', 'social-post-flow' ); ?></p>
</div>

<div id="wpzinc-onboarding-footer">
	<div class="left">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=social-post-flow&tab=post&type=post' ) ); ?>" class="button button-hero">
			<?php esc_html_e( 'View settings', 'social-post-flow' ); ?>
		</a>
	</div>
	<div class="right">
		<a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>" class="button button-primary button-hero">
			<?php esc_html_e( 'Write a new Post', 'social-post-flow' ); ?>
		</a>
	</div>
</div>
