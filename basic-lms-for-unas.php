<?php
/**
 * Plugin Name:     Basic LMS with UNAS
 * Plugin URI:      https://TODO:
 * Description:     Basic LMS with automatic user management from UNAS
 * Author:          GergelyBarna
 * Author URI:      https://mediterranfarm.hu
 * Text Domain:     basic-lms-for-unas
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Basic_Lms_For_Unas
 */


// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

define( 'UNASLMS_VERSION', '0.1.0' );

function unaslms_activate() {
    // Do something
}

function unaslms_deactivate() {
    // Do something
}

register_activation_hook( __FILE__, 'unaslms_activate' );
register_deactivation_hook( __FILE__, 'unaslms_deactivate' );

const API_KEY = 'a8de627d72160ca44f8194e0e1cdf12d92dc90dd';
const COURSE_PRODUCT_NUMBERS = [
    'UNASLMS-1', // TODO: update with actual product numbers
    'UNASLMS-2',
    'UNASLMS-3',
];

add_action('init', function() {
    if (!(isset($_SERVER['REQUEST_URI']) && rtrim($_SERVER['REQUEST_URI'], '/') === '/activate')) {
        return;
    }

    $email = email_form();
    if (!isset($email) || empty($email)) {
        exit;
    }

    $token = unas_login(API_KEY);
    if (!isset($token) || empty($token)) {
        echo 'Error: failed to login to the webstore.';
        // TODO: help for the user
        exit;
    }

    $orders = get_orders_by_email($email, $token);
    if (!isset($orders) || empty($orders) || !isset($orders->Order) || empty($orders->Order)) {
        echo 'Error: no orders found for this email.';
        // TODO: error handling
        exit;
    }

    $items = find_items_by_article_number($orders, COURSE_PRODUCT_NUMBERS);
    if (empty($items)) {
        echo 'Error: no items found for the given SKUs.';
        // TODO: error handling
        exit;
    }

    if (email_exists($email)) {
        echo 'You are already registered. Please log in.';
        wp_login_form();

    // register
    } else {
        // set full name from $orders
    }

    // Add role to view courses if not already has role

    // Redirect to courses page

    exit;
});

function unas_login($apiKey) {
    $request = '<?xml version="1.0" encoding="UTF-8" ?>
        <Params>
            <ApiKey>' . $apiKey . '</ApiKey>
        </Params>';

    $response = wp_remote_post(
        'https://api.unas.eu/shop/login',
        array(
            'headers' => array('Content-Type' => 'application/xml'),
            'body'    => $request,
            'timeout' => 20,
        )
    );

    if (is_wp_error($response)) {
        echo 'Error: failed to connect to the webstore.';
        exit;
    }
    
    $xml = simplexml_load_string(wp_remote_retrieve_body($response));
    $token = (string)$xml->Token;
    return $token;
}


function email_form() {
    $submitted_email = null;
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['reg_email']) &&
        !empty(trim($_POST['reg_email']))
    ) {
        $submitted_email = trim($_POST['reg_email']);
        return $submitted_email;
    } else {
        ?>
        <form method="post">
            <label for="reg_email">Email:</label>
            <input type="email" name="reg_email" id="reg_email" required>
            <button type="submit">Submit</button>
        </form>
        <?php
    }
}

function get_orders_by_email($email, $token) {
    $headers = array(
        'Content-Type' => 'application/xml',
        'Authorization' => 'Bearer ' . $token,
    );
    $get_order_request = '<?xml version="1.0" encoding="UTF-8" ?>
        <Params>
            <Email>' . htmlspecialchars($email) . '</Email>
        </Params>';

    $get_order_response = wp_remote_post(
        'https://api.unas.eu/shop/getOrder',
        array(
            'headers' => $headers,
            'body'    => $get_order_request,
            'timeout' => 20,
        )
    );

    if (is_wp_error($get_order_response)) {
        echo 'Error: failed to get orders.';
        exit;
    }

    $orders_xml = wp_remote_retrieve_body($get_order_response);

    require_once __DIR__ . '/models/Orders.php';
    $orders_obj = Orders::fromXml($orders_xml);

    return $orders_obj;
}

function find_items_by_article_number($orders, $skus) {
    $items = [];
    $sku_lookup = array_flip($skus);

    foreach ($orders->Order as $order) {
        if (!isset($order->Items) && empty($order->Items)) {
            continue;
        }
        foreach ($order->Items as $item) {
            if (isset($item->SKU) && isset($sku_lookup[$item->SKU])) {
                $items[] = [
                    'sku' => $item->SKU,
                    'item' => $item
                ];
            }
        }
    }
    return $items;
}

