<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\WordPress;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * Post Select Field
 *
 * Dropdown select field for WordPress posts.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\WordPress
 */
class PostSelectField extends AbstractField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'post_select';
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
		$postType    = $config['post_type'] ?? 'post';
		$multiple    = ! empty( $config['multiple'] );
		$placeholder = $config['placeholder'] ?? __( 'Select a post...', 'wp-jarvis' );

		// Handle multiple select
		if ( $multiple ) {
			$attrs['multiple'] = 'multiple';
			$attrs['name']     = ( $attrs['name'] ?? '' ) . '[]';
		}

		$attrs['class'] = 'wpj-post-select';

		// Get posts
		$posts = get_posts( [
			'post_type'      => $postType,
			'posts_per_page' => $config['limit'] ?? 100,
			'orderby'        => $config['orderby'] ?? 'title',
			'order'          => $config['order'] ?? 'ASC',
			'post_status'    => $config['post_status'] ?? 'publish',
		] );

		$values = $multiple ? (array) $value : [ $value ];

		$html = sprintf( '<select %s>', $this->buildAttributesString( $attrs ) );

		// Placeholder
		if ( ! $multiple ) {
			$html .= sprintf( '<option value="">%s</option>', esc_html( $placeholder ) );
		}

		foreach ( $posts as $post ) {
			$selected = in_array( (string) $post->ID, array_map( 'strval', $values ), true ) ? ' selected' : '';
			$html     .= sprintf(
				'<option value="%d"%s>%s</option>',
				$post->ID,
				$selected,
				esc_html( $post->post_title )
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
