<?php
/**
 * Single Template for Endotoxin Page Post Type
 * URL: /pages/endotoxin/{endotoxin-page-slug}/
 */

defined( 'ABSPATH' ) || exit;
get_header();

$endotoxin_page_id = get_the_ID();
$endotoxin_page_title = get_the_title();
?>

<style>
    .endotoxin-page-wrapper {
        padding: 5%;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .endotoxin-page-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .endotoxin-columns {
        display: flex;
        flex-wrap: wrap;
        gap: 40px;
        margin-bottom: 50px;
    }
    
    .endotoxin-reports {
        flex: 1;
        min-width: 300px;
    }
    
    .endotoxin-product {
        flex: 1;
        min-width: 250px;
        text-align: center;
    }
    
    .endotoxin-reports table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    
    .endotoxin-reports th {
        background: #f8f8f8;
        padding: 12px;
        text-align: left;
        border-bottom: 2px solid #ddd;
        font-weight: 600;
    }
    
    .endotoxin-reports td {
        padding: 12px;
        border-bottom: 1px solid #eee;
    }
    
    .endotoxin-reports tr:hover {
        background: #f9f9f9;
    }
    
    .endotoxin-reports a {
        color: #551a8b;
        text-decoration: none;
        font-weight: 500;
    }
    
    .endotoxin-reports a:hover {
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
    
    .endotoxin-latest {
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
        .endotoxin-columns {
            flex-direction: column;
        }
        
        .endotoxin-reports, .endotoxin-product {
            min-width: 100%;
        }
    }
</style>

<div class="endotoxin-page-wrapper">
    <!-- Header Section -->
    <div class="endotoxin-page-header">
        <h2 style="color: #666; font-weight: 300; margin-bottom: 10px;">Rigorous Endotoxin Testing</h2>
        <h1 style="margin: 0 0 20px 0; color: #333;">Endotoxin Reports for <?php echo esc_html($endotoxin_page_title); ?></h1>
        <p style="font-size: 16px; line-height: 1.6; color: #666; max-width: 800px; margin: 0 auto;">
            Every batch undergoes comprehensive endotoxin testing to ensure the highest quality standards 
            and safety for research applications.
        </p>
    </div>

    <?php
    // 1. Get the linked product from this Endotoxin Page
    $product = get_field('product', $endotoxin_page_id);
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
    
    // 2. Get Endotoxin posts linked to this product
    $reports = array();
    
    if ($product_id) {
        // Query Endotoxin posts that are linked to this product
        $endotoxin_posts = get_posts(array(
            'post_type' => 'endotoxin',
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
        
        foreach ($endotoxin_posts as $endotoxin_post) {
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
            
            $reports[] = array(
                'date' => $analysis_date ? $analysis_date : get_the_date('Y-m-d', $endotoxin_post->ID),
                'pdf' => $pdf_url,
                'endotoxin_id' => $endotoxin_post->ID
            );
        }
    }
    
    // 3. If no Endotoxin posts found via product relationship, try the repeater field as fallback
    if (empty($reports)) {
        $endotoxin_history = get_field('endotoxin_history', $endotoxin_page_id);
        
        if ($endotoxin_history && is_array($endotoxin_history)) {
            foreach ($endotoxin_history as $history_item) {
                if (isset($history_item['lot_numbers_from_coa']) && $history_item['lot_numbers_from_coa']) {
                    $endotoxin_post = $history_item['lot_numbers_from_coa'];
                    
                    if (is_object($endotoxin_post) && isset($endotoxin_post->ID)) {
                        $analysis_date = get_field('analysis_date', $endotoxin_post->ID);
                        $pdf_file = get_field('certificate_of_analysis_pdf_file', $endotoxin_post->ID);
                        
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
                        
                        $reports[] = array(
                            'date' => $analysis_date ? $analysis_date : get_the_date('Y-m-d', $endotoxin_post->ID),
                            'pdf' => $pdf_url,
                            'endotoxin_id' => $endotoxin_post->ID
                        );
                    }
                }
            }
        }
    }
    
    // Sort reports by date descending
    usort($reports, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    ?>

    <div class="endotoxin-columns">
        <!-- Left Column: Reports Table -->
        <div class="endotoxin-reports">
            <h3>Endotoxin Reports</h3>
            <?php if (!empty($reports)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Analysis Date</th>
                            <th>Report</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><strong><?php echo esc_html(date('F j, Y', strtotime($report['date']))); ?></strong></td>
                                <td>
                                    <?php if ($report['pdf']): ?>
                                        <a href="<?php echo esc_url($report['pdf']); ?>" target="_blank" rel="noopener">View PDF</a>
                                    <?php else: ?>
                                        <span style="color: #999;">Not Available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No endotoxin reports found for this product.</p>
                <p><em>This product may not have any endotoxin entries linked to it.</em></p>
            <?php endif; ?>
        </div>

        <!-- Right Column: Product Information -->
        <div class="endotoxin-product">
            <?php if ($product_obj): ?>
                <div class="product-image"><?php echo $product_obj->get_image('medium'); ?></div>
                <h3><?php echo esc_html($product_obj->get_name()); ?></h3>
                <a class="product-button" href="<?php echo get_permalink($product_id); ?>">View Product Details</a>
            <?php else: ?>
                <p>No product linked to this endotoxin page.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($reports)): 
        $latest_report = $reports[0]; ?>
        <div class="endotoxin-latest">
            <h3>Latest Endotoxin Report</h3>
            <p>Tested on <strong><?php echo esc_html(date('F j, Y', strtotime($latest_report['date']))); ?></strong>.</p>
            <?php if ($latest_report['pdf']): ?>
                <div class="pdf-viewer">
                    <embed src="<?php echo esc_url($latest_report['pdf']); ?>#toolbar=0&navpanes=0&scrollbar=0" type="application/pdf" width="100%" height="600px" />
                </div>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="<?php echo esc_url($latest_report['pdf']); ?>" target="_blank" class="product-button">Download PDF Report</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 50px; padding-top: 30px; border-top: 1px solid #eee;">
        <a href="<?php echo home_url('/endotoxin-library/'); ?>" style="color: #551a8b; text-decoration: none;">← Back to Endotoxin Library</a>
    </div>
</div>

<?php get_footer(); ?>