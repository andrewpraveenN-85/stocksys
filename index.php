<?php
// index.php
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
include __DIR__ . '/pages/layout/header.php';


if ($page === 'dashboard') {
echo '<h2>Dashboard</h2>';
echo '<p>Use the navigation to manage units, suppliers, raw materials, products, GRNs, recipes, production, and stock.</p>';
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