<?php
// index.php
require_once 'auth.php';
requireLogin(); // Require login to access any page

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
include __DIR__ . '/pages/layout/header.php';


if ($page === 'dashboard') {
$user = getCurrentUser();
echo '<h2>Dashboard</h2>';
echo '<p>Welcome, ' . htmlspecialchars($user['full_name']) . ' (' . ucfirst($user['role']) . ')</p>';

if (hasRole('admin')) {
    echo '<p>You have <strong>Administrator</strong> access - full control over all features including edit, view, and delete operations.</p>';
} elseif (hasRole('manager')) {
    echo '<p>You have <strong>Manager</strong> access - can view all data and create GRN (Goods Received Notes).</p>';
}

echo '<p>Use the navigation menu to access the features available to your role.</p>';
} else {
$allowed = [
'units','suppliers','raw_materials','products',
'grn_create','grn_list','recipes','production_create','production_list','stock_report'
];
if (in_array($page, $allowed)) {
include __DIR__ . "/pages/$page.php";
} else {
echo '<p>Page not found.</p>';
}
}


include __DIR__ . '/pages/layout/footer.php';