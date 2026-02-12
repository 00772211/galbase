<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta name='description' content='GALBase论坛 - Galgame资源站点'>
	<title>加载中...</title>
	<link rel="stylesheet" href="/css/header.css">
	<link rel="stylesheet" href="/css/style.css">
	<link rel="stylesheet" href="/css/forum.css">
</head>
<body>
	<?php
		// 引入顶部导航栏
		require_once dirname(__FILE__).'/header.php';
	?>
	<br>
	<br>
	<?php require_once dirname(__FILE__)."/navigation_bar.php"; ?>



	<div class="board main_board dynamic_height">
		<img src="/data/imgs/title_arc.png" class="title_arc" alt="版块装束图片">
		<div class="board_2nd">
			<header>
				<img src="/data/imgs/title_start.png" class="title_start" alt="版块装束图片">
				<ul class="title_content"><?php echo title_format(get_board_name($_GET['fid'], 1, 1)); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end" alt="版块装束图片">

				<div class="buttons_">
					<?php
						// 资源收入版块2个按钮
						if ($_GET['fid'] == "1-1") {
							echo "<button onclick=\"request_topics_with_score()\">按评分排序</button>";
							echo "<button onclick=\"request_topics_with_normal()\">按默认排序</button>";
						}

						// 番剧版块
						elseif ($_GET['fid'] == "1-4") {
							echo "<button onclick=\"window.open('/topic/153')\">上传动画</button>";
						}

						// 音乐版块1个新按钮
						elseif ($_GET['fid'] == "2-4") {
							echo "<button onclick=\"window.open('/topic/1042')\">上传音乐</button>";
						}
					?>
				</div>
			</header>
			<main>
				<div id="topics"></div>

				<div class="page">
					<button class="last_page">上一页</button>
					<span> 当前页数：<?php echo $_GET['page']; ?> </span>
					<button class="next_page" style="margin-left: 5px;">下一页</button><br><br>
					<input type="text" id="page_number" placeholder="请输入你需要跳转的页数">
					<button onclick="goto_page()">跳转</button>
				</div>
			</main>
		</div>
	</div>



	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>

	<?php require_once dirname(__FILE__)."/js/forum.php"; ?>
	<?php require_once dirname(__FILE__)."/footer.php" ?>




























