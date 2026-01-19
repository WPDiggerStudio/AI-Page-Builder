/**
 * Block: Feature Card
 *
 * Editor registration with editable attributes.
 * This file uses vanilla JavaScript (no build required).
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 * @package BraCalculator\App\WordPress\Blocks
 */
(function (wp) {
    const {registerBlockType} = wp.blocks;
    const {useBlockProps, InspectorControls, RichText} = wp.blockEditor;
    const {PanelBody, ToggleControl, TextControl} = wp.components;
    const {createElement: el, Fragment} = wp.element;

    registerBlockType("wpjarvis/feature-card", {
        /**
         * Edit function - editable controls for heading, description, showIcon.
         */
        edit: function (props) {
            const {attributes, setAttributes} = props;
            const {heading, description, showIcon} = attributes;
            const blockProps = useBlockProps({className: "feature-card"});

            return el(
                Fragment,
                {},
                // Inspector Controls (sidebar)
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        {title: "Feature Card Settings", initialOpen: true},
                        el(ToggleControl, {
                            label: "Show Icon",
                            checked: showIcon,
                            onChange: function (value) {
                                setAttributes({showIcon: value});
                            },
                        }),
                        el(TextControl, {
                            label: "Heading",
                            value: heading,
                            onChange: function (value) {
                                setAttributes({heading: value});
                            },
                        }),
                        el(TextControl, {
                            label: "Description",
                            value: description,
                            onChange: function (value) {
                                setAttributes({description: value});
                            },
                        }),
                    ),
                ),
                // Block Content
                el(
                    "div",
                    blockProps,
                    showIcon &&
                    el(
                        "div",
                        {className: "feature-card__icon"},
                        el("span", {className: "dashicons dashicons-star-filled"}),
                    ),
                    el(
                        "div",
                        {className: "feature-card__content"},
                        el(RichText, {
                            tagName: "h3",
                            className: "feature-card__heading",
                            value: heading,
                            onChange: function (value) {
                                setAttributes({heading: value});
                            },
                            placeholder: "Enter heading...",
                        }),
                        el(RichText, {
                            tagName: "p",
                            className: "feature-card__description",
                            value: description,
                            onChange: function (value) {
                                setAttributes({description: value});
                            },
                            placeholder: "Enter description...",
                        }),
                    ),
                ),
            );
        },

        /**
         * Save function - returns null for dynamic blocks.
         * The frontend rendering is handled by render.php.
         */
        save: function () {
            return null;
        },
    });
})(window.wp);
