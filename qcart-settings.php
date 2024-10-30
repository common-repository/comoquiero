<?php

try {
    add_action('admin_menu', array('QCART_Settings', 'add_options_menu'));
} catch (Exception $e) {
    // IGNORE ERRORS BITNAMI MULTISITE
}

class QCART_Settings
{
    public static function add_options_menu()
    {
        try {
            add_options_page('Qcart Settings', 'Qcart', 'manage_options', 'qcart', array('QCART_Settings', 'display_settings_page'));
        } catch (Exception $e) {
            // IGNORE ERRORS BITNAMI MULTISITE
        }
    }

    public static function display_settings_page()
    {
        
        $qcart_cache = isset($_POST['qcart_cache']) ? $_POST['qcart_cache'] : "";
        $qcartCache = sanitize_text_field($qcart_cache);
        $updated = false;
        $error = "";

        if (!empty($qcartCache)) {
            wp_cache_flush();
            $update = true;
        }

        require "templates/settings.php";
    }
}
