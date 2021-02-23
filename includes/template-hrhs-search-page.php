<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * This template is based loosely on the twentytwentyone archive.php template
 * It may need to be updated for the Rocktown History design
 */

get_header();

$description = get_the_archive_description();
?>


<?php if ( have_posts() ) : ?>

<header class="page-header alignwide">
  <h1 class="page-title">HRHS Database Search</h1>
</header><!-- .page-header -->

<?php while ( have_posts() ) : ?>
        <?php the_post(); ?>
        <?php get_template_part( 'template-parts/content/content', get_theme_mod( 'display_excerpt_or_full_post', 'excerpt' ) ); ?>
<?php endwhile; ?>

<?php twenty_twenty_one_the_posts_navigation(); ?>

<?php else : ?>
<?php get_template_part( 'template-parts/content/content-none' ); ?>
<?php endif; ?>

<?php get_footer(); ?>
