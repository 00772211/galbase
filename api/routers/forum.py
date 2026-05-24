from fastapi import HTTPException, Request, Depends, APIRouter, Path, Query, Body
from dependencies import *
from functions import *
from typing import Optional
from pydantic import BaseModel, Field
import json
import asyncmy
from enum import Enum
from typing import List
from models import *
import time
import random



router = APIRouter(prefix="/forum", tags=["板块 / 论坛"])






@router.get("/random", summary="随机一个有OP的Galgame", description="特别注意的是这个随机出来是只有GAL标题和帖子 tid 和视频OP链接，会自动屏蔽拔作。<br>返回的 preview 是图片的ID，返回的 vid 是视频的ID。")
async def API_get_random(
    pool = Depends(get_db), 
    config = Depends(get_config)
):

    # 从 vid索引表 随机取一个 vid 和 tid
    row = await db_fetchone(pool, "SELECT vid, tid FROM vids_index WHERE no_push IS NULL ORDER BY RAND() LIMIT 1")
    vid = row['vid']
    tid = row['tid']

    # 从 tid 获取 fid
    fid = await get_fid(pool, tid)
    url = await chunk(config, tid)
    sharding = str(tid)[-1]

    # 查表
    table = f"topics_{fid}_{sharding}"
    row = await db_fetchone(pool, "SELECT title, preview FROM `{}` WHERE tid=%s LIMIT 1".format(table), (tid, ))
    aid = row['preview']

    return success({
        "title": row['title'],
        "fid": fid,
        "tid": tid,
        "url": f"/topic/{tid}",
        "op": f"{url}/{tid}/{vid}.mp4",
        "preview": f"{url}/{tid}/{aid}.avif"
    })


@router.get("/newest", summary="获取最新更新的帖子标题和 tid", description="只会返回10个帖子信息，注意返回的格式是列表。")
async def API_get_newest_topic(
    pool = Depends(get_db), 
    config = Depends(get_config),
    rds = Depends(get_redis)
):

    # 查redis缓存
    newest = await rds.get(f"newest")
    if newest: return success(json.loads(newest))

    # 获取最新10个tid
    async with pool.acquire() as conn:
        async with conn.cursor(asyncmy.cursors.DictCursor) as cur:
            await cur.execute("SELECT tid FROM topics_index ORDER BY last_modify DESC LIMIT 10")
            rows = await cur.fetchall()

    # 查每个tid的信息
    data = []
    for row in rows:
        topic = await get_topic(pool, config, row['tid'], False, "title, uid, date, tid, view_count, reply_count")
        data.append(topic)

    # 缓存进redis
    await rds.set(f"newest", json.dumps(data), ex=1800)
    return success(data)



@router.get("/recommend", summary="获取今日推荐的5个Galgame，不推荐拔作", description="不会出现拔作")
async def API_recommend(pool = Depends(get_db), config = Depends(get_config), rds = Depends(get_redis)):

    # 查redis缓存
    data = await rds.get(f"recommend")
    if data: return success(json.loads(data))

    # 获取5个推荐
    row = await db_fetchone(pool, "SELECT value from sys_auto_increment_value WHERE variable='recommend' LIMIT 1")
    tids = row['value'].split("|")

    # 获取每个tid的标题和封面
    data = []
    for tid in tids:
        topic = await get_topic(pool, config, int(tid), False, "tid, title, preview", True)
        data.append(topic)

    # 缓存进redis
    await rds.set(f"recommend", json.dumps(data), ex=1800)
    return success(data)




@router.get("/home", summary="获取主页各板块的信息", description="只有3大板块...")
async def API_home(pool = Depends(get_db), config = Depends(get_config), rds = Depends(get_redis)):
    # 查redis缓存
    data = await rds.get(f"home")
    if data: return success(json.loads(data))

    # 获取每个fid的帖子数量和最新帖子tid
    async with pool.acquire() as conn:
        async with conn.cursor(asyncmy.cursors.DictCursor) as cur:
            await cur.execute("SELECT fid, COUNT(*) AS count, MAX(tid) AS tid FROM topics_index GROUP BY fid")
            rows = await cur.fetchall()

    # 整合数据
    data = {}
    data['forum'] = {}
    for row in rows:
        fid = row['fid']
        tid = row['tid']
        board = config['board'][fid]

        # 查帖子数据
        topic = await get_topic(pool, config, tid, False, "title, uid, date, view_count, reply_count", False, fid)

        data['forum'][fid] = {
            "count": row['count'],
            "tid": tid,
            "board": board,
            "topic": topic
        }

    # 缓存进redis
    await rds.set(f"home", json.dumps(data), ex=1800)
    return success(data)



@router.get("/online", summary="获取在校学生", description="由于获取的同时需要更新指定sessionID的最后登录时间，所以用POST请求传参sessionID，不传sessionID的话就不更新最后登录时间。")
async def API_online(
    pool = Depends(get_db),
    rds = Depends(get_redis),
    config = Depends(get_config),
    sessionID = Depends(get_sessionID)
):
    # 更新在线时间
    ts = await timestamp()
    if sessionID:
        uid = await get_uid_by_sessionID(sessionID, rds, pool)
        await db_update(pool, "INSERT INTO online (uid, last_online) VALUES (%s, %s) ON DUPLICATE KEY UPDATE last_online=%s", (uid, ts, ts, ))

        # 更新最后在线时间
        date = await get_date()
        sharding = str(uid)[-1]
        table = f"users_data_{sharding}"
        await db_update(pool, "UPDATE {} SET last_login_time=%s WHERE uid=%s LIMIT 1".format(table), (date, uid, ))

    # 获取在线用户
    async with pool.acquire() as conn:
        async with conn.cursor(asyncmy.cursors.DictCursor) as cur:
            if sessionID:
                await cur.execute("SELECT * FROM online WHERE uid !=%s ORDER BY last_online DESC", (uid, ))
            else:
                await cur.execute("SELECT * FROM online ORDER BY last_online DESC")
            rows = await cur.fetchall()

    data = []
    for row in rows:
        
        # 获取用户名和头像
        uid = row['uid']
        user = await get_user_old(pool, config, uid, "avatar")

        # 在线状态
        last_ts = int(row['last_online'])
        if ts - last_ts < 300:
            state = True
        else:
            state = False

        data.append({
            "uid": uid,
            "uname": user['uname'],
            "avatar_small": user['avatar_small'],
            "online": state
        })

    return success(data)



@router.put("/online", summary="增加在线时间", description="5分钟只能调用一次，不建议滥用此接口，刷时间没有什么意义")
async def API_put_online(
    pool = Depends(get_db),
    rds = Depends(get_redis),
    sessionID = Depends(get_sessionID)
):
    if not sessionID:
        return fail("未登录不能调用该接口！")

    # 查redis缓存
    limit = await rds.get(f"online_limit:{sessionID}")
    if limit:
        return fail("在线时间增加失败，与上次在线时间相差小于5分钟！")

    uid = await get_uid_by_sessionID(sessionID, rds, pool)
    sharding = str(uid)[-1]
    table = f"users_data_{sharding}"

    # 增加在线时间
    ts = await timestamp()
    await db_update(pool, "UPDATE {} SET online_time = online_time + 5 WHERE uid=%s LIMIT 1".format(table), (uid, ))
    await db_update(pool, "INSERT INTO online (uid, last_online) VALUES (%s, %s) ON DUPLICATE KEY UPDATE last_online=%s", (uid, ts, ts, ))

    await rds.set(f"online_limit:{sessionID}", 1, ex=280)
    return success("在线时间增加成功")



@router.get("/board", summary="获取所有fid及对应的板块名字", description="返回的是一个JSON")
async def API_board_list(
    config = Depends(get_config)
):
    return success(config['board'])





@router.get("/{fid}/{page}", summary="获取指定板块的帖子列表", description="1-1 1-2 1-3 1-4板块均为卡片式，其他板块均为列表式。<br>返回的列表是20个或更少。")
async def API_request_topics(
    fid: fid_enum = Path(..., description="板块ID"),
    page: int = Path(..., description="页数"),
    sort: sort_type = Query(..., description="排列方法"),
    filter: List[filter_type] = Query(..., min_items=1, description="过滤参数"),
    pool = Depends(get_db), 
    config = Depends(get_config), 
    rds = Depends(get_redis)
):
    fid = fid.value

    # 查redis缓存
    filter_str = "+".join(f.value for f in filter)
    topics = await rds.get(f"request_topics:{fid} {page} {sort.value} {filter_str}")
    if topics: return success(json.loads(topics))

    # 获取板块名字
    if fid in config['board']:
        board = config['board'][fid]
    else:
        return fail("fid不存在。")
    
    # 过滤判断
    sql_add = ""
    if filter_type.no_H in filter:
        sql_add += "AND no_push IS NULL "
    if filter_type.only_H in filter:
        sql_add += "AND no_push = 1 "

    # 获取帖子
    async with pool.acquire() as conn:
        async with conn.cursor(asyncmy.cursors.DictCursor) as cur:
            await cur.execute("SELECT tid FROM `topics_index` WHERE fid=%s {} ORDER BY {} DESC LIMIT 20 OFFSET %s".format(sql_add, sort.value), (fid, page * 20, ))
            rows = await cur.fetchall()

    # 循环每个tid
    data = []
    for row in rows:
        tid = row['tid']

        # 卡片式
        if fid == "1-1" or fid == "1-2" or fid == "1-3" or fid == "1-4":
            topic = await get_topic(
                pool,
                config,
                tid,
                True,
                "title, uid, date, tags, preview, view_count, reply_count",
                True,
                fid
            )
            topic['tid'] = tid

            # 1-1板块才有评分
            if fid == "1-1":
                topic['score'] = await get_score(pool, tid)
            data.append(topic)

        # 列表式
        else:
            topic = await get_topic(pool, config, tid, False, "title, content, uid, date, preview, reply_count", True, fid)
            topic['tid'] = tid

            # 获取该tid的最新回复数据
            sharding = str(tid)[-1]
            row = await db_fetchone(pool, "SELECT uid, content, date FROM {} WHERE tid=%s ORDER BY rid DESC LIMIT 1".format(f"replies_{sharding}"), (tid, ))
            if row:
                topic['reply'] = row
                topic['reply']['uname'] = await get_uname(pool, row['uid'])
            data.append(topic)

    # 缓存进redis
    await rds.set(f"request_topics:{fid} {page} {sort.value} {filter_str}", json.dumps({"board": board, "topics": data}), ex=1800)

    return success({
        "board": board, 
        "topics": data
    })



@router.get("", summary="每次访问页面都得请求的接口，包含了所有信息", description="30分钟缓存")
async def API_get_forums(
    pool = Depends(get_db), 
    config = Depends(get_config), 
    rds = Depends(get_redis),
):
    # 查redis缓存
    forum = await rds.get(f"forum")
    if forum: return success(json.loads(forum))


    # 获取随机6个壁纸
    bgs = []
    IDS = random.sample(list(config['bgs'].keys()), 6)
    for ID in IDS:
        full = config['bgs'][ID].split("|")
        chunk = int(full[0])
        nums = int(full[1])
        gal = full[2]
        select = random.randint(1, nums)
        if config['localhost'] == True:
            bgs.append({
                "url": f"/data/bgs/{chunk}/{ID}/{ID} ({select}).avif",
                "gal": gal
            })
        else:
            bgs.append({
                "url": f"https://bg{chunk}.galbase.top/{ID}/{ID} ({select}).avif",
                "gal": gal
            })

    # 获取随机头像
    select = random.randint(1, config["random_avatars"])
    random_avatar = f"{config['chunk']['data3']}/random/{select}.avif"

    # 导航栏
    nav = await get_navigation_bar(pool, rds)

    data = {
        "random_avatar": random_avatar,
        "maintenance": config['maintenance'],
        "maintenance_msg": config['maintenance_msg'],
        "bgs": bgs,
        "nav": nav,
        "version": config['version'],
    }

    # 缓存进redis
    await rds.set(f"forum", json.dumps(data), ex=1800)
    return success(data)