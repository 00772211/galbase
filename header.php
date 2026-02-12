<?php
	// 必要的php
	require_once dirname(__FILE__).'/config.php';
	require_once dirname(__FILE__).'/conn.php';
	require_once dirname(__FILE__).'/functions.php';
?>
<!-- 随机背景图片 -->
<head>
	<style>
		<?php
			// 每5分钟一个区间固定种子
			$seed = floor(time() / (5 * 60));
			mt_srand($seed);

			// 获取数组的键值，即壁纸父ID
			$bg_ID = array_rand($bgs, 6);
			$all = [];

			// 获取壁纸子ID
			foreach ($bg_ID as $f_ID) {
				$info = explode("|", $bgs[$f_ID]);
				$chunk = $info[0];
				$s_ID = $info[1];
				$bg_num = rand(1, $s_ID);

				// 主机的背景地址和实际背景地址不一样
				if ($_SERVER['SERVER_ADDR'] == "127.0.0.1") {
					array_push($all, "http://127.0.0.1:8000/data/bgs/$chunk/$f_ID/$f_ID ($bg_num).avif");
				} else {
					array_push($all, "https://bg{$chunk}.galbase.top/$f_ID/$f_ID ($bg_num).avif");
				}
			}

			// 用于播放器和video标签的封面用的变量
			$bg_path = $all[0];

			// 结束固定种子
			mt_srand();
		?>
        .cb-slideshow li:nth-child(1) span { background-image: url('<?php echo $all[0]; ?>'); }
        .cb-slideshow li:nth-child(2) span { background-image: url('<?php echo $all[1]; ?>'); }
        .cb-slideshow li:nth-child(3) span { background-image: url('<?php echo $all[2]; ?>'); }
        .cb-slideshow li:nth-child(4) span { background-image: url('<?php echo $all[3]; ?>'); }
        .cb-slideshow li:nth-child(5) span { background-image: url('<?php echo $all[4]; ?>'); }
        .cb-slideshow li:nth-child(6) span { background-image: url('<?php echo $all[5]; ?>'); }

        @media (min-width: 767px) {
            .large {
                background: url('<?php echo $all[0]; ?>') no-repeat;
                background-size: cover;
                height: 100vh;
            }
        }
	</style>

	<?php
		// 新年灯笼
		if ($config['new_yeah_lantern']) {
			echo "<link rel='stylesheet' href='/css/lantern.css'>";
		}

		// 下雪
		if ($config['snow']) {
			echo "<link rel='stylesheet' href='/css/snow.css'>";
		}
	?>
</head>


<?php
	// 维护开启
	if ($config['maintenance'] == TRUE) {

		// 不为管理员 && 开启禁止访问
		if (administrator(get_uid()) == FALSE && $config['disable_visit'] == TRUE) {
			exit("<h1>{$config['maintenance_msg']}</h1>");

		// 可以访问但是警告
		} else {
			echo '<script src="/js/float_window.js"></script>';
		}
	}
?>
<script src="/js/get_cookie.js"></script>
<script src="/js/lock.js"></script>
<script src="/js/finger.js"></script>
<script>
	// 
	// 获取浏览器指纹
	// 
	const fpPromise = import('/js/finger.js').then(FingerprintJS => FingerprintJS.load())
	fpPromise.then(fp => fp.get()).then(result => {
		const finger = result.visitorId
		const cookie_finger = get_cookie("finger")

		// 判断cookie中指纹是否存在
		if (!cookie_finger) {
			set_cookie('finger', finger);

		// 指纹存在，判断指纹是否变更
		} else if (cookie_finger != finger) {
			set_cookie('finger', finger);
		}
    })


	
	// 
	// 公告
	// 
	if (<?PHP if ($config['maintenance'] == TRUE) { echo 0; } else { echo 1; } ?> == 0) {
		float_window.create()
		float_window.title("公告")
		float_window.content(`<?PHP echo $config['maintenance_msg']; ?>`)
		float_window.open()
		float_window.lock(3)
	}
</script>

<?php
	// 前端存在sessionID
	if (isset($_COOKIE['sessionID'])) {

		// 获取用户uid
		$uid = get_uid();

		// uid不存在
		if (!$uid) {
			// 删除cookie
			setcookie('sessionID', '', time() - 2592000000, '/');
			exit("<script>alert('你当前登录已过期，请刷新页面重新登录！')</script>");
		}
	}

	if (!isset($uid)) {
		$uid = FALSE;
	}
?>


	<!-- 导航栏 -->
	<div class="header">
		<img class="logo" src="/data/imgs/logo.png" title="返回主页" onclick="window.location.href = '/index.php'" loading="lazy" alt="图片加载失败">

		<div class="search">
			<input type="text" placeholder="整站全文搜索"><span onclick="search()"></span>
		</div>

		<div class="user_dropdown">
			<?php
				// 已登录


				if ($uid) {
					$uname = get_uname($uid);
					echo "<a class='uname' title='$uname'>$uname</a>";

				// 未登录
				} else {
					echo "<a class='uname' href='/register.php'>注册 / 登录</a>";
				}
			?>
			<nav class="content">
				<a href="/user_admin.php">账号管理</a>
				<a href="/space/<?php echo $uid; ?>">个人空间</a>
				<a onclick="quet()">退出登录</a>
			</nav>
		</div>

		<img class="avatar" src="<?php echo get_avatar($uid)['small']; ?>" loading="lazy" alt="图片加载失败">

		<a href="/msg.php" target="_blank"><span class="msg"><img src="
			<?php
				// 未登录 或 登录过期
				if (!$uid) {
					echo "/data/imgs/msg_none.png";

				// 已登录
				} else {
					// 判断用户是否有未读通知
					$result = mysqli_query($link, sprintf("SELECT IF(count(uid) > 0, 1, 0) from logs_%s WHERE uid=$uid and `read`=0", date('Y')));
					$result = $result->fetch_assoc()['IF(count(uid) > 0, 1, 0)'];

					// 存在返回1，不存在返回0
					if ($result == 1) {
						echo "/data/imgs/msg.png";
					} else {
						echo "/data/imgs/msg_none.png";
					}
				}
			?>
		" loading="lazy" alt="图片加载失败"></span></a>

		<span class="text broadcast">本站永久域名home.galbase.top 收藏不迷路</span>
	</div>

	<?php
		// 新年灯笼
		if ($config['new_yeah_lantern']) {
			require_once dirname(__FILE__).'/js/lantern.php';
		}
	?>


<script>
	// 
	// 搜索栏内容回车触发
	// 
	document.querySelector('.search input').addEventListener('keypress', function(e) {
		if (e.key === 'Enter') {
			search()
	}})


	// 
	// 未登录清理header下拉菜单
	// 
	if (document.querySelector(".user_dropdown .uname").href) {
		document.querySelector(".user_dropdown nav").style.display = "none"
	}

	// 
	// 搜索栏
	// 
	function search() {
		// 请求锁，防止过量请求
		if (lock()) {
			return
		}

		// 获取搜索内容
		var content = document.querySelector(".search input").value

		// 搜索内容不存在
		if (!content) {
			alert("未输入搜索内容")

		// 搜索内容存在跳转
		} else {
			window.open(`/search.php?type=normal&content=${content}`, "_blank")
		}
	}

	// 
	// 
	// 退出登录
	// 
	// 
	const quet = () => {
		// cookie信息整理
		const date = new Date();
		date.setDate(date.getDate() - 31); // 设置有效期为30天
		const expires = 'expires=' + date.toUTCString();
								
		// 设置cookie
		document.cookie = `sessionID=0; ` + expires + '; path=/'

		// 重新进入页面
		window.location.href = '/index.php'
	}
</script>



