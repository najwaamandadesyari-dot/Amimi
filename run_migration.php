<?php
require 'config/database.php';
$sql = file_get_contents('migration_v2.sql');
if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Migration successful.";
} else {
    echo "Error: " . $conn->error;
}
