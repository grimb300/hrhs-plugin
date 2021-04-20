<?php

namespace HRHSPlugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HRHS_Simple_Search {

  /* **********
   * Properties
   * **********/

  // This property controls whether the search is being done on custom post types ( using get_posts( <query object> ) )
  // or custom database tables ( using $wpdb->get_results( <SQL SELECT statement> ) )
  // FIXME: I could allow this to be configured by the constructor if the plugin needs to mix the types of data
  //        For now I'm keeping it static for all searches
  // private $custom_post_types = true;  // Search CPTs
  private $custom_post_types = false; // Search custom database tables

  // These properties are filled in by the constructor
  private $needle = null;
  private $haystack_def = array();
  private $all_fields = array();
  private $searchable_fields = array();
  private $displayable_fields = array();

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

  }

  public function get_search_results( $params = array() ) {
    // hrhs_debug( sprintf( 'Inside display_search_results( %s )', var_export( $params, true ) ) );
    // If params is empty or none of the fields are searchable, return an empty array
    if ( empty( $params ) || empty( $this->searchable_fields ) ) {
      return array();
    }

    // Get the params
    // TODO: Do I need to sanitize further since the needle is used in a MySQL query?
    $needle = strtolower( filter_var( trim( $params[ 'needle' ] ), FILTER_SANITIZE_STRING ) );

    // Branch here, depending on if the search should be done with custom post types or custom database tables
    if ( $this->custom_post_types ) {
      // hrhs_debug( 'HRHS_Simple_Search::get_search_results - Searching custom post types' );

      // Build a query for this haystack
      $meta_query = array(
        'relation' => 'OR', // Needle must match at least one field
      );
      foreach( $this->searchable_fields as $field ) {
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

      // Get the matching posts
      $matching_post_ids = get_posts( $get_posts_query );

      // Turn the array of post IDs into an array of arrays containing the postmeta data for each post
      $results = array_map(
        function ( $post_id ) {
          $result[ 'id' ] = $post_id; // Using lower case 'id' here to match what is in the custom table

          // Add the meta data to the result
          $meta_data = get_post_meta( $post_id );
          foreach ( $this->all_fields as $field ) {
            $field_name = $field[ 'slug' ];
            $result[ $field_name ] = array_key_exists( $field_name, $meta_data ) ? $meta_data[ $field_name ][0] : '';
          }
          return $result;
        },
        $matching_post_ids
      );
  
      // Return the search results
      return $results;

    } else {
      // hrhs_debug( 'HRHS_Simple_Search::get_search_results - Searching custom database tables' );

      require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-database.php';
      $results = HRHS_Database::get_results( array(
        'name' => $this->haystack_def[ 'slug' ],
        'columns' => array_map( function ( $field ) { return $field[ 'slug' ]; }, $this->searchable_fields ),
        'needle' => $needle
      ) );
      return $results;
    }
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
