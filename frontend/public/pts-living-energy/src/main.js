import * as THREE from 'three';
import {OrbitControls} from 'three/addons/controls/OrbitControls.js';
import {GLTFLoader} from 'three/addons/loaders/GLTFLoader.js';
import {RoomEnvironment} from 'three/addons/environments/RoomEnvironment.js';
import {MeshoptDecoder} from 'three/addons/libs/meshopt_decoder.module.js';
import {createRoofLayers} from './roof-layers.js';
import {createUtilitySource} from './utility-source.js';
import {createPowerMap} from './power-map.js';
import {createEnergyTimeline} from './timeline.js';
import {batchStaticFoliage,batchStaticDetails} from './scene-batching.js';
import {solarAt,simulatedDate,siteParts,formatSolarTime,SOLAR_SITE} from './solar-clock.js';
import {createStaticLayer,MOVING_LAYER} from './static-layer.js';

const $=selector=>document.querySelector(selector);
const stage=$('#scenePanel');
const canvas=$('#energyCanvas');
const loading=$('#loadingOverlay');
const poster=$('#ptsPoster');
const hotspotLayer=$('#hotspotLayer');
const popup=$('#objectPopover');
const reducedMotion=window.matchMedia('(prefers-reduced-motion: reduce)');
const viewerParams=new URLSearchParams(window.location.search);
const embeddedMode=viewerParams.get('embed')==='1';
const livingMode=!viewerParams.has('classic');
// Living energy flow on the homepage; the frame governor turns it off if the device cannot hold it.
let ambientFlow=embeddedMode&&!reducedMotion.matches;
if(livingMode)document.body.classList.add('is-living-experience');
let solarMode=livingMode?'live':'simulation';
let solarSample=null,solarStamp='',lastSolarSync=0;
let hostVisible=!embeddedMode||window.parent===window,animationFrameId=0,animationTimerId=0,hostScrolling=false,hostExpanded=false;
function setHostExpanded(expanded){
 hostExpanded=expanded;
 window.parent.postMessage({type:'pts-living-energy:expand',expanded},window.location.origin);
 document.querySelector('#energyCanvas').style.touchAction=expanded?'none':'pan-y';
 const button=document.querySelector('#fullscreenBtn');
 button.classList.toggle('is-active',expanded);
 button.setAttribute('aria-pressed',String(expanded));
}
if(embeddedMode){
 window.addEventListener('message',event=>{
  if(event.source!==window.parent||event.origin!==window.location.origin)return;
  if(event.data?.type!=='pts-living-energy:visibility'||typeof event.data.visible!=='boolean')return;
  hostVisible=event.data.visible;
  if(hostVisible)startAnimation();
  else if(readyPosted)stopAnimation();
 });
 // Yield the shared main thread while the host page scrolls.
 window.addEventListener('message',event=>{
  if(event.source!==window.parent||event.origin!==window.location.origin)return;
  if(event.data?.type!=='pts-living-energy:scrolling')return;
  hostScrolling=event.data.scrolling===true;
  if(hostScrolling)stopAnimation();
  else startAnimation();
 });
 document.addEventListener('visibilitychange',()=>{
  if(document.hidden)stopAnimation();
  else if(!document.hidden)startAnimation();
 });
}
// Homepage story: the host page tells the story beside the model and steers it by chapter.
const embedStory={intro:embeddedMode,flight:false};
const EMBED_CHAPTERS={day:{device:'pv',orbit:-.16,height:1.14},night_storage:{device:'battery',orbit:.13,height:1},night_grid:{device:'grid',orbit:.27,height:.88},backup:{device:'priority',orbit:-.10,height:1.03}};
function setEmbedIntro(on){
 embedStory.intro=on;
 document.body.classList.toggle('embed-intro',on);
 document.body.classList.toggle('living-exploring',!on);
 if(on)popup.hidden=true;
 sceneDirty=true;startAnimation();
}
function showEmbedChapter(id){
 const shot=EMBED_CHAPTERS[id];
 if(!shot||!state.ready)return;
 state.battery=true;
 if(id==='backup')state.backupDaylight=false;
 activeMode(id,true);
 popup.hidden=true;
 const portrait=stage.clientWidth<620;
 desiredTarget.copy(targetBase).lerp(anchorOf(shot.device),portrait?.1:.3);
 const offset=cameraBase.clone().sub(targetBase).applyAxisAngle(new THREE.Vector3(0,1,0),shot.orbit*(portrait?.45:1));
 offset.y*=portrait?1+(shot.height-1)*.5:shot.height;
 desiredCamera.copy(desiredTarget).add(offset.multiplyScalar(portrait?1.04:1.04));
 if(reducedMotion.matches){camera.position.copy(desiredCamera);controls.target.copy(desiredTarget);tweenCamera=false;}
 else{tweenCamera=true;embedStory.flight=true;}
 sceneDirty=true;startAnimation();
}
if(embeddedMode&&window.parent!==window){
 document.body.classList.add('embed-intro');
 window.addEventListener('message',event=>{
  if(event.source!==window.parent||event.origin!==window.location.origin)return;
  if(event.data?.type==='pts-living-energy:chapter')showEmbedChapter(event.data.id);
  else if(event.data?.type==='pts-living-energy:intro')setEmbedIntro(event.data.on===true);
 });
 // A tap or a real drag means the visitor is using the model. A scroll starting on it is neither.
 const tellHost=()=>window.parent.postMessage({type:'pts-living-energy:interaction'},window.location.origin);
 document.addEventListener('click',tellHost,{capture:true,passive:true});
 document.addEventListener('pointermove',event=>{if(event.buttons)tellHost();},{passive:true});
}
let modelNorthOffsetDegrees=0;
let livingChapterIndex=0;
const labels={
 all:{index:'00',title:'Toàn bộ hệ thống',description:'Quan sát mối liên hệ giữa mặt trời, inverter, lưu trữ, điện lưới và tải trong nhà.'},
 pv:{index:'01',title:'Tấm pin năng lượng',description:'Tấm pin nhận ánh nắng và tạo điện một chiều (DC) đưa xuống inverter.'},
 inverter:{index:'02',title:'Inverter hybrid',description:'Thiết bị điều phối và biến đổi điện. Các chế độ lưu trữ/backup phụ thuộc thiết bị và thiết kế tương thích.'},
 battery:{index:'03',title:'Pin lưu trữ',description:'Nguồn điện lưu trữ có thể cấp cho inverter trong những cấu hình cho phép.'},
 house:{index:'04',title:'Phụ tải trong nhà',description:'Trong trạng thái bình thường, tải được cấp điện theo nguồn đang hoạt động. Hệ thống dự phòng chỉ cấp các tải được thiết kế ưu tiên.'},
 grid:{index:'05',title:'Điểm đấu nối lưới',description:'Nguồn điện lực đi vào công tơ và bộ chuyển nguồn/gateway.'},
 priority:{index:'06',title:'Tải ưu tiên',description:'Chỉ những tải được đấu nối vào ngõ backup phù hợp mới tiếp tục được cấp điện khi mất lưới.'},
 meter:{index:'07',title:'Công tơ điện lực',description:'Công tơ đo điện năng tại điểm giao nhận với lưới. Vị trí và cấu hình thực tế theo đơn vị điện lực.'},
 gateway:{index:'08',title:'Bộ chuyển nguồn / ATS',description:'Thiết bị chuyển nguồn và cách ly khi mất lưới, chỉ có tác dụng nếu phù hợp với inverter và cấu hình bảo vệ.'},
 main:{index:'09',title:'Tủ điện chính',description:'Tủ phân phối các mạch phụ tải bình thường. Kết cấu và đấu nối thực tế phải tuân theo bản thiết kế điện.'},
 prioritypanel:{index:'10',title:'Tủ tải ưu tiên',description:'Nhánh điện dành cho các tải được chọn. Chế độ backup chỉ cấp các tải thuộc nhánh này.'}
};
labels.grid.title='Cột điện · Nguồn điện lưới';
labels.grid.description='Nguồn hạ áp bên ngoài đi từ cột điện, qua cặp dây cấp có vỏ bọc, đầu nhận điện và công tơ.';
const scenarioData={
 day:{title:'Ban ngày',headline:'Điện mặt trời cấp tải',flow:'PV → Inverter → Phụ tải',copy:'Điện mặt trời đi qua inverter để cấp các phụ tải trong nhà.',chip:'DAYLIGHT / PV MODE',poster:'day',batteryRequired:false,night:false,backup:false,pathsKey:'day'},
 night_storage:{title:'Đêm dùng pin',headline:'Pin lưu trữ cấp tải',flow:'Pin lưu trữ → Inverter → Phụ tải',copy:'Ban đêm, nếu có pin và thiết bị phù hợp, pin lưu trữ cấp điện qua inverter cho các phụ tải.',chip:'NIGHT / STORAGE MODE',poster:'night_storage',batteryRequired:true,night:true,backup:false,pathsKey:'nightWithBattery'},
 night_grid:{title:'Đêm dùng lưới',headline:'Điện lưới cấp tải',flow:'Cột điện → công tơ → phân phối AC / inverter → tải',copy:'Trong tình huống này, nguồn điện lưới từ cột đi qua cáp cấp và công tơ vào hệ thống điện AC. Tủ phân phối và inverter liên kết theo thiết kế cụ thể.',chip:'NIGHT / GRID MODE',poster:'night_grid',batteryRequired:false,night:true,backup:false,pathsKey:'nightWithoutBattery'},
 backup:{title:'Mất điện',headline:'Chỉ tải ưu tiên còn điện',flow:'Pin → Inverter backup → Tải ưu tiên',copy:'Mô phỏng thiết bị có ngõ backup tương thích: lưới bị cách ly, pin cấp điện qua inverter đến tải ưu tiên, không phải toàn bộ ngôi nhà.',chip:'BACKUP / ISOLATED GRID',poster:'backup',batteryRequired:true,night:true,backup:true,pathsKey:'backup'}
};
const state={scenario:'day',battery:true,flow:true,labels:true,eco:embeddedMode||reducedMotion.matches,technical:false,sun:true,backupDaylight:true,houseLights:true,xray:false,xrayPreviousTechnical:false,selected:'all',rotate:false,ready:false,contract:null};
const powerMap=createPowerMap($('#powerMap'),device=>{
 selectDevice(device);
 if(window.innerWidth<701)stage.scrollIntoView({behavior:reducedMotion.matches?'auto':'smooth',block:'center'});
});
// Unifies the 24-hour scrubber from V02, V05 electrical routes and Codex night lighting.
let fromTimeline=false;
const timeMachine=createEnergyTimeline({
 onHourChange:(hour,isDay)=>{
  if(journey.active)stopJourney();
  if(livingMode){solarMode='simulation';refreshSolar(true);isDay=solarSample.daylight;}
  fromTimeline=true;
  try{
   if(state.scenario==='backup'){
    state.backupDaylight=isDay;
    activeMode('backup');
   }else activeMode(isDay?'day':state.battery?'night_storage':'night_grid');
  }finally{fromTimeline=false;}
 },
 getCaption:(hour,isDay)=>state.scenario==='backup'?'Mất lưới · Cách ly tải thường'
  :isDay?'Ban ngày · Quang điện'
  :state.scenario==='night_storage'?'Ban đêm · Pin lưu trữ':'Ban đêm · Điện lưới',
 onPlay:()=>{if(journey.active)stopJourney();}
});
/**
 * Atmospheric clock is independent of the electrical topology.
 * LIVE uses site-local date/time. Scrub uses that site's date with a user hour.
 * No weather, inverter telemetry or site-verified model orientation is inferred.
 */
function refreshSolar(force=false){
 if(!livingMode)return null;
 const now=new Date();
 const stamp=solarMode==='live'
  ?'live:'+Math.floor(now.getTime()/15000)
  :'sim:'+timeMachine.hour+':'+siteParts(now).day;
 if(!force && solarStamp===stamp && solarSample)return solarSample;
 const previousDay=solarSample?.daylight;
 solarSample=solarAt(solarMode==='live'?now:simulatedDate(timeMachine.hour,now));
 solarStamp=stamp;
 const light=solarSample.daylight ? solarSample.golden?'dusk':'day':'night';
 document.body.dataset.atmosphere=light;
 if(embeddedMode&&window.parent!==window)window.parent.postMessage({type:'pts-living-energy:atmosphere',atmosphere:light},window.location.origin);
 stage.classList.toggle('is-night',!solarSample.daylight);
 stage.classList.toggle('is-golden-hour',solarSample.golden);
 $('#livingClockTime').textContent=formatSolarTime(solarSample.parts);
 $('#livingClockState').textContent=solarMode==='live'?'● THEO GIỜ HIỆN TẠI':'◷ GIỜ MÔ PHỎNG';
 $('#livingClockLocation').textContent=embeddedMode?`${SOLAR_SITE.name} · mô phỏng ánh sáng`:SOLAR_SITE.name;
 $('#livingUseLive').setAttribute('aria-pressed',String(solarMode==='live'));
 $('#livingUseSimulation').setAttribute('aria-pressed',String(solarMode==='simulation'));
 $('#livingClockScrubber').hidden=solarMode==='live';
 $('#livingTimeSlider').value=String(timeMachine.hour);
 $('#livingSunInfo').textContent='Góc cao mặt trời: '+solarSample.altitude.toFixed(1)+'°';
 if(previousDay!==undefined && previousDay!==solarSample.daylight)lastSolarSync=-1;
 return solarSample;
}
const scene=new THREE.Scene();
const utilitySource=createUtilitySource(THREE,scene);
const camera=new THREE.PerspectiveCamera(35,1,.1,1000);
camera.position.set(19,16,20);
// Embedded (homepage) renders at DPR 1 on the low-power path; MSAA is the costliest per-frame GPU step there.
const renderer=new THREE.WebGLRenderer({canvas,antialias:!embeddedMode,alpha:true,stencil:false,powerPreference:'high-performance'});
renderer.setClearColor(0x000000,0);
renderer.outputColorSpace=THREE.SRGBColorSpace;
renderer.toneMapping=THREE.ACESFilmicToneMapping;
renderer.toneMappingExposure=1.2;
renderer.shadowMap.enabled=!state.eco||embeddedMode;
renderer.shadowMap.type=THREE.PCFShadowMap;
// Light and scene geometry change far less often than the WebGL camera frame rate.
// Do not re-render all shadow-casting geometry on every frame.
renderer.shadowMap.autoUpdate=false;
renderer.shadowMap.needsUpdate=true;
// Blender world lighting is not embedded in GLB. Generate a tiny local studio
// reflection environment once to bring the authored PBR metal/glass surfaces to life.
const envGenerator=new THREE.PMREMGenerator(renderer);
const envRoom=new RoomEnvironment();
const studioEnvironment=envGenerator.fromScene(envRoom,.04);
scene.environment=studioEnvironment.texture;
scene.environmentIntensity=.22;
envRoom.dispose?.();
envGenerator.dispose();
const hemi=new THREE.HemisphereLight(0xffffff,0x7e93a1,3.1);
const key=new THREE.DirectionalLight(0xfff3dc,3.3);
key.position.set(-9,14,11);
key.castShadow=!state.eco||embeddedMode;
key.shadow.mapSize.set(1024,1024);
Object.assign(key.shadow.camera,{left:-15,right:15,top:15,bottom:-15,near:.1,far:65});
if(embeddedMode){key.shadow.mapSize.set(2048,2048);key.shadow.radius=3;}
key.shadow.bias=-.00026;
key.shadow.normalBias=.035;
const fill=new THREE.DirectionalLight(0xb8d6ff,1.2);
fill.position.set(12,11,-8);
scene.add(hemi,key,fill);
const sunWorldGoal=new THREE.Vector3();
const lastShadowSunPosition=new THREE.Vector3(1e6,1e6,1e6);
let renderFrameNumber=0,shadowUpdateFrame=-20;
const sunColorGoal=new THREE.Color();
const controls=new OrbitControls(camera,canvas);
controls.enableDamping=true;
controls.dampingFactor=.055;
controls.enablePan=true;
controls.minDistance=3;
controls.maxDistance=65;
controls.maxPolarAngle=Math.PI*.495;
controls.autoRotateSpeed=.55;
const loader=new GLTFLoader();
// The GLB ships meshopt-compressed geometry (EXT_meshopt_compression); the decoder is vendored locally.
loader.setMeshoptDecoder(MeshoptDecoder);
const raycaster=new THREE.Raycaster();
const pointer=new THREE.Vector2();
const clock=new THREE.Clock();
const anchors=new Map();
// A subtle 3D focus beacon anchors the guided tour to real scene nodes,
// rather than drawing ungrounded 2D decorations over the model.
const focusBeacon=new THREE.Group();
const focusRing=new THREE.Mesh(
 new THREE.TorusGeometry(.42,.018,6,56),
 new THREE.MeshBasicMaterial({color:0xe0b654,transparent:true,opacity:.88,depthWrite:false,side:THREE.DoubleSide})
);
focusRing.rotation.x=Math.PI/2;
const focusOuter=new THREE.Mesh(
 new THREE.TorusGeometry(.58,.010,6,56),
 new THREE.MeshBasicMaterial({color:0x72cdd7,transparent:true,opacity:.58,depthWrite:false,side:THREE.DoubleSide})
);
focusOuter.rotation.x=Math.PI/2;
focusBeacon.add(focusRing,focusOuter);
focusBeacon.visible=false;
scene.add(focusBeacon);
const hotspotElements=new Map();
const routedObjects=[];
const routeAnimations=[];
const batteryObjects=[];
const gridServiceWires=[];
const sunObjects=[];
const sunActions=[];
const technicalHighlights=new Map();
const meshForPicking=[];
const xrayShells=[];
const houseLightZones=[];
let entrySoffitLamp=null;
let entrySoffitLight=null;
const sourceNodes=new Map(); // original Blender names survive GLTFLoader in userData.name
let foliageBatch={drawGroups:0,instances:0,candidates:0};
let detailBatch={drawGroups:0,sourceMeshes:0},pvBatch=null,shellBatch=null;
const roofLayers=createRoofLayers(()=>syncRoutes());
let mixer=null;
let model=null;
let cameraBase=new THREE.Vector3(18,15,20);
let targetBase=new THREE.Vector3(0,1.7,0);
let desiredCamera=cameraBase.clone();
let desiredTarget=targetBase.clone();
let tweenCamera=false,tweenRemaining=Infinity;
let livingStageVisible=true;

function routeOf(name){
 if(!/^(FLOW|GRAPH|ARROW|PHOTON)__/.test(name))return null;
 const segments=name.split('__');
 return segments[1] ? segments[1].split(/[ .]/)[0] : null;
}
function activeRoutes(){
 if(!state.contract)return [];
 return state.contract.routes[scenarioData[state.scenario].pathsKey]||[];
}
function findBlenderObject(name){return sourceNodes.get(name)||model?.getObjectByName(name)||null;}
function createHouseLighting(){
 const glowCanvas=document.createElement('canvas');
 glowCanvas.width=glowCanvas.height=128;
 const glowContext=glowCanvas.getContext('2d');
 const glowGradient=glowContext.createRadialGradient(64,64,5,64,64,63);
 glowGradient.addColorStop(0,'rgba(255,188,111,0.95)');
 glowGradient.addColorStop(.55,'rgba(255,161,78,0.66)');
 glowGradient.addColorStop(1,'rgba(255,145,58,0)');
 glowContext.fillStyle=glowGradient;
 glowContext.fillRect(0,0,128,128);
 const glowTexture=new THREE.CanvasTexture(glowCanvas);
 glowTexture.colorSpace=THREE.SRGBColorSpace;
 const definitions=[
  {role:'priority',anchor:'ROOM / Priority glass fascia',fixtures:['ROOM / Priority glass fascia','ROOM / Priority shelf lit'],intensity:27,emission:3.6,glowSize:[1.46,.88],glowOpacity:.62},
  {role:'normal',anchor:'ROOM / Nonpriority glazing',fixtures:['ROOM / Nonpriority glazing'],intensity:23,emission:3.2,glowSize:[.52,.82],glowOpacity:.52}
 ];
 for(const definition of definitions){
  const anchor=findBlenderObject(definition.anchor);
  if(!anchor)continue;
  const light=new THREE.PointLight(0xffb96b,0,5.8,2);
  light.position.copy(anchor.localToWorld(new THREE.Vector3(0,0,-.72)));
  light.castShadow=false;
  // Every PointLight adds a per-pixel loop to every material. The embed keeps the emissive glow only.
  if(!embeddedMode)scene.add(light);
  const glow=new THREE.Mesh(new THREE.PlaneGeometry(...definition.glowSize),new THREE.MeshBasicMaterial({
   map:glowTexture,color:'#fff1da',transparent:true,opacity:0,depthWrite:false,side:THREE.DoubleSide,toneMapped:false
  }));
  glow.position.copy(anchor.localToWorld(new THREE.Vector3(0,0,.43)));
  glow.renderOrder=1;
  scene.add(glow);
  const materials=[];
  for(const name of definition.fixtures){
   const fixture=findBlenderObject(name);
   if(!fixture?.material)continue;
   const prepare=source=>{
    const material=source.clone();
    if(material.emissive){
     material.emissive.set('#ffb15b');
     material.emissiveIntensity=0;
     materials.push({material,baseOpacity:source.opacity});
    }
    return material;
   };
   fixture.material=Array.isArray(fixture.material)?fixture.material.map(prepare):prepare(fixture.material);
  }
  houseLightZones.push({role:definition.role,light,intensity:definition.intensity,emission:definition.emission,materials,glow,glowOpacity:definition.glowOpacity});
 }
}
function syncHouseLighting(){
 const night=livingMode?refreshSolar().daylight===false:(state.scenario==='backup'?!state.backupDaylight:scenarioData[state.scenario].night);
 const backup=state.scenario==='backup';
 const row=$('#houseLightsRow');
 row.hidden=!night;
 $('#houseLightsToggle').checked=state.houseLights;
 $('#houseLightsHint').textContent=backup
  ? (state.houseLights?'Chỉ đèn tải ưu tiên sáng khi mất lưới':'Đèn tải ưu tiên đang tắt')
  : (state.houseLights?'Minh họa đèn sáng theo nguồn đang cấp tải':'Đèn trong nhà đang tắt');
 if(entrySoffitLamp&&entrySoffitLight){
  const exteriorOn=night&&state.houseLights&&!backup;
  entrySoffitLamp.material.userData.ptsSoffitTarget=exteriorOn?1.65:0;
  entrySoffitLight.userData.targetIntensity=exteriorOn?8.5:0;
 }
 for(const zone of houseLightZones){
  const enabled=night&&state.houseLights&&(!backup||zone.role==='priority');
  zone.light.userData.targetIntensity=enabled?zone.intensity:0;
  zone.glow.material.userData.ptsHouseGlowTarget=enabled?zone.glowOpacity:0;
  for(const entry of zone.materials){
   entry.material.userData.ptsHouseLightTarget=enabled?zone.emission:0;
   entry.material.userData.ptsHouseOpacityTarget=enabled?Math.min(1,entry.baseOpacity+.3):entry.baseOpacity;
  }
 }
}
function syncRoutes(){
 const flowAllowed=state.flow&&!roofLayers.busy;
 const active=new Set(activeRoutes());
 // A stationary utility pole is never itself a power-flow animation:
 // only accent the service cable when the grid supplies the selected load.
 const gridImporting=state.scenario==='night_grid'&&flowAllowed;
 utilitySource.setActive(gridImporting);
 for(const wire of gridServiceWires){
  wire.material.emissive?.set(gridImporting?'#4caec1':'#000000');
  wire.material.emissiveIntensity=gridImporting?.38:0;
 }
 const gridLabel=$('#gridImportStatus');
 if(gridLabel){
  gridLabel.textContent=state.scenario==='backup'
   ?'Mất lưới · Đã cách ly'
   :state.scenario==='night_grid'
   ?'Đang cấp tải từ lưới'
   :state.scenario==='night_storage'
   ?'Lưới kết nối · Pin đang cấp tải'
   :'Lưới kết nối · PV đang cấp tải';
  gridLabel.dataset.mode=state.scenario==='backup'?'isolated':gridImporting?'importing':'standby';
 }
 for(const item of routedObjects){
  const isPhoton=item.name.startsWith('PHOTON__');
  item.visible=active.has(routeOf(item.name)) && flowAllowed && (!isPhoton || !reducedMotion.matches);
 }
 if(mixer){
  for(const item of routeAnimations){
   const shouldPlay=active.has(item.route) && flowAllowed && (!state.eco||embeddedMode) && !reducedMotion.matches;
   if(shouldPlay && !item.running){
    item.action.reset().play();item.running=true;
   }else if(!shouldPlay && item.running){
    item.action.stop();item.running=false;
   }
  }
 }
  for(const item of batteryObjects)item.visible=state.battery;
  const daylight=(state.scenario==='day'||(state.scenario==='backup'&&state.backupDaylight))&&state.sun&&(!livingMode||refreshSolar().daylight);
  for(const obj of sunObjects){
    const isDiagramSun=obj.name==='SUN__SOURCE_DISC'||obj.name==='SUN__ORBIT_GLYPH';
    obj.visible=daylight&&(!isDiagramSun||state.technical);
  }
  if(mixer)for(const item of sunActions){
    const play=daylight&&(!state.eco||embeddedMode)&&!reducedMotion.matches;
    if(play&&!item.running){item.action.reset().play();item.running=true;}
    else if(!play&&item.running){item.action.stop();item.running=false;}
  }
  const isolated=state.scenario==='backup';
  const grid=technicalHighlights.get('grid');
  if(grid?.material){grid.material.color.set(isolated?'#f0a358':'#74aea6');
    grid.material.emissive?.set(isolated?'#db7b38':'#386f6c');}
  const priority=technicalHighlights.get('priority');
  if(priority?.material){priority.material.color.set(isolated?'#f3bc66':'#65777b');
    priority.material.emissive?.set(isolated?'#e0b654':'#0b1a25');}
  const normal=technicalHighlights.get('normal');
  if(normal?.material){normal.material.color.set(isolated?'#53626b':'#d5a75d');
    normal.material.emissive?.set(isolated?'#080f16':'#8d6931');}
}
function activeMode(id,fromClick=false){
 if(fromClick && journey.active && !journey.applying)stopJourney();
 if(!scenarioData[id])return;
 if(livingMode&&fromClick){
  if(solarMode==='live')timeMachine.setHour(Math.round(siteParts(new Date()).hourFraction*4)/4);
  solarMode='simulation';solarStamp='';
 }
 if(id==='backup' && !state.battery)return;
 if(id==='night_storage')state.battery=true;
 if(id==='night_grid')state.battery=false;
 state.scenario=id;
 if(!fromTimeline && (!livingMode||solarMode==='simulation')){
  if(id==='day')timeMachine.setHour(12);
  else if(id==='night_storage'||id==='night_grid')timeMachine.setHour(22);
  else if(id==='backup')timeMachine.setHour(state.backupDaylight?12:22);
  if(fromClick)timeMachine.stop();
 }
 timeMachine.paint();
 const config=scenarioData[id];
 if(id==='night_grid')$('#v05TopologyText').textContent='Cột điện → dây cấp AC → công tơ → hệ phân phối và inverter → tải';
 $('#batteryToggle').checked=state.battery;
 document.querySelectorAll('[data-scenario]').forEach(button=>{
  const yes=button.dataset.scenario===id;
  button.classList.toggle('is-active',yes);
  button.setAttribute('aria-pressed',String(yes));
 });
 $('[data-scenario="backup"]').disabled=!state.battery;
 $('#clockLabel').textContent=config.title;
 $('#cycleStatus')?.replaceChildren(document.createTextNode(config.title));
 $('#currentState').textContent=config.headline;
 $('#currentSubState').textContent=config.flow;
 $('#scenarioCopy').textContent=id==='backup'&&state.backupDaylight
  ? 'Mất lưới ban ngày: mái PV vẫn nhận nắng nhưng không mặc định phát điện khi mất lưới. Chỉ tải ưu tiên có thể được cấp bởi hệ backup tương thích.'
  : config.copy;
 $('#sceneHeading').textContent='PTS Technical Energy · '+config.title;
 $('#sceneChip').lastChild.textContent=' '+config.chip;
 const night=livingMode?refreshSolar().daylight===false:(id==='backup'?!state.backupDaylight:config.night);
 stage.classList.toggle('is-night',night);
  const gridBanner=$('#gridFlowBanner');
  const gridImporting=id==='night_grid';
  const gridIsolated=id==='backup';
  gridBanner.hidden=!(gridImporting||gridIsolated);
  gridBanner.dataset.mode=gridIsolated?'isolated':'importing';
  stage.classList.toggle('has-grid-banner',!gridBanner.hidden);
  $('#gridFlowBannerTitle').textContent=gridIsolated?'ĐIỆN LƯỚI ĐÃ CÁCH LY':'ĐIỆN LƯỚI ĐANG CẤP TẢI';
  $('#gridFlowBannerPath').textContent=gridIsolated
   ?'Cột điện không cấp tải · Nhánh backup hoạt động độc lập'
   :'Cột điện → cáp cấp → công tơ → hệ điện nhà';
  // Update the sky immediately on slider input; do not depend on the next GPU render frame.
  const solarBrightness=timeMachine.brightness();
  stage.classList.toggle('is-golden-hour',livingMode?solarSample.golden:(!night&&solarBrightness>0&&solarBrightness<.85));
 syncHouseLighting();
 if(livingMode){
  $('#livingSceneCaption').querySelector('strong').textContent=config.headline;
  $('#livingSceneCaption').querySelector('span').textContent=config.copy;
  document.querySelectorAll('[data-living-mode]').forEach(button=>{
   const selected=button.dataset.livingMode===id;
   button.classList.toggle('is-active',selected);
   button.setAttribute('aria-pressed',String(selected));
  });
 }
 $('#backupTimeRow').hidden=id!=='backup'||(livingMode&&solarMode==='live');
 $('#backupDaylightToggle').checked=state.backupDaylight;
 stage.classList.toggle('is-backup',config.backup);
 const sunOn=!night&&state.sun;
 $('#solarAtmosphere').hidden=!sunOn;
 $('#v05TopologyText').textContent=config.backup
  ? 'Lưới cách ly · Pin → inverter backup → tải ưu tiên'
  : config.poster==='night_grid'
  ? 'Lưới → công tơ → gateway → inverter → tải'
  : config.poster==='night_storage'
  ? 'Pin lưu trữ (DC) → inverter → tải (AC)'
  : 'Ánh nắng → PV (DC) → inverter → tải (AC)';
 if(id==='backup'&&state.backupDaylight)
  $('#v05TopologyText').textContent='Lưới cách ly · Pin cấp tải ưu tiên · PV không mặc định phát điện';
 if(id==='night_grid')$('#v05TopologyText').textContent='Cột điện → dây cấp AC → công tơ → hệ phân phối và inverter → tải';
 poster.alt='Phối cảnh minh họa hệ thống năng lượng mặt trời';
 if(state.selected==='battery'&&!state.battery)selectDevice('inverter');
 syncRoutes();
 powerMap.render(id,state.battery,state.backupDaylight);
 window.__PTS_MODE=id;
}
function setBattery(enabled){
 state.battery=enabled;
 if(!enabled && state.scenario==='backup')activeMode('night_grid');
 else if(!enabled && state.scenario==='night_storage')activeMode('night_grid');
 else if(enabled && state.scenario==='night_grid')activeMode('night_storage');
 else activeMode(state.scenario);
}
function anchorOf(device){
 const lookup={pv:'HOTSPOT_PV',inverter:'HOTSPOT_INVERTER',battery:'HOTSPOT_BATTERY',
  house:'HOTSPOT_HOUSE',grid:'HOTSPOT_GRID',priority:'HOTSPOT_PRIORITY_LOAD',
  meter:'HOTSPOT_UTILITY_METER',gateway:'HOTSPOT_GATEWAY',main:'HOTSPOT_MAIN_PANEL',
  prioritypanel:'HOTSPOT_PRIORITY_PANEL'};
 const obj=anchors.get(lookup[device]);
 if(obj)return obj.getWorldPosition(new THREE.Vector3());
 return targetBase.clone();
}
function syncFocusBeacon(){
 const selected=state.selected;
 focusBeacon.visible=state.ready&&selected!=='all'&&state.labels;
 if(!focusBeacon.visible)return;
 focusBeacon.position.copy(anchorOf(selected));
 focusBeacon.position.y+=.21;
}
function selectDevice(device){
 if(journey.active && !journey.applying)stopJourney();
 if(!labels[device] || (device==='battery'&&!state.battery))return;
 state.selected=device;
 if(livingMode&&device!=='all'&&!embedStory.intro)document.body.classList.add('living-exploring');
 document.querySelectorAll('.device-menu [data-focus]').forEach(button=>{
  const selected=button.dataset.focus===device;
  button.classList.toggle('is-selected',selected);
  button.setAttribute('aria-pressed',String(selected));
 });
 if(device==='all')popup.hidden=true;
 else{
  $('#popoverTag').textContent='THIẾT BỊ '+labels[device].index;
  $('#popoverTitle').textContent=labels[device].title;
  $('#popoverDesc').textContent=labels[device].description;
  popup.hidden=false;
 }
 const sourceFocus=$('#utilityQuickFocus');
 if(sourceFocus){
  const focused=device==='grid';
  sourceFocus.setAttribute('aria-pressed',String(focused));
  sourceFocus.setAttribute('aria-label',focused?'Trở về góc nhìn toàn cảnh':'Phóng to cột điện và đường dây cấp nguồn lưới');
  sourceFocus.querySelector('span').textContent=focused?'Quay lại toàn cảnh ↙':'Xem gần đường điện vào nhà ↗';
 }
 if(!state.ready)return;
 syncFocusBeacon();
 desiredTarget.copy(device==='all'?targetBase:anchorOf(device));
 // Mobile source close-up uses actual Blender pole bounding box, not arbitrary scaling.
 if(device==='grid'&&stage.clientWidth<701){
  const pole=findBlenderObject('GRID_SOURCE / Low-voltage concrete utility pole');
  if(pole){
   const bounds=new THREE.Box3().setFromObject(pole);
   const center=bounds.getCenter(new THREE.Vector3());
   const h=bounds.getSize(new THREE.Vector3()).y;
   const factor=Math.max(1,h/5.8);
   desiredTarget.copy(center).add(new THREE.Vector3(-.20,.1,0));
   desiredCamera.copy(desiredTarget).add(new THREE.Vector3(6.6,5.8,8.2).multiplyScalar(factor*.88));
  }
 }else{
  const baseScale=device==='all'?1:((device==='priority'||device==='prioritypanel')?1.50:((device==='house'||device==='grid')?1.26:.86));
  const scale=baseScale*(stage.clientWidth<560?1.13:1);
  const offset=new THREE.Vector3(9*scale,7.8*scale,12*scale);
  desiredCamera.copy(device==='all'?cameraBase:desiredTarget.clone().add(offset));
 }
 controls.autoRotate=false;
 state.rotate=false;
 $('#autoRotateBtn').classList.remove('is-active');
 $('#autoRotateBtn').setAttribute('aria-pressed','false');
 // Software/mobile WebGL may render only a few frames per second. Snap utility
 // inspection to its correct framing rather than getting stuck halfway through.
 if(stage.clientWidth<701 && (device==='grid'||device==='all')){
  camera.position.copy(desiredCamera);
  controls.target.copy(desiredTarget);
  controls.update();
  tweenCamera=false;
 }else tweenCamera=true;
}
function hotspotLayout(){
 // Read the stage box once per frame; reading it per hotspot forces a layout each time.
 const rect=stage.getBoundingClientRect();
 for(const [id,node] of hotspotElements){
  node.hidden=!state.ready||!state.labels||(id==='battery'&&!state.battery)||(['meter','gateway','main','prioritypanel'].includes(id)&&!state.technical);
  if(node.hidden)continue;
  const p=anchorOf(id).project(camera);
  if(p.z<=-1||p.z>=1||Math.abs(p.x)>1.1||Math.abs(p.y)>1.1){node.hidden=true;continue;}
  const x=(p.x*.5+.5)*rect.width;
  const y=(-p.y*.5+.5)*rect.height;
  node.style.translate=`${x}px ${y}px`;node.__x=x;node.__y=y;
  node.classList.toggle('is-flipped',x>rect.width*.7);
 }
 const visible=Array.from(hotspotElements.values()).filter(el=>!el.hidden);
 visible.sort((a,b)=>Number(b.dataset.focus===state.selected)-Number(a.dataset.focus===state.selected));
 const displayed=[];
 for(const el of visible){
  const x=el.__x,y=el.__y;
  const hit=displayed.some(p=>Math.abs(p.x-x)<126 && Math.abs(p.y-y)<34);
  const label=el.querySelector('.hotspot-text');
  if(label)label.hidden=(hit&&el.dataset.focus!=='grid')||(stage.clientWidth<540 && el.dataset.focus!=='grid' && (state.selected==='all'||el.dataset.focus!==state.selected));
  if(!hit)displayed.push({x,y});
 }
}
function createHotspots(){
 for(const id of ['pv','inverter','battery','grid','house','priority','meter','gateway','main','prioritypanel']){
  const button=document.createElement('button');
  button.type='button';
  button.className='hotspot';
  button.dataset.focus=id;
  button.setAttribute('aria-label','Xem '+labels[id].title);
  const point=document.createElement('span');point.className='hotspot-point';
  const name=document.createElement('span');name.className='hotspot-text';name.textContent=id==='grid'?'Cột điện':labels[id].title;
  button.append(point,name);
  button.addEventListener('click',()=>selectDevice(id));
  hotspotLayer.appendChild(button);hotspotElements.set(id,button);
 }
}
/* Cinematic Solar Journey: source-aligned, no fictitious energy calculations. */
const journeyChapters=[
 {scenario:'day',device:'pv',title:'Nắng chạm mái nhà',tag:'CHƯƠNG 01 · NGUỒN NĂNG LƯỢNG',
  copy:'Ánh sáng chiếu lên các tấm quang điện. Hệ PV tạo dòng một chiều (DC) khi đủ điều kiện hoạt động.'},
 {scenario:'day',device:'inverter',title:'Năng lượng được chuyển đổi',tag:'CHƯƠNG 02 · ĐIỀU PHỐI NĂNG LƯỢNG',
  copy:'Inverter biến đổi điện phù hợp để cấp phụ tải.'},
 {scenario:'night_storage',device:'battery',title:'Ánh sáng tắt. Năng lượng còn lại.',tag:'CHƯƠNG 03 · LƯU TRỮ THÔNG MINH',
  copy:'Khi mặt trời lặn, hệ lưu trữ tương thích có thể cấp điện qua inverter. Thời gian sử dụng phụ thuộc pin, mức sạc và tải.'},
 {scenario:'backup',device:'priority',title:'Giữ sáng những điều thiết yếu.',tag:'CHƯƠNG 04 · DỰ PHÒNG',
  copy:'Trong cấu hình có backup và cách ly lưới, nhánh tải ưu tiên vẫn có thể được cấp điện. Không phải mọi tải trong nhà đều còn điện.'}
];
const journey={active:false,index:0,elapsed:0,paused:false,applying:false,pending:false,seconds:8.5};
const journeyUI={overlay:$('#journeyOverlay'),launch:$('#journeyLaunch'),title:$('#journeyTitle'),
 copy:$('#journeyCopy'),index:$('#journeyIndex'),progress:$('#journeyProgress'),
 chapters:$('#journeyChapters'),pause:$('#journeyPause')};
// An immersive presentation uses the same scene, not a second renderer.
const presentation={active:false,tourOwns:false};
function setPresentation(active){
 presentation.active=Boolean(active);
 document.querySelector('.workspace').classList.toggle('is-presentation',presentation.active);
 const button=$('#presentationToggle');
 button.classList.toggle('is-active',presentation.active);
 button.setAttribute('aria-pressed',String(presentation.active));
 button.setAttribute('aria-label',presentation.active?'Thoát chế độ trình diễn 3D':'Bật chế độ trình diễn 3D');
 button.title=presentation.active?'Hiện lại bảng điều khiển':'Tập trung vào mô hình · ẩn bảng điều khiển';
 // Recompute the camera framing immediately after the workspace changes size.
 resize();
 window.requestAnimationFrame(resize);
}
function stopJourney(){
 journey.pending=false;
 if(!journey.active)return;
 journey.active=false;journey.applying=false;journey.paused=false;
 journeyUI.overlay.hidden=true;
 journeyUI.launch.setAttribute('aria-pressed','false');
 stage.classList.remove('is-journey');
 if(presentation.tourOwns){presentation.tourOwns=false;setPresentation(false);}
}
function setJourneyChapter(index){
 if(!journey.active)return;
 journey.index=(index+journeyChapters.length)%journeyChapters.length;
 journey.elapsed=0;
 const item=journeyChapters[journey.index];
 journey.applying=true;
 try{
  state.battery=true;
  if(item.scenario==='backup')state.backupDaylight=false;
  activeMode(item.scenario);
  selectDevice(item.device);
  // Cinematic close-up keeps context: avoid cutting off the whole roof or ESS.
  // Ordinary device clicks retain their existing precise camera behaviour.
  const isPortrait=stage.clientWidth<620;
  desiredTarget.copy(targetBase).lerp(anchorOf(item.device),isPortrait?.08:(presentation.active?.26:.43));
  const contextualOffset=cameraBase.clone().sub(targetBase);
  const sceneDepth=contextualOffset.length();
  // An intentional orbit angle for each chapter avoids four nearly identical
  // shots. Mobile uses smaller changes to retain complete equipment context.
  const orbitAngles=isPortrait?[-.05,.06,.10,-.04]:[-.16,.13,.27,-.10];
  const orbitHeights=isPortrait?[1.06,.99,.96,1.02]:[1.14,1.0,.88,1.03];
  const shotOffset=contextualOffset.clone().applyAxisAngle(new THREE.Vector3(0,1,0),orbitAngles[journey.index]);
  shotOffset.y*=orbitHeights[journey.index];
  desiredCamera.copy(desiredTarget).add(shotOffset.clone().multiplyScalar(isPortrait?1.42:(presentation.active?.91:.99)));
  // Cinematic safe areas: mobile caption covers the bottom; desktop caption occupies the left.
  // Shift the camera and orbit target together, preserving the existing 3D model.
  if(presentation.active&&isPortrait){
   const lift=sceneDepth*.16;
   desiredTarget.y-=lift;
   desiredCamera.y-=lift;
  }else if(presentation.active){
   const screenRight=new THREE.Vector3().crossVectors(new THREE.Vector3(0,1,0),shotOffset).normalize();
   const sideShift=screenRight.multiplyScalar(-sceneDepth*.16);
   desiredTarget.add(sideShift);
   desiredCamera.add(sideShift);
  }
  if(reducedMotion.matches){
   camera.position.copy(desiredCamera);
   controls.target.copy(desiredTarget);
   tweenCamera=false;
  }else tweenCamera=true;
  popup.hidden=true;
  journeyUI.index.textContent=item.tag;
  journeyUI.title.textContent=item.title;
  journeyUI.copy.textContent=item.copy;
  if(!reducedMotion.matches&&typeof journeyUI.title.animate==='function'){
   const shotText=[{opacity:0,transform:'translateY(8px)'},{opacity:1,transform:'translateY(0)'}];
   journeyUI.title.animate(shotText,{duration:420,easing:'cubic-bezier(.16,1,.3,1)'});
   journeyUI.copy.animate(shotText,{duration:510,easing:'cubic-bezier(.16,1,.3,1)'});
  }
  journeyUI.progress.style.transform='scaleX(0)';
  journeyUI.chapters.querySelectorAll('button').forEach((button,i)=>{
   button.classList.toggle('is-current',i===journey.index);
   button.setAttribute('aria-pressed',String(i===journey.index));
  });
 }finally{journey.applying=false;}
}
function startJourney(){
 if(!state.ready){journey.pending=true;return;}
 if(roofLayers.busy)setRoofLayersExpanded(false,{instant:true});
 if(journey.active){stopJourney();return;}
 if(!presentation.active){presentation.tourOwns=true;setPresentation(true);}
 journey.active=true;journey.paused=reducedMotion.matches;journey.elapsed=0;
 timeMachine.stop();
 journeyUI.overlay.hidden=false;
 journeyUI.launch.setAttribute('aria-pressed','true');
 stage.classList.add('is-journey');
 controls.autoRotate=false;state.rotate=false;
 $('#autoRotateBtn').classList.remove('is-active');
 $('#autoRotateBtn').setAttribute('aria-pressed','false');
 journeyUI.pause.textContent=journey.paused?'▶':'Ⅱ';
 journeyUI.pause.setAttribute('aria-label',journey.paused?'Tiếp tục hành trình':'Tạm dừng');
 setJourneyChapter(0);
 stage.scrollIntoView({behavior:reducedMotion.matches?'instant':'smooth',block:'center'});
}
function advanceJourney(){
 if(journey.index+1>=journeyChapters.length){stopJourney();return;}
 setJourneyChapter(journey.index+1);
}
function tickJourney(dt){
 if(!journey.active||journey.paused||!state.ready)return;
 journey.elapsed+=dt;
 journeyUI.progress.style.transform='scaleX('+Math.min(1,journey.elapsed/journey.seconds).toFixed(3)+')';
 if(journey.elapsed>=journey.seconds)advanceJourney();
}
function initJourney(){
 journeyUI.chapters.replaceChildren();
 journeyChapters.forEach((item,i)=>{
  const button=document.createElement('button');button.type='button';
  button.textContent=String(i+1).padStart(2,'0');
  button.setAttribute('aria-label','Chương '+(i+1)+': '+item.title);
  button.addEventListener('click',()=>setJourneyChapter(i));
  journeyUI.chapters.appendChild(button);
 });
 journeyUI.launch.addEventListener('click',startJourney);
 $('#journeyNext').addEventListener('click',advanceJourney);
 $('#journeyPrev').addEventListener('click',()=>setJourneyChapter(journey.index-1));
 $('#journeyExit').addEventListener('click',stopJourney);
 journeyUI.pause.addEventListener('click',()=>{
  journey.paused=!journey.paused;
  journeyUI.pause.textContent=journey.paused?'▶':'Ⅱ';
  journeyUI.pause.setAttribute('aria-label',journey.paused?'Tiếp tục hành trình':'Tạm dừng');
 });
 document.addEventListener('keydown',event=>{
  if(!journey.active)return;
  if(event.key==='Escape'){stopJourney();return;}
  if(event.target.closest?.('input, textarea, select'))return;
  if(event.key==='ArrowRight'){event.preventDefault();advanceJourney();}
  if(event.key==='ArrowLeft'){event.preventDefault();setJourneyChapter(journey.index-1);}
  if(event.code==='Space'){event.preventDefault();journeyUI.pause.click();}
 });
 reducedMotion.addEventListener('change',()=>{
  if(journey.active&&reducedMotion.matches){
   journey.paused=true;journeyUI.pause.textContent='▶';
   journeyUI.pause.setAttribute('aria-label','Tiếp tục hành trình');
  }
 });
}
function resize(){
 const bounds=stage.getBoundingClientRect();
 const dpr=Math.min(window.devicePixelRatio||1,state.eco?1:1.65)*renderScale;
 renderer.setPixelRatio(dpr);
 camera.aspect=Math.max(1,bounds.width)/Math.max(1,bounds.height);
 camera.fov=bounds.width<520?42:35;
 // Lift the model clear of the control dock.
 if(embeddedMode)camera.setViewOffset(Math.max(1,bounds.width),Math.max(1,bounds.height),0,bounds.width<=860?56:34,Math.max(1,bounds.width),Math.max(1,bounds.height));
 camera.updateProjectionMatrix();
 renderer.setSize(Math.max(1,Math.round(bounds.width)),Math.max(1,Math.round(bounds.height)),false);
 sceneDirty=true; // resizing clears the drawing buffer
}
function identifyDevice(object){
 let obj=object;
 while(obj){
  const n=(obj.userData?.name||obj.name||'').toLowerCase();
  if(n.startsWith('pv_')||n.startsWith('solar /'))return 'pv';
  if(n.startsWith('inverter /'))return 'inverter';
  if(n.startsWith('battery /'))return 'battery';
  if(n.startsWith('utility_meter /'))return 'meter';
  if(n.startsWith('gateway /'))return 'gateway';
  if(n.startsWith('main_panel /'))return 'main';
  if(n.startsWith('priority_panel /'))return 'prioritypanel';
  if(n.startsWith('utility_service /'))return 'grid';
  if(n.startsWith('grid /'))return 'grid';
  if(n.startsWith('room / priority'))return 'priority';
  if(n.startsWith('house /')||n.startsWith('interior /'))return 'house';
  obj=obj.parent;
 }
 return null;
}
function updateXray(){
 renderer.shadowMap.needsUpdate=true;
 stage.classList.toggle('is-xray',state.xray);
 $('#xrayBadge').hidden=!state.xray;
 for(const entry of xrayShells){
  entry.obj.material=state.xray?entry.ghost:entry.original;
  entry.obj.castShadow=state.xray?false:entry.shadow;
  entry.obj.receiveShadow=state.xray?false:entry.receive;
 }
 if(state.xray && !state.technical){
  state.technical=true;
  $('#technicalToggle').checked=true;
  $('#technicalDeviceList').hidden=false;
  $('#technicalLegend').hidden=false;
  syncRoutes();
  hotspotLayout();
 }
}
function setRoofLayersExpanded(value,{instant=false}={}){
 if(!state.ready||roofLayers.count!==48)return;
 renderer.shadowMap.needsUpdate=true;
 roofLayers.setExpanded(value,{instant:instant||reducedMotion.matches});
 stage.classList.toggle('is-roof-exploded',Boolean(value));
 const button=$('#roofLayerToggle');
 button.setAttribute('aria-pressed',String(Boolean(value)));
 button.setAttribute('aria-label',value?'Lắp lại tấm pin trên mái':'Tách lớp mái pin');
 button.title=value?'Lắp lại 12 tấm pin':'Tách lớp 12 tấm pin để quan sát kết cấu';
 button.classList.toggle('is-active',Boolean(value));
 $('#roofLayerBadge').hidden=!value;
 syncRoutes();
}
function initializeLivingExperience(){
 if(!livingMode)return;
 if(embeddedMode){
  // One compact dock inside the stage. Existing controls move in, their behaviour stays untouched.
  document.body.classList.add('pts-embed-active');
  if(window.parent===window)document.body.classList.add('living-exploring');
  const dock=document.createElement('div');
  dock.className='embed-dock';
  dock.append($('#livingClock'),$('#livingStageModes'),document.querySelector('.stage-actions'));
  // The options panel lives inside the stage too: fullscreen only shows what is inside the stage.
  const options=$('#controlPanel');
  dock.append(options);
  stage.append(dock);
  $('#livingDrawerClose').firstChild.textContent='Tùy chọn ';
  // Vertical swipes keep scrolling the page on touch screens; sideways swipes turn the model.
  canvas.style.touchAction='pan-y';
 }
 const workspace=stage.closest('.workspace');
 const drawer=$('#controlPanel'),open=$('#livingOpenControls');
 const setDrawer=value=>{
  workspace.classList.toggle('is-controls-open',value);
  drawer.classList.toggle('is-mobile-open',value);
  open.setAttribute('aria-expanded',String(value));
  if(value)$('#livingDrawerClose').focus({preventScroll:true});
  else open.focus({preventScroll:true});
 };
 open.addEventListener('click',()=>setDrawer(!workspace.classList.contains('is-controls-open')));
 $('#livingDrawerClose').addEventListener('click',()=>setDrawer(false));
 const northControl=$('#livingNorthOffset');
 northControl.addEventListener('input',()=>{
  modelNorthOffsetDegrees=Number(northControl.value);
  $('#livingNorthReadout').textContent=(modelNorthOffsetDegrees>0?'+':'')+modelNorthOffsetDegrees+'°';
 });
 document.addEventListener('keydown',event=>{
  if(event.key==='Escape'&&workspace.classList.contains('is-controls-open'))setDrawer(false);
 });
 $('#livingJourneyStart').addEventListener('click',()=>{
  document.body.classList.add('living-exploring');
  $('#journeyLaunch').click();
 });
 $('#embedJourneyStart').addEventListener('click',()=>$('#journeyLaunch').click());
 $('#livingFreeExplore').addEventListener('click',()=>{
  document.body.classList.add('living-exploring');
  selectDevice('all');
  $('#livingHero').classList.add('is-dismissed');
 });
 const setLive=()=>{
  solarMode='live';solarStamp='';timeMachine.stop();
  const local=siteParts(new Date());
  timeMachine.setHour(Math.round(local.hourFraction*4)/4);
  const light=refreshSolar(true);
  state.backupDaylight=light.daylight;
  if(state.scenario!=='backup')activeMode(light.daylight?'day':'night_storage');
  else activeMode('backup');
  syncRoutes();syncHouseLighting();
 };
 $('#livingUseLive').addEventListener('click',setLive);
 $('#livingUseSimulation').addEventListener('click',()=>{
  if(solarMode==='live'){
   const p=siteParts(new Date());
   timeMachine.setHour(Math.round(p.hourFraction*4)/4);
  }
  solarMode='simulation';solarStamp='';timeMachine.stop();
  const light=refreshSolar(true);
  state.backupDaylight=light.daylight;
  if(state.scenario==='backup')activeMode('backup');
  syncRoutes();syncHouseLighting();
 });
 $('#livingTimeSlider').addEventListener('input',event=>{
  solarMode='simulation';solarStamp='';timeMachine.stop();
  timeMachine.setHour(event.target.value,true);
 });
 document.querySelectorAll('[data-living-mode]').forEach(button=>{
  button.addEventListener('click',()=>{
   const id=button.dataset.livingMode;
   if(id==='backup'&&!state.battery)setBattery(true);
   document.body.classList.add('living-exploring');
   activeMode(id,true);
  });
 });
 const steps=[...document.querySelectorAll('.living-scroll-step')];
 const observer=new IntersectionObserver(entries=>{
  if(!state.ready||journey.active||workspace.classList.contains('is-controls-open'))return;
  const current=entries.filter(x=>x.isIntersecting).sort((a,b)=>b.intersectionRatio-a.intersectionRatio)[0];
  if(!current)return;
  steps.forEach(card=>card.classList.toggle('is-current',card===current.target));
  livingChapterIndex=steps.indexOf(current.target);
  $('#livingChapterCounter').textContent=String(livingChapterIndex+1).padStart(2,'0')+' / 03 · ENERGY STORY';
  $('#livingChapterName').textContent=['Mái nhà đón nắng','Trái tim hệ thống','Khi lưới điện thay đổi'][livingChapterIndex];
  document.querySelectorAll('.living-chapter-ticks i').forEach((dot,i)=>dot.classList.toggle('is-current',i===livingChapterIndex));
  const focus=current.target.dataset.livingStep;
  if(focus && state.selected!==focus){
   document.body.classList.add('living-exploring');
   selectDevice(focus);popup.hidden=true;
  }
 },{rootMargin:'-35% 0px -30% 0px',threshold:[0,.2,.5]});
 steps.forEach(step=>observer.observe(step));
 document.querySelectorAll('[data-living-focus]').forEach(button=>button.addEventListener('click',()=>{
  document.body.classList.add('living-exploring');
  selectDevice(button.dataset.livingFocus);popup.hidden=true;
 }));
 $('#livingReturnOverview').addEventListener('click',()=>{
  selectDevice('all');
  window.scrollTo({top:0,behavior:reducedMotion.matches?'instant':'smooth'});
 });
 let scrollQueued=false;
 const paintScroll=()=>{
  scrollQueued=false;
  const position=-$('#livingShell').getBoundingClientRect().top;
  document.body.classList.toggle('living-scrolled',position>110);
 };
 window.addEventListener('scroll',()=>{
  if(scrollQueued)return;
  scrollQueued=true;window.requestAnimationFrame(paintScroll);
 },{passive:true});
 paintScroll();
 refreshSolar(true);
}
function startControls(){
 $('#utilityQuickFocus').addEventListener('click',()=>{
  if(!state.ready)return;
  const isGrid=state.selected==='grid';
  selectDevice(isGrid?'all':'grid');
  popup.hidden=true;
 });
 const gridMenuLabel=document.querySelector('[data-focus="grid"] .dev-name');
 if(gridMenuLabel)gridMenuLabel.textContent='Cột điện · Nguồn điện lưới';
 document.querySelectorAll('[data-grid-step]').forEach(button=>{
  button.addEventListener('click',()=>{
   if(!state.ready)return;
   selectDevice(button.dataset.gridStep);
   document.querySelectorAll('[data-grid-step]').forEach(b=>b.classList.toggle('is-selected',b===button));
  });
 });
 initJourney();
 timeMachine.init();
 initializeLivingExperience();
 const mobileControls=$('#mobileControlsToggle');
 mobileControls.addEventListener('click',()=>{
  const panel=$('#controlPanel');
  const expanded=panel.classList.toggle('is-mobile-open');
  mobileControls.setAttribute('aria-expanded',String(expanded));
  mobileControls.querySelector('.mobile-controls-chevron').textContent=expanded?'⌃':'⌄';
 });
 $('#roofLayerToggle').addEventListener('click',()=>{
   if(!state.ready||roofLayers.count!==48)return;
   if(journey.active)stopJourney();
   timeMachine.stop();
   const next=!roofLayers.expanded;
   setRoofLayersExpanded(next);
   if(next){
    selectDevice('pv');
    popup.hidden=true;
    // A wider contextual framing shows all twelve lifted modules plus rails;
    // ordinary individual-device focus remains unchanged.
    const roofTarget=targetBase.clone().lerp(anchorOf('pv'),stage.clientWidth<620?.42:.56);
    desiredTarget.copy(roofTarget);
    const roofOffset=cameraBase.clone().sub(targetBase);
    desiredCamera.copy(roofTarget).add(roofOffset.multiplyScalar(stage.clientWidth<620?1.08:.76));
    tweenCamera=true;
   }
  });
  $('#presentationToggle').addEventListener('click',()=>{
  if(journey.active)stopJourney();
  presentation.tourOwns=false;
  setPresentation(!presentation.active);
 });
 document.addEventListener('keydown',event=>{
  if(event.key==='Escape'&&presentation.active&&!journey.active)setPresentation(false);
 });
 document.querySelectorAll('[data-scenario]').forEach(button=>{
  button.addEventListener('click',()=>activeMode(button.dataset.scenario,true));
 });
 $('#batteryToggle').addEventListener('change',event=>setBattery(event.target.checked));
 $('#flowToggle').addEventListener('change',event=>{state.flow=event.target.checked;syncRoutes();});
 $('#labelsToggle').addEventListener('change',event=>{state.labels=event.target.checked;hotspotLayout();syncFocusBeacon();});
 $('#ecoToggle').checked=state.eco;
 $('#ecoToggle').addEventListener('change',event=>{
  state.eco=event.target.checked;key.castShadow=!state.eco;renderer.shadowMap.enabled=!state.eco;
  renderer.shadowMap.needsUpdate=true;resize();syncRoutes();
 });
 $('#sunToggle').addEventListener('change',event=>{
  state.sun=event.target.checked;
  const daylight=state.scenario==='day'||(state.scenario==='backup'&&state.backupDaylight);
  $('#solarAtmosphere').hidden=!(daylight&&state.sun);
  syncRoutes();
 });
 $('#backupDaylightToggle').addEventListener('change',event=>{
  state.backupDaylight=event.target.checked;
  if(state.scenario==='backup')activeMode('backup');
 });
 $('#houseLightsToggle').addEventListener('change',event=>{
  state.houseLights=event.target.checked;
  syncHouseLighting();
 });
 $('#xrayToggle').addEventListener('change',event=>{
  if(journey.active)stopJourney();
  if(event.target.checked)state.xrayPreviousTechnical=state.technical;
  state.xray=event.target.checked;
  updateXray();
  if(!state.xray&&!state.xrayPreviousTechnical){
   state.technical=false;
   $('#technicalToggle').checked=false;
   $('#technicalDeviceList').hidden=true;
   $('#technicalLegend').hidden=true;
   syncRoutes();hotspotLayout();
  }
 });
 $('#technicalToggle').addEventListener('change',event=>{
  state.technical=event.target.checked;
  $('#technicalDeviceList').hidden=!state.technical;
  $('#technicalLegend').hidden=!state.technical;
  hotspotLayout();
  syncRoutes();
 });
 document.querySelectorAll('.device-menu [data-focus]').forEach(button=>{
  button.addEventListener('click',()=>selectDevice(button.dataset.focus));
 });
 $('#resetViewBtn').addEventListener('click',()=>selectDevice('all'));
 $('#autoRotateBtn').addEventListener('click',()=>{
  state.rotate=!state.rotate;tweenCamera=false;
  $('#autoRotateBtn').classList.toggle('is-active',state.rotate);
  $('#autoRotateBtn').setAttribute('aria-pressed',String(state.rotate));
 });
 $('#fullscreenBtn').addEventListener('click',async()=>{
  // iPhone has no element fullscreen API: the host page expands the frame to cover the screen instead.
  if(embeddedMode&&window.parent!==window&&(!stage.requestFullscreen||hostExpanded)){setHostExpanded(!hostExpanded);return;}
  try{if(document.fullscreenElement)await document.exitFullscreen();else await stage.requestFullscreen();resize();}
  catch(e){
   if(embeddedMode&&window.parent!==window)setHostExpanded(true);
   else console.warn('Fullscreen not available',e);
  }
 });
 $('#popoverClose').addEventListener('click',()=>popup.hidden=true);
 document.addEventListener('keydown',e=>{if(e.key==='Escape'){popup.hidden=true;if(hostExpanded)setHostExpanded(false);}});
 controls.addEventListener('start',()=>{tweenCamera=false;if(journey.active)stopJourney();});
 // A drag that ends on the canvas also fires click; it must not select a device and fly the camera away.
 let pressX=0,pressY=0;
 canvas.addEventListener('pointerdown',event=>{pressX=event.clientX;pressY=event.clientY;},{passive:true});
 canvas.addEventListener('click',event=>{
  if(!state.ready||Math.hypot(event.clientX-pressX,event.clientY-pressY)>6)return;
  const rect=canvas.getBoundingClientRect();
  pointer.x=(event.clientX-rect.left)/rect.width*2-1;
  pointer.y=-((event.clientY-rect.top)/rect.height*2-1);
  raycaster.setFromCamera(pointer,camera);
  const hits=raycaster.intersectObjects(state.xray?meshForPicking.filter(x=>!x.userData.isXrayShell):meshForPicking,false);
  for(const hit of hits){
   const id=identifyDevice(hit.object);
   if(id){selectDevice(id);break;}
  }
 });
 new ResizeObserver(resize).observe(stage);
 if(livingMode){
  new IntersectionObserver(entries=>{livingStageVisible=entries[0]?.isIntersecting!==false;},{rootMargin:'160px'}).observe(stage);
 }
 window.addEventListener('resize',resize);
 reducedMotion.addEventListener('change',syncRoutes);
}
let interactiveUntil=0;
// Embed render-on-demand: the scene is static unless something below says otherwise, so an
// idle frame costs nothing and the canvas simply keeps showing its last image.
let sceneDirty=true,lastRenderedStamp='',shadersReady=!embeddedMode,readyPosted=false;
// The first frame is prepared ahead of the visitor, so reaching the section never pays for it.
const hostAllowsDrawing=()=>!embeddedMode||((hostVisible||!readyPosted)&&!hostScrolling);
// Each heavy loading step waits for the host page to stop scrolling and then gets its own task.
const hostIdle=()=>new Promise(resolve=>{const check=()=>hostScrolling?setTimeout(check,120):setTimeout(resolve,0);check();});
// Interaction-only resolution drop, learned from real frame pacing and restored at rest.
// The still image is drawn once, so it can afford real sharpness; capped for very large screens.
function restRenderScale(){
 const bounds=stage.getBoundingClientRect();
 const wanted=Math.min(2,Math.max(1.25,window.devicePixelRatio||1));
 return Math.min(wanted,Math.sqrt(4.5e6/Math.max(1,bounds.width*bounds.height)));
}
let staticLayer=null;
let renderScale=1,learnedScale=1,frameSum=0,frameSamples=0,lastActiveFrameAt=0,lastDrawAt=0,scaleCapped=false;
const sceneActive=()=>performance.now()<interactiveUntil||journey.active||timeMachine.playing||state.rotate||tweenCamera||roofLayers.busy;
const idleAnimating=()=>ambientFlow||utilitySource.active;
function applyRenderScale(next){
 if(renderScale===next)return;
 renderScale=next;
 resize();
}
// Only a drag redraws the whole model, so only a drag may lower resolution, and only on a slow GPU.
// The full-quality still image returns the moment the drag ends.
function paceFrames(now){
 if(!sceneActive()){
  lastActiveFrameAt=0;frameSum=0;frameSamples=0;
  applyRenderScale(restRenderScale());
  return;
 }
 applyRenderScale(learnedScale);
 if(lastActiveFrameAt&&now-lastActiveFrameAt<1000){
  frameSum+=now-lastActiveFrameAt;frameSamples++;
  if(frameSamples>=12){
   const average=frameSum/frameSamples;
   if(average>30){
    scaleCapped=true;
    if(learnedScale>.8){learnedScale=.8;applyRenderScale(learnedScale);}
   }else if(!scaleCapped&&learnedScale<1.4&&average<20){
    learnedScale=+(learnedScale+.2).toFixed(2);applyRenderScale(learnedScale);
   }
   frameSum=0;frameSamples=0;
  }
 }
 lastActiveFrameAt=now;
}
// Moving flow is cheap now; it only stops if even that cannot keep up.
let flowFrameAt=0,flowSum=0,flowSamples=0,flowSlowWindows=0;
function drawFlowFrame(now){
 const dt=Math.min(.05,clock.getDelta());
 if(mixer&&ambientFlow)mixer.update(dt);
 utilitySource.tick(clock.elapsedTime,reducedMotion.matches);
 staticLayer.present(scene,camera);
 if(flowFrameAt&&now-flowFrameAt<1000){
  flowSum+=now-flowFrameAt;flowSamples++;
  if(flowSamples>=40){
   flowSlowWindows=flowSum/flowSamples>120?flowSlowWindows+1:0;
   if(flowSlowWindows>=2&&ambientFlow){ambientFlow=false;sceneDirty=true;}
   flowSum=0;flowSamples=0;
  }
 }
 flowFrameAt=now;
}
if(embeddedMode){
 const wake=()=>{interactiveUntil=performance.now()+1500;sceneDirty=true;flowFrameAt=0;startAnimation();};
 // Any control change, however it was triggered, must redraw the still image.
 for(const name of ['pointerdown','keydown','click','change','input'])document.addEventListener(name,wake,{passive:true});
 // Wheel zooms only when the visitor has engaged the model or filled the screen with it, and
 // hands back to the page at the zoom limits, so the 3D section can never trap scrolling.
 let engaged=false,zoomHintShown=false;
 const filling=()=>Boolean(document.fullscreenElement)||hostExpanded;
 canvas.addEventListener('pointerdown',()=>{engaged=true;},{passive:true});
 stage.addEventListener('pointerleave',()=>{engaged=false;},{passive:true});
 canvas.addEventListener('wheel',event=>{
  const distance=camera.position.distanceTo(controls.target);
  const atLimit=(event.deltaY>0&&distance>=controls.maxDistance*.99)||(event.deltaY<0&&distance<=controls.minDistance*1.01);
  if(event.ctrlKey||filling()||(engaged&&!atLimit)){wake();return;}
  event.stopImmediatePropagation();
  if(!engaged&&!zoomHintShown){
   zoomHintShown=true;
   const hint=document.createElement('div');
   hint.className='embed-zoom-hint';
   hint.textContent='Bấm vào mô hình rồi lăn chuột để phóng to';
   stage.append(hint);
   window.setTimeout(()=>hint.remove(),2600);
  }
 },{capture:true,passive:true});
 // Page scroll needs vertical swipes, except when the model fills the screen.
 document.addEventListener('fullscreenchange',()=>{canvas.style.touchAction=filling()?'none':'pan-y';sceneDirty=true;resize();startAnimation();});
 document.addEventListener('pointermove',event=>{if(event.buttons)wake();},{passive:true});
 // A short glide after release. Damping decays per frame, so it must not extend the window itself.
 document.addEventListener('pointerup',()=>{interactiveUntil=performance.now()+700;sceneDirty=true;startAnimation();},{passive:true});
 controls.addEventListener('change',()=>{sceneDirty=true;});
}
function animate(){
 animationFrameId=0;
 if(document.hidden||!hostAllowsDrawing())return;
 const frameNow=performance.now();
 if(embeddedMode&&state.ready&&shadersReady&&!sceneDirty&&!sceneActive()){
  refreshSolar();
  if(solarStamp===lastRenderedStamp){
   if(idleAnimating()&&staticLayer)drawFlowFrame(frameNow);
   else if(idleAnimating()&&frameNow-lastDrawAt>=30){sceneDirty=true;}
   if(!sceneDirty){scheduleNextFrame();return;}
  }
 }
 lastDrawAt=frameNow;
 const dt=Math.min(.05,clock.getDelta());
 if(embeddedMode)paceFrames(frameNow);
 // The frame that ends an interaction lands exactly on target: nothing keeps drawing to converge.
 const settle=embeddedMode&&!sceneActive();
 const ease=factor=>settle?1:factor;
 if(livingMode&&!livingStageVisible&&!journey.active&&(!embeddedMode||readyPosted)){refreshSolar();scheduleNextFrame();return;}
 tickJourney(dt);
 timeMachine.tick(dt);
 if(mixer && !reducedMotion.matches && (!state.eco||ambientFlow))mixer.update(dt);
 roofLayers.tick(dt,reducedMotion.matches);
 const live= livingMode?refreshSolar():null;
 const night=livingMode?!live.daylight:(state.scenario==='backup'?!state.backupDaylight:scenarioData[state.scenario].night);
 const backup=scenarioData[state.scenario].backup;
 const sunLevel=livingMode?live.brightness:timeMachine.brightness();
 const solarPhase=THREE.MathUtils.clamp((timeMachine.hour-6.25)/12.25,0,1);
 const sunElevation=Math.sin(Math.PI*solarPhase);
 const goldenHour=livingMode?live.golden:(!night&&sunLevel>0&&sunLevel<.85);
 if(livingMode){
  // East = +X, North = -Z is a preview convention until Blender site axis is calibrated.
  const az=(live.azimuth+modelNorthOffsetDegrees)*Math.PI/180,alt=Math.max(4,live.altitude)*Math.PI/180;
  sunWorldGoal.set(30*Math.sin(az)*Math.cos(alt),Math.max(4,30*Math.sin(alt)),-30*Math.cos(az)*Math.cos(alt));
 }else sunWorldGoal.set(-14+solarPhase*28,5+14*sunElevation,13);
  key.position.lerp(sunWorldGoal,ease(.065));
  renderFrameNumber++;
  const shadowMoving=key.position.distanceToSquared(lastShadowSunPosition)>(stage.clientWidth<700?.55*.55:.32*.32);
  if((!state.eco||embeddedMode) && (roofLayers.busy || shadowMoving) && renderFrameNumber-shadowUpdateFrame>=6){
    renderer.shadowMap.needsUpdate=true;
    lastShadowSunPosition.copy(key.position);
    shadowUpdateFrame=renderFrameNumber;
  }
 if(livingMode && lastSolarSync!==solarStamp){
  lastSolarSync=solarStamp;
  if(solarMode==='live'&&state.scenario!=='backup'){
   const desired=solarSample.daylight?'day':'night_storage';
   if(state.scenario!==desired)activeMode(desired);
  }
  syncHouseLighting();syncRoutes();
 }
  hemi.intensity=THREE.MathUtils.lerp(hemi.intensity,night?(livingMode?.75:1.1):1.3+1.7*sunLevel,ease(.05));
  key.intensity=THREE.MathUtils.lerp(key.intensity,night?(livingMode?.16:.30):1.0+2.7*sunLevel,ease(.05));
 fill.intensity=THREE.MathUtils.lerp(fill.intensity,night?(livingMode?.59:.9):1.2,ease(.05));
  sunColorGoal.set(night?0xb3d6ff:goldenHour?0xffce92:0xfff1dc);
  key.color.lerp(sunColorGoal,ease(.055));
 renderer.toneMappingExposure=THREE.MathUtils.lerp(renderer.toneMappingExposure,night?(livingMode?(backup?.73:.87):(backup?.9:1.04)):1.25,ease(.05));
  // At night, keep metal reflections restrained so interior lights remain credible.
  scene.environmentIntensity=THREE.MathUtils.lerp(scene.environmentIntensity,night?(livingMode?.07:.09):.22,ease(.045));
 const lightEase=settle?1:1-Math.exp(-10*dt);
 if(entrySoffitLamp&&entrySoffitLight){
  const material=entrySoffitLamp.material;
  material.emissiveIntensity=THREE.MathUtils.lerp(material.emissiveIntensity,material.userData.ptsSoffitTarget||0,lightEase);
  entrySoffitLight.intensity=THREE.MathUtils.lerp(entrySoffitLight.intensity,entrySoffitLight.userData.targetIntensity||0,lightEase);
 }
 for(const zone of houseLightZones){
  zone.light.intensity=THREE.MathUtils.lerp(zone.light.intensity,zone.light.userData.targetIntensity||0,lightEase);
  zone.glow.material.opacity=THREE.MathUtils.lerp(zone.glow.material.opacity,zone.glow.material.userData.ptsHouseGlowTarget||0,lightEase);
  for(const entry of zone.materials){
   const material=entry.material;
   material.emissiveIntensity=THREE.MathUtils.lerp(material.emissiveIntensity,material.userData.ptsHouseLightTarget||0,lightEase);
   material.opacity=THREE.MathUtils.lerp(material.opacity,material.userData.ptsHouseOpacityTarget??entry.baseOpacity,lightEase);
  }
 }
 if(tweenCamera){
  const cameraEase=1-Math.exp(-(journey.active||embedStory.flight?2.6:10)*dt);
  camera.position.lerp(desiredCamera,cameraEase);controls.target.lerp(desiredTarget,cameraEase);
  // Also end when no longer closing in: OrbitControls can clamp a goal that is out of its range.
  const remaining=camera.position.distanceTo(desiredCamera)+controls.target.distanceTo(desiredTarget);
  if(remaining<.08||Math.abs(tweenRemaining-remaining)<.002){tweenCamera=false;embedStory.flight=false;}
  tweenRemaining=remaining;
 }
 if(focusBeacon.visible){
   const pulse=reducedMotion.matches?1:1+.075*Math.sin(clock.elapsedTime*2.3);
   focusBeacon.scale.setScalar(pulse);
   focusBeacon.rotation.y=reducedMotion.matches?0:clock.elapsedTime*.11;
  }
  controls.autoRotate=state.rotate;
 if(settle)controls.enableDamping=false; // drops the leftover glide so the scene can really stop
 controls.update();
 if(embeddedMode)controls.enableDamping=true;
 hotspotLayout();
 utilitySource.tick(clock.elapsedTime,reducedMotion.matches);
  if(shadersReady){
   pvBatch?.setMerged(!roofLayers.busy&&!stage.classList.contains('is-roof-exploded'));
   shellBatch?.setMerged(!state.xray);
   if(settle&&staticLayer){
    staticLayer.renderStill(scene,camera);
    staticLayer.present(scene,camera);
   }else renderer.render(scene,camera);
   if(settle){sceneDirty=false;lastRenderedStamp=solarStamp;}
   if(embeddedMode&&!readyPosted&&window.parent!==window){
    readyPosted=true;
    window.parent.postMessage({type:'pts-living-energy:ready'},window.location.origin);
   }
  }
  scheduleNextFrame();
}
function stopAnimation(){
 if(animationFrameId){cancelAnimationFrame(animationFrameId);animationFrameId=0;}
 if(animationTimerId){clearTimeout(animationTimerId);animationTimerId=0;}
}
function scheduleNextFrame(){
 if(document.hidden||!hostAllowsDrawing())return;
 const active=!embeddedMode||!state.eco||sceneActive();
 if(active&&!state.eco){animationFrameId=requestAnimationFrame(animate);return;}
 // Anything moving stays on the display's frame clock; a still scene only checks once a second.
 if(active||sceneDirty||idleAnimating()){animationFrameId=requestAnimationFrame(animate);return;}
 animationTimerId=window.setTimeout(()=>{animationTimerId=0;startAnimation();},1000);
}
function startAnimation(){
 if(animationTimerId){clearTimeout(animationTimerId);animationTimerId=0;}
 if(!animationFrameId&&!document.hidden&&hostAllowsDrawing())animationFrameId=requestAnimationFrame(animate);
}
function finishLoading(gltf){
 model=gltf.scene;
 scene.add(model);
 renderer.shadowMap.needsUpdate=true;
 model.updateMatrixWorld(true);
 if(embeddedMode){
  // Low-power path for the homepage embed: a transmissive material makes three.js re-render the
  // scene into a transmission target every frame. The glazing keeps its authored alpha-blended tint.
  model.traverse(obj=>{
   const materials=Array.isArray(obj.material)?obj.material:[obj.material];
   for(const material of materials){
    if(material?.isMeshPhysicalMaterial&&material.transmission>0){material.transmission=0;material.needsUpdate=true;}
   }
  });
 }
  // Preserve original Blender names; Three.js sanitizes names from GLB.
  model.traverse(obj=>{if(obj.userData?.name)sourceNodes.set(obj.userData.name,obj);});
 model.traverse(obj=>{
   if(obj.name.startsWith('HOTSPOT_'))anchors.set(obj.name,obj);
   if(obj.name.startsWith('SUN__')||obj.name.startsWith('SUNPHOTON__'))sunObjects.push(obj);
   if(['GATEWAY / Disconnect status rail','LOAD_PRIORITY / Smart light icon base','LOAD_NORMAL / Appliance icon base'].includes(obj.name)){
     if(obj.material){obj.material=obj.material.clone();
       technicalHighlights.set(obj.name.startsWith('GATEWAY')?'grid':obj.name.startsWith('LOAD_PRIORITY')?'priority':'normal',obj);}
   }
   if(obj.isMesh){
     const sourceName=obj.userData.name||obj.name;
     if(sourceName==='ARCH_POLISH / Entry warm light diffuser'){
       const authored=Array.isArray(obj.material)?obj.material[0]:obj.material;
       if(authored?.emissive){
         obj.material=authored.clone();
         obj.material.emissive.set('#ffd6a0');
         obj.material.emissiveIntensity=0;
         entrySoffitLamp=obj;
         entrySoffitLight=new THREE.PointLight(0xffca92,0,5.4,2);
         entrySoffitLight.position.copy(obj.getWorldPosition(new THREE.Vector3())).add(new THREE.Vector3(0,-.07,.23));
         entrySoffitLight.castShadow=false;
         if(!embeddedMode)scene.add(entrySoffitLight);
       }
     }
      if(sourceName.startsWith('GRID_SERVICE / Insulated drop conductor')||
         sourceName==='GRID_SERVICE / Insulated service riser to meter'){
        const m=Array.isArray(obj.material)?obj.material[0]:obj.material;
        if(m?.isMeshStandardMaterial||m?.isMeshPhysicalMaterial){
          obj.material=m.clone();
          gridServiceWires.push(obj);
        }
      }
     if((sourceName.startsWith('HOUSE /')||sourceName.startsWith('ROOF /'))&&obj.material){
       const makeGhost=m=>{
        const copy=m.clone();
        copy.transparent=true;copy.opacity=sourceName.startsWith('ROOF /')?.14:.20;
        copy.depthWrite=false;copy.side=THREE.DoubleSide;return copy;
       };
       xrayShells.push({
        obj,original:obj.material,
        ghost:Array.isArray(obj.material)?obj.material.map(makeGhost):makeGhost(obj.material),
        shadow:obj.castShadow,receive:obj.receiveShadow
       });
       obj.userData.isXrayShell=true;
     }
     const name=sourceName;
     // The embed updates its shadow map only when the sun moves, so a fuller caster set costs little per frame.
     obj.castShadow=embeddedMode
      ?!/^(FLOW__|PHOTON__|SUN|SUNPHOTON|GLAZING|ANNOTATION|POWER|TECH|GRAPH|STAGE|LANDSCAPE \/|GARDEN)/.test(name)&&!Array.isArray(obj.material)&&!obj.material?.transparent
      :/^(HOUSE \/|ROOF \/|PV_\d+_\d+ \/ Anodized Panel Chassis|UTILITY_|GATEWAY \/|MAIN_PANEL \/|PRIORITY_PANEL \/|INVERTER \/|BATTERY \/)/.test(name);
     obj.receiveShadow=embeddedMode
      ?!/^(FLOW__|PHOTON__|SUN|SUNPHOTON|ANNOTATION)/.test(name)
      :/^(STAGE \/|LANDSCAPE \/|HOUSE \/|ROOF \/)/.test(name);
     const shell=xrayShells.at(-1);
     if(shell?.obj===obj){shell.shadow=obj.castShadow;shell.receive=obj.receiveShadow;}
   }
  if(routeOf(obj.name))routedObjects.push(obj);
  if(obj.name.startsWith('BATTERY /'))batteryObjects.push(obj);
  if(obj.isMesh)meshForPicking.push(obj);
 });
 foliageBatch=batchStaticFoliage(THREE,scene,sourceNodes);
 // The embed has no shadows or x-ray of these groups, so it can merge every static device body too.
 detailBatch=embeddedMode
  ?batchStaticDetails(THREE,scene,model,/^(LANDSCAPE|LANDSCAPE_TREE|GARDEN|DETAIL|INTERIOR|ARCH_POLISH|SIGNATURE|INSTALL|STAGE|INVERTER|MAIN_PANEL|PRIORITY_PANEL|UTILITY_METER|GATEWAY|GRID_SOURCE|UTILITY_SERVICE)\b/)
  :batchStaticDetails(THREE,scene,model);
 if(embeddedMode){
  pvBatch=batchStaticDetails(THREE,scene,model,/^PV_\d/);
  shellBatch=batchStaticDetails(THREE,scene,model,/^(HOUSE|ROOF) \//);
 }
 utilitySource.init(sourceNodes);
  const liftedFaces=roofLayers.collect(model);
  if(liftedFaces!==48){
   console.warn('PTS roof study requires exactly 48 PV face meshes; feature disabled',liftedFaces);
   $('#roofLayerToggle').disabled=true;
  }
  createHouseLighting();
 mixer=new THREE.AnimationMixer(model);
 for(const clip of gltf.animations){
  const route=routeOf(clip.name);
   if(route)routeAnimations.push({route,action:mixer.clipAction(clip),running:false});
   if(clip.name.startsWith('SUNPHOTON__'))sunActions.push({action:mixer.clipAction(clip),running:false});
 }
 // Blender's SUN_LIGHT is excluded from GLB. Align Three.js shadows to
 // the exported source sun marker and PV focus, rather than hardcoding another sun.
 const sourceSun=model.getObjectByName('SUN__SOURCE_DISC');
 const roofFocus=anchors.get('HOTSPOT_PV');
 if(sourceSun&&roofFocus){
  key.position.copy(sourceSun.getWorldPosition(new THREE.Vector3()));
  key.target.position.copy(roofFocus.getWorldPosition(new THREE.Vector3()));
  scene.add(key.target);
  key.target.updateMatrixWorld(true);
 }
 const box=new THREE.Box3();
 model.traverse(obj=>{
  if(obj.isMesh && !obj.name.startsWith('SUN__') && !obj.name.startsWith('SUNPHOTON__')
       && !obj.name.startsWith('FLOW__') && !obj.name.startsWith('PHOTON__')){
    box.expandByObject(obj);
  }
 });
 if(box.isEmpty())box.setFromObject(model);
 const center=box.getCenter(new THREE.Vector3());
 const size=box.getSize(new THREE.Vector3());
 const d=Math.max(size.x,size.z,size.y);
 targetBase.copy(center);
 targetBase.y+=.18;
 if(livingMode&&stage.clientWidth<530)targetBase.x+=.55;
 const portraitFactor=stage.clientWidth<530?(livingMode?(embeddedMode?1.95:1.62):1.11):(stage.clientWidth/Math.max(1,stage.clientHeight))<1.05?1.20:.96;
 cameraBase.copy(targetBase).add(new THREE.Vector3(d*.95,d*.76,d*1.16).multiplyScalar(portraitFactor));
 camera.position.copy(cameraBase);
 controls.target.copy(targetBase);
 desiredCamera.copy(cameraBase);
 desiredTarget.copy(targetBase);
 // Permit detailed pole inspection without changing the full-system overview.
 controls.minDistance=Math.max(3,d*.22);
 controls.maxDistance=d*4;
 // In the embed, zooming out stops at the overview, which is also where the wheel returns to the page.
 if(embeddedMode)controls.maxDistance=cameraBase.distanceTo(targetBase)*1.02;
 controls.update();
 state.ready=true;
 if(livingMode){
  const sample=refreshSolar(true);
  state.backupDaylight=sample.daylight;
  controls.enableZoom=embeddedMode;
  document.body.classList.toggle('living-exploring',embeddedMode&&document.body.classList.contains('pts-embed-active')&&!embedStory.intro);
 }
 if(journey.pending){journey.pending=false;window.setTimeout(startJourney,80);}
 updateXray();
 activeMode(livingMode?(solarSample?.daylight?'day':'night_storage'):state.scenario);
 renderer.render(scene,camera);
 if(embeddedMode){
  // Everything that moves goes on its own layer; lights must reach both layers.
  staticLayer=createStaticLayer(THREE,renderer);
  if(staticLayer){
   for(const light of [hemi,key,fill])light.layers.enable(MOVING_LAYER);
   const moving=[...routedObjects,...sunObjects];
   scene.traverse(obj=>{if(obj.name.startsWith('PTS_UI / Grid service direction marker'))moving.push(obj);});
   for(const root of moving)root.traverse(obj=>obj.layers.set(MOVING_LAYER));
   camera.layers.enable(MOVING_LAYER);
  }
 }
 stage.dataset.ready='true';
 stage.classList.add('is-loaded');

 loading.classList.add('is-hidden');
 window.setTimeout(()=>loading.hidden=true,420);
 window.__PTS_V05_READY=true;
 window.__PTS_V05_DEBUG=()=>({
  activity:{interactiveMs:Math.round(interactiveUntil-performance.now()),journey:journey.active,playing:timeMachine.playing,rotate:state.rotate,tween:tweenCamera,roof:roofLayers.busy,utility:utilitySource.active},
  staticLayer:Boolean(staticLayer),ambientFlow,renderScale,learnedScale,sceneDirty,drawCalls:renderer.info.render.calls,triangles:renderer.info.render.triangles,hostVisible,animationFrameId,animationTimerId,elapsedTime:+clock.elapsedTime.toFixed(2),modelVisible:model?.visible,modelChildren:model?.children.length,eco:state.eco,dpr:renderer.getPixelRatio(),renderFrameNumber,detailBatch,
  scenario:state.scenario,battery:state.battery,selected:state.selected,flow:state.flow,
  activeRoutes:activeRoutes(),routeObjects:routedObjects.filter(o=>o.visible).length,
  clipCount:gltf.animations.length,activeClips:routeAnimations.filter(x=>x.running).length,
   hotspotCount:anchors.size,camera:camera.position.toArray().map(x=>+x.toFixed(2)),
   orbitTarget:controls.target.toArray().map(x=>+x.toFixed(2)),
   desiredTarget:desiredTarget.toArray().map(x=>+x.toFixed(2)),
   cameraDistance:+camera.position.distanceTo(controls.target).toFixed(2),minCameraDistance:controls.minDistance,
   sunVisible:sunObjects.filter(o=>o.visible).length,sunClips:sunActions.length,
   activeSunClips:sunActions.filter(a=>a.running).length,
   technical:state.technical,gridIsolated:state.scenario==='backup',backupDaylight:state.backupDaylight,night:stage.classList.contains('is-night'),
   houseLights:state.houseLights,houseLightZones:houseLightZones.length,
   architecturalPolishCount:[...sourceNodes.keys()].filter(name=>name.startsWith('ARCH_POLISH /')).length,
   soffitLampPresent:!!entrySoffitLamp,soffitLightTarget:entrySoffitLight?.userData.targetIntensity||0,
   litHouseZones:houseLightZones.filter(zone=>(zone.light.userData.targetIntensity||0)>0).map(zone=>zone.role),
  journeyActive:journey.active,journeyStep:journey.index,journeyPaused:journey.paused,
  timelineHour:timeMachine.hour,timelinePlaying:timeMachine.playing,presentationActive:presentation.active,
  xray:state.xray,xrayShells:xrayShells.length,
   utilityPoleMeshCount:[...sourceNodes.keys()].filter(name=>name.startsWith('GRID_SOURCE /')).length,
   gridAnchorPosition:anchors.get('HOTSPOT_GRID')?.position.toArray().map(v=>+v.toFixed(2)),
   poleProjected:(()=>{
    const pole=sourceNodes.get('GRID_SOURCE / Low-voltage concrete utility pole');
    if(!pole)return null;
    const b=new THREE.Box3().setFromObject(pole),p=b.getCenter(new THREE.Vector3());
    return {top:new THREE.Vector3(p.x,b.max.y,p.z).project(camera).toArray().map(x=>+x.toFixed(3)),
      foot:new THREE.Vector3(p.x,b.min.y,p.z).project(camera).toArray().map(x=>+x.toFixed(3))};
   })(),
   serviceWireCount:gridServiceWires.length,
   serviceWireGlow:gridServiceWires.filter(w=>w.material.emissiveIntensity>.01).length,
   utilityFlowMarkers:utilitySource.markerCount,utilityFlowVisible:utilitySource.visibleCount,utilityFlowReady:utilitySource.ready,focusBeaconVisible:focusBeacon.visible,
   activePowerLane:$('#powerMap').dataset.activeLane,utilityQuickFocus:$('#utilityQuickFocus').getAttribute('aria-pressed'),
   roofLayerExpanded:roofLayers.expanded,roofLayerProgress:roofLayers.progress,roofLayerParts:roofLayers.count,roofLayerModules:roofLayers.moduleCount,
   renderCalls:renderer.info.render.calls,renderTriangles:renderer.info.render.triangles,
   foliageBatch,
   gpuGeometries:renderer.info.memory.geometries,gpuTextures:renderer.info.memory.textures,
  solarMode:livingMode?solarMode:'classic',solarTime:livingMode?formatSolarTime(solarSample.parts):null,
  solarAltitude:livingMode?+solarSample.altitude.toFixed(2):null,
  solarAzimuth:livingMode?+solarSample.azimuth.toFixed(2):null,
  modelNorthOffset:livingMode?modelNorthOffsetDegrees:0,
  sunKeyPosition:key.position.toArray().map(v=>+v.toFixed(2)),
  livingChapterIndex:livingMode?livingChapterIndex:-1,
  atmosphere:livingMode?document.body.dataset.atmosphere:'classic',
  livingControlsOpen:stage.closest('.workspace')?.classList.contains('is-controls-open')||false,
  status:'loaded'
 });
}
async function load(){
 try{
  const response=await fetch(new URL('../assets/PTS_V05_Scene_Contract.json',import.meta.url));
  if(!response.ok)throw Error('Scenario contract not found: '+response.status);
  state.contract=await response.json();
  const modelResponse=await fetch(new URL('../assets/pts-technical-v05.glb',import.meta.url));
  if(!modelResponse.ok)throw Error('Model not found: '+modelResponse.status);
  const modelData=await modelResponse.arrayBuffer();
  await hostIdle();
  const gltf=await loader.parseAsync(modelData,'');
  await hostIdle();
  finishLoading(gltf);
  if(!shadersReady){
   await hostIdle();
   // Compile off the main thread where the driver allows it, so the first frame does not stall the page.
   // Some drivers never report completion while the frame is off screen, so waiting is capped.
   const capped=work=>Promise.race([Promise.resolve(work).catch(()=>{}),new Promise(resolve=>setTimeout(resolve,4000))]);
   await capped(renderer.compileAsync(scene,camera));
   await hostIdle();
   await capped(staticLayer?.compile(scene,camera));
   await hostIdle();
   shadersReady=true;sceneDirty=true;
   startAnimation();
  }
 }catch(err){
  console.error('PTS V05 model loading failed:',err);
  loading.querySelector('strong').textContent='Không tải được mô hình 3D';
  $('#loadingMessage').textContent='Bản ảnh vẫn có thể xem được. Kiểm tra Node server và file GLB.';
  stage.classList.remove('is-loaded');
  stage.dataset.ready='error';
  window.__PTS_V05_ERROR=String(err);
  loading.classList.add('is-hidden');
  window.setTimeout(()=>loading.hidden=true,420);
 }
}
createHotspots();
startControls();
activeMode('day');
resize();
load();
startAnimation();
