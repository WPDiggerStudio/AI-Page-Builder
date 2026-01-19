<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields;

use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\WP\Fields\Contracts\FieldInterface;

/**
 * AbstractField - Base class for all field types.
 *
 * Provides a consistent wrapper structure, labels, descriptions, and shared
 * methods for all fields. Inspired by CMB2's UX patterns.
 *
 * @package WPJarvis\Framework\WP\Fields
 */
abstract class AbstractField implements FieldInterface {
	/**
	 * CSS class prefix for all field elements.
	 */
	protected const CSS_PREFIX = 'wpj-field';

	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	abstract public function getType(): string;

	/**
	 * Render the actual input element.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 * @param mixed $value Current field value.
	 * @param string $context Rendering context.
	 *
	 * @return string The input HTML.
	 */
	abstract protected function renderInput( array $config, mixed $value, string $context ): string;

	/**
	 * Render the complete field with a wrapper structure.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 * @param mixed $value Current field value.
	 * @param string $context Rendering context (metabox, widget, shortcode, settings, block).
	 *
	 * @return string The rendered HTML output.
	 */
	public function render( array $config, mixed $value, string $context ): string {
		// Allow rendering just the input if requested
		if ( ! empty( $config['render_input_only'] ) ) {
			return $this->renderInput( $config, $value, $context );
		}

		$wrapperClasses = $this->getWrapperClasses( $config, $context );
		$wrapperAttrs   = $this->getWrapperAttributes( $config );

		$output = sprintf(
			'<div class="%s"%s>',
			esc_attr( implode( ' ', $wrapperClasses ) ),
			$wrapperAttrs
		);

		// Label row
		if ( ! empty( $config['label'] ) && $this->shouldRenderLabel( $config ) ) {
			$output .= $this->renderLabel( $config );
		}

		// Input row
		$output .= '<div class="' . self::CSS_PREFIX . '__input">';
		$output .= $this->renderBeforeInput( $config );
		$output .= $this->renderInput( $config, $value, $context );
		$output .= $this->renderAfterInput( $config );
		$output .= '</div>';
		// Description
		if ( ! empty( $config['description'] ) ) {
			$output .= $this->renderDescription( $config );
		}

		// Errors (if any)
		if ( ! empty( $config['errors'] ) ) {
			$output .= $this->renderErrors( $config['errors'] );
		}

		$output .= '</div>';

		/**
		 * Filter the complete field output.
		 *
		 * @param string $output The rendered field HTML.
		 * @param array $config Field configuration.
		 * @param mixed $value Current field value.
		 * @param string $context Rendering context.
		 * @param FieldInterface $field The field instance.
		 */
		return Hooks::applyFilters( 'field_output', $output, $config, $value, $context, $this );
	}

	/**
	 * Get wrapper CSS classes.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 * @param string $context Rendering context.
	 *
	 * @return array<string> CSS classes.
	 */
	protected function getWrapperClasses( array $config, string $context ): array {
		$classes = [
			self::CSS_PREFIX,
			self::CSS_PREFIX . '--' . $this->getType(),
			self::CSS_PREFIX . '--context-' . $context,
		];

		// Layout class
		$layout    = $config['layout'] ?? 'stacked';
		$classes[] = self::CSS_PREFIX . '--layout-' . $layout;

		// Required class
		if ( ! empty( $config['required'] ) ) {
			$classes[] = self::CSS_PREFIX . '--required';
		}

		// Error class
		if ( ! empty( $config['errors'] ) ) {
			$classes[] = self::CSS_PREFIX . '--has-errors';
		}

		// Disabled class
		if ( ! empty( $config['disabled'] ) || ! empty( $config['readonly'] ) ) {
			$classes[] = self::CSS_PREFIX . '--disabled';
		}

		// Size class (small, medium, large, full)
		if ( ! empty( $config['size'] ) ) {
			$classes[] = self::CSS_PREFIX . '--size-' . $config['size'];
		}

		// Custom classes from config
		if ( ! empty( $config['wrapper_class'] ) ) {
			$classes[] = $config['wrapper_class'];
		}

		/**
		 * Filter wrapper CSS classes.
		 *
		 * @param array $classes CSS classes.
		 * @param array $config Field configuration.
		 * @param string $context Rendering context.
		 * @param FieldInterface $field The field instance.
		 */
		return Hooks::applyFilters( 'field_wrapper_classes', $classes, $config, $context, $this );
	}

	/**
	 * Get wrapper attributes.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return array|string HTML attributes string.
	 */
	protected function getWrapperAttributes( array $config ): array|string {
		$attrs = [];

		// Data attributes for conditional logic
		if ( ! empty( $config['show_on'] ) ) {
			$attrs['data-show-on'] = wp_json_encode( $config['show_on'] );
		}

		if ( ! empty( $config['hide_on'] ) ) {
			$attrs['data-hide-on'] = wp_json_encode( $config['hide_on'] );
		}

		// Custom data attributes
		if ( ! empty( $config['data'] ) && is_array( $config['data'] ) ) {
			foreach ( $config['data'] as $key => $value ) {
				$attrs[ 'data-' . $key ] = is_array( $value ) ? wp_json_encode( $value ) : $value;
			}
		}

		$attrString = '';
		foreach ( $attrs as $key => $value ) {
			$attrString .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
		}

		return $attrString;
	}

	/**
	 * Render field label.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return string Label HTML.
	 */
	protected function renderLabel( array $config ): string {
		$id         = $config['id'] ?? '';
		$label      = $config['label'] ?? '';
		$required   = ! empty( $config['required'] );
		$tooltip    = $config['tooltip'] ?? '';
		$labelClass = $config['label_class'] ?? 'middle';

		$output = '<div class="' . $labelClass . ' ' . self::CSS_PREFIX . '__label">';
		$output .= sprintf( '<label for="%s">', esc_attr( $id ) );
		$output .= esc_html( $label );

		if ( $required ) {
			$output .= '<span class="' . self::CSS_PREFIX . '__required" aria-label="required">*</span>';
		}

		$output .= '</label>';

		// Tooltip
		if ( ! empty( $tooltip ) ) {
			$output .= sprintf(
				'<span class="%s__tooltip" title="%s">?</span>',
				self::CSS_PREFIX,
				esc_attr( $tooltip )
			);
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render field description.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return string Description HTML.
	 */
	protected function renderDescription( array $config ): string {
		$description = $config['description'] ?? '';
		$id          = $config['id'] ?? '';

		return sprintf(
			'<p class="%s__description" id="%s-description">%s</p>',
			self::CSS_PREFIX,
			esc_attr( $id ),
			wp_kses_post( $description )
		);
	}

	/**
	 * Render validation errors.
	 *
	 * @param array<string> $errors Error messages.
	 *
	 * @return string Errors HTML.
	 */
	protected function renderErrors( array $errors ): string {
		if ( empty( $errors ) ) {
			return '';
		}

		$output = '<div class="' . self::CSS_PREFIX . '__errors" role="alert">';
		foreach ( $errors as $error ) {
			$output .= sprintf(
				'<p class="%s__error">%s</p>',
				self::CSS_PREFIX,
				esc_html( $error )
			);
		}
		$output .= '</div>';

		return $output;
	}

	/**
	 * Render content before input.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return string HTML before input.
	 */
	protected function renderBeforeInput( array $config ): string {
		$output = '';

		if ( ! empty( $config['before'] ) ) {
			$output .= '<span class="' . self::CSS_PREFIX . '__before">' . wp_kses_post( $config['before'] ) . '</span>';
		}

		/**
		 * Action before field input.
		 *
		 * @param array $config Field configuration.
		 * @param FieldInterface $field The field instance.
		 */
		ob_start();
		Hooks::doAction( 'field_before_input', $config, $this );
		$output .= ob_get_clean();

		return $output;
	}

	/**
	 * Render content after input.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return string HTML after input.
	 */
	protected function renderAfterInput( array $config ): string {
		$output = '';

		/**
		 * Action after field input.
		 *
		 * @param array $config Field configuration.
		 * @param FieldInterface $field The field instance.
		 */
		ob_start();
		Hooks::doAction( 'field_after_input', $config, $this );
		$output .= ob_get_clean();

		if ( ! empty( $config['after'] ) ) {
			$output .= '<span class="' . self::CSS_PREFIX . '__after">' . wp_kses_post( $config['after'] ) . '</span>';
		}

		return $output;
	}

	/**
	 * Check if the label should be rendered.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return bool Whether to render a label.
	 */
	protected function shouldRenderLabel( array $config ): bool {
		return ! isset( $config['show_label'] ) || $config['show_label'] !== false;
	}

	/**
	 * Get common input attributes.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return array<string, string> Input attributes.
	 */
	protected function getInputAttributes( array $config ): array {
		$attrs = [
			'id'   => $config['id'] ?? '',
			'name' => $config['name'] ?? $config['id'] ?? '',
		];

		// Standard attributes
		if ( ! empty( $config['placeholder'] ) ) {
			$attrs['placeholder'] = $config['placeholder'];
		}

		if ( ! empty( $config['required'] ) ) {
			$attrs['required'] = 'required';
		}

		if ( ! empty( $config['disabled'] ) ) {
			$attrs['disabled'] = 'disabled';
		}

		if ( ! empty( $config['readonly'] ) ) {
			$attrs['readonly'] = 'readonly';
		}

		// Aria attributes
		if ( ! empty( $config['description'] ) ) {
			$attrs['aria-describedby'] = ( $config['id'] ?? '' ) . '-description';
		}

		// Custom attributes
		if ( ! empty( $config['attributes'] ) && is_array( $config['attributes'] ) ) {
			$attrs = array_merge( $attrs, $config['attributes'] );
		}

		return $attrs;
	}

	/**
	 * Build attributes string from an array.
	 *
	 * @param array<string, string> $attrs Attributes array.
	 *
	 * @return string HTML attributes string.
	 */
	protected function buildAttributesString( array $attrs ): string {
		$parts = [];
		foreach ( $attrs as $key => $value ) {
			if ( $value === true || $value === $key ) {
				$parts[] = esc_attr( $key );
			} elseif ( $value !== false && $value !== null ) {
				$parts[] = sprintf( '%s="%s"', esc_attr( $key ), esc_attr( (string) $value ) );
			}
		}

		return implode( ' ', $parts );
	}

	/**
	 * Sanitize a field value for storage.
	 *
	 * @param mixed $value Input value to sanitize.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return mixed Sanitized value safe for storage.
	 */
	public function sanitize( mixed $value, array $config ): mixed {
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Validate a field value.
	 *
	 * @param mixed $value Input value to validate.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return array{valid: bool, errors: array<string>} Validation result.
	 */
	public function validate( mixed $value, array $config ): array {
		$errors = [];

		// Check required
		if ( ! empty( $config['required'] ) && $this->isEmpty( $value ) ) {
			$errors['required'] = __( 'This field is required.', 'wp-jarvis' );
		}

		return [
			'valid'  => empty( $errors ),
			'errors' => $errors,
		];
	}

	/**
	 * Prepare a field value for storage.
	 *
	 * @param mixed $value Input value to prepare.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return mixed Prepared value for storage.
	 */
	public function prepare( mixed $value, array $config ): mixed {
		return $this->sanitize( $value, $config );
	}

	/**
	 * Get the field's default value.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return mixed The default value for this field.
	 */
	public function getDefault( array $config ): mixed {
		return $config['default'] ?? '';
	}

	/**
	 * Get the field's assets (scripts and styles).
	 *
	 * @return array{scripts: array, styles: array} Assets to enqueue.
	 */
	public function getAssets(): array {
		return [
			'scripts' => [],
			'styles'  => [],
		];
	}

	/**
	 * Check if a value is empty.
	 *
	 * @param mixed $value The value to check.
	 *
	 * @return bool Whether the value is empty.
	 */
	protected function isEmpty( mixed $value ): bool {
		if ( is_null( $value ) ) {
			return true;
		}

		if ( is_string( $value ) && trim( $value ) === '' ) {
			return true;
		}

		if ( is_array( $value ) && empty( $value ) ) {
			return true;
		}

		return false;
	}
}
