<?php

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
      'name_info' => array(
        'slug' => 'name_entry',
        'singular_name' => 'Name Entry',
        'plural_name' => 'Name Entries',
        // TODO: Verify which fields should be searchable, currently following what was done on old site
        'fields' => array(
          array( 'label' => 'Surname',     'slug' => 'surname',   'search' => 'all',    'display' => 'all' ), // Order first
          array( 'label' => 'Given Name',  'slug' => 'givenname', 'search' => 'member', 'display' => 'all' ), // Order second
          array( 'label' => 'Birth',       'slug' => 'birth',     'search' => 'none',   'display' => 'member' ),
          array( 'label' => 'Death',       'slug' => 'death',     'search' => 'none',   'display' => 'member' ),
          array( 'label' => 'Marriage',    'slug' => 'marriage',  'search' => 'none',   'display' => 'member' ),
          array( 'label' => 'Remarks',     'slug' => 'remarks',   'search' => 'none',   'display' => 'member' ),
          array( 'label' => 'Information', 'slug' => 'infoname',  'search' => 'none',   'display' => 'all' ),
          array( 'label' => 'ID',          'slug' => 'ID',        'search' => 'none',   'display' => 'none' ),
        ),
      ),
      'news_info' => array(
        'slug' => 'news_entry',
        'singular_name' => 'News Entry',
        'plural_name' => 'News Entries',
        // TODO: Verify which fields should be searchable, currently following what was done on old site
        'fields' => array(
          array( 'label' => 'Year',       'slug' => 'year',  'search' => 'member', 'display' => 'member' ),
          array( 'label' => 'Newspaper',  'slug' => 'news',  'search' => 'none',   'display' => 'member' ),
          array( 'label' => 'Pages',      'slug' => 'pages', 'search' => 'none',   'display' => 'member' ),
          array( 'label' => 'Sort Order', 'slug' => 'sort',  'search' => 'none',   'display' => 'none' ), // Order first
        ),
      ),
      'place_info' => array(
        'slug' => 'place_entry',
        'singular_name' => 'Place Entry',
        'plural_name' => 'Place Entries',
        // TODO: Verify which fields should be searchable, currently following what was done on old site
        'fields' => array(
          array( 'label' => 'Placename',  'slug' => 'placename',  'search' => 'member', 'display' => 'member' ), // Order first
          array( 'label' => 'Othername',  'slug' => 'othername',  'search' => 'none',   'display' => 'member' ), // Order second
          array( 'label' => 'Location',   'slug' => 'location',   'search' => 'none',   'display' => 'member' ),
          array( 'label' => 'Remarks',    'slug' => 'remarks',    'search' => 'none',   'display' => 'member' ),
          array( 'label' => 'Infosource', 'slug' => 'infosource', 'search' => 'none',   'display' => 'member' ),
          array( 'label' => 'Designator', 'slug' => 'designator', 'search' => 'none',   'display' => 'member' ),
        ),
      ),
      'obit_info' => array(
        'slug' => 'obit_entry',
        'singular_name' => 'Obituary Entry',
        'plural_name' => 'Obituary Entries',
        // TODO: Verify which fields should be searchable, currently following what was done on old site
        'fields' => array(
          array( 'label' => 'Surname',       'slug' => 'surname',      'search' => 'all',    'display' => 'all' ), // Order first
          array( 'label' => 'Given Name',    'slug' => 'givenname',    'search' => 'member', 'display' => 'all' ), // Order second
          array( 'label' => 'Date of Birth', 'slug' => 'birth',        'search' => 'none',   'display' => 'all' ),
          array( 'label' => 'Date of Death', 'slug' => 'death',        'search' => 'none',   'display' => 'all' ),
          array( 'label' => 'Parents',       'slug' => 'parents',      'search' => 'none',   'display' => 'all' ),
          array( 'label' => 'Spouse',        'slug' => 'marriage',     'search' => 'none',   'display' => 'all' ),
          array( 'label' => 'Age',           'slug' => 'age',          'search' => 'none',   'display' => 'all' ),
          array( 'label' => 'Obit Location', 'slug' => 'obitlocation', 'search' => 'none',   'display' => 'all' ),
        ),
      ),
    );

    // Just in case instantiate_post_types() doesn't instantiate any post types
    $this->post_type_objs = array();

    $this->load_dependencies();
    $this->instantiate_post_types();
    $this->instantiate_search_page();
  }

  // Load dependencies
  private function load_dependencies() {
    require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-post-type.php';
    require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-search.php';
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
    // Register the database search page
    $this->search_page->hrhs_search_page_rewrite_rules();
    // Register the search page for each post type
    foreach ( $this->post_type_objs as $post_type_obj ) {
      $post_type_obj->register_post_type_search_page();
    }
    // Then flush the rewrite rules for them to take effect
    flush_rewrite_rules();

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
