<?php
/**
 * Text to Image class using ImageMagick
 *
 * @package Social_Post_Flow
 * @author Social Post Flow
 */

/**
 * Creates images from text and an optional background image or color using ImageMagick.
 *
 * @package Social_Post_Flow
 * @author  Social Post Flow
 */
class Social_Post_Flow_Text_To_Image_Imagick {

	/**
	 * Holds the image created from Imagick
	 *
	 * @since   1.0.0
	 *
	 * @var     Imagick
	 */
	protected $im = null;

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
	 * @var     array
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
	 * @var     array
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
	 * @var     float
	 */
	protected $line_height = 1.25;

	/**
	 * Holds the text baseline alignment
	 *
	 * @since   1.0.0
	 *
	 * @var     float
	 */
	protected $baseline = 0.2;

	/**
	 * Holds the text font face
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	protected $font_face = null;

	/**
	 * Holds the text background color
	 *
	 * @since   1.0.0
	 *
	 * @var     array|false
	 */
	protected $text_background_color = false;

	/**
	 * Holds the text box dimensions and inner padding
	 *
	 * @since   1.0.0
	 *
	 * @var     array
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

		// Convert hex to rgba if needed.
		if ( ! is_array( $background_color ) ) {
			$background_color = $this->hex_to_rgba( $background_color );
		}

		$this->im = new Imagick();
		$this->im->newImage( $width, $height, new ImagickPixel( $this->rgba_to_string( $background_color ) ) );
		$this->im->setImageFormat( 'png' );

	}

	/**
	 * Load an existing image
	 *
	 * @since   1.0.0
	 *
	 * @param   int $attachment_id  Attachment ID.
	 * @return  mixed               WP_Error | array (width,height)
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

		// Load MIME type.
		$this->mime = get_post_mime_type( $attachment_id );
		if ( ! $this->mime ) {
			return new WP_Error( 'social_post_flow_load_attachment_missing', __( 'Could not determine MIME type of the background image.', 'social-post-flow' ) );
		}

		// Load image.
		try {
			$this->im = new Imagick( $image_path );
		} catch ( Exception $e ) {
			return new WP_Error( 'social_post_flow_load_attachment_error', $e->getMessage() );
		}

		// Return width and height of image.
		return array(
			$image[1],
			$image[2],
		);

	}

	/**
	 * Add centered text with given parameters
	 *
	 * @since   1.0.0
	 *
	 * @param   string $text                   Text.
	 * @param   string $font_face              Path and Filename to Font File.
	 * @param   int    $text_size              Font Size, in pixels.
	 * @param   mixed  $text_color             (string) HEX, (array) RGBA, (bool) false.
	 * @param   mixed  $text_background_color  (string) HEX, (array) RGBA, (bool) false.
	 * @param   int    $width                  Text Width.
	 * @param   int    $height                 Text Height.
	 * @param   int    $padding                Padding.
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
	 * Output the generated image
	 *
	 * @since   1.0.0
	 */
	public function output() {

		header( 'Content-Type: ' . $this->mime );
		echo $this->im->getImageBlob(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		die();

	}

	/**
	 * Save to temporary file
	 *
	 * @since   1.0.0
	 *
	 * @return  string  Image Destination Path and Filename
	 */
	public function save_tmp() {

		$destination = get_temp_dir() . 'social-post-flow-text-to-image-' . bin2hex( random_bytes( 5 ) );
		$this->im->writeImage( $destination );
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

		if ( ! is_array( $color ) ) {
			$color = $this->hex_to_rgba( $color );
		}

		$this->text_color = $color;

	}

	/**
	 * Sets the font face
	 *
	 * @since   1.0.0
	 *
	 * @param   string $path   Path and filename.
	 */
	public function set_font_face( $path ) {

		$this->font_face = $path;

	}

	/**
	 * Sets the font size
	 *
	 * @since   1.0.0
	 *
	 * @param   int $pixels     Font Size.
	 */
	public function set_text_size( $pixels ) {

		$this->text_size = $pixels;

	}

	/**
	 * Sets the text background color
	 *
	 * @since   1.0.0
	 *
	 * @param   mixed $color  Text Color (Hex string or RGBA array).
	 */
	public function set_text_background_color( $color ) {

		if ( ! $color ) {
			return;
		}

		if ( ! is_array( $color ) ) {
			$color = $this->hex_to_rgba( $color );
		}

		$this->text_background_color = $color;

	}

	/**
	 * Sets the line height
	 *
	 * @since   1.0.0
	 *
	 * @param   float $line_height  Line height multiplier.
	 */
	public function set_line_height( $line_height ) {

		$this->line_height = $line_height;

	}

	/**
	 * Sets the baseline
	 *
	 * @since   1.0.0
	 *
	 * @param   float $baseline  Baseline position.
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
	 * Sets the text box dimensions
	 *
	 * @since   1.0.0
	 *
	 * @param   int $x      X position.
	 * @param   int $y      Y position.
	 * @param   int $width  Width.
	 * @param   int $height Height.
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
	 * Draws the text on the image
	 *
	 * @since   1.0.0
	 *
	 * @param   string $text   Text to draw.
	 */
	public function draw( $text ) {

		// Bail if a font face wasn't defined.
		if ( ! isset( $this->font_face ) ) {
			return new WP_Error( 'social_post_flow_imagick_text_draw_missing_font_face', __( 'You must specify a font file.', 'social-post-flow' ) );
		}

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

		// Define font face and text size.
		$draw = new ImagickDraw();
		$draw->setFont( $this->font_face );
		$draw->setFontSize( $this->text_size );

		// Fetch canvas dimensions once so we can bounds-check each line below.
		// If the calculated text position falls outside the canvas, ImageMagick
		// throws "no pixels defined in cache" - we skip those lines to avoid
		// a fatal error when the text overflows the available space (e.g. a
		// very long status combined with a narrow background image and large
		// font size).
		try {
			$canvas_width  = $this->im->getImageWidth();
			$canvas_height = $this->im->getImageHeight();
		} catch ( Exception $e ) {
			return new WP_Error( 'social_post_flow_imagick_text_draw_canvas_error', $e->getMessage() );
		}

		foreach ( $lines as $current_line => $line ) {

			// Get text bounding box.
			$box       = $this->im->queryFontMetrics( $draw, $line );
			$box_width = $box['textWidth'];

			// Calculate horizontal alignment.
			switch ( $this->text_align['x'] ) {
				case 'center':
					$text_align_x = ( $this->box['width'] - $box_width ) / 2;
					break;
				case 'right':
					$text_align_x = ( $this->box['width'] - $box_width );
					break;
				default:
					$text_align_x = 0;
					break;
			}

			$current_line_x_pos = $this->box['x'] + $text_align_x;
			$current_line_y_pos = $this->box['y'] + $text_align_y + ( $line_height_pixels * ( 1 - $this->baseline ) ) + ( $current_line * $line_height_pixels );

			// Skip lines that fall outside the image canvas. Without this check,
			// Imagick's annotateImage() will throw a fatal "no pixels defined in
			// cache" exception when asked to draw at coordinates with no
			// underlying pixel data.
			if (
				$current_line_y_pos < 0
				|| $current_line_y_pos > $canvas_height
				|| $current_line_x_pos > $canvas_width
				|| ( $current_line_x_pos + $box_width ) < 0
			) {
				continue;
			}

			// Draw text background if specified.
			if ( $this->text_background_color ) {
				$rect = new ImagickDraw();
				$rect->setFillColor( $this->rgba_to_string( $this->text_background_color ) );
				$rect->rectangle(
					$current_line_x_pos,
					$this->box['y'] + $text_align_y + ( $current_line * $line_height_pixels ),
					$current_line_x_pos + $box_width,
					$this->box['y'] + $text_align_y + ( $current_line * $line_height_pixels ) + $this->text_size
				);
				$this->im->drawImage( $rect );
			}

			// Draw the text.
			// Wrapped in try/catch as a final safety net: if Imagick still
			// raises an exception for any reason (e.g. font / glyph issues),
			// fail gracefully with a WP_Error instead of a fatal error that
			// would prevent the post from publishing.
			$draw->setFillColor( $this->rgba_to_string( $this->text_color ) );
			try {
				$this->im->annotateImage(
					$draw,
					$current_line_x_pos,
					$current_line_y_pos,
					0,
					$line
				);
			} catch ( Exception $e ) {
				return new WP_Error( 'social_post_flow_imagick_text_draw_annotate_error', $e->getMessage() );
			}
		}

	}

	/**
	 * Converts hex color to RGBA array
	 *
	 * @since   1.0.0
	 *
	 * @param   string $hex    Hex color.
	 * @return  mixed          array|WP_Error
	 */
	private function hex_to_rgba( $hex ) {

		$hex = str_replace( '#', '', $hex );

		if ( strlen( $hex ) === 6 ) {
			return array(
				'r' => hexdec( substr( $hex, 0, 2 ) ),
				'g' => hexdec( substr( $hex, 2, 2 ) ),
				'b' => hexdec( substr( $hex, 4, 2 ) ),
				'a' => 0,
			);
		}

		if ( strlen( $hex ) === 3 ) {
			return array(
				'r' => hexdec( str_repeat( substr( $hex, 0, 1 ), 2 ) ),
				'g' => hexdec( str_repeat( substr( $hex, 1, 1 ), 2 ) ),
				'b' => hexdec( str_repeat( substr( $hex, 2, 1 ), 2 ) ),
				'a' => 0,
			);
		}

		return new WP_Error(
			'social_post_flow_imagick_text_hex_to_rgba_error',
			sprintf(
				/* translators: HEX Color */
				__( 'Could not convert hex color %s to RGBA', 'social-post-flow' ),
				$hex
			)
		);

	}

	/**
	 * Converts RGBA array to ImageMagick color string
	 *
	 * @since   1.0.0
	 *
	 * @param   array $color  RGBA color array.
	 * @return  string        ImageMagick color string
	 */
	private function rgba_to_string( $color ) {

		return sprintf(
			'rgba(%d, %d, %d, %f)',
			$color['r'],
			$color['g'],
			$color['b'],
			1 - ( $color['a'] ?? 0 ) / 127 // Convert PHP's 0-127 alpha to 0-1 range.
		);

	}

	/**
	 * Wraps text that would overflow
	 *
	 * @since   1.0.0
	 *
	 * @param   string $text   Text to wrap.
	 * @return  array          Array of lines
	 */
	protected function wrap_text_with_overflow( $text ) {

		$lines          = array();
		$explicit_lines = preg_split( '/\n|\r\n?/', $text );

		$draw = new ImagickDraw();
		$draw->setFont( $this->font_face );
		$draw->setFontSize( $this->text_size );

		foreach ( $explicit_lines as $line ) {
			$words        = explode( ' ', $line );
			$current_line = $words[0];
			$count        = count( $words );

			for ( $i = 1; $i < $count; $i++ ) {
				$test_line = $current_line . ' ' . $words[ $i ];
				$metrics   = $this->im->queryFontMetrics( $draw, $test_line );

				if ( $metrics['textWidth'] >= $this->box['width'] ) {
					$lines[]      = $current_line;
					$current_line = $words[ $i ];
				} else {
					$current_line = $test_line;
				}
			}

			$lines[] = $current_line;
		}

		return $lines;

	}

}
