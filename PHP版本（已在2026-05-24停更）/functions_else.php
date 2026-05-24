<?php

	// 
	// 爬取touchgal最新20个帖子
	// 
	function touchgal_get() {
		global $link;

		$base_url = "https://www.touchgal.top/";
		$post_ids = [];

		// 使用 cURL 获取页面内容
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $base_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");

		// 忽略 SSL 证书验证（仅用于调试，生产环境请避免使用）
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		
		// 执行请求并获取结果
		$html = curl_exec($ch);

		// 检查是否请求成功
		if(curl_errno($ch)) {
			// 输出 cURL 错误信息
			echo "cURL 错误: " . curl_error($ch) . "\n";
			curl_close($ch);
			return;
		}

		// 检查HTTP状态码
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($http_code != 200) {
			echo "HTTP 错误: 状态码 $http_code\n";
			curl_close($ch);
			return;
		}

		curl_close($ch);

		if (!$html) {
			echo "获取页面失败：未返回内容\n";
			return;
		}

		// 使用正则表达式匹配所有 /xxxxxxx（8位16进制 ID）链接
		preg_match_all('/\/([0-9a-f]{8})/', $html, $matches);

		// 仅抓取前20个 ID
		for ($i = 0; $i < min(20, count($matches[1])); $i++) {
			$id = $matches[1][$i];

			// 数据库不存在则添加
			if (mysql_exist('touchgal', 'id', "$id") == 0) {
				mysqli_query($link, "INSERT INTO `touchgal` (`id`, `status`) VALUES ('$id', '0');");
			}
		}
	}


	
	// 随机最新一个touchgal帖
	function touchgal_topic_get() {
		global $link;

		$id = get_value("touchgal", "id", "status=0");
		$base_url = "https://www.touchgal.top/$id";
		echo "执行ID：" . $base_url . "<br>";
		$post_ids = [];

		// 使用 cURL 获取页面内容
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $base_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");

		// 忽略 SSL 证书验证（仅用于调试，生产环境请避免使用）
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		
		// 执行请求并获取结果
		$html = curl_exec($ch);

		// 检查是否请求成功
		if(curl_errno($ch)) {
			// 输出 cURL 错误信息
			echo "cURL 错误: " . curl_error($ch) . "\n";
			curl_close($ch);
			return;
		}

		// 检查HTTP状态码
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($http_code != 200) {
			echo "HTTP 错误: 状态码 $http_code\n";
			curl_close($ch);
			return;
		}

		curl_close($ch);

		if (!$html) {
			echo "获取页面失败：未返回内容\n";
			return;
		}

		// 标题
		try {
			$title = str_select($html, "<h1", "</h1>");
			$title = explode(">", $title)[1];
		} catch (\Throwable $th) {
			echo "标题获取失败";
			throw $th;
		}

		// 封面
		try {
			$cover = str_select($html, "object-cover", ">");
			$cover = str_select($cover, "src=\"", "\"");
		} catch (\Throwable $th) {
			echo "封面获取失败";
			throw $th;
		}

		// 游戏介绍
		try {

			// 包含游戏截图要用不同的匹配方式
			if (strstr($html, "游戏截图")) {
				$info = str_select($html, "<h2>游戏介绍</h2>", "<h2>游戏截图</h2>");
				$info = str_replace("<p>", "", $info);
				$info = str_replace("</p>", "<br>", $info);
			} else {
				$info = str_select($html, "<h2>游戏介绍</h2>", "</div>");
				$info = str_replace("<p>", "", $info);
				$info = str_replace("</p>", "<br>", $info);
			}

		} catch (\Throwable $th) {
			echo "游戏介绍获取失败";
			throw $th;
		}

		// 游戏截图
		try {
			if (strstr($html, "<h2>游戏截图</h2>")) {
				$imgs = str_select($html, "<h2>游戏截图</h2>", "</div>");
				preg_match_all('/https?:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(?:\/[^\s"]*)?\.avif/', $imgs, $matches);
				$imgs = $matches[0];
			}

			// 游戏截图转换成帖子code
			if (isset($imgs)) {
				$imgs_code = [];
				for ($i=0; $i < count($imgs); $i++) { 
					$url = $imgs[$i];
					$imgs_code[$i] = "{_img$url}";
				}
				$imgs_code = implode("", $imgs_code);
				$imgs_code = "{_img$cover}" . $imgs_code;
			} else {
				$imgs_code = "{_img$cover}";
			}

		} catch (\Throwable $th) {
			echo "游戏截图获取失败";
			throw $th;
		}

		// OP
		try {
			if (strstr($html, "<h2>PV鉴赏</h2>")) {
				$op = str_select($html, "<h2>PV鉴赏</h2>", "</div>");
				$op = str_select($op, "src=\"", "\"");
				$op_code = "{_video$op}";
			} else {
				$op_code  = "";
			}
		} catch (\Throwable $th) {
			echo "OP爬取失败，失败原因：$th";
		}

		// 开发
		try {
			$development = str_select($html, "所属会社", "</span>");
			$development = str_select($development, "span", "+");
			$development = str_select($development, ">", "<");
			$development = "{?pre开发：" . $development . "?}";
		} catch (\Throwable $th) {
			echo "开发商获取失败";
			throw $th;
		}

		// 发售日
		try {
			if (strstr($html, "发售时间")) {
				$date = str_select($html, "发售时间: ", "</span>");
				$date = explode(">", $date)[1];
			} else {
				$date = get_time();
			}
			if ($date == "unknown") {
				$date = get_time();
			}
			$timestamp = strtotime($date);
		} catch (\Throwable $th) {
			echo "发售日获取失败";
			throw $th;
		}


		// 游戏别名
		try {
			if (strstr($html, "游戏别名")) {
				$else = str_select($html, "游戏别名", "</ul>");
				preg_match_all('/<li>(.*?)<\/li>/s', $else, $matches);
				$else = $matches[1];
				// 转成帖子格式
				$else = implode(" / ", $else);
				$else = "{?pre" . $else . "?}";
			} else {
				$else = "";
			}
		} catch (\Throwable $th) {
			echo "游戏别名获取失败";
			throw $th;
		}

		// 合并最终结构
		$download = "{?pre下载链接：<a href='$base_url' target='_blank'>$base_url</a>?}";

		// 帖子内容整合
		$content = $else . $development . $info . $op_code . $imgs_code . $download;
		$content = str_replace("\n", '', $content);
		// $content = strip_tags($content);
		$content = mysqli_real_escape_string($link, $content);

		$tid = get_value("sys_auto_increment_value", "value", "variable='tid'");
		$tid_last_char = substr($tid, -1);

		mysqli_query($link, "INSERT INTO `topics_3-1_$tid_last_char` (`tid`, `title`, `content`, `uid`, `date`, `tags`, `preview`, `view_count`, `reply_count`) VALUES ('$tid', '$title', '$content', '73', '$date', '12', '', '0', '0');");

		// 记录索引
		mysqli_query($link, "INSERT INTO `topics_index` (`fid`, `tid`, `last_modify`, `score`) VALUES ('3-1', '$tid', '$timestamp', NULL);");

		// tid + 1
		mysqli_query($link, "UPDATE sys_auto_increment_value SET value = value + 1 WHERE variable='tid'");

		// 更新数据库
		mysqli_query($link, "UPDATE touchgal SET status=1  WHERE id='$id'");
	}



	// 
	// 月慕最新资讯
	// 
	function ym_get () {
		global $link;

		$base_url = "https://www.ymgal.games/search?type=article&keyword=&sort=time&category=%E8%B5%84%E8%AE%AF&page=1";

		// 使用 cURL 获取页面内容
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $base_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");

		// 忽略 SSL 证书验证（仅用于调试，生产环境请避免使用）
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		
		// 执行请求并获取结果
		$html = curl_exec($ch);

		// 获取大致范围
		$content = str_select($html, "article-result-list", "pager-box");

		// 细分每个标题
		$topics = explode("ui item", $content);

		// 去掉第一个
		array_shift($topics);

		// 清空数据表
		mysqli_query($link, "TRUNCATE `ymgal`");

		// 清空月慕图片缓存图片
		delete_folder(__DIR__."/data/html/ym", FALSE);

		foreach ($topics as $topic) {
			// 获取标题
			$title = explode("header", $topic)[1];
			$title = explode("</a>", $title)[0];
			$title = explode(">", $title)[1];

			// 获取直链
			$src = explode("header", $topic)[1];
			$src = explode(">", $src)[0];
			$src = explode("href=", $src)[1];
			$src = str_replace('"', "", $src);
			$src = "https://www.ymgal.games" . $src;

			// 获取封面
			$img_src = explode("image", $topic)[1];
			$img_src = explode("alt", $img_src)[0];
			$img_src = explode("src=", $img_src)[1];
			$img_src = str_replace('"', "", $img_src);

			$img = file_get_contents($img_src);
			$file_name = md5($title);
			file_put_contents("./data/html/ym/$file_name.webp", $img);

			// 添加内容
			mysqli_query($link, "INSERT INTO `ymgal` (`title`, `preview`, `src`) VALUES (\"$title\", '/data/html/ym/$file_name.webp', '$src'); ");
		}
	}
