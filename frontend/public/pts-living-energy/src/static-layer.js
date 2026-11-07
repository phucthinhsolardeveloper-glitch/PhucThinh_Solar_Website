/**
 * Keeps the still part of the scene in an antialiased off-screen image (colour and depth).
 * Moving energy flow is then drawn over it each frame, correctly hidden behind the building,
 * so a frame costs about 30 draw calls instead of the whole model and quality never has to drop.
 */
export const MOVING_LAYER=1;
const STILL_LAYER=0;

export function createStaticLayer(THREE,renderer){
 const supported=renderer.extensions.has('EXT_color_buffer_float')||renderer.extensions.has('EXT_color_buffer_half_float');
 if(!supported)return null;
 const target=new THREE.WebGLRenderTarget(4,4,{
  type:THREE.HalfFloatType,samples:4,depthBuffer:true,
  minFilter:THREE.NearestFilter,magFilter:THREE.NearestFilter,generateMipmaps:false,
  depthTexture:new THREE.DepthTexture(4,4,THREE.UnsignedIntType),
 });
 const quadScene=new THREE.Scene();
 const quadCamera=new THREE.OrthographicCamera(-1,1,1,-1,0,1);
 const material=new THREE.ShaderMaterial({
  uniforms:{tColor:{value:target.texture},tDepth:{value:target.depthTexture}},
  vertexShader:'varying vec2 vUv;void main(){vUv=uv;gl_Position=vec4(position.xy,0.,1.);}',
  fragmentShader:`
   uniform sampler2D tColor;uniform sampler2D tDepth;varying vec2 vUv;
   void main(){
    vec4 c=texture2D(tColor,vUv);
    float a=max(c.a,.0001);
    gl_FragColor=vec4(c.rgb/a,1.);
    #include <tonemapping_fragment>
    #include <colorspace_fragment>
    gl_FragColor=vec4(gl_FragColor.rgb*c.a,c.a);
    gl_FragDepth=texture2D(tDepth,vUv).r;
   }`,
  depthTest:true,depthWrite:true,depthFunc:THREE.AlwaysDepth,blending:THREE.NoBlending,transparent:false,
 });
 const quad=new THREE.Mesh(new THREE.PlaneGeometry(2,2),material);
 quad.frustumCulled=false;
 quadScene.add(quad);
 const size=new THREE.Vector2();
 const ALL=(1<<STILL_LAYER)|(1<<MOVING_LAYER);

 function renderStill(scene,camera){
  renderer.getDrawingBufferSize(size);
  if(target.width!==size.x||target.height!==size.y)target.setSize(size.x,size.y);
  camera.layers.set(STILL_LAYER);
  renderer.setRenderTarget(target);
  renderer.render(scene,camera);
  renderer.setRenderTarget(null);
  camera.layers.mask=ALL;
 }
 function present(scene,camera){
  const autoClear=renderer.autoClear;
  renderer.render(quadScene,quadCamera);
  renderer.autoClear=false;
  camera.layers.set(MOVING_LAYER);
  renderer.render(scene,camera);
  camera.layers.mask=ALL;
  renderer.autoClear=autoClear;
 }
 async function compile(scene,camera){
  renderer.setRenderTarget(target);
  await renderer.compileAsync(scene,camera).catch(()=>{});
  renderer.setRenderTarget(null);
  await renderer.compileAsync(quadScene,quadCamera).catch(()=>{});
 }
 return {renderStill,present,compile};
}
