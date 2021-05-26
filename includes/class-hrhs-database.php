<?php

namespace HRHSPlugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HRHS_Database {

  /* **********
   * Properties
   * **********/

  private static $hrhs_db_prefix = 'hrhs_db_';
  private static $hrhs_option_prefix = 'hrhs_db_version_';

  /* *******
   * Methods
   * *******/

  private static function gen_table_name( $name ) {
    global $wpdb;
    return $wpdb->prefix . self::$hrhs_db_prefix . $name;
  }

  private static function gen_option_name( $name ) {
    return self::$hrhs_option_prefix . $name;
  }

  public static function install( $params = array() ) {

    // If $params is empty, do nothing
    if ( empty( $params ) ) {
      // hrhs_debug( 'HRHS_Database::install called with empty params, returning' );
      return;
    }

    // FIXME: I'm sure there's a more efficient way of doing defaults
    // Get the current and new database versions
    $table_name = array_key_exists( 'table_name', $params ) ? $params[ 'table_name' ] : 'test_table';
    $new_db_version = array_key_exists( 'version', $params ) ? $params[ 'version' ] : '0.1';
    // $option_name = 'hrhs_db_version_' . $table_name;
    $option_name = self::gen_option_name( $table_name );
    $current_db_version = get_option( $option_name, null );
    // hrhs_debug( 'HRHS_Database::install called for table ' . $params[ 'table_name' ] . ' version ' . $new_db_version );

    // If no current database version, create the table
    if ( null === $current_db_version ) {
      // hrhs_debug( 'No current database version, creating table' );
      self::create_table( array(
        'name' => $table_name,
        'version' => $new_db_version,
        'columns' =>
          empty( $params[ 'fields'] ) ?
          array(
            array( 'name' => 'col_1', 'data_type' => 'varchar(75)' ),
            array( 'name' => 'col_2', 'data_type' => 'varchar(75)' ),
            array( 'name' => 'col_3', 'data_type' => 'varchar(75)' )
          ) :
          $params[ 'fields' ],
      ) );
    } elseif ( $new_db_version === $current_db_version ) {
      // If the new database version matches the existing database, return
      // hrhs_debug( 'Database is up to date, do nothing' );
      return;
    } else {
      // Upgrade the database
      // FIXME: How do I best do this?
      //        Initial thought is to have the caller provide an upgrade callback function
      // hrhs_debug( sprintf( 'Upgrade the %s table from version %s to %s', $table_name, $current_db_version, $new_db_version ) );
      // self::upgrade_table( array(
      //   'name' => $table_name,
      //   'current_version' => $current_db_version,
      //   'new_version' => $new_db_version,
      //   'callback' => $params[ 'upgrade_callback' ],
      // ) );
    }
  }

  private static function create_table( $params ) {
    global $wpdb;
    // hrhs_debug( 'HRHS_Database::create_table called' );

    // I don't want to mess with malformed params at this point, if required fields are missing return
    if (  empty( $params[ 'name' ] )    ||
          empty( $params[ 'version' ] ) ||
          empty( $params[ 'columns' ] ) ) {
      // hrhs_debug( 'HRHS_Database::create_table - Missing params, returning');
      return;
    }

    // Construct the table name and the option name
    // $table_name = $wpdb->prefix . self::$hrhs_db_prefix . $params[ 'name' ];
    $table_name = self::gen_table_name( $params[ 'name' ] );
    // $option_name = 'hrhs_db_version_' . $params[ 'name' ];
    $option_name = self::gen_option_name( $params[ 'name' ] );
    
    // Get the character set used by the DB
    $charset_collate = $wpdb->get_charset_collate();
    
    // hrhs_debug( 'HRHS_Database::create_table - Creating database table ' . $table_name );
    // Build the SQL 'CREATE TABLE' command
    // FIXME: Need to add data type info to the columns
    $sql_columns = array();
    foreach ( $params[ 'columns' ] as $column ) {
      $sql_columns[] = sprintf( "%s %s NOT NULL,", $column[ 'name' ], $column[ 'data_type' ] );
    }
    $sql_columns_string = implode( "\n", $sql_columns );
    $sql = 
    "CREATE TABLE $table_name (
      id mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
      $sql_columns_string
      PRIMARY KEY  (id)
    ) $charset_collate;";

  // Run the SQL
  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  // hrhs_debug( 'SQL Command:' );
  // hrhs_debug( $sql );
  dbDelta( $sql );

  // Update the database version in options
  update_option( $option_name, $params[ 'version' ] );

}
  
  // FIXME: This function seems a little too redundant to be necessary
  //        Might be worth using for no other reason than to hide the table prefixes from the rest of the plugin
  public static function insert_data( $params ) {
    global $wpdb;
    // hrhs_debug( 'HRHS_Database::insert_data called' );
    
    // I don't want to mess with malformed params at this point, if required fields are missing return
    if (  empty( $params[ 'name' ] ) ||
    empty( $params[ 'data' ] ) ) {
      // hrhs_debug( 'HRHS_Database::insert_data - Missing params, returning');
      return;
    }
    
    // Construct the table name
    // $table_name = $wpdb->prefix . self::$hrhs_db_prefix . $params[ 'name' ];
    $table_name = self::gen_table_name( $params[ 'name' ] );

    // Insert the data
    $wpdb->insert( $table_name, $param[ 'data' ] );
  }

  public static function get_results( $params ) {
    global $wpdb;
    // hrhs_debug( 'HRHS_Database::get_results called' );
    
    // I don't want to mess with malformed params at this point, if required fields are missing return
    // FIXME: For now, the only required field is the name of the table to be searched. Revisit this decision later
    if ( empty( $params[ 'name' ] ) ) {
      // hrhs_debug( 'HRHS_Database::get_results - Missing name param, returning empty array');
      return array();;
    }

    // Build the parts of the SQL query

    // Generate the table name
    $table_name = self::gen_table_name( $params[ 'name' ] );

    // If there is a needle, generate the WHERE terms
    $where_placeholder = '';
    $where_values = array();
    if ( ! empty( $params[ 'needle' ] ) ) {
      $where_placeholder_parts = array();
      $where_values = array();
      foreach ( $params[ 'needle' ] as $column => $string ) {
        if ( ! empty( $string) ) {
          $where_placeholder_parts[] = "$column LIKE \"%%%s%%\"";
          $where_values[] = $string;
        }
      }
      $where_placeholder = implode(  ' AND ', $where_placeholder_parts );
    } else {
      // FIXME: For now, return no results when a needle isn't passed.
      return array();
    }

    // If there is a sort order, generate the ORDER BY terms
    $order_by = '';
    if ( ! empty( $params[ 'sort' ] ) ) {
      // hrhs_debug( 'Creating sort order:' );
      // hrhs_debug( $params[ 'sort' ] );
      $order_by = "ORDER BY " . implode( ', ', array_map(
        function ( $sort ) {
          return sprintf( 'CAST(%s AS CHAR) %s', $sort[ 'slug' ], strtoupper( $sort[ 'dir' ] ) );
        },
        $params[ 'sort' ]
      ) );
    }

    // If pagination is requested, generate the LIMIT term
    $limit = '';
    if ( ! empty( $params[ 'records_per_page' ] ) ) {
      $offset = 0;
      if ( ! empty( $params[ 'paged' ] ) ) {
        $offset = ( $params[ 'paged' ] - 1 ) * $params[ 'records_per_page' ];
      }
      $limit = sprintf( 'LIMIT %s, %s', $offset, $params[ 'records_per_page' ] );
    }

    // The SQL command I'm going for:
    //    SELECT SQL_CALC_FOUND_ROWS  * FROM <table_name>  
    //      WHERE <column_1> LIKE '%<string_1>%'
    //      AND <column_2> LIKE '%<string_2>%'
    //      ORDER BY CAST(<column_1> AS CHAR) <column_1_dir>, CAST(<column_2> AS CHAR) <column_2_dir>
    //      LIMIT <start_record>, <num_records_per_page>

    // Build the SQL command from the generated parts
    // FIXME: Making an assumption that there will always be a needle.
    //        If there isn't I'm not sure if I can use prepare()
    // NOTE: Not using SQL_CALC_FOUND_ROWS due to this note in MySQL 8.0
    //       https://dev.mysql.com/doc/refman/8.0/en/information-functions.html#function_found-rows
    $query_sql_command = $wpdb->prepare(
      "SELECT * FROM `$table_name` WHERE $where_placeholder $order_by $limit;",
      $where_values
    );
    // hrhs_debug( 'SQL Command: ' . $query_sql_command );

    // Return the results of the database query
    $results = $wpdb->get_results( $query_sql_command, ARRAY_A );
    // hrhs_debug( sprintf( 'The search results (%d):', count( $results ) ) );
    // hrhs_debug( $results );

    // Get the total number of matches
    $count_sql_command = $wpdb->prepare(
      "SELECT COUNT(*) FROM `$table_name` WHERE $where_placeholder;",
      $where_values
    );
    $total_matches = $wpdb->get_results( $count_sql_command, ARRAY_N );
    // hrhs_debug( 'COUNT(*) results');
    // hrhs_debug( $total_matches );

    return array(
      'results' => $results,
      'found_results' => $total_matches[0][0],
      'MySQL_query' => $query_sql_command,
    );

  }

  public static function get_count( $params ) {
    global $wpdb;
    // hrhs_debug( 'HRHS_Database::get_results called' );
    
    // I don't want to mess with malformed params at this point, if required fields are missing return
    // FIXME: For now, the only required field is the name of the table to be searched. Revisit this decision later
    if ( empty( $params[ 'name' ] ) ) {
      // hrhs_debug( 'HRHS_Database::get_results - Missing name param, returning empty array');
      return array();;
    }

    $table_name = self::gen_table_name( $params[ 'name' ] );

    // FIXME: For now, assume that the count is for the entire table (no search or filter)
    $sql_command = "SELECT COUNT(*) FROM $table_name;";
    
    // Return the results of the database count query
    $results = $wpdb->get_results( $sql_command, ARRAY_A );
    // hrhs_debug( 'HRHS_Database::get_count - The count results:' );
    // hrhs_debug( $results );
    return intval( $results[0][ 'COUNT(*)' ] );
  }

}