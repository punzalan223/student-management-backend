# Laravel Student Services Management API

This is the backend API for the **Student Services Management Module**. Built with **Laravel 12**, it provides authentication, role-based access, student management, service request handling, and Excel import functionality.

---

## Installation

1. Install dependencies:

```bash
composer install
```

2. Copy .env.example to .env and update your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=service-management-backend
DB_USERNAME=root
DB_PASSWORD=

3. Run migrations and seeders:
```bash
php artisan migrate --seed
```

4. Start the Laravel server:
```bash
php artisan serve
```

5. Start the queue worker (required for Excel import processing):
```bash
php artisan queue:work
```

## Excel Import Format
Admin users can upload Excel files for bulk service requests. The file must follow this column structure:

| Student Number | Service Type   | Requested Date | First Name | Last Name | Grade Level | Email                                                           |
| -------------- | -------------- | -------------- | ---------- | --------- | ----------- | --------------------------------------------------------------- |
| 2024001        | Good Moral     | 2025-11-24     | Juan       | Dela Cruz | 10          | [juan.delacruz@example.com](mailto:juan.delacruz@example.com)   |
| 2024002        | ID Replacement | 2025-11-25     | Maria      | Santos    | 11          | [maria.santos@example.com](mailto:maria.santos@example.com)     |
| 2024003        | Form 137       | 2025-11-26     | Carlos     | Ramos     | 12          | [carlos.ramos@example.com](mailto:carlos.ramos@example.com)     |
| 2024004        | Good Moral     | 2025-11-27     | Ana        | Lopez     | 13          | [ana.lopez@example.com](mailto:ana.lopez@example.com)           |
| 2024005        | ID Replacement | 2025-11-28     | Pedro      | Garcia    | 10          | [pedro.garcia@example.com](mailto:pedro.garcia@example.com)     |
| 2024006        | Form 137       | 2025-11-29     | Luisa      | Martinez  | 11          | [luisa.martinez@example.com](mailto:luisa.martinez@example.com) |

- or you can download the sample csv file at the folder named "data-sample.csv"

## Notes

- There is also a **SQL file** ready to be downloaded inside the folder for database setup.
- Make sure to update your `.env` database credentials before running migrations.
- Queue worker (`php artisan queue:work`) is required for processing Excel imports.
