<?php
session_start();

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'cozyhomestay';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: homepage.php");
    exit();
}

$user_id = $_SESSION['user']['user_id'];

// Fetch user's bookings with homestay details and amenities
$query = "SELECT b.*, h.name as homestay_name, h.address, h.price_per_night,
          p.payment_method, p.status as payment_status, p.payment_date,
          GROUP_CONCAT(DISTINCT a.name) as amenity_names,
          GROUP_CONCAT(DISTINCT a.price) as amenity_prices,
          cl.current_tier,
          lt.discount_percentage
          FROM bookings b
          LEFT JOIN homestays h ON b.homestay_id = h.homestay_id
          LEFT JOIN payments p ON b.booking_id = p.booking_id
          LEFT JOIN booking_amenities ba ON b.booking_id = ba.booking_id
          LEFT JOIN amenities a ON ba.amenity_id = a.amenity_id
          LEFT JOIN customer_loyalty cl ON b.user_id = cl.user_id
          LEFT JOIN loyalty_tiers lt ON cl.current_tier = lt.tier_id
          WHERE b.user_id = ?
          GROUP BY b.booking_id
          ORDER BY b.check_in_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - EFZEE COTTAGE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* Previous styles remain unchanged */
        .price-breakdown {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        .price-breakdown-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 0.5rem;
        }

        .price-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
        }

        .price-item:last-child {
            border-top: 2px solid #dee2e6;
            margin-top: 1rem;
            padding-top: 1rem;
            font-weight: 600;
        }

        .amenity-list {
            margin: 1rem 0;
            padding: 0.5rem 0;
        }

        .amenity-item {
            display: flex;
            justify-content: space-between;
            padding: 0.25rem 0;
            color: #666;
        }

        .loyalty-discount {
            color: #28a745;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <a href="homepage.php" class="nav-brand">EFZEE COTTAGE</a>
    </nav>

    <div class="container">
        <h1>My Bookings</h1>

        <?php if (empty($bookings)): ?>
            <div class="booking-card">
                <p>You haven't made any bookings yet.</p>
                <a href="homepage.php" class="btn btn-primary">Book Now</a>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card">
                    <div class="booking-header">
                        <h2><?php echo htmlspecialchars($booking['homestay_name']); ?></h2>
                        <span class="booking-status status-<?php echo strtolower($booking['status']); ?>">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                    </div>

                    <div class="booking-details">
                        <div class="detail-group">
                            <div class="detail-label">Check-in Date</div>
                            <div><?php echo date('F d, Y', strtotime($booking['check_in_date'])); ?></div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label">Check-out Date</div>
                            <div><?php echo date('F d, Y', strtotime($booking['check_out_date'])); ?></div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label">Total Guests</div>
                            <div><?php echo $booking['total_guests']; ?> persons</div>
                        </div>
                    </div>

                    <!-- Price Breakdown Section -->
                    <div class="price-breakdown">
                        <div class="price-breakdown-title">Price Breakdown</div>

                        <?php
                        $check_in = new DateTime($booking['check_in_date']);
                        $check_out = new DateTime($booking['check_out_date']);
                        $nights = $check_in->diff($check_out)->days;
                        $base_price = $booking['price_per_night'] * $nights;
                        ?>

                        <div class="price-item">
                            <span>Base Price (<?php echo $nights; ?> nights ×
                                RM<?php echo number_format($booking['price_per_night'], 2); ?>)</span>
                            <span>RM <?php echo number_format($base_price, 2); ?></span>
                        </div>

                        <?php if ($booking['amenity_names']): ?>
                            <div class="amenity-list">
                                <div class="detail-label">Selected Amenities:</div>
                                <?php
                                $amenity_names = explode(',', $booking['amenity_names']);
                                $amenity_prices = explode(',', $booking['amenity_prices']);
                                $total_amenities = 0;

                                for ($i = 0; $i < count($amenity_names); $i++):
                                    $amenity_price = $amenity_prices[$i] * $nights;
                                    $total_amenities += $amenity_price;
                                    ?>
                                    <div class="amenity-item">
                                        <span><?php echo htmlspecialchars($amenity_names[$i]); ?> (RM<?php echo $amenity_prices[$i]; ?>
                                            × <?php echo $nights; ?> nights)</span>
                                        <span>RM <?php echo number_format($amenity_price, 2); ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($booking['discount_percentage'] > 0): ?>
                            <div class="price-item loyalty-discount">
                                <span>Loyalty Discount (<?php echo $booking['discount_percentage']; ?>%)</span>
                                <span>- RM
                                    <?php echo number_format(($base_price + ($total_amenities ?? 0)) * ($booking['discount_percentage'] / 100), 2); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="price-item">
                            <strong>Total Amount</strong>
                            <strong>RM <?php echo number_format($booking['total_price'], 2); ?></strong>
                        </div>
                    </div>

                    <div class="payment-info">
                        <div class="detail-label">Payment Information</div>
                        <div class="detail-group">
                            <div>Method:
                                <?php echo $booking['payment_method'] ? ucfirst(str_replace('_', ' ', $booking['payment_method'])) : 'Not specified'; ?>
                            </div>
                            <div>Status:
                                <?php echo $booking['payment_status'] ? ucfirst($booking['payment_status']) : 'Pending'; ?>
                            </div>
                            <?php if ($booking['payment_date']): ?>
                                <div>Date: <?php echo date('F d, Y', strtotime($booking['payment_date'])); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <?php if ($booking['status'] === 'pending'): ?>
                            <a href="modify_booking.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-primary">Modify
                                Booking</a>
                            <a href="cancel_booking.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-danger">Cancel
                                Booking</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>