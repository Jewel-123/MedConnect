<?php
require_once 'db.php';

echo "--- Active Consultations Output (All Doctors) ---\n";
// This logic is copied from get_active_consultations in doctor_api.php
$query = "
    (SELECT c.id, u.full_name as patient_name, u.email as patient_email,
           p.gender as patient_gender,
           TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as patient_age,
           CAST('consultation' AS CHAR(20)) as type,
           CAST(c.status AS CHAR) as status,
           c.updated_at,
           CAST(c.symptoms AS CHAR) as symptoms,
           CAST(c.consultation_mode AS CHAR) as consultation_mode,
           CAST((CASE 
               WHEN c.severity = 'high' OR c.urgency_score >= 75 THEN 'emergency'
               WHEN c.severity = 'medium' OR c.urgency_score >= 50 THEN 'priority'
               ELSE 'routine' 
           END) AS CHAR) as urgency_level,
            CAST(NULL AS CHAR) as scheduled_date,
            CAST(NULL AS CHAR) as scheduled_time,
            c.id as linked_consultation_id
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    LEFT JOIN patient_profiles p ON u.id = p.user_id
    WHERE c.status IN ('accepted', 'confirmed', 'in_progress', 'paused'))
    
    UNION ALL
    
    (SELECT a.id, u.full_name as patient_name, u.email as patient_email,
           p.gender as patient_gender,
           TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as patient_age,
           CAST('appointment' AS CHAR(20)) as type,
           CAST(a.status AS CHAR) as status,
           a.created_at as updated_at,
           CAST(a.notes AS CHAR) as symptoms,
           CAST('offline' AS CHAR) as consultation_mode,
           CAST('routine' AS CHAR) as urgency_level,
            CAST(a.scheduled_date AS CHAR) as scheduled_date,
            CAST(a.scheduled_time AS CHAR) as scheduled_time,
            c_link.id as linked_consultation_id
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    LEFT JOIN patient_profiles p ON u.id = p.user_id
    LEFT JOIN consultations c_link ON a.id = c_link.appointment_id
    WHERE a.status IN ('confirmed', 'in_progress', 'paused')
      AND a.payment_status = 'paid')
    ORDER BY updated_at DESC
";

$res = $conn->query($query);
$results = [];
while($row = $res->fetch_assoc()) {
    $results[] = $row;
}
print_r($results);
?>
