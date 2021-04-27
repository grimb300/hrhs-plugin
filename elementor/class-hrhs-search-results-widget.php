<?php

namespace HRHSElementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use HRHSPlugin\HRHS_Options;
use HRHSPlugin\HRHS_Simple_Search;
use function HRHSPlugin\hrhs_debug;

final class HRHS_Search_Results_Widget extends Widget_Base {

  // Class constructor
  public function __construct( $data = array(), $args = null ) {
    parent::__construct( $data, $args );

    // Register the stylesheet
    $hrhs_search_styles_css_ver = date( 'ymd-Gis', filemtime( HRHS_PLUGIN_PATH . 'css/hrhs-search-styles.css' ) );
    wp_register_style( 'hrhs_search_styles_css', HRHS_PLUGIN_URL . 'css/hrhs-search-styles.css', array(), $hrhs_search_styles_css_ver );
  }

  // Retreive the widget name
  public function get_name() {
    return 'hrhs_search_results_widget';
  }

  // Retrieve the widget title
  public function get_title() {
    return 'HRHS Search Results';
  }

  // Retrieve the widget icon
  public function get_icon() {
    // FIXME: This is from the tutorial
    return 'fa fa-pencil';
  }

  // Retreive the list of categories the widget belongs to
  // Used to determine where to display the widget in the editor
  // NOTE: Elementor currently only supports one category,
  //       passing multiple categories will use the first one
  // FIXME: This is from the tutorial, it would be cooler if I had my own category
  public function get_categories() {
    return array( 'general' );
  }

  // Enqueue styles
  public function get_style_depends() {
    return array( 'hrhs_search_styles_css' );
  }

  // Register the widget controls
  // Adds different input fields to allow the user to change and customize the widget settings
  protected function _register_controls() {
    // hrhs_debug( 'Inside HRHS_Search_Results_Widget::_register_controls()');
    
    // FIXME: Are there any settings I need for the search results?
    //        More importantly, do I need a controls section if there are no controls?
    $this->start_controls_section(
      'section_content',
      array(
        'label' => 'Content'
      )
    );

    $this->end_controls_section();
  }

  // Render the widget output on the frontend
  // Written in PHP and used to generate the final HTML
  // NOTE: This is displayed on the frontend and the editor when not editing the content
  protected function render() {
    // Get the settings values
    $settings = $this->get_settings_for_display();

    // Get the search term(s) (if present)
    $needle = empty( $_GET[ 'search' ] ) ? null : $_GET[ 'search' ];

    // Get the haystack (if present)
    $haystack = empty( $_GET[ 'search_type' ] ) ? null : $_GET[ 'search_type' ];

    // If both the needle and haystack are defined, instantiate the "simple_search" object for this haystack
    $search_obj = null;
    if ( null !== $needle && null !== $haystack ) {
      $search_obj = new HRHS_Simple_Search( array( 'haystack' => $haystack ) );
    }

    ?>
    <?php
    // If a needle was provided, display the search results
    if ( null !== $search_obj ) {
      // Get the selected search fields (if present)
      $selected_fields = empty( $_GET[ 'search_fields' ] ) ? array() : $_GET[ 'search_fields' ];
      // hrhs_debug( 'SearchResultsWidget: Selected fields' );
      // hrhs_debug( $selected_fields );
      $search_results = $search_obj->get_search_results(
        array(
          'needle' => $needle,
          'fields' => $selected_fields,
        )
      );
      $num_results = count( $search_results );
      ?>
      <div class="hrhs_search_results_wrap">
        <h4>Your search for "<?php echo $needle; ?>" generated <?php echo $num_results; ?> results</h4>
        <?php
        // If any results were returned, display them here
        if ( $num_results > 0 ) {
          $display_fields = $search_obj->get_display_fields();
          if ( ! empty( $display_fields ) ) {
            ?>
            <table>
              <tbody>
                <tr>
                  <?php foreach ( $display_fields as $field ) { ?>
                    <th scope="col"><?php echo $field[ 'label' ]; ?></th>
                  <?php } ?>
                </tr>
                <?php foreach ( $search_results as $post_id ) { ?>
                  <tr>
                    <?php
                    $result_meta_data = get_post_meta( $post_id );
                    foreach( $display_fields as $field ) {
                      $field_meta_name = $field[ 'slug' ];
                      $field_meta_data =
                        array_key_exists( $field_meta_name, $result_meta_data )
                        ? $result_meta_data[ $field_meta_name ][0]
                        : '';
                      ?>
                      <td><?php echo $field_meta_data; ?></td>
                      <?php
                    } // foreach $display_fields
                    ?>
                  </tr>
                <?php } // foreach $search_results ?>
              </tbody>
            </table>
            <?php
          }
        }
        ?>
      </div>
      <?php
    }
  }

  // Render the widget output in the editor
  // Written as a Backbone JavaScript template and used to generate the live preview
  // NOTE: This is displayed when editing the content in the editor
  protected function _content_template() {
    ?>
    <div class="hrhs_search_results_wrap">
      <h4>Your search for "test" generated 3 results</h4>
      <table>
        <tbody>
          <tr>
            <th scope="col">Heading 1</th>
            <th scope="col">Heading 2</th>
            <th scope="col">Heading 3</th>
            <th scope="col">Heading 4</th>
          </tr>
          <tr>
            <td>Record 1 - Data 1</td>
            <td>Record 1 - Data 2</td>
            <td>Record 1 - Data 3</td>
            <td>Record 1 - Data 4</td>
          </tr>
          <tr>
            <td>Record 2 - Data 1</td>
            <td>Record 2 - Data 2</td>
            <td>Record 2 - Data 3</td>
            <td>Record 2 - Data 4</td>
          </tr>
          <tr>
            <td>Record 3 - Data 1</td>
            <td>Record 3 - Data 2</td>
            <td>Record 3 - Data 3</td>
            <td>Record 3 - Data 4</td>
          </tr>
        </tbody>
      </table>
    </div>
   <?php
  }

}