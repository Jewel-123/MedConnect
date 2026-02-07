<?php
/**
 * NLP Symptom Analyzer
 * Analyzes patient symptoms to determine specialty and urgency
 */

require_once 'db.php';

class SymptomAnalyzer {
    private $conn;
    private $keywords = [];
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        $this->loadKeywords();
    }
    
    /**
     * Load symptom keywords from database
     */
    private function loadKeywords() {
        $result = $this->conn->query("SELECT * FROM symptom_keywords");
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $this->keywords[] = $row;
            }
        }
    }
    
    /**
     * Analyze symptoms and return specialty, urgency, and score
     * @param string $symptoms Patient's symptom description
     * @return array Analysis results
     */
    public function analyze($symptoms) {
        $symptoms = strtolower(trim($symptoms));
        
        $matches = [];
        $maxUrgency = 0;
        $specialties = [];
        
        // Find matching keywords
        foreach ($this->keywords as $keyword) {
            $keywordLower = strtolower($keyword['keyword']);
            
            // Check if keyword exists in symptoms
            if (stripos($symptoms, $keywordLower) !== false) {
                $matches[] = $keyword;
                
                // Track max urgency
                if ($keyword['urgency_score'] > $maxUrgency) {
                    $maxUrgency = $keyword['urgency_score'];
                }
                
                // Collect specialties
                if ($keyword['specialty'] && !in_array($keyword['specialty'], $specialties)) {
                    $specialties[] = $keyword['specialty'];
                }
            }
        }
        
        // Determine primary specialty (most common or highest urgency)
        $primarySpecialty = $this->determinePrimarySpecialty($matches);
        
        // Determine urgency level
        $urgencyLevel = $this->getUrgencyLevel($maxUrgency);
        
        // Get recommended doctor based on specialty
        $recommendedDoctor = $this->getRecommendedDoctor($primarySpecialty);
        
        return [
            'matched_keywords' => array_column($matches, 'keyword'),
            'urgency_score' => $maxUrgency > 0 ? $maxUrgency : 50, // Default to 50 if no matches
            'urgency_level' => $urgencyLevel,
            'primary_specialty' => $primarySpecialty,
            'all_specialties' => $specialties,
            'recommended_doctor' => $recommendedDoctor,
            'is_emergency' => $maxUrgency >= 90
        ];
    }
    
    /**
     * Determine primary specialty from matches
     */
    private function determinePrimarySpecialty($matches) {
        if (empty($matches)) {
            return 'General Physician'; // Default
        }
        
        // Count specialty occurrences
        $specialtyCounts = [];
        $specialtyMaxUrgency = [];
        
        foreach ($matches as $match) {
            $specialty = $match['specialty'] ?? 'General Physician';
            
            if (!isset($specialtyCounts[$specialty])) {
                $specialtyCounts[$specialty] = 0;
                $specialtyMaxUrgency[$specialty] = 0;
            }
            
            $specialtyCounts[$specialty]++;
            $specialtyMaxUrgency[$specialty] = max($specialtyMaxUrgency[$specialty], $match['urgency_score']);
        }
        
        // Sort by count, then by urgency
        $specialties = array_keys($specialtyCounts);
        usort($specialties, function($keyA, $keyB) use ($specialtyCounts, $specialtyMaxUrgency) {
            if ($specialtyCounts[$keyA] === $specialtyCounts[$keyB]) {
                return $specialtyMaxUrgency[$keyB] - $specialtyMaxUrgency[$keyA];
            }
            return $specialtyCounts[$keyB] - $specialtyCounts[$keyA];
        });
        
        return $specialties[0];
    }
    
    /**
     * Get urgency level from score
     */
    private function getUrgencyLevel($score) {
        if ($score >= 90) return 'emergency';
        if ($score >= 70) return 'urgent';
        return 'routine';
    }
    
    /**
     * Get recommended doctor based on specialty
     */
    private function getRecommendedDoctor($specialty) {
        // Query for an available doctor with the matching specialty
        $stmt = $this->conn->prepare("
            SELECT u.full_name 
            FROM users u
            INNER JOIN doctor_profiles dp ON u.id = dp.user_id
            WHERE u.role = 'doctor' 
            AND dp.specialization = ?
            LIMIT 1
        ");
        
        $stmt->bind_param("s", $specialty);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['full_name'];
        }
        
        // If no exact match, try to find any available doctor
        $generalQuery = $this->conn->query("
            SELECT u.full_name 
            FROM users u
            WHERE u.role = 'doctor'
            LIMIT 1
        ");
        
        if ($generalQuery && $row = $generalQuery->fetch_assoc()) {
            return $row['full_name'];
        }
        
        return 'Available Doctor';
    }
    
    /**
     * Get symptom summary (first matched keywords)
     */
    public function getSummary($symptoms, $maxKeywords = 3) {
        $analysis = $this->analyze($symptoms);
        $keywords = array_slice($analysis['matched_keywords'], 0, $maxKeywords);
        
        if (empty($keywords)) {
            // Return first few words of symptoms
            $words = explode(' ', $symptoms);
            return implode(' ', array_slice($words, 0, 5)) . '...';
        }
        
        return implode(', ', $keywords);
    }
}

/**
 * Helper function to analyze symptoms
 * @param string $symptoms Symptom description
 * @return array Analysis results
 */
function analyzeSymptoms($symptoms) {
    global $conn;
    $analyzer = new SymptomAnalyzer($conn);
    return $analyzer->analyze($symptoms);
}