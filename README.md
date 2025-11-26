## API Documentation: Catering API
### Important None:
You can test the API directly using my Postman collection:
click to open => https://solar-resonance-278359.postman.co/workspace/Team-Workspace~1a2d800b-458f-49ba-8fa2-db0eb7f4301e/collection/22299640-f0d40563-5130-4491-b0b8-5ddf6ada84a9?action=share&creator=22299640
### Description
This API is designed to manage backend operations for a catering service. It provides endpoints for managing facilities, locations, and tags, including CRUD operations, search functionality, and pagination support.

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose OR
- PHP 8.3+, MySQL 8.0+, Composer

### Installation

**Using Docker (Recommended):**
```bash
# Clone and navigate to project
cd Catering-API

# Copy environment file
cp .env.example .env

# Start containers
docker-compose up -d

# Setup database with sample data
docker exec catering_api_app php sql/database_manager.php setup

# Verify installation
curl http://localhost:8080/health
```

**Using Local Development (XAMPP/MAMP):**
```bash
# Install dependencies
composer install

# Copy and configure .env
cp .env.example .env

# Setup database
php sql/database_manager.php setup

# Verify installation
curl http://localhost/Catering-API/public/health
```

📚 **Detailed Installation Guide:** [docs/PROJECT-INSTALLATION.md](docs/PROJECT-INSTALLATION.md)

---

## 📖 Documentation

| Document | Description |
|----------|-------------|
| [API Documentation](#api-endpoints) | Complete API endpoint reference |
| [Installation Guide](docs/PROJECT-INSTALLATION.md) | Detailed setup instructions |
| [Testing Guide](docs/TEST_COMMANDS.md) | How to run tests |
| [Unit Test Strategy](docs/UNIT_TEST_STRATEGY.md) | Testing best practices |
| [Refactoring Log](docs/REFACTORING.md) | Code improvements history |
| [Project Structure](docs/PROJECT_STRUCTURE.md) | Codebase organization |
| [Database Management](sql/README.md) | Database operations |

---

## 🔐 Authentication

The API uses JWT (JSON Web Token) authentication.

### 1. Configure Environment (.env)
```env
# Generate JWT secret: php -r "echo base64_encode(random_bytes(32));"
JWT_SECRET_KEY=your_secure_jwt_secret

# Hash password: php -r "echo password_hash('yourpass', PASSWORD_BCRYPT);"
LOGIN_USERNAME=admin
LOGIN_PASSWORD=$2y$10$hashed_password_here
```

### 2. Login
```bash
curl -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username": "admin", "password": "yourpass"}'
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### 3. Use Token
```bash
curl http://localhost:8080/facilities \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

## 🛠️ API Endpoints

### Base URLs
- **Docker:** `http://localhost:8080`
- **Local:** `http://localhost/Catering-API/public`

### Health Check
- `GET /health` - API health status (no auth required)

### Authentication
- `POST /auth/login` - Get JWT token

### Facilities
- `GET /facilities` - List all facilities (pagination supported)
- `GET /facilities/{id}` - Get facility by ID
- `GET /facilities/search` - Search facilities with filters
- `POST /facilities` - Create new facility
- `PUT /facilities/{id}` - Update facility
- `DELETE /facilities/{id}` - Delete facility

### Locations
- `GET /locations` - List all locations
- `GET /locations/{id}` - Get location by ID
- `POST /locations` - Create new location
- `PUT /locations/{id}` - Update location
- `DELETE /locations/{id}` - Delete location

### Tags
- `GET /tags` - List all tags
- `GET /tags/{id}` - Get tag by ID
- `POST /tags` - Create new tag
- `PUT /tags/{id}` - Update tag
- `DELETE /tags/{id}` - Delete tag

### Employees
- `GET /employees` - List all employees
- `GET /employees/{id}` - Get employee by ID
- `GET /employees/facility/{facility_id}` - Get employees by facility
- `POST /employees` - Create new employee
- `PUT /employees/{id}` - Update employee
- `DELETE /employees/{id}` - Delete employee

---

## 📝 API Examples

### Create Facility
```bash
curl -X POST http://localhost:8080/facilities \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "Grand Conference Hall",
    "location_id": 1,
    "tagIds": [1, 2],
    "tagNames": ["Wedding", "Corporate"]
  }'
```

### Search Facilities
```bash
# Search with filters and AND operator
curl "http://localhost:8080/facilities/search?query=Conference&filter=city,tag&operator=AND" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Pagination
```bash
curl "http://localhost:8080/facilities?page=1&per_page=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 🧪 Testing

```bash
# Docker
docker-compose exec app vendor/bin/phpunit
docker-compose exec app vendor/bin/phpunit --testdox

# Local
vendor/bin/phpunit
composer test-unit

# With coverage
composer test-coverage
```

**Current Status:** 166/166 tests passing (100%) ✅

📚 **Complete Testing Guide:** [docs/TEST_COMMANDS.md](docs/TEST_COMMANDS.md)

---

## 🔧 Database Management

```bash
# Docker
docker exec catering_api_app php sql/database_manager.php [command]

# Local
php sql/database_manager.php [command]
```

**Available Commands:**
- `setup` - Fresh installation with sample data
- `status` - Check database state
- `reset` - Clear and reload data (dev only)
- `clear-data` - Remove data, keep structure
- `drop-tables` - Remove all tables (dangerous!)

📚 **Details:** [sql/README.md](sql/README.md)

---

## 📊 Sample Data

After running `setup`, you get:
- ✅ 25 locations (Dutch cities)
- ✅ 25 catering facilities
- ✅ 7 category tags
- ✅ Realistic relationships

---

## 🐳 Docker Services

| Service | Container | Port | Purpose |
|---------|-----------|------|---------|
| Nginx | `catering_api_nginx` | 80, 443 | Web server |
| PHP-FPM | `catering_api_app` | 8080 | Application |
| MySQL | `catering_api_db` | 3307 | Database |
| phpMyAdmin | `catering_phpmyadmin` | 8081 | DB management |

**Useful Commands:**
```bash
# View logs
docker-compose logs -f app

# Access shell
docker-compose exec app bash

# Stop containers
docker-compose down

# Restart
docker-compose restart
```

---

## 📦 Project Structure

```
Catering-API/
├── App/
│   ├── Controllers/      # HTTP request handlers
│   ├── Services/         # Business logic
│   ├── Repositories/     # Data access layer
│   ├── Models/          # Domain models
│   ├── Domain/
│   │   └── Exceptions/  # Custom exceptions
│   ├── Helpers/         # Utility classes
│   ├── Middleware/      # JWT authentication
│   └── Plugins/         # HTTP, DI, Database
├── docs/                # Documentation
├── sql/                 # Database scripts
├── tests/               # PHPUnit tests
└── public/              # Entry point
```

📚 **Detailed Structure:** [docs/PROJECT_STRUCTURE.md](docs/PROJECT_STRUCTURE.md)

---

## ⚡ Key Features

- ✅ RESTful API design
- ✅ JWT authentication
- ✅ Domain-driven exception handling
- ✅ Comprehensive input validation
- ✅ Security-focused logging (sensitive data sanitization)
- ✅ Pagination support
- ✅ Advanced search and filtering
- ✅ Docker containerization
- ✅ 100% unit test coverage (166 tests)
- ✅ PSR-4 autoloading

---

## 🔒 Security Features

- JWT token-based authentication
- Password hashing (bcrypt)
- Input sanitization (XSS protection)
- SQL injection prevention (prepared statements)
- Sensitive data sanitization in logs
- HTTPS support (production ready)

---

## 🛡️ Error Handling

The API uses standard HTTP status codes:

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request (validation errors) |
| 401 | Unauthorized (invalid/missing token) |
| 404 | Not Found |
| 409 | Conflict (resource in use) |
| 422 | Unprocessable Entity (business rule violation) |
| 500 | Internal Server Error |

**Example Error Response:**
```json
{
  "error": "Facility with ID 999 not found"
}
```

---

## 🤝 Contributing

1. Write tests for new features
2. Follow PSR-12 coding standards
3. Update documentation
4. Run tests before committing

---

## 📄 License

This project is open source and available for educational purposes.

---

## 📞 Support
For issues or questions, please refer to the documentation in the `docs/` directory.
