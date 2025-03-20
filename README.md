# README DTT BACK END ASSESSMENT #

## Local development environment setup
1. Set up a local development environment (PHP, MySQL/MariaDB and a web server). The following suites have all requirements bundled: 
   - [XAMPP](https://www.apachefriends.org) (recommended)
   - [MAMP](https://www.mamp.info/en)
   - [wamp](https://www.wampserver.com/en) (Windows only)
2. Install the PHP package manager [Composer](https://getcomposer.org/).
3. Put the project folder `web_backend_test_catering_api` in the `htdocs` folder of your web server.
4. Run the terminal command `composer install` in your project folder.

## Project setup
1. Create a database in [phpmyadmin](http://localhost/phpmyadmin) or use [MySQL Workbench](https://www.mysql.com/products/workbench/).
2. Fill in the config file `/config/config.php`:
    1. Set the database name.
    2. Set the username.
    3. Set the password.
3. Set your project's base path in `/routes/router.php` as follows:

```
$router->setBasePath('/web_backend_test_catering_api');
```

## Test the local development environment
1. Navigate to the project using a browser: `http://localhost/<project_folder>` (example: [http://localhost/web_backend_test_catering_api](http://localhost/web_backend_test_catering_api)). The page should print `Hello World!`
2. Import the included Postman collection in the Postman application.
3. Set the Postman collection variable `baseUrl` to your correct base URL
4. Run the Test API call within Postman. It should return `Hello World!`, same as in step 1 with the browser.

### Routing
The base setup uses an external [Router Plugin](https://github.com/bramus/router). The routes are registered in `/routes/routes.php`.

To register a route provide:

1. the path. Example: `/auth/login`
2. the controller and method. Example: `App\Controllers\AuthController@login`

### Database
The database is registered in the DI container. Among other database features, querying the database within a DiAware context (such as a controller) can be done by using `$this->db->executeQuery($query, $bind);`.

This will invoke the executeQuery method of the `App\Plugins\Db\Db` class.

