
<script src="/js/xhr.js"></script>
<script src="/js/time_diff.js"></script>
<script src="/js/fullscreen.js"></script>
<script>
	// 
	// 
	// 标题变更
	// 
	// 
	document.title = "<?php echo get_board_name($_GET['fid']); ?>"
	const mode = "<?php 
		if (isset($_GET['mode'])) {
			echo $_GET['mode'];
		}
	?>"



	// 
	// 
	// 请求帖子
	// 
	// 
	var data = {
		"cmd": "request_topics",
		"fid": "<?php echo $_GET['fid']; ?>",
		"page": <?php echo $_GET['page']; ?>,
		"mode": mode
	}
	xhr("/server.php", data).then((result) => {
		// 解析返回值为JSON格式
		var topics = JSON.parse(result)

		//添加帖子
		add_topic(topics)
	})



	// 
	// 
	// 整合帖子数据至DOM元素
	// 
	// 
	const add_topic = (forums) => {

		// 无帖子数据
		if (!forums) {
			alert('当前页数不存在帖子数据')
			return
		}

		// 获取帖子需要添加至的DOM
		var topics_region = document.querySelector('#topics')

		// 循环每个帖子
		for (n = 0; n < forums.length; n++) {

			// 判断是卡片版块
			if ("<?php echo $_GET['fid']; ?>" == "1-1" || "<?php echo $_GET['fid']; ?>" == "1-2" || "<?php echo $_GET['fid']; ?>" == "1-3" || "<?php echo $_GET['fid']; ?>" == "1-4") {
				
					// 判断帖子封面是否存在
					if (forums[n]['preview']) {
						var path = `${forums[n]['chunk']}/${forums[n]['tid']}/preview.avif`
					} else {
						var path = "/data/imgs/yingmei_small.jpg"
					}

					// 小于7天显示时间tag
					var day = time_diff(forums[n]['date'])
					if (day <= 7) {
						var date_tag = `<li class="tag tag3">${day}天前</li>`
					} else {
						var date_tag = ""
					}

					// tags切片
					var tags = `${forums[n]['tags']}`.split("|")
					var tags_html = ""
					for (var i = 0; i < tags.length; i++) {
						var tag = tags[i]
						
						// 特殊tag
						if (tag == "生肉") {
							var add_tag_format = "tag3"
						} else {
							var add_tag_format = ""
						}
						var tags_html = tags_html + `<li class="tag ${add_tag_format}">${tag}</li>`
					}

					// fid=1-4是动画版块
					if ("<?php echo $_GET['fid']; ?>" == "1-4") {
						var style_1 = `style="width: 100%"`
						var style_2 = `style="aspect-ratio: 10 / 14;"`
					}

					// 帖子卡片
					var html = `
						<div class="forum_card">
							<div class="cover" ${style_2}>
								<img src="${path}" title="${forums[n]['title']}" onclick="window.open('/topic/${forums[n]['tid']}')" ${style_1} alt="帖子封面">
							</div>
							<a class="title" href="/topic/${forums[n]['tid']}" target="_blank" title="${forums[n]['title']}">${forums[n]['title']}</a>
							<ul class="tags">
								${date_tag}
								${tags_html}
							</ul>
							<div class="info">
								<img class="avatar" src="${forums[n]['avatar']}" loading="lazy" alt="图片加载失败">
								<a href="#" class="uname" target="_blank" title="查看TA的空间">${forums[n]['auther']}</a>
								<span class="score"><img src="/data/imgs/rate.png" alt="评分">${forums[n]['rating']['average']}(${forums[n]['rating']['ratings']})</span>
								<span class="reply"><img src="/data/imgs/message.png" alt="回复数">${forums[n]['reply_count']}</span>
								<span class="view"><img src="/data/imgs/view.png" alt="浏览数">${forums[n]['view_count']}</span>
							</div>
						</div>
					`
					topics_region.insertAdjacentHTML("beforeend", html)
					
			// 列表式帖子
			} else {

				// 存在最新回复
				if (forums[n]['newest_reply']) {

					// 获取第一条回复数据和日期
					var date = forums[n]['newest_reply_date'];
					var newest_content = forums[n]['newest_reply'].replace("<br>", "");

				// 无回复
				} else {
					var date = ''
					var newest_content = ''
				}

				// 预览内容删除<br>标签
				forums[n]['content'] = forums[n]['content'].replace("<br>", "")

				var html = `
					<div class="forum_list" id="_${forums[n]['tid']}">
						<div class="info">
							<a class="title" href="/topic/${forums[n]['tid']}" target="_blank">${forums[n]['title']}</a><br>
							<span class="auther">${forums[n]['auther']}-${forums[n]['date']}</span><br>
							<span class="precontent">${forums[n]['content'].slice(0, 40)}</span>
						</div>
						<div class="preview_img"></div>
						<div class="new_reply">
							<span class="count">${forums[n]['reply_count']}回复</span><br>
							<span class="date">${date}</span><br>
							<span class="content">${newest_content}</span>
						</div>
					</div>
				`
				topics_region.insertAdjacentHTML("beforeend", html)

				// 判断预览图是否存在
				if (forums[n]['preview']) {
					previews_add(forums[n]['tid'], forums[n]['preview'], forums[n]['chunk'])
				}
			}
		}
	}

	// 按分数高低排序
	const request_topics_with_score = () => {
		window.location.href = `/forums/<?php echo $_GET['fid']; ?>/<?php echo $_GET['page']; ?>/score`
	}

	// 按默认排序
	const request_topics_with_normal = () => {
		window.location.href = `/forum/<?php echo $_GET['fid']; ?>/<?php echo $_GET['page']; ?>`
	}



	// 
	// 
	// 上一页
	// 
	// 
	var last_page_button = document.querySelector('.page .last_page')
	last_page_button.addEventListener('click', function() {
		// 获取fid和page - 1
		var fid = "<?php echo $_GET['fid']; ?>"
		var page = <?php echo $_GET['page']; ?> - 1

		if (page < 0) {
			alert('无上一页')
		} else {

			// 从URL进行跳转
			if (!mode) {
				window.location.href = `/forum/${fid}/${page}`
			} else {
				window.location.href = `/forums/${fid}/${page}/${mode}`
			}
			
		}
	})



	// 
	// 
	// 下一页
	// 
	// 
	var next_page_button = document.querySelector('.page .next_page')
	next_page_button.addEventListener('click', function() {
		// 获取fid和page + 1
		var fid = "<?php echo $_GET['fid']; ?>"
		var page = <?php echo $_GET['page']; ?> + 1

		// 从URL进行跳转
		if (!mode) {
			window.location.href = `/forum/${fid}/${page}`
		} else {
			window.location.href = `/forums/${fid}/${page}/${mode}`
		}
	})



	// 
	// 
	// 指定页数跳转
	// 
	// 
	const goto_page = () => {
		// 获取需要跳转的fid和page
		var fid = "<?php echo $_GET['fid']; ?>"
		var page = document.querySelector('#page_number').value

		// 未填入需要跳转的page
		if (!page) {
			alert('请输入你要跳转的网页')

		// 执行跳转
		} else {

			// 从URL进行跳转
			if (!mode) {
				window.location.href = `/forum/${fid}/${page}`
			} else {
				window.location.href = `/forums/${fid}/${page}/${mode}`
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
</script>
