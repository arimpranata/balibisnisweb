<?php
/**
 * Page builder for the starter content.
 *
 * Each page is described once as sections → columns → widgets. From that
 * description we produce (1) Elementor data, so the page is fully editable in
 * Elementor, and (2) matching fallback HTML for post_content, so the page
 * still looks right when Elementor is not active. The fallback reuses
 * Elementor's class names, so one stylesheet styles both.
 *
 * Widgets: ['heading', text, tag, class] · ['text', html, class] · ['button', text, url, class]
 *          ['card', icon, title, text, class] · ['html', html, class] · ['form'] · ['shortcode', code]
 */
defined('ABSPATH') || exit;

function lightandpulse_eid(): string
{
    return substr(bin2hex(random_bytes(4)), 0, 7);
}

/** Column sizes Elementor knows as classes; anything else uses _inline_size. */
function lightandpulse_col_class(float $size): int
{
    foreach ([100, 83, 80, 75, 70, 66, 60, 50, 40, 33, 30, 25, 20, 16] as $known) {
        if ($size >= $known) {
            return $known;
        }
    }
    return 16;
}

function lightandpulse_url(string $path): string
{
    return str_starts_with($path, 'http') || str_starts_with($path, '#') ? $path : home_url($path);
}

// --------------------------------------------------------------------- Elementor data
function lightandpulse_to_elementor(array $sections): array
{
    $out = [];
    foreach ($sections as $sec) {
        $cols = [];
        $sizes = $sec['sizes'] ?? array_fill(0, count($sec['cols']), round(100 / count($sec['cols']), 3));
        foreach ($sec['cols'] as $i => $widgets) {
            $size = (float) $sizes[$i];
            $colSettings = ['_column_size' => lightandpulse_col_class($size)];
            if (abs($size - lightandpulse_col_class($size)) > 0.5) {
                $colSettings['_inline_size'] = $size;
            }
            $cols[] = [
                'id' => lightandpulse_eid(), 'elType' => 'column', 'isInner' => false,
                'settings' => $colSettings,
                'elements' => array_map('lightandpulse_widget_elementor', $widgets),
            ];
        }
        $settings = ['css_classes' => $sec['class'] ?? '', 'gap' => 'extended'];
        if (!empty($sec['id'])) {
            $settings['_element_id'] = $sec['id'];
        }
        $out[] = ['id' => lightandpulse_eid(), 'elType' => 'section', 'isInner' => false, 'settings' => $settings, 'elements' => $cols];
    }
    return $out;
}

function lightandpulse_widget_elementor(array $w): array
{
    $type = $w[0];
    $settings = [];
    switch ($type) {
        case 'heading':
            $widgetType = 'heading';
            $settings = ['title' => $w[1], 'header_size' => $w[2] ?? 'h2', '_css_classes' => $w[3] ?? ''];
            break;
        case 'text':
            $widgetType = 'text-editor';
            $settings = ['editor' => $w[1], '_css_classes' => $w[2] ?? ''];
            break;
        case 'button':
            $widgetType = 'button';
            $settings = [
                'text' => $w[1], 'link' => ['url' => lightandpulse_url($w[2]), 'is_external' => '', 'nofollow' => ''],
                'size' => 'md', '_element_width' => 'auto', '_css_classes' => 'lp-btn ' . ($w[3] ?? 'lp-btn-primary'),
            ];
            break;
        case 'card':
            $widgetType = 'icon-box';
            $fa = LIGHTANDPULSE_FA[$w[1]] ?? 'fas fa-check';
            $settings = [
                'selected_icon' => ['value' => $fa, 'library' => str_starts_with($fa, 'fab') ? 'fa-brands' : (str_starts_with($fa, 'far') ? 'fa-regular' : 'fa-solid')],
                'title_text' => $w[2], 'description_text' => $w[3], 'title_size' => 'h3',
                '_css_classes' => 'lp-card ' . ($w[4] ?? ''),
            ];
            break;
        case 'form':
            $widgetType = 'lightandpulse_lead_form';
            $settings = ['button' => $w[1] ?? __('Send enquiry', 'lightandpulse'), 'services' => 'yes', 'success' => __('Thank you! We will reply on WhatsApp or email within one working day.', 'lightandpulse')];
            break;
        case 'shortcode':
            $widgetType = 'shortcode';
            $settings = ['shortcode' => $w[1]];
            break;
        default: // html
            $widgetType = 'html';
            $settings = ['html' => $w[1], '_css_classes' => $w[2] ?? ''];
    }
    return ['id' => lightandpulse_eid(), 'elType' => 'widget', 'widgetType' => $widgetType, 'settings' => $settings, 'elements' => []];
}

// --------------------------------------------------------------------- Fallback HTML
function lightandpulse_to_html(array $sections): string
{
    $html = '<div class="elementor lp-static">';
    foreach ($sections as $sec) {
        $sizes = $sec['sizes'] ?? array_fill(0, count($sec['cols']), round(100 / count($sec['cols']), 3));
        $html .= '<section' . (!empty($sec['id']) ? ' id="' . esc_attr($sec['id']) . '"' : '') . ' class="elementor-section elementor-top-section elementor-section-boxed ' . esc_attr($sec['class'] ?? '') . '"><div class="elementor-container elementor-column-gap-extended">';
        foreach ($sec['cols'] as $i => $widgets) {
            $size = (float) $sizes[$i];
            $html .= '<div class="elementor-column elementor-col-' . lightandpulse_col_class($size) . ' elementor-top-column" style="--lp-col:' . esc_attr((string) $size) . '%"><div class="elementor-widget-wrap elementor-element-populated">';
            foreach ($widgets as $w) {
                $html .= lightandpulse_widget_html($w);
            }
            $html .= '</div></div>';
        }
        $html .= '</div></section>';
    }
    return $html . '</div>';
}

function lightandpulse_widget_html(array $w): string
{
    $wrap = fn(string $type, string $class, string $inner, string $extra = '') => '<div class="elementor-element elementor-widget elementor-widget-' . $type . ' ' . esc_attr(trim($class . ' ' . $extra)) . '"><div class="elementor-widget-container">' . $inner . '</div></div>';
    switch ($w[0]) {
        case 'heading':
            $tag = $w[2] ?? 'h2';
            return $wrap('heading', $w[3] ?? '', "<$tag class=\"elementor-heading-title elementor-size-default\">" . wp_kses_post($w[1]) . "</$tag>");
        case 'text':
            return $wrap('text-editor', $w[2] ?? '', wp_kses_post($w[1]));
        case 'button':
            $a = '<div class="elementor-button-wrapper"><a class="elementor-button elementor-button-link elementor-size-md" href="' . esc_url(lightandpulse_url($w[2])) . '"><span class="elementor-button-content-wrapper"><span class="elementor-button-text">' . esc_html($w[1]) . '</span></span></a></div>';
            return $wrap('button', 'lp-btn ' . ($w[3] ?? 'lp-btn-primary'), $a, 'elementor-widget__width-auto');
        case 'card':
            $box = '<div class="elementor-icon-box-wrapper"><div class="elementor-icon-box-icon"><span class="elementor-icon">' . lightandpulse_icon($w[1]) . '</span></div><div class="elementor-icon-box-content"><h3 class="elementor-icon-box-title"><span>' . esc_html($w[2]) . '</span></h3><p class="elementor-icon-box-description">' . esc_html($w[3]) . '</p></div></div>';
            return $wrap('icon-box', 'lp-card ' . ($w[4] ?? ''), $box);
        case 'form':
            // A shortcode, not rendered HTML: the form's spam-check timestamp must be fresh on every view.
            return $wrap('shortcode', '', '[lightandpulse_lead_form button="' . esc_attr($w[1] ?? __('Send enquiry', 'lightandpulse')) . '"]');
        case 'shortcode':
            return $wrap('shortcode', '', $w[1]);
        default:
            return $wrap('html', $w[2] ?? '', $w[1]);
    }
}
