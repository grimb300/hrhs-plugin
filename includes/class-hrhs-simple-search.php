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

    // Get the number of results to return. Default is 'all' (-1)
    $num_results = empty( $params[ 'num_results'] ) ? -1 : intval( $params[ 'num_results' ] );
    // hrhs_debug( sprintf( 'get_search_results: Was sent num_results: %s, this was interpreted as: %s', $params[ 'num_results' ], $num_results ) );
    if ( 0 === $num_results ) {
      // Catch the case where a non-integer is passed (intval returns 0)
      // NOTE: This also catches the 'all' case, which is expected
      $num_results = -1;
    }

    // Get the page number that should be retrieved
    $page_num = empty( $params[ 'page_num' ] ) ? 1 : $params[ 'page_num' ];

    // Get the desired sort order
    $sort_order = empty( $params[ 'sort' ] ) ? array() : $params[ 'sort' ];
    hrhs_debug( 'Sort order is:' );
    hrhs_debug( $sort_order );

    // Done getting the params
    //////////////////////////

    // Create a meta query for the necessary fields
    $meta_query = array(
      'relation' => 'AND', // Needle must match at least one field
    );
    foreach( $search_fields as $field ) {
      $search_clause = $field[ 'slug' ] . '_clause';
      $meta_query[ $search_clause ] = array(
        'key' => $field[ 'slug' ],
        'value' => $needle,   // NOTE: Using LIKE will automagically add
        'compare' => 'LIKE',  //       SQL wildcards (%) around the value
      );
    }
    // Add sort ordering
    $orderby = array();
    foreach( $sort_order as $field ) {
      hrhs_debug( 'Sorting field ' . $field );
      // If there isn't already a search clause for this field create a simple exists clause
      // FIXME: This seems dangerous. It won't display records where the meta value doesn't exist.
      //        Not sure if it's possible to guarantee add records have all meta values right now.
      $sort_clause = $field . '_clause';
      if ( ! array_key_exists( $sort_clause, $meta_query ) ) {
        $meta_query[ $sort_clause ] = array(
          'key' => $field,
          'compare' => 'EXISTS',
        );
      }
      $orderby[ $sort_clause ] = 'ASC';
    }
    hrhs_debug( 'Meta query and orderby:' );
    hrhs_debug( $meta_query );
    hrhs_debug( $orderby );
    // Build the query for this haystack
    $get_posts_query = array(
      'posts_per_page' => $num_results,
      'paged' => $page_num,
      'fields' => 'ids',   // Return an array of post IDs
      'post_type' => $this->haystack_def[ 'slug' ], // Search only the current haystack's post type
      'post_status' => 'publish', // Return only published posts
      'meta_query' => $meta_query,
      'orderby' => $orderby,
    );

    // NOTE: Have to put a backslash in front of WP_Query to find it in the global namespace
    $my_query = new \WP_Query( $get_posts_query );
    // hrhs_debug( sprintf( 'WP_Query returned %d posts', $my_query->post_count ) );
    // hrhs_debug( sprintf( 'WP_Query there are a total of %d posts', $my_query->found_posts ) );
    // hrhs_debug( 'WP_Query returning posts' );
    // hrhs_debug( $my_query->posts );

    // hrhs_debug( 'Query:' );
    // hrhs_debug( $get_posts_query );

    // Return the search results
    // return get_posts( $get_posts_query );

    // FIXME: When merging back into main...
    //        MySQL has a function FOUND_ROWS() which will return
    //        the total number of rows found during the last query
    //        Therefore it should look something like:
    //        SELECT * FROM <custom_table>
    //          WHERE <filters>
    //          LIMIT <num_per_page>; <== returns the paged results
    //        SELECT FOUND_ROWS();    <== returns the total number of found rows
    return array(
      'results' => $my_query->posts,
      'found_results' => $my_query->found_posts
    );
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

  // Get all searchable fields (regardless of user status)
  public function get_all_search_fields() {
    return $this->filter_fields_by_attribute( $this->all_fields, 'search' );
  }

  // Get the user searchable fields
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
    //   2. If the field attribute is "all", return that field
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

  // Filter fields based on the attribute for any user
  private function filter_fields_by_attribute( $all_fields = array(), $filter_attribute = 'none' ) {
    // Filter criteria:
    //   1. If the provided filter_attribute is missing or "none", return all fields
    //   2. If the field attribute is anything other than "none", return that field
    return array_filter(
      $all_fields,
      function( $field ) use ( $filter_attribute ) {
        return  ( 'none' === $filter_attribute ) ||
                ( 'none' !== $field[ $filter_attribute ] );
      }
    );
  }

}
