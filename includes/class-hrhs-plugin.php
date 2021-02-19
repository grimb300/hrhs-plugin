<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HRHS_Plugin {

  /* **********
   * Properties
   * **********/

  // Post types
  protected $name_info_cpt;

  /* *******
   * Methods
   * *******/

  // Constructor
  public function __construct() {
    hrhs_debug( 'Running HRHS_Plugin:__construct()' );

    $this->load_dependencies();
    $this->instantiate_post_types();
  }

  // Load dependencies
  private function load_dependencies() {
    require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-post-type.php';
  }

  // Instantiate the post types
  private function instantiate_post_types() {
    $this->name_info_cpt = new HRHS_Post_Type();
  }
  
  // Run
  // TODO: Not sure this is really necessary
  public function run() {
    hrhs_debug( 'Running HRHS_Plugin:run()' );
  }

  // Plugin activation
  public function hrhs_activation() {
    hrhs_debug( 'Running HRHS_Plugin:hrhs_activation()' );
  }

  // Plugin deactivation
  public function hrhs_deactivation() {
    hrhs_debug( 'Running HRHS_Plugin:hrhs_deactivation()' );
  }
}