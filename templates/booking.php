<section id="booking" class="parallax-section">
    <div class="parallax-content">
        <div class="loyalty-card">
            <h3><i class="fas fa-crown"></i> Your Loyalty Status</h3>
            <?php if (isset($_SESSION['user'])):
                $loyalty = getCustomerLoyaltyStatus($_SESSION['user']['user_id'], $conn);
                $tiers = getLoyaltyTiers($conn);
                $nextTier = null;

                $loyalty = getCustomerLoyaltyStatus($_SESSION['user']['user_id'], $conn) ?? [
                    'loyalty_points' => 0,
                    'current_tier' => '-'
                ];

                // Find next tier
                foreach ($tiers as $tier) {
                    if ($tier['min_points'] > $loyalty['loyalty_points']) {
                        $nextTier = $tier;
                        break;
                    }
                }
                ?>
                <div class="loyalty-progress">
                    <div class="tier-info">
                        <span class="current-tier">Tier <?= $loyalty['current_tier'] ?? '' ?></span>

                        <?php if ($nextTier): ?>
                            <span class="next-tier">Next: Tier <?= $nextTier['tier_id'] ?> (<?= $nextTier['min_points'] ?>
                                points)</span>
                        <?php endif; ?>
                    </div>
                    <?php
                    $progress = 0;
                    if ($nextTier && $nextTier['min_points'] > 0) {
                        $progress = ($loyalty['loyalty_points'] / $nextTier['min_points']) * 100;
                        $progress = min(100, $progress); // cap at 100%
                    }
                    ?>
                    <div class="progress-bar">
                        <div class="progress" style="width: <?= round($progress, 2) ?>%"></div>
                    </div>

                    <div class="points-info">
                        <span><?= $loyalty['loyalty_points'] ?? '' ?> points</span>

                        <?php if ($nextTier): ?>
                            <span><?= $nextTier['min_points'] - $loyalty['loyalty_points'] ?> to next tier</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="loyalty-benefits">
                    <h4>Your Benefits:</h4>
                    <ul>
                        <li><i class="fas fa-check"></i>
                            <?= calculateLoyaltyDiscount($_SESSION['user']['user_id'], $conn) ?>% discount on all
                            bookings
                        </li>
                        <li><i class="fas fa-check"></i> Priority customer support</li>
                        <li><i class="fas fa-check"></i> Early access to promotions</li>
                    </ul>
                </div>
            <?php else: ?>
                <p>Sign in to view your loyalty status and earn rewards!</p>
            <?php endif; ?>
        </div>
        <h1>Book Your Stay</h1>
        <p class="subtitle">Reserve Your Perfect Getaway</p>

        <div id="loginMessage" class="login-message" style="display: none;">
            <p>Please login to complete your booking</p>
        </div>

        <?php
        $selected_homestay_id = isset($_GET['homestay_id']) ? intval($_GET['homestay_id']) : 1;

        // Get booked dates for selected homestay
        $booked_dates = [];
        $stmt = $conn->prepare("SELECT check_in_date, check_out_date FROM bookings WHERE status != 'cancelled' AND homestay_id = ?");
        $stmt->bind_param("i", $selected_homestay_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $start = new DateTime($row['check_in_date']);
            $end = new DateTime($row['check_out_date']);
            for ($date = clone $start; $date <= $end; $date->modify('+1 day')) {
                $booked_dates[] = $date->format('Y-m-d');
            }
        }
        $stmt->close();

        $today = new DateTime();
        $month = $today->format('F Y');
        $days_in_month = $today->format('t');
        $first_day = new DateTime($today->format('Y-m-01'));
        $starting_day = $first_day->format('w'); // 0-6 (Sun-Sat)
        ?>

        <!-- Calendar -->
        <div class="calendar-container">
            <h3>Check Availability</h3>

            <!-- Dropdown to select Homestay -->
            <?php
            $selected_homestay_id = isset($_GET['homestay_id']) ? (int) $_GET['homestay_id'] : null;
            ?>

            <form method="get" style="margin-bottom: 20px;">
                <label for="homestay_id"><strong>Select Homestay:</strong></label>
                <select name="homestay_id" id="homestay_id" onchange="this.form.submit()">
                    <?php
                    // Fetch only available homestays from the database
                    $query = "SELECT homestay_id, name FROM homestays WHERE status = 'available'";
                    $result = mysqli_query($conn, $query);

                    if ($result && mysqli_num_rows($result) > 0):
                        while ($row = mysqli_fetch_assoc($result)):
                            $selected = ($selected_homestay_id == $row['homestay_id']) ? 'selected' : '';
                            ?>
                            <option value="<?= $row['homestay_id'] ?>" <?= $selected ?>>
                                <?= htmlspecialchars($row['name']) ?>
                            </option>
                            <?php
                        endwhile;
                    else:
                        ?>
                        <option disabled>No available homestays</option>
                    <?php endif; ?>
                </select>
            </form>



            <div id="calendar">
                <div class="calendar-grid">
                    <h4><?= $month; ?></h4>
                    <div class="calendar-header">
                        <div>Sun</div>
                        <div>Mon</div>
                        <div>Tue</div>
                        <div>Wed</div>
                        <div>Thu</div>
                        <div>Fri</div>
                        <div>Sat</div>
                    </div>

                    <div class="calendar-days">
                        <?php
                        for ($i = 0; $i < $starting_day; $i++) {
                            echo "<div class='calendar-day empty'></div>";
                        }

                        for ($day = 1; $day <= $days_in_month; $day++) {
                            $date_str = $today->format('Y-m-') . str_pad($day, 2, '0', STR_PAD_LEFT);
                            $current_date = new DateTime($date_str);
                            $is_booked = in_array($date_str, $booked_dates);
                            $is_past = $current_date < new DateTime('today');

                            $classes = ['calendar-day'];
                            if ($is_past) {
                                $classes[] = 'past';
                            } elseif ($is_booked) {
                                $classes[] = 'booked';
                            } else {
                                $classes[] = 'available';
                            }

                            echo "<div class='" . implode(' ', $classes) . "'>";
                            echo $day;
                            if ($is_booked && !$is_past) {
                                echo "<span class='booked-label'>Booked</span>";
                            }
                            echo "</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Form -->
        <form id="bookingForm" class="booking-form" method="POST" action="process_booking.php">
            <div class="form-row">
                <div class="form-group">
                    <label>Check-in Date</label>
                    <input type="date" name="check_in_date" id="checkInDate" required>
                </div>
                <div class="form-group">
                    <label>Check-out Date</label>
                    <input type="date" name="check_out_date" id="checkOutDate" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Number of Guests</label>
                    <input type="number" name="total_guests" id="totalGuests" min="1" max="10" required>
                </div>
                <div class="form-group">
                    <label>Homestay</label>
                    <select name="homestay_id" id="homestaySelect" required>
                        <option value="" disabled selected>Select Homestay</option>
                        <?php
                        $homestaysQuery = "SELECT * FROM homestays WHERE status = 'available' ORDER BY name";
                        $homestaysResult = $conn->query($homestaysQuery);
                        while ($homestay = $homestaysResult->fetch_assoc()):
                            ?>
                            <option value="<?= $homestay['homestay_id'] ?>" data-price="<?= $homestay['price_per_night'] ?>"
                                data-max-guests="<?= $homestay['max_guests'] ?>">
                                <?= htmlspecialchars($homestay['name']) ?> -
                                RM<?= number_format($homestay['price_per_night'], 2) ?>/night
                            </option>
                        <?php endwhile; ?>
                    </select>

                </div>

                <div id="amenitiesContainer" class="form-group">
                </div>

            </div>

            <?php
            // Fetch amenities with prices for the selected homestay
            $selected_homestay_id = isset($_GET['homestay_id']) ? intval($_GET['homestay_id']) : 1;

            // Query to get amenities available for this homestay
            $amenities_query = "SELECT a.amenity_id, a.name, a.icon, a.price 
                   FROM amenities a
                   JOIN homestay_amenities ha ON a.amenity_id = ha.amenity_id
                   WHERE ha.homestay_id = ?";
            $stmt = $conn->prepare($amenities_query);
            $stmt->bind_param("i", $selected_homestay_id);
            $stmt->execute();
            $amenities_result = $stmt->get_result();
            $available_amenities = $amenities_result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Convert to JSON for JavaScript use
            $amenities_json = json_encode($available_amenities);
            ?>


            <div class="price-breakdown">
                <h3>Price Breakdown</h3>
                <div class="price-row">
                    <span>Base Rate (per night):</span>
                    <span id="baseRate">RM 0.00</span>
                </div>
                <div class="price-row">
                    <span>Number of Nights:</span>
                    <span id="numberOfNights">0</span>
                </div>

                <div id="selectedAmenitiesContainer">
                    <!-- Selected amenities price rows will be inserted here -->
                </div>

                <div class="price-row subtotal-row">
                    <span>Subtotal:</span>
                    <span id="subtotal">RM 0.00</span>
                </div>

                <div class="price-row discount-row" style="display: none;">
                    <span><i class="fas fa-tag"></i> <span id="discountText">Loyalty Discount</span>:</span>
                    <span id="discountAmount">-RM 0.00</span>
                </div>

                <div class="price-row total">
                    <span>Total:</span>
                    <span id="totalPrice">RM 0.00</span>
                </div>
            </div>


            <script>
                // Use PHP JSON data
                const availableAmenities = <?= $amenities_json ?>;

                // Example base rate and nights (replace with your dynamic values)
                let baseRate = parseFloat(document.querySelector('#homestaySelect option:checked').dataset.price) || 0;
                let numberOfNights = 1;  // You can change this dynamically as needed

                document.getElementById('baseRate').textContent = `RM ${baseRate.toFixed(2)}`;
                document.getElementById('numberOfNights').textContent = numberOfNights;

                const selectedAmenitiesContainer = document.getElementById('selectedAmenitiesContainer');

                // Function to update price breakdown when amenities change
                function updatePriceBreakdown(selectedAmenityIds) {
                    // Clear current amenities list
                    selectedAmenitiesContainer.innerHTML = '';

                    let amenitiesTotal = 0;
                    selectedAmenityIds.forEach(id => {
                        const amenity = availableAmenities.find(a => a.amenity_id == id);
                        if (amenity) {
                            amenitiesTotal += parseFloat(amenity.price);
                            // Create a price row for this amenity
                            const row = document.createElement('div');
                            row.classList.add('price-row');
                            row.innerHTML = `<span>${amenity.name}:</span> <span>RM ${parseFloat(amenity.price).toFixed(2)}</span>`;
                            selectedAmenitiesContainer.appendChild(row);
                        }
                    });

                    // Calculate subtotal: (baseRate * nights) + amenitiesTotal
                    const subtotal = (baseRate * numberOfNights) + amenitiesTotal;
                    document.getElementById('subtotal').textContent = `RM ${subtotal.toFixed(2)}`;

                    // For simplicity, no discount here
                    document.getElementById('totalPrice').textContent = `RM ${subtotal.toFixed(2)}`;
                }

                // Hook this to your amenities checkboxes change event
                // Example: Assuming you have checkboxes named amenities[] with amenity_id as value
                function setupAmenityCheckboxes() {
                    const checkboxes = document.querySelectorAll('input[name="amenities[]"]');
                    checkboxes.forEach(cb => {
                        cb.addEventListener('change', () => {
                            const selected = Array.from(checkboxes)
                                .filter(chk => chk.checked)
                                .map(chk => chk.value);
                            updatePriceBreakdown(selected);
                        });
                    });
                }

                // Initialize price breakdown with no amenities selected
                updatePriceBreakdown([]);

                // Wait for DOM fully loaded to bind events
                document.addEventListener('DOMContentLoaded', () => {
                    setupAmenityCheckboxes();

                    // Also update baseRate if homestay changes
                    const homestaySelect = document.getElementById('homestaySelect');
                    homestaySelect.addEventListener('change', () => {
                        baseRate = parseFloat(homestaySelect.options[homestaySelect.selectedIndex].dataset.price) || 0;
                        document.getElementById('baseRate').textContent = `RM ${baseRate.toFixed(2)}`;
                        updatePriceBreakdown(
                            Array.from(document.querySelectorAll('input[name="amenities[]"]:checked')).map(cb => cb.value)
                        );
                    });
                });
            </script>


            <!-- Add this somewhere visible -->
            <div id="loyaltyMessage" class="loyalty-message" style="display: none;">
                <i class="fas fa-crown"></i>
                <span id="loyaltyMessageText"></span>
            </div>

            <?php $user_id = $_SESSION['user']['user_id'] ?? 0; ?>
            <input type="hidden" name="user_id" value="<?= $_SESSION['user']['user_id'] ?? 0; ?>">
            <input type="hidden" name="calculated_price" id="calculatedPrice" value="0.00">

            <div class="payment-method-select">
                <h3>Select Payment Method</h3>
                <div class="payment-options">
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="credit_card" required>
                        <i class="fas fa-credit-card"></i>
                        <span>Credit Card</span>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="debit_card" required>
                        <i class="fas fa-money-check"></i>
                        <span>Debit Card</span>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="bank_transfer" required>
                        <i class="fas fa-university"></i>
                        <span>Bank Transfer</span>
                    </label>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="e_wallet" required>
                        <i class="fas fa-wallet"></i>
                        <span>E-Wallet</span>
                    </label>
                </div>
            </div>

            <button type="submit" class="submit-btn">Book Now</button>
        </form>
    </div>
</section>