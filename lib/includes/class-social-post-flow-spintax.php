<?php
/**
 * Spintax class
 *
 * @package Social_Post_Flow
 * @author WP Zinc
 */

/**
 * Performs spintax on text.
 *
 * @package Social_Post_Flow
 * @author  WP Zinc
 * @version 4.3.4
 */
class Social_Post_Flow_Spintax {

	/**
	 * Holds the base object.
	 *
	 * @since   4.3.4
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Holds an array of words and spintax replacements when using
	 * add_spintax()
	 *
	 * @since   4.3.4
	 *
	 * @var     array
	 */
	public $replacements = array();

	/**
	 * Constructor.
	 *
	 * @since   4.3.4
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct() {

		

	}

	/**
	 * Searches for spintax, replacing each spintax with one term
	 *
	 * @since   4.3.4
	 *
	 * @param   string $text   Text.
	 * @return  string          Text
	 */
	public function process( $text ) {

		// Use fastest method to process spintax.
		$spun_text = preg_replace_callback(
			'/\{(((?>[^\{\}]+)|(?R))*?)\}/x',
			array( $this, 'replace' ),
			$text
		);

		// If the method worked, we'll have a result - return it.
		if ( ! empty( $spun_text ) && ! is_null( $spun_text ) ) {
			return $spun_text;
		}

		// If here, the spintax is too long for PHP to process.
		// Fallback to a slower but more reliable method.
		while ( strpos( $text, '{' ) !== false && strpos( $text, '}' ) !== false && strpos( $text, '|' ) !== false ) {
			$text = preg_replace_callback(
				'/\{(((?>[^\{\}]+))*?)\}/x',
				array( $this, 'replace' ),
				$text
			);

			if ( is_null( $text ) ) {
				switch ( preg_last_error() ) {
					case PREG_NO_ERROR:
						return new WP_Error( 'wp_to_social_pro_spintax_process_no_error', __( 'Spintax Error: No Error', 'social-post-flow' ) );

					case PREG_INTERNAL_ERROR:
						return new WP_Error( 'wp_to_social_pro_spintax_process_internal_error', __( 'Spintax Error: Internal Error', 'social-post-flow' ) );

					case PREG_BACKTRACK_LIMIT_ERROR:
						return new WP_Error( 'wp_to_social_pro_spintax_process_backtrack_limit_error', __( 'Spintax Error: Backtrack Limit Hit', 'social-post-flow' ) );

					case PREG_RECURSION_LIMIT_ERROR:
						return new WP_Error( 'wp_to_social_pro_spintax_process_recursion_limit_error', __( 'Spintax Error: Recursion Limit Hit', 'social-post-flow' ) );

					case PREG_BAD_UTF8_ERROR:
						return new WP_Error( 'wp_to_social_pro_spintax_process_bad_utf8_error', __( 'Spintax Error: Bad UTF-8 encountered', 'social-post-flow' ) );

					case PREG_BAD_UTF8_OFFSET_ERROR:
						return new WP_Error( 'wp_to_social_pro_spintax_process_bad_utf8_offset_error', __( 'Spintax Error: Bad UTF-8 offset encountered', 'social-post-flow' ) );

					case PREG_JIT_STACKLIMIT_ERROR:
						return new WP_Error( 'wp_to_social_pro_spintax_process_jit_stack_limit_error', __( 'Spintax Error: JIT Stack Limit Hit', 'social-post-flow' ) );
				}
			}
		}

		return $text;

	}

	/**
	 * Replaces spintax with text
	 *
	 * @since   4.3.4
	 *
	 * @param   string $text   Text.
	 * @return  string          Text
	 */
	public function replace( $text ) {

		// Process.
		$processed_text = $this->process( $text[1] );

		// Bail if an error occured.
		if ( is_wp_error( $processed_text ) ) {
			return $processed_text;
		}

		// If no pipe delimiter exists, this isn't spintax.
		// It might be CSS or JSON, so we need to return the original string with the curly braces.
		if ( strpos( $processed_text, '|' ) === false ) {
			return '{' . $processed_text . '}';
		}

		// If a double pipe delimiter exists, this isn't spintax.
		// It might be JS, so we need to return the original string with the curly braces.
		if ( strpos( $processed_text, '||' ) !== false ) {
			return '{' . $processed_text . '}';
		}

		// Explode the spintax options and return a random array value.
		$parts = explode( '|', $processed_text );
		return $parts[ array_rand( $parts ) ];

	}

}
