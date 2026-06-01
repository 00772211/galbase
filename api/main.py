
import asyncio
from fastapi import FastAPI, BackgroundTasks,Path, Depends, HTTPException, Request
from fastapi.responses import JSONResponse
from fastapi.middleware.cors import CORSMiddleware
from fastapi.security import HTTPBearer
import asyncmy
from asyncmy.pool import Pool
from typing import Optional, List, Dict, Any
from contextlib import asynccontextmanager
from pydantic import BaseModel, Field
import redis.asyncio as redis
import redis.asyncio as aioredis
from starlette.exceptions import HTTPException as StarletteHTTPException
import json
from pathlib import Path as PATH
from functions import *
from dependencies import get_config
from apscheduler.schedulers.background import BackgroundScheduler
from datetime import datetime
import httpx
from routers import admin, user, topic, forum, gal, developer, anime, search
import os

# redis缓存
rds = aioredis.Redis(
    host="localhost",
    port=6379,
    decode_responses=True,

    # 关键
    health_check_interval=30,
    socket_keepalive=True,
    retry_on_timeout=True,
)


pool: Pool = None

@asynccontextmanager
async def lifespan(app: FastAPI):
    global pool
    pool = await asyncmy.create_pool(
        host='localhost',
        port=3306,
        user='root',
        password='',
        db='galbase',
        minsize=2,
        maxsize=5, 
        pool_recycle=3600,
        autocommit=True,
    )
    print("数据库连接池已启动")

    # **把初始化好的 pool 和 rds 绑定到 dependencies**
    import dependencies
    dependencies.pool = pool
    dependencies.rds = rds

    # 全局加载 JSON 配置
    CONFIG_PATH = PATH(__file__).parent / "config.json"
    with open(CONFIG_PATH, "r", encoding="utf-8") as f:
        dependencies.CONFIG = json.load(f)

    # 本地化
    if dependencies.CONFIG["localhost"] == True:
        dependencies.CONFIG['chunk'] = dependencies.CONFIG['chunk_localhost']

    yield

    pool.close()
    await pool.wait_closed()
    print("数据库连接池已关闭")



app = FastAPI(lifespan=lifespan)


app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,  # 是否允许携带 cookie
    allow_methods=["*"],     # 允许哪些 HTTP 方法
    allow_headers=["*"],     # 允许哪些请求头
)

# 引用分模块
app.include_router(user.router)
app.include_router(admin.router)
app.include_router(forum.router)
app.include_router(topic.router)
app.include_router(developer.router)
app.include_router(gal.router)
app.include_router(anime.router)
app.include_router(search.router)


# 全局异常处理器
@app.exception_handler(StarletteHTTPException)
async def http_exception_handler(request: Request, exc: StarletteHTTPException):
    return JSONResponse(
        status_code=exc.status_code,
        content={"error": exc.detail, "data": None}
    )

# 定时任务
def my_task():
    async def _run():
        async with httpx.AsyncClient() as client:
            await client.post("http://127.0.0.1:8005/admin/new_day", json={"psw": "aaadmin123321"})
    asyncio.run(_run())

# 测试用
@app.get("/")
async def root(
    pool = Depends(get_db), 
    config = Depends(get_config), 
    rds = Depends(get_redis)
 ):
    # 更新1：需要手动上传表search_tags

    # 更新2
    # 940 941 942 945 939 这些tid的tag有问题，index没有索引，建议从帖子topic-1-1-里删除

    # # 更新3：
    # fids = []
    # for k,v in config['board'].items():
    #     fids.append(k)
    # for fid in fids:
    #     for i in range(10):
    #         table = f"topics_{fid}_{i}"
    #         rows = await db_rows(pool, "SELECT tid, tags FROM `{}`".format(table))
    #         for row in rows:
    #             tid = row['tid']
    #             tags = row['tags'].split("|")
    #             if tags != ['']:
    #                 for tag in tags:
    #                     await db_insert(pool, "INSERT INTO search_tags (tid, tagID) VALUES (%s, %s)", (tid, tag))
    # await update_ym(pool)

    return success({"msg": "Hello, Welcome!"})



if __name__ == "__main__":
    scheduler = BackgroundScheduler()
    scheduler.add_job(my_task, 'cron', hour=0, minute=0)
    scheduler.start()

    import uvicorn
    # uvicorn.run("main:app", host="127.0.0.1", port=8005)
    uvicorn.run("main:app", host="127.0.0.1", port=8005, reload=True)


