!function(){function n(){

	function unwebp(){
		// кастомные атрибуты просто удаляем, а src придётся модифицировать
		var imgAttrs = ['data-srcset', 'data-src', 'srcset', 'src']; 
		for (var i = 0; i < imgAttrs.length; i++){
			var imgs = document.querySelectorAll('img['+imgAttrs[i]+'*=".webp"]');
			if (imgs.length){
				for (var k = 0; k < imgs.length; k++){
					if (imgAttrs[i] == 'src'){
						var attr = imgs[k].getAttribute(imgAttrs[i]);
						attr = attr.replace('.webp', '');
						imgs[k].setAttribute(imgAttrs[i], attr);
					} else {
						//imgs[k].removeAttribute(imgAttrs[i]);
						imgs[k].setAttribute(imgAttrs[i], imgs[k].src);
					}
				}
			}
		}

		var bgAttrs = ['data-background-image', 'data-bg', 'style', 'data-poster', 'poster'];
		for (var i = 0; i < bgAttrs.length; i++){
			var els = document.querySelectorAll('*['+bgAttrs[i]+'*=".webp"]');
			if (els.length){
				for (var k = 0; k < els.length; k++){
					var attr = els[k].getAttribute(bgAttrs[i]);
					attr = attr.replace('.webp', '');
					els[k].setAttribute(bgAttrs[i], attr);
				}
			}
		}
	}

	var d = document.documentElement.classList;
	if (!(d.contains("webp-on"))){
		if(!d.contains("webp-off")){
			// check localStorage
			var sto = window.localStorage;
			var localStorageWebp = sto.getItem('webpsupp');
			if (localStorageWebp == '1'){
				d.add("webp-on");
			} else if (localStorageWebp == '0'){
				d.add("webp-off");
				unwebp();
			} else {
				var webp = "data:image/webp;base64,UklGRhYAAABXRUJQVlA4TAoAAAAvAAAAAEX/I/of";
				var img = new Image();
				img.onload = function(){d.add("webp-on");sto.setItem('webpsupp','1')};
				img.onerror = function(){d.add("webp-off");sto.setItem('webpsupp','0');unwebp()};
				img.src = webp;
			}
		} else {
			unwebp();
		}
	}

}"loading"!=document.readyState?n():document.addEventListener("DOMContentLoaded",function(){n()})}();