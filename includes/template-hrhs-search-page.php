<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * This template is based loosely on the twentytwentyone archive.php template
 * It may need to be updated for the Rocktown History design
 */

get_header();

$description = get_the_archive_description();
?>


<?php //if ( have_posts() ) : ?>

<?php
// Build the page title
// Filter should be defined in class-hrhs-search.php
$page_title = apply_filters( 'hrhs_search_title', 'Search Page Title Goes Here' );
?>
<header class="page-header alignwide">
  <h1 class="page-title"><?php echo $page_title; ?></h1>
</header><!-- .page-header -->

<?php
// Build the search form
// Filter should be defined in class-hrhs-search.php
$hrhs_search_form = apply_filters( 'hrhs_search_form', '<form><h3>Search Form Goes Here</h3></form>' );
echo $hrhs_search_form;
?>

<?php
// Display the search results
// Filter should be defined in class-hrhs-search.php
$hrhs_search_results = apply_filters( 'hrhs_search_results', '<h3>No Search Results</h3>' );
echo $hrhs_search_results;
?>

<?php //while ( have_posts() ) : ?>
        <?php //the_post(); ?>
        <?php //get_template_part( 'template-parts/content/content', get_theme_mod( 'display_excerpt_or_full_post', 'excerpt' ) ); ?>
<?php //endwhile; ?>

<?php //twenty_twenty_one_the_posts_navigation(); ?>

<?php //else : ?>
<?php //get_template_part( 'template-parts/content/content-none' ); ?>
<?php //endif; ?>

<?php get_footer(); ?>
