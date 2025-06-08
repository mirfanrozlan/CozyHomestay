<?php
session_start();
header('Content-Type: application/json');
require_once 'include/config.php';

// Check login
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Validate required fields
    $required_fields = ['homestay_id', 'check_in_date', 'check_out_date', 'total_guests', 'calculated_price', 'payment_method'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Use user_id from session (secure)
    $user_id = $_SESSION['user']['user_id'];

    // Validate and sanitize input data
    $homestay_id = filter_var($_POST['homestay_id'], FILTER_VALIDATE_INT);
    $check_in_date = date('Y-m-d', strtotime($_POST['check_in_date']));
    $check_out_date = date('Y-m-d', strtotime($_POST['check_out_date']));
    $total_guests = filter_var($_POST['total_guests'], FILTER_VALIDATE_INT);
    $total_price = filter_var($_POST['calculated_price'], FILTER_VALIDATE_FLOAT);
    $payment_method = htmlspecialchars(strip_tags($_POST['payment_method']));

    // Validate dates
    if ($check_in_date >= $check_out_date) {
        throw new Exception("Check-out date must be after check-in date");
    }

    // Start transaction
    $conn->begin_transaction();

    // Insert booking
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, homestay_id, check_in_date, check_out_date, total_guests, total_price, status) 
                           VALUES (?, ?, ?, ?, ?, ?, 'confirmed')");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("iissid", $user_id, $homestay_id, $check_in_date, $check_out_date, $total_guests, $total_price);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $booking_id = $conn->insert_id;

    // ✅ Insert selected amenities into booking_amenities table
    if (!empty($_POST['amenities']) && is_array($_POST['amenities'])) {
        $stmtAmenities = $conn->prepare("INSERT INTO booking_amenities (booking_id, amenity_id) VALUES (?, ?)");
        if (!$stmtAmenities) {
            throw new Exception("Prepare failed for amenities: " . $conn->error);
        }

        foreach ($_POST['amenities'] as $amenity_id) {
            $amenity_id = (int) $amenity_id;
            $stmtAmenities->bind_param("ii", $booking_id, $amenity_id);
            if (!$stmtAmenities->execute()) {
                throw new Exception("Failed to insert amenity: " . $stmtAmenities->error);
            }
        }
    }

    // Insert payment
    $stmt2 = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, status, payment_date) 
                            VALUES (?, ?, ?, 'completed', CURDATE())");
    if (!$stmt2) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt2->bind_param("ids", $booking_id, $total_price, $payment_method);
    if (!$stmt2->execute()) {
        throw new Exception("Execute failed: " . $stmt2->error);
    }

    // Update loyalty status
    updateLoyaltyStatus($user_id, $total_price, $conn);

    // Commit transaction
    $conn->commit();

    $response = [
        'success' => true,
        'booking_id' => $booking_id,
        'message' => 'Booking created successfully'
    ];

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn instanceof mysqli && $conn->connect_errno === 0) {
        $conn->rollback();
    }

    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];
}

echo json_encode($response);

// Function to update loyalty status
function updateLoyaltyStatus($user_id, $booking_amount, $conn)
{
    try {
        // Add points (1 point per RM10 spent)
        $points = floor($booking_amount / 10);

        $stmt = $conn->prepare("
            INSERT INTO customer_loyalty (user_id, loyalty_points, total_bookings, total_spent) 
            VALUES (?, ?, 1, ?)
            ON DUPLICATE KEY UPDATE 
                loyalty_points = loyalty_points + VALUES(loyalty_points),
                total_bookings = total_bookings + 1,
                total_spent = total_spent + VALUES(total_spent)
        ");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("iid", $user_id, $points, $booking_amount);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        // Update tier based on new points
        $updateTier = $conn->prepare("
            UPDATE customer_loyalty cl
            JOIN (
                SELECT MAX(tier_id) as max_tier 
                FROM loyalty_tiers 
                WHERE min_points <= (SELECT loyalty_points FROM customer_loyalty WHERE user_id = ?)
            ) t
            SET cl.current_tier = t.max_tier
            WHERE cl.user_id = ?;
        ");
        if (!$updateTier) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $updateTier->bind_param("ii", $user_id, $user_id);
        if (!$updateTier->execute()) {
            throw new Exception("Execute failed: " . $updateTier->error);
        }

    } catch (Exception $e) {
        // Log the error but don’t fail the booking because of loyalty update failure
        error_log("Loyalty update failed: " . $e->getMessage());
    }
}
?>