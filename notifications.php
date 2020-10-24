<?php
/**
 * Plugin Name: Admin Notification Center
 * Description: A notification center plugin for WordPress.
 * Plugin URI: https://wedevs.com
 * Author: Tareq Hasan
 * Author URI: https://tareq.co
 * Version: 1.0
 * License: GPL2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * The main plugin class
 */
final class WeDevs_Notification {

    /**
     * Plugin version
     *
     * @var string
     */
    const VERSION = '1.0';

    /**
     * Class construcotr
     */
    private function __construct() {
        $this->define_constants();

        register_activation_hook( __FILE__, [ $this, 'activate' ] );

        add_action( 'plugins_loaded', [ $this, 'init_plugin' ] );
    }

    /**
     * Initializes a singleton instance
     *
     * @return \WeDevs_Notification
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Define the required plugin constants
     *
     * @return void
     */
    public function define_constants() {
        define( 'WD_NOTIF_VERSION', self::VERSION );
        define( 'WD_NOTIF_FILE', __FILE__ );
        define( 'WD_NOTIF_PATH', __DIR__ );
        define( 'WD_NOTIF_URL', plugins_url( '', WD_NOTIF_FILE ) );
        define( 'WD_NOTIF_ASSETS', WD_NOTIF_URL . '/assets' );
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init_plugin() {

        new WeDevs\Notification\Assets();
        new WeDevs\Notification\API();

        if ( is_admin() ) {
            new WeDevs\Notification\Admin();
        }
    }

    /**
     * Do stuff upon plugin activation
     *
     * @return void
     */
    public function activate() {
        $installer = new WeDevs\Notification\Installer();
        $installer->run();
    }
}

/**
 * Initializes the main plugin
 *
 * @return \WeDevs_Notification
 */
function wd_notification() {
    return WeDevs_Notification::init();
}

// kick-off the plugin
wd_notification();
