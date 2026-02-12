<?php
	header('Access-Control-Allow-Origin: *');

	// 连接数据库
	require_once dirname(dirname(__FILE__)).'/conn.php';

	// 连接外部函数
	require_once dirname(dirname(__FILE__)).'/functions.php';

	if(!$_POST['cmd']) {
		exit("cmd参数不存在");
	}

	function containsSubstringInJson($filePath, $substring) {
		// 检查文件是否存在
		if (!file_exists($filePath)) {
			echo "文件不存在！";
			return false;
		}
		
		// 读取文件内容
		$jsonContent = file_get_contents($filePath);
		
		// 检查文件读取是否成功
		if ($jsonContent === false) {
			echo "读取文件失败！";
			return false;
		}

		// 使用 strpos 检查子字符串是否存在
		if (strpos($jsonContent, $substring) !== false) {
			return true;  // 找到子字符串
		} else {
			return false; // 未找到子字符串
		}
	}
	
	function insertJsonData($filePath, $newData) {
		// 读取文件内容
		if (!file_exists($filePath)) {
			echo "文件不存在！";
			return false;
		}
		$jsContent = file_get_contents($filePath);

		// 查找 "const cache = " 是否已经存在
		$startPos = strpos(trim($jsContent), "const cache =");
		if ($startPos === false) {
			echo "文件格式错误！没有 'const cache = ' 开头。\n";
			return false;
		}

		// 去除开头的 "const cache = " 部分和结尾的分号，只保留 JSON 部分
		$jsonStartPos = strpos($jsContent, '{');
		$jsonEndPos = strrpos($jsContent, '}');

		if ($jsonStartPos === false || $jsonEndPos === false) {
			echo "JSON 部分格式错误！\n";
			return false;
		}

		// 提取 JSON 部分
		$jsonContent = substr($jsContent, $jsonStartPos, $jsonEndPos - $jsonStartPos + 1);
		
		// 如果 JSON 部分为空，初始化为空对象
		if (empty($jsonContent)) {
			$jsonContent = '{}';
		}

		// 解码 JSON 内容为数组
		$data = json_decode($jsonContent, true);

		// 检查 JSON 是否解析成功
		if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
			echo "JSON文件解析错误！\n";
			echo "错误信息: " . json_last_error_msg() . "\n";
			return false;
		}

		// 获取要插入的键值对的 key
		$newDataKey = key($newData);
		if (isset($data[$newDataKey])) {
			echo "键值已存在，跳过插入。\n";
			return false;
		}

		// 插入新的键值对
		$data[$newDataKey] = $newData[$newDataKey];

		// 将数据重新编码为 JSON
		$jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

		// 拼接 "const cache = " 前缀，并加上分号
		$jsContent = "const cache = " . $jsonContent . ";\n";

		// 将更新后的 JS 内容写回文件
		file_put_contents($filePath, $jsContent);

		echo "数据插入成功。\n";
		return true;
	}


	switch ($_POST['cmd']) {
		// 
		// 头像更新
		// 
		case 'new_info':
			$SteamID64 = $_POST['SteamID64'];

			// 判断是否包含了缓存，包含则无视
			$result = containsSubstringInJson(dirname(__FILE__).'/data/html/zhizhuzidata/cache.js', $SteamID64);
			if ($result) {
				break;
			}

			// 更新头像和用户名
			$html = file_get_contents("https://id.ovohi.com/?query=$SteamID64");

			// 获取头像URL
			$avatar = explode('<div class="player-info">', $html)[1];
			$avatar = explode('" alt', $avatar)[0];
			$avatar = explode('src="', $avatar)[1];

			// 获取名字
			$uname = explode('<div class="player-details">', $html)[1];
			$uname = explode('</h2>', $uname)[0];
			$uname = explode('<h2>', $uname)[1];

			$newData = [
				"$SteamID64" => [
					"uname" => "$uname",
					"avatar" => "$avatar"
				]
			];
			insertJsonData(dirname(__FILE__).'/data/html/zhizhuzidata/cache.js', $newData);

			// 日志记录
			log_add(0, "蜘蛛子项目毒狗有信息更新！更新ID：https://id.ovohi.com/?query=$SteamID64", 0, 1);
			break;


		// 
		// 
		// 更新毒狗信息
		// 
		// 
		case 'updata':
			$content = $_POST['content'];
			log_add(0, "$content", 0, 1);
			break;

		case 'updata_all':
			$admin = $_POST['admin'];
			$type = $_POST['type'];

			// 判定admin
			// if ($admin != "2233") {
			// 	exit()
			// }

			// 读取文件内容
			$jsonContent = file_get_contents('data.json');

			// 解码JSON数据
			$data = json_decode($jsonContent, true); // 设置为true返回关联数组
			$data = $data[$type];

			// foreach ($data_white as $key => $value) {
			// 	// 如果值是数组或对象，可以进一步处理
			// 	echo "<li><img src='https://0d000721.cc/data/html/zhizhuzidata/avatars/$key.jpg'>$key | $value</li>";
			// }

			$html = file_get_contents("https://id.ovohi.com/?query=76561199792409987");
			$html = explode("查询工具", $html)[1];
			// $html = explode("友情链接", $html)[0];
			file_put_contents("/a.txt", $html);

			

			break;
	}
