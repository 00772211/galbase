// 
// 全局变量
// 
const PATH = window.location.pathname.split("/")
var TYPE = PATH[2]
var HTML

const DOM_state = document.querySelector("#search_state")
const DOM_content = document.querySelector("#search_content")
const DOM_msgs = document.querySelector(".msgs")
const DOM_type = document.querySelector("#type")



// 
// 隐藏header中的搜索框
// 
document.querySelector(".header .search").hidden = true



// 
// 复现HTML
// 
DOM_content.value = decodeURIComponent(PATH[3])
document.querySelector("#type").value = TYPE



// 
// 搜索指南
// 
const GUI_search_guide = () => {
    float_window.title("指南")
    float_window.content(`
            TAG搜索最为特殊，支持多个tag。<br>
            和查询：用“,”符号，如查询“夏日”和“海岛”则需要搜索：“夏日,恋爱”。<br>
            或查询：用“|”符号，如查询“夏日”或“海岛”则需要搜索：“夏日|恋爱”。<br>
            会社查询也支持“和查询”和“或查询”。
    `)
    float_window.open()

}



// 
// 主动搜索
// 
const search_full = () => {
    // 请求锁，防止过量请求
    if (lock()) {
        return
    }

    TYPE = DOM_type.value
    var content = DOM_content.value
    
    if (!content) {
        float_window.title("提示")
        float_window.content("搜索内容不能为空！")
        float_window.open()
        return
    }

    DOM_state.textContent = "系统正在查询…请耐心等待"

    fetch_API("GET", `${API}/search/${TYPE}/${content}`).then(res => {
        if (res['error']) {
            float_window.title("错误")
            float_window.content(`${res['error']}`)
            float_window.open()
            DOM_state.textContent = `查询出错`
            return
        }

        DOM_msgs.innerHTML = ""

        // 按date排序
        res.data.topic.sort((a, b) => new Date(b.date) - new Date(a.date));

        res['data']['topic'].forEach((topic, i) => {
            if (topic['contain'] == "title") {
                HTML = `
                    <li>
                        <span class='tag tag3'>标题包含</span> ${topic['date']} -> <a href='/space/${topic['uid']}' target='_blank'>${res['data']['unames'][topic['uid']]}(${topic['uid']})</a> 在板块 ${res['data']['board'][topic['fid']]} 发帖 <a href='/topic/${topic['tid']}' target='_blank'>${topic['title']}</a>
                    </li>
                `

            } else if (topic['contain'] == "content") {
                HTML = `
                    <li>
                        <span class='tag'>内容包含</span> ${topic['date']} -> <a href='/space/${topic['uid']}' target='_blank'>${res['data']['unames'][topic['uid']]}(${topic['uid']})</a> 在板块 ${res['data']['board'][topic['fid']]} 发帖 <a href='/topic/${topic['tid']}' target='_blank'>${topic['title']}</a>
                    </li>
                `

            } else if (topic['contain'] == "tag" || topic['contain'] == "developer") {
                var tags = topic['tags'].split("|")

                var tags_HTML = ``
                tags.forEach((id, i) => {
                    tag = res['data']['tags_decode'][id]
                    tags_HTML += `<span class='tag'>${tag}</span>`
                })
                
                HTML = `
                    <li>
                        ${topic['date']} -> <a href='/space/${topic['uid']}' target='_blank'>${res['data']['unames'][topic['uid']]}(${topic['uid']})</a> 在板块 ${res['data']['board'][topic['fid']]} 发帖 <a href='/topic/${topic['tid']}' target='_blank'>${topic['title']}</a> -> ${tags_HTML}
                    </li>
                `
            }
            DOM_msgs.insertAdjacentHTML("beforeend", HTML)
        })

        var count = res['data']['topic'].length
        DOM_state.textContent = `搜索完成，共${count}个内容`

    }).catch(err => {
		console.log(`搜索请求失败: ${err.message}`);
		setTimeout(search_full, 1000);
    })
}
search_full()
