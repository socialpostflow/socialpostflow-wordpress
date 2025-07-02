<?php
/**
 * Text to Image class, using PHP's GD Library.
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Creates images from text and an optional background image or color.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 1.0.0
 */
class Social_Post_Flow_Text_To_Image_GD {

	/**
	 * Holds the image created from imagecreatetruecolor
	 *
	 * @since   1.0.0
	 *
	 * @var     resource
	 */
	protected $im = false;

	/**
	 * Holds the image mime type
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	protected $mime = 'image/png';

	/**
	 * Holds the font size
	 *
	 * @since   1.0.0
	 *
	 * @var     int
	 */
	protected $text_size = 12;

	/**
	 * Holds the font color RGBA values
	 *
	 * @since   1.0.0
	 *
	 * @var     int
	 */
	protected $text_color = array(
		'r' => 0,
		'g' => 0,
		'b' => 0,
		'a' => 0,
	);

	/**
	 * Holds the text horizontal and vertical alignment
	 *
	 * @since   1.0.0
	 *
	 * @var     int
	 */
	protected $text_align = array(
		'x' => 'center',
		'y' => 'center',
	);

	/**
	 * Determines if text should be wrapped onto newlines if it overflows
	 *
	 * @since   1.0.0
	 *
	 * @var     bool
	 */
	protected $text_wrapping_overflow = true;

	/**
	 * Holds the text line height
	 *
	 * @since   1.0.0
	 *
	 * @var     decimal
	 */
	protected $line_height = 1.25;

	/**
	 * Holds the text baseline alignment
	 *
	 * @since   1.0.0
	 *
	 * @var     decimal
	 */
	protected $baseline = 0.2;

	/**
	 * Holds the text font face
	 *
	 * @since   1.0.0
	 *
	 * @var     int
	 */
	protected $font_face = null;

	/**
	 * Holds the text background color
	 *
	 * @since   1.0.0
	 *
	 * @var     int
	 */
	protected $text_background_color = false;

	/**
	 * Holds the text box dimensions and inner padding
	 *
	 * @since   1.0.0
	 *
	 * @var     int
	 */
	protected $box = array(
		'x'      => 0,
		'y'      => 0,
		'width'  => 100,
		'height' => 100,
	);

	/**
	 * Creates a new image of the specified dimensions with optional background color, ready for text
	 * to then be applied
	 *
	 * @since   1.0.0
	 *
	 * @param   int   $width              Image Width.
	 * @param   int   $height             Image Height.
	 * @param   mixed $background_color   (string) HEX, (array) RGBA, (bool) false.
	 */
	public function create( $width, $height, $background_color ) {

		// Convert hex to rgba.
		if ( ! is_array( $background_color ) ) {
			$background_color = $this->hex_to_rgba( $background_color );
		}

		$this->im = imagecreatetruecolor( $width, $height );
		imagefill( $this->im, 0, 0, imagecolorallocate( $this->im, $background_color['r'], $background_color['g'], $background_color['b'] ) );

	}

	/**
	 * Load an existing image, ready for text to then be applied
	 *
	 * @since   1.0.0
	 *
	 * @param   int $attachment_id  Attachment ID.
	 * @return  mixed                   WP_Error | array (width,height)
	 */
	public function load( $attachment_id ) {

		// Load image width and height.
		$image = wp_get_attachment_image_src( $attachment_id, 'full' );

		// Load image path from WordPress.
		$image_path = wp_get_original_image_path( $attachment_id );

		// Bail if image could not be found.
		if ( ! $image || ! $image_path ) {
			return new WP_Error( 'social_post_flow_load_attachment_missing', __( 'Could not find the background image.', 'social-post-flow' ) );
		}

		// Load as a JPEG or PNG.
		$this->mime = get_post_mime_type( $attachment_id );
		if ( ! $this->mime ) {
			return new WP_Error( 'social_post_flow_load_attachment_missing', __( 'Could not determine MIME type of the background image.', 'social-post-flow' ) );
		}
		switch ( $this->mime ) {
			case 'image/png':
				$background_image = imagecreatefrompng( $image_path );
				break;

			case 'image/jpeg':
				$background_image = imagecreatefromjpeg( $image_path );
				break;

			default:
				return new WP_Error( 'social_post_flow_load_attachment_missing', __( 'Unsupported MIME type for the background image.', 'social-post-flow' ) );
		}

		// If the background image is false, something went wrong.
		if ( ! $background_image ) {
			return new WP_Error(
				'social_post_flow_load_attachment_error',
				__( 'An error occured when attempting to to load the background image.', 'social-post-flow' )
			);
		}

		// Create blank image matching required width and height.
		$this->im = imagecreatetruecolor( $image[1], $image[2] );

		// Copy background image to new image.
		// We can't just return $background_image as the resource, because when we want to define the text color
		// later on, imagecolorallocate() will fail as it's constrained by the background image color pallete.
		imagecopyresampled( $this->im, $background_image, 0, 0, 0, 0, $image[1], $image[2], $image[1], $image[2] );

		// Return width and height of image.
		return array(
			$image[1],
			$image[2],
		);

	}

	/**
	 * Utility function to add centered text in the given font face, size and color
	 *
	 * @since   1.0.0
	 *
	 * @param   string $text                   Text.
	 * @param   string $font_face              Path and Filename to Font File.
	 * @param   int    $text_size              Font Size, in pixels.
	 * @param   mixed  $text_color             (string) HEX, (array) RGBA, (bool) false.
	 * @param   mixed  $text_background_color  (string) HEX, (array) RGBA, (bool) false.
	 * @param   int    $width                  Text Width (should not exceed the image's width from create() or load()).
	 * @param   int    $height                 Text Height (should not exceed the image's height from create() or load()).
	 * @param   int    $padding                Padding to apply between the width and height and the text.
	 */
	public function add_text( $text, $font_face, $text_size, $text_color, $text_background_color, $width, $height, $padding ) {

		$this->set_font_face( $font_face );
		$this->set_text_size( $text_size );
		$this->set_text_color( $text_color );
		$this->set_text_background_color( $text_background_color );
		$this->set_text_box( $padding, $padding, $width - ( $padding * 2 ), $height - ( $padding * 2 ) );
		$this->draw( $text );

	}

	/**
	 * Output the generated image into the browser
	 *
	 * @since   1.0.0
	 */
	public function output() {

		header( 'Content-Type: ' . $this->mime );

		switch ( $this->mime ) {

			case 'image/png':
				imagepng( $this->im );
				break;

			case 'image/jpeg':
				imagejpeg( $this->im );
				break;

		}

		die();

	}

	/**
	 * Save the generated image to a temporary filename.
	 *
	 * @since   1.0.0
	 *
	 * @return  string  Image Destination Path and Filename
	 */
	public function save_tmp() {

		// Define temporary destination.
		$destination = get_temp_dir() . 'social-post-flow-text-to-image-' . bin2hex( random_bytes( 5 ) );

		// Save Image to Destination Path and File.
		switch ( $this->mime ) {

			case 'image/png':
				imagepng( $this->im, $destination );
				break;

			case 'image/jpeg':
				imagejpeg( $this->im, $destination );
				break;

		}

		// Return Destination Path and File.
		return $destination;

	}

	/**
	 * Sets the text color
	 *
	 * @since   1.0.0
	 *
	 * @param   mixed $color  Color (array (r,g,b,a) or hex).
	 */
	public function set_text_color( $color ) {

		if ( ! $color ) {
			return false;
		}

		// Convert hex to rgba.
		if ( ! is_array( $color ) ) {
			$color = $this->hex_to_rgba( $color );
		}

		$this->text_color = $color;

	}

	/**
	 * Sets the font face to the given font file's path and filename
	 *
	 * @since   1.0.0
	 *
	 * @param   string $path   Path and filename.
	 */
	public function set_font_face( $path ) {

		$this->font_face = $path;

	}

	/**
	 * Sets the font size, in pixels
	 *
	 * @since   1.0.0
	 *
	 * @param   int $pixels     Font Size.
	 */
	public function set_text_size( $pixels ) {

		$this->text_size = $pixels;

	}

	/**
	 * Sets the background color
	 *
	 * @since   1.0.0
	 *
	 * @param   mixed $color  Text Color (Hex string or RGBA array).
	 */
	public function set_text_background_color( $color ) {

		if ( ! $color ) {
			return;
		}

		// Convert hex to rgba.
		if ( ! is_array( $color ) ) {
			$color = $this->hex_to_rgba( $color );
		}

		$this->text_background_color = $color;

	}

	/**
	 * Sets the text line height
	 *
	 * @since   1.0.0
	 *
	 * @param   decimal $line_height  Height of the single text line, in percents, proportionally to font size.
	 */
	public function set_line_height( $line_height ) {

		$this->line_height = $line_height;

	}

	/**
	 * Sets the text line height
	 *
	 * @since   1.0.0
	 *
	 * @param   decimal $baseline  Position of baseline, in percents, proportionally to line height measuring from the bottom.
	 */
	public function set_baseline( $baseline ) {

		$this->baseline = $baseline;

	}

	/**
	 * Sets the text alignment
	 *
	 * @since   1.0.0
	 *
	 * @param   string $x  Horizontal alignment (left, center, right).
	 * @param   string $y  Vertical alignment (top, center, bottom).
	 */
	public function set_text_alignment( $x = 'left', $y = 'top' ) {

		$this->text_align = array(
			'x' => $x,
			'y' => $y,
		);

	}

	/**
	 * Defines the text box size and padding
	 *
	 * @since   1.0.0
	 *
	 * @param   int $x      Padding, in pixels from left edge of image.
	 * @param   int $y      Padding, in pixels from top edge of image.
	 * @param   int $width  Width of texbox in pixels.
	 * @param   int $height Height of textbox in pixels.
	 */
	public function set_text_box( $x, $y, $width, $height ) {

		$this->box = array(
			'x'      => $x,
			'y'      => $y,
			'width'  => $width,
			'height' => $height,
		);

	}

	/**
	 * Draws the given text on the image
	 *
	 * @since   1.0.0
	 *
	 * @param   string $text   Text to draw. May contain newline characters.
	 */
	public function draw( $text ) {

		// Bail if a font face wasn't defined.
		if ( ! isset( $this->font_face ) ) {
			return new WP_Error( 'social_post_flow_gd_text_draw_missing_font_face', __( 'You must specify a font file.', 'social-post-flow' ) );
		}

		// Strip emojis from text, as they're not supported in GD.
		$text = preg_replace( '/\x{1F3F4}\x{E0067}\x{E0062}(?:\x{E0077}\x{E006C}\x{E0073}|\x{E0073}\x{E0063}\x{E0074}|\x{E0065}\x{E006E}\x{E0067})\x{E007F}|(?:\x{1F9D1}\x{1F3FF}\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D})?|\x{200D}(?:\x{1F48B}\x{200D})?)\x{1F9D1}|\x{1F469}\x{1F3FF}\x{200D}\x{1F91D}\x{200D}[\x{1F468}\x{1F469}]|\x{1FAF1}\x{1F3FF}\x{200D}\x{1FAF2})[\x{1F3FB}-\x{1F3FE}]|(?:\x{1F9D1}\x{1F3FE}\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D})?|\x{200D}(?:\x{1F48B}\x{200D})?)\x{1F9D1}|\x{1F469}\x{1F3FE}\x{200D}\x{1F91D}\x{200D}[\x{1F468}\x{1F469}]|\x{1FAF1}\x{1F3FE}\x{200D}\x{1FAF2})[\x{1F3FB}-\x{1F3FD}\x{1F3FF}]|(?:\x{1F9D1}\x{1F3FD}\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D})?|\x{200D}(?:\x{1F48B}\x{200D})?)\x{1F9D1}|\x{1F469}\x{1F3FD}\x{200D}\x{1F91D}\x{200D}[\x{1F468}\x{1F469}]|\x{1FAF1}\x{1F3FD}\x{200D}\x{1FAF2})[\x{1F3FB}\x{1F3FC}\x{1F3FE}\x{1F3FF}]|(?:\x{1F9D1}\x{1F3FC}\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D})?|\x{200D}(?:\x{1F48B}\x{200D})?)\x{1F9D1}|\x{1F469}\x{1F3FC}\x{200D}\x{1F91D}\x{200D}[\x{1F468}\x{1F469}]|\x{1FAF1}\x{1F3FC}\x{200D}\x{1FAF2})[\x{1F3FB}\x{1F3FD}-\x{1F3FF}]|(?:\x{1F9D1}\x{1F3FB}\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D})?|\x{200D}(?:\x{1F48B}\x{200D})?)\x{1F9D1}|\x{1F469}\x{1F3FB}\x{200D}\x{1F91D}\x{200D}[\x{1F468}\x{1F469}]|\x{1FAF1}\x{1F3FB}\x{200D}\x{1FAF2})[\x{1F3FC}-\x{1F3FF}]|\x{1F468}(?:\x{1F3FB}(?:\x{200D}(?:\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D}\x{1F468}[\x{1F3FB}-\x{1F3FF}]|\x{1F468}[\x{1F3FB}-\x{1F3FF}])|\x{200D}(?:\x{1F48B}\x{200D}\x{1F468}[\x{1F3FB}-\x{1F3FF}]|\x{1F468}[\x{1F3FB}-\x{1F3FF}]))|\x{1F91D}\x{200D}\x{1F468}[\x{1F3FC}-\x{1F3FF}]|[\x{2695}\x{2696}\x{2708}]\x{FE0F}|[\x{2695}\x{2696}\x{2708}]|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]))?|[\x{1F3FC}-\x{1F3FF}]\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D}\x{1F468}[\x{1F3FB}-\x{1F3FF}]|\x{1F468}[\x{1F3FB}-\x{1F3FF}])|\x{200D}(?:\x{1F48B}\x{200D}\x{1F468}[\x{1F3FB}-\x{1F3FF}]|\x{1F468}[\x{1F3FB}-\x{1F3FF}]))|\x{200D}(?:\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D})?|\x{200D}(?:\x{1F48B}\x{200D})?)\x{1F468}|[\x{1F468}\x{1F469}]\x{200D}(?:\x{1F466}\x{200D}\x{1F466}|\x{1F467}\x{200D}[\x{1F466}\x{1F467}])|\x{1F466}\x{200D}\x{1F466}|\x{1F467}\x{200D}[\x{1F466}\x{1F467}]|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F3FF}\x{200D}(?:\x{1F91D}\x{200D}\x{1F468}[\x{1F3FB}-\x{1F3FE}]|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F3FE}\x{200D}(?:\x{1F91D}\x{200D}\x{1F468}[\x{1F3FB}-\x{1F3FD}\x{1F3FF}]|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F3FD}\x{200D}(?:\x{1F91D}\x{200D}\x{1F468}[\x{1F3FB}\x{1F3FC}\x{1F3FE}\x{1F3FF}]|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F3FC}\x{200D}(?:\x{1F91D}\x{200D}\x{1F468}[\x{1F3FB}\x{1F3FD}-\x{1F3FF}]|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|(?:\x{1F3FF}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FE}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FD}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FC}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{200D}[\x{2695}\x{2696}\x{2708}])\x{FE0F}|\x{200D}(?:[\x{1F468}\x{1F469}]\x{200D}[\x{1F466}\x{1F467}]|[\x{1F466}\x{1F467}])|\x{1F3FF}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FE}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FD}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FC}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FF}|\x{1F3FE}|\x{1F3FD}|\x{1F3FC}|\x{200D}[\x{2695}\x{2696}\x{2708}])?|(?:\x{1F469}(?:\x{1F3FB}\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D}[\x{1F468}\x{1F469}]|[\x{1F468}\x{1F469}])|\x{200D}(?:\x{1F48B}\x{200D}[\x{1F468}\x{1F469}]|[\x{1F468}\x{1F469}]))|[\x{1F3FC}-\x{1F3FF}]\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D}[\x{1F468}\x{1F469}]|[\x{1F468}\x{1F469}])|\x{200D}(?:\x{1F48B}\x{200D}[\x{1F468}\x{1F469}]|[\x{1F468}\x{1F469}])))|\x{1F9D1}[\x{1F3FB}-\x{1F3FF}]\x{200D}\x{1F91D}\x{200D}\x{1F9D1})[\x{1F3FB}-\x{1F3FF}]|\x{1F469}\x{200D}\x{1F469}\x{200D}(?:\x{1F466}\x{200D}\x{1F466}|\x{1F467}\x{200D}[\x{1F466}\x{1F467}])|\x{1F469}(?:\x{200D}(?:\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D}[\x{1F468}\x{1F469}]|[\x{1F468}\x{1F469}])|\x{200D}(?:\x{1F48B}\x{200D}[\x{1F468}\x{1F469}]|[\x{1F468}\x{1F469}]))|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F3FF}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FE}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FD}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FC}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FB}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F9D1}(?:\x{200D}(?:\x{1F91D}\x{200D}\x{1F9D1}|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F384}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F3FF}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F384}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FE}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F384}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FD}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F384}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FC}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F384}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FB}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F384}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F469}\x{200D}\x{1F466}\x{200D}\x{1F466}|\x{1F469}\x{200D}\x{1F469}\x{200D}[\x{1F466}\x{1F467}]|\x{1F469}\x{200D}\x{1F467}\x{200D}[\x{1F466}\x{1F467}]|(?:\x{1F441}\x{FE0F}?\x{200D}\x{1F5E8}|\x{1F9D1}(?:\x{1F3FF}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FE}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FD}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FC}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FB}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{200D}[\x{2695}\x{2696}\x{2708}])|\x{1F469}(?:\x{1F3FF}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FE}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FD}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FC}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FB}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{200D}[\x{2695}\x{2696}\x{2708}])|\x{1F636}\x{200D}\x{1F32B}|\x{1F3F3}\x{FE0F}?\x{200D}\x{26A7}|\x{1F43B}\x{200D}\x{2744}|(?:[\x{1F3C3}\x{1F3C4}\x{1F3CA}\x{1F46E}\x{1F470}\x{1F471}\x{1F473}\x{1F477}\x{1F481}\x{1F482}\x{1F486}\x{1F487}\x{1F645}-\x{1F647}\x{1F64B}\x{1F64D}\x{1F64E}\x{1F6A3}\x{1F6B4}-\x{1F6B6}\x{1F926}\x{1F935}\x{1F937}-\x{1F939}\x{1F93D}\x{1F93E}\x{1F9B8}\x{1F9B9}\x{1F9CD}-\x{1F9CF}\x{1F9D4}\x{1F9D6}-\x{1F9DD}][\x{1F3FB}-\x{1F3FF}]|[\x{1F46F}\x{1F9DE}\x{1F9DF}])\x{200D}[\x{2640}\x{2642}]|[\x{26F9}\x{1F3CB}\x{1F3CC}\x{1F575}](?:[\x{FE0F}\x{1F3FB}-\x{1F3FF}]\x{200D}[\x{2640}\x{2642}]|\x{200D}[\x{2640}\x{2642}])|\x{1F3F4}\x{200D}\x{2620}|[\x{1F3C3}\x{1F3C4}\x{1F3CA}\x{1F46E}\x{1F470}\x{1F471}\x{1F473}\x{1F477}\x{1F481}\x{1F482}\x{1F486}\x{1F487}\x{1F645}-\x{1F647}\x{1F64B}\x{1F64D}\x{1F64E}\x{1F6A3}\x{1F6B4}-\x{1F6B6}\x{1F926}\x{1F935}\x{1F937}-\x{1F939}\x{1F93C}-\x{1F93E}\x{1F9B8}\x{1F9B9}\x{1F9CD}-\x{1F9CF}\x{1F9D4}\x{1F9D6}-\x{1F9DD}]\x{200D}[\x{2640}\x{2642}]|[\xA9\xAE\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}\x{21AA}\x{231A}\x{231B}\x{2328}\x{23CF}\x{23ED}-\x{23EF}\x{23F1}\x{23F2}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}\x{25AB}\x{25B6}\x{25C0}\x{25FB}\x{25FC}\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}\x{2615}\x{2618}\x{2620}\x{2622}\x{2623}\x{2626}\x{262A}\x{262E}\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{265F}\x{2660}\x{2663}\x{2665}\x{2666}\x{2668}\x{267B}\x{267E}\x{267F}\x{2692}\x{2694}-\x{2697}\x{2699}\x{269B}\x{269C}\x{26A0}\x{26A7}\x{26AA}\x{26B0}\x{26B1}\x{26BD}\x{26BE}\x{26C4}\x{26C8}\x{26CF}\x{26D1}\x{26D3}\x{26E9}\x{26F0}-\x{26F5}\x{26F7}\x{26F8}\x{26FA}\x{2702}\x{2708}\x{2709}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2733}\x{2734}\x{2744}\x{2747}\x{2763}\x{27A1}\x{2934}\x{2935}\x{2B05}-\x{2B07}\x{2B1B}\x{2B1C}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F170}\x{1F171}\x{1F17E}\x{1F17F}\x{1F202}\x{1F237}\x{1F321}\x{1F324}-\x{1F32C}\x{1F336}\x{1F37D}\x{1F396}\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}\x{1F39F}\x{1F3CD}\x{1F3CE}\x{1F3D4}-\x{1F3DF}\x{1F3F5}\x{1F3F7}\x{1F43F}\x{1F4FD}\x{1F549}\x{1F54A}\x{1F56F}\x{1F570}\x{1F573}\x{1F576}-\x{1F579}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F5A5}\x{1F5A8}\x{1F5B1}\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}\x{1F6CB}\x{1F6CD}-\x{1F6CF}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6F0}\x{1F6F3}])\x{FE0F}|\x{1F441}\x{FE0F}?\x{200D}\x{1F5E8}|\x{1F9D1}(?:\x{1F3FF}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FE}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FD}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FC}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FB}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{200D}[\x{2695}\x{2696}\x{2708}])|\x{1F469}(?:\x{1F3FF}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FE}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FD}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FC}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FB}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{200D}[\x{2695}\x{2696}\x{2708}])|\x{1F3F3}\x{FE0F}?\x{200D}\x{1F308}|\x{1F469}\x{200D}\x{1F467}|\x{1F469}\x{200D}\x{1F466}|\x{1F636}\x{200D}\x{1F32B}|\x{1F3F3}\x{FE0F}?\x{200D}\x{26A7}|\x{1F635}\x{200D}\x{1F4AB}|\x{1F62E}\x{200D}\x{1F4A8}|\x{1F415}\x{200D}\x{1F9BA}|\x{1FAF1}(?:\x{1F3FF}|\x{1F3FE}|\x{1F3FD}|\x{1F3FC}|\x{1F3FB})?|\x{1F9D1}(?:\x{1F3FF}|\x{1F3FE}|\x{1F3FD}|\x{1F3FC}|\x{1F3FB})?|\x{1F469}(?:\x{1F3FF}|\x{1F3FE}|\x{1F3FD}|\x{1F3FC}|\x{1F3FB})?|\x{1F43B}\x{200D}\x{2744}|(?:[\x{1F3C3}\x{1F3C4}\x{1F3CA}\x{1F46E}\x{1F470}\x{1F471}\x{1F473}\x{1F477}\x{1F481}\x{1F482}\x{1F486}\x{1F487}\x{1F645}-\x{1F647}\x{1F64B}\x{1F64D}\x{1F64E}\x{1F6A3}\x{1F6B4}-\x{1F6B6}\x{1F926}\x{1F935}\x{1F937}-\x{1F939}\x{1F93D}\x{1F93E}\x{1F9B8}\x{1F9B9}\x{1F9CD}-\x{1F9CF}\x{1F9D4}\x{1F9D6}-\x{1F9DD}][\x{1F3FB}-\x{1F3FF}]|[\x{1F46F}\x{1F9DE}\x{1F9DF}])\x{200D}[\x{2640}\x{2642}]|[\x{26F9}\x{1F3CB}\x{1F3CC}\x{1F575}](?:[\x{FE0F}\x{1F3FB}-\x{1F3FF}]\x{200D}[\x{2640}\x{2642}]|\x{200D}[\x{2640}\x{2642}])|\x{1F3F4}\x{200D}\x{2620}|\x{1F1FD}\x{1F1F0}|\x{1F1F6}\x{1F1E6}|\x{1F1F4}\x{1F1F2}|\x{1F408}\x{200D}\x{2B1B}|\x{2764}(?:\x{FE0F}\x{200D}[\x{1F525}\x{1FA79}]|\x{200D}[\x{1F525}\x{1FA79}])|\x{1F441}\x{FE0F}?|\x{1F3F3}\x{FE0F}?|[\x{1F3C3}\x{1F3C4}\x{1F3CA}\x{1F46E}\x{1F470}\x{1F471}\x{1F473}\x{1F477}\x{1F481}\x{1F482}\x{1F486}\x{1F487}\x{1F645}-\x{1F647}\x{1F64B}\x{1F64D}\x{1F64E}\x{1F6A3}\x{1F6B4}-\x{1F6B6}\x{1F926}\x{1F935}\x{1F937}-\x{1F939}\x{1F93C}-\x{1F93E}\x{1F9B8}\x{1F9B9}\x{1F9CD}-\x{1F9CF}\x{1F9D4}\x{1F9D6}-\x{1F9DD}]\x{200D}[\x{2640}\x{2642}]|\x{1F1FF}[\x{1F1E6}\x{1F1F2}\x{1F1FC}]|\x{1F1FE}[\x{1F1EA}\x{1F1F9}]|\x{1F1FC}[\x{1F1EB}\x{1F1F8}]|\x{1F1FB}[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1F3}\x{1F1FA}]|\x{1F1FA}[\x{1F1E6}\x{1F1EC}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1FE}\x{1F1FF}]|\x{1F1F9}[\x{1F1E6}\x{1F1E8}\x{1F1E9}\x{1F1EB}-\x{1F1ED}\x{1F1EF}-\x{1F1F4}\x{1F1F7}\x{1F1F9}\x{1F1FB}\x{1F1FC}\x{1F1FF}]|\x{1F1F8}[\x{1F1E6}-\x{1F1EA}\x{1F1EC}-\x{1F1F4}\x{1F1F7}-\x{1F1F9}\x{1F1FB}\x{1F1FD}-\x{1F1FF}]|\x{1F1F7}[\x{1F1EA}\x{1F1F4}\x{1F1F8}\x{1F1FA}\x{1F1FC}]|\x{1F1F5}[\x{1F1E6}\x{1F1EA}-\x{1F1ED}\x{1F1F0}-\x{1F1F3}\x{1F1F7}-\x{1F1F9}\x{1F1FC}\x{1F1FE}]|\x{1F1F3}[\x{1F1E6}\x{1F1E8}\x{1F1EA}-\x{1F1EC}\x{1F1EE}\x{1F1F1}\x{1F1F4}\x{1F1F5}\x{1F1F7}\x{1F1FA}\x{1F1FF}]|\x{1F1F2}[\x{1F1E6}\x{1F1E8}-\x{1F1ED}\x{1F1F0}-\x{1F1FF}]|\x{1F1F1}[\x{1F1E6}-\x{1F1E8}\x{1F1EE}\x{1F1F0}\x{1F1F7}-\x{1F1FB}\x{1F1FE}]|\x{1F1F0}[\x{1F1EA}\x{1F1EC}-\x{1F1EE}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F7}\x{1F1FC}\x{1F1FE}\x{1F1FF}]|\x{1F1EF}[\x{1F1EA}\x{1F1F2}\x{1F1F4}\x{1F1F5}]|\x{1F1EE}[\x{1F1E8}-\x{1F1EA}\x{1F1F1}-\x{1F1F4}\x{1F1F6}-\x{1F1F9}]|\x{1F1ED}[\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1F9}\x{1F1FA}]|\x{1F1EC}[\x{1F1E6}\x{1F1E7}\x{1F1E9}-\x{1F1EE}\x{1F1F1}-\x{1F1F3}\x{1F1F5}-\x{1F1FA}\x{1F1FC}\x{1F1FE}]|\x{1F1EB}[\x{1F1EE}-\x{1F1F0}\x{1F1F2}\x{1F1F4}\x{1F1F7}]|\x{1F1EA}[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1F7}-\x{1F1FA}]|\x{1F1E9}[\x{1F1EA}\x{1F1EC}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F4}\x{1F1FF}]|\x{1F1E8}[\x{1F1E6}\x{1F1E8}\x{1F1E9}\x{1F1EB}-\x{1F1EE}\x{1F1F0}-\x{1F1F5}\x{1F1F7}\x{1F1FA}-\x{1F1FF}]|\x{1F1E7}[\x{1F1E6}\x{1F1E7}\x{1F1E9}-\x{1F1EF}\x{1F1F1}-\x{1F1F4}\x{1F1F6}-\x{1F1F9}\x{1F1FB}\x{1F1FC}\x{1F1FE}\x{1F1FF}]|\x{1F1E6}[\x{1F1E8}-\x{1F1EC}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F4}\x{1F1F6}-\x{1F1FA}\x{1F1FC}\x{1F1FD}\x{1F1FF}]|[#\*0-9]\x{FE0F}?\x{20E3}|\x{1F93C}[\x{1F3FB}-\x{1F3FF}]|\x{2764}\x{FE0F}?|[\x{1F3C3}\x{1F3C4}\x{1F3CA}\x{1F46E}\x{1F470}\x{1F471}\x{1F473}\x{1F477}\x{1F481}\x{1F482}\x{1F486}\x{1F487}\x{1F645}-\x{1F647}\x{1F64B}\x{1F64D}\x{1F64E}\x{1F6A3}\x{1F6B4}-\x{1F6B6}\x{1F926}\x{1F935}\x{1F937}-\x{1F939}\x{1F93D}\x{1F93E}\x{1F9B8}\x{1F9B9}\x{1F9CD}-\x{1F9CF}\x{1F9D4}\x{1F9D6}-\x{1F9DD}][\x{1F3FB}-\x{1F3FF}]|[\x{26F9}\x{1F3CB}\x{1F3CC}\x{1F575}][\x{FE0F}\x{1F3FB}-\x{1F3FF}]?|\x{1F3F4}|[\x{270A}\x{270B}\x{1F385}\x{1F3C2}\x{1F3C7}\x{1F442}\x{1F443}\x{1F446}-\x{1F450}\x{1F466}\x{1F467}\x{1F46B}-\x{1F46D}\x{1F472}\x{1F474}-\x{1F476}\x{1F478}\x{1F47C}\x{1F483}\x{1F485}\x{1F48F}\x{1F491}\x{1F4AA}\x{1F57A}\x{1F595}\x{1F596}\x{1F64C}\x{1F64F}\x{1F6C0}\x{1F6CC}\x{1F90C}\x{1F90F}\x{1F918}-\x{1F91F}\x{1F930}-\x{1F934}\x{1F936}\x{1F977}\x{1F9B5}\x{1F9B6}\x{1F9BB}\x{1F9D2}\x{1F9D3}\x{1F9D5}\x{1FAC3}-\x{1FAC5}\x{1FAF0}\x{1FAF2}-\x{1FAF6}][\x{1F3FB}-\x{1F3FF}]|[\x{261D}\x{270C}\x{270D}\x{1F574}\x{1F590}][\x{FE0F}\x{1F3FB}-\x{1F3FF}]|[\x{261D}\x{270A}-\x{270D}\x{1F385}\x{1F3C2}\x{1F3C7}\x{1F408}\x{1F415}\x{1F43B}\x{1F442}\x{1F443}\x{1F446}-\x{1F450}\x{1F466}\x{1F467}\x{1F46B}-\x{1F46D}\x{1F472}\x{1F474}-\x{1F476}\x{1F478}\x{1F47C}\x{1F483}\x{1F485}\x{1F48F}\x{1F491}\x{1F4AA}\x{1F574}\x{1F57A}\x{1F590}\x{1F595}\x{1F596}\x{1F62E}\x{1F635}\x{1F636}\x{1F64C}\x{1F64F}\x{1F6C0}\x{1F6CC}\x{1F90C}\x{1F90F}\x{1F918}-\x{1F91F}\x{1F930}-\x{1F934}\x{1F936}\x{1F93C}\x{1F977}\x{1F9B5}\x{1F9B6}\x{1F9BB}\x{1F9D2}\x{1F9D3}\x{1F9D5}\x{1FAC3}-\x{1FAC5}\x{1FAF0}\x{1FAF2}-\x{1FAF6}]|[\x{1F3C3}\x{1F3C4}\x{1F3CA}\x{1F46E}\x{1F470}\x{1F471}\x{1F473}\x{1F477}\x{1F481}\x{1F482}\x{1F486}\x{1F487}\x{1F645}-\x{1F647}\x{1F64B}\x{1F64D}\x{1F64E}\x{1F6A3}\x{1F6B4}-\x{1F6B6}\x{1F926}\x{1F935}\x{1F937}-\x{1F939}\x{1F93D}\x{1F93E}\x{1F9B8}\x{1F9B9}\x{1F9CD}-\x{1F9CF}\x{1F9D4}\x{1F9D6}-\x{1F9DD}]|[\x{1F46F}\x{1F9DE}\x{1F9DF}]|[\xA9\xAE\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}\x{21AA}\x{231A}\x{231B}\x{2328}\x{23CF}\x{23ED}-\x{23EF}\x{23F1}\x{23F2}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}\x{25AB}\x{25B6}\x{25C0}\x{25FB}\x{25FC}\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}\x{2615}\x{2618}\x{2620}\x{2622}\x{2623}\x{2626}\x{262A}\x{262E}\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{265F}\x{2660}\x{2663}\x{2665}\x{2666}\x{2668}\x{267B}\x{267E}\x{267F}\x{2692}\x{2694}-\x{2697}\x{2699}\x{269B}\x{269C}\x{26A0}\x{26A7}\x{26AA}\x{26B0}\x{26B1}\x{26BD}\x{26BE}\x{26C4}\x{26C8}\x{26CF}\x{26D1}\x{26D3}\x{26E9}\x{26F0}-\x{26F5}\x{26F7}\x{26F8}\x{26FA}\x{2702}\x{2708}\x{2709}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2733}\x{2734}\x{2744}\x{2747}\x{2763}\x{27A1}\x{2934}\x{2935}\x{2B05}-\x{2B07}\x{2B1B}\x{2B1C}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F170}\x{1F171}\x{1F17E}\x{1F17F}\x{1F202}\x{1F237}\x{1F321}\x{1F324}-\x{1F32C}\x{1F336}\x{1F37D}\x{1F396}\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}\x{1F39F}\x{1F3CD}\x{1F3CE}\x{1F3D4}-\x{1F3DF}\x{1F3F5}\x{1F3F7}\x{1F43F}\x{1F4FD}\x{1F549}\x{1F54A}\x{1F56F}\x{1F570}\x{1F573}\x{1F576}-\x{1F579}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F5A5}\x{1F5A8}\x{1F5B1}\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}\x{1F6CB}\x{1F6CD}-\x{1F6CF}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6F0}\x{1F6F3}]|[\x{23E9}-\x{23EC}\x{23F0}\x{23F3}\x{25FD}\x{2693}\x{26A1}\x{26AB}\x{26C5}\x{26CE}\x{26D4}\x{26EA}\x{26FD}\x{2705}\x{2728}\x{274C}\x{274E}\x{2753}-\x{2755}\x{2757}\x{2795}-\x{2797}\x{27B0}\x{27BF}\x{2B50}\x{1F0CF}\x{1F18E}\x{1F191}-\x{1F19A}\x{1F201}\x{1F21A}\x{1F22F}\x{1F232}-\x{1F236}\x{1F238}-\x{1F23A}\x{1F250}\x{1F251}\x{1F300}-\x{1F320}\x{1F32D}-\x{1F335}\x{1F337}-\x{1F37C}\x{1F37E}-\x{1F384}\x{1F386}-\x{1F393}\x{1F3A0}-\x{1F3C1}\x{1F3C5}\x{1F3C6}\x{1F3C8}\x{1F3C9}\x{1F3CF}-\x{1F3D3}\x{1F3E0}-\x{1F3F0}\x{1F3F8}-\x{1F407}\x{1F409}-\x{1F414}\x{1F416}-\x{1F43A}\x{1F43C}-\x{1F43E}\x{1F440}\x{1F444}\x{1F445}\x{1F451}-\x{1F465}\x{1F46A}\x{1F479}-\x{1F47B}\x{1F47D}-\x{1F480}\x{1F484}\x{1F488}-\x{1F48E}\x{1F490}\x{1F492}-\x{1F4A9}\x{1F4AB}-\x{1F4FC}\x{1F4FF}-\x{1F53D}\x{1F54B}-\x{1F54E}\x{1F550}-\x{1F567}\x{1F5A4}\x{1F5FB}-\x{1F62D}\x{1F62F}-\x{1F634}\x{1F637}-\x{1F644}\x{1F648}-\x{1F64A}\x{1F680}-\x{1F6A2}\x{1F6A4}-\x{1F6B3}\x{1F6B7}-\x{1F6BF}\x{1F6C1}-\x{1F6C5}\x{1F6D0}-\x{1F6D2}\x{1F6D5}-\x{1F6D7}\x{1F6DD}-\x{1F6DF}\x{1F6EB}\x{1F6EC}\x{1F6F4}-\x{1F6FC}\x{1F7E0}-\x{1F7EB}\x{1F7F0}\x{1F90D}\x{1F90E}\x{1F910}-\x{1F917}\x{1F920}-\x{1F925}\x{1F927}-\x{1F92F}\x{1F93A}\x{1F93F}-\x{1F945}\x{1F947}-\x{1F976}\x{1F978}-\x{1F9B4}\x{1F9B7}\x{1F9BA}\x{1F9BC}-\x{1F9CC}\x{1F9D0}\x{1F9E0}-\x{1F9FF}\x{1FA70}-\x{1FA74}\x{1FA78}-\x{1FA7C}\x{1FA80}-\x{1FA86}\x{1FA90}-\x{1FAAC}\x{1FAB0}-\x{1FABA}\x{1FAC0}-\x{1FAC2}\x{1FAD0}-\x{1FAD9}\x{1FAE0}-\x{1FAE7}]/u', '', $text );

		// Define lines of text based on the text wrapping setting.
		$lines = ( $this->text_wrapping_overflow ? $this->wrap_text_with_overflow( $text ) : array( $text ) );

		// Calculate line height, in pixels.
		$line_height_pixels = $this->line_height * $this->text_size;

		// Calculate text height.
		$text_height = count( $lines ) * $line_height_pixels;

		// Determine text vertical alignment.
		switch ( $this->text_align['y'] ) {

			case 'center':
				$text_align_y = ( $this->box['height'] / 2 ) - ( $text_height / 2 );
				break;

			case 'bottom':
				$text_align_y = $this->box['height'] - $text_height;
				break;

			case 'top':
			default:
				$text_align_y = 0;
				break;

		}

		// Iterate through each line of text, adding it to the image.
		foreach ( $lines as $current_line => $line ) {

			// Get text bounding box.
			$box       = $this->calculate_box( $line );
			$box_width = $box[2] - $box[0];

			// Calculate horizontal alignment.
			switch ( $this->text_align['x'] ) {
				case 'center':
					$text_align_x = ( $this->box['width'] - $box_width ) / 2;
					break;
				case 'right':
					$text_align_x = ( $this->box['width'] - $box_width );
					break;
				case 'left':
				default:
					$text_align_x = 0;
					break;
			}

			// Define the current text line's X and Y position.
			$current_line_x_pos = $this->box['x'] + $text_align_x;
			$current_line_y_pos = $this->box['y'] + $text_align_y + ( $line_height_pixels * ( 1 - $this->baseline ) ) + ( $current_line * $line_height_pixels );

			// Apply Text Background Color.
			if ( $this->text_background_color ) {
				$this->draw_text_background(
					$current_line_x_pos,
					$this->box['y'] + $text_align_y + ( $current_line * $line_height_pixels ) + ( $line_height_pixels - $this->text_size ) + ( 1 - $this->line_height ) * 13 * ( 1 / 50 * $this->text_size ),
					$box_width,
					$this->text_size,
					$this->text_background_color
				);
			}

			// Draw Text.
			$this->draw_text( $current_line_x_pos, $current_line_y_pos, $line );

		}

	}

	/**
	 * Converts the given hex color to RGB
	 *
	 * @since   1.0.0
	 *
	 * @param   string $hex    Hex color (e.g. #000000).
	 * @return  mixed           array | WP_Error
	 */
	private function hex_to_rgba( $hex ) {

		$hex = str_replace( '#', '', $hex );

		if ( strlen( $hex ) === 6 ) {
			return array(
				'r' => hexdec( substr( $hex, 0, 2 ) ),
				'g' => hexdec( substr( $hex, 2, 2 ) ),
				'b' => hexdec( substr( $hex, 4, 2 ) ),
			);
		}

		if ( strlen( $hex ) === 3 ) {
			return array(
				'r' => hexdec( str_repeat( substr( $hex, 0, 1 ), 2 ) ),
				'g' => hexdec( str_repeat( substr( $hex, 1, 1 ), 2 ) ),
				'b' => hexdec( str_repeat( substr( $hex, 2, 1 ), 2 ) ),
			);
		}

		// If here, we couldn't convert the hex to RGBA.
		return new WP_Error(
			'social_post_flow_gd_text_hex_to_rgba_error',
			sprintf(
				/* translators: HEX Color */
				__( 'Could not convert hex color %s to RGBA', 'social-post-flow' ),
				$hex
			)
		);

	}

	/**
	 * Splits overflowing text into array of strings that won't overflow the text box.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $text Text.
	 * @return  array
	 */
	protected function wrap_text_with_overflow( $text ) {

		$lines = array();

		// Split text explicitly into lines by \n, \r\n and \r.
		$explicit_lines = preg_split( '/\n|\r\n?/', $text );

		// Iterate through each line, checking if it needs to be wrapped.
		foreach ( $explicit_lines as $line ) {

			$words = explode( ' ', $line );
			$line  = $words[0];
			$count = count( $words );

			for ( $i = 1; $i < $count; $i++ ) {
				$box = $this->calculate_box( $line . ' ' . $words[ $i ] );

				if ( ( $box[4] - $box[6] ) >= $this->box['width'] ) {
					$lines[] = $line;
					$line    = $words[ $i ];
				} else {
					$line .= ' ' . $words[ $i ];
				}
			}

			$lines[] = $line;

		}

		return $lines;

	}

	/**
	 * Gets the given RBGA color index from the image, if it already exists.
	 * If it doesn't exist it adds the color, returning the index.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $color  RBG(A) Color.
	 * @return  int             Index
	 */
	protected function get_color_index( $color ) {

		// Check if the given RBGa combination is already a palette in the image.
		if ( isset( $color['a'] ) ) {
			$index = imagecolorexactalpha( $this->im, $color['r'], $color['g'], $color['b'], $color['a'] );
		} else {
			$index = imagecolorexact( $this->im, $color['r'], $color['g'], $color['b'] );
		}

		// If a palette was founding matching the RBGA values, return its index.
		if ( $index !== -1 ) {
			return $index;
		}

		if ( isset( $color['a'] ) ) {
			return imagecolorallocatealpha( $this->im, $color['r'], $color['g'], $color['b'], $color['a'] );
		}

		return imagecolorallocate( $this->im, $color['r'], $color['g'], $color['b'] );

	}

	/**
	 * Returns the font size in points
	 *
	 * @since   1.0.0
	 *
	 * @return  float
	 */
	protected function get_text_size_in_points() {

		return 0.75 * $this->text_size;

	}

	/**
	 * Returns the bounding box of the given text, font size and font being used
	 *
	 * @since   1.0.0
	 *
	 * @param   string $text   Text.
	 * @return  array
	 */
	protected function calculate_box( $text ) {

		return imageftbbox( $this->get_text_size_in_points(), 0, $this->font_face, $text );

	}

	/**
	 * Draws a filled rectangle
	 *
	 * @since   1.0.0
	 *
	 * @param   int   $x      X Position.
	 * @param   int   $y      Y Position.
	 * @param   int   $width  Width.
	 * @param   int   $height Height.
	 * @param   mixed $color  rgba array or hex string.
	 */
	protected function draw_text_background( $x, $y, $width, $height, $color ) {

		imagefilledrectangle( $this->im, $x, $y, $x + $width, $y + $height, $this->get_color_index( $color ) );

	}

	/**
	 * Draw the text
	 *
	 * @since   1.0.0
	 *
	 * @param   int    $x      Horizontal Starting Point.
	 * @param   int    $y      Vertical Starting Point.
	 * @param   string $text   Text to Draw.
	 */
	protected function draw_text( $x, $y, $text ) {

		$this->draw_text_on_image(
			$x,
			$y,
			$this->text_color,
			$text
		);

	}

	/**
	 * Draw the text on the image
	 *
	 * @since   1.0.0
	 *
	 * @param   int    $x      Horizontal Starting Point.
	 * @param   int    $y      Vertical Starting Point.
	 * @param   array  $color  RGBA Color.
	 * @param   string $text   Text to Draw.
	 */
	protected function draw_text_on_image( $x, $y, $color, $text ) {

		imagefttext(
			$this->im,
			$this->get_text_size_in_points(),
			0, // no rotation.
			(int) $x,
			(int) $y,
			$this->get_color_index( $color ),
			$this->font_face,
			$text
		);

	}
}
