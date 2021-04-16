<?php

namespace HRHSPlugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HRHS_Plugin {

  /* **********
   * Properties
   * **********/

  // Post types
  protected $post_type_defs;
  protected $post_type_objs;
  // Search page
  protected $search_page;

  /* *******
   * Methods
   * *******/

  // Constructor
  public function __construct() {
    // Define the post types
    $this->post_type_defs = array(
      'name_entry' => array(
        'slug' => 'name_entry',
        'singular_name' => 'Name Entry',
        'plural_name' => 'Name Entries',
        // TODO: Verify which fields should be searchable, currently following what was done on old site
        'fields' => array(
          array(
            'label' => 'Surname',      'slug' => 'surname',
            'search' => 'all',         'display' => 'all',
            'sql_data_type' => 'varchar(75)', // Data type from legacy MySQL database
          ), // Order first
          array(
            'label' => 'Given Name',   'slug' => 'givenname',
            'search' => 'member',      'display' => 'all',
            'sql_data_type' => 'varchar(75)', // Data type from legacy MySQL database
          ), // Order second
          array(
            'label' => 'Birth',        'slug' => 'birth',
            'search' => 'none',        'display' => 'member',
            'sql_data_type' => 'varchar(25)', // Data type from legacy MySQL database
          ),
          array(
            'label' => 'Death',        'slug' => 'death',
            'search' => 'none',        'display' => 'member',
            'sql_data_type' => 'varchar(25)', // Data type from legacy MySQL database
          ),
          array(
            'label' => 'Marriage',     'slug' => 'marriage',
            'search' => 'none',        'display' => 'member',
            'sql_data_type' => 'varchar(25)', // Data type from legacy MySQL database
          ),
          array(
            'label' => 'Remarks',      'slug' => 'remarks',
            'search' => 'none',        'display' => 'member',
            'sql_data_type' => 'varchar(75)', // Data type from legacy MySQL database
          ),
          array(
            'label' => 'Information',  'slug' => 'infoname',
            'search' => 'none',        'display' => 'all',
            'sql_data_type' => 'varchar(50)', // Data type from legacy MySQL database
          ),
          // Changing the name of the 'ID' field to not be confused with the 'id' field automatically added by default with HRHS_Database
          // array( 'label' => 'ID',          'slug' => 'ID',        'search' => 'none',   'display' => 'none' ),
          array(
            'label' => 'ID',           'slug' => 'legacyID',
            'search' => 'none',        'display' => 'none',
            'sql_data_type' => 'int(9)', // Data type from legacy MySQL database
            ),
        ),
        'database_version' => '0.1',
      ),
      'news_entry' => array(
        'slug' => 'news_entry',
        'singular_name' => 'News Entry',
        'plural_name' => 'News Entries',
        // TODO: Verify which fields should be searchable, currently following what was done on old site
        'fields' => array(
          array(
            'label' => 'Year',       'slug' => 'year',
            'search' => 'member', 'display' => 'member',
            'sql_data_type' => 'varchar(4)', // Data type from legacy MySQL database
          ),
          array(
            'label' => 'Newspaper',  'slug' => 'news',
            'search' => 'none',   'display' => 'member',
            'sql_data_type' => 'varchar(100)', // Data type from legacy MySQL database
          ),
          array(
            'label' => 'Pages',      'slug' => 'pages',
            'search' => 'none',   'display' => 'member',
            'sql_data_type' => 'varchar(4)', // Data type from legacy MySQL database
          ),
          array(
            'label' => 'Sort Order', 'slug' => 'sort',
            'search' => 'none',   'display' => 'none',
            'sql_data_type' => 'varchar(6)', // Data type from legacy MySQL database
          ), // Order first,
        ),
        'database_version' => '0.1',
      ),
      'place_entry' => array(
        'slug' => 'place_entry',
        'singular_name' => 'Place Entry',
        'plural_name' => 'Place Entries',
        // TODO: Verify which fields should be searchable, currently following what was done on old site
        'fields' => array(
          array(
            'label' => 'Placename',  'slug' => 'placename',
            'search' => 'member', 'display' => 'member',
            'sql_data_type' => 'varchar(50)', // Data type from legacy MySQL database
          ), // Order first
          array(
            'label' => 'Othername',  'slug' => 'othername',
            'search' => 'none',   'display' => 'member',
            'sql_data_type' => 'varchar(100)', // Data type from legacy MySQL database
          ), // Order second
          array(
            'label' => 'Location',   'slug' => 'location',
            'search' => 'none',   'display' => 'member',
            'sql_data_type' => 'varchar(250)', // Data type from legacy MySQL database
          ),
          array(
            'label' => 'Remarks',    'slug' => 'remarks',
            'search' => 'none',   'display' => 'member',
            'sql_data_type' => 'varchar(250)', // Data type from legacy MySQL database
          ),
          array(
            'label' => 'Infosource', 'slug' => 'infosource',
            'search' => 'none',   'display' => 'member',
            'sql_data_type' => 'varchar(50)', // Data type from legacy MySQL database
          ),
          array(
            'label' => 'Designator', 'slug' => 'designator',
            'search' => 'none',   'display' => 'member',
            'sql_data_type' => 'varchar(12)', // Data type from legacy MySQL database
          ),
        ),
        'database_version' => '0.1',
      ),
      // 'obit_entry' => array(
      //   'slug' => 'obit_entry',
      //   'singular_name' => 'Obituary Entry',
      //   'plural_name' => 'Obituary Entries',
      //   // TODO: Verify which fields should be searchable, currently following what was done on old site
      //   'fields' => array(
      //     array( 'label' => 'Surname',       'slug' => 'surname',      'search' => 'all',    'display' => 'all' ), // Order first
      //     array( 'label' => 'Given Name',    'slug' => 'givenname',    'search' => 'member', 'display' => 'all' ), // Order second
      //     array( 'label' => 'Date of Birth', 'slug' => 'birth',        'search' => 'none',   'display' => 'all' ),
      //     array( 'label' => 'Date of Death', 'slug' => 'death',        'search' => 'none',   'display' => 'all' ),
      //     array( 'label' => 'Parents',       'slug' => 'parents',      'search' => 'none',   'display' => 'all' ),
      //     array( 'label' => 'Spouse',        'slug' => 'marriage',     'search' => 'none',   'display' => 'all' ),
      //     array( 'label' => 'Age',           'slug' => 'age',          'search' => 'none',   'display' => 'all' ),
      //     array( 'label' => 'Obit Location', 'slug' => 'obitlocation', 'search' => 'none',   'display' => 'all' ),
      //   ),
      //   'database_version' => '0.1',
      // ),
    );

    // Just in case instantiate_post_types() doesn't instantiate any post types
    $this->post_type_objs = array();

    $this->load_dependencies();
    $this->instantiate_post_types();
    // $this->instantiate_search_page();
    add_action( 'plugins_loaded', array( $this, 'register_elementor_widgets' ) );
    // $this->register_elementor_widgets();
    add_action( 'wp_authenticate', array( $this, 'hrhs_handle_empty_login' ), 1, 2 );
    add_action( 'wp_login_failed', array( $this, 'hrhs_handle_failed_login' ) );

    // Initialize the options
    // NOTE: Using options in WordPress in this way feels wrong
    $options_obj = new HRHS_Options();
    $options_obj->set( array( 'post_type_defs' => $this->post_type_defs ) );
  }

  // Load dependencies
  private function load_dependencies() {
    require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-post-type.php';
    // require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-search.php';
    require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-simple-search.php';
    require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-options.php';
  }

  // Instantiate the post types
  private function instantiate_post_types() {
    // Iterate across the post types in post_type_defs
    foreach ( $this->post_type_defs as $post_type => $post_type_def ) {
      $this->post_type_objs[ $post_type ] = new HRHS_Post_type( $post_type_def );
    }
  }

  // Instantiate the search page
  private function instantiate_search_page() {
    // Build the search_types_fields and search_types_label arrays out of post_types_defs
    $search_types_fields = array();
    $search_types_label = array();
    foreach ( $this->post_type_defs as $post_type ) {
      $search_types_fields[ $post_type[ 'slug' ] ] = $post_type[ 'fields' ];
      $search_types_label[ $post_type[ 'slug' ] ] = $post_type[ 'plural_name' ];
    }
    $this->search_page = new HRHS_Search( array(
      // Using the default slug and title for now
      'search_types_fields' => $search_types_fields,
      'search_types_label' => $search_types_label
    ) );
  }

  // Register the HRHS specific Elementor widgets
  public function register_elementor_widgets() {
    // Required versions
    $MINIMUM_ELEMENTOR_VERSION = '2.0.0';
    $MINIMUM_PHP_VERSION = '7.0';
 
    // Check if Elementor insatlled and activated
    if ( ! did_action( 'elementor/loaded' ) ) {
      hrhs_debug( 'HRHS_Elementor_Widgets requires Elementor to be installed and activated.' );
      return;
    }

    // Check for required Elementor version
    if ( ! version_compare( ELEMENTOR_VERSION, $MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
      hrhs_debug( sprintf( 'HRHS_Elementor_Widgets requires Elementor version %s or greater.', $MINIMUM_ELEMENTOR_VERSION ) );
      return;
    }

    // Check for required PHP version
    if ( version_compare( PHP_VERSION, $MINIMUM_PHP_VERSION, '<' ) ) {
      hrhs_debug( sprintf( 'HRHS_Elementor_Widgets requires PHP version %s or greater.', $MINIMUM_PHP_VERSION ) );
      return;
    }

    // Once we get here, We have passed all validation checks so we can safely include our widgets.
    // hrhs_debug( 'HRHS_Elementor_Widgets has met all of the requirements, loading widgets' );
    require_once HRHS_PLUGIN_PATH . 'elementor/class-hrhs-elementor-widgets.php';
  }

  // Get the HTTP_REFERER (if it exists) and strip off any "login" parameters in the URL
  private function hrhs_get_referer() {
    $referer = preg_replace( '/^(.*)\?.*$/', '$1', $_SERVER[ 'HTTP_REFERER' ] );
    return $referer;
  }

  // Elegantly handle member logins with empty credentials from non-admin pages
  public function hrhs_handle_empty_login( $username, $pwd ) {
    // hrhs_debug( 'Inside hrhs_handle_empty_login()' );
    $referer = $this->hrhs_get_referer();
    // If there's a valid referer and it is not the default WP login or admin page
    if ( ! empty( $referer ) && ! strstr( $referer, 'wp-login' ) && ! strstr( $referer, 'wp-admin' ) ) {
      if ( empty( $username ) ) {
        wp_redirect( $referer . '?login=empty_user' );
        exit;
      }
      if ( empty( $pwd ) ) {
        wp_redirect( $referer . '?login=empty_pwd' );
        exit;
      }
    }
  }
  
  // Elegantly handle failed member logins from non-admin pages
  public function hrhs_handle_failed_login( $username ) {
    // hrhs_debug( 'Inside hrhs_handle_failed_login()' );
    $referer = $this->hrhs_get_referer();
    // If there's a valid referer and it is not the default WP login or admin page
    if ( ! empty( $referer ) && ! strstr( $referer, 'wp-login' ) && ! strstr( $referer, 'wp-admin' ) ) {
      wp_redirect( $referer . '?login=failed' );
      exit;
    }
  }
 
  // Run
  // TODO: Not sure this is really necessary
  public function run() {
  }

  // Plugin activation
  public function hrhs_activation() {
    // Register each of the post types
    foreach ( $this->post_type_objs as $post_type_obj ) {
      $post_type_obj->register_hrhs_post_type();
    }

    /**
     * Comment out the registering of search pages for now,
     * going the elementor custom widgets route instead
     */
    // // Register the database search page
    // $this->search_page->hrhs_search_page_rewrite_rules();
    // // Register the search page for each post type
    // foreach ( $this->post_type_objs as $post_type_obj ) {
    //   $post_type_obj->register_post_type_search_page();
    // }
    // // Then flush the rewrite rules for them to take effect
    // flush_rewrite_rules();
    /**
     * End comment out registering search pages
     */

    // Create the default HRHS member user (if necessary)
    // NOTE: The email MUST be unique to all other users
    if ( ! get_user_by( 'login', 'HRHS-MEMBER' ) ) {
      // hrhs_debug( 'User HRHS-MEMBER does not exist, creating...' );
      wp_insert_user( array(
        'user_login' => 'HRHS-MEMBER',
        'user_pass' => 'STORIES',
        'user_email' => 'test@test.com', // TODO: Default hrhs email?
        'first_name' => 'HRHS Member',
        'display_name' => 'HRHS Member',
        'nickname' => 'HRHS Member',
        'role' => 'subscriber',
        'show_admin_bar_front' => true
      ) );
    } else {
      // hrhs_debug( 'User HRHS-MEMBER already exists, do nothing' );
    }

    // Install the HRHS custom database tables
    require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-database.php';
    foreach ( $this->post_type_defs as $post_type ) {
      HRHS_Database::install( array(
        'table_name' => $post_type[ 'slug' ],
        'fields' => array_map(
          function ( $field ) {
            return array(
              'name' => $field[ 'slug' ],
              'data_type' => $field[ 'sql_data_type' ]
            );
          },
          $post_type[ 'fields' ]
        ),
        'version' => $post_type[ 'database_version' ],
      ) );
    }
  }

  // Plugin deactivation
  public function hrhs_deactivation() {
    // Flush the rewrite rules so changes take effect
    flush_rewrite_rules();
  }

  /* ******************
   * Accessor functions
   * ******************/

  // Get the search post types/fields
  // TODO: Commenting out until I find a use for it
  // public function get_search_types_fields() {
  //   return $this->search_page->get_search_types_fields();
  // }
}
