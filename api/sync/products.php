<?php
require_once __DIR__ . '/../../auth.php';

header('Content-Type: application/json');

function json_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Removed authentication checks - commented out for reference
// if (!isLoggedIn()) {
//     json_response(['error' => 'Unauthorized'], 401);
// }

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$posId = isset($_GET['pos_id']) ? (int)$_GET['pos_id'] : 0;

// Get request body
$input = [];
if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        $input = $_POST;
    }
}

// Use shared helpers from config.php and lib/functions.php

switch ($method) {
    case 'POST':
        // Validate required fields
        if (empty($input['name'])) {
            json_response(['error' => 'Product name is required'], 422);
        }

        $name = trim($input['name']);
        $bodyId = isset($input['id']) ? (int)num($input['id']) : 0;
        $inputPosId = isset($input['pos_id']) ? (int)num($input['pos_id']) : null;

        $escapedName = esc($name);

        // Check if product exists by pos_id (if provided)
        if ($inputPosId !== null) {
            $existing = get_one("SELECT id FROM products WHERE pos_id = $inputPosId");
            if ($existing) {
                // Update existing product by pos_id
                q("UPDATE products SET name = '$escapedName' WHERE pos_id = $inputPosId");
                $productId = $existing['id'];
                
                $product = get_one("SELECT id, name, pos_id FROM products WHERE id = $productId");
                json_response([
                    'ok' => true, 
                    'product' => $product,
                    'action' => 'updated'
                ], 200);
            }
        }

        // If no pos_id match, proceed with normal insert/update logic
        if ($bodyId > 0) {
            // Check if product with this ID already exists
            $existing = get_one("SELECT id FROM products WHERE id = $bodyId");
            if ($existing) {
                // Update existing product
                if ($inputPosId !== null) {
                    q("UPDATE products SET name = '$escapedName', pos_id = $inputPosId WHERE id = $bodyId");
                } else {
                    q("UPDATE products SET name = '$escapedName' WHERE id = $bodyId");
                }
                $productId = $bodyId;
            } else {
                // Insert with specified ID
                if ($inputPosId !== null) {
                    q("INSERT INTO products (id, name, pos_id) VALUES ($bodyId, '$escapedName', $inputPosId)");
                } else {
                    q("INSERT INTO products (id, name) VALUES ($bodyId, '$escapedName')");
                }
                $productId = $bodyId;
            }
        } else {
            // Insert with auto-increment ID
            if ($inputPosId !== null) {
                q("INSERT INTO products (name, pos_id) VALUES ('$escapedName', $inputPosId)");
            } else {
                q("INSERT INTO products (name) VALUES ('$escapedName')");
            }
            $productId = mysqli_insert_id($conn);
        }

        // Return the created/updated product
        $product = get_one("SELECT id, name, pos_id FROM products WHERE id = $productId");
        json_response([
            'ok' => true, 
            'product' => $product,
            'action' => 'created'
        ], 201);

    case 'PUT':
        // Get ID from query string, body, or pos_id
        $targetId = 0;
        $targetPosId = $posId > 0 ? $posId : (isset($input['pos_id']) ? (int)num($input['pos_id']) : 0);
        
        // Try to find product by pos_id first
        if ($targetPosId > 0) {
            $existing = get_one("SELECT id FROM products WHERE pos_id = $targetPosId");
            if ($existing) {
                $targetId = $existing['id'];
            }
        }
        
        // If not found by pos_id, try by regular id
        if ($targetId == 0) {
            $targetId = $id > 0 ? $id : (isset($input['id']) ? (int)num($input['id']) : 0);
        }
        
        if ($targetId <= 0) {
            json_response(['error' => 'Product ID or pos_id is required'], 400);
        }

        // Check if product exists
        $existing = get_one("SELECT id FROM products WHERE id = $targetId");
        if (!$existing) {
            json_response(['error' => 'Product not found'], 404);
        }

        // Validate name
        if (empty($input['name'])) {
            json_response(['error' => 'Product name is required'], 422);
        }

        $name = trim($input['name']);
        $escapedName = esc($name);
        $newPosId = isset($input['pos_id']) ? (int)num($input['pos_id']) : null;

        // Build update query
        if ($newPosId !== null) {
            q("UPDATE products SET name = '$escapedName', pos_id = $newPosId WHERE id = $targetId");
        } else {
            q("UPDATE products SET name = '$escapedName' WHERE id = $targetId");
        }

        $updatedProduct = get_one("SELECT id, name, pos_id FROM products WHERE id = $targetId");
        json_response([
            'ok' => true, 
            'product' => $updatedProduct
        ]);

    case 'DELETE':
        // Get ID from query string, body, or pos_id
        $targetId = 0;
        $targetPosId = $posId > 0 ? $posId : (isset($input['pos_id']) ? (int)num($input['pos_id']) : 0);
        
        // Try to find product by pos_id first
        if ($targetPosId > 0) {
            $existing = get_one("SELECT id FROM products WHERE pos_id = $targetPosId");
            if ($existing) {
                $targetId = $existing['id'];
            }
        }
        
        // If not found by pos_id, try by regular id
        if ($targetId == 0) {
            $targetId = $id > 0 ? $id : (isset($input['id']) ? (int)num($input['id']) : 0);
        }
        
        if ($targetId <= 0) {
            json_response(['error' => 'Product ID or pos_id is required'], 400);
        }

        // Check if product exists
        $existing = get_one("SELECT id FROM products WHERE id = $targetId");
        if (!$existing) {
            json_response(['error' => 'Product not found'], 404);
        }

        // Check if product is being used (your existing logic)
        $usedInRecipes = get_one("SELECT COUNT(*) as count FROM recipes WHERE product_id = $targetId");
        $usedInProductions = get_one("SELECT COUNT(*) as count FROM productions pr 
                                    JOIN recipes r ON r.id = pr.recipe_id 
                                    WHERE r.product_id = $targetId");

        if ((int)$usedInRecipes['count'] > 0 || (int)$usedInProductions['count'] > 0) {
            json_response(['error' => 'Cannot delete: Product is used in recipes or productions'], 409);
        }

        q("DELETE FROM products WHERE id = $targetId");
        json_response(['ok' => true]);

    default:
        json_response(['error' => 'Method not allowed'], 405);
}
?>