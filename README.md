# 🏠 Boarding House Booking System (Native PHP & MySQL)

A clean, lightweight **Native PHP & MySQL Boarding House Booking System** with full CRUD capabilities, online payments (GCash, Maya, Bank Transfer), user authentication (Admin & Tenant), and PDF receipt generation.

---

## 🚀 Quick Setup Instructions (2 Steps)

### Step 1: Clone Repository into Laragon `www` Folder
```bash
cd C:\laragon\www
git clone https://github.com/YOUR_USERNAME/boardinghouse-booking-system.git App_Dev
```

### Step 2: Run 1-Click Database Setup
Open **Laragon** (Start Apache & MySQL), then visit this URL in your web browser:
```text
http://localhost/App_Dev/setup.php
```
This automatically creates the `boardinghouse_db` database, sets up all MySQL tables (`users`, `rooms`, `bookings`, `payments`), and seeds default admin/tenant accounts!

---

## 🔑 Default Accounts

| Role | Email | Password |
| :--- | :--- | :--- |
| **Admin** | `admin@boardinghouse.com` | `admin123` |
| **Tenant** | `tenant@boardinghouse.com` | `tenant123` |

---

## 📄 Application URLs

- **Public Room Browser**: `http://localhost/App_Dev/index.php`
- **Login**: `http://localhost/App_Dev/login.php`
- **Tenant Tracker & Online Payments**: `http://localhost/App_Dev/my_bookings.php`
- **Admin Room CRUD**: `http://localhost/App_Dev/rooms.php`
- **Admin Booking Management**: `http://localhost/App_Dev/bookings.php`
