<?php
/**
 * Location Service
 * Handles geographical proximity calculations
 */

class LocationService {
    
    /**
     * Calculate distance between two coordinates using Haversine formula
     * @param float $lat1 Latitude of point 1
     * @param float $lon1 Longitude of point 1
     * @param float $lat2 Latitude of point 2
     * @param float $lon2 Longitude of point 2
     * @return float Distance in kilometers
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        // Earth's radius in kilometers
        $earthRadius = 6371;
        
        // Convert degrees to radians
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);
        
        // Haversine formula
        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;
        
        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) * sin($deltaLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        $distance = $earthRadius * $c;
        
        return round($distance, 2);
    }
    
    /**
     * Convert distance to proximity score (0-100)
     * Closer = higher score
     * @param float $distance Distance in kilometers
     * @return int Score from 0-100
     */
    public static function getProximityScore($distance) {
        // 0 km = 100 points
        // 50+ km = 0 points
        // Linear scale
        
        if ($distance <= 0) return 100;
        if ($distance >= 50) return 0;
        
        return (int) (100 - ($distance * 2));
    }
    
    /**
     * Get nearest pharmacy for a patient
     * @param mysqli $conn Database connection
     * @param float $patientLat Patient latitude
     * @param float $patientLon Patient longitude
     * @param int $limit Number of results to return
     * @return array Array of pharmacies with distances
     */
    public static function getNearestPharmacies($conn, $patientLat, $patientLon, $limit = 5) {
        // Get all pharmacies with locations
        $query = "
            SELECT 
                u.id, 
                u.full_name,
                p.pharmacy_name,
                p.address,
                p.city,
                p.phone,
                p.latitude,
                p.longitude,
                p.is_24_hours
            FROM users u
            INNER JOIN pharmacy_locations p ON u.id = p.pharmacy_id
            WHERE u.role = 'pharmacy' 
            AND u.status = 'approved'
            AND p.latitude IS NOT NULL 
            AND p.longitude IS NOT NULL
        ";
        
        $result = $conn->query($query);
        $pharmacies = [];
        
        while ($row = $result->fetch_assoc()) {
            $distance = self::calculateDistance(
                $patientLat, 
                $patientLon, 
                $row['latitude'], 
                $row['longitude']
            );
            
            $row['distance_km'] = $distance;
            $row['proximity_score'] = self::getProximityScore($distance);
            $pharmacies[] = $row;
        }
        
        // Sort by distance
        usort($pharmacies, function($a, $b) {
            return $a['distance_km'] <=> $b['distance_km'];
        });
        
        return array_slice($pharmacies, 0, $limit);
    }
    
    /**
     * Get nearest doctors for a patient
     * @param mysqli $conn Database connection
     * @param float $patientLat Patient latitude
     * @param float $patientLon Patient longitude
     * @param string $specialty Required specialty (optional)
     * @param int $limit Number of results to return
     * @return array Array of doctors with distances
     */
    public static function getNearestDoctors($conn, $patientLat, $patientLon, $specialty = null, $limit = 10) {
        $specialtyCondition = $specialty ? "AND d.specialization = ?" : "";
        
        $query = "
            SELECT 
                u.id,
                u.full_name,
                d.specialization,
                d.consultation_fee,
                d.languages_spoken,
                l.practice_name,
                l.address,
                l.city,
                l.latitude,
                l.longitude
            FROM users u
            INNER JOIN doctor_profiles d ON u.id = d.user_id
            INNER JOIN doctor_locations l ON u.id = l.doctor_id
            WHERE u.role = 'doctor' 
            AND u.status = 'approved'
            AND l.latitude IS NOT NULL 
            AND l.longitude IS NOT NULL
            AND l.is_primary = TRUE
            $specialtyCondition
        ";
        
        $stmt = $conn->prepare($query);
        if ($specialty) {
            $stmt->bind_param('s', $specialty);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $doctors = [];
        
        while ($row = $result->fetch_assoc()) {
            $distance = self::calculateDistance(
                $patientLat, 
                $patientLon, 
                $row['latitude'], 
                $row['longitude']
            );
            
            $row['distance_km'] = $distance;
            $row['proximity_score'] = self::getProximityScore($distance);
            $doctors[] = $row;
        }
        
        $stmt->close();
        
        // Sort by distance
        usort($doctors, function($a, $b) {
            return $a['distance_km'] <=> $b['distance_km'];
        });
        
        return array_slice($doctors, 0, $limit);
    }
    
    /**
     * Geocode an address to coordinates (placeholder - requires Google Maps API or similar)
     * @param string $address Full address
     * @return array|null ['latitude' => float, 'longitude' => float] or null
     */
    public static function geocodeAddress($address) {
        // TODO: Implement geocoding using Google Maps API, OpenStreetMap, or similar
        // For now, return null
        // In production, you would call an external geocoding service
        
        return null;
    }
}