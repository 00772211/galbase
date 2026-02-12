<?php
	// 连接数据库
	require_once dirname(dirname(__FILE__)).'/conn.php';

	// 连接外部函数
	require_once dirname(dirname(__FILE__)).'/functions.php';

	if(!$_POST['cmd']) {
		exit("cmd参数不存在");
	}

	switch ($_POST['cmd']) {



		// 请求下载
		case 'download':
			require_once dirname(dirname(__FILE__))."/config.php";
			$tid = $_POST['tid'];
			$uid = get_uid();

			// 未登录，uid4523的用户名是 游客
			if (!$uid) {
				$uid = 4523;
			}

			$uid_last_char = substr($uid, -1);

			// // 今日下载达到3次，禁止下载
			// $today = get_time("Y-m-d");
			// if (get_value("logs_download", "count", "date='$today' AND uid=$uid") >= $config['download_count']) {
			// 	exit("limit");
			// }

			// 获取所有链接
			$urls = mysqli_query($link, "SELECT baidu, onedrive, direct_link, else_url FROM wangpan_urls WHERE tid=$tid LIMIT 1")->fetch_assoc();
			if (!$urls) {

				// 是作者，触发上传指令
				if (get_topic($tid, "uid") == $uid) {
					exit("upload");

				// 不是作者，弹空链接提示
				} else {
					exit("none");
				}
			}

			// 计算该用户本月下载了多少个GAL
			$year = get_time("Y");
			$month = get_time("m");
			$uname = get_uname($uid);
			$count = mysqli_query($link, "SELECT COUNT(uid) FROM `logs_$year` WHERE (content LIKE '%$uname($uid)%' AND content LIKE '%请求了一次下载链接%' AND date LIKE '%$year-$month%'); ")->fetch_assoc();
			$count = $count['COUNT(uid)'] + 1;

			log_add($uid, "\$user 在帖子 \$title 请求了一次下载链接，本月累计请求了{$count}次", $tid);

			// 判断空链接
			if ($urls['baidu'] == "") { $urls['baidu'] = "不存在"; }
			if ($urls['onedrive'] == "") { $urls['onedrive'] = "不存在"; }
			if ($urls['direct_link'] == "") { $urls['direct_link'] = "不存在"; }
			if ($urls['else_url'] == "") { $urls['else_url'] = "不存在"; }

			// 获取各个链接
			$result['baidu'] = $urls['baidu'];
			$result['OD'] = $urls['onedrive'];
			$result['direct_link'] = $urls['direct_link'];
			$result['else_url'] = $urls['else_url'];

			echo json_encode($result);
			break;




		// 上传下载链接
		case 'upload_urls':
			// 获取链接和tid
			$tid = $_POST['tid'];
			$baidu = $_POST['baidu'];
			$OD = $_POST['OD'];
			$direct_link = $_POST['direct_link'];
			$else_url = $_POST['else_url'];

			// 填入新链接
			try {
				file_put_contents("/ces.txt", "INSERT INTO wangpan_urls (tid, baidu, onedrive, direct_link, else_url) VALUES ($tid, '$baidu', '$OD', '$direct_link', '$else_url')");
				mysqli_query($link, "INSERT INTO wangpan_urls (tid, baidu, onedrive, direct_link, else_url) VALUES ($tid, '$baidu', '$OD', '$direct_link', '$else_url')");

			} catch (Exception $exception) {
				echo "存在异常：$exception";
			}

			// 获取前端uid
			$uid = get_uid();
			log_add($uid, "\$user 在帖子 \$title 填入了新链接", $tid);
			break;




		// 移除帖子网盘链接
		case 'remove_urls':
			$tid = $_POST['tid'];
			$uid = get_uid();
			$auther_uid = get_topic($tid, "uid");

			// 获取作者uid
			if (administrator($uid) == 1 or $uid == $auther_uid) {

				// 移除所有网盘链接
				mysqli_query($link, "DELETE FROM `wangpan_urls` WHERE tid=$tid LIMIT 1; ");
				log_add($uid, "\$user 在帖子 \$title 移除了所有链接", $tid);
				echo "succ";

			} else {
				exit("refuse");
			}
			break;
















	}
