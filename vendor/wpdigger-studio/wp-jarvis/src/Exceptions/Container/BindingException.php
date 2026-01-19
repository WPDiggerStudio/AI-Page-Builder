<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Exceptions\Container;

use WPJarvis\Framework\Exceptions\FrameworkException;

/**
 * BindingException - Exception for container binding errors.
 */
class BindingException extends FrameworkException {
	/**
	 * Create exception for unbound abstract.
	 *
	 * @param string $abstract The abstract binding name.
	 *
	 * @return static The exception instance.
	 */
	public static function notBound( string $abstract ): static {
		return new static(
			sprintf(
			/* translators: %s: target/binding name */
				__( 'Target [%s] is not bound in the container.', 'wp-jarvis' ),
				$abstract
			)
		);
	}

	/**
	 * Create an exception for non-instantiable class.
	 *
	 * @param string $class The class name.
	 *
	 * @return static The exception instance.
	 */
	public static function notInstantiable( string $class ): static {
		return new static(
			sprintf(
			/* translators: %s: class name */
				__( 'Target [%s] is not instantiable.', 'wp-jarvis' ),
				$class
			)
		);
	}

	/**
	 * Create exception for unresolvable dependency.
	 *
	 * @param string $dependency The dependency name.
	 * @param string $class The class name.
	 *
	 * @return static The exception instance.
	 */
	public static function unresolvableDependency( string $dependency, string $class ): static {
		return ( new static(
			sprintf(
			/* translators: 1: dependency name, 2: class name */
				__( 'Unresolvable dependency [%1$s] in class [%2$s].', 'wp-jarvis' ),
				$dependency,
				$class
			)
		) )->withContext( [ 'dependency' => $dependency, 'class' => $class ] );
	}
}
