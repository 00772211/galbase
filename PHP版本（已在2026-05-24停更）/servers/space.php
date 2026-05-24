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
		// 请求推荐作品
		// 
		case 'request_best_stories':
			$uid = $_POST['uid'];
			$stories = get_value("space_best_stories", "stories", "uid=$uid");

			// 存在则查找信息
			if ($stories) {
				$stories = explode("|", $stories);

				// 循环每个tid
				for ($i = 0; $i < count($stories); $i++) {
					$tid = $stories[$i];
					$chunk = chunk($tid, "", TRUE);
					$data[$i]['tid'] = $tid;
					$data[$i]['title'] = get_topic($tid, "title");
					$data[$i]['chunk'] = $chunk;
				}

				echo json_encode($data);
			}

			break;



		// 
		// 添加作品
		// 
		case 'add_story':
			$uid = get_uid();
			$tid = $_POST['tid'];

			// 不存在任何作品
			if (mysql_exist("space_best_stories", "uid", $uid) == 0) {
				mysqli_query($link, "INSERT INTO `space_best_stories` (uid, stories) VALUES ($uid, '$tid')");
				echo "添加成功，F5刷新后显示";

			// 存在任一作品
			} else {

				$stories = get_value("space_best_stories", "stories", "uid=$uid");

				// 拆分字符串为数组
				$array = explode("|", $stories);

				// 判断值是否存在
				if (in_array($tid, $array)) {
					echo "您要添加的作品已经存在在您的推荐里！";
				
				} else {
					$stories = "$stories|$tid";
					mysqli_query($link, "UPDATE `space_best_stories` SET `stories`='$stories' WHERE uid=$uid LIMIT 1");
					echo "添加成功，F5刷新后显示";
				}
			}
			break;



		// 
		// 搜索作品
		// 
		case 'search_work':
			$content = $_POST['kw'];
			$count = 0;

			// 获取所有tid并循环
			$tids = mysqli_query($link, "SELECT * FROM topics_index ORDER BY tid DESC");
			while ($row = $tids->fetch_assoc()) {
				$fid = $row['fid'];
				$tid = $row['tid'];
				$tid_last_char = substr($tid, -1);

				// 查询匹配帖子
				$result = mysqli_query($link, "SELECT title FROM `topics_{$fid}_{$tid_last_char}` WHERE (title LIKE '%$content%') AND tid IN (SELECT tid FROM `topics_{$fid}_{$tid_last_char}` WHERE tid=$tid);");
				if (mysqli_num_rows($result) > 0) {
					$result = $result->fetch_assoc();
					$title = $result['title'];
					$count++;
					$data[$count]['tid'] = $tid;
					$data[$count]['chunk'] = chunk($tid, "", TRUE);
					$data[$count]['title'] = $title;
				}
			}

			$data['count'] = $count;
			echo json_encode($data);
			break;

		

		// 
		// 收藏帖子
		// 
		case 'collection':
			$tid = $_POST['tid'];
			$uid = get_uid();
			$uid_last_char = substr($uid, -1);

			// 不存在此收藏
			if (mysql_exist("collection_$uid_last_char", "uid", $uid, "AND tid=$tid") == 0) {
				mysqli_query($link, "INSERT INTO `collection_$uid_last_char` (uid, tid) VALUES ($uid, $tid)");
				echo "取消收藏";

			// 存在收藏
			} else {
				mysqli_query($link, "DELETE FROM `collection_$uid_last_char` WHERE uid=$uid AND tid=$tid LIMIT 1");
				echo "收藏本贴";
			}
			break;



		// 
		// 收藏列表
		// 
		case 'request_collections':
			$uid = $_POST['uid'];
			$uid_last_char = substr($uid, -1);
			
			// 存在收藏
			if (mysql_exist("collection_$uid_last_char", "uid", $uid) == 1) {
				// 获取该用户的收藏
				$result = mysqli_query($link, "SELECT tid FROM `collection_$uid_last_char` WHERE uid=$uid");
				$n = 0;

				while ($row = $result->fetch_assoc()) {
					$tid = $row['tid'];
					$title = get_topic($tid, "title");

					// 被删贴了
					if (!$title) {

						// 删除收藏索引
						mysqli_query($link, "DELETE FROM `collection_$uid_last_char` WHERE uid=$uid AND tid=$tid LIMIT 1");
						continue;
					}
					$data[$n]['tid'] = $tid;
					$data[$n]['title'] = $title;
					$n++;
				}

				echo json_encode($data);

			// 无收藏
			} else {
				echo FALSE;
			}
			break;


		// 
		// 已推GAL
		// 
		case 'request_finished_galgame':
			$uid = $_POST['uid'];
			$data = [];

			// 循环评分表
			for ($i = 0; $i < 10; $i++) {
				$tids = mysqli_query($link, "SELECT tid,date FROM `rating_$i` WHERE uid=$uid AND state='已推完'");
				while ($row = $tids->fetch_assoc()) {
					$tid = $row['tid'];
					$title = get_topic($tid, "title");
					$date = $row['date'];

					// 构建完整数据返回
					$data[$date]['tid'] = $tid;
					$data[$date]['title'] = $title;
				}
			}

			echo json_encode($data);
			break;


		
		// 
		// 刷新顺序
		// 
		case 'refresh_works':
			$uid = $_POST['uid'];
			$tids = $_POST['tids'];

			// 判断是否为本人
			if (!auther($uid)) {
				return;
			}

			// 更新数据表
			mysqli_query($link, "UPDATE `space_best_stories` SET `stories`='$tids' WHERE uid=$uid LIMIT 1");

			echo TRUE;
			break;



		// 
		// 删除作品
		// 
		case 'remove_work':
			$uid = $_POST['uid'];
			$tid = $_POST['tid'];

			// 判断是否为本人
			if (!auther($uid)) {
				return;
			}

			// 获取字段
			$tids = get_value("space_best_stories", "stories", "uid='$uid'");
			$tids = "|" . $tids . "|";

			// 清除目标tid
			$tids = str_replace("|$tid|", "|", $tids);
			
			// 去掉第一个和最后一个|
			$tids = substr($tids, 1);
			$tids = substr($tids, 0, -1);

			// 更新数据表
			mysqli_query($link, "UPDATE `space_best_stories` SET `stories`='$tids' WHERE uid=$uid LIMIT 1");

			echo TRUE;
			break;
	}


