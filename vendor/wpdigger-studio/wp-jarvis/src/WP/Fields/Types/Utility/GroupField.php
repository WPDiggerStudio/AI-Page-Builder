<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\Utility;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * GroupField - Repeatable group of fields.
 *
 * Features proper repeater UI with add/remove functionality.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\Utility
 */
class GroupField extends AbstractField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'group';
	}

	/**
	 * Render the complete group field.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 * @param mixed $value Current field value.
	 * @param string $context Rendering context.
	 *
	 * @return string The rendered HTML output.
	 */
	public function render( array $config, mixed $value, string $context ): string {
		$fields     = $config['fields'] ?? [];
		$repeatable = ! empty( $config['repeatable'] );
		$groupTitle = $config['group_title'] ?? $config['title'] ?? '';
		$addText    = $config['add_text'] ?? __( '+ Add Item', 'wp-jarvis' );

		$wrapperClasses   = $this->getWrapperClasses( $config, $context );
		$wrapperClasses[] = self::CSS_PREFIX . '--group';

		$html = sprintf(
			'<div class="%s">',
			esc_attr( implode( ' ', $wrapperClasses ) )
		);

		// Label
		if ( ! empty( $config['label'] ) ) {
			$html .= $this->renderLabel( $config );
		}

		$html .= '<div class="' . self::CSS_PREFIX . '__input">';

		if ( $repeatable ) {
			$html .= $this->renderRepeater( $config, $value, $context, $fields, $groupTitle, $addText );
		} else {
			$html .= $this->renderGroupContainer( $config, $value, $context, $fields, $groupTitle );
		}

		$html .= '</div>';

		// Description
		if ( ! empty( $config['description'] ) ) {
			$html .= $this->renderDescription( $config );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render a single group container.
	 *
	 * @param array<string, mixed> $config Configuration.
	 * @param mixed $value Value.
	 * @param string $context Context.
	 * @param array<array<string, mixed>> $fields Field configs.
	 * @param string $title Group title.
	 *
	 * @return string HTML.
	 */
	protected function renderGroupContainer( array $config, mixed $value, string $context, array $fields, string $title ): string {
		$value = is_array( $value ) ? $value : [];
		$id    = $config['id'] ?? '';
		$name  = $config['name'] ?? $id;

		$html = '<div class="' . self::CSS_PREFIX . '__group-container">';

		if ( $title ) {
			$html .= '<div class="' . self::CSS_PREFIX . '__group-header">';
			$html .= '<h4 class="' . self::CSS_PREFIX . '__group-title">' . esc_html( $title ) . '</h4>';
			$html .= '</div>';
		}

		$html .= '<div class="' . self::CSS_PREFIX . '__group-content">';

		foreach ( $fields as $fieldConfig ) {
			$fieldId    = $fieldConfig['id'] ?? '';
			$fieldName  = $name . '[' . $fieldId . ']';
			$fieldValue = $value[ $fieldId ] ?? null;

			$fieldConfig['id']     = $id . '_' . $fieldId;
			$fieldConfig['name']   = $fieldName;
			$fieldConfig['layout'] = $fieldConfig['layout'] ?? 'inline';

			$field = $this->getFieldInstance( $fieldConfig['type'] ?? 'text' );
			if ( $field ) {
				$html .= $field->render( $fieldConfig, $fieldValue, $context );
			}
		}

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render repeater field.
	 *
	 * @param array<string, mixed> $config Configuration.
	 * @param mixed $value Value.
	 * @param string $context Context.
	 * @param array<array<string, mixed>> $fields Field configs.
	 * @param string $title Group title.
	 * @param string $addText Add button text.
	 *
	 * @return string HTML.
	 */
	protected function renderRepeater( array $config, mixed $value, string $context, array $fields, string $title, string $addText ): string {
		$values = is_array( $value ) ? array_values( $value ) : [];
		$id     = $config['id'] ?? '';
		$name   = $config['name'] ?? $id;

		$html = '<div class="' . self::CSS_PREFIX . '__repeater" data-field-id="' . esc_attr( $id ) . '">';

		if ( empty( $values ) ) {
			$html .= '<div class="' . self::CSS_PREFIX . '__repeater-empty">';
			$html .= esc_html__( 'No items yet. Click the button below to add one.', 'wp-jarvis' );
			$html .= '</div>';
		}

		// Existing items
		foreach ( $values as $index => $itemValue ) {
			$html .= $this->renderRepeaterItem( $id, $name, $context, $fields, $itemValue, $index, $title );
		}

		// Add button
		$html .= sprintf(
			'<button type="button" class="button %s__repeater-add" data-template="%s"><span class="dashicons dashicons-plus-alt2"></span> %s</button>',
			self::CSS_PREFIX,
			esc_attr( $id ),
			esc_html( $addText )
		);

		// Template for JS
		$html .= '<script type="text/template" id="' . esc_attr( $id ) . '-template">';
		$html .= $this->renderRepeaterItem( $id, $name, $context, $fields, [], '{{INDEX}}', $title );
		$html .= '</script>';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render a single repeater item.
	 *
	 * @param string $id Base ID.
	 * @param string $name Base name.
	 * @param string $context Context.
	 * @param array<array<string, mixed>> $fields Fields.
	 * @param mixed $itemValue Item value.
	 * @param int|string $index Index.
	 * @param string $title Item title.
	 *
	 * @return string HTML.
	 */
	protected function renderRepeaterItem( string $id, string $name, string $context, array $fields, mixed $itemValue, int|string $index, string $title ): string {
		$itemValue = is_array( $itemValue ) ? $itemValue : [];
		$isOpen    = ! is_int( $index ); // Template items start to open
		$openClass = $isOpen ? ' wpj-field__repeater-item--open' : '';

		$html = '<div class="' . self::CSS_PREFIX . '__repeater-item' . $openClass . '" data-index="' . esc_attr( (string) $index ) . '">';

		// Accordion header (clickable)
		$html .= '<div class="' . self::CSS_PREFIX . '__repeater-item-header">';
		$html .= '<span class="' . self::CSS_PREFIX . '__repeater-item-toggle dashicons dashicons-arrow-down"></span>';
		$html .= '<span class="' . self::CSS_PREFIX . '__repeater-item-title">';
		$html .= $title ? esc_html( $title . ' #' ) . '<span class="item-number">' . ( is_int( $index ) ? $index + 1 : '{{NUMBER}}' ) . '</span>' : esc_html__( 'Item', 'wp-jarvis' ) . ' #' . ( is_int( $index ) ? $index + 1 : '{{NUMBER}}' );
		$html .= '</span>';
		$html .= '<div class="' . self::CSS_PREFIX . '__repeater-item-actions">';
		$html .= '<button type="button" class="button button-link-delete ' . self::CSS_PREFIX . '__repeater-remove"><span class="dashicons dashicons-trash"></span> ' . esc_html__( 'Remove', 'wp-jarvis' ) . '</button>';
		$html .= '</div>';
		$html .= '</div>';

		// Accordion content (collapsible)
		$html .= '<div class="' . self::CSS_PREFIX . '__repeater-item-content">';

		// Fields
		foreach ( $fields as $fieldConfig ) {
			$fieldId    = $fieldConfig['id'] ?? '';
			$fieldName  = $name . '[' . $index . '][' . $fieldId . ']';
			$fieldValue = $itemValue[ $fieldId ] ?? null;

			$fieldConfig['id']     = $id . '_' . $index . '_' . $fieldId;
			$fieldConfig['name']   = $fieldName;
			$fieldConfig['layout'] = $fieldConfig['layout'] ?? 'inline';

			$field = $this->getFieldInstance( $fieldConfig['type'] ?? 'text' );
			if ( $field ) {
				$html .= $field->render( $fieldConfig, $fieldValue, $context );
			}
		}

		$html .= '</div>'; // content
		$html .= '</div>'; // item

		return $html;
	}

	/**
	 * Get a field instance by type.
	 *
	 * @param string $type Field type.
	 *
	 * @return AbstractField|null Field instance.
	 */
	protected function getFieldInstance( string $type ): ?AbstractField {
		if ( ! function_exists( 'wpj_app' ) ) {
			return null;
		}

		try {
			return wpj_app( 'field_registry' )->create( $type, [] );
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Render input (not used).
	 *
	 * @param array<string, mixed> $config Config.
	 * @param mixed $value Value.
	 * @param string $context Context.
	 *
	 * @return string Empty.
	 */
	protected function renderInput( array $config, mixed $value, string $context ): string {
		return '';
	}

	/**
	 * Sanitize field value.
	 *
	 * @param mixed $value Value.
	 * @param array<string, mixed> $config Config.
	 *
	 * @return array Sanitized.
	 */
	public function sanitize( mixed $value, array $config ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$fields     = $config['fields'] ?? [];
		$repeatable = ! empty( $config['repeatable'] );

		if ( $repeatable ) {
			$sanitized = [];
			foreach ( $value as $index => $itemValue ) {
				if ( is_array( $itemValue ) ) {
					$sanitized[] = $this->sanitizeItem( $itemValue, $fields );
				}
			}

			return $sanitized;
		}

		return $this->sanitizeItem( $value, $fields );
	}

	/**
	 * Sanitize single item.
	 *
	 * @param mixed $itemValue Item.
	 * @param array<array<string, mixed>> $fields Fields.
	 *
	 * @return array<string, mixed> Sanitized.
	 */
	protected function sanitizeItem( mixed $itemValue, array $fields ): array {
		if ( ! is_array( $itemValue ) ) {
			return [];
		}

		$sanitized = [];

		foreach ( $fields as $fieldConfig ) {
			$fieldId    = $fieldConfig['id'] ?? '';
			$fieldType  = $fieldConfig['type'] ?? 'text';
			$fieldValue = $itemValue[ $fieldId ] ?? null;

			$field = $this->getFieldInstance( $fieldType );
			if ( $field ) {
				$sanitized[ $fieldId ] = $field->sanitize( $fieldValue, $fieldConfig );
			} else {
				$sanitized[ $fieldId ] = sanitize_text_field( (string) $fieldValue );
			}
		}

		return $sanitized;
	}

	/**
	 * Get default value.
	 *
	 * @param array<string, mixed> $config Config.
	 *
	 * @return array Default.
	 */
	public function getDefault( array $config ): mixed {
		return $config['default'] ?? [];
	}
}
