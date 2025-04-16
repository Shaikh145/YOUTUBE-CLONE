<?php
$host = 'localhost';
$dbname = 'dbtgcif2wuhumm';
$user = 'uklz9ew3hrop3';
$password = 'zyrbspyjlzjb';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
