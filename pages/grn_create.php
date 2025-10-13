<?php
// pages/grn_create.php
require_once __DIR__ . '/../auth.php';

// Check permissions - Admin and Manager can create GRN
if (!canCreateGRN()) {
    flash('err', 'Access denied. Only administrators and managers can create GRN.');
    header('Location: index.php');
    exit;
}

// ===== AUTO-GENERATE GRN NUMBER =====
function generate_grn_number() {
  $today = date('Ymd');
  $prefix = "GRN-$today-";
  
  $last = get_one("SELECT grn_no FROM grns 
                   WHERE grn_no LIKE '".esc($prefix)."%' 
                   ORDER BY id DESC LIMIT 1");
  
  if ($last) {
    $last_num = (int)substr($last['grn_no'], -3);
    $new_num = $last_num + 1;
  } else {
    $new_num = 1;
  }
  
  return $prefix . str_pad($new_num, 3, '0', STR_PAD_LEFT);
}

// ===== Handle submit =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supplier_id'])) {
  $grn_no      = generate_grn_number();
  $supplier_id = (int)($_POST['supplier_id'] ?? 0);
  $grn_date    = esc($_POST['grn_date'] ?? date('Y-m-d'));
  $notes       = esc($_POST['notes'] ?? '');

  $types   = $_POST['item_type'] ?? [];
  $raw_ids = $_POST['raw_material_id'] ?? [];
  $prod_ids= $_POST['product_id'] ?? [];
  $qtys    = $_POST['qty'] ?? [];
  $costs   = $_POST['unit_cost'] ?? [];

  if (!$supplier_id || empty($types)) {
    flash('err','Missing required fields');
    header('Location: index.php?page=grn_create'); exit;
  }

  q("INSERT INTO grns (grn_no, supplier_id, grn_date, notes, total_cost)
     VALUES ('$grn_no', $supplier_id, '$grn_date', '$notes', 0)");
  $grn_id = mysqli_insert_id($conn);

  $grand_total = 0.0;

  q("START TRANSACTION");

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

      q("INSERT INTO grn_items (grn_id, raw_material_id, product_id, qty, unit_cost, total_cost)
         VALUES ($grn_id, NULL, $pid, $qty, $uc, $total)");

      // Add FIFO entry
      q("INSERT INTO stock_fifo (grn_id, item_type, item_id, qty_received, qty_remaining)
         VALUES ($grn_id, 'product', $pid, $qty, $qty)");

      q("UPDATE products SET current_qty = current_qty + $qty WHERE id = $pid");
      $newBal = get_one("SELECT current_qty FROM products WHERE id = $pid")['current_qty'];

      q("INSERT INTO stock_ledger
         (item_type,item_id,ref_type,ref_id,entry_date,qty_in,qty_out,balance_after,note)
         VALUES ('product',$pid,'GRN',$grn_id,'".now()."',$qty,0,$newBal,'GRN $grn_no')");

    } else {
      $rid = (int)($raw_ids[$i] ?? 0);
      if (!$rid) { continue; }

      q("INSERT INTO grn_items (grn_id, raw_material_id, product_id, qty, unit_cost, total_cost)
         VALUES ($grn_id, $rid, NULL, $qty, $uc, $total)");

      // Add FIFO entry
      q("INSERT INTO stock_fifo (grn_id, item_type, item_id, qty_received, qty_remaining)
         VALUES ($grn_id, 'raw', $rid, $qty, $qty)");

      q("UPDATE raw_materials SET current_qty = current_qty + $qty WHERE id = $rid");
      $newBal = get_one("SELECT current_qty FROM raw_materials WHERE id = $rid")['current_qty'];

      q("INSERT INTO stock_ledger
         (item_type,item_id,ref_type,ref_id,entry_date,qty_in,qty_out,balance_after,note)
         VALUES ('raw',$rid,'GRN',$grn_id,'".now()."',$qty,0,$newBal,'GRN $grn_no')");
    }
  }

  q("UPDATE grns SET total_cost = $grand_total WHERE id = $grn_id");
  q("COMMIT");

  flash('ok',"GRN saved successfully! GRN Number: $grn_no");
  header('Location: index.php?page=grn_list'); exit;
}

// ===== Form data =====
$suppliers = get_all('SELECT * FROM suppliers ORDER BY name');
$raws = get_all('SELECT r.id, r.name, u.symbol, u.id as unit_id
                 FROM raw_materials r
                 JOIN units u ON u.id = r.unit_id
                 ORDER BY r.name');

$prods = get_all('SELECT p.id, p.name, u.symbol, u.id as unit_id
                  FROM products p
                  JOIN units u ON u.id = p.unit_id
                  ORDER BY p.name');

$next_grn = generate_grn_number();
?>

<h2>Create GRN</h2>

<div class="card" style="background:#065f46;margin-bottom:16px;padding:12px">
  <strong>Next GRN Number:</strong> <span style="font-size:18px"><?= htmlspecialchars($next_grn) ?></span>
  <br><small>Auto-generated based on current date</small>
</div>

<form method="post" class="card">
  <div class="grid-2">
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
      <th>Unit</th>
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
        <select name="raw_material_id[]" class="sel-raw" onchange="updateUnit(this)">
          <option value="">-- Select Raw --</option>
          <?php foreach($raws as $r): ?>
            <option value="<?= $r['id'] ?>" data-unit="<?= htmlspecialchars($r['symbol']) ?>">
              <?= htmlspecialchars($r['name']).' ('.$r['symbol'].')' ?>
            </option>
          <?php endforeach; ?>
        </select>

        <select name="product_id[]" class="sel-prod" style="display:none" onchange="updateUnit(this)">
          <option value="">-- Select Product --</option>
          <?php foreach($prods as $p): ?>
            <option value="<?= $p['id'] ?>" data-unit="<?= htmlspecialchars($p['symbol']) ?>">
              <?= htmlspecialchars($p['name']).' ('.$p['symbol'].')' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </td>
      <td class="unit-display">—</td>
      <td><input type="number" step="0.001" name="qty[]" value="0" required></td>
      <td><input type="number" step="0.001" name="unit_cost[]" value="0" required></td>
      <td class="row-total">0.000</td>
      <td><button type="button" onclick="addRow()">+</button></td>
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
  rawSel.value=''; prodSel.value='';
  updateUnit(isProd ? prodSel : rawSel);
}

function updateUnit(sel){
  const tr = sel.closest('tr');
  const unitCell = tr.querySelector('.unit-display');
  const unit = sel.options[sel.selectedIndex]?.dataset?.unit || '—';
  if (unitCell) unitCell.textContent = unit;
}

function addRow(){
  const tbl = document.getElementById('items');
  const tr = tbl.rows[1].cloneNode(true);
  tr.querySelectorAll('input').forEach(i => i.value = '0');
  tr.querySelector('.row-total').textContent = '0.000';
  tr.querySelector('.unit-display').textContent = '—';
  
  const typeSel = tr.querySelector('select[name="item_type[]"]');
  if (typeSel){ typeSel.value = 'raw'; }
  
  const rawSel  = tr.querySelector('.sel-raw');  
  if (rawSel){ rawSel.style.display=''; rawSel.value=''; }
  
  const prodSel = tr.querySelector('.sel-prod'); 
  if (prodSel){ prodSel.style.display='none'; prodSel.value=''; }

  const btn = document.createElement('button');
  btn.type='button'; btn.textContent='−';
  btn.onclick = function(){ this.closest('tr').remove(); calc(); };
  tr.cells[6].innerHTML=''; tr.cells[6].appendChild(btn);

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