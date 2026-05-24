// 
// 全局变量
// 
var PATH = window.location.pathname
PATH = PATH.split("/")

const FID = PATH[2]
const PAGE = parseInt(PATH[3])
var NAV

var SORT = "tid"
if (PATH[1] == "forums") {
	SORT = PATH[4]
}
document.querySelector("#sort").value = SORT

var FILTER = "pass"
if (get_cookie("filter")) {
	FILTER = get_cookie("filter")
}



// 
// 特殊板块特殊按钮
// 
if (FID == "1-4") {
	HTML = `<button onclick="window.open('/topic/153')">上传动画</button>`
	document.querySelector(".buttons_").insertAdjacentHTML("beforeend", HTML)
} else if (FID == "2-4") {
	HTML = `<button onclick="window.open('/topic/1042')">上传音乐</button>`
	document.querySelector(".buttons_").insertAdjacentHTML("beforeend", HTML)
}



// 
// DOM还原
// 
document.querySelector(".page span").textContent = ` 当前页数： ${PAGE}`



// 
// 请求帖子
// 
function request_topics() {
	const topics_region = document.querySelector('#topics')

	var data = {
		"sort": SORT,
		"filter": FILTER
	}

	fetch_API("GET", `${API}/forum/${FID}/${PAGE}`, data).then(res => {
		document.title = res['data']['board']
		document.querySelector(".dynamic_height .title_content").innerHTML = title_format(res['data']['board'].slice(1, -1))

		// 添加每个帖子
		res['data']['topics'].forEach((topic, i) => {

			// 卡片式
			if (FID == "1-1" || FID == "1-2" || FID == "1-3" || FID == "1-4") {
				
				// 无封面处理
				if (!topic['preview']) {
					var preview = "/data/imgs/yingmei_small.jpg"
				} else {
					var preview = `${topic['url']}/preview.avif`
				}

				// 日期处理
				let day = time_diff(topic['date'])
				if (day == "今天" || day == "1天前" || day == "2天前" || day == "3天前" || day == "4天前" || day == "5天前" || day == "6天前" || day == "7天前") {
					var date_tag = `<li class="tag tag3">${day}</li>`
				} else {
					var date_tag = ""
				}

				// tag循环
				let tags_html = ""

				topic['tags'].split("|").forEach((id, i) => {
					var tag = topic['tags_decode'][id]

					// 特殊tag处理
					if (tag == "生肉") {
						tags_html += `<li class="tag tag3">${tag}</li>`
					} else {
						tags_html += `<li class="tag">${tag}</li>`
					}
				})

				// 动画板块特殊处理
				if (FID == "1-4") {
					var style_1 = `style="width: 100%"`
					var style_2 = `style="aspect-ratio: 10 / 14;"`
				} else {
					var style_1 = ""
					var style_2 = ""
				}

				// 1-1板块卡片式添加评分
				if (FID == "1-1") {
					var score_html = `<span class="score"><img src="/data/imgs/rate.png" alt="评分">${topic['score']['avg']}(${topic['score']['count']})</span>`
				} else {
					var score_html = ""
				}

				let html = `
					<div class="forum_card">
						<div class="cover" ${style_2}>
							<img src="${preview}" title="${topic['title']}" onclick="window.open('/topic/${topic['tid']}')" ${style_1} alt="帖子封面">
						</div>
						<a class="title" href="/topic/${topic['tid']}" target="_blank" title="${topic['title']}">${topic['title']}</a>
						<ul class="tags">
							${date_tag}
							${tags_html}
						</ul>
						<div class="info">
							<img class="avatar" src="${topic['auther']['avatar_small']}" loading="lazy" alt="图片加载失败">
							<a href="/space/${topic['auther']['uid']}" class="uname" target="_blank" title="查看TA的空间">${topic['auther']['uname']}</a>
							${score_html}
							<span class="reply"><img src="/data/imgs/reply.png" alt="回复数">${topic['reply_count']}</span>
							<span class="view"><img src="/data/imgs/view.png" alt="浏览数">${topic['view_count']}</span>
						</div>
					</div>
				`
				topics_region.insertAdjacentHTML("beforeend", html)

			// 列表式
			} else {
				
				// 无最新回复
				let reply_html = ``
				if (topic['reply']) {
					reply_html = `
						<span class="count">${topic['reply_count']}回复</span><br>
						<span class="date">${topic['reply']['date']}</span><br>
						<span class="content">${topic['reply']['uname']}：${topic['reply']['content']}</span>
					`
				}

				// 预览图
				var preview_html = ""
				if (topic['preview']) {
					var preview_html = ``
					let aids = topic['preview'].split('|')

					// 循环添加
					for (let i = 0; i < aids.length; i++) {
						var preview_html = preview_html + `<img src="${topic['url']}/preview_${i}.avif" title="全屏查看" onclick="fullscreen(this)" alt="帖子缩略图">`
					}
				}

				let html = `
					<div class="forum_list" id="_${topic['tid']}">
						<div class="info">
							<a class="title" href="/topic/${topic['tid']}" target="_blank">${topic['title']}</a><br>
							<span class="auther">${topic['auther']['uname']} - ${topic['date']}</span><br>
							<span class="precontent">${topic['content'].replace("<br>", "").slice(0, 40)}</span>
						</div>
						<div class="preview_img">
							${preview_html}
						</div>
						<div class="new_reply">
							${reply_html}
						</div>
					</div>
				`
				topics_region.insertAdjacentHTML("beforeend", html)
			}
		})
	}).catch(err => {
		console.log(`帖子请求失败: ${err.message}`);
		setTimeout(request_topics, 1000);
	});
}
request_topics();



// 
// 上一页
// 
var last_page_button = document.querySelector('.page .last_page')
last_page_button.addEventListener('click', function() {
	if (PAGE <= 0) {
		float_window.title("提示")
		float_window.content("没有上一页了")
		float_window.open()
	} else {
		if (SORT == "tid") {
			window.location.href = `/forum/${FID}/${PAGE - 1}`
		} else {
			window.location.href = `/forums/${FID}/${PAGE - 1}/${SORT}`
		}
		
	}
})



// 
// 下一页
// 
var next_page_button = document.querySelector('.page .next_page')
next_page_button.addEventListener('click', function() {
	if (SORT == "tid") {
		window.location.href = `/forum/${FID}/${PAGE + 1}`
	} else {
		window.location.href = `/forums/${FID}/${PAGE + 1}/${SORT}`
	}
})



// 
// 指定页数跳转
// 
const goto_page = () => {
	// 获取需要跳转的fid和page
	var page = document.querySelector('#page_number').value

	// 未填入需要跳转的page
	if (!page) {
		float_window.title("提示")
		float_window.content("请输入要跳转的页数！")
		float_window.open()
	} else {
		if (SORT == "tid") {
			window.location.href = `/forum/${FID}/${page}`
		} else {
			window.location.href = `/forums/${FID}/${page}/${SORT}`
		}

	}
}



// 
// 
// 列表式帖子缩略图添加
// 
// 
const previews_add = (tid, aids, chunk) => {
	// 获取DOM元素
	var imgs_dom = document.querySelector(`#_${tid} .preview_img`)

	// 对aids进行分割
	var aids = aids.split('|')

	// 遍历添加每一张图片
	for (let i = 0; i < aids.length; i++) {
		var html = `<img src="${chunk}/${tid}/preview_${i}.avif" title="全屏查看" onclick="fullscreen(this)" alt="帖子缩略图">`
		imgs_dom.insertAdjacentHTML("beforeend", html)
	}
}



// 
// 排序选择
// 
const sort_DOM = document.getElementById('sort');

// 添加 change 事件监听器
sort_DOM.addEventListener('change', function(e) {
	// 获取当前选择的选项的值
	const sort = e.target.value;

	if (sort == "normal") {
		window.location.href = `/forum/${FID}/${PAGE}`
	}

	if (sort == "score") {
		window.location.href = `/forums/${FID}/${PAGE}/score`
	}

})



// 
// 排除拔作
// 
const no_push_DOM = document.getElementById("no_push");

no_push_DOM.addEventListener("change", function() {

	// 请求锁，防止过量请求
	if (small_lock()) {
		return
	}

	if (no_push_DOM.checked) {
		var no_push_state = true
	} else {
		var no_push_state = false
	}

	var data = {
		"cmd": "no_push",
		"state": no_push_state
	};

	// 调用 xhr 请求
	xhr("/servers/user_config.php", data).then((result) => {
		location.reload();
	})
})



// 
// 只有拔作
// 
const only_H_DOM = document.getElementById("only_H");


only_H_DOM.addEventListener("change", function() {

	// 请求锁，防止过量请求
	if (small_lock()) {
		return
	}

	if (only_H_DOM.checked) {
		var only_H_state = true
	} else {
		var only_H_state = false
	}

	var data = {
		"cmd": "only_H",
		"state": only_H_state
	};

	// 调用 xhr 请求
	xhr("/servers/user_config.php", data).then((result) => {
		location.reload();
	})
})
