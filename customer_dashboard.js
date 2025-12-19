// Welcome animation on page load
document.addEventListener('DOMContentLoaded', function() {
    // Animate stats counting up
    const statValues = document.querySelectorAll('.stat-value');
    statValues.forEach(stat => {
        const target = parseInt(stat.textContent);
        let current = 0;
        const increment = Math.ceil(target / 20);
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            stat.textContent = current;
        }, 50);
    });

    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.action-card, .card, .welcome-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Add to cart confirmation
document.querySelectorAll('.btn-add').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="fas fa-check"></i> Added!';
        this.style.background = '#10b981';
        
        setTimeout(() => {
            this.innerHTML = originalText;
            this.style.background = '#d97528';
        }, 1500);
    });
});

// Update cart count dynamically
function updateCartBadge(count) {
    const cartLink = document.querySelector('.cart-link');
    if (cartLink) {
        cartLink.innerHTML = '<i class="fas fa-shopping-cart"></i> Cart (' + count + ')';
    }
}

// Greeting based on time of day
function updateGreeting() {
    const hour = new Date().getHours();
    const welcomeText = document.querySelector('.welcome-info h2');
    if (welcomeText) {
        const name = welcomeText.textContent.split(',')[1]?.trim().replace('!', '').replace('👋', '').trim() || 'there';
        let greeting = 'Welcome back';
        
        if (hour < 12) greeting = 'Good morning';
        else if (hour < 17) greeting = 'Good afternoon';
        else greeting = 'Good evening';
        
        welcomeText.innerHTML = greeting + ', ' + name + '! 👋';
    }
}

updateGreeting();