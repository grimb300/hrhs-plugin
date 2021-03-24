<?php

namespace HRHSElementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

final class HRHS_Login_Widget extends Widget_Base {

  // Class constructor
  public function __construct( $data = array(), $args = null ) {
    parent::__construct( $data, $args );

    // The tutorial registers a stylesheet here
    $hrhs_search_styles_css_ver = date( 'ymd-Gis', filemtime( HRHS_PLUGIN_PATH . 'css/hrhs-search-styles.css' ) );
    wp_register_style( 'hrhs_search_styles_css', HRHS_PLUGIN_URL . 'css/hrhs-search-styles.css', array(), $hrhs_search_styles_css_ver );
  }

  // Retreive the widget name
  public function get_name() {
    return 'hrhs_login_widget';
  }

  // Retreive the widget title
  public function get_title() {
    return 'HRHS Login';
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
    return array( 'hrhs_search_styles_css' );
  }

  // Register the widget controls
  // Adds different input fields to allow the user to change and customize the widget settings
  protected function _register_controls() {
    $this->start_controls_section(
      'section_content',
      array(
        'label' => 'Content'
      )
    );

    $this->add_control(
      'title',
      array(
        'label' => 'Description',
        'type' => Controls_Manager::TEXTAREA,
        'default' => 'Description'
      )
    );

    $this->add_control(
      'content',
      array(
        'label' => 'Content',
        'type' => Controls_Manager::WYSIWYG,
        'default' => 'Content'
      )
    );

    $this->end_controls_section();
  }

  // Render the widget output on the frontend
  // Written in PHP and used to generate the final HTML
  protected function render() {
    $settings = $this->get_settings_for_display();

    $this->add_inline_editing_attributes( 'title', 'none' );
    $this->add_inline_editing_attributes( 'description', 'basic' );
    $this->add_inline_editing_attributes( 'content', 'advanced' );
    ?>
    <h2 <?php echo $this->get_render_attribute_string( 'title' ); ?>><?php echo wp_kses( $settings[ 'title' ], array() ); ?></h2>
    <div <?php echo $this->get_render_attribute_string( 'description'); ?>><?php echo wp_kses( $settings[ 'description' ], array() ); ?></div>
    <div <?php echo $this->get_render_attribute_string( 'content' ); ?>><?php echo wp_kses( $settings[ 'content' ], array() ); ?></div>
		<?php
  }

  // Render the widget output in the editor
  // Written as a Backbone JavaScript template and used to generate the live preview
  protected function _content_template() {
    ?>
    <#
    view.addInlineEditingAttributes( 'title', 'none' );
    view.addInlineEditingAttributes( 'description', 'none' );
    view.addInlineEditingAttributes( 'content', 'none' );
    #>
    <h2 {{{ view.getRenderAttributeString( 'title' ) }}}>{{{ settings.title }}}</h2>
    <div {{{ view.getRenderAttributeString( 'description' ) }}}>{{{ settings.description }}}</div>
    <div {{{ view.getRenderAttributeString( 'content' ) }}}>{{{ settings.content }}}</div>
    <?php
  }

}