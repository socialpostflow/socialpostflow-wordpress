<?php
/**
 * Outputs a screen with a button/link/form to authenticate the Plugin
 * with the third party API service.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */

?>
<header>
	<h1>
		<?php esc_html_e( 'Social Post Flow', 'social-post-flow' ); ?>

		<span>
			<?php esc_html_e( 'Settings', 'social-post-flow' ); ?>
		</span>
	</h1>
</header>

<div class="wrap">
	<div class="wrap-inner">
		<!-- Notices -->
		<hr class="wp-header-end" />

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-1">
				<div id="post-body-content">
					<div id="normal-sortables" class="meta-box-sortables ui-sortable">
						<div class="postbox"> 
							<div class="wpzinc-option">
								<p class="description">
									<?php
									esc_html_e( 'To allow this Plugin to post updates to your social media profiles using Social Post Flow, please authorize Social Post Flow below.', 'social-post-flow' );
									?>
								</p>
								<p class="description">
									<?php
									esc_html_e( 'Don\'t have a Social Post Flow account?', 'social-post-flow' );
									?>
									<a href="<?php echo esc_attr( social_post_flow()->get_class( 'api' )->get_registration_url() ); ?>" target="_blank" rel="nofollow noopener">
										<?php esc_html_e( 'Sign up', 'social-post-flow' ); ?>
									</a>
								</p>
							</div>

							<?php
							/**
							 * Allow the API to output its authentication button link or form, to authenticate
							 * with the API.
							 *
							 * @since   4.2.0
							 *
							 * @param   array   $schedule   Schedule Options
							 */
							do_action( 'social_post_flow_output_auth' );
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
