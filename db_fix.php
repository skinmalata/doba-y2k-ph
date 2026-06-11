<?php
try {
    $pdo = new PDO(
        'mysql:host=sql113.ezyro.com;dbname=ezyro_42149896_oldboys;charset=utf8mb4',
        'ezyro_42149896',
        '4271dd672660f3f',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->query("SHOW COLUMNS FROM levies LIKE 'status'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE levies ADD COLUMN status ENUM('active','closed') DEFAULT 'active' AFTER created_by");
        echo "Added 'status' column to levies.\n";
    } else {
        echo "'status' column already exists in levies.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM levy_payments LIKE 'receipt_ref'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE levy_payments ADD COLUMN receipt_ref VARCHAR(100) DEFAULT NULL AFTER paid_at");
        echo "Added 'receipt_ref' column to levy_payments.\n";
    } else {
        echo "'receipt_ref' column already exists in levy_payments.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM transactions LIKE 'member_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN member_id INT DEFAULT NULL AFTER amount");
        echo "Added 'member_id' column to transactions.\n";
    } else {
        echo "'member_id' column already exists in transactions.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM levy_payments LIKE 'receipt_ref'");
    if ($stmt->fetch()) {
        echo "'receipt_ref' column exists in levy_payments.\n";
    }

    echo "Database schema check complete.\n";

    echo "\n-- Migration for partial levy payments --\n";
    echo "The levy_payments table already supports partial payments (amount column exists).\n";
    echo "No schema changes needed. Remove the 'already paid' check in application code.\n";

    echo "\n-- Migration for transactions edit/delete --\n";
    echo "No schema changes needed. Application-level changes only.\n";

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
