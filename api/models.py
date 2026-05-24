from pydantic import BaseModel, Field
from typing import Optional, List
from enum import Enum

class uname_model(BaseModel):
    uname: str = Field(..., description="用户名")

class admin(BaseModel):
    psw: str

class sessionID_model(BaseModel):
    sessionID: str = Field(..., description="sessionID")

class sessionID_unrequested_model(BaseModel):
    sessionID: Optional[str] = None

class login_model(BaseModel):
    uname: str
    psw: str

class remove_reply(BaseModel):
    sessionID: str = Field(..., description="sessionID")
    tid: int = Field(..., description="帖子ID")
    rid: int = Field(..., description="回复区ID（楼层）")

class urls_model(BaseModel):
    baidu: str = Field(None, description="百度网盘", example="")
    OD: str = Field(None, description="OneDrive网盘", example="")
    direct: str = Field(None, description="直链", example="")
    else_url: str = Field(None, description="其他链接", example="")

class email_model(BaseModel):
    email: str = Field(..., description="邮箱")

class send_topic_model(BaseModel):
    title: str  = Field(..., description="帖子标题")
    content: str  = Field(..., description="帖子内容")
    tags: str  = Field(..., description="TAG")
    cover: str  = Field(..., description="封面预览图ID（aid）")
    fid: str  = Field(..., description="板块ID")
    
class replace_topic_model(BaseModel):
    title: str  = Field(..., description="帖子标题")
    content: str  = Field(..., description="帖子内容")
    tags: str  = Field(..., description="TAG")
    cover: str  = Field(..., description="封面预览图ID（aid）")
    tid: int  = Field(..., description="帖子ID")
    
class tids_model(BaseModel):
    tids: List[int] = Field(..., description="帖子tid列表"),

class search_type_model(str, Enum):
    normal = "normal"
    title = "title"
    tag = "tag"
    developer = "developer"

class fid_enum(str, Enum):
    fid1_1 = "1-1"
    fid1_2 = "1-2"
    fid1_3 = "1-3"
    fid1_4 = "1-4"
    fid2_1 = "2-1"
    fid2_2 = "2-2"
    fid2_3 = "2-3"
    fid2_4 = "2-4"
    fid3_1 = "3-1"
    fid3_2 = "3-2"

class sort_type(str, Enum):
    tid = "tid"
    score = "score"

class filter_type(str, Enum):
    normal = "pass"
    no_H = "no_H"
    only_H = "only_H"
