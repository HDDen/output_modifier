/* Preprocessor to use "data-avif"-attribute*/
if (window.localStorage.getItem('avifsupp') == '1'){
    !function(){function n(){
        var avifs = document.querySelectorAll('*[data-avif]');
        if (avifs.length){
            for (var i = 0; i < avifs.length; i++){

                var avif = avifs[i].getAttribute('data-avif');
                var attr;

                if (avifs[i].hasAttribute('data-srcset')){
                    attr = 'data-srcset';
                } else if (avifs[i].hasAttribute('data-src')){
                    attr = 'data-src';
                } else if (el.hasAttribute('data-bg')){
                    attr = 'data-bg';
                } else if (el.hasAttribute('data-poster')){
                    attr = 'data-poster';
                }

                avifs[i].setAttribute(attr, avif);
            }
        }
    }"loading"!=document.readyState?n():document.addEventListener("DOMContentLoaded",function(){n()})}();
}