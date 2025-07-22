# GEMINI.md

This file provides guidance to Gemini when working with code in this repository.

## Build & Development
- `npm run dev` - Start Vite development server
- `npm run build` - Build frontend assets
- `composer dev` - Start development server with queue, logs and Vite

## Linting & Formatting
- `npm run lint` - Lint JavaScript/TypeScript files
- `npm run lint:fix` - Lint and fix JS/TS files
- `npm run types` - Check TypeScript types
- `npm run format` - Format code with Prettier
- `composer exec vendor/bin/pint` - Format PHP code

## Testing
- `./vendor/bin/pest` - Run all PHP tests
- `./vendor/bin/pest --filter TestName` - Run specific PHP test
- `./vendor/bin/pest --coverage` - Run PHP tests with coverage
- `./vendor/bin/phpstan analyse` - Static analysis (level 5)
- `./vendor/bin/rector` - Automated code refactoring
- `npm test` - Run all Jest tests for React components
- `npm test -- --watch` - Run tests in watch mode
- `npm test -- --testPathPattern=dashboard` - Run specific React test
- `npm test -- --coverage` - Run React tests with coverage

## Code Style
- PHP: PSR-12 with Laravel conventions (see pint.json)
- JS/TS: 4 spaces indentation, single quotes
- React: Functional components with hooks
- Strict typing in PHP and TypeScript
- Imports should be organized alphabetically
- Use Laravel's exception handling for errors
- Follow Laravel's naming conventions

## Frontend Testing
- Tests are written using Jest and React Testing Library
- Component tests are placed in `__tests__` folders next to the components
- Mock external dependencies including InertiaJS, hooks, and icons
- Tests should verify both rendering and functionality
- Test files use the `.test.tsx` extension
- Focus on user interaction and accessibility



  You are an expert in Laravel, PHP, and related web development technologies.

  Key Principles
  - Write concise, technical responses with accurate PHP examples.
  - Follow Laravel best practices and conventions.
  - Use object-oriented programming with a focus on SOLID principles.
  - Prefer iteration and modularization over duplication.
  - Use descriptive variable and method names.
  - Use lowercase with dashes for directories (e.g., app/Http/Controllers).
  - Favor dependency injection and service containers.

  PHP/Laravel
  - Use PHP 8.2+ features when appropriate (e.g., typed properties, match expressions).
  - Follow PSR-12 coding standards.
  - Use strict typing: declare(strict_types=1);
  - Utilize Laravel's built-in features and helpers when possible.
  - File structure: Follow Laravel's directory structure and naming conventions.
  - Implement proper error handling and logging:
    - Use Laravel's exception handling and logging features.
    - Create custom exceptions when necessary.
    - Use try-catch blocks for expected exceptions.
  - Use Laravel's validation features for form and request validation.
  - Implement middleware for request filtering and modification.
  - Utilize Laravel's Eloquent ORM for database interactions.
  - Use Laravel's query builder for complex database queries.
  - Implement proper database migrations and seeders.

  Dependencies
  - Laravel (latest stable version)
  - InertiaJS
  - Composer for dependency management
  - React with Typescript
  - TailwindCSS version 4
  - Shadcn components library
  - Roles and permissions in the Laravel app are managed by the "spatie/laravel-permission" Laravel plugin.
  - Shadcn is used for the front-end components

  Laravel Best Practices
  - Use Eloquent ORM instead of raw SQL queries when possible.
  - Implement Repository pattern for data access layer.
  - Use Laravel's built-in authentication and authorization features.
  - Utilize Laravel's caching mechanisms for improved performance.
  - Implement job queues for long-running tasks.
  - Use Laravel's built-in testing tools (Pest, Dusk) for unit and feature tests.
  - Implement API versioning for public APIs.
  - Use Laravel's localization features for multi-language support.
  - Implement proper CSRF protection and security measures.
  - Use Laravel Mix for asset compilation.
  - Implement proper database indexing for improved query performance.
  - Use Laravel's built-in pagination features.
  - Implement proper error logging and monitoring.
  - Use Pest PHP conventions and best practices for testing. Try to have many assertions and big coverage.

  Key Conventions
  1. Follow Laravel's MVC architecture.
  2. Use Laravel's InertiaJS routing system for defining application endpoints.
  3. Implement proper request validation using Form Requests.
  4. Use Laravel's Blade templating engine for views.
  5. Implement proper database relationships using Eloquent.
  6. Use Laravel's built-in authentication scaffolding.
  7. Implement proper API resource transformations.
  8. Use Laravel's event and listener system for decoupled code.
  9. Implement proper database transactions for data integrity.
  10. Use Laravel's built-in scheduling features for recurring tasks.
