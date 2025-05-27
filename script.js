// Calendar Functionality
class Calendar {
    constructor() {
        this.currentDate = new Date();
        this.selectedDate = null;
        this.availableDates = this.loadAvailableDates();
        this.initializeCalendar();
    }

    loadAvailableDates() {
        // Simulate available dates (in real app, this would come from backend)
        const dates = {};
        const today = new Date();
        for(let i = 0; i < 60; i++) {
            const date = new Date(today);
            date.setDate(today.getDate() + i);
            // Randomly mark some dates as unavailable
            dates[this.formatDate(date)] = Math.random() > 0.3 ? 'available' : 'unavailable';
        }
        return dates;
    }

    formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    initializeCalendar() {
        this.renderCalendar();
        this.attachEventListeners();
    }

    renderCalendar() {
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();

        // Update month display
        document.getElementById('currentMonth').textContent = 
            new Date(year, month).toLocaleString('default', { month: 'long', year: 'numeric' });

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        const startingDay = firstDay.getDay();

        const calendarDays = document.getElementById('calendarDays');
        calendarDays.innerHTML = '';

        // Add empty cells for days before the first day of the month
        for(let i = 0; i < startingDay; i++) {
            calendarDays.appendChild(this.createDayElement(''));
        }

        // Add days of the month
        for(let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            const dateStr = this.formatDate(date);
            const availability = this.availableDates[dateStr];
            const isSelected = this.selectedDate === dateStr;

            const dayElement = this.createDayElement(
                day,
                availability,
                isSelected,
                dateStr
            );
            calendarDays.appendChild(dayElement);
        }
    }

    createDayElement(content, availability = '', isSelected = false, dateStr = '') {
        const div = document.createElement('div');
        div.className = 'calendar-day';
        
        if (content === '') {
            div.className += ' empty';
            return div;
        }

        div.textContent = content;
        div.dataset.date = dateStr;

        if (availability === 'available') {
            div.className += ' available';
        } else if (availability === 'unavailable') {
            div.className += ' unavailable';
        }

        if (isSelected) {
            div.className += ' selected';
        }

        if (availability === 'available') {
            div.addEventListener('click', () => this.selectDate(dateStr, div));
        }

        return div;
    }

    selectDate(dateStr, element) {
        // Remove previous selection
        const previousSelected = document.querySelector('.calendar-day.selected');
        if (previousSelected) {
            previousSelected.classList.remove('selected');
        }

        // Add new selection
        element.classList.add('selected');
        this.selectedDate = dateStr;

        // Show SweetAlert confirmation
        Swal.fire({
            title: 'Date Selected!',
            text: `You have selected ${new Date(dateStr).toLocaleDateString()}. Would you like to proceed with this date?`,
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Yes, proceed!',
            cancelButtonText: 'No, choose another date'
        }).then((result) => {
            if (!result.isConfirmed) {
                element.classList.remove('selected');
                this.selectedDate = null;
            }
        });
    }

    attachEventListeners() {
        document.getElementById('prevMonth').addEventListener('click', () => {
            this.currentDate.setMonth(this.currentDate.getMonth() - 1);
            this.renderCalendar();
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            this.currentDate.setMonth(this.currentDate.getMonth() + 1);
            this.renderCalendar();
        });
    }
}

// Initialize Calendar
const calendar = new Calendar();


// Dynamic Pricing and Personalization Features
class HomestayManager {
    constructor() {
        this.basePrice = 100; // Base price per night
        this.preferences = new Map();
        this.loadUserPreferences();
    }

    loadUserPreferences() {
        const savedPreferences = localStorage.getItem('userPreferences');
        if (savedPreferences) {
            this.preferences = new Map(JSON.parse(savedPreferences));
        }
    }

    saveUserPreferences() {
        localStorage.setItem('userPreferences', 
            JSON.stringify(Array.from(this.preferences.entries())));
    }

    setPreference(userId, preference) {
        this.preferences.set(userId, {
            ...preference,
            timestamp: new Date().toISOString()
        });
        this.saveUserPreferences();
    }

    calculateDynamicPrice(basePrice, date) {
        const dayOfWeek = date.getDay();
        const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
        const isHoliday = this.isHoliday(date);
        const occupancyRate = this.getOccupancyRate(date);

        let price = basePrice;

        // Weekend markup
        if (isWeekend) price *= 1.2;

        // Holiday markup
        if (isHoliday) price *= 1.3;

        // Occupancy-based adjustment
        if (occupancyRate > 0.8) {
            price *= 1.15; // High demand
        } else if (occupancyRate < 0.3) {
            price *= 0.85; // Low demand
        }

        return Math.round(price);
    }

    isHoliday(date) {
        // Add holiday logic here
        return false;
    }

    getOccupancyRate(date) {
        // Simulate occupancy rate based on availability data
        const availabilityData = JSON.parse(localStorage.getItem('availabilityData')) || {};
        const dateKey = `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`;
        return availabilityData[dateKey] === 'unavailable' ? 0.9 : 0.5;
    }

    // Customer Loyalty Program
    calculateLoyaltyDiscount(userId) {
        const userBookings = this.getUserBookingHistory(userId);
        if (userBookings >= 5) return 0.15; // 15% discount
        if (userBookings >= 3) return 0.10; // 10% discount
        if (userBookings >= 1) return 0.05; // 5% discount
        return 0;
    }

    getUserBookingHistory(userId) {
        const bookingHistory = JSON.parse(localStorage.getItem('bookingHistory')) || {};
        return bookingHistory[userId] || 0;
    }

    // Personalization System
    recordCustomerPreferences(userId, preferences) {
        const userPrefs = {
            roomSetup: preferences.roomSetup,
            amenities: preferences.amenities,
            specialRequests: preferences.specialRequests,
            timestamp: new Date().toISOString()
        };
        this.setPreference(userId, userPrefs);
    }
}

// Initialize HomestayManager
const homestayManager = new HomestayManager();

// Enhance Booking Form
if (document.getElementById('bookingForm')) {
    const bookingForm = document.getElementById('bookingForm');
    
    // Add preference fields to booking form
    const preferencesDiv = document.createElement('div');
    preferencesDiv.className = 'form-group';
    preferencesDiv.innerHTML = `
        <label for="roomSetup">Room Setup Preference</label>
        <select id="roomSetup" name="roomSetup">
            <option value="standard">Standard</option>
            <option value="business">Business</option>
            <option value="family">Family</option>
        </select>

        <label for="specialRequests">Special Requests</label>
        <textarea id="specialRequests" name="specialRequests" rows="3"></textarea>
    `;
    bookingForm.insertBefore(preferencesDiv, bookingForm.querySelector('button'));

    // Enhanced form submission
    bookingForm.addEventListener('submit', (e) => {
        e.preventDefault();

        const userId = localStorage.getItem('userEmail');
        const preferences = {
            roomSetup: document.getElementById('roomSetup').value,
            specialRequests: document.getElementById('specialRequests').value,
            amenities: ['wifi', 'breakfast'] // Default amenities
        };

        // Record preferences
        homestayManager.recordCustomerPreferences(userId, preferences);

        // Calculate price with loyalty discount
        const selectedDate = new Date(document.querySelector('.calendar-day.selected').dataset.date);
        const basePrice = homestayManager.basePrice;
        const dynamicPrice = homestayManager.calculateDynamicPrice(basePrice, selectedDate);
        const loyaltyDiscount = homestayManager.calculateLoyaltyDiscount(userId);
        const finalPrice = dynamicPrice * (1 - loyaltyDiscount);

        // Show booking confirmation with personalized message
        Swal.fire({
            title: 'Booking Confirmed!',
            html: `
                <p>Thank you for choosing our homestay!</p>
                <p>Base Price: $${basePrice}</p>
                <p>Dynamic Price: $${dynamicPrice}</p>
                ${loyaltyDiscount > 0 ? `<p>Loyalty Discount: ${loyaltyDiscount * 100}%</p>` : ''}
                <p>Final Price: $${finalPrice}</p>
                <p>We've noted your preferences and will prepare accordingly.</p>
            `,
            icon: 'success'
        }).then(() => {
            bookingForm.reset();
            // Update booking history
            const bookingHistory = JSON.parse(localStorage.getItem('bookingHistory')) || {};
            bookingHistory[userId] = (bookingHistory[userId] || 0) + 1;
            localStorage.setItem('bookingHistory', JSON.stringify(bookingHistory));
        });
    });
}

// Update calendar day rendering to show dynamic pricing
if (document.getElementById('calendarGrid')) {
    const originalRenderCalendar = window.renderCalendar;
    window.renderCalendar = function() {
        originalRenderCalendar();
        
        // Add dynamic pricing to calendar days
        document.querySelectorAll('.calendar-day:not(.empty)').forEach(dateCell => {
            const day = parseInt(dateCell.textContent);
            const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
            const dynamicPrice = homestayManager.calculateDynamicPrice(homestayManager.basePrice, date);
            
            // Add price display
            const priceDisplay = document.createElement('div');
            priceDisplay.style.fontSize = '0.8em';
            priceDisplay.style.color = '#666';
            priceDisplay.textContent = `$${dynamicPrice}`;
            dateCell.appendChild(priceDisplay);
        });
    };
    
    // Re-render calendar
    window.renderCalendar();
}