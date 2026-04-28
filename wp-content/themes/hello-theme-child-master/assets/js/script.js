/* Add explicit width and height on the logo img */
jQuery(document).ready(function($) {
    // Target the logo image element
    var logoImage = $('.mega-toggle-blocks-left .mega-menu-logo img');

    // Set explicit width and height attributes
    logoImage.attr('width', '207'); // Set your desired width in pixels
    logoImage.attr('height', '35'); // Set your desired height in pixels
});

/* Add explicit width and height on the img */
jQuery(document).ready(function($) {
    // Target Elementor images without explicit dimensions
    var elementorImages = $('.elementor-image-box-wrapper img');
    
    var elementorImagesWrapper = $('figure.elementor-image-box-img');

    // Set explicit width and height attributes for each image
    elementorImagesWrapper.each(function() {
      $(this).find('img').attr('width', '58'); // Set your desired width in pixels
      $(this).find('img').attr('height', '64'); // Set your desired height in pixels
    });
    
    
});

jQuery(document).ready(function($) {
    // Target Elementor images without explicit dimensions
    var elementorImages = $('.eael-infobox img');
    
    var elementorImagesWrapper = $('.eael-infobox');

    elementorImagesWrapper.each(function() {
        $(this).find('img').attr('width', $(this).width()); // Set your desired width in pixels
        $(this).find('img').attr('height', $(this).height()); // Set your desired height in pixels
    //   console.log('Infobox: ', $(this).width());
    //   console.log('Infobox height: ', $(this).height());
    });
    // Set explicit width and height attributes for each image
    /* elementorImagesWrapper.each(function() {
      $(this).find('img').attr('width', '58'); // Set your desired width in pixels
      $(this).find('img').attr('height', '64'); // Set your desired height in pixels
    }); */
    
    
});
/* Add alt text on the logo image */
jQuery(document).ready(function($) {
    // Select images without alt attribute within the specified container
    $('div.mega-toggle-blocks-left > div#mega-toggle-block-2 > a.mega-menu-logo > img:not([alt])').each(function() {
        // Add a descriptive alt text here
        $(this).attr('alt', 'Datadestruction logo');
    });
});
/* Add aria label on the links */
/* jQuery(document).ready(function($) {
    // Select the link elements that need to be improved for accessibility
    $('div.elementor-widget-container > div.elementor-icon-box-wrapper').each(function() {
        // console.log('Link: ', $(this));
        // Check if the link already has an aria-label attribute
        if (!$(this).find('div.elementor-icon-box-icon > a.elementor-icon').attr('aria-label')) {
            // Retrieve the link text
            var linkText = $(this).find('div.elementor-icon-box-content > h4.elementor-icon-box-title').text().trim();
            
            // Add an aria-label attribute with the link text
            $(this).find('div.elementor-icon-box-icon > a.elementor-icon').attr('aria-label', linkText);
        }
    });
}); */
/* Add label on select field */
/* jQuery(document).ready(function($) {
    // Select all select elements without associated labels
    $('select:not([aria-labelledby])').each(function() {
        // Get the ID of the select element
        var selectId = $(this).attr('id');
        
        if (selectId) {
            // Create a label element with the "for" attribute pointing to the select's ID
            if(selectId == 'input_12_8'){
                select_label = 'Services';
            }else if(selectId == 'input_12_9'){
                select_label = 'Purpose';
            }else{
                select_label = 'Select label';
            }
            var label = $('<label>').attr('for', selectId).text(select_label); // Replace 'Select Label' with your desired label text
            
            // Add CSS to hide the label visually but keep it accessible to screen readers
            label.css({
                'position': 'absolute',
                'left': '-9999px'
            });
            
            // Insert the label element before the select element
            $(this).before(label);
        }
    });
}); */
/* Phone icon aria label */
/* jQuery(document).ready(function($) {
    // Select links without discernible names
    $('a:not([aria-label]):not([aria-labelledby])').each(function() {
        
        // Check if the link has class "mega-icon" and is inside a specific div
        if ($(this).hasClass('mega-icon') && $(this).parent().is('#mega-toggle-block-3')) {
            
           $(this).attr('aria-label', 'Phone Icon Link');
            // Add an aria-label attribute to the link to provide a discernible name
           $(this).attr('aria-label', 'Phone Icon Link'); // Replace 'Phone Icon Link' with your desired label
            // Add text content to the link for screen readers
            $(this).text('Call Us: (555) 555-555'); // Replace with your desired link text 
        }
    });
}); */

document.addEventListener("DOMContentLoaded", function() {
    const widgetsToLazyLoad = document.querySelectorAll(".contact-cta-btn-contact");

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const widget = entry.target;
                // Load the widget's content or perform any desired action
                // widget.classList.remove("lazy-load-widget");
                observer.unobserve(widget);
            }
        });
    });

    // Observe each widget
    widgetsToLazyLoad.forEach(widget => {
        observer.observe(widget);
    });
});

document.addEventListener("DOMContentLoaded", function() {
    const widgetsToLazyLoad = document.querySelectorAll(".a11y-toolbar");

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const widget = entry.target;
                // Load the widget's content or perform any desired action
                // widget.classList.remove("lazy-load-widget");
                observer.unobserve(widget);
            }
        });
    });

    // Observe each widget
    widgetsToLazyLoad.forEach(widget => {
        observer.observe(widget);
    });
});

/* Open external links in new tab */
document.addEventListener("DOMContentLoaded", function() {
    var links = document.querySelectorAll("a");
    for (var i = 0; i < links.length; i++) {
        if (links[i].hostname !== window.location.hostname) {
            links[i].setAttribute("target", "_blank");
            links[i].setAttribute("rel", "noopener noreferrer");
        }
    }
});


