<?php
// pages/production_create.php
// Assumes config.php and lib/functions.php are already included via pages/layout/header.php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recipe_id'])) {
  $recipe_id = (int)($_POST['recipe_id'] ?? 0);
  $batches   = (int)($_POST['batches'] ?? 0);
  $pdate     = esc($_POST['production_date'] ?? date('Y-m-d'));
  $notes     = esc($_POST['notes'] ?? '');

  // Validate
  $rec = get_one("SELECT * FROM recipes WHERE id = $recipe_id");
  if (!$rec || $batches <= 0) {
    flash('err', 'Invalid recipe/batches');
    header('Location: index.php?page=production_create'); exit;
  }

  // Get product and recipe items
  // (product info not strictly needed beyond id; kept for clarity)
  $prod   = get_one("SELECT * FROM products WHERE id = ".$rec['product_id']);
  $items  = get_all("SELECT ri.*, rm.name, rm.current_qty
                     FROM recipe_items ri
                     JOIN raw_materials rm ON rm.id = ri.raw_material_id
                     WHERE ri.recipe_id = $recipe_id");

  // Check stock availability
  foreach ($items as $it) {
    $need = $it['qty'] * $batches;
    if ($it['current_qty'] < $need) {
      flash('err', 'Insufficient stock for '.htmlspecialchars($it['name']).' (need '.$need.', have '.$it['current_qty'].')');
      header('Location: index.php?page=production_create'); exit;
    }
  }

  // Create production
  $output = $rec['yield_qty'] * $batches;
  q("INSERT INTO productions (recipe_id, batches, production_date, total_output_qty, notes)
     VALUES ($recipe_id, $batches, '$pdate', $output, '$notes')");
  $prod_id = mysqli_insert_id($conn);

  // Consume raw materials
  foreach ($items as $it) {
    $rid  = (int)$it['raw_material_id'];
    $need = $it['qty'] * $batches;

    q("UPDATE raw_materials SET current_qty = current_qty - $need WHERE id = $rid");
    $bal = get_one("SELECT current_qty FROM raw_materials WHERE id = $rid")['current_qty'];

    q("INSERT INTO stock_ledger (item_type, item_id, ref_type, ref_id, entry_date, qty_in, qty_out, balance_after, note)
       VALUES ('raw', $rid, 'PROD_CONS', $prod_id, '".now()."', 0, $need, $bal, 'Production consume #$prod_id')");
  }

  // Increase product stock
  q("UPDATE products SET current_qty = current_qty + $output WHERE id = ".$rec['product_id']);
  $pbal = get_one("SELECT current_qty FROM products WHERE id = ".$rec['product_id'])['current_qty'];

  q("INSERT INTO stock_ledger (item_type, item_id, ref_type, ref_id, entry_date, qty_in, qty_out, balance_after, note)
     VALUES ('product', ".$rec['product_id'].", 'PROD_OUT', $prod_id, '".now()."', $output, 0, $pbal, 'Production output #$prod_id')");

  flash('ok', 'Production recorded');
  header('Location: index.php?page=production_list'); exit;
}

// Form data
$recipes = get_all("SELECT r.id, r.name, r.yield_qty, p.name AS product
                    FROM recipes r
                    JOIN products p ON p.id = r.product_id
                    ORDER BY r.id DESC");
?>

<h2>Record Production</h2>

<form method="post" class="card">
  <div class="grid-3">
    <label>Recipe
      <select name="recipe_id" required>
        <option value="">-- Select --</option>
        <?php foreach ($recipes as $r): ?>
          <option value="<?= $r['id'] ?>">
            <?= htmlspecialchars($r['name']).' → '.htmlspecialchars($r['product']).' (Yield '.$r['yield_qty'].')' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Batches
      <input type="number" name="batches" min="1" value="1" required>
    </label>

    <label>Date
      <input type="date" name="production_date" value="<?= date('Y-m-d') ?>" required>
    </label>
  </div>

  <label>Notes
    <input name="notes">
  </label>

  <button>Save Production</button>
</form>
