<?php
/**
 * Message Classifier Service
 * Analyzes patient messages and classifies them for doctor workflow guidance
 */

class MessageClassifier {
    
    // Classification types
    const TYPE_NON_CLINICAL = 'non_clinical';
    const TYPE_PARTIAL = 'partial_symptom';
    const TYPE_DETAILED = 'detailed_symptom';
    const TYPE_FOLLOW_UP = 'follow_up';
    const TYPE_GENERAL = 'general';
    
    // Workflow stages
    const STAGE_GREETING = 'greeting';
    const STAGE_CHIEF_COMPLAINT = 'chief_complaint';
    const STAGE_HPI = 'hpi';
    const STAGE_MEDICAL_HISTORY = 'medical history';
    const STAGE_ASSESSMENT = 'assessment';
    const STAGE_PLAN = 'plan';
    const STAGE_CLOSING = 'closing';
    
    private $nonClinicalKeywords = ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening', 'thanks', 'thank you', 'ok', 'okay', 'yes', 'no', 'bye'];
    
    private $symptomKeywords = ['pain', 'ache', 'fever', 'cough', 'cold', 'headache', 'dizzy', 'nausea', 'vomit', 'diarrhea', 'constipation', 'rash', 'itch', 'swelling', 'bleeding', 'breathless', 'tired', 'weak', 'chest pain', 'stomach', 'back', 'neck', 'throat', 'ear', 'eye', 'nose'];
    
    private $detailIndicators = ['since', 'for', 'days', 'weeks', 'months', 'years', 'severe', 'mild', 'moderate', 'constant', 'intermittent', 'worse', 'better'];
    
    private $followUpKeywords = ['also', 'additionally', 'another', 'and', 'plus', 'furthermore'];
    
    /**
     * Main classification method
     * @param string $message The patient message to classify
     * @param array $context Optional context from consultation (previous messages, etc.)
     * @return array Classification result with type, stage, confidence, and suggestions
     */
    public function classify($message, $context = []) {
        $message = strtolower(trim($message));
        $words = explode(' ', $message);
        $wordCount = count($words);
        
        $result = [
            'classification' => self::TYPE_GENERAL,
            'workflow_stage' => null,
            'confidence' => 0.5,
            'detected_keywords' => [],
            'suggested_response' => '',
            'suggested_questions' => []
        ];
        
        // Check for non-clinical (greeting, acknowledgment)
        if ($this->isNonClinical($message, $words)) {
            $result['classification'] = self::TYPE_NON_CLINICAL;
            $result['workflow_stage'] = self::STAGE_GREETING;
            $result['confidence'] = 0.9;
            $result['suggested_response'] = "Acknowledge briefly and ask about symptoms.";
            $result['suggested_questions'] = [
                "What symptoms are you experiencing today?",
                "How can I help you?",
                "What brings you here today?"
            ];
            return $result;
        }
        
        // Detect symptom keywords
        $symptomKeywordsFound = [];
        foreach ($this->symptomKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                $symptomKeywordsFound[] = $keyword;
            }
        }
        
        // Detect detail indicators
        $detailsFound = [];
        foreach ($this->detailIndicators as $indicator) {
            if (strpos($message, $indicator) !== false) {
                $detailsFound[] = $indicator;
            }
        }
        
        $result['detected_keywords'] = array_merge($symptomKeywordsFound, $detailsFound);
        
        // Classification logic
        if (count($symptomKeywordsFound) > 0) {
            // Has symptom keywords
            if (count($detailsFound) >= 2 || $wordCount > 10) {
                // Detailed symptom description
                $result['classification'] = self::TYPE_DETAILED;
                $result['workflow_stage'] = self::STAGE_HPI;
                $result['confidence'] = 0.85;
                $result['suggested_response'] = "Good detail provided. Proceed with clinical workflow.";
                $result['suggested_questions'] = [
                    "Does anything make it better or worse?",
                    "Have you tried any treatments?",
                    "Any other symptoms?"
                ];
            } else {
                // Partial symptom (mentioned but lacks detail)
                $result['classification'] = self::TYPE_PARTIAL;
                $result['workflow_stage'] = self::STAGE_CHIEF_COMPLAINT;
                $result['confidence'] = 0.75;
                $result['suggested_response'] = "Symptom mentioned but needs more details.";
                $result['suggested_questions'] = [
                    "Where exactly is the " . $symptomKeywordsFound[0] . " located?",
                    "When did this start?",
                    "How severe is it on a scale of 1-10?"
                ];
            }
        } else {
            // No clear symptoms - general message
            $result['classification'] = self::TYPE_GENERAL;
            $result['suggested_response'] = "Ask for specific symptoms.";
            $result['suggested_questions'] = [
                "Can you describe your symptoms in more detail?",
                "What health concern brought you here today?"
            ];
        }
        
        // Check for follow-up indicators
        foreach ($this->followUpKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                $result['classification'] = self::TYPE_FOLLOW_UP;
                break;
            }
        }
        
        return $result;
    }
    
    /**
     * Check if message is non-clinical
     */
    private function isNonClinical($message, $words) {
        // Very short messages (1-3 words) that match greeting keywords
        if (count($words) <= 3) {
            foreach ($this->nonClinicalKeywords as $keyword) {
                if ($message === $keyword || strpos($message, $keyword) === 0) {
                    return true;
                }
            }
        }
        
        // Check if message is just emojis or very short
        if (strlen($message) <= 15 && count($words) <= 2) {
            $hasSymptomKeyword = false;
            foreach ($this->symptomKeywords as $symptom) {
                if (strpos($message, $symptom) !== false) {
                    $hasSymptomKeyword = true;
                    break;
                }
            }
            if (!$hasSymptomKeyword) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get suggested follow-up based on classification
     */
    public function getSuggestedFollowUp($classification, $workflowStage) {
        // Return appropriate follow-up questions based on stage
        $suggestions = [
            self::STAGE_GREETING => [
                "What symptoms are you experiencing today?",
                "How can I assist you?",
                "What brings you to the consultation today?"
            ],
            self::STAGE_CHIEF_COMPLAINT => [
                "When did this symptom start?",
                "Where exactly do you feel this?",
                "On a scale of 1-10, how severe is it?"
            ],
            self::STAGE_HPI => [
                "Does anything make it better or worse?",
                "Have you taken any medication for this?",
                "Are there any other associated symptoms?"
            ],
            self::STAGE_MEDICAL_HISTORY => [
                "Do you have any chronic conditions?",
                "Are you currently on any medications?",
                "Any known allergies?"
            ]
        ];
        
        return $suggestions[$workflowStage] ?? [];
    }
}
?>
