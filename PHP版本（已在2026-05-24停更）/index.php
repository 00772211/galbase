<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta name='description' content='GALBase论坛 - Galgame资源站点'>
	<title>GALBase论坛 - Galgame资源站点</title>
	<!-- css -->
	<link rel="stylesheet" href="/css/header.css">
	<link rel="stylesheet" href="/css/style.css">
	<link rel="stylesheet" href="/css/home_page.css">
	<style>
		#fps {
			position: sticky;
			left: 0;
			bottom: 0;
			background-color:aqua;
		}
	</style>
</head>
<body>
	<?php
		// 引入顶部导航栏
		require_once dirname(__FILE__).'/header.php';
	?>


	<br>
	<br>

	<div class="board op_board">
		<img src="/data/imgs/telescope_top.png" class="telescope_top">
		<img src="/data/imgs/telescope_bottom.png" class="telescope_bottom">
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<img src="/data/imgs/star_orbit.png" class="star_orbit">
			<img src="/data/imgs/light_star.png" class="light_star">
			<header>
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format("OPENING"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">
			</header>
			<main>
				<div class="home_page_op">
					<video src="
						<?php
							// 从vid索引表随机取一个vid和tid
							$result = mysqli_query($link, "SELECT vid, tid FROM vids_index WHERE no_push IS NULL ORDER BY RAND() LIMIT 1")->fetch_assoc();
							$vid = $result['vid'];
							$tid = $result['tid'];

							// 从tid中获取帖子内容
							$data = get_topic($tid);
							$preview = $data['preview'];
							$title = $data['title'];
							$chunk = chunk($tid, "", TRUE);

							echo "$chunk/$tid/$vid.mp4";
						?>
					" poster="<?php echo "$chunk/$tid/$preview.avif"; ?>" frameborder="no" framespacing="0" scrolling="no" allow="autoplay; encrypted-media" allowfullscreen="true" preload="auto" controls controlsList="nodownload"></video>
					<span class="title"><a href="/topic/<?php echo $tid; ?>" target="_blank"><?php echo $title; ?></a></span>
				</div>

				<div class="newest_topic">
					<div class="card">
						<div class="auther">
							<img src="/data/imgs/title_start.png" class="avatar" alt="图片加载失败">
							<span class="uname">最近更新</span>
						</div>
					</div>
				</div>
			</main>

			<div class="recommend_gal_list"></div>
		</div>
	</div>






	<br>
	<br>
	<br>
	<br>


	<?php require_once dirname(__FILE__)."/navigation_bar.php"; ?>





	<div class="board main_board">
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<header>
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format("学园集聚之地"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">
				<div class="sign">
					<img class="star" src="/data/imgs/star.png" alt="图片加载失败">
					<p>然后是—— 这片星空。我所拥有的就是这些。</p>
				</div>
			</header>
			<main>
				<?php $fid = "1-1"; ?>
				<div class="section_board">
					<img src="/data/imgs/board/<?php echo $fid; ?>.png" alt="">
					<div class="line title">
						<a href="/forum/<?php echo $fid; ?>/0" target="_blank"><?php echo get_board_name($fid); ?></a>
						<span class="topic_number">收入数: <?php echo get_topics_count($fid) ?></span></div>
					<?php $data = get_newest_topic($fid); ?>
					<div class="line">&ensp;<a href="/topic/<?php echo $data['tid']; ?>" style="text-decoration: underline;" target="_blank"><?php echo $data['title']; ?></a></div>
					<div class="line">
						<img src="/data/imgs/people.png" style="height: 10px; width: 10px; margin: 0 2px 0 2px" alt="图片加载失败">
						<a href="/space/<?php echo $data['uid']; ?>" target="_blank"><?php echo $data['auther']; ?></a>
						<img src="/data/imgs/date.png" style="height: 10px; width: 10px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['date']; ?>
						<img src="/data/imgs/view.png" style="height: 10px; width: 16px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['view_count']; ?>
						<img src="/data/imgs/message.png" style="height: 11px; width: 13px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['reply_count']; ?>
					</div>
				</div>

				<?php $fid = "1-2"; ?>
				<div class="section_board">
					<img src="/data/imgs/board/<?php echo $fid; ?>.png" alt="">
					<div class="line title">
						<a href="/forum/<?php echo $fid; ?>/0" target="_blank"><?php echo get_board_name($fid); ?></a>
						<span class="topic_number">合集数: <?php echo get_topics_count($fid) ?></span></div>
					<?php $data = get_newest_topic($fid); ?>
					<div class="line">&ensp;<a href="/topic/<?php echo $data['tid']; ?>" style="text-decoration: underline;" target="_blank"><?php echo $data['title']; ?></a></div>
					<div class="line">
						<img src="/data/imgs/people.png" style="height: 10px; width: 10px; margin: 0 2px 0 2px" alt="图片加载失败">
						<a href="/space/<?php echo $data['uid']; ?>" target="_blank"><?php echo $data['auther']; ?></a>
						<img src="/data/imgs/date.png" style="height: 10px; width: 10px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['date']; ?>
						<img src="/data/imgs/view.png" style="height: 10px; width: 16px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['view_count']; ?>
						<img src="/data/imgs/message.png" style="height: 11px; width: 13px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['reply_count']; ?>
					</div>
				</div>

				<?php $fid = "1-3"; ?>
				<div class="section_board">
					<img src="/data/imgs/board/<?php echo $fid; ?>.png" alt="">
					<div class="line title">
						<a href="/forum/<?php echo $fid; ?>/0" target="_blank"><?php echo get_board_name($fid); ?></a>
						<span class="topic_number">有感数: <?php echo get_topics_count($fid) ?></span></div>
					<?php $data = get_newest_topic($fid); ?>
					<div class="line">&ensp;<a href="/topic/<?php echo $data['tid']; ?>" style="text-decoration: underline;" target="_blank"><?php echo $data['title']; ?></a></div>
					<div class="line">
						<img src="/data/imgs/people.png" style="height: 10px; width: 10px; margin: 0 2px 0 2px" alt="图片加载失败">
						<a href="/space/<?php echo $data['uid']; ?>" target="_blank"><?php echo $data['auther']; ?></a>
						<img src="/data/imgs/date.png" style="height: 10px; width: 10px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['date']; ?>
						<img src="/data/imgs/view.png" style="height: 10px; width: 16px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['view_count']; ?>
						<img src="/data/imgs/message.png" style="height: 11px; width: 13px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['reply_count']; ?>
					</div>
				</div>

				<?php $fid = "1-4"; ?>
				<div class="section_board">
					<img src="/data/imgs/board/<?php echo $fid; ?>.png" alt="">
					<div class="line title">
						<a href="/forum/<?php echo $fid; ?>/0" target="_blank"><?php echo get_board_name($fid); ?></a>
						<span class="topic_number">番剧数: <?php echo get_topics_count($fid) ?></span></div>
					<?php $data = get_newest_topic($fid); ?>
					<div class="line">&ensp;<a href="/topic/<?php echo $data['tid']; ?>" style="text-decoration: underline;" target="_blank"><?php echo $data['title']; ?></a></div>
					<div class="line">
						<img src="/data/imgs/people.png" style="height: 10px; width: 10px; margin: 0 2px 0 2px" alt="图片加载失败">
						<a href="/space/<?php echo $data['uid']; ?>" target="_blank"><?php echo $data['auther']; ?></a>
						<img src="/data/imgs/date.png" style="height: 10px; width: 10px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['date']; ?>
						<img src="/data/imgs/view.png" style="height: 10px; width: 16px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['view_count']; ?>
						<img src="/data/imgs/message.png" style="height: 11px; width: 13px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['reply_count']; ?>
					</div>
				</div>
			</main>
		</div>
	</div>


	<div class="board main_board" style="margin-top: 15px;">
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<header>
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format("学园文学茶馆"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">
				<div class="sign">
					<img class="star" src="/data/imgs/star.png" alt="图片加载失败">
					<p>纵使时间，抹杀了太多想要保留的初体验。</p>
				</div>
			</header>
			<main>

				<?php $fid = "2-1"; ?>
				<div class="section_board">
					<img src="/data/imgs/board/<?php echo $fid; ?>.gif" alt="">
					<div class="line title">
						<a href="/forum/<?php echo $fid; ?>/0" target="_blank"><?php echo get_board_name($fid); ?></a>
						<span class="topic_number">闲聊数: <?php echo get_topics_count($fid) ?></span></div>
					<?php $data = get_newest_topic($fid); ?>
					<div class="line">&ensp;<a href="/topic/<?php echo $data['tid']; ?>" style="text-decoration: underline;" target="_blank"><?php echo $data['title']; ?></a></div>
					<div class="line">
						<img src="/data/imgs/people.png" style="height: 10px; width: 10px; margin: 0 2px 0 2px" alt="图片加载失败">
						<a href="/space/<?php echo $data['uid']; ?>" target="_blank"><?php echo $data['auther']; ?></a>
						<img src="/data/imgs/date.png" style="height: 10px; width: 10px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['date']; ?>
						<img src="/data/imgs/view.png" style="height: 10px; width: 16px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['view_count']; ?>
						<img src="/data/imgs/message.png" style="height: 11px; width: 13px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['reply_count']; ?>
					</div>
				</div>

				<?php $fid = "2-2"; ?>
				<div class="section_board">
					<img src="/data/imgs/board/<?php echo $fid; ?>.gif" alt="">
					<div class="line title">
						<a href="/forum/<?php echo $fid; ?>/0" target="_blank"><?php echo get_board_name($fid); ?></a>
						<span class="topic_number">图书数: <?php echo get_topics_count($fid) ?></span></div>
					<?php $data = get_newest_topic($fid); ?>
					<div class="line">&ensp;<a href="/topic/<?php echo $data['tid']; ?>" style="text-decoration: underline;" target="_blank"><?php echo $data['title']; ?></a></div>
					<div class="line">
						<img src="/data/imgs/people.png" style="height: 10px; width: 10px; margin: 0 2px 0 2px" alt="图片加载失败">
						<a href="/space/<?php echo $data['uid']; ?>" target="_blank"><?php echo $data['auther']; ?></a>
						<img src="/data/imgs/date.png" style="height: 10px; width: 10px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['date']; ?>
						<img src="/data/imgs/view.png" style="height: 10px; width: 16px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['view_count']; ?>
						<img src="/data/imgs/message.png" style="height: 11px; width: 13px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['reply_count']; ?>
					</div>
				</div>

				<?php $fid = "2-3"; ?>
				<div class="section_board">
					<img src="/data/imgs/board/<?php echo $fid; ?>.gif" alt="">
					<div class="line title">
						<a href="/forum/<?php echo $fid; ?>/0" target="_blank"><?php echo get_board_name($fid); ?></a>
						<span class="topic_number">咖啡数: <?php echo get_topics_count($fid) ?></span></div>
					<?php $data = get_newest_topic($fid); ?>
					<div class="line">&ensp;<a href="/topic/<?php echo $data['tid']; ?>" style="text-decoration: underline;" target="_blank"><?php echo $data['title']; ?></a></div>
					<div class="line">
						<img src="/data/imgs/people.png" style="height: 10px; width: 10px; margin: 0 2px 0 2px" alt="图片加载失败">
						<a href="/space/<?php echo $data['uid']; ?>" target="_blank"><?php echo $data['auther']; ?></a>
						<img src="/data/imgs/date.png" style="height: 10px; width: 10px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['date']; ?>
						<img src="/data/imgs/view.png" style="height: 10px; width: 16px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['view_count']; ?>
						<img src="/data/imgs/message.png" style="height: 11px; width: 13px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['reply_count']; ?>
					</div>
				</div>

				<?php $fid = "2-4"; ?>
				<div class="section_board">
					<img src="/data/imgs/board/<?php echo $fid; ?>.gif" alt="">
					<div class="line title">
						<a href="/forum/<?php echo $fid; ?>/0" target="_blank"><?php echo get_board_name($fid); ?></a>
						<span class="topic_number">活动数: <?php echo get_topics_count($fid) ?></span></div>
					<?php $data = get_newest_topic($fid); ?>
					<div class="line">&ensp;<a href="/topic/<?php echo $data['tid']; ?>" style="text-decoration: underline;" target="_blank"><?php echo $data['title']; ?></a></div>
					<div class="line">
						<img src="/data/imgs/people.png" style="height: 10px; width: 10px; margin: 0 2px 0 2px" alt="图片加载失败">
						<a href="/space/<?php echo $data['uid']; ?>" target="_blank"><?php echo $data['auther']; ?></a>
						<img src="/data/imgs/date.png" style="height: 10px; width: 10px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['date']; ?>
						<img src="/data/imgs/view.png" style="height: 10px; width: 16px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['view_count']; ?>
						<img src="/data/imgs/message.png" style="height: 11px; width: 13px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['reply_count']; ?>
					</div>
				</div>
			</main>
		</div>
	</div>



	<div class="board main_board" style="margin-top: 15px;">
		<img src="/data/imgs/telescope_top.png" class="telescope_top">
		<img src="/data/imgs/telescope_bottom.png" class="telescope_bottom">
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<img src="/data/imgs/star_orbit.png" class="star_orbit">
			<img src="/data/imgs/light_star.png" class="light_star">
			<header>
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format("璀璨群星之上"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">
				<div class="sign">
					<img class="star" src="/data/imgs/star.png" alt="图片加载失败">
					<p>浪漫夜空下最闪亮的除了星星，还有年轻的模样。</p>
				</div>
			</header>
			<main style="width: 100%;">

				<?php $fid = "3-1"; ?>
				<div class="section_board">
					<img src="/data/imgs/board/<?php echo $fid; ?>.png" alt="">
					<div class="line title">
						<a href="/forum/<?php echo $fid; ?>/0" target="_blank"><?php echo get_board_name($fid); ?></a>
						<span class="topic_number">星辰数: <?php echo get_topics_count($fid) ?></span></div>
					<?php $data = get_newest_topic($fid); ?>
					<div class="line">&ensp;<a href="/topic/<?php echo $data['tid']; ?>" style="text-decoration: underline;" target="_blank"><?php echo $data['title']; ?></a></div>
					<div class="line">
						<img src="/data/imgs/people.png" style="height: 10px; width: 10px; margin: 0 2px 0 2px" alt="图片加载失败">
						<a href="/space/<?php echo $data['uid']; ?>" target="_blank"><?php echo $data['auther']; ?></a>
						<img src="/data/imgs/date.png" style="height: 10px; width: 10px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['date']; ?>
						<img src="/data/imgs/view.png" style="height: 10px; width: 16px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['view_count']; ?>
						<img src="/data/imgs/message.png" style="height: 11px; width: 13px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['reply_count']; ?>
					</div>
				</div>

				<?php $fid = "3-2"; ?>
				<div class="section_board">
					<img src="/data/imgs/board/<?php echo $fid; ?>.png" alt="">
					<div class="line title">
						<a href="/forum/<?php echo $fid; ?>/0" target="_blank"><?php echo get_board_name($fid); ?></a>
						<span class="topic_number">存档数: <?php echo get_topics_count($fid) ?></span></div>
					<?php $data = get_newest_topic($fid); ?>
					<div class="line">&ensp;<a href="/topic/<?php echo $data['tid']; ?>" style="text-decoration: underline;" target="_blank"><?php echo $data['title']; ?></a></div>
					<div class="line">
						<img src="/data/imgs/people.png" style="height: 10px; width: 10px; margin: 0 2px 0 2px" alt="图片加载失败">
						<a href="/space/<?php echo $data['uid']; ?>" target="_blank"><?php echo $data['auther']; ?></a>
						<img src="/data/imgs/date.png" style="height: 10px; width: 10px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['date']; ?>
						<img src="/data/imgs/view.png" style="height: 10px; width: 16px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['view_count']; ?>
						<img src="/data/imgs/message.png" style="height: 11px; width: 13px; margin: 0 2px 0 20px" alt="图片加载失败"><?php echo $data['reply_count']; ?>
					</div>
				</div>
			</main>
		</div>
	</div>




	<div class="board main_board" style="margin-top: 15px;">
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<header>
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format("今日登校学生"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">
				<div class="sign">
					<img class="star" src="/data/imgs/star.png" alt="图片加载失败">
					<p>没人能阻止四季轮转，真是一件非常痛苦的事情。</p>
				</div>
			</header>
			<main id="online">
				<ul>
					<?php
						$timestamp = time();
						if ($uid) {
							echo "<li><img src='".get_avatar($uid)['small']."' alt='在线用户头像'><a href='/space/$uid' target='_blank'>{$uname}（<span style='color:#00187C'>在校</span>）</a></li>";

							// 更新在线用户，若存在则更新，不存在则新增
							if (mysql_exist("online", "uid", "$uid") == 1) {
								mysqli_query($link, "UPDATE `online` SET `last_online` = '$timestamp' WHERE uid=$uid LIMIT 1; ");
							} else {
								mysqli_query($link, "INSERT INTO `online` (`uid`, `last_online`) VALUES ('$uid', '$timestamp'); ");
							}
						}

						// 获取当前在线的所有用户
						$data = mysqli_query($link, "SELECT * FROM online ORDER BY last_online DESC");
						$today = get_time("Y-m-d");
						while ($row = $data->fetch_assoc()) {
							$target_uid = $row['uid'];
							
							// 自己不显示
							if ($uid == $target_uid) {
								continue;
							}
							// 获取上次在线时间
							$last_online = $row['last_online'];

							// 判断是否为今日
							if (date("Y-m-d", $last_online) == $today) {

								// 判断是否最近5分钟在线
								if ($timestamp - $last_online < 300) {
									$state = "<span style='color:#00187C'>在校</span>";
								} else {
									$state = "离校";
								}

								$target_uname = get_uname($target_uid);
								$avatar = get_avatar($target_uid)['small'];
								echo "<li><img src='$avatar' alt='在线用户头像'><a href='/space/$target_uid' target='_blank'>$target_uname （{$state}）</a></li>";
							}
						}
					?>
				</ul>
			</main>
		</div>
	</div>

	<br><br><br>

<!-- 连接js -->
<?php require_once dirname(__FILE__)."/js/index.php"; ?>



<?php require_once dirname(__FILE__)."/footer.php"; ?>



























