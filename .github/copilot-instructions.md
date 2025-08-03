# Project Overview

This project is a WordPress plugin called "Basic LMS for UNAS" that provides learning management system functionality with automatic user management through integration with the UNAS e-commerce API. The plugin enables course enrollment based on product purchases from a UNAS webshop, automatically managing user registration, login, and role assignment.

## Folder Structure

- `/models`: Contains PHP data model classes for API integration
  - `Course.php`: Course entity with product number, role, and title
  - `Orders.php`: Complete order data structure with nested classes for UNAS API responses
- `/languages`: Translation files for internationalization (.po and .mo files)
- `/`: Root plugin directory containing main plugin file and configuration
- `readme.txt`: WordPress plugin documentation and metadata
- `uninstall.php`: Plugin cleanup procedures

## Libraries and Frameworks

- **WordPress Core**: WordPress hooks, actions, and user management functions
- **Ultimate Member**: User registration, login, and profile management integration
- **UNAS API**: External e-commerce API integration via XML requests
- **SimpleXML**: PHP XML parsing for API responses
- **WordPress HTTP API**: `wp_remote_post()` for secure HTTP requests

## Coding Standards

- Use PHP 7.4+ syntax with null coalescing operator (`??`) and typed properties
- Follow WordPress coding standards and naming conventions with `unaslms_` prefix
- Use WordPress sanitization functions: `sanitize_text_field()`, `esc_html()`, `esc_attr()`, `esc_url()`
- Implement proper error handling with user-friendly Hungarian messages
- Use WordPress hooks: `add_action()`, `add_filter()`, `register_activation_hook()`, `register_deactivation_hook()`
- Exit early with security checks: `if (!defined('ABSPATH')) { exit; }`
- Use constants for configuration: `const UNASLMS_API_KEY`, `const UNASLMS_COURSES`, `const UNASLMS_DEBUG`
- Use `define()` for version constants: `define('UNASLMS_VERSION', '0.1.0')`

## Plugin Configuration

- Store API credentials as constants in main plugin file
- Define courses as array of Course objects with product number, role, and title mapping
- Use debug constant `UNASLMS_DEBUG` to control development features
- Configure plugin to work specifically on `/activate` URL endpoint

## API Integration Guidelines

- Use XML format for UNAS API communication with proper encoding
- Implement token-based authentication flow with Bearer tokens
- Handle API errors gracefully with Hungarian user feedback
- Structure API responses using dedicated model classes
- Use `htmlspecialchars()` for XML data sanitization
- Implement proper timeout handling (20 seconds for API calls)

## Security Guidelines

- Validate and sanitize all user inputs from `$_POST` and `$_SERVER`
- Use WordPress nonce verification for forms (implement where needed)
- Implement proper authentication checks with `is_user_logged_in()`
- Escape output data with `esc_html()`, `esc_url()`, etc.
- Use WordPress built-in user management functions
- Redirect after successful operations with `wp_safe_redirect()` to prevent form resubmission
- Use `wp_logout()` for secure user logout

## User Management Integration

- Integrate with Ultimate Member plugin functions: `um_get_core_page()`, `um_fetch_user()`, `UM()->user()->auto_login()`
- Use WordPress user system: `wp_get_current_user()`, `email_exists()`, `user_can()`
- Implement custom user roles for different courses using `add_role()`
- Handle email-based user identification and verification
- Redirect users between registration and login pages based on email existence
- Auto-login users after registration completion

## Course Management

- Define courses as Course objects with product number, WordPress role, and display title
- Use WordPress user roles and capabilities for course access control
- Support multiple course enrollment per user through role assignment
- Track course assignments through user capabilities and roles
- Match UNAS product SKUs with WordPress user roles for automatic enrollment

## Error Handling

- Provide clear Hungarian language error messages for all failure scenarios
- Handle API connection failures gracefully
- Validate email existence before processing
- Check for proper user authentication states
- Display helpful feedback for configuration errors

## UI Guidelines

- Provide Hungarian language user interface for all user-facing content
- Show clear error messages and success feedback in Hungarian
- Use simple HTML forms with proper labels and input validation
- Implement WordPress-compatible styling and responsive design
- Display course information and enrollment status clearly
- Use `email_form()` function pattern for consistent form rendering

## Development Features

- Use `UNASLMS_DEBUG` constant to control development-only features
- Implement debug output for API responses during development
- Provide detailed error logging for troubleshooting
- Allow bypassing API calls in debug