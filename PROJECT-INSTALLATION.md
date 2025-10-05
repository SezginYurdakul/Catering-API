## **Method 1: Using Local Development Tools (XAMPP, MAMP, or WAMP)**

1. **Set up a local development environment**:  
   Install a local development suite that includes PHP, MySQL/MariaDB, and a web server. The following options are recommended:
   - [XAMPP](https://www.apachefriends.org) (recommended)
   - [MAMP](https://www.mamp.info/en)
   - [WAMP](https://www.wampserver.com/en) (Windows only)

2. **Install Composer**:  
   Download and install the PHP package manager [Composer](https://getcomposer.org/). This is required to manage the dependencies of the project.

3. **Place the project in the web server directory**:  
   - Download the project folder `Catering-API` from the repository.
   - Move the folder into the `htdocs` directory of your web server (e.g., `C:\xampp\htdocs` for XAMPP).

4. **Install dependencies**:  
   Open a terminal, navigate to the project folder, and run the following command:
   ```bash
   composer install
   ```
   This will install all the required PHP dependencies for the project.

4.1 **Configure environment**:  
   Copy the example environment file and configure it:
   ```bash
   cp .env.example .env
   ```
   
   Edit the `.env` file with your local settings:
   ```properties
   DB_HOST=localhost
   DB_DATABASE=catering_db
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   JWT_SECRET_KEY=your_secure_jwt_key
   LOGIN_USERNAME=your_admin_username
   LOGIN_PASSWORD=your_hashed_password
   ```

6. **Setup the database**:  
   Use the new database management system for easy setup:
   ```bash
   # Navigate to project directory
   cd /path/to/Catering-API
   
   # Complete database setup (creates database, tables, and sample data)
   php sql/database_manager.php setup
   
   # Check status
   php sql/database_manager.php status
   ```
   
   **Alternative**: If you prefer manual setup:
   - Open **phpMyAdmin** (or any MySQL client).
   - Create a new database named `catering_db`.
   - Run the SQL files in order: `01_create_database.sql`, `02_create_tables.sql`, `03_seed_tables.sql`

Your project should now be ready to use! Open your browser and navigate to `http://localhost/Catering-API/public` to access the API.

---

## **Method 2: Using Docker**

1. **Install Docker**:  
   Ensure Docker is installed on your system. If not, follow the installation guide for your operating system:  
   [Docker Installation Guide](https://docs.docker.com/manuals/).

2. **Download the project**:  
   Clone or download the project folder `Catering-API` from the repository.

3.1 **Prepare the .env file**:  
   In the root directory of the project, copy the example environment file and configure it:
   ```bash
   cp .env.example .env
   ```
   
   Then edit the `.env` file with your actual database credentials:
   ```properties
   DB_HOST=db
   DB_DATABASE=catering_db
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   JWT_SECRET_KEY=your_secure_jwt_key
   LOGIN_USERNAME=your_admin_username
   LOGIN_PASSWORD=your_hashed_password
   ```
   
   **⚠️ Security Note**: Never commit the `.env` file to version control. Use strong passwords and generate a secure JWT secret key.



4. **Start Docker containers**:  
   In the root directory of the project, run the following command in the terminal:
   ```bash
   docker compose up
   ```
   This will create and start three containers:
   - A container for the MySQL database.
   - A container for the PHP application.
   - A container for the web server.

5. **Setup the database**:  
   Use the new database management system:
   ```bash
   # Complete database setup (creates database, tables, and sample data)
   docker exec catering_api_app php sql/database_manager.php setup
   
   # Check status
   docker exec catering_api_app php sql/database_manager.php status
   ```
   
   **Alternative manual approach**:
   - Access the MySQL container using the Docker CLI or a MySQL client.
   - Create a new database named `catering_db`.
   - Run the SQL files in order from the `sql/` directory.

Your project should now be ready to use! Open your browser and navigate to `http://localhost:8080` to access the API.

---

## **Additional Notes**

- **Database Management**:  
  The project includes a comprehensive database management system. See `sql/README.md` for detailed information about available commands:
  - `setup`: Complete fresh installation
  - `reset`: Clear and reload data (development)
  - `status`: Check database state
  - `clear-data`: Remove data but keep structure
  - `drop-tables`: Remove all tables (dangerous!)

- **Environment Variables**:  
  Ensure the .env file is correctly configured for both methods. For Docker, `DB_HOST` should be set to `db`, while for local development tools like XAMPP, it should be set to `localhost`.

- **Security Best Practices**:  
  - Never commit the `.env` file to version control
  - Use strong, unique passwords for database and admin accounts
  - Generate a secure JWT secret key (minimum 256-bit)
  - The provided `.env.example` is for reference only - always use your own credentials

- **Database Access**:  
  If you encounter issues accessing the database, verify the credentials in the .env file and ensure the database service is running.

- **Docker Tips**:  
  - Use `docker ps` to check the status of running containers.
  - Use `docker exec -it <container_name> bash` to access a container's shell.

- **Testing the API**:  
  Use tools like [Postman](https://www.postman.com/) or [cURL](https://curl.se/) to test the API endpoints.

---

By following these steps, you can set up the project using either local development tools or Docker. If you have any questions or encounter issues, feel free to reach out! 😊