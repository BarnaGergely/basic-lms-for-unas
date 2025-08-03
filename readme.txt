=== Basic LMS for UNAS ===
Contributors: gergelybarna
Donate link: https://mediterranfarm.hu/
Tags: lms, learning management, unas, e-commerce, courses, user management, automatic enrollment
Requires at least: 6.0
Tested up to: 6.8.2
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Basic custom LMS with automatic user management and course enrollment based on UNAS e-commerce purchases.

== Description ==

Basic LMS for UNAS is a WordPress plugin that provides learning management system functionality with automatic user management through integration with the UNAS e-commerce API. The plugin enables seamless course enrollment based on product purchases from a UNAS webshop, automatically managing user registration, login, and role assignment.

= Key Features =

* **Automatic Course Enrollment**: Users are automatically enrolled in courses based on their UNAS webshop purchases
* **Seamless User Management**: Integration with WordPress user system and Ultimate Member plugin
* **Role-Based Access Control**: Automatic assignment of WordPress user roles for course access
* **UNAS API Integration**: Secure connection to UNAS e-commerce platform via XML API
* **Hungarian Language Support**: User interface in Hungarian language
* **Email-Based Verification**: Course enrollment verification through email addresses used for purchases

= How It Works =

1. User enters their email address on the `/activate` page
2. Plugin checks UNAS API for purchases associated with that email
3. If courses are found in purchase history, user is guided through registration/login
4. Upon successful authentication, appropriate course roles are automatically assigned
5. User gains access to purchased courses based on their WordPress user role

= Technical Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* Ultimate Member plugin for user management
* Active UNAS webshop with API access
* UNAS API key configured in plugin constants

== Installation ==

1. Upload the `basic-lms-for-unas` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Install and configure the Ultimate Member plugin
4. Configure your UNAS API key in the plugin file (`UNASLMS_API_KEY` constant)
5. Set up your courses in the `UNASLMS_COURSES` constant with:
   - UNAS product SKU/number
   - WordPress user role name
   - Course title
6. Create a page with the slug `/activate` where users can enter their email
7. Configure Ultimate Member registration and login pages

== Configuration ==

= Setting Up Courses =

Edit the `UNASLMS_COURSES` constant in the main plugin file:

```php
const UNASLMS_COURSES = [
    new Course('PRODUCT_SKU', 'wordpress_role', 'Course Title'),
    // Add more courses as needed
];
```

= UNAS API Setup =

1. Log into your UNAS admin panel
2. Go to Beállítások / Külső kapcsolatok / API kapcsolat
3. Copy your API key
4. Update the `UNASLMS_API_KEY` constant in the plugin file

= WordPress Roles =

Create custom user roles in WordPress (via Ultimate Member or other plugins) that correspond to your courses. Each course should have its own role for proper access control.

== Frequently Asked Questions ==

= Do I need a UNAS webshop to use this plugin? =

Yes, this plugin is specifically designed to work with UNAS e-commerce platforms. You need an active UNAS webshop with API access enabled.

= Which plugins are required? =

The Ultimate Member plugin is required for user registration and login functionality. The plugin integrates with Ultimate Member's user management system.

= Can I customize the user interface? =

The plugin provides basic forms and messages in Hungarian. You can customize the interface by modifying the plugin functions or using WordPress hooks and filters.

= How secure is the UNAS API integration? =

The plugin uses secure HTTPS connections, token-based authentication, and WordPress's built-in HTTP API for all communications with UNAS servers.

= Can users access multiple courses? =

Yes, users can have multiple course roles assigned automatically based on their purchase history from the UNAS webshop.

= What happens if a user's email doesn't match any purchases? =

The plugin will display an error message indicating that no purchases were found for that email address.

== Screenshots ==

1. Email input form on the activation page
2. Course enrollment confirmation screen
3. WordPress admin showing automatically assigned user roles

== Changelog ==

= 0.1.0 =
* Initial release
* Basic UNAS API integration
* Automatic user registration and login workflow
* Course role assignment based on product purchases
* Ultimate Member integration
* Hungarian language interface
* Email-based course verification
* Debug mode for development

== Upgrade Notice ==

= 0.1.0 =
Initial release of Basic LMS with UNAS plugin. Provides automatic course enrollment based on UNAS e-commerce purchases.

== Developer Information ==

= Constants =

* `UNASLMS_API_KEY`: Your UNAS API key
* `UNASLMS_COURSES`: Array of Course objects defining available courses
* `UNASLMS_DEBUG`: Set to true for debug mode (default: false)
* `UNASLMS_VERSION`: Plugin version

= Main Functions =

* `get_course_items_from_UNAS($email)`: Retrieves purchased course items for an email
* `unas_login($apiKey)`: Authenticates with UNAS API
* `get_orders_by_email($email, $token)`: Fetches orders from UNAS API
* `find_items_by_courses($orders, $courses)`: Matches purchased items with defined courses

= Hooks and Actions =

* Uses `init` action for main plugin functionality
* Integrates with Ultimate Member registration completion
* Custom redirect filters for seamless user experience
