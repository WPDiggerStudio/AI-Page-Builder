<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\Utility;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * Location Field
 *
 * Location/address input field with Google Maps integration.
 * Supports geocoding and current location detection.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\Utility
 */
class LocationField extends AbstractField {

	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'location';
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
		$id   = $config['id'] ?? '';
		$name = $config['name'] ?? $id;

		// Parse value
		$locationData = is_array( $value ) ? $value : [];
		$address      = $locationData['address'] ?? '';
		$lat          = $locationData['lat'] ?? '';
		$lng          = $locationData['lng'] ?? '';

		// Default zoom level
		$defaultZoom = $config['zoom'] ?? 15;
		$mapHeight   = $config['map_height'] ?? '300px';

		$html = '<div class="' . self::CSS_PREFIX . '__location-container" data-field-id="' . esc_attr( $id ) . '">';

		// Address input with the search icon and current location button
		$html .= '<div class="' . self::CSS_PREFIX . '__location-address-row">';
		$html .= '<div class="' . self::CSS_PREFIX . '__icon-wrapper" style="flex: 1;">';
		$html .= '<span class="' . self::CSS_PREFIX . '__icon ' . self::CSS_PREFIX . '__icon--left">';
		$html .= '<span class="dashicons dashicons-search"></span>';
		$html .= '</span>';
		$html .= sprintf(
			'<input type="text" id="%s_address" name="%s[address]" value="%s"
				class="regular-text wpj-location-address" placeholder="%s" autocomplete="off" />',
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $address ),
			esc_attr__( 'Search for address...', 'wp-jarvis' )
		);
		$html .= '</div>';

		// Current location button
		$html .= sprintf(
			'<button type="button" class="button wpj-location-current" data-field-id="%s" title="%s">
				<span class="dashicons dashicons-location"></span>
			</button>',
			esc_attr( $id ),
			esc_attr__( 'Get current location', 'wp-jarvis' )
		);
		$html .= '</div>';

		// Leaflet OpenStreetMap container
		$html .= sprintf(
			'<div class="' . self::CSS_PREFIX . '__location-map wpj-leaflet-map" id="%s_map"
				data-lat="%s" data-lng="%s" data-zoom="%d" style="height: %s;"></div>',
			esc_attr( $id ),
			esc_attr( $lat ),
			esc_attr( $lng ),
			(int) $defaultZoom,
			esc_attr( $mapHeight )
		);

		// Coordinates (hidden by default, can be shown)
		$html .= '<div class="' . self::CSS_PREFIX . '__location-coords">';
		$html .= sprintf(
			'<input type="text" id="%s_lat" name="%s[lat]" value="%s"
				class="small-text wpj-location-lat" placeholder="%s" readonly />',
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $lat ),
			esc_attr__( 'Latitude', 'wp-jarvis' )
		);
		$html .= sprintf(
			'<input type="text" id="%s_lng" name="%s[lng]" value="%s"
				class="small-text wpj-location-lng" placeholder="%s" readonly />',
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $lng ),
			esc_attr__( 'Longitude', 'wp-jarvis' )
		);
		$html .= '</div>';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Sanitize a field value for storage.
	 *
	 * @param mixed $value Input value to sanitize.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return array<string, mixed> Sanitized value.
	 */
	public function sanitize( mixed $value, array $config ): mixed {
		if ( ! is_array( $value ) ) {
			return [
				'address' => '',
				'lat'     => '',
				'lng'     => '',
			];
		}

		return [
			'address' => sanitize_text_field( $value['address'] ?? '' ),
			'lat'     => is_numeric( $value['lat'] ?? '' ) ? (float) $value['lat'] : '',
			'lng'     => is_numeric( $value['lng'] ?? '' ) ? (float) $value['lng'] : '',
		];
	}

	/**
	 * Get the field's default value.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return array<string, mixed> The default value.
	 */
	public function getDefault( array $config ): mixed {
		return $config['default'] ?? [
			'address' => '',
			'lat'     => '',
			'lng'     => '',
		];
	}
}
