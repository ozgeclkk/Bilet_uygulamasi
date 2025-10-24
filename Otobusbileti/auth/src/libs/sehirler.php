<?php
require_once __DIR__ . '/connection.php'; 

function getCitiesList(): array
{
    $pdo = db(); 
    
    $sql = "
        SELECT DISTINCT departure_city AS city 
        FROM Trips
        WHERE departure_city IS NOT NULL AND departure_city != ''
        
        UNION
        
        SELECT DISTINCT destination_city AS city 
        FROM Trips
        WHERE destination_city IS NOT NULL AND destination_city != ''
        
        ORDER BY city ASC
    ";
    
    try {
        $stmt = $pdo->query($sql);
        $cities = $stmt->fetchAll(PDO::FETCH_COLUMN); 
        
        if (empty($cities)) {
            return ['Istanbul', 'Ankara', 'Izmir'];
        }
        
        return $cities;

    } catch (\PDOException $e) {
        
        return [];
    }
}