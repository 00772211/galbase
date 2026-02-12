<?php

	// 
	// 压缩图片为avif
	// 
	function to_avif($in, $out="", $remove=TRUE, $q=80) {
		if (!$out) {
			// 获取文件命
			$file_name = explode("/", $in);
			$file_name = end($file_name);
			$out = explode(".", $file_name)[0] . ".avif";
			$out = str_replace($file_name, $out, $in);
		}

		// 使用 GD 库加载 JPEG 图像
		$image = imagecreatefromjpeg($in);
		if (!$image) {
			return "无法加载 JPEG 图像";
		}

		// 使用 GD 库保存为 AVIF 格式
		if (!imageavif($image, $out, $q)) {
			return "无法保存为 AVIF 格式";
		}

		// 释放资源
		imagedestroy($image);

		// 删除jpg源文件
		if ($remove) {
			unlink($in);
		}
		// // 尝试调用外部压缩
		// try {

		// 	// win系统和ubuntu系统路径不一样
		// 	if (stripos(PHP_OS, 'WIN') === 0) {
		// 		$result = shell_exec(__DIR__."/data/avifenc.exe -q $q -s 6 $in $out");
		// 	} else {
		// 		$result = shell_exec("/var/www/html/data/avifenc -q $q -s 6 $in $out");
		// 	}

		// 	// 删除jpg源文件
		// 	if ($remove) {
		// 		unlink($in);
		// 	}

		// 	// 检查命令是否成功执行
		// 	if ($result === null) {
		// 		throw new Exception("命令执行失败或没有输出");
		// 	}
		// 	return TRUE;
	
		// } catch (Exception $e) {
		// 	return "捕获异常: " . $e->getMessage();
		// }
	}

	// 
	// avif图片转png
	// 
	function to_png($in, $out, $remove=TRUE) {
		if (!$out) {
			// 获取文件命
			$file_name = explode("/", $in);
			$file_name = end($file_name);
			$out = explode(".", $file_name)[0] . ".png";
			$out = str_replace($file_name, $out, $in);
		}

		// 尝试调用外部压缩
		try {

			// win系统和ubuntu系统路径不一样
			if (stripos(PHP_OS, 'WIN') === 0) {
				$result = shell_exec(__DIR__."/data/avifdec.exe $in $out");
			} else {
				$result = shell_exec("/var/www/html/data/avifdec $in $out");
			}

			// 删除jpg源文件
			if ($remove) {
				unlink($in);
			}

			// 检查命令是否成功执行
			if ($result === null) {
				throw new Exception("命令执行失败或没有输出");
			}
			return TRUE;
	
		} catch (Exception $e) {
			return "捕获异常: " . $e->getMessage();
		}
	}



	// 
	// 
	// 前端title格式化输出
	// 格式："系 统 通 知"
	// 
	function title_format($text) {
		// 初始化一个空字符串来保存结果
		$new_text = '';
	
		// 将字符串转换为数组，使用 preg_split 来按每个字符拆分
		$chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
	
		foreach ($chars as $char) {
			$new_text .= "<li>$char</li>";
		}
	
		return $new_text;
	}




	// 
	// 
	// 判断文件服务器文件是否存在
	// 
	// 
	function file_exist($url) {
		// 文件存在
		if (!strstr(get_headers($url)[3], "charset")) {
			return TRUE;

		// 文件不存在
		} else {
			return FALSE;
		}
	}



	// 
	// 
	// 获取用户对GAL的评分和状态
	// 
	// 
	function get_score($tid) {
		global $link;

		$score = mysqli_query($link, "SELECT score FROM topics_index WHERE tid=$tid LIMIT 1")->fetch_assoc()['score'];
		return $score;
	}


	function get_rating($tid, $uid="") {
		global $link;
		$tid_last_char = substr($tid, -1);

		// 用户评分
		if ($uid) {
			$result = mysqli_query($link, "SELECT date, score, state FROM `rating_$tid_last_char` WHERE tid=$tid AND uid=$uid");
			$result = $result->fetch_assoc();
			// 0 -> date
			// 1 -> score
			// 2 -> state

			// 如果评分不存在
			if (!$result) {
				$result['date'] = "无";
				$result['score'] = 0;
				$result['state'] = "未进行";
			}
			return $result;

		// 总评分
		} else {
			$tid_last_char = substr($tid, -1);
			$result = mysqli_query($link, "SELECT ROUND(AVG(score), 2) AS average, COUNT(tid) AS ratings FROM rating_$tid_last_char WHERE tid=$tid; ")->fetch_assoc();
			// ['average']
			// ['ratings']

			// 如果评分不存在
			if ($result['average'] == null) {
				$result['average'] = 0;
			}
			return $result;
		}
	}



	// 
	// 
	// 日志记录
	// 
	// 
	// \$user -> lzh_2(1)
	// \$title -> feng下的夏天
	function log_add($uid, $content, $tid="", $auther_uid="") {
		global $link;

		// 用户替换
		if(strstr($content, "\$user")) {
			$uname = get_uname($uid);
			$content = str_replace("\$user", "<a href='/space/$uid' target='_blank'>$uname($uid)</a>", $content);
		}

		// 帖子标题替换
		if(strstr($content, "\$title")) {
			$title = get_topic($tid, "title");
			$content = str_replace("\$title", "<a href='/topic/$tid' target='_blank'>$title</a>", $content);
		}

		// 若auther_uid没填为系统日志
		$year = get_time("Y");
		if (!($auther_uid)) {
			$date = get_time("Y-m-d H:i");
			mysqli_query($link, "INSERT INTO `logs_$year` (uid, date, content, `read`) VALUES (0, '$date', \"$content\", '0'); ");

		// 目标作者的日志
		} else {
			$date = get_time("Y-m-d");
			mysqli_query($link, "INSERT INTO `logs_$year` (uid, date, content, `read`) VALUES ($auther_uid, '$date', \"$content\", '0'); ");
		}

		
	}

	



	// 
	// 
	// 获取用户头像
	// 
	// 
	function get_avatar($uid="") {
		global $config;
		$domain = $config['data3'];

		// 已登录
		if ($uid) {

			// 判断是否有头像
			$uid_last_char = substr($uid, -1);
			if (get_value("users_data_$uid_last_char", "avatar", "uid=$uid")) {
				$data['big'] = "$domain/{$uid}_big.avif";
				$data['medium'] = "$domain/{$uid}_medium.avif";
				$data['small'] = "$domain/{$uid}_small.avif";
				return $data;
			}
		}

		// 随机取一个头像
		$num = rand(1, $config['random_avatars']);
		$data['big'] = "$domain/random/" . $num . ".avif";
		$data['medium'] = "$domain/random/" . $num . ".avif";
		$data['small'] = "$domain/random/" . $num . ".avif";
		return $data;
	}



	// 
	// 
	// 获取版块名字
	// 
	// 
	function get_board_name($fid, $hidden=0, $remove_space=0) {
		global $boards;
	
		$name = $boards[$fid];
		
		// 如果需要隐藏首尾字符
		if ($hidden == 1) {
			// 去掉字符串的第一个和最后一个字符
			$name = str_replace("「", "", $name);
			$name = str_replace("」", "", $name);
		}
	
		// 如果需要去除空格
		if ($remove_space == 1) {
			$name = str_replace(" ", "", $name);
		}
	
		return $name;
	}

	// 
	// 
	// 加密，有效期5分钟
	// 
	// 
	function encode($text) {
		global $link;

		// 加密信息并储存进数据库
		$timestamp = time();
		$md5 = md5($text . '||' . get_uid() . '||'. $timestamp);
		mysqli_query($link, "INSERT INTO encode (md5, text, timestamp) VALUES ('$md5', '$text', $timestamp)");

		return $md5;
	}



	// 
	// 
	// 解密，有效期5分钟
	// 
	// 
	function decode($encode) {
		global $link;

		// 获取MD5对应的timestamp
		$timestamp = time() - 300; // 300秒
		$result = mysqli_query($link, "SELECT text, timestamp FROM encode WHERE md5='$encode' AND timestamp > $timestamp; ");
		$text = $result->fetch_assoc()['text'];

		// 从数据库删除
		mysqli_query($link, "DELETE FROM encode WHERE md5='$encode'; ");

		return $text;
	}



	// 
	// 
	// 判断uid是否为管理员
	// 
	// 
	function administrator($uid) {
		$administrators = [1, 73];
		if (in_array($uid, $administrators)) {
			return TRUE;
		} else {
			return FALSE;
		}
	}



	// 
	// 
	//	图片压缩，目前存在文件覆盖的BUG。
	// 
	// 
	function img_zip($sourcePath, $targetPath, $quality, $maxResolution) {
		// 判断文件不存在
		if (!file_exists($sourcePath)) {
			return("图片不存在");
		}

		// 获取图片信息
		$imageInfo = getimagesize($sourcePath);
		$mime = $imageInfo['mime'];
		$width = $imageInfo[0];
		$height = $imageInfo[1];

		// 计算压缩后的尺寸（如果超过最大分辨率）
		if ($width > $maxResolution || $height > $maxResolution) {
			$ratio = min($maxResolution / $width, $maxResolution / $height);
			$newWidth = (int)($width * $ratio);  // 强制转换为整数
			$newHeight = (int)($height * $ratio);  // 强制转换为整数
		} else {
			$newWidth = $width;
			$newHeight = $height;
		}

		// 根据 MIME 类型加载图片并处理
		switch ($mime) {
			case 'image/jpeg':
				$image = imagecreatefromjpeg($sourcePath);
				break;
			case 'image/png':
				$image = imagecreatefrompng($sourcePath);
				break;
			case 'image/gif':
				$image = imagecreatefromgif($sourcePath);
				break;
			case 'image/avif':
				// GD 库支持 AVIF 格式，确保 PHP 和 GD 正确配置
				$image = imagecreatefromavif($sourcePath);
				break;
			default:
				return "不支持的图片格式";
		}

		if (!$image) {
			return "无法读取图片";
		}

		// 创建压缩后的画布
		$compressedImage = imagecreatetruecolor($newWidth, $newHeight);

		// 将原始图像复制到压缩画布中
		imagecopyresampled($compressedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

		// 根据 MIME 类型保存图像
		switch ($mime) {
			case 'image/jpeg':
				imagejpeg($compressedImage, $targetPath, $quality);
				break;
			case 'image/png':
				imagepng($compressedImage, $targetPath, round($quality / 10)); // PNG 的质量范围是 0-9
				break;
			case 'image/gif':
				imagegif($compressedImage, $targetPath);
				break;
			case 'image/avif':
				// 保存为 AVIF 格式
				imageavif($compressedImage, $targetPath, $quality);
				break;
		}

		// 释放资源
		imagedestroy($image);
		imagedestroy($compressedImage);

		return TRUE;
	}


	function img_zip_old($sourcePath, $targetPath, $quality, $maxResolution) {
		// 判断文件不存在
		if (!file_exists($sourcePath)) {
			return("图片不存在");
		}

		// // 判断目标图片存在
		// if (file_exists($targetPath)) {
		// 	return("目标图片已存在");
		// }

		// 获取图片信息
		$imageInfo = getimagesize($sourcePath);
		$mime = $imageInfo['mime'];
		$width = $imageInfo[0];
		$height = $imageInfo[1];
	
		// 计算压缩后的尺寸（如果超过最大分辨率）
		if ($width > $maxResolution || $height > $maxResolution) {
			$ratio = min($maxResolution / $width, $maxResolution / $height);
			$newWidth = $width * $ratio;
			$newHeight = $height * $ratio;
		} else {
			$newWidth = $width;
			$newHeight = $height;
		}
	
		// 创建画布
		$image = imagecreatefromstring(file_get_contents($sourcePath));
	
		// 创建压缩后的画布
		$compressedImage = imagecreatetruecolor($newWidth, $newHeight);
	
		// 将原始图像复制到压缩画布中，并设置压缩质量
		imagecopyresampled($compressedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
	
		// 保存压缩后的图像
		imagejpeg($compressedImage, $targetPath, $quality);
	
		// 释放资源
		imagedestroy($image);
		imagedestroy($compressedImage);

		return("succ");
	}


	// function img_zip($sourcePath, $targetPath, $quality, $maxResolution) {
	//     // 判断文件不存在
	//     if (!file_exists($sourcePath)) {
	//         return("图片不存在");
	//     }

	//     // 获取图片信息
	//     $imageInfo = getimagesize($sourcePath);
	//     $mime = $imageInfo['mime'];
	//     $width = $imageInfo[0];
	//     $height = $imageInfo[1];

	//     // 计算压缩后的尺寸（如果超过最大分辨率）
	//     if ($width > $maxResolution || $height > $maxResolution) {
	//         $ratio = min($maxResolution / $width, $maxResolution / $height);
	//         $newWidth = $width * $ratio;
	//         $newHeight = $height * $ratio;
	//     } else {
	//         $newWidth = $width;
	//         $newHeight = $height;
	//     }

	//     // 使用 Imagick 来处理图像
	//     try {
	//         $image = new Imagick($sourcePath);

	//         // 调整尺寸
	//         $image->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1, true);

	//         // 根据 MIME 类型保存图像
	//         switch ($mime) {
	//             case 'image/jpeg':
	//                 $image->setImageCompressionQuality($quality);
	//                 $image->setImageFormat('jpeg');
	//                 break;
	//             case 'image/png':
	//                 $image->setImageCompressionQuality($quality);
	//                 $image->setImageFormat('png');
	//                 break;
	//             case 'image/gif':
	//                 $image->setImageFormat('gif');
	//                 break;
	//             case 'image/avif':
	//                 $image->setImageCompressionQuality($quality);
	//                 $image->setImageFormat('avif');
	//                 break;
	//             default:
	//                 return "不支持的图片格式";
	//         }

	//         // 保存图像
	//         $image->writeImage($targetPath);

	//         // 清理资源
	//         $image->destroy();
	//     } catch (Exception $e) {
	//         return "处理图片时出错: " . $e->getMessage();
	//     }

	//     return "succ";
	// }



	
	// 
	// 
	//	获取前端uid
	// 
	// 
	function get_uid() {
		global $link;

		if (isset($_COOKIE['sessionID'])) {
			// 根据sessionID查找uid
			$uid = mysqli_query($link, "SELECT uid FROM users_sessions WHERE sessionID='{$_COOKIE['sessionID']}'");
			$uid = $uid->fetch_assoc()['uid'];

			return($uid);
		} else {
			return FALSE;
		}
	}



	// 
	// 
	//	根据uid查找用户名
	// 
	// 
	function get_uname($uid) {
		global $link;
		
		// 拆分uid最后一位做分表
		$uid_last_char = substr($uid, -1);

		// 根据uid查找用户名
		$uname = mysqli_query($link, "SELECT uname FROM `users_info_{$uid_last_char}` WHERE uid={$uid}");
		$uname = $uname->fetch_assoc()['uname'];

		return($uname);
	}



	// 
	// 
	// 根据tid找fid
	// 
	// 
	function get_fid($tid) {
		global $link;

		// 查 topics_index 表
		$fid = mysqli_query($link, "SELECT fid FROM topics_index WHERE tid=$tid LIMIT 1");
		$fid = $fid->fetch_assoc()['fid'];

		return($fid);
	}



	// 
	// 
	// 根据tid获取帖子内容
	// 
	// 
	function get_topic($tid, $fetch='*') {
		global $link;

		// 根据tid获取fid
		$fid = get_fid($tid);

		// 根据tid和fid查表找帖子数据
		$tid_last_char = substr($tid, -1);

		try {
			$data = mysqli_query($link, "SELECT {$fetch} FROM `topics_{$fid}_{$tid_last_char}` WHERE tid=$tid LIMIT 1");
			$data = $data->fetch_assoc();
		} catch (Throwable $e) {
			// return "error：数据查询错误。" . $e->getMessage();
			return FALSE;
		}

		// 根据 $fetch 需要返回
		if ($fetch != "*") {
			return($data[$fetch]);

		// 全返回
		} else {
			return($data);
		}
	}



	// 
	// 
	// 判断某表字段是否存在
	// 
	// 
	function mysql_exist($table, $fetch, $value, $else='') {
		global $link;

		// 判断目标数据库字段是否存在
		$result = mysqli_query($link, "SELECT IF(COUNT($fetch) > 0, 1, 0) FROM `$table` WHERE $fetch='$value' $else LIMIT 1;");
		$result = $result->fetch_row()[0];

		// 存在返回1，不存在返回0
		return($result);
	}



	// 
	// 
	// 删除文件夹
	// 
	// 
	function delete_folder($folder_path, $self=TRUE) {
		if (!is_dir($folder_path)) { // 验证文件夹是否存在
			return;
		}
	
		$files = array_diff(scandir($folder_path), array('.', '..')); // 获取文件夹中的文件和子文件夹（不包括"."和"..")
	
		foreach ($files as $file) {
			$filePath = $folder_path . '/' . $file;
			
			if (is_dir($filePath)) { // 如果是子文件夹
				delete_folder($filePath);
			} else { // 如果是文件，直接删除
				unlink($filePath);
			}
		}
	
		if ($self) {
			// 删除空文件夹
			rmdir($folder_path);
		}
	}



	// 
	// 
	// 获取fid对应的最新贴
	// 
	// 
	function get_newest_topic($fid) {
		global $link;

		// 获取当前fid最新tid
		$tid = mysqli_query($link, "SELECT tid FROM topics_index WHERE fid='$fid' ORDER BY tid DESC LIMIT 1;");
		$tid = $tid->fetch_assoc()['tid'];

		// 获取tid最后一位做分表
		$tid_last_char = substr($tid, -1);

		// 根据tid最后一位查表找最新帖子数据
		$data = mysqli_query($link, "SELECT uid, title, date, view_count, reply_count FROM `topics_{$fid}_{$tid_last_char}` WHERE tid={$tid} LIMIT 1;");
		$data = $data->fetch_assoc();
		$data['tid'] = $tid;
		// ['tid]
		// ['uid']
		// ['title']
		// ['date']
		// ['view_count']
		// ['reply_count']

		// 标题去掉<br>
		$data['title'] = str_replace("<br>", "", $data['title']);

		// 获取帖子作者用户名
		$data['auther'] = get_uname($data['uid']);

		// 利用js更新数据
		$data['date'] = time_diff($data['date']);
		return $data;
	}



	// 
	// 
	// 获取fid对应的帖子数量
	// 
	// 
	function get_topics_count($fid) {
		global $link;

		// 获取fid对应的帖子数量
		$count = mysqli_query($link, "SELECT COUNT(fid) FROM topics_index WHERE fid='$fid' ;");
		$count = $count->fetch_assoc()['COUNT(fid)'];
		return($count);
	}



	// 
	// 
	// 计算时间差，结果为小时
	// 
	// 
	function time_diff($time) {
		$timezone = new DateTimeZone('Asia/Shanghai');

		// 支持多种格式
		$formats = ['Y-m-d H:i', 'Y-m-d H:i:s', 'Y-m-d'];
		$datetime = false;

		foreach ($formats as $format) {
			$datetime = DateTime::createFromFormat($format, $time, $timezone);
			if ($datetime !== false) break;
		}

		if ($datetime === false) {
			return "无效的时间格式";
		}

		$now = new DateTime('now', $timezone);

		// 比较日期部分（仅年月日）
		$date_given = $datetime->format('Y-m-d');
		$date_now   = $now->format('Y-m-d');

		// 如果是同一天
		if ($date_given === $date_now) {
			return "今天";
		}

		// 计算时间差（小时）
		$timestamp = $datetime->getTimestamp();
		$now_timestamp = $now->getTimestamp();
		$diff_hours = ($now_timestamp - $timestamp) / 3600;

		if ($diff_hours < 0) {
			return "未来时间";
		}

		// 根据差值选择单位
		if ($diff_hours > 365 * 24) {
			$years = floor($diff_hours / (365 * 24));
			return $years . "年前";
		} elseif ($diff_hours > 30 * 24) {
			$months = floor($diff_hours / (30 * 24));
			return $months . "个月前";
		} elseif ($diff_hours > 48) {
			$days = floor($diff_hours / 24);
			return $days . "天前";
		} elseif ($diff_hours >= 24) {
			return "1天前";
		} else {
			$hours = floor($diff_hours);
			return $hours . "小时前";
		}
	}


	// 
	// 
	// 获取数据表中的一个值
	// 
	// 
	function get_value($table, $value, $if) {
		global $link;

		$result = mysqli_query($link, "SELECT $value FROM $table WHERE $if LIMIT 1; ");

		// 查询函数不为0获取value
		if (mysqli_num_rows($result) != 0) {
			return $result->fetch_assoc()[$value];
		} else {
			return FALSE;
		}
	}





	// 
	// 
	// 格式化tag为字符串
	// 
	// 
	function format_tags_to_str($tid) {
		global $link;

		// 从tid获取tags，并且备份一次为格式化做准备
		$tags = get_topic($tid, "tags");

		if ($tags) {
			$array = [];

			// 对tags进行拆分
			$tags = explode("|", $tags);

			// 循环每个tag对应的id
			for ($i = 0; $i < count($tags); $i++) {
				$id = $tags[$i];

				// 根据id查找对应的tag字符
				$tag = get_value("tags_index", "tag", "id=$id");

				// 对tags进行恢复
				$array[$i] = $tag;
			}
			return(implode("|", $array));
		}
	}



	// 
	// 
	// 格式化tag为id
	// 类型有tid 和 str 2种
	// 
	// 
	function format_tags_to_id($tid, $type="tid") {
		global $link;

		// 从tid获取tags，并且备份一次为格式化做准备
		if ($type == "tid") {
			$tags = get_topic($tid, "tags");
		}

		// 如果类型为str
		if ($type == "str") {
			$tags = $tid;
		}

		if ($tags) {
			$array = [];

			// 对tags进行拆分
			$tags = explode("|", $tags);

			// 循环每个tag
			for ($i = 0; $i < count($tags); $i++) {
				$tag = $tags[$i];

				// 查询tag是否存在id
				$result = mysql_exist('tags_index', 'tag', "$tag");
				
				// 如果tag不存在，则创建tag
				if ($result != 1) {
					mysqli_query($link, "INSERT INTO `tags_index` (tag, count) VALUES ('$tag', 1)");

				// 如果tag存在，tag热度+1
				} else {
					mysqli_query($link, "UPDATE tags_index SET count = count + 1 WHERE tag='$tag'; ");
				}

				// 获取各个tag对应的id
				$id = get_value("tags_index", "id", "tag='$tag'");

				// 格式化tag储存字符串
				$array[$i] = $id;
			}

			$tags_id = implode("|", $array);

			// 类型为str时，需要返回
			if ($type == "str") {
				return($tags_id);
			}

			// 更新tag
			$fid = get_fid($tid);
			$tid_last_char = substr($tid, -1);
			mysqli_query($link, "UPDATE `topics_{$fid}_$tid_last_char` SET tags='$tags_id' WHERE tid=$tid LIMIT 1");
		}
	}



	// 
	// 
	// 获取上海时间Y-m-d H:i:s
	// 
	// 
	function get_time($format='Y-m-d') {
		// 给定时区
		$timezone = new DateTimeZone('Asia/Shanghai');
		$date = new DateTime('now', $timezone);
		$date = $date->format($format);
		return($date);
	}



	// 
	// 判断tid处在哪个OD储存区间
	// 
	function chunk($tid, $type="", $url=FALSE) {
		// 正常版块
		if (!$type) {

			// 第一区间
			if ($tid >= 1 && $tid <= 863) {

				// 以链接的形式返回
				if ($url == TRUE) {
					if ($_SERVER['SERVER_ADDR'] == "127.0.0.1") {
						return "http://127.0.0.1:8000/data/forums/1/data1";
					} else {

						// 晚高峰期20:00 - 22:00
						$h = get_time("H");
						if ($h >= 20 && $h < 22) {
							return("/data/forums/1/data1");
						}
						return "https://data1.galbase.top";
					}
				}
				return "1";

			// 第二区间
			} elseif ($tid > 863 && $tid <= 99999) {

				// 以链接的形式返回
				if ($url == TRUE) {
					if ($_SERVER['SERVER_ADDR'] == "127.0.0.1") {
						return "http://127.0.0.1:8000/data/forums/2/data2";
					} else {

						// 晚高峰期20:00 - 22:00
						$h = get_time("H");
						if ($h >= 20 && $h < 22) {
							return("/data/forums/2/data2");
						}
						return "https://d2gal.dpdns.org";
					}
				}
				return "2";
			} else {
				return "error";
			}
		}

		// 动画版块
		if ($type == "anime") {
			if ($tid >= 1 && $tid <= 99999) {
				return "1";
			}
		}
	}


	// 
	// 更新tid中的所有vid索引
	// 
	function update_vid_index($tid) {
		global $link;

		// 删除指定tid的所有vids索引
		mysqli_query($link, "DELETE FROM vids_index WHERE tid=$tid; ");

		// 遍历文件，筛选出以 .mp4 结尾的文件
		$chunk = chunk($tid);
		foreach(scandir(dirname(__FILE__)."/forums/$chunk/data$chunk/$tid") as $file) {
			if(pathinfo($file, PATHINFO_EXTENSION) == 'mp4') {
				if ($file) {
					$vid = str_replace(".mp4", "", $file);

					// 记录进vid索引
					mysqli_query($link, "INSERT INTO `vids_index` (vid, tid) VALUES ($vid, $tid); ");
				}
			}
		}
	}



	// 
	// 生成缩略图
	// 
	function create_preview($fid, $tid, $aids) {
		$chunk = chunk($tid);

		// 判断fid是否为1-1，如果是，则只创建一个preview
		if ($fid == '1-1' || $fid == '1-2' || $fid == '1-3' || $fid == '1-4') {

			// 将avif压小
			$in = dirname(__FILE__)."/data/forums/$chunk/data$chunk/$tid/$aids.avif";
			$out = dirname(__FILE__)."/data/forums/$chunk/data$chunk/$tid/preview.avif";

			img_zip($in, $out, 80, 400);

			// 判断win系统还是linux系统，不同系统用不同的工具压缩
			// if (stripos(PHP_OS, 'WIN') === 0) {
			// 	shell_exec("avifdec -q 100 $in $out");
			// 	img_zip($out, $out, 1, 400);
			// 	to_avif($out);
			// } else {
			// 	shell_exec("magick $in -resize 400x400\> $out");
			// }

		// fid不为1-1，多个缩略图
		} else {

			// 对aids进行分割
			$aids = explode('|', $aids);

			// 遍历压缩所有图片
			for ($i=0; $i < count($aids); $i++) { 
				$in = dirname(__FILE__)."/data/forums/$chunk/data$chunk/$tid/{$aids[$i]}.avif";
				$out = dirname(__FILE__)."/data/forums/$chunk/data$chunk/$tid/preview_$i.avif";
				img_zip($in, $out, 80, 400);
				// shell_exec("magick $in -resize 400x400\> $out");
			}
		}
	}



	// 
	// 递归复制文件夹的函数
	// 
	function copyDirectory($src, $dst) {
		if (!is_dir($src)) {
			return false;
		}
		// 创建目标文件夹
		@mkdir($dst);
		$files = scandir($src);
		foreach ($files as $file) {
			if ($file != '.' && $file != '..') {
				if (is_dir("$src/$file")) {
					copyDirectory("$src/$file", "$dst/$file");
				} else {
					copy("$src/$file", "$dst/$file");
				}
			}
		}
		return true;
	}



	// 
	// 确认uid操作是否为本人
	// 
	function auther($uid) {
		$auther_uid = get_uid();
		if ($uid == $auther_uid) {
			return TRUE;
		} else {
			return FALSE;
		}
	}



	// 
	// 获取前端指纹
	// 
	function get_finger() {
		if (isset($_COOKIE['finger'])) {
			$finger = $_COOKIE['finger'];
		}

		// 部分用户规避指纹，记录IP为指纹
		if (!isset($finger)) {
			$finger = $_SERVER['REMOTE_ADDR'];
		}

		return $finger;
	}



	// 
	// 推送帖子
	// 
	function push($tid) {
		global $link;

		$timestamp = strtotime(get_time("Y-m-d H:i:s"));
		mysqli_query($link, "UPDATE topics_index SET last_modify='$timestamp' WHERE tid='$tid'");
	}



	// 
	// 防止二重回复
	// 
	function reply_defense($tid, $content) {
		global $link;

		// 获取最新rid
		$rid = get_value("sys_auto_increment_value", "value", "variable='rid'") - 1;
		$tid_last_char = substr($tid, -1);

		// 获取对于rid的回复内容和时间
		$result = mysqli_query($link, "SELECT content, date FROM `replies_$tid_last_char` WHERE rid=$rid LIMIT 1; ");

		if (mysqli_num_rows($result) != 0) {
			$result = $result->fetch_assoc();

			// 判断时间差 < 600秒
			$reply_date = strtotime($result['date']);
			$timestamp = strtotime(get_time());
			if ($timestamp - $reply_date < 600) {

				// 判断内容是否一样
				if ($content == $result['content']) {
					return TRUE;
				}
			}
		}
	}



	// 
	// 伪正则表达式
	// 
	function str_select($source, $pre, $last) {
		$source = explode($pre, $source)[1];
		$source = explode($last, $source)[0];
		return $source;
	}



	// 
	// 
	// 
?>