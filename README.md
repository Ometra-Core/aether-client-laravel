# Aether Client
Aether Client is a Laravel package that allows any client application to easily connect and interact with the Aether Server. With this package, you can manage "actions" and "realms" directly from your Laravel application's command line.

## Installation
Install this package in your Laravel project via Composer:

 ```bash
composer require ometra/aether-client:dev-main
  ```

## Enviroment Setup 
Befor using the package, add the following variables to your .env file to connect with Aether Server:

```bash
AETHER_BASE_URL=      # URL of your Aether server
AETHER_REALM_ID=      # Realm ID for your application
AETHER_TOKEN=         # Authentication token
AETHER_LOG_LEV=       # Log level (e.g., debug, info, error)
 ```

## Available Artisan Commands
Each command should be executed from your project root using php artisan. Below you’ll find the main commands provided by the package, grouped by functionality.
  
### Actions
 - ***List actions***:
Displays all actions configured for the application.

 ```bash
  php artisan aether:actions
 ```
 
 - ***Create a new action***:
Launches an interactive wizard for creating a new action, including name, description, and frequency.

 ```bash
 php artisan aether:create-action
 ```
 - ***Update an existing action***:
 Select and modify any registered action.

 ```bash
 php artisan aether:update-action
 ```
- ***Delete an action***:
Shows all registered actions and lets you select one to delete from your server.

 ```bash 
 php artisan aether:delete-action
 ```

- ***Update the configuration of an action that belongs to a realm***:   
Allows updating the relationships and settings between actions and realms. 

 ```bash 
 php artisan aether:update-action-realm
 ```

### Realms

- ***List realms***:
Shows all realms registered for your application. 

 ```bash
 php artisan aether:realms
 ```
- ***Create a new realm***:
Interactive wizard for registering a new realm.
 ```bash
 php artisan aether:create-realm
 ```

- ***Update a realm***:
  Select a realm to rename it and/or add new action. 

 ```bash
 php artisan aether:update-realm 
 ```

### Monitoring
 Sends a specific event (e.g., completion, error, etc.) to the Aether server.
 ```bash
   php artisan aether:report {action} {status?}
 ```

🔹 <span style="color: #5DADE2; font-weight: bold;">action (required)</span>: Name of the action to report  
🔹 <span style="color: #5DADE2; font-weight: bold;">status (optional)</span>: Status or extra message

### Aditional information 

> **Note**: By default, every application includes a special heartbeat action to check that the environment is running properly.
