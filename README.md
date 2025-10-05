## API Documentation: Catering API

### Description
This API is designed to manage backend operations for a catering service. It provides endpoints for managing facilities, locations, and tags, including CRUD operations, search functionality, and pagination support.

## 🚀 Quick Setup

### Database Setup
Before using the API, you need to set up the database. The project includes a comprehensive database management system:

```bash
# Complete fresh installation (creates database, tables, and sample data)
docker exec catering_api_app php sql/database_manager.php setup

# Check database status
docker exec catering_api_app php sql/database_manager.php status

# Reset data during development
docker exec catering_api_app php sql/database_manager.php reset
```

**What you get:**
- 25 sample locations (Dutch cities)
- 25 catering facilities
- 7 category tags
- Realistic facility-tag relationships

📚 **For detailed database management**: See [sql/README.md](sql/README.md)

---

**📝 Note**: In the examples below, replace the base URL according to your setup:
- **Local Development**: `http://localhost/Catering-API/public`  
- **Docker**: `http://localhost:8080`

**💡 Tip**: All cURL examples in this documentation use Docker URLs (`localhost:8080`). For local development, simply replace with `localhost/Catering-API/public`.

### Collection Contents
- **Facilities**:
  - Create, update, delete, and list facilities.
  - Search and filter facilities.
  - List facilities with pagination support.

- **Locations**:
  - Create, update, delete, and list locations.
  - Retrieve details of a specific location.
  - List locations with pagination support.

- **Tags**:
  - Create, update, delete, and list tags.
  - Retrieve details of a specific tag.
  - List tags with pagination support.

### **How to Use the Catering API**

This section explains how to interact with the Catering API, including authentication, request structure, and endpoint-specific behaviors.

---

### **Base URL**
All API requests should be made to the following base URL:

**For Local Development (XAMPP/MAMP):**
```
http://localhost/Catering-API/public
```

**For Docker:**
```
http://localhost:8080
```

### **Quick Test**
To verify that your API is running correctly, test the health check endpoint:

**For Local Development:**
```bash
curl http://localhost/Catering-API/public/health
```

**For Docker:**
```bash
curl http://localhost:8080/health
```

**Expected Response:**
```json
{
    "message": "Catering API is working!"
}
```

---

### **Authentication**
The Catering API uses **JWT (JSON Web Token)** for authentication. To access protected endpoints, you must first authenticate and obtain a JWT token.

#### **1. Login Endpoint**
- **Endpoint**: `POST /auth/login`
- **Request Body**:
  ```json
  {
      "username": "your_username",
      "password": "your_password"
  }
  ```
- **Response**:
  ```json
  {
      "token": "your_jwt_token"
  }
  ```
- Use the returned `token` in the `Authorization` header for all subsequent requests:
  ```
  Authorization: Bearer <your_jwt_token>
  ```

---

### **Request Headers**
All requests must include the following headers:
- `Content-Type: application/json`
- `Authorization: Bearer <your_jwt_token>` (for protected endpoints)

---

### **Pagination**
For endpoints that support pagination, you can use the following query parameters:
- `page`: Specifies the page number to retrieve (default: 1).
- `per_page`: Specifies the number of items per page (default: 10).

Example:
```
GET /facilities?page=1&per_page=5
```

---

### **Search and Filtering**
The `GET /facilities/search` endpoint allows searching and filtering facilities using query parameters. You can combine search terms, filters, and logical operators to refine your results.

---

#### **Query Parameters**

- **`query`**: A search term to match against facility records.
- **`filter`**: A comma-separated list of fields to filter by. Supported fields:
  - `city`: Filter by the city name.
  - `tag`: Filter by tag name.
  - `facility_name`: Filter by facility name.
- **`operator`**: Logical operator to combine filters. Supported values:
  - `AND`: All filter conditions must be met.
  - `OR`: At least one filter condition must be met (default: `OR`).

---

#### **Example Requests**

##### **1. Search by Query Only**
Search for facilities with the keyword "Conference":
```
GET /facilities/search?query=Conference
```

##### **2. Filter by Multiple Fields**
Filter facilities by `city`, `tag`, and `facility_name`:
```
GET /facilities/search?filter=city,tag,facility_name
```

##### **3. Combine Search and Filtering**
Search for facilities with the keyword "Conference" and filter by `city`, `tag`, and `facility_name`:
```
GET /facilities/search?query=Conference&filter=city,tag,facility_name
```

##### **4. Use Logical Operators**
Search for facilities with the keyword "Conference" and filter by `city`, `tag`, and `facility_name` using the `AND` operator:
```
GET /facilities/search?query=Conference&filter=city,tag,facility_name&operator=AND
```

Search for facilities with the keyword "Conference" and filter by `city`, `tag`, and `facility_name` using the `OR` operator:
```
GET /facilities/search?query=Conference&filter=city,tag,facility_name&operator=OR
```

---

#### **Behavior**

1. **`query` Parameter**:
   - Matches the search term against all relevant fields (e.g., `facility_name`, `tag`, `city`).

2. **`filter` Parameter**:
   - Specifies which fields to apply the filter to.
   - If no `filter` is provided, the search applies to all fields by default.

3. **`operator` Parameter**:
   - Determines how multiple filters are combined:
     - `AND`: All conditions must be true for a record to be included.
     - `OR`: At least one condition must be true for a record to be included (default behavior).

---

#### **Example Responses**

##### **Response for `GET /facilities/search?query=Conference&filter=city,tag,facility_name&operator=AND`**
```json
{
    "facilities": [
        {
            "id": 1,
            "name": "Conference Center",
            "creation_date": "2025-04-09 09:29:28",
            "tagIds": [
                { "id": 6, "name": "Conference" }
            ],
            "location": {
                "id": 1,
                "city": "Rotterdam",
                "address": "Coolsingel 10",
                "zip_code": "3012AD",
                "country_code": "NL",
                "phone_number": "+31-10-7654321"
            }
        }
    ],
    "pagination": {
        "total_items": 1,
        "current_page": 1,
        "per_page": 10,
        "total_pages": 1
    }
}
```

---

#### **Notes**
- If both `query` and `filter` are provided, the API will return results that match the search query and satisfy the filter conditions.
- If no `operator` is specified, the default behavior is `OR`.
- Filters are case-insensitive.

---

By using the `query`, `filter`, and `operator` parameters, you can perform advanced searches and retrieve facilities that meet your specific criteria.

---

### **Tag Management in Facilities**

#### **1. Creating Tags via Facility Creation**
When creating a facility, you can include the `tagNames` field in the request body. This field should be an array of strings. For each string:
- If the tag does not exist in the database, a new tag will be created.
- The created tags will be associated with the newly created facility.

**Example Request**:
```json
{
    "name": "Test Facility",
    "location_id": 1,
    "tagIds": [1, 2],
    "tagNames": ["Wedding", "Conference"]
}
```

**Behavior**:
- Tags with IDs `1` and `2` will be associated with the facility.
- New tags "Wedding" and "Conference" will be created (if they do not already exist) and associated with the facility.

---

#### **2. Creating Tags via Facility Update**
When updating a facility, you can include either `tagIds` or `tagNames` in the request body:
- **`tagIds`**: Updates the facility's tags to include the specified tag IDs.
- **`tagNames`**: Creates new tags for names not already in the database and associates them with the facility.

**Example Request**:
```json
{
    "name": "Updated Facility",
    "location_id": 2,
    "tagIds": [3, 4],
    "tagNames": ["Corporate Event", "Outdoor"]
}
```

**Behavior**:
- Tags with IDs `3` and `4` will be associated with the facility.
- New tags "Corporate Event" and "Outdoor" will be created (if they do not already exist) and associated with the facility.

---

### **Error Handling**
The API uses standard HTTP status codes to indicate the success or failure of a request. Below are some common status codes and their meanings:

- **200 OK**: The request was successful.
- **201 Created**: A new resource was successfully created.
- **204 No Content**: The request was successful, but there is no content to return.
- **400 Bad Request**: The request was invalid or missing required parameters.
- **401 Unauthorized**: Authentication failed or the token is missing/invalid.
- **404 Not Found**: The requested resource could not be found.
- **500 Internal Server Error**: An error occurred on the server.

**Example Error Response**:
```json
{
    "error": "Invalid location ID. It must be a positive integer."
}
```

---
### **Location Management**

The Catering API allows you to manage locations independently or associate them with facilities. Locations are created or updated through their own endpoints, and a valid `location_id` is required when creating or updating a facility.

---

#### **Endpoints for Location Management**

1. **Create Location**
   - **Endpoint**: `POST /locations`
   - **Description**: Create a new location.
   - **Request Body**:
     ```json
     {
         "city": "Amsterdam",
         "address": "Damrak 1",
         "zip_code": "1012AB",
         "country_code": "NL",
         "phone_number": "+31-20-1234567"
     }
     ```
   - **Example Request**:
     
     Docker:
     ```bash
     curl --location 'http://localhost:8080/locations' \
     --header 'Content-Type: application/json' \
     --header 'Authorization: Bearer <your_jwt_token>' \
     --data '{
         "city": "Amsterdam",
         "address": "Damrak 1",
         "zip_code": "1012AB",
         "country_code": "NL",
         "phone_number": "+31-20-1234567"
     }'
     ```
     
     Local Development:
     ```bash
     curl --location 'http://localhost/Catering-API/public/locations' \
     --header 'Content-Type: application/json' \
     --header 'Authorization: Bearer <your_jwt_token>' \
     --data '{
         "city": "Amsterdam",
         "address": "Damrak 1",
         "zip_code": "1012AB",
         "country_code": "NL",
         "phone_number": "+31-20-1234567"
     }'
     ```
   - **Response**:
     ```json
     {
         "message": "Location created successfully.",
         "location": {
             "id": 1,
             "city": "Amsterdam",
             "address": "Damrak 1",
             "zip_code": "1012AB",
             "country_code": "NL",
             "phone_number": "+31-20-1234567"
         }
     }
     ```

2. **Update Location**
   - **Endpoint**: `PUT /locations/{id}`
   - **Description**: Update an existing location.
   - **Request Body**:
     ```json
     {
         "city": "Rotterdam",
         "address": "Coolsingel 10",
         "zip_code": "3012AD",
         "country_code": "NL",
         "phone_number": "+31-10-7654321"
     }
     ```
   - **Example Request**:
     ```bash
     curl --location --request PUT 'http://localhost:8080/locations/1' \
     --header 'Content-Type: application/json' \
     --header 'Authorization: Bearer <your_jwt_token>' \
     --data '{
         "city": "Rotterdam",
         "address": "Coolsingel 10",
         "zip_code": "3012AD",
         "country_code": "NL",
         "phone_number": "+31-10-7654321"
     }'
     ```
   - **Response**:
     ```json
     {
         "message": "Location updated successfully.",
         "location": {
             "id": 1,
             "city": "Rotterdam",
             "address": "Coolsingel 10",
             "zip_code": "3012AD",
             "country_code": "NL",
             "phone_number": "+31-10-7654321"
         }
     }
     ```

3. **Retrieve Location**
   - **Endpoint**: `GET /locations/{id}`
   - **Description**: Retrieve details of a specific location.
   - **Example Request**:
     ```bash
     curl --location 'http://localhost:8080/locations/1' \
     --header 'Content-Type: application/json' \
     --header 'Authorization: Bearer <your_jwt_token>'
     ```
   - **Response**:
     ```json
     {
         "id": 1,
         "city": "Amsterdam",
         "address": "Damrak 1",
         "zip_code": "1012AB",
         "country_code": "NL",
         "phone_number": "+31-20-1234567"
     }
     ```

4. **List Locations**
   - **Endpoint**: `GET /locations`
   - **Description**: List all locations with optional pagination.
   - **Query Parameters**:
     - `page`: Specifies the page number to retrieve (default: 1).
     - `per_page`: Specifies the number of items per page (default: 10).
   - **Example Request**:
     ```bash
     curl --location 'http://localhost:8080/locations?page=1&per_page=5' \
     --header 'Content-Type: application/json' \
     --header 'Authorization: Bearer <your_jwt_token>'
     ```
   - **Response**:
     ```json
     {
         "locations": [
             {
                 "id": 1,
                 "city": "Amsterdam",
                 "address": "Damrak 1",
                 "zip_code": "1012AB",
                 "country_code": "NL",
                 "phone_number": "+31-20-1234567"
             },
             {
                 "id": 2,
                 "city": "Rotterdam",
                 "address": "Coolsingel 10",
                 "zip_code": "3012AD",
                 "country_code": "NL",
                 "phone_number": "+31-10-7654321"
             }
         ],
         "pagination": {
             "total_items": 2,
             "current_page": 1,
             "per_page": 5,
             "total_pages": 1
         }
     }
     ```

---

#### **Using Locations in Facility Management**

When creating or updating a facility, you must provide a valid `location_id` in the request body. This `location_id` must correspond to an existing location in the database.

1. **Create Facility with Location**
   - **Endpoint**: `POST /facilities`
   - **Request Body**:
     ```json
     {
         "name": "Test Facility",
         "location_id": 1,
         "tagIds": [1, 2],
         "tagNames": ["Wedding", "Conference"]
     }
     ```
   - **Behavior**:
     - The facility will be associated with the location specified by `location_id`.
     - If the `location_id` does not exist, the API will return a `400 Bad Request` error.

2. **Update Facility with Location**
   - **Endpoint**: `PUT /facilities/{id}`
   - **Request Body**:
     ```json
     {
         "name": "Updated Facility",
         "location_id": 2,
         "tagIds": [3, 4],
         "tagNames": ["Corporate Event", "Outdoor"]
     }
     ```
   - **Behavior**:
     - The facility's location will be updated to the location specified by `location_id`.
     - If the `location_id` does not exist, the API will return a `400 Bad Request` error.

---

#### **Error Handling for Locations**
- **400 Bad Request**: Returned if the `location_id` is missing or invalid.
  ```json
  {
      "error": "Invalid location ID. It must be a positive integer."
  }
  ```
- **404 Not Found**: Returned if the specified `location_id` does not exist.
  ```json
  {
      "error": "Location with ID 999 not found."
  }
  ```

---

By managing locations independently and associating them with facilities, you can ensure that your data remains consistent and well-structured.
### **Testing the API**
You can test the API using tools like:
- [Postman](https://www.postman.com/): A GUI-based API testing tool.
- [cURL](https://curl.se/): A command-line tool for making HTTP requests.

**Example cURL Command**:

For Local Development:
```bash
curl --location 'http://localhost/Catering-API/public/facilities' \
--header 'Content-Type: application/json' \
--header 'Authorization: Bearer <your_jwt_token>'
```

For Docker:
```bash
curl --location 'http://localhost:8080/facilities' \
--header 'Content-Type: application/json' \
--header 'Authorization: Bearer <your_jwt_token>'
```

---

By following these guidelines, you can effectively interact with the Catering API and manage facilities, locations, and tags. For further assistance, refer to the endpoint-specific documentation above.
### Endpoints
- **Base URL**: See setup instructions above (Local: `http://localhost/Catering-API/public` or Docker: `http://localhost:8080`)
- **Authentication**: JWT authentication is required for this API (except `/health` endpoint).
- **Content-Type**: All requests should include the header `Content-Type: application/json`.

### Example Endpoints
#### Health Check
- **GET** `/health`: Check if the API is running (no authentication required).

#### Authentication
- **POST** `/auth/login`: Login and get JWT token.

#### Facilities
- **GET** `/facilities?page=1&per_page=5`: List facilities with pagination.
- **GET** `/facilities/search?query=Conference&filter=city,tag&operator=AND`: Search facilities with advanced filters.
- **POST** `/facilities`: Create a new facility.
- **GET** `/facilities/{id}`: Retrieve details of a specific facility.
- **PUT** `/facilities/{id}`: Update a specific facility.
- **DELETE** `/facilities/{id}`: Delete a specific facility.

#### Locations
- **GET** `/locations?page=1&per_page=5`: List locations with pagination.
- **POST** `/locations`: Create a new location.
- **GET** `/locations/{id}`: Retrieve details of a specific location.
- **PUT** `/locations/{id}`: Update a specific location.
- **DELETE** `/locations/{id}`: Delete a specific location.

#### Tags
- **GET** `/tags?page=1&per_page=5`: List tags with pagination.
- **POST** `/tags`: Create a new tag.
- **GET** `/tags/{id}`: Retrieve details of a specific tag.
- **PUT** `/tags/{id}`: Update a specific tag.
- **DELETE** `/tags/{id}`: Delete a specific tag.




## **Authentication**

##

To access protected endpoints, you must first authenticate and obtain a JWT token. The authentication process requires configuration in both the .env file and config.php.



Environment Variables in .env

The following environment variables must be set in your `.env` file:

---

#### JWT Secret Key

```env
JWT_SECRET_KEY=your_secret_key
```

You can generate a secure JWT key using this PHP script:

```php
<?php
echo base64_encode(random_bytes(32));
```

---

#### Login Credentials

```env
LOGIN_USERNAME=your_username
LOGIN_PASSWORD=your_hashed_password
```

You can hash your password securely using this PHP snippet:

```php
<?php
$password = 'your_password_here';
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
echo $hashedPassword;
```

**Sample hashed password (output):**

```
$2y$10$eCwv...wUvdKZfKcV6o1OQWxYbG
```

#### **Environment Configuration in `config.php`**
The `config.php` file is responsible for loading and managing environment variables defined in the `.env` file. These variables are critical for configuring the application, including database connections, authentication, and JWT token management.

Here are the key environment variables used in `config.php`:

1. **Database Configuration**
   - The following variables are used to configure the database connection:
     ```php
     'db' => [
         'host' => $_ENV['DB_HOST'],
         'database' => $_ENV['DB_DATABASE'],
         'username' => $_ENV['DB_USERNAME'],
         'password' => $_ENV['DB_PASSWORD'],
     ],
     ```

2. **JWT Configuration**
   - The following variables are used for managing JWT tokens:
     ```php
     'jwt' => [
         'secret_key' => $_ENV['JWT_SECRET_KEY'],
     ],
     ```

3. **Authentication Configuration**
   - The following variables are used for basic authentication:
     ```php
     'auth' => [
         'username' => $_ENV['LOGIN_USERNAME'],
         'password' => $_ENV['LOGIN_PASSWORD'],
     ],

#### **Login Endpoint**
- **POST** `/auth/login`
- **Request Body**:
  ```json
  {
      "username": "your_username",
      "password": "your_password"
  }
  
### Notes
- **Pagination Parameters**:
  - `page`: Specifies the page number to retrieve (default: 1).
  - `per_page`: Specifies the number of items per page (default: 10).
- **Search and Filtering**:
  - Facilities can be searched and filtered using query parameters.