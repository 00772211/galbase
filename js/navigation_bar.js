HTML = `
	<div class="board_2nd">
		<a href="/topic/add" target="_blank" style="color: black"><span><img src="${SRC}/new.png" style="width: 20px;" alt="图片加载失败">发帖</span></a>
		<span onclick="goto_top()"><img src="${SRC}/top.png" style="width: 20px;" alt="图片加载失败">主页</span>
		<span onclick="goto_bottom()"><img src="${SRC}/top.png" style="width: 20px; rotate: 180deg;" alt="图片加载失败">底部</span>

		<div id="auther">
		</div>

		<span onclick="tags_list(this)"><img src="${SRC}/arrow.png" style="height: 10px;" alt="图片加载失败">热门标签</span>
		<span id="tags_list" style="display:block;">
			<ul></ul>
		</span>

		<span id="fid1" onclick="boards_list(this, 1)"><img src="${SRC}/arrow.png" style="height: 10px;" alt="图片加载失败">学园集聚之地版块</span>
		<span id="boards_list_1" style="display:block;">
			<ul>
				<li id="nav_fid_1-1"><a href="/forum/1-1/0" target="_blank"></a></li>
				<li id="nav_fid_1-2"><a href="/forum/1-2/0" target="_blank"></a></li>
				<li id="nav_fid_1-3"><a href="/forum/1-3/0" target="_blank"></a></li>
				<li id="nav_fid_1-4"><a href="/forum/1-4/0" target="_blank"></a></li>
			</ul>
		</span>

		<span id="fid2" onclick="boards_list(this, 2)"><img src="${SRC}/arrow.png" style="height: 10px;" alt="图片加载失败">学园文学茶馆版块</span>
		<span id="boards_list_2" style="display:block;">
			<ul>
				<li id="nav_fid_2-1"><a href="/forum/2-1/0" target="_blank"></a></li>
				<li id="nav_fid_2-2"><a href="/forum/2-2/0" target="_blank"></a></li>
				<li id="nav_fid_2-3"><a href="/forum/2-3/0" target="_blank"></a></li>
				<li id="nav_fid_2-4"><a href="/forum/2-4/0" target="_blank"></a></li>
			</ul>
		</span>

		<span id="fid3" onclick="boards_list(this, 3)"><img src="${SRC}/arrow.png" style="height: 10px;" alt="图片加载失败">璀璨群星之上版块</span>
		<span id="boards_list_3" style="display:block;">
			<ul>
				<li id="nav_fid_3-1"><a href="/forum/2-1/0" target="_blank"></a></li>
				<li id="nav_fid_3-2"><a href="/forum/2-2/0" target="_blank"></a></li>
			</ul>
		</span>
		<span><img src="${SRC}/list.png" style="height: 14px;" alt="图片加载失败"><a href="/topic/1004" target="_blank" style="color: black">论坛使用教程</a></span>
		<span><img src="${SRC}/url.png" style="width: 18px;" alt="图片加载失败"><a href="/topic/381" target="_blank" style="color: black">BUG反馈</a></span>
		<span><img src="${SRC}/url.png" style="width: 18px;" alt="图片加载失败"><a href="/topic/514" target="_blank" style="color: black">提供GAL</a></span>
		<span><img src="${SRC}/url.png" style="width: 18px;" alt="图片加载失败"><a href="/topic/689" target="_blank" style="color: black">请求汉化GAL</a></span>
		<span><img src="${SRC}/url.png" style="width: 18px;" alt="图片加载失败"><a href="/topic/953" target="_blank" style="color: black">支持本站</a></span>
		<span><img src="${SRC}/url.png" style="width: 18px;" alt="图片加载失败"><a href="/topic/1184" target="_blank" style="color: black">防迷路看这</a></span>
		<span id="online_count"><img src="${SRC}/star.png" style="width: 18px;" alt="图片加载失败">今日在线</span>
		<span id="highest_online"><img src="${SRC}/star.png" style="width: 18px;" alt="图片加载失败">最高在线</span>
		<span id="topics_count"><img src="${SRC}/star.png" style="width: 18px;" alt="图片加载失败">论坛帖数</span>
	</div>
`
document.querySelector(".navigation_bar").innerHTML = HTML










// 
// 全局变量
// 
const Dom_navigation_bar = document.querySelector(".navigation_bar")



// 
// 更新导航栏，需要有全局变量NAV
// 
const update_nav = () => {

	// 更新TAGS
	const DOM_tags = document.querySelector("#tags_list ul")
	NAV['tags'].forEach((tag) => {
		HTML = `<li class="tag"><a href="/search/tag/${tag}" target="_blank">${tag}</a></li>`
		DOM_tags.insertAdjacentHTML("beforeend", HTML)		
	})

	// 更新板块
	Object.entries(NAV['board']).forEach(([k, v]) => {
		document.querySelector(`#nav_fid_${k} a`).textContent = v
	})

	// 更新ym
	const DOM_ym = document.querySelector(".navigation_bar .board_2nd")
	NAV['ym'].forEach((topic, i) => {
		HTML = `
			<span class="ym" id="ym_${i}">
				<a href="${topic['src']}" target="_blank">
					<img src="${topic['preview']}" alt="月慕最新资讯预览图" title="${topic['title']}" loading="lazy">
					<p title="${topic['title']}">${topic['title']}</p>
				</a>
			</span>
		`
		DOM_ym.insertAdjacentHTML("beforeend", HTML)
	})

	// 更新在线人数
	document.querySelector("#online_count").textContent = `今日在线：${NAV['online']}`
	document.querySelector("#highest_online").textContent = `最高在线：${NAV['hightest_online']}`
	document.querySelector("#topics_count").textContent = `论坛帖数：${NAV['topics_count']}`
}



// 
// 监听内容高度，同步高度
// 
document.addEventListener("DOMContentLoaded", () => {
    const dynamic_height = document.querySelector('.dynamic_height')
	if (dynamic_height) {
		const resizeObserver = new ResizeObserver((entries) => {
			for (const entry of entries) {
				var new_height = entry.target.offsetHeight;
				Dom_navigation_bar.style.height = new_height + "px";
			}
		})

		resizeObserver.observe(dynamic_height);
	}

	const dynamic_height_flex = document.querySelector(".dynamic_height_flex");
	if (dynamic_height_flex) {
		const updateBottomY = () => {
			const rect = dynamic_height_flex.getBoundingClientRect()
			const bottomY = rect.bottom + window.scrollY
			const nav_topY = Dom_navigation_bar.getBoundingClientRect().top + window.scrollY
			const new_height = bottomY - nav_topY - 5
			Dom_navigation_bar.style.height = new_height + "px";
		}

		// 监听高度变化
		const resizeObserver = new ResizeObserver(() => {
			updateBottomY()
		});

		resizeObserver.observe(dynamic_height_flex);

		// 初始执行一次
		updateBottomY()
	}
})



// 
// 版块列表
// 
const boards_list = (element, fid) => {
	const boards_list = document.querySelector(`#boards_list_${fid}`)
	const img = element.querySelector("img")

	// block=打开 none=关闭
	// 关闭列表
	if (boards_list.style.display == "block") {
		img.style.transform = 'rotate(180deg)';
		boards_list.style.display = "none"

	// 打开列表
	} else {
		img.style.transform = 'rotate(0deg)';
		boards_list.style.display = "block"
	}
}



// 
// 打开tags列表
// 
const tags_list = (element) => {
	const tags_lsit = document.querySelector("#tags_list")
	const img = element.querySelector("img")

	// block=打开 none=关闭
	// 关闭列表
	if (tags_lsit.style.display == "block") {
		img.style.transform = 'rotate(180deg)';
		tags_lsit.style.display = "none"

	// 打开列表
	} else {
		img.style.transform = 'rotate(0deg)';
		tags_lsit.style.display = "block"
	}
}


// 
// 回到论坛主页或顶部
// 
const goto_top = () => {
	const path = window.location.pathname

	// 在主页，/和/index.php等效主页
	if (path == "/" || path == "/index.html") {
		document.querySelector(".op_board").scrollIntoView({ behavior: 'smooth', block: 'start' });

	// 不在主页
	} else {
		window.open(`/`, "_self")
	}
}



// 
// 回到论坛底部
// 
const goto_bottom = () => {
	document.querySelector("footer .content").scrollIntoView({ behavior: 'smooth', block: 'start' });
}






// // 
// // 重新分配导航栏
// // 
// const updata_bar = (new_height) => {
// 	// 做减法，从最后一个span标签开始删除
// 	const navigation_bar = document.querySelector(".navigation_bar .board_2nd");
// 	const spans = navigation_bar.querySelectorAll('span');

// 	// 全部显示
// 	Array.from(spans).forEach((span, index) => {
// 		span.hidden = false
// 	})
	
// 	// 将 NodeList 转换为数组并反转，在末尾span做减法
// 	Array.from(spans).reverse().forEach((span, index) => {
// 		const span_height = span.offsetHeight;
// 		if (document.querySelector(".navigation_bar").offsetHeight - span_height > new_height) {
// 			span.hidden = true
// 		}
// 	})
// }


// // 
// // 月慕的最新资讯位置
// // 
// const add_ym_topics = (topics) => {
// 	ym_DOM = document.querySelector(".navigation_bar .board_2nd")

// 	for (n = 0; n < topics.length; n++) {
// 		let title = topics[n]['title']
// 		let pre = topics[n]['pre']
// 		let src = topics[n]['src']
// 		let html = `
// 			<span class="ym" id="ym_${n}" hidden>
// 				<a href="${src}" target="_blank">
// 					<img src="${pre}" alt="月慕最新资讯预览图" title="${title}" loading="lazy">
// 					<p title="${title}">${title}</p>
// 				</a>
// 			</span>
// 		`
// 		ym_DOM.insertAdjacentHTML("beforeend", html)
// 	}
// }

// // 
// // 重分配ym资讯
// // 
// const updata_ym = (new_height) => {

// 	// 初次加载隐藏所有
// 	for (i = 0; i < 10; i++) {
// 		try {
// 			document.querySelector(`#ym_${i}`).hidden = true
// 		} catch (error) {
// 			console.log(error);
// 		}
// 	}

// 	// 计算第一个咨询的卡片高度
// 	let ym_card = document.querySelector(`#ym_0`)
// 	ym_card.hidden = false

// 	// 卡片有padding6属性，需要-12得到图片的长度，图片长宽比190:100，除以1.9获得图片高
// 	let img_height = (ym_card.offsetWidth - 12) / 1.9

// 	// 获取文本高
// 	let text_height = document.querySelector(`#ym_0 p`).offsetHeight

// 	// 卡片总高，有padding6属性，所以要加12
// 	let ym_card_height = img_height + text_height + 12

// 	// 可继续添加
// 	let navigation_bar_height = document.querySelector(".navigation_bar").offsetHeight
// 	if (navigation_bar_height < new_height) {
		
// 		// 计算可添加个数，做向下取整
// 		let num = Math.floor((new_height - navigation_bar_height) / ym_card_height)

// 		for (i = 1; i < num + 1; i++) {
// 			document.querySelector(`#ym_${i}`).hidden = false
// 		}

// 	} else {
// 		ym_card.hidden = true
// 	}
// }



// // 
// // 重新分配导航栏
// // 
// const updata_bar = (new_height) => {
// 	// 做减法，从最后一个span标签开始删除
// 	const navigation_bar = document.querySelector(".navigation_bar .board_2nd");
// 	const spans = navigation_bar.querySelectorAll('span');

// 	// 全部显示
// 	Array.from(spans).forEach((span, index) => {
// 		span.hidden = false
// 	})
	
// 	// 将 NodeList 转换为数组并反转，在末尾span做减法
// 	Array.from(spans).reverse().forEach((span, index) => {
// 		const span_height = span.offsetHeight;
// 		if (document.querySelector(".navigation_bar").offsetHeight - span_height > new_height) {
// 			span.hidden = true
// 		}
// 	})
// }


