(function(){
	var preload_selectors = '';
	document.getElementsByTagName("body")[0].setAttribute('data-instant-whitelist', '');
	var preload = document.querySelectorAll(preload_selectors);
	for (var i = 0; i < preload.length; i++) {
		preload[i].setAttribute('data-instant', '');
	}
})();
