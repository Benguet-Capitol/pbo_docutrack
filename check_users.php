<?php
require 'vendor/autoload.php';

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=pbo-mbm', 'root', '');
    $stmt = $pdo->query('SELECT COUNT(*) FROM users');
    echo "Users count: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query('SELECT id, name FROM users LIMIT 5');
    echo "\nUsers:\n";
    while ($row = $stmt->fetch()) {
        echo "ID: " . $row['id'] . ", Name: " . $row['name'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
