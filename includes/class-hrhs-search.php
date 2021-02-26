<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HRHS_Search {

  /* **********
   * Properties
   * **********/

  private $slug;
  private $search_types_fields;

  /* *******
   * Methods
   * *******/

  public function __construct( $params = array() ) {

    // FIXME: I'm sure there's a more efficient way of doing defaults
    $this->slug =
      array_key_exists( 'slug', $params )
      ? $param[ 'slug' ]
      : 'hrhs-database';
    $this->search_types_fields =
      array_key_exists( 'search_types_fields', $params )
      ? $params[ 'search_types_fields' ]
      : array(
        'default_cpt' => array(
          'default_field_1',
          'default_field_2',
          'default_field_3',
        )
      );

    $this->initialize_hrhs_save();
  }

  private function initialize_hrhs_save() {
    $this->create_hrhs_search_page();
  }

  public function create_hrhs_search_page() {
    add_action( 'init', array( $this, 'hrhs_search_page_rewrite_rules' ) );
    add_filter( 'query_vars', array( $this, 'hrhs_search_page_query_vars' ) );
    add_filter( 'template_include', array( $this, 'hrhs_search_page_template_include' ), 50 );
    add_filter( 'hrhs_search_form', array( $this, 'display_hrhs_search_form' ) );
    add_filter( 'hrhs_search_results', array( $this, 'display_hrhs_search_results' ) );
  }

  public function hrhs_search_page_rewrite_rules() {
    add_rewrite_rule(
      "^{$this->slug}/?$",             // regex
      "index.php?{$this->slug}=true", // query
      "top"                           // priority (top or bottom)
    );
  }

  public function hrhs_search_page_query_vars( $vars ) {
    array_push( $vars, $this->slug );
    return $vars;
  }

  public function hrhs_search_page_template_include( $template ) {
    $hrhs_search_page = get_query_var( $this->slug, false ); // default to false if not present

    if ( false !== $hrhs_search_page ) {
      $template = HRHS_PLUGIN_PATH . 'includes/template-hrhs-search-page.php';
    }
    return $template;
  }

  /* ************************
   * Display filter functions
   * ************************/

  public function display_hrhs_search_form( $search_form ) {
    // Open the form and addd the search text box
    $search_form = <<<END
    <!-- <form id="hrhs-search" role="search" class="widget widget_search" action="#hrhs-search" method="post"> -->
    <form id="hrhs-search" role="search" class="widget widget_search" action="" method="get">
      <label for="hrhs-search-needle">Search...</label>
      <input type="text" name="hrhs-search[needle]" id="hrhs-search-needle">
    END;
    // Add a checkbox per post type
    $checkbox_template = <<<END
    <input type="checkbox" name="hrhs-search[haystacks][%s]" id="hrhs-search-haystacks-%s" checked>
    <label for="hrhs-search-haystacks-%s">%s</label>
    END;
    foreach ( $this->search_types_fields as $type ) {
      $slug = array_key_exists( 'slug', $type ) ? $type[ 'slug' ] : 'unknown';
      $name = array_key_exists( 'plural_name', $type ) ? $type[ 'plural_name' ] : 'Unknown';
      $search_form .= sprintf( $checkbox_template, $slug, $slug, $slug, $name );
    }
    // Add the search button and close the form
    $search_form .= <<<END
      <input type="submit" class="search-submit " value="Search">
    </form>
    END;

    return $search_form;
  }
  
  public function display_hrhs_search_results( $search_results ) {
    // Check to see if there is a search to perform
    if ( array_key_exists( 'hrhs-search', $_REQUEST ) ) {
      // Grab the search string and the data base(s) to search
      $post_data = stripslashes_deep( $_REQUEST[ 'hrhs-search' ] );
      if ( empty( $post_data[ 'needle' ] ) ) {
        // Return warning message
        return '<h3>Empty search string</h3>';
      }
      if ( empty( $post_data[ 'haystacks' ] ) ) {
        // Return warning message
        return '<h3>Must choose at least one database to search</h3>';
      }
      $search_string = filter_var( trim( $post_data[ 'needle' ] ), FILTER_SANITIZE_STRING );
      $search_types = $post_data[ 'haystacks' ];

      // Iterate across the search types and get the results
      foreach ( $post_data[ 'haystacks' ] as $type => $value ) {
        $search_results .= sprintf( '<p>Searching for %s posts</p>', $type );
      }
    }

    // Return the search results
    return $search_results;
  }

  /* ******************
   * Accessor functions
   * ******************/

  // Get the search post types/fields
  public function get_search_types_fields() {
    return $this->search_types_fields;
  }

}
