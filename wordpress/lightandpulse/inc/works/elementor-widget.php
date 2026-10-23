<?php
/**
 * Elementor widget: Lightandpulse Works (portfolio grid).
 */
defined('ABSPATH') || exit;

class Lightandpulse_Works_Widget extends \Elementor\Widget_Base
{
    public function get_name(): string
    {
        return 'lightandpulse_works';
    }

    public function get_title(): string
    {
        return __('Lightandpulse Works', 'lightandpulse');
    }

    public function get_icon(): string
    {
        return 'eicon-gallery-grid';
    }

    public function get_categories(): array
    {
        return ['general'];
    }

    public function get_keywords(): array
    {
        return ['portfolio', 'works', 'projects', 'lightandpulse'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section('content', ['label' => __('Works', 'lightandpulse')]);
        $this->add_control('limit', [
            'label' => __('Number of works (0 = all)', 'lightandpulse'), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 0, 'min' => 0,
        ]);
        $this->add_control('filter', [
            'label' => __('Show type filter', 'lightandpulse'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes',
        ]);
        $this->add_control('note', [
            'type' => \Elementor\Controls_Manager::RAW_HTML,
            'raw' => sprintf(__('Manage projects in <a href="%s" target="_blank">Works</a>.', 'lightandpulse'), esc_url(admin_url('edit.php?post_type=lp_work'))),
        ]);
        $this->end_controls_section();
    }

    protected function render(): void
    {
        $s = $this->get_settings_for_display();
        $limit = (int) ($s['limit'] ?? 0);
        echo lightandpulse_render_works(['limit' => $limit > 0 ? $limit : -1, 'filter' => ($s['filter'] ?? '') === 'yes']); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
    }
}
