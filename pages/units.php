<?php // pages/units.php

// Create / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id     = (int)($_POST['id'] ?? 0);
  $name   = esc($_POST['name'] ?? '');
  $symbol = esc($_POST['symbol'] ?? '');

  if (!$name || !$symbol) {
    flash('err','Name and symbol required'); header('Location: index.php?page=units'); exit;
  }

  if ($id > 0) {
    q("UPDATE units SET name='$name', symbol='$symbol' WHERE id=$id");
    flash('ok','Unit updated');
  } else {
    q("INSERT INTO units (name, symbol) VALUES ('$name', '$symbol')");
    flash('ok','Unit added');
  }
  header('Location: index.php?page=units'); exit;
}

// Delete
if (($_GET['action'] ?? '') === 'delete') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id > 0) {
    // Optional: check FK usage (raw_materials/products) before delete
    $used1 = get_one("SELECT COUNT(*) c FROM raw_materials WHERE unit_id=$id")['c'];
    $used2 = get_one("SELECT COUNT(*) c FROM products WHERE unit_id=$id")['c'];
    if ($used1 || $used2) {
      flash('err','Cannot delete: Unit in use'); header('Location: index.php?page=units'); exit;
    }
    q("DELETE FROM units WHERE id=$id");
    flash('ok','Unit deleted');
  }
  header('Location: index.php?page=units'); exit;
}

// Edit (prefill)
$edit = null;
if (($_GET['action'] ?? '') === 'edit') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id > 0) $edit = get_one("SELECT * FROM units WHERE id=$id");
}

// Search
$search = esc($_GET['search'] ?? '');
$where = $search ? "WHERE name LIKE '%$search%'" : '';
$units = get_all("SELECT * FROM units $where ORDER BY id DESC");
?>

<h2>Units</h2>

<!-- Search Bar -->
<form method="get" class="card" style="margin-bottom: 15px;">
  <input type="hidden" name="page" value="units">
  <label>Search by Name:
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Enter unit name...">
  </label>
  <button type="submit">Search</button>
  <?php if ($search): ?>
    <a href="index.php?page=units" style="margin-left:8px;">Clear</a>
  <?php endif; ?>
</form>

<!-- Add / Edit Form -->
<form method="post" class="card">
  <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
  <label>Name <input name="name" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" required></label>
  <label>Symbol <input name="symbol" value="<?= htmlspecialchars($edit['symbol'] ?? '') ?>" required></label>
  <button><?= $edit ? 'Update Unit' : 'Add Unit' ?></button>
  <?php if ($edit): ?>
    <a href="index.php?page=units" style="margin-left:8px">Cancel</a>
  <?php endif; ?>
</form>

<!-- Units Table -->
<table class="table">
  <tr><th>ID</th><th>Name</th><th>Symbol</th><th>Actions</th></tr>
  <?php if (count($units) > 0): ?>
    <?php foreach($units as $u): ?>
      <tr>
        <td><?= $u['id'] ?></td>
        <td><?= htmlspecialchars($u['name']) ?></td>
        <td><?= htmlspecialchars($u['symbol']) ?></td>
        <td>
          <a href="index.php?page=units&action=edit&id=<?= $u['id'] ?>">Edit</a> |
          <a href="index.php?page=units&action=delete&id=<?= $u['id'] ?>" onclick="return confirm('Delete this unit?')">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php else: ?>
    <tr><td colspan="4" style="text-align:center;">No units found.</td></tr>
  <?php endif; ?>
</table>
