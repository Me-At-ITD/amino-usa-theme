document.addEventListener('DOMContentLoaded', function () { 

  // 🎬 Handle Background Videos
  document.querySelectorAll('.lb-bg-video-section[data-bg-video]').forEach(section => {
    const url = section.getAttribute('data-bg-video');
    if (!url) return;

    let video = section.querySelector('video');
    if (!video) {
      video = document.createElement('video');
      video.autoplay = true;
      video.loop = true;
      video.muted = true;
      video.playsInline = true;
      Object.assign(video.style, {
        position: 'absolute',
        top: '0',
        left: '0',
        width: '100%',
        height: '100%',
        objectFit: 'cover',
        zIndex: '0'
      });
      section.insertBefore(video, section.firstChild);
    }

    // Ensure <source> exists
    let source = video.querySelector('source');
    if (!source) {
      source = document.createElement('source');
      source.type = 'video/mp4';
      video.appendChild(source);
    }
    source.src = url;

    // Poster fallback support
    const poster = section.getAttribute('data-bg-poster');
    if (poster) {
      video.setAttribute('poster', poster);
    }

    video.load();
  });

  // 🎨 Background images
  document.querySelectorAll('[data-bg-image]').forEach(function(section){
    const url = section.getAttribute('data-bg-image');
    if(url) {
      section.style.backgroundImage = 'url(' + url + ')';
      section.style.backgroundSize = 'cover';
      section.style.backgroundPosition = 'center';
    }
  });

  console.log("✅ counter.js loaded");

  const counter = document.querySelector(".counter");
  if (!counter) {
    console.log("❌ No .counter element found on page");
  } else {
    // Clean up the editable text (remove + , and spaces)
    let textValue = counter.innerText.replace(/[+,]/g, "").trim();
    let target = 0;

    if (textValue && !isNaN(textValue)) {
      target = parseInt(textValue, 10);
    }

    // Keep data-target in sync
    counter.setAttribute("data-target", target);
    console.log("🎯 Counter target value:", target);

    let count = 0;
    const speed = 200; // lower = faster
    let hasStarted = false;

    const updateCount = () => {
      const increment = Math.ceil(target / speed);
      if (count < target) {
        count += increment;
        counter.innerText = count.toLocaleString() + "+";
        requestAnimationFrame(updateCount);
      } else {
        counter.innerText = target.toLocaleString() + "+";
        console.log("✅ Counter finished at:", counter.innerText);
      }
    };

    // 👀 Start counter only when in viewport
    const observer = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (entry.isIntersecting && !hasStarted) {
          hasStarted = true;
          updateCount();
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });

    observer.observe(counter);
  }

// 🌄 Smooth 2-axis parallax background for .parallax-section elements
window.addEventListener('scroll', () => {
  const scrollY = window.scrollY;

  // tweak speeds as needed
  const offsetX = scrollY * 0.15; // horizontal
  const offsetY = scrollY * 0.25; // vertical

  document.querySelectorAll('.parallax-section').forEach(section => {
    // diagonal motion, still centered
    section.style.backgroundPosition = `calc(50% + ${offsetX}px) calc(50% + ${offsetY}px)`;
  });
});


});
