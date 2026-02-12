<script src="/js/xhr.js"></script>
<script src="/js/get_cookie.js"></script>

<!-- 音乐源 -->
<audio src="" id="bgm"></audio>

<!-- 音乐播放器 -->
<div class="play_box">
	<!-- 音乐播放器背景 -->
	<div class="bc">
		<img src="<?php echo $bg_path; ?>" alt="播放器封面">
	</div>

	<!-- 播放组件 -->
	<div class="state">
		<img class="cover" src="/data/imgs/pannya_icon.png" alt="播放器封面" style="margin: -2px;">
		<!-- <img class="cover" src="/data/imgs/pannya_icon.png" alt="播放器封面"> -->
		<div class="state_set iconfont icon-bofang"></div>
	</div>

	<!-- 功能组件 -->
	<div class="functions">
		<div class="last iconfont icon-shangyishou"></div>
		<div class="next iconfont icon-xiayishou"></div>
	</div>

	<!-- 进度条组件 -->
	<div class="progress">
		<div class="music_info">小憩一会~听首音乐吧~</div>

		<div class="line">
			<div class="pros">
				<div class="Anchor"></div>
			</div>
		</div>

		<div class="time_show">00:00 / 00:00</div>
	</div>

	<!-- 音量组件 -->
	<div class="tool">
		<div class="volume iconfont icon-volumeMiddle">
			<div class="volume_range">
				<input type="range" class="range">
			</div>
		</div>
	</div>

	<!-- 音乐信息组件 -->
	
</div>