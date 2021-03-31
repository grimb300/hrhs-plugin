<?php

namespace HRHSElementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use function HRHSPlugin\hrhs_debug;

final class HRHS_Login_Widget extends Widget_Base {

  // Class constructor
  public function __construct( $data = array(), $args = null ) {
    parent::__construct( $data, $args );

    // The tutorial registers a stylesheet here
    // $hrhs_search_styles_css_ver = date( 'ymd-Gis', filemtime( HRHS_PLUGIN_PATH . 'css/hrhs-search-styles.css' ) );
    // wp_register_style( 'hrhs_search_styles_css', HRHS_PLUGIN_URL . 'css/hrhs-search-styles.css', array(), $hrhs_search_styles_css_ver );
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
    // return array( 'hrhs_search_styles_css' );
    return array();
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
        'label' => 'Title',
        'type' => Controls_Manager::TEXT,
        'default' => 'Member Access'
      )
    );

    $this->add_control(
      'description',
      array(
        'label' => 'Description',
        'type' => Controls_Manager::TEXTAREA,
        'default' => 'Rocktown History members provide sustaining funds for the collection, preservation, and research of genealogy and local history resources. Member benefits include expanded online searches of Names and Historic Locations.'
      )
    );

    $this->add_control(
      'password_label',
      array(
        'label' => 'Password Label',
        'type' => Controls_Manager::TEXTAREA,
        'default' => 'Password is case sensitive. Please contact the Administrator with access questions.'
      )
    );

    $this->add_control(
      'button_text',
      array(
        'label' => 'Login Button Text',
        'type' => Controls_Manager::TEXT,
        'default' => 'Log In'
      )
    );

    $this->end_controls_section();
  }

  // Render the widget output on the frontend
  // Written in PHP and used to generate the final HTML
  // NOTE: This is displayed on the frontend and the editor when not editing the content
  protected function render() {
    $settings = $this->get_settings_for_display();

    $this->add_inline_editing_attributes( 'title', 'none' );
    $this->add_inline_editing_attributes( 'description', 'basic' );
    ?>
    <div class="hrhs_member_login_wrap">
      <div class="hrhs_memeber_greeting">
        <?php
        if ( is_user_logged_in() ) {
          // Create a personallized welcome message
          $current_user = wp_get_current_user();
          $display_name = $current_user->user_login;
          if ( ! empty( $current_user->first_name ) ) {
            $display_name = $current_user->first_name;
          } elseif ( ! empty( $current_user->last_name ) ) {
            $display_name = $current_user->last_name;
          }
          ?>
          <h4>Welcome, <?php echo $display_name; ?>!</h4>
          <?php
        } else {
          // Display the member login message
          ?>
          <h4 <?php echo $this->get_render_attribute_string( 'title' ); ?>><?php echo $settings[ 'title' ]; ?></h4>
          <p <?php echo $this->get_render_attribute_string( 'description' ); ?>><?php echo $settings[ 'description' ]; ?></p>
          <?php
        }
        ?>
      </div>
      <?php
      if ( is_user_logged_in() ) {
        // Display the logout link
        wp_loginout( $_SERVER[ 'REQUEST_URI' ], true );
      } else {
        // Display the login form
        wp_login_form( array(
          // 'echo' => false,
          'echo' => true,
          'redirect' => $_SERVER[ 'REQUEST_URI' ],
          'form_id' => 'hrhs_member_login_form',
          'label_username' => '',
          'label_password' => $settings[ 'password_label' ],
          // 'label_remember' => '',
          'label_log_in' => $settings[ 'button_text' ],
          'id_username' => 'hrhs_member_username',
          'id_password' => 'hrhs_member_password',
          // 'id_remember' => 'hrhs_member_remember',
          'id_submit' => 'hrhs_member_submit',
          'remember' => false,
          'value_username' => 'HRHS-MEMBER',
          // 'value_remember' => true,
        ) );
        // If the previous login attempt failed, add an appropriate message
        if ( ! empty( $_REQUEST[ 'login' ] ) ) {
          // hrhs_debug( 'Login failure: '. $_REQUEST[ 'login' ] );
          if ( 'failed' === $_REQUEST[ 'login' ] ) {
            ?>
            <p class="hrhs_login_error">Incorrect password, try again</p>
            <?php
          }
          if ( 'empty_user' === $_REQUEST[ 'login' ] ) {
            ?>
            <p class="hrhs_login_error">Empty user name, try again</p>
            <?php
          }
          if ( 'empty_pwd' === $_REQUEST[ 'login' ] ) {
            ?>
            <p class="hrhs_login_error">Empty password, try again</p>
            <?php
          }
        } else {
          // hrhs_debug( 'No login info' );
        }
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
    <div class="hrhs_member_login_wrap">
      <div class="hrhs_memeber_greeting">
        <h4 {{{ view.getRenderAttributeString( 'title' ) }}}>{{{ settings.title }}}</h4>
        <p {{{ view.getRenderAttributeString( 'description' ) }}}>{{{ settings.description }}}</p>
      </div>
      <?php
      // Create the login form
      wp_login_form( array(
        // 'echo' => false,
        'echo' => true,
        'redirect' => $_SERVER[ 'REQUEST_URI' ],
        'form_id' => 'hrhs_member_login_form',
        'label_username' => '',
        'label_password' => $settings[ 'password_label' ],
        // 'label_remember' => '',
        'label_log_in' => $settings[ 'button_text' ],
        'id_username' => 'hrhs_member_username',
        'id_password' => 'hrhs_member_password',
        // 'id_remember' => 'hrhs_member_remember',
        'id_submit' => 'hrhs_member_submit',
        'remember' => false,
        'value_username' => 'HRHS-MEMBER',
        // 'value_remember' => true,
      ) );
      ?>
    </div>
    <?php
  }

}