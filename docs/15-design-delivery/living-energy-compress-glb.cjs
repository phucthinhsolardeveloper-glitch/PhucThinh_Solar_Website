#!/usr/bin/env node
/*
 * Living Energy GLB compressor (meshopt, structure-preserving).
 *
 * Usage (from a scratch dir, NOT inside the repo):
 *   npm i @gltf-transform/core@4.5.1 @gltf-transform/extensions@4.5.1 @gltf-transform/functions@4.5.1 meshoptimizer@1.2.0
 *   node living-energy-compress-glb.cjs <source.glb> <out.glb>
 *   cp <out.glb> frontend/public/pts-living-energy/assets/pts-technical-v05.glb
 *
 * Why not the plain CLI (`gltf-transform optimize` / `meshopt`): quantization moves the
 * dequantization scale into child nodes for animated meshes (e.g. PHOTON__* nodes). That
 * renames/unnames 33 meshes, which breaks name-based routing and the scene framing box.
 * This script folds those children back into their named parents, and then verifies the
 * node-name set is unchanged.
 */
// quantize splits animated mesh nodes into parent (animated translation) + unnamed child (mesh + dequant scale).
// Fold each such child back into its parent so names and animation targets stay as authored.
const {NodeIO}=require('@gltf-transform/core');
const {ALL_EXTENSIONS}=require('@gltf-transform/extensions');
const {meshopt}=require('@gltf-transform/functions');
const {MeshoptEncoder}=require('meshoptimizer');
(async()=>{
 const io=new NodeIO().registerExtensions(ALL_EXTENSIONS).registerDependencies({'meshopt.encoder':MeshoptEncoder});
 const doc=await io.read(process.argv[2]);
 const namesBefore=doc.getRoot().listNodes().map(n=>n.getName()).sort();
 await MeshoptEncoder.ready;
 await doc.transform(meshopt({encoder:MeshoptEncoder,level:'medium'}));
 let folded=0;
 for(const node of doc.getRoot().listNodes()){
  const kids=node.listChildren();
  if(node.getMesh()||kids.length!==1)continue;
  const child=kids[0];
  if(child.getName()||!child.getMesh()||child.listChildren().length)continue;
  if(node.getScale()&&node.getScale().some((v,i)=>v!==[1,1,1][i]))continue;
  if(child.getRotation().some((v,i)=>v!==[0,0,0,1][i])||child.getTranslation().some(v=>Math.abs(v)>1e-6))continue;
  node.setMesh(child.getMesh());node.setScale(child.getScale());node.setWeights?.(child.getWeights?.()||[]);
  node.removeChild(child);child.dispose();folded++;
 }
 console.log('folded',folded);
 const namesAfter=doc.getRoot().listNodes().map(n=>n.getName()).sort();
 if(JSON.stringify(namesBefore)!==JSON.stringify(namesAfter))throw new Error('node names changed; refusing to write');
 await io.write(process.argv[3],doc);
 console.log('node names verified:',namesAfter.length);
})().catch(e=>{console.error('ERR',e);process.exit(1);});
