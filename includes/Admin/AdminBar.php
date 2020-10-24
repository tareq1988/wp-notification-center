<?php

namespace WeDevs\Notification\Admin;

/**
 * Admin bar class
 */
class AdminBar {

    /**
     * Initialize the class
     */
    function __construct() {
        add_action( 'admin_bar_menu', [ $this, 'add_menu_icon' ], 0 );
        add_action( 'admin_footer', [ $this, 'print_template' ] );
    }

    /**
     * Add the admin bar menu
     *
     * @param void $wp_admin_bar
     */
    public function add_menu_icon( $wp_admin_bar ) {
        $args = array(
            'id'    => 'wd-notify-center',
            'title' => '<span class="ab-icon"><svg viewBox="0 0 472.615 472.615">
                <g>
                    <path d="M370.696,254.31v-70.943c0-61.689-41.619-113.541-98.271-129.335V0h-72.233v54.032
                        c-56.654,15.794-98.272,67.646-98.272,129.335v70.943c0,56.144-36.926,116.194-78.953,151.111h146.147
                        c0,37.11,30.084,67.194,67.195,67.194c37.11,0,67.194-30.084,67.194-67.194H449.65
                        C407.622,370.505,370.696,310.454,370.696,254.31z M236.312,104.185c-43.669,0-79.193,35.519-79.193,79.183h-19.692
                        c0-54.519,44.361-98.875,98.885-98.875V104.185z"/>
                </g>
                </svg></span>
                <span class="screen-reader-text">' . __( 'Notification', 'notification-center' ) . '</span>',
            'parent' => 'top-secondary',
            'href'   => '',
        );

        $wp_admin_bar->add_node( $args );
    }

    /**
     * Add the templates
     *
     * @return void
     */
    public function print_template() {
        include __DIR__ . '/views/template.php';
    }
}
