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
     * Get all editions (including drafts) for admin management
     */
    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT e.*, 
                GROUP_CONCAT(c.name SEPARATOR ', ') as categories
                FROM editions e 
                LEFT JOIN edition_categories ec ON e.id = ec.edition_id
                LEFT JOIN categories c ON ec.category_id = c.id
                GROUP BY e.id 
                ORDER BY e.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Create new edition
     */
    public function create($data) {
        $sql = "INSERT INTO editions (title, description, date, pdf_path, thumbnail_path, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $data['title'],
            $data['description'] ?? '',
            $data['date'],
            $data['pdf_path'],
            $data['thumbnail_path'] ?? '',
            $data['status'] ?? 'draft',
            $data['created_at'] ?? date('Y-m-d H:i:s')
        ];
        
        $result = $this->db->query($sql, $params);
        return $result ? $this->db->getLastInsertId() : false;
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
