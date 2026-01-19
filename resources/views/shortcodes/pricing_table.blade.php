@php
/**
 * PricingTable Shortcode View Template
 *
 * Available variables:
 * @var array<string, mixed> $atts Shortcode attributes
 * @var string|null $content Shortcode content
 *
 * @package BraCalculator\\App\WordPress\Shortcodes
 */
@endphp

<div @if(!empty($atts['id'])) id="{{ esc_attr($atts['id']) }}" @endif class="pricing_table-shortcode {{ !empty($atts['class']) ? esc_attr($atts['class']) : '' }}">
    @if(!empty($atts['title']))
        <h3 class="pricing_table-title">{{ esc_html($atts['title']) }}</h3>
    @endif

    @if($content)
        <div class="pricing_table-content">
            {!! $content !!}
        </div>
    @endif

    {{-- Add your custom content here --}}
</div>
