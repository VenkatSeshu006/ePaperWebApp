<?php
/**
 * Category Model Class
 */

class Category {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all categories (for admin)
     */
    public function getAll() {
        $sql = "SELECT * FROM categories ORDER BY sort_order ASC, name ASC";
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Get active categories only
     */
    public function getActive() {
        $sql = "SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order ASC, name ASC";
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Get category by slug
     */
    public function getBySlug($slug) {
        $sql = "SELECT * FROM categories WHERE slug = ?";
        return $this->db->query($sql, [$slug])->fetch();
    }
    
    /**
     * Get categories with edition counts
     */
    public function getWithCounts() {
        $sql = "SELECT c.*, 
                COUNT(DISTINCT ec.edition_id) as edition_count
                FROM categories c
                LEFT JOIN edition_categories ec ON c.id = ec.category_id
                LEFT JOIN editions e ON ec.edition_id = e.id AND e.status = 'published'
                WHERE c.status = 'active'
                GROUP BY c.id
                ORDER BY c.sort_order ASC, c.name ASC";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Create new category
     */
    public function create($data) {
        $sql = "INSERT INTO categories (name, slug, description, color, icon, sort_order, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['name'],
            $data['slug'],
            $data['description'] ?? '',
            $data['color'] ?? '#007bff',
            $data['icon'] ?? 'fas fa-folder',
            $data['sort_order'] ?? 0,
            $data['status'] ?? 'active',
            $data['created_at'] ?? date('Y-m-d H:i:s')
        ];
        
        $result = $this->db->query($sql, $params);
        return $result ? $this->db->getLastInsertId() : false;
    }
    
    /**
     * Update category
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if ($key !== 'id' && $value !== null) {
                $fields[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE categories SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Delete category
     */
    public function delete($id) {
        // First remove category associations
        $this->db->query("DELETE FROM edition_categories WHERE category_id = ?", [$id]);
        
        // Then delete the category
        return $this->db->query("DELETE FROM categories WHERE id = ?", [$id]);
    }
    
    /**
     * Get category by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM categories WHERE id = ?";
        return $this->db->query($sql, [$id])->fetch();
    }
    
    /**
     * Check if slug exists (for validation)
     */
    public function slugExists($slug, $excludeId = null) {
        $sql = "SELECT id FROM categories WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        return $this->db->query($sql, $params)->fetch() !== false;
    }
    
    /**
     * Generate unique slug from name
     */
    public function generateSlug($name, $excludeId = null) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}
?>
