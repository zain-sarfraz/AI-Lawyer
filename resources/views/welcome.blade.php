<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AI Lawyer — The AI-powered practice platform for lawyers and law firms</title>
<meta name="description" content="AI Lawyer answers your calls, books your appointments, organizes clients and cases, and researches case law across Pakistan, UK, Canada, Australia and USA.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Geist:wght@300;400;500;600;700&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{
  --ink:#0C0B09; --ink-2:#13110E; --ink-3:#1B1914; --ink-4:#25221B;
  --line:rgba(236,222,190,.10); --line-2:rgba(236,222,190,.18);
  --ivory:#F4EFE4; --ivory-2:#EAE3D3; --paper:#FBF8F2;
  --text:#EFE9DC; --muted:#A39B89; --dim:#6F685A;
  --gold:#D4B06A; --gold-2:#ECD39A; --gold-deep:#9A773A;
  --sage:#8FB3A0; --rose:#D98E7E;
  --serif:"Instrument Serif", Georgia, "Times New Roman", serif;
  --sans:"Geist", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
  --mono:"Geist Mono", ui-monospace, SFMono-Regular, Menlo, monospace;
  --r:14px; --r-lg:22px;
  --wrap:1200px;
  --ease:cubic-bezier(.2,.7,.2,1);
}
*,*::before,*::after{box-sizing:border-box}
html{scroll-behavior:smooth;scroll-padding-top:84px}
body{margin:0;background:var(--ink);color:var(--text);font-family:var(--sans);font-size:16px;line-height:1.6;-webkit-font-smoothing:antialiased;overflow-x:hidden}
img,svg{display:block;max-width:100%}
a{color:inherit;text-decoration:none}
button{font:inherit;color:inherit;cursor:pointer;background:none;border:0}
:focus-visible{outline:2px solid var(--gold);outline-offset:3px;border-radius:6px}
.wrap{width:min(var(--wrap),100% - 48px);margin-inline:auto}
.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0}
.skip{position:absolute;left:16px;top:-60px;background:var(--gold);color:var(--ink);padding:10px 16px;border-radius:10px;z-index:200;font-weight:600}
.skip:focus{top:16px}

/* ---------- type ---------- */
.eyebrow{display:inline-flex;align-items:center;gap:10px;font-family:var(--mono);font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:var(--gold)}
.eyebrow::before{content:"";width:22px;height:1px;background:currentColor;opacity:.7}
h1,h2,h3{margin:0;font-weight:500;letter-spacing:-.02em;line-height:1.05}
.display{font-family:var(--serif);font-weight:400;letter-spacing:-.015em}
.display em,.h2 em{font-style:italic;color:var(--gold-2)}
.h2{font-family:var(--serif);font-weight:400;font-size:clamp(2.4rem,4.6vw,4rem);line-height:1.02;letter-spacing:-.015em}
.lede{color:var(--muted);font-size:clamp(1rem,1.3vw,1.125rem);max-width:56ch}
.section{padding:clamp(88px,11vw,152px) 0;position:relative}
.section-head{display:grid;gap:18px;margin-bottom:clamp(44px,6vw,72px);max-width:760px}
.section-head.center{margin-inline:auto;text-align:center;justify-items:center}

/* ---------- buttons ---------- */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;height:48px;padding:0 22px;border-radius:999px;font-weight:500;font-size:15px;transition:transform .3s var(--ease),background .3s,box-shadow .3s,border-color .3s,color .3s;white-space:nowrap}
.btn svg{width:16px;height:16px;transition:transform .3s var(--ease)}
.btn:hover svg{transform:translateX(3px)}
.btn-gold{background:linear-gradient(180deg,var(--gold-2),var(--gold));color:#1a1408;box-shadow:0 1px 0 rgba(255,255,255,.4) inset,0 10px 30px -10px rgba(212,176,106,.6)}
.btn-gold:hover{transform:translateY(-2px);box-shadow:0 1px 0 rgba(255,255,255,.4) inset,0 18px 40px -12px rgba(212,176,106,.75)}
.btn-ghost{border:1px solid var(--line-2);color:var(--text)}
.btn-ghost:hover{border-color:var(--gold);color:var(--gold-2);transform:translateY(-2px)}
.btn-ink{background:var(--ink);color:var(--ivory)}
.btn-ink:hover{transform:translateY(-2px);background:#000}
.btn-sm{height:40px;padding:0 18px;font-size:14px}

/* ---------- nav ---------- */
.nav{position:fixed;inset:0 0 auto;z-index:100;transition:background .4s,border-color .4s,backdrop-filter .4s;border-bottom:1px solid transparent}
.nav.scrolled{background:rgba(12,11,9,.72);backdrop-filter:blur(18px) saturate(140%);-webkit-backdrop-filter:blur(18px) saturate(140%);border-color:var(--line)}
.nav-inner{display:flex;align-items:center;justify-content:space-between;height:72px;gap:24px}
.logo{display:inline-flex;align-items:center;gap:10px;font-weight:600;font-size:17px;letter-spacing:-.01em}
.logo-mark{width:32px;height:32px;border-radius:9px;background:linear-gradient(145deg,var(--gold-2),var(--gold-deep));display:grid;place-items:center;box-shadow:0 0 0 1px rgba(255,255,255,.15) inset}
.logo-mark svg{width:18px;height:18px;color:#1a1408}
.logo b{font-family:var(--serif);font-style:italic;font-weight:400;font-size:20px;color:var(--gold-2);margin-left:2px}
.nav-links{display:flex;gap:4px;list-style:none;margin:0;padding:0}
.nav-links a{display:block;padding:8px 14px;border-radius:999px;font-size:14px;color:var(--muted);transition:color .25s,background .25s}
.nav-links a:hover{color:var(--text);background:rgba(255,255,255,.04)}
.nav-cta{display:flex;align-items:center;gap:8px}
.nav-signin{font-size:14px;color:var(--muted);padding:8px 14px;transition:color .25s}
.nav-signin:hover{color:var(--text)}
.burger{display:none;width:42px;height:42px;border-radius:12px;border:1px solid var(--line-2);place-items:center}
.burger span{display:block;width:18px;height:1.5px;background:var(--text);position:relative;transition:background .3s}
.burger span::before,.burger span::after{content:"";position:absolute;left:0;width:18px;height:1.5px;background:var(--text);transition:transform .35s var(--ease)}
.burger span::before{top:-6px}.burger span::after{top:6px}
.nav.open .burger span{background:transparent}
.nav.open .burger span::before{transform:translateY(6px) rotate(45deg)}
.nav.open .burger span::after{transform:translateY(-6px) rotate(-45deg)}
.mobile-menu{display:none}

/* ---------- hero ---------- */
.hero{padding:clamp(130px,16vw,180px) 0 clamp(60px,8vw,110px);position:relative;overflow:hidden;isolation:isolate}
.hero::before{content:"";position:absolute;inset:-20% -10% auto;height:900px;background:
 radial-gradient(600px 420px at 50% 18%,rgba(212,176,106,.22),transparent 70%),
 radial-gradient(500px 360px at 85% 50%,rgba(143,179,160,.08),transparent 70%),
 radial-gradient(500px 360px at 10% 60%,rgba(154,119,58,.12),transparent 70%);z-index:-2}
.hero::after{content:"";position:absolute;inset:0;background-image:linear-gradient(var(--line) 1px,transparent 1px),linear-gradient(90deg,var(--line) 1px,transparent 1px);background-size:72px 72px;mask-image:radial-gradient(ellipse 70% 55% at 50% 30%,#000 30%,transparent 75%);-webkit-mask-image:radial-gradient(ellipse 70% 55% at 50% 30%,#000 30%,transparent 75%);opacity:.45;z-index:-1}
.hero-copy{text-align:center;display:grid;justify-items:center;gap:26px}
.pill{display:inline-flex;align-items:center;gap:10px;padding:6px 14px 6px 6px;border-radius:999px;border:1px solid var(--line-2);background:rgba(255,255,255,.03);font-size:13px;color:var(--muted)}
.pill .dot{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;background:rgba(143,179,160,.14);color:var(--sage);font-family:var(--mono);font-size:11px;letter-spacing:.06em;text-transform:uppercase}
.pill .dot::before{content:"";width:6px;height:6px;border-radius:50%;background:var(--sage);box-shadow:0 0 0 0 rgba(143,179,160,.6);animation:ping 2s infinite}
@keyframes ping{0%{box-shadow:0 0 0 0 rgba(143,179,160,.55)}80%,100%{box-shadow:0 0 0 8px rgba(143,179,160,0)}}
.hero h1{font-size:clamp(3rem,8.4vw,7.2rem);line-height:.95;max-width:13ch}
.hero h1 .sans{font-family:var(--sans);font-weight:500;letter-spacing:-.045em;font-size:.86em}
.hero .lede{text-align:center;font-size:clamp(1.05rem,1.5vw,1.25rem);max-width:52ch}
.hero-ctas{display:flex;gap:12px;flex-wrap:wrap;justify-content:center}
.hero-visual{margin-top:clamp(56px,7vw,88px);position:relative;perspective:1800px}
.hero-visual .app{transform:rotateX(14deg) scale(.96);transform-origin:50% 0;transition:transform 1.4s var(--ease)}
.hero-visual.in .app{transform:rotateX(0) scale(1)}
.hero-visual::after{content:"";position:absolute;left:10%;right:10%;bottom:-40px;height:120px;background:radial-gradient(closest-side,rgba(212,176,106,.25),transparent);filter:blur(30px);z-index:-1}
.float{position:absolute;z-index:3;background:rgba(27,25,20,.86);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border:1px solid var(--line-2);border-radius:16px;padding:14px 16px;box-shadow:0 30px 60px -20px rgba(0,0,0,.7);font-size:13px;animation:bob 6s ease-in-out infinite}
.float-a{left:-28px;top:34%;width:250px}
.float-b{right:-24px;top:12%;width:230px;animation-delay:-3s}
@keyframes bob{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
.float .k{font-family:var(--mono);font-size:10.5px;letter-spacing:.1em;text-transform:uppercase;color:var(--dim)}
.float .v{margin-top:6px;color:var(--text);line-height:1.45}
.float .row{display:flex;align-items:center;gap:10px}
.ic{width:30px;height:30px;border-radius:9px;display:grid;place-items:center;flex:none}
.ic svg{width:16px;height:16px}
.ic-gold{background:rgba(212,176,106,.14);color:var(--gold)}
.ic-sage{background:rgba(143,179,160,.14);color:var(--sage)}

/* ---------- app mockup ---------- */
.app{background:linear-gradient(180deg,#1A1813,#13110E);border:1px solid var(--line-2);border-radius:var(--r-lg);overflow:hidden;box-shadow:0 60px 120px -40px rgba(0,0,0,.85),0 0 0 1px rgba(255,255,255,.02) inset;font-size:13px;color:var(--text);text-align:left}
.app-bar{display:flex;align-items:center;gap:14px;height:42px;padding:0 16px;border-bottom:1px solid var(--line);background:rgba(0,0,0,.18)}
.dots{display:flex;gap:6px}.dots i{width:10px;height:10px;border-radius:50%;background:#3a352b}
.url{flex:1;max-width:340px;margin-inline:auto;height:24px;border-radius:7px;background:rgba(255,255,255,.04);display:flex;align-items:center;justify-content:center;gap:6px;font-family:var(--mono);font-size:11px;color:var(--dim)}
.url svg{width:11px;height:11px}
.app-body{display:grid;grid-template-columns:208px 1fr;min-height:520px}
.side{border-right:1px solid var(--line);padding:16px 12px;display:flex;flex-direction:column;gap:2px;background:rgba(0,0,0,.12)}
.ws{display:flex;align-items:center;gap:10px;padding:8px 10px 14px;margin-bottom:8px;border-bottom:1px solid var(--line)}
.ws-av{width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#3b3326,#201c15);border:1px solid var(--line-2);display:grid;place-items:center;font-family:var(--serif);font-size:15px;color:var(--gold-2)}
.ws small{display:block;color:var(--dim);font-size:11px;line-height:1.2}
.ws strong{display:block;font-weight:500;font-size:12.5px;line-height:1.3}
.nav-i{display:flex;align-items:center;gap:10px;padding:7px 10px;border-radius:8px;color:var(--muted);font-size:12.5px}
.nav-i svg{width:15px;height:15px;opacity:.8}
.nav-i.on{background:rgba(212,176,106,.1);color:var(--gold-2)}
.nav-i .badge{margin-left:auto;font-family:var(--mono);font-size:10px;padding:1px 6px;border-radius:6px;background:rgba(212,176,106,.16);color:var(--gold)}
.side .sep{height:1px;background:var(--line);margin:8px 6px}
.main{padding:22px 24px;display:grid;gap:18px;align-content:start}
.main-top{display:flex;align-items:flex-end;justify-content:space-between;gap:12px}
.main-top h4{margin:0;font-family:var(--serif);font-weight:400;font-size:26px;letter-spacing:-.01em;line-height:1.1}
.main-top p{margin:2px 0 0;color:var(--dim);font-size:12px}
.chip{display:inline-flex;align-items:center;gap:6px;height:28px;padding:0 11px;border-radius:8px;border:1px solid var(--line-2);color:var(--muted);font-size:12px;white-space:nowrap}
.chip.gold{background:var(--gold);color:#1a1408;border-color:transparent;font-weight:500}
.grid-2{display:grid;grid-template-columns:1.25fr 1fr;gap:16px}
.panel{border:1px solid var(--line);border-radius:12px;background:rgba(255,255,255,.015);padding:14px 16px}
.panel-h{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;font-size:12px;color:var(--muted)}
.panel-h b{color:var(--text);font-weight:500;font-size:12.5px}
.live{display:inline-flex;align-items:center;gap:6px;font-family:var(--mono);font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:var(--sage)}
.live::before{content:"";width:6px;height:6px;border-radius:50%;background:var(--sage);animation:ping 2s infinite}
.call{display:grid;gap:12px}
.call-who{display:flex;align-items:center;gap:12px}
.av{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;font-size:12px;font-weight:600;flex:none}
.av-1{background:#2d2a22;color:var(--gold-2)}.av-2{background:#22302a;color:var(--sage)}.av-3{background:#33241f;color:var(--rose)}.av-4{background:#262530;color:#b9b4d8}
.wave{display:flex;align-items:center;gap:3px;height:28px;margin-left:auto}
.wave i{width:3px;border-radius:2px;background:var(--gold);animation:wave 1.1s ease-in-out infinite;opacity:.85}
@keyframes wave{0%,100%{height:5px}50%{height:24px}}
.transcript{display:grid;gap:8px;font-size:12px}
.bubble{padding:8px 11px;border-radius:10px;max-width:88%;line-height:1.45}
.bubble.ai{background:rgba(212,176,106,.1);color:var(--ivory-2);border:1px solid rgba(212,176,106,.16)}
.bubble.caller{background:rgba(255,255,255,.04);color:var(--muted);justify-self:end}
.booked{display:flex;align-items:center;gap:10px;padding:9px 11px;border-radius:10px;background:rgba(143,179,160,.08);border:1px solid rgba(143,179,160,.2);color:var(--sage);font-size:12px}
.booked svg{width:15px;height:15px;flex:none}
.sched{display:grid;gap:4px}
.slot{display:grid;grid-template-columns:48px 3px 1fr auto;gap:12px;align-items:center;padding:9px 6px;border-radius:9px;transition:background .25s}
.slot:hover{background:rgba(255,255,255,.03)}
.slot time{font-family:var(--mono);font-size:11px;color:var(--dim)}
.slot .bar{height:30px;border-radius:2px;background:var(--gold)}
.slot .bar.s{background:var(--sage)}.slot .bar.r{background:var(--rose)}.slot .bar.d{background:#5b5547}
.slot b{display:block;font-weight:500;font-size:12.5px}
.slot small{color:var(--dim);font-size:11px}
.tag{font-family:var(--mono);font-size:10px;letter-spacing:.04em;padding:3px 8px;border-radius:6px;border:1px solid var(--line-2);color:var(--muted);white-space:nowrap}
.tag.g{color:var(--gold);border-color:rgba(212,176,106,.3)}
.tag.s{color:var(--sage);border-color:rgba(143,179,160,.3)}
.tag.r{color:var(--rose);border-color:rgba(217,142,126,.3)}
.cases-row{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.case-mini{padding:12px 14px;border:1px solid var(--line);border-radius:11px;display:grid;gap:8px}
.case-mini .t{font-weight:500;font-size:12.5px;line-height:1.35}
.case-mini .m{display:flex;justify-content:space-between;align-items:center;color:var(--dim);font-size:11px}
.prog{height:3px;background:rgba(255,255,255,.06);border-radius:2px;overflow:hidden}
.prog i{display:block;height:100%;background:linear-gradient(90deg,var(--gold-deep),var(--gold-2))}

/* ---------- features ---------- */
.features-grid{display:grid;grid-template-columns:repeat(3,1fr);border-top:1px solid var(--line);border-left:1px solid var(--line)}
.feature{padding:clamp(28px,3.4vw,44px);border-right:1px solid var(--line);border-bottom:1px solid var(--line);position:relative;overflow:hidden;transition:background .5s}
.feature::before{content:"";position:absolute;inset:0;background:radial-gradient(400px 220px at var(--mx,50%) var(--my,0%),rgba(212,176,106,.10),transparent 70%);opacity:0;transition:opacity .4s}
.feature:hover::before{opacity:1}
.feature .num{font-family:var(--mono);font-size:11px;color:var(--dim);letter-spacing:.1em}
.f-icon{width:46px;height:46px;border-radius:13px;border:1px solid var(--line-2);display:grid;place-items:center;color:var(--gold);margin:22px 0 26px;background:linear-gradient(180deg,rgba(212,176,106,.08),transparent);transition:transform .5s var(--ease),border-color .4s}
.f-icon svg{width:22px;height:22px}
.feature:hover .f-icon{transform:translateY(-4px) rotate(-4deg);border-color:rgba(212,176,106,.5)}
.feature h3{font-size:21px;font-weight:500;letter-spacing:-.02em;margin-bottom:10px;position:relative}
.feature p{margin:0;color:var(--muted);font-size:15px;position:relative;max-width:34ch}

/* ---------- light sections ---------- */
.light{background:var(--ivory);color:#1a1712}
.light .lede{color:#5f5848}
.light .eyebrow{color:var(--gold-deep)}
.light .h2 em{color:var(--gold-deep)}

/* ---------- video demo player ---------- */
.vplayer-wrap {
  max-width: 980px;
  margin: 0 auto;
  border-radius: 20px;
  background: radial-gradient(800px 400px at 50% 0%, rgba(212,176,106,.12), transparent 70%), var(--ink-2);
  border: 1px solid var(--line-2);
  box-shadow: 0 40px 100px -20px rgba(0,0,0,.7), 0 0 0 1px rgba(255,255,255,.05) inset;
  overflow: hidden;
  position: relative;
}
.vplayer-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 20px;
  border-bottom: 1px solid var(--line);
  background: rgba(12,11,9,.6);
  backdrop-filter: blur(10px);
}
.vplayer-dots {
  display: flex;
  align-items: center;
  gap: 7px;
}
.vplayer-dot {
  width: 11px;
  height: 11px;
  border-radius: 50%;
}
.vplayer-title-badge {
  font-family: var(--mono);
  font-size: 11.5px;
  letter-spacing: .08em;
  color: var(--muted);
  text-transform: uppercase;
  display: flex;
  align-items: center;
  gap: 8px;
}
.vplayer-live-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #10B981;
  box-shadow: 0 0 8px #10B981;
  animation: vpulse 1.8s infinite;
}
@keyframes vpulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: .4; transform: scale(.85); }
}
.vplayer-stage {
  position: relative;
  min-height: 480px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: clamp(24px, 4vw, 44px);
  overflow: hidden;
}
.vscene {
  display: none;
  animation: vsceneIn .45s var(--ease) both;
}
.vscene.active {
  display: block;
}
@keyframes vsceneIn {
  from { opacity: 0; transform: translateY(10px) scale(.99); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
.vcontrols {
  padding: 16px 20px 20px;
  border-top: 1px solid var(--line);
  background: rgba(12,11,9,.8);
  display: grid;
  gap: 12px;
}
.vscrubber {
  height: 6px;
  border-radius: 999px;
  background: rgba(255,255,255,.1);
  cursor: pointer;
  position: relative;
  overflow: hidden;
}
.vscrubber-bar {
  height: 100%;
  width: 0%;
  background: linear-gradient(90deg, var(--gold-deep), var(--gold-2));
  border-radius: 999px;
  transition: width .15s linear;
}
.vcontrols-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}
.vplay-group {
  display: flex;
  align-items: center;
  gap: 14px;
}
.vplay-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--gold);
  color: #1a1408;
  display: grid;
  place-items: center;
  transition: transform .2s var(--ease), background .2s;
  cursor: pointer;
}
.vplay-btn:hover {
  transform: scale(1.08);
  background: var(--gold-2);
}
.vtime {
  font-family: var(--mono);
  font-size: 12px;
  color: var(--muted);
}
.vchapters {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
.vchap-btn {
  font-family: var(--mono);
  font-size: 11px;
  letter-spacing: .04em;
  padding: 4px 10px;
  border-radius: 6px;
  background: rgba(255,255,255,.04);
  color: var(--muted);
  border: 1px solid transparent;
  transition: all .2s;
  cursor: pointer;
}
.vchap-btn:hover {
  color: var(--text);
  border-color: var(--line-2);
}
.vchap-btn.active {
  background: rgba(212,176,106,.14);
  color: var(--gold-2);
  border-color: rgba(212,176,106,.4);
}

/* ---------- how ---------- */
.steps{display:grid;grid-template-columns:repeat(4,1fr);gap:0;position:relative;counter-reset:step}
.steps::before{content:"";position:absolute;top:27px;left:28px;right:28px;height:1px;background:linear-gradient(90deg,var(--gold-deep),rgba(154,119,58,.15))}
.step{padding-right:28px;position:relative}
.step-n{width:56px;height:56px;border-radius:50%;background:var(--paper);border:1px solid rgba(154,119,58,.35);display:grid;place-items:center;font-family:var(--serif);font-size:26px;color:var(--gold-deep);position:relative;z-index:1;margin-bottom:28px;transition:background .4s,color .4s,transform .5s var(--ease)}
.step:hover .step-n{background:#1a1712;color:var(--gold-2);transform:scale(1.06)}
.step h3{font-size:20px;font-weight:500;letter-spacing:-.02em;margin-bottom:10px;color:#1a1712}
.step p{margin:0;color:#6a6252;font-size:15px}

/* ---------- join ---------- */
.join{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.join-card{position:relative;padding:clamp(32px,4vw,52px);border-radius:var(--r-lg);border:1px solid var(--line-2);overflow:hidden;display:grid;gap:22px;align-content:start;transition:transform .5s var(--ease),border-color .4s;background:linear-gradient(180deg,rgba(255,255,255,.025),transparent 60%)}
.join-card:hover{transform:translateY(-6px);border-color:rgba(212,176,106,.45)}
.join-card.firm{background:radial-gradient(500px 300px at 100% 0%,rgba(212,176,106,.16),transparent 70%),linear-gradient(180deg,#1c1913,#13110e)}
.join-card .kicker{font-family:var(--mono);font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:var(--dim)}
.join-card h3{font-family:var(--serif);font-weight:400;font-size:clamp(2.2rem,3.6vw,3rem);letter-spacing:-.01em}
.join-card > p{margin:0;color:var(--muted);max-width:40ch}
.join-vis{height:120px;display:flex;align-items:center;gap:0}
.seat{width:52px;height:52px;border-radius:50%;border:2px solid var(--ink-2);display:grid;place-items:center;font-weight:600;font-size:14px;margin-left:-12px;transition:transform .4s var(--ease)}
.seat:first-child{margin-left:0}
.join-card:hover .seat{transform:translateY(-4px)}
.join-card:hover .seat:nth-child(2){transition-delay:.05s}.join-card:hover .seat:nth-child(3){transition-delay:.1s}.join-card:hover .seat:nth-child(4){transition-delay:.15s}
.seat.solo{width:72px;height:72px;font-family:var(--serif);font-size:30px;font-weight:400;background:linear-gradient(145deg,#2f2a20,#1a1712);color:var(--gold-2);border:1px solid var(--line-2)}
.join-vis .ws-line{flex:1;height:1px;background:linear-gradient(90deg,var(--line-2),transparent);margin-left:18px}
.list{list-style:none;margin:0;padding:0;display:grid;gap:10px}
.list li{display:flex;gap:12px;align-items:flex-start;font-size:15px;color:var(--ivory-2)}
.list li svg{width:18px;height:18px;flex:none;margin-top:3px;color:var(--gold)}

/* ---------- research ---------- */
.research{display:grid;grid-template-columns:.85fr 1.15fr;gap:clamp(40px,6vw,88px);align-items:center}
.research .section-head{margin-bottom:28px}
.juris-list{display:grid;gap:0;margin-top:8px;border-top:1px solid rgba(26,23,18,.12)}
.juris-list li{list-style:none;display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid rgba(26,23,18,.12);font-size:15px}
.juris-list li span{font-family:var(--mono);font-size:11px;color:#8a8170;letter-spacing:.08em}
.juris-list li b{font-weight:500}
.r-app{background:var(--paper);border:1px solid rgba(26,23,18,.1);border-radius:var(--r-lg);box-shadow:0 50px 100px -40px rgba(60,45,20,.35),0 2px 0 rgba(255,255,255,.8) inset;padding:22px;color:#1a1712}
.search{display:flex;align-items:center;gap:12px;height:52px;border-radius:13px;border:1px solid rgba(26,23,18,.14);background:#fff;padding:0 8px 0 16px;font-size:14px}
.search svg{width:18px;height:18px;color:#8a8170;flex:none}
.search .q{flex:1;color:#1a1712;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
.search .q::after{content:"";display:inline-block;width:1.5px;height:16px;background:var(--gold-deep);vertical-align:-3px;margin-left:2px;animation:blink 1s steps(1) infinite}
@keyframes blink{50%{opacity:0}}
.search .go{height:36px;padding:0 14px;border-radius:9px;background:#1a1712;color:var(--gold-2);font-size:13px;font-weight:500;display:flex;align-items:center}
.filters{display:flex;flex-wrap:wrap;gap:8px;margin:16px 0 18px}
.flt{height:34px;padding:0 14px;border-radius:999px;border:1px solid rgba(26,23,18,.14);font-size:13px;color:#5f5848;display:inline-flex;align-items:center;gap:8px;transition:all .25s;background:#fff}
.flt:hover{border-color:var(--gold-deep);color:#1a1712}
.flt[aria-pressed="true"]{background:#1a1712;color:var(--gold-2);border-color:#1a1712}
.flt .cc{font-family:var(--mono);font-size:10.5px;opacity:.7}
.results{display:grid;gap:10px;min-height:340px;align-content:start}
.res{padding:16px 18px;border-radius:13px;border:1px solid rgba(26,23,18,.1);background:#fff;display:grid;gap:8px;transition:transform .35s var(--ease),box-shadow .35s,opacity .35s;animation:resIn .45s var(--ease) both}
.res:hover{transform:translateY(-2px);box-shadow:0 14px 30px -18px rgba(60,45,20,.4)}
@keyframes resIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
.res-top{display:flex;align-items:center;justify-content:space-between;gap:12px}
.res-j{display:inline-flex;align-items:center;gap:7px;font-family:var(--mono);font-size:10.5px;letter-spacing:.08em;text-transform:uppercase;color:var(--gold-deep)}
.res-j i{width:6px;height:6px;border-radius:50%;background:var(--gold-deep)}
.res h4{margin:0;font-family:var(--serif);font-weight:400;font-size:21px;line-height:1.2;letter-spacing:-.005em}
.res .cite{font-family:var(--mono);font-size:12px;color:#8a8170}
.res p{margin:0;font-size:13.5px;color:#5f5848;line-height:1.5}
.src{display:inline-flex;align-items:center;gap:6px;font-size:12.5px;font-weight:500;color:#1a1712;border-bottom:1px solid rgba(154,119,58,.5);padding-bottom:1px;white-space:nowrap;transition:color .2s,border-color .2s}
.src:hover{color:var(--gold-deep);border-color:var(--gold-deep)}
.src svg{width:12px;height:12px}

/* ---------- preview ---------- */
.tabs{display:inline-flex;gap:4px;padding:5px;border-radius:999px;border:1px solid var(--line-2);background:rgba(255,255,255,.02);margin:0 auto 40px;position:relative}
.tab{position:relative;z-index:1;height:42px;padding:0 20px;border-radius:999px;font-size:14px;color:var(--muted);transition:color .3s}
.tab[aria-selected="true"]{color:#1a1408}
.tab-ind{position:absolute;top:5px;bottom:5px;left:5px;border-radius:999px;background:linear-gradient(180deg,var(--gold-2),var(--gold));transition:transform .5s var(--ease),width .5s var(--ease)}
.tabs-wrap{text-align:center}
.panels{position:relative}
.tpanel{animation:panelIn .6s var(--ease) both}
.tpanel[hidden]{display:none}
@keyframes panelIn{from{opacity:0;transform:translateY(14px) scale(.99)}to{opacity:1;transform:none}}
.cal{display:grid;grid-template-columns:44px repeat(5,1fr);border:1px solid var(--line);border-radius:12px;overflow:hidden}
.cal .hd{padding:10px;font-size:11.5px;color:var(--muted);border-bottom:1px solid var(--line);border-left:1px solid var(--line);display:flex;justify-content:space-between}
.cal .hd b{font-weight:500;color:var(--text)}
.cal .hd.today b{color:var(--gold-2)}
.cal .tm{font-family:var(--mono);font-size:10px;color:var(--dim);padding:6px 6px 0;height:52px;border-bottom:1px solid var(--line)}
.cal .cell{height:52px;border-left:1px solid var(--line);border-bottom:1px solid var(--line);position:relative}
.ev{position:absolute;left:4px;right:4px;top:4px;border-radius:7px;padding:5px 8px;font-size:11px;line-height:1.3;overflow:hidden;border-left:2px solid}
.ev b{display:block;font-weight:500}
.ev small{opacity:.75;font-size:10px}
.ev.g{background:rgba(212,176,106,.13);border-color:var(--gold);color:var(--gold-2)}
.ev.s{background:rgba(143,179,160,.12);border-color:var(--sage);color:#bcd6c8}
.ev.r{background:rgba(217,142,126,.12);border-color:var(--rose);color:#efc0b5}
.ev.h2x{height:96px}
.split{display:grid;grid-template-columns:1fr 280px;gap:16px}
.tbl{width:100%;border-collapse:collapse;font-size:12.5px}
.tbl th{font-weight:400;text-align:left;font-size:11px;color:var(--dim);padding:8px 10px;border-bottom:1px solid var(--line);font-family:var(--mono);letter-spacing:.04em}
.tbl td{padding:11px 10px;border-bottom:1px solid var(--line);vertical-align:middle}
.tbl tr:hover td{background:rgba(255,255,255,.02)}
.tbl .who{display:flex;align-items:center;gap:10px}
.tbl .who .av{width:26px;height:26px;font-size:10px}
.doc{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:9px;border:1px solid var(--line);font-size:12px}
.doc svg{width:15px;height:15px;color:var(--gold);flex:none}
.doc small{margin-left:auto;color:var(--dim);font-size:10.5px;font-family:var(--mono)}
.kv{display:grid;grid-template-columns:auto 1fr;gap:6px 14px;font-size:12px}
.kv dt{color:var(--dim)}.kv dd{margin:0;text-align:right}
.case-doc{display:grid;grid-template-columns:1fr 260px;gap:16px}
.case-doc h5{margin:0 0 4px;font-family:var(--serif);font-weight:400;font-size:24px;letter-spacing:-.01em}
.hl{background:linear-gradient(transparent 60%,rgba(212,176,106,.28) 60%)}
.para{color:var(--muted);font-size:12.5px;line-height:1.65;margin:0 0 10px}
.prev-caption{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:36px}
.prev-caption div{border-top:1px solid var(--line);padding-top:16px;color:var(--muted);font-size:14px;transition:border-color .3s,color .3s}
.prev-caption div b{display:block;color:var(--text);font-weight:500;margin-bottom:4px;font-size:15px}
.prev-caption div.on{border-color:var(--gold);color:var(--ivory-2)}

/* ---------- pricing ---------- */
.toggle{display:inline-flex;align-items:center;gap:14px;font-size:14px;color:#6a6252;margin-top:8px}
.toggle .lbl.on{color:#1a1712;font-weight:500}
.switch{width:56px;height:30px;border-radius:999px;background:#1a1712;position:relative;transition:background .3s}
.switch::after{content:"";position:absolute;top:4px;left:4px;width:22px;height:22px;border-radius:50%;background:linear-gradient(180deg,var(--gold-2),var(--gold));transition:transform .4s var(--ease)}
.switch[aria-checked="true"]::after{transform:translateX(26px)}
.save{font-family:var(--mono);font-size:11px;padding:4px 9px;border-radius:999px;background:rgba(154,119,58,.14);color:var(--gold-deep);letter-spacing:.04em}
.plans{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;align-items:stretch}
.plan{background:var(--paper);border:1px solid rgba(26,23,18,.1);border-radius:var(--r-lg);padding:clamp(28px,3vw,38px);display:flex;flex-direction:column;gap:22px;position:relative;transition:transform .5s var(--ease),box-shadow .5s}
.plan:hover{transform:translateY(-6px);box-shadow:0 40px 80px -40px rgba(60,45,20,.35)}
.plan.pop{background:#14120e;color:var(--text);border-color:#14120e;box-shadow:0 40px 90px -40px rgba(20,15,5,.7)}
.plan.pop::before{content:"";position:absolute;inset:0;border-radius:inherit;background:radial-gradient(420px 220px at 50% 0%,rgba(212,176,106,.22),transparent 70%);pointer-events:none}
.badge-pop{position:absolute;top:-13px;left:50%;transform:translateX(-50%);height:26px;padding:0 12px;border-radius:999px;background:linear-gradient(180deg,var(--gold-2),var(--gold));color:#1a1408;font-size:12px;font-weight:600;display:inline-flex;align-items:center;white-space:nowrap}
.plan-name{display:flex;align-items:center;justify-content:space-between;gap:10px}
.plan-name h3{font-size:20px;font-weight:500;letter-spacing:-.02em}
.plan-name .tag{border-color:rgba(26,23,18,.18);color:#6a6252}
.plan.pop .plan-name .tag{border-color:var(--line-2);color:var(--gold)}
.plan .for{margin:-12px 0 0;font-size:14px;color:#6a6252}
.plan.pop .for{color:var(--muted)}
.price{display:flex;align-items:baseline;gap:6px;min-height:64px}
.price .amt{font-family:var(--serif);font-size:58px;line-height:1;letter-spacing:-.02em;transition:opacity .25s,transform .25s}
.price .amt.swap{opacity:0;transform:translateY(6px)}
.price .per{font-size:14px;color:#8a8170}
.plan.pop .price .per{color:var(--muted)}
.price-note{margin:-14px 0 0;font-size:12.5px;color:#8a8170;min-height:20px;font-family:var(--mono)}
.plan.pop .price-note{color:var(--dim)}
.plan .list{flex:1;border-top:1px solid rgba(26,23,18,.1);padding-top:22px}
.plan.pop .list{border-color:var(--line)}
.plan .list li{color:#3b3529;font-size:14.5px}
.plan .list li svg{color:var(--gold-deep)}
.plan.pop .list li{color:var(--ivory-2)}
.plan.pop .list li svg{color:var(--gold)}
.plan .btn{width:100%}
.plan .btn-outline{border:1px solid rgba(26,23,18,.2);color:#1a1712}
.plan .btn-outline:hover{border-color:#1a1712;background:#1a1712;color:var(--gold-2);transform:translateY(-2px)}
.account-type-tag{display:inline-flex;align-items:center;padding:4px 9px;border-radius:6px;font-size:11.5px;font-weight:700;letter-spacing:.03em;text-transform:uppercase;margin-top:-6px;align-self:flex-start}
.account-type-tag.individual{background:rgba(99,91,255,.09);color:#4f46e5;border:1px solid rgba(99,91,255,.2)}
.account-type-tag.firm{background:rgba(212,175,55,.12);color:#926815;border:1px solid rgba(212,175,55,.28)}
.plan.pop .account-type-tag.firm{background:rgba(212,176,106,.15);color:var(--gold);border-color:rgba(212,176,106,.3)}
.sales-modal-backdrop{position:fixed;inset:0;background:rgba(10,12,16,.75);backdrop-filter:blur(4px);display:none;place-items:center;z-index:9999;padding:20px}
.sales-modal-backdrop.open{display:grid}
.sales-modal{background:#ffffff;border:1px solid #e2e8f0;border-radius:18px;max-width:480px;width:100%;padding:28px 24px;box-shadow:0 24px 60px rgba(0,0,0,.3);position:relative;color:#0a2540}
.sales-modal-close{position:absolute;top:18px;right:18px;background:none;border:none;font-size:22px;line-height:1;color:#64748b;cursor:pointer;padding:4px}
.sales-modal h3{margin:0 0 6px;font-size:20px;font-weight:700}
.sales-modal p{margin:0 0 18px;color:#64748b;font-size:13.5px}
.sales-modal .form-field{margin-bottom:14px}
.sales-modal label{display:block;font-size:12.5px;font-weight:600;margin-bottom:5px;color:#334155}
.sales-modal input,.sales-modal textarea{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:10px 12px;font:inherit;font-size:14px;color:#0a2540;background:#fff;box-sizing:border-box}
.sales-modal input:focus,.sales-modal textarea:focus{outline:none;border-color:#635bff;box-shadow:0 0 0 3px rgba(99,91,255,.15)}
.sales-modal .btn-submit{width:100%;background:#0a2540;color:#fff;border:0;border-radius:10px;padding:12px;font-weight:700;cursor:pointer;margin-top:6px}
.sales-modal .btn-submit:hover{background:#1e293b}

/* ---------- security ---------- */
.sec{display:grid;grid-template-columns:1fr 1.3fr;gap:clamp(40px,6vw,96px);align-items:center}
.sec-items{display:grid;gap:0;border-top:1px solid var(--line)}
.sec-item{display:grid;grid-template-columns:56px 1fr;gap:20px;padding:26px 0;border-bottom:1px solid var(--line);align-items:start}
.sec-item .f-icon{margin:0}
.sec-item h3{font-size:19px;font-weight:500;letter-spacing:-.02em;margin-bottom:6px}
.sec-item p{margin:0;color:var(--muted);font-size:15px}
.vault{position:relative;aspect-ratio:1;max-width:440px;width:100%;justify-self:center}
.ring{position:absolute;inset:0;border-radius:50%;border:1px solid var(--line-2)}
.ring.r2{inset:13%;border-color:rgba(212,176,106,.22);border-style:dashed;animation:spin 40s linear infinite}
.ring.r3{inset:27%;border-color:rgba(212,176,106,.35)}
.ring.r1{animation:spin 80s linear infinite reverse}
.ring.r1::before,.ring.r2::before{content:"";position:absolute;width:10px;height:10px;border-radius:50%;background:var(--gold);top:-5px;left:calc(50% - 5px);box-shadow:0 0 18px var(--gold)}
@keyframes spin{to{transform:rotate(360deg)}}
.core{position:absolute;inset:38%;border-radius:28%;background:linear-gradient(145deg,#2c271d,#14120e);border:1px solid rgba(212,176,106,.4);display:grid;place-items:center;box-shadow:0 0 80px rgba(212,176,106,.2)}
.core svg{width:42%;height:42%;color:var(--gold-2)}
.orb{position:absolute;padding:6px 11px;border-radius:999px;background:rgba(27,25,20,.9);border:1px solid var(--line-2);font-family:var(--mono);font-size:10.5px;color:var(--muted);letter-spacing:.04em;white-space:nowrap}

/* ---------- faq ---------- */
.faq{display:grid;grid-template-columns:.8fr 1.2fr;gap:clamp(40px,6vw,96px);align-items:start}
.faq-list{border-top:1px solid var(--line)}
.faq details{border-bottom:1px solid var(--line)}
.faq summary{list-style:none;display:flex;align-items:center;justify-content:space-between;gap:24px;padding:24px 0;cursor:pointer;font-size:18px;font-weight:500;letter-spacing:-.015em;transition:color .25s}
.faq summary::-webkit-details-marker{display:none}
.faq summary:hover{color:var(--gold-2)}
.plus{width:30px;height:30px;border-radius:50%;border:1px solid var(--line-2);flex:none;position:relative;transition:transform .4s var(--ease),background .3s,border-color .3s}
.plus::before,.plus::after{content:"";position:absolute;left:50%;top:50%;width:11px;height:1.5px;background:currentColor;transform:translate(-50%,-50%)}
.plus::after{transform:translate(-50%,-50%) rotate(90deg);transition:transform .4s var(--ease)}
.faq details[open] .plus{background:var(--gold);border-color:var(--gold);color:#1a1408}
.faq details[open] .plus::after{transform:translate(-50%,-50%) rotate(0)}
.faq .ans{overflow:hidden}
.faq .ans p{margin:0 0 24px;color:var(--muted);max-width:60ch}

/* ---------- cta ---------- */
.cta{position:relative;overflow:hidden;border-radius:32px;padding:clamp(64px,9vw,120px) clamp(24px,5vw,72px);text-align:center;background:radial-gradient(700px 380px at 50% 110%,rgba(212,176,106,.35),transparent 70%),linear-gradient(180deg,#1a1712,#100e0b);border:1px solid var(--line-2);isolation:isolate}
.cta::before{content:"";position:absolute;inset:0;background-image:linear-gradient(var(--line) 1px,transparent 1px),linear-gradient(90deg,var(--line) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(ellipse 60% 70% at 50% 100%,#000,transparent 70%);-webkit-mask-image:radial-gradient(ellipse 60% 70% at 50% 100%,#000,transparent 70%);z-index:-1}
.cta h2{font-family:var(--serif);font-weight:400;font-size:clamp(2.6rem,6vw,5.2rem);line-height:1;letter-spacing:-.015em;max-width:16ch;margin:0 auto 22px}
.cta h2 em{color:var(--gold-2)}
.cta p{color:var(--muted);margin:0 auto 36px;max-width:46ch}

/* ---------- footer ---------- */
footer{padding:72px 0 36px;border-top:1px solid var(--line);margin-top:clamp(88px,11vw,152px)}
.foot{display:grid;grid-template-columns:1.4fr repeat(3,1fr);gap:40px}
.foot p{color:var(--muted);font-size:14px;max-width:32ch;margin:16px 0 0}
.foot h4{margin:0 0 16px;font-family:var(--mono);font-weight:400;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:var(--dim)}
.foot ul{list-style:none;margin:0;padding:0;display:grid;gap:10px}
.foot ul a{color:var(--muted);font-size:14px;transition:color .2s}
.foot ul a:hover{color:var(--gold-2)}
.foot-bottom{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-top:56px;padding-top:24px;border-top:1px solid var(--line);color:var(--dim);font-size:13px}
.wordmark{font-family:var(--serif);font-size:clamp(5rem,19vw,17rem);line-height:.8;letter-spacing:-.03em;text-align:center;margin-top:48px;background:linear-gradient(180deg,rgba(236,211,154,.22),rgba(236,211,154,0) 85%);-webkit-background-clip:text;background-clip:text;color:transparent;user-select:none;white-space:nowrap}

/* ---------- reveal ---------- */
.reveal{opacity:0;transform:translateY(28px);transition:opacity 1s var(--ease),transform 1s var(--ease)}
.reveal.in{opacity:1;transform:none}
.d1{transition-delay:.08s}.d2{transition-delay:.16s}.d3{transition-delay:.24s}.d4{transition-delay:.32s}.d5{transition-delay:.4s}

/* ---------- responsive ---------- */
@media (max-width:1080px){
  .nav-links,.nav-cta .nav-signin,.nav-cta .btn{display:none}
  .burger{display:grid}
  .mobile-menu{display:block;position:fixed;inset:72px 0 auto;background:rgba(12,11,9,.97);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-bottom:1px solid var(--line);padding:12px 24px 28px;transform:translateY(-12px);opacity:0;visibility:hidden;transition:all .35s var(--ease)}
  .nav.open .mobile-menu{transform:none;opacity:1;visibility:visible}
  .mobile-menu ul{list-style:none;margin:0;padding:0}
  .mobile-menu li a{display:block;padding:16px 0;border-bottom:1px solid var(--line);font-size:18px}
  .mobile-menu .mm-cta{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:20px}
  .float-a{left:-10px}.float-b{right:-10px}
  .research,.sec,.faq{grid-template-columns:minmax(0,1fr)}
  .research > *,.sec > *,.faq > *{min-width:0}
  .vault{max-width:340px}
}
@media (max-width:900px){
  .features-grid{grid-template-columns:repeat(2,1fr)}
  .steps{grid-template-columns:repeat(2,1fr);gap:44px 0}
  .steps::before{display:none}
  .join,.plans{grid-template-columns:1fr}
  .plans{max-width:520px;margin-inline:auto;gap:28px}
  .app-body{grid-template-columns:1fr}
  .side{display:none}
  .split,.case-doc{grid-template-columns:1fr}
  .foot{grid-template-columns:1fr 1fr}
  .foot > div:first-child{grid-column:1/-1}
  .float{display:none}
}
@media (max-width:640px){
  .wrap{width:calc(100% - 32px)}
  .features-grid{grid-template-columns:1fr}
  .steps{grid-template-columns:1fr}
  .grid-2{grid-template-columns:1fr}
  .cases-row{grid-template-columns:1fr}
  .cases-row .case-mini:nth-child(n+2){display:none}
  .main{padding:16px}
  .main-top .chip:not(.gold){display:none}
  .app-body{min-height:0}
  .tabs{display:flex;width:100%;overflow-x:auto}
  .tab{flex:1;padding:0 12px;font-size:13px}
  .prev-caption{grid-template-columns:1fr;gap:14px}
  .cal{grid-template-columns:36px repeat(3,1fr)}
  .cal .c4,.cal .c5{display:none}
  .tbl .hide-sm{display:none}
  .hero-ctas .btn,.cta .hero-ctas .btn{width:100%}
  .hero-ctas{width:100%;max-width:360px}
  .faq summary{font-size:16px}
}
@media (prefers-reduced-motion:reduce){
  *,*::before,*::after{animation:none!important;transition:none!important}
  html{scroll-behavior:auto}
  .reveal{opacity:1;transform:none}
  .hero-visual .app{transform:none}
}
</style>
</head>
<body>
<a href="#main" class="skip">Skip to content</a>

<!-- SVG icon sprite -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <defs>
    <symbol id="i-scale" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M7 21h10M5 7h14M12 5l-1-2h2z"/><path d="M5 7l-3 7a3.5 3.5 0 0 0 6 0zM19 7l-3 7a3.5 3.5 0 0 0 6 0z"/></symbol>
    <symbol id="i-phone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/><path d="M15 3a6 6 0 0 1 6 6M15 7a2 2 0 0 1 2 2"/></symbol>
    <symbol id="i-cal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4M8 14h3M8 17h6"/></symbol>
    <symbol id="i-users" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/></symbol>
    <symbol id="i-folder" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M8 13h8M8 16h5"/></symbol>
    <symbol id="i-book" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4.5A1.5 1.5 0 0 1 5.5 3H20v15H5.5A1.5 1.5 0 0 0 4 19.5z"/><path d="M4 19.5A1.5 1.5 0 0 0 5.5 21H20M9 7h7M9 10h5"/></symbol>
    <symbol id="i-spark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"/></symbol>
    <symbol id="i-sliders" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/></symbol>
    <symbol id="i-building" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V9l7-5 7 5v12"/><path d="M9 21v-6h6v6M9 11h.01M15 11h.01"/></symbol>
    <symbol id="i-home" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="16" width="7" height="5" rx="2"/></symbol>
    <symbol id="i-bell" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 16V11a6 6 0 0 1 12 0v5l2 2H4z"/><path d="M10 21h4"/></symbol>
    <symbol id="i-chart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></symbol>
    <symbol id="i-gear" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></symbol>
    <symbol id="i-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></symbol>
    <symbol id="i-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></symbol>
    <symbol id="i-ext" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></symbol>
    <symbol id="i-search" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></symbol>
    <symbol id="i-lock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14.5v2.5"/></symbol>
    <symbol id="i-shield" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v6c0 4.5-3.4 8.2-8 9-4.6-.8-8-4.5-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/></symbol>
    <symbol id="i-key" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M17 6l3 3M15 8l2 2"/></symbol>
    <symbol id="i-log" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 12h7M9 15h7M9 18h4"/></symbol>
    <symbol id="i-file" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5"/></symbol>
  </defs>
</svg>

<!-- ============ NAV ============ -->
<header class="nav" id="nav">
  <div class="wrap nav-inner">
    <a href="#top" class="logo" aria-label="AI Lawyer home">
      <span class="logo-mark"><svg><use href="#i-scale"/></svg></span>
      <span>AI <b>Lawyer</b></span>
    </a>
    <nav aria-label="Primary">
      <ul class="nav-links">
        <li><a href="#features">Features</a></li>
        <li><a href="#how">How It Works</a></li>
        <li><a href="#research">Case Research</a></li>
        <li><a href="#pricing">Pricing</a></li>
        <li><a href="#faq">FAQ</a></li>
      </ul>
    </nav>
    <div class="nav-cta">
      <a href="/login" class="nav-signin">Sign In</a>
      <a href="#pricing" class="btn btn-gold btn-sm">Get Started</a>
      <button class="burger" id="burger" aria-label="Open menu" aria-expanded="false" aria-controls="mobileMenu"><span></span></button>
    </div>
  </div>
  <div class="mobile-menu" id="mobileMenu">
    <ul>
      <li><a href="#features">Features</a></li>
      <li><a href="#how">How It Works</a></li>
      <li><a href="#research">Case Research</a></li>
      <li><a href="#pricing">Pricing</a></li>
      <li><a href="#faq">FAQ</a></li>
    </ul>
    <div class="mm-cta">
      <a href="/login" class="btn btn-ghost">Sign In</a>
      <a href="#pricing" class="btn btn-gold">Get Started</a>
    </div>
  </div>
</header>

<main id="main">

<!-- ============ HERO ============ -->
<section class="hero" id="top" aria-labelledby="hero-title">
  <div class="wrap">
    <div class="hero-copy">
      <span class="pill reveal"><span class="dot">Live</span> AI receptionist, calendar, cases &amp; case law — one workspace</span>
      <h1 id="hero-title" class="display reveal d1"><span class="sans">Your practice,</span> <em>always</em> in session.</h1>
      <p class="lede reveal d2">AI Lawyer answers your calls, books consultations, organizes clients and cases, and researches case law across five jurisdictions — so you can spend your hours on the law.</p>
      <div class="hero-ctas reveal d3">
        <a href="#pricing" class="btn btn-gold">Get Started <svg><use href="#i-arrow"/></svg></a>
        <a href="/contact" class="btn btn-ghost">Book a Demo</a>
      </div>
    </div>

    <div class="hero-visual" id="heroVisual">
      <div class="float float-a" aria-hidden="true">
        <div class="row"><span class="ic ic-sage"><svg><use href="#i-cal"/></svg></span><span class="k">Appointment booked</span></div>
        <div class="v">Thu, 11:30 · Tenancy consultation with Ayesha Khan</div>
      </div>
      <div class="float float-b" aria-hidden="true">
        <div class="row"><span class="ic ic-gold"><svg><use href="#i-book"/></svg></span><span class="k">Research · UK</span></div>
        <div class="v">3 authorities found · sources linked</div>
      </div>

      <div class="app" role="img" aria-label="AI Lawyer dashboard showing a live AI receptionist call, today's appointments and active cases">
        <div class="app-bar"><div class="dots"><i></i><i></i><i></i></div><div class="url"><svg><use href="#i-lock"/></svg>app.ailawyer · Dashboard</div></div>
        <div class="app-body">
          <aside class="side" aria-hidden="true">
            <div class="ws"><span class="ws-av">K</span><div><strong>Khan &amp; Partners</strong><small>Firm workspace</small></div></div>
            <span class="nav-i on"><svg><use href="#i-home"/></svg>Dashboard</span>
            <span class="nav-i"><svg><use href="#i-phone"/></svg>Calls<span class="badge">2</span></span>
            <span class="nav-i"><svg><use href="#i-cal"/></svg>Appointments</span>
            <span class="nav-i"><svg><use href="#i-users"/></svg>Clients &amp; Leads</span>
            <span class="nav-i"><svg><use href="#i-folder"/></svg>Cases</span>
            <span class="nav-i"><svg><use href="#i-book"/></svg>Case Research</span>
            <div class="sep"></div>
            <span class="nav-i"><svg><use href="#i-spark"/></svg>AI Agent</span>
            <span class="nav-i"><svg><use href="#i-bell"/></svg>Notifications</span>
            <span class="nav-i"><svg><use href="#i-chart"/></svg>Reports</span>
            <span class="nav-i"><svg><use href="#i-gear"/></svg>Firm Settings</span>
          </aside>
          <div class="main" aria-hidden="true">
            <div class="main-top">
              <div><h4>Good morning, Ayesha</h4><p>Tuesday · Your AI receptionist is answering calls</p></div>
              <div style="display:flex;gap:8px"><span class="chip"><svg width="13" height="13"><use href="#i-search"/></svg>Search</span><span class="chip gold">+ New case</span></div>
            </div>
            <div class="grid-2">
              <div class="panel">
                <div class="panel-h"><b>AI Receptionist</b><span class="live">On call</span></div>
                <div class="call">
                  <div class="call-who">
                    <span class="av av-2">SR</span>
                    <div><b style="font-weight:500;font-size:12.5px">Sara Rahman</b><br><small style="color:var(--dim);font-size:11px">New lead · Property</small></div>
                    <div class="wave"><i style="animation-delay:-.1s"></i><i style="animation-delay:-.5s"></i><i style="animation-delay:-.3s"></i><i style="animation-delay:-.8s"></i><i style="animation-delay:-.2s"></i><i style="animation-delay:-.6s"></i><i style="animation-delay:-.4s"></i><i style="animation-delay:-.9s"></i></div>
                  </div>
                  <div class="transcript">
                    <div class="bubble caller">My landlord is withholding my deposit. Can I speak to someone this week?</div>
                    <div class="bubble ai">I can book a tenancy consultation with Ayesha Khan. Thursday at 11:30 is available — shall I confirm?</div>
                  </div>
                  <div class="booked"><svg><use href="#i-check"/></svg>Booked · Thu 11:30 · Lead added to Clients</div>
                </div>
              </div>
              <div class="panel">
                <div class="panel-h"><b>Today</b><span>4 appointments</span></div>
                <div class="sched">
                  <div class="slot"><time>09:30</time><span class="bar"></span><div><b>Omar Siddiqui</b><small>Contract review</small></div><span class="tag g">Office</span></div>
                  <div class="slot"><time>11:00</time><span class="bar s"></span><div><b>Hana Malik</b><small>Family law intake</small></div><span class="tag s">Call</span></div>
                  <div class="slot"><time>14:15</time><span class="bar r"></span><div><b>Daniel Brooks</b><small>Employment dispute</small></div><span class="tag r">Video</span></div>
                  <div class="slot"><time>16:00</time><span class="bar d"></span><div><b>Internal</b><small>Case prep · Ahmed v. Noor</small></div><span class="tag">Block</span></div>
                </div>
              </div>
            </div>
            <div class="cases-row">
              <div class="case-mini"><span class="t">Ahmed v. Noor Developers</span><div class="prog"><i style="width:68%"></i></div><div class="m"><span>Civil · Hearing prep</span><span class="tag g">Active</span></div></div>
              <div class="case-mini"><span class="t">Brooks — Wrongful dismissal</span><div class="prog"><i style="width:35%"></i></div><div class="m"><span>Employment · Discovery</span><span class="tag s">Open</span></div></div>
              <div class="case-mini"><span class="t">Malik family settlement</span><div class="prog"><i style="width:84%"></i></div><div class="m"><span>Family · Drafting</span><span class="tag r">Review</span></div></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ FEATURES ============ -->
<section class="section" id="features" aria-labelledby="features-title" style="padding-top:clamp(40px,6vw,80px)">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow reveal">Features</span>
      <h2 id="features-title" class="h2 reveal d1">Everything your practice runs on, <em>in one place.</em></h2>
    </div>
    <div class="features-grid">
      <article class="feature reveal">
        <span class="num">01</span>
        <div class="f-icon"><svg><use href="#i-phone"/></svg></div>
        <h3>AI Receptionist</h3>
        <p>Answers incoming calls, captures caller details and books consultations — with every call logged in Calls History.</p>
      </article>
      <article class="feature reveal d1">
        <span class="num">02</span>
        <div class="f-icon"><svg><use href="#i-cal"/></svg></div>
        <h3>Smart Appointments</h3>
        <p>A shared calendar that respects your services and availability, with notifications when something is booked.</p>
      </article>
      <article class="feature reveal d2">
        <span class="num">03</span>
        <div class="f-icon"><svg><use href="#i-folder"/></svg></div>
        <h3>Client &amp; Case Management</h3>
        <p>Track leads into clients, open cases, and keep every document attached to the matter it belongs to.</p>
      </article>
      <article class="feature reveal">
        <span class="num">04</span>
        <div class="f-icon"><svg><use href="#i-book"/></svg></div>
        <h3>Case Law Research</h3>
        <p>Search real case law across Pakistan, UK, Canada, Australia and USA — filtered by jurisdiction, with source links.</p>
      </article>
      <article class="feature reveal d1">
        <span class="num">05</span>
        <div class="f-icon"><svg><use href="#i-sliders"/></svg></div>
        <h3>AI Agent Settings</h3>
        <p>Decide how your receptionist greets callers, which services it offers and when it can book you.</p>
      </article>
      <article class="feature reveal d2">
        <span class="num">06</span>
        <div class="f-icon"><svg><use href="#i-building"/></svg></div>
        <h3>Firm Workspace</h3>
        <p>A multi-tenant workspace for your whole firm, with reports and firm settings managed in one place.</p>
      </article>
    </div>
  </div>
</section>

<!-- ============ 30-SECOND VIDEO DEMO ============ -->
<section class="section" id="demo-video" aria-labelledby="demo-video-title">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow reveal">Watch In Action</span>
      <h2 id="demo-video-title" class="h2 reveal d1">Every Missed Call is a <em>Lost Client.</em></h2>
      <p class="lede reveal d2">See how our 24/7 AI Voice Receptionist, Legal CRM, and 5-Country Case Law engine automate your legal practice in 30 seconds.</p>
    </div>

    <div class="vplayer-wrap reveal">
      <!-- Player Top Bar -->
      <div class="vplayer-topbar">
        <div class="vplayer-dots">
          <span class="vplayer-dot" style="background:#FF5F56"></span>
          <span class="vplayer-dot" style="background:#FFBD2E"></span>
          <span class="vplayer-dot" style="background:#27C93F"></span>
        </div>
        <div class="vplayer-title-badge">
          <span class="vplayer-live-dot"></span>
          <span>Calls missed today: <strong>0</strong></span>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
          <button type="button" id="vtopSoundBtn" style="background:rgba(212,176,106,.15);border:1px solid rgba(212,176,106,.4);color:var(--gold-2);padding:4px 12px;border-radius:999px;font-size:11.5px;font-family:var(--mono);cursor:pointer;display:inline-flex;align-items:center;gap:5px;">
            <span id="vtopSoundIcon">🔊</span> <span id="vtopSoundText">Sound: ON</span>
          </button>
          <div style="font-family:var(--mono);font-size:11px;color:var(--dim)">30-SEC DEMO</div>
        </div>
      </div>

      <!-- Real Audio Tracks (Recorded Voiceover) -->
      <audio id="vAudioPlayer" preload="auto" playsinline webkit-playsinline src="{{ asset('audio/promo-full.wav') }}"></audio>

      <!-- Player Interactive Stage (30-Sec Visual Walkthrough) -->
      <div class="vplayer-stage" id="vplayerStage" style="position:relative;cursor:pointer;" title="Click anywhere to toggle sound">
        
        <!-- Scene 1: The Hook (0:00 - 0:06) -->
        <div class="vscene active" id="vscene1">
          <div style="max-width:560px;margin:0 auto;text-align:center;display:grid;gap:18px;">
            <div style="display:inline-flex;align-items:center;gap:8px;padding:6px 14px;border-radius:999px;background:rgba(217,142,126,.12);border:1px solid rgba(217,142,126,.3);color:var(--rose);font-size:12px;font-family:var(--mono);margin:0 auto;">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
              <span>24/7 AI Voice Receptionist</span>
            </div>
            <h3 style="font-family:var(--serif);font-size:clamp(2rem,3.8vw,3.2rem);line-height:1.1;font-weight:400;color:var(--text);">
              Every Missed Call is a <em style="color:var(--gold-2);font-style:italic;">Lost Client.</em>
            </h3>
            <p style="color:var(--muted);font-size:15px;line-height:1.6;margin:0;">
              Prospective clients hire the first lawyer who answers. With AI Lawyer, your front desk is always open, converting callers into confirmed bookings 24/7.
            </p>
            <div style="display:inline-flex;align-items:center;justify-content:center;gap:20px;padding:14px 22px;border-radius:14px;background:rgba(255,255,255,.03);border:1px solid var(--line);margin-top:8px;">
              <div>
                <span style="font-family:var(--mono);font-size:11px;color:var(--dim);text-transform:uppercase;">Calls Missed Today</span>
                <div style="font-size:26px;font-weight:700;color:#10B981;">0</div>
              </div>
              <div style="width:1px;height:36px;background:var(--line);"></div>
              <div>
                <span style="font-family:var(--mono);font-size:11px;color:var(--dim);text-transform:uppercase;">Response Time</span>
                <div style="font-size:26px;font-weight:700;color:var(--gold);">&lt; 1 sec</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Scene 2: 24/7 AI Voice Receptionist (0:06 - 0:14) -->
        <div class="vscene" id="vscene2">
          <div style="max-width:620px;margin:0 auto;display:grid;gap:16px;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-radius:12px;background:rgba(19,17,14,.9);border:1px solid var(--line-2);">
              <div style="display:flex;align-items:center;gap:10px;">
                <span style="width:10px;height:10px;border-radius:50%;background:#10B981;box-shadow:0 0 10px #10B981;"></span>
                <span style="font-family:var(--mono);font-size:13px;color:var(--text);font-weight:500;">📞 Incoming call · +92 300 1234567</span>
              </div>
              <div class="wave" style="margin:0;">
                <i style="animation-delay:0s"></i><i style="animation-delay:.15s"></i><i style="animation-delay:.3s"></i><i style="animation-delay:.45s"></i><i style="animation-delay:.6s"></i>
              </div>
            </div>

            <div class="transcript" style="font-size:13px;gap:10px;">
              <div class="bubble ai" style="max-width:85%;">
                <strong style="color:var(--gold);display:block;font-size:11px;font-family:var(--mono);margin-bottom:2px;">AI RECEPTIONIST</strong>
                "Thank you for calling. How can I help?"
              </div>
              <div class="bubble caller" style="max-width:85%;background:rgba(255,255,255,.07);">
                <strong style="color:var(--muted);display:block;font-size:11px;font-family:var(--mono);margin-bottom:2px;">CLIENT</strong>
                "I need a family law consultation."
              </div>
              <div class="bubble ai" style="max-width:85%;">
                <strong style="color:var(--gold);display:block;font-size:11px;font-family:var(--mono);margin-bottom:2px;">AI RECEPTIONIST</strong>
                "Qualified. Tomorrow 11:00 AM works?"
              </div>
            </div>

            <div class="booked" style="font-size:13.5px;padding:12px 16px;border-radius:12px;">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
              <span><strong>✓ Consultation booked</strong> · Tomorrow 11:00 AM</span>
            </div>
          </div>
        </div>

        <!-- Scene 3: Full Legal CRM + 5-Country Case Law AI (0:14 - 0:22) -->
        <div class="vscene" id="vscene3">
          <div style="display:grid;gap:16px;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
              <div>
                <span class="eyebrow" style="font-size:10px;">CRM &amp; Billing</span>
                <h4 style="font-size:19px;font-weight:500;margin:4px 0 0;">Full Legal CRM + 5-Country Case Law AI</h4>
              </div>
              <div style="display:flex;gap:10px;">
                <span style="padding:4px 10px;border-radius:8px;font-size:11.5px;font-weight:600;background:rgba(212,176,106,.15);border:1px solid rgba(212,176,106,.3);color:var(--gold-2);">Solo Lawyer <strong>$299</strong> per month</span>
                <span style="padding:4px 10px;border-radius:8px;font-size:11.5px;font-weight:600;background:rgba(255,255,255,.05);border:1px solid var(--line-2);color:var(--text);">Law Firm <strong>$599</strong> per month</span>
              </div>
            </div>

            <!-- Mini CRM Table -->
            <div style="overflow-x:auto;border:1px solid var(--line);border-radius:12px;background:rgba(0,0,0,.3);">
              <table class="tbl" style="margin:0;">
                <thead>
                  <tr>
                    <th>CLIENT NAME</th>
                    <th>PRACTICE AREA</th>
                    <th>STATUS</th>
                    <th style="text-align:right">ACTION</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><div class="who"><span class="seat" style="width:26px;height:26px;font-size:11px;background:#1e3a47;color:#5eead4;">AK</span><b>Ahmed Khan</b></div></td>
                    <td style="color:var(--muted)">Family law</td>
                    <td><span class="tag s">Active</span></td>
                    <td style="text-align:right;color:var(--dim);font-family:var(--mono);font-size:11px;">View Case →</td>
                  </tr>
                  <tr>
                    <td><div class="who"><span class="seat" style="width:26px;height:26px;font-size:11px;background:#2d264f;color:#c4b5fd;">SA</span><b>Sara Ali</b></div></td>
                    <td style="color:var(--muted)">Corporate</td>
                    <td><span class="tag s">Active</span></td>
                    <td style="text-align:right;color:var(--dim);font-family:var(--mono);font-size:11px;">View Case →</td>
                  </tr>
                  <tr>
                    <td><div class="who"><span class="seat" style="width:26px;height:26px;font-size:11px;background:#3f2e18;color:#fde047;">UM</span><b>Usman Malik</b></div></td>
                    <td style="color:var(--muted)">Criminal</td>
                    <td><span class="tag g">Consult booked</span></td>
                    <td style="text-align:right;color:var(--dim);font-family:var(--mono);font-size:11px;">Calendar →</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Secure checkout + 5-Country Flags Row -->
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 14px;border-radius:10px;background:rgba(255,255,255,.02);border:1px solid var(--line);flex-wrap:wrap;">
              <div style="display:flex;align-items:center;gap:8px;font-size:12px;">
                <span style="color:#10B981;font-weight:700;">🔒 Secure checkout</span>
                <span style="color:var(--dim);">·</span>
                <span style="color:var(--muted);font-family:var(--mono);">Card number</span>
                <span style="color:var(--dim);">·</span>
                <span style="color:var(--gold-2);font-weight:600;">Pay with Stripe</span>
              </div>
              <div style="display:flex;gap:10px;font-size:12px;color:var(--ivory-2);flex-wrap:wrap;align-items:center;">
                <span style="font-family:var(--mono);font-size:10.5px;color:var(--dim);text-transform:uppercase;">AI case law research:</span>
                <span>🇵🇰 Pakistan</span>
                <span>🇺🇸 United States</span>
                <span>🇬🇧 United Kingdom</span>
                <span>🇨🇦 Canada</span>
                <span>🇦🇺 Australia</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Scene 4: Outro & Call to Action (0:22 - 0:30) -->
        <div class="vscene" id="vscene4">
          <div style="max-width:540px;margin:0 auto;text-align:center;display:grid;gap:18px;">
            <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,var(--gold-2),var(--gold));color:#1a1408;display:grid;place-items:center;margin:0 auto;box-shadow:0 8px 24px rgba(212,176,106,.35);">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 3v18M7 7h10"/><path d="M7 7l-3 6a3 3 0 1 0 6 0zM17 7l-3 6a3 3 0 1 0 6 0z"/></svg>
            </div>
            <div style="font-family:var(--mono);font-size:12px;color:var(--gold-2);text-transform:uppercase;letter-spacing:.1em;">AI Lawyer</div>
            <h3 style="font-family:var(--serif);font-size:clamp(2.2rem,4vw,3.2rem);line-height:1.08;font-weight:400;color:var(--text);">
              Automate Your Practice <em style="color:var(--gold-2);font-style:italic;">Today.</em>
            </h3>
            <p style="color:var(--muted);font-size:15px;margin:0;">
              Scale your firm, eliminate administrative hours, and deliver an effortless client booking experience around the clock.
            </p>
            <div style="display:flex;align-items:center;justify-content:center;gap:14px;margin-top:10px;flex-wrap:wrap;">
              <a href="#pricing" class="btn btn-gold">
                <span>Start today</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
              </a>
              <span style="font-family:var(--mono);font-size:12.5px;color:var(--gold-2);border-bottom:1px solid rgba(212,176,106,.3);padding-bottom:1px;">ailawyer.toolkitpro.cloud/public</span>
            </div>
          </div>
        </div>

      </div>

      <!-- Player Bottom Controls Bar -->
      <div class="vcontrols">
        <div class="vscrubber" id="vscrubber" title="Click to seek">
          <div class="vscrubber-bar" id="vscrubberBar"></div>
        </div>
        <div class="vcontrols-row">
          <div class="vplay-group" style="flex-wrap:wrap;gap:8px;">
            <button type="button" class="vplay-btn" id="vplayBtn" aria-label="Play or Pause">
              <svg id="vplayIcon" width="16" height="16" fill="currentColor" viewBox="0 0 24 24" style="display:none"><polygon points="6 3 20 12 6 21 6 3"/></svg>
              <svg id="vpauseIcon" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
            </button>
            <div class="vtime">
              <span id="vcurrTime">0:00</span> / <span>0:30</span>
            </div>
            <button type="button" id="vsoundBtn" aria-label="Toggle Sound" style="display:inline-flex;align-items:center;gap:6px;background:rgba(212,176,106,.15);border:1px solid rgba(212,176,106,.4);color:var(--gold-2);padding:5px 12px;border-radius:8px;font-size:12px;font-family:var(--mono);cursor:pointer;">
              <span id="vsoundIcon">🔊</span>
              <span id="vsoundText">Sound: ON</span>
            </button>
            <label for="vAudioUpload" id="vAudioUploadLabel" style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.07);border:1px solid var(--line-2);color:var(--text);padding:5px 12px;border-radius:8px;font-size:11.5px;font-family:var(--mono);cursor:pointer;" title="Upload custom voiceover (mp3/wav)">
              <span>🎙️</span>
              <span id="vAudioUploadText">Add your own voice (mp3)</span>
              <input type="file" id="vAudioUpload" accept="audio/*,.mp3,.wav" style="display:none;">
            </label>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ HOW IT WORKS ============ -->
<section class="section light" id="how" aria-labelledby="how-title">
  <div class="wrap">
    <div class="section-head">
      <span class="eyebrow reveal">How It Works</span>
      <h2 id="how-title" class="h2 reveal d1">From sign-up to <em>first booked call</em> in four steps.</h2>
    </div>
    <ol class="steps" style="list-style:none;margin:0;padding:0">
      <li class="step reveal"><div class="step-n" aria-hidden="true">1</div><h3>Create your workspace</h3><p>Set up a personal workspace as a lawyer, or a shared one for your firm.</p></li>
      <li class="step reveal d1"><div class="step-n" aria-hidden="true">2</div><h3>Configure</h3><p>Add your services and availability, then set up your AI receptionist.</p></li>
      <li class="step reveal d2"><div class="step-n" aria-hidden="true">3</div><h3>Manage your day</h3><p>Calls, appointments, clients and cases flow into one dashboard.</p></li>
      <li class="step reveal d3"><div class="step-n" aria-hidden="true">4</div><h3>Research</h3><p>Look up case law without leaving the workspace your matters live in.</p></li>
    </ol>
  </div>
</section>

<!-- ============ JOIN ============ -->
<section class="section" id="join" aria-labelledby="join-title">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow reveal">Join AI Lawyer</span>
      <h2 id="join-title" class="h2 reveal d1">Built for the <em>solo practitioner</em> and the firm.</h2>
    </div>
    <div class="join">
      <article class="join-card reveal">
        <span class="kicker">For individuals</span>
        <div class="join-vis" aria-hidden="true"><span class="seat solo">A</span><span class="ws-line"></span></div>
        <h3>Individual Lawyer</h3>
        <p>Your own legal workspace — receptionist, calendar, clients, cases and research, set up around how you practise.</p>
        <ul class="list">
          <li><svg><use href="#i-check"/></svg>Personal AI receptionist &amp; calendar</li>
          <li><svg><use href="#i-check"/></svg>Your clients, cases and documents</li>
          <li><svg><use href="#i-check"/></svg>Multi-country case law research</li>
        </ul>
        <div><a href="{{ route('checkout.show', ['plan' => 'starter']) }}" class="btn btn-ghost">Start as a lawyer <svg><use href="#i-arrow"/></svg></a></div>
      </article>
      <article class="join-card firm reveal d1">
        <span class="kicker">For firms</span>
        <div class="join-vis" aria-hidden="true">
          <span class="seat av-1">AK</span><span class="seat av-2">OS</span><span class="seat av-3">HM</span><span class="seat av-4">+5</span><span class="ws-line"></span>
        </div>
        <h3>Law Firm</h3>
        <p>One shared, multi-lawyer workspace managed through the platform — each lawyer with their own role and calendar.</p>
        <ul class="list">
          <li><svg><use href="#i-check"/></svg>Multi-lawyer shared workspace</li>
          <li><svg><use href="#i-check"/></svg>Role-based access &amp; firm settings</li>
          <li><svg><use href="#i-check"/></svg>Firm-wide reports and calls history</li>
        </ul>
        <div><a href="{{ route('checkout.show', ['plan' => 'growth']) }}" class="btn btn-gold">Set up your firm <svg><use href="#i-arrow"/></svg></a></div>
      </article>
    </div>
  </div>
</section>

<!-- ============ CASE RESEARCH ============ -->
<section class="section light" id="research" aria-labelledby="research-title">
  <div class="wrap research">
    <div>
      <div class="section-head">
        <span class="eyebrow reveal">Case Research</span>
        <h2 id="research-title" class="h2 reveal d1">Real case law, <em>five jurisdictions,</em> one search.</h2>
        <p class="lede reveal d2">Filter by jurisdiction and open every authority at its source — so you can verify before you rely on it.</p>
      </div>
      <ul class="juris-list reveal d3">
        <li><b>Pakistan</b><span>PK</span></li>
        <li><b>United Kingdom</b><span>UK</span></li>
        <li><b>Canada</b><span>CA</span></li>
        <li><b>Australia</b><span>AU</span></li>
        <li><b>United States</b><span>US</span></li>
      </ul>
    </div>

    <div class="r-app reveal d2">
      <div class="search" role="presentation">
        <svg><use href="#i-search"/></svg>
        <span class="q">Constitutional limits on executive power</span>
        <span class="go">Search</span>
      </div>
      <div class="filters" role="group" aria-label="Filter by jurisdiction">
        <button class="flt" data-j="all" aria-pressed="true">All</button>
        <button class="flt" data-j="PK" aria-pressed="false"><span class="cc">PK</span>Pakistan</button>
        <button class="flt" data-j="UK" aria-pressed="false"><span class="cc">UK</span>UK</button>
        <button class="flt" data-j="CA" aria-pressed="false"><span class="cc">CA</span>Canada</button>
        <button class="flt" data-j="AU" aria-pressed="false"><span class="cc">AU</span>Australia</button>
        <button class="flt" data-j="US" aria-pressed="false"><span class="cc">US</span>USA</button>
      </div>
      <div class="results" id="results" aria-live="polite"></div>
    </div>
  </div>
</section>

<!-- ============ PRODUCT PREVIEW ============ -->
<section class="section" id="preview" aria-labelledby="preview-title">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow reveal">Product</span>
      <h2 id="preview-title" class="h2 reveal d1">See the workspace <em>you'll live in.</em></h2>
    </div>

    <div class="tabs-wrap reveal d2">
      <div class="tabs" role="tablist" aria-label="Product previews">
        <span class="tab-ind" id="tabInd" aria-hidden="true"></span>
        <button class="tab" role="tab" id="tab-1" aria-controls="panel-1" aria-selected="true">Dashboard &amp; Appointments</button>
        <button class="tab" role="tab" id="tab-2" aria-controls="panel-2" aria-selected="false" tabindex="-1">Cases &amp; Clients</button>
        <button class="tab" role="tab" id="tab-3" aria-controls="panel-3" aria-selected="false" tabindex="-1">Case Research</button>
      </div>
    </div>

    <div class="panels reveal d3">
      <!-- Panel 1 -->
      <div class="tpanel" role="tabpanel" id="panel-1" aria-labelledby="tab-1">
        <div class="app">
          <div class="app-bar"><div class="dots"><i></i><i></i><i></i></div><div class="url"><svg><use href="#i-lock"/></svg>app.ailawyer · Appointments</div></div>
          <div class="app-body" style="min-height:0">
            <aside class="side" aria-hidden="true">
              <div class="ws"><span class="ws-av">K</span><div><strong>Khan &amp; Partners</strong><small>Firm workspace</small></div></div>
              <span class="nav-i"><svg><use href="#i-home"/></svg>Dashboard</span>
              <span class="nav-i"><svg><use href="#i-phone"/></svg>Calls</span>
              <span class="nav-i on"><svg><use href="#i-cal"/></svg>Appointments</span>
              <span class="nav-i"><svg><use href="#i-users"/></svg>Clients &amp; Leads</span>
              <span class="nav-i"><svg><use href="#i-folder"/></svg>Cases</span>
              <span class="nav-i"><svg><use href="#i-book"/></svg>Case Research</span>
            </aside>
            <div class="main" aria-hidden="true">
              <div class="main-top">
                <div><h4>This week</h4><p>Showing Ayesha Khan · All services</p></div>
                <div style="display:flex;gap:8px"><span class="chip">Week</span><span class="chip gold">+ Appointment</span></div>
              </div>
              <div class="cal">
                <div class="hd" style="border-left:0"></div>
                <div class="hd"><span>Mon</span><b>10</b></div>
                <div class="hd today"><span>Tue</span><b>11</b></div>
                <div class="hd"><span>Wed</span><b>12</b></div>
                <div class="hd c4"><span>Thu</span><b>13</b></div>
                <div class="hd c5"><span>Fri</span><b>14</b></div>

                <div class="tm">09:00</div>
                <div class="cell"><div class="ev g"><b>Contract review</b><small>O. Siddiqui</small></div></div>
                <div class="cell"><div class="ev g"><b>Omar Siddiqui</b><small>Office</small></div></div>
                <div class="cell"></div>
                <div class="cell c4"></div>
                <div class="cell c5"><div class="ev s h2x"><b>Court prep</b><small>Ahmed v. Noor</small></div></div>

                <div class="tm">10:00</div>
                <div class="cell"></div>
                <div class="cell"></div>
                <div class="cell"><div class="ev r"><b>Daniel Brooks</b><small>Video</small></div></div>
                <div class="cell c4"></div>
                <div class="cell c5"></div>

                <div class="tm">11:00</div>
                <div class="cell"><div class="ev s"><b>Family intake</b><small>Call</small></div></div>
                <div class="cell"><div class="ev s"><b>Hana Malik</b><small>Call</small></div></div>
                <div class="cell"></div>
                <div class="cell c4"><div class="ev g"><b>Sara Rahman</b><small>Booked by AI</small></div></div>
                <div class="cell c5"></div>

                <div class="tm">14:00</div>
                <div class="cell"></div>
                <div class="cell"><div class="ev r"><b>Daniel Brooks</b><small>Employment</small></div></div>
                <div class="cell"><div class="ev g"><b>New consult</b><small>Booked by AI</small></div></div>
                <div class="cell c4"></div>
                <div class="cell c5"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Panel 2 -->
      <div class="tpanel" role="tabpanel" id="panel-2" aria-labelledby="tab-2" hidden>
        <div class="app">
          <div class="app-bar"><div class="dots"><i></i><i></i><i></i></div><div class="url"><svg><use href="#i-lock"/></svg>app.ailawyer · Cases</div></div>
          <div class="app-body" style="min-height:0">
            <aside class="side" aria-hidden="true">
              <div class="ws"><span class="ws-av">K</span><div><strong>Khan &amp; Partners</strong><small>Firm workspace</small></div></div>
              <span class="nav-i"><svg><use href="#i-home"/></svg>Dashboard</span>
              <span class="nav-i"><svg><use href="#i-phone"/></svg>Calls</span>
              <span class="nav-i"><svg><use href="#i-cal"/></svg>Appointments</span>
              <span class="nav-i"><svg><use href="#i-users"/></svg>Clients &amp; Leads</span>
              <span class="nav-i on"><svg><use href="#i-folder"/></svg>Cases</span>
              <span class="nav-i"><svg><use href="#i-book"/></svg>Case Research</span>
            </aside>
            <div class="main" aria-hidden="true">
              <div class="main-top">
                <div><h4>Cases</h4><p>Active matters across the firm</p></div>
                <div style="display:flex;gap:8px"><span class="chip">Filter</span><span class="chip gold">+ New case</span></div>
              </div>
              <div class="split">
                <div class="panel" style="padding:6px 8px;overflow-x:auto">
                  <table class="tbl">
                    <thead><tr><th>Matter</th><th>Client</th><th class="hide-sm">Lawyer</th><th>Status</th></tr></thead>
                    <tbody>
                      <tr><td>Ahmed v. Noor Developers</td><td><div class="who"><span class="av av-1">FA</span>Faisal Ahmed</div></td><td class="hide-sm">A. Khan</td><td><span class="tag g">Active</span></td></tr>
                      <tr><td>Wrongful dismissal</td><td><div class="who"><span class="av av-3">DB</span>Daniel Brooks</div></td><td class="hide-sm">O. Siddiqui</td><td><span class="tag s">Open</span></td></tr>
                      <tr><td>Family settlement</td><td><div class="who"><span class="av av-2">HM</span>Hana Malik</div></td><td class="hide-sm">A. Khan</td><td><span class="tag r">Review</span></td></tr>
                      <tr><td>Commercial lease</td><td><div class="who"><span class="av av-4">LT</span>Lumen Traders</div></td><td class="hide-sm">Z. Hussain</td><td><span class="tag g">Active</span></td></tr>
                      <tr><td>Deposit dispute</td><td><div class="who"><span class="av av-2">SR</span>Sara Rahman</div></td><td class="hide-sm">A. Khan</td><td><span class="tag">Lead</span></td></tr>
                    </tbody>
                  </table>
                </div>
                <div class="panel" style="display:grid;gap:14px;align-content:start">
                  <div class="call-who"><span class="av av-1">FA</span><div><b style="font-weight:500">Faisal Ahmed</b><br><small style="color:var(--dim);font-size:11px">Client · since March</small></div></div>
                  <dl class="kv"><dt>Case</dt><dd>Ahmed v. Noor</dd><dt>Practice</dt><dd>Civil</dd><dt>Next</dt><dd style="color:var(--gold-2)">Fri · Court prep</dd></dl>
                  <div class="panel-h" style="margin:4px 0 0"><b>Documents</b><span>3</span></div>
                  <div class="doc"><svg><use href="#i-file"/></svg>Plaint_v2.pdf<small>PDF</small></div>
                  <div class="doc"><svg><use href="#i-file"/></svg>Sale agreement.pdf<small>PDF</small></div>
                  <div class="doc"><svg><use href="#i-file"/></svg>Hearing notes.docx<small>DOC</small></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Panel 3 -->
      <div class="tpanel" role="tabpanel" id="panel-3" aria-labelledby="tab-3" hidden>
        <div class="app">
          <div class="app-bar"><div class="dots"><i></i><i></i><i></i></div><div class="url"><svg><use href="#i-lock"/></svg>app.ailawyer · Case Research</div></div>
          <div class="app-body" style="min-height:0">
            <aside class="side" aria-hidden="true">
              <div class="ws"><span class="ws-av">K</span><div><strong>Khan &amp; Partners</strong><small>Firm workspace</small></div></div>
              <span class="nav-i"><svg><use href="#i-home"/></svg>Dashboard</span>
              <span class="nav-i"><svg><use href="#i-phone"/></svg>Calls</span>
              <span class="nav-i"><svg><use href="#i-cal"/></svg>Appointments</span>
              <span class="nav-i"><svg><use href="#i-users"/></svg>Clients &amp; Leads</span>
              <span class="nav-i"><svg><use href="#i-folder"/></svg>Cases</span>
              <span class="nav-i on"><svg><use href="#i-book"/></svg>Case Research</span>
            </aside>
            <div class="main" aria-hidden="true">
              <div class="main-top">
                <div><h4>Case Research</h4><p>“duty of care negligence” · United Kingdom</p></div>
                <div style="display:flex;gap:8px"><span class="chip">UK</span><span class="chip gold">New search</span></div>
              </div>
              <div class="case-doc">
                <div class="panel">
                  <span class="res-j" style="color:var(--gold)"><i style="background:var(--gold)"></i>United Kingdom · House of Lords</span>
                  <h5 style="margin-top:10px">Donoghue v Stevenson</h5>
                  <p style="font-family:var(--mono);font-size:11.5px;color:var(--dim);margin:0 0 16px">[1932] UKHL 100 · [1932] AC 562</p>
                  <p class="para">The House of Lords held that a manufacturer owes a duty of care to the ultimate consumer of its product. Lord Atkin set out the <span class="hl">“neighbour principle”</span> — you must take reasonable care to avoid acts or omissions you can reasonably foresee would be likely to injure your neighbour.</p>
                  <p class="para">The decision is widely treated as the foundation of the modern law of negligence across common-law jurisdictions.</p>
                  <a class="src" href="#research" style="color:var(--gold-2);border-color:rgba(212,176,106,.5)">Open source <svg><use href="#i-ext"/></svg></a>
                </div>
                <div class="panel" style="display:grid;gap:10px;align-content:start">
                  <div class="panel-h" style="margin:0"><b>Related results</b><span>UK</span></div>
                  <div class="doc"><svg><use href="#i-book"/></svg>Caparo v Dickman<small>1990</small></div>
                  <div class="doc"><svg><use href="#i-book"/></svg>Robinson v CC West Yorks<small>2018</small></div>
                  <div class="doc"><svg><use href="#i-book"/></svg>Bolam v Friern HMC<small>1957</small></div>
                  <div class="booked" style="margin-top:6px;color:var(--gold-2);background:rgba(212,176,106,.08);border-color:rgba(212,176,106,.22)"><svg><use href="#i-folder"/></svg>Save to case · Ahmed v. Noor</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="prev-caption" id="prevCaption">
      <div class="on"><b>Dashboard &amp; Appointments</b>Your day at a glance, with AI-booked consultations on the calendar.</div>
      <div><b>Cases &amp; Clients</b>Every matter, client and document in one connected view.</div>
      <div><b>Case Research</b>Read the authority, open the source, keep moving.</div>
    </div>
  </div>
</section>

<!-- ============ PRICING ============ -->
@php
    $hasYearlyPrices = !empty(config('stripe.prices.starter.yearly')) || !empty(config('stripe.prices.growth.yearly'));
    $activeUser = auth()->user();
    $hasActivePlan = $activeUser && $activeUser->hasActiveSubscription();
    $dashboardUrl = $hasActivePlan ? route($activeUser->homeRoute()) : null;
@endphp
<section class="section light" id="pricing" aria-labelledby="pricing-title">
  <div class="wrap">
    <div class="section-head center">
      <span class="eyebrow reveal">Pricing</span>
      <h2 id="pricing-title" class="h2 reveal d1">Simple plans for <em>serious practices.</em></h2>
      @if ($hasYearlyPrices)
      <div class="toggle reveal d2">
        <span class="lbl on" id="lblMonthly">Monthly</span>
        <button class="switch" id="billing" role="switch" aria-checked="false" aria-label="Bill yearly"></button>
        <span class="lbl" id="lblYearly">Yearly</span>
        <span class="save">2 months free</span>
      </div>
      @endif
    </div>

    <div class="plans">
      <article class="plan reveal">
        <div class="plan-name"><h3>Starter</h3></div>
        <span class="account-type-tag individual">Individual Lawyer Account</span>
        <p class="for">For individual lawyers.</p>
        <div class="price"><span class="amt" data-m="$299" data-y="$2,990">$299</span><span class="per" data-m="/month" data-y="/year">/month</span></div>
        <p class="price-note" data-m="Billed monthly" data-y="Billed yearly">Billed monthly</p>
        <ul class="list">
          <li><svg><use href="#i-check"/></svg>AI phone receptionist</li>
          <li><svg><use href="#i-check"/></svg>Appointments &amp; calendar</li>
          <li><svg><use href="#i-check"/></svg>Client, lead &amp; case management</li>
          <li><svg><use href="#i-check"/></svg>Multi-country case law research</li>
        </ul>
        @if ($hasActivePlan)
          <a href="{{ $dashboardUrl }}" class="btn btn-outline">Go to Dashboard</a>
        @else
          <a href="{{ route('checkout.show', ['plan' => 'starter', 'interval' => 'monthly']) }}" class="btn btn-outline plan-cta" data-plan="starter">Get Started</a>
        @endif
      </article>

      <article class="plan pop reveal d1">
        <span class="badge-pop">Most Popular</span>
        <div class="plan-name"><h3>Growth</h3></div>
        <span class="account-type-tag firm">Law Firm Account</span>
        <p class="for">For established law firms.</p>
        <div class="price"><span class="amt" data-m="$599" data-y="$5,990">$599</span><span class="per" data-m="/month" data-y="/year">/month</span></div>
        <p class="price-note" data-m="Billed monthly" data-y="Billed yearly">Billed monthly</p>
        <ul class="list">
          <li><svg><use href="#i-check"/></svg>Everything in Starter</li>
          <li><svg><use href="#i-check"/></svg>Multi-lawyer firm workspace</li>
          <li><svg><use href="#i-check"/></svg>Role-based access &amp; audit logs</li>
          <li><svg><use href="#i-check"/></svg>Reports &amp; firm settings</li>
        </ul>
        @if ($hasActivePlan)
          <a href="{{ $dashboardUrl }}" class="btn btn-gold">Go to Dashboard <svg><use href="#i-arrow"/></svg></a>
        @else
          <a href="{{ route('checkout.show', ['plan' => 'growth', 'interval' => 'monthly']) }}" class="btn btn-gold plan-cta" data-plan="growth">Get Started <svg><use href="#i-arrow"/></svg></a>
        @endif
      </article>

      <article class="plan reveal d2">
        <div class="plan-name"><h3>Pilot</h3><span class="tag">Locked rate</span></div>
        <span class="account-type-tag firm">Law Firm Account</span>
        <p class="for">Growth features, rate locked for 12 months.</p>
        <div class="price"><span class="amt" data-m="$400" data-y="$4,800">$400</span><span class="per" data-m="/month equiv." data-y="/year">/month equiv.</span></div>
        <p class="price-note" data-m="Billed $4,800 yearly" data-y="Equivalent to $400/month">Billed $4,800 yearly</p>
        <ul class="list">
          <li><svg><use href="#i-check"/></svg>All Growth features</li>
          <li><svg><use href="#i-check"/></svg>12-month locked rate</li>
          <li><svg><use href="#i-check"/></svg>Billed annually</li>
        </ul>
        @if ($hasActivePlan)
          <a href="{{ $dashboardUrl }}" class="btn btn-outline">Go to Dashboard</a>
        @else
          <button type="button" class="btn btn-outline" onclick="openSalesModal('pilot')">Contact Sales</button>
        @endif
      </article>
    </div>
  </div>
</section>

<!-- ============ SECURITY ============ -->
<section class="section" id="security" aria-labelledby="security-title">
  <div class="wrap sec">
    <div class="vault reveal" aria-hidden="true">
      <div class="ring r1"></div><div class="ring r2"></div><div class="ring r3"></div>
      <div class="core"><svg><use href="#i-shield"/></svg></div>
      <span class="orb" style="top:6%;left:58%">firm: khan-partners</span>
      <span class="orb" style="top:46%;left:-4%">role: lawyer</span>
      <span class="orb" style="bottom:8%;left:46%">audit · case.updated</span>
    </div>
    <div>
      <div class="section-head" style="margin-bottom:36px">
        <span class="eyebrow reveal">Security</span>
        <h2 id="security-title" class="h2 reveal d1">Your firm's data <em>stays your firm's.</em></h2>
      </div>
      <div class="sec-items">
        <div class="sec-item reveal"><div class="f-icon"><svg><use href="#i-lock"/></svg></div><div><h3>Firm data isolation</h3><p>Every firm works in its own tenant. Clients, cases and calls are never shared across workspaces.</p></div></div>
        <div class="sec-item reveal d1"><div class="f-icon"><svg><use href="#i-key"/></svg></div><div><h3>Role-based access</h3><p>Control what each lawyer and staff member can see and do inside the firm.</p></div></div>
        <div class="sec-item reveal d2"><div class="f-icon"><svg><use href="#i-log"/></svg></div><div><h3>Audit logs</h3><p>A record of important actions, so you always know who changed what.</p></div></div>
      </div>
    </div>
  </div>
</section>

<!-- ============ FAQ ============ -->
<section class="section" id="faq" aria-labelledby="faq-title" style="padding-top:0">
  <div class="wrap faq">
    <div class="section-head">
      <span class="eyebrow reveal">FAQ</span>
      <h2 id="faq-title" class="h2 reveal d1">Questions, <em>answered.</em></h2>
      <p class="lede reveal d2">Still unsure? <a href="/contact" style="color:var(--gold-2);border-bottom:1px solid rgba(212,176,106,.4)">Talk to our team.</a></p>
    </div>
    <div class="faq-list reveal d2">
      <details>
        <summary>How long does onboarding take?<span class="plus" aria-hidden="true"></span></summary>
        <div class="ans"><p>Create your lawyer or firm workspace, add your services and availability, and configure your AI receptionist. You can start managing calls and appointments as soon as that's done.</p></div>
      </details>
      <details>
        <summary>How does the AI receptionist book appointments?<span class="plus" aria-hidden="true"></span></summary>
        <div class="ans"><p>It answers calls using the services and availability you've set in AI Agent Settings, books a slot on your calendar, and records the call in Calls History — you're notified when it happens.</p></div>
      </details>
      <details>
        <summary>Can my whole firm use one workspace?<span class="plus" aria-hidden="true"></span></summary>
        <div class="ans"><p>Yes. A Law Firm workspace is shared by multiple lawyers, with role-based access, firm settings and reports — while staying isolated from every other firm on the platform.</p></div>
      </details>
      <details>
        <summary>Which jurisdictions does case research cover?<span class="plus" aria-hidden="true"></span></summary>
        <div class="ans"><p>Pakistan, the United Kingdom, Canada, Australia and the United States. Filter by jurisdiction and open each result at its source.</p></div>
      </details>
      <details>
        <summary>What's the difference between Growth and Pilot?<span class="plus" aria-hidden="true"></span></summary>
        <div class="ans"><p>Pilot includes the same features as Growth, billed yearly at $4,800 — equivalent to $400/month — with the rate locked for 12 months.</p></div>
      </details>
    </div>
  </div>
</section>

<!-- ============ FINAL CTA ============ -->
<section aria-labelledby="cta-title">
  <div class="wrap">
    <div class="cta reveal">
      <span class="eyebrow" style="margin-bottom:22px">Get started</span>
      <h2 id="cta-title">Let the AI take the calls. <em>You take the case.</em></h2>
      <p>Set up your workspace today and let AI Lawyer handle the front desk.</p>
      <div class="hero-ctas" style="margin-inline:auto">
        <a href="/register" class="btn btn-gold">Get Started <svg><use href="#i-arrow"/></svg></a>
        <a href="/contact" class="btn btn-ghost">Contact Sales</a>
      </div>
    </div>
  </div>
</section>

</main>

<!-- ============ FOOTER ============ -->
<footer>
  <div class="wrap">
    <div class="foot">
      <div>
        <a href="#top" class="logo" aria-label="AI Lawyer home"><span class="logo-mark"><svg><use href="#i-scale"/></svg></span><span>AI <b>Lawyer</b></span></a>
        <p>The AI-powered practice platform for individual lawyers and law firms.</p>
      </div>
      <div>
        <h4>Product</h4>
        <ul><li><a href="#features">Features</a></li><li><a href="#how">How It Works</a></li><li><a href="#research">Case Research</a></li><li><a href="#preview">Product</a></li></ul>
      </div>
      <div>
        <h4>Company</h4>
        <ul><li><a href="#pricing">Pricing</a></li><li><a href="#security">Security</a></li><li><a href="#faq">FAQ</a></li><li><a href="/contact">Contact Sales</a></li></ul>
      </div>
      <div>
        <h4>Account</h4>
        <ul><li><a href="/login">Sign In</a></li><li><a href="/register">Get Started</a></li></ul>
      </div>
    </div>
    <div class="foot-bottom">
      <span>© <span id="year">2026</span> AI Lawyer. All rights reserved.</span>
      <span>Case research results are for reference — always verify at source.</span>
    </div>
    <div class="wordmark" aria-hidden="true">AI Lawyer</div>
  </div>
</footer>

<script>
(() => {
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];

  /* Nav scroll + mobile menu */
  const nav = $('#nav'), burger = $('#burger');
  const onScroll = () => nav.classList.toggle('scrolled', scrollY > 12);
  onScroll(); addEventListener('scroll', onScroll, { passive: true });
  burger.addEventListener('click', () => {
    const open = nav.classList.toggle('open');
    burger.setAttribute('aria-expanded', open);
    burger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
  });
  $$('#mobileMenu a').forEach(a => a.addEventListener('click', () => {
    nav.classList.remove('open'); burger.setAttribute('aria-expanded', 'false');
  }));

  /* Scroll reveals */
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
  }, { threshold: 0.14, rootMargin: '0px 0px -40px 0px' });
  $$('.reveal, #heroVisual').forEach(el => io.observe(el));

  /* Feature hover glow follows cursor */
  $$('.feature').forEach(f => f.addEventListener('pointermove', e => {
    const r = f.getBoundingClientRect();
    f.style.setProperty('--mx', (e.clientX - r.left) + 'px');
    f.style.setProperty('--my', (e.clientY - r.top) + 'px');
  }));

  /* Case research filters */
  const cases = [
    { j: 'PK', jn: 'Pakistan · Supreme Court', t: 'Benazir Bhutto v. Federation of Pakistan', c: 'PLD 1988 SC 416', p: 'Expanded the reading of fundamental rights and the Supreme Court’s jurisdiction to hear matters of public importance.' },
    { j: 'UK', jn: 'United Kingdom · Supreme Court', t: 'R (Miller) v The Prime Minister', c: '[2019] UKSC 41', p: 'Held the prorogation of Parliament unlawful where it frustrated Parliament’s constitutional functions without reasonable justification.' },
    { j: 'CA', jn: 'Canada · Supreme Court', t: 'R v Oakes', c: '[1986] 1 SCR 103', p: 'Established the test for whether a limit on a Charter right is justified in a free and democratic society.' },
    { j: 'AU', jn: 'Australia · High Court', t: 'Australian Communist Party v Commonwealth', c: '(1951) 83 CLR 1', p: 'Struck down legislation dissolving the party, affirming judicial review of the limits of Commonwealth power.' },
    { j: 'US', jn: 'United States · Supreme Court', t: 'Marbury v. Madison', c: '5 U.S. 137 (1803)', p: 'Established the principle of judicial review of acts of the legislative and executive branches.' },
    { j: 'US', jn: 'United States · Supreme Court', t: 'Youngstown Sheet & Tube Co. v. Sawyer', c: '343 U.S. 579 (1952)', p: 'Limited presidential power to seize private property without authorisation from Congress.' }
  ];
  const results = $('#results');
  const esc = s => s.replace(/[&<>"]/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ch]));
  const render = j => {
    const list = j === 'all' ? cases.slice(0, 4) : cases.filter(c => c.j === j);
    results.innerHTML = list.map((c, i) => `
      <article class="res" style="animation-delay:${i * 60}ms">
        <div class="res-top"><span class="res-j"><i></i>${esc(c.jn)}</span><a class="src" href="#research" aria-label="Open source for ${esc(c.t)}">Source <svg><use href="#i-ext"/></svg></a></div>
        <h4>${esc(c.t)}</h4>
        <span class="cite">${esc(c.c)}</span>
        <p>${esc(c.p)}</p>
      </article>`).join('');
  };
  $$('.flt').forEach(b => b.addEventListener('click', () => {
    $$('.flt').forEach(x => x.setAttribute('aria-pressed', x === b));
    render(b.dataset.j);
  }));
  render('all');

  /* Product preview tabs */
  const tabs = $$('[role="tab"]'), ind = $('#tabInd'), caps = $$('#prevCaption > div');
  const moveInd = t => { ind.style.width = t.offsetWidth + 'px'; ind.style.transform = `translateX(${t.offsetLeft - 5}px)`; };
  const select = (t, focus) => {
    tabs.forEach((x, i) => {
      const on = x === t;
      x.setAttribute('aria-selected', on); x.tabIndex = on ? 0 : -1;
      $('#' + x.getAttribute('aria-controls')).hidden = !on;
      caps[i].classList.toggle('on', on);
    });
    moveInd(t); if (focus) t.focus();
  };
  tabs.forEach((t, i) => {
    t.addEventListener('click', () => select(t));
    t.addEventListener('keydown', e => {
      const k = e.key; let n = null;
      if (k === 'ArrowRight') n = tabs[(i + 1) % tabs.length];
      if (k === 'ArrowLeft') n = tabs[(i - 1 + tabs.length) % tabs.length];
      if (k === 'Home') n = tabs[0]; if (k === 'End') n = tabs[tabs.length - 1];
      if (n) { e.preventDefault(); select(n, true); }
    });
  });
  const reInd = () => moveInd($('[role="tab"][aria-selected="true"]'));
  addEventListener('resize', reInd);
  (document.fonts ? document.fonts.ready : Promise.resolve()).then(reInd);
  reInd();

  /* Pricing toggle */
  const sw = $('#billing'), lm = $('#lblMonthly'), ly = $('#lblYearly');
  if (sw && lm && ly) {
    const setBilling = yearly => {
      sw.setAttribute('aria-checked', yearly);
      lm.classList.toggle('on', !yearly); ly.classList.toggle('on', yearly);
      const key = yearly ? 'y' : 'm';
      const interval = yearly ? 'yearly' : 'monthly';
      $$('.plan .amt').forEach(a => {
        a.classList.add('swap');
        setTimeout(() => { a.textContent = a.dataset[key]; a.classList.remove('swap'); }, 180);
      });
      $$('.plan .per, .plan .price-note').forEach(p => p.textContent = p.dataset[key]);
      $$('.plan-cta').forEach(a => {
        const plan = a.dataset.plan;
        if (!plan) return;
        a.href = `/checkout?plan=${encodeURIComponent(plan)}&interval=${interval}`;
      });
    };
    sw.addEventListener('click', () => setBilling(sw.getAttribute('aria-checked') !== 'true'));
    lm.addEventListener('click', () => setBilling(false));
    ly.addEventListener('click', () => setBilling(true));
    lm.style.cursor = ly.style.cursor = 'pointer';
  }

  $('#year').textContent = new Date().getFullYear();
})();

function openSalesModal(plan = 'pilot') {
  const backdrop = document.getElementById('salesModalBackdrop');
  const planInput = document.getElementById('salesPlanInput');
  if (planInput) planInput.value = plan;
  if (backdrop) backdrop.classList.add('open');
}

function closeSalesModal() {
  const backdrop = document.getElementById('salesModalBackdrop');
  if (backdrop) backdrop.classList.remove('open');
}

function submitSalesInquiry(e) {
  e.preventDefault();
  const form = document.getElementById('salesInquiryForm');
  const btn = document.getElementById('salesSubmitBtn');
  const successAlert = document.getElementById('salesSuccessAlert');
  const errorAlert = document.getElementById('salesErrorAlert');

  btn.disabled = true;
  btn.textContent = 'Submitting...';
  successAlert.style.display = 'none';
  errorAlert.style.display = 'none';

  const formData = new FormData(form);

  fetch('{{ route("sales-inquiries.store") }}', {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    },
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    btn.disabled = false;
    btn.textContent = 'Submit Inquiry';
    if (data.success) {
      successAlert.textContent = data.message;
      successAlert.style.display = 'block';
      form.reset();
      setTimeout(closeSalesModal, 3000);
    } else {
      errorAlert.textContent = data.message || 'Something went wrong. Please try again.';
      errorAlert.style.display = 'block';
    }
  })
  .catch(err => {
    btn.disabled = false;
    btn.textContent = 'Submit Inquiry';
    errorAlert.textContent = 'Network error. Please try again.';
    errorAlert.style.display = 'block';
  });
}

// ---------- 30-Second Video Demo Controller with Real Audio Narration ----------
(function() {
  if (!document.getElementById('demo-video')) return;
  const duration = 30;
  let currentTime = 0;
  let isPlaying = false;
  let timer = null;
  let soundEnabled = true;

  const audio = document.getElementById('vAudioPlayer');
  const scrubberBar = document.getElementById('vscrubberBar');
  const scrubber = document.getElementById('vscrubber');
  const playBtn = document.getElementById('vplayBtn');
  const playIcon = document.getElementById('vplayIcon');
  const pauseIcon = document.getElementById('vpauseIcon');
  const currTimeEl = document.getElementById('vcurrTime');
  const soundBtn = document.getElementById('vsoundBtn');
  const soundIcon = document.getElementById('vsoundIcon');
  const soundText = document.getElementById('vsoundText');
  const topSoundBtn = document.getElementById('vtopSoundBtn');
  const topSoundIcon = document.getElementById('vtopSoundIcon');
  const topSoundText = document.getElementById('vtopSoundText');
  const audioUpload = document.getElementById('vAudioUpload');
  const audioUploadText = document.getElementById('vAudioUploadText');
  const stage = document.getElementById('vplayerStage');

  const scenes = [
    { id: 0, start: 0, end: 6, el: document.getElementById('vscene1') },
    { id: 1, start: 6, end: 14, el: document.getElementById('vscene2') },
    { id: 2, start: 14, end: 22, el: document.getElementById('vscene3') },
    { id: 3, start: 22, end: 30, el: document.getElementById('vscene4') }
  ];

  function updateSoundLabels(on) {
    soundEnabled = on;
    const label = on ? 'Sound: ON' : 'Sound: OFF';
    const icon = on ? '🔊' : '🔈';
    if (soundIcon) soundIcon.textContent = icon;
    if (soundText) soundText.textContent = label;
    if (topSoundIcon) topSoundIcon.textContent = icon;
    if (topSoundText) topSoundText.textContent = label;
  }

  function tryPlayAudio() {
    if (!audio || !soundEnabled) return;
    audio.currentTime = currentTime;
    audio.muted = false;
    const p = audio.play();
    if (p !== undefined) {
      p.then(() => {
        updateSoundLabels(true);
      }).catch(() => {
        // Browser blocked unmuted autoplay before user interaction:
        // Play muted immediately so time track runs in exact sync with visual scenes
        audio.muted = true;
        audio.play().catch(() => {});
      });
    }
  }

  // Automatic un-mute on ANY user movement or interaction (scroll, wheel, mousemove, keydown, click)
  function tryAutoUnmute() {
    if (!audio || !soundEnabled || !isPlaying) return;
    if (audio.muted || audio.paused) {
      audio.muted = false;
      const p = audio.play();
      if (p !== undefined) {
        p.then(() => {
          updateSoundLabels(true);
          // Once audio is unmuted and active, clean up movement listeners
          window.removeEventListener('mousemove', tryAutoUnmute);
          window.removeEventListener('pointermove', tryAutoUnmute);
        }).catch(() => {});
      }
    }
  }

  // Continuous listeners across all user input channels
  const userActions = ['pointerdown', 'mousedown', 'pointerup', 'mouseup', 'touchstart', 'touchend', 'keydown', 'wheel', 'scroll', 'pointermove', 'mousemove'];
  userActions.forEach(evt => {
    window.addEventListener(evt, tryAutoUnmute, { capture: true, passive: true });
    document.addEventListener(evt, tryAutoUnmute, { capture: true, passive: true });
  });
  stage?.addEventListener('pointerenter', tryAutoUnmute, { passive: true });
  stage?.addEventListener('pointermove', tryAutoUnmute, { passive: true });

  function toggleSound() {
    soundEnabled = !soundEnabled;
    updateSoundLabels(soundEnabled);
    if (!soundEnabled) {
      if (audio) { audio.pause(); audio.muted = true; }
    } else {
      if (audio) {
        audio.muted = false;
        audio.currentTime = currentTime;
        audio.play().catch(() => {});
      }
    }
  }

  soundBtn?.addEventListener('click', toggleSound);
  topSoundBtn?.addEventListener('click', toggleSound);

  audioUpload?.addEventListener('change', (e) => {
    const file = e.target.files && e.target.files[0];
    if (file) {
      const url = URL.createObjectURL(file);
      if (audio) {
        audio.src = url;
        audio.currentTime = 0;
        currentTime = 0;
        soundEnabled = true;
        updateSoundLabels(true);
        audio.muted = false;
        audio.play().catch(() => {});
      }
      if (audioUploadText) {
        audioUploadText.textContent = file.name.length > 20 ? file.name.substring(0, 17) + '...' : file.name;
      }
      play();
    }
  });

  function formatTime(s) {
    const sec = Math.floor(s);
    return '0:' + (sec < 10 ? '0' : '') + sec;
  }

  function updateUI() {
    if (currTimeEl) currTimeEl.textContent = formatTime(currentTime);
    if (scrubberBar) scrubberBar.style.width = ((currentTime / duration) * 100) + '%';

    scenes.forEach(scene => {
      if (scene.el) {
        if (currentTime >= scene.start && (currentTime < scene.end || (currentTime === duration && scene.end === duration))) {
          scene.el.classList.add('active');
        } else {
          scene.el.classList.remove('active');
        }
      }
    });
  }

  function tick() {
    if (!isPlaying) return;
    if (audio && !audio.paused && !audio.ended && audio.currentTime > 0) {
      currentTime = audio.currentTime;
    } else {
      currentTime += 0.25;
    }

    if (currentTime > duration) {
      currentTime = 0;
      if (audio) {
        audio.currentTime = 0;
        if (soundEnabled) {
          audio.muted = false;
          audio.play().catch(() => {});
        }
      }
    }
    updateUI();
  }

  function play() {
    isPlaying = true;
    if (playIcon) playIcon.style.display = 'none';
    if (pauseIcon) pauseIcon.style.display = 'block';
    if (!timer) timer = setInterval(tick, 250);
    tryPlayAudio();
  }

  function pause() {
    isPlaying = false;
    if (audio) audio.pause();
    if (playIcon) playIcon.style.display = 'block';
    if (pauseIcon) pauseIcon.style.display = 'none';
    if (timer) { clearInterval(timer); timer = null; }
  }

  const videoSection = document.getElementById('demo-video');
  let userManuallyPaused = false;

  playBtn?.addEventListener('click', () => {
    if (isPlaying) {
      userManuallyPaused = true;
      pause();
    } else {
      userManuallyPaused = false;
      play();
      tryAutoUnmute();
    }
  });

  scrubber?.addEventListener('click', (e) => {
    const rect = scrubber.getBoundingClientRect();
    const clickX = e.clientX - rect.left;
    currentTime = Math.max(0, Math.min(duration, (clickX / rect.width) * duration));
    if (audio) {
      audio.currentTime = currentTime;
    }
    updateUI();
    if (!isPlaying) {
      play();
      tryAutoUnmute();
    }
  });

  // Clicking on the video stage toggles sound or unmutes
  stage?.addEventListener('click', (e) => {
    if (e.target.closest('a') || e.target.closest('button') || e.target.closest('label') || e.target.closest('input')) return;
    if (audio && soundEnabled) {
      audio.muted = false;
      audio.currentTime = currentTime;
      audio.play().catch(() => {});
      updateSoundLabels(true);
    }
  });

  // Scroll-triggered Auto-play when video comes into view, Auto-pause when scrolled away
  if (videoSection && 'IntersectionObserver' in window) {
    const videoObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting && entry.intersectionRatio >= 0.15) {
          // When scrolled into view: auto-play video & audio if user hasn't manually paused
          if (!userManuallyPaused && !isPlaying) {
            play();
            tryAutoUnmute();
          }
        } else if (!entry.isIntersecting || entry.intersectionRatio < 0.10) {
          // When scrolled past or away from video: pause video and audio
          if (isPlaying) {
            pause();
          }
        }
      });
    }, {
      threshold: [0.05, 0.15, 0.3, 0.5]
    });

    videoObserver.observe(videoSection);
  } else {
    play();
  }
})();
</script>

<div id="salesModalBackdrop" class="sales-modal-backdrop" onclick="if(event.target === this) closeSalesModal()">
  <div class="sales-modal">
    <button type="button" class="sales-modal-close" onclick="closeSalesModal()" aria-label="Close">&times;</button>
    <h3>Contact Sales · Pilot Plan</h3>
    <p>Locked at $400/mo ($4,800/yr) for 12 months. Tell us about your practice.</p>

    <div id="salesSuccessAlert" style="display:none;background:#dcfce7;border:1px solid #86efac;color:#166534;padding:12px;border-radius:8px;font-size:13.5px;margin-bottom:14px;"></div>
    <div id="salesErrorAlert" style="display:none;background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:12px;border-radius:8px;font-size:13.5px;margin-bottom:14px;"></div>

    <form id="salesInquiryForm" onsubmit="submitSalesInquiry(event)">
      @csrf
      <input type="hidden" name="plan" id="salesPlanInput" value="pilot">
      <div class="form-field">
        <label for="salesName">Full name *</label>
        <input type="text" id="salesName" name="name" required placeholder="e.g. Attorney John Doe">
      </div>
      <div class="form-field">
        <label for="salesEmail">Work email *</label>
        <input type="email" id="salesEmail" name="email" required placeholder="john@lawfirm.com">
      </div>
      <div class="form-field">
        <label for="salesFirm">Law firm name</label>
        <input type="text" id="salesFirm" name="firm_name" placeholder="Doe &amp; Partners Law">
      </div>
      <div class="form-field">
        <label for="salesMessage">Message or estimated practice size *</label>
        <textarea id="salesMessage" name="message" rows="3" required placeholder="Tell us about your team size, expected call volume, and timeline..."></textarea>
      </div>
      <button type="submit" id="salesSubmitBtn" class="btn-submit">Submit Inquiry</button>
    </form>
  </div>
</div>
</body>
</html>
