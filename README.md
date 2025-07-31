# E-Paper CMS v2.0 🗞️

A modern, responsive digital newspaper content management system with comprehensive sharing capabilities, built with PHP, MySQL, and JavaScript.

## 🚀 Features

### User Experience
- **📱 Responsive Design** - Perfect viewing on desktop, tablet, and mobile
- **🖼️ Interactive Viewer** - Smooth page navigation with thumbnail sidebar
- **🔍 Zoom Controls** - Zoom in/out and fit-to-width functionality
- **✂️ Advanced Clipping Tool** - Crop and save article clips with metadata
- **⌨️ Keyboard Navigation** - Arrow keys, Home, End for easy browsing
- **📦 Archive Browser** - Browse all published editions
- **🔗 Share Functionality** - Native sharing API support

### Admin Features
- **🎯 Modern Dashboard** - Clean interface with key statistics
- **📄 Easy Upload** - Simple PDF upload with automatic processing
- **📊 Content Management** - Full CRUD operations for editions
- **📈 Analytics** - Track views, downloads, and engagement
- **⚙️ System Settings** - Configurable site preferences
- **🔐 Authentication** - Simple admin login system

### Technical Excellence
- **🏗️ Modern Architecture** - MVC pattern with proper separation of concerns
- **🛡️ Security** - Input validation, SQL injection prevention, XSS protection
- **⚡ Performance** - Optimized queries, image compression, efficient caching
- **🔧 Maintainability** - Clean code structure, comprehensive error handling

## 📋 Requirements

- **PHP 7.4+** with MySQLi and PDO extensions
- **MySQL 5.7+** or MariaDB 10.2+
- **Web Server** (Apache, Nginx, or similar)
- **Modern Browser** with JavaScript enabled

## 🛠️ Installation

### 1. Database Setup
```sql
-- Import the database schema
mysql -u username -p database_name < database.sql
```

### 2. Configuration
Edit `config/config.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'your_database');
```

### 3. File Permissions
```bash
chmod 755 uploads/
chmod 755 cache/
chmod 755 temp/
```

### 4. Access
- **Frontend**: `http://your-domain.com/`
- **Admin**: `http://your-domain.com/admin/dashboard.php`
- **Default Login**: `admin` / `admin123`

## 📁 Project Structure

```
├── 📄 index.php                 # Main viewer application
├── 📄 database.sql             # Complete database schema
├── 📁 admin/                   # Administrative interface
│   ├── dashboard.php           # Admin dashboard
│   ├── upload.php              # Upload new editions
│   ├── manage_editions.php     # Edition management
│   └── settings.php            # System settings
├── 📁 api/                     # REST API endpoints
│   ├── save-clip.php           # Clip saving endpoint
│   └── track-download.php      # Analytics tracking
├── 📁 assets/                  # Static resources
│   └── css/style.css           # Modern responsive CSS
├── 📁 classes/                 # Model classes
│   ├── Edition.php             # Edition management
│   ├── Category.php            # Category system
│   ├── Clip.php                # Clipping functionality
│   ├── Analytics.php           # Statistics tracking
│   └── ImageProcessor.php      # Image utilities
├── 📁 config/                  # Configuration
│   └── config.php              # Database and app settings
├── 📁 includes/                # Core utilities
│   └── database.php            # Database connection
├── 📁 templates/               # Template files
│   └── maintenance.php         # Error handling
└── 📁 uploads/                 # File storage
    ├── clips/                  # Saved clip images
    └── [date]/                 # Edition files by date
```

## 🎯 Usage

### For Administrators

#### Upload New Edition
1. Login to admin dashboard
2. Click "Upload New Edition"
3. Fill in title, description, and date
4. Select PDF file (max 50MB)
5. Click "Upload Edition"

#### Manage Content
- **View All Editions**: Access through "Manage Editions"
- **Edit Status**: Toggle between Published/Draft
- **Delete Editions**: Remove unwanted content
- **View Analytics**: Track engagement metrics

### For Users

#### Navigate Editions
- **Browse Pages**: Use thumbnail sidebar or navigation buttons
- **Zoom Control**: Use toolbar zoom buttons or mouse wheel
- **Keyboard Shortcuts**: Arrow keys for navigation, Esc to close modals

#### Create Clips
1. Click the "Clip Tool" button
2. Select area to crop
3. Click "Save" to store the clip
4. Clips are automatically saved with metadata

## 🔧 Configuration

### Database Settings
All database configuration is in `config/config.php`:
```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'epaper_cms');
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('ITEMS_PER_PAGE', 10);
define('THUMBNAIL_WIDTH', 200);
define('THUMBNAIL_HEIGHT', 280);
```

### Upload Limits
Adjust in `admin/settings.php` or modify PHP settings:
```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
```

## 📊 Database Schema

### Core Tables
- **editions** - Main edition storage
- **edition_pages** - Individual page data
- **clips** - User-generated clips
- **categories** - Content categorization
- **page_analytics** - View tracking
- **settings** - System configuration

### Key Relationships
- Editions → Pages (1:N)
- Editions → Clips (1:N)
- Editions → Categories (N:M)
- Analytics → Editions (N:1)

## 🛡️ Security Features

- **Input Validation** - All user inputs sanitized and validated
- **SQL Injection Prevention** - Prepared statements throughout
- **XSS Protection** - Output escaping for all dynamic content
- **File Upload Security** - Type validation and safe file handling
- **Session Management** - Secure admin authentication

## 🚀 Performance Optimizations

- **Database Indexing** - Optimized queries with proper indexes
- **Image Optimization** - Automatic compression and thumbnails
- **Caching System** - File-based caching for improved response times
- **Lazy Loading** - Images loaded on demand
- **Minified Assets** - Compressed CSS and JavaScript

## 🔄 API Endpoints

### Save Clip
```http
POST /api/save-clip.php
Content-Type: application/json

{
  "edition_id": 1,
  "page_number": 1,
  "image_data": "data:image/jpeg;base64,...",
  "crop_data": {
    "x": 100,
    "y": 50,
    "width": 300,
    "height": 200
  },
  "title": "Article Title",
  "description": "Article description"
}
```

### Track Download
```http
POST /api/track-download.php
Content-Type: application/json

{
  "type": "edition",
  "id": 1
}
```

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📝 License

This project is open source and available under the [MIT License](LICENSE).

## 🆘 Troubleshooting

### Common Issues

#### Database Connection Error
- Verify database credentials in `config/config.php`
- Ensure MySQL service is running
- Check database exists and user has permissions

#### File Upload Fails
- Check PHP upload limits (`upload_max_filesize`, `post_max_size`)
- Verify `uploads/` directory permissions (755)
- Ensure adequate disk space

#### Cropper Tool Not Working
- Verify Cropper.js is loaded
- Check browser console for JavaScript errors
- Ensure modern browser with Canvas support

### Debug Mode
Enable debug mode in `config/config.php`:
```php
define('DEBUG_MODE', true);
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📞 Support

For support and questions:
- Check the troubleshooting section above
- Review the code comments for implementation details
- Create an issue for bugs or feature requests

---

**Digital E-Paper CMS v2.0** - Built with ❤️ for modern digital publishing
