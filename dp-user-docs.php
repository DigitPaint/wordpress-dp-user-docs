<?php
/**
 * User documentation as markdown rendered right into WordPress
 *
 * @package   dp_user_docs
 * @author    Flurin Egger <flurin@digitpaint.nl>
 * @license   MIT
 * @link      .
 * @copyright 2016 Digitpaint
 *
 * @wordpress-plugin
 * Plugin Name:       DP User Docs
 * Plugin URI:        https://github.com/digitpaint/wordpress-dp-user-docs
 * Description:       User documentation rendered as markdown right in your admin
 * Version:           1.0.2
 * Author:            Flurin Egger, Digitpaint
 * Author URI:        https://github.com/digitpaint/wordpress-dp-user-docss
 * Text Domain:       dp-user-docs
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 */

if ( is_admin() ) {
    // we are in admin mode
    require_once( dirname( __FILE__ ) . '/admin/admin.php' );
}
