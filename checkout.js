// Store total for button text updates
let orderTotal = document.getElementById('orderTotal')?.value || '0.00';

// Select payment method
function selectPayment(type, element) {
    // Remove selected from all
    document.querySelectorAll('.payment-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    
    // Add selected to clicked
    element.classList.add('selected');
    
    // Hide all detail forms
    document.getElementById('cardDetails').classList.remove('show');
    document.getElementById('momoDetails').classList.remove('show');
    
    // Show relevant form
    if (type === 'visa') {
        document.getElementById('cardDetails').classList.add('show');
    } else if (type === 'momo') {
        document.getElementById('momoDetails').classList.add('show');
    }
    
    // Update button text
    updateButtonText(type);
}

// Update button text based on payment method
function updateButtonText(type) {
    const btn = document.getElementById('submitBtn');
    const total = btn.getAttribute('data-total');
    
    if (type === 'cod') {
        btn.innerHTML = '<i class="fas fa-check"></i> Place Order - GHC ' + total;
    } else {
        btn.innerHTML = '<i class="fas fa-lock"></i> Pay GHC ' + total;
    }
}

// Format card number with spaces
document.getElementById('cardNumber')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s/g, '').replace(/\D/g, '');
    let formatted = value.match(/.{1,4}/g)?.join(' ') || value;
    e.target.value = formatted;
});

// Format expiry date
document.getElementById('expiryDate')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2);
    }
    e.target.value = value;
});

// CVV only numbers
document.getElementById('cvv')?.addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/\D/g, '');
});

// MoMo number formatting
document.getElementById('momoNumber')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 10) value = value.substring(0, 10);
    
    // Format as 0XX XXX XXXX
    if (value.length > 6) {
        value = value.substring(0, 3) + ' ' + value.substring(3, 6) + ' ' + value.substring(6);
    } else if (value.length > 3) {
        value = value.substring(0, 3) + ' ' + value.substring(3);
    }
    e.target.value = value;
});

// Form submission with loading
document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
    const btn = document.getElementById('submitBtn');
    const selectedPayment = document.querySelector('input[name="payment"]:checked').value;
    
    // Validate card details if Visa selected
    if (selectedPayment === 'Visa/Card') {
        const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
        const expiry = document.getElementById('expiryDate').value;
        const cvv = document.getElementById('cvv').value;
        const cardName = document.getElementById('cardName').value;
        
        if (cardNumber.length < 16 || !expiry || cvv.length < 3 || !cardName) {
            e.preventDefault();
            alert('Please fill in all card details');
            return;
        }
    }
    
    // Validate MoMo details if MoMo selected
    if (selectedPayment === 'MoMo Ghana') {
        const network = document.getElementById('momoNetwork').value;
        const momoNumber = document.getElementById('momoNumber').value.replace(/\s/g, '');
        
        if (!network || momoNumber.length < 10) {
            e.preventDefault();
            alert('Please fill in all Mobile Money details');
            return;
        }
    }
    
    // Show loading state
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;
});

// Page load animations
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});