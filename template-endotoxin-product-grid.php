<?php
/**
 * Template Name: Endotoxin Product Grid
 * Description: Shows a grid of products that have Endotoxin entries with search functionality
 */

defined( 'ABSPATH' ) || exit;
get_header();

// 1) Get all published products
$all_products = get_posts(array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
));

// 2) Build products data structure
$products_data = array();

foreach ($all_products as $product) {
    $products_data[$product->ID] = array(
        'post' => $product,
        'endotoxin_pages' => array(), // Endotoxin Pages that reference this product
        'endotoxins' => array(),      // Endotoxin posts that reference this product
    );
}

// 3) Find Endotoxin posts and link them to products
$endotoxin_posts = get_posts(array(
    'post_type' => 'endotoxin',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
));

foreach ($endotoxin_posts as $endotoxin_post) {
    // Get product linked to this Endotoxin post
    $product = get_field('product', $endotoxin_post->ID);
    
    $product_id = 0;
    if ($product) {
        if (is_object($product) && isset($product->ID)) {
            $product_id = $product->ID;
        } elseif (is_numeric($product)) {
            $product_id = $product;
        } elseif (is_array($product) && !empty($product)) {
            // Handle array of products (take first one)
            $first_product = $product[0];
            if (is_object($first_product) && isset($first_product->ID)) {
                $product_id = $first_product->ID;
            } elseif (is_numeric($first_product)) {
                $product_id = $first_product;
            }
        }
    }
    
    if ($product_id && isset($products_data[$product_id])) {
        // Get Endotoxin data
        $analysis_date = get_field('analysis_date', $endotoxin_post->ID);
        $pdf_file = get_field('certificate_of_analysis_pdf_file', $endotoxin_post->ID);
        
        // Get PDF URL
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
        
        $products_data[$product_id]['endotoxins'][] = array(
            'endotoxin_id' => $endotoxin_post->ID,
            'title' => get_the_title($endotoxin_post),
            'pdf_url' => $pdf_url,
            'date' => $analysis_date ? date('Y-m-d', strtotime($analysis_date)) : get_the_date('Y-m-d', $endotoxin_post),
        );
    }
}

// 4) Try to find Endotoxin Pages
$endotoxin_page_posts = get_posts(array(
    'post_type' => 'endotoxin-page',
    'posts_per_page' => -1,
    'post_status' => array('publish', 'private', 'draft'),
));

foreach ($endotoxin_page_posts as $endotoxin_page) {
    // Get the product linked to this Endotoxin Page
    $product = get_field('product', $endotoxin_page->ID);
    
    $product_id = 0;
    if ($product) {
        if (is_object($product) && isset($product->ID)) {
            $product_id = $product->ID;
        } elseif (is_numeric($product)) {
            $product_id = $product;
        } elseif (is_array($product) && !empty($product)) {
            $first_product = $product[0];
            if (is_object($first_product) && isset($first_product->ID)) {
                $product_id = $first_product->ID;
            } elseif (is_numeric($first_product)) {
                $product_id = $first_product;
            }
        }
    }
    
    if ($product_id && isset($products_data[$product_id])) {
        $products_data[$product_id]['endotoxin_pages'][] = $endotoxin_page;
    }
}

// 5) Filter products that have Endotoxins
$products_with_endotoxins = array_filter($products_data, function($data) {
    return !empty($data['endotoxins']);
});

// 6) Handle search functionality
$search_term = isset($_GET['endotoxin_search']) ? sanitize_text_field($_GET['endotoxin_search']) : '';

if (!empty($search_term)) {
    $filtered_products = array();
    
    foreach ($products_with_endotoxins as $product_id => $product_data) {
        $product_name = strtolower(get_the_title($product_id));
        $product_matches = stripos($product_name, strtolower($search_term)) !== false;
        
        // For endotoxin, we only search by product name since there are no lot numbers
        if ($product_matches) {
            $filtered_products[$product_id] = $product_data;
        }
    }
    
    $products_with_endotoxins = $filtered_products;
}

// 7) Output the grid
?>

<style>
    .page-template-template-endotoxin-product-grid-php .site-content{
        padding:5%;
    }
    
    .endotoxin-search-container {
        margin: 30px 0;
        max-width: 100%;
    }
    
    .endotoxin-search-form {
        display: flex;
        gap: 10px;
    }
    
    .endotoxin-search-input {
        flex: 1;
        padding: 12px 15px;
        border: 2px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
        outline: none;
        transition: border-color 0.3s;
    }
    
    .endotoxin-search-input:focus {
        border-color: #551a8b;
    }
    
    .endotoxin-search-button {
        background: #551a8b;
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        transition: background 0.3s;
    }
    
    .endotoxin-search-button:hover {
        background: #3d1263;
    }
    
    .search-results-info {
        margin: 15px 0;
        color: #666;
        font-style: italic;
    }
    
    .endotoxin-grid { 
        display:flex; 
        flex-wrap:wrap; 
        gap:18px; 
        margin-top: 20px;
    }
    
    .endotoxin-card { 
        width: calc(33.3% - 15px); 
        border:1px solid #eee; 
        padding:12px; 
        border-radius:8px; 
        box-shadow:0 1px 3px rgba(0,0,0,.04);
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .endotoxin-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .endotoxin-card img { 
        max-width:100%; 
        height:140px; 
        object-fit:contain; 
        display:block; 
        margin-bottom:8px; 
    }
    .endotoxin-card h3 {
        margin:0px;
    }
    .endotoxin-card h3 a{ 
        font-size: 19px;
font-weight: 600;
line-height: 28px;
color: rgb(17, 24, 39);
        margin:6px 0 10px; 
    }
    
    .endotoxin-buttons { 
        font-size: 13px;
        font-weight: 500;
        line-height: 20px;
        color: #551a8b;
        background:#edeaf2;
        padding:0.25rem 1.5rem;
        width: fit-content; 
        border-radius:50px;
        text-decoration: none;
        display: inline-block;
        margin: 10px 0;
        transition: background 0.3s;
    }
    
    .endotoxin-buttons:hover { 
        background:#ddd; 
    }
    
    .endotoxin-list { 
        display:flex; 
        flex-wrap:wrap; 
        gap:6px; 
        margin-top:0px; 
        padding-top:8px; 
    }
    
    .endotoxin-btn { 
        font-weight: 500;
        line-height: 20px;
        font-size:13px;
        color: #551a8b;
        background:#edeaf2;
        padding:0.25rem 1.5rem;
        width: fit-content; 
        border-radius:50px;
        text-decoration: none;
        display: inline-block;
        transition: background 0.3s;
    }
    
    .endotoxin-btn:hover { 
        background:#ddd; 
    }
    
    .endotoxin-btn.disabled { 
        opacity: 0.6; 
        pointer-events: none; 
    }

    @media (max-width:900px){ 
        .endotoxin-card{ width: calc(50% - 18px); } 
    }
    
    @media (max-width:480px){ 
        .endotoxin-card{ width:100%; } 
        .endotoxin-search-form { flex-direction: column; }
    }
    
    .endotoxin-cats { 
        display: flex;
        flex-wrap: wrap;
        gap: 5px; 
        margin: 5px 0;
    }
    
    .endotoxin-cat { 
        background:#f4f4f4; 
        padding:2px 6px; 
        border-radius:4px; 
        font-size: 10px;
        font-weight: 400;
        line-height: 16px;
        color: #551a8b;
        text-decoration:none; 
        text-transform:uppercase;
        transition: background 0.3s;
    }
    
    .endotoxin-cat:hover { 
        background:#ddd; 
    }
    
    .no-results {
        text-align: center;
        padding: 50px;
        color: #666;
    }
</style>

<h1 style="margin-top:0px;">Endotoxin Library</h1>

<!-- Search Form -->
<div class="endotoxin-search-container">
    <form method="GET" class="endotoxin-search-form">
        <input type="text" 
               name="endotoxin_search" 
               class="endotoxin-search-input" 
               placeholder="Search by product name..." 
               value="<?php echo esc_attr($search_term); ?>"
               aria-label="Search Endotoxin Library">
        <button type="submit" class="endotoxin-search-button">Search</button>
    </form>
    
    <?php if (!empty($search_term)): ?>
        <div class="search-results-info">
            <?php 
            $result_count = count($products_with_endotoxins);
            if ($result_count > 0) {
                echo 'Found ' . $result_count . ' product' . ($result_count !== 1 ? 's' : '') . ' matching "' . esc_html($search_term) . '"';
            } else {
                echo 'No products found matching "' . esc_html($search_term) . '"';
            }
            ?>
            <?php if (!empty($search_term)): ?>
                <br><a href="?" style="color: #551a8b; text-decoration: none;">← Clear search</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (empty($products_with_endotoxins)): ?>
    <div class="no-results">
        <h2><?php echo empty($search_term) ? 'No products with Endotoxin entries found.' : 'No products found'; ?></h2>
        <?php if (empty($search_term)): ?>
            <p>This could be because:</p>
            <ul style="text-align: left; display: inline-block;">
                <li>Endotoxin posts are not linked to products via ACF fields</li>
                <li>Endotoxin posts exist but have no product relationships set</li>
                <li>There's an issue with the ACF field names</li>
            </ul>
        <?php else: ?>
            <p>Try searching with different terms or <a href="?" style="color: #551a8b;">clear your search</a>.</p>
        <?php endif; ?>
    </div>
    <?php get_footer(); ?>
    <?php return; ?>
<?php endif; ?>

<div class="endotoxin-grid">
<?php foreach ($products_with_endotoxins as $product_id => $pdata): 
    $product = $pdata['post'];
    $prod_link = get_permalink($product_id);
    $thumb = get_the_post_thumbnail($product_id, 'medium');
    
    if (!$thumb) {
        $thumb = '<img src="' . wc_placeholder_img_src() . '" alt="' . esc_attr(get_the_title($product_id)) . '"/>';
    }
    
    // Get Endotoxin Page URL
    $endotoxin_page_url = '';
    $endotoxin_page_text = 'View Endotoxin History';
    
    if (!empty($pdata['endotoxin_pages'])) {
        $endotoxin_page_url = get_permalink($pdata['endotoxin_pages'][0]->ID);
    } else {
        $product_slug = $product->post_name;
        $endotoxin_page_url = home_url('/endotoxin/' . $product_slug . '/');
        $endotoxin_page_text = 'Endotoxin History';
    }
    
    $endotoxins = $pdata['endotoxins'];
    
    // Sort Endotoxins by date descending
    usort($endotoxins, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
?>
   <article class="endotoxin-card" data-product-id="<?php echo esc_attr($product_id); ?>">
        <a href="<?php echo esc_url($prod_link); ?>"><?php echo $thumb; ?></a>
        
        <?php
        // Show product categories
        $terms = get_the_terms($product_id, 'product_cat');
        if ($terms && !is_wp_error($terms)) {
            echo '<div class="endotoxin-cats">';
            foreach ($terms as $t) {
                $link = get_term_link($t);
                if (!is_wp_error($link)) {
                    echo '<a class="endotoxin-cat" href="' . esc_url($link) . '">' . esc_html($t->name) . '</a>';
                }
            }
            echo '</div>';
        }
        ?>
        
        <h3>
            <a href="<?php echo esc_url($prod_link); ?>">
                <?php echo esc_html(get_the_title($product_id)); ?>
            </a>
        </h3>

        <div>
            <a class="endotoxin-buttons" href="<?php echo esc_url($endotoxin_page_url); ?>">
                <?php echo $endotoxin_page_text; ?>
            </a>
        </div>

        <?php if (!empty($endotoxins)): ?>
            <p style="margin-bottom:5px; font-size:13px; font-weight:600;">Available Reports:</p>
            <div class="endotoxin-list">
                <?php foreach ($endotoxins as $entry): 
                    $report_label = 'Report ' . date('M j, Y', strtotime($entry['date']));
                    $pdf_url = $entry['pdf_url'] ? esc_url($entry['pdf_url']) : '';
                ?>
                    <?php if ($pdf_url): ?>
                        <a class="endotoxin-btn" href="<?php echo $pdf_url; ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo $report_label; ?>
                        </a>
                    <?php else: ?>
                        <span class="endotoxin-btn disabled"><?php echo $report_label; ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
<?php endforeach; ?>
</div>

<?php get_footer(); ?>