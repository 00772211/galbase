<?php
	// 连接数据库
	require_once dirname(dirname(__FILE__)).'/conn.php';

	// 连接外部函数
	require_once dirname(dirname(__FILE__)).'/functions.php';

	if(!$_POST['cmd']) {
		exit("cmd参数不存在");
	}

	switch ($_POST['cmd']) {

		// 不推送拔作
		case 'no_push':
			$state = $_POST['state'];
			$uid = get_uid();

			if ($state == "true") {
				user_config($uid, "no_push", 1);
			} else {
				user_config($uid, "no_push", 0);
			}
			break;



		// 只推送拔作
		case 'only_H':
			$state = $_POST['state'];
			$uid = get_uid();

			if ($state == "true") {
				user_config($uid, "only_H", 1);
			} else {
				user_config($uid, "only_H", 0);
			}
			break;
	}
