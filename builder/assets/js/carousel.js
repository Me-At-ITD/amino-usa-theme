// Function to initialize product carousel
function initProductCarousel() {
    if (typeof $.fn.owlCarousel !== 'undefined') {
        $('.lb-product-carousel').owlCarousel({
            items: 4,
            loop: false,
            dots: true,
            nav: false,
            margin: 5,
            smartSpeed: 600,
            autoplay: true,
            autoplayTimeout: 4000,
            autoplayHoverPause: true,
            responsive: {
                0: {
                    items: 1
                },
                600: {
                    items: 2
                },
                1000: {
                    items: 4
                }
            }
        });
    } else {
        console.warn('Owl Carousel not loaded');
    }
}

// Initialize carousel on document ready
$(document).ready(function() {
    initProductCarousel();
});