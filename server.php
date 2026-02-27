<?php
	// 连接数据库
	require_once dirname(__FILE__).'/conn.php';

	// 连接外部函数
	require_once dirname(__FILE__).'/functions.php';





	if($_POST['cmd']) {
		switch ($_POST['cmd']) {

			// 查看帖子结构
			case 'view_topic_format':
				
				// 根据tid获取帖子内容
				$tid = $_POST['tid'];
				$content = get_topic($tid, "content");
				echo $content;
				break;


			// 请求音乐
			case 'request_music':
				$newest_mid = get_value("sys_auto_increment_value", "value", "variable='mid'") - 1;
				$mid = mt_rand(1, $newest_mid);

				// 获取歌曲信息
				$mid_last = substr($mid, -1);
				$data = mysqli_query($link, "SELECT * FROM music_$mid_last WHERE mid=$mid LIMIT 1") -> fetch_assoc();
				echo json_encode($data);
				break;

			// 请求指定mid音乐数据
			case 'request_target_music':
				$mid = $_POST['mid'];
				$mid_last = substr($mid, -1);
				$data = mysqli_query($link, "SELECT * FROM music_$mid_last WHERE mid=$mid LIMIT 1") -> fetch_assoc();
				echo json_encode($data);
				break;









			// 更新评分
			case 'update_rating':
				$tid = $_POST['tid'];
				$tid_last_char = substr($tid, -1);
				$score = $_POST['score'];
				$state = $_POST['state'];
				$uid = get_uid();
				$date = get_time();
				$uid_last_char = substr($uid, -1);

				// 先前评分过，更新评分
				if(mysql_exist("rating_$tid_last_char", "uid", "$uid", "AND tid=$tid") == 1) {
					mysqli_query($link, "UPDATE rating_$tid_last_char SET date = '$date', score = '$score', state = '$state' WHERE tid=$tid AND uid=$uid; ");

				// 第一次评分
				} else {
					mysqli_query($link, "INSERT INTO `rating_$tid_last_char` (tid, uid, date, score, state) VALUE ('$tid', '$uid', '$date', '$score', '$state'); ");
				}

				// 重新计算平均分
				$average = mysqli_query($link, "SELECT ROUND(AVG(score), 2) AS average FROM rating_$tid_last_char WHERE tid=$tid; ")->fetch_assoc()['average'];
				mysqli_query($link, "UPDATE topics_index SET score='$average' WHERE tid=$tid; ");

				// 计算学分，学分=推完的gal数
				for ($n = 0; $n < 10; $n++) {
					$count = mysqli_query($link, "SELECT COUNT(tid) FROM `rating_$n` WHERE uid='$uid' AND state='已推完'; ");
					$count = $count->fetch_assoc()['COUNT(tid)'];
					$all_count = $all_count + $count;
				}

				// 更新学分
				mysqli_query($link, "UPDATE `users_data_$uid_last_char` SET credit='$all_count' WHERE uid='$uid' LIMIT 1; ");

				log_add($uid, "\$user 在帖子 \$title 更新了评分：{$score} | {$state}，新平均分{$average}。", $tid);
				break;










			// // 保存百度网盘账号信息
			// case 'save_bdwp_account':
			// 	$cookie = $_POST['cookie'];
			// 	$uid = get_uid();
				
			// 	// cookie未空，删除账号
			// 	if (!$cookie) {
			// 		mysqli_query($link, "DELETE FROM pan_account WHERE uid=$uid; ");
			// 		exit;
			// 	}

			// 	// 判断之前填写过cookie
			// 	if (mysql_exist("pan_account", "uid", $uid) == 1) {

			// 		// 更新cookie
			// 		mysqli_query($link, "UPDATE pan_account SET cookie='$cookie' WHERE uid=$uid; ");

			// 	// 新填入，储存进pan_account
			// 	} else {
			// 		mysqli_query($link, "INSERT INTO `pan_account` (uid, cookie) VALUES ($uid, '$cookie');");
			// 	}
			// 	break;







			// 封禁用户
			case 'ban':
				// 判定管理员权限组
				if (!get_uid() == 1) {
					exit("你当前没有权限封禁任何人");
				}

				// 获取前端信息
				$uid = $_POST['uid'];
				$uid_last_char = substr($uid, -1);
				$ban_reason = $_POST['ban_reason'];

				// 根据目标uid查找QQ
				$qq = get_value("users_info_$uid_last_char", "qq", "uid=$uid");

				// 将QQ记录banlist中
				// mysqli_query($link, "INSERT INTO qq_banlist (qq) VALUE ('$qq'); ");

				// 将用户密码抹除，同时删除sessionID
				mysqli_query($link, "UPDATE users_info_$uid_last_char SET psw='' WHERE uid=$uid; ");
				mysqli_query($link, "DELETE FROM users_sessions WHERE uid=$uid; ");

				echo "风纪执行完成";
				// 
				// 日志记录
				// 

				$uid_ = get_uid();	// 执行者uid
				$uname = get_uname($uid);	// 被ban的用户名
				$uname_ = get_uname($uid_);	// 执行者用户名

				mysqli_query($link, sprintf("INSERT INTO logs_%s (uid, date, content, `read`) VALUES (0, '%s', '$uname_($uid_) 对 $uname($uid) 执行了风纪行动，执行理由：$ban_reason', 0);", date('Y'),  date('Y-m-d H:i')));
				break;



			// 请求旧站的老链接（临时）
			case 'request_old_download':
				$tid = $_POST['tid'];
				$uid = get_uid();

				// 根据tid查找百度网盘链接
				$pan = mysqli_query($link, "SELECT value FROM pre_forum_typeoptionvar WHERE tid=$tid AND optionid=14 LIMIT 1");
				$pan = $pan->fetch_assoc()['value'];

				// 查询今日是否下载过，下载过返回1，未下载返回0
				$today = date('Y-m-d');
				$result = mysql_exist('logs_download', 'uid', $uid, "AND date='$today'");
				if ($result == 1) {

					// 判断下载次数是否小于3
					$download_count = get_value("logs_download", "count", "date='$today' AND uid=$uid");
					require_once dirname(__FILE__)."/config.php";
					if ($download_count < $config['download_count']) {

						// 下载次数 + 1
						mysqli_query($link, "UPDATE `logs_download` SET count = count + 1 WHERE date='$today' AND uid=$uid LIMIT 1;");

						// 返回给前端URL
						echo $pan;

						// 日志记录
						log_add($uid, "\$user 在旧站帖子 \$title 请求了一次下载链接。", $tid);

					// 下载次数超过3次，禁止下载
					} else {
						echo 'refuse';
					}




				// 今日未下载
				} else {

					// 记录今日第一次下载链接
					mysqli_query($link, "INSERT INTO `logs_download` (date, uid, count) VALUE ('$today', $uid, 1)");

					echo $pan;

					// 
					// 日志记录
					// 
					// 获取帖子标题
					$title = get_value("pre_forum_post", "subject", "position=1 AND tid=$tid");

					// 获取前端用户名
					$uname = get_uname($uid);

					mysqli_query($link, sprintf("INSERT INTO logs_%s (uid, date, content, `read`) VALUES (0, '%s', '{$uname}({$uid}) 在旧站帖子 <a href=\"/index.php\" target=\"_blank\">$title</a>请求了一次下载链接。', 0);", date('Y'),  date('Y-m-d H:i')));

				}
				break;



























			// 系统信息已读
			case 'finish_read_system_msgs':
				mysqli_query($link, sprintf("UPDATE logs_%s SET `read` = 1 WHERE uid=0 ORDER by date DESC", date('Y')));
				break;





			// 请求系统信息
			case 'request_system_msgs':
				// 查询uid=0的近1000条信息
				$data = mysqli_query($link, sprintf("SELECT * FROM logs_%s WHERE uid=0 ORDER by date DESC LIMIT 1000", date('Y')));

				$n = 0;
				// 循环每个msg
				while ($row = $data->fetch_assoc()) {
					$msgs[$n]['date'] = $row['date'];
					$msgs[$n]['content'] = $row['content'];
					$msgs[$n]['read'] = $row['read'];
					$n++;
				}

				// 返回前端
				echo json_encode($msgs);
				break;












			// 标记已读
			case 'finish_read':
				// 获取前端sessionID查找uid
				$uid = get_uid();

				mysqli_query($link, sprintf("UPDATE logs_%s SET `read` = 1 WHERE uid=$uid ORDER by date DESC LIMIT 50", date('Y')));
				break;




			// 请求个人信息
			case 'request_msgs':
				// 获取前端sessionID查找uid
				$uid = get_uid();

				// 根据uid查找50条最新信息
				$year = get_time("Y");
				$data = mysqli_query($link, "SELECT * FROM logs_$year WHERE uid=$uid ORDER by date DESC LIMIT 50");

				$n = 0;
				// 循环每个msg
				while ($row = $data->fetch_assoc()) {
					$msgs[$n]['date'] = $row['date'];
					$msgs[$n]['content'] = $row['content'];
					$msgs[$n]['read'] = $row['read'];
					$n++;
				}

				// 返回前端
				echo json_encode($msgs);
				break;



			// 请求帖子数据
			case 'request_topics':
				$fid = $_POST['fid'];
				$page = $_POST['page'];
				$mode = $_POST['mode'];
				$uid = get_uid();

				// sql结构
				$no_push_format = "";
				$only_H_format = "";

				// 判断是否排除拔作
				if ($uid) {
					if (user_config($uid, "no_push")) {
						$no_push_format = "AND no_push IS NULL";
					}

					if (user_config($uid, "only_H")) {
						$only_H_format = "AND no_push = 1";
					}
				}

				$formats = $no_push_format . $only_H_format;

				// 偏移量 = page * 20，page初始为0
				$offset_value = $page * 20;

				// 默认排序
				if (!$mode) {
					// 获取需要的20个tid
					$result = mysqli_query($link, "SELECT tid FROM `topics_index` WHERE fid='$fid' $formats ORDER BY tid DESC LIMIT 20 OFFSET $offset_value; ");
				}

				// 按分数高低排序
				if ($mode == "score") {
					// 获取所有tid
					$result = mysqli_query($link, "SELECT tid FROM `topics_index` WHERE fid='$fid' $formats ORDER BY score DESC LIMIT 20 OFFSET $offset_value; ");
				}

				$n = 0;

				// 循环每个tid找到对应的帖子数据
				while ($row = $result->fetch_assoc()) {
					// 获取tid最后一位
					$tid = $row['tid'];
					$tid_last_char = substr($tid, -1);

					// 列表式请求
					if ($fid != "1-1" && $fid != "1-2" && $fid != "1-3" && $fid != "1-4") {
						$data = mysqli_query($link, "SELECT * FROM `topics_{$fid}_{$tid_last_char}` WHERE tid={$tid} LIMIT 1");
						$data = $data->fetch_assoc();
						// ['tid']
						// ['title']
						// ['content']
						// ['uid']
						// ['date']
						// ['tags']
						// ['preview']

						// 获取该tid的最新回复数据
						$reply = mysqli_query($link, "SELECT uid, content, date FROM replies_{$tid_last_char} WHERE tid=$tid ORDER BY rid DESC LIMIT 1");
						$reply = $reply->fetch_assoc();

						// 如果最新回复存在
						if ($reply) {

							// 获取最新回复内容
							$reply_content = $reply['content'];

							// 获取最新回复用户名
							$reply_uname = get_uname($reply['uid']);

							// 整合
							$data['newest_reply_date'] = $reply['date'];
							$data['newest_reply'] = "{$reply_uname}：$reply_content";
						}

						$data['chunk'] = chunk($data['tid'], "", TRUE);

					// 卡片式请求
					} else {
						require_once dirname(__FILE__)."/config.php";

						$data = mysqli_query($link, "SELECT tid, title, uid, date, preview, view_count, reply_count FROM `topics_{$fid}_{$tid_last_char}` WHERE tid={$tid} LIMIT 1");
						$data = $data->fetch_assoc();
						// ['tid']
						// ['title']
						// ['uid']
						// ['date']
						// ['preview']
						// ['view_count']
						// ['reply_count']
						// ['score']
						// ['tags']

						// 获取评分
						$data['rating'] = get_rating($data['tid']);

						// 获取tag
						$data['tags'] = format_tags_to_str($data['tid']);

						// 获取头像
						$data['avatar'] = get_avatar($data['uid'])['small'];
						
						$data['chunk'] = chunk($data['tid'], "", TRUE);
					}

					// 获取uid对应的用户名
					$data['auther'] = get_uname($data['uid']);

					// 整合数据
					$forums[$n]  = $data;
					$n++;
				}

				// 将所有数据返回前端
				echo json_encode($forums);
				break;


		











			// 帖子数据请求
			case 'request_list_topics':
				// 参数获取
				$fid = $_POST['fid'];
				$page = $_POST['page'];

				// 偏移量 = page * 20，page初始为0
				$offset_value = $page * 20;

				// 获取需要的20个tid
				$result = mysqli_query($link, "SELECT tid FROM topics_index WHERE fid='{$fid}' and tid <= {$max} ORDER BY tid DESC LIMIT 20");
				$n = 0;

				// 循环每个tid找到对应的帖子数据
				while ($row = $result->fetch_assoc()) {
					// 获取tid最后一位
					$tid = $row['tid'];
					$tid_last_char = substr($tid, -1);

					// 根据tid最后一位查表
					$data = mysqli_query($link, "SELECT * FROM `topics_{$fid}_{$tid_last_char}` WHERE tid={$tid} LIMIT 1");
					$data = $data->fetch_assoc();
					// ['tid']
					// ['title']
					// ['content']
					// ['uid']
					// ['date']
					// ['tags']
					// ['preview']


					// 获取uid对应的用户名
					$uid = $data['uid'];
					$uid_last_char = substr($uid, -1);
					$uname = mysqli_query($link, "SELECT uname FROM `users_info_{$uid_last_char}` WHERE uid={$uid}");
					$uname = $uname->fetch_assoc()['uname'];

					// 获取给定tid的最新回复数据
					$reply = mysqli_query($link, "SELECT uid, content, date FROM replies_{$tid_last_char} WHERE tid=$tid ORDER BY rid DESC LIMIT 1");
					$reply = $reply->fetch_assoc();

					// 如果最新回复存在，整合并输出
					if ($reply) {
						$uname = get_uname($reply['uid']);
						$content = $reply['content'];

						$data['newest_reply_date'] = $reply['date'];
						$data['newest_reply'] = "{$uname}：$content";
					}

					// 赋值给总数据
					$data['auther'] = $uname;

					// 整合数据
					$forums[$n]  = $data;
					$n++;
				}

				// 将所有数据返回前端
				echo json_encode($forums);
				break;








			// 在线时间增加
			case 'add_online_time':
				// 根据sessionID查找uid
				$uid = get_uid();

				// 取uid最后一位字符分表
				$uid_last_char = substr($uid, -1);

				// 获取上一次最后的在线时间
				$last_online_data = get_value("users_data_{$uid_last_char}", "last_login_time", "uid=$uid");
				$last_online_data = strtotime($last_online_data);

				// 超过5分钟，增加在线时间
				$timestamp = time();
				if ($timestamp - $last_online_data >= 300) {
					mysqli_query($link, "UPDATE users_data_{$uid_last_char} SET online_time = online_time + 5 WHERE uid=$uid");
					$date = get_time("Y-m-d H:i");
					mysqli_query($link, "UPDATE users_data_{$uid_last_char} SET last_login_time = '$date' WHERE uid=$uid");
				}

				// 更新在线用户，若存在则更新，不存在则新增
				if (mysql_exist("online", "uid", "$uid") == 1) {
					mysqli_query($link, "UPDATE `online` SET `last_online` = '$timestamp' WHERE uid=$uid LIMIT 1; ");
				} else {
					mysqli_query($link, "INSERT INTO `online` (`uid`, `last_online`) VALUES ('$uid', '$timestamp'); ");
				}
				break;









			// // 头像审核拒绝
			// case 'refuse_avatar':
			// 	$uid = $_POST['uid'];

			// 	// 删除头像审核数据
			// 	mysqli_query($link, "DELETE FROM check_avatars WHERE uid=$uid LIMIT 1");

			// 	// 删除头像文件
			// 	unlink("/data/_checking/avatars/{$uid}.jpg");
			// 	unlink("/data/_checking/avatars/{$uid}_small.jpg");

			// 	// 获取uid最后一位做分表（头像主人）
			// 	$uid_last_char = substr($uid, -1);

			// 	// 根据uid查找用户名（头像主人）
			// 	$uname = mysqli_query($link, "SELECT uname FROM `users_info_{$uid_last_char}` WHERE uid={$uid}");
			// 	$uname = $uname->fetch_assoc()['uname'];

			// 	// 根据sessionID查找uid（审核用户）
			// 	$uid_ = mysqli_query($link, "SELECT uid FROM users_sessions WHERE sessionID='{$_COOKIE['sessionID']}'");
			// 	$uid_ = $uid_->fetch_assoc()['uid'];

				
			// 	// 拆分uid最后一位做分表（审核用户）
			// 	$uid_last_char_ = substr($uid_, -1);

			// 	// 根据uid查找用户名（审核用户）
			// 	$uname_ = mysqli_query($link, "SELECT uname FROM `users_info_{$uid_last_char_}` WHERE uid={$uid_}");
			// 	$uname_ = $uname_->fetch_assoc()['uname'];

			// 	// 记录日志
			// 	mysqli_query($link, sprintf("INSERT INTO logs_%s (uid, date, content, `read`) VALUES (0, '%s', '{$uname_}({$uid_}) 审核并拒绝了 {$uname}({$uid}) 的头像。', 0)", date('Y'),  date('Y-m-d H:i')));
			// 	break;


			// // 头像审核通过
			// case 'allow_avatar':
			// 	$uid = $_POST['uid'];

			// 	// 移动头像文件
			// 	rename("/data/_checking/avatars/{$uid}.jpg", "/data/avatars/{$uid}.jpg");
			// 	rename("/data/_checking/avatars/{$uid}_small.jpg", "/data/avatars/{$uid}_small.jpg");

			// 	// 删除头像审核数据
			// 	mysqli_query($link, "DELETE FROM check_avatars WHERE uid=$uid LIMIT 1");

			// 	// 删除头像文件
			// 	unlink("/data/_checking/avatars/{$uid}.jpg");
			// 	unlink("/data/_checking/avatars/{$uid}_small.jpg");

			// 	// 获取uid最后一位做分表（头像主人）
			// 	$uid_last_char = substr($uid, -1);

			// 	// 根据uid查找用户名（头像主人）
			// 	$uname = mysqli_query($link, "SELECT uname FROM `users_info_{$uid_last_char}` WHERE uid={$uid}");
			// 	$uname = $uname->fetch_assoc()['uname'];

			// 	// 根据sessionID查找uid（审核用户）
			// 	$uid_ = mysqli_query($link, "SELECT uid FROM users_sessions WHERE sessionID='{$_COOKIE['sessionID']}'");
			// 	$uid_ = $uid_->fetch_assoc()['uid'];


			// 	// 拆分uid最后一位做分表（审核用户）
			// 	$uid_last_char_ = substr($uid_, -1);

			// 	// 根据uid查找用户名（审核用户）
			// 	$uname_ = mysqli_query($link, "SELECT uname FROM `users_info_{$uid_last_char_}` WHERE uid={$uid_}");
			// 	$uname_ = $uname_->fetch_assoc()['uname'];

			// 	// 记录日志
			// 	mysqli_query($link, sprintf("INSERT INTO logs_%s (uid, date, content, `read`) VALUES (0, '%s', '{$uname_}({$uid_}) 审核并通过了 {$uname}({$uid}) 的头像：<a href=\"/data/avatars/{$uid}_small.jpg\" target=\"_blank\">{$uid}_small.jpg</a>', 0)", date('Y'),  date('Y-m-d H:i')));
			// 	break;



			// 更改密码
			case 'replace_psw':
				$new_psw = $_POST['psw'];

				// 根据sessionID查找uid
				$uid = mysqli_query($link, "SELECT uid FROM users_sessions WHERE sessionID='{$_COOKIE['sessionID']}'");
				$uid = $uid->fetch_assoc()['uid'];

				// 取uid最后一位字符分表
				$uid_last_char = substr($uid, -1);

				// 更新密码
				mysqli_query($link, "UPDATE users_info_{$uid_last_char} SET psw = '$new_psw' WHERE uid=$uid LIMIT 1");
				break;



				




			// 更新个人信息
			case 'user_data_update':
				$info = explode('||', $_POST['info']);
				// [0]	->	sign
				// [1]	->	sign_img
				// [2]	->	best_love_story
				// [3]	->	playing_story
				// [4]	->	recommend_stories

				// 根据sessionID查找uid
				$uid = mysqli_query($link, "SELECT uid FROM users_sessions WHERE sessionID='{$_COOKIE['sessionID']}'");
				$uid = $uid->fetch_assoc()['uid'];

				// 取uid最后一位字符分表
				$uid_last_char = substr($uid, -1);

				// 更新数据
				mysqli_query($link, "UPDATE users_data_{$uid_last_char} SET sign = '{$info[0]}', sign_img = '{$info[1]}', best_love_story = '{$info[2]}', playing_story = '{$info[3]}', recommend_stories = '{$info[4]}' WHERE uid = $uid;");
				break;









				






				// 登录失败
				if (!$uid) {

					// 判断是否被封禁
					for ($n = 0; $n < 10; $n++) {
						$uid = mysqli_query($link, "SELECT uid FROM users_info_{$n} WHERE uname='$uname' AND psw='' LIMIT 1; ")->fetch_assoc()['uid'];
						if ($uid) {
							echo '账号被封禁，如有疑问请连续登录10多次，然后耐心等待管理员解封，解封后默认密码变为123456，请前往「澄空心情驿站 / 心跳町温泉」版块表明自己的疑问，若不表明则判定为机器进行永久封禁。';
							log_add($uid, "被封用户 \$user 尝试登录，请管理员注意。");
							return;
						}
					}

					echo '登录失败，请仔细确认 用户名 / 密码 是否存在错误。';
				}
				break;


				
			// 
			// 获取会社所有作品
			// 
			case 'get_developer_works':
				$developer = $_POST['developer'];
				$tag_id = get_value("tags_index", "id", "tag='$developer'");

				if (isset($tag_id)) {

					// 判断1-2版块哪个帖子tag包含此tagID
					for ($i = 0; $i < 10; $i++) {

						// 获取所有tags
						$tags = get_value("`topics_1-2_$i`", "tags", "tags LIKE '%$tag_id%'");

						if ($tags) {
							$tags = explode("|", $tags);

							if (in_array($tag_id, $tags)) {
								$content = get_value("`topics_1-2_$i`", "content", "tags LIKE '%$tag_id%'");
								
								if ($content) {
									// 匹配table标签
									preg_match('/<table\b[^>]*>[\s\S]*?<\/table>/ms', $content, $content);
									echo $content[0];
									break;
								}
							}
						}
					}
				}
				break;



			// 
			// 获取动画集数解密
			// 
			case 'decode_anime_ep':
				$tid = $_POST['tid'];
				$ep = $_POST['ep'];

				// 日志记录
				$uid = get_uid();

				if ($uid) {
					log_add($uid, "\$user 观看了动画 \$title 第{$ep}集", $tid);
				} else {
					$finger = $_COOKIE['finger'];
					log_add(0, "游客($finger) 观看了动画 \$title 第{$ep}集", $tid);
				}

				// 获取
				echo md5("$tid|$ep");
				break;
		}
	}
