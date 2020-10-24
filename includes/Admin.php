<?php

namespace WeDevs\Notification;

/**
 * Admin
 */
class Admin {

    /**
     * Initialize the class
     */
    function __construct() {
        new Admin\AdminBar();
        new Admin\Profile();
    }
}
