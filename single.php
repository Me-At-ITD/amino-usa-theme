<?php
/**
 * The template for displaying all single posts
 */
get_header();
?>

<div class="single-post-container">
    <?php while (have_posts()) : the_post(); ?>
    
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <!-- Post Title -->
        <header class="post-header">
            <h1 class="post-title"><?php the_title(); ?></h1>
            <div class="post-meta">
                <span class="post-date"><?php echo get_the_date(); ?></span>
                <span class="post-author">By <?php the_author(); ?></span>
                <span class="post-categories"><?php the_category(', '); ?></span>
            </div>
        </header>

        <!-- Featured Image -->
        <?php if (has_post_thumbnail()) : ?>
        <div class="post-featured-image">
            <?php the_post_thumbnail('large', array('alt' => get_the_title())); ?>
        </div>
        <?php endif; ?>

        <!-- Post Content -->
        <div class="post-content">
            <?php the_content(); ?>
        </div>

        <!-- Post Tags -->
        <footer class="post-footer">
            <?php if (has_tag()) : ?>
            <div class="post-tags">
                <span>Tags: </span><?php the_tags('', ', ', ''); ?>
            </div>
            <?php endif; ?>
        </footer>
    </article>

    <?php endwhile; ?>

    <!-- Related Posts -->
    <div class="related-posts">
        <h2 class="related-posts-title">Related Posts</h2>
        <div class="related-posts-grid">
            <?php
            $categories = get_the_category();
            $category_ids = array();
            
            foreach ($categories as $category) {
                $category_ids[] = $category->term_id;
            }
            
            $related_args = array(
                'category__in' => $category_ids,
                'post__not_in' => array(get_the_ID()),
                'posts_per_page' => 3,
                'orderby' => 'rand'
            );
            
            $related_query = new WP_Query($related_args);
            
            if ($related_query->have_posts()) :
                while ($related_query->have_posts()) : $related_query->the_post();
            ?>
            <article class="related-post">
                <?php if (has_post_thumbnail()) : ?>
                <a href="<?php the_permalink(); ?>" class="related-post-thumbnail">
                    <?php the_post_thumbnail('medium', array('alt' => get_the_title())); ?>
                </a>
                <?php endif; ?>
                
                <div class="related-post-content">
                    <h3 class="related-post-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>
                    <div class="related-post-excerpt">
                        <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                    </div>
                    <a href="<?php the_permalink(); ?>" class="read-more">Read More</a>
                </div>
            </article>
            <?php
                endwhile;
                wp_reset_postdata();
            else :
                echo '<p>No related posts found.</p>';
            endif;
            ?>
        </div>
    </div>
</div>

<?php
get_footer();