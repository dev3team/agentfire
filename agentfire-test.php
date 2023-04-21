<?php

/**
 * Area Test
 *
 * @link https://agentfire.com
 * @since 1.0.0
 * @package AgentFire\Plugin\Test
 *
 * @wordpress-plugin
 * Plugin Name: AgentFire Test
 * Description: Area Test plugin
 * Plugin URI: https://dev-3.com
 * Version: 1.0.0
 * Author: Max Siryk
 * License: Proprietary
 * Network: false
 */

namespace AgentFire\Plugin;

define( 'AGENTFIRE_TEST_VERSION', '1.0.0' );
define( 'AGENTFIRE_TEST_PATH', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'AGENTFIRE_BASE_NAME' ) ) {
    define( 'AGENTFIRE_BASE_NAME', plugin_dir_url( __FILE__ ) );
}

require AGENTFIRE_TEST_PATH . 'vendor/autoload.php';

/**
 * Plugin initialization
 */
Test::getInstance();


