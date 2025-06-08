<nav class="navbar">
    <div class="nav-brand">EFZEE COTTAGE</div>
    <button class="mobile-menu-btn">
        <i class="fas fa-bars"></i>
    </button>
    <div class="nav-links">
        <a href="#home" class="active">Home</a>
        <a href="#about">About Us</a>
        <a href="#gallery">Gallery</a>
        <a href="#booking">Book Now</a>
        <?php if (isset($_SESSION['user'])): ?>
            <a href="mybooking.php">My Bookings</a>
        <?php endif; ?>
        <!-- <a href="#reviews">Reviews</a> -->
        <div class="nav-user-menu">
            <button id="loginBtn" class="nav-button">Login / Sign Up</button>
            <form id="logoutForm" action="logout.php" method="POST" style="display: inline;"></form>
            <a href="logout.php" id="logoutBtn" style="display: none;">Logout</a>

            </form>

        </div>
    </div>
</nav>
<!-- Active Section Indicator -->
<div class="active-section">
    <a href="#home" class="active" data-section="Home"></a>
    <a href="#about" data-section="About"></a>
    <a href="#gallery" data-section="Gallery"></a>
    <a href="#booking" data-section="Booking"></a>
</div>