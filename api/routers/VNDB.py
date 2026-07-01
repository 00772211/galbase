from fastapi import HTTPException, Request, Depends, APIRouter, Path, Query, Body
from fastapi.responses import HTMLResponse
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



router = APIRouter(prefix="/vndb", tags=["VNDB"])



@router.get("/developer", summary="获取一个制作组的所有作品", description="返回的是一个表格。该管理员仅管理员可调用。")
async def API_get_vndb_developer_releases(
    url: str = Query(..., embed=True, description="制作组Releases页面，如：https://vndb.org/p2734"),
    config = Depends(get_config),
    uid = Depends(get_uid_by_headers)
):
    if uid not in config['administrators']:
        return fail("该管理员仅管理员可调用")

    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
    }
    html = requests.get(url, headers=headers).text

    # 解析HTML
    soup = BeautifulSoup(html, "html.parser")

    # 找到 releases 表格
    table = soup.find("table", class_="releases")
    results = []

    # 获取所有 tr
    rows = table.find_all("tr")
    current_title = None

    for row in rows:
        # 如果是作品标题行
        if "vn" in row.get("class", []):
            a = row.find("a")
            if a:
                current_title = a.get("title")
            continue
        if not current_title:
            continue

        # 获取版本名称
        tc4 = row.find("td", class_="tc4")
        if not tc4:
            continue
        release_name = tc4.get_text(strip=True)

        # 跳过试玩版
        if "Trial Edition" in release_name:
            continue

        # 获取发行日期
        tc1 = row.find("td", class_="tc1")
        if not tc1:
            continue
        release_date = tc1.get_text(strip=True)
        results.append({
            "title": current_title,
            "date": release_date
        })

        # 当前作品只取第一个正式版
        current_title = None

    html_output = '''
<table border="1" cellpadding="5px" cellspacing="0px" width="100%">
    <thead>
        <tr>
            <th>作品序号</th>
            <th>日文标题</th>
            <th>民间译名</th>
            <th>初代发售时间</th>
        </tr>
    </thead>

    <tbody>
    '''

    i = 1
    for item in results:
        html_output += f'''
        <tr>
            <td>{i}<br>{{_goto}}</td>
            <td>{item["title"]}</td>
            <td>XXXXXXXXXXXXXX</td>
            <td>{item["date"]}</td>
        </tr>
    '''
        i += 1


    html_output += '''
    </tbody>
</table>
    '''
    return HTMLResponse(content=html_output)



@router.get("/gal", summary="获取VNDB入库本站所需的信息", description="返回的是一个JSON")
async def API_get_vndb_developer_releases(
    url: str = Query(..., embed=True, description="游戏页面URL，如：https://vndb.org/v1"),
    config = Depends(get_config),
    uid = Depends(get_uid_by_headers)
):
    # 请求页面
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
    }
    html = requests.get(url, headers=headers).text
    soup = BeautifulSoup(html, "html.parser")

    # 游戏标题（日文）
    title = ""
    titles_td = soup.select_one("td.titles")
    if titles_td:
        ja_span = titles_td.select_one('span[lang="ja"]')
        if ja_span:
            title = ja_span.get_text(strip=True)

    # 开发商
    developer = ""
    for tr in soup.select(".vndetails tr"):
        tds = tr.find_all("td")
        if len(tds) >= 2 and tds[0].get_text(strip=True) == "Developer":
            developer = tds[1].get_text(" ", strip=True)
            break

    # 游戏流程
    play_time = ""
    for tr in soup.select(".vndetails tr"):
        tds = tr.find_all("td")
        if len(tds) >= 2 and tds[0].get_text(strip=True) == "Play time":
            td = tds[1]
            text = td.get_text(" ", strip=True)
            text = re.sub(r"\s+from.*$", "", text)
            text = text.replace("h ", "h")
            play_time = text
            break

    # 发售日
    release_date = None
    release_table = soup.select_one(".vnreleases table.releases")
    if release_table:
        for tr in release_table.select("tr"):
            date_td = tr.select_one(".tc1")
            title_td = tr.select_one(".tc4")
            if not date_td or not title_td:
                continue
            release_name = title_td.get_text(" ", strip=True)
            if "Trial Edition" in release_name or "(patch)" in release_name:
                continue
            date_text = date_td.get_text(strip=True)
            if re.match(r"\d{4}-\d{2}-\d{2}", date_text):
                release_date = date_text
                break

    # Chinese (simplified) 标题
    cn_title = None

    for details in soup.select(".vnreleases details"):
        summary = details.find("summary")
        if not summary:
            continue
        if "Chinese (simplified)" not in summary.get_text():
            continue
        first_release = details.select_one("table.releases .tc4 a")
        if first_release:
            cn_title = first_release.get("title")
            break

    # 输出JSON
    result = {
        "title": title,
        "cn_title": cn_title,
        "developer": developer,
        "play_time": f"{play_time})",
        "date": release_date
    }

    return success(result)



@router.get("/download", summary="下载VNDB的帖子页面", description="成功会返回一个地址")
async def API_get_vndb_developer_releases(
    url: str = Query(..., embed=True, description="游戏页面URL，如：https://vndb.org/v1"),
    config = Depends(get_config),
    uid = Depends(get_uid_by_headers)
):
    # 请求页面
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
    }
    html = requests.get(url, headers=headers).text
    html_md5 = await md5(html)
    soup = BeautifulSoup(html, "html.parser")


    with open("page.html", "w", encoding="utf-8") as f:
        f.write(html)

    return html_md5