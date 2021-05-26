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
  private $default_search = array();
  private $default_sort = array();
  private $split_searchable_fields = array();
  private $can_split_search = false;

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

    // Get the fields that can be split searched
    // Cross reference with the fields that are searchable by this user
    // hrhs_debug( sprintf( 'Searchable fields: %s', implode( ', ', array_map( function ( $f ) { return $f[ 'slug' ]; }, $this->searchable_fields ) ) ) );
    // hrhs_debug( sprintf( 'Split searchable fields: %s', implode( ', ', $this->haystack_def[ 'split_search' ] ) ) );
    $this->split_searchable_fields =
      empty( $this->haystack_def[ 'split_search' ] )
      ? array()
      : array_filter(
        $this->haystack_def[ 'split_search' ],
        function( $split_field ) {
          return array_reduce(
            $this->searchable_fields,
            function( $result, $search_field ) use ( $split_field ) {
              return $result || $split_field === $search_field[ 'slug' ];
            },
            false
          );
        }
      );
    $this->can_split_search = count( $this->split_searchable_fields ) > 1;

    // hrhs_debug( sprintf(
    //   'Haystack %s %s do a split search using %s fields',
    //   $this->haystack_def[ 'slug' ],
    //   $this->can_split_search ? 'can' : 'can not',
    //   empty( $this->split_searchable_fields ) ? 'none' : implode( ' ', $this->split_searchable_fields )
    // ) );

    // Filter and order the displayable fields by the 'default_sort' param
    $default_sort = array_filter(
      $this->displayable_fields,
      function( $field ) {
        return ! empty( $field[ 'default_sort' ] ) && is_int( $field[ 'default_sort' ] );
      }
    );
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
    $this->default_sort = $default_sort;

    // Add filter to posts_where that adds wildcards to the needle being searched
    add_filter( 'posts_where', array( $this, 'add_wildcards_to_needle' ), 10, 2 );

  }

  public function add_wildcards_to_needle ( $where, $wp_query_obj ) {
    // Run a callback on any LIKE statement that contains spaces
    $new_where = preg_replace_callback(
      '/LIKE \'({\w+})((\w+ )+\w+){\w+}\'/',
      function ( $matches ) {
        // The array passed to the callback follows a similar form to the preg_replace() references:
        //   $matches[0] --> The entire string being replaced
        //   $matches[1] --> 1st reference
        //   $matches[2] --> 2nd reference
        //   $matches[3] --> 3rd reference, and so on
        $wildcard = $matches[1];
        $search_string = $matches[2];

        // Replace the spaces in the search terms with wildcards
        $modified_search_string = preg_replace( '/\s+/', $wildcard, $search_string );
        // hrhs_debug( 'Modified search string: ' . $modified_search_string );

        // Return the substituted search terms in the LIKE statement
        return sprintf( 'LIKE \'%s%s%s\'', $wildcard, $modified_search_string, $wildcard );
      },
      $where
    );
    return $new_where;
  }

  // This is something I'm constantly having to do over and over. Get the slugs out of an array of field objects
  private function get_field_slugs( $field_objs = array() ) {
    return array_map(
      function ( $field_obj ) {
        return $field_obj[ 'slug' ];
      },
      $field_objs
    );
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
    $full_needle = $params[ 'needle' ];
    // hrhs_debug( 'Full needle:' );
    // hrhs_debug( $full_needle );
    // Filter the provided needle to only fields searchable by this user
    $searchable_needle = array();
    foreach ( $full_needle as $slug => $string ) {
      // Add this field to the list of searchable needles if it is searchable by this user
      $is_searchable = array_reduce(
        $this->searchable_fields,
        function ( $result, $search_field ) use ( $slug ) {
          return $result || $slug === $search_field[ 'slug' ];
        },
        false
      );
      if ( $is_searchable ) {
        // FIXME: Do I want to do a search/replace here to filter out extra spaces and punctuation
        //        How about names that may or may not have a space, ex: "McGrath" vs "Mc Grath"
        // Old solution (for only the punctuation part of the problem):
        // NOTE: Removing the appostraphe because it breaks searching for "o'don"
        //       since it will return "Gordon" before "O'Donnell" or "O'Donald"
        $filtered_string = implode(
          ' ',
          array_filter(
            // preg_split( '/[\s\.,\'\"\-]+/', $string ),
            preg_split( '/[\s\.,\"\-]+/', $string ),
            function ( $elm ) {
              return ! empty( $elm );
            }
          )
        );
        // hrhs_debug( sprintf( 'Filtered "%s" to "%s"', $string, $filtered_string ) );
        // $searchable_needle[ $slug ] = $string;
        $searchable_needle[ $slug ] = $filtered_string;
      }
    }
    // hrhs_debug( 'Searchable needle:' );
    // hrhs_debug( $searchable_needle );
    // hrhs_debug( 'Searchable fields by this user' );
    // hrhs_debug( $this->searchable_fields );
    // return array(
    //   'found_results' => 0
    // );

    // Get the number of results to return. Default is all (-1)
    $num_results = empty( $params[ 'num_results'] ) ? -1 : intval( $params[ 'num_results' ] );
    if ( 0 === $num_results ) {
      // Catch the case where a non-integer is passed (intval returns 0)
      // NOTE: This also catches the 'all' case, which is expected
      $num_results = -1;
    }

    // Get the page number that should be retrieved
    $page_num = empty( $params[ 'page_num' ] ) ? 1 : $params[ 'page_num' ];

    // Get the desired sort order
    $sort_order = empty( $params[ 'sort' ] ) ? array() : $params[ 'sort' ];

    // Done getting the params
    //////////////////////////

    // Branch here, depending on if the search should be done with custom post types or custom database tables
    if ( $this->custom_post_types ) {

      // Define the default WP_Query query (used by all searches below)
      $wp_query = array(
        'posts_per_page' => $num_results,
        'paged' => $page_num,
        'fields' => 'ids',   // Return an array of post IDs
        'post_type' => $this->haystack_def[ 'slug' ], // Search only the current haystack's post type
        'post_status' => 'publish', // Return only published posts
        'meta_query' => array( 'relation' => 'AND' ), // The top level will AND the search clause with the sort clause(s)
        'orderby' => array(),
      );
      // Add the sort ordering
      foreach( $sort_order as $field ) {
        // Create a simple exists clause for each ordered field
        // FIXME: This seems dangerous. It won't display records where the meta value doesn't exist.
        //        Not sure if it's possible to guarantee add records have all meta values right now.
        $sort_clause = $field[ 'slug' ] . '_sort_clause';
        $wp_query[ 'meta_query' ][ $sort_clause ] = array(
          'key' => $field[ 'slug' ],
          'compare' => 'EXISTS',
        );  
        $wp_query[ 'orderby' ][ $sort_clause ] = strtoupper( $field[ 'dir' ] );
      }

      // Build the meta query from the searchable needle
      foreach ( $searchable_needle as $slug => $string ) {
        $search_clause = $slug . '_search_clause';
        $wp_query[ 'meta_query' ][ $search_clause ] = array(
          'key' => $slug,
          'value' => $string,
          'compare' => 'LIKE',
        );
      }

      // NOTE: Have to put a backslash in front of WP_Query to find it in the global namespace
      $my_query = new \WP_Query( $wp_query );
      hrhs_debug( 'New needle query: ' . $my_query->request );
      // hrhs_debug( 'New needle query:' );
      // hrhs_debug( $wp_query[ 'meta_query' ] );

      /* ************************************************************************************************
       * Quick MySQL code break
       * After playing around in phpMyAdmin, I came up with this direct MySQL query which does what I want
       * This will be useful when merging back into the main branch and working with custom tables
       * ************************************************************************************************/

      // $sql = "SELECT post.ID AS ID, sur.meta_value AS Surname, given.meta_value AS GivenName FROM `wp_posts` AS post
      // LEFT JOIN `wp_postmeta` AS given ON post.ID = given.post_id
      // LEFT JOIN `wp_postmeta` AS sur ON post.ID = sur.post_id
      // WHERE post.post_type = 'name_entry'
      // AND given.meta_key = 'givenname'
      // AND sur.meta_key = 'surname'
      // AND (given.meta_value LIKE '%a%' AND sur.meta_value LIKE '%a%') ORDER BY sur.meta_value ASC, given.meta_value ASC;";

      /* ************************************************************************************************
      * END MySQL code break
      * ************************************************************************************************/

      // hrhs_debug( 'Query:' );
      // hrhs_debug( $get_posts_query );

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
        $my_query->posts
      );

      // Return the search results
      // FIXME: When merging back into main...
      //        MySQL has a function FOUND_ROWS() which will return
      //        the total number of rows found during the last query
      //        Therefore it should look something like:
      //        SELECT * FROM <custom_table>
      //          WHERE <filters>
      //          LIMIT <num_per_page>; <== returns the paged results
      //        SELECT FOUND_ROWS();    <== returns the total number of found rows
      return array(
        'results' => $results,
        'found_results' => $my_query->found_posts,
        'MySQL_query' => $my_query->request,
      );

    } else {
      // hrhs_debug( 'HRHS_Simple_Search::get_search_results - Searching custom database tables' );

      // TODO: Create a split search similar to what was done for the CPT search.
      //       Should be able to do all searches at one time (instead of iteratively)
      //       Since it is using custom tables, there is less of a penalty for having multiple terms.

      require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-database.php';
      $results = HRHS_Database::get_results( array(
        'name' => $this->haystack_def[ 'slug' ],
        'needle' => $searchable_needle,
        'sort' => $sort_order,
        'records_per_page' => $num_results,
        'paged' => $page_num,
      ) );
      return $results;
    }


    // NOTE: Have to put a backslash in front of WP_Query to find it in the global namespace
    $my_query = new \WP_Query( $get_posts_query );

    // hrhs_debug( 'MySQL request:' );
    // hrhs_debug( $my_query->request );

    // Return the search results
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
