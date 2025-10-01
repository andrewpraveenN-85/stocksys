<?php // pages/raw_materials.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$name = esc($_POST['name'] ?? '');
$unit_id = (int)($_POST['unit_id'] ?? 0);
$opening = num($_POST['opening_qty'] ?? 0);
if ($name && $unit_id) {
q("INSERT INTO raw_materials (name, unit_id, opening_qty, current_qty) VALUES ('$name',$unit_id,$opening,$opening)");
// ledger opening
$raw_id = mysqli_insert_id($conn);
$bal = $opening;
q("INSERT INTO stock_ledger (item_type,item_id,ref_type,ref_id,entry_date,qty_in,qty_out,balance_after,note) VALUES ('raw',$raw_id,'OPENING',NULL,'".now()."',$opening,0,$bal,'Opening balance')");
flash('ok','Raw material added');
} else {
flash('err','Name and Unit required');
}
header('Location: index.php?page=raw_materials'); exit;
}
$units = get_all('SELECT * FROM units ORDER BY name');
$rows = get_all('SELECT r.*, u.symbol FROM raw_materials r JOIN units u ON u.id=r.unit_id ORDER BY r.id DESC');
?>
<h2>Raw Materials</h2>
<form method="post" class="card">
<label>Name <input name="name" required></label>
<label>Unit
<select name="unit_id" required>
<option value="">-- Select --</option>
<?php foreach($units as $u): ?>
<option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']).' ('.$u['symbol'].')' ?></option>
<?php endforeach; ?>
</select>
</label>
<label>Opening Qty <input type="number" step="0.001" name="opening_qty" value="0"></label>
<button>Add Raw Material</button>
</form>
<table class="table">
<tr><th>ID</th><th>Name</th><th>Unit</th><th>Current Qty</th></tr>
<?php foreach($rows as $r): ?>
<tr>
<td><?= $r['id'] ?></td>
<td><?= htmlspecialchars($r['name']) ?></td>
<td><?= htmlspecialchars($r['symbol']) ?></td>
<td><?= $r['current_qty'] ?></td>
</tr>
<?php endforeach; ?>
</table>