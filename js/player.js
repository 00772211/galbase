// 判断有没有历史播放记录
if (!get_cookie("playlist")) {
    set_cookie("playlist", "1|1|1|1|1|1|1|1|1|{1}")
    set_cookie("bgm_volume", "1")
}

// 二开自定义DOM
let music_cover = document.querySelector(".play_box .cover");
let proLine = document.querySelector(".pros");
let volumeBtn = document.querySelector(".volume");
let volumeBar = document.querySelector(".volume_range");
let volumePro = document.querySelector(".range");
let music_ico = document.querySelector(".state_set")
let music_source = document.querySelector("audio");
let timeshow = document.querySelector(".time_show");
let music_last = document.querySelector(".last");
let music_next = document.querySelector(".next");


// 新定义全局变量
let ico_rotate = 0           // 封面旋转角
let music_state = 'none'     // 音乐播放状态
let music_playing_time = 0   // 当前歌曲播放时长
let isDragging = false;      // 拖动状态
let wasPlaying = false;      // 拖动前的播放状态
let isMuted = false;         // 静音状态
let ico_rotate_state = null; // 



// 当前的网页加载完毕的时候触发的方法
window.onload = function(){
    musicMove()
    volumeSet()
}



// 音乐名过长自动滚动
const scroll_text = () => {
    var target = document.querySelector(".music_info")
    var move = target.scrollWidth / 2

    // 设置过渡动画属性
    target.style.transition = 'transform 20s linear';

    // 强制浏览器重绘，确保过渡生效
    target.offsetHeight;

    // 应用向左移动的变换
    target.style.transform = `translateX(-${move}px)`;

    // 监听动画结束
    target.addEventListener('transitionend', () => {
        target.style.transition = 'none';
        target.style.transform = ''
    })

    setTimeout(scroll_text);
}



// 
// 请求新音乐
// 
const request_music = () => {

    // 请求锁，防止过量请求
    if (small_lock()) {
        return
    }

    // 清除之前的旋转定时器，防止累加
    clearInterval(ico_rotate_state);

    var data = { "cmd": "request_music" };
    xhr("/server.php", data).then((result) => {
        var result = JSON.parse(result);

        // 变更播放历史
        var playlist = get_cookie("playlist").replace("=", "")
        set_cookie("playlist", playlist.split('|').slice(1).concat(result['mid'] + "=").join('|'))        

        // 更新播放状态
        music_state = "playing";
        music_ico.className = "state_set iconfont icon-24gf-pause2";

        // 更新音乐信息、资源和封面
        document.querySelector(".music_info").textContent = `${result['name']} (歌手：${result['artist']}) `;
        document.querySelector("#bgm").src = `/data/animes/${result['chunk']}/music/${result['mid']}.mp3`;
        document.querySelector(".play_box .state .cover").src = `/data/animes/${result['chunk']}/music/${result['mid']}.jpg`;
        document.querySelector(".play_box .bc img").src = `/data/animes/${result['chunk']}/music/${result['mid']}.jpg`;

        // 播放音乐
        music_source.play();

        // 重置旋转角度
        ico_rotate = 0;
        music_cover.style.transform = `rotate(${ico_rotate}deg)`;

        // 开启新的旋转定时器
        ico_rotate_state = setInterval(() => {
            ico_rotate++;
            music_cover.style.transform = `rotate(${ico_rotate}deg)`;
        }, 40)

        // 音乐名过长自动滚动
        document.querySelectorAll('.music_info').forEach(el => {
            if (el.scrollWidth > el.parentElement.clientWidth) {
                // 左侧对齐
                el.style.left = '0px'

                // 复制一遍文本
                el.textContent += el.textContent;
                scroll_text()
            }
        })
    })
}



// 
// 播放特定mid音乐
// 
const request_target_music = (mid) => {
    // 清除之前的旋转定时器，防止累加
    clearInterval(ico_rotate_state);

    var data = {
        "cmd": "request_target_music",
        "mid": mid
    };
    xhr("/server.php", data).then((result) => {
        var result = JSON.parse(result);

        // 更新播放状态
        music_state = "playing";
        music_ico.className = "state_set iconfont icon-24gf-pause2";

        // 更新音乐信息、资源和封面
        document.querySelector(".music_info").textContent = `${result['name']} (歌手：${result['artist']}) `;
        document.querySelector("#bgm").src = `/data/animes/${result['chunk']}/music/${result['mid']}.mp3`;
        document.querySelector(".play_box .state .cover").src = `/data/animes/${result['chunk']}/music/${result['mid']}.jpg`;
        document.querySelector(".play_box .bc img").src = `/data/animes/${result['chunk']}/music/${result['mid']}.jpg`;

        // 播放音乐
        music_source.play();

        // 重置旋转角度
        ico_rotate = 0;
        music_cover.style.transform = `rotate(${ico_rotate}deg)`;

        // 开启新的旋转定时器
        ico_rotate_state = setInterval(() => {
            ico_rotate++;
            music_cover.style.transform = `rotate(${ico_rotate}deg)`;
        }, 40)

        // 音乐名过长自动滚动
        document.querySelectorAll('.music_info').forEach(el => {
            if (el.scrollWidth > el.parentElement.clientWidth) {
                // 左侧对齐
                el.style.left = '0px'

                // 复制一遍文本
                el.textContent += el.textContent;
                scroll_text()
            }
        })
    })
}











// 
// 音乐的开始和暂停方法
// 
music_ico.onclick = function() {
    if (music_state == "none") {
        request_music();
    } else {

        // 如果是暂停
        if (music_source.paused) {
            music_source.play();
            // 启动旋转定时器前先清除旧的
            clearInterval(ico_rotate_state);
            ico_rotate_state = setInterval(() => {
                ico_rotate++;
                music_cover.style.transform = `rotate(${ico_rotate}deg)`;
            }, 40);
            music_state = "playing";
            music_ico.className = "state_set iconfont icon-24gf-pause2";
        
        // 如果在播放中
        } else {
            music_source.pause();
            clearInterval(ico_rotate_state);
            music_state = "stop";
            music_ico.className = "state_set iconfont icon-bofang";
        }
    }
}



// 
// 辅助函数：时间补零格式化（如 5 → "05"）
// 
function formatTime(time) {
    return time < 10 ? `0${time}` : time;
}



// 
// 通过 timeupdate 事件实时更新时间和进度条，1s一次
// 
music_source.addEventListener('timeupdate', () => {
    if (!music_source.duration || isDragging) return; // 拖动时不自动更新
    
    // 计算时长
    const current_min = Math.floor(music_source.currentTime / 60)
    const current_sec = Math.floor(music_source.currentTime % 60)
    const total_min = Math.floor(music_source.duration / 60)
    const total_sec = Math.floor(music_source.duration % 60)

    // 更新时间显示
    timeshow.textContent = `${formatTime(current_min)}:${formatTime(current_sec)} / ${formatTime(total_min)}:${formatTime(total_sec)}`

    // 进度条更新
    const progressPercent = (music_source.currentTime / music_source.duration) * 100;
    proLine.style.width = `${progressPercent}%`;
});



// 
// 监听音频播放结束
// 
music_source.addEventListener('ended', () => {
    clearInterval(ico_rotate_state);
    ico_rotate = 0;
    music_cover.style.transform = `rotate(0deg)`;
    request_music();
});


// 
// 音乐进度拖动方法
// 
function musicMove() {
    const progressBar = document.querySelector('.progress .line');

    // 鼠标按下事件
    progressBar.onmousedown = function(e) {
        wasPlaying = !music_source.paused;
        isDragging = true;
        
        // 暂停音乐并停止旋转
        music_source.pause();
        clearInterval(ico_rotate_state);
        updateProgress(e); // 立即更新位置
    };

    // 鼠标移动事件
    document.onmousemove = function(e) {
        if (!isDragging) return;
        updateProgress(e);
    };

    // 鼠标释放事件
    document.onmouseup = function() {
        if (!isDragging) return;
        isDragging = false;
        
        // 恢复播放状态
        if (wasPlaying) {
            music_source.play();

            // 重启封面旋转
            ico_rotate_state = setInterval(() => {
                ico_rotate++;
                music_cover.style.transform = `rotate(${ico_rotate}deg)`;
            }, 40);
        }
    };

    // 点击跳转事件
    progressBar.addEventListener('click', function(e) {
        if (isDragging) return;
        updateProgress(e);
    });

    // 统一更新进度方法
    function updateProgress(e) {
        const rect = progressBar.getBoundingClientRect();
        const clickX = e.clientX - rect.left;
        const percentage = Math.min(1, Math.max(0, clickX / rect.width));

        // 更新进度显示
        proLine.style.width = `${percentage * 100}%`;
        
        if (music_source.duration) {
            music_source.currentTime = percentage * music_source.duration;
        }
    }
}



// 
// 音量调整
// 
function volumeSet() {
    // 读取存储的音量值，如果不存在则默认1
    let storedVolume = parseFloat(get_cookie("bgm_volume"));
    if (isNaN(storedVolume)) storedVolume = 1;

    // 设置播放器的音量和滑块的位置
    music_source.volume = storedVolume;
    volumePro.value = storedVolume * 100;

    // 根据音量值设置音量图标
    if (storedVolume === 0) {
        volumeBtn.className = "volume iconfont icon-volumeDisable";
    } else {
        volumeBtn.className = "volume iconfont icon-volumeMiddle";
    }
    
    // 标志位：记录刚刚结束拖动
    let justDragged = false;
    // 鼠标悬停显示音量条
    volumeBtn.onmouseenter = function() {
        volumeBar.style.height = "100px";
        volumeBar.style.padding = "5px";
        volumeBar.style.top = "-110px";
    }

    // 鼠标离开隐藏音量条
    volumeBtn.onmouseleave = function() {
        volumeBar.style.height = "0px";
        volumeBar.style.padding = "0px";
        volumeBar.style.top = "0px";
    }

    // 监听音量滑块的鼠标事件，记录拖动结束
    volumePro.addEventListener('mousedown', function(e) {
        // 拖动开始时可以重置标志
        justDragged = false;
    });

    volumePro.addEventListener('mouseup', function(e) {
        // 拖动结束后设置标志，并阻止事件冒泡
        justDragged = true;
        e.stopPropagation();
        // 用 setTimeout 在短暂延迟后清除标志，避免影响后续点击
        setTimeout(() => {
            justDragged = false;
        }, 50);
    });

    // 点击音量图标切换静音
    volumeBtn.onclick = function(e) {
        // 如果刚刚结束拖动，忽略此次点击
        if (justDragged) {
            // 清除标志并退出
            justDragged = false;
            return;
        }
        // 正常点击逻辑：切换静音
        if (isMuted || volumePro.value == 0) {
            isMuted = false;
            music_source.muted = false;
            volumePro.disabled = false;
            volumeBtn.className = "volume iconfont icon-volumeMiddle";
        } else {
            isMuted = true;
            music_source.muted = true;
            volumePro.disabled = true;
            volumeBtn.className = "volume iconfont icon-volumeDisable";
        }

        save_bgm_volume()
    }

    // 更新音量和图标的统一方法
    function updateVolume() {
        
        // 调整音量时自动取消静音
        isMuted = false;
        music_source.muted = false;
        volumePro.disabled = false;
        
        // 更新实际音量和图标
        music_source.volume = volumePro.value / 100;
        volumeBtn.className = `volume iconfont ${volumePro.value == 0 ? 'icon-volumeDisable' : 'icon-volumeMiddle'}`;

        save_bgm_volume()
    }

    // 监听音量滑块变化
    volumePro.addEventListener('input', updateVolume);
}


// 
// 音量更新储存
// 
const save_bgm_volume = () => {
    // 保存当前音量到Cookie
    set_cookie("bgm_volume", music_source.volume.toString());
}



// 
// 上一首
// 
music_last.onclick = function() {
    playlist = get_cookie("playlist").split("|")

    // 循环每个值
    for (let index = 0; index < playlist.length; index++) {

        // 包含 = 代表正在播放
        if (playlist[index].includes("=")) {

            // 没有上一首了
            if (index == 0) {
                alert("没有上一首啦！就10首记录而已！")
                break
            }

            // 请求锁，防止过量请求
            if (small_lock()) {
                return
            }

            // 获取上一首播放的mid，并添加正在播放符号
            last_mid = playlist[index - 1]

            // 取消正在播放的 = 符号
            playlist[index] = playlist[index].replace("=", "")
            
            // 修正真正播放的
            playlist[index - 1] = `${last_mid}=`

            request_target_music(last_mid)
            break
        }
    }

    set_cookie("playlist", playlist.join("|"))
}

// 下一首
music_next.onclick = function() {
    
    playlist = get_cookie("playlist").split("|")

    // 循环每个值
    for (let index = 0; index < playlist.length; index++) {

        // 包含 = 代表正在播放
        if (playlist[index].includes("=")) {

            // 没有下一首了，请求个新歌
            if (index == 9) {
                request_music()
                break
            }

            // 请求锁，防止过量请求
            if (small_lock()) {
                return
            }

            // 获取下一首播放的mid，并添加正在播放符号
            next_mid = playlist[index + 1]

            // 取消正在播放的 = 符号
            playlist[index] = playlist[index].replace("=", "")
            
            // 修正真正播放的
            playlist[index + 1] = `${next_mid}=`

            request_target_music(next_mid)
            break
        }
    }

    set_cookie("playlist", playlist.join("|"))
}



// 
// 若在帖子页，添加到回复区
// 
// const full_url = window.location.href
// if (full_url.includes("/topic/")) {
    
// }
