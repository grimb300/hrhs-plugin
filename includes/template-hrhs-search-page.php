<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * This template is based loosely on the twentytwentyone archive.php template
 * It may need to be updated for the Rocktown History design
 * Updated to look acceptable with the Ozeum theme
 */

get_header();

?>

<?php
// Build the page title
// Filter should be defined in class-hrhs-search.php
$page_title = apply_filters( 'hrhs_search_title', 'Search Page Title Goes Here' );
?>
<header class="page-header alignwide">
  <h1 class="page-title"><?php echo $page_title; ?></h1>
</header><!-- .page-header -->

<div id="hrhs_search_wrap">
  <div id="hrhs_search_form_wrap">
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
  </div>
  <div id="hrhs_search_widget_wrap">
    <?php
    // Build the login widget
    echo apply_filters( 'hrhs_search_login_info', '<h4>Login Info Goes Here</h4>' );
    ?>
  </div>
</div>

<?php get_footer(); ?>
