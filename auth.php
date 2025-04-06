<?php
require_once 'db.php';

class Auth {
    public static function initSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function isLoggedIn() {
        self::initSession();
        
        if (isset($_SESSION['user_id']) && isset($_SESSION['session_token'])) {
            $user = DB::validateSession($_SESSION['session_token']);
            if ($user && $user['id'] == $_SESSION['user_id']) {
                return $user;
            }
        }
        
        // Check for remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            $user = DB::validateSession($_COOKIE['remember_token']);
            if ($user) {
                self::loginUser($user['id'], $user['username'], $user['session_token']);
                return $user;
            }
        }
        
        return false;
    }

    public static function loginUser($userId, $username, $sessionToken, $remember = false) {
        self::initSession();
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['session_token'] = $sessionToken;
        
        if ($remember) {
            $expire = time() + 30 * 24 * 60 * 60; // 30 days
            setcookie('remember_token', $sessionToken, $expire, '/');
        }
    }

    public static function logoutUser() {
        self::initSession();
        
        if (isset($_SESSION['session_token'])) {
            DB::logoutUser($_SESSION['session_token']);
        }
        
        // Clear session data
        session_unset();
        session_destroy();
        
        // Clear remember me cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }

    public static function redirectIfNotLoggedIn($redirectTo = 'login.php') {
        if (!self::isLoggedIn()) {
            header("Location: $redirectTo");
            exit;
        }
    }
}