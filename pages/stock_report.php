<?php
// pages/stock_report.php
// Assumes config.php + lib/functions.php are already included via layout/header.php

// ---------- Helpers ----------
function dtrim($s){ return substr($s,0,10); } // YYYY-MM-DD from date inputs

// Get opening balance as of $start (exclusive): last ledger balance BEFORE $start,
// falling back to item opening_qty if no ledger yet.
function opening_balance($type, $item_id, $start_date) {
  $start_ts = esc($start_date . ' 00:00:00');
  $type = esc($type);
  $item_id = (int)$item_id;

  $row = get_one("SELECT balance_after
                  FROM stock_ledger
                  WHERE item_type='$type' AND item_id=$item_id AND entry_date < '$start_ts'
                  ORDER BY entry_date DESC, id DESC LIMIT 1");
  if ($row && isset($row['balance_after'])) return (float)$row['balance_after'];

  if ($type === 'raw') {
    $r = get_one("SELECT opening_qty FROM raw_materials WHERE id=$item_id");
    return $r ? (float)$r['opening_qty'] : 0.0;
  } else {
    $r = get_one("SELECT opening_qty FROM products WHERE id=$item_id");
    return $r ? (float)$r['opening_qty'] : 0.0;
  }
}

// Sum In/Out in date window (inclusive)
function range_sums($type, $item_id, $start_date, $end_date) {
  $type = esc($type);
  $item_id = (int)$item_id;
  $start_ts = esc($start_date . ' 00:00:00');
  $end_ts   = esc($end_date   . ' 23:59:59');

  $row = get_one("SELECT
                    COALESCE(SUM(qty_in),0)  AS tin,
                    COALESCE(SUM(qty_out),0) AS tout
                  FROM stock_ledger
                  WHERE item_type='$type' AND item_id=$item_id
                    AND entry_date BETWEEN '$start_ts' AND '$end_ts'");
  return [
    'in'  => (float)($row['tin'] ?? 0),
    'out' => (float)($row['tout'] ?? 0),
  ];
}

// ---------- Filters ----------
$today = date('Y-m-d');
$default_start = date('Y-m-d', strtotime('-7 days'));

$start = isset($_GET['start']) && $_GET['start'] !== '' ? dtrim($_GET['start']) : $default_start;
$end   = isset($_GET['end'])   && $_GET['end']   !== '' ? dtrim($_GET['end'])   : $today;
$hide_null = isset($_GET['hide_null']) && $_GET['hide_null'] === '1';

// Simple guard: if start > end, swap
if (strtotime($start) > strtotime($end)) {
  $tmp = $start; $start = $end; $end = $tmp;
}

// Excel export for Raw Materials
if (isset($_GET['export']) && $_GET['export'] === 'excel_raw') {
  header('Content-Type: application/vnd.ms-excel');
  header('Content-Disposition: attachment; filename="raw_materials_report_'.$start.'_to_'.$end.'.xls"');
  
  echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
  echo '<head><meta charset="utf-8"><style>table {border-collapse: collapse;} th, td {border: 1px solid black; padding: 8px;} th {background-color: #4CAF50; color: white;}</style></head>';
  echo '<body>';
  echo '<h2>Raw Materials Stock Report</h2>';
  echo '<p>Period: '.$start.' to '.$end.'</p>';
  echo '<table>';
  echo '<tr><th>#</th><th>Raw Material</th><th>Unit</th><th>Opening</th><th>In</th><th>Out</th><th>Closing</th></tr>';

  $raws = get_all("SELECT r.id, r.name, u.symbol
                   FROM raw_materials r
                   JOIN units u ON u.id=r.unit_id
                   ORDER BY r.name");
  
  $totals = ['opening'=>0,'in'=>0,'out'=>0,'closing'=>0];
  $i = 0;
  
  foreach ($raws as $r) {
    $op = opening_balance('raw', $r['id'], $start);
    $sum = range_sums('raw', $r['id'], $start, $end);
    $cl = $op + $sum['in'] - $sum['out'];
    
    // Skip if hide_null is enabled and all values are zero
    if ($hide_null && $op == 0 && $sum['in'] == 0 && $sum['out'] == 0 && $cl == 0) {
      continue;
    }
    
    $i++;
    echo '<tr>';
    echo '<td>'.$i.'</td>';
    echo '<td>'.htmlspecialchars($r['name']).'</td>';
    echo '<td>'.htmlspecialchars($r['symbol']).'</td>';
    echo '<td>'.number_format($op, 3).'</td>';
    echo '<td>'.number_format($sum['in'], 3).'</td>';
    echo '<td>'.number_format($sum['out'], 3).'</td>';
    echo '<td>'.number_format($cl, 3).'</td>';
    echo '</tr>';
    
    $totals['opening'] += $op;
    $totals['in'] += $sum['in'];
    $totals['out'] += $sum['out'];
    $totals['closing'] += $cl;
  }
  
  echo '<tr style="font-weight:bold; background-color:#f0f0f0;">';
  echo '<td colspan="3">TOTAL</td>';
  echo '<td>'.number_format($totals['opening'], 3).'</td>';
  echo '<td>'.number_format($totals['in'], 3).'</td>';
  echo '<td>'.number_format($totals['out'], 3).'</td>';
  echo '<td>'.number_format($totals['closing'], 3).'</td>';
  echo '</tr>';
  echo '</table>';
  echo '</body></html>';
  exit;
}

// Excel export for Products
if (isset($_GET['export']) && $_GET['export'] === 'excel_product') {
  header('Content-Type: application/vnd.ms-excel');
  header('Content-Disposition: attachment; filename="products_report_'.$start.'_to_'.$end.'.xls"');
  
  echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
  echo '<head><meta charset="utf-8"><style>table {border-collapse: collapse;} th, td {border: 1px solid black; padding: 8px;} th {background-color: #2196F3; color: white;}</style></head>';
  echo '<body>';
  echo '<h2>Products Stock Report</h2>';
  echo '<p>Period: '.$start.' to '.$end.'</p>';
  echo '<table>';
  echo '<tr><th>#</th><th>Product</th><th>Unit</th><th>Opening</th><th>In</th><th>Out</th><th>Closing</th></tr>';

  $prods = get_all("SELECT p.id, p.name, u.symbol
                    FROM products p
                    JOIN units u ON u.id=p.unit_id
                    ORDER BY p.name");
  
  $totals = ['opening'=>0,'in'=>0,'out'=>0,'closing'=>0];
  $i = 0;
  
  foreach ($prods as $p) {
    $op = opening_balance('product', $p['id'], $start);
    $sum = range_sums('product', $p['id'], $start, $end);
    $cl = $op + $sum['in'] - $sum['out'];
    
    // Skip if hide_null is enabled and all values are zero
    if ($hide_null && $op == 0 && $sum['in'] == 0 && $sum['out'] == 0 && $cl == 0) {
      continue;
    }
    
    $i++;
    echo '<tr>';
    echo '<td>'.$i.'</td>';
    echo '<td>'.htmlspecialchars($p['name']).'</td>';
    echo '<td>'.htmlspecialchars($p['symbol']).'</td>';
    echo '<td>'.number_format($op, 3).'</td>';
    echo '<td>'.number_format($sum['in'], 3).'</td>';
    echo '<td>'.number_format($sum['out'], 3).'</td>';
    echo '<td>'.number_format($cl, 3).'</td>';
    echo '</tr>';
    
    $totals['opening'] += $op;
    $totals['in'] += $sum['in'];
    $totals['out'] += $sum['out'];
    $totals['closing'] += $cl;
  }
  
  echo '<tr style="font-weight:bold; background-color:#f0f0f0;">';
  echo '<td colspan="3">TOTAL</td>';
  echo '<td>'.number_format($totals['opening'], 3).'</td>';
  echo '<td>'.number_format($totals['in'], 3).'</td>';
  echo '<td>'.number_format($totals['out'], 3).'</td>';
  echo '<td>'.number_format($totals['closing'], 3).'</td>';
  echo '</tr>';
  echo '</table>';
  echo '</body></html>';
  exit;
}

// CSV export (original - kept for backward compatibility)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="stock_report_'.$start.'_to_'.$end.'.csv"');

  $out = fopen('php://output', 'w');
  fputcsv($out, ['Type','Item','Unit','Opening','In','Out','Closing']);

  // RAW
  $raws = get_all("SELECT r.id, r.name, u.symbol
                   FROM raw_materials r
                   JOIN units u ON u.id=r.unit_id
                   ORDER BY r.name");
  foreach ($raws as $r) {
    $op = opening_balance('raw', $r['id'], $start);
    $sum = range_sums('raw', $r['id'], $start, $end);
    $cl = $op + $sum['in'] - $sum['out'];
    
    if ($hide_null && $op == 0 && $sum['in'] == 0 && $sum['out'] == 0 && $cl == 0) {
      continue;
    }
    
    fputcsv($out, ['raw', $r['name'], $r['symbol'], $op, $sum['in'], $sum['out'], $cl]);
  }

  // PRODUCTS
  $prods = get_all("SELECT p.id, p.name, u.symbol
                    FROM products p
                    JOIN units u ON u.id=p.unit_id
                    ORDER BY p.name");
  foreach ($prods as $p) {
    $op = opening_balance('product', $p['id'], $start);
    $sum = range_sums('product', $p['id'], $start, $end);
    $cl = $op + $sum['in'] - $sum['out'];
    
    if ($hide_null && $op == 0 && $sum['in'] == 0 && $sum['out'] == 0 && $cl == 0) {
      continue;
    }
    
    fputcsv($out, ['product', $p['name'], $p['symbol'], $op, $sum['in'], $sum['out'], $cl]);
  }
  fclose($out);
  exit;
}

// ---------- Data for screen ----------
$raws = get_all("SELECT r.id, r.name, u.symbol
                 FROM raw_materials r
                 JOIN units u ON u.id=r.unit_id
                 ORDER BY r.name");

$prods = get_all("SELECT p.id, p.name, u.symbol
                  FROM products p
                  JOIN units u ON u.id=p.unit_id
                  ORDER BY p.name");

// Precompute aggregates and grand totals
$raw_rows = [];
$raw_totals = ['opening'=>0,'in'=>0,'out'=>0,'closing'=>0];

foreach ($raws as $r) {
  $op = opening_balance('raw', $r['id'], $start);
  $sum = range_sums('raw', $r['id'], $start, $end);
  $cl = $op + $sum['in'] - $sum['out'];

  // Skip if hide_null is enabled and all values are zero
  if ($hide_null && $op == 0 && $sum['in'] == 0 && $sum['out'] == 0 && $cl == 0) {
    continue;
  }

  $raw_rows[] = [
    'id'   => (int)$r['id'],
    'name' => $r['name'],
    'unit' => $r['symbol'],
    'op'   => $op,
    'in'   => $sum['in'],
    'out'  => $sum['out'],
    'cl'   => $cl
  ];
  $raw_totals['opening'] += $op;
  $raw_totals['in']      += $sum['in'];
  $raw_totals['out']     += $sum['out'];
  $raw_totals['closing'] += $cl;
}

$prod_rows = [];
$prod_totals = ['opening'=>0,'in'=>0,'out'=>0,'closing'=>0];

foreach ($prods as $p) {
  $op = opening_balance('product', $p['id'], $start);
  $sum = range_sums('product', $p['id'], $start, $end);
  $cl = $op + $sum['in'] - $sum['out'];

  // Skip if hide_null is enabled and all values are zero
  if ($hide_null && $op == 0 && $sum['in'] == 0 && $sum['out'] == 0 && $cl == 0) {
    continue;
  }

  $prod_rows[] = [
    'id'   => (int)$p['id'],
    'name' => $p['name'],
    'unit' => $p['symbol'],
    'op'   => $op,
    'in'   => $sum['in'],
    'out'  => $sum['out'],
    'cl'   => $cl
  ];
  $prod_totals['opening'] += $op;
  $prod_totals['in']      += $sum['in'];
  $prod_totals['out']     += $sum['out'];
  $prod_totals['closing'] += $cl;
}

// Recent ledger in window
$ledger = get_all("
  SELECT id,item_type,item_id,ref_type,ref_id,entry_date,qty_in,qty_out,note
  FROM stock_ledger
  WHERE entry_date BETWEEN '".$start." 00:00:00' AND '".$end." 23:59:59'
  ORDER BY entry_date DESC, id DESC
  LIMIT 300
");
?>
<h2>Stock Report</h2>

<form method="get" class="card" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap">
  <input type="hidden" name="page" value="stock_report">
  <label>From
    <input type="date" name="start" value="<?= htmlspecialchars($start) ?>">
  </label>
  <label>To
    <input type="date" name="end" value="<?= htmlspecialchars($end) ?>">
  </label>
  <label style="display:flex; align-items:center; gap:6px;">
    <input type="checkbox" name="hide_null" value="1" <?= $hide_null ? 'checked' : '' ?>>
    Hide Null Items
  </label>
  <button>Apply</button>
  
  <div style="display:flex; gap:8px;">
    <a href="index.php?page=stock_report&start=<?= urlencode($start) ?>&end=<?= urlencode($end) ?>&hide_null=<?= $hide_null ? '1' : '0' ?>&export=excel_raw"
       class="button"
       style="text-decoration:none;padding:8px;border:1px solid #4CAF50;border-radius:8px;background:#0b1220;color:#4CAF50;">
      📊 Export Raw Materials (Excel)
    </a>
    <a href="index.php?page=stock_report&start=<?= urlencode($start) ?>&end=<?= urlencode($end) ?>&hide_null=<?= $hide_null ? '1' : '0' ?>&export=excel_product"
       class="button"
       style="text-decoration:none;padding:8px;border:1px solid #2196F3;border-radius:8px;background:#0b1220;color:#2196F3;">
      📊 Export Products (Excel)
    </a>
    <a href="index.php?page=stock_report&start=<?= urlencode($start) ?>&end=<?= urlencode($end) ?>&hide_null=<?= $hide_null ? '1' : '0' ?>&export=csv"
       class="button"
       style="text-decoration:none;padding:8px;border:1px solid #334155;border-radius:8px;background:#0b1220;color:#e2e8f0;">
      📄 Export CSV
    </a>
  </div>
</form>

<?php if ($hide_null): ?>
<div class="card" style="background:#fef3c7; color:#92400e; padding:10px; margin-bottom:12px;">
  ℹ️ Showing only items with activity (hiding zero/null items)
</div>
<?php endif; ?>

<div class="grid-2">
  <div class="card">
    <h3 style="margin-top:0">Raw Materials — Summary (<?= htmlspecialchars($start) ?> to <?= htmlspecialchars($end) ?>)</h3>
    <div class="grid-3">
      <div class="card"><strong>Opening</strong><div><?= number_format($raw_totals['opening'], 3) ?></div></div>
      <div class="card"><strong>In</strong><div><?= number_format($raw_totals['in'], 3) ?></div></div>
      <div class="card"><strong>Out</strong><div><?= number_format($raw_totals['out'], 3) ?></div></div>
    </div>
    <div class="card" style="margin-top:10px"><strong>Closing</strong><div><?= number_format($raw_totals['closing'], 3) ?></div></div>

    <table class="table" style="margin-top:12px">
      <tr>
        <th>#</th>
        <th>Raw Material</th>
        <th>Unit</th>
        <th>Opening</th>
        <th>In</th>
        <th>Out</th>
        <th>Closing</th>
      </tr>
      <?php foreach ($raw_rows as $i => $row): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td><?= htmlspecialchars($row['unit']) ?></td>
          <td><?= number_format($row['op'], 3) ?></td>
          <td><?= number_format($row['in'], 3) ?></td>
          <td><?= number_format($row['out'], 3) ?></td>
          <td><?= number_format($row['cl'], 3) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Products — Summary (<?= htmlspecialchars($start) ?> to <?= htmlspecialchars($end) ?>)</h3>
    <div class="grid-3">
      <div class="card"><strong>Opening</strong><div><?= number_format($prod_totals['opening'], 3) ?></div></div>
      <div class="card"><strong>In</strong><div><?= number_format($prod_totals['in'], 3) ?></div></div>
      <div class="card"><strong>Out</strong><div><?= number_format($prod_totals['out'], 3) ?></div></div>
    </div>
    <div class="card" style="margin-top:10px"><strong>Closing</strong><div><?= number_format($prod_totals['closing'], 3) ?></div></div>

    <table class="table" style="margin-top:12px">
      <tr>
        <th>#</th>
        <th>Product</th>
        <th>Unit</th>
        <th>Opening</th>
        <th>In</th>
        <th>Out</th>
        <th>Closing</th>
      </tr>
      <?php foreach ($prod_rows as $i => $row): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td><?= htmlspecialchars($row['unit']) ?></td>
          <td><?= number_format($row['op'], 3) ?></td>
          <td><?= number_format($row['in'], 3) ?></td>
          <td><?= number_format($row['out'], 3) ?></td>
          <td><?= number_format($row['cl'], 3) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<h3 style="margin-top:16px">Ledger (<?= htmlspecialchars($start) ?> to <?= htmlspecialchars($end) ?>)</h3>
<table class="table">
  <tr>
    <th>ID</th>
    <th>Date/Time</th>
    <th>Type</th>
    <th>Item ID</th>
    <th>Ref</th>
    <th>In</th>
    <th>Out</th>
    <th>Note</th>
  </tr>
  <?php foreach ($ledger as $l): ?>
    <tr>
      <td><?= (int)$l['id'] ?></td>
      <td><?= htmlspecialchars($l['entry_date']) ?></td>
      <td><?= htmlspecialchars($l['item_type']) ?></td>
      <td><?= (int)$l['item_id'] ?></td>
      <td><?= htmlspecialchars($l['ref_type']).' #'.(int)$l['ref_id'] ?></td>
      <td><?= number_format((float)$l['qty_in'],3) ?></td>
      <td><?= number_format((float)$l['qty_out'],3) ?></td>
      <td><?= htmlspecialchars($l['note']) ?></td>
    </tr>
  <?php endforeach; ?>
</table>