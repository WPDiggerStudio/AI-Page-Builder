<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\WP\Admin;

use WPJarvis\Framework\Support\Facades\Hooks;

/**
 * Menu - Fluent builder for admin menus.
 */

/**
 * Menu - Fluent builder for admin menus.
 */
class Menu {
	/**
	 * The registered menu pages.
	 *
	 * @var array<string, array{page_title: string, menu_title: string, capability: string, menu_slug: string, callback: callable|string, icon_url: string, position: int|null}>
	 */
	private array $pages = [];

	/**
	 * The registered submenu pages.
	 *
	 * @var array<string, array<array{parent_slug: string, page_title: string, menu_title: string, capability: string, menu_slug: string, callback: callable|string}>>
	 */
	private array $subpages = [];

	/**
	 * The current page being built.
	 *
	 * @var string|null
	 */
	private ?string $currentPage = null;

	/**
	 * Create a new Menu instance.
	 *
	 * @return static The new Menu instance.
	 */
	public static function make(): static {
		return new static();
	}

	/**
	 * Add a menu page.
	 *
	 * @param string $slug The menu slug.
	 * @param string $title The page title.
	 *
	 * @return static The current instance for chaining.
	 */
	public function page( string $slug, string $title ): static {
		$this->currentPage    = $slug;
		$this->pages[ $slug ] = [
			'page_title' => $title,
			'menu_title' => $title,
			'capability' => 'manage_options',
			'menu_slug'  => $slug,
			'callback'   => '',
			'icon_url'   => '',
			'position'   => null,
		];

		return $this;
	}

	/**
	 * Set the page title and menu title.
	 *
	 * @param string $pageTitle The page title.
	 * @param string|null $menuTitle The menu title (defaults to page title).
	 *
	 * @return static The current instance for chaining.
	 */
	public function title( string $pageTitle, ?string $menuTitle = null ): static {
		if ( $this->currentPage ) {
			$this->pages[ $this->currentPage ]['page_title'] = $pageTitle;
			$this->pages[ $this->currentPage ]['menu_title'] = $menuTitle ?? $pageTitle;
		}

		return $this;
	}

	/**
	 * Set the required capability.
	 *
	 * @param string $capability The required capability.
	 *
	 * @return static The current instance for chaining.
	 */
	public function capability( string $capability ): static {
		if ( $this->currentPage ) {
			$this->pages[ $this->currentPage ]['capability'] = $capability;
		}

		return $this;
	}

	/**
	 * Set the menu icon.
	 *
	 * @param string $icon The dashicon or URL.
	 *
	 * @return static The current instance for chaining.
	 */
	public function icon( string $icon ): static {
		if ( $this->currentPage ) {
			$this->pages[ $this->currentPage ]['icon_url'] = $icon;
		}

		return $this;
	}

	/**
	 * Set the menu position.
	 *
	 * @param int|float $position The menu position.
	 *
	 * @return static The current instance for chaining.
	 */
	public function position( int|float $position ): static {
		if ( $this->currentPage ) {
			$this->pages[ $this->currentPage ]['position'] = $position;
		}

		return $this;
	}

	/**
	 * Set the render callback.
	 *
	 * @param callable $callback The render callback function.
	 *
	 * @return static The current instance for chaining.
	 */
	public function render( callable $callback ): static {
		if ( $this->currentPage ) {
			$this->pages[ $this->currentPage ]['callback'] = $callback;
		}

		return $this;
	}

	/**
	 * Set the view to render.
	 *
	 * @param string $view The view name.
	 * @param array<string, mixed> $data The view data.
	 *
	 * @return static The current instance for chaining.
	 */
	public function view( string $view, array $data = [] ): static {
		return $this->render( function () use ( $view, $data ) {
			echo wpj_view( $view, $data )->render();
		} );
	}

	/**
	 * Add a submenu page.
	 *
	 * @param string $slug The submenu slug.
	 * @param string $title The page title.
	 * @param callable|null $callback The render callback function.
	 *
	 * @return static The current instance for chaining.
	 */
	public function subpage( string $slug, string $title, ?callable $callback = null ): static {
		if ( ! $this->currentPage ) {
			return $this;
		}

		$this->subpages[ $this->currentPage ][] = [
			'parent_slug' => $this->currentPage,
			'page_title'  => $title,
			'menu_title'  => $title,
			'capability'  => $this->pages[ $this->currentPage ]['capability'],
			'menu_slug'   => $slug,
			'callback'    => $callback,
		];

		return $this;
	}

	/**
	 * Set the title for the first submenu item.
	 *
	 * WordPress auto-creates the first submenu with the same title as the parent.
	 * This method allows you to rename it to something like "Dashboard" or "Overview".
	 *
	 * @param string $title The first submenu title.
	 *
	 * @return static The current instance for chaining.
	 */
	public function firstSubmenuTitle( string $title ): static {
		if ( $this->currentPage ) {
			$this->pages[ $this->currentPage ]['first_submenu_title'] = $title;
		}

		return $this;
	}


	/**
	 * Register all menu pages with WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		Hooks::action( 'admin_menu', [ $this, 'addMenus' ] );
	}

	/**
	 * Add menu pages to WordPress admin.
	 *
	 * @return void
	 */
	public function addMenus(): void {
		foreach ( $this->pages as $slug => $page ) {
			add_menu_page(
				$page['page_title'],
				$page['menu_title'],
				$page['capability'],
				$page['menu_slug'],
				$page['callback'],
				$page['icon_url'],
				$page['position']
			);

			// Rename the auto-generated first submenu to avoid duplicate parent title.
			// WordPress creates a submenu with the same title as the parent menu.
			// Re-register it with slug = parent slug to rename it to "Dashboard".
			$firstSubmenuTitle = $page['first_submenu_title'] ?? __( 'Dashboard', 'wp-jarvis' );
			add_submenu_page(
				$slug,
				$page['page_title'],
				$firstSubmenuTitle,
				$page['capability'],
				$slug,
				$page['callback']
			);
		}

		foreach ( $this->subpages as $items ) {
			foreach ( $items as $subpage ) {
				add_submenu_page(
					$subpage['parent_slug'],
					$subpage['page_title'],
					$subpage['menu_title'],
					$subpage['capability'],
					$subpage['menu_slug'],
					$subpage['callback']
				);
			}
		}
	}
}
