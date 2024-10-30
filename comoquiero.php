<?php
/*
 * Plugin Name: Qcart
 * Version: 23.09.131449
 * Author: ComoQuiero
 * Author URI: https://qcart.app
 * Plugin URI: https://qcart.app
 * Description: Your plugin to automatically allow your users to add a shopping list cart for your recipes, display nutrition, and more!
 * Text Domain: comoquiero
 */

// stackoverflow.com/questions/6127559/wordpress-plugin-call-to-undefined-function-wp-get-current-user
add_action('init', function () {
    add_action('wp_head', "qcart_head");

    if (is_admin()) {
        include_once 'qcart-settings.php';
    } else {
        add_action('wp_enqueue_scripts', "qcart_scripts");
    }

    // fetch("/wp-admin/admin-ajax.php?action=new", { method: "POST", body: JSON.stringify({ action: "new" }) });
    add_action('wp_ajax_nopriv_new', "qcart_newDomain");

    // fetch("/wp-admin/admin-ajax.php?action=domain", { method: "POST", body: JSON.stringify({ action: "domain" }) });
    add_action('wp_ajax_nopriv_domain', "qcart_changeDomain");

    // fetch("/wp-admin/admin-ajax.php?action=postsync", { method: "POST", body: JSON.stringify({ action: "postsync" }) });
    add_action('wp_ajax_nopriv_postsync', "qcart_postSync");

});

function qcart_newDomain()
{
    $inputJSON = file_get_contents('php://input');
    $data = json_decode($inputJSON, true);

    $subdomain = $data['subdomain'];
    // $domain = "$subdomain.34.235.171.208.nip.io";
    $domain = "$subdomain.qcart.app";

    $id = wpmu_create_blog($domain, "", $subdomain, 1, array('public' => $public));
    die("Inserted domain '$id'");
}

function qcart_changeDomain()
{
    $inputJSON = file_get_contents('php://input');
    $data = json_decode($inputJSON, true);

    $current = $data['current'];
    $new = $data['new'];

    $q = "UPDATE bitnami_wordpress.wp_blogs SET domain = '$new' WHERE domain = '$current'";
    $wpdb->query($wpdb->prepare($q));
    if ($wpdb->last_error) {
        die($wpdb->last_error);
    }

    die(json_encode($result));
}

function qcart_postSync()
{
    // wordpress.stackexchange.com/questions/307193/admin-ajax-php-doesnt-work-when-using-post-data-and-axios
    $inputJSON = file_get_contents('php://input');
    $data = json_decode($inputJSON, true);

    $id = $data['id'];
    $title = wp_strip_all_tags($data['title']);
    $img = $data['img'];
    $date = $data['date'];

    if (empty($id)) {
        http_response_code(400);
        die("missing id");
    }

    if (!is_numeric($id)) {
        http_response_code(400);
        die("not numberic id");
    }

    if (get_post_status($id)) {
        http_response_code(200);
        die("post $id already exists");
    }

    if (empty($title)) {
        http_response_code(400);
        die("missing title");
    }

    if (empty($img)) {
        http_response_code(400);
        die("missing img");
    }

    if (empty($date)) {
        http_response_code(400);
        die("missing date");
    }

    $post = array(
        'import_id' => $id,
        'post_title' => $title,
        'post_content' => $data['content'],
        'post_status' => 'publish',
        'post_date' => $date,
        'post_date_gmt' => $date,
        'post_modified' => $date,
        'post_modified_gmt' => $date,
        'post_category' => $data['categories']
    );

    // // REMOVE TEXT TO HTML CONVERSIONS
    // remove_filter('content_save_pre', 'wp_filter_post_kses');
    // remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');

    $postId = wp_insert_post($post, true);

    // // RETURN TEXT TO HTML CONVERSIONS
    // add_filter('content_save_pre', 'wp_filter_post_kses');
    // add_filter('content_filtered_save_pre', 'wp_filter_post_kses');

    if (empty($postId)) {
        http_response_code(400);
        die("wp_insert_post error. " . json_encode($post));
    }

    if (!is_numeric($postId)) {
        http_response_code(400);
        die($postId->get_error_message());
    }

    // if (!empty($img)) {
    try {
        my_featured_image($img, $postId);
    } catch (Exception $e) {
        http_response_code(400);
        die($e->getMessage());
    }
    // }

    die("Inserted post $postId");
};

// https://stackoverflow.com/questions/19193674/wrong-url-when-using-wp-insert-attachment
function my_file_get_contents($url)
{
    $options = array(
        CURLOPT_AUTOREFERER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_TIMEOUT => 120,
        // CURLOPT_MAXREDIRS => 10,
        CURLOPT_FAILONERROR => true,
    );

    $ch = curl_init($url);
    curl_setopt_array($ch, $options);

    $result = curl_exec($ch);
    $info = curl_getinfo($ch);

    $start = $info['header_size'];
    $body = substr($result, $start, strlen($result) - $start);

    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch) || $httpcode >= 300) {
        throw new Exception(curl_error($ch));
    }

    curl_close($ch);
    return $body;
}

function my_featured_image($image_url, $post_id)
{
    $upload_dir = wp_upload_dir();
    $image_data = my_file_get_contents($image_url);
    if (empty($image_data)) {
        http_response_code(400);
        die("empty image_data");
    }

    $url = parse_url($image_url);
    $filename = $url['path'];
    $filename = str_replace('/', '', $filename);

    // $filename = basename($filename); //INSTAGRAM IMAGES DONT HAVE FILENAME
    $filename = "$filename.jpeg";

    if (wp_mkdir_p($upload_dir["path"])) {
        $file = $upload_dir["path"] . "/" . $filename;
    } else {
        $file = $upload_dir["basedir"] . "/" . $filename;
    }
    file_put_contents($file, $image_data);
    $wp_filetype = wp_check_filetype($filename, null);
    $post_author = get_post_field("post_author", $post_id);
    $attachment = array(
        "post_author" => $post_author,
        "post_mime_type" => $wp_filetype["type"],
        "post_title" => sanitize_file_name($filename),
        "post_content" => "",
        "post_status" => "inherit",
    );
    $attach_id = wp_insert_attachment($attachment, $file, $post_id);
    require_once ABSPATH . "wp-admin/includes/image.php";
    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    $res1 = wp_update_attachment_metadata($attach_id, $attach_data);
    $res2 = set_post_thumbnail($post_id, $attach_id);
}

function qcart_scripts()
{
    // HANDLE SHORTCODE [qcart]
    add_shortcode('qcart', function ($atts) {
        $q = $atts["q"] ? "qcart-" . $atts["q"] : "cq-parent";
        return "<div class='$q'></div>";
    });

    // // DEPRECATED SHORTCODE [comoquiero]:
    // add_shortcode('comoquiero', function ($atts) {
    //     $q = $atts["q"] ? "qcart-" . $atts["q"] : "cq-parent";
    //     return "<div class='$q'></div>";
    // });
}

function qcart_head()
{
    // LOCATION.SEARCH
    $q = "";

    $key = get_option('qcart_key');
    if (!empty($key)) {

        $supermarket = get_option('qcart_supermarket');
        $brands = get_option('qcart_brands');

        // KEY
        $q .= "key=$key";

        // SUPERMARKET
        if (!empty($supermarket)) {
            $q .= "&smkt=$supermarket";
        }

        // BRANDS
        if (!empty($brands)) {
            $q .= "&brands=$brands";
        }
    }

    echo "<script>!function (e, f, u) { e.async = 1; e.src = u; f.parentNode.insertBefore(e, f); }(document.createElement('script'), document.getElementsByTagName('script')[0], 'https://qcart.app/btn.js?trg=any&$q');</script>";
}


add_action('wp_ajax_qcart_handle_cache', 'qcart_handle_cache');

function qcart_handle_cache() {

    if (current_user_can('manage_options')) {
        wp_cache_flush();
        wp_send_json_success(array('message' => 'Caché AMP Borrada correctamente!'));
    } else {
        return;
    }
}



function qcart_enqueue_clean_cache() {
    wp_enqueue_script('qcart-ajax', plugin_dir_url(__FILE__) . 'js/qcart-ajax.js', array('jquery'), '1.0', true);
    wp_localize_script('qcart-ajax', 'qcart_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
}
add_action('admin_enqueue_scripts', 'qcart_enqueue_clean_cache');


function my_amp_script() {
    if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
        //echo '<script custom-element="amp-script" src="'. plugin_dir_url(__FILE__).'js/qcart.js"></script>';
        //echo '<amp-iframe width=1 height=1 src="'. plugin_dir_url(__FILE__).'js/qcart.js" sandbox="allow-scripts allow-same-origin" layout="fixed" frameborder="0"></amp-iframe>';
        echo '<script async custom-element="amp-script" type="text/javascript"> !function (e, f, u) {
            e.async = 1;
            e.src = u;
            f.parentNode.insertBefore(e, f);
        }(document.createElement("script"), document.getElementsByTagName("script")[0], "https://qcart.app/btn.js?trg=any"); 
        </script>';
    }
}
add_action( 'amp_post_template_head', 'my_amp_script', 999 );