<?php

declare( strict_types=1 );

namespace WPJarvis\Framework\Providers;

use WPJarvis\Framework\Foundation\ServiceProvider;
use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\WP\Fields\Contracts\FieldRegistryInterface;
use WPJarvis\Framework\WP\Fields\Registry;

/**
 * Field Service Provider
 *
 * Registers all field types with the field registry.
 *
 * @package WPJarvis\Framework\Providers
 */
class FieldServiceProvider extends ServiceProvider {
	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register(): void {
		// Register the field registry as a singleton
		$this->singleton( FieldRegistryInterface::class, fn() => new Registry() );

		// Register the field registry alias for easier access
		// The alias() method creates a reference to the existing binding
		$this->alias( FieldRegistryInterface::class, 'field_registry' );
	}

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function boot(): void {
		$registry = $this->app->make( FieldRegistryInterface::class );

		// Register General field types
		$this->registerGeneralFields( $registry );

		// Register WordPress-specific field types
		$this->registerWordPressFields( $registry );

		// Register Utility/Advanced field types
		$this->registerUtilityFields( $registry );

		// Register oEmbed AJAX handler
		$this->registerOembedAjax();

		// Enqueue field assets on admin screens
		$this->enqueueFieldAssets();

		/**
		 * Fires action after all field types are registered.
		 *
		 * @param Registry $registry The field registry instance.
		 */
		Hooks::doAction( 'fields_booted', $registry );
	}

	/**
	 * Enqueue field system assets on admin screens.
	 *
	 * @return void
	 */
	private function enqueueFieldAssets(): void {
		Hooks::action( 'admin_enqueue_scripts', function () {
			// Get plugin URL from config or construct from a path
			$frameworkUrl = plugins_url( '', dirname( __DIR__, 2 ) . '/wp-jarvis-framework.php' );
			$assetsUrl    = $frameworkUrl . '/resources/assets';

			// Enqueue field styles
			wp_enqueue_style(
				'wpj-fields',
				$assetsUrl . '/css/fields.css',
				[],
				'1.0.0'
			);

			/**
			 * Filter to allow adding custom field styles.
			 *
			 * @param string $assetsUrl The assets URL.
			 */
			do_action( 'wpj_field_enqueue_styles', $assetsUrl );

			// Enqueue the color picker if needed
			if ( wp_script_is( 'wp-color-picker', 'registered' ) ) {
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker' );
			}

			// Enqueue WordPress media library for upload fields
			wp_enqueue_media();

			// Enqueue Ace Editor from CDN for code fields
			wp_enqueue_script(
				'ace-editor',
				'https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.6/ace.min.js',
				[],
				'1.32.6',
				true
			);

			// Enqueue Select2 for enhanced select fields
			wp_enqueue_style(
				'select2',
				'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css',
				[],
				'4.0.13'
			);
			wp_enqueue_script(
				'select2',
				'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js',
				[ 'jquery' ],
				'4.0.13',
				true
			);

			// Enqueue Leaflet.js for location fields (OpenStreetMap - FREE, no API key)
			wp_enqueue_style(
				'leaflet',
				'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css',
				[],
				'1.9.4'
			);
			wp_enqueue_script(
				'leaflet',
				'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js',
				[],
				'1.9.4',
				true
			);

			// Enqueue flag-icons CSS for country select field
			wp_enqueue_style(
				'flag-icons',
				'https://cdnjs.cloudflare.com/ajax/libs/flag-icons/7.2.3/css/flag-icons.min.css',
				[],
				'7.2.3'
			);

			// Localize script with AJAX data for oEmbed
			wp_localize_script(
				'jquery',
				'wpjFields',
				[
					'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
					'oembedNonce' => wp_create_nonce( 'wpj_oembed_nonce' ),
				]
			);

			// Enqueue field initialization script
			wp_add_inline_script(
				'jquery',
				$this->getFieldInitScript(),
				'after'
			);
		} );
	}

	/**
	 * Get inline JavaScript for field initialization.
	 *
	 * @return string JavaScript code.
	 */
	private function getFieldInitScript(): string {
		return <<<'JS'
(function($) {
    $(document).ready(function() {
        // Tabs Handler
        $(document).on('click', '.wpj-tab', function(e) {
            e.preventDefault();
            var $tab = $(this);
            var tabId = $tab.data('tab-id');
            var $wrap = $tab.closest('.wpj-tabs-wrap');

            if ($tab.hasClass('active')) return;

            // Update tab navigation
            $wrap.find('.wpj-tab').removeClass('active');
            $tab.addClass('active');

            // Update tab content
            $wrap.find('.wpj-tab-content').removeClass('wpj-tab-content-active');
            $wrap.find('.wpj-tab-content[data-tab-id="' + tabId + '"]').addClass('wpj-tab-content-active');
        });
        // Initialize color pickers
        if ($.fn.wpColorPicker) {
            $('.wpj-color-picker').wpColorPicker({
                change: function(event, ui) {
                    $(this).siblings('.wpj-field__color-preview').css('background-color', ui.color.toString());
                }
            });
        }

        // Initialize Ace Editor for code fields
        if (typeof ace !== 'undefined') {
            $('.wpj-ace-editor').each(function() {
                var $container = $(this);
                if ($container.data('ace-initialized')) return;

                var textareaId = $container.data('textarea');
                var language = $container.data('language') || 'html';
                var theme = $container.data('theme') || 'chrome';
                var readOnly = $container.data('readonly') === 'true';
                var $textarea = $('#' + textareaId);

                // Initialize Ace Editor
                var editor = ace.edit($container[0]);
                editor.setTheme('ace/theme/' + theme);
                editor.session.setMode('ace/mode/' + language);
                editor.setReadOnly(readOnly);
                editor.setShowPrintMargin(false);
                editor.setOptions({
                    fontSize: '13px',
                    showLineNumbers: true,
                    showGutter: true,
                    highlightActiveLine: true,
                    wrap: true
                });

                // Set initial value from textarea
                editor.setValue($textarea.val(), -1);

                // Sync editor content to textarea on change
                editor.on('change', function() {
                    $textarea.val(editor.getValue());
                });

                $container.data('ace-initialized', true);
                $container.data('ace-editor', editor);
            });
        }

        // ============================================================
        // Select2 Initialization
        // ============================================================
        if ($.fn.select2) {
            // Initialize regular Select2
            $('.wpj-select2').not('.wpj-country-select').each(function() {
                var $select = $(this);
                if ($select.data('select2-initialized')) return;

                var placeholder = $select.data('placeholder') || 'Select...';
                var allowClear = $select.data('allow-clear') !== false;
                var multiple = $select.prop('multiple');

                $select.select2({
                    placeholder: placeholder,
                    allowClear: allowClear && !multiple,
                    width: '100%',
                    dropdownAutoWidth: false,
                    minimumResultsForSearch: 10
                });

                $select.data('select2-initialized', true);
            });

            // Initialize Country Select2 with flag icons
            $('.wpj-country-select').each(function() {
                var $select = $(this);
                if ($select.data('select2-initialized')) return;

                var placeholder = $select.data('placeholder') || 'Select a country...';

                // Flag icon template function
                function formatCountry(option) {
                    if (!option.id) return option.text;
                    var $option = $(option.element);
                    var countryCode = $option.data('country-code');
                    if (!countryCode) return option.text;
                    return $('<span><span class="fi fi-' + countryCode + '" style="margin-right: 8px;"></span>' + option.text + '</span>');
                }

                $select.select2({
                    placeholder: placeholder,
                    allowClear: true,
                    width: '100%',
                    dropdownAutoWidth: false,
                    minimumResultsForSearch: 5,
                    templateResult: formatCountry,
                    templateSelection: formatCountry
                });

                $select.data('select2-initialized', true);
            });
        }
        $(document).on('keypress', '.wpj-money-input', function(e) {
            var charCode = e.which ? e.which : e.keyCode;
            var value = $(this).val();

            // Allow: backspace, delete, tab, escape, enter
            if (charCode === 8 || charCode === 9 || charCode === 27 || charCode === 13) {
                return true;
            }

            // Allow: minus sign (only at start)
            if (charCode === 45) {
                return value.length === 0;
            }

            // Allow: decimal point (only one)
            if (charCode === 46 || charCode === 44) {
                return value.indexOf('.') === -1 && value.indexOf(',') === -1;
            }

            // Allow: 0-9
            if (charCode >= 48 && charCode <= 57) {
                return true;
            }

            e.preventDefault();
            return false;
        });

        // Clean pasted content for money fields
        $(document).on('input', '.wpj-money-input', function() {
            var $input = $(this);
            var value = $input.val();
            var cleaned = value.replace(/[^0-9.,\-]/g, '');
            if (value !== cleaned) {
                $input.val(cleaned);
            }
        });

        // ============================================================
        // Real-time URL Validation
        // ============================================================
        $(document).on('blur input', '.wpj-field--url input[type="url"], .wpj-field--text_url input[type="url"]', function() {
            var $input = $(this);
            var value = $input.val().trim();
            var $field = $input.closest('.wpj-field');
            var $errorContainer = $field.find('.wpj-field__inline-error');

            // Remove existing error
            $errorContainer.remove();
            $field.removeClass('wpj-field--has-errors');

            if (value === '') return;

            // URL pattern validation
            var urlPattern = /^(https?|ftp):\/\/[^\s/$.?#].[^\s]*$/i;
            var isValid = urlPattern.test(value);

            if (!isValid) {
                $field.addClass('wpj-field--has-errors');
                $field.find('.wpj-field__input').after(
                    '<p class="wpj-field__inline-error wpj-field__error">' +
                    'Please enter a valid URL (e.g., https://example.com)' +
                    '</p>'
                );
            }
        });

        // ============================================================
        // Real-time Email Validation
        // ============================================================
        $(document).on('blur input', '.wpj-field--email input[type="email"], .wpj-field--text_email input[type="email"]', function() {
            var $input = $(this);
            var value = $input.val().trim();
            var $field = $input.closest('.wpj-field');
            var $errorContainer = $field.find('.wpj-field__inline-error');

            // Remove existing error
            $errorContainer.remove();
            $field.removeClass('wpj-field--has-errors');

            if (value === '') return;

            // Email pattern validation
            var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            var isValid = emailPattern.test(value);

            if (!isValid) {
                $field.addClass('wpj-field--has-errors');
                $field.find('.wpj-field__input').after(
                    '<p class="wpj-field__inline-error wpj-field__error">' +
                    'Please enter a valid email address' +
                    '</p>'
                );
            }
        });

        // ============================================================
        // Leaflet OpenStreetMap Location Field (FREE - No API Key)
        // ============================================================
        function initLeafletMaps() {
            if (typeof L === 'undefined') return;

            $('.wpj-leaflet-map').each(function() {
                var $mapContainer = $(this);
                if ($mapContainer.data('map-initialized')) return;

                // Ensure container has explicit height
                if ($mapContainer.height() < 100) {
                    $mapContainer.css('height', '300px');
                }

                var mapId = $mapContainer.attr('id');
                var $container = $mapContainer.closest('.wpj-field__location-container');
                var $addressInput = $container.find('.wpj-location-address');
                var $latInput = $container.find('.wpj-location-lat');
                var $lngInput = $container.find('.wpj-location-lng');

                var lat = parseFloat($mapContainer.data('lat')) || 0;
                var lng = parseFloat($mapContainer.data('lng')) || 0;
                var zoom = parseInt($mapContainer.data('zoom')) || 15;
                var hasCoords = lat !== 0 || lng !== 0;

                // Default center
                var center = hasCoords ? [lat, lng] : [40.7128, -74.0060];
                var initialZoom = hasCoords ? zoom : 4;

                // Initialize map without automatic view
                var map = L.map(mapId);

                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                // Set view AFTER rendering to force proper pane positioning
                map.setView(center, initialZoom);

                // Create marker
                var marker = L.marker(center, {
                    draggable: true
                });

                if (hasCoords) {
                    marker.addTo(map);
                }

                // Function to update coordinates
                function updateCoords(latlng) {
                    $latInput.val(latlng.lat.toFixed(6));
                    $lngInput.val(latlng.lng.toFixed(6));
                    marker.setLatLng(latlng);
                    if (!map.hasLayer(marker)) {
                        marker.addTo(map);
                    }
                    map.panTo(latlng);
                }

                // Reverse geocode using Nominatim (FREE)
                function reverseGeocode(lat, lng) {
                    $.ajax({
                        url: 'https://nominatim.openstreetmap.org/reverse',
                        data: {
                            lat: lat,
                            lon: lng,
                            format: 'json'
                        },
                        success: function(data) {
                            if (data && data.display_name) {
                                $addressInput.val(data.display_name);
                            }
                        }
                    });
                }

                // Marker drag
                marker.on('dragend', function(e) {
                    var pos = marker.getLatLng();
                    $latInput.val(pos.lat.toFixed(6));
                    $lngInput.val(pos.lng.toFixed(6));
                    reverseGeocode(pos.lat, pos.lng);
                });

                // Map click
                map.on('click', function(e) {
                    updateCoords(e.latlng);
                    map.setZoom(15);
                    reverseGeocode(e.latlng.lat, e.latlng.lng);
                });

                // Address search on Enter (using Nominatim)
                $addressInput.on('keypress', function(e) {
                    if (e.which === 13) {
                        e.preventDefault();
                        var address = $(this).val();
                        if (address) {
                            $.ajax({
                                url: 'https://nominatim.openstreetmap.org/search',
                                data: {
                                    q: address,
                                    format: 'json',
                                    limit: 1
                                },
                                success: function(data) {
                                    if (data && data.length > 0) {
                                        var result = data[0];
                                        var latlng = L.latLng(parseFloat(result.lat), parseFloat(result.lon));
                                        updateCoords(latlng);
                                        map.setZoom(15);
                                        $addressInput.val(result.display_name);
                                    }
                                }
                            });
                        }
                    }
                });

                $mapContainer.data('map-initialized', true);
                $mapContainer.data('map', map);
                $mapContainer.data('marker', marker);

                // ============================================================
                // FIX: Use IntersectionObserver to handle visibility changes
                // This fixes the 0px transform issue on load, in tabs, and metaboxes
                // ============================================================
                if ('IntersectionObserver' in window) {
                    var resizeObserver = new IntersectionObserver(function(entries) {
                        entries.forEach(function(entry) {
                            if (entry.isIntersecting) {
                                // The map is now visible! Recalculate size.
                                map.invalidateSize();

                                // If you want to recenter the map on the marker
                                if (hasCoords) {
                                    map.panTo(center, {animate: false});
                                }
                            }
                        });
                    });

                    // Start observing the map container
                    resizeObserver.observe($mapContainer[0]);
                } else {
                    // Fallback for very old browsers
                    setTimeout(function() { map.invalidateSize(); }, 500);
                    setTimeout(function() { map.invalidateSize(); }, 2000);
                }

                // Also hook into Tab system if the map is inside a tab
                $(document).on('click', '.wpj-tab', function() {
                    setTimeout(function() { map.invalidateSize(); }, 50);
                });
            });

            // Current location button handler
            $(document).on('click', '.wpj-location-current', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var $container = $btn.closest('.wpj-field__location-container');
                var $mapContainer = $container.find('.wpj-leaflet-map');
                var $addressInput = $container.find('.wpj-location-address');
                var $latInput = $container.find('.wpj-location-lat');
                var $lngInput = $container.find('.wpj-location-lng');

                if (navigator.geolocation) {
                    $btn.prop('disabled', true).find('.dashicons').removeClass('dashicons-location').addClass('dashicons-update spin');

                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            var lat = position.coords.latitude;
                            var lng = position.coords.longitude;
                            var latlng = L.latLng(lat, lng);

                            $latInput.val(lat.toFixed(6));
                            $lngInput.val(lng.toFixed(6));

                            var map = $mapContainer.data('map');
                            var marker = $mapContainer.data('marker');
                            if (map && marker) {
                                marker.setLatLng(latlng);
                                if (!map.hasLayer(marker)) {
                                    marker.addTo(map);
                                }
                                map.panTo(latlng);
                                map.setZoom(15);
                            }

                            // Reverse geocode
                            $.ajax({
                                url: 'https://nominatim.openstreetmap.org/reverse',
                                data: {lat: lat, lon: lng, format: 'json'},
                                success: function(data) {
                                    if (data && data.display_name) {
                                        $addressInput.val(data.display_name);
                                    }
                                }
                            });

                            $btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-location');
                        },
                        function(error) {
                            alert('Could not get your location: ' + error.message);
                            $btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-location');
                        },
                        {enableHighAccuracy: true, timeout: 10000}
                    );
                } else {
                    alert('Geolocation is not supported by your browser.');
                }
            });
        }

        // Call Leaflet init with a delay to ensure container is rendered
        setTimeout(initLeafletMaps, 200);

        // ============================================================
        // oEmbed Preview Handler
        // ============================================================
        $(document).on('click', '.wpj-oembed-fetch', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var fieldId = $btn.data('field-id');
            var $container = $btn.closest('.wpj-field__oembed-container');
            var $input = $container.find('.wpj-oembed-input');
            var $preview = $container.find('.wpj-field__oembed-preview');
            var url = $input.val().trim();

            if (!url) {
                alert('Please enter a URL first.');
                return;
            }

            // Show loading
            $btn.prop('disabled', true);
            $preview.html('<p class="description"><span class="dashicons dashicons-update spin"></span> Loading preview...</p>');

            // Make AJAX request
            $.ajax({
                url: wpjFields.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wpj_oembed_fetch',
                    nonce: wpjFields.oembedNonce,
                    url: url
                },
                success: function(response) {
                    if (response.success && response.data.embed) {
                        $preview.html(response.data.embed);
                    } else {
                        $preview.html('<p class="description">' + (response.data.message || 'No embed available.') + '</p>');
                    }
                    $btn.prop('disabled', false);
                },
                error: function() {
                    $preview.html('<p class="description">Error loading preview.</p>');
                    $btn.prop('disabled', false);
                }
            });
        });

        // Media Upload Handler
        $(document).on('click', '.wpj-field__upload-dropzone', function(e) {
            e.preventDefault();
            var $container = $(this).closest('.wpj-field__upload-container');
            var $input = $container.find('.wpj-field__upload-value');
            var mediaType = $container.data('media-type') || '';

            var frame = wp.media({
                title: 'Select or Upload Media',
                button: { text: 'Use this media' },
                library: mediaType ? { type: mediaType } : {},
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $input.val(attachment.id).trigger('change');

                // Update preview
                var $preview = $container.find('.wpj-field__upload-preview');
                var isImage = attachment.type === 'image';
                var html = '';

                if (isImage) {
                    html += '<img src="' + (attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url) + '" class="wpj-field__upload-image" />';
                }

                html += '<div class="wpj-field__upload-file-info">';
                html += '<span class="wpj-field__upload-file-icon dashicons dashicons-' + (isImage ? 'format-image' : 'media-default') + '"></span>';
                html += '<span class="wpj-field__upload-file-name">' + attachment.filename + '</span>';
                html += '</div>';
                html += '<div class="wpj-field__upload-file-path">';
                html += '<input type="text" value="' + attachment.url + '" readonly id="' + $input.attr('id') + '-path" />';
                html += '<button type="button" class="button button-small wpj-field__upload-copy-btn" data-target="' + $input.attr('id') + '-path">Copy</button>';
                html += '</div>';
                html += '<div class="wpj-field__upload-actions">';
                html += '<button type="button" class="button button-link-delete wpj-field__upload-remove" data-target="' + $input.attr('id') + '">Remove</button>';
                html += '</div>';

                $preview.html(html).addClass('has-file');
                $container.find('.wpj-field__upload-dropzone').hide();
            });

            frame.open();
        });

        // Remove Upload Handler
        $(document).on('click', '.wpj-field__upload-remove', function(e) {
            e.preventDefault();
            var targetId = $(this).data('target');
            var $container = $(this).closest('.wpj-field__upload-container');

            $('#' + targetId).val('').trigger('change');
            $container.find('.wpj-field__upload-preview').html('').removeClass('has-file');
            $container.find('.wpj-field__upload-dropzone').show();
        });

        // Copy Path Handler
        $(document).on('click', '.wpj-field__upload-copy-btn', function(e) {
            e.preventDefault();
            var targetId = $(this).data('target');
            var $input = $('#' + targetId);
            $input.select();
            document.execCommand('copy');

            var $btn = $(this);
            var originalText = $btn.text();
            $btn.text('Copied!');
            setTimeout(function() { $btn.text(originalText); }, 1500);
        });

        // Repeater Add Handler
        $(document).on('click', '.wpj-field__repeater-add', function(e) {
            e.preventDefault();
            var templateId = $(this).data('template');
            var $repeater = $(this).closest('.wpj-field__repeater');
            var $template = $('#' + templateId + '-template');

            if ($template.length) {
                var currentItems = $repeater.find('.wpj-field__repeater-item').length;
                var newIndex = currentItems;
                var html = $template.html()
                    .replace(/\{\{INDEX\}\}/g, newIndex)
                    .replace(/\{\{NUMBER\}\}/g, (newIndex + 1));

                $repeater.find('.wpj-field__repeater-empty').remove();

                // Collapse all existing items
                $repeater.find('.wpj-field__repeater-item').removeClass('wpj-field__repeater-item--open');

                // Add new item (open by default)
                var $newItem = $(html);
                $newItem.addClass('wpj-field__repeater-item--open');
                $newItem.insertBefore($(this));
            }
        });

        // Accordion Toggle Handler
        $(document).on('click', '.wpj-field__repeater-item-header', function(e) {
            // Don't toggle if clicking on remove button
            if ($(e.target).closest('.wpj-field__repeater-remove').length) {
                return;
            }

            var $item = $(this).closest('.wpj-field__repeater-item');
            var $repeater = $item.closest('.wpj-field__repeater');

            // Close all other items in this repeater
            $repeater.find('.wpj-field__repeater-item').not($item).removeClass('wpj-field__repeater-item--open');

            // Toggle current item
            $item.toggleClass('wpj-field__repeater-item--open');
        });

        // Repeater Remove Handler
        $(document).on('click', '.wpj-field__repeater-remove', function(e) {
            e.preventDefault();
            var $item = $(this).closest('.wpj-field__repeater-item');
            var $repeater = $item.closest('.wpj-field__repeater');

            $item.slideUp(200, function() {
                $(this).remove();
                // Re-number remaining items
                $repeater.find('.wpj-field__repeater-item').each(function(index) {
                    $(this).find('.item-number').text(index + 1);
                });
            });
        });

        // File List Handler
        $(document).on('click', '.wpj-field__file-list-add', function(e) {
            e.preventDefault();
            var $container = $(this).closest('.wpj-field__file-list-container');
            var fieldId = $container.data('field-id');
            var $list = $container.find('.wpj-field__file-list');

            var frame = wp.media({
                title: 'Select Files',
                button: { text: 'Add to Gallery' },
                multiple: true
            });

            frame.on('select', function() {
                var attachments = frame.state().get('selection').toJSON();
                attachments.forEach(function(att) {
                    var isImage = att.type === 'image';
                    var html = '<li class="wpj-field__file-list-item" data-id="' + att.id + '">';
                    html += '<input type="hidden" name="' + fieldId + '[' + att.id + ']" value="' + att.url + '" />';
                    if (isImage) {
                        html += '<img src="' + (att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url) + '" class="wpj-field__file-list-image" />';
                    } else {
                        html += '<span class="wpj-field__file-list-icon dashicons dashicons-media-default"></span>';
                    }
                    html += '<span class="wpj-field__file-list-name">' + att.filename + '</span>';
                    html += '<button type="button" class="button button-link-delete wpj-field__file-list-remove">Remove</button>';
                    html += '</li>';
                    $list.append(html);
                });
            });

            frame.open();
        });

        $(document).on('click', '.wpj-field__file-list-remove', function(e) {
            e.preventDefault();
            $(this).closest('.wpj-field__file-list-item').remove();
        });

        // CMB2-Style Conditional Logic
        function wpjConditionalLogic() {
            $('[data-conditional-id]').each(function() {
                var $field = $(this).closest('.wpj-field');
                var condId = $(this).data('conditional-id');
                var condValue = $(this).data('conditional-value');
                var invert = $(this).data('conditional-invert') === true;

                // Parse condValue if it's a JSON array string
                if (typeof condValue === 'string' && condValue.charAt(0) === '[') {
                    try { condValue = JSON.parse(condValue); } catch(e) {}
                }

                function checkCondition() {
                    var $target = $('[name="' + condId + '"], #' + condId);
                    var targetVal = '';

                    if ($target.is(':checkbox')) {
                        targetVal = $target.is(':checked');
                    } else if ($target.is(':radio')) {
                        targetVal = $('[name="' + condId + '"]:checked').val() || '';
                    } else {
                        targetVal = $target.val() || '';
                    }

                    var matches = false;
                    if (Array.isArray(condValue)) {
                        matches = condValue.indexOf(targetVal) !== -1;
                    } else if (typeof condValue === 'boolean') {
                        matches = targetVal === condValue;
                    } else {
                        matches = targetVal == condValue;
                    }

                    if (invert) matches = !matches;

                    if (matches) {
                        $field.removeClass('field_is_hidden');
                    } else {
                        $field.addClass('field_is_hidden');
                    }
                }

                checkCondition();
                $('[name="' + condId + '"], #' + condId).on('change', checkCondition);
            });
        }

        wpjConditionalLogic();

        // Re-init conditional logic when repeater rows are added
        $(document).on('click', '.wpj-field__repeater-add', function() {
            setTimeout(wpjConditionalLogic, 100);
        });

        // Conditional field visibility (legacy data-show-on/data-hide-on)
        $('[data-show-on], [data-hide-on]').each(function() {
            var $field = $(this);
            var showOn = $field.data('show-on');
            var hideOn = $field.data('hide-on');

            function updateVisibility() {
                var show = true;

                if (showOn) {
                    $.each(showOn, function(fieldId, value) {
                        var $target = $('#' + fieldId);
                        var targetVal = $target.is(':checkbox') ? $target.is(':checked') : $target.val();
                        if (Array.isArray(value)) {
                            show = show && value.indexOf(targetVal) !== -1;
                        } else {
                            show = show && targetVal == value;
                        }
                    });
                }

                if (hideOn) {
                    $.each(hideOn, function(fieldId, value) {
                        var $target = $('#' + fieldId);
                        var targetVal = $target.is(':checkbox') ? $target.is(':checked') : $target.val();
                        if (Array.isArray(value)) {
                            show = show && value.indexOf(targetVal) === -1;
                        } else {
                            show = show && targetVal != value;
                        }
                    });
                }

                $field.toggle(show);
            }

            updateVisibility();

            var allFieldIds = [];
            if (showOn) allFieldIds = allFieldIds.concat(Object.keys(showOn));
            if (hideOn) allFieldIds = allFieldIds.concat(Object.keys(hideOn));

            $.each(allFieldIds, function(i, fieldId) {
                $('#' + fieldId).on('change', updateVisibility);
            });
        });
    });
})(jQuery);
JS;
	}

	/**
	 * Register General field types.
	 *
	 * @param Registry $registry The field registry instance.
	 *
	 * @return void
	 */
	private function registerGeneralFields( Registry $registry ): void {
			$registry->register( 'text', \WPJarvis\Framework\WP\Fields\Types\General\TextField::class );
			$registry->register( 'small_text', \WPJarvis\Framework\WP\Fields\Types\General\SmallTextField::class );
			$registry->register( 'text_small', \WPJarvis\Framework\WP\Fields\Types\General\SmallTextField::class );
			$registry->register( 'text_medium', \WPJarvis\Framework\WP\Fields\Types\General\MediumTextField::class );
			$registry->register( 'text_money', \WPJarvis\Framework\WP\Fields\Types\General\MoneyField::class );
			$registry->register( 'textarea', \WPJarvis\Framework\WP\Fields\Types\General\TextareaField::class );
			$registry->register( 'textarea_small', \WPJarvis\Framework\WP\Fields\Types\General\SmallTextareaField::class );
			$registry->register( 'textarea_code', \WPJarvis\Framework\WP\Fields\Types\General\CodeField::class );
			$registry->register( 'code', \WPJarvis\Framework\WP\Fields\Types\General\CodeField::class );
			$registry->register( 'number', \WPJarvis\Framework\WP\Fields\Types\General\NumberField::class );
			$registry->register( 'url', \WPJarvis\Framework\WP\Fields\Types\General\UrlField::class );
			$registry->register( 'text_url', \WPJarvis\Framework\WP\Fields\Types\General\UrlField::class );
			$registry->register( 'email', \WPJarvis\Framework\WP\Fields\Types\General\EmailField::class );
			$registry->register( 'text_email', \WPJarvis\Framework\WP\Fields\Types\General\EmailField::class );
			$registry->register( 'hidden', \WPJarvis\Framework\WP\Fields\Types\General\HiddenField::class );
			$registry->register( 'select', \WPJarvis\Framework\WP\Fields\Types\General\SelectField::class );
			$registry->register( 'multi_select', \WPJarvis\Framework\WP\Fields\Types\General\MultiSelectField::class );
			$registry->register( 'checkbox', \WPJarvis\Framework\WP\Fields\Types\General\CheckboxField::class );
			$registry->register( 'multicheck', \WPJarvis\Framework\WP\Fields\Types\General\MultiCheckField::class );
			$registry->register( 'multicheck_inline', \WPJarvis\Framework\WP\Fields\Types\General\MultiCheckInlineField::class );
			$registry->register( 'radio', \WPJarvis\Framework\WP\Fields\Types\General\RadioField::class );
			$registry->register( 'radio_inline', \WPJarvis\Framework\WP\Fields\Types\General\RadioInlineField::class );
			$registry->register( 'date', \WPJarvis\Framework\WP\Fields\Types\General\DateField::class );
			$registry->register( 'text_date', \WPJarvis\Framework\WP\Fields\Types\General\DateField::class );
			$registry->register( 'time', \WPJarvis\Framework\WP\Fields\Types\General\TimeField::class );
			$registry->register( 'text_time', \WPJarvis\Framework\WP\Fields\Types\General\TimeField::class );
	}

	/**
	 * Register WordPress-specific field types.
	 *
	 * @param Registry $registry The field registry instance.
	 *
	 * @return void
	 */
	private function registerWordPressFields( Registry $registry ): void {
		$registry->register( 'taxonomy_select', \WPJarvis\Framework\WP\Fields\Types\WordPress\TaxonomySelectField::class );
		$registry->register( 'taxonomy_radio', \WPJarvis\Framework\WP\Fields\Types\WordPress\TaxonomyRadioField::class );
		$registry->register( 'taxonomy_radio_inline', \WPJarvis\Framework\WP\Fields\Types\WordPress\TaxonomyRadioInlineField::class );
		$registry->register( 'taxonomy_multicheck', \WPJarvis\Framework\WP\Fields\Types\WordPress\TaxonomyMultiCheckField::class );
		$registry->register( 'taxonomy_multicheck_inline', \WPJarvis\Framework\WP\Fields\Types\WordPress\TaxonomyMultiCheckInlineField::class );
		$registry->register( 'post_select', \WPJarvis\Framework\WP\Fields\Types\WordPress\PostSelectField::class );
		$registry->register( 'media_upload', \WPJarvis\Framework\WP\Fields\Types\WordPress\MediaUploadField::class );
		$registry->register( 'file', \WPJarvis\Framework\WP\Fields\Types\WordPress\MediaUploadField::class );
		$registry->register( 'file_upload', \WPJarvis\Framework\WP\Fields\Types\WordPress\FileUploadField::class );
		$registry->register( 'image_upload', \WPJarvis\Framework\WP\Fields\Types\WordPress\ImageUploadField::class );
		$registry->register( 'file_list', \WPJarvis\Framework\WP\Fields\Types\WordPress\FileListField::class );
	}

	/**
	 * Register Utility/Advanced field types.
	 *
	 * @param Registry $registry The field registry instance.
	 *
	 * @return void
	 */
	private function registerUtilityFields( Registry $registry ): void {
		$registry->register( 'title', \WPJarvis\Framework\WP\Fields\Types\Utility\TitleField::class );
		$registry->register( 'group', \WPJarvis\Framework\WP\Fields\Types\Utility\GroupField::class );
		$registry->register( 'wysiwyg', \WPJarvis\Framework\WP\Fields\Types\Utility\WysiwygField::class );
		$registry->register( 'colorpicker', \WPJarvis\Framework\WP\Fields\Types\Utility\ColorPickerField::class );
		$registry->register( 'color_picker', \WPJarvis\Framework\WP\Fields\Types\Utility\ColorPickerField::class );
		$registry->register( 'location', \WPJarvis\Framework\WP\Fields\Types\Utility\LocationField::class );
		$registry->register( 'country', \WPJarvis\Framework\WP\Fields\Types\Utility\CountryField::class );
		$registry->register( 'select_timezone', \WPJarvis\Framework\WP\Fields\Types\Utility\TimezoneField::class );
		$registry->register( 'timezone', \WPJarvis\Framework\WP\Fields\Types\Utility\TimezoneField::class );
		$registry->register( 'oembed', \WPJarvis\Framework\WP\Fields\Types\Utility\OembedField::class );
	}

	/**
	 * Register oEmbed AJAX handler.
	 *
	 * @return void
	 */
	private function registerOembedAjax(): void {
		// Register AJAX action early
		Hooks::action( 'wp_ajax_wpj_oembed_fetch', function () {
			// Verify nonce - use wp_verify_nonce for graceful handling
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

			if ( ! wp_verify_nonce( $nonce, 'wpj_oembed_nonce' ) ) {
				wp_send_json_error( [ 'message' => __( 'Security check failed. Please refresh the page.', 'wp-jarvis' ) ] );

				return;
			}

			$url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';

			if ( empty( $url ) ) {
				wp_send_json_error( [ 'message' => __( 'Please enter a valid URL.', 'wp-jarvis' ) ] );

				return;
			}

			// Get oEmbed
			$embed = wp_oembed_get( $url, [ 'width' => 500 ] );

			if ( empty( $embed ) ) {
				wp_send_json_error( [ 'message' => __( 'No embed available for this URL. Make sure it\'s a valid embeddable URL (e.g., https://www.youtube.com/watch?v=...).', 'wp-jarvis' ) ] );

				return;
			}

			wp_send_json_success( [ 'embed' => $embed ] );
		} );
	}
}
