// 
// 全局变量
// 
var YEAR = new Date().getFullYear();
const DOM_year = document.querySelector("#year")
const DOM_msgs = document.querySelector(".msgs")
var HTML



// 
// 计算年份
// 
for (let i = 2023; i <= YEAR; i++) {
	if (i == YEAR) {
		HTML = `<option selected value="${i}">${i}年</option>`
	} else {
		HTML = `<option value="${i}">${i}年</option>`
	}
		
	DOM_year.insertAdjacentHTML("afterbegin", HTML)
}



// 
// 请求消息
// 
function request_msg() {
	fetch_API("GET", `${API}/user/msg/${YEAR}`).then(res => {
		if (res['error']) {
			float_window.title("错误")
			float_window.content(`${res['error']}`)
			float_window.open()
			return
		}

		DOM_msgs.innerHTML = ``

		res['data'].forEach((msg, i) => {
			if (msg['read'] == true) {
				HTML = `<li><span class="tag">已读</span>${msg['date']} -> ${msg['content']}</li>`
			} else {
				HTML = `<li><span class="tag tag3">未读</span>${msg['date']} -> ${msg['content']}</li>`
			}
			DOM_msgs.insertAdjacentHTML("beforeend", HTML)
		})	
	}).catch(err => {
		console.log(`信息请求失败: ${err.message}`)
		setTimeout(request_msg, 1000)
	})
}

if (LOGIN == true) {
	request_msg()
} else {
	float_window.title("提示")
	float_window.content("未登录，无个人消息，3秒后自动返回主页")
	float_window.open()
	setTimeout(() => {
		window.location.href = "/"
	}, 3000)
}



// 
// 更换年份信息
// 
DOM_year.addEventListener('change', function(e) {
	YEAR = parseInt(e.target.value)
	request_msg()
})



// 
// 标为已读
// 
const finish_read = () => {
	fetch_API("PUT", `${API}/user/msg/${YEAR}`).then(res => {
		if (res['error']) {
			float_window.title("错误")
			float_window.content(`${res['error']}`)
			float_window.open()
			return
		}

		DOM_msgs.querySelectorAll(".tag3").forEach(el => {
			el.classList.remove("tag3")
			el.textContent = "已读"
		})
	}).catch(err => {
		console.log(`已读失败: ${err.message}`)
		setTimeout(finish_read, 1000)
	})
}