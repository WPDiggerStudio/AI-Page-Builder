<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Types\Utility;

use WPJarvis\Framework\WP\Fields\AbstractField;

/**
 * Timezone Field
 *
 * Select field for timezone selection with grouped options.
 *
 * @package WPJarvis\Framework\WP\Fields\Types\Utility
 */
class TimezoneField extends AbstractField {

	/**
	 * Get the field type identifier.
	 *
	 * @return string The field type.
	 */
	public function getType(): string {
		return 'select_timezone';
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
		$placeholder = $config['placeholder'] ?? __( 'Select a timezone...', 'wp-jarvis' );

		// Add Select2 class
		$attrs['class']            = 'wpj-select2 wpj-timezone-select ' . ( $config['class'] ?? '' );
		$attrs['data-placeholder'] = $placeholder;

		$html = sprintf( '<select %s>', $this->buildAttributesString( $attrs ) );

		// Empty option for placeholder
		$html .= '<option value=""></option>';

		// Get a WordPress timezone list (grouped by continent)
		$timezones = $this->getTimezones();

		foreach ( $timezones as $group => $zones ) {
			$html .= sprintf( '<optgroup label="%s">', esc_attr( $group ) );

			foreach ( $zones as $zone_id => $zone_label ) {
				$selected = ( (string) $value === $zone_id ) ? ' selected' : '';
				$html     .= sprintf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $zone_id ),
					$selected,
					esc_html( $zone_label )
				);
			}

			$html .= '</optgroup>';
		}

		$html .= '</select>';

		// Show current time in selected timezone
		if ( ! empty( $value ) ) {
			try {
				$tz   = new \DateTimeZone( $value );
				$now  = new \DateTime( 'now', $tz );
				$html .= sprintf(
					'<p class="wpj-field__description wpj-timezone-preview">%s: %s</p>',
					esc_html__( 'Current time', 'wp-jarvis' ),
					esc_html( $now->format( 'Y-m-d H:i:s' ) )
				);
			} catch ( \Exception $e ) {
				// Invalid timezone, ignore
			}
		}

		return $html;
	}

	/**
	 * Sanitize a field value for storage.
	 *
	 * @param mixed $value Input value to sanitize.
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return string Sanitized value.
	 */
	public function sanitize( mixed $value, array $config ): mixed {
		$value = sanitize_text_field( (string) $value );

		// Validate timezone
		if ( ! empty( $value ) ) {
			try {
				new \DateTimeZone( $value );

				return $value;
			} catch ( \Exception $e ) {
				return '';
			}
		}

		return '';
	}

	/**
	 * Get a grouped list of timezones.
	 *
	 * @return array<string, array<string, string>> Grouped timezones.
	 */
	private function getTimezones(): array {
		$timezones = [];
		$regions   = [
			'Africa'     => \DateTimeZone::AFRICA,
			'America'    => \DateTimeZone::AMERICA,
			'Antarctica' => \DateTimeZone::ANTARCTICA,
			'Arctic'     => \DateTimeZone::ARCTIC,
			'Asia'       => \DateTimeZone::ASIA,
			'Atlantic'   => \DateTimeZone::ATLANTIC,
			'Australia'  => \DateTimeZone::AUSTRALIA,
			'Europe'     => \DateTimeZone::EUROPE,
			'Indian'     => \DateTimeZone::INDIAN,
			'Pacific'    => \DateTimeZone::PACIFIC,
		];

		// Add a UTC option first
		$timezones['UTC'] = [ 'UTC' => 'UTC (Coordinated Universal Time)' ];

		foreach ( $regions as $region => $mask ) {
			$zone_ids = \DateTimeZone::listIdentifiers( $mask );

			if ( empty( $zone_ids ) ) {
				continue;
			}

			$timezones[ $region ] = [];

			foreach ( $zone_ids as $zone_id ) {
				try {
					$tz     = new \DateTimeZone( $zone_id );
					$now    = new \DateTime( 'now', $tz );
					$offset = $tz->getOffset( $now );

					// Format offset
					$hours   = abs( intdiv( $offset, 3600 ) );
					$minutes = abs( ( $offset % 3600 ) / 60 );
					$sign    = $offset >= 0 ? '+' : '-';
					$gmt     = sprintf( 'GMT%s%02d:%02d', $sign, $hours, $minutes );

					// Format label (remove region prefix)
					$city = str_replace( [ '_', '/' ], [ ' ', ' - ' ], $zone_id );
					if ( str_starts_with( $city, $region . ' - ' ) ) {
						$city = substr( $city, strlen( $region ) + 3 );
					}

					$timezones[ $region ][ $zone_id ] = sprintf( '(%s) %s', $gmt, $city );
				} catch ( \Exception $e ) {
					$timezones[ $region ][ $zone_id ] = $zone_id;
				}
			}

			// Sort by label
			asort( $timezones[ $region ] );
		}

		return $timezones;
	}

	/**
	 * Get the field's default value.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return string The default value.
	 */
	public function getDefault( array $config ): mixed {
		// Default to WordPress timezone
		return $config['default'] ?? wp_timezone_string();
	}
}
