<?php
/**
 * Page template
 */
get_header();
if ( function_exists('amino_render_builder_template') && amino_render_builder_template() ) {
    // Builder template rendered
} else {
    amino_display_content();
}
get_footer();