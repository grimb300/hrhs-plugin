<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * This template is based loosely on the twentytwentyone archive.php template
 * It may need to be updated for the Rocktown History design
 */

get_header();

if ( is_user_logged_in() ) {
  $current_user = wp_get_current_user();
  $display_name = $current_user->user_login;
  if ( ! empty( $current_user->first_name ) ) {
    $display_name = $current_user->first_name;
  } elseif ( ! empty( $current_user->last_name ) ) {
    $display_name = $current_user->last_name;
  }
  echo '<h4>Welcome, ' . $display_name . '!</h4>';
  wp_loginout( $_SERVER[ 'REQUEST_URI' ] );
} else {
  echo '<h4>User is NOT logged in</h4>';
  wp_login_form( array(
    'value_username' => 'HRHS-MEMBER',
    'value_remember' => true
  ) );
}

?>

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

<?php get_footer(); ?>
