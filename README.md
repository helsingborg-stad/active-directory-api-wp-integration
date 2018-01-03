# Active Directory Integration

Integration with the simple active directory api service (https://github.com/helsingborg-stad/active-directory-api). Simply define AD_INTEGRATION_URL with the url to the base directory of the api. The following functions will be enabled by default: 

- Only allow local WordPress users to login (their usernames must match those in active directory)
- Use the password stored in active-directory for matching WordPress users. 
- Update of users basic data like email, first name and last name on login. 

# Trigger manual bulkimport
You can trigger a manual bulkimport. This will directly call the bulkimport function in your current call. Profile updates will be sheduled every minute following, until all WordPress users has been updated. 

Import new users / remove old: https://site.dev/wp-admin/?adbulkimport
Update registered profiles: https://site.dev/wp-admin/?adbulkprofile
Propagate user roles to all sites: https://site.dev/wp-admin/?adbulkpropagate

# Trigger manual cleaning actions 
Cleaning actions to keep WordPress tables clean in some installations that are using bad object cache-engines.

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

# Options Bulk Import (Define Constants)
- AD_BULK_IMPORT: Turn on or off bulk import (true/false)
- AD_BULK_IMPORT_USER: User account that can read all items in the ad
- AD_BULK_IMPORT_PASSWORD: Password to the account name above
- AD_BULK_IMPORT_ROLE: Default role to assign new users (default to "subscriber")
- AD_BULK_IMPORT_REASSIGN_USERNAME: Reassign content of deleted users to this username. Will fallback to first user if not set or the user is missing. 
- AD_BULK_IMPORT_PROPAGATE: Propagate users on the whole network of blogs (default to true). 

* Be careful setting these options. All of them are not compatible. For instance: You cannot save the password, and generate a random password.
** Ad upodate meta should be set in order to enable ad_meta_prefix constant. 

# Filters 
Filter the meta keys stored in the database. 
```php
add_filter('adApiWpIntegration/profile/metaKey', function($meta_key){
    return $meta_key; 
}); 
```

Filter the default redirect to page, subscribers may not beaffected to this (to homepage as default)
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
