<?php

add_action('admin_menu', array('QCART_Settings', 'add_options_menu'));

class QCART_Settings
{
    public static function add_options_menu()
    {
        add_options_page('Qcart Settings', 'Qcart', 'manage_options', 'qcart', array('QCART_Settings', 'display_settings_page'));
    }

    public static function display_settings_page()
    {
        $qcart_update = isset($_POST['qcart_update']) ? $_POST['qcart_update'] : "";
        $qcart_key = isset($_POST['qcart_key']) ? $_POST['qcart_key'] : "";
        $qcart_supermarket = isset($_POST['qcart_supermarket']) ? $_POST['qcart_supermarket'] : "";
        $qcart_brands = isset($_POST['qcart_brands']) ? $_POST['qcart_brands'] : "";
        $qcart_cache = isset($_POST['qcart_cache']) ? $_POST['qcart_cache'] : "";

        $qcartUpdate = sanitize_text_field($qcart_update);
        $qcartKey = sanitize_text_field($qcart_key);
        $qcartSupermarket = sanitize_text_field($qcart_supermarket);
        $qcartBrands = sanitize_text_field($qcart_brands);
        $qcartCache = sanitize_text_field($qcart_cache);

        $updated = false;
        $error = "";

        if (!empty($qcartUpdate)) {

            //KEY
            if (isset($qcartKey)) {
                if (preg_match("/ /", $qcartKey)) {
                    $error .= "Invalid key. ";
                } else {
                    update_option('qcart_key', $qcartKey);
                }
            }

            //SUPERMARKET
            if (isset($qcartSupermarket)) {
                if (!preg_match("/^[a-z_]+$/", $qcartSupermarket)) {
                    $error .= "Invalid supermarket. ";
                } else {
                    update_option('qcart_supermarket', $qcartSupermarket);
                }
            }

            //BRANDS
            if (isset($qcartBrands)) {
                if (preg_match("/ /", $qcartBrands) || is_numeric($qcartBrands)) {
                    $error .= "Invalid brands. ";
                } else {
                    update_option('qcart_brands', $qcartBrands);
                }
            }

            $updated = true;
        }

        if (!empty($qcartCache)) {
            wp_cache_flush();
        }

        $key = get_option('qcart_key');
        $supermarket = get_option('qcart_supermarket');
        $brands = get_option('qcart_brands');

        require "templates/settings.php";
    }
}
