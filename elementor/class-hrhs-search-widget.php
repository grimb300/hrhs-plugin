<?php

namespace HRHSElementor\Widgets;
// FIXME: What happens if the namespace is changed to HRHSPlugin to match the rest of the plugin?

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use HRHSPlugin\HRHS_Options;
use HRHSPlugin\HRHS_Simple_Search;
use function HRHSPlugin\hrhs_debug;

final class HRHS_Search_Widget extends Widget_Base {

  // Class constructor
  public function __construct( $data = array(), $args = null ) {
    parent::__construct( $data, $args );

    // Register the stylesheet
    $hrhs_search_styles_css_ver = date( 'ymd-Gis', filemtime( HRHS_PLUGIN_PATH . 'css/hrhs-search-styles.css' ) );
    wp_register_style( 'hrhs_search_styles_css', HRHS_PLUGIN_URL . 'css/hrhs-search-styles.css', array(), $hrhs_search_styles_css_ver );
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
    return array( 'hrhs_search_styles_css' );
  }

  // Register the widget controls
  // Adds different input fields to allow the user to change and customize the widget settings
  protected function _register_controls() {
    // hrhs_debug( 'Inside HRHS_Search_Widget::_register_controls()');

    // Get the various CPTs that could be searched out of options
    require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-options.php';
    $options_obj = new HRHS_Options();
    $my_options = $options_obj->get();
    // hrhs_debug( $my_options );

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
    $needle =
      empty( $_REQUEST[ 'hrhs-search' ][ 'needle' ] )
      ? null
      : $_REQUEST[ 'hrhs-search' ][ 'needle' ];

    // The haystack will always be the widget's search_type despite the request parameter "haystacks",
    // keeping it around for possible backward compatability with the old "search" class
    $haystack = $settings[ 'search_type' ];

    // Instantiate the "simple_search" object for this needle/haystack
    $search_obj = new HRHS_Simple_Search( array(
      'needle' => $needle,
      'haystack' => $haystack
    ) );

    // Get the searchable status for this haystack
    $haystack_not_searchable = empty( $search_obj->get_search_fields() );

    ?>
    <div class="hrhs_search_wrap">
      <h4 <?php echo $this->get_render_attribute_string( 'title'); ?>><?php echo $settings[ 'title' ]; ?></h4>
      <?php
      if ( $haystack_not_searchable ) {
        // If the haystack isn't searchable, display the "login required" message
        ?>
        <p <?php echo $this->get_render_attribute_string( 'login_msg'); ?>><?php echo $settings[ 'login_msg' ]; ?></p>
        <?php
      } else {
        // Else, display the search form
        ?>
        <p <?php echo $this->get_render_attribute_string( 'description'); ?>><?php echo $settings[ 'description' ]; ?></p>
        <form id="hrhs-search" action="" method="post">
          <input type="hidden" name="hrhs-search[haystacks][<?php echo $settings[ 'search_type' ]; ?>]" value="on">
          <input type="text" name="hrhs-search[needle]" id="hrhs-search-needle" value="<?php echo $needle; ?>">
          <input type="submit" class="search-submit" value="<?php echo $settings[ 'button_text' ]; ?>">
        </form>
        <?php
      }
      ?>
    </div>
    <?php
    // If a needle was provided and the haystack is searchable, display the search results
    if ( null !== $needle && ! $haystack_not_searchable ) {
      // FIXME: There's an inconsistency between what is returned between the CPT and custom table flows.
      //        Move the complexity into hrhs_simple_search so the consumer doesn't need to know what is going on.
      $search_results = $search_obj->get_search_results( array( 'needle' => $needle ) );
      // hrhs_debug( 'get_search_results returned:' );
      // hrhs_debug( $search_results );
      $num_results = count( $search_results );
      ?>
      <div class="hrhs_results_wrap">
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
                <?php foreach ( $search_results as $result ) { ?>
                <tr>
                  <?php foreach( $display_fields as $field ) { ?>
                  <td><?php echo $result[ $field[ 'slug' ] ]; ?></td>
                  <?php } // foreach $display_fields ?>
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
    <#
    view.addInlineEditingAttributes( 'title', 'none' );
    view.addInlineEditingAttributes( 'description', 'basic' );
    #>
    <div class="hrhs_search_wrap">
      <h4 {{{ view.getRenderAttributeString( 'title') }}}>{{{ settings.title }}}</h4>
      <p {{{ view.getRenderAttributeString( 'description') }}}>{{{ settings.description }}}</p>
      <form id="hrhs-search" action="" method="post">
        <input type="hidden" name="hrhs-search[haystacks][{{{ settings.search_type }}}]" value="on">
        <input type="text" name="hrhs-search[needle]" id="hrhs-search-needle" value="">
        <input type="submit" class="search-submit" value="{{{ settings.button_text }}}">
      </form>
    </div>
    <div class="hrhs_results_wrap">
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