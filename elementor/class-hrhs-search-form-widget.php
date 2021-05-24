<?php

namespace HRHSElementor\Widgets;
// FIXME: What happens if the namespace is changed to HRHSPlugin to match the rest of the plugin?

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use HRHSPlugin\HRHS_Options;
use HRHSPlugin\HRHS_Simple_Search;
use function HRHSPlugin\hrhs_debug;

final class HRHS_Search_Form_Widget extends Widget_Base {

  // Class constructor
  public function __construct( $data = array(), $args = null ) {
    parent::__construct( $data, $args );

    // Register the stylesheet
    $hrhs_search_styles_css_ver = date( 'ymd-Gis', filemtime( HRHS_PLUGIN_PATH . 'css/hrhs-search-styles.css' ) );
    wp_register_style( 'hrhs_search_styles_css', HRHS_PLUGIN_URL . 'css/hrhs-search-styles.css', array(), $hrhs_search_styles_css_ver );
  }

  // Retrieve the widget name
  public function get_name() {
    return 'hrhs_search_form_widget';
  }

  // Retrieve the widget title
  public function get_title() {
    return 'HRHS Search Form';
  }

  // Retreive the widget icon
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
    return array( 'hrhs_search_styles_css', 'dashicons' );
  }

  // Register the widget controls
  // Adds different input fields to allow the user to change and customize the widget settings
  protected function _register_controls() {

    // Get the various CPTs that could be searched out of options
    require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-options.php';
    $options_obj = new HRHS_Options();
    $my_options = $options_obj->get();

    // Build the search_type select array
    $search_types_select = array_map( 
      function ( $type_def ) {
        return $type_def[ 'plural_name' ];
      },
      $my_options[ 'post_type_defs' ]
    );
    // For lack of a better idea pick the first post type to be the default
    $search_type_default = array_key_first( $my_options[ 'post_type_defs' ] );

    $this->start_controls_section(
      'section_content',
      array(
        'label' => 'Content'
      )
    );

    $this->add_control(
      'search_type',
      array(
        'label' => 'Post Type to Search',
        'type' => Controls_Manager::SELECT,
        'options' => $search_types_select,
        'default' => $search_type_default
        )
      );
      
      $this->add_control(
        'title',
        array(
          'label' => 'Title',
          'type' => Controls_Manager::TEXT,
          'default' => 'Database Search'
        )
      );
  
    $this->add_control(
      'description',
      array(
        'label' => 'Search Description',
        'type' => Controls_Manager::WYSIWYG,
        'default' => 'Search is not case sensitive. Results indicate number and type of records available through the HRHS Library database.'
      )
    );

    $this->add_control(
      'num_results',
      array(
        'label' => 'Default Search Results Per Page',
        'type' => Controls_Manager::SELECT,
        'options' => array( '10, 25', '50', 'all' ),
        'default' => '50'
      )
    );

    $this->add_control(
    'button_text',
    array(
      'label' => 'Search Button Text',
      'type' => Controls_Manager::TEXT,
      'default' => 'Search'
      )
    );
      
    $this->add_control(
      'login_msg',
      array(
        'label' => 'Login Required Message',
        'type' => Controls_Manager::TEXTAREA,
        'default' => 'This search is reserved for HRHS Members, please login to continue.'
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

    // Enable inline editing for certain settings
    $this->add_inline_editing_attributes( 'title', 'none' );
    $this->add_inline_editing_attributes( 'description', 'basic' );
    $this->add_inline_editing_attributes( 'login_msg', 'basic' );

    // Get the search term(s) (if present)
    // NOTE: WP uses magic quotes by default, have to use stripslashes on user provided $_GET values
    $needle = empty( $_GET[ 'search' ] ) ? null : stripslashes_deep( $_GET[ 'search' ] );
    
    // The haystack will always be the widget's search_type despite the request parameter "haystacks",
    // keeping it around for possible backward compatibility with the old "search" class
    $haystack = $settings[ 'search_type' ];
    
    // Instantiate the "simple_search" object for this needle/haystack
    $search_obj = new HRHS_Simple_Search( array( 'haystack' => $haystack ) );  
      
    // Get the searchable fields for this haystack
    $possible_searchable_fields = $search_obj->get_all_search_fields();
    $user_searchable_fields = $search_obj->get_search_fields();
    $not_searchable = empty( $user_searchable_fields );

    ?>
    <div class="hrhs_search_form_wrap">
      <h4 <?php echo $this->get_render_attribute_string( 'title'); ?>><?php echo $settings[ 'title' ]; ?></h4>
      <?php
      if ( $not_searchable ) {
        // If the haystack isn't searchable, display the "login required" message
        ?>
        <p <?php echo $this->get_render_attribute_string( 'login_msg' ); ?>><?php echo $settings[ 'login_msg' ]; ?></p>
        <?php
      } else {
        // Else, display the search form
        ?>
        <p <?php echo $this->get_render_attribute_string( 'description' ); ?>><?php echo $settings[ 'description' ]; ?></p>
        <form id="hrhs-search" action="" method="get">
          <input type="hidden" name="search_type" value="<?php echo $settings[ 'search_type' ]; ?>">
          <?php // FIXME: Tried using "s" as the query parameter, but it got picked up by core WP ?>
          <div class="hrhs-form-layout-wrap">
            <div class="hrhs-input-wrap">
              <?php 
              if ( count( $possible_searchable_fields ) > 1 ) {
                foreach ( $possible_searchable_fields as $field ) {
                  $slug = $field[ 'slug' ];
                  $label = $field[ 'label' ];
                  $id = 'hrhs-search-needle-' . $slug;
                  $name = sprintf( 'search[%s]', $slug );
                  $value = empty( $needle[ $slug ] ) ? null : $needle[ $slug ];
                  $disabled = $field[ 'search' ] === 'member' && ! is_user_logged_in() ? ' disabled' : '';
                  ?>
                  <p class="hrhs-input-row">
                    <input type="text" name="<?php echo $name; ?>" id="<?php echo $id; ?>" value="<?php echo $value; ?>"<?php echo $disabled; ?>>
                    <label for="<?php echo $id; ?>"><?php echo $label; ?><?php echo empty( $disabled ) ? '' : ' (members only)'; ?></label>
                  </p>
                  <?php
                }
              } else {
                ?>
                <p class="hrhs-input-row">
                  <input type="text" name="search" id="hrhs-search-needle" value="<?php echo $needle; ?>">
                </p>
                <?php
              }
              ?>
              <p class="hrhs-input-row">
                <select name="num_results" id="hrhs-search-results-per-page">
                  <?php
                  // FIXME: Need to make this a class property
                  $num_results_options = array( '10', '25', '50', 'all' );
                  foreach ( $num_results_options as $option ) {
                    $num_results = empty( $_GET[ 'num_results' ] ) ? $settings[ 'num_results' ] : $_GET[ 'num_results' ];
                    $selected = $option === $num_results ? ' selected' : '';
                    ?>
                    <option value="<?php echo $option; ?>"<?php echo $selected; ?>><?php echo $option; ?></option>
                    <?php
                  }
                  ?>
                </select>
                <label for="hrhs-search-results-per-page">Results Per Page</label>
              </p>
              <p class="hrhs-input-row">
                <input type="submit" class="search-submit button button-primary" value="<?php echo $settings[ 'button_text' ]; ?>">
              </p>
            </div>
          </div>
        </form>
        <?php
      }
      ?>
    </div>
    <?php
  }

  // Render the widget output in the editor
  // Written as a Backbone JavaScript template and used to generate the live preview
  // NOTE: This is displayed when editing the content in the editor
  protected function _content_template() {
    ?>
    <#
    view.addInlineEditingAttributes( 'title', 'none' );
    view.addInlineEditingAttributes( 'description', 'basic' );
    #>
    <div class="hrhs_search_form_wrap">
      <h4 {{{ view.getRenderAttributeString( 'title') }}}>{{{ settings.title }}}</h4>
      <p {{{ view.getRenderAttributeString( 'description') }}}>{{{ settings.description }}}</p>
      <form id="hrhs-search" action="" method="post">
        <input type="hidden" name="hrhs-search[haystacks][{{{ settings.search_type }}}]" value="on">
        <input type="text" name="hrhs-search[needle]" id="hrhs-search-needle" value="">
        <input type="submit" class="search-submit" value="{{{ settings.button_text }}}">
      </form>
    </div>
   <?php
  }

}