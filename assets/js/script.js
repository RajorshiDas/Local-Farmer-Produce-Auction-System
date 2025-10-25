// Farmer Auction System JavaScript

// Countdown timer for auctions
function updateCountdown() {
    const countdownElements = document.querySelectorAll('.countdown-timer');
    
    countdownElements.forEach(element => {
        const endTime = new Date(element.dataset.endTime).getTime();
        const startTime = element.dataset.startTime ? new Date(element.dataset.startTime).getTime() : null;
        const now = new Date().getTime();
        const isUpcoming = element.classList.contains('upcoming-timer');
        
        // For upcoming auctions, show time until start
        if (isUpcoming && startTime) {
            const distanceToStart = startTime - now;
            
            if (distanceToStart <= 0) {
                element.innerHTML = "Auction Starting Soon!";
                element.classList.add('text-success');
                return;
            }
            
            const days = Math.floor(distanceToStart / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distanceToStart % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distanceToStart % (1000 * 60 * 60)) / (1000 * 60));
            
            let timeString = 'Starts in ';
            if (days > 0) timeString += days + 'd ';
            if (hours > 0) timeString += hours + 'h ';
            if (minutes > 0 || (days === 0 && hours === 0)) timeString += minutes + 'm';
            
            element.innerHTML = timeString;
            element.classList.add('text-info');
            return;
        }
        
        // For active auctions, show time until end
        const distance = endTime - now;
        
        if (distance < 0) {
            element.innerHTML = "Auction Ended";
            element.classList.add('text-danger');
            return;
        }
        
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        let timeString = 'Ends in ';
        if (days > 0) timeString += days + 'd ';
        if (hours > 0) timeString += hours + 'h ';
        if (minutes > 0) timeString += minutes + 'm ';
        timeString += seconds + 's';
        
        element.innerHTML = timeString;
        
        // Add ending soon class if less than 1 hour
        if (distance < 3600000) {
            element.classList.add('ending-soon');
        }
    });
}

// Update countdown every second
setInterval(updateCountdown, 1000);

// Initialize countdown on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCountdown();
});

// Wishlist functionality
function toggleWishlist(productId, button) {
    fetch('ajax/toggle_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ product_id: productId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const icon = button.querySelector('i');
            if (data.in_wishlist) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                button.classList.add('btn-danger');
                button.classList.remove('btn-outline-danger');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                button.classList.remove('btn-danger');
                button.classList.add('btn-outline-danger');
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

// Bid form validation
function validateBidForm() {
    const bidAmount = document.getElementById('bid_amount');
    const currentBid = parseFloat(document.getElementById('current_bid').value);
    const bidValue = parseFloat(bidAmount.value);
    
    if (bidValue <= currentBid) {
        alert('Your bid must be higher than the current bid.');
        bidAmount.focus();
        return false;
    }
    
    return true;
}

// Auto-refresh auction data
function refreshAuctionData() {
    const auctionCards = document.querySelectorAll('.auction-card');
    auctionCards.forEach(card => {
        const auctionId = card.dataset.auctionId;
        if (auctionId) {
            fetch(`ajax/get_auction_data.php?auction_id=${auctionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const currentBidElement = card.querySelector('.current-bid');
                    const bidCountElement = card.querySelector('.bid-count');
                    
                    if (currentBidElement) {
                        currentBidElement.textContent = '৳' + data.current_bid;
                    }
                    if (bidCountElement) {
                        bidCountElement.textContent = data.bid_count + ' bids';
                    }
                }
            })
            .catch(error => console.error('Error refreshing auction data:', error));
        }
    });
}

// Refresh auction data every 30 seconds
setInterval(refreshAuctionData, 30000);

// Image preview for file uploads
function previewImages(input) {
    const preview = document.getElementById('image-preview');
    preview.innerHTML = '';
    
    if (input.files) {
        Array.from(input.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'col-md-3 mb-2';
                div.innerHTML = `
                    <div class="position-relative">
                        <img src="${e.target.result}" class="img-thumbnail" style="width: 100%; height: 150px; object-fit: cover;">
                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" onclick="removeImage(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }
}

function removeImage(button) {
    button.closest('.col-md-3').remove();
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Show loading spinner
function showLoading(element) {
    const originalContent = element.innerHTML;
    element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    element.disabled = true;
    
    return function hideLoading() {
        element.innerHTML = originalContent;
        element.disabled = false;
    };
}

// Notification system
function showNotification(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Check for new notifications
function checkNotifications() {
    if (typeof userType !== 'undefined' && userType === 'buyer') {
        fetch('ajax/check_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.count > 0) {
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    badge.textContent = data.count;
                    badge.style.display = 'flex';
                }
            }
        })
        .catch(error => console.error('Error checking notifications:', error));
    }
}

// Check notifications every 30 seconds
setInterval(checkNotifications, 30000);

// Initialize notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    checkNotifications();
});