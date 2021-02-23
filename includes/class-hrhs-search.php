<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HRHS_Search {

  /* **********
   * Properties
   * **********/

  private $slug;
  private $search_types_fields;

  /* *******
   * Methods
   * *******/

  public function __construct( $params = array() ) {

    // FIXME: I'm sure there's a more efficient way of doing defaults
    $this->slug =
      array_key_exists( 'slug', $params )
      ? $param[ 'slug' ]
      : 'hrhs-database';
    $this->search_types_fields =
      array_key_exists( 'search_types_fields', $params )
      ? $params[ 'search_type_fields' ]
      : array(
        'default_cpt' => array(
          'default_field_1',
          'default_field_2',
          'default_field_3',
        )
      );

    $this->initialize_hrhs_save();
  }

  private function initialize_hrhs_save() {
    $this->create_hrhs_search_page();
  }

  public function create_hrhs_search_page() {
    add_action( 'init', array( $this, 'hrhs_search_page_rewrite_rules' ) );
    add_filter( 'query_vars', array( $this, 'hrhs_search_page_query_vars' ) );
    add_filter( 'template_include', array( $this, 'hrhs_search_page_template_include' ), 50 );
  }

  public function hrhs_search_page_rewrite_rules() {
    hrhs_debug( 'Running hrhs_search_page_rewrite_rules' );
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

}