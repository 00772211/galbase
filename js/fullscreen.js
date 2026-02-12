// 
// 
//	帖子作者头像全屏
// 
// 
const fullscreen_avatar = (element) => {
	// 获取头像URL
	var avatar = element
	var url = avatar.src.replace("_small", "")
	avatar.src = url
	fullscreen(avatar)
}






// 
// 
//	帖内图片全屏
// 
// 
function fullscreen(element) {
	// 全屏模式未启用
	if (!document.fullscreenElement && !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
		// 不同浏览器的兼容性处理
		if (element.requestFullscreen) {
			element.requestFullscreen();
		} else if (element.mozRequestFullScreen) {
			element.mozRequestFullScreen();
		} else if (element.webkitRequestFullscreen) {
			element.webkitRequestFullscreen();
		} else if (element.msRequestFullscreen) {
			element.msRequestFullscreen();
		}
	} else {
		if (document.exitFullscreen) {
			document.exitFullscreen();
		} else if (document.mozCancelFullScreen) {
			document.mozCancelFullScreen();
		} else if (document.webkitExitFullscreen) {
			document.webkitExitFullscreen();
		} else if (document.msExitFullscreen) {
			document.msExitFullscreen();
		}
	}
}