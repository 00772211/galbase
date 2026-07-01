// 
// CSS
// 
const style = document.createElement("style");
style.textContent = `
    .crystal-bg {
        position: fixed !important;
        inset: 0;
        pointer-events: none;
    }
    .shard {
        position: absolute;
        opacity: 0;
        animation: drift 5s linear infinite;
        pointer-events: none;
        z-index: 999;
    }
    @keyframes drift {
        0%   { transform: translateY(-20px) rotate(0deg) scale(0.9); opacity: 0; }
        30%  { opacity: 0.6; }
        60%  { opacity: 0.55; }
        90%  { opacity: 0.4; }
        100% { transform: translateY(110vh) rotate(180deg) scale(1.1); opacity: 0; }
    }
`
document.head.appendChild(style)



// 
// DOM
// 
HTML = `
    <div class="crystal-bg" id="crystalBg"></div>
`
document.body.insertAdjacentHTML('beforeend', HTML)



// 
// 粒子
// 
const Crystal_shards = document.getElementById("crystalBg");
const shardColors = ["#a8d8f8","#c0e4ff","#7ec8f0","#daf0ff","#90c8e8","#b8dcf8"];
for(let i = 0; i < 18; i++) {
    const s = document.createElement("div");
    s.className = "shard";
    const size = 6 + Math.random() * 10;
    const color = shardColors[i % shardColors.length];
    const isHex = Math.random() > 0.5;
    if(isHex) {
        s.innerHTML = `<svg width="${size*2}" height="${size*2.2}" viewBox="0 0 20 24" fill="none"><polygon points="10,1 19,6 19,18 10,23 1,18 1,6" fill="${color}" opacity="0.9"/></svg>`;
    } else {
        s.innerHTML = `<svg width="${size*1.4}" height="${size*2}" viewBox="0 0 14 22" fill="none"><polygon points="7,0 14,8 10,22 4,22 0,8" fill="${color}" opacity="0.85"/></svg>`;
    }
    s.style.left = (Math.random() * 100) + "%";
    s.style.animationDuration = (14 + Math.random() * 16) + "s";
    s.style.animationDelay = (Math.random() * 10) + "s";
    Crystal_shards.appendChild(s);
}