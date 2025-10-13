<?php
// setup_database.php - Automatic database setup
require_once 'config.php';

echo "<h2>Setting up User Authentication Database</h2>";

// SQL commands to execute
$sql_commands = [
    "CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL UNIQUE,
        `password` varchar(255) NOT NULL,
        `role` enum('admin','manager') NOT NULL DEFAULT 'manager',
        `full_name` varchar(100) NOT NULL,
        `email` varchar(100) DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `is_active` tinyint(1) DEFAULT 1,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "INSERT IGNORE INTO `users` (`username`, `password`, `role`, `full_name`, `email`) VALUES
    ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', 'admin@restaurant.com'),
    ('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'Restaurant Manager', 'manager@restaurant.com')"
];

$success_count = 0;
$error_count = 0;

foreach ($sql_commands as $i => $sql) {
    echo "<p>Executing command " . ($i + 1) . "...</p>";
    
    try {
        $result = q($sql);
        echo "<p style='color: green;'>✅ Command " . ($i + 1) . " executed successfully</p>";
        $success_count++;
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Command " . ($i + 1) . " failed: " . $e->getMessage() . "</p>";
        $error_count++;
    }
}

echo "<hr>";
echo "<h3>Setup Summary:</h3>";
echo "<p>✅ Successful commands: $success_count</p>";
echo "<p>❌ Failed commands: $error_count</p>";

if ($success_count > 0) {
    echo "<h3>🎉 Database setup completed!</h3>";
    echo "<h4>Default Login Credentials:</h4>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> username=admin, password=admin123</li>";
    echo "<li><strong>Manager:</strong> username=manager, password=admin123</li>";
    echo "</ul>";
    
    echo "<p><a href='login.php' style='background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
} else {
    echo "<p style='color: red;'>❌ Setup failed. Please check your database connection.</p>";
}

// Test if users table exists
$test_result = get_one("SELECT COUNT(*) as count FROM users");
if ($test_result) {
    echo "<p style='color: green;'>✅ Users table verified. Found " . $test_result['count'] . " users.</p>";
}
?>

