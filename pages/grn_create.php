<?php
// pages/grn_create.php
// Expects config.php + lib/functions.php included via layout/header.php

// ===== Handle submit =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grn_no'])) {
  $grn_no      = esc($_POST['grn_no']);
  $supplier_id = (int)($_POST['supplier_id'] ?? 0);
  $grn_date    = esc($_POST['grn_date'] ?? date('Y-m-d'));
  $notes       = esc($_POST['notes'] ?? '');

  $types   = $_POST['item_type'] ?? [];
  $raw_ids = $_POST['raw_material_id'] ?? [];
  $prod_ids= $_POST['product_id'] ?? [];
  $qtys    = $_POST['qty'] ?? [];
  $costs   = $_POST['unit_cost'] ?? [];

  if (!$grn_no || !$supplier_id || empty($types)) {
    flash('err','Missing required fields');
    header('Location: index.php?page=grn_create'); exit;
  }

  // Create GRN header
  q("INSERT INTO grns (grn_no, supplier_id, grn_date, notes, total_cost)
     VALUES ('$grn_no', $supplier_id, '$grn_date', '$notes', 0)");
  $grn_id = mysqli_insert_id($conn);

  $grand_total = 0.0;

  // Iterate items
  for ($i = 0; $i < count($types); $i++) {
    $type = $types[$i] ?? 'raw';
    $qty  = num($qtys[$i] ?? 0);
    $uc   = num($costs[$i] ?? 0);
    if ($qty <= 0) { continue; }

    $total = $qty * $uc;
    $grand_total += $total;

    if ($type === 'product') {
      $pid = (int)($prod_ids[$i] ?? 0);
      if (!$pid) { continue; }

      // Insert GRN item as product line (raw_material_id NULL)
      q("INSERT INTO grn_items (grn_id, raw_material_id, product_id, qty, unit_cost, total_cost)
         VALUES ($grn_id, NULL, $pid, $qty, $uc, $total)");

      // Update product stock
      q("UPDATE products SET current_qty = current_qty + $qty WHERE id = $pid");
      $newBal = get_one("SELECT current_qty FROM products WHERE id = $pid")['current_qty'];

      // Ledger (product)
      q("INSERT INTO stock_ledger
         (item_type,item_id,ref_type,ref_id,entry_date,qty_in,qty_out,balance_after,note)
         VALUES ('product',$pid,'GRN',$grn_id,'".now()."',$qty,0,$newBal,'GRN $grn_no')");

    } else {
      $rid = (int)($raw_ids[$i] ?? 0);
      if (!$rid) { continue; }

      // Insert GRN item as raw line (product_id NULL)
      q("INSERT INTO grn_items (grn_id, raw_material_id, product_id, qty, unit_cost, total_cost)
         VALUES ($grn_id, $rid, NULL, $qty, $uc, $total)");

      // Update raw stock
      q("UPDATE raw_materials SET current_qty = current_qty + $qty WHERE id = $rid");
      $newBal = get_one("SELECT current_qty FROM raw_materials WHERE id = $rid")['current_qty'];

      // Ledger (raw)
      q("INSERT INTO stock_ledger
         (item_type,item_id,ref_type,ref_id,entry_date,qty_in,qty_out,balance_after,note)
         VALUES ('raw',$rid,'GRN',$grn_id,'".now()."',$qty,0,$newBal,'GRN $grn_no')");
    }
  }

  // Update total
  q("UPDATE grns SET total_cost = $grand_total WHERE id = $grn_id");

  flash('ok','GRN saved');
  header('Location: index.php?page=grn_list'); exit;
}

// ===== Form data =====
$suppliers = get_all('SELECT * FROM suppliers ORDER BY name');
$raws = get_all('SELECT r.id, r.name, u.symbol
                 FROM raw_materials r
                 JOIN units u ON u.id = r.unit_id
                 ORDER BY r.name');

$prods = get_all('SELECT p.id, p.name, u.symbol
                  FROM products p
                  JOIN units u ON u.id = p.unit_id
                  ORDER BY p.name');
?>

<h2>Create GRN</h2>

<form method="post" class="card">
  <div class="grid-3">
    <label>GRN No
      <input name="grn_no" required>
    </label>
    <label>Date
      <input type="date" name="grn_date" value="<?= date('Y-m-d') ?>" required>
    </label>
    <label>Supplier
      <select name="supplier_id" required>
        <option value="">-- Select --</option>
        <?php foreach($suppliers as $s): ?>
          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>

  <table class="table" id="items">
    <tr>
      <th>Type</th>
      <th>Item</th>
      <th>Qty</th>
      <th>Unit Cost</th>
      <th>Total</th>
      <th></th>
    </tr>

    <tr>
      <td>
        <select name="item_type[]" onchange="toggleItemSelect(this)" required>
          <option value="raw">Raw</option>
          <option value="product">Product</option>
        </select>
      </td>
      <td>
        <!-- Raw select -->
        <select name="raw_material_id[]" class="sel-raw">
          <option value="">-- Select Raw --</option>
          <?php foreach($raws as $r): ?>
            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']).' ('.$r['symbol'].')' ?></option>
          <?php endforeach; ?>
        </select>

        <!-- Product select -->
        <select name="product_id[]" class="sel-prod" style="display:none">
          <option value="">-- Select Product --</option>
          <?php foreach($prods as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']).' ('.$p['symbol'].')' ?></option>
          <?php endforeach; ?>
        </select>
      </td>
      <td><input type="number" step="0.001" name="qty[]" value="0" required></td>
      <td><input type="number" step="0.001" name="unit_cost[]" value="0" required></td>
      <td class="row-total">0.000</td>
      <td><button type="button" onclick="addRow()">＋</button></td>
    </tr>
  </table>

  <label>Notes
    <input name="notes">
  </label>

  <div class="right">
    <strong>Grand Total: <span id="grand">0.000</span></strong>
  </div>

  <button>Save GRN</button>
</form>

<script>
function toggleItemSelect(sel){
  const tr = sel.closest('tr');
  const isProd = sel.value === 'product';
  const rawSel  = tr.querySelector('.sel-raw');
  const prodSel = tr.querySelector('.sel-prod');
  if (!rawSel || !prodSel) return;
  rawSel.style.display  = isProd ? 'none' : '';
  prodSel.style.display = isProd ? '' : 'none';
  if (isProd) rawSel.value=''; else prodSel.value='';
}

function addRow(){
  const tbl = document.getElementById('items');
  const tr = tbl.rows[1].cloneNode(true); // clone first data row
  tr.querySelectorAll('input').forEach(i => i.value = '0');
  tr.querySelector('.row-total').textContent = '0.000';
  const typeSel = tr.querySelector('select[name="item_type[]"]');
  if (typeSel){ typeSel.value = 'raw'; }
  const rawSel  = tr.querySelector('.sel-raw');  if (rawSel){ rawSel.style.display=''; rawSel.value=''; }
  const prodSel = tr.querySelector('.sel-prod'); if (prodSel){ prodSel.style.display='none'; prodSel.value=''; }

  const btn = document.createElement('button');
  btn.type='button'; btn.textContent='－';
  btn.onclick = function(){ this.closest('tr').remove(); calc(); };
  tr.cells[5].innerHTML=''; tr.cells[5].appendChild(btn);

  tbl.tBodies[0].appendChild(tr);
  calc();
}

function calc(){
  let grand = 0;
  document.querySelectorAll('#items tr').forEach((tr,i)=>{
    if (i===0) return;
    const qty = parseFloat(tr.querySelector('input[name="qty[]"]')?.value || '0') || 0;
    const uc  = parseFloat(tr.querySelector('input[name="unit_cost[]"]')?.value || '0') || 0;
    const t = qty * uc;
    const cell = tr.querySelector('.row-total');
    if (cell) cell.textContent = t.toFixed(3);
    grand += t;
  });
  const g = document.getElementById('grand');
  if (g) g.textContent = grand.toFixed(3);
}
setInterval(calc, 300);
</script>
