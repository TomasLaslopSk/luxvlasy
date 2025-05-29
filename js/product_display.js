document.addEventListener('DOMContentLoaded', () => {
    const productListContainer = document.getElementById('product-list');
    const filterButtonsContainer = document.querySelector('.filter-buttons-container'); // Get the filter buttons container

    // Define your project's base path. This is crucial for consistent asset loading.
    // Example: For http://localhost:8888/luxvlasy_mamp/, the basePath should be '/luxvlasy_mamp/'.
    // If your project was directly in htdocs (e.g., http://localhost:8888/), it would be '/'.
    const basePath = '/luxvlasy_mamp/';

    if (!productListContainer) {
        console.warn("Element with ID 'product-list' not found. Product display script will not run.");
        return;
    }

    // Initialize cart count on page load
    if (typeof window.updateCartCount === 'function') {
        // Use basePath for php/cart_handler.php
        fetch(`${basePath}php/cart_handler.php?action=get_count`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.updateCartCount(data.count);
                }
            })
            .catch(error => console.error('Error fetching initial cart count in product_display.js:', error));
    }

    /**
     * Fetches product data from the server and displays it on the page.
     * @param {string} filterType The type of filter (e.g., 'all', 'discount', 'brand', 'category').
     * @param {string} filterValue The value to filter by (e.g., 'Balmain', 'Šampóny').
     */
    async function fetchAndDisplayProducts(filterType = 'all', filterValue = '') {
        productListContainer.innerHTML = '<p class="loading-message">Načítavam produkty...</p>'; // Show loading message

        try {
            // Construct the URL with appropriate filter parameters. Use basePath for get_products.php.
            const url = `${basePath}php/get_products.php?filter_type=${filterType}&filter_value=${encodeURIComponent(filterValue)}`;
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const products = await response.json();

            productListContainer.innerHTML = ''; // Clear loading message

            if (Array.isArray(products) && products.length > 0) {
                products.forEach(product => {
                    const productCard = document.createElement('div');
                    productCard.className = 'product-card';

                    const originalPrice = parseFloat(product.price);
                    const discountValue = parseFloat(product.discount) || 0;
                    const hasMeaningfulDiscount = discountValue > 0 && discountValue < 1;

                    let priceHtmlContent;
                    let priceForButton = originalPrice;

                    if (hasMeaningfulDiscount) {
                        const discountedPrice = originalPrice * (1 - discountValue);
                        priceForButton = discountedPrice;
                        priceHtmlContent = `
                            <span class="original-price">€${originalPrice.toFixed(2).replace('.', ',')}</span>
                            <span class="discounted-price">€${discountedPrice.toFixed(2).replace('.', ',')}</span>
                        `;
                    } else {
                        priceHtmlContent = `
                            <span class="single-price">€${originalPrice.toFixed(2).replace('.', ',')}</span>
                        `;
                    }

                    const productImage = product.image || 'placeholder.jpg';
                    const productHoverImage = product.hover_image || productImage;

                    productCard.innerHTML = `
                        <img
                            src="${basePath}images/${productImage}"
                            alt="${product.name || 'Product'}"
                            data-original="${basePath}images/${productImage}"
                            data-hover="${basePath}images/${productHoverImage}"
                            class="swap-image"
                        >
                        <p class="product-brand-name">${product.brand || 'Neznáma značka'}</p>
                        <a href="${basePath}product_detail.php?id=${product.id}" class="product-title-name">${product.name || 'Neznámy produkt'}</a>
                        ${product.product_category ? `<p class="product_category">${product.product_category}</p>` : ''}
                        <p class="product-short-description">${product.short_description || ''}</p>
                        <div class="product-price-display">
                            ${priceHtmlContent}
                        </div>
                        <button class="add-to-cart-btn button primary-button"
                                data-product-id="${product.id}"
                                data-product-name="${product.name}"
                                data-product-price="${priceForButton.toFixed(2)}"
                                data-product-image="${productImage}">
                            <i class="fas fa-shopping-bag"></i> Do košíka
                        </button>
                    `;
                    productListContainer.appendChild(productCard);
                });

                document.querySelectorAll('.swap-image').forEach(img => {
                    img.addEventListener('mouseenter', () => {
                        const hoverSrc = img.getAttribute('data-hover');
                        if (hoverSrc) img.src = hoverSrc;
                    });
                    img.addEventListener('mouseleave', () => {
                        const originalSrc = img.getAttribute('data-original');
                        if (originalSrc) img.src = originalSrc;
                    });
                });

                document.querySelectorAll('.add-to-cart-btn').forEach(button => {
                    button.addEventListener('click', addToCart);
                });

            } else {
                productListContainer.innerHTML = '<p class="info-message">Produkty sa nenašli alebo sa nepodarilo načítať.</p>';
                console.warn('No products found or data array is empty:', products);
            }
        } catch (error) {
            productListContainer.innerHTML = '<p class="error-message">Chyba pri načítaní produktov. Skúste to prosím neskôr.</p>';
            console.error('There was a problem with the fetch operation:', error);
        }
    }

    /**
     * Handles adding a product to the cart via AJAX.
     * @param {Event} event The click event.
     */
    async function addToCart(event) {
        const button = event.currentTarget;
        const productId = button.dataset.productId;
        const productName = button.dataset.productName;
        const productPrice = button.dataset.productPrice;
        const productImage = button.dataset.productImage;

        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('quantity', 1);
        formData.append('product_name', productName);
        formData.append('product_price', productPrice);
        formData.append('product_image', productImage);

        try {
            // Use basePath for php/cart_handler.php
            const response = await fetch(`${basePath}php/cart_handler.php`, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                alert(data.message);
                if (typeof window.updateCartCount === 'function') {
                    window.updateCartCount(data.newCartCount);
                } else {
                    console.warn('window.updateCartCount function not found. Cart badge might not update.');
                }
            } else {
                alert('Chyba: ' + data.message);
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            alert('Chyba pri pridávaní do košíka.');
        }
    }

    // Event listeners for filter buttons (assuming these are internal filter buttons on the products page)
    if (filterButtonsContainer) {
        filterButtonsContainer.addEventListener('click', (event) => {
            if (event.target.classList.contains('filter-btn')) {
                // Clear all active classes
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelector('.main-nav .nav-item a[href*="filter=discount"]')?.classList.remove('active');
                // Remove active class from any other category links in the main nav that might be active
                document.querySelectorAll('.main-nav .nav-item a[href*="category="]').forEach(link => {
                    link.classList.remove('active');
                });

                // Add active class to the clicked button
                event.target.classList.add('active');

                const filterValue = event.target.dataset.category;
                const filterTypeFromData = event.target.dataset.filterType;

                let filterTypeToSend = 'brand'; // Default for existing brand buttons
                if (filterValue === 'all') {
                    filterTypeToSend = 'all';
                } else if (filterValue === 'discount') {
                    filterTypeToSend = 'discount';
                } else if (filterTypeFromData === 'category') {
                    filterTypeToSend = 'category';
                }

                fetchAndDisplayProducts(filterTypeToSend, filterValue);

                // Update URL to reflect the new filter state if desired, or clear it
                // For filter buttons on the page, clearing is often desired for cleaner UX
                history.pushState(null, '', location.pathname);
            }
        });
    }

    // --- REVISED LOGIC FOR HANDLING URL PARAMETERS ON PAGE LOAD ---
    const urlParams = new URLSearchParams(window.location.search);
    const filterParam = urlParams.get('filter'); // e.g., 'discount'
    const categoryParam = urlParams.get('category'); // e.g., 'Luxusné doplnky'

    let initialFilterType = 'all';
    let initialFilterValue = '';

    // Clear all active states initially
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.main-nav .nav-item a').forEach(link => link.classList.remove('active'));


    if (filterParam === 'discount') {
        initialFilterType = 'discount';
        initialFilterValue = ''; // No specific value for discount filter
        const akcieLink = document.querySelector(`.main-nav .nav-item a[href="${basePath}products.php?filter=discount"]`);
        if (akcieLink) {
            akcieLink.classList.add('active');
        }
    } else if (categoryParam) { // Check for category parameter first
        initialFilterType = 'category';
        initialFilterValue = categoryParam;
        // Try to activate the corresponding category link in the main nav
        const categoryLink = document.querySelector(`.main-nav .nav-item a[href="${basePath}products.php?category=${encodeURIComponent(categoryParam)}"]`);
        if (categoryLink) {
            categoryLink.classList.add('active');
        }
        // Also activate corresponding filter button if it exists (e.g., a "Luxusné doplnky" button)
        const categoryFilterButton = document.querySelector(`.filter-btn[data-filter-type="category"][data-category="${categoryParam}"]`);
        if (categoryFilterButton) {
            categoryFilterButton.classList.add('active');
        }
    } else {
        // Default: show all products, activate the 'Všetko' button if it exists
        initialFilterType = 'all';
        const allButton = document.querySelector('.filter-btn[data-category="all"]');
        if (allButton) {
            allButton.classList.add('active');
        }
    }

    // Call the function to fetch and display products initially based on URL or default
    fetchAndDisplayProducts(initialFilterType, initialFilterValue);


    // Existing promo slider and dots logic (unchanged)
    const promoSlider = document.getElementById('promoSlider');
    const sliderItems = document.querySelectorAll('.slider-item');
    const sliderPrev = document.querySelector('.slider-prev');
    const sliderNext = document.querySelector('.slider-next');
    const sliderDotsContainer = document.querySelector('.slider-dots');
    let currentSlide = 0;

    // Create dots
    if (sliderItems.length > 0 && sliderDotsContainer) { // Ensure slider elements exist
        sliderItems.forEach((_, index) => {
            const dot = document.createElement('span');
            dot.classList.add('dot');
            if (index === 0) dot.classList.add('active');
            dot.dataset.slideIndex = index; // Add this line if not already present
            sliderDotsContainer.appendChild(dot);
        });
    }
    const sliderDots = document.querySelectorAll('.dot'); // Re-select after creation

    const showSlide = (index) => {
        if (sliderItems.length === 0) return; // Prevent error if no slides

        sliderItems.forEach((item, i) => {
            item.classList.remove('active');
            if (sliderDots[i]) { // Check if dot exists
                sliderDots[i].classList.remove('active');
            }
            if (i === index) {
                item.classList.add('active');
                if (sliderDots[i]) { // Check if dot exists
                    sliderDots[i].classList.add('active');
                }
            }
        });
    };

    if (sliderPrev && sliderNext) { // Ensure navigation buttons exist
        sliderPrev.addEventListener('click', () => {
            currentSlide = (currentSlide - 1 + sliderItems.length) % sliderItems.length;
            showSlide(currentSlide);
        });

        sliderNext.addEventListener('click', () => {
            currentSlide = (currentSlide + 1) % sliderItems.length;
            showSlide(currentSlide);
        });
    }

    if (sliderDots.length > 0) { // Ensure dots exist before adding listeners
        sliderDots.forEach(dot => {
            dot.addEventListener('click', (e) => {
                currentSlide = parseInt(e.target.dataset.slideIndex);
                showSlide(currentSlide);
            });
        });
    }


    // Automatic slide change
    let autoSlideInterval;
    if (sliderItems.length > 1) { // Only auto-slide if there's more than one slide
        autoSlideInterval = setInterval(() => {
            currentSlide = (currentSlide + 1) % sliderItems.length;
            showSlide(currentSlide);
        }, 5000); // Change slide every 5 seconds
    }

    // Optional: Stop interval if page is hidden
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            clearInterval(autoSlideInterval);
        } else if (sliderItems.length > 1) {
            autoSlideInterval = setInterval(() => {
                currentSlide = (currentSlide + 1) % sliderItems.length;
                showSlide(currentSlide);
            }, 5000);
        }
    });

});