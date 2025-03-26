## **Method 1: Using Local Development Tools (XAMPP, MAMP, or WAMP)**

1. **Set up a local development environment**:  
   Install a local development suite that includes PHP, MySQL/MariaDB, and a web server. The following options are recommended:
   - [XAMPP](https://www.apachefriends.org) (recommended)
   - [MAMP](https://www.mamp.info/en)
   - [WAMP](https://www.wampserver.com/en) (Windows only)

2. **Install Composer**:  
   Download and install the PHP package manager [Composer](https://getcomposer.org/). This is required to manage the dependencies of the project.

3. **Place the project in the web server directory**:  
   - Download the project folder `web_backend_test_catering_api` from the repository.
   - Move the folder into the `htdocs` directory of your web server (e.g., `C:\xampp\htdocs` for XAMPP).

4. **Install dependencies**:  
   Open a terminal, navigate to the project folder, and run the following command:
   ```bash
   composer install
   ```
   This will install all the required PHP dependencies for the project.

5. **Create the database**:  
   - Open **phpMyAdmin** (or any MySQL client).
   - Create a new database named `catering_db`.

6. **Run the database setup script**:  
   - Navigate to the sql folder inside the project directory.
   - Run the following command in the terminal:
     ```bash
     php install.php
     ```
   This script will create the necessary tables and populate the database with sample data.

Your project should now be ready to use! Open your browser and navigate to `http://localhost/web_backend_test_catering_api` to access the API.

---

## **Method 2: Using Docker**

1. **Install Docker**:  
   Ensure Docker is installed on your system. If not, follow the installation guide for your operating system:  
   [Docker Installation Guide](https://docs.docker.com/manuals/).

2. **Download the project**:  
   Clone or download the project folder `web_backend_test_catering_api` from the repository.

3. **Prepare the .env file**:  
   In the root directory of the project, create a .env file with the following content:
   ```properties
   DB_HOST=db
   DB_DATABASE=catering_db
   DB_USERNAME=admin
   DB_PASSWORD=admin
   ```

4. **Start Docker containers**:  
   In the root directory of the project, run the following command in the terminal:
   ```bash
   docker compose up
   ```
   This will create and start three containers:
   - A container for the MySQL database.
   - A container for the PHP application.
   - A container for the web server.

5. **Create the database**:  
   - Access the MySQL container using the Docker CLI or a MySQL client.
   - Create a new database named `catering_db`.

6. **Run the database setup script**:  
   - Access the PHP container using the Docker CLI.
   - Navigate to the sql folder inside the project directory.
   - Run the following command:
     ```bash
     php install.php
     ```
   This script will create the necessary tables and populate the database with sample data.

Your project should now be ready to use! Open your browser and navigate to `http://localhost/web_backend_test_catering_api` to access the API.

---

## **Additional Notes**
- **Environment Variables**:  
  Ensure the .env file is correctly configured for both methods. For Docker, `DB_HOST` should be set to `db`, while for local development tools like XAMPP, it should be set to `localhost`.

- **Database Access**:  
  If you encounter issues accessing the database, verify the credentials in the .env file and ensure the database service is running.

- **Docker Tips**:  
  - Use `docker ps` to check the status of running containers.
  - Use `docker exec -it <container_name> bash` to access a container's shell.

- **Testing the API**:  
  Use tools like [Postman](https://www.postman.com/) or [cURL](https://curl.se/) to test the API endpoints.

---

By following these steps, you can set up the project using either local development tools or Docker. If you have any questions or encounter issues, feel free to reach out! 😊