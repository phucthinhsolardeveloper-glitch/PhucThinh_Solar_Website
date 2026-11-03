(() => {
'use strict';
const layouts = [
{id:'bleed',title:'Immersive Full-bleed',category:'cinematic',tag:'Hero / Special',img:'hero-rooftop.webp',preview:'bleed',why:'Ảnh lớn phủ qua header cho điểm nhấn cần cảm xúc và chiều sâu.',risk:'Độ tương phản chữ, ảnh nền sáng, menu mobile và header khi cuộn.',tool:'CSS + Vue; Motion for Vue cho chuyển trạng thái.',pages:['home','projects','solutions'],demo:'layout-lab.html'},
{id:'editorial',title:'Editorial Split',category:'editorial',tag:'Editorial / Story',img:'hero-home.jpg',preview:'editorial',why:'Chia nhịp hình ảnh và văn bản có chủ đích; dễ đọc, dễ định hướng.',risk:'Bố cục 50/50 lặp lại máy móc; thiếu phân cấp typography.',tool:'Nuxt UI + Tailwind + CSS layout.',pages:['about','solutions','contact'],demo:'layout-lab.html'},
{id:'frame',title:'Architectural Frame',category:'editorial',tag:'Media / Composition',img:'hero-solar-farm.webp',preview:'frame',why:'Đặt ảnh trong khung có tỷ lệ kỹ thuật, xung quanh là khoảng trắng.',risk:'Không được crop những vị trí thiết bị quan trọng.',tool:'CSS Grid, media focal point và overlay nhẹ.',pages:['about','projects','knowledge'],demo:'layout-lab.html'},
{id:'cinema',title:'Solar Cinema',category:'cinematic',tag:'Case study / Hero',img:'hero-solar-farm.webp',preview:'cinema',why:'Dùng một ảnh nổi bật thật, nhịp câu chuyện theo chương và CTA rõ.',risk:'Ảnh giả, chương thiếu dữ liệu, hiệu ứng che nội dung khi cuộn.',tool:'CSS + Motion for Vue; GSAP chỉ khi thật sự cần timeline.',pages:['projects','home','about'],demo:'layout-lab.html'},
{id:'precision',title:'Precision Paper',category:'editorial',tag:'Type / Reading',img:'hero-home.jpg',preview:'precision',why:'Typography có nhịp, khoảng trắng, lưới kỹ thuật tinh tế và media có nguồn.',risk:'Khoảng trắng quá lớn, cảm giác thiếu nội dung, eyebrow AI vô nghĩa.',tool:'CSS Grid + PTS typography/tokens.',pages:['knowledge','about','contact','solutions'],demo:'reference.html'},
{id:'journal',title:'Field Journal',category:'editorial',tag:'Documentary / Chapters',img:'hero-rooftop.webp',preview:'journal',why:'Hồ sơ công trình hoặc bài kiến thức với ảnh, chú thích, điểm neo nội dung.',risk:'Bịa case study, mô tả thiếu nguồn và chapter rỗng.',tool:'Nuxt content/CMS, anchors, scroll progress.',pages:['projects','knowledge','about'],demo:'layout-lab.html'},
{id:'blueprint',title:'Engineering Blueprint',category:'technical',tag:'Diagram / Inspector',img:'story-inverter.webp',preview:'blueprint',why:'Sơ đồ hệ thống và inspector có trạng thái, giúp giải thích luồng năng lượng.',risk:'Sai nguyên lý kỹ thuật điện và tự tạo thông số.',tool:'SVG + Vue state + Motion for Vue.',pages:['solutions','pdp','knowledge'],demo:'reference.html'},
{id:'atelier',title:'Product Atelier',category:'commerce',tag:'Product / PDP',img:'story-inverter.webp',preview:'atelier',why:'Sản phẩm là trung tâm với ảnh rõ, thông số, nguồn dữ liệu và CTA.',risk:'Thông số ảo, gallery crop, CTA bị khuất trên mobile.',tool:'Nuxt UI + Tailwind + real product DTO.',pages:['pdp','catalog','home'],demo:'reference.html'},
{id:'compare',title:'Engineering Comparison',category:'technical',tag:'Decision / Comparison',img:'hero-rooftop.webp',preview:'compare',why:'So sánh thiết bị trên dữ liệu chung có đơn vị, điểm khác biệt dễ thấy.',risk:'So sánh thiếu cùng chuẩn, tạo ưu thế giả và bảng quá rộng trên mobile.',tool:'Vue computed, data matrix, accessible table.',pages:['pdp','catalog','solutions'],demo:'reference.html'},
{id:'filters',title:'Discovery Filter Rail',category:'commerce',tag:'Catalog / Usability',img:'hero-solar-farm.webp',preview:'filters',why:'Bộ lọc, phân loại, sắp xếp và lựa chọn sản phẩm không gây rối.',risk:'Mất query URL, lọc chậm, drawer mobile khó sử dụng.',tool:'Nuxt UI + URL state + responsive drawer.',pages:['catalog','pdp'],demo:'reference.html'},
{id:'energy',title:'Spatial Energy Lab',category:'experimental',tag:'2.5D / 3D',img:'hero-rooftop.webp',preview:'energy',why:'Cảnh minh họa hệ PV, biến tần, lưu trữ, dòng năng lượng và chế độ hoạt động.',risk:'Phải phân biệt SVG 2.5D với WebGL thật; topology và hiệu năng.',tool:'SVG/Motion-v (2.5D), TresJS/Three.js (3D thật theo pilot).',pages:['solutions','home','pdp'],demo:'reference.html'},
{id:'workspace',title:'Instrument Workspace',category:'technical',tag:'App / Dashboard',img:'story-inverter.webp',preview:'workspace',why:'Ứng dụng dạng bảng, danh sách và chi tiết liên kết; có thể tái dùng cho CRM.',risk:'Dùng bento chỉ để trang trí, mất mật độ thông tin, phân quyền không rõ.',tool:'Vue + Nuxt UI + tables/drawers + real permissions.',pages:['crm','catalog'],demo:'reference.html'},
];
const effects=[
{id:'header',title:'Transparent → Solid Header',tier:'interface',tool:'CSS + observer',desc:'Ảnh đi phía sau header rồi chuyển nền khi cuộn.',pages:['home','projects','solutions'],heavy:false},
{id:'hover',title:'Focus / Hover Feedback',tier:'interface',tool:'CSS',desc:'Tạo affordance bằng underline, focus ring, phản hồi nút.',pages:['home','about','catalog','pdp','crm','knowledge','contact'],heavy:false},
{id:'gallery',title:'Gallery Crossfade',tier:'interface',tool:'Motion for Vue',desc:'Đổi media có lý do, không nhấp nháy.',pages:['projects','pdp','catalog'],heavy:false},
{id:'shared',title:'Shared Element Transition',tier:'interface',tool:'Motion for Vue',desc:'Liên hệ thumbnail và trang chi tiết bằng chuyển động nhẹ.',pages:['pdp','projects','catalog'],heavy:false},
{id:'lightbox',title:'Fullscreen Lightbox',tier:'interface',tool:'Nuxt UI / dialog',desc:'Xem ảnh đầy đủ, điều khiển bàn phím/touch.',pages:['projects','pdp','knowledge'],heavy:false},
{id:'imageReveal',title:'Image Clip Reveal',tier:'story',tool:'Motion for Vue/CSS',desc:'Cho media xuất hiện như một lớp mở đầu.',pages:['about','projects','solutions'],heavy:false},
{id:'typeReveal',title:'Text Motion / Editorial',tier:'story',tool:'Motion for Vue',desc:'Một khoảnh khắc typography có chủ đích.',pages:['about','home','projects'],heavy:false},
{id:'parallax',title:'Subtle Parallax Depth',tier:'story',tool:'Motion for Vue',desc:'Độ sâu nhỏ, tắt ở reduced-motion.',pages:['about','home','projects'],heavy:false},
{id:'chapters',title:'Sticky Chapter Progress',tier:'story',tool:'CSS/IntersectionObserver',desc:'Biết đang đọc chương nào khi nội dung dài.',pages:['projects','knowledge','solutions'],heavy:false},
{id:'rail',title:'Horizontal Media Rail',tier:'story',tool:'CSS scroll-snap',desc:'Duyệt gallery bằng touch/trackpad, không hijack wheel.',pages:['projects','pdp','knowledge'],heavy:false},
{id:'compareSlider',title:'Before / After Slider',tier:'interactive',tool:'Vue + range input',desc:'So sánh ảnh thật hai trạng thái có cùng bối cảnh.',pages:['projects','knowledge'],heavy:false},
{id:'inspector',title:'Technical Inspector',tier:'interactive',tool:'Vue state + Motion-v',desc:'Đổi thiết bị/thông số hiển thị từ dữ liệu thực.',pages:['solutions','pdp','crm'],heavy:false},
{id:'energyFlow',title:'Energy Flow Modes',tier:'interactive',tool:'SVG + Motion-v',desc:'Ngày/đêm/backup, minh họa đúng topology.',pages:['solutions','home','pdp'],heavy:false},
{id:'scrub',title:'GSAP Scroll Sequence',tier:'advanced',tool:'GSAP ScrollTrigger',desc:'Timeline đồng bộ theo cuộn, chỉ khi đã có kịch bản.',pages:['projects','home','solutions'],heavy:true},
{id:'three',title:'WebGL / True 3D Stage',tier:'advanced',tool:'TresJS / Three.js',desc:'Camera, geometry, light, interaction, performance fallback.',pages:['solutions','home','pdp'],heavy:true},
{id:'video',title:'Video Hero / Scroll Frames',tier:'advanced',tool:'Video/GSAP',desc:'Video ngắn có poster fallback và quyền tắt chuyển động.',pages:['home','projects'],heavy:true},
];
const pageText={home:'Trang chủ',about:'Giới thiệu',solutions:'Giải pháp',projects:'Dự án',catalog:'Danh mục',pdp:'Chi tiết sản phẩm',knowledge:'Kiến thức',contact:'Liên hệ',crm:'Ứng dụng CRM'};
const pageBest={home:'bleed',about:'editorial',solutions:'energy',projects:'cinema',catalog:'filters',pdp:'atelier',knowledge:'precision',contact:'editorial',crm:'workspace'};
const state={page:'projects',filter:'all',layout:'cinema',effects:[],compare:[]};
const $=id=>document.getElementById(id);
const grid=$('layout-grid'),effectsGrid=$('effects-grid');
function cardThumb(x) {
  const url='assets/'+x.img;
  return '<div class="card-art art-'+x.preview+'">'+
  '<div class="art-top"><span>PTS</span><i></i></div>'+
  '<div class="art-image" style="background-image:url(\''+url+'\')"></div>'+
  '<div class="art-copy"><b></b><b></b><em></em></div>'+
  '<div class="art-extra"></div></div>';
}
function renderGrid(){
 const items=layouts.filter(x=>state.filter==='all'||x.category===state.filter);
 grid.innerHTML=items.map(x=>'<button type="button" class="layout-card'+(x.id===state.layout?' chosen':'')+'" data-pick="'+x.id+'" aria-pressed="'+(x.id===state.layout)+'">'+
 cardThumb(x)+'<div class="card-body"><div class="card-label"><small>'+x.tag+'</small>'+(x.pages.includes(state.page)?'<span class="for-page">Đề xuất</span>':'')+'</div>'+
 '<strong>'+x.title+'</strong><p>'+x.why+'</p></div></button>').join('');
 grid.querySelectorAll('[data-pick]').forEach(b=>b.addEventListener('click',()=>pick(b.dataset.pick)));
}
function pick(id){
 if(!layouts.find(x=>x.id===id))return;
 state.layout=id;
 const x=layouts.find(x=>x.id===id);
 const preview=$('preview-art');
 preview.className='preview-art preview-'+x.preview;
 $('preview-photo').src='assets/'+x.img;
 $('preview-photo').alt='Ảnh minh họa bố cục '+x.title;
 $('preview-headline').textContent=x.id==='atelier'?'Chi tiết đúng để đưa ra lựa chọn đúng.':x.id==='workspace'?'Dữ liệu và hành động phải tìm thấy ngay.':x.id==='energy'?'Hiểu hệ năng lượng qua tương tác.':'Giải pháp năng lượng được kể bằng tư liệu thật.';
 $('preview-summary').textContent=x.why;
 $('selected-meta').textContent=(layouts.indexOf(x)+1).toString().padStart(2,'0')+' / '+layouts.length+' — '+x.tag;
 $('preview-typetag').textContent=x.title.toUpperCase();
 $('selected-title').textContent=x.title;
 $('selected-desc').textContent=x.tag+' · '+x.category;
 $('selected-why').textContent=x.why;
 $('selected-risk').textContent=x.risk;
 $('selected-tool').textContent=x.tool;
 $('open-demo').href=x.demo;
 $('open-demo').textContent='Mở bản demo gần nhất ↗';
 $('compare-button').disabled=state.compare.includes(x.id);
 $('compare-button').textContent=state.compare.includes(x.id)?'Đã thêm vào so sánh ✓':'+ Thêm vào danh sách so sánh';
 renderGrid();
 renderEffects();
 updateBrief();
}
function renderCompare(){
 const box=$('compare-list');
 if(!state.compare.length)box.innerHTML='<div class="compare-empty">Chưa chọn phương án so sánh.</div>';
 else box.innerHTML=state.compare.map(id=>{
  const x=layouts.find(a=>a.id===id);
  return '<div class="compare-chip"><strong>'+x.title+'</strong><small>'+x.tag+'</small><button type="button" data-remove="'+x.id+'" aria-label="Xóa '+x.title+' khỏi so sánh">×</button></div>';
 }).join('');
 box.querySelectorAll('[data-remove]').forEach(b=>b.addEventListener('click',()=>{state.compare=state.compare.filter(id=>id!==b.dataset.remove);renderCompare();pick(state.layout)}));
}
function renderEffects(){
 const sorted=[...effects].sort((a,b)=>Number(b.pages.includes(state.page))-Number(a.pages.includes(state.page)));
 effectsGrid.innerHTML=sorted.map(x=>'<label class="effect-card'+(state.effects.includes(x.id)?' selected':'')+'">'+
 '<input type="checkbox" data-effect="'+x.id+'"'+(state.effects.includes(x.id)?' checked':'')+' aria-label="Chọn '+x.title+'">'+
 '<div class="effect-body"><div class="effect-top"><span>'+x.tier+'</span>'+(x.pages.includes(state.page)?'<small>Gợi ý</small>':'')+'</div>'+
 '<strong>'+x.title+'</strong><p>'+x.desc+'</p><span class="effect-tool">'+x.tool+(x.heavy?' · Thêm dependency khi duyệt':'')+'</span></div></label>').join('');
 $('effects-count').textContent=state.effects.length+' / 3';
 effectsGrid.querySelectorAll('[data-effect]').forEach(el=>el.addEventListener('change',()=>{
  if(el.checked){
   if(state.effects.length>=3){el.checked=false;$('effect-warning').textContent='Chỉ nên chọn tối đa ba hiệu ứng chính cho một màn hình. Hãy bỏ một hiệu ứng trước.';return;}
   state.effects.push(el.dataset.effect);
  }else state.effects=state.effects.filter(x=>x!==el.dataset.effect);
  $('effect-warning').textContent=state.effects.some(id=>effects.find(e=>e.id===id).heavy)?'Có hiệu ứng chuyên biệt: cần phê duyệt concept và đánh giá performance/fallback trước khi thêm thư viện runtime.':'';
  renderEffects();updateBrief();
 }));
}
function buildBrief(){
 const x=layouts.find(v=>v.id===state.layout);
 const selected=state.effects.map(id=>effects.find(v=>v.id===id));
 const comparison=state.compare.map(id=>layouts.find(x=>x.id===id).title).join(', ')||'Chưa so sánh';
 const owner=state.page==='home'?'Trang chủ, header và footer đang được bảo vệ: mọi thay đổi lớn cần duyệt riêng.':'Header/footer và dữ liệu CMS hiện tại được giữ; chỉ thay trong route được duyệt.';
 const effectLine=selected.length?selected.map(e=>'- '+e.title+' ('+e.tool+') — '+e.desc).join('\n'):'- Chưa chọn, triển khai không hiệu ứng đặc biệt cho tới khi duyệt.';
 const goal=$('goal-input').value.trim()||'Xây dựng trải nghiệm hữu ích, rõ ràng và có bản sắc.';
 const protect=$('constraints-input').value.trim()||'Không thay đổi dữ liệu, SEO, production hoặc phần không thuộc phạm vi.';
 return [
 '# [DESIGN REVIEW] '+pageText[state.page]+' — '+x.title,
 '',
 '## Outcome',
 goal,
 '',
 '## Selected direction',
 '- Trang: '+pageText[state.page],
 '- Phong cách: '+x.title+' / '+x.tag,
 '- Mục đích: '+x.why,
 '- Công cụ: '+x.tool,
 '- So sánh đã lưu: '+comparison,
 '',
 '## Effects (maximum three)',
 effectLine,
 '',
 '## Protected scope',
 owner,protect,
 '',
 '## Required implementation contract',
 '- Đọc: docs/15-design-delivery/00-START-HERE.md, 02-PTS-VISUAL-LANGUAGE.md, 03-VISUAL-ACCEPTANCE.md, 08-DESIGN-CATALOG.md.',
 '- Đọc: docs/02-development/public-ui-ux-design-system.md và các ADR, AGENTS.md hiện hành.',
 '- Dùng dữ liệu CMS/API và ảnh có nguồn xác minh; không bịa dự án/thông số.',
 '- Chỉ nghiên cứu direction trong lab trước; implementation cần owner chọn phương án và phạm vi.',
 '- Giữ functional/data/SEO parity, mobile/accessibility, 0/1/many data states.',
 '',
 '## Definition of done',
 '- [ ] Preview hoạt động được trên route hoặc worktree cụ thể; một H1; no horizontal overflow.',
 '- [ ] Screenshot desktop 1440px + mobile 390px, sau khi scroll warm lazy media, đủ first/mid/end.',
 '- [ ] Các CTA, navigation, gallery, form, các trạng thái hoạt động.',
 '- [ ] Reduced motion, focus, keyboard, fallback và performance đã được kiểm tra.',
 '- [ ] Một reviewer độc lập nêu điều tốt/chưa tốt, có vòng sửa và owner/design APPROVE.',
 '- [ ] PR/link issue/commit, xác nhận integration preview và worktree disposition.',
 '',
 '## Skill routing',
 '- Design exploration: frontend-design + ui-ux-pro-max + brand + design-system.',
 '- Implementation: Nuxt/Vue + Tailwind + Motion for Vue (có sẵn).',
 '- Review: web-design-guidelines + Playwright + PTS visual acceptance.',
 '- GSAP/TresJS: chỉ thêm ở task riêng nếu special interaction đã duyệt.',
 '',
 '## Evidence',
 '- Live layout references: docs/15-design-delivery/layout-lab.html; reference.html; design-picker.html.',
 '- Source/route, latest screenshot, real data, issue/PR link: bổ sung sau khi làm.',
 '',
 '> Generated by PTS Design Picker. This is a draft direction, not an approval or shipped feature.',
 ].join('\n');
}
function updateBrief(){
 $('brief-text').value=buildBrief();
}
document.querySelectorAll('[data-filter]').forEach(b=>b.addEventListener('click',()=>{
 state.filter=b.dataset.filter;
 document.querySelectorAll('[data-filter]').forEach(x=>x.setAttribute('aria-pressed',String(x===b)));
 renderGrid();
}));
$('page-select').addEventListener('change',e=>{
 state.page=e.target.value;
 state.effects=[];
 $('effect-warning').textContent='';
 pick(pageBest[state.page]||'editorial');
});
$('compare-button').addEventListener('click',()=>{
 if(state.compare.includes(state.layout))return;
 if(state.compare.length>=3){const removed=state.compare.shift();void removed;}
 state.compare.push(state.layout);
 renderCompare();pick(state.layout);
});
for(const id of ['goal-input','constraints-input'])$(id).addEventListener('input',updateBrief);
$('copy-brief').addEventListener('click',async()=>{
 let ok=false;
 try{if(navigator.clipboard&&window.isSecureContext){await navigator.clipboard.writeText($('brief-text').value);ok=true;}}catch{}
 if(!ok){
  $('brief-text').focus();$('brief-text').select();
  try{ok=document.execCommand('copy')}catch{}
 }
 $('copy-status').textContent=ok?'Đã sao chép brief.':'Không tự sao chép được từ file local: văn bản đã được chọn, bấm Ctrl+C.';
});
$('download-brief').addEventListener('click',()=>{
 const blob=new Blob([$('brief-text').value],{type:'text/markdown;charset=utf-8'});
 const a=document.createElement('a');
 const url=URL.createObjectURL(blob);
 a.href=url;a.download='PTS-'+state.page+'-'+state.layout+'-design-brief.md';
 document.body.appendChild(a);a.click();a.remove();
 setTimeout(()=>URL.revokeObjectURL(url),1200);
 $('copy-status').textContent='Đã yêu cầu tải tệp brief Markdown.';
});
renderCompare();
pick(state.layout);
})();