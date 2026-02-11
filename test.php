<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Millet Marketplace - Connection Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }
        .test-item {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .test-item h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .success {
            border-left-color: #28a745;
        }
        .success h3 {
            color: #28a745;
        }
        .error {
            border-left-color: #dc3545;
        }
        .error h3 {
            color: #dc3545;
        }
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
        }
        .info {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }
        .code {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin-top: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌾 Millet Marketplace - System Test</h1>
        
        <?php
        require_once __DIR__ . '/vendor/autoload.php';
        use App\Config\Database;
        
        // Test 1: Composer Autoload
        echo '<div class="test-item success">';
        echo '<span class="status success">✓ PASSED</span>';
        echo '<h3>1. Composer Autoload</h3>';
        echo '<p class="info">Composer dependencies loaded successfully.</p>';
        echo '</div>';
        
        // Test 2: Database Connection
        try {
            $db = Database::getConnection();
            echo '<div class="test-item success">';
            echo '<span class="status success">✓ PASSED</span>';
            echo '<h3>2. Database Connection</h3>';
            echo '<p class="info">Successfully connected to MySQL database.</p>';
            
            // Get database info
            $stmt = $db->query("SELECT DATABASE() as db_name");
            $result = $stmt->fetch();
            echo '<p class="info"><strong>Database:</strong> ' . htmlspecialchars($result['db_name']) . '</p>';
            
            // Count tables
            $stmt = $db->query("SHOW TABLES");
            $tables = $stmt->fetchAll();
            echo '<p class="info"><strong>Tables:</strong> ' . count($tables) . ' tables found</p>';
            
            if (count($tables) > 0) {
                echo '<div class="code">';
                foreach ($tables as $table) {
                    echo '• ' . htmlspecialchars(array_values($table)[0]) . '<br>';
                }
                echo '</div>';
            }
            
            echo '</div>';
        } catch (Exception $e) {
            echo '<div class="test-item error">';
            echo '<span class="status error">✗ FAILED</span>';
            echo '<h3>2. Database Connection</h3>';
            echo '<p class="info">Failed to connect to database.</p>';
            echo '<div class="code">' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<p class="info" style="margin-top: 10px;"><strong>Fix:</strong> Check your .env file and ensure MySQL is running in XAMPP.</p>';
            echo '</div>';
        }
        
        // Test 3: Environment Variables
        echo '<div class="test-item">';
        $envVars = [
            'DB_HOST' => Database::getEnv('DB_HOST'),
            'DB_NAME' => Database::getEnv('DB_NAME'),
            'DB_USER' => Database::getEnv('DB_USER'),
            'SMTP_HOST' => Database::getEnv('SMTP_HOST'),
            'JWT_SECRET' => Database::getEnv('JWT_SECRET') ? '***configured***' : 'NOT SET'
        ];
        
        $allConfigured = true;
        foreach ($envVars as $key => $value) {
            if (empty($value)) {
                $allConfigured = false;
                break;
            }
        }
        
        if ($allConfigured) {
            echo '<span class="status success">✓ PASSED</span>';
            echo '<h3>3. Environment Configuration</h3>';
            echo '<p class="info">All required environment variables are configured.</p>';
        } else {
            echo '<span class="status error">⚠ WARNING</span>';
            echo '<h3>3. Environment Configuration</h3>';
            echo '<p class="info">Some environment variables may not be configured.</p>';
        }
        
        echo '<div class="code">';
        foreach ($envVars as $key => $value) {
            echo htmlspecialchars($key) . ' = ' . htmlspecialchars($value ?: 'NOT SET') . '<br>';
        }
        echo '</div>';
        echo '</div>';
        
        // Test 4: PHP Version
        echo '<div class="test-item">';
        $phpVersion = phpversion();
        $isPhp8 = version_compare($phpVersion, '8.0.0', '>=');
        
        if ($isPhp8) {
            echo '<span class="status success">✓ PASSED</span>';
            echo '<h3>4. PHP Version</h3>';
            echo '<p class="info">PHP version is compatible (PHP ' . htmlspecialchars($phpVersion) . ').</p>';
        } else {
            echo '<span class="status error">✗ FAILED</span>';
            echo '<h3>4. PHP Version</h3>';
            echo '<p class="info">PHP 8.0+ is required. Current version: ' . htmlspecialchars($phpVersion) . '</p>';
        }
        echo '</div>';
        
        // Test 5: Required Extensions
        echo '<div class="test-item">';
        $requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'curl'];
        $missingExtensions = [];
        
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $missingExtensions[] = $ext;
            }
        }
        
        if (empty($missingExtensions)) {
            echo '<span class="status success">✓ PASSED</span>';
            echo '<h3>5. PHP Extensions</h3>';
            echo '<p class="info">All required PHP extensions are loaded.</p>';
            echo '<div class="code">';
            foreach ($requiredExtensions as $ext) {
                echo '✓ ' . htmlspecialchars($ext) . '<br>';
            }
            echo '</div>';
        } else {
            echo '<span class="status error">✗ FAILED</span>';
            echo '<h3>5. PHP Extensions</h3>';
            echo '<p class="info">Missing extensions: ' . implode(', ', $missingExtensions) . '</p>';
        }
        echo '</div>';
        ?>
        
        <div class="footer">
            <p>✅ All tests passed? Start testing the API!</p>
            <p><a href="api/">Test API Endpoint</a> | <a href="QUICKSTART.md">View Quick Start Guide</a></p>
        </div>
    </div>
</body>
</html>
