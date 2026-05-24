// 
// 遣返
// 
if (LOGIN == false) {
	float_window.title("提示")
	float_window.content("未登录，用户管理页面对您没用，3秒后自动返回主页")
	float_window.open()
	setTimeout(() => {
		window.location.href = "/"
	}, 3000)
}



// 
// 请求用户数据
// 
function request_user_data() {
    fetch_API("GET", `${API}/user/${UID}`).then(res => {
        document.querySelector(".avatar_big").src = res['data']['user']['avatar_big']
        document.querySelector("#sign").value = res['data']['user']['sign']
        document.querySelector("#sign_img").value = res['data']['user']['sign_img']
        document.querySelector("#best_love_story").value = res['data']['user']['best_love_story']
        document.querySelector("#playing_story").value = res['data']['user']['playing_story']
        document.querySelector("#recommend_stories").value = res['data']['user']['recommend_stories']

    }).catch(err => {
        console.log(`用户数据请求失败: ${err.message}`);
        setTimeout(request_user_data, 1000);
    });
}

if (LOGIN == true) {
    request_user_data()
}



// 
// 请求邮箱
// 
function request_email() {
    fetch_API("GET", `${API}/user/email`).then(res => {
        document.querySelector("#email").value = res['data']
    }).catch(err => {
        console.log(`邮箱请求失败: ${err.message}`);
        setTimeout(request_email, 1000);
    });
}

if (LOGIN == true) {
    request_email()
}



// 
// 更新邮箱
// 
const update_email = () => {
    const email = document.getElementById('email').value

    if (!email) {
        float_window.title("提示")
        float_window.content("邮箱未填入！")
        float_window.open()
        return
    }

    if (!email.includes("@")) {
        float_window.title("提示")
        float_window.content("邮箱不合法！")
        float_window.open()
        return
    }

    var data = {
        email: email
    }

    fetch_API("PUT", `${API}/user/email`, {}, data).then(res => {
        float_window.title("提示")
        float_window.content(`${res['data']}`)
        float_window.open()

    }).catch(err => {
        float_window.title("错误")
        float_window.content(`${err.message}`)
        float_window.open()
    })
}



// 
// 更改用户名
// 
const update_uname = () => {
    float_window.create()
    float_window.title("更改用户名")
    float_window.content(`
        <input type="text" id="new_uname" placeholder="请输入新的用户名！" value="">
        <button onclick="submit_uname()">提交</button>
        `)
    float_window.open()
}
const submit_uname = () => {
    var uname = document.querySelector("#new_uname").value
    if (!uname) {
        alert("用户名不能为空！")
        return
    }

    fetch_API("PUT", `${API}/user/uname`, {}, {uname: uname}).then(res => {
        float_window.title("提示")
        float_window.content(`${res['data']}`)
        float_window.open()

    }).catch(err => {
        float_window.title("错误")
        float_window.content(`${err.message}`)
        float_window.open()
    })
}



// 
// 故事集数据更新
// 
const user_data_update = () => {
    const sign = document.getElementById('sign').value
    const sign_img = document.getElementById('sign_img').value
    const best_love_story = document.getElementById('best_love_story').value
    const playing_story = document.getElementById('playing_story').value
    const recommend_stories = document.getElementById('recommend_stories').value

    var data = {
        sign: sign,
        sign_img:sign_img,
        best_love_story: best_love_story,
        playing_story: playing_story,
        recommend_stories: recommend_stories
    }

    fetch_API("PUT", `${API}/user/update`, {}, data).then(res => {
        float_window.title("提示")
        float_window.content(`${res['data']}`)
        float_window.open()

    }).catch(err => {
        float_window.title("错误")
        float_window.content(`${err.message}`)
        float_window.open()
    })
}


// 
// 更新头像
// 
const replace_avatar = () => {
    float_window.title("更换头像")
    HTML = `
        <div id="uploadBox" hidden>
            <label for="upload" id="uploadLabel">点击选择图片</label>
            <input type="file" accept="image/*" id="upload">
        </div>


        <div>
            <div id="previewWrapper">
                <img id="preview" name="预览头像" style="width: 100%">
                <div id="selectionBox">
                    <div id="resizeHandle"></div>
                </div>
            </div>
            <button onclick="select_img()">选择图片</button>
            <button id="downloadButton">上传头像</button>
        </div>

        <div id="avatar_upload_state"></div>
    `
    float_window.content(HTML)
    float_window.open()
    select_avatar()
}



// 
// 更改密码
// 
const replace_psw = () => {
    float_window.title("修改密码")
    float_window.content(`
        <input type="password" id="new_psw" placeholder="请输入新密码" autocomplete="current-password" required>
        <button onclick="submit_psw()">提交新密码</button>
    `)
    float_window.open()
}
const submit_psw = () => {
    var psw = document.querySelector("#new_psw").value
    if (!psw) {
        float_window.title("提示")
        float_window.content(`密码不能为空！`)
        float_window.open()
    }

    fetch_API("PUT", `${API}/user/psw`, {}, {psw: md5(psw)}).then(res => {
        float_window.title("提示")
        float_window.content(`${res['data']}`)
        float_window.open()

    }).catch(err => {
        float_window.title("错误")
        float_window.content(`${err.message}`)
        float_window.open()
    })
}






// 
// 头像上传相关
// 
const select_img = () => {
    document.getElementById("upload").click()
}
const select_avatar = () => {
    const preview = document.getElementById('preview');
    const selectionBox = document.getElementById('selectionBox');
    const resizeHandle = document.getElementById('resizeHandle');
    const downloadButton = document.getElementById('downloadButton');

    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let startWidth = 0;
    let startHeight = 0;

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
        const wrapperWidth = document.getElementById('previewWrapper').clientWidth;
        const wrapperHeight = document.getElementById('previewWrapper').clientHeight;
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
        document.querySelector("#avatar_upload_state").textContent = "正在上传中，请耐心等待！"

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

            var data = new FormData();
            data.append(
                "file",
                blob,
                "avatar.jpg"
            );

            fetch_API("POST", `${API}/user/avatar`, {}, data).then(res => {
                if (res['error']) {
                    float_window.title("错误")
                    float_window.content(`${res['error']}`)
                    float_window.open()
                    return
                }

                document.querySelector("#avatar_upload_state").textContent = "上传完成！"
                float_window.title("提示")
                float_window.content(`${res['data']}`)
                float_window.open()

            }).catch(err => {
                float_window.title("错误")
                float_window.content(`${err.message}`)
                float_window.open()
            })





            // const result = await fetch_API(
            //     "POST",
            //     "/api/avatar",
            //     {},
            //     formData
            // );
        }, 'image/jpeg', 1)
    }


    // 绑定选择文件事件
    document.getElementById('upload').addEventListener('change', function(e) {
        const file = e.target.files[0];

        if (file && file.type.startsWith('image/')) {
            displayCroppedImage(file);
        }
    });

    // 绑定下载头像事件
    downloadButton.addEventListener('click', downloadAvatar);

    // 绑定选择框和缩放手柄事件
    bindSelectionEvents();
}
