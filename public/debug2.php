<?php
echo "<h2>Environment Debug v2</h2>";

echo "<h3>Basic Info</h3>";
echo "<strong>Current Directory:</strong> " . getcwd() . "<br>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>PDO MySQL Available:</strong> " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "<br>";

echo "<h3>Laravel Bootstrap Test with Proper Init</h3>";
try {
    define('LARAVEL_START', microtime(true));
    
    require __DIR__.'/../vendor/autoload.php';
    echo "✓ Autoloader loaded<br>";
    
    $app = require_once __DIR__.'/../bootstrap/app.php';
    echo "✓ App instance created<br>";
    
    // Boot the application properly
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "✓ Kernel created<br>";
    
    $request = Illuminate\Http\Request::capture();
    echo "✓ Request captured<br>";
    
    // This will properly boot all service providers
    $response = $kernel->handle($request);
    echo "✓ Application booted successfully<br>";
    
    echo "<h3>Configuration Values</h3>";
    echo "<strong>App Environment:</strong> " . app()->environment() . "<br>";
    echo "<strong>DB Connection:</strong> " . config('database.default') . "<br>";
    echo "<strong>DB Host:</strong> " . config('database.connections.mysql.host') . "<br>";
    echo "<strong>DB Database:</strong> " . config('database.connections.mysql.database') . "<br>";
    
    echo "<h3>Database Connection Test</h3>";
    try {
        DB::connection()->getPdo();
        echo "✓ <span style='color:green'>Database connection successful!</span><br>";
    } catch (Exception $e) {
        echo "✗ <span style='color:red'>Database connection failed: " . $e->getMessage() . "</span><br>";
    }
    
} catch (Exception $e) {
    echo "<span style='color:red'>✗ Error: " . $e->getMessage() . "</span><br>";
    echo "<pre style='background:#f5f5f5;padding:10px;'>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "<span style='color:red'>✗ Fatal Error: " . $e->getMessage() . "</span><br>";
    echo "<pre style='background:#f5f5f5;padding:10px;'>" . $e->getTraceAsString() . "</pre>";
}
?>