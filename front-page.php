<?php
/**
 * Front Page – shows page content (builder-friendly)
 */
get_header();
if ( function_exists('amino_render_builder_template') && amino_render_builder_template() ) {
    // Builder template rendered
} else {
    amino_display_content();
}
get_footer();