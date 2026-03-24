<?php
/**
 * Outputs the UI on Posts to allow multiple images to be expressly
 * defined for use when sending a status update.
 *
 * @since   1.0.0
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="wpzinc-option">
	<div class="full wpzinc-media-library-selector"
			data-input-name="social-post-flow[additional_images][]"
			data-file-type="image"
			data-output-size="small"
			data-multiple="true"
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
						<input type="hidden" name="social-post-flow[additional_images][]" value="<?php echo esc_attr( ! $image['id'] ? '' : $image['id'] ); ?>" />
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
			esc_html_e( 'Select Images', 'social-post-flow' );
			?>
		</button>
	</div>

	<p class="description">
		<?php
		echo esc_html__( 'First image will be used instead of the Featured Image where a status\' type = Image, Story or Pin.', 'social-post-flow' );
		?>
	</p>
	<p class="description">
		<?php
		echo esc_html__( 'Additional images will be used where a status\' type = Image.', 'social-post-flow' );
		?>
	</p>
	<p class="description">
		<?php
		esc_html_e( 'Drag and drop images to reorder. The number of additional images included in a status will depend on the social network. Refer to the ', 'social-post-flow' );
		?>
		<a href="https://www.socialpostflow.com/documentation/wordpress-plugin/image-statuses/" target="_blank"><?php esc_html_e( 'Documentation', 'social-post-flow' ); ?></a>
	</p>
</div>
