<!-- 壁纸轮换ul -->
<ul class="cb-slideshow">
	<li><span></span></li>
	<li><span></span></li>
	<li><span></span></li>
	<li><span></span></li>
	<li><span></span></li>
	<li><span></span></li>
</ul>



<div class="board navigation_bar">
	<div class="board_2nd">
		<a href="/send_topic.php?mod=add" target="_blank" style="color: black"><span><img src="/data/imgs/new.png" style="width: 20px;" alt="图片加载失败">发帖</span></a>
		<span onclick="goto_top()"><img src="/data/imgs/top.png" style="width: 20px;" alt="图片加载失败">主页</span>
		<span onclick="goto_bottom()"><img src="/data/imgs/top.png" style="width: 20px; rotate: 180deg;" alt="图片加载失败">底部</span>
	
		<?php
			// 帖子页面需要加载作者模板
			if (strstr($_SERVER['REQUEST_URI'], "view_topic.php") || strstr($_SERVER['REQUEST_URI'], "/topic/")) {
				require_once dirname(__FILE__)."/navigation_bar_view_topic.php";
			}
		?>

		<span onclick="tags_list(this)"><img src="/data/imgs/arrow.png" style="height: 10px;" alt="图片加载失败">热门标签</span>
		<span id="tags_list" style="display:block;">
			<ul>
				<?php 
					// tag获取排除全数字和ep，数字特俗仅保留0721
					$tags = mysqli_query($link, "SELECT tag FROM `tags_index` WHERE tag NOT LIKE '%ep%' AND (tag NOT REGEXP '^[0-9]+$' OR tag = '0721') ORDER by count DESC LIMIT 30;");

					while ($row = $tags->fetch_assoc()) {
						$tag =  $row['tag'];
						if ($tag != "更新日志" && $tag != "ep12" && $tag != "ep24") {
							echo "<li class='tag'><a href='/search.php?type=tag&content=$tag' target='_blank'>$tag</a></li>";
						}
					}
				?>
			</ul>
		</span>

		<span id="fid1" onclick="boards_list(this, 1)"><img src="/data/imgs/arrow.png" style="height: 10px;" alt="图片加载失败">学园集聚之地版块</span>
		<span id="boards_list_1" style="display:block;">
			<ul>
				<li><a href="/forum/1-1/0" target="_blank"><?php echo get_board_name("1-1"); ?></a></li>
				<li><a href="/forum/1-2/0" target="_blank"><?php echo get_board_name("1-2"); ?></a></li>
				<li><a href="/forum/1-3/0" target="_blank"><?php echo get_board_name("1-3"); ?></a></li>
				<li><a href="/forum/1-4/0" target="_blank"><?php echo get_board_name("1-4"); ?></a></li>
			</ul>
		</span>

		<span id="fid2" onclick="boards_list(this, 2)"><img src="/data/imgs/arrow.png" style="height: 10px;" alt="图片加载失败">学园文学茶馆版块</span>
		<span id="boards_list_2" style="display:block;">
			<ul>
				<li><a href="/forum/2-1/0" target="_blank"><?php echo get_board_name("2-1"); ?></a></li>
				<li><a href="/forum/2-2/0" target="_blank"><?php echo get_board_name("2-2"); ?></a></li>
				<li><a href="/forum/2-3/0" target="_blank"><?php echo get_board_name("2-3"); ?></a></li>
				<li><a href="/forum/2-4/0" target="_blank"><?php echo get_board_name("2-4"); ?></a></li>
			</ul>
		</span>

		<span id="fid3" onclick="boards_list(this, 3)"><img src="/data/imgs/arrow.png" style="height: 10px;" alt="图片加载失败">璀璨群星之上版块</span>
		<span id="boards_list_3" style="display:block;">
			<ul>
				<li><a href="/forum/2-1/0" target="_blank"><?php echo get_board_name("3-1"); ?></a></li>
				<li><a href="/forum/2-2/0" target="_blank"><?php echo get_board_name("3-2"); ?></a></li>
			</ul>
		</span>
		<span><img src="/data/imgs/list.png" style="height: 14px;" alt="图片加载失败"><a href="/topic/1004" target="_blank" style="color: black">论坛使用教程</a></span>
		<span><img src="/data/imgs/url.png" style="width: 18px;" alt="图片加载失败"><a href="/topic/381" target="_blank" style="color: black">BUG反馈</a></span>
		<span><img src="/data/imgs/url.png" style="width: 18px;" alt="图片加载失败"><a href="/topic/514" target="_blank" style="color: black">提供GAL</a></span>
		<span><img src="/data/imgs/url.png" style="width: 18px;" alt="图片加载失败"><a href="/topic/689" target="_blank" style="color: black">请求汉化GAL</a></span>
		<span><img src="/data/imgs/url.png" style="width: 18px;" alt="图片加载失败"><a href="/topic/953" target="_blank" style="color: black">支持本站</a></span>
		<span><img src="/data/imgs/url.png" style="width: 18px;" alt="图片加载失败"><a href="/topic/1184" target="_blank" style="color: black">防迷路看这</a></span>
		<?php
			if (strstr($_SERVER['REQUEST_URI'], "index.php") || $_SERVER['REQUEST_URI'] == "/") {
				$online = mysqli_query($link, "SELECT COUNT(uid) FROM online; ")->fetch_assoc()['COUNT(uid)'];
				$highest_onlie = get_value("sys_auto_increment_value", "value", "variable='highest_online'");
				$topics_count = mysqli_query($link, "SELECT COUNT(tid) FROM topics_index; ")->fetch_assoc()['COUNT(tid)'];
				echo '<span><img src="/data/imgs/star.png" style="width: 18px;" alt="图片加载失败">今日在线: ' . $online .  '</span>';
				echo '<span><img src="/data/imgs/star.png" style="width: 18px;" alt="图片加载失败">最高在线: ' . $highest_onlie . '</span>';
				echo '<span><img src="/data/imgs/star.png" style="width: 18px;" alt="图片加载失败">论坛帖数: ' . $topics_count . '</span>';
			}
		?>
		
	</div>
</div>

<script>

	// 
	// 月慕的最新资讯位置
	// 
	const add_ym_topics = (topics) => {
		ym_DOM = document.querySelector(".navigation_bar .board_2nd")

		for (n = 0; n < topics.length; n++) {
			let title = topics[n]['title']
			let pre = topics[n]['pre']
			let src = topics[n]['src']
			let html = `
				<span class="ym" id="ym_${n}" hidden>
					<a href="${src}" target="_blank">
						<img src="${pre}" alt="月慕最新资讯预览图" title="${title}" loading="lazy">
						<p title="${title}">${title}</p>
					</a>
				</span>
			`
			ym_DOM.insertAdjacentHTML("beforeend", html)
		}
	}

	// 
	// 重分配ym资讯
	// 
	const updata_ym = (new_height) => {

		// 初次加载隐藏所有
		for (i = 0; i < 10; i++) {
			try {
				document.querySelector(`#ym_${i}`).hidden = true
			} catch (error) {
				console.log(error);
			}
		}

		// 计算第一个咨询的卡片高度
		let ym_card = document.querySelector(`#ym_0`)
		ym_card.hidden = false

		// 卡片有padding6属性，需要-12得到图片的长度，图片长宽比190:100，除以1.9获得图片高
		let img_height = (ym_card.offsetWidth - 12) / 1.9

		// 获取文本高
		let text_height = document.querySelector(`#ym_0 p`).offsetHeight

		// 卡片总高，有padding6属性，所以要加12
		let ym_card_height = img_height + text_height + 12

		// 可继续添加
		let navigation_bar_height = document.querySelector(".navigation_bar").offsetHeight
		if (navigation_bar_height < new_height) {
			
			// 计算可添加个数，做向下取整
			let num = Math.floor((new_height - navigation_bar_height) / ym_card_height)

			for (i = 1; i < num + 1; i++) {
				document.querySelector(`#ym_${i}`).hidden = false
			}

		} else {
			ym_card.hidden = true
		}
	}



	// 
	// 重新分配导航栏
	// 
	const updata_bar = (new_height) => {
		// 做减法，从最后一个span标签开始删除
		const navigation_bar = document.querySelector(".navigation_bar .board_2nd");
		const spans = navigation_bar.querySelectorAll('span');

		// 全部显示
		Array.from(spans).forEach((span, index) => {
			span.hidden = false
		})
		
		// 将 NodeList 转换为数组并反转，在末尾span做减法
		Array.from(spans).reverse().forEach((span, index) => {
			const span_height = span.offsetHeight;
			if (document.querySelector(".navigation_bar").offsetHeight - span_height > new_height) {
				span.hidden = true
			}
		})
	}


	// 
	// 获取最新的ym咨询到前端
	// 
	<?php
		$ym_data = mysqli_query($link, "SELECT * FROM `ymgal` WHERE 1; ");
		if (mysqli_num_rows($ym_data) != 0) {
			$n = 0;
			// 循环每个msg
			while ($row = $ym_data->fetch_assoc()) {
				$ym[$n]['title'] = $row['title'];
				$ym[$n]['pre'] = $row['preview'];
				$ym[$n]['src'] = $row['src'];
				$n++;
			}

			$ym_topics = json_encode($ym);
			echo "add_ym_topics($ym_topics)";
		}
	?>


</script>