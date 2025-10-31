# Aether Client
## System Overview
This is the package that the user must install within the application in which they wish to use aether. For the client to be able to make requests, it must define the following environment variables:
```bash
AETHER_BASE_URL=
AETHER_REALM_ID=
AETHER_TOKEN=
AETHER_LOG_LEV=
 ```
 ## Package installation
 To integrate Aether into any system, you need to run the following code:
 ```bash
composer require ometra/aether-client:dev-main
  ```
> **Note: Each application includes a default **heartbeat** action, which is used to monitor that the environment is running.

  ## Console Commands
  Displays the full list of actions configured in the realm.
 ```bash
  php artisan aether:actions
 ```
 
Allows you to create a new action by specifying its name, description, and frequency.
 ```bash
 php artisan aether:create-action
 ```

Lets you select an existing action to update its details.
 ```bash
 php artisan aether:update-action
 ```
  
Shows all registered actions and lets you choose one to delete.
 ```bash 
 php artisan aether:delete-action
 ```
  
Displays the list of realms registered in the application.
 ```bash
 php artisan aether:realms
 ```
 
 Creates a new realm and optionally allows you to associate actions with it.
 ```bash
 php artisan aether:create-realm
 ```
 
  Lets you select an existing realm to rename it and optionally add more actions.
 ```bash
 php artisan aether:update-realm 
 ```

 Reports a specific action by providing its name.
 ```bash
 php artisan aether:report name_action
 ```
