// js/main.js

document.addEventListener('DOMContentLoaded', () => {
    // --- Dropdown Menu Logic ---
    const dropdownToggle = document.querySelector('.dropdown-toggle');
    const dropdownMenu = document.querySelector('.dropdown-menu');

    if (dropdownToggle && dropdownMenu) {
        // For hover effect on desktop
        dropdownToggle.parentElement.addEventListener('mouseenter', () => {
            dropdownMenu.style.display = 'block';
            setTimeout(() => { // Trigger transition after display block
                dropdownMenu.style.opacity = '1';
                dropdownMenu.style.transform = 'translateY(0)';
            }, 10); // Small delay to allow display property to apply
        });

        dropdownToggle.parentElement.addEventListener('mouseleave', () => {
            dropdownMenu.style.opacity = '0';
            dropdownMenu.style.transform = 'translateY(10px)';
            // Hide after transition
            setTimeout(() => {
                dropdownMenu.style.display = 'none';
            }, 300); // Match transition duration
        });

        // For click on mobile (if media query hides hover effect)
        // This part would typically be more complex with a hamburger menu.
        // For now, it just ensures the dropdown is functional if the hover doesn't work.
        dropdownToggle.addEventListener('click', (event) => {
            // Prevent default link action
            event.preventDefault();

            // Toggle active class on parent nav-item
            dropdownToggle.parentElement.classList.toggle('active');

            if (dropdownMenu.style.display === 'block') {
                dropdownMenu.style.opacity = '0';
                dropdownMenu.style.transform = 'translateY(10px)';
                setTimeout(() => {
                    dropdownMenu.style.display = 'none';
                }, 300);
            } else {
                dropdownMenu.style.display = 'block';
                setTimeout(() => {
                    dropdownMenu.style.opacity = '1';
                    dropdownMenu.style.transform = 'translateY(0)';
                }, 10);
            }
        });
    }

    // --- Image Swap on Product Cards (if applicable on this page) ---
    const swapImages = document.querySelectorAll('.swap-image');
    swapImages.forEach(img => {
      img.addEventListener('mouseenter', () => {
        const hoverSrc = img.getAttribute('data-hover');
        if (hoverSrc) img.src = hoverSrc;
      });
      img.addEventListener('mouseleave', () => {
        const originalSrc = img.getAttribute('data-original');
        if (originalSrc) img.src = originalSrc;
      });
    });


    // --- Promo Slider Logic ---
    const slider = document.getElementById('promoSlider');
    // Ensure slider and sliderItems exist before trying to query them
    if (slider) { // Check if the slider container actually exists on the page
        const sliderItems = Array.from(slider.querySelectorAll('.slider-item'));
        const prevButton = document.querySelector('.slider-prev');
        const nextButton = document.querySelector('.slider-next');
        const dotsContainer = document.querySelector('.slider-dots');

        let currentSlide = 0;
        let autoSlideInterval; // For auto-play

        // Function to show a specific slide
        function showSlide(index) {
            // Hide all slides
            sliderItems.forEach(item => item.classList.remove('active'));
            // Show the current slide
            if (sliderItems[index]) { // Ensure the slide exists
                sliderItems[index].classList.add('active');
            }

            // Update active dot
            updateDots(index);
        }

        // Function to generate and update slider dots
        function updateDots(activeIndex) {
            if (!dotsContainer) return; // Ensure dots container exists

            dotsContainer.innerHTML = ''; // Clear existing dots
            sliderItems.forEach((_, index) => {
                const dot = document.createElement('span');
                dot.classList.add('dot');
                if (index === activeIndex) {
                    dot.classList.add('active');
                }
                dot.addEventListener('click', () => {
                    currentSlide = index;
                    showSlide(currentSlide);
                    resetAutoSlide(); // Reset auto-play when a dot is clicked
                });
                dotsContainer.appendChild(dot);
            });
        }

        // Go to next slide
        function nextSlide() {
            currentSlide = (currentSlide + 1) % sliderItems.length;
            showSlide(currentSlide);
        }

        // Go to previous slide
        function prevSlide() {
            currentSlide = (currentSlide - 1 + sliderItems.length) % sliderItems.length;
            showSlide(currentSlide);
        }

        // Auto-play functionality
        function startAutoSlide() {
            // Clear any existing interval to prevent duplicates
            if (autoSlideInterval) {
                clearInterval(autoSlideInterval);
            }
            autoSlideInterval = setInterval(nextSlide, 5000); // Change slide every 5 seconds
        }

        function resetAutoSlide() {
            clearInterval(autoSlideInterval);
            startAutoSlide();
        }

        // Event Listeners for buttons
        if (prevButton) {
            prevButton.addEventListener('click', () => {
                prevSlide();
                resetAutoSlide(); // Reset auto-play on manual navigation
            });
        }

        if (nextButton) {
            nextButton.addEventListener('click', () => {
                nextSlide();
                resetAutoSlide(); // Reset auto-play on manual navigation
            });
        }

        // Initialize the slider if it exists and has items
        if (sliderItems.length > 0) {
            showSlide(currentSlide); // Show the first slide initially
            startAutoSlide(); // Start auto-play
        }
    } // End of if(slider) check
});