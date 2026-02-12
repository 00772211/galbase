// 
// 
// 使用要导入get_cookie.js
// 
// 
function lock() {
	// 获取上一个请求时间戳
	var last_timestamp = get_cookie("last_lock")

	// 获取当前时间
	var timestamp = Math.floor(Date.now() / 1000)

	// 锁触发
	if (timestamp - last_timestamp < 3) {
		alert("本次请求过于频繁已阻止，请等待3秒后再进行！")
		return 1

	// 上新锁
	} else {
		// 添加时间戳到cookie
		var date = new Date()
		date.setDate(date.getDate() + 30) // 设置有效期为30天
		var expires = 'expires=' + date.toUTCString()
		document.cookie = `last_lock=${timestamp};` + expires + '; path=/'
	}
}

function small_lock() {
	// 获取上一个请求时间戳
	var last_timestamp = get_cookie("last_lock")

	// 获取当前时间
	var timestamp = Math.floor(Date.now() / 1000)

	// 锁触发
	if (timestamp - last_timestamp < 1) {
		alert("本次请求过于频繁已阻止，请等待1秒后再进行！")
		return 1

	// 上新锁
	} else {
		// 添加时间戳到cookie
		var date = new Date()
		date.setDate(date.getDate() + 30) // 设置有效期为30天
		var expires = 'expires=' + date.toUTCString()
		document.cookie = `last_lock=${timestamp};` + expires + '; path=/'
	}
}















