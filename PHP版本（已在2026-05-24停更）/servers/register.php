<?php
	// 连接数据库
	require_once dirname(dirname(__FILE__)).'/conn.php';

	// 连接外部函数
	require_once dirname(dirname(__FILE__)).'/functions.php';

	if(!$_POST['cmd']) {
		exit("cmd参数不存在");
	}

	switch ($_POST['cmd']) {

		// 
		// 
		// 注册
		// 
		// 
		case 'register':
			// 获取注册信息
			$uname = $_POST['uname'];
			$psw = $_POST['psw'];

			// 提取最新uid
			$uid = get_value("sys_auto_increment_value", "value", "variable='uid'");

			// 取uid最后一位字符分表
			$uid_last_char = substr($uid, -1);

			// 分表储存注册信息uid uname psw
			mysqli_query($link, "INSERT INTO users_info_$uid_last_char (uid, uname, psw) VALUE ($uid, '$uname', '$psw')");

			// 获取当前时间
			$time = get_time();

			// 储存用户默认数据
			mysqli_query($link, "INSERT INTO users_data_{$uid_last_char} (uid, online_time, identity, credit, academic_year, schoolship, judment_count, canned_count, register_time, last_login_time) VALUES ({$uid}, 0, '结姬学园学生', 0, '中学一年生', 0, 0, 0, '{$time}', '{$time}')");

			// 自增表uid+1
			mysqli_query($link, "UPDATE sys_auto_increment_value SET value = value + 1 WHERE variable='uid'; ");
			
			// 日志记录
			log_add($uid, "\$user 于今日正式入学结姬学园！");

			log_add($uid, "欢迎\$user 于今日正式入学结姬学园！还请您收藏<a href='https://home.galbase.top' target='_blank'>https://home.galbase.top</a>以防站点更换域名！或者收藏本站永久域名：<a href='https://0d000721.cc' target='_blank'>https://0d000721.cc</a> <a href='https://ciallo.ca' target='_blank'>https://ciallo.ca</a>", 0, $uid);

			// 数据库记录注册信息
			echo '注册成功，请手动进行登录';
			break;



		// 
		// 登录
		// 
		case 'login':
			// 获取信息
			$uname = $_POST['uname'];
			$psw = $_POST['psw'];

			// 查询用户名
			for ($n = 0; $n < 10; $n++) {
				$uid = mysqli_query($link, "SELECT uid FROM users_info_{$n} WHERE uname='$uname' and psw='$psw' LIMIT 1; ");
				if (mysqli_num_rows($uid)) {
					$uid = $uid->fetch_assoc()['uid'];

					// 开启会话
					session_start();

					// 生成新的sessionID
					session_regenerate_id(true);

					$sessionID = session_id();

					// 设置前端cookie
					setcookie('sessionID', $sessionID, time() + 2592000, '/');

					// 记录新的sessionID对应的uid 
					mysqli_query($link, "INSERT INTO users_sessions(sessionID, uid) VALUES ('{$sessionID}', {$uid})");		

					// 登录成功
					echo "登录成功";
					exit;
				}
			}

			echo '登录失败，请仔细确认 用户名 / 密码 是否存在错误。'; 


			// // 登录失败
			// if (!isset($uid)) {

			// 	// 判断是否被封禁
			// 	for ($n = 0; $n < 10; $n++) {
			// 		$uid = mysqli_query($link, "SELECT uid FROM users_info_{$n} WHERE uname='$uname' AND psw='' LIMIT 1; ")->fetch_assoc()['uid'];
			// 		if ($uid) {
			// 			echo '账号被封禁，如有疑问请注册新号前往「澄空心情驿站 / 心跳町温泉」版块表明自己的疑问，会根据情况进行解封。';
			// 			log_add($uid, "被封用户 \$user 尝试登录，请管理员注意。");
			// 			return;
			// 		}
			// 	}

			// 	echo '登录失败，请仔细确认 用户名 / 密码 是否存在错误。';
			// }
			break;


		// 
		// 
		// 重置密码
		// 
		// 
		case 'reset_psw':
			$buid = $_POST['buid'];
			$psw = $_POST['psw'];

			// 验证buid
			$bili_tags = file_get_contents("https://api.bilibili.com/x/space/acc/tags?mid=$buid");

			// BUID没通过验证
			if(!strstr($bili_tags, 'reset')){
				exit("填写的B站账号个人标签不存在\"reset\"");
			}

			// 根据buid重置密码
			for ($n = 0; $n < 10; $n++) {
				$uid = mysqli_query($link, "SELECT uid FROM users_info_{$n} WHERE buid=$buid LIMIT 1; ")->fetch_assoc()['uid'];
				if ($uid) {
					mysqli_query($link, "UPDATE `users_info_{$n}` SET `psw`='$psw' WHERE buid=$buid LIMIT 1; ");
					exit("密码重置成功！");
					break;
				}
			}
			break;
	}
