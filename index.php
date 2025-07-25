<?php
function normalizeQuotes($line) {
    // Replace single quotes with double quotes
    return str_replace("'", '"', $line);
}

function isIgnoredLine($line) {
    $trimmed = trim($line);
    
    // Ignore empty lines
    if (empty($trimmed)) {
        return true;
    }
    
    // Ignore lines with only opening or closing braces
    if ($trimmed === '{' || $trimmed === '}') {
        return true;
    }
    
    // Ignore single-line comments (// or #)
    if (preg_match('/^\s*(\/\/|#)/', $trimmed)) {
        return true;
    }
    
    // Ignore lines that are only block comment start or end
    if (preg_match('/^\s*\/\*.*\*\/\s*$/', $trimmed) || 
        preg_match('/^\s*\/\*/', $trimmed) || 
        preg_match('/^\s*\*\//', $trimmed) ||
        preg_match('/^\s*\*/', $trimmed)) {
        return true;
    }
    
    return false;
}

function processFile($filepath) {
    if (!file_exists($filepath)) {
        throw new Exception("File not found: $filepath");
    }
    
    $lines = file($filepath, FILE_IGNORE_NEW_LINES);
    $processedLines = [];
    $inBlockComment = false;
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Handle block comments
        if (strpos($trimmed, '/*') !== false && strpos($trimmed, '*/') !== false) {
            // Single line block comment - ignore
            continue;
        } elseif (strpos($trimmed, '/*') !== false) {
            $inBlockComment = true;
            continue;
        } elseif (strpos($trimmed, '*/') !== false) {
            $inBlockComment = false;
            continue;
        }
        
        if ($inBlockComment) {
            continue;
        }
        
        if (!isIgnoredLine($line)) {
            // Normalize whitespace and quotes
            $normalized = normalizeQuotes(trim($line));
            $processedLines[] = $normalized;
        }
    }
    
    return $processedLines;
}

function compareFiles($file1Path, $file2Path) {
    try {
        $file1Lines = processFile($file1Path);
        $file2Lines = processFile($file2Path);
        
        // Convert to sets for comparison
        $set1 = array_flip($file1Lines);
        $set2 = array_flip($file2Lines);
        
        // Find common lines
        $commonLines = array_intersect_key($set1, $set2);
        $commonCount = count($commonLines);
        
        // Find lines unique to each file
        $uniqueToFile1 = array_diff_key($set1, $set2);
        $uniqueToFile2 = array_diff_key($set2, $set1);
        
        return [
            'common' => $commonCount,
            'unique_to_file1' => count($uniqueToFile1),
            'unique_to_file2' => count($uniqueToFile2),
            'file1_total' => count($file1Lines),
            'file2_total' => count($file2Lines)
        ];
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file1 = $_POST['file1'] ?? '';
    $file2 = $_POST['file2'] ?? '';
    
    if (empty($file1) || empty($file2)) {
        $error = "Please provide both file paths.";
    } else {
        $result = compareFiles($file1, $file2);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Comparison Tool</title>
    <link rel="icon" type="image/png" sizes="256x256" href="./assets/images/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }


        h2 {
            color: #333;
            margin-top: 30px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        input[type="text"]:focus {
            border-color: #4CAF50;
            outline: none;
        }
        
        button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        
        button:hover {
            background-color: #45a049;
        }
        
        .results {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 4px;
            border-left: 4px solid #4CAF50;
        }
        
        .error {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
        }
        
        .stat {
            margin: 10px 0;
            padding: 10px;
            background-color: white;
            border-radius: 4px;
            border-left: 3px solid #007bff;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .instructions {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #2196f3;
        }
        
        .instructions h3 {
            margin-top: 0;
            color: #1976d2;
        }
        
        .instructions ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .instructions li {
            margin: 5px 0;
        }

        /* --- Table Styling --- */
        .result-table {
            font-family: "Nunito", sans-serif;
            width: 100%;
            border-collapse: collapse; /* Removes space between cell borders */
        }

        /* --- Table Cell (td) Styling --- */
        .result-table td {
            padding: 1rem 1.5rem; /* Generous padding for readability */
            border-bottom: 1px solid #e5e7eb; /* Subtle line between rows */
            vertical-align: middle;
        }

        /* --- Remove bottom border from the last row --- */
        .result-table tr:last-child td {
            border-bottom: none;
        }

        /* --- Zebra-striping for alternate row background color --- */
        .result-table tr:nth-child(even) {
            background-color: #f9fafb; /* Very light gray for even rows */
        }

        /* --- Style for the first cell in each row (the "label") --- */
        .result-table td:first-child {
            font-weight: 500; /* Medium font weight for labels */
            color: #111827; /* Darker text for labels */
        }
        
        /* --- Style for the data cells --- */
        .result-table td:not(:first-child) {
            text-align: right; /* Align numbers to the right */
            font-weight: 700; /* Bold font for data */
            font-size: 1.125rem; /* Slightly larger font size for emphasis */
        }

        /* --- Specific colors for data cells to add visual meaning --- */
        .text-blue {
            color: #2563eb;
        }
        .text-red {
            color: #dc2626;
        }
        .text-green {
            color: #16a34a;
        }

        /* --- Button Styling --- */
        .button-container {
            margin-top: 1.5rem;
            text-align: center;
        }

        .copy-button {
            background-color: #4f46e5; /* Indigo */
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            font-size: 1rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out, transform 0.1s ease;
        }

        .copy-button:hover {
            background-color: #4338ca;
        }

        .copy-button:active {
            transform: scale(0.98);
        }

        .message-box {
            background-color: #e0f2fe; /* Light blue */
            color: #0c4a6e; /* Dark blue text */
            padding: 12px;
            border-radius: 8px;
            margin-top: 20px;
            display: none; /* Hidden by default */
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .message-box.show {
            display: block;
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>File Comparison Tool</h1>
        
        <div class="instructions">
            <h3>How it works:</h3>
            <ul>
                <li>Compares two files line by line</li>
                <li>Ignores leading and trailing whitespace</li>
                <li>Treats single quotes and double quotes as the same</li>
                <li>Ignores empty lines</li>
                <li>Ignores lines with only opening or closing braces ({ or })</li>
                <li>Ignores comments (// # /* */ and block comments)</li>
            </ul>
        </div>
        
        <form method="post">
            <div class="form-group">
                <label for="file1">New File Path:</label>
                <input type="text" id="file1" name="file1" 
                       value="<?php echo htmlspecialchars($_POST['file1'] ?? ''); ?>" 
                       placeholder="e.g., /path/to/file1.txt"
                       required>
            </div>
            
            <div class="form-group">
                <label for="file2">Old File Path:</label>
                <input type="text" id="file2" name="file2" 
                       value="<?php echo htmlspecialchars($_POST['file2'] ?? ''); ?>" 
                       placeholder="e.g., /path/to/file2.txt"
                       required>
            </div>
            
            <button type="submit">Compare Files</button>
        </form>
        
        <?php if ($error): ?>
            <div class="error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($result): ?>
            <?php if (isset($result['error'])): ?>
                <div class="error">
                    <strong>Error:</strong> <?php echo htmlspecialchars($result['error']); ?>
                </div>
            <?php else: ?>
                <!-- <div class="results">
                    <h2>Comparison Results</h2>
                    
                    <div class="stat">
                        <div class="stat-value"><?php echo $result['common']; ?></div>
                        <div class="stat-label">Lines that are the same in both files</div>
                    </div>
                    
                    <div class="stat">
                        <div class="stat-value"><?php echo $result['unique_to_file1']; ?></div>
                        <div class="stat-label">Lines in New File that are not in Old File</div>
                    </div>
                    
                    <div class="stat">
                        <div class="stat-value"><?php echo $result['unique_to_file2']; ?></div>
                        <div class="stat-label">Lines in Old File that are not in New File</div>
                    </div>
                    
                    <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">
                    
                    <div style="display: flex; gap: 20px;">
                        <div style="flex: 1;">
                            <strong>New File processed lines:</strong> <?php echo $result['file1_total']; ?>
                        </div>
                        <div style="flex: 1;">
                            <strong>Old File processed lines:</strong> <?php echo $result['file2_total']; ?>
                        </div>
                    </div>
                </div> -->

                <h2>Comparison Results</h2>

                <table class="result-table" id="resultTable">
                    <tbody>
                        <tr>
                            <td>New File processed lines</td>
                            <td class="text-green"><?php echo $result['file1_total']; ?></td>
                        </tr>
                        <tr>
                            <td>Old File processed lines</td>
                            <td class="text-green"><?php echo $result['file2_total']; ?></td>
                        </tr>
                        <tr>
                            <td>Lines that are the same in both files</td>
                            <td class="text-blue"><?php echo $result['common']; ?></td>
                        </tr>
                        <tr>
                            <td>Lines in New File that are not in Old File</td>
                            <td class="text-red"><?php echo $result['unique_to_file1']; ?></td>
                        </tr>
                        <tr>
                            <td>Lines in Old File that are not in New File</td>
                            <td class="text-red"><?php echo $result['unique_to_file2']; ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="button-container">
                    <button id="copyButton" class="copy-button"  onclick="copyTableToClipboard('resultTable')">Copy Table</button>
                </div>

                <div id="messageBox" class="message-box">
                    Table copied to clipboard!
                </div>

            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        /**
         * Copies the content of an HTML table to the clipboard in a format
         * that can be pasted directly into Microsoft Word as a table.
         * @param {string} tableId The ID of the HTML table element to copy.
         */
        function copyTableToClipboard(tableId) {
            const table = document.getElementById(tableId);
            const messageBox = document.getElementById('messageBox');

            if (!table) {
                console.error(`Table with ID '${tableId}' not found.`);
                showMessage('Error: Table not found.', true);
                return;
            }

            // Create a temporary div to hold the table's HTML
            const tempDiv = document.createElement('div');
            // Clone the table to avoid modifying the original DOM element directly
            // and to ensure all styles are preserved for clipboard copy.
            tempDiv.appendChild(table.cloneNode(true));

            // Append the temporary div to the body (it needs to be in the DOM to be selected)
            document.body.appendChild(tempDiv);

            // Select the content of the temporary div
            const range = document.createRange();
            range.selectNode(tempDiv);
            window.getSelection().removeAllRanges(); // Clear any existing selections
            window.getSelection().addRange(range);

            try {
                // Execute the copy command
                const successful = document.execCommand('copy');
                if (successful) {
                    showMessage('Table copied to clipboard!');
                    console.log('Table copied successfully!');
                } else {
                    showMessage('Failed to copy table. Please try again.', true);
                    console.error('Failed to copy table.');
                }
            } catch (err) {
                showMessage('Error copying table: ' + err, true);
                console.error('Error copying table:', err);
            } finally {
                // Clean up: remove the temporary div and clear the selection
                document.body.removeChild(tempDiv);
                window.getSelection().removeAllRanges();
            }
        }

        /**
         * Displays a temporary message to the user.
         * @param {string} message The message to display.
         * @param {boolean} isError If true, styles the message as an error.
         */
        function showMessage(message, isError = false) {
            const messageBox = document.getElementById('messageBox');
            messageBox.textContent = message;

            // Apply error styling if it's an error message
            if (isError) {
                messageBox.style.backgroundColor = '#fee2e2'; /* Light red */
                messageBox.style.color = '#991b1b'; /* Dark red text */
            } else {
                messageBox.style.backgroundColor = '#e0f2fe'; /* Light blue */
                messageBox.style.color = '#0c4a6e'; /* Dark blue text */
            }

            messageBox.classList.add('show');

            // Hide the message after 3 seconds
            setTimeout(() => {
                messageBox.classList.remove('show');
            }, 3000);
        }
    </script>
</body>
</html>