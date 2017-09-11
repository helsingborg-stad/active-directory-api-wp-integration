# Active Directory Integration

Integration with the simple active directory api service (https://github.com/helsingborg-stad/active-directory-api). Simply define AD_INTEGRATION_URL with the url to the base directory of the api. The following functions will be enabled by default: 

- Only allow local WordPress users to login (their usernames must match those in active directory)
- Use the password stored in active-directory for matching WordPress users. 
- Update of users basic data like email, first name and last name on login. 

# Options (Define constants)
- AD_UPDATE_NAME: Update first and last name. 
- AD_UPDATE_EMAIL: Update email if it not belongs to another user account. 
- AD_SAVE_PASSWORD: Wheter to save the ad-password (true) in WordPress. *
- AD_RANDOM_PASSWORD: Block random password generator. *
- AD_USER_DOMAIN: Define a domain that belongs to ad-users (to block password reset). *

# Options Bulk import (Define constants)
- AD_BULK_IMPORT: Turn on or off bulk import (true/false)
- AD_BULK_IMPORT_USER: User account that can read all items in the ad
- AD_BULK_IMPORT_PASSWORD: Password to the account name above
- AD_BULK_IMPORT_ROLE: Default role to assign new users 
- AD_BULK_IMPORT_REASSIGN_USERNAME: Reassign content of deleted users to this username. Will fallback to first user if not set or the user is missing. 
- AD_BULK_IMPORT_PROPAGATE: Propagate users on the whole network of blogs (default to true). 

* Be careful setting these options. All of them are not compatible. For instance: You cannot save the password, and generate a random password.


