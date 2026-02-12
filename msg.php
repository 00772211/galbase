<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta name='description' content='GALBase论坛 - Galgame资源站点'>
	<title>个人通知</title>
	<!-- css -->
	<link rel="stylesheet" href="/css/header.css">
	<link rel="stylesheet" href="/css/style.css">

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
	<?php require_once dirname(__FILE__).'/header.php'; ?>
	<?php
		// 未登录处理
		if (!$uid) {
			exit("<script>alert('当前页面需要登录才能查看内容')</script>");
		}
	?>

<br>
	<br>

	<div class="board main_board else">
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<header>
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format("个人通知"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">
				<div class="buttons"><button onclick="finish_read()">全部标为已读</button></div>
			</header>
			<main>
				<ul class="msgs">
					<!-- <li><span class="box none_read">未读</span><span>2023-08-16 23:48</span>：你的回复被系统删除</li> -->
					<!-- <li><span class="box">已读</span><span>2023-08-16 23:48</span> -> lzh_2(1)回复了你的帖子<a href="/index.php">这是一个测试</a>：这是回复信息</li> -->
				</ul>
			</main>
		</div>
	</div>





</body>


<script>

	// 标为已读
	const finish_read = () => {

		// 构建xhr请求
		var data = new FormData()
		data.append("cmd", "finish_read")

		// 发送xhr
		var xhr = new XMLHttpRequest()
		xhr.open("POST", '/server.php', true)
		xhr.send(data)

		// xhr处理
		xhr.onreadystatechange = () => {
			if(xhr.readyState == 4 && xhr.status == 200){
				try {
					window.location.href = '/msg.php'
				} catch (error) {
					console.error('xhr请求失败，失败code：' + error)
				}
			}
		}
		
		// xhr请求超时
		xhr.timeout = 9000	// ms
		xhr.ontimeout = () => alert("请求超时")
	}



















	// 获取DOM元素
	const msgs_div = document.querySelector('.msgs')
	
	// 消息添加
	const add_msg = (msgs) => {
		// 循环添加
		for (n = 0; n < msgs.length; n++) {
			// 未读判断
			if (msgs[n]['read'] == 0) {
				// css用
				var state = 'tag3'
				msgs[n]['read'] = '未读'

			// 已读判断
			} else {
				var state = ''
				msgs[n]['read'] = '已读'
			}

			// 整合消息添加
			var html = `
				<li><span class="tag ${state}">${msgs[n]['read']}</span>${msgs[n]['date']} -> ${msgs[n]['content']}</li>
			`
			console.log(`${msgs[n]['content']}`);
			msgs_div.insertAdjacentHTML("beforeend", html)
		}
	}





	// 构建xhr请求
	var data = new FormData()
	data.append("cmd", "request_msgs")

	// 发送xhr
	var xhr = new XMLHttpRequest()
	xhr.open("POST", '/server.php', true)
	xhr.send(data)

	// xhr处理
	xhr.onreadystatechange = () => {
		if(xhr.readyState == 4 && xhr.status == 200){
			try {
				// 获取xhr返回值
				var return_data = xhr.responseText

				// 存在返回信息
				if (return_data) {
					// 解析返回值为JSON格式
					var return_data = JSON.parse(return_data)

					// 添加每个消息
					add_msg(return_data)
				// 不存在返回信息
				} else {
					alert('当前你账号没有任何个人通知')
				}

			} catch (error) {
				console.error('xhr请求失败，失败code：' + error)
			}
		}
	}
	
	// xhr请求超时
	xhr.timeout = 9000	// ms
	xhr.ontimeout = () => alert("请求超时")
</script>

























<!-- 引入底部模块 -->
<?php require_once dirname(__FILE__).'/footer.php'; ?>







<!-- 关闭数据库 -->
<?php mysqli_close($link); ?>