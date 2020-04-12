<?php

namespace WeDevs\Notification;

/**
 * Installer
 */
class Installer {

    /**
     * Run the installer
     *
     * @return void
     */
    public function run() {
        $this->add_version();
        $this->create_tables();
    }

    /**
     * Add time and version on DB
     */
    public function add_version() {
        $installed = get_option( 'wd_notification_installed' );

        if ( ! $installed ) {
            update_option( 'wd_notification_installed', time() );
        }

        update_option( 'wd_notification_version', WD_NOTIF_VERSION );
    }

    /**
     * Create necessary database tables
     *
     * @return void
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $schema = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wd_notifications` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `body` text DEFAULT NULL,
            `link` varchar(255) DEFAULT NULL,
            `read` tinyint(1) unsigned NOT NULL DEFAULT 0,
            `type` enum('CORE','THEME','PLUGIN','USER') DEFAULT 'USER',
            `to` bigint(20) unsigned NOT NULL,
            `sent_by` varchar(100) NOT NULL DEFAULT '',
            `icon` varchar(255) DEFAULT '',
            `origin` varchar(60) DEFAULT '',
            `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `read_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `to` (`to`),
            KEY `sent_at` (`sent_at`)
        ) $charset_collate";

        if ( ! function_exists( 'dbDelta' ) ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        dbDelta( $schema );
    }
}
