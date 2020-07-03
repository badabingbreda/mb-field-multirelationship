<?php
/**
 * Meta Box Multi Relationship Field
 *
 * @package     Package
 * @author      Badabingbreda
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Meta Box Multi Relationship Field
 * Plugin URI:  https://www.badabing.nl
 * Description: Adds a Field Type for Meta Box to handle multiple relationships for a single CPT
 * Version:     0.5.1
 * Author:      Badabingbreda
 * Author URI:  https://www.badabing.nl
 * Text Domain: textdomain
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
define( 'MULTIRELATIONSHIP_VERSION' 	, '0.5.1' );
define( 'MULTIRELATIONSHIP_DIR'			, plugin_dir_path( __FILE__ ) );
define( 'MULTIRELATIONSHIP_FILE'		, __FILE__ );
define( 'MULTIRELATIONSHIP_URL' 		, plugins_url( '/', __FILE__ ) );

require_once 'src/class-rwmb-multirelationship-field.php';
require_once 'src/multirelationshipField.php';
