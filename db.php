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

    /************************
     * DOCUMENT OPERATIONS *
     ************************/
    
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

    /************************
     * USER AUTHENTICATION *
     ************************/
    
    /**
     * Register a new user
     */
    public static function registerUser($username, $email, $password) {
        try {
            $conn = self::connect();
            
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return false; // User already exists
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->execute();
            
            return $conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error registering user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Login user
     */
    public static function loginUser($username, $password) {
        try {
            $conn = self::connect();
            
            // Get user by username
            $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password, $user['password'])) {
                return false; // Invalid credentials
            }
            
            // Create session
            $sessionToken = bin2hex(random_bytes(32));
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            $stmt = $conn->prepare("INSERT INTO sessions (user_id, session_token, ip_address, user_agent, expires_at) 
                                   VALUES (:user_id, :session_token, :ip_address, :user_agent, :expires_at)");
            $stmt->bindParam(':user_id', $user['id']);
            $stmt->bindParam(':session_token', $sessionToken);
            $stmt->bindParam(':ip_address', $ipAddress);
            $stmt->bindParam(':user_agent', $userAgent);
            $stmt->bindParam(':expires_at', $expiresAt);
            $stmt->execute();
            
            return [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'session_token' => $sessionToken
            ];
        } catch (PDOException $e) {
            error_log("Error logging in user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate session token
     */
    public static function validateSession($sessionToken) {
        try {
            $conn = self::connect();
            
            $stmt = $conn->prepare("SELECT u.id, u.username, s.expires_at 
                                   FROM sessions s 
                                   JOIN users u ON s.user_id = u.id 
                                   WHERE s.session_token = :session_token 
                                   AND s.expires_at > NOW()");
            $stmt->bindParam(':session_token', $sessionToken);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error validating session: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Logout user
     */
    public static function logoutUser($sessionToken) {
        try {
            $conn = self::connect();
            
            $stmt = $conn->prepare("DELETE FROM sessions WHERE session_token = :session_token");
            $stmt->bindParam(':session_token', $sessionToken);
            $stmt->execute();
            
            return true;
        } catch (PDOException $e) {
            error_log("Error logging out user: " . $e->getMessage());
            return false;
        }
    }
}