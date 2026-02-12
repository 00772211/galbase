<?php
	// 连接数据库
	require_once dirname(dirname(__FILE__)).'/conn.php';

	// 连接外部函数
	require_once dirname(dirname(__FILE__)).'/functions.php';

	if(!$_POST['cmd']) {
		exit("cmd参数不存在");
	}

	switch ($_POST['cmd']) {

		// 发布帖子
		case 'send_topic':
			// 获取数据
			$title = $_POST['title'];
			$content = $_POST['content'];
			$tags = $_POST['tags'];
			$cover = $_POST['cover'];
			$fid = $_POST['fid'];
			$cid = $_POST['cid'];

			// 时区设置，如果不设置，strtotime函数会自动补上时分秒，如2011-11-11参数进入，会变成2011-11-11 08:00:00进入。
			date_default_timezone_set('Asia/Shanghai');
			$date = get_time();
			$timestamp = strtotime($date);

			// 根据今日发帖数去排列最近发帖
			$min = $timestamp - 1;
			$max = $timestamp + 100;
			$count = mysqli_query($link, "SELECT COUNT(tid) FROM topics_index WHERE last_modify BETWEEN $min AND $max;");
			$count = $count->fetch_assoc()['COUNT(tid)'];
			$timestamp = $timestamp + $count;

			// 替换单引号
			if (strpos($title, "'")) {
				$title = str_replace("'", "&apos;", $title);
			}
			if (strstr($content, "'")) {
				$content = str_replace("'", "&apos;", $content);
			}
			if (strstr($tags, "'")) {
				$tags = str_replace("'", "&apos;", $tags);
			}

			// 根据sessionID查找uid
			$uid = get_uid();

			// 如果cid不存在
			if (!$cid) {
				break;
			}

			// cid全为数字，即为tid，为修改帖子
			if (is_numeric($cid)) {
				$tid = $cid;
				$tid_last_char = substr($tid, -1);

				// 获取旧的fid
				$old_fid = get_fid($tid);

				// 旧fid == 新fid，更新帖子内容
				if ($old_fid == $fid) {
					// 更新帖子
					mysqli_query($link, "UPDATE `topics_{$fid}_{$tid_last_char}` SET title='$title', content='$content', tags='$tags', preview='$cover' WHERE tid={$tid} LIMIT 1");

					// 更新topics_index索引列表
					mysqli_query($link, "UPDATE topics_index SET last_modify='$timestamp' WHERE tid=$tid");

					// 对tags格式化	
					format_tags_to_id($tid);

					// 更新vid索引
					if ($fid == "1-1") {
						update_vid_index($tid);
					}

					// 获取作者UID，管理员更改帖子发送至用户
					$auther_uid = get_topic($tid, "uid");
					if ($auther_uid != $uid) {
						log_add($uid, "\$user 在版块{$fid}重新编辑了你的帖子：\$title", $tid, $auther_uid);
					}

					// 记录日志
					log_add($uid, "\$user 在版块{$fid}重新编辑的帖子：\$title", $tid);

				// fid有变更
				} else {

					// 获取帖子的发帖的 日期 浏览量 回复量
					$data = get_topic($tid);
					// ['date']
					// ['view_count']
					// ['reply_count']

					// 更新topics_index索引列表
					mysqli_query($link, "UPDATE topics_index SET fid='$fid',last_modify='$timestamp' WHERE tid=$tid");

					// 删除旧贴分表占位
					mysqli_query($link, "DELETE FROM `topics_{$old_fid}_{$tid_last_char}` WHERE tid=$tid LIMIT 1");

					// 插入新的fid分表
					mysqli_query($link, "INSERT INTO `topics_{$fid}_{$tid_last_char}` (tid, title, content, uid, date, tags, preview, view_count, reply_count) VALUES ('$tid', '$title', '$content', '{$data['uid']}', '{$data['date']}', '$tags', '$cover', '{$data['view_count']}', '{$data['reply_count']}')");

					// 对tags格式化	
					format_tags_to_id($tid);

					// 更新vid索引
					if ($fid == "1-1") {
						update_vid_index($tid);
					}

					// 获取作者UID，管理员更改帖子发送至用户
					$auther_uid = get_topic($tid, "uid");
					if ($auther_uid != $uid) {
						log_add($uid, "\$user 在版块{$fid}重新编辑了你的帖子：\$title，并且将帖子从版块{$old_fid}迁移至{$fid}", $tid, $auther_uid);
					}

					// 记录日志
					log_add($uid, "\$user 将重新编辑的帖子：\$title 从版块{$old_fid}迁移至{$fid}", $tid);
				}

				// 生成预览图
				if ($cover) {
					create_preview($fid, $tid, $cover);
				}
				break;

			// 新发帖审核
			} else {
				// 获取最新tid
				$tid = get_value("sys_auto_increment_value", "value", "variable='tid'");
				$tid_last_char = substr($tid, -1);

				// tid自增值+1
				mysqli_query($link, "UPDATE sys_auto_increment_value SET value = value + 1 WHERE variable='tid'");

				// 分配入表
				mysqli_query($link, "INSERT INTO `topics_{$fid}_{$tid_last_char}` (tid, title, content, uid, date, tags, preview, view_count, reply_count) VALUES ('$tid', '$title', '$content', '$uid', '$date', '$tags', '$cover', 0, 0)");

				// 记录至索引
				mysqli_query($link, "INSERT INTO `topics_index` (fid, tid, last_modify) VALUES ('$fid', $tid, $timestamp)");

				// 更新tags
				format_tags_to_id($tid);

				// 对帖子作者猫罐头 + 1
				$uid_last_char = substr($uid, -1);
				mysqli_query($link, "UPDATE `users_data_$uid_last_char` SET canned_count = canned_count + 1 WHERE uid=$uid LIMIT 1;");

				// 新发帖文件夹入库
				$chunk = chunk($tid);
				if (file_exists("./data/forums/$chunk/data$chunk/{$cid}")) {
					if (copyDirectory("./data/forums/$chunk/data$chunk/{$cid}", "./data/forums/$chunk/data$chunk/{$tid}")) {
						delete_folder("./data/forums/$chunk/data$chunk/{$cid}");
					}
				}

				// 生成预览图
				if ($cover) {
					create_preview($fid, $tid, $cover);
				}

				// 更新vid索引
				if ($fid == "1-1") {
					update_vid_index($tid);
				}

				// 记录日志
				log_add($uid, "\$user 在版块{$fid}发布了帖子：\$title", $tid);
			}

			// 更新索引

			break;






		// 删除帖子
		case 'remove_topic':
			$uid = get_uid();
			$tid = $_POST['tid'];
			$reason = $_POST['reason'];
			$tid_last_char = substr($tid, -1);
			$auther_uid = get_topic($tid, "uid");

			// 如果是管理员
			if (administrator($uid) == 1 or $uid == $auther_uid) {

				// 获取帖子标题，记录日志用
				$title = get_topic($tid, "title");

				// 获取tid对应的fid
				$fid = get_fid($tid);

				// 根据分表删除帖子索引和数据
				mysqli_query($link, "DELETE FROM `topics_index` WHERE tid=$tid LIMIT 1; ");
				mysqli_query($link, "DELETE FROM `topics_{$fid}_$tid_last_char` WHERE tid=$tid LIMIT 1; ");

				// 如果每日推荐中包含，则清除每日推荐
				// 每日推荐格式：0||1||2
				$recommend = get_value("sys_auto_increment_value", "value", "variable='recommend'");

				// 格式清除：0||，||1||，||2
				$recommend = str_replace("$tid||", "84||", $recommend);
				$recommend = str_replace("||$tid||", "||84||", $recommend);
				$recommend = str_replace("||$tid", "||84", $recommend);
			
				// 更新每日推荐
				mysqli_query($link, "UPDATE sys_auto_increment_value SET value='$recommend' WHERE variable='recommend' LIMIT 1");

				// 执行者风纪执行+1
				$uid_last_char = substr($uid, -1);
				mysqli_query($link, "UPDATE users_data_$uid_last_char SET judment_count = judment_count + 1 WHERE uid=$uid; ");

				// 被执行者风纪执行-1 同时 奶酪罐头-1
				$auther_uid_last_char = substr($auther_uid, -1);
				mysqli_query($link, "UPDATE users_data_$auther_uid_last_char SET judment_count = judment_count - 1 WHERE uid=$auther_uid; ");
				mysqli_query($link, "UPDATE users_data_$auther_uid_last_char SET canned_count = canned_count - 1 WHERE uid=$auther_uid; ");

				// 删除所有有关回复
				mysqli_query($link, "DELETE FROM replies_$tid_last_char WHERE tid=$tid; ");

				// 删除帖子的文件夹
				$chunk = chunk($tid);
				delete_folder("./data/forums/$chunk/data$chunk/$tid");

				// 记录日志
				log_add($uid, "\$user 在版块{$fid}对帖子 <a>$title</a> 进行了风纪执行，执行理由：$reason");
				log_add($uid, "\$user 对你的帖子 <a>$title</a> 进行了风纪执行，执行理由：$reason", $tid, $auther_uid);

				echo 'succ';

			} else {
				echo "refuse";
			}
			break;



	}


