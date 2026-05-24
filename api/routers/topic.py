from fastapi import HTTPException, Request, Depends, APIRouter, Path, Query, Body, UploadFile, File
from dependencies import get_db, get_redis, get_config, get_sessionID, get_sessionID_login, get_uid_by_headers, get_finger
from functions import *
from typing import Optional
from pydantic import BaseModel, Field
import json
import random
import secrets
from enum import Enum
from typing import List
import re
import time
from models import *
import shutil
import os
from pathlib import Path as PATH

class reply_model(BaseModel):
    tid: int
    content: str
    rid: int = None

router = APIRouter(prefix="/topic", tags=["帖子"])





@router.get("/{tid}/score", summary="获取指定tid的帖子评分", description="如果填入用户就找特定用户的，不填就是总评分")
async def API_get_score(
    tid: int = Path(..., description="帖子ID"),
    full: bool = Query(False, description="是否获取完整详细评分"),
    pool = Depends(get_db)
):
    if full == False:
        return success(await get_score(pool, tid))
    else:
        return success(await get_score(pool, tid, True))



@router.put("/score", summary="提交 / 更改新评分", description="需要登录")
async def API_get_score(
    tid: int = Body(..., embed=True, description="帖子ID"),
    score: float = Body(..., embed=True, description="评分"),
    state: str = Body(..., embed=True, description="游玩状态"),
    content: str = Body(..., embed=True, description="简评"),
    pool = Depends(get_db),
    rds = Depends(get_redis),
    uid = Depends(get_uid_by_headers)
):

    # 更新个人评分
    date = await get_date()
    sharding = str(uid)[-1]
    table = f"scores_{sharding}"
    await db_update(pool, "DELETE FROM {} WHERE tid=%s AND uid=%s".format(table), (tid, uid, ))
    await db_insert(
        pool, 
        "INSERT INTO {} (tid, uid, date, score, state, content) VALUES (%s, %s, %s, %s, %s, %s)".format(table),
        (tid, uid, date, score, state, content)
    )

    # 获取帖子的所有评分用户，重新计算平均分
    row = await db_fetchone(pool, "SELECT uids FROM scores WHERE tid=%s LIMIT 1", (tid, ))

    # 帖子首次评分
    if not row:
        await db_insert(pool, "INSERT INTO scores (tid, uids, avg) VALUES (%s, %s, %s)", (tid, uid, score))
        return success("评分已完成提交！")

    # 获取所有分数
    scores = []
    uids = []
    for i in range(10):
        table = f"scores_{i}"
        rows = await db_rows(
            pool, 
            "SELECT uid, score FROM {} WHERE tid=%s".format(table),
            (tid, )
        )
        for row in rows:
            uids.append(row['uid'])
            scores.append(float(row['score']))

    # 计算新平均分
    avg = sum(scores) / len(scores)
    uids = "|".join([str(uid) for uid in uids])
    await db_update(pool, "UPDATE scores SET uids=%s, avg=%s WHERE tid=%s LIMIT 1", (uids, avg, tid))

    # 清理redis缓存
    await rds.delete(f"topic:{tid} True True")
    return success("评分已完成提交！")



@router.get("/{tid}/reply", summary="获取指定 tid 的历史回复", description="")
async def API_get_reply(
        tid: int = Path(..., description="帖子 ID"),
        pool = Depends(get_db),
        rds = Depends(get_redis),
        config = Depends(get_config)
    ):

    reply = await rds.get(f"reply:{tid}")
    if reply: return success(json.loads(reply))

    # 获取最新rid
    sharding = str(tid)[-1]
    async with pool.acquire() as conn:
        async with conn.cursor(asyncmy.cursors.DictCursor) as cur:
            await cur.execute(
                "SELECT rid, uid, content, date, reply_rid FROM {} WHERE tid=%s ORDER BY rid DESC LIMIT 500".format(f"replies_{sharding}"), 
                (tid, )
            )
            rows = await cur.fetchall()

    if not rows:
        return fail("此贴无回复区")

    # 获取回复里每个uid对应的用户信息
    for row in rows:
        row['poster'] = await get_user_old(pool, config, row['uid'])

    # 缓存进redis
    await rds.set(f"reply:{tid}", json.dumps(rows), ex=1800)
    return success(rows)



@router.post("/{tid}/reply", summary="回复帖子 / 回复回复", description="除了rid选填，其余参数必填。<br>如果写了rid就是针对某个回复进行回复，rid=楼层数，正常回复就别带参数rid。<br>sessionID填了的话finger就填uid即可，如果是游客，sessionID填“游客”，finger需要随便填。")
async def API_reply(
    model: reply_model,
    pool = Depends(get_db),
    rds = Depends(get_redis),
    config = Depends(get_config),
    sessionID = Depends(get_sessionID),
    finger = Depends(get_finger)
):
    tid = model.tid
    content = model.content
    rid = model.rid

    if not sessionID:
        uid = 4523

    # 找uid，如果找不到就当游客处理
    else:
        uid = await get_uid_by_sessionID(sessionID, rds, pool)

    # 判断是否为重复回复
    sharding = str(tid)[-1]
    row = await db_fetchone(pool, "SELECT content, date FROM {} WHERE tid=%s".format(f"replies_{sharding}"), (tid, ))
    date = await get_date()
    if row:
        if row['date'] == date and row['content'] == content:
            return fail("您今天已回复了同样的内容！此次回复作废！")

    # 获取一个rid
    n_rid = await db_auto_increment_value(pool, "rid")

    # 指定tid回复量 + 1
    fid = await get_fid(pool, tid)
    await db_update(pool, "UPDATE `{}` SET reply_count = reply_count + 1 WHERE tid=%s".format(f"topics_{fid}_{sharding}"), (tid, ))

    # 将回复信息储存进数据库
    await db_insert(
        pool,
        "INSERT INTO {} (tid, rid, uid, content, date, reply_rid) VALUES (%s, %s, %s, %s, %s, %s)".format(f"replies_{sharding}"), 
        (tid, n_rid, uid, content, date, rid, )
    )

    # 通知到作者
    date = await get_date("all")

    if not rid:
        row = await get_topic(pool, config, tid, False, "uid", False, fid, False)
        auther_uid = row['uid']

        await msg_add(pool, auther_uid, f"$user 回复了你的帖子 $title：{content}", tid, uid)
        await log_add_source(f"{date} {uid}({finger})回复帖子{tid}")

    # 通知到楼主
    else:
        row = await db_fetchone(pool, "SELECT uid FROM {} WHERE rid=%s LIMIT 1".format(f"replies_{sharding}"), (rid, ))
        op_uid = row['uid']

        await msg_add(pool, op_uid, f"$user 在帖子 $title 回复了您：{content}", tid, uid)
        await log_add_source(f"{date} {uid}({finger})在帖子{tid}回复楼{rid}")

    # 清理redis缓存
    await rds.delete(f"reply:{tid}")
    return success("回复成功")



@router.delete("/{tid}/reply", summary="删除回复", description="不要恶意调用这个接口！")
async def API_delete_reply(
    tid: int = Path(..., description="帖子ID"),
    rid: int = Query(..., description="回复楼层ID"),
    pool = Depends(get_db),
    rds = Depends(get_redis),
    config = Depends(get_config),
    uid = Depends(get_uid_by_headers)
):

    # 判断tid和rid的合法性
    sharding = str(tid)[-1]
    row = await db_fetchone(pool, "SELECT uid, content FROM {} WHERE rid=%s".format(f"replies_{sharding}"), (rid, ))
    if not row:
        return fail("该tid内的所有rid未找到对应的楼层")

    # 判断sessionID是否为管理员
    if uid not in config['administrators']:

        # 判断是不是作者自己
        auther_uid = int(row['uid'])
        if uid != auther_uid:
            return fail("非管理员或者非评论楼主")

    # 执行删除
    await db_update(pool, "DELETE FROM {} WHERE rid=%s".format(f"replies_{sharding}"), (rid, ))

    # 日志记录
    content = row['content']
    await msg_add(pool, auther_uid, f"您在帖子 $title 下的回复：{content} 已被删除，如有疑问可以发帖咨询！", tid)

    date = await get_date("all")
    await log_add_source(f"{date} {uid}删除{auther_uid}的回复：{content}")

    # 清理redis缓存
    await rds.delete(f"reply:{tid}")
    return success("删除成功")


@router.get("/{tid}/download", summary="获取帖子内的资源下载链接", description="请不要滥用这个接口！")
async def API_download(
    request: Request,
    tid: int = Path(..., description="帖子ID"),
    pool = Depends(get_db),
    rds = Depends(get_redis),
    sessionID = Depends(get_sessionID),
    finger = Depends(get_finger)
):

    # 获取客户端 IP（兼容代理）
    client_IP = await get_client_ip(request)

    # 获取redis缓存
    num = await rds.incr(f"reg_download:{client_IP}")
    if num == 1:
        
        # 第一次下载设置过期时间
        await rds.expire(f"reg_download:{client_IP}", 1800)
    if num > 30:
        ttl = await rds.ttl(f"reg_download:{client_IP}")
        s = round(ttl / 60)
        return fail(f"当前IP达到下载限制，请等待{s}分钟后再试试吧！")

    # 获取所有链接
    row = await db_fetchone(pool, "SELECT * FROM wangpan_urls WHERE tid=%s LIMIT 1", (tid, ))
    if not row:
        return fail("该帖子不存在链接！若不是帖子所有者请耐心等待作者上传链接！若长时间未出现链接可以发评论提醒作者！")
    else:

        # 日志记录
        date = await get_date("all")
        if not sessionID:
            await log_add_source(f"{date} {finger}下载{tid}")
        else:  
            uid = await get_uid_by_sessionID(sessionID, rds, pool)
            await log_add_source(f"{date} {uid}下载{tid}")
            
        return success(row)



@router.post("/{tid}/download", summary="提交帖子下载链接", description="urls模型里的4个网盘链接都必须传参，没有就传空字符串！")
async def API_download_upload(
    model: urls_model,
    tid: int = Path(..., description="帖子ID"),
    pool = Depends(get_db),
    config = Depends(get_config),
    uid = Depends(get_uid_by_headers)
):
    baidu = model.baidu
    OD = model.OD
    direct = model.direct
    else_url = model.else_url

    # 判断空链接
    if "http" not in baidu and "http" not in OD and "http" not in direct and "http" not in else_url:
        return fail("链接全部无效！请正确传参！")

    # 判断是管理员或者帖子所有者
    if uid in config['administrators'] and await auther(pool, tid, uid) == False:
        return fail("非管理员或帖子所有者无权限！")
    else:
    
        # 链接已存在
        row = await db_fetchone(pool, "SELECT 1 FROM wangpan_urls WHERE tid=%s LIMIT 1", (tid, ))
        if row:
            return fail("帖子里已存有链接，若想插入新链接请先清空帖子里的链接再插入！")

        # 填入新链接
        await db_insert(pool, "INSERT INTO wangpan_urls (tid, baidu, onedrive, direct_link, else_url) VALUES (%s, %s, %s, %s, %s)", (tid, baidu, OD, direct, else_url, ))

        # 日志记录
        date = await get_date("all")
        await log_add_source(f"{date} {uid}上传链接{tid}")
        return success("上传成功！")
        



@router.delete("/{tid}/download", summary="移除帖子下载链接", description="请不要滥用这个接口！")
async def API_download_remove(
    tid: int = Path(..., description="帖子ID"),
    pool = Depends(get_db),
    rds = Depends(get_redis),
    config = Depends(get_config),
    sessionID = Depends(get_sessionID)
):
    # 判断是管理员或者帖子所有者
    uid = await get_uid_by_sessionID(sessionID, rds, pool)
    if uid in config['administrators'] or await auther(pool, tid, uid) == True:

        # 移除所有网盘链接
        await db_update(pool, "DELETE FROM wangpan_urls WHERE tid=%s LIMIT 1", (tid, ))

        # 日志记录
        date = await get_date("all")
        await log_add_source(f"{date} {uid}清空下载链接{tid}")
        return success("删除成功！")

    else:
        return fail("无权限移除！")












@router.get("/{tid}/collection/{uid}", summary="获取指定uid的tid收藏状态", description="只有输出success")
async def API_collection_get(
    tid: int = Path(..., description="帖子ID"),
    uid: int = Path(..., description="用户ID"),
    pool = Depends(get_db)
):
    if await get_collection_state(pool, tid, uid) == True:
        return success({"collection_state": True})
    else:
        return success({"collection_state": False})



@router.post("/{tid}/collection", summary="添加收藏", description="需要登录")
async def API_post_collection(
    tid: int = Path(..., description="帖子ID"),
    pool = Depends(get_db),
    rds = Depends(get_redis),
    uid = Depends(get_uid_by_headers)
):
    # 查表看看存在不存在
    if get_collection_state(pool, tid, uid) == True:
        return fail("已经收藏过了")
    
    # 添加
    else:
        sharding = str(uid)[-1]
        table = f"collection_{sharding}"
        await db_insert(pool, "INSERT INTO {} (uid, tid) VALUES (%s, %s)".format(table), (uid, tid))
        
        await rds.delete(f"space:{uid}")
        return success("已收藏本贴，你可以从右上角进入\"个人空间\"查看已收藏的帖子。")



@router.delete("/{tid}/collection", summary="取消收藏", description="需要登录")
async def API_delete_collection(
    tid: int = Path(..., description="帖子ID"),
    pool = Depends(get_db),
    rds = Depends(get_redis),
    uid = Depends(get_uid_by_headers)
):
    # 查表看看存在不存在
    if get_collection_state(pool, tid, uid) == False:
        return fail("未收藏！")
    
    # 删除
    else:
        sharding = str(uid)[-1]
        table = f"collection_{sharding}"
        await db_update(pool, "DELETE FROM {} WHERE uid=%s AND tid=%s".format(table), (uid, tid, ))

        await rds.delete(f"space:{uid}")
        return success("取消收藏成功！")













@router.post("", summary="发帖", description="发帖成功会返回一个tid")
async def API_send_topic(
    model: send_topic_model,
    pool = Depends(get_db),
    rds = Depends(get_redis),
    config = Depends(get_config),
    sessionID = Depends(get_sessionID)
):
    if not sessionID:
        return fail("该接口调用需要登录！")

    title = model.title
    content = model.content
    tags = model.tags
    cover = model.cover
    fid = model.fid

    # 标题合法性
    # if len(content.encode("utf-8")) > 255:
    #     return fail("标题超过255个字节！请适当缩短标题！")

    # cover合法性
    if fid == "1-1" or fid == "1-2" or fid == "1-3" or fid == "1-4":
        if "|" in cover:
            return fail("封面预览图不合法！你所选的板块只支持单个图片做预览图！")

    uid = await get_uid_by_sessionID(sessionID, rds, pool)

    # 根据今日发帖数去排列最近发帖
    ts = await timestamp()
    row = await db_fetchone(pool, "SELECT COUNT(tid) AS count FROM topics_index WHERE last_modify BETWEEN %s AND %s", (ts - 1, ts + 100, ))
    count = row['count']
    ts = ts + count

    # 获取最新tid
    row = await db_fetchone(pool, "SELECT value FROM sys_auto_increment_value WHERE variable='tid' LIMIT 1")
    tid = int(row['value'])
    sharding = str(tid)[-1]
    await db_update(pool, "UPDATE sys_auto_increment_value SET value = value + 1 WHERE variable='tid'")

    # tags转成tagsID，backup是给拔作不推送计算使用的
    if tags:
        tags_backup = tags

        # "夏日": 31
        tags_json = await tags_to_ID(pool, tags.split("|"), True)
        place = ""
        tags = []
        for TAG, ID in tags_json.items():
            place += f"({tid}, {ID}),"
            tags.append(str(ID))
        place = place[:-1]

        # ID入库tid TAG关系表
        await db_insert(pool, f"INSERT INTO search_tags (tid, tagID) VALUES {place}")
        tags = "|".join(tags)

    # 帖子入库
    table = f"topics_{fid}_{sharding}"
    date = await get_date()
    await db_insert(
        pool,
        "INSERT INTO `{}` (tid, title, content, uid, date, tags, preview, view_count, reply_count) VALUES (%s, %s, %s, %s, %s, %s, %s, 0, 0)".format(table),
        (tid, title, content, uid, date, tags, cover, )
    )
    await db_insert(
        pool,
        "INSERT INTO `topics_index` (fid, tid, last_modify) VALUES (%s, %s, %s)",
        (fid, tid, ts)
    )

    # 对帖子作者猫罐头 + 1
    sharding = str(uid)[-1]
    table = f"users_data_{sharding}"
    await db_update(pool, "UPDATE {} SET canned_count = canned_count + 1 WHERE uid=%s LIMIT 1".format(table), (uid, ))

    # 文件夹缓存变更，用的是R2可以直接重命名，如果是rclone挂载OneDrive，则需要复制+粘贴+删除才能完成重命名操作
    cache = await md5(str(uid))
    sharding = await chunk(config, tid, "data", True)
    old_path = PATH(f"{config['path']}/data/forums/{sharding}/data{sharding}/{cache}")
    if old_path.exists():
        new_path2 = PATH(f"{config['path']}/data/forums/{sharding}/data{sharding}/{tid}")
        old_path.rename(new_path2)
    
    # 生成缩略图
    if cover:
        if fid == "1-1" or fid == "1-2" or fid == "1-3" or fid == "1-4":
            in_path = PATH(f"{config['path']}/data/forums/{sharding}/data{sharding}/{tid}/{cover}.avif")
            out_path = PATH(f"{config['path']}/data/forums/{sharding}/data{sharding}/{tid}/preview.avif")
            await avif(in_path, out_path, 80, 400)
        else:
            aids = cover.split("|")
            i = 0
            for aid in aids:
                in_path = PATH(f"{config['path']}/data/forums/{sharding}/data{sharding}/{tid}/{aid}.avif")
                out_path = PATH(f"{config['path']}/data/forums/{sharding}/data{sharding}/{tid}/preview_{i}.avif")
                await avif(in_path, out_path, 80, 400)
                i += 1

    # 更新vid索引
    await db_update(pool, "DELETE FROM vids_index WHERE tid=%s", (tid, ))
    path = PATH(f"{config['path']}/data/forums/{sharding}/data{sharding}/{tid}")
    mp4_list = [i.name for i in path.glob("*.mp4")]
    for name in mp4_list:
        vid = name.replace(".mp4")
        await db_insert(pool, "INSERT INTO `vids_index` (vid, tid) VALUES (%s, %s)", (vid, tid, ))
    
    # 拔作TAG的帖子不推送
    if tags and "拔作" in tags_backup.split("|"):
        await db_update(pool, "UPDATE topics_index SET no_push=1 WHERE tid=%s", (tid, ))
        await db_update(pool, "UPDATE vids_index SET no_push=1 WHERE tid=%s", (tid, ))
    
    # 日志记录
    await log_add_source(f"{date} {uid}在{fid}发帖：{title}(${tid})")

    await rds.flushdb()
    return success(tid)



@router.put("", summary="修改帖子", description="成功后会返回帖子ID（tid）")
async def API_put_topic(
    model: replace_topic_model,
    pool = Depends(get_db),
    rds = Depends(get_redis),
    config = Depends(get_config),
    sessionID = Depends(get_sessionID)
):
    if not sessionID:
        return fail("该接口需要登录才能调用！")

    title = model.title
    content = model.content
    tags = model.tags
    cover = model.cover
    tid = model.tid

    # 标题合法性
    # if len(content.encode("utf-8")) > 255:
    #     return fail("标题超过255个字节！请适当缩短标题！")
    
    # 管理员或者帖子所有者才能编辑
    uid = await get_uid_by_sessionID(sessionID, rds, pool)
    auther_uid = await auther(pool, tid, uid, True)
    if uid not in config['administrators'] and uid != auther_uid:
        return fail("没有权限去更改这个帖子！")

    # fid
    fid = await get_fid(pool, tid)
    board = config['board'][fid]

    # 不是作者更改帖子，通知作者
    if uid != auther_uid:
        await msg_add(pool, auther_uid, f"$user 在板块 {board}({fid}) 重新编辑了您的帖子：$title", tid, uid)

    # cover合法性
    if fid == "1-1" or fid == "1-2" or fid == "1-3" or fid == "1-4":
        if "|" in cover:
            return fail("封面预览图不合法！你所选的板块只支持单个图片做预览图！")

    # 根据今日发帖数去排列最近发帖
    ts = await timestamp()
    row = await db_fetchone(pool, "SELECT COUNT(tid) AS count FROM topics_index WHERE last_modify BETWEEN %s AND %s", (ts - 1, ts + 100, ))
    count = row['count']
    ts = ts + count

    # tags转成tagsID，backup是给拔作不推送计算使用的
    if tags:
        tags_backup = tags

        # "夏日": 31
        tags_json = await tags_to_ID(pool, tags.split("|"), True)
        place = ""
        tags = []
        for TAG, ID in tags_json.items():
            place += f"({tid}, {ID}),"
            tags.append(str(ID))
        place = place[:-1]

        # ID入库tid TAG关系表
        await db_update(pool, "DELETE FROM search_tags WHERE tid=%s", (tid, ))
        await db_insert(pool, f"INSERT INTO search_tags (tid, tagID) VALUES {place}")
        tags = "|".join(tags)



    # 拔作TAG的帖子不推送
    if tags and "拔作" in tags_backup.split("|"):
        await db_update(pool, "UPDATE topics_index SET no_push=1 WHERE tid=%s", (tid, ))
        await db_update(pool, "UPDATE vids_index SET no_push=1 WHERE tid=%s", (tid, ))

    # 更新数据库
    sharding = str(tid)[-1]
    table = f"topics_{fid}_{sharding}"
    await db_update(
        pool, 
        "UPDATE `{}` SET title=%s, content=%s, tags=%s, preview=%s WHERE tid=%s LIMIT 1".format(table),
        (title, content, tags, cover, tid)
    )

    # 更新topics_index索引列表
    await db_update(
        pool,
        "UPDATE topics_index SET last_modify=%s WHERE tid=%s LIMIT 1",
        (ts, tid)
    )

    # 生成缩略图
    sharding = await chunk(config, tid, "data", True)
    if cover:
        if fid == "1-1" or fid == "1-2" or fid == "1-3" or fid == "1-4":
            in_path = PATH(f"{config['path']}/data/forums/{sharding}/data{sharding}/{tid}/{cover}.avif")
            out_path = PATH(f"{config['path']}/data/forums/{sharding}/data{sharding}/{tid}/preview.avif")
            await avif(in_path, out_path, 80, 400)
        else:
            aids = cover.split("|")
            i = 0
            for aid in aids:
                in_path = PATH(f"{config['path']}/data/forums/{sharding}/data{sharding}/{tid}/{aid}.avif")
                out_path = PATH(f"{config['path']}/data/forums/{sharding}/data{sharding}/{tid}/preview_{i}.avif")
                await avif(in_path, out_path, 80, 400)

                i += 1

    # 更新vid索引
    await db_update(pool, "DELETE FROM vids_index WHERE tid=%s", (tid, ))
    path = PATH(f"{config['path']}/data/forums/{sharding}/data{sharding}/{tid}")
    mp4_list = [i.name for i in path.glob("*.mp4")]
    for name in mp4_list:
        vid = name.replace(".mp4", "")
        await db_insert(pool, "INSERT INTO `vids_index` (vid, tid) VALUES (%s, %s)", (vid, tid, ))

    # 日志记录
    date = await get_date()
    await log_add_source(f"{date} {uid}修改帖子：{title}(${tid})")

    await rds.flushdb()
    return success(tid)


    
@router.put("/fid", summary="修改帖子所在板块", description="由于板块转移功能很少用，我就没集成在修改帖子的接口里。")
async def API_collection_remove(
    pool = Depends(get_db),
    rds = Depends(get_redis)
):
    return 23



@router.get("/imgs", summary="获取指定帖子的所有图片aid", description="如果帖子是已经存在的则需要传参tid，如果帖子正在编辑中，则不需要传参tid，只需要headers里带sessionID。")
async def API_get_topic_imgs(
    tid: int = Query(None, description="帖子ID"),
    pool = Depends(get_db),
    uid = Depends(get_uid_by_headers),
    config = Depends(get_config)
):
    # 判断权限
    if tid:
        if uid not in config['administrators'] and await auther(pool, tid, uid) == False:
            return fail("你没有权限！")

    # 获取帖子路径
    if tid:
        path, url = await get_topic_path(pool, config, "tid", tid, True)
    else:
        path, url = await get_topic_path(pool, config, "uid", uid, True)

    # 循环帖内所有文件
    data = []
    for i in path.glob("*.avif"):
        name = i.name.replace(".avif", "")
        file_path = f"{url}/{name}.avif"
        data.append({
            "name": name,
            "path": file_path
        })

    return success(data)



@router.post("/img", summary="上传图片到帖子文件夹", description="如果帖子已经存在则必须传参tid，如果正在编辑中，会从headers获取sessionID，<br>后缀仅支持jpg jpeg png webp avif。上传限制20MB。<br>我建议如果调用接口去上传的话只上传jpg格式，因为后端会自动压缩成80质量的avif图片，需要上传原图的还是建议调用附件接口吧。")
async def API_POST_topic_imgs(
    tid: int = Query(None, description="帖子ID"),
    file: UploadFile = File(..., description="图片文件"),
    pool = Depends(get_db),
    uid = Depends(get_uid_by_headers),
    config = Depends(get_config)
):
    # 操控权限检测
    if tid:
        if uid not in config['administrators'] and await auther(pool, tid, uid) == False:
            return fail("你没有权限向该帖子上传图片！")

    # 文件合法性检测
    ext = PATH(file.filename).suffix.lower().replace(".", "")
    ALLOW_EXT = ["jpg","jpeg","png","webp","avif"]
    if ext not in ALLOW_EXT:
        return fail("文件后缀不支持！仅支持jpg jpeg png webp avif")
    
    # 文件大小检测
    file.file.seek(0, 2)
    size = file.file.tell()
    file.file.seek(0)
    if size > 20 * 1024 * 1024:
        return fail("文件大小超过20MB，禁止上传！")

    # 帖子文件路径
    if tid:
        path = await get_topic_path(pool, config, "tid", tid)
    else:
        path = await get_topic_path(pool, config, "uid", uid)

    # 如果帖子文件夹不存在则创建
    path.mkdir(parents=True, exist_ok=True)

    # 获取最新aid
    aid = await db_auto_increment_value(pool, "aid")

    # 移入文件
    with open(path / f"{aid}.{ext}", "wb") as f:
        shutil.copyfileobj(file.file, f)

    # 压缩成avif格式
    in_path = path / f"{aid}.{ext}"
    out_path = path / f"{aid}.avif"
    await avif(in_path, out_path)

    # 删除源文件
    in_path.unlink(missing_ok=True)

    return success(aid)



@router.delete("/imgs/{aid}", summary="删除指定aid", description="如果帖子是已经存在的则需要传参tid，如果帖子正在编辑中，则不需要传参tid，只需要headers里带sessionID。")
async def API_delete_topic_imgs(
    aid: int = Path(..., description="图片ID"),
    tid: int = Query(None, description="帖子ID"),
    pool = Depends(get_db),
    config = Depends(get_config),
    uid = Depends(get_uid_by_headers)
):
    # 操控权限检测
    if tid:
        if uid not in config['administrators'] and await auther(pool, tid, uid) == False:
            return fail("你没有权限删除该帖子的图片！")

    # 帖子文件路径
    if tid:
        path = await get_topic_path(pool, config, "tid", tid)
    else:
        path = await get_topic_path(pool, config, "uid", uid)

    # 删除源文件
    file_name = path / f"{aid}.avif"
    file_name.unlink(missing_ok=True)

    return success("删除成功！")



@router.get("/videos", summary="获取指定帖子的所有视频vid", description="如果帖子是已经存在的则需要传参tid，如果帖子正在编辑中，则不需要传参tid，只需要headers里带sessionID。")
async def API_get_topic_videos(
    tid: int = Query(None, description="帖子ID"),
    pool = Depends(get_db),
    uid = Depends(get_uid_by_headers),
    config = Depends(get_config)
):
    if tid:
        if uid not in config['administrators'] and await auther(pool, tid, uid) == False:
            return fail("你没权限！")

    # 帖子文件路径
    if tid:
        path = await get_topic_path(pool, config, "tid", tid)
    else:
        path = await get_topic_path(pool, config, "uid", uid)

    # 循环帖内所有文件
    data = []
    for i in path.glob("*.mp4"):
        name = i.name.replace(".mp4", "")
        data.append({
            "name": name
        })

    return success(data)



@router.post("/videos", summary="上传视频切片到帖子文件夹", description="如果帖子是已经存在的则需要传参tid，如果帖子正在编辑中，则不需要传参tid，只需要headers里带sessionID。<br>需要注意接口仅支持切片上传，需要自行切片，切片大小最好自己控制在10MB以下，仅支持mp4格式。<br>发现恶意调用该接口的用户，将进行封号处理！")
async def API_post_videos(
    tid: int = Query(None, description="帖子ID"),
    file: UploadFile = File(..., description="视频切片"),
    pool = Depends(get_db),
    uid = Depends(get_uid_by_headers),
    config = Depends(get_config)
):
    # 操控权限检测
    if tid:
        if uid not in config['administrators'] and await auther(pool, tid, uid) == False:
            return fail("你没有权限！")

    # 获取path
    chunk = PATH(file.filename).suffix.lower().replace(".", "")
    if tid:
        path = await get_topic_path(pool, config, "tid", tid)
    else:
        path = await get_topic_path(pool, config, "uid", uid)
    os.makedirs(path, exist_ok=True)

    # 删除已有切片
    if int(chunk) == 0:
        for i in path.glob("video.mp4.*"):
            name = i.name
            file_path = path / f"{name}"
            file_path.unlink(missing_ok=True)

    # 储存切片
    with open(path / f"video.mp4.{chunk}", "wb") as f:
        shutil.copyfileobj(file.file, f)

    return success("上传成功！")




@router.delete("/videos/{vid}", summary="删除帖子内的视频", description="如果帖子是已经存在的则需要传参tid，如果帖子正在编辑中，则不需要传参tid，只需要headers里带sessionID。")
async def API_delete_videos(
    vid: int = Path(..., description="视频ID"),
    tid: int = Query(None, description="帖子ID"),
    pool = Depends(get_db),
    uid = Depends(get_uid_by_headers),
    config = Depends(get_config)
):
    # 操控权限检测
    if tid:
        if uid not in config['administrators'] and await auther(pool, tid, uid) == False:
            return fail("你没有权限！")

    # 获取path
    if tid:
        path = await get_topic_path(pool, config, "tid", tid)
    else:
        path = await get_topic_path(pool, config, "uid", uid)

    # 删除源文件
    file_path = path / f"{vid}.mp4"
    file_path.unlink(missing_ok=True)

    return success("删除成功！")



@router.get("/videos/chunk/{max}", summary="视频切片检查", description="如果帖子是已经存在的则需要传参tid，如果帖子正在编辑中，则不需要传参tid，只需要headers里带sessionID。<br>返回的是一个列表")
async def API_get_videos_chunk(
    max: int = Path(..., description="总切片数量"),
    tid: int = Query(None, description="帖子ID"),
    pool = Depends(get_db),
    uid = Depends(get_uid_by_headers),
    config = Depends(get_config)
):
    # 获取path
    if tid:
        path = await get_topic_path(pool, config, "tid", tid)
    else:
        path = await get_topic_path(pool, config, "uid", uid)

    # 找出缺失的切片
    data = []
    for i in range(max):
        file_path = path / f"video.mp4.{i}"
        if not file_path.exists():
            data.append(i)

    return success(data)



@router.post("/videos/chunk/{max}", summary="视频切片合并", description="如果帖子是已经存在的则需要传参tid，如果帖子正在编辑中，则不需要传参tid，只需要headers里带sessionID。")
async def API_post_videos_chunk(
    max: int = Path(..., description="总切片数量"),
    tid: int = Query(None, description="帖子ID"),
    pool = Depends(get_db),
    uid = Depends(get_uid_by_headers),
    config = Depends(get_config)
):
    # 获取path
    if tid:
        path = await get_topic_path(pool, config, "tid", tid)
    else:
        path = await get_topic_path(pool, config, "uid", uid)

    # 获取vid
    vid = await db_auto_increment_value(pool, "vid")
    out_path = path / f"{vid}.mp4"

    with open(out_path, "wb") as out:
        for i in range(max):
            chunk_path = path / f"video.mp4.{i}"

            with open(chunk_path, "rb") as f:
                while True:
                    data = f.read(1024 * 1024)
                    if not data:
                        break
                    out.write(data)
            chunk_path.unlink(missing_ok=True)

    return success("合并完成！")





@router.get("/{tid}", summary="获取指定 tid 的帖子数据", description="")
async def API_get_topic(
        request: Request,
        tid: int = Path(..., description="帖子 ID"),
        tags_decode: bool = Query(True, description="是否解析 tagID 为对应的中文"),
        full: bool = Query(True, description="是否获取完整详细评分"),
        pool = Depends(get_db),
        rds = Depends(get_redis),
        config = Depends(get_config),
        finger = Depends(get_finger)
    ):

    # 日志记录
    identifier = finger if finger else await get_client_ip(request)
    date = await get_date("all")
    await log_add_source(f"{date} {identifier}访问帖子{tid}")

    # 查redis缓存做浏览量+1
    view_add = await rds.get(f"view:{tid}:{identifier}")
    if not view_add:
        await rds.set(f"view:{tid}:{identifier}", 1, ex=300)
        allow_add = True
    else:
        allow_add = False

    # 查redis缓存
    if (tags_decode == True):
        topic_data = await rds.get(f"topic:{tid} {tags_decode} {full}")
        if topic_data: return success(json.loads(topic_data))

    data = {}

    # 查询帖子
    try:
        data['topic'] = await get_topic(pool, config, tid, tags_decode, "*", True, None, allow_add)
    except:
        return fail("tid未找到帖子数据！")

    # 获取所有评分信息
    data['scores'] = await get_score(pool, tid, full)

    # 缓存进redis
    await rds.set(f"topic:{tid} {tags_decode} {full}", json.dumps(data), ex=1800)
    return success(data)



@router.delete("/{tid}", summary="清除指定帖子", description="必须是管理员或则是帖子所有者才能删除")
async def API_remove_topic(
    tid: int = Path(..., description="帖子ID"),
    reason: str = Body(..., embed=True, description="删贴原因"),
    pool = Depends(get_db), 
    rds = Depends(get_redis),
    config = Depends(get_config),
    uid = Depends(get_uid_by_headers)
):
    # 判断tid合法性
    fid = await get_fid(pool, tid)
    sharding = str(tid)[-1]
    table = f"topics_{fid}_{sharding}"
    row = await db_fetchone(pool, "SELECT title, uid FROM `{}` WHERE tid=%s LIMIT 1".format(table), (tid, ))
    auther_uid = row['uid']
    if not row:
        return fail("该tid未查询到相关帖子")

    # 判断sessionID是否为管理员
    if uid not in config['administrators']:
        
        # 判断是不是作者自己
        if uid != auther_uid:
            return fail("非管理员或者非贴子所有人")

    # 获取帖子标题，记录日志用
    title = row['title']

    # 根据分表删除帖子索引和数据
    await db_update(pool, "DELETE FROM topics_index WHERE tid=%s LIMIT 1", (tid, ))
    await db_update(pool, "DELETE FROM `{}` WHERE tid=%s LIMIT 1".format(f"topics_{fid}_{sharding}"), (tid, ))

    # 删除所有有关回复
    await db_update(pool, "DELETE FROM {} WHERE tid=%s".format(f"replies_{sharding}"), (tid, ))

    # 删除vid索引
    await db_update(pool, "DELETE FROM vids_index WHERE tid=%s", (tid, ))

    # 删除帖子评分
    await db_update(pool, "DELETE FROM scores WHERE tid=%s", (tid, ))
    for i in range(10):
        await db_update(pool, "DELETE FROM {} WHERE tid=%s".format(f"scores_{i}"), (tid, ))

    # 删除tag关系表
    await db_update(pool, "DELETE FROM search_tags WHERE tid=%s", (tid, ))

    # 删除收藏
    for i in range(10):
        await db_update(pool, "DELETE FROM {} WHERE tid=%s".format(f"collection_{i}"), (tid, ))

    # 
    # 以下是关联更新
    # 
    # 如果每日推荐中包含，则重新创建每日推荐
    row = await db_fetchone(pool, "SELECT value FROM sys_auto_increment_value WHERE variable='recommend'")
    recommends = row['value'].split("|")
    if str(tid) in recommends:
        await create_recommends(pool)

    # 执行者风纪执行+1
    sharding = str(uid)[-1]
    await db_update(pool, "UPDATE {} SET judment_count = judment_count + 1 WHERE uid=%s".format(f"users_data_{sharding}"), (uid, ))
   
    # 被执行者风纪执行-1 同时 奶酪罐头-1
    sharding = str(auther_uid)[-1]
    await db_update(pool, "UPDATE {} SET judment_count = judment_count - 1 WHERE uid=%s".format(f"users_data_{sharding}"), (auther_uid, ))
    await db_update(pool, "UPDATE {} SET canned_count = canned_count - 1 WHERE uid=%s".format(f"users_data_{sharding}"), (auther_uid, ))

    # 删除帖子文件夹
    sharding = await chunk(config, tid, "data", True)
    topic_path = PATH(__file__).parent.parent.parent / f"data/forums/{sharding}/data{sharding}/{tid}"
    shutil.rmtree(topic_path, ignore_errors=True)

    # 日志记录
    date = await get_date("all")
    await msg_add(pool, auther_uid, f"$user 对你的帖子 {title} 进行了风纪执行，执行理由：{reason}", None, uid)
    await log_add_source(f"{date} {uid}删除帖子{tid}: {title}")

    # 清理redis缓存
    await rds.flushdb()
    return success("删除成功")