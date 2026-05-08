<?php
try {
    $p = new PDO('mysql:host=localhost;dbname=dana_concrete_db', 'root', '');
    $res = $p->query('DESCRIBE company_adjustments')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($res as $r) {
        echo $r['Field'] . " (" . $r['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
