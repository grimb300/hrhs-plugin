<?php

namespace HRHSElementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use HRHSPlugin\HRHS_Options;
use function HRHSPlugin\hrhs_debug;

final class HRHS_Search_Widget extends Widget_Base {

  // Class constructor
  public function __construct( $data = array(), $args = null ) {
    parent::__construct( $data, $args );

    // The tutorial registers a stylesheet here
    // $hrhs_search_styles_css_ver = date( 'ymd-Gis', filemtime( HRHS_PLUGIN_PATH . 'css/hrhs-search-styles.css' ) );
    // wp_register_style( 'hrhs_search_styles_css', HRHS_PLUGIN_URL . 'css/hrhs-search-styles.css', array(), $hrhs_search_styles_css_ver );
  }

  // Retreive the widget name
  public function get_name() {
    return 'hrhs_search_widget';
  }

  // Retreive the widget title
  public function get_title() {
    return 'HRHS Search';
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
    // return array( 'hrhs_search_styles_css' );
    return array();
  }

  // Register the widget controls
  // Adds different input fields to allow the user to change and customize the widget settings
  protected function _register_controls() {
    hrhs_debug( 'Inside HRHS_Search_Widget::_register_controls()');

    // Get the various CPTs that could be searched out of options
    require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-options.php';
    $options_obj = new HRHS_Options();
    $my_options = $options_obj->get();
    hrhs_debug( $my_options );

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

    // $this->add_control(
    //   'title',
    //   array(
    //     'label' => 'Title',
    //     'type' => Controls_Manager::TEXT,
    //     'default' => 'Title'
    //   )
    // );

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
      'description',
      array(
        'label' => 'Description',
        'type' => Controls_Manager::TEXTAREA,
        'default' => 'Search is not case sensitive. Results indicate number and type of records available through the HRHS Library database.'
      )
    );

    // $this->add_control(
    //   'content',
    //   array(
    //     'label' => 'Content',
    //     'type' => Controls_Manager::WYSIWYG,
    //     'default' => 'Content'
    //   )
    // );

    $this->end_controls_section();
  }

  // Render the widget output on the frontend
  // Written in PHP and used to generate the final HTML
  // NOTE: This is displayed on the frontend and the editor when not editing the content
  protected function render() {
    $settings = $this->get_settings_for_display();

    // $this->add_inline_editing_attributes( 'title', 'none' );
    $this->add_inline_editing_attributes( 'description', 'basic' );
    // $this->add_inline_editing_attributes( 'content', 'advanced' );
    ?>
    <div class="hrhs_search_wrap">
      <p <?php echo $this->get_render_attribute_string( 'description'); ?>><?php echo $settings[ 'descripton' ]; ?></p>
      <form id="hrhs-search" action="" method="post">
        <input type="text" name="hrhs-search[needle]" id="hrhs-search-needle" value="???">
        <input type="submit" class="search-submit" value="Search?">
      </form>
    </div>
		<?php
  }

  // Render the widget output in the editor
  // Written as a Backbone JavaScript template and used to generate the live preview
  // NOTE: This is displayed when editing the content in the editor
  protected function _content_template() {
    ?>
    <#
    view.addInlineEditingAttributes( 'description', 'none' );
    #>
    <div class="hrhs_search_wrap">
      <p {{{ view.getRenderAttributeString( 'description') }}}>{{{ settings.descripton }}}</p>
      <form id="hrhs-search" action="" method="post">
        <input type="text" name="hrhs-search[needle]" id="hrhs-search-needle" value="???">
        <input type="submit" class="search-submit" value="Search?">
      </form>
    </div>
    <?php
  }

}