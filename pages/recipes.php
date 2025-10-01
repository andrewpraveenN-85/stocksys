<?php // pages/recipes.php
// Create recipe
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['product_id'])){
$product_id=(int)($_POST['product_id']??0);
$name=esc($_POST['name']??'');
$yield=num($_POST['yield_qty']??0);
$notes=esc($_POST['notes']??'');
$rids=$_POST['raw_material_id']??[];
$qtys=$_POST['qty']??[];
if($product_id && $name && $yield>0 && !empty($rids)){
q("INSERT INTO recipes (product_id,name,yield_qty,notes) VALUES ($product_id,'$name',$yield,'$notes')");
$recipe_id=mysqli_insert_id($conn);
for($i=0;$i<count($rids);$i++){
$rid=(int)$rids[$i]; $qv=num($qtys[$i]);
if($rid && $qv>0){ q("INSERT INTO recipe_items (recipe_id,raw_material_id,qty) VALUES ($recipe_id,$rid,$qv)"); }
}
flash('ok','Recipe saved');
header('Location: index.php?page=recipes'); exit;
} else {
flash('err','Missing required fields');
}
}
$products=get_all('SELECT p.id,p.name,u.symbol FROM products p JOIN units u ON u.id=p.unit_id ORDER BY p.name');
$raws=get_all('SELECT r.id,r.name,u.symbol FROM raw_materials r JOIN units u ON u.id=r.unit_id ORDER BY r.name');
$recipes=get_all('SELECT r.*, p.name product_name FROM recipes r JOIN products p ON p.id=r.product_id ORDER BY r.id DESC');
?>
<h2>Recipes</h2>
<form method="post" class="card">
<div class="grid-3">
<label>Product
<select name="product_id" required>
<option value="">-- Select --</option>
<?php foreach($products as $p): ?>
<option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']).' ('.$p['symbol'].')' ?></option>
<?php endforeach; ?>
</select>
</label>
<label>Recipe Name <input name="name" required></label>
<label>Yield Qty (per batch) <input type="number" step="0.001" name="yield_qty" required></label>
</div>
<table class="table" id="ritems">
<tr><th>Raw Material</th><th>Qty per batch</th><th></th></tr>
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
<td><button type="button" onclick="addR()">＋</button></td>
</tr>
</table>
<label>Notes <input name="notes"></label>
<button>Save Recipe</button>
</form>


<h3>Existing Recipes</h3>
<table class="table">
<tr><th>ID</th><th>Recipe</th><th>Product</th><th>Yield</th></tr>
<?php foreach($recipes as $r): ?>
<tr>
<td><?= $r['id'] ?></td>
<td><?= htmlspecialchars($r['name']) ?></td>
<td><?= htmlspecialchars($r['product_name']) ?></td>
<td><?= $r['yield_qty'] ?></td>
</tr>
<?php endforeach; ?>
</table>
<script>
function addR(){
const t=document.getElementById('ritems');
const tr=t.rows[1].cloneNode(true);
tr.querySelector('input').value='0';
const btn=document.createElement('button'); btn.type='button'; btn.textContent='－'; btn.onclick=function(){this.closest('tr').remove();};
tr.cells[2].innerHTML=''; tr.cells[2].appendChild(btn);
t.tBodies[0].appendChild(tr);
}
</script>