# Active Directory Integration

Integration with the simple active directory api service (https://github.com/helsingborg-stad/active-directory-api). Simply define AD_INTEGRATION_URL with the url to the base directory of the api. The following functions will be enabled by default: 

- Only allow local WordPress users to login (their usernames must match those in active directory)
- Use the password stored in active-directory for matching WordPress users. 
- Update of users basic data like email, first name and last name on login. 
