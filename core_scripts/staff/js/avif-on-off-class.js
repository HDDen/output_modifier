(function(){
	var d = document.documentElement.classList;
	if (!(d.contains("avif-checked"))){
		// check localstorage
		var sto = window.localStorage;
		var localStorageWebp = sto.getItem('avifsupp');
		if (localStorageWebp == '1'){
			d.add("avif-on","avif-checked");
		} else if (localStorageWebp == '0'){
			d.add("avif-off","avif-checked");
		} else {
			var avif = "data:image/avif;base64,AAAAFGZ0eXBhdmlmAAAAAG1pZjEAAACgbWV0YQAAAAAAAAAOcGl0bQAAAAAAAQAAAB5pbG9jAAAAAEQAAAEAAQAAAAEAAAC8AAAAGwAAACNpaW5mAAAAAAABAAAAFWluZmUCAAAAAAEAAGF2MDEAAAAARWlwcnAAAAAoaXBjbwAAABRpc3BlAAAAAAAAAAQAAAAEAAAADGF2MUOBAAAAAAAAFWlwbWEAAAAAAAAAAQABAgECAAAAI21kYXQSAAoIP8R8hAQ0BUAyDWeeUy0JG+QAACANEkA=";
			var img = new Image();
			img.onload = function(){d.add("avif-on","avif-checked");sto.setItem('avifsupp', '1')};
			img.onerror = function(){d.add("avif-off","avif-checked");sto.setItem('avifsupp', '0')};
			img.src = avif;
		}
	}
})();