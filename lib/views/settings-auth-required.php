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
		<?php echo esc_html( $this->base->plugin->displayName ); ?>

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
									echo esc_html(
										sprintf(
										/* translators: %1$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %2$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
											__( 'To allow this Plugin to post updates to your social media profiles using %1$s, please authorize %2$s below.', 'social-post-flow' ),
											$this->base->plugin->account,
											$this->base->plugin->account
										)
									);
									?>
								</p>
								<p class="description">
									<?php
									echo esc_html(
										sprintf(
										/* translators: %1$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
											__( 'Don\'t have a %1$s account?', 'social-post-flow' ),
											$this->base->plugin->account
										)
									);
									?>
									<a href="<?php echo esc_attr( social_post_flow()->get_class( 'api' )->get_registration_url() ); ?>" target="_blank" rel="nofollow noopener">
										<?php esc_html_e( 'Sign up for free', 'social-post-flow' ); ?>
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
