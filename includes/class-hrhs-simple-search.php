<?php

namespace HRHSPlugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HRHS_Simple_Search {

  /* **********
   * Properties
   * **********/

  private $needle = null;
  private $haystack_def = array();
  private $all_fields = array();
  private $searchable_fields = array();
  private $displayable_fields = array();
  private $default_search = array();
  private $default_sort = array();

  /* *******
   * Methods
   * *******/

  public function __construct( $params = array() ) {

    // hrhs_debug( 'Inside HRHS_Simple_Search::__construct()' );

    $this->needle = empty( $params[ 'needle' ] ) ? null : $params[ 'needle' ];
    
    if ( ! empty( $params[ 'haystack' ] ) ) {
      // Get the various CPTs that could be searched out of options
      require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-options.php';
      $options_obj = new HRHS_Options();
      $my_options = $options_obj->get();
      $this->haystack_def = $my_options[ 'post_type_defs' ][ $params[ 'haystack' ] ];
      $this->all_fields = $my_options[ 'post_type_defs' ][ $params[ 'haystack' ] ][ 'fields' ];
    }

    // Filter down the field lists based on type of field and user login status
    $this->searchable_fields = $this->filter_fields_by_user_status( $this->all_fields, 'search' );
    $this->displayable_fields = $this->filter_fields_by_user_status( $this->all_fields, 'display' );

    // Further filter the searchable fields by the 'default_search' param
    $default_search = array_filter(
      $this->searchable_fields,
      function( $field ) {
        return ! empty( $field[ 'default_search' ] ) && boolval( $field[ 'default_search' ] );
      }
    );
    // If empty, default to the searchable_fields
    $this->default_search = empty( $default_search ) ? $this->searchable_fields : $default_search;

    // Filter and order the displayable fields by the 'default_sort' param
    $default_sort = array_filter(
      $this->displayable_fields,
      function( $field ) {
        return ! empty( $field[ 'default_sort' ] ) && is_int( $field[ 'default_sort' ] );
      }
    );
    // hrhs_debug( 'default_sort after filter' );
    // hrhs_debug( $default_sort );
    // NOTE: usort sorts in place
    usort(
      $default_sort,
      function( $field_a, $field_b ) {
        $a_sort = $field_a[ 'default_sort' ];
        $b_sort = $field_b[ 'default_sort' ];
        if ( $a_sort === $b_sort ) {
          return 0;
        }
        return $a_sort < $b_sort ? -1 : 1;
      }
    );
    // hrhs_debug( 'default_sort after usort' );
    // hrhs_debug( $default_sort );
    $this->default_sort = $default_sort;

  }

  public function get_search_results( $params = array() ) {
    // hrhs_debug( sprintf( 'Inside display_search_results( %s )', var_export( $params, true ) ) );
    // If params is empty or none of the fields are searchable, return an empty array
    if ( empty( $params ) || empty( $this->searchable_fields ) ) {
      return array();
    }

    ////////////////////////
    // Get the params

    // Get the needle
    // TODO: Do I need to sanitize further since the needle is used in a MySQL query?
    $needle = strtolower( filter_var( trim( $params[ 'needle' ] ), FILTER_SANITIZE_STRING ) );

    // Get the fields
    // Default is $this->default_search, but can be changed by $params[ 'fields' ]
    $params_fields = empty( $params[ 'fields' ] ) ? array() : $params[ 'fields' ];
    // hrhs_debug( 'params_fields:' );
    // hrhs_debug( $params_fields );
    $filtered_fields = array_filter(
      $this->searchable_fields,
        // function... use... pulls $params_fields into the filter function scope
        function( $field ) use ( $params_fields ) {
        return in_array( $field[ 'slug' ], $params_fields );
      }
    );
    $search_fields = empty( $filtered_fields ) ? $this->default_search : $filtered_fields;
    // hrhs_debug( 'All searchable fields:' );
    // hrhs_debug( $this->searchable_fields );
    // hrhs_debug( 'Filtered search fields:' );
    // hrhs_debug( $search_fields );

    // Done getting the params
    //////////////////////////

    // Build a query for this haystack
    $meta_query = array(
      'relation' => 'OR', // Needle must match at least one field
    );
    foreach( $search_fields as $field ) {
      $meta_query[ $field[ 'slug' ] . '_clause' ] = array(
        'key' => $field[ 'slug' ],
        'value' => $needle,   // NOTE: Using LIKE will automagically add
        'compare' => 'LIKE',  //       SQL wildcards (%) around the value
      );
    }
    $get_posts_query = array(
      'numberposts' => -1, // Return all matches
      'fields' => 'ids',   // Return an array of post IDs
      'post_type' => $this->haystack_def[ 'slug' ], // Search only the current haystack's post type
      'post_status' => 'publish', // Return only published posts
      'meta_query' => $meta_query,
    );

    // hrhs_debug( 'Query:' );
    // hrhs_debug( $get_posts_query );

    // Return the search results
    return get_posts( $get_posts_query );
  }

  /* ******************
   * Accessor functions
   * ******************/

  // Get the search post types/fields
  // TODO: Commenting out until I find a use for it
  // public function get_search_types_fields() {
  //   return $this->search_types_fields;
  // }

  // Check if this is the current search page
  // By default, all HRHS_Search pages use the same template and same filters, check against the slug of the current page
  // private function is_current_search_page() {
  //   return boolval( get_query_var( $this->slug, false ) );
  // }

  // Get the search fields
  public function get_search_fields() {
    return $this->searchable_fields;
  }

  // Get the display fields
  public function get_display_fields() {
    return $this->displayable_fields;
  }

  // Get the default search fields
  public function get_default_search() {
    return $this->default_search;
  }

  // Get the default sort order
  public function get_default_sort() {
    return $this->default_sort;
  }
  
  // Filter fields based on the attribute and user status
  private function filter_fields_by_user_status( $all_fields = array(), $filter_attribute = 'none' ) {
    // Filter criteria:
    //   1. If the provided filter_attribute is missing or "none", return all fields
    //   2. if the field attribute is "all", return that field
    //   3. If the field attribute is "member", return if the user is logged in
    return array_filter(
      $all_fields,
      function( $field ) use ( $filter_attribute ) {
        return  ( 'none' === $filter_attribute ) ||
                ( 'all' === $field[ $filter_attribute ] ) ||
                ( is_user_logged_in() && ( 'member' === $field[ $filter_attribute ] ) );
      }
    );
  }

}
