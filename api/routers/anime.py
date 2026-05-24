from fastapi import HTTPException, Request, Depends, APIRouter, Path, Query
from dependencies import *
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
import hashlib


router = APIRouter(prefix="/anime", tags=["动画"])



@router.get("/{tid}/{ep}", summary="获取指定tid的指定ep URL", description="请不要滥用这个接口！")
async def API_anime_ep_get(
    tid: int = Path(..., description="帖子ID"),
    ep: int = Path(..., description="集数"),
    config = Depends(get_config),
    sessionID = Depends(get_sessionID),
    finger = Depends(get_finger)
):
    text = f"{tid}|{ep}"
    md5 = hashlib.md5(text.encode("utf-8")).hexdigest()

    # 获取chunk
    sharding = await chunk(config, tid, "anime")
    url = f"{sharding}/{tid}/{ep}_{md5}.mp4"

    # 日志记录
    date = await get_date("all")
    if not sessionID:
        await log_add_source(f"{date} {finger}观看{tid}第{ep}集")
    else:
        uid = await get_uid_by_sessionID(sessionID, rds, pool)
        await log_add_source(f"{date} {uid}观看{tid}第{ep}集")

    return success(url)
