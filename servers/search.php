<?php
	// 连接数据库
	require_once dirname(dirname(__FILE__)).'/conn.php';

	// 连接外部函数
	require_once dirname(dirname(__FILE__)).'/functions.php';

	if(!$_POST['cmd']) {
		exit("cmd参数不存在");
	}

	// 
	// 提取日期
	// 
	function extractDate($str) {
		preg_match('/\d{4}-\d{2}-\d{2}/', $str, $matches);
		return $matches[0] ?? null;  // 返回日期，若没有找到则返回null
	}

	switch ($_POST['cmd']) {
		
		// 
		// 搜索
		// 
		case 'search':
			$type = $_POST['type'];
			$content = $_POST['content'];
			$uid = get_uid();
			require_once dirname(dirname(__FILE__)).'/config.php';

			if ($content) {

				// 日志记录
				if ($uid) {
					log_add($uid, "\$user 使用了一次搜索，类型为{$type}，搜索内容：$content");

				} else {
					$finger = get_finger();			
					log_add(0, "游客($finger) 使用了一次搜索，类型为{$type}，搜索内容：$content");
				}
			}
			
			$count = 0;

			// 仅匹配标题
			if ($type == "title") {

				// 获取所有tid并循环
				$tids = mysqli_query($link, "SELECT * FROM topics_index ORDER BY tid DESC");
				while ($row = $tids->fetch_assoc()) {
					$fid = $row['fid'];
					$tid = $row['tid'];
					$tid_last_char = substr($tid, -1);

					// 查询匹配帖子
					$result = mysqli_query($link, "SELECT title, uid, date FROM `topics_{$fid}_{$tid_last_char}` WHERE (title LIKE '%$content%') AND tid IN (SELECT tid FROM `topics_{$fid}_{$tid_last_char}` WHERE tid=$tid);");
					if (mysqli_num_rows($result) != 0) {
						$result = $result->fetch_assoc();
						$count++;

						// 获取帖子数据整合
						$title = $result['title'];
						$auther = get_uname($result['uid']);
						$date = $result['date'];
						$board_name = get_board_name($fid);
						$format = "$date -> <a href='/space/". $result['uid']."' target='_blank'>$auther(" . $result['uid'] . ")</a> 在版块 $board_name 发帖 <a href='/topic/$tid' target='_blank'>$title</a>";

						$data[$count] = "<li><span class='tag'>$count</span>$format</li>";
					}
				}
			}


			if ($type == "tag") {
				$tag_ID = get_value("tags_index", "id", "tag='$content'");

				// tag存在
				if ($tag_ID) {
					
					// 根据tag ID查找拥有该tag ID的帖子
					$tids = mysqli_query($link, "SELECT * FROM topics_index ORDER BY tid DESC");
					while ($row = $tids->fetch_assoc()) {
						$fid = $row['fid'];
						$tid = $row['tid'];
						$tid_last_char = substr($tid, -1);

						// 查询匹配帖子
						$result = mysqli_query($link, "SELECT title, uid, date, tags FROM `topics_{$fid}_{$tid_last_char}` WHERE (tags LIKE '%$tag_ID|%' OR tags LIKE '%|$tag_ID%' OR tags='$tag_ID') AND tid IN (SELECT tid FROM `topics_{$fid}_{$tid_last_char}` WHERE tid=$tid);");
						if (mysqli_num_rows($result) > 0) {
							$result = $result->fetch_assoc();
							$count++;

							// 再次精确匹配tag_ID（光上面sql语法无法更准确的获取到指定小数字tag_ID）
							$tags = explode('|', $result['tags']);
							if (in_array($tag_ID, $tags)) {

								// 获取帖子数据整合
								$title = $result['title'];
								$auther = $result['uid'];
								$uname = get_uname($auther);
								$date = $result['date'];
								$board_name = get_board_name($fid);
								$tag_str = format_tags_to_str($tid);
								$format = "$date -> <a href='/space/$auther' target='_blank'>$uname($auther)</a> 在版块 $board_name 发帖 <a href='/topic/$tid' target='_blank'>$title</a>";

								$data[$count] = "<li><span class='tag'>$count</span><span class='tag tag3'>$tag_str</span>$format</li>";
							}
						}	
					}

				// tag不存在
				} else {
					$count = 0;
				}
			}



			// 全文匹配
			if ($type == "normal") {


				// 从数据库中匹配搜索
				$tids = mysqli_query($link, "SELECT * FROM topics_index ORDER BY tid DESC");

				// 循环所有tid
				while ($row = $tids->fetch_assoc()) {
					$fid = $row['fid'];
					$tid = $row['tid'];
					$tid_last_char = substr($tid, -1);


					// 根据fid和tid查找匹配内容
					$result = mysqli_query($link, "SELECT title, uid, date FROM `topics_{$fid}_{$tid_last_char}` WHERE (title LIKE '%$content%' OR content LIKE '%$content%') AND tid IN (SELECT tid FROM `topics_{$fid}_{$tid_last_char}` WHERE tid=$tid);");
					
					if (mysqli_num_rows($result) > 0) {
						$result = $result->fetch_assoc();
			
						// 获取标题
						$title = $result['title'];
						$count++;

						// 获取各项数据
						$auther = get_uname($result['uid']);
						$date = $result['date'];
						$board_name = get_board_name($fid);
						// 2024-01-25 21:40 -> lzh_2(1) 在版块XXX发帖 这是帖子标题 跳转<a href='/view_topic.php?tid=$tid&page=0' target='_blank'>$title</a>

						$format = "$date -> <a href='/space/" . $result['uid'] . "' tartget='_blank'>$auther(" . $result['uid'] . ")</a> 在版块 $board_name 发帖 <a href='/topic/$tid' target='_blank'>$title</a>";

						// 标题包含
						if (strstr($title, $content)) {
							$data[$count] = "<li><span class='tag tag3'>标题包含</span>$format</li>";

						// 内容包含
						} else {
							$data[$count] = "<li><span class='tag'>内容包含</span>$format</li>";
						}
					}
					
				}
			}

			// 将字符串与提取的日期关联，并按日期进行倒序排序
			usort($data, function($a, $b) {
				// 提取日期
				$dateA = extractDate($a);
				$dateB = extractDate($b);

				// 日期转为时间戳进行比较
				$timestampA = strtotime($dateA);
				$timestampB = strtotime($dateB);

				// 倒序排序
				return $timestampB - $timestampA;
			});

			$data['count'] = $count;
			echo json_encode($data);
			break;
	}
