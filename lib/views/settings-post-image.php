<?php
/**
 * Outputs the UI on Posts to allow multiple images to be expressly
 * defined for use when sending a status update.
 *
 * @since   3.2.6
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 */

?>
<div class="wpzinc-option">
	<div class="full wpzinc-media-library-selector"
			data-input-name="<?php echo esc_attr( 'social-post-flow' ); ?>[additional_images][]"
			data-file-type="image"
			data-output-size="small"
			data-multiple="<?php echo esc_attr( $this->base->supports( 'additional_images' ) ? 'true' : 'false' ); ?>"
			data-limit="10">
		<ul class="images">
			<?php
			// Output any existing selected images.
			foreach ( $images as $i => $image ) {
				// Skip if no image defined.
				if ( ! $image['thumbnail_url'] ) {
					continue;
				}
				?>
				<li class="wpzinc-media-library-attachment">
					<div class="wpzinc-media-library-insert">
						<input type="hidden" name="<?php echo esc_attr( 'social-post-flow' ); ?>[additional_images][]" value="<?php echo esc_attr( ! $image['id'] ? '' : $image['id'] ); ?>" />
						<img src="<?php echo esc_attr( ! $image['thumbnail_url'] ? '' : $image['thumbnail_url'] ); ?>" />
					</div>
					<a href="#" class="wpzinc-media-library-remove" title="<?php esc_attr_e( 'Remove', 'social-post-flow' ); ?>"><?php esc_html_e( 'Remove', 'social-post-flow' ); ?></a>
				</li>
				<?php
			}
			?>
		</ul>

		<button class="wpzinc-media-library-insert button button-secondary">
			<?php
			if ( $this->base->supports( 'additional_images' ) ) {
				esc_html_e( 'Select Images', 'social-post-flow' );
			} else {
				esc_html_e( 'Select Image', 'social-post-flow' );
			}
			?>
		</button>
	</div>

	<?php
	// Output description depending on whether additional images are supported, or just the featured image.
	if ( $this->base->supports( 'additional_images' ) ) {
		?>
		<p class="description">
			<?php
			if ( $supports_opengraph ) {
				echo esc_html(
					sprintf(
					/* translators: Post Type Singular */
						__( 'The first image only replaces the Featured Image in a status where a status\' option is not set to "Use OpenGraph Settings". Additional images only work where a status\' option is set to "Use Featured Image, not Linked to %s".', 'social-post-flow' ),
						$post_type_object->labels->singular_name
					)
				);
			} else {
				echo esc_html(
					sprintf(
					/* translators: Post Type Singular */
						__( 'The first image only replaces the Featured Image in a status where a status\' option is not set to "No Image". Additional images only work where a status\' option is set to "Use Featured Image, not Linked to %s".', 'social-post-flow' ),
						$post_type_object->labels->singular_name
					)
				);
			}
			?>
		</p>
		<p class="description">
			<?php
			esc_html_e( 'Drag and drop images to reorder. The number of additional images included in a status will depend on the social network. Refer to the ', 'social-post-flow' );
			?>
			<a href="<?php echo esc_attr( $this->base->plugin->documentation_url ); ?>/featured-image-settings/" target="_blank"><?php esc_html_e( 'Documentation', 'social-post-flow' ); ?></a>
		</p>
		<?php
	} elseif ( $supports_opengraph ) {
		?>
		<p class="description">
			<?php
			echo esc_html(
				sprintf(
					__( 'This image only replaces the Featured Image in a status where a status\' option is not set to "Use OpenGraph Settings".', 'social-post-flow' ),
					$post_type_object->labels->singular_name
				)
			);
			?>
		</p>
		<?php
	} else {
		?>
		<p class="description">
			<?php
			echo esc_html(
				sprintf(
					/* translators: Post Type Singular */
					__( 'This image only replaces the Featured Image in a status where a status\' option is not set to "No Image".', 'social-post-flow' ),
					$post_type_object->labels->singular_name
				)
			);
			?>
		</p>
		<?php
	}
	?>
</div>
