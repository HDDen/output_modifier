(function(d, sto){
	if (!(d.contains("avif-checked"))){
		// check foк safari
		var isSafari = navigator.vendor && navigator.vendor.indexOf('Apple') > -1 &&
               navigator.userAgent &&
               navigator.userAgent.indexOf('CriOS') == -1 &&
               navigator.userAgent.indexOf('FxiOS') == -1;
		if (isSafari){
			d.add("avif-off");d.add("avif-checked");
		} else {
			// check localstorage
			var localStorageAvif = sto.getItem('avifsupp');
			if (localStorageAvif === '1'){
				d.add("avif-on");d.add("avif-checked"); // splitted for IE compatibility
			} else if (localStorageAvif === '0'){
				d.add("avif-off");d.add("avif-checked");
			} else {
				var img = new Image();
				img.onload = function(){d.add("avif-on");d.add("avif-checked");sto.setItem('avifsupp', '1')};
				img.onerror = function(){d.add("avif-off");d.add("avif-checked");sto.setItem('avifsupp', '0')};
				img.src = "data:image/avif;base64,AAAAIGZ0eXBhdmlmAAAAAGF2aWZtaWYxbWlhZk1BMUIAAADybWV0YQAAAAAAAAAoaGRscgAAAAAAAAAAcGljdAAAAAAAAAAAAAAAAGxpYmF2aWYAAAAADnBpdG0AAAAAAAEAAAAeaWxvYwAAAABEAAABAAEAAAABAAABGgAAAB0AAAAoaWluZgAAAAAAAQAAABppbmZlAgAAAAABAABhdjAxQ29sb3IAAAAAamlwcnAAAABLaXBjbwAAABRpc3BlAAAAAAAAAAIAAAACAAAAEHBpeGkAAAAAAwgICAAAAAxhdjFDgQ0MAAAAABNjb2xybmNseAACAAIAAYAAAAAXaXBtYQAAAAAAAAABAAEEAQKDBAAAACVtZGF0EgAKCBgANogQEAwgMg8f8D///8WfhwB8+ErK42A=";
			}
		}
	}
}(document.documentElement.classList, window.localStorage));