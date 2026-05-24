
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

    var data = {
    	"cmd": "update_uname",
    	"uname": uname
    }
    xhr("/servers/user_admin.php", data).then((result) => {
        console.log(result);
        
    	if (result) {
            float_window.content(`用户名更改成功！F5刷新后显示！`)
        } else {
            float_window.content(`用户名更改失败！可能是网络问题！再试试呗！还不行就发帖联系管理员！`)
        }
    })

}

