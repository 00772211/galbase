// 全局变量
const PATH = window.location.pathname
const TID = parseInt(PATH.split("/").pop())

var TOPIC
var NAV
var DEVELOPER
var SCORES
if (LOGIN == true) {
	var REPLY_LOCK = false
} else {
	var REPLY_LOCK = true
}
var COLLECTION_STATE
var HTML
var REMOVE_KOHARU = false



// 
// 未登录取消功能按钮
// 
if (LOGIN == false) {
	document.querySelector(".dynamic_height .buttons_").hidden = true
}



// 
// 去掉小春
// 
if (get_cookie("remove_koharu") == true) {
	REMOVE_KOHARU = true
}



// 
// 取消图片防剧透
// 
if (get_cookie("no_imgs_defense") == true) {
	const css = `
		.defense_img img {
			filter: blur(0px) !important;
		}
	`
	const style = document.createElement("style");
	style.textContent = css;
	document.head.appendChild(style)
}


// 
// 请求帖子数据
// 
function request_topic() {
	fetch_API("GET", `${API}/topic/${TID}`, {tags_decode: true}).then(res => {
		if (res['error']) {
			float_window.title("错误")
			float_window.content("该tid未找到对应的帖子内容！3秒后将自动返回主页！")
			float_window.open()
			setTimeout(() => {
				window.location.href = "/"
			}, 3000)
			return
		}
		log("帖子数据", res)

		TOPIC = res['data']['topic']
		SCORES = res['data']['scores']

		// 取消hidden
		if (TOPIC['fid'] == "1-1") {
			document.querySelector("#rate").hidden = false
		}

		// 更新浏览器标题
		document.title = TOPIC['title'].replaceAll("」「", " ").replaceAll("「", "").replaceAll("」", "") + " galgame资源 / GALBase论坛"

		// 更新标题
		document.querySelector(".main_board .title").textContent = TOPIC['title']

		// 帖子时间
		TOPIC['last_modify'] = format_date(TOPIC['last_modify'])
		var send_topic_time_diff = time_diff(TOPIC['date'])
		var last_modify_time_diff = time_diff(TOPIC['last_modify'])

		if (send_topic_time_diff == last_modify_time_diff) {
			HTML = `浏览数: ${TOPIC['view_count']} 回复数: ${TOPIC['reply_count']} 发布于: ${TOPIC['date']}（${send_topic_time_diff}）`
		} else {
			HTML = `浏览数: ${TOPIC['view_count']} 回复数: ${TOPIC['reply_count']} 发布于: ${TOPIC['date']}（${send_topic_time_diff}） 最后更新: ${TOPIC['last_modify']}（${last_modify_time_diff}）`
		}
		document.querySelector(".topic_info").innerHTML = HTML

		// 显示tag
		if (TOPIC['tags']) {
			TOPIC['tags'].split("|").forEach((id, i) => {
				var tag = TOPIC['tags_decode'][id]
				let tag_html = `<span class="tag">${tag}</span>`
				document.querySelector("#tags").insertAdjacentHTML("beforeend", tag_html)
			})
		}

		// 特殊格式{?info?}需要隐藏顶部的tag
		// 注：这里为什么上面先做显示tag下面又做隐藏不写一起，是因为后续的帖子内容解析需要依赖hidden起来的tag
		if (TOPIC['content'].includes("{?info")) {
			document.querySelector("#tags").hidden = true
		}

		// 若tid是负数，代表旧站帖子，提示警告信息
		if (TID < 0) {
			float_window.title("提示")
			float_window.content("你当前浏览的帖子为旧站迁移至新站的保留贴，帖内的信息已经过期且不再更新，请在新站搜索相关标题找到新帖")
			float_window.open()				
		}

		// 解析帖子内容
		topic_content_parse(TOPIC['content'])

		// 更新导航栏
		update_nav_auther()

		// 更新帖子评分
		update_rating()

	}).catch(err => {
		log("帖子请求失败", err)
		send_danmuku("错误", "帖子请求失败！自动重试中！")
		setTimeout(request_topic, 1000)
	});
}
request_topic()



// 
// 更新左侧导航栏
// 
const update_nav_auther = () => {

	// 在线时间
	const online_time = Math.round(TOPIC['auther']['online_time'] / 60)

	HTML = `
		<span class="auther">
			<a href="/space/${TOPIC['uid']}" target="_blank"><b>${TOPIC['auther']['uname']}</b></a><br>
			<img class="avatar" src="${TOPIC['auther']['avatar_medium']}" onclick="fullscreen_avatar(this)" loading="lazy" alt="作者头像">
			<p class="sign">${TOPIC['auther']['sign']}</p>
		</span>

		<span class="auther_data">
			<ul>
				<li>学生证UID: ${TOPIC['uid']}</li>
				<li>在校时间: >${online_time}小时</li>
				<li>身份: ${TOPIC['auther']['identity']}</li>
				<li title="学分 = 已推完GAL数总和">学分: ${TOPIC['auther']['credit']}点</li>
				<li>学年: ${TOPIC['auther']['academic_year']}</li>
				<li>奖学金: ${TOPIC['auther']['schoolship']}呜溜</li>
				<li title="风纪执行 = 删除别人帖数">风纪执行: ${TOPIC['auther']['judment_count']}次</li>
				<li title="奶酪味鸡胸肉猫罐头 = 发帖数">奶酪味鸡胸肉猫罐头: ${TOPIC['auther']['canned_count']}罐</li>
				<li>注册时间: ${TOPIC['auther']['register_time']}</li>
				<li>最后登录: ${TOPIC['auther']['last_login_time']}</li>
			</ul>
		</span>

		<span class="love_img">
			<img src="" onclick="fullscreen(this)" loading="lazy" alt="图片加载失败">
		</span>

		<span onclick="boards_list(this, 101)"><img src="${SRC}/arrow.png" style="height: 10px;" alt="图片加载失败">此生挚爱</span>
		<span id="boards_list_101" style="display:block;">
			<ul>
				<li>${TOPIC['auther']['best_love_story']}</li>
			</ul>
		</span>

		<span onclick="boards_list(this, 102)"><img src="${SRC}/arrow.png" style="height: 10px;" alt="图片加载失败">正在推进</span>
		<span id="boards_list_102" style="display:block;">
			<ul>
				<li>${TOPIC['auther']['playing_story']}</li>
			</ul>
		</span>

		<span onclick="boards_list(this, 103)"><img src="${SRC}/arrow.png" style="height: 10px;" alt="图片加载失败">强烈推荐</span>
		<span id="boards_list_103" style="display:block;">
			<ul id="recommend">
			</ul>
		</span>
	`
	document.querySelector("#auther").innerHTML = HTML

	// 更新签名图片
	if (TOPIC['auther']['sign_img']) {
		document.querySelector("#auther .love_img img").src = TOPIC['auther']['sign_img']
	} else {
		document.querySelector("#auther .love_img img").src = BG
	}

	// 更新推荐
	if (TOPIC['auther']['recommend_stories']) {
		const DOM_recommend = document.querySelector("#recommend")
		if (TOPIC['auther']['recommend_stories'].includes("|")) {
			var recommends = TOPIC['auther']['recommend_stories'].split("|")
			recommends.forEach((story, i) => {
				HTML = `
					<li>${story}</li>
				`
				DOM_recommend.insertAdjacentHTML("beforeend", HTML)
			})
		} else {
			DOM_recommend.innerHTML = `<li>${TOPIC['auther']['recommend_stories']}</li>`
		}
	}
}



// 
// 帖子内容解析
// 
const topic_content_parse = (content) => {
	
	// 判断是否为动画板块
	const ep_TAG = Object.values(TOPIC['tags_decode']).filter(value => value.includes("ep"))[0]
	if (ep_TAG) {
		// 从tag中获取总集数
		var ep = ep_TAG.replace("ep", "")

		// 根据集数构建集数html
		var ep_code = ``
		for (let i = 1; i <= ep; i++) {
			var ep_code = ep_code + `<span onclick="ep_goto(${i})" id="ep${i}">${i}</span>`
		}
		var ep_code = `<div id="ep">` + ep_code + `</div>`

		var html = `
			<video id="anime_player" src="" poster="${BG}" preload="auto" allow="autoplay; encrypted-media" allowfullscreen="true" controls="" controlslist="nodownload"></video>
		` + ep_code

		document.querySelector('#content').insertAdjacentHTML("beforeend", html)
	}



	// {?info?}
	if (content.includes("{?info")) {
		// 获取{?info?}内容
		var regex = /{\?info\s*([\s\S]*?)\s*\?\}/
		var info = content.match(regex)[1]

		// 拆分数据
		var gal_info = info.split('\n')
		// [0]	->	开发
		// [1]	->	流程
		// [2]	->	发行日期
		// [3]	->	适合游玩季节
		// [4]	->	背景logo
		// [5]	->	顶部OP

		// 获取各项具体值
		DEVELOPER = gal_info[0].match(/.+?:(.*)/)[1]
		request_developer_works()
		var play_time = gal_info[1].match(/.+:(.*)/)[1]
		var releases_date = gal_info[2].match(/.+:(.*)/)[1]
		var season = gal_info[3].match(/.+:(.*)/)[1]
		var logo = gal_info[4].match(/.+:(.*)/)[1]
		var vid = gal_info[5].match(/.+:(.*)/)[1]

		// logo判定是否为动态
		if (logo.length > 1) {

			// .gif后缀 logo不做后缀
			if (logo.includes(".gif")) {
				var path = `Developer/${logo}`
			// 正常png后缀
			} else {
				var path = `Developer/${logo}.png`
			}
		} else {
			var path = `news.png`
		}

		// tags获取
		const tags_html = document.querySelector("#tags").innerHTML

		// 是否有OP
		var op_html = ``
		if (vid) {
			var op_html = `
				<div class="player_window">
					<video class="player" src="${TOPIC['url']}/${vid}.mp4" poster="${TOPIC['url']}/${TOPIC['preview']}.avif" preload="auto" allow="autoplay; encrypted-media" allowfullscreen="true" controls controlsList="nodownload"></video>
				</div>
			`
		}

		var content = content.replace(regex, `
			<ul class="gal_info" style="background: url(${SRC}/${path}) no-repeat center center">
				<li>主题:${tags_html}</li>
				<li>开发:${DEVELOPER}</li>
				<li>流程:${play_time}</li>
				<li>发行日期:${releases_date}</li>
				<li>适合游玩季节:${season}</li>
			</ul>
			${op_html}
		`)
	}

	// {?text?}
	if (content.includes("{?text")) {
		var regex = /{\?text\s*([\s\S]*?)\s*\?\}/
		var content = content.replace(regex, `<div class="gal_info_text">$1</div>`)
	}

	// 
	// 生成防剧透class，匹配[_d *]的正则表达式
	// 
	var content = content.replace(/\[_d\s+([^\[\]]+)\]/g, function(match, p1) {
		return '<div class="defense_img">' + p1 + '</div>';
	})

	// 
	// 对图片code进行修饰，匹配 {_i*|*} 的正则表达式
	// 
	content = content.replace(
		/{_i(\d+)(?:\|(\d+))?}/g,
		(match, imgA, imgB) => {

			const imgId = (REMOVE_KOHARU && imgB)
				? imgB
				: imgA

			return `<img src="${TOPIC['url']}/${imgId}.avif"
						id="_${imgId}"
						onclick="fullscreen(this)"
						loading="lazy">`
		}
	)

	// 
	// 对图片code进行修饰，匹配 {_bigi*} 的正则表达式
	// 
	content = content.replace(
		/{_bigi(\d+)(?:\|(\d+))?}/g,
		(match, imgA, imgB) => {

			const imgId = (REMOVE_KOHARU && imgB)
				? imgB
				: imgA

			return `<img class="big_img" src="${TOPIC['url']}/${imgId}.avif"
						id="_${imgId}"
						onclick="fullscreen(this)"
						loading="lazy">`
		}
	)

	// 
	// 对视频code进行修饰，匹配 {_v*} 的正则表达式
	// 
	var	html_code = `
		<div class="player_window">
			<video class="player" src="${TOPIC['url']}/$2.mp4" poster="${BG}" preload="auto" allow="autoplay; encrypted-media" allowfullscreen="true" controls controlsList="nodownload"></video>
		</div>
	`
	var content = content.replace(/{(_v(\d+))}/g, html_code)

	// 
	// 对视频code进行修饰，匹配 {_video*} 的正则表达式
	// 
	var	html_code = `
		<div class="player_window">
			<video class="player" src="$1" poster="${BG}" preload="auto" allow="autoplay; encrypted-media" allowfullscreen="true" controls controlsList="nodownload"></video>
		</div>
	`
	var content = content.replace(/{_video([^}]+)}/g, html_code)

	// 
	// 对视频code进行修饰，匹配 {_smallvideo*} 的正则表达式
	// 
	var	html_code = `
		<div class="player_window player_window_small">
			<video class="player" src="$1" poster="${BG}" preload="auto" allow="autoplay; encrypted-media" allowfullscreen="true" controls controlsList="nodownload"></video>
		</div>
	`
	var content = content.replace(/{_smallvideo([^}]+)}/g, html_code)

	// 
	// 对图片code进行修饰，匹配 {_img*} 的正则表达式
	// 
	var	html_code = `<img src="$1" id="_$1" onclick="fullscreen(this)" loading="lazy">`
	var content = content.replace(/{_img([^}]+)}/g, html_code)

	// 
	// 对跳转{_gototid}进行修饰，匹配 {_goto*} 的正则表达式
	// 
	var regex = /{(_goto(\d+))}/g
	var content = content.replace(regex, `<a href="/topic/$2" target="_blank">跳转</a>`)

	// 
	// 对站内音乐{_m}进行修饰，匹配 {_m*} 的正则表达式
	// 
	var regex = /{(_m(\d+))}/g
	var content = content.replace(regex, `（功能暂未开发）`)

	// 
	// 对站内音乐{_music}进行修饰，匹配 {_music*} 的正则表达式
	// 
	var regex = /{(_music(\d+))}/g
	var content = content.replace(regex, `（功能暂未开发2）`)

	// 
	// 对网易云音乐{_wyy}进行修饰，匹配 {_wyy*} 的正则表达式
	// 
	var regex = /{(_wyy(\d+))}/g
	var content = content.replace(regex, `<iframe frameborder="no" border="0" marginwidth="0" marginheight="0" width="100%" height="88" src="https://music.163.com/outchain/player?
type=2&id=$2&auto=0&height=66"></iframe>`)

	// 
	// 对网易云音乐{_subtitle}进行修饰，匹配 {_wyy*} 的正则表达式
	// 
	var regex = /{_subtitle([^}]+)}/g
	var content = content.replace(regex, `<div class="sub_title"><span class="title_text">$1</span><span class="particle p1"></span><span class="particle p2"></span><span class="particle p3"></span></div>`)

	// 
	// 对网易云音乐{_wyys}进行修饰，匹配 {_wyys*} 的正则表达式
	// 
	var regex = /{(_wyys(\d+))}/g
	var content = content.replace(regex, `<iframe frameborder="no" border="0" marginwidth="0" marginheight="0" width="100%" height=450 src="//music.163.com/outchain/player?type=0&id=$2&auto=0&height=430"></iframe>`)

	// 
	// 对{?pre *?}进行修饰，匹配 {?pre *?} 的正则表达式
	// 
	// 匹配 {?pre ... ?} 中的内容并替换为 <pre>...</pre>
	var regex = /{\?pre\s*([\s\S]*?)\s*\?}/g;
	var content = content.replace(regex, "<pre>$1</pre>");

	// 
	// 对{?MD5 *?}进行修饰，匹配 {?MD5 *?} 的正则表达式
	// 
	var regex = /{\?MD5\s*([\s\S]*?)\s*\?}/g;
	var content = content.replace(regex, "<pre>文件MD5(用于校验文件完整性和安全性，不懂使用<a href='/topic/5334' target='_blank' style='text-decoration: underline;'>点我</a>)：<br>$1</pre>");


	// 正式添加content到帖子内容
	document.querySelector('#content').insertAdjacentHTML("beforeend", content)

	// 添加下载DOM
	if (TOPIC['content'].includes("{?info")) {

		// 旧站密码
		if (TOPIC['date'].split("-")[0] <= 2024) {
			var zip_psw = "飞越地平线fleeworld.top"
		} else {
			var zip_psw = "我未曾忘却galbase.top"
		}

		var download_html = `
			<div class="bottom">
				<div class="prompt">解压密码错误看这：<br>下载时网络不稳定数据丢包造成压缩包损坏！避开晚高峰期下载或更换更加稳定的魔法重新下载即可！</div>

				<div class="download">
					<img src="${SRC}/download.png" style="width: 64px;" onclick="download()" loading="lazy" alt="图片加载失败"><p class="space"></p>
					<span class="tag tag2" onclick="download()">获取该Galgame的下载链接<br>解压密码：${zip_psw}</span>
				</div><br>
			</div>
		`
		document.querySelector('#content').insertAdjacentHTML("beforeend", download_html)
	}
}



// 
// 修改帖子
// 
const replace_topic = () => {
	window.location.href = `/topic/replace/${TID}`
}



// 
// 功能菜单下拉触发
// 
const toggleSelect = () => {
	const options = document.getElementById('options');
	
	// 当下拉关闭
	if (options.style.display === 'block') {
		const gal_info_DOM = document.querySelector('.gal_info')
		if (gal_info_DOM) {
			gal_info_DOM.style.pointerEvents = "all"
		}
		options.style.display = 'none'

	// 下拉菜单打开
	} else {
		const gal_info_DOM = document.querySelector('.gal_info')
		if (gal_info_DOM) {
			gal_info_DOM.style.pointerEvents = "none"
		}
		options.style.display = 'block';
	}
}

// // 
// // 下拉后选择
// // 
// const toggleSelect = (value) => {
// 	const selectText = document.querySelector('.select_main span');
// 	const options = document.getElementById('options');

// 	document.querySelector('.gal_info').style.pointerEvents = "all"
// 	document.querySelector('.score').hidden = false

// 	// 隐藏下拉菜单
// 	options.style.display = 'none';
// }

// 点击事件：点击外部区域时关闭下拉菜单
document.addEventListener('click', function(e) {
	if (!e.target.closest('.select_warp')) {
		const options = document.getElementById('options');
		options.style.display = 'none';

		const gal_info_DOM = document.querySelector('.gal_info')
		if (gal_info_DOM) {
			gal_info_DOM.style.pointerEvents = "all"
		}
	}
});



// 
// 加载回复区
// 
function request_reply() {
	fetch_API("GET", `${API}/topic/${TID}/reply`).then(res => {

		// 历史回复存在
		if (res['data']) {
			document.getElementById('reply_region').hidden = false
			const replies_region = document.querySelector('#reply_region main')

			// 倒叙一下，目的是底楼层先渲染
			res['data'].sort((a, b) => a['rid'] - b['rid'])

			// 循环每个回复
			res['data'].forEach((reply, i) => {

				// 回复对象存在，向DOM找回复内容
				var add = ``
				if (reply['reply_rid']) {
					try {
						var r_content = document.querySelector(`#rid${reply['reply_rid']} .rel`).textContent
						var r_uname = document.querySelector(`#rid${reply['reply_rid']} .uname a`).textContent
						var r_space = document.querySelector(`#rid${reply['reply_rid']} .uname a`).href
						var add = `<pre><a href="${r_space}" target="_blank">@${r_uname}</a>：${r_content}</pre>`
					} catch (err) {
						var add = `<pre>（评论已被删除）</pre>`
					}
				}

				var html = `
					<div class="reply_card" id="rid${reply['rid']}">
						<div class="auther">
							<img class="avatar" src="${reply['poster']['avatar_medium']}" onclick="fullscreen_avatar(this)" loading="lazy" alt="图片加载失败">
							<span class="uname"><a href="/space/${reply['uid']}" target="_blank"><b>${reply['poster']['uname']}</b></a></span>
						</div>
						<div class="content">
							${add}
							<div class="rel">${reply['content']}</div>
						</div>
						<div class="bottom">
							<input type="text" id="rid_${reply['rid']}" placeholder="请输入需要回复的内容">
							<button onclick="reply(${reply['rid']})">参与回复</button>
							<button class="button2" onclick="remove_reply(${reply['rid']})">风纪执行</button>
						</div>
						<span class="date">${reply['date']} - ${reply['rid']}楼</span>
					</div>
				`
				replies_region.insertAdjacentHTML("afterbegin", html)
			})
		}
	}).catch(err => {
		log("回复区请求失败", err)
		send_danmuku("错误", "回复区请求失败！自动重试中！")
		setTimeout(request_reply, 1000)
	});
}
request_reply()



// 
// 获取会社所有作品
// 
function request_developer_works() {
	fetch_API("GET", `${API}/developer/${DEVELOPER}/works`).then(res => {
		if (res['data']) {

			// 对跳转{_gototid}进行修饰，匹配 {_goto*} 的正则表达式
			res['data'] = res['data'].replace(/{(_goto(\d+))}/g, `<a href="/topic/$2" target="_blank">跳转</a>`)
			var html = `<pre>以下是${DEVELOPER}会社的所有作品</pre>` + res['data']
			document.querySelector('#developer_works main').insertAdjacentHTML("beforeend", html)
			document.getElementById('developer_works').hidden = false
		}
		
	}).catch(err => {
		log("会社所有作品请求失败", err)
		send_danmuku("错误", "会社所有作品请求失败！自动重试中！")
		setTimeout(request_developer_works, 1000)
	});
}



// 
//	回复
// 
function reply(rid='') {

	// 游客请求锁
	if (REPLY_LOCK == true) {
		float_window.title("提示")
		float_window.content("您当前身份为游客，若别人回复您的消息的话，系统通知不到您，确定还要回复吗？<br>你需要自行关注本贴回复区。关闭本窗口重新回复即可回复成功。")
		float_window.open()
		REPLY_LOCK = false
		return
	}

	// 请求锁，防止过量请求
	if (lock()) {
		return
	}

	// rid不存在，新回复
	if (!rid) {
		var content = document.querySelector('.reply_content').value
		var data = {
			tid: TID,
			content: content
		}

	// 回复别人
	} else {
		var content = document.querySelector(`#rid_${rid}`).value
		var data = {
			tid: TID,
			content: content,
			rid: rid
		}
	}

	if (!content) {
		float_window.title("提示")
		float_window.content("未输入需要回复的内容")
		float_window.open()
		return
	}

	fetch_API("POST", `${API}/topic/${TID}/reply`, {}, data).then(res => {
		if (res['error']) {
			float_window.content(`${res['error']}`)
			float_window.open()
		}

		// 成功
		if (res['data']) {
			float_window.content(`${res['data']}`)
			float_window.open()
			location.reload()
		}
	}).catch(err => {
		log("回复失败", err)
		send_danmuku("错误", "回复失败！自动重试中！")
		setTimeout(() => {
			reply(rid)
		}, 1000)
	})
}



// 
// 删除评论
// 
const remove_reply = (rid) => {
	if (LOGIN == false) {
		float_window.title("提示")
		float_window.content("你没有权限！")
		float_window.open()
		return
	}

	// 请求锁，防止过量请求
	if (lock()) {
		return
	}

	fetch_API("DELETE", `${API}/topic/${TID}/reply`, {rid: rid}).then(res => {
		if (res['error']) {
			float_window.content(`${res['error']}`)
			float_window.open()
		}

		// 成功
		if (res['data']) {
			float_window.content(`删除成功！3秒后自动刷新`)
			float_window.open()
			setTimeout(() => {
				location.reload()
			}, 3000)
		}
	}).catch(err => {
		log("删除失败", err)
		send_danmuku("错误", "删除失败！自动重试中！")
		setTimeout(() => {
			remove_reply(rid)
		}, 1000)
	})
}



// 
// 风纪执行
// 
const judment = () => {
	float_window.title("风纪执行")
	float_window.content(`
		<div class="center">
			<table border="1" cellpadding="12px" cellspacing="0px" width="100%">
				<thead>
					<tr><th>执行结果</th> <th>执行</th></tr>
				</thead>

				<tbody>
					<tr><td>该帖子被删。<br>执行者风纪执行+1次，被执行者风纪执行-1次。</td> <td><input type="text" id="judment_reason" placeholder="请输入风纪执行理由"><button onclick="remove_topic()">执行</button></td></tr>
					<tr><td>移除本贴网盘链接。<br>仅能用于「资源收入繁华街」版块。</td> <td><button class="button2" onclick="remove_urls()">移除贴子网盘链接</button><br></td></tr>
					<tr><td>帖子所有权转移。</td> <td><button class="button2" onclick="topic_auther_change()">确定转移</button><br></td></tr>
				</tbody>
			</table>
		</div>
	`)
	float_window.open()
}



// 
// 删贴
// 
const remove_topic = () => {
	// 获取风机执行的理由
	var reason = document.querySelector("#judment_reason").value

	// 理由不存在
	if (!reason) {
		alert("请输入删帖理由！")
		return
	}

	fetch_API("DELETE", `${API}/topic/${TID}`, {}, {reason: reason}).then(res => {
		if (res['error']) {
			float_window.content(`${res['error']}`)
			float_window.open()
		}

		// 成功
		if (res['data']) {
			float_window.content(`删除成功！3秒后返回主页`)
			float_window.open()
			setTimeout(() => {
				window.location.href = "/"
			}, 3000)
		}
	}).catch(err => {
		float_window.content(`${err.message}`)
		float_window.open()
	})
}



// 
// 下载
// 
const download = () => {
	// 请求锁
	if (lock()) {
		return
	}

	float_window.title("下载")
	float_window.content(`
		<b>链接正在获取中...请耐心等待..</b><br>
	`)
	float_window.open()

	fetch_API("GET", `${API}/topic/${TID}/download`).then(res => {
		if (res['error']) {

			// 是作者
			if (TOPIC['uid'] == UID) {

				// 触发提交下载
				float_window.title("上传链接")
				float_window.content(`
					<b>没有就别填，至少填写一项。</b><br><br>

					<table border="1" cellpadding="0px" cellspacing="0px" width="100%">
						<tbody style="text-align: center">
							<tr><td>百度网盘</td> <td><input id="baidu" type="text" style="width: 100%" placeholder="格式：https://pan.baidu.com/s/xxx?pwd=xxx"></td></tr>
							<tr><td>OneDrive</td> <td><input id="OD" type="text" style="width: 100%"></td></tr>
							<tr><td>直链</td> <td><input id="direct_link" type="text" style="width: 100%"></td></tr>
							<tr><td>其他</td> <td><input id="else_url" type="text" style="width: 100%"></td></tr>
						</tbody>
					</table>

					<div style="width:100%; text-align: center;padding: 5px">
						<button onclick="upload_urls()">提交信息</button>
					</div>
				`)
				float_window.open()

			// 不是作者
			} else {
				float_window.content(`${res['error']}`)
				float_window.open()
			}
		}

		if (res['data']) {
			float_window.content(`
				<b>OneDrive需要自行挂魔法解决</b><br>
				<b>如果你没有魔法，可以看这个<a href="/topic/782" target="_blank">帖子</a>，学习如何科学上网下载OneDrive</b><br><br>
				
				<table border="1" cellpadding="0px" cellspacing="0px" width="100%">
					<tbody style="text-align: center">
						<tr><td>百度网盘</td> <td><input type="text" style="width: 100%;pointer-events: none;" placeholder="${res['data']['baidu']}"></td> <td><button onclick="copy('${res['data']['baidu']}')">复制</button> <button onclick="goto('${res['data']['baidu']}')">跳转</button></td></tr>
						<tr><td>OneDrive</td> <td><input type="text" style="width: 100%;pointer-events: none;" placeholder="${res['data']['onedrive']}"</td> <td><button onclick="copy('${res['data']['onedrive']}')">复制</button> <button onclick="goto('${res['data']['onedrive']}">跳转</button></td></tr>
						<tr><td>直链</td> <td><input type="text" style="width: 100%;pointer-events: none;" placeholder="${res['data']['direct_link']}"</td> <td><button onclick="copy('${res['data']['direct_link']}')">复制</button> <button onclick="goto('${res['data']['direct_link']}')">跳转</button></td></tr>
						<tr><td>其他</td> <td><input type="text" style="width: 100%;pointer-events: none;" placeholder="${res['data']['else_url']}"</td> <td><button onclick="copy('${res['data']['else_url']}')">复制</button> <button onclick="goto('${res['data']['else_url']}')">跳转</button></td></tr>
					</tbody>
				</table>

				<br>
				<b style="color:red">TMD你们怎么就不看这句话呢↓ 出了问题还问</b><br>
				<b>解压密码错误都是下载时网络不稳定数据丢包造成压缩包损坏！</b><br>
				<b>避开晚高峰期下载或更换更加稳定的魔法<b style="color:red">重新下载即可！重新下载即可！重新下载即可！</b></b><br>
				<b style="color:red">密码的看一遍能死啊↑</b>
			`)
		}
	}).catch(err => {
		log("下载请求失败", err)
		send_danmuku("错误", "下载失败！自动重试中！")
		setTimeout(download, 1000)
	})
}



// 
// 复制
// 
const copy = (content) => {
	// 创建一个临时的textarea元素
	var textarea = document.createElement("textarea");

	// 设置元素的内容为要复制的文本
	textarea.value = content;

	// 将元素添加到文档中
	document.body.appendChild(textarea);

	// 选择文本
	textarea.select();

	// 将文本复制到剪贴板
	document.execCommand("copy");

	// 删除临时元素
	document.body.removeChild(textarea);
}



// 
// 跳转
// 
const goto = (url) => {
	// 多个直链
	if (url.includes("|")) {
		var urls = url.split('|')

		for (let i = 0; i < urls.length; i++) {
			window.open(urls[i], "_blank")
		}

	// 单个直链
	} else {
		window.open(url, "_blank")
	}
}


// 
// 传输网盘链接
// 
const upload_urls = () => {

	// 请求锁
	if (small_lock()) {
		return
	}

	// 获取链接
	var baidu = document.querySelector("#baidu").value
	var OD = document.querySelector("#OD").value
	var direct_link = document.querySelector("#direct_link").value
	var else_url = document.querySelector("#else_url").value

	// 全部不填
	if (!baidu && !OD && !direct_link && !else_url) {
		alert("起码得填一项吧...")
		return
	}

	var data = {
		baidu: baidu,
		OD: OD,
		direct: direct_link,
		else_url: else_url
	}

	fetch_API("POST", `${API}/topic/${TID}/download`, {}, data).then(res => {
		if (res['error']) {
			float_window.content(`${res['error']}`)
			float_window.open()
		}

		if (res['data']) {
			float_window.content(`${res['data']}`)
			float_window.open()
		}
	}).catch(err => {
		console.log("链接上传请求错误", err.message)
		setTimeout(upload_urls, 1000);
	})
}





// 
// 请求收藏状态
// 
function request_collection_state() {
	fetch_API("GET", `${API}/topic/${TID}/collection/${UID}`).then(res => {
		COLLECTION_STATE = res['data']['collection_state']

		// 已收藏
		if (COLLECTION_STATE == true) {
			document.querySelector("#collection_button span").textContent = "取消收藏"

		// 未收藏
		} else {
			document.querySelector("#collection_button span").textContent = "收藏本贴"
		}
	}).catch(err => {
		console.log(`收藏状态请求失败: ${err.message}`);
		setTimeout(request_collection_state, 1000);
	})
}
if (LOGIN == true) {
	request_collection_state()
}



// 
// 收藏帖子
// 
const collection = () => {
	// 请求锁
	if (lock()) {
		return
	}

	// 收藏
	if (COLLECTION_STATE == false) {

		fetch_API("POST", `${API}/topic/${TID}/collection`).then(res => {
			float_window.content(`${res['data']}`)
			float_window.open()

			COLLECTION_STATE = true
			document.querySelector("#collection_button span").textContent = "取消本贴"
		}).catch(err => {
			float_window.content(`${err.message}`)
			float_window.open()
		})

	// 已收藏，发取消收藏
	} else {

		fetch_API("DELETE", `${API}/topic/${TID}/collection`).then(res => {
			float_window.content(`${res['data']}`)
			float_window.open()

			COLLECTION_STATE = false
			document.querySelector("#collection_button span").textContent = "收藏本贴"
		}).catch(err => {
			float_window.content(`${err.message}`)
			float_window.open()
		})
	}
}



// 
// 查看帖子结构
// 
const view_topic_format = () => {
	const cache_content = TOPIC['content'].replace(/</g, "&lt;").replace(/>/g, "&gt;")
	float_window.title("该贴内容如下")
	float_window.width("80%")
	float_window.content(`<div class="limit"><pre>${cache_content}</pre></div>`)
	float_window.open()
}



// 
// 移除所有网盘链接
// 
const remove_urls = () => {
	fetch_API("DELETE", `${API}/topic/${TID}/download`).then(res => {
		if (res['error']) {
			float_window.content(`${res['error']}`)
			float_window.open()
		}

		if (res['data']) {
			float_window.content(`${res['data']}`)
			float_window.open()
		}
	}).catch(err => {
		float_window.content(`${err.message}`)
		float_window.open()
	})
}



// 
// 动画版块切换集数
// 
const ep_goto = (num) => {
	fetch_API("GET", `${API}/anime/${TID}/${num}`).then(res => {
		log("动画集数请求", res)
		if (res['data']) {
			var current_ep = document.querySelector("#anime_player").src
			if (current_ep.includes("mp4")) {
				current_ep = document.querySelector("#anime_player").src.split("/").pop().replace(".mp4", "").split("_")[0]
				document.querySelector(`#ep${current_ep}`).className = ""
			}

			document.querySelector(`#ep${num}`).className = "select"
			document.querySelector("#anime_player").src = res['data']
		}
	}).catch(err => {
		float_window.content(`${err.message}`)
		float_window.open()
	})
}



// 
// 更新帖子评分
// 
const update_rating = () => {
	const DOM_user_rate = document.querySelector("#user_rate")
	const rating_data = {}

	if (SCORES['count'] == 0) {
		return
	}

	// 构建弹幕数据集
	SCORES['full'].forEach((user, i) => {
		COMMENTS.push({
			"user": user['user']['uname'],
			"text": `评分：${user['score']} 评价：${user['content']}`
		})
	})
	scheduleBatch()

	// 添加历史简评
	SCORES['full'].forEach((user, i) => {
		rating_data[user['user']['uname']] = user['score']
		HTML = `
			<div class="card" id="_rate1">
				<div class="auther">
					<img class="avatar" src="${user['user']['avatar_small']}" alt="图片加载失败">
					<img src="${SRC}/user.png" style="height: 11px;margin-right: 5px" alt="图片加载失败">
					<a class="uname" href="/space/${user['uid']}" title="点击进入 TA 的个人空间" target="_blank" style="color: #666666;">${user['user']['uname']}</a>
				</div>
				<div class="content">
					<span>${user['content']}</span>
				</div>
				<div class="info">
					<span class="date"><img src="${SRC}/date.png" alt="图片加载失败">${user['date']}</span>
					<span class="message"><img src="${SRC}/list.png" alt="图片加载失败">${user['state']}</span>
				</div>
			</div>
		`
		DOM_user_rate.insertAdjacentHTML("beforeend", HTML)

		// 自己的评分
		if (LOGIN == true && user['uid'] == UID) {
			document.querySelector("#last_rating").textContent = user['date']
			document.querySelector(".my_score").value = user['score']
			document.querySelector("#rating_content").value = user['content']
			document.querySelector(".state").value = user['state']
		}
	})

	// 柱状图生成
	const max = 10; // 设定 Y 轴最大值基准
	const chart = document.getElementById("chart");
	const slider = document.getElementById("slider");
	const chartHeight = chart.clientHeight; 

	// 2. 计算平均值
	const values = Object.values(rating_data);
	const avg = values.reduce((a, b) => a + b, 0) / values.length;

	// 3. 动态生成柱状图 DOM
	Object.entries(rating_data).forEach(([key, value]) => {
		const wrapper = document.createElement("div");
		wrapper.className = "bar-wrapper";
		
		const bar = document.createElement("div");
		bar.className = "bar";
		
		// 根据百分比计算高度
		bar.style.height = (value / max * chartHeight) + "px";
		
		const valueSpan = document.createElement("span");
		valueSpan.className = "bar-value";
		valueSpan.textContent = value.toFixed(1);
		
		const labelEl = document.createElement("div");
		labelEl.className = "label";
		labelEl.textContent = key;
		
		bar.appendChild(valueSpan);
		wrapper.appendChild(bar);
		wrapper.appendChild(labelEl);
		chart.appendChild(wrapper);
	});

	// 4. 绘制平均线
	const avgHeight = (avg / max * chartHeight);
	const avgLine = document.createElement("div");
	avgLine.className = "avg-line";
	avgLine.style.bottom = avgHeight + "px"; 
	chart.appendChild(avgLine);

	const avgLabel = document.createElement("div");
	avgLabel.className = "avg-label";
	avgLabel.style.bottom = avgHeight + "px";
	avgLabel.textContent = "AVG: " + avg.toFixed(2);
	chart.appendChild(avgLabel);

	// 5. 鼠标拖拽平移逻辑 (Drag to Scroll)
	let isDown = false; // 记录鼠标是否按下
	let startX;       // 记录起始点击坐标
	let scrollLeft;   // 记录起始滚动位置

	slider.addEventListener('mousedown', (e) => {
		isDown = true;
		// 计算鼠标相对于容器的初始 X
		startX = e.pageX - slider.offsetLeft;
		scrollLeft = slider.scrollLeft;
	});

	// 鼠标离开或松开时，停止拖拽
	slider.addEventListener('mouseleave', () => isDown = false);
	slider.addEventListener('mouseup', () => isDown = false);

	slider.addEventListener('mousemove', (e) => {
		if (!isDown) return; // 未按下则跳过
		e.preventDefault();   // 阻止默认行为（如文字选中）
		
		const x = e.pageX - slider.offsetLeft;
		// 计算位移差，乘以 2 提升滑动灵敏度
		const walk = (x - startX) * 2; 
		slider.scrollLeft = scrollLeft - walk;
	});
}



// 
// 修改评分或上传新评分
// 
const put_rating = () => {
	if (LOGIN == false) {
		float_window.title("错误")
		float_window.content("未登录用户不能进行评分！")
		float_window.open()
		return
	}

	// 请求锁，防止过量请求
	if (small_lock()) {
		return
	}

	// 获取评分信息
	var score = document.querySelector(".my_score").value
	var content = document.querySelector("#rating_content").value
	var state = document.querySelector(".state").value

	// 遣返
	if (!score) {
		float_window.title("提醒")
		float_window.content("您未填入评分！")
		float_window.open()
	}
	if (!content) {
		float_window.title("提醒")
		float_window.content("您未填入简评！")
		float_window.open()
	}

	var data = {
		tid: TID,
		score: score,
		state: state,
		content: content
	}

	fetch_API("PUT", `${API}/topic/score`, {}, data).then(res => {
		if (res['error']) {
			float_window.title("错误")
			float_window.content(`${res['error']}`)
			float_window.open()
			return
		}
		if (res['data']) {
			float_window.title("提示")
			float_window.content(`${res['data']}`)
			float_window.open()
		}
	}).catch(err => {
		console.log("修改评分请求失败", err.message)
		setTimeout(put_rating, 1000);
	})
}




// 
// 将音乐播放器放到回复区
// 
// const move_to_reply = () => {
// 	const reply_right = document.querySelector('#reply main');
// 	const play_box = document.querySelector('.play_box');
// 	reply_right.appendChild(play_box);

// 	play_box.classList.add("reply_play_box");
// }



// 
// 手机APP
// 
document.addEventListener("DOMContentLoaded", () => {
	const DOM_phone = document.querySelector("#phone .inner ul")
	HTML = `
		<li class="app" onclick="VNDB()">
			<img src="${SRC}/phone_vndb.ico" alt="VNDB">
			<span>VNDB</span>
		</li>
	`
	DOM_phone.insertAdjacentHTML("beforeend", HTML)
})



// 
// VNDB搜索
// 
const VNDB = () => {
	float_window.title("VNDB")
	float_window.content(`
		开发中
	`)
	float_window.open()
}


// 
// 帖子所有权转移
// 
const topic_auther_change = () => {
	fetch_API("PUT", `${API}/topic/${TID}/auther`).then(res => {
		log("帖子所有权转移", res)
		if (res['error']) {
			float_window.title("错误")
			float_window.content(`${res['error']}`)
			float_window.open()
			return
		}

		float_window.title("提示")
		float_window.content(`${res['data']}`)
		float_window.open()
	}).catch(err => {
		log("帖子所有权转移失败", err)
		send_danmuku("错误", "帖子所有权转移失败！自动重试中！")
		setTimeout(topic_auther_change, 1000);
	})
}