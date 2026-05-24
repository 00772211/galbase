from fastapi import HTTPException, Request, Depends, APIRouter, Path, Query
from dependencies import get_db, get_redis, get_config
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


router = APIRouter(prefix="/developer", tags=["会社"])


@router.get("/{developer}/works", summary="获取指定会社的所有作品", description="返回的是一个前端TABLE标签")
async def API_developer(
    developer: str = Path(..., description="会社名字，支持中文英文及空格，最好标准些。（我也不知道分不分大小写，我亲测是不分，建议分。）"),
    pool = Depends(get_db)
):
    
    # 查tagID
    row = await db_fetchone(pool, "SELECT id FROM tags_index WHERE tag=%s LIMIT 1", (developer, ))
    if not row:
        return fail("会社ID未入库，未查询到。")
    tagID = row['id']

    # 根据会社tagID在1-2板块找帖子
    for i in range(10):
        row = await db_fetchone(pool, "SELECT content, tags FROM `{}` WHERE tags LIKE %s".format(f"topics_1-2_{i}"), (f"%{tagID}%",))
        if row:

            # 精准匹配tag
            if str(tagID) in row['tags'].split("|"):

                # 匹配table标签
                match = re.search(r'<table\b[^>]*>[\s\S]*?</table>', row['content'], re.M | re.S)
                if match:
                    result = match.group(0)
                    return success(result)
                else:
                    return fail("会社ID已入库，且已发帖，但是未匹配到table标签。")
    return fail("会社ID已入库，但是未查询到合集帖子。")

