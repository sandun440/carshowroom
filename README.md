# AutoHub Car Showroom Management System

A lightweight PHP-based showroom management system for managing cars, staff, sales, and showroom operations.

## 🔎 Project Overview

AutoHub is built with vanilla PHP, PDO, and Tailwind CSS. It provides:

- Secure login and role-based access control
- Admin dashboard with inventory, staff, and sales views
- Car management: add, edit, delete vehicles
- Sales recording with customer information
- Staff management for administrators
- Real-time showroom metrics and reporting pages

## 🚀 Features

### Authentication
- Admin and sales user login
- Session-based access control
- `includes/auth.php` handles login guard and role checks

### Admin Functionality
- Dashboard overview metrics
- Car inventory management
- Sales reporting
- Staff management and account activation
- Register new staff users

### Sales Team Functionality
- View available cars
- Record new sales
- View assigned sales and performance

### UI
- Mobile-responsive layout using Tailwind CSS CDN
- Font Awesome icons for visual polish
- Single-page dashboard with dynamic section loading

## ⚙️ Prerequisites

- Windows with XAMPP installed
- Apache and MySQL enabled
- PHP 7.4+ / PHP 8.x recommended
- `mod_rewrite` not required but useful for future routing

## 📁 Project Structure

- `index.php` — redirects to login page
- `login.php` — login form and authentication
- `logout.php` — ends session and redirects to login
- `register.php` — admin-only staff registration
- `dashboard.php` — main authenticated dashboard container
- `config/db.php` — PDO database connection settings
- `includes/auth.php` — login requirement and role helper functions
- `sections/` — dashboard content sections
  - `overview.php`
  - `cars.php`
  - `users.php`
  - `sales.php`
  - `available.php`
  - `my-sales.php`
- `uploads/` — upload folder for car assets (if used)
- `assets/css/output.css` — compiled frontend styles

## 🛠️ Installation

1. Copy the project folder into your XAMPP `htdocs` directory.
   - Example: `C:\xampp\htdocs\carshowroom`

2. Start Apache and MySQL from the XAMPP Control Panel.

3. Open the database management tool:
   - `http://localhost/phpmyadmin`

4. Create a database named `car_showroom`.

5. Configure database credentials in `config/db.php`.
   - Update `$host`, `$db`, `$user`, and `$pass` as needed.

6. Create the required database tables.
   - Use the sample SQL below if you do not already have schema scripts.

## 🧩 Sample Database Schema

The application expects a schema with these tables and columns.

```sql
CREATE TABLE roles (
  role_id INT AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(50) NOT NULL
);

CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(50),
  password_hash VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

CREATE TABLE cars (
  car_id INT AUTO_INCREMENT PRIMARY KEY,
  make VARCHAR(100) NOT NULL,
  model VARCHAR(100) NOT NULL,
  year YEAR NOT NULL,
  price DECIMAL(12,2) NOT NULL,
  color VARCHAR(50),
  mileage INT DEFAULT 0,
  fuel_type VARCHAR(50),
  transmission VARCHAR(50),
  description TEXT,
  status VARCHAR(50) DEFAULT 'available',
  vin VARCHAR(100),
  added_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (added_by) REFERENCES users(user_id)
);

CREATE TABLE customers (
  customer_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150),
  phone VARCHAR(50) NOT NULL,
  address TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sales (
  sale_id INT AUTO_INCREMENT PRIMARY KEY,
  car_id INT NOT NULL,
  customer_id INT NOT NULL,
  salesperson_id INT NOT NULL,
  sale_price DECIMAL(12,2) NOT NULL,
  sale_date DATE NOT NULL,
  payment_method VARCHAR(100) NOT NULL,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (car_id) REFERENCES cars(car_id),
  FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
  FOREIGN KEY (salesperson_id) REFERENCES users(user_id)
);
```

## 🧪 Default Demo Credentials

- Admin: `admin / admin123`
- Sales: `sales / sales123`

> These credentials are included for demo purposes. Update or remove them before using in production.

## 🚪 Usage

1. Open in browser:
   - `http://localhost/carshowroom`

2. You will be redirected to the login page.
3. Use admin credentials to access full dashboard features.
4. Sales users can access available cars and record sales.

## 🔒 Security Notes

- Update `config/db.php` with secure credentials.
- Never store production passwords in plain text.
- The application uses `password_hash()` and `password_verify()` for password security.
- Use HTTPS for production deployments.

## 🔧 Customization

- Tailwind CSS is loaded via CDN in each page header.
- Page sections are loaded dynamically through `dashboard.php` using the `section` query parameter.
- To customize the UI, edit the HTML templates and Tailwind utility classes directly.

## ✅ Recommended Improvements

- Add email verification and password reset flows.
- Implement CSRF protection for all forms.
- Add file upload support for car photos.
- Introduce migration scripts or database seeders.
- Use a router or framework for cleaner URL management.

## 📌 Notes

- `dashboard.php` controls navigation and includes section files from `sections/`.
- `register.php` is restricted to admins only.
- `logout.php` clears the session and sends users back to login.

## 📝 Contact

For questions or further customization, edit the code in the corresponding file paths and test in XAMPP.
