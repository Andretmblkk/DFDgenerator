<?php

declare(strict_types=1);

namespace LaravelDfd\Renderer;

use LaravelDfd\IR\DFDLevel;

final class HtmlRenderer
{
    public function __construct(private SvgRenderer $svgRenderer = new SvgRenderer())
    {
    }

    /**
     * @param array{system: string, selectedLevel: int, levels: array<int, DFDLevel>, groups?: array<int, mixed>} $hierarchy
     */
    public function render(array $hierarchy): string
    {
        $levels = $hierarchy['levels'];
        $payload = [
            'system' => $hierarchy['system'],
            'selectedLevel' => $hierarchy['selectedLevel'],
            'meta' => $hierarchy['meta'] ?? [],
            'levels' => array_map(static fn (DFDLevel $level): array => $level->toArray(), $levels),
        ];
        $svgs = [];

        foreach ($levels as $level) {
            $svgs[$level->getId()] = $this->svgRenderer->render($level);
        }

        return '<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>' . $this->escape($hierarchy['system']) . ' DFD</title>
<link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<div class="app">
<aside class="side">
<button class="mobile-toggle" type="button" id="toggleSidebar">Menu</button>
<h1 class="brand">Laravel DFD Generator</h1>
<p class="creator">Created by Andre Tumbelaka</p>
<div class="project-card">
<strong>' . $this->escape($hierarchy['system']) . '</strong>
<span id="generatedAt"></span>
</div>
<input class="search" id="search" type="search" placeholder="Cari diagram atau proses">
<nav class="nav" id="levelNav"></nav>
<div class="tree" id="tree"></div>
<div class="stats" id="stats"></div>
</aside>
<main class="main">
<header class="top">
<div><div class="breadcrumb" id="breadcrumb"></div><div class="title" id="title"></div><div class="hint">Drag, touch, or middle mouse to pan. Scroll untuk zoom halus.</div></div>
<div class="tool"><button type="button" id="fit">Fit</button><button type="button" id="zoomOut" title="Zoom out">-</button><button type="button" id="zoomIn" title="Zoom in">+</button><button type="button" id="reset">Reset</button><button type="button" id="fullscreen">Fullscreen</button><button type="button" id="exportSvg">SVG</button><button type="button" id="exportPng">PNG</button><button type="button" id="exportJson">JSON</button></div>
</header>
<section class="canvas" id="canvas"><div class="stage" id="stage"></div><div class="minimap" id="minimap"></div></section>
<footer class="footer">Special thanks to Andre Tumbelaka for dedication and development of this project.</footer>
</main>
</div>
<script>window.DFD_DATA=' . $this->json($payload) . ';window.DFD_SVGS=' . $this->json($svgs) . ';</script>
<script src="assets/viewer.js"></script>
</body>
</html>
';
    }

    public function styles(): string
    {
        return ':root{color-scheme:dark;--bg:#090d16;--panel:rgba(18,25,39,.78);--panel-solid:#121927;--text:#eef4ff;--muted:#9aa8bf;--line:rgba(151,164,190,.2);--accent:#39d98a;--accent2:#6ea8ff;--active:rgba(57,217,138,.12);--shadow:0 24px 80px rgba(0,0,0,.28)}
*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at top left,rgba(57,217,138,.16),transparent 34%),linear-gradient(135deg,#080c14,#101827 55%,#0b1020);color:var(--text);font:14px/1.45 Inter,Segoe UI,Arial,sans-serif}.app{display:grid;grid-template-columns:330px 1fr;min-height:100vh}.side{backdrop-filter:blur(18px);border-right:1px solid var(--line);background:var(--panel);padding:18px;overflow:auto;box-shadow:var(--shadow);z-index:2}.mobile-toggle{display:none}.brand{font-size:18px;font-weight:800;margin:0 0 3px;letter-spacing:.2px}.creator{margin:0 0 18px;color:var(--muted);font-size:12px}.project-card{display:grid;gap:5px;margin-bottom:14px;padding:14px;border:1px solid var(--line);border-radius:16px;background:linear-gradient(180deg,rgba(255,255,255,.08),rgba(255,255,255,.03));box-shadow:0 12px 40px rgba(0,0,0,.18)}.project-card span{color:var(--muted);font-size:12px}.search{width:100%;border:1px solid var(--line);border-radius:12px;background:rgba(255,255,255,.06);color:var(--text);padding:11px 12px;margin-bottom:14px;outline:none}.search:focus{border-color:var(--accent2);box-shadow:0 0 0 4px rgba(110,168,255,.12)}.nav{display:grid;gap:8px}.nav button,.tool button{border:1px solid var(--line);background:rgba(255,255,255,.06);color:var(--text);border-radius:12px;padding:9px 11px;text-align:left;cursor:pointer;transition:.18s ease}.nav button:hover,.tool button:hover{transform:translateY(-1px);border-color:rgba(110,168,255,.55)}.nav button.active{background:var(--active);border-color:var(--accent);box-shadow:0 0 0 4px rgba(57,217,138,.08)}.tree{margin-top:18px;border-top:1px solid var(--line);padding-top:14px}.tree strong{display:block;margin-bottom:8px}.tree details{margin:8px 0;border:1px solid var(--line);border-radius:12px;padding:8px;background:rgba(255,255,255,.035)}.tree summary{cursor:pointer;font-weight:700}.tree a{display:block;color:var(--muted);padding:6px 0 4px 14px;text-decoration:none;border-radius:8px}.tree a:hover,.tree a.active{color:var(--accent);background:rgba(57,217,138,.08)}.stats{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:16px}.stat{border:1px solid var(--line);border-radius:12px;padding:10px;background:rgba(255,255,255,.04)}.stat b{display:block;font-size:18px}.stat span{color:var(--muted);font-size:11px}.main{display:grid;grid-template-rows:auto 1fr auto;min-width:0}.top{position:sticky;top:0;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;border-bottom:1px solid var(--line);background:rgba(10,15,25,.78);backdrop-filter:blur(16px);z-index:1}.breadcrumb{color:var(--muted);font-size:12px;margin-bottom:3px}.title{font-weight:800;font-size:18px}.tool{display:flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:flex-end}.tool button{text-align:center;padding:8px 10px}.canvas{overflow:hidden;position:relative;cursor:grab}.canvas.dragging{cursor:grabbing}.stage{transform-origin:0 0;position:absolute;left:0;top:0;padding:24px;transition:transform .08s linear}.stage svg{width:1180px;max-width:none;height:auto;display:block;filter:drop-shadow(0 24px 60px rgba(0,0,0,.22))}.hint{color:var(--muted);font-size:12px}.minimap{position:absolute;right:18px;bottom:18px;width:180px;height:110px;border:1px solid var(--line);border-radius:14px;background:rgba(8,12,20,.72);overflow:hidden;box-shadow:var(--shadow)}.minimap svg{width:100%;height:100%;opacity:.62}.footer{padding:10px 18px;color:var(--muted);border-top:1px solid var(--line);background:rgba(10,15,25,.62);font-size:12px}@media(max-width:900px){.app{grid-template-columns:1fr}.side{position:fixed;inset:0 auto 0 0;width:min(88vw,340px);transform:translateX(-105%);transition:.22s ease}.side.open{transform:translateX(0)}.mobile-toggle{display:block;margin-bottom:12px;border:1px solid var(--line);border-radius:10px;background:rgba(255,255,255,.08);color:var(--text);padding:8px 10px}.top{align-items:flex-start;flex-direction:column}.stage svg{width:980px}.minimap{display:none}}
';
    }

    public function script(): string
    {
        return 'const data=window.DFD_DATA||{levels:[],meta:{}};
const svgs=window.DFD_SVGS||{};
const nav=document.getElementById("levelNav");
const tree=document.getElementById("tree");
const title=document.getElementById("title");
const canvas=document.getElementById("canvas");
const stage=document.getElementById("stage");
const minimap=document.getElementById("minimap");
const breadcrumb=document.getElementById("breadcrumb");
const search=document.getElementById("search");
let current=(data.levels.find(level=>level.level===0)||data.levels[0]||{}).id||"level-0";
let scale=1,pan={x:0,y:0},drag=null;
document.getElementById("generatedAt").textContent=data.meta.generatedAt?`Generated ${new Date(data.meta.generatedAt).toLocaleString()}`:"Generated now";
document.getElementById("stats").innerHTML=["routes","processes","dataStores","externalEntities"].map(key=>`<div class=\"stat\"><b>${data.meta[key]||0}</b><span>${key}</span></div>`).join("");
function renderNav(){const query=(search.value||"").toLowerCase();nav.innerHTML="";data.levels.filter(level=>!query||level.title.toLowerCase().includes(query)||level.processes.some(process=>process.label.toLowerCase().includes(query))).forEach(level=>{const button=document.createElement("button");button.type="button";button.textContent=`Level ${level.level} - ${level.title.replace(/^Level \\d+\\s*/,"")}`;button.className=level.id===current?"active":"";button.onclick=()=>show(level.id);nav.appendChild(button);});}
function renderTree(){tree.innerHTML="<strong>Hierarchy</strong>";data.levels.filter(level=>level.level===1).forEach(level=>{level.processes.forEach(process=>{const details=document.createElement("details");details.open=true;const summary=document.createElement("summary");summary.textContent=process.label;summary.onclick=()=>show(level.id);details.appendChild(summary);data.levels.filter(child=>child.parentProcessId===process.id).forEach(child=>{const a=document.createElement("a");a.href="#";a.textContent=child.title;a.dataset.target=child.id;a.onclick=event=>{event.preventDefault();show(child.id);};details.appendChild(a);});tree.appendChild(details);});});}
function activateTree(id){tree.querySelectorAll("a").forEach(link=>link.classList.toggle("active",link.dataset.target===id));}
function show(id){current=id;const level=data.levels.find(item=>item.id===id)||data.levels[0];if(!level)return;title.textContent=level.title;breadcrumb.textContent=`${data.system} / Level ${level.level}`;stage.innerHTML=svgs[level.id]||"";minimap.innerHTML=svgs[level.id]||"";stage.querySelectorAll(".dfd-process").forEach(node=>{node.style.cursor="pointer";node.addEventListener("click",()=>openChild(node.dataset.id));});renderNav();activateTree(level.id);applyTransform();}
function openChild(processId){const child=data.levels.find(level=>level.parentProcessId===processId);if(child)show(child.id);}
function applyTransform(){stage.style.transform=`translate(${pan.x}px,${pan.y}px) scale(${scale})`;}
function zoomAt(delta,cx=canvas.clientWidth/2,cy=canvas.clientHeight/2){const old=scale;scale=Math.min(2.8,Math.max(.25,scale+delta));pan.x=cx-(cx-pan.x)*(scale/old);pan.y=cy-(cy-pan.y)*(scale/old);applyTransform();}
function fit(){const svg=stage.querySelector("svg");if(!svg)return;const box=svg.viewBox.baseVal;scale=Math.min((canvas.clientWidth-60)/box.width,(canvas.clientHeight-60)/box.height);pan={x:30,y:30};applyTransform();}
document.getElementById("zoomIn").onclick=()=>zoomAt(.15);
document.getElementById("zoomOut").onclick=()=>zoomAt(-.15);
document.getElementById("fit").onclick=fit;
document.getElementById("reset").onclick=()=>{scale=1;pan={x:0,y:0};applyTransform();};
document.getElementById("fullscreen").onclick=()=>document.documentElement.requestFullscreen&&document.documentElement.requestFullscreen();
document.getElementById("exportSvg").onclick=()=>download(`${current}.svg`,svgs[current]||"","image/svg+xml");
document.getElementById("exportJson").onclick=()=>download(`${current}.json`,JSON.stringify(data.levels.find(level=>level.id===current)||{},null,2),"application/json");
document.getElementById("exportPng").onclick=()=>{const svg=stage.querySelector("svg");if(!svg)return;const img=new Image();const blob=new Blob([svg.outerHTML],{type:"image/svg+xml"});const url=URL.createObjectURL(blob);img.onload=()=>{const c=document.createElement("canvas");c.width=img.width||1180;c.height=img.height||720;const ctx=c.getContext("2d");ctx.drawImage(img,0,0);URL.revokeObjectURL(url);c.toBlob(png=>downloadBlob(`${current}.png`,png));};img.src=url;};
function download(name,content,type){downloadBlob(name,new Blob([content],{type}));}
function downloadBlob(name,blob){if(!blob)return;const a=document.createElement("a");a.href=URL.createObjectURL(blob);a.download=name;a.click();setTimeout(()=>URL.revokeObjectURL(a.href),1200);}
canvas.addEventListener("wheel",event=>{event.preventDefault();zoomAt(event.deltaY<0?.12:-.12,event.clientX,event.clientY);},{passive:false});
canvas.addEventListener("mousedown",event=>{if(event.button!==0&&event.button!==1)return;drag={x:event.clientX-pan.x,y:event.clientY-pan.y};canvas.classList.add("dragging");});
canvas.addEventListener("touchstart",event=>{const t=event.touches[0];drag={x:t.clientX-pan.x,y:t.clientY-pan.y};canvas.classList.add("dragging");},{passive:true});
canvas.addEventListener("touchmove",event=>{if(!drag)return;const t=event.touches[0];pan={x:t.clientX-drag.x,y:t.clientY-drag.y};applyTransform();},{passive:true});
window.addEventListener("mousemove",event=>{if(!drag)return;pan={x:event.clientX-drag.x,y:event.clientY-drag.y};applyTransform();});
window.addEventListener("mouseup",()=>{drag=null;canvas.classList.remove("dragging");});
window.addEventListener("touchend",()=>{drag=null;canvas.classList.remove("dragging");});
document.getElementById("toggleSidebar").onclick=()=>document.querySelector(".side").classList.toggle("open");
search.addEventListener("input",renderNav);
renderTree();show(current);
';
    }

    /**
     * @param mixed $payload
     */
    private function json(mixed $payload): string
    {
        return (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
