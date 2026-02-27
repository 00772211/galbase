<script src="/js/xhr.js"></script>
<script src="/js/time_diff.js"></script>
<script src="/js/fullscreen.js"></script>
<script src="/js/float_window.js"></script>
<script src="/js/lock.js"></script>


<?php 
	// 若内容有script标签，优先输出
	if (strstr($data['content'], "<script>")) {
		// 用正则匹配所有 <script> 标签
		preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $data['content'], $matches);

		// $matches[0] 包含完整的 <script>...</script> 代码块
		$scripts = implode("\n", $matches[0]);
		// echo($scripts); 这段输出在最底下

		$data['content'] = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $data['content']);
	}
?>

<script>
	// 
	// 标题变更
	// 
	document.title = "<?php echo $data['title']; ?>".replaceAll("」「", " ").replaceAll("「", "").replaceAll("」", "") + " galgame资源 / GALBase论坛"



	// 
	// 未登录取消功能按钮
	// 
	if (!"<?php echo $uid; ?>") {
		document.querySelector(".buttons_").hidden = true
	}



	// 
	// 时间差计算
	// 
	var time_diff = time_diff("<?php echo $data['date']; ?>")
	document.querySelector("#time_diff").textContent = time_diff


	
	// 
	// 
	// 收藏帖子
	// 
	// 
	const collection = () => {
		// 请求锁
		if (lock()) {
			return
		}

		var data = {
			"cmd": "collection",
			"tid": "<?php echo $_GET['tid']; ?>"
		}
		xhr("/servers/space.php", data).then((result) => {
			float_window.create()
			float_window.title("提示")
			float_window.content("已收藏本贴/取消收藏，你可以从右上角进入\"个人空间\"查看已收藏的帖子。")
			float_window.open()
			
			// 更新按钮文字
			document.querySelector("#collection_button span").textContent = result
		})
	}



	// 
	// 
	//  帖子内容标签解析
	// 
	// 
	var html = `<?php echo $data['content']; ?>`

	// 判断fid
	var fid = `<?php echo $fid; ?>`

	// tags获取
	var tags = "<?php echo $data['tags']; ?>".split('|')

	// 如果fid不是1-1就显示tag
	if (fid != '1-1') {

		// 循环每一个tag
		for (let i = 0; i < tags.length; i++) {

			// 特殊标签过滤
			if (tags[i].includes("ep")) {
				continue
			}
			let tag_html = `<span class="tag">${tags[i]}</span>`
			document.querySelector("#tags").insertAdjacentHTML("beforeend", tag_html)
		}
	}

















	// fid == 1-1，有特殊的闭合标签
	if (fid == '1-1') {

		// {?info?}
		if (html.includes("{?info")) {
			// 获取{?info?}内容
			var regex = /{\?info\s*([\s\S]*?)\s*\?\}/
			var info = html.match(regex)[1]

			// 拆分数据
			var gal_info = info.split('\n')
			// [0]	->	开发
			// [1]	->	流程
			// [2]	->	发行日期
			// [3]	->	适合游玩季节
			// [4]	->	背景logo
			// [5]	->	顶部OP

			// 获取各项具体值
			var developer = gal_info[0].match(/.+?:(.*)/)[1]
			var play_time = gal_info[1].match(/.+:(.*)/)[1]
			var releases_date = gal_info[2].match(/.+:(.*)/)[1]
			var season = gal_info[3].match(/.+:(.*)/)[1]
			var logo = gal_info[4].match(/.+:(.*)/)[1]

			// logo判定是否为动态
			if (logo.length > 2) {

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

			// 所有主题
			var theme = "<?php echo $data['tags']; ?>".split('|')

			// 循环每一个主题标签
			for (n = 0; n < theme.length; n++) {

				// 特殊标签，生肉
				if (theme[n] == "生肉") {
					var style = "background: #ce110e; border-color: #830000"
				} else {
					var style = ""
				}

				// 格式化每一个主题
				theme[n] = `<span class="tag" style="${style}">${theme[n]}</span>`
			}

			// 恢复成字符串
			var theme = theme.join("")

			// {?info?} 内格式重新赋予
			var html = html.replace(regex, `
				<ul class="gal_info" style="background: url(/data/imgs/${path}) no-repeat center center">
					<li>主题:${theme}</li>
					<li>开发:${developer}</li>
					<li>流程:${play_time}</li>
					<li>发行日期:${releases_date}</li>
					<li>适合游玩季节:${season}</li>

					<?php

						// 获取该帖评分数据
						$ratings = get_rating($_GET['tid']);

						// 获取个人评分
						if ($uid) {
							$my_rating = get_rating($tid, $uid);
						} else {
							$my_rating['score'] = "0";
							$my_rating['state'] = "未进行";
						}

					?>
					<li class="score" onclick="open_mark()">
						平均分：<?php echo $ratings['average']; ?> (All:<?php echo $ratings['ratings']; ?>)<br>
						你的评分：<?php echo $my_rating['score']; ?><br>
						你的游玩状态：<?php echo $my_rating['state']; ?><br>
					</li>
				</ul>

				<div class="player_window">
					<video class="player" src="" poster="
						<?php
							// 有视频封面
							if ($data['preview']) {
								echo "{$data['chunk']}/{$data['tid']}/{$data['preview']}.avif";

							// 无视频封面
							} else {
								echo "/data/imgs/yingmei.jpg";
							}
						?>
					" preload="auto" allow="autoplay; encrypted-media" allowfullscreen="true" controls controlsList="nodownload"></video>
				</div>
			`)
		}

		// {?text?}
		if (html.includes("{?text")) {

			// 获取{?text?}内容
			var regex = /{\?text\s*([\s\S]*?)\s*\?\}/
			var html = html.replace(regex, `<div class="gal_info_text">$1</div>`)
		}
	}

	// fid == 1-4是动画版块
	if (fid == "1-4") {
		
		// 从tag中获取总集数
		var ep = tags[0]
		var ep = ep.replace("ep", "")

		// 根据集数构建集数html
		var ep_code = ``
		for (let i = 1; i <= ep; i++) {
			
			// 第一个默认添加select
			if (i == 1) {
				var select = "select"
			} else {
				var select = ""
			}

			var ep_code = ep_code + `<span onclick="ep_goto(${i})" id="ep${i}" class="${select}">${i}</span>`
		}
		var ep_code = `<div id="ep">` + ep_code + `</div>`

		var chunk = `<?php echo chunk($_GET['tid'], "anime"); ?>`

		var html = `
			<video id="anime_player" src="/data/animes/${chunk}/<?php echo $_GET['tid']; ?>/<?php echo "1_" . md5("$tid|1"); ?>.mp4" poster="<?php echo $bg_path; ?>" preload="auto" allow="autoplay; encrypted-media" allowfullscreen="true" controls="" controlslist="nodownload"></video>
		` + ep_code + html
	}

	// 生成防剧透class，匹配[_d *]的正则表达式
	var regex = /\[_d\s+([^\[\]]+)\]/g

	// 生成div结构包被{_i23}
	var html = html.replace(regex, function(match, p1) {
		return '<div class="defense_img">' + p1 + '</div>';
	})

	// 
	// 对图片code进行修饰，匹配 {_i*} 的正则表达式
	// 
	var regex = /{(_i(\d+))}/g

	// 替换匹配的内容
	var html = html.replace(regex, `<img src="<?php echo $data['chunk']; ?>/<?php echo $data['tid']; ?>/$2.avif" id="_$2" onclick="fullscreen(this)" loading="lazy">`)

	// 
	// 对视频code进行修饰，匹配 {_v*} 的正则表达式
	// 
	var regex = /{(_v(\d+))}/g

	var	html_code = `
		<div class="player_window">
			<video class="player" src="<?php echo $data['chunk']; ?>/<?php echo $data['tid']; ?>/$2.mp4" poster="<?php echo $bg_path; ?>" preload="auto" allow="autoplay; encrypted-media" allowfullscreen="true" controls controlsList="nodownload"></video>
		</div>
	`

	var html = html.replace(regex, html_code)

	// 
	// 对视频code进行修饰，匹配 {_video*} 的正则表达式
	// 
	var	html_code = `
		<div class="player_window">
			<video class="player" src="$1" poster="<?php echo $bg_path; ?>" preload="auto" allow="autoplay; encrypted-media" allowfullscreen="true" controls controlsList="nodownload"></video>
		</div>
	`
	var html = html.replace(/{_video([^}]+)}/g, html_code)

	// 
	// 对视频code进行修饰，匹配 {_smallvideo*} 的正则表达式
	// 
	var	html_code = `
		<div class="player_window player_window_small">
			<video class="player" src="$1" poster="<?php echo $bg_path; ?>" preload="auto" allow="autoplay; encrypted-media" allowfullscreen="true" controls controlsList="nodownload"></video>
		</div>
	`
	var html = html.replace(/{_smallvideo([^}]+)}/g, html_code)

	// 
	// 对图片code进行修饰，匹配 {_img*} 的正则表达式
	// 
	var	html_code = `<img src="$1" id="_$1" onclick="fullscreen(this)" loading="lazy">`
	var html = html.replace(/{_img([^}]+)}/g, html_code)

	// 
	// 对跳转{_gototid}进行修饰，匹配 {_goto*} 的正则表达式
	// 
	var regex = /{(_goto(\d+))}/g
	var html = html.replace(regex, `<a href="/topic/$2" target="_blank">跳转</a>`)

	// 
	// 对站内音乐{_m}进行修饰，匹配 {_m*} 的正则表达式
	// 
	var regex = /{(_m(\d+))}/g
	var html = html.replace(regex, `（功能暂未开发）`)

	// 
	// 对站内音乐{_music}进行修饰，匹配 {_music*} 的正则表达式
	// 
	var regex = /{(_music(\d+))}/g
	var html = html.replace(regex, `（功能暂未开发2）`)

	// 
	// 对网易云音乐{_wyy}进行修饰，匹配 {_wyy*} 的正则表达式
	// 
	var regex = /{(_wyy(\d+))}/g
	var html = html.replace(regex, `<iframe frameborder="no" border="0" marginwidth="0" marginheight="0" width="100%" height="88" src="https://music.163.com/outchain/player?
type=2&id=$2&auto=0&height=66"></iframe>`)

	// 
	// 对网易云音乐{_subtitle}进行修饰，匹配 {_wyy*} 的正则表达式
	// 
	var regex = /{_subtitle([^}]+)}/g
	var html = html.replace(regex, `<div class="sub_title">$1</div>`)

	// 
	// 对网易云音乐{_wyys}进行修饰，匹配 {_wyys*} 的正则表达式
	// 
	var regex = /{(_wyys(\d+))}/g
	var html = html.replace(regex, `<iframe frameborder="no" border="0" marginwidth="0" marginheight="0" width="100%" height=450 src="//music.163.com/outchain/player?type=0&id=$2&auto=0&height=430"></iframe>`)

	// 
	// 对{?pre *?}进行修饰，匹配 {?pre *?} 的正则表达式
	// 

	// 匹配 {?pre ... ?} 中的内容并替换为 <pre>...</pre>
	var regex = /{\?pre\s*([\s\S]*?)\s*\?}/g;
	var html = html.replace(regex, "<pre>$1</pre>");

	// 
	// 对{?MD5 *?}进行修饰，匹配 {?MD5 *?} 的正则表达式
	// 
	var regex = /{\?MD5\s*([\s\S]*?)\s*\?}/g;
	var html = html.replace(regex, "<pre>文件MD5(用于校验文件完整性和安全性，不懂使用<a href='/topic/5334' target='_blank' style='text-decoration: underline;'>点我</a>)：<br>$1</pre>");

	// 添加帖子内容
	document.querySelector('#content').insertAdjacentHTML("afterbegin", html)


	// 获取op vid
	if (gal_info) {
		var op_vid = gal_info[5].match(/.+:(.*)/)[1]

		if (op_vid) {
			document.querySelector(".player_window .player").src = `<?php echo $data['chunk']; ?>/<?php echo $data['tid']; ?>/${op_vid}.mp4`
		
			// 无opening
		} else {
			document.querySelector(".player_window .player").src = `/data/videos/bbs_opening.mp4`
		}
	}

	// 添加下载功能至bottom
	if (fid == '1-1') {

		// 旧站密码
		if (<?php echo explode("-", $data['date'])[0]; ?> <= 2024) {
			var zip_psw = "飞越地平线fleeworld.top"
		} else {
			var zip_psw = "我未曾忘却galbase.top"
		}

		var download_html = `
			<div class="prompt">解压密码错误看这：<br>下载时网络不稳定数据丢包造成压缩包损坏！避开晚高峰期下载或更换更加稳定的魔法重新下载即可！</div>

			<div class="download">
				<img src="/data/imgs/download.png" style="width: 64px;" onclick="download()" loading="lazy" alt="图片加载失败"><p class="space"></p>
				<span class="tag tag2" onclick="download()">获取该Galgame的下载链接<br>解压密码：${zip_psw}</span>
			</div>

			<br>

			
		`
		document.querySelector('#content .bottom').insertAdjacentHTML("beforeend", download_html)
	}




	// 获取会社所有作品
	if (fid = '1-1') {
		var data = {
			"cmd": "get_developer_works",
			"developer": developer
		}
		console.log(data);
		

		xhr("/server.php", data).then((result) => {
			if (result) {
				// 对跳转{_gototid}进行修饰，匹配 {_goto*} 的正则表达式
				var result = result.replace(/{(_goto(\d+))}/g, `<a href="/topic/$2" target="_blank">跳转</a>`)
				var result = `<pre>以下是${developer}会社的所有作品</pre>` + result

				document.querySelector('#developer_works main').insertAdjacentHTML("beforeend", result)
				document.getElementById('developer_works').hidden = false
			}
		})
	}




























	

	// 
	// 回复区加载
	// 
	const replies_add = (replies) => {

		console.log(replies);
		

		// 有回复
		if (replies != "none") {
			document.getElementById('reply_region').hidden = false
		// 无回复
		} else if (replies == 'none') {
			return
		}

		var replies_region = document.querySelector('#reply_region main')

		// 循环每个回复
		for (n = 0; n < replies.length; n++) {
			// 回复别人的对象存在
			var add = ""
			if (replies[n]['reply_uid']) {
				var add = `<a href="/space/${replies[n]['reply_uid']}" target="_blank">@${replies[n]['reply_uname']}</a>：${replies[n]['reply_content']}<br>`
			}

			let html = `
				<div class="reply_card">
					<div class="auther">
						<img class="avatar" src="${replies[n]['avatar']}" onclick="fullscreen_avatar(this)" loading="lazy" alt="图片加载失败">
						<span class="uname"><a href="/space/${replies[n]['uid']}" target="_blank"><b>${replies[n]['uname']}</b></a></span>
					</div>
					<div class="content">
						${add}
						${replies[n]['content']}
					</div>
					<div class="bottom">
						<input type="text" id="rid_${replies[n]['rid']}" placeholder="请输入需要回复的内容">
						<button onclick="reply(${replies[n]['rid']})">参与回复</button>
						<button class="button2" onclick="remove_reply(${replies[n]['rid']})">风纪执行</button>
					</div>
					<span class="date">${replies[n]['date']} - ${replies[n]['rid']}楼</span>
				</div>
			`
			replies_region.insertAdjacentHTML("beforeend", html)
		}
	}
	<?php
		if ($data['reply_count'] > 0) {
			// 获取最新rid
			$newest_rid = mysqli_query($link, "SELECT rid FROM replies_$tid_last_char WHERE tid=$tid ORDER BY rid DESC LIMIT 1;");
			$newest_rid = $newest_rid->fetch_assoc()['rid'];

			// 获取最大请求值和最新请求值
			// 计算公式:
			// 		min = $page * 20
			// 		max = ($page + 1) *20
			$page = $_GET['page'] ?? "0";

			$min = $page * 20;
			$max = ($page + 1) * 20;

			// 按目标max请求指定数量rid数据（最多请求20个
			$result = mysqli_query($link, "SELECT * FROM replies_$tid_last_char WHERE tid='{$tid}' ORDER BY rid DESC LIMIT $min, $max;");
			if (mysqli_num_rows($result) > 0) {
				// 循环每个reply数据
				$n = 0;
				while ($row = $result->fetch_assoc()) {
					$replies[$n]['rid'] = $row['rid'];
					$replies[$n]['uid'] = $row['uid'];
					$replies[$n]['content'] = $row['content'];
					$replies[$n]['uname'] = get_uname($row['uid']);
					$replies[$n]['avatar'] = get_avatar($row['uid'])['small'];

					// 获取回复日期和reply_rid
					$replies[$n]['date'] = $row['date'];
					$replies[$n]['reply_rid'] = $row['reply_rid'];

					// 存在reply_rid
					if ($replies[$n]['reply_rid']) {

						// 获取reply_rid对应的uid和内容
						$reply_rid = $replies[$n]['reply_rid'];
						$reply_data = mysqli_query($link, "SELECT uid, content FROM replies_$tid_last_char WHERE rid=$reply_rid ;");
						$reply_data = $reply_data->fetch_assoc();

						// 获取uid对应的uname
						$replies[$n]['reply_uid'] = $reply_data['uid'];
						$replies[$n]['reply_uname'] = get_uname($reply_data['uid']);
						$replies[$n]['reply_content'] = $reply_data['content'];
					}
					$n++;
				}
		
				// 交给js处理
				$replies = json_encode($replies);
				echo "replies_add($replies);";
			}
		// 无回复删除前端DOM
		} else {
			echo "replies_add('none');";
		}
	?>




	// 
	// 
	//	回复功能
	// 
	// 
	function reply(rid='') {
		// rid不存在，新回复
		if (!rid) {
			var content = document.querySelector('.reply_content').value
			if (!content) {
				float_window.create()
				float_window.title("提示")
				float_window.content("未输入需要回复的内容")
				float_window.open()
				return
			}

			// 请求锁，防止过量请求
			if (lock()) {
				return
			}

			var data = {
				"cmd": "reply_topic",
				"tid": "<?php echo $_GET['tid']; ?>",
				"content": `${content}`
			}
			xhr("/servers/reply.php", data).then((result) => {
				if (!"<?php echo get_uid() ?>") {
					alert('您当前处于未注册状态，您的回复需要您自己留意回复信息！系统通知不到您！');
				}

				if (result) {
					float_window.create()
					float_window.title("提示")
					float_window.content(result)
					float_window.open()
				} else {
					location.reload()
				}
			})


		// rid存在，追加回复
		} else {
			// 获取追加内容
			var content = document.querySelector(`#rid_${rid}`).value
			if (!content) {
				float_window.create()
				float_window.title("提示")
				float_window.content("未输入需要回复的内容")
				float_window.open()
				return
			}

			var data = {
				"cmd": "reply_reply",
				"rid": rid,
				"tid": "<?php echo $_GET['tid']; ?>",
				"content": `${content}`
			}
			xhr("/servers/reply.php", data).then((result) => {
				if (!"<?php echo get_uid() ?>") {
					alert('您当前处于未注册状态，您的回复需要您自己留意回复信息！系统通知不到您！');
				}
				
				if (result) {
					float_window.create()
					float_window.title("提示")
					float_window.content(result)
					float_window.open()
				} else {
					location.reload()
				}
			})
		}
	}



	// 
	// 
	// 删除评论
	// 
	// 
	const remove_reply = (rid) => {
		// 获取tid
		var tid = "<?php echo $_GET['tid']; ?>"

		var data = {
			"cmd": "remove_reply",
			"rid": rid,
			"tid": "<?php echo $_GET['tid']; ?>",
			"content": `${content}`
		}
		xhr("/servers/reply.php", data).then((result) => {
			// 成功删除
			if (result == 'succ') {

				// 刷新页面
				location.reload()
			}

			// 没权限
			if (result == 'refuse') {
				alert("你没权限删除此贴")
			}
		})
	}


































	// 
	// 
	// 删贴
	// 
	// 
	const remove_topic = () => {
		// 获取风机执行的理由
		var reason = document.querySelector("#judment_reason").value
		
		// 理由不存在
		if (!reason) {
			var reason = "最高权限删除"
		}

		// 构造请求数据
		var data = new FormData()
		data.append("cmd", "remove_topic")
		data.append("tid", "<?php echo $_GET['tid']; ?>")
		data.append("reason", reason)

		// 发送xhr
		var xhr = new XMLHttpRequest()
		xhr.open("POST", "/servers/topic.php", true)
		xhr.send(data)

		// xhr处理
		xhr.onreadystatechange = () => {
			if(xhr.readyState == 4 && xhr.status == 200){
				var result = xhr.responseText

				// 成功删除
				if (result == 'succ') {
					window.open(`/forum/${fid}/0`)
				}

				// 没权限
				if (result == 'refuse') {
					alert("你没权限删除此贴")
				}
			}
		}
	}





	// 
	// 
	// 风纪执行
	// 
	// 
	const judment = () => {
		float_window.create()
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
					</tbody>
				</table>
			</div>
		`)
		float_window.open()
	}



	// 
	// 
	// 打开评分系统
	// 
	// 
	const open_mark = () => {

		// 未登录禁止评分
		if (!"<?php echo $uid; ?>") {
			float_window.create()
			float_window.title("未登录")
			float_window.content("未登录禁止评分！")
			float_window.open()
			return
		}

		float_window.create()
		float_window.title("你的评分")

		<?php
			// 获取个人评分
			if ($uid) {
				$my_rating = get_rating($tid, $uid);
			} else {
				$my_rating['score'] = "";
				$my_rating['state'] = "";
				$my_rating['date'] = "";
			}
		?>

		var score = "<?php echo $my_rating['score']; ?>"
		var state = "<?php echo $my_rating['state']; ?>"
		var date = "<?php echo $my_rating['date']; ?>"

		float_window.content(`
			<div class="rating">
				<input type="text" class="my_score" value="${score}" placeholder="你觉得该作品值几分？">

				<select class="scores">
					<option value="10">10 (masterpiece)</option>
					<option value="9">9 - 10 (excellent)</option>
					<option value="8">8 - 9 (very good)</option>
					<option value="7">7 - 8 (good)</option>
					<option value="6">6 - 7 (decent)</option>
					<option value="5">5 - 6 (so-so)</option>
					<option value="4">4 - 5 (weak)</option>
					<option value="3">3 - 4 (bad)</option>
					<option value="2">2 - 3 (awful)</option>
					<option value="1">1 - 2 (worst ever)</option>
					<option value="0" selected>评分参考</option>
				</select>

				<select class="state">
					<option>已推完</option>
					<option>进行中</option>
					<option>弃坑</option>
					<option>雷作</option>
					<option>未进行</option>
				</select>


				<button class="button2" onclick="update_rating()">更新数据</button>
				<br>您上一次更新于：${date}
			</div>
		`)
		float_window.open()

		// 给selecte标签选择目标
		var score = Math.floor(score);
		document.querySelector(".rating .scores").selectedIndex = 10 - score;

		// 状态选择目标
		var state_dom = document.querySelector('.state');
		for (var i = 0; i < state_dom.options.length; i++) {
  			if (state_dom.options[i].text === state) {
				state_dom.selectedIndex = i;
    			break;
			}
		}

	}



	// 
	// 
	// 更新评分数据
	// 
	// 
	const update_rating = () => {
		var score = document.querySelector(".rating .my_score").value
		var state = document.querySelector(".rating .state").value

		if (!Number(score)) {
			alert("你输入的评分不全为数字！")
			return
		}

		// 构造请求数据
		var data = new FormData()
		data.append("cmd", "update_rating")
		data.append("tid", <?php echo $_GET['tid']; ?>)
		data.append("score", score)
		data.append("state", state)

		// 发送xhr
		var xhr = new XMLHttpRequest()
		xhr.open("POST", "/server.php", true)
		xhr.send(data)

		// xhr处理
		xhr.onreadystatechange = () => {
			if(xhr.readyState == 4 && xhr.status == 200){

				// 刷新页面
				location.reload();
			}
		}
	}








	// 
	// 
	// 下载
	// 
	// 
	const download = () => {
		// 请求锁
		if (lock()) {
			return
		}

		// 弹出提醒别多次点击
		float_window.create()
		float_window.title("下载")
		float_window.content(`
			<b>链接正在获取中...请耐心等待..</b><br>
		`)
		float_window.open()

		var data = {
			"cmd": "download",
			"tid": "<?php echo $_GET['tid']; ?>"
		}
		xhr("/servers/urls.php", data).then((result) => {

			if (result == "limit") {
				alert("你今日下载次数已超过限制。（默认用户一天限制10次下载请求）")
				return
			}

			if (result == "upload") {
				upload_urls()
				return
			}

			if (result == "none") {
				alert("别着急，才刚发帖文件都还没上传完哪来的链接！请稍微再等等！")
				return
			}

			// 解析JSON
			var urls = JSON.parse(result)
			
			float_window.content(`
				<b>OneDrive需要自行挂魔法解决</b><br>
				<b>如果你没有魔法，可以看这个<a href="/topic/782" target="_blank">帖子</a>，学习如何科学上网下载OneDrive</b><br><br>
				
				<table border="1" cellpadding="0px" cellspacing="0px" width="100%">
					<tbody style="text-align: center">
						<tr><td>百度网盘</td> <td><input type="text" style="width: 100%;pointer-events: none;" placeholder="${urls['baidu']}"></td> <td><button onclick="copy('${urls['baidu']}')">复制</button> <button onclick="goto('${urls['baidu']}')">跳转</button></td></tr>
						<tr><td>OneDrive</td> <td><input type="text" style="width: 100%;pointer-events: none;" placeholder="${urls['OD']}"</td> <td><button onclick="copy('${urls['OD']}')">复制</button> <button onclick="goto('${urls['OD']}')">跳转</button></td></tr>
						<tr><td>直链</td> <td><input type="text" style="width: 100%;pointer-events: none;" placeholder="${urls['direct_link']}"</td> <td><button onclick="copy('${urls['direct_link']}')">复制</button> <button onclick="goto('${urls['direct_link']}')">跳转</button></td></tr>
						<tr><td>其他</td> <td><input type="text" style="width: 100%;pointer-events: none;" placeholder="${urls['else_url']}"</td> <td><button onclick="copy('${urls['else_url']}')">复制</button> <button onclick="goto('${urls['else_url']}')">跳转</button></td></tr>
					</tbody>
				</table>

				<br>
				<b style="color:red">TMD你们怎么就不看这句话呢↓ 出了问题还问</b><br>
				<b>解压密码错误都是下载时网络不稳定数据丢包造成压缩包损坏！</b><br>
				<b>避开晚高峰期下载或更换更加稳定的魔法<b style="color:red">重新下载即可！重新下载即可！重新下载即可！</b></b><br>
				<b style="color:red">密码的看一遍能死啊↑</b>
			`)
		})
	}


	// 
	// 
	// 复制
	// 
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
	// 
	// 跳转
	// 
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
	// 
	// 传输网盘链接
	// 
	// 
	const upload_urls = (upload="") => {

		// 不存在链接
		if (!upload) {
			float_window.create()
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
					<button onclick="upload_urls(1)">提交信息</button>
				</div>
			`)
			float_window.open()

		// 存在链接进行提交
		} else {
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
				"cmd": "upload_urls",
				"tid": "<?php echo $_GET['tid']; ?>",
				"baidu": baidu,
				"OD": OD,
				"direct_link": direct_link,
				"else_url": else_url
			}
			xhr("/servers/urls.php", data).then((result) => {
				alert("链接上传完成，自动为您刷新页面。")
				location.reload();
			})
		}
	}




	// 
	// 
	// 查看帖子结构
	// 
	// 
	const view_topic_format = () => {
		float_window.create()
		float_window.title("该贴内容如下")
		float_window.width("80%")

		var data = {
			"cmd": "view_topic_format",
			"tid": "<?php echo $_GET['tid']; ?>"
		}
		xhr("/server.php", data).then((result) => {
			var data = result

			if (data.includes("<")) {
				var data = data.replace(/</g, "&lt;")
			}

			if (data.includes(">")) {
				var data = data.replace(/>/g, "&gt;")
			}

			float_window.content(`<div class="limit"><pre>${data}</pre></div>`)
			float_window.open()
		})
	}



	// 
	// 
	// 移除百度网盘链接
	// 
	// 
	const remove_urls = () => {
		var data = {
			"cmd": "remove_urls",
			"tid": "<?php echo $_GET['tid']; ?>"
		}
		xhr("/servers/urls.php", data).then((result) => {
			if (result == "succ") {
				alert("移除成功")
				location.reload();
			}

			if (result == "refuse") {
				alert("你没权限移除本贴链接")
			}
		})
	}



	// 
	// 动画版块切换集数
	// 
	const ep_goto = (num) => {
		var chunk = `<?php echo chunk($_GET['tid'], "anime"); ?>`

		// 获取当前集数
		var current_ep = document.querySelector("#anime_player").src.split("/").pop().replace(".mp4", "").split("_")[0]
		document.querySelector(`#ep${current_ep}`).className = ""
		document.querySelector(`#ep${num}`).className = "select"

		// 请求加密URL
		var data = {
			"cmd": "decode_anime_ep",
			"tid": "<?php echo $_GET['tid']; ?>",
			"ep": num
		}
		xhr("/server.php", data).then((result) => {
			if (!result) {
				alert("集数跳转失败，网络问题！重新尝试即可！")
			} else {
				document.querySelector("#anime_player").src = `<?php echo $tcp_port; ?>/data/animes/${chunk}/<?php echo $_GET['tid']; ?>/${num}_${result}.mp4`
			}
		})
	}



	// 
	// 将音乐播放器放到回复区
	// 
	const move_to_reply = () => {
		console.log(123);
		const reply_right = document.querySelector('#reply main');
		const play_box = document.querySelector('.play_box');
		reply_right.appendChild(play_box);

		play_box.classList.add("reply_play_box");
	}




	// 
	// 下拉触发
	// 
	const toggleSelect = () => {
		const options = document.getElementById('options');
		

		// 当下拉关闭
		if (options.style.display === 'block') {
			const gal_info_DOM = document.querySelector('.gal_info')
			if (gal_info_DOM) {
				gal_info_DOM.style.pointerEvents = "all"
				document.querySelector('.score').hidden = false
			}
			options.style.display = 'none'

		// 下拉菜单打开
		} else {
			const gal_info_DOM = document.querySelector('.gal_info')
			if (gal_info_DOM) {
				gal_info_DOM.style.pointerEvents = "none"
				document.querySelector('.score').hidden = true
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
				document.querySelector('.score').hidden = false
			}
		}
	});





	// 
	// 
	// 左侧导航栏自动收缩
	// 
	// 
	// window.addEventListener('load', () => {

	// 	// fid3折叠
	// 	var bar_fid3_height = document.querySelector("#fid3").offsetHeight
	// 	var topic_height = document.querySelector("#content").offsetHeight
	// 	var bar_height = document.querySelector(".navigation_bar").offsetHeight

	// 	if (bar_height - topic_height > bar_fid3_height) {
	// 		boards_list(document.querySelector("#fid3"), "3")
	// 	}

	// 	// fid2折叠
	// 	var bar_fid2_height = document.querySelector("#fid2").offsetHeight
	// 	var bar_height = document.querySelector(".navigation_bar").offsetHeight

	// 	if (bar_height - topic_height > bar_fid2_height) {
	// 		boards_list(document.querySelector("#fid2"), "2")
	// 	}

	// 	// fid1折叠
	// 	var bar_fid1_height = document.querySelector("#fid1").offsetHeight
	// 	var bar_height = document.querySelector(".navigation_bar").offsetHeight

	// 	if (bar_height - topic_height > bar_fid1_height) {
	// 		boards_list(document.querySelector("#fid1"), "1")
	// 	}

	// 	// 做减法，从最后一个span标签开始删除
	// 	const navigation_bar = document.querySelector(".navigation_bar .board_2nd");
	// 	const spans = navigation_bar.querySelectorAll('span');

	// 	// 将 NodeList 转换为数组并反转
	// 	Array.from(spans).reverse().forEach((span, index) => {
	// 		const span_height = span.offsetHeight;
	// 		if (document.querySelector(".navigation_bar").offsetHeight - span_height > topic_height) {
	// 			span.hidden = true
	// 		}
	// 	})

	// })

	
</script>

<?php
	if (isset($scripts)) {
		echo($scripts);
	}
 ?>




