<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>MD5值校验</title>
	<!-- css -->
	<link rel="stylesheet" href="/css/header.css">
	<link rel="stylesheet" href="/css/style.css">
</head>
<body>
	<!-- 引入顶部导航栏 -->
	<?php require_once dirname(__FILE__).'/header.php'; ?>
	<br>
	<?php require_once dirname(__FILE__)."/navigation_bar.php"; ?>

	<div class="board main_board else">
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<header>
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format("MD5值校验"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">
			</header>
			<main>
				<pre>请不要修改下载时的文件名！修改文件名后MD5值会发生改变导致无法正常校验！<br>本站5333号后的资源才有MD5信息，5333号前没有入库，所以没有信息。</pre>
				<a href="path/to/your/file.bat" download>点击下载 .bat 文件</a>
				<!-- <button onclick="select_files()">文件选择</button> -->
				<!-- <input type="file" accept="*" id="files_MD5" multiple hidden> -->
			</main>
		</div>
	</div>

	<h4 style="visibility: hidden;">x</h4>

	<div class="board main_board">
		<img src="/data/imgs/title_arc.png" class="title_arc">
		<div class="board_2nd">
			<header>
				<img src="/data/imgs/title_start.png" class="title_start">
				<ul class="title_content"><?php echo title_format("有什么用？"); ?></ul>
				<img src="/data/imgs/title_end.png" class="title_end">
			</header>
			<main style="padding: 10px;">
				文件MD5值，对于本站来说主要用于校验文件完整性，其次是校验安全性。<br>
				由于本站大量资源位于OneDrive网盘中，大部分的魔法不稳定会造成数据包丢包，导致下载的文件发生数据缺失进而导致压缩包损坏无法正常解压，如提示解压密码错误，头部数据错误等报错信息。
			</main>
		</div>
	</div>



<script src="/js/md5.js"></script>
<script src="/js/xhr.js"></script>
<script src="/js/fullscreen.js"></script>
<script src="/js/float_window.js"></script>
<script src="/js/user_admin.js"></script>

<script>
	// const files_MD5_DOM = document.querySelector('#files_MD5')

	// // 选择文件
	// const select_files = () => {
	// 	files_MD5_DOM.click()
	// }

	// // 监听上传
	// files_MD5_DOM.onchange = function () {
	// 	const files = files_MD5_DOM.files

	// 	// 遍历所有文件
	// 	for (let i = 0; i < files.length; i++) {

	// 	}
	// }

const files_MD5_DOM = document.querySelector('#files_MD5')

// 选择文件
const select_files = () => {
    files_MD5_DOM.click()
}

// 监听上传
files_MD5_DOM.onchange = function () {
    const files = files_MD5_DOM.files

    // 遍历所有文件
    for (let i = 0; i < files.length; i++) {
        const file = files[i]

        // 创建文件读取器
        const reader = new FileReader()

        reader.onload = function (e) {
            const fileContent = e.target.result

            // 计算文件的MD5值
            const fileMD5 = md5(fileContent)

            // 输出文件名和MD5值
            console.log(`文件名: ${file.name}, MD5值: ${fileMD5}`)
        }

        // 读取文件内容
        reader.readAsArrayBuffer(file)
    }
}
</script>




















<!-- 引入底部模块 -->
<?php require_once dirname(__FILE__).'/footer.php'; ?>







<!-- 关闭数据库 -->
<?php mysqli_close($link); ?>