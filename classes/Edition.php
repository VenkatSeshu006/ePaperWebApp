<?php
/**
 * Edition Model Class
 * Handles all edition-related database operations
 */

class Edition {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all published editions with pagination
     */
    public function getPublished($page = 1, $limit = ITEMS_PER_PAGE, $category = null) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT e.*, 
                GROUP_CONCAT(c.name SEPARATOR ', ') as categories,
                GROUP_CONCAT(c.color SEPARATOR ',') as category_colors
                FROM editions e 
                LEFT JOIN edition_categories ec ON e.id = ec.edition_id
                LEFT JOIN categories c ON ec.category_id = c.id
                WHERE e.status = 'published'";
        
        $params = [];
        
        if ($category) {
            $sql .= " AND c.slug = ?";
            $params[] = $category;
        }
        
        $sql .= " GROUP BY e.id ORDER BY e.date DESC, e.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    /**
     * Get latest published edition
     */
    public function getLatest() {
        $sql = "SELECT e.*, 
                GROUP_CONCAT(c.name SEPARATOR ', ') as categories
                FROM editions e 
                LEFT JOIN edition_categories ec ON e.id = ec.edition_id
                LEFT JOIN categories c ON ec.category_id = c.id
                WHERE e.status = 'published'
                GROUP BY e.id 
                ORDER BY e.date DESC, e.created_at DESC 
                LIMIT 1";
        
        $result = $this->db->query($sql)->fetch();
        return $result ?: null;
    }
    
    /**
     * Get edition by ID
     */
    public function getById($id) {
        $sql = "SELECT e.*, 
                GROUP_CONCAT(c.name SEPARATOR ', ') as categories,
                u.full_name as created_by_name
                FROM editions e 
                LEFT JOIN edition_categories ec ON e.id = ec.edition_id
                LEFT JOIN categories c ON ec.category_id = c.id
                LEFT JOIN admin_users u ON e.created_by = u.id
                WHERE e.id = ? AND e.status = 'published'
                GROUP BY e.id";
        
        return $this->db->query($sql, [$id])->fetch();
    }
    
    /**
     * Get edition by ID (admin version - gets all statuses)
     */
    public function getByIdAdmin($id) {
        $sql = "SELECT e.*, 
                GROUP_CONCAT(c.name SEPARATOR ', ') as categories,
                u.full_name as created_by_name
                FROM editions e 
                LEFT JOIN edition_categories ec ON e.id = ec.edition_id
                LEFT JOIN categories c ON ec.category_id = c.id
                LEFT JOIN admin_users u ON e.created_by = u.id
                WHERE e.id = ?
                GROUP BY e.id";
        
        return $this->db->query($sql, [$id])->fetch();
    }
    
    /**
     * Get edition by slug
     */
    public function getBySlug($slug) {
        $sql = "SELECT e.*, 
                GROUP_CONCAT(c.name SEPARATOR ', ') as categories
                FROM editions e 
                LEFT JOIN edition_categories ec ON e.id = ec.edition_id
                LEFT JOIN categories c ON ec.category_id = c.id
                WHERE e.slug = ? AND e.status = 'published'
                GROUP BY e.id";
        
        return $this->db->query($sql, [$slug])->fetch();
    }
    
    /**
     * Get edition pages
     */
    public function getPages($editionId) {
        $sql = "SELECT * FROM edition_pages 
                WHERE edition_id = ? 
                ORDER BY page_number ASC";
        
        return $this->db->query($sql, [$editionId])->fetchAll();
    }
    
    /**
     * Get featured editions
     */
    public function getFeatured($limit = 6) {
        $sql = "SELECT e.*, 
                GROUP_CONCAT(c.name SEPARATOR ', ') as categories
                FROM editions e 
                LEFT JOIN edition_categories ec ON e.id = ec.edition_id
                LEFT JOIN categories c ON ec.category_id = c.id
                WHERE e.status = 'published' AND e.featured = 1
                GROUP BY e.id 
                ORDER BY e.date DESC 
                LIMIT ?";
        
        return $this->db->query($sql, [$limit])->fetchAll();
    }
    
    /**
     * Search editions
     */
    public function search($query, $page = 1, $limit = ITEMS_PER_PAGE) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT e.*, 
                GROUP_CONCAT(c.name SEPARATOR ', ') as categories,
                MATCH(e.title, e.description) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                FROM editions e 
                LEFT JOIN edition_categories ec ON e.id = ec.edition_id
                LEFT JOIN categories c ON ec.category_id = c.id
                WHERE e.status = 'published' 
                AND (MATCH(e.title, e.description) AGAINST(? IN NATURAL LANGUAGE MODE)
                     OR e.title LIKE ? OR e.description LIKE ?)
                GROUP BY e.id 
                ORDER BY relevance DESC, e.date DESC 
                LIMIT ? OFFSET ?";
        
        $searchTerm = "%{$query}%";
        return $this->db->query($sql, [$query, $query, $searchTerm, $searchTerm, $limit, $offset])->fetchAll();
    }
    
    /**
     * Get total count for pagination
     */
    public function getCount($category = null) {
        $sql = "SELECT COUNT(DISTINCT e.id) as total FROM editions e";
        $params = [];
        
        if ($category) {
            $sql .= " JOIN edition_categories ec ON e.id = ec.edition_id
                      JOIN categories c ON ec.category_id = c.id
                      WHERE e.status = 'published' AND c.slug = ?";
            $params[] = $category;
        } else {
            $sql .= " WHERE e.status = 'published'";
        }
        
        $result = $this->db->query($sql, $params)->fetch();
        return $result['total'] ?? 0;
    }
    
    /**
     * Increment view count
     */
    public function incrementViews($id) {
        $sql = "UPDATE editions SET views = views + 1 WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    /**
     * Increment download count
     */
    public function incrementDownloads($id) {
        $sql = "UPDATE editions SET downloads = downloads + 1 WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    /**
     * Get total count of published editions
     */
    public function getTotalCount() {
        $sql = "SELECT COUNT(*) as total FROM editions WHERE status = 'published'";
        $result = $this->db->query($sql)->fetch();
        return $result ? (int)$result['total'] : 0;
    }
    
    /**
     * Get total count of all editions (for admin interface)
     */
    public function getTotalCountAll() {
        $sql = "SELECT COUNT(*) as total FROM editions";
        $result = $this->db->query($sql)->fetch();
        return $result ? (int)$result['total'] : 0;
    }
    
    /**
     * Get all editions (including drafts) for admin management
     */
    public function getAll($limit = null, $offset = 0) {
        // Simplified query without categories join for debugging
        $sql = "SELECT * FROM editions ORDER BY created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Create new edition
     */
    public function create($data) {
        $sql = "INSERT INTO editions (title, slug, description, date, thumbnail_path, pdf_path, total_pages, file_size, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        // Handle date field - check for both 'date' and 'publication_date' keys
        $date = null;
        if (isset($data['date'])) {
            $date = $data['date'];
        } elseif (isset($data['publication_date'])) {
            $date = $data['publication_date'];
        } else {
            $date = date('Y-m-d'); // Default to current date
        }

        // Generate slug from title
        $slug = $this->generateSlug($data['title']);
        
        $params = [
            $data['title'],
            $slug,
            $data['description'] ?? '',
            $date,
            $data['thumbnail_path'] ?? '', // Maps to thumbnail_path in database
            $data['pdf_path'] ?? $data['pdf_file'] ?? '', // Support both names, prioritize pdf_path
            $data['total_pages'] ?? 0,
            $data['file_size'] ?? 0,
            $data['status'] ?? 'published' // Default to published
        ];
        
        $result = $this->db->query($sql, $params);
        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * Generate URL-friendly slug from title
     */
    private function generateSlug($title) {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Ensure uniqueness
        $originalSlug = $slug;
        $counter = 1;
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Check if slug already exists
     */
    private function slugExists($slug) {
        $sql = "SELECT COUNT(*) FROM editions WHERE slug = ?";
        $result = $this->db->query($sql, [$slug])->fetch();
        return $result['COUNT(*)'] > 0;
    }
    
    /**
     * Update edition
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE editions SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Delete edition
     */
    public function delete($id) {
        // First delete associated data
        $this->db->query("DELETE FROM edition_categories WHERE edition_id = ?", [$id]);
        $this->db->query("DELETE FROM edition_pages WHERE edition_id = ?", [$id]);
        $this->db->query("DELETE FROM clips WHERE edition_id = ?", [$id]);
        
        // Then delete the edition
        return $this->db->query("DELETE FROM editions WHERE id = ?", [$id]);
    }
}
?>
