(function(d,sto){
	if (!(d.contains("webp-on") || d.contains("webp-off"))){
		// check localstorage
		const localStorageWebp = sto.getItem('webpsupp');
		if (localStorageWebp === '1'){
			d.add("webp-on");
		} else if (localStorageWebp === '0'){
			d.add("webp-off");
		} else {
			const img = new Image();
			img.onload = function(){d.add("webp-on");sto.setItem('webpsupp', '1')};
			img.onerror = function(){d.add("webp-off");sto.setItem('webpsupp', '0')};
			img.src = "data:image/webp;base64,UklGRhYAAABXRUJQVlA4TAoAAAAvAAAAAEX/I/of";
		}
	}
}(document.documentElement.classList, window.localStorage));