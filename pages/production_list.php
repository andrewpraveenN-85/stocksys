<?php
// pages/production_list.php
requireRole('admin');

// ===== Delete + rollback handler =====
if (($_GET['action'] ?? '') === 'delete') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id > 0) {
    $pr = get_one("SELECT * FROM productions WHERE id = $id");
    if ($pr) {
      $recipe_id = (int)$pr['recipe_id'];
      $batches   = (int)$pr['batches'];
      $output    = (float)$pr['total_output_qty'];

      $rec = get_one("SELECT * FROM recipes WHERE id = $recipe_id");
      if ($rec) {
        $product_id = (int)$rec['product_id'];

        q("START TRANSACTION");

        // 1) Roll back product increase (subtract produced quantity)
        q("UPDATE products SET current_qty = current_qty - $output WHERE id = $product_id");
        $pbal = get_one("SELECT current_qty FROM products WHERE id = $product_id")['current_qty'];
        q("INSERT INTO stock_ledger
           (item_type,item_id,ref_type,ref_id,entry_date,qty_in,qty_out,balance_after,note)
           VALUES ('product',$product_id,'ADJUST',$id,'".now()."',0,$output,$pbal,'Rollback Production #$id output')");

        // 2) Give raw materials back (add what was consumed)
        $items = get_all("SELECT ri.*, rm.name
                          FROM recipe_items ri
                          JOIN raw_materials rm ON rm.id = ri.raw_material_id
                          WHERE ri.recipe_id = $recipe_id");
        foreach ($items as $it) {
          $rid  = (int)$it['raw_material_id'];
          $need = (float)$it['qty'] * $batches;

          q("UPDATE raw_materials SET current_qty = current_qty + $need WHERE id = $rid");
          $rbal = get_one("SELECT current_qty FROM raw_materials WHERE id = $rid")['current_qty'];

          q("INSERT INTO stock_ledger
             (item_type,item_id,ref_type,ref_id,entry_date,qty_in,qty_out,balance_after,note)
             VALUES ('raw',$rid,'ADJUST',$id,'".now()."',$need,0,$rbal,'Rollback Production #$id consume')");
        }

        // 3) Delete the production header
        q("DELETE FROM productions WHERE id = $id");

        q("COMMIT");
        flash('ok', 'Production deleted and stock rolled back');
      }
    }
  }
  header('Location: index.php?page=production_list'); exit;
}

// ===== Search filters =====
$search_recipe = trim($_GET['search_recipe'] ?? '');
$search_product = trim($_GET['search_product'] ?? '');
$search_date_from = $_GET['search_date_from'] ?? '';
$search_date_to = $_GET['search_date_to'] ?? '';

$where = "1=1";
if ($search_recipe) {
  $search_recipe = esc($search_recipe);
  $where .= " AND r.name LIKE '%$search_recipe%'";
}
if ($search_product) {
  $search_product = esc($search_product);
  $where .= " AND p.name LIKE '%$search_product%'";
}
if ($search_date_from) {
  $search_date_from = esc($search_date_from);
  $where .= " AND pr.production_date >= '$search_date_from'";
}
if ($search_date_to) {
  $search_date_to = esc($search_date_to);
  $where .= " AND pr.production_date <= '$search_date_to'";
}

// ===== Fetch list =====
$rows = get_all("SELECT pr.*, r.name AS recipe_name, p.name AS product_name,
                        u.symbol AS product_unit
                 FROM productions pr
                 JOIN recipes r  ON r.id  = pr.recipe_id
                 JOIN products p ON p.id  = r.product_id
                 JOIN units u ON u.id = p.unit_id
                 WHERE $where
                 ORDER BY pr.production_date DESC, pr.id DESC");

// ===== View single production =====
$view_id = (int)($_GET['view'] ?? 0);
$view_prod = null;
$view_recipe_items = [];
if ($view_id) {
  $view_prod = get_one("SELECT pr.*, r.name AS recipe_name, p.name AS product_name,
                               u.symbol AS product_unit, pu.symbol AS recipe_unit
                        FROM productions pr
                        JOIN recipes r ON r.id = pr.recipe_id
                        JOIN products p ON p.id = r.product_id
                        JOIN units u ON u.id = p.unit_id
                        JOIN units pu ON pu.id = r.yield_unit_id
                        WHERE pr.id = $view_id");
  if ($view_prod) {
    $view_recipe_items = get_all("SELECT ri.*, rm.name AS raw_name, ru.symbol AS raw_unit
                                  FROM recipe_items ri
                                  JOIN raw_materials rm ON rm.id = ri.raw_material_id
                                  JOIN units ru ON ru.id = rm.unit_id
                                  WHERE ri.recipe_id = {$view_prod['recipe_id']}
                                  ORDER BY ri.id");
  }
}
?>

<h2>Production List</h2>

<?php if (!$view_id): ?>

<!-- Search Form -->
<div class="card" style="background:#1f2937;margin-bottom:16px">
  <form method="get" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:10px;align-items:end">
    <input type="hidden" name="page" value="production_list">
    
    <label>Recipe
      <input type="text" name="search_recipe" placeholder="Recipe name" value="<?= htmlspecialchars($search_recipe) ?>">
    </label>
    
    <label>Product
      <input type="text" name="search_product" placeholder="Product name" value="<?= htmlspecialchars($search_product) ?>">
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

<!-- Results Table -->
<table class="table">
  <tr>
    <th>ID</th>
    <th>Date</th>
    <th>Recipe</th>
    <th>Product</th>
    <th>Batches</th>
    <th>Output Qty</th>
    <th>Unit</th>
    <th>Actions</th>
  </tr>

  <?php if (empty($rows)): ?>
    <tr><td colspan="8" style="text-align:center;color:#9ca3af">No productions found</td></tr>
  <?php else: ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars($r['production_date']) ?></td>
        <td><?= htmlspecialchars($r['recipe_name']) ?></td>
        <td><?= htmlspecialchars($r['product_name']) ?></td>
        <td><?= (int)$r['batches'] ?></td>
        <td><?= number_format((float)$r['total_output_qty'], 3) ?></td>
        <td><?= htmlspecialchars($r['product_unit']) ?></td>
        <td>
          <a href="index.php?page=production_list&view=<?= (int)$r['id'] ?>" class="btn-view">View</a>
          <a href="index.php?page=production_list&action=delete&id=<?= (int)$r['id'] ?>"
             onclick="return confirm('Delete this production and rollback stock?')" class="btn-delete">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php endif; ?>
</table>

<?php else: ?>

<!-- View Single Production -->
<?php if ($view_prod): ?>
  <div style="margin-bottom:20px">
    <a href="index.php?page=production_list" class="btn-back">← Back to List</a>
  </div>

  <div class="card" style="background:#065f46;margin-bottom:16px">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
      <div>
        <p><strong>Production ID:</strong> <?= (int)$view_prod['id'] ?></p>
        <p><strong>Date:</strong> <?= htmlspecialchars($view_prod['production_date']) ?></p>
        <p><strong>Recipe:</strong> <?= htmlspecialchars($view_prod['recipe_name']) ?></p>
      </div>
      <div>
        <p><strong>Product:</strong> <?= htmlspecialchars($view_prod['product_name']) ?></p>
        <p><strong>Batches:</strong> <?= (int)$view_prod['batches'] ?></p>
        <p><strong>Total Output:</strong> <?= number_format((float)$view_prod['total_output_qty'], 3) ?> <?= htmlspecialchars($view_prod['product_unit']) ?></p>
      </div>
    </div>
  </div>

  <h3>Recipe Items (Raw Materials Used)</h3>
  <table class="table">
    <tr>
      <th>Raw Material</th>
      <th>Qty Per Batch</th>
      <th>Unit</th>
      <th>Batches</th>
      <th>Total Used</th>
    </tr>
    <?php foreach ($view_recipe_items as $item): 
      $total_used = (float)$item['qty'] * (int)$view_prod['batches'];
    ?>
      <tr>
        <td><?= htmlspecialchars($item['raw_name']) ?></td>
        <td><?= number_format((float)$item['qty'], 3) ?></td>
        <td><?= htmlspecialchars($item['raw_unit']) ?></td>
        <td><?= (int)$view_prod['batches'] ?></td>
        <td><?= number_format($total_used, 3) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <div style="margin-top:20px;text-align:right">
    <a href="index.php?page=production_list&action=delete&id=<?= (int)$view_prod['id'] ?>"
       onclick="return confirm('Delete this production and rollback stock?')" class="btn-delete">Delete Production</a>
  </div>

<?php else: ?>
  <p style="color:#ef4444">Production not found</p>
  <a href="index.php?page=production_list">← Back to List</a>
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