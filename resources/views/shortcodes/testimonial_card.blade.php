@php
/**
 * TestimonialCard Shortcode View Template
 *
 * Available variables:
 * @var array<string, mixed> $atts Shortcode attributes
 * @var string|null $content Shortcode content
 *
 * @package BraCalculator\\App\WordPress\Shortcodes
 */
@endphp

<div @if(!empty($atts['id'])) id="{{ esc_attr($atts['id']) }}" @endif class="testimonial_card-shortcode {{ !empty($atts['class']) ? esc_attr($atts['class']) : '' }}">
    @if(!empty($atts['title']))
        <h3 class="testimonial_card-title">{{ esc_html($atts['title']) }}</h3>
    @endif

    @if($content)
        <div class="testimonial_card-content">
            {!! $content !!}
        </div>
    @endif

    {{-- Add your custom content here --}}
</div>
