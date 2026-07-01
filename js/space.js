
// 
// 全局变量
// 
const TARGET = parseInt(window.location.pathname.split("/")[2])
var HTML
var HIDDEN_STATE = "hidden"

const DOM_finished_gal = document.querySelector('.right .content')
const DOM_collection = document.querySelector('.topics')
const DOM_best_works = document.querySelector('.best_works .works')

HIDDEN_STATE = ""

// 
// 请求用户数据
// 
function request_user_space() {
    fetch_API("GET", `${API}/user/${TARGET}/space`).then(res => {
        document.title = `${res['data']['uname']}的空间`

        // 身份牌
        document.querySelector(".left .avatar img").src = res['data']['avatar_small']
        document.querySelector(".left #uname").textContent = `学生：${res['data']['uname']}`
        document.querySelector(".left #academic_year").textContent = `学年：${res['data']['academic_year']}`
        document.querySelector(".left #identity").textContent = `身份：${res['data']['identity']}`
        document.querySelector(".left .reg").textContent = `入学时间：${res['data']['register_time']}`
        document.querySelector(".left .last").textContent = `最后在校时间：${res['data']['last_login_time']}`

        // 已推完的Gal
        res['data']['finished'].forEach((topic, i) => {
            HTML = `<li>${topic['date']} -><a href="/topic/${topic['tid']}" target="_blank">${topic['title']}</a></li>`
            DOM_finished_gal.insertAdjacentHTML("afterbegin", HTML)
        })

        // 收藏的帖子
        res['data']['collections'].forEach((topic, i) => {
            HTML = `<li><a href="/topic/${topic['tid']}" target="_blank">${topic['title']}</a> </li>`
            DOM_collection.insertAdjacentHTML("afterbegin", HTML)
        })
        
        // 判断作者
        if (TARGET == UID) {
            HIDDEN_STATE = ""
        }

        // 推荐此生必玩之作
        res['data']['best_stories'].forEach((topic, i) => {
            HTML = `
                <li id="_${topic['tid']}">
                    <div class="cover">
                        <div class="func" ${HIDDEN_STATE}>
                            <div class="move" draggable="true">移动</div>
                            <div class="remove" onclick="remove_work(${topic['tid']})">删除</div>
                        </div>

                        <img src="${topic['url']}/${topic['tid']}/preview.avif" title="${topic['title']}" onclick="window.open('/topic/${topic['tid']}')" alt="此生最喜欢的作品之一" draggable="false">
                    </div>

                    <a class="work_title" href="/topic/${topic['tid']}" target="_blank" title="${topic['title']}">${topic['title']}</a>
                </li>
            `
            DOM_best_works.insertAdjacentHTML("beforeend", HTML)
        })

        // 添加功能
        if (TARGET == UID) {
            HTML = `
				<li id="_0">
					<div class="cover">
						<img data-src="/yingmei.jpg" title="点击添加一个作品" onclick="open_add_story()" alt="添加作品">
					</div>

					<a class="work_title" title="点击添加一个作品" onclick="open_add_story()" style="cursor: pointer;">点击添加一个作品</a>
				</li>
			`
            DOM_best_works.insertAdjacentHTML("beforeend", HTML)
        }


    }).catch(err => {
        console.log(`用户数据请求失败: ${err.message}`);
        setTimeout(request_user_space, 1000);
    });
}
request_user_space()



// 
// 拖拽卡片
// 
const list = document.querySelector(".best_works .works")
let drag_source = null;

// 拖拽开始
list.addEventListener("dragstart", (e) => {
    if (!e.target.classList.contains("move")) return;

    // 设置拖拽效果
    e.dataTransfer.effectAllowed = 'move';

    // 保存拖拽源（move 的父级 .best_works .works li 元素）
    drag_source = e.target.closest('.best_works .works li');

    setTimeout(() => {
        drag_source.classList.add('moving');
    }, 0);
});

// 拖拽允许放置
list.addEventListener("dragover", (e) => {
    e.preventDefault();
});

// 拖拽进入目标
list.addEventListener("dragenter", (e) => {
    e.preventDefault();

    // 只处理目标是 .best_works .works li 元素，且不是自身
    const target = e.target.closest('.best_works .works li');

    if (!target || target === drag_source) return;

    const children = Array.from(list.children);
    const source_index = children.indexOf(drag_source);
    const target_index = children.indexOf(target);

    const all_index = Array.from(document.querySelector(".best_works .works").children).length;

    // 添加卡片的帖子禁止移动
    if (target_index == all_index - 1) {
        return
    }

    // 判断是向前拖入，还是向后拖入
    if (source_index < target_index) {
        list.insertBefore(target, drag_source);
    } else {
        list.insertBefore(drag_source, target);
    }
});



// 
// 拖拽结束
// 
list.addEventListener("dragend", (e) => {
    if (drag_source) {
        drag_source.classList.remove('moving');
        drag_source = null;

        // 获取所有作品父级DOM
        const stories_DOM = document.querySelector(".best_works .works");

        // 获取所有直接子元素
        const stories = stories_DOM.children;

        // 提取子元素的 tid 并拼成列表
        var tids = Array.from(stories).map(stories => parseInt(stories.id.replaceAll("_", "")))

        // 去掉最后一个tid0
        tids.pop()

        var data = {
            tids: tids
        }

        fetch_API("PUT", `${API}/user/stories`, {}, data).then(res => {
            if (res['error']) {
                float_window.title("错误")
                float_window.content(`${res['error']}`)
                float_window.open()
            }
        }).catch(err => {
            float_window.title("错误")
            float_window.content(`${err.message}`)
            float_window.open()
        })
    }
})



// 
// 添加作品GUI
// 
const open_add_story = () => {
    float_window.title("作品添加")

    HTML = `
        <div class="add_story">
            <input type="text" placeholder="请输入你想添加的作品名">
            <button onclick="search_work()">&emsp;搜索&emsp;</button>
            <span style="color:#333" hidden>请点击下边作品进行添加</span>

            <div class="split"></div>
            <div class="limit"></div>
        </div>
    `
    float_window.content(HTML)
    float_window.open()
}



// 
// 搜索作品
// 
const search_work = () => {
    // 请求锁，防止过量请求
    if (small_lock()) {
        return
    }

    const stories_region = document.querySelector(".add_story .limit")

    // 获取搜索关键词
    kw = document.querySelector(".add_story input").value

    fetch_API("GET", `${API}/search/${kw}/space`).then(res => {
        if (res['error']) {
            float_window.title("错误")
            float_window.content(`${res['error']}`)
            float_window.open()
            return
        }

        res['data'].forEach((topic, i) => {
            HTML = `
                <div class="result" onclick="add_story(${topic['tid']})" title="点击添加">
                    <img src="${topic['url']}/${topic['tid']}/preview.avif" title="点击添加" alt="作品封面">
                    <p>${topic['title']}</p>
                </div>
            `
            stories_region.insertAdjacentHTML("beforeend", HTML)
        })
    }).catch(err => {
        float_window.title("错误")
        float_window.content(`${err.message}`)
        float_window.open()
    })
}



// 
// 添加作品
// 
const add_story = (tid) => {
    // 请求锁，防止过量请求
    if (small_lock()) {
        return
    }

    var data = {
        tid: tid
    }

    fetch_API("POST", `${API}/user/stories`, data).then(res => {
        if (res['error']) {
            float_window.title("错误")
            float_window.content(`${res['error']}`)
            float_window.open()
            return
        }

        if (res['data']) {
            float_window.title("提示")
            float_window.content(`添加成功，3秒钟后自动刷新！`)
            float_window.open()
			setTimeout(() => {
				location.reload()
			}, 3000)

        }
    }).catch(err => {
        float_window.title("错误")
        float_window.content(`${err.message}`)
        float_window.open()
    })
}



// 
// 删除作品
// 
const remove_work = (tid) => {
    var data = {
        tid: tid
    }

    fetch_API("DELETE", `${API}/user/stories`, data).then(res => {
        if (res['error']) {
            float_window.title("错误")
            float_window.content(`${res['error']}`)
            float_window.open()
        }

        if (res['data']) {
            float_window.title("提示")
            float_window.content(`${res['data']}`)
            float_window.open()

            document.querySelector(`#_${tid}`).remove()
        }
    }).catch(err => {
        float_window.title("错误")
        float_window.content(`${err.message}`)
        float_window.open()
    })
}


