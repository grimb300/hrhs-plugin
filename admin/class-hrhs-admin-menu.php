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
    add_action( 'admin_menu', array( $this, 'add_custom_table_submenu' ) );
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

  // Add the custom post type submenu items
  public function add_custom_table_submenu() {
    // Get the post_type_defs out of the options
    require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-options.php';
    $options_obj = new HRHS_Options();
    $my_options = $options_obj->get();
    $post_type_defs = $my_options[ 'post_type_defs' ];

    // Add a submenu page per post type
    foreach ( $post_type_defs as $post_type ) {
      add_submenu_page(
        'hrhs-database-menu',                           // Parent menu slug
        $post_type[ 'plural_name' ],                    // Page title
        $post_type[ 'plural_name' ],                    // Menu title
        'edit_posts',                                   // Required capability to display menu
        "hrhs-db-{$post_type[ 'slug' ]}-menu",          // Menu slug
        array( $this, 'display_custom_table_submenu' ), // Callback function to display the page
        // $position:integer|null // Menu position
      );
    }
  }

  // Display the root menu page
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

  // Display the custom table based submenu page
  // This emulates the post type edit page
  public function display_custom_table_submenu() {
    if ( !current_user_can( 'edit_posts' ) )  {
      wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    // Get the menu slug, strip out the post type slug
    $menu_slug = array_key_exists( 'page', $_REQUEST ) ? $_REQUEST[ 'page' ] : null;
    $post_type_slug = str_replace( array( 'hrhs-db-', '-menu' ), array( '', '' ), $menu_slug );
    // hrhs_debug( 'menu_slug: ' . $menu_slug );
    // hrhs_debug( 'post_type_slug: ' . $post_type_slug );

    // Get the post_type_defs for this post type out of the options
    require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-options.php';
    $options_obj = new HRHS_Options();
    $my_options = $options_obj->get();
    $post_type_def = $my_options[ 'post_type_defs' ][ $post_type_slug ];
    // hrhs_debug( 'post_type_def:' );
    // hrhs_debug( $post_type_def );

    ?>
    <div class="wrap">
      <h1 class="wp-heading-inline"><?php echo $post_type_def[ 'plural_name' ]; ?></h1>
      <!-- The CPT has a "Add New" button in the h1 -->
      <!-- The CPT has a bunch of search and table nav stuff here -->
      <table class="wp-list-table widefat fixed striped table-view-list posts">
        <thead>
          <tr>
            <td id="cb" class="manage-column column-cb check-column">
              <label for="cb-select-all-1" class="screen-reader-text">Select All</label>
              <input type="checkbox" id="cb-select-all-1">
            </td>
            <?php foreach ( $post_type_def[ 'fields' ] as $field ) { ?>
              <?php $slug = $field[ 'slug' ]; ?>
              <?php $label = $field[ 'label' ]; ?>
              <th id="<?php echo $slug; ?>" class="manage-column column-<?php echo $slug; ?> column-primary sortable desc" scope="col">
                <!-- The CPT has a link and sorting indicator -->
                <span><?php echo $label; ?></span>
              </th>
            <?php } ?>
          </tr>
        </thead>
        <tbody id="the-list">
          <?php require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-database.php'; ?>
          <?php $results = HRHS_Database::get_results( array( 'name' => $post_type_slug ) ); ?>
          <?php foreach ( $results as $result ) { ?>
            <?php $post_id = $result[ 'id' ]; ?>
            <tr id="post-<?php echo $post_id; ?>" class="iedit author-self level-0 post-<?php echo $post_id; ?> type-name_entry status-publish hentry entry">
              <th class="check-column" scope="row">
                <!-- The CPT has a label for the checkbox -->
                <input type="checkbox" name="post[]" id="cb-select-<?php echo $post_id; ?>" value="<?php echo $post_id; ?>">
                <!-- The CPT has a div containing two spans for the locked indicator -->
              </th>
              <?php foreach ( $post_type_def[ 'fields' ] as $field ) { ?>
                <?php $slug = $field[ 'slug' ]; ?>
                <?php $label = $field[ 'label' ]; ?>
                <td class="<?php echo $slug; ?> column-<?php echo $slug ?> has-row-actions column-primary page-<?php echo $slug; ?>" data-colname="<?php echo $label; ?>">
                  <!-- The CPT contains locked-info, a hidden div, the "row-actions", and a button -->
                  <strong>
                    <!-- The CPT wraps the text with an anchor tag linking to the edit page -->
                    <?php echo $result[ $slug ]; ?>
                  </strong>
                </td>
              <?php } ?>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
    <?php
  }
}