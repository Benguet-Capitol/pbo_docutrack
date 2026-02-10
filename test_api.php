<?php
require 'vendor/autoload.php';

$users = [
    ['id' => 1, 'name' => 'Samuel B. Soliman', 'username' => 'samuel', 'usertype' => 'admin', 'fk_office_id' => 1],
    ['id' => 2, 'name' => 'Samuel B. Soliman', 'username' => 'samuel2', 'usertype' => 'user', 'fk_office_id' => 2],
    ['id' => 3, 'name' => 'Cindy Grace L. Copas', 'username' => 'cindy', 'usertype' => 'user', 'fk_office_id' => 3],
    ['id' => 4, 'name' => 'Samuel B. Soliman', 'username' => 'samuel3', 'usertype' => 'user', 'fk_office_id' => 1],
    ['id' => 5, 'name' => 'Samuel B. Soliman', 'username' => 'samuel4', 'usertype' => 'user', 'fk_office_id' => 2],
];

echo "Testing API would return:\n";
echo json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
echo "Users available for forwarding: " . count($users);
