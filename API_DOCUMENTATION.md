# Samaco Brewery API Documentation

## Overview
This API provides endpoints for the Samaco Brewery Management System backend, designed to be consumed by the React Native mobile application.

**Base URL:** `http://localhost:8000/api`

**Authentication:** JWT Bearer Token (obtained from `/api/login`)

---

## Authentication Endpoints

### 1. Login
**Endpoint:** `POST /api/login`

**Description:** Authenticate user and receive JWT token

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (200 OK):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "fullName": "John Doe",
    "firstName": "John",
    "lastName": "Doe",
    "roles": ["ROLE_USER"]
  }
}
```

**Response (401 Unauthorized):**
```json
{
  "code": 401,
  "message": "Invalid credentials"
}
```

---

### 2. Register
**Endpoint:** `POST /api/register`

**Description:** Register a new user account

**Request Body:**
```json
{
  "email": "newuser@example.com",
  "password": "password123",
  "firstName": "Jane",
  "lastName": "Smith"
}
```

**Response (201 Created):**
```json
{
  "message": "Registration successful. Please check your email to verify your account.",
  "user": {
    "id": 2,
    "email": "newuser@example.com",
    "firstName": "Jane",
    "lastName": "Smith",
    "isVerified": false
  }
}
```

**Response (400 Bad Request):**
```json
{
  "errors": {
    "email": "This email is already in use"
  }
}
```

---

### 3. Verify Email
**Endpoint:** `POST /api/verify-email`

**Description:** Verify user email address

**Request Body:**
```json
{
  "token": "verification_token_here"
}
```

**Response (200 OK):**
```json
{
  "message": "Email verified successfully"
}
```

---

### 4. Resend Verification
**Endpoint:** `POST /api/resend-verification`

**Description:** Resend email verification token

**Request Body:**
```json
{
  "email": "user@example.com"
}
```

**Response (200 OK):**
```json
{
  "message": "Verification email sent"
}
```

---

## Customer API Endpoints (Authenticated)

### 5. Get Current User Profile
**Endpoint:** `GET /api/me`

**Description:** Get authenticated user's profile information

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Response (200 OK):**
```json
{
  "user": {
    "id": 1,
    "email": "user@example.com",
    "fullName": "John Doe",
    "firstName": "John",
    "lastName": "Doe",
    "roles": ["ROLE_USER"],
    "isVerified": true,
    "createdAt": "2024-01-15 10:30:00"
  }
}
```

**Response (401 Unauthorized):**
```json
{
  "error": "Not authenticated"
}
```

---

### 6. Get All Products
**Endpoint:** `GET /api/products`

**Description:** Retrieve all available products

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Response (200 OK):**
```json
{
  "products": [
    {
      "id": 1,
      "name": "Classic Lager",
      "description": "A smooth, refreshing lager with notes of caramel and a crisp finish",
      "price": 89.00,
      "stockQuantity": 500,
      "minimumStock": 50,
      "category": {
        "id": 1,
        "name": "Lager"
      },
      "createdAt": "2024-01-15 10:30:00"
    },
    {
      "id": 2,
      "name": "Dark Stout",
      "description": "Rich and creamy stout with hints of coffee and chocolate",
      "price": 120.00,
      "stockQuantity": 300,
      "minimumStock": 30,
      "category": {
        "id": 2,
        "name": "Stout"
      },
      "createdAt": "2024-01-15 11:00:00"
    }
  ],
  "count": 2
}
```

---

### 7. Get Product by ID
**Endpoint:** `GET /api/products/{id}`

**Description:** Retrieve a specific product by ID

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Response (200 OK):**
```json
{
  "id": 1,
  "name": "Classic Lager",
  "description": "A smooth, refreshing lager with notes of caramel and a crisp finish",
  "price": 89.00,
  "stockQuantity": 500,
  "minimumStock": 50,
  "category": {
    "id": 1,
    "name": "Lager",
    "description": "Light, refreshing beers"
  },
  "createdAt": "2024-01-15 10:30:00"
}
```

**Response (404 Not Found):**
```json
{
  "error": "Product not found"
}
```

---

### 8. Get All Categories
**Endpoint:** `GET /api/categories`

**Description:** Retrieve all product categories

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Response (200 OK):**
```json
{
  "categories": [
    {
      "id": 1,
      "name": "Lager",
      "description": "Light, refreshing beers",
      "productCount": 5,
      "products": [
        {
          "id": 1,
          "name": "Classic Lager",
          "price": 89.00
        }
      ]
    },
    {
      "id": 2,
      "name": "Stout",
      "description": "Dark, rich beers",
      "productCount": 3,
      "products": [
        {
          "id": 2,
          "name": "Dark Stout",
          "price": 120.00
        }
      ]
    }
  ],
  "count": 2
}
```

---

### 9. Get User Orders
**Endpoint:** `GET /api/orders`

**Description:** Retrieve orders for the authenticated user

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Response (200 OK):**
```json
{
  "orders": [
    {
      "id": 1,
      "orderNumber": "ORD-ABC123",
      "orderDate": "2024-01-15 14:30:00",
      "status": "pending",
      "totalAmount": "178.00",
      "notes": "Please deliver after 5 PM",
      "customer": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com"
      },
      "items": [
        {
          "productName": "Classic Lager",
          "quantity": 2,
          "unitPrice": "89.00"
        }
      ]
    }
  ],
  "count": 1
}
```

---

### 10. Create Order
**Endpoint:** `POST /api/orders`

**Description:** Create a new order

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Request Body:**
```json
{
  "customerId": 1,
  "items": [
    {
      "productId": 1,
      "quantity": 2
    },
    {
      "productId": 2,
      "quantity": 1
    }
  ],
  "notes": "Please deliver after 5 PM"
}
```

**Response (201 Created):**
```json
{
  "id": 2,
  "orderNumber": "ORD-XYZ789",
  "orderDate": "2024-01-15 15:00:00",
  "status": "pending",
  "totalAmount": "298.00",
  "customer": {
    "id": 1,
    "name": "John Doe"
  },
  "items": [
    {
      "productId": 1,
      "productName": "Classic Lager",
      "quantity": 2,
      "unitPrice": "89.00"
    },
    {
      "productId": 2,
      "productName": "Dark Stout",
      "quantity": 1,
      "unitPrice": "120.00"
    }
  ]
}
```

**Response (400 Bad Request):**
```json
{
  "error": "Insufficient stock for product: Classic Lager"
}
```

---

### 11. Get Order by ID
**Endpoint:** `GET /api/orders/{id}`

**Description:** Retrieve a specific order by ID

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Response (200 OK):**
```json
{
  "id": 1,
  "orderNumber": "ORD-ABC123",
  "orderDate": "2024-01-15 14:30:00",
  "status": "pending",
  "totalAmount": "178.00",
  "notes": "Please deliver after 5 PM",
  "customer": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "phone": "+63 123 456 7890",
    "address": "123 Brewery Street, Manila, Philippines"
  },
  "items": [
    {
      "id": 1,
      "productName": "Classic Lager",
      "quantity": 2,
      "unitPrice": "89.00",
      "subtotal": "178.00"
    }
  ]
}
```

**Response (404 Not Found):**
```json
{
  "error": "Order not found"
}
```

---

## Error Response Format

All error responses follow this format:

```json
{
  "error": "Error message description",
  "code": 400
}
```

### Common HTTP Status Codes

- `200 OK` - Request successful
- `201 Created` - Resource created successfully
- `400 Bad Request` - Invalid request data
- `401 Unauthorized` - Authentication required or invalid
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation error
- `500 Internal Server Error` - Server error

---

## Rate Limiting

- **Login endpoint:** 5 requests per minute per IP
- **Register endpoint:** 3 requests per minute per IP
- **Other endpoints:** 100 requests per minute per authenticated user

---

## Pagination

List endpoints support pagination via query parameters:

```
GET /api/products?page=1&limit=20
```

**Response:**
```json
{
  "data": [...],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 100,
    "totalPages": 5
  }
}
```

---

## Filtering & Sorting

Products endpoint supports filtering and sorting:

```
GET /api/products?category=1&sort=price&order=asc
```

**Parameters:**
- `category` - Filter by category ID
- `sort` - Field to sort by (name, price, createdAt)
- `order` - Sort direction (asc, desc)

---

## WebSocket Events (Real-time Updates)

For mobile & web synchronization, the API emits WebSocket events:

### Event: `order.updated`
```json
{
  "event": "order.updated",
  "data": {
    "orderId": 1,
    "status": "delivered",
    "timestamp": "2024-01-15T15:30:00Z"
  }
}
```

### Event: `product.stock.updated`
```json
{
  "event": "product.stock.updated",
  "data": {
    "productId": 1,
    "stockQuantity": 450,
    "timestamp": "2024-01-15T15:30:00Z"
  }
}
```

---

## Testing

Use the provided JWT token in the Authorization header:

```bash
curl -X GET http://localhost:8000/api/products \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

## Support

For API issues or questions, contact the development team at: `support@samacobrewery.com`
