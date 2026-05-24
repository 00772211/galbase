<script src="/js/time_diff.js"></script>
<script src="/js/xhr.js"></script>
<script>
	// 
	// 
	// 最新5条帖子
	// 
	// 
	const newest_topic_add = (topics) => {
		console.log(topics);
		

		var newest_topic_region = document.querySelector(".newest_topic")
		for (let i = 0; i < topics.length; i++) {
		
			let day = time_diff(topics[i]['date'])

			let html = `
				<div class="card">
					<div class="auther">
						<img class="avatar" src="${topics[i]['avatar']}" alt="图片加载失败">
						<img src="/data/imgs/people.png" style="height: 11px;margin-right: 5px" alt="图片加载失败">
						<a class="uname" href="/space/${topics[i]['uid']}" title="点击进入 TA 的个人空间" target="_blank" style="color: #666666;">${topics[i]['uname']}</a>
					</div>
					<div class="preview_content">
						<a href="/topic/${topics[i]['tid']}" title="点击进入帖子" target="_blank">${topics[i]['title']}</a>
					</div>
					<div class="topic_info">
						<span class="data"><img src="/data/imgs/date.png" alt="图片加载失败"> ${day}</span>
						<span class="message"><img src="/data/imgs/message.png" alt="图片加载失败">${topics[i]['reply_count']}</span>
						<span class="view"><img src="/data/imgs/view.png" alt="图片加载失败">${topics[i]['view_count']}&emsp;</span>
					</div>
				</div>
			`
			newest_topic_region.insertAdjacentHTML("beforeend", html)
		}
	}

<?php
	// 获取最新8个tid
	$data = mysqli_query($link, "SELECT tid FROM topics_index ORDER BY last_modify DESC LIMIT 8;");
	$i = 0;

	// 循环每个tid找到对应的帖子数据
	while ($row = $data->fetch_assoc()) {
		$tid = $row['tid'];

		// 获取帖子数据
		$topics[$i] = get_topic($tid);
		$topics[$i]['date'] = date("Y-m-d", get_value("topics_index", "last_modify", "tid='$tid'"));

		// 获取作者头像和用户名
		$topics[$i]['avatar'] = get_avatar($topics[$i]['uid'])['small'];
		$topics[$i]['uname'] = get_uname($topics[$i]['uid']);
		$i++;
	}

	// 转给js处理帖子数据
	$topics = json_encode($topics);
	echo "newest_topic_add($topics)";
?>


	// 
	// 
	// 每日推荐5个GAL
	// 
	//
	const recommend_gal_add = (recommends) => {
		console.log();
		
		var recommend_region = document.querySelector(".recommend_gal_list")
		for (let i = 0; i < 5; i++) {			
			let html = `
				<div class="card">
					<img src="${recommends[i]['chunk']}/${recommends[i]['tid']}/${recommends[i]['preview']}.avif" alt="图片加载失败">
					<span class="title"><a href="/topic/${recommends[i]['tid']}" target="_blank">${recommends[i]['title']}</a></span>
				</div>
			`
			recommend_region.insertAdjacentHTML("beforeend", html)
		}
	}

	<?php
		// 获取5个推荐tids
		$tids = get_value("sys_auto_increment_value", "value", "variable='recommend'");
		$tids = explode('||', $tids);

		for ($i=0; $i < 5; $i++) { 
			$tid = $tids[$i];

			// 获取tid对应的标题
			$data = get_topic($tid);

			$recommends[$i]['tid'] = $tid;
			$recommends[$i]['title'] = str_replace("<br>", "", $data['title']);
			$recommends[$i]['preview'] = $data['preview'];
			$recommends[$i]['chunk'] = chunk($tid, "", TRUE);
		}

		// 转给js处理帖子数据
		$recommends = json_encode($recommends);
		echo "recommend_gal_add($recommends)";
	?>


	// 监听OP播放器窗口高度变化
	const op_player = document.querySelector('.home_page_op')
	const resizeObserver = new ResizeObserver((entries) => {

		// 自适配高度
		const op_player_height = document.querySelector(".home_page_op").offsetHeight
		document.querySelector(".newest_topic").style.height = `${op_player_height}px`

	})
	resizeObserver.observe(op_player)




	// 
	// 监听OP播放器窗口高度变化
	// 
	// const op_player = document.querySelector('.home_page_op')
	// const resizeObserver = new ResizeObserver((entries) => {
	// 	// 修复每日5个推荐，若有很多空位额外请求
	// 	const op_player_height = document.querySelector(".home_page_op").offsetHeight
	// 	document.querySelector(".newest_topic").style.height = `${op_player_height}px`

	// 	// 获取最后发帖卡片的高度，判断缺多少个
	// 	const title_height = document.querySelector(".newest_topic .card:first-child").offsetHeight
	// 	const card_height = document.querySelector(".newest_topic .card:last-child").offsetHeight
	// 	const empty_height = op_player_height - title_height - 5 * card_height
	// 	const request_num = Math.floor(empty_height / card_height) + 1
		
	// 	var data = {
	// 		"cmd": "request_target_num_recommend",
	// 		"num": request_num
	// 	}
	// 	xhr("/servers/home_page.php", data).then((result) => {
	// 		var result = JSON.parse(result);
	// 		newest_topic_add(result)
	// 	})
	// })
	// resizeObserver.observe(op_player)











</script>



