<?php
// lib/functions.php
function flash($key, $msg = null) {
if ($msg === null) {
if (!empty($_SESSION['flash'][$key])) {
$m = $_SESSION['flash'][$key];
unset($_SESSION['flash'][$key]);
return $m;
}
return null;
} else {
$_SESSION['flash'][$key] = $msg;
}
}


function get_all($sql){
$rs = q($sql);
$out = [];
while ($row = mysqli_fetch_assoc($rs)) $out[] = $row;
return $out;
}


function get_one($sql){
$rs = q($sql);
return mysqli_fetch_assoc($rs);
}


function now(){ return date('Y-m-d H:i:s'); }