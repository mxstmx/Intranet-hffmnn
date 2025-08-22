<?php
// Database connection using SQLite
$pdo = new PDO('sqlite:' . __DIR__ . '/intranet.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
