<?php
	// 连接数据库
	require_once dirname(dirname(__FILE__)).'/conn.php';

	// 连接外部函数
	require_once dirname(dirname(__FILE__)).'/functions.php';

	if(!$_POST['cmd']) {
		exit("cmd参数不存在");
	}

	switch ($_POST['cmd']) {
		
		// 请求指定数量推荐
		case 'request_target_num_recommend':

			$num = $_POST['num'];

			// 获取最新5个tid
			$data = mysqli_query($link, "SELECT tid FROM topics_index ORDER BY last_modify DESC LIMIT $num OFFSET 5;");
			$i = 0;

			// 循环每个tid找到对应的帖子数据
			while ($row = $data->fetch_assoc()) {
				$tid = $row['tid'];

				// 获取帖子数据
				$topics[$i] = get_topic($tid);

				// 获取作者头像和用户名
				$topics[$i]['avatar'] = get_avatar($topics[$i]['uid'])['small'];
				$topics[$i]['uname'] = get_uname($topics[$i]['uid']);
				$i++;
			}

			// 转给js处理帖子数据
			$topics = json_encode($topics);
			echo $topics;
			break;
	}
