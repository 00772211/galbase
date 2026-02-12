<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta name='description' content='GALBase论坛 - Galgame资源站点'>
	<title></title>
	<!-- css -->
	<link rel="stylesheet" href="/css/header.css">
	<link rel="stylesheet" href="/css/style.css">
	<link rel="stylesheet" href="/css/space.css">

	<style>
		/* 每条通知 */
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
	<?php
		require_once dirname(__FILE__).'/header.php';
		require_once dirname(__FILE__).'/js/space.php';
	?>
	<?php
		// 判断有没有输入uid
		if (!$_GET['uid']) {
			exit("URL内容不合法，未填入UID。");
		}
	?>

	<br>
	<br>

	<div class="board main_board else">
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<header>
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format(get_uname($_GET['uid'])."的空间"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">
			</header>
			<main>
				<div class="left">
					<div class="card">
						<div class="split">
							<img class="shool_logo" src="/data/imgs/pannya_icon.png" alt="图片加载失败">
							<span class="name">结姬学园 / 各务台学园</span>
							<img class="telescope" src="/data/imgs/telescope_white.png" alt="图片加载失败">
						</div>

<?php
	// 获取用户数据
	$uid_last_char = substr($_GET['uid'], -1);
	$auther = get_uname($_GET['uid']);
	$user_data = mysqli_query($link, "SELECT academic_year, identity, register_time, last_login_time FROM `users_data_$uid_last_char` WHERE uid={$_GET['uid']} LIMIT 1;")->fetch_assoc();
?>


						<div class="info">
							<div class="avatar"><img src="<?php echo get_avatar($_GET['uid'])['medium']; ?>" alt=""></div>	
							<div class="student">
								<span>学生：<?php echo $auther." ({$_GET['uid']})"; ?></span>
								<span>学年：<?php echo $user_data['academic_year']; ?></span>
								<span>身份：<?php echo $user_data['identity']; ?></span>
							</div>
						</div>

						<div class="footer">
							<span class="reg">入学时间：<?php echo $user_data['register_time']; ?></span>
							<span class="last">最后在校时间：<?php echo substr($user_data['last_login_time'], 0, 10) ?></span>
						</div>

					</div>
				</div>

				<div class="right">
					<div class="window">
						<div class="title">已经推完的Galgame</div>
						<ul class="content">
						</ul>
					</div>
				</div><br>
			</main>

			<main>
				<div class="collection">
					<span class="title">收藏的帖子</span>
					<ul class="topics">
					</ul>
				</div>
			</main>

			<main class="best_works_f">
				<div class="best_works">
					<span class="title"><?php echo $uname; ?>推荐此生必玩之作</span>
					<ul class="works"></ul>
				</div>
			</main>
		</div>
	</div>
</body>






<script src="/js/xhr.js"></script>
<script>
	// 更新title
	document.title = "<?php echo get_uname($_GET['uid']); ?>的空间"
	var collection_region = document.querySelector('.topics')
	var finished_gal = document.querySelector('.right .content')

	// 更新收藏的帖子
	var data = {
		"cmd": "request_collections",
		"uid": "<?php echo $_GET['uid'] ?>"
	}

	xhr("/servers/space.php", data).then((result) => {

		// 存在
		if (result) {
			// 判断列表长度
			var result = JSON.parse(result)
			
			for (n = 0; n < result.length; n++) {
				html = `<li><a href="/topic/${result[n]['tid']}" target="_blank">${result[n]['title']}</a> </li>`
				collection_region.insertAdjacentHTML("afterbegin", html)
			}

		// 不存在收藏
		} else {
			document.querySelector('.collection').parentElement.hidden = true
		}
	})

	//更新已推GAL
	var data = {
		"cmd": "request_finished_galgame",
		"uid": "<?php echo $_GET['uid'] ?>"
	}
	xhr("/servers/space.php", data).then((result) => {
		// 存在
		if (result) {
			// 判断列表长度
			var result = JSON.parse(result)

			// 循环数据
			for (const date in result) {
				if (result.hasOwnProperty(date)) {
					let date_ = date.slice(0, -3);
					html = `<li>${date_} -><a href="/topic/${result[date]['tid']}" target="_blank">${result[date]['title']}</a></li>`
					finished_gal.insertAdjacentHTML("afterbegin", html)
				}
			}
		}
	})

	// 头像加载完后平衡左右2个高度
	if (document.querySelector('.info .avatar img').complete) {
		const left_h = document.querySelector('.left .card').clientHeight
		const right_title_h = document.querySelector('.right .title').clientHeight
		const right_h = left_h - right_title_h - 30
		document.querySelector('.right .content').style.maxHeight = `${right_h}px`;
	}

	// 
	// 拖拽卡片
	// 
	const list = document.querySelector(".best_works .works")
	let drag_source = null;

	// 拖拽开始
	list.addEventListener("dragstart", (e) => {
		if (!e.target.classList.contains("move")) return;

		// 设置拖拽效果
		e.dataTransfer.effectAllowed = 'move';

		// 保存拖拽源（move 的父级 .best_works .works li 元素）
		drag_source = e.target.closest('.best_works .works li');

		setTimeout(() => {
			drag_source.classList.add('moving');
		}, 0);
	});

	// 拖拽允许放置
	list.addEventListener("dragover", (e) => {
		e.preventDefault();
	});

	// 拖拽进入目标
	list.addEventListener("dragenter", (e) => {
		e.preventDefault();

		// 只处理目标是 .best_works .works li 元素，且不是自身
		const target = e.target.closest('.best_works .works li');

		if (!target || target === drag_source) return;

		const children = Array.from(list.children);
		const source_index = children.indexOf(drag_source);
		const target_index = children.indexOf(target);

		const all_index = Array.from(document.querySelector(".best_works .works").children).length;

		// 添加卡片的帖子禁止移动
		if (target_index == all_index - 1) {
			return
		}

		// 判断是向前拖入，还是向后拖入
		if (source_index < target_index) {
			list.insertBefore(target, drag_source);
		} else {
			list.insertBefore(drag_source, target);
		}
	});


	// 
	// 拖拽结束
	// 
	list.addEventListener("dragend", (e) => {
		if (drag_source) {
			drag_source.classList.remove('moving');
			drag_source = null;

			// 获取所有作品父级DOM
			const stories_DOM = document.querySelector(".best_works .works");

			// 获取所有直接子元素
			const stories = stories_DOM.children;

			// 提取子元素的 tid 并拼接成字符串
			var tids = Array.from(stories).map(stories => stories.id).join('|');
			
			// 去除多余项
			var tids = tids.replace("|_0", "").replaceAll("_", "")

			//更新新顺序
			var data = {
				"cmd": "refresh_works",
				"uid": "<?php echo $_GET['uid'] ?>",
				"tids": tids
			}
			xhr("/servers/space.php", data).then((result) => {
				if (!result) {
					alert("移动失败，可能是网络问题，建议重新尝试移动！")
				}
			})
		}
	})



	// 
	// 删除作品
	// 
	const remove_work = (tid) => {
		//更新新顺序
		var data = {
			"cmd": "remove_work",
			"uid": "<?php echo $_GET['uid'] ?>",
			"tid": tid
		}
		xhr("/servers/space.php", data).then((result) => {
			if (!result) {
				alert("删除失败，请不要更改前端结构！！")
			} else {
				location.reload();
			}
		})
	}
</script>







<!-- 引入底部模块 -->
<?php require_once dirname(__FILE__).'/footer.php'; ?>





