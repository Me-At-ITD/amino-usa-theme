<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Remove LightBuilder styles panels from Customizer
 */
add_action('customize_register', function( $wp_customize ) {
    // Remove the LightBuilder Styles section
    $wp_customize->remove_section('lb_styles');

    // Remove the Global Styles section
    $wp_customize->remove_section('lb_global_styles');
}, 999); // High priority so it runs after sections are added
