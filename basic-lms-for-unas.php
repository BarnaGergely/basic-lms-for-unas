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
// https://gist.github.com/stnc/98028e7c73aae258473e89a67778bc58

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

define('UNASLMS_VERSION', '0.1.0');

const API_KEY = 'a8de627d72160ca44f8194e0e1cdf12d92dc90dd';
const COURSE_PRODUCT_NUMBERS = [
    'UNASLMS-1', // TODO: update with actual product numbers
    'UNASLMS-2',
    'product_587',
];
const COURSES = [
    new Course('UNASLMS-1', 'unaslms_course_1', 'UNAS LMS Course 1'),
    new Course('UNASLMS-2', 'unaslms_course_2', 'UNAS LMS Course 2'),
    new Course('product_587', 'kurzus1', 'Kurzus 1'),
];

register_activation_hook(__FILE__, 'unaslms_activate');
register_deactivation_hook(__FILE__, 'unaslms_deactivate');


add_shortcode('showcourses', function () {
    return '<h2>Elérhető tanfolyamok</h2>';
});

add_action('init', function () {
    if (!(isset($_SERVER['REQUEST_URI']) && rtrim($_SERVER['REQUEST_URI'], '/') === '/activate')) {
        return;
    }

    $email = email_form();
    if (!isset($email) || empty($email)) {
        exit;
    }

    $token = unas_login(API_KEY);
    if (!isset($token) || empty($token)) {
        echo 'Hiba: nem sikerült bejelentkezni a webáruház API-jába.';
        // TODO: help for the user
        exit;
    }

    $orders = get_orders_by_email($email, $token);
    if (!isset($orders) || empty($orders) || !isset($orders->Order) || empty($orders->Order)) {
        echo 'Hiba: ehhez az emailhez nem található rendelés.';
        // TODO: error handling
        exit;
    }

    $items = find_items_by_article_number($orders, COURSE_PRODUCT_NUMBERS);
    if (!isset($items) || empty($items)) {
        echo 'Hiba: a megadott cikkszámokhoz nem található tétel.';
        // TODO: error handling
        exit;
    }

    // if not the actual user logged in, log out
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        if (strtolower($current_user->user_email) !== strtolower($email)) {
            wp_logout();
            wp_redirect($_SERVER['REQUEST_URI']);
            exit;
        }
    }

    if (!is_user_logged_in()) {
        // login
        if (email_exists($email)) {
            echo 'Már regisztráltál. Kérlek, jelentkezz be!';
            // Show login form and attempt to log in the user if credentials are submitted
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log']) && isset($_POST['pwd'])) {
                $creds = array(
                    'user_login'    => sanitize_text_field($_POST['log']),
                    'user_password' => $_POST['pwd'],
                    'remember'      => true,
                );
                $user = wp_signon($creds, false);
                if (is_wp_error($user)) {
                    echo '<p>Hibás bejelentkezési adatok. Kérlek, próbáld újra!</p>';
                    wp_login_form(array('value_username' => esc_attr($email)));
                } else {
                    wp_redirect($_SERVER['REQUEST_URI']);
                    exit;
                }
            } else {
                wp_login_form(array('value_username' => esc_attr($email)));
                echo '<p><a href="' . esc_url(wp_lostpassword_url()) . '">Elfelejtett jelszó?</a></p>';
            }

            // register than login
        } else {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reg_pass1']) && isset($_POST['reg_pass2'])) {
                $pass1 = $_POST['reg_pass1'];
                $pass2 = $_POST['reg_pass2'];

                if (empty($pass1) || empty($pass2)) {
                    echo '<p>Kérlek, töltsd ki mindkét jelszó mezőt!</p>';
                } elseif ($pass1 !== $pass2) {
                    echo '<p>A két jelszó nem egyezik!</p>';
                } else {
                    $user_id = wp_create_user($email, $pass1, $email);
                    if (is_wp_error($user_id)) {
                        echo '<p>Hiba a regisztráció során: ' . esc_html($user_id->get_error_message()) . '</p>';
                    } else {

                        // Log in
                        $creds = array(
                            'user_login'    => $email,
                            'user_password' => $pass1,
                            'remember'      => true,
                        );
                        $user = wp_signon($creds, false);
                        if (is_wp_error($user)) {
                            echo '<p>Hiba a bejelentkezés során: ' . esc_html($user->get_error_message()) . '</p>';
                        } else {
                            wp_redirect($_SERVER['REQUEST_URI']);
                            exit;
                        }
                    }
                }
            }

            // Registration form
?>
            <form method="post">
                <label for="reg_email">Email cím:</label>
                <input type="email" name="reg_email" id="reg_email" value="<?php echo esc_attr($email); ?>" readonly disabled>
                <br>
                <label for="reg_pass1">Jelszó:</label>
                <input type="password" name="reg_pass1" id="reg_pass1" required>
                <br>
                <label for="reg_pass2">Jelszó újra:</label>
                <input type="password" name="reg_pass2" id="reg_pass2" required>
                <br>
                <button type="submit">Regisztráció</button>
            </form>
        <?php
        }
    }

    // if the role assosiated to the item's sku, is not in the user's roles, add it
    foreach ($items as $item) {
        if (!isset($item->Sku) || !isset(COURSES[$item->Sku])) {
            echo 'Hiba: a tétel cikkszáma vagy a kurzus nem található.';
            continue;
        }

        if (!current_user_can(COURSES[$item->Sku])) {
            $user = wp_get_current_user();
            $user->add_role(COURSES[$item->Sku]);
            echo '<p>Hozzáadtuk a ' . esc_html(COURSES[$item->Sku]) . ' kurzust a fiókodhoz.</p>';
        } else {
            echo '<p>Már hozzáadtuk a ' . esc_html(COURSES[$item->Sku]) . ' kurzust a fiókodhoz.</p>';
        }
    }

    // show the new courses with links to the courses

    // show the old courses with links to the courses

    exit;
});

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
            <label for="reg_email">Email cím:</label>
            <input type="email" name="reg_email" id="reg_email" required>
            <button type="submit">Küldés</button>
        </form>
<?php
    }
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

function find_items_by_article_number($orders, $skus) {
    $items = [];
    $sku_lookup = array_flip($skus);

    foreach ($orders->Order as $order) {
        if (!isset($order->Items) && empty($order->Items)) {
            continue;
        }
        foreach ($order->Items->Item as $item) {
            if (isset($item->Sku) && isset($sku_lookup[$item->Sku])) {
                $items[] = $item;
            }
        }
    }
    return $items;
}

function unaslms_activate() {
    // Do something
}

function unaslms_deactivate() {
    // Do something
}