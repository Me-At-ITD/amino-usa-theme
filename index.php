<?php
/**
 * Index – fallback loop
 */
get_header();
?>
<?php if ( have_posts() ) : ?>
  <?php while ( have_posts() ) : the_post(); ?>
    <article <?php post_class(); ?>>
      <header>
        <?php if ( is_singular() ) : ?>
          <h1><?php the_title(); ?></h1>
        <?php else : ?>
          <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <?php endif; ?>
      </header>
      <div class="entry-content">
        <?php if ( is_singular() ) { the_content(); } else { the_excerpt(); } ?>
      </div>
    </article>
  <?php endwhile; ?>
  <?php the_posts_pagination(); ?>
<?php else : ?>
  <p><?php esc_html_e('No content found.', 'amino-usa'); ?></p>
<?php endif; ?>
<?php
get_footer();