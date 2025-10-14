<?php
// pages/products.php
require_once __DIR__ . '/../auth.php';

// Check permissions
if (!canEdit()) {
    flash('err', 'Access denied. Only administrators can manage products.');
    header('Location: index.php');
    exit;
}

// // ===== Create/Update =====
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//   $id      = (int)($_POST['id'] ?? 0);
//   $name    = esc($_POST['name'] ?? '');
//   $unit_id = (int)($_POST['unit_id'] ?? 0);
//   $opening = num($_POST['opening_qty'] ?? 0);
//   $price   = num($_POST['selling_price'] ?? 0);

//   if (!$name || !$unit_id) {
//     flash('err','Name and Unit required'); header('Location: index.php?page=products'); exit;
//   }

//   if ($id > 0) {
//     q("UPDATE products SET name='$name', unit_id=$unit_id, selling_price=$price WHERE id=$id");
//     flash('ok','Product updated');
//   } else {
//     q("INSERT INTO products (name, unit_id, opening_qty, current_qty, selling_price)
//        VALUES ('$name', $unit_id, $opening, $opening, $price)");
//     $pid = mysqli_insert_id($conn);
//     if ($opening > 0) {
//       q("INSERT INTO stock_ledger
//          (item_type,item_id,ref_type,ref_id,entry_date,qty_in,qty_out,balance_after,note)
//          VALUES ('product',$pid,'OPENING',NULL,'".now()."',$opening,0,$opening,'Opening balance')");
//     }
//     flash('ok','Product added');
//   }
//   header('Location: index.php?page=products'); exit;
// }

// // ===== Delete =====
// if (($_GET['action'] ?? '') === 'delete') {
//   $id = (int)($_GET['id'] ?? 0);
//   if ($id > 0) {
//     // Block delete if referenced by recipe or production
//     $usedRec = get_one("SELECT COUNT(*) c FROM recipes WHERE product_id=$id")['c'];
//     $usedProd= get_one("SELECT COUNT(*) c
//                         FROM productions pr
//                         JOIN recipes r ON r.id = pr.recipe_id
//                         WHERE r.product_id = $id")['c'];
//     if ($usedRec || $usedProd) {
//       flash('err','Cannot delete: Product has recipes or productions');
//     } else {
//       q("DELETE FROM products WHERE id=$id");
//       flash('ok','Product deleted');
//     }
//   }
//   header('Location: index.php?page=products'); exit;
// }

// // ===== Edit (prefill) =====
// $edit = null;
// if (($_GET['action'] ?? '') === 'edit') {
//   $id = (int)($_GET['id'] ?? 0);
//   if ($id > 0) $edit = get_one("SELECT * FROM products WHERE id=$id");
// }

// ===== Units =====
$units = get_all('SELECT * FROM units ORDER BY name');

// ===== Cost calculation =====
// A) avg GRN cost per RAW material
$rawAvgCost = [];
$tmp = get_all("
  SELECT gi.raw_material_id AS rid,
         CASE WHEN SUM(gi.qty)=0 THEN 0 ELSE SUM(gi.total_cost)/SUM(gi.qty) END AS avg_cost
  FROM grn_items gi
  WHERE gi.raw_material_id IS NOT NULL
  GROUP BY gi.raw_material_id
");
foreach ($tmp as $r) $rawAvgCost[(int)$r['rid']] = (float)$r['avg_cost'];

// B) recipes per product
$recipes = []; // product_id => ['id'=>rid,'yield'=>float]
$tmp = get_all("SELECT id, product_id, yield_qty FROM recipes");
foreach ($tmp as $rec) { $recipes[(int)$rec['product_id']] = ['id'=>(int)$rec['id'],'yield'=>(float)$rec['yield_qty']]; }

// C) recipe items
$recipeItems = []; // recipe_id => [ [raw_material_id, qty], ... ]
if ($recipes) {
  $ids = implode(',', array_map('intval', array_column($recipes,'id')));
  if ($ids !== '') {
    $ri = get_all("SELECT recipe_id, raw_material_id, qty
                   FROM recipe_items WHERE recipe_id IN ($ids)");
    foreach ($ri as $row) {
      $recipeItems[(int)$row['recipe_id']][] = [
        'raw_material_id' => (int)$row['raw_material_id'],
        'qty'             => (float)$row['qty'],
      ];
    }
  }
}

// D) Compute recipe-based cost per product (per unit)
$productCost = []; // pid => float|null
foreach ($recipes as $pid => $info) {
  $recId = $info['id'];
  $yield = max(0.000001, $info['yield']);
  $items = $recipeItems[$recId] ?? [];
  if (!$items) { $productCost[$pid] = null; continue; }
  $batchCost = 0.0; $missing = false;
  foreach ($items as $it) {
    $rm = $it['raw_material_id'];
    $q  = $it['qty'];
    if (!array_key_exists($rm, $rawAvgCost)) { $missing = true; break; }
    $batchCost += $q * $rawAvgCost[$rm];
  }
  $productCost[$pid] = $missing ? null : ($batchCost / $yield);
}

// E) Fallback: for products without recipe, use avg cost from PRODUCT GRN lines
$tmp = get_all("
  SELECT product_id AS pid,
         CASE WHEN SUM(qty)=0 THEN 0 ELSE SUM(total_cost)/SUM(qty) END AS avg_cost
  FROM grn_items
  WHERE product_id IS NOT NULL
  GROUP BY product_id
");
foreach ($tmp as $row) {
  $pid = (int)$row['pid'];
  $avg = (float)$row['avg_cost'];
  if (!isset($productCost[$pid]) || $productCost[$pid] === null) {
    $productCost[$pid] = $avg; // finished goods (e.g., soft drinks)
  }
}

// ===== Fetch products =====
$rows = get_all("SELECT p.*, u.symbol
                 FROM products p
                 JOIN units u ON u.id = p.unit_id
                 ORDER BY p.id DESC");
?>

<h2>Products (Final Dishes & Finished Goods)</h2>

<!-- <form method="post" class="card">
  <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
  <label>Name
    <input name="name" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" required>
  </label>

  <label>Unit
    <select name="unit_id" required>
      <option value="">-- Select --</option>
      <?php foreach($units as $u): ?>
        <option value="<?= (int)$u['id'] ?>" <?= ($edit && (int)$edit['unit_id']===(int)$u['id'])?'selected':'' ?>>
          <?= htmlspecialchars($u['name']).' ('.$u['symbol'].')' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>Opening Qty
    <input type="number" step="0.001" name="opening_qty" value="<?= $edit['opening_qty'] ?? 0 ?>">
  </label>

  <label>Selling Price
    <input type="number" step="0.01" name="selling_price" value="<?= $edit['selling_price'] ?? 0 ?>">
  </label>

  <button><?= $edit ? 'Update Product' : 'Add Product' ?></button>
  <?php if ($edit): ?><a href="index.php?page=products" style="margin-left:8px">Cancel</a><?php endif; ?>
</form> -->

<table class="table">
  <tr>
    <th>ID</th>
    <th>Name</th>
    <!-- <th>Unit</th>
    <th>Current Qty</th>
    <th>Selling Price</th>
    <th>Current Cost (per unit)</th>
    <th>Margin</th> -->
    <!-- <th>Actions</th> -->
  </tr>
  <?php foreach ($rows as $r): ?>
    <?php
      $pid   = (int)$r['id'];
      $sp    = (float)$r['selling_price'];
      $ccost = $productCost[$pid] ?? null;

      $marginAbs = ($ccost !== null) ? ($sp - $ccost) : null;
      $marginPct = ($ccost !== null && $sp > 0) ? ($marginAbs / $sp * 100.0) : null;
    ?>
    <tr>
      <td><?= $pid ?></td>
      <td><?= htmlspecialchars($r['name']) ?></td>
      <!-- <td><?= htmlspecialchars($r['symbol']) ?></td>
      <td><?= (float)$r['current_qty'] ?></td>
      <td><?= number_format($sp, 2) ?></td>
      <td><?= $ccost === null ? '—' : number_format($ccost, 2) ?></td>
      <td>
        <?php if ($ccost === null): ?>
          —
        <?php else: ?>
          <?= number_format($marginAbs, 2) ?>
          <?= $marginPct !== null ? ' ('.number_format($marginPct, 1).'%)' : '' ?>
        <?php endif; ?>
      </td> -->
      <!-- <td>
        <a href="index.php?page=products&action=edit&id=<?= $pid ?>">Edit</a> |
        <a href="index.php?page=products&action=delete&id=<?= $pid ?>"
           onclick="return confirm('Delete this product?')">Delete</a>
      </td> -->
    </tr>
  <?php endforeach; ?>
</table>
