// 
// 
//	获取cookie
// 
// 
const get_cookie = (name) => {
	// 将cookie字符串拆分成一个名值对数组
	const kvArray = document.cookie.split(';');

	for (i=0; i < kvArray.length; i++) {

		const kv = kvArray[i].split('=');
		// 移除名称中的空格
		const cookieName = kv[0].trim();

		if (cookieName === name) {
			return decodeURIComponent(kv[1]);
		}
	}
	return null;
}

// 
// 
// 设置cookie
// 
// 
function set_cookie(name, value, days = 99999999, path = '/') {
	const expires = new Date(Date.now() + days * 864e5).toUTCString();
	document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=${path}`;
  }

// 使用方式
//   get_cookie("last_lock")
//   set_cookie('topic_title', topic_title);