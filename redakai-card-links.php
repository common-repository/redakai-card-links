<?php

/*
Plugin Name: Redakai Card Links
Plugin URI: http://www.tcgplayer.com/
Description: The goal of this Plug-in is to provide an instantaneous way for you to turn all Redakai card names within your blog posts into card information links with Hi-Mid-Low pricing! You never need to highlight a card name and hit a button over and over again. Just type up your entire post and then click the "Card Parse Article" button. All Redakai card names are instantly turned into links that have a hover effect showing the card image and its current Hi-Mid-Low price from over 30 of the internets cheapest vendors!
Version: 1.0.1
Author: TCGplayer
Author URI: http://www.tcgplayer.com
License: GPL2
*/

/*  Copyright 2011 TCGPlayer.com (email : webmaster@tcgplayer.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Useful links:
http://codex.wordpress.org/Function_Reference
http://codex.wordpress.org/Writing_a_Plugin
http://codex.wordpress.org/Creating_Tables_with_Plugins
http://codex.wordpress.org/Function_Reference/wpdb_Class#Run_Any_Query_on_the_Database
http://codex.wordpress.org/Data_Validation
http://codex.wordpress.org/Plugin_API
http://codex.wordpress.org/Plugin_API/Action_Reference
http://codex.wordpress.org/Adding_Administration_Menus
*/
	
	require_once( 'constants.inc.php' );
	add_action( 'init', 'redakaicardref_load_translation_file' );
	add_action( 'init', 'redakaicardref_load_javascript' );
	add_action( 'admin_init', 'redakaicardref_add_button' );
	add_action( 'admin_init', 'redakaicardref_add_before_jquery_and_options' );
	add_action( 'admin_menu', 'redakaicardref_settings_menu' );
	register_activation_hook( __FILE__, 'redakaicardref_plugin_install' );
	register_deactivation_hook( __FILE__,  'redakaicardref_plugin_uninstall' );
 
	function redakaicardref_load_translation_file() {
    // relative path to WP_PLUGIN_DIR where the translation files will sit:
		$plugin_path = plugin_basename( dirname( __FILE__ ) .'/languages' );
	    load_plugin_textdomain( REDAKAICARDREF_PLUGIN_NAME, '', $plugin_path );
	}
	
	function redakaicardref_plugin_install() {
		global $wpdb;
		$sql_file_name = REDAKAICARDREF_DIRECTORY . REDAKAICARDREF_SQL_FILE;
		error_log("CREATE TABLE $wpdb->prefix" . REDAKAICARDREF_TABLE_NAME . " (card_name varchar(255) NOT NULL, PRIMARY KEY (card_name));\n");

		$installation_version = get_option( REDAKAICARDREF_OPTION_NAME );
	// This is the first installation
		if( !isset($installation_version) || !$installation_version ) {
		// Create the option so that we can check for upgrades later and the default partner code
			add_option( REDAKAICARDREF_OPTION_NAME, REDAKAICARDREF_VERSION, null, 'no' );
			add_option( REDAKAICARDREF_PLUGIN_NAME . '_partner_code', 'WORDPRESS', null, 'no' );

			$creation_query = "CREATE TABLE $wpdb->prefix" . REDAKAICARDREF_TABLE_NAME . " (card_name varchar(255) NOT NULL, PRIMARY KEY (card_name));";

			if( !isset( $wpdb ) ) {
				include_once( ABSPATH . '/wp-load.php' );
				include_once( ABSPATH . '/wp-includes/wp-db.php' );
			}

		// Create the table
			if( $wpdb->query( $creation_query ) === false )
				die( "Could not create the plugin table in the database. The error message was " . $wpdb->print_error() . ".\n");

		// Insert the data
			if( !is_file( $sql_file_name ) )
				die( "Could not insert the card list in the plugin table. The error message was " . $wpdb->print_error() . '.' );
			elseif( $wpdb->query( "INSERT IGNORE INTO $wpdb->prefix" . REDAKAICARDREF_TABLE_NAME . " (card_name) VALUES" . file_get_contents( $sql_file_name ) ) === false )
				die( "Could not insert the card list in the plugin table. The error message was " . $wpdb->print_error() . '.' );

		// Copy the TinyMCE plugin file
			if( !redakaicardref_recursive_copy( REDAKAICARDREF_DIRECTORY . 'redakai_tinymce/', ABSPATH . REDAKAICARDREF_TINYMCE_PLUGIN_DIRECTORY ) )
				die( "Could not copy the editor plugin to the appropriate directory. Please see installation notes in order to copy the directory manually. This plugin will not work without a successful copy." );

	// This is an upgrade
		} elseif ( $installation_version != REDAKAICARDREF_VERSION ) {
			update_option( REDAKAICARDREF_OPTION_NAME, REDAKAICARDREF_VERSION );
		// Update the TinyMCE plugin
			redakaicardref_recursive_copy( REDAKAICARDREF_DIRECTORY . 'redakai_tinymce/', ABSPATH . REDAKAICARDREF_TINYMCE_PLUGIN_DIRECTORY );

			if( !isset( $wpdb ) ) {
				include_once( ABSPATH . '/wp-load.php' );
				include_once( ABSPATH . '/wp-includes/wp-db.php' );
			}

		// Update the data in the database
			if( !is_file( $sql_file_name ) )
				die( "Could not insert the card list in the plugin table. The error message was " . $wpdb->print_error() . '.' );
			elseif( !$wpdb->query( "INSERT IGNORE INTO $wpdb->prefix" . REDAKAICARDREF_TABLE_NAME . " VALUES" . file_get_contents( $sql_file_name ) ) )
				die( "Could not insert the card list in the plugin table. The error message was " . $wpdb->print_error() . '.' );
		}
	}

// Remove the option for the plugin version and the table containing all data
	function redakaicardref_plugin_uninstall() {
		global $wpdb;

		delete_option( REDAKAICARDREF_OPTION_NAME );
		
	// Only send the query if the database access is enabled
		if( isset( $wpdb ) )
			@$wpdb->query( 'DROP TABLE ' . $wpdb->prefix . REDAKAICARDREF_TABLE_NAME );
	}

	function redakaicardref_add_button() {
	// Don't bother doing this stuff if the current user lacks permissions
		if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
			return;

	// Add only in Rich Editor mode
		if ( get_user_option('rich_editing') ) {
			add_filter( 'mce_external_plugins', 'redakaicardref_add_tinymce_plugin' );
			add_filter( 'mce_buttons', 'redakaicardref_register_button' );
		}
	}
	
// The button to the TinyMCE UI
	function redakaicardref_register_button( $buttons_array ) {
		array_push( $buttons_array, 'separator', REDAKAICARDREF_PLUGIN_NAME );
		return $buttons_array;
	}
	
// Add the TinyMCE plugin to the list of enabled plugins
	function redakaicardref_add_tinymce_plugin( $plugins_array ) {
		$plugins_array[REDAKAICARDREF_PLUGIN_NAME] = 'plugins/' . REDAKAICARDREF_PLUGIN_NAME . '/editor_plugin.js';
		return $plugins_array;
	}
	
	function redakaicardref_add_before_jquery_and_options() {
		wp_localize_script( 'jquery', 'redakaiValues', array( 'nonce' => wp_create_nonce( REDAKAICARDREF_NONCE_NAME ), 'parser' => plugins_url( 'parser.php', __FILE__ ), 'path' => realpath(ABSPATH) ) );
		wp_enqueue_script( 'jquery' );
		register_setting( 'redakaicardref_options', REDAKAICARDREF_PLUGIN_NAME . '_partner_code', 'redakaicardref_verify_options' );
	}

	function redakaicardref_load_javascript() {
		wp_enqueue_script( REDAKAICARDREF_PLUGIN_NAME . '_cluetip', WP_PLUGIN_URL . '/redakai-card-links/jquery.cluetip.min.js', array('jquery') );
		wp_enqueue_script( 'redakaicardref', WP_PLUGIN_URL . '/redakai-card-links/redakaicardref.js', array('jquery') );
		wp_enqueue_style( 'redakaicardref',WP_PLUGIN_URL . '/redakai-card-links/jquery.cluetip.css' );
	}

	function redakaicardref_settings_menu() {
		add_options_page('Options for the Redakai card links plugin', 'Redakai Card Links', 'install_plugins', 'redakaicardref_settings', 'redakaicardref_display_options');
	}
	
	function redakaicardref_display_options() {
		$partner_code = get_option( REDAKAICARDREF_PLUGIN_NAME . '_partner_code' );
		$partner_code_name = REDAKAICARDREF_PLUGIN_NAME . '_partner_code';
		$save_changes_text = __( 'Save Changes' );

		echo <<<OPTION_END
<div class="wrap">
	<h2>Redakai links options</h2>
	<p>Enter a custom name for your Blog other than "WORDPRESS". The name must contain between 6 and 10 capital letters. This setting is optional.</p>
	<form method="post" action="options.php">
OPTION_END;
		
		settings_fields( 'redakaicardref_options' );

		echo <<<OPTION_END
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Blog name</th>
				<td><input type="text" name="$partner_code_name" value="$partner_code" /></td>
			</tr>
		</table>
		<p class="submit"><input type="submit" class="button-primary" value="$save_changes_text" /></p>
	</form>
</div>
OPTION_END;
	}
	
	function redakaicardref_verify_options($user_input) {
		$verified_input = $user_input;
		
		if( !preg_match( '/^[A-Z]{6,10}$/', $verified_input ) )
			$verified_input = '';
		
		return $verified_input;
	}

/**
 * Functions not directly related to Wordpress
 */
	function redakaicardref_recursive_copy( $source, $destination, $directory_permissions = 0755, $file_permissions = 0644 ) {
		if( !is_dir($source) )
			return copy( $source, $destination );
			
		if( $source[strlen($source) - 1] != '/' )
			$source .= '/';
		
	// Create directory because copy does not create the destination and assign permissions
		@mkdir( $destination );
		chmod( $destination, $directory_permissions );
		
		if( $destination[strlen($destination) - 1] != '/' )
			$destination .= '/';
		
		$source_directory = opendir( $source );
		
		if( $source_directory ) {
			while( $file_read = readdir($source_directory) ) {
				if( $file_read != '.' && $file_read != '..' ) {
					$current_file = $source . $file_read;
					$destination_file = $destination . $file_read;

					if(is_dir($current_file)) {
						redakaicardref_recursive_copy( $current_file, $destination_file );
					} else {
						copy( $current_file, $destination_file );
						chmod( $destination_file, $file_permissions );
					}
				}
			}
			
			closedir( $source_directory );
		} else {
			return false;
		}

		return true;
	}

?>