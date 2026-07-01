// 
// 加载所有图片
// 
document.addEventListener("DOMContentLoaded", function() {
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target
                img.src = SRC + img.dataset.src
                observer.unobserve(img)
            }
        });
    });

    document.querySelectorAll("img[data-src]").forEach(img => {
        observer.observe(img)
    })
})





HTML = `
    <div id="imp"></div>

    <div class="danmaku-wrap" id="dw"></div>

    <div class="show_bg" onclick="show_bg()">
        <img src="${SRC}/title_start.png" class="bt" title="观看背景 / 回到论坛" alt="图片加载失败" loading="lazy">
        <img src="${SRC}/title_arc.png" class="title_arc" loading="lazy">
    </div>

    <div id="phone" style="display: none;">
		<img src="${SRC}/phone.avif" class="shell" alt="手机" loading="lazy">
		<img src="${SRC}/phone_bg.png" class="bg" alt="手机壁纸" loading="lazy">
		<div class="inner"> 
			<ul></ul>
		</div>
    </div>
	<img src="${SRC}/phone_open.png" id="phone_open" onclick="GUI_phone()" alt="打开手机" title="打开手机" loading="lazy">
`
document.body.insertAdjacentHTML('beforeend', HTML)

HTML = `
<footer>
	<div class="t">
		<img src="${SRC}/footer_bg.avif" class="bg" alt="footer上侧背景" loading="lazy">
		<img src="${SRC}/footer.avif" class="dark" alt="footer上册套图" loading="lazy">
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
			<img src="${SRC}/logo.png" class="logo" alt="本站LOGO" loading="lazy">

 			<ul>
				<li><a href="/topic/1" target="_blank">本站源码</a></li>
				<li><a href="https://api.galbase.top/docs#/" target="_blank">本站API</a></li>
			</ul>

 			<ul>
				<li>友情链接</li>
                <li><a href="/topic/5585" target="_blank">申请友链</a></li>
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

if (LOGIN == true) {
    if (get_cookie("no_log_online") != 1) {
        setTimeout(add_online_time, 5 * 60 * 1000)
    }
}



// 
// 天文望远镜监听
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



// 
// 加载季节特效
// 
const load_effect = () => {
    if (CONFIG['summer'] == true) {
        load_script("/js/effect/summer.js");
    }
}




// 
// 弹幕配置
// 
const TRACK_COUNT  = 8;         // 轨道数
const TRACK_START_Y = 100;       // 第一条轨道距顶部距离 (px)
const TRACK_GAP    = 44;        // 轨道间距 (px)
const SPEED_MIN    = 90;        // 最低速度 (px/s)
const SPEED_MAX    = 160;       // 最高速度 (px/s)
const STYLES = ['ghost','ink','petal','sakura','night','aurora','sand','dusk','frost','ember'];



// 
// 随机样式
// 
function randomStyle() {
  return STYLES[Math.floor(Math.random() * STYLES.length)];
}



// 
// 轨道状态
// 
const trackState = Array.from({ length: TRACK_COUNT }, () => ({
  width: 0, speed: 0, enterTime: 0
}));



// 
// 弹幕头像
// 
function avCls(style, isMine) {
  if (isMine) return 'av-mine';
  return 'av-' + (style ?? 'ghost');
}



// 
// 量宽
// 
function measureWidth(user, text, style, isMine) {
  const el = document.createElement('div');
  el.className = 'danmaku-item ' + (isMine ? 'style-my-mine' : 'style-' + style);
  Object.assign(el.style, {
    visibility: 'hidden', position: 'fixed',
    left: '-9999px', top: '-9999px', animation: 'none'
  });
  el.innerHTML = `<span class="avatar ${avCls(style, isMine)}">${user[0]}</span><span>${text}</span>`;
  document.body.appendChild(el);
  const w = el.offsetWidth;
  el.remove();
  return w;
}



// 
// 防追尾检测
// 
function canUseTrack(trackIdx, newW, newV) {
  const prev = trackState[trackIdx];
  if (!prev.enterTime) return true;

  const sw = window.innerWidth;
  const dt = (Date.now() - prev.enterTime) / 1000;

  const totalDur = (sw + prev.width) / prev.speed;
  if (dt >= totalDur + 1) return true;

  if (prev.speed * dt < prev.width) return false;

  if (newV > prev.speed) {
    const tCatch = (prev.speed * dt - prev.width) / (newV - prev.speed);
    const tExit  = (sw + prev.width) / prev.speed - dt;
    if (tCatch <= tExit) return false;
  }

  return true;
}



// 
// 选轨
// 
function pickTrack(newW, newV) {
  for (let i = 0; i < TRACK_COUNT; i++) {
    if (canUseTrack(i, newW, newV)) return i;
  }
  return trackState.reduce(
    (best, s, i) => (s.enterTime < trackState[best].enterTime ? i : best), 0
  );
}



// 
// 生成弹幕
// 
let paused = false;
const timers = [];

function send_danmuku(user, text, isMine = false) {
    const style = isMine ? null : randomStyle();  // 随机抽样式
    const wrap  = document.getElementById('dw');
    const sw    = window.innerWidth;

    const w     = measureWidth(user, text, style, isMine);
    const speed = SPEED_MIN + Math.random() * (SPEED_MAX - SPEED_MIN);
    const dur   = (sw + w) / speed;

    const trackIdx = pickTrack(w, speed);
    trackState[trackIdx] = { width: w, speed, enterTime: Date.now() };

    const el = document.createElement('div');
    el.className = 'danmaku-item ' + (isMine ? 'style-my-mine' : 'style-' + style);

    const y = TRACK_START_Y + trackIdx * TRACK_GAP + (Math.random() * 6 - 3);
    el.style.top = y + 'px';
    el.style.animationDuration = dur + 's';
    el.style.setProperty('--travel', (sw + w) + 'px');
    if (paused) el.style.animationPlayState = 'paused';

    el.innerHTML = `<span class="avatar ${avCls(style, isMine)}">${user[0]}</span><span>${text}</span>`;

    wrap.appendChild(el);

    const t = setTimeout(() => el.remove(), dur * 1000 + 300);
    timers.push(t);
}



// 
// 弹幕调度
// 
function scheduleBatch() {
  COMMENTS.forEach((c, i) => {
    const t = setTimeout(
      () => send_danmuku(c.user, c.text),
      i * 1200 + Math.random() * 500
    )
    timers.push(t)
  })
}







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



// 
// 打开关闭手机
// 
function GUI_phone() {
    const phone = document.getElementById('phone');
	const phone_oepn = document.querySelector("#phone_open")
    const isVisible = phone.classList.contains('is-visible');
	

	if (isVisible) {
		phone_oepn.alt = "打开手机"
		phone_oepn.title = "打开手机"
		phone.classList.remove('is-visible');
		phone.classList.remove('is-open');
		phone.style.transform = '';   // ← 先清掉 JS 残留的 transform
		phone.style.transition = '';  // ← 顺带清掉 transition
		phone.classList.add('is-close');
		phone.addEventListener('animationend', () => {
			phone.style.display = 'none';
			phone.classList.remove('is-close');
		}, { once: true });
	} else {
		phone_oepn.alt = "关闭手机"
		phone_oepn.title = "关闭手机"
        phone.style.display = 'block';
        phone.classList.add('is-visible');
        phone.classList.remove('is-close');
        phone.classList.add('is-open');
        phone.addEventListener('animationend', () => {
            phone.classList.remove('is-open');
        }, { once: true });
    }
}



// 
// 3D手机视差
// 
const PHONE = document.querySelector("#phone");
PHONE.addEventListener('mousemove', e => {
  const r = PHONE.getBoundingClientRect();
  const dx = (e.clientX - (r.left + r.width  / 2)) / (r.width  / 2);
  const dy = (e.clientY - (r.top  + r.height / 2)) / (r.height / 2);

  PHONE.style.transform =
    `translate(0, -50%) perspective(1200px) rotateY(${dx * 3}deg) rotateX(${-dy * 2}deg)`;
});

PHONE.addEventListener('mouseleave', () => {
  PHONE.style.transition = 'transform 0.8s ease';
  PHONE.style.transform  = 'translate(0, -50%)';
  setTimeout(() => PHONE.style.transition = '', 800);
})