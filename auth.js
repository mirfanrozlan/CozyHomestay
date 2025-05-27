// DOM Elements
const loginBtn = document.getElementById('loginBtn');
const loginModal = document.getElementById('loginModal');
const closeBtn = document.querySelector('.close');
const tabBtns = document.querySelectorAll('.tab-btn');
const loginForm = document.getElementById('loginForm');
const signupForm = document.getElementById('signupForm');

// Show/Hide Modal
loginBtn.onclick = () => loginModal.style.display = 'block';
closeBtn.onclick = () => loginModal.style.display = 'none';
window.onclick = (e) => {
    if (e.target === loginModal) {
        loginModal.style.display = 'none';
    }
};

// Tab Switching
tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        // Update active tab button
        tabBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // Show corresponding form
        const isLogin = btn.dataset.tab === 'login';
        loginForm.style.display = isLogin ? 'flex' : 'none';
        signupForm.style.display = isLogin ? 'none' : 'flex';
    });
});

// Handle Login
loginForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const email = loginForm.querySelector('input[type="email"]').value;
    const password = loginForm.querySelector('input[type="password"]').value;
    const role = loginForm.querySelector('select[name="role"]').value;

    // Check admin credentials (in real app, this would be a secure backend check)
    if (role === 'admin') {
        if (email === 'admin@cozy.com' && password === 'admin123') {
            localStorage.setItem('userEmail', email);
            localStorage.setItem('userRole', role);
            Swal.fire({
                title: 'Welcome Admin!',
                text: 'Successfully logged in to admin panel',
                icon: 'success'
            }).then(() => {
                window.location.href = 'admin.html';
            });
            return;
        } else {
            Swal.fire({
                title: 'Error!',
                text: 'Invalid admin credentials!',
                icon: 'error'
            });
            return;
        }
    }

    // Regular customer login
    const isReturningCustomer = localStorage.getItem('returningCustomer');

    // Store user data
    localStorage.setItem('userEmail', email);
    localStorage.setItem('userRole', role);
    localStorage.setItem('returningCustomer', 'true');

    // Show success message with loyalty program integration
    const userBookings = homestayManager.getUserBookingHistory(email);
    const loyaltyDiscount = homestayManager.calculateLoyaltyDiscount(email);
    
    Swal.fire({
        title: 'Welcome back!',
        html: `
            ${isReturningCustomer && role === 'customer' ? 
                `<p>You get ${loyaltyDiscount * 100}% off on your next stay!</p>` : 
                '<p>Successfully logged in!</p>'}
            ${userBookings > 0 ? 
                `<p>You have made ${userBookings} booking(s) with us.</p>` : ''}
        `,
        icon: 'success'
    }).then(() => {
        loginModal.style.display = 'none';
        // Redirect to admin panel if admin
        if (role === 'admin') {
            window.location.href = 'admin.html';
        }
        // Refresh page to show loyalty discount if returning customer
        else if (isReturningCustomer) {
            window.location.reload();
        }
    });
});

// Handle Sign Up
signupForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const email = signupForm.querySelector('input[type="email"]').value;
    const password = signupForm.querySelector('input[type="password"]').value;
    const confirmPassword = signupForm.querySelectorAll('input[type="password"]')[1].value;

    // Basic validation
    if (password !== confirmPassword) {
        Swal.fire({
            title: 'Error!',
            text: 'Passwords do not match!',
            icon: 'error'
        });
        return;
    }

    // Simulate signup (In real app, this would be a backend API call)
    localStorage.setItem('userEmail', email);
    localStorage.setItem('userRole', 'customer');

    // Initialize user preferences
    homestayManager.setPreference(email, {
        roomSetup: 'standard',
        amenities: ['wifi', 'breakfast'],
        specialRequests: ''
    });

    Swal.fire({
        title: 'Success!',
        html: `
            <p>Account created successfully!</p>
            <p>Welcome to our loyalty program! Book your first stay to start earning discounts.</p>
        `,
        icon: 'success'
    }).then(() => {
        loginModal.style.display = 'none';
    });
});

// Check for returning customer on page load
document.addEventListener('DOMContentLoaded', () => {
    const userId = localStorage.getItem('userEmail');
    const userRole = localStorage.getItem('userRole');
    const isReturningCustomer = localStorage.getItem('returningCustomer');

    if (isReturningCustomer && userRole === 'customer') {
        const loyaltyDiscount = homestayManager.calculateLoyaltyDiscount(userId);
        if (loyaltyDiscount > 0) {
            // Create and show loyalty banner
            const banner = document.createElement('div');
            banner.style.cssText = `
                position: fixed;
                top: 80px;
                left: 50%;
                transform: translateX(-50%);
                background-color: #27ae60;
                color: white;
                padding: 1rem 2rem;
                border-radius: 5px;
                z-index: 98;
                text-align: center;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            `;
            banner.textContent = `🎉 Welcome back! You get ${loyaltyDiscount * 100}% off on your next stay.`;
            document.body.appendChild(banner);

            // Remove banner after 5 seconds
            setTimeout(() => banner.remove(), 5000);
        }
    }
});