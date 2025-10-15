<?php
require_once __DIR__ . '/../../auth.php';

header('Content-Type: application/json');

function json_response($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Get request body
$input = [];
if ($method === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        $input = $_POST;
    }

    if (empty($input['sales_items'])) {
        json_response(['error' => 'Sales items are required'], 422);
    }

    $error_flag = [];
    $success_flag = [];
    //foreach sales item in sales_items
    foreach ($input['sales_items'] as $sales_item) {
        //flag errors with count but continue to next sales item do not rewturn data
        if (empty($sales_item['pos_id'])) {
            $error_flag[] = [['error' => 'Product id is required. product: ' . $sales_item['pos_id']], 422];
            continue;
        }
        if (empty($sales_item['qty'])) {
            $error_flag[] = [['error' => 'Quantity is required. product: ' . $sales_item['pos_id']], 422];
            continue;
        }
        //from products table get id where equal to sales_itme pos_id
        $product = get_one("SELECT * FROM products WHERE pos_id = " . $sales_item['pos_id']);
        if (!$product) {
            $error_flag[] = [['error' => 'Product not found. product: ' . $sales_item['pos_id']], 404];
            continue;
        }
        //from recipe table get id where equal to product id
        $recipe = get_one("SELECT * FROM recipes WHERE product_id = " . $product['id']);
        if (!$recipe) {
            //if recipe not found, skip to next sales item mark the error_flag
            $error_flag[] = [['error' => 'No recipe found for product. product: ' . $sales_item['pos_id']], 404];
            continue;
        }

        //select recipe_items where equal to recipe id
        $recipe_items = get_all("SELECT * FROM recipe_items WHERE recipe_id = " . $recipe['id']);
        if (!$recipe_items) {
            //flag error but continue to next sales item
            $error_flag[] = [['error' => 'No recipe items found for product. product: ' . $sales_item['pos_id']], 404];
            continue;
        }

        $error_flag2=0;
        $success_flag2;
        //foreach recipe_items
        foreach ($recipe_items as $recipe_item) {
            //per one yeild qty
            $used_qty = ($recipe_item['qty'] / $recipe['yield_qty']) * $sales_item['qty'];
            //update raw_materials set current_qty = current_qty - used_qty where equal to raw_material_id
            //check before deduct, ifstock is sufficient mark the error_flag
            // if stock is insufficient, add error to error_flag and skip deduction
            //else continue to deduct
            $raw_material = get_one("SELECT * FROM raw_materials WHERE id = " . $recipe_item['raw_material_id']);
            if ($raw_material['current_qty'] < $used_qty) {
                $error_flag2++;
            } else {
                q("UPDATE raw_materials SET current_qty = current_qty - " . $used_qty . " WHERE id = " . $recipe_item['raw_material_id']);
                //add to stock_ledger
                $newBalArray = get_one("SELECT current_qty FROM raw_materials WHERE id = " . $recipe_item['raw_material_id']);
                $newBal = $newBalArray['current_qty'];
                $pid = $recipe_item['raw_material_id'];
                $grn_id = $sales_item['pos_id'];
                q("INSERT INTO stock_ledger (item_type,item_id,ref_type,ref_id,entry_date,qty_in,qty_out,balance_after,note) VALUES ('product',$pid,'GRN',$grn_id,'" . now() . "',$used_qty,0,$newBal,'POS $grn_id')");
            }
        }

        //check count of recipe_items vs error_flag2 count if equal then all recipe items failed
        if (count($recipe_items) == $error_flag2) {
            $error_flag[] = [['error' => 'recipe items failed for product: ' . $sales_item['pos_id']], 422];
            continue;
        } else {
            $success_flag[] = [['ok' => true, 'message' => 'Stock updated for product: ' . $sales_item['pos_id']]];
            continue;
        }
    }

    if (!empty($error_flag)) {
        json_response(['errors' => $error_flag], 422);
    } else {
        json_response(['success' => $success_flag], 200);
    }
}