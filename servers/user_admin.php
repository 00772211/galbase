<?php
	// 连接数据库
	require_once dirname(dirname(__FILE__)).'/conn.php';

	// 连接外部函数
	require_once dirname(dirname(__FILE__)).'/functions.php';

	if(!$_POST['cmd']) {
		exit("cmd参数不存在");
	}

	switch ($_POST['cmd']) {
		// 
		// 更新邮箱
		// 
		case 'update_email':
			$email = $_POST['email'];
			$uid = get_uid();

			// 存在，修改
			if (mysql_exist("users_email", "uid", "$uid")) {
				mysqli_query($link, "UPDATE users_email SET email='$email' WHERE uid=$uid LIMIT 1;");

			// 不存在，增加
			} else {
				mysqli_query($link, "INSERT INTO `users_email` (uid, email) VALUES ('$uid', '$email');");
			}
			break;

		// 
		// 更新新用户名
		// 
		case 'update_uname':
			$uname = $_POST['uname'];
			$uid = get_uid();
			$old_uname = get_uname($uid);
			$uid_last_char = substr($uid, -1);

			mysqli_query($link, "UPDATE `users_info_$uid_last_char` SET uname=\"$uname\" WHERE uid=$uid LIMIT 1; ");
			echo TRUE;

			log_add($uid, "$old_uname 更改了新用户名：\$user");
			break;
	}
