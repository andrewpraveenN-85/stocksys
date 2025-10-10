<?php
// pages/grn_list.php
requireRole('admin');

// ===== Delete + rollback handler (raw + product) WITH FIFO =====
if (($_GET['action'] ?? '') === 'delete') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id > 0) {
    $items = get_all("SELECT * FROM grn_items WHERE grn_id = $id");

    q("START TRANSACTION");

    foreach ($items as $it) {
      if (!empty($it['product_id'])) {
        $pid = (int)$it['product_id'];
        $qty = (float)$it['qty'];

        // FIFO: Get oldest FIFO entries for this product
        $fifo_entries = get_all("SELECT * FROM stock_fifo 
                                WHERE item_type='product' AND item_id=$pid 
                                ORDER BY grn_id ASC, id ASC");
        
        $remaining_qty = $qty;
        foreach ($fifo_entries as $entry) {
          if ($remaining_qty <= 0) break;
          $available = (float)$entry['qty_remaining'];
          $deduct = min($available, $remaining_qty);
          
          $new_remaining = (float)$entry['qty_remaining'] - $deduct;
          q("UPDATE stock_fifo SET qty_remaining = $new_remaining WHERE id = {$entry['id']}");
          $remaining_qty -= $deduct;
        }

        q("UPDATE products SET current_qty = current_qty - $qty WHERE id = $pid");
        $bal = get_one("SELECT current_qty FROM products WHERE id = $pid")['current_qty'];

        q("INSERT INTO stock_ledger
           (item_type,item_id,ref_type,ref_id,entry_date,qty_in,qty_out,balance_after,note)
           VALUES ('product',$pid,'ADJUST',$id,'".now()."',0,$qty,$bal,'Rollback GRN #$id (product)')");

      } else {
        $rid = (int)$it['raw_material_id'];
        $qty = (float)$it['qty'];

        // FIFO: Get oldest FIFO entries for this raw material
        $fifo_entries = get_all("SELECT * FROM stock_fifo 
                                WHERE item_type='raw' AND item_id=$rid 
                                ORDER BY grn_id ASC, id ASC");
        
        $remaining_qty = $qty;
        foreach ($fifo_entries as $entry) {
          if ($remaining_qty <= 0) break;
          $available = (float)$entry['qty_remaining'];
          $deduct = min($available, $remaining_qty);
          
          $new_remaining = (float)$entry['qty_remaining'] - $deduct;
          q("UPDATE stock_fifo SET qty_remaining = $new_remaining WHERE id = {$entry['id']}");
          $remaining_qty -= $deduct;
        }

        q("UPDATE raw_materials SET current_qty = current_qty - $qty WHERE id = $rid");
        $bal = get_one("SELECT current_qty FROM raw_materials WHERE id = $rid")['current_qty'];

        q("INSERT INTO stock_ledger
           (item_type,item_id,ref_type,ref_id,entry_date,qty_in,qty_out,balance_after,note)
           VALUES ('raw',$rid,'ADJUST',$id,'".now()."',0,$qty,$bal,'Rollback GRN #$id (raw)')");
      }
    }

    q("DELETE FROM grn_items WHERE grn_id = $id");
    q("DELETE FROM stock_fifo WHERE grn_id = $id");
    q("DELETE FROM grns WHERE id = $id");

    q("COMMIT");
    flash('ok','GRN deleted and stock rolled back');
  }
  header('Location: index.php?page=grn_list'); exit;
}

// ===== Search filters =====
$search_grn = trim($_GET['search_grn'] ?? '');
$search_supplier = trim($_GET['search_supplier'] ?? '');
$search_date_from = $_GET['search_date_from'] ?? '';
$search_date_to = $_GET['search_date_to'] ?? '';

$where = "1=1";
if ($search_grn) {
  $search_grn = esc($search_grn);
  $where .= " AND g.grn_no LIKE '%$search_grn%'";
}
if ($search_supplier) {
  $search_supplier = esc($search_supplier);
  $where .= " AND s.name LIKE '%$search_supplier%'";
}
if ($search_date_from) {
  $search_date_from = esc($search_date_from);
  $where .= " AND g.grn_date >= '$search_date_from'";
}
if ($search_date_to) {
  $search_date_to = esc($search_date_to);
  $where .= " AND g.grn_date <= '$search_date_to'";
}

// ===== Fetch list =====
$rows = get_all("SELECT g.*, s.name AS supplier
                 FROM grns g
                 JOIN suppliers s ON s.id = g.supplier_id
                 WHERE $where
                 ORDER BY g.grn_date DESC, g.id DESC");

// ===== View single GRN =====
$view_id = (int)($_GET['view'] ?? 0);
$view_grn = null;
$view_items = [];
if ($view_id) {
  $view_grn = get_one("SELECT g.*, s.name AS supplier
                       FROM grns g
                       JOIN suppliers s ON s.id = g.supplier_id
                       WHERE g.id = $view_id");
  if ($view_grn) {
    $view_items = get_all("SELECT i.*, 
                           r.name AS raw_name, ru.symbol AS raw_unit,
                           p.name AS prod_name, pu.symbol AS prod_unit
                           FROM grn_items i
                           LEFT JOIN raw_materials r ON r.id = i.raw_material_id
                           LEFT JOIN units ru ON ru.id = r.unit_id
                           LEFT JOIN products p ON p.id = i.product_id
                           LEFT JOIN units pu ON pu.id = p.unit_id
                           WHERE i.grn_id = $view_id
                           ORDER BY i.id");
  }
}
?>

<h2>GRN List</h2>

<?php if (!$view_id): ?>

<!-- Search Form -->
<div class="card" style="background:#1f2937;margin-bottom:16px">
  <form method="get" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:10px;align-items:end">
    <input type="hidden" name="page" value="grn_list">
    
    <label>GRN Number
      <input type="text" name="search_grn" placeholder="e.g. GRN-20251009" value="<?= htmlspecialchars($search_grn) ?>">
    </label>
    
    <label>Supplier
      <input type="text" name="search_supplier" placeholder="Supplier name" value="<?= htmlspecialchars($search_supplier) ?>">
    </label>
    
    <label>From Date
      <input type="date" name="search_date_from" value="<?= htmlspecialchars($search_date_from) ?>">
    </label>
    
    <label>To Date
      <input type="date" name="search_date_to" value="<?= htmlspecialchars($search_date_to) ?>">
    </label>
    
    <button type="submit">Search</button>
  </form>
</div>

<!-- Results -->
<table class="table">
  <tr>
    <th>ID</th>
    <th>GRN No</th>
    <th>Date</th>
    <th>Supplier</th>
    <th>Items</th>
    <th>Total</th>
    <th>Actions</th>
  </tr>
  <?php foreach ($rows as $r): 
    $item_count = get_one("SELECT COUNT(*) as cnt FROM grn_items WHERE grn_id = {$r['id']}")['cnt'];
  ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td><?= htmlspecialchars($r['grn_no']) ?></td>
      <td><?= htmlspecialchars($r['grn_date']) ?></td>
      <td><?= htmlspecialchars($r['supplier']) ?></td>
      <td><?= (int)$item_count ?></td>
      <td><?= number_format((float)$r['total_cost'], 2) ?></td>
      <td>
        <a href="index.php?page=grn_list&view=<?= (int)$r['id'] ?>" class="btn-view">View</a>
        <a href="index.php?page=grn_list&action=delete&id=<?= (int)$r['id'] ?>"
           onclick="return confirm('Delete this GRN and rollback stock?')" class="btn-delete">Delete</a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<?php if (empty($rows)): ?>
  <p style="text-align:center;color:#9ca3af">No GRNs found</p>
<?php endif; ?>

<?php else: ?>

<!-- View Single GRN -->
<?php if ($view_grn): ?>
  <div style="margin-bottom:20px">
    <a href="index.php?page=grn_list" class="btn-back">← Back to List</a>
  </div>

  <div class="card" style="background:#065f46;margin-bottom:16px">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
      <div>
        <p><strong>GRN Number:</strong> <?= htmlspecialchars($view_grn['grn_no']) ?></p>
        <p><strong>Date:</strong> <?= htmlspecialchars($view_grn['grn_date']) ?></p>
        <p><strong>Supplier:</strong> <?= htmlspecialchars($view_grn['supplier']) ?></p>
      </div>
      <div>
        <p><strong>Total Cost:</strong> <?= number_format((float)$view_grn['total_cost'], 2) ?></p>
        <p><strong>Notes:</strong> <?= htmlspecialchars($view_grn['notes'] ?: '—') ?></p>
      </div>
    </div>
  </div>

  <h3>Items</h3>
  <table class="table">
    <tr>
      <th>Type</th>
      <th>Item Name</th>
      <th>Unit</th>
      <th>Quantity</th>
      <th>Unit Cost</th>
      <th>Total Cost</th>
    </tr>
    <?php foreach ($view_items as $item): 
      $type = $item['raw_material_id'] ? 'Raw' : 'Product';
      $name = $item['raw_material_id'] ? htmlspecialchars($item['raw_name']) : htmlspecialchars($item['prod_name']);
      $unit = $item['raw_material_id'] ? htmlspecialchars($item['raw_unit']) : htmlspecialchars($item['prod_unit']);
    ?>
      <tr>
        <td><?= $type ?></td>
        <td><?= $name ?></td>
        <td><?= $unit ?></td>
        <td><?= number_format((float)$item['qty'], 3) ?></td>
        <td><?= number_format((float)$item['unit_cost'], 2) ?></td>
        <td><?= number_format((float)$item['total_cost'], 2) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <div style="margin-top:20px;text-align:right">
    <a href="index.php?page=grn_list&action=delete&id=<?= (int)$view_grn['id'] ?>"
       onclick="return confirm('Delete this GRN and rollback stock?')" class="btn-delete">Delete GRN</a>
  </div>

<?php else: ?>
  <p style="color:#ef4444">GRN not found</p>
  <a href="index.php?page=grn_list">← Back to List</a>
<?php endif; ?>

<?php endif; ?>

<style>
  .btn-view, .btn-delete, .btn-back {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    margin-right: 5px;
  }
  .btn-view {
    background: #3b82f6;
    color: white;
  }
  .btn-view:hover {
    background: #2563eb;
  }
  .btn-delete {
    background: #ef4444;
    color: white;
  }
  .btn-delete:hover {
    background: #dc2626;
  }
  .btn-back {
    background: #6b7280;
    color: white;
    margin-bottom: 15px;
  }
  .btn-back:hover {
    background: #4b5563;
  }
</style>