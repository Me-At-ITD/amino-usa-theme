<?php
/**
 * The template for displaying category archives
 * Matches the design of the blog page
 */

get_header(); ?>

<div class="container category-layout">
    
    <!-- Page Header -->
    <header class="page-header">
        <h1 class="page-title"><?php single_cat_title(); ?></h1>
        <?php if (category_description()) : ?>
            <div class="category-description"><?php echo category_description(); ?></div>
        <?php endif; ?>
    </header>

    <div class="posts-wrapper">
        
        <!-- 🔹 Left Column (Latest Posts) -->
        <div class="latest-posts">
            <h2>Latest Posts</h2>
            <?php if (have_posts()) : ?>
                <?php while (have_posts()) : the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('post-card'); ?>>
                        <div class="post-thumb">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('medium'); ?>
                            </a>
                        </div>
                        <div class="post-body">
                            <span class="post-cat"><?php the_category(', '); ?></span>
                            <h3 class="post-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <p class="post-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 25, '...'); ?></p>
                            <a href="<?php the_permalink(); ?>" class="read-more">Read More</a>
                            <span class="post-date"><?php echo get_the_date(); ?></span>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else : ?>
                <p>No posts found in this category.</p>
            <?php endif; ?>
        </div>

        <!-- 🔹 Right Column (Featured Posts) -->
        <div class="featured-posts">
            <h2>Featured Posts</h2>
            <?php
            // Get top 3 posts in same category
       // Get latest 3 posts in this category
$featured_query = new WP_Query(array(
    'cat' => get_queried_object_id(),
    'posts_per_page' => 3,
));

            if ($featured_query->have_posts()) :
                while ($featured_query->have_posts()) : $featured_query->the_post(); ?>
                    <div class="featured-card">
                        <a href="<?php the_permalink(); ?>">
                            <?php the_post_thumbnail('medium'); ?>
                            <h3><?php the_title(); ?></h3>
                        </a>
                        <p><?php echo wp_trim_words(get_the_excerpt(), 15, '...'); ?></p>
                        <span class="post-date"><?php echo get_the_date(); ?></span>
                    </div>
                <?php endwhile;
                wp_reset_postdata();
            endif;
            ?>
        </div>

    </div>
</div>

<style>
.category-layout {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}
.page-header {
    text-align: center;
    margin: 30px 0;
}
.posts-wrapper {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}
.latest-posts h2, .featured-posts h2 {
    margin-bottom: 20px;
}
.post-card {
    display: flex;
    background: #fff;
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    overflow: hidden;
}
.post-card .post-thumb img {
    width: 180px;
    height: 100%;
    object-fit: cover;
}
.post-body {
    padding: 15px;
    flex: 1;
}
.post-cat {
    font-size: 12px;
    color: #6a1b9a;
    font-weight: bold;
    display: inline-block;
    margin-bottom: 5px;
}
.post-title {
    margin: 0 0 10px;
    font-size: 18px;
}
.post-title a { text-decoration:none; color:#111; }
.post-excerpt { font-size:14px; color:#555; margin-bottom:10px; }
.read-more {
    font-weight: bold;
    font-size: 14px;
    color: #6a1b9a;
    text-decoration: none;
}
.read-more:hover { text-decoration: underline; }
.post-date {
    display:block;
    margin-top:8px;
    font-size:12px;
    color:#999;
}
.featured-card {
    background:#fff;
    border-radius:10px;
    overflow:hidden;
    margin-bottom:20px;
    box-shadow:0 4px 10px rgba(0,0,0,0.05);
    padding:15px;
}
.featured-card img {
    width:100%;
    height:150px;
    object-fit:cover;
    border-radius:6px;
    margin-bottom:10px;
}
.featured-card h3 {
    font-size:16px;
    margin:0 0 8px;
}
.featured-card p {
    font-size:14px;
    color:#555;
    margin-bottom:10px;
}
@media(max-width: 900px) {
    .posts-wrapper {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>