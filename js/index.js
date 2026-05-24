// 全局变量
var HTML
var NAV



// 
// 首页OP请求
// 
function request_opening() {
	console.log(`${API}/forum/random`);
	
	fetch_API("GET", `${API}/forum/random`).then(res => {
		document.querySelector(".home_page_op video").src = res.data.op;
		document.querySelector(".home_page_op video").poster = res.data.preview;
		document.querySelector(".home_page_op a").textContent = res.data.title;
		document.querySelector(".home_page_op a").href = res.data.url;
	}).catch(err => {
		console.log(`首页OP请求失败: ${err.message}`);
		setTimeout(request_opening, 1000);
	})
}
request_opening();



// 
// 最近更新
// 
function request_newest() {
	fetch_API("GET", `${API}/forum/newest`).then(res => {
		const DOM_newest_topic = document.querySelector(".newest_topic")
		res['data'].forEach((topic, i) => {
			let day = time_diff(topic['date'])
			let html = `
				<div class="card">
					<div class="auther">
						<img class="avatar" src="${topic['auther']['avatar_small']}" alt="图片加载失败">
						<img src="/data/imgs/user.png" style="height: 11px;margin-right: 5px" alt="图片加载失败">
						<a class="uname" href="/space/${topic['auther']['uid']}" title="点击进入 TA 的个人空间" target="_blank" style="color: #666666;">${topic['auther']['uname']}</a>
					</div>
					<div class="preview_content">
						<a href="/topic/${topic['tid']}" title="点击进入帖子" target="_blank">${topic['title']}</a>
					</div>
					<div class="topic_info">
						<span class="data"><img src="/data/imgs/date.png" alt="图片加载失败"> ${day}</span>
						<span class="message"><img src="/data/imgs/reply.png" alt="图片加载失败">${topic['reply_count']}</span>
						<span class="view"><img src="/data/imgs/view.png" alt="图片加载失败">${topic['view_count']}&emsp;</span>
					</div>
				</div>
			`
			DOM_newest_topic.insertAdjacentHTML("beforeend", html)

		})
		
	}).catch(err => {
		console.log(`最近更新内容请求失败: ${err.message}`);
		setTimeout(request_newest, 1000);
	});
}
request_newest();



// 
// 每日推荐5个GAL
//
function recommend() {
	fetch_API("GET", `${API}/forum/recommend`).then(res => {
		const DOM_recommend = document.querySelector(".recommend_gal_list")
		DOM_recommend.innerHTML = ""

		res['data'].forEach((topic, i) => {
			let html = `
				<div class="card">
					<img src="${topic['url']}/preview.avif" alt="图片加载失败">
					<span class="title"><a href="/topic/${topic['tid']}" target="_blank">${topic['title']}</a></span>
				</div>
			`
			DOM_recommend.insertAdjacentHTML("beforeend", html)
		})

	}).catch(err => {
		console.log(`每日推荐请求失败: ${err.message}`);
		setTimeout(recommend, 1000);
	});
}
recommend();



// 
// 获取主页各大板块信息
//
function home() {
	fetch_API("GET", `${API}/forum/home`).then(res => {
		Object.entries(res['data']['forum']).forEach(([key, item]) => {
			let fid = key
			let DOM = document.querySelector(`#fid_${fid}`)

			// 取消所有hidden
			DOM.querySelector('.cover').hidden = false
			DOM.querySelector('.num').hidden = false
			DOM.querySelector('.info').hidden = false

			// 赋值
			if (fid.includes("2-")) {
				DOM.querySelector('.cover').src = `/data/imgs/board/${fid}.gif`
			} else {
				DOM.querySelector('.cover').src = `/data/imgs/board/${fid}.png`
			}
			DOM.querySelector('.title').textContent = item.board
			DOM.querySelector('.title').href = `/forum/${fid}/0`
			DOM.querySelector('.num span').textContent = item.count
			DOM.querySelector('.topic').textContent = item.topic.title
			DOM.querySelector('.topic').href = `/topic/${item.tid}`
			DOM.querySelector('.auther').href = `/space/${item.topic.uid}`
			DOM.querySelector('.auther').textContent = `${item.topic.auther.uname}`
			let day = time_diff(item.topic.date)
			DOM.querySelector('.date').textContent = day
			DOM.querySelector('.view').textContent = item.topic.view_count
			DOM.querySelector('.reply').textContent = item.topic.reply_count
		})
	}).catch(err => {
		console.log(`主页板块请求失败: ${err.message}`);
		setTimeout(home, 1000);
	});
}
home();



// 
// 监听OP播放器窗口高度变化
// 
const op_player = document.querySelector('.home_page_op')
const resizeObserver = new ResizeObserver((entries) => {

	// 自适配高度
	const op_player_height = document.querySelector(".home_page_op").offsetHeight
	document.querySelector(".newest_topic").style.height = `${op_player_height}px`

})
resizeObserver.observe(op_player)



// 
// 获取在校学生
//
function request_online() {
	fetch_API("GET", `${API}/forum/online`).then(res => {
		const DOM_online = document.querySelector("#online ul")

		// 添加自己
		if (LOGIN == true) {
			avatar_url = get_cookie("avatar_small")
			HTML = `<li><img src="${avatar_url}" alt='在线用户头像'><a href='/space/${UID}' target='_blank'>${UNAME}（<span style='color:#00187C'>在校</span>）</a></li>`
			DOM_online.insertAdjacentHTML("beforeend", HTML)
		}

		res['data'].forEach((user, i) => {
			if (user['online'] == true) {
				HTML = `<li><img src="${user['avatar_small']}" alt='在线用户头像'><a href='/space/${user['uid']}' target='_blank'>${user['uname']}（<span style='color:#00187C'>在校</span>）</a></li>`
			} else {
				HTML = `<li><img src="${user['avatar_small']}" alt='在线用户头像'><a href='/space/${user['uid']}' target='_blank'>${user['uname']}（<span>离校</span>）</a></li>`
			}
			DOM_online.insertAdjacentHTML("beforeend", HTML)
		})
	}).catch(err => {
		console.log(`在线用户请求失败: ${err.message}`);
		setTimeout(request_online, 1000);
	})
}
request_online();


