# 🎪 Event Management System

A complete PHP + MySQL event management system with User and Admin modules, QR ticket generation, and attendance tracking.

---

## 📋 Requirements

- PHP 7.4+ | MySQL 5.7+ | Apache (XAMPP recommended)
- PHP extensions: `pdo_mysql`, `gd`

---

## 🚀 Setup Instructions (XAMPP)

### Step 1: Place Files
Extract zip and put the `event-management/` folder here:
```
C:\xampp\htdocs\event-management\
```

### Step 2: Import Database
1. Start **Apache** and **MySQL** in XAMPP Control Panel
2. Open `http://localhost/phpmyadmin`
3. Click **Import** → Choose `database.sql` → Click **Go**

### Step 3: Configure Database
Open `includes/db.php` — default XAMPP settings usually need no change:
```php
define('DB_USER', 'root');
define('DB_PASS', '');   // blank for default XAMPP
```

### Step 4: ⚠️ Set Admin Password (REQUIRED)
Visit this URL once in your browser:
```
http://localhost/event-management/setup_admin.php
```
You'll see a green success page confirming the admin account is ready.
**Delete `setup_admin.php` after this step.**

### Step 5: Login
- **Users:** `http://localhost/event-management/user/login.php`
- **Admin:** `http://localhost/event-management/admin/login.php`

---

## 🔐 Default Admin Credentials

| Field    | Value             |
|----------|-------------------|
| Email    | admin@eventms.com |
| Password | admin123          |

> You MUST run `setup_admin.php` first — otherwise admin login won't work!

---

## ❗ Why don't my events appear in Browse Events?

Events use an **approval workflow**:

1. User creates event → status = **Pending**
2. Admin logs in → Pending Approvals → clicks **Approve**
3. Event becomes **Approved** → now visible in Browse Events

To check your event's status: go to **Dashboard** → "My Created Events" table.

To quickly approve your test events:
1. Go to `http://localhost/event-management/admin/login.php`
2. Login with `admin@eventms.com` / `admin123`
3. Click **Pending Approvals** in the sidebar
4. Click **Approve**

---

## 📁 File Structure

```
event-management/
├── admin/
│   ├── login.php           Admin login
│   ├── dashboard.php       Stats overview
│   ├── events.php          View/delete all events
│   ├── approve_event.php   Approve or reject events
│   ├── attendance.php      Mark attendance by QR/reg number
│   ├── users.php           View all users
│   └── _sidebar.php        Shared sidebar
├── user/
│   ├── register.php        User sign up
│   ├── login.php           User login
│   ├── dashboard.php       Home + my events status
│   ├── view_events.php     Browse & register for events
│   ├── create_event.php    Submit new event
│   └── my_registrations.php  QR tickets
├── includes/
│   ├── db.php              PDO database connection
│   ├── auth.php            Session management & helpers
│   └── qr_generator.php   QR code generation
├── assets/
│   ├── css/style.css
│   ├── js/main.js
│   ├── uploads/            Event banner images
│   └── qrcodes/            QR ticket images
├── setup_admin.php         Run once to create admin account
├── database.sql            Full schema
└── README.md
```

---

## 🔒 Security Notes
- Passwords hashed with bcrypt (`password_hash`)
- PDO prepared statements (no SQL injection)
- Session-based auth (separate for users & admins)
- Input sanitization on all forms

---

## 🐛 Troubleshooting

| Problem | Fix |
|---------|-----|
| `session_start()` warning | Already fixed in this version — each file only calls it once via `auth.php` |
| Admin login fails | Run `setup_admin.php` first |
| Events don't show in Browse | They need admin approval first — check Dashboard → My Created Events |
| Images not uploading | Check `assets/uploads/` folder exists and XAMPP has write permission |
| QR codes missing | Check `assets/qrcodes/` exists; QR uses Google Charts API (needs internet) |
