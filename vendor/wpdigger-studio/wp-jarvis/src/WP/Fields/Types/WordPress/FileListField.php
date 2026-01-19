<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\WordPress;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * FileListField - Multiple file/image gallery field.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\WordPress
 */
class FileListField extends AbstractField {
	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'file_list';
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
		$id          = $config['id'] ?? '';
		$name        = $config['name'] ?? $id;
		$addText     = $config['text']['add_upload_files_text'] ?? __( 'Add or Upload Files', 'wp-jarvis' );
		$removeText  = $config['text']['remove_text'] ?? __( 'Remove', 'wp-jarvis' );
		$previewSize = $config['preview_size'] ?? [ 100, 100 ];

		$files = is_array( $value ) ? $value : [];

		$html = '<div class="' . self::CSS_PREFIX . '__file-list-container" data-field-id="' . esc_attr( $id ) . '">';

		// File list
		$html .= '<ul class="' . self::CSS_PREFIX . '__file-list" id="' . esc_attr( $id ) . '-list">';

		foreach ( $files as $attachmentId => $attachmentUrl ) {
			$html .= $this->renderFileItem( $attachmentId, $attachmentUrl, $name, $previewSize, $removeText );
		}

		$html .= '</ul>';

		// Add button
		$html .= sprintf(
			'<button type="button" class="button %s__file-list-add" data-target="%s"><span class="dashicons dashicons-plus-alt2"></span> %s</button>',
			self::CSS_PREFIX,
			esc_attr( $id ),
			esc_html( $addText )
		);

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render a single file item.
	 *
	 * @param int $attachmentId Attachment ID.
	 * @param string $attachmentUrl Attachment URL.
	 * @param string $name Field name.
	 * @param array<int> $previewSize Preview dimensions.
	 * @param string $removeText Remove button text.
	 *
	 * @return string HTML.
	 */
	protected function renderFileItem( int $attachmentId, string $attachmentUrl, string $name, array $previewSize, string $removeText ): string {
		$isImage  = wp_attachment_is_image( $attachmentId );
		$fileName = basename( get_attached_file( $attachmentId ) );

		$html = '<li class="' . self::CSS_PREFIX . '__file-list-item" data-id="' . esc_attr( (string) $attachmentId ) . '">';

		// Hidden input
		$html .= sprintf(
			'<input type="hidden" name="%s[%d]" value="%s" />',
			esc_attr( $name ),
			$attachmentId,
			esc_url( $attachmentUrl )
		);

		// Preview
		if ( $isImage ) {
			$html .= wp_get_attachment_image( $attachmentId, $previewSize, false, [ 'class' => self::CSS_PREFIX . '__file-list-image' ] );
		} else {
			$html .= '<span class="' . self::CSS_PREFIX . '__file-list-icon dashicons dashicons-media-default"></span>';
		}

		// File name
		$html .= '<span class="' . self::CSS_PREFIX . '__file-list-name">' . esc_html( $fileName ) . '</span>';

		// Remove button
		$html .= sprintf(
			'<button type="button" class="button button-link-delete %s__file-list-remove">%s</button>',
			self::CSS_PREFIX,
			esc_html( $removeText )
		);

		$html .= '</li>';

		return $html;
	}

	/**
	 * Sanitize a field value for storage.
	 *
	 * @param mixed $value Input value to sanitize.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return array<int, string> Sanitized attachment array.
	 */
	public function sanitize( mixed $value, array $config ): mixed {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$sanitized = [];
		foreach ( $value as $attachmentId => $url ) {
			$attachmentId = absint( $attachmentId );
			if ( $attachmentId && wp_get_attachment_url( $attachmentId ) ) {
				$sanitized[ $attachmentId ] = esc_url_raw( $url );
			}
		}

		return $sanitized;
	}

	/**
	 * Get the field's default value.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return array<int, string> The default value.
	 */
	public function getDefault( array $config ): mixed {
		return $config['default'] ?? [];
	}

	/**
	 * Get the field's assets.
	 *
	 * @return array{scripts: array, styles: array} Assets to enqueue.
	 */
	public function getAssets(): array {
		return [
			'scripts' => [ 'media-upload', 'thickbox' ],
			'styles'  => [ 'thickbox' ],
		];
	}
}
