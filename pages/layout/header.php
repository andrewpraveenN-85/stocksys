<?php // pages/layout/header.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/functions.php';
require_once __DIR__ . '/../../auth.php';
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Restaurant Stock</title>
<link rel="stylesheet" href="public/assets/style.css">
</head>
<body>
<header>
<h1>Restaurant Stock</h1>
<nav>
<a href="index.php">Dashboard</a>
<?php if (canView()): ?>
    <a href="index.php?page=stock_report">Stock Report</a>
<?php endif; ?>

<?php if (canEdit()): ?>
    <a href="index.php?page=units">Units</a>
    <a href="index.php?page=suppliers">Suppliers</a>
    <a href="index.php?page=raw_materials">Raw Materials</a>
    <a href="index.php?page=products">Products</a>
    <a href="index.php?page=recipes">Recipes</a>
    <a href="index.php?page=production_create">Production</a>
    <a href="index.php?page=production_list">Production List</a>
<?php endif; ?>

<?php if (canCreateGRN()): ?>
    <a href="index.php?page=grn_create">Create GRN</a>
<?php endif; ?>

<?php if (canView()): ?>
    <a href="index.php?page=grn_list">GRN List</a>
<?php endif; ?>

<?php if (isLoggedIn()): ?>
    <span style="color: #93c5fd; margin-left: 20px;">
        Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?> (<?= ucfirst($_SESSION['role']) ?>)
    </span>
    <a href="logout.php" style="color: #ef4444; margin-left: 10px;">Logout</a>
<?php endif; ?>
</nav>
</header>
<main>
<?php if ($m = flash('ok')): ?>
<div class="flash ok"><?= htmlspecialchars($m) ?></div>
<?php endif; ?>
<?php if ($m = flash('err')): ?>
<div class="flash err"><?= htmlspecialchars($m) ?></div>
<?php endif; ?>