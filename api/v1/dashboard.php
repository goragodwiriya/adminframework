<?php
/**
 * CRM Dashboard API
 * Returns dashboard metrics
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Response
$data = [
    'success' => true,
    'message' => 'Dashboard data retrieved successfully',
    'code' => 200,
    'data' => [
        'overview' => [
            'total_customers' => rand(10, 100),
            'customer_growth' => rand(-10, 10),
            'pipeline_value' => rand(0, 10000),
            'active_deals' => rand(0, 10),
            'revenue_this_month' => number_format(rand(0, 10000), 2),
            'revenue_growth' => rand(-10, 10),
            'win_rate_this_month' => rand(0, 100),
            'won_deals_this_month' => rand(0, 10),
            'lost_deals_this_month' => rand(0, 10)
        ]
    ]
];

echo json_encode($data, JSON_UNESCAPED_UNICODE);
