<?php
header('Content-Type: application/json');
require 'include/config.php';

if (!isset($_SESSION['user'])) {
    echo json_encode([
        'discount' => 0,
        'tier_name' => '',
        'points' => 0,
        'next_tier' => '',
        'points_needed' => 0
    ]);
    exit;
}

$user_id = $_SESSION['user']['user_id'];

// Get current loyalty status
$stmt = $conn->prepare("
    SELECT 
        cl.loyalty_points as points,
        lt.tier_name,
        lt.discount_percentage as discount
    FROM customer_loyalty cl
    LEFT JOIN loyalty_tiers lt ON cl.current_tier = lt.tier_id
    WHERE cl.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$currentStatus = $result->fetch_assoc() ?? ['points' => 0, 'tier_name' => '', 'discount' => 0];

// Get next tier information
$nextTierStmt = $conn->prepare("
    SELECT 
        tier_name,
        min_points - ? as points_needed
    FROM loyalty_tiers
    WHERE min_points > ?
    ORDER BY min_points ASC
    LIMIT 1
");
$nextTierStmt->bind_param("ii", $currentStatus['points'], $currentStatus['points']);
$nextTierStmt->execute();
$nextTierResult = $nextTierStmt->get_result();
$nextTier = $nextTierResult->fetch_assoc() ?? ['tier_name' => '', 'points_needed' => 0];

echo json_encode([
    'discount' => (float) $currentStatus['discount'],
    'tier_name' => $currentStatus['tier_name'] ?? '',
    'points' => (int) $currentStatus['points'],
    'next_tier' => $nextTier['tier_name'] ?? '',
    'points_needed' => (int) $nextTier['points_needed']
]);
?>