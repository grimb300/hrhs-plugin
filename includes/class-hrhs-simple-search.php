<?php

namespace HRHSPlugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HRHS_Simple_Search {

  /* **********
   * Properties
   * **********/

  /* *******
   * Methods
   * *******/

  public function __construct( $params = array() ) {

    hrhs_debug( 'Inside HRHS_Simple_Search::__construct()' );

  }

  static public function display_search_results( $params = array() ) {

    ?>
    <h4>Results from <code>HRHS_Simple_Search::display_search_results()</code></h4>
    <?php
    return;
    // Get the types and fields this user can search on and which can be displayed
    $searchable_haystacks = $this->get_search_fields();
    $displayable_haystacks = $this->get_display_fields();

    // If no searchable haystacks, return nothing
    // Assumes the search form is displaying an appropriate message
    if ( empty( $searchable_haystacks ) ) {
      return '';
    }
    
    // String to hold the search results
    $search_results = '';
    
    // Check to see if there is a search to perform
    if ( array_key_exists( 'hrhs-search', $_REQUEST ) ) {
      // Grab the search string and the data base(s) to search
      $search_params = stripslashes_deep( $_REQUEST[ 'hrhs-search' ] );
      if ( empty( $search_params[ 'needle' ] ) ) {
        // Return warning message
        return '<h3>Empty search string</h3>';
      }
      if ( empty( $search_params[ 'haystacks' ] ) ) {
        // Return warning message
        return '<h3>Must choose at least one database to search</h3>';
      }
      // TODO: Do I need to sanitize further since the needle is used in a MySQL query?
      $needle = strtolower( filter_var( trim( $search_params[ 'needle' ] ), FILTER_SANITIZE_STRING ) );
      $haystacks = array_keys( $search_params[ 'haystacks' ] );
      // hrhs_debug( 'Searching...' );
      // hrhs_debug( array(
      //   'needle' => $needle,
      //   'haystacks' => $haystacks
      // ) );

      // Iterate across the search types and get the results
      foreach ( $haystacks as $haystack ) {
        // Get the search and display fields for this haystack (post type)
        $search_fields =
          empty( $searchable_haystacks[ $haystack ] )
          ? array()
          : $searchable_haystacks[ $haystack ];
        $display_fields =
          empty( $displayable_haystacks[ $haystack ] )
          ? array()
          : $displayable_haystacks[ $haystack ];

        // Only perform the query if this haystack is both searchable and displayable
        if ( ! empty( $search_fields ) && ! empty( $display_fields ) ) {
          // Build a meta query for this haystack
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
          // hrhs_debug( 'Meta query:' );
          // hrhs_debug( $meta_query );
          $get_posts_query = array(
            'numberposts' => -1, // Return all matches
            'fields' => 'ids',   // Return an array of post IDs
            'post_type' => $haystack, // Search only the current haystack's post type
            'meta_query' => $meta_query,
          );
          // hrhs_debug( 'Get Posts query:' );
          // hrhs_debug( $get_posts_query );
          $matching_posts = get_posts( $get_posts_query );
          $search_results .= sprintf( '<p>Found %d matches for %s posts</p>', count( $matching_posts ), $haystack );
          if ( count( $matching_posts ) > 0 ) {
            // Start the table
            $search_results .= '<table><tbody>';
            // Build the table heading
            $search_results .= '<tr>';
            foreach( $display_fields as $field ) {
              $search_results .= '<th scope="col">' . $field[ 'label' ] . '</th>';
            }
            $search_results .= '</tr>';
            // Display each entry
            foreach( $matching_posts as $post_id ) {
              $search_results .= '<tr>';
              $meta_data = get_post_meta( $post_id );
              foreach( $display_fields as $field ) {
                $search_results .= sprintf( '<td>%s</td>', array_key_exists( $field[ 'slug' ], $meta_data ) ? $meta_data[ $field[ 'slug' ] ][ 0 ] : '' );
              }
              $search_results .= '</tr>';
            }
            // Close the table
            $search_results .= '</tbody></table>';
          }
        }
      }
    }

    // Return the search results
    return '' !== $search_results ? $search_results : $original_search_results;
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
  private function is_current_search_page() {
    return boolval( get_query_var( $this->slug, false ) );
  }

  // Get the search fields based on the user status
  public function get_search_fields() {
    return $this->filter_types_fields_by_user_status( 'search' );
  }

  // Get the display fields based on the user status
  public function get_display_fields() {
    return $this->filter_types_fields_by_user_status( 'display' );
  }
  
  // Get the fields based on the type and user status (used by the two functions above)
  public function filter_types_fields_by_user_status( $filter_attribute = 'none' ) {
    // If no filter attribute, return the entire array
    if ( 'none' === $filter_attribute ) {
      return $this->search_types_fields;
    }

    // Iterate across all types filtering the fields
    $filtered_fields = array();
    foreach ( $this->search_types_fields as $type => $fields ) {
      $filtered_fields[ $type ] = array_filter(
        $fields,
        // function... use... pulls $filter_attribute into the filter function scope
        function( $field ) use ( $filter_attribute ) {
          return  ( 'all' === $field[ $filter_attribute ] ) ||
                  ( is_user_logged_in() && ( 'member' === $field[ $filter_attribute ] ) );
        }
      );
    }

    // Filter out the types that don't have any fields left
    $filtered_types = array_filter(
      $filtered_fields,
      function( $type ) {
        return count( $type ) > 0;
      }
    );

    return $filtered_types;
  }

}
