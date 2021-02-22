<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HRHS_Plugin {

  /* **********
   * Properties
   * **********/

  // Post types
  protected $post_types;

  /* *******
   * Methods
   * *******/

  // Constructor
  public function __construct() {
    // Just in case instantiate_post_types() doesn't instantiate any post types
    $this->post_types = array();

    $this->load_dependencies();
    $this->instantiate_post_types();
  }

  // Load dependencies
  private function load_dependencies() {
    require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-post-type.php';
  }

  // Instantiate the post types
  private function instantiate_post_types() {
    // Default post type
    $this->post_types[ 'default' ] = new HRHS_Post_Type();
    // Name info table from MySQL database (nameinfo)
    $this->post_types[ 'name_info' ] = new HRHS_Post_Type( array(
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
  
  // Run
  // TODO: Not sure this is really necessary
  public function run() {
  }

  // Plugin activation
  public function hrhs_activation() {
    // Register each of the post types
    foreach ( $this->post_types as $post_type_obj ) {
      $post_type_obj->register_hrhs_post_type();
    }
    // Then flush the rewrite rules for them to take effect
    flush_rewrite_rules();
  }

  // Plugin deactivation
  public function hrhs_deactivation() {
    // Flush the rewrite rules so changes take effect
    flush_rewrite_rules();
  }
}
