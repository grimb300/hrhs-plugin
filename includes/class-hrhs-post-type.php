<?php

namespace HRHSPlugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HRHS_Post_Type {

  /* **********
   * Properties
   * **********/

  private $slug;
  private $singular_name;
  private $plural_name;
  private $icon;
  private $fields;
  private $search_page_slug;
  private $search_page_title;
  private $search_page_types_fields;
  private $search_page;

  /* *******
   * Methods
   * *******/

  public function __construct( $params = array() ) {

    // FIXME: I'm sure there's a more efficient way of doing defaults
    $this->slug =
      array_key_exists( 'slug', $params )
      ? $params[ 'slug' ]
      : 'default_cpt';
    $this->singular_name =
      array_key_exists( 'singular_name', $params )
      ? $params[ 'singular_name' ]
      : 'Default Post';
    $this->plural_name =
      array_key_exists( 'plural_name', $params )
      ? $params[ 'plural_name' ]
      : 'Default Posts';
    $this->icon =
      array_key_exists( 'icon', $params )
      ? $params[ 'icon' ]
      : 'dashicons-hammer';
    $this->fields =
      array_key_exists( 'fields', $params )
      ? $params[ 'fields' ]
      : array(
          array(
            'slug' => 'default_field_1',
            'label' => 'Default Field 1',
            'default' => null,
          ),
          array(
            'slug' => 'default_field_2',
            'label' => 'Default Field 2',
            'default' => null,
          ),
          array(
            'slug' => 'default_field_3',
            'label' => 'Default Field 3',
            'default' => null,
          ),
        );

    // Search page params
    $this->search_page_slug = $this->slug . '-search';
    $this->search_page_title = $this->plural_name . ' Search';
    $this->search_page_types_fields = array(
      $this->slug => $this->fields
    );
    $this->search_page_types_label = array(
      $this->slug => $this->plural_name
    );

    $this->initialize_hrhs_post_type();
    $this->instantiate_search_page( $params );
  }
  
  private function initialize_hrhs_post_type() {
    add_action( 'init', array( $this, 'register_hrhs_post_type' ) );
    add_action( 'save_post', array( $this, 'save_hrhs_post_type' ));
    // add_filter( 'the_title', array( $this, 'display_hrhs_post_type_title' ), 10, 2 );
    add_filter( 'the_content', array( $this, 'display_hrhs_post_type_content' ) );
  }

  private function instantiate_search_page( $params ) {
    $this->search_page = new HRHS_Search( array(
      'slug' => $this->search_page_slug,
      'title' => $this->search_page_title,
      'search_types_fields' => $this->search_page_types_fields,
      'search_types_label' => $this->search_page_types_label
    ) );
  }

  public function register_hrhs_post_type() {
    $post_type_labels = array(
      'name' => $this->plural_name,
      'singular_name' => $this->singular_name,
      'all_items' => "All {$this->plural_name}",
    );
    $post_type_args = array(
      'labels' => $post_type_labels,
      'description' => 'Default Custom Post Type, nothing special here',
      'public' => true,
      'register_meta_box_cb' => array( $this, 'register_hrhs_post_type_meta_box' ),
      'supports' => false, // Default is array( 'title', 'editor' ), I want only the meta_box (defined below)
      'menu_icon' => $this->icon,
      'has_archive' => true, // Set to false if don't want archive page
    );
    register_post_type( $this->slug, $post_type_args );
  }

  public function register_hrhs_post_type_meta_box( $post ) {
    $post_type = get_post_type( $post );
    $meta_box_id = $post_type . '_meta_box';
    add_meta_box(
      $meta_box_id,                 // id attribute of the meta box
      "{$this->singular_name} Fields",   // meta box title
      array( $this, 'display_hrhs_post_type_meta_box' ), // callback, display function
      $post_type,                   // screen(s) to display meta box 
      'advanced',                   // context, built in options: advanced (default), normal, side
      'high',                       // priority within the context: high, core, default (default), low
      array(                        // $args passed to the callback
        'input_id_prefix' => $post_type . '_field',
      ),
    );
  }

  public function display_hrhs_post_type_meta_box( $post ) {
    $input_prefix = $this->slug . '_field';
    $all_post_meta = get_post_meta( $post->ID );
    ?>
    <table class="form-table">
      <tbody>
        <?php
        foreach( $this->fields as $field ) {
          $input_id = $input_prefix . '_' . $field[ 'slug' ];
          $input_name = $input_prefix . '[' . $field[ 'slug' ] . ']';
          $input_value = array_key_exists( 'default', $field ) && ! is_null( $field[ 'default' ] ) ? $field[ 'default' ] : '';
          if ( array_key_exists( $field[ 'slug' ], $all_post_meta ) ) {
            // Because the post_meta fields are being retrieved at once,
             // each field is an array and the value required is at index 0
            $input_value = $all_post_meta[ $field[ 'slug' ] ][ 0 ];
          }
          ?>
        <tr>
          <th scope="row">
            <label for="<?php echo $input_id; ?>"><?php echo $field[ 'label' ]; ?></label>
          </th>
          <td>
            <input type="text" name="<?php echo $input_name; ?>" id="<?php echo $input_id; ?>" value="<?php echo $input_value; ?>" >
          </td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
    <?php
  }

  public function save_hrhs_post_type( $post_ID ) {
    // If not this post type, return
    if ( $this->slug !== get_post_type( $post_ID ) ) {
      return;
    }

    // WordPress uses "magic quotes" on form data.
    // The WP docs gives this as the solution to reverse it
    $my_POST = stripslashes_deep( $_POST );

    // Check if any of the post fields are from this post type's form data
    $input_key = $this->slug . '_field';
    $expected_fields = array_map(
      function( $field ) {
        return $field[ 'slug' ];
      },
      $this->fields
    );
    if ( array_key_exists( $input_key, $my_POST ) ) {
      $input_fields = $my_POST[ $input_key ];

      // Iterate across the fields...
      foreach ( $input_fields as $field_name => $field_value ) {
        // Check that this field is expected
        if ( in_array( $field_name, $expected_fields ) ) {
          // Update
          update_post_meta( $post_ID, $field_name, $field_value );
        }
      }
    }
  }

  public function display_hrhs_post_type_content( $content ) {
    global $post;

    if ( is_singular() ) {
      if ( $this->slug === $post->post_type ) {
        // return '<h3>This is a ' . $this->slug . ' post type</h3>' . $content;
      } else {
        // return '<h3>This is not a ' . $this->slug . ' post type</h3>' . $content;
      }
    }
    return $content;
  }

  // public function display_hrhs_post_type_title( $title, $post_ID ) {
  //   global $post;

  //   if ( get_post_type( $post_ID ) === $this->slug ) {
  //     hrhs_debug( 'Title for post type ' . $this->slug );
  //     hrhs_debug( $title );
  //     // Return a blank array, essentially deleting the headers for this post
  //     return '';
  //   }
  //   return $title;
  // }

  /* ******************
   * Accessor functions
   * ******************/

  // Register the search page rewrite rules (used by the plugin activation function)
  public function register_post_type_search_page() {
    $this->search_page->hrhs_search_page_rewrite_rules();
  }

}
