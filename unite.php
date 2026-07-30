<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ターン制ユナイトバトル</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --cs:32px;
  --txt:#e9eefc; --sub:#9aa8c8; --line:#2c3860;
  --ally:#4a94ff; --enemy:#ff5f5f; --wild:#e6c979; --shoot:#5cffa8;
}
html,body{height:100%}
body{
  background:radial-gradient(1300px 760px at 50% -12%,#1e2c58 0%,#0b1020 62%,#060912 100%);
  color:var(--txt);
  font-family:'Hiragino Kaku Gothic ProN','Yu Gothic','Meiryo',system-ui,sans-serif;
  padding:12px;-webkit-text-size-adjust:100%;
}
button,select{font-family:inherit;color:inherit;cursor:pointer;border:none;background:none}
button:disabled{cursor:not-allowed}
svg{display:block;overflow:visible}

.wrap{max-width:1320px;margin:0 auto}
h1{font-size:1.35rem;letter-spacing:.04em;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
h1 .tag{font-size:.62rem;background:linear-gradient(90deg,var(--ally),var(--enemy));padding:3px 8px;border-radius:99px;letter-spacing:.1em}
.sub{color:var(--sub);font-size:.78rem;margin-top:3px}

/* ---------- scoreboard ---------- */
.board{display:flex;align-items:stretch;gap:10px;margin:12px 0 8px;
  background:linear-gradient(180deg,#182144,#111834);border:1px solid var(--line);border-radius:12px;padding:10px 12px}
.side{flex:1;display:flex;flex-direction:column;gap:4px;min-width:0}
.side .nm{font-size:.7rem;letter-spacing:.12em;color:var(--sub)}
.side .sc{font-size:1.9rem;font-weight:800;line-height:1}
.side.a .sc{color:var(--ally)} .side.e{text-align:right} .side.e .sc{color:var(--enemy)}
.goalrow{display:flex;gap:3px;flex-wrap:wrap}
.side.e .goalrow{justify-content:flex-end}
.gpip{font-size:.6rem;padding:1px 5px;border-radius:4px;border:1px solid var(--line);background:#0e1428;color:var(--sub);white-space:nowrap}
.gpip.open{border-color:#5c74b8;color:#cfe0ff}
.gpip.dead{opacity:.35;text-decoration:line-through}
.tmid{text-align:center;display:flex;flex-direction:column;justify-content:center;min-width:140px}
.tmid .t{font-size:.68rem;color:var(--sub);letter-spacing:.1em}
.tmid .v{font-size:1.2rem;font-weight:700}
.tmid .v small{font-size:.7rem;color:var(--sub);font-weight:400}
.tmid select{font-size:.66rem;background:#0e1630;border:1px solid var(--line);border-radius:6px;padding:2px 4px;margin-top:5px}

/* ---------- turn order ---------- */
.order{display:flex;align-items:center;gap:5px;margin-bottom:8px;background:linear-gradient(180deg,#141c3c,#0f1630);
  border:1px solid var(--line);border-radius:10px;padding:6px 8px;overflow-x:auto}
.order .lbl{font-size:.62rem;color:var(--sub);letter-spacing:.1em;flex:none;padding-right:2px}
.oi{flex:none;position:relative;width:34px;height:34px;border-radius:10px;border:2px solid;background:#0d142c;
  display:flex;align-items:center;justify-content:center;transition:.15s}
.oi.a{border-color:var(--ally)} .oi.e{border-color:var(--enemy)}
.oi svg{width:26px;height:26px}
.oi .no{position:absolute;top:-6px;left:-4px;font-size:.55rem;background:#0d142c;border:1px solid var(--line);
  border-radius:99px;padding:0 3px;color:var(--sub)}
.oi.now{transform:scale(1.16);box-shadow:0 0 0 2px #ffe14d,0 0 12px rgba(255,225,77,.55);z-index:2}
.oi.me{background:#2a2410}
.oi.dead{opacity:.3}
.oi.done{opacity:.55}

/* ---------- layout ---------- */
.main{display:flex;gap:12px;align-items:flex-start}
.left{flex:1;min-width:0;max-width:100%}
.right{width:330px;flex:none;display:flex;flex-direction:column;gap:10px}
@media(max-width:1120px){.main{flex-direction:column}.right{width:100%}}

/* ---------- map ---------- */
#mapWrap{background:linear-gradient(180deg,#0f1630,#0a1022);border:1px solid var(--line);border-radius:12px;
  padding:6px;overflow:auto}
#mapStage{position:relative;width:max-content;margin:0 auto}
#map{display:grid;grid-template-columns:repeat(29,var(--cs))}
#fx{position:absolute;inset:0;pointer-events:none;z-index:20}
.cell{width:var(--cs);height:var(--cs);position:relative;background:#18234f;
  outline:1px solid rgba(255,255,255,.03);outline-offset:-1px}
.cell.wall{background:#070b18;outline-color:transparent}
.cell.wall::after{content:'';position:absolute;inset:10%;border-radius:4px;
  background:linear-gradient(160deg,#16203c,#0d1428)}
.cell.bush{background:#1e3a29}
.cell.bush::after{content:'';position:absolute;inset:0;opacity:.5;
  background:
    radial-gradient(circle at 26% 32%,#4b8a58 0 21%,transparent 22%),
    radial-gradient(circle at 70% 42%,#3d7449 0 19%,transparent 20%),
    radial-gradient(circle at 44% 74%,#448050 0 20%,transparent 21%)}
.cell.lane{background:#1b2652}
.cell.zoneA{background:#0f3a55}
.cell.zoneE{background:#3a1e2b}
.cell.baseA{background:#1d4a86}
.cell.baseE{background:#6b2323}
.cell.gaA{background:#1f5aa8}
.cell.gaE{background:#8f3030}
.cell.gaA.dead,.cell.gaE.dead{background:#2a2f45}
/* ゴール加速ライン：必ず地形色より後に置く（background-image を上書きさせるため） */
.cell.accel{background-image:linear-gradient(180deg,
  transparent 0 25%,rgba(140,225,255,.32) 25% 30%,transparent 30% 70%,
  rgba(140,225,255,.32) 70% 75%,transparent 75% 100%)}
.cell.accel::before{content:'';position:absolute;left:0;right:0;top:calc(50% - 1px);height:2px;
  background:repeating-linear-gradient(90deg,rgba(170,240,255,.5) 0 4px,transparent 4px 9px);z-index:1}

/* goal 3x3 frame */
.gbox{position:absolute;left:-100%;top:-100%;width:300%;height:300%;border:2px solid;border-radius:9px;
  display:flex;align-items:center;justify-content:center;z-index:1;pointer-events:none}
.gbox.a{border-color:#6fb0ff;box-shadow:inset 0 0 14px rgba(70,140,255,.35)}
.gbox.e{border-color:#ff9090;box-shadow:inset 0 0 14px rgba(255,90,90,.3)}
.gbox.closed{border-style:dashed;opacity:.5}
.gbox.dead{border-style:dotted;opacity:.28}
.gbox b{font-size:calc(var(--cs)*.42);font-weight:800;text-shadow:0 2px 4px #000c;opacity:.85}

/* ---------- units ---------- */
.u{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;z-index:3}
.u .ring{position:absolute;inset:2%;border-radius:12px;border:2px solid}
.u.a .ring{border-color:var(--ally);background:rgba(74,148,255,.2)}
.u.e .ring{border-color:var(--enemy);background:rgba(255,95,95,.2)}
.u.w .ring{border-color:var(--wild);background:rgba(230,201,121,.18)}
.u.me .ring{border-color:#ffe14d;box-shadow:0 0 0 2px rgba(255,225,77,.3),0 0 10px rgba(255,225,77,.45)}
.u.now .ring{animation:pulse .7s ease-in-out infinite}
@keyframes pulse{0%,100%{box-shadow:0 0 0 1px #ffe14d,0 0 6px rgba(255,225,77,.5)}
                 50%{box-shadow:0 0 0 3px #ffe14d,0 0 16px rgba(255,225,77,.9)}}
.u svg{position:relative;z-index:2;width:88%;height:88%;filter:drop-shadow(0 1px 2px rgba(0,0,0,.55))}

/* 攻撃モーション：攻撃側は対象方向へ突き、被弾側は震える */
.u.lunge{animation:lunge .34s ease-out}
@keyframes lunge{0%{transform:none}
  28%{transform:translate(calc(var(--ax)*38%),calc(var(--ay)*38%))}
  55%{transform:translate(calc(var(--ax)*-12%),calc(var(--ay)*-12%))}
  100%{transform:none}}
.u.shake{animation:shake .32s ease-in-out}
@keyframes shake{0%,100%{transform:none}
  18%{transform:translate(-16%,0) rotate(-8deg)}
  42%{transform:translate(14%,0) rotate(7deg)}
  68%{transform:translate(-8%,0) rotate(-3deg)}}

/* HPゲージ＝足元の横バー */
.hp{position:absolute;left:6%;right:6%;bottom:0;height:4px;border-radius:2px;
  background:rgba(0,0,0,.72);box-shadow:0 0 0 1px rgba(0,0,0,.6);overflow:hidden;z-index:4}
.hp i{display:block;height:100%;background:#4ce07a}
.hp.s2 i{background:#ffd24c}.hp.s1 i{background:#ff5d5d}
.sh{position:absolute;left:6%;right:6%;bottom:5px;height:2px;background:#7fd8ff;border-radius:2px;z-index:4}

/* シュートゲージ＝アイコンを囲む緑のリング＋ラベル（HPバーと形が完全に別） */
.chgring{position:absolute;inset:-9%;border-radius:50%;z-index:6;pointer-events:none;
  background:conic-gradient(from -90deg,var(--shoot) calc(var(--p)*1%),rgba(4,22,13,.6) 0);
  -webkit-mask:radial-gradient(circle,transparent 57%,#000 60%);
          mask:radial-gradient(circle,transparent 57%,#000 60%);
  animation:chgglow 1.1s ease-in-out infinite}
@keyframes chgglow{0%,100%{filter:drop-shadow(0 0 2px rgba(92,255,168,.5))}
                   50%{filter:drop-shadow(0 0 8px rgba(92,255,168,.95))}}
.chgtag{position:absolute;left:50%;bottom:calc(var(--cs)*-0.32);transform:translateX(-50%);z-index:7;
  background:#08281a;border:1px solid var(--shoot);color:#a9ffcd;border-radius:99px;
  font-size:calc(var(--cs)*.27);font-weight:800;padding:0 4px;line-height:1.35;white-space:nowrap;
  box-shadow:0 1px 4px #000a}

.pts{position:absolute;top:-3px;right:-3px;background:linear-gradient(180deg,#ffe17a,#f4b93c);color:#4a3200;
  font-size:calc(var(--cs)*.3);font-weight:800;border-radius:99px;padding:0 3px;min-width:calc(var(--cs)*.38);
  text-align:center;z-index:5;box-shadow:0 1px 3px #000a}
/* 野生ポケモンを倒したときに拾える点数（枠だけ金色＝所持点バッジと区別） */
.wpt{position:absolute;bottom:-5px;right:-4px;z-index:5;background:#2a2410;border:1px solid var(--wild);
  color:#ffd97a;border-radius:99px;font-size:calc(var(--cs)*.27);font-weight:800;padding:0 3px;line-height:1.3;
  box-shadow:0 1px 3px #000a}
.badge{position:absolute;top:-4px;left:-3px;font-size:calc(var(--cs)*.3);z-index:5;line-height:1;
  background:#0d142c;border-radius:99px;padding:1px 3px;border:1px solid var(--line)}
.downmk{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
  font-size:calc(var(--cs)*.42);color:#8792b5;z-index:2}

/* highlights */
.cell.hlMove{box-shadow:inset 0 0 0 2px #4ce07a;cursor:pointer}
.cell.hlMoveFast{box-shadow:inset 0 0 0 2px #7df0ff,inset 0 0 11px rgba(125,240,255,.6);cursor:pointer}
.cell.hlAtk{box-shadow:inset 0 0 0 2px #ff8a4c;cursor:pointer}
.cell.hlHeal{box-shadow:inset 0 0 0 2px #7fd8ff;cursor:pointer}
.cell.hlArea{box-shadow:inset 0 0 0 2px #ffe14d;cursor:pointer}
.cell.aoe::after{content:'';position:absolute;inset:0;background:rgba(255,180,60,.42);z-index:2}
.cell.hit::after{content:'';position:absolute;inset:0;background:rgba(255,70,70,.55);z-index:2;animation:fade .5s forwards}
@keyframes fade{to{opacity:0}}

/* ---------- fx layer ---------- */
.fxtok{position:absolute;left:0;top:0;z-index:24;display:flex;align-items:center;justify-content:center;
  will-change:transform}
.fxtok svg{width:88%;height:88%;filter:drop-shadow(0 2px 6px rgba(0,0,0,.75))}
.fxtok .ring{position:absolute;inset:2%;border-radius:12px;border:2px solid}
.fxtok.a .ring{border-color:var(--ally);background:rgba(74,148,255,.2)}
.fxtok.e .ring{border-color:var(--enemy);background:rgba(255,95,95,.2)}
.fxtok.w .ring{border-color:var(--wild);background:rgba(230,201,121,.18)}
.fxtok.me .ring{border-color:#ffe14d;box-shadow:0 0 12px rgba(255,225,77,.6)}
.fxtrail{position:absolute;z-index:22;border-radius:50%;pointer-events:none;
  animation:trailfade .6s ease-out forwards}
@keyframes trailfade{0%{opacity:.8;transform:scale(1)}100%{opacity:0;transform:scale(.35)}}
.fxbeam{position:absolute;z-index:23;height:3px;border-radius:2px;transform-origin:0 50%;
  animation:beam .36s ease-out forwards}
@keyframes beam{0%{opacity:0}18%{opacity:1}100%{opacity:0}}
.fxtext{position:absolute;z-index:30;font-weight:800;pointer-events:none;white-space:nowrap;
  text-shadow:0 2px 4px #000,0 0 4px #000;animation:rise 1s ease-out forwards}
@keyframes rise{0%{opacity:0;transform:translate(-50%,6px) scale(.75)}
  16%{opacity:1;transform:translate(-50%,0) scale(1.15)}
  100%{opacity:0;transform:translate(-50%,-26px) scale(1)}}
.fxtext.dmg{color:#ff9a9a}.fxtext.pt{color:#ffd24c}.fxtext.heal{color:#8dffb8}
.fxtext.ko{color:#ff6b6b}.fxtext.sc{color:#8dffb8}

/* ---------- panels ---------- */
.card{background:linear-gradient(180deg,#182144,#111834);border:1px solid var(--line);border-radius:12px;padding:10px 11px}
.card h2{font-size:.72rem;letter-spacing:.12em;color:var(--sub);margin-bottom:7px;font-weight:600}
.mecard{display:flex;gap:9px;align-items:center}
.mecard .av{width:52px;height:52px;flex:none;border-radius:13px;background:#0e1630;border:2px solid #ffe14d;
  display:flex;align-items:center;justify-content:center;padding:3px}
.mecard .av svg{width:100%;height:100%}
.mecard .info{flex:1;min-width:0}
.mecard .nm{font-weight:700;font-size:.94rem}
.mecard .st{font-size:.66rem;color:var(--sub);display:flex;gap:8px;flex-wrap:wrap;margin-top:2px}
.gauge{margin-top:7px}
.gauge .lb{display:flex;justify-content:space-between;font-size:.63rem;color:var(--sub);margin-bottom:2px}
.bigbar{height:10px;background:#000a;border-radius:5px;overflow:hidden;box-shadow:0 0 0 1px #0008 inset}
.bigbar i{display:block;height:100%;background:linear-gradient(90deg,#3ce06f,#8ef0a8)}
.bigbar.s2 i{background:linear-gradient(90deg,#ffc93c,#ffe08a)}
.bigbar.s1 i{background:linear-gradient(90deg,#ff4d4d,#ff9a9a)}
/* シュートゲージ：セグメント式（HPの連続バーと形を分ける） */
.shootbox{margin-top:7px;border:1px solid #245c42;background:#0a1f17;border-radius:9px;padding:6px 8px}
.shootbox .hd{display:flex;justify-content:space-between;font-size:.65rem;color:#8dffc0;font-weight:700;gap:6px}
.shootbox.off{opacity:.45}
.segs{display:flex;gap:3px;margin-top:4px}
.segs span{flex:1;height:11px;border-radius:3px;background:#0b1a14;border:1px solid #245c42}
.segs span.on{background:linear-gradient(180deg,#8dffc0,#3ddc8c);border-color:#8dffc0;
  box-shadow:0 0 7px rgba(92,255,168,.65)}

.acts{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:8px}
.act{border:1px solid var(--line);background:#0e1630;border-radius:9px;padding:7px 8px;text-align:left;transition:.12s}
.act:hover:not(:disabled){border-color:#5c74b8;background:#152046}
.act.sel{border-color:#ffe14d;background:#2e2a12;box-shadow:0 0 0 1px #ffe14d inset}
.act:disabled{opacity:.38}
.act .t{font-size:.8rem;font-weight:700;display:flex;justify-content:space-between;align-items:center;gap:4px}
.act .t b{font-size:.6rem;color:#ffd24c;font-weight:600;text-align:right}
.act .d{font-size:.62rem;color:var(--sub);margin-top:2px;line-height:1.35}
.act.wide{grid-column:1/-1}
.act.goal{border-color:#3ddc8c;background:#0f2c1e}
.act.goal:hover:not(:disabled){background:#164329}
.hintbar{margin-top:7px;font-size:.7rem;color:#ffe14d;min-height:1.1em;line-height:1.4}

.rlist{display:flex;flex-direction:column;gap:3px}
.rrow{display:flex;align-items:center;gap:6px;font-size:.7rem}
.rrow .e{width:24px;height:24px;flex:none;display:flex;align-items:center;justify-content:center}
.rrow .e svg{width:22px;height:22px}
.rrow .n{width:76px;flex:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rrow .b{flex:1;height:6px;background:#000a;border-radius:3px;overflow:hidden}
.rrow .b i{display:block;height:100%}
.rrow.a .b i{background:var(--ally)} .rrow.e .b i{background:var(--enemy)}
.rrow .p{width:28px;text-align:right;flex:none;color:#ffd24c;font-weight:700}
.rrow .s{width:36px;text-align:right;flex:none;color:var(--sub);font-size:.62rem}
.rrow.dead{opacity:.4}
.rrow.me .n{color:#ffe14d;font-weight:700}

#log{height:186px;overflow-y:auto;font-size:.7rem;line-height:1.6;padding-right:4px}
#log .th{color:#ffe14d;font-weight:700;border-top:1px solid var(--line);margin-top:5px;padding-top:4px}
#log .th:first-child{border:none;margin-top:0}
#log .a{color:#a8ccff}#log .e{color:#ffb3b3}#log .w{color:#ddc890}
#log .sc{color:#7dffb0;font-weight:700}#log .ko{color:#ff8a8a;font-weight:700}
#log::-webkit-scrollbar{width:6px}#log::-webkit-scrollbar-thumb{background:#2c3860;border-radius:3px}

details.rules{margin-top:10px;background:#101838;border:1px solid var(--line);border-radius:10px;padding:8px 11px}
details.rules summary{cursor:pointer;font-size:.76rem;font-weight:700;color:#cfe0ff}
details.rules div{font-size:.72rem;color:var(--sub);line-height:1.8;margin-top:7px}
details.rules b{color:#e9eefc}

/* ---------- overlays ---------- */
.ov{position:fixed;inset:0;background:rgba(5,8,18,.92);backdrop-filter:blur(4px);z-index:100;
  display:flex;align-items:flex-start;justify-content:center;padding:16px;overflow-y:auto}
.ov.hide{display:none}
/* margin:auto で「収まるときは中央・はみ出すときは上寄せ+スクロール可」にする */
.ovbox{max-width:1000px;width:100%;margin:auto}
.ovbox h2{font-size:1.5rem;text-align:center;margin-bottom:4px}
.ovbox p.lead{text-align:center;color:var(--sub);font-size:.8rem;margin-bottom:14px}
.picks{display:grid;grid-template-columns:repeat(auto-fill,minmax(184px,1fr));gap:9px}
.pick{border:1px solid var(--line);background:linear-gradient(180deg,#1a2450,#111834);border-radius:12px;
  padding:10px;text-align:left;transition:.13s;display:flex;flex-direction:column}
.pick:hover{border-color:#7f9bff;transform:translateY(-3px);box-shadow:0 8px 22px rgba(0,0,0,.45)}
.pick .hd{display:flex;align-items:center;gap:8px}
.pick .hd .av{width:52px;height:52px;flex:none;border-radius:13px;background:#0c1228;border:1px solid var(--line);padding:3px}
.pick .hd .av svg{width:100%;height:100%}
.pick .hd .nm{font-weight:800;font-size:.88rem;line-height:1.25}
.pick .hd .ro{font-size:.6rem;color:#ffd24c}
.pick .stats{display:grid;grid-template-columns:1fr 1fr;gap:1px 8px;font-size:.63rem;color:var(--sub);margin:7px 0 5px}
.pick .mv{margin-top:auto;font-size:.62rem;color:#b9c8ea;line-height:1.5;border-top:1px dashed var(--line);padding-top:5px}
.pick .mv em{color:#ffe14d;font-style:normal;font-weight:700}
.result{background:linear-gradient(180deg,#182144,#0e1430);border:1px solid var(--line);border-radius:16px;
  padding:26px 22px;text-align:center;max-width:430px;margin:0 auto}
.result .big{font-size:2.1rem;font-weight:900;letter-spacing:.06em}
.result .big.win{color:#4ce07a}.result .big.lose{color:#ff6b6b}.result .big.draw{color:#ffd24c}
.result .sc2{font-size:1.5rem;margin:10px 0;font-weight:700}
.btn{display:inline-block;margin-top:14px;padding:10px 26px;border-radius:99px;font-weight:700;font-size:.86rem;
  background:linear-gradient(90deg,#4a94ff,#7f5aff);box-shadow:0 4px 16px rgba(74,148,255,.35)}
.btn:hover{filter:brightness(1.12)}
</style>
</head>
<body>
<div class="wrap">
  <h1>ターン制ユナイトバトル <span class="tag">5 vs 5 / TURN BASED</span></h1>
  <div class="sub">行動順に1匹ずつ行動する5対5のチーム戦。相手ゴールに多く得点したチームの勝ち。</div>

  <div class="board">
    <div class="side a">
      <div class="nm">MY TEAM (青)</div><div class="sc" id="scA">0</div>
      <div class="goalrow" id="gA"></div>
    </div>
    <div class="tmid">
      <div class="t">TURN</div>
      <div class="v"><span id="turnNo">1</span><small> / <span id="turnMax">70</span></small></div>
      <div class="t" style="margin-top:5px" id="phaseTxt">行動を選択</div>
      <select id="spdSel" title="自動行動の表示速度">
        <option value="520">速度: ゆっくり</option>
        <option value="280" selected>速度: ふつう</option>
        <option value="150">速度: はやい</option>
        <option value="30">速度: 最速(演出なし)</option>
      </select>
    </div>
    <div class="side e">
      <div class="nm">(赤) ENEMY TEAM</div><div class="sc" id="scE">0</div>
      <div class="goalrow" id="gE"></div>
    </div>
  </div>

  <div class="order" id="orderStrip"></div>

  <div class="main">
    <div class="left">
      <div id="mapWrap"><div id="mapStage"><div id="map"></div><div id="fx"></div></div></div>
      <details class="rules">
        <summary>ルール / 操作説明</summary>
        <div>
          <b>■ 距離の数え方</b>：移動は<b>上下左右のみ1マス</b>。斜めへ行くには2マスかかります（＝マンハッタン距離）。<b>こうげき・わざの射程、範囲わざの半径も同じ数え方</b>です。<br>
          <b>■ すり抜け</b>：移動の<b>途中は他のポケモン（味方・敵・野生）を通り抜けられます</b>。ただし<b>止まれるのは空いているマスだけ</b>です。囲まれても動けなくなりません。<br>
          <b>■ ゴール加速ライン</b>：<b>ゴールとゴールを結ぶ直線</b>（マップの水色の破線）の上では<b>移動1で2マス</b>進めます。素早さ3なら加速ライン上を6マス。移動先ハイライトのうち<b>水色に光っているマスが加速ラインを使った到達先</b>です。<br>
          <b>■ 行動順</b>：<b>あなた → 敵1 → 味方2 → 敵2 → …</b> の固定順で、<b>1匹ずつ順番に決定・実行</b>します。あなた以外の9匹は自動で順に動きます（速度は上のセレクトで変更可）。<b>野生ポケモンは移動しません</b>。全員の行動後にまとめて反撃します。<br>
          <b>■ 行動</b>：毎ターン「移動 / こうげき / わざ1 / わざ2 / ゴール / 待機」から<b>1つだけ</b>選べます。<br>
          <b>■ わざ</b>：使うとクールタイム（CT）が発生し、その間は再使用できません。<br>
          <b>■ 得点の入手</b>：野生ポケモンを倒す／相手ポケモンを倒す（相手が持っていた点＋1をもらう）。<br>
          　・<b>アイコン右下の金枠「◆N」＝ 倒したときに拾える点数</b>（野生ポケモンのみ表示）。<br>
          　・<b>アイコン右上の金色の丸い数字＝ そのポケモンが今持っている点数</b>。<br>
          <b>■ ゴール</b>：ゴールは<b>3×3マスのエリア</b>。相手の有効ゴールのエリア内に入り、<b>「ゴール」アクション</b>を選ぶとシュートを開始します。<br>
          　・所持点数が多いほど<b>完了までのターン数が増えます</b>（1〜3点=1ターン / 4〜7点=2 / 8〜11点=3 / 12〜15点=4 / 16点以上=5）。<br>
          　・シュート中は<b>アイコンを囲む緑のリングゲージ</b>で進行を表示します（HPは足元の横バー）。<br>
          　・<b>シュート中にダメージを受けるとキャンセル</b>され、最初からやり直しです。エリアから出た場合・別の行動をした場合もキャンセルされます。<br>
          <b>■ ゴールの回復</b>：<b>自陣の生きているゴールエリア内にいるとターン終了時にHPが回復</b>（最大HPの10%）。自陣ベースではさらに回復します（20%）。<br>
          <b>■ ゴールの順番</b>：各チーム5個（上レーン2・下レーン2・中央1）。レーンは<b>外側→内側</b>の順にしか壊せません。<b>中央ゴールは相手ゴールを2つ以上壊すと開放</b>されます。<br>
          <b>■ 気絶</b>：HPが0になるとスタート地点に戻され、3ターン行動できません（持っていた点は倒した相手へ）。<br>
          <b>■ 勝敗</b>：制限ターン終了時に得点が多いチームの勝ち。相手ゴールを5個すべて壊すと即勝利。<br>
          <b>■ 中央のカジリガメは高得点。52ターン目にサンダーが中央に出現します。</b>
        </div>
      </details>
    </div>

    <div class="right">
      <div class="card">
        <h2>YOUR POKÉMON</h2>
        <div class="mecard">
          <div class="av" id="meAv"></div>
          <div class="info"><div class="nm" id="meNm">-</div><div class="st" id="meSt"></div></div>
        </div>
        <div class="gauge">
          <div class="lb"><span>❤️ HP</span><span id="meHp">-</span></div>
          <div class="bigbar" id="meBar"><i style="width:100%"></i></div>
        </div>
        <div class="shootbox off" id="shootBox">
          <div class="hd"><span>⚡ シュートゲージ</span><span id="shootTxt">-</span></div>
          <div class="segs" id="shootSegs"></div>
        </div>
        <div class="acts" id="acts"></div>
        <div class="hintbar" id="hint"></div>
      </div>
      <div class="card">
        <h2>TEAMS</h2>
        <div class="rlist" id="rosterA"></div>
        <div style="height:7px"></div>
        <div class="rlist" id="rosterE"></div>
      </div>
      <div class="card">
        <h2>BATTLE LOG</h2>
        <div id="log"></div>
      </div>
    </div>
  </div>
</div>

<div class="ov" id="ovSelect">
  <div class="ovbox">
    <h2>使うポケモンを選ぼう</h2>
    <p class="lead">残りの9匹が、味方4匹・敵5匹にランダムで振り分けられます。</p>
    <div class="picks" id="picks"></div>
  </div>
</div>

<div class="ov hide" id="ovResult">
  <div class="ovbox"><div class="result">
    <div class="big" id="rBig">-</div>
    <div class="sc2" id="rSc">-</div>
    <div style="font-size:.76rem;color:var(--sub)" id="rNote"></div>
    <button class="btn" onclick="location.reload()">もう一度あそぶ</button>
  </div></div>
</div>

<script>
'use strict';

/* =========================================================
   SPRITES  (inline SVG / 64x64)
   ========================================================= */
const SVG = s => '<svg viewBox="0 0 64 64" aria-hidden="true">' + s + '</svg>';
const SPR = {
 pika: SVG(`
  <g stroke="#4a3a10" stroke-width="3" stroke-linejoin="round">
   <path d="M18 25 L8 5 L28 19Z" fill="#f7d02c"/><path d="M46 25 L56 5 L36 19Z" fill="#f7d02c"/>
   <circle cx="32" cy="38" r="19" fill="#f7d02c"/></g>
  <path d="M8 5 L15 17 L21 11Z" fill="#3b3226"/><path d="M56 5 L49 17 L43 11Z" fill="#3b3226"/>
  <circle cx="25" cy="35" r="3.3" fill="#2b2418"/><circle cx="39" cy="35" r="3.3" fill="#2b2418"/>
  <circle cx="18" cy="45" r="4.3" fill="#e8564a"/><circle cx="46" cy="45" r="4.3" fill="#e8564a"/>
  <path d="M28 45q4 4 8 0" fill="none" stroke="#2b2418" stroke-width="2.6" stroke-linecap="round"/>`),
 ninetales: SVG(`
  <g stroke="#5b6a86" stroke-width="3" stroke-linejoin="round">
   <path d="M15 27 L10 6 L27 20Z" fill="#e6f0fb"/><path d="M49 27 L54 6 L37 20Z" fill="#e6f0fb"/>
   <path d="M32 15 C19 15 12 27 14 40 C16 52 25 58 32 58 C39 58 48 52 50 40 C52 27 45 15 32 15Z" fill="#f7fbff"/></g>
  <path d="M25 22 L32 7 L39 22Z" fill="#d7e7f7" stroke="#5b6a86" stroke-width="2.4" stroke-linejoin="round"/>
  <circle cx="25" cy="36" r="3.2" fill="#3b6ea5"/><circle cx="39" cy="36" r="3.2" fill="#3b6ea5"/>
  <path d="M32 42 l-3.5 4.5 h7Z" fill="#8fa8c4"/>
  <path d="M26 51 q6 5 12 0" fill="none" stroke="#8fa8c4" stroke-width="2.4" stroke-linecap="round"/>`),
 chariz: SVG(`
  <g stroke="#7a3410" stroke-width="3" stroke-linejoin="round">
   <path d="M1 15 L21 33 L4 49Z" fill="#3f8ad8"/><path d="M63 15 L43 33 L60 49Z" fill="#3f8ad8"/>
   <path d="M20 18 L13 4 L28 13Z" fill="#f8a862"/><path d="M44 18 L51 4 L36 13Z" fill="#f8a862"/>
   <path d="M32 13 C18 13 12 27 14 39 C16 51 25 57 32 57 C39 57 48 51 50 39 C52 27 46 13 32 13Z" fill="#ef8030"/></g>
  <circle cx="25" cy="33" r="3.2" fill="#2b1a0c"/><circle cx="39" cy="33" r="3.2" fill="#2b1a0c"/>
  <path d="M21 44 Q32 53 43 44 Q32 49 21 44Z" fill="#f9d86c" stroke="#7a3410" stroke-width="2.2" stroke-linejoin="round"/>`),
 lucario: SVG(`
  <g stroke="#1b2748" stroke-width="3" stroke-linejoin="round">
   <path d="M17 25 L11 4 L28 19Z" fill="#2f5fa8"/><path d="M47 25 L53 4 L36 19Z" fill="#2f5fa8"/>
   <path d="M32 13 C19 13 13 27 15 39 C17 51 25 57 32 57 C39 57 47 51 49 39 C51 27 45 13 32 13Z" fill="#3d7ad1"/>
   <path d="M22 39 Q32 34 42 39 Q42 52 32 56 Q22 52 22 39Z" fill="#242c3c"/></g>
  <circle cx="24" cy="31" r="3" fill="#e2494f"/><circle cx="40" cy="31" r="3" fill="#e2494f"/>
  <circle cx="32" cy="44" r="2.6" fill="#0d1220"/>
  <circle cx="32" cy="23" r="3.2" fill="#f2c14e" stroke="#1b2748" stroke-width="2"/>`),
 snorlax: SVG(`
  <g stroke="#182831" stroke-width="3" stroke-linejoin="round">
   <path d="M13 21 L6 9 L19 14Z" fill="#3b5c6b"/><path d="M51 21 L58 9 L45 14Z" fill="#3b5c6b"/>
   <ellipse cx="32" cy="37" rx="24" ry="21" fill="#3b5c6b"/>
   <path d="M9 39 Q32 58 55 39 Q53 58 32 58 Q11 58 9 39Z" fill="#efe2c0"/></g>
  <path d="M20 33 q5 4.5 10 0" fill="none" stroke="#16222a" stroke-width="2.8" stroke-linecap="round"/>
  <path d="M34 33 q5 4.5 10 0" fill="none" stroke="#16222a" stroke-width="2.8" stroke-linecap="round"/>
  <path d="M27 43 q5 5 10 0" fill="none" stroke="#16222a" stroke-width="2.6" stroke-linecap="round"/>`),
 slowbro: SVG(`
  <g stroke="#8a4560" stroke-width="3" stroke-linejoin="round">
   <ellipse cx="47" cy="46" rx="15" ry="13" fill="#caa06c"/>
   <path d="M18 19 L12 7 L26 15Z" fill="#f4a6ba"/><path d="M46 19 L52 7 L38 15Z" fill="#f4a6ba"/>
   <path d="M32 11 C20 11 13 23 14 35 C15 48 24 55 32 55 C40 55 47 47 47 35 C47 22 43 11 32 11Z" fill="#f4a6ba"/></g>
  <circle cx="24" cy="29" r="2.9" fill="#3a2230"/><circle cx="39" cy="29" r="2.9" fill="#3a2230"/>
  <path d="M24 41 q7 5 14 -1" fill="none" stroke="#3a2230" stroke-width="2.6" stroke-linecap="round"/>
  <ellipse cx="47" cy="46" rx="7" ry="6" fill="#e0c090" stroke="#8a4560" stroke-width="2"/>`),
 gengar: SVG(`
  <path d="M8 18 l5-15 4 11 5-15 5 13 5-14 4 12 5-11 5 13" fill="#7b5aa6" stroke="#33204f"
        stroke-width="2.8" stroke-linejoin="round"/>
  <path d="M32 9 C17 9 8 21 8 35 C8 49 18 58 32 58 C46 58 56 49 56 35 C56 21 47 9 32 9Z"
        fill="#7b5aa6" stroke="#33204f" stroke-width="3"/>
  <circle cx="23" cy="31" r="5" fill="#fff" stroke="#33204f" stroke-width="2"/>
  <circle cx="41" cy="31" r="5" fill="#fff" stroke="#33204f" stroke-width="2"/>
  <circle cx="23.5" cy="32" r="2.1" fill="#2a1a3f"/><circle cx="41.5" cy="32" r="2.1" fill="#2a1a3f"/>
  <path d="M18 43 Q32 57 46 43 Q32 49 18 43Z" fill="#fff" stroke="#33204f" stroke-width="2.4" stroke-linejoin="round"/>`),
 absol: SVG(`
  <g stroke="#4a4f63" stroke-width="3" stroke-linejoin="round">
   <path d="M18 23 L12 9 L27 18Z" fill="#f7f8fb"/>
   <path d="M32 15 C21 15 15 27 16 39 C17 51 25 57 32 57 C39 57 47 51 48 39 C49 27 43 15 32 15Z" fill="#f7f8fb"/></g>
  <path d="M37 17 Q63 -2 63 21 Q49 13 41 31Z" fill="#2f3344" stroke="#1d2029" stroke-width="2.4" stroke-linejoin="round"/>
  <circle cx="25" cy="35" r="3" fill="#d94a5a"/><circle cx="39" cy="35" r="3" fill="#d94a5a"/>
  <path d="M27 46 q5 4 10 0" fill="none" stroke="#6b7080" stroke-width="2.4" stroke-linecap="round"/>`),
 wiggly: SVG(`
  <g stroke="#a85a76" stroke-width="3" stroke-linejoin="round">
   <path d="M13 27 L3 18 L15 17Z" fill="#f7b8ce"/><path d="M51 27 L61 18 L49 17Z" fill="#f7b8ce"/>
   <circle cx="32" cy="37" r="21" fill="#f7b8ce"/></g>
  <path d="M32 16 Q20 8 25 20 Q30 15 32 22" fill="none" stroke="#a85a76" stroke-width="3" stroke-linecap="round"/>
  <circle cx="24" cy="35" r="5.6" fill="#2c9fd6" stroke="#26384f" stroke-width="2"/>
  <circle cx="40" cy="35" r="5.6" fill="#2c9fd6" stroke="#26384f" stroke-width="2"/>
  <circle cx="25.6" cy="33.2" r="1.9" fill="#fff"/><circle cx="41.6" cy="33.2" r="1.9" fill="#fff"/>
  <path d="M28 47 q4 4 8 0" fill="none" stroke="#8c4a63" stroke-width="2.4" stroke-linecap="round"/>`),
 comfey: SVG(`
  <circle cx="32" cy="35" r="18" fill="none" stroke="#4e9c5a" stroke-width="7"/>
  <g stroke="#2f5f38" stroke-width="2">
   <circle cx="32" cy="15" r="7" fill="#f79ab8"/><circle cx="50" cy="29" r="6" fill="#f7d76c"/>
   <circle cx="44" cy="50" r="6" fill="#f79ab8"/><circle cx="20" cy="50" r="6" fill="#cf8ee0"/>
   <circle cx="14" cy="29" r="6" fill="#f7d76c"/></g>
  <circle cx="32" cy="35" r="8.5" fill="#fdf3d8" stroke="#2f5f38" stroke-width="2"/>
  <circle cx="29" cy="34" r="1.7" fill="#3a4a2c"/><circle cx="35" cy="34" r="1.7" fill="#3a4a2c"/>
  <path d="M30 38.5 q2 2.2 4 0" fill="none" stroke="#3a4a2c" stroke-width="1.8" stroke-linecap="round"/>`),
 /* --- wild --- */
 otachi: SVG(`
  <g stroke="#6b4a22" stroke-width="3" stroke-linejoin="round">
   <path d="M15 24 L11 9 L25 18Z" fill="#c99a55"/><path d="M49 24 L53 9 L39 18Z" fill="#c99a55"/>
   <ellipse cx="32" cy="38" rx="18" ry="18" fill="#d8ad66"/>
   <path d="M32 24 Q22 30 24 42 Q27 53 32 53 Q37 53 40 42 Q42 30 32 24Z" fill="#f0dcae"/></g>
  <circle cx="25" cy="34" r="2.8" fill="#3a2a12"/><circle cx="39" cy="34" r="2.8" fill="#3a2a12"/>
  <circle cx="32" cy="43" r="2.4" fill="#7a5526"/>`),
 ludi: SVG(`
  <g stroke="#2c5a3a" stroke-width="3" stroke-linejoin="round">
   <ellipse cx="32" cy="41" rx="18" ry="17" fill="#6cbf7a"/>
   <ellipse cx="32" cy="21" rx="25" ry="6.5" fill="#e0c25a"/>
   <path d="M21 21 Q32 6 43 21Z" fill="#e8cf72"/></g>
  <circle cx="25" cy="39" r="3" fill="#22331f"/><circle cx="39" cy="39" r="3" fill="#22331f"/>
  <path d="M26 48 q6 5 12 0" fill="none" stroke="#22331f" stroke-width="2.4" stroke-linecap="round"/>`),
 bouff: SVG(`
  <g stroke="#2a2118" stroke-width="3" stroke-linejoin="round">
   <circle cx="32" cy="21" r="15" fill="#c79a4e"/>
   <path d="M32 23 C20 23 14 33 16 43 C18 53 26 58 32 58 C38 58 46 53 48 43 C50 33 44 23 32 23Z" fill="#3a3126"/>
   <path d="M13 31 Q4 29 6 39 Q13 39 16 35Z" fill="#e8e0cc"/><path d="M51 31 Q60 29 58 39 Q51 39 48 35Z" fill="#e8e0cc"/></g>
  <circle cx="26" cy="39" r="2.8" fill="#e0d8c4"/><circle cx="38" cy="39" r="2.8" fill="#e0d8c4"/>
  <ellipse cx="32" cy="49" rx="7" ry="5" fill="#7a6a52" stroke="#2a2118" stroke-width="2"/>`),
 drednaw: SVG(`
  <g stroke="#3a2a1c" stroke-width="3" stroke-linejoin="round">
   <path d="M5 43 Q32 12 59 43Z" fill="#7a5a3a"/>
   <path d="M5 43 Q32 55 59 43 Q32 60 5 43Z" fill="#46684a"/>
   <path d="M32 45 Q21 45 21 53 Q21 60 32 60 Q43 60 43 53 Q43 45 32 45Z" fill="#5f8a5a"/></g>
  <circle cx="26" cy="51" r="2.6" fill="#1f2a1c"/><circle cx="38" cy="51" r="2.6" fill="#1f2a1c"/>
  <path d="M25 57 h14" stroke="#efe6d0" stroke-width="3" stroke-linecap="round"/>
  <path d="M15 39 l6-10 5 9 6-11 5 10 6-8" fill="none" stroke="#3a2a1c" stroke-width="2.3" stroke-linejoin="round"/>`),
 zapdos: SVG(`
  <g stroke="#7a5a10" stroke-width="3" stroke-linejoin="round">
   <path d="M3 26 L21 35 L5 43Z" fill="#f7d02c"/><path d="M61 26 L43 35 L59 43Z" fill="#f7d02c"/>
   <path d="M32 5 L22 23 h7 l-4 11 15-17 h-7Z" fill="#fdec8a"/>
   <ellipse cx="32" cy="37" rx="15" ry="15" fill="#f7d02c"/></g>
  <circle cx="26" cy="35" r="3" fill="#2b2418"/><circle cx="38" cy="35" r="3" fill="#2b2418"/>
  <path d="M32 41 l-6 5 6 4.5 6-4.5Z" fill="#e08a2c" stroke="#7a5a10" stroke-width="2" stroke-linejoin="round"/>`),
};

/* =========================================================
   MAP  — 左上1/4だけ定義し、上下左右にミラーして完全対称にする
   ========================================================= */
const QUAD = [
 "###############",
 "#..............",
 "#..............",
 "#......~....~..",
 "#..............",
 "#......###.###.",
 "#.......~...~..",
 "#.......##.....",
 "#..............",
];
const W = 29, H = 17;
const MAP = (()=>{
  const g=[];
  for(let r=0;r<H;r++){
    const qr = r<=8 ? r : (H-1-r);
    let s='';
    for(let c=0;c<W;c++) s += QUAD[qr][c<=14 ? c : (W-1-c)];
    g.push(s);
  }
  return g;
})();

const TURN_LIMIT  = 70;
const ZAPDOS_TURN = 52;
const DMG_K = 150;
const GOAL_HEAL = 0.10, BASE_HEAL = 0.20;

/* 移動コスト：通常マス=2 / 加速ライン=1、移動予算 = 素早さ×2
   → 加速ライン上は「移動1で2マス」進める */
const COST_NORMAL = 2, COST_FAST = 1;
const ACCEL_ROWS = [2,8,14], ACCEL_C0 = 4, ACCEL_C1 = W-1-4;

const BASE = { ally:{r:8,c:1}, enemy:{r:8,c:W-2} };
const BASE_ZONE = { ally:{r0:7,r1:9,c0:1,c1:2}, enemy:{r0:7,r1:9,c0:W-3,c1:W-2} };
/* 初期配置：レーンごとに間隔をあける（互いに隣接しない）。左右ミラー */
const START_A = [{r:8,c:1},{r:2,c:1},{r:5,c:1},{r:11,c:1},{r:14,c:1}];
const START = { ally:START_A, enemy:START_A.map(p=>({r:p.r,c:W-1-p.c})) };

/* =========================================================
   POKÉMON
   ========================================================= */
const POKEMON = [
 { id:'pika', name:'ピカチュウ', role:'アタック', hp:480, atk:62, def:30, spd:3, rng:3,
   moves:[{name:'でんきショック',kind:'single',power:60,range:5,cd:2,desc:'遠くの敵1体に電撃'},
          {name:'エレキボール',kind:'aoe',power:45,range:4,radius:2,cd:4,desc:'着弾点から半径2に雷'}]},
 { id:'ninetales', name:'アローラキュウコン', role:'アタック', hp:450, atk:66, def:28, spd:3, rng:4,
   moves:[{name:'れいとうビーム',kind:'single',power:75,range:6,cd:3,desc:'超射程の単体高火力'},
          {name:'ふぶき',kind:'aoe',power:40,range:4,radius:3,cd:5,desc:'広範囲(半径3)を凍らせる'}]},
 { id:'chariz', name:'リザードン', role:'バランス', hp:660, atk:58, def:46, spd:3, rng:1,
   moves:[{name:'ほのおのパンチ',kind:'dash',power:70,range:5,dash:4,cd:3,desc:'敵に踏み込んで殴る'},
          {name:'だいもんじ',kind:'aoe',power:55,range:3,radius:2,cd:5,desc:'自分の周りを焼き払う'}]},
 { id:'lucario', name:'ルカリオ', role:'バランス', hp:620, atk:62, def:44, spd:4, rng:1,
   moves:[{name:'しんくうは',kind:'single',power:65,range:4,cd:2,desc:'離れた敵に波動を飛ばす'},
          {name:'インファイト',kind:'dash',power:85,range:6,dash:5,cd:4,desc:'大きく踏み込んで連打'}]},
 { id:'snorlax', name:'カビゴン', role:'ディフェンス', hp:880, atk:44, def:62, spd:2, rng:1,
   moves:[{name:'ヘビーボンバー',kind:'dash',power:60,range:5,dash:4,cd:3,desc:'体当たりで突っ込む'},
          {name:'まもる',kind:'shield',shield:230,range:2,cd:5,desc:'自分と周囲の味方にシールド'}]},
 { id:'slowbro', name:'ヤドラン', role:'ディフェンス', hp:800, atk:46, def:58, spd:2, rng:3,
   moves:[{name:'なみのり',kind:'aoe',power:50,range:4,radius:2,cd:3,desc:'波で範囲攻撃'},
          {name:'テレキネシス',kind:'single',power:70,range:4,cd:5,stun:true,desc:'1体を拘束し次ターン行動不能'}]},
 { id:'gengar', name:'ゲンガー', role:'スピード', hp:430, atk:70, def:26, spd:5, rng:2,
   moves:[{name:'シャドーボール',kind:'single',power:80,range:3,cd:3,desc:'単体に大ダメージ'},
          {name:'たたりめ',kind:'aoe',power:45,range:2,radius:2,cd:4,desc:'近くの敵をまとめて叩く'}]},
 { id:'absol', name:'アブソル', role:'スピード', hp:450, atk:74, def:24, spd:5, rng:1,
   moves:[{name:'つじぎり',kind:'dash',power:95,range:6,dash:5,cd:3,desc:'一気に距離を詰めて斬る'},
          {name:'サイコカッター',kind:'single',power:70,range:2,cd:2,desc:'近距離の敵を斬撃'}]},
 { id:'wiggly', name:'プクリン', role:'サポート', hp:570, atk:48, def:40, spd:3, rng:3,
   moves:[{name:'うたう',kind:'single',power:20,range:4,cd:4,stun:true,desc:'眠らせて次ターン行動不能'},
          {name:'いやしのはどう',kind:'heal',heal:190,range:3,cd:3,desc:'味方1体(自分可)を回復'}]},
 { id:'comfey', name:'ワタシラガ', role:'サポート', hp:510, atk:44, def:36, spd:4, rng:4,
   moves:[{name:'はなびらのまい',kind:'aoe',power:40,range:4,radius:2,cd:3,desc:'花びらで範囲攻撃'},
          {name:'フラワーヒール',kind:'heal',heal:150,range:4,radius:2,cd:3,desc:'対象と周囲の味方を回復'}]},
];

const WILD_DEFS = {
  otachi:  {name:'オタチ',    hp:90,  atk:30, def:10, rng:1, pts:2,  resp:8 },
  ludi:    {name:'ルンパッパ', hp:150, atk:40, def:20, rng:1, pts:3,  resp:12},
  bouff:   {name:'バッフロン', hp:180, atk:45, def:25, rng:1, pts:4,  resp:12},
  drednaw: {name:'カジリガメ', hp:320, atk:60, def:35, rng:1, pts:8,  resp:20},
  zapdos:  {name:'サンダー',  hp:700, atk:80, def:40, rng:2, pts:25, resp:99},
};
const WILD_SPAWNS = [
  {t:'otachi',r:2,c:7},{t:'otachi',r:2,c:21},{t:'otachi',r:14,c:7},{t:'otachi',r:14,c:21},
  {t:'bouff', r:2,c:14},{t:'bouff', r:14,c:14},
  {t:'ludi',  r:7,c:7},{t:'ludi',  r:9,c:7},{t:'ludi',r:7,c:21},{t:'ludi',r:9,c:21},
  {t:'bouff', r:6,c:11},{t:'bouff',r:10,c:11},{t:'bouff',r:6,c:17},{t:'bouff',r:10,c:17},
  {t:'drednaw',r:6,c:14},{t:'drednaw',r:10,c:14},
  {t:'zapdos', r:8,c:14, spawnTurn:ZAPDOS_TURN},
];
const GOAL_DEFS = [
  {team:'ally', lane:'top',tier:1,r:2, c:10,cap:20},
  {team:'ally', lane:'top',tier:2,r:2, c:4, cap:28},
  {team:'ally', lane:'bot',tier:1,r:14,c:10,cap:20},
  {team:'ally', lane:'bot',tier:2,r:14,c:4, cap:28},
  {team:'ally', lane:'mid',tier:3,r:8, c:4, cap:36},
  {team:'enemy',lane:'top',tier:1,r:2, c:W-1-10,cap:20},
  {team:'enemy',lane:'top',tier:2,r:2, c:W-1-4, cap:28},
  {team:'enemy',lane:'bot',tier:1,r:14,c:W-1-10,cap:20},
  {team:'enemy',lane:'bot',tier:2,r:14,c:W-1-4, cap:28},
  {team:'enemy',lane:'mid',tier:3,r:8, c:W-1-4, cap:36},
];

/* =========================================================
   STATE
   ========================================================= */
let S = null;
let sel = null;
let running = false;
let curActor = null;
let SPEED = 280;
let AUTO_PASS = true;
let movingUid = null;      // 移動アニメ中は元マスのアイコンを隠す
let fxAttacker = null;     // {uid,ax,ay}
let fxShake = [];          // 被弾した uid
let hitCells = [];

const key=(r,c)=>r*W+c;
const inb=(r,c)=>r>=0&&r<H&&c>=0&&c<W;
const passable=(r,c)=>inb(r,c)&&MAP[r][c]!=='#';
/* 射程・範囲は「上下左右1マス／斜め2マス」＝マンハッタン距離 */
const dist=(a,b)=>Math.abs(a.r-b.r)+Math.abs(a.c-b.c);
const DIRS=[[-1,0],[1,0],[0,-1],[0,1]];
const sleep=ms=>new Promise(r=>setTimeout(r,ms));
const fxOn=()=>SPEED>=60;

/* ゴール加速ライン（ゴールとゴールを結ぶ直線） */
const ACCEL=new Set();
for(const r of ACCEL_ROWS) for(let c=ACCEL_C0;c<=ACCEL_C1;c++) if(passable(r,c)) ACCEL.add(key(r,c));
const stepCost=(r,c)=>ACCEL.has(key(r,c))?COST_FAST:COST_NORMAL;
const budgetOf=u=>u.spd*COST_NORMAL;

/* =========================================================
   SETUP
   ========================================================= */
function makeUnit(def,team,idx){
  return { uid:team+idx, def, team, kind:'poke', name:def.name, spr:SPR[def.id],
    maxHp:def.hp, hp:def.hp, atk:def.atk, dfs:def.def, spd:def.spd, rng:def.rng,
    r:0,c:0, pts:0, cd:[0,0], shield:0, shieldT:0, stun:0, stunNew:0, down:0,
    charge:0, chargeNeed:0, chargeGid:-1, isPlayer:false, lane:'mid', ord:0 };
}
function makeWild(sp,i){
  const d=WILD_DEFS[sp.t];
  return { uid:'w'+i, team:'wild', kind:'wild', def:d, name:d.name, spr:SPR[sp.t],
    maxHp:d.hp, hp:sp.spawnTurn?0:d.hp, atk:d.atk, dfs:d.def, rng:d.rng, spd:0,
    r:sp.r,c:sp.c, home:{r:sp.r,c:sp.c}, pts:0, shield:0, shieldT:0, stun:0, stunNew:0,
    charge:0, down:sp.spawnTurn?999:0, spawnTurn:sp.spawnTurn||0, resp:d.resp, ptsGive:d.pts };
}
function shuffle(a){for(let i=a.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[a[i],a[j]]=[a[j],a[i]];}return a;}

function startGame(pokeId){
  const pool=POKEMON.filter(p=>p.id!==pokeId); shuffle(pool);
  const mine=POKEMON.find(p=>p.id===pokeId);
  const allyD=[mine,...pool.slice(0,4)], enemD=pool.slice(4,9);

  S={turn:1,units:[],wilds:[],goals:[],score:{ally:0,enemy:0},log:[],over:false,order:[]};
  allyD.forEach((d,i)=>{const u=makeUnit(d,'ally',i); if(i===0)u.isPlayer=true; S.units.push(u);});
  enemD.forEach((d,i)=>S.units.push(makeUnit(d,'enemy',i)));
  const LANES=['mid','top','top','bot','bot'];
  ['ally','enemy'].forEach(t=>S.units.filter(u=>u.team===t).forEach((u,i)=>{
    u.lane=LANES[i];
    const p=START[t][i]; u.r=p.r; u.c=p.c;
  }));

  const A=S.units.filter(u=>u.team==='ally'), E=S.units.filter(u=>u.team==='enemy');
  S.order=[A[0]];
  for(let i=0;i<5;i++){ if(E[i])S.order.push(E[i]); if(A[i+1])S.order.push(A[i+1]); }
  S.order.forEach((u,i)=>{u.ord=i+1;});

  S.wilds=WILD_SPAWNS.map(makeWild);
  S.goals=GOAL_DEFS.map((g,i)=>({...g,gid:i,filled:0,alive:true}));
  initAreas(S.goals);

  movingUid=null; clearFx();
  fxEl.innerHTML='';
  document.getElementById('ovSelect').classList.add('hide');
  document.getElementById('turnMax').textContent=TURN_LIMIT;
  pushLog('th','── バトル開始！ ──');
  pushLog('th','ターン 1');
  render();
  maybeAutoPass();
}

function freeSpawn(team){ return freeNear(BASE[team]); }
function freeNear(b){
  const seen=new Set([key(b.r,b.c)]); const q=[{r:b.r,c:b.c}];
  let i=0;
  while(i<q.length){
    const cur=q[i++];
    if(passable(cur.r,cur.c)&&!unitAt(cur.r,cur.c)) return cur;
    for(const [dr,dc] of DIRS){
      const nr=cur.r+dr,nc=cur.c+dc;
      if(!passable(nr,nc)||seen.has(key(nr,nc))) continue;
      seen.add(key(nr,nc)); q.push({r:nr,c:nc});
    }
  }
  return {r:b.r,c:b.c};
}

/* =========================================================
   QUERIES
   ========================================================= */
function allActors(){ return S.units.concat(S.wilds); }
function isAlive(u){ return u.down===0 && u.hp>0; }
function liveWilds(){ return S.wilds.filter(isAlive); }
function unitAt(r,c){ return allActors().find(u=>isAlive(u)&&u.r===r&&u.c===c)||null; }
function isFoe(a,b){
  if(!a||!b||a===b) return false;
  if(a.team==='wild') return b.team!=='wild';
  if(b.team==='wild') return true;
  return a.team!==b.team;
}
function foesOf(u){ return allActors().filter(x=>isAlive(x)&&isFoe(u,x)); }
function alliesOf(u){ return S.units.filter(x=>isAlive(x)&&x.team===u.team&&x!==u); }
function player(){ return S.units[0]; }

const inGoal=(g,r,c)=>Math.abs(r-g.r)<=1&&Math.abs(c-g.c)<=1;
function goalTiles(g){
  const t=[];
  for(let r=g.r-1;r<=g.r+1;r++)for(let c=g.c-1;c<=g.c+1;c++) if(passable(r,c)) t.push({r,c});
  return t;
}
function inBaseZone(team,r,c){
  const z=BASE_ZONE[team];
  return r>=z.r0&&r<=z.r1&&c>=z.c0&&c<=z.c1;
}
function openGoalsFor(team){
  const owner = team==='ally' ? 'enemy' : 'ally';
  const gs = S.goals.filter(g=>g.team===owner);
  const destroyed = gs.filter(g=>!g.alive).length;
  return gs.filter(g=>{
    if(!g.alive) return false;
    if(g.tier===1) return true;
    if(g.tier===2){ const o=gs.find(x=>x.lane===g.lane&&x.tier===1); return o&&!o.alive; }
    return destroyed>=2;
  });
}
function goalUnderFoot(u){ return openGoalsFor(u.team).find(g=>inGoal(g,u.r,u.c))||null; }
const chargeNeed = pts => Math.min(5, 1+Math.floor(pts/4));

/* =========================================================
   PATHFINDING  (4方向 / コスト付き / 他ユニットはすり抜け可)
   ========================================================= */
function costField(targets){
  const d=new Int32Array(W*H).fill(-1);
  const buckets=[];
  const push=(c,k)=>{ (buckets[c]||(buckets[c]=[])).push(k); };
  for(const t of (Array.isArray(targets)?targets:[targets])){
    if(!passable(t.r,t.c)) continue;
    const k=key(t.r,t.c);
    if(d[k]<0){ d[k]=0; push(0,k); }
  }
  for(let c=0;c<buckets.length;c++){
    const b=buckets[c]; if(!b) continue;
    for(let i=0;i<b.length;i++){
      const k=b[i]; if(d[k]!==c) continue;
      const r=(k/W)|0, cc=k%W;
      for(const [dr,dc] of DIRS){
        const nr=r+dr,nc=cc+dc;
        if(!passable(nr,nc)) continue;
        const nk=key(nr,nc), nd=c+stepCost(nr,nc);
        if(d[nk]>=0&&nd>=d[nk]) continue;
        d[nk]=nd; push(nd,nk);
      }
    }
  }
  return d;
}
/* 到達可能マス：経路は他ユニットをすり抜け、止まれるのは空きマスのみ
   → 周囲を囲まれても動けなくならない */
function reachable(u){
  const budget=budgetOf(u);
  const d=new Int32Array(W*H).fill(-1);
  const start=key(u.r,u.c); d[start]=0;
  const buckets=[[start]];
  for(let c=0;c<buckets.length&&c<=budget;c++){
    const b=buckets[c]; if(!b) continue;
    for(let i=0;i<b.length;i++){
      const k=b[i]; if(d[k]!==c) continue;
      const r=(k/W)|0, cc=k%W;
      for(const [dr,dc] of DIRS){
        const nr=r+dr,nc=cc+dc;
        if(!passable(nr,nc)) continue;
        const nk=key(nr,nc), nd=c+stepCost(nr,nc);
        if(nd>budget||(d[nk]>=0&&nd>=d[nk])) continue;
        d[nk]=nd; (buckets[nd]||(buckets[nd]=[])).push(nk);
      }
    }
  }
  const out=new Map();
  for(let k=0;k<W*H;k++){
    if(k===start||d[k]<0) continue;
    const occ=unitAt((k/W)|0,k%W);
    if(!occ||occ===u) out.set(k,d[k]);
  }
  return out;
}
/* dest 方向へ budget 分進む道順（マス列）を返す。止まれない終点は手前まで戻す */
function pathTo(u,dests,budget){
  const f=costField(dests);
  const path=[]; let cur={r:u.r,c:u.c}, spent=0;
  for(let guard=0;guard<W*H;guard++){
    const cd=f[key(cur.r,cur.c)];
    if(cd<=0) break;
    let best=null,bd=cd,bc=0;
    for(const [dr,dc] of DIRS){
      const nr=cur.r+dr,nc=cur.c+dc;
      if(!passable(nr,nc)) continue;
      const dv=f[key(nr,nc)];
      if(dv<0||dv>=bd) continue;
      bd=dv; best={r:nr,c:nc}; bc=stepCost(nr,nc);
    }
    if(!best||spent+bc>budget) break;
    spent+=bc; cur=best; path.push(cur);
  }
  while(path.length){
    const last=path[path.length-1];
    const occ=unitAt(last.r,last.c);
    if(!occ||occ===u) break;
    path.pop();
  }
  return path;
}
function distTo(u,dests){
  const d=costField(dests)[key(u.r,u.c)];
  return d<0?99999:d;
}

/* =========================================================
   COMBAT
   ========================================================= */
const calcDmg=(src,tgt,power)=>Math.max(1,Math.round((power+src.atk)*DMG_K/(100+tgt.dfs)));

function markAttack(src,tgt){
  const dr=tgt.r-src.r, dc=tgt.c-src.c;
  const m=Math.max(Math.abs(dr),Math.abs(dc))||1;
  fxAttacker={uid:src.uid, ax:+(dc/m).toFixed(2), ay:+(dr/m).toFixed(2)};
  if(!fxShake.includes(tgt.uid)) fxShake.push(tgt.uid);
  if(fxOn()&&dist(src,tgt)>1) fxBeam(src,tgt,src.team==='ally'?'#8ec6ff':(src.team==='enemy'?'#ffa0a0':'#ffe6a0'));
}
function applyDamage(src,tgt,power,label){
  let dmg=calcDmg(src,tgt,power);
  if(tgt.shield>0){ const ab=Math.min(tgt.shield,dmg); tgt.shield-=ab; dmg-=ab; }
  tgt.hp-=dmg;
  hitCells.push(key(tgt.r,tgt.c));
  markAttack(src,tgt);
  floatText(tgt.r,tgt.c,'-'+dmg,'dmg');
  pushLog(logCls(src), `${mark(src)}${src.name} の ${label} → ${mark(tgt)}${tgt.name} に ${dmg}`);
  if(tgt.charge>0){
    tgt.charge=0; tgt.chargeNeed=0; tgt.chargeGid=-1;
    pushLog('ko',`  └ ${mark(tgt)}${tgt.name} のシュートはキャンセルされた！`);
    floatText(tgt.r,tgt.c,'シュート中断','ko');
  }
  if(tgt.hp<=0) knockOut(src,tgt);
}
function knockOut(src,tgt){
  tgt.hp=0; tgt.shield=0; tgt.charge=0; tgt.chargeNeed=0;
  let gain=0;
  if(tgt.kind==='wild') gain=tgt.ptsGive;
  else { gain=tgt.pts+1; tgt.pts=0; }
  if(src.kind==='poke'&&gain>0){
    src.pts+=gain;
    floatText(src.r,src.c,'+'+gain+'点','pt');
  }
  pushLog('ko',`💥 ${mark(tgt)}${tgt.name} がダウン！${src.kind==='poke'?` ${mark(src)}${src.name} が ${gain}点 獲得`:''}`);
  tgt.down = tgt.kind==='wild' ? tgt.resp : 3;
}
const mark=u=>u.team==='ally'?'🔵':(u.team==='enemy'?'🔴':'⚪');
const logCls=u=>u.team==='ally'?'a':(u.team==='enemy'?'e':'w');

/* =========================================================
   ACTION EXECUTION   戻り値: {from, path, acted}
   ========================================================= */
function execAction(u,act){
  const res={from:{r:u.r,c:u.c},path:null,acted:false};
  if(!isAlive(u)||!act) return res;
  if(u.stun>0){ pushLog('w',`${mark(u)}${u.name} は行動できない…`); return res; }
  if(act.type!=='goal'&&u.charge>0){ u.charge=0; u.chargeNeed=0; u.chargeGid=-1; }

  if(act.type==='wait'||act.type==='none'){ pushLog(logCls(u),`${mark(u)}${u.name} は待機`); return res; }

  if(act.type==='move'){
    const to=act.to; if(!to) return res;
    const path=pathTo(u,to,budgetOf(u));
    if(path.length){
      const end=path[path.length-1];
      pushLog(logCls(u),`👟 ${mark(u)}${u.name} が (${res.from.r},${res.from.c}) → (${end.r},${end.c}) へ移動`);
      u.r=end.r; u.c=end.c; res.path=path;
    }else{
      pushLog('w',`${mark(u)}${u.name} は移動できなかった`);
    }
    return res;
  }
  if(act.type==='goal'){ execGoal(u); res.acted=true; return res; }

  if(act.type==='attack'){
    const t=act.target;
    if(!t||!isAlive(t)||dist(u,t)>u.rng){ pushLog('w',`${mark(u)}${u.name} のこうげきは届かなかった`); return res; }
    applyDamage(u,t,0,'こうげき'); res.acted=true; return res;
  }
  if(act.type==='skill'){
    const m=u.def.moves[act.idx];
    if(u.cd[act.idx]>0) return res;
    let used=false;

    if(m.kind==='single'){
      const t=act.target;
      if(t&&isAlive(t)&&dist(u,t)<=m.range){
        applyDamage(u,t,m.power,m.name);
        if(m.stun&&isAlive(t)){ t.stunNew=1; pushLog('w',`  └ ${t.name} は次のターン行動できない！`);
          floatText(t.r,t.c,'行動不能','ko'); }
        used=true;
      }
    } else if(m.kind==='aoe'){
      const p=act.at;
      if(p&&dist(u,p)<=m.range){
        pushLog(logCls(u),`${mark(u)}${u.name} の ${m.name}！`);
        const list=foesOf(u).filter(x=>dist(x,p)<=m.radius);
        if(!list.length) pushLog('w','  └ だが誰にも当たらなかった…');
        list.forEach(x=>{ if(isAlive(x)) applyDamage(u,x,m.power,m.name); });
        used=true;
      }
    } else if(m.kind==='dash'){
      const t=act.target;
      if(t&&isAlive(t)&&dist(u,t)<=m.range){
        const path=pathTo(u,{r:t.r,c:t.c},m.dash*COST_NORMAL);
        if(path.length){ const e=path[path.length-1]; u.r=e.r; u.c=e.c; res.path=path; }
        if(dist(u,t)<=Math.max(1,u.rng)) applyDamage(u,t,m.power,m.name);
        else pushLog('w',`${mark(u)}${u.name} の ${m.name} は届かなかった…`);
        used=true;
      }
    } else if(m.kind==='heal'){
      const t=act.target;
      if(t&&isAlive(t)&&dist(u,t)<=m.range){
        const list = m.radius ? S.units.filter(x=>isAlive(x)&&x.team===u.team&&dist(x,t)<=m.radius) : [t];
        pushLog(logCls(u),`${mark(u)}${u.name} の ${m.name}！`);
        list.forEach(x=>{ const b=x.hp; x.hp=Math.min(x.maxHp,x.hp+m.heal);
          if(x.hp>b) floatText(x.r,x.c,'+'+(x.hp-b),'heal');
          pushLog(logCls(u),`  └ ${x.name} を ${x.hp-b} 回復`); });
        used=true;
      }
    } else if(m.kind==='shield'){
      const list=[...new Set([u,...S.units.filter(x=>isAlive(x)&&x.team===u.team&&dist(x,u)<=(m.range||0))])];
      list.forEach(x=>{ x.shield=Math.max(x.shield,m.shield); x.shieldT=3; floatText(x.r,x.c,'🛡','heal'); });
      pushLog(logCls(u),`${mark(u)}${u.name} の ${m.name}！ ${list.length}体にシールド`);
      used=true;
    }
    if(used){ u.cd[act.idx]=m.cd; res.acted=true; }
    return res;
  }
  return res;
}

function execGoal(u){
  const g=goalUnderFoot(u);
  if(!g||u.pts<=0){
    u.charge=0; u.chargeNeed=0; u.chargeGid=-1;
    pushLog('w',`${mark(u)}${u.name} はシュートできなかった`);
    return;
  }
  if(u.charge===0||u.chargeGid!==g.gid){ u.chargeGid=g.gid; u.chargeNeed=chargeNeed(u.pts); u.charge=0; }
  u.charge++;
  if(u.charge<u.chargeNeed){
    pushLog(logCls(u),`🎯 ${mark(u)}${u.name} がシュート中… (${u.charge}/${u.chargeNeed})`);
    floatText(u.r,u.c,`${u.charge}/${u.chargeNeed}`,'sc');
    return;
  }
  const amt=Math.min(u.pts,g.cap-g.filled);
  g.filled+=amt; u.pts-=amt; S.score[u.team]+=amt;
  u.charge=0; u.chargeNeed=0; u.chargeGid=-1;
  pushLog('sc',`⭐ ${mark(u)}${u.name} がシュート成功！ ${amt}点（${laneName(g)}ゴール）`);
  floatText(u.r,u.c,`GOAL +${amt}`,'sc');
  if(g.filled>=g.cap){ g.alive=false; pushLog('sc',`🔥 ${g.team==='ally'?'味方':'敵'}の${laneName(g)}ゴールを破壊！`); }
}
const laneName=g=>g.lane==='top'?'上':(g.lane==='bot'?'下':'中央');

/* =========================================================
   AI
   ========================================================= */
function skillScore(u,i){
  const m=u.def.moves[i];
  if(u.cd[i]>0) return null;
  const foes=foesOf(u);
  if(m.kind==='single'){
    const c=foes.filter(x=>dist(u,x)<=m.range);
    if(!c.length) return null;
    c.sort((a,b)=>a.hp-b.hp);
    const t=c[0], d=calcDmg(u,t,m.power);
    return {sc:d*(d>=t.hp+t.shield?3:1)+(m.stun?40:0), act:{type:'skill',idx:i,target:t}};
  }
  if(m.kind==='aoe'){
    let best=null;
    for(const f of foes){
      if(dist(u,f)>m.range) continue;
      const p={r:f.r,c:f.c};
      const n=foes.filter(x=>dist(x,p)<=m.radius);
      const sc=n.reduce((s,x)=>s+calcDmg(u,x,m.power),0)*(n.length>1?1.4:1);
      if(!best||sc>best.sc) best={sc,act:{type:'skill',idx:i,at:p}};
    }
    return best;
  }
  if(m.kind==='dash'){
    if(u.hp<u.maxHp*0.35) return null;
    const c=foes.filter(x=>dist(u,x)<=m.range);
    if(!c.length) return null;
    c.sort((a,b)=>a.hp-b.hp);
    return {sc:calcDmg(u,c[0],m.power)*1.1, act:{type:'skill',idx:i,target:c[0]}};
  }
  if(m.kind==='heal'){
    const c=[u,...alliesOf(u)].filter(x=>dist(u,x)<=m.range&&x.hp<x.maxHp*0.72);
    if(!c.length) return null;
    c.sort((a,b)=>(a.hp/a.maxHp)-(b.hp/b.maxHp));
    return {sc:Math.min(m.heal,c[0].maxHp-c[0].hp)*1.15, act:{type:'skill',idx:i,target:c[0]}};
  }
  if(m.kind==='shield'){
    if(u.hp>u.maxHp*0.7||!foesOf(u).some(x=>dist(u,x)<=3)) return null;
    return {sc:m.shield*0.8, act:{type:'skill',idx:i}};
  }
  return null;
}
function healSpots(team){
  const t=[]; const z=BASE_ZONE[team];
  for(let r=z.r0;r<=z.r1;r++)for(let c=z.c0;c<=z.c1;c++) if(passable(r,c)) t.push({r,c});
  S.goals.filter(g=>g.team===team&&g.alive).forEach(g=>t.push(...goalTiles(g)));
  return t;
}
function moveAct(u,dests){
  const p=pathTo(u,dests,budgetOf(u));
  if(!p.length) return null;
  const e=p[p.length-1];
  if(e.r===u.r&&e.c===u.c) return null;
  return {type:'move',to:e};
}
function aiAction(u){
  if(u.stun>0) return {type:'none'};
  const foes=foesOf(u), pokeFoes=foes.filter(x=>x.kind==='poke');
  const here=goalUnderFoot(u);
  if(u.charge>0&&here&&u.pts>0) return {type:'goal'};

  if(u.hp<u.maxHp*0.3&&pokeFoes.some(x=>dist(u,x)<=5)){
    const spots=healSpots(u.team);
    if(spots.length&&distTo(u,spots)>0){ const a=moveAct(u,spots); if(a) return a; }
  }
  if(here&&u.pts>0) return {type:'goal'};

  const goals=openGoalsFor(u.team);
  if(u.pts>=2&&goals.length){
    const gs=goals.map(g=>({g,d:distTo(u,goalTiles(g))})).sort((a,b)=>a.d-b.d);
    if(gs[0].d<=budgetOf(u)+6){ const a=moveAct(u,goalTiles(gs[0].g)); if(a) return a; }
  }
  let best=null;
  for(let i=0;i<2;i++){ const s=skillScore(u,i); if(s&&(!best||s.sc>best.sc)) best=s; }
  const inR=foes.filter(x=>dist(u,x)<=u.rng);
  if(inR.length){
    inR.sort((a,b)=>{
      const ka=calcDmg(u,a,0)>=a.hp+a.shield?0:1, kb=calcDmg(u,b,0)>=b.hp+b.shield?0:1;
      if(ka!==kb) return ka-kb;
      const pa=a.kind==='poke'?0:1, pb=b.kind==='poke'?0:1;
      if(pa!==pb) return pa-pb;
      return a.hp-b.hp;
    });
    const t=inR[0], d=calcDmg(u,t,0);
    const sc=d*(d>=t.hp+t.shield?3:1);
    if(!best||sc>best.sc) best={sc,act:{type:'attack',target:t}};
  }
  if(best) return best.act;

  if(u.pts>=2&&goals.length){
    const gs=goals.map(g=>({g,d:distTo(u,goalTiles(g))})).sort((a,b)=>a.d-b.d);
    const a=moveAct(u,goalTiles(gs[0].g)); if(a) return a;
  }
  const targets=[];
  for(const w of liveWilds()){
    let b=0;
    if(u.lane==='top'&&w.home.r<=5) b=7;
    if(u.lane==='bot'&&w.home.r>=11) b=7;
    if(u.lane==='mid'&&w.home.r>5&&w.home.r<11) b=7;
    if(w.def===WILD_DEFS.zapdos) b+=16;
    if(w.def===WILD_DEFS.drednaw) b+=9;
    targets.push({r:w.r,c:w.c,sc:b+w.ptsGive*2-dist(u,w)*0.6});
  }
  for(const f of pokeFoes){
    let b=(f.pts>=4?9:0)+(f.hp<f.maxHp*0.45?5:0)+(f.charge>0?14:0);
    if(u.lane==='top'&&f.r<=5) b+=3;
    if(u.lane==='bot'&&f.r>=11) b+=3;
    targets.push({r:f.r,c:f.c,sc:b-dist(u,f)*0.7});
  }
  for(const g of S.goals.filter(x=>x.team===u.team&&x.alive)){
    const intruder=goalTiles(g).map(t=>unitAt(t.r,t.c)).find(o=>o&&isFoe(u,o)&&o.pts>0);
    if(intruder) targets.push({r:intruder.r,c:intruder.c,sc:24-dist(u,intruder)*0.4});
  }
  if(!targets.length&&goals.length) targets.push({r:goals[0].r,c:goals[0].c,sc:1});
  if(!targets.length) return {type:'wait'};
  targets.sort((a,b)=>b.sc-a.sc);
  return moveAct(u,{r:targets[0].r,c:targets[0].c}) || {type:'wait'};
}

/* =========================================================
   FX LAYER  (移動アニメ / 攻撃演出 / ダメージ表示)
   ========================================================= */
const fxEl=document.getElementById('fx');
const CS=()=>parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--cs'))||30;
function floatText(r,c,txt,cls){
  if(!fxOn()) return;
  const cs=CS(), d=document.createElement('div');
  d.className='fxtext '+cls;
  d.textContent=txt;
  d.style.left=(c*cs+cs/2)+'px';
  d.style.top=(r*cs-cs*0.12)+'px';
  d.style.fontSize=Math.max(10,cs*0.42)+'px';
  fxEl.appendChild(d);
  setTimeout(()=>d.remove(),1050);
}
function fxBeam(a,b,color){
  const cs=CS();
  const x1=a.c*cs+cs/2, y1=a.r*cs+cs/2, x2=b.c*cs+cs/2, y2=b.r*cs+cs/2;
  const len=Math.hypot(x2-x1,y2-y1), ang=Math.atan2(y2-y1,x2-x1)*180/Math.PI;
  const d=document.createElement('div');
  d.className='fxbeam';
  d.style.left=x1+'px'; d.style.top=(y1-1.5)+'px';
  d.style.width=len+'px';
  d.style.transform=`rotate(${ang}deg)`;
  d.style.background=`linear-gradient(90deg,transparent,${color},#fff)`;
  d.style.boxShadow=`0 0 8px ${color}`;
  fxEl.appendChild(d);
  setTimeout(()=>d.remove(),380);
}
function trailDot(r,c,cs){
  const d=document.createElement('div');
  d.className='fxtrail';
  d.style.left=(c*cs+cs*0.34)+'px'; d.style.top=(r*cs+cs*0.34)+'px';
  d.style.width=(cs*0.32)+'px'; d.style.height=(cs*0.32)+'px';
  d.style.background='rgba(140,235,255,.8)';
  fxEl.appendChild(d);
  setTimeout(()=>d.remove(),620);
}
/* 始点→終点を1マスずつなめらかに移動させる */
async function animateMove(u,from,path){
  if(!fxOn()||!path||!path.length) return;
  const cs=CS();
  movingUid=u.uid; render();
  const tok=document.createElement('div');
  tok.className='fxtok '+(u.team==='ally'?'a':(u.team==='enemy'?'e':'w'))+(u.isPlayer?' me':'');
  tok.style.width=cs+'px'; tok.style.height=cs+'px';
  tok.innerHTML='<span class="ring"></span>'+u.spr;
  tok.style.transform=`translate(${from.c*cs}px,${from.r*cs}px)`;
  fxEl.appendChild(tok);
  const per=Math.max(55,Math.min(130,Math.round(SPEED/4)));
  await sleep(20);
  tok.style.transition=`transform ${per}ms linear`;
  let prev=from;
  for(const p of path){
    trailDot(prev.r,prev.c,cs);
    tok.style.transform=`translate(${p.c*cs}px,${p.r*cs}px)`;
    prev=p;
    await sleep(per);
  }
  tok.remove();
  movingUid=null;
}
function clearFx(){ fxAttacker=null; fxShake=[]; hitCells=[]; }

/* =========================================================
   TURN LOOP  (1匹ずつ順番に決定・実行)
   ========================================================= */
async function runTurn(playerAct){
  if(S.over||running) return;
  running=true; sel=null;

  for(const u of S.order){
    if(S.over) break;
    curActor=u; clearFx();
    if(!isAlive(u)) continue;
    const isAI = u!==player();
    if(isAI){ render(); await sleep(fxOn()?SPEED*0.28:0); }

    const act = isAI ? aiAction(u) : playerAct;
    const res = execAction(u,act);
    if(res.path&&res.path.length) await animateMove(u,res.from,res.path);
    render();
    if(fxOn()) await sleep(res.acted?Math.min(SPEED*0.75,360):SPEED*0.22);
    clearFx();
  }

  curActor=null; clearFx();
  for(const w of liveWilds()){
    if(w.stun>0) continue;
    const t=foesOf(w).filter(x=>dist(w,x)<=w.rng).sort((a,b)=>a.hp-b.hp)[0];
    if(t) applyDamage(w,t,0,'こうげき');
  }
  if(hitCells.length){ render(); if(fxOn()) await sleep(Math.min(SPEED*0.7,340)); }

  endTurn();
  running=false; clearFx();
  render();
  if(!S.over) maybeAutoPass();
}

function endTurn(){
  for(const u of S.units){
    for(let i=0;i<2;i++) if(u.cd[i]>0) u.cd[i]--;
    if(u.shieldT>0){ u.shieldT--; if(u.shieldT===0) u.shield=0; }
    if(u.stun>0) u.stun--;
    if(u.stunNew){ u.stun=1; u.stunNew=0; }

    if(isAlive(u)&&u.hp<u.maxHp){
      const base=inBaseZone(u.team,u.r,u.c);
      const g=S.goals.find(x=>x.team===u.team&&x.alive&&inGoal(x,u.r,u.c));
      if(base||g){
        const before=u.hp;
        u.hp=Math.min(u.maxHp,u.hp+Math.round(u.maxHp*(base?BASE_HEAL:GOAL_HEAL)));
        if(u.hp>before){
          pushLog(logCls(u),`💚 ${mark(u)}${u.name} が${base?'ベース':'自陣ゴール'}で ${u.hp-before} 回復`);
          floatText(u.r,u.c,'+'+(u.hp-before),'heal');
        }
      }
    }
    if(u.charge>0&&!goalUnderFoot(u)){ u.charge=0; u.chargeNeed=0; u.chargeGid=-1; }

    if(u.down>0){
      u.down--;
      if(u.down===0){
        const p=freeSpawn(u.team); u.r=p.r; u.c=p.c; u.hp=u.maxHp; u.shield=0;
        pushLog(logCls(u),`🔄 ${mark(u)}${u.name} が復帰した`);
      }
    }
  }
  for(const w of S.wilds){
    if(w.stun>0) w.stun--;
    if(w.stunNew){ w.stun=1; w.stunNew=0; }
    if(w.down<=0) continue;
    /* 野生ポケモンは一切移動しない。復活は必ず元の位置で、
       誰かが乗っている間は次のターンまで待つ（ずれ・重なりを防ぐ） */
    if(w.spawnTurn&&w.down===999){
      if(S.turn+1>=w.spawnTurn&&!unitAt(w.home.r,w.home.c)){
        w.down=0; w.hp=w.maxHp; w.r=w.home.r; w.c=w.home.c;
        pushLog('sc',`⚡ ${w.name} が中央に出現！（${w.ptsGive}点）`);
      }
      continue;
    }
    w.down--;
    if(w.down===0){
      if(unitAt(w.home.r,w.home.c)) w.down=1;
      else { w.hp=w.maxHp; w.r=w.home.r; w.c=w.home.c; w.pts=0; }
    }
  }
  S.turn++;
  checkEnd();
  if(!S.over) pushLog('th',`ターン ${S.turn}`);
}

function maybeAutoPass(){
  if(!AUTO_PASS||S.over||running) return;
  const u=player();
  if(!isAlive(u)||u.stun>0) setTimeout(()=>{ if(!running&&!S.over) runTurn({type:'none'}); },Math.min(SPEED,420));
}
function checkEnd(){
  const aDead=S.goals.filter(g=>g.team==='ally'&&!g.alive).length;
  const eDead=S.goals.filter(g=>g.team==='enemy'&&!g.alive).length;
  if(eDead>=5) return endGame('ally','相手ゴールを全破壊！');
  if(aDead>=5) return endGame('enemy','自陣ゴールが全破壊された…');
  if(S.turn>TURN_LIMIT){
    const w=S.score.ally>S.score.enemy?'ally':(S.score.enemy>S.score.ally?'enemy':'draw');
    return endGame(w,'制限ターン終了');
  }
}
function endGame(win,note){
  S.over=true;
  const big=document.getElementById('rBig');
  big.textContent = win==='ally'?'WIN!':(win==='enemy'?'LOSE...':'DRAW');
  big.className='big '+(win==='ally'?'win':(win==='enemy'?'lose':'draw'));
  document.getElementById('rSc').innerHTML=
    `<span style="color:var(--ally)">${S.score.ally}</span> - <span style="color:var(--enemy)">${S.score.enemy}</span>`;
  document.getElementById('rNote').textContent=note;
  document.getElementById('ovResult').classList.remove('hide');
}
function pushLog(cls,txt){ S.log.push({cls,txt}); if(S.log.length>400) S.log.splice(0,120); }

/* =========================================================
   TARGETING / INPUT
   ========================================================= */
function validTargets(u,s){
  const out=new Map();
  if(!s||!isAlive(u)||u.stun>0) return out;
  if(s.type==='move'){
    for(const [k,cost] of reachable(u)) out.set(k,{r:(k/W)|0,c:k%W,cost});
    return out;
  }
  if(s.type==='attack'){
    foesOf(u).forEach(t=>{ if(dist(u,t)<=u.rng) out.set(key(t.r,t.c),{r:t.r,c:t.c,target:t}); });
    return out;
  }
  if(s.type!=='skill') return out;
  const m=u.def.moves[s.idx];
  if(u.cd[s.idx]>0) return out;
  if(m.kind==='single'||m.kind==='dash'){
    foesOf(u).forEach(t=>{ if(dist(u,t)<=m.range) out.set(key(t.r,t.c),{r:t.r,c:t.c,target:t}); });
  }else if(m.kind==='aoe'){
    for(let r=0;r<H;r++)for(let c=0;c<W;c++) if(passable(r,c)&&dist(u,{r,c})<=m.range) out.set(key(r,c),{r,c});
  }else if(m.kind==='heal'){
    [u,...alliesOf(u)].forEach(t=>{ if(dist(u,t)<=m.range) out.set(key(t.r,t.c),{r:t.r,c:t.c,target:t}); });
  }else if(m.kind==='shield'){
    out.set(key(u.r,u.c),{r:u.r,c:u.c});
  }
  return out;
}
function hlClass(u,s,v){
  if(s.type==='move') return dist(u,v)>u.spd ? 'hlMoveFast' : 'hlMove';
  if(s.type==='attack') return 'hlAtk';
  const m=u.def.moves[s.idx];
  if(m.kind==='heal'||m.kind==='shield') return 'hlHeal';
  if(m.kind==='aoe') return 'hlArea';
  return 'hlAtk';
}
function onCellClick(r,c){
  if(S.over||running||!sel) return;
  const u=player();
  const v=validTargets(u,sel).get(key(r,c));
  if(!v) return;
  let act=null;
  if(sel.type==='move') act={type:'move',to:{r,c}};
  else if(sel.type==='attack') act={type:'attack',target:v.target};
  else{
    const m=u.def.moves[sel.idx];
    if(m.kind==='aoe') act={type:'skill',idx:sel.idx,at:{r,c}};
    else if(m.kind==='shield') act={type:'skill',idx:sel.idx};
    else act={type:'skill',idx:sel.idx,target:v.target};
  }
  runTurn(act);
}
function pickAct(s){
  if(S.over||running) return;
  const u=player();
  if(!isAlive(u)||u.stun>0) return;
  if(s.type==='goal'){ runTurn({type:'goal'}); return; }
  if(s.type==='wait'){ runTurn({type:'wait'}); return; }
  if(s.type==='skill'){
    if(u.cd[s.idx]>0) return;
    if(u.def.moves[s.idx].kind==='shield'){ runTurn({type:'skill',idx:s.idx}); return; }
  }
  sel=(sel&&sel.type===s.type&&sel.idx===s.idx)?null:s;
  render();
}

/* =========================================================
   RENDER
   ========================================================= */
const mapEl=document.getElementById('map');
mapEl.addEventListener('click',e=>{
  const c=e.target.closest('.cell'); if(!c) return;
  onCellClick(+c.dataset.r,+c.dataset.c);
});
mapEl.addEventListener('mousemove',e=>{
  const c=e.target.closest('.cell');
  hoverAoe(c?+c.dataset.r:-1,c?+c.dataset.c:-1);
});
mapEl.addEventListener('mouseleave',()=>hoverAoe(-1,-1));

let aoeKeys=[];
function hoverAoe(r,c){
  let next=[];
  if(r>=0&&sel&&sel.type==='skill'&&!S.over&&!running){
    const u=player(), m=u.def.moves[sel.idx];
    if(m.kind==='aoe'&&validTargets(u,sel).has(key(r,c))){
      for(let rr=0;rr<H;rr++)for(let cc=0;cc<W;cc++)
        if(Math.abs(rr-r)+Math.abs(cc-c)<=m.radius) next.push(key(rr,cc));
    }
  }
  if(next.join()===aoeKeys.join()) return;
  aoeKeys.forEach(k=>mapEl.children[k]&&mapEl.children[k].classList.remove('aoe'));
  next.forEach(k=>mapEl.children[k]&&mapEl.children[k].classList.add('aoe'));
  aoeKeys=next;
}

const GOAL_AREA=new Map();
function initAreas(goals){
  GOAL_AREA.clear();
  goals.forEach(g=>goalTiles(g).forEach(t=>GOAL_AREA.set(key(t.r,t.c),g)));
}
function terrainClass(r,c){
  const t=MAP[r][c];
  if(t==='#') return 'wall';
  let cls;
  const g=GOAL_AREA.get(key(r,c));
  if(g) cls=(g.team==='ally'?'gaA':'gaE')+(g.alive?'':' dead');
  else if(inBaseZone('ally',r,c)) cls='baseA';
  else if(inBaseZone('enemy',r,c)) cls='baseE';
  else if(t==='~') cls='bush';
  else if(r<=4||r>=12) cls='lane';
  else if(c<=9) cls='zoneA';
  else if(c>=W-10) cls='zoneE';
  else cls='';
  if(ACCEL.has(key(r,c))) cls+=' accel';
  return cls;
}

function render(){
  if(!S) return;
  const u=player();
  const openA=openGoalsFor('enemy').map(g=>g.gid);
  const openE=openGoalsFor('ally').map(g=>g.gid);

  document.getElementById('scA').textContent=S.score.ally;
  document.getElementById('scE').textContent=S.score.enemy;
  document.getElementById('turnNo').textContent=Math.min(S.turn,TURN_LIMIT);
  document.getElementById('phaseTxt').textContent =
    S.over?'試合終了':(running?(curActor?`${curActor.name} 行動中`:'解決中')
      :(isAlive(u)?(u.stun>0?'行動不能':'あなたの番'):`気絶中 (復帰まで${u.down})`));
  const pip=(team,open)=>S.goals.filter(g=>g.team===team).sort((a,b)=>a.tier-b.tier)
    .map(g=>`<span class="gpip ${!g.alive?'dead':(open.includes(g.gid)?'open':'')}">${laneName(g)}${g.tier} ${g.alive?(g.cap-g.filled):'×'}</span>`).join('');
  document.getElementById('gA').innerHTML=pip('ally',openA);
  document.getElementById('gE').innerHTML=pip('enemy',openE);

  document.getElementById('orderStrip').innerHTML='<span class="lbl">行動順</span>'+
    S.order.map(o=>{
      const done=running&&curActor&&S.order.indexOf(o)<S.order.indexOf(curActor);
      return `<div class="oi ${o.team==='ally'?'a':'e'}${o===curActor&&running?' now':''}${o.isPlayer?' me':''}`+
             `${isAlive(o)?'':' dead'}${done?' done':''}" title="${o.name}">`+
             `<span class="no">${o.ord}</span>${o.spr}</div>`;
    }).join('');

  const vt=(sel&&!running&&!S.over)?validTargets(u,sel):new Map();
  const occupied=new Map();
  allActors().forEach(a=>{ if(isAlive(a)) occupied.set(key(a.r,a.c),a); });
  const downedAt=new Map();
  S.units.forEach(a=>{ if(!isAlive(a)){ const b=BASE[a.team]; downedAt.set(key(b.r,b.c),(downedAt.get(key(b.r,b.c))||0)+1); } });

  let html='';
  for(let r=0;r<H;r++)for(let c=0;c<W;c++){
    const k=key(r,c);
    let cls='cell '+terrainClass(r,c);
    const v=vt.get(k);
    if(v) cls+=' '+hlClass(u,sel,v);
    if(hitCells.includes(k)) cls+=' hit';
    html+=`<div class="${cls}" data-r="${r}" data-c="${c}">`;
    const g=S.goals.find(x=>x.r===r&&x.c===c);
    if(g){
      const open=g.team==='ally'?openA.includes(g.gid):openE.includes(g.gid);
      html+=`<div class="gbox ${g.team==='ally'?'a':'e'}${!g.alive?' dead':(open?'':' closed')}">`+
            `<b>${g.alive?(g.cap-g.filled):'×'}</b></div>`;
    }
    const a=occupied.get(k);
    if(a&&a.uid!==movingUid){
      const tc=a.team==='ally'?'a':(a.team==='enemy'?'e':'w');
      const ratio=Math.max(0,a.hp)/a.maxHp;
      const lunge=!!(fxAttacker&&fxAttacker.uid===a.uid);
      const shake=fxShake.includes(a.uid);
      html+=`<div class="u ${tc}${a.isPlayer?' me':''}${a===curActor&&running?' now':''}`+
            `${lunge?' lunge':''}${shake?' shake':''}" title="${unitTip(a)}"`+
            (lunge?` style="--ax:${fxAttacker.ax};--ay:${fxAttacker.ay}"`:'')+'>'+
            `<span class="ring"></span>${a.spr}`+
            `<div class="hp ${ratio<0.3?'s1':(ratio<0.6?'s2':'')}"><i style="width:${ratio*100}%"></i></div>`+
            (a.shield>0?'<div class="sh"></div>':'')+
            (a.charge>0?`<div class="chgring" style="--p:${Math.round(a.charge/a.chargeNeed*100)}"></div>`+
                        `<div class="chgtag">⚡${a.charge}/${a.chargeNeed}</div>`:'')+
            (a.pts>0?`<div class="pts">${a.pts}</div>`:'')+
            (a.kind==='wild'?`<div class="wpt">◆${a.ptsGive}</div>`:'')+
            (a.stun>0?'<div class="badge">💫</div>':'')+
            `</div>`;
    }else if(!a&&downedAt.has(k)){
      html+=`<div class="downmk">💤${downedAt.get(k)>1?downedAt.get(k):''}</div>`;
    }
    html+='</div>';
  }
  mapEl.innerHTML=html;
  aoeKeys=[];

  document.getElementById('meAv').innerHTML=u.spr;
  document.getElementById('meNm').textContent=u.name;
  document.getElementById('meSt').innerHTML=
    `<span>${u.def.role}</span><span>⚔${u.atk}</span><span>🛡${u.dfs}</span><span>👟${u.spd}</span><span>🎯${u.rng}</span>`;
  const rr=Math.max(0,u.hp)/u.maxHp;
  const bar=document.getElementById('meBar');
  bar.className='bigbar '+(rr<0.3?'s1':(rr<0.6?'s2':''));
  bar.innerHTML=`<i style="width:${rr*100}%"></i>`;
  document.getElementById('meHp').textContent=
    `${Math.max(0,u.hp)} / ${u.maxHp}${u.shield>0?` (+🛡${u.shield})`:''}　所持得点 ${u.pts}`;

  const gHere=isAlive(u)?goalUnderFoot(u):null;
  const need=u.charge>0?u.chargeNeed:chargeNeed(u.pts);
  const active=u.charge>0;
  document.getElementById('shootBox').className='shootbox'+(active?'':' off');
  document.getElementById('shootTxt').textContent = active?`${u.charge} / ${u.chargeNeed} ターン`
    : (gHere&&u.pts>0?`開始すると ${need} ターン`:(u.pts>0?'ゴールエリア外':'得点なし'));
  document.getElementById('shootSegs').innerHTML=
    Array.from({length:Math.max(1,need)},(_,i)=>`<span class="${i<u.charge?'on':''}"></span>`).join('');

  const A=document.getElementById('acts'); A.innerHTML='';
  const dis=S.over||running||!isAlive(u)||u.stun>0;
  const add=(label,desc,s,off,tag,extra)=>{
    const b=document.createElement('button');
    b.className='act'+(sel&&sel.type===s.type&&sel.idx===s.idx?' sel':'')+(extra||'');
    b.disabled=dis||off;
    b.innerHTML=`<div class="t"><span>${label}</span>${tag?`<b>${tag}</b>`:''}</div><div class="d">${desc}</div>`;
    b.onclick=()=>pickAct(s);
    A.appendChild(b);
  };
  const canMove=isAlive(u)&&u.stun===0&&reachable(u).size>0;
  add('移動',canMove?`上下左右に${u.spd}マス（加速ラインは${u.spd*2}マス）`:'空きマスが無くて動けません',
      {type:'move'},!canMove,`👟${u.spd}`);
  add('こうげき',`射程${u.rng} / 威力 ${calcDmg(u,{dfs:35},0)}目安`,{type:'attack'},false,`🎯${u.rng}`);
  u.def.moves.forEach((m,i)=>{
    const off=u.cd[i]>0;
    const tag=off?`CT ${u.cd[i]}`:`射程${m.range}${m.radius?` 半径${m.radius}`:''}`;
    add(`わざ${i+1}: ${m.name}`,m.desc+`（CT${m.cd}）`,{type:'skill',idx:i},off,tag);
  });
  const gOk=!!gHere&&u.pts>0;
  add(u.charge>0?`ゴール（継続 ${u.charge}/${u.chargeNeed}）`:'ゴール',
      gOk?`${need}ターンでシュート完了。ダメージを受けると中断`
        :(gHere?'得点を持っていません':'相手の有効ゴールのエリア内で使えます'),
      {type:'goal'},!gOk,gOk?`⏱${need}`:'',' wide goal');
  add(u.stun>0?'行動不能（ターンを進める）':'待機',(!isAlive(u)?'気絶中です':'何もしないでターンを進める'),
      {type:'wait'},S.over||running||!isAlive(u),'',' wide');

  const hint=document.getElementById('hint');
  if(S.over) hint.textContent='';
  else if(running) hint.textContent='自動行動中…';
  else if(!isAlive(u)) hint.textContent='気絶中です。自動でターンが進みます。';
  else if(u.stun>0) hint.textContent='行動不能です。自動でターンが進みます。';
  else if(sel){
    const t=sel.type==='move'?'移動先（水色に光るマスは加速ライン経由）':(sel.type==='attack'?'攻撃する相手':
      (u.def.moves[sel.idx].kind==='aoe'?'着弾させる地点':
       u.def.moves[sel.idx].kind==='heal'?'回復する味方':'わざの対象'));
    hint.textContent=`▶ マップ上で${t}をクリック（もう一度ボタンで解除）`;
  }else hint.textContent=`あなたの番です（行動順 ${u.ord} 番目）。行動を1つ選んでください。`;

  const row=a=>{
    const rt=Math.max(0,a.hp)/a.maxHp;
    return `<div class="rrow ${a.team==='ally'?'a':'e'}${isAlive(a)?'':' dead'}${a.isPlayer?' me':''}">`+
      `<div class="e">${a.spr}</div><div class="n">${a.name}</div>`+
      `<div class="b"><i style="width:${rt*100}%"></i></div>`+
      `<div class="p">${a.pts?'★'+a.pts:''}</div>`+
      `<div class="s">${isAlive(a)?Math.max(0,a.hp):'💤'+a.down}</div></div>`;
  };
  document.getElementById('rosterA').innerHTML=S.units.filter(x=>x.team==='ally').map(row).join('');
  document.getElementById('rosterE').innerHTML=S.units.filter(x=>x.team==='enemy').map(row).join('');

  const L=document.getElementById('log');
  L.innerHTML=S.log.slice(-140).map(l=>`<div class="${l.cls}">${l.txt}</div>`).join('');
  L.scrollTop=L.scrollHeight;
}
function unitTip(a){
  return `${a.name}（${a.team==='ally'?'味方':a.team==='enemy'?'敵':'野生'}）\n`+
    `HP ${Math.max(0,a.hp)}/${a.maxHp}\n素早さ ${a.spd} / 射程 ${a.rng}`+
    (a.kind==='wild'?`\n倒すと ${a.ptsGive}点`:'')+
    (a.pts?`\n所持得点 ${a.pts}`:'')+
    (a.charge>0?`\nシュート中 ${a.charge}/${a.chargeNeed}`:'');
}

/* =========================================================
   BOOT
   ========================================================= */
function buildPicks(){
  const P=document.getElementById('picks'); P.innerHTML='';
  POKEMON.forEach(p=>{
    const b=document.createElement('button');
    b.className='pick';
    b.innerHTML=`<div class="hd"><div class="av">${SPR[p.id]}</div>
        <span><span class="nm">${p.name}</span><br><span class="ro">${p.role}</span></span></div>
      <div class="stats"><span>HP ${p.hp}</span><span>こうげき ${p.atk}</span>
        <span>ぼうぎょ ${p.def}</span><span>素早さ ${p.spd}</span><span>射程 ${p.rng}</span><span></span></div>
      <div class="mv">
        <em>わざ1</em> ${p.moves[0].name}（射程${p.moves[0].range}${p.moves[0].radius?` 半径${p.moves[0].radius}`:''} / CT${p.moves[0].cd}）<br>
        <em>わざ2</em> ${p.moves[1].name}（射程${p.moves[1].range}${p.moves[1].radius?` 半径${p.moves[1].radius}`:''} / CT${p.moves[1].cd}）
      </div>`;
    b.onclick=()=>startGame(p.id);
    P.appendChild(b);
  });
}
function fitMap(){
  const wrap=document.getElementById('mapWrap');
  const avail=wrap.clientWidth-16;
  document.documentElement.style.setProperty('--cs',Math.max(17,Math.min(38,Math.floor(avail/W)))+'px');
}
document.getElementById('spdSel').addEventListener('change',e=>{ SPEED=+e.target.value; });
window.addEventListener('resize',fitMap);
buildPicks(); fitMap();
</script>
</body>
</html>
