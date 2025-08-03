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

// TODO: simple register example: https://github.com/kamerpower/login-registration-wp-plugin/tree/master

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Store the plugin's version
define('UNASLMS_VERSION', '0.1.0');

// Api key for UNAS API from Beállítások / Külső kapcsolatok / API kapcsolat
const API_KEY = 'a8de627d72160ca44f8194e0e1cdf12d92dc90dd';

// Include required model classes
require_once __DIR__ . '/models/Course.php';

// Avaliable courses product numbers, role numbers, and course names
const COURSES = [
    new Course('UNASLMS-1', 'unaslms_course_1', 'UNAS LMS Course 1'),
    new Course('UNASLMS-2', 'unaslms_course_2', 'UNAS LMS Course 2'),
    new Course('product_587', 'kurzus1', 'Kurzus 1'),
];

add_shortcode('showcourses', function () {
    return '<h2>Elérhető tanfolyamok</h2>';
});

add_action('init', function () {
    // Ensure the plugin is only activated on the activation page
    if (!(isset($_SERVER['REQUEST_URI']) && rtrim($_SERVER['REQUEST_URI'], '/') === '/activate')) {
        return;
    }

    $debug = true; // Set to false in production

    // iput variables
    $request_method = $_SERVER['REQUEST_METHOD'];
    $reg_email = $_POST['reg_email'] ?? null; // The email submitted by the user
    $pass = $_POST['pwd'] ?? null; // If the user is already registered, they might submit a password
    $pass1 = $_POST['reg_pass1'] ?? null; // For new registrations
    $pass2 = $_POST['reg_pass2'] ?? null; // For new registrations


    // Initial state (no data submitted)
    if (($request_method !== 'POST' || empty($reg_email))) {
        email_form();
        // TODO: error handling
        exit;

        // Email submitted but not logged in
    } else if (
        $request_method === 'POST'
        && isset($reg_email)
        && !empty($reg_email)
        && empty($pass)
        && empty($pass1)
        && empty($pass2)
        && !is_user_logged_in()
    ) {
        if (!$debug) {
            $items = get_course_items_from_UNAS($reg_email);
            if (is_string($items)) {
                echo $items;
                exit;
            }
        }

        if (email_exists($reg_email) === false) {
            // Email doesn't exist, redirect to ultimate member registration
            wp_safe_redirect(um_get_core_page('register'));
            exit;
        } else {
            // Email exists, redirect to WordPress login
            wp_safe_redirect(um_get_core_page('login'));
            exit;
        }

        // TODO: redirect back to this page after login
        // TODO: customize login URL and page

        // User is logged in
    } else if (
        $request_method === 'POST'
        && isset($reg_email)
        && !empty($reg_email)
        && is_user_logged_in()
    ) {
        // logged in with wrong user
        $user = wp_get_current_user();
        if ($user->user_email !== $reg_email) {
            echo 'Hiba: a bejelentkezett felhasználó email címe nem egyezik a megadott email címmel.';
            // bejelentkezés másik felhasználóként
            wp_logout();
            echo 'Kérem jelentkezzen be újra azzal az email címmel, amivel a tanfolyamot megvásárolta.';
            // bejelentkezés gomb
            echo '<button type="button" onclick="location.href=\'' . esc_url(um_get_core_page('login')) . '\'">Bejelentkezés</button>';
            exit;
        }

        // logged in with the correct email
        $items = get_course_items_from_UNAS($user->user_email);
        if (is_string($items)) {
            echo $items;
            exit;
        }

        // Debug: show the items
        echo '<h3>Debug - Found items:</h3>';
        echo '<pre>' . print_r($items, true) . '</pre>';

        $errors = [];
        $added_courses = [];
        foreach ($items as $item) {
            // find the course by product number
            $course = array_filter(COURSES, function ($course) use ($item) {
                return $course->product_number === $item->Sku;
            });
            if (empty($course)) {
                $errors[] = 'Hiba: a megadott cikkszámokhoz nem található tétel.';
            } else {
                $course_found = array_values($course)[0]; // Get the first element from filtered array
                $course_role = $course_found->role;
                if (!user_can($user->ID, $course_role)) {
                    $user->add_role($course_role);
                    $added_courses[] = $course_found;
                }
            }
        }

        if (!empty($errors)) {
            echo implode('<br>', $errors);
        } else {
            echo 'Az alábbi kurzusok hozzáadva: ';
            foreach ($added_courses as $course) {
                echo '<br>' . esc_html($course->title);
            }
        }

        // Error state
    } else {
        echo 'Hiba: érvénytelen kérés.';
        exit;
    }

    exit;
});

function email_form() {
?>
    <form method="post">
        <label for="reg_email">Email cím:</label>
        <input type="email" name="reg_email" id="reg_email" required>
        <button type="submit">Küldés</button>
    </form>
<?php
}

/**
 * Get course items from UNAS
 *
 * @param string $reg_email The email address of the user
 * @return array|string Array of course items if successful, or an error message if not
 */
function get_course_items_from_UNAS($reg_email) {

    if (empty($reg_email))
        return 'Hiba: az email cím nem lehet üres.';

    $token = unas_login(API_KEY);
    if (!isset($token) || empty($token)) {
        return 'Hiba: nem sikerült bejelentkezni a webáruház API-jába.';
    }

    $orders = get_orders_by_email($reg_email, $token);
    if (!isset($orders) || empty($orders) || !isset($orders->Order) || empty($orders->Order)) {
        return 'Hiba: ezzel az email címmel nem vásároltak a webáruházban.';
    }

    $items = find_items_by_courses($orders, COURSES);
    if (!isset($items) || empty($items)) {
        return 'Hiba: ezzel az email címmel nem vásároltak kurzust.';
    }

    return $items;
}

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
        echo 'Hiba: nem sikerült csatlakozni a webáruház API-jához.';
        exit;
    }

    $xml = simplexml_load_string(wp_remote_retrieve_body($response));
    $token = (string)$xml->Token;
    return $token;
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
        echo 'Hiba: nem sikerült lekérni a rendeléseket.';
        exit;
    }

    $orders_xml = wp_remote_retrieve_body($get_order_response);

    require_once __DIR__ . '/models/Orders.php';
    $orders_obj = Orders::fromXml($orders_xml);

    return $orders_obj;
}

function find_items_by_courses($orders, $courses) {
    $items = [];

    foreach ($orders->Order as $order) {
        if (!isset($order->Items) && empty($order->Items)) {
            continue;
        }
        foreach ($order->Items->Item as $item) {
            if (isset($item->Sku) && isset($courses) && in_array($item->Sku, array_map(function ($course) {
                return $course->product_number;
            }, $courses))) {
                $items[] = $item;
            }
        }
    }
    return $items;
}

function unaslms_activate() {
    // Do something
}

/**
 * Redirect to the previous page after login via the Ultimate Member login form.
 */
add_filter('um_browser_url_redirect_to__filter', function ($url) {
    if (empty($url) && isset($_SERVER['HTTP_REFERER'])) {
        $url = esc_url(wp_unslash($_SERVER['HTTP_REFERER']));
    }
    return add_query_arg('umuid', uniqid(), $url);
});


add_action('um_registration_complete', 'um_121721_change_registration_role', 1);
function um_121721_change_registration_role($user_id) {
    um_fetch_user($user_id);
    UM()->user()->auto_login($user_id);
    if (empty($url) && isset($_SERVER['HTTP_REFERER'])) {
        $url = esc_url(wp_unslash($_SERVER['HTTP_REFERER']));
    }
    wp_redirect($url);
    exit;
}

register_activation_hook(__FILE__, 'unaslms_activate');

function unaslms_deactivate() {
    // Do something
}
register_deactivation_hook(__FILE__, 'unaslms_deactivate');
