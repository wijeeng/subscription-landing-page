
---

# Subscription Landing Page

This project implements a subscription flow based on the provided Figma design.
It includes phone number input, PIN verification, and integration with both mock and real APIs.

---

## 🚀 Features

* Phone number normalization and validation
* Pre- and post-subscription checks
* PIN input with auto focus UX
* Error handling with user friendly messages
* Mock mode for testing without external API
* Redirect support for already subscribed users
* Basic PHPUnit unit tests

---

## 📁 Project Structure

- `api/` → API entry point (proxy)
- `config/` → Configuration
- `logs/` → Log
- `mock/` → Mock API endpoints
- `public/` → Frontend (HTML, JS, CSS)
- `src/controllers/` → Business logic
- `src/helpers/` → Validation and reusable utilities
- `src/services/` → Shared services
- `test/`  → PHPUnit unit tests

---

## 🧭 Flow

1. User enters phone number
2. `POST /check-subscription`
3. `POST /send-pin`
4. User enters PIN
5. `POST /confirm-pin`
6. Final `POST /check-subscription` to verify subscription

---

## ⚙️ Setup

## 1. Install dependencies

```bash
composer install
```

## 2. Run locally

Place the project inside a local PHP server environment
(Apache, XAMPP, Laragon, etc.)

Example:

```txt
http://localhost/mdg/public/?mode=mock
```

---

# 🔧 Modes

The `mode` query parameter controls API behavior.

## Mock mode

```txt
?mode=mock
```

Uses local mock responses for development/testing.

## Production mode

```txt
?mode=prod
```

Uses configured real provider endpoints.

---

# 🧪 Unit Testing

This project includes basic PHPUnit unit tests for reusable validation logic.

## Run tests

```bash
./vendor/bin/phpunit test
```

---

## 💡 Notes

* The `mode` parameter controls API behavior:

    * `mock` → uses mock responses
    * `prod` → calls real API endpoints
* For demo purposes, API authentication is simplified and handled at the API layer.
* In a production setup, sensitive operations should be handled server side to avoid exposing credentials in the frontend.

---

## 📌 Summary

This implementation focuses on simplicity, clear API separation, and a smooth user experience, while remaining flexible for integration with different backend environments.

---
