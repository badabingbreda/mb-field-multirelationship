<?php
/**
 * Meta Box Multi Relationship
 *
 * @package     Package
 * @author      Badabingbreda
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Meta Box Multi Relationship
 * Plugin URI:  https://www.badabing.nl
 * Description: Meta Box plugin that adds ability to add multiple relationships to one main object
 * Version:     0.5.0
 * Author:      Badabingbreda
 * Author URI:  https://www.badabing.nl
 * Text Domain: textdomain
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
define( 'MULTIRELATIONSHIP_VERSION' 	, '0.5.0' );
define( 'MULTIRELATIONSHIP_DIR'			, plugin_dir_path( __FILE__ ) );
define( 'MULTIRELATIONSHIP_FILE'		, __FILE__ );
define( 'MULTIRELATIONSHIP_URL' 		, plugins_url( '/', __FILE__ ) );

require_once 'src/class-rwmb-multirelationship-field.php';
require_once 'src/multirelationshipField.php';
