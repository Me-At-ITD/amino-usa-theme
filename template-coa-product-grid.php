<?php
/**
 * Template Name: COA Product Grid
 * Description: Shows a grid of products that have COA entries with search functionality
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
        'coa_pages' => array(), // COA Pages that reference this product
        'coas' => array(),      // COA posts that reference this product
    );
}

// 3) Find COA posts and link them to products
$coa_posts = get_posts(array(
    'post_type' => 'coa',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
));

foreach ($coa_posts as $coa_post) {
    // Get product linked to this COA post
    $product = get_field('product', $coa_post->ID);
    
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
        // Get COA data
        $lot = get_field('lot_number', $coa_post->ID);
        $pdf_file = get_field('certificate_of_analysis_pdf_file', $coa_post->ID);
        $analysis_date = get_field('analysis_date', $coa_post->ID);
        
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
        
        $products_data[$product_id]['coas'][] = array(
            'coa_id' => $coa_post->ID,
            'title' => get_the_title($coa_post),
            'lot' => $lot,
            'pdf_url' => $pdf_url,
            'date' => $analysis_date ? date('Y-m-d', strtotime($analysis_date)) : get_the_date('Y-m-d', $coa_post),
        );
    }
}

// 4) Try to find COA Pages
$coa_page_posts = get_posts(array(
    'post_type' => 'coa-page',
    'posts_per_page' => -1,
    'post_status' => array('publish', 'private', 'draft'),
));

foreach ($coa_page_posts as $coa_page) {
    // Get the product linked to this COA Page
    $product = get_field('product', $coa_page->ID);
    
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
        $products_data[$product_id]['coa_pages'][] = $coa_page;
    }
}

// 5) Filter products that have COAs
$products_with_coas = array_filter($products_data, function($data) {
    return !empty($data['coas']);
});

// 6) Handle search functionality
$search_term = isset($_GET['coa_search']) ? sanitize_text_field($_GET['coa_search']) : '';

if (!empty($search_term)) {
    $filtered_products = array();
    
    foreach ($products_with_coas as $product_id => $product_data) {
        $product_name = strtolower(get_the_title($product_id));
        $product_matches = stripos($product_name, strtolower($search_term)) !== false;
        
        $lot_matches = false;
        foreach ($product_data['coas'] as $coa) {
            if (stripos($coa['lot'], $search_term) !== false) {
                $lot_matches = true;
                break;
            }
        }
        
        // Include product if either product name or lot number matches
        if ($product_matches || $lot_matches) {
            $filtered_products[$product_id] = $product_data;
        }
    }
    
    $products_with_coas = $filtered_products;
}

// 7) Output the grid
?>

<style>
    .page-template-template-coa-product-grid-php .site-content{
        padding:5%;
    }
    
    .coa-search-container {
        margin: 30px 0;
        max-width: 100%;
    }
    
    .coa-search-form {
        display: flex;
        gap: 10px;
    }
    
    .coa-search-input {
        flex: 1;
        padding: 12px 15px;
        border: 2px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
        outline: none;
        transition: border-color 0.3s;
    }
    
    .coa-search-input:focus {
        border-color: #551a8b;
    }
    
    .coa-search-button {
        background: #551a8b;
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        transition: background 0.3s;
    }
    
    .coa-search-button:hover {
        background: #3d1263;
    }
    
    .search-results-info {
        margin: 15px 0;
        color: #666;
        font-style: italic;
    }
    
    .coa-grid { 
        display:flex; 
        flex-wrap:wrap; 
        gap:18px; 
        margin-top: 20px;
    }
    
    .coa-card { 
        width: calc(33.3% - 15px); 
        border:1px solid #eee; 
        padding:12px; 
        border-radius:8px; 
        box-shadow:0 1px 3px rgba(0,0,0,.04);
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .coa-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .coa-card img { 
        max-width:100%; 
        height:140px; 
        object-fit:contain; 
        display:block; 
        margin-bottom:8px; 
    }
    .coa-card h3 {
        margin:0px;
    }
    .coa-card h3 a{ 
        font-size: 19px;
font-weight: 600;
line-height: 28px;
color: rgb(17, 24, 39);
        margin:6px 0 10px; 
    }
    
    .coa-buttons { 
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
    
    .coa-buttons:hover { 
        background:#ddd; 
    }
    
    .coa-lot-list { 
        display:flex; 
        flex-wrap:wrap; 
        gap:6px; 
        margin-top:0px; 
        padding-top:8px; 
    }
    
    .coa-lot-btn { 
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
    
    .coa-lot-btn:hover { 
        background:#ddd; 
    }
    
    .coa-lot-btn.disabled { 
        opacity: 0.6; 
        pointer-events: none; 
    }

    @media (max-width:900px){ 
        .coa-card{ width: calc(50% - 18px); } 
    }
    
    @media (max-width:480px){ 
        .coa-card{ width:100%; } 
        .coa-search-form { flex-direction: column; }
    }
    
    .coa-cats { 
        display: flex;
        flex-wrap: wrap;
        gap: 5px; 
        margin: 5px 0;
    }
    
    .coa-cat { 
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
    
    .coa-cat:hover { 
        background:#ddd; 
    }
    
    .no-results {
        text-align: center;
        padding: 50px;
        color: #666;
    }
</style>

<h1 style="margin-top:0px;">COA Library</h1>

<!-- Search Form -->
<div class="coa-search-container">
    <form method="GET" class="coa-search-form">
        <input type="text" 
               name="coa_search" 
               class="coa-search-input" 
               placeholder="Search by product name or lot number..." 
               value="<?php echo esc_attr($search_term); ?>"
               aria-label="Search COA Library">
        <button type="submit" class="coa-search-button">Search</button>
    </form>
    
    <?php if (!empty($search_term)): ?>
        <div class="search-results-info">
            <?php 
            $result_count = count($products_with_coas);
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

<?php if (empty($products_with_coas)): ?>
    <div class="no-results">
        <h2><?php echo empty($search_term) ? 'No products with COA entries found.' : 'No products found'; ?></h2>
        <?php if (empty($search_term)): ?>
            <p>This could be because:</p>
            <ul style="text-align: left; display: inline-block;">
                <li>COA posts are not linked to products via ACF fields</li>
                <li>COA posts exist but have no product relationships set</li>
                <li>There's an issue with the ACF field names</li>
            </ul>
        <?php else: ?>
            <p>Try searching with different terms or <a href="?" style="color: #551a8b;">clear your search</a>.</p>
        <?php endif; ?>
    </div>
    <?php get_footer(); ?>
    <?php return; ?>
<?php endif; ?>

<div class="coa-grid">
<?php foreach ($products_with_coas as $product_id => $pdata): 
    $product = $pdata['post'];
    $prod_link = get_permalink($product_id);
    $thumb = get_the_post_thumbnail($product_id, 'medium');
    
    if (!$thumb) {
        $thumb = '<img src="' . wc_placeholder_img_src() . '" alt="' . esc_attr(get_the_title($product_id)) . '"/>';
    }
    
    // Get COA Page URL
    $coa_page_url = '';
    $coa_page_text = 'View COA History';
    
    if (!empty($pdata['coa_pages'])) {
        $coa_page_url = get_permalink($pdata['coa_pages'][0]->ID);
    } else {
        $product_slug = $product->post_name;
        $coa_page_url = home_url('/analysis/' . $product_slug . '/');
        $coa_page_text = 'COA History';
    }
    
    $coas = $pdata['coas'];
    
    // Sort COAs by date descending
    usort($coas, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
?>
   <article class="coa-card" data-product-id="<?php echo esc_attr($product_id); ?>">
        <a href="<?php echo esc_url($prod_link); ?>"><?php echo $thumb; ?></a>
        
        <?php
        // Show product categories
        $terms = get_the_terms($product_id, 'product_cat');
        if ($terms && !is_wp_error($terms)) {
            echo '<div class="coa-cats">';
            foreach ($terms as $t) {
                $link = get_term_link($t);
                if (!is_wp_error($link)) {
                    echo '<a class="coa-cat" href="' . esc_url($link) . '">' . esc_html($t->name) . '</a>';
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
            <a class="coa-buttons" href="<?php echo esc_url($coa_page_url); ?>">
                <?php echo $coa_page_text; ?>
            </a>
        </div>

        <?php if (!empty($coas)): ?>
            <p style="margin-bottom:5px; font-size:13px; font-weight:600;">Available Lot Numbers:</p>
            <div class="coa-lot-list">
                <?php foreach ($coas as $entry): 
                    $lot_label = $entry['lot'] ? esc_html($entry['lot']) : 'Lot #' . esc_html($entry['coa_id']);
                    $pdf_url = $entry['pdf_url'] ? esc_url($entry['pdf_url']) : '';
                ?>
                    <?php if ($pdf_url): ?>
                        <a class="coa-lot-btn" href="<?php echo $pdf_url; ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo $lot_label; ?>
                        </a>
                    <?php else: ?>
                        <span class="coa-lot-btn disabled"><?php echo $lot_label; ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
<?php endforeach; ?>
</div>

<?php get_footer(); ?>