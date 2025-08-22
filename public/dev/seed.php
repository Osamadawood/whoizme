<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/events.php';

// user_id = 1 افتراضيًا، عدّل لو مختلف
wz_log_event($pdo, 1, 'link', 10, 'click', 'os.me/test');
echo "Seeded ok";