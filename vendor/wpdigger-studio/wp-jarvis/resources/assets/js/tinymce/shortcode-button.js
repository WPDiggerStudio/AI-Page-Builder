/**
 * WP-Jarvis TinyMCE Shortcode Button Plugin
 * With Field System Integration via AJAX Modal
 */
(function () {
    'use strict';

    // Safety check for TinyMCE
    if (typeof tinymce === 'undefined') {
        return;
    }

    var config = window.wpJarvisShortcodeConfig || {};
    var buttongId = config.buttonId || 'wpjarvis';

    tinymce.PluginManager.add(buttongId, function (editor, url) {
        var shortcodes = config.shortcodes || {};
        var i18n = config.i18n || {};
        var useModal = config.useModal || false;

        /**
         * Create a closure to capture the correct shortcode data for the loop.
         */
        function createMenuOnClick(shortcodeData) {
            return function () {
                if (useModal) {
                    openModalDialog(shortcodeData);
                } else {
                    openTinyMCEDialog(shortcodeData);
                }
            };
        }

        // Build menu items
        var menuItems = [];
        Object.keys(shortcodes).forEach(function (tag) {
            var shortcodeData = shortcodes[tag];
            shortcodeData.tag = tag;

            menuItems.push({
                text: shortcodeData.title || tag,
                onclick: createMenuOnClick(shortcodeData)
            });
        });

        var btnSettings = {
            title: config.title || 'Insert Shortcode',
            type: menuItems.length > 1 ? 'menubutton' : 'button',
            menu: menuItems.length > 1 ? menuItems : undefined,
            onclick: menuItems.length === 1 ? createMenuOnClick(shortcodes[Object.keys(shortcodes)[0]]) : undefined
        };

        // Use 'image' for SVG data, or 'icon' for the font class
        if (config.icon) {
            btnSettings.image = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(config.icon);
        } else {
            btnSettings.icon = 'code';
        }

        editor.addButton(buttongId || 'wpjarvis_shortcodes', btnSettings);

        /**
         * Open a WordPress-style modal dialog with AJAX-loaded fields
         */
        function openModalDialog(shortcode) {
            // Create a modal overlay
            var overlay = document.createElement('div');
            overlay.className = 'wpj-shortcode-modal-overlay';
            overlay.innerHTML = '<div class="wpj-shortcode-modal">' +
                '<div class="wpj-shortcode-modal-header">' +
                '<h2>' + (shortcode.title || 'Insert Shortcode') + '</h2>' +
                '<button type="button" class="wpj-shortcode-modal-close">&times;</button>' +
                '</div>' +
                '<div class="wpj-shortcode-modal-body">' +
                '<div class="wpj-shortcode-loading">' + (i18n.loading || 'Loading...') + '</div>' +
                '</div>' +
                '</div>';

            document.body.appendChild(overlay);

            // Close button handler
            overlay.querySelector('.wpj-shortcode-modal-close').addEventListener('click', function () {
                document.body.removeChild(overlay);
            });

            // Close on overlay click
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) {
                    document.body.removeChild(overlay);
                }
            });

            // Close on the Escape key
            function escHandler(e) {
                if (e.key === 'Escape') {
                    document.body.removeChild(overlay);
                    document.removeEventListener('keydown', escHandler);
                }
            }

            document.addEventListener('keydown', escHandler);

            // Load dialog content via AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('POST', config.ajaxUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    var modalBody = overlay.querySelector('.wpj-shortcode-modal-body');

                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success && response.data.html) {
                                modalBody.innerHTML = response.data.html;

                                // Bind form submit
                                bindFormSubmit(overlay, shortcode, editor);
                            } else {
                                modalBody.innerHTML = '<p class="wpj-error">' + (response.data.message || 'Error loading dialog.') + '</p>';
                            }
                        } catch (e) {
                            modalBody.innerHTML = '<p class="wpj-error">Error parsing response.</p>';
                        }
                    } else {
                        modalBody.innerHTML = '<p class="wpj-error">Request failed.</p>';
                    }
                }
            };

            xhr.send('action=wpj_shortcode_dialog&shortcode=' + encodeURIComponent(shortcode.tag) + '&nonce=' + encodeURIComponent(config.nonce));
        }

        /**
         * Bind form submit handler
         */
        function bindFormSubmit(overlay, shortcode, editor) {
            var form = overlay.querySelector('.wpj-shortcode-form');
            if (!form) return;

            // Insert button
            var insertBtn = form.querySelector('.wpj-shortcode-insert');
            var cancelBtn = form.querySelector('.wpj-shortcode-cancel');

            if (insertBtn) {
                insertBtn.addEventListener('click', function (e) {
                    e.preventDefault();

                    var formData = {};
                    var inputs = form.querySelectorAll('input, select, textarea');

                    inputs.forEach(function (input) {
                        var name = input.name;
                        if (!name) return;

                        // Use data-field-id if available, otherwise extract from name
                        var fieldId = input.dataset.fieldId;
                        if (!fieldId) {
                            // Extract field ID by removing the prefix (wpj_sc_TAG_)
                            // The prefix format is: wpj_sc_{shortcode_tag}_{field_id}
                            var prefix = 'wpj_sc_' + shortcode.tag + '_';
                            if (name.indexOf(prefix) === 0) {
                                fieldId = name.substring(prefix.length);
                            } else {
                                fieldId = name;
                            }
                        }

                        if (input.type === 'checkbox') {
                            formData[fieldId] = input.checked;
                        } else if (input.type === 'radio') {
                            if (input.checked) {
                                formData[fieldId] = input.value;
                            }
                        } else {
                            formData[fieldId] = input.value;
                        }
                    });

                    var shortcodeString = buildShortcode(shortcode.tag, formData, shortcode.allowContent);
                    editor.insertContent(shortcodeString);

                    // Close modal
                    document.body.removeChild(overlay);
                });
            }

            if (cancelBtn) {
                cancelBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.body.removeChild(overlay);
                });
            }
        }

        /**
         * Open TinyMCE native dialog (fallback)
         */
        function openTinyMCEDialog(shortcode) {
            var body = [];
            var attributes = shortcode.attributes || {};

            Object.keys(attributes).forEach(function (name) {
                var attr = attributes[name];
                var field = {
                    name: name,
                    label: attr.description || name,
                    value: (attr.default !== undefined) ? attr.default : ''
                };

                switch (attr.type) {
                    case 'textarea':
                    case 'html':
                        field.type = 'textbox';
                        field.multiline = true;
                        field.minHeight = 100;
                        break;

                    case 'boolean':
                    case 'bool':
                        field.type = 'checkbox';
                        field.checked = (attr.default === true || attr.default === 'true' || attr.default === 1);
                        break;

                    case 'select':
                        field.type = 'listbox';
                        field.values = (attr.options || []).map(function (opt) {
                            if (typeof opt === 'object') {
                                return {text: opt.label || opt.value, value: opt.value};
                            }
                            return {text: opt, value: opt};
                        });
                        break;

                    case 'color':
                        field.type = 'textbox';
                        field.classes = 'color-picker';
                        break;

                    default:
                        field.type = 'textbox';
                }

                body.push(field);
            });

            if (shortcode.allowContent) {
                body.push({
                    name: '_content',
                    label: i18n.content || 'Content',
                    type: 'textbox',
                    multiline: true,
                    minHeight: 100,
                    value: ''
                });
            }

            editor.windowManager.open({
                title: shortcode.title || 'Insert Shortcode',
                body: body,
                onsubmit: function (e) {
                    var shortcodeString = buildShortcode(shortcode.tag, e.data, shortcode.allowContent);
                    editor.insertContent(shortcodeString);
                }
            });
        }

        /**
         * Build a shortcode string from form data
         */
        function buildShortcode(tag, data, allowContent) {
            var attrs = [];
            var content = '';

            Object.keys(data).forEach(function (key) {
                var value = data[key];

                if (key === '_content') {
                    content = value;
                    return;
                }

                // Skip empty values
                if (value === '' || value === null || value === undefined) {
                    return;
                }

                // Handle boolean
                if (value === true || value === 'true') {
                    attrs.push(key + '="true"');
                    return;
                }
                if (value === false) {
                    return;
                }

                // Escape quotes
                var safeValue = String(value).replace(/"/g, "'");
                attrs.push(key + '="' + safeValue + '"');
            });

            var output = '[' + tag;
            if (attrs.length > 0) {
                output += ' ' + attrs.join(' ');
            }
            output += ']';

            if (allowContent) {
                output += content + '[/' + tag + ']';
            }

            return output;
        }
    });
})();