# Toko Kelontong API

A comprehensive API for managing a small grocery store (toko kelontong) with inventory management, cash transactions, and user authentication.

## Features

- **User Authentication** - JWT-based authentication for secure API access
- **Item Management** - Create, read, update, and delete store items with inventory tracking
- **Stock Movements** - Track all inventory movements (in/out)
- **Cash Transactions** - Record and manage cash flow and transactions
- **RESTful API** - Clean and intuitive REST endpoints
- **Docker Support** - Ready-to-deploy containerized application

## Tech Stack

**Backend:**
- **PHP** 8.3+
- **Laravel** 13.8 - Modern PHP framework
- **MySQL** 8.4 - Relational database

**Frontend Build:**
- **Vite** 8.0 - Next-generation frontend tooling
- **Tailwind CSS** 4.0 - Utility-first CSS framework

**DevOps:**
- **Docker** & **Docker Compose** - Container orchestration
- **Nginx** 1.27 - Web server

**Testing & Quality:**
- **Pest** 4.7 - PHP testing framework
- **Laravel Pint** - PHP code style fixer

## Prerequisites

### Local Development
- PHP 8.3 or higher
- Composer 2.0+
- Node.js 18+ with npm
- MySQL 8.0+ (or SQLite for quick setup)

### Docker
- Docker 20.10+
- Docker Compose 2.0+

## Installation

### Option 1: Local Development

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd toko-kelontong
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install --ignore-scripts
   ```

3. **Setup environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Setup database**
   ```bash
   php artisan migrate
   ```

5. **Build frontend assets**
   ```bash
   npm run build
   ```

6. **Or use the setup script**
   ```bash
   composer run setup
   ```

### Option 2: Docker

1. **Build and start containers**
   ```bash
   docker compose up -d --build
   ```

2. **Run migrations**
   ```bash
   docker compose exec app php artisan migrate --force
   ```

3. **Access the API**
   - API URL: `http://localhost:8000/api`
   - Health check: `http://localhost:8000/up`

## Development

### Run development server
```bash
composer run dev
```

This command starts:
- Laravel development server
- Queue listener
- Log monitoring (Pail)
- Vite dev server for frontend assets

### Build frontend
```bash
npm run build
```

### Run tests
```bash
php artisan test
```

Or with Pest:
```bash
./vendor/bin/pest
```

## API Documentation

### Authentication Endpoints

**Register a new user**
```
POST /api/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

**Login**
```
POST /api/auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "secret123"
}
```

**Get current user** (requires JWT token)
```
GET /api/auth/me
Authorization: Bearer <token>
```

**Logout** (requires JWT token)
```
POST /api/auth/logout
Authorization: Bearer <token>
```

### Items Management

**List items**
```
GET /api/items
Authorization: Bearer <token>
```

**Create item**
```
POST /api/items
Authorization: Bearer <token>
Content-Type: application/json

{
  "name": "Item Name",
  "sku": "SKU123",
  "price": 10000,
  "quantity": 100
}
```

**Get single item**
```
GET /api/items/{id}
Authorization: Bearer <token>
```

**Update item**
```
PUT /api/items/{id}
Authorization: Bearer <token>
```

**Delete item**
```
DELETE /api/items/{id}
Authorization: Bearer <token>
```

### Cash Transactions

**List transactions**
```
GET /api/cash-transactions
Authorization: Bearer <token>
```

**Create transaction**
```
POST /api/cash-transactions
Authorization: Bearer <token>
Content-Type: application/json

{
  "type": "in|out",
  "amount": 50000,
  "description": "Transaction description"
}
```

**Get single transaction**
```
GET /api/cash-transactions/{id}
Authorization: Bearer <token>
```

**Update transaction**
```
PUT /api/cash-transactions/{id}
Authorization: Bearer <token>
```

**Delete transaction**
```
DELETE /api/cash-transactions/{id}
Authorization: Bearer <token>
```

### Stock Movements

**List stock movements**
```
GET /api/stock-movements
Authorization: Bearer <token>
```

**Create stock movement**
```
POST /api/stock-movements
Authorization: Bearer <token>
Content-Type: application/json

{
  "item_id": 1,
  "type": "in|out",
  "quantity": 50,
  "notes": "Movement notes"
}
```

**Get single movement**
```
GET /api/stock-movements/{id}
Authorization: Bearer <token>
```

## Project Structure

```
toko-kelontong/
├── app/
│   ├── Http/
│   │   ├── Controllers/       # API controllers
│   │   └── Middleware/        # Custom middleware
│   ├── Models/                # Eloquent models
│   │   ├── Item.php
│   │   ├── CashTransaction.php
│   │   ├── StockMovement.php
│   │   └── User.php
│   └── Services/              # Business logic
│       └── JwtService.php
├── database/
│   ├── migrations/            # Database schemas
│   ├── seeders/               # Data seeders
│   └── factories/             # Model factories for testing
├── routes/
│   └── api.php                # API routes
├── tests/                     # Test suites
├── docker/                    # Docker configuration
│   ├── nginx/
│   └── php/
├── resources/                 # Frontend assets
│   ├── css/
│   └── js/
└── storage/                   # Application storage
    ├── app/
    ├── framework/
    └── logs/
```

## Database Schema

### Users Table
- id (Primary Key)
- name
- email (Unique)
- password
- email_verified_at
- remember_token
- created_at
- updated_at

### Items Table
- id (Primary Key)
- name
- sku (Unique)
- price
- quantity
- created_at
- updated_at

### Cash Transactions Table
- id (Primary Key)
- user_id (Foreign Key)
- type (in/out)
- amount
- description
- created_at
- updated_at

### Stock Movements Table
- id (Primary Key)
- item_id (Foreign Key)
- type (in/out)
- quantity
- notes
- created_at
- updated_at

## Docker Configuration

### Services
- **app** - Laravel PHP-FPM application
- **nginx** - Web server (port 8000)
- **mysql** - Database server (port 3307)

### Database Defaults
```
Database: toko_kelontong
Username: toko
Password: secret
Host: mysql
Port: 3306
```

Override these in `.env` file:
```
DOCKER_DB_USERNAME=your_username
DOCKER_DB_PASSWORD=your_password
DOCKER_DB_ROOT_PASSWORD=your_root_password
```

### Volumes
- `storage-data` - Application storage
- `cache-data` - Bootstrap cache

## Configuration

### JWT Configuration
Edit `config/jwt.php` to configure JWT token settings:
- Token expiration time
- Secret key
- Algorithm

### Cache & Sessions
- Cache Store: Database
- Queue Connection: Database
- Session Driver: Database

## Troubleshooting

### Database Connection Issues (Docker)
```bash
# Check MySQL health
docker compose ps

# View logs
docker compose logs mysql

# Exec into app container
docker compose exec app bash
```

### Permission Issues (Docker)
```bash
# Set proper permissions
docker compose exec app chmod -R 755 storage bootstrap/cache
```

### Reset Database
```bash
# In Docker
docker compose exec app php artisan migrate:refresh

# Locally
php artisan migrate:refresh
```

## Testing

Run the test suite:
```bash
./vendor/bin/pest

# With coverage
./vendor/bin/pest --coverage
```

## API Testing

Use the included Postman collection for testing:
```
postman/toko-kelontong-api.postman_collection.json
```

Import this file into Postman to get pre-configured requests.

## Code Quality

Run Laravel Pint for code formatting:
```bash
./vendor/bin/pint
```

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For issues, questions, or contributions, please open an issue or pull request in the repository.

---

**Last Updated:** June 2, 2026
