# Project Overview

This project is a WordPress plugin called "Basic LMS with UNAS" that provides learning management system functionality with automatic user management through integration with the UNAS e-commerce API. The plugin enables course enrollment based on product purchases from a UNAS webshop, automatically managing user registration, login, and role assignment.

## Folder Structure

- `/models`: Contains PHP data model classes for API integration
  - `Course.php`: Course entity with product number, role, and title
  - `Orders.php`: Complete order data structure with nested classes for UNAS API responses
- `/`: Root plugin directory containing main plugin file and configuration
- `readme.txt`: WordPress plugin documentation and metadata
- `uninstall.php`: Plugin cleanup procedures

## Libraries and Frameworks

- **WordPress Core**: WordPress hooks, actions, and user management functions
- **UNAS API**: External e-commerce API integration via XML requests
- **SimpleXML**: PHP XML parsing for API responses
- **WordPress HTTP API**: `wp_remote_post()` for secure HTTP requests

## Coding Standards

- Use PHP 7.4+ typed properties and return types
- Follow WordPress coding standards and naming conventions
- Use WordPress sanitization functions: `sanitize_text_field()`, `esc_html()`, `esc_attr()`, `esc_url()`
- Implement proper error handling with user-friendly Hungarian messages
- Use WordPress hooks: `add_action()`, `add_shortcode()`, `register_activation_hook()`
- Exit early with security checks: `if (!defined('ABSPATH')) { exit; }`

## API Integration Guidelines

- Store API credentials as constants (API_KEY)
- Use XML format for UNAS API communication
- Implement token-based authentication flow
- Handle API errors gracefully with user feedback
- Structure API responses using dedicated model classes

## Security Guidelines

- Validate and sanitize all user inputs
- Use WordPress nonce verification for forms
- Implement proper authentication checks
- Escape output data appropriately
- Use WordPress built-in user management functions
- Redirect after successful operations to prevent form resubmission

## User Management

- Integrate with WordPress user system (`wp_create_user()`, `wp_signon()`)
- Use WordPress roles and capabilities for course access
- Implement custom user roles for different courses
- Handle email-based user identification and verification
- Provide password reset functionality via WordPress core

## Course Management

- Define courses as product-role-title mappings
- Use WordPress user roles for course access control
- Support multiple course enrollment per user
- Track course assignments through user capabilities

## UI Guidelines

- Provide Hungarian language user interface
- Show clear error messages and success feedback
- Use WordPress-style forms and styling
- Implement responsive design compatible with WordPress themes
- Display course information using shortcodes (`[showcourses]`)
