<?php
require_once 'auth.php';

Auth::logoutUser();
header('Location: login.php');
exit;