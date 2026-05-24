// function time_diff(time) {
// 	// 获取当前时间
// 	var current_time = new Date()

// 	// 获取给定时间字符串的时间
// 	var send_time = new Date(time)

// 	// 计算时间差（单位为毫秒）
// 	var time_diff = send_time.getTime() - current_time.getTime()

// 	// 将时间差转换为天数
// 	var time_diff = Math.ceil(time_diff / (1000 * 60 * 60 * 24))

// 	// 取绝对值
// 	var time_diff = Math.abs(time_diff)

// 	return time_diff
// }


function time_diff(timeStr) {
  // 使用上海时区
  const timeZone = 'Asia/Shanghai';

  // 尝试多种日期格式解析
  let date = new Date(timeStr);
  if (isNaN(date)) {
    // 如果没有时间部分，加上00:00
    date = new Date(timeStr + 'T00:00:00');
  }

  // 当前时间（上海时区）
  const now = new Date(new Date().toLocaleString('en-US', { timeZone }));

  // 输入时间（调整为上海时区）
  const inputTime = new Date(date.toLocaleString('en-US', { timeZone }));

  const diffMs = now - inputTime;
  const diffHours = diffMs / 1000 / 3600;

  if (diffHours < 0) return "未来时间";

  // 判断日期是否是同一天
  const nowDate = now.toISOString().split('T')[0];
  const inputDate = inputTime.toISOString().split('T')[0];
  if (nowDate === inputDate) return "今天";

  // 转换单位
  if (diffHours > 365 * 24) {
    const years = Math.floor(diffHours / (365 * 24));
    return `${years}年前`;
  } else if (diffHours > 30 * 24) {
    const months = Math.floor(diffHours / (30 * 24));
    return `${months}个月前`;
  } else if (diffHours > 48) {
    const days = Math.floor(diffHours / 24);
    return `${days}天前`;
  } else if (diffHours >= 24) {
    return "1天前";
  } else {
    const hours = Math.floor(diffHours);
    return `${hours}小时前`;
  }
}