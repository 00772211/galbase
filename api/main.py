
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
from routers import admin, user, topic, forum, gal, developer, anime, search, VNDB
import os
import sys
import time
import dependencies


last_request = int(time.time())

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

    # 清空当前 Redis
    await rds.flushdb()
    print("Redis缓存已清空")

    pool = await asyncmy.create_pool(
        host='localhost',
        port=3306,
        user='root',
        password='ss123321',
        db='galbase',
        minsize=2,
        maxsize=5, 
        pool_recycle=3600,
        autocommit=True,
    )
    print("数据库连接池已启动")

    # **把初始化好的 pool 和 rds 绑定到 dependencies**
    dependencies.pool = pool
    dependencies.rds = rds

    # 全局加载 JSON 配置
    CONFIG_PATH = PATH(__file__).parent / "config.json"
    with open(CONFIG_PATH, "r", encoding="utf-8") as f:
        dependencies.CONFIG = json.load(f)

    # 本地化
    if dependencies.CONFIG["localhost"] == True:
        dependencies.CONFIG['chunk'] = dependencies.CONFIG['chunk_localhost']

    # linux环境
    if sys.platform.startswith('linux'):
        dependencies.CONFIG['path'] = dependencies.CONFIG['linux_path']
        dependencies.CONFIG['mysqldump'] = dependencies.CONFIG['linux_mysqldump']

    # 启动后台空闲任务检测
    task = asyncio.create_task(idle_checker())

    yield
    
    # 关闭时取消任务
    task.cancel()

    pool.close()
    await pool.wait_closed()
    print("数据库连接池已关闭")



app = FastAPI(
    lifespan=lifespan,
    swagger_ui_parameters={"persistAuthorization": True}
)


app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=False,  # 是否允许携带 cookie
    allow_methods=["*"],     # 允许哪些 HTTP 方法
    allow_headers=["*"],     # 允许哪些请求头
)

# 中间件，更新最后请求时间
@app.middleware("http")
async def update_last_request(request: Request, call_next):
    global last_request
    response = await call_next(request)

    # 只有成功请求才更新
    if (200 <= response.status_code < 400):
        last_request = time.time()
        print("请求更新")

    return response



# 引用分模块
app.include_router(user.router)
app.include_router(admin.router)
app.include_router(forum.router)
app.include_router(topic.router)
app.include_router(developer.router)
app.include_router(gal.router)
app.include_router(anime.router)
app.include_router(search.router)
app.include_router(VNDB.router)


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
    # x_user_id: Optional[str] = Header(None),
 ):
    return success({"msg": "Hello, Welcome!"})


@app.get("/test")
async def root2(
    pool = Depends(get_db), 
    config = Depends(get_config), 
    rds = Depends(get_redis),
    uid = Depends(get_uid_by_headers)
 ):
    if uid not in config['administrators']:
        return fail("您没权限调用该接口！")
    await get_touchgal_topic()

    return success({"msg": "Hello, Welcome!"})









if __name__ == "__main__":
    scheduler = BackgroundScheduler()
    scheduler.add_job(my_task, 'cron', hour=0, minute=1)
    scheduler.start()

    import uvicorn
    # uvicorn.run("main:app", host="127.0.0.1", port=8005)
    uvicorn.run("main:app", host="127.0.0.1", port=8005, reload=True)


