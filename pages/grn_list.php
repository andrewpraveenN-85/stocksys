<?php
// pages/grn_list.php

// ===== Delete + rollback handler (raw + product) =====
if (($_GET['action'] ?? '') === 'delete') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id > 0) {
    $items = get_all("SELECT * FROM grn_items WHERE grn_id = $id");

    q("START TRANSACTION");

    foreach ($items as $it) {
      if (!empty($it['product_id'])) {
        // Rollback product line
        $pid = (int)$it['product_id'];
        $qty = (float)$it['qty'];

        q("UPDATE products SET current_qty = current_qty - $qty WHERE id = $pid");
        $bal = get_one("SELECT current_qty FROM products WHERE id = $pid")['current_qty'];

        q("INSERT INTO stock_ledger
           (item_type,item_id,ref_type,ref_id,entry_date,qty_in,qty_out,balance_after,note)
           VALUES ('product',$pid,'ADJUST',$id,'".now()."',0,$qty,$bal,'Rollback GRN #$id (product)')");

      } else {
        // Rollback raw line
        $rid = (int)$it['raw_material_id'];
        $qty = (float)$it['qty'];

        q("UPDATE raw_materials SET current_qty = current_qty - $qty WHERE id = $rid");
        $bal = get_one("SELECT current_qty FROM raw_materials WHERE id = $rid")['current_qty'];

        q("INSERT INTO stock_ledger
           (item_type,item_id,ref_type,ref_id,entry_date,qty_in,qty_out,balance_after,note)
           VALUES ('raw',$rid,'ADJUST',$id,'".now()."',0,$qty,$bal,'Rollback GRN #$id (raw)')");
      }
    }

    // Remove details then header
    q("DELETE FROM grn_items WHERE grn_id = $id");
    q("DELETE FROM grns WHERE id = $id");

    q("COMMIT");
    flash('ok','GRN deleted and stock rolled back');
  }
  header('Location: index.php?page=grn_list'); exit;
}

// ===== Fetch list =====
$rows = get_all("SELECT g.*, s.name AS supplier
                 FROM grns g
                 JOIN suppliers s ON s.id = g.supplier_id
                 ORDER BY g.id DESC");
?>
<h2>GRN List</h2>

<table class="table">
  <tr>
    <th>ID</th>
    <th>GRN No</th>
    <th>Date</th>
    <th>Supplier</th>
    <th>Total</th>
    <th>Actions</th>
  </tr>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td><?= htmlspecialchars($r['grn_no']) ?></td>
      <td><?= htmlspecialchars($r['grn_date']) ?></td>
      <td><?= htmlspecialchars($r['supplier']) ?></td>
      <td><?= number_format((float)$r['total_cost'], 2) ?></td>
      <td>
        <a href="index.php?page=grn_list&action=delete&id=<?= (int)$r['id'] ?>"
           onclick="return confirm('Delete this GRN and rollback stock?')">Delete</a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
