<?php
/**
 * Template Name: Posts Two Column (Latest + Featured)
 * Description: Custom posts layout with Latest Posts on the left and Featured Posts on the right.
 */

get_header(); ?>

<div class="container two-col-posts">
    <div class="row">
        <!-- Left Column: Latest Posts -->
        <div class="col latest-posts">
            <h2 class="section-title">Latest Posts</h2>
            <?php
            $latest_posts = new WP_Query([
                'post_type'      => 'post',
                'posts_per_page' => 6,
                'ignore_sticky_posts' => true,
            ]);
            if ($latest_posts->have_posts()) :
                while ($latest_posts->have_posts()) : $latest_posts->the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('post-card'); ?>>
                        <div class="post-thumb">
                            <a href="<?php the_permalink(); ?>">
                                <?php if (has_post_thumbnail()) {
                                    the_post_thumbnail('medium');
                                } else {
                                    echo '<img src="' . get_template_directory_uri() . '/assets/images/fallback-thumbnail.jpg" alt="' . get_the_title() . '">';
                                } ?>
                            </a>
                        </div>
                        <div class="post-info">
                            <div class="post-category">
                                <?php $category = get_the_category();
                                if ($category) {
                                    echo '<span class="cat-label">' . esc_html($category[0]->name) . '</span>';
                                } ?>
                            </div>
                            <h3 class="post-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <p class="post-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?></p>
                            <div class="post-meta">
                                <span class="read-more"><a href="<?php the_permalink(); ?>">Read More</a></span>
                                <span class="date"><?php echo get_the_date(); ?></span>
                            </div>
                        </div>
                    </article>
                <?php endwhile;
                wp_reset_postdata();
            endif; ?>
        </div>

        <!-- Right Column: Featured Posts -->
        <div class="col featured-posts">
            <h2 class="section-title">Featured Posts</h2>
            <?php
            $featured_posts = new WP_Query([
                'post_type'      => 'post',
                'posts_per_page' => 3,
                'meta_key'       => 'is_featured', // ← optional custom field, or replace with sticky posts
                'meta_value'     => '1',
            ]);

            if (!$featured_posts->have_posts()) {
                // fallback: get sticky posts
                $sticky = get_option('sticky_posts');
                $featured_posts = new WP_Query([
                    'post_type'      => 'post',
                    'posts_per_page' => 3,
                    'post__in'       => $sticky,
                ]);
            }

            if ($featured_posts->have_posts()) :
                while ($featured_posts->have_posts()) : $featured_posts->the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('post-card small'); ?>>
                        <div class="post-thumb">
                            <a href="<?php the_permalink(); ?>">
                                <?php if (has_post_thumbnail()) {
                                    the_post_thumbnail('medium');
                                } else {
                                    echo '<img src="' . get_template_directory_uri() . '/assets/images/fallback-thumbnail.jpg" alt="' . get_the_title() . '">';
                                } ?>
                            </a>
                        </div>
                        <div class="post-info">
                            <h3 class="post-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <p class="post-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 15, '...'); ?></p>
                            <div class="post-meta">
                                <span class="read-more"><a href="<?php the_permalink(); ?>">Read More</a></span>
                                <span class="date"><?php echo get_the_date(); ?></span>
                            </div>
                        </div>
                    </article>
                <?php endwhile;
                wp_reset_postdata();
            endif; ?>
        </div>
    </div>
</div>

<style>
/* Layout */
.container.two-col-posts {
  max-width: 1200px;
  margin: 40px auto;
  padding: 0 20px;
}
.row {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 30px;
}
.section-title {
  font-size: 1.8rem;
  font-weight: 700;
  margin-bottom: 20px;
}
.post-card {
  display: flex;
  padding: 20px;
  gap: 15px;
  margin-bottom: 25px;
  border: 1px solid #eee;
  border-radius: 8px;
  overflow: hidden;
  background: #fff;
  transition: transform .3s ease, box-shadow .3s ease;
}
.post-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 12px rgba(0,0,0,0.08);
}
.post-card.small {
  flex-direction: column;
}
.post-thumb img {
  width: 120px;
  height: 100%;
  object-fit: cover;
  border-radius: 6px;
}
.post-card.small .post-thumb img {
  width: 100%;
  height: 150px;
}
.post-info {
  flex: 1;
}
.post-category .cat-label {
  background: #6a0dad;
  color: #fff;
  padding: 3px 8px;
  font-size: 0.75rem;
  border-radius: 12px;
  text-transform: uppercase;
}
.post-title {
  font-size: 1.1rem;
  margin: 8px 0;
}
.post-title a {
  color: #333;
  text-decoration: none;
}
.post-title a:hover {
  color: #6a0dad;
}
.post-excerpt {
  font-size: 0.9rem;
  color: #555;
  margin-bottom: 10px;
}
.post-meta {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  color: #777;
}
.read-more a {
  color: #6a0dad;
  font-weight: 600;
  text-decoration: none;
}
.read-more a:hover {
  text-decoration: underline;
}

/* Responsive */
@media (max-width: 992px) {
  .row { grid-template-columns: 1fr; }
}
</style>

<?php get_footer(); ?>