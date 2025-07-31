<?php
/**
 * Analytics Utility Class
 */

class Analytics {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Track page view
     */
    public function trackView($editionId = null, $pageNumber = null) {
        if (!$this->isEnabled()) {
            return false;
        }
        
        $data = $this->getBasicData();
        $data['edition_id'] = $editionId;
        $data['page_number'] = $pageNumber;
        $data['event_type'] = 'view';
        
        return $this->insertEvent($data);
    }
    
    /**
     * Enhanced track download with client info
     */
    public function trackDownload($type, $id = null, $clientInfo = []) {
        if (!$this->isEnabled()) {
            return false;
        }
        
        // Handle legacy single parameter call
        if (is_numeric($type) && $id === null) {
            $id = $type;
            $type = 'edition';
        }
        
        $data = array_merge($this->getBasicData(), $clientInfo);
        $data['edition_id'] = $type === 'edition' ? $id : null;
        $data['clip_id'] = $type === 'clip' ? $id : null;
        $data['event_type'] = $type . '_download';
        
        return $this->insertEvent($data);
    }
    
    /**
     * Track share
     */
    public function trackShare($editionId = null, $pageNumber = null) {
        if (!$this->isEnabled()) {
            return false;
        }
        
        $data = $this->getBasicData();
        $data['edition_id'] = $editionId;
        $data['page_number'] = $pageNumber;
        $data['event_type'] = 'share';
        
        return $this->insertEvent($data);
    }
    
    /**
     * Track clip creation
     */
    public function trackClip($editionId, $pageNumber) {
        if (!$this->isEnabled()) {
            return false;
        }
        
        $data = $this->getBasicData();
        $data['edition_id'] = $editionId;
        $data['page_number'] = $pageNumber;
        $data['event_type'] = 'clip';
        
        return $this->insertEvent($data);
    }
    
    /**
     * Get basic tracking data
     */
    private function getBasicData() {
        return [
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'referrer' => $_SERVER['HTTP_REFERER'] ?? null,
            'device_type' => $this->getDeviceType(),
            'browser' => $this->getBrowser(),
            'os' => $this->getOS(),
            'session_id' => $this->getSessionId()
        ];
    }
    
    /**
     * Insert analytics event
     */
    private function insertEvent($data) {
        try {
            $sql = "INSERT INTO page_analytics 
                    (edition_id, page_number, event_type, ip_address, user_agent, referrer, 
                     device_type, browser, os, session_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            return $this->db->query($sql, [
                $data['edition_id'],
                $data['page_number'] ?? null,
                $data['event_type'],
                $data['ip_address'],
                $data['user_agent'],
                $data['referrer'],
                $data['device_type'],
                $data['browser'],
                $data['os'],
                $data['session_id']
            ]);
        } catch (Exception $e) {
            error_log("Analytics insert failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }
    
    /**
     * Detect device type
     */
    private function getDeviceType() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (preg_match('/tablet|ipad|android(?!.*mobile)/i', $userAgent)) {
            return 'tablet';
        } elseif (preg_match('/mobile|iphone|android/i', $userAgent)) {
            return 'mobile';
        } else {
            return 'desktop';
        }
    }
    
    /**
     * Detect browser
     */
    private function getBrowser() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (preg_match('/Chrome/i', $userAgent)) return 'Chrome';
        if (preg_match('/Firefox/i', $userAgent)) return 'Firefox';
        if (preg_match('/Safari/i', $userAgent)) return 'Safari';
        if (preg_match('/Edge/i', $userAgent)) return 'Edge';
        if (preg_match('/Opera/i', $userAgent)) return 'Opera';
        
        return 'Unknown';
    }
    
    /**
     * Detect operating system
     */
    private function getOS() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (preg_match('/Windows/i', $userAgent)) return 'Windows';
        if (preg_match('/Mac/i', $userAgent)) return 'macOS';
        if (preg_match('/Linux/i', $userAgent)) return 'Linux';
        if (preg_match('/Android/i', $userAgent)) return 'Android';
        if (preg_match('/iOS|iPhone|iPad/i', $userAgent)) return 'iOS';
        
        return 'Unknown';
    }
    
    /**
     * Get or create session ID
     */
    private function getSessionId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['analytics_id'])) {
            $_SESSION['analytics_id'] = uniqid('analytics_', true);
        }
        
        return $_SESSION['analytics_id'];
    }
    
    /**
     * Check if analytics is enabled
     */
    private function isEnabled() {
        // You can implement settings check here
        return true; // For now, always enabled
    }
    
    /**
     * Get analytics summary
     */
    public function getSummary($days = 30) {
        $sql = "SELECT 
                COUNT(*) as total_events,
                COUNT(DISTINCT session_id) as unique_visitors,
                COUNT(CASE WHEN event_type = 'view' THEN 1 END) as page_views,
                COUNT(CASE WHEN event_type = 'download' THEN 1 END) as downloads,
                COUNT(CASE WHEN event_type = 'share' THEN 1 END) as shares,
                COUNT(CASE WHEN event_type = 'clip' THEN 1 END) as clips
                FROM page_analytics 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        return $this->db->query($sql, [$days])->fetch();
    }
    
    /**
     * Get total views across all editions
     */
    public function getTotalViews() {
        $sql = "SELECT COUNT(*) as total FROM page_analytics WHERE event_type = 'view'";
        $result = $this->db->query($sql)->fetch();
        return $result ? (int)$result['total'] : 0;
    }
    
    /**
     * Get monthly views data for charts
     */
    public function getMonthlyViews($months = 12) {
        $sql = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as views
                FROM page_analytics 
                WHERE event_type = 'view' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC";
        
        return $this->db->query($sql, [$months])->fetchAll();
    }
    
    /**
     * Get monthly statistics
     */
    public function getMonthlyStats($months = 12) {
        $sql = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(CASE WHEN event_type = 'view' THEN 1 END) as views,
                COUNT(CASE WHEN event_type = 'download' THEN 1 END) as downloads,
                COUNT(DISTINCT session_id) as unique_visitors
                FROM page_analytics 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month DESC";
        
        return $this->db->query($sql, [$months])->fetchAll();
    }
    
    /**
     * Track page download specifically
     */
    public function trackPageDownload($editionId, $pageNumber, $clientInfo = []) {
        if (!$this->isEnabled()) {
            return false;
        }
        
        $data = array_merge($this->getBasicData(), $clientInfo);
        $data['edition_id'] = $editionId;
        $data['page_number'] = $pageNumber;
        $data['event_type'] = 'page_download';
        
        return $this->insertEvent($data);
    }
}
?>
