(function(){
	var d = document.documentElement.classList;
	if (!(d.contains("webp-on") || d.contains("webp-off"))){
		// check localstorage
		var sto = window.localStorage;
		var localStorageWebp = sto.getItem('webpsupp');
		if (localStorageWebp == '1'){
			d.add("webp-on");
		} else if (localStorageWebp == '0'){
			d.add("webp-off");
		} else {
			var webp = "data:image/webp;base64,UklGRhYAAABXRUJQVlA4TAoAAAAvAAAAAEX/I/of";
			var img = new Image();
			img.onload = function(){d.add("webp-on");sto.setItem('webpsupp', '1')};
			img.onerror = function(){d.add("webp-off");sto.setItem('webpsupp', '0')};
			img.src = webp;
		}
	}
})();