<?php
require_once __DIR__ . '/config/database.php';

$sqlFile = file_get_contents(__DIR__ . '/chat_setup.sql');
$queries = array_filter(array_map('trim', explode(';', $sqlFile)));

foreach ($queries as $query) {
    if (!empty($query)) {
        if ($conn->query($query) === TRUE) {
            echo " Berhasil: " . substr($query, 0, 60) . "...<br>";
        } else {
            echo " Error: " . $conn->error . "<br>";
        }
    }
}

echo "<br><h3> Tabel chat berhasil dibuat!</h3>";
echo "<a href='/Amimi/'>← Kembali ke homepage</a>";
?>
