<?
/*
 * Plugin Name: Spaceman Auth Integration 
 * Plugin URI:  
 * Description: Allows wordpress to obtain novalabs spaceman login/pw
 * Version:     0.0
 * Author:      Bob Coggeshall
 * */

require_once( plugin_dir_path( __FILE__ ) . 'spaceman.php');

// [spaceman_auth_login] in a page sends business to us
add_shortcode( 'spaceman_auth_login',  'spaceman_auth_login' );

// add 'LOGIN' to the menu
add_filter( 'wp_nav_menu_items', 'spaceman_add_menu_items');


// arrange for our js to get loaded 
add_action( 'wp_enqueue_scripts', 'spaceman_enqueue_scripts' );


add_action( 'wp_ajax_sman_auth_validate'       , 'sman_auth_validate' ); // if we are logged into wordpress
add_action( 'wp_ajax_nopriv_sman_auth_validate', 'sman_auth_validate' ); // if we are not
?>
