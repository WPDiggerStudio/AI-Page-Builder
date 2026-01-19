<?php

declare(strict_types=1);

namespace BraCalculator\App\WordPress\Admin\Menus;

use WPJarvis\Framework\WP\Admin\Menu as MenuBuilder;
use WPJarvis\Framework\WP\Admin\Settings;

/**
 * TestDashboard Admin Menu
 *
 * Manages the Test Dashboard admin menu.
 *
 * @package BraCalculator\App\WordPress\Admin\Menus
 */
class TestDashboard
{
	/**
	 * Menu slug.
	 */
	public const SLUG = 'test-dashboard';

	/**
	 * Page title.
	 */
	public const PAGE_TITLE = 'Test Dashboard';

	/**
	 * Menu title.
	 */
	public const MENU_TITLE = 'Test Dashboard';

	/**
	 * Required capability.
	 */
	public const CAPABILITY = 'manage_options';

	/**
	 * Settings builder instance.
	 *
	 * @var Settings|null
	 */
	private ?Settings $settings = null;

	/**
	 * Register the admin menu.
	 */
	public function register(): void
	{
		MenuBuilder::make()
			->page(self::SLUG, self::PAGE_TITLE)
			->title(self::PAGE_TITLE, self::MENU_TITLE)
			->icon('dashicons-admin-generic')
			->position(30)
			->capability(self::CAPABILITY)
			->render([$this, 'renderDashboard'])
			->subpage(self::SLUG . '-settings', __('Settings', 'bra-calculator'), [$this, 'renderSettings'])
			->register();

		// Build and register settings
		$this->buildSettings();

		// Enqueue admin styles
		add_action('admin_enqueue_scripts', [$this, 'enqueueStyles']);
	}

	/**
	 * Define settings fields.
	 *
	 * Each field should have: type, id, label, and optional description/options.
	 * Using a method allows for proper localization with __() function.
	 *
	 * @return array<int, array<string, mixed>> Array of field definitions.
	 */
	protected function settingsFields(): array
	{
		return [
			// Text Fields Section
			[
				'type' => 'title',
				'id' => 'text_section',
				'label' => __('Text Fields', 'bra-calculator'),
			],
			[
				'type' => 'text',
				'id' => 'site_name',
				'label' => __('Site Name', 'bra-calculator'),
				'description' => __('Enter your site name.', 'bra-calculator'),
                'size' => 'full'
			],
			[
				'type' => 'email',
				'id' => 'contact_email',
				'label' => __('Contact Email', 'bra-calculator'),
				'description' => __('Primary contact email address.', 'bra-calculator'),
			],
			[
				'type' => 'url',
				'id' => 'website_url',
				'label' => __('Website URL', 'bra-calculator'),
				'description' => __('Your website URL.', 'bra-calculator'),
			],
			[
				'type' => 'number',
				'id' => 'items_per_page',
				'label' => __('Items Per Page', 'bra-calculator'),
				'description' => __('Number of items to display per page.', 'bra-calculator'),
				'default' => 10,
				'min' => 1,
				'max' => 100,
			],

			// Content Section
			[
				'type' => 'title',
				'id' => 'content_section',
				'label' => __('Content Settings', 'bra-calculator'),
			],
			[
				'type' => 'textarea',
				'id' => 'footer_text',
				'label' => __('Footer Text', 'bra-calculator'),
				'description' => __('Text to display in the footer.', 'bra-calculator'),
				'rows' => 3,
			],
			[
				'type' => 'wysiwyg',
				'id' => 'welcome_message',
				'label' => __('Welcome Message', 'bra-calculator'),
				'description' => __('Message displayed to new users.', 'bra-calculator'),
			],

			// Options Section
			[
				'type' => 'title',
				'id' => 'options_section',
				'label' => __('Options', 'bra-calculator'),
			],
			[
				'type' => 'select',
				'id' => 'display_mode',
				'label' => __('Display Mode', 'bra-calculator'),
				'options' => [
					'grid' => __('Grid', 'bra-calculator'),
					'list' => __('List', 'bra-calculator'),
					'card' => __('Card', 'bra-calculator'),
				],
				'default' => 'grid',
			],
			[
				'type' => 'checkbox',
				'id' => 'enable_caching',
				'label' => __('Enable Caching', 'bra-calculator'),
				'checkbox_label' => __('Enable caching for better performance', 'bra-calculator'),
			],
			[
				'type' => 'multicheck',
				'id' => 'enabled_features',
				'label' => __('Enabled Features', 'bra-calculator'),
				'description' => __('Select which features to enable.', 'bra-calculator'),
				'options' => [
					'comments' => __('Comments', 'bra-calculator'),
					'ratings' => __('Ratings', 'bra-calculator'),
					'sharing' => __('Social Sharing', 'bra-calculator'),
					'analytics' => __('Analytics', 'bra-calculator'),
				],
			],
			[
				'type' => 'radio',
				'id' => 'layout_style',
				'label' => __('Layout Style', 'bra-calculator'),
				'description' => __('Choose the layout style.', 'bra-calculator'),
				'options' => [
					'modern' => __('Modern', 'bra-calculator'),
					'classic' => __('Classic', 'bra-calculator'),
					'minimal' => __('Minimal', 'bra-calculator'),
				],
				'default' => 'modern',
			],

			// Appearance Section
			[
				'type' => 'title',
				'id' => 'appearance_section',
				'label' => __('Appearance', 'bra-calculator'),
			],
			[
				'type' => 'color_picker',
				'id' => 'primary_color',
				'label' => __('Primary Color', 'bra-calculator'),
				'description' => __('Main brand color.', 'bra-calculator'),
				'default' => '#667eea',
			],
			[
				'type' => 'image_upload',
				'id' => 'logo',
				'label' => __('Logo', 'bra-calculator'),
				'description' => __('Upload your logo image.', 'bra-calculator'),
			],
		];
	}

	/**
	 * Define settings tabs.
	 *
	 * Return empty array for no tabs.
	 *
	 * @return array<int, array<string, mixed>> Array of tab definitions.
	 */
	protected function settingsTabs(): array
	{
		return [
			[
				'id' => 'general',
				'title' => __('General', 'bra-calculator'),
				'icon' => 'dashicons-admin-generic',
				'fields' => ['text_section', 'site_name', 'contact_email', 'website_url', 'items_per_page'],
			],
			[
				'id' => 'content',
				'title' => __('Content', 'bra-calculator'),
				'icon' => 'dashicons-admin-page',
				'fields' => ['content_section', 'footer_text', 'welcome_message'],
			],
			[
				'id' => 'options',
				'title' => __('Options', 'bra-calculator'),
				'icon' => 'dashicons-admin-settings',
				'fields' => ['options_section', 'display_mode', 'enable_caching', 'enabled_features', 'layout_style'],
			],
			[
				'id' => 'appearance',
				'title' => __('Appearance', 'bra-calculator'),
				'icon' => 'dashicons-art',
				'fields' => ['appearance_section', 'primary_color', 'logo'],
			],
		];
	}

	/**
	 * Define settings rows for column layouts.
	 *
	 * @return array<int, array<int, array<string, string>>> Array of row definitions.
	 */
	protected function settingsRows(): array
	{
		return [
			[
				['contact_email', 'class' => 'wpj-col-half'],
				['website_url', 'class' => 'wpj-col-half'],
			],
		];
	}

	/**
	 * Whether to use vertical tabs.
	 *
	 * @return bool True for vertical tabs.
	 */
	protected function verticalTabs(): bool
	{
		return true;
	}

	/**
	 * Build the settings instance.
	 *
	 * @return void
	 */
	private function buildSettings(): void
	{
		$this->settings = Settings::make(self::SLUG . '-settings', __(self::PAGE_TITLE . ' Settings', 'bra-calculator'))
			->capability(self::CAPABILITY);

		// Register fields
		foreach ($this->settingsFields() as $field) {
			$this->settings->field(
				$field['type'],
				$field['id'],
				$field['label'],
				array_diff_key($field, array_flip(['type', 'id', 'label']))
			);
		}

		// Register rows
		foreach ($this->settingsRows() as $row) {
			$this->settings->row($row);
		}

		// Enable vertical tabs if configured
		if ($this->verticalTabs()) {
			$this->settings->verticalTabs();
		}

		// Register tabs
		foreach ($this->settingsTabs() as $tab) {
			$this->settings->tab(
				$tab['id'],
				$tab['title'],
				$tab['fields'],
				$tab['icon'] ?? ''
			);
		}

		// Register settings only (page is already registered by Menu builder)
		$this->settings->registerSettingsOnly();
	}

	/**
	 * Enqueue admin styles for our pages.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueueStyles(string $hook): void
	{
		if (!str_contains($hook, self::SLUG)) {
			return;
		}

		wp_add_inline_style('wp-admin', $this->getInlineStyles());
	}

	/**
	 * Get inline CSS styles.
	 *
	 * @return string CSS styles.
	 */
	protected function getInlineStyles(): string
	{
		return '
		    .settings-wrapper {
                display: flex;
            }

            .wp-jarvis-settings.wpj-tabs-vertical {
                margin: 0 !important;
            }

            .wp-jarvis-settings form {
                width: 100%;
            }

            .wp-jarvis-settings p.submit {
                text-align: right;
                max-width: 100%;
                margin-top: 20px;
                padding-top: 10px;
                margin-right: 20px;
            }
			/* Dashboard Styles */
			.wpj-dashboard {
				max-width: 1400px;
				margin: 20px auto;
				padding: 0 20px;
			}

			.wpj-hero {
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				border-radius: 16px;
				padding: 48px;
				margin-bottom: 32px;
				color: #fff;
				position: relative;
				overflow: hidden;
				box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
			}

			.wpj-hero h1 {
				font-size: 36px;
				font-weight: 700;
				margin: 0 0 12px;
				color: #fff;
			}

			.wpj-hero p {
				font-size: 18px;
				opacity: 0.9;
				margin: 0;
			}

			.wpj-hero-actions {
				margin-top: 24px;
				display: flex;
				gap: 12px;
			}

			.wpj-hero-actions .button {
				background: rgba(255,255,255,0.2);
				border: 2px solid rgba(255,255,255,0.4);
				color: #fff;
				padding: 10px 24px;
				font-size: 14px;
				font-weight: 600;
				border-radius: 8px;
				transition: all 0.3s ease;
				text-decoration: none;
			}

			.wpj-hero-actions .button:hover {
				background: rgba(255,255,255,0.3);
				color: #fff;
			}

			.wpj-hero-actions .button-primary {
				background: #fff;
				border-color: #fff;
				color: #667eea;
			}

			.wpj-stats-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
				gap: 24px;
				margin-bottom: 32px;
			}

			.wpj-stat-card {
				background: #fff;
				border-radius: 12px;
				padding: 24px;
				box-shadow: 0 2px 12px rgba(0,0,0,0.08);
				border: 1px solid #e5e7eb;
				transition: all 0.3s ease;
				position: relative;
				overflow: hidden;
			}

			.wpj-stat-card:hover {
				transform: translateY(-4px);
				box-shadow: 0 12px 40px rgba(0,0,0,0.12);
			}

			.wpj-stat-card::before {
				content: "";
				position: absolute;
				top: 0;
				left: 0;
				right: 0;
				height: 4px;
			}

			.wpj-stat-card.purple::before { background: linear-gradient(90deg, #667eea, #764ba2); }
			.wpj-stat-card.blue::before { background: linear-gradient(90deg, #3b82f6, #1d4ed8); }
			.wpj-stat-card.green::before { background: linear-gradient(90deg, #10b981, #059669); }
			.wpj-stat-card.orange::before { background: linear-gradient(90deg, #f59e0b, #d97706); }

			.wpj-stat-icon {
				width: 48px;
				height: 48px;
				border-radius: 12px;
				display: flex;
				align-items: center;
				justify-content: center;
				margin-bottom: 16px;
				font-size: 24px;
			}

			.wpj-stat-card.purple .wpj-stat-icon { background: rgba(102, 126, 234, 0.1); color: #667eea; }
			.wpj-stat-card.blue .wpj-stat-icon { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
			.wpj-stat-card.green .wpj-stat-icon { background: rgba(16, 185, 129, 0.1); color: #10b981; }
			.wpj-stat-card.orange .wpj-stat-icon { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

			.wpj-stat-value {
				font-size: 32px;
				font-weight: 700;
				color: #1f2937;
				line-height: 1;
				margin-bottom: 4px;
			}

			.wpj-stat-label {
				font-size: 14px;
				color: #6b7280;
				font-weight: 500;
			}

			.wpj-cards-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
				gap: 24px;
				margin-bottom: 32px;
			}

			.wpj-card {
				background: #fff;
				border-radius: 12px;
				padding: 28px;
				box-shadow: 0 2px 12px rgba(0,0,0,0.08);
				border: 1px solid #e5e7eb;
			}

			.wpj-card-header {
				display: flex;
				align-items: center;
				gap: 12px;
				margin-bottom: 20px;
				padding-bottom: 16px;
				border-bottom: 1px solid #f3f4f6;
			}

			.wpj-card-icon {
				width: 40px;
				height: 40px;
				border-radius: 10px;
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				display: flex;
				align-items: center;
				justify-content: center;
				color: #fff;
				font-size: 18px;
			}

			.wpj-card-title {
				font-size: 18px;
				font-weight: 600;
				color: #1f2937;
				margin: 0;
			}

			.wpj-action-list {
				list-style: none;
				margin: 0;
				padding: 0;
			}

			.wpj-action-list li {
				margin-bottom: 12px;
			}

			.wpj-action-link {
				display: flex;
				align-items: center;
				gap: 12px;
				padding: 14px 16px;
				background: #f9fafb;
				border-radius: 10px;
				text-decoration: none;
				color: #374151;
				font-weight: 500;
				transition: all 0.2s ease;
			}

			.wpj-action-link:hover {
				background: #f3f4f6;
				color: #667eea;
				transform: translateX(4px);
			}

			.wpj-footer {
				text-align: center;
				padding: 24px;
				color: #9ca3af;
				font-size: 13px;
			}

			/* Settings Styles */
			.wpj-settings-wrap {
				padding: 0 10px;
			}

			.wpj-settings-title {
				display: flex;
				align-items: center;
				gap: 12px;
				font-size: 28px;
				font-weight: 600;
				color: #1f2937;
				margin-bottom: 8px;
			}

			.wpj-settings-title .dashicons {
				font-size: 32px;
				width: 32px;
				height: 32px;
				color: #667eea;
			}

			.wpj-settings-hero {
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				border-radius: 12px;
				padding: 24px 32px;
				margin-bottom: 24px;
				color: #fff;
			}

			.wpj-settings-hero p {
				margin: 0;
				font-size: 15px;
				opacity: 0.95;
			}

			/* Tabs Styling */
			.wp-jarvis-settings.wpj-tabs-vertical {
				display: flex;
				gap: 0;
				background: #fff;
				border-radius: 12px;
				box-shadow: 0 2px 12px rgba(0,0,0,0.08);
				border: 1px solid #e5e7eb;
				overflow: hidden;
			}

			.wp-jarvis-settings .wpj-tabs {
				width: 220px;
				background: #f9fafb;
				border-right: 1px solid #e5e7eb;
				padding: 16px 0;
				flex-shrink: 0;
			}

			.wp-jarvis-settings .wpj-tab {
				display: flex;
				align-items: center;
				gap: 10px;
				padding: 14px 20px;
				color: #4b5563;
				cursor: pointer;
				transition: all 0.2s ease;
				border-left: 3px solid transparent;
				font-weight: 500;
			}

			.wp-jarvis-settings .wpj-tab:hover {
				background: #f3f4f6;
				color: #374151;
			}

			.wp-jarvis-settings .wpj-tab.active {
				background: #fff;
				color: #667eea;
				border-left-color: #667eea;
			}

			.wp-jarvis-settings .wpj-tabs-content {
				flex: 1;
				padding: 32px;
			}

			.wp-jarvis-settings .wpj-tab-content {
				display: none;
			}

			.wp-jarvis-settings .wpj-tab-content-active {
				display: block;
			}

			/* Field Styling */
			.wp-jarvis-settings .wpj-field {
				margin-bottom: 24px;
			}

			.wp-jarvis-settings .wpj-field-row {
				display: flex;
				gap: 20px;
				flex-wrap: wrap;
			}

			.wp-jarvis-settings .wpj-col-half {
				flex: 1;
				min-width: 250px;
			}

			.wp-jarvis-settings input[type="text"],
			.wp-jarvis-settings input[type="email"],
			.wp-jarvis-settings input[type="url"],
			.wp-jarvis-settings input[type="number"],
			.wp-jarvis-settings select,
			.wp-jarvis-settings textarea {
				width: 100%;
				padding: 10px 14px;
				border: 1px solid #d1d5db;
				border-radius: 8px;
				font-size: 14px;
				transition: all 0.2s ease;
			}

			.wp-jarvis-settings input:focus,
			.wp-jarvis-settings select:focus,
			.wp-jarvis-settings textarea:focus {
				border-color: #667eea;
				box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
				outline: none;
			}

			.wp-jarvis-settings .button-primary {
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				border: none;
				padding: 10px 24px;
				font-size: 14px;
				font-weight: 600;
				border-radius: 8px;
				cursor: pointer;
				transition: all 0.2s ease;
			}

			.wp-jarvis-settings .button-primary:hover {
				transform: translateY(-2px);
				box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
			}

			@media (max-width: 900px) {
				.wp-jarvis-settings.wpj-tabs-vertical {
					flex-direction: column;
				}
				.wp-jarvis-settings .wpj-tabs {
					width: 100%;
					border-right: none;
					border-bottom: 1px solid #e5e7eb;
					display: flex;
					flex-wrap: wrap;
					padding: 12px;
				}
				.wp-jarvis-settings .wpj-tab {
					border-left: none;
					border-bottom: 3px solid transparent;
				}
				.wp-jarvis-settings .wpj-tab.active {
					border-bottom-color: #667eea;
					border-left-color: transparent;
				}
			}
		';
	}

	/**
	 * Render the dashboard page.
	 */
	public function renderDashboard(): void
	{
		if (!current_user_can(self::CAPABILITY)) {
			wp_die(__('You do not have permission to access this page.', 'bra-calculator'));
		}

		$stats = $this->getStats();
		?>
		<div class="wpj-dashboard">
			<!-- Hero Section -->
			<div class="wpj-hero">
				<h1><?php esc_html_e('Welcome to Test Dashboard!', 'bra-calculator'); ?></h1>
				<p><?php esc_html_e('Manage your plugin settings and monitor performance from this dashboard.', 'bra-calculator'); ?>
				</p>
				<div class="wpj-hero-actions">
					<a href="<?php echo esc_url(admin_url('admin.php?page=' . self::SLUG . '-settings')); ?>"
						class="button button-primary">
						<?php esc_html_e('Configure Settings', 'bra-calculator'); ?>
					</a>
					<a href="#" class="button">
						<?php esc_html_e('View Documentation', 'bra-calculator'); ?>
					</a>
				</div>
			</div>

			<!-- Stats Grid -->
			<div class="wpj-stats-grid">
				<div class="wpj-stat-card purple">
					<div class="wpj-stat-icon">
						<span class="dashicons dashicons-admin-post"></span>
					</div>
					<div class="wpj-stat-value"><?php echo esc_html($stats['posts']); ?></div>
					<div class="wpj-stat-label"><?php esc_html_e('Published Posts', 'bra-calculator'); ?></div>
				</div>

				<div class="wpj-stat-card blue">
					<div class="wpj-stat-icon">
						<span class="dashicons dashicons-admin-page"></span>
					</div>
					<div class="wpj-stat-value"><?php echo esc_html($stats['pages']); ?></div>
					<div class="wpj-stat-label"><?php esc_html_e('Published Pages', 'bra-calculator'); ?></div>
				</div>

				<div class="wpj-stat-card green">
					<div class="wpj-stat-icon">
						<span class="dashicons dashicons-admin-users"></span>
					</div>
					<div class="wpj-stat-value"><?php echo esc_html($stats['users']); ?></div>
					<div class="wpj-stat-label"><?php esc_html_e('Total Users', 'bra-calculator'); ?></div>
				</div>

				<div class="wpj-stat-card orange">
					<div class="wpj-stat-icon">
						<span class="dashicons dashicons-admin-comments"></span>
					</div>
					<div class="wpj-stat-value"><?php echo esc_html($stats['comments']); ?></div>
					<div class="wpj-stat-label"><?php esc_html_e('Comments', 'bra-calculator'); ?></div>
				</div>
			</div>

			<!-- Cards Grid -->
			<div class="wpj-cards-grid">
				<!-- Quick Actions Card -->
				<div class="wpj-card">
					<div class="wpj-card-header">
						<div class="wpj-card-icon">
							<span class="dashicons dashicons-superhero"></span>
						</div>
						<h3 class="wpj-card-title"><?php esc_html_e('Quick Actions', 'bra-calculator'); ?></h3>
					</div>
					<ul class="wpj-action-list">
						<li>
							<a href="<?php echo esc_url(admin_url('admin.php?page=' . self::SLUG . '-settings')); ?>"
								class="wpj-action-link">
								<span class="dashicons dashicons-admin-settings"></span>
								<?php esc_html_e('Configure Settings', 'bra-calculator'); ?>
							</a>
						</li>
						<li>
							<a href="<?php echo esc_url(admin_url('post-new.php')); ?>" class="wpj-action-link">
								<span class="dashicons dashicons-plus-alt"></span>
								<?php esc_html_e('Create New Post', 'bra-calculator'); ?>
							</a>
						</li>
						<li>
							<a href="<?php echo esc_url(admin_url('users.php')); ?>" class="wpj-action-link">
								<span class="dashicons dashicons-groups"></span>
								<?php esc_html_e('Manage Users', 'bra-calculator'); ?>
							</a>
						</li>
					</ul>
				</div>

				<!-- System Info Card -->
				<div class="wpj-card">
					<div class="wpj-card-header">
						<div class="wpj-card-icon">
							<span class="dashicons dashicons-info-outline"></span>
						</div>
						<h3 class="wpj-card-title"><?php esc_html_e('System Information', 'bra-calculator'); ?></h3>
					</div>
					<ul class="wpj-action-list">
						<li>
							<div class="wpj-action-link" style="cursor: default;">
								<span class="dashicons dashicons-wordpress"></span>
								<?php printf(__('WordPress %s', 'bra-calculator'), get_bloginfo('version')); ?>
							</div>
						</li>
						<li>
							<div class="wpj-action-link" style="cursor: default;">
								<span class="dashicons dashicons-editor-code"></span>
								<?php printf(__('PHP %s', 'bra-calculator'), PHP_VERSION); ?>
							</div>
						</li>
						<li>
							<div class="wpj-action-link" style="cursor: default;">
								<span class="dashicons dashicons-database"></span>
								<?php
								global $wpdb;
								printf(__('MySQL %s', 'bra-calculator'), $wpdb->db_version());
								?>
							</div>
						</li>
					</ul>
				</div>
			</div>

			<!-- Footer -->
			<div class="wpj-footer">
				<?php printf(
					esc_html__('Made with %s by %s', 'bra-calculator'),
					'<span style="color: #ef4444;">♥</span>',
					'<a href="#">Test Dashboard</a>'
				); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get dashboard stats.
	 *
	 * @return array<string, int>
	 */
	protected function getStats(): array
	{
		$posts = wp_count_posts();
		$pages = wp_count_posts('page');
		$users = count_users();
		$comments = wp_count_comments();

		return [
			'posts' => (int) ($posts->publish ?? 0),
			'pages' => (int) ($pages->publish ?? 0),
			'users' => (int) ($users['total_users'] ?? 0),
			'comments' => (int) ($comments->approved ?? 0),
		];
	}

	/**
	 * Render the settings page.
	 */
	public function renderSettings(): void
	{
		if (!current_user_can(self::CAPABILITY)) {
			wp_die(__('You do not have permission to access this page.', 'bra-calculator'));
		}
		?>
		<div class="wrap wpj-settings-wrap">
			<h1 class="wpj-settings-title">
				<span class="dashicons dashicons-admin-settings"></span>
				<?php esc_html_e('Test Dashboard Settings', 'bra-calculator'); ?>
			</h1>

			<div class="wpj-settings-hero">
				<p><?php esc_html_e('Configure your plugin settings below. Changes are saved when you click Save Changes.', 'bra-calculator'); ?>
				</p>
			</div>

			<?php $this->settings->render(); ?>
		</div>
		<?php
	}

	/**
	 * Get a settings value.
	 *
	 * @param string $key The setting key.
	 * @param mixed $default The default value.
	 *
	 * @return mixed The setting value.
	 */
	public static function getSetting(string $key, mixed $default = null): mixed
	{
		$values = get_option(self::SLUG . '-settings_settings', []);

		return $values[$key] ?? $default;
	}

	/**
	 * Get all settings values.
	 *
	 * @return array<string, mixed> All values.
	 */
	public static function getAllSettings(): array
	{
		return get_option(self::SLUG . '-settings_settings', []);
	}

	/**
	 * Get the menu URL.
	 *
	 * @param string $page Page slug suffix.
	 *
	 * @return string
	 */
	public static function url(string $page = ''): string
	{
		$slug = self::SLUG;
		if ($page) {
			$slug .= '-' . $page;
		}

		return admin_url('admin.php?page=' . $slug);
	}

	/**
	 * Check if we're on one of our admin pages.
	 *
	 * @return bool
	 */
	public static function isCurrentPage(): bool
	{
		$screen = get_current_screen();

		return $screen && str_contains($screen->id, self::SLUG);
	}
}
