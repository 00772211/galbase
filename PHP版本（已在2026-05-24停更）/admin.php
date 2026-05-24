<meta name='description' content='GALBase论坛 - Galgame资源站点'>
<?php
	require_once dirname(__FILE__).'/conn.php';
	require_once dirname(__FILE__).'/functions.php';

	// 未登录禁止访问
	$uid = get_uid();
	if (!$uid) {
		log_add(0, "有未知用户正在尝试访问管理面板，请管理员注意该消息。");
		exit("你未登录。");
	}

	// 非管理员访问
	if (administrator($uid) == 0) {
		log_add($uid, "\$user 正在尝试访问管理面板，请管理员注意该用户。");
		exit("你不是管理员访问这里干嘛？");
	}

	// 指令处理部分
	if (isset($_GET['cmd'])) {
		switch ($_GET['cmd']) {

			// 封禁用户
			case 'ban_user':
				$uid = $_GET['value'];
				$uid_last_char = substr($uid, -1);
				$uname = get_uname($uid);

				// 将用户密码抹除，同时删除sessionID
				mysqli_query($link, "DELETE FROM `users_sessions` WHERE uid=$uid");
				mysqli_query($link, "UPDATE users_info_$uid_last_char SET psw='' WHERE uid=$uid; ");

				log_add($uid, "\$user 已被封禁。");
				echo "风纪执行完成";
				break;


			// 警告用户获取资源过多
			case 'warn_user':
				$uid = $_GET['value'];
				log_add(0, "你近期请求的数量过多，已被判为机器账号，请前往<a href='/forum/2-1/0' target='_blank'>「澄空心情驿站 / 心跳町温泉」</a>版块说明过量请求原因，如无视此消息继续过量请求将进行封号处理！", 0, $uid);
				echo "警告完成";
				break;


			// 检测所有用户的uid
			case 'check_all_users_buid':

				// 获取最新uid
				$newest_uid = get_value("sys_auto_increment_value", "value", "variable='uid'") - 1;

				echo "<table border='1' cellpadding='12px' cellspacing='0px' width='60%'>";
				echo "<thead><tr><th>UID</th> <th>uname</th> <th>psw</th> <th>reg</th> <th>last</th></tr></thead>";
				echo "<tbody>";

				// 循环找到每个uid对应的UID
				for ($uid = $newest_uid; $uid >= 1; $uid--) {
					// 获取uid最后一位做分表
					$uid_last_char = substr($uid, -1);

					// 获取用户所有数据
					$data = mysqli_query($link, "SELECT * FROM users_info_$uid_last_char WHERE uid=$uid LIMIT 1")->fetch_assoc();
					// uid
					// uname
					// psw
					$data2 = mysqli_query($link, "SELECT * FROM users_data_$uid_last_char WHERE uid=$uid LIMIT 1")->fetch_assoc();

					echo "<tr><td>{$data['uid']}</td> <td>{$data['uname']}</td> <td>{$data['psw']}</td> <td>{$data2['register_time']}</td> <td>{$data2['last_login_time']}</td></tr>";
				}
				echo "</tbody></table>";
				break;

			// 解封用户
			case 'unban_user':
				$unban_uid = $_GET['value'];
				$uid_last_char = substr($unban_uid, -1);

				// 123456的MD5值
				$psw = "e10adc3949ba59abbe56e057f20f883e";

				// 判定是否为空密码
				$result = mysql_exist("users_info_$uid_last_char", "psw", "");

				// 已被封禁
				if ($result == 1) {
					mysqli_query($link, "UPDATE `users_info_{$uid_last_char}` SET psw='$psw' WHERE uid=$unban_uid LIMIT 1; ");
					$uname = get_uname($unban_uid);
					log_add($uid, "\$user 解封了用户 <a href='/space/$unban_uid' target='_blank'>$uname($unban_uid)</a>");
					echo "用户解封成功。";
				} else {
					echo "用户未被封禁";
				}
				break;

			// 查找日志
			case 'search_logs':
				$content = $_GET['value'];
				$year = get_time("Y");
				$data = mysqli_query($link, "SELECT * FROM `logs_$year` WHERE content LIKE '%$content%' ORDER by date DESC");

				echo "<table border='1' cellpadding='12px' cellspacing='0px' width='60%'>";
				echo "<thead><tr><th>UID发给谁</th> <th>记录时间</th> <th>记录内容</th> <th>已读状态</th></tr></thead>";
				echo "<tbody>";

				// 循环每条信息输出
				while ($row = $data->fetch_assoc()) {
					$uid = $row['uid'];
					$date = $row['date'];
					$content = $row['content'];
					$read = $row['read'];
					echo "<tr><td>$uid</td> <td>$date</td> <td>$content</td> <td>$read</td></td></tr>";
				}
				echo "</tbody></table>";
				break;

			// 发送通知给所有用户
			case 'send_msg_to_all_users':
				$content = $_GET['value'];
				$max_uid = get_value("sys_auto_increment_value", "value", "variable='uid'");
				for ($uid = 1; $uid < $max_uid; $uid++) {
					log_add(0, "$content", 0, $uid);
				}
				echo "发送完成";
				break;


			case 'delete_cache':
				if (!strstr($_GET['value'], "|")) {
					exit("指令不对。");
				}
				$content = explode("|", $_GET['value']);
				$min = $content[0];
				$max = $content[1];

				$result = mysqli_query($link, "SELECT tid FROM `topics_index` WHERE tid > $min AND tid < $max;");

				while ($row = $result->fetch_assoc()) {
					$tid = $row['tid'];
					$chunk = chunk($tid);
			
					if (file_exists("./data/forums/$chunk/$tid/cache")) {
						delete_folder("./data/forums/$chunk/$tid/cache");
						echo "$tid | cache存在，已清除" . "<br>";
					} else {
						echo "$tid | 文件夹不存在" . "<br>";
					}
				}
				break;


			// 
			// 加密动画URL
			// 
			case 'encode_anime':
				$tid = $_GET['value'];
				$chunk = chunk($tid, "anime");
		
				// 获取文件夹下的所有图像文件
				$videos = scandir(dirname(__FILE__)."/data/animes/$chunk/$tid");				

				// 删去0键和1键，0为"."本级目录，1为".."上级目录，和缩略图缓存db
				unset($videos[0]);
				unset($videos[1]);
				unset($videos[array_search('Thumbs.db', $videos)]);

				// 利用正则表达式过滤掉非mp4文件
				$videos = array_filter($videos, function($value) {
					return preg_match('/\.mp4$/', $value);
				});

				// 去除键名
				$videos = array_values($videos);

				// 获取数组长度
				$eps = count($videos);

				// 循环每集
				for ($i=1; $i <= $eps; $i++) { 

					// 加密方式 = tid|ep
					$encode = md5("$tid|$i");

					// 尝试更改文件名，若有存在加密路径则无视，需要用@来抑制warning警告
					@rename(dirname(__FILE__)."/data/animes/$chunk/$tid/$i.mp4", dirname(__FILE__)."/data/animes/$chunk/$tid/{$i}_$encode.mp4");
				}

				// 获取帖子所以mp4
				if (file_exists(dirname(__FILE__)."/data/animes/$chunk/$tid")) {

					// 获取文件夹下的所有图像文件
					$imgs = scandir(dirname(__FILE__)."/data/animes/$chunk/$tid");				

					// 删去0键和1键，0为"."本级目录，1为".."上级目录，和缩略图缓存db
					unset($imgs[0]);
					unset($imgs[1]);
					unset($imgs[array_search('Thumbs.db', $imgs)]);

					// 利用正则表达式过滤掉非mp4文件
					$imgs = array_filter($imgs, function($value) {
						return preg_match('/\.mp4$/', $value);
					});

					// 获取数组长度，及集数
					$eps = count($imgs);
				}

				// 获取该贴的tag
				$tags = format_tags_to_str($tid);
				$tags = explode("|", $tags);

				// 循环每个tag
				for ($i = 0; $i < count($tags); $i++) {
					$tag = $tags[$i];

					// 包含集数标签，进行更换
					if (strstr($tag, "ep")) {
						$tags[$i] = "ep" . $eps;
						break;
					}
				}

				// 合并数组为字符串
				$tags = implode("|", $tags);

				// 格式化为tagID
				$tags_ID = format_tags_to_id($tags, "str");

				// 更新帖子
				$fid = get_fid($tid);
				$tid_last_char = substr($tid, -1);
				mysqli_query($link, "UPDATE `topics_{$fid}_$tid_last_char` SET tags='$tags_ID' WHERE tid=$tid LIMIT 1");

				// 推送帖子
				push($tid);

				echo "加密成功";
				break;



			// 
			// 刷新月慕最新推送
			// 
			case 'reget_ym':
				mysqli_query($link, "TRUNCATE `galbase`.`ymgal`");

				$html = file_get_contents("https://www.ymgal.games/search?type=article&keyword=&sort=time&category=%E8%B5%84%E8%AE%AF&page=1");

				// 获取大致范围
				$content = explode("article-result-list", $html)[1];
				$content = explode("pager-box", $content)[0];

				// 细分每个标题
				$topics = explode("ui item", $content);

				// 去掉第一个
				array_shift($topics);

				// 清空数据表
				mysqli_query($link, "TRUNCATE `ymgal`; ");

				foreach ($topics as $topic) {
					// 获取标题
					$title = explode("header", $topic)[1];
					$title = explode("</a>", $title)[0];
					$title = explode(">", $title)[1];

					// 获取直链
					$src = explode("header", $topic)[1];
					$src = explode(">", $src)[0];
					$src = explode("href=", $src)[1];
					$src = str_replace('"', "", $src);
					$src = "https://www.ymgal.games" . $src;

					// 获取封面
					$img_src = explode("image", $topic)[1];
					$img_src = explode("alt", $img_src)[0];
					$img_src = explode("src=", $img_src)[1];
					$img_src = str_replace('"', "", $img_src);

					$img = file_get_contents($img_src);
					$file_name = md5($title);
					file_put_contents("./data/html/ym/$file_name.webp", $img);

					// 添加内容
					mysqli_query($link, "INSERT INTO `ymgal` (`title`, `preview`, `src`) VALUES (\"$title\", '/data/html/ym/$file_name.webp', '$src'); ");
				}

				break;



			// 
			// 爬取指定数量touchgal帖子
			// 
			case 'touchgal':
				$num = $_GET['value'];
				require_once __DIR__.'/functions_else.php';

				for ($i=0; $i < $num; $i++) { 
					touchgal_topic_get();
				}
				echo "完成";
				break;



			// 
			// 删除指定ID的touchgal预帖子
			// 
			case 'touchgal_remove':
				$ID = $_GET['value'];
				mysqli_query($link, "DELETE FROM `touchgal` WHERE id='$ID'; ");
				echo "完成";
				break;


				
			default:
				exit("<script>alert('输入的cmd无效')</script>");
				break;
		}
		exit;
	}



	// POST请求部分
	if (isset($_POST['cmd'])) {
		switch ($_POST['cmd']) {
			// 上传LOGO
			case 'upload_logo':
				$file_name = $_FILES['file']['name'];
				$file_cache = $_FILES['file']['tmp_name'];
				move_uploaded_file($file_cache, "./data/imgs/Developer/$file_name");
				break;

			// 上传OP
			case 'upload_op':
				$tid = $_FILES['file']['name'];
				$tid = explode(".", $tid)[0];
				$file_cache = $_FILES['file']['tmp_name'];
				$chunk = chunk($tid);

				// 获取vid
				$vid = mysqli_query($link, "SELECT value FROM sys_auto_increment_value WHERE variable='vid' LIMIT 1");
				$vid = $vid->fetch_assoc()['value'];

				// 写入视频文件
				move_uploaded_file($file_cache, "./data/forums/$chunk/$tid/$vid.mp4");

				// vid + 1
				mysqli_query($link, "UPDATE `sys_auto_increment_value` SET value = value + 1 WHERE variable='vid' LIMIT 1;");
				break;
		}
	}
?>
























<table border='1' cellpadding='12px' cellspacing='0px' width='60%'>
	<thead><tr><th>序号</th> <th>功能</th> <th>执行</th> <th>参数</th></tr></thead>
	
	<tbody>
		<tr><td>1</td> <td>查看所有用户的BUID、uname、qq</td> <td><button onclick="cmd('check_all_users_buid')">执行</button></td> <td>无</td></tr>
		<tr><td>2</td> <td>封禁用户</td> <td><button onclick="cmd('ban_user', true)">执行</button></td> <td><input type="text" id="ban_user_value" placeholder="UID"></td></tr>
		<tr><td>3</td> <td>解封用户</td> <td><button onclick="cmd('unban_user', true)">执行</button></td> <td><input type="text" id="unban_user_value" placeholder="UID"></td></tr>
		<tr><td>4</td> <td>上传LOGO</td> <td><input type="file" id="upload_logo"></td> <td>高不超过160px</tr>
		<tr><td>5</td> <td>日志查询</td> <td><button onclick="cmd('search_logs', true)">执行</button></td> <td><input type="text" id="search_logs_value" placeholder="查询关键字"></tr>
		<tr><td>6</td> <td>给所有用户发送通知</td> <td><button onclick="cmd('send_msg_to_all_users', true)">执行</button></td> <td><input type="text" id="send_msg_to_all_users_value" placeholder="内容"></tr>
		<tr><td>7</td> <td>警告指定用户获取资源过多</td> <td><button onclick="cmd('warn_user', true)">执行</button></td> <td><input type="text" id="warn_user_value" placeholder="UID"></tr>
		<tr><td>8</td> <td>删除视频cache缓存</td> <td><button onclick="cmd('delete_cache', true)">执行</button></td> <td><input type="text" id="delete_cache_value" placeholder="min tid|max tid"></tr>
		<tr><td>9</td> <td>上传OP</td> <td><input type="file" id="upload_op"></td> <td>视频文件名为tid</tr>
		<tr><td>10</td> <td>加密动画URL</td> <td><button onclick="cmd('encode_anime', true)">执行</button></td> <td><input type="text" id="encode_anime_value" placeholder="tid"></tr>
		<tr><td>11</td> <td>重新获取月慕今日推送</td> <td><button onclick="cmd('reget_ym')">执行</button></td> <td>用于修复全站瘫痪，具体表现为全站宕机。</tr>
		<tr><td>12</td> <td>爬取指定数量touchgal帖子</td> <td><button onclick="cmd('touchgal', true)">执行</button></td> <td><input type="text" id="touchgal_value" placeholder="数量"></tr>
		<tr><td>13</td> <td>删除指定ID的touchgal预帖子</td> <td><button onclick="cmd('touchgal_remove', true)">执行</button></td> <td><input type="text" id="touchgal_remove_value" placeholder="ID"></tr>
	</tbody>
</table>








<script src="/js/xhr.js"></script>
<script>
	const cmd = (cmd, decision="") => {
		if (decision) {
			var value = document.querySelector(`#${cmd}_value`).value
		}

		if (value) {
			window.open(`/admin.php?cmd=${cmd}&value=${value}`, "_self")
		} else {
			window.open(`/admin.php?cmd=${cmd}`, "_self")
		}
	}

	document.querySelector("#upload_logo").onchange = function () {

		// 获取文件
		var file = document.querySelector("#upload_logo").files[0]
		var data = {
			"cmd": "upload_logo",
			"file": file
		}
		xhr("/admin.php", data).then((result) => {
			alert("上传完成")
		})
	}

	document.querySelector("#upload_op").onchange = function () {

		// 获取文件
		var file = document.querySelector("#upload_op").files[0]
		var data = {
			"cmd": "upload_op",
			"file": file
		}
		xhr("/admin.php", data).then((result) => {
			console.log(result);
			
			alert("上传完成")
		})
	}
</script>
































<?php

	exit("页面保护，请不要在admin内直接执行");
	// 
	// 
	// 创建topics_1-1_0
	// 
	// 
	for ($n = 0; $n < 10; $n++) {
		mysqli_query($link, sprintf('CREATE TABLE `topics_2-4_%s` (tid INT(255) NOT NULL, title TINYTEXT NULL, content TEXT NULL, uid INT(255) NOT NULL, date TINYTEXT NOT NULL, tags TINYTEXT NULL, preview TINYTEXT NULL, view_count INT(255) NULL, reply_count INT(255) NULL, UNIQUE(tid)) ENGINE = MyISAM', $n));
	}





	if($_POST['cmd']){
		// 获取cmd
		$cmd = $_POST['cmd'];

		// 如果存在有参请求，获取参数
		if ($_POST['request']) {
			$request = $_POST['request'];
		}
		
		// 分割cmd
		switch ($cmd) {




			// 重置日志表
			case 'reset_logs':
				mysqli_query($link, "DROP table logs_2023");
				mysqli_query($link, "CREATE TABLE logs_2023 (uid INT(255) NOT NULL, date TINYTEXT NOT NULL, content TEXT NOT NULL, `read` TINYINT NOT NULL) ENGINE = MyISAM;");
				echo '重置完成';
				break;






			// 重置回复审核表
			case 'reset_check_replies':
				mysqli_query($link, 'drop table check_replies');
				mysqli_query($link, "CREATE TABLE check_replies (tid INT(255) NOT NULL, uid INT(255) NOT NULL, content TEXT NOT NULL) engine = MyISAM;");
				echo '重置完成';
				break;





			// 清理用户sessionID
			case 'reset_users_sessions':
				// 清除所有session记录
				mysqli_query($link, 'drop table users_sessions');

				// 创建users_sessions
				mysqli_query($link, 'create table users_sessions (sessionID text not null, uid int(255) not null) engine = MyISAM');
				echo '重置完成';
				break;
				



			// 帖子审核表
			case 'reset_check_topics':
				// 删除check_topics表
				mysqli_query($link, 'drop table check_topics');

				// 创建check_topics表
				mysqli_query($link, 'CREATE TABLE check_topics ( title TINYTEXT NULL, content TEXT NULL, uid INT(255) NOT NULL, date TINYTEXT NOT NULL, tags TINYTEXT NULL, fid TINYTEXT NOT NULL, preview TINYTEXT NULL, cid TINYTEXT NOT NULL) ENGINE = MyISAM;');
				echo '重置完成';
				break;




			// 头像审核表
			case 'reset_check_avatars':
				// 删除check_avatars表
				mysqli_query($link, 'drop table check_avatars');

				// 创建check_avatars表
			
				mysqli_query($link, "CREATE TABLE check_avatars (uid INT(255) NOT NULL, file_size INT(255) NOT NULL, UNIQUE(uid)) ENGINE = MyISAM");
				echo '重置完成';
				break;
			




			// 清除所有帖子表
			case 'reset_topics':
				// 删除topics_x
				for ($n = 0; $n < 10; $n++) {
					mysqli_query($link, sprintf('drop table `topics_1-1_%s`', $n));
					mysqli_query($link, sprintf('drop table `topics_2-1_%s`', $n));

					// 创建topics_x
					mysqli_query($link, sprintf('CREATE TABLE `topics_1-1_%s` (tid INT(255) NOT NULL, title TINYTEXT NULL, content TEXT NULL, uid INT(255) NOT NULL, date TINYTEXT NOT NULL, tags TINYTEXT NULL, preview TINYTEXT NULL, view_count INT(255) NULL, reply_count INT(255) NULL, UNIQUE(tid)) ENGINE = MyISAM', $n));
					mysqli_query($link, sprintf('CREATE TABLE `topics_2-1_%s` (tid INT(255) NOT NULL, title TINYTEXT NULL, content TEXT NULL, uid INT(255) NOT NULL, date TINYTEXT NOT NULL, tags TINYTEXT NULL, preview TINYTEXT NULL, view_count INT(255) NULL, reply_count INT(255) NULL, UNIQUE(tid)) ENGINE = MyISAM', $n));
				}

				// 删除索引表 和 创建索引表
				mysqli_query($link, "DROP TABLE topics_index");
				mysqli_query($link, "CREATE TABLE topics_index (fid TINYTEXT NOT NULL, tid INT(255) NOT NULL, UNIQUE(tid)) ENGINE = MyISAM");
				echo '重置完成';
				break;








			// 清除用户数据
			case 'reset_users_data':
				for ($n=0; $n < 10; $n++) {
					// 清除所有用户数据
					mysqli_query($link, 'drop table users_data_'.$n);

					// 创建用户数据表
					mysqli_query($link, "CREATE TABLE users_data_{$n} (uid INT(255) NULL, online_time INT(255) NULL, identity TINYTEXT NULL, credit INT(255) NULL, academic_year TINYTEXT NULL, schoolship INT(255) NULL, judment_count INT(255) NULL, canned_count INT(255) NULL, register_time TINYTEXT NULL, last_login_time TINYTEXT NULL, sign TINYTEXT NULL, sign_img TINYTEXT NULL, best_love_story TINYTEXT NULL, playing_story TINYTEXT NULL, recommend_stories TINYTEXT NULL, UNIQUE(uid)) ENGINE = MyISAM");
				}
				echo '数据库重置完成';
				break;










			// 用户注册登录信息
			case 'reset_users_info':
				// 删除users_info表
				for ($n=0; $n < 10; $n++) {
					mysqli_query($link, 'drop table users_info_'.$n);
				}
				
				// 创建users_info表
				for ($n=0; $n < 10; $n++) {
					$sql = sprintf('
						create table users_info_%s (
							uid int(255) not null, 
							uname tinytext not null, 
							psw tinytext not null, 
							email tinytext not null, 
							buid int(255) not null, 
							ban int(255) not null,
							UNIQUE(uid)
						) engine = MyISAM', $n);
					mysqli_query($link, $sql);
				}
				echo '数据库重置完成';
				break;
			

			


			// 系统自增值
			case 'reset_auto_increment_value':
				// 清除系统自增变量表
				mysqli_query($link, 'drop table sys_auto_increment_value');


				// 创建系统增值变量表
				$sql = '
					CREATE TABLE sys_auto_increment_value ( 
						variable TINYTEXT NOT NULL , 
						value TINYTEXT NOT NULL
					) ENGINE = MyISAM;';
				mysqli_query($link, $sql);

				// 最新UID新增
				$sql = 'INSERT INTO sys_auto_increment_value (variable, value) VALUES (\'uid\', 1)';
				mysqli_query($link, $sql);

				// 最新帖子tid新增
				$sql = 'INSERT INTO sys_auto_increment_value (variable, value) VALUES (\'tid\', 1)';
				mysqli_query($link, $sql);

				// 最新aid新增
				$sql = 'INSERT INTO sys_auto_increment_value (variable, value) VALUES (\'aid\', 1)';
				mysqli_query($link, $sql);

				// 最新rid新增
				$sql = 'INSERT INTO sys_auto_increment_value (variable, value) VALUES (\'rid\', 1)';
				mysqli_query($link, $sql);
				echo '数据库重置完成';
				break;



			// 重置帖子回复数据
			case 'reset_replies_data':
				for ($n=0; $n < 10; $n++) {
					// 清除所有用户数据
					mysqli_query($link, 'drop table replies_'.$n);

					// 创建用户数据表
					mysqli_query($link, "CREATE TABLE replies_{$n} (tid INT(255) NOT NULL, uid INT(255) NOT NULL, rid INT(255) NOT NULL, content TEXT NULL, UNIQUE(rid)) ENGINE = MyISAM");
				}
				echo '数据库重置完成';
				break;


			

		}
	}








	// // 关闭数据库连接，减少资源浪费
	// mysqli_close($link);

	







































	



	// // 注册处理
	// if($_POST['cmd'] == 'register'){
	// 	$uname = $_POST['uname'];
	// 	$psw = $_POST['psw'];
	// 	// echo json_encode($uname);
	// }







	// // ces指令
	// if($_POST['cmd'] == 'ces'){
	// 	echo json_encode('sucess!');
	// 	// print_r($_COOKIE);
	// 	// $_SESSION['ces'] = 'ces';
	// 	// echo json_encode($_SESSION);
	// }







	// echo json_encode($ces);




