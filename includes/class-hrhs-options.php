<?php

namespace HRHSPlugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HRHS_Options {

  /* **********
   * Properties
   * **********/

  private $options = array( 'hrhs_options' );

  /*********
   * Methods
   * *******/

  public function __construct( $params = array() ) {

    // FIXME: I'm sure there's a more efficient way of doing defaults
    if ( array_key_exists( 'options', $params ) ) {
      $this->options = $params[ 'options' ];
    }

    // If the options don't exist, create them
    foreach ( $this->options as $option ) {
      if ( 'false' === get_option( $option ) ) {
        update_option( $option, array() );
      }
    }
  }

  public function get( $option = 'hrhs_options' ) {
    return get_option( $option );
  }

  public function set( $value, $option = 'hrhs_options' ) {
    update_option( $option, $value );
  }

}