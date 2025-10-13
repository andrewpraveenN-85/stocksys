<?php
// pages/raw_materials.php
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Handle DELETE
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            q("DELETE FROM stock_ledger WHERE item_type='raw' AND item_id=$id");
            q("DELETE FROM raw_materials WHERE id=$id");
            flash('ok', 'Raw material deleted');
        }
        header('Location: index.php?page=raw_materials');
        exit;
    }
    
    // Handle ADD or UPDATE
    if ($action === 'add' || $action === 'update') {
        $name = trim($_POST['name'] ?? '');
        $name = ucwords(strtolower($name)); // Capitalize properly
        $unit_id = (int)($_POST['unit_id'] ?? 0);
        $opening = num($_POST['opening_qty'] ?? 0);
        $id = (int)($_POST['id'] ?? 0);
        
        if ($name && $unit_id) {
            if ($action === 'add') {
                q("INSERT INTO raw_materials (name, unit_id, opening_qty, current_qty) VALUES ('$name',$unit_id,$opening,$opening)");
                $raw_id = mysqli_insert_id($conn);
                $bal = $opening;
                q("INSERT INTO stock_ledger (item_type,item_id,ref_type,ref_id,entry_date,qty_in,qty_out,balance_after,note) 
                   VALUES ('raw',$raw_id,'OPENING',NULL,'".now()."',$opening,0,$bal,'Opening balance')");
                flash('ok', 'Raw material added');
            } else {
                q("UPDATE raw_materials SET name='$name', unit_id=$unit_id WHERE id=$id");
                flash('ok', 'Raw material updated');
            }
        } else {
            flash('err', 'Name and Unit required');
        }
        header('Location: index.php?page=raw_materials');
        exit;
    }
}

// Fetch units
$units = get_all('SELECT * FROM units ORDER BY name');

// Handle search
$search = esc($_GET['search'] ?? '');
$where = $search ? "WHERE r.name LIKE '%$search%'" : '';

// Fetch raw materials
$rows = get_all("SELECT r.*, u.symbol 
                 FROM raw_materials r 
                 JOIN units u ON u.id=r.unit_id 
                 $where 
                 ORDER BY r.id DESC");

// Edit (prefill)
$edit_id = (int)($_GET['edit'] ?? 0);
$edit_row = null;
if ($edit_id) {
    $edit_row = get_one("SELECT r.*, u.symbol FROM raw_materials r 
                         JOIN units u ON u.id=r.unit_id WHERE r.id=$edit_id");
}
?>

<h2>Raw Materials</h2>

<!-- Search Bar -->
<form method="get" class="card" style="margin-bottom: 15px;">
    <input type="hidden" name="page" value="raw_materials">
    <label>Search by Name:
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Enter material name...">
    </label>
    <button type="submit">Search</button>
    <?php if ($search): ?>
        <a href="index.php?page=raw_materials" style="margin-left:8px;">Clear</a>
    <?php endif; ?>
</form>

<!-- Add / Edit Form -->
<form method="post" class="card">
    <input type="hidden" name="action" value="<?= $edit_row ? 'update' : 'add' ?>">
    <?php if ($edit_row): ?>
        <input type="hidden" name="id" value="<?= $edit_row['id'] ?>">
    <?php endif; ?>
    
    <label>Name 
        <input name="name" required value="<?= $edit_row ? htmlspecialchars($edit_row['name']) : '' ?>">
    </label>
    
    <label>Unit 
        <select name="unit_id" required>
            <option value="">-- Select --</option>
            <?php foreach($units as $u): ?>
                <option value="<?= $u['id'] ?>" <?= $edit_row && $edit_row['unit_id'] == $u['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['name']).' ('.$u['symbol'].')' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    
    <label>Opening Qty 
        <input type="number" step="0.001" name="opening_qty" value="<?= $edit_row ? $edit_row['opening_qty'] : 0 ?>" <?= $edit_row ? 'disabled' : '' ?>>
    </label>
    
    <button><?= $edit_row ? 'Update Raw Material' : 'Add Raw Material' ?></button>
    <?php if ($edit_row): ?>
        <a href="index.php?page=raw_materials" class="btn-cancel">Cancel</a>
    <?php endif; ?>
</form>

<!-- Table -->
<table class="table">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Unit</th>
        <th>Opening Qty</th>
        <th>Current Qty</th>
        <th>Actions</th>
    </tr>
    <?php if (count($rows) > 0): ?>
        <?php foreach($rows as $r): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['name']) ?></td>
                <td><?= htmlspecialchars($r['symbol']) ?></td>
                <td><?= $r['opening_qty'] ?></td>
                <td><?= $r['current_qty'] ?></td>
                <td>
                    <a href="index.php?page=raw_materials&edit=<?= $r['id'] ?>" class="btn-edit">Edit</a>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this raw material?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn-delete">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="6" style="text-align:center;">No raw materials found.</td></tr>
    <?php endif; ?>
</table>

<style>
    .btn-edit, .btn-delete, .btn-cancel {
        display: inline-block;
        padding: 8px 12px;
        border-radius: 6px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        font-size: 14px;
    }
    .btn-edit {
        background: #3b82f6;
        color: white;
    }
    .btn-edit:hover {
        background: #2563eb;
    }
    .btn-delete {
        background: #ef4444;
        color: white;
    }
    .btn-delete:hover {
        background: #dc2626;
    }
    .btn-cancel {
        background: #6b7280;
        color: white;
        margin-left: 5px;
    }
    .btn-cancel:hover {
        background: #4b5563;
    }
</style>
