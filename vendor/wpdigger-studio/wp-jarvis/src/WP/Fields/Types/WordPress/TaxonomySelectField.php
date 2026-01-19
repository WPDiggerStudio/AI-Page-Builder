<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\WordPress;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * Taxonomy Select Field
 *
 * Dropdown select field for WordPress taxonomy terms.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\WordPress
 */
class TaxonomySelectField extends AbstractField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'taxonomy_select';
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
		$attrs       = $this->getInputAttributes( $config );
		$taxonomy    = $config['taxonomy'] ?? 'category';
		$multiple    = ! empty( $config['multiple'] );
		$placeholder = $config['placeholder'] ?? __( 'Select a term...', 'wp-jarvis' );

		// Handle multiple select
		if ( $multiple ) {
			$attrs['multiple'] = 'multiple';
			$attrs['name']     = ( $attrs['name'] ?? '' ) . '[]';
		}

		$attrs['class'] = 'wpj-taxonomy-select';

		// Get terms
		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => $config['hide_empty'] ?? false,
			'orderby'    => $config['orderby'] ?? 'name',
			'order'      => $config['order'] ?? 'ASC',
		] );

		if ( is_wp_error( $terms ) ) {
			$terms = [];
		}

		$values = $multiple ? (array) $value : [ $value ];

		$html = sprintf( '<select %s>', $this->buildAttributesString( $attrs ) );

		// Placeholder
		if ( ! $multiple ) {
			$html .= sprintf( '<option value="">%s</option>', esc_html( $placeholder ) );
		}

		foreach ( $terms as $term ) {
			$selected = in_array( (string) $term->term_id, array_map( 'strval', $values ), true ) ? ' selected' : '';
			$html     .= sprintf(
				'<option value="%d"%s>%s</option>',
				$term->term_id,
				$selected,
				esc_html( $term->name )
			);
		}

		$html .= '</select>';

		return $html;
	}

	/**
	 * Sanitize a field value for storage.
	 *
	 * @param mixed $value Input value to sanitize.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return int|array<int> Sanitized value.
	 */
	public function sanitize( mixed $value, array $config ): int|array {
		$multiple = ! empty( $config['multiple'] );

		if ( $multiple ) {
			$values = (array) $value;

			return array_map( 'absint', $values );
		}

		return absint( $value );
	}

	/**
	 * Get the field's default value.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return mixed The default value.
	 */
	public function getDefault( array $config ): mixed {
		$default  = $config['default'] ?? null;
		$multiple = ! empty( $config['multiple'] );

		if ( $multiple ) {
			return is_array( $default ) ? $default : [];
		}

		return $default ?? 0;
	}
}
