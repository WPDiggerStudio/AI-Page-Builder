<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\WordPress;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * TaxonomyRadioField - Taxonomy terms as radio buttons.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\WordPress
 */
class TaxonomyRadioField extends AbstractField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'taxonomy_radio';
	}

	/**
	 * Render the input element.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 * @param mixed $value Current field value.
	 * @param string $context Rendering context.
	 *
	 * @return string The input HTML.
	 */
	protected function renderInput( array $config, mixed $value, string $context ): string {
		$taxonomy = $config['taxonomy'] ?? 'category';
		$id       = $config['id'] ?? '';
		$name     = $config['name'] ?? $id;
		$inline   = ! empty( $config['inline'] );

		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => $config['hide_empty'] ?? false,
			'orderby'    => $config['orderby'] ?? 'name',
			'order'      => $config['order'] ?? 'ASC',
		] );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			$noTermsText = $config['text']['no_terms_text'] ?? __( 'No terms', 'wp-jarvis' );

			return '<p class="' . self::CSS_PREFIX . '__no-terms">' . esc_html( $noTermsText ) . '</p>';
		}

		$wrapperClass = self::CSS_PREFIX . '__options';
		if ( $inline ) {
			$wrapperClass .= ' ' . self::CSS_PREFIX . '__options--inline';
		}

		$html = '<div class="' . esc_attr( $wrapperClass ) . '">';

		// None option
		if ( ! empty( $config['show_option_none'] ) ) {
			$noneLabel = $config['show_option_none'] === true ? __( 'None', 'wp-jarvis' ) : $config['show_option_none'];
			$checked   = empty( $value ) ? 'checked' : '';

			$html .= '<div class="' . self::CSS_PREFIX . '__option">';
			$html .= sprintf(
				'<input type="radio" id="%s_none" name="%s" value="" %s />',
				esc_attr( $id ),
				esc_attr( $name ),
				$checked
			);
			$html .= sprintf( '<label for="%s_none">%s</label>', esc_attr( $id ), esc_html( $noneLabel ) );
			$html .= '</div>';
		}

		foreach ( $terms as $term ) {
			$termId  = $id . '_' . $term->term_id;
			$checked = ( (int) $value === $term->term_id ) ? 'checked' : '';

			$html .= '<div class="' . self::CSS_PREFIX . '__option">';
			$html .= sprintf(
				'<input type="radio" id="%s" name="%s" value="%d" %s />',
				esc_attr( $termId ),
				esc_attr( $name ),
				$term->term_id,
				$checked
			);
			$html .= sprintf( '<label for="%s">%s</label>', esc_attr( $termId ), esc_html( $term->name ) );
			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Sanitize a field value for storage.
	 *
	 * @param mixed $value Input value to sanitize.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return int Sanitized term ID.
	 */
	public function sanitize( mixed $value, array $config ): int {
		return absint( $value );
	}
}
