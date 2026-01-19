<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Fields\Security;

use WPJarvis\Framework\WP\Fields\Contracts\FieldValidatorInterface;

/**
 * Field Validator
 *
 * Provides validation, capability checking, nonce verification,
 * and output escaping for field data.
 *
 * @package WPJarvis\Framework\WP\Fields\Security
 */
class Validator implements FieldValidatorInterface {
	/**
	 * Validate a field value.
	 *
	 * @param mixed $value Input value to validate.
	 * @param array<string, mixed> $config Field configuration.
	 * @param string $context Validation context (metabox, widget, shortcode, settings, block).
	 *
	 * @return array{valid: bool, errors: array<string>} Validation result.
	 */
	public function validate( mixed $value, array $config, string $context ): array {
		$errors = [];

		// Check required fields
		if ( isset( $config['required'] ) && $config['required'] && empty( $value ) ) {
			$errors['required'] = __( 'This field is required.', 'wp-jarvis' );
		}

		// Check min/max length for text fields
		if ( isset( $config['min_length'] ) && is_string( $value ) && strlen( $value ) < $config['min_length'] ) {
			$errors['min_length'] = sprintf( __( 'Minimum length is %d.', 'wp-jarvis' ), $config['min_length'] );
		}

		if ( isset( $config['max_length'] ) && is_string( $value ) && strlen( $value ) > $config['max_length'] ) {
			$errors['max_length'] = sprintf( __( 'Maximum length is %d.', 'wp-jarvis' ), $config['max_length'] );
		}

		// Check numeric range
		if ( isset( $config['min'] ) && is_numeric( $value ) && $value < $config['min'] ) {
			$errors['min'] = sprintf( __( 'Minimum value is %s.', 'wp-jarvis' ), $config['min'] );
		}

		if ( isset( $config['max'] ) && is_numeric( $value ) && $value > $config['max'] ) {
			$errors['max'] = sprintf( __( 'Maximum value is %s.', 'wp-jarvis' ), $config['max'] );
		}

		// Check email format
		if ( isset( $config['type'] ) && $config['type'] === 'email' && ! empty( $value ) ) {
			if ( ! is_email( $value ) ) {
				$errors['email'] = __( 'Please enter a valid email address.', 'wp-jarvis' );
			}
		}

		// Check URL format
		if ( isset( $config['type'] ) && $config['type'] === 'url' && ! empty( $value ) ) {
			if ( ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
				$errors['url'] = __( 'Please enter a valid URL.', 'wp-jarvis' );
			}
		}

		// Check pattern matching
		if ( isset( $config['pattern'] ) && ! empty( $value ) ) {
			if ( ! preg_match( $config['pattern'], $value ) ) {
				$errors['pattern'] = __( 'This field does not match the required format.', 'wp-jarvis' );
			}
		}

		// Check allowed values
		if ( isset( $config['allowed'] ) && ! empty( $value ) ) {
			$allowed = is_array( $config['allowed'] ) ? $config['allowed'] : [ $config['allowed'] ];
			if ( ! in_array( $value, $allowed, true ) ) {
				$errors['allowed'] = sprintf( __( 'Please select one of the following: %s.', 'wp-jarvis' ), implode( ', ', $allowed ) );
			}
		}

		return [
			'valid'  => empty( $errors ),
			'errors' => $errors,
		];
	}

	/**
	 * Check if a user has required capability.
	 *
	 * @param string $capability Required capability (e.g., 'edit_posts').
	 * @param int|null $objectId Object ID for capability check.
	 *
	 * @return bool True if the user has capability.
	 */
	public function checkCapability( string $capability, ?int $objectId = null ): bool {
		if ( $objectId !== null ) {
			return user_can( $capability, $objectId );
		}

		return current_user_can( $capability );
	}

	/**
	 * Verify nonce for form submission.
	 *
	 * @param string $nonceAction Nonce action name.
	 * @param string $nonceField Nonce field name.
	 * @param string $nonceValue Nonce value to verify.
	 *
	 * @return bool True if nonce is valid.
	 */
	public function verifyNonce( string $nonceAction, string $nonceField, string $nonceValue ): bool {
		return wp_verify_nonce( $nonceValue, $nonceAction, $nonceField );
	}

	/**
	 * Escape output for safe display.
	 *
	 * @param mixed $value Value to escape.
	 * @param string $context Escaping context (html, attr, url, js, sql).
	 *
	 * @return string Escaped value.
	 */
	public function escape( mixed $value, string $context ): string {
		return match ( $context ) {
			'html' => wp_kses_post( (string) $value, $this->getAllowedHtml() ),
			'attr' => esc_attr( (string) $value ),
			'url' => esc_url_raw( (string) $value ),
			'js' => esc_js( (string) $value ),
			'sql' => esc_sql( (string) $value ),
			default => $value,
		};
	}

	/**
	 * Get allowed HTML tags for wp_kses_post.
	 *
	 * @return string Allowed HTML tags.
	 */
	private function getAllowedHtml(): string {
		return 'a,abbr,acronym,address,area,article,aside,audio,b,b,bdi,bdo,big,blockquote,br,canvas,caption,center,cite,cite,code,col,colgroup,data,datalist,dd,del,details,dfn,dialog,dir,div,dl,dt,em,embed,fieldset,figure,footer,form,h1,h2,h3,h4,h5,h6,header,hgroup,hr,i,iframe,img,input,ins,kbd,label,legend,li,map,mark,menu,menuitem,meter,noscript,object,ol,optgroup,output,p,param,picture,pre,progress,q,rp,rt,ruby,s,samp,script,section,select,small,source,span,strong,style,sub,sup,table,tbody,td,textarea,time,track,u,ul,var,video';
	}
}
