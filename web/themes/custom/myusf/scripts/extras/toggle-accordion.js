const link = document.querySelector('#remove-show');

    link.addEventListener('click', function() {
        const elements = document.querySelectorAll("[id^='collapse-accordion']");

        elements.forEach(element => {
            element.classList.toggle('show');
        });

        const isAnyElementOpen = Array.from(elements).some(element => element.classList.contains('show'));

        if (isAnyElementOpen) {
            link.textContent = "Close All";
        } else {
            link.textContent = "Open All";
        }
    });