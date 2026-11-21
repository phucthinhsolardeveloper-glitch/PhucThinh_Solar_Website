/**
 * Build precise, source-backed product-gallery spec images.
 * Usage: node scripts/content/create-product-gallery-cards.cjs
 * Draft-only: never touches database, uploads or production.
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require('../../tools/visual-tests/node_modules/playwright');

const root = path.resolve(__dirname, '../..');
const dir = path.join(root, 'backend/resources/product-gallery/2026-11-17/drafts');
const inputDir = path.join(dir, 'source');
const modelSource = 'https://vn.solaxpower.com/products/x3-neo-lv.html';
const products = [
  {id:811, code:'X3-NEO-10K-LV', ac:10, pv:20, mppt:'2 (2 / 1)', current:'36 / 18 A', weight:'44,6 kg'},
  {id:812, code:'X3-NEO-12K-LV', ac:12, pv:24, mppt:'2 (2 / 2)', current:'36 / 36 A', weight:'44,6 kg'},
  {id:813, code:'X3-NEO-15K-LV', ac:15, pv:30, mppt:'2 (2 / 2)', current:'36 / 36 A', weight:'44,6 kg'},
  {id:814, code:'X3-NEO-20K-LV', ac:20, pv:40, mppt:'2 (2 / 2)', current:'36 / 36 A', weight:'45,2 kg'}
];
function escapeHtml(s){return String(s).replace(/[&<>"']/g, x=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[x]));}
function template(p, imageBase64){
  return `<!doctype html><html lang="vi"><head><meta charset="UTF-8"/><style>
  @font-face {font-family:system;src:local("Arial");}
  *{box-sizing:border-box}html,body{width:1200px;height:1200px;margin:0}
  body{font-family:Arial,"Noto Sans",sans-serif;color:#0A2251;background:#f6f8fb}
  .page{width:1200px;height:1200px;position:relative;overflow:hidden;background:#f7f9fc}
  .header{height:231px;background:#0A2251;color:#fff;padding:55px 66px 38px;position:relative}
  .header:after{content:"";position:absolute;left:66px;bottom:0;width:176px;height:7px;background:#C9962E}
  .eyebrow{font-size:25px;letter-spacing:4px;font-weight:bold;color:#E0B654;margin-bottom:21px}
  .h1{font-size:51px;line-height:1.1;font-weight:800;letter-spacing:-1.4px}
  .sub{font-size:25px;color:#c9d5e6;margin-top:13px}
  .content{padding:49px 66px}
  .hero{display:flex;gap:42px;align-items:center;height:444px}
  .photo{height:408px;width:425px;background:#fff;border:1px solid #e4eaf0;border-radius:24px;display:flex;align-items:center;justify-content:center;box-shadow:0 10px 32px rgba(10,34,81,.04)}
  .photo img{width:94%;height:94%;object-fit:contain}
  .right{flex:1}
  .mode{font-size:23px;color:#57667c;text-transform:uppercase;letter-spacing:2px;font-weight:bold;margin-bottom:23px}
  .rating{font-size:98px;font-weight:850;color:#0A2251;letter-spacing:-4px;line-height:1}
  .rating span{font-size:45px;letter-spacing:0}
  .desc{margin-top:16px;font-size:27px;font-weight:700;line-height:1.35}
  .pill{display:inline-block;color:#0A2251;background:#F2E5C7;padding:13px 24px;border-radius:100px;font-size:21px;font-weight:700;margin-top:27px}
  .section{font-size:24px;color:#0A2251;font-weight:800;margin:23px 0 19px;letter-spacing:.2px}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:19px}
  .metric{background:#fff;border:1px solid #e2e9f0;border-radius:18px;padding:24px 29px;min-height:113px}
  .metric .k{font-size:19px;color:#61718a;line-height:1.1}
  .metric .v{font-size:30px;font-weight:800;margin-top:13px}
  .footer{position:absolute;left:66px;right:66px;bottom:35px;border-top:1px solid #d8e0e9;padding-top:19px;display:flex;align-items:center;justify-content:space-between;font-size:17px;color:#61718a}
  .footer strong{color:#0A2251;letter-spacing:1.4px}
  </style></head><body><div class="page">
  <div class="header"><div class="eyebrow">PHÚC THỊNH SOLAR / THÔNG SỐ HÃNG</div><div class="h1">SolaX ${escapeHtml(p.code)}</div><div class="sub">Biến tần hybrid điện áp thấp · 3 pha</div></div>
  <div class="content"><div class="hero">
  <div class="photo"><img alt="Ảnh đại diện SolaX ${escapeHtml(p.code)}" src="data:image/png;base64,${imageBase64}"/></div>
  <div class="right"><div class="mode">Công suất AC định mức</div>
  <div class="rating">${p.ac}<span> kW</span></div>
  <div class="desc">Tương ứng model ${escapeHtml(p.code)}</div>
  <div class="pill">Cấp bảo vệ IP65</div></div></div>
  <div class="section">Các thông số quan trọng khi lựa chọn</div>
  <div class="grid">
    <div class="metric"><div class="k">Công suất PV đề xuất tối đa</div><div class="v">${p.pv} kWp</div></div>
    <div class="metric"><div class="k">Dải điện áp MPPT</div><div class="v">160–950 V</div></div>
    <div class="metric"><div class="k">MPPT (số chuỗi theo bộ)</div><div class="v">${p.mppt}</div></div>
    <div class="metric"><div class="k">Dòng đầu vào tối đa theo MPPT</div><div class="v">${p.current}</div></div>
  </div></div>
  <div class="footer"><span>Nguồn: SolaX Power · X3-NEO-LV · 17/11/2026</span><strong>PHUCTHINHSOLAR.COM</strong></div>
  </div></body></html>`;
}
(async()=>{
  fs.mkdirSync(dir,{recursive:true});
  const browser=await chromium.launch({headless:true});
  const page=await browser.newPage({viewport:{width:1200,height:1200},deviceScaleFactor:1});
  const manifest=[];
  for(const p of products){
    const orig=path.join(inputDir,`${p.id}-avatar.png`);
    if(!fs.existsSync(orig))throw new Error('Missing approved photo: '+orig);
    const b64=fs.readFileSync(orig).toString('base64');
    await page.setContent(template(p,b64),{waitUntil:'load'});
    const filename=`pts-solax-${p.code.toLowerCase()}-spec-card-20261117.jpg`;
    const dst=path.join(dir,filename);
    await page.screenshot({path:dst,type:'jpeg',quality:90,animations:'disabled'});
    manifest.push({product_id:p.id,model:p.code,image_file:filename,type:'spec_card',source:modelSource,photo_source:'current production product.image',checked_at:'2026-11-17',status:'DRAFT_REVIEW',sha256:require('crypto').createHash('sha256').update(fs.readFileSync(dst)).digest('hex')});
    console.log(p.id,p.code,filename,fs.statSync(dst).size);
  }
  fs.writeFileSync(path.join(dir,'manifest.json'),JSON.stringify(manifest,null,2)+'\n');
  await browser.close();
})().catch(e=>{console.error(e);process.exit(1);});
