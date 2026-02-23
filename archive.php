<?php
/**
 * Archive
 */
get_header();
if ( have_posts() ) :
  the_archive_title('<h1>','</h1>');
  the_archive_description('<div class="archive-desc">','</div>');
  while ( have_posts() ) : the_post();
    echo '<article '; post_class(); echo '>';
      echo '<h2><a href="' . esc_url( get_permalink() ) . '">'; the_title(); echo '</a></h2>';
      echo '<div class="entry-summary">'; the_excerpt(); echo '</div>';
    echo '</article>';
  endwhile;
  the_posts_pagination();
else :
  echo '<p>' . esc_html__('Nothing here yet.', 'amino-usa') . '</p>';
endif;
get_footer();