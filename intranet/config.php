<?php
// Database connection using SQLite. The database file "intranet.db"
// is stored in the same directory as this configuration file.
$pdo = new PDO('sqlite:' . __DIR__ . '/intranet.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
