// 自动判断环境
const isDev = window.location.hostname === "127.0.0.1" || window.location.hostname === "localhost";

// 根据环境设置
const DEBUG = isDev; 
const API = isDev ? "http://127.0.0.1" : "https://api.galbase.top";
const SRC = isDev ? "http://127.0.0.1/data/forums/3/data3/imgs" : "https://d3gal.dpdns.org/imgs"



// 
//	获取cookie
// 
const get_cookie = (name) => {
	// 将cookie字符串拆分成一个名值对数组
	const kvArray = document.cookie.split(';');

	for (i=0; i < kvArray.length; i++) {

		const kv = kvArray[i].split('=');
		// 移除名称中的空格
		const cookieName = kv[0].trim();

		if (cookieName === name) {
			return decodeURIComponent(kv[1]);
		}
	}
	return null;
}

var COOKIE_FINGER = get_cookie("finger")
if (!COOKIE_FINGER) {
    COOKIE_FINGER = "首次访问"
}



// 
// fetch请求
// 
async function fetch_API(
    method,
    url,
    query = {},
    body = null,
    head = null,
    timeout = 10000
) {

    //
    // Query 参数
    //
    const params = new URLSearchParams();

    Object.entries(query).forEach(([key, value]) => {

        // 忽略 null / undefined
        if (value === null || value === undefined) {
            return;
        }

        // 数组参数
        if (Array.isArray(value)) {

            value.forEach(v => {

                if (v !== null && v !== undefined) {
                    params.append(key, v);
                }

            });

        } else {

            params.append(key, value);

        }

    });

    const queryString = params.toString();

    const fullUrl = queryString
        ? `${url}?${queryString}`
        : url;

    //
    // 超时控制
    //
    const controller = new AbortController();

    const timeoutId = setTimeout(
        () => controller.abort(),
        timeout
    );

    try {

        //
        // Headers
        //
        const headers = {
            Accept: "application/json",
            finger: COOKIE_FINGER
        };

        //
        // 登录状态
        //
        if (LOGIN === true) {
            headers.Authorization =
                `Bearer ${SESSIONID}`;
        }

        //
        // 自定义 Header
        //
        if (
            head !== null &&
            typeof head === "object"
        ) {
            Object.assign(headers, head);
        }

        //
        // Body 自动处理
        //
        let requestBody = undefined;

        if (body !== null) {

            //
            // 文件上传
            //
            if (body instanceof FormData) {

                requestBody = body;

            } else {

                //
                // JSON
                //
                headers["Content-Type"] =
                    "application/json";

                requestBody =
                    JSON.stringify(body);

            }

        }

        //
        // 发起请求
        //
        const response = await fetch(
            fullUrl,
            {
                method,
                headers,
                body: requestBody,
                signal: controller.signal
            }
        );

        clearTimeout(timeoutId);

        //
        // HTTP 状态码检查
        //
        if (!response.ok) {

            let errorMessage =
                `请求失败，状态码：${response.status}`;

            try {

                const errorData =
                    await response.json();

                if (
                    errorData &&
                    errorData.error
                ) {
                    errorMessage =
                        errorData.error;
                }

            } catch (_) {}

            throw new Error(errorMessage);

        }

        //
        // 无内容返回
        //
        if (response.status === 204) {
            return null;
        }

        //
        // JSON 返回
        //
        return await response.json();

    } catch (err) {

        clearTimeout(timeoutId);

        if (
            err.name === "AbortError"
        ) {
            throw new Error("请求超时");
        }

        throw err;

    }

}


// fetch_API("GET", `${API}/admin/remove_topic`).then(res => {
//     if (res['error']) {
//         float_window.title("错误")
//         float_window.content(`${res['error']}`)
//         float_window.open()
//         return
//     }
// 
// }).catch(err => {
//     float_window.content(`${err.message}`)
//     float_window.open()
// })












































// 
// 加载js
// 
function load_script(src) {
  return new Promise((resolve, reject) => {
    const script = document.createElement("script");

    script.src = src;
    script.async = true;

    script.onload = () => resolve(script);
    script.onerror = () => reject(new Error(`Failed to load: ${src}`));

    document.head.appendChild(script);
  });
}

// 用法：await load_script("./effect.js");







const title_format = (text) => {
    return [...text.replace(/\s/g, "")].map(char => `<li>${char}</li>`).join("");
}

// 
// 悬浮窗
// 
class float_window_control {
	constructor() {
	}

	// 创建
	create() {
		var html = `
			<div id="float_window">
				<div class="board main_board">
					<img src="${SRC}/title_arc.png" class="title_arc">
					<div class="board_2nd">
						<header>
							<img src="${SRC}/title_start.png" class="title_start">
							<ul class="title_content"></ul>
							<img src="${SRC}/title_end.png" class="title_end">
							<div class="buttons_">
								<button onclick="float_window.close()" style="float: right;">关闭</button>
							</div>
						</header>
						<main>
						</main>
					</div>
				</div>
			</div>
		`
		document.body.insertAdjacentHTML("beforeend", html)

		// 总DOM获取
		const float_window_header = document.querySelector('#float_window header')
		const float_window_content = document.querySelector('#float_window .board')
		
		// 拖动功能
		float_window_header.addEventListener('mousedown', function(e) {
			var x = e.pageX - float_window_content.offsetLeft
			var y = e.pageY - float_window_content.offsetTop

			// 鼠标移动事件
			document.addEventListener('mousemove', float_window_move)
			function float_window_move(e) {
				
				// 重新赋值给float_window_content
				float_window_content.style.left = e.pageX - x + 'px'
				float_window_content.style.top = e.pageY - y + 'px'
			}

			// 鼠标松开事件
			document.addEventListener('mouseup', function() {
				document.removeEventListener('mousemove', float_window_move)
			})
		})
	}

	// 显示float_window
	open() {
		document.querySelector('#float_window').style.display = 'block'

		// 改变蒙蔽高度
		const height = document.documentElement.offsetHeight
		document.querySelector("#float_window").style.height = `${height}px`

		// 计算高度
		const top_num = (window.scrollY) + (window.innerHeight) / 2
		document.querySelector("#float_window .board").style.top = `${top_num}px`
		// document.querySelector('#float_window .board').scrollIntoView({behavior: "smooth", block: "start"})
	}

	// 关闭float_window
	close() {
		document.querySelector('#float_window').style.display = 'none'
	}

	// 修改title
	title(title) {
		var title_format = ""
		for (let i = 0; i < title.length; i++) {
			let char = title[i]
			title_format = title_format + `<li>${char}</li>`
		}
		document.querySelector('#float_window .title_content').innerHTML = title_format
        document.querySelector('#float_window .board').style.width = `50%`
	}

	// 修改内容
	content(content) {
		document.querySelector('#float_window main').innerHTML = content
	}

	// 修改float_window宽度
	width(num) {
		document.querySelector('#float_window .board').style.width = `${num}`
	}

	// 锁关闭时间
	lock(num = 3) {
		// 去除关闭按钮的onclick
		document.querySelector('#float_window .buttons_ button').textContent = `禁止关闭`
		document.querySelector('#float_window .buttons_ button').onclick = ""

		for (let i = 0; i < num + 1; i++) {
			setTimeout(() => {
				let total_num = num - i
				document.querySelector('#float_window .buttons_ button').textContent = `禁止关闭（${total_num}）`

				// 倒数结束
				if (total_num == 0) {
					document.querySelector('#float_window .buttons_ button').textContent = `关闭`
					document.querySelector('#float_window .buttons_ button').onclick = function() { float_window.close() }
				}
			}, 1000 * i)
		}
	}
}

// 加载float_window类
const float_window = new float_window_control()

// float_window.create()
// float_window.title("测试")
// float_window.width("90%")
// float_window.open()
// float_window.lock(3)






// 
//	帖子作者头像全屏
// 
const fullscreen_avatar = (element) => {
	// 获取头像URL
	var avatar = element

	if (avatar.src.includes("_")) {
		var url = avatar.src.split("_")[0]
		url = `${url}_big.avif`
		avatar.src = url
	}

	fullscreen(avatar)
}






// 
// 
//	帖内图片全屏
// 
// 
var _iz_sc=1, _iz_ox=0, _iz_oy=0, _iz_drag=false, _iz_sx, _iz_sy, _iz_sox, _iz_soy;

function fullscreen(element) {
    var viewer = document.getElementById("_viewer");
    if (!viewer) {
        viewer = document.createElement("div");
        viewer.id = "_viewer";
        viewer.style.cssText = "display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;justify-content:center;align-items:center;cursor:zoom-out";

        var img = document.createElement("img");
        img.id = "_viewer_img";
        img.style.cssText = "max-width:90%;max-height:90%;cursor:grab";
        viewer.appendChild(img);
        document.body.appendChild(viewer);

        function apply() {
            img.style.transform = "translate("+Math.round(_iz_ox)+"px,"+Math.round(_iz_oy)+"px) scale("+_iz_sc+")";
        }

        viewer.addEventListener("wheel", function(e) {
            e.preventDefault();
            var ns = Math.max(1, Math.min(8, _iz_sc * (e.deltaY < 0 ? 1.12 : 1/1.12)));
            var r = img.getBoundingClientRect();
            var px = e.clientX-(r.left+r.width/2), py = e.clientY-(r.top+r.height/2);
            var k = ns/_iz_sc; _iz_ox = _iz_ox+px-px*k; _iz_oy = _iz_oy+py-py*k; _iz_sc = ns; apply();
        }, {passive:false});

        img.addEventListener("mousedown", function(e) {
            if(_iz_sc<=1) return; _iz_drag=true; img.style.cursor="grabbing";
            _iz_sx=e.clientX; _iz_sy=e.clientY; _iz_sox=_iz_ox; _iz_soy=_iz_oy; e.preventDefault();
        });
        document.addEventListener("mousemove", function(e) {
            if(!_iz_drag) return; _iz_ox=_iz_sox+(e.clientX-_iz_sx); _iz_oy=_iz_soy+(e.clientY-_iz_sy); apply();
        });
        document.addEventListener("mouseup", function() { _iz_drag=false; img.style.cursor="grab"; });
        img.addEventListener("dblclick", function() { _iz_sc=1;_iz_ox=0;_iz_oy=0; apply(); });
        viewer.addEventListener("click", function(e) { if(e.target===viewer) viewer.style.display="none"; });
        document.addEventListener("keydown", function(e) { if(e.key==="Escape") viewer.style.display="none"; });
    }

    var img = document.getElementById("_viewer_img");
    img.src = element.src;
    img.style.transform = "";
    _iz_sc=1; _iz_ox=0; _iz_oy=0;
    viewer.style.display = "flex";
}





// 
// 设置cookie
// 
function set_cookie(name, value, days = 365, path = '/') {
	const expires = new Date(Date.now() + days * 864e5).toUTCString();
	document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=${path}`;
}

// 使用方式
//   get_cookie("last_lock")
//   set_cookie('topic_title', topic_title);


// 
// 清除cookie
// 
function clear_cookie() {
    const cookies = document.cookie.split(";");

    for (let cookie of cookies) {
        const name = cookie.split("=")[0].trim();
        document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
    }
}





// 
// 
// 使用要导入get_cookie.js
// 
// 
function lock() {
	// 获取上一个请求时间戳
	var last_timestamp = get_cookie("last_lock")

	// 获取当前时间
	var timestamp = Math.floor(Date.now() / 1000)

	// 锁触发
	if (timestamp - last_timestamp < 3) {
		alert("本次请求过于频繁已阻止，请等待3秒后再进行！")
		return 1

	// 上新锁
	} else {
		// 添加时间戳到cookie
		var date = new Date()
		date.setDate(date.getDate() + 30) // 设置有效期为30天
		var expires = 'expires=' + date.toUTCString()
		document.cookie = `last_lock=${timestamp};` + expires + '; path=/'
	}
}

function small_lock() {
	// 获取上一个请求时间戳
	var last_timestamp = get_cookie("last_lock")

	// 获取当前时间
	var timestamp = Math.floor(Date.now() / 1000)

	// 锁触发
	if (timestamp - last_timestamp < 1) {
		alert("本次请求过于频繁已阻止，请等待1秒后再进行！")
		return 1

	// 上新锁
	} else {
		// 添加时间戳到cookie
		var date = new Date()
		date.setDate(date.getDate() + 30) // 设置有效期为30天
		var expires = 'expires=' + date.toUTCString()
		document.cookie = `last_lock=${timestamp};` + expires + '; path=/'
	}
}



function time_diff(input) {
  const timeZone = 'Asia/Shanghai';
  let date;

  // 如果是数字，判断秒/毫秒
  if (typeof input === 'number') {
    if (input.toString().length <= 10) {
      input = input * 1000; // 秒 -> 毫秒
    }
    date = new Date(input);
  } else if (typeof input === 'string') {
    date = new Date(input);
    if (isNaN(date)) {
      // 只包含日期没有时间，加上 00:00
      date = new Date(input + 'T00:00:00');
    }
  } else {
    throw new Error('不支持的输入类型');
  }

  // 当前日期（上海时区）
  const now = new Date(new Date().toLocaleString('en-US', { timeZone }));
  const inputTime = new Date(date.toLocaleString('en-US', { timeZone }));

  // 取年月日
  const formatDate = (d) => {
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
  };

  const nowDateStr = formatDate(now);
  const inputDateStr = formatDate(inputTime);

  if (nowDateStr === inputDateStr) return "今天";
  
  // 计算时间差（天为单位）
  const diffMs = nowTimeMs(nowDateStr) - nowTimeMs(inputDateStr);
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
  if (diffDays < 0) return "未来时间";
  if (diffDays === 1) return "1天前";
  if (diffDays < 30) return `${diffDays}天前`;
  if (diffDays < 365) return `${Math.floor(diffDays / 30)}个月前`;
  return `${Math.floor(diffDays / 365)}年前`;

  // 辅助函数：把日期字符串转为毫秒数
  function nowTimeMs(dateStr) {
    const parts = dateStr.split('-');
    return new Date(parts[0], parts[1] - 1, parts[2]).getTime();
  }
}


function format_date(timestamp) {
    const date = new Date(timestamp * 1000);

    const formatter = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Shanghai',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });

    return formatter.format(date); // en-CA 语言环境自动输出 YYYY-MM-DD
}

























async function fetch_POST(url, data = {}, timeout = 10000) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeout);

    try {
        let headers = {
            'Accept': 'application/json'
        };

        // 登录
        if (LOGIN === true) {
            headers['Authorization'] = `Bearer ${SESSIONID}`;
        }
        let body;

        // 文件上传
        if (data instanceof FormData) {
            body = data;
            // 不要手动设置 Content-Type
            // 浏览器会自动生成 multipart/form-data boundary
        } else {

            // 普通JSON
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(data);
        }

        const response = await fetch(url, {
            method: 'POST',
            headers,
            body,
            signal: controller.signal
        });
        clearTimeout(timeoutId);
        if (!response.ok) {
            throw new Error(`请求失败，状态码：${response.status}`);
        }
        return await response.json();
    } catch (err) {
        clearTimeout(timeoutId);
        if (err.name === 'AbortError') {
            throw new Error('请求超时');
        } else {
            throw err;
        }
    }
}



async function fetch_PUT(url, data = {}, timeout = 10000) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeout);

    try {
        if (LOGIN == true) {
            var headers = {
                'Accept': 'application/json',
                'Authorization': `Bearer ${SESSIONID}`
            }
        } else {
            var headers = {'Accept': 'application/json'}
        }

        const response = await fetch(url, {
            method: 'PUT',
            headers: headers,
            body: JSON.stringify(data),
            signal: controller.signal
        });

        clearTimeout(timeoutId);  // 成功响应清理定时器

        if (!response.ok) {
            throw new Error(`请求失败，状态码：${response.status}`);
        }

        return await response.json(); // 返回解析后的 JSON
    } catch (err) {
        clearTimeout(timeoutId);
        if (err.name === 'AbortError') {
            throw new Error('请求超时');  // 超时
        } else {
            throw err;  // 其他错误
        }
    }
}

async function fetch_GET(url, params = {}, timeout = 10000) {
    // 把 params 转成查询字符串
    const queryString = new URLSearchParams(params).toString();
    const fullUrl = queryString ? `${url}?${queryString}` : url;

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeout);

    try {
        if (LOGIN == true) {
            var headers = {
                'Accept': 'application/json',
                'Authorization': `Bearer ${SESSIONID}`
            }
        } else {
            var headers = {'Accept': 'application/json'}
        }

        const response = await fetch(fullUrl, {
            method: 'GET',
            headers: headers,
            signal: controller.signal
        });

        clearTimeout(timeoutId);

        if (!response.ok) {
            throw new Error(`请求失败，状态码：${response.status}`);
        }

        return await response.json();
    } catch (err) {
        clearTimeout(timeoutId);
        if (err.name === 'AbortError') {
            throw new Error('请求超时');
        } else {
            throw err;
        }
    }
}



async function fetch_DELETE(url, params = {}, timeout = 10000) {
    const queryString = new URLSearchParams(params).toString();
    const fullUrl = queryString ? `${url}?${queryString}` : url;

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeout);

    try {
        if (LOGIN == true) {
            var headers = {
                'Accept': 'application/json',
                'Authorization': `Bearer ${SESSIONID}`
            }
        } else {
            var headers = {'Accept': 'application/json'}
        }
        const response = await fetch(fullUrl, {
            method: 'DELETE',
            headers: headers,
            signal: controller.signal
        });
        clearTimeout(timeoutId);
        if (!response.ok) {
            throw new Error(`请求失败，状态码：${response.status}`);
        }
        return await response.json();
    } catch (err) {
        clearTimeout(timeoutId);
        if (err.name === 'AbortError') {
            throw new Error('请求超时');
        }
        throw err;
    }
}






function log(...args) {
    if (DEBUG) console.log(...args);
}





// function request_topic() {
//     fetch_GET(`${API}/topic/get/${TID}`, {tags_decode: true, finger: FINGER}, 10000).then(res => {
//     }).catch(err => {
//         console.log(`帖子请求失败: ${err.message}`);
//         setTimeout(request_topic, 1000);
//     });
// }
// request_topic()