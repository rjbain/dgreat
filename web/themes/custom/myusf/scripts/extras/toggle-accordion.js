// Select the link element by its id 'toggle-all'
const link = document.querySelector('#toggle-all');

// Function to update the link text based on the state of accordion elements
function updateLinkText() {
    const elements = document.querySelectorAll("[id^='collapse-accordion']");
    const isAnyElementOpen = Array.from(elements).some(element => element.classList.contains('show'));
    link.textContent = isAnyElementOpen ? "Close All" : "Open All";
}

// Add an event listener for the click event on the link
link.addEventListener('click', function() {
    // Select all elements whose id starts with 'collapse-accordion'
    const elements = document.querySelectorAll("[id^='collapse-accordion']");

    // Check if any element is currently open (has the 'show' class)
    const isAnyElementOpen = Array.from(elements).some(element => element.classList.contains('show'));

    if (isAnyElementOpen) {
        // If any element is open, close only those elements that are currently open
        elements.forEach(element => {
            if (element.classList.contains('show')) {
                element.classList.remove('show');
            }
        });
        link.textContent = "Open All";
    } else {
        // If all elements are closed, open only those elements that are currently closed
        elements.forEach(element => {
            if (!element.classList.contains('show')) {
                element.classList.add('show');
            }
        });
        link.textContent = "Close All";
    }
});

// Set up a MutationObserver to watch for changes to the 'show' class on each element
const elements = document.querySelectorAll("[id^='collapse-accordion']");
elements.forEach(element => {
    const observer = new MutationObserver(updateLinkText);
    observer.observe(element, { attributes: true, attributeFilter: ['class'] });
});