from fastapi import Depends, HTTPException, Header, Request
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
import asyncmy

rds = None
pool = None
CONFIG = None
security = HTTPBearer(auto_error=False)



async def get_redis():
    return rds



async def get_db():
    return pool



async def get_config():
    return CONFIG



async def get_sessionID(
    credentials: HTTPAuthorizationCredentials = Depends(security)
):
    # 游客
    if credentials is None:
        return None

    # 登录用户
    return credentials.credentials



async def get_sessionID_login(
    credentials: HTTPAuthorizationCredentials = Depends(security)
):
    # 游客
    if credentials is None:
        raise HTTPException(
            status_code=401,
            detail="调用该接口必须登录！"
        )

    # 登录用户
    return credentials.credentials



async def get_uid_by_headers (
    credentials: HTTPAuthorizationCredentials = Depends(security)
):
    # 游客
    if credentials is None:
        raise HTTPException(
            status_code=401,
            detail="调用该接口必须登录！"
        )

    # 登录用户
    sessionID = credentials.credentials

    # 尝试从 Redis 缓存获取用户信息
    uid = await rds.get(f"uid:{sessionID}")
    if uid:
        return int(uid)

    # 从数据库里查uid
    async with pool.acquire() as conn:
        async with conn.cursor(asyncmy.cursors.DictCursor) as cur:
            await cur.execute("SELECT uid FROM users_sessions WHERE sessionID=%s", (sessionID,))
            row = await cur.fetchone()

    if not row:
        raise HTTPException(status_code=401, detail="登录过期！sessionID未找到对应的UID")
    uid = row["uid"]

    # 缓存进redsi
    await rds.set(f"uid:{sessionID}", uid, ex=1800)
    
    return uid



async def get_finger(
    request: Request,
    finger: str | None = Header(None, description="finger可传可不传")
):
    if finger:
        return finger

    # Cloudflare / nginx 真实IP
    ip = request.headers.get("CF-Connecting-IP")

    if not ip:
        ip = request.headers.get("X-Forwarded-For")

    if not ip:
        ip = request.client.host

    return ip