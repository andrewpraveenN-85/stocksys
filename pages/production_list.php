<?php // pages/production_list.php
// Delete + rollback handler
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

// Fetch list
$rows = get_all("SELECT pr.*, r.name AS recipe_name, p.name AS product_name
                 FROM productions pr
                 JOIN recipes r  ON r.id  = pr.recipe_id
                 JOIN products p ON p.id  = r.product_id
                 ORDER BY pr.id DESC");
?>
<h2>Production List</h2>

<table class="table">
  <tr>
    <th>ID</th>
    <th>Date</th>
    <th>Recipe</th>
    <th>Product</th>
    <th>Batches</th>
    <th>Output Qty</th>
    <th>Actions</th>
  </tr>

  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td><?= htmlspecialchars($r['production_date']) ?></td>
      <td><?= htmlspecialchars($r['recipe_name']) ?></td>
      <td><?= htmlspecialchars($r['product_name']) ?></td>
      <td><?= (int)$r['batches'] ?></td>
      <td><?= (float)$r['total_output_qty'] ?></td>
      <td>
        <a href="index.php?page=production_list&action=delete&id=<?= (int)$r['id'] ?>"
           onclick="return confirm('Delete this production and rollback stock?')">Delete</a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
