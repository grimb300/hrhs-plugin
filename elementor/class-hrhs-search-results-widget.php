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
    // $this->start_controls_section(
    //   'section_content',
    //   array(
    //     'label' => 'Content'
    //   )
    // );

    // $this->end_controls_section();
  }

  private function gen_pagination_links( $params = array() ) {
    // Get the current page number out of the params
    $current_page = empty( $params[ 'current_page' ] ) ? 1 : intval( $params[ 'current_page' ] );

    // Get the last page number out of the params
    $last_page = empty( $params[ 'last_page' ] ) ? 1 : intval( $params[ 'last_page' ] );
    // If the last page is page 1, return, there's nothing to display
    if ( 1 === $last_page ) {
      return;
    }

    // Get the query_vars out of the params
    $query_vars = empty( $params[ 'query_vars' ] ) ? array() : $params[ 'query_vars' ];
    // hrhs_debug( 'gen_pagination_links: query_vars' );
    // hrhs_debug( $query_vars );

    // Echo the links
    // TODO: Been going back and forth in my head as to what is the best way to display the links.
    //       Option 1: Use a format similar to the admin list page
    //                 << < 3 4 5 6 7 > >>
    //                  | |     |     | |
    //                  | |     |     | +-> Last Page
    //                  | |     |     +---> Next Page
    //                  | |     +---------> Direct Pages (shifts based on current page)
    //                  | +---------------> Previous Page
    //                  +-----------------> First Page
    //       Option 2: Show the first and last page with a shifting series around the current page
    //                 1 ... 3 4 5 6 7 ... 10
    //                 |         |         |
    //                 |         |         +-> Last Page
    //                 |         +-----------> Direct Pages (shifts based on current page)
    //                 +---------------------> First Page
    //       For now, going with option 2 since it displays more info by default, commenting out option 1
    ?>
    <div class="pagination">
      <strong>Go to page: </strong>
      <?php
      // Option 1 (commented out)
      // The rendered html would look something like:
      //   <span class="first-page"><a href="">&laquo;</a></span>
      //   <span class="prev-page"><a href="">&lsaquo;</a></span>
      //   <span class="direct-page page-num-3"><a href="">3</a></span>
      //   <span class="direct-page page-num-4"><a href="">4</a></span>
      //   <span class="direct-page page-num-5 current"><strong>5</strong></span>
      //   <span class="direct-page page-num-6"><a href="">6</a></span>
      //   <span class="direct-page page-num-7"><a href="">7</a></span>
      //   <span class="next-page"><a href="">&rsaquo;</a></span>
      //   <span class="last-page"><a href="">&raquo;</a></span>

      // Option 2
      // Create an array of pages to render links for
      $pages_to_render = array();
      if ( $last_page <= 5 ) {
        // Doesn't make sense to skip page number, render them all
        $pages_to_render = range( 1, $last_page );
      } elseif ( $current_page <= 3 ) {
        // The current page is within 2 steps of the first page
        // NOTE: I'm using page number -1 to represent the ellipsis (.../&hellip;)
        $pages_to_render = array_merge( range( 1, $current_page + 2 ), array( -1, $last_page ) );
      } elseif ( $current_page >= $last_page - 2 ) {
        // The current page is within 2 steps of the last page
        // NOTE: I'm using page number -1 to represent the ellipsis (.../&hellip;)
        $pages_to_render = array_merge( array( 1, -1 ), range( $current_page - 2, $last_page ) );
      } else {
        // The current page is somewhere in the middle
        // NOTE: I'm using page number -1 to represent the ellipsis (.../&hellip;)
        $pages_to_render = array_merge( array( 1, -1 ), range( $current_page - 2, $current_page + 2 ), array( -1, $last_page ) );
      }

      // Now iterate across the links to render
      foreach ( $pages_to_render as $page ) {
        // Add this page to the query vars
        $query_vars[ 'results_page' ] = $page;

        // Render the appropriate span
        if ( -1 === $page ) {
          // If the page number is -1 render an ellipsis
          ?>
          <span class="dot-dot-dot">&hellip;</span>
          <?php
        } elseif ( $current_page === $page ) {
          // If this is the current page, no link and make it bold
          ?>
          <span class="direct-page page-num-<?php echo $page; ?> current"><strong><?php echo $page; ?></strong></span>
          <?php
        } else {
          // Render the link
          ?>
          <span class="direct-page page-num-<?php echo $page; ?>"><a href="<?php echo $this->gen_pagination_url( $query_vars ); ?>"><?php echo $page; ?></a></span>
          <?php
        }
      }
    ?>
    </div>
    <?php
  }

  private function gen_pagination_url( $query_vars = array() ) {
    return get_page_link() . '?' . http_build_query( $query_vars );
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

      // Get the number of results per page (if present)
      // FIXME: Hard coded default needs to come from a centralized location
      $num_results = empty( $_GET[ 'num_results' ] ) ? '50' : $_GET[ 'num_results' ];

      // Get the page number to display
      $page_num = empty( $_GET[ 'results_page' ] ) ? 1 : $_GET[ 'results_page' ];

      // Get the default sort order
      $sortable_fields = $search_obj->get_default_sort();
      hrhs_debug( 'Default search order:' );
      hrhs_debug( $sortable_fields );

      // Sort on the request query field, if present. Otherwise, use default sort order.
      $sort_order =
        empty( $_GET[ 'sort_field' ] )
        ? array_map( function ( $field ) { return $field[ 'slug' ]; }, $sortable_fields )
        : array( $_GET[ 'sort_field' ] );

      // Get the search results to be displayed
      // FIXME: This will need some major work when merged back into main
      $search_results = $search_obj->get_search_results(
        array(
          'needle' => $needle,
          'fields' => $selected_fields,
          'num_results' => $num_results,
          'page_num' => $page_num,
          'sort' => $sort_order,
          // 'sort' => $sortable_fields,
        )
      );

      // Get the total number of results (not just the ones being displayed)
      // FIXME: Needs a new function in HRHS_Simple_Search
      // $total_results = count( $search_results );
      $total_results = $search_results[ 'found_results' ];

      ?>
      <div class="hrhs_search_results_wrap">
        <h4>Your search for "<?php echo $needle; ?>" generated <?php echo $total_results; ?> results</h4>
        <?php
        // If any results were returned, display them here
        if ( $total_results > 0 ) {
          // Display the pagination controls only if:
          //    1. The number of results per page is not 'all'
          //    2. There is more than 1 page of results
          $display_pagination = 'all' !== $num_results && $total_results <= intval( $num_results );
          if ( $display_pagination ) {
            // Get the interesting query vars from the current page
            $pagination_query_vars = array();
            if ( ! empty( $_GET[ 'search_type' ] ) )   { $pagination_query_vars[ 'search_type' ] = $_GET[ 'search_type' ]; }
            if ( ! empty( $_GET[ 'search' ] ) )        { $pagination_query_vars[ 'search' ] = $_GET[ 'search' ]; }
            if ( ! empty( $_GET[ 'search_fields' ] ) ) { $pagination_query_vars[ 'search_fields' ] = $_GET[ 'search_fields' ]; }
            if ( ! empty( $_GET[ 'num_results' ] ) )   { $pagination_query_vars[ 'num_results' ] = $_GET[ 'num_results' ]; }
            // hrhs_debug( 'pagination_query_vars' );
            // hrhs_debug( $pagination_query_vars );
            // Display the pagination links
            $this->gen_pagination_links(
              array( 
                'current_page' => $page_num,
                'last_page' => ceil( $total_results / intval( $num_results ) ),
                'query_vars' => $pagination_query_vars
              )
            );
          }
          // Display the table
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
                <?php foreach ( $search_results[ 'results' ] as $post_id ) { ?>
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
          // Display the pagination links, again, if necessary
          if ( $display_pagination ) {
            $this->gen_pagination_links(
              array( 
                'current_page' => $page_num,
                'last_page' => ceil( $total_results / intval( $num_results ) ),
                'query_vars' => $pagination_query_vars
              )
            );
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