!function(){function n(){
    var debug_mode = false;

	// для получения режима дебага из get-параметра
	function getParameterByName(name) {
	    var match = RegExp('[?&]' + name + '=([^&]*)').exec(window.location.search);
	    return match && decodeURIComponent(match[1].replace(/\+/g, ' '));
	}
	// включаем дебаг, если есть get-параметр deb=y
	if (getParameterByName('deb') == 'y'){
		debug_mode = true;
	}

	if (debug_mode){
		var debug_output = document.createElement('div');
		debug_output.setAttribute("id", "debug_output");
		debug_output.style.padding = '10px';
		debug_output.style.border = '1px solid black';
		debug_output.style.width = '100%';
		//debug_output.style.position = 'fixed';
	    //debug_output.style.zIndex = '999';
	    //debug_output.style.bottom = '0';
	    //debug_output.style.maxHeight = '50vh';
	    debug_output.style.background = '#fff';
		debug_output.innerHTML = '<p>Debugger:</p>';
		document.getElementsByTagName('body')[0].appendChild(debug_output);
		debug_output = document.getElementById('debug_output');
	}

	function testWebP(cbk) {
	    if (debug_mode){
			debug_output.innerHTML += '<p>Мы в testWebP()</p>';
		}
	    var w = new Image();
	    w.onerror = function () {
	        if (debug_mode){
				debug_output.innerHTML += '<p>Webp не поддерживается, запускаем коллбэк</p>';
			}
	        cbk(false);
	    };
	    w.src = 'data:image/webp;base64,UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==';
	};

	function unwebp(){
		if (debug_mode){
			debug_output.innerHTML += '<p>unwebp(): start</p>';
		}

		var imgs = document.querySelectorAll('img[src*=".webp"], img[srcset*=".webp"], img[data-src*=".webp"], img[data-srcset*=".webp"]');
		//console.log(imgs);

		if (debug_mode){
			debug_output.innerHTML += '<p>unwebp(): imgs.length = '+imgs.length+'</p>';
		}

		if (imgs.length > 0){
			if (debug_mode){
				debug_output.innerHTML += '<p>unwebp(): Начало перебора imgs</p>';
			}
			var attribs = ['src', 'srcset', 'data-src', 'data-srcset'];

			for (var i = 0; i < imgs.length; i++){
				for (var k = 0; k < attribs.length; k++){

					if (imgs[i].hasAttribute(attribs[k])){
						if (debug_mode){
							debug_output.innerHTML += '<p>unwebp(): imgs['+i+'] имеет атрибут '+attribs[k]+'</p>';
						}

						var attr = imgs[i].getAttribute(attribs[k]);

						if (debug_mode){
							debug_output.innerHTML += '<p>unwebp(): '+attr+'</p>';
						}

						attr = attr.replace('.webp', '');

						if (debug_mode){
							debug_output.innerHTML += '<p>unwebp(): '+attr+'</p>';
							debug_output.innerHTML += '<p>unwebp(): --------------</p>';
						}

						imgs[i].setAttribute(attribs[k], attr);
					}

				}
			}
		}

		if (debug_mode){
			debug_output.innerHTML += '<p>unwebp(): теперь фоновые</p>';
		}

		var bgs = document.querySelectorAll('*[style*=".webp"], *[data-bg*=".webp"], *[data-background-image*=".webp"]');

		if (debug_mode){
			debug_output.innerHTML += '<p>unwebp(): bgs.length = '+bgs.length+'</p>';
		}

		if (bgs.length > 0){
			if (debug_mode){
				debug_output.innerHTML += '<p>unwebp(): старт перебора bgs</p>';
			}
			var attribs = ['style', 'data-bg', 'data-background-image'];
			for (var i = 0; i < bgs.length; i++){
				for (var k = 0; k < attribs.length; k++){

					if (bgs[i].hasAttribute(attribs[k])){

						if (debug_mode){
							debug_output.innerHTML += '<p>unwebp(): bgs['+i+'] имеет атрибут '+attribs[k]+'</p>';
						}

						var attr = bgs[i].getAttribute(attribs[k]);

						if (debug_mode){
							debug_output.innerHTML += '<p>unwebp(): '+attr+'</p>';
						}

						attr = attr.replace('.webp', '');

						if (debug_mode){
							debug_output.innerHTML += '<p>unwebp(): '+attr+'</p>';
							debug_output.innerHTML += '<p>unwebp(): --------------</p>';
						}
						bgs[i].setAttribute(attribs[k], attr);
					}

				}
			}
		}
	}

	if (debug_mode){
		debug_output.innerHTML += '<p>Запуск testWebp()</p>';
	}
	testWebP(function(support) {
	    if (!support){
	    	unwebp();
	    }
	});
}"loading"!=document.readyState?n():document.addEventListener("DOMContentLoaded",function(){n()})}();