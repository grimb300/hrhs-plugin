<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * This template is based loosely on the twentytwentyone archive.php template
 * It may need to be updated for the Rocktown History design
 */

get_header();

// This seems like a bad idea, but I don't know of an alternate way (yet)
// The HRHS_Plugin class has the info about the post types and fields that can be searched
$hrhs = new HRHS_Plugin();
$searchable_post_types = $hrhs->get_search_types_fields();

hrhs_debug( 'Searchable post types:' );
hrhs_debug( $searchable_post_types );

$description = get_the_archive_description();
?>


<?php if ( have_posts() ) : ?>

<header class="page-header alignwide">
  <h1 class="page-title">HRHS Database Search</h1>
</header><!-- .page-header -->

<form role="search" class="widget widget_search" action="#" method="post">
  <label for="hrhs-search-needle">Search...</label>
  <input type="text" name="hrhs-search[needle]" id="hrhs-search-needle">
  <input type="submit" class="search-submit " value="Search">
</form>

<?php
// Check to see if the plugin has been instantiated
if ( ! empty( $hrhs ) ) {
  echo '<p>HRHS_Plugin is instantiated</p>';
} else {
  echo '<p>HRHS_Plugin is not instantiated</p>';
}
?>

<?php while ( have_posts() ) : ?>
        <?php the_post(); ?>
        <?php get_template_part( 'template-parts/content/content', get_theme_mod( 'display_excerpt_or_full_post', 'excerpt' ) ); ?>
<?php endwhile; ?>

<?php twenty_twenty_one_the_posts_navigation(); ?>

<?php else : ?>
<?php get_template_part( 'template-parts/content/content-none' ); ?>
<?php endif; ?>

<?php get_footer(); ?>
