<?php
/**
 * Quarterly Sales Chart Data
 * Returns data for bar chart
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Mock quarterly sales data
$data = [
    'success' => true,
    'message' => 'Quarterly sales data retrieved successfully',
    'code' => 200,
    'data' => [
        [
            'name' => '2024',
            'data' => [
                ['label' => 'Q1', 'value' => 25000],
                ['label' => 'Q2', 'value' => 12000],
                ['label' => 'Q3', 'value' => 18000],
                ['label' => 'Q4', 'value' => 17000]
            ]
        ],
        [
            'name' => '2025',
            'data' => [
                ['label' => 'Q1', 'value' => 18000],
                ['label' => 'Q2', 'value' => 13000],
                ['label' => 'Q3', 'value' => 23000],
                ['label' => 'Q4', 'value' => 12000]
            ]
        ]
    ]
];

echo json_encode($data, JSON_UNESCAPED_UNICODE);
