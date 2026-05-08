<?php
try {
    $p = new PDO('mysql:host=localhost;dbname=dana_concrete_db', 'root', '');
    $res = $p->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($res as $t) {
        if (strpos($t, 'adjust') !== false || strpos($t, 'person') !== false) {
            echo $t . "\n";
        }
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
