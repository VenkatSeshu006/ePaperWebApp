#!/bin/bash

# E-Paper CMS v2.0 - Project Synchronization Script
# This script ensures all components are properly synchronized

echo "🚀 E-Paper CMS v2.0 - Project Synchronization"
echo "=============================================="
echo ""

# Check if we're in the right directory
if [ ! -f "index.php" ] || [ ! -f "config.php" ]; then
    echo "❌ Error: Please run this script from the E-Paper CMS root directory"
    exit 1
fi

echo "✅ Found E-Paper CMS installation"
echo ""

# 1. Create required directories
echo "📁 Creating required directories..."
mkdir -p uploads/{clips,temp,thumbnails}
mkdir -p cache
mkdir -p logs
mkdir -p admin/backup_files
echo "✅ Directories created"
echo ""

# 2. Set proper permissions
echo "🔐 Setting file permissions..."
chmod 755 uploads/ cache/ logs/
chmod 644 *.php
chmod 644 admin/*.php
chmod 644 api/*.php
chmod 644 includes/*.php
chmod 644 assets/css/*.css
chmod 644 assets/js/*.js
echo "✅ Permissions set"
echo ""

# 3. Validate file structure
echo "🔍 Validating file structure..."

# Core files
CORE_FILES=(
    "index.php"
    "config.php" 
    "setup.php"
    "system-check.php"
    "save_clip.php"
    "manifest.json"
    "README.md"
    "PROJECT_SYNC.md"
)

for file in "${CORE_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "  ✅ $file"
    else
        echo "  ❌ Missing: $file"
    fi
done

# Admin files
ADMIN_FILES=(
    "admin/dashboard.php"
    "admin/upload.php"
    "admin/login.php"
    "admin/settings.php"
    "admin/clips.php"
)

for file in "${ADMIN_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "  ✅ $file"
    else
        echo "  ❌ Missing: $file"
    fi
done

# API files
API_FILES=(
    "api/edition-data.php"
    "api/search.php"
    "api/track-view.php"
)

for file in "${API_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "  ✅ $file"
    else
        echo "  ❌ Missing: $file"
    fi
done

# Include files
INCLUDE_FILES=(
    "includes/database.php"
    "includes/auth.php"
    "includes/crud_operations.php"
)

for file in "${INCLUDE_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "  ✅ $file"
    else
        echo "  ❌ Missing: $file"
    fi
done

# Asset files
ASSET_FILES=(
    "assets/css/style.css"
    "assets/js/app.js"
)

for file in "${ASSET_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "  ✅ $file"
    else
        echo "  ❌ Missing: $file"
    fi
done

echo ""

# 4. Check PHP syntax
echo "🔧 Checking PHP syntax..."
for phpfile in $(find . -name "*.php" -not -path "./vendor/*"); do
    if php -l "$phpfile" > /dev/null 2>&1; then
        echo "  ✅ $phpfile - Syntax OK"
    else
        echo "  ❌ $phpfile - Syntax Error"
        php -l "$phpfile"
    fi
done
echo ""

# 5. Verify configuration
echo "⚙️ Verifying configuration..."

# Check if config.php has required constants
if grep -q "APP_VERSION" config.php; then
    echo "  ✅ Configuration constants defined"
else
    echo "  ❌ Missing configuration constants in config.php"
fi

# Check database configuration
if [ -f "includes/database.php" ]; then
    if grep -q "getConnection" includes/database.php; then
        echo "  ✅ Database connection function found"
    else
        echo "  ❌ Database connection function missing"
    fi
else
    echo "  ❌ Database configuration file missing"
fi

echo ""

# 6. Generate integrity report
echo "📊 Generating integrity report..."
{
    echo "# E-Paper CMS v2.0 - Integrity Report"
    echo "Generated: $(date)"
    echo ""
    echo "## File Count Summary"
    echo "- PHP files: $(find . -name "*.php" | wc -l)"
    echo "- CSS files: $(find . -name "*.css" | wc -l)"
    echo "- JS files: $(find . -name "*.js" | wc -l)"
    echo "- Total files: $(find . -type f | wc -l)"
    echo ""
    echo "## Directory Structure"
    tree -I 'node_modules|vendor|.git' . || find . -type d | sort
    echo ""
    echo "## File Checksums"
    find . -type f -name "*.php" -o -name "*.js" -o -name "*.css" | sort | xargs md5sum
} > integrity-report.txt

echo "✅ Integrity report saved to integrity-report.txt"
echo ""

# 7. Final validation
echo "🎯 Final validation..."

# Check if system-check.php exists and is accessible
if [ -f "system-check.php" ]; then
    echo "  ✅ System health check available at: system-check.php"
else
    echo "  ❌ System health check missing"
fi

# Check if main viewer is accessible
if [ -f "index.php" ]; then
    echo "  ✅ Main viewer available at: index.php"
else
    echo "  ❌ Main viewer missing"
fi

# Check if admin panel is accessible
if [ -f "admin/dashboard.php" ]; then
    echo "  ✅ Admin panel available at: admin/"
else
    echo "  ❌ Admin panel missing"
fi

echo ""
echo "🎉 Project Synchronization Complete!"
echo ""
echo "📋 Next Steps:"
echo "1. Run: php system-check.php (to verify system health)"
echo "2. Visit: admin/ (to access admin panel)"
echo "3. Upload your first PDF edition"
echo "4. View at: index.php"
echo ""
echo "🔗 Quick Links:"
echo "- Health Check: http://localhost/epaper-site/system-check.php"
echo "- Admin Panel: http://localhost/epaper-site/admin/"
echo "- Main Viewer: http://localhost/epaper-site/index.php"
echo ""
echo "✨ E-Paper CMS v2.0 is ready for use!"
