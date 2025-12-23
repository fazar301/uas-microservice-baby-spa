<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Latumi App

A baby spa application built with Laravel.

## Prerequisites

- PHP >= 8.1
- Composer
- Node.js & NPM
- MySQL
- Git

## Installation

1. Clone the repository:
```bash
git clone https://github.com/your-username/latumi-app.git
cd latumi-app
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install NPM dependencies:
```bash
npm install
```

4. Create environment file:
```bash
cp .env.example .env
```

5. Generate application key:
```bash
php artisan key:generate
php artisan storage:link
```

6. Configure your database in `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=latumi
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

7. Run migrations:
```bash
php artisan migrate
```

8. Seed the database with default users:
```bash
php artisan db:seed
```
This will create two default users:
- Admin: admin@latumi.com / admin123
- Customer: customer@latumi.com / customer123

9. Start the development server:
```bash
php artisan serve
```

10. In a new terminal, start Vite for frontend assets:
```bash
npm run dev
```

The application should now be running at `http://localhost:8000`

## Features

- Baby spa services
- Online booking system
- Service management
- User authentication
- Admin dashboard

## Code Quality & Linting

This project uses [Laravel Pint](https://laravel.com/docs/pint) for PHP code formatting and linting. Pint ensures consistent code style across the project.

### Available Commands

- **Check code style** (without making changes):
```bash
composer format:check
```

- **Fix code style automatically**:
```bash
composer format:fix
```

- **Format code** (alias for format:fix):
```bash
composer format
```

- **Run Pint directly**:
```bash
./vendor/bin/pint
```

### Configuration

The Pint configuration is stored in `pint.json` and includes:
- Laravel preset for consistent styling
- Custom rules for array syntax, imports, and spacing
- Automatic removal of unused imports
- Proper operator spacing and line breaks

### Pre-commit Hook (Optional)

To automatically format code before commits, you can add this to your `.git/hooks/pre-commit`:

```bash
#!/bin/sh
composer format:fix
git add -A
```

Make the hook executable:
```bash
chmod +x .git/hooks/pre-commit
```

### GitHub Actions

This project includes automated code quality checks via GitHub Actions:

- **Laravel Pint**: Automatically checks and formats PHP code on pull requests and pushes
- **Code Quality**: Runs comprehensive checks including syntax validation and tests
- **Pull Request Template**: Includes code quality checklist for contributors

The workflows are located in `.github/workflows/` and will:
- Run on pushes to `main` and `develop` branches
- Run on pull requests to `main` and `develop` branches
- Only trigger when PHP files, composer files, or pint.json are modified
- Cache dependencies for faster builds
- Provide detailed feedback on code quality issues

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Contributing

1. Create a new branch for your feature:
```bash
git checkout -b feature/your-feature-name
```

2. Make your changes and commit them:
```bash
git add .
git commit -m "Add your feature description"
```

3. Push to your branch:
```bash
git push origin feature/your-feature-name
```

4. Create a Pull Request on GitHub

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Docker Setup

### Prerequisites
- Docker
- Docker Compose

### Getting Started with Docker

1. Clone the repository:
```bash
git clone <repository-url>
cd latumi-app
```

2. Copy the environment file:
```bash
cp .env.example .env
```

3. Start the Docker containers:
```bash
docker-compose up -d
```

4. Install dependencies and set up the application:
```bash
# Install PHP dependencies
docker-compose exec app composer install

# Install Node.js dependencies
docker-compose exec app npm install

# Generate application key
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate

# Build assets
docker-compose exec app npm run build
```

5. Access the application:
- Web application: http://localhost:8000
- MySQL database: localhost:3306

### Useful Docker Commands

- Stop containers:
```bash
docker-compose down
```

- View logs:
```bash
docker-compose logs -f
```

- Access container shell:
```bash
docker-compose exec app bash
```

- Rebuild containers:
```bash
docker-compose up -d --build
```
