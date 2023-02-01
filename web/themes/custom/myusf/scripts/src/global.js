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

            // When you tab away from the search submit button, go to the login button, then the subnav.

            // If not logged in.
            if ($logInBtn.length) {
                $logInBtn.blur(function() {
                    if ($submenuBtn.hasClass('hideButton')) {
                        $("#sidebar_first #CollapsingNavbar .nav-link").first().focus();
                    } else {
                        $submenuBtn.focus();
                    }
                });

            } else {
                if ($("#navbarDropdownMenuLink").length) {
                    // User is logged in.
                    $submenuBtn.focus();
                }
            }
            // When leaving the subnav, go to the main content.
            $("#sidebar_first #CollapsingNavbar .nav-link").last().blur(function() {
                $("#block-myusf-content a").first().focus();
            });
            // When leaving main content area, go to right-hand sidebar.
            $("#block-myusf-content a").last().blur(function() {
                $("#sidebar_second a").first().focus();
            });


            // Fix for Ensemble
            $("[id^=contentView_]").attr("align","center");

            // Make .pdf and .txt files open in a new window.
            $(document).ready(function() {
                $("a[href$='.pdf'], a[href$='.txt']")
                    .attr("target", "_blank")
                    .attr("rel", "noopener")
                    .attr("aria-label", "Opens in new window")
            });
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
})(jQuery, Drupal);
