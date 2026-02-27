<h1><br></h1>

<!-- 音乐播放器 -->
<?php
	if ($_SERVER['REQUEST_URI'] == "/" || $_SERVER['REQUEST_URI'] == "/index.php" || strstr($_SERVER['REQUEST_URI'], "/topic/")) {
		echo '<link rel="stylesheet" href="/css/player.css">';
		require_once dirname(__FILE__)."/player.php";
		echo '<script src="/js/player.js"></script>';
	}
?>


<!-- 弹幕 -->





<?php
	// 更新最新日期
	$today = get_time("Y-m-d");
	$server_today = get_value("sys_auto_increment_value", "value", "variable='today'");
	if ($server_today != $today) {
		require_once __DIR__."/functions_else.php";

		// 判断结算时最高在线人数
		$online = mysqli_query($link, "SELECT COUNT(uid) FROM online; ")->fetch_assoc()['COUNT(uid)'];
		$highest_onlie = get_value("sys_auto_increment_value", "value", "variable='highest_online'");
		if ($online > $highest_onlie) {
			mysqli_query($link, "UPDATE sys_auto_increment_value SET value='$online' WHERE variable='highest_online' LIMIT 1");
		}

		// 清空在线列表
		mysqli_query($link, "DELETE FROM `online`;");

		// 更新日期
		mysqli_query($link, "UPDATE sys_auto_increment_value SET value = '{$today}' WHERE variable='today' LIMIT 1;");

		// 清空今日浏览数
		mysqli_query($link, "UPDATE sys_auto_increment_value SET value='0' WHERE variable='views' LIMIT 1");

		// 
		// 获取今日推荐5个GAL
		// 
		$result = mysqli_query($link, "SELECT tid FROM topics_index WHERE fid='1-1' AND tid > 0 AND no_push IS NULL ORDER BY RAND() LIMIT 5;");

		// 清楚tids变量，来源于/js/index.php中的$tids会影响到下面的值
		unset($tids);
		$tids = [];

		while ($row = $result->fetch_assoc()) {
			// 获取tid
			$tid = $row['tid'];
			$tids[] = $tid;
		}

		$tids = implode("||", $tids);

		mysqli_query($link, "UPDATE sys_auto_increment_value SET value='$tids' WHERE variable='recommend' LIMIT 1; ");

		// 更新月慕咨询
		ym_get();

		// 更新touchgal
		touchgal_get();

		// 月份更新，自动重置热门tag
		if (get_time("m") != explode("-", $server_today)[1]) {
			
			// 所有非热门tag浏览量清零
			mysqli_query($link, "UPDATE tags_index SET count = 0 WHERE tag NOT IN (SELECT tag FROM (SELECT tag FROM tags_index ORDER BY count DESC LIMIT 30) AS top30); ");

			// 热门tag浏览量设置为1，目的是让系统有正确的sql索引
			mysqli_query($link, "UPDATE tags_index SET count = 1 WHERE tag IN (SELECT tag FROM (SELECT tag FROM tags_index ORDER BY count DESC LIMIT 30) AS top30); ");
		}
	}
?>






<div id="imp">
<?php echo $config['maintenance_msg']; ?>
</div>









<!-- 右下角显示壁纸按钮 -->
<div class="show_bg" onclick="show_bg()">
	<img src="/data/imgs/title_start.png" class="bt" title="观看背景 / 回到论坛" alt="图片加载失败">
	<img src="/data/imgs/title_arc.png" class="title_arc">
</div>


<footer>
	<div class="t">
		<img class="bg" src="/data/imgs/footer_bg.avif" alt="footer上侧背景">
		<img class="dark" src="/data/imgs/footer.avif" alt="footer上册套图">
		<div class="content">
			<h1>欢迎访问 GALBase！</h1>
			本站于2024年12月31日与FleeWorld论坛(2019-12-24 - 2024-12-24)合并！<br>
			<br>
			…谢谢你，在无数的站点之中发现了我。<br>
			…谢谢你…喜欢上我们的站点。<br>
			谢谢你，能够爱上…这样的站点。<br>
			<br>
			<br>
			如果你有HTML CSS JS PHP MYSQL相关知识和一点点热心！<br>
			都可以直接在论坛内多交流加入我们！
		</div>
	</div>

	<div class="b">
		<br><br><br>
		<div class="links">
			<img class="logo" src="/data/imgs/logo.png" alt="本站LOGO">

 			<ul>
				<li>友情链接</li>
				<li><a href="/topic/1" target="_blank">本站源码</a></li>
			</ul>

			<ul>
				<li><a href="https://www.kungal.com" target="_blank">鲲 Galgame</a></li>
				<li><a href="https://www.touchgal.io" target="_blank">TouchGal</a></li>
			</ul>
			<ul>
				<li><a href="https://shinnku.com" target="_blank">真红小站</a></li>
				<li><a href="https://2dfan.com" target="_blank">2DFan</a></li>
			</ul>
			<ul>
				<li><a href="https://www.ttloli.com" target="_blank">忧郁的 Loli</a></li>
				<li><a href="https://soul-plus.net" target="_blank">南+ South Plus</a></li>
			</ul>
			<ul>
				<li><a href="https://www.hikarinagi.org" target="_blank">Hikarinagi</a></li>
				<li><a href="https://gallibrary.pw" target="_blank">GAL 图书馆</a></li>
			</ul>

			<div class="contact-info">
				<p>联系：<strong>admin@galbase.top</strong> 或者站内发帖！</p>
				<p>免费提供二级域名：<strong>galbase.top</strong>&emsp;<strong>0d000721.cc</strong>&emsp;<strong>ciallo.ca</strong></p>
			</div>
			
		</div>
		<br>
	
	</div>
</footer>




<?php
	// 下雪
	if ($config['snow']) {
		require_once dirname(__FILE__).'/js/snow.php';
	}
?>





<script>
	// 
	// 回到论坛主页或顶部
	// 
	const goto_top = () => {
		const path = window.location.pathname

		// 在主页，/和/index.php等效主页
		if (path == "/" || path == "/index.php") {
			document.querySelector(".op_board").scrollIntoView({ behavior: 'smooth', block: 'start' });

		// 不在主页
		} else {
			window.open(`/`, "_self")
		}
	}



	// 
	// 
	// 回到论坛底部
	// 
	// 
	const goto_bottom = () => {
		document.querySelector("footer .content").scrollIntoView({ behavior: 'smooth', block: 'start' });
	}


	
	// 
	// 
	// 打开tags列表
	// 
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
	// 
	// 版块列表
	// 
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
	// 
	// 显示背景
	// 
	// 
	const show_bg = () => {
		// 从header导航栏中索取visibility属性判定是否需要回显
		var state = document.querySelector(".header")

		// 需要恢复div
		if (state.style.visibility == 'hidden') {
			// 获取body标签内的所有div元素
			var divs = document.body.getElementsByTagName('div')

			// 遍历所有div显示
			for (var i = 0; i < divs.length; i++) {
				divs[i].style.visibility = 'visible'
			}

		// 隐藏div显示背景
		} else {
			// 获取body标签内的所有div元素
			var divs = document.body.getElementsByTagName('div')

			// 遍历所有div隐藏
			for (var i = 0; i < divs.length; i++) {
				divs[i].style.visibility = 'hidden'
			}

			// 保留class="show_bg"功能按钮
			var show_bg = document.querySelector(".show_bg")
			show_bg.style.visibility = 'visible'
		}
	}
	// 
	// 
	// 3分钟记录添加一次在线时间
	// 
	// 
	function add_online_time() {
		if ("<?php echo $uid; ?>" == "1") {
			return		
		}

		// 通过xhr更新密码
		var data = new FormData()
		data.append("cmd", "add_online_time")

		// 发送xhr
		var xhr = new XMLHttpRequest()
		xhr.open("POST", "/server.php", true)
		xhr.send(data)

		// 进入下一次循环
		setTimeout(add_online_time, 5 * 60 * 1000);
	}

	setTimeout(add_online_time, 5 * 60 * 1000);
</script>






<script>
	// 监听所有版块
	const board_telescope = document.querySelectorAll('.board');

	// 遍历所有版块
	board_telescope.forEach(target_board => {

		if (target_board.querySelector('.telescope_top')) {
			const telescope = target_board.querySelector('.telescope_top')
			const light_star = target_board.querySelector('.light_star')

			// 鼠标移入目标版块
			target_board.addEventListener('mouseenter', () => {
				telescope.style.animation = "telescope_down 1s 1"
				telescope.style.animationFillMode = "forwards";
				light_star.style.animation = "light_star 2s 1"
			})

			// 鼠标移除目标版块
			target_board.addEventListener('mouseleave', () => {
				telescope.style.animation = "telescope_up 1s 1"
				telescope.style.animationFillMode = "forwards"
				light_star.style.animation = ""
			})
		}
	})
</script>





<script>
	// 判断设备
	function detectDeviceType() {
    	var userAgent = navigator.userAgent;
		if (/Android/i.test(userAgent)) {
			return "手机";
		} else if (/iPhone|iPad|iPod/i.test(userAgent)) {
			return "手机";
		} else if (/Windows Phone/i.test(userAgent)) {
			return "手机";
		} else if (/Macintosh|MacIntel|MacPPC|Mac68K/i.test(userAgent)) {
			return "电脑";
		} else if (/Windows|Win16|Win32|Win64/i.test(userAgent)) {
			return "电脑";
		} else if (/Linux/i.test(userAgent) && !/Android/i.test(userAgent)) {
			return "电脑";
		} else if (/iPad/i.test(userAgent)) {
			return "平板";
		} else {
			return "未知设备";
		}
	}

	var deviceType = detectDeviceType();
	if (deviceType == "手机") {
		alert("检测到当前你在用手机浏览本论坛，本论坛仅适配PC！")
	}



	// 
	// 监听内容高度变化
	// 
	const dynamic_height = document.querySelector('.dynamic_height');

	if (dynamic_height) {
		const resizeObserver = new ResizeObserver((entries) => {
			for (const entry of entries) {
				const newHeight = entry.target.offsetHeight;
				updata_bar(newHeight)
				updata_ym(newHeight);
				
				console.log("新增月慕资讯内容和导航栏");
			}
		})

		resizeObserver.observe(dynamic_height);
	}



// 在DOM加载完成后加载视差
document.addEventListener('DOMContentLoaded', () => {
	// 
	// 底部dark视差
	// 
	const darkImg = document.querySelector('footer .t .dark');
	let isVisible = false;
	let latestScrollY = 0;
	let ticking = false;
	const maxOffset = 200; // 正数向下移动，负数向上移动

	// 1. 用 IntersectionObserver 判断元素是否在可视区
	const observer = new IntersectionObserver((entries) => {
		entries.forEach(entry => {
			isVisible = entry.isIntersecting;
		});
	}, { threshold: 0 });

	observer.observe(darkImg);

	// 2. 滚动事件只记录滚动值
	window.addEventListener('scroll', () => {
		latestScrollY = window.scrollY;
		requestTick();
	});

	// 3. requestAnimationFrame 优化计算
	function requestTick() {
		if (!ticking) {
			requestAnimationFrame(updateParallax);
			ticking = true;
		}
	}

	// 4. 更新视差位置
	function updateParallax() {
		ticking = false;
		if (!isVisible) return;

		const rect = darkImg.getBoundingClientRect();
		const viewportHeight = window.innerHeight;

		// progress 0~1 表示元素进入视口的程度
		let progress = (viewportHeight - rect.top) / (viewportHeight + rect.height);
		progress = Math.min(Math.max(progress, 0), 1); // 限制在0~1之间

		darkImg.style.transform = `translateY(${maxOffset * progress}px)`;
	}
});

// 	// 
// 	// 播放器停止进入footer
// 	// 
// 	const playBox = document.querySelector('.play_box');
// 	const footer = document.querySelector('footer');

// 	// 固定播放器距离底部的默认距离
// 	const fixedBottom = 16; // 对应 CSS 中 1cqw 的 px 值，大约根据屏幕自适应调整

// 	window.addEventListener('scroll', () => {
// 		// 页面可视高度
// 		const viewportHeight = window.innerHeight;
// 		// footer 距离页面顶部的距离
// 		const footerTop = footer.getBoundingClientRect().top + window.scrollY;

// 		// 当前滚动到底部的位置
// 		const scrollBottom = window.scrollY + viewportHeight;

// 		if (scrollBottom >= footerTop) {
// 			// 当滚动到底部 footer 时，让播放器停在 footer 上方
// 			const offset = scrollBottom - footerTop + fixedBottom;
// 			playBox.style.bottom = `${offset}px`;
// 		} else {
// 			// 普通固定在页面底部
// 			playBox.style.bottom = `${fixedBottom}px`;
// 		}
// 	});

</script>