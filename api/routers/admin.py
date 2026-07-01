from fastapi import HTTPException, Request, Depends, APIRouter, Path, Query, File, UploadFile
from dependencies import *
from functions import *
from typing import Optional
from pydantic import BaseModel, Field
import asyncmy
from models import *
import shutil
from pathlib import Path as PATH
import subprocess
import zipfile

router = APIRouter(prefix="/admin", tags=["管理员"])



@router.get("/auth", summary="验证管理员身份", description="根据前端cookie判断是否为管理员")
async def API_auth(uid: int = Depends(get_uid)):
    administrators = [1, 73]

    if uid in administrators:
        return success({"admin": True})
    
    else:
        return success({"admin": False})



@router.get("/auth/{uid}", summary="验证管理员身份", description="根据指定uid判断是否为管理员")
async def API_auth_uid(uid: int):
    administrators = [1, 73]

    if uid in administrators:
        return success({"admin": True})

    else:
        return success({"admin": False})
    


@router.post("/new_day", summary="执行服务器定时任务", description="更新每日更新，清空今日在线人数统计，爬取月慕最新资讯，爬取各大论坛数据等功能。")
async def API_new_day(model: admin, pool = Depends(get_db), config = Depends(get_config)):
    if model.psw != "aaadmin123321":
        return fail("密码错误")

    # 获取5个推荐并存入数据库
    async with pool.acquire() as conn:
        async with conn.cursor(asyncmy.cursors.DictCursor) as cur:
            await cur.execute("SELECT tid FROM topics_index WHERE fid='1-1' AND tid > 0 AND no_push IS NULL ORDER BY RAND() LIMIT 5;")
            rows = await cur.fetchall()
    tids = [str(row['tid']) for row in rows]
    tids = "|".join(tids)
    await db_update(pool, "UPDATE sys_auto_increment_value SET value=%s WHERE variable='recommend' LIMIT 1", (tids, ))

    # 结算最高在线人数
    row = await db_fetchone(pool, "SELECT COUNT(uid) as count FROM online")
    today_online = int(row['count'])
    row = await db_fetchone(pool, "SELECT value FROM sys_auto_increment_value WHERE variable='highest_online' LIMIT 1")
    highest_online = int(row['value'])
    if today_online > highest_online:
        await db_update(pool, "UPDATE sys_auto_increment_value SET value=%s WHERE variable='highest_online' LIMIT 1", (today_online, ))

    # 清空在线列表
    await db_update(pool, "DELETE FROM `online`")

    # 更新日期
    date = await get_date()
    await db_update(pool, "UPDATE sys_auto_increment_value SET value=%s WHERE variable='today' LIMIT 1", (date, ))

    # 更新月慕咨询
    await update_ym(pool)

    # 爬取最新的touchgal帖子
    await touchgal_get()

    # 月份更新
    day = int(await get_date("day"))
    if day == 1:

        # 所有非热门tag浏览量清零
        await db_update(pool, "UPDATE tags_index SET count = 0 WHERE tag NOT IN (SELECT tag FROM (SELECT tag FROM tags_index ORDER BY count DESC LIMIT 30) AS top30)")

        # 热门tag浏览量设置为1，目的是让系统有正确的sql索引
        await db_update(pool, "UPDATE tags_index SET count = 1 WHERE tag IN (SELECT tag FROM (SELECT tag FROM tags_index ORDER BY count DESC LIMIT 30) AS top30)")

    # 备份数据库
    await mysql_backup(pool, config)
    return success("执行完成")



@router.get("/clear_redis", summary="清理redis缓存", description="开发调试使用")
async def API_clear_redis(rds = Depends(get_redis)):
    await rds.flushdb()
    return success("清理完成")



@router.post("/logo", summary="上传logo图片", description="高度控制在160px以内。")
async def API_POST_topic_imgs(
    file: UploadFile = File(..., description="图片文件"),
    config = Depends(get_config)
):
    
    # 移入文件
    path = PATH(config['path'])
    with open(path / f"data/forums/3/data3/imgs/Developer/{file.filename}", "wb") as f:
        shutil.copyfileobj(file.file, f)

    return success("上传成功")




@router.put("/touchgal", summary="替换3-1板块中touchgal网站的URL", description="str为需要替换的旧网址，需要完全填写，如https://www.touchgal.us")
async def API_POST_topic_imgs(
    str = Query(..., description="需要替换的字符串"),
    pool = Depends(get_db),
    config = Depends(get_config)
):
    new = config['touchgal']
    for i in range(10):
        await db_update(
            pool,
            "UPDATE `{}` SET content = REPLACE(content, %s, %s) WHERE content LIKE %s".format(f"topics_3-1_{i}"),
            (str, new, f"%{str}%")
        )

    return success("替换完成")



@router.post("/backup", summary="备份数据库")
async def backup_database(
    pool = Depends(get_db),
    config = Depends(get_config),
    uid = Depends(get_uid_by_headers)
):
    if uid not in config["administrators"]:
        return fail("您没权限调用该接口！")

    result = await mysql_backup(pool, config)
    return success(result)









# @router.post("/remove_reply", summary="清除指定rid的回复", description="需要传参管理员sessionID")
# async def API_remove_reply(
#     model: remove_reply,
#     pool = Depends(get_db), 
#     rds = Depends(get_redis),
#     config = Depends(get_config)
# ):

#     # 判断tid和rid的合法性
#     tid = model.tid
#     rid = model.rid
#     sharding = str(tid)[-1]
#     row = await db_fetchone(pool, "SELECT uid, content FROM {} WHERE rid=%s".format(f"replies_{sharding}"), (rid, ))
#     if not row:
#         return fail("该tid内的所有rid未找到对应的楼层")

#     # 判断sessionID是否为管理员
#     uid = await get_uid_by_sessionID(model.sessionID, rds, pool)
#     if uid not in config['administrators']:

#         # 判断是不是作者自己
#         auther_uid = row['uid']
#         if uid != auther_uid:
#             return fail("非管理员或者非评论楼主")

#     # 执行删除
#     await db_update(pool, "DELETE FROM {} WHERE rid=%s".format(f"replies_{sharding}"), (rid, ))

#     # 日志记录
#     content = row['content']
#     await msg_add(pool, auther_uid, f"您在帖子 $title 下的回复：{content} 已被删除，如有疑问可以发帖咨询！", tid)

#     date = await get_date("all")
#     await log_add_source(f"{date} {uid}删除回复：{content}")

#     # 清理redis缓存
#     await rds.delete(f"reply:{tid}")
#     return success("删除成功")



