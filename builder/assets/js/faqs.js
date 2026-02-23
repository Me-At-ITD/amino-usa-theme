jQuery(document).ready(function($) {
  // ✅ Close all accordions on page load
  $('.lb-accordion-content').hide();
  $('.lb-accordion-toggle').text('+');
  $('.lb-accordion-item').removeClass('active');

  // Accordion click handler
  $(document).on('click', '.lb-accordion-header', function() {
    const $accordion = $(this).closest('.lb-accordion-item');
    const $content = $accordion.find('.lb-accordion-content');
    const $toggle = $(this).find('.lb-accordion-toggle');
    
    // Close all other accordions
    $accordion.siblings('.lb-accordion-item').removeClass('active')
      .find('.lb-accordion-content').slideUp(300)
      .end().find('.lb-accordion-toggle').text('+');
    
    // Toggle current accordion
    if ($accordion.hasClass('active')) {
      $content.slideUp(300);
      $toggle.text('+');
      $accordion.removeClass('active');
    } else {
      $content.slideDown(300);
      $toggle.text('-');
      $accordion.addClass('active');
    }
  });
});
