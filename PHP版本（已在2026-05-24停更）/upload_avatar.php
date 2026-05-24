

<?php
	require_once dirname(__FILE__).'/conn.php';
	require_once dirname(__FILE__).'/config.php';
	require_once dirname(__FILE__).'/functions.php';

	// 前端存在sessionID
	if (isset($_COOKIE['sessionID'])) {

		// uid不存在
		if (!get_uid()) {
			// 删除cookie
			exit('你当前登录已过期，请返回论坛主页重新登录');
		}

	// 未登录
	} else {
		exit('未登录，禁止上传头像');
	}
?>



<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>头像上传与裁剪</title>
	<style>
		body {
			text-align: center;
		}
		#uploadBox {
			display: flex;
			align-items: center;
			justify-content: center;
			margin-left: 50%;
			width: 100px;
			height: 30px;
			border: 3px dashed #ccc;
			cursor: pointer;
			transform: translate(-50%);
		}

		#uploadBox:hover {
			border-color: #aaa;
		}

		#uploadBox input[type="file"] {
			display: none;
		}

		#previewContainer {
			display: flex;
			align-items: center;
			justify-content: center;
			margin-top: 20px;
		}

		#previewWrapper {
			position: relative;
			width: 400px;
			height: auto;
			max-height: 400px;
			overflow: hidden;
		}

		#preview {
			width: 100%;
			max-height: 100%;
			object-fit: contain;
		}

		#selectionBox {
			position: absolute;
			top: -1%;
			left: -1%;
			/* height: 2vw; */
			border: 2px dashed #f00;
		}

		#resizeHandle {
			position: absolute;
			bottom: 0;
			right: 0;
			width: 10px;
			height: 10px;
			background-color: #f00;
			cursor: se-resize;
		}

		#downloadButton {
			margin-top: 20px;
		}
	</style>
</head>
<body>
	<div id="uploadBox">
		<label for="upload" id="uploadLabel">点击选择图片</label>
		<input type="file" accept="image/*" id="upload" />
	</div>


	<div style="margin-left: 50%; transform: translate(-25%, 0)">
		<div id="previewWrapper">
			<img id="preview" name="预览头像">
			<div id="selectionBox">
				<div id="resizeHandle"></div>
			</div>
		</div>
		<button onclick="back()" style="margin-left: -50%;">返回论坛</button>
		<button id="downloadButton" style="margin-left: -50%;">上传头像</button>
	</div>

	<div>上传后请耐心等待一会，会有3个弹窗出现。若出现"请求超时"则头像没上传成功。</div>

	<script>
		// 获取页面元素
		const uploadInput = document.getElementById('upload');
		const previewWrapper = document.getElementById('previewWrapper');
		const preview = document.getElementById('preview');
		const selectionBox = document.getElementById('selectionBox');
		const resizeHandle = document.getElementById('resizeHandle');
		const downloadButton = document.getElementById('downloadButton');



		let isDragging = false;
		let startX = 0;
		let startY = 0;
		let startWidth = 0;
		let startHeight = 0;



		function back() {
			window.location.href = "/user_admin.php"
		}


		// 压缩成大图片
		function zip_to_big(file) {
			const reader = new FileReader();

			reader.onload = function(e) {
				const img = new Image();
				img.src = e.target.result;

				img.onload = function() {
					const canvas = document.createElement('canvas');
					const ctx = canvas.getContext('2d');

					// 设置压缩后的宽高（取一边做压缩判定）
					const maxWidth = 1920;
					const maxHeight = 1920;

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

					// 转为blob传输给服务器
					canvas.toBlob(function(blob) {
						// 创建FormData对象
						const formData = new FormData();
						formData.append('cmd', 'upload_avatar'); // 'avatar'为上传文件字段名，可以根据实际情况修改
						formData.append('uid', <?php echo get_uid(); ?>);
						formData.append('size', 'big');
						formData.append('file', blob); // 'avatar'为上传文件字段名，可以根据实际情况修改

						// 发送请求
						const request = new XMLHttpRequest();
						request.open('POST', './data/server.php'); // 根据实际情况指定服务器上传地址
						request.send(formData);
						
						// 监听请求
						request.onreadystatechange = function(){
							if(request.readyState == 4){
								if(request.status == 200){
									alert('大头像上传完成，请耐心等待审核完成')
								}
							}
						}

					}, 'image/jpg', 1) // 0.75是jpg压缩质量
				}
			}
			reader.readAsDataURL(file)
		}


		// 压缩成中图片
		function zip_to_medium(file) {
			const reader = new FileReader();

			reader.onload = function(e) {
				const img = new Image();
				img.src = e.target.result;

				img.onload = function() {
					const canvas = document.createElement('canvas');
					const ctx = canvas.getContext('2d');

					// 设置压缩后的宽高（取一边做压缩判定）
					const maxWidth = 500;
					const maxHeight = 500;

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

					// 转为blob传输给服务器
					canvas.toBlob(function(blob) {
						// 创建FormData对象
						const formData = new FormData();
						formData.append('cmd', 'upload_avatar'); // 'avatar'为上传文件字段名，可以根据实际情况修改
						formData.append('uid', <?php echo get_uid(); ?>);
						formData.append('size', 'medium');
						formData.append('file', blob); // 'avatar'为上传文件字段名，可以根据实际情况修改

						// 发送请求
						const request = new XMLHttpRequest();
						request.open('POST', './data/server.php'); // 根据实际情况指定服务器上传地址
						request.send(formData);
						
						// 监听请求
						request.onreadystatechange = function(){
							if(request.readyState == 4){
								if(request.status == 200){
									alert('中头像上传完成，请耐心等待审核完成')
								}
							}
						}

					}, 'image/jpg', 1) // 0.75是jpg压缩质量



				}
			}
			reader.readAsDataURL(file)
		}






		// 压缩成小图片
		function zip_to_small(file) {
			const reader = new FileReader();

			reader.onload = function(e) {
				const img = new Image();
				img.src = e.target.result;

				img.onload = function() {
					const canvas = document.createElement('canvas');
					const ctx = canvas.getContext('2d');

					// 设置压缩后的宽高（取一边做压缩判定）
					const maxWidth = 200;
					const maxHeight = 200;

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

					// 转为blob传输给服务器
					canvas.toBlob(function(blob) {
						// 创建FormData对象
						const formData = new FormData();
						formData.append('cmd', 'upload_avatar'); // 'avatar'为上传文件字段名，可以根据实际情况修改
						formData.append('uid', <?php echo get_uid(); ?>); // 'avatar'为上传文件字段名，可以根据实际情况修改
						formData.append('size', 'small');
						formData.append('file', blob); // 'avatar'为上传文件字段名，可以根据实际情况修改

						// 发送请求
						const request = new XMLHttpRequest();
						request.open('POST', './data/server.php'); // 根据实际情况指定服务器上传地址
						request.send(formData);
						
						// 监听请求
						request.onreadystatechange = function(){
							if(request.readyState == 4){
								if(request.status == 200){
									alert('小头像上传完成，请耐心等待审核完成')
								}
							}
						}

					}, 'image/jpg', 0.75) // 0.75是jpg压缩质量
				}
			}
			reader.readAsDataURL(file)
		}






		// 显示裁剪后的图片
		function displayCroppedImage(file) {
			const reader = new FileReader();

			reader.onload = function(e) {
				preview.src = e.target.result;

				preview.onload = function() {
					resetSelection();
				};
			};

			reader.readAsDataURL(file);
		}

		// 重置选择框位置和大小
		function resetSelection() {
			const wrapperWidth = previewWrapper.clientWidth;
			const wrapperHeight = previewWrapper.clientHeight;
			const imageSizeRatio = preview.naturalWidth / preview.naturalHeight;

			let selectionSize = Math.min(wrapperWidth, wrapperHeight * imageSizeRatio);

			const initialSelection = {
				x: (wrapperWidth - selectionSize) / 2,
				y: (wrapperHeight - selectionSize / imageSizeRatio) / 2,
				width: selectionSize,
				height: selectionSize / imageSizeRatio
			};

			updateSelectionBox(initialSelection);
		}

		// 更新选择框的位置和大小
		function updateSelectionBox(selection) {
			selectionBox.style.left = selection.x + 'px';
			selectionBox.style.top = selection.y + 'px';
			selectionBox.style.width = selection.width + 'px';
			selectionBox.style.height = selection.height + 'px';
		}

		// 绑定选择框拖动和缩放事件
		function bindSelectionEvents() {
			selectionBox.addEventListener('mousedown', handleMouseDown);
			resizeHandle.addEventListener('mousedown', handleResizeMouseDown);
		}

		// 处理选择框拖动事件
		function handleMouseDown(event) {
			event.preventDefault();

			isDragging = true;

			startX = event.clientX;
			startY = event.clientY;

			startWidth = parseInt(selectionBox.style.width, 10);
			startHeight = parseInt(selectionBox.style.height, 10);

			window.addEventListener('mousemove', handleMouseMove);
			window.addEventListener('mouseup', handleMouseUp);
		}

		// 处理选择框拖动过程中的鼠标移动事件
		function handleMouseMove(event) {
			if (!isDragging) return;

			const deltaX = event.clientX - startX;
			const deltaY = event.clientY - startY;

			const selection = {
				x: parseInt(selectionBox.style.left, 10) + deltaX,
				y: parseInt(selectionBox.style.top, 10) + deltaY,
				width: startWidth,
				height: startHeight
			};

			updateSelectionBox(selection);

			startX = event.clientX;
			startY = event.clientY;
		}

		// 处理选择框拖动结束事件
		function handleMouseUp(event) {
			isDragging = false;

			window.removeEventListener('mousemove', handleMouseMove);
			window.removeEventListener('mouseup', handleMouseUp);
		}

		// 处理选择框缩放事件
		function handleResizeMouseDown(event) {
			event.stopPropagation();

			startX = event.clientX;
			startY = event.clientY;

			startWidth = parseInt(selectionBox.style.width, 10);
			startHeight = parseInt(selectionBox.style.height, 10);

			window.addEventListener('mousemove', handleResizeMouseMove);
			window.addEventListener('mouseup', handleResizeMouseUp);
		}

		// 处理选择框缩放过程中的鼠标移动事件
		function handleResizeMouseMove(event) {
			const deltaX = event.clientX - startX;
			const deltaY = event.clientY - startY;

			const newWidth = startWidth + deltaX;
			const newHeight = startHeight + deltaY;

			if (newWidth > 0 && newHeight > 0) {
				const selection = {
					x: parseInt(selectionBox.style.left, 10),
					y: parseInt(selectionBox.style.top, 10),
					width: newWidth,
					height: newHeight
				};

				updateSelectionBox(selection);
			}
		}

		// 处理选择框缩放结束事件
		function handleResizeMouseUp(event) {
			window.removeEventListener('mousemove', handleResizeMouseMove);
			window.removeEventListener('mouseup', handleResizeMouseUp);
		}

		// 下载裁剪后的头像并上传
		function downloadAvatar() {
			const canvas = document.createElement('canvas');
			const context = canvas.getContext('2d');
			const scaleFactor = preview.naturalWidth / preview.width;

			const selection = {
				x: parseInt(selectionBox.style.left, 10),
				y: parseInt(selectionBox.style.top, 10),
				width: parseInt(selectionBox.style.width, 10),
				height: parseInt(selectionBox.style.height, 10)
			};

			const canvasWidth = selection.width * scaleFactor;
			const canvasHeight = selection.height * scaleFactor;

			canvas.width = canvasWidth;
			canvas.height = canvasHeight;

			context.drawImage(
				preview,
				selection.x * scaleFactor,
				selection.y * scaleFactor,
				canvasWidth,
				canvasHeight,
				0,
				0,
				canvasWidth,
				canvasHeight
			);

			// 转换为Blob对象
			canvas.toBlob(function(blob) {
				// 压缩成大头像
				zip_to_big(blob)

				// 压缩成中头像
				zip_to_medium(blob)

				// 压缩成小头像
				zip_to_small(blob)
			})
		}


		// 绑定选择文件事件
		uploadInput.addEventListener('change', function(e) {
			const file = e.target.files[0];

			if (file && file.type.startsWith('image/')) {
				displayCroppedImage(file);
			}
		});

		// 绑定下载头像事件
		downloadButton.addEventListener('click', downloadAvatar);

		// 绑定选择框和缩放手柄事件
		bindSelectionEvents();
	</script>
</body>
</html>