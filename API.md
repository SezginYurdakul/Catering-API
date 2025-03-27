## API Documentation: Catering API

### Description
This API is designed to manage backend operations for a catering service. It provides endpoints for managing facilities, locations, and tags, including CRUD operations, search functionality, and pagination support.

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

### Usage
- **Base URL**: `http://localhost/web_backend_test_catering_api`
- **Authentication**: No authentication is required for this API.
- **Content-Type**: All requests should include the header `Content-Type: application/json`.

### Example Endpoints
#### Facilities
- **GET** `/facilities?page=1&per_page=5`: List facilities with pagination.
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

### Authentication
To access protected endpoints, you must first authenticate and obtain a JWT token.

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