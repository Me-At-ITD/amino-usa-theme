<?php
/**
 * Single Template for COA Page Post Type
 * URL: /pages/analysis/{coa-page-slug}/
 */

defined( 'ABSPATH' ) || exit;
get_header();

$coa_page_id = get_the_ID();
$coa_page_title = get_the_title();
?>

<style>
    .coa-page-wrapper {
        padding: 5%;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .coa-page-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .coa-columns {
        display: flex;
        flex-wrap: wrap;
        gap: 40px;
        margin-bottom: 50px;
    }
    
    .coa-lots {
        flex: 1;
        min-width: 300px;
    }
    
    .coa-product {
        flex: 1;
        min-width: 250px;
        text-align: center;
    }
    
    .coa-lots table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    
    .coa-lots th {
        background: #f8f8f8;
        padding: 12px;
        text-align: left;
        border-bottom: 2px solid #ddd;
        font-weight: 600;
    }
    
    .coa-lots td {
        padding: 12px;
        border-bottom: 1px solid #eee;
    }
    
    .coa-lots tr:hover {
        background: #f9f9f9;
    }
    
    .coa-lots a {
        color: #551a8b;
        text-decoration: none;
        font-weight: 500;
    }
    
    .coa-lots a:hover {
        text-decoration: underline;
    }
    
    .product-image {
        max-width: 200px;
        height: auto;
        margin: 0 auto 20px;
    }
    
    .product-button {
        display: inline-block;
        background: #551a8b;
        color: white;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 5px;
        margin-top: 10px;
    }
    
    .product-button:hover {
        background: #3d1263;
    }
    
    .coa-latest {
        background: #f8f8f8;
        padding: 30px;
        border-radius: 8px;
        margin-top: 40px;
    }
    
    .pdf-viewer {
        border: 1px solid #ddd;
        margin-top: 20px;
        border-radius: 5px;
        overflow: hidden;
    }
    
    @media (max-width: 768px) {
        .coa-columns {
            flex-direction: column;
        }
        
        .coa-lots, .coa-product {
            min-width: 100%;
        }
    }
</style>

<div class="coa-page-wrapper">
    <!-- Header Section -->
    <div class="coa-page-header">
        <h2 style="color: #666; font-weight: 300; margin-bottom: 10px;">Rigorous Third-Party Testing</h2>
        <h1 style="margin: 0 0 20px 0; color: #333;">Certificates of Analysis for <?php echo esc_html($coa_page_title); ?></h1>
        <p style="font-size: 16px; line-height: 1.6; color: #666; max-width: 800px; margin: 0 auto;">
            Every batch of our research chemicals undergoes comprehensive third-party testing 
            for identity, purity, and concentration to ensure the highest quality standards.
        </p>
    </div>

    <?php
    // 1. Get the linked product from this COA Page (same as grid)
    $product = get_field('product', $coa_page_id);
    $product_id = 0;
    $product_obj = null;
    
    if ($product) {
        if (is_object($product) && isset($product->ID)) {
            $product_id = $product->ID;
            $product_obj = wc_get_product($product_id);
        } elseif (is_numeric($product)) {
            $product_id = $product;
            $product_obj = wc_get_product($product_id);
        } elseif (is_array($product) && !empty($product)) {
            $first_product = $product[0];
            if (is_object($first_product) && isset($first_product->ID)) {
                $product_id = $first_product->ID;
                $product_obj = wc_get_product($product_id);
            } elseif (is_numeric($first_product)) {
                $product_id = $first_product;
                $product_obj = wc_get_product($product_id);
            }
        }
    }
    
    // 2. Get COA posts linked to this product (SAME LOGIC AS GRID)
    $lots = array();
    
    if ($product_id) {
        // Query COA posts that are linked to this product (same as grid)
        $coa_posts = get_posts(array(
            'post_type' => 'coa',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'product', // ACF relationship field
                    'value' => $product_id,
                    'compare' => 'LIKE'
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        foreach ($coa_posts as $coa_post) {
            $lot_number = get_field('lot_number', $coa_post->ID);
            $analysis_date = get_field('analysis_date', $coa_post->ID);
            $pdf_file = get_field('certificate_of_analysis_pdf_file', $coa_post->ID);
            
            // Get PDF URL (same as grid)
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
            
            if ($lot_number) {
                $lots[] = array(
                    'lot' => $lot_number,
                    'date' => $analysis_date ? $analysis_date : get_the_date('Y-m-d', $coa_post->ID),
                    'pdf' => $pdf_url,
                    'coa_id' => $coa_post->ID
                );
            }
        }
    }
    
    // 3. If no COA posts found via product relationship, try the repeater field as fallback
    if (empty($lots)) {
        $coa_history = get_field('coa_history', $coa_page_id);
        
        if ($coa_history && is_array($coa_history)) {
            foreach ($coa_history as $history_item) {
                if (isset($history_item['lot_numbers_from_coa']) && $history_item['lot_numbers_from_coa']) {
                    $coa_post = $history_item['lot_numbers_from_coa'];
                    
                    if (is_object($coa_post) && isset($coa_post->ID)) {
                        $lot_number = get_field('lot_number', $coa_post->ID);
                        $analysis_date = get_field('analysis_date', $coa_post->ID);
                        $pdf_file = get_field('certificate_of_analysis_pdf_file', $coa_post->ID);
                        
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
                        
                        if ($lot_number) {
                            $lots[] = array(
                                'lot' => $lot_number,
                                'date' => $analysis_date ? $analysis_date : get_the_date('Y-m-d', $coa_post->ID),
                                'pdf' => $pdf_url,
                                'coa_id' => $coa_post->ID
                            );
                        }
                    }
                }
            }
        }
    }
    
    // Sort lots by date descending
    usort($lots, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    ?>

    <div class="coa-columns">
        <!-- Left Column: Lot Numbers Table -->
        <div class="coa-lots">
            <h3>Certificates by Lot Number</h3>
            <?php if (!empty($lots)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Lot Number</th>
                            <th>Analysis Date</th>
                            <th>Certificate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lots as $lot): ?>
                            <tr>
                                <td><strong><?php echo esc_html($lot['lot']); ?></strong></td>
                                <td><?php echo esc_html(date('F j, Y', strtotime($lot['date']))); ?></td>
                                <td>
                                    <?php if ($lot['pdf']): ?>
                                        <a href="<?php echo esc_url($lot['pdf']); ?>" target="_blank" rel="noopener">View PDF</a>
                                    <?php else: ?>
                                        <span style="color: #999;">Not Available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No certificates found for this product.</p>
                <p><em>This product may not have any COA entries linked to it.</em></p>
            <?php endif; ?>
        </div>

        <!-- Right Column: Product Information -->
        <div class="coa-product">
            <?php if ($product_obj): ?>
                <div class="product-image"><?php echo $product_obj->get_image('medium'); ?></div>
                <h3><?php echo esc_html($product_obj->get_name()); ?></h3>
                <a class="product-button" href="<?php echo get_permalink($product_id); ?>">View Product Details</a>
            <?php else: ?>
                <p>No product linked to this COA page.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($lots)): 
        $latest_lot = $lots[0]; ?>
        <div class="coa-latest">
            <h3>Latest Certificate of Analysis</h3>
            <p>Lot number <strong><?php echo esc_html($latest_lot['lot']); ?></strong> tested on <strong><?php echo esc_html(date('F j, Y', strtotime($latest_lot['date']))); ?></strong>.</p>
            <?php if ($latest_lot['pdf']): ?>
                <div class="pdf-viewer">
                    <embed src="<?php echo esc_url($latest_lot['pdf']); ?>#toolbar=0&navpanes=0&scrollbar=0" type="application/pdf" width="100%" height="600px" />
                </div>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="<?php echo esc_url($latest_lot['pdf']); ?>" target="_blank" class="product-button">Download PDF Certificate</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 50px; padding-top: 30px; border-top: 1px solid #eee;">
        <a href="<?php echo home_url('/coa-library/'); ?>" style="color: #551a8b; text-decoration: none;">← Back to COA Library</a>
    </div>
</div>

<?php get_footer(); ?>