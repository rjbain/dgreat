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

  Drupal.behaviors.smoothScrollAccordion = {
    initialized: false,
    attach: function (context, settings) {

      function findAccordionHeader(element) {
        const selectors = [
          '.accordion-header',
          '[data-toggle="collapse"]'
        ];
        return selectors.reduce((found, sel) => found || element.querySelector(sel), null);
      }

      function findAccordionContent(element, header) {
        const selectors = [
          '.accordion-collapse',
          '.ui-accordion-content',
          '.field-group-accordion-item-body',
          '.paragraph-content'
        ];
        let content = selectors.reduce((found, sel) => found || element.querySelector(sel), null);
        if (!content && header?.getAttribute('aria-controls')) {
          content = document.getElementById(header.getAttribute('aria-controls'));
        }
        return content;
      }

      function openAccordion(id) {
        const targetElement = document.getElementById(id);
        if (!targetElement) {
          console.warn(`Accordion with ID "${id}" not found`);
          return;
        }

        const header = findAccordionHeader(targetElement);
        const content = findAccordionContent(targetElement, header);

        if (!header) return;

        const isBootstrap = content?.classList.contains('collapse');
        const isJQueryUI = header.classList.contains('ui-accordion-header');
        const isFieldGroup = header.classList.contains('field-group-format-toggler');

        if (
          (isBootstrap && !content.classList.contains('show')) ||
          (isJQueryUI && header.classList.contains('ui-accordion-header-collapsed')) ||
          (isFieldGroup && header.getAttribute('aria-expanded') === 'false') ||
          (!isBootstrap && !isJQueryUI && !isFieldGroup && header.getAttribute('aria-expanded') !== 'true')
        ) {
          header.click();
        }
      }

      if (!$(document).data('smoothScrollAccordionInitialized')) {
        $(document).data('smoothScrollAccordionInitialized', true);

        $(document).on('click', 'a[href^="#"]', function (e) {
          const href = this.getAttribute('href');
          if (href && href !== '#' && href.length > 1) {
            e.preventDefault();
            const targetId = href.substring(1);
            openAccordion(targetId);
          }
        });
      }

      if (context === document && window.location.hash && !Drupal.behaviors.smoothScrollAccordion.initialized) {
        Drupal.behaviors.smoothScrollAccordion.initialized = true;

        const targetId = window.location.hash.substring(1);
        setTimeout(() => {
          openAccordion(targetId);
        }, 300); // Delay ensures DOM is ready
      }
    }
  };



})(jQuery, Drupal);
