window.addEventListener("DOMContentLoaded", function (e) {

    let current = 0;
    let slides = document.getElementById("eb_slides").querySelectorAll("img");

    if (slides) {
        setInterval(function () {

            for (let i = 0; i < slides.length; i++) {
                slides[i].style.opacity = 0;
            }

            current = (current !== slides.length - 1) ? current + 1 : 0;
            slides[current].style.opacity = 1;

        }, 6000);
    }

}, false);