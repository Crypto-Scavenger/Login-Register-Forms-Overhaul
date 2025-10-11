# Login Forms Overhaul

Complete control over WordPress login and logout flows including custom styling, error message handling, redirects, session management, and granular access controls. Features a futuristic cyberpunk dark theme by default.

## Description

**Login Forms Overhaul** provides comprehensive customization of the WordPress authentication experience. Control every aspect of the login page appearance, behavior, and security features through an intuitive admin interface. Perfect for agencies, membership sites, client projects, or any WordPress installation requiring a branded authentication experience.

### Key Features

#### Visual Control
- **Cyberpunk Dark Theme** - Futuristic design with #262626 background, #ffffff text, and #d11c1c accents
- **Custom CSS/JS Injection** - Add your own styles and scripts to the login page
- **Custom Logo** - Replace the WordPress logo with your own branding
- **Logo Link Customization** - Control where the logo links to
- **Font Awesome Support** - Built-in icon integration for enhanced visuals
- **Mobile Responsive** - Optimized for all screen sizes

#### Behavioral Control
- **Hide Login Errors** - Security enhancement to prevent username enumeration
- **Custom Error Messages** - Replace detailed errors with generic messages
- **Login Redirects** - Role-based redirect URLs after successful login
- **Logout Redirects** - Custom URLs after logout
- **Skip Logout Confirmation** - Direct logout without confirmation page
- **Session Expire Control** - Hide or customize session expiration warnings

#### Form Element Control
- **Disable Language Switcher** - Hide the language selection dropdown
- **Disable Privacy Link** - Remove the privacy policy link
- **Disable Back to Site** - Hide the "← Go to [Site Name]" link
- **Complete Element Control** - Show or hide any login form element

#### Access Control
- **Role-Based Exceptions** - Allow specific user roles to bypass customizations
- **IP Allowlist** - Whitelist IP addresses to see default login page
- **Granular Permissions** - Control who sees customizations and who doesn't

#### Security & Performance
- **Custom Database Tables** - All settings stored in dedicated table (no wp_options bloat)
- **Lazy Loading** - Settings loaded only when needed for optimal performance
- **Conditional Asset Loading** - CSS/JS loaded only on login pages
- **Object Caching** - Built-in caching for database queries
- **SQL Injection Protection** - All queries use prepared statements
- **XSS Prevention** - All output properly escaped
- **CSRF Protection** - Nonce verification on all forms
- **Capability Checks** - Double verification in render and save methods

## Installation

### Manual Installation

1. Download the plugin files
2. Upload the `login-forms-overhaul` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to **Appearance → Login Forms** to configure settings

### Configuration

After activation:

1. Go to **Appearance → Login Forms** in your WordPress admin
2. Configure settings across four tabs:
   - **General** - Enable/disable plugin and cleanup options
   - **Styling** - Visual customization (CSS, JS, logo)
   - **Behavior** - Login/logout behavior and form elements
   - **Access Control** - Role exceptions and IP allowlist
3. Click "Save Changes"
4. Open an incognito/private window to preview changes

## Settings Guide

### General Tab

#### Enable Plugin
Toggle all customizations on or off without losing your settings.

#### Cleanup on Uninstall
Choose whether to remove all plugin data when uninstalling. Enabled by default.

### Styling Tab

#### Use Default Dark Theme
Applies the built-in cyberpunk dark theme with:
- Background: #262626 (dark gray)
- Text: #ffffff (white)
- Accents: #d11c1c (red)
- Futuristic grid overlay
- Animated glow effects
- Monospace fonts
- Scanline animations

#### Custom Logo URL
Enter a direct URL to your logo image. Leave empty to use the default WordPress logo.

**Recommended dimensions:** 84x84 pixels for best results

#### Logo Link URL
URL that users navigate to when clicking the logo. Defaults to your home page.

#### Custom CSS
Add your own CSS styles to further customize the login page. Injected directly into the page head.

**Example:**
```css
body.login {
    background-image: url('your-background.jpg');
}
#loginform {
    border-radius: 10px;
}
```

#### Custom JavaScript
Add custom JavaScript functionality to the login page. Do not include `<script>` tags.

**Example:**
```javascript
console.log('Custom login page loaded');
document.getElementById('user_login').placeholder = 'Enter username';
```

### Behavior Tab

#### Hide Login Errors
**Security Enhancement** - Replaces detailed error messages (like "Unknown username" or "Incorrect password") with a generic message to prevent username enumeration attacks.

#### Custom Error Message
The generic message shown when "Hide Login Errors" is enabled. Default: "Invalid credentials."

#### Disable Language Switcher
Hides the language selection dropdown from the login page.

#### Disable Privacy Link
Removes the "Privacy Policy" link from below the login form.

#### Disable Back to Site Link
Hides the "← Go to [Site Name]" link that appears below the login form.

#### Subscriber Login Redirect
Custom URL to redirect users with the Subscriber role after successful login. Leave empty to use WordPress default behavior.

**Example:** `/dashboard/` or `/my-account/`

#### Logout Redirect URL
Custom URL to redirect users after logging out. Defaults to the home page if empty.

#### Skip Logout Confirmation
Immediately logs out users without showing the "You are now logged out" confirmation page.

#### Hide Session Expire Modal
Suppresses the session expiration warning modal that appears when a user's session is about to expire.

### Access Control Tab

#### Role Exceptions
Select user roles that should bypass all customizations and see the default WordPress login page. Useful for:
- Administrators who need standard access
- Developers working on the site
- Support staff requiring default interface

**Available roles:** Administrator, Editor, Author, Contributor, Subscriber, and any custom roles

#### IP Allowlist
Enter IP addresses (one per line) that should bypass all customizations. Users from these IPs will see the default WordPress login page.

**Use cases:**
- Office IP addresses
- VPN IP addresses
- Developer machines
- Emergency access

**Format:**
```
192.168.1.100
203.0.113.42
198.51.100.0
```

## Plugin Hooks

### Available Hooks

The plugin exposes several hooks for developers who want to extend functionality:

#### Filters

```php
// Modify whether customizations should apply
add_filter('lfo_should_customize', function($should_customize, $settings) {
    // Your logic here
    return $should_customize;
}, 10, 2);

// Modify custom CSS before injection
add_filter('lfo_custom_css', function($css, $settings) {
    // Your modifications
    return $css;
}, 10, 2);

// Modify custom JS before injection
add_filter('lfo_custom_js', function($js, $settings) {
    // Your modifications
    return $js;
}, 10, 2);
```

#### Actions

```php
// After settings are saved
add_action('lfo_settings_saved', function($settings) {
    // Your code here
});

// Before login assets are enqueued
add_action('lfo_before_enqueue_assets', function() {
    // Your code here
});
```

## File Structure

```
login-forms-overhaul/
├── login-forms-overhaul.php    # Main plugin file
├── README.md                    # This file
├── uninstall.php               # Uninstall handler
├── index.php                   # Security stub
├── assets/                     # All assets (no subdirectories)
│   ├── admin.css              # Admin interface styles
│   ├── admin.js               # Admin interface scripts
│   ├── login.css              # Login page styles (dark theme)
│   ├── login.js               # Login page behavior
│   └── index.php              # Security stub
└── includes/                   # PHP classes
    ├── class-database.php     # Database operations
    ├── class-core.php         # Core functionality
    ├── class-admin.php        # Admin interface
    └── index.php              # Security stub
```

## WordPress APIs Used

- **Plugin API** - Action and filter hooks for WordPress integration
- **Database API** - All queries use `$wpdb->prepare()` with proper placeholders
- **Settings API** - Custom database table for all plugin settings
- **HTTP API** - Safe redirects via `wp_safe_redirect()`
- **Nonce API** - CSRF protection on all forms
- **Capabilities API** - Permission checks via `current_user_can()`

## Security Implementation

### SQL Injection Prevention
- All database queries use `$wpdb->prepare()` with `%i` for table names (WordPress 6.2+)
- Zero direct SQL string concatenation
- Defensive table existence checks before all queries

### XSS Prevention
- All output escaped with appropriate functions:
  - `esc_html()` for text content
  - `esc_attr()` for HTML attributes
  - `esc_url()` for URLs
  - `esc_textarea()` for textarea content
  - `wp_strip_all_tags()` for user-submitted CSS/JS

### CSRF Protection
- WordPress nonces on all forms
- Nonce verification before processing with `wp_verify_nonce()`
- Unique nonce actions per form

### Input Validation
- All POST data sanitized with appropriate functions
- URL validation with `esc_url_raw()`
- Text sanitization with `sanitize_text_field()`
- Array sanitization with `array_map()`
- IP address validation and sanitization

### Capability Checks
- `manage_options` required for all admin operations
- Double verification in both render and save methods
- Checks even if user appears to have access

### Password Security
- No password storage (authentication handled by WordPress core)
- No custom authentication mechanisms
- Respects WordPress security practices

## Performance Optimizations

### Lazy Loading
- Settings loaded only when needed (not in constructors)
- Prevents unnecessary database queries
- Settings cached in memory during request

### Conditional Asset Loading
- Login CSS/JS only loaded on wp-login.php
- Admin CSS/JS only loaded on plugin settings page
- No global asset loading

### Database Optimization
- Custom table prevents wp_options bloat
- All queries implement object caching
- Defensive checks avoid repeated failed queries
- Indexed columns for fast lookups

### Caching Strategy
- Settings cached in class properties
- No repeated database queries per request
- Cache cleared automatically when settings change

## Common Use Cases

### Agency/Client Sites
- Brand the login page with client logo and colors
- Hide WordPress branding completely
- Create professional, custom authentication experience

### Membership Sites
- Role-based login redirects to member areas
- Custom logout redirects to landing pages
- Hide login errors for security

### E-commerce Sites
- Redirect customers to account dashboard after login
- Custom styling to match store branding
- Seamless checkout experience

### Development/Staging
- Use IP allowlist for developer access
- Role exceptions for admin testing
- Quick enable/disable toggle for testing

### High-Security Sites
- Hide all login error messages
- Disable unnecessary form elements
- Session expire control for security

## Compatibility

- **WordPress:** 6.8+ required
- **PHP:** 7.4+ required
- **MySQL:** 5.6+ or MariaDB 10.0+
- **Multisite:** Compatible (per-site settings)
- **Caching Plugins:** Compatible
- **Security Plugins:** Compatible with Wordfence, Limit Login Attempts, Fail2ban
- **Authentication Plugins:** Works alongside most auth plugins
- **Page Builders:** Not applicable (login page only)

## Frequently Asked Questions

**Q: Will this affect my site's security?**  
A: The plugin enhances security by allowing you to hide detailed error messages and control access. All code follows WordPress security best practices.

**Q: Can I use my own logo?**  
A: Yes! Enter the direct URL to your logo image in the Styling tab. Recommended size is 84x84 pixels.

**Q: Will my custom CSS override the dark theme?**  
A: Yes. Your custom CSS is injected after the default theme, so it will override any conflicting styles.

**Q: Can I disable customizations for specific users?**  
A: Yes. Use the Role Exceptions setting to bypass customizations for specific user roles, or use the IP Allowlist for specific IP addresses.

**Q: Does this work with two-factor authentication plugins?**  
A: Yes. The plugin only customizes the visual appearance and basic behavior. It doesn't interfere with authentication mechanisms.

**Q: Can I see a preview before saving?**  
A: Save your settings, then open an incognito/private window and visit your login page to preview changes.

**Q: Will this slow down my site?**  
A: No. Assets only load on the login page, and all settings use lazy loading and caching for optimal performance.

**Q: Can I revert to the default WordPress login?**  
A: Yes. Simply uncheck "Enable Plugin" in the General tab or deactivate the plugin.

**Q: Does this work with WooCommerce?**  
A: Yes, but WooCommerce has its own account pages. This plugin only affects wp-login.php.

**Q: Can I use this on WordPress multisite?**  
A: Yes. Each site in the network has its own independent settings.

## Changelog

### Version 1.0.0
- Initial release
- Cyberpunk dark theme with animated effects
- Custom CSS and JavaScript injection
- Custom logo and logo link
- Hide login error messages
- Custom error message text
- Login redirects (role-based)
- Logout redirects
- Skip logout confirmation
- Session expire modal control
- Disable language switcher
- Disable privacy link
- Disable back to site link
- Role-based exceptions
- IP allowlist for bypass
- Custom database table implementation
- Object caching for performance
- Comprehensive security implementation
- Tabbed admin interface under Appearance menu
- Mobile responsive design
- Optional cleanup on uninstall

## License

This plugin is licensed under GPL v2 or later.

---

**Plugin Version:** 1.0.0  
**Requires WordPress:** 6.8+  
**Requires PHP:** 7.4+  
**License:** GPL v2 or later
