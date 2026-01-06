# FASE 0.12: Herramientas de Análisis - Sistema de Diálogos

## 📋 Índice

1. [Scripts de Análisis Automatizado](#scripts-de-análisis-automatizado)
2. [Base de Conocimiento](#base-de-conocimiento)
3. [Decisiones de Diseño Documentadas](#decisiones-de-diseño-documentadas)
4. [Referencias y Recursos](#referencias-y-recursos)

---

## Scripts de Análisis Automatizado

### Script 1: Mapear Estructura de Clases

#### Objetivo
Analizar y mapear la estructura de clases del sistema, incluyendo modelos, controladores, y relaciones.

#### Implementación (PHP - Laravel)

```php
<?php
// database/scripts/mapear-estructura-clases.php

require __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Support\Facades\File;
use ReflectionClass;

class ClassMapper
{
    private $models = [];
    private $controllers = [];
    private $services = [];
    private $output = [];

    public function analyze()
    {
        $this->analyzeModels();
        $this->analyzeControllers();
        $this->analyzeServices();
        $this->generateReport();
    }

    private function analyzeModels()
    {
        $modelPath = app_path('Models');
        $files = File::allFiles($modelPath);

        foreach ($files as $file) {
            $className = 'App\\Models\\' . $file->getFilenameWithoutExtension();
            
            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);
                
                if ($reflection->isSubclassOf('Illuminate\\Database\\Eloquent\\Model')) {
                    $this->models[] = [
                        'name' => $reflection->getName(),
                        'table' => $this->getTableName($reflection),
                        'fillable' => $this->getFillable($reflection),
                        'relationships' => $this->getRelationships($reflection),
                        'scopes' => $this->getScopes($reflection),
                        'methods' => $this->getMethods($reflection),
                    ];
                }
            }
        }
    }

    private function analyzeControllers()
    {
        $controllerPath = app_path('Http/Controllers');
        $files = File::allFiles($controllerPath);

        foreach ($files as $file) {
            $className = 'App\\Http\\Controllers\\' . $file->getFilenameWithoutExtension();
            
            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);
                
                $this->controllers[] = [
                    'name' => $reflection->getName(),
                    'methods' => $this->getPublicMethods($reflection),
                    'dependencies' => $this->getDependencies($reflection),
                ];
            }
        }
    }

    private function analyzeServices()
    {
        $servicePath = app_path('Services');
        
        if (File::exists($servicePath)) {
            $files = File::allFiles($servicePath);

            foreach ($files as $file) {
                $className = 'App\\Services\\' . $file->getFilenameWithoutExtension();
                
                if (class_exists($className)) {
                    $reflection = new ReflectionClass($className);
                    
                    $this->services[] = [
                        'name' => $reflection->getName(),
                        'methods' => $this->getPublicMethods($reflection),
                    ];
                }
            }
        }
    }

    private function getTableName(ReflectionClass $reflection)
    {
        if ($reflection->hasProperty('table')) {
            $property = $reflection->getProperty('table');
            $property->setAccessible(true);
            return $property->getValue($reflection->newInstanceWithoutConstructor());
        }
        return null;
    }

    private function getFillable(ReflectionClass $reflection)
    {
        if ($reflection->hasProperty('fillable')) {
            $property = $reflection->getProperty('fillable');
            $property->setAccessible(true);
            return $property->getValue($reflection->newInstanceWithoutConstructor());
        }
        return [];
    }

    private function getRelationships(ReflectionClass $reflection)
    {
        $relationships = [];
        $methods = $reflection->getMethods();

        foreach ($methods as $method) {
            if ($method->isPublic() && !$method->isStatic()) {
                $docComment = $method->getDocComment();
                if ($docComment && (
                    strpos($docComment, '@return') !== false && (
                        strpos($docComment, 'belongsTo') !== false ||
                        strpos($docComment, 'hasMany') !== false ||
                        strpos($docComment, 'hasOne') !== false ||
                        strpos($docComment, 'belongsToMany') !== false
                    )
                )) {
                    $relationships[] = $method->getName();
                }
            }
        }

        return $relationships;
    }

    private function getScopes(ReflectionClass $reflection)
    {
        $scopes = [];
        $methods = $reflection->getMethods();

        foreach ($methods as $method) {
            if ($method->isPublic() && strpos($method->getName(), 'scope') === 0) {
                $scopes[] = lcfirst(substr($method->getName(), 5)); // Remove 'scope' prefix
            }
        }

        return $scopes;
    }

    private function getMethods(ReflectionClass $reflection)
    {
        $methods = [];
        foreach ($reflection->getMethods() as $method) {
            if ($method->isPublic() && !$method->isStatic()) {
                $methods[] = $method->getName();
            }
        }
        return $methods;
    }

    private function getPublicMethods(ReflectionClass $reflection)
    {
        $methods = [];
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$method->isStatic() && $method->getDeclaringClass()->getName() === $reflection->getName()) {
                $methods[] = $method->getName();
            }
        }
        return $methods;
    }

    private function getDependencies(ReflectionClass $reflection)
    {
        $dependencies = [];
        $constructor = $reflection->getConstructor();

        if ($constructor) {
            $parameters = $constructor->getParameters();
            foreach ($parameters as $parameter) {
                $type = $parameter->getType();
                if ($type && !$type->isBuiltin()) {
                    $dependencies[] = $type->getName();
                }
            }
        }

        return $dependencies;
    }

    private function generateReport()
    {
        $output = [
            'generated_at' => now()->toDateTimeString(),
            'models' => $this->models,
            'controllers' => $this->controllers,
            'services' => $this->services,
            'statistics' => [
                'total_models' => count($this->models),
                'total_controllers' => count($this->controllers),
                'total_services' => count($this->services),
            ],
        ];

        $outputPath = storage_path('app/analysis/class-structure.json');
        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, json_encode($output, JSON_PRETTY_PRINT));

        echo "Reporte generado en: {$outputPath}\n";
        echo "Total modelos: " . count($this->models) . "\n";
        echo "Total controladores: " . count($this->controllers) . "\n";
        echo "Total servicios: " . count($this->services) . "\n";
    }
}

// Ejecutar
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$mapper = new ClassMapper();
$mapper->analyze();
```

#### Uso

```bash
php database/scripts/mapear-estructura-clases.php
```

#### Salida

Genera un archivo JSON en `storage/app/analysis/class-structure.json` con:
- Lista de modelos con sus propiedades, relaciones y scopes
- Lista de controladores con sus métodos y dependencias
- Lista de servicios con sus métodos
- Estadísticas generales

---

### Script 2: Extraer Dependencias

#### Objetivo
Analizar las dependencias entre clases, modelos, controladores y servicios.

#### Implementación (PHP - Laravel)

```php
<?php
// database/scripts/extraer-dependencias.php

require __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;

class DependencyExtractor
{
    private $dependencies = [];
    private $graph = [];

    public function analyze()
    {
        $this->analyzeModels();
        $this->analyzeControllers();
        $this->analyzeServices();
        $this->buildGraph();
        $this->generateReport();
    }

    private function analyzeModels()
    {
        $modelPath = app_path('Models');
        $files = File::allFiles($modelPath);

        foreach ($files as $file) {
            $className = 'App\\Models\\' . $file->getFilenameWithoutExtension();
            
            if (class_exists($className)) {
                $this->extractDependencies($className);
            }
        }
    }

    private function analyzeControllers()
    {
        $controllerPath = app_path('Http/Controllers');
        $files = File::allFiles($controllerPath);

        foreach ($files as $file) {
            $className = 'App\\Http\\Controllers\\' . $file->getFilenameWithoutExtension();
            
            if (class_exists($className)) {
                $this->extractDependencies($className);
            }
        }
    }

    private function analyzeServices()
    {
        $servicePath = app_path('Services');
        
        if (File::exists($servicePath)) {
            $files = File::allFiles($servicePath);

            foreach ($files as $file) {
                $className = 'App\\Services\\' . $file->getFilenameWithoutExtension();
                
                if (class_exists($className)) {
                    $this->extractDependencies($className);
                }
            }
        }
    }

    private function extractDependencies($className)
    {
        $reflection = new ReflectionClass($className);
        $deps = [];

        // Dependencias del constructor
        $constructor = $reflection->getConstructor();
        if ($constructor) {
            foreach ($constructor->getParameters() as $param) {
                $type = $param->getType();
                if ($type && !$type->isBuiltin()) {
                    $deps[] = $type->getName();
                }
            }
        }

        // Dependencias de métodos
        foreach ($reflection->getMethods() as $method) {
            foreach ($method->getParameters() as $param) {
                $type = $param->getType();
                if ($type && !$type->isBuiltin()) {
                    $deps[] = $type->getName();
                }
            }
        }

        // Dependencias de propiedades
        foreach ($reflection->getProperties() as $property) {
            $docComment = $property->getDocComment();
            if ($docComment) {
                preg_match('/@var\s+([^\s]+)/', $docComment, $matches);
                if (isset($matches[1])) {
                    $type = trim($matches[1], '\\');
                    if (class_exists($type) || interface_exists($type)) {
                        $deps[] = $type;
                    }
                }
            }
        }

        $this->dependencies[$className] = array_unique($deps);
    }

    private function buildGraph()
    {
        foreach ($this->dependencies as $class => $deps) {
            $this->graph[$class] = [];
            foreach ($deps as $dep) {
                if (strpos($dep, 'App\\') === 0) {
                    $this->graph[$class][] = $dep;
                }
            }
        }
    }

    private function generateReport()
    {
        $output = [
            'generated_at' => now()->toDateTimeString(),
            'dependencies' => $this->dependencies,
            'graph' => $this->graph,
            'statistics' => [
                'total_classes' => count($this->dependencies),
                'total_dependencies' => array_sum(array_map('count', $this->dependencies)),
            ],
        ];

        $outputPath = storage_path('app/analysis/dependencies.json');
        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, json_encode($output, JSON_PRETTY_PRINT));

        // Generar también formato DOT para Graphviz
        $this->generateDotFile();

        echo "Reporte generado en: {$outputPath}\n";
    }

    private function generateDotFile()
    {
        $dot = "digraph Dependencies {\n";
        $dot .= "  rankdir=LR;\n";
        $dot .= "  node [shape=box];\n\n";

        foreach ($this->graph as $class => $deps) {
            $shortName = $this->getShortName($class);
            foreach ($deps as $dep) {
                $depShortName = $this->getShortName($dep);
                $dot .= "  \"{$shortName}\" -> \"{$depShortName}\";\n";
            }
        }

        $dot .= "}\n";

        $outputPath = storage_path('app/analysis/dependencies.dot');
        File::put($outputPath, $dot);

        echo "Archivo DOT generado en: {$outputPath}\n";
        echo "Para generar imagen: dot -Tpng {$outputPath} -o dependencies.png\n";
    }

    private function getShortName($fullName)
    {
        $parts = explode('\\', $fullName);
        return end($parts);
    }
}

// Ejecutar
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$extractor = new DependencyExtractor();
$extractor->analyze();
```

#### Uso

```bash
php database/scripts/extraer-dependencias.php
```

#### Salida

Genera:
- `storage/app/analysis/dependencies.json`: JSON con todas las dependencias
- `storage/app/analysis/dependencies.dot`: Archivo DOT para Graphviz

---

### Script 3: Analizar Uso de Memoria

#### Objetivo
Analizar el uso de memoria de los modelos y optimizar queries.

#### Implementación (PHP - Laravel)

```php
<?php
// database/scripts/analizar-uso-memoria.php

require __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MemoryAnalyzer
{
    private $results = [];

    public function analyze()
    {
        $this->analyzeModels();
        $this->analyzeQueries();
        $this->generateReport();
    }

    private function analyzeModels()
    {
        $models = [
            'App\\Models\\DialogoV2',
            'App\\Models\\NodoDialogoV2',
            'App\\Models\\RespuestaDialogoV2',
            'App\\Models\\SesionDialogoV2',
            'App\\Models\\DecisionDialogoV2',
        ];

        foreach ($models as $modelClass) {
            if (class_exists($modelClass)) {
                $memoryBefore = memory_get_usage();
                
                // Cargar 100 registros
                $records = $modelClass::take(100)->get();
                
                $memoryAfter = memory_get_usage();
                $memoryUsed = $memoryAfter - $memoryBefore;
                
                $this->results['models'][] = [
                    'model' => $modelClass,
                    'records_loaded' => $records->count(),
                    'memory_used_bytes' => $memoryUsed,
                    'memory_used_kb' => round($memoryUsed / 1024, 2),
                    'memory_per_record_bytes' => $records->count() > 0 ? round($memoryUsed / $records->count(), 2) : 0,
                ];
            }
        }
    }

    private function analyzeQueries()
    {
        DB::enableQueryLog();

        // Test queries comunes
        $tests = [
            'load_dialogo_with_nodos' => function() {
                return \App\Models\DialogoV2::with('nodos')->first();
            },
            'load_dialogo_with_all_relations' => function() {
                return \App\Models\DialogoV2::with(['nodos', 'nodos.respuestas', 'sesiones'])->first();
            },
            'load_sesion_with_decisions' => function() {
                return \App\Models\SesionDialogoV2::with('decisiones')->first();
            },
        ];

        foreach ($tests as $testName => $test) {
            $memoryBefore = memory_get_usage();
            $timeBefore = microtime(true);
            
            $result = $test();
            
            $timeAfter = microtime(true);
            $memoryAfter = memory_get_usage();
            
            $queries = DB::getQueryLog();
            DB::flushQueryLog();
            
            $this->results['queries'][] = [
                'test' => $testName,
                'execution_time_ms' => round(($timeAfter - $timeBefore) * 1000, 2),
                'memory_used_bytes' => $memoryAfter - $memoryBefore,
                'memory_used_kb' => round(($memoryAfter - $memoryBefore) / 1024, 2),
                'queries_count' => count($queries),
                'queries' => array_map(function($q) {
                    return [
                        'query' => $q['query'],
                        'time_ms' => round($q['time'], 2),
                    ];
                }, $queries),
            ];
        }
    }

    private function generateReport()
    {
        $output = [
            'generated_at' => now()->toDateTimeString(),
            'results' => $this->results,
            'recommendations' => $this->generateRecommendations(),
        ];

        $outputPath = storage_path('app/analysis/memory-usage.json');
        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, json_encode($output, JSON_PRETTY_PRINT));

        echo "Reporte generado en: {$outputPath}\n";
        $this->printSummary();
    }

    private function generateRecommendations()
    {
        $recommendations = [];

        foreach ($this->results['models'] ?? [] as $model) {
            if ($model['memory_per_record_bytes'] > 1024) {
                $recommendations[] = "Modelo {$model['model']} usa más de 1KB por registro. Considerar optimización.";
            }
        }

        foreach ($this->results['queries'] ?? [] as $query) {
            if ($query['queries_count'] > 5) {
                $recommendations[] = "Query '{$query['test']}' ejecuta {$query['queries_count']} queries. Considerar eager loading.";
            }
            if ($query['execution_time_ms'] > 1000) {
                $recommendations[] = "Query '{$query['test']}' tarda más de 1 segundo. Considerar optimización.";
            }
        }

        return $recommendations;
    }

    private function printSummary()
    {
        echo "\n=== RESUMEN ===\n\n";
        
        echo "Modelos analizados:\n";
        foreach ($this->results['models'] ?? [] as $model) {
            echo "  - {$model['model']}: {$model['memory_per_record_bytes']} bytes/registro\n";
        }
        
        echo "\nQueries analizadas:\n";
        foreach ($this->results['queries'] ?? [] as $query) {
            echo "  - {$query['test']}: {$query['execution_time_ms']}ms, {$query['queries_count']} queries\n";
        }
    }
}

// Ejecutar
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$analyzer = new MemoryAnalyzer();
$analyzer->analyze();
```

#### Uso

```bash
php database/scripts/analizar-uso-memoria.php
```

---

### Script 4: Generar Documentación Automática

#### Objetivo
Generar documentación automática desde comentarios PHPDoc y estructura de código.

#### Implementación (PHP - Laravel)

```php
<?php
// database/scripts/generar-documentacion.php

require __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class DocumentationGenerator
{
    private $documentation = [];

    public function generate()
    {
        $this->documentModels();
        $this->documentControllers();
        $this->generateMarkdown();
    }

    private function documentModels()
    {
        $modelPath = app_path('Models');
        $files = File::allFiles($modelPath);

        foreach ($files as $file) {
            $className = 'App\\Models\\' . $file->getFilenameWithoutExtension();
            
            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);
                
                if ($reflection->isSubclassOf('Illuminate\\Database\\Eloquent\\Model')) {
                    $this->documentation['models'][] = $this->documentClass($reflection);
                }
            }
        }
    }

    private function documentControllers()
    {
        $controllerPath = app_path('Http/Controllers');
        $files = File::allFiles($controllerPath);

        foreach ($files as $file) {
            $className = 'App\\Http\\Controllers\\' . $file->getFilenameWithoutExtension();
            
            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);
                $this->documentation['controllers'][] = $this->documentClass($reflection);
            }
        }
    }

    private function documentClass(ReflectionClass $reflection)
    {
        $doc = [
            'name' => $reflection->getName(),
            'short_name' => $reflection->getShortName(),
            'namespace' => $reflection->getNamespaceName(),
            'description' => $this->extractDescription($reflection->getDocComment()),
            'properties' => [],
            'methods' => [],
        ];

        // Documentar propiedades
        foreach ($reflection->getProperties() as $property) {
            $doc['properties'][] = [
                'name' => $property->getName(),
                'visibility' => $this->getVisibility($property),
                'description' => $this->extractDescription($property->getDocComment()),
            ];
        }

        // Documentar métodos
        foreach ($reflection->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() === $reflection->getName()) {
                $doc['methods'][] = [
                    'name' => $method->getName(),
                    'visibility' => $this->getVisibility($method),
                    'description' => $this->extractDescription($method->getDocComment()),
                    'parameters' => $this->documentParameters($method),
                    'return_type' => $this->getReturnType($method),
                ];
            }
        }

        return $doc;
    }

    private function extractDescription($docComment)
    {
        if (!$docComment) return '';
        
        $lines = explode("\n", $docComment);
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, '/**') === false && strpos($line, '*/') === false && strpos($line, '*') === 0) {
                $description = trim(substr($line, 1));
                if (!empty($description) && strpos($description, '@') === false) {
                    return $description;
                }
            }
        }
        return '';
    }

    private function getVisibility($reflection)
    {
        if ($reflection->isPublic()) return 'public';
        if ($reflection->isProtected()) return 'protected';
        if ($reflection->isPrivate()) return 'private';
        return '';
    }

    private function documentParameters(ReflectionMethod $method)
    {
        $params = [];
        foreach ($method->getParameters() as $param) {
            $params[] = [
                'name' => $param->getName(),
                'type' => $param->getType() ? $param->getType()->getName() : 'mixed',
                'required' => !$param->isOptional(),
                'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
            ];
        }
        return $params;
    }

    private function getReturnType(ReflectionMethod $method)
    {
        $returnType = $method->getReturnType();
        if ($returnType) {
            return $returnType->getName();
        }
        
        // Intentar extraer de PHPDoc
        $docComment = $method->getDocComment();
        if ($docComment && preg_match('/@return\s+([^\s]+)/', $docComment, $matches)) {
            return trim($matches[1], '\\');
        }
        
        return 'mixed';
    }

    private function generateMarkdown()
    {
        $markdown = "# Documentación Automática del Sistema\n\n";
        $markdown .= "Generado el: " . now()->toDateTimeString() . "\n\n";

        // Documentar modelos
        $markdown .= "## Modelos\n\n";
        foreach ($this->documentation['models'] ?? [] as $model) {
            $markdown .= $this->generateClassMarkdown($model);
        }

        // Documentar controladores
        $markdown .= "## Controladores\n\n";
        foreach ($this->documentation['controllers'] ?? [] as $controller) {
            $markdown .= $this->generateClassMarkdown($controller);
        }

        $outputPath = storage_path('app/analysis/auto-documentation.md');
        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, $markdown);

        echo "Documentación generada en: {$outputPath}\n";
    }

    private function generateClassMarkdown($class)
    {
        $md = "### {$class['short_name']}\n\n";
        
        if (!empty($class['description'])) {
            $md .= "{$class['description']}\n\n";
        }

        $md .= "**Namespace:** `{$class['namespace']}`\n\n";

        if (!empty($class['properties'])) {
            $md .= "#### Propiedades\n\n";
            foreach ($class['properties'] as $prop) {
                $md .= "- `{$prop['visibility']} {$prop['name']}`";
                if (!empty($prop['description'])) {
                    $md .= ": {$prop['description']}";
                }
                $md .= "\n";
            }
            $md .= "\n";
        }

        if (!empty($class['methods'])) {
            $md .= "#### Métodos\n\n";
            foreach ($class['methods'] as $method) {
                $md .= "##### `{$method['name']}()`\n\n";
                if (!empty($method['description'])) {
                    $md .= "{$method['description']}\n\n";
                }
                
                if (!empty($method['parameters'])) {
                    $md .= "**Parámetros:**\n";
                    foreach ($method['parameters'] as $param) {
                        $md .= "- `{$param['type']} \${$param['name']}`";
                        if (!$param['required']) {
                            $md .= " (opcional)";
                        }
                        $md .= "\n";
                    }
                    $md .= "\n";
                }
                
                if ($method['return_type'] !== 'mixed') {
                    $md .= "**Retorna:** `{$method['return_type']}`\n\n";
                }
            }
        }

        return $md;
    }
}

// Ejecutar
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$generator = new DocumentationGenerator();
$generator->generate();
```

#### Uso

```bash
php database/scripts/generar-documentacion.php
```

---

## Base de Conocimiento

### Notas de Análisis por Componente

#### 1. Sistema de Diálogos (Backend)

**Ubicación**: `app/Models/DialogoV2.php`

**Notas**:
- Modelo principal para almacenar diálogos
- Soporta versionado mediante campo `version`
- Estados: `borrador`, `activo`, `archivado`
- Campo `metadata_unity` para datos específicos de Unity
- Método `exportarParaUnity()` para exportar a formato JSON

**Decisiones de Diseño**:
- Usar `SoftDeletes` para no perder datos al eliminar
- Campo `publico` para controlar visibilidad
- Campo `configuracion` (JSON) para flexibilidad

#### 2. Sistema de Nodos

**Ubicación**: `app/Models/NodoDialogoV2.php`

**Notas**:
- Representa un nodo en el grafo de diálogo
- Tipos: `npc`, `pc`, `agrupacion`
- Posiciones directas (`posicion_x`, `posicion_y`) para editor visual
- Campo `conversant_id` para alineación con Pixel Crushers
- Campo `menu_text` para texto en menú de respuestas

**Decisiones de Diseño**:
- Posiciones directas en lugar de JSON para mejor performance
- Campo `es_inicial` para identificar nodo de inicio
- Campo `condiciones` (JSON) para evaluación de condiciones

#### 3. Sistema de Respuestas

**Ubicación**: `app/Models/RespuestaDialogoV2.php`

**Notas**:
- Representa una opción/respuesta del usuario
- Conecta nodo origen con nodo destino
- Campo `orden` para ordenar respuestas
- Campo `condiciones` (JSON) para mostrar/ocultar respuestas
- Soporte para usuarios no registrados

**Decisiones de Diseño**:
- Campo `requiere_usuario_registrado` para controlar acceso
- Campo `requiere_rol` para restricciones por rol
- Campo `consecuencias` (JSON) para ejecutar acciones

#### 4. Sistema de Sesiones

**Ubicación**: `app/Models/SesionDialogoV2.php`

**Notas**:
- Representa una sesión activa de diálogo
- Vinculada a `SesionJuicio` para contexto
- Campo `variables` (JSON) para estado de sesión
- Campo `historial_nodos` (JSON) para tracking
- Soporte para audio completo de sesión

**Decisiones de Diseño**:
- Variables en JSON para flexibilidad
- Historial como array de IDs para eficiencia
- Estados: `iniciado`, `en_curso`, `pausado`, `finalizado`

#### 5. Sistema de Decisiones

**Ubicación**: `app/Models/DecisionDialogoV2.php`

**Notas**:
- Registra cada decisión tomada por usuario
- Vinculada a sesión y usuario
- Campos de evaluación para profesor
- Soporte para audio MP3 de decisión
- Tracking de usuarios no registrados

**Decisiones de Diseño**:
- Campo `usuario_id` nullable para usuarios no registrados
- Campo `usuario_no_registrado_id` para tracking
- Estados de evaluación: `pendiente`, `evaluado`, `revisado`

---

### Decisiones de Diseño Documentadas

#### Decisión 1: Arquitectura Cliente-Servidor

**Fecha**: 2026-01-05

**Decisión**: Usar arquitectura cliente-servidor en lugar de sistema local como Pixel Crushers.

**Razón**:
- Necesidad de multi-usuario
- Persistencia centralizada
- Evaluación por profesores
- Historial completo

**Alternativas Consideradas**:
- Sistema local como Pixel Crushers (rechazado: no soporta multi-usuario)
- Sistema híbrido (rechazado: complejidad innecesaria)

**Impacto**:
- Requiere API REST
- Requiere sincronización
- Mayor latencia pero mejor escalabilidad

---

#### Decisión 2: JSON en lugar de Lua

**Fecha**: 2026-01-05

**Decisión**: Usar JSON para condiciones y variables en lugar de Lua.

**Razón**:
- Más fácil de validar
- Más fácil de depurar
- No requiere motor Lua
- Compatible con APIs REST

**Alternativas Consideradas**:
- Lua como Pixel Crushers (rechazado: complejidad)
- SQL directo (rechazado: falta flexibilidad)

**Impacto**:
- Menos potente que Lua pero más simple
- Requiere parser JSON en Unity

---

#### Decisión 3: Posiciones Directas

**Fecha**: 2026-01-05

**Decisión**: Usar campos `posicion_x` y `posicion_y` directos en lugar de JSON.

**Razón**:
- Mejor performance en queries
- Más fácil de indexar
- Más fácil de ordenar

**Alternativas Consideradas**:
- JSON `posicion` (rechazado: peor performance)
- Campo calculado (rechazado: complejidad)

**Impacto**:
- Más campos en tabla pero mejor rendimiento

---

#### Decisión 4: Sistema de Evaluación Integrado

**Fecha**: 2026-01-05

**Decisión**: Integrar sistema de evaluación directamente en `decisiones_dialogo_v2`.

**Razón**:
- Requisito del sistema educativo
- Facilita evaluación por profesores
- Historial completo de evaluaciones

**Alternativas Consideradas**:
- Tabla separada (rechazado: complejidad innecesaria)
- Sistema externo (rechazado: falta integración)

**Impacto**:
- Campos adicionales en tabla
- Funcionalidad única vs Pixel Crushers

---

#### Decisión 5: Audio MP3

**Fecha**: 2026-01-05

**Decisión**: Almacenar audio MP3 de decisiones y sesiones completas.

**Razón**:
- Requisito para retroalimentación
- Permite revisión posterior
- Mejora experiencia educativa

**Alternativas Consideradas**:
- Solo texto (rechazado: no cumple requisitos)
- Audio en formato diferente (rechazado: MP3 es estándar)

**Impacto**:
- Requiere almacenamiento de archivos
- Campos adicionales en tablas

---

### Referencias y Recursos Útiles

#### Documentación de Pixel Crushers

- **Sitio Web**: https://www.pixelcrushers.com/dialogue-system/
- **Documentación**: https://www.pixelcrushers.com/dialogue-system-manual/
- **Foros**: https://www.pixelcrushers.com/forums/
- **Ejemplos**: Incluidos en el paquete del plugin

#### Documentación de Laravel

- **Laravel 12.x Docs**: https://laravel.com/docs/12.x
- **Eloquent ORM**: https://laravel.com/docs/12.x/eloquent
- **API Resources**: https://laravel.com/docs/12.x/eloquent-resources
- **Testing**: https://laravel.com/docs/12.x/testing

#### Documentación de Unity

- **Unity Manual**: https://docs.unity3d.com/Manual/
- **Unity Scripting API**: https://docs.unity3d.com/ScriptReference/
- **Unity UI (uGUI)**: https://docs.unity3d.com/Manual/UISystem.html
- **GraphView API**: https://docs.unity3d.com/Packages/com.unity.ui.builder@latest/

#### Recursos Adicionales

- **JSON Schema**: https://json-schema.org/
- **REST API Best Practices**: https://restfulapi.net/
- **MySQL Optimization**: https://dev.mysql.com/doc/refman/8.0/en/optimization.html
- **C# Best Practices**: https://docs.microsoft.com/en-us/dotnet/csharp/programming-guide/

---

## Resumen de Herramientas

### Scripts Disponibles

1. ✅ **mapear-estructura-clases.php**: Mapea estructura de clases
2. ✅ **extraer-dependencias.php**: Extrae dependencias entre clases
3. ✅ **analizar-uso-memoria.php**: Analiza uso de memoria
4. ✅ **generar-documentacion.php**: Genera documentación automática

### Base de Conocimiento

1. ✅ **Notas por componente**: Documentadas
2. ✅ **Decisiones de diseño**: 5 decisiones documentadas
3. ✅ **Referencias**: Enlaces a documentación útil

### Uso de Herramientas

```bash
# Ejecutar todos los scripts
php database/scripts/mapear-estructura-clases.php
php database/scripts/extraer-dependencias.php
php database/scripts/analizar-uso-memoria.php
php database/scripts/generar-documentacion.php

# Ver resultados
ls -la storage/app/analysis/
```

---

**Última actualización:** 2026-01-05  
**Versión:** 1.0.0
