<script src="/js/xhr.js"></script>
<script src="/js/float_window.js"></script>
<script src="/js/lock.js"></script>




<script>
	// 
	// 
	// 创建悬浮窗
	// 
	// 
	float_window.create()



	// 
	// 
	// 更改标题
	// 
	// 
	const mod = "<?php echo $_GET['mod']; ?>"
	if (mod == "add") {
		document.title = "新发帖"
	}
	if (mod == "replace") {
		document.title = "修改发帖"
	}


	// 
	// 
	// 发帖文本域内插入内容
	// 
	// 
	function insert_text(text) {
		var textarea = document.getElementById("topic_content")
		
		// 获取光标位置
		var cursorPos = textarea.selectionStart

		// 要插入的内容
		var textToInsert = text
		var currentValue = textarea.value
		var newValue = currentValue.slice(0, cursorPos) + textToInsert + currentValue.slice(cursorPos);
		textarea.value = newValue;

		// 恢复光标位置
		textarea.selectionStart = cursorPos + textToInsert.length;
		textarea.selectionEnd = cursorPos + textToInsert.length;
		textarea.focus();
	}



	// 
	// 
	// 图片上传
	// 
	// 
	const open_upload_imgs = () => {
		// 修改title
		float_window.title("图片上传")

		// 内容体变更
		var html = `
			<div class="function">
				仅仅上传图片是无法在帖子内显示，请自行点击图片将图片代码添加进帖子内容里。<br>
				<button onclick="open_imgs_upload_windows()">图片上传</button>
				<input type="file" accept="image/jpeg, image/png, image/gif" id="imgs_upload" multiple hidden>
			</div>
			<div id="imgs"></div>
		`
		float_window.content(html)

		// 打开悬浮窗
		float_window.open()

		// dom元素获取
		const imgs_upload = document.querySelector('#imgs_upload')

		// 加载存在的图片
		load_topic_imgs("<?php echo $cid; ?>")

		// 监听图片input的变化
		imgs_upload.onchange = function () {
			const files = imgs_upload.files
			var imgs_area = document.querySelector('#imgs')

			// 遍历所有图片
			for (let n = 0; n < files.length; n++) {

				// 延迟2s
				setTimeout(function() {

					// 获取图片并压缩图片
					var file = files[n];

					// 生成每张图片专属的进度条ID，ID = 图片大小 + 时间戳
					var id = file['size'] + Date.now()

					// 添加图片到图片列表中
					let url = URL.createObjectURL(file)
					var html = `
						<div class="card" id="_${id}">
							<img src="${url}" loading="lazy" alt="图片加载失败">
							<progress value="0" max="100"></progress>
							<span class="remove" onclick="">编码中</span>
							<span class="aid"></span>
						</div>
					`
					imgs_area.insertAdjacentHTML("beforeend", html)

					// 压缩图片并上传
					img_zip(id, file);
				}, n * 2000);
			}
		}
	}



	// 
	// 
	// 加载已上传的图片
	// 
	// 
	const load_topic_imgs = (cid) => {
		var data = {
			"cmd": "load_topic_imgs",
			"cid": cid
		}
		xhr("/data/server.php", data).then((result) => {

			// 无图片
			if (!result) {
				return
			}

			var chunk = "<?php
				if (isset($_GET['tid'])) {
					echo chunk($_GET['tid'], "", TRUE); 
				} else {
					echo chunk(get_value("sys_auto_increment_value", "value", "variable='tid'"), "", TRUE);
				}			
			?>"

			// 对返回数据进行JSON解析
			var result = JSON.parse(result)
			console.log(result);

			// 判断数组长度非0
			if (result.length != 0) {
				var imgs = result

				// 循环每一张图片
				for (n = 0; n < imgs.length; n++) {
					// aid去除.jpg
					var aid = imgs[n].replace('.avif', '')

					// 前端显示图片到cmd
					var html = `
						<div class="card" id="_${aid}">
							<img src="${chunk}/${cid}/${aid}.avif" loading="lazy" alt="图片加载失败" onclick="insert_text('{_i${aid}}')">
							<progress value="100" max="100"></progress>
							<span class="remove" onclick="delete_img('${aid}')">删除</span>
							<span class="aid">aid:${aid}</span>
						</div>
					`
					document.querySelector('#imgs').insertAdjacentHTML("beforeend", html)
				}
			}
		})
	}



	// 
	// 
	// 打开隐藏input的上传窗口
	// 
	// 
	const open_imgs_upload_windows = () => {
		imgs_upload.click()
	}


	// 
	// 
	// 删除图片
	// 
	// 
	const delete_img = (aid) => {
		var data = {
			"cmd": "remove_topic_img",
			"cid": "<?php echo $cid; ?>",
			"aid": aid
		}
		xhr("/data/server.php", data).then((result) => {
			if (result) {
				document.querySelector(`#_${aid}`).innerHTML = ''
			}
		})
	}

































	




	// 
	// 
	// 视频上传
	// 
	// 
	const open_upload_videos = () => {
		// 更改悬浮窗标题
		float_window.title("视频上传")

		// 内容体变更
		var html = `
			<div class="function">
				<button onclick="open_video_upload_windows()">1-视频切片</button>
				<button onclick="chunks_upload()">2-视频上传</button>
				<button onclick="check_chunks()">3-切片检查</button>
				<button onclick="combine_chunks()">4-视频合并</button>
				<button onclick="target_chunk_upload()">5-指定上传</button>
				<span>正在加载...</span>
				<button onclick="window.open('/topic/1578', '_blank')" style="float: right; margin-right: 20px;">本站视频上传教程</button>
				<input type="file" accept="video/mp4, video/webm, video/x-msvideo" id="upload_video" hidden>
			</div>
			<div id="videos">
				<br>
			</div>
		`
		float_window.content(html)

		// 打开悬浮窗
		float_window.open()

		// xhr加载已有视频
		var data = {
			"cmd": "load_topic_videos",
			"cid": "<?php echo $cid; ?>"
		}
		xhr("/data/server.php", data).then((result) => {
			console.log(result);
			
			// 对返回数据进行JSON解析
			if (result) {
				var videos = JSON.parse(result)

				// 判断数组长度非0，非0即存在视频
				if (videos.length != 0) {

					// 循环每一个视频并添加到DOM中
					for (i = 0; i < videos.length; i++) {
						var video = videos[i]
						var vid = video.split(" | ")[0].replace('.mp4', '')
						var html = `<span class="tag" onclick="insert_text('{_v${vid}}')">${video}</span><span class="tag tag3" onclick="delete_video('${vid}')">删除${video}</span><br><br>`
						document.querySelector('#videos').insertAdjacentHTML("beforeend", html)
					}
					document.querySelector('#float_window .function span').textContent = "已加载全部视频"
				}
				
			} else {
				document.querySelector('#float_window .function span').textContent = "不存在视频"
			}
		})
	}



	// 
	// 文件选择窗口
	// 
	let chunks_info = {chunks: []}
	const open_video_upload_windows = () => {
		var video_upload = document.querySelector('#upload_video')
		video_upload.click()

		// 监听DOM改变
		video_upload.onchange = function () {
			document.querySelector(".function span").textContent = '文件正在切片中，请稍等'

			// 获取视频文件
			var file = video_upload.files[0]

			// 获取文件base64，定义切片大小，总块数计算
			chunks_info['base64'] = btoa(file)
			chunks_info['size'] = file.size
			
			const chunk_size = 1 * 1024 * 1024; // 2MB
			var all_chunks = Math.ceil(file.size / chunk_size)

			// 定义片头，定义片尾
			let start = 0
			let end = Math.min(chunk_size, file.size)

			// 循环总切片数
			for (i = 0; i < all_chunks; i++) {
				let chunk = file.slice(start, end)
				chunks_info['chunks'][i] = chunk
				console.log(chunk);

				// 下一个片段的开头等于上个片段的末尾
				start = end
						
				// 新生成一个片尾，值为end + chunk_size
				end = Math.min(end + chunk_size, file.size)
			}
			document.querySelector(".function span").textContent = '文件切片完成，可以进行下一步。'
		}
	}



	// 
	// 切片上传
	// 
	let uploaded = 0;
	const chunks_upload = async () => {
		// 未进行切片
		if (chunks_info['chunks'].length == 0) {
			document.querySelector(".function span").textContent = '没有进行切片！不能进行上传！';
			return;
		}

		// 初始进度条进度
		uploaded = 0;
		const max = chunks_info['chunks'].length;

		// 遍历所有切片，依次上传
		for (let i = 0; i < max; i++) {
			uploaded++;

			// 更新上传进度
			video_upload_process(uploaded, max);

			const data = {
				"cmd": "chunks_upload",
				"cid": "<?php echo $cid; ?>",
				"file_base64": chunks_info['base64'],
				"index": i,
				"all_chunks": max,
				"chunk": chunks_info['chunks'][i]
			};

			try {
				// 等待每个请求完成后再上传下一个
				const result = await xhr("/data/server.php", data);
				console.log(result);

				if (result == 'succ') {
					document.querySelector(".function span").textContent = '上传完成，请进行下一步！';
				}

				if (result == 'error') {
					document.querySelector(".function span").textContent = '上传失败！请重新上传！';
				}

			} catch (error) {
				console.error('上传切片出错:', error);
				// 即使出错，依然继续上传下一个切片
				document.querySelector(".function span").textContent = `上传切片 ${i} 失败，正在继续上传下一个...`;
			}
		}
	}



	// 
	// 切片检查
	// 
	const check_chunks = () => {

		// 未进行切片
		if (chunks_info['chunks'].length == 0) {
			document.querySelector(".function span").textContent = '没有进行切片！不能进行上传！'
			return
		}

		document.querySelector(".function span").textContent = '切片检查请求发送中，请耐心等待！'

		const max = chunks_info['chunks'].length

		var data = {
			"cmd": "check_chunks",
			"cid": "<?php echo $cid; ?>",
			"file_base64": chunks_info['base64'],
			"all_chunks": max
		}

		// 调用 xhr 请求
		xhr("/data/server.php", data).then((result) => {
			var result = JSON.parse(result)

			if (result['state'] == 'succ') {
				document.querySelector(".function span").textContent = '切片完整！可以进行下一步！'
			}

			// 恢复视频失败回显
			if (result['state'] == 'error') {
				document.querySelector(".function span").textContent = result['content']
			}
		}).catch((error) => {
				if (error.message === '请求超时') {
					console.log('请求超时123');
				} else {
					console.log('发生了其他错误:', error);
			}
		})
	}



	// 
	// 视频切片合并
	// 
	const combine_chunks = () => {

		// 未进行切片
		if (chunks_info['chunks'].length == 0) {
			document.querySelector(".function span").textContent = '没有进行切片！不能进行上传！'
			return
		}

		document.querySelector(".function span").textContent = '视频开始合并，请耐心等待合并结果！'
		const max = chunks_info['chunks'].length

		var data = {
			"cmd": "combine_chunks",
			"cid": "<?php echo $cid; ?>",
			"file_base64": chunks_info['base64'],
			"all_chunks": max,
			"size": chunks_info['size']
		}

		xhr("/data/server.php", data).then((result) => {
			console.log(result);

			if (result == 'succ') {
				document.querySelector(".function span").textContent = '视频合并完成！请重新打开“视频上传”窗口即可刷新出来！'
			}

			// 恢复视频失败回显
			if (result == 'error') {
				document.querySelector(".function span").textContent = '视频合并失败，原因未知，建议上传全部重新开始。'
			}
		})
	}



	// 
	// 指定切片上传
	// 
	const target_chunk_upload = () => {
		// 未进行切片
		if (chunks_info['chunks'].length == 0) {
			document.querySelector(".function span").textContent = '没有进行切片！不能进行上传！'
			return
		}

		const target = prompt('请输入你需要指定上传的切片序号');
		if (!target) {
			document.querySelector(".function span").textContent = '未指定切片序号！'
			return;
		}

		if (!Number(target)) {
			document.querySelector(".function span").textContent = '指定切片序号不为整数！'
			return;
		}

		document.querySelector(".function span").textContent = '指定切片正在上传，请耐心等待！'

		const data = {
			"cmd": "chunks_upload",
			"cid": "<?php echo $cid; ?>",
			"file_base64": chunks_info['base64'],
			"index": target,
			"all_chunks": chunks_info['chunks'].length,
			"chunk": chunks_info['chunks'][target]
		};

		xhr("/data/server.php", data).then((result) => {
			document.querySelector(".function span").textContent = '切片上传完成！'
		}).catch((error) => {
			alert(error)
		})


		// try {
		// 	const result = await xhr("/data/server.php", data);
		// 	console.log(result);

		// 	// if (result == 'succ') {
		// 	// 	document.querySelector(".function span").textContent = '上传完成，请进行下一步！';
		// 	// }

		// 	// if (result == 'error') {
		// 	// 	document.querySelector(".function span").textContent = '上传失败！请重新上传！';
		// 	// }

		// } catch (error) {
		// 	document.querySelector(".function span").textContent = `上传切片 ${i} 失败`;
		// }
	}
	









	// 
	// 
	// 视频上传进度条回显示
	// 
	// 
	const video_upload_process = (uploaded, all_chunks) => {
		// 更新上传记录
		if (uploaded < all_chunks) {
			document.querySelector(".function span").textContent = `正在上传: ${uploaded} / ${all_chunks}`
		}

		// 视频上传到最后一个切片，服务器正在恢复切片
		if (uploaded == all_chunks) {
			document.querySelector(".function span").textContent = `切片上传完成！请进行下一步！`
		}
	}



	// 
	// 
	// 视频删除
	// 
	// 
	const delete_video = (vid) => {
		var vid = vid.replace(".mp4", '')
		console.log(vid);
		
		
		// 构建xhr数据
		var data = new FormData()
		data.append("cmd", "delete_video")
		data.append("cid_or_tid", "<?php echo $cid; ?>")
		data.append("vid", vid)

		// 发送xhr到目标服务器
		var xhr = new XMLHttpRequest()
		xhr.open("POST", "<?php echo $tcp_port; ?>/data/server.php", true)
		xhr.send(data)

		// 监听请求结果
		xhr.onreadystatechange = () => {
			if(xhr.readyState == 4 && xhr.status == 200){
				alert(`${vid}.mp4已删除，请重新打开"视频上传窗口"确认`)
			}
		}

	}













	// 
	// 打开视频插入
	// 
	
	const open_music_gui = () => {
		float_window.title("插入音乐")
		float_window.content(`
			目前支持插入的音乐平台有：GalBase、网易云。<br>
			<pre>GalBase站内音乐插入方法：{_m123} {_music123} \n（其中123为音乐ID，即站内「汐凪第一学园演唱会」版块歌单里显示的mid）\n（{_music123}是自动播放，打开帖子后就会播放，{_m123}则不会。）</pre>
			<pre>网易云音乐单曲插入方法：{_wyy123}\n（其中123为网易云音乐ID，你可以从音乐的分享链接中找到，默认不自动播放）</pre>
			<pre>网易云音乐歌单插入方法：{_wyys123}\n（其中123为网易云歌单ID，你可以从歌单的分享链接中找到，默认不自动播放）</pre>
		`)
		float_window.open()
	}


	// 
	// 
	// 打开音乐插入
	// 
	// 
	const open_video_gui = () => {
		float_window.title("插入视频")
		float_window.content(`
			插入前你需要有视频直链，如：https://galbase.top/data/videos/bbs_opening.mp4<br>
			<pre>插入方法：{_video直链} {_smallvideo直链}</pre>
			<pre>大宽度插入方法：{_videohttps://galbase.top/data/videos/bbs_opening.mp4}</pre>
			<pre>小宽度插入方法：{_smallvideohttps://galbase.top/data/videos/bbs_opening.mp4}</pre>

		`)
		float_window.open()
	}



	// 
	// 打开子标题GUI
	// 
	const insert_subtitle = () => {
		float_window.title("子标题")
		float_window.content(`
			<pre>插入方法：{_subtitle子标题内容}</pre>
		`)
		float_window.open()
	}





	// 
	// 
	// 打开表格教程
	// 
	// 
	const open_table_guide = () => {
		float_window.title("建表格式")
		float_window.width("80%")
		float_window.content(`
			<div class="limit">
			建表需要一定的HTML知识，本站暂时无法提供更简便的建表格式<br>
			<div style="display:float; float:left; width: 60%">
				<pre>
&lt;table border="1" cellpadding="12px" cellspacing="0px" width="40%">
   &lt;thead>
      &lt;tr>
         &lt;th>Key&lt;/th> &lt;th>Value&lt;/th> &lt;th>Value2&lt;/th>
      &lt;/tr>
   &lt;/thead>

   &lt;tbody>
      &lt;tr>
         &lt;td>测试&lt;/td> &lt;td>测试2&lt;/td> &lt;td>测试3&lt;/td>
      &lt;/tr>
      &lt;tr>
         &lt;td>测试4&lt;/td> &lt;td>测试5&lt;/td> &lt;td>测试6&lt;/td>
      &lt;/tr>

      // 合并教程
      &lt;tr>
         &lt;td>1&lt;/td>	&lt;td colspan="2">2&lt;/td>
      &lt;/tr>
      &lt;tr>
         &lt;td rowspan="2">3&lt;/td> &lt;td>4&lt;/td> &lt;td>5&lt;/td>
      &lt;/tr>
      &lt;tr>
         &lt;td>6&lt;/td> &lt;td>7&lt;/td>
      &lt;/tr>
   &lt;/tbody>
&lt;/table>
				</pre>
			</div>

			<div style="display:float; float:left; width: 40%">
				cellpadding 单元格与文字边框的距离，默认0px<br>
				cellspacing 单元格与单元格的距离，默认2px<br>
				<br>
				rowspan 跨行合并<br>
				colspan 跨列合并<br>
				<br>
				rowspan="2" 合并2个单元格<br>
				<br>

				
				<table border="1" cellspacing="0px" width="100%">
					<thead>
						<tr>
							<th>Key</th> <th>Value</th> <th>Value2</th>
						</tr>
					</thead>

					<tbody>
						<tr>
							<td>测试</td>	<td>测试2</td>	<td>测试3</td>
						</tr>
						<tr>
							<td>测试4</td>	<td>测试5</td>	<td>测试6</td>
						</tr>
						<tr>
							<td>1</td>	<td colspan="2">2</td>
						</tr>
						<tr>
							<td rowspan="2">3</td>	<td>4</td>	<td>5</td>
						</tr>
						<tr>
							<td>6</td>	<td>7</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		`)
		float_window.open()
	}



	// 
	// 
	// 生成预览封面
	// 
	// 
	const create_preview = () => {
		// 获取fid
		var fid = document.querySelector('#target_fid').value
		var aids = document.querySelector('#cover').value

		// 构建xhr请求给服务器
		var data = new FormData()
		data.append("cmd", "create_preview")
		data.append("fid", fid)
		data.append("folder", "<?php echo $cid; ?>")
		data.append("aids", aids)

		// 对参数进行判断
		if (!fid) {
			alert("fid未填写")
			return
		}
		if (!aids) {
			alert("封面图片aid未填写")
			return
		}

		// 发送请求给服务器
		var xhr = new XMLHttpRequest()
		xhr.open("POST", '<?php echo $tcp_port; ?>/data/server.php', true)
		xhr.send(data)

		// xhr处理
		xhr.onreadystatechange = () => {
			if(xhr.readyState == 4 && xhr.status == 200){
				
				// 返回响应
				var return_data = xhr.responseText
				console.log(return_data);
				switch (return_data) {
					case 'succ':
						alert("封面缩略图生成完成")
						break;

					case '图片不存在':
						alert("fid或aid不正确，无法找到正确的图片文件")
						break;
					
					// case '目标图片已存在':
					// 	alert("缩略图已存在，请删除后再重新生成")
					// 	break;

					default:
						alert("未知错误，请联系站长")
						break;
				}
			}
		}
	}
	





















	// 
	// 
	//	图片压缩并上传
	// 
	// 
	function img_zip(id, file) {
		return new Promise((resolve, reject) => {
			const reader = new FileReader();

			reader.onload = function(e) {
				const img = new Image();
				img.src = e.target.result;

				img.onload = function() {
				const canvas = document.createElement('canvas');
				const ctx = canvas.getContext('2d');

				// 设置压缩后的宽高（取一边做压缩判定）
				const maxWidth = 1920;
				const maxHeight = 2048;

				let width = img.width;
				let height = img.height;
				

				// 如果图片尺寸超过最大限制，则等比例缩放
				if (width > maxWidth || height > maxHeight) {
					const ratio = Math.min(maxWidth / width, maxHeight / height);
					width *= ratio;
					height *= ratio;
				}

				// 设置canvas尺寸
				canvas.width = width;
				canvas.height = height;

				// 绘制图片到canvas
				ctx.drawImage(img, 0, 0, width, height);

				// 转为blob返回
				canvas.toBlob(function(blob) {
					// resolve(blob); // 将Blob对象通过resolve()传递回来
					var file = blob

					// 获取DOM元素
					document.querySelector(`#_${id} .remove`).textContent = '上传中'

					// 构建xhr文件数据
					var xhr = new XMLHttpRequest()
					var data = new FormData()
					data.append("cmd", "topic_imgs_upload")
					data.append("cid", "<?php echo $cid; ?>")
					data.append("file", file)

					// 监听xhr
					xhr.onreadystatechange = () => {
						if(xhr.readyState == 4 && xhr.status == 200){
							try {
								// xhr上传完成回显aid和删除
								var aid = xhr.responseText

								document.querySelector(`#_${id} .aid`).textContent = 'aid:' + aid

								// 将DOM元素base64 ID改位aid
								// 目的：辅助删除图片
								document.querySelector(`#_${id}`).setAttribute("id", `_${aid}`)

								// 赋予删除onclick具体内容
								document.querySelector(`#_${aid} .remove`).textContent = '删除'
								document.querySelector(`#_${aid} .remove`).setAttribute("onclick", `delete_img('${aid}')`)

								// // 赋予图片插入code
								document.querySelector(`#_${aid} img`).setAttribute("onclick", `insert_text('{_i${aid}}')`)
							} catch (error) {
								console.error('xhr请求失败，失败code：' + error)
							}
						}
					}

					// 获取进度条dom
					let progress = document.querySelector(`#_${id} progress`)

					// 上传进度条显示
					xhr.upload.onprogress = function(e){

						let progress_value = (e.loaded / e.total) * 100
						progress.value = progress_value

						// 上传完成分配aid
						if (progress_value == 100) {
							document.querySelector(`#_${id} .remove`).textContent = '压缩中'
						}
					}

					// 发送xhr
					xhr.open("POST", `/data/server.php`, true)
					xhr.send(data)


				}, 'image/jpeg', 1);
				};
			};
			
			reader.onerror = reject;
			reader.readAsDataURL(file);
		});
	}






















	//
	//
	//	发帖指南
	//
	//
	function topic_guide() {
		float_window.title("发帖指南")
		float_window.content(`
			不知道发帖在哪个版块的请发帖至「澄空心情驿站 / 心跳町温泉」<br>
			<br>
			1.资源类请发布在「资源收入繁华街」<br>
			2.某社全作品合集，或者作品推荐请发布在「资源合集 / TOP作品推荐」<br>
			3.玩后感和杂谈类请发布在「玩后感 / 杂谈 / 忘却的旋律」<br>
			4.番剧/游戏OP/其他影像请发布在「幌路北学园演剧社」<br>
			5.闲聊，日常，不懂发哪请发布至「澄空心情驿站 / 心跳町温泉」<br>
			6.小说类/教程类/历史记录类发帖在「凰华学院图书馆」<br>
			7.「Cubic Cafe / YuKuRu咖啡厅」版块暂时未规划<br>
			8.「结姬学园校区 / 各务台学园校区」版块暂时未规划<br>
			9.「星光文库」版块暂时未规划<br>
			10.存档需要储存请发布在「银河仓库」<br>
		`)
		float_window.open()
	}







	// 
	// 
	//	加载帖子数据
	// 
	// 
	const load_topic_data = (load_protect) => {
		if (load_protect == 'yes') {
			// 取title，tags，content
			var title = get_cookie('topic_title')
			var tags = get_cookie('topic_tags')
			var content = get_cookie('topic_content')

			// 添加至html
			document.querySelector('#topic_title').value = title
			document.querySelector('#tags').value = tags
			document.querySelector('#topic_content').value = content
		}
	}



	// 
	// 
	//	保存发帖数据至前端cookie
	// 
	// 
	const save_topic_data = (save_protect) => {
		if (save_protect == 'yes') {
			// 取帖子标题进行cookie存储
			var topic_title = document.querySelector('#topic_title').value

			// 取标签
			var topic_tags = document.querySelector('#tags').value

			// 取帖子内容（大量文本存储）
			var topic_content = document.querySelector('#topic_content').value
			
			// 整理cookie信息
			var date = new Date()
			date.setDate(date.getDate() + 30) // 设置有效期为30天
			var expires = 'expires=' + date.toUTCString()

			// 储存title，tags，content
			document.cookie = `topic_title=${topic_title};` + expires + '; path=/'
			document.cookie = `topic_tags=${topic_tags};` + expires + '; path=/'
			document.cookie = `topic_content=${topic_content};` + expires + '; path=/'
		}
	}



	// 
	// 获取时间，格式：2022-22-22
	// 
	const get_time = () => {
		const options = {
			timeZone: 'Asia/Shanghai',
			year: 'numeric',
			month: '2-digit',
			day: '2-digit',
		};
		
		const formatter = new Intl.DateTimeFormat('zh-CN', options);
		const parts = formatter.formatToParts(new Date());
		
		// 提取年、月、日
		const year = parts.find(p => p.type === 'year').value;
		const month = parts.find(p => p.type === 'month').value;
		const day = parts.find(p => p.type === 'day').value;
		
		return `${year}-${month}-${day}`;
	};
</script>






<script>
	var date = get_time("Y-M-D")
	console.log(date);


	// 
	// 
	// 发帖
	// 
	// 
	const send_topic = () => {
		var title = document.querySelector('#topic_title').value
		var content = document.querySelector('#topic_content').value
		var tags = document.querySelector('#tags').value
		var cover = document.querySelector('#cover').value
		var fid = document.querySelector('#target_fid').value
		var cid = "<?php echo $cid; ?>"

		if (!title) {
			alert("禁止上传无标题帖子！")
			return
		}

		if (!content) {
			alert("禁止上传空内容帖子！")
			return
		}

		if (!fid) {
			alert("你未选择上传到指定版块！")
			return
		}

		float_window.title("提醒")
		float_window.content("发帖成功，系统正在压缩图片，成功会自动进行跳转请耐心等待。")
		float_window.open()

		// 请求锁，防止过量请求
		if (lock()) {
			return
		}

		var data = {
			"cmd": "send_topic",
			"title": title,
			"content": content,
			"tags": tags,
			"cover": cover,
			"fid": fid,
			"cid": cid
		}
		xhr("/servers/topic.php", data).then((result) => {
			
			// 修改帖子内容，回到帖子
			if (cid + 0 > -9000) {
				window.location.href = `topic/${cid}`

			// 第一次发帖
			} else {
				const newest_tid = `
					<?php
						// 获取最新tid
						echo get_value("sys_auto_increment_value", "value", "variable='tid'");
					?>
				`
				window.location.href = `topic/${newest_tid}`
			}
		})
	}





	// 判断是否开启了only-HTML模式
	if (document.querySelector('#topic_content').textContent.includes("{?only-HTML?}")) {
		document.querySelector('.bottom_function #only-HTML').checked = true
	}

	const onlyHTML = () => {
		// 未开启
		var textarea = document.getElementById("topic_content")
		

		// 开启
		if (document.querySelector('.bottom_function #only-HTML').checked == true) {
			textarea.value = "{?only-HTML?}\n" + textarea.value;

		// 关闭
		} else {
			textarea.value = textarea.value.replace("{?only-HTML?}\n", "");
		}
	}
</script>