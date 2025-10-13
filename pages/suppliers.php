<?php // pages/suppliers.php

// Create / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id      = (int)($_POST['id'] ?? 0);
  $name    = esc($_POST['name'] ?? '');
  $phone   = esc($_POST['phone'] ?? '');
  $address = esc($_POST['address'] ?? '');

  if (!$name) { flash('err','Name required'); header('Location: index.php?page=suppliers'); exit; }

  if ($id > 0) {
    q("UPDATE suppliers SET name='$name', phone='$phone', address='$address' WHERE id=$id");
    flash('ok','Supplier updated');
  } else {
    q("INSERT INTO suppliers (name, phone, address) VALUES ('$name', '$phone', '$address')");
    flash('ok','Supplier added');
  }
  header('Location: index.php?page=suppliers'); exit;
}

// Delete
if (($_GET['action'] ?? '') === 'delete') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id > 0) {
    $used = get_one("SELECT COUNT(*) c FROM grns WHERE supplier_id=$id")['c'];
    if ($used) {
      flash('err','Cannot delete: Supplier has GRNs'); header('Location: index.php?page=suppliers'); exit;
    }
    q("DELETE FROM suppliers WHERE id=$id");
    flash('ok','Supplier deleted');
  }
  header('Location: index.php?page=suppliers'); exit;
}

// Edit (prefill)
$edit = null;
if (($_GET['action'] ?? '') === 'edit') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id > 0) $edit = get_one("SELECT * FROM suppliers WHERE id=$id");
}

// Search
$search = esc($_GET['search'] ?? '');
$where = $search ? "WHERE name LIKE '%$search%'" : '';
$suppliers = get_all("SELECT * FROM suppliers $where ORDER BY id DESC");
?>

<h2>Suppliers</h2>

<!-- Search Bar -->
<form method="get" class="card" style="margin-bottom: 15px;">
  <input type="hidden" name="page" value="suppliers">
  <label>Search by Name:
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Enter supplier name...">
  </label>
  <button type="submit">Search</button>
  <?php if ($search): ?>
    <a href="index.php?page=suppliers" style="margin-left:8px;">Clear</a>
  <?php endif; ?>
</form>

<!-- Add / Edit Form -->
<form method="post" class="card">
  <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
  <label>Name <input name="name" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" required></label>
  <label>Phone <input name="phone" value="<?= htmlspecialchars($edit['phone'] ?? '') ?>"></label>
  <label>Address <input name="address" value="<?= htmlspecialchars($edit['address'] ?? '') ?>"></label>
  <button><?= $edit ? 'Update Supplier' : 'Add Supplier' ?></button>
  <?php if ($edit): ?><a href="index.php?page=suppliers" style="margin-left:8px">Cancel</a><?php endif; ?>
</form>

<!-- Suppliers Table -->
<table class="table">
  <tr><th>ID</th><th>Name</th><th>Phone</th><th>Address</th><th>Actions</th></tr>
  <?php if (count($suppliers) > 0): ?>
    <?php foreach($suppliers as $s): ?>
      <tr>
        <td><?= $s['id'] ?></td>
        <td><?= htmlspecialchars($s['name']) ?></td>
        <td><?= htmlspecialchars($s['phone']) ?></td>
        <td><?= htmlspecialchars($s['address']) ?></td>
        <td>
          <a href="index.php?page=suppliers&action=edit&id=<?= $s['id'] ?>">Edit</a> |
          <a href="index.php?page=suppliers&action=delete&id=<?= $s['id'] ?>" onclick="return confirm('Delete this supplier?')">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php else: ?>
    <tr><td colspan="5" style="text-align:center;">No suppliers found.</td></tr>
  <?php endif; ?>
</table>
