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

router = APIRouter(prefix="/gal", tags=["Galgame"])

@router.get("/{tid}", summary="XXX", description="请不要滥用这个接口！")
async def API_download(
    tid: int = Path(..., description="帖子ID"),
    finger: Optional[str] = Query(None, description="指纹，可传可不传"),
    pool = Depends(get_db),
    rds = Depends(get_redis),
    config = Depends(get_config)
):
    return 1
