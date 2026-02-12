<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta name='description' content='GALBase论坛 - Galgame资源站点'>
	<title>发帖 / 修改帖子</title>
	<!-- css -->
	<link rel="stylesheet" href="/css/header.css">
	<link rel="stylesheet" href="/css/style.css">
	<link rel="stylesheet" href="/css/send_topic.css">

</head>
<body>
	<!-- 引入顶部导航栏 -->
	<?php require_once dirname(__FILE__).'/header.php'; ?>
	<?php
		// 未登录处理
		if (!$uid) {
			exit("<script>alert('当前页面需要登录才能查看内容')</script>");
		}
	?>

	<?php
		// 新发帖
		if ($_GET['mod'] == 'add') {
			$fid = $_GET['fid'] ?? "";
			$cid = md5("$uid");
		}

		// 修改贴需要的数据请求
		if ($_GET['mod'] == 'replace') {
			// 找到tid对应的fid
			$tid = $_GET['tid'];
			$cid = $tid;
			$fid = mysqli_query($link, "SELECT fid FROM topics_index WHERE tid=$tid;");
			$fid = $fid->fetch_assoc()['fid'];

			// 根据fid和tid最后一位分表查询找到帖子作者的uid
			$tid_last_char = substr($tid, -1);
			$data = mysqli_query($link, "SELECT * FROM `topics_{$fid}_{$tid_last_char}` WHERE tid=$tid;");
			$data = $data->fetch_assoc();

			// 对tags进行格式化
			$data['tags'] = format_tags_to_str($tid);

			// 如果作者uid不等于前端uid，无权限修改
			if ($data['uid'] != $uid && !administrator($uid)) {
				exit("<script>alert('当前tid对应帖子的作者不是你，无更改权限')</script>");
			}
		} else {
			$data['title'] = "";
			$data['content'] = "";
			$data['tags'] = "";
			$data['preview'] = "";
		}
	?>



	<br>
	<br>
	<?php require_once dirname(__FILE__)."/navigation_bar.php"; ?>



	<div class="board main_board else">
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<header>
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content">
					<?php
						if ($_GET['mod'] == "add") {
							echo title_format("发布新帖");
						}
						if ($_GET['mod'] == "replace") {
							echo title_format("修改帖子");
						}
					?>
				</ul>
				<img src="/data/imgs/title_end.png" class="title_end">
				<div class="buttons_">
					<button onclick="send_topic()">发帖</button>
					<button onclick="topic_guide()">发帖指南</button>
				</div>
			</header>
			<main>
				<div class="input_line">
					<input type="text" id="topic_title" placeholder="帖子标题(必填)" value="<?php echo $data['title']; ?>">
					<select id="target_fid">
						<option selected value="<?php echo $fid; ?>">
							<?php
								if ($_GET['mod'] == "replace") {
									echo get_board_name($fid);
								} else {
									echo "发帖至目标版块";
								}
							?>	
						</option>
						<option value="1-1">GAL资源&emsp;- <?php echo get_board_name("1-1"); ?></option>
						<option value="1-2">资源合集&emsp;- <?php echo get_board_name("1-2"); ?></option>
						<option value="1-3">玩后感&emsp;&emsp;- <?php echo get_board_name("1-3"); ?></option>
						<option value="1-4">动漫&emsp;&emsp;&emsp;- <?php echo get_board_name("1-4"); ?></option>
						<option value="2-1">日常交流&emsp;- <?php echo get_board_name("2-1"); ?></option>
						<option value="2-2">小说攻略&emsp;- <?php echo get_board_name("2-2"); ?></option>
						<option value="2-3">重要帖子&emsp;- <?php echo get_board_name("2-3"); ?></option>
						<option value="2-4">音乐&emsp;&emsp;&emsp;- <?php echo get_board_name("2-4"); ?></option>
						<option value="3-1">未开通&emsp;&emsp;- <?php echo get_board_name("3-1"); ?></option>
						<option value="3-2">汉化组&emsp;&emsp;- <?php echo get_board_name("3-2"); ?></option>
					</select>
					<input type="text" id="tags" placeholder="标签，用|号隔开(可不填)" value="<?php echo $data['tags']; ?>">
					<input type="text" id="cover" placeholder="封面图片aid，用|号隔开(可不填)" value="<?php echo $data['preview']; ?>">
				</div><br>


				<div class="function_line">
					<div onclick="open_upload_imgs()"><img src="/data/imgs/img.png" loading="lazy" alt="图片加载失败"><br><span>图片上传</span></div>
					<div onclick="open_upload_videos()"><img src="/data/imgs/video.png" loading="lazy" alt="图片加载失败"><br><span>视频上传</span></div>
					<div onclick="alert('站长目前不知道这个功能能干嘛，暂不开发咯QWQ')"><img src="/data/imgs/file.png" loading="lazy" alt="图片加载失败"><br><span>附件上传</span></div>
					<div onclick="alert('表情')"><img src="/data/imgs/emoji.png" loading="lazy" alt="图片加载失败"><br><span>表情</span></div>
					<div onclick="insert_text('<br>')"><img src="/data/imgs/switch_line.png" loading="lazy" alt="图片加载失败"><br><span>换行</span></div>
					<div onclick="insert_text('[_d ]')"><img src="/data/imgs/img_defense.png" loading="lazy" title="格式：[_d {_i1}{_i2}{_i3}]" alt="图片加载失败"><br><span>防剧透</span></div>
					<div onclick="insert_text('{?pre\n\n?}')"><img src="/data/imgs/pre.png" loading="lazy" title="格式：{?pre *?}" alt="图片加载失败"><br><span>内容突出</span></div>
					<div onclick="open_table_guide()"><img src="/data/imgs/table.png" loading="lazy" alt="图片加载失败"><br><span>表格</span></div>
					<div onclick="insert_text('{_goto}')"><img src="/data/imgs/goto.png" loading="lazy" title="格式：{_goto1}" alt="图片加载失败"><br><span>站内跳转</span></div>
					<div onclick="insert_text('{?info\n开发:\n流程:\n发行日期:\n适合游玩季节:\nlogo:\nopening:\n?}\n\n{?text\n「」「」\n?}')"><img src="/data/imgs/ai.png" loading="lazy" alt="图片加载失败"><br><span>资源收入</span></div>
					<div onclick="open_music_gui()"><img src="/data/imgs/music.png" loading="lazy" alt="插入音乐"><br><span>插入音乐</span></div>
					<div onclick="open_video_gui()"><img src="/data/imgs/video.png" loading="lazy" alt="插入视频"><br><span>插入视频</span></div>
					<div onclick="insert_subtitle()"><img src="/data/imgs/sub_title.png" loading="lazy" alt="插入子标题"><br><span>子标题</span></div>
				</div>

				<textarea id="topic_content" placeholder="帖子内容"><?php echo $data['content']; ?></textarea>

				<div class="bottom_function">
					<label class="checkbox" title="使用HTML元素后，所有的快捷标签将失效！">
						<input type="checkbox" id="only-HTML" onclick="onlyHTML()">
						<span class="checkmark"></span>
						<span>仅使用HTML</span>
					</label>
				</div>
			</main>
		</div>
	</div>



	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>

	<!-- 加载需要的js -->
	<?php require_once dirname(__FILE__)."/js/send_topic.php"; ?>






	<!-- 引入底部模块 -->
	<?php require_once dirname(__FILE__).'/footer.php'; ?>



