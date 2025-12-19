# 🐰 BunnyKitty PHP Framework

A lightweight, JSON-RPC inspired PHP micro-framework built on top of Symfony HttpFoundation with MongoDB support. BunnyKitty provides a simple yet powerful architecture for building API backends with middleware support, configuration management, and a clean separation between framework and application code.

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Project Structure](#project-structure)
- [Configuration](#configuration)
- [Creating Routes (Handlers)](#creating-routes-handlers)
- [Creating Middlewares](#creating-middlewares)
- [Request & Response](#request--response)
- [Database (MongoDB)](#database-mongodb)
- [Helper Functions](#helper-functions)
- [API Format](#api-format)
- [Examples](#examples)
- [JavaScript Client](#javascript-client)
- [Running the Application](#running-the-application)

---

## Features

- 🚀 **Lightweight** – Minimal overhead, built on Symfony HttpFoundation
- 📡 **JSON-RPC Inspired** – Custom protocol for structured API communication
- 🔐 **Middleware Pipeline** – Configurable middleware chain per route
- 🗄️ **MongoDB Integration** – Built-in MongoDB client with helper functions
- ⚙️ **TOML Configuration** – Simple, readable configuration files
- 🎯 **File-based Routing** – Routes map directly to PHP files

---

## Requirements

- PHP 8.1+
- Composer
- MongoDB (optional, for database features)

---

## Installation

1. **Clone or create the project:**

```bash
git clone <repository-url> my-project
cd my-project
```

2. **Install dependencies:**

```bash
composer install
```

3. **Create environment file:**

```bash
cp .env.example .env
```

4. **Configure your `.env` file:**

```env
# MongoDB Configuration (optional)
MONGODB_URI=mongodb://root:supersecret@localhost:27017
MONGODB_DBNAME=myapp
```

5. **Start MongoDB (if using database features):**

```bash
# Using Docker
docker run -d -p 27017:27017 --name mongodb \
  -e MONGO_INITDB_ROOT_USERNAME=root \
  -e MONGO_INITDB_ROOT_PASSWORD=supersecret \
  mongo
```

---

## Project Structure

```
project-root/
├── app/                        # Your application code
│   ├── Middlewares/            # Custom middlewares
│   │   ├── authorize.php
│   │   └── cors.php
│   ├── Routes/                 # Route handlers
│   │   ├── add.php
│   │   ├── auth/
│   │   │   └── login.php
│   │   ├── posts/
│   │   │   └── get.php
│   │   └── users/
│   │       └── get.php
│   └── autoload.php
│
├── framework/                  # Core framework (do not modify)
│   ├── Config/
│   │   └── Manager.php         # Configuration singleton
│   ├── Database/
│   │   └── MongoDB/
│   │       └── functions.php   # MongoDB helpers
│   ├── Handlers/
│   │   └── functions.php       # Request dispatcher
│   ├── Helpers/
│   │   └── functions.php       # Utility functions
│   ├── Http/
│   │   ├── RequestWrapper.php
│   │   └── ResponseWrapper.php
│   └── Middlewares/
│       └── functions.php       # Middleware factory
│
├── config.toml                 # Route configuration
├── composer.json
├── index.php                   # Entry point
└── .env                        # Environment variables
```

### Directory Breakdown

| Directory | Purpose |
|-----------|---------|
| `app/` | **Your code** – Routes, middlewares, models, and application logic |
| `app/Routes/` | Route handlers – Each file is a callable endpoint |
| `app/Middlewares/` | Custom middleware functions |
| `framework/` | **Core framework** – HTTP handling, config, database, helpers |
| `config.toml` | Route-to-middleware mapping |

---

## Configuration

### Environment Variables (`.env`)

```env
# MongoDB
MONGODB_URI=mongodb://root:supersecret@localhost:27017
MONGODB_DBNAME=myapp

# Add your custom variables
APP_SECRET=your-secret-key
```

### Route Configuration (`config.toml`)

The `config.toml` file defines which middlewares run for each route:

```toml
# Route: auth/login → runs 'cors' middleware
[routes.config."auth/login"]
middlewares = ["cors"]

# Route: users/get → runs 'cors' then 'authorize' middleware
[routes.config."users/get"]
middlewares = ["cors", "authorize"]

# Route: posts/get → runs 'cors' then 'authorize' middleware
[routes.config."posts/get"]
middlewares = ["cors", "authorize"]

# Route: add → no middlewares
[routes.config.add]
middlewares = []
```

#### Configuration Rules

- Route names match the file path under `app/Routes/` (without `.php`)
- Use quotes for routes with slashes: `"auth/login"`
- Middlewares execute in the order listed
- Empty array `[]` means no middlewares

### Accessing Configuration in Code

```php
use function Marking\BunnyKitty\Helpers\config;

// Get a specific configuration value
$middlewares = config("routes.config.users/get.middlewares");

// Get the config manager instance
$configManager = config();
```

---

## Creating Routes (Handlers)

Routes are PHP files in `app/Routes/` that return a callable. The file path becomes the method name in API requests.

### Basic Route

**File:** `app/Routes/add.php`

```php
<?php

return function ($request, $response, $a, $b) {
    return $a + $b;
};
```

**API Call:**
```json
{
  "jsonrpc": "custom",
  "method": "add",
  "params": [5, 3],
  "id": 1
}
```

### Nested Route

**File:** `app/Routes/auth/login.php`

```php
<?php

return function ($request, $response, $username, $password) {
    if ($username === "admin" && $password === "password") {
        return [
            "token" => "my-custom-token",
            "user_id" => 1,
            "auth" => true,
            "message" => "Login successful",
        ];
    }

    return [
        "message" => "Invalid credentials",
        "auth" => false,
    ];
};
```

**API Call:**
```json
{
  "jsonrpc": "custom",
  "method": "auth/login",
  "params": ["admin", "password"],
  "id": 1
}
```

### Handler Function Signature

```php
return function (
    Request $request,           // Symfony HttpFoundation Request
    ?JsonResponse $response,    // Response from middlewares (or null)
    ...$params                  // Parameters from "params" array in request
) {
    // Return an array or value
    return ["result" => "data"];
};
```

### Accessing Request Data in Handlers

```php
<?php

use Symfony\Component\HttpFoundation\Request;

return function (Request $request, $response) {
    // Access headers
    $authHeader = $request->headers->get("Authorization");
    
    // Access attributes set by middlewares
    $userId = $request->attributes->get("userId");
    $isAuthorized = $request->attributes->get("authorized");
    
    // Access query parameters (if any)
    $page = $request->query->get("page", 1);
    
    return ["userId" => $userId];
};
```

---

## Creating Middlewares

Middlewares are PHP files in `app/Middlewares/` that return a callable. They process requests before they reach the handler.

### Middleware Signature

```php
<?php

use Symfony\Component\HttpFoundation\Request;

return function (
    Request $request,
    $response = null,
    $next = null
): ?array {
    // Your middleware logic here
    
    // Continue to next middleware or handler
    if ($next !== null) {
        return $next($request, $response);
    }
    
    return [$request, $response];
};
```

### Authorization Middleware Example

**File:** `app/Middlewares/authorize.php`

```php
<?php

use Symfony\Component\HttpFoundation\Request;
use function Marking\BunnyKitty\Helpers\response;

return function (Request $request, $response = null, $next = null): ?array {
    $authHeader = $request->headers->get("Authorization");

    if ($authHeader && str_starts_with($authHeader, "Bearer ")) {
        $authToken = substr($authHeader, 7);
    }

    if ($authToken !== "my-custom-token") {
        // This exits and returns an error response
        response()->unauthorized("Missing or invalid token");
    }

    // Attach data to request for use in handlers
    $request->attributes->set("authToken", $authToken);
    $request->attributes->set("authorized", true);

    if ($next !== null) {
        return $next($request, $response);
    }

    return [$request, $response];
};
```

### CORS Middleware Example

**File:** `app/Middlewares/cors.php`

```php
<?php

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

return function (Request $request, $response = null, $next = null): ?array {
    $headers = [
        "Access-Control-Allow-Origin" => "http://localhost:3000",
        "Access-Control-Allow-Methods" => "POST, OPTIONS",
        "Access-Control-Allow-Headers" => "Content-Type, Authorization",
        "Access-Control-Allow-Credentials" => "true",
        "Access-Control-Max-Age" => "3600",
    ];

    // Handle preflight requests
    if ($request->getMethod() === Request::METHOD_OPTIONS) {
        $response = new JsonResponse("", JsonResponse::HTTP_NO_CONTENT);

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        $response->send();
        exit();
    }

    if ($next !== null) {
        return $next($request, $response);
    }

    return [$request, $response];
};
```

### Register Middleware to Route

Add the middleware to `config.toml`:

```toml
[routes.config."my/route"]
middlewares = ["cors", "authorize"]
```

---

## Request & Response

### ResponseWrapper Methods

The `response()` helper returns a `ResponseWrapper` with these methods:

```php
use function Marking\BunnyKitty\Helpers\response;

// Success response (exits script)
response()->successAndExit(["data" => "value"], "Custom message");

// Error response (exits script)
response()->errorAndExit("Error message", Response::HTTP_BAD_REQUEST);

// Not found response (exits script)
response()->notFoundAndExit("Resource not found");

// Unauthorized response (exits script)
response()->unauthorized("Invalid token");

// Send custom JSON (exits script)
response()->sendJsonAndExit(["custom" => "data"], Response::HTTP_OK);
```

### Success Response Format

```json
{
  "jsonrpc": "custom",
  "success": true,
  "message": "Success",
  "result": [{ "your": "data" }]
}
```

### Error Response Format

```json
{
  "jsonrpc": "custom",
  "success": false,
  "error": {
    "code": 400,
    "message": "Error description"
  }
}
```

---

## Database (MongoDB)

### Setup

1. Ensure MongoDB is running
2. Configure `.env`:

```env
MONGODB_URI=mongodb://root:password@localhost:27017
MONGODB_DBNAME=myapp
```

### Using MongoDB in Handlers

```php
<?php

use function Marking\BunnyKitty\Database\MongoDB\requireModel;

return function ($request, $response) {
    // Get a collection (model)
    $posts = requireModel("posts");
    
    // Find documents
    $result = iterator_to_array(
        $posts->find(
            [],  // filter
            [
                "limit" => 100,
                "sort" => ["createdAt" => -1],
            ]
        )
    );
    
    return $result;
};
```

### MongoDB Helper Functions

```php
use function Marking\BunnyKitty\Database\MongoDB\mongo;
use function Marking\BunnyKitty\Database\MongoDB\db;
use function Marking\BunnyKitty\Database\MongoDB\requireModel;

// Get MongoDB client
$client = mongo();

// Get database (uses MONGODB_DBNAME from .env)
$database = db();

// Get database with custom name
$database = db("other_database");

// Get collection directly
$users = requireModel("users");

// Common operations
$users->insertOne(["name" => "John", "email" => "john@example.com"]);
$users->find(["active" => true]);
$users->findOne(["_id" => new MongoDB\BSON\ObjectId($id)]);
$users->updateOne(["_id" => $id], ['$set' => ["name" => "Jane"]]);
$users->deleteOne(["_id" => $id]);
```

---

## Helper Functions

Import helpers using the framework namespace:

```php
use function Marking\BunnyKitty\Helpers\config;
use function Marking\BunnyKitty\Helpers\response;
use function Marking\BunnyKitty\Helpers\pathFromRootDir;
```

### Available Helpers

| Function | Description |
|----------|-------------|
| `config($key)` | Get configuration value using dot notation |
| `config()` | Get the ConfigManager instance |
| `response()` | Get ResponseWrapper for sending responses |
| `pathFromRootDir(...$paths)` | Build absolute path from project root |

### Examples

```php
// Get config value
$middlewares = config("routes.config.users/get.middlewares");

// Build path
$filePath = pathFromRootDir("app", "data", "file.json");
// Returns: /absolute/path/to/project/app/data/file.json

// Send response
response()->successAndExit(["users" => $users]);
```

---

## API Format

BunnyKitty uses a JSON-RPC inspired protocol. All requests must:

- Use **POST** method (or OPTIONS for CORS preflight)
- Have `Content-Type: application/json` header
- Include a JSON body with the required fields

### Request Format

```json
{
  "jsonrpc": "custom",
  "method": "route/path",
  "params": ["param1", "param2"],
  "id": 1
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `jsonrpc` | string | Yes | Must be `"custom"` |
| `method` | string | Yes | Route path (maps to `app/Routes/{method}.php`) |
| `params` | array | No | Parameters passed to handler function |
| `id` | mixed | Yes | Request identifier (returned in response) |

### Response Format

**Success:**
```json
{
  "jsonrpc": "custom",
  "success": true,
  "message": "Success",
  "result": [{ "data": "here" }]
}
```

**Error:**
```json
{
  "jsonrpc": "custom",
  "success": false,
  "error": {
    "code": 400,
    "message": "Error description"
  }
}
```

---

## Examples

### Complete Request/Response Flow

**1. Create a route:** `app/Routes/users/create.php`

```php
<?php

use function Marking\BunnyKitty\Database\MongoDB\requireModel;
use function Marking\BunnyKitty\Helpers\response;

return function ($request, $response, $name, $email) {
    $users = requireModel("users");
    
    // Check if user exists
    $existing = $users->findOne(["email" => $email]);
    if ($existing) {
        response()->errorAndExit("User already exists", 409);
    }
    
    // Create user
    $result = $users->insertOne([
        "name" => $name,
        "email" => $email,
        "createdAt" => new MongoDB\BSON\UTCDateTime(),
    ]);
    
    return [
        "id" => (string) $result->getInsertedId(),
        "name" => $name,
        "email" => $email,
    ];
};
```

**2. Configure middleware:** `config.toml`

```toml
[routes.config."users/create"]
middlewares = ["cors", "authorize"]
```

**3. Make API request:**

```bash
curl -X POST http://localhost:8000 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer my-custom-token" \
  -d '{
    "jsonrpc": "custom",
    "method": "users/create",
    "params": ["John Doe", "john@example.com"],
    "id": 1
  }'
```

**4. Response:**

```json
{
  "jsonrpc": "custom",
  "success": true,
  "message": "Success",
  "result": [{
    "id": "507f1f77bcf86cd799439011",
    "name": "John Doe",
    "email": "john@example.com"
  }]
}
```

---

## JavaScript Client

A ready-to-use JavaScript client for interacting with the BunnyKitty API.

### BunnyKitty Client Example

```javascript
/**
 * BunnyKitty API Client
 * Works in both browser and Node.js environments
 */
class BunnyKittyClient {
  constructor(url) {
    this.url = url;
    
    // Return a Proxy to enable dynamic method calls
    return new Proxy(this, {
      get(target, prop) {
        // If the property exists on the target, return it
        if (prop in target) {
          return target[prop];
        }
        
        // Otherwise, create a namespace proxy
        return target._createNamespace(prop);
      }
    });
  }

  /**
   * Create a namespace proxy for chaining (e.g., client.auth.login)
   */
  _createNamespace(namespace, path = []) {
    const fullPath = [...path, namespace];
    
    return new Proxy(() => {}, {
      get: (target, prop) => {
        // Create nested namespace
        return this._createNamespace(prop, fullPath);
      },
      apply: (target, thisArg, args) => {
        // When called as a function, make the RPC call
        const method = fullPath.join('/');
        const params = args[0] || null;
        return this.call(method, params);
      }
    });
  }

  /**
   * Generate a random ID for the request
   * Modify as you want, this is just an example
   */
  generateId() {
    return crypto.randomUUID();
  }

  /**
   * Make a JSON-RPC call
   * @param {string} method - The method to call (e.g., "auth/login", "add")
   * @param {Object|Array} params - Parameters for the method
   * @returns {Promise<Object>} The result from the server
   */
  async call(method, params = null) {
    const request = {
      jsonrpc: "custom",
      method,
      params,
      id: this.generateId()
    };

    try {
      const response = await fetch(this.url, {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(request)
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      // Check if the request was successful
      if (!data.success) {
        const error = new Error(data.error?.message || "Unknown error");
        error.code = data.error?.code;
        error.rpcError = data.error;
        throw error;
      }

      return data;
    } catch (error) {
      console.error("JSON-RPC call failed:", error);
      throw error;
    }
  }

  /**
   * Make a JSON-RPC notification (no response expected)
   * @param {string} method - The method to call
   * @param {Object|Array} params - Parameters for the method
   */
  async notify(method, params = null) {
    const request = {
      jsonrpc: "custom",
      method,
      params
      // No id for notifications
    };

    try {
      await fetch(this.url, {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(request)
      });
    } catch (error) {
      console.error("JSON-RPC notification failed:", error);
      throw error;
    }
  }

  /**
   * Batch multiple requests
   * @param {Array<{method: string, params: Object|Array}>} requests
   * @returns {Promise<Array<Object>>}
   */
  async batch(requests) {
    const batch = requests.map(req => ({
      jsonrpc: "custom",
      method: req.method,
      params: req.params,
      id: this.generateId()
    }));

    try {
      const response = await fetch(this.url, {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(batch)
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      return await response.json();
    } catch (error) {
      console.error("JSON-RPC batch call failed:", error);
      throw error;
    }
  }
}

// Usage Examples:

// Initialize the client
const client = new BunnyKittyClient("https://api.example.com/rpc");

// Example 1: Login using dot notation
async function login() {
  try {
    const response = await client.auth.login({
      username: "admin",
      password: "password"
    });
    
    console.log("Login successful:", response.result[0]);
    return response.result[0].token;
  } catch (error) {
    console.error("Login failed:", error.message);
  }
}

// Example 2: Simple addition using dot notation
async function add(a, b) {
  try {
    const response = await client.add([a, b]);
    console.log(`${a} + ${b} =`, response.result[0]);
    return response.result[0];
  } catch (error) {
    console.error("Addition failed:", error.message);
  }
}

// Example 3: Deep nested namespaces
async function deepNesting() {
  try {
    // Translates to "user/profile/settings/update"
    const response = await client.user.profile.settings.update({
      theme: "dark",
      notifications: true
    });
    console.log("Settings updated:", response);
  } catch (error) {
    console.error("Update failed:", error);
  }
}

// Example 4: Mix of both styles
async function mixedStyles() {
  try {
    // Using dot notation
    await client.auth.login({ username: "admin", password: "password" });
    
    // Using call method directly
    await client.call("user/logout", {});
    
    // Both work!
  } catch (error) {
    console.error("Operation failed:", error);
  }
}

// Example 5: Batch requests (still uses the batch method)
async function batchExample() {
  try {
    const results = await client.batch([
      { method: "add", params: [1, 2] },
      { method: "add", params: [3, 4] },
      { method: "add", params: [5, 6] }
    ]);
    
    console.log("Batch results:", results);
  } catch (error) {
    console.error("Batch failed:", error);
  }
}

// Example 6: Real-world usage
async function performOperations() {
  try {
    // Login
    const loginResult = await client.auth.login({
      username: "admin",
      password: "password"
    });
    const token = loginResult.result[0].token;
    
    // Get user data
    const userData = await client.user.get({ token });
    
    // Update settings
    await client.user.settings.update({
      token,
      preferences: { theme: "dark" }
    });
    
    // Perform calculation
    const sum = await client.math.add([10, 20]);
    
    console.log("All operations completed successfully");
  } catch (error) {
    console.error("Operation failed:", error);
  }
}
```

---

## Running the Application

### Development Server

```bash
php -S localhost:8000 index.php
```

### With Docker

Create a `Dockerfile`:

```dockerfile
FROM php:8.2-apache
RUN pecl install mongodb && docker-php-ext-enable mongodb
COPY . /var/www/html/
RUN a2enmod rewrite
```

### Testing with cURL

```bash
# Simple add operation
curl -X POST http://localhost:8000 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"custom","method":"add","params":[5,3],"id":1}'

# Login
curl -X POST http://localhost:8000 \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"custom","method":"auth/login","params":["admin","password"],"id":1}'

# Protected route with auth
curl -X POST http://localhost:8000 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer my-custom-token" \
  -d '{"jsonrpc":"custom","method":"users/get","params":[],"id":1}'
```

---

## Quick Reference

### Creating a New Route

1. Create file: `app/Routes/my/route.php`
2. Return a callable:
   ```php
   <?php
   return function ($request, $response, ...$params) {
       return ["result" => "data"];
   };
   ```
3. Add to `config.toml`:
   ```toml
   [routes.config."my/route"]
   middlewares = []
   ```

### Creating a New Middleware

1. Create file: `app/Middlewares/myMiddleware.php`
2. Return a callable:
   ```php
   <?php
   return function ($request, $response, $next) {
       // Logic here
       return [$request, $response];
   };
   ```
3. Add to routes in `config.toml`:
   ```toml
   [routes.config."my/route"]
   middlewares = ["myMiddleware"]
   ```

---

## License

MIT License

---

## Author

Created by Mark Caggiano

