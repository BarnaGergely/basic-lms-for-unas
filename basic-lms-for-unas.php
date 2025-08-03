<?php

/**
 * Plugin Name:     Basic LMS for UNAS
 * Plugin URI:      https://github.com/BarnaGergely/basic-lms-for-unas
 * Description:     Basic custom LMS with automatic user management for UNAS
 * Author:          GergelyBarna
 * Author URI:      https://barnagergely.com
 * Text Domain:     basic-lms-for-unas
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         basic_lms_for_unas
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include required model classes
require_once __DIR__ . '/models/Course.php';



/****************************** EZT KERESED ***************************************/

// Api key for UNAS API from Beállítások / Külső kapcsolatok / API kapcsolat
const UNASLMS_API_KEY = 'YOUR_SECRET_API_KEY';

// Available courses and their associated roles
// Parameters:
// The courses Product Number (Sku) from UNAS, 
// Role ID from WordPress > Ultimate Members > User Roles, 
// Course Title
const UNASLMS_COURSES = [
    new Course('UNASLMS-1', 'unaslms_course_1', 'UNAS LMS Course 1', 'https://example.com/course-1'),
    new Course('UNASLMS-2', 'unaslms_course_2', 'UNAS LMS Course 2', 'https://example.com/course-2'),
    new Course('product_587', 'kurzus1', 'Kurzus 1', 'https://example.com/kurzus-1'),
];

/*********************************************************************************/



const UNASLMS_DEBUG = false; // Set to false in production

// Store the plugin's version
define('UNASLMS_VERSION', '0.1.0');

/**
 * Main Plugin Class
 */
class BasicLMSForUNAS {
    
    /**
     * Plugin version
     */
    const VERSION = '0.1.0';
    
    /**
     * Page slug for our managed activation page
     */
    const PAGE_SLUG = 'activate';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_filter('the_content', [$this, 'filter_page_content']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Page protection hooks
        add_filter('user_can_edit_post', [$this, 'prevent_page_editing'], 10, 3);
        add_filter('page_row_actions', [$this, 'modify_page_row_actions'], 10, 2);
        add_filter('post_row_actions', [$this, 'modify_page_row_actions'], 10, 2);
        add_action('admin_notices', [$this, 'show_edit_prevention_notice']);
        add_action('pre_post_update', [$this, 'prevent_content_update'], 10, 2);
        add_filter('display_post_states', [$this, 'add_managed_state'], 10, 2);
        add_action('admin_head-edit.php', [$this, 'add_admin_css']);
        add_action('admin_head', [$this, 'add_admin_css']);
        add_action('wp_before_admin_bar_render', [$this, 'remove_edit_link_from_admin_bar']);
        
        // Ultimate Member integration hooks
        add_filter('um_browser_url_redirect_to__filter', [$this, 'um_redirect_after_login']);
        add_action('um_registration_complete', [$this, 'um_redirect_after_registration'], 1);
    }
    
    /**
     * Plugin initialization
     */
    public function init(): void {
        // Load text domain for translations
        load_plugin_textdomain('basic-lms-for-unas', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Plugin activation
     */
    public function activate(): void {
        $this->create_activation_page();
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate(): void {
        flush_rewrite_rules();
    }
    
    /**
     * Create the course activation page
     */
    private function create_activation_page(): void {
        // Check if page already exists
        $page = get_page_by_path(self::PAGE_SLUG);
        
        if (!$page) {
            $page_data = [
                'post_title'    => __('Kurzus aktiválása', 'basic-lms-for-unas'),
                'post_name'     => self::PAGE_SLUG,
                'post_content'  => '',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_author'   => 1,
                'comment_status' => 'closed',
                'ping_status'   => 'closed'
            ];
            
            $page_id = wp_insert_post($page_data);
            
            if (!is_wp_error($page_id)) {
                // Store the page ID in options for later reference
                update_option('basic_lms_activation_page_id', $page_id);
                
                // Add metadata to mark this page as plugin managed
                update_post_meta($page_id, '_basic_lms_managed_page', true);
                update_post_meta($page_id, '_edit_lock', time() . ':1'); // Prevent editing
            }
        } else {
            // If page exists, store its ID and mark as managed
            update_option('basic_lms_activation_page_id', $page->ID);
            update_post_meta($page->ID, '_basic_lms_managed_page', true);
            update_post_meta($page->ID, '_edit_lock', time() . ':1');
        }
    }

    
    /**
     * Filter page content to show our course activation interface
     */
    public function filter_page_content(string $content): string {
        if (is_page(self::PAGE_SLUG)) {
            $activation_content = $this->get_activation_content();
            return $activation_content;
        }
        
        return $content;
    }
    
    /**
     * Get the course activation content
     */
    private function get_activation_content(): string {
        ob_start();
        
        // HTTP input variables
        $request_method = $_SERVER['REQUEST_METHOD'];
        $reg_email = $_POST['reg_email'] ?? null; // The email submitted by the user
        $pass = $_POST['pwd'] ?? null; // If the user is already registered, they might submit a password
        $pass1 = $_POST['reg_pass1'] ?? null; // For new registrations
        $pass2 = $_POST['reg_pass2'] ?? null; // For new registrations
        
        ?>
        <div class="unaslms container">
            <div class="activation-wrapper">
                <h1>Kurzus aktiválása</h1>
                <?php

                // Initial state (no data submitted)
                if (($request_method !== 'POST' || empty($reg_email))) {
                    $this->email_form();

                // Email submitted but not logged in state
                } else if (
                    $request_method === 'POST'
                    && isset($reg_email)
                    && !empty($reg_email)
                    && empty($pass)
                    && empty($pass1)
                    && empty($pass2)
                    && !is_user_logged_in()
                ) {
                    if (!UNASLMS_DEBUG) {
                        $items = $this->get_course_items_from_UNAS($reg_email);
                        if (is_string($items)) {
                            echo '<div class="error">' . esc_html($items) . '</div>';
                            return ob_get_clean();
                        }
                    }

                    if (email_exists($reg_email) === false) {
                        // Email doesn't exist, redirect to ultimate member registration
                        ?>
                        <p>Ahhoz hogy bármikor biztonságosan hozzá férhess a kurzusaidhoz, kérlek regisztrálj.</p>
                        <button type="button" onclick="location.href='<?php echo esc_url(um_get_core_page('register')); ?>'">Regisztráció</button>
                        <?php
                    } else {
                        // Email exists, redirect to WordPress login
                        ?>
                        <p>Ahhoz hogy hozzá férhess a kurzusaidhoz, kérlek jelentkezz be.</p>
                        <button type="button" onclick="location.href='<?php echo esc_url(um_get_core_page('login')); ?>'">Bejelentkezés</button>
                        <?php
                    }

                // User is logged in state
                } else if (
                    $request_method === 'POST'
                    && isset($reg_email)
                    && !empty($reg_email)
                    && is_user_logged_in()
                ) {
                    // logged in with wrong user
                    $user = wp_get_current_user();
                    if ($user->user_email !== $reg_email) {
                        echo '<div class="error">Hiba: a bejelentkezett felhasználó email címe nem egyezik a megadott email címmel.</div>';
                        // bejelentkezés másik felhasználóként
                        wp_logout();
                        echo '<p>Kérem jelentkezzen be újra azzal az email címmel, amivel a tanfolyamot megvásárolta.</p>';
                        // bejelentkezés gomb
                        echo '<button type="button" onclick="location.href=\'' . esc_url(um_get_core_page('login')) . '\'">Bejelentkezés</button>';
                    } else {
                        // logged in with the correct email
                        $items = $this->get_course_items_from_UNAS($user->user_email);
                        if (is_string($items)) {
                            echo '<div class="error">' . esc_html($items) . '</div>';
                            return ob_get_clean();
                        }

                        // Debug: show the items
                        if (UNASLMS_DEBUG) {
                            echo '<h3>Debug - Found items:</h3>';
                            echo '<pre>' . print_r($items, true) . '</pre>';
                        }

                        $errors = [];
                        $added_courses = [];
                        foreach ($items as $item) {
                            // find the course by product number
                            $course = array_filter(UNASLMS_COURSES, function ($course) use ($item) {
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
                            echo '<div class="error">' . implode('<br>', $errors) . '</div>';
                        } else {
                            ?>
                            <div class="success">
                                <h2>Az alábbi kurzusok hozzáadva:</h2>
                                <?php
                                foreach ($added_courses as $course) {
                                ?>
                                    <div class="course-item">
                                        <h3><?php echo esc_html($course->title); ?></h3>
                                        <p><a href="<?php echo esc_url($course->url); ?>">Kurzus link</a></p>
                                    </div>
                                <?php
                                }
                                ?>
                            </div>
                            <?php
                        }
                    }

                // Error state
                } else {
                    echo '<div class="error">Hiba: érvénytelen kérés.</div>';
                }

                ?>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Display the email form
     */
    private function email_form(): void {
        ?>
        <p>Ezen az oldalon fogod tudni elérni a Mediterranfarm webáruházban vásárolt kurzusaidat.</p>
        <p>Kérjük add meg az email címet, amivel megvásároltad valamelyik kurzusunkat.</p>
        <p>Itt tudsz új kurzusokat vásárolni: <a href="https://www.mediterranfarm.hu/" target="_blank">kurzusaink</a></p>
        <form method="post">
            <label for="reg_email">Email cím:</label>
            <input type="email" name="reg_email" id="reg_email" required>
            <button type="submit">Küldés</button>
        </form>
        <?php
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts(): void {
        if (is_page(self::PAGE_SLUG)) {
            wp_enqueue_style(
                'basic-lms-for-unas-style',
                plugin_dir_url(__FILE__) . 'assets/style.css',
                [],
                self::VERSION
            );
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu(): void {
        add_options_page(
            __('Basic LMS for UNAS Settings', 'basic-lms-for-unas'),
            __('UNAS LMS', 'basic-lms-for-unas'),
            'manage_options',
            'basic-lms-for-unas',
            [$this, 'admin_page']
        );
    }
    
    /**
     * Admin page content
     */
    public function admin_page(): void {
        $page_id = get_option('basic_lms_activation_page_id');
        $page_url = $page_id ? get_permalink($page_id) : '';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Basic LMS for UNAS Settings', 'basic-lms-for-unas'); ?></h1>
            
            <div class="card">
                <h2><?php echo esc_html__('Course Activation Page', 'basic-lms-for-unas'); ?></h2>
                <p><?php echo esc_html__('This plugin creates and manages a course activation page for UNAS integration.', 'basic-lms-for-unas'); ?></p>
                
                <?php if ($page_url): ?>
                    <p>
                        <strong><?php echo esc_html__('Page URL:', 'basic-lms-for-unas'); ?></strong>
                        <a href="<?php echo esc_url($page_url); ?>" target="_blank">
                            <?php echo esc_html($page_url); ?>
                        </a>
                    </p>
                    <p>
                        <a href="<?php echo esc_url($page_url); ?>" class="button button-primary" target="_blank">
                            <?php echo esc_html__('View Activation Page', 'basic-lms-for-unas'); ?>
                        </a>
                    </p>
                <?php else: ?>
                    <p class="notice notice-warning">
                        <?php echo esc_html__('Activation page not found. Try deactivating and reactivating the plugin.', 'basic-lms-for-unas'); ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2><?php echo esc_html__('Page Protection', 'basic-lms-for-unas'); ?></h2>
                <p><?php echo esc_html__('The activation page is protected from editing. Its content is managed entirely by this plugin.', 'basic-lms-for-unas'); ?></p>
                <p><strong><?php echo esc_html__('Note:', 'basic-lms-for-unas'); ?></strong> <?php echo esc_html__('You cannot edit this page through the WordPress editor. Any changes must be made by updating the plugin.', 'basic-lms-for-unas'); ?></p>
            </div>
            
            <div class="card">
                <h2><?php echo esc_html__('UNAS API Configuration', 'basic-lms-for-unas'); ?></h2>
                <p><?php echo esc_html__('API Key Status:', 'basic-lms-for-unas'); ?> 
                    <?php if (UNASLMS_API_KEY === 'YOUR_SECRET_API_KEY'): ?>
                        <span style="color: red;"><?php echo esc_html__('Not configured', 'basic-lms-for-unas'); ?></span>
                    <?php else: ?>
                        <span style="color: green;"><?php echo esc_html__('Configured', 'basic-lms-for-unas'); ?></span>
                    <?php endif; ?>
                </p>
                <p><?php echo esc_html__('Available Courses:', 'basic-lms-for-unas'); ?> <strong><?php echo count(UNASLMS_COURSES); ?></strong></p>
                <p><?php echo esc_html__('Debug Mode:', 'basic-lms-for-unas'); ?> <strong><?php echo UNASLMS_DEBUG ? 'Enabled' : 'Disabled'; ?></strong></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Check if the given post is our managed page
     */
    private function is_managed_page($post_id): bool {
        $managed_page_id = get_option('basic_lms_activation_page_id');
        return $managed_page_id && (int)$post_id === (int)$managed_page_id;
    }
    
    /**
     * Prevent editing of our managed page
     */
    public function prevent_page_editing(bool $can_edit, int $user_id, int $post_id): bool {
        if ($this->is_managed_page($post_id)) {
            return false;
        }
        return $can_edit;
    }
    
    /**
     * Modify page row actions to remove edit links and add managed indicator
     */
    public function modify_page_row_actions(array $actions, \WP_Post $post): array {
        if ($this->is_managed_page($post->ID)) {
            // Remove edit-related actions
            unset($actions['edit']);
            unset($actions['inline hide-if-no-js']);
            unset($actions['duplicate']);
            
            // Add view action if not present
            if (!isset($actions['view'])) {
                $actions['view'] = sprintf(
                    '<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
                    esc_url(get_permalink($post->ID)),
                    esc_attr(sprintf(__('View &#8220;%s&#8221;'), $post->post_title)),
                    __('View')
                );
            }
        }
        return $actions;
    }
    
    /**
     * Show notice when trying to edit the managed page
     */
    public function show_edit_prevention_notice(): void {
        global $pagenow, $post;
        
        if (($pagenow === 'post.php' || $pagenow === 'page.php') && 
            isset($_GET['action']) && $_GET['action'] === 'edit' && 
            isset($post) && $this->is_managed_page($post->ID)) {
            
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . esc_html__('Warning:', 'basic-lms-for-unas') . '</strong> ';
            echo esc_html__('This page is managed by the Basic LMS for UNAS plugin. Any changes you make here will be overridden by the plugin.', 'basic-lms-for-unas');
            echo '</p></div>';
        }
    }
    
    /**
     * Prevent content updates for managed page
     */
    public function prevent_content_update(int $post_id, array $data): void {
        if ($this->is_managed_page($post_id)) {
            // Restore original content to prevent changes
            remove_filter('content_save_pre', 'wp_filter_post_kses');
            remove_filter('content_save_pre', 'wp_targeted_link_rel');
            
            // Get the current post to restore its content
            $post = get_post($post_id);
            if ($post) {
                global $wpdb;
                $wpdb->update(
                    $wpdb->posts,
                    ['post_content' => ''], // Keep content empty as plugin manages it
                    ['ID' => $post_id],
                    ['%s'],
                    ['%d']
                );
            }
        }
    }
    
    /**
     * Add "managed" state to the page in the list
     */
    public function add_managed_state(array $post_states, \WP_Post $post): array {
        if ($this->is_managed_page($post->ID)) {
            $post_states['basic_lms_managed'] = __('LMS managed', 'basic-lms-for-unas');
        }
        return $post_states;
    }
    
    /**
     * Add custom CSS to admin pages list
     */
    public function add_admin_css(): void {
        global $pagenow;
        $managed_page_id = get_option('basic_lms_activation_page_id');
        
        // Only add CSS on relevant admin pages
        if ($managed_page_id && ($pagenow === 'edit.php' || $pagenow === 'post.php' || $pagenow === 'page.php')) {
            echo '<style>
                /* Highlight the managed page row */
                tr#post-' . esc_attr($managed_page_id) . ' {
                    background-color: #f8f9fa;
                    border-left: 4px solid #007cba;
                }
                tr#post-' . esc_attr($managed_page_id) . ' .page-title strong {
                    color: #007cba;
                }
                
                /* Style the managed post state text with WordPress standard gray */
                .post-state-basic_lms_managed,
                span.post-state-basic_lms_managed,
                .post-states .post-state-basic_lms_managed,
                .column-title .post-state-basic_lms_managed,
                .row-title .post-state-basic_lms_managed {
                    color: #646970 !important;
                    font-weight: normal !important;
                    font-style: normal !important;
                }
                
                /* Ensure it matches other post states */
                .post-state {
                    color: #646970;
                }
            </style>';
        }
    }
    
    /**
     * Remove edit link from admin bar for managed page
     */
    public function remove_edit_link_from_admin_bar(): void {
        global $wp_admin_bar, $post;
        
        if (is_page() && isset($post) && $this->is_managed_page($post->ID)) {
            $wp_admin_bar->remove_node('edit');
        }
    }
    
    /**
     * Ultimate Member redirect after login
     */
    public function um_redirect_after_login($url) {
        if (empty($url) && isset($_SERVER['HTTP_REFERER'])) {
            $url = esc_url(wp_unslash($_SERVER['HTTP_REFERER']));
        }
        return add_query_arg('umuid', uniqid(), $url);
    }
    
    /**
     * Ultimate Member redirect after registration
     */
    public function um_redirect_after_registration($user_id) {
        um_fetch_user($user_id);
        UM()->user()->auto_login($user_id);
        if (empty($url) && isset($_SERVER['HTTP_REFERER'])) {
            $url = esc_url(wp_unslash($_SERVER['HTTP_REFERER']));
        }
        wp_redirect($url);
        exit;
    }

    /**
     * Get course items from UNAS
     *
     * @param string $reg_email The email address of the user
     * @return array|string Array of course items if successful, or an error message if not
     */
    private function get_course_items_from_UNAS($reg_email) {

        if (empty($reg_email))
            return 'Hiba: az email cím nem lehet üres.';

        $token = $this->unas_login(UNASLMS_API_KEY);
        if (!isset($token) || empty($token)) {
            return 'Hiba: nem sikerült bejelentkezni a webáruház API-jába.';
        }

        $orders = $this->get_orders_by_email($reg_email, $token);
        if (is_string($orders)) {
            return $orders; // Return the error message if there was an error
        } else if (!isset($orders) || empty($orders) || !isset($orders->Order) || empty($orders->Order)) {
            return 'Hiba: ezzel az email címmel nem vásároltak a webáruházban.';
        }

        $items = $this->find_items_by_courses($orders, UNASLMS_COURSES);
        if (!isset($items) || empty($items)) {
            return 'Hiba: ezzel az email címmel nem vásároltak kurzust.';
        }

        return $items;
    }

    /**
     * Log in to the UNAS API
     *
     * @param string $apiKey UNAS API key
     * @return string|null The API token if successful, or null if not
     */
    private function unas_login($apiKey) {
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
            return null; // Return null if there was an error
        }

        $xml = simplexml_load_string(wp_remote_retrieve_body($response));
        $token = (string)$xml->Token;
        return $token;
    }

    /**
     * Get orders by email from UNAS API
     *
     * @param string $email The email address to search for
     * @param string $token The UNAS API token for authentication
     * @return Orders|string Orders object if successful, or an error message if not
     */
    private function get_orders_by_email($email, $token) {
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
            return 'Hiba: nem sikerült lekérni a rendeléseket.';
        }

        $orders_xml = wp_remote_retrieve_body($get_order_response);

        require_once __DIR__ . '/models/Orders.php';
        $orders_obj = Orders::fromXml($orders_xml);

        return $orders_obj;
    }


    /**
     * Find items by courses from orders
     *
     * @param Orders $orders The orders object containing order data
     * @param array<Course> $courses Array of Course objects to match against
     * @return array<Item> Array of items that match the courses
     */
    private function find_items_by_courses($orders, $courses) {
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
}

// Initialize the plugin
new BasicLMSForUNAS();
