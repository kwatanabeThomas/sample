<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ターン制ユナイトバトル</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --cs:30px;
  --bg0:#0b1020; --bg1:#121a33; --bg2:#1b2547;
  --ally:#3d8bff; --allyD:#1b4f9c;
  --enemy:#ff5a5a; --enemyD:#9c2323;
  --wild:#e6c979; --wildD:#6d5c33;
  --txt:#e9eefc; --sub:#9aa8c8;
  --line:#2c3860;
}
html,body{height:100%}
body{
  background:radial-gradient(1200px 700px at 50% -10%,#1d2a52 0%,#0b1020 60%,#070a15 100%);
  color:var(--txt);
  font-family:'Hiragino Kaku Gothic ProN','Yu Gothic','Meiryo',system-ui,sans-serif;
  padding:12px;
  -webkit-text-size-adjust:100%;
}
button{font-family:inherit;color:inherit;cursor:pointer;border:none;background:none}
button:disabled{cursor:not-allowed}

/* ============ layout ============ */
.wrap{max-width:1240px;margin:0 auto}
h1{font-size:1.35rem;letter-spacing:.04em;display:flex;align-items:center;gap:8px}
h1 .tag{font-size:.62rem;background:linear-gradient(90deg,var(--ally),var(--enemy));padding:3px 8px;border-radius:99px;letter-spacing:.1em}
.sub{color:var(--sub);font-size:.78rem;margin-top:3px}

/* ============ scoreboard ============ */
.board{
  display:flex;align-items:stretch;gap:10px;margin:12px 0 8px;
  background:linear-gradient(180deg,#182144,#111834);
  border:1px solid var(--line);border-radius:12px;padding:10px 12px;
}
.side{flex:1;display:flex;flex-direction:column;gap:4px}
.side .nm{font-size:.7rem;letter-spacing:.12em;color:var(--sub)}
.side .sc{font-size:1.9rem;font-weight:800;line-height:1}
.side.a .sc{color:var(--ally)} .side.e{text-align:right} .side.e .sc{color:var(--enemy)}
.goalrow{display:flex;gap:3px;flex-wrap:wrap}
.side.e .goalrow{justify-content:flex-end}
.gpip{font-size:.6rem;padding:1px 5px;border-radius:4px;border:1px solid var(--line);background:#0e1428;color:var(--sub)}
.gpip.open{border-color:#5c74b8;color:#cfe0ff}
.gpip.dead{opacity:.35;text-decoration:line-through}
.tmid{text-align:center;display:flex;flex-direction:column;justify-content:center;min-width:110px}
.tmid .t{font-size:.68rem;color:var(--sub);letter-spacing:.1em}
.tmid .v{font-size:1.15rem;font-weight:700}
.tmid .v small{font-size:.7rem;color:var(--sub);font-weight:400}

/* ============ main ============ */
.main{display:flex;gap:12px;align-items:flex-start}
.left{flex:1;min-width:0;max-width:100%}
.right{width:318px;flex:none;display:flex;flex-direction:column;gap:10px}
@media(max-width:1080px){.main{flex-direction:column}.right{width:100%}}

/* ============ map ============ */
#mapWrap{
  background:linear-gradient(180deg,#0f1630,#0b1124);
  border:1px solid var(--line);border-radius:12px;padding:6px;overflow:auto;
}
#map{display:grid;grid-template-columns:repeat(26,var(--cs));margin:0 auto;width:max-content}
.cell{
  width:var(--cs);height:var(--cs);position:relative;
  background:#16204a;outline:1px solid rgba(255,255,255,.028);outline-offset:-1px;
}
.cell.wall{background:#080c1c;outline-color:transparent}
.cell.wall::after{content:'';position:absolute;inset:12%;border-radius:3px;background:#111935}
.cell.bush{background:#1c3a26}
.cell.bush::after{content:'🌿';position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:calc(var(--cs)*.5);opacity:.5}
.cell.zoneA{background:#123256}
.cell.zoneE{background:#2c1a2c}
.cell.baseA{background:#1c3c74}
.cell.baseE{background:#5c2020}
.cell.lane{background:#1a2550}

/* goals */
.goal{position:absolute;inset:6%;border-radius:50%;display:flex;align-items:center;justify-content:center;
  font-size:calc(var(--cs)*.36);font-weight:800;border:2px solid;}
.goal.a{border-color:#5aa2ff;background:rgba(40,90,180,.5);color:#dceaff}
.goal.e{border-color:#ff7d7d;background:rgba(180,50,50,.5);color:#ffe2e2}
.goal.closed{opacity:.45;border-style:dashed}
.goal.dead{opacity:.25;border-style:dotted}

/* units */
.u{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
  font-size:calc(var(--cs)*.66);line-height:1;z-index:3}
.u .ring{position:absolute;inset:3%;border-radius:50%;border:2px solid}
.u.a .ring{border-color:var(--ally);background:rgba(61,139,255,.18)}
.u.e .ring{border-color:var(--enemy);background:rgba(255,90,90,.18)}
.u.w .ring{border-color:var(--wild);background:rgba(230,201,121,.2)}
.u.me .ring{border-color:#ffe14d;box-shadow:0 0 0 2px rgba(255,225,77,.35),0 0 10px rgba(255,225,77,.5)}
.u .em{position:relative;z-index:2;filter:drop-shadow(0 1px 1px #000)}
.hp{position:absolute;left:8%;right:8%;bottom:1px;height:3px;border-radius:2px;background:#000a;overflow:hidden;z-index:4}
.hp i{display:block;height:100%;background:#4ce07a}
.hp.s2 i{background:#ffd24c}.hp.s1 i{background:#ff5d5d}
.sh{position:absolute;left:8%;right:8%;bottom:4px;height:2px;background:#7fd8ff;border-radius:2px;z-index:4}
.pts{position:absolute;top:-2px;right:-2px;background:#ffd24c;color:#3a2a00;font-size:calc(var(--cs)*.3);
  font-weight:800;border-radius:99px;padding:0 3px;min-width:calc(var(--cs)*.36);text-align:center;z-index:5;
  box-shadow:0 1px 3px #000a}
.stun{position:absolute;top:-3px;left:-2px;font-size:calc(var(--cs)*.34);z-index:5}
.down{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
  font-size:calc(var(--cs)*.4);color:#8792b5;z-index:3}

/* highlights */
.cell.hlMove{box-shadow:inset 0 0 0 2px #4ce07a;background:#1e3f3a;cursor:pointer}
.cell.hlAtk{box-shadow:inset 0 0 0 2px #ff8a4c;background:#42251c;cursor:pointer}
.cell.hlHeal{box-shadow:inset 0 0 0 2px #7fd8ff;background:#1b3b4a;cursor:pointer}
.cell.hlArea{box-shadow:inset 0 0 0 2px #ffe14d;background:#40381a;cursor:pointer}
.cell.aoe{background:#5a3a1a !important}
.cell.hit{animation:hit .4s ease-out}
@keyframes hit{0%{background:#ff5a5a !important}100%{}}

/* ============ panels ============ */
.card{background:linear-gradient(180deg,#182144,#111834);border:1px solid var(--line);border-radius:12px;padding:10px 11px}
.card h2{font-size:.72rem;letter-spacing:.12em;color:var(--sub);margin-bottom:7px;font-weight:600}

.mecard{display:flex;gap:9px;align-items:center}
.mecard .av{width:44px;height:44px;flex:none;border-radius:12px;background:#0e1630;
  border:2px solid #ffe14d;display:flex;align-items:center;justify-content:center;font-size:1.5rem}
.mecard .info{flex:1;min-width:0}
.mecard .nm{font-weight:700;font-size:.92rem}
.mecard .st{font-size:.66rem;color:var(--sub);display:flex;gap:8px;flex-wrap:wrap;margin-top:2px}
.bigbar{height:9px;background:#000a;border-radius:5px;overflow:hidden;margin-top:5px;position:relative}
.bigbar i{display:block;height:100%;background:linear-gradient(90deg,#3ce06f,#8ef0a8)}
.bigbar.s2 i{background:linear-gradient(90deg,#ffc93c,#ffe08a)}
.bigbar.s1 i{background:linear-gradient(90deg,#ff4d4d,#ff9a9a)}
.hpnum{font-size:.66rem;color:var(--sub);margin-top:2px;display:flex;justify-content:space-between}

.acts{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:8px}
.act{
  border:1px solid var(--line);background:#0e1630;border-radius:9px;padding:7px 8px;text-align:left;
  transition:.12s;
}
.act:hover:not(:disabled){border-color:#5c74b8;background:#152046}
.act.sel{border-color:#ffe14d;background:#2e2a12;box-shadow:0 0 0 1px #ffe14d inset}
.act:disabled{opacity:.4}
.act .t{font-size:.8rem;font-weight:700;display:flex;justify-content:space-between;align-items:center;gap:4px}
.act .t b{font-size:.6rem;color:#ffd24c;font-weight:600}
.act .d{font-size:.62rem;color:var(--sub);margin-top:2px;line-height:1.35}
.act.wide{grid-column:1/-1}
.hintbar{margin-top:7px;font-size:.7rem;color:#ffe14d;min-height:1.1em}

/* roster */
.rlist{display:flex;flex-direction:column;gap:4px}
.rrow{display:flex;align-items:center;gap:6px;font-size:.7rem;padding:2px 0}
.rrow .e{font-size:1rem;width:19px;text-align:center;flex:none}
.rrow .n{width:74px;flex:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rrow .b{flex:1;height:6px;background:#000a;border-radius:3px;overflow:hidden}
.rrow .b i{display:block;height:100%}
.rrow.a .b i{background:#3d8bff} .rrow.e .b i{background:#ff5a5a}
.rrow .p{width:26px;text-align:right;flex:none;color:#ffd24c;font-weight:700}
.rrow .s{width:34px;text-align:right;flex:none;color:var(--sub);font-size:.62rem}
.rrow.dead{opacity:.4}
.rrow.me .n{color:#ffe14d;font-weight:700}

/* log */
#log{height:190px;overflow-y:auto;font-size:.7rem;line-height:1.6;padding-right:4px}
#log .th{color:#ffe14d;font-weight:700;border-top:1px solid var(--line);margin-top:5px;padding-top:4px}
#log .th:first-child{border:none;margin-top:0}
#log .a{color:#a8ccff}#log .e{color:#ffb3b3}#log .w{color:#ddc890}
#log .sc{color:#7dffb0;font-weight:700}#log .ko{color:#ff8a8a;font-weight:700}
#log::-webkit-scrollbar{width:6px}#log::-webkit-scrollbar-thumb{background:#2c3860;border-radius:3px}

details.rules{margin-top:10px;background:#101838;border:1px solid var(--line);border-radius:10px;padding:8px 11px}
details.rules summary{cursor:pointer;font-size:.76rem;font-weight:700;color:#cfe0ff}
details.rules div{font-size:.72rem;color:var(--sub);line-height:1.75;margin-top:7px}
details.rules b{color:#e9eefc}

/* ============ overlays ============ */
.ov{position:fixed;inset:0;background:rgba(5,8,18,.9);backdrop-filter:blur(4px);z-index:100;
  display:flex;align-items:center;justify-content:center;padding:16px;overflow-y:auto}
.ov.hide{display:none}
.ovbox{max-width:900px;width:100%}
.ovbox h2{font-size:1.5rem;text-align:center;margin-bottom:4px}
.ovbox p.lead{text-align:center;color:var(--sub);font-size:.8rem;margin-bottom:14px}
.picks{display:grid;grid-template-columns:repeat(auto-fill,minmax(166px,1fr));gap:8px}
.pick{border:1px solid var(--line);background:linear-gradient(180deg,#182144,#111834);border-radius:11px;
  padding:9px;text-align:left;transition:.13s;display:flex;flex-direction:column}
.pick:hover{border-color:#7f9bff;transform:translateY(-2px)}
.pick .hd{display:flex;align-items:center;gap:7px}
.pick .hd .nm{line-height:1.25}
.pick .hd .em{font-size:1.6rem}
.pick .hd .nm{font-weight:800;font-size:.88rem}
.pick .hd .ro{font-size:.6rem;color:#ffd24c}
.pick .stats{display:grid;grid-template-columns:1fr 1fr;gap:1px 8px;font-size:.63rem;color:var(--sub);margin:6px 0 5px}
.pick .mv{margin-top:auto;font-size:.62rem;color:#b9c8ea;line-height:1.45;border-top:1px dashed var(--line);padding-top:4px}
.pick .mv em{color:#ffe14d;font-style:normal;font-weight:700}
.result{background:linear-gradient(180deg,#182144,#0e1430);border:1px solid var(--line);border-radius:16px;
  padding:26px 22px;text-align:center;max-width:420px;margin:0 auto}
.result .big{font-size:2.1rem;font-weight:900;letter-spacing:.06em}
.result .big.win{color:#4ce07a}.result .big.lose{color:#ff6b6b}.result .big.draw{color:#ffd24c}
.result .sc2{font-size:1.5rem;margin:10px 0;font-weight:700}
.btn{display:inline-block;margin-top:14px;padding:10px 26px;border-radius:99px;font-weight:700;font-size:.86rem;
  background:linear-gradient(90deg,#3d8bff,#7f5aff);box-shadow:0 4px 16px rgba(61,139,255,.35)}
.btn:hover{filter:brightness(1.12)}
</style>
</head>
<body>
<div class="wrap">

  <h1>ターン制ユナイトバトル <span class="tag">5 vs 5 / TURN BASED</span></h1>
  <div class="sub">同時ターン制の 5対5 チーム戦。相手ゴールに多く得点したチームの勝ち。</div>

  <div class="board">
    <div class="side a">
      <div class="nm">MY TEAM (青)</div>
      <div class="sc" id="scA">0</div>
      <div class="goalrow" id="gA"></div>
    </div>
    <div class="tmid">
      <div class="t">TURN</div>
      <div class="v"><span id="turnNo">1</span><small> / <span id="turnMax">60</span></small></div>
      <div class="t" style="margin-top:6px" id="phaseTxt">行動を選択</div>
    </div>
    <div class="side e">
      <div class="nm">(赤) ENEMY TEAM</div>
      <div class="sc" id="scE">0</div>
      <div class="goalrow" id="gE"></div>
    </div>
  </div>

  <div class="main">
    <div class="left">
      <div id="mapWrap"><div id="map"></div></div>
      <details class="rules">
        <summary>ルール / 操作説明</summary>
        <div>
          <b>■ 行動</b>：あなたのポケモンは毎ターン「移動 / こうげき / わざ1 / わざ2 / 待機」から<b>1つだけ</b>選べます。10匹全員が同じターンに1行動します。<br>
          <b>■ 解決順</b>：<b>①行動フェーズ（こうげき・わざ）→ ②移動フェーズ</b> の順に、素早さの高い順で処理されます。つまり<b>ターン開始時に敵の射程内にいると必ず攻撃を受けます</b>。射程外へ逃げるのではなく、最初から射程に入らない立ち回りが重要です。<br>
          <b>■ 素早さ</b>＝1回の移動で進めるマス数。斜め移動もできます（壁の角は抜けられません）。<br>
          <b>■ わざ</b>：使うとクールタイム（CT）が発生し、その間は再使用できません。<br>
          <b>■ 得点の入手</b>：野生ポケモンを倒す／相手ポケモンを倒す（相手が持っていた点＋1をもらう）。<br>
          <b>■ シュート</b>：得点を持ったまま<b>相手の有効ゴールのマスに立つ</b>と、移動フェーズ終了時に自動でシュートします。<br>
          <b>■ ゴール</b>：各チーム5個（上レーン2・下レーン2・中央1）。レーンは<b>外側→内側</b>の順にしか壊せません。<b>中央ゴールは相手ゴールを2つ以上壊すと開放</b>されます。<br>
          <b>■ 気絶</b>：HPが0になるとスタート地点に戻され、3ターン行動できません（持っていた点は倒した相手へ）。<br>
          <b>■ 勝敗</b>：制限ターン終了時に得点が多いチームの勝ち。相手ゴールを5個すべて壊すと即勝利。<br>
          <b>■ 中央のカジリガメ（🐢）は高得点。45ターン目にサンダー（🦅）が中央に出現します。</b>
        </div>
      </details>
    </div>

    <div class="right">
      <div class="card">
        <h2>YOUR POKÉMON</h2>
        <div class="mecard">
          <div class="av" id="meEm">?</div>
          <div class="info">
            <div class="nm" id="meNm">-</div>
            <div class="st" id="meSt"></div>
          </div>
        </div>
        <div class="bigbar" id="meBar"><i style="width:100%"></i></div>
        <div class="hpnum"><span id="meHp">-</span><span id="mePt"></span></div>
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

<!-- ===== select ===== -->
<div class="ov" id="ovSelect">
  <div class="ovbox">
    <h2>使うポケモンを選ぼう</h2>
    <p class="lead">残りの9匹が、味方4匹・敵5匹にランダムで振り分けられます。</p>
    <div class="picks" id="picks"></div>
  </div>
</div>

<!-- ===== result ===== -->
<div class="ov hide" id="ovResult">
  <div class="ovbox">
    <div class="result">
      <div class="big" id="rBig">-</div>
      <div class="sc2" id="rSc">-</div>
      <div style="font-size:.76rem;color:var(--sub)" id="rNote"></div>
      <button class="btn" onclick="location.reload()">もう一度あそぶ</button>
    </div>
  </div>
</div>

<script>
'use strict';

/* =========================================================
   MAP
   ========================================================= */
const MAP = [
"##########################",
"#........................#",
"#........................#",
"#........................#",
"#....####..####..####....#",
"#........................#",
"#...~..##......##..~.....#",
"#........................#",
"#...~..##......##..~.....#",
"#........................#",
"#....####..####..####....#",
"#........................#",
"#........................#",
"#........................#",
"##########################",
];
const H = MAP.length, W = MAP[0].length;
const TURN_LIMIT = 60;
const ZAPDOS_TURN = 45;
const DMG_K = 150;          // damage scaling constant

const BASE = { ally:{r:7,c:1}, enemy:{r:7,c:24} };

/* =========================================================
   POKÉMON
   kind: single / aoe / dash / heal / shield
   ========================================================= */
const POKEMON = [
 { id:'pika', name:'ピカチュウ', em:'⚡', role:'アタック', hp:480, atk:62, def:30, spd:3, rng:3,
   moves:[
    {name:'でんきショック', kind:'single', power:60, range:5, cd:2, desc:'遠くの敵1体に電撃'},
    {name:'エレキボール',   kind:'aoe',    power:45, range:4, radius:1, cd:4, desc:'着弾点の周囲1マスに雷'},
   ]},
 { id:'ninetales', name:'アローラキュウコン', em:'❄️', role:'アタック', hp:450, atk:66, def:28, spd:3, rng:4,
   moves:[
    {name:'れいとうビーム', kind:'single', power:75, range:6, cd:3, desc:'超射程の単体高火力'},
    {name:'ふぶき',        kind:'aoe',    power:40, range:4, radius:2, cd:5, desc:'広範囲(半径2)を凍らせる'},
   ]},
 { id:'chariz', name:'リザードン', em:'🐉', role:'バランス', hp:660, atk:58, def:46, spd:3, rng:1,
   moves:[
    {name:'ほのおのパンチ', kind:'dash', power:70, range:4, dash:3, cd:3, desc:'敵に踏み込んで殴る'},
    {name:'だいもんじ',    kind:'aoe',  power:55, range:3, radius:2, cd:5, desc:'自分の周りを焼き払う'},
   ]},
 { id:'lucario', name:'ルカリオ', em:'🥋', role:'バランス', hp:620, atk:62, def:44, spd:4, rng:1,
   moves:[
    {name:'しんくうは',   kind:'single', power:65, range:4, cd:2, desc:'離れた敵に波動を飛ばす'},
    {name:'インファイト', kind:'dash',   power:85, range:5, dash:4, cd:4, desc:'大きく踏み込んで連打'},
   ]},
 { id:'snorlax', name:'カビゴン', em:'😴', role:'ディフェンス', hp:880, atk:44, def:62, spd:2, rng:1,
   moves:[
    {name:'ヘビーボンバー', kind:'dash',   power:60, range:4, dash:3, cd:3, desc:'体当たりで突っ込む'},
    {name:'まもる',        kind:'shield', shield:230, range:2, cd:5, desc:'自分と周囲の味方にシールド'},
   ]},
 { id:'slowbro', name:'ヤドラン', em:'🐚', role:'ディフェンス', hp:800, atk:46, def:58, spd:2, rng:3,
   moves:[
    {name:'なみのり',     kind:'aoe',    power:50, range:4, radius:1, cd:3, desc:'波で範囲攻撃'},
    {name:'テレキネシス', kind:'single', power:70, range:4, cd:5, stun:true, desc:'1体を拘束し次ターン行動不能'},
   ]},
 { id:'gengar', name:'ゲンガー', em:'👻', role:'スピード', hp:430, atk:70, def:26, spd:5, rng:2,
   moves:[
    {name:'シャドーボール', kind:'single', power:80, range:3, cd:3, desc:'単体に大ダメージ'},
    {name:'たたりめ',      kind:'aoe',    power:45, range:2, radius:1, cd:4, desc:'近くの敵をまとめて叩く'},
   ]},
 { id:'absol', name:'アブソル', em:'🗡️', role:'スピード', hp:450, atk:74, def:24, spd:5, rng:1,
   moves:[
    {name:'つじぎり',       kind:'dash',   power:95, range:5, dash:4, cd:3, desc:'一気に距離を詰めて斬る'},
    {name:'サイコカッター', kind:'single', power:70, range:2, cd:2, desc:'近距離の敵を斬撃'},
   ]},
 { id:'wiggly', name:'プクリン', em:'🎈', role:'サポート', hp:570, atk:48, def:40, spd:3, rng:3,
   moves:[
    {name:'うたう',        kind:'single', power:20, range:4, cd:4, stun:true, desc:'眠らせて次ターン行動不能'},
    {name:'いやしのはどう', kind:'heal',  heal:190, range:3, cd:3, desc:'味方1体(自分可)を回復'},
   ]},
 { id:'comfey', name:'ワタシラガ', em:'🌸', role:'サポート', hp:510, atk:44, def:36, spd:4, rng:4,
   moves:[
    {name:'はなびらのまい', kind:'aoe',  power:40, range:4, radius:1, cd:3, desc:'花びらで範囲攻撃'},
    {name:'フラワーヒール', kind:'heal', heal:150, range:4, radius:1, cd:3, desc:'対象と周囲の味方を回復'},
   ]},
];

/* =========================================================
   WILD POKÉMON
   ========================================================= */
const WILD_DEFS = {
  otachi:  {name:'オタチ',    em:'🦫', hp:90,  atk:30, def:10, rng:1, pts:2,  resp:8 },
  ludi:    {name:'ルンパッパ', em:'🌴', hp:150, atk:40, def:20, rng:1, pts:3,  resp:12},
  bouff:   {name:'バッフロン', em:'🐃', hp:180, atk:45, def:25, rng:1, pts:4,  resp:12},
  drednaw: {name:'カジリガメ', em:'🐢', hp:320, atk:60, def:35, rng:1, pts:8,  resp:20},
  zapdos:  {name:'サンダー',  em:'🦅', hp:700, atk:80, def:40, rng:2, pts:25, resp:99},
};
const WILD_SPAWNS = [
  // lanes
  {t:'otachi', r:2,  c:6 }, {t:'otachi', r:2,  c:10}, {t:'otachi', r:2,  c:15}, {t:'otachi', r:2,  c:19},
  {t:'otachi', r:12, c:6 }, {t:'otachi', r:12, c:10}, {t:'otachi', r:12, c:15}, {t:'otachi', r:12, c:19},
  // jungle
  {t:'ludi',  r:6, c:6 }, {t:'bouff', r:8, c:6 },
  {t:'ludi',  r:6, c:18}, {t:'bouff', r:8, c:18},
  // center objectives
  {t:'drednaw', r:5, c:12}, {t:'drednaw', r:9, c:12},
  {t:'zapdos',  r:7, c:12, spawnTurn:ZAPDOS_TURN},
];

/* =========================================================
   GOALS  (team = 所有チーム。相手がここに得点する)
   ========================================================= */
const GOAL_DEFS = [
  {team:'ally',  lane:'top', tier:1, r:2,  c:8,  cap:30},
  {team:'ally',  lane:'top', tier:2, r:2,  c:4,  cap:40},
  {team:'ally',  lane:'bot', tier:1, r:12, c:8,  cap:30},
  {team:'ally',  lane:'bot', tier:2, r:12, c:4,  cap:40},
  {team:'ally',  lane:'mid', tier:3, r:7,  c:3,  cap:60},
  {team:'enemy', lane:'top', tier:1, r:2,  c:17, cap:30},
  {team:'enemy', lane:'top', tier:2, r:2,  c:21, cap:40},
  {team:'enemy', lane:'bot', tier:1, r:12, c:17, cap:30},
  {team:'enemy', lane:'bot', tier:2, r:12, c:21, cap:40},
  {team:'enemy', lane:'mid', tier:3, r:7,  c:22, cap:60},
];

/* =========================================================
   STATE
   ========================================================= */
let S = null;
let sel = null;     // {type:'move'|'attack'|'skill', idx}
let busy = false;

const key = (r,c)=>r*W+c;
const inb = (r,c)=>r>=0&&r<H&&c>=0&&c<W;
const ch  = (r,c)=>MAP[r][c];
const passable = (r,c)=>inb(r,c)&&ch(r,c)!=='#';
const cheb = (a,b)=>Math.max(Math.abs(a.r-b.r),Math.abs(a.c-b.c));
const DIRS = [[-1,0],[1,0],[0,-1],[0,1],[-1,-1],[-1,1],[1,-1],[1,1]];

function stepOk(r,c,dr,dc){
  const nr=r+dr,nc=c+dc;
  if(!passable(nr,nc)) return false;
  if(dr!==0&&dc!==0){ if(!passable(r+dr,c)||!passable(r,c+dc)) return false; }
  return true;
}

/* ---------- setup ---------- */
function makeUnit(def, team, idx){
  return {
    uid:team+idx, def, team, kind:'poke',
    name:def.name, em:def.em,
    maxHp:def.hp, hp:def.hp, atk:def.atk, dfs:def.def, spd:def.spd, rng:def.rng,
    r:0, c:0, pts:0, cd:[0,0], shield:0, shieldT:0,
    stun:0, stunNew:0, down:0, isPlayer:false, lane:'mid',
  };
}
function makeWild(sp,i){
  const d = WILD_DEFS[sp.t];
  return { uid:'w'+i, team:'wild', kind:'wild', def:d, name:d.name, em:d.em,
    maxHp:d.hp, hp:(sp.spawnTurn?0:d.hp), atk:d.atk, dfs:d.def, rng:d.rng, spd:0,
    r:sp.r, c:sp.c, home:{r:sp.r,c:sp.c}, pts:0, shield:0, shieldT:0, stun:0, stunNew:0,
    down: sp.spawnTurn ? 999 : 0, spawnTurn: sp.spawnTurn||0, resp:d.resp, ptsGive:d.pts };
}

function startGame(pokeId){
  const pool = POKEMON.filter(p=>p.id!==pokeId);
  shuffle(pool);
  const mine  = POKEMON.find(p=>p.id===pokeId);
  const allyD = [mine, ...pool.slice(0,4)];
  const enemD = pool.slice(4,9);

  S = { turn:1, units:[], wilds:[], goals:[], score:{ally:0,enemy:0}, log:[], over:false };

  allyD.forEach((d,i)=>{ const u=makeUnit(d,'ally',i); if(i===0) u.isPlayer=true; S.units.push(u); });
  enemD.forEach((d,i)=>{ S.units.push(makeUnit(d,'enemy',i)); });
  // 両チーム同じレーン配分（上2・下2・中央1）。index0 が中央 = プレイヤー枠。
  const LANES=['mid','top','top','bot','bot'];
  ['ally','enemy'].forEach(t=>{
    S.units.filter(u=>u.team===t).forEach((u,i)=>{ u.lane=LANES[i]; });
  });

  S.wilds = WILD_SPAWNS.map(makeWild);
  S.goals = GOAL_DEFS.map((g,i)=>({...g, gid:i, filled:0, alive:true}));

  S.units.forEach(u=>{ const p=freeSpawn(u.team); u.r=p.r; u.c=p.c; });

  document.getElementById('ovSelect').classList.add('hide');
  document.getElementById('turnMax').textContent = TURN_LIMIT;
  pushLog('th','── バトル開始！ ──');
  render();
}

function shuffle(a){ for(let i=a.length-1;i>0;i--){ const j=Math.floor(Math.random()*(i+1)); [a[i],a[j]]=[a[j],a[i]]; } return a; }

function freeSpawn(team){ return freeNear(BASE[team]); }
/* (r,c) から最も近い「通行可能かつ空き」のマスを返す */
function freeNear(b){
  const seen = new Set([key(b.r,b.c)]);
  let q=[{r:b.r,c:b.c}];
  while(q.length){
    const cur=q.shift();
    if(passable(cur.r,cur.c) && !unitAt(cur.r,cur.c)) return cur;
    for(const [dr,dc] of DIRS){
      const nr=cur.r+dr,nc=cur.c+dc;
      if(!passable(nr,nc)||seen.has(key(nr,nc))) continue;
      seen.add(key(nr,nc)); q.push({r:nr,c:nc});
    }
  }
  return {r:b.r,c:b.c};
}

/* ---------- queries ---------- */
function allActors(){ return S.units.concat(S.wilds); }
function isAlive(u){ return u.down===0 && u.hp>0; }
function livePokes(){ return S.units.filter(isAlive); }
function liveWilds(){ return S.wilds.filter(isAlive); }
function unitAt(r,c){ return allActors().find(u=>isAlive(u)&&u.r===r&&u.c===c) || null; }
function isFoe(a,b){
  if(!a||!b||a===b) return false;
  if(a.team==='wild') return b.team!=='wild';
  if(b.team==='wild') return true;
  return a.team!==b.team;
}
function foesOf(u){ return allActors().filter(x=>isAlive(x)&&isFoe(u,x)); }
function alliesOf(u){ return S.units.filter(x=>isAlive(x)&&x.team===u.team&&x!==u); }
function player(){ return S.units[0]; }

/* goal availability: 攻撃側 team が得点できるゴール */
function openGoalsFor(team){
  const owner = team==='ally' ? 'enemy' : 'ally';
  const gs = S.goals.filter(g=>g.team===owner);
  const destroyed = gs.filter(g=>!g.alive).length;
  return gs.filter(g=>{
    if(!g.alive) return false;
    if(g.tier===1) return true;
    if(g.tier===2){
      const outer = gs.find(x=>x.lane===g.lane&&x.tier===1);
      return outer && !outer.alive;
    }
    return destroyed>=2; // center
  });
}

/* ---------- pathfinding ---------- */
function distField(targets){
  const d = new Int16Array(W*H).fill(-1);
  const q=[];
  for(const t of targets){ if(passable(t.r,t.c)&&d[key(t.r,t.c)]<0){ d[key(t.r,t.c)]=0; q.push(t); } }
  let i=0;
  while(i<q.length){
    const cur=q[i++]; const cd=d[key(cur.r,cur.c)];
    for(const [dr,dc] of DIRS){
      if(!stepOk(cur.r,cur.c,dr,dc)) continue;
      const nr=cur.r+dr,nc=cur.c+dc;
      if(d[key(nr,nc)]>=0) continue;
      d[key(nr,nc)]=cd+1; q.push({r:nr,c:nc});
    }
  }
  return d;
}
/* 移動可能マス（他ユニットは通行不可） */
function reachable(u){
  const out=new Map(); const start=key(u.r,u.c);
  out.set(start,0);
  let frontier=[{r:u.r,c:u.c}];
  for(let step=1;step<=u.spd;step++){
    const nxt=[];
    for(const cur of frontier){
      for(const [dr,dc] of DIRS){
        if(!stepOk(cur.r,cur.c,dr,dc)) continue;
        const nr=cur.r+dr,nc=cur.c+dc,k=key(nr,nc);
        if(out.has(k)) continue;
        const occ=unitAt(nr,nc);
        if(occ&&occ!==u) continue;
        out.set(k,step); nxt.push({r:nr,c:nc});
      }
    }
    frontier=nxt;
    if(!frontier.length) break;
  }
  out.delete(start);
  return out;
}
/* dest へ最大 steps 歩いた到達点 */
function walkToward(u,dest,steps){
  const f = distField([dest]);
  let cur={r:u.r,c:u.c};
  for(let i=0;i<steps;i++){
    let best=null,bd=f[key(cur.r,cur.c)];
    if(bd<0) break;
    for(const [dr,dc] of DIRS){
      if(!stepOk(cur.r,cur.c,dr,dc)) continue;
      const nr=cur.r+dr,nc=cur.c+dc;
      const dv=f[key(nr,nc)];
      if(dv<0||dv>=bd) continue;
      const occ=unitAt(nr,nc);
      if(occ&&occ!==u) continue;
      if(!best||dv<f[key(best.r,best.c)]) best={r:nr,c:nc};
    }
    if(!best) break;
    cur=best;
    if(cur.r===dest.r&&cur.c===dest.c) break;
  }
  return cur;
}

/* ---------- combat ---------- */
function calcDmg(src,tgt,power){
  return Math.max(1, Math.round((power + src.atk) * DMG_K / (100 + tgt.dfs)));
}
let hitCells = [];
function applyDamage(src,tgt,power,label){
  let dmg = calcDmg(src,tgt,power);
  if(tgt.shield>0){
    const ab=Math.min(tgt.shield,dmg); tgt.shield-=ab; dmg-=ab;
  }
  tgt.hp -= dmg;
  hitCells.push(key(tgt.r,tgt.c));
  const cls = src.team==='ally'?'a':(src.team==='enemy'?'e':'w');
  pushLog(cls, `${teamMark(src)}${src.name} の ${label} → ${teamMark(tgt)}${tgt.name} に ${dmg}`);
  if(tgt.hp<=0) knockOut(src,tgt);
}
function knockOut(src,tgt){
  tgt.hp=0; tgt.shield=0;
  let gain=0;
  if(tgt.kind==='wild'){ gain = tgt.ptsGive; }
  else { gain = tgt.pts + 1; tgt.pts=0; }
  if(src.kind==='poke' && gain>0){ src.pts += gain; }
  pushLog('ko', `💥 ${teamMark(tgt)}${tgt.name} がダウン！ ${src.kind==='poke'?`${teamMark(src)}${src.name} が ${gain}点 獲得`:''}`);
  if(tgt.kind==='wild'){ tgt.down = tgt.resp; }
  else { tgt.down = 3; }
}
function teamMark(u){ return u.team==='ally'?'🔵':(u.team==='enemy'?'🔴':'⚪'); }

/* ---------- action execution ---------- */
function execAction(u,act){
  if(!isAlive(u)||!act) return;
  if(u.stun>0) return;
  if(act.type==='wait'||act.type==='move'||act.type==='none') return;

  if(act.type==='attack'){
    const t=act.target;
    if(!t||!isAlive(t)||cheb(u,t)>u.rng) return;
    applyDamage(u,t,0,'こうげき');
    return;
  }
  if(act.type==='skill'){
    const m=u.def.moves[act.idx];
    if(u.cd[act.idx]>0) return;
    let used=false;

    if(m.kind==='single'){
      const t=act.target;
      if(t&&isAlive(t)&&cheb(u,t)<=m.range){
        applyDamage(u,t,m.power,m.name);
        if(m.stun&&isAlive(t)){ t.stunNew=1; pushLog('w',`  └ ${t.name} は行動不能になった！`); }
        used=true;
      }
    }
    else if(m.kind==='aoe'){
      const p=act.at;
      if(p&&cheb(u,p)<=m.range){
        const list=foesOf(u).filter(x=>cheb(x,p)<=m.radius);
        pushLog(u.team==='ally'?'a':'e', `${teamMark(u)}${u.name} の ${m.name}！`);
        if(!list.length) pushLog('w','  └ だが誰にも当たらなかった…');
        list.forEach(x=>applyDamage(u,x,m.power,m.name));
        used=true;
      }
    }
    else if(m.kind==='dash'){
      const t=act.target;
      if(t&&isAlive(t)&&cheb(u,t)<=m.range){
        const p=walkToward(u,{r:t.r,c:t.c},m.dash);
        if(p.r!==u.r||p.c!==u.c){ u.r=p.r; u.c=p.c; }
        if(cheb(u,t)<=Math.max(1,u.rng)) applyDamage(u,t,m.power,m.name);
        else pushLog('w',`${teamMark(u)}${u.name} の ${m.name} は届かなかった…`);
        used=true;
      }
    }
    else if(m.kind==='heal'){
      const t=act.target;
      if(t&&isAlive(t)&&cheb(u,t)<=m.range){
        const list = m.radius ? S.units.filter(x=>isAlive(x)&&x.team===u.team&&cheb(x,t)<=m.radius) : [t];
        list.forEach(x=>{
          const before=x.hp;
          x.hp=Math.min(x.maxHp,x.hp+m.heal);
          pushLog(u.team==='ally'?'a':'e', `${teamMark(u)}${u.name} の ${m.name} → ${x.name} を ${x.hp-before} 回復`);
        });
        used=true;
      }
    }
    else if(m.kind==='shield'){
      const list=[u,...S.units.filter(x=>isAlive(x)&&x.team===u.team&&cheb(x,u)<=(m.range||0))];
      const uniq=[...new Set(list)];
      uniq.forEach(x=>{ x.shield=Math.max(x.shield,m.shield); x.shieldT=3; });
      pushLog(u.team==='ally'?'a':'e', `${teamMark(u)}${u.name} の ${m.name}！ ${uniq.length}体にシールド`);
      used=true;
    }
    if(used) u.cd[act.idx]=m.cd;
  }
}

/* ---------- AI ---------- */
function skillScore(u,i){
  const m=u.def.moves[i];
  if(u.cd[i]>0) return null;
  const foes=foesOf(u);
  if(m.kind==='single'){
    const t=foes.filter(x=>cheb(u,x)<=m.range).sort((a,b)=>a.hp-b.hp)[0];
    if(!t) return null;
    return {sc: calcDmg(u,t,m.power)*(t.hp<=calcDmg(u,t,m.power)?3:1)+(m.stun?40:0),
            act:{type:'skill',idx:i,target:t}};
  }
  if(m.kind==='aoe'){
    let best=null;
    for(const f of foes){
      if(cheb(u,f)>m.range) continue;
      const p={r:f.r,c:f.c};
      const n=foes.filter(x=>cheb(x,p)<=m.radius);
      const sc=n.reduce((s,x)=>s+calcDmg(u,x,m.power),0)*(n.length>1?1.4:1);
      if(!best||sc>best.sc) best={sc,act:{type:'skill',idx:i,at:p}};
    }
    return best;
  }
  if(m.kind==='dash'){
    const t=foes.filter(x=>cheb(u,x)<=m.range).sort((a,b)=>a.hp-b.hp)[0];
    if(!t) return null;
    if(u.hp<u.maxHp*0.35) return null;      // 突っ込むと危険
    return {sc: calcDmg(u,t,m.power)*1.1, act:{type:'skill',idx:i,target:t}};
  }
  if(m.kind==='heal'){
    const cand=[u,...alliesOf(u)].filter(x=>cheb(u,x)<=m.range&&x.hp<x.maxHp*0.72);
    if(!cand.length) return null;
    cand.sort((a,b)=>(a.hp/a.maxHp)-(b.hp/b.maxHp));
    const t=cand[0];
    return {sc: Math.min(m.heal, t.maxHp-t.hp)*1.15, act:{type:'skill',idx:i,target:t}};
  }
  if(m.kind==='shield'){
    const near=foesOf(u).filter(x=>cheb(u,x)<=3).length;
    if(!near||u.hp>u.maxHp*0.7) return null;
    return {sc: m.shield*0.8, act:{type:'skill',idx:i}};
  }
  return null;
}

function aiAction(u){
  if(u.stun>0) return {type:'none'};
  const foes = foesOf(u);
  const pokeFoes = foes.filter(x=>x.kind==='poke');
  const nearestFoe = foes.slice().sort((a,b)=>cheb(u,a)-cheb(u,b))[0];
  const goals = openGoalsFor(u.team);

  // 1) 危険なら撤退
  if(u.hp < u.maxHp*0.3 && pokeFoes.some(x=>cheb(u,x)<=5)){
    const dest = BASE[u.team];
    if(cheb(u,dest)>1){
      const p=walkToward(u,dest,u.spd);
      if(p.r!==u.r||p.c!==u.c) return {type:'move',to:p};
    }
  }

  // 2) 得点を持っていればシュートへ
  if(u.pts>=3 && goals.length){
    const gs = goals.slice().sort((a,b)=>cheb(u,a)-cheb(u,b));
    const g = gs[0];
    if(u.r===g.r && u.c===g.c) return {type:'wait'};
    // ゴール直前なら攻撃よりも前進を優先
    const p=walkToward(u,{r:g.r,c:g.c},u.spd);
    if(cheb(u,g)<=u.spd+2 && (p.r!==u.r||p.c!==u.c)) return {type:'move',to:p};
  }

  // 3) わざ
  let best=null;
  for(let i=0;i<2;i++){
    const s=skillScore(u,i);
    if(s&&(!best||s.sc>best.sc)) best=s;
  }
  // 4) 通常こうげき
  const inRange = foes.filter(x=>cheb(u,x)<=u.rng);
  if(inRange.length){
    // とどめを刺せる相手を優先
    inRange.sort((a,b)=>{
      const ka=calcDmg(u,a,0)>=a.hp+a.shield?0:1, kb=calcDmg(u,b,0)>=b.hp+b.shield?0:1;
      if(ka!==kb) return ka-kb;
      const pa=a.kind==='poke'?0:1, pb=b.kind==='poke'?0:1;
      if(pa!==pb) return pa-pb;
      return a.hp-b.hp;
    });
    const t=inRange[0];
    const sc=calcDmg(u,t,0)*(calcDmg(u,t,0)>=t.hp+t.shield?3:1);
    if(!best||sc>best.sc) best={sc,act:{type:'attack',target:t}};
  }
  if(best) return best.act;

  // 5) 得点を持っていればシュートへ（遠くても）
  if(u.pts>=3 && goals.length){
    const g = goals.slice().sort((a,b)=>cheb(u,a)-cheb(u,b))[0];
    const p=walkToward(u,{r:g.r,c:g.c},u.spd);
    if(p.r!==u.r||p.c!==u.c) return {type:'move',to:p};
  }

  // 6) 目標へ移動
  const targets=[];
  const wilds = liveWilds();
  // レーン担当の野生
  for(const w of wilds){
    let bonus=0;
    if(u.lane==='top' && w.home.r<=4) bonus=6;
    if(u.lane==='bot' && w.home.r>=10) bonus=6;
    if(u.lane==='mid' && w.home.r>4 && w.home.r<10) bonus=6;
    if(w.def===WILD_DEFS.zapdos) bonus+=14;
    if(w.def===WILD_DEFS.drednaw) bonus+=8;
    targets.push({r:w.r,c:w.c,sc:bonus + w.ptsGive*2 - cheb(u,w)*0.8});
  }
  for(const f of pokeFoes){
    let bonus=(f.pts>=4?8:0)+(f.hp<f.maxHp*0.45?5:0);
    if(u.lane==='top'&&f.r<=4) bonus+=3;
    if(u.lane==='bot'&&f.r>=10) bonus+=3;
    targets.push({r:f.r,c:f.c,sc:bonus - cheb(u,f)*0.9});
  }
  // 自陣ゴールに敵が乗っていたら防衛
  for(const g of S.goals.filter(x=>x.team===u.team&&x.alive)){
    const o=unitAt(g.r,g.c);
    if(o&&isFoe(u,o)&&o.pts>0) targets.push({r:g.r,c:g.c,sc:20-cheb(u,g)*0.5});
  }
  if(!targets.length){
    const g = goals[0];
    if(g) targets.push({r:g.r,c:g.c,sc:1});
  }
  if(!targets.length) return {type:'wait'};
  targets.sort((a,b)=>b.sc-a.sc);
  const p=walkToward(u,{r:targets[0].r,c:targets[0].c},u.spd);
  if(p.r===u.r&&p.c===u.c) return {type:'wait'};
  return {type:'move',to:p};
}

/* ---------- turn resolution ---------- */
function resolveTurn(playerAct){
  if(S.over||busy) return;
  busy=true; hitCells=[];
  pushLog('th',`── ターン ${S.turn} ──`);

  const acts=new Map();
  acts.set(player(), playerAct);
  for(const u of S.units){ if(u===player()) continue; if(isAlive(u)) acts.set(u, aiAction(u)); }
  for(const w of S.wilds){
    if(!isAlive(w)) continue;
    const t=foesOf(w).filter(x=>cheb(w,x)<=w.rng).sort((a,b)=>a.hp-b.hp)[0];
    acts.set(w, t?{type:'attack',target:t}:{type:'wait'});
  }

  // ---- phase 1 : 行動（こうげき・わざ）
  const order=[...acts.keys()].sort((a,b)=>(b.spd-a.spd)||(a.uid<b.uid?-1:1));
  for(const u of order){
    if(!isAlive(u)) continue;
    const a=acts.get(u);
    if(!a||a.type==='move'||a.type==='wait'||a.type==='none') continue;
    execAction(u,a);
  }

  // ---- phase 2 : 移動
  for(const u of order){
    if(!isAlive(u)||u.stun>0) continue;
    const a=acts.get(u);
    if(!a||a.type!=='move'||!a.to) continue;
    const occ=unitAt(a.to.r,a.to.c);
    if(occ&&occ!==u){
      const p=walkToward(u,a.to,u.spd);
      u.r=p.r; u.c=p.c;
    }else{ u.r=a.to.r; u.c=a.to.c; }
  }

  // ---- phase 3 : シュート
  for(const u of S.units){
    if(!isAlive(u)||u.pts<=0) continue;
    const gs=openGoalsFor(u.team);
    const g=gs.find(x=>x.r===u.r&&x.c===u.c);
    if(!g) continue;
    const amt=Math.min(u.pts, g.cap-g.filled);
    if(amt<=0) continue;
    g.filled+=amt; u.pts-=amt; S.score[u.team]+=amt;
    pushLog('sc',`⭐ ${teamMark(u)}${u.name} がシュート成功！ ${amt}点（${laneName(g)}ゴール）`);
    if(g.filled>=g.cap){ g.alive=false; pushLog('sc',`🔥 ${g.team==='ally'?'味方':'敵'}の${laneName(g)}ゴールを破壊！`); }
  }

  // ---- phase 4 : 後処理
  for(const u of S.units){
    for(let i=0;i<2;i++) if(u.cd[i]>0) u.cd[i]--;
    if(u.shieldT>0){ u.shieldT--; if(u.shieldT===0) u.shield=0; }
    if(u.stun>0) u.stun--;
    if(u.stunNew){ u.stun=1; u.stunNew=0; }
    if(u.down>0){
      u.down--;
      if(u.down===0){ const p=freeSpawn(u.team); u.r=p.r; u.c=p.c; u.hp=u.maxHp; u.shield=0;
        pushLog(u.team==='ally'?'a':'e',`🔄 ${teamMark(u)}${u.name} が復帰した`); }
    }
  }
  for(const w of S.wilds){
    if(w.stun>0) w.stun--;
    if(w.stunNew){ w.stun=1; w.stunNew=0; }
    if(w.down>0){
      if(w.spawnTurn && w.hp<=0 && S.turn+1>=w.spawnTurn && w.down===999){
        const p=freeNear(w.home);
        w.down=0; w.hp=w.maxHp; w.r=p.r; w.c=p.c;
        pushLog('sc',`⚡ ${w.name} が中央に出現！（${w.ptsGive}点）`);
        continue;
      }
      if(w.down!==999){
        w.down--;
        if(w.down===0){
          const p=freeNear(w.home);
          w.hp=w.maxHp; w.r=p.r; w.c=p.c; w.pts=0;
        }
      }
    }
  }

  S.turn++;
  sel=null;
  checkEnd();
  render();
  busy=false;
}
function laneName(g){ return g.lane==='top'?'上':(g.lane==='bot'?'下':'中央'); }

function checkEnd(){
  const aDead=S.goals.filter(g=>g.team==='ally'&&!g.alive).length;
  const eDead=S.goals.filter(g=>g.team==='enemy'&&!g.alive).length;
  if(eDead>=5) return endGame('ally','相手ゴールを全破壊！');
  if(aDead>=5) return endGame('enemy','自陣ゴールが全破壊された…');
  if(S.turn>TURN_LIMIT){
    const w = S.score.ally>S.score.enemy?'ally':(S.score.enemy>S.score.ally?'enemy':'draw');
    return endGame(w,'制限ターン終了');
  }
}
function endGame(win,note){
  S.over=true;
  const big=document.getElementById('rBig');
  if(win==='ally'){ big.textContent='WIN!'; big.className='big win'; }
  else if(win==='enemy'){ big.textContent='LOSE...'; big.className='big lose'; }
  else { big.textContent='DRAW'; big.className='big draw'; }
  document.getElementById('rSc').innerHTML =
    `<span style="color:var(--ally)">${S.score.ally}</span> - <span style="color:var(--enemy)">${S.score.enemy}</span>`;
  document.getElementById('rNote').textContent=note;
  document.getElementById('ovResult').classList.remove('hide');
}

/* ---------- log ---------- */
function pushLog(cls,txt){ S.log.push({cls,txt}); if(S.log.length>260) S.log.splice(0,60); }

/* =========================================================
   TARGETING
   ========================================================= */
function validTargets(u,s){
  const out=new Map(); // key -> {r,c,target?}
  if(!s) return out;
  if(s.type==='move'){
    for(const [k,v] of reachable(u)){ out.set(k,{r:Math.floor(k/W),c:k%W}); }
    return out;
  }
  if(s.type==='attack'){
    foesOf(u).forEach(t=>{ if(cheb(u,t)<=u.rng) out.set(key(t.r,t.c),{r:t.r,c:t.c,target:t}); });
    return out;
  }
  const m=u.def.moves[s.idx];
  if(u.cd[s.idx]>0) return out;
  if(m.kind==='single'||m.kind==='dash'){
    foesOf(u).forEach(t=>{ if(cheb(u,t)<=m.range) out.set(key(t.r,t.c),{r:t.r,c:t.c,target:t}); });
  }else if(m.kind==='aoe'){
    for(let r=0;r<H;r++)for(let c=0;c<W;c++){
      if(!passable(r,c)) continue;
      if(cheb(u,{r,c})<=m.range) out.set(key(r,c),{r,c});
    }
  }else if(m.kind==='heal'){
    [u,...alliesOf(u)].forEach(t=>{ if(cheb(u,t)<=m.range) out.set(key(t.r,t.c),{r:t.r,c:t.c,target:t}); });
  }else if(m.kind==='shield'){
    out.set(key(u.r,u.c),{r:u.r,c:u.c});
  }
  return out;
}
function hlClass(u,s){
  if(s.type==='move') return 'hlMove';
  if(s.type==='attack') return 'hlAtk';
  const m=u.def.moves[s.idx];
  if(m.kind==='heal'||m.kind==='shield') return 'hlHeal';
  if(m.kind==='aoe') return 'hlArea';
  return 'hlAtk';
}

function onCellClick(r,c){
  if(S.over||busy||!sel) return;
  const u=player();
  if(!isAlive(u)) return;
  const v=validTargets(u,sel).get(key(r,c));
  if(!v) return;
  let act=null;
  if(sel.type==='move') act={type:'move',to:{r,c}};
  else if(sel.type==='attack') act={type:'attack',target:v.target};
  else {
    const m=u.def.moves[sel.idx];
    if(m.kind==='aoe') act={type:'skill',idx:sel.idx,at:{r,c}};
    else if(m.kind==='shield') act={type:'skill',idx:sel.idx};
    else act={type:'skill',idx:sel.idx,target:v.target};
  }
  resolveTurn(act);
}

function pickAct(s){
  if(S.over||busy) return;
  const u=player();
  if(!isAlive(u)) return;
  if(s.type==='skill'){
    const m=u.def.moves[s.idx];
    if(u.cd[s.idx]>0) return;
    if(m.kind==='shield'){ resolveTurn({type:'skill',idx:s.idx}); return; }
  }
  sel = (sel && sel.type===s.type && sel.idx===s.idx) ? null : s;
  render();
}

/* =========================================================
   RENDER
   ========================================================= */
const mapEl=document.getElementById('map');
let cellEls=[];
function buildMap(){
  mapEl.innerHTML='';
  cellEls=[];
  for(let r=0;r<H;r++)for(let c=0;c<W;c++){
    const d=document.createElement('div');
    d.className='cell';
    d.dataset.r=r; d.dataset.c=c;
    d.addEventListener('click',()=>onCellClick(r,c));
    d.addEventListener('mouseenter',()=>onHover(r,c));
    d.addEventListener('mouseleave',clearAoe);
    mapEl.appendChild(d); cellEls[key(r,c)]=d;
  }
}
function onHover(r,c){
  clearAoe();
  if(!sel||sel.type!=='skill'||S.over) return;
  const u=player(); const m=u.def.moves[sel.idx];
  if(m.kind!=='aoe') return;
  if(!validTargets(u,sel).has(key(r,c))) return;
  for(let rr=r-m.radius;rr<=r+m.radius;rr++)for(let cc=c-m.radius;cc<=c+m.radius;cc++){
    if(!inb(rr,cc)) continue;
    cellEls[key(rr,cc)].classList.add('aoe');
  }
}
function clearAoe(){ document.querySelectorAll('.cell.aoe').forEach(e=>e.classList.remove('aoe')); }

function terrainClass(r,c){
  const t=ch(r,c);
  if(t==='#') return 'wall';
  if(t==='~') return 'bush';
  if(c<=2 && r>=6 && r<=8) return 'baseA';
  if(c>=23 && r>=6 && r<=8) return 'baseE';
  if(r<=3||r>=11) return 'lane';
  if(c<=8) return 'zoneA';
  if(c>=17) return 'zoneE';
  return '';
}

function render(){
  const u=player();
  // ---- header
  document.getElementById('scA').textContent=S.score.ally;
  document.getElementById('scE').textContent=S.score.enemy;
  document.getElementById('turnNo').textContent=Math.min(S.turn,TURN_LIMIT);
  document.getElementById('phaseTxt').textContent = S.over?'試合終了':(isAlive(u)?'行動を選択':`気絶中 (復帰まで${u.down})`);

  const openA=openGoalsFor('enemy').map(g=>g.gid); // 敵が狙えるゴール = 味方ゴール
  const openE=openGoalsFor('ally').map(g=>g.gid);
  const mk=(team,openList)=>S.goals.filter(g=>g.team===team)
    .sort((a,b)=>a.tier-b.tier)
    .map(g=>`<span class="gpip ${!g.alive?'dead':(openList.includes(g.gid)?'open':'')}">${laneName(g)}${g.tier} ${g.alive?(g.cap-g.filled):'×'}</span>`).join('');
  document.getElementById('gA').innerHTML=mk('ally',openA);
  document.getElementById('gE').innerHTML=mk('enemy',openE);

  // ---- map
  const vt = (sel&&isAlive(u)&&!S.over) ? validTargets(u,sel) : new Map();
  const hc = sel?hlClass(u,sel):'';
  for(let r=0;r<H;r++)for(let c=0;c<W;c++){
    const el=cellEls[key(r,c)];
    let cls='cell '+terrainClass(r,c);
    if(vt.has(key(r,c))) cls+=' '+hc;
    if(hitCells.includes(key(r,c))) cls+=' hit';
    el.className=cls;
    el.innerHTML='';
  }
  // goals
  for(const g of S.goals){
    const el=cellEls[key(g.r,g.c)];
    const open = g.team==='ally'?openA.includes(g.gid):openE.includes(g.gid);
    const d=document.createElement('div');
    d.className='goal '+(g.team==='ally'?'a':'e')+(!g.alive?' dead':(open?'':' closed'));
    d.textContent = g.alive?(g.cap-g.filled):'×';
    d.title = `${g.team==='ally'?'味方':'敵'}${laneName(g)}ゴール / 残り耐久 ${g.alive?g.cap-g.filled:0}`;
    el.appendChild(d);
  }
  // actors
  for(const a of allActors()){
    if(a.hp<=0 && a.kind==='wild') continue;
    if(!isAlive(a)){
      if(a.kind==='poke'){ /* ダウン中はベースに表示しない */ }
      continue;
    }
    const el=cellEls[key(a.r,a.c)];
    const box=document.createElement('div');
    const tc = a.team==='ally'?'a':(a.team==='enemy'?'e':'w');
    box.className='u '+tc+(a.isPlayer?' me':'');
    box.innerHTML=`<span class="ring"></span><span class="em">${a.em}</span>`;
    const ratio=a.hp/a.maxHp;
    const hp=document.createElement('div');
    hp.className='hp '+(ratio<0.3?'s1':(ratio<0.6?'s2':''));
    hp.innerHTML=`<i style="width:${Math.max(0,ratio*100)}%"></i>`;
    box.appendChild(hp);
    if(a.shield>0){ const s=document.createElement('div'); s.className='sh'; box.appendChild(s); }
    if(a.pts>0){ const p=document.createElement('div'); p.className='pts'; p.textContent=a.pts; box.appendChild(p); }
    if(a.stun>0){ const s=document.createElement('div'); s.className='stun'; s.textContent='💫'; box.appendChild(s); }
    box.title=`${a.name} (${a.team==='ally'?'味方':a.team==='enemy'?'敵':'野生'})\nHP ${Math.max(0,a.hp)}/${a.maxHp}\n素早さ ${a.spd} / 射程 ${a.rng}${a.pts?`\n所持得点 ${a.pts}`:''}`;
    el.appendChild(box);
  }
  // downed markers at base
  for(const a of S.units){
    if(isAlive(a)) continue;
    const b=BASE[a.team];
    const el=cellEls[key(b.r,b.c)];
    if(!el.querySelector('.down')){
      const d=document.createElement('div'); d.className='down'; d.textContent='💤'; el.appendChild(d);
    }
  }
  hitCells=[];

  // ---- my card
  document.getElementById('meEm').textContent=u.em;
  document.getElementById('meNm').textContent=u.name;
  document.getElementById('meSt').innerHTML =
    `<span>${u.def.role}</span><span>⚔${u.atk}</span><span>🛡${u.dfs}</span><span>👟${u.spd}</span><span>🎯${u.rng}</span>`;
  const rr=Math.max(0,u.hp)/u.maxHp;
  const bar=document.getElementById('meBar');
  bar.className='bigbar '+(rr<0.3?'s1':(rr<0.6?'s2':''));
  bar.innerHTML=`<i style="width:${rr*100}%"></i>`;
  document.getElementById('meHp').textContent=`HP ${Math.max(0,u.hp)} / ${u.maxHp}${u.shield>0?` (+🛡${u.shield})`:''}`;
  document.getElementById('mePt').innerHTML=`所持得点 <b style="color:#ffd24c">${u.pts}</b>`;

  // ---- actions
  const A=document.getElementById('acts');
  A.innerHTML='';
  const dis = S.over || !isAlive(u) || u.stun>0;
  const addBtn=(label,desc,s,off,tag)=>{
    const b=document.createElement('button');
    b.className='act'+(sel&&sel.type===s.type&&sel.idx===s.idx?' sel':'')+(s.wide?' wide':'');
    b.disabled=dis||off;
    b.innerHTML=`<div class="t"><span>${label}</span>${tag?`<b>${tag}</b>`:''}</div><div class="d">${desc}</div>`;
    b.onclick=()=>pickAct(s);
    A.appendChild(b);
  };
  addBtn('移動',`${u.spd}マスまで進む`,{type:'move'},false,`👟${u.spd}`);
  addBtn('こうげき',`射程${u.rng} / 威力 ${calcDmg(u,{dfs:35},0)}目安`,{type:'attack'},false,`🎯${u.rng}`);
  u.def.moves.forEach((m,i)=>{
    const off=u.cd[i]>0;
    const tag = off?`CT ${u.cd[i]}`:`射程${m.range}${m.radius?`/範囲${m.radius}`:''}`;
    addBtn(`わざ${i+1}: ${m.name}`, m.desc+`（CT${m.cd}）`, {type:'skill',idx:i}, off, tag);
  });
  const w=document.createElement('button');
  w.className='act wide'; w.disabled=S.over||!isAlive(u);
  w.innerHTML=`<div class="t"><span>${u.stun>0?'行動不能（このターンはスキップ）':'待機してターンを進める'}</span></div>`;
  w.onclick=()=>{ if(!S.over&&!busy) resolveTurn({type:'wait'}); };
  A.appendChild(w);

  const hint=document.getElementById('hint');
  if(S.over) hint.textContent='';
  else if(!isAlive(u)) hint.textContent='気絶中です。「待機」でターンを進めましょう。';
  else if(u.stun>0) hint.textContent='行動不能です。「待機」でターンを進めましょう。';
  else if(sel){
    const t = sel.type==='move'?'移動先':(sel.type==='attack'?'攻撃する相手':
      (u.def.moves[sel.idx].kind==='aoe'?'着弾させる地点':
       u.def.moves[sel.idx].kind==='heal'?'回復する味方':'わざの対象'));
    hint.textContent=`▶ マップ上で${t}をクリック（もう一度ボタンで解除）`;
  } else hint.textContent='行動を1つ選んでください。';

  // ---- roster
  const rowHtml=(a)=>{
    const rt=Math.max(0,a.hp)/a.maxHp;
    return `<div class="rrow ${a.team==='ally'?'a':'e'}${isAlive(a)?'':' dead'}${a.isPlayer?' me':''}">
      <div class="e">${a.em}</div><div class="n">${a.name}</div>
      <div class="b"><i style="width:${rt*100}%"></i></div>
      <div class="p">${a.pts?'★'+a.pts:''}</div>
      <div class="s">${isAlive(a)?Math.max(0,a.hp):'💤'+a.down}</div></div>`;
  };
  document.getElementById('rosterA').innerHTML=S.units.filter(x=>x.team==='ally').map(rowHtml).join('');
  document.getElementById('rosterE').innerHTML=S.units.filter(x=>x.team==='enemy').map(rowHtml).join('');

  // ---- log
  const L=document.getElementById('log');
  L.innerHTML=S.log.slice(-120).map(l=>`<div class="${l.cls}">${l.txt}</div>`).join('');
  L.scrollTop=L.scrollHeight;
}

/* =========================================================
   SELECT SCREEN / BOOT
   ========================================================= */
function buildPicks(){
  const P=document.getElementById('picks');
  P.innerHTML='';
  POKEMON.forEach(p=>{
    const b=document.createElement('button');
    b.className='pick';
    b.innerHTML=`
      <div class="hd"><span class="em">${p.em}</span>
        <span><span class="nm">${p.name}</span><br><span class="ro">${p.role}</span></span></div>
      <div class="stats">
        <span>HP ${p.hp}</span><span>こうげき ${p.atk}</span>
        <span>ぼうぎょ ${p.def}</span><span>素早さ ${p.spd}</span>
        <span>射程 ${p.rng}</span><span></span>
      </div>
      <div class="mv">
        <em>わざ1</em> ${p.moves[0].name}（射程${p.moves[0].range} / CT${p.moves[0].cd}）<br>
        <em>わざ2</em> ${p.moves[1].name}（射程${p.moves[1].range} / CT${p.moves[1].cd}）
      </div>`;
    b.onclick=()=>startGame(p.id);
    P.appendChild(b);
  });
}
function fitMap(){
  const wrap=document.getElementById('mapWrap');
  const avail=wrap.clientWidth-16;
  const cs=Math.max(17,Math.min(42,Math.floor(avail/W)));
  document.documentElement.style.setProperty('--cs',cs+'px');
}
window.addEventListener('resize',fitMap);
buildMap(); buildPicks(); fitMap();
</script>
</body>
</html>
