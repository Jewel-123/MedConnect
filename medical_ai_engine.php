<?php
/**
 * Medical AI Engine
 * Advanced symptom-matching and triage assistant with strict safety controls
 * 
 * This engine implements a 9-step workflow:
 * 1. Symptom Extraction
 * 2. Symptom Normalization
 * 3. Context Awareness
 * 4. Red-Flag & Urgency Check
 * 5. Condition Matching (Differential Analysis)
 * 6. Explain the Match
 * 7. Confidence Scoring
 * 8. Clarifying Questions
 * 9. Safety Rules
 */

require_once 'db.php';

class MedicalAIEngine {
    private $conn;
    private $rawSymptoms;
    private $extractedSymptoms = [];
    private $normalizedSymptoms = [];
    private $context = [];
    private $redFlags = [];
    private $matchedConditions = [];
    private $clarifyingQuestions = [];
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }
    
    /**
     * Main analysis method - executes the full 9-step workflow
     * 
     * @param string $symptoms Raw symptom description from user
     * @param array $context Optional context (age, gender, existing_conditions, etc.)
     * @return array Structured analysis result
     */
    public function analyze($symptoms, $context = []) {
        $this->rawSymptoms = $symptoms;
        $this->context = $context;
        
        // Step 1: Extract symptoms
        $this->extractSymptoms();
        
        // Step 2: Normalize symptoms
        $this->normalizeSymptoms();
        
        // Step 3: Context awareness (already set)
        
        // Step 4: Red-flag detection
        $this->detectRedFlags();
        
        // Step 5: Condition matching
        $this->matchConditions();
        
        // Step 6 & 7: Explain matches and score confidence (done in matchConditions)
        
        // Step 8: Generate clarifying questions
        $this->generateClarifyingQuestions();
        
        // Step 9: Format output with safety rules
        return $this->formatOutput();
    }
    
    /**
     * STEP 1: Extract symptoms from raw input
     * Identifies individual symptoms and their characteristics
     */
    private function extractSymptoms() {
        $text = strtolower($this->rawSymptoms);
        
        // Get all known symptom keywords from database
        $result = $this->conn->query("
            SELECT DISTINCT symptom_name FROM condition_symptoms
            UNION
            SELECT DISTINCT symptom_keyword FROM red_flag_symptoms
            UNION
            SELECT DISTINCT informal_term FROM symptom_normalizations
        ");
        
        $knownSymptoms = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $knownSymptoms[] = $row[array_keys($row)[0]];
            }
        }
        
        // Extract symptoms found in text
        foreach ($knownSymptoms as $symptom) {
            if (stripos($text, strtolower($symptom)) !== false) {
                $this->extractedSymptoms[] = [
                    'symptom' => $symptom,
                    'onset' => $this->extractOnset($text),
                    'duration' => $this->extractDuration($text),
                    'severity' => $this->extractSeverity($text),
                    'location' => $this->extractLocation($text, $symptom),
                    'frequency' => 'Not Provided',
                    'triggers' => 'Not Provided',
                    'relieving_factors' => 'Not Provided'
                ];
            }
        }
        
        // If no specific symptoms found, mark as general description
        if (empty($this->extractedSymptoms)) {
            $this->extractedSymptoms[] = [
                'symptom' => 'general symptoms',
                'onset' => 'Not Provided',
                'duration' => $this->extractDuration($text),
                'severity' => $this->extractSeverity($text),
                'location' => 'Not Provided',
                'frequency' => 'Not Provided',
                'triggers' => 'Not Provided',
                'relieving_factors' => 'Not Provided'
            ];
        }
    }
    
    /**
     * STEP 2: Normalize symptoms to medical terminology
     */
    private function normalizeSymptoms() {
        foreach ($this->extractedSymptoms as $extracted) {
            $symptom = $extracted['symptom'];
            
            // Check if normalization exists
            $stmt = $this->conn->prepare("
                SELECT medical_term, category 
                FROM symptom_normalizations 
                WHERE informal_term = ? OR synonyms LIKE ?
                LIMIT 1
            ");
            $likePattern = "%$symptom%";
            $stmt->bind_param("ss", $symptom, $likePattern);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $this->normalizedSymptoms[] = [
                    'original' => $symptom,
                    'medical_term' => $row['medical_term'],
                    'category' => $row['category'],
                    'details' => $extracted
                ];
            } else {
                // No normalization found, use as-is
                $this->normalizedSymptoms[] = [
                    'original' => $symptom,
                    'medical_term' => $symptom,
                    'category' => 'general',
                    'details' => $extracted
                ];
            }
            
            $stmt->close();
        }
    }
    
    /**
     * STEP 4: Detect red-flag symptoms requiring urgent care
     */
    private function detectRedFlags() {
        $text = strtolower($this->rawSymptoms);
        
        $result = $this->conn->query("
            SELECT * FROM red_flag_symptoms 
            ORDER BY urgency_level DESC
        ");
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $keyword = strtolower($row['symptom_keyword']);
                
                if (stripos($text, $keyword) !== false) {
                    // Check context requirements if any
                    $contextRequired = json_decode($row['context_required'], true);
                    $contextMatch = true;
                    
                    if ($contextRequired) {
                        if (isset($contextRequired['age_over']) && 
                            (!isset($this->context['age']) || $this->context['age'] < $contextRequired['age_over'])) {
                            $contextMatch = false;
                        }
                        if (isset($contextRequired['gender']) && 
                            (!isset($this->context['gender']) || $this->context['gender'] !== $contextRequired['gender'])) {
                            $contextMatch = false;
                        }
                    }
                    
                    if ($contextMatch) {
                        $this->redFlags[] = [
                            'symptom' => $row['symptom_keyword'],
                            'urgency_level' => $row['urgency_level'],
                            'warning_message' => $row['warning_message'],
                            'recommended_action' => $row['recommended_action'],
                            'associated_conditions' => $row['associated_conditions']
                        ];
                    }
                }
            }
        }
    }
    
    /**
     * STEP 5-7: Match conditions and calculate confidence scores
     */
    private function matchConditions() {
        $conditionScores = [];
        
        // Get all normalized symptom terms
        $symptomTerms = array_column($this->normalizedSymptoms, 'medical_term');
        
        if (empty($symptomTerms)) {
            return;
        }
        
        // Find conditions that match these symptoms
        $placeholders = implode(',', array_fill(0, count($symptomTerms), '?'));
        $stmt = $this->conn->prepare("
            SELECT 
                mc.id,
                mc.condition_name,
                mc.description,
                mc.specialty,
                mc.severity_level,
                mc.requires_immediate_care,
                cs.symptom_name,
                cs.likelihood_score,
                cs.is_primary_symptom,
                cs.is_required
            FROM medical_conditions mc
            INNER JOIN condition_symptoms cs ON mc.id = cs.condition_id
            WHERE cs.symptom_name IN ($placeholders)
        ");
        
        $stmt->bind_param(str_repeat('s', count($symptomTerms)), ...$symptomTerms);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Calculate scores for each condition
        while ($row = $result->fetch_assoc()) {
            $conditionId = $row['id'];
            
            if (!isset($conditionScores[$conditionId])) {
                $conditionScores[$conditionId] = [
                    'condition_name' => $row['condition_name'],
                    'description' => $row['description'],
                    'specialty' => $row['specialty'],
                    'severity_level' => $row['severity_level'],
                    'requires_immediate_care' => $row['requires_immediate_care'],
                    'matched_symptoms' => [],
                    'total_score' => 0,
                    'symptom_count' => 0
                ];
            }
            
            $conditionScores[$conditionId]['matched_symptoms'][] = [
                'symptom' => $row['symptom_name'],
                'likelihood' => $row['likelihood_score'],
                'is_primary' => $row['is_primary_symptom']
            ];
            
            // Weight primary symptoms more heavily
            $weight = $row['is_primary_symptom'] ? 1.5 : 1.0;
            $conditionScores[$conditionId]['total_score'] += $row['likelihood_score'] * $weight;
            $conditionScores[$conditionId]['symptom_count']++;
        }
        
        $stmt->close();
        
        // Calculate confidence scores and get missing symptoms
        foreach ($conditionScores as $conditionId => &$condition) {
            // Average score weighted by number of matched symptoms
            $avgScore = $condition['total_score'] / max($condition['symptom_count'], 1);
            
            // Boost score if multiple symptoms match
            $matchBonus = min($condition['symptom_count'] * 5, 20);
            
            // Calculate final confidence (0-100%)
            $confidence = min(100, round($avgScore + $matchBonus));
            
            // Get expected symptoms for this condition
            $stmt = $this->conn->prepare("
                SELECT symptom_name, is_primary_symptom, is_required
                FROM condition_symptoms
                WHERE condition_id = ?
            ");
            $stmt->bind_param("i", $conditionId);
            $stmt->execute();
            $allSymptoms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            // Find missing symptoms
            $matchedSymptomNames = array_column($condition['matched_symptoms'], 'symptom');
            $missingSymptoms = [];
            
            foreach ($allSymptoms as $symptom) {
                if (!in_array($symptom['symptom_name'], $matchedSymptomNames)) {
                    $missingSymptoms[] = [
                        'symptom' => $symptom['symptom_name'],
                        'is_primary' => $symptom['is_primary_symptom'],
                        'is_required' => $symptom['is_required']
                    ];
                }
            }
            
            $condition['confidence'] = $confidence;
            $condition['missing_symptoms'] = $missingSymptoms;
            
            // Reduce confidence if required symptoms are missing
            foreach ($missingSymptoms as $missing) {
                if ($missing['is_required']) {
                    $condition['confidence'] = max(0, $condition['confidence'] - 30);
                }
            }
        }
        
        // Sort by confidence
        usort($conditionScores, function($a, $b) {
            return $b['confidence'] - $a['confidence'];
        });
        
        // Take top 5 conditions
        $this->matchedConditions = array_slice($conditionScores, 0, 5);
        
        // Ensure at least 3 conditions if possible
        if (count($this->matchedConditions) < 3 && count($conditionScores) >= 3) {
            $this->matchedConditions = array_slice($conditionScores, 0, 3);
        }
    }
    
    /**
     * STEP 8: Generate clarifying questions
     */
    private function generateClarifyingQuestions() {
        $symptomTerms = array_column($this->normalizedSymptoms, 'medical_term');
        
        if (empty($symptomTerms)) {
            return;
        }
        
        $placeholders = implode(',', array_fill(0, count($symptomTerms), '?'));
        $stmt = $this->conn->prepare("
            SELECT * FROM clarifying_questions_bank
            WHERE symptom_keyword IN ($placeholders)
            ORDER BY priority DESC
            LIMIT 3
        ");
        
        $stmt->bind_param(str_repeat('s', count($symptomTerms)), ...$symptomTerms);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $this->clarifyingQuestions[] = [
                'question' => $row['question_text'],
                'type' => $row['question_type'],
                'options' => json_decode($row['options'], true),
                'helps_differentiate' => $row['helps_differentiate']
            ];
        }
        
        $stmt->close();
    }
    
    /**
     * STEP 9: Format output with safety disclaimers
     */
    private function formatOutput() {
        $output = [];
        
        // Extracted Symptoms
        $output['extracted_symptoms'] = array_map(function($s) {
            return sprintf(
                "%s (onset: %s, duration: %s, severity: %s)",
                $s['symptom'],
                $s['onset'],
                $s['duration'],
                $s['severity']
            );
        }, $this->extractedSymptoms);
        
        // Normalized Medical Terms
        $output['normalized_symptoms'] = array_map(function($s) {
            return sprintf("%s → %s", $s['original'], $s['medical_term']);
        }, $this->normalizedSymptoms);
        
        // Context Considered
        $output['context_considered'] = [];
        if (!empty($this->context['age'])) {
            $output['context_considered'][] = "Age: " . $this->context['age'];
        }
        if (!empty($this->context['gender'])) {
            $output['context_considered'][] = "Gender: " . $this->context['gender'];
        }
        if (!empty($this->context['existing_conditions'])) {
            $output['context_considered'][] = "Existing conditions: " . $this->context['existing_conditions'];
        }
        if (empty($output['context_considered'])) {
            $output['context_considered'][] = "Context unavailable";
        }
        
        // Urgent Warning Signs
        $output['urgent_warning_signs'] = [];
        if (empty($this->redFlags)) {
            $output['urgent_warning_signs'][] = "None detected";
        } else {
            foreach ($this->redFlags as $flag) {
                $output['urgent_warning_signs'][] = [
                    'symptom' => $flag['symptom'],
                    'urgency' => strtoupper($flag['urgency_level']),
                    'warning' => $flag['warning_message'],
                    'action' => $flag['recommended_action']
                ];
            }
        }
        
        // Possible Conditions (Ranked)
        $output['possible_conditions'] = [];
        foreach ($this->matchedConditions as $condition) {
            $likelihood = 'Low likelihood';
            if ($condition['confidence'] >= 70) {
                $likelihood = 'High likelihood';
            } elseif ($condition['confidence'] >= 50) {
                $likelihood = 'Medium likelihood';
            }
            
            $output['possible_conditions'][] = [
                'condition' => $condition['condition_name'],
                'confidence' => $condition['confidence'] . '%',
                'likelihood' => $likelihood,
                'specialty' => $condition['specialty'],
                'description' => $condition['description'],
                'supporting_symptoms' => array_column($condition['matched_symptoms'], 'symptom'),
                'missing_symptoms' => array_map(function($m) {
                    return $m['symptom'] . ($m['is_primary'] ? ' (key symptom)' : '');
                }, array_slice($condition['missing_symptoms'], 0, 3))
            ];
        }
        
        // Clarifying Questions
        $output['clarifying_questions'] = array_map(function($q) {
            return $q['question'];
        }, $this->clarifyingQuestions);
        
        // Safety Notice
        $output['safety_notice'] = "This information is not a medical diagnosis. If symptoms worsen, persist, or involve urgent warning signs, seek care from a qualified healthcare professional.";
        
        // Additional metadata
        $output['analysis_metadata'] = [
            'has_red_flags' => !empty($this->redFlags),
            'highest_urgency' => !empty($this->redFlags) ? $this->redFlags[0]['urgency_level'] : 'routine',
            'conditions_found' => count($this->matchedConditions),
            'confidence_level' => !empty($this->matchedConditions) ? 
                ($this->matchedConditions[0]['confidence'] >= 70 ? 'high' : 
                ($this->matchedConditions[0]['confidence'] >= 50 ? 'medium' : 'low')) : 'insufficient_data'
        ];
        
        return $output;
    }
    
    // ========================================================
    // Helper Methods for Symptom Extraction
    // ========================================================
    
    private function extractOnset($text) {
        if (preg_match('/sudden(ly)?/i', $text)) return 'sudden';
        if (preg_match('/gradual(ly)?/i', $text)) return 'gradual';
        return 'Not Provided';
    }
    
    private function extractDuration($text) {
        // Look for time expressions
        if (preg_match('/(\d+)\s*(hour|hr|day|week|month|year)s?/i', $text, $matches)) {
            return $matches[0];
        }
        if (preg_match('/(today|yesterday|this morning|last night)/i', $text, $matches)) {
            return $matches[0];
        }
        return 'Not Provided';
    }
    
    private function extractSeverity($text) {
        if (preg_match('/severe|terrible|worst|unbearable|excruciating/i', $text)) return 'severe';
        if (preg_match('/moderate|noticeable|significant/i', $text)) return 'moderate';
        if (preg_match('/mild|slight|minor/i', $text)) return 'mild';
        return 'Not Provided';
    }
    
    private function extractLocation($text, $symptom) {
        // Common location patterns
        $locations = [
            'left', 'right', 'upper', 'lower', 'front', 'back',
            'chest', 'abdomen', 'head', 'arm', 'leg', 'neck'
        ];
        
        foreach ($locations as $location) {
            if (stripos($text, $location) !== false) {
                return $location;
            }
        }
        
        return 'Not Provided';
    }
    
    /**
     * Log analysis for auditing and improvement
     */
    public function logAnalysis($consultationId, $patientId, $analysisResult) {
        $stmt = $this->conn->prepare("
            INSERT INTO ai_analysis_logs 
            (consultation_id, patient_id, raw_symptoms, normalized_symptoms, 
             extracted_context, matched_conditions, red_flags_detected, clarifying_questions)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $normalizedJson = json_encode($this->normalizedSymptoms);
        $contextJson = json_encode($this->context);
        $conditionsJson = json_encode($this->matchedConditions);
        $redFlagsJson = json_encode($this->redFlags);
        $questionsJson = json_encode($this->clarifyingQuestions);
        
        $stmt->bind_param(
            "iissssss",
            $consultationId,
            $patientId,
            $this->rawSymptoms,
            $normalizedJson,
            $contextJson,
            $conditionsJson,
            $redFlagsJson,
            $questionsJson
        );
        
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Helper function for quick analysis
 */
function analyzeSymptomsAI($symptoms, $context = []) {
    global $conn;
    $engine = new MedicalAIEngine($conn);
    return $engine->analyze($symptoms, $context);
}
?>
