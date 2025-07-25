<?php

/**
 * Dump and Die - Laravel-style debugging function
 * Dumps variables with beautiful formatting and stops execution
 */
function dd(...$vars) {
    // Check if we're in CLI or web environment
    $isCli = php_sapi_name() === 'cli';
    
    if (!$isCli) {
        // Web environment - output HTML
        echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>dd() Debug Output</title>
    <style>
        body { 
            font-family: "SF Mono", Monaco, Inconsolata, "Roboto Mono", Consolas, "Courier New", monospace;
            background: #1a1a1a; 
            color: #e1e1e1; 
            margin: 0; 
            padding: 20px;
            line-height: 1.4;
        }
        .dd-container { 
            background: #2d2d2d; 
            border-radius: 8px; 
            padding: 20px; 
            margin: 10px 0;
            border-left: 4px solid #ff6b6b;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .dd-header { 
            color: #ff6b6b; 
            font-weight: bold; 
            margin-bottom: 15px;
            font-size: 16px;
            border-bottom: 1px solid #444;
            padding-bottom: 10px;
        }
        .dd-type { 
            color: #4ecdc4; 
            font-weight: bold; 
        }
        .dd-null { color: #999; font-style: italic; }
        .dd-bool { color: #f39c12; }
        .dd-string { color: #2ecc71; }
        .dd-number { color: #e74c3c; }
        .dd-array { color: #9b59b6; }
        .dd-object { color: #3498db; }
        .dd-resource { color: #e67e22; }
        .dd-indent { margin-left: 20px; }
        .dd-key { color: #f1c40f; }
        .dd-arrow { color: #95a5a6; margin: 0 5px; }
        .dd-length { color: #95a5a6; font-size: 12px; }
        .dd-backtrace {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #444;
            font-size: 12px;
            color: #bdc3c7;
        }
        .dd-file { color: #e74c3c; }
        .dd-line { color: #f39c12; }
    </style>
</head>
<body>';
    }
    
    // Get backtrace for debugging info
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = $backtrace[0] ?? null;
    
    // If no variables passed, just show where dd() was called
    if (empty($vars)) {
        $vars = ['dd() called with no arguments'];
    }
    
    foreach ($vars as $index => $var) {
        if (!$isCli) {
            echo '<div class="dd-container">';
            echo '<div class="dd-header">';
            if (count($vars) > 1) {
                echo "Variable #" . ($index + 1) . " ";
            }
            if ($caller) {
                echo '<span class="dd-file">' . basename($caller['file']) . '</span>';
                echo '<span class="dd-line">:' . $caller['line'] . '</span>';
            }
            echo '</div>';
            echo formatVariable($var, 0, false);
            echo '</div>';
        } else {
            // CLI environment - output plain text
            echo "\n" . str_repeat("=", 50) . "\n";
            if (count($vars) > 1) {
                echo "Variable #" . ($index + 1) . "\n";
            }
            if ($caller) {
                echo "File: " . $caller['file'] . ":" . $caller['line'] . "\n";
            }
            echo str_repeat("-", 30) . "\n";
            echo formatVariableCli($var, 0);
            echo "\n" . str_repeat("=", 50) . "\n";
        }
    }
    
    if (!$isCli) {
        echo '</body></html>';
    }
    
    // Stop execution
    exit(1);
}

/**
 * Format variable for HTML output
 */
function formatVariable($var, $depth = 0, $isArrayValue = false) {
    $indent = str_repeat('<div class="dd-indent">', $depth);
    $closeIndent = str_repeat('</div>', $depth);
    $type = gettype($var);
    
    switch ($type) {
        case 'NULL':
            return $indent . '<span class="dd-type">null</span> <span class="dd-null">null</span>' . $closeIndent;
            
        case 'boolean':
            $value = $var ? 'true' : 'false';
            return $indent . '<span class="dd-type">bool</span> <span class="dd-bool">' . $value . '</span>' . $closeIndent;
            
        case 'integer':
            return $indent . '<span class="dd-type">int</span> <span class="dd-number">' . $var . '</span>' . $closeIndent;
            
        case 'double':
            return $indent . '<span class="dd-type">float</span> <span class="dd-number">' . $var . '</span>' . $closeIndent;
            
        case 'string':
            $length = strlen($var);
            $escaped = htmlspecialchars($var, ENT_QUOTES, 'UTF-8');
            return $indent . '<span class="dd-type">string</span> <span class="dd-length">(' . $length . ')</span> <span class="dd-string">"' . $escaped . '"</span>' . $closeIndent;
            
        case 'array':
            if (empty($var)) {
                return $indent . '<span class="dd-type">array</span> <span class="dd-length">(0)</span> []' . $closeIndent;
            }
            
            $count = count($var);
            $result = $indent . '<span class="dd-type">array</span> <span class="dd-length">(' . $count . ')</span> [<br>';
            
            foreach ($var as $key => $value) {
                $result .= '<div class="dd-indent">';
                $result .= '<span class="dd-key">' . (is_string($key) ? '"' . htmlspecialchars($key) . '"' : $key) . '</span>';
                $result .= '<span class="dd-arrow">=></span>';
                $result .= formatVariable($value, $depth + 1, true);
                $result .= '</div>';
            }
            
            $result .= $indent . ']' . $closeIndent;
            return $result;
            
        case 'object':
            $className = get_class($var);
            $result = $indent . '<span class="dd-type">object</span>(<span class="dd-object">' . $className . '</span>) {<br>';
            
            $reflection = new ReflectionClass($var);
            $properties = $reflection->getProperties();
            
            foreach ($properties as $property) {
                $property->setAccessible(true);
                $value = $property->getValue($var);
                $visibility = $property->isPublic() ? 'public' : ($property->isProtected() ? 'protected' : 'private');
                
                $result .= '<div class="dd-indent">';
                $result .= '<span class="dd-key">' . $visibility . ' $' . $property->getName() . '</span>';
                $result .= '<span class="dd-arrow">=></span>';
                $result .= formatVariable($value, $depth + 1, true);
                $result .= '</div>';
            }
            
            $result .= $indent . '}' . $closeIndent;
            return $result;
            
        case 'resource':
            return $indent . '<span class="dd-type">resource</span> <span class="dd-resource">' . get_resource_type($var) . '</span>' . $closeIndent;
            
        default:
            return $indent . '<span class="dd-type">' . $type . '</span> ' . var_export($var, true) . $closeIndent;
    }
}

/**
 * Format variable for CLI output
 */
function formatVariableCli($var, $depth = 0) {
    $indent = str_repeat("  ", $depth);
    $type = gettype($var);
    
    switch ($type) {
        case 'NULL':
            return $indent . "null null";
            
        case 'boolean':
            $value = $var ? 'true' : 'false';
            return $indent . "bool $value";
            
        case 'integer':
            return $indent . "int $var";
            
        case 'double':
            return $indent . "float $var";
            
        case 'string':
            $length = strlen($var);
            return $indent . "string($length) \"$var\"";
            
        case 'array':
            if (empty($var)) {
                return $indent . "array(0) []";
            }
            
            $count = count($var);
            $result = $indent . "array($count) [\n";
            
            foreach ($var as $key => $value) {
                $keyStr = is_string($key) ? "\"$key\"" : $key;
                $result .= $indent . "  $keyStr => ";
                $result .= formatVariableCli($value, $depth + 1) . "\n";
            }
            
            $result .= $indent . "]";
            return $result;
            
        case 'object':
            $className = get_class($var);
            $result = $indent . "object($className) {\n";
            
            $reflection = new ReflectionClass($var);
            $properties = $reflection->getProperties();
            
            foreach ($properties as $property) {
                $property->setAccessible(true);
                $value = $property->getValue($var);
                $visibility = $property->isPublic() ? 'public' : ($property->isProtected() ? 'protected' : 'private');
                
                $result .= $indent . "  $visibility $" . $property->getName() . " => ";
                $result .= formatVariableCli($value, $depth + 1) . "\n";
            }
            
            $result .= $indent . "}";
            return $result;
            
        case 'resource':
            return $indent . "resource " . get_resource_type($var);
            
        default:
            return $indent . "$type " . var_export($var, true);
    }
}

// Optional: Create an alias for dump() function (like Laravel's dump without die)
function dump(...$vars) {
    $isCli = php_sapi_name() === 'cli';
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = $backtrace[0] ?? null;
    
    if (empty($vars)) {
        $vars = ['dump() called with no arguments'];
    }
    
    foreach ($vars as $index => $var) {
        if (!$isCli) {
            echo '<div style="font-family: monospace; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 15px; margin: 10px 0; color: #495057;">';
            echo '<div style="color: #6c757d; font-size: 12px; margin-bottom: 10px;">';
            if (count($vars) > 1) {
                echo "Variable #" . ($index + 1) . " ";
            }
            if ($caller) {
                echo basename($caller['file']) . ':' . $caller['line'];
            }
            echo '</div>';
            echo '<pre style="margin: 0; white-space: pre-wrap;">' . print_r($var, true) . '</pre>';
            echo '</div>';
        } else {
            echo "\n--- DUMP ---\n";
            if (count($vars) > 1) {
                echo "Variable #" . ($index + 1) . "\n";
            }
            if ($caller) {
                echo "File: " . $caller['file'] . ":" . $caller['line'] . "\n";
            }
            print_r($var);
            echo "\n-----------\n";
        }
    }
}