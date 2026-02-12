class float_window_control {
	constructor() {
	}

	// 创建
	create() {
		var html = `
			<div id="float_window">
				<div class="board main_board">
					<img src="/data/imgs/title_arc.png" class="title_arc">
					<div class="board_2nd">
						<header>
							<img src="/data/imgs/title_start.png" class="title_start">
							<ul class="title_content"></ul>
							<img src="/data/imgs/title_end.png" class="title_end">
							<div class="buttons_">
								<button onclick="float_window.close()" style="float: right;">关闭</button>
							</div>
						</header>
						<main>
						</main>
					</div>
				</div>
			</div>
		`
		document.body.insertAdjacentHTML("beforeend", html)

		// 总DOM获取
		const float_window_header = document.querySelector('#float_window header')
		const float_window_content = document.querySelector('#float_window .board')
		
		// 拖动功能
		float_window_header.addEventListener('mousedown', function(e) {
			var x = e.pageX - float_window_content.offsetLeft
			var y = e.pageY - float_window_content.offsetTop

			// 鼠标移动事件
			document.addEventListener('mousemove', float_window_move)
			function float_window_move(e) {
				
				// 重新赋值给float_window_content
				float_window_content.style.left = e.pageX - x + 'px'
				float_window_content.style.top = e.pageY - y + 'px'
			}

			// 鼠标松开事件
			document.addEventListener('mouseup', function() {
				document.removeEventListener('mousemove', float_window_move)
			})
		})
	}

	// 显示float_window
	open() {
		document.querySelector('#float_window').style.display = 'block'

		// 改变蒙蔽高度
		const height = document.documentElement.offsetHeight
		document.querySelector("#float_window").style.height = `${height}px`

		// 计算高度
		const top_num = (window.scrollY) + (window.innerHeight) / 2
		document.querySelector("#float_window .board").style.top = `${top_num}px`
		// document.querySelector('#float_window .board').scrollIntoView({behavior: "smooth", block: "start"})
	}

	// 关闭float_window
	close() {
		document.querySelector('#float_window').style.display = 'none'
	}

	// 修改title
	title(title) {
		var title_format = ""
		for (let i = 0; i < title.length; i++) {
			let char = title[i]
			title_format = title_format + `<li>${char}</li>`
		}
		document.querySelector('#float_window .title_content').innerHTML = title_format
	}

	// 修改内容
	content(content) {
		document.querySelector('#float_window main').innerHTML = content
	}

	// 修改float_window宽度
	width(num) {
		document.querySelector('#float_window .board').style.width = `${num}`
	}

	// 锁关闭时间
	lock(num = 3) {
		// 去除关闭按钮的onclick
		document.querySelector('#float_window .buttons_ button').textContent = `禁止关闭`
		document.querySelector('#float_window .buttons_ button').onclick = ""

		for (let i = 0; i < num + 1; i++) {
			setTimeout(() => {
				let total_num = num - i
				document.querySelector('#float_window .buttons_ button').textContent = `禁止关闭（${total_num}）`

				// 倒数结束
				if (total_num == 0) {
					document.querySelector('#float_window .buttons_ button').textContent = `关闭`
					document.querySelector('#float_window .buttons_ button').onclick = function() { float_window.close() }
				}
			}, 1000 * i)
		}
	}
}

// 加载float_window类
const float_window = new float_window_control()
// float_window.create()
// float_window.title("测试")
// float_window.width("90%")
// float_window.open()
