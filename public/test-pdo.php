<?php
echo "PHP Version: " . phpversion() . "<br>";
echo "PDO Available: " . (extension_loaded('pdo') ? 'Yes' : 'No') . "<br>";
echo "PDO MySQL Available: " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "<br>";
echo "<br>Loaded Extensions:<br>";
foreach (get_loaded_extensions() as $ext) {
    if (strpos(strtolower($ext), 'pdo') !== false || strpos(strtolower($ext), 'mysql') !== false) {
        echo "- $ext<br>";
    }
}
echo "<br>PDO Drivers:<br>";
if (extension_loaded('pdo')) {
    foreach (PDO::getAvailableDrivers() as $driver) {
        echo "- $driver<br>";
    }
}
?>