<?php
/**
 * Clip Model Class
 * Handles clipping functionality
 */

class Clip {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new clip
     */
    public function create($data) {
        $conn = $this->db->getConnection();
        $sql = "INSERT INTO clips (edition_id, page_number, title, description, image_path, 
                x, y, width, height) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $data['edition_id'],
            $data['page_number'],
            $data['title'] ?? null,
            $data['description'] ?? null,
            $data['image_path'],
            $data['x'],
            $data['y'],
            $data['width'],
            $data['height']
        ]);
        
        return $conn->lastInsertId();
    }
    
    /**
     * Get clip by ID
     */
    public function getById($id) {
        $sql = "SELECT c.*, e.title as edition_title, e.date as edition_date
                FROM clips c
                JOIN editions e ON c.edition_id = e.id
                WHERE c.id = ? AND c.status = 'active'";
        
        return $this->db->query($sql, [$id])->fetch();
    }
    
    /**
     * Get recent clips
     */
    public function getRecent($limit = 10) {
        $sql = "SELECT c.*, e.title as edition_title, e.date as edition_date
                FROM clips c
                JOIN editions e ON c.edition_id = e.id
                WHERE c.status = 'active'
                ORDER BY c.created_at DESC
                LIMIT ?";
        
        return $this->db->query($sql, [$limit])->fetchAll();
    }
    
    /**
     * Get clips for an edition
     */
    public function getByEdition($editionId, $limit = 20) {
        $sql = "SELECT * FROM clips 
                WHERE edition_id = ? AND status = 'active'
                ORDER BY created_at DESC
                LIMIT ?";
        
        return $this->db->query($sql, [$editionId, $limit])->fetchAll();
    }
    
    /**
     * Increment view count
     */
    public function incrementViews($id) {
        $sql = "UPDATE clips SET views = views + 1 WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    /**
     * Increment share count
     */
    public function incrementShares($id) {
        $sql = "UPDATE clips SET shares = shares + 1 WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    /**
     * Generate unique filename for clip
     */
    public function generateFilename($editionId, $pageNumber) {
        return 'clip_' . $editionId . '_' . $pageNumber . '_' . uniqid() . '.jpg';
    }
}
?>
