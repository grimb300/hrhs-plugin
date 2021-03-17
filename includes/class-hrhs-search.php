<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HRHS_Search {

  /* **********
   * Properties
   * **********/

  private $slug;
  private $title;
  private $search_types_fields;
  private $search_types_label;

  /* *******
   * Methods
   * *******/

  public function __construct( $params = array() ) {

    // FIXME: I'm sure there's a more efficient way of doing defaults
    $this->slug =
    array_key_exists( 'slug', $params )
    ? $params[ 'slug' ]
    : 'hrhs-database';
    $this->title =
      array_key_exists( 'title', $params )
      ? $params[ 'title' ]
      : 'HRHS Database Search';
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
    $this->search_types_label = 
      array_key_exists( 'search_types_label', $params )
      ? $params[ 'search_types_label' ]
      : array( 'default_cpt' => 'Default CPT' );

    $this->initialize_hrhs_search();
  }

  private function initialize_hrhs_search() {
    $this->create_hrhs_search_page();
  }

  public function create_hrhs_search_page() {
    add_action( 'init', array( $this, 'hrhs_search_page_rewrite_rules' ) );
    add_filter( 'query_vars', array( $this, 'hrhs_search_page_query_vars' ) );
    add_filter( 'template_include', array( $this, 'hrhs_search_page_template_include' ), 50 );
    add_filter( 'hrhs_search_title', array( $this, 'display_hrhs_search_title' ) );
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

  public function display_hrhs_search_title( $title ) {
    // Check which search page this is
    if ( ! $this->is_current_search_page() ) {
      // Not my page, bail and return without modification
      return $title;
    }

    // Replace with the title property
    return $this->title;
  }

  public function display_hrhs_search_form( $search_form ) {
    // Check which search page this is
    if ( ! $this->is_current_search_page() ) {
      // Not my page, bail and return without modification
      return $search_form;
    }
    
    // Open the form and addd the search text box
    $search_form = <<<END
    <form id="hrhs-search" role="search" class="widget widget_search" action="" method="post">
    <!-- <form id="hrhs-search" role="search" class="widget widget_search" action="" method="get"> -->
      <label for="hrhs-search-needle">Search...</label>
      <input type="text" name="hrhs-search[needle]" id="hrhs-search-needle">
    END;

    // Get the post types (haystacks) this search page will be searching through
    $haystacks = array_keys( $this->search_types_fields );
    // hrhs_debug( 'There are ' .count( $haystacks ) . ' post types to search' );
    // If there is only one post type...
    if ( count( $haystacks ) === 1 ) {
      // ...set the haystack value via a hidden input
      $search_form .= sprintf(
        '<input type="hidden" name="hrhs-search[haystacks][%s]" id="hrhs-search-haystacks-%s" value="on">',
        $haystacks[0], $haystacks[0]
      );
    } else {
      // ... else, add a checkbox per haystack
      $checkbox_template = <<<END
      <input type="checkbox" name="hrhs-search[haystacks][%s]" id="hrhs-search-haystacks-%s" checked>
      <label for="hrhs-search-haystacks-%s">%s</label>
      END;
      foreach ( $haystacks as $haystack ) {
        // Get the label for this haystack, "Unknown" if it doesn't exist
        $label = array_key_exists( $haystack, $this->search_types_label ) ? $this->search_types_label[ $haystack ] : 'Unknown';
        $search_form .= sprintf( $checkbox_template, $haystack, $haystack, $haystack, $label );
      }
    }

    // Add the search button and close the form
    $search_form .= <<<END
      <input type="submit" class="search-submit " value="Search">
    </form>
    END;

    return $search_form;
  }
  
  public function display_hrhs_search_results( $original_search_results ) {
    // Check which search page this is
    if ( ! $this->is_current_search_page() ) {
      // Not my page, bail and return without modification
      return $original_search_results;
    }
    
    // String to hold the search results
    $search_results = '';
    
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
      // TODO: Do I need to sanitize further since the needle is used in a MySQL query?
      $needle = strtolower( filter_var( trim( $post_data[ 'needle' ] ), FILTER_SANITIZE_STRING ) );
      $haystacks = array_keys( $post_data[ 'haystacks' ] );
      // hrhs_debug( 'Searching...' );
      // hrhs_debug( array(
      //   'needle' => $needle,
      //   'haystacks' => $haystacks
      // ) );

      // Iterate across the search types and get the results
      foreach ( $haystacks as $haystack ) {
        // Get the search and display fields for this haystack (post type)
        $fields = array_key_exists( $haystack, $this->search_types_fields ) ? $this->search_types_fields[ $haystack ] : array();
        $search_fields = array_filter( $fields, function( $field ) {
          return  ( 'all' === $field[ 'search' ] ) ||
                  ( is_user_logged_in() && ( 'member' === $field[ 'search' ] ) );
        } );
        $display_fields = array_filter( $fields, function( $field ) {
          return  ( 'all' === $field[ 'display' ] ) ||
                  ( is_user_logged_in() && ( 'member' === $field[ 'display' ] ) );
        } );

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
        $search_results .= sprintf( '<p>Found %d matches for %s posts</p>', count( $matching_posts ), $haystack );
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

}
