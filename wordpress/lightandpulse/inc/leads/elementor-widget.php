<?php
/**
 * Elementor widget: Lightandpulse Lead Form.
 */
defined('ABSPATH') || exit;

class Lightandpulse_Lead_Form_Widget extends \Elementor\Widget_Base
{
    public function get_name(): string
    {
        return 'lightandpulse_lead_form';
    }

    public function get_title(): string
    {
        return __('Lightandpulse Lead Form', 'lightandpulse');
    }

    public function get_icon(): string
    {
        return 'eicon-form-horizontal';
    }

    public function get_categories(): array
    {
        return ['general'];
    }

    public function get_keywords(): array
    {
        return ['form', 'lead', 'contact', 'enquiry', 'whatsapp', 'lightandpulse'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section('content', ['label' => __('Form', 'lightandpulse')]);
        $this->add_control('button', [
            'label'   => __('Button text', 'lightandpulse'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('Send enquiry', 'lightandpulse'),
        ]);
        $this->add_control('services', [
            'label'        => __('Show “What do you need?” checkboxes', 'lightandpulse'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);
        $this->add_control('success', [
            'label'   => __('Thank-you message', 'lightandpulse'),
            'type'    => \Elementor\Controls_Manager::TEXTAREA,
            'default' => __('Thank you! We will reply on WhatsApp or email within one working day.', 'lightandpulse'),
        ]);
        $this->end_controls_section();

        $this->start_controls_section('style_button', ['label' => __('Button', 'lightandpulse'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('button_bg', [
            'label'     => __('Background', 'lightandpulse'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .lp-lead-form button' => 'background-color: {{VALUE}};'],
        ]);
        $this->add_control('button_color', [
            'label'     => __('Text colour', 'lightandpulse'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .lp-lead-form button' => 'color: {{VALUE}};'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'button_typography',
            'selector' => '{{WRAPPER}} .lp-lead-form button',
        ]);
        $this->end_controls_section();

        $this->start_controls_section('style_fields', ['label' => __('Fields', 'lightandpulse'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('label_color', [
            'label'     => __('Label colour', 'lightandpulse'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .lp-lead-form label, {{WRAPPER}} .lp-lead-form legend' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('field_radius', [
            'label'      => __('Field corner radius', 'lightandpulse'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'selectors'  => ['{{WRAPPER}} .lp-lead-form input, {{WRAPPER}} .lp-lead-form textarea, {{WRAPPER}} .lp-lead-form button' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void
    {
        $s = $this->get_settings_for_display();
        echo lpl_render_lead_form([ // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
            'button'   => $s['button'] ?: __('Send enquiry', 'lightandpulse'),
            'services' => ($s['services'] ?? '') === 'yes',
            'success'  => $s['success'] ?: __('Thank you! We will be in touch soon.', 'lightandpulse'),
        ]);
    }
}
