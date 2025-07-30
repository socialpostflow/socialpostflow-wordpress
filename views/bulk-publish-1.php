<?php
/**
 * Outputs Bulk Publish View, Stage 1, for a Post Type
 *
 * @since 1.0.0
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>	
<!-- Post Type -->
<div id="<?php echo esc_attr( $post_type ); ?>-panel" class="panel">

	<!-- Post Selection Tool -->
	<div id="post-selection" class="postbox">
		<h3 class="hndle">
			<?php
			echo esc_html(
				sprintf(
				/* translators: %1$s: Post Type Name */
					__( 'Choose %1$s to Publish to Social Post Flow', 'social-post-flow' ),
					$post_types[ $post_type ]->labels->name
				)
			);
			?>
		</h3>

		<div class="posts">
			<!-- Posts -->
			<div class="wpzinc-option">
				<p class="description">
					<?php
					echo esc_html(
						sprintf(
						/* translators: %1$s: Number of Posts, %2$s: Post Type Name, %3$s: Post Type Name */
							__( '%1$s %2$s found matching your query.  Choose the %3$s below that you want to Publish to Social Post Flow.  They\'ll be sent to Social Post Flow in the order listed.', 'social-post-flow' ),
							count( $post_ids ),
							$post_types[ $post_type ]->labels->name,
							$post_types[ $post_type ]->labels->name,
						)
					);
					?>
				</p>
				<p class="description">
					<?php
					echo esc_html(
						sprintf(
						/* translators: Post Type Name */
							__( 'If you need to define the status(es) to send for bulk publishing %s', 'social-post-flow' ),
							$post_types[ $post_type ]->labels->name
						)
					);
					?>
					<a href="<?php echo esc_attr( admin_url( 'admin.php?page=social-post-flow&tab=post&type=' . $post_type ) ); ?>" target="_blank">
						<?php
						esc_html_e( 'click here', 'social-post-flow' );
						?>
					</a>
				</p>
			</div>
			<div class="wpzinc-option">
				<div class="full">
					<div class="tax-selection">
						<div class="tabs-panel" style="height: 200px;">
							<ul class="list:category categorychecklist form-no-clear" style="margin: 0; padding: 0;">				                    			
								<?php
								foreach ( $post_ids as $bulk_publish_post_id ) {
									?>
									<li>
										<label class="selectit">
											<input type="checkbox" name="social-post-flow[posts][<?php echo esc_attr( $bulk_publish_post_id ); ?>]" value="<?php echo esc_attr( $bulk_publish_post_id ); ?>" />
											<?php echo esc_html( get_the_title( $bulk_publish_post_id ) ); ?>      
										</label>
									</li>
									<?php
								}
								?>
							</ul>
						</div>
					</div>
				</div>
			</div>

			<div class="wpzinc-option">
				<div class="left">
					<label for="toggle"><?php esc_html_e( 'Select All', 'social-post-flow' ); ?></label>
				</div>

				<div class="right">
					<input type="checkbox" name="toggle" id="toggle" value="1" />

					<p class="description">
						<?php
						echo esc_html(
							sprintf(
							/* translators: Post Type Name */
								__( 'Check or uncheck this option to select / deselect all %s above.', 'social-post-flow' ),
								$post_types[ $post_type ]->labels->name
							)
						);
						?>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- /post_type -->

<!-- Buttons -->
<input type="hidden" name="post_ids" value="<?php echo esc_attr( implode( ',', $post_ids ) ); ?>" />
<input type="hidden" name="stage" value="2" />
<input type="submit" name="submit" value="<?php echo esc_attr__( 'Publish to Social Post Flow Now', 'social-post-flow' ); ?>" class="button button-primary" />
