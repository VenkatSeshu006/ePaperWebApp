# 🔧 E-Paper CMS Database Setup Guide

## ❌ Issue Identified
**Error:** `Unknown database 'epaper_cms'`  
**Cause:** The database hasn't been created yet

## ✅ Solution Steps

### 🚀 Quick Setup (Recommended)

1. **Open Database Setup Page**  
   Navigate to: `http://localhost/epaper-site/database-setup.html`

2. **Click "Start Database Setup"**  
   This will automatically:
   - Create the `epaper_cms` database
   - Create all required tables
   - Insert sample data
   - Create default admin user

3. **Use Default Admin Credentials**
   - **Username:** `admin`
   - **Password:** `admin123`

### 🛠️ Manual Setup (Alternative)

If the automated setup doesn't work, run these SQL commands in phpMyAdmin:

```sql
-- Create database
CREATE DATABASE epaper_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE epaper_cms;

-- Run the setup script
```
Then visit: `http://localhost/epaper-site/setup-database.php`

### 🧪 Verification Steps

1. **Test Database Connection**  
   Visit: `http://localhost/epaper-site/database-test.php`

2. **Run System Test**  
   Visit: `http://localhost/epaper-site/system-test.php`

3. **Access Admin Dashboard**  
   Visit: `http://localhost/epaper-site/admin/dashboard.php`

## 📋 Required Tables Created

- ✅ `categories` - Content categories
- ✅ `editions` - Edition management  
- ✅ `edition_categories` - Many-to-many relationships
- ✅ `edition_pages` - Individual page data
- ✅ `analytics` - Usage tracking
- ✅ `users` - Admin authentication
- ✅ `settings` - System configuration
- ✅ `clips` - Content clipping feature

## 🎯 After Setup

1. **Login to Admin Panel**
   - URL: `http://localhost/epaper-site/admin/dashboard.php`
   - Username: `admin`
   - Password: `admin123`

2. **Change Default Password**
   - Go to User Management
   - Update admin password

3. **Start Using the System**
   - Upload your first edition
   - Create custom categories
   - Explore all features

## 🔗 Quick Access Links

| Purpose | URL |
|---------|-----|
| **Database Setup** | `http://localhost/epaper-site/database-setup.html` |
| **Database Test** | `http://localhost/epaper-site/database-test.php` |
| **System Test** | `http://localhost/epaper-site/system-test.php` |
| **Admin Dashboard** | `http://localhost/epaper-site/admin/dashboard.php` |
| **Categories Management** | `http://localhost/epaper-site/admin/categories.php` |
| **Public Homepage** | `http://localhost/epaper-site/index.php` |

## ⚠️ Troubleshooting

**If setup fails:**
1. Ensure XAMPP MySQL service is running
2. Check MySQL username/password in `config/config.php`
3. Verify PHP has MySQL extension enabled
4. Check file permissions on upload directories

**Common Issues:**
- **XAMPP not running:** Start Apache and MySQL services
- **Port conflicts:** Check if MySQL is running on port 3306
- **Permission errors:** Ensure write permissions on uploads folder

## 🎉 Success Indicators

✅ Database setup page shows "Setup Complete"  
✅ Database test page shows all green checkmarks  
✅ Admin dashboard loads without errors  
✅ Categories page displays Bootstrap interface  
✅ System test shows all components working  

Your E-Paper CMS v2.0 will be fully operational after completing these steps!
