<?php

namespace HRHSPlugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HRHS_Admin_Menu {

  /* **********
   * Properties
   * **********/

  /* *******
   * Methods
   * *******/

  // Constructor
  public function __construct() {
    add_action( 'admin_menu', array( $this, 'add_root_menu' ) );
    add_action( 'admin_menu', array( $this, 'add_cpt_submenu' ) );
  }

  // Add the root menu
  public function add_root_menu() {
    add_menu_page(
      'HRHS Database',                     // Page title
      'HRHS Database',                     // Menu title
      'edit_posts',                        // Required capability to display menu
      'hrhs-database-menu',                // Menu slug
      array( $this, 'display_root_menu' ), // Callback function to display the page
      'dashicons-hammer',                  // Icon url
      6                                    // Menu position
    );
  }

  // Add the custom post type submenu items
  public function add_cpt_submenu() {
    // Get the post_type_defs out of the options
    require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-options.php';
    $options_obj = new HRHS_Options();
    $my_options = $options_obj->get();
    $post_type_defs = $my_options[ 'post_type_defs' ];

    // Add a submenu page per post type
    foreach ( $post_type_defs as $post_type ) {
      add_submenu_page(
        'hrhs-database-menu',                        // Parent menu slug
        $post_type[ 'plural_name' ],                 // Page title
        $post_type[ 'plural_name' ],                 // Menu title
        'edit_posts',                                // Required capability to display menu
        "edit.php?post_type={$post_type[ 'slug' ]}", // Menu slug
        // $function:callable, // Callback function to display the page
        // $position:integer|null // Menu position
      );
    }

  }

  // Display the root menu
  public function display_root_menu() {
    if ( !current_user_can( 'edit_posts' ) )  {
      wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    ?>
    <div class="wrap">
      <p>Here is where the form would go if I actually had options.</p>
    </div>
    <?php
  }
}