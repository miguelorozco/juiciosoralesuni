<?php
echo "<h2>Environment Debug</h2>";
echo "<strong>Current Directory:</strong> " . getcwd() . "<br>";
echo "<strong>Script Filename:</strong> " . __FILE__ . "<br>";
echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>PHP ini:</strong> " . php_ini_loaded_file() . "<br>";

echo "<h3>PDO Status</h3>";
echo "<strong>PDO Available:</strong> " . (extension_loaded('pdo') ? 'Yes' : 'No') . "<br>";
echo "<strong>PDO MySQL Available:</strong> " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "<br>";
if (extension_loaded('pdo')) {
    echo "<strong>PDO Drivers:</strong> " . implode(', ', PDO::getAvailableDrivers()) . "<br>";
}

echo "<h3>.env File Status</h3>";
$envPath = dirname(__DIR__) . '/.env';
echo "<strong>.env Path:</strong> " . $envPath . "<br>";
echo "<strong>.env Exists:</strong> " . (file_exists($envPath) ? 'Yes' : 'No') . "<br>";
echo "<strong>.env Readable:</strong> " . (is_readable($envPath) ? 'Yes' : 'No') . "<br>";

echo "<h3>Laravel Bootstrap Test</h3>";
try {
    require __DIR__.'/../vendor/autoload.php';
    echo "✓ Autoloader loaded<br>";
    
    $app = require_once __DIR__.'/../bootstrap/app.php';
    echo "✓ App bootstrapped<br>";
    
    echo "<strong>App Environment:</strong> " . $app->environment() . "<br>";
    echo "<strong>DB Connection:</strong> " . config('database.default') . "<br>";
    
} catch (Exception $e) {
    echo "<span style='color:red'>✗ Error: " . $e->getMessage() . "</span><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>