/**
 * Block: Hero Section
 *
 * Editor registration for dynamic (PHP-rendered) blocks.
 * This file uses vanilla JavaScript (no build required).
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 * @package BraCalculator\App\WordPress\Blocks
 */
(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { useBlockProps } = wp.blockEditor;
    const { createElement: el } = wp.element;

    registerBlockType('bra-calculator/hero-section', {
        /**
         * Edit function - shows a placeholder in the editor.
         * The actual rendering is handled by PHP (render.php).
         */
        edit: function() {
            const blockProps = useBlockProps();

            return el('div', blockProps,
                el('p', {}, 'Hero Section')
            );
        },

        /**
         * Save function - returns null for dynamic blocks.
         * The frontend rendering is handled by render.php.
         */
        save: function() {
            return null;
        }
    });
})(window.wp);
