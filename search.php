<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta name='description' content='GALBase论坛 - Galgame资源站点'>
	<title>搜索结果</title>
	<!-- css -->
	<link rel="stylesheet" href="/css/header.css">
	<link rel="stylesheet" href="/css/style.css">

	<style>
		.msgs li {
			text-align: left;
			overflow: auto;
		}

		.none_read {
			background-color: #D6790E;
		}

		* {
			margin: 0;
			padding: 0;
		}

		.else {
			width: 90%;
			margin-left: 5%;
		}
	</style>

</head>
<body>
	<!-- 引入顶部导航栏 -->
	<?php require_once dirname(__FILE__).'/header.php'; ?>

	<br>
	<br>

	<div class="board main_board else">
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<header>
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format("搜索结果"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">

				<div class="buttons_">
					<button onclick="search_type('tag')">仅搜索会社</button>
					<button onclick="search_type('tag')">仅搜索TAG</button>
					<button onclick="search_type('title')">仅搜索标题</button>
					<button onclick="search_type('normal')">全文搜索（默认）</button>
				</div>

			</header>
			<main>
				<h2 id="search_state">搜索不能包含特殊符号</h2>
				<ul class="msgs"></ul>
			</main>
		</div>
	</div>
</body>

<script src="/js/float_window.js"></script>
<script src="/js/xhr.js"></script>
<script>
	document.querySelector("#search_state").textContent = "系统正在查询…请耐心等待"
	var data = {
		"cmd": "search",
		"type": "<?php echo $_GET['type']; ?>",
		"content": "<?php echo $_GET['content']; ?>"
	}
	xhr("/servers/search.php", data).then((result) => {
		var result = JSON.parse(result)

		// 获取结果数量
		document.querySelector("#search_state").textContent = `查询结束，查询结果：${result['count']}个`

		// 循环添加至DOM
		var msgs_region = document.querySelector(".msgs")

		for (let i = 0; i < result['count']; i++) {			
			msgs_region.insertAdjacentHTML("beforeend", result[i])
		}
	})
	
	const search_type = (type) => {
		// 请求锁，防止过量请求
		if (lock()) {
			return
		}

		var content = `<?php echo $_GET['content']; ?>`
		window.location.href = `/search.php?type=${type}&content=${content}`	
	}
</script>

















<!-- 引入底部模块 -->
<?php require_once dirname(__FILE__).'/footer.php'; ?>







<!-- 关闭数据库 -->
<?php mysqli_close($link); ?>