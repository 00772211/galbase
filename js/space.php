<script src="/js/xhr.js"></script>
<script src="/js/lock.js"></script>
<script src="/js/float_window.js"></script>


<script>
	// 悬浮窗创建
	float_window.create()

	// 
	// 添加作品GUI
	// 
	const open_add_story = () => {
		// 修改title
		float_window.title("作品添加")

		// 内容体变更
		var html = `
			<div class="add_story">
				<input type="text" placeholder="请输入你想添加的作品名">
				<button onclick="search_work()">&emsp;搜索&emsp;</button>
				<span style="color:#333" hidden>请点击下边作品进行添加</span>

				<div class="split"></div>
			</div>



		`
		float_window.content(html)

		// 打开悬浮窗
		float_window.open()
	}


	// 
	// 搜索作品
	// 
	const search_work = () => {
		// 请求锁，防止过量请求
		if (small_lock()) {
			return
		}

		var stories_region = document.querySelector(".add_story")

		// 获取搜索关键词
		kw = document.querySelector(".add_story input").value
		
		var data = {
			"cmd": "search_work",
			"kw": kw
		}

		xhr("/servers/space.php", data).then((result) => {
			stories_region.querySelectorAll('.result').forEach(el => el.remove());
			document.querySelector(".add_story span").hidden = false

			var result = JSON.parse(result)

			for (let i = 1; i <= result['count']; i++) {
				var html = `
					<div class="result" onclick="add_story(${result[i]['tid']})" title="点击添加">
						<img src="${result[i]['chunk']}/${result[i]['tid']}/preview.avif" title="点击添加" alt="作品封面">
						<p>${result[i]['title']}</p>
					</div>
				`
				stories_region.insertAdjacentHTML("beforeend", html)
			}

		})
	}



	// 
	// 确定添加作品
	// 
	const add_story = (tid) => {
		// 请求锁，防止过量请求
		if (small_lock()) {
			return
		}

		var data = {
			"cmd": "add_story",
			"tid": tid
		}

		xhr("/servers/space.php", data).then((result) => {
			alert(result)
		})
	}



	// 
	// 加载推荐作品
	// 
	var data = {
		"cmd": "request_best_stories",
		"uid": "<?php echo $_GET['uid'] ?>"
	}

	xhr("/servers/space.php", data).then((result) => {

		// 存在作品
		if (result) {
			var result = JSON.parse(result)
			
			for (let i = 0; i <= result.length - 1; i++) {

				var html = `
					<li id="_${result[i]['tid']}">
						<div class="cover">
							<div class="func" <?php
								// 非本人禁止功能页
								if (get_uid() != $_GET['uid']) {
									echo "hidden";
								}
							?>>
								<div class="move" draggable="true">移动</div>
								<div class="remove" onclick="remove_work(${result[i]['tid']})">删除</div>
							</div>

							<img src="${result[i]['chunk']}/${result[i]['tid']}/preview.avif" title="${result[i]['title']}" onclick="window.open('/topic/${result[i]['tid']}')" alt="此生最喜欢的作品之一" draggable="false">
						</div>

						<a class="work_title" href="/topic/${result[i]['tid']}" target="_blank" title="${result[i]['title']}">${result[i]['title']}</a>
					</li>
				`

				document.querySelector('.best_works .works').insertAdjacentHTML("beforeend", html)
			}

		// 不存在作品
		} else {

			// 不是作者本人
			if ("<?php echo $_GET['uid']; ?>" != "<?php echo get_uid(); ?>") {
				document.querySelector('.best_works').parentElement.hidden = true
			}
		}

		// 添加功能
		if ("<?php echo $_GET['uid']; ?>" == "<?php echo get_uid(); ?>") {
			var html = `
				<li id="_0">
					<div class="cover">
						<img src="/data/imgs/yingmei.jpg" title="点击添加一个作品" onclick="open_add_story()" alt="添加作品">
					</div>

					<a class="work_title" title="点击添加一个作品" onclick="open_add_story()" style="cursor: pointer;">点击添加一个作品</a>
				</li>
			`
			document.querySelector('.best_works .works').insertAdjacentHTML("beforeend", html)
		}
	})

</script>
