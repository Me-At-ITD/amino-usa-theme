jQuery(function($){

  // REST API endpoint configuration

// Replace the REST_ROOT line with this more robust version

const REST_ROOT = (function() {

  if (window.wpApiSettings && wpApiSettings.root) {

    return wpApiSettings.root;

  }

  if (window.LB && LB.rest) {

    return LB.rest;

  }

  // Get the site URL dynamically

  const siteUrl = window.location.origin;

  return siteUrl + '/wp-json/';

})();

console.log('REST API endpoint:', REST_ROOT);

  console.log('Lightweight Builder initialized');

  

  // DOM elements for the builder interface

  const $canvas = $('#lb-canvas');

  const $name   = $('#lb-template-name');

  const $list   = $('#lb-templates');

  let currentTemplateId = 0;

  let LB_CURRENT_TARGET = null; // Stores the currently edited element



  // ---- Helper Functions ----



  /**

   * Creates control buttons for blocks (up, down, delete)

   * @returns {jQuery} The control buttons element

   */

  function blockControls(){

    return $('<div class="lb-tools" />')

      .append('<button type="button" class="lb-up" title="Move up">↑</button>')

      .append('<button type="button" class="lb-down" title="Move down">↓</button>')

      .append('<button type="button" class="lb-del" title="Delete">✕</button>');

  }



  /**

   * Creates a new block element with controls and content area

   * @param {string} cls - CSS class for the block

   * @param {boolean} editable - Whether the content is editable

   * @param {string} innerHtml - Initial HTML content

   * @returns {jQuery} The created block element

   */

  function makeBlock(cls, editable=false, innerHtml=''){

    const $b = $('<div class="lb-block" />');

    $b.append(blockControls());

    const $inner = $('<div />').addClass(cls).attr('contenteditable', 'false').html(innerHtml);

    $b.append($inner);

    addPencils($inner.get(0)); // Add edit pencils to the content

    return $b;

  }



  /**

   * Initializes accordion functionality for FAQ sections

   */

  function initAccordions() {

    // Accordion toggle functionality

    $(document).on('click', '.lb-accordion-header', function() {

      const $accordion = $(this).closest('.lb-accordion-item');

      const $content = $accordion.find('.lb-accordion-content');

      const $toggle = $(this).find('.lb-accordion-toggle');

      

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

    

    // Add new accordion item

    $(document).on('click', '.lb-add-accordion', function() {

      const $container = $(this).closest('.lb-faqs-section').find('.lb-accordions-container');

      const accordionCount = $container.find('.lb-accordion-item').length + 1;

      

      const newAccordion = $(

        '<div class="lb-accordion-item">' +

          '<div class="lb-accordion-header">' +

            '<h3 class="lb-accordion-title" data-edit="text">Question ' + accordionCount + '</h3>' +

            '<span class="lb-accordion-toggle">+</span>' +

          '</div>' +

          '<div class="lb-accordion-content" style="display: none;">' +

            '<div class="lb-accordion-answer" data-edit="text">Answer to question ' + accordionCount + ' goes here...</div>' +

          '</div>' +

          '<div class="lb-accordion-controls lb-builder-only">' +

            '<button type="button" class="button lb-accordion-up" title="Move Up">↑</button>' +

            '<button type="button" class="button lb-accordion-down" title="Move Down">↓</button>' +

            '<button type="button" class="button lb-accordion-remove" title="Remove">×</button>' +

          '</div>' +

        '</div>'

      );

      

      $container.append(newAccordion);

      addPencils(newAccordion[0]); // Add edit pencils to the new accordion

    });

    

    // Accordion controls (move up, move down, remove)

    $(document).on('click', '.lb-accordion-up', function(e) {

      e.stopPropagation();

      e.preventDefault();

      const $accordion = $(this).closest('.lb-accordion-item');

      const $prev = $accordion.prev('.lb-accordion-item');

      

      if ($prev.length) {

        $accordion.insertBefore($prev);

      }

    });

    

    $(document).on('click', '.lb-accordion-down', function(e) {

      e.stopPropagation();

      e.preventDefault();

      const $accordion = $(this).closest('.lb-accordion-item');

      const $next = $accordion.next('.lb-accordion-item');

      

      if ($next.length) {

        $accordion.insertAfter($next);

      }

    });

    

    $(document).on('click', '.lb-accordion-remove', function(e) {

      e.stopPropagation();

      e.preventDefault();

      const $accordion = $(this).closest('.lb-accordion-item');

      

      if ($accordion.siblings('.lb-accordion-item').length > 0) {

        $accordion.remove();

      } else {

        alert('You need to have at least one accordion item.');

      }

    });

  }



  /**

   * Creates a new section in the canvas

   * @param {string} sectionHTML - HTML content for the section

   */

  function makeSection(sectionHTML){

    console.log('makeSection called');

    const canvas = document.querySelector('#lb-canvas');

    if (!canvas) {

      console.error('Canvas not found');

      return;

    }

    

    let wrapper = document.createElement('div');

    wrapper.classList.add('lb-block');

    wrapper.innerHTML = sectionHTML;



    const tools = blockControls();

    $(wrapper).prepend(tools);



    canvas.appendChild(wrapper);

    console.log('Adding pencils to new section');

    addPencils(wrapper); // Add edit pencils to the new section

  }



  /**

   * Initializes background videos in the specified scope

   * @param {Element} scope - DOM element to search within (defaults to document)

   */

  function initBgVideos(scope = document) {

    scope.querySelectorAll('[data-edit="bg-video"]').forEach(section => {

        const bgVid = section.getAttribute('data-bg-video');

        if (!bgVid) return;



        let video = section.querySelector('video');

        if (!video) {

            video = document.createElement('video');

            video.autoplay = true;

            video.loop = true;

            video.muted = true;

            video.playsInline = true;

            Object.assign(video.style, {

                position: 'absolute',

                top: '0', left: '0',

                width: '100%', height: '100%',

                objectFit: 'cover',

                zIndex: '0'

            });

            section.insertBefore(video, section.firstChild);

        }



        let source = video.querySelector('source');

        if (!source) {

            source = document.createElement('source');

            source.type = 'video/mp4';

            video.appendChild(source);

        }



        source.src = bgVid;

        video.load();

    });

  }



  // Initialize background videos on page load

  initBgVideos(document);



  /**

   * Adds edit pencils to editable elements within a scope

   * @param {Element} scope - DOM element to search within

   */

 

  function addPencils(scope){

    console.log('addPencils called with scope:', scope);

    const root = scope || document;

    

    // Reset any existing pencil attachments in this scope

    root.querySelectorAll('[data-lb-pen-attached]').forEach(el => {

      el.removeAttribute('data-lb-pen-attached');

    });

    

    // Select all editable elements but exclude those inside rich text containers

    const targets = root.querySelectorAll(`

      [data-edit="text"]:not(.lb-builder-only):not(button):not([data-richtext-container] *), 

      [data-edit="richtext"]:not(.lb-builder-only):not(button),

      a:not(.lb-builder-only):not(button):not([data-richtext-container] *), 

      button[data-edit]:not(.lb-builder-only):not([data-richtext-container] *), 

      img:not(.lb-builder-only):not(button):not([data-richtext-container] *), 

      [data-edit="bg"]:not(.lb-builder-only):not(button):not([data-richtext-container] *), 

      [data-edit="bg-image"]:not(.lb-builder-only):not(button):not([data-richtext-container] *),

      [data-edit="bg-video"]:not(.lb-builder-only):not(button):not([data-richtext-container] *), 

      video:not(.lb-builder-only):not(button):not([data-richtext-container] *), 

      [data-edit="video"]:not(.lb-builder-only):not(button):not([data-richtext-container] *)

    `);

  

    console.log('Found ' + targets.length + ' potential editable elements');

    

    targets.forEach(function(el){

      // Skip if already has pencil or is in tools

      if (el.closest('.lb-tools')) return;

      if (el.hasAttribute('data-lb-pen-attached')) return;

      if (el.classList.contains('lb-builder-only')) return;

      

      // Skip elements inside rich text containers (they're edited as part of the rich text)

      if (el.closest('[data-richtext-container]')) return;

  

      // Skip button elements (they're controls, not content)

      if (el.tagName === 'BUTTON' && !el.hasAttribute('data-edit')) return;

  

      const isClickable = el.tagName === 'A' || el.tagName === 'BUTTON';

      const isBgElement = el.hasAttribute('data-edit') && 

                         (el.getAttribute('data-edit') === 'bg' || 

                          el.getAttribute('data-edit') === 'bg-image');

      

      const pen = document.createElement('button');

      pen.type = 'button';

      pen.className = 'lb-pen';

      pen.title = isClickable ? 'Edit text & link' : 

                 isBgElement ? 'Edit background' : 'Edit element';

      pen.innerHTML = '✎';

  

      // Special handling for background elements

      if (isBgElement) {

        Object.assign(pen.style, {

          position: 'absolute',

          top: '10px',

          right: '10px',

          zIndex: '1000'

        });

        

        if (getComputedStyle(el).position === 'static') {

          el.style.position = 'relative';

        }

        

        el.appendChild(pen);

      } 

      else {

        if (el.parentNode.classList.contains('lb-editable-wrap')) {

          el.parentNode.appendChild(pen);

        } else {

          const wrapper = document.createElement('span');

          wrapper.className = 'lb-editable-wrap';

          el.parentNode.insertBefore(wrapper, el);

          wrapper.appendChild(el);

          wrapper.appendChild(pen);

        }

      }

  

      el.setAttribute('data-lb-pen-attached', '1');

  

      // Click handlers

      pen.addEventListener('click', function(e){ 

        e.stopPropagation(); 

        openEditorModal(el); 

      });

      

      // Only add click handler to element if it's a button or link

      if (el.tagName === 'A' || el.tagName === 'BUTTON') {

        el.addEventListener('click', function(e){ 

          e.stopPropagation(); 

          openEditorModal(el); 

        });

      }

    });

  }



  // ---- Modal Functions ----



  /**

 * Opens the editor modal for a target element

 * @param {Element} targetEl - The element to edit

 */

  function openEditorModal(targetEl){

    console.log('Opening editor for:', targetEl);

    LB_CURRENT_TARGET = targetEl;

    const $modal = $('#lb-editor-modal');

    const $form  = $('#lb-editor-form');

  

    $form[0].reset();

    $('#lb-link-field').hide();

    $('#lb-image-field').hide();

    $('#lb-bg-field').hide();

    $('#lb-video-field').hide();

    $('#lb-bg-video-field').hide();

    $('#lb-classid-field').hide();

    $('#lb-text-field').hide();

    $('#lb-richtext-field').hide();

    $('#lb-image-preview').hide();

    $('#lb-bg-preview').hide();

  

    // Get the current content

    let currentContent = '';

    if (targetEl) {

      currentContent = targetEl.innerHTML;

    }

    

    // Always show the rich text field for all text editing

    $('#lb-richtext-field').show();

    $form.find('[name="richtext"]').val(currentContent);

  

    // For links/buttons, also show link field

    if (targetEl && (targetEl.tagName === 'A' || targetEl.tagName === 'BUTTON')){

      $('#lb-link-field').show();

      const href = targetEl.getAttribute('href') || targetEl.getAttribute('data-link') || '';

      $form.find('[name="link_url"]').val(href);

    }

    else if (targetEl.tagName === 'IMG') {

      $('#lb-image-field').show();

      const imgSrc = targetEl.getAttribute('src') || '';

      $form.find('[name="image_url"]').val(imgSrc);

      

      if (imgSrc) {

        $('#lb-image-preview').show().find('img').attr('src', imgSrc);

      }

    }

    else if (targetEl.hasAttribute('data-edit') && 

             (targetEl.getAttribute('data-edit') === 'bg' || 

              targetEl.getAttribute('data-edit') === 'bg-image')) {

      $('#lb-bg-field').show();

      

      let bgUrl = '';

      const bgStyle = targetEl.style.backgroundImage || '';

      

      if (bgStyle && bgStyle !== 'none') {

        bgUrl = bgStyle.replace(/^url\(["']?/, '').replace(/["']?\)$/, '');

      }

      

      if (!bgUrl && targetEl.hasAttribute('data-bg-image')) {

        bgUrl = targetEl.getAttribute('data-bg-image') || '';

      }

      

      $form.find('[name="bg_url"]').val(bgUrl);

      

      if (bgUrl) {

        $('#lb-bg-preview').show().css('background-image', 'url(' + bgUrl + ')');

      }

    }

    else if (targetEl.tagName === 'VIDEO' || targetEl.getAttribute('data-edit') === 'video') {

      $('#lb-video-field').show();

      $form.find('[name="video_url"]').val(targetEl.getAttribute('src') || '');

    }

    else if (targetEl.hasAttribute('data-edit') && targetEl.getAttribute('data-edit') === 'bg-video') {

      $('#lb-bg-video-field').show();

      const bgVideo = targetEl.getAttribute('data-bg-video') || '';

      $form.find('[name="bg_video_url"]').val(bgVideo);

    }

  

    // Class & ID - show for all elements

    $('#lb-classid-field').show();

    $form.find('[name="custom_class"]').val(targetEl.className || '');

    $form.find('[name="custom_id"]').val(targetEl.id || '');

  

    // Initialize TinyMCE for rich text editing

    setTimeout(function() {

      initTinyMCE();

    }, 100);

  

    $modal.show();

  }



//*** Initialize TinyMCE editor for rich text fields*/

function initTinyMCE() {

 // Remove any existing TinyMCE instances

 if (typeof tinymce !== 'undefined') {

   tinymce.remove('#lb-richtext-editor');

 }

 

 // Get the current content

 const content = $('#lb-richtext-editor').val();

 

 // Initialize TinyMCE if available

 if (typeof tinymce !== 'undefined' && $('#lb-richtext-editor').length) {

   tinymce.init({

     selector: '#lb-richtext-editor',

     height: 300,

     menubar: false,

     plugins: [

       'lists link image charmap paste help wordcount'

     ],

     toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | removeformat | help',

     content_css: '//www.tiny.cloud/css/codepen.min.css',

     setup: function(editor) {

       editor.on('init', function() {

         // Set the content after initialization

         editor.setContent(content);

       });

       

       editor.on('change', function() {

         editor.save();

       });

     }

   });

 } else {

   // Fallback to textarea if TinyMCE is not available

   console.log('TinyMCE not available, using textarea');

 }

}

  /**

   * Closes the editor modal

   */

 // Enhanced modal close function

function closeEditorModal(){ 

  // Remove TinyMCE instance

  if (typeof tinymce !== 'undefined') {

    tinymce.remove('#lb-richtext-editor');

  }

  

  // Reset form and clear all fields

  const $form = $('#lb-editor-form');

  $form[0].reset();

  

  // Clear previews

  $('#lb-image-preview').hide().find('img').attr('src', '');

  $('#lb-bg-preview').hide().css('background-image', '');

  

  // Hide all field groups

  $form.find('.lb-field').hide();

  

  $('#lb-editor-modal').hide(); 

  LB_CURRENT_TARGET = null; 

}



// Add event listener for modal close button

$(document).on('click', '.lb-modal-close, #lb-editor-modal .lb-modal-backdrop', function(e) {

  e.preventDefault();

  closeEditorModal();

});



// Prevent Enter key from submitting the form in the modal

$(document).on('keydown', '#lb-editor-modal input, #lb-editor-modal textarea', function(e) {

  if (e.keyCode === 13) {

    e.preventDefault();

  }

});

  // Event listeners for modal

  $(document).on('click', '.lb-modal-close, #lb-editor-modal .lb-modal-backdrop', closeEditorModal);



  // Preview image when URL changes

  $(document).on('input', '[name="image_url"]', function() {

    const url = $(this).val();

    if (url) {

      $('#lb-image-preview').show().find('img').attr('src', url);

    } else {

      $('#lb-image-preview').hide();

    }

  });



  // Preview background image when URL changes

  $(document).on('input', '[name="bg_url"]', function() {

    const url = $(this).val();

    if (url) {

      $('#lb-bg-preview').show().css('background-image', 'url(' + url + ')');

    } else {

      $('#lb-bg-preview').hide();

    }

  });



  // save function



  $('#lb-editor-save').on('click', function(){

    if(!LB_CURRENT_TARGET) return closeEditorModal();

    const $form = $('#lb-editor-form');

    let newRichText = $form.find('[name="richtext"]').val();
    if (typeof tinymce !== 'undefined') {
      const editor = tinymce.get('lb-richtext-editor');
      if (editor) {
        newRichText = editor.getContent();
        editor.save();
      }
    }

    const newLink = $form.find('[name="link_url"]').val();

  

    // Apply rich text content

    if (typeof newRichText === 'string'){

      LB_CURRENT_TARGET.innerHTML = newRichText;

      LB_CURRENT_TARGET.style.display = '';

    }

  

    // Handle links for buttons/anchors

    if (LB_CURRENT_TARGET && (LB_CURRENT_TARGET.tagName === 'A' || LB_CURRENT_TARGET.tagName === 'BUTTON')){

      if (newLink) {

        if (LB_CURRENT_TARGET.tagName === 'A') {

          LB_CURRENT_TARGET.setAttribute('href', newLink);

        } else {

          LB_CURRENT_TARGET.setAttribute('data-link', newLink);

        }

      } else {

        if (LB_CURRENT_TARGET.tagName === 'A') {

          LB_CURRENT_TARGET.removeAttribute('href');

        } else {

          LB_CURRENT_TARGET.removeAttribute('data-link');

        }

      }

    }



  // --- IMAGE ---

  if (LB_CURRENT_TARGET && LB_CURRENT_TARGET.tagName === 'IMG') {

    const newImg = $form.find('[name="image_url"]').val();

    if (newImg) {

      LB_CURRENT_TARGET.setAttribute('src', newImg);

      LB_CURRENT_TARGET.style.display = '';

      // Set alt text if empty

      if (!LB_CURRENT_TARGET.getAttribute('alt')) {

        LB_CURRENT_TARGET.setAttribute('alt', '');

      }

    } else {

      // Hide the image if no URL

      LB_CURRENT_TARGET.style.display = 'none';

    }

  }



  // --- BACKGROUND IMAGE ---

  if (LB_CURRENT_TARGET && LB_CURRENT_TARGET.hasAttribute('data-edit') && 

      (LB_CURRENT_TARGET.getAttribute('data-edit') === 'bg' || 

       LB_CURRENT_TARGET.getAttribute('data-edit') === 'bg-image')) {

    const newBg = $form.find('[name="bg_url"]').val();

    if (newBg) {

      LB_CURRENT_TARGET.style.backgroundImage = 'url(' + newBg + ')';

      LB_CURRENT_TARGET.style.backgroundSize = 'cover';

      LB_CURRENT_TARGET.style.backgroundPosition = 'center';

      LB_CURRENT_TARGET.setAttribute('data-bg-image', newBg);

      LB_CURRENT_TARGET.style.display = '';

    } else {

      LB_CURRENT_TARGET.style.backgroundImage = '';

      LB_CURRENT_TARGET.style.backgroundSize = '';

      LB_CURRENT_TARGET.style.backgroundPosition = '';

      LB_CURRENT_TARGET.removeAttribute('data-bg-image');

      LB_CURRENT_TARGET.style.display = 'none';

    }

  }



  // --- VIDEO ---

  if (LB_CURRENT_TARGET && (LB_CURRENT_TARGET.tagName === 'VIDEO' || LB_CURRENT_TARGET.getAttribute('data-edit') === 'video')) {

    const newVid = $form.find('[name="video_url"]').val();

    if (newVid) {

      LB_CURRENT_TARGET.setAttribute('src', newVid);

      LB_CURRENT_TARGET.style.display = '';

    } else {

      LB_CURRENT_TARGET.style.display = 'none';

    }

  }



  // --- BACKGROUND VIDEO ---

  if (LB_CURRENT_TARGET && LB_CURRENT_TARGET.hasAttribute('data-edit') && LB_CURRENT_TARGET.getAttribute('data-edit') === 'bg-video') {

    const newBgVid = $form.find('[name="bg_video_url"]').val();

    if (newBgVid) {

      LB_CURRENT_TARGET.setAttribute('data-bg-video', newBgVid);

      LB_CURRENT_TARGET.style.display = '';



      let videoElement = LB_CURRENT_TARGET.querySelector('video');

      if (!videoElement) {

        videoElement = document.createElement('video');

        videoElement.autoplay = true;

        videoElement.loop = true;

        videoElement.muted = true;

        videoElement.playsInline = true;

        Object.assign(videoElement.style, {

          position: 'absolute',

          top: '0', left: '0',

          width: '100%', height: '100%',

          objectFit: 'cover',

          zIndex: '0'

        });

        LB_CURRENT_TARGET.insertBefore(videoElement, LB_CURRENT_TARGET.firstChild);

      }



      let sourceElement = videoElement.querySelector('source');

      if (!sourceElement) {

        sourceElement = document.createElement('source');

        sourceElement.type = 'video/mp4';

        videoElement.appendChild(sourceElement);

      }

      sourceElement.src = newBgVid;

      videoElement.load();



    } else {

      LB_CURRENT_TARGET.removeAttribute('data-bg-video');

      LB_CURRENT_TARGET.style.display = 'none';

      const videoElement = LB_CURRENT_TARGET.querySelector('video');

      if (videoElement) videoElement.remove();

    }

  }



  // --- CLASS & ID ---

  const newClass = $form.find('[name="custom_class"]').val();

  const newId = $form.find('[name="custom_id"]').val();

  if (LB_CURRENT_TARGET) {

    LB_CURRENT_TARGET.className = newClass;

    LB_CURRENT_TARGET.id = newId;

  }



  // Remove TinyMCE instance before closing

  if (typeof tinymce !== 'undefined') {

    tinymce.remove('#lb-richtext-editor');

  }

  

  closeEditorModal();

});



  // ---- Posts Widget Functions ----



  /**

   * Loads posts for a section (handles both single and combined widgets)

   * @param {jQuery} $section - The section element

   */

  function loadPostsForSection($section) {

    // Check if this is a combined widget

    const isCombined = $section.hasClass('lb-combined-posts-widget');

    

    if (isCombined) {

      // Load posts for both columns

      loadPostsForColumn($section.find('.lb-main-posts-column'));

      loadPostsForColumn($section.find('.lb-featured-posts-column'));

    } else {

      // Regular single column widget

      loadPostsForColumn($section);

    }

  }



  /**

   * Loads posts for a specific column

   * @param {jQuery} $column - The column element

   */

  function loadPostsForColumn($column) {

    const $container = $column.find('.lb-posts-container');

    if ($container.length === 0) return;

    

    const widgetType = $container.data('widget-type');

    const postsPerPage = $container.data('posts-per-page') || 5;

    

    // Show loading state

    $container.html('<div class="lb-posts-loading">Loading posts...</div>');

    

    $.post(LB.ajax, {

      action: 'lb_load_posts',

      nonce: LB.nonce,

      page: 1,

      posts_per_page: postsPerPage,

      widget_type: widgetType

    }, function(response) {

      if (response.success) {

        // Replace the loading text with actual posts

        $container.html(response.data.html);

        

        // Show load more button if there are more posts to load (only for main posts column)

        const $loadMoreContainer = $column.find('.lb-load-more-container');

        if (widgetType === 'all-posts' && response.data.has_more && $loadMoreContainer.length) {

          $loadMoreContainer.show();

          $loadMoreContainer.data('next-page', response.data.next_page);

        } else if ($loadMoreContainer.length) {

          $loadMoreContainer.hide();

        }

      } else {

        console.error('AJAX error:', response);

        $container.html('<p>Error loading posts.</p>');

      }

    }).fail(function(xhr, status, error) {

      // Fallback to REST if AJAX fails (e.g., 403 due to nonce)

      jQuery.ajax({ url: REST_ROOT + 'lb/v1/products', type: 'GET', success: function(res){ if(res && res.html){ $container.empty().html(res.html); } }, error: function(){ /* leave default error UI */ } });

      

      console.error('AJAX request failed:', status, error);

      $container.html('<p>Failed to load posts.</p>');

    });

  }



  // Handle load more button click

  $(document).off('click', '.lb-load-more-posts').on('click', '.lb-load-more-posts', function(e) {

    e.preventDefault();

    e.stopPropagation();

    

    const $button = $(this);

    const $loadMoreContainer = $button.closest('.lb-load-more-container');

    const $column = $loadMoreContainer.closest('.lb-main-posts-column');

    const $container = $column.find('.lb-posts-container');

    const widgetType = $container.data('widget-type');

    const postsPerPage = $container.data('posts-per-page') || 5;

    const nextPage = $loadMoreContainer.data('next-page') || 2;

    

    // Disable button to prevent multiple clicks

    if ($button.prop('disabled')) {

      return;

    }

    

    $button.text('Loading...').prop('disabled', true);

    

    $.post(LB.ajax, {

      action: 'lb_load_posts',

      nonce: LB.nonce,

      page: nextPage,

      posts_per_page: postsPerPage,

      widget_type: widgetType

    }, function(response) {

      if (response.success) {

        // Append new posts (the response should only contain new posts, not loading text)

        $container.append(response.data.html);

        

        // Update next page or hide button

        if (response.data.has_more) {

          $loadMoreContainer.data('next-page', response.data.next_page);

          $button.text('Load More').prop('disabled', false);

        } else {

          $loadMoreContainer.hide();

        }

      }

    }).fail(function(xhr, status, error) {

      // Fallback to REST if AJAX fails (e.g., 403 due to nonce)

      jQuery.ajax({ url: REST_ROOT + 'lb/v1/products', type: 'GET', success: function(res){ if(res && res.html){ $container.empty().html(res.html); } }, error: function(){ /* leave default error UI */ } });

      

      console.error('Load more AJAX failed:', status, error);

      $button.text('Load More').prop('disabled', false);

      alert('Failed to load more posts. Please try again.');

    });

  });



  // Load posts when a posts widget section is added

  $canvas.on('DOMNodeInserted', '.lb-posts-widget, .lb-combined-posts-widget', function(e) {

    const $widget = $(this);

    

    // Check if we've already loaded posts for this widget

    if (!$widget.data('posts-loaded')) {

      $widget.data('posts-loaded', true);

      

      // Use a small delay to ensure the element is fully in the DOM

      setTimeout(function() {

        loadPostsForSection($widget);

      }, 100);

    }

  });



  // Load posts for any existing widgets when the page loads

  $(document).ready(function() {

    $('.lb-posts-widget, .lb-combined-posts-widget').each(function() {

      $(this).data('posts-loaded', true);

      loadPostsForSection($(this));

    });

  });



  // ---- WooCommerce Functions ----



  /**

   * Loads WooCommerce categories

   * @param {jQuery} $container - The container element

   */

/**

 * Loads WooCommerce categories

 * @param {jQuery} $container - The container element

 */

function loadWooCategories($container) {

  // Show loading state with spinner

  $container.html('<div class="lb-loader"><div class="spinner"></div></div>');

  

  $.ajax({

    url: (window.wpApiSettings?.root || (window.LB && LB.rest) || '/wp-json/') + 'lb/v1/categories',

    type: 'GET',

    success: function(response) {

      console.log('Categories loaded successfully:', response);

      if (response.html) {

        $container.empty().html(response.html);

        

        // Reinitialize mobile carousels after categories are loaded

        setTimeout(function() {

          initMobileCarousels();

        }, 100);

      }

    },

    error: function(xhr, status, error) {

      console.error('REST API request failed:', status, error);

      $container.html('<p>Failed to load categories.</p>');

    }

  });

}



  /**

   * Loads WooCommerce products

   * @param {jQuery} $container - The container element

   */

  function loadWooProducts($container) {

    const productType = $container.data('product-type') || 'featured';



    // Show loading spinner

    $container.html('<div class="lb-loader"><span class="dot"></span><span class="dot"></span><span class="dot"></span></div>');



    $.ajax({

      url: (window.wpApiSettings?.root || (window.LB && LB.rest) || '/wp-json/') + 'lb/v1/products',

      type: 'GET',

      data: { type: productType },

      success: function(res) {

        if (res && res.html) {

          $container.empty().html(res.html);



          // Reset Owl Carousel if it exists

          if ($container.hasClass('owl-loaded')) {

            $container.trigger('destroy.owl.carousel');

            $container.removeClass('owl-loaded owl-hidden');

            $container.find('.owl-stage-outer').children().unwrap();

          }



          // Initialize Owl Carousel after a short delay

          setTimeout(function() {

            if (typeof $.fn.owlCarousel !== 'undefined') {

              $container.owlCarousel({

                items: 4,

                loop: true,

                dots: true,

                nav: false,

                margin: 20,

                smartSpeed: 600,

                autoplay: true,

                autoplayTimeout: 4000,

                autoplayHoverPause: true,

                responsive: {

                  0: { items: 1 },

                  600: { items: 2 },

                  1000: { items: 4 }

                }

              });

            } else {

              console.warn('Owl Carousel not loaded');

            }

          }, 100);

        } else {

          $container.html('<p>No ' + productType + ' products found.</p>');

        }

      },

      error: function(xhr, status, error) {

        console.error('Products REST API failed:', status, error);

        $container.html('<p>Failed to load ' + productType + ' products.</p>');

      }

    });

  }



  // Load WooCommerce data when widgets are added

  $canvas.on('DOMNodeInserted', '.lb-woo-categories, .lb-woo-products', function(e) {

    const $widget = $(this);

    

    // Check if we've already loaded data for this widget

    if (!$widget.data('woo-loaded')) {

      $widget.data('woo-loaded', true);

      

      // Use a small delay to ensure the element is fully in the DOM

      setTimeout(function() {

        if ($widget.hasClass('lb-woo-categories')) {

          loadWooCategories($widget);

        } else if ($widget.hasClass('lb-woo-products')) {

          loadWooProducts($widget);

        }

      }, 100);

    }

  });



  // Load WooCommerce data for any existing widgets when the page loads

  $(document).ready(function() {

    $('.lb-woo-categories').each(function() {

      $(this).data('woo-loaded', true);

      loadWooCategories($(this));

    });

    

    $('.lb-woo-products').each(function() {

      $(this).data('woo-loaded', true);

      loadWooProducts($(this));

    });

  });



  

  // Enhanced mobile carousel for features and categories

function initMobileCarousels() {

  const isMobile = window.innerWidth < 768;

  console.log('Initializing mobile carousels, isMobile:', isMobile);

  

  // Features carousel for mobile

  $('.lb-features3 .lb-features').each(function() {

    const $features = $(this);

    

    if (isMobile) {

      if (!$features.hasClass('owl-loaded')) {

        console.log('Initializing features carousel');

        $features.addClass('owl-carousel');

        $features.owlCarousel({

          items: 1,

          loop: true,

          dots: true,

          nav: false,

          margin: 20,

          autoplay: false

        });

      }

    } else {

      if ($features.hasClass('owl-loaded')) {

        console.log('Destroying features carousel');

        $features.trigger('destroy.owl.carousel');

        $features.removeClass('owl-carousel owl-loaded owl-hidden');

        $features.find('.owl-stage-outer').children().unwrap();

      }

    }

  });

  

  // Category grid carousel for mobile - handle both empty and loaded states

  $('.lb-categorygrid').each(function() {

    const $categoryGrid = $(this);

    const $container = $categoryGrid.find('.lb-grid-4, .lb-woo-categories, .lb-grid');

    

    console.log('Found category grid:', $categoryGrid);

    console.log('Category container:', $container);

    console.log('Container children:', $container.children().length);

    

    if ($container.length > 0 && isMobile) {

      // Check if categories are already loaded (not just loader)

      const hasLoadedContent = $container.children().length > 0 && 

                              !$container.find('.lb-loader').length && 

                              !$container.find('.spinner').length;

      

      if (hasLoadedContent && !$container.hasClass('owl-loaded')) {

        console.log('Initializing category carousel with content');

        $container.addClass('owl-carousel');

        $container.owlCarousel({

          items: 2,

          loop: true,

          dots: true,

          nav: false,

          margin: 15,

          autoplay: false,

          responsive: {

            0: { items: 1 },

            480: { items: 1 }

          }

        });

      }

    } else if ($container.length > 0 && !isMobile) {

      if ($container.hasClass('owl-loaded')) {

        console.log('Destroying category carousel');

        $container.trigger('destroy.owl.carousel');

        $container.removeClass('owl-carousel owl-loaded owl-hidden');

        $container.find('.owl-stage-outer').children().unwrap();

      }

    }

  });

}



// Initialize on page load

$(document).ready(function() {

  initMobileCarousels();

});



// Reinitialize on window resize

let resizeTimer;

$(window).on('resize', function() {

  clearTimeout(resizeTimer);

  resizeTimer = setTimeout(function() {

    initMobileCarousels();

  }, 250);

});



// Initialize when new sections are added

$canvas.on('DOMNodeInserted', '.lb-features3, .lb-categorygrid', function() {

  setTimeout(function() {

    initMobileCarousels();

  }, 100);

});



// Special handling for when categories are loaded via AJAX

$canvas.on('DOMNodeInserted', '.lb-woo-categories', function(e) {

  const $widget = $(this);

  

  if (!$widget.data('woo-loaded')) {

    $widget.data('woo-loaded', true);

    

    // Wait a bit longer for categories to load, then initialize carousel

    setTimeout(function() {

      loadWooCategories($widget);

    }, 150);

  }

});



// Also reinitialize when template is loaded

$('#lb-load').on('click', function() {

  setTimeout(function() {

    initMobileCarousels();

  }, 500);

});

  // ---- Add Elements Panel ----



  // Handle adding different types of sections

  $('.lb-add').on('click', function(){

    const type = $(this).data('type');

    console.log('Adding section type:', type);

    switch(type){


      case 'heading':

        makeSection(

          '<section class="lb-heading-section">' +

            '<div class="container">' +

              '<div class="lb-heading-content" data-edit="richtext" data-richtext-container="true" data-remove-if-empty="true">' +

                '<h1>Main Heading</h1>' +

                '<p>Your content goes here...</p>' +

              '</div>' +

            '</div>' +

          '</section>'

        );

        break;

      case 'hero':

        makeSection(

          '<section class="lb-hero lb-bg-image-section" data-edit="bg-image" data-bg-image="" style="background-size: cover; background-position: center;">' +

            '<div class="container">' +

              '<h1 class="lb-hero-title" data-edit="text">Amino USA</h1>' +

              '<p class="lb-hero-sub" data-edit="text">Science-driven formulations. Fast U.S. shipping.</p>' +

              '<a class="lb-btn" href="#" data-edit="text">Shop Now</a>' +

            '</div>' +

          '</section>'

        );

        break;

case 'bg-image-section':
  console.log('CREATING bg-image-section');
  
  // Create the HTML - EXACT same as bg-video but with bg-image
  const html = '<section class="lb-bg-video-section" data-edit="bg-image" style="position: relative; overflow: hidden;" data-bg-image="">' +
    '<div class="lb-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 1;"></div>' +
    '<div class="container" style="position: relative; z-index: 1;">' +
      '<img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZWVlZWVlIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJtb25vc3BhY2UiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTk5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Qcm9kdWN0IEltYWdlPC90ZXh0Pjwvc3ZnPg==" data-edit="image" alt=""/>' +
      '<p data-edit="text" class="hero-bg-video-txt">This section has a background image with overlay</p>' +
      '<a class="lb-btn" href="#" data-edit="text">Shop Now</a>' +
    '</div>' +
  '</section>';
  
  
  // Use makeSection
  makeSection(html);
  
  break;
   
        

        case 'content-image-split':

          makeSection(

            '<section class="lb-content-image-split lb-hero lb-bg-image-section" data-edit="bg-image" data-bg-image="">'+

              '<div class="lb-content-image-inner lb-grid-2">'+

                // Left side - Content (editable)

                '<div class="lb-content-side">'+

                  '<h2 data-edit="text">Your Heading Here</h2>'+

                  '<h2 data-edit="text">Your Heading Here</h2>'+

                  '<p data-edit="text">This is your content area. You can add text content here.</p>'+
                                                      '<h3 data-edit="text">Your Heading Here</h3>'+
                                     '<a class="lb-btn" href="#" data-edit="text">login</a>' +


                 

                '</div>'+

                // Right side - Image (editable)

                '<div class="lb-image-side">'+

                  '<img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAwIiBoZWlnaHQ9IjQwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZWVlZWVlIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJtb25vc3BhY2UiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTk5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5JbWFnZSBQbGFjZWhvbGRlcjwvdGV4dD48L3N2Zz4=" data-edit="image" alt="">'+

                '</div>'+

              '</div>'+

           '</section>'

          );

          break;
          
      
            

      case 'features3':

        makeSection(

          '<section class="lb-features3" ><div class="container" data-edit="bg-image" data-bg-image="" style="background-size: cover; background-position: center;">' +

            '<div class="lb-features lb-grid-3" >' +

              '<div class="lb-feature" data-edit="bg-image" data-bg-image="" style="background-size: cover; background-position: center;">'+

              '<img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZWVlZWVlIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJtb25vc3BhY2UiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTk5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Qcm9kdWN0IEltYWdlPC90ZXh0Pjwvc3ZnPg==" data-edit="image" alt="">'+

             '<h2 data-edit="text">Produced in the USA</h2>'+

             '<p data-edit="text">We have partnered with an ISO 9001:2015 approved manufacturer here in the U.S. to produce a majority of our research compounds</p>'+

             '</div>' +

             '<div class="lb-feature" data-edit="bg-image" data-bg-image="" style="background-size: cover; background-position: center;">'+

             '<img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZWVlZWVlIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJtb25vc3BhY2UiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTk5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Qcm9kdWN0IEltYWdlPC90ZXh0Pjwvc3ZnPg==" data-edit="image" alt="">'+

            '<h2 data-edit="text">Produced in the USA</h2>'+

            '<p data-edit="text">We have partnered with an ISO 9001:2015 approved manufacturer here in the U.S. to produce a majority of our research compounds</p>'+

             '</div>' +

             '<div class="lb-feature" data-edit="bg-image" data-bg-image="" style="background-size: cover; background-position: center;">'+

             '<img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZWVlZWVlIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJtb25vc3BhY2UiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTk5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Qcm9kdWN0IEltYWdlPC90ZXh0Pjwvc3ZnPg==" data-edit="image" alt="">'+

            '<h2 data-edit="text">Produced in the USA</h2>'+

            '<p data-edit="text">We have partnered with an ISO 9001:2015 approved manufacturer here in the U.S. to produce a majority of our research compounds</p>'+

             '</div>' +

           '</div></div></section>'

       );

       break;

     

     // Category grid section

     case 'categorygrid':

       makeSection(

         '<section class="lb-categorygrid"><div class="container">'+

           '<h2 data-edit="text" class="lb-h2">Browse by Category</h2>'+

           '<div class="lb-grid lb-grid-4 lb-woo-categories" data-nonce="' + LB.nonce + '">'+

             '<div class="lb-loader"><div class="spinner"></div></div>'+

           '</div></div></section>'

       ); 

       break;



     // Product grid section

     case 'productgrid':

       makeSection(

         '<section class="lb-productgrid"><div class="container">'+

           '<h2 data-edit="text" class="lb-h2">Featured Products</h2>'+

           '<div class="lb-product-carousel owl-carousel lb-woo-products" data-product-type="featured" data-nonce="' + LB.nonce + '">'+

             '<div class="lb-loader"><div class="spinner"></div></div>'+

           '</div></div></section>'

       ); 

       break;



     // New launch grid section

     case 'newlaunchgrid':

       makeSection(

         '<section class="lb-newlaunchgrid"><div class="container">'+

           '<h2 data-edit="text" class="lb-h2">New Launch Products</h2>'+

           '<div class="lb-product-carousel owl-carousel lb-woo-products" data-product-type="newlaunch" data-nonce="' + LB.nonce + '">'+

             '<div class="lb-loader"><div class="spinner"></div></div>'+

           '</div></div></section>'

       ); 

       break;



     // FAQ section with accordion functionality

     case 'faqs':

       makeSection(

         '<section class="lb-faqs-section lb-bg-image-section" data-edit="bg-image" data-bg-image="">' +

           '<div class="container">' +

             '<h2 class="lb-faqs-title" data-edit="text" data-remove-if-empty="true">Frequently Asked Questions</h2>' +

            

             '<div class="lb-accordions-container">' +

               '<div class="lb-accordion-item">' +

                 '<div class="lb-accordion-header">' +

                   '<h3 class="lb-accordion-title" data-edit="text">Question 1</h3>' +

                   '<span class="lb-accordion-toggle">+</span>' +

                 '</div>' +

                 '<div class="lb-accordion-content">' +

                   '<div class="lb-accordion-answer" data-edit="text">Answer to question 1 goes here...</div>' +

                 '</div>' +

                 '<div class="lb-accordion-controls lb-builder-only">' +

                   '<button type="button" class="button lb-accordion-up" title="Move Up">↑</button>' +

                   '<button type="button" class="button lb-accordion-down" title="Move Down">↓</button>' +

                   '<button type="button" class="button lb-accordion-remove" title="Remove">×</button>' +

                 '</div>' +

               '</div>' +

             '</div>' +

             '<button type="button" class="button lb-add-accordion lb-builder-only">Add New FAQ Item</button>' +

           '</div>' +

         '</section>'

       );

       

       // Initialize accordion functionality

       initAccordions();

       break;



     // Stat badge section

     case 'statbadge':

       makeSection(

         '<section class="lb-badge"><div class="container">'+

           '<div class="lb-badge-inner lb-grid-2">'+

           '<div class="lb-badge-left"><img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZWVlZWVlIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJtb25vc3BhY2UiIGZvbnQ-c2l6ZT0iMTQiIGZpbGw9IiM5OTk5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Qcm9kdWN0IEltYWdlPC90ZXh0Pjwvc3ZnPg==" data-edit="image" alt=""></div>'+

             '<div class="lb-badge-right"><img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZWVlZWVlIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJtb25vc3BhY2UiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTk5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Qcm9kdWN0IEltYWdlPC90ZXh0Pjwvc3ZnPg==" data-edit="image" alt=""><h3 data-edit="text">ISO 9001:2015 Partnership</h3><p data-edit="text">We partner with approved manufacturers for quality and purity.</p></div>'+

           '</div></div></section>'

       ); 

       break;

     

     // Milestones section with counter

     case 'milestones':

       makeSection(

         '<section class="lb-milestones"><div class="container">'+

         '<div class="lb-stat counter" data-edit="text" data-target="100000">100000</div>'+ 

           '<h2 data-edit="text" class="lb-h2">Research Milestones Supported</h2>'+

          

           '<p data-edit="text" class="lb-center">We are dedicated to supporting researchers nationwide...</p>'+

         '</div></section>'

       );

       break;



     // Newsletter subscription section

     case 'newsletter':

       makeSection(

         '<section class="lb-newsletter"><div class="container">'+

           '<h3 data-edit="text">Be The First To Know</h3>'+

           '<p data-edit="text">Sign up to stay informed about our latest product releases and discounts.</p>'+

          '<div class="lb-cf7-shortcode" data-edit="text" data-remove-if-empty="true" data-cf7-id="123">' +

               'Contact Form 7 Shortcode - Click to edit form ID' +

             '</div>' 

       ); 

       break;

     

     // Video section

     case 'video':

       makeSection(

         '<section class="lb-video-section">'+

           '<div class="container">'+

             '<h2 data-edit="text" class="lb-h2">Featured Video</h2>'+

             '<video controls data-edit="video" style="max-width: 100%; height: auto;">'+

               '<source src="" type="video/mp4">'+

               'Your browser does not support the video tag.'+

             '</video>'+

           '</div>'+

         '</section>'

       ); 

       break;

     

     // Background video section

     case 'bg-video':

       // Insert the section template

       makeSection(

           '<section class="lb-bg-video-section" data-edit="bg-video" style="position: relative; overflow: hidden;" data-bg-video="">'+

               '<video autoplay loop muted playsinline style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: 0;">'+

                   '<source src="" type="video/mp4">'+

                   'Your browser does not support the video tag.'+

               '</video>'+

               '<div class="lb-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 1;"></div>'+

               '<div class="container" style="position: relative; z-index: 1;">'+

              '<img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZWVlZWVlIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJtb25vc3BhY2UiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTk5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Qcm9kdWN0IEltYWdlPC90ZXh0Pjwvc3ZnPg==" data-edit="image" alt=""/>'+

                   '<p data-edit="text" class="hero-bg-video-txt">This section has a video background</p>'+

                   '<a class="lb-btn" href="#" data-edit="text">Shop Now</a>' +

               '</div>'+

           '</section>'

       );

   

       // After inserting, sync video sources

       initBgVideos();

       break;

       

     // Contact form section

     case 'contactform':

       makeSection(

         '<section class="lb-contact-section">' +

           '<div class="container">' +

             '<div class="lb-contact-info lb-grid-3">' +

             '<div class="lb-contact-item">' +

             '<div class="lb-contact-icon"><img src="<?php echo esc_url( home_url() ); ?>/custom-site/wp-content/uploads/2025/08/phone-2.png"/></div>' +

             '<h3 data-edit="text">Phone Number</h3>' +

             '<p data-edit="text">+1 (555) 123-4567</p>' +

           '</div>' +

           '<div class="lb-contact-item">' +

             '<div class="lb-contact-icon"><img src="<?php echo esc_url( home_url() ); ?>/custom-site/wp-content/uploads/2025/08/envelope.png"/></div>' +

             '<h3 data-edit="text">Email Address</h3>' +

             '<p data-edit="text">info@yourcompany.com</p>' +

           '</div>' +

           '<div class="lb-contact-item">' +

             '<div class="lb-contact-icon"><img src="<?php echo esc_url( home_url() ); ?>/custom-site/wp-content/uploads/2025/08/location.png"/></div>' +

             '<h3 data-edit="text">Our Address</h3>' +

             '<p data-edit="text">123 Business Ave, City, State 12345</p>' +

           '</div>' +

             '</div>' +

             '<h2 class="lb-contact-heading" data-edit="text">Get In Touch</h2>' +

             '<div class="lb-cf7-shortcode" data-edit="text" data-remove-if-empty="true" data-cf7-id="123">' +

               'Contact Form 7 Shortcode - Click to edit form ID' +

             '</div>' +

           '</div>' +

         '</section>'

       );

       break;

     

     // Combined posts widget (latest + featured posts)

     case 'combinedpostswidget':

       makeSection(

         '<section class="lb-combined-posts-widget">' +

           '<div class="container">' +

             '<div class="lb-combined-posts-grid">' +

               '<div class="lb-main-posts-column">' +

                 '<h2 data-edit="text" class="lb-h2">Latest Posts</h2>' +

                 '<div class="lb-posts-container" data-widget-type="all-posts" data-posts-per-page="5">' +

                   '<div class="lb-posts-loading">Loading posts...</div>' +

                 '</div>' +

                 '<div class="lb-load-more-container" style="display: none;">' +

                   '<button class="lb-btn lb-load-more-posts">Load More</button>' +

                 '</div>' +

               '</div>' +

               '<div class="lb-featured-posts-column">' +

                 '<h2 data-edit="text" class="lb-h2">Featured Posts</h2>' +

                 '<div class="lb-posts-container" data-widget-type="featured-posts" data-posts-per-page="5">' +

                   '<div class="lb-posts-loading">Loading featured posts...</div>' +

                 '</div>' +

               '</div>' +

             '</div>' +

           '</div>' +

         '</section>'

       );

       break;

     

     // Standard posts widget

     case 'postswidget':

       makeSection(

         '<section class="lb-posts-widget">' +

           '<div class="container">' +

             '<h2 data-edit="text" class="lb-h2">Latest Posts</h2>' +

             '<div class="lb-posts-container" data-widget-type="all-posts" data-posts-per-page="5">' +

               '<div class="lb-posts-loading">Loading posts...</div>' +

             '</div>' +

             '<div class="lb-load-more-container" style="display: none;">' +

               '<button class="lb-btn lb-load-more-posts">Load More</button>' +

             '</div>' +

           '</div>' +

         '</section>'

       );

       break;



     // Featured posts widget

     case 'featuredpostswidget':

       makeSection(

         '<section class="lb-posts-widget">' +

           '<div class="container">' +

             '<h2 data-edit="text" class="lb-h2">Featured Posts</h2>' +

             '<div class="lb-posts-container" data-widget-type="featured-posts" data-posts-per-page="5">' +

               '<div class="lb-posts-loading">Loading featured posts...</div>' +

             '</div>' +

           '</div>' +

         '</section>'

       );

       break;
     

     default: 

       return;

   }

 });



 // ---- Canvas Interactions ----



 // Delete block

 $canvas.on('click', '.lb-del', function(){ 

   $(this).closest('.lb-block').remove(); 

 });

 

 // Move block up

 $canvas.on('click', '.lb-up', function(){

   const $b = $(this).closest('.lb-block'); 

   const $prev = $b.prev('.lb-block'); 

   if ($prev.length) $b.insertBefore($prev);

 });

 

 // Move block down

 $canvas.on('click', '.lb-down', function(){

   const $b = $(this).closest('.lb-block'); 

   const $next = $b.next('.lb-block'); 

   if ($next.length) $b.insertAfter($next);

 });



 // ---- Serialization ----



 /**

  * Serializes the canvas content for saving

  * @returns {string} The serialized HTML content

  */

 function serializeCanvas(){

   const clone = $canvas.clone();



   // Remove all builder-specific elements and attributes

   clone.find('.lb-tools, .lb-pen').remove();

   clone.find('[data-lb-pen-attached]').removeAttr('data-lb-pen-attached');

   clone.find('.lb-editable-wrap').each(function(){

     $(this).replaceWith($(this).contents());

   });



   // Also remove any contenteditable attributes we added

   clone.find('[contenteditable]').removeAttr('contenteditable');



   // Preserve lb-builder-only class as data attribute for reloading

   clone.find('.lb-builder-only').each(function() {

     $(this).attr('data-lb-builder-only', 'true');

   });



   clone.find('.lb-block').each(function(){

     const $child = $(this).children().not('.lb-tools').first();

     

     // Preserve background image styles for elements with data-edit="bg"

     if ($child.attr('data-edit') === 'bg') {

       const original = $(this).find('[data-edit="bg"]').first();

       if (original.length) {

         $child.attr('style', original.attr('style'));

       }

     }

     

     $(this).replaceWith($child);

   });



   return clone.html();

 }



 // ---- Template Management ----



 /**

  * Loads and displays the list of saved templates

  */

 function listTemplates(){

  console.log('Loading template list');

  $.ajax({

    url: LB.ajax,

    type: 'POST',

    data: {

      action: 'lb_list_templates', 

      nonce: LB.nonce

    },

    success: function(res) {

      if(!res.success) {

        console.error('Failed to load templates:', res);

        // Fallback: Show empty template list

        $list.empty();

        $list.append($('<option>').val(0).text('No templates available'));

        return;

      }

      console.log('Loaded ' + res.data.length + ' templates');

      $list.empty();

      $list.append($('<option>').val(0).text('— Select Template —'));

      res.data.forEach(function(item){

        $list.append($('<option>').val(item.id).text(item.title));

      });

    },

    error: function(xhr, status, error) {

      console.error('AJAX error loading templates:', status, error);

      // Fallback for AJAX failure

      $list.empty();

      $list.append($('<option>').val(0).text('Error loading templates'));

      

      // Try REST API as fallback

      jQuery.ajax({

        url: REST_ROOT + 'lb/v1/templates',

        type: 'GET',

        success: function(res) {

          if(res && Array.isArray(res)) {

            $list.empty();

            $list.append($('<option>').val(0).text('— Select Template —'));

            res.forEach(function(item){

              $list.append($('<option>').val(item.id).text(item.title));

            });

          }

        }

      });

    }

  });

}

 

 // Initialize template list

 listTemplates();



 // Save template

 $('#lb-save').on('click', function(){

   const name = $name.val().trim() || ('Template ' + new Date().toLocaleString());

   const content = serializeCanvas();

   console.log('Saving template:', name);

   

   $.post(LB.ajax, {action:'lb_save_template', nonce:LB.nonce, id: currentTemplateId, name, content}, function(res){

     if(res.success){

       alert(res.data.message || 'Saved');

       if (!currentTemplateId && res.data.id) currentTemplateId = res.data.id;

       listTemplates();

     } else {

       alert('Error saving template: ' + (res.data || res));

     }

   });

 });



 // Media upload buttons

 $(document).on('click', '.lb-upload', function(e){

   e.preventDefault();

   const field = $(this).data('target'); // e.g. "image_url"

   const frame = wp.media({ title: 'Select Media', multiple: false });

   frame.on('select', function(){

     const attachment = frame.state().get('selection').first().toJSON();

     $('[name="'+field+'"]').val(attachment.url);

     

     // Trigger preview update

     if (field === 'image_url') {

       $('#lb-image-preview').show().find('img').attr('src', attachment.url);

     } else if (field === 'bg_url') {

       $('#lb-bg-preview').show().css('background-image', 'url(' + attachment.url + ')');

     }

   });

   frame.open();

 });

 

 /**

  * Checks and hides empty elements that should be removed when empty

  * @param {Element} container - The container to check for empty elements

  */

 function checkAndHideEmptyElements(container) {

   const elements = container.querySelectorAll('[data-remove-if-empty]');

   elements.forEach(el => {

     const hasContent = el.textContent && el.textContent.trim() !== '';

     const hasImages = el.querySelector('img[src]');

     const hasBackground = el.style.backgroundImage && el.style.backgroundImage !== 'none';

     

     if (!hasContent && !hasImages && !hasBackground) {

       el.style.display = 'none';

     } else {

       el.style.display = '';

     }

   });

 }

 

 // Load template

 $('#lb-load').on('click', function(){

   const id = parseInt($list.val() || 0, 10); 

   if(!id) return alert('Select a template to load');



   console.log('Loading template ID:', id);

   $.post(LB.ajax, {action:'lb_load_template', nonce:LB.nonce, id}, function(res){

     if(res.success){

       console.log('Template loaded successfully');

       $canvas.html('');

       $name.val(res.data.title || '');

       currentTemplateId = id;



       const temp = $('<div>').html(res.data.content);

       

       temp.children().each(function(){

         const $child = $(this);

         const cls = ($child.attr('class')||'').split(' ').find(c=>/^lb-/.test(c)) || '';

         const $blk = makeBlock(cls || 'lb-section', false, $child.html());



         if(cls){

           $blk.find('.'+cls).replaceWith($child);

           $blk.children().not('.lb-tools').attr('contenteditable', 'false');

         } else {

           $blk.find('.lb-section').html($child.prop('outerHTML'));

         }

         

         // Restore lb-builder-only class from data attribute

         $blk.find('[data-lb-builder-only]').each(function() {

           $(this).addClass('lb-builder-only').removeAttr('data-lb-builder-only');

         });

         

         // Ensure background images are properly styled

         $blk.find('[data-edit="bg"]').each(function() {

           const bgImage = $(this).css('background-image');

           if (bgImage && bgImage !== 'none') {

             $(this).css({

               'background-size': 'cover',

               'background-position': 'center'

             });

           }

         });

         

         $canvas.append($blk);

       });



       // Check and hide empty elements

       checkAndHideEmptyElements($canvas[0]);

       

       // Add pencils to the entire canvas

       addPencils($canvas.get(0));

       

       // Ensure bg-video sections reload correctly

       $canvas.find('[data-edit="bg-video"]').each(function(){

         const bgVid = this.getAttribute('data-bg-video');

         if (bgVid) {

           let videoElement = this.querySelector('video');

           if (!videoElement) {

             videoElement = document.createElement('video');

             videoElement.autoplay = true;

             videoElement.loop = true;

             videoElement.muted = true;

             videoElement.playsInline = true;

             Object.assign(videoElement.style, {

               position: 'absolute',

               top: '0', left: '0',

               width: '100%', height: '100%',

               objectFit: 'cover',

               zIndex: '0'

             });

             this.insertBefore(videoElement, this.firstChild);

           }



           let sourceElement = videoElement.querySelector('source');

           if (!sourceElement) {

             sourceElement = document.createElement('source');

             sourceElement.type = 'video/mp4';

             videoElement.appendChild(sourceElement);

           }

           sourceElement.src = bgVid;



           videoElement.load();

         }

       });



       // Ensure background images are properly restored

       $canvas.find('[data-edit="bg"], [data-edit="bg-image"]').each(function() {

         const bgUrl = this.getAttribute('data-bg-image');

         if (bgUrl) {

           this.style.backgroundImage = 'url(' + bgUrl + ')';

           this.style.backgroundSize = 'cover';

           this.style.backgroundPosition = 'center';

         }

       });

       

       // Reinitialize accordion functionality after loading

       initAccordions();



     } else {

       console.error('Failed to load template');

       alert('Failed to load template');

     }

   });

 });

 

 // Delete template

 $('#lb-delete').on('click', function(){

   const id = parseInt($list.val() || 0, 10);

   if(!id) return alert('Select a template to delete');

   

   if(confirm('Are you sure you want to delete this template?')) {

     $.post(LB.ajax, {action:'lb_delete_template', nonce:LB.nonce, id}, function(res){

       if(res.success) {

         alert('Template deleted');

         listTemplates();

       } else {

         alert('Error deleting template');

       }

     });

   }

 });

});



/**

* Fallback function to ensure nonce attribute is present on widgets at runtime

* This helps with AJAX requests that require authentication

*/

function injectNonceIfMissing() {

 try {

   var n = (window.LB && LB.nonce) ? LB.nonce : null;

   if (!n) return;

   jQuery('.lb-woo-products, .lb-woo-categories').each(function(){

     var $el = jQuery(this);

     if(!$el.data('nonce')) { 

       $el.attr('data-nonce', n); 

       $el.data('nonce', n); 

     }

   });

 } catch(e){}

}



// Run the nonce injection when document is ready

jQuery(document).ready(injectNonceIfMissing);