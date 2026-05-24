HTML = `
    <div id="imp"></div>

    <div class="show_bg" onclick="show_bg()">
        <img src="/data/imgs/title_start.png" class="bt" title="观看背景 / 回到论坛" alt="图片加载失败">
        <img src="/data/imgs/title_arc.png" class="title_arc">
    </div>
`
document.body.insertAdjacentHTML('beforeend', HTML)

HTML = `
<footer>
	<div class="t">
		<img class="bg" src="/data/imgs/footer_bg.avif" alt="footer上侧背景">
		<img class="dark" src="/data/imgs/footer.avif" alt="footer上册套图">
		<div class="content">
			<h1>欢迎访问 GALBase！</h1>
			本站于2024年12月31日与FleeWorld论坛(2019-12-24 - 2024-12-24)合并！<br>
			<br>
			…谢谢你，在无数的站点之中发现了我。<br>
			…谢谢你…喜欢上我们的站点。<br>
			谢谢你，能够爱上…这样的站点。<br>
			<br>
			<br>
			如果你有HTML CSS JS PHP MYSQL PYTHON相关知识和一点点热心！<br>
			都可以直接在论坛内多交流加入我们！
		</div>
	</div>

	<div class="b">
		<br><br><br>
		<div class="links">
			<img class="logo" src="/data/imgs/logo.png" alt="本站LOGO">

 			<ul>
				<li>友情链接</li>
				<li><a href="/topic/1" target="_blank">本站源码</a></li>
			</ul>

			<ul>
				<li><a href="https://www.kungal.com" target="_blank">鲲 Galgame</a></li>
				<li><a href="https://www.touchgal.io" target="_blank">TouchGal</a></li>
			</ul>
			<ul>
				<li><a href="https://shinnku.com" target="_blank">真红小站</a></li>
				<li><a href="https://2dfan.com" target="_blank">2DFan</a></li>
			</ul>
			<ul>
				<li><a href="https://www.ttloli.com" target="_blank">忧郁的 Loli</a></li>
				<li><a href="https://soul-plus.net" target="_blank">南+ South Plus</a></li>
			</ul>
			<ul>
				<li><a href="https://www.hikarinagi.org" target="_blank">Hikarinagi</a></li>
				<li><a href="https://gallibrary.pw" target="_blank">GAL 图书馆</a></li>
			</ul>

			<div class="contact-info">
				<p><strong style="color: #bd1616">事务联系</strong>：<strong>admin@galbase.top</strong> 或者站内发帖！</p>
				<p>免费提供二级域名：<strong>galbase.top</strong>&emsp;<strong>0d000721.cc</strong>&emsp;<strong>ciallo.ca</strong></p>
			</div>
			
		</div>
		<br>
	</div>
</footer>
`
document.body.insertAdjacentHTML('beforeend', HTML)



// 
// 显示背景
// 
const show_bg = () => {
    // 从header导航栏中索取visibility属性判定是否需要回显
    var state = document.querySelector(".header")

    // 需要恢复div
    if (state.style.visibility == 'hidden') {
        // 获取body标签内的所有div元素
        var divs = document.body.getElementsByTagName('div')

        // 遍历所有div显示
        for (var i = 0; i < divs.length; i++) {
            divs[i].style.visibility = 'visible'
        }

    // 隐藏div显示背景
    } else {
        // 获取body标签内的所有div元素
        var divs = document.body.getElementsByTagName('div')

        // 遍历所有div隐藏
        for (var i = 0; i < divs.length; i++) {
            divs[i].style.visibility = 'hidden'
        }

        // 保留class="show_bg"功能按钮
        var show_bg = document.querySelector(".show_bg")
        show_bg.style.visibility = 'visible'
    }
}



// 
// 5分钟记录添加一次在线时间
// 
function add_online_time() {
    // 当前时间
    let now = Date.now()

    // 上次请求时间
    let last = localStorage.getItem("online_time")

    // 5分钟内不再请求
    if (last && now - Number(last) < 5 * 60 * 1000) {
        setTimeout(add_online_time, 10000)
        return
    }

    fetch_API("PUT", `${API}/forum/online`).then(res => {

        // 写入本地时间
        localStorage.setItem(
            "online_time",
            now
        )

        setTimeout(
            add_online_time,
            5 * 60 * 1000
        )
    }).catch(err => {
        console.log(err)
        setTimeout(
            add_online_time,
            10000
        )
    })
}
if (LOGIN == true && UID != 1) {
    setTimeout(add_online_time, 5 * 60 * 1000);
}













// 
// 监听所有版块
// 
const board_telescope = document.querySelectorAll('.board');

// 遍历所有版块
board_telescope.forEach(target_board => {

    if (target_board.querySelector('.telescope_top')) {
        const telescope = target_board.querySelector('.telescope_top')
        const light_star = target_board.querySelector('.light_star')

        // 鼠标移入目标版块
        target_board.addEventListener('mouseenter', () => {
            telescope.style.animation = "telescope_down 1s 1"
            telescope.style.animationFillMode = "forwards";
            light_star.style.animation = "light_star 2s 1"
        })

        // 鼠标移除目标版块
        target_board.addEventListener('mouseleave', () => {
            telescope.style.animation = "telescope_up 1s 1"
            telescope.style.animationFillMode = "forwards"
            light_star.style.animation = ""
        })
    }
})














// 	// 
// 	// 播放器停止进入footer
// 	// 
// 	const playBox = document.querySelector('.play_box');
// 	const footer = document.querySelector('footer');

// 	// 固定播放器距离底部的默认距离
// 	const fixedBottom = 16; // 对应 CSS 中 1cqw 的 px 值，大约根据屏幕自适应调整

// 	window.addEventListener('scroll', () => {
// 		// 页面可视高度
// 		const viewportHeight = window.innerHeight;
// 		// footer 距离页面顶部的距离
// 		const footerTop = footer.getBoundingClientRect().top + window.scrollY;

// 		// 当前滚动到底部的位置
// 		const scrollBottom = window.scrollY + viewportHeight;

// 		if (scrollBottom >= footerTop) {
// 			// 当滚动到底部 footer 时，让播放器停在 footer 上方
// 			const offset = scrollBottom - footerTop + fixedBottom;
// 			playBox.style.bottom = `${offset}px`;
// 		} else {
// 			// 普通固定在页面底部
// 			playBox.style.bottom = `${fixedBottom}px`;
// 		}
// 	});


// 
// 在DOM加载完成后加载视差
// 
document.addEventListener('DOMContentLoaded', () => {
	// 
	// 底部dark视差
	// 
	const darkImg = document.querySelector('footer .t .dark');
	let isVisible = false;
	let latestScrollY = 0;
	let ticking = false;
	const maxOffset = 200; // 正数向下移动，负数向上移动

	// 1. 用 IntersectionObserver 判断元素是否在可视区
	const observer = new IntersectionObserver((entries) => {
		entries.forEach(entry => {
			isVisible = entry.isIntersecting;
		});
	}, { threshold: 0 });

	observer.observe(darkImg);

	// 2. 滚动事件只记录滚动值
	window.addEventListener('scroll', () => {
		latestScrollY = window.scrollY;
		requestTick();
	});

	// 3. requestAnimationFrame 优化计算
	function requestTick() {
		if (!ticking) {
			requestAnimationFrame(updateParallax);
			ticking = true;
		}
	}

	// 4. 更新视差位置
	function updateParallax() {
		ticking = false;
		if (!isVisible) return;

		const rect = darkImg.getBoundingClientRect();
		const viewportHeight = window.innerHeight;

		// progress 0~1 表示元素进入视口的程度
		let progress = (viewportHeight - rect.top) / (viewportHeight + rect.height);
		progress = Math.min(Math.max(progress, 0), 1); // 限制在0~1之间

		darkImg.style.transform = `translateY(${maxOffset * progress}px)`;
	}
});




// 
// 判断设备
// 
function detectDeviceType() {
    var userAgent = navigator.userAgent;
    if (/Android/i.test(userAgent)) {
        return "手机";
    } else if (/iPhone|iPad|iPod/i.test(userAgent)) {
        return "手机";
    } else if (/Windows Phone/i.test(userAgent)) {
        return "手机";
    } else if (/Macintosh|MacIntel|MacPPC|Mac68K/i.test(userAgent)) {
        return "电脑";
    } else if (/Windows|Win16|Win32|Win64/i.test(userAgent)) {
        return "电脑";
    } else if (/Linux/i.test(userAgent) && !/Android/i.test(userAgent)) {
        return "电脑";
    } else if (/iPad/i.test(userAgent)) {
        return "平板";
    } else {
        return "未知设备";
    }
}

var deviceType = detectDeviceType();
if (deviceType == "手机") {
    alert("检测到当前你在用手机浏览本论坛，本论坛仅适配PC！")
}



