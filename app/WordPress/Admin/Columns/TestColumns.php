<?php

declare( strict_types=1 );

namespace BraCalculator\App\WordPress\Admin\Columns;

use WPJarvis\Framework\Support\Facades\Hooks;
use WPJarvis\Framework\WP\Admin\Columns as ColumnsBuilder;

/**
 * TestColumns Admin Columns
 *
 * Manages admin list table columns for test_article.
 *
 * @package BraCalculator\App\WordPress\Admin\Columns
 */
class TestColumns {
    /**
     * The post-type.
     */
    public const POST_TYPE = 'test_article';

    /**
     * Register the columns.
     */
    public function register(): void {
        ColumnsBuilder::for( self::POST_TYPE )
                // Add a thumbnail column after the checkbox
                      ->after( 'cb', 'thumbnail', __( 'Image', 'bra-calculator' ), function ( int $postId ) {
                    if ( has_post_thumbnail( $postId ) ) {
                        echo get_the_post_thumbnail( $postId, [ 50, 50 ], [
                                'style' => 'border-radius: 4px;'
                        ] );
                    } else {
                        echo '<span class="dashicons dashicons-format-image" style="color:#ccc;font-size:32px;"></span>';
                    }
                } )

                // Add a custom meta column
                      ->after( 'title', 'custom_field', __( 'Custom Field', 'bra-calculator' ), function ( int $postId ) {
                    $value = get_post_meta( $postId, '_custom_field', true );
                    echo esc_html( $value ?: '—' );
                } )

                // Add a status column with a visual indicator
                      ->add( 'status', __( 'Status', 'bra-calculator' ), function ( int $postId ) {
                    $status       = get_post_meta( $postId, '_status', true );
                    $statusLabels = [
                            'active'   => [ 'label' => __( 'Active', 'bra-calculator' ), 'color' => '#00a32a' ],
                            'inactive' => [ 'label' => __( 'Inactive', 'bra-calculator' ), 'color' => '#d63638' ],
                            'pending'  => [ 'label' => __( 'Pending', 'bra-calculator' ), 'color' => '#dba617' ],
                    ];

                    $info = $statusLabels[ $status ] ?? [ 'label' => __( 'Unknown', 'bra-calculator' ), 'color' => '#666' ];
                    printf(
                            '<span style="display:inline-flex;align-items:center;gap:4px;">
                        <span style="width:8px;height:8px;border-radius:50%%;background:%s;"></span>
                        %s
                    </span>',
                            esc_attr( $info['color'] ),
                            esc_html( $info['label'] )
                    );
                } )

                // Add a featured indicator
                      ->boolean( 'featured', __( 'Featured', 'bra-calculator' ), '_featured', '★', '—' )

                // Add taxonomy column
                // ->taxonomy('categories', __('Categories', 'bra-calculator'), 'category')

                // Make columns sortable
                      ->sortable( 'custom_field', '_custom_field' )

                // Remove unwanted columns
                // ->remove('comments')
                // ->remove('author')

                      ->register();
    }

    /**
     * Add custom styles for the columns.
     */
    public function enqueueStyles(): void {
        $screen = get_current_screen();

        if ( $screen && $screen->id === 'edit-' . self::POST_TYPE ) {
            Hooks::action( 'admin_head', static function () {
                ?>
                <style>
                    .column-thumbnail {
                        width: 60px;
                    }

                    .column-featured {
                        width: 80px;
                        text-align: center;
                    }

                    .column-status {
                        width: 100px;
                    }

                    .column-custom_field {
                        width: 150px;
                    }
                </style>
                <?php
            } );
        }
    }

    /**
     * Register column styles hook.
     */
    public function registerStyles(): void {
        Hooks::action( 'current_screen', [ $this, 'enqueueStyles' ] );
    }
}
