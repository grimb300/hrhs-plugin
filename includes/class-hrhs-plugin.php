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
        'fields' => array(
          array( 'label' => 'Surname',       'slug' => 'surname',   'searchable' => 'all', ),
          array( 'label' => 'Given Name',    'slug' => 'givenname', 'searchable' => 'member' ),
          array( 'label' => 'Date of Birth', 'slug' => 'birth',     'searchable' => 'member' ),
          array( 'label' => 'Date of Death', 'slug' => 'death',     'searchable' => 'member' ),
          array( 'label' => 'Spouse',        'slug' => 'marriage',  'searchable' => 'member' ),
          array( 'label' => 'Remarks',       'slug' => 'remarks',   'searchable' => 'member' ),
          array( 'label' => 'Info Name',     'slug' => 'infoname',  'searchable' => 'member' ),
          array( 'label' => 'ID',            'slug' => 'ID',        'searchable' => 'none' ),
        ),
      ),
      'news_info' => array(
        'slug' => 'news_entry',
        'singular_name' => 'News Entry',
        'plural_name' => 'News Entries',
        'fields' => array(
          array( 'label' => 'Year',            'slug' => 'year',  'searchable' => 'member', ),
          array( 'label' => 'Newspaper',       'slug' => 'news',  'searchable' => 'member' ),
          array( 'label' => 'Number of Pages', 'slug' => 'pages', 'searchable' => 'member' ),
          array( 'label' => 'Sort Order',      'slug' => 'sort',  'searchable' => 'none' ),
       ),
      ),
      'place_info' => array(
        'slug' => 'place_entry',
        'singular_name' => 'Place Entry',
        'plural_name' => 'Place Entries',
        'fields' => array(
          array( 'label' => 'Place Name',  'slug' => 'placename',  'searchable' => 'member', ),
          array( 'label' => 'Other Name',  'slug' => 'othername',  'searchable' => 'member' ),
          array( 'label' => 'Designator',  'slug' => 'designator', 'searchable' => 'member' ),
          array( 'label' => 'Location',    'slug' => 'location',   'searchable' => 'member' ),
          array( 'label' => 'Remarks',     'slug' => 'remarks',    'searchable' => 'member' ),
          array( 'label' => 'Info Source', 'slug' => 'infosource', 'searchable' => 'member' ),
        ),
      ),
      'obit_info' => array(
        'slug' => 'obit_entry',
        'singular_name' => 'Obituary Entry',
        'plural_name' => 'Obituary Entries',
        'fields' => array(
          array( 'label' => 'Surname',       'slug' => 'surname',      'searchable' => 'all', ),
          array( 'label' => 'Given Name',    'slug' => 'givenname',    'searchable' => 'member' ),
          array( 'label' => 'Date of Birth', 'slug' => 'birth',        'searchable' => 'member' ),
          array( 'label' => 'Date of Death', 'slug' => 'death',        'searchable' => 'member' ),
          array( 'label' => 'Parents',       'slug' => 'parents',      'searchable' => 'member' ),
          array( 'label' => 'Spouse',        'slug' => 'marriage',     'searchable' => 'member' ),
          array( 'label' => 'Age',           'slug' => 'age',          'searchable' => 'member' ),
          array( 'label' => 'Obit Location', 'slug' => 'obitlocation', 'searchable' => 'member' ),
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
    // Default post type
    $this->post_type_objs[ 'default' ] = new HRHS_Post_Type();
    // Name info table from MySQL database (nameinfo)
    $this->post_type_objs[ 'name_info' ] = new HRHS_Post_Type( array(
      'slug' => 'name_entry',
      'singular_name' => 'Name Entry',
      'plural_name' => 'Name Entries',
      'fields' => array(
        array(
          'slug' => 'surname',
          'label' => 'Surname',
        ),
        array(
          'slug' => 'givenname',
          'label' => 'Given Name',
        ),
        array(
          'slug' => 'birth',
          'label' => 'Date of Birth',
        ),
        array(
          'slug' => 'death',
          'label' => 'Date of Death',
        ),
        array(
          'slug' => 'marriage',
          'label' => 'Spouse',
        ),
        array(
          'slug' => 'remarks',
          'label' => 'Remarks',
        ),
        array(
          'slug' => 'infoname',
          'label' => 'Info Name',
        ),
      ),
    ) );
  }

  // Instantiate the search page
  private function instantiate_search_page() {
    $this->search_page = new HRHS_Search();
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
    // Then flush the rewrite rules for them to take effect
    flush_rewrite_rules();
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
  public function get_search_types_fields() {
    return $this->search_page->get_search_types_fields();
  }
}
