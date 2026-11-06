/**
 * Animated GRID-source direction overlay for the PTS Solar V0.5 teaching model.
 * Connects actual semantic GLB objects, without inventing electrical conductor
 * sizes, load flow or new energy routes. Directional highlights are illustrative.
 */
export function createUtilitySource(THREE,scene){
 const markers=[];
 let active=false;
 let ready=false;
 let curve=null;
 const markerGeometry=new THREE.SphereGeometry(.065,10,8);
 const markerMaterial=new THREE.MeshBasicMaterial({
   color:0x6cf4e7,transparent:true,opacity:.96,
   depthTest:false,depthWrite:false
 });
 for(let i=0;i<3;i++){
  const obj=new THREE.Mesh(markerGeometry,markerMaterial);
  obj.name='PTS_UI / Grid service direction marker '+(i+1);
  obj.visible=false;
  obj.renderOrder=95;
  scene.add(obj);
  markers.push(obj);
 }
 const at=(source,name)=>source.get(name)?.getWorldPosition(new THREE.Vector3())||null;
 function init(sourceNodes){
  const pole=at(sourceNodes,'GRID_SOURCE / Insulator top 1');
  const head=at(sourceNodes,'GRID_SERVICE / Service head canopy');
  const meter=at(sourceNodes,'UTILITY_METER / Weatherproof meter cabinet');
  if(!pole||!head||!meter){
   console.warn('PTS utility supply flow anchors not found');
   ready=false;
   return false;
  }
  // Direction: pole insulator -> suspended insulated drop -> service head
  // -> meter housing. A slight foreground offset keeps the guide distinguishable.
  const mid=pole.clone().lerp(head,.51).add(new THREE.Vector3(0,-.15,.035));
  const riser=head.clone().lerp(meter,.37).add(new THREE.Vector3(.09,0,.06));
  curve=new THREE.CatmullRomCurve3([pole,mid,head,riser,meter],false,'centripetal',.5);
  ready=true;
  return true;
 }
 function setActive(next){
  active=Boolean(next)&&ready;
  if(!active)for(const marker of markers)marker.visible=false;
 }
 function tick(elapsedSeconds,reducedMotion=false){
  if(!active||!curve)return;
  const phase=(elapsedSeconds*.23)%1;
  for(let i=0;i<markers.length;i++){
   const t=(phase-i*.20+1)%1;
   const marker=markers[i];
   marker.visible=!reducedMotion||i===0;
   marker.position.copy(curve.getPointAt(reducedMotion?.70:t));
   marker.scale.setScalar(reducedMotion?1:.8+.18*Math.sin((t*2*Math.PI)));
  }
 }
 return {
  init,setActive,tick,
  get active(){return active;},
  get markerCount(){return markers.length;},
  get visibleCount(){return markers.filter(x=>x.visible).length;},
  get ready(){return ready;}
 };
}
