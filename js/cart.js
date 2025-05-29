// Define the base path for consistent URL generation.
// This must match your MAMP setup, e.g., '/luxvlasy_mamp/' if your project is in /htdocs/luxvlasy_mamp/
const basePath = '/luxvlasy_mamp/'; // KEEP THIS EXACTLY AS IS

// Global function to update the cart count badge in the header
window.updateCartCount = function(count) {
    const cartCountElement = document.getElementById('cart-item-count');
    if (cartCountElement) {
        cartCountElement.textContent = count;
        // Optionally add a subtle animation for update
        cartCountElement.classList.add('bouncing');
        setTimeout(() => {
            cartCountElement.classList.remove('bouncing');
        }, 300);
    }
};

// Function to fetch and display cart items (used on cart.php)
window.fetchCartItemsAndDisplay = async function() {
    const cartItemsContainer = document.getElementById('cart-items-container');
    const cartSummaryTotal = document.getElementById('cart-summary-total');
    const checkoutBtn = document.getElementById('checkout-btn');

    // ONLY proceed if on the cart page (these elements exist)
    if (!cartItemsContainer || !cartSummaryTotal) {
        // This means we are not on the cart.php page or essential elements are missing.
        // It's normal if cart.js is included on other pages (like index.php).
        return;
    }

    try {
        const response = await fetch(`${basePath}php/cart_handler.php?action=get_items`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();

        if (data.success && data.items.length > 0) {
            cartItemsContainer.innerHTML = ''; // Clear previous items
            data.items.forEach(item => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'cart-item';
                itemDiv.dataset.productId = item.product_id; // Add data-product-id

                // Format price and subtotal for display with comma as decimal
                const formattedPrice = parseFloat(item.price).toFixed(2).replace('.', ',');
                const formattedSubtotal = parseFloat(item.subtotal).toFixed(2).replace('.', ',');

                itemDiv.innerHTML = `
                    <img src="${basePath}images/${item.image}" alt="${item.name}"> <h4>${item.name}</h4>
                    <p class="price">€${formattedPrice}</p>
                    <input type="number" class="item-quantity" value="${item.quantity}" min="1">
                    <p class="subtotal">Spolu: €<span class="subtotal-value">${formattedSubtotal}</span></p>
                    <button class="remove-from-cart-btn button secondary-button" data-product-id="${item.product_id}">
                        <i class="fas fa-trash-alt"></i> Odstrániť
                    </button>
                `;
                cartItemsContainer.appendChild(itemDiv);
            });

            // Format total for display with comma as decimal
            cartSummaryTotal.textContent = `€${parseFloat(data.total).toFixed(2).replace('.', ',')}`;
            checkoutBtn.disabled = false; // Enable checkout button

            // Attach event listeners for quantity and remove buttons
            document.querySelectorAll('.item-quantity').forEach(input => {
                input.addEventListener('change', async (event) => {
                    const productId = event.target.closest('.cart-item').dataset.productId;
                    const newQuantity = parseInt(event.target.value);
                    if (!isNaN(newQuantity) && newQuantity >= 1) {
                        await window.updateCartItemQuantity(productId, newQuantity);
                    } else if (newQuantity === 0) { // If quantity is reduced to 0, remove it
                        await window.removeFromCart(productId);
                    }
                });
            });

            document.querySelectorAll('.remove-from-cart-btn').forEach(button => {
                button.addEventListener('click', async (event) => {
                    const productId = event.target.dataset.productId || event.target.closest('button').dataset.productId;
                    if (confirm('Naozaj chcete odstrániť túto položku z košíka?')) {
                        await window.removeFromCart(productId);
                    }
                });
            });

        } else {
            cartItemsContainer.innerHTML = '<p class="empty-cart-message">Váš košík je prázdny.</p>';
            cartSummaryTotal.textContent = '€0,00';
            checkoutBtn.disabled = true; // Disable checkout button if cart is empty
        }
        // This line is important here to update the header count after any cart page specific action
        window.updateCartCount(data.newCartCount);
    } catch (error) {
        // In a production environment, you might log this to a server-side error tracker
        // console.error('Error fetching cart items:', error); // Removed for production
        cartItemsContainer.innerHTML = '<p class="error-message">Chyba pri načítaní košíka. Skúste to prosím neskôr.</p>';
        cartSummaryTotal.textContent = '€0,00';
        checkoutBtn.disabled = true;
    }
};

// Function to update item quantity in cart
window.updateCartItemQuantity = async function(productId, quantity) {
    const formData = new FormData();
    formData.append('action', 'update_quantity');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    try {
        const response = await fetch(`${basePath}php/cart_handler.php`, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (!data.success) {
            alert('Error updating quantity: ' + data.message);
        }
        await window.fetchCartItemsAndDisplay(); // Re-fetch to update display and totals
    } catch (error) {
        // console.error('Error updating quantity:', error); // Removed for production
        alert('Chyba pri aktualizácii množstva.');
    }
};

// Function to remove item from cart
window.removeFromCart = async function(productId) {
    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('product_id', productId);

    try {
        const response = await fetch(`${basePath}php/cart_handler.php`, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (!data.success) {
            alert('Error removing item: ' + data.message);
        }
        await window.fetchCartItemsAndDisplay(); // Re-fetch to update display and totals
    } catch (error) {
        // console.error('Error removing item:', error); // Removed for production
        alert('Chyba pri odstraňovaní položky z košíka.');
    }
};


// --- Multi-step Checkout Logic ---
// ONLY EXECUTE THIS BLOCK IF ON THE CART/CHECKOUT PAGE
const checkoutProcessContainer = document.querySelector('.checkout-process-container');
if (checkoutProcessContainer) { // This condition ensures the code only runs on cart.php
    document.addEventListener('DOMContentLoaded', () => {
        // Remove temporary CSS style injection for bouncing animation
        // It's assumed the animation CSS is now in style.css

        const checkoutSteps = document.querySelectorAll('.checkout-step');
        const stepIndicators = document.querySelectorAll('.step-label');
        const checkoutBtn = document.getElementById('checkout-btn'); // From Step 1 (Cart Summary)
        const continueToInfoBtn = document.querySelector('.continue-to-info-btn'); // From Step 2
        const placeOrderBtn = document.querySelector('.place-order-btn'); // From Step 3

        const backToCartBtn = document.querySelector('.back-to-cart-btn'); // From Step 2
        const backToShippingBtn = document.querySelector('.back-to-shipping-btn'); // From Step 3

        let currentCheckoutStep = 1; // Start at the first step

        function showCheckoutStep(stepNumber) {
            checkoutSteps.forEach((step, index) => {
                if (index + 1 === stepNumber) {
                    step.classList.add('active');
                } else {
                    step.classList.remove('active');
                }
            });

            if (stepIndicators.length > 0) {
                stepIndicators.forEach(indicator => indicator.classList.remove('active', 'completed'));
                for (let i = 0; i < stepNumber; i++) {
                    if (stepIndicators[i]) {
                        stepIndicators[i].classList.add('active');
                        if (i < stepNumber -1) {
                            stepIndicators[i].classList.add('completed');
                        }
                    }
                }
            }
            currentCheckoutStep = stepNumber;
        }

        // Event listener for "Pokračovať k platbe" button (from cart summary)
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', () => {
                showCheckoutStep(2); // Move to Step 2: Shipping & Payment
                fetchShippingPaymentOptions(); // Fetch options when moving to step 2
            });
        }

        // Event listener for "Pokračovať na Informácie o Vás" button (from Shipping & Payment)
        if (continueToInfoBtn) {
            continueToInfoBtn.addEventListener('click', (event) => {
                event.preventDefault();
                const countrySelect = document.getElementById('country');
                const currencySelect = document.getElementById('currency');

                const selectedShippingRadio = document.querySelector('input[name="shipping_method"]:checked');
                const selectedPaymentRadio = document.querySelector('input[name="payment_method"]:checked');

                if (!countrySelect || !countrySelect.value || !currencySelect || !currencySelect.value) {
                    alert('Prosím, vyberte krajinu a menu.');
                    return;
                }

                if (!selectedShippingRadio) {
                    alert('Prosím, vyberte metódu dopravy.');
                    return;
                }
                if (!selectedPaymentRadio) {
                    alert('Prosím, vyberte metódu platby.');
                    return;
                }

                showCheckoutStep(3); // Move to Step 3: User Information
            });
        }

        // Event listener for "Dokončiť objednávku" button (from User Information)
        const actualPlaceOrderBtn = document.querySelector('.place-order-btn');
        if (actualPlaceOrderBtn) {
            actualPlaceOrderBtn.addEventListener('click', async (event) => {
                event.preventDefault(); // This is essential to stop the browser's default form submission

                const activeStep = document.querySelector('.checkout-steps__step.active');
                if (activeStep && activeStep.id !== 'checkout-step-3') {
                    alert('Prosím, prejdite na krok 3 pre dokončenie objednávky.');
                    return;
                }

                const formData = new FormData();

                const firstNameEl = document.getElementById('first_name');
                if (!firstNameEl) { alert("Missing first name field!"); return; }
                formData.append('first_name', firstNameEl.value);

                const lastNameEl = document.getElementById('last_name');
                if (!lastNameEl) { alert("Missing last name field!"); return; }
                formData.append('last_name', lastNameEl.value);

                const emailEl = document.getElementById('email');
                if (!emailEl) { alert("Missing email field!"); return; }
                formData.append('email', emailEl.value);

                const phoneEl = document.getElementById('phone');
                if (!phoneEl) { alert("Missing phone field!"); return; }
                formData.append('phone', phoneEl.value);

                const streetEl = document.getElementById('street');
                if (!streetEl) { alert("Missing street field!"); return; }
                formData.append('street', streetEl.value);

                const houseNumberEl = document.getElementById('house_number');
                if (!houseNumberEl) { alert("Missing house number field!"); return; }
                formData.append('house_number', houseNumberEl.value);

                const cityEl = document.getElementById('city');
                if (!cityEl) { alert("Missing city field!"); return; }
                formData.append('city', cityEl.value);

                const postalCodeEl = document.getElementById('postal_code');
                if (!postalCodeEl) { alert("Missing postal code field!"); return; }
                formData.append('postal_code', postalCodeEl.value);

                const addressCountryEl = document.getElementById('address_country');
                if (!addressCountryEl) { alert("Missing address country field!"); return; }
                formData.append('address_country', addressCountryEl.value);

                const selectedShippingRadio = document.querySelector('input[name="shipping_method"]:checked');
                if (selectedShippingRadio) {
                    formData.append('shipping_method_id', selectedShippingRadio.value);
                } else {
                    alert('Prosím, vyberte metódu dopravy.');
                    return;
                }

                const selectedPaymentRadio = document.querySelector('input[name="payment_method"]:checked');
                if (selectedPaymentRadio) {
                    formData.append('payment_method_id', selectedPaymentRadio.value);
                } else {
                    alert('Prosím, vyberte metódu platby.');
                    return;
                }

                try {
                    const response = await fetch('php/process_order.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Redirect to confirmation page with order ID
                        window.location.href = `${basePath}order_confirmation.php?order_id=${result.order_id}`;
                    } else {
                        alert('Chyba pri objednávke: ' + result.message);
                    }
                } catch (error) {
                    // console.error('Error in fetch or JSON parsing:', error); // Removed for production
                    alert('Došlo k chybe pri komunikácii so serverom alebo spracovaní odpovede.');
                }
            });
        }

        // Event listener for "Späť" buttons
        if (backToCartBtn) {
            backToCartBtn.addEventListener('click', () => {
                showCheckoutStep(1);
            });
        }
        if (backToShippingBtn) {
            backToShippingBtn.addEventListener('click', () => {
                showCheckoutStep(2);
            });
        }

        // Initial display of the first step when the page loads
        showCheckoutStep(1);

        if (typeof window.fetchCartItemsAndDisplay === 'function') {
            window.fetchCartItemsAndDisplay();
        }

        // --- Dynamic Loading for Shipping & Payment Options (Step 2) ---
        const countrySelect = document.getElementById('country');
        const currencySelect = document.getElementById('currency');
        const shippingOptionsDiv = document.getElementById('shipping-options');
        const paymentOptionsDiv = document.getElementById('payment-options');
        const noShippingError = document.getElementById('no-shipping-error');
        const noPaymentError = document.getElementById('no-payment-error');


        async function fetchShippingPaymentOptions() {
            if (!countrySelect || !currencySelect || !shippingOptionsDiv || !paymentOptionsDiv) {
                // console.warn("Missing elements for shipping/payment options. Skipping fetch."); // Removed for production
                return;
            }

            const previouslySelectedShippingId = document.querySelector('input[name="shipping_method"]:checked')?.value;
            const previouslySelectedPaymentId = document.querySelector('input[name="payment_method"]:checked')?.value;

            const country = countrySelect.value;
            const currency = currencySelect.value;

            shippingOptionsDiv.innerHTML = '<p class="info-message">Načítavam možnosti dopravy...</p>';
            paymentOptionsDiv.innerHTML = '<p class="info-message">Načítavam možnosti platby...</p>';
            noShippingError.classList.add('hidden');
            noPaymentError.classList.add('hidden');
            if (continueToInfoBtn) continueToInfoBtn.disabled = true;

            try {
                const response = await fetch(`${basePath}php/get_shipping_payment_options.php?country=${country}&currency=${currency}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();

                // Render Shipping Options
                shippingOptionsDiv.innerHTML = '';
                if (data.shipping_methods && data.shipping_methods.length > 0) {
                    data.shipping_methods.forEach(method => {
                        const isChecked = (previouslySelectedShippingId && previouslySelectedShippingId === String(method.id)) ? 'checked' : '';
                        const methodHtml = `
                            <div class="shipping-option">
                                <input type="radio" id="shipping_${method.id}" name="shipping_method" value="${method.id}" ${isChecked} required>
                                <label for="shipping_${method.id}">${method.name} - €${parseFloat(method.price).toFixed(2).replace('.', ',')}</label>
                            </div>
                        `;
                        shippingOptionsDiv.insertAdjacentHTML('beforeend', methodHtml);
                    });
                } else {
                    shippingOptionsDiv.innerHTML = '';
                    noShippingError.classList.remove('hidden');
                }

                // Render Payment Options
                paymentOptionsDiv.innerHTML = '';
                if (data.payment_methods && data.payment_methods.length > 0) {
                    data.payment_methods.forEach(method => {
                        const isChecked = (previouslySelectedPaymentId && previouslySelectedPaymentId === String(method.id)) ? 'checked' : '';
                        const methodHtml = `
                            <div class="payment-option">
                                <input type="radio" id="payment_${method.id}" name="payment_method" value="${method.id}" ${isChecked} required>
                                <label for="payment_${method.id}">${method.name}</label>
                            </div>
                        `;
                        paymentOptionsDiv.insertAdjacentHTML('beforeend', methodHtml);
                    });
                } else {
                    paymentOptionsDiv.innerHTML = '';
                    noPaymentError.classList.remove('hidden');
                }

                if (continueToInfoBtn) {
                    if (data.shipping_methods.length > 0 && data.payment_methods.length > 0) {
                        continueToInfoBtn.disabled = false;
                    } else {
                        continueToInfoBtn.disabled = true;
                    }
                }

            } catch (error) {
                // console.error('Error fetching shipping/payment options:', error); // Removed for production
                if (shippingOptionsDiv) shippingOptionsDiv.innerHTML = '<p class="error-message">Chyba pri načítaní možností dopravy.</p>';
                if (paymentOptionsDiv) paymentOptionsDiv.innerHTML = '<p class="error-message">Chyba pri načítaní možností platby.</p>';
                if (continueToInfoBtn) continueToInfoBtn.disabled = true;
            }
        }

        // Event listeners for country/currency changes to re-fetch options
        if (countrySelect) countrySelect.addEventListener('change', fetchShippingPaymentOptions);
        if (currencySelect) currencySelect.addEventListener('change', fetchShippingPaymentOptions);

        // --- IMPORTANT: Browser Cache (pageshow event) ---
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                // console.log("Page restored from BFCache. Re-fetching cart count and items."); // Removed for production
                fetch(`${basePath}php/cart_handler.php?action=get_count`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.updateCartCount(data.count);
                        } else {
                            window.updateCartCount(0);
                        }
                    })
                    .catch(error => {
                        // console.error('Error fetching cart count on pageshow:', error); // Removed for production
                        window.updateCartCount(0);
                    });

                if (currentCheckoutStep === 1) {
                    window.fetchCartItemsAndDisplay();
                }
                if (currentCheckoutStep === 2) {
                    fetchShippingPaymentOptions();
                }
            }
        });
    });
}