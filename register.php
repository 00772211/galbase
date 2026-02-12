<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta name='description' content='GALBase论坛 - Galgame资源站点'>
	<title>注册 / 登录</title>
	<!-- css -->
	<link rel="stylesheet" href="/css/header.css">
	<link rel="stylesheet" href="/css/style.css">
</head>
<body>
	<?php
		// 引入顶部导航栏
		require_once dirname(__FILE__).'/header.php';
	?>
	<br>
	<br>
	<?php require_once dirname(__FILE__)."/navigation_bar.php"; ?>



	<div class="board main_board">
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<header>
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format("注册"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">
			</header>
			<main style="text-align: center;">
				<input type="text" id="reg_uname" placeholder="用户名">
				<input type="password" id="reg_psw" placeholder="密码"><br><br style="line-height: 5px">
				<button onclick="register()">提交注册信息</button>
			</main>
		</div>
	</div>

	

	<div class="board main_board" style="margin-top: 15px;">
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<header>
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format("登录"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">
			</header>
			<main style="text-align: center;">
				<input type="text" id="login_uname" placeholder="用户名">
				<input type="password" id="login_psw" placeholder="密码"><br style="line-height: 5px">
				<span>忘记密码就新注册个账号，发帖让站长帮你找回密码！</span><br>
				<button onclick="login()">提交登录信息</button>
			</main>
		</div>
	</div>



	<div class="board main_board" style="margin-top: 15px;">
		<img src="/data/imgs/telescope_top.png" class="telescope_top">
		<img src="/data/imgs/telescope_bottom.png" class="telescope_bottom">
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<img src="/data/imgs/star_orbit.png" class="star_orbit">
			<img src="/data/imgs/light_star.png" class="light_star">
			<header>
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format("声明"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">
				<div class="sign">
					<img class="star" src="/data/imgs/star.png" alt="图片加载失败">
					<p>当真心想要做成一件事的时候，人总是孤独的。</p>
				</div>
			</header>
			<main>
				<pre style="text-align: center;">本站坚持开源精神，你的密码会被MD5加密后储存入数据库，本站数据库也属于开源资料！<br>温馨提示：MD5加密是不可逆的，没有人会知道你的密码，包括管理员和任何开发者。</pre>
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


<!-- MD5 -->
<script src="/js/md5.js"></script>
<script src="/js/xhr.js"></script>
<script src="/js/get_cookie.js"></script>
<script src="/js/lock.js"></script>

<script>
	// 
	// 
	// 注册提交
	// 
	// 
	const register = () => {
		// 注册信息提取
		const uname = document.querySelector('#reg_uname').value
		const psw = md5(document.querySelector('#reg_psw').value)

		// 有信息未填
		if (uname == "" || psw == "") {
			alert("请填写完整注册信息")
			return
		}

		// 请求锁，防止过量请求
		if (lock()) {
			return
		}

		// xhr请求
		var data = {
			"cmd": "register",
			"uname": uname,
			"psw": psw
		}
		xhr("/servers/register.php", data).then((result) => {
			alert(result);
		})
	}





	// 
	// 
	// 登录提交
	// 
	// 

	const login = () => {
		// 获取登录信息
		const uname = document.querySelector('#login_uname').value
		const psw = md5(document.querySelector('#login_psw').value)

		// 信息未填完全
		if (uname == "" || psw == "d41d8cd98f00b204e9800998ecf8427e") {
			alert("有信息未填")
			return
		}

		const data = {
			"cmd": "login",
			"uname": uname,
			"psw": psw
		}
		xhr("/servers/register.php", data).then((result) => {
			if (result == "登录成功") {				
				// 跳转到管理页面
				window.location.href = `/user_admin.php`
			} else {
				alert(result);
			}
		})
	}
</script>












	<?php require_once dirname(__FILE__)."/footer.php" ?>




























