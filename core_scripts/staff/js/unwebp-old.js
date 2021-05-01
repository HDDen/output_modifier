!function(){function n(){

	function unwebp(){
		var imgs = document.querySelectorAll('img[src*=".webp"], img[srcset*=".webp"], img[data-src*=".webp"], img[data-srcset*=".webp"]');

		if (imgs.length > 0){
			var attribs = ['src', 'srcset', 'data-src', 'data-srcset'];

			for (var i = 0; i < imgs.length; i++){
				for (var k = 0; k < attribs.length; k++){

					if (imgs[i].hasAttribute(attribs[k])){
						var attr = imgs[i].getAttribute(attribs[k]);
						attr = attr.replace('.webp', '');
						imgs[i].setAttribute(attribs[k], attr);
					}

				}
			}
		}

		var bgs = document.querySelectorAll('*[style*=".webp"], *[data-bg*=".webp"], *[data-background-image*=".webp"]');
		if (bgs.length > 0){
			var attribs = ['style', 'data-bg', 'data-background-image'];
			for (var i = 0; i < bgs.length; i++){
				for (var k = 0; k < attribs.length; k++){

					if (bgs[i].hasAttribute(attribs[k])){
						var attr = bgs[i].getAttribute(attribs[k]);
						attr = attr.replace('.webp', '');
						bgs[i].setAttribute(attribs[k], attr);
					}

				}
			}
		}
	}

	var d = document.documentElement.classList;
	if (!(d.contains("webp-on"))){
		if(!d.contains("webp-off")){
			var webp = "data:image/webp;base64,UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==";
			var img = new Image();
			img.onload = function(){d.add("webp-on")};
			img.onerror = function(){d.add("webp-off"); unwebp()};
			img.src = webp;
		} else {
			unwebp();
		}
	}

}"loading"!=document.readyState?n():document.addEventListener("DOMContentLoaded",function(){n()})}();