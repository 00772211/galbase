// 
// 全局变量
// 
const API = "http:/apitest.galbase.top"
var HTML
var LOGIN = false
var CONFIG
var NAV
var BG
float_window.create()



// 
// 登录
// 
if (get_cookie("sessionID")) {
    LOGIN = true
    var UID = get_cookie("uid")
    var UNAME = get_cookie("uname")
    var SESSIONID = get_cookie("sessionID")
}



// 初始背景图片
if (get_cookie("BG")) {
    BG = get_cookie("BG")
} else {
    BG = "/data/imgs/footer_bg.avif"
}



// 
// 全局变量：浏览器指纹
// 
if (LOGIN == true) {
    var FINGER = UID
} else {
    const fpPromise = import('/js/finger.js').then(FingerprintJS => FingerprintJS.load())
    fpPromise.then(fp => fp.get()).then(result => {
        var FINGER = result.visitorId
        const cookie_finger = get_cookie("finger")

        // 判断cookie中指纹是否存在
        if (!cookie_finger) {
            set_cookie('finger', FINGER);

        // 指纹存在，判断指纹是否变更
        } else if (cookie_finger != FINGER) {
            set_cookie('finger', FINGER);
        }
    })
}



// 
// 下放headers
// 
HTML = `
    <img class="logo" src="/data/imgs/logo.png" title="返回主页" onclick="window.location.href = '/'" loading="lazy" alt="图片加载失败">

    <div class="search">
        <input type="text" placeholder="整站全文搜索"><span onclick="search()"></span>
    </div>

    <div class="user_dropdown">
        <a class='uname'></a>
        <nav class="content">
            <a href="/user_admin.html">账号管理</a>
            <a class="space" href="">个人空间</a>
            <a href="/register">切换账号</a>
            <a onclick="quet()">退出登录</a>
        </nav>
    </div>

    <img class="avatar" src="" loading="lazy" alt="导航栏用户头像" hidden>
    <a href="/msg" target="_blank"><span class="msg"><img src="/data/imgs/msg_none.png" loading="lazy" alt="图片加载失败"></span></a>
    <span class="text broadcast">本站永久域名home.galbase.top 收藏不迷路</span>
`
document.querySelector(".header").innerHTML = HTML



//  
// 更新导航栏头像（未登陆的情况下）
// 
const update_header_avatar = () => {
    if (!CONFIG) {
        setTimeout(update_header_avatar, 100);
        return
    }
    document.querySelector(".header .avatar").src = CONFIG['random_avatar']
    document.querySelector(".header .avatar").hidden = false
}



// 
// 更新的导航栏用户名
// 
const DOM_user_dropdown = document.querySelector(".user_dropdown a")
if (LOGIN == true) {
    DOM_user_dropdown.title = UNAME
    DOM_user_dropdown.textContent = UNAME
    document.querySelector(".user_dropdown .space").href = `/space/${UID}`
    document.querySelector(".header .avatar").src = get_cookie("avatar_small")
    document.querySelector(".header .avatar").hidden = false
} else {
    DOM_user_dropdown.href = "/register"
    DOM_user_dropdown.title = "注册 / 登录"
    DOM_user_dropdown.textContent = "注册 / 登录"
    document.querySelector(".user_dropdown .content").style.display = "none"
    update_header_avatar()
}



// 
// 请求必要数据
// 
function request_forum() {
	fetch_API("GET", `${API}/forum`).then(res => {

        // 全局变量
        CONFIG = res['data']
        NAV = res['data']['nav']

        // 背景图片
        set_cookie("BG", res['data']['bgs'][0]["url"])

        // 下放背景
        const css = `
        .cb-slideshow li:nth-child(1) span { background-image: url('${res['data']['bgs'][0]["url"]}'); }
        .cb-slideshow li:nth-child(2) span { background-image: url('${res['data']['bgs'][1]["url"]}'); }
        .cb-slideshow li:nth-child(3) span { background-image: url('${res['data']['bgs'][2]["url"]}'); }
        .cb-slideshow li:nth-child(4) span { background-image: url('${res['data']['bgs'][3]["url"]}'); }
        .cb-slideshow li:nth-child(5) span { background-image: url('${res['data']['bgs'][4]["url"]}'); }
        .cb-slideshow li:nth-child(6) span { background-image: url('${res['data']['bgs'][5]["url"]}'); }

        @media (min-width: 767px) {
            .large {
                background: url('${BG}') no-repeat;
                background-size: cover;
                height: 100vh;
            }
        }
        `
        const style = document.createElement("style");
        style.textContent = css;
        document.head.appendChild(style)
        HTML = `
            <ul class="cb-slideshow">
                <li><span></span></li>
                <li><span></span></li>
                <li><span></span></li>
                <li><span></span></li>
                <li><span></span></li>
                <li><span></span></li>
            </ul>
        `
        document.body.insertAdjacentHTML('beforeend', HTML)

        // 更新导航栏
        if (document.querySelector(".navigation_bar")) {
            update_nav()
        }

        // 更新底部通知
        if (document.querySelector("#imp")) {
            document.querySelector("#imp").innerHTML = CONFIG['maintenance_msg']
        }

        // 维护状态
        if (CONFIG['maintenance'] == true && document.querySelector(".op_board")) {
            float_window.title("公告")
            float_window.content(`${CONFIG['maintenance_msg']}`)
            float_window.open()
            float_window.lock(3)
        }
	}).catch(err => {
		console.log(`导航栏请求失败: ${err.message}`);
		setTimeout(request_forum, 1000);
	});
}
request_forum()



// 
// 未读显示
// 
function request_msg_unread() {
    fetch_API("GET", `${API}/user/msg/unread`).then(res => {
        if (!res['error'] && res['data']['unread'] == true) {
            document.querySelector(".header .msg img").src = "/data/imgs/msg.png"
        }
    }).catch(err => {
        console.log(`未读请求失败：${err.message}`);
        setTimeout(request_msg_unread, 1000);
    })
}
if (LOGIN == true) {
    request_msg_unread();
}



// 
// 搜索栏内容回车触发
// 
document.querySelector('.search input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        search()
}})



// 
// 搜索栏
// 
function search() {
    // 获取搜索内容
    var content = document.querySelector(".search input").value

    // 搜索内容不存在
    if (!content) {
        alert("未输入搜索内容")

    // 搜索内容存在跳转
    } else {
        window.open(`/search/title/${content}`, "_blank")
    }
}



// 
// 退出登录
// 
const quet = () => {
    // 清除cookie
    clear_cookie()

    // 重新进入页面
    window.location.href = '/'
}
