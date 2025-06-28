<?php

/**
 * Test script for array handling in CSV export
 */

echo "Testing array handling for CSV export...\n\n";

// Test cases for different answer types
$testAnswers = [
    'string_simple' => 'John Doe',
    'string_with_quotes' => '"John Doe"',
    'array_simple' => ['Option 1', 'Option 2'],
    'array_with_numbers' => [1, 2, 3],
    'array_mixed' => ['Option 1', 2, 'Option 3'],
    'null_value' => null,
    'empty_string' => '',
    'numeric' => 42,
    'boolean_true' => true,
    'boolean_false' => false,
];

function processAnswer($rawAnswer) {
    // Handle different answer types
    if (is_array($rawAnswer)) {
        // If it's an array, join the values
        $answer = implode(', ', array_map('strval', $rawAnswer));
    } else if (is_string($rawAnswer)) {
        // If it's a string, use it directly
        $answer = $rawAnswer;
    } else {
        // For any other type, convert to string
        $answer = (string) $rawAnswer;
    }
    
    // Remove quotes if present (only for strings)
    if (is_string($answer)) {
        $answer = trim($answer, '"');
    }
    
    return $answer;
}

foreach ($testAnswers as $type => $value) {
    echo "Testing $type: ";
    echo "Input: " . json_encode($value) . " => ";
    
    try {
        $result = processAnswer($value);
        echo "Output: '$result'\n";
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ All test cases completed successfully!\n";
