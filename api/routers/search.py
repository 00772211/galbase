from fastapi import HTTPException, Request, Depends, APIRouter, Path, Query, Body
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
from dependencies import *
from functions import *


class reply_model(BaseModel):
    sessionID: str
    finger: str
    tid: int
    content: str
    rid: int = None

router = APIRouter(prefix="/search", tags=["搜索"])



@router.get("/{kw}/space", summary="个人空间用的作品搜索", description="kw=key word")
async def API_search_work_in_space(
        request: Request,
        kw: str = Path(..., description="关键词"),
        pool = Depends(get_db),
        rds = Depends(get_redis),
        config = Depends(get_config)
    ):

    # 获取客户端 IP（兼容代理）
    client_IP = await get_client_ip(request)

    # 获取redis缓存
    num = await rds.incr(f"search_in_space:{client_IP}")
    if num == 1:

        # 第一次下载设置过期时间
        await rds.expire(f"search_in_space:{client_IP}", 300)
    if num > 10:
        ttl = await rds.ttl(f"search_in_space:{client_IP}")
        s = round(ttl / 60)
        return fail(f"当前IP达到搜索限制，请等待{s}分钟后再试试吧！")

    # 获取所有tid
    async with pool.acquire() as conn:
        async with conn.cursor(asyncmy.cursors.DictCursor) as cur:
            await cur.execute("SELECT tid, fid FROM topics_index WHERE fid='1-1' ORDER BY tid DESC")
            rows = await cur.fetchall()

    data = []
    for row in rows:
        tid = row['tid']
        fid = row['fid']
        sharding = str(tid)[-1]
        table = f"topics_{fid}_{sharding}"
        row = await db_fetchone(pool, "SELECT title FROM `{}` WHERE (title LIKE %s) AND tid=%s".format(table), (f"%{kw}%", tid, ))
        if row:
            url = await chunk(config, tid)
            data.append({
                "tid": tid,
                "title": row['title'],
                "url": url
            })

    return success(data)





@router.get("/{type}/{kw}", summary="搜索功能", description="kw=key word，tag查询支持多个tag。<br>和查询：用“,”符号，如查询“夏日”和“海岛”则需要填参kw为：“夏日,恋爱”。<br>或查询：用“|”符号，如查询“夏日”或“海岛”则需要填参kw数为：“夏日|恋爱”")
async def API_search_work_in_space(
        request: Request,
        type: search_type_model = Path(..., description="查询方法"),
        kw: str = Path(..., description="搜索关键词"),
        pool = Depends(get_db),
        rds = Depends(get_redis),
        config = Depends(get_config),
        sessionID = Depends(get_sessionID),
        finger = Depends(get_finger)
    ):

    # 获取客户端 IP（兼容代理）
    client_IP = await get_client_ip(request)

    # 获取redis缓存
    num = await rds.incr(f"search_limit:{client_IP}")
    if num == 1:

        # 第一次下载设置过期时间
        await rds.expire(f"search_limit:{client_IP}", 300)
    if num > 10:
        ttl = await rds.ttl(f"search_limit:{client_IP}")
        s = round(ttl / 60)
        return fail(f"当前IP达到搜索限制，请等待{s}分钟后再试试吧！")

    # 所有fid
    fids = []
    for k,v in config['board'].items():
        fids.append(k)

    data = []
    uids = set()
    if type == "normal" or type == "title":

        # 获取每个板块，每个分表的帖子
        for fid in fids:
            for i in range(10):
                table = f"topics_{fid}_{i}"
                
                # 全文匹配和标题匹配
                if type == "normal":
                    sql = "SELECT tid, title, uid, date FROM `{}` WHERE (title LIKE %s OR content LIKE %s)".format(table)
                    params = (f"%{kw}%", f"%{kw}%", )
                elif type == "title":
                    sql = "SELECT tid, title, uid, date FROM `{}` WHERE (title LIKE %s)".format(table)
                    params = (f"%{kw}%", )
                rows = await db_rows(pool,sql,params)

                # 每一个帖子
                for row in rows:
                    uid = int(row['uid'])
                    uids.add(uid)
                    title = row['title']

                    # 标题包含还是内容包含
                    if kw in title:
                        contain = "title"
                    else:
                        contain = "content"

                    data.append({
                        "contain": contain,
                        "tid": row['tid'],
                        "title": title,
                        "uid": uid,
                        "date": row['date'],
                        "fid": fid
                    })

        # 查每一个uid的uname
        unames = {}
        for uid in uids:
            uname = await get_uname(pool, uid)
            unames[uid] = uname

        # 日志记录
        await log_add(pool, rds, f"类型：{type}搜索: {kw}", sessionID, finger)

        return success({
            "topic": data,
            "unames": unames,
            "board": config['board']
        })
    

    elif type == "tag" or type == "developer":
        if "," in kw:
            tags = kw.split(",")
        elif "|" in kw:
            tags = kw.split("|")
        else:
            tags = [f"{kw}"]
        tags_json = await tags_to_ID(pool, tags)
        tags = []
        for k,v in tags_json.items():
            tags.append(v)

        # 构建sql
        length = len(tags)
        placeholders = ",".join(["%s"] * length)
        query = tuple(tags)

        # 构建和查询，这里的DISTINCT是去重用的
        if "," in kw:
            sql = f"SELECT tid FROM search_tags WHERE tagID IN ({placeholders}) GROUP BY tid HAVING COUNT(DISTINCT tagID) = {length}"

        # 构建或查询
        elif "|" in kw:
            sql = f"SELECT DISTINCT tid FROM search_tags WHERE tagID IN ({placeholders})"

        # 单个TAG
        else:
            sql = f"SELECT DISTINCT tid FROM search_tags WHERE tagID={placeholders}"

        # 查询每个tid
        rows = await db_rows(pool, sql, query)
        if not rows:
            return fail("未查询到！")

        # 每一个帖子
        tags_encode = set()
        for row in rows:
            tid = int(row['tid'])
            fid = await get_fid(pool, tid)
            sharding = str(tid)[-1]
            table = f"topics_{fid}_{sharding}"
            row = await db_fetchone(
                pool,
                "SELECT title, uid, date, tags FROM `{}` WHERE tid=%s".format(table),
                (tid, )
            )

            uid = row['uid']
            uids.add(uid)

            # 将tags拆分然后添加进元组里去重
            for tag in row['tags'].split("|"):
                tags_encode.add(tag)

            data.append({
                "contain": "tag",
                "tid": tid,
                "title": row['title'],
                "uid": uid,
                "date": row['date'],
                "tags": row['tags'],
                "fid": fid
            })

        # 查每一个uid的uname
        unames = {}
        for uid in uids:
            uname = await get_uname(pool, uid)
            unames[uid] = uname

        # 查每一个tagID对应的中文
        tags_decode = await tags_to_str(pool, tags_encode)

        # 日志记录
        await log_add(pool, rds, f"类型：{type}搜索: {kw}", sessionID, finger)

        return success({
            "topic": data,
            "unames": unames,
            "board": config['board'],
            "tags_decode": tags_decode
        })
    else:
        return fail("搜索方式不合法！")