!function(){function n(){

	function processAttrs(where){
		var where = where || document;

		try {
			// детект префикса пути для webp
			var prefix = document.querySelector('[data-webpprefix]');
			if (prefix){
				prefix = prefix.getAttribute('data-webpprefix');
			}

			// кастомные атрибуты просто удаляем, а src придётся модифицировать
			var imgAttrs = ['data-srcset', 'data-src', 'srcset', 'src']; 
			for (var i = 0; i < imgAttrs.length; i++){
				var imgs = where.querySelectorAll('img['+imgAttrs[i]+'*=".webp"]');
				if (imgs.length){
					for (var k = 0; k < imgs.length; k++){
						if (imgAttrs[i] == 'src'){
							var attr = imgs[k].getAttribute(imgAttrs[i]);
							attr = attr.replace('.webp', '');
							if (prefix){
								attr = attr.replace(prefix, '');
							}
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
				var els = where.querySelectorAll('*['+bgAttrs[i]+'*=".webp"]');
				if (els.length){
					for (var k = 0; k < els.length; k++){
						var attr = els[k].getAttribute(bgAttrs[i]);
						attr = attr.replace('.webp', '');
						if (prefix){
							attr = attr.replace(prefix, '');
						}
						els[k].setAttribute(bgAttrs[i], attr);
					}
				}
			}
		} catch (error) {
			console.log(error, where);
		}
	}

	function unwebp(){
		// Init
		processAttrs();

		// mutationObserver для обработки вновь прибывших
		var webpObserver = new MutationObserver(function(records){
			for (var i = 0; i < records.length; i++){
				if (records[i]['addedNodes']){
					for (var k = 0; k < records[i]['addedNodes'].length; k++){
						if (records[i]['addedNodes'][k].nodeName != '#text'){
							processAttrs(records[i]['addedNodes'][k]);
						}
					}
				}
			}
		});
		webpObserver.observe(document.body, {
			childList: true, // наблюдать за непосредственными детьми
			subtree: true, // и более глубокими потомками
		});
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