<!-- omit in toc -->

# Local Development

<!-- omit in toc -->

## Table of Contents

- [Local Development](#local-development)
  - [Table of Contents](#table-of-contents)
  - [1. Clone the Repository](#1-clone-the-repository)
  - [2. Environment Configuration](#2-environment-configuration)
    - [2.1 Base Environment (.env)](#21-base-environment-env)
    - [2.2 DDEV Environment (.env.ddev)](#22-ddev-environment-envddev)
    - [2.3 Native Environment (.env.native)](#23-native-environment-envnative)
  - [3. Switching Between DDEV and Native](#3-switching-between-ddev-and-native)
    - [3.1 Using DDEV](#31-using-ddev)
    - [3.2 Using Native](#32-using-native)
  - [4. Email Viewing](#4-email-viewing)
    - [4.1 DDEV](#41-ddev)
    - [4.2 Native](#42-native)
  - [5. Tips - General Guidelines](#5-tips---general-guidelines)
    - [5.1 Keeping the dependencies up-to-date](#51-keeping-the-dependencies-up-to-date)
      - [5.1.1 Backend](#511-backend)
      - [5.1.2 Frontend](#512-frontend)
  - [6. Where to Go From Here](#6-where-to-go-from-here)
  - [7. Troubleshooting](#7-troubleshooting)

## 1. Clone the Repository

```sh
git clone https://github.com/scify/price-updater.git

cd price-updater
```

## 2. Environment Configuration

price-updater uses different environment configurations based on whether you are running with **DDEV** or **Native**.
The application automatically loads the appropriate environment variables, based on the `APP_DEVELOPMENT_ENV`
environment variable.

### 2.1 Base Environment (.env)

The default `.env` file contains the general configuration, and is used by both **DDEV** and **Native**.
Copy the `.env.example` file to create a new `.env` file, and edit as needed:

```sh
cp .env.example .env
```

### 2.2 DDEV Environment (.env.ddev)

If you are using **DDEV**, create a `.env.ddev` file with the following:

```ini
APP_URL = "https://price-updater.ddev.site:8443"
DB_HOST = "db"
DB_DATABASE = "db"
DB_USERNAME = "db"
DB_PASSWORD = "db"
VITE_DEV_URL = "https://price-updater.ddev.site"
VITE_APP_PORT = "8443"
VITE_DEV_PORT = "5179"
```

You can copy the `.env.ddev.example` file to create a new `.env.ddev` file:

```sh
cp .env.ddev.example .env.ddev
```

### 2.3 Native Environment (.env.native)

If you are using a Native environment (Composer, PHP, etc running locally), create a `.env.native` file with the following:

```ini
APP_URL = http://localhost:8000
DB_HOST = 127.0.0.1
DB_DATABASE = "my_app"
DB_USERNAME = "admin" # Change to your database username
DB_PASSWORD = "pass" # Change to your database password
VITE_DEV_URL = "http://localhost"
VITE_APP_PORT = "8000"
VITE_DEV_PORT = "5173"
```

You can copy the `.env.native.example` file to create a new `.env.native` file:

```sh
cp .env.native.example .env.native
```

## 3. Switching Between DDEV and Native

If you want to switch between **DDEV** and **Native** for development, you can set the `APP_DEVELOPMENT_ENV`
environment variable to either `ddev` or `native`.
**Note:** After switching environments, you will need to clear the config cache (both DDEV and Native):

```sh
ddev restart # If using DDEV

./clear-cache.sh
```

### 3.1 Using DDEV

First generate an application key:

```sh
ddev artisan key:generate
```

To start the development environment using **DDEV**:

```sh
ddev start
```

Run migrations:

```sh
ddev artisan migrate
```

Start the frontend development server:

```sh
ddev npm run dev
```

### 3.2 Using Native

First generate an application key:

```sh
php artisan key:generate
```

To switch back to using Native for local development:

```sh
composer install
```

Run migrations:

```sh
php artisan migrate
```

Start the frontend development server:

```sh
npm run dev
```

## 4. Email Viewing

### 4.1 DDEV

When using **DDEV**, you can view emails sent by the application using [Mailpit](https://github.com/axllent/mailpit). Read
more [here](https://ddev.readthedocs.io/en/stable/users/usage/developer-tools/#email-capture-and-review-mailpit).

### 4.2 Native

When using **Native**, you can view emails sent by the application using one of the methods
described [here](https://laravel.com/docs/12.x/mail#mail-and-local-development).

## 5. Tips - General Guidelines

### 5.1 Keeping the dependencies up-to-date

#### 5.1.1 Backend

Run `composer outdated --direct` to check for outdated Composer dependencies, and update them as needed.

#### 5.1.2 Frontend

Use tools like [ncu](https://www.npmjs.com/package/npm-check-updates) to check for outdated NPM dependencies.

## 6. Where to Go From Here

- Watch [this video](https://www.youtube.com/watch?v=phaBzRIioAw) to learn more about the Vue/Inertia setup.
- Take a look at `app/Providers/AppServiceProvider.php` to check the configuration.

## 7. Troubleshooting

- Ensure the correct environment file is loaded using `env('DB_HOST')` in Tinker.
  - For DDEV, run `ddev exec php artisan tinker`, and then run `env('DB_HOST')`.
  - For Native, run `php artisan tinker`, and then run `env('DB_HOST')`.
- If the frontend fails to load, ensure the correct environment variables are set.
  - For DDEV, ensure the `VITE_DEV_URL` and `VITE_APP_PORT` are set correctly.
  - For Native, ensure the `VITE_DEV_URL` and `VITE_APP_PORT` are set correctly.
- If Vite fails due to port conflicts, restart it using `pkill -f node`.
- Run `ddev restart` if database issues persist.

Enjoy developing with Laradev! 🚀
