<?php

// Email form submitted state



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


function login_form($email = '', $errors = []) {
    ?>
    <form method="post">
        <label for="log">Email cím:</label>
        <input type="email" name="reg_email" id="reg_email" value="<?php echo esc_attr($email); ?>" required>
        <br>
        <label for="pwd">Jelszó:</label>
        <input type="password" name="pwd" id="pwd" required>
        <br>
        <button type="submit">Bejelentkezés</button>
    </form>
    <?php
    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo '<p class="error">' . esc_html($error) . '</p>';
        }
    }
}

function register_form() {
    ?>
    <form method="post">
        <label for="reg_email">Email cím:</label>
        <input type="email" name="reg_email" id="reg_email" required>
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


// User submitted login form
if (
    $request_method === 'POST'
    && isset($reg_email)
    && !empty($reg_email)
    && isset($pass)
    && !empty($pass)
    && empty($pass1)
    && empty($pass2)
    && !is_user_logged_in()
) {
    // login logic
    $creds = array(
        'user_login'    => sanitize_text_field($reg_email),
        'user_password' => sanitize_text_field($pass),
        'remember'      => true,
    );
    $user = wp_signon($creds, false);
    if (is_wp_error($user)) {
        echo '<p>Hibás bejelentkezési adatok. Kérlek, próbáld újra!</p>';
        login_form($reg_email);
        exit;
    } else {
        wp_redirect($_SERVER['REQUEST_URI']);
        exit;
    }

    // User submitted registration form
} else if (
    $request_method === 'POST'
    && isset($reg_email)
    && !empty($reg_email)
    && isset($pass1)
    && !empty($pass1)
    && isset($pass2)
    && !empty($pass2)
    && empty($pass)
    && !is_user_logged_in()
) {
    // register logic
}
