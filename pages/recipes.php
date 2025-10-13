<?php // pages/recipes.php

// ===== SEARCH FUNCTIONALITY =====
$search_query = '';
$where_conditions = [];

if (($_GET['search'] ?? '') === '1') {
    $search_query = esc($_GET['search_query'] ?? '');
    
    if (!empty($search_query)) {
        $search_like = "'%" . $search_query . "%'";
        $where_conditions[] = "(r.name LIKE $search_like OR 
                               p.name LIKE $search_like OR 
                               EXISTS (SELECT 1 FROM recipe_items ri 
                                       JOIN raw_materials rm ON rm.id = ri.raw_material_id 
                                       WHERE ri.recipe_id = r.id AND rm.name LIKE $search_like))";
    }
}

// Build WHERE clause
$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = ' WHERE ' . implode(' AND ', $where_conditions);
}

// ===== UPDATE RECIPE =====
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['recipe_id']) && isset($_POST['update'])){
  $recipe_id = (int)($_POST['recipe_id'] ?? 0);
  $product_id = (int)($_POST['product_id'] ?? 0);
  $name = esc($_POST['name'] ?? '');
  $yield = num($_POST['yield_qty'] ?? 0);
  $notes = esc($_POST['notes'] ?? '');
  $rids = $_POST['raw_material_id'] ?? [];
  $qtys = $_POST['qty'] ?? [];

  if($recipe_id && $product_id && $name && $yield > 0 && !empty($rids)){
    // Update recipe header
    q("UPDATE recipes SET product_id=$product_id, name='$name', yield_qty=$yield, notes='$notes' WHERE id=$recipe_id");
    
    // Delete old recipe items
    q("DELETE FROM recipe_items WHERE recipe_id=$recipe_id");
    
    // Insert new recipe items
    for($i=0; $i<count($rids); $i++){
      $rid = (int)$rids[$i]; 
      $qv = num($qtys[$i]);
      if($rid && $qv > 0){ 
        q("INSERT INTO recipe_items (recipe_id, raw_material_id, qty) VALUES ($recipe_id, $rid, $qv)"); 
      }
    }
    flash('ok','Recipe updated successfully');
    header('Location: index.php?page=recipes'); exit;
  } else {
    flash('err','Missing required fields');
  }
}

// ===== CREATE NEW RECIPE =====
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['product_id']) && !isset($_POST['update'])){
  $product_id = (int)($_POST['product_id'] ?? 0);
  $name = esc($_POST['name'] ?? '');
  $yield = num($_POST['yield_qty'] ?? 0);
  $notes = esc($_POST['notes'] ?? '');
  $rids = $_POST['raw_material_id'] ?? [];
  $qtys = $_POST['qty'] ?? [];
  
  if($product_id && $name && $yield > 0 && !empty($rids)){
    q("INSERT INTO recipes (product_id, name, yield_qty, notes) VALUES ($product_id, '$name', $yield, '$notes')");
    $recipe_id = mysqli_insert_id($conn);
    
    for($i=0; $i<count($rids); $i++){
      $rid = (int)$rids[$i]; 
      $qv = num($qtys[$i]);
      if($rid && $qv > 0){ 
        q("INSERT INTO recipe_items (recipe_id, raw_material_id, qty) VALUES ($recipe_id, $rid, $qv)"); 
      }
    }
    flash('ok','Recipe saved');
    header('Location: index.php?page=recipes'); exit;
  } else {
    flash('err','Missing required fields');
  }
}

// ===== DELETE RECIPE =====
if (($_GET['action'] ?? '') === 'delete') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id > 0) {
    // Check if used in production
    $used = get_one("SELECT COUNT(*) c FROM productions WHERE recipe_id=$id")['c'];
    if ($used) {
      flash('err','Cannot delete: Recipe has production records');
    } else {
      q("DELETE FROM recipe_items WHERE recipe_id=$id");
      q("DELETE FROM recipes WHERE id=$id");
      flash('ok','Recipe deleted');
    }
  }
  header('Location: index.php?page=recipes'); exit;
}

// ===== EDIT MODE - Load recipe data =====
$edit = null;
$edit_items = [];
if (($_GET['action'] ?? '') === 'edit') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id > 0) {
    $edit = get_one("SELECT * FROM recipes WHERE id=$id");
    if ($edit) {
      $edit_items = get_all("SELECT ri.*, rm.name, u.symbol 
                             FROM recipe_items ri
                             JOIN raw_materials rm ON rm.id = ri.raw_material_id
                             JOIN units u ON u.id = rm.unit_id
                             WHERE ri.recipe_id=$id");
    }
  }
}

// ===== VIEW MODE - Load recipe details =====
$view = null;
$view_items = [];
if (($_GET['action'] ?? '') === 'view') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id > 0) {
    $view = get_one("SELECT r.*, p.name AS product_name, u.symbol AS product_unit
                     FROM recipes r
                     JOIN products p ON p.id = r.product_id
                     JOIN units u ON u.id = p.unit_id
                     WHERE r.id=$id");
    if ($view) {
      $view_items = get_all("SELECT ri.qty, rm.name AS raw_name, u.symbol AS raw_unit
                             FROM recipe_items ri
                             JOIN raw_materials rm ON rm.id = ri.raw_material_id
                             JOIN units u ON u.id = rm.unit_id
                             WHERE ri.recipe_id=$id
                             ORDER BY rm.name");
    }
  }
}

$products = get_all('SELECT p.id, p.name, u.symbol FROM products p JOIN units u ON u.id=p.unit_id ORDER BY p.name');
$raws = get_all('SELECT r.id, r.name, u.symbol FROM raw_materials r JOIN units u ON u.id=r.unit_id ORDER BY r.name');
$recipes = get_all("SELECT r.*, p.name product_name FROM recipes r JOIN products p ON p.id=r.product_id $where_clause ORDER BY r.id DESC");
?>

<h2>Recipes</h2>

<?php if ($view): ?>
  <!-- ===== VIEW RECIPE DETAILS ===== -->
  <div class="card">
    <h3 style="margin-top:0">Recipe Details</h3>
    <div class="grid-2">
      <div>
        <strong>Recipe Name:</strong> <?= htmlspecialchars($view['name']) ?><br>
        <strong>Product:</strong> <?= htmlspecialchars($view['product_name']) ?> (<?= htmlspecialchars($view['product_unit']) ?>)<br>
        <strong>Yield per Batch:</strong> <?= $view['yield_qty'] ?> <?= htmlspecialchars($view['product_unit']) ?><br>
        <strong>Notes:</strong> <?= htmlspecialchars($view['notes'] ?: 'N/A') ?>
      </div>
    </div>

    <h4>Ingredients (per batch)</h4>
    <table class="table">
      <tr>
        <th>#</th>
        <th>Raw Material</th>
        <th>Quantity</th>
        <th>Unit</th>
      </tr>
      <?php foreach($view_items as $i => $item): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= htmlspecialchars($item['raw_name']) ?></td>
          <td><?= number_format($item['qty'], 3) ?></td>
          <td><?= htmlspecialchars($item['raw_unit']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>

    <div style="margin-top:12px">
      <a href="index.php?page=recipes&action=edit&id=<?= (int)$view['id'] ?>" class="button" style="text-decoration:none;padding:8px 12px;border:1px solid #334155;border-radius:8px;background:#0b1220;color:#e2e8f0;display:inline-block">Edit Recipe</a>
      <a href="index.php?page=recipes<?= !empty($search_query) ? '&search=1&search_query=' . urlencode($search_query) : '' ?>" style="margin-left:8px">Back to List</a>
    </div>
  </div>

<?php else: ?>
  <!-- ===== SEARCH SECTION ===== -->
  <div class="card" style="margin-bottom: 20px;">
    <h3 style="margin-top:0">Search Recipes</h3>
    <form method="get" style="display: flex; gap: 10px; align-items: end;">
      <input type="hidden" name="page" value="recipes">
      
      <div style="flex: 1;">
        <label>Search by recipe name, product, or ingredient</label>
        <input type="text" name="search_query" value="<?= htmlspecialchars($search_query) ?>" 
               placeholder="Enter recipe name, product, or ingredient..." style="width: 100%;">
      </div>
      
      <div>
        <button type="submit" name="search" value="1">Search</button>
        <?php if (!empty($search_query)): ?>
          <a href="index.php?page=recipes" style="margin-left: 8px;">Clear</a>
        <?php endif; ?>
      </div>
    </form>
    
    <?php if (!empty($search_query)): ?>
      <p style="margin: 10px 0 0 0; font-style: italic;">
        Showing results for: "<?= htmlspecialchars($search_query) ?>"
        <?php if (count($recipes) > 0): ?>
          (<?= count($recipes) ?> recipe<?= count($recipes) !== 1 ? 's' : '' ?> found)
        <?php endif; ?>
      </p>
    <?php endif; ?>
  </div>

  <!-- ===== CREATE/EDIT RECIPE FORM ===== -->
  <form method="post" class="card">
    <?php if ($edit): ?>
      <input type="hidden" name="recipe_id" value="<?= $edit['id'] ?>">
      <input type="hidden" name="update" value="1">
    <?php endif; ?>

    <div class="grid-3">
      <label>Product
        <select name="product_id" required>
          <option value="">-- Select --</option>
          <?php foreach($products as $p): ?>
            <option value="<?= $p['id'] ?>" <?= ($edit && $edit['product_id'] == $p['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($p['name']).' ('.$p['symbol'].')' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Recipe Name 
        <input name="name" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" required>
      </label>
      <label>Yield Qty (per batch) 
        <input type="number" step="0.001" name="yield_qty" value="<?= $edit['yield_qty'] ?? '' ?>" required>
      </label>
    </div>

    <h4>Ingredients</h4>
    <table class="table" id="ritems">
      <tr>
        <th>Raw Material</th>
        <th>Qty per batch</th>
        <th></th>
      </tr>
      
      <?php if ($edit && !empty($edit_items)): ?>
        <!-- Edit mode: show existing items -->
        <?php foreach($edit_items as $idx => $item): ?>
          <tr>
            <td>
              <select name="raw_material_id[]" required>
                <option value="">-- Select --</option>
                <?php foreach($raws as $r): ?>
                  <option value="<?= $r['id'] ?>" <?= ($item['raw_material_id'] == $r['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($r['name']).' ('.$r['symbol'].')' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><input type="number" step="0.001" name="qty[]" value="<?= $item['qty'] ?>" required></td>
            <td>
              <?php if ($idx === 0): ?>
                <button type="button" onclick="addR()">+</button>
              <?php else: ?>
                <button type="button" onclick="this.closest('tr').remove()">−</button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <!-- Create mode: single empty row -->
        <tr>
          <td>
            <select name="raw_material_id[]" required>
              <option value="">-- Select --</option>
              <?php foreach($raws as $r): ?>
                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']).' ('.$r['symbol'].')' ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td><input type="number" step="0.001" name="qty[]" value="0" required></td>
          <td><button type="button" onclick="addR()">+</button></td>
        </tr>
      <?php endif; ?>
    </table>

    <label>Notes 
      <input name="notes" value="<?= htmlspecialchars($edit['notes'] ?? '') ?>">
    </label>

    <button><?= $edit ? 'Update Recipe' : 'Save Recipe' ?></button>
    <?php if ($edit): ?>
      <a href="index.php?page=recipes<?= !empty($search_query) ? '&search=1&search_query=' . urlencode($search_query) : '' ?>" style="margin-left:8px">Cancel</a>
    <?php endif; ?>
  </form>

  <!-- ===== EXISTING RECIPES LIST ===== -->
  <h3>Existing Recipes <?= !empty($search_query) ? '(Filtered)' : '' ?></h3>
  
  <?php if (empty($recipes)): ?>
    <div class="card" style="text-align: center; padding: 30px;">
      <p style="margin: 0; font-style: italic;">
        <?php if (!empty($search_query)): ?>
          No recipes found matching "<?= htmlspecialchars($search_query) ?>"
        <?php else: ?>
          No recipes found. Create your first recipe above.
        <?php endif; ?>
      </p>
    </div>
  <?php else: ?>
    <table class="table">
      <tr>
        <th>ID</th>
        <th>Recipe</th>
        <th>Product</th>
        <th>Yield</th>
        <th>Actions</th>
      </tr>
      <?php foreach($recipes as $r): ?>
        <tr>
          <td><?= $r['id'] ?></td>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td><?= htmlspecialchars($r['product_name']) ?></td>
          <td><?= $r['yield_qty'] ?></td>
          <td>
            <a href="index.php?page=recipes&action=view&id=<?= $r['id'] ?><?= !empty($search_query) ? '&search=1&search_query=' . urlencode($search_query) : '' ?>">View</a> |
            <a href="index.php?page=recipes&action=edit&id=<?= $r['id'] ?><?= !empty($search_query) ? '&search=1&search_query=' . urlencode($search_query) : '' ?>">Edit</a> |
            <a href="index.php?page=recipes&action=delete&id=<?= $r['id'] ?>" onclick="return confirm('Delete this recipe?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
<?php endif; ?>

<script>
function addR(){
  const t = document.getElementById('ritems');
  const tr = t.rows[1].cloneNode(true);
  tr.querySelector('input').value = '0';
  tr.querySelectorAll('select').forEach(s => s.value = '');
  
  const btn = document.createElement('button'); 
  btn.type = 'button'; 
  btn.textContent = '−'; 
  btn.onclick = function(){this.closest('tr').remove();};
  
  tr.cells[2].innerHTML = ''; 
  tr.cells[2].appendChild(btn);
  t.tBodies[0].appendChild(tr);
}
</script>