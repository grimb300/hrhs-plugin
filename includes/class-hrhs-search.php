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
    add_filter( 'hrhs_search_login_info', array( $this, 'display_hrhs_search_login_info' ) );
    add_filter( 'hrhs_search_title', array( $this, 'display_hrhs_search_title' ) );
    add_filter( 'hrhs_search_form', array( $this, 'display_hrhs_search_form' ) );
    add_filter( 'hrhs_search_results', array( $this, 'display_hrhs_search_results' ) );
    add_action( 'wp_login_failed', array( $this, 'hrhs_search_page_handle_failed_login' ) );
    add_action( 'wp_enqueue_scripts', array( $this, 'hrhs_serach_enqueue_scripts') );
  }

  public function hrhs_serach_enqueue_scripts() {
    hrhs_debug( 'Inside hrhs_serach_enqueue_scripts' );
    // Check which search page this is
    if ( ! $this->is_current_search_page() ) {
      // Not my page, bail and return without modification
      // return;
    }

    // Get the current version of the CSS file
    $hrhs_search_styles_css_ver = date( 'ymd-Gis', filemtime( HRHS_PLUGIN_PATH . 'css/hrhs-search-styles.css' ) );
    wp_enqueue_style( 'hrhs_search_styles_css', HRHS_PLUGIN_URL . 'css/hrhs-search-styles.css', array(), $hrhs_search_styles_css_ver );
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

  public function hrhs_search_page_handle_failed_login( $username ) {
    hrhs_debug( 'Inside hrhs_search_page_handle_failed_login()' );
    $referer = $_SERVER[ 'HTTP_REFERER' ];
    // If there's a valid referer and it is this page...
    if ( ! empty( $referer ) && strstr( $referer , $this->slug ) ) {
      // ...redirect back to the search page, adding a param to indicate the login failed
      wp_redirect( $referer . '?login=failed' );
      exit;
   }
  }

  /* ************************
   * Display filter functions
   * ************************/

  public function display_hrhs_search_login_info( $login_info ) {
    // Check which search page this is
    if ( ! $this->is_current_search_page() ) {
      // Not my page, bail and return without modification
      return $login_info;
    }

    // Open a div to contain the member login form
    $login_info = '<div id="hrhs_member_login_info">';

    // Are we logged in
    if ( is_user_logged_in() ) {
      // Create welcome message
      $current_user = wp_get_current_user();
      $display_name = $current_user->user_login;
      if ( ! empty( $current_user->first_name ) ) {
        $display_name = $current_user->first_name;
      } elseif ( ! empty( $current_user->last_name ) ) {
        $display_name = $current_user->last_name;
      }
      $login_info .= '<div id="hrhs_member_greeting">';
      $login_info .= '<h4>Welcome, ' . $display_name . '!</h4>';
      $login_info .= '</div>';
      // Add a logout link
      $login_info .= wp_loginout( $_SERVER[ 'REQUEST_URI' ], false );
    } else {
      // Create a not logged in message
      $login_info .= '<div id="hrhs_member_greeting">';
      $login_info .= '<h4>Member Access</h4>';
      $login_info .= '<p>Rocktown History members provide sustaining funds for the collection, preservation, and research of genealogy and local history resources. Member benefits include expanded online searches of Names and Historic Locations.</p>';
      $login_info .= '</div>';
      // Add a login form
      $login_info .= wp_login_form( array(
        'echo' => false,
        'redirect' => $_SERVER[ 'REQUEST_URI' ],
        'form_id' => 'hrhs_member_login_form',
        'label_username' => '',
        'label_password' => 'Password is case sensitive. Please contact the Administrator with access questions.',
        // 'label_remember' => '',
        'label_log_in' => 'Log In',
        'id_username' => 'hrhs_member_username',
        'id_password' => 'hrhs_member_password',
        // 'id_remember' => 'hrhs_member_remember',
        'id_submit' => 'hrhs_member_submit',
        'remember' => false,
        'value_username' => 'HRHS-MEMBER',
        // 'value_remember' => true,
      ) );
      // If the previous login attempt failed, add an appropriate message
      if ( ! empty( $_REQUEST[ 'login' ] ) && 'failed' === $_REQUEST[ 'login' ] ) {
        $login_info .= '<p>Incorrect password, try again</p>';
      }
    }

    // Close the surrounding div
    $login_info .= '</div>';
    
    return $login_info;
  }

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

    // Get the search params from a previous search, if it exists
    $search_params = array_key_exists( 'hrhs-search', $_REQUEST ) ? stripslashes_deep( $_REQUEST[ 'hrhs-search' ] ) : array();

    // Get the types and fields this user can search on
    $searchable_haystacks = $this->get_search_fields();
    // hrhs_debug( 'Haystacks searchable by this user:' );
    // foreach ( $searchable_haystacks as $haystack => $fields ) {
    //   $num_fields = count( $fields );
    //   hrhs_debug( sprintf( 'Haystack %s has %d search fields', $haystack, $num_fields ) );
    // }

    // If no searchable haystacks, return message
    if ( empty( $searchable_haystacks ) ) {
      return '<h3>This search is reserved for HRHS Members</h3>';
    }
   
    // Open the form
    $search_form = '<form id="hrhs-search" role="search" class="widget widget_search" action="" method="post">';
    // $search_form = '<form id="hrhs-search" role="search" class="widget widget_search" action="" method="get">';

    // Add the search text box
    $search_form .= '<label for="hrhs-search-needle">Search...</label>';
    $search_form .= sprintf(
      '<input type="text" name="hrhs-search[needle]" id="hrhs-search-needle" value="%s">',
      empty( $search_params[ 'needle' ] ) ? '' : $search_params[ 'needle' ]
    );  

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
      <input type="checkbox" name="hrhs-search[haystacks][%s]" id="hrhs-search-haystacks-%s"%s%s>
      <label for="hrhs-search-haystacks-%s">%s</label>
      END;
      foreach ( $haystacks as $haystack ) {
        // If this haystack isn't searchable, disable it
        $disabled = array_key_exists( $haystack, $searchable_haystacks ) ? '' : ' disabled';
        // Make the checkbox "checked" unless it wasn't checked in the previous search
        // or if it is disabled
        $checked =  ( array_key_exists( 'haystacks', $search_params ) &&
        empty( $search_params[ 'haystacks' ][ $haystack ] ) ) ||
        ! empty( $disabled ) ? '' : ' checked';
        // Get the label for this haystack, "Unknown" if it doesn't exist
        $label = array_key_exists( $haystack, $this->search_types_label ) ? $this->search_types_label[ $haystack ] : 'Unknown';
        // If this haystack is disabled, mark it "members only"
        $label .= empty( $disabled ) ? '' : ' (members only)';
        // Add to the search form
        $search_form .= sprintf( $checkbox_template, $haystack, $haystack, $checked, $disabled, $haystack, $label );
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
