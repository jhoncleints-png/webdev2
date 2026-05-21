# Samaco Brewery Management System

A comprehensive brewery management system built with Symfony 6, featuring a web admin panel and RESTful API for mobile app integration.

## 📋 Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Running the Application](#running-the-application)
- [API Documentation](#api-documentation)
- [Database Schema](#database-schema)
- [User Roles](#user-roles)
- [Testing](#testing)
- [Deployment](#deployment)
- [Project Structure](#project-structure)
- [Troubleshooting](#troubleshooting)

## ✨ Features

- **User Authentication**
  - JWT-based authentication for API
  - Google OAuth integration
  - Email verification
  - Password reset functionality

- **Role-Based Access Control (RBAC)**
  - Customer (ROLE_USER)
  - Staff (ROLE_STAFF)
  - Admin (ROLE_ADMIN)

- **Product Management**
  - CRUD operations for products
  - Category management
  - Stock tracking with low-stock alerts
  - Product search and filtering

- **Order Management**
  - Order creation and tracking
  - Order status management (pending, delivered, cancelled)
  - Order history
  - Automatic order number generation

- **Customer Management**
  - Customer profiles
  - Contact information
  - Order history per customer

- **Activity Logging**
  - Track all user actions
  - Audit trail for security
  - Activity log viewer for admins

- **API Endpoints**
  - RESTful API for mobile app integration
  - JWT authentication
  - Standardized JSON responses
  - Proper error handling

## 🛠 Tech Stack

- **Backend:** Symfony 6.4
- **Database:** MySQL/MariaDB
- **ORM:** Doctrine
- **Authentication:** JWT (LexikJWTAuthenticationBundle)
- **OAuth:** Google OAuth2 (knpu/oauth2-client-bundle)
- **API Platform:** API Platform (for RESTful API)
- **Frontend:** Twig templates with TailwindCSS
- **Validation:** Symfony Validator
- **Testing:** PHPUnit

## 📦 Prerequisites

Before you begin, ensure you have the following installed:

- **PHP:** 8.2 or higher
- **Composer:** 2.x or higher
- **MySQL/MariaDB:** 8.0 or higher
- **Node.js:** 18.x or higher (for asset compilation)
- **npm/yarn:** For managing JavaScript dependencies
- **Git:** For version control

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/brewery-management.git
cd brewery-management
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install JavaScript Dependencies

```bash
npm install
```

### 4. Configure Environment Variables

Copy the example environment file and configure it:

```bash
cp .env .env.local
```

Edit `.env.local` and update the following variables:

```env
# Database Configuration
DATABASE_URL="mysql://root:password@127.0.0.1:3306/brewery_db?serverVersion=8.0"

# JWT Configuration
JWT_SECRET_KEY="your-secret-key-change-this-in-production"
JWT_PUBLIC_KEY="your-public-key-change-this-in-production"
JWT_PASSPHRASE="your-passphrase-change-this-in-production"

# Google OAuth (Optional)
GOOGLE_CLIENT_ID="your-google-client-id"
GOOGLE_CLIENT_SECRET="your-google-client-secret"

# Mailer Configuration
MAILER_DSN="smtp://user:password@smtp.example.com:587?verify_peer=0"

# App Environment
APP_ENV=dev
APP_SECRET="your-app-secret-change-this"
```

### 5. Generate JWT Keys

```bash
php bin/console lexik:jwt:generate-keypair
```

### 6. Create the Database

```bash
php bin/console doctrine:database:create
```

### 7. Run Database Migrations

```bash
php bin.console doctrine:migrations:migrate
```

### 8. Load Fixtures (Optional - for development)

```bash
php bin/console doctrine:fixtures:load
```

This will create:
- 1 Admin user (admin@samacobrewery.com / admin123)
- 1 Staff user (staff@samacobrewery.com / staff123)
- 1 Customer user (customer@samacobrewery.com / customer123)
- Sample products and categories

### 9. Compile Assets

```bash
npm run build
```

## ⚙️ Configuration

### Database Configuration

The database connection is configured in `.env.local`. Ensure your MySQL/MariaDB server is running and the database exists.

### JWT Configuration

JWT keys are generated in `config/jwt/` directory. Keep these files secure and never commit them to version control.

### CORS Configuration

CORS is configured in `config/packages/nelmio_cors.yaml` to allow API access from your mobile app. Update the `allow_origin` setting to match your mobile app's domain.

## 🏃 Running the Application

### Development Server

Start the Symfony development server:

```bash
symfony server:start
```

The application will be available at:
- **Web:** http://localhost:8000
- **API:** http://localhost:8000/api

### Using Docker (Optional)

If you prefer Docker, use the provided docker-compose file:

```bash
docker-compose up -d
```

## 📚 API Documentation

Complete API documentation is available in [API_DOCUMENTATION.md](API_DOCUMENTATION.md).

### Quick API Reference

| Endpoint | Method | Description | Auth Required |
|----------|--------|-------------|---------------|
| `/api/login` | POST | Login and get JWT token | No |
| `/api/register` | POST | Register new user | No |
| `/api/verify-email` | POST | Verify email address | No |
| `/api/me` | GET | Get current user profile | Yes |
| `/api/products` | GET | Get all products | Yes |
| `/api/products/{id}` | GET | Get product by ID | Yes |
| `/api/categories` | GET | Get all categories | Yes |
| `/api/orders` | GET | Get user orders | Yes |
| `/api/orders/{id}` | GET | Get order by ID | Yes |
| `/api/orders` | POST | Create new order | Yes |

### API Authentication

Include the JWT token in the Authorization header:

```bash
curl -X GET http://localhost:8000/api/me \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## 🗄 Database Schema

### Entities

- **User** - System users (Admin, Staff, Customers)
- **Customer** - Customer profiles linked to users
- **Product** - Product catalog
- **Category** - Product categories
- **Order** - Customer orders
- **OrderItem** - Items within an order
- **ActivityLog** - User activity audit trail

### Relationships

- User → Customer (One-to-One)
- User → Product (Many-to-One - createdBy)
- User → Order (Many-to-One - createdBy)
- Category → Product (One-to-Many)
- Customer → Order (One-to-Many)
- Order → OrderItem (One-to-Many)
- Product → OrderItem (One-to-Many)

## 👥 User Roles

### ROLE_ADMIN
- Full access to all features
- Can manage users, products, orders, customers
- Can view activity logs
- Can delete records

### ROLE_STAFF
- Can manage products (create, edit own products)
- Can manage customers (create, edit own customers)
- Can manage orders (create, edit own orders)
- Cannot delete records
- Cannot access user management

### ROLE_USER (Customer)
- Can view products and categories
- Can create orders
- Can view own order history
- Can manage own profile

## 🧪 Testing

### Run PHPUnit Tests

```bash
php bin/phpunit
```

### Run Specific Test

```bash
php bin/phpunit tests/Controller/ProductControllerTest.php
```

### Generate Coverage Report

```bash
php bin/phpunit --coverage-html coverage
```

## 🚢 Deployment

### Railway Deployment

1. Create a new project on Railway
2. Connect your GitHub repository
3. Add environment variables in Railway dashboard
4. Deploy automatically on push to main branch

### Required Environment Variables for Production

```env
DATABASE_URL=mysql://user:password@host:3306/dbname
JWT_SECRET_KEY=your-production-secret
JWT_PUBLIC_KEY=your-production-public-key
JWT_PASSPHRASE=your-production-passphrase
APP_ENV=prod
APP_SECRET=your-production-app-secret
MAILER_DSN=smtp://user:password@smtp.example.com:587
```

### Build Commands

```bash
composer install --no-dev --optimize-autoloader
php bin/console cache:clear
php bin/console doctrine:migrations:migrate --no-interaction
npm run build
```

## 📁 Project Structure

```
brewery-management/
├── assets/                 # Frontend assets (CSS, JS)
├── bin/                    # Console scripts
├── config/                 # Configuration files
│   ├── packages/          # Bundle configurations
│   └── routes/            # Route definitions
├── migrations/             # Database migrations
├── public/                 # Public web directory
│   ├── images/           # Image assets
│   └── build/            # Compiled assets
├── src/
│   ├── Controller/       # Controllers
│   ├── Entity/           # Doctrine entities
│   ├── Form/             # Form types
│   ├── Repository/       # Doctrine repositories
│   ├── Security/         # Security handlers
│   └── Service/          # Business logic services
├── templates/             # Twig templates
│   ├── dashboard/       # Dashboard templates
│   ├── landing/          # Landing page
│   ├── about/            # About page
│   └── contact/          # Contact page
├── tests/                 # PHPUnit tests
├── translations/          # Translation files
├── vendor/                # Composer dependencies
├── .env                   # Environment variables
├── .env.local            # Local environment overrides
├── composer.json         # PHP dependencies
├── package.json           # JavaScript dependencies
└── README.md             # This file
```

## 🔧 Troubleshooting

### Database Connection Error

**Problem:** Can't connect to database

**Solution:**
1. Ensure MySQL/MariaDB is running
2. Check DATABASE_URL in `.env.local`
3. Verify database credentials
4. Create the database if it doesn't exist

### JWT Authentication Not Working

**Problem:** JWT token not being accepted

**Solution:**
1. Regenerate JWT keys: `php bin/console lexik:jwt:generate-keypair`
2. Check JWT_SECRET_KEY in `.env.local`
3. Verify token is being sent in Authorization header

### Assets Not Loading

**Problem:** CSS/JS files not loading

**Solution:**
1. Run `npm install` to install dependencies
2. Run `npm run build` to compile assets
3. Clear cache: `php bin/console cache:clear`

### Permission Denied

**Problem:** Access denied to certain routes

**Solution:**
1. Check user roles in database
2. Verify access_control rules in `config/packages/security.yaml`
3. Ensure user is authenticated

### CORS Error

**Problem:** API requests blocked by CORS

**Solution:**
1. Update `config/packages/nelmio_cors.yaml`
2. Add your mobile app's domain to `allow_origin`
3. Clear cache after changes

## 📞 Support

For support, please contact:
- Email: support@samacobrewery.com
- GitHub Issues: https://github.com/yourusername/brewery-management/issues

## 📄 License

This project is licensed under the MIT License.

## 🙏 Acknowledgments

- Symfony Framework
- API Platform
- LexikJWTAuthenticationBundle
- All open-source contributors

---

**Last Updated:** January 2025
