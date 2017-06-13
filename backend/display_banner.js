function reloadBanner() {
	function onReceiveBanner() {
		document.getElementById("banner_holder").innerHTML = this.responseText;
	}
	var req=new XMLHttpRequest();
	req.addEventListener("load", onReceiveBanner);
	req.open("GET", "/backend/banner.php");
	req.send();
}
reloadBanner();