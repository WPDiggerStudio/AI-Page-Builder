<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Validation;

use WPJarvis\Framework\Contracts\Validation\Validator as ValidatorContract;
use WPJarvis\Framework\Exceptions\Validation\ValidationException;

/**
 * Validator - Laravel-style validation for WordPress.
 */
class Validator implements ValidatorContract {
	/**
	 * The data under validation.
	 *
	 * @var array<string, mixed>
	 */
	private array $data;

	/**
	 * The validation rules.
	 *
	 * @var array<string, string|array>
	 */
	private array $rules;

	/**
	 * The error messages.
	 *
	 * @var array<string, array<string>>
	 */
	private array $errors = [];

	/**
	 * The failed rules.
	 *
	 * @var array<string, array<string>>
	 */
	private array $failedRules = [];

	/**
	 * Custom error messages.
	 *
	 * @var array<string, string>
	 */
	private array $customMessages;

	/**
	 * Custom attribute names.
	 *
	 * @var array<string, string>
	 */
	private array $customAttributes;

	/**
	 * After validation callbacks.
	 *
	 * @var array<callable>
	 */
	private array $afterCallbacks = [];

	/**
	 * Default error messages.
	 *
	 * @var array<string, string>
	 */
	private array $defaultMessages = [];

	/**
	 * Create a new validator instance.
	 *
	 * @param array<string, mixed> $data
	 * @param array<string, string|array> $rules
	 * @param array<string, string> $messages
	 * @param array<string, string> $attributes
	 */
	public function __construct(
		array $data,
		array $rules,
		array $messages = [],
		array $attributes = []
	) {
		$this->data             = $data;
		$this->rules            = $rules;
		$this->customMessages   = $messages;
		$this->customAttributes = $attributes;
		$this->loadDefaultMessages();
	}

	/**
	 * Load the default localized messages.
	 */
	protected function loadDefaultMessages(): void {
		$this->defaultMessages = [
			'required'        => __( 'The :attribute field is required.', 'wp-jarvis' ),
			'email'           => __( 'The :attribute must be a valid email address.', 'wp-jarvis' ),
			'url'             => __( 'The :attribute must be a valid URL.', 'wp-jarvis' ),
			'numeric'         => __( 'The :attribute must be a number.', 'wp-jarvis' ),
			'integer'         => __( 'The :attribute must be an integer.', 'wp-jarvis' ),
			'string'          => __( 'The :attribute must be a string.', 'wp-jarvis' ),
			'array'           => __( 'The :attribute must be an array.', 'wp-jarvis' ),
			'boolean'         => __( 'The :attribute must be true or false.', 'wp-jarvis' ),
			'min'             => __( 'The :attribute must be at least :min.', 'wp-jarvis' ),
			'max'             => __( 'The :attribute must not be greater than :max.', 'wp-jarvis' ),
			'between'         => __( 'The :attribute must be between :min and :max.', 'wp-jarvis' ),
			'in'              => __( 'The selected :attribute is invalid.', 'wp-jarvis' ),
			'not_in'          => __( 'The selected :attribute is invalid.', 'wp-jarvis' ),
			'confirmed'       => __( 'The :attribute confirmation does not match.', 'wp-jarvis' ),
			'same'            => __( 'The :attribute and :other must match.', 'wp-jarvis' ),
			'different'       => __( 'The :attribute and :other must be different.', 'wp-jarvis' ),
			'regex'           => __( 'The :attribute format is invalid.', 'wp-jarvis' ),
			'date'            => __( 'The :attribute is not a valid date.', 'wp-jarvis' ),
			'alpha'           => __( 'The :attribute must only contain letters.', 'wp-jarvis' ),
			'alpha_num'       => __( 'The :attribute must only contain letters and numbers.', 'wp-jarvis' ),
			'alpha_dash'      => __( 'The :attribute must only contain letters, numbers, dashes and underscores.', 'wp-jarvis' ),
			'size'            => __( 'The :attribute must be :min.', 'wp-jarvis' ),
			'file'            => __( 'The :attribute must be a file.', 'wp-jarvis' ),
			'image'           => __( 'The :attribute must be an image.', 'wp-jarvis' ),
			'mimes'           => __( 'The :attribute must be a file of type: :min.', 'wp-jarvis' ),
			'exists'          => __( 'The selected :attribute is invalid.', 'wp-jarvis' ),
			'unique'          => __( 'The :attribute has already been taken.', 'wp-jarvis' ),
			'required_if'     => __( 'The :attribute field is required.', 'wp-jarvis' ),
			'required_unless' => __( 'The :attribute field is required.', 'wp-jarvis' ),
			'required_with'   => __( 'The :attribute field is required.', 'wp-jarvis' ),
			'ip'              => __( 'The :attribute must be a valid IP address.', 'wp-jarvis' ),
			'json'            => __( 'The :attribute must be a valid JSON string.', 'wp-jarvis' ),
		];
	}

	/**
	 * Create a new validator instance.
	 *
	 * @param array<string, mixed> $data
	 * @param array<string, string|array> $rules
	 * @param array<string, string> $messages
	 *
	 * @return static
	 */
	public static function make(
		array $data,
		array $rules,
		array $messages = []
	): static {
		return new static( $data, $rules, $messages );
	}

	/**
	 * Run the validator's rules against its data.
	 *
	 * @return array<string, mixed>
	 * @throws ValidationException
	 */
	final public function validate(): array {
		if ( $this->fails() ) {
			throw ValidationException::withErrors( $this->errors );
		}

		return $this->validated();
	}

	/**
	 * Determine if the data fails the validation rules.
	 */
	public function fails(): bool {
		return ! $this->passes();
	}

	/**
	 * Determine if the data passes the validation rules.
	 */
	public function passes(): bool {
		$this->errors      = [];
		$this->failedRules = [];

		foreach ( $this->rules as $attribute => $rules ) {
			$this->validateAttribute( $attribute, $this->parseRules( $rules ) );
		}

		// Run after callbacks
		foreach ( $this->afterCallbacks as $callback ) {
			$callback( $this );
		}

		return empty( $this->errors );
	}

	/**
	 * Parse rules into an array.
	 *
	 * @param string|array $rules
	 *
	 * @return array<string>
	 */
	protected function parseRules( string|array $rules ): array {
		if ( is_array( $rules ) ) {
			return $rules;
		}

		return explode( '|', $rules );
	}

	/**
	 * Validate a single attribute.
	 *
	 * @param string $attribute
	 * @param array<string> $rules
	 */
	protected function validateAttribute( string $attribute, array $rules ): void {
		$value = $this->getValue( $attribute );

		foreach ( $rules as $rule ) {
			[ $ruleName, $parameters ] = $this->parseRule( $rule );

			if ( $ruleName === '' ) {
				continue;
			}

			$method = 'validate' . str_replace( '_', '', ucwords( $ruleName, '_' ) );

			if ( method_exists( $this, $method ) && ! $this->$method( $attribute, $value, $parameters ) ) {
				$this->addError( $attribute, $ruleName, $parameters );
				$this->failedRules[ $attribute ][] = $ruleName;
			}
		}
	}

	/**
	 * Parse a rule and its parameters.
	 *
	 * @param string $rule
	 *
	 * @return array{0: string, 1: array}
	 */
	protected function parseRule( string $rule ): array {
		if ( str_contains( $rule, ':' ) ) {
			[ $ruleName, $paramString ] = explode( ':', $rule, 2 );
			$parameters = explode( ',', $paramString );
		} else {
			$ruleName   = $rule;
			$parameters = [];
		}

		return [ trim( $ruleName ), $parameters ];
	}

	/**
	 * Get a value from the data.
	 *
	 * @param string $attribute
	 *
	 * @return mixed
	 */
	protected function getValue( string $attribute ): mixed {
		return $this->data[ $attribute ] ?? null;
	}

	/**
	 * Add an error message.
	 *
	 * @param string $attribute
	 * @param string $rule
	 * @param array $parameters
	 */
	protected function addError( string $attribute, string $rule, array $parameters = [] ): void {
		$message = $this->getMessage( $attribute, $rule );
		$message = $this->replaceParameters( $message, $attribute, $rule, $parameters );

		$this->errors[ $attribute ][] = $message;
	}

	/**
	 * Get the error message for a rule.
	 *
	 * @param string $attribute
	 * @param string $rule
	 *
	 * @return string
	 */
	protected function getMessage( string $attribute, string $rule ): string {
		// Check for a custom message
		if ( isset( $this->customMessages["{$attribute}.{$rule}"] ) ) {
			return $this->customMessages["{$attribute}.{$rule}"];
		}

		if ( isset( $this->customMessages[ $rule ] ) ) {
			return $this->customMessages[ $rule ];
		}

		// FIX: Use :attribute placeholder and localize
		return $this->defaultMessages[ $rule ] ?? __( 'The :attribute field is invalid.', 'wp-jarvis' );
	}

	/**
	 * Replace message parameters.
	 *
	 * @param string $message
	 * @param string $attribute
	 * @param string $rule
	 * @param array $parameters
	 *
	 * @return string
	 */
	protected function replaceParameters(
		string $message,
		string $attribute,
		string $rule,
		array $parameters
	): string {
		$attributeName = $this->customAttributes[ $attribute ] ?? str_replace( '_', ' ', $attribute );

		$message = str_replace( ':attribute', $attributeName, $message );

		if ( isset( $parameters[0] ) ) {
			$message = str_replace( array( ':min', ':max', ':other' ), array( $parameters[0], $parameters[0], $parameters[0] ), $message );
		}

		if ( isset( $parameters[1] ) ) {
			$message = str_replace( ':max', $parameters[1], $message );
		}

		return $message;
	}

	/**
	 * Get the validation errors.
	 *
	 * @return array<string, array<string>>
	 */
	public function errors(): array {
		return $this->errors;
	}

	/**
	 * Get the failed rules.
	 *
	 * @return array<string, array<string>>
	 */
	public function failed(): array {
		return $this->failedRules;
	}

	/**
	 * Get the validated data.
	 *
	 * @return array<string, mixed>
	 */
	public function validated(): array {
		$validated = [];

		foreach ( array_keys( $this->rules ) as $attribute ) {
			if ( array_key_exists( $attribute, $this->data ) ) {
				$validated[ $attribute ] = $this->data[ $attribute ];
			}
		}

		return $validated;
	}

	/**
	 * After validation callback.
	 *
	 * @param callable $callback
	 *
	 * @return static
	 */
	public function after( callable $callback ): static {
		$this->afterCallbacks[] = $callback;

		return $this;
	}

	/**
	 * Set custom error messages.
	 *
	 * @param array<string, string> $messages
	 *
	 * @return static
	 */
	public function setCustomMessages( array $messages ): static {
		$this->customMessages = array_merge( $this->customMessages, $messages );

		return $this;
	}

	/**
	 * Set custom attribute names.
	 *
	 * @param array<string, string> $attributes
	 *
	 * @return static
	 */
	public function setAttributeNames( array $attributes ): static {
		$this->customAttributes = array_merge( $this->customAttributes, $attributes );

		return $this;
	}

	// -------------------------------------------------------------------------
	// Validation Rules
	// -------------------------------------------------------------------------

	/**
	 * Validate that the attribute is required.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters.
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateRequired( string $attribute, mixed $value, array $parameters ): bool {
		if ( is_null( $value ) ) {
			return false;
		}

		if ( is_string( $value ) && trim( $value ) === '' ) {
			return false;
		}

		if ( is_array( $value ) && count( $value ) === 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate that the attribute is a valid email.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters.
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateEmail( string $attribute, mixed $value, array $parameters ): bool {
		if ( empty( $value ) ) {
			return true;
		}

		return filter_var( $value, FILTER_VALIDATE_EMAIL ) !== false;
	}

	/**
	 * Validate that the attribute is a valid URL.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters.
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateUrl( string $attribute, mixed $value, array $parameters ): bool {
		if ( empty( $value ) ) {
			return true;
		}

		return filter_var( $value, FILTER_VALIDATE_URL ) !== false;
	}

	/**
	 * Validate that the attribute is numeric.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters.
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateNumeric( string $attribute, mixed $value, array $parameters ): bool {
		if ( is_null( $value ) ) {
			return true;
		}

		return is_numeric( $value );
	}

	/**
	 * Validate that the attribute is an integer.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters.
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateInteger( string $attribute, mixed $value, array $parameters ): bool {
		if ( is_null( $value ) ) {
			return true;
		}

		return filter_var( $value, FILTER_VALIDATE_INT ) !== false;
	}

	/**
	 * Validate that the attribute is a string.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters.
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateString( string $attribute, mixed $value, array $parameters ): bool {
		if ( is_null( $value ) ) {
			return true;
		}

		return is_string( $value );
	}

	/**
	 * Validate that the attribute is an array.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters.
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateArray( string $attribute, mixed $value, array $parameters ): bool {
		if ( is_null( $value ) ) {
			return true;
		}

		return is_array( $value );
	}

	/**
	 * Validate that the attribute is a boolean.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters.
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateBoolean( string $attribute, mixed $value, array $parameters ): bool {
		if ( is_null( $value ) ) {
			return true;
		}

		return in_array( $value, [ true, false, 0, 1, '0', '1' ], true );
	}

	/**
	 * Validate that the attribute meets the minimum value.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters (min value).
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateMin( string $attribute, mixed $value, array $parameters ): bool {
		if ( is_null( $value ) ) {
			return true;
		}

		$min = (int) ( $parameters[0] ?? 0 );

		if ( is_numeric( $value ) ) {
			return $value >= $min;
		}

		if ( is_string( $value ) ) {
			return mb_strlen( $value ) >= $min;
		}

		if ( is_array( $value ) ) {
			return count( $value ) >= $min;
		}

		return false;
	}

	/**
	 * Validate that the attribute does not exceed the maximum value.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters (max value).
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateMax( string $attribute, mixed $value, array $parameters ): bool {
		if ( is_null( $value ) ) {
			return true;
		}

		$max = (int) ( $parameters[0] ?? 0 );

		if ( is_numeric( $value ) ) {
			return $value <= $max;
		}

		if ( is_string( $value ) ) {
			return mb_strlen( $value ) <= $max;
		}

		if ( is_array( $value ) ) {
			return count( $value ) <= $max;
		}

		return false;
	}

	/**
	 * Validate that the attribute is between min and max values.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters (min, max values).
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateBetween( string $attribute, mixed $value, array $parameters ): bool {
		if ( is_null( $value ) ) {
			return true;
		}

		$min = (int) ( $parameters[0] ?? 0 );
		$max = (int) ( $parameters[1] ?? 0 );

		if ( is_numeric( $value ) ) {
			return $value >= $min && $value <= $max;
		}

		if ( is_string( $value ) ) {
			$length = mb_strlen( $value );

			return $length >= $min && $length <= $max;
		}

		return false;
	}

	/**
	 * Validate that the attribute is in the list of allowed values.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters (allowed values).
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateIn( string $attribute, mixed $value, array $parameters ): bool {
		if ( is_null( $value ) ) {
			return true;
		}

		return in_array( $value, $parameters, true );
	}

	/**
	 * Validate that the attribute is not in the list of values.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters (forbidden values).
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateNotIn( string $attribute, mixed $value, array $parameters ): bool {
		return ! $this->validateIn( $attribute, $value, $parameters );
	}

	/**
	 * Validate that the attribute has a matching confirmation field.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters.
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateConfirmed( string $attribute, mixed $value, array $parameters ): bool {
		$confirmation = $this->getValue( $attribute . '_confirmation' );

		return $value === $confirmation;
	}

	/**
	 * Validate that the attribute matches another field.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters (another field name).
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateSame( string $attribute, mixed $value, array $parameters ): bool {
		$other = $this->getValue( $parameters[0] ?? '' );

		return $value === $other;
	}

	/**
	 * Validate that the attribute is different from another field.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters (another field name).
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateDifferent( string $attribute, mixed $value, array $parameters ): bool {
		$other = $this->getValue( $parameters[0] ?? '' );

		return $value !== $other;
	}

	/**
	 * Validate that the attribute matches a regex pattern.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters (regex pattern).
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateRegex( string $attribute, mixed $value, array $parameters ): bool {
		if ( is_null( $value ) ) {
			return true;
		}

		$pattern = $parameters[0] ?? '';

		return preg_match( $pattern, $value ) > 0;
	}

	/**
	 * Validate that the attribute is a valid date.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters.
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateDate( string $attribute, mixed $value, array $parameters ): bool {
		if ( empty( $value ) ) {
			return true;
		}

		return strtotime( $value ) !== false;
	}

	/**
	 * Validate that the attribute contains only letters.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters.
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateAlpha( string $attribute, mixed $value, array $parameters ): bool {
		if ( is_null( $value ) ) {
			return true;
		}

		return preg_match( '/^[\pL\pM]+$/u', $value ) > 0;
	}

	/**
	 * Validate that the attribute contains only letters and numbers.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters.
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateAlphaNum( string $attribute, mixed $value, array $parameters ): bool {
		if ( is_null( $value ) ) {
			return true;
		}

		return preg_match( '/^[\pL\pM\pN]+$/u', $value ) > 0;
	}

	/**
	 * Validate that the attribute contains only letters, numbers, dashes, and underscores.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters.
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateAlphaDash( string $attribute, mixed $value, array $parameters ): bool {
		if ( is_null( $value ) ) {
			return true;
		}

		return preg_match( '/^[\pL\pM\pN_-]+$/u', $value ) > 0;
	}

	/**
	 * Validate that the attribute can be null (always passes).
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters.
	 *
	 * @return bool Always returns true.
	 */
	protected function validateNullable( string $attribute, mixed $value, array $parameters ): bool {
		return true;
	}

	/**
	 * Validate that the attribute has a specific size.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters (size).
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateSize( string $attribute, mixed $value, array $parameters ): bool {
		if ( is_null( $value ) ) {
			return true;
		}

		$size = (int) ( $parameters[0] ?? 0 );

		if ( is_numeric( $value ) ) {
			return (int) $value === $size;
		}

		if ( is_string( $value ) ) {
			return mb_strlen( $value ) === $size;
		}

		if ( is_array( $value ) ) {
			return count( $value ) === $size;
		}

		return false;
	}

	/**
	 * Validate that the attribute is a file.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters.
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateFile( string $attribute, mixed $value, array $parameters ): bool {
		if ( is_null( $value ) ) {
			return true;
		}

		// Check if it's an uploaded file array
		if ( is_array( $value ) && isset( $value['tmp_name'] ) ) {
			return is_uploaded_file( $value['tmp_name'] );
		}

		return false;
	}

	/**
	 * Validate that the attribute is an image file.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters.
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateImage( string $attribute, mixed $value, array $parameters ): bool {
		if ( ! $this->validateFile( $attribute, $value, $parameters ) ) {
			return false;
		}

		if ( is_array( $value ) && isset( $value['type'] ) ) {
			return str_starts_with( $value['type'], 'image/' );
		}

		return false;
	}

	/**
	 * Validate that the attribute has an allowed MIME type.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters (allowed extensions).
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateMimes( string $attribute, mixed $value, array $parameters ): bool {
		if ( is_null( $value ) || empty( $parameters ) ) {
			return true;
		}

		if ( is_array( $value ) && isset( $value['name'] ) ) {
			$extension = strtolower( pathinfo( $value['name'], PATHINFO_EXTENSION ) );

			return in_array( $extension, $parameters, true );
		}

		return false;
	}

	/**
	 * Validate that the attribute value exists in a database table.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters (table, column).
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateExists( string $attribute, mixed $value, array $parameters ): bool {
		if ( is_null( $value ) || empty( $parameters[0] ) ) {
			return true;
		}

		global $wpdb;

		$table  = $wpdb->prefix . $parameters[0];
		$column = $parameters[1] ?? $attribute;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE {$column} = %s",
				$value
			)
		);

		return (int) $result > 0;
	}

	/**
	 * Validate that the attribute value is unique in a database table.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters (table, column, ignoreId, ignoreColumn).
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateUnique( string $attribute, mixed $value, array $parameters ): bool {
		if ( is_null( $value ) || empty( $parameters[0] ) ) {
			return true;
		}

		global $wpdb;

		$table        = $wpdb->prefix . $parameters[0];
		$column       = $parameters[1] ?? $attribute;
		$ignoreId     = $parameters[2] ?? null;
		$ignoreColumn = $parameters[3] ?? 'id';

		$sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = %s";

		if ( $ignoreId ) {
			$sql .= $wpdb->prepare( " AND {$ignoreColumn} != %s", $ignoreId );
		}

		$result = $wpdb->get_var( $wpdb->prepare( $sql, $value ) );

		return (int) $result === 0;
	}

	/**
	 * Validate that the attribute is required if another attribute is present.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters (another field).
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateRequiredIf( string $attribute, mixed $value, array $parameters ): bool {
		$otherField = $parameters[0] ?? '';
		$otherValue = $parameters[1] ?? null;

		$actualOtherValue = $this->getValue( $otherField );

		if ( $actualOtherValue === $otherValue || $otherValue === null ) {
			return $this->validateRequired( $attribute, $value, [] );
		}

		return true;
	}

	/**
	 * Validate that the attribute is required unless another field has a value.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters (another field, value).
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateRequiredUnless( string $attribute, mixed $value, array $parameters ): bool {
		$otherField = $parameters[0] ?? '';
		$otherValue = $parameters[1] ?? null;

		$actualOtherValue = $this->getValue( $otherField );

		if ( $actualOtherValue !== $otherValue ) {
			return $this->validateRequired( $attribute, $value, [] );
		}

		return true;
	}

	/**
	 * Validate that the attribute is required when another attribute exists.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters (other fields).
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateRequiredWith( string $attribute, mixed $value, array $parameters ): bool {
		foreach ( $parameters as $field ) {
			$otherValue = $this->getValue( $field );
			if ( $this->validateRequired( $field, $otherValue, [] ) ) {
				return $this->validateRequired( $attribute, $value, [] );
			}
		}

		return true;
	}

	/**
	 * Validate that the attribute is a valid IP address.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters.
	 *
	 * @return bool True if validation passes, false otherwise.
	 */
	protected function validateIp( string $attribute, mixed $value, array $parameters ): bool {
		if ( empty( $value ) ) {
			return true;
		}

		return filter_var( $value, FILTER_VALIDATE_IP ) !== false;
	}

	/**
	 * Validate that the attribute is a valid JSON string.
	 *
	 * @param string $attribute The attribute name.
	 * @param mixed $value The value to validate.
	 * @param array<string> $parameters The rule parameters.
	 *
	 * @return bool True if validation passes, false otherwise.
	 * @throws \JsonException
	 */
	protected function validateJson( string $attribute, mixed $value, array $parameters ): bool {
		if ( empty( $value ) ) {
			return true;
		}

		if ( ! is_string( $value ) ) {
			return false;
		}

		json_decode( $value, false, 512, JSON_THROW_ON_ERROR );

		return json_last_error() === JSON_ERROR_NONE;
	}
}
