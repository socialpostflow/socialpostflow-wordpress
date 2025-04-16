<?php
/**
 * GD Image class.
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Adds functionality to WordPress' GD Image Editor class
 * for adding padding to a source image to produce an
 * image that is the required width and height.
 *
 * This is primarily used by Social_Post_Flow_Image when
 * an image needs to meet a required aspect ratio for
 * publication to social media.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 5.0.0
 */
class Social_Post_Flow_Image_GD extends WP_Image_Editor_GD {

	/**
	 * Copies the image into the center of a new, larger canvas.
	 *
	 * @since   5.0.0
	 *
	 * @param   int $dst_w  Destination image width.
	 * @param   int $dst_h  Destination image height.
	 * @return  bool|WP_Error
	 */
	public function pad( $dst_w, $dst_h ) {

		// Create target image canvas that matches the required width and height.
		$dst = wp_imagecreatetruecolor( (int) $dst_w, (int) $dst_h );

		if ( function_exists( 'imageantialias' ) ) {
			imageantialias( $dst, true );
		}

		// Calculate padding for the original image.
		$padding_top    = ( $dst_h - $this->size['height'] ) / 2;
		$padding_bottom = $dst_h - $this->size['height'] - $padding_top;

		// Copy source image to the target image, centered.
		// This provides an image that is of the correct width and height to meet the aspect ratio.
		imagecopy(
			$dst,
			$this->image,
			( $dst_w - $this->size['width'] ) / 2,
			$padding_top,
			0,
			0,
			$this->size['width'],
			$this->size['height']
		);

		if ( is_gd_image( $dst ) ) {
			imagedestroy( $this->image );
			$this->image = $dst;
			$this->update_size();
			return true;
		}

		return new WP_Error( 'image_crop_error', __( 'Image crop failed.', 'social-post-flow' ), $this->file );

	}

}
