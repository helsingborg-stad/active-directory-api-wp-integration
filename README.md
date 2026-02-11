# Active Directory Integration

Integration with the simple active directory api service (https://github.com/helsingborg-stad/active-directory-api). Simply define AD_INTEGRATION_URL with the url to the base directory of the api. The following functions will be enabled by default: 

- Only allow local WordPress users to login (their usernames must match those in active directory)
- Use the password stored in active-directory for matching WordPress users. 
- Update of users basic data like email, first name and last name on login. 

# Database modifications
This plugin will alter the index of the user table to require unique usernames. This is not a default behavior for WordPress and may break other plugins related to user management. 

# WordPress Admin User Actions
When bulk import is enabled, the plugin adds an "Update from AD" action to each user in the WordPress admin user list (Users → All Users). This allows administrators to manually update individual user profiles from Active Directory with a single click.

**How to use:**
1. Navigate to Users → All Users in WordPress admin
2. Hover over a user row
3. Click "Update from AD" link
4. The user's profile will be updated with the latest information from Active Directory
5. A success or error message will be displayed

# WP-CLI Commands
The plugin provides WP-CLI commands for managing bulk operations with clear logging. This is the recommended approach for production environments.

## User Synchronization Commands

### Sync users with Active Directory
Imports new users from AD and removes users that no longer exist:
```bash
wp adintegration sync
wp adintegration sync --max-delete-limit=500
```

### Update user profiles
Updates all WordPress user profiles with latest data from AD:
```bash
wp adintegration update-profiles
```

### Propagate users (Multisite)
Adds all users to all sites in the network:
```bash
wp adintegration propagate
```

## Individual User Management (CRUD Operations)

Manage individual users with get, update, and delete operations.

### Get user information from Active Directory
Retrieves user details from AD and shows WordPress status:
```bash
wp adintegration user get john.doe
wp adintegration user get john.doe@example.com
```

### Update user profile from Active Directory
Updates an existing WordPress user's profile from AD, or creates the user if `--create` flag is used:
```bash
# Update existing user
wp adintegration user update john.doe

# Create user if not exists and update profile
wp adintegration user update john.doe --create
```

### Delete user from WordPress
Removes a user from WordPress (requires confirmation):
```bash
wp adintegration user delete john.doe
```

## Cleaning Commands

### Remove duplicate users
```bash
wp adintegration clean-users
```

### Remove orphaned user metadata
```bash
wp adintegration clean-meta
```

### Remove empty user capabilities
```bash
wp adintegration clean-capabilities
```

# Trigger manual bulkimport (Legacy - Query Parameters)
**⚠️ Deprecated:** Query parameter triggers are maintained for backward compatibility but WP-CLI commands are recommended for better logging and monitoring.

Import new users / remove old: https://site.dev/wp-admin/?adbulkimport
Update registered profiles: https://site.dev/wp-admin/?adbulkprofile
Propagate user roles to all sites: https://site.dev/wp-admin/?adbulkpropagate

# Trigger manual cleaning actions (Legacy - Query Parameters)
**⚠️ Deprecated:** Query parameter triggers are maintained for backward compatibility but WP-CLI commands are recommended.

Remove duplicate users: https://site.dev/wp-admin/?adcleanusers
Remove orphan user meta: https://site.dev/wp-admin/?adcleanmeta
Remove user capabilitys that are empty: https://site.dev/wp-admin/?adcleancap

# Options (Define Constants)
- AD_UPDATE_NAME: Update first and last name. 
- AD_UPDATE_EMAIL: Update email if it not belongs to another user account. 
- AD_UPDATE_META: Update meta according to result (will use ad-keys as meta keys prefixed with below). **
- AD_META_PREFIX: Prefix for metakeys stored in the database. **
- AD_SAVE_PASSWORD: Wheter to save the ad-password (true) in WordPress. *
- AD_RANDOM_PASSWORD: Block random password generator. *
- AD_USER_DOMAIN: Define a domain that belongs to ad-users (to block password reset). *
- AD_HP_VALIDATION: Define to false to disable honeypot login protection.
- AD_NONCE_VALIDATION: Define to false to disable nonce login protection.

# Options Bulk Import (Define Constants)
These options imports all users avabile in active directory nightly. 

- AD_BULK_IMPORT: Turn on or off bulk import (true/false)
- AD_BULK_IMPORT_USER: User account that can read all items in the ad
- AD_BULK_IMPORT_PASSWORD: Password to the account name above
- AD_BULK_IMPORT_ROLE: Default role to assign new users (default to "subscriber")
- AD_BULK_IMPORT_REASSIGN_USERNAME: Reassign content of deleted users to this username. Will fallback to first user if not set or the user is missing. 
- AD_BULK_IMPORT_PROPAGATE: Propagate users on the whole network of blogs (default to true). 

# Options Auto Create Users
These options creates a user on the site if it exists in active directory on signon.  

- AD_AUTOCREATE_USER: Turn on or off auto signup (true/false)
- AD_AUTOCREATE_ROLE: Default role to assign new users (default to "subscriber")

* Be careful setting these options. All of them are not compatible. For instance: You cannot save the password, and generate a random password.
** Ad update meta should be set in order to enable ad_meta_prefix constant. 


# Filters 
Filter the meta keys stored in the database. 
```php
add_filter('adApiWpIntegration/profile/metaKey', function($meta_key){
    return $meta_key; 
}); 
```

Filter the default redirect to page, subscribers may not be affected to this (to homepage as default)
```php
add_filter('adApiWpIntegration/login/defaultRedirect', function(){
    return home_url();
}); 
```


# Example configuration 
```php
 define('AD_INTEGRATION_URL', 'https://internalproductionserver.com/ad-api/');
 define('AD_UPDATE_NAME', true);
 define('AD_UPDATE_EMAIL', true);
 define('AD_SAVE_PASSWORD', false);
 define('AD_RANDOM_PASSWORD', true);
 define('AD_USER_DOMAIN', 'company.com');

 define('AD_BULK_IMPORT', true);
 define('AD_BULK_IMPORT_USER', 'bulkimportaduser');
 define('AD_BULK_IMPORT_PASSWORD', '*********');
 define('AD_BULK_IMPORT_ROLE', 'subscriber');
 define('AD_BULK_IMPORT_REASSIGN_USERNAME', 'administrator');
```
