<?php
/**
 * Outputs when an error occured in Bulk Publish.
 *
 * @since 3.0.5
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */

?>
<header>
	<h1>
		<?php echo esc_html_e( 'Social Post Flow', 'social-post-flow' ); ?>
		<span>
			<?php esc_html_e( 'Bulk Publish', 'social-post-flow' ); ?>
		</span>
	</h1>
</header>

<hr class="wp-header-end" />

<div class="wrap">
	<?php
	// Output notices.
	social_post_flow()->get_class( 'notices' )->output_notices();
	?>
</div>
