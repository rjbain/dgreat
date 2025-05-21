(function ($, Drupal) {
    Drupal.behaviors.mainJS = {
        attach: function (context, settings) {

            // Remove focus from buttons after clicking.

            $(".nav-item").mouseleave(function(){
                this.blur();
            });
            $(".carousel-control-next").mouseleave(function(){
                this.blur();
            });
            $(".carousel-control-prev").mouseleave(function(){
                this.blur();
            });

            $(".header_search").mouseleave(function(){
                this.blur();
            });

            // Make main menu keyboard accessible.
            let $dropdownLink = $('ul.navbar-nav li.dropdown button.nav-link');
            let dropdownOpenLabel = 'Open Sub-Navigation Menu';
            let dropdownCloseLabel = 'Close Sub-Navigation Menu';

            // Open the submenu if you tab to the dropdown and press the Space key.
            $($dropdownLink).keydown(function(e) {
                if (e.key === ' ' || e.key === 'Spacebar') { // Space Bar key maps to keycode `32`
                    e.preventDefault();
                    $(this).parent('li').toggleClass('show');
                    $(this).attr('aria-expanded', function (i, attr) {
                        return attr === 'true' ? 'false' : 'true';
                    });
                    $(this).attr('aria-label', function (i, attr) {
                        return attr === dropdownOpenLabel ? dropdownCloseLabel : dropdownOpenLabel;
                    });
                    $(this).parent('li').find('ul.dropdown-menu').toggleClass('show');
                }
            });

            // When you focus on a dropdown link, close any open submenus.
            $($dropdownLink).focus(function() {
                $($dropdownLink).parent('li').removeClass('show');
                $($dropdownLink).attr('aria-expanded','false');
                $($dropdownLink).attr('aria-label', dropdownOpenLabel);
                $($dropdownLink).parent('li').find('ul.dropdown-menu').removeClass('show');
            });

            let $logInBtn = $(".navbar .login-btn");
            let $submenuBtn = $("#submenuButton");
            let $submenuLinks = $("#sidebar_first .submenu .nav-link");

            // When you tab away from the search submit button, go to the login button, then the subnav.

            // If not logged in.
            if ($logInBtn.length) {
                $logInBtn.blur(function() {
                    // If the submenu button is hidden.
                    if ($submenuBtn.hasClass("hideButton")) {
                        $submenuLinks.first().focus();
                    } else {
                        $submenuBtn.focus();
                    }
                });

            }
          let dashboardButton = $(".dashboard-button");
            // If logged in, tab from dashboard button to subnav button.
          dashboardButton.on( 'keydown', function(e) {
            // If the tab key is active and the shift key is not, move forward.
            if (!e.shiftKey && e.keyCode === 9) {
              if ($submenuBtn.hasClass("hideButton")) {
                $submenuLinks.first().focus();
              } else {
                $submenuBtn.focus();
              }
            }
            });
            // If you focus on the submenu button and open it, tab next to the first link.
            $($submenuBtn).blur(function(){
                // If the submenu is closed.
                if ($submenuBtn.hasClass("collapsed")) {
                    // Go to the main content.
                    $("#block-myusf-content a").first().focus();
                } else {
                    // Otherwise go to the first subnav link.
                    $submenuLinks.first().focus();
                }
            });
            // When leaving the subnav, go to the main content.
            $submenuLinks.last().blur(function() {
                $("#block-myusf-content a").first().focus();
            });
            // When leaving main content area, go to right-hand sidebar.
            $("#block-myusf-content a").last().blur(function() {
                $("#sidebar_second a").first().focus();
            });

            // Fix for Ensemble
            $("[id^=contentView_]").attr("align","center");

        }
    };
    Drupal.behaviors.surveyModal = {
        attach: function (context, settings) {
            // Using once() to apply the myCustomBehaviour effect when you want to run just one function.
            if ($('#studentSurveyModal').length && drupalSettings.dgreatStudentSurveys.hasSeen !== true) {
                $('#studentSurveyModal').once('surveyModal').modal('show');
            }
        }
    };

  // Add our new smooth scroll to accordion behavior
  Drupal.behaviors.smoothScrollAccordion = {
    // Add initialization flag
    initialized: false,
    attach: function (context, settings) {
      // Find all accordion items - adapt selector based on USF site structure
      const accordionItems = document.querySelectorAll('[id^="accordion-"]');

      /**
       * Scrolls to element and opens accordion
       * @param {string} id - The ID of the accordion to scroll to
       */
      function scrollToAccordion(id) {
        const targetElement = document.getElementById(id);

        if (!targetElement) {
          console.warn(`Accordion with ID "${id}" not found`);
          return;
        }

        // Find the accordion header/toggle based on USF site structure
        const accordionHeader =
          targetElement.querySelector('.accordion-header') ||
          targetElement.querySelector('.ui-accordion-header') ||
          targetElement.querySelector('.field-group-format-toggler') ||
          targetElement.querySelector('.paragraph--type--accordion .field--name-field-title') ||
          targetElement.querySelector('[data-toggle="collapse"]') ||
          targetElement.querySelector('h3.ui-accordion-header') ||
          targetElement.querySelector('a.accordion-button');

        // Find the accordion content
        const accordionContent =
          targetElement.querySelector('.accordion-collapse') ||
          targetElement.querySelector('.ui-accordion-content') ||
          targetElement.querySelector('.field-group-accordion-item-body') ||
          targetElement.querySelector('.paragraph-content') ||
          (accordionHeader ? document.getElementById(accordionHeader.getAttribute('aria-controls')) : null);

        // Add margin to scroll position to account for fixed headers
        const headerOffset = 100; // Adjust this value based on your site's header height
        const elementPosition = targetElement.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

        // Smooth scroll to the accordion with offset
        window.scrollTo({
          top: offsetPosition,
          behavior: 'smooth'
        });

        // Wait for scroll to complete before opening the accordion
        setTimeout(() => {
          // Open the accordion - handle different accordion implementations
          if (accordionHeader) {
            // Check if it's a Bootstrap accordion
            if (accordionContent && accordionContent.classList.contains('collapse')) {
              // Bootstrap accordion
              if (!accordionContent.classList.contains('show')) {
                accordionHeader.click();
              }
            }
            // Check if it's a jQuery UI accordion (commonly used in USF site)
            else if (accordionHeader.classList.contains('ui-accordion-header')) {
              if (accordionHeader.classList.contains('ui-accordion-header-collapsed')) {
                accordionHeader.click();
              }
            }
            // Check if it's a field-group-accordion (common in Drupal)
            else if (accordionHeader.classList.contains('field-group-format-toggler')) {
              if (accordionHeader.getAttribute('aria-expanded') === 'false') {
                accordionHeader.click();
              }
            }
            // Generic implementation - fallback
            else {
              const isExpanded = accordionHeader.getAttribute('aria-expanded') === 'true';
              if (!isExpanded) {
                accordionHeader.click();
              }
            }
          }
        }, 500); // Allow time for the scroll to complete
      }

      // Handle anchor links within the page - avoid using once()
      // Create a flag to track if we've already attached this event
      if (!$(document).data('smoothScrollAccordionInitialized')) {
        $(document).data('smoothScrollAccordionInitialized', true);
        $(document).on('click', 'a[href^="#"]', function(e) {
          const href = this.getAttribute('href');
          if (href !== '#' && href.length > 1) {
            e.preventDefault();
            const targetId = href.substring(1);
            scrollToAccordion(targetId);

            // Update URL hash without jumping
            if (history.pushState) {
              history.pushState(null, null, href);
            } else {
              location.hash = href;
            }
          }
        });
      }

      // Check for hash in URL on page load - use a static flag to run only once
      if (context === document && window.location.hash && !Drupal.behaviors.smoothScrollAccordion.initialized) {
        // Set flag to ensure this runs only once
        Drupal.behaviors.smoothScrollAccordion.initialized = true;

        const targetId = window.location.hash.substring(1);
        // Delay to ensure DOM is fully loaded
        setTimeout(() => {
          scrollToAccordion(targetId);
        }, 300); // Longer delay for initial page load to ensure everything is rendered
      }
    }
  };

})(jQuery, Drupal);
