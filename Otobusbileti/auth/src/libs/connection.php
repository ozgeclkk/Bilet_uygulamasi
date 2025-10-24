<?php

function db(): PDO
{
    static $pdo;
    
    
    if ($pdo === null) {
        
       
        $databaseFile = __DIR__ . '/../../database/Otobusbileti.sqlite'; 

        
        if (!file_exists($databaseFile)) {
            
            throw new \PDOException("HATA: SQLite veritabanı dosyası bulunamadı. Lütfen yolu kontrol edin: " . $databaseFile);
        }
        
        try {
         
            $pdo = new PDO("sqlite:" . $databaseFile);
            
            
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); 
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); 
            
        } catch (\PDOException $e) {
            $error_message = "SQLite bağlantısı başarısız oldu: " . $e->getMessage();
            throw new \PDOException($error_message, (int)$e->getCode());
        }
    }
    
    return $pdo;
}

$db_conn = db(); 

function db_select(string $sql, array $params = []): array {
    global $db_conn; 
    $stmt = $db_conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}


function db_execute(string $sql, array $params = []): int {
    global $db_conn;
    $stmt = $db_conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}


function db_last_insert_id(): ?string {
    global $db_conn;
    return $db_conn->lastInsertId();
}

?>