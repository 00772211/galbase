<span class="auther">
	<a href="/space/<?php echo $auther['uid']; ?>" target="_blank"><b><?php echo $auther['uname']; ?></b></a><br>
	<img class="avatar" src="<?php echo get_avatar($auther['uid'])['medium']; ?>" onclick="fullscreen_avatar(this)" loading="lazy" alt="图片加载失败">
	<p class="sign"><?php echo $auther['sign']; ?></p>
</span>

<span class="auther_data">
	<img class="pannya" src="/data/imgs/pannya.png" loading="lazy" alt="图片加载失败">
	<ul>
		<li>学生证UID: <?php echo $auther['uid']; ?></li>
		<li>在校时间: ><?php echo round($auther['online_time'] / 60) ?>小时</li>
		<li>身份: <?php echo $auther['identity']; ?></li>
		<li title="学分 = 已推完GAL数总和">学分: <?php echo $auther['credit']; ?>点</li>
		<li>学年: <?php echo $auther['academic_year']; ?></li>
		<li>奖学金: <?php echo $auther['schoolship']; ?>呜溜</li>
		<li title="风纪执行 = 删除别人帖数">风纪执行: <?php echo $auther['judment_count']; ?>次</li>
		<li title="奶酪味鸡胸肉猫罐头 = 发帖数">奶酪味鸡胸肉猫罐头: <?php echo $auther['canned_count']; ?>罐</li>
		<li>注册时间: <?php echo $auther['register_time']; ?></li>
		<li>最后登录: <?php echo $auther['last_login_time']; ?></li>
	</ul>
</span>

<span class="love_img">
	<img src="
		<?php
			if (strlen($auther['sign_img']) > 0) {
				// 修改sign_img中的格式
				// tid|aid
				$auther['sign_img'] = explode("|", $auther['sign_img']);
				$chunk = chunk($auther['sign_img'][0], "", TRUE);

				echo "$chunk/{$auther['sign_img'][0]}/{$auther['sign_img'][1]}.avif"; 

			} else {
				echo "/data/imgs/yingmei_small.jpg";
			}
		?>
	" onclick="fullscreen(this)" loading="lazy" alt="图片加载失败">
</span>

<span onclick="boards_list(this, 101)"><img src="/data/imgs/arrow.png" style="height: 10px;" alt="图片加载失败">此生挚爱</span>
<span id="boards_list_101" style="display:block;">
	<ul>
		<li>
			<?php
				// 不存在
				if (!$auther['best_love_story']) {
					echo '无';

				// 存在
				} else {
					echo $auther['best_love_story'];
				}
			?>
		</li>
	</ul>
</span>

<span onclick="boards_list(this, 102)"><img src="/data/imgs/arrow.png" style="height: 10px;" alt="图片加载失败">正在推进</span>
<span id="boards_list_102" style="display:block;">
	<ul>
		<li>
			<?php
				// 不存在
				if (!$auther['playing_story']) {
					echo '无';
				// 存在
				} else {
					echo $auther['playing_story'];
				}
			?>
		</li>
	</ul>
</span>

<span onclick="boards_list(this, 103)"><img src="/data/imgs/arrow.png" style="height: 10px;" alt="图片加载失败">强烈推荐</span>
<span id="boards_list_103" style="display:block;">
	<ul>
		<?php
			// 如果推荐集存在
			if ($auther['recommend_stories']) {
				$recommends = explode("|", $auther['recommend_stories']);

				// 循环每个推荐
				for ($i = 0; $i < count($recommends); $i++) {
					echo "<li>{$recommends[$i]}</li>";
				}
			}
		?>
	</ul>
</span>


