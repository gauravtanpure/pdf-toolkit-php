<?php
class DB {
    private static $pdo = null;
    
    public static function connect() {
        if (self::$pdo === null) {
            $host = 'localhost';
            $dbname = 'pdf_toolkit';
            $username = 'root';
            $password = '';
            
            try {
                self::$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ATTR_ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
    
    public static function storeDocument($fileData, $operationType = null) {
        $pdo = self::connect();
        
        $content = file_get_contents($fileData['tmp_name']);
        $hash = hash('sha256', $content);
        
        // Check if file already exists
        $stmt = $pdo->prepare("SELECT id FROM pdf_documents WHERE hash = ?");
        $stmt->execute([$hash]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            return $existing['id'];
        }
        
        $stmt = $pdo->prepare("INSERT INTO pdf_documents 
                             (original_name, file_size, file_type, content, hash, session_id, operation_type) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $fileData['name'],
            $fileData['size'],
            $fileData['type'],
            $content,
            $hash,
            session_id(),
            $operationType
        ]);
        
        return $pdo->lastInsertId();
    }
    
    public static function getDocument($id) {
        $pdo = self::connect();
        $stmt = $pdo->prepare("SELECT * FROM pdf_documents WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public static function storeOperation($operationType, $inputIds, $outputId, $parameters = null) {
        $pdo = self::connect();
        
        $stmt = $pdo->prepare("INSERT INTO pdf_operations 
                             (operation_type, input_ids, output_id, parameters, session_id) 
                             VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $operationType,
            json_encode($inputIds),
            $outputId,
            $parameters ? json_encode($parameters) : null,
            session_id()
        ]);
        
        return $pdo->lastInsertId();
    }
    
    public static function cleanupSessionFiles($sessionId) {
        $pdo = self::connect();
        
        // Delete operations first
        $stmt = $pdo->prepare("DELETE FROM pdf_operations WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        
        // Then delete documents not referenced by any operation
        $stmt = $pdo->prepare("DELETE FROM pdf_documents 
                              WHERE session_id = ? 
                              AND id NOT IN (SELECT output_id FROM pdf_operations WHERE output_id IS NOT NULL)");
        $stmt->execute([$sessionId]);
    }
}
?>