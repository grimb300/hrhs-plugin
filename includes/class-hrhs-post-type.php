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

    $this->slug = 'generic_cpt';
    $this->fields = array( 'name', 'address', 'phone_number' );

    $this->initialize_hrhs_post_type();
  }
  
  private function initialize_hrhs_post_type() {
    add_action( 'init', array( $this, 'register_hrhs_post_type' ) );
    add_action( 'save_post', array( $this, 'save_hrhs_post_type' ));
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
    $post_type = get_post_type( $post );
    $meta_box_id = $post_type . '_meta_box';
    add_meta_box(
      $meta_box_id,                 // id attribute of the meta box
      'Generic Post Type Fields',   // meta box title
      array( $this, 'display_hrhs_post_type_meta_box' ), // callback, display function
      $post_type,                   // screen(s) to display meta box 
      'advanced',                   // context, built in options: advanced (default), normal, side
      'high',                       // priority within the context: high, core, default (default), low
      array(                        // $args passed to the callback
        'input_id_prefix' => $post_type . '_field',
      ),
    );
  }

  public function display_hrhs_post_type_meta_box( $post, $args ) {
    $input_prefix = $this->slug . '_field';
    ?>
    <table class="form-table">
      <tbody>
        <?php
        foreach( $this->fields as $field ) {
          $input_id = $input_prefix . '_' . $field;
          $input_name = $input_prefix . '[' . $field . ']';
          ?>
        <tr>
          <th scope="row">
            <label for="<?php echo $input_id; ?>"><?php echo $field; ?></label>
          </th>
          <td>
            <input
              type="text"
              name="<?php echo $input_name; ?>"
              id="<?php echo $input_id; ?>"
              value="<?php echo get_post_meta( $post->ID, $field, true ); ?>"
            >
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
    if ( array_key_exists( $input_key, $my_POST ) ) {
      $input_fields = $my_POST[ $input_key ];

      // Iterate across the fields...
      foreach ( $input_fields as $field_name => $field_value ) {
        // Check that this field is defined
        if ( in_array( $field_name, $this->fields ) ) {
          // Update
          update_post_meta( $post_ID, $field_name, $field_value );
        }
      }
    }
  }
}