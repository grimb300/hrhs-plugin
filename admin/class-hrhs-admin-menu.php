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
      <?php // FIXME: Add link to anchor tag ?>
      <a href="" class="page-title-action">Add New</a>
      <hr class="wp-header-end">
      <h2 class="screen-reader-text">Filter posts list</h2>
      <?php // FIXME: Are these filters needed? If so, add link to anchor tag ?>
      <ul class="subsubsub">
        <li class="all"><a href="">All <span class="count">(###)</span></a> |</li>
        <li class="publish"><a href="">Published <span class="count">(###)</span></a> |</li>
        <li class="draft"><a href="">Draft <span class="count">(###)</span></a> |</li>
        <li class="trash"><a href="">Trash <span class="count">(###)</span></a></li>
      </ul>
      <form id="posts-filter" method="get">
        <p class="search-box">
          <label for="post-search-input" class="screen-reader-text">Search Posts:</label>
          <input type="search" name="s" id="post-search-input" value>
          <input type="submit" id="search-submit" class="button" value="Search Posts">
        </p>
        <input type="hidden" name="post_status" class="post_status_page" value="all">
        <input type="hidden" name="post_type" class="post_type_page" value="name_entry">
        <input type="hidden" id="_wpnonce" name="_wpnonce" value="foobarbaz">
        <input type="hidden" name="_wp_http_referer" value="/wp-admin/edit.php?s&post_status=all&post_type=name_entry&action=-1&m=0&paged=5&action2=-1">
        <div class="tablenav top">
          <div class="alignleft actions bulactions">
            <label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label>
            <select name="action" id="bulk-action-selector-top">
              <option value="-1">Bulk actions</option>
              <option value="edit" class="hide-if-no-js">Edit</option>
              <option value="trash">Move to Trash</option>
            </select>
            <input type="submit" id="doaction" class="button action" value="Apply">
          </div>
          <div class="alignleft actions">
            <label for="filter-by-date" class="screen-reader-text">Filter by date</label>
            <select name="m" id="filter-by-date">
              <option selected="selected" value="0">All dates</option>
              <option value="202103">March 20201</option>
            </select>
            <input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter">
          </div>
          <h2 class="screen-reader-text">Posts list navigation</h2>
          <div class="tablenav-pages">
            <span class="displaying-num">### items</span>
            <span class="pagination-links">
              <a href="" class="first-page button">
                <span class="screen-reader-text">First page</span>
                <span aria-hidden="true">&laquo;</span>
              </a>
              <a href="" class="prev-page button">
                <span class="screen-reader-text">Previous page</span>
                <span aria-hidden="true">&lsaquo;</span>
              </a>
              <span class="paging-input">
                <label for="current-page-selector" class="screen-reader-text">Current page</label>
                <input type="text" class="current-page" id="current-page-selector" name="paged" value="1" size="2" aria-describedby="table-paging">
                <span class="tablenav-paging-text"> of <span class="total-pages">###</span></span>
              </span>
              <a href="" class="next-page button">
                <span class="screen-reader-text">Next page</span>
                <span aria-hidden="true">&rsaquo;</span>
              </a>
              <a href="" class="last-page button">
                <span class="screen-reader-text">Last page</span>
                <span aria-hidden="true">&raquo;</span>
              </a>
            </span>
          </div>
          <br class="clear">
        </div>
        <h2 class="screen-reader-text">Posts list</h2>
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
                <?php // If I implement sorting, need to add conditionals on the classes (sortable, sorted, asc, desc, etc) ?>
                <?php // FIXME: Add link to anchor tag ?>
                <th id="<?php echo $slug; ?>" class="manage-column column-<?php echo $slug; ?> column-primary sortable desc" scope="col">
                  <a href="">
                    <span><?php echo $label; ?></span>
                    <span class="sorting-indicator"></span>
                  </a>
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
                  <?php // The CPT has a label for the checkbox ?>
                  <input type="checkbox" name="post[]" id="cb-select-<?php echo $post_id; ?>" value="<?php echo $post_id; ?>">
                  <?php // The CPT has a div containing two spans for the locked indicator ?>
                </th>
                <?php foreach ( $post_type_def[ 'fields' ] as $index => $field ) { ?>
                  <?php
                    $slug = $field[ 'slug' ];
                    $label = $field[ 'label' ];
                    // All columns get two classes by default
                    $class_list = array( $slug, 'column-' . $slug );
                    if ( 0 === $index ) {
                      // The first column gets a few extra classes (maybe not all necessary)
                      $class_list[] = 'has-row-actions';
                      $class_list[] = 'column-primary';
                      $class_list[] = 'page-' . $slug;
                    }
                  ?>
                  <td class="<?php echo implode( ' ', $class_list ); ?>" data-colname="<?php echo $label; ?>">
                    <?php
                    /**
                     * The CPT contains locked-info, a hidden div with various post info, the "row-actions", and a button (what is the button for?)
                     * The CPT wraps the "title" text with a strong tag and an anchor tag linking to the edit page
                     *     I suspect this is to indicate that clicking the "title" is the quick way to edit the post
                     * FIXME: Do I want the anchor tag, if so where will the link take me?
                     */
                    ?>
                    <?php echo $result[ $slug ]; ?>
                    <?php
                    // If this is the first column, add the "row-actions" div
                    // FIXME: Do all of these actions make sense? Pick which ones need to work and add links to anchor tag
                    if ( 0 === $index ) {
                      ?>
                      <div class="row-actions">
                        <span class="edit"><a href="" aria-label="Edit <?php echo $post_id; ?>">Edit</a> | </span>
                        <span class="inline hide-if-no-js"><button type="button" class="button-link editinline" aria-label="Quick edit <?php echo $post_id; ?> inline" aria-expanded="false">Quick&nbsp;Edit</button> | </span>
                        <span class="trash"><a href="" class="submitdelete" aria-label="Move <?php echo $post_id; ?> to the Trash">Trash</a> | </span>
                        <span class="view"><a href="" rel="bookmark" aria-label="View <?php echo $post_id; ?>">View</a></span>
                      </div>
                      <?php
                    }
                    ?>
                  </td>
                <?php } ?>
              </tr>
            <?php } ?>
          </tbody>
        </table>
        <div class="tablenav bottom">
          <div class="alignleft actions bulactions">
            <label for="bulk-action-selector-bottom" class="screen-reader-text">Select bulk action</label>
            <select name="action" id="bulk-action-selector-bottom">
              <option value="-1">Bulk actions</option>
              <option value="edit" class="hide-if-no-js">Edit</option>
              <option value="trash">Move to Trash</option>
            </select>
            <input type="submit" id="doaction" class="button action" value="Apply">
          </div>
          <div class="alignleft actions">
          </div>
          <div class="tablenav-pages">
            <span class="displaying-num">### items</span>
            <span class="pagination-links">
              <a href="" class="first-page button">
                <span class="screen-reader-text">First page</span>
                <span aria-hidden="true">&laquo;</span>
              </a>
              <a href="" class="prev-page button">
                <span class="screen-reader-text">Previous page</span>
                <span aria-hidden="true">&lsaquo;</span>
              </a>
              <span class="paging-input">
                <label for="current-page-selector" class="screen-reader-text">Current page</label>
                <input type="text" class="current-page" id="current-page-selector" name="paged" value="1" size="2" aria-describedby="table-paging">
                <span class="tablenav-paging-text"> of <span class="total-pages">###</span></span>
              </span>
              <a href="" class="next-page button">
                <span class="screen-reader-text">Next page</span>
                <span aria-hidden="true">&rsaquo;</span>
              </a>
              <a href="" class="last-page button">
                <span class="screen-reader-text">Last page</span>
                <span aria-hidden="true">&raquo;</span>
              </a>
            </span>
          </div>
        </div>
      </form>
    </div>
    <?php
  }
}