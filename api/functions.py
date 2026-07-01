
import asyncmy
from fastapi import HTTPException, Request, Depends
from dependencies import *
import dependencies
from datetime import datetime
from zoneinfo import ZoneInfo
from pathlib import Path as PATH
import random
import json
import hashlib
from PIL import Image
import pillow_avif
from bs4 import BeautifulSoup
import requests
from urllib.parse import urljoin
import shutil
import os
import time
import anyio
import subprocess
import zipfile


def success(data: dict):
    return {"error": "", "data": data}

def fail(msg: str):
    return {"error": msg, "data": None}


# 查
async def db_fetchone(pool, query, params=()):
    async with pool.acquire() as conn:
        async with conn.cursor(asyncmy.cursors.DictCursor) as cur:
            await cur.execute(query, params)
            return await cur.fetchone()

# 增
async def db_insert(pool, query, params=()):
    async with pool.acquire() as conn:
        async with conn.cursor() as cur:  # 不需要 DictCursor
            await cur.execute(query, params)
            await conn.commit()           # 提交事务
            return cur.lastrowid          # 返回自增ID（如果有）

# 增 多行
async def db_insert_many(pool, query, params_list):
    if not params_list:
        return 0

    async with pool.acquire() as conn:
        async with conn.cursor() as cur:

            # 每一行的 (%s,%s,%s)
            row_placeholder = "(" + ",".join(["%s"] * len(params_list[0])) + ")"

            # 拼接所有行
            values_sql = ",".join([row_placeholder] * len(params_list))

            sql = query.strip() + " VALUES " + values_sql

            # flatten 参数
            flat_params = [item for row in params_list for item in row]

            await cur.execute(sql, flat_params)
            await conn.commit()

            return cur.rowcount

# 删 改
async def db_update(pool, query, params=()):
    async with pool.acquire() as conn:
        async with conn.cursor() as cur:
            await cur.execute(query, params)
            await conn.commit()
            return cur.rowcount  # 返回受影响行数

# 多行
async def db_rows(pool, query, params=()):
    async with pool.acquire() as conn:
        async with conn.cursor(asyncmy.cursors.DictCursor) as cur:
            await cur.execute(query, params)
            return await cur.fetchall()

# 查 改
async def db_auto_increment_value(pool, fetch: str):
    async with pool.acquire() as conn:
        async with conn.cursor() as cur:
            await cur.execute("""
                UPDATE sys_auto_increment_value
                SET value = LAST_INSERT_ID(value + 1)
                WHERE variable=%s
            """, (fetch, ))

            await cur.execute(
                "SELECT LAST_INSERT_ID()"
            )
            new_id = (await cur.fetchone())[0]
            await conn.commit()
            return new_id



# 
# 空闲处理
# 
async def idle_checker():
    config = await get_config()
    if config['localhost'] == True:
        return

    while True:
        await anyio.sleep(60)
        now = int(time.time())
        if now - dependencies.last_request > 120:  # 3分钟
            await get_touchgal_topic()

            # 防止重复触发
            dependencies.last_request = int(time.time())



async def chunk(config, tid: int, type: str = "data", onlyID: bool = False) -> str:
    """
    获取指定 tid 所在的分块

    :config: config依赖注入
    :tid: 帖子ID
    :type: 默认为data，其他可传参anime bgm
    :onlyID: 是否只返回ID，默认False
    """
    if tid < 0:
        return None


    if type == "data":
        for i in range(1, 100):
            min = config['chunk_range'][f'data{i}'][0]
            max = config['chunk_range'][f'data{i}'][1]
            if tid >= min and tid <= max:
                if onlyID == True:
                    return i
                else:
                    return (config['chunk'][f'data{i}'])

    elif type == "anime":
        if onlyID == True:
            return 1
        else:
            return (config['chunk']['anime1'])



async def get_uid(request: Request, rds = Depends(get_redis), pool = Depends(get_db)) -> int:
    """
    获取用户uid

    :request: req
    :rds: redis
    :pool: 数据库连接池
    """

    # 从 cookie 获取 sessionID
    sessionID = request.cookies.get("sessionID")
    if not sessionID:
        raise HTTPException(status_code=401, detail="未登录")

    # 尝试从 Redis 缓存获取用户信息
    uid = await rds.get(f"session:{sessionID}")
    if uid:
        return int(uid)

    # 从数据库里查uid
    row = await db_fetchone(pool, "SELECT uid FROM users_sessions WHERE sessionID=%s", (sessionID,))
    if not row:
        raise HTTPException(status_code=401, detail="登录过期")
    uid = row["uid"]

    # 缓存进redis
    await rds.set(f"session:{sessionID}", uid, ex=1800)
    return uid






async def get_uid_by_sessionID(sessionID: str, rds, pool) -> int:
    """
    根据sessionID获取uid

    :request: req
    :rds: redis
    :pool: 数据库连接池
    """
    # 尝试从 Redis 缓存获取用户信息
    uid = await rds.get(f"uid:{sessionID}")
    if uid:
        return int(uid)

    # 从数据库里查uid
    row = await db_fetchone(pool, "SELECT uid FROM users_sessions WHERE sessionID=%s", (sessionID,))
    if not row:
        raise HTTPException(status_code=401, detail="登录过期")
    uid = row["uid"]

    # 缓存进redsi
    await rds.set(f"uid:{sessionID}", uid, ex=1800)
    return uid



async def get_date(fetch: str = "") -> str:
    """
    获取上海时间

    :fetch: 可以填year month day，all，不填则返回2022-22-22
    """
    shanghai_time = datetime.now(ZoneInfo("Asia/Shanghai"))

    if fetch == "year":
        return str(shanghai_time.year)
    elif fetch == "month":
        return str(shanghai_time.month)
    elif fetch == "day":
        return str(shanghai_time.day)
    elif fetch == "all":
        return shanghai_time.strftime("%Y-%m-%d %H:%M")

    # 默认返回完整日期
    return shanghai_time.strftime("%Y-%m-%d")


async def log_add_source(content: str):
    """
    日志追加，原始文本

    :content: 日志添加内容
    """
    config = await get_config()

    year = await get_date("year")
    path = PATH(config['path']) / "data/logs/logs_{}.log".format(year)
    with open(path, "a", encoding="utf-8") as f:
        f.write(f"{content}\n")



async def log_add(pool, rds, content: str, sessionID: str = None, finger: str = None):
    """
    日志追加

    :content: 日志添加内容
    :sessionID: 用户的sessionID
    :finger: 用户的finger，注：sessionID得和finger一起传，不能只传一个
    """
    config = await get_config()

    year = await get_date("year")
    date = await get_date("all")
    path = PATH(config['path']) / "data/logs/logs_{}.log".format(year)

    # 默认存储
    if sessionID == None and finger == None:
        with open(path, "a", encoding="utf-8") as f:
            f.write(f"{date} {content}\n")

    # 带有sessionID或者finger的存储
    else:
        if not sessionID:
            with open(path, "a", encoding="utf-8") as f:
                f.write(f"{date} {finger} {content}\n")
        else:
            uid = await get_uid_by_sessionID(sessionID, rds, pool)
            with open(path, "a", encoding="utf-8") as f:
                f.write(f"{date} {uid} {content}\n")



async def msg_add(pool, uid: int, content: str, tid: int = None, target_uid: int = None):
    """
    个人信息通知添加

    :pool: 数据库连接池
    :uid: 目标uid
    :content: 需要添加的内容，若有$title则需填入tid，若有$user则需要填入target_uid
    :tid: 帖子ID
    :target_uid: 目标uid
    """
    # 是自己的话则不通知
    if target_uid == uid:
        return

    year = await get_date("year")
    date = await get_date()

    # 格式化$title
    if tid:
        fid = await get_fid(pool, tid)
        sharding = str(tid)[-1]
        row = await db_fetchone(pool, "SELECT title FROM `{}` WHERE tid=%s LIMIT 1".format(f"topics_{fid}_{sharding}"), (tid, ))
        title = row['title']
        content = content.replace("$title", f"<a href='/topic/{tid}' target='_blank'>{title}</a>")

    # 格式化$user
    if target_uid:
        uname = await get_uname(pool, target_uid)
        content = content.replace("$user", f"<a href='/space/{target_uid}'>{uname}({target_uid})</a>")

    table = f"logs_{year}"
    await db_insert(pool, "INSERT INTO {} (uid, date, content, `read`) VALUES (%s, %s, %s, 0)".format(table), (uid, date, content, ))
    return


async def get_fid(pool, tid: int, update: bool = False) -> str:
    """
    获取帖子所在的板块

    :pool: 数据库连接池
    :tid: 帖子ID
    :update: 是否获取最后更新时间戳，如果是True则返回2个值
    """
    rds = await get_redis()

    # 尝试从 Redis 缓存中获取
    fid = await rds.get(f"fid:{tid}{update}")
    if fid:
        return fid

    # 只获取fid
    if update == False:
        row = await db_fetchone(pool, "SELECT fid FROM topics_index WHERE tid=%s LIMIT 1", (tid, ))
        if not row:
            return None

        # 缓存进redsi
        await rds.set(f"fid:{tid}{update}", row['fid'], ex=1800)

        return row['fid']
    
    # 获取fid和last_modify
    else:
        row = await db_fetchone(pool, "SELECT fid, last_modify FROM topics_index WHERE tid=%s LIMIT 1", (tid, ))
        if not row:
            return None
        return row['fid'], row['last_modify']



async def get_topic(
        pool, 
        config,
        tid: int, 
        tags_decode: bool = False,
        field: str = "*",
        url_decode: bool = False,
        fid: str = None,
        add_view: bool = False
    ):
    """
    获取帖子所有信息

    :pool: 数据库连接池
    :config: config配置文件
    :tid: 帖子ID
    :tags_decode: 是否解析 tagID 为对应的中文    
    :field: 数据库中的 field 位置
    :url_decode: 是否解析出帖子的储存链接
    :fid: 是否传入fid，能减少数据库的读取量
    :add_view: 是否增加帖子浏览量，同上增加tags热度
    """
    # 初始化
    last_modify = None

    # 是否传入fid
    if not fid:
        fid, last_modify = await get_fid(pool, tid, True)

    # 查表
    sharding = str(tid)[-1]
    table = f"topics_{fid}_{sharding}"
    data = await db_fetchone(pool, "SELECT {} FROM `{}` WHERE tid=%s LIMIT 1".format(field, table), (tid, ))
    data['fid'] = fid

    if last_modify:
        data['last_modify'] = last_modify

    # 解析作者信息
    if 'uid' in data:
        auther_data = await get_user_old(pool, config, data['uid'])
        data['auther'] = auther_data

    # 解析出中文tag
    if tags_decode == True:
        tags = data['tags'].split("|")
        data['tags_decode'] = await tags_to_str(pool, tags)

    # 解析出帖子储存链接
    if url_decode == True:
        url = await chunk(config, tid)
        data['url'] = f"{url}/{tid}"

    # 是否增加帖子浏览量
    if (add_view == True):
        await db_update(pool, "UPDATE `{}` SET view_count = view_count + 1 WHERE tid=%s".format(table), (tid, ))
        
        placeholders = ",".join(["%s"] * len(tags))
        sql = f"UPDATE tags_index SET count=count+1 WHERE id IN ({placeholders})"
        await db_update(pool, sql, tuple(tags))

    return data



async def tags_to_str(pool, tags: list):
    """
    将 tagID 转为字符串

    :pool: 数据库连接池
    :tags: tags列表
    """
    placeholders = ",".join(["%s"] * len(tags))
    sql = f"SELECT id, tag FROM tags_index WHERE id IN ({placeholders})"

    # 获取出 tagID 对应的 tag
    rows = await db_rows(pool, sql, tuple(tags))

    # JSON格式输出
    data = {}
    for row in rows:
        data[row['id']] = row['tag']
    return data



async def tags_to_ID(
    pool,
    tags_str: list,
    auto_create: bool = False,
    uid: int = None
):
    """
    将 tags 转为ID

    :pool: 数据库连接池
    :tags_str: tags字符串列表
    :auto_create: 是否自动创建
    :uid: 创建者uid
    """

    if not tags_str:
        return {}

    placeholders = ",".join(["%s"] * len(tags_str))
    sql = f"SELECT id, tag FROM tags_index WHERE tag IN ({placeholders})"

    async with pool.acquire() as conn:
        async with conn.cursor(asyncmy.cursors.DictCursor) as cur:
            await cur.execute(sql, tuple(tags_str))
            rows = await cur.fetchall()

    # 先做数据库结果映射
    db_tags = {
        row["tag"]: row["id"]
        for row in rows
    }

    # 自动创建不存在tag
    if auto_create:

        new_tags = [
            tag for tag in tags_str
            if tag not in db_tags
        ]

        for tag in new_tags:
            tag_id = await db_insert(
                pool,
                "INSERT INTO tags_index (tag, count) VALUES (%s, 0)",
                (tag,)
            )
            db_tags[tag] = tag_id

    # 按原输入顺序重新构建
    tags = {
        tag: db_tags[tag]
        for tag in tags_str
        if tag in db_tags
    }

    return tags




async def get_user(
    pool, 
    uid: int,
    uname: bool = True,
    fetch: str = "*",
    avatar: bool = False,
    level: bool = False
) -> dict:
    """
    获取用户所有信息

    :pool: 数据库连接池
    :uid: 用户ID
    :uname: 是否获取用户名
    :fetch: 从users_data_{}表取片段
    :avatar: 是否获取头像
    :level: 是否获取等级，如果是True则传参的fetch需要包含有academic_year字段
    """
    config = await get_config()

    # 查redis缓存
    rds = await get_redis()
    data = await rds.get(f"user:{uid} {fetch}")
    if data: return json.loads(data)

    # 获取用户信息
    sharding = str(uid)[-1]
    table = f"users_data_{sharding}"
    data = await db_fetchone(
        pool,
        "SELECT {} FROM {} WHERE uid=%s LIMIT 1".format(fetch, table),
        (uid, )
    )

    # 查uname
    if uname == True:
        data['uname'] = await get_uname(pool, uid)

    # 解析出头像
    if avatar == True:

        # 有头像
        domain = config['chunk']['data3']
        if data['avatar'] == 1:
            data['avatar_big'] =  f"{domain}/{uid}_big.avif"
            data['avatar_medium'] =  f"{domain}/{uid}_medium.avif"
            data['avatar_small'] =  f"{domain}/{uid}_small.avif"

        # 无头像
        else:
            num = random.randint(1, config['random_avatars'])
            data['avatar_big'] =  f"{domain}/random/{num}.avif"
            data['avatar_medium'] =  f"{domain}/random/{num}.avif"
            data['avatar_small'] =  f"{domain}/random/{num}.avif"

    # 获取等级
    if level == True:
        data['level'] = config['level'][data['academic_year']]
    return data



async def get_user_old(pool, config, uid: int, fetch: str = "*") -> dict:
    """
    获取用户所有信息，会输出头像

    :pool: 数据库连接池
    :config: 配置文件
    :uid: 用户ID
    :fetch: 是否只从users_data_{}表取片段
    """
    # 查redis缓存
    rds = await get_redis()
    data = await rds.get(f"user_old:{uid}")
    if data: return json.loads(data)

    # 查uname
    sharding = str(uid)[-1]
    table = f"users_info_{sharding}"
    row = await db_fetchone(pool, "SELECT uname FROM {} WHERE uid=%s LIMIT 1".format(table), (uid, ))
    if not row:
        raise HTTPException(status_code=401, detail="uid未被注册！")
    uname = row["uname"]

    # 获取用户信息
    table = f"users_data_{sharding}"
    async with pool.acquire() as conn:
        async with conn.cursor(asyncmy.cursors.DictCursor) as cur:
            await cur.execute(
                "SELECT {} FROM {} WHERE uid=%s LIMIT 1".format(fetch, table),
                (uid, )
            )
            data = await cur.fetchall()

    # uname 添加至 data 里
    data = data[0]
    data.update({"uname": uname})

    # 有头像
    domain = config['chunk']['data3']
    if (data['avatar'] == 1):
        data.update({"avatar_big": f"{domain}/{uid}_big.avif"})
        data.update({"avatar_medium": f"{domain}/{uid}_medium.avif"})
        data.update({"avatar_small": f"{domain}/{uid}_small.avif"})

    # 无头像
    else:
        num = random.randint(1, config['random_avatars'])
        data.update({"avatar_big": f"{domain}/random/{num}.avif"})
        data.update({"avatar_medium": f"{domain}/random/{num}.avif"})
        data.update({"avatar_small": f"{domain}/random/{num}.avif"})

    # 缓存进redis
    await rds.set(f"user_old:{uid}", json.dumps(data), ex=1800)
    return data



async def get_uname(pool, uid: int) -> str:
    """
    获取指定UID的用户名

    :pool: 数据库连接池
    :uid: 用户ID
    """
    rds = await get_redis()

    # 尝试从 Redis 缓存获取用户信息
    uname = await rds.get(f"uname:{uid}")
    if uname:
        return uname

    # 查uname
    sharding = str(uid)[-1]
    table = f"users_info_{sharding}"
    row = await db_fetchone(pool, "SELECT uname FROM {} WHERE uid=%s LIMIT 1".format(table), (uid, ))
    if not row:
        raise HTTPException(status_code=401, detail="uid未被注册！")
    uname = row["uname"]

    # 缓存进redsi
    await rds.set(f"uname:{uid}", uname, ex=1800)
    return uname



async def get_score(pool, tid: int, full: bool = False) -> dict:
    """
    获取评分，若uid参数未填则为总评分

    :pool: 数据库连接池
    :tid: 帖子ID
    :full: 是否为完整输出，如果是则返回每个用户的详细评分
    """

    # 总评分
    data = {}
    row = await db_fetchone(pool, "SELECT uids, avg FROM scores WHERE tid=%s", (tid, ))
    if not row:
        return {
            "avg": 0,
            "count": 0
        }

    avg = row['avg']
    uids = row['uids'].split("|")
    uids = [int(uid) for uid in uids]
    
    if full == False:
        return {
            "avg": avg,
            "count": len(uids)
        }

    # 所有用户的评分（未来如果评分多，则有待优化）
    data = []
    for uid in uids:
        shading = str(uid)[-1]
        table = f"scores_{shading}"
        row = await db_fetchone(
            pool, 
            "SELECT date, score, state, content FROM {} WHERE tid=%s AND uid=%s LIMIT 1".format(table), 
            (tid, uid, )
        )

        # 获取用户信息
        user = await get_user(pool, uid, True, "avatar", True)

        data.append({
            "uid": uid,
            "date": row['date'],
            "score": row['score'],
            "state": row['state'],
            "content": row['content'],
            "user": user
        })

    # 完整输出
    return {
        "avg": avg,
        "count": len(uids),
        "full": data
    }



async def get_client_ip(request: Request) -> str:
    """
    获取客户端IP

    :request: Request
    """
    x_forwarded_for = request.headers.get("x-forwarded-for")
    if x_forwarded_for:
        return x_forwarded_for.split(",")[0].strip()

    x_real_ip = request.headers.get("x-real-ip")
    if x_real_ip:
        return x_real_ip

    return request.client.host



async def create_recommends(pool):
    """
    创建每日推荐

    :pool: 数据库连接池
    """

    # 获取5个推荐并存入数据库
    async with pool.acquire() as conn:
        async with conn.cursor(asyncmy.cursors.DictCursor) as cur:
            await cur.execute("SELECT tid FROM topics_index WHERE fid='1-1' AND tid > 0 AND no_push IS NULL ORDER BY RAND() LIMIT 5")
            rows = await cur.fetchall()
    tids = [str(row['tid']) for row in rows]
    tids = "|".join(tids)
    await db_update(pool, "UPDATE sys_auto_increment_value SET value=%s WHERE variable='recommend' LIMIT 1", (tids, ))
    return True



async def get_collection_state(pool, tid: int, uid: int) -> bool:
    """
    查看指定tid uid收藏状态

    :pool: 数据库连接池
    :tid: 帖子ID
    :uid: 用户ID
    """

    # 查表
    sharding = str(uid)[-1]
    table = f"collection_{sharding}"
    row = await db_fetchone(pool, "SELECT 1 FROM {} WHERE uid=%s AND tid=%s LIMIT 1".format(table), (uid, tid, ))
    if row:
        return True
    else:
        return False



async def auther(pool, tid: int, uid: int, return_uid: bool = False):
    """
    获取uid是否为帖子主人

    :pool: 数据库连接池
    :tid: 帖子ID
    :uid: 用户ID
    :return_uid: 是否返回作者uid，如果返回uid则不返回布偶值
    """
    # 判断tid合法性
    fid = await get_fid(pool, tid)
    sharding = str(tid)[-1]
    table = f"topics_{fid}_{sharding}"
    row = await db_fetchone(pool, "SELECT uid FROM `{}` WHERE tid=%s".format(table), (tid, ))
    if uid == row['uid']:
        if return_uid == True:
            return int(row['uid'])
        else:
            return True
    else:
        if return_uid == True:
            return int(row['uid'])
        else:
            return False



async def get_title(pool, tid: int, fid: str = None) -> str:
    """
    获取帖子标题

    :pool: 数据库连接池
    :tid: 帖子ID
    :fid: 板块ID，填入可减少计算
    """

    if not fid:
        fid = await get_fid(pool, tid)

    # 获取帖子标题
    sharding = str(tid)[-1]
    table = f"topics_{fid}_{sharding}"
    row = await db_fetchone(pool, "SELECT title FROM `{}` WHERE tid=%s LIMIT 1".format(table), (tid, ))
    return row['title']



async def timestamp(rel: bool = False):
    """
    获取上海当天 0:00 的秒级时间戳
    """

    shanghai_tz = ZoneInfo("Asia/Shanghai")

    now = datetime.now(shanghai_tz)

    # 当天0:00的时间戳
    if rel == False:
        today_start = now.replace(
            hour=0,
            minute=0,
            second=0,
            microsecond=0
        )

        return int(today_start.timestamp())
    
    # 真实秒级时间戳
    else:
        return int(now.timestamp())



async def str_to_timestamp(str):
    # 自动判断格式
    if " " in str:
        fmt = "%Y-%m-%d %H:%M:%S"
    else:
        fmt = "%Y-%m-%d"

    dt = datetime.strptime(str, fmt)
    dt_shanghai = dt.replace(tzinfo=ZoneInfo("Asia/Shanghai"))
    return int(dt_shanghai.timestamp())



async def md5(text: str):
    """
    获取文本和数字的MD5值
    
    :text: 字符串
    """
    md5 = hashlib.md5(text.encode("utf-8")).hexdigest()
    return md5



async def avif(
    source_path,
    target_path,
    quality=80,
    max_resolution=None
):
    """
    图片压缩成avif格式，默认质量80

    :source_path: 输入路径
    :target_path: 输出路径
    :quality: 压缩质量 0 - 100
    :max_resolution: 最大分辨率限制，不传则不限制
    """

    source_path = PATH(source_path)
    target_path = PATH(target_path)

    # 文件不存在
    if not source_path.exists():
        return "图片不存在"
    try:

        # 打开图片
        img = Image.open(source_path)

        # 没透明一般 RGB 更好
        if img.mode == "RGBA":
            pass
        else:
            img = img.convert("RGB")
        width, height = img.size

        # 只有传了 max_resolution 才缩放
        if max_resolution:
            if width > max_resolution or height > max_resolution:
                ratio = min(
                    max_resolution / width,
                    max_resolution / height
                )
                img = img.resize(
                    (
                        int(width * ratio),
                        int(height * ratio)
                    ),
                    Image.Resampling.LANCZOS
                )

        # 创建目录
        target_path.parent.mkdir(
            parents=True,
            exist_ok=True
        )

        # 保存为 AVIF
        img.save(
            target_path,
            "AVIF",
            quality=quality,
        )
        return True
    except Exception as e:
        return str(e)



async def get_topic_path(
    pool,
    config,
    style: str,
    target: int,
    url_format: bool = False
):
    """
    获取帖子路径

    :pool: 数据库连接池
    :config: 配置文件
    :style: 获取依赖方式，只有tid和uid2种选项
    :target: style="tid"就传参tid，"uid"就传参uid
    :url: 是否输出URL路径，如果True则return2个参数
    """

    # 帖子文件路径
    if style == "tid":
        sharding = await chunk(config, target, "data", True)
        path = PATH(f"{config['path']}/data/forums/{sharding}/data{sharding}/{target}")
        if url_format == True:
            url = f"/data/forums/{sharding}/data{sharding}/{target}"
    elif style == "uid":
        row = await db_fetchone(pool, "SELECT value FROM sys_auto_increment_value WHERE variable='tid' LIMIT 1")
        tid = int(row['value'])
        sharding = await chunk(config, tid, "data", True)
        uid_md5 = await md5(str(target))
        path = PATH(f"{config['path']}/data/forums/{sharding}/data{sharding}/{uid_md5}")
        if url_format == True:
            url = f"/data/forums/{sharding}/data{sharding}/{uid_md5}"

    # 输出
    if url_format == True:
        return path, url
    else:
        return path



async def get_navigation_bar(
    pool,
    rds
):
    """
    获取导航栏的所有数据

    :pool: 数据库连接池
    """
    # 查redis缓存
    nav = await rds.get(f"nav")
    if nav: return (json.loads(nav))

    config = await get_config()

    # 获取热门tag
    rows = await db_rows(pool, "SELECT tag FROM tags_index WHERE tag NOT LIKE %s AND (tag NOT REGEXP '^[0-9]+$' OR tag = '0721') ORDER by count DESC LIMIT 30", (f"%ep%",))
    tags = [row['tag'] for row in rows]

    # 获取最新的ym咨询
    ym = await db_rows(pool, "SELECT * FROM ymgal")

    # 获取在线人数
    row = await db_fetchone(pool, "SELECT COUNT(uid) AS count FROM online")
    online = row['count']

    # 最高在线
    row = await db_fetchone(pool, "SELECT value FROM sys_auto_increment_value WHERE variable='highest_online' LIMIT 1")
    hightest_online = int(row['value'])

    # 总帖数
    row = await db_fetchone(pool, "SELECT COUNT(tid) AS count FROM topics_index")
    topics_count = row['count']

    # 缓存进redis
    data = {
        "tags": tags,
        "board": config['board'],
        "ym": ym,
        "online": online,
        "hightest_online": hightest_online,
        "topics_count":topics_count
    }

    # 缓存
    await rds.set(f"nav", json.dumps(data), ex=1800)
    return data



async def update_ym(pool):
    """
    月慕咨询更新

    :pool: 数据库连接池
    """
    config = await get_config()

    url = "https://www.ymgal.games/search?type=article&keyword=&sort=time&category=%E8%B5%84%E8%AE%AF&page=1"
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
    }
    html = requests.get(url, headers=headers).text
    soup = BeautifulSoup(html, "lxml")

    # 找到帖子列表容器
    article_list = soup.find("div", id="article-result-list")

    # 所有帖子
    items = article_list.find_all("div", class_="ui item")
    data = []
    for item in items:

        # 标题和链接
        header = item.find("a", class_="header")
        title = header.get_text(strip=True)

        # 补全相对链接
        link = urljoin(url, header["href"])

        # 封面图
        img = item.find("div", class_="image").find("img")
        cover = img["src"]
        data.append({
            "title": title,
            "link": link,
            "cover": cover
        })

    # 清空月慕缓存文件夹
    path = PATH(config['path']) / "data/forums/3/data3/ym"
    shutil.rmtree(path)
    os.makedirs(path)

    # 循环每一个封面
    for topic in data:
        url = topic['cover']
        res = requests.get(url)
        ID = url.split("/")[-1]

        with open(path / f"{ID}", "wb") as f:
            f.write(res.content)

    # JSON转元组并入库
    data_ = []
    for topic in data:
        ID = topic['cover'].split("/")[-1]
        chunk = config['chunk']['data3']
        url = f"{chunk}/ym/{ID}"
        data_.append(
            (topic['title'], url, topic['link'])
        )

    # 入库
    await db_update(pool, "DELETE FROM ymgal")
    await db_insert_many(
        pool,
        "INSERT INTO ymgal (title, preview, src)",
        data_
    )
    return



async def get_user_config(
    pool,
    uid: int,
    fetch: str = "*"
):
    """
    获取用户的配置

    :pool: 数据库连接池
    :uid: 用户ID
    :fetch: 从users_configs_{i}分表中取什么片段，默认*
    """
    table = f"users_configs_{str(uid)[-1]}"
    row = await db_fetchone(
        pool, 
        "SELECT {} FROM {} WHERE uid=%s LIMIT 1".format(fetch, table),
        (uid, )
    )

    return row



async def get_level(
    pool,
    uid: int,
):
    """
    获取论坛等级

    :pool: 数据库连接池
    :uid: 用户ID
    """
    config = await get_config()
    table = f"users_data_{str(uid)[-1]}"
    row = await db_fetchone(
        pool,
        "SELECT academic_year FROM {} WHERE uid=%s LIMIT 1".format(table),
        (uid, )
    )
    academic_year = row['academic_year']
    level = config['level'][academic_year]
    return level





async def touchgal_get():
    """
    爬取touchgal的最新帖子
    """
    pool = dependencies.pool
    config = await get_config()
    url = config['touchgal']

    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
        "Cookie": config['touchgal_cookie'],
        "Accept-Language": "zh-CN,zh;q=0.9,en;q=0.8",
        "Connection": "keep-alive",
        "Upgrade-Insecure-Requests": "1"
    }

    try:
        response = requests.get(url, headers=headers)
        response.raise_for_status()
        

        html = response.text
        soup = BeautifulSoup(html, "html.parser")

        # 找到标题
        title = soup.find(
            lambda tag:
            tag.name == "h2" and
            "最新 Galgame" in tag.get_text(strip=True)
        )

        if not title:
            raise Exception("未找到『最新 Galgame』模块")

        # 找到所属 section
        section = title.find_parent("section")

        if not section:
            raise Exception("未找到 section")

        # 找到该 section 下的第一个 grid
        grid = section.find(
            lambda tag:
            tag.name == "div" and
            tag.get("class") and
            any("grid-cols-2" in c for c in tag.get("class"))
        )

        if not grid:
            raise Exception("未找到帖子列表")

        # 提取帖子ID
        post_ids = []

        for a in grid.find_all("a", href=True, recursive=False):
            href = a["href"]

            if href.startswith("/"):
                post_ids.append(href.strip("/"))

        # 查已有
        rows = await db_rows(
            pool,
            f"""
            SELECT id
            FROM touchgal
            WHERE id IN ({','.join(['%s'] * len(post_ids))})
            """,
            post_ids
        )

        exists = {row["id"] for row in rows}

        # 过滤
        insert_data = [
            (id_,)
            for id_ in post_ids
            if id_ not in exists
        ]

        # 批量插入
        if insert_data:
            await db_insert_many(
                pool,
                "INSERT INTO touchgal (id)",
                insert_data
            )

    except requests.exceptions.RequestException as e:
        await msg_add(pool, 73, "touchgal爬取异常：{e}")
    return




async def get_touchgal_topic():
    """
    爬取touchgal的最新帖子
    """
    pool = dependencies.pool
    config = await get_config()

    # 取一个touchgal的帖子ID
    row = await db_fetchone(pool, "SELECT id FROM touchgal WHERE status=0 LIMIT 1")
    if not row:
        return
    
    ID = row['id']
    url = config['touchgal'] + f"/{ID}"
    
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
        "Cookie": config['touchgal_cookie'],
        "Accept-Language": "zh-CN,zh;q=0.9,en;q=0.8",
        "Connection": "keep-alive",
        "Upgrade-Insecure-Requests": "1"
    }

    try:
        response = requests.get(url, headers=headers)
        response.raise_for_status()

        html = response.text
        print(1)
        if "未找到对应 Galgame" in html:
            print(2)
            await db_update(
                pool,
                f"UPDATE touchgal SET status=1  WHERE id='{ID}' LIMIT 1"
            )
            return

        soup = BeautifulSoup(html, "html.parser")

        # 获取帖子标题
        title = soup.title.text
        title = title.split("|")
        title.pop()
        title = '|'.join(title)

        # 获取游戏别名
        try:
            h2 = soup.find("h2", string="游戏别名")
            ul = h2.find_next_sibling("ul")
            aliases = [
                li.get_text(strip=True)
                for li in ul.find_all("li")
            ]
            aliases_str = " / ".join(aliases)
            aliases = "{?pre" + aliases_str + "?}"
        except:
            aliases = ""

        # 获取开发商
        try:
            h2 = soup.find("h2", string="所属会社")
            container = h2.find_parent().find_next_sibling()
            span = container.find("span")
            developer = span.get_text(strip=True)
            developer = developer.split("+")[0]
            developer = "{?pre开发：" + developer + "?}"
        except:
            developer = ""

        # 获取游戏介绍
        try:
            h2 = soup.find("h2", string="游戏介绍")
            paragraphs = []
            node = h2.find_next_sibling()
            while node and node.name != "h2":
                if node.name == "p":
                    html = node.decode_contents().replace("<br/>", "<br>")
                    if not html.endswith("<br>"):
                        html += "<br>"
                    paragraphs.append(html)
                node = node.find_next_sibling()
            overview = "<br>".join(paragraphs)
        except:
            overview = "无介绍"

        # 获取发售日期
        try:
            for span in soup.find_all("span"):
                text = span.get_text(strip=True)
                if "发售时间" in text:
                    date = text.replace("发售时间:", "").strip()
                    break
        except:
            date = await get_date()

        # 获取OP
        try:
            h2 = soup.find("h2", string="PV鉴赏")
            video_div = h2.find_next_sibling("div")
            video_url = video_div.get("data-src", "")
            OP = "{_video" + video_url + "}"
        except:
            OP = ""

        # 获取图片
        try:
            h2 = soup.find("h2", string="游戏截图")
            img_container = h2.find_next_sibling("div")
            imgs = [
                img.get("src")
                for img in img_container.find_all("img")
                if img.get("src")
            ]
            imgs_src = ""
            for img in imgs:
                imgs_src += "{_img" + img + "}"
        except:
            imgs_src = ""

        # 下载链接
        download = "{?pre下载链接：" + f"<a href='{url}' target='_blank'>{url}</a>" + "?}"

        # 完整入库结构
        content = f"{aliases}{developer}{overview}{OP}{imgs_src}{download}"

        # 入库
        tid = await db_auto_increment_value(pool, "tid")
        table = f"topics_3-1_" + str(tid)[-1]
        await db_insert(
            pool,
            "INSERT INTO `{}` (`tid`, `title`, `content`, `uid`, `date`, `tags`, `preview`, `view_count`, `reply_count`) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)".format(table),
            (tid, title, content, 73, date, "12", "", 0, 0)
        )

        ts = int(datetime.strptime(date, "%Y-%m-%d").timestamp())
        await db_insert(
            pool,
            "INSERT INTO `topics_index` (`fid`, `tid`, `last_modify`, `score`) VALUES (%s, %s, %s, %s)",
            ("3-1", tid, ts, "")
        )

        # 更新touchgal爬取状态
        await db_update(
            pool,
            f"UPDATE touchgal SET status=1  WHERE id='{ID}' LIMIT 1"
        )
    except requests.exceptions.RequestException as e:
        await msg_add(pool, 73, "touchgal爬取帖子异常：{e}")
    return



async def mysql_backup(pool, config):
    """
    备份数据库
    """
    BACKUP_DIR = PATH(config["path"]) / "mysql_backup"

    # 创建目录
    BACKUP_DIR.mkdir(parents=True, exist_ok=True)

    # 获取数据库信息
    host = pool._conn_kwargs["host"]
    port = pool._conn_kwargs["port"]
    user = pool._conn_kwargs["user"]
    password = pool._conn_kwargs["password"]
    db_name = pool._conn_kwargs["db"]

    # 获取全部表
    async with pool.acquire() as conn:
        async with conn.cursor() as cur:
            await cur.execute("SHOW TABLES")
            tables = [row[0] for row in await cur.fetchall()]

    # 开始导出
    for table in tables:

        sql_file = BACKUP_DIR / f"{table}.sql"

        cmd = [
            config["mysqldump"],
            "-h", host,
            "-P", str(port),
            "-u", user,
            f"-p{password}",
        ]

        # sessions、logs 仅结构
        if table in config['black_list_tables']:
            cmd.append("--no-data")

        cmd.extend([
            db_name,
            table
        ])

        with open(sql_file, "wb") as f:
            subprocess.run(
                cmd,
                stdout=f,
                stderr=subprocess.PIPE,
                check=True
            )

    # 压缩成zip
    zip_path = PATH(config["path"]) / "data/forums/3/data3/mysql/mysql_backup.zip"
    if zip_path.exists():
        zip_path.unlink()

    with zipfile.ZipFile(
        zip_path,
        "w",
        compression=zipfile.ZIP_DEFLATED
    ) as z:

        for sql_file in BACKUP_DIR.glob("*.sql"):
            z.write(
                sql_file,
                arcname=sql_file.name
            )

    # 删除整个备份目录
    shutil.rmtree(BACKUP_DIR, ignore_errors=True)

    return {
        "msg": "备份完成",
        "path": str(BACKUP_DIR),
        "tables": len(tables),
        "zip": str(zip_path),
    }