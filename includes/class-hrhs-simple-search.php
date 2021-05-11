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
  private $custom_post_types = true;  // Search CPTs
  // private $custom_post_types = false; // Search custom database tables

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
    $full_needle = strtolower( $params[ 'needle' ] );
    // Split the full search string into its parts delimited by spaces and punctuation
    // Make sure to squash any empty needles
    // FIXME: Building this list up over time. Starting with spaces, periods, commas, single- and double-quotes
    $needles = array_filter( preg_split( '/[\s\.,\'\"]+/', $full_needle ), function ( $elm ) { return ! empty( $elm ); } );
    $has_multiple_needles = count( $needles ) > 1 ? true : false;
    if ( $has_multiple_needles ) {
      hrhs_debug( sprintf( 'Full needle (%s) broken up into its parts: %s', $full_needle, implode( ' ', $needles ) ) );
    }

    // Get the fields
    // Default is $this->default_search, but can be changed by $params[ 'fields' ]
    $params_fields = empty( $params[ 'fields' ] ) ? array() : $params[ 'fields' ];
    $filtered_fields = array_filter(
      $this->searchable_fields,
        // function... use... pulls $params_fields into the filter function scope
        function( $field ) use ( $params_fields ) {
        return in_array( $field[ 'slug' ], $params_fields );
      }
    );
    $search_fields = empty( $filtered_fields ) ? $this->default_search : $filtered_fields;
    $search_field_slugs = $this->get_field_slugs( $search_fields );
    $will_search_multiple_fields = count( $search_fields ) > 1;

    // Get the number of results to return. Default is 'all' (-1)
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

      // Create a meta query for the necessary fields
      // This complex-ish meta query will look like:
      //   results = ( ( field1 = needle1 ) OR ( field2 = needle1 ) OR ( field1 = needle2 ) OR ( field2 = needle2 ) OR ... )
      //             AND field1 exists AND field2 exists AND ...
      // The fieldx exists terms are used only for sorting purposes
      //
      // SQL code break -- The generated SQL query looks like:
      //   SELECT SQL_CALC_FOUND_ROWS  wp_posts.ID FROM wp_posts
      //   INNER JOIN wp_postmeta ON ( wp_posts.ID = wp_postmeta.post_id )
      //   INNER JOIN wp_postmeta AS mt1 ON ( wp_posts.ID = mt1.post_id )
      //   INNER JOIN wp_postmeta AS mt2 ON ( wp_posts.ID = mt2.post_id )
      //   WHERE 1=1  AND ( 
      //     ( 
      //       ( wp_postmeta.meta_key = 'surname' AND wp_postmeta.meta_value LIKE '%a%' ) 
      //       OR 
      //       ( wp_postmeta.meta_key = 'givenname' AND wp_postmeta.meta_value LIKE '%a%' )
      //     ) 
      //     AND 
      //     mt1.meta_key = 'surname' 
      //     AND 
      //     mt2.meta_key = 'givenname'
      //   ) AND wp_posts.post_type = 'name_entry' AND ((wp_posts.post_status = 'publish'))
      //   GROUP BY wp_posts.ID
      //   ORDER BY CAST(mt1.meta_value AS CHAR) ASC, CAST(mt2.meta_value AS CHAR) ASC
      //   LIMIT 0, 50
      // END SQL code break

      // After initial implementation I found that trying to do this in one massive
      // WP_Query is VERY slow beyond two "needles" and two search fields. This is because
      // every AND term in the WHERE clause requires a new INNER JOIN. The total number of JOINs
      // increases exponentially with the number of needles/fields. Yay for postmeta!
      // This method has been commented out below:
      // $meta_query = array(
      //   'relation' => 'AND', // The search_clause will be ANDed with any sorting clauses (shouldn't affect the results)
      //   'search_clause' => array(
      //     'relation' => 'OR', // The individual searches are ORed
      //   )
      // );
      // // Start with the "dumb" search. Search for the full needle string in each of the search fields.
      // foreach( $search_field_slugs as $field ) {
      //   $search_clause = $field . '_search_clause';
      //   $meta_query[ 'search_clause' ][ $search_clause ] = array(
      //     'key' => $field,
      //     'value' => $full_needle,   // NOTE: Using LIKE will automagically add
      //     'compare' => 'LIKE',       //       SQL wildcards (%) around the value
      //   );
      // }
      // // If the full needle string can be broken up into parts,
      // // search for the parts in each of the search fields.
      // // This is different than the above search since it strips out extra spaces and punctuation
      // // FIXME: Do I really need this search AND the one above. Consider removing the "dumb" search.
      // if ( $has_multiple_needles ) {
      //   foreach( $search_field_slugs as $field ) {
      //     $search_clause = $field . '_smarter_search_clause';
      //     $meta_query[ 'search_clause' ][ $search_clause ] = array(
      //       'key' => $field,
      //       'value' => implode( ' ', $needles ),   // NOTE: Using LIKE will automagically add
      //       'compare' => 'LIKE',       //       SQL wildcards (%) around the value
      //     );
      //   }
      // }
      // // FIXME: This seems clunky. Consider rewriting.
      // // If the full needle string can be broken up into parts
      // //   AND the user wants to search on multiple fields
      // //   AND this haystack can split a search across multiple fields
      // //   AND the defined split search fields are in the user fields
      // // Then add the meta queries to perform these searches
      // hrhs_debug( sprintf(
      //   'Checking to see if the split search fields (%s) are contained within the user search fields (%s)',
      //   implode( ', ', $this->split_searchable_fields ),
      //   implode( ', ', $search_field_slugs )
      // ) );
      // $user_fields_is_split_search = array_reduce(
      //   $this->split_searchable_fields,
      //   function( $result, $split_search ) use ( $search_field_slugs ) {
      //     return $result && in_array( $split_search, $search_field_slugs );
      //   },
      //   true
      // );
      // hrhs_debug( sprintf( 'The user defined search fields %s match the split search fields', $user_fields_is_split_search ? 'does' : 'does not' ) );
      // hrhs_debug( sprintf( 'Searching for needles: %s', implode( ', ', $needles ) ) );
      // if ( $has_multiple_needles && $will_search_multiple_fields && $this->can_split_search && $user_fields_is_split_search ) {
      //   // FIXME: This algorithm works for two split search fields, more than two requires being more clever
      //   $left_field = $this->split_searchable_fields[ 0 ];
      //   $right_field = $this->split_searchable_fields[ 1 ];
      //   for ( $idx = 1; $idx < count( $needles ); $idx += 1 ) {
      //     $split_search_clause = $left_field . '_' . $right_field . '_split_search_' . $idx . '_clause';
      //     $left_search_clause = $left_field . '_left_search_' . $idx . '_clause';
      //     $right_search_clause = $right_field . '_right_search_' . $idx . '_clause';
      //     $left_search_string = implode( ' ', array_slice( $needles, 0, $idx ) );
      //     $right_search_string = implode( ' ', array_slice( $needles, $idx ) );
      //     hrhs_debug( sprintf( 
      //       'For %s, left search (%s) and right search (%s)',
      //       $split_search_clause, $left_search_string, $right_search_string
      //     ) );
      //     $meta_query[ 'search_clause' ][ $split_search_clause ] = array(
      //       'relation' => 'AND',
      //       $left_search_clause => array(
      //         'key' => $left_field,
      //         'value' => $left_search_string,   // NOTE: Using LIKE will automagically add
      //         'compare' => 'LIKE',       //       SQL wildcards (%) around the value
      //       ),
      //       $right_search_clause => array(
      //         'key' => $right_field,
      //         'value' => $right_search_string,   // NOTE: Using LIKE will automagically add
      //         'compare' => 'LIKE',       //       SQL wildcards (%) around the value
      //       ),
      //     );
      //   }
      // }
      //
      // END VERY slow method
      ////////////////////////////////////////////////////////////////////////

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

      ////////////////////////////////////////////////////////////////////////
      // New method

      // 1. See if we get lucky and the search string with no manipulation returns results

      // Create a meta_query using only the $full_needle on each $search_field
      $wp_query[ 'meta_query' ][ 'search_clause' ] = array(
        'relation' => 'OR'
      );
      foreach ( $search_field_slugs as $search_field ) {
        $search_clause = $search_field . '_full_needle_clause';
        $wp_query[ 'meta_query' ][ 'search_clause' ][ $search_clause ] = array(
          'key' => $search_field,
          'value' => $full_needle,
          'compare' => 'LIKE',
        );
      }
      
      $my_query = new \WP_Query( $wp_query );
      // hrhs_debug( 'Full needle query: ' . $my_query->request );
      hrhs_debug( 'Full needle query:' );
      hrhs_debug( $wp_query[ 'meta_query' ][ 'search_clause' ] );
  
      // 2. After breaking the full string into multiple needles, try all needles on each field
      
      if ( 0 === $my_query->found_posts && $has_multiple_needles ) {
        // Create a meta_query using all the $needles on each $search_field
        $wp_query[ 'meta_query' ][ 'search_clause' ] = array(
          'relation' => 'OR'
        );
        foreach( $search_field_slugs as $search_field ) {
          $search_clause = $search_field . '_smarter_search_clause';
          $wp_query[ 'meta_query' ][ 'search_clause' ][ $search_clause ] = array(
            'key' => $search_field,
            'value' => implode( ' ', $needles ),
            'compare' => 'LIKE',
          );
        }

        $my_query = new \WP_Query( $wp_query );
        // hrhs_debug( 'Smarter search query: ' . $my_query->request );
        hrhs_debug( 'Smarter search query:' );
        hrhs_debug( $wp_query[ 'meta_query' ][ 'search_clause' ] );
    }

      // 3. If there are multiple needles and multiple fields and the fields support a split search
      //    Try each of the splits individually and return the results

      // Is this a split search candidate
      $can_split_search = 
        $has_multiple_needles &&        // This search string can be split
        $will_search_multiple_fields && // This search is searching multiple fields
        $this->can_split_search &&      // This haystack is capable of doing a split search
        array_reduce(                   // The split searchable fields are being searched
          $this->split_searchable_fields,
          function ( $result, $split_search ) use ( $search_field_slugs ) {
            return $result && in_array( $split_search, $search_field_slugs );
          },
          true
        );

      if ( 0 === $my_query->found_posts && $can_split_search ) {
        // FIXME: This algorithm works for two split search fields
        //        More than two requires being more clever
        // Get the two fields to split the search across
        $left_field = $this->split_searchable_fields[ 0 ];
        $right_field = $this->split_searchable_fields[ 1 ];

        // Iterate across the needles, splitting them up into different groups
        // Break the loop prematurely if the query returns any results
        // FIXME: This may not return all possible matches.
        //        I probably don't care because I'm migrating to custom db tables.
        for ( $idx = 1; $idx < count( $needles ) && 0 === $my_query->found_posts; $idx += 1 ) {
          $left_search_clause = $left_field . '_left_search_clause';
          $right_search_clause = $right_field . '_right_search_clause';
          $left_search_string = implode( ' ', array_slice( $needles, 0, $idx ) );
          $right_search_string = implode( ' ', array_slice( $needles, $idx ) );

          // Create a meta_query ANDing the left and right strings
          $wp_query[ 'meta_query' ][ 'search_clause' ] = array(
            'relation' => 'AND',
            $left_search_clause => array(
              'key' => $left_field,
              'value' => $left_search_string,
              'compare' => 'LIKE',
            ),
            $right_search_clause => array(
              'key' => $right_field,
              'value' => $right_search_string,
              'compare' => 'LIKE',
            ),
          );

          $my_query = new \WP_Query( $wp_query );
          // hrhs_debug( 'Split search query: ' . $my_query->request );
          hrhs_debug( 'Split search query:' );
          hrhs_debug( $wp_query[ 'meta_query' ][ 'search_clause' ] );
        }
      }

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

      // NOTE: Have to put a backslash in front of WP_Query to find it in the global namespace

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
        'columns' => array_map( function ( $field ) { return $field[ 'slug' ]; }, $this->searchable_fields ),
        'needle' => $full_needle,
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
