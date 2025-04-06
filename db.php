<?php
class DB {
    private static $connection = null;

    public static function connect() {
        if (self::$connection === null) {
            $host = 'localhost';
            $dbname = 'pdf_toolkit';
            $username = 'root'; // Change as needed
            $password = ''; // Change as needed

            try {
                self::$connection = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                error_log("Connection failed: " . $e->getMessage());
                die("Database connection failed. Please try again later.");
            }
        }
        return self::$connection;
    }

    /**
     * Store document in database
     */
    public static function storeDocument($fileData, $operationType) {
        try {
            $conn = self::connect();
            
            $stmt = $conn->prepare("INSERT INTO documents 
                (original_name, file_name, file_type, file_size, operation_type, file_content, created_at) 
                VALUES (:original_name, :file_name, :file_type, :file_size, :operation_type, :file_content, NOW())");
            
            $fileContent = file_get_contents($fileData['tmp_name']);
            
            $stmt->bindParam(':original_name', $fileData['name']);
            $stmt->bindParam(':file_name', $fileData['tmp_name']);
            $stmt->bindParam(':file_type', $fileData['type']);
            $stmt->bindParam(':file_size', $fileData['size']);
            $stmt->bindParam(':operation_type', $operationType);
            $stmt->bindParam(':file_content', $fileContent, PDO::PARAM_LOB);
            
            $stmt->execute();
            
            return $conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error storing document: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Store operation details
     */
    public static function storeOperation($operation, $inputDocIds, $outputDocId, $metadata = []) {
        try {
            $conn = self::connect();
            
            $stmt = $conn->prepare("INSERT INTO operations 
                (operation_type, input_doc_ids, output_doc_id, metadata, created_at) 
                VALUES (:operation_type, :input_doc_ids, :output_doc_id, :metadata, NOW())");
            
            $inputIds = is_array($inputDocIds) ? implode(',', $inputDocIds) : $inputDocIds;
            $meta = json_encode($metadata);
            
            $stmt->bindParam(':operation_type', $operation);
            $stmt->bindParam(':input_doc_ids', $inputIds);
            $stmt->bindParam(':output_doc_id', $outputDocId);
            $stmt->bindParam(':metadata', $meta);
            
            $stmt->execute();
            
            return $conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error storing operation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get document by ID
     */
    public static function getDocument($id) {
        try {
            $conn = self::connect();
            
            $stmt = $conn->prepare("SELECT * FROM documents WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting document: " . $e->getMessage());
            return false;
        }
    }
}

