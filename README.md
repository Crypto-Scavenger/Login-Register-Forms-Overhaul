# Login & Register Forms Overhaul

Complete control over WordPress login, registration, and logout flows with an invite-only registration system to combat spam bots.

## Description

This plugin transforms WordPress user authentication into a fully customizable, secure, and controlled experience. It provides comprehensive visual and behavioral control over all login-related flows while implementing an invite-code system to prevent unauthorized registrations.

## Features

### Visual Control
- **Custom Styles**: Apply custom CSS and JavaScript to login pages
- **Logo Customization**: Replace the WordPress logo with your own
- **Futuristic Cyberpunk Theme**: Built-in modern theme with glowing effects and animations
- **Responsive Design**: Optimized for both desktop and mobile devices
- **Theme Presets**: Built-in cyberpunk theme with customizable CSS editor

### Behavioral Control

#### Login Behavior
- Hide or replace login error messages (wrong password, unknown username)
- Prevent information disclosure through generic error messages
- Custom error message configuration
- Role-based and IP-based bypass options

#### Registration Behavior
- **Invite-Only System**: Require valid invite codes for registration
- **Remove Email Requirement**: Allow registration with username, password, and invite code only
- Completely disable registration when needed
- Hide registration validation messages
- Custom registration redirects

#### Logout Behavior
- Custom logout redirect URLs
- Override default logout confirmation pages
- Seamless logout experience

#### Session Management
- Hide session expiration modals
- Extended session duration
- Prevent unexpected logouts

### Invite Code System

#### Code Management
- Create individual codes manually or generate in bulk
- Set usage limits (1-999 uses per code)
- Optional expiry dates
- Assign specific user roles per code
- Automatic code deactivation upon exhaustion
- 24-hour grace period before deletion

#### Security Features
- **Hashed Storage**: Codes stored as SHA-256 hashes
- **Rate Limiting**: Configurable attempt limits and time windows
- **Usage Tracking**: Complete audit log of all attempts
- **IP Logging**: Track registration attempts by IP address
- **Statistics Dashboard**: Monitor successful and failed attempts

#### Notifications
- Email alerts when codes are exhausted
- Configurable notification recipient
- Detailed usage statistics

### Advanced Features

#### Role-Based Exceptions
- Allow specific roles to bypass all modifications
- Maintain normal WordPress experience for administrators
- Comma-separated role configuration

#### IP Allowlist
- Whitelist specific IP addresses
- Bypass restrictions for trusted networks
- One IP per line configuration

#### REST API Safe
- Does not expose authentication internals
- Compatible with headless WordPress setups

#### Integration
- Works alongside Wordfence, Limit Login Attempts, and Fail2ban
- Priority hooks for authentication plugins
- No conflicts with security plugins

## Installation

1. Upload the `login-register-forms-overhaul` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Users > Login & Register** to configure settings
4. Create invite codes in the Invite Codes tab
5. Enable the invite code requirement and customize your settings

## Usage

### Configuring Visual Styles

1. Go to **Users > Login & Register**
2. Click the **Visual Styles** tab
3. Enable custom styles
4. Upload a custom logo or enter a URL
5. Add custom CSS for further customization
6. Add custom JavaScript if needed
7. Click **Save Changes**

### Setting Up Behavior

1. Click the **Behavior** tab
2. Configure login behavior:
   - Enable hiding of login errors
   - Set custom error messages
3. Configure registration behavior:
   - Toggle registration disable
   - Hide registration errors
   - Remove email requirement
4. Configure logout behavior:
   - Set custom redirect URL
5. Configure session management:
   - Hide session expire modals
6. Click **Save Changes**

### Managing Invite Codes

#### Creating a Single Code

1. Click the **Invite Codes** tab
2. Scroll to "Create New Invite Code"
3. Enter a code (6-12 characters) or click "Generate Random"
4. Set usage limit (default: 1)
5. Optionally set expiry date
6. Select user role for registrants
7. Click **Create Code**

#### Bulk Generating Codes

1. Scroll to "Bulk Generate Codes"
2. Enter number of codes (max 1000)
3. Set prefix (default: INV)
4. Configure usage limit, expiry, and role
5. Click **Generate Codes**
6. Codes will be automatically created with format: PREFIX-RANDOM8

#### Managing Existing Codes

- View all codes in the table at the bottom
- See usage statistics: remaining uses, total uses, expiry, status
- Delete codes individually with the Delete button
- Inactive codes are marked in red

### Advanced Configuration

1. Click the **Advanced** tab
2. Configure role exceptions:
   - Enable role-based bypass
   - Enter role slugs (e.g., administrator,editor)
3. Configure IP allowlist:
   - Enable IP-based bypass
   - Enter IP addresses (one per line)
4. Configure cleanup:
   - Enable to remove all data on uninstall
5. Click **Save Changes**

### Using Invite Codes (User Perspective)

1. Navigate to the registration page
2. Enter desired username
3. Enter desired password
4. Enter valid invite code
5. Email is optional (if email requirement removed)
6. Submit registration
7. Account created with role specified by invite code

## File Structure

```
login-register-forms-overhaul/
├── login-register-forms-overhaul.php  # Main plugin file
├── README.md                          # This file
├── uninstall.php                      # Cleanup on deletion
├── index.php                          # Security stub
├── includes/
│   ├── class-database.php             # Database operations
│   ├── class-invite-codes.php         # Invite code management
│   ├── class-core.php                 # Core functionality
│   ├── class-admin.php                # Admin interface
│   └── index.php                      # Security stub
└── assets/
    ├── admin.css                      # Admin page styling
    ├── admin.js                       # Admin page JavaScript
    ├── login.css                      # Login page cyberpunk theme
    ├── login.js                       # Login page JavaScript
    └── index.php                      # Security stub
```

### File Descriptions

**login-register-forms-overhaul.php**
- Plugin header and metadata
- Constant definitions (version, paths, URLs)
- Class includes and initialization
- Activation hook registration

**includes/class-database.php**
- Custom database table creation and management
- Settings CRUD operations with caching
- Lazy loading for performance
- Three tables: settings, invite codes, code usage

**includes/class-invite-codes.php**
- Invite code creation and validation
- Bulk code generation
- Usage tracking and statistics
- Rate limiting implementation
- Email notifications
- SHA-256 code hashing

**includes/class-core.php**
- Login/registration/logout modifications
- Custom styles and scripts injection
- Session management
- Bypass logic for roles and IPs
- WordPress hook integration
- Email requirement removal

**includes/class-admin.php**
- Settings page with tabbed interface
- Code management interface
- Form handling and validation
- Media uploader integration
- Statistics display

**assets/admin.css**
- Clean, minimal admin styling
- Tabbed interface layout
- Responsive design for mobile
- WordPress admin theme consistency

**assets/admin.js**
- Media uploader functionality
- Random code generation
- Tab switching
- Form validation

**assets/login.css**
- Futuristic cyberpunk theme
- Glowing effects and animations
- Custom colors (#262626, #ffffff, #d11c1c)
- Responsive mobile design
- Font Awesome icon support

**assets/login.js**
- Session expire modal hiding
- Focus effects
- Form submission prevention
- Loading animations

**uninstall.php**
- Checks cleanup preference
- Drops database tables if enabled
- Clears scheduled tasks
- Flushes cache

## Database Schema

### lrfo_settings Table
```sql
- id (bigint, primary key)
- setting_key (varchar, unique)
- setting_value (longtext)
```

### lrfo_invite_codes Table
```sql
- id (bigint, primary key)
- code_string (varchar, unique, SHA-256 hash)
- usage_limit (int, 1-999)
- uses_remaining (int)
- total_uses (int)
- expiry_date (bigint, unix timestamp, nullable)
- user_role (varchar)
- created_at (datetime)
- is_active (tinyint)
```

### lrfo_code_usage Table
```sql
- id (bigint, primary key)
- code_id (bigint, foreign key)
- ip_address (varchar)
- attempt_type (varchar: success, invalid, exhausted, expired)
- user_id (bigint, nullable)
- attempted_at (datetime)
```

## Security Features

### Input Validation
- All user inputs validated and sanitized
- Nonce verification on all forms
- Capability checks on all admin actions
- SQL injection prevention via prepared statements

### Code Storage
- Invite codes hashed with SHA-256
- Case-insensitive validation
- Secure comparison functions

### Rate Limiting
- Configurable attempts per time window
- IP-based tracking
- Automatic blocking of brute force attempts

### Output Escaping
- All output properly escaped
- Context-appropriate escaping functions
- No XSS vulnerabilities

### Access Control
- Role-based access checks
- IP allowlist support
- Bypass mechanisms for administrators

## Performance Optimization

- Settings cached in memory
- Lazy loading of settings
- Database query optimization
- Minimal HTTP requests
- Efficient asset loading
- Object caching support

## Privacy & GDPR Compliance

This plugin:
- Stores data locally in WordPress database
- Does not send data to external services
- Logs IP addresses for security purposes only
- Allows optional email-free registration
- Provides data cleanup on uninstall
- No cookies or tracking beyond WordPress core

## Compatibility

- **WordPress**: 6.8 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- Works with all modern themes
- Compatible with security plugins
- REST API compatible
- Multisite compatible

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Invite Codes Not Working
- Ensure "Require Invite Code" is enabled
- Check that codes are active and not expired
- Verify usage limit hasn't been reached
- Check rate limiting settings

### Custom Styles Not Applying
- Verify "Enable Custom Styles" is checked
- Clear browser cache
- Check for CSS syntax errors
- Ensure no theme conflicts

### Registration Still Requires Email
- Enable "Remove Email Requirement" setting
- Clear any caching plugins
- Test in incognito/private browsing mode

### Session Expire Modal Still Showing
- Enable "Hide Session Expire Modal"
- Clear browser cache
- Ensure no conflicting plugins

### Codes Being Deleted Too Soon
- Codes auto-delete 24 hours after exhaustion
- This is by design to keep database clean
- Export codes before they're deleted if needed

## Development

### Hooks Available

**Filters:**
- `lrfo_validate_invite_code` - Modify code validation logic
- `lrfo_code_user_role` - Change assigned user role
- `lrfo_bypass_check` - Custom bypass conditions

**Actions:**
- `lrfo_code_created` - After code creation
- `lrfo_code_used` - After successful code use
- `lrfo_code_exhausted` - When code reaches limit
- `lrfo_settings_saved` - After settings update

### Constants

```php
LRFO_VERSION    // Plugin version
LRFO_DIR        // Plugin directory path
LRFO_URL        // Plugin URL
```

## Changelog

### Version 1.0.0
- Initial release
- Visual customization system
- Behavioral controls for login/registration/logout
- Invite-only registration system
- Bulk code generation
- Usage statistics and tracking
- Rate limiting
- Email notifications
- Role-based exceptions
- IP allowlist
- Cyberpunk theme
- Mobile responsive design
- Custom database tables
- Clean uninstall option
- 
## License

GPL v2 or later

---

**Plugin Version:** 1.0.0  
**Requires WordPress:** 6.8+  
**Requires PHP:** 7.4+  
**Text Domain:** login-register-forms-overhaul
