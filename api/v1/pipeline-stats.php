<?php
/**
 * Pipeline Stats Chart Data
 * Returns data for pie/doughnut chart
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Pipeline stage data
$stages = [
    'lead' => 3,
    'qualified' => 4,
    'proposal' => 4,
    'negotiation' => 3,
    'won' => 5,
    'lost' => 1
];

$colors = [
    'lead' => '#3b82f6',
    'qualified' => '#10b981',
    'proposal' => '#f59e0b',
    'negotiation' => '#8b5cf6',
    'won' => '#06b6d4',
    'lost' => '#ef4444'
];

$stageLabels = [
    'lead' => 'Lead',
    'qualified' => 'Qualified',
    'proposal' => 'Proposal',
    'negotiation' => 'Negotiation',
    'won' => 'Won',
    'lost' => 'Lost'
];

$stageData = [];
foreach ($stages as $key => $value) {
    $stageData[] = [
        'label' => $stageLabels[$key],
        'value' => $value,
        'color' => $colors[$key]
    ];
}

$data = [
    'success' => true,
    'message' => 'Pipeline statistics data retrieved successfully',
    'code' => 200,
    'data' => [
        [
            'name' => 'จำนวนดีล',
            'data' => $stageData
        ]
    ]
];

echo json_encode($data, JSON_UNESCAPED_UNICODE);
