<?php
require 'vendor/autoload.php';

try {
    // Check local mysql database
    $localPdo = new PDO('mysql:host=127.0.0.1;dbname=pbo-mbm', 'root', '');
    $stmt = $localPdo->query('SELECT id, name FROM users ORDER BY id LIMIT 5');
    echo "LOCAL MySQL Database Users:\n";
    while ($row = $stmt->fetch()) {
        echo "ID: " . $row['id'] . ", Name: " . $row['name'] . "\n";
    }
    
    echo "\n---\n\n";
    
    // Check registry database
    $registryPdo = new PDO('mysql:host=127.0.0.1;dbname=pbo_registry', 'root', '');
    $stmt = $registryPdo->query('SELECT id, name FROM users ORDER BY id LIMIT 5');
    echo "PBO Registry Database Users:\n";
    while ($row = $stmt->fetch()) {
        echo "ID: " . $row['id'] . ", Name: " . $row['name'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
