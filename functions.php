<?php
/**
 * Amino USA functions and definitions
 * 
 * This file contains all the theme functionality including:
 * - Theme setup and configuration
 * - Enqueue scripts and styles
 * - Custom functionality for WooCommerce
 * - REST API endpoints
 * - Custom post types and meta boxes
 */

// Define theme version constant for cache busting
if ( ! defined( 'AMINO_VERSION' ) ) {
	define( 'AMINO_VERSION', '1.0.0' );
}

// Theme setup - runs after WordPress is initialized
add_action( 'after_setup_theme', function () {
	// Let WP manage document title, thumbnails, etc.
	add_theme_support( 'title-tag' ); // WordPress manages page titles
	add_theme_support( 'post-thumbnails' ); // Enable featured images
	add_theme_support( 'automatic-feed-links' ); // Add RSS feed links
	add_theme_support( 'html5', [ 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ] ); // Enable HTML5 features

	// Wide/full for block editor
	add_theme_support( 'align-wide' ); // Enable wide and full alignment in Gutenberg
	add_theme_support( 'editor-styles' ); // Enable editor styles
	add_editor_style( 'editor-style.css' ); // Load shared editor stylesheet

	// WooCommerce support (optional but harmless)
	add_theme_support( 'woocommerce' ); // Enable WooCommerce support

	// Register navigation menus
	register_nav_menus( [
		'primary' => __( 'Primary Menu', 'amino-usa' ), // Main navigation
		'company'  => __( 'Company Menu', 'amino-usa' ), // Company links menu
		'useful-links'  => __( 'Useful Links Menu', 'amino-usa' ), // Useful links menu
		'policy'  => __( 'Policy Menu', 'amino-usa' ), // Policy links menu
	] );
});

// Enable Dashicons for all users (including visitors)
function customtheme_load_dashicons() {
    wp_enqueue_style('dashicons'); // Load WordPress dashicons font
}
add_action('wp_enqueue_scripts', 'customtheme_load_dashicons');

// Enqueue theme styles and scripts
function amino_enqueue_scripts() {
    // Stylesheet with cache busting
    wp_enqueue_style(
        'amino-style',
        get_stylesheet_uri(),
        [],
        time() // dynamic version to break cache
    );

    // Theme JavaScript with cache busting
    wp_enqueue_script(
        'amino-js',
        get_template_directory_uri() . '/assets/js/theme.js',
        [],
        time(), // use time() instead of $ver
        true
    );
}
add_action( 'wp_enqueue_scripts', 'amino_enqueue_scripts', 20 );


// Register widget areas (for builder/footers)
add_action( 'widgets_init', function () {
	// Footer widget area 1
	register_sidebar([
		'name'          => __( 'Footer 1', 'amino-usa' ),
		'id'            => 'footer-1',
		'before_widget' => '<section class="widget %2$s"><div class="widget-content">',
		'after_widget'  => '</div></section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	]);
	
	// Footer widget area 2
	register_sidebar([
		'name'          => __( 'Footer 2', 'amino-usa' ),
		'id'            => 'footer-2',
		'before_widget' => '<section class="widget %2$s"><div class="widget-content">',
		'after_widget'  => '</div></section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	]);
});

// Ensure body classes include page template + theme slug for targeting
add_filter( 'body_class', function( $classes ){
	$classes[] = 'wp-theme-amino-usa'; // Add theme-specific body class
	return $classes;
});

// Display content function for templates
function amino_display_content() {
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			the_content(); // Display post content
			wp_link_pages([ // Display pagination for multi-page posts
				'before' => '<div class="page-links">',
				'after'  => '</div>',
			]);
		}
	} else {
		get_template_part( 'template-parts/content', 'none' ); // Display "no content" template
	}
}

// Safe wrapper to output a menu (won't error if missing)
function amino_menu( $location = 'primary' ) {
	if ( has_nav_menu( $location ) ) {
		wp_nav_menu([
			'theme_location' => $location,
			'container'      => false, // No container wrapper
			'menu_class'     => 'menu',
			'fallback_cb'    => false, // No fallback if menu doesn't exist
			'depth'          => 2, // Only 2 levels of menu items
		]);
	}
}

// 🔹 Amino Builder integration - Load builder functionality files
if ( file_exists( get_template_directory() . '/inc/admin-builder.php' ) ) {
    require_once get_template_directory() . '/inc/admin-builder.php'; // Admin builder functions
}
if ( file_exists( get_template_directory() . '/inc/customizer.php' ) ) {
    require_once get_template_directory() . '/inc/customizer.php'; // Theme customizer functions
}

// Enqueue LB on frontend - Load builder assets
add_action('wp_enqueue_scripts', function(){
   // Enqueue builder CSS
wp_enqueue_style(
    'lb-builder',
    get_template_directory_uri() . '/builder/assets/css/builder.css',
    [],
    time() // dynamic version to break cache
);

// Enqueue builder JS
wp_enqueue_script(
    'lb-builder',
    get_template_directory_uri() . '/builder/assets/js/builder.js',
    ['jquery'],
    time(), // dynamic version to break cache
    true
);


    // Localize script for AJAX and REST API access
    wp_localize_script('lb-builder','LB', [
        'ajax'  => admin_url('admin-ajax.php'), // AJAX URL
        'nonce' => wp_create_nonce('lb_nonce'), // Security nonce
        'rest' => esc_url_raw( rest_url() ) // REST API URL
    ]);
    
    // Debug output - log nonce creation
    error_log('LB nonce created: ' . wp_create_nonce('lb_nonce'));
});

// ---- AJAX: Load posts ----
// Handle AJAX requests for loading posts (both logged in and out users)
add_action('wp_ajax_lb_load_posts', 'my_lb_load_posts');
add_action('wp_ajax_nopriv_lb_load_posts', 'my_lb_load_posts');

function my_lb_load_posts() {
    check_ajax_referer('lb_nonce', 'nonce'); // Verify nonce for security
    
    // Get request parameters with sanitization
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 5;
    $widget_type = isset($_POST['widget_type']) ? sanitize_text_field($_POST['widget_type']) : 'all-posts';
    
    // If featured posts, just show the latest 5
    $is_featured = ($widget_type === 'featured-posts');
    
    // Setup query arguments
    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $posts_per_page,
        'paged'          => $page,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    
    // If it's featured, only fetch the first page (latest posts)
    if ($is_featured) {
        $args['paged'] = 1;          // always first page
        $args['posts_per_page'] = 5; // always 5 posts
    }
    
    $query = new WP_Query($args);
    
    ob_start(); // Start output buffering
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            
			if ($is_featured) {
				// Featured posts layout: latest 5, stacked line by line
				?>
				<div class="lb-post-item lb-featured">
					<div class="lb-post-image">
						<a href="<?php the_permalink(); ?>">
							<?php 
							if (has_post_thumbnail()) {
								the_post_thumbnail('medium'); // Display featured image
							} else {
								// Fallback image if no featured image
								echo '<img src="' . esc_url(get_template_directory_uri() . '/assets/images/fallback.png') . '" alt="Dummy Image" />';
							}
							?>
						</a>
					</div>
			
					<h3 class="lb-post-title">
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</h3>
			
					<div class="lb-post-excerpt">
						<?php 
						$excerpt = get_the_excerpt();
						$excerpt = wp_trim_words($excerpt, 20, '...'); // Trim excerpt to 20 words
						echo esc_html($excerpt);
						?>
					</div>
			
					<div class="lb-post-meta">
						<a href="<?php the_permalink(); ?>" class="lb-read-more">Read More</a>
						<span class="lb-post-date"><?php echo get_the_date(); ?></span>
					</div>
				</div>
				<?php
			} else {
                // Regular posts layout (grid with image left, text right)
                ?>
                <div class="lb-post-item">
                    <div class="lb-post-image">
                        <a href="<?php the_permalink(); ?>">
                            <?php 
                            if (has_post_thumbnail()) {
                                the_post_thumbnail('medium'); // Display featured image
                            } else {
                                // Fallback image if no featured image
                                echo '<img src="' . esc_url(get_template_directory_uri() . '/assets/images/fallback.png') . '" alt="Dummy Image" />';
                            }
                            ?>
                        </a>
                    </div>
                    
                    <div class="lb-post-content">
                        <div class="lb-post-category">
                            <?php 
                            $categories = get_the_category();
                            if (!empty($categories)) {
                                echo esc_html($categories[0]->name); // Display first category
                            }
                            ?>
                        </div>
                        
                        <h3 class="lb-post-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        
                        <div class="lb-post-excerpt">
                            <?php 
                            $excerpt = get_the_excerpt();
                            $excerpt = wp_trim_words($excerpt, 25, '...'); // Trim excerpt to 25 words
                            echo esc_html($excerpt);
                            ?>
                        </div>
                        
                        <div class="lb-post-meta">
                            <a href="<?php the_permalink(); ?>" class="lb-read-more">Read More</a>
                            <span class="lb-post-date"><?php echo get_the_date(); ?></span>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
        
        // For featured posts → no load more, just 5 latest
        $has_more = (!$is_featured && $query->max_num_pages > $page);
        
        // Return JSON response with HTML content
        wp_send_json_success(array(
            'html'      => ob_get_clean(), // Get buffered content
            'has_more'  => $has_more, // Whether more posts are available
            'next_page' => $page + 1 // Next page number
        ));
    } else {
        // No posts found
        wp_send_json_success(array(
            'html'      => '<p>No posts found.</p>',
            'has_more'  => false,
            'next_page' => $page
        ));
    }
    
    wp_reset_postdata(); // Reset post data
    wp_die(); // Terminate AJAX request properly
}

function mytheme_enqueue_bg_video() {
    if ( !is_admin() ) { // Only load on frontend
        wp_enqueue_script(
            'lb-bg-video',
            get_stylesheet_directory_uri() . '/builder/assets/js/bg-video.js',
            [],
            time(), // Use time() to break cache
            true // Load in footer
        );
    }
}
add_action( 'wp_enqueue_scripts', 'mytheme_enqueue_bg_video', 20 );



// Enqueue accordion functionality for FAQs
function mytheme_enqueue_scripts() {
    // Register and enqueue accordion.js
    wp_enqueue_script(
        'accordion-js', // Handle name
        get_template_directory_uri() . '/builder/assets/js/faqs.js', // File path
        array('jquery'), // Dependencies
        null, // Version (null = no version string)
        true // Load in footer
    );
}
add_action('wp_enqueue_scripts', 'mytheme_enqueue_scripts');

// Enqueue carousel functionality
function my_theme_enqueue_custom_scripts() {
    wp_enqueue_script(
        'custom-carousel',
        get_template_directory_uri() . '/builder/assets/js/carousel.js',
        array('jquery'), // depends on jQuery
        '1.0',
        true // load in footer
    );
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_custom_scripts');

// Enqueue LB on frontend (duplicate - might need consolidation)
add_action('wp_enqueue_scripts', function(){
    // load your builder assets
    wp_enqueue_style('lb-builder', get_template_directory_uri().'/builder/assets/css/builder.css', [], '1.0.1');
    wp_enqueue_script('lb-builder', get_template_directory_uri().'/builder/assets/js/builder.js', ['jquery'], '1.0.1', true);

    // Localize script for AJAX communication
    wp_localize_script('lb-builder','LB', [
        'ajax'  => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('lb_nonce'),
        'rest'  => esc_url_raw( rest_url() )
    ]);    
});

// Load COA CPTs (Certificate of Analysis Custom Post Types)
if ( file_exists( get_template_directory() . '/inc/coa-cpts.php' ) ) {
	require_once get_template_directory() . '/inc/coa-cpts.php';
}

// Add REST API endpoints for products and categories
add_action('rest_api_init', function() {
    // REST endpoint for products
    register_rest_route('lb/v1', '/products', array(
        'methods' => 'GET',
        'callback' => 'lb_rest_products',
        'permission_callback' => '__return_true' // Publicly accessible
    ));
    
    // REST endpoint for categories
    register_rest_route('lb/v1', '/categories', array(
        'methods' => 'GET',
        'callback' => 'lb_rest_categories',
        'permission_callback' => '__return_true' // Publicly accessible
    ));
});

// REST API callback for product categories
function lb_rest_categories() {
    if (!class_exists('WooCommerce')) {
        return new WP_Error('no_woocommerce', 'WooCommerce not active', array('status' => 400));
    }
    
    // Get product categories
    $categories = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => true,
        'number' => 8, // Limit to 8 categories
    ));
    
    ob_start(); // Start output buffering
    
  
    if (!empty($categories) && !is_wp_error($categories)) {
        foreach ($categories as $category) {
            $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
            $image = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : wc_placeholder_img_src();
            $cat_link = get_term_link($category); // ✅ category archive link
            ?>
            <div class="lb-cat">
                <a href="<?php echo esc_url($cat_link); ?>">
                    <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($category->name); ?>">
                    <div><?php echo esc_html($category->name); ?></div>
                </a>
            </div>
            <?php
        }
    } else {
        echo '<p>No categories found.</p>';
    }
   
    
    return array('html' => ob_get_clean()); // Return buffered content
}

// ---- AJAX: Load WooCommerce Products ----
add_action('wp_ajax_lb_load_woo_products', 'lb_load_woo_products');
add_action('wp_ajax_nopriv_lb_load_woo_products', 'lb_load_woo_products');

// AJAX handler for WooCommerce products
function lb_load_woo_products() {
    check_ajax_referer('lb_nonce', 'nonce'); // Security check
    
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce not active');
        wp_die();
        return;
    }
    
    // Query arguments for products
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 12,
        'post_status' => 'publish',
    );
    
    $products = new WP_Query($args);
    
    ob_start(); // Start output buffering
    
    if ($products->have_posts()) {
        while ($products->have_posts()) {
            $products->the_post();
            global $product;
            ?>
            <div class="lb-product">
                <?php echo $product->get_image('medium'); // Product image ?>
                <p><?php echo wc_get_product_category_list($product->get_id()); // Product categories ?></p>
                <h4><?php echo get_the_title(); // Product title ?></h4>
                <div class="lb-price"><?php echo $product->get_price_html(); // Product price ?></div>
            </div>
            <?php
        }
        wp_reset_postdata(); // Reset post data
    } else {
        echo '<p>No products found.</p>';
    }
    
    // Return JSON response
    wp_send_json_success(array(
        'html' => ob_get_clean() // Get buffered content
    ));
    wp_die(); // Terminate AJAX request
}

// REST API callback for products
function lb_rest_products(WP_REST_Request $request) {
    if (!class_exists('WooCommerce')) {
        return new WP_Error('no_woocommerce', 'WooCommerce not active', array('status' => 400));
    }

    // Get product type from request (default to featured)
    $type = $request->get_param('type') ? sanitize_text_field($request->get_param('type')) : 'featured';
    
    // Base query arguments
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => 12,
        'post_status'    => 'publish',
    );
    
    // Add meta query based on product type
    if ($type === 'featured') {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => 'featured', // Featured products
            ),
        );
    } elseif ($type === 'newlaunch') {
       // Show last 15 added products (by publish date, newest first)
    $args['posts_per_page'] = 15;
    $args['orderby']        = 'date';
    $args['order']          = 'DESC';
    }

    $query = new WP_Query($args);
    ob_start(); // Start output buffering
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            global $product;
            if (!$product instanceof WC_Product) {
                $product = wc_get_product(get_the_ID()); // Get product object
            }
            ?>
          <div class="lb-product-item">
    <a href="<?php the_permalink(); ?>" class="lb-product-thumb">
        <?php 
        if (has_post_thumbnail()) {
            the_post_thumbnail('medium'); // Product image
        } else {
            echo wc_placeholder_img('medium'); // Placeholder image
        }
        ?>
    </a>

    <div class="lb-product-info">
        <?php
        // Show product categories
        echo wc_get_product_category_list(get_the_ID(), ', ', '<div class="lb-category">', '</div>');
        ?>

        <h4 class="lb-title">
            <a href="<?php the_permalink(); ?>"><?php echo get_the_title(); ?></a>
        </h4>

        <div class="lb-rating">
            <?php
            if ( $product ) {
                $rating = $product->get_average_rating();
                // Show rating stars (fallback to 0 if no rating yet)
                echo wc_get_rating_html( $rating ? $rating : 0, $product->get_rating_count() );
            }
            ?>
        </div>

        <div class="lb-price">
            <?php echo $product ? $product->get_price_html() : ''; // Product price ?>
        </div>
    </div>
</div>

            <?php
        }
        wp_reset_postdata(); // Reset post data
    } else {
        echo '<p>No ' . esc_html($type) . ' products found.</p>';
    }

    return array('html' => ob_get_clean()); // Return buffered content
}

// Register custom widget area for WooCommerce sidebar
function custom_woocommerce_sidebar_widgets() {
    register_sidebar( array(
        'name'          => __( 'Shop Sidebar', 'your-theme' ),
        'id'            => 'shop-sidebar',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'custom_woocommerce_sidebar_widgets' );

// Custom filter widget for category + price in WooCommerce sidebar
function woocommerce_custom_filter_sidebar() {
    ?>
    <aside class="woocommerce-filter-sidebar">

        <!-- Product Categories -->
        <div class="filter-section filter-categories">
            <h2><?php esc_html_e( 'Filter by Category', 'your-theme' ); ?></h2>
            <ul class="product-categories">
                <?php
                // List product categories
                wp_list_categories( array(
                    'taxonomy'     => 'product_cat',
                    'title_li'     => '',
                    'show_count'   => true,
                    'hide_empty'   => true,
                ) );
                ?>
            </ul>
        </div>

        <!-- Price Filter -->
        <div class="filter-section filter-price">
            <?php
            // WooCommerce built-in price filter widget
            the_widget( 'WC_Widget_Price_Filter', array(), array(
                'before_widget' => '<div class="widget_price_filter">',
                'after_widget'  => '</div>',
            ) );
            ?>
        </div>

    </aside>
    <?php
}

// Hook custom filter sidebar to WooCommerce sidebar
add_action( 'woocommerce_sidebar', 'woocommerce_custom_filter_sidebar', 5 );

// Custom fields in single product

// Add a custom metabox for Content fields and Disclaimer
// add_action( 'add_meta_boxes', 'add_custom_content_metabox' );
// function add_custom_content_metabox() {
//     add_meta_box(
//         'custom_product_content_box',
//         __( 'Extra Product Content', 'woocommerce' ),
//         'render_custom_content_metabox',
//         'product',
//         'normal',
//         'high'
//     );
// }

// Render metabox fields
// function render_custom_content_metabox( $post ) {
//     // Get existing values
//     $content_one   = get_post_meta( $post->ID, '_content_one', true );
//     $content_two   = get_post_meta( $post->ID, '_content_two', true );
//     $content_three = get_post_meta( $post->ID, '_content_three', true );
//     $disclaimer    = get_post_meta( $post->ID, '_disclaimer', true );
    
//     // Content 1 field
//     echo '<p><label for="content_one"><strong>Content 1</strong></label></p>';
//     wp_editor( $content_one, 'content_one', array( 'textarea_name' => 'content_one', 'textarea_rows' => 3 ) );

//     // Content 2 field
//     echo '<p><label for="content_two"><strong>Content 2</strong></label></p>';
//     wp_editor( $content_two, 'content_two', array( 'textarea_name' => 'content_two', 'textarea_rows' => 3 ) );

//     // Content 3 field
//     echo '<p><label for="content_three"><strong>Content 3</strong></label></p>';
//     wp_editor( $content_three, 'content_three', array( 'textarea_name' => 'content_three', 'textarea_rows' => 3 ) );

//     // Disclaimer field
//     echo '<p><label for="disclaimer"><strong>Disclaimer</strong></label></p>';
//     wp_editor( $disclaimer, 'disclaimer', array( 'textarea_name' => 'disclaimer', 'textarea_rows' => 3 ) );
// }

// Save custom metabox fields
// add_action( 'save_post_product', 'save_custom_content_metabox' );
// function save_custom_content_metabox( $post_id ) {
//     // Save each field with proper sanitization
//     if ( isset( $_POST['content_one'] ) ) {
//         update_post_meta( $post_id, '_content_one', wp_kses_post( $_POST['content_one'] ) );
//     }
//     if ( isset( $_POST['content_two'] ) ) {
//         update_post_meta( $post_id, '_content_two', wp_kses_post( $_POST['content_two'] ) );
//     }
//     if ( isset( $_POST['content_three'] ) ) {
//         update_post_meta( $post_id, '_content_three', wp_kses_post( $_POST['content_three'] ) );
//     }
//     if ( isset( $_POST['disclaimer'] ) ) {
//         update_post_meta( $post_id, '_disclaimer', wp_kses_post( $_POST['disclaimer'] ) );
//     }
// }

// Show fields on single product page (without disclaimer)
// add_action( 'woocommerce_single_product_summary', 'display_custom_content_on_product_page', 25 );
// function display_custom_content_on_product_page() {
//     global $post;

//     // Get saved values
//     $content_one   = get_post_meta( $post->ID, '_content_one', true );
//     $content_two   = get_post_meta( $post->ID, '_content_two', true );
//     $content_three = get_post_meta( $post->ID, '_content_three', true );
//     $disclaimer    = get_post_meta( $post->ID, '_disclaimer', true );

//     // Display fields if they have content
//     if ( $content_one || $content_two || $content_three || $disclaimer ) {
//         echo '<div class="custom-product-fields">';
//         if ( $content_one ) {
//             echo '<div class="content-one">' . wpautop( $content_one ) . '</div>';
//         }
//         if ( $content_two ) {
//             echo '<div class="content-two">' . wpautop( $content_two ) . '</div>';
//         }
//         if ( $content_three ) {
//             echo '<div class="content-three">' . wpautop( $content_three ) . '</div>';
//         } if ( $disclaimer ) {
//             echo '<div class="product-disclaimer">' . wpautop( $disclaimer ) . '</div>';
//         }
//         echo '</div>';
//     }
// }





// 1️⃣ Keep the original hook as it is
add_action('woocommerce_product_thumbnails', 'product_guarantee_icons', 20);
function product_guarantee_icons() {
    global $post;
    ?>
    <section class="guarantee-icons" style="margin-top:20px;text-align:center;">
        <div class="guarantee-icons-wrap">

            <?php
            // Define icons with descriptions
            $icons = [
                ['img'=>'/wp-content/uploads/2025/10/30-days.png','title'=>'Money Back Guarantee','desc'=>'Return or exchange your unopened product within 30 days no questions asked.'],
                ['img'=>'/wp-content/uploads/2025/10/advertisment.png','title'=>'Satisfaction Guaranteed','desc'=>'If you are unsatisfied we will refund back 100% of your order.'],
                ['img'=>'/wp-content/uploads/2025/10/easy-return.png','title'=>'Easy Return','desc'=>'If you need to make a return the process is simple, straightforward and tracked.'],
                ['img'=>'/wp-content/uploads/2025/10/secure.png','title'=>'Secure Ordering','desc'=>'Our checkout is SSL encrypted and completely secure.'],
                ['img'=>'/wp-content/uploads/2025/10/delivery-truck.png','title'=>'Same Day Shipping','desc'=>'Order today and we will ship the very same day, every business day.'],
                ['img'=>'/wp-content/uploads/2025/10/badge.png','title'=>'Third-party Tested','desc'=>'Our products are verified by independent third party laboratories to meet quality standards.'],
                ['img'=>'/wp-content/uploads/2025/10/report.png','title'=>'Batch & Lot Tested','desc'=>'All product batches and lots are assigned unique identifiers and tied to publicly posted lab reports.'],
            ];

            foreach ($icons as $icon) : ?>
                <div class="single-icon-box" title="<?php echo esc_attr($icon['desc']); ?>">
                    <div class="icon-img">
                        <img src="<?php echo esc_url(home_url($icon['img'])); ?>" alt="<?php echo esc_attr($icon['title']); ?>" />
                    </div>
                    <p><?php echo esc_html($icon['title']); ?></p>
                </div>
            <?php endforeach; ?>

        </div>
    </section> 
    <?php
}

// 2️⃣ Shortcode version (only works if product has thumbnail gallery)
add_shortcode('guarantee_icons', 'product_guarantee_icons_shortcode');

function product_guarantee_icons_shortcode() {
    global $product;

    // Ensure we are on a single product page with a product object
    if ( ! $product instanceof WC_Product ) {
        return '';
    }

    // Only show if product has gallery images
    $gallery_ids = $product->get_gallery_image_ids();
    if ( empty( $gallery_ids ) ) {
        return ''; // Do not render shortcode
    }

    ob_start();
    product_guarantee_icons(); // Reuse the existing function
    return ob_get_clean();
}





// Show "Why AminoUSA?" section after description 
add_action( 'woocommerce_after_single_product_summary', 'why_choose_amino_section', 15 );
function why_choose_amino_section() {
    global $post;
    ?>
      <?php
        // Display only the disclaimer ACF field below the guarantee icons
        $content_desclaimer = get_field('content_desclaimer', $post->ID);

        if ($content_desclaimer) {
            echo '<div class="product-disclaimer-section" style="margin-top: 30px; clear: both; width:70%;">';
            echo '<div class="product-disclaimer" style="padding: 15px;background: #fff;border: 1px solid #400080;font-style: italic;  border-radius: 20px;color: #400080;text-align: left;">' . wpautop($content_desclaimer) . '</div>';
            echo '</div>';
        }
        ?>
    <!-- Product Description Accordion -->
    <div class="product-description-accordion" style="margin-top: 30px;">
        <div class="accordion-item">
            <button class="accordion-header" style="width: 100%; text-align: left; padding: 15px; background: #f5f5f5; border: 1px solid #ddd; cursor: pointer; border-radius: 5px; font-weight: bold; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size:32px;"> Structure & Specification </span>
                <span class="accordion-icon">
                    <svg class="accordion-chevron-down" aria-hidden="true" viewBox="0 0 320 512" xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px;">
                        <path d="M31.3 192h257.3c17.8 0 26.7 21.5 14.1 34.1L174.1 354.8c-7.8 7.8-20.5 7.8-28.3 0L17.2 226.1C4.6 213.5 13.5 192 31.3 192z"></path>
                    </svg>
                    <svg class="accordion-chevron-up" aria-hidden="true" viewBox="0 0 320 512" xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px; display: none;">
                        <path d="M288.662 352H31.338c-17.818 0-26.741-21.543-14.142-34.142l128.662-128.662c7.81-7.81 20.474-7.81 28.284 0l128.662 128.662c12.6 12.599 3.676 34.142-14.142 34.142z"></path>
                    </svg>
                </span>
            </button>
            <div class="accordion-content" style="display: none; padding: 15px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 5px 5px; background: #fff;">
                <?php
                $product_description = get_the_content();
                if (!empty($product_description)) {
                    echo apply_filters('the_content', $product_description);
                } else {
                    echo '<p>No product description available.</p>';
                }
                ?>
            </div>
        </div>
    </div>
    <section class="why-choose-amino" style="margin-top:40px;text-align:center;">
        <h2>Why Choose Amino USA?</h2>
        <div class="why-choose-amino-cards" >
            <div style="text-align:center;" class="why-choose-amino-single-card">
                <img src="<?php echo esc_url( home_url() ); ?>/wp-content/uploads/2025/10/free-shipping.png" alt="Free Shipping" width="150" />
                <h4>Free shipping</h4>
                <p>Free shipping in the US for orders $250+</p>
            </div>
            <div style="text-align:center;" class="why-choose-amino-single-card">
                <img src="<?php echo esc_url( home_url() ); ?>/wp-content/uploads/2025/10/buyer-protection.png" alt="Buyer Protection" width="150" />
                <h4>Buyer Protection</h4>
                <p>Protected and discrete shipping at the customers request.</p>
            </div>
            <div style="text-align:center;" class="why-choose-amino-single-card">
                <img src="<?php echo esc_url( home_url() ); ?>/wp-content/uploads/2025/10/customer-service.png" alt="Customer Service" width="150" />
                <h4>Excellent Customer Service</h4>
                <p>We take pride in our ability to offer our customer the highest level of customer service possible</p>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const accordionHeader = document.querySelector('.accordion-header');
            const accordionContent = document.querySelector('.accordion-content');
            const chevronDown = document.querySelector('.accordion-chevron-down');
            const chevronUp = document.querySelector('.accordion-chevron-up');

            accordionHeader.addEventListener('click', function() {
                const isActive = this.classList.contains('active');

                this.classList.toggle('active');

                if (isActive) {
                    accordionContent.style.display = 'none';
                    chevronDown.style.display = 'block';
                    chevronUp.style.display = 'none';
                } else {
                    accordionContent.style.display = 'block';
                    chevronDown.style.display = 'none';
                    chevronUp.style.display = 'block';
                }
            });
        });
    </script>
    <?php
}

// Enable WooCommerce product gallery features
add_action( 'after_setup_theme', 'mytheme_wc_support' );
function mytheme_wc_support() {
    add_theme_support( 'wc-product-gallery-slider' );   // Thumbnail slider
}

// Add + and - buttons around quantity input
add_action( 'woocommerce_after_quantity_input_field', 'custom_quantity_plus' );
function custom_quantity_plus() {
    echo '<button type="button" class="plus">+</button>'; // Plus button
}

add_action( 'woocommerce_before_quantity_input_field', 'custom_quantity_minus' );
function custom_quantity_minus() {
    echo '<button type="button" class="minus">-</button>'; // Minus button
}

// Show ACF linked products buttons and Bulk Discount Section before Add to Cart and Quantity
add_action( 'woocommerce_before_add_to_cart_quantity', 'custom_linked_products_and_bulk_discount' );
function custom_linked_products_and_bulk_discount() {
    global $product;

    // 1. Display ACF repeater field buttons
    if( have_rows('linked_products', $product->get_id()) ) : 
        echo '<section class="linked-products-section" style="margin-bottom:20px;">';
     
        
        while( have_rows('linked_products', $product->get_id()) ) : the_row();
            $label = get_sub_field('label'); // Button text
            $linked_product = get_sub_field('product'); // This might be an array or ID
            $current_variation = get_sub_field('current_variation'); // Checkbox

            // Handle both array and single ID formats
            $product_id = null;
            
            if( is_array($linked_product) && isset($linked_product['ID']) ) {
                // If it's a post object array
                $product_id = $linked_product['ID'];
            } elseif( is_array($linked_product) && !empty($linked_product) ) {
                // If it's an array with first element as ID
                $product_id = $linked_product[0];
            } elseif( is_numeric($linked_product) ) {
                // If it's already a single ID
                $product_id = $linked_product;
            }

            if( $product_id && $label ) {
                $linked_product_obj = wc_get_product( $product_id );
                
                if( $linked_product_obj && $linked_product_obj->is_visible() ) {
                    $url = get_permalink( $product_id );
                    
                    // Change button color if checkbox is checked
                    $btn_style = $current_variation ? 
                        'background-color:#000;color:#fff;border-color:#000;' : 
                        'background-color:#400080;color:#fff;border:1px solid #400080;';
                    
                    echo '<a href="'.esc_url($url).'" class="linked-product-button" style="display:inline-block;margin:5px;padding:8px 15px;border-radius:0px;text-decoration:none;'.$btn_style.'" onmouseover="this.style.opacity=\'0.8\'" onmouseout="this.style.opacity=\'1\'">';
                    echo esc_html( $label );
                    echo '</a>';
                }
            }

        endwhile;
        echo '</section>';
    endif;

    // 2. Display Bulk Discount Section
    ?>
   <section class="bulk-discount-section">
        <ul >
            <li style="margin-bottom:8px;">
                <span style="display:inline-block;padding-left:20px;position:relative;">
                    <span style="position:absolute;left:0;">&#10148;</span> 
                    Buy 5 and save <strong>5%</strong>
                </span>
            </li>
            <li style="margin-bottom:8px;">
                <span style="display:inline-block;padding-left:20px;position:relative;">
                    <span style="position:absolute;left:0;">&#10148;</span> 
                    Buy 10 and save <strong>10%</strong>
                </span>
            </li>
            <li>
                <span style="display:inline-block;padding-left:20px;position:relative;">
                    <span style="position:absolute;left:0;">&#10148;</span> 
                    Buy 20 and save <strong>15%</strong>
                </span>
            </li>
        </ul>
        <p style="font-size:14px;color:#555;margin-top:10px;">
            Tax excluded. Shipping calculated at checkout
        </p>
    </section>
    <?php
}





// Show ACF content fields above Third-Party Testing section
add_action( 'woocommerce_before_add_to_cart_form', 'display_acf_content_fields_above_testing', 5 );
function display_acf_content_fields_above_testing() {
    global $post;
    
    // Get ACF fields
    $content_first = get_field('content_first', $post->ID);
    $content_second = get_field('content_second', $post->ID);
    $content_third = get_field('content_third', $post->ID);
    
    // Display fields if they have content
    if ( $content_first || $content_second || $content_third ) {
        echo '<div class="acf-content-fields-above-testing" style="margin-bottom: 20px;">';
        
        if ( $content_first ) {
            echo '<div class="content-first" style="margin-bottom: 15px;">' . wpautop( $content_first ) . '</div>';
        }
        if ( $content_second ) {
            echo '<div class="content-second" style="margin-bottom: 15px;">' . wpautop( $content_second ) . '</div>';
        }
        if ( $content_third ) {
            echo '<div class="content-third" style="margin-bottom: 15px;">' . wpautop( $content_third ) . '</div>';
        }
        
        echo '</div>';
    }
}

// Show Third-Party Testing Section before Add to Cart form
add_action( 'woocommerce_before_add_to_cart_form', 'custom_third_party_testing_section', 10 );
function custom_third_party_testing_section() {
    ?>
    <section class="third-party-testing">
        <div class="testing-icon" style="flex:0 0 50px;">
            <img src="<?php echo esc_url( home_url() ); ?>/wp-content/uploads/2025/10/circle-chart.png" alt="Testing Icon" width="50" />
        </div>
        <div class="testing-text">
            <h4 style="margin:0 0 5px;font-weight:bold;color:#4a007d;">Rigorous Third-Party Testing</h4>
            <p style="margin:0;font-size:15px;color:#444;">
                Every batch of our research chemicals and peptides undergoes third-party testing.
            </p>
        </div>
    </section>
    <?php
}



// Enqueue JS for quantity buttons functionality
add_action( 'wp_footer', 'custom_quantity_buttons_script' );
function custom_quantity_buttons_script() {
    if ( ! is_product() ) return; // only load on product pages
    ?>
    <script>
    jQuery(function($) {
        $('form.cart').on('click', 'button.plus, button.minus', function() {
            var qty = $(this).closest('form.cart').find('.qty');
            var val = parseFloat(qty.val());
            var max = parseFloat(qty.attr('max'));
            var min = parseFloat(qty.attr('min'));
            var step = parseFloat(qty.attr('step'));

            if ($(this).is('.plus')) {
                if (max && (max <= val)) {
                    qty.val(max); // Don't exceed maximum quantity
                } else {
                    qty.val(val + step); // Increase quantity by step
                }
            } else {
                if (min && (min >= val)) {
                    qty.val(min); // Don't go below minimum quantity
                } else if (val > 1) {
                    qty.val(val - step); // Decrease quantity by step
                }
            }
            qty.trigger('change'); // Trigger change event for other scripts
        });
    });
    </script>
    <?php
}

// Add custom cards with image URLs to WooCommerce My Account dashboard
add_action( 'woocommerce_account_dashboard', 'custom_my_account_dashboard_cards' );

function custom_my_account_dashboard_cards() {
    // Define the cards with direct image URLs
    $cards = array(
        'orders' => array(
            'url'   => wc_get_account_endpoint_url( 'orders' ),
            'img'   => home_url( '/wp-content/uploads/2025/10/package.png' ),
            'label' => __( 'Orders', 'woocommerce' ),
        ),
        'addresses' => array(
            'url'   => wc_get_account_endpoint_url( 'edit-address' ),
            'img'   => home_url( '/wp-content/uploads/2025/10/place-marker.png' ),
            'label' => __( 'Addresses', 'woocommerce' ),
        ),
        'account' => array(
            'url'   => wc_get_account_endpoint_url( 'edit-account' ),
            'img'   => home_url( '/wp-content/uploads/2025/10/user.png' ),
            'label' => __( 'Account Details', 'woocommerce' ),
        ),
        'logout' => array(
            'url'   => wc_logout_url(),
            'img'   => home_url( '/wp-content/uploads/2025/10/logout.png' ),
            'label' => __( 'Logout', 'woocommerce' ),
        ),
    );
    

    // Start card container
    echo '<div class="custom-account-cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-top:20px;">';

    // Loop through each card and output HTML
    foreach ( $cards as $card ) {
        echo '<div class="account-card" style="border:1px solid #eee;padding:20px;text-align:center;border-radius:5px;box-shadow:0 2px 5px rgba(0,0,0,0.1);">';
        
        // Card icon
        if ( ! empty( $card['img'] ) ) {
            echo '<div class="account-card-icon" style="margin-bottom:10px;">';
            echo '<img src="' . esc_url( $card['img'] ) . '" alt="' . esc_attr( $card['label'] ) . '" style="width:50px;height:50px;object-fit:contain;margin:auto;" />';
            echo '</div>';
        }
        
        // Card link
        echo '<a href="' . esc_url( $card['url'] ) . '" style="text-decoration:none;font-weight:bold;color:#333;font-size:16px;">' . esc_html( $card['label'] ) . '</a>';
        echo '</div>';
    }

    echo '</div>'; // End card container
}
// Display Certificate of Analysis (COA) information on single product page using ACF
function amino_show_coa_info_single_product() {
    global $product;

    if ( ! $product || ! $product->get_id() ) {
        return; // Exit if no product
    }

    $pid = $product->get_id(); // Get product ID

    // Get all COA posts
    $coa_posts = get_posts( array(
        'post_type'      => 'coa', // Certificate of Analysis post type
        'posts_per_page' => -1, // Get all posts
        'post_status'    => 'publish', // Only published posts
        'orderby'        => 'date', // Order by date
        'order'          => 'DESC', // Newest first
    ) );

    $latest_pdf_url = ''; // Initialize PDF URL
    $latest_lot     = ''; // Initialize lot number

    // Find COA for this product using ACF
    if ( $coa_posts ) {
        foreach ( $coa_posts as $c ) {
            // Get product relationship field from COA post using ACF
            $coa_products = get_field('product', $c->ID);
            
            // Check if this COA is linked to the current product
            $is_linked = false;
            if ($coa_products) {
                if (is_array($coa_products)) {
                    foreach ($coa_products as $coa_product) {
                        $coa_product_id = 0;
                        if (is_object($coa_product) && isset($coa_product->ID)) {
                            $coa_product_id = $coa_product->ID;
                        } elseif (is_numeric($coa_product)) {
                            $coa_product_id = $coa_product;
                        }
                        
                        if ($coa_product_id == $pid) {
                            $is_linked = true;
                            break;
                        }
                    }
                } elseif (is_object($coa_products) && isset($coa_products->ID)) {
                    if ($coa_products->ID == $pid) {
                        $is_linked = true;
                    }
                } elseif (is_numeric($coa_products)) {
                    if ($coa_products == $pid) {
                        $is_linked = true;
                    }
                }
            }

            if ($is_linked) {
                // Get ACF fields from COA post
                $lot = get_field('lot_number', $c->ID); // Get lot number
                $pdf_file = get_field('certificate_of_analysis_pdf_file', $c->ID); // Get PDF file
                
                // Get PDF URL from ACF file field
                $pdf_url = '';
                if ($pdf_file) {
                    if (is_array($pdf_file) && isset($pdf_file['url'])) {
                        $pdf_url = $pdf_file['url'];
                    } elseif (is_string($pdf_file)) {
                        $pdf_url = $pdf_file;
                    } elseif (is_numeric($pdf_file)) {
                        $pdf_url = wp_get_attachment_url($pdf_file);
                    }
                }
                
                if ($lot && $pdf_url) {
                    $latest_lot     = $lot; // Set latest lot number
                    $latest_pdf_url = $pdf_url; // Set latest PDF URL
                    break; // first one (latest) is enough
                }
            }
        }
    }

    // Find COA Page using ACF
    $coa_pages = get_posts( array(
        'post_type'      => 'coa-page', // COA page post type
        'posts_per_page' => -1, // Get all pages
        'post_status'    => 'publish', // Only published pages
    ) );
    
    $coa_page_url = ''; // Initialize COA page URL
    if ( $coa_pages ) {
        foreach ( $coa_pages as $cp ) {
            // Get product relationship field from COA Page using ACF
            $page_products = get_field('product', $cp->ID);
            
            // Check if this COA Page is linked to the current product
            $is_linked = false;
            if ($page_products) {
                if (is_array($page_products)) {
                    foreach ($page_products as $page_product) {
                        $page_product_id = 0;
                        if (is_object($page_product) && isset($page_product->ID)) {
                            $page_product_id = $page_product->ID;
                        } elseif (is_numeric($page_product)) {
                            $page_product_id = $page_product;
                        }
                        
                        if ($page_product_id == $pid) {
                            $is_linked = true;
                            break;
                        }
                    }
                } elseif (is_object($page_products) && isset($page_products->ID)) {
                    if ($page_products->ID == $pid) {
                        $is_linked = true;
                    }
                } elseif (is_numeric($page_products)) {
                    if ($page_products == $pid) {
                        $is_linked = true;
                    }
                }
            }

            if ($is_linked) {
                $coa_page_url = get_permalink( $cp->ID ); // Get COA page URL
                break;
            }
        }
    }

    // Output COA information if available
    if ( $latest_pdf_url || $coa_page_url ) {
        echo '<div class="product-coa-info" style="margin-top:20px; padding:12px; border:1px solid #eee; border-radius:6px; display:block;">';
        echo '<div  style="display:flex; align-items:center; gap:12px;">';
    
        // Left icon
        echo '<div class="testing-icon" style="flex:0 0 30px;">
                <img src="' . esc_url( home_url() ) . '/wp-content/uploads/2025/10/circle-chart.png" alt="Testing Icon" width="30" />
              </div>';
    
        // Right content
        echo '<div class="coa-text">';
    
          
       
            echo '<p style="margin:0px;"><strong> Certificate of Analysis</strong></p>';
      
    
        echo '</div>'; // End coa-text
        
        echo '</div>'; // End product-coa-info
        echo '<div class="coa-text">';
    
        // Latest COA PDF link
        if ( $latest_pdf_url && $latest_lot ) {
            echo '<p style="margin-bottom:0px;"><a href="' . esc_url( $latest_pdf_url ) . '" target="_blank" rel="noopener noreferrer" style="text-decoration: underline !important;">Latest Certificate of Analysis</a></p>';
        }
    
        // COA page link
        if ( $coa_page_url ) {
            echo '<p style="margin-top:0px;">Or, <a href="' . esc_url( $coa_page_url ) . '" style="text-decoration: underline !important;">look up certificates by lot number</a></p>';
        }
    
        echo '</div>'; 
        echo '</div>'; 
    }
}

// Add COA info to single product page
add_action( 'woocommerce_single_product_summary', 'amino_show_coa_info_single_product', 35 );
// Display Endotoxin information on single product page using ACF
function amino_show_endotoxin_info_single_product() {
    global $product;

    if ( ! $product || ! $product->get_id() ) {
        return; // Exit if no product
    }

    $pid = $product->get_id(); // Get product ID

    // Get all Endotoxin posts
    $endotoxin_posts = get_posts( array(
        'post_type'      => 'endotoxin', // Endotoxin post type
        'posts_per_page' => -1, // Get all posts
        'post_status'    => 'publish', // Only published posts
        'orderby'        => 'date', // Order by date
        'order'          => 'DESC', // Newest first
    ) );

    $latest_pdf_url = ''; // Initialize PDF URL
    $latest_date     = ''; // Initialize analysis date

    // Find Endotoxin for this product using ACF
    if ( $endotoxin_posts ) {
        foreach ( $endotoxin_posts as $e ) {
            // Get product relationship field from Endotoxin post using ACF
            $endotoxin_products = get_field('product', $e->ID);
            
            // Check if this Endotoxin is linked to the current product
            $is_linked = false;
            if ($endotoxin_products) {
                if (is_array($endotoxin_products)) {
                    foreach ($endotoxin_products as $endotoxin_product) {
                        $endotoxin_product_id = 0;
                        if (is_object($endotoxin_product) && isset($endotoxin_product->ID)) {
                            $endotoxin_product_id = $endotoxin_product->ID;
                        } elseif (is_numeric($endotoxin_product)) {
                            $endotoxin_product_id = $endotoxin_product;
                        }
                        
                        if ($endotoxin_product_id == $pid) {
                            $is_linked = true;
                            break;
                        }
                    }
                } elseif (is_object($endotoxin_products) && isset($endotoxin_products->ID)) {
                    if ($endotoxin_products->ID == $pid) {
                        $is_linked = true;
                    }
                } elseif (is_numeric($endotoxin_products)) {
                    if ($endotoxin_products == $pid) {
                        $is_linked = true;
                    }
                }
            }

            if ($is_linked) {
                // Get ACF fields from Endotoxin post
                $analysis_date = get_field('analysis_date', $e->ID); // Get analysis date
                $pdf_file = get_field('certificate_of_analysis_pdf_file', $e->ID); // Get PDF file
                
                // Get PDF URL from ACF file field
                $pdf_url = '';
                if ($pdf_file) {
                    if (is_array($pdf_file) && isset($pdf_file['url'])) {
                        $pdf_url = $pdf_file['url'];
                    } elseif (is_string($pdf_file)) {
                        $pdf_url = $pdf_file;
                    } elseif (is_numeric($pdf_file)) {
                        $pdf_url = wp_get_attachment_url($pdf_file);
                    }
                }
                
                if ($analysis_date && $pdf_url) {
                    $latest_date     = $analysis_date; // Set latest analysis date
                    $latest_pdf_url = $pdf_url; // Set latest PDF URL
                    break; // first one (latest) is enough
                }
            }
        }
    }

    // Find Endotoxin Page using ACF
    $endotoxin_pages = get_posts( array(
        'post_type'      => 'endotoxin-page', // Endotoxin page post type
        'posts_per_page' => -1, // Get all pages
        'post_status'    => 'publish', // Only published pages
    ) );
    
    $endotoxin_page_url = ''; // Initialize Endotoxin page URL
    if ( $endotoxin_pages ) {
        foreach ( $endotoxin_pages as $ep ) {
            // Get product relationship field from Endotoxin Page using ACF
            $page_products = get_field('product', $ep->ID);
            
            // Check if this Endotoxin Page is linked to the current product
            $is_linked = false;
            if ($page_products) {
                if (is_array($page_products)) {
                    foreach ($page_products as $page_product) {
                        $page_product_id = 0;
                        if (is_object($page_product) && isset($page_product->ID)) {
                            $page_product_id = $page_product->ID;
                        } elseif (is_numeric($page_product)) {
                            $page_product_id = $page_product;
                        }
                        
                        if ($page_product_id == $pid) {
                            $is_linked = true;
                            break;
                        }
                    }
                } elseif (is_object($page_products) && isset($page_products->ID)) {
                    if ($page_products->ID == $pid) {
                        $is_linked = true;
                    }
                } elseif (is_numeric($page_products)) {
                    if ($page_products == $pid) {
                        $is_linked = true;
                    }
                }
            }

            if ($is_linked) {
                $endotoxin_page_url = get_permalink( $ep->ID ); // Get Endotoxin page URL
                break;
            }
        }
    }

    // Output Endotoxin information if available
    if ( $latest_pdf_url || $endotoxin_page_url ) {
        echo '<div class="product-endotoxin-info" style="margin-top:20px; padding:12px; border:1px solid #eee; border-radius:6px; display:block;">';
        echo '<div  style="display:flex; align-items:center; gap:12px;">';
    
        // Left icon
        echo '<div class="testing-icon" style="flex:0 0 30px;">
                <img src="' . esc_url( home_url() ) . '/wp-content/uploads/2025/10/circle-chart.png" alt="Testing Icon" width="30" />
              </div>';
    
        // Right content
        echo '<div class="endotoxin-text">';
    
          
       
            echo '<p style="margin:0px;"><strong>Endotoxin Report</strong></p>';
      
    
        echo '</div>'; // End endotoxin-text
        
        echo '</div>'; // End product-endotoxin-info
        echo '<div class="endotoxin-text">';
    
        // Latest Endotoxin PDF link
        if ( $latest_pdf_url && $latest_date ) {
            $formatted_date = date('F j, Y', strtotime($latest_date));
            echo '<p style="margin-bottom:0px;"><a href="' . esc_url( $latest_pdf_url ) . '" target="_blank" rel="noopener noreferrer" style="text-decoration: underline !important;">Latest Endotoxin Report (' . esc_html($formatted_date) . ')</a></p>';
        }
    
        // Endotoxin page link
        if ( $endotoxin_page_url ) {
            echo '<p style="margin-top:0px;">Or, <a href="' . esc_url( $endotoxin_page_url ) . '" style="text-decoration: underline !important;">view all endotoxin reports</a></p>';
        }
    
        echo '</div>'; 
        echo '</div>'; 
    }
}

// Add Endotoxin info to single product page
add_action( 'woocommerce_single_product_summary', 'amino_show_endotoxin_info_single_product', 36 );



// Add Buy Now button after Add to Cart
add_action( 'woocommerce_after_add_to_cart_button', 'add_buy_now_button' );
function add_buy_now_button() {
    global $product;

    if( ! $product->is_type('simple') ) return; // Works for simple products only

    $product_id = $product->get_id();
    $checkout_url = wc_get_checkout_url() . '?add-to-cart=' . $product_id;
    ?>
    <a href="<?php echo esc_url($checkout_url); ?>" 
       class="button buy-now-button" 
       >
 Buy Now
    </a>
    <?php
}









// Age verification popup
function age_verification_popup_scripts() {
    // ✅ Only output the popup if cookie is NOT set
    if (isset($_COOKIE['ageVerified']) && $_COOKIE['ageVerified'] === 'true') {
        return; // Already verified → don't render popup
    }
    ?>
    <style>
        #age-verification-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 99999;
            font-family: "Poppins", sans-serif;
        }
        #age-verification-box {
            background: #fff;
            padding: 30px 25px;
            border-radius: 12px;
            max-width: 520px;
            width: 92%;
            box-shadow: 0 6px 18px rgba(0,0,0,0.25);
            text-align: left;
        }
        #age-verification-box h2 {
            font-size: 20px;
            margin: 0 0 8px 0;
            color: #222;
            font-weight: 600;
        }
        #age-verification-box h3 {
            font-size: 26px;
            margin: 0 0 20px 0;
            color: #1b1b1b;
            font-weight: 700;
        }
        #age-verification-box img {
            display: block;
            width: 160px;
            margin-bottom: 20px;
        }
        #age-verification-box p {
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 18px;
            color: #333;
        }
        #age-verification-box label {
            display: flex;
            align-items: center;
            font-size: 14px;
            margin-bottom: 20px;
            cursor: pointer;
            color: #333;
        }
        #age-verification-box input[type="checkbox"] {
            margin-right: 8px;
        }
        #age-verification-buttons {
            display: flex;
            justify-content: start;
            gap: 12px;
        }
        #age-verification-box button {
            padding: 10px 22px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        #age-yes {
            background: #400080;
            color: #fff;
        }
        #age-yes:disabled {
            background: #aaa;
            cursor: not-allowed;
        }
        #age-no {
            background: #400080;
            color: #fff;
        }
        #age-error {
            color: red;
            display: none;
            margin-top: 12px;
            font-size: 14px;
            text-align: center;
        }
    </style>

    <div id="age-verification-overlay">
        <div id="age-verification-box">
            <h2>WELCOME TO</h2>
            <img src="<?php echo esc_url( home_url() ); ?>/wp-content/uploads/2024/01/Purple-Logo-Transparent-Background-1.png" alt="Logo" />
            <p>
                All products available through <?php bloginfo('name'); ?> are strictly intended for scientific and laboratory research only.
            </p>
            <p>
                By proceeding, I confirm that I am 21 years of age or older and that all products will be used strictly for research purposes.
            </p>
            <label>
                <input type="checkbox" id="age-confirm">
                I confirm that all products are for laboratory research purposes only.
            </label>
            <div id="age-verification-buttons">
                <button id="age-yes" disabled>YES, I'M 21 AND ABOVE</button>
                <button id="age-no">NO</button>
            </div>
            <p id="age-error">You are not old enough to access this website and/or understand the purpose of these research products.</p>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const checkbox = document.getElementById("age-confirm");
            const yesBtn = document.getElementById("age-yes");
            const noBtn = document.getElementById("age-no");
            const errorMsg = document.getElementById("age-error");

            checkbox.addEventListener("change", function() {
                yesBtn.disabled = !this.checked;
            });

            yesBtn.addEventListener("click", function() {
                if (checkbox.checked) {
                    // ✅ Set cookie for 30 days
                    document.cookie = "ageVerified=true; max-age=2592000; path=/";
                    document.getElementById("age-verification-overlay").style.display = "none";
                }
            });

            noBtn.addEventListener("click", function() {
                errorMsg.style.display = "block";
            });
        });
    </script>
    <?php
}
add_action('wp_footer', 'age_verification_popup_scripts');





// ==========================
// Handle CONTACT FORM submit
// ==========================
function my_contact_form_handle_submission() {
    global $form_success, $form_submitted;

    if (isset($_POST['cf_submitted'])) {
        $name    = sanitize_text_field($_POST['cf_name']);
        $phone   = sanitize_text_field($_POST['cf_phone']);
        $email   = sanitize_email($_POST['cf_email']);
        $order   = sanitize_text_field($_POST['cf_order']);
        $message = sanitize_textarea_field($_POST['cf_message']);

        // Send email
        $to      = get_option('admin_email');        
        // $to = "meatitd9@gmail.com"; 
        $subject = "New Contact Form Message from $name";
        $headers = array(
           'Content-Type: text/html; charset=UTF-8',
           'From: My Website <noreply@yourdomain.com>', 
           "Reply-To: $email"
          );


        $body = "
            <strong>Name:</strong> $name <br>
            <strong>Phone:</strong> $phone <br>
            <strong>Email:</strong> $email <br>
            <strong>Order Number:</strong> $order <br><br>
            <strong>Message:</strong><br>
            $message
        ";

        wp_mail($to, $subject, $body, $headers);

        $form_success = '
        <div id="successPopup" class="success-popup">
            <div class="success-popup-content">
                
                <p>✅ Thank you, ' . esc_html($name) . '! Your message has been sent successfully.</p>
                <button id="closePopup">Close</button>
            </div>
        </div>
        
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const popup = document.getElementById("successPopup");
            popup.style.display = "flex";
            document.getElementById("closePopup").addEventListener("click", function() {
                popup.style.display = "none";
            });
            // Auto close after 5s
            setTimeout(() => popup.style.display = "none", 5000);
        });
        </script>
        ';
        

        $form_submitted = true;
    }
}
add_action('init', 'my_contact_form_handle_submission');


// ==========================
// Handle EMAIL-ONLY FORM submit
// ==========================
function my_email_form_handle_submission() {
    global $email_success, $email_submitted;

    if (isset($_POST['email_only_submitted'])) {
        $email = sanitize_email($_POST['email_only']);

        // Send email
        $to      = get_option('admin_email');
        // $to = "meatitd9@gmail.com";
        $subject = "New Email Subscription";
        $headers = array(
          'Content-Type: text/html; charset=UTF-8',
          'From: My Website <noreply@yourdomain.com>', 
          "Reply-To: $email"
       );

        $body = "
            <strong>New subscriber email:</strong> $email
        ";

        wp_mail($to, $subject, $body, $headers);

        $email_success = '
        <div id="successPopup" class="success-popup">
            <div class="success-popup-content">
                
                <p>🎉 Thank you! Your email has been submitted successfully.</p>
                <button id="closePopup">Close</button>
            </div>
        </div>
        
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const popup = document.getElementById("successPopup");
            popup.style.display = "flex";
            document.getElementById("closePopup").addEventListener("click", function() {
                popup.style.display = "none";
            });
            // Auto close after 5s
            setTimeout(() => popup.style.display = "none", 5000);
        });
        </script>
        ';
        

        $email_submitted = true;
    }
}
add_action('init', 'my_email_form_handle_submission');


// ==========================
// Shortcode: Contact Form
// ==========================
function my_custom_contact_form() {
    global $form_success, $form_submitted;
    ob_start();

    if (!empty($form_submitted)) {
        echo $form_success;
    } else { ?>
        <form method="post" class="my-contact-form">
            <div class="fields-wrap">
                <p class="name-field">
                    <label>Your Name *</label><br>
                    <input type="text" name="cf_name" required>
                </p>
                <p class="phone-field">
                    <label>Your Phone *</label><br>
                    <input type="text" name="cf_phone" required>
                </p>
            </div>
            <div class="fields-wrap">
                <p class="email-field">
                    <label>Your Email *</label><br>
                    <input type="email" name="cf_email" required>
                </p>
                <p class="order-nmbr-field">
                    <label>Your Order Number</label><br>
                    <input type="text" name="cf_order">
                </p>
            </div>
            <p class="message-field">
                <label>Message *</label><br>
                <textarea name="cf_message" rows="5" required></textarea>
            </p>
            <p>
                <button type="submit" name="cf_submitted" style="
                    background:#400080;
                    color:#fff;
                    padding:10px 20px;
                    border:none;
                    border-radius:50px;
                    cursor:pointer;
                    font-size:16px;">
                    Send Message
                </button>
            </p>
        </form>
    <?php }

    return ob_get_clean();
}
add_shortcode('my_contact_form', 'my_custom_contact_form');


// ==========================
// Shortcode: Email-only Form
// ==========================
function my_email_only_form() {
    global $email_success, $email_submitted;
    ob_start();

    if (!empty($email_submitted)) {
        echo $email_success;
    } else { ?>
        <form method="post" class="my-email-form" style="width:100%; display:flex; flex-wrap:wrap; gap:10px; margin:auto; text-align:center;">
            <input type="email" name="email_only" placeholder="Enter your email" required style="
                padding:12px;
                flex:1;
                border:1px solid #fff;
                border-radius:6px;
                background:transparent;
                color:white;
                ">
            <button type="submit" name="email_only_submitted" style="
                background:white;
                color:#6109B9;
                flex:1;
                padding:12px;
                border:none;
                border-radius:6px;
                cursor:pointer;
                font-size:16px;
                font-weight:bold;">
                Subscribe
            </button>
        </form>
    <?php }

    return ob_get_clean();
}
add_shortcode('my_email_form', 'my_email_only_form');



// search result

// 🔹 AJAX product search
function my_ajax_product_search() {
    $term = sanitize_text_field($_GET['term']);
    
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => 5,
        's'              => $term,
        'post_status'    => 'publish', 
    );
    
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        echo '<ul>';
        while ($query->have_posts()) {
            $query->the_post();
            global $product;
            echo '<li>
                <a href="' . get_the_permalink() . '">
                    ' . get_the_post_thumbnail(get_the_ID(), 'thumbnail') . '
                    <span>' . get_the_title() . '</span>
                    <span style="margin-left:auto;color:#666;">' . $product->get_price_html() . '</span>
                </a>
            </li>';
        }
        echo '</ul>';
    } else {
        echo '<p style="padding:10px;">No products found</p>';
    }

    wp_die(); // important to end AJAX
}
add_action('wp_ajax_product_search', 'my_ajax_product_search');
add_action('wp_ajax_nopriv_product_search', 'my_ajax_product_search');


// ---- AJAX: Load Category Posts ----
add_action('wp_ajax_lb_load_category_posts', 'lb_load_category_posts');
add_action('wp_ajax_nopriv_lb_load_category_posts', 'lb_load_category_posts');

function lb_load_category_posts() {
    check_ajax_referer('lb_nonce', 'nonce');
    
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 10;
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    
    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $posts_per_page,
        'paged'          => $page,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    
    if ($category_id) {
        $args['cat'] = $category_id;
    }
    
    $query = new WP_Query($args);
    
    ob_start();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            ?>
            <article class="post-item">
                <div class="post-thumbnail">
                    <a href="<?php the_permalink(); ?>">
                        <?php 
                        if (has_post_thumbnail()) {
                            the_post_thumbnail('medium');
                        } else {
                            echo '<img src="' . esc_url(get_template_directory_uri() . '/assets/images/fallback-thumbnail.jpg') . '" alt="' . get_the_title() . '" />';
                        }
                        ?>
                    </a>
                </div>
                
                <div class="post-content">
                    <div class="post-categories">
                        <?php the_category(', '); ?>
                    </div>
                    
                    <h2 class="entry-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h2>
                    
                    <div class="entry-meta">
                        <span class="posted-on"><?php echo get_the_date(); ?></span>
                    </div>
                    
                    <div class="entry-summary">
                        <?php 
                        $excerpt = get_the_excerpt();
                        $excerpt = wp_trim_words($excerpt, 25, '...');
                        echo '<p>' . $excerpt . '</p>';
                        ?>
                    </div>
                    
                    <div class="entry-footer">
                        <a href="<?php the_permalink(); ?>" class="read-more">Read More</a>
                    </div>
                </div>
            </article>
            <?php
        }
        
        $has_more = $query->max_num_pages > $page;
        
        wp_send_json_success(array(
            'html'      => ob_get_clean(),
            'has_more'  => $has_more,
            'next_page' => $page + 1
        ));
    } else {
        wp_send_json_success(array(
            'html'      => '<p>No posts found in this category.</p>',
            'has_more'  => false,
            'next_page' => $page
        ));
    }
    
    wp_reset_postdata();
    wp_die();
}

// Replace default WooCommerce sale badge with percentage discount
add_filter('woocommerce_sale_flash', 'custom_woocommerce_sale_percentage', 20, 3);

function custom_woocommerce_sale_percentage($html, $post, $product) {
    if ($product->is_type('variable')) {
        // For variable products (ranges of discounts)
        $percentages = [];
        $prices = $product->get_variation_prices();
        foreach ($prices['regular_price'] as $key => $regular_price) {
            $sale_price = $prices['sale_price'][$key];
            if ($sale_price < $regular_price) {
                $percentages[] = round((($regular_price - $sale_price) / $regular_price) * 100);
            }
        }
        if (!empty($percentages)) {
            $percentage = max($percentages); // Show max discount
            $html = '<span class="onsale">-' . $percentage . '%</span>';
        }
    } else {
        // For simple products
        $regular_price = (float) $product->get_regular_price();
        $sale_price = (float) $product->get_sale_price();
        if ($sale_price && $regular_price > 0) {
            $percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
            $html = '<span class="onsale">-' . $percentage . '%</span>';
        }
    }
    return $html;
}




add_action('woocommerce_single_product_summary', 'show_save_price_after_price', 11);

function show_save_price_after_price() {
    global $product;

    if (!$product->is_on_sale()) return; // Only show if product is on sale

    $regular_price = floatval($product->get_regular_price());
    $sale_price    = floatval($product->get_sale_price());
    $save_amount   = $regular_price - $sale_price;

    if ($save_amount > 0) {
        echo '<div class="you-save-box" >';
        echo 'Save $' . number_format($save_amount, 2);
        echo '</div>';
    }
}

/**
 * Redirect non-logged in users to Guest User page
 */
function redirect_non_logged_users_to_guest_page() {
    // Check if user is not logged in
    if (!is_user_logged_in()) {
        // Exceptions - pages that should remain accessible
        $exceptions = array(
            'guest-user',           // Your guest user page
            'my-account',           // WooCommerce My Account page
            'lost-password',        // Password reset
            'reset-password',       // Password reset
            // Add WooCommerce endpoints that should be accessible
            'wc-api',               // WooCommerce API
            'wc-auth',              // WooCommerce auth
        );
        
        // Get current page slug
        $current_page = get_post_field('post_name', get_post());
        
        // Don't redirect if on an exception page
        if (in_array($current_page, $exceptions)) {
            return;
        }
        
        // Don't redirect if on WooCommerce My Account page or endpoints
        if (is_account_page() || is_page($exceptions)) {
            return;
        }
        
        // Don't redirect AJAX requests (important for WooCommerce functionality)
        if (wp_doing_ajax()) {
            return;
        }
        
        // Don't redirect REST API requests
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }
        
        // Redirect to Guest User page instead of My Account
        // Check if guest-user page exists and get its URL
        $guest_page = get_page_by_path('guest-user');
        
        if ($guest_page && $guest_page->post_status === 'publish') {
            wp_redirect(get_permalink($guest_page->ID));
        } else {
            // Fallback to My Account page if guest-user page doesn't exist
            wp_redirect(wc_get_page_permalink('myaccount'));
        }
        exit;
    }
}
add_action('template_redirect', 'redirect_non_logged_users_to_guest_page');





// Include custom functionality
require_once get_template_directory() . '/inc/admin-builder.php';
require_once get_template_directory() . '/inc/coa-cpts.php';
require_once get_template_directory() . '/inc/customizer.php';

