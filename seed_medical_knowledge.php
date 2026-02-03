<?php
/**
 * Seed Medical Knowledge Base
 * Populates the database with initial medical conditions, symptoms, and red flags
 */

require_once 'db.php';

echo "========================================\n";
echo "Medical Knowledge Base Seeding Script\n";
echo "========================================\n\n";

// ========================================================
// 1. MEDICAL CONDITIONS
// ========================================================
echo "Seeding medical conditions...\n";

$conditions = [
    // Cardiovascular
    ['Myocardial Infarction (Heart Attack)', 'Blockage of blood flow to the heart muscle', 'Cardiology', 'critical', 1, 'adults', 'any', 'common'],
    ['Angina Pectoris', 'Chest pain due to reduced blood flow to heart', 'Cardiology', 'moderate', 0, 'adults', 'any', 'common'],
    ['Atrial Fibrillation', 'Irregular heart rhythm', 'Cardiology', 'moderate', 0, 'adults', 'any', 'common'],
    ['Hypertensive Crisis', 'Dangerously high blood pressure', 'Cardiology', 'severe', 1, 'adults', 'any', 'common'],
    
    // Respiratory
    ['Pneumonia', 'Lung infection causing inflammation', 'Pulmonology', 'moderate', 0, 'any', 'any', 'common'],
    ['Asthma Exacerbation', 'Acute worsening of asthma symptoms', 'Pulmonology', 'moderate', 0, 'any', 'any', 'very_common'],
    ['Pulmonary Embolism', 'Blood clot in lung arteries', 'Pulmonology', 'critical', 1, 'adults', 'any', 'uncommon'],
    ['Acute Bronchitis', 'Inflammation of bronchial tubes', 'Pulmonology', 'mild', 0, 'any', 'any', 'very_common'],
    ['COVID-19', 'Viral respiratory infection', 'Infectious Disease', 'moderate', 0, 'any', 'any', 'very_common'],
    
    // Gastrointestinal
    ['Acute Appendicitis', 'Inflammation of the appendix', 'General Surgery', 'severe', 1, 'any', 'any', 'common'],
    ['Gastroenteritis', 'Stomach and intestinal inflammation', 'Gastroenterology', 'mild', 0, 'any', 'any', 'very_common'],
    ['Peptic Ulcer Disease', 'Sores in stomach or duodenum lining', 'Gastroenterology', 'moderate', 0, 'adults', 'any', 'common'],
    ['Cholecystitis', 'Gallbladder inflammation', 'Gastroenterology', 'moderate', 0, 'adults', 'any', 'common'],
    ['Gastroesophageal Reflux Disease (GERD)', 'Chronic acid reflux', 'Gastroenterology', 'mild', 0, 'adults', 'any', 'very_common'],
    
    // Neurological
    ['Migraine', 'Severe recurring headache', 'Neurology', 'moderate', 0, 'any', 'any', 'very_common'],
    ['Tension Headache', 'Common stress-related headache', 'Neurology', 'mild', 0, 'any', 'any', 'very_common'],
    ['Stroke (CVA)', 'Interrupted blood supply to brain', 'Neurology', 'critical', 1, 'adults', 'any', 'common'],
    ['Meningitis', 'Inflammation of brain/spinal cord membranes', 'Neurology', 'critical', 1, 'any', 'any', 'uncommon'],
    ['Vertigo (BPPV)', 'Sensation of spinning dizziness', 'Neurology', 'mild', 0, 'adults', 'any', 'common'],
    
    // Musculoskeletal
    ['Acute Lower Back Pain', 'Sudden onset back pain', 'Orthopedics', 'moderate', 0, 'adults', 'any', 'very_common'],
    ['Osteoarthritis', 'Joint degeneration', 'Rheumatology', 'moderate', 0, 'adults', 'any', 'very_common'],
    ['Rheumatoid Arthritis', 'Autoimmune joint inflammation', 'Rheumatology', 'moderate', 0, 'adults', 'any', 'common'],
    ['Muscle Strain', 'Overstretched or torn muscle', 'Orthopedics', 'mild', 0, 'any', 'any', 'very_common'],
    
    // Infectious Diseases
    ['Influenza', 'Viral respiratory infection', 'Infectious Disease', 'mild', 0, 'any', 'any', 'very_common'],
    ['Urinary Tract Infection', 'Bacterial infection of urinary system', 'Urology', 'mild', 0, 'any', 'female', 'very_common'],
    ['Strep Throat', 'Bacterial throat infection', 'ENT', 'mild', 0, 'any', 'any', 'very_common'],
    ['Cellulitis', 'Bacterial skin infection', 'Dermatology', 'moderate', 0, 'any', 'any', 'common'],
    
    // Dermatological
    ['Allergic Dermatitis', 'Skin inflammation from allergen', 'Dermatology', 'mild', 0, 'any', 'any', 'very_common'],
    ['Eczema', 'Chronic inflammatory skin condition', 'Dermatology', 'mild', 0, 'any', 'any', 'very_common'],
    ['Psoriasis', 'Autoimmune skin condition', 'Dermatology', 'moderate', 0, 'any', 'any', 'common'],
    ['Shingles', 'Viral infection causing painful rash', 'Dermatology', 'moderate', 0, 'adults', 'any', 'common'],
    
    // Endocrine
    ['Hyperthyroidism', 'Overactive thyroid gland', 'Endocrinology', 'moderate', 0, 'adults', 'any', 'common'],
    ['Hypothyroidism', 'Underactive thyroid gland', 'Endocrinology', 'mild', 0, 'adults', 'any', 'common'],
    ['Diabetes Mellitus Type 2', 'Insulin resistance', 'Endocrinology', 'moderate', 0, 'adults', 'any', 'very_common'],
    
    // Mental Health
    ['Generalized Anxiety Disorder', 'Chronic excessive worry', 'Psychiatry', 'moderate', 0, 'any', 'any', 'very_common'],
    ['Major Depressive Disorder', 'Persistent low mood and loss of interest', 'Psychiatry', 'moderate', 0, 'any', 'any', 'very_common'],
    ['Panic Disorder', 'Recurrent unexpected panic attacks', 'Psychiatry', 'moderate', 0, 'any', 'any', 'common'],
    
    // Other Common Conditions
    ['Sinusitis', 'Sinus inflammation', 'ENT', 'mild', 0, 'any', 'any', 'very_common'],
    ['Allergic Rhinitis', 'Hay fever', 'Allergy/Immunology', 'mild', 0, 'any', 'any', 'very_common'],
    ['Anemia', 'Low red blood cell count', 'Hematology', 'mild', 0, 'any', 'any', 'common'],
];

$stmt = $conn->prepare("
    INSERT INTO medical_conditions 
    (condition_name, description, specialty, severity_level, requires_immediate_care, common_age_range, gender_specific, prevalence)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$conditionCount = 0;
foreach ($conditions as $condition) {
    $stmt->bind_param("ssssisss", ...$condition);
    if ($stmt->execute()) {
        $conditionCount++;
    }
}
$stmt->close();

echo "✓ Inserted $conditionCount medical conditions\n\n";

// ========================================================
// 2. CONDITION SYMPTOMS MAPPING
// ========================================================
echo "Seeding condition-symptom mappings...\n";

$symptomMappings = [
    // Myocardial Infarction
    [1, 'chest pain', 'cardiovascular', 95, 1, 1, 'sudden', 'minutes to hours', 'Crushing or squeezing sensation'],
    [1, 'shortness of breath', 'respiratory', 80, 1, 0, 'sudden', 'minutes to hours', null],
    [1, 'left arm pain', 'musculoskeletal', 70, 1, 0, 'sudden', 'minutes to hours', 'Radiating pain'],
    [1, 'sweating', 'general', 75, 0, 0, 'sudden', 'minutes to hours', 'Cold sweat'],
    [1, 'nausea', 'gastrointestinal', 60, 0, 0, 'sudden', 'minutes to hours', null],
    [1, 'jaw pain', 'musculoskeletal', 50, 0, 0, 'sudden', 'minutes to hours', null],
    
    // Angina
    [2, 'chest pain', 'cardiovascular', 90, 1, 1, 'gradual', 'minutes', 'Triggered by exertion'],
    [2, 'chest pressure', 'cardiovascular', 85, 1, 0, 'gradual', 'minutes', null],
    [2, 'shortness of breath', 'respiratory', 70, 0, 0, 'gradual', 'minutes', null],
    
    // Pneumonia
    [5, 'cough', 'respiratory', 90, 1, 1, 'gradual', 'days to weeks', 'Productive cough'],
    [5, 'fever', 'general', 85, 1, 0, 'gradual', 'days', 'High fever'],
    [5, 'shortness of breath', 'respiratory', 75, 1, 0, 'gradual', 'days', null],
    [5, 'chest pain', 'respiratory', 60, 0, 0, 'gradual', 'days', 'Pleuritic pain'],
    [5, 'fatigue', 'general', 70, 0, 0, 'gradual', 'days to weeks', null],
    
    // Asthma Exacerbation
    [6, 'wheezing', 'respiratory', 95, 1, 1, 'sudden', 'hours to days', null],
    [6, 'shortness of breath', 'respiratory', 90, 1, 1, 'sudden', 'hours to days', null],
    [6, 'chest tightness', 'respiratory', 80, 1, 0, 'sudden', 'hours to days', null],
    [6, 'cough', 'respiratory', 75, 0, 0, 'sudden', 'hours to days', 'Dry cough'],
    
    // Acute Appendicitis
    [10, 'abdominal pain', 'gastrointestinal', 95, 1, 1, 'gradual', 'hours to days', 'Right lower quadrant'],
    [10, 'nausea', 'gastrointestinal', 80, 1, 0, 'gradual', 'hours', null],
    [10, 'vomiting', 'gastrointestinal', 75, 0, 0, 'gradual', 'hours', null],
    [10, 'fever', 'general', 70, 0, 0, 'gradual', 'hours to days', 'Low-grade fever'],
    [10, 'loss of appetite', 'gastrointestinal', 85, 1, 0, 'gradual', 'hours', null],
    
    // Gastroenteritis
    [11, 'diarrhea', 'gastrointestinal', 95, 1, 1, 'sudden', 'hours to days', null],
    [11, 'nausea', 'gastrointestinal', 85, 1, 0, 'sudden', 'hours to days', null],
    [11, 'vomiting', 'gastrointestinal', 80, 1, 0, 'sudden', 'hours to days', null],
    [11, 'abdominal cramps', 'gastrointestinal', 75, 0, 0, 'sudden', 'hours to days', null],
    [11, 'fever', 'general', 60, 0, 0, 'sudden', 'hours to days', null],
    
    // Migraine
    [15, 'headache', 'neurological', 100, 1, 1, 'gradual', 'hours to days', 'Throbbing, unilateral'],
    [15, 'nausea', 'gastrointestinal', 80, 1, 0, 'gradual', 'hours to days', null],
    [15, 'photophobia', 'neurological', 75, 1, 0, 'gradual', 'hours to days', 'Light sensitivity'],
    [15, 'phonophobia', 'neurological', 70, 0, 0, 'gradual', 'hours to days', 'Sound sensitivity'],
    [15, 'visual aura', 'neurological', 30, 0, 0, 'sudden', 'minutes', null],
    
    // Tension Headache
    [16, 'headache', 'neurological', 100, 1, 1, 'gradual', 'hours to days', 'Bilateral, band-like'],
    [16, 'neck pain', 'musculoskeletal', 60, 0, 0, 'gradual', 'hours to days', null],
    [16, 'scalp tenderness', 'neurological', 40, 0, 0, 'gradual', 'hours', null],
    
    // Stroke
    [17, 'facial drooping', 'neurological', 85, 1, 1, 'sudden', 'minutes', 'One-sided'],
    [17, 'arm weakness', 'neurological', 85, 1, 1, 'sudden', 'minutes', 'One-sided'],
    [17, 'speech difficulty', 'neurological', 80, 1, 1, 'sudden', 'minutes', 'Slurred or confused'],
    [17, 'confusion', 'neurological', 70, 1, 0, 'sudden', 'minutes', null],
    [17, 'vision problems', 'neurological', 65, 0, 0, 'sudden', 'minutes', null],
    [17, 'severe headache', 'neurological', 60, 0, 0, 'sudden', 'minutes', null],
    
    // Influenza
    [24, 'fever', 'general', 95, 1, 1, 'sudden', 'days', 'High fever'],
    [24, 'body aches', 'musculoskeletal', 90, 1, 1, 'sudden', 'days', null],
    [24, 'fatigue', 'general', 90, 1, 0, 'sudden', 'days to weeks', null],
    [24, 'cough', 'respiratory', 80, 1, 0, 'gradual', 'days to weeks', 'Dry cough'],
    [24, 'sore throat', 'respiratory', 70, 0, 0, 'gradual', 'days', null],
    [24, 'headache', 'neurological', 75, 0, 0, 'sudden', 'days', null],
    
    // UTI
    [25, 'painful urination', 'urological', 95, 1, 1, 'gradual', 'hours to days', 'Burning sensation'],
    [25, 'frequent urination', 'urological', 90, 1, 1, 'gradual', 'hours to days', null],
    [25, 'urgency to urinate', 'urological', 85, 1, 0, 'gradual', 'hours to days', null],
    [25, 'cloudy urine', 'urological', 70, 0, 0, 'gradual', 'hours to days', null],
    [25, 'pelvic pain', 'urological', 60, 0, 0, 'gradual', 'hours to days', 'In women'],
    
    // Lower Back Pain
    [20, 'lower back pain', 'musculoskeletal', 100, 1, 1, 'sudden or gradual', 'days to weeks', null],
    [20, 'muscle stiffness', 'musculoskeletal', 80, 1, 0, 'gradual', 'days to weeks', null],
    [20, 'limited range of motion', 'musculoskeletal', 70, 0, 0, 'gradual', 'days to weeks', null],
    
    // Sinusitis
    [38, 'facial pain', 'ent', 90, 1, 1, 'gradual', 'days to weeks', 'Pressure sensation'],
    [38, 'nasal congestion', 'ent', 95, 1, 1, 'gradual', 'days to weeks', null],
    [38, 'headache', 'neurological', 75, 1, 0, 'gradual', 'days to weeks', null],
    [38, 'post-nasal drip', 'ent', 80, 0, 0, 'gradual', 'days to weeks', null],
    [38, 'reduced sense of smell', 'ent', 70, 0, 0, 'gradual', 'days to weeks', null],
];

$stmt = $conn->prepare("
    INSERT INTO condition_symptoms 
    (condition_id, symptom_name, symptom_category, likelihood_score, is_primary_symptom, is_required, typical_onset, typical_duration, notes)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$mappingCount = 0;
foreach ($symptomMappings as $mapping) {
    $stmt->bind_param("issiiisss", ...$mapping);
    if ($stmt->execute()) {
        $mappingCount++;
    }
}
$stmt->close();

echo "✓ Inserted $mappingCount symptom mappings\n\n";

// ========================================================
// 3. RED FLAG SYMPTOMS
// ========================================================
echo "Seeding red flag symptoms...\n";

$redFlags = [
    ['chest pain', 'emergency', 'Severe chest pain can indicate a heart attack or other life-threatening cardiac condition.', 'Seek immediate emergency care. Call emergency services if pain is severe, crushing, or accompanied by shortness of breath, sweating, or arm pain.', 'Myocardial Infarction, Pulmonary Embolism, Aortic Dissection', '{"age_over": 40}'],
    ['difficulty breathing', 'emergency', 'Severe difficulty breathing may indicate a serious respiratory or cardiac emergency.', 'Seek immediate medical attention. Call emergency services if breathing difficulty is severe or worsening rapidly.', 'Pulmonary Embolism, Heart Failure, Severe Asthma, Anaphylaxis', null],
    ['sudden severe headache', 'emergency', 'Sudden severe headache (worst headache of life) may indicate bleeding in the brain.', 'Seek immediate emergency care. This could be a brain hemorrhage or aneurysm.', 'Subarachnoid Hemorrhage, Stroke, Meningitis', null],
    ['facial drooping', 'emergency', 'Facial drooping, especially one-sided, is a classic sign of stroke.', 'Call emergency services immediately. Time is critical for stroke treatment.', 'Stroke, Bell\'s Palsy', null],
    ['slurred speech', 'emergency', 'Sudden onset of slurred speech may indicate stroke.', 'Seek immediate emergency care if accompanied by facial drooping or arm weakness.', 'Stroke, TIA', null],
    ['severe abdominal pain', 'urgent', 'Severe abdominal pain may indicate appendicitis, perforation, or other surgical emergency.', 'Seek medical evaluation within hours. Go to emergency if pain is severe and worsening.', 'Appendicitis, Peritonitis, Ectopic Pregnancy, Bowel Obstruction', null],
    ['blood in stool', 'urgent', 'Blood in stool requires medical evaluation to rule out serious conditions.', 'Contact your doctor within 24 hours. Seek emergency care if bleeding is heavy or accompanied by severe pain.', 'GI Bleeding, Colorectal Cancer, Inflammatory Bowel Disease', null],
    ['blood in urine', 'urgent', 'Blood in urine requires evaluation for infection, stones, or other conditions.', 'Contact your doctor within 24-48 hours for evaluation.', 'UTI, Kidney Stones, Bladder Cancer', null],
    ['confusion', 'urgent', 'Sudden confusion or altered mental status requires prompt evaluation.', 'Seek medical care within hours, especially if accompanied by fever, headache, or other symptoms.', 'Stroke, Meningitis, Sepsis, Hypoglycemia', '{"sudden_onset": true}'],
    ['high fever', 'urgent', 'Very high fever (>103°F/39.4°C) or fever lasting more than 3 days requires evaluation.', 'Contact your doctor. Seek emergency care if fever is accompanied by severe headache, stiff neck, confusion, or difficulty breathing.', 'Sepsis, Meningitis, Pneumonia', '{"temperature_over": 103}'],
    ['seizure', 'emergency', 'First-time seizure or prolonged seizure requires immediate medical attention.', 'Call emergency services if seizure lasts more than 5 minutes or if this is the first seizure.', 'Epilepsy, Stroke, Brain Tumor, Meningitis', null],
    ['loss of consciousness', 'emergency', 'Loss of consciousness requires immediate medical evaluation.', 'Call emergency services immediately.', 'Stroke, Heart Arrhythmia, Seizure, Hypoglycemia', null],
    ['severe allergic reaction', 'emergency', 'Signs of anaphylaxis include difficulty breathing, swelling of face/throat, and rapid pulse.', 'Use epinephrine auto-injector if available and call emergency services immediately.', 'Anaphylaxis', null],
    ['suicidal thoughts', 'emergency', 'Thoughts of self-harm or suicide require immediate intervention.', 'Call emergency services or suicide prevention hotline immediately. Do not leave the person alone.', 'Major Depression, Bipolar Disorder', null],
    ['sudden vision loss', 'emergency', 'Sudden vision loss may indicate stroke, retinal detachment, or other serious condition.', 'Seek immediate emergency care.', 'Stroke, Retinal Detachment, Temporal Arteritis', null],
    ['severe burns', 'emergency', 'Large or deep burns require immediate medical care.', 'Seek emergency care for burns larger than 3 inches, burns on face/hands/feet/genitals, or third-degree burns.', 'Burn Injury', null],
    ['neck stiffness with fever', 'emergency', 'Combination of stiff neck and fever may indicate meningitis.', 'Seek immediate emergency care. Meningitis is a medical emergency.', 'Meningitis', null],
    ['coughing up blood', 'urgent', 'Coughing up blood requires prompt medical evaluation.', 'Seek medical care within hours. Go to emergency if bleeding is heavy.', 'Pulmonary Embolism, Tuberculosis, Lung Cancer', null],
    ['severe dehydration', 'urgent', 'Signs include extreme thirst, no urination for 8+ hours, dizziness, confusion.', 'Seek medical care within hours, especially in children and elderly.', 'Dehydration, Gastroenteritis', null],
    ['pregnancy bleeding', 'urgent', 'Vaginal bleeding during pregnancy requires immediate evaluation.', 'Contact your OB/GYN immediately or go to emergency room.', 'Miscarriage, Ectopic Pregnancy, Placental Abruption', '{"gender": "female"}'],
];

$stmt = $conn->prepare("
    INSERT INTO red_flag_symptoms 
    (symptom_keyword, urgency_level, warning_message, recommended_action, associated_conditions, context_required)
    VALUES (?, ?, ?, ?, ?, ?)
");

$redFlagCount = 0;
foreach ($redFlags as $flag) {
    $stmt->bind_param("ssssss", ...$flag);
    if ($stmt->execute()) {
        $redFlagCount++;
    }
}
$stmt->close();

echo "✓ Inserted $redFlagCount red flag symptoms\n\n";

// ========================================================
// 4. SYMPTOM NORMALIZATIONS
// ========================================================
echo "Seeding symptom normalizations...\n";

$normalizations = [
    // Pain descriptions
    ['tummy ache', 'abdominal pain', 'gastrointestinal', 'stomach ache, belly pain, stomach pain'],
    ['stomach ache', 'abdominal pain', 'gastrointestinal', 'tummy ache, belly pain'],
    ['belly pain', 'abdominal pain', 'gastrointestinal', 'stomach ache, tummy ache'],
    ['heart racing', 'palpitations', 'cardiovascular', 'racing heart, rapid heartbeat, heart pounding'],
    ['racing heart', 'palpitations', 'cardiovascular', 'heart racing, rapid heartbeat'],
    ['can\'t breathe', 'shortness of breath', 'respiratory', 'difficulty breathing, breathless, hard to breathe'],
    ['hard to breathe', 'shortness of breath', 'respiratory', 'difficulty breathing, can\'t breathe'],
    ['breathless', 'shortness of breath', 'respiratory', 'difficulty breathing, can\'t breathe'],
    
    // Nausea/vomiting
    ['feel sick', 'nausea', 'gastrointestinal', 'queasy, feeling nauseous, upset stomach'],
    ['queasy', 'nausea', 'gastrointestinal', 'feel sick, nauseous, upset stomach'],
    ['throwing up', 'vomiting', 'gastrointestinal', 'puking, being sick, vomit'],
    ['puking', 'vomiting', 'gastrointestinal', 'throwing up, being sick'],
    ['being sick', 'vomiting', 'gastrointestinal', 'throwing up, puking'],
    
    // Fever/temperature
    ['hot and cold', 'fever', 'general', 'chills, temperature, running a fever'],
    ['running a temperature', 'fever', 'general', 'hot and cold, chills, running a fever'],
    ['burning up', 'fever', 'general', 'hot, high temperature, running a fever'],
    ['chills', 'fever', 'general', 'shivering, shaking, cold sweats'],
    ['shivering', 'chills', 'general', 'shaking, trembling'],
    
    // Headache
    ['head hurts', 'headache', 'neurological', 'sore head, head pain, headache'],
    ['sore head', 'headache', 'neurological', 'head hurts, head pain'],
    ['pounding head', 'headache', 'neurological', 'throbbing headache, severe headache'],
    ['splitting headache', 'severe headache', 'neurological', 'terrible headache, worst headache'],
    
    // Dizziness
    ['lightheaded', 'dizziness', 'neurological', 'dizzy, feeling faint, woozy'],
    ['woozy', 'dizziness', 'neurological', 'lightheaded, dizzy, unsteady'],
    ['room spinning', 'vertigo', 'neurological', 'spinning sensation, dizzy spells'],
    ['feeling faint', 'presyncope', 'cardiovascular', 'lightheaded, about to pass out'],
    
    // Fatigue
    ['worn out', 'fatigue', 'general', 'exhausted, tired, no energy'],
    ['exhausted', 'fatigue', 'general', 'worn out, extremely tired, drained'],
    ['no energy', 'fatigue', 'general', 'tired, exhausted, lethargic'],
    ['wiped out', 'fatigue', 'general', 'exhausted, extremely tired'],
    
    // Cough
    ['hacking cough', 'cough', 'respiratory', 'bad cough, persistent cough'],
    ['tickle in throat', 'throat irritation', 'respiratory', 'scratchy throat, itchy throat'],
    ['phlegm', 'productive cough', 'respiratory', 'mucus, sputum, coughing up stuff'],
    
    // Skin
    ['itchy', 'pruritus', 'dermatological', 'itching, scratchy'],
    ['rash', 'skin rash', 'dermatological', 'skin eruption, spots, bumps'],
    ['bumps', 'skin lesions', 'dermatological', 'lumps, spots, rash'],
    ['red skin', 'erythema', 'dermatological', 'redness, inflamed skin'],
    
    // Gastrointestinal
    ['runs', 'diarrhea', 'gastrointestinal', 'loose stools, watery stools'],
    ['loose stools', 'diarrhea', 'gastrointestinal', 'runs, watery stools'],
    ['constipated', 'constipation', 'gastrointestinal', 'can\'t go, blocked up'],
    ['heartburn', 'acid reflux', 'gastrointestinal', 'indigestion, burning chest'],
    ['indigestion', 'dyspepsia', 'gastrointestinal', 'upset stomach, heartburn'],
    
    // Urinary
    ['peeing a lot', 'frequent urination', 'urological', 'urinating often, going a lot'],
    ['burning when peeing', 'dysuria', 'urological', 'painful urination, hurts to pee'],
    ['hurts to pee', 'dysuria', 'urological', 'burning urination, painful peeing'],
    
    // Musculoskeletal
    ['sore muscles', 'myalgia', 'musculoskeletal', 'muscle pain, aching muscles'],
    ['aching joints', 'arthralgia', 'musculoskeletal', 'joint pain, sore joints'],
    ['stiff', 'stiffness', 'musculoskeletal', 'rigid, tight muscles'],
    ['swollen', 'swelling', 'general', 'puffy, edema, inflammation'],
    
    // Respiratory
    ['stuffy nose', 'nasal congestion', 'respiratory', 'blocked nose, congested'],
    ['blocked nose', 'nasal congestion', 'respiratory', 'stuffy nose, congested'],
    ['runny nose', 'rhinorrhea', 'respiratory', 'nasal discharge, drippy nose'],
    ['sore throat', 'pharyngitis', 'respiratory', 'throat pain, scratchy throat'],
    
    // Mental/Cognitive
    ['can\'t focus', 'difficulty concentrating', 'neurological', 'brain fog, trouble focusing'],
    ['brain fog', 'cognitive impairment', 'neurological', 'can\'t think clearly, confused'],
    ['forgetful', 'memory problems', 'neurological', 'memory loss, can\'t remember'],
    ['anxious', 'anxiety', 'psychiatric', 'worried, nervous, stressed'],
    ['down', 'depression', 'psychiatric', 'sad, low mood, depressed'],
    ['can\'t sleep', 'insomnia', 'psychiatric', 'trouble sleeping, sleeplessness'],
    
    // General
    ['no appetite', 'loss of appetite', 'general', 'not hungry, don\'t want to eat'],
    ['night sweats', 'nocturnal hyperhidrosis', 'general', 'sweating at night, drenched at night'],
    ['weight loss', 'unintentional weight loss', 'general', 'losing weight, dropping weight'],
];

$stmt = $conn->prepare("
    INSERT INTO symptom_normalizations 
    (informal_term, medical_term, category, synonyms)
    VALUES (?, ?, ?, ?)
");

$normalizationCount = 0;
foreach ($normalizations as $norm) {
    $stmt->bind_param("ssss", ...$norm);
    if ($stmt->execute()) {
        $normalizationCount++;
    }
}
$stmt->close();

echo "✓ Inserted $normalizationCount symptom normalizations\n\n";

// ========================================================
// 5. CLARIFYING QUESTIONS BANK
// ========================================================
echo "Seeding clarifying questions...\n";

$questions = [
    ['chest pain', 'Is the pain crushing or squeezing in nature?', 'yes_no', null, 'Myocardial Infarction vs Angina', 10],
    ['chest pain', 'Does the pain radiate to your left arm, jaw, or back?', 'yes_no', null, 'Myocardial Infarction', 10],
    ['chest pain', 'Is the pain worse when you take a deep breath?', 'yes_no', null, 'Pleurisy vs Cardiac', 8],
    ['headache', 'Is the headache throbbing or constant?', 'multiple_choice', '["Throbbing", "Constant/Pressure", "Sharp/Stabbing"]', 'Migraine vs Tension Headache', 8],
    ['headache', 'Do you have sensitivity to light or sound?', 'yes_no', null, 'Migraine', 7],
    ['headache', 'Is this the worst headache of your life?', 'yes_no', null, 'Subarachnoid Hemorrhage', 10],
    ['abdominal pain', 'Where exactly is the pain located?', 'multiple_choice', '["Upper right", "Upper left", "Lower right", "Lower left", "Central", "All over"]', 'Appendicitis, Cholecystitis, etc.', 9],
    ['abdominal pain', 'Does the pain get worse after eating?', 'yes_no', null, 'GERD, Peptic Ulcer', 7],
    ['cough', 'Are you coughing up mucus or phlegm?', 'yes_no', null, 'Productive vs Dry Cough', 8],
    ['cough', 'Is there any blood in what you cough up?', 'yes_no', null, 'Serious respiratory conditions', 10],
    ['fever', 'How high is your temperature?', 'scale', '{"min": 99, "max": 106, "unit": "°F"}', 'Severity assessment', 9],
    ['fever', 'Have you been in contact with anyone who is sick?', 'yes_no', null, 'Infectious disease', 6],
    ['shortness of breath', 'Did this come on suddenly or gradually?', 'multiple_choice', '["Suddenly", "Gradually over hours", "Gradually over days/weeks"]', 'Pulmonary Embolism vs Pneumonia vs CHF', 9],
    ['shortness of breath', 'Is it worse when lying down?', 'yes_no', null, 'Heart Failure', 8],
    ['dizziness', 'Does the room feel like it\'s spinning?', 'yes_no', null, 'Vertigo vs Lightheadedness', 8],
    ['dizziness', 'Does it happen when you stand up?', 'yes_no', null, 'Orthostatic Hypotension', 7],
    ['back pain', 'Does the pain radiate down your leg?', 'yes_no', null, 'Sciatica', 8],
    ['back pain', 'Did this start after lifting something heavy?', 'yes_no', null, 'Muscle Strain', 6],
    ['nausea', 'Have you vomited?', 'yes_no', null, 'Severity assessment', 7],
    ['rash', 'Is the rash itchy?', 'yes_no', null, 'Allergic vs Infectious', 7],
    ['rash', 'Did you start any new medications recently?', 'yes_no', null, 'Drug Reaction', 8],
];

$stmt = $conn->prepare("
    INSERT INTO clarifying_questions_bank 
    (symptom_keyword, question_text, question_type, options, helps_differentiate, priority)
    VALUES (?, ?, ?, ?, ?, ?)
");

$questionCount = 0;
foreach ($questions as $q) {
    $stmt->bind_param("sssssi", ...$q);
    if ($stmt->execute()) {
        $questionCount++;
    }
}
$stmt->close();

echo "✓ Inserted $questionCount clarifying questions\n\n";

// ========================================================
// Summary
// ========================================================
echo "========================================\n";
echo "✓ Medical Knowledge Base Seeded Successfully!\n";
echo "========================================\n";
echo "Summary:\n";
echo "  - Medical Conditions: $conditionCount\n";
echo "  - Symptom Mappings: $mappingCount\n";
echo "  - Red Flag Symptoms: $redFlagCount\n";
echo "  - Symptom Normalizations: $normalizationCount\n";
echo "  - Clarifying Questions: $questionCount\n";
echo "========================================\n";

$conn->close();
?>
