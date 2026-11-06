import {mergeGeometries} from 'three/addons/utils/BufferGeometryUtils.js';

/**
 * Runtime draw-call reduction for noninteractive, repeated foliage submeshes.
 * Keeps the original Blender GLB, object names, electrical paths and picking anchors
 * untouched; instances only decorative geometry with identical GPU resources.
 */
export function batchStaticFoliage(THREE,scene,sourceNodes){
 const groups=new Map();
 let candidates=0;
 for(const [name,root] of sourceNodes){
  if(!/^ARCH_POLISH \/ (west|east) sage shrub \d{2}$/.test(name))continue;
  root.traverse(child=>{
   if(!child.isMesh || child.isSkinnedMesh || !child.geometry || child.morphTargetInfluences?.length)return;
   const mats=Array.isArray(child.material)?child.material:[child.material];
   if(!mats.length || mats.some(m=>!m))return;
   const key=child.geometry.uuid+'|'+mats.map(m=>m.uuid).join(',');
   if(!groups.has(key))groups.set(key,[]);
   groups.get(key).push(child);
   candidates++;
  });
 }
 let instances=0,drawGroups=0;
 for(const meshes of groups.values()){
  if(meshes.length<3)continue;
  const first=meshes[0];
  const instance=new THREE.InstancedMesh(first.geometry,first.material,meshes.length);
  instance.name='PTS Foliage Batch '+String(drawGroups+1);
  instance.frustumCulled=false;
  instance.castShadow=false;
  instance.receiveShadow=false;
  meshes.forEach((mesh,index)=>{
   mesh.updateWorldMatrix(true,false);
   instance.setMatrixAt(index,mesh.matrixWorld);
   mesh.visible=false;
  });
  instance.instanceMatrix.needsUpdate=true;
  scene.add(instance);
  drawGroups++;
  instances+=meshes.length;
 }
 return {drawGroups,instances,candidates};
}

const DEFAULT_STATIC_NAMES=/^(LANDSCAPE|GARDEN|DETAIL|INTERIOR) \/ /;

// The GLB positions and normals are quantized (normalized Int16/Int8). Baking a world
// matrix into those would overflow the integer range, so expand to Float32 first.
function floatGeometry(THREE,source){
 const geometry=new THREE.BufferGeometry();
 for(const [name,attribute] of Object.entries(source.attributes)){
  const {count,itemSize}=attribute;
  const array=new Float32Array(count*itemSize);
  for(let i=0;i<count;i++)for(let c=0;c<itemSize;c++)array[i*itemSize+c]=attribute.getComponent(i,c);
  geometry.setAttribute(name,new THREE.BufferAttribute(array,itemSize));
 }
 if(source.index)geometry.setIndex(source.index.clone());
 return geometry;
}
function isShown(object){
 for(let node=object;node;node=node.parent)if(!node.visible)return false;
 return true;
}

// Decorative meshes are authored as many tiny Blender objects. Merge only opaque,
// static geometry that has no animation, x-ray or visibility role. Source meshes stay
// in the graph (hidden): the raycaster ignores visibility, so device picking still works.
export function batchStaticDetails(THREE,scene,model,staticNames=DEFAULT_STATIC_NAMES){
 const groups=new Map();
 model.updateMatrixWorld(true);
 model.traverse(mesh=>{
  if(!mesh.isMesh||mesh.isSkinnedMesh||!mesh.geometry||Array.isArray(mesh.material))return;
  const name=mesh.userData.name||mesh.name;
  if(!staticNames.test(name)||!isShown(mesh))return;
  const material=mesh.material;
  if(!material||material.transparent||material.alphaTest>0)return;
  if(mesh.geometry.groups.length>1||mesh.morphTargetInfluences?.length)return;
  if(mesh.matrixWorld.determinant()<0)return; // baking a mirror would flip the winding
  const attributes=Object.entries(mesh.geometry.attributes)
   .map(([key,value])=>`${key}:${value.itemSize}`)
   .sort().join('|');
  const key=`${material.uuid}|${attributes}|${Boolean(mesh.geometry.index)}|${mesh.castShadow}|${mesh.receiveShadow}`;
  if(!groups.has(key))groups.set(key,[]);
  groups.get(key).push(mesh);
 });
 let sourceMeshes=0,drawGroups=0;
 const batches=[],sources=[];
 let merged=true;
 for(const meshes of groups.values()){
  if(meshes.length<2)continue;
  const geometries=meshes.map(mesh=>{
   const geometry=floatGeometry(THREE,mesh.geometry);
   geometry.applyMatrix4(mesh.matrixWorld);
   return geometry;
  });
  const mergedGeometry=mergeGeometries(geometries,false);
  geometries.forEach(geometry=>geometry.dispose());
  if(!mergedGeometry)continue;
  const batch=new THREE.Mesh(mergedGeometry,meshes[0].material);
  batch.name=`PTS Static Detail Batch ${++drawGroups}`;
  batch.castShadow=meshes[0].castShadow;
  batch.receiveShadow=meshes[0].receiveShadow;
  batch.frustumCulled=true;
  scene.add(batch);
  meshes.forEach(mesh=>{mesh.visible=false;});
  batches.push(batch);sources.push(...meshes);
  sourceMeshes+=meshes.length;
 }
 // Features that animate or re-skin the individual meshes switch back to them while active.
 function setMerged(next){
  if(merged===next)return;
  merged=next;
  for(const batch of batches)batch.visible=next;
  for(const mesh of sources)mesh.visible=!next;
 }
 return {drawGroups,sourceMeshes,setMerged};
}
