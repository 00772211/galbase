// 全局变量
const PATH = window.location.pathname
var HTML
var TOPIC
const DOM_title = document.querySelector("#topic_title")
const DOM_fid = document.querySelector("#target_fid")
const DOM_tags = document.querySelector("#tags")
const DOM_cover = document.querySelector("#cover")
const DOM_content = document.querySelector("#topic_content")



if (PATH.includes("add")) {
	var MOD = "add"
}
if (PATH.includes("replace")) {
	var MOD = "replace"
	var TID = parseInt(PATH.split("/").pop())
}



// 
// 更改标题
// 
if (MOD == "add") {
	document.title = "新发帖"
}
if (MOD == "replace") {
	document.title = "修改发帖"
	document.querySelector(".main_board .title_content").innerHTML = title_format("修改帖子")
	document.querySelector("#send_topic_button").textContent = "修改帖子"
}



// 
// 未登录遣返
// 
if (LOGIN == false) {
	float_window.title("错误")
	float_window.content("该tid未找到对应的帖子内容！3秒后将自动返回主页！")
	float_window.open()
	setTimeout(() => {
		window.location.href = "/"
	}, 3000)
}



// 
// 请求板块fid
// 
function request_board() {
	fetch_API("GET", `${API}/forum/board`).then(res => {
		DOM_fid.insertAdjacentHTML("beforeend", `<option value="1-1">GAL资源&emsp;- ${res['data']['1-1']}</option>`)
		DOM_fid.insertAdjacentHTML("beforeend", `<option value="1-2">资源合集&emsp;- ${res['data']['1-2']}</option>`)
		DOM_fid.insertAdjacentHTML("beforeend", `<option value="1-3">玩后感&emsp;&emsp;- ${res['data']['1-3']}</option>`)
		DOM_fid.insertAdjacentHTML("beforeend", `<option value="1-4">动漫&emsp;&emsp;&emsp;- ${res['data']['1-4']}</option>`)
		DOM_fid.insertAdjacentHTML("beforeend", `<option value="2-1">日常交流&emsp;- ${res['data']['2-1']}</option>`)
		DOM_fid.insertAdjacentHTML("beforeend", `<option value="2-2">小说攻略&emsp;- ${res['data']['2-2']}</option>`)
		DOM_fid.insertAdjacentHTML("beforeend", `<option value="2-3">重要帖子&emsp;- ${res['data']['2-3']}</option>`)
		DOM_fid.insertAdjacentHTML("beforeend", `<option value="2-4">音乐&emsp;&emsp;&emsp;- ${res['data']['2-4']}</option>`)
		DOM_fid.insertAdjacentHTML("beforeend", `<option value="3-1">未开通&emsp;&emsp;- ${res['data']['3-1']}</option>`)
		DOM_fid.insertAdjacentHTML("beforeend", `<option value="3-2">汉化组&emsp;&emsp;- ${res['data']['3-2']}</option>`)
	}).catch(err => {
		console.log(`帖子请求失败: ${err.message}`)
		setTimeout(request_board, 1000)
	})
}

if (LOGIN == true) {
	request_board()
}


// 
// 修改发帖自动选择板块
// 
const select_board = () => {
	if (DOM_fid.options.length > 2) {
		DOM_fid.value = TOPIC['fid']
		DOM_fid.disabled = true
	} else {
		setTimeout(select_board, 1000)
	}
}



// 
// 请求帖子数据
// 
function request_topic() {
	fetch_API("GET", `${API}/topic/${TID}`, {tags_decode: true, finger: FINGER}).then(res => {
		TOPIC = res['data']['topic']
		DOM_title.value = TOPIC['title']

		// tag用 | 拼接
		if (TOPIC['tags_decode']) {
			const TAG = Object.values(TOPIC['tags_decode'])
			DOM_tags.value = TAG.join("|")
		}

		if (TOPIC['preview']) {
			DOM_cover.value = TOPIC['preview']
		}
		
		DOM_content.value = TOPIC['content']

		// 选择fid
		select_board()

	}).catch(err => {
		console.log(`帖子请求失败: ${err.message}`)
		setTimeout(request_topic, 1000)
	})
}

if (MOD == "replace") {
	request_topic()
}



// 
// 发帖
// 
const send_topic = () => {

	// 请求锁，防止过量请求
	if (lock()) {
		return
	}

	// 无标题
	if (!DOM_title.value) {
		float_window.title("错误")
		float_window.content("禁止上传无标题帖子")
		float_window.open()
		return
	}

	// 空内容
	if (!DOM_content.value) {
		float_window.title("错误")
		float_window.content("禁止上传空内容帖子！")
		float_window.open()
		return
	}

	// 无板块
	if (!DOM_fid.value) {
		float_window.title("错误")
		float_window.content("你未选择上传到指定版块！")
		float_window.open()
		return
	}

	// 修改发帖直接转函数
	if (MOD == "replace") {
		replace_topic()
		return
	}

	var data = {
		title: DOM_title.value,
		content: DOM_content.value,
		tags: DOM_tags.value,
		cover: DOM_cover.value,
		fid: DOM_fid.value
	}

	float_window.title("提醒")
	float_window.content("数据正在发送至服务器！请耐心等待一会~")
	float_window.open()

	fetch_API("POST", `${API}/topic`, {}, data).then(res => {
		if (res['error']) {
			float_window.title("错误")
			float_window.content(`${res['error']}`)
			float_window.open()
			return
		}

		float_window.title("提示")
		float_window.content(`发帖成功！将于3秒后自动跳转至您发布的帖子！`)
		float_window.open()
		setTimeout(() => {
			window.location.href = `/topic/${res['data']}`
		}, 3000)
	}).catch(err => {
		float_window.title("错误")
		float_window.content(`${err.message}`)
		float_window.open()
// 		if (error.message === '请求超时') {
// 			float_window.content("发帖超时！<br>原因：可能是网络发生了丢包 / 服务器宕机了<br>您可以新打开个主页看看有没有自己的帖子，若没有请重新点击发帖再次发帖！")
// 		} else {
// 			float_window.content("发帖出现未知错误！<br>原因：可能是网络发生了丢包 / 服务器宕机了 / 其他未知问题<br>您可以新打开个主页看看有没有自己的帖子，若没有请重新点击发帖再次发帖！")
// 		}
	})
}



// 
// 修改帖子
// 
const replace_topic = () => {
	var data = {
		title: DOM_title.value,
		content: DOM_content.value,
		tags: DOM_tags.value,
		cover: DOM_cover.value,
		tid: TID
	}

	float_window.title("提醒")
	float_window.content("数据正在发送至服务器！请耐心等待一会~")
	float_window.open()

	fetch_API("PUT", `${API}/topic`, {}, data).then(res => {
		console.log(res);
		
		if (res['error']) {
			float_window.title("错误")
			float_window.content(`${res['error']}`)
			float_window.open()
			return
		}

		float_window.title("提示")
		float_window.content(`修改成功！将于3秒后自动跳转至您发布的帖子！`)
		float_window.open()
		setTimeout(() => {
			window.location.href = `/topic/${res['data']}`
		}, 3000)
	}).catch(err => {
		float_window.title("错误")
		float_window.content(`${err.message}`)
		float_window.open()
	})
}



// 
// 文本域内插入内容
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
// 图片上传
// 
const GUI_imgs_upload = () => {

	// 修改title
	float_window.title("图片上传")

	// 内容体变更
	var html = `
		<div class="function">
			仅仅上传图片是无法在帖子内显示，请自行点击图片将图片代码添加进帖子内容里。<br>
			<button onclick="open_imgs_upload_windows()">图片上传</button>
			<button onclick="load_topic_imgs()">重新加载已上传的图片</button>
			<input type="file" accept="image/jpeg, image/png, image/gif, image/avif" id="imgs_upload" multiple hidden>
		</div>
		<div class="limit">
			<div id="imgs"></div>
		</div>
	`
	float_window.content(html)

	// 打开悬浮窗
	float_window.open()

	// dom元素获取
	const imgs_upload = document.querySelector('#imgs_upload')

	// 加载存在的图片
	load_topic_imgs()

	// 监听图片input的变化
	imgs_upload.onchange = function () {
		var files = imgs_upload.files
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
// 加载已上传的图片
// 
const load_topic_imgs = () => {
	var DOM_imgs = document.querySelector("#imgs")
	DOM_imgs.innerHTML = ""

	var data = {}
	if (MOD == "replace") {
		var data = {tid: TID}
	}

	fetch_API("GET", `${API}/topic/imgs`, data).then(res => {
		if (res['error']) {
			float_window.title("错误")
			float_window.content(`${res['error']}`)
			float_window.open()
			return
		}
		
		res['data'].forEach((img, i) => {
			HTML = `
				<div class="card" id="_${img['name']}">
					<img src="${img['path']}" loading="lazy" alt="图片加载失败" onclick="insert_text('{_i${img['name']}}')">
					<progress value="100" max="100"></progress>
					<span class="remove" onclick="delete_img('${img['name']}')">删除</span>
					<span class="aid">aid:${img['name']}</span>
				</div>
			`
			DOM_imgs.insertAdjacentHTML("beforeend", HTML)
		})
	}).catch(err => {
		float_window.title("错误")
		float_window.content(`${err.message}`)
		float_window.open()
	})
}



// 
// 打开隐藏input的上传窗口
// 
const open_imgs_upload_windows = () => {
	imgs_upload.click()
}



// 
// 删除图片
// 
const delete_img = (aid) => {

	var data = {}
	if (MOD == "replace") {
		var data = {tid: TID}
	}	

	fetch_API("DELETE", `${API}/topic/imgs/${aid}`, data).then(res => {
		if (res['error']) {
			float_window.title("错误")
			float_window.content(`${res['error']}`)
			float_window.open()
			return
		}
		document.querySelector(`#_${aid}`).innerHTML = ''
	}).catch(err => {
		float_window.title("错误")
		float_window.content(`${err.message}`)
		float_window.open()
	})
}



// 
//	图片压缩并上传
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
				var file = blob

				// 获取DOM元素
				document.querySelector(`#_${id} .remove`).textContent = '上传中'

				// 构建xhr文件数据
				var xhr = new XMLHttpRequest()
				var data = new FormData()

				// 定义文件名file
				data.append(
					"file",
					file,
					"image.jpg"
				)

				// 监听xhr
				xhr.onreadystatechange = () => {
					if(xhr.readyState == 4 && xhr.status == 200){
						try {
							// xhr上传完成回显aid和删除
							var res = JSON.parse(xhr.responseText)

							if (res['error']) {
								float_window.title("错误")
								float_window.content(res['error'])
								float_window.open()
								return
							}

							var aid = res['data']

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
							alert(`请求失败：${error}`)
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
				if (MOD == "replace") {
					xhr.open("POST", `${API}/topic/img?tid=${TID}`, true)
				} else {
					xhr.open("POST", `${API}/topic/img`, true)
				}
				xhr.setRequestHeader(
					'Authorization',
					`Bearer ${SESSIONID}`
				)
				xhr.send(data)
			}, 'image/jpeg', 1);
			};
		};
		
		reader.onerror = reject;
		reader.readAsDataURL(file);
	});
}



// 
// 视频上传
// 
const GUI_upload_videos = () => {
	float_window.title("视频上传")

	// 内容体变更
	HTML = `
		<div class="function">
			<button onclick="load_topic_videos()">重新加载已上传的视频</button>
			<span>正在加载...</span>
			<button onclick="window.open('/topic/1578', '_blank')" style="float: right; margin-right: 20px;">本站视频上传教程</button><br><br>
			<button onclick="open_video_upload_windows()">1-视频切片</button>
			<button onclick="chunks_upload()">2-视频上传</button>
			<button onclick="check_chunks()">3-切片检查</button>
			<button onclick="combine_chunks()">4-视频合并</button>
			<button onclick="target_chunk_upload()">5-指定上传</button>
			<input type="file" accept="video/mp4, video/webm, video/x-msvideo" id="upload_video" hidden>
		</div><br>
		<div id="videos">
		</div>
	`
	float_window.content(HTML)

	// 打开悬浮窗
	float_window.open()

	// 加载已存在的视频
	load_topic_videos()
}



// 
// 加载已有视频
// 
const load_topic_videos = () => {
	var DOM_videos = document.querySelector("#videos")
	DOM_videos.innerHTML = ""	

	var data = {}
	if (MOD == "replace") {
		data = {tid: TID}
	}

	fetch_API("GET", `${API}/topic/videos`, data).then(res => {
		if (res['error']) {
			float_window.title("错误")
			float_window.content(`${res['error']}`)
			float_window.open()
			return
		}
		
		res['data'].forEach((video, i) => {
			HTML = `<span class="tag" onclick="insert_text('{_v${video['name']}}')">${video['name']}.mp4</span><span class="tag tag3" onclick="delete_video('${video['name']}')">删除${video['name']}.mp4</span><br><br>`
			DOM_videos.insertAdjacentHTML("beforeend", HTML)
		})
		document.querySelector('#float_window .function span').textContent = "已加载全部视频"
	}).catch(err => {
		float_window.title("错误")
		float_window.content(`${err.message}`)
		float_window.open()
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
	var max = chunks_info['chunks'].length;

	// 遍历所有切片，依次上传
	for (let i = 0; i < max; i++) {
		try {
			uploaded++
			video_upload_process(uploaded, max)

			var data = new FormData()

			// 定义文件名file
			data.append(
				"file",
				chunks_info['chunks'][i],
				`video.mp4.${i}`
			)

			if (MOD == "replace") {
				await fetch_POST(`${API}/topic/videos?tid=${TID}`, data)
			} else {
				await fetch_POST(`${API}/topic/videos`, data)
			}
		} catch(err) {
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

	var data = {}
	if (MOD == "replace") {
		var data = {tid: TID}
	}

	fetch_API("GET", `${API}/topic/videos/chunk/${max}`, data).then(res => {
		if (res['error']) {
			float_window.title("错误")
			float_window.content(`${res['error']}`)
			float_window.open()
			return
		}

		var lost = res['data'].join()
		if (lost == "") {
			document.querySelector(".function span").textContent = '切片完整！可以进行下一步！'
		} else {
			document.querySelector(".function span").textContent = `切片丢失：${lost}`
		}
		
	}).catch(err => {
		float_window.title("错误")
		float_window.content(`${err.message}`)
		float_window.open()
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

	
	query = {}
	if (MOD == "replace") {
		query = {tid: TID}
	}

	// 定义文件名file
	var data = new FormData()
	data.append(
		"file",
		chunks_info['chunks'][target],
		`video.mp4.${target}`
	)

	fetch_API("POST", `${API}/topic/videos`, query, data).then(res => {
		if (res['error']) {
			float_window.title("错误")
			float_window.content(`${res['error']}`)
			float_window.open()
			return
		}

		document.querySelector('#float_window .function span').textContent = "切片上传完成！"
	}).catch(err => {
		document.querySelector('#float_window .function span').textContent = "切片上传失败"
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


	var data = {}
	if (MOD == "replace") {
		data = {tid: TID}
	}

	fetch_API("POST", `${API}/topic/videos/chunk/${max}`, data).then(res => {
		if (res['error']) {
			float_window.title("错误")
			float_window.content(`${res['error']}`)
			float_window.open()
			return
		}

		document.querySelector('#float_window .function span').textContent = "视频合并完成！正在自动刷新！"
		load_topic_videos()
	}).catch(err => {
		document.querySelector('#float_window .function span').textContent = "合并失败！原因未知，建议重新上传，若还是无法上传请联系管理员！"
	})
}



// 
// 视频上传进度条回显示
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
	var data = {}
	if (MOD == "replace") {
		data = {tid: TID}
	}

	fetch_API("DELETE", `${API}/topic/videos/${vid}`, data).then(res => {
	    if (res['error']) {
	        float_window.title("错误")
	        float_window.content(`${res['error']}`)
	        float_window.open()
	        return
	    }

		load_topic_videos()
	
	}).catch(err => {
		alert(err.message)
	})
}



// 
// 打开音乐插入
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
// 打开视频插入
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
// 打开表格教程
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
//	发帖指南
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





// // 判断是否开启了only-HTML模式
// if (document.querySelector('#topic_content').textContent.includes("{?only-HTML?}")) {
// 	document.querySelector('.bottom_function #only-HTML').checked = true
// }

// const onlyHTML = () => {
// 	// 未开启
// 	var textarea = document.getElementById("topic_content")
	

// 	// 开启
// 	if (document.querySelector('.bottom_function #only-HTML').checked == true) {
// 		textarea.value = "{?only-HTML?}\n" + textarea.value;

// 	// 关闭
// 	} else {
// 		textarea.value = textarea.value.replace("{?only-HTML?}\n", "");
// 	}
// }
