<?php
	$tid = $_GET['tid'];
	setcookie('last_view', "$tid", time() + 600, '/');
?>

<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta name='description' content='GALBase论坛 - Galgame资源站点'>
	<link rel="stylesheet" href="/css/header.css">
	<link rel="stylesheet" href="/css/style.css">
	<link rel="stylesheet" href="/css/view_topic.css">
</head>
<body>
	<?php
		// 引入顶部导航栏
		require_once dirname(__FILE__).'/header.php';
	?>

	<?php
		$tid_last_char = substr($tid, -1);

		// 若tid是负数，代表旧站帖子，提示警告信息
		if ($tid < 0) {
			echo "<script>alert('你当前浏览的帖子为旧站迁移至新站的保留贴，帖内的信息已经过期且不再更新，请在新站搜索相关标题找到新帖')</script>";
		}

		// 找到tid对应的fid
		$result = mysqli_query($link, "SELECT fid, last_modify FROM topics_index WHERE tid={$tid} LIMIT 1;")->fetch_assoc();
		$fid = $result['fid'];
		date_default_timezone_set('Asia/Shanghai');
		$last_modify = date('Y-m-d', $result['last_modify']);

		// tid找fid无效
		if (!$fid) {
			exit("<script>alert('当前tid无对应的帖子内容')</script>");

		// tid找fid有效，帖子浏览量 + 1
		} else {

			// 查看cookie中上个浏览的帖子
			if (isset($_COOKIE['last_view']) && $_COOKIE['last_view'] != $tid) {
				mysqli_query($link, "UPDATE `topics_{$fid}_{$tid_last_char}` SET view_count = view_count + 1 WHERE tid=$tid");
			}
		}



		// 根据fid和tid查表找帖子数据
		$data = mysqli_query($link, "SELECT * FROM `topics_{$fid}_{$tid_last_char}`  WHERE tid={$tid} LIMIT 1;");
		$data = $data->fetch_assoc();

		$data['chunk'] = chunk($data['tid'], "", TRUE);

		// 对data里的tags进行格式化
		$data['tags'] = format_tags_to_str($tid);

		$uid_ = $data['uid'];
		$uid_last_char_ = substr($uid_, -1);

		// 获取uid对应的用户数据
		$auther = mysqli_query($link, "SELECT * FROM users_data_{$uid_last_char_} WHERE uid=$uid_ LIMIT 1");
		$auther = $auther->fetch_assoc();

		// 获取uid对应的用户名uname_
		$uname_ = mysqli_query($link, "SELECT uname FROM users_info_{$uid_last_char_} WHERE uid={$uid_} LIMIT 1");
		$auther['uname'] = $uname_->fetch_assoc()['uname'];

		// 格式化回单引号
		if (strstr($data['title'], "&apos;")) {
			$data['title'] = str_replace("&apos;", "'", $data['title']);
		}

		// 日志记录
		if ($uid) {
			log_add($uid, "\$user 访问了帖子 \$title", $tid);
		} else {
			$finger = $_COOKIE['finger'];

			// 部分用户规避指纹，记录IP为指纹
			if (!$finger) {
				$finger = $_SERVER['REMOTE_ADDR'];
			}
			
			log_add(0, "游客($finger) 访问了帖子 \$title", $tid);
		}
	?>

	<br>
	<br>
	

	<div class="board main_board" style="width:97.5%">
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<header class="function">
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format("TITLE"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">
				<span class="title"><?php echo $data['title']; ?></span>
			</header>
		</div>
	</div>
	<br>
	<br>

	<?php require_once dirname(__FILE__)."/navigation_bar.php"; ?>

	<div class="board main_board dynamic_height">
		<div class="board_2nd">
			<header style="display: flex;">
				<span class="topic_info">浏览数: <?php echo $data['view_count']; ?>  回复数: <?php echo $data['reply_count']; ?> 发布于: <?php echo $data['date']; ?>（<span id="time_diff"></span>） <?php
					if ($data['date'] != $last_modify) {
						echo "最后更新: " . $last_modify . "（" . time_diff($last_modify) . "）";
					}
				?></span>

				<div id="tags"></div>

				<div class="buttons_">
					<div class="select_warp">
						<div class="select_main" onclick="toggleSelect()">
							<span><img src="/data/imgs/function.png" alt="帖子操作" loading="lazy">帖子操作</span>
						</div>
						
						<div class="options" id="options">
							<div class="option" onclick="view_topic_format()">
								<img src="/data/imgs/structure.png" alt="查看本贴结构" loading="lazy">
								<span>查看本贴结构</span>
							</div>

							<div class="option" onclick="window.location.href = '/send_topic.php?mod=replace&tid=<?php echo $data['tid'];?>'">
								<img src="/data/imgs/note.png" alt="修改帖子" loading="lazy">
								<span>修改帖子</span>
							</div>

							<div class="option" onclick="judment()">
								<img src="/data/imgs/judment.png" alt="风纪执行" loading="lazy">
								<span>风纪执行</span>
							</div>

							<div class="option" id="collection_button" onclick="collection()">
								<img src="/data/imgs/collection.png" alt="收藏本贴 / 取消收藏" loading="lazy">
								<span><?php
										if (mysql_exist("collection_$uid_last_char_", "uid", $uid, "AND tid=$tid") == 1) {
											echo "取消收藏";
										} else {
											echo "收藏本贴";
										}
								?></span>
							</div>
						</div>
					</div>


				</div>
			</header>
			<main id="content">
				<div class="bottom"></div>
			</main>
		</div>
	</div>

	<div class="board wide_board" id="reply">
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<header class="function">
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format("回复"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">
			</header>
			<main>
				<img src="/data/imgs/file.png" onclick="alert('附件上传未实装')" loading="lazy" alt="附件上传" title="附件上传">
				<img src="/data/imgs/emoji.png" onclick="alert('表情未实装')" loading="lazy" alt="表情" title="表情">
				<textarea class="reply_content" placeholder="请输入你想回复的内容！请不要使用英文双引号！"></textarea>
				<button onclick="reply()">参与回复</button>
			</main>
		</div>
	</div>

	<div class="board wide_board" id="developer_works" hidden>
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<header class="function">
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format("该会社所有作品"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">
			</header>
			<main></main>
		</div>
	</div>

	<div class="board wide_board" id="reply_region" hidden>
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<header class="function">
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format("历史回复"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">
			</header>
			<main></main>
		</div>
	</div>


	<!-- 连接js -->
	<?php require_once dirname(__FILE__)."/js/view_topic.php"; ?>
	<?php require_once dirname(__FILE__)."/footer.php" ?>