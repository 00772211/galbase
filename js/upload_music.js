    // 将Base64字符串转换为Blob对象
    function base64ToBlob(base64) {
        const parts = base64.split(';base64,');
        if (parts.length !== 2) {
            throw new Error('无效的Base64字符串');
        }
        const mimeType = parts[0].split(':')[1];
        const rawData = atob(parts[1]);
        const uintArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; i++) {
            uintArray[i] = rawData.charCodeAt(i);
        }
        return new Blob([uintArray], { type: mimeType });
    }

    // 封装获取时长的函数
    function getDuration(file) {
        return new Promise((resolve) => {
            const url = URL.createObjectURL(file);
            const audio = new Audio(url);
            
            audio.addEventListener('loadedmetadata', () => {
                URL.revokeObjectURL(url);
                resolve(audio.duration);
            });
        });
    }

    // 监听input
    document.querySelector("#music_upload").onchange = function (e) {

        // 获取音乐文件
        const file = e.target.files[0];
        const coverArtDiv = document.getElementById('coverArt');

        // 获取元数据
        jsmediatags.read(file, {
            onSuccess: (tag) => {
                document.querySelector("#music_info").hidden = false
                const { title, artist, album } = tag.tags;
                document.querySelector("#music_info .name").innerHTML = `歌曲名：${title}`
                document.querySelector("#music_info .artist").innerHTML = `歌手：${artist}`
                document.querySelector("#music_info .album").innerHTML = `专辑：${album}`

                // 获取时长，异步
                getDuration(file).then(duration => {
                    document.querySelector("#music_info .time").innerHTML = `时长：${duration}秒`

                    // 新增封面处理逻辑
                    const picture = tag.tags.picture;
                    if (picture) {
                        // 将二进制数据转换为Base64字符串
                        let base64String = "";
                        for (let i = 0; i < picture.data.length; i++) {
                            base64String += String.fromCharCode(picture.data[i]);
                        }
                        const base64 = `data:${picture.format};base64,${window.btoa(base64String)}`;

                        // 显示封面
                        coverArtDiv.innerHTML = `<img src="${base64}" alt="专辑封面" style="max-width: 300px;">`;

                        float_window.create()
                        float_window.title("音乐上传")
                        float_window.content(`音乐上传中，请耐心等待…`)
                        float_window.open()

                        // 转换为Blob并上传到服务器
                        const blob = base64ToBlob(base64);
                        var data = new FormData()
                        data.append('cmd', "upload_music");
                        data.append('name', title);
                        data.append('artist', artist);
                        data.append('album', album);
                        data.append('time', duration);
                        data.append('cover', blob);
                        data.append('music', file);

                        // 构建xhr
                        var xhr = new XMLHttpRequest()
                        xhr.open('POST', "/data/server.php", true)
                        xhr.send(data)

                        xhr.onreadystatechange = () => {
                            if(xhr.readyState == 4 && xhr.status == 200){
                                float_window.content(`上传成功，音乐已自动归类为默认歌单：<a href="/topic/1081" target="_blank">默认歌单</a>`)
                            }
                        }

                        // xhr请求超时
                        xhr.timeout = 40000	// ms
                        xhr.ontimeout = () => alert("请求超时")
                        
                    } else {
                        coverArtDiv.innerHTML = "未找到专辑封面";
                    }
                })
            },
            onError: (error) => alert('元数据读取失败:', error)
        })
    }