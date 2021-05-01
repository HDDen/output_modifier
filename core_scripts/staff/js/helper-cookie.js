!function(){function n(){
	// Нужно проверить/установить куки.
	// Длительность куки будет 7 дней (на случай, если посетитель вернётся с обновленным браузером) и распространяться на поддомены
	// 1) Проверяем, есть ли кука "webpactive"
	// 2) Если есть, закругляемся.
	// 3) Если нет, проверяем класс 'webp-on' и 'webp-off', если класса нет - проверяем сами.
	// 4) Если нет поддержки, просто создаём куку с значением false, path=/;, max-age=604800

	function getCookie(name) {
	  var matches = document.cookie.match(new RegExp(
	    "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
	  ));
	  return matches ? decodeURIComponent(matches[1]) : undefined;
	}

	// нужно сделать так, чтобы изначально всё передавалось в объекте
	function setCookie(options) {
	  var name = options.name || false;
	  var value = options.value || false;

	  // проверка на пустое имя или значение
	  if (!name || !value){
	  	return false;
	  }

	  // сюда определим дополнительные параметры
	  var additionalParams = {
	  	'path': '/',
	  	'max-age': 604800,
	  };

	  if (options.hasOwnProperty('domain')){
	  	additionalParams.domain = options.domain;
	  }

	  if (options.hasOwnProperty('expires')) {
	    additionalParams.expires = options.expires.toUTCString();
	  }


	  var updatedCookie = encodeURIComponent(name) + "=" + encodeURIComponent(value);

	  for (var optionKey in additionalParams) {
	    updatedCookie += "; " + optionKey;
	    var optionValue = additionalParams[optionKey];
	    if (optionValue !== true) {
	      updatedCookie += "=" + optionValue;
	    }
	  }

	  document.cookie = updatedCookie;
	}

	var cookie = getCookie('webpactive');
	if (cookie === undefined ){

		var classMarker = document.querySelectorAll('.webp-on, .webp-off');
		if (classMarker.length > 0){

			classMarker = classMarker[0];
			if (classMarker.classList.contains('webp-off')){
				setCookie({'name': 'webpactive', 'value': 'false'}); //, {'domain': 'esnp24.ru'}
				console.log('webp: cookie is setted as false by DOM-checking'); // debug
			} else if (classMarker.classList.contains('webp-on')){
				setCookie({'name': 'webpactive', 'value': 'true'});
				console.log('webp: cookie is setted as true by DOM-checking'); // debug
			}
		} else {
			// проверяем поддержку сами
			var supportsWebP=function(){"use strict";return new Promise(function(A){var n=new Image;n.onerror=function(){return A(!1)},n.onload=function(){return A(1===n.width)},n.src="data:image/webp;base64,UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAwA0JaQAA3AA/vuUAAA="}).catch(function(){return!1})}();
			supportsWebP.then(function(supported){
				if (supported) {
			    	setCookie({'name': 'webpactive', 'value': 'true'});
			    	console.log('webp: cookie is setted as true by manual checking'); // debug
			    } else {
			    	setCookie({'name': 'webpactive', 'value': 'false'});
			    	console.log('webp: cookie is setted as false by manual checking'); // debug
			    }
			});
		}
	}

	// дебаг
	if (cookie !== undefined ){
		console.log('webp: cookie is setted as ' + cookie);
	}
}"loading"!=document.readyState?n():document.addEventListener("DOMContentLoaded",function(){n()})}();