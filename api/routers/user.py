from fastapi import HTTPException, Request, Depends, APIRouter, Path, Query, Body, UploadFile, File
from dependencies import get_db, get_redis, get_config, get_sessionID, get_uid_by_headers
from functions import *
from typing import Optional
from pydantic import BaseModel, Field
import json
import random
import secrets
from models import sessionID_model, email_model, login_model, uname_model, tids_model
from typing import List
import shutil
import os



router = APIRouter(prefix="/user", tags=["用户"])



@router.get("/email", summary="获取对应sessionID的邮箱", description="因为邮箱是隐私，不能直接获取。")
async def API_get_email(
    pool = Depends(get_db),
    uid = Depends(get_uid_by_headers)
):
    row = await db_fetchone(pool, "SELECT email FROM users_email WHERE uid=%s LIMIT 1", (uid, ))
    if not row:
        return fail("未设置邮箱")
    else:
        return success(row['email'])



@router.put("/email", summary="修改邮箱", description="INSERT和UPDATE一起写了，所以不需要POST接口。")
async def API_get_email(
    model: email_model,
    pool = Depends(get_db),
    uid = Depends(get_uid_by_headers)
):
    # 添加和修改
    await db_update(pool, "INSERT INTO users_email (uid, email) VALUES (%s, %s) ON DUPLICATE KEY UPDATE email=%s", (uid, model.email, model.email, ))
    return success("邮箱修改成功")












@router.put("/uname", summary="修改用户名", description="不能为空")
async def API_put_rename(
    model: uname_model,
    rds = Depends(get_redis),
    pool = Depends(get_db),
    uid = Depends(get_uid_by_headers)
):
    old_uname = await get_uname(pool, uid)
    uname = model.uname

    # 更新数据
    sharding = str(uid)[-1]
    table = f"users_info_{sharding}"
    await db_update(pool, "UPDATE {} SET uname=%s WHERE uid=%s LIMIT 1".format(table), (uname, uid, ))

    # 日志记录
    date = await get_date("all")
    await log_add_source(f"{date} {old_uname}({uid})更新用户名为 {uname}")

    # 清理redis缓存
    await rds.delete(f"user:{uid}")

    return success("用户名修改成功！新的用户名将于30分钟后生效！30分钟后你需要退出登录再登录即可正式生效！")



@router.put("/psw", summary="修改密码", description="需要传参sessionID")
async def API_put_psw(
    psw: str = Body(..., embed=True, description="新密码"),
    rds = Depends(get_redis),
    pool = Depends(get_db),
    uid = Depends(get_uid_by_headers)
):
    sharding = str(uid)[-1]
    table = f"users_info_{sharding}"
    await db_update(pool, "UPDATE {} SET psw=%s WHERE uid=%s".format(table), (psw, uid, ))
    return success("密码修改成功")



@router.put("/update", summary="修改指定SessionID的心路历程", description="都得传，不能缺少任何一个参数。")
async def API_get_email(
    sign: str = Body(..., embed=True, description="展示的个性签名"),
    sign_img: str = Body(..., embed=True, description="此生挚爱的图片，需要填入tid|aid"),
    best_love_story: str = Body(..., embed=True,description="此生挚爱的故事"),
    playing_story: str = Body(..., embed=True,description="正在推进的故事"),
    recommend_stories: str = Body(..., embed=True,description="强烈推荐的故事"),
    rds = Depends(get_redis),
    pool = Depends(get_db),
    uid = Depends(get_uid_by_headers)
):
    sharding = str(uid)[-1]
    table = f"users_data_{sharding}"

    await db_update(
        pool,
        "UPDATE {} SET sign=%s, sign_img=%s, best_love_story=%s, playing_story=%s, recommend_stories=%s WHERE uid=%s".format(table),
        (sign, sign_img, best_love_story, playing_story, recommend_stories, uid)
        )

    # 清除redis缓存
    await rds.delete(f"user:{uid}")

    return success("信息修改成功")














@router.post("/stories", summary="添加一个新的此生必玩故事", description="一次只能添加一个")
async def API_add_story(
    tid: int = Query(..., description="帖子ID"),
    rds = Depends(get_redis),
    pool = Depends(get_db),
    uid = Depends(get_uid_by_headers)
):
    # 判断tid合法性
    row = await db_fetchone(pool, "SELECT 1 FROM topics_index WHERE tid=%s", (tid, ))
    if not row:
        return fail("tid不存在！")

    tids = []

    # 获取已有作品
    row = await db_fetchone(pool, "SELECT stories FROM space_best_stories WHERE uid=%s", (uid, ))
    if row:
        tids = row['stories'].split("|")

    tids.append(str(tid))
    tids = "|".join(tids)
    await db_update(pool, "INSERT INTO space_best_stories (uid, stories) VALUES (%s, %s) ON DUPLICATE KEY UPDATE stories=%s", (uid, tids, tids, ))
    await rds.delete(f"space:{uid}")
    return success("添加成功")



@router.put("/stories", summary="修改此生必玩作品的tid顺序", description="需要传参tids为列表，里面纯仅数字tid，如：[1242, 244, 1344]")
async def API_sort_stories(
    model: tids_model,
    rds = Depends(get_redis),
    pool = Depends(get_db),
    uid = Depends(get_uid_by_headers)
):
    tids = model.tids
    tids = "|".join([str(tid) for tid in tids])
    await db_update(pool, "INSERT INTO space_best_stories (uid, stories) VALUES (%s, %s) ON DUPLICATE KEY UPDATE stories=%s", (uid, tids, tids, ))
    await rds.delete(f"space:{uid}")
    return success("作品顺序更新完成")



@router.delete("/stories", summary="删除指定的此生必玩故事", description="只能删除一个，不支持批量删除")
async def API_sort_stories(
    tid: int = Query(..., description="帖子tid列表"),
    pool = Depends(get_db),
    rds = Depends(get_redis),
    uid = Depends(get_uid_by_headers)
):
    row = await db_fetchone(pool, "SELECT stories FROM space_best_stories WHERE uid=%s LIMIT 1", (uid, ))
    tids = row['stories'].split("|")

    # 如果tid不在tids里
    if str(tid) not in tids:
        return fail("目标此生必推故事里没有此tid")

    tids.remove(str(tid))
    tids = "|".join(tids)
    await db_update(pool, "UPDATE space_best_stories SET stories=%s WHERE uid=%s LIMIT 1", (tids, uid, ))
    await rds.delete(f"space:{uid}")
    return success("删除成功")




















@router.get("/{uid}", summary="获取对应uid的所有信息", description="如果用户不选择公开的话，将不获取。（未来上线）")
async def API_get_user(
    uid: int = Path(..., description="用户ID"),
    config = Depends(get_config), 
    rds = Depends(get_redis),
    pool = Depends(get_db)
):

    # 查redis缓存
    user_data = await rds.get(f"user:{uid}")
    if user_data: return success(json.loads(user_data))

    data = {}
    data['user'] = await get_user_old(pool, config, uid)

    # 缓存进redis
    await rds.set(f"user:{uid}", json.dumps(data), ex=1800)

    return success(data)



@router.get("/{uid}/space", summary="获取对应uid的个人空间", description="如果用户不选择公开的话，将不获取。（未来上线）")
async def API_get_space(
    uid: int = Path(..., description="用户ID"),
    config = Depends(get_config), 
    rds = Depends(get_redis),
    pool = Depends(get_db)
):
    # 查redis缓存
    data = await rds.get(f"space:{uid}")
    if data: return success(json.loads(data))

    # 获取用户数据
    data = await get_user_old(pool, config, uid)
    sharding = str(uid)[-1]

    # 获取已推完的Galgame
    async with pool.acquire() as conn:
        async with conn.cursor(asyncmy.cursors.DictCursor) as cur:
            table = f"scores_{sharding}"
            await cur.execute("SELECT tid, date, state FROM {} WHERE uid=%s AND state='已推完'".format(table), (uid, ))
            rows = await cur.fetchall()

    data['finished'] = []
    for row in rows:
        tid = row['tid']
        title = await get_title(pool, tid)
        data['finished'].append({
            "tid": tid,
            "title": title,
            "date": row['date']
        })

    # 获取收藏的帖子
    async with pool.acquire() as conn:
        async with conn.cursor(asyncmy.cursors.DictCursor) as cur:
            table = f"collection_{sharding}"
            await cur.execute("SELECT tid FROM {} WHERE uid=%s".format(table), (uid, ))
            rows = await cur.fetchall()

    data['collections'] = []
    for row in rows:
        tid = row['tid']
        title = await get_title(pool, tid)
        data['collections'].append({
            "tid": tid,
            "title": title
        })

    # # 获取推荐的作品
    data['best_stories'] = []
    row = await db_fetchone(pool, "SELECT stories FROM space_best_stories WHERE uid=%s LIMIT 1", (uid, ))
    if row:
        tids = row['stories'].split("|")

        for tid in tids:
            tid = int(tid)
            title = await get_title(pool, tid)
            url = await chunk(config, tid)
            data['best_stories'].append({
                "tid": tid,
                "title": title,
                "url": url
            })

    # 缓存进redis
    await rds.set(f"space:{uid}", json.dumps(data), ex=1800)
    return success(data)



















@router.get("/msg/unread", summary="获取sessionID对应用户的未读信息状态", description="如果有未读返回就是 True，没未读返回就是 False")
async def get_msg(
    rds = Depends(get_redis),
    pool = Depends(get_db),
    sessionID = Depends(get_sessionID)
):
    if not sessionID:
        return success({"unread": False})

    # 查redis缓存
    # state = await rds.get(f"unread:{sessionID}")
    # if state: return success({"unread": json.loads(state)})

    uid = await get_uid_by_sessionID(sessionID, rds, pool)
    year = int(await get_date("year"))
    print(233)

    # 查数据库
    row = await db_fetchone(pool, "SELECT EXISTS(SELECT 1 FROM {} WHERE uid=%s AND `read`=0) AS unread".format(f"logs_{year}"), (uid, ))
    state = bool(row["unread"])
    
    # 缓存进redis
    await rds.set(f"unread:{sessionID}", json.dumps(state), ex=600)
    return success({"unread": state})



@router.get("/msg/{year}", summary="获取sessionID对应的所有个人信息", description="需要传参年份")
async def get_msg(
    year: int = Path(..., description="年份"),
    pool = Depends(get_db),
    rds = Depends(get_redis),
    sessionID = Depends(get_sessionID)
):
    uid = await get_uid_by_sessionID(sessionID, rds, pool)

    # 判断年份合法性
    now_year = int(await get_date("year"))
    if year >= 2023 and year <= now_year:
        async with pool.acquire() as conn:
            async with conn.cursor(asyncmy.cursors.DictCursor) as cur:
                table = f"logs_{year}"
                await cur.execute("SELECT * FROM {} WHERE uid=%s ORDER by date DESC".format(table), (uid, ))
                rows = await cur.fetchall()
        
        data = []
        for row in rows:
            if row['read'] == 1:
                state = True
            else:
                state = False
            
            data.append({
                "date": row['date'],
                "content": row['content'],
                "read": state
            })
        return success(data)
    else:
        return fail("年份不合法！必须大于等于2023，小于今年！")



@router.put("/msg/{year}", summary="完成阅读，标记成已读", description="需要传参sessionID")
async def get_finish_read(
    year: int = Path(..., description="年份"),
    pool = Depends(get_db),
    rds = Depends(get_redis),
    sessionID = Depends(get_sessionID)
):
    # 判断年份合法性
    now_year = int(await get_date("year"))
    if year >= 2023 and year <= now_year:
        uid = await get_uid_by_sessionID(sessionID, rds, pool)
        table = f"logs_{year}"
        await db_update(pool, "UPDATE {} SET `read`=1 WHERE uid=%s".format(table), (uid, ))

        # 清除redis缓存
        await rds.delete(f"unread:{sessionID}")

        return success(f"所有{year}年的信息都已标记“已读”！")
    else:
        return fail("年份不合法！必须大于等于2023，小于今年！")




















@router.post("/register", summary="注册新账号", description="成功了返回 True，失败了会告知失败原因，需要注意传入的psw最好是自己MD5加密后传输！这个接口不会自动帮密码进行MD5加密！")
async def API_register(data: login_model, request: Request, rds = Depends(get_redis), pool = Depends(get_db)):
    uname = data.uname
    psw = data.psw

    # 判断是否已经注册
    for i in range(10):
        row = await db_fetchone(pool, "SELECT EXISTS(SELECT 1 FROM {} WHERE uname=%s AND psw=%s) AS re".format(f"users_info_{i}"), (uname, psw, ))
        re = bool(row['re'])
        if re == True:
            return fail("用户名和密码已经注册过了")

    # 获取客户端 IP（兼容代理）
    x_forwarded_for = request.headers.get("X-Forwarded-For")
    if x_forwarded_for:
        client_IP = x_forwarded_for.split(",")[0].strip()
    else:
        client_IP = request.client.host

    # 获取redis缓存
    num = await rds.incr(f"reg_limit:{client_IP}")
    if num == 1:
        # 第一次注册设置过期时间
        await rds.expire(f"reg_limit:{client_IP}", 1800)
    if num > 10:
        return fail("当前IP达到注册限制，请30分钟后再注册吧~")

    # 获取最新uid
    row = await db_fetchone(pool, "SELECT value FROM sys_auto_increment_value WHERE variable='uid' LIMIT 1")
    uid = row['value']  # 字符串类型
    chunk = uid[-1]     # 字符串类型

    # 分表储存注册信息uid uname psw
    table = f"users_info_{chunk}"
    await db_insert(pool, "INSERT INTO {} (uid, uname, psw) VALUES (%s, %s, %s)".format(table), (uid, uname, psw, ))

    # 储存用户默认数据
    table = f"users_data_{chunk}"
    date = await get_date()
    await db_insert(pool, "INSERT INTO {} (uid, online_time, identity, credit, academic_year, schoolship, judment_count, canned_count, register_time, last_login_time) VALUES (%s, 0, '结姬学园学生', 0, '中学一年生', 0, 0, 0, %s, %s)".format(table), (uid, date, date, ))

    # 自增表uid + 1
    await db_update(pool, "UPDATE sys_auto_increment_value SET value = value + 1 WHERE variable='uid'")

    # 日志
    await msg_add(pool, uid, "欢迎您于今日正式入学结姬学园！还请您收藏<a href='https://home.galbase.top' target='_blank'>https://home.galbase.top</a>以防站点更换域名！或者收藏本站永久域名：<a href='https://0d000721.cc' target='_blank'>https://0d000721.cc</a> <a href='https://ciallo.ca' target='_blank'>https://ciallo.ca</a>")
    await log_add_source(f"{date} uid:{uid} 注册成功")
    return success({"register": True})



@router.post("/login", summary="登录账号", description="需要注意psw需要传入MD5值，成功了会返回sessionID和uid")
async def API_login(
    request: Request, 
    data: login_model, 
    rds = Depends(get_redis), 
    pool = Depends(get_db), 
    config = Depends(get_config)
):
    uname = data.uname
    psw = data.psw

    # 获取客户端 IP（兼容代理）
    x_forwarded_for = request.headers.get("X-Forwarded-For")
    if x_forwarded_for:
        client_IP = x_forwarded_for.split(",")[0].strip()
    else:
        client_IP = request.client.host

    # 获取redis缓存
    num = await rds.incr(f"login_limit:{client_IP}")
    if num == 1:

        # 第一次登录设置过期时间
        await rds.expire(f"login_limit:{client_IP}", 1800)
    if num > 30:
        return fail("当前IP达到登录限制，请30分钟后再注册吧~")

    for i in range(10):
        table = f"users_info_{i}"
        row = await db_fetchone(pool, "SELECT uid FROM {} WHERE uname=%s AND psw=%s LIMIT 1".format(table), (uname, psw, ))
        if row:
            uid = int(row['uid'])

            # session表新增KV
            sessionID = secrets.token_hex(16)
            await db_update(pool, "INSERT INTO users_sessions (sessionID, uid) VALUES (%s, %s)", (sessionID, uid, ))

            # 获取用户信息
            user = await get_user(pool, uid, True, "avatar", True)
            return success({
                "uid": uid,
                "sessionID": sessionID, 
                "user": user
            })

    return fail("登录失败，账号密码不匹配！看看是不是密码输错了呢？")



@router.post("/avatar", summary="上传头像", description="会覆盖旧头像。")
async def API_post_avatar(
    file: UploadFile = File(..., description="图片文件"),
    pool = Depends(get_db),
    uid = Depends(get_uid_by_headers),
    config = Depends(get_config)
):
    # 文件合法性检测
    ext = PATH(file.filename).suffix.lower().replace(".", "")
    ALLOW_EXT = ["jpg","jpeg","png","webp","avif"]
    if ext not in ALLOW_EXT:
        return fail("文件后缀不支持！仅支持jpg jpeg png webp avif")
    
    # 文件大小检测
    file.file.seek(0, 2)
    size = file.file.tell()
    file.file.seek(0)
    if size > 10 * 1024 * 1024:
        return fail("文件大小超过10MB，禁止上传！")

    # 移入文件
    path = PATH(config['path']) / f"data/forums/3/data3"
    with open(path / f"{uid}.{ext}", "wb") as f:
        shutil.copyfileobj(file.file, f)

    # 压缩成3分
    input = path / f"{uid}.{ext}"
    output = path / f"{uid}_big.avif"
    await avif(input, output, 90, 1980)
    output = path / f"{uid}_medium.avif"
    await avif(input, output, 90, 500)
    output = path / f"{uid}_small.avif"
    await avif(input, output, 90, 200)

    # 删除源文件
    input.unlink(missing_ok=True)

    return success("头像上传成功！可以按Ctrl+F5强制刷新显示新头像！")