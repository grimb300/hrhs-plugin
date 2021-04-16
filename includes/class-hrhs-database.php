<?php

namespace HRHSPlugin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class HRHS_Database {

  /* **********
   * Properties
   * **********/

  private static $hrhs_db_version = 'null';
  private static $hrhs_db_prefix = 'hrhs_db_';

  /* *******
   * Methods
   * *******/

  public static function install( $params = array() ) {

    // If $params is empty, do nothing
    if ( empty( $params ) ) {
      hrhs_debug( 'HRHS_Database::install called with empty params, returning' );
      return;
    }

    // FIXME: I'm sure there's a more efficient way of doing defaults
    // Get the current and new database versions
    $table_name = array_key_exists( 'table_name', $params ) ? $params[ 'table_name' ] : 'test_table';
    $new_db_version = array_key_exists( 'version', $params ) ? $params[ 'version' ] : '0.1';
    $option_name = 'hrhs_db_version_' . $table_name;
    $current_db_version = get_option( $option_name, null );
    hrhs_debug( 'HRHS_Database::install called for table ' . $params[ 'table_name' ] . ' version ' . $new_db_version );

    // If no current database version, create the table
    if ( null === $current_db_version ) {
      hrhs_debug( 'No current database version, creating table' );
      self::create_table( array(
        'name' => $table_name,
        'version' => $new_db_version,
        'columns' => empty( $params[ 'fields'] ) ? array( 'col_1', 'col_2', 'col_3' ) : $params[ 'fields' ],
      ) );
    } elseif ( $new_db_version === $current_db_version ) {
      // If the new database version matches the existing database, return
      hrhs_debug( 'Database is up to date, do nothing' );
      return;
    } else {
      // Upgrade the database
      // FIXME: How do I best do this?
      //        Initial thought is to have the caller provide an upgrade callback function
      hrhs_debug( sprintf( 'Upgrade the %s table from version %s to %s', $table_name, $current_db_version, $new_db_version ) );
      // self::upgrade_table( array(
      //   'name' => $table_name,
      //   'current_version' => $current_db_version,
      //   'new_version' => $new_db_version,
      //   'callback' => $params[ 'upgrade_callback' ],
      // ) );
    }

    // Update the database (will initialize the database if it doesn't exist)
    // self::update_db();
    // if ( $old_hrhs_db_version !== self::$hrhs_db_version ) {
    //   update_option( 'hrhs_db_version', self::$hrhs_db_version );
    // } else {
    //   hrhs_debug( 'HRHS_Database is up to date, nothing to do here' );
    // }
  }

  private static function create_table( $params ) {
    global $wpdb;
    hrhs_debug( 'HRHS_Database::create_table called' );

    // I don't want to mess with malformed params at this point, if required fields are missing return
    if (  empty( $params[ 'name' ] )    ||
          empty( $params[ 'version' ] ) ||
          empty( $params[ 'columns' ] ) ) {
      hrhs_debug( 'HRHS_Database::create_table - Missing params, returning');
      return;
    }

    // Construct the table name and the option name
    $table_name = $wpdb->prefix . self::$hrhs_db_prefix . $params[ 'name' ];
    $option_name = 'hrhs_db_version_' . $params[ 'name' ];
    
    // Get the character set used by the DB
    $charset_collate = $wpdb->get_charset_collate();
    
    hrhs_debug( 'HRHS_Database::create_table - Creating database table ' . $table_name );
    // Build the SQL 'CREATE TABLE' command
    // FIXME: Need to add data type info to the columns
    $sql_columns = array();
    foreach ( $params[ 'columns' ] as $column ) {
      $sql_columns[] = "$column varchar(70) NOT NULL,";
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
  dbDelta( $sql );

  // Update the database version in options
  update_option( $option_name, $params[ 'version' ] );

}
  
  // FIXME: This function seems a little too redundant to be necessary
  //        Might be worth using for no other reason than to hide the table prefixes from the rest of the plugin
  public static function insert_data( $params ) {
    global $wpdb;
    hrhs_debug( 'HRHS_Database::insert_data called' );
    
    // I don't want to mess with malformed params at this point, if required fields are missing return
    if (  empty( $params[ 'name' ] ) ||
    empty( $params[ 'data' ] ) ) {
      hrhs_debug( 'HRHS_Database::insert_data - Missing params, returning');
      return;
    }
    
    // Construct the table name
    $table_name = $wpdb->prefix . self::$hrhs_db_prefix . $params[ 'name' ];

    // Insert the data
    $wpdb->insert( $table_name, $param[ 'data' ] );
  }
 
}