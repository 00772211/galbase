<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>账号管理</title>
	<!-- css -->
	<link rel="stylesheet" href="/css/header.css">
	<link rel="stylesheet" href="/css/style.css">

	<style>
		/* 清除内外边距 */
		* {
			margin: 0;
			padding: 0;
		}
		
		.else main {
			position: relative;
		}

		input {
			width: 30%;
		}

		.avatar_big {
			position: absolute;
			height: 100%;
			right: 0;
			border: white 6px solid;
			border-radius: 15%;
			transition: border 0.75s, border-radius 0.75s;
			box-sizing: border-box;
			cursor: pointer;
		}

		.avatar_big:hover {
			border: #EEEEEE 4px solid;
			border-radius: 0;
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
	<?php require_once dirname(__FILE__)."/navigation_bar.php"; ?>


	<div class="board main_board else">
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<header>
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format("心路历程"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">
			</header>
			<main>
				<img class="avatar_big" src="<?php echo get_avatar($uid)['medium']; ?>" onclick="fullscreen_avatar(this)" loading="lazy" alt="头像加载失败"><br><br>

				<?php
					// 获取作品集数据
					$uid_last_char = substr($uid, -1);
					$data = mysqli_query($link, "SELECT * FROM users_data_{$uid_last_char} WHERE uid={$uid} LIMIT 1");
					$data = $data->fetch_assoc();

					// 在线时间换算成h
					$data['online_time'] = $data['online_time'] / 60;
				?>
				展示的个性签名：<input type="text" id="sign" placeholder="Love Forever" value="<?php echo $data['sign']; ?>"><br><br>
				此生挚爱的图片：<input type="text" id="sign_img" placeholder="格式：tid|aid，例如:1|1" value="<?php echo $data['sign_img']; ?>"><br><br>
				此生挚爱的故事：<input type="text" id="best_love_story" placeholder="友情是宽容，友情是仁慈" value="<?php echo $data['best_love_story']; ?>"><br><br>
				正在推进的故事：<input type="text" id="playing_story" placeholder="友情是不张扬，不自夸。" value="<?php echo $data['playing_story']; ?>"><br><br>
				强烈推荐的故事：<input type="text" id="recommend_stories" placeholder="格式：A|B|C|D|E" value="<?php echo $data['recommend_stories']; ?>"><br><br>
				<button onclick="user_data_update()">全部信息提交</button>
				<button onclick="update_uname()">更改用户名</button>
				<button onclick="const new_psw = prompt('请输入你需要更改的新密码'); user_admin('replace_psw', new_psw)">更改密码</button>
				<button class="button2" onclick="user_admin('replace_avatar')">更换头像</button>
			</main>
		</div>
	</div>

	<h4 style="visibility: hidden;">x</h4>

	<div class="board main_board">
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<header>
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format("设置"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">
			</header>
			<main>
				待开发中…未来见…
			</main>
		</div>
	</div>

	<h4 style="visibility: hidden;">x</h4>

	<div class="board main_board">
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<header>
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format("账号绑定"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">
			</header>
			<main style="padding: 10px;">
				绑定的邮箱：<input type="text" id="email" placeholder="Just Living Database" value="<?php echo get_value("users_email", "email", "uid='$uid'"); ?>">
				<button onclick="update_email()">提交邮箱</button><br>
				<span>本绑定的作用：当论坛站点发生迁移或变更域名时，又或者最坏的情况站点关门，都会通知至此邮箱。</span><br>
				<span>注：请不要使用QQ/网易等国内邮箱会被拦截，最好绑定谷歌/微软等国外邮箱，有事通知时均以谷歌邮箱进行发送。</span>
			</main>
		</div>
	</div>



<script src="/js/md5.js"></script>
<script src="/js/xhr.js"></script>
<script src="/js/fullscreen.js"></script>
<script src="/js/float_window.js"></script>
<script src="/js/user_admin.js"></script>

<script>
	// 更新邮箱
	const update_email = () => {
		const email = document.getElementById('email').value

		if (!email) {
			alert('没填啊你！')
			return
		}

		server = email.split("@")[1]
		console.log(server);

		if (server == "qq.com" || server == "163.com") {
			float_window.create()
			float_window.title("绑定无效")
			float_window.content(`检测到邮箱服务器为国内，你就算绑定了我也发不到你的邮箱啊，白绑，建议绑定微软、谷歌、雅虎、Proton等国外邮箱`)
			float_window.open()
			return
		}

		var data = {
			"cmd": "update_email",
			"email": email
		}
		xhr("/servers/user_admin.php", data).then((result) => {
			float_window.create()
			float_window.title("绑定成功")
			float_window.content(`若未来你不能访问，记得检查邮箱哦！`)
			float_window.open()
		})

	}


	// 故事集数据更新
	const user_data_update = () => {
		const sign = document.getElementById('sign').value
		const sign_img = document.getElementById('sign_img').value
		const best_love_story = document.getElementById('best_love_story').value
		const playing_story = document.getElementById('playing_story').value
		const recommend_stories = document.getElementById('recommend_stories').value
		const stories_data = `${sign}||${sign_img}||${best_love_story}||${playing_story}||${recommend_stories}`

		// 通过xhr提交数据
		var data = new FormData()
		data.append("cmd", "user_data_update")
		data.append("info", stories_data)

		// 发送xhr
		const xhr = new XMLHttpRequest()
		xhr.open("POST", "/server.php", true)
		xhr.send(data)

		// xhr处理
		xhr.onreadystatechange = () => {
			if(xhr.readyState == 4 && xhr.status == 200){
				try {
					alert('数据已更新')
					window.location.href = '/user_admin.php'
				} catch (error) {
					console.error('xhr请求失败，失败code：' + error)
				}
			}
		}

		
		// xhr请求超时
		xhr.timeout = 5000	// ms
		xhr.ontimeout = () => alert("请求超时")
	}





	// 账号操作
	const user_admin = (cmd, value='') => {
		switch (cmd) {
			// 更换头像
			case 'replace_avatar':
				window.location.href = '/upload_avatar.php'
				break





			// 更改密码 value = 新密码
			case 'replace_psw':
				if (!value) {
					alert('请输入需要更改的密码')
				} else {
					const new_psw = md5(value)

					// 通过xhr更新密码
					var data = new FormData()
					data.append("cmd", "replace_psw")
					data.append("psw", new_psw)

					// 发送xhr
					var xhr = new XMLHttpRequest()
					xhr.open("POST", "/server.php", true)
					xhr.send(data)

					// xhr处理
					xhr.onreadystatechange = () => {
						if(xhr.readyState == 4 && xhr.status == 200){
							try {
								alert('密码已更改')
							} catch (error) {
								console.error('xhr请求失败，失败code：' + error)
							}
						}
					}

					// xhr请求超时
					xhr.timeout = 5000	// ms
					xhr.ontimeout = () => alert("请求超时")
				}
				break



			// 退出登录
			case 'quet':
				// cookie信息整理
				const date = new Date();
				date.setDate(date.getDate() - 31); // 设置有效期为30天
				const expires = 'expires=' + date.toUTCString();
										
				// 设置cookie
				document.cookie = `sessionID=0; ` + expires + '; path=/'

				// 重新进入页面
				window.location.href = '/register.php'
				break
		}
	}
</script>




















<!-- 引入底部模块 -->
<?php require_once dirname(__FILE__).'/footer.php'; ?>







<!-- 关闭数据库 -->
<?php mysqli_close($link); ?>