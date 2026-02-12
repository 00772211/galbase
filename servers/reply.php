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
		// 帖子回复
		// 
		// 
		case 'reply_topic':
			// 获取回复内容
			$tid = $_POST['tid'];
			$tid_last_char = substr($tid, -1);
			$fid = get_fid($tid);
			$content = $_POST['content'];

			// 防止重复回复
			if (reply_defense($tid, $content)) {
				echo "你在5分钟内回复了2次一样的内容~ 是不是页面没自动刷新呢~ 要不F5刷新看看呢";
				break;
			}

			// 获取回复者的UID和用户名
			$uid = get_uid();

			// 游客
			if (!$uid) {
				$uid = 4523;
			}

			$uname = get_uname($uid);
			
			// 分配一个rid
			$rid = get_value("sys_auto_increment_value", "value", "variable='rid'");
			mysqli_query($link, "UPDATE sys_auto_increment_value SET value = value + 1 WHERE variable='rid'");

			// 指定tid回复量 + 1
			mysqli_query($link, "UPDATE `topics_{$fid}_{$tid_last_char}` SET reply_count = reply_count + 1 WHERE tid=$tid");

			// 将回复信息储存进数据库
			$time = get_time("Y-m-d");
			$result =  mysqli_query($link, "INSERT INTO replies_$tid_last_char (tid, rid, uid, content, date) VALUES ($tid, $rid, $uid, '$content', '$time')");

			// 获取作者信息
			$auther_uid = get_topic($tid, "uid");
			
			// 判断sql语句执行成功
			if ($result) {
				
				log_add($uid, "\$user 回复了你的帖子 \$title：$content", $tid, $auther_uid);
				log_add($uid, "\$user 回复了帖子 \$title：$content", $tid);

			// 回复包含特殊字符
			} else {
				log_add($uid, "\$user 回复了你的帖子 \$title：(该回复包含特殊字符无法显示)", $tid, $auther_uid);
				log_add($uid, "\$user 在帖子 \$title 回复了一则包含特殊字符的回复", $tid);
			}
			break;



		// 
		// 
		// 回复别人的回复
		// 
		// 
		case 'reply_reply':
			// 根据sessionID查找uid
			$uid = get_uid();
			$reply_rid = $_POST['rid'];
			$content = $_POST['content'];
			$tid = $_POST['tid'];
			$fid = get_fid($tid);
			$tid_last_char = substr($tid, -1);
			
			// 防止重复回复
			if (reply_defense($tid, $content)) {
				echo "你在5分钟内回复了2次一样的内容~ 是不是页面没自动刷新呢~ 要不F5刷新看看呢";
				break;
			}

			// 游客
			if (!$uid) {
				$uid = 4523;
				$finger = $_COOKIE['finger'];

				// 部分用户规避指纹，记录IP为指纹
				if (!isset($finger)) {
					$finger = $_SERVER['REMOTE_ADDR'];
				}
			}

			// 分配一个rid
			$rid = get_value("sys_auto_increment_value", "value", "variable='rid'");
			mysqli_query($link, "UPDATE sys_auto_increment_value SET value = value + 1 WHERE variable='rid'");

			// 将回复信息储存进数据库
			$time = get_time();
			$result =  mysqli_query($link, "INSERT INTO replies_$tid_last_char (tid, rid, uid, content, date, reply_rid) VALUES ($tid, $rid, $uid, '$content', '$time', $reply_rid)");

			// 指定tid回复量 + 1
			mysqli_query($link, "UPDATE `topics_{$fid}_{$tid_last_char}` SET reply_count = reply_count + 1 WHERE tid=$tid");

			// 获取作者信息
			$auther_uid = mysqli_query($link, "SELECT uid FROM replies_{$tid_last_char} WHERE rid=$reply_rid LIMIT 1")->fetch_assoc()['uid'];

			// 判断sql语句执行成功
			if ($result) {

				// 日志记录
				if ($uid) {
					log_add($uid, "\$user 在帖子 \$title 回复了你：$content", $tid, $auther_uid);
					log_add($uid, "\$user 回复了帖子 \$title：$content", $tid);
				} else {
					log_add(0, "游客($finger) 访问了帖子 \$title", $tid, $auther_uid);
					log_add(0, "游客($finger) 回复了帖子 \$title：$content", $tid);
				}

			// 回复包含特殊字符
			} else {

				log_add($uid, "\$user 在帖子 \$title 回复了你：(该回复包含特殊字符无法显示)", $tid, $auther_uid);
				log_add($uid, "\$user 在帖子 \$title 回复了一则包含特殊字符的回复", $tid);
			}
			break;

		// 
		// 
		// 删除回复
		// 
		// 
		case 'remove_reply':
			$uid = get_uid();
			$tid = $_POST['tid'];
			$tid_last_char = substr($tid, -1);
			$rid = $_POST['rid'];

			// 如果是管理员
			if (administrator($uid) == 1) {

				// 获取回复内容，日志记录用
				$content = get_value("replies_$tid_last_char", "content", "rid=$rid");

				// 根据tid分表删除
				mysqli_query($link, "DELETE FROM `replies_$tid_last_char` WHERE rid=$rid OR reply_rid=$rid");

				// 记录日志
				log_add($uid, "\$user 在帖子 \$title 删除了回复：$content", $tid);

				// BUG未修复
				echo "succ";
			} else {
				echo "refuse";
			}
			break;
	}
