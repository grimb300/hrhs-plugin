<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HRHS_Post_Type {

  /* **********
   * Properties
   * **********/

  private $slug;
  private $fields;

  /* *******
   * Methods
   * *******/

  public function __construct( $params = array() ) {
    hrhs_debug( 'Running HRHS_Post_Type:__construct()' );

    $this->slug = 'generic_cpt';
    $this->fields = array( 'name', 'address', 'phone_number' );

    $this->initialize_hrhs_post_type();
  }
  
  private function initialize_hrhs_post_type() {
    add_action( 'init', array( $this, 'register_hrhs_post_type' ) );
  }

  public function register_hrhs_post_type() {
    $post_type_args = array(
      'label' => 'Generic CPT',
      'description' => 'Generic Custom Post Type, nothing special here',
      'public' => true,
      'register_meta_box_cb' => array( $this, 'register_hrhs_post_type_meta_box' ),
    );
    register_post_type( $this->slug, $post_type_args );
  }

  public function register_hrhs_post_type_meta_box( $post ) {
    add_meta_box( /** NEED TO ADD ARGS HERE */);
  }
}