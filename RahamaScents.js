/*// Database simulation with localStorage
function initDB() {
    if (!localStorage.getItem('users')) {
        localStorage.setItem('users', JSON.stringify([])); // Users array
    }
    if (!localStorage.getItem('scents')) {
        localStorage.setItem('scents', JSON.stringify([
            { id: 1, name: 'Lavender Bliss', price: 20, stock: 10 },
            { id: 2, name: 'Ocean Breeze', price: 25, stock: 15 }
        ])); // Scents/products
    }
    if (!localStorage.getItem('orders')) {
        localStorage.setItem('orders', JSON.stringify([])); // Orders array
    }
    if (!localStorage.getItem('cart')) {
        localStorage.setItem('cart', JSON.stringify([])); // Cart for customer
    }
}

initDB();

// Signup function
function signup() {
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const role = document.getElementById('role').value;
    let users = JSON.parse(localStorage.getItem('users'));
    if (users.find(u => u.username === username)) {
        alert('User exists');
        return;
    }
    users.push({ username, password, role });
    localStorage.setItem('users', JSON.stringify(users));
    alert('Signup successful! Now login.');
}

// Login function
function login() {
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    let users = JSON.parse(localStorage.getItem('users'));
    const user = users.find(u => u.username === username && u.password === password);
    if (user) {
        localStorage.setItem('currentUser', JSON.stringify(user));
        showDashboard(user.role);
        document.getElementById('login').style.display = 'none';
    } else {
        alert('Invalid credentials');
    }
}

// Show dashboard based on role
function showDashboard(role) {
    document.getElementById('shop').style.display = 'block';
    document.getElementById('dashboard').style.display = 'block';
    document.getElementById('orders').style.display = 'block';
    document.getElementById('analytics').style.display = 'block';
    // Load specific content (we'll add in next steps)
    if (role === 'admin') {
        loadAdminDashboard();
    } else if (role === 'customer') {
        loadCustomerDashboard();
    } else if (role === 'delivery') {
        loadDeliveryDashboard();
    }
}

// Placeholder functions for dashboards (we'll fill in next steps)
function loadAdminDashboard() { alert('Admin dashboard loading...'); }
function loadCustomerDashboard() { alert('Customer dashboard loading...'); }
function loadDeliveryDashboard() { alert('Delivery dashboard loading...'); }*/
// Simple smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            window.scrollTo({
                top: target.offsetTop - 80, // Offset for fixed header
                behavior: 'smooth'
            });
        }
    });
});

// Basic form submission alert (replace with real backend later)
document.querySelector('form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    alert('Thank you! Your message has been sent. We will get back to you soon.');
    this.reset();
});

// Optional: Add active class to current nav link
document.addEventListener('DOMContentLoaded', () => {
    const current = location.pathname.split('/').pop() || 'RahamaScents.php';
    document.querySelectorAll('.nav a').forEach(link => {
        if (link.getAttribute('href') === current) {
            link.classList.add('active');
        }
    });
});