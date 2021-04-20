<?php

// From tutorials: https://www.benmarshall.me/create-an-elementor-widget/
//                 https://github.com/bmarshall511/elementor-awesomesauce/
//                 https://developers.elementor.com/creating-an-extension-for-elementor/
//                 https://developers.elementor.com/creating-a-new-widget/

namespace HRHSElementor;
// FIXME: What happens if the namespace is changed to HRHSPlugin to match the rest of the plugin?

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use function HRHSPlugin\hrhs_debug;

final class HRHS_Elementor_Widgets {

  /* **********
   * Properties
   * **********/

   // The single instance of the class
   private static $instance = null;

  /* *******
   * Methods
   * *******/

  // Ensures that only one instance of the class is loaded or can be loaded
  public static function instance() {
    if ( is_null( self::$instance ) ) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  // Include individual widget files
  private function include_widget_files() {
    require_once HRHS_PLUGIN_PATH . 'elementor/class-hrhs-login-widget.php';
    require_once HRHS_PLUGIN_PATH . 'elementor/class-hrhs-search-widget.php';
  }

  // Register the new Elementor widgets
  public function register_widgets() {
    // hrhs_debug( 'Inside HRHS_Elementor_Widgets::register_widgets()' );
    // return;
    // It's now safe to include the widget files
    $this->include_widget_files();

    // Register the widget classes
    \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Widgets\HRHS_Login_Widget() );
    \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Widgets\HRHS_Search_Widget() );
  }

  public function __construct() {
    // hrhs_debug( 'Inside HRHS_Elementor_Widgets::__construct()' );
    add_action( 'elementor/init', array( $this, 'init' ) );
  }
  
  public function init() {
    // hrhs_debug( 'Inside HRHS_Elementor_Widgets::init()' );
    // Register the widgets
    add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_widgets' ) );
  }

}

// Instantiate the class
HRHS_Elementor_Widgets::instance();