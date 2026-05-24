<?php
	header("Access-Control-Allow-Origin: *");

	// 链接必要模块
	require_once dirname(dirname(__FILE__)).'/conn.php';
	require_once dirname(dirname(__FILE__)).'/functions.php';

	// 尝试从xhr中获取cmd
	if ($_POST['cmd']) {
		$cmd = $_POST['cmd'];
	}

	if ($cmd){
		switch ($cmd) {

			// 上传音乐
			case 'upload_music':
				// 获取文件信息
				$name = $_POST['name'];
				$artist = $_POST['artist'];
				$album = $_POST['album'];
				$time = round($_POST['time']);
				$cover = $_FILES['cover']['tmp_name'];
				$file = $_FILES['music']['tmp_name'];
				$uid = get_uid();

				if (!$uid) {
					break;
				}

				// 获取当前最新tid
				$tid = get_value("sys_auto_increment_value", "value", "variable='tid'");
				$chunk = chunk($tid, "anime");

				// 获取最新mid
				$mid = get_value("sys_auto_increment_value", "value", "variable='mid'");
				$mid_last = substr($mid, -1);

				// 优先移动文件
				move_uploaded_file($cover, __DIR__."/animes/$chunk/music/$mid.jpg");
				move_uploaded_file($file, __DIR__."/animes/$chunk/music/$mid.mp3");

				if (!file_exists(__DIR__."/animes/$chunk/music/$mid.jpg") || !file_exists(__DIR__."/animes/$chunk/music/$mid.mp3")) {
					@unlink(__DIR__."/animes/$chunk/music/$mid.jpg");
					@unlink(__DIR__."/animes/$chunk/music/$mid.mp3");
					echo "上传失败，请重新上传";
					break;
				}

				// 音乐信息储存至数据库
				// 已知BUG：
				// （注意这里前后端都没写双引号过滤，以后会出现报错很正常！）
				// （没做相同音乐的数据筛查）
				mysqli_query($link, "INSERT INTO `music_$mid_last` (`mid`, `name`, `artist`, `album`, `time`, `chunk`, `upload_uid`) VALUES ('$mid', \"$name\", \"$artist\", \"$album\", '$time', '$chunk', '$uid'); ");
				$list = get_value("music_index", "mids", "list=0");
				mysqli_query($link, "UPDATE `music_index` SET `mids` = '{$list}|{$mid}' WHERE `list` = 0;");

				// mid+1
				mysqli_query($link, "UPDATE sys_auto_increment_value SET value = value + 1 WHERE variable='mid'");

				echo "succ";

				// 对封面进行压缩
				img_zip(__DIR__."/animes/$chunk/music/$mid.jpg", __DIR__."/animes/$chunk/music/$mid.jpg", 75, 200);
				break;



			// 更新今日硬盘占用
			case 'update_server_disk':
				$free_space = disk_free_space(dirname(__FILE__));
				$total_space = round($free_space / (1024 * 1024 * 1024), 2) - 40.00;
				mysqli_query($link, "UPDATE sys_auto_increment_value SET value='$total_space' WHERE variable='server_ram' LIMIT 1");
				break;



			// 发帖时加载帖子图片
			case 'load_topic_imgs':
				$cid = $_POST['cid'];

				// 新发帖
				if (!is_numeric($cid)) {
					$chunk = chunk(get_value("sys_auto_increment_value", "value", "variable='tid'"));

				// tid
				} else {
					$chunk = chunk($cid);
				}

				// 存在才查询
				if (file_exists(__DIR__."/forums/$chunk/data$chunk/{$cid}")) {

					// 获取文件夹下的所有图像文件
					$imgs = scandir(__DIR__."/forums/$chunk/data$chunk/{$cid}");				

					// 删去0键和1键，0为"."本级目录，1为".."上级目录，和缩略图缓存db
					unset($imgs[0]);
					unset($imgs[1]);
					unset($imgs[array_search('Thumbs.db', $imgs)]);

					// 利用正则表达式过滤掉非avif文件
					$imgs = array_filter($imgs, function($value) {
						return preg_match('/\.avif$/', $value);
					});

					// 去除键名
					$imgs = array_values($imgs);

					// 返回前端
					echo json_encode($imgs);
				}
				break;



			// 帖子图片上传
			case 'topic_imgs_upload':
				// cid或tid提取
				$cid = $_POST['cid'];

				// 获取最新aid
				$aid = get_value("sys_auto_increment_value", "value", "variable='aid'");

				// 修改发帖，判断cid是否为tid
				if (is_numeric($cid)) {
					$chunk = chunk($cid);
				// 新发帖
				} else {
					$chunk = chunk(get_value("sys_auto_increment_value", "value", "variable='tid'"));
				}

				// 不存文件夹则创建
				if (!file_exists(__DIR__."/forums/$chunk/data$chunk/{$cid}")) {
					mkdir(__DIR__."/forums/$chunk/data$chunk/{$cid}");
				}

				// 移入文件
				$file_cache = $_FILES['file']['tmp_name'];
				$path = __DIR__."/forums/$chunk/data$chunk/$cid/$aid.jpg";
				move_uploaded_file($file_cache, $path);

				// 压缩成avif格式
				to_avif($path);

				// 返回前端最新显示
				echo $aid;

				// // aid + 1
				mysqli_query($link, "UPDATE sys_auto_increment_value SET value = value + 1 WHERE variable='aid'");
				break;



			// 
			// 视频切片上传
			// 
			case 'chunks_upload':
				// 获取参数
				$cid = $_POST['cid'];
				$base64 = $_POST['file_base64'];
				$index = $_POST['index'];
				$all_chunks = $_POST['all_chunks'];

				// 修改帖子，tid
				if (is_numeric($cid)) {
					$chunk = chunk($cid);

				// 新发帖，cid
				} else {
					$chunk = chunk(get_value("sys_auto_increment_value", "value", "variable='tid'"));
				}

				// 帖子文件夹不存在则创建
				$path = __DIR__."/forums/$chunk/data$chunk/$cid";
				if (!file_exists($path)) {
					mkdir($path);
				}

				// 缓存文件夹不存在则创建缓存文件夹
				$path = __DIR__."/forums/$chunk/data$chunk/$cid/cache";
				if (!is_dir($path)) {
					mkdir($path, 0777, true);
				}

				// 判断切片是否存在，存在则不写入
				$path = __DIR__."/forums/$chunk/data$chunk/$cid/cache/$base64.mp4.$index";
				if (!file_exists($path)) {
					// 保存切片
					move_uploaded_file($_FILES['chunk']['tmp_name'], $path);
				}

				if ($index + 1 == $all_chunks) {
					echo "succ";
				}
				break;



			// 
			// 检测视频切片是否存在
			// 
			case 'check_chunks':
				$cid = $_POST['cid'];
				$file_base64 = $_POST['file_base64'];
				$all_chunks = $_POST['all_chunks'];

				// 修改帖子，tid
				if (is_numeric($cid)) {
					$chunk = chunk($cid);

				// 新发帖，cid
				} else {
					$chunk = chunk(get_value("sys_auto_increment_value", "value", "variable='tid'"));
				}

				// 检查cache文件夹是否存在
				$path = __DIR__."/forums/$chunk/data$chunk/$cid/cache";
				if (!file_exists($path)) {
					$result['state'] = "error";
					$result['content'] = "视频未上传！服务器无缓存切片！";
					echo json_encode($result);
					break;
				}

				// 检查切片数量
				for ($i = 0; $i < $all_chunks; $i++) {
					$path = __DIR__."/forums/$chunk/data$chunk/$cid/cache/$file_base64.mp4.$i";
					if (!file_exists($path)) {
						$result['state'] = "error";
						$result['content'] = "切片不完整，请重新上传！丢失切片：$i";
						echo json_encode($result);
						exit;
					}
				}

				$result['state'] = "succ";
				echo json_encode($result);
				break;



			// 
			// 合并视频切片
			// 
			case 'combine_chunks':
				$cid = $_POST['cid'];
				$base64 = $_POST['file_base64'];
				$all_chunks = $_POST['all_chunks'];
				$size = $_POST['size'];

				// 修改帖子，tid
				if (is_numeric($cid)) {
					$chunk = chunk($cid);

				// 新发帖，cid
				} else {
					$chunk = chunk(get_value("sys_auto_increment_value", "value", "variable='tid'"));
				}

				// 检查cache文件夹是否存在
				$path = __DIR__."/forums/$chunk/data$chunk/$cid/cache";
				if (!file_exists($path)) {
					echo "error";
					break;
				}

				// 获取vid
				$vid = mysqli_query($link, "SELECT value FROM sys_auto_increment_value WHERE variable='vid' LIMIT 1");
				$vid = $vid->fetch_assoc()['value'];

				// 指定输出文件
				$output_file = fopen(__DIR__."/forums/$chunk/data$chunk/$cid/$vid.mp4", 'w');

				// 将切片添加入数组
				$input_files = array();
				for ($i = 0; $i < $all_chunks; $i++) {
					array_push($input_files, __DIR__."/forums/$chunk/data$chunk/$cid/cache/$base64.mp4.$i");
				}

				// 循环每个切片
				foreach ($input_files as $intput_file) {
					$input = fopen($intput_file, 'r');

					// 逐个读取输入文件片段内容，并写入到输出文件中
					while (!feof($input)) {
						$data = fread($input, 1048576); // 每次读取1MB = 1 * 1024 * 1024数据
						fwrite($output_file, $data);

						// 休眠，单位s
						sleep(1);
					}
					fclose($input);
				}
				fclose($output_file);

				// 获取源视频大小 和 现视频大小
				$now_size = filesize(__DIR__."/forums/$chunk/data$chunk/$cid/$vid.mp4");

				// 如果恢复视频大小 = 源视频大小，删除所有切片文件
				if ($now_size == $size) {
					// vid + 1
					mysqli_query($link, "UPDATE `sys_auto_increment_value` SET value = value + 1 WHERE variable='vid' LIMIT 1;");

					// 删除cache文件夹
					delete_folder(__DIR__."/forums/$chunk/data$chunk/$cid/cache");
					echo 'succ';

				// 如果恢复视频大小 != 源视频大小
				} else {
					echo 'error';
				}
				break;














			// 
			// 加载帖子视频
			// 
			case 'load_topic_videos':
				// 获取cid 或 tid
				$cid = $_POST['cid'];

				// 修改帖子，tid
				if (is_numeric($cid)) {
					$chunk = chunk($cid);

				// 新发帖，cid
				} else {
					$chunk = chunk(get_value("sys_auto_increment_value", "value", "variable='tid'"));
				}

				if (file_exists(__DIR__."/forums/$chunk/data$chunk/$cid")) {
					// 获取文件夹下的所有图像文件
					$videos = scandir(__DIR__."/forums/$chunk/data$chunk/$cid");

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

					// 获取视频文件大小
					foreach ($videos as &$video) {
						$file_path = __DIR__."/forums/$chunk/data$chunk/$cid/$video";

						// 获取文件大小并转换为MB
						$file_size = filesize($file_path) / (1024 * 1024); // 转换为MB
						$file_size = number_format($file_size, 1); // 保留一位小数

						$video .= " | {$file_size}MB";
					}

					// 解除引用
					unset($video);

					// 返回前端
					echo json_encode($videos);
				}
				break;


			// 
			// 删除视频
			// 
			case 'delete_video':
				$cid = $_POST['cid_or_tid'];
				$vid = $_POST['vid'];
				$chunk = "";

				// 修改帖子，tid
				if (is_numeric($cid)) {
					$chunk = chunk($cid);

				// 新发帖，cid
				} else {
					$chunk = chunk(get_value("sys_auto_increment_value", "value", "variable='tid'"));
				}

				// 删除目标视频文件
				unlink("./forums/$chunk/data$chunk/$cid/$vid.mp4");
				break;






			// 帖子删除图片
			case 'remove_topic_img':
				// 获取cid / tid和aid
				$cid = $_POST['cid'];
				$aid = $_POST['aid'];

				// 修改帖子，tid
				if (is_numeric($cid)) {
					$chunk = chunk($cid);
					
				// 新发帖，cid
				} else {
					$chunk = chunk(get_value("sys_auto_increment_value", "value", "variable='tid'"));
				}

				unlink(__DIR__."/forums/$chunk/data$chunk/$cid/$aid.avif");
				echo TRUE;
				break;




			// 上传头像
			case 'upload_avatar':
				$uid = $_POST['uid'];
				$size = $_POST['size'];

				// 移动头像进缓存文件夹
				$file_cache = $_FILES['file']['tmp_name'];
				move_uploaded_file($file_cache, __DIR__."/forums/3/data3/{$uid}_{$size}.jpg");

				// 压缩成avif
				to_avif(dirname(__FILE__)."/forums/3/data3/{$uid}_{$size}.jpg");

				if ($size == "size") {

					// 记录作者有头像
					$uid_last_char = substr($uid, -1);
					mysqli_query($link, "UPDATE `users_data_$uid_last_char` SET avatar=1 WHERE uid=$uid; ");

					// 日志
					require_once dirname(dirname(__FILE__))."/config.php";
					$uname = get_uname($uid);
					mysqli_query($link, sprintf("INSERT INTO logs_%s (uid, date, content, `read`) VALUES (0, '%s', '<a href=\"/space/$uid\" target=\"_blank\">$uname</a> 上传了头像<a href=\"$tcp_port/data/avatars/{$uid}.jpg\" target=\"_blank\">{$uid}_small.jpg</a>', 0);", date('Y'),  date('Y-m-d H:i')));
				}
				break;
	}
}

