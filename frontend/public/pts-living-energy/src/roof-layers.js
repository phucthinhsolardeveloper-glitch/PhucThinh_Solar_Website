/**
 * PTS Solar roof layer study.
 * Educational disassembly of the 12 illustrative PV modules, NOT an
 * installation/exploded manufacturer drawing. Rails and clamps stay in place.
 * Works exclusively with the already loaded GLB model (no duplicated assets).
 */
const PV_FACE=/^PV_(\d{2})_(\d{2}) \/ (Anodized Panel Chassis|Chrome Busbar|Dark PV Composite|Monocrystalline 60 cells)(?:\.\d+)?$/;
const clamp=(v,min=0,max=1)=>Math.max(min,Math.min(max,v));
const ease=v=>v*v*(3-2*v);
export function createRoofLayers(onSettled=()=>{}){
 const parts=[];
 let expanded=false,progress=0,target=0,settled=true,startTime=0,from=0;
 function collect(root){
  parts.length=0;
  root.traverse(obj=>{
   if(!obj.isMesh)return;
   const name=obj.userData?.name||obj.name||'';
   const match=name.match(PV_FACE);
   if(!match)return;
   const row=Number(match[1]),column=Number(match[2]);
   parts.push({obj,base:obj.position.clone(),row,column,
    delay:(column-1)*.018+(row-1)*.036});
  });
  return parts.length;
 }
 function animateProgress(t){
  progress=clamp(t);
  for(const item of parts){
   const t0=clamp((progress-item.delay)/(1-item.delay));
   const a=ease(t0);
   item.obj.position.set(
    item.base.x+(item.column-3.5)*.065*a,
    item.base.y+(item.row===1?1.06:.87)*a,
    item.base.z+(item.row===1?.15:-.15)*a
   );
  }
 }
 function setExpanded(next,{instant=false}={}){
  expanded=Boolean(next);
  from=progress;
  target=expanded?1:0;
  startTime=performance.now();
  settled=false;
  if(instant){animateProgress(target);settled=true;onSettled();}
 }
 function tick(dt,reducedMotion=false){
  if(settled)return;
  if(reducedMotion){animateProgress(target);settled=true;onSettled();return;}
  // Wall-clock timing prevents extremely long transitions on slow WebGL GPUs.
  // The scene may render at a low frame rate; every visible frame still lands
  // at the physically correct progress of the 1.35 second animation.
  const phase=clamp((performance.now()-startTime)/1350);
  animateProgress(from+(target-from)*ease(phase));
  if(phase>=.985||Math.abs(progress-target)<.008){animateProgress(target);settled=true;onSettled();}
 }
 return {
  collect,setExpanded,tick,
  get expanded(){return expanded;},
  get progress(){return progress;},
  get busy(){return expanded||!settled;},
  get count(){return parts.length;},
  get moduleCount(){return new Set(parts.map(p=>p.row+'-'+p.column)).size;}
 };
}
