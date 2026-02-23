<?php

/**

 * Lightweight Builder for WordPress

 * Custom theme with lightweight page builder functionality

 * 

 * @package MyLightBuilder

 */



// Security check to prevent direct access

if (!defined('ABSPATH')) exit;



// =============================================

// ADMIN INTERFACE SETUP

// =============================================



/**

 * Add admin menu page for the Light Builder

 */

add_action('admin_menu', function() {

    add_theme_page(

        __('Light Builder', 'my-lightbuilder'), 

        __('Light Builder', 'my-lightbuilder'), 

        'edit_pages', 

        'lightbuilder', 

        'my_lb_builder_screen'

    );

});



/**

 * Render the builder admin interface

 */

function my_lb_builder_screen() {

    // Check user capabilities

    if (!current_user_can('edit_pages')) return;

    

    // Create nonce for security

    $nonce = wp_create_nonce('lb_nonce');

    ?>

    

    <div class="wrap">

        <h1><?php _e('Light Builder','my-lightbuilder'); ?></h1>

        <p><?php _e('Add sections, then click the ✎ pencil to edit text. For buttons/links, you can also set the URL.','my-lightbuilder'); ?></p>

        <div id="lb-app" data-nonce="<?php echo esc_attr($nonce); ?>">

            <aside class="lb-panel">

                <strong><?php _e('Custom Sections','my-lightbuilder'); ?></strong>

                <button class="button lb-add" data-type="heading"><?php _e('Heading Section','my-lightbuilder'); ?></button>

                <button class="button lb-add" data-type="hero"><?php _e('Hero Section','my-lightbuilder'); ?></button>

                <button class="button lb-add" data-type="content-image-split"><?php _e('Content + Image Split','my-lightbuilder'); ?></button>
                <button class="button lb-add" data-type="features3"><?php _e('3 Feature Highlights','my-lightbuilder'); ?></button>

                <button class="button lb-add" data-type="categorygrid"><?php _e('Category Grid','my-lightbuilder'); ?></button>

                <button class="button lb-add" data-type="faqs"><?php _e('FAQs Accordion','my-lightbuilder'); ?></button>

                <button class="button lb-add" data-type="productgrid"><?php _e('Product Grid','my-lightbuilder'); ?></button>

                <button class="button lb-add" data-type="newlaunchgrid"><?php _e('New Launch Products','my-lightbuilder'); ?></button>

                <button class="button lb-add" data-type="statbadge"><?php _e('Badge/Announcement','my-lightbuilder'); ?></button>

                <button class="button lb-add" data-type="milestones"><?php _e('Milestones Counter','my-lightbuilder'); ?></button>

                <button class="button lb-add" data-type="newsletter"><?php _e('Newsletter Signup','my-lightbuilder'); ?></button>

                <button class="button lb-add" data-type="video"><?php _e('Video Section','my-lightbuilder'); ?></button>

                <button class="button lb-add" data-type="bg-video"><?php _e('Background Video Section','my-lightbuilder'); ?></button>
                <button class="button lb-add" data-type="bg-image-section"><?php _e('Background Image Section','my-lightbuilder'); ?></button>

                <button class="button lb-add" data-type="contactform">Contact Form Section</button>

                <button class="button lb-add" data-type="combinedpostswidget"><?php _e('Blog Section','my-lightbuilder'); ?></button>

                <button class="button lb-add" data-type="postswidget"><?php _e('Posts Grid','my-lightbuilder'); ?></button>

                <button class="button lb-add" data-type="featuredpostswidget"><?php _e('Featured Posts','my-lightbuilder'); ?></button>
                



                <hr>

                <input type="text" id="lb-template-name" style="width:100%;" class="regular-text" placeholder="<?php esc_attr_e('Template name','my-lightbuilder'); ?>">

                <button id="lb-save" class="button button-primary"><?php _e('Save Template','my-lightbuilder'); ?></button>

                <hr>

                <select id="lb-templates" style="width:100%"></select>

                <button id="lb-load" class="button"><?php _e('Load','my-lightbuilder'); ?></button>

                <button id="lb-delete" class="button"><?php _e('Delete','my-lightbuilder'); ?></button>

            </aside>

            <section id="lb-canvas" class="lb-canvas" contenteditable="false"></section>

        </div>

    </div>



    <!-- Popup Editor Modal -->

<div id="lb-editor-modal" style="display:none;">

    <div class="lb-modal-backdrop"></div>

    <div class="lb-modal" style="max-width: 800px;">

        <div class="lb-modal-header">

            <h3><?php _e('Edit Content','my-lightbuilder'); ?></h3>

            <button type="button" class="button lb-modal-close" aria-label="<?php esc_attr_e('Close','my-lightbuilder'); ?>">×</button>

        </div>

        <div class="lb-modal-body">

            <form id="lb-editor-form">

                <!-- Single rich text field -->

                <div class="lb-field" id="lb-richtext-field">

                    <label><?php _e('Content','my-lightbuilder'); ?></label>

                    <textarea id="lb-richtext-editor" name="richtext" rows="10"></textarea>

                </div>

                

                <!-- Link field (only for buttons/links) -->

                <div class="lb-field" id="lb-link-field" style="display:none;">

                    <label><?php _e('Link URL','my-lightbuilder'); ?></label>

                    <input type="url" name="link_url" placeholder="https://">

                </div>

                

                <!-- Image field -->

                <div class="lb-field" id="lb-image-field" style="display:none;">

                    <label><?php _e('Image URL','my-lightbuilder'); ?></label>

                    <input type="text" name="image_url" placeholder="https://">

                    <button class="button lb-upload" data-target="image_url"><?php _e('Select Image','my-lightbuilder'); ?></button>

                    <div id="lb-image-preview" style="margin-top: 10px; display: none;">

                        <img src="" style="max-width: 100%; height: auto; border: 1px solid #ddd;">

                    </div>

                </div>

                

                <!-- Background image field -->

                <div class="lb-field" id="lb-bg-field" style="display:none;">

                    <label><?php _e('Background Image URL','my-lightbuilder'); ?></label>

                    <input type="text" name="bg_url" placeholder="https://">

                    <button class="button lb-upload" data-target="bg_url"><?php _e('Select Image','my-lightbuilder'); ?></button>

                    <div id="lb-bg-preview" style="margin-top: 10px; display: none; height: 100px; background-size: cover; background-position: center; border: 1px solid #ddd;"></div>

                </div>

                

                <!-- Video fields -->

                <div class="lb-field" id="lb-video-field" style="display:none;">

                    <label><?php _e('Video URL','my-lightbuilder'); ?></label>

                    <input type="text" name="video_url" placeholder="https://">

                </div>

                

                <div class="lb-field" id="lb-bg-video-field" style="display:none;">

                    <label><?php _e('Background Video URL','my-lightbuilder'); ?></label>

                    <input type="text" name="bg_video_url" placeholder="https://">

                </div>

                

                <!-- Class & ID field -->

                <div class="lb-field" id="lb-classid-field">

                    <label><?php _e('CSS Class','my-lightbuilder'); ?></label>

                    <input type="text" name="custom_class" placeholder="my-class">

                    <label><?php _e('CSS ID','my-lightbuilder'); ?></label>

                    <input type="text" name="custom_id" placeholder="my-id">

                </div>

            </form>

        </div>

        <div class="lb-modal-footer">

            <button type="button" class="button button-primary" id="lb-editor-save"><?php _e('Save','my-lightbuilder'); ?></button>

            <button type="button" class="button lb-modal-close"><?php _e('Cancel','my-lightbuilder'); ?></button>

        </div>

    </div>

</div>

    <?php

}



// =============================================

// ADMIN ASSETS ENQUEUE

// =============================================



/**

 * Enqueue admin scripts and styles

 */

add_action('admin_enqueue_scripts', function($hook) {

    // Only load on our builder page

    if ($hook !== 'appearance_page_lightbuilder') return;

    

    // Enqueue styles

    wp_enqueue_style('lb-builder', get_template_directory_uri() . '/builder/assets/css/builder.css', [], '1.0.1');

    

    // Enqueue scripts

    wp_enqueue_script('lb-builder', get_template_directory_uri() . '/builder/assets/js/builder.js', ['jquery'], '1.0.1', true);

    

    // Enable media uploader

    wp_enqueue_media();

    

    // Localize script with AJAX URL and nonce

    wp_localize_script('lb-builder', 'LB', [

        'ajax' => admin_url('admin-ajax.php'),

        'nonce' => wp_create_nonce('lb_nonce')

    ]);

});



// =============================================

// CUSTOM POST TYPE FOR TEMPLATES

// =============================================



/**

 * Register custom post type for storing templates

 */

add_action('init', function() {

    register_post_type('lb_template', [

        'label' => __('Builder Templates', 'my-lightbuilder'),

        'public' => false,

        'show_ui' => true,

        'show_in_menu' => true,

        'capability_type' => 'post',

        'map_meta_cap' => true,

        'supports' => ['title'], // minimal support

        'menu_icon' => 'dashicons-layout',

    ]);

});





// =============================================

// AJAX HANDLERS

// =============================================



/**

 * AJAX: List all available templates

 */

add_action('wp_ajax_lb_list_templates', function() {

    check_ajax_referer('lb_nonce', 'nonce');

    

    $posts = get_posts([

        'post_type' => 'lb_template',

        'numberposts' => -1,

        'post_status' => 'publish',

        'orderby' => 'date',

        'order' => 'DESC'

    ]);

    

    $out = array_map(function($p) { 

        return ['id' => $p->ID, 'title' => $p->post_title]; 

    }, $posts);

    

    wp_send_json_success($out);

});



/**

 * AJAX: Save template (create or update)

 */

add_action('wp_ajax_lb_save_template', function() {

    check_ajax_referer('lb_nonce', 'nonce');

    

    $name = sanitize_text_field($_POST['name'] ?? '');

    $content = wp_kses_post($_POST['content'] ?? '');

    $maybe_id = isset($_POST['id']) ? absint($_POST['id']) : 0;



    if (empty($name) || empty($content)) {

        wp_send_json_error('Missing fields');

    }



    // Update existing template

    if ($maybe_id && get_post_type($maybe_id) === 'lb_template') {

        $update_result = wp_update_post([

            'ID' => $maybe_id,

            'post_title' => $name,

            'post_content' => $content,

        ], true);

        

        if (is_wp_error($update_result)) {

            wp_send_json_error($update_result->get_error_message());

        }

        

        wp_send_json_success(['id' => $maybe_id, 'message' => __('Template updated', 'my-lightbuilder')]);

    }



    // Create new template

    $new_id = wp_insert_post([

        'post_type' => 'lb_template',

        'post_title' => $name,

        'post_content' => $content,

        'post_status' => 'publish'

    ], true);



    if (is_wp_error($new_id)) {

        wp_send_json_error($new_id->get_error_message());

    }

    

    wp_send_json_success(['id' => $new_id, 'message' => __('Template created', 'my-lightbuilder')]);

});



/**

 * AJAX: Load a specific template

 */

add_action('wp_ajax_lb_load_template', function() {

    check_ajax_referer('lb_nonce', 'nonce');

    

    $id = absint($_POST['id'] ?? 0);

    $p = get_post($id);

    

    if (!$p || $p->post_type !== 'lb_template') {

        wp_send_json_error('Not found');

    }

    

    wp_send_json_success(['content' => $p->post_content, 'title' => $p->post_title]);

});



/**

 * AJAX: Delete a template

 */

add_action('wp_ajax_lb_delete_template', function() {

    check_ajax_referer('lb_nonce', 'nonce');

    

    $id = absint($_POST['id'] ?? 0);

    if ($id) {

        wp_trash_post($id);

    }

    

    wp_send_json_success();

});



// =============================================

// PAGE META BOX FOR TEMPLATE SELECTION

// =============================================



/**

 * Add meta box to pages for template selection

 */

add_action('add_meta_boxes', function() {

    add_meta_box(

        'lb_template_select', 

        __('LightBuilder Template', 'my-lightbuilder'), 

        function($post) {

            $selected = get_post_meta($post->ID, '_lb_selected_template', true);

            $templates = get_posts([

                'post_type' => 'lb_template',

                'numberposts' => -1,

                'post_status' => 'publish',

                'orderby' => 'title',

                'order' => 'ASC'

            ]);

            

            echo '<select name="lb_selected_template" style="width:100%">';

            echo '<option value="0">' . esc_html__('— No Template —', 'my-lightbuilder') . '</option>';

            

            foreach ($templates as $t) {

                printf(

                    '<option value="%d"%s>%s</option>', 

                    $t->ID, 

                    selected((int)$selected, (int)$t->ID, false), 

                    esc_html($t->post_title)

                );

            }

            

            echo '</select>';

            wp_nonce_field('lb_meta', 'lb_meta_nonce');

        }, 

        'page', 

        'side', 

        'high'

    );

});



/**

 * Save the selected template for a page

 */

add_action('save_post_page', function($post_id) {

    // Verify nonce

    if (!isset($_POST['lb_meta_nonce']) || !wp_verify_nonce($_POST['lb_meta_nonce'], 'lb_meta')) {

        return;

    }

    

    // Skip autosaves

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {

        return;

    }

    

    // Check user permissions

    if (!current_user_can('edit_page', $post_id)) {

        return;

    }

    

    // Save the template selection

    $val = isset($_POST['lb_selected_template']) ? absint($_POST['lb_selected_template']) : 0;

    update_post_meta($post_id, '_lb_selected_template', $val);

});



// =============================================

// FRONT-END TEMPLATE RENDERING

// =============================================



/**

 * Render the template on the front-end

 */

add_filter('the_content', function($content) {

    // Only process on front-end for pages

    if (is_admin() || !is_page()) {

        return $content;

    }

    

    $id = get_the_ID();

    $tpl = (int) get_post_meta($id, '_lb_selected_template', true);

    

    // Return original content if no template is selected

    if (!$tpl) {

        return $content;

    }

    

    // Get the template content

    $p = get_post($tpl);

    if (!$p) {

        return $content;

    }

    

    // Process shortcodes and return the template HTML

    $html = do_shortcode($p->post_content);

    return $html;

}, 9); // Priority 9 to run before default content processing





// Enqueue admin scripts and styles

add_action('admin_enqueue_scripts', function($hook) {

    // Only load on our builder page

    if ($hook !== 'appearance_page_lightbuilder') return;

    

    // Enqueue styles

    wp_enqueue_style('lb-builder', get_template_directory_uri() . '/builder/assets/css/builder.css', [], '1.0.1');

    

    // Enqueue scripts

    wp_enqueue_script('lb-builder', get_template_directory_uri() . '/builder/assets/js/builder.js', ['jquery'], '1.0.1', true);

    

    // Enable media uploader and TinyMCE

    wp_enqueue_media();

    wp_enqueue_editor();

    

    // Localize script with AJAX URL and nonce

    wp_localize_script('lb-builder', 'LB', [

        'ajax' => admin_url('admin-ajax.php'),

        'nonce' => wp_create_nonce('lb_nonce')

    ]);

});