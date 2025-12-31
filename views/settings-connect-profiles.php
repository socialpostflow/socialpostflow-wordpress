<?php
/**
 * Outputs the connect profiles screen, allowing the user to begin the process of connecting
 * their social media accounts to Social Post Flow, without showing other settings.
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
			<?php esc_html_e( 'Connect Profiles', 'social-post-flow' ); ?>
		</span>
	</h1>
</header>

<div class="wrap">
	<div class="wrap-inner">
		<!-- Notices -->
		<hr class="wp-header-end" />

		<?php
		// Output an error notice that the user needs to connect their profiles to Social Post Flow.
		?>
		<div class="notice notice-error">
			<p>
				<?php esc_html_e( 'Connect profiles to Social Post Flow below to start sending WordPress content to social media.', 'social-post-flow' ); ?>
			</p>
		</div>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-1">
				<div id="post-body-content">
					<div id="normal-sortables" class="meta-box-sortables ui-sortable">
						<div class="inside">
							<div class="wpzinc-grid wpzinc-grid-columns-3">
								<?php
								foreach ( $providers as $provider => $provider_data ) {
									?>
									<div class="wpzinc-grid-item">
										<strong><?php echo esc_html( $provider_data['name'] ); ?></strong>
										<a href="<?php echo esc_attr( social_post_flow()->get_class( 'api' )->get_connect_profiles_url( $provider ) ); ?>" class="button" target="_blank">
											<?php esc_html_e( 'Connect', 'social-post-flow' ); ?>
										</a>
									</div>
									<?php
								}
								?>
							</div>
						</div>

						<a href="<?php echo esc_url( admin_url( 'admin.php?page=social-post-flow&tab=post&type=post' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'I\'ve connected profiles to Social Post Flow', 'social-post-flow' ); ?>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
