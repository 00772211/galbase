async function xhr(url, JSON) {
	// 变更请求数据
	var data = new FormData();
	for (var key in JSON) {
		data.append(key, JSON[key]);
	}

	// 构建xhr
	var xhr = new XMLHttpRequest();
	xhr.open('POST', url, true);
	xhr.send(data);

	// 返回一个 Promise
	return new Promise((resolve, reject) => {
		xhr.onreadystatechange = () => {
		if (xhr.readyState == 4) {
			if (xhr.status == 200) {
				resolve(xhr.responseText);  // 请求成功
			} else {
				reject(new Error(`请求失败，状态码：${xhr.status}`));  // 请求失败
			}
		}
		};

		// 设置超时
		xhr.timeout = 20000;  // 设置超时为20秒

		// 处理超时情况
		xhr.ontimeout = () => {
			reject(new Error('请求超时'));  // 发生超时时 reject 错误
		};
	});
}


// var data = {
// "cmd": "send",
// "value": "1"
// };

// // 调用 xhr 请求
// xhr("/tty.php", data).then((result) => {
// 	console.log(result);  // 请求成功时处理响应
// }).catch((error) => {
// 		if (error.message === '请求超时') {
// 			console.log('请求超时，执行超时后的处理代码');
// 			// 这里可以添加你希望超时后执行的代码，比如重新发送请求、提示用户、记录日志等
// 		} else {
// 			console.log('发生了其他错误:', error);
// 	}
// })









// async function xhr(url, JSON) {
// 	// 变更请求数据
// 	var data = new FormData()
// 	for (var key in JSON) {
// 		data.append(key, JSON[key])
// 	}

// 	// 构建xhr
// 	var xhr = new XMLHttpRequest()
// 	xhr.open('POST', url, true)
// 	xhr.send(data)

// 	// 回调函数
// 	return new Promise((resolve) => {
// 		xhr.onreadystatechange = () => {
// 			if(xhr.readyState == 4 && xhr.status == 200){
// 				resolve(xhr.responseText)
// 			}
// 		}

// 		// // xhr请求超时
// 		// xhr.timeout = 20000	// ms
// 		// xhr.ontimeout = () => alert("请求超时")
// 	})
// }

// 
// 
// 使用例子：
// 
// 
// var data = {
// 	"cmd": "send",
// 	"value": "1"
// }
// xhr("/tty.php", data).then((result) => {
// 	console.log(result);
// })
