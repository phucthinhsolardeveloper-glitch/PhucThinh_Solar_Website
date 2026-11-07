/**
 * @license
 * Copyright 2010-2026 Three.js Authors
 * SPDX-License-Identifier: MIT
 */import{Matrix3 as Oe,Vector2 as ht,Color as Ye,mergeUniforms as vt,Vector3 as We,Mesh as Gt,BoxGeometry as Sn,ShaderMaterial as qt,BackSide as St,cloneUniforms as Vn,Matrix4 as Yt,ColorManagement as Je,SRGBTransfer as Xe,PlaneGeometry as Wn,FrontSide as jt,getUnlitUniformColorSpace as pr,IntType as kn,warn as He,HalfFloatType as Dt,UnsignedByteType as Ct,FloatType as yt,RGBAFormat as Ft,Plane as hr,WebGLRenderTarget as bt,CubeTexture as zn,NoBlending as Zt,LinearMipmapLinearFilter as Ht,LinearFilter as At,CubeCamera as En,floorPowerOfTwo as mo,CubeReflectionMapping as ln,CubeRefractionMapping as en,LinearSRGBColorSpace as Xn,EquirectangularReflectionMapping as Mn,EquirectangularRefractionMapping as xn,warnOnce as mr,Uint32BufferAttribute as _r,Uint16BufferAttribute as gr,error as Ze,DataArrayTexture as Kn,Vector4 as mt,BufferGeometry as qn,Float32BufferAttribute as Yn,RawShaderMaterial as vr,OrthographicCamera as Sr,DepthTexture as tn,DepthStencilFormat as Vt,UnsignedInt248Type as $t,NoToneMapping as Ut,CustomToneMapping as Zn,NeutralToneMapping as $n,AgXToneMapping as Qn,ACESFilmicToneMapping as Jn,CineonToneMapping as jn,ReinhardToneMapping as ei,LinearToneMapping as ti,Data3DTexture as Er,AlwaysCompare as ni,Texture as Mr,CubeDepthTexture as ii,GLSL3 as ri,VSMShadowMap as nn,PCFShadowMap as cn,AddOperation as xr,MixOperation as Tr,MultiplyOperation as Ar,LinearTransfer as ai,UniformsUtils as Rr,DoubleSide as wt,NormalBlending as fn,TangentSpaceNormalMap as oi,ObjectSpaceNormalMap as Cr,Layers as br,RGFormat as Qt,RG11_EAC_Format as Tn,RED_GREEN_RGTC2_Format as An,MeshDepthMaterial as Pr,MeshDistanceMaterial as Lr,BufferAttribute as Dr,PCFSoftShadowMap as Ur,DepthFormat as rn,NearestFilter as Wt,UnsignedIntType as kt,GreaterEqualCompare as si,LessEqualCompare as li,Frustum as ci,LessEqualDepth as fi,ReverseSubtractEquation as wr,SubtractEquation as Nr,AddEquation as an,OneMinusConstantAlphaFactor as Ir,ConstantAlphaFactor as yr,OneMinusConstantColorFactor as Fr,ConstantColorFactor as Or,OneMinusDstAlphaFactor as Br,OneMinusDstColorFactor as Gr,OneMinusSrcAlphaFactor as Hr,OneMinusSrcColorFactor as Vr,DstAlphaFactor as Wr,DstColorFactor as kr,SrcAlphaSaturateFactor as zr,SrcAlphaFactor as Xr,SrcColorFactor as Kr,OneFactor as qr,ZeroFactor as Yr,NotEqualDepth as Zr,GreaterDepth as $r,GreaterEqualDepth as Qr,EqualDepth as Jr,LessDepth as jr,AlwaysDepth as ea,NeverDepth as ta,CullFaceNone as na,CullFaceBack as di,CullFaceFront as ia,CustomBlending as ra,MultiplyBlending as ui,SubtractiveBlending as pi,AdditiveBlending as hi,ReversedDepthFuncs as _o,MinEquation as aa,MaxEquation as oa,MirroredRepeatWrapping as sa,ClampToEdgeWrapping as Rn,RepeatWrapping as la,LinearMipmapNearestFilter as Cn,NearestMipmapLinearFilter as dn,NearestMipmapNearestFilter as ca,NotEqualCompare as fa,GreaterCompare as da,EqualCompare as ua,LessCompare as pa,NeverCompare as ha,NoColorSpace as Jt,getByteLength as ma,UnsignedShortType as un,createElementNS as go,UnsignedShort4444Type as mi,UnsignedShort5551Type as _i,UnsignedInt5999Type as _a,UnsignedInt101111Type as ga,ByteType as va,ShortType as Sa,AlphaFormat as Ea,RGBFormat as Ma,RedFormat as xa,RedIntegerFormat as gi,RGIntegerFormat as vi,RGBAIntegerFormat as Si,RGB_S3TC_DXT1_Format as bn,RGBA_S3TC_DXT1_Format as Pn,RGBA_S3TC_DXT3_Format as Ln,RGBA_S3TC_DXT5_Format as Dn,RGB_PVRTC_4BPPV1_Format as Ei,RGB_PVRTC_2BPPV1_Format as Mi,RGBA_PVRTC_4BPPV1_Format as xi,RGBA_PVRTC_2BPPV1_Format as Ti,RGB_ETC1_Format as Ai,RGB_ETC2_Format as Ri,RGBA_ETC2_EAC_Format as Ci,R11_EAC_Format as bi,SIGNED_R11_EAC_Format as Pi,SIGNED_RG11_EAC_Format as Li,RGBA_ASTC_4x4_Format as Di,RGBA_ASTC_5x4_Format as Ui,RGBA_ASTC_5x5_Format as wi,RGBA_ASTC_6x5_Format as Ni,RGBA_ASTC_6x6_Format as Ii,RGBA_ASTC_8x5_Format as yi,RGBA_ASTC_8x6_Format as Fi,RGBA_ASTC_8x8_Format as Oi,RGBA_ASTC_10x5_Format as Bi,RGBA_ASTC_10x6_Format as Gi,RGBA_ASTC_10x8_Format as Hi,RGBA_ASTC_10x10_Format as Vi,RGBA_ASTC_12x10_Format as Wi,RGBA_ASTC_12x12_Format as ki,RGBA_BPTC_Format as zi,RGB_BPTC_SIGNED_Format as Xi,RGB_BPTC_UNSIGNED_Format as Ki,RED_RGTC1_Format as qi,SIGNED_RED_RGTC1_Format as Yi,SIGNED_RED_GREEN_RGTC2_Format as Zi,ExternalTexture as $i,EventDispatcher as Ta,PerspectiveCamera as Un,ArrayCamera as Aa,WebXRController as wn,RAD2DEG as vo,DataTexture as Ra,createCanvasElement as Ca,SRGBColorSpace as ba,REVISION as Pa,log as Qi,FrustumArray as La,WebGLCoordinateSystem as Ji,probeAsync as So}from"./three.core.js";import{AdditiveAnimationBlendMode as _d,AlwaysStencilFunc as gd,AmbientLight as vd,AnimationAction as Sd,AnimationClip as Ed,AnimationLoader as Md,AnimationMixer as xd,AnimationObjectGroup as Td,AnimationUtils as Ad,ArcCurve as Rd,ArrowHelper as Cd,AttachedBindMode as bd,Audio as Pd,AudioAnalyser as Ld,AudioContext as Dd,AudioListener as Ud,AudioLoader as wd,AxesHelper as Nd,BasicDepthPacking as Id,BasicShadowMap as yd,BatchedMesh as Fd,BezierInterpolant as Od,Bone as Bd,BooleanKeyframeTrack as Gd,Box2 as Hd,Box3 as Vd,Box3Helper as Wd,BoxHelper as kd,BufferGeometryLoader as zd,Cache as Xd,Camera as Kd,CameraHelper as qd,CanvasTexture as Yd,CapsuleGeometry as Zd,CatmullRomCurve3 as $d,CircleGeometry as Qd,Clock as Jd,ColorKeyframeTrack as jd,Compatibility as eu,CompressedArrayTexture as tu,CompressedCubeTexture as nu,CompressedTexture as iu,CompressedTextureLoader as ru,ConeGeometry as au,Controls as ou,CubeTextureLoader as su,CubicBezierCurve as lu,CubicBezierCurve3 as cu,CubicInterpolant as fu,CullFaceFrontBack as du,Curve as uu,CurvePath as pu,CylinderGeometry as hu,Cylindrical as mu,DataTextureLoader as _u,DataUtils as gu,DecrementStencilOp as vu,DecrementWrapStencilOp as Su,DefaultLoadingManager as Eu,DetachedBindMode as Mu,DirectionalLight as xu,DirectionalLightHelper as Tu,DiscreteInterpolant as Au,DodecahedronGeometry as Ru,DynamicCopyUsage as Cu,DynamicDrawUsage as bu,DynamicReadUsage as Pu,EdgesGeometry as Lu,EllipseCurve as Du,EqualStencilFunc as Uu,Euler as wu,ExtrudeGeometry as Nu,FileLoader as Iu,Float16BufferAttribute as yu,Fog as Fu,FogExp2 as Ou,FramebufferTexture as Bu,GLBufferAttribute as Gu,GLSL1 as Hu,GreaterEqualStencilFunc as Vu,GreaterStencilFunc as Wu,GridHelper as ku,Group as zu,HTMLTexture as Xu,HemisphereLight as Ku,HemisphereLightHelper as qu,IcosahedronGeometry as Yu,ImageBitmapLoader as Zu,ImageLoader as $u,ImageUtils as Qu,IncrementStencilOp as Ju,IncrementWrapStencilOp as ju,InstancedBufferAttribute as ep,InstancedBufferGeometry as tp,InstancedInterleavedBuffer as np,InstancedMesh as ip,Int16BufferAttribute as rp,Int32BufferAttribute as ap,Int8BufferAttribute as op,InterleavedBuffer as sp,InterleavedBufferAttribute as lp,Interpolant as cp,InterpolateBezier as fp,InterpolateDiscrete as dp,InterpolateLinear as up,InterpolateSmooth as pp,InterpolationSamplingMode as hp,InterpolationSamplingType as mp,InvertStencilOp as _p,KeepStencilOp as gp,KeyframeTrack as vp,LOD as Sp,LatheGeometry as Ep,LessEqualStencilFunc as Mp,LessStencilFunc as xp,Light as Tp,LightProbe as Ap,LightShadow as Rp,Line as Cp,Line3 as bp,LineBasicMaterial as Pp,LineCurve as Lp,LineCurve3 as Dp,LineDashedMaterial as Up,LineLoop as wp,LineSegments as Np,LinearInterpolant as Ip,LinearMipMapLinearFilter as yp,LinearMipMapNearestFilter as Fp,Loader as Op,LoaderUtils as Bp,LoadingManager as Gp,LoopOnce as Hp,LoopPingPong as Vp,LoopRepeat as Wp,MOUSE as kp,Material as zp,MaterialBlending as Xp,MaterialLoader as Kp,MathUtils as qp,Matrix2 as Yp,MeshBasicMaterial as Zp,MeshLambertMaterial as $p,MeshMatcapMaterial as Qp,MeshNormalMaterial as Jp,MeshPhongMaterial as jp,MeshPhysicalMaterial as eh,MeshStandardMaterial as th,MeshToonMaterial as nh,NearestMipMapLinearFilter as ih,NearestMipMapNearestFilter as rh,NeverStencilFunc as ah,NoNormalPacking as oh,NormalAnimationBlendMode as sh,NormalGAPacking as lh,NormalRGPacking as ch,NotEqualStencilFunc as fh,NumberKeyframeTrack as dh,Object3D as uh,ObjectLoader as ph,OctahedronGeometry as hh,Path as mh,PlaneHelper as _h,PointLight as gh,PointLightHelper as vh,Points as Sh,PointsMaterial as Eh,PolarGridHelper as Mh,PolyhedronGeometry as xh,PositionalAudio as Th,PropertyBinding as Ah,PropertyMixer as Rh,QuadraticBezierCurve as Ch,QuadraticBezierCurve3 as bh,Quaternion as Ph,QuaternionKeyframeTrack as Lh,QuaternionLinearInterpolant as Dh,RGBADepthPacking as Uh,RGBDepthPacking as wh,RGBIntegerFormat as Nh,RGDepthPacking as Ih,Ray as yh,Raycaster as Fh,RectAreaLight as Oh,RenderObjectRefreshType as Bh,RenderTarget as Gh,RenderTarget3D as Hh,ReplaceStencilOp as Vh,RingGeometry as Wh,Scene as kh,ShadowMaterial as zh,Shape as Xh,ShapeGeometry as Kh,ShapePath as qh,ShapeUtils as Yh,Skeleton as Zh,SkeletonHelper as $h,SkinnedMesh as Qh,Source as Jh,Sphere as jh,SphereGeometry as em,Spherical as tm,SphericalHarmonics3 as nm,SplineCurve as im,SpotLight as rm,SpotLightHelper as am,Sprite as om,SpriteMaterial as sm,StaticCopyUsage as lm,StaticDrawUsage as cm,StaticReadUsage as fm,StereoCamera as dm,StreamCopyUsage as um,StreamDrawUsage as pm,StreamReadUsage as hm,StringKeyframeTrack as mm,TOUCH as _m,TetrahedronGeometry as gm,TextureLoader as vm,TextureSource as Sm,TextureUtils as Em,Timer as Mm,TimestampQuery as xm,TorusGeometry as Tm,TorusKnotGeometry as Am,Triangle as Rm,TriangleFanDrawMode as Cm,TriangleStripDrawMode as bm,TrianglesDrawMode as Pm,TubeGeometry as Lm,UVMapping as Dm,Uint8BufferAttribute as Um,Uint8ClampedBufferAttribute as wm,Uniform as Nm,UniformsGroup as Im,VectorKeyframeTrack as ym,VideoFrameTexture as Fm,VideoTexture as Om,WebGL3DRenderTarget as Bm,WebGLArrayRenderTarget as Gm,WebGPUCoordinateSystem as Hm,WireframeGeometry as Vm,WrapAroundEnding as Wm,ZeroCurvatureEnding as km,ZeroSlopeEnding as zm,ZeroStencilOp as Xm,getConsoleFunction as Km,setConsoleFunction as qm}from"./three.core.js";function Da(){let e=null,n=!1,t=null,i=null;function c(s,u){i=e.requestAnimationFrame(c),t(s,u)}return{start:function(){n!==!0&&t!==null&&e!==null&&(i=e.requestAnimationFrame(c),n=!0)},stop:function(){e!==null&&e.cancelAnimationFrame(i),n=!1},setAnimationLoop:function(s){t=s},setContext:function(s){e=s}}}function Eo(e){const n=new WeakMap;function t(m,I){const C=m.array,X=m.usage,F=C.byteLength,v=e.createBuffer();e.bindBuffer(I,v),e.bufferData(I,C,X),m.onUploadCallback();let M;if(C instanceof Float32Array)M=e.FLOAT;else if(typeof Float16Array<"u"&&C instanceof Float16Array)M=e.HALF_FLOAT;else if(C instanceof Uint16Array)m.isFloat16BufferAttribute?M=e.HALF_FLOAT:M=e.UNSIGNED_SHORT;else if(C instanceof Int16Array)M=e.SHORT;else if(C instanceof Uint32Array)M=e.UNSIGNED_INT;else if(C instanceof Int32Array)M=e.INT;else if(C instanceof Int8Array)M=e.BYTE;else if(C instanceof Uint8Array)M=e.UNSIGNED_BYTE;else if(C instanceof Uint8ClampedArray)M=e.UNSIGNED_BYTE;else throw new Error("THREE.WebGLAttributes: Unsupported buffer data format: "+C);return{buffer:v,type:M,bytesPerElement:C.BYTES_PER_ELEMENT,version:m.version,size:F}}function i(m,I,C){const X=I.array,F=I.updateRanges;if(e.bindBuffer(C,m),F.length===0)e.bufferSubData(C,0,X);else{F.sort((M,L)=>M.start-L.start);let v=0;for(let M=1;M<F.length;M++){const L=F[v],B=F[M];B.start<=L.start+L.count+1?L.count=Math.max(L.count,B.start+B.count-L.start):(++v,F[v]=B)}F.length=v+1;for(let M=0,L=F.length;M<L;M++){const B=F[M];e.bufferSubData(C,B.start*X.BYTES_PER_ELEMENT,X,B.start,B.count)}I.clearUpdateRanges()}I.onUploadCallback()}function c(m){return m.isInterleavedBufferAttribute&&(m=m.data),n.get(m)}function s(m){m.isInterleavedBufferAttribute&&(m=m.data);const I=n.get(m);I&&(e.deleteBuffer(I.buffer),n.delete(m))}function u(m,I){if(m.isInterleavedBufferAttribute&&(m=m.data),m.isGLBufferAttribute){const X=n.get(m);(!X||X.version<m.version)&&n.set(m,{buffer:m.buffer,type:m.type,bytesPerElement:m.elementSize,version:m.version});return}const C=n.get(m);if(C===void 0)n.set(m,t(m,I));else if(C.version<m.version){if(C.size!==m.array.byteLength)throw new Error("THREE.WebGLAttributes: The size of the buffer attribute's array buffer does not match the original size. Resizing buffer attributes is not supported.");i(C.buffer,m,I),C.version=m.version}}return{get:c,remove:s,update:u}}var Mo=`#ifdef USE_ALPHAHASH
	if ( diffuseColor.a < getAlphaHashThreshold( vPosition ) ) discard;
#endif`,xo=`#ifdef USE_ALPHAHASH
	const float ALPHA_HASH_SCALE = 0.05;
	float hash2D( vec2 value ) {
		return fract( 1.0e4 * sin( 17.0 * value.x + 0.1 * value.y ) * ( 0.1 + abs( sin( 13.0 * value.y + value.x ) ) ) );
	}
	float hash3D( vec3 value ) {
		return hash2D( vec2( hash2D( value.xy ), value.z ) );
	}
	float getAlphaHashThreshold( vec3 position ) {
		float maxDeriv = max(
			length( dFdx( position.xyz ) ),
			length( dFdy( position.xyz ) )
		);
		float pixScale = 1.0 / ( ALPHA_HASH_SCALE * maxDeriv );
		vec2 pixScales = vec2(
			exp2( floor( log2( pixScale ) ) ),
			exp2( ceil( log2( pixScale ) ) )
		);
		vec2 alpha = vec2(
			hash3D( floor( pixScales.x * position.xyz ) ),
			hash3D( floor( pixScales.y * position.xyz ) )
		);
		float lerpFactor = fract( log2( pixScale ) );
		float x = ( 1.0 - lerpFactor ) * alpha.x + lerpFactor * alpha.y;
		float a = min( lerpFactor, 1.0 - lerpFactor );
		vec3 cases = vec3(
			x * x / ( 2.0 * a * ( 1.0 - a ) ),
			( x - 0.5 * a ) / ( 1.0 - a ),
			1.0 - ( ( 1.0 - x ) * ( 1.0 - x ) / ( 2.0 * a * ( 1.0 - a ) ) )
		);
		float threshold = ( x < ( 1.0 - a ) )
			? ( ( x < a ) ? cases.x : cases.y )
			: cases.z;
		return clamp( threshold , 1.0e-6, 1.0 );
	}
#endif`,To=`#ifdef USE_ALPHAMAP
	diffuseColor.a *= texture2D( alphaMap, vAlphaMapUv ).g;
#endif`,Ao=`#ifdef USE_ALPHAMAP
	uniform sampler2D alphaMap;
#endif`,Ro=`#ifdef USE_ALPHATEST
	#ifdef ALPHA_TO_COVERAGE
	diffuseColor.a = smoothstep( alphaTest, alphaTest + fwidth( diffuseColor.a ), diffuseColor.a );
	if ( diffuseColor.a == 0.0 ) discard;
	#else
	if ( diffuseColor.a < alphaTest ) discard;
	#endif
#endif`,Co=`#ifdef USE_ALPHATEST
	uniform float alphaTest;
#endif`,bo=`#ifdef USE_AOMAP
	float ambientOcclusion = ( texture2D( aoMap, vAoMapUv ).r - 1.0 ) * aoMapIntensity + 1.0;
	reflectedLight.indirectDiffuse *= ambientOcclusion;
	#if defined( USE_CLEARCOAT ) 
		clearcoatSpecularIndirect *= ambientOcclusion;
	#endif
	#if defined( USE_SHEEN ) 
		sheenSpecularIndirect *= ambientOcclusion;
	#endif
	#if defined( USE_ENVMAP ) && defined( STANDARD )
		float dotNV = saturate( dot( geometryNormal, geometryViewDir ) );
		reflectedLight.indirectSpecular *= computeSpecularOcclusion( dotNV, ambientOcclusion, material.roughness );
	#endif
#endif`,Po=`#ifdef USE_AOMAP
	uniform sampler2D aoMap;
	uniform float aoMapIntensity;
#endif`,Lo=`#ifdef USE_BATCHING
	#if ! defined( GL_ANGLE_multi_draw )
	#define gl_DrawID _gl_DrawID
	uniform int _gl_DrawID;
	#endif
	uniform highp sampler2D batchingTexture;
	uniform highp usampler2D batchingIdTexture;
	mat4 getBatchingMatrix( const in float i ) {
		int size = textureSize( batchingTexture, 0 ).x;
		int j = int( i ) * 4;
		int x = j % size;
		int y = j / size;
		vec4 v1 = texelFetch( batchingTexture, ivec2( x, y ), 0 );
		vec4 v2 = texelFetch( batchingTexture, ivec2( x + 1, y ), 0 );
		vec4 v3 = texelFetch( batchingTexture, ivec2( x + 2, y ), 0 );
		vec4 v4 = texelFetch( batchingTexture, ivec2( x + 3, y ), 0 );
		return mat4( v1, v2, v3, v4 );
	}
	float getIndirectIndex( const in int i ) {
		int size = textureSize( batchingIdTexture, 0 ).x;
		int x = i % size;
		int y = i / size;
		return float( texelFetch( batchingIdTexture, ivec2( x, y ), 0 ).r );
	}
#endif
#ifdef USE_BATCHING_COLOR
	uniform sampler2D batchingColorTexture;
	vec4 getBatchingColor( const in float i ) {
		int size = textureSize( batchingColorTexture, 0 ).x;
		int j = int( i );
		int x = j % size;
		int y = j / size;
		return texelFetch( batchingColorTexture, ivec2( x, y ), 0 );
	}
#endif`,Do=`#ifdef USE_BATCHING
	mat4 batchingMatrix = getBatchingMatrix( getIndirectIndex( gl_DrawID ) );
#endif`,Uo=`vec3 transformed = vec3( position );
#ifdef USE_ALPHAHASH
	vPosition = vec3( position );
#endif`,wo=`vec3 objectNormal = vec3( normal );
#ifdef USE_TANGENT
	vec3 objectTangent = vec3( tangent.xyz );
#endif`,No=`float G_BlinnPhong_Implicit( ) {
	return 0.25;
}
float D_BlinnPhong( const in float shininess, const in float dotNH ) {
	return RECIPROCAL_PI * ( shininess * 0.5 + 1.0 ) * pow( dotNH, shininess );
}
vec3 BRDF_BlinnPhong( const in vec3 lightDir, const in vec3 viewDir, const in vec3 normal, const in vec3 specularColor, const in float shininess ) {
	vec3 halfDir = normalize( lightDir + viewDir );
	float dotNH = saturate( dot( normal, halfDir ) );
	float dotVH = saturate( dot( viewDir, halfDir ) );
	vec3 F = F_Schlick( specularColor, 1.0, dotVH );
	float G = G_BlinnPhong_Implicit( );
	float D = D_BlinnPhong( shininess, dotNH );
	return F * ( G * D );
} // validated`,Io=`#ifdef USE_IRIDESCENCE
	const mat3 XYZ_TO_REC709 = mat3(
		 3.2404542, -0.9692660,  0.0556434,
		-1.5371385,  1.8760108, -0.2040259,
		-0.4985314,  0.0415560,  1.0572252
	);
	vec3 Fresnel0ToIor( vec3 fresnel0 ) {
		vec3 sqrtF0 = sqrt( fresnel0 );
		return ( vec3( 1.0 ) + sqrtF0 ) / ( vec3( 1.0 ) - sqrtF0 );
	}
	vec3 IorToFresnel0( vec3 transmittedIor, float incidentIor ) {
		return pow2( ( transmittedIor - vec3( incidentIor ) ) / ( transmittedIor + vec3( incidentIor ) ) );
	}
	float IorToFresnel0( float transmittedIor, float incidentIor ) {
		return pow2( ( transmittedIor - incidentIor ) / ( transmittedIor + incidentIor ));
	}
	vec3 evalSensitivity( float OPD, vec3 shift ) {
		float phase = 2.0 * PI * OPD * 1.0e-9;
		vec3 val = vec3( 5.4856e-13, 4.4201e-13, 5.2481e-13 );
		vec3 pos = vec3( 1.6810e+06, 1.7953e+06, 2.2084e+06 );
		vec3 var = vec3( 4.3278e+09, 9.3046e+09, 6.6121e+09 );
		vec3 xyz = val * sqrt( 2.0 * PI * var ) * cos( pos * phase + shift ) * exp( - pow2( phase ) * var );
		xyz.x += 9.7470e-14 * sqrt( 2.0 * PI * 4.5282e+09 ) * cos( 2.2399e+06 * phase + shift[ 0 ] ) * exp( - 4.5282e+09 * pow2( phase ) );
		xyz /= 1.0685e-7;
		vec3 rgb = XYZ_TO_REC709 * xyz;
		return rgb;
	}
	vec3 evalIridescence( float outsideIOR, float eta2, float cosTheta1, float thinFilmThickness, vec3 baseF0 ) {
		vec3 I;
		float iridescenceIOR = mix( outsideIOR, eta2, smoothstep( 0.0, 0.03, thinFilmThickness ) );
		float sinTheta2Sq = pow2( outsideIOR / iridescenceIOR ) * ( 1.0 - pow2( cosTheta1 ) );
		float cosTheta2Sq = 1.0 - sinTheta2Sq;
		if ( cosTheta2Sq < 0.0 ) {
			return vec3( 1.0 );
		}
		float cosTheta2 = sqrt( cosTheta2Sq );
		float R0 = IorToFresnel0( iridescenceIOR, outsideIOR );
		float R12 = F_Schlick( R0, 1.0, cosTheta1 );
		float T121 = 1.0 - R12;
		float phi12 = 0.0;
		if ( iridescenceIOR < outsideIOR ) phi12 = PI;
		float phi21 = PI - phi12;
		vec3 baseIOR = Fresnel0ToIor( clamp( baseF0, 0.0, 0.9999 ) );		vec3 R1 = IorToFresnel0( baseIOR, iridescenceIOR );
		vec3 R23 = F_Schlick( R1, 1.0, cosTheta2 );
		vec3 phi23 = vec3( 0.0 );
		if ( baseIOR[ 0 ] < iridescenceIOR ) phi23[ 0 ] = PI;
		if ( baseIOR[ 1 ] < iridescenceIOR ) phi23[ 1 ] = PI;
		if ( baseIOR[ 2 ] < iridescenceIOR ) phi23[ 2 ] = PI;
		float OPD = 2.0 * iridescenceIOR * thinFilmThickness * cosTheta2;
		vec3 phi = vec3( phi21 ) + phi23;
		vec3 R123 = clamp( R12 * R23, 1e-5, 0.9999 );
		vec3 r123 = sqrt( R123 );
		vec3 Rs = pow2( T121 ) * R23 / ( vec3( 1.0 ) - R123 );
		vec3 C0 = R12 + Rs;
		I = C0;
		vec3 Cm = Rs - T121;
		for ( int m = 1; m <= 2; ++ m ) {
			Cm *= r123;
			vec3 Sm = 2.0 * evalSensitivity( float( m ) * OPD, float( m ) * phi );
			I += Cm * Sm;
		}
		return max( I, vec3( 0.0 ) );
	}
#endif`,yo=`#ifdef USE_BUMPMAP
	uniform sampler2D bumpMap;
	uniform float bumpScale;
	vec2 dHdxy_fwd() {
		vec2 dSTdx = dFdx( vBumpMapUv );
		vec2 dSTdy = dFdy( vBumpMapUv );
		float Hll = bumpScale * texture2D( bumpMap, vBumpMapUv ).x;
		float dBx = bumpScale * texture2D( bumpMap, vBumpMapUv + dSTdx ).x - Hll;
		float dBy = bumpScale * texture2D( bumpMap, vBumpMapUv + dSTdy ).x - Hll;
		return vec2( dBx, dBy );
	}
	vec3 perturbNormalArb( vec3 surf_pos, vec3 surf_norm, vec2 dHdxy, float faceDirection ) {
		vec3 vSigmaX = normalize( dFdx( surf_pos.xyz ) );
		vec3 vSigmaY = normalize( dFdy( surf_pos.xyz ) );
		vec3 vN = surf_norm;
		vec3 R1 = cross( vSigmaY, vN );
		vec3 R2 = cross( vN, vSigmaX );
		float fDet = dot( vSigmaX, R1 ) * faceDirection;
		vec3 vGrad = sign( fDet ) * ( dHdxy.x * R1 + dHdxy.y * R2 );
		return normalize( abs( fDet ) * surf_norm - vGrad );
	}
#endif`,Fo=`#if NUM_CLIPPING_PLANES > 0
	vec4 plane;
	#ifdef ALPHA_TO_COVERAGE
		float distanceToPlane, distanceGradient;
		float clipOpacity = 1.0;
		#pragma unroll_loop_start
		for ( int i = 0; i < UNION_CLIPPING_PLANES; i ++ ) {
			plane = clippingPlanes[ i ];
			distanceToPlane = - dot( vClipPosition, plane.xyz ) + plane.w;
			distanceGradient = fwidth( distanceToPlane ) / 2.0;
			clipOpacity *= smoothstep( - distanceGradient, distanceGradient, distanceToPlane );
			if ( clipOpacity == 0.0 ) discard;
		}
		#pragma unroll_loop_end
		#if UNION_CLIPPING_PLANES < NUM_CLIPPING_PLANES
			float unionClipOpacity = 1.0;
			#pragma unroll_loop_start
			for ( int i = UNION_CLIPPING_PLANES; i < NUM_CLIPPING_PLANES; i ++ ) {
				plane = clippingPlanes[ i ];
				distanceToPlane = - dot( vClipPosition, plane.xyz ) + plane.w;
				distanceGradient = fwidth( distanceToPlane ) / 2.0;
				unionClipOpacity *= 1.0 - smoothstep( - distanceGradient, distanceGradient, distanceToPlane );
			}
			#pragma unroll_loop_end
			clipOpacity *= 1.0 - unionClipOpacity;
		#endif
		diffuseColor.a *= clipOpacity;
		if ( diffuseColor.a == 0.0 ) discard;
	#else
		#pragma unroll_loop_start
		for ( int i = 0; i < UNION_CLIPPING_PLANES; i ++ ) {
			plane = clippingPlanes[ i ];
			if ( dot( vClipPosition, plane.xyz ) > plane.w ) discard;
		}
		#pragma unroll_loop_end
		#if UNION_CLIPPING_PLANES < NUM_CLIPPING_PLANES
			bool clipped = true;
			#pragma unroll_loop_start
			for ( int i = UNION_CLIPPING_PLANES; i < NUM_CLIPPING_PLANES; i ++ ) {
				plane = clippingPlanes[ i ];
				clipped = ( dot( vClipPosition, plane.xyz ) > plane.w ) && clipped;
			}
			#pragma unroll_loop_end
			if ( clipped ) discard;
		#endif
	#endif
#endif`,Oo=`#if NUM_CLIPPING_PLANES > 0
	varying vec3 vClipPosition;
	uniform vec4 clippingPlanes[ NUM_CLIPPING_PLANES ];
#endif`,Bo=`#if NUM_CLIPPING_PLANES > 0
	varying vec3 vClipPosition;
#endif`,Go=`#if NUM_CLIPPING_PLANES > 0
	vClipPosition = - mvPosition.xyz;
#endif`,Ho=`#if defined( USE_COLOR ) || defined( USE_COLOR_ALPHA )
	diffuseColor *= vColor;
#endif`,Vo=`#if defined( USE_COLOR ) || defined( USE_COLOR_ALPHA )
	varying vec4 vColor;
#endif`,Wo=`#if defined( USE_COLOR ) || defined( USE_COLOR_ALPHA ) || defined( USE_INSTANCING_COLOR ) || defined( USE_BATCHING_COLOR )
	varying vec4 vColor;
#endif`,ko=`#if defined( USE_COLOR ) || defined( USE_COLOR_ALPHA ) || defined( USE_INSTANCING_COLOR ) || defined( USE_BATCHING_COLOR )
	vColor = vec4( 1.0 );
#endif
#ifdef USE_COLOR_ALPHA
	vColor *= color;
#elif defined( USE_COLOR )
	vColor.rgb *= color;
#endif
#ifdef USE_INSTANCING_COLOR
	vColor.rgb *= instanceColor.rgb;
#endif
#ifdef USE_BATCHING_COLOR
	vColor *= getBatchingColor( getIndirectIndex( gl_DrawID ) );
#endif`,zo=`#define PI 3.141592653589793
#define PI2 6.283185307179586
#define PI_HALF 1.5707963267948966
#define RECIPROCAL_PI 0.3183098861837907
#define RECIPROCAL_PI2 0.15915494309189535
#define EPSILON 1e-6
#ifndef saturate
#define saturate( a ) clamp( a, 0.0, 1.0 )
#endif
#define whiteComplement( a ) ( 1.0 - saturate( a ) )
float pow2( const in float x ) { return x*x; }
vec3 pow2( const in vec3 x ) { return x*x; }
float pow3( const in float x ) { return x*x*x; }
float pow4( const in float x ) { float x2 = x*x; return x2*x2; }
float max3( const in vec3 v ) { return max( max( v.x, v.y ), v.z ); }
float average( const in vec3 v ) { return dot( v, vec3( 0.3333333 ) ); }
highp float rand( const in vec2 uv ) {
	const highp float a = 12.9898, b = 78.233, c = 43758.5453;
	highp float dt = dot( uv.xy, vec2( a,b ) ), sn = mod( dt, PI );
	return fract( sin( sn ) * c );
}
#ifdef HIGH_PRECISION
	float precisionSafeLength( vec3 v ) { return length( v ); }
#else
	float precisionSafeLength( vec3 v ) {
		float maxComponent = max3( abs( v ) );
		return length( v / maxComponent ) * maxComponent;
	}
#endif
struct IncidentLight {
	vec3 color;
	vec3 direction;
	bool visible;
};
struct ReflectedLight {
	vec3 directDiffuse;
	vec3 directSpecular;
	vec3 indirectDiffuse;
	vec3 indirectSpecular;
};
#ifdef USE_ALPHAHASH
	varying vec3 vPosition;
#endif
vec3 transformDirection( in vec3 dir, in mat4 matrix ) {
	return normalize( ( matrix * vec4( dir, 0.0 ) ).xyz );
}
#define inverseTransformDirection transformDirectionByInverseViewMatrix
vec3 transformNormalByInverseViewMatrix( in vec3 normal, in mat4 viewMatrix ) {
	return normalize( ( vec4( normal, 0.0 ) * viewMatrix ).xyz );
}
vec3 transformDirectionByInverseViewMatrix( in vec3 dir, in mat4 viewMatrix ) {
	return normalize( ( vec4( dir, 0.0 ) * viewMatrix ).xyz );
}
bool isPerspectiveMatrix( mat4 m ) {
	return m[ 2 ][ 3 ] == - 1.0;
}
vec2 equirectUv( in vec3 dir ) {
	float u = atan( dir.z, dir.x ) * RECIPROCAL_PI2 + 0.5;
	float v = asin( clamp( dir.y, - 1.0, 1.0 ) ) * RECIPROCAL_PI + 0.5;
	return vec2( u, v );
}
vec3 BRDF_Lambert( const in vec3 diffuseColor ) {
	return RECIPROCAL_PI * diffuseColor;
}
vec3 F_Schlick( const in vec3 f0, const in float f90, const in float dotVH ) {
	float fresnel = exp2( ( - 5.55473 * dotVH - 6.98316 ) * dotVH );
	return f0 * ( 1.0 - fresnel ) + ( f90 * fresnel );
}
float F_Schlick( const in float f0, const in float f90, const in float dotVH ) {
	float fresnel = exp2( ( - 5.55473 * dotVH - 6.98316 ) * dotVH );
	return f0 * ( 1.0 - fresnel ) + ( f90 * fresnel );
} // validated`,Xo=`vec3 transformedNormal = objectNormal;
#ifdef USE_TANGENT
	vec3 transformedTangent = objectTangent;
#endif
#ifdef USE_BATCHING
	mat3 bm = mat3( batchingMatrix );
	transformedNormal /= vec3( dot( bm[ 0 ], bm[ 0 ] ), dot( bm[ 1 ], bm[ 1 ] ), dot( bm[ 2 ], bm[ 2 ] ) );
	transformedNormal = bm * transformedNormal;
	#ifdef USE_TANGENT
		transformedTangent = bm * transformedTangent;
	#endif
#endif
#ifdef USE_INSTANCING
	mat3 im = mat3( instanceMatrix );
	transformedNormal /= vec3( dot( im[ 0 ], im[ 0 ] ), dot( im[ 1 ], im[ 1 ] ), dot( im[ 2 ], im[ 2 ] ) );
	transformedNormal = im * transformedNormal;
	#ifdef USE_TANGENT
		transformedTangent = im * transformedTangent;
	#endif
#endif
transformedNormal = normalMatrix * transformedNormal;
#ifdef FLIP_SIDED
	transformedNormal = - transformedNormal;
#endif
#ifdef USE_TANGENT
	transformedTangent = ( modelViewMatrix * vec4( transformedTangent, 0.0 ) ).xyz;
#endif`,Ko=`#ifdef USE_DISPLACEMENTMAP
	uniform sampler2D displacementMap;
	uniform float displacementScale;
	uniform float displacementBias;
#endif`,qo=`#ifdef USE_DISPLACEMENTMAP
	transformed += normalize( objectNormal ) * ( texture2D( displacementMap, vDisplacementMapUv ).x * displacementScale + displacementBias );
#endif`,Yo=`#ifdef USE_EMISSIVEMAP
	vec4 emissiveColor = texture2D( emissiveMap, vEmissiveMapUv );
	#ifdef DECODE_VIDEO_TEXTURE_EMISSIVE
		emissiveColor = sRGBTransferEOTF( emissiveColor );
	#endif
	totalEmissiveRadiance *= emissiveColor.rgb;
#endif`,Zo=`#ifdef USE_EMISSIVEMAP
	uniform sampler2D emissiveMap;
#endif`,$o="gl_FragColor = linearToOutputTexel( gl_FragColor );",Qo=`vec4 LinearTransferOETF( in vec4 value ) {
	return value;
}
vec4 sRGBTransferEOTF( in vec4 value ) {
	return vec4( mix( pow( value.rgb * 0.9478672986 + vec3( 0.0521327014 ), vec3( 2.4 ) ), value.rgb * 0.0773993808, vec3( lessThanEqual( value.rgb, vec3( 0.04045 ) ) ) ), value.a );
}
vec4 sRGBTransferOETF( in vec4 value ) {
	return vec4( mix( pow( value.rgb, vec3( 0.41666 ) ) * 1.055 - vec3( 0.055 ), value.rgb * 12.92, vec3( lessThanEqual( value.rgb, vec3( 0.0031308 ) ) ) ), value.a );
}`,Jo=`#ifdef USE_ENVMAP
	#ifdef ENV_WORLDPOS
		vec3 cameraToFrag;
		if ( isOrthographic ) {
			cameraToFrag = normalize( vec3( - viewMatrix[ 0 ][ 2 ], - viewMatrix[ 1 ][ 2 ], - viewMatrix[ 2 ][ 2 ] ) );
		} else {
			cameraToFrag = normalize( vWorldPosition - cameraPosition );
		}
		vec3 worldNormal = transformNormalByInverseViewMatrix( normal, viewMatrix );
		#ifdef ENVMAP_MODE_REFLECTION
			vec3 reflectVec = reflect( cameraToFrag, worldNormal );
		#else
			vec3 reflectVec = refract( cameraToFrag, worldNormal, refractionRatio );
		#endif
	#else
		vec3 reflectVec = vReflect;
	#endif
	#ifdef ENVMAP_TYPE_CUBE
		vec4 envColor = textureCube( envMap, envMapRotation * reflectVec );
		#ifdef ENVMAP_BLENDING_MULTIPLY
			outgoingLight = mix( outgoingLight, outgoingLight * envColor.xyz, specularStrength * reflectivity );
		#elif defined( ENVMAP_BLENDING_MIX )
			outgoingLight = mix( outgoingLight, envColor.xyz, specularStrength * reflectivity );
		#elif defined( ENVMAP_BLENDING_ADD )
			outgoingLight += envColor.xyz * specularStrength * reflectivity;
		#endif
	#endif
#endif`,jo=`#ifdef USE_ENVMAP
	uniform float envMapIntensity;
	uniform mat3 envMapRotation;
	uniform samplerCube envMap;
	#ifdef ENVMAP_TYPE_PMREM
		float roughnessToMip( const in float roughness ) {
			float r = clamp( roughness, 0.0, 1.0 );
			return ENVMAP_MAX_LOD * r * ( 2.0 - r );
		}
	#endif
#endif`,es=`#ifdef USE_ENVMAP
	uniform float reflectivity;
	#if defined( USE_BUMPMAP ) || defined( USE_NORMALMAP ) || defined( PHONG ) || defined( LAMBERT )
		#define ENV_WORLDPOS
	#endif
	#ifdef ENV_WORLDPOS
		varying vec3 vWorldPosition;
		uniform float refractionRatio;
	#else
		varying vec3 vReflect;
	#endif
#endif`,ts=`#ifdef USE_ENVMAP
	#if defined( USE_BUMPMAP ) || defined( USE_NORMALMAP ) || defined( PHONG ) || defined( LAMBERT )
		#define ENV_WORLDPOS
	#endif
	#ifdef ENV_WORLDPOS
		
		varying vec3 vWorldPosition;
	#else
		varying vec3 vReflect;
		uniform float refractionRatio;
	#endif
#endif`,ns=`#ifdef USE_ENVMAP
	#ifdef ENV_WORLDPOS
		vWorldPosition = worldPosition.xyz;
	#else
		vec3 cameraToVertex;
		if ( isOrthographic ) {
			cameraToVertex = normalize( vec3( - viewMatrix[ 0 ][ 2 ], - viewMatrix[ 1 ][ 2 ], - viewMatrix[ 2 ][ 2 ] ) );
		} else {
			cameraToVertex = normalize( worldPosition.xyz - cameraPosition );
		}
		vec3 worldNormal = transformNormalByInverseViewMatrix( transformedNormal, viewMatrix );
		#ifdef ENVMAP_MODE_REFLECTION
			vReflect = reflect( cameraToVertex, worldNormal );
		#else
			vReflect = refract( cameraToVertex, worldNormal, refractionRatio );
		#endif
	#endif
#endif`,is=`#ifdef USE_FOG
	vFogDepth = - mvPosition.z;
#endif`,rs=`#ifdef USE_FOG
	varying float vFogDepth;
#endif`,as=`#ifdef USE_FOG
	#ifdef FOG_EXP2
		float fogFactor = 1.0 - exp( - fogDensity * fogDensity * vFogDepth * vFogDepth );
	#else
		float fogFactor = smoothstep( fogNear, fogFar, vFogDepth );
	#endif
	gl_FragColor.rgb = mix( gl_FragColor.rgb, fogColor, fogFactor );
#endif`,os=`#ifdef USE_FOG
	uniform vec3 fogColor;
	varying float vFogDepth;
	#ifdef FOG_EXP2
		uniform float fogDensity;
	#else
		uniform float fogNear;
		uniform float fogFar;
	#endif
#endif`,ss=`#ifdef USE_GRADIENTMAP
	uniform sampler2D gradientMap;
#endif
vec3 getGradientIrradiance( vec3 normal, vec3 lightDirection ) {
	float dotNL = dot( normal, lightDirection );
	vec2 coord = vec2( dotNL * 0.5 + 0.5, 0.0 );
	#ifdef USE_GRADIENTMAP
		return vec3( texture2D( gradientMap, coord ).r );
	#else
		vec2 fw = fwidth( coord ) * 0.5;
		return mix( vec3( 0.7 ), vec3( 1.0 ), smoothstep( 0.7 - fw.x, 0.7 + fw.x, coord.x ) );
	#endif
}`,ls=`#ifdef USE_LIGHTMAP
	uniform sampler2D lightMap;
	uniform float lightMapIntensity;
#endif`,cs=`LambertMaterial material;
material.diffuseColor = diffuseColor.rgb;
material.specularStrength = specularStrength;`,fs=`varying vec3 vViewPosition;
struct LambertMaterial {
	vec3 diffuseColor;
	float specularStrength;
};
void RE_Direct_Lambert( const in IncidentLight directLight, const in vec3 geometryPosition, const in vec3 geometryNormal, const in vec3 geometryViewDir, const in vec3 geometryClearcoatNormal, const in LambertMaterial material, inout ReflectedLight reflectedLight ) {
	float dotNL = saturate( dot( geometryNormal, directLight.direction ) );
	vec3 irradiance = dotNL * directLight.color;
	reflectedLight.directDiffuse += irradiance * BRDF_Lambert( material.diffuseColor );
}
void RE_IndirectDiffuse_Lambert( const in vec3 irradiance, const in vec3 geometryPosition, const in vec3 geometryNormal, const in vec3 geometryViewDir, const in vec3 geometryClearcoatNormal, const in LambertMaterial material, inout ReflectedLight reflectedLight ) {
	reflectedLight.indirectDiffuse += irradiance * BRDF_Lambert( material.diffuseColor );
}
#define RE_Direct				RE_Direct_Lambert
#define RE_IndirectDiffuse		RE_IndirectDiffuse_Lambert`,ds=`uniform bool receiveShadow;
uniform vec3 ambientLightColor;
#if defined( USE_LIGHT_PROBES )
	uniform vec3 lightProbe[ 9 ];
#endif
vec3 shGetIrradianceAt( in vec3 normal, in vec3 shCoefficients[ 9 ] ) {
	float x = normal.x, y = normal.y, z = normal.z;
	vec3 result = shCoefficients[ 0 ] * 0.886227;
	result += shCoefficients[ 1 ] * 2.0 * 0.511664 * y;
	result += shCoefficients[ 2 ] * 2.0 * 0.511664 * z;
	result += shCoefficients[ 3 ] * 2.0 * 0.511664 * x;
	result += shCoefficients[ 4 ] * 2.0 * 0.429043 * x * y;
	result += shCoefficients[ 5 ] * 2.0 * 0.429043 * y * z;
	result += shCoefficients[ 6 ] * ( 0.743125 * z * z - 0.247708 );
	result += shCoefficients[ 7 ] * 2.0 * 0.429043 * x * z;
	result += shCoefficients[ 8 ] * 0.429043 * ( x * x - y * y );
	return result;
}
vec3 getLightProbeIrradiance( const in vec3 lightProbe[ 9 ], const in vec3 normal ) {
	vec3 worldNormal = transformNormalByInverseViewMatrix( normal, viewMatrix );
	vec3 irradiance = shGetIrradianceAt( worldNormal, lightProbe );
	return irradiance;
}
vec3 getAmbientLightIrradiance( const in vec3 ambientLightColor ) {
	vec3 irradiance = ambientLightColor;
	return irradiance;
}
float getDistanceAttenuation( const in float lightDistance, const in float cutoffDistance, const in float decayExponent ) {
	float distanceFalloff = 1.0 / max( pow( lightDistance, decayExponent ), 0.01 );
	if ( cutoffDistance > 0.0 ) {
		distanceFalloff *= pow2( saturate( 1.0 - pow4( lightDistance / cutoffDistance ) ) );
	}
	return distanceFalloff;
}
float getSpotAttenuation( const in float coneCosine, const in float penumbraCosine, const in float angleCosine ) {
	return smoothstep( coneCosine, penumbraCosine, angleCosine );
}
#if NUM_SUN_LIGHTS > 0
	struct SunLight {
		vec3 direction;
		vec3 color;
	};
	uniform SunLight sunLights[ NUM_SUN_LIGHTS ];
	void getSunLightInfo( const in SunLight sunLight, out IncidentLight light ) {
		light.color = sunLight.color;
		light.direction = sunLight.direction;
		light.visible = true;
	}
#endif
#if NUM_DIR_LIGHTS > 0
	struct DirectionalLight {
		vec3 direction;
		vec3 color;
	};
	uniform DirectionalLight directionalLights[ NUM_DIR_LIGHTS ];
	void getDirectionalLightInfo( const in DirectionalLight directionalLight, out IncidentLight light ) {
		light.color = directionalLight.color;
		light.direction = directionalLight.direction;
		light.visible = true;
	}
#endif
#if NUM_POINT_LIGHTS > 0
	struct PointLight {
		vec3 position;
		vec3 color;
		float distance;
		float decay;
	};
	uniform PointLight pointLights[ NUM_POINT_LIGHTS ];
	void getPointLightInfo( const in PointLight pointLight, const in vec3 geometryPosition, out IncidentLight light ) {
		vec3 lVector = pointLight.position - geometryPosition;
		light.direction = normalize( lVector );
		float lightDistance = length( lVector );
		light.color = pointLight.color;
		light.color *= getDistanceAttenuation( lightDistance, pointLight.distance, pointLight.decay );
		light.visible = ( light.color != vec3( 0.0 ) );
	}
#endif
#if NUM_SPOT_LIGHTS > 0
	struct SpotLight {
		vec3 position;
		vec3 direction;
		vec3 color;
		float distance;
		float decay;
		float coneCos;
		float penumbraCos;
	};
	uniform SpotLight spotLights[ NUM_SPOT_LIGHTS ];
	void getSpotLightInfo( const in SpotLight spotLight, const in vec3 geometryPosition, out IncidentLight light ) {
		vec3 lVector = spotLight.position - geometryPosition;
		light.direction = normalize( lVector );
		float angleCos = dot( light.direction, spotLight.direction );
		float spotAttenuation = getSpotAttenuation( spotLight.coneCos, spotLight.penumbraCos, angleCos );
		if ( spotAttenuation > 0.0 ) {
			float lightDistance = length( lVector );
			light.color = spotLight.color * spotAttenuation;
			light.color *= getDistanceAttenuation( lightDistance, spotLight.distance, spotLight.decay );
			light.visible = ( light.color != vec3( 0.0 ) );
		} else {
			light.color = vec3( 0.0 );
			light.visible = false;
		}
	}
#endif
#if NUM_RECT_AREA_LIGHTS > 0
	struct RectAreaLight {
		vec3 color;
		vec3 position;
		vec3 halfWidth;
		vec3 halfHeight;
	};
	uniform sampler2D ltc_1;	uniform sampler2D ltc_2;
	uniform RectAreaLight rectAreaLights[ NUM_RECT_AREA_LIGHTS ];
#endif
#if NUM_HEMI_LIGHTS > 0
	struct HemisphereLight {
		vec3 direction;
		vec3 skyColor;
		vec3 groundColor;
	};
	uniform HemisphereLight hemisphereLights[ NUM_HEMI_LIGHTS ];
	vec3 getHemisphereLightIrradiance( const in HemisphereLight hemiLight, const in vec3 normal ) {
		float dotNL = dot( normal, hemiLight.direction );
		float hemiDiffuseWeight = 0.5 * dotNL + 0.5;
		vec3 irradiance = mix( hemiLight.groundColor, hemiLight.skyColor, hemiDiffuseWeight );
		return irradiance;
	}
#endif
#include <lightprobes_pars_fragment>`,us=`#ifdef USE_ENVMAP
	vec3 getIBLIrradiance( const in vec3 normal ) {
		#ifdef ENVMAP_TYPE_PMREM
			vec3 worldNormal = transformNormalByInverseViewMatrix( normal, viewMatrix );
			vec4 envMapColor = textureLod( envMap, envMapRotation * worldNormal, ENVMAP_MAX_LOD );
			return PI * envMapColor.rgb * envMapIntensity;
		#else
			return vec3( 0.0 );
		#endif
	}
	vec3 getIBLRadiance( const in vec3 viewDir, const in vec3 normal, const in float roughness ) {
		#ifdef ENVMAP_TYPE_PMREM
			vec3 reflectVec = reflect( - viewDir, normal );
			reflectVec = normalize( mix( reflectVec, normal, pow4( roughness ) ) );
			reflectVec = transformDirectionByInverseViewMatrix( reflectVec, viewMatrix );
			vec4 envMapColor = textureLod( envMap, envMapRotation * reflectVec, roughnessToMip( roughness ) );
			return envMapColor.rgb * envMapIntensity;
		#else
			return vec3( 0.0 );
		#endif
	}
	#ifdef USE_RETROREFLECTION
		vec3 getIBLRetroRadiance( const in vec3 viewDir, const in vec3 normal, const in float roughness ) {
			#ifdef ENVMAP_TYPE_PMREM
				vec3 retroVec = normalize( mix( viewDir, normal, pow4( roughness ) ) );
				retroVec = transformDirectionByInverseViewMatrix( retroVec, viewMatrix );
				vec4 envMapColor = textureLod( envMap, envMapRotation * retroVec, roughnessToMip( roughness ) );
				return envMapColor.rgb * envMapIntensity;
			#else
				return vec3( 0.0 );
			#endif
		}
	#endif
	#ifdef USE_ANISOTROPY
		vec3 getIBLAnisotropyRadiance( const in vec3 viewDir, const in vec3 normal, const in float roughness, const in vec3 bitangent, const in float anisotropy ) {
			#ifdef ENVMAP_TYPE_PMREM
				vec3 bentNormal = cross( bitangent, viewDir );
				bentNormal = normalize( cross( bentNormal, bitangent ) );
				bentNormal = normalize( mix( bentNormal, normal, pow2( pow2( 1.0 - anisotropy * ( 1.0 - roughness ) ) ) ) );
				return getIBLRadiance( viewDir, bentNormal, roughness );
			#else
				return vec3( 0.0 );
			#endif
		}
		#ifdef USE_RETROREFLECTION
			vec3 getIBLAnisotropyRetroRadiance( const in vec3 viewDir, const in vec3 normal, const in float roughness, const in vec3 bitangent, const in float anisotropy ) {
				#ifdef ENVMAP_TYPE_PMREM
					vec3 bentNormal = cross( bitangent, viewDir );
					bentNormal = normalize( cross( bentNormal, bitangent ) );
					bentNormal = normalize( mix( bentNormal, normal, pow2( pow2( 1.0 - anisotropy * ( 1.0 - roughness ) ) ) ) );
					return getIBLRetroRadiance( viewDir, bentNormal, roughness );
				#else
					return vec3( 0.0 );
				#endif
			}
		#endif
	#endif
#endif`,ps=`ToonMaterial material;
material.diffuseColor = diffuseColor.rgb;`,hs=`varying vec3 vViewPosition;
struct ToonMaterial {
	vec3 diffuseColor;
};
void RE_Direct_Toon( const in IncidentLight directLight, const in vec3 geometryPosition, const in vec3 geometryNormal, const in vec3 geometryViewDir, const in vec3 geometryClearcoatNormal, const in ToonMaterial material, inout ReflectedLight reflectedLight ) {
	vec3 irradiance = getGradientIrradiance( geometryNormal, directLight.direction ) * directLight.color;
	reflectedLight.directDiffuse += irradiance * BRDF_Lambert( material.diffuseColor );
}
void RE_IndirectDiffuse_Toon( const in vec3 irradiance, const in vec3 geometryPosition, const in vec3 geometryNormal, const in vec3 geometryViewDir, const in vec3 geometryClearcoatNormal, const in ToonMaterial material, inout ReflectedLight reflectedLight ) {
	reflectedLight.indirectDiffuse += irradiance * BRDF_Lambert( material.diffuseColor );
}
#define RE_Direct				RE_Direct_Toon
#define RE_IndirectDiffuse		RE_IndirectDiffuse_Toon`,ms=`BlinnPhongMaterial material;
material.diffuseColor = diffuseColor.rgb;
material.specularColor = specular;
material.specularShininess = shininess;
material.specularStrength = specularStrength;`,_s=`varying vec3 vViewPosition;
struct BlinnPhongMaterial {
	vec3 diffuseColor;
	vec3 specularColor;
	float specularShininess;
	float specularStrength;
};
void RE_Direct_BlinnPhong( const in IncidentLight directLight, const in vec3 geometryPosition, const in vec3 geometryNormal, const in vec3 geometryViewDir, const in vec3 geometryClearcoatNormal, const in BlinnPhongMaterial material, inout ReflectedLight reflectedLight ) {
	float dotNL = saturate( dot( geometryNormal, directLight.direction ) );
	vec3 irradiance = dotNL * directLight.color;
	reflectedLight.directDiffuse += irradiance * BRDF_Lambert( material.diffuseColor );
	reflectedLight.directSpecular += irradiance * BRDF_BlinnPhong( directLight.direction, geometryViewDir, geometryNormal, material.specularColor, material.specularShininess ) * material.specularStrength;
}
void RE_IndirectDiffuse_BlinnPhong( const in vec3 irradiance, const in vec3 geometryPosition, const in vec3 geometryNormal, const in vec3 geometryViewDir, const in vec3 geometryClearcoatNormal, const in BlinnPhongMaterial material, inout ReflectedLight reflectedLight ) {
	reflectedLight.indirectDiffuse += irradiance * BRDF_Lambert( material.diffuseColor );
}
#define RE_Direct				RE_Direct_BlinnPhong
#define RE_IndirectDiffuse		RE_IndirectDiffuse_BlinnPhong`,gs=`PhysicalMaterial material;
material.diffuseColor = diffuseColor.rgb;
material.diffuseContribution = diffuseColor.rgb * ( 1.0 - metalnessFactor );
material.metalness = metalnessFactor;
vec3 dxy = max( abs( dFdx( nonPerturbedNormal ) ), abs( dFdy( nonPerturbedNormal ) ) );
float geometryRoughness = max( max( dxy.x, dxy.y ), dxy.z );
material.roughness = max( roughnessFactor, 0.045 );
material.roughness += geometryRoughness;
material.roughness = min( material.roughness, 1.0 );
#ifdef USE_DIFFUSE_ROUGHNESS
	float diffuseRoughnessFactor = diffuseRoughness;
	#ifdef USE_DIFFUSE_ROUGHNESSMAP
		diffuseRoughnessFactor *= texture2D( diffuseRoughnessMap, vDiffuseRoughnessMapUv ).r;
	#endif
	material.diffuseRoughness = saturate( diffuseRoughnessFactor );
#endif
#ifdef IOR
	material.ior = ior;
	#ifdef USE_SPECULAR
		float specularIntensityFactor = specularIntensity;
		vec3 specularColorFactor = specularColor;
		#ifdef USE_SPECULAR_COLORMAP
			specularColorFactor *= texture2D( specularColorMap, vSpecularColorMapUv ).rgb;
		#endif
		#ifdef USE_SPECULAR_INTENSITYMAP
			specularIntensityFactor *= texture2D( specularIntensityMap, vSpecularIntensityMapUv ).a;
		#endif
		material.specularF90 = mix( specularIntensityFactor, 1.0, metalnessFactor );
	#else
		float specularIntensityFactor = 1.0;
		vec3 specularColorFactor = vec3( 1.0 );
		material.specularF90 = 1.0;
	#endif
	material.specularColor = min( pow2( ( material.ior - 1.0 ) / ( material.ior + 1.0 ) ) * specularColorFactor, vec3( 1.0 ) ) * specularIntensityFactor;
	material.specularColorBlended = mix( material.specularColor, diffuseColor.rgb, metalnessFactor );
#else
	material.specularColor = vec3( 0.04 );
	material.specularColorBlended = mix( material.specularColor, diffuseColor.rgb, metalnessFactor );
	material.specularF90 = 1.0;
#endif
#ifdef USE_CLEARCOAT
	material.clearcoat = clearcoat;
	material.clearcoatRoughness = clearcoatRoughness;
	material.clearcoatF0 = vec3( 0.04 );
	material.clearcoatF90 = 1.0;
	#ifdef USE_CLEARCOATMAP
		material.clearcoat *= texture2D( clearcoatMap, vClearcoatMapUv ).x;
	#endif
	#ifdef USE_CLEARCOAT_ROUGHNESSMAP
		material.clearcoatRoughness *= texture2D( clearcoatRoughnessMap, vClearcoatRoughnessMapUv ).y;
	#endif
	material.clearcoat = saturate( material.clearcoat );	material.clearcoatRoughness = max( material.clearcoatRoughness, 0.045 );
	material.clearcoatRoughness += geometryRoughness;
	material.clearcoatRoughness = min( material.clearcoatRoughness, 1.0 );
#endif
#ifdef USE_DISPERSION
	material.dispersion = dispersion;
#endif
#ifdef USE_RETROREFLECTION
	material.retroreflectivity = retroreflectivity;
#endif
#ifdef USE_IRIDESCENCE
	material.iridescence = iridescence;
	material.iridescenceIOR = iridescenceIOR;
	#ifdef USE_IRIDESCENCEMAP
		material.iridescence *= texture2D( iridescenceMap, vIridescenceMapUv ).r;
	#endif
	#ifdef USE_IRIDESCENCE_THICKNESSMAP
		material.iridescenceThickness = (iridescenceThicknessMaximum - iridescenceThicknessMinimum) * texture2D( iridescenceThicknessMap, vIridescenceThicknessMapUv ).g + iridescenceThicknessMinimum;
	#else
		material.iridescenceThickness = iridescenceThicknessMaximum;
	#endif
#endif
#ifdef USE_SHEEN
	material.sheenColor = sheenColor;
	#ifdef USE_SHEEN_COLORMAP
		material.sheenColor *= texture2D( sheenColorMap, vSheenColorMapUv ).rgb;
	#endif
	material.sheenRoughness = clamp( sheenRoughness, 0.0001, 1.0 );
	#ifdef USE_SHEEN_ROUGHNESSMAP
		material.sheenRoughness *= texture2D( sheenRoughnessMap, vSheenRoughnessMapUv ).a;
	#endif
#endif
#ifdef USE_ANISOTROPY
	#ifdef USE_ANISOTROPYMAP
		mat2 anisotropyMat = mat2( anisotropyVector.x, anisotropyVector.y, - anisotropyVector.y, anisotropyVector.x );
		vec3 anisotropyPolar = texture2D( anisotropyMap, vAnisotropyMapUv ).rgb;
		vec2 anisotropyV = anisotropyMat * normalize( 2.0 * anisotropyPolar.rg - vec2( 1.0 ) ) * anisotropyPolar.b;
	#else
		vec2 anisotropyV = anisotropyVector;
	#endif
	material.anisotropy = length( anisotropyV );
	if( material.anisotropy == 0.0 ) {
		anisotropyV = vec2( 1.0, 0.0 );
	} else {
		anisotropyV /= material.anisotropy;
		material.anisotropy = saturate( material.anisotropy );
	}
	material.alphaT = mix( pow2( material.roughness ), 1.0, pow2( material.anisotropy ) );
	material.anisotropyT = tbn[ 0 ] * anisotropyV.x + tbn[ 1 ] * anisotropyV.y;
	material.anisotropyB = tbn[ 1 ] * anisotropyV.x - tbn[ 0 ] * anisotropyV.y;
#endif`,vs=`uniform sampler2D dfgLUT;
struct PhysicalMaterial {
	vec3 diffuseColor;
	vec3 diffuseContribution;
	vec3 specularColor;
	vec3 specularColorBlended;
	float roughness;
	float metalness;
	float specularF90;
	float dispersion;
	vec2 dfg;
	vec3 multiScatteringCompensation;
	#ifdef USE_DIFFUSE_ROUGHNESS
		float diffuseRoughness;
	#endif
	#ifdef USE_RETROREFLECTION
		float retroreflectivity;
	#endif
	#ifdef USE_CLEARCOAT
		float clearcoat;
		float clearcoatRoughness;
		vec3 clearcoatF0;
		float clearcoatF90;
	#endif
	#ifdef USE_IRIDESCENCE
		float iridescence;
		float iridescenceIOR;
		float iridescenceThickness;
		vec3 iridescenceFresnel;
		vec3 iridescenceF0Dielectric;
		vec3 iridescenceF0Metallic;
	#endif
	#ifdef USE_SHEEN
		vec3 sheenColor;
		float sheenRoughness;
	#endif
	#ifdef IOR
		float ior;
	#endif
	#ifdef USE_TRANSMISSION
		float transmission;
		float transmissionAlpha;
		float thickness;
		float attenuationDistance;
		vec3 attenuationColor;
	#endif
	#ifdef USE_ANISOTROPY
		float anisotropy;
		float alphaT;
		vec3 anisotropyT;
		vec3 anisotropyB;
	#endif
};
vec3 clearcoatSpecularDirect = vec3( 0.0 );
vec3 clearcoatSpecularIndirect = vec3( 0.0 );
vec3 sheenSpecularDirect = vec3( 0.0 );
vec3 sheenSpecularIndirect = vec3(0.0 );
vec3 Schlick_to_F0( const in vec3 f, const in float f90, const in float dotVH ) {
    float x = clamp( 1.0 - dotVH, 0.0, 1.0 );
    float x2 = x * x;
    float x5 = clamp( x * x2 * x2, 0.0, 0.9999 );
    return ( f - vec3( f90 ) * x5 ) / ( 1.0 - x5 );
}
#ifdef USE_DIFFUSE_ROUGHNESS
	const float EON_EPSILON = 1e-7;
	float FON_DirectionalAlbedo( const in float mu, const in float roughness, const in float A ) {
		float muComp = 1.0 - mu;
		float gOverPi = muComp * ( 0.0571085289 + muComp * ( 0.491881867 + muComp * ( - 0.332181442 + muComp * 0.0714429953 ) ) );
		return A * ( 1.0 + roughness * gOverPi );
	}
	vec3 BRDF_EON( const in vec3 lightDir, const in vec3 viewDir, const in vec3 normal, const in vec3 diffuseColor, const in float roughness ) {
		vec3 rho = saturate( diffuseColor );
		if ( roughness <= EON_EPSILON ) return BRDF_Lambert( rho );
		float dotNL = saturate( dot( normal, lightDir ) );
		float dotNV = saturate( dot( normal, viewDir ) );
		float s = dot( lightDir, viewDir ) - dotNL * dotNV;
		float sOverT = ( s > 0.0 ) ? s / max( max( dotNL, dotNV ), EON_EPSILON ) : s;
		float A = 1.0 / ( 1.0 + ( 0.5 - 2.0 / ( 3.0 * PI ) ) * roughness );
		vec3 singleScatter = rho * RECIPROCAL_PI * A * ( 1.0 + roughness * sOverT );
		float averageAlbedo = A * ( 1.0 + ( 2.0 / 3.0 - 28.0 / ( 15.0 * PI ) ) * roughness );
		float albedoV = FON_DirectionalAlbedo( dotNV, roughness, A );
		float albedoL = FON_DirectionalAlbedo( dotNL, roughness, A );
		vec3 rhoMultiScatter = rho * rho * averageAlbedo / max( vec3( EON_EPSILON ), vec3( 1.0 ) - rho * ( 1.0 - averageAlbedo ) );
		vec3 multiScatter = rhoMultiScatter * RECIPROCAL_PI
			* max( EON_EPSILON, 1.0 - albedoV )
			* max( EON_EPSILON, 1.0 - albedoL )
			/ max( EON_EPSILON, 1.0 - averageAlbedo );
		return singleScatter + multiScatter;
	}
	vec3 EON_DirectionalAlbedo( const in vec3 diffuseColor, const in float roughness, const in float dotNV ) {
		vec3 rho = saturate( diffuseColor );
		if ( roughness <= EON_EPSILON ) return rho;
		float A = 1.0 / ( 1.0 + ( 0.5 - 2.0 / ( 3.0 * PI ) ) * roughness );
		float directionalAlbedo = FON_DirectionalAlbedo( dotNV, roughness, A );
		float averageAlbedo = A * ( 1.0 + ( 2.0 / 3.0 - 28.0 / ( 15.0 * PI ) ) * roughness );
		vec3 rhoMultiScatter = rho * rho * averageAlbedo / max( vec3( EON_EPSILON ), vec3( 1.0 ) - rho * ( 1.0 - averageAlbedo ) );
		return rho * directionalAlbedo + rhoMultiScatter * ( 1.0 - directionalAlbedo );
	}
#endif
float V_GGX_SmithCorrelated( const in float alpha, const in float dotNL, const in float dotNV ) {
	float a2 = pow2( alpha );
	float gv = dotNL * sqrt( a2 + ( 1.0 - a2 ) * pow2( dotNV ) );
	float gl = dotNV * sqrt( a2 + ( 1.0 - a2 ) * pow2( dotNL ) );
	return 0.5 / max( gv + gl, EPSILON );
}
float D_GGX( const in float alpha, const in float dotNH ) {
	float a2 = pow2( alpha );
	float denom = pow2( dotNH ) * ( a2 - 1.0 ) + 1.0;
	return RECIPROCAL_PI * a2 / pow2( denom );
}
#ifdef USE_ANISOTROPY
	float V_GGX_SmithCorrelated_Anisotropic( const in float alphaT, const in float alphaB, const in float dotTV, const in float dotBV, const in float dotTL, const in float dotBL, const in float dotNV, const in float dotNL ) {
		float gv = dotNL * length( vec3( alphaT * dotTV, alphaB * dotBV, dotNV ) );
		float gl = dotNV * length( vec3( alphaT * dotTL, alphaB * dotBL, dotNL ) );
		return 0.5 / max( gv + gl, EPSILON );
	}
	float D_GGX_Anisotropic( const in float alphaT, const in float alphaB, const in float dotNH, const in float dotTH, const in float dotBH ) {
		float a2 = alphaT * alphaB;
		highp vec3 v = vec3( alphaB * dotTH, alphaT * dotBH, a2 * dotNH );
		highp float v2 = dot( v, v );
		float w2 = a2 / v2;
		return RECIPROCAL_PI * a2 * pow2 ( w2 );
	}
#endif
#ifdef USE_CLEARCOAT
	vec3 BRDF_GGX_Clearcoat( const in vec3 lightDir, const in vec3 viewDir, const in vec3 normal, const in PhysicalMaterial material) {
		vec3 f0 = material.clearcoatF0;
		float f90 = material.clearcoatF90;
		float roughness = max( material.clearcoatRoughness, 0.045 );
		float alpha = pow2( roughness );
		vec3 halfDir = normalize( lightDir + viewDir );
		float dotNL = saturate( dot( normal, lightDir ) );
		float dotNV = saturate( dot( normal, viewDir ) );
		float dotNH = saturate( dot( normal, halfDir ) );
		float dotVH = saturate( dot( viewDir, halfDir ) );
		vec3 F = F_Schlick( f0, f90, dotVH );
		float V = V_GGX_SmithCorrelated( alpha, dotNL, dotNV );
		float D = D_GGX( alpha, dotNH );
		return F * ( V * D );
	}
#endif
vec3 BRDF_GGX( const in vec3 lightDir, const in vec3 viewDir, const in vec3 normal, const in PhysicalMaterial material ) {
	vec3 f0 = material.specularColorBlended;
	float f90 = material.specularF90;
	float roughness = max( material.roughness, 0.045 );
	float alpha = pow2( roughness );
	vec3 halfDir = normalize( lightDir + viewDir );
	float dotNL = saturate( dot( normal, lightDir ) );
	float dotNV = saturate( dot( normal, viewDir ) );
	float dotNH = saturate( dot( normal, halfDir ) );
	float dotVH = saturate( dot( viewDir, halfDir ) );
	vec3 F = F_Schlick( f0, f90, dotVH );
	#ifdef USE_IRIDESCENCE
		F = mix( F, material.iridescenceFresnel, material.iridescence );
	#endif
	#ifdef USE_ANISOTROPY
		float dotTL = dot( material.anisotropyT, lightDir );
		float dotTV = dot( material.anisotropyT, viewDir );
		float dotTH = dot( material.anisotropyT, halfDir );
		float dotBL = dot( material.anisotropyB, lightDir );
		float dotBV = dot( material.anisotropyB, viewDir );
		float dotBH = dot( material.anisotropyB, halfDir );
		float alphaT = max( material.alphaT, alpha );
		float V = V_GGX_SmithCorrelated_Anisotropic( alphaT, alpha, dotTV, dotBV, dotTL, dotBL, dotNV, dotNL );
		float D = D_GGX_Anisotropic( alphaT, alpha, dotNH, dotTH, dotBH );
	#else
		float V = V_GGX_SmithCorrelated( alpha, dotNL, dotNV );
		float D = D_GGX( alpha, dotNH );
	#endif
	return F * ( V * D );
}
vec2 LTC_Uv( const in vec3 N, const in vec3 V, const in float roughness ) {
	const float LUT_SIZE = 64.0;
	const float LUT_SCALE = ( LUT_SIZE - 1.0 ) / LUT_SIZE;
	const float LUT_BIAS = 0.5 / LUT_SIZE;
	float dotNV = saturate( dot( N, V ) );
	vec2 uv = vec2( roughness, sqrt( 1.0 - dotNV ) );
	uv = uv * LUT_SCALE + LUT_BIAS;
	return uv;
}
float LTC_ClippedSphereFormFactor( const in vec3 f ) {
	float l = length( f );
	return max( ( l * l + f.z ) / ( l + 1.0 ), 0.0 );
}
vec3 LTC_EdgeVectorFormFactor( const in vec3 v1, const in vec3 v2 ) {
	float x = dot( v1, v2 );
	float y = abs( x );
	float a = 0.8543985 + ( 0.4965155 + 0.0145206 * y ) * y;
	float b = 3.4175940 + ( 4.1616724 + y ) * y;
	float v = a / b;
	float theta_sintheta = ( x > 0.0 ) ? v : 0.5 * inversesqrt( max( 1.0 - x * x, 1e-7 ) ) - v;
	return cross( v1, v2 ) * theta_sintheta;
}
vec3 LTC_Evaluate( const in vec3 N, const in vec3 V, const in vec3 P, const in mat3 mInv, const in vec3 rectCoords[ 4 ] ) {
	vec3 v1 = rectCoords[ 1 ] - rectCoords[ 0 ];
	vec3 v2 = rectCoords[ 3 ] - rectCoords[ 0 ];
	vec3 lightNormal = cross( v1, v2 );
	if( dot( lightNormal, P - rectCoords[ 0 ] ) < 0.0 ) return vec3( 0.0 );
	vec3 T1, T2;
	T1 = normalize( V - N * dot( V, N ) );
	T2 = - cross( N, T1 );
	mat3 mat = mInv * transpose( mat3( T1, T2, N ) );
	vec3 coords[ 4 ];
	coords[ 0 ] = mat * ( rectCoords[ 0 ] - P );
	coords[ 1 ] = mat * ( rectCoords[ 1 ] - P );
	coords[ 2 ] = mat * ( rectCoords[ 2 ] - P );
	coords[ 3 ] = mat * ( rectCoords[ 3 ] - P );
	coords[ 0 ] = normalize( coords[ 0 ] );
	coords[ 1 ] = normalize( coords[ 1 ] );
	coords[ 2 ] = normalize( coords[ 2 ] );
	coords[ 3 ] = normalize( coords[ 3 ] );
	vec3 vectorFormFactor = vec3( 0.0 );
	vectorFormFactor += LTC_EdgeVectorFormFactor( coords[ 0 ], coords[ 1 ] );
	vectorFormFactor += LTC_EdgeVectorFormFactor( coords[ 1 ], coords[ 2 ] );
	vectorFormFactor += LTC_EdgeVectorFormFactor( coords[ 2 ], coords[ 3 ] );
	vectorFormFactor += LTC_EdgeVectorFormFactor( coords[ 3 ], coords[ 0 ] );
	float result = LTC_ClippedSphereFormFactor( vectorFormFactor );
	return vec3( result );
}
#if defined( USE_SHEEN )
float D_Charlie( float roughness, float dotNH ) {
	float alpha = pow2( roughness );
	float invAlpha = 1.0 / alpha;
	float cos2h = dotNH * dotNH;
	float sin2h = max( 1.0 - cos2h, 0.0078125 );
	return ( 2.0 + invAlpha ) * pow( sin2h, invAlpha * 0.5 ) / ( 2.0 * PI );
}
float V_Neubelt( float dotNV, float dotNL ) {
	return saturate( 1.0 / ( 4.0 * ( dotNL + dotNV - dotNL * dotNV ) ) );
}
vec3 BRDF_Sheen( const in vec3 lightDir, const in vec3 viewDir, const in vec3 normal, vec3 sheenColor, const in float sheenRoughness ) {
	vec3 halfDir = normalize( lightDir + viewDir );
	float dotNL = saturate( dot( normal, lightDir ) );
	float dotNV = saturate( dot( normal, viewDir ) );
	float dotNH = saturate( dot( normal, halfDir ) );
	float D = D_Charlie( sheenRoughness, dotNH );
	float V = V_Neubelt( dotNV, dotNL );
	return sheenColor * ( D * V );
}
#endif
float IBLSheenBRDF( const in vec3 normal, const in vec3 viewDir, const in float roughness ) {
	float dotNV = saturate( dot( normal, viewDir ) );
	float r2 = roughness * roughness;
	float rInv = 1.0 / ( roughness + 0.1 );
	float a = -1.9362 + 1.0678 * roughness + 0.4573 * r2 - 0.8469 * rInv;
	float b = -0.6014 + 0.5538 * roughness - 0.4670 * r2 - 0.1255 * rInv;
	float DG = exp( a * dotNV + b );
	return saturate( DG );
}
vec3 EnvironmentBRDF( const in vec3 normal, const in vec3 viewDir, const in vec3 specularColor, const in float specularF90, const in float roughness ) {
	float dotNV = saturate( dot( normal, viewDir ) );
	vec2 fab = texture2D( dfgLUT, vec2( roughness, dotNV ) ).rg;
	return specularColor * fab.x + specularF90 * fab.y;
}
#ifdef USE_IRIDESCENCE
void computeMultiscatteringIridescence( const in vec2 fab, const in vec3 specularColor, const in float specularF90, const in float iridescence, const in vec3 iridescenceF0, inout vec3 singleScatter, inout vec3 multiScatter ) {
#else
void computeMultiscattering( const in vec2 fab, const in vec3 specularColor, const in float specularF90, inout vec3 singleScatter, inout vec3 multiScatter ) {
#endif
	#ifdef USE_IRIDESCENCE
		vec3 Fr = mix( specularColor, iridescenceF0, iridescence );
	#else
		vec3 Fr = specularColor;
	#endif
	vec3 FssEss = Fr * fab.x + specularF90 * fab.y;
	float Ess = fab.x + fab.y;
	float Ems = 1.0 - Ess;
	vec3 Favg = Fr + ( 1.0 - Fr ) * 0.047619;	vec3 Fms = FssEss * Favg / ( 1.0 - Ems * Favg );
	singleScatter += FssEss;
	multiScatter += Fms * Ems;
}
#if NUM_RECT_AREA_LIGHTS > 0
	void RE_Direct_RectArea_Physical( const in RectAreaLight rectAreaLight, const in vec3 geometryPosition, const in vec3 geometryNormal, const in vec3 geometryViewDir, const in vec3 geometryClearcoatNormal, const in PhysicalMaterial material, inout ReflectedLight reflectedLight ) {
		vec3 normal = geometryNormal;
		vec3 viewDir = geometryViewDir;
		vec3 position = geometryPosition;
		vec3 lightPos = rectAreaLight.position;
		vec3 halfWidth = rectAreaLight.halfWidth;
		vec3 halfHeight = rectAreaLight.halfHeight;
		vec3 lightColor = rectAreaLight.color;
		float roughness = material.roughness;
		vec3 rectCoords[ 4 ];
		rectCoords[ 0 ] = lightPos + halfWidth - halfHeight;		rectCoords[ 1 ] = lightPos - halfWidth - halfHeight;
		rectCoords[ 2 ] = lightPos - halfWidth + halfHeight;
		rectCoords[ 3 ] = lightPos + halfWidth + halfHeight;
		vec2 uv = LTC_Uv( normal, viewDir, roughness );
		vec4 t1 = texture2D( ltc_1, uv );
		vec4 t2 = texture2D( ltc_2, uv );
		mat3 mInv = mat3(
			vec3( t1.x, 0, t1.y ),
			vec3(    0, 1,    0 ),
			vec3( t1.z, 0, t1.w )
		);
		vec3 fresnel = ( material.specularColorBlended * t2.x + ( material.specularF90 - material.specularColorBlended ) * t2.y );
		reflectedLight.directSpecular += lightColor * fresnel * LTC_Evaluate( normal, viewDir, position, mInv, rectCoords );
		reflectedLight.directDiffuse += lightColor * material.diffuseContribution * LTC_Evaluate( normal, viewDir, position, mat3( 1.0 ), rectCoords );
		#ifdef USE_CLEARCOAT
			vec3 Ncc = geometryClearcoatNormal;
			vec2 uvClearcoat = LTC_Uv( Ncc, viewDir, material.clearcoatRoughness );
			vec4 t1Clearcoat = texture2D( ltc_1, uvClearcoat );
			vec4 t2Clearcoat = texture2D( ltc_2, uvClearcoat );
			mat3 mInvClearcoat = mat3(
				vec3( t1Clearcoat.x, 0, t1Clearcoat.y ),
				vec3(             0, 1,             0 ),
				vec3( t1Clearcoat.z, 0, t1Clearcoat.w )
			);
			vec3 fresnelClearcoat = material.clearcoatF0 * t2Clearcoat.x + ( material.clearcoatF90 - material.clearcoatF0 ) * t2Clearcoat.y;
			clearcoatSpecularDirect += lightColor * fresnelClearcoat * LTC_Evaluate( Ncc, viewDir, position, mInvClearcoat, rectCoords );
		#endif
	}
#endif
void RE_Direct_Physical( const in IncidentLight directLight, const in vec3 geometryPosition, const in vec3 geometryNormal, const in vec3 geometryViewDir, const in vec3 geometryClearcoatNormal, const in PhysicalMaterial material, inout ReflectedLight reflectedLight ) {
	float dotNL = saturate( dot( geometryNormal, directLight.direction ) );
	vec3 irradiance = dotNL * directLight.color;
	#ifdef USE_CLEARCOAT
		float dotNLcc = saturate( dot( geometryClearcoatNormal, directLight.direction ) );
		vec3 ccIrradiance = dotNLcc * directLight.color;
		clearcoatSpecularDirect += ccIrradiance * BRDF_GGX_Clearcoat( directLight.direction, geometryViewDir, geometryClearcoatNormal, material );
	#endif
	#ifdef USE_SHEEN
 
 		sheenSpecularDirect += irradiance * BRDF_Sheen( directLight.direction, geometryViewDir, geometryNormal, material.sheenColor, material.sheenRoughness );
 
 		float sheenAlbedoV = IBLSheenBRDF( geometryNormal, geometryViewDir, material.sheenRoughness );
 		float sheenAlbedoL = IBLSheenBRDF( geometryNormal, directLight.direction, material.sheenRoughness );
 
 		float sheenEnergyComp = 1.0 - max3( material.sheenColor ) * max( sheenAlbedoV, sheenAlbedoL );
 
 		irradiance *= sheenEnergyComp;
 
 	#endif
	vec3 specularBRDF = BRDF_GGX( directLight.direction, geometryViewDir, geometryNormal, material );
	#ifdef USE_RETROREFLECTION
		vec3 retroViewDir = reflect( - geometryViewDir, geometryNormal );
		vec3 retroSpecularBRDF = BRDF_GGX( directLight.direction, retroViewDir, geometryNormal, material );
		specularBRDF = mix( specularBRDF, retroSpecularBRDF, saturate( material.retroreflectivity ) );
	#endif
	reflectedLight.directSpecular += irradiance * specularBRDF * material.multiScatteringCompensation;
	vec3 halfDir = normalize( directLight.direction + geometryViewDir );
	float dotVH = saturate( dot( geometryViewDir, halfDir ) );
	vec3 F = F_Schlick( material.specularColor, material.specularF90, dotVH );
	#ifdef USE_RETROREFLECTION
		vec3 retroHalfDir = normalize( directLight.direction + retroViewDir );
		float dotRetroVH = saturate( dot( retroViewDir, retroHalfDir ) );
		vec3 retroF = F_Schlick( material.specularColor, material.specularF90, dotRetroVH );
		F = mix( F, retroF, saturate( material.retroreflectivity ) );
	#endif
	#ifdef USE_DIFFUSE_ROUGHNESS
		vec3 diffuseBRDF = BRDF_EON( directLight.direction, geometryViewDir, geometryNormal, material.diffuseColor, material.diffuseRoughness ) * ( 1.0 - material.metalness );
	#else
		vec3 diffuseBRDF = BRDF_Lambert( material.diffuseContribution );
	#endif
	reflectedLight.directDiffuse += irradiance * diffuseBRDF * ( 1.0 - F );
}
void RE_IndirectDiffuse_Physical( const in vec3 irradiance, const in vec3 geometryPosition, const in vec3 geometryNormal, const in vec3 geometryViewDir, const in vec3 geometryClearcoatNormal, const in PhysicalMaterial material, inout ReflectedLight reflectedLight ) {
	vec3 singleScattering = vec3( 0.0 );
	vec3 multiScattering = vec3( 0.0 );
	#ifdef USE_IRIDESCENCE
		computeMultiscatteringIridescence( material.dfg, material.specularColor, material.specularF90, material.iridescence, material.iridescenceF0Dielectric, singleScattering, multiScattering );
	#else
		computeMultiscattering( material.dfg, material.specularColor, material.specularF90, singleScattering, multiScattering );
	#endif
	#ifdef USE_DIFFUSE_ROUGHNESS
		float dotNV = saturate( dot( geometryNormal, geometryViewDir ) );
		vec3 diffuseAlbedo = EON_DirectionalAlbedo( material.diffuseColor, material.diffuseRoughness, dotNV ) * ( 1.0 - material.metalness );
		vec3 diffuse = irradiance * RECIPROCAL_PI * diffuseAlbedo * ( 1.0 - singleScattering - multiScattering );
	#else
		vec3 diffuse = irradiance * BRDF_Lambert( material.diffuseContribution ) * ( 1.0 - singleScattering - multiScattering );
	#endif
	#ifdef USE_SHEEN
		float sheenAlbedo = IBLSheenBRDF( geometryNormal, geometryViewDir, material.sheenRoughness );
		sheenSpecularIndirect += irradiance * material.sheenColor * sheenAlbedo * RECIPROCAL_PI;
		float sheenEnergyComp = 1.0 - max3( material.sheenColor ) * sheenAlbedo;
		diffuse *= sheenEnergyComp;
	#endif
	reflectedLight.indirectDiffuse += diffuse;
}
void RE_IndirectSpecular_Physical( const in vec3 radiance, const in vec3 irradiance, const in vec3 clearcoatRadiance, const in vec3 geometryPosition, const in vec3 geometryNormal, const in vec3 geometryViewDir, const in vec3 geometryClearcoatNormal, const in PhysicalMaterial material, inout ReflectedLight reflectedLight) {
	#ifdef USE_CLEARCOAT
		clearcoatSpecularIndirect += clearcoatRadiance * EnvironmentBRDF( geometryClearcoatNormal, geometryViewDir, material.clearcoatF0, material.clearcoatF90, material.clearcoatRoughness );
	#endif
	#ifdef USE_SHEEN
		sheenSpecularIndirect += irradiance * material.sheenColor * IBLSheenBRDF( geometryNormal, geometryViewDir, material.sheenRoughness ) * RECIPROCAL_PI;
 	#endif
	vec3 singleScatteringDielectric = vec3( 0.0 );
	vec3 multiScatteringDielectric = vec3( 0.0 );
	vec3 singleScatteringMetallic = vec3( 0.0 );
	vec3 multiScatteringMetallic = vec3( 0.0 );
	#ifdef USE_IRIDESCENCE
		computeMultiscatteringIridescence( material.dfg, material.specularColor, material.specularF90, material.iridescence, material.iridescenceF0Dielectric, singleScatteringDielectric, multiScatteringDielectric );
		computeMultiscatteringIridescence( material.dfg, material.diffuseColor, material.specularF90, material.iridescence, material.iridescenceF0Metallic, singleScatteringMetallic, multiScatteringMetallic );
	#else
		computeMultiscattering( material.dfg, material.specularColor, material.specularF90, singleScatteringDielectric, multiScatteringDielectric );
		computeMultiscattering( material.dfg, material.diffuseColor, material.specularF90, singleScatteringMetallic, multiScatteringMetallic );
	#endif
	vec3 singleScattering = mix( singleScatteringDielectric, singleScatteringMetallic, material.metalness );
	vec3 multiScattering = mix( multiScatteringDielectric, multiScatteringMetallic, material.metalness );
	vec3 totalScatteringDielectric = singleScatteringDielectric + multiScatteringDielectric;
	#ifdef USE_DIFFUSE_ROUGHNESS
		float dotNV = saturate( dot( geometryNormal, geometryViewDir ) );
		vec3 diffuse = EON_DirectionalAlbedo( material.diffuseColor, material.diffuseRoughness, dotNV ) * ( 1.0 - material.metalness ) * ( 1.0 - totalScatteringDielectric );
	#else
		vec3 diffuse = material.diffuseContribution * ( 1.0 - totalScatteringDielectric );
	#endif
	vec3 cosineWeightedIrradiance = irradiance * RECIPROCAL_PI;
	vec3 indirectSpecular = radiance * singleScattering;
	indirectSpecular += multiScattering * cosineWeightedIrradiance;
	vec3 indirectDiffuse = diffuse * cosineWeightedIrradiance;
	#ifdef USE_SHEEN
		float sheenAlbedo = IBLSheenBRDF( geometryNormal, geometryViewDir, material.sheenRoughness );
		float sheenEnergyComp = 1.0 - max3( material.sheenColor ) * sheenAlbedo;
		indirectSpecular *= sheenEnergyComp;
		indirectDiffuse *= sheenEnergyComp;
	#endif
	reflectedLight.indirectSpecular += indirectSpecular;
	reflectedLight.indirectDiffuse += indirectDiffuse;
}
#define RE_Direct				RE_Direct_Physical
#define RE_Direct_RectArea		RE_Direct_RectArea_Physical
#define RE_IndirectDiffuse		RE_IndirectDiffuse_Physical
#define RE_IndirectSpecular		RE_IndirectSpecular_Physical
float computeSpecularOcclusion( const in float dotNV, const in float ambientOcclusion, const in float roughness ) {
	return saturate( pow( dotNV + ambientOcclusion, exp2( - 16.0 * roughness - 1.0 ) ) - 1.0 + ambientOcclusion );
}`,Ss=`
vec3 geometryPosition = - vViewPosition;
vec3 geometryNormal = normal;
vec3 geometryViewDir = ( isOrthographic ) ? vec3( 0, 0, 1 ) : normalize( vViewPosition );
vec3 geometryClearcoatNormal = vec3( 0.0 );
#ifdef USE_CLEARCOAT
	geometryClearcoatNormal = clearcoatNormal;
#endif
#ifdef USE_IRIDESCENCE
	float dotNVi = saturate( dot( normal, geometryViewDir ) );
	if ( material.iridescenceThickness == 0.0 ) {
		material.iridescence = 0.0;
	} else {
		material.iridescence = saturate( material.iridescence );
	}
	if ( material.iridescence > 0.0 ) {
		vec3 iridescenceFresnelDielectric = evalIridescence( 1.0, material.iridescenceIOR, dotNVi, material.iridescenceThickness, material.specularColor );
		vec3 iridescenceFresnelMetallic = evalIridescence( 1.0, material.iridescenceIOR, dotNVi, material.iridescenceThickness, material.diffuseColor );
		material.iridescenceFresnel = mix( iridescenceFresnelDielectric, iridescenceFresnelMetallic, material.metalness );
		material.iridescenceF0Dielectric = Schlick_to_F0( iridescenceFresnelDielectric, 1.0, dotNVi );
		material.iridescenceF0Metallic = Schlick_to_F0( iridescenceFresnelMetallic, 1.0, dotNVi );
	}
#endif
#ifdef STANDARD
	float dotNVms = saturate( dot( geometryNormal, geometryViewDir ) );
	material.dfg = texture2D( dfgLUT, vec2( material.roughness, dotNVms ) ).rg;
	#if ( NUM_SUN_LIGHTS > 0 || NUM_DIR_LIGHTS > 0 || NUM_POINT_LIGHTS > 0 || NUM_SPOT_LIGHTS > 0 )
		float EssMs = material.dfg.x + material.dfg.y;
		material.multiScatteringCompensation = 1.0 + material.specularColorBlended * ( 1.0 / EssMs - 1.0 );
	#endif
#endif
IncidentLight directLight;
#if ( NUM_POINT_LIGHTS > 0 ) && defined( RE_Direct )
	PointLight pointLight;
	#if defined( USE_SHADOWMAP ) && NUM_POINT_LIGHT_SHADOWS > 0
	PointLightShadow pointLightShadow;
	#endif
	#pragma unroll_loop_start
	for ( int i = 0; i < NUM_POINT_LIGHTS; i ++ ) {
		pointLight = pointLights[ i ];
		getPointLightInfo( pointLight, geometryPosition, directLight );
		#if defined( USE_SHADOWMAP ) && ( UNROLLED_LOOP_INDEX < NUM_POINT_LIGHT_SHADOWS ) && ( defined( SHADOWMAP_TYPE_PCF ) || defined( SHADOWMAP_TYPE_BASIC ) )
		pointLightShadow = pointLightShadows[ i ];
		directLight.color *= ( directLight.visible && receiveShadow ) ? getPointShadow( pointShadowMap[ i ], pointLightShadow.shadowMapSize, pointLightShadow.shadowIntensity, pointLightShadow.shadowBias, pointLightShadow.shadowRadius, vPointShadowCoord[ i ], pointLightShadow.shadowCameraNear, pointLightShadow.shadowCameraFar ) : 1.0;
		#endif
		RE_Direct( directLight, geometryPosition, geometryNormal, geometryViewDir, geometryClearcoatNormal, material, reflectedLight );
	}
	#pragma unroll_loop_end
#endif
#if ( NUM_SPOT_LIGHTS > 0 ) && defined( RE_Direct )
	SpotLight spotLight;
	vec4 spotColor;
	vec3 spotLightCoord;
	bool inSpotLightMap;
	#if defined( USE_SHADOWMAP ) && NUM_SPOT_LIGHT_SHADOWS > 0
	SpotLightShadow spotLightShadow;
	#endif
	#pragma unroll_loop_start
	for ( int i = 0; i < NUM_SPOT_LIGHTS; i ++ ) {
		spotLight = spotLights[ i ];
		getSpotLightInfo( spotLight, geometryPosition, directLight );
		#if ( UNROLLED_LOOP_INDEX < NUM_SPOT_LIGHT_SHADOWS_WITH_MAPS )
		#define SPOT_LIGHT_MAP_INDEX UNROLLED_LOOP_INDEX
		#elif ( UNROLLED_LOOP_INDEX < NUM_SPOT_LIGHT_SHADOWS )
		#define SPOT_LIGHT_MAP_INDEX NUM_SPOT_LIGHT_MAPS
		#else
		#define SPOT_LIGHT_MAP_INDEX ( UNROLLED_LOOP_INDEX - NUM_SPOT_LIGHT_SHADOWS + NUM_SPOT_LIGHT_SHADOWS_WITH_MAPS )
		#endif
		#if ( SPOT_LIGHT_MAP_INDEX < NUM_SPOT_LIGHT_MAPS )
			spotLightCoord = vSpotLightCoord[ i ].xyz / vSpotLightCoord[ i ].w;
			inSpotLightMap = all( lessThan( abs( spotLightCoord * 2. - 1. ), vec3( 1.0 ) ) );
			spotColor = texture2D( spotLightMap[ SPOT_LIGHT_MAP_INDEX ], spotLightCoord.xy );
			directLight.color = inSpotLightMap ? directLight.color * spotColor.rgb : directLight.color;
		#endif
		#undef SPOT_LIGHT_MAP_INDEX
		#if defined( USE_SHADOWMAP ) && ( UNROLLED_LOOP_INDEX < NUM_SPOT_LIGHT_SHADOWS )
		spotLightShadow = spotLightShadows[ i ];
		directLight.color *= ( directLight.visible && receiveShadow ) ? getShadow( spotShadowMap[ i ], spotLightShadow.shadowMapSize, spotLightShadow.shadowIntensity, spotLightShadow.shadowBias, spotLightShadow.shadowRadius, vSpotLightCoord[ i ] ) : 1.0;
		#endif
		RE_Direct( directLight, geometryPosition, geometryNormal, geometryViewDir, geometryClearcoatNormal, material, reflectedLight );
	}
	#pragma unroll_loop_end
#endif
#if ( NUM_SUN_LIGHTS > 0 ) && defined( RE_Direct )
	SunLight sunLight;
	#if defined( USE_SHADOWMAP ) && NUM_SUN_LIGHT_SHADOWS > 0
	SunLightShadow sunLightShadow;
	#endif
	#pragma unroll_loop_start
	for ( int i = 0; i < NUM_SUN_LIGHTS; i ++ ) {
		sunLight = sunLights[ i ];
		getSunLightInfo( sunLight, directLight );
		#if defined( USE_SHADOWMAP ) && ( UNROLLED_LOOP_INDEX < NUM_SUN_LIGHT_SHADOWS )
		sunLightShadow = sunLightShadows[ i ];
		directLight.color *= ( directLight.visible && receiveShadow ) ? getSunShadow( sunShadowMap[ i ], sunLightShadow, UNROLLED_LOOP_INDEX ) : 1.0;
		#endif
		RE_Direct( directLight, geometryPosition, geometryNormal, geometryViewDir, geometryClearcoatNormal, material, reflectedLight );
	}
	#pragma unroll_loop_end
#endif
#if ( NUM_DIR_LIGHTS > 0 ) && defined( RE_Direct )
	DirectionalLight directionalLight;
	#if defined( USE_SHADOWMAP ) && NUM_DIR_LIGHT_SHADOWS > 0
	DirectionalLightShadow directionalLightShadow;
	#endif
	#pragma unroll_loop_start
	for ( int i = 0; i < NUM_DIR_LIGHTS; i ++ ) {
		directionalLight = directionalLights[ i ];
		getDirectionalLightInfo( directionalLight, directLight );
		#if defined( USE_SHADOWMAP ) && ( UNROLLED_LOOP_INDEX < NUM_DIR_LIGHT_SHADOWS )
		directionalLightShadow = directionalLightShadows[ i ];
		directLight.color *= ( directLight.visible && receiveShadow ) ? getShadow( directionalShadowMap[ i ], directionalLightShadow.shadowMapSize, directionalLightShadow.shadowIntensity, directionalLightShadow.shadowBias, directionalLightShadow.shadowRadius, vDirectionalShadowCoord[ i ] ) : 1.0;
		#endif
		RE_Direct( directLight, geometryPosition, geometryNormal, geometryViewDir, geometryClearcoatNormal, material, reflectedLight );
	}
	#pragma unroll_loop_end
#endif
#if ( NUM_RECT_AREA_LIGHTS > 0 ) && defined( RE_Direct_RectArea )
	RectAreaLight rectAreaLight;
	#pragma unroll_loop_start
	for ( int i = 0; i < NUM_RECT_AREA_LIGHTS; i ++ ) {
		rectAreaLight = rectAreaLights[ i ];
		RE_Direct_RectArea( rectAreaLight, geometryPosition, geometryNormal, geometryViewDir, geometryClearcoatNormal, material, reflectedLight );
	}
	#pragma unroll_loop_end
#endif
#if defined( RE_IndirectDiffuse )
	vec3 iblIrradiance = vec3( 0.0 );
	vec3 irradiance = getAmbientLightIrradiance( ambientLightColor );
	#if defined( USE_LIGHT_PROBES )
		irradiance += getLightProbeIrradiance( lightProbe, geometryNormal );
	#endif
	#if ( NUM_HEMI_LIGHTS > 0 )
		#pragma unroll_loop_start
		for ( int i = 0; i < NUM_HEMI_LIGHTS; i ++ ) {
			irradiance += getHemisphereLightIrradiance( hemisphereLights[ i ], geometryNormal );
		}
		#pragma unroll_loop_end
	#endif
	#if NUM_LIGHT_PROBE_GRIDS > 0
		vec3 probeWorldPos = ( ( vec4( geometryPosition, 1.0 ) - viewMatrix[ 3 ] ) * viewMatrix ).xyz;
		vec3 probeWorldNormal = transformNormalByInverseViewMatrix( geometryNormal, viewMatrix );
		int probeGrid = - 1;
		#pragma unroll_loop_start
		for ( int i = 0; i < NUM_LIGHT_PROBE_GRIDS; i ++ ) {
			if ( isInsideLightProbeGrid( probeWorldPos, probeWorldNormal, probesMin[ i ], probesMax[ i ], probesResolution[ i ] ) ) probeGrid = UNROLLED_LOOP_INDEX;
		}
		#pragma unroll_loop_end
		#pragma unroll_loop_start
		for ( int i = 0; i < NUM_LIGHT_PROBE_GRIDS; i ++ ) {
			if ( probeGrid == UNROLLED_LOOP_INDEX ) irradiance += getLightProbeGridIrradiance( probesSH[ i ], probesMin[ i ], probesMax[ i ], probesResolution[ i ], probeWorldPos, probeWorldNormal );
		}
		#pragma unroll_loop_end
	#endif
#endif
#if defined( RE_IndirectSpecular )
	vec3 radiance = vec3( 0.0 );
	vec3 clearcoatRadiance = vec3( 0.0 );
#endif`,Es=`#if defined( RE_IndirectDiffuse )
	#ifdef USE_LIGHTMAP
		vec4 lightMapTexel = texture2D( lightMap, vLightMapUv );
		vec3 lightMapIrradiance = lightMapTexel.rgb * lightMapIntensity;
		irradiance += lightMapIrradiance;
	#endif
	#if defined( USE_ENVMAP ) && defined( ENVMAP_TYPE_PMREM )
		#if defined( STANDARD ) || defined( LAMBERT ) || defined( PHONG )
			iblIrradiance += getIBLIrradiance( geometryNormal );
		#endif
	#endif
#endif
#if defined( USE_ENVMAP ) && defined( RE_IndirectSpecular )
	#ifdef USE_ANISOTROPY
		vec3 iblRadiance = getIBLAnisotropyRadiance( geometryViewDir, geometryNormal, material.roughness, material.anisotropyB, material.anisotropy );
	#else
		vec3 iblRadiance = getIBLRadiance( geometryViewDir, geometryNormal, material.roughness );
	#endif
	#ifdef USE_RETROREFLECTION
		#ifdef USE_ANISOTROPY
			vec3 retroIBLRadiance = getIBLAnisotropyRetroRadiance( geometryViewDir, geometryNormal, material.roughness, material.anisotropyB, material.anisotropy );
		#else
			vec3 retroIBLRadiance = getIBLRetroRadiance( geometryViewDir, geometryNormal, material.roughness );
		#endif
		iblRadiance = mix( iblRadiance, retroIBLRadiance, saturate( material.retroreflectivity ) );
	#endif
	radiance += iblRadiance;
	#ifdef USE_CLEARCOAT
		clearcoatRadiance += getIBLRadiance( geometryViewDir, geometryClearcoatNormal, material.clearcoatRoughness );
	#endif
#endif`,Ms=`#if defined( RE_IndirectDiffuse )
	#if defined( LAMBERT ) || defined( PHONG )
		irradiance += iblIrradiance;
	#endif
	RE_IndirectDiffuse( irradiance, geometryPosition, geometryNormal, geometryViewDir, geometryClearcoatNormal, material, reflectedLight );
#endif
#if defined( RE_IndirectSpecular )
	RE_IndirectSpecular( radiance, iblIrradiance, clearcoatRadiance, geometryPosition, geometryNormal, geometryViewDir, geometryClearcoatNormal, material, reflectedLight );
#endif`,xs=`#if NUM_LIGHT_PROBE_GRIDS > 0
uniform highp sampler3D probesSH[ NUM_LIGHT_PROBE_GRIDS ];
uniform vec3 probesMin[ NUM_LIGHT_PROBE_GRIDS ];
uniform vec3 probesMax[ NUM_LIGHT_PROBE_GRIDS ];
uniform vec3 probesResolution[ NUM_LIGHT_PROBE_GRIDS ];
bool isInsideLightProbeGrid( vec3 worldPos, vec3 worldNormal, vec3 probesMin, vec3 probesMax, vec3 probesResolution ) {
	vec3 spacing = ( probesMax - probesMin ) / probesResolution;
	vec3 samplePos = worldPos + worldNormal * spacing * 0.5;
	vec3 margin = spacing * 0.01;
	return all( greaterThanEqual( samplePos, probesMin - margin ) ) && all( lessThanEqual( samplePos, probesMax + margin ) );
}
vec3 getLightProbeGridIrradiance( highp sampler3D probesSH, vec3 probesMin, vec3 probesMax, vec3 probesResolution, vec3 worldPos, vec3 worldNormal ) {
	vec3 res = probesResolution;
	vec3 gridRange = probesMax - probesMin;
	vec3 probeSpacing = gridRange / res;
	vec3 samplePos = worldPos + worldNormal * probeSpacing * 0.5;
	vec3 uvw = clamp( ( samplePos - probesMin ) / gridRange, 0.5 / res, 1.0 - 0.5 / res );
	float nz          = res.z;
	float paddedSlices = nz + 2.0;
	float atlasDepth  = 7.0 * paddedSlices;
	float uvZBase     = uvw.z * nz + 1.0;
	vec4 s0 = texture( probesSH, vec3( uvw.xy, ( uvZBase                       ) / atlasDepth ) );
	vec4 s1 = texture( probesSH, vec3( uvw.xy, ( uvZBase +       paddedSlices   ) / atlasDepth ) );
	vec4 s2 = texture( probesSH, vec3( uvw.xy, ( uvZBase + 2.0 * paddedSlices   ) / atlasDepth ) );
	vec4 s3 = texture( probesSH, vec3( uvw.xy, ( uvZBase + 3.0 * paddedSlices   ) / atlasDepth ) );
	vec4 s4 = texture( probesSH, vec3( uvw.xy, ( uvZBase + 4.0 * paddedSlices   ) / atlasDepth ) );
	vec4 s5 = texture( probesSH, vec3( uvw.xy, ( uvZBase + 5.0 * paddedSlices   ) / atlasDepth ) );
	vec4 s6 = texture( probesSH, vec3( uvw.xy, ( uvZBase + 6.0 * paddedSlices   ) / atlasDepth ) );
	vec3 c0 = s0.xyz;
	vec3 c1 = vec3( s0.w, s1.xy );
	vec3 c2 = vec3( s1.zw, s2.x );
	vec3 c3 = s2.yzw;
	vec3 c4 = s3.xyz;
	vec3 c5 = vec3( s3.w, s4.xy );
	vec3 c6 = vec3( s4.zw, s5.x );
	vec3 c7 = s5.yzw;
	vec3 c8 = s6.xyz;
	float x = worldNormal.x, y = worldNormal.y, z = worldNormal.z;
	vec3 result = c0 * 0.886227;
	result += c1 * 2.0 * 0.511664 * y;
	result += c2 * 2.0 * 0.511664 * z;
	result += c3 * 2.0 * 0.511664 * x;
	result += c4 * 2.0 * 0.429043 * x * y;
	result += c5 * 2.0 * 0.429043 * y * z;
	result += c6 * ( 0.743125 * z * z - 0.247708 );
	result += c7 * 2.0 * 0.429043 * x * z;
	result += c8 * 0.429043 * ( x * x - y * y );
	return max( result, vec3( 0.0 ) );
}
#endif`,Ts=`#if defined( USE_LOGARITHMIC_DEPTH_BUFFER )
	gl_FragDepth = vIsPerspective == 0.0 ? gl_FragCoord.z : log2( vFragDepth ) * logDepthBufFC * 0.5;
#endif`,As=`#if defined( USE_LOGARITHMIC_DEPTH_BUFFER )
	uniform float logDepthBufFC;
	varying float vFragDepth;
	varying float vIsPerspective;
#endif`,Rs=`#ifdef USE_LOGARITHMIC_DEPTH_BUFFER
	varying float vFragDepth;
	varying float vIsPerspective;
#endif`,Cs=`#ifdef USE_LOGARITHMIC_DEPTH_BUFFER
	vFragDepth = 1.0 + gl_Position.w;
	vIsPerspective = float( isPerspectiveMatrix( projectionMatrix ) );
#endif`,bs=`#ifdef USE_MAP
	vec4 sampledDiffuseColor = texture2D( map, vMapUv );
	#ifdef DECODE_VIDEO_TEXTURE
		sampledDiffuseColor = sRGBTransferEOTF( sampledDiffuseColor );
	#endif
	diffuseColor *= sampledDiffuseColor;
#endif`,Ps=`#ifdef USE_MAP
	uniform sampler2D map;
#endif`,Ls=`#if defined( USE_MAP ) || defined( USE_ALPHAMAP )
	#if defined( USE_POINTS_UV )
		vec2 uv = vUv;
	#else
		vec2 uv = ( uvTransform * vec3( gl_PointCoord.x, 1.0 - gl_PointCoord.y, 1 ) ).xy;
	#endif
#endif
#ifdef USE_MAP
	diffuseColor *= texture2D( map, uv );
#endif
#ifdef USE_ALPHAMAP
	diffuseColor.a *= texture2D( alphaMap, uv ).g;
#endif`,Ds=`#if defined( USE_POINTS_UV )
	varying vec2 vUv;
#else
	#if defined( USE_MAP ) || defined( USE_ALPHAMAP )
		uniform mat3 uvTransform;
	#endif
#endif
#ifdef USE_MAP
	uniform sampler2D map;
#endif
#ifdef USE_ALPHAMAP
	uniform sampler2D alphaMap;
#endif`,Us=`float metalnessFactor = metalness;
#ifdef USE_METALNESSMAP
	vec4 texelMetalness = texture2D( metalnessMap, vMetalnessMapUv );
	metalnessFactor *= texelMetalness.b;
#endif`,ws=`#ifdef USE_METALNESSMAP
	uniform sampler2D metalnessMap;
#endif`,Ns=`#ifdef USE_INSTANCING_MORPH
	float morphTargetInfluences[ MORPHTARGETS_COUNT ];
	float morphTargetBaseInfluence = texelFetch( morphTexture, ivec2( 0, gl_InstanceID ), 0 ).r;
	for ( int i = 0; i < MORPHTARGETS_COUNT; i ++ ) {
		morphTargetInfluences[i] =  texelFetch( morphTexture, ivec2( i + 1, gl_InstanceID ), 0 ).r;
	}
#endif`,Is=`#if defined( USE_MORPHCOLORS )
	#if defined( USE_COLOR_ALPHA )
		vColor *= morphTargetBaseInfluence;
	#elif defined( USE_COLOR )
		vColor.rgb *= morphTargetBaseInfluence;
	#endif
	for ( int i = 0; i < MORPHTARGETS_COUNT; i ++ ) {
		#if defined( USE_COLOR_ALPHA )
			if ( morphTargetInfluences[ i ] != 0.0 ) vColor += getMorph( gl_VertexID, i, 2 ) * morphTargetInfluences[ i ];
		#elif defined( USE_COLOR )
			if ( morphTargetInfluences[ i ] != 0.0 ) vColor.rgb += getMorph( gl_VertexID, i, 2 ).rgb * morphTargetInfluences[ i ];
		#endif
	}
#endif`,ys=`#ifdef USE_MORPHNORMALS
	objectNormal *= morphTargetBaseInfluence;
	for ( int i = 0; i < MORPHTARGETS_COUNT; i ++ ) {
		if ( morphTargetInfluences[ i ] != 0.0 ) objectNormal += getMorph( gl_VertexID, i, 1 ).xyz * morphTargetInfluences[ i ];
	}
#endif`,Fs=`#ifdef USE_MORPHTARGETS
	#ifndef USE_INSTANCING_MORPH
		uniform float morphTargetBaseInfluence;
		uniform float morphTargetInfluences[ MORPHTARGETS_COUNT ];
	#endif
	uniform sampler2DArray morphTargetsTexture;
	uniform ivec2 morphTargetsTextureSize;
	vec4 getMorph( const in int vertexIndex, const in int morphTargetIndex, const in int offset ) {
		int texelIndex = vertexIndex * MORPHTARGETS_TEXTURE_STRIDE + offset;
		int y = texelIndex / morphTargetsTextureSize.x;
		int x = texelIndex - y * morphTargetsTextureSize.x;
		ivec3 morphUV = ivec3( x, y, morphTargetIndex );
		return texelFetch( morphTargetsTexture, morphUV, 0 );
	}
#endif`,Os=`#ifdef USE_MORPHTARGETS
	transformed *= morphTargetBaseInfluence;
	for ( int i = 0; i < MORPHTARGETS_COUNT; i ++ ) {
		if ( morphTargetInfluences[ i ] != 0.0 ) transformed += getMorph( gl_VertexID, i, 0 ).xyz * morphTargetInfluences[ i ];
	}
#endif`,Bs=`float faceDirection = gl_FrontFacing ? 1.0 : - 1.0;
#ifdef FLAT_SHADED
	vec3 fdx = dFdx( vViewPosition );
	vec3 fdy = dFdy( vViewPosition );
	vec3 normal = normalize( cross( fdx, fdy ) );
#else
	vec3 normal = normalize( vNormal );
	#ifdef DOUBLE_SIDED
		normal *= faceDirection;
	#endif
#endif
#if defined( USE_NORMALMAP_TANGENTSPACE ) || defined( USE_CLEARCOAT_NORMALMAP ) || defined( USE_ANISOTROPY )
	#ifdef USE_TANGENT
		mat3 tbn = mat3( normalize( vTangent ), normalize( vBitangent ), normal );
	#else
		mat3 tbn = getTangentFrame( - vViewPosition, normal,
		#if defined( USE_NORMALMAP )
			vNormalMapUv
		#elif defined( USE_CLEARCOAT_NORMALMAP )
			vClearcoatNormalMapUv
		#else
			vUv
		#endif
		);
	#endif
	#ifdef DOUBLE_SIDED
		tbn[0] *= faceDirection;
		tbn[1] *= faceDirection;
	#endif
#endif
#ifdef USE_CLEARCOAT_NORMALMAP
	#ifdef USE_TANGENT
		mat3 tbn2 = mat3( normalize( vTangent ), normalize( vBitangent ), normal );
	#else
		mat3 tbn2 = getTangentFrame( - vViewPosition, normal, vClearcoatNormalMapUv );
	#endif
	#ifdef DOUBLE_SIDED
		tbn2[0] *= faceDirection;
		tbn2[1] *= faceDirection;
	#endif
#endif
vec3 nonPerturbedNormal = normal;`,Gs=`#ifdef USE_NORMALMAP_OBJECTSPACE
	normal = texture2D( normalMap, vNormalMapUv ).xyz * 2.0 - 1.0;
	#ifdef FLIP_SIDED
		normal = - normal;
	#endif
	#ifdef DOUBLE_SIDED
		normal = normal * faceDirection;
	#endif
	normal = normalize( normalMatrix * normal );
#elif defined( USE_NORMALMAP_TANGENTSPACE )
	vec3 mapN = texture2D( normalMap, vNormalMapUv ).xyz * 2.0 - 1.0;
	#if defined( USE_PACKED_NORMALMAP )
		mapN = vec3( mapN.xy, sqrt( saturate( 1.0 - dot( mapN.xy, mapN.xy ) ) ) );
	#endif
	mapN.xy *= normalScale;
	normal = normalize( tbn * mapN );
#elif defined( USE_BUMPMAP )
	normal = perturbNormalArb( - vViewPosition, normal, dHdxy_fwd(), faceDirection );
#endif`,Hs=`#ifndef FLAT_SHADED
	varying vec3 vNormal;
	#ifdef USE_TANGENT
		varying vec3 vTangent;
		varying vec3 vBitangent;
	#endif
#endif`,Vs=`#ifndef FLAT_SHADED
	varying vec3 vNormal;
	#ifdef USE_TANGENT
		varying vec3 vTangent;
		varying vec3 vBitangent;
	#endif
#endif`,Ws=`#ifndef FLAT_SHADED
	vNormal = normalize( transformedNormal );
	#ifdef USE_TANGENT
		vTangent = normalize( transformedTangent );
		vBitangent = normalize( cross( vNormal, vTangent ) * tangent.w );
		#ifdef FLIP_SIDED
			vBitangent = - vBitangent;
		#endif
	#endif
#endif`,ks=`#ifdef USE_NORMALMAP
	uniform sampler2D normalMap;
	uniform vec2 normalScale;
#endif
#ifdef USE_NORMALMAP_OBJECTSPACE
	uniform mat3 normalMatrix;
#endif
#if ! defined ( USE_TANGENT ) && ( defined ( USE_NORMALMAP_TANGENTSPACE ) || defined ( USE_CLEARCOAT_NORMALMAP ) || defined( USE_ANISOTROPY ) )
	mat3 getTangentFrame( vec3 eye_pos, vec3 surf_norm, vec2 uv ) {
		vec3 q0 = dFdx( eye_pos.xyz );
		vec3 q1 = dFdy( eye_pos.xyz );
		vec2 st0 = dFdx( uv.st );
		vec2 st1 = dFdy( uv.st );
		vec3 N = surf_norm;
		vec3 q1perp = cross( q1, N );
		vec3 q0perp = cross( N, q0 );
		vec3 T = q1perp * st0.x + q0perp * st1.x;
		vec3 B = q1perp * st0.y + q0perp * st1.y;
		float det = max( dot( T, T ), dot( B, B ) );
		float scale = ( det == 0.0 ) ? 0.0 : inversesqrt( det );
		return mat3( T * scale, B * scale, N );
	}
#endif`,zs=`#ifdef USE_CLEARCOAT
	vec3 clearcoatNormal = nonPerturbedNormal;
#endif`,Xs=`#ifdef USE_CLEARCOAT_NORMALMAP
	vec3 clearcoatMapN = texture2D( clearcoatNormalMap, vClearcoatNormalMapUv ).xyz * 2.0 - 1.0;
	clearcoatMapN.xy *= clearcoatNormalScale;
	clearcoatNormal = normalize( tbn2 * clearcoatMapN );
#endif`,Ks=`#ifdef USE_CLEARCOATMAP
	uniform sampler2D clearcoatMap;
#endif
#ifdef USE_CLEARCOAT_NORMALMAP
	uniform sampler2D clearcoatNormalMap;
	uniform vec2 clearcoatNormalScale;
#endif
#ifdef USE_CLEARCOAT_ROUGHNESSMAP
	uniform sampler2D clearcoatRoughnessMap;
#endif`,qs=`#ifdef USE_IRIDESCENCEMAP
	uniform sampler2D iridescenceMap;
#endif
#ifdef USE_IRIDESCENCE_THICKNESSMAP
	uniform sampler2D iridescenceThicknessMap;
#endif`,Ys=`#ifdef OPAQUE
diffuseColor.a = 1.0;
#endif
#ifdef USE_TRANSMISSION
diffuseColor.a *= material.transmissionAlpha;
#endif
gl_FragColor = vec4( outgoingLight, diffuseColor.a );`,Zs=`vec3 packNormalToRGB( const in vec3 normal ) {
	return normalize( normal ) * 0.5 + 0.5;
}
vec3 unpackRGBToNormal( const in vec3 rgb ) {
	return 2.0 * rgb.xyz - 1.0;
}
const float PackUpscale = 256. / 255.;const float UnpackDownscale = 255. / 256.;const float ShiftRight8 = 1. / 256.;
const float Inv255 = 1. / 255.;
const vec4 PackFactors = vec4( 1.0, 256.0, 256.0 * 256.0, 256.0 * 256.0 * 256.0 );
const vec2 UnpackFactors2 = vec2( UnpackDownscale, 1.0 / PackFactors.g );
const vec3 UnpackFactors3 = vec3( UnpackDownscale / PackFactors.rg, 1.0 / PackFactors.b );
const vec4 UnpackFactors4 = vec4( UnpackDownscale / PackFactors.rgb, 1.0 / PackFactors.a );
vec4 packDepthToRGBA( const in float v ) {
	if( v <= 0.0 )
		return vec4( 0., 0., 0., 0. );
	if( v >= 1.0 )
		return vec4( 1., 1., 1., 1. );
	float vuf;
	float af = modf( v * PackFactors.a, vuf );
	float bf = modf( vuf * ShiftRight8, vuf );
	float gf = modf( vuf * ShiftRight8, vuf );
	return vec4( vuf * Inv255, gf * PackUpscale, bf * PackUpscale, af );
}
vec3 packDepthToRGB( const in float v ) {
	if( v <= 0.0 )
		return vec3( 0., 0., 0. );
	if( v >= 1.0 )
		return vec3( 1., 1., 1. );
	float vuf;
	float bf = modf( v * PackFactors.b, vuf );
	float gf = modf( vuf * ShiftRight8, vuf );
	return vec3( vuf * Inv255, gf * PackUpscale, bf );
}
vec2 packDepthToRG( const in float v ) {
	if( v <= 0.0 )
		return vec2( 0., 0. );
	if( v >= 1.0 )
		return vec2( 1., 1. );
	float vuf;
	float gf = modf( v * 256., vuf );
	return vec2( vuf * Inv255, gf );
}
float unpackRGBAToDepth( const in vec4 v ) {
	return dot( v, UnpackFactors4 );
}
float unpackRGBToDepth( const in vec3 v ) {
	return dot( v, UnpackFactors3 );
}
float unpackRGToDepth( const in vec2 v ) {
	return v.r * UnpackFactors2.r + v.g * UnpackFactors2.g;
}
vec4 pack2HalfToRGBA( const in vec2 v ) {
	vec4 r = vec4( v.x, fract( v.x * 255.0 ), v.y, fract( v.y * 255.0 ) );
	return vec4( r.x - r.y / 255.0, r.y, r.z - r.w / 255.0, r.w );
}
vec2 unpackRGBATo2Half( const in vec4 v ) {
	return vec2( v.x + ( v.y / 255.0 ), v.z + ( v.w / 255.0 ) );
}
float viewZToOrthographicDepth( const in float viewZ, const in float near, const in float far ) {
	return ( viewZ + near ) / ( near - far );
}
float orthographicDepthToViewZ( const in float depth, const in float near, const in float far ) {
	#ifdef USE_REVERSED_DEPTH_BUFFER
	
		return depth * ( far - near ) - far;
	#else
		return depth * ( near - far ) - near;
	#endif
}
float viewZToPerspectiveDepth( const in float viewZ, const in float near, const in float far ) {
	return ( ( near + viewZ ) * far ) / ( ( far - near ) * viewZ );
}
float perspectiveDepthToViewZ( const in float depth, const in float near, const in float far ) {
	
	#ifdef USE_REVERSED_DEPTH_BUFFER
		return ( near * far ) / ( ( near - far ) * depth - near );
	#else
		return ( near * far ) / ( ( far - near ) * depth - far );
	#endif
}`,$s=`#ifdef PREMULTIPLIED_ALPHA
	gl_FragColor.rgb *= gl_FragColor.a;
#endif`,Qs=`vec4 mvPosition = vec4( transformed, 1.0 );
#ifdef USE_BATCHING
	mvPosition = batchingMatrix * mvPosition;
#endif
#ifdef USE_INSTANCING
	mvPosition = instanceMatrix * mvPosition;
#endif
mvPosition = modelViewMatrix * mvPosition;
gl_Position = projectionMatrix * mvPosition;`,Js=`#ifdef DITHERING
	gl_FragColor.rgb = dithering( gl_FragColor.rgb );
#endif`,js=`#ifdef DITHERING
	vec3 dithering( vec3 color ) {
		float grid_position = rand( gl_FragCoord.xy );
		vec3 dither_shift_RGB = vec3( 0.25 / 255.0, -0.25 / 255.0, 0.25 / 255.0 );
		dither_shift_RGB = mix( 2.0 * dither_shift_RGB, -2.0 * dither_shift_RGB, grid_position );
		return color + dither_shift_RGB;
	}
#endif`,el=`float roughnessFactor = roughness;
#ifdef USE_ROUGHNESSMAP
	vec4 texelRoughness = texture2D( roughnessMap, vRoughnessMapUv );
	roughnessFactor *= texelRoughness.g;
#endif`,tl=`#ifdef USE_ROUGHNESSMAP
	uniform sampler2D roughnessMap;
#endif`,nl=`#if NUM_SPOT_LIGHT_COORDS > 0
	varying vec4 vSpotLightCoord[ NUM_SPOT_LIGHT_COORDS ];
#endif
#if NUM_SPOT_LIGHT_MAPS > 0
	uniform sampler2D spotLightMap[ NUM_SPOT_LIGHT_MAPS ];
#endif
#ifdef USE_SHADOWMAP
	#if NUM_SUN_LIGHT_SHADOWS > 0
		#define SUN_LIGHT_CASCADES 2
		#if defined( SHADOWMAP_TYPE_PCF )
			uniform sampler2DShadow sunShadowMap[ NUM_SUN_LIGHT_SHADOWS ];
		#else
			uniform sampler2D sunShadowMap[ NUM_SUN_LIGHT_SHADOWS ];
		#endif
		uniform mat4 sunShadowMatrix[ NUM_SUN_LIGHT_SHADOWS * SUN_LIGHT_CASCADES ];
		uniform vec4 sunShadowCascade[ NUM_SUN_LIGHT_SHADOWS * SUN_LIGHT_CASCADES ];
		varying vec4 vSunShadowWorldPosition;
		varying vec3 vSunShadowWorldNormal;
		struct SunLightShadow {
			float shadowIntensity;
			float shadowBias;
			float shadowNormalBias;
			float shadowRadius;
			vec2 shadowMapSize;
		};
		uniform SunLightShadow sunLightShadows[ NUM_SUN_LIGHT_SHADOWS ];
	#endif
	#if NUM_DIR_LIGHT_SHADOWS > 0
		#if defined( SHADOWMAP_TYPE_PCF )
			uniform sampler2DShadow directionalShadowMap[ NUM_DIR_LIGHT_SHADOWS ];
		#else
			uniform sampler2D directionalShadowMap[ NUM_DIR_LIGHT_SHADOWS ];
		#endif
		varying vec4 vDirectionalShadowCoord[ NUM_DIR_LIGHT_SHADOWS ];
		struct DirectionalLightShadow {
			float shadowIntensity;
			float shadowBias;
			float shadowNormalBias;
			float shadowRadius;
			vec2 shadowMapSize;
		};
		uniform DirectionalLightShadow directionalLightShadows[ NUM_DIR_LIGHT_SHADOWS ];
	#endif
	#if NUM_SPOT_LIGHT_SHADOWS > 0
		#if defined( SHADOWMAP_TYPE_PCF )
			uniform sampler2DShadow spotShadowMap[ NUM_SPOT_LIGHT_SHADOWS ];
		#else
			uniform sampler2D spotShadowMap[ NUM_SPOT_LIGHT_SHADOWS ];
		#endif
		struct SpotLightShadow {
			float shadowIntensity;
			float shadowBias;
			float shadowNormalBias;
			float shadowRadius;
			vec2 shadowMapSize;
		};
		uniform SpotLightShadow spotLightShadows[ NUM_SPOT_LIGHT_SHADOWS ];
	#endif
	#if NUM_POINT_LIGHT_SHADOWS > 0
		#if defined( SHADOWMAP_TYPE_PCF )
			uniform samplerCubeShadow pointShadowMap[ NUM_POINT_LIGHT_SHADOWS ];
		#elif defined( SHADOWMAP_TYPE_BASIC )
			uniform samplerCube pointShadowMap[ NUM_POINT_LIGHT_SHADOWS ];
		#endif
		varying vec4 vPointShadowCoord[ NUM_POINT_LIGHT_SHADOWS ];
		struct PointLightShadow {
			float shadowIntensity;
			float shadowBias;
			float shadowNormalBias;
			float shadowRadius;
			vec2 shadowMapSize;
			float shadowCameraNear;
			float shadowCameraFar;
		};
		uniform PointLightShadow pointLightShadows[ NUM_POINT_LIGHT_SHADOWS ];
	#endif
	#if defined( SHADOWMAP_TYPE_PCF )
		float interleavedGradientNoise( vec2 position ) {
			return fract( 52.9829189 * fract( dot( position, vec2( 0.06711056, 0.00583715 ) ) ) );
		}
		vec2 vogelDiskSample( int sampleIndex, int samplesCount, float phi ) {
			const float goldenAngle = 2.399963229728653;
			float r = sqrt( ( float( sampleIndex ) + 0.5 ) / float( samplesCount ) );
			float theta = float( sampleIndex ) * goldenAngle + phi;
			return vec2( cos( theta ), sin( theta ) ) * r;
		}
	#endif
	#if defined( SHADOWMAP_TYPE_PCF )
		float getShadow( sampler2DShadow shadowMap, vec2 shadowMapSize, float shadowIntensity, float shadowBias, float shadowRadius, vec4 shadowCoord ) {
			float shadow = 1.0;
			shadowCoord.xyz /= shadowCoord.w;
			#ifdef USE_REVERSED_DEPTH_BUFFER
				shadowCoord.z -= shadowBias;
			#else
				shadowCoord.z += shadowBias;
			#endif
			bool inFrustum = shadowCoord.x >= 0.0 && shadowCoord.x <= 1.0 && shadowCoord.y >= 0.0 && shadowCoord.y <= 1.0;
			bool frustumTest = inFrustum && shadowCoord.z <= 1.0;
			if ( frustumTest ) {
				vec2 texelSize = vec2( 1.0 ) / shadowMapSize;
				vec2 radius = shadowRadius * texelSize;
				float phi = interleavedGradientNoise( gl_FragCoord.xy ) * PI2;
				shadow = (
					texture( shadowMap, vec3( shadowCoord.xy + vogelDiskSample( 0, 5, phi ) * radius, shadowCoord.z ) ) +
					texture( shadowMap, vec3( shadowCoord.xy + vogelDiskSample( 1, 5, phi ) * radius, shadowCoord.z ) ) +
					texture( shadowMap, vec3( shadowCoord.xy + vogelDiskSample( 2, 5, phi ) * radius, shadowCoord.z ) ) +
					texture( shadowMap, vec3( shadowCoord.xy + vogelDiskSample( 3, 5, phi ) * radius, shadowCoord.z ) ) +
					texture( shadowMap, vec3( shadowCoord.xy + vogelDiskSample( 4, 5, phi ) * radius, shadowCoord.z ) )
				) * 0.2;
			}
			return mix( 1.0, shadow, shadowIntensity );
		}
	#elif defined( SHADOWMAP_TYPE_VSM )
		float getShadow( sampler2D shadowMap, vec2 shadowMapSize, float shadowIntensity, float shadowBias, float shadowRadius, vec4 shadowCoord ) {
			float shadow = 1.0;
			shadowCoord.xyz /= shadowCoord.w;
			#ifdef USE_REVERSED_DEPTH_BUFFER
				shadowCoord.z -= shadowBias;
			#else
				shadowCoord.z += shadowBias;
			#endif
			bool inFrustum = shadowCoord.x >= 0.0 && shadowCoord.x <= 1.0 && shadowCoord.y >= 0.0 && shadowCoord.y <= 1.0;
			bool frustumTest = inFrustum && shadowCoord.z <= 1.0;
			if ( frustumTest ) {
				vec2 distribution = texture2D( shadowMap, shadowCoord.xy ).rg;
				float mean = distribution.x;
				float variance = distribution.y * distribution.y;
				#ifdef USE_REVERSED_DEPTH_BUFFER
					float hard_shadow = step( mean, shadowCoord.z );
				#else
					float hard_shadow = step( shadowCoord.z, mean );
				#endif
				
				if ( hard_shadow == 1.0 ) {
					shadow = 1.0;
				} else {
					variance = max( variance, 0.0000001 );
					float d = shadowCoord.z - mean;
					float p_max = variance / ( variance + d * d );
					p_max = clamp( ( p_max - 0.3 ) / 0.65, 0.0, 1.0 );
					shadow = max( hard_shadow, p_max );
				}
			}
			return mix( 1.0, shadow, shadowIntensity );
		}
	#else
		float getShadow( sampler2D shadowMap, vec2 shadowMapSize, float shadowIntensity, float shadowBias, float shadowRadius, vec4 shadowCoord ) {
			float shadow = 1.0;
			shadowCoord.xyz /= shadowCoord.w;
			#ifdef USE_REVERSED_DEPTH_BUFFER
				shadowCoord.z -= shadowBias;
			#else
				shadowCoord.z += shadowBias;
			#endif
			bool inFrustum = shadowCoord.x >= 0.0 && shadowCoord.x <= 1.0 && shadowCoord.y >= 0.0 && shadowCoord.y <= 1.0;
			bool frustumTest = inFrustum && shadowCoord.z <= 1.0;
			if ( frustumTest ) {
				float depth = texture2D( shadowMap, shadowCoord.xy ).r;
				#ifdef USE_REVERSED_DEPTH_BUFFER
					shadow = step( depth, shadowCoord.z );
				#else
					shadow = step( shadowCoord.z, depth );
				#endif
			}
			return mix( 1.0, shadow, shadowIntensity );
		}
	#endif
	#if NUM_SUN_LIGHT_SHADOWS > 0
		float getSunShadow(
			#if defined( SHADOWMAP_TYPE_PCF )
				sampler2DShadow shadowMap,
			#else
				sampler2D shadowMap,
			#endif
			SunLightShadow sunLightShadow,
			int shadowIndex
		) {
			vec4 shadowWorldPosition = vec4( vSunShadowWorldPosition.xyz + vSunShadowWorldNormal * sunLightShadow.shadowNormalBias, 1.0 );
			float viewDepth = vSunShadowWorldPosition.w;
			int cascadeOffset = shadowIndex * SUN_LIGHT_CASCADES;
			float shadow = 1.0;
			for ( int i = SUN_LIGHT_CASCADES - 1; i >= 0; i -- ) {
				vec4 cascade = sunShadowCascade[ cascadeOffset + i ];
				if ( viewDepth >= cascade.x && viewDepth < cascade.y ) {
					float cascadeShadow = getShadow(
						shadowMap,
						sunLightShadow.shadowMapSize,
						sunLightShadow.shadowIntensity,
						sunLightShadow.shadowBias,
						sunLightShadow.shadowRadius,
						sunShadowMatrix[ cascadeOffset + i ] * shadowWorldPosition
					);
					shadow = mix( cascadeShadow, shadow, smoothstep( cascade.z, cascade.y, viewDepth ) );
				}
			}
			return shadow;
		}
	#endif
	#if NUM_POINT_LIGHT_SHADOWS > 0
	#if defined( SHADOWMAP_TYPE_PCF )
	float getPointShadow( samplerCubeShadow shadowMap, vec2 shadowMapSize, float shadowIntensity, float shadowBias, float shadowRadius, vec4 shadowCoord, float shadowCameraNear, float shadowCameraFar ) {
		float shadow = 1.0;
		vec3 lightToPosition = shadowCoord.xyz;
		vec3 bd3D = normalize( lightToPosition );
		vec3 absVec = abs( lightToPosition );
		float viewSpaceZ = max( max( absVec.x, absVec.y ), absVec.z );
		if ( viewSpaceZ - shadowCameraFar <= 0.0 && viewSpaceZ - shadowCameraNear >= 0.0 ) {
			float lightDistance = length( lightToPosition );
			float depthRange = shadowCameraFar - shadowCameraNear;
			#ifdef USE_REVERSED_DEPTH_BUFFER
				float depthA = - shadowCameraNear / depthRange - shadowBias;
				float depthB = shadowCameraNear * shadowCameraFar / ( depthRange * lightDistance );
			#else
				float depthA = shadowCameraFar / depthRange + shadowBias;
				float depthB = - shadowCameraNear * shadowCameraFar / ( depthRange * lightDistance );
			#endif
			float texelSize = shadowRadius / shadowMapSize.x;
			vec3 absDir = abs( bd3D );
			vec3 tangent = absDir.x > absDir.z ? vec3( 0.0, 1.0, 0.0 ) : vec3( 1.0, 0.0, 0.0 );
			tangent = normalize( cross( bd3D, tangent ) );
			vec3 bitangent = cross( bd3D, tangent );
			float phi = interleavedGradientNoise( gl_FragCoord.xy ) * PI2;
			shadow = 0.0;
			for ( int i = 0; i < 5; i ++ ) {
				vec2 offset = vogelDiskSample( i, 5, phi );
				vec3 direction = bd3D + ( tangent * offset.x + bitangent * offset.y ) * texelSize;
				vec3 absDirection = abs( direction );
				float dp = depthA + depthB / max( max( absDirection.x, absDirection.y ), absDirection.z );
				shadow += texture( shadowMap, vec4( direction, dp ) );
			}
			shadow *= 0.2;
		}
		return mix( 1.0, shadow, shadowIntensity );
	}
	#elif defined( SHADOWMAP_TYPE_BASIC )
	float getPointShadow( samplerCube shadowMap, vec2 shadowMapSize, float shadowIntensity, float shadowBias, float shadowRadius, vec4 shadowCoord, float shadowCameraNear, float shadowCameraFar ) {
		float shadow = 1.0;
		vec3 lightToPosition = shadowCoord.xyz;
		vec3 absVec = abs( lightToPosition );
		float viewSpaceZ = max( max( absVec.x, absVec.y ), absVec.z );
		if ( viewSpaceZ - shadowCameraFar <= 0.0 && viewSpaceZ - shadowCameraNear >= 0.0 ) {
			float dp = ( shadowCameraFar * ( viewSpaceZ - shadowCameraNear ) ) / ( viewSpaceZ * ( shadowCameraFar - shadowCameraNear ) );
			dp += shadowBias;
			vec3 bd3D = normalize( lightToPosition );
			float depth = textureCube( shadowMap, bd3D ).r;
			#ifdef USE_REVERSED_DEPTH_BUFFER
				depth = 1.0 - depth;
			#endif
			shadow = step( dp, depth );
		}
		return mix( 1.0, shadow, shadowIntensity );
	}
	#endif
	#endif
#endif`,il=`#if NUM_SPOT_LIGHT_COORDS > 0
	uniform mat4 spotLightMatrix[ NUM_SPOT_LIGHT_COORDS ];
	varying vec4 vSpotLightCoord[ NUM_SPOT_LIGHT_COORDS ];
#endif
#ifdef USE_SHADOWMAP
	#if NUM_SUN_LIGHT_SHADOWS > 0
		varying vec4 vSunShadowWorldPosition;
		varying vec3 vSunShadowWorldNormal;
	#endif
	#if NUM_DIR_LIGHT_SHADOWS > 0
		uniform mat4 directionalShadowMatrix[ NUM_DIR_LIGHT_SHADOWS ];
		varying vec4 vDirectionalShadowCoord[ NUM_DIR_LIGHT_SHADOWS ];
		struct DirectionalLightShadow {
			float shadowIntensity;
			float shadowBias;
			float shadowNormalBias;
			float shadowRadius;
			vec2 shadowMapSize;
		};
		uniform DirectionalLightShadow directionalLightShadows[ NUM_DIR_LIGHT_SHADOWS ];
	#endif
	#if NUM_SPOT_LIGHT_SHADOWS > 0
		struct SpotLightShadow {
			float shadowIntensity;
			float shadowBias;
			float shadowNormalBias;
			float shadowRadius;
			vec2 shadowMapSize;
		};
		uniform SpotLightShadow spotLightShadows[ NUM_SPOT_LIGHT_SHADOWS ];
	#endif
	#if NUM_POINT_LIGHT_SHADOWS > 0
		uniform mat4 pointShadowMatrix[ NUM_POINT_LIGHT_SHADOWS ];
		varying vec4 vPointShadowCoord[ NUM_POINT_LIGHT_SHADOWS ];
		struct PointLightShadow {
			float shadowIntensity;
			float shadowBias;
			float shadowNormalBias;
			float shadowRadius;
			vec2 shadowMapSize;
			float shadowCameraNear;
			float shadowCameraFar;
		};
		uniform PointLightShadow pointLightShadows[ NUM_POINT_LIGHT_SHADOWS ];
	#endif
#endif`,rl=`#if ( defined( USE_SHADOWMAP ) && ( NUM_DIR_LIGHT_SHADOWS > 0 || NUM_SUN_LIGHT_SHADOWS > 0 || NUM_POINT_LIGHT_SHADOWS > 0 ) ) || ( NUM_SPOT_LIGHT_COORDS > 0 )
	#ifdef HAS_NORMAL
		vec3 shadowWorldNormal = transformNormalByInverseViewMatrix( transformedNormal, viewMatrix );
	#else
		vec3 shadowWorldNormal = vec3( 0.0 );
	#endif
	vec4 shadowWorldPosition;
#endif
#if defined( USE_SHADOWMAP )
	#if NUM_SUN_LIGHT_SHADOWS > 0
		vSunShadowWorldPosition = vec4( worldPosition.xyz, - mvPosition.z );
		vSunShadowWorldNormal = shadowWorldNormal;
	#endif
	#if NUM_DIR_LIGHT_SHADOWS > 0
		#pragma unroll_loop_start
		for ( int i = 0; i < NUM_DIR_LIGHT_SHADOWS; i ++ ) {
			shadowWorldPosition = worldPosition + vec4( shadowWorldNormal * directionalLightShadows[ i ].shadowNormalBias, 0 );
			vDirectionalShadowCoord[ i ] = directionalShadowMatrix[ i ] * shadowWorldPosition;
		}
		#pragma unroll_loop_end
	#endif
	#if NUM_POINT_LIGHT_SHADOWS > 0
		#pragma unroll_loop_start
		for ( int i = 0; i < NUM_POINT_LIGHT_SHADOWS; i ++ ) {
			shadowWorldPosition = worldPosition + vec4( shadowWorldNormal * pointLightShadows[ i ].shadowNormalBias, 0 );
			vPointShadowCoord[ i ] = pointShadowMatrix[ i ] * shadowWorldPosition;
		}
		#pragma unroll_loop_end
	#endif
#endif
#if NUM_SPOT_LIGHT_COORDS > 0
	#pragma unroll_loop_start
	for ( int i = 0; i < NUM_SPOT_LIGHT_COORDS; i ++ ) {
		shadowWorldPosition = worldPosition;
		#if ( defined( USE_SHADOWMAP ) && UNROLLED_LOOP_INDEX < NUM_SPOT_LIGHT_SHADOWS )
			shadowWorldPosition.xyz += shadowWorldNormal * spotLightShadows[ i ].shadowNormalBias;
		#endif
		vSpotLightCoord[ i ] = spotLightMatrix[ i ] * shadowWorldPosition;
	}
	#pragma unroll_loop_end
#endif`,al=`float getShadowMask() {
	float shadow = 1.0;
	#ifdef USE_SHADOWMAP
	#if NUM_SUN_LIGHT_SHADOWS > 0
	SunLightShadow sunLight;
	#pragma unroll_loop_start
	for ( int i = 0; i < NUM_SUN_LIGHT_SHADOWS; i ++ ) {
		sunLight = sunLightShadows[ i ];
		shadow *= receiveShadow ? getSunShadow( sunShadowMap[ i ], sunLight, UNROLLED_LOOP_INDEX ) : 1.0;
	}
	#pragma unroll_loop_end
	#endif
	#if NUM_DIR_LIGHT_SHADOWS > 0
	DirectionalLightShadow directionalLight;
	#pragma unroll_loop_start
	for ( int i = 0; i < NUM_DIR_LIGHT_SHADOWS; i ++ ) {
		directionalLight = directionalLightShadows[ i ];
		shadow *= receiveShadow ? getShadow( directionalShadowMap[ i ], directionalLight.shadowMapSize, directionalLight.shadowIntensity, directionalLight.shadowBias, directionalLight.shadowRadius, vDirectionalShadowCoord[ i ] ) : 1.0;
	}
	#pragma unroll_loop_end
	#endif
	#if NUM_SPOT_LIGHT_SHADOWS > 0
	SpotLightShadow spotLight;
	#pragma unroll_loop_start
	for ( int i = 0; i < NUM_SPOT_LIGHT_SHADOWS; i ++ ) {
		spotLight = spotLightShadows[ i ];
		shadow *= receiveShadow ? getShadow( spotShadowMap[ i ], spotLight.shadowMapSize, spotLight.shadowIntensity, spotLight.shadowBias, spotLight.shadowRadius, vSpotLightCoord[ i ] ) : 1.0;
	}
	#pragma unroll_loop_end
	#endif
	#if NUM_POINT_LIGHT_SHADOWS > 0 && ( defined( SHADOWMAP_TYPE_PCF ) || defined( SHADOWMAP_TYPE_BASIC ) )
	PointLightShadow pointLight;
	#pragma unroll_loop_start
	for ( int i = 0; i < NUM_POINT_LIGHT_SHADOWS; i ++ ) {
		pointLight = pointLightShadows[ i ];
		shadow *= receiveShadow ? getPointShadow( pointShadowMap[ i ], pointLight.shadowMapSize, pointLight.shadowIntensity, pointLight.shadowBias, pointLight.shadowRadius, vPointShadowCoord[ i ], pointLight.shadowCameraNear, pointLight.shadowCameraFar ) : 1.0;
	}
	#pragma unroll_loop_end
	#endif
	#endif
	return shadow;
}`,ol=`#ifdef USE_SKINNING
	mat4 boneMatX = getBoneMatrix( skinIndex.x );
	mat4 boneMatY = getBoneMatrix( skinIndex.y );
	mat4 boneMatZ = getBoneMatrix( skinIndex.z );
	mat4 boneMatW = getBoneMatrix( skinIndex.w );
#endif`,sl=`#ifdef USE_SKINNING
	uniform mat4 bindMatrix;
	uniform mat4 bindMatrixInverse;
	uniform highp sampler2D boneTexture;
	mat4 getBoneMatrix( const in float i ) {
		int size = textureSize( boneTexture, 0 ).x;
		int j = int( i ) * 4;
		int x = j % size;
		int y = j / size;
		vec4 v1 = texelFetch( boneTexture, ivec2( x, y ), 0 );
		vec4 v2 = texelFetch( boneTexture, ivec2( x + 1, y ), 0 );
		vec4 v3 = texelFetch( boneTexture, ivec2( x + 2, y ), 0 );
		vec4 v4 = texelFetch( boneTexture, ivec2( x + 3, y ), 0 );
		return mat4( v1, v2, v3, v4 );
	}
#endif`,ll=`#ifdef USE_SKINNING
	vec4 skinVertex = bindMatrix * vec4( transformed, 1.0 );
	vec4 skinned = vec4( 0.0 );
	skinned += boneMatX * skinVertex * skinWeight.x;
	skinned += boneMatY * skinVertex * skinWeight.y;
	skinned += boneMatZ * skinVertex * skinWeight.z;
	skinned += boneMatW * skinVertex * skinWeight.w;
	transformed = ( bindMatrixInverse * skinned ).xyz;
#endif`,cl=`#ifdef USE_SKINNING
	mat4 skinMatrix = mat4( 0.0 );
	skinMatrix += skinWeight.x * boneMatX;
	skinMatrix += skinWeight.y * boneMatY;
	skinMatrix += skinWeight.z * boneMatZ;
	skinMatrix += skinWeight.w * boneMatW;
	skinMatrix = bindMatrixInverse * skinMatrix * bindMatrix;
	objectNormal = vec4( skinMatrix * vec4( objectNormal, 0.0 ) ).xyz;
	#ifdef USE_TANGENT
		objectTangent = vec4( skinMatrix * vec4( objectTangent, 0.0 ) ).xyz;
	#endif
#endif`,fl=`float specularStrength;
#ifdef USE_SPECULARMAP
	vec4 texelSpecular = texture2D( specularMap, vSpecularMapUv );
	specularStrength = texelSpecular.r;
#else
	specularStrength = 1.0;
#endif`,dl=`#ifdef USE_SPECULARMAP
	uniform sampler2D specularMap;
#endif`,ul=`#if defined( TONE_MAPPING )
	gl_FragColor.rgb = toneMapping( gl_FragColor.rgb );
#endif`,pl=`#ifndef saturate
#define saturate( a ) clamp( a, 0.0, 1.0 )
#endif
uniform float toneMappingExposure;
vec3 LinearToneMapping( vec3 color ) {
	return saturate( toneMappingExposure * color );
}
vec3 ReinhardToneMapping( vec3 color ) {
	color *= toneMappingExposure;
	return saturate( color / ( vec3( 1.0 ) + color ) );
}
vec3 CineonToneMapping( vec3 color ) {
	color *= toneMappingExposure;
	color = max( vec3( 0.0 ), color - 0.004 );
	return pow( ( color * ( 6.2 * color + 0.5 ) ) / ( color * ( 6.2 * color + 1.7 ) + 0.06 ), vec3( 2.2 ) );
}
vec3 RRTAndODTFit( vec3 v ) {
	vec3 a = v * ( v + 0.0245786 ) - 0.000090537;
	vec3 b = v * ( 0.983729 * v + 0.4329510 ) + 0.238081;
	return a / b;
}
vec3 ACESFilmicToneMapping( vec3 color ) {
	const mat3 ACESInputMat = mat3(
		vec3( 0.59719, 0.07600, 0.02840 ),		vec3( 0.35458, 0.90834, 0.13383 ),
		vec3( 0.04823, 0.01566, 0.83777 )
	);
	const mat3 ACESOutputMat = mat3(
		vec3(  1.60475, -0.10208, -0.00327 ),		vec3( -0.53108,  1.10813, -0.07276 ),
		vec3( -0.07367, -0.00605,  1.07602 )
	);
	color *= toneMappingExposure / 0.6;
	color = ACESInputMat * color;
	color = RRTAndODTFit( color );
	color = ACESOutputMat * color;
	return saturate( color );
}
const mat3 LINEAR_REC2020_TO_LINEAR_SRGB = mat3(
	vec3( 1.6605, - 0.1246, - 0.0182 ),
	vec3( - 0.5876, 1.1329, - 0.1006 ),
	vec3( - 0.0728, - 0.0083, 1.1187 )
);
const mat3 LINEAR_SRGB_TO_LINEAR_REC2020 = mat3(
	vec3( 0.6274, 0.0691, 0.0164 ),
	vec3( 0.3293, 0.9195, 0.0880 ),
	vec3( 0.0433, 0.0113, 0.8956 )
);
vec3 agxDefaultContrastApprox( vec3 x ) {
	vec3 x2 = x * x;
	vec3 x4 = x2 * x2;
	return + 15.5 * x4 * x2
		- 40.14 * x4 * x
		+ 31.96 * x4
		- 6.868 * x2 * x
		+ 0.4298 * x2
		+ 0.1191 * x
		- 0.00232;
}
vec3 AgXToneMapping( vec3 color ) {
	const mat3 AgXInsetMatrix = mat3(
		vec3( 0.856627153315983, 0.137318972929847, 0.11189821299995 ),
		vec3( 0.0951212405381588, 0.761241990602591, 0.0767994186031903 ),
		vec3( 0.0482516061458583, 0.101439036467562, 0.811302368396859 )
	);
	const mat3 AgXOutsetMatrix = mat3(
		vec3( 1.1271005818144368, - 0.1413297634984383, - 0.14132976349843826 ),
		vec3( - 0.11060664309660323, 1.157823702216272, - 0.11060664309660294 ),
		vec3( - 0.016493938717834573, - 0.016493938717834257, 1.2519364065950405 )
	);
	const float AgxMinEv = - 12.47393;	const float AgxMaxEv = 4.026069;
	color *= toneMappingExposure;
	color = LINEAR_SRGB_TO_LINEAR_REC2020 * color;
	color = AgXInsetMatrix * color;
	color = max( color, 1e-10 );	color = log2( color );
	color = ( color - AgxMinEv ) / ( AgxMaxEv - AgxMinEv );
	color = clamp( color, 0.0, 1.0 );
	color = agxDefaultContrastApprox( color );
	color = AgXOutsetMatrix * color;
	color = pow( max( vec3( 0.0 ), color ), vec3( 2.2 ) );
	color = LINEAR_REC2020_TO_LINEAR_SRGB * color;
	color = clamp( color, 0.0, 1.0 );
	return color;
}
vec3 NeutralToneMapping( vec3 color ) {
	const float StartCompression = 0.8 - 0.04;
	const float Desaturation = 0.15;
	color *= toneMappingExposure;
	float x = min( color.r, min( color.g, color.b ) );
	float offset = x < 0.08 ? x - 6.25 * x * x : 0.04;
	color -= offset;
	float peak = max( color.r, max( color.g, color.b ) );
	if ( peak < StartCompression ) return color;
	float d = 1. - StartCompression;
	float newPeak = 1. - d * d / ( peak + d - StartCompression );
	color *= newPeak / peak;
	float g = 1. - 1. / ( Desaturation * ( peak - newPeak ) + 1. );
	return mix( color, vec3( newPeak ), g );
}
vec3 CustomToneMapping( vec3 color ) { return color; }`,hl=`#ifdef USE_TRANSMISSION
	material.transmission = transmission;
	material.transmissionAlpha = 1.0;
	material.thickness = thickness;
	material.attenuationDistance = attenuationDistance;
	material.attenuationColor = attenuationColor;
	#ifdef USE_TRANSMISSIONMAP
		material.transmission *= texture2D( transmissionMap, vTransmissionMapUv ).r;
	#endif
	#ifdef USE_THICKNESSMAP
		material.thickness *= texture2D( thicknessMap, vThicknessMapUv ).g;
	#endif
	vec3 pos = vWorldPosition;
	vec3 v = normalize( cameraPosition - pos );
	vec3 n = transformNormalByInverseViewMatrix( normal, viewMatrix );
	vec4 transmitted = getIBLVolumeRefraction(
		n, v, material.roughness, material.diffuseContribution, material.specularColorBlended, material.specularF90,
		pos, modelMatrix, viewMatrix, projectionMatrix, material.dispersion, material.ior, material.thickness,
		material.attenuationColor, material.attenuationDistance );
	material.transmissionAlpha = mix( material.transmissionAlpha, transmitted.a, material.transmission );
	totalDiffuse = mix( totalDiffuse, transmitted.rgb, material.transmission );
#endif`,ml=`#ifdef USE_TRANSMISSION
	uniform float transmission;
	uniform float thickness;
	uniform float attenuationDistance;
	uniform vec3 attenuationColor;
	#ifdef USE_TRANSMISSIONMAP
		uniform sampler2D transmissionMap;
	#endif
	#ifdef USE_THICKNESSMAP
		uniform sampler2D thicknessMap;
	#endif
	uniform vec2 transmissionSamplerSize;
	uniform sampler2D transmissionSamplerMap;
	uniform mat4 modelMatrix;
	uniform mat4 projectionMatrix;
	varying vec3 vWorldPosition;
	float w0( float a ) {
		return ( 1.0 / 6.0 ) * ( a * ( a * ( - a + 3.0 ) - 3.0 ) + 1.0 );
	}
	float w1( float a ) {
		return ( 1.0 / 6.0 ) * ( a *  a * ( 3.0 * a - 6.0 ) + 4.0 );
	}
	float w2( float a ){
		return ( 1.0 / 6.0 ) * ( a * ( a * ( - 3.0 * a + 3.0 ) + 3.0 ) + 1.0 );
	}
	float w3( float a ) {
		return ( 1.0 / 6.0 ) * ( a * a * a );
	}
	float g0( float a ) {
		return w0( a ) + w1( a );
	}
	float g1( float a ) {
		return w2( a ) + w3( a );
	}
	float h0( float a ) {
		return - 1.0 + w1( a ) / ( w0( a ) + w1( a ) );
	}
	float h1( float a ) {
		return 1.0 + w3( a ) / ( w2( a ) + w3( a ) );
	}
	vec4 bicubic( sampler2D tex, vec2 uv, vec4 texelSize, float lod ) {
		uv = uv * texelSize.zw + 0.5;
		vec2 iuv = floor( uv );
		vec2 fuv = fract( uv );
		float g0x = g0( fuv.x );
		float g1x = g1( fuv.x );
		float h0x = h0( fuv.x );
		float h1x = h1( fuv.x );
		float h0y = h0( fuv.y );
		float h1y = h1( fuv.y );
		vec2 p0 = ( vec2( iuv.x + h0x, iuv.y + h0y ) - 0.5 ) * texelSize.xy;
		vec2 p1 = ( vec2( iuv.x + h1x, iuv.y + h0y ) - 0.5 ) * texelSize.xy;
		vec2 p2 = ( vec2( iuv.x + h0x, iuv.y + h1y ) - 0.5 ) * texelSize.xy;
		vec2 p3 = ( vec2( iuv.x + h1x, iuv.y + h1y ) - 0.5 ) * texelSize.xy;
		return g0( fuv.y ) * ( g0x * textureLod( tex, p0, lod ) + g1x * textureLod( tex, p1, lod ) ) +
			g1( fuv.y ) * ( g0x * textureLod( tex, p2, lod ) + g1x * textureLod( tex, p3, lod ) );
	}
	vec4 textureBicubic( sampler2D sampler, vec2 uv, float lod ) {
		vec2 fLodSize = vec2( textureSize( sampler, int( lod ) ) );
		vec2 cLodSize = vec2( textureSize( sampler, int( lod + 1.0 ) ) );
		vec2 fLodSizeInv = 1.0 / fLodSize;
		vec2 cLodSizeInv = 1.0 / cLodSize;
		vec4 fSample = bicubic( sampler, uv, vec4( fLodSizeInv, fLodSize ), floor( lod ) );
		vec4 cSample = bicubic( sampler, uv, vec4( cLodSizeInv, cLodSize ), ceil( lod ) );
		return mix( fSample, cSample, fract( lod ) );
	}
	vec3 getVolumeTransmissionRay( const in vec3 n, const in vec3 v, const in float thickness, const in float ior, const in mat4 modelMatrix ) {
		vec3 refractionVector = refract( - v, normalize( n ), 1.0 / ior );
		vec3 modelScale;
		modelScale.x = length( vec3( modelMatrix[ 0 ].xyz ) );
		modelScale.y = length( vec3( modelMatrix[ 1 ].xyz ) );
		modelScale.z = length( vec3( modelMatrix[ 2 ].xyz ) );
		return normalize( refractionVector ) * thickness * modelScale;
	}
	float applyIorToRoughness( const in float roughness, const in float ior ) {
		return roughness * clamp( ior * 2.0 - 2.0, 0.0, 1.0 );
	}
	vec4 getTransmissionSample( const in vec2 fragCoord, const in float roughness, const in float ior ) {
		float lod = log2( transmissionSamplerSize.x ) * applyIorToRoughness( roughness, ior );
		return textureBicubic( transmissionSamplerMap, fragCoord.xy, lod );
	}
	vec3 volumeAttenuation( const in float transmissionDistance, const in vec3 attenuationColor, const in float attenuationDistance ) {
		if ( isinf( attenuationDistance ) ) {
			return vec3( 1.0 );
		} else {
			vec3 attenuationCoefficient = -log( attenuationColor ) / attenuationDistance;
			vec3 transmittance = exp( - attenuationCoefficient * transmissionDistance );			return transmittance;
		}
	}
	vec4 getIBLVolumeRefraction( const in vec3 n, const in vec3 v, const in float roughness, const in vec3 diffuseColor,
		const in vec3 specularColor, const in float specularF90, const in vec3 position, const in mat4 modelMatrix,
		const in mat4 viewMatrix, const in mat4 projMatrix, const in float dispersion, const in float ior, const in float thickness,
		const in vec3 attenuationColor, const in float attenuationDistance ) {
		vec4 transmittedLight;
		vec3 transmittance;
		#ifdef USE_DISPERSION
			float halfSpread = ( ior - 1.0 ) * 0.025 * dispersion;
			vec3 iors = vec3( ior - halfSpread, ior, ior + halfSpread );
			for ( int i = 0; i < 3; i ++ ) {
				vec3 transmissionRay = getVolumeTransmissionRay( n, v, thickness, iors[ i ], modelMatrix );
				vec3 refractedRayExit = position + transmissionRay;
				vec4 ndcPos = projMatrix * viewMatrix * vec4( refractedRayExit, 1.0 );
				vec2 refractionCoords = ndcPos.xy / ndcPos.w;
				refractionCoords += 1.0;
				refractionCoords /= 2.0;
				vec4 transmissionSample = getTransmissionSample( refractionCoords, roughness, iors[ i ] );
				transmittedLight[ i ] = transmissionSample[ i ];
				transmittedLight.a += transmissionSample.a;
				transmittance[ i ] = diffuseColor[ i ] * volumeAttenuation( length( transmissionRay ), attenuationColor, attenuationDistance )[ i ];
			}
			transmittedLight.a /= 3.0;
		#else
			vec3 transmissionRay = getVolumeTransmissionRay( n, v, thickness, ior, modelMatrix );
			vec3 refractedRayExit = position + transmissionRay;
			vec4 ndcPos = projMatrix * viewMatrix * vec4( refractedRayExit, 1.0 );
			vec2 refractionCoords = ndcPos.xy / ndcPos.w;
			refractionCoords += 1.0;
			refractionCoords /= 2.0;
			transmittedLight = getTransmissionSample( refractionCoords, roughness, ior );
			transmittance = diffuseColor * volumeAttenuation( length( transmissionRay ), attenuationColor, attenuationDistance );
		#endif
		vec3 attenuatedColor = transmittance * transmittedLight.rgb;
		vec3 F = EnvironmentBRDF( n, v, specularColor, specularF90, roughness );
		float transmittanceFactor = ( transmittance.r + transmittance.g + transmittance.b ) / 3.0;
		return vec4( ( 1.0 - F ) * attenuatedColor, 1.0 - ( 1.0 - transmittedLight.a ) * transmittanceFactor );
	}
#endif`,_l=`#if defined( USE_UV ) || defined( USE_ANISOTROPY )
	varying vec2 vUv;
#endif
#ifdef USE_MAP
	varying vec2 vMapUv;
#endif
#ifdef USE_ALPHAMAP
	varying vec2 vAlphaMapUv;
#endif
#ifdef USE_LIGHTMAP
	varying vec2 vLightMapUv;
#endif
#ifdef USE_AOMAP
	varying vec2 vAoMapUv;
#endif
#ifdef USE_BUMPMAP
	varying vec2 vBumpMapUv;
#endif
#ifdef USE_NORMALMAP
	varying vec2 vNormalMapUv;
#endif
#ifdef USE_EMISSIVEMAP
	varying vec2 vEmissiveMapUv;
#endif
#ifdef USE_METALNESSMAP
	varying vec2 vMetalnessMapUv;
#endif
#ifdef USE_ROUGHNESSMAP
	varying vec2 vRoughnessMapUv;
#endif
#ifdef USE_DIFFUSE_ROUGHNESSMAP
	varying vec2 vDiffuseRoughnessMapUv;
#endif
#ifdef USE_ANISOTROPYMAP
	varying vec2 vAnisotropyMapUv;
#endif
#ifdef USE_CLEARCOATMAP
	varying vec2 vClearcoatMapUv;
#endif
#ifdef USE_CLEARCOAT_NORMALMAP
	varying vec2 vClearcoatNormalMapUv;
#endif
#ifdef USE_CLEARCOAT_ROUGHNESSMAP
	varying vec2 vClearcoatRoughnessMapUv;
#endif
#ifdef USE_IRIDESCENCEMAP
	varying vec2 vIridescenceMapUv;
#endif
#ifdef USE_IRIDESCENCE_THICKNESSMAP
	varying vec2 vIridescenceThicknessMapUv;
#endif
#ifdef USE_SHEEN_COLORMAP
	varying vec2 vSheenColorMapUv;
#endif
#ifdef USE_SHEEN_ROUGHNESSMAP
	varying vec2 vSheenRoughnessMapUv;
#endif
#ifdef USE_SPECULARMAP
	varying vec2 vSpecularMapUv;
#endif
#ifdef USE_SPECULAR_COLORMAP
	varying vec2 vSpecularColorMapUv;
#endif
#ifdef USE_SPECULAR_INTENSITYMAP
	varying vec2 vSpecularIntensityMapUv;
#endif
#ifdef USE_TRANSMISSIONMAP
	uniform mat3 transmissionMapTransform;
	varying vec2 vTransmissionMapUv;
#endif
#ifdef USE_THICKNESSMAP
	uniform mat3 thicknessMapTransform;
	varying vec2 vThicknessMapUv;
#endif`,gl=`#if defined( USE_UV ) || defined( USE_ANISOTROPY )
	varying vec2 vUv;
#endif
#ifdef USE_MAP
	uniform mat3 mapTransform;
	varying vec2 vMapUv;
#endif
#ifdef USE_ALPHAMAP
	uniform mat3 alphaMapTransform;
	varying vec2 vAlphaMapUv;
#endif
#ifdef USE_LIGHTMAP
	uniform mat3 lightMapTransform;
	varying vec2 vLightMapUv;
#endif
#ifdef USE_AOMAP
	uniform mat3 aoMapTransform;
	varying vec2 vAoMapUv;
#endif
#ifdef USE_BUMPMAP
	uniform mat3 bumpMapTransform;
	varying vec2 vBumpMapUv;
#endif
#ifdef USE_NORMALMAP
	uniform mat3 normalMapTransform;
	varying vec2 vNormalMapUv;
#endif
#ifdef USE_DISPLACEMENTMAP
	uniform mat3 displacementMapTransform;
	varying vec2 vDisplacementMapUv;
#endif
#ifdef USE_EMISSIVEMAP
	uniform mat3 emissiveMapTransform;
	varying vec2 vEmissiveMapUv;
#endif
#ifdef USE_METALNESSMAP
	uniform mat3 metalnessMapTransform;
	varying vec2 vMetalnessMapUv;
#endif
#ifdef USE_ROUGHNESSMAP
	uniform mat3 roughnessMapTransform;
	varying vec2 vRoughnessMapUv;
#endif
#ifdef USE_DIFFUSE_ROUGHNESSMAP
	uniform mat3 diffuseRoughnessMapTransform;
	varying vec2 vDiffuseRoughnessMapUv;
#endif
#ifdef USE_ANISOTROPYMAP
	uniform mat3 anisotropyMapTransform;
	varying vec2 vAnisotropyMapUv;
#endif
#ifdef USE_CLEARCOATMAP
	uniform mat3 clearcoatMapTransform;
	varying vec2 vClearcoatMapUv;
#endif
#ifdef USE_CLEARCOAT_NORMALMAP
	uniform mat3 clearcoatNormalMapTransform;
	varying vec2 vClearcoatNormalMapUv;
#endif
#ifdef USE_CLEARCOAT_ROUGHNESSMAP
	uniform mat3 clearcoatRoughnessMapTransform;
	varying vec2 vClearcoatRoughnessMapUv;
#endif
#ifdef USE_SHEEN_COLORMAP
	uniform mat3 sheenColorMapTransform;
	varying vec2 vSheenColorMapUv;
#endif
#ifdef USE_SHEEN_ROUGHNESSMAP
	uniform mat3 sheenRoughnessMapTransform;
	varying vec2 vSheenRoughnessMapUv;
#endif
#ifdef USE_IRIDESCENCEMAP
	uniform mat3 iridescenceMapTransform;
	varying vec2 vIridescenceMapUv;
#endif
#ifdef USE_IRIDESCENCE_THICKNESSMAP
	uniform mat3 iridescenceThicknessMapTransform;
	varying vec2 vIridescenceThicknessMapUv;
#endif
#ifdef USE_SPECULARMAP
	uniform mat3 specularMapTransform;
	varying vec2 vSpecularMapUv;
#endif
#ifdef USE_SPECULAR_COLORMAP
	uniform mat3 specularColorMapTransform;
	varying vec2 vSpecularColorMapUv;
#endif
#ifdef USE_SPECULAR_INTENSITYMAP
	uniform mat3 specularIntensityMapTransform;
	varying vec2 vSpecularIntensityMapUv;
#endif
#ifdef USE_TRANSMISSIONMAP
	uniform mat3 transmissionMapTransform;
	varying vec2 vTransmissionMapUv;
#endif
#ifdef USE_THICKNESSMAP
	uniform mat3 thicknessMapTransform;
	varying vec2 vThicknessMapUv;
#endif`,vl=`#if defined( USE_UV ) || defined( USE_ANISOTROPY )
	vUv = vec3( uv, 1 ).xy;
#endif
#ifdef USE_MAP
	vMapUv = ( mapTransform * vec3( MAP_UV, 1 ) ).xy;
#endif
#ifdef USE_ALPHAMAP
	vAlphaMapUv = ( alphaMapTransform * vec3( ALPHAMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_LIGHTMAP
	vLightMapUv = ( lightMapTransform * vec3( LIGHTMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_AOMAP
	vAoMapUv = ( aoMapTransform * vec3( AOMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_BUMPMAP
	vBumpMapUv = ( bumpMapTransform * vec3( BUMPMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_NORMALMAP
	vNormalMapUv = ( normalMapTransform * vec3( NORMALMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_DISPLACEMENTMAP
	vDisplacementMapUv = ( displacementMapTransform * vec3( DISPLACEMENTMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_EMISSIVEMAP
	vEmissiveMapUv = ( emissiveMapTransform * vec3( EMISSIVEMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_METALNESSMAP
	vMetalnessMapUv = ( metalnessMapTransform * vec3( METALNESSMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_ROUGHNESSMAP
	vRoughnessMapUv = ( roughnessMapTransform * vec3( ROUGHNESSMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_DIFFUSE_ROUGHNESSMAP
	vDiffuseRoughnessMapUv = ( diffuseRoughnessMapTransform * vec3( DIFFUSE_ROUGHNESSMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_ANISOTROPYMAP
	vAnisotropyMapUv = ( anisotropyMapTransform * vec3( ANISOTROPYMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_CLEARCOATMAP
	vClearcoatMapUv = ( clearcoatMapTransform * vec3( CLEARCOATMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_CLEARCOAT_NORMALMAP
	vClearcoatNormalMapUv = ( clearcoatNormalMapTransform * vec3( CLEARCOAT_NORMALMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_CLEARCOAT_ROUGHNESSMAP
	vClearcoatRoughnessMapUv = ( clearcoatRoughnessMapTransform * vec3( CLEARCOAT_ROUGHNESSMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_IRIDESCENCEMAP
	vIridescenceMapUv = ( iridescenceMapTransform * vec3( IRIDESCENCEMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_IRIDESCENCE_THICKNESSMAP
	vIridescenceThicknessMapUv = ( iridescenceThicknessMapTransform * vec3( IRIDESCENCE_THICKNESSMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_SHEEN_COLORMAP
	vSheenColorMapUv = ( sheenColorMapTransform * vec3( SHEEN_COLORMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_SHEEN_ROUGHNESSMAP
	vSheenRoughnessMapUv = ( sheenRoughnessMapTransform * vec3( SHEEN_ROUGHNESSMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_SPECULARMAP
	vSpecularMapUv = ( specularMapTransform * vec3( SPECULARMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_SPECULAR_COLORMAP
	vSpecularColorMapUv = ( specularColorMapTransform * vec3( SPECULAR_COLORMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_SPECULAR_INTENSITYMAP
	vSpecularIntensityMapUv = ( specularIntensityMapTransform * vec3( SPECULAR_INTENSITYMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_TRANSMISSIONMAP
	vTransmissionMapUv = ( transmissionMapTransform * vec3( TRANSMISSIONMAP_UV, 1 ) ).xy;
#endif
#ifdef USE_THICKNESSMAP
	vThicknessMapUv = ( thicknessMapTransform * vec3( THICKNESSMAP_UV, 1 ) ).xy;
#endif`,Sl=`#if defined( USE_ENVMAP ) || defined( DISTANCE ) || defined ( USE_SHADOWMAP ) || defined ( USE_TRANSMISSION ) || NUM_SPOT_LIGHT_COORDS > 0
	vec4 worldPosition = vec4( transformed, 1.0 );
	#ifdef USE_BATCHING
		worldPosition = batchingMatrix * worldPosition;
	#endif
	#ifdef USE_INSTANCING
		worldPosition = instanceMatrix * worldPosition;
	#endif
	worldPosition = modelMatrix * worldPosition;
#endif`;const El=`varying vec2 vUv;
uniform mat3 uvTransform;
void main() {
	vUv = ( uvTransform * vec3( uv, 1 ) ).xy;
	gl_Position = vec4( position.xy, 1.0, 1.0 );
}`,Ml=`uniform sampler2D t2D;
uniform float backgroundIntensity;
varying vec2 vUv;
void main() {
	vec4 texColor = texture2D( t2D, vUv );
	#ifdef DECODE_VIDEO_TEXTURE
		texColor = vec4( mix( pow( texColor.rgb * 0.9478672986 + vec3( 0.0521327014 ), vec3( 2.4 ) ), texColor.rgb * 0.0773993808, vec3( lessThanEqual( texColor.rgb, vec3( 0.04045 ) ) ) ), texColor.w );
	#endif
	texColor.rgb *= backgroundIntensity;
	gl_FragColor = texColor;
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
}`,xl=`varying vec3 vWorldDirection;
#include <common>
void main() {
	vWorldDirection = transformDirection( position, modelMatrix );
	#include <begin_vertex>
	#include <project_vertex>
	gl_Position.z = gl_Position.w;
}`,Tl=`uniform float backgroundBlurriness;
uniform float backgroundIntensity;
uniform mat3 backgroundRotation;
varying vec3 vWorldDirection;
#include <envmap_common_pars_fragment>
void main() {
	#ifdef ENVMAP_TYPE_PMREM
		vec4 texColor = textureLod( envMap, backgroundRotation * vWorldDirection, roughnessToMip( backgroundBlurriness ) );
	#else
		vec4 texColor = textureCube( envMap, backgroundRotation * vWorldDirection );
	#endif
	texColor.rgb *= backgroundIntensity;
	gl_FragColor = texColor;
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
}`,Al=`varying vec3 vWorldDirection;
#include <common>
void main() {
	vWorldDirection = transformDirection( position, modelMatrix );
	#include <begin_vertex>
	#include <project_vertex>
	gl_Position.z = gl_Position.w;
}`,Rl=`uniform samplerCube tCube;
uniform float tFlip;
uniform float opacity;
varying vec3 vWorldDirection;
void main() {
	vec4 texColor = textureCube( tCube, vec3( tFlip * vWorldDirection.x, vWorldDirection.yz ) );
	gl_FragColor = texColor;
	gl_FragColor.a *= opacity;
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
}`,Cl=`#include <common>
#include <batching_pars_vertex>
#include <uv_pars_vertex>
#include <displacementmap_pars_vertex>
#include <morphtarget_pars_vertex>
#include <skinning_pars_vertex>
#include <logdepthbuf_pars_vertex>
#include <clipping_planes_pars_vertex>
varying vec2 vHighPrecisionZW;
void main() {
	#include <uv_vertex>
	#include <batching_vertex>
	#include <skinbase_vertex>
	#include <morphinstance_vertex>
	#ifdef USE_DISPLACEMENTMAP
		#include <beginnormal_vertex>
		#include <morphnormal_vertex>
		#include <skinnormal_vertex>
	#endif
	#include <begin_vertex>
	#include <morphtarget_vertex>
	#include <skinning_vertex>
	#include <displacementmap_vertex>
	#include <project_vertex>
	#include <logdepthbuf_vertex>
	#include <clipping_planes_vertex>
	vHighPrecisionZW = gl_Position.zw;
}`,bl=`#if DEPTH_PACKING == 3200
	uniform float opacity;
#endif
#include <common>
#include <packing>
#include <uv_pars_fragment>
#include <map_pars_fragment>
#include <alphamap_pars_fragment>
#include <alphatest_pars_fragment>
#include <alphahash_pars_fragment>
#include <logdepthbuf_pars_fragment>
#include <clipping_planes_pars_fragment>
varying vec2 vHighPrecisionZW;
void main() {
	vec4 diffuseColor = vec4( 1.0 );
	#include <clipping_planes_fragment>
	#if DEPTH_PACKING == 3200
		diffuseColor.a = opacity;
	#endif
	#include <map_fragment>
	#include <alphamap_fragment>
	#include <alphatest_fragment>
	#include <alphahash_fragment>
	#include <logdepthbuf_fragment>
	#ifdef USE_REVERSED_DEPTH_BUFFER
		float fragCoordZ = vHighPrecisionZW[ 0 ] / vHighPrecisionZW[ 1 ];
	#else
		float fragCoordZ = 0.5 * vHighPrecisionZW[ 0 ] / vHighPrecisionZW[ 1 ] + 0.5;
	#endif
	#if DEPTH_PACKING == 3200
		gl_FragColor = vec4( vec3( 1.0 - fragCoordZ ), opacity );
	#elif DEPTH_PACKING == 3201
		gl_FragColor = packDepthToRGBA( fragCoordZ );
	#elif DEPTH_PACKING == 3202
		gl_FragColor = vec4( packDepthToRGB( fragCoordZ ), 1.0 );
	#elif DEPTH_PACKING == 3203
		gl_FragColor = vec4( packDepthToRG( fragCoordZ ), 0.0, 1.0 );
	#endif
}`,Pl=`#define DISTANCE
varying vec3 vWorldPosition;
#include <common>
#include <batching_pars_vertex>
#include <uv_pars_vertex>
#include <displacementmap_pars_vertex>
#include <morphtarget_pars_vertex>
#include <skinning_pars_vertex>
#include <clipping_planes_pars_vertex>
void main() {
	#include <uv_vertex>
	#include <batching_vertex>
	#include <skinbase_vertex>
	#include <morphinstance_vertex>
	#ifdef USE_DISPLACEMENTMAP
		#include <beginnormal_vertex>
		#include <morphnormal_vertex>
		#include <skinnormal_vertex>
	#endif
	#include <begin_vertex>
	#include <morphtarget_vertex>
	#include <skinning_vertex>
	#include <displacementmap_vertex>
	#include <project_vertex>
	#include <worldpos_vertex>
	#include <clipping_planes_vertex>
	vWorldPosition = worldPosition.xyz;
}`,Ll=`#define DISTANCE
uniform vec3 referencePosition;
uniform float nearDistance;
uniform float farDistance;
varying vec3 vWorldPosition;
#include <common>
#include <uv_pars_fragment>
#include <map_pars_fragment>
#include <alphamap_pars_fragment>
#include <alphatest_pars_fragment>
#include <alphahash_pars_fragment>
#include <clipping_planes_pars_fragment>
void main() {
	vec4 diffuseColor = vec4( 1.0 );
	#include <clipping_planes_fragment>
	#include <map_fragment>
	#include <alphamap_fragment>
	#include <alphatest_fragment>
	#include <alphahash_fragment>
	float dist = length( vWorldPosition - referencePosition );
	dist = ( dist - nearDistance ) / ( farDistance - nearDistance );
	dist = saturate( dist );
	gl_FragColor = vec4( dist, 0.0, 0.0, 1.0 );
}`,Dl=`varying vec3 vWorldDirection;
#include <common>
void main() {
	vWorldDirection = transformDirection( position, modelMatrix );
	#include <begin_vertex>
	#include <project_vertex>
}`,Ul=`uniform sampler2D tEquirect;
varying vec3 vWorldDirection;
#include <common>
void main() {
	vec3 direction = normalize( vWorldDirection );
	vec2 sampleUV = equirectUv( direction );
	gl_FragColor = texture2D( tEquirect, sampleUV );
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
}`,wl=`uniform float scale;
attribute float lineDistance;
varying float vLineDistance;
#include <common>
#include <uv_pars_vertex>
#include <color_pars_vertex>
#include <fog_pars_vertex>
#include <morphtarget_pars_vertex>
#include <logdepthbuf_pars_vertex>
#include <clipping_planes_pars_vertex>
void main() {
	vLineDistance = scale * lineDistance;
	#include <uv_vertex>
	#include <color_vertex>
	#include <morphinstance_vertex>
	#include <morphcolor_vertex>
	#include <begin_vertex>
	#include <morphtarget_vertex>
	#include <project_vertex>
	#include <logdepthbuf_vertex>
	#include <clipping_planes_vertex>
	#include <fog_vertex>
}`,Nl=`uniform vec3 diffuse;
uniform float opacity;
uniform float dashSize;
uniform float totalSize;
varying float vLineDistance;
#include <common>
#include <color_pars_fragment>
#include <uv_pars_fragment>
#include <map_pars_fragment>
#include <fog_pars_fragment>
#include <logdepthbuf_pars_fragment>
#include <clipping_planes_pars_fragment>
void main() {
	vec4 diffuseColor = vec4( diffuse, opacity );
	#include <clipping_planes_fragment>
	if ( mod( vLineDistance, totalSize ) > dashSize ) {
		discard;
	}
	vec3 outgoingLight = vec3( 0.0 );
	#include <logdepthbuf_fragment>
	#include <map_fragment>
	#include <color_fragment>
	outgoingLight = diffuseColor.rgb;
	#include <opaque_fragment>
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
	#include <fog_fragment>
	#include <premultiplied_alpha_fragment>
}`,Il=`#include <common>
#include <batching_pars_vertex>
#include <uv_pars_vertex>
#include <envmap_pars_vertex>
#include <color_pars_vertex>
#include <fog_pars_vertex>
#include <morphtarget_pars_vertex>
#include <skinning_pars_vertex>
#include <logdepthbuf_pars_vertex>
#include <clipping_planes_pars_vertex>
void main() {
	#include <uv_vertex>
	#include <color_vertex>
	#include <morphinstance_vertex>
	#include <morphcolor_vertex>
	#include <batching_vertex>
	#if defined ( USE_ENVMAP ) || defined ( USE_SKINNING )
		#include <beginnormal_vertex>
		#include <morphnormal_vertex>
		#include <skinbase_vertex>
		#include <skinnormal_vertex>
		#include <defaultnormal_vertex>
	#endif
	#include <begin_vertex>
	#include <morphtarget_vertex>
	#include <skinning_vertex>
	#include <project_vertex>
	#include <logdepthbuf_vertex>
	#include <clipping_planes_vertex>
	#include <worldpos_vertex>
	#include <envmap_vertex>
	#include <fog_vertex>
}`,yl=`uniform vec3 diffuse;
uniform float opacity;
#ifndef FLAT_SHADED
	varying vec3 vNormal;
#endif
#include <common>
#include <dithering_pars_fragment>
#include <color_pars_fragment>
#include <uv_pars_fragment>
#include <map_pars_fragment>
#include <alphamap_pars_fragment>
#include <alphatest_pars_fragment>
#include <alphahash_pars_fragment>
#include <aomap_pars_fragment>
#include <lightmap_pars_fragment>
#include <envmap_common_pars_fragment>
#include <envmap_pars_fragment>
#include <fog_pars_fragment>
#include <specularmap_pars_fragment>
#include <logdepthbuf_pars_fragment>
#include <clipping_planes_pars_fragment>
void main() {
	vec4 diffuseColor = vec4( diffuse, opacity );
	#include <clipping_planes_fragment>
	#include <logdepthbuf_fragment>
	#include <map_fragment>
	#include <color_fragment>
	#include <alphamap_fragment>
	#include <alphatest_fragment>
	#include <alphahash_fragment>
	#include <specularmap_fragment>
	ReflectedLight reflectedLight = ReflectedLight( vec3( 0.0 ), vec3( 0.0 ), vec3( 0.0 ), vec3( 0.0 ) );
	#ifdef USE_LIGHTMAP
		vec4 lightMapTexel = texture2D( lightMap, vLightMapUv );
		reflectedLight.indirectDiffuse += lightMapTexel.rgb * lightMapIntensity * RECIPROCAL_PI;
	#else
		reflectedLight.indirectDiffuse += vec3( 1.0 );
	#endif
	#include <aomap_fragment>
	reflectedLight.indirectDiffuse *= diffuseColor.rgb;
	vec3 outgoingLight = reflectedLight.indirectDiffuse;
	#include <envmap_fragment>
	#include <opaque_fragment>
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
	#include <fog_fragment>
	#include <premultiplied_alpha_fragment>
	#include <dithering_fragment>
}`,Fl=`#define LAMBERT
varying vec3 vViewPosition;
#include <common>
#include <batching_pars_vertex>
#include <uv_pars_vertex>
#include <displacementmap_pars_vertex>
#include <envmap_pars_vertex>
#include <color_pars_vertex>
#include <fog_pars_vertex>
#include <normal_pars_vertex>
#include <morphtarget_pars_vertex>
#include <skinning_pars_vertex>
#include <shadowmap_pars_vertex>
#include <logdepthbuf_pars_vertex>
#include <clipping_planes_pars_vertex>
void main() {
	#include <uv_vertex>
	#include <color_vertex>
	#include <morphinstance_vertex>
	#include <morphcolor_vertex>
	#include <batching_vertex>
	#include <beginnormal_vertex>
	#include <morphnormal_vertex>
	#include <skinbase_vertex>
	#include <skinnormal_vertex>
	#include <defaultnormal_vertex>
	#include <normal_vertex>
	#include <begin_vertex>
	#include <morphtarget_vertex>
	#include <skinning_vertex>
	#include <displacementmap_vertex>
	#include <project_vertex>
	#include <logdepthbuf_vertex>
	#include <clipping_planes_vertex>
	vViewPosition = - mvPosition.xyz;
	#include <worldpos_vertex>
	#include <envmap_vertex>
	#include <shadowmap_vertex>
	#include <fog_vertex>
}`,Ol=`#define LAMBERT
uniform vec3 diffuse;
uniform vec3 emissive;
uniform float opacity;
#include <common>
#include <dithering_pars_fragment>
#include <color_pars_fragment>
#include <uv_pars_fragment>
#include <map_pars_fragment>
#include <alphamap_pars_fragment>
#include <alphatest_pars_fragment>
#include <alphahash_pars_fragment>
#include <aomap_pars_fragment>
#include <lightmap_pars_fragment>
#include <emissivemap_pars_fragment>
#include <envmap_common_pars_fragment>
#include <envmap_pars_fragment>
#include <envmap_physical_pars_fragment>
#include <fog_pars_fragment>
#include <bsdfs>
#include <lights_pars_begin>
#include <normal_pars_fragment>
#include <lights_lambert_pars_fragment>
#include <shadowmap_pars_fragment>
#include <bumpmap_pars_fragment>
#include <normalmap_pars_fragment>
#include <specularmap_pars_fragment>
#include <logdepthbuf_pars_fragment>
#include <clipping_planes_pars_fragment>
void main() {
	vec4 diffuseColor = vec4( diffuse, opacity );
	#include <clipping_planes_fragment>
	ReflectedLight reflectedLight = ReflectedLight( vec3( 0.0 ), vec3( 0.0 ), vec3( 0.0 ), vec3( 0.0 ) );
	vec3 totalEmissiveRadiance = emissive;
	#include <logdepthbuf_fragment>
	#include <map_fragment>
	#include <color_fragment>
	#include <alphamap_fragment>
	#include <alphatest_fragment>
	#include <alphahash_fragment>
	#include <specularmap_fragment>
	#include <normal_fragment_begin>
	#include <normal_fragment_maps>
	#include <emissivemap_fragment>
	#include <lights_lambert_fragment>
	#include <lights_fragment_begin>
	#include <lights_fragment_maps>
	#include <lights_fragment_end>
	#include <aomap_fragment>
	vec3 outgoingLight = reflectedLight.directDiffuse + reflectedLight.indirectDiffuse + totalEmissiveRadiance;
	#include <envmap_fragment>
	#include <opaque_fragment>
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
	#include <fog_fragment>
	#include <premultiplied_alpha_fragment>
	#include <dithering_fragment>
}`,Bl=`#define MATCAP
varying vec3 vViewPosition;
#include <common>
#include <batching_pars_vertex>
#include <uv_pars_vertex>
#include <color_pars_vertex>
#include <displacementmap_pars_vertex>
#include <fog_pars_vertex>
#include <normal_pars_vertex>
#include <morphtarget_pars_vertex>
#include <skinning_pars_vertex>
#include <logdepthbuf_pars_vertex>
#include <clipping_planes_pars_vertex>
void main() {
	#include <uv_vertex>
	#include <color_vertex>
	#include <morphinstance_vertex>
	#include <morphcolor_vertex>
	#include <batching_vertex>
	#include <beginnormal_vertex>
	#include <morphnormal_vertex>
	#include <skinbase_vertex>
	#include <skinnormal_vertex>
	#include <defaultnormal_vertex>
	#include <normal_vertex>
	#include <begin_vertex>
	#include <morphtarget_vertex>
	#include <skinning_vertex>
	#include <displacementmap_vertex>
	#include <project_vertex>
	#include <logdepthbuf_vertex>
	#include <clipping_planes_vertex>
	#include <fog_vertex>
	vViewPosition = - mvPosition.xyz;
}`,Gl=`#define MATCAP
uniform vec3 diffuse;
uniform float opacity;
uniform sampler2D matcap;
varying vec3 vViewPosition;
#include <common>
#include <dithering_pars_fragment>
#include <color_pars_fragment>
#include <uv_pars_fragment>
#include <map_pars_fragment>
#include <alphamap_pars_fragment>
#include <alphatest_pars_fragment>
#include <alphahash_pars_fragment>
#include <fog_pars_fragment>
#include <normal_pars_fragment>
#include <bumpmap_pars_fragment>
#include <normalmap_pars_fragment>
#include <logdepthbuf_pars_fragment>
#include <clipping_planes_pars_fragment>
void main() {
	vec4 diffuseColor = vec4( diffuse, opacity );
	#include <clipping_planes_fragment>
	#include <logdepthbuf_fragment>
	#include <map_fragment>
	#include <color_fragment>
	#include <alphamap_fragment>
	#include <alphatest_fragment>
	#include <alphahash_fragment>
	#include <normal_fragment_begin>
	#include <normal_fragment_maps>
	vec3 viewDir = normalize( vViewPosition );
	vec3 x = normalize( vec3( viewDir.z, 0.0, - viewDir.x ) );
	vec3 y = cross( viewDir, x );
	vec2 uv = vec2( dot( x, normal ), dot( y, normal ) ) * 0.495 + 0.5;
	#ifdef USE_MATCAP
		vec4 matcapColor = texture2D( matcap, uv );
	#else
		vec4 matcapColor = vec4( vec3( mix( 0.2, 0.8, uv.y ) ), 1.0 );
	#endif
	vec3 outgoingLight = diffuseColor.rgb * matcapColor.rgb;
	#include <opaque_fragment>
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
	#include <fog_fragment>
	#include <premultiplied_alpha_fragment>
	#include <dithering_fragment>
}`,Hl=`#define NORMAL
#if defined( FLAT_SHADED ) || defined( USE_BUMPMAP ) || defined( USE_NORMALMAP_TANGENTSPACE )
	varying vec3 vViewPosition;
#endif
#include <common>
#include <batching_pars_vertex>
#include <uv_pars_vertex>
#include <displacementmap_pars_vertex>
#include <normal_pars_vertex>
#include <morphtarget_pars_vertex>
#include <skinning_pars_vertex>
#include <logdepthbuf_pars_vertex>
#include <clipping_planes_pars_vertex>
void main() {
	#include <uv_vertex>
	#include <batching_vertex>
	#include <beginnormal_vertex>
	#include <morphinstance_vertex>
	#include <morphnormal_vertex>
	#include <skinbase_vertex>
	#include <skinnormal_vertex>
	#include <defaultnormal_vertex>
	#include <normal_vertex>
	#include <begin_vertex>
	#include <morphtarget_vertex>
	#include <skinning_vertex>
	#include <displacementmap_vertex>
	#include <project_vertex>
	#include <logdepthbuf_vertex>
	#include <clipping_planes_vertex>
#if defined( FLAT_SHADED ) || defined( USE_BUMPMAP ) || defined( USE_NORMALMAP_TANGENTSPACE )
	vViewPosition = - mvPosition.xyz;
#endif
}`,Vl=`#define NORMAL
uniform float opacity;
#if defined( FLAT_SHADED ) || defined( USE_BUMPMAP ) || defined( USE_NORMALMAP_TANGENTSPACE )
	varying vec3 vViewPosition;
#endif
#include <uv_pars_fragment>
#include <normal_pars_fragment>
#include <bumpmap_pars_fragment>
#include <normalmap_pars_fragment>
#include <logdepthbuf_pars_fragment>
#include <clipping_planes_pars_fragment>
void main() {
	vec4 diffuseColor = vec4( 0.0, 0.0, 0.0, opacity );
	#include <clipping_planes_fragment>
	#include <logdepthbuf_fragment>
	#include <normal_fragment_begin>
	#include <normal_fragment_maps>
	gl_FragColor = vec4( normalize( normal ) * 0.5 + 0.5, diffuseColor.a );
	#ifdef OPAQUE
		gl_FragColor.a = 1.0;
	#endif
}`,Wl=`#define PHONG
varying vec3 vViewPosition;
#include <common>
#include <batching_pars_vertex>
#include <uv_pars_vertex>
#include <displacementmap_pars_vertex>
#include <envmap_pars_vertex>
#include <color_pars_vertex>
#include <fog_pars_vertex>
#include <normal_pars_vertex>
#include <morphtarget_pars_vertex>
#include <skinning_pars_vertex>
#include <shadowmap_pars_vertex>
#include <logdepthbuf_pars_vertex>
#include <clipping_planes_pars_vertex>
void main() {
	#include <uv_vertex>
	#include <color_vertex>
	#include <morphcolor_vertex>
	#include <batching_vertex>
	#include <beginnormal_vertex>
	#include <morphinstance_vertex>
	#include <morphnormal_vertex>
	#include <skinbase_vertex>
	#include <skinnormal_vertex>
	#include <defaultnormal_vertex>
	#include <normal_vertex>
	#include <begin_vertex>
	#include <morphtarget_vertex>
	#include <skinning_vertex>
	#include <displacementmap_vertex>
	#include <project_vertex>
	#include <logdepthbuf_vertex>
	#include <clipping_planes_vertex>
	vViewPosition = - mvPosition.xyz;
	#include <worldpos_vertex>
	#include <envmap_vertex>
	#include <shadowmap_vertex>
	#include <fog_vertex>
}`,kl=`#define PHONG
uniform vec3 diffuse;
uniform vec3 emissive;
uniform vec3 specular;
uniform float shininess;
uniform float opacity;
#include <common>
#include <dithering_pars_fragment>
#include <color_pars_fragment>
#include <uv_pars_fragment>
#include <map_pars_fragment>
#include <alphamap_pars_fragment>
#include <alphatest_pars_fragment>
#include <alphahash_pars_fragment>
#include <aomap_pars_fragment>
#include <lightmap_pars_fragment>
#include <emissivemap_pars_fragment>
#include <envmap_common_pars_fragment>
#include <envmap_pars_fragment>
#include <envmap_physical_pars_fragment>
#include <fog_pars_fragment>
#include <bsdfs>
#include <lights_pars_begin>
#include <normal_pars_fragment>
#include <lights_phong_pars_fragment>
#include <shadowmap_pars_fragment>
#include <bumpmap_pars_fragment>
#include <normalmap_pars_fragment>
#include <specularmap_pars_fragment>
#include <logdepthbuf_pars_fragment>
#include <clipping_planes_pars_fragment>
void main() {
	vec4 diffuseColor = vec4( diffuse, opacity );
	#include <clipping_planes_fragment>
	ReflectedLight reflectedLight = ReflectedLight( vec3( 0.0 ), vec3( 0.0 ), vec3( 0.0 ), vec3( 0.0 ) );
	vec3 totalEmissiveRadiance = emissive;
	#include <logdepthbuf_fragment>
	#include <map_fragment>
	#include <color_fragment>
	#include <alphamap_fragment>
	#include <alphatest_fragment>
	#include <alphahash_fragment>
	#include <specularmap_fragment>
	#include <normal_fragment_begin>
	#include <normal_fragment_maps>
	#include <emissivemap_fragment>
	#include <lights_phong_fragment>
	#include <lights_fragment_begin>
	#include <lights_fragment_maps>
	#include <lights_fragment_end>
	#include <aomap_fragment>
	vec3 outgoingLight = reflectedLight.directDiffuse + reflectedLight.indirectDiffuse + reflectedLight.directSpecular + reflectedLight.indirectSpecular + totalEmissiveRadiance;
	#include <envmap_fragment>
	#include <opaque_fragment>
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
	#include <fog_fragment>
	#include <premultiplied_alpha_fragment>
	#include <dithering_fragment>
}`,zl=`#define STANDARD
varying vec3 vViewPosition;
#ifdef USE_TRANSMISSION
	varying vec3 vWorldPosition;
#endif
#include <common>
#include <batching_pars_vertex>
#include <uv_pars_vertex>
#include <displacementmap_pars_vertex>
#include <color_pars_vertex>
#include <fog_pars_vertex>
#include <normal_pars_vertex>
#include <morphtarget_pars_vertex>
#include <skinning_pars_vertex>
#include <shadowmap_pars_vertex>
#include <logdepthbuf_pars_vertex>
#include <clipping_planes_pars_vertex>
void main() {
	#include <uv_vertex>
	#include <color_vertex>
	#include <morphinstance_vertex>
	#include <morphcolor_vertex>
	#include <batching_vertex>
	#include <beginnormal_vertex>
	#include <morphnormal_vertex>
	#include <skinbase_vertex>
	#include <skinnormal_vertex>
	#include <defaultnormal_vertex>
	#include <normal_vertex>
	#include <begin_vertex>
	#include <morphtarget_vertex>
	#include <skinning_vertex>
	#include <displacementmap_vertex>
	#include <project_vertex>
	#include <logdepthbuf_vertex>
	#include <clipping_planes_vertex>
	vViewPosition = - mvPosition.xyz;
	#include <worldpos_vertex>
	#include <shadowmap_vertex>
	#include <fog_vertex>
#ifdef USE_TRANSMISSION
	vWorldPosition = worldPosition.xyz;
#endif
}`,Xl=`#define STANDARD
#ifdef PHYSICAL
	#define IOR
	#define USE_SPECULAR
#endif
uniform vec3 diffuse;
uniform vec3 emissive;
uniform float roughness;
uniform float metalness;
uniform float opacity;
#ifdef IOR
	uniform float ior;
#endif
#ifdef USE_SPECULAR
	uniform float specularIntensity;
	uniform vec3 specularColor;
	#ifdef USE_SPECULAR_COLORMAP
		uniform sampler2D specularColorMap;
	#endif
	#ifdef USE_SPECULAR_INTENSITYMAP
		uniform sampler2D specularIntensityMap;
	#endif
#endif
#ifdef USE_CLEARCOAT
	uniform float clearcoat;
	uniform float clearcoatRoughness;
#endif
#ifdef USE_DIFFUSE_ROUGHNESS
	uniform float diffuseRoughness;
	#ifdef USE_DIFFUSE_ROUGHNESSMAP
		uniform sampler2D diffuseRoughnessMap;
	#endif
#endif
#ifdef USE_DISPERSION
	uniform float dispersion;
#endif
#ifdef USE_RETROREFLECTION
	uniform float retroreflectivity;
#endif
#ifdef USE_IRIDESCENCE
	uniform float iridescence;
	uniform float iridescenceIOR;
	uniform float iridescenceThicknessMinimum;
	uniform float iridescenceThicknessMaximum;
#endif
#ifdef USE_SHEEN
	uniform vec3 sheenColor;
	uniform float sheenRoughness;
	#ifdef USE_SHEEN_COLORMAP
		uniform sampler2D sheenColorMap;
	#endif
	#ifdef USE_SHEEN_ROUGHNESSMAP
		uniform sampler2D sheenRoughnessMap;
	#endif
#endif
#ifdef USE_ANISOTROPY
	uniform vec2 anisotropyVector;
	#ifdef USE_ANISOTROPYMAP
		uniform sampler2D anisotropyMap;
	#endif
#endif
varying vec3 vViewPosition;
#include <common>
#include <dithering_pars_fragment>
#include <color_pars_fragment>
#include <uv_pars_fragment>
#include <map_pars_fragment>
#include <alphamap_pars_fragment>
#include <alphatest_pars_fragment>
#include <alphahash_pars_fragment>
#include <aomap_pars_fragment>
#include <lightmap_pars_fragment>
#include <emissivemap_pars_fragment>
#include <iridescence_fragment>
#include <envmap_common_pars_fragment>
#include <envmap_physical_pars_fragment>
#include <fog_pars_fragment>
#include <lights_pars_begin>
#include <normal_pars_fragment>
#include <lights_physical_pars_fragment>
#include <transmission_pars_fragment>
#include <shadowmap_pars_fragment>
#include <bumpmap_pars_fragment>
#include <normalmap_pars_fragment>
#include <clearcoat_pars_fragment>
#include <iridescence_pars_fragment>
#include <roughnessmap_pars_fragment>
#include <metalnessmap_pars_fragment>
#include <logdepthbuf_pars_fragment>
#include <clipping_planes_pars_fragment>
void main() {
	vec4 diffuseColor = vec4( diffuse, opacity );
	#include <clipping_planes_fragment>
	ReflectedLight reflectedLight = ReflectedLight( vec3( 0.0 ), vec3( 0.0 ), vec3( 0.0 ), vec3( 0.0 ) );
	vec3 totalEmissiveRadiance = emissive;
	#include <logdepthbuf_fragment>
	#include <map_fragment>
	#include <color_fragment>
	#include <alphamap_fragment>
	#include <alphatest_fragment>
	#include <alphahash_fragment>
	#include <roughnessmap_fragment>
	#include <metalnessmap_fragment>
	#include <normal_fragment_begin>
	#include <normal_fragment_maps>
	#include <clearcoat_normal_fragment_begin>
	#include <clearcoat_normal_fragment_maps>
	#include <emissivemap_fragment>
	#include <lights_physical_fragment>
	#include <lights_fragment_begin>
	#include <lights_fragment_maps>
	#include <lights_fragment_end>
	#include <aomap_fragment>
	vec3 totalDiffuse = reflectedLight.directDiffuse + reflectedLight.indirectDiffuse;
	vec3 totalSpecular = reflectedLight.directSpecular + reflectedLight.indirectSpecular;
	#include <transmission_fragment>
	vec3 outgoingLight = totalDiffuse + totalSpecular + totalEmissiveRadiance;
	#ifdef USE_SHEEN
 
		outgoingLight = outgoingLight + sheenSpecularDirect + sheenSpecularIndirect;
 
 	#endif
	#ifdef USE_CLEARCOAT
		float dotNVcc = saturate( dot( geometryClearcoatNormal, geometryViewDir ) );
		vec3 Fcc = F_Schlick( material.clearcoatF0, material.clearcoatF90, dotNVcc );
		outgoingLight = outgoingLight * ( 1.0 - material.clearcoat * Fcc ) + ( clearcoatSpecularDirect + clearcoatSpecularIndirect ) * material.clearcoat;
	#endif
	#include <opaque_fragment>
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
	#include <fog_fragment>
	#include <premultiplied_alpha_fragment>
	#include <dithering_fragment>
}`,Kl=`#define TOON
varying vec3 vViewPosition;
#include <common>
#include <batching_pars_vertex>
#include <uv_pars_vertex>
#include <displacementmap_pars_vertex>
#include <color_pars_vertex>
#include <fog_pars_vertex>
#include <normal_pars_vertex>
#include <morphtarget_pars_vertex>
#include <skinning_pars_vertex>
#include <shadowmap_pars_vertex>
#include <logdepthbuf_pars_vertex>
#include <clipping_planes_pars_vertex>
void main() {
	#include <uv_vertex>
	#include <color_vertex>
	#include <morphinstance_vertex>
	#include <morphcolor_vertex>
	#include <batching_vertex>
	#include <beginnormal_vertex>
	#include <morphnormal_vertex>
	#include <skinbase_vertex>
	#include <skinnormal_vertex>
	#include <defaultnormal_vertex>
	#include <normal_vertex>
	#include <begin_vertex>
	#include <morphtarget_vertex>
	#include <skinning_vertex>
	#include <displacementmap_vertex>
	#include <project_vertex>
	#include <logdepthbuf_vertex>
	#include <clipping_planes_vertex>
	vViewPosition = - mvPosition.xyz;
	#include <worldpos_vertex>
	#include <shadowmap_vertex>
	#include <fog_vertex>
}`,ql=`#define TOON
uniform vec3 diffuse;
uniform vec3 emissive;
uniform float opacity;
#include <common>
#include <dithering_pars_fragment>
#include <color_pars_fragment>
#include <uv_pars_fragment>
#include <map_pars_fragment>
#include <alphamap_pars_fragment>
#include <alphatest_pars_fragment>
#include <alphahash_pars_fragment>
#include <aomap_pars_fragment>
#include <lightmap_pars_fragment>
#include <emissivemap_pars_fragment>
#include <gradientmap_pars_fragment>
#include <fog_pars_fragment>
#include <bsdfs>
#include <lights_pars_begin>
#include <normal_pars_fragment>
#include <lights_toon_pars_fragment>
#include <shadowmap_pars_fragment>
#include <bumpmap_pars_fragment>
#include <normalmap_pars_fragment>
#include <logdepthbuf_pars_fragment>
#include <clipping_planes_pars_fragment>
void main() {
	vec4 diffuseColor = vec4( diffuse, opacity );
	#include <clipping_planes_fragment>
	ReflectedLight reflectedLight = ReflectedLight( vec3( 0.0 ), vec3( 0.0 ), vec3( 0.0 ), vec3( 0.0 ) );
	vec3 totalEmissiveRadiance = emissive;
	#include <logdepthbuf_fragment>
	#include <map_fragment>
	#include <color_fragment>
	#include <alphamap_fragment>
	#include <alphatest_fragment>
	#include <alphahash_fragment>
	#include <normal_fragment_begin>
	#include <normal_fragment_maps>
	#include <emissivemap_fragment>
	#include <lights_toon_fragment>
	#include <lights_fragment_begin>
	#include <lights_fragment_maps>
	#include <lights_fragment_end>
	#include <aomap_fragment>
	vec3 outgoingLight = reflectedLight.directDiffuse + reflectedLight.indirectDiffuse + totalEmissiveRadiance;
	#include <opaque_fragment>
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
	#include <fog_fragment>
	#include <premultiplied_alpha_fragment>
	#include <dithering_fragment>
}`,Yl=`uniform float size;
uniform float scale;
#include <common>
#include <color_pars_vertex>
#include <fog_pars_vertex>
#include <morphtarget_pars_vertex>
#include <logdepthbuf_pars_vertex>
#include <clipping_planes_pars_vertex>
#ifdef USE_POINTS_UV
	varying vec2 vUv;
	uniform mat3 uvTransform;
#endif
void main() {
	#ifdef USE_POINTS_UV
		vUv = ( uvTransform * vec3( uv, 1 ) ).xy;
	#endif
	#include <color_vertex>
	#include <morphinstance_vertex>
	#include <morphcolor_vertex>
	#include <begin_vertex>
	#include <morphtarget_vertex>
	#include <project_vertex>
	gl_PointSize = size;
	#ifdef USE_SIZEATTENUATION
		bool isPerspective = isPerspectiveMatrix( projectionMatrix );
		if ( isPerspective ) gl_PointSize *= ( scale / - mvPosition.z );
	#endif
	#include <logdepthbuf_vertex>
	#include <clipping_planes_vertex>
	#include <worldpos_vertex>
	#include <fog_vertex>
}`,Zl=`uniform vec3 diffuse;
uniform float opacity;
#include <common>
#include <color_pars_fragment>
#include <map_particle_pars_fragment>
#include <alphatest_pars_fragment>
#include <alphahash_pars_fragment>
#include <fog_pars_fragment>
#include <logdepthbuf_pars_fragment>
#include <clipping_planes_pars_fragment>
void main() {
	vec4 diffuseColor = vec4( diffuse, opacity );
	#include <clipping_planes_fragment>
	vec3 outgoingLight = vec3( 0.0 );
	#include <logdepthbuf_fragment>
	#include <map_particle_fragment>
	#include <color_fragment>
	#include <alphatest_fragment>
	#include <alphahash_fragment>
	outgoingLight = diffuseColor.rgb;
	#include <opaque_fragment>
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
	#include <fog_fragment>
	#include <premultiplied_alpha_fragment>
}`,$l=`#include <common>
#include <batching_pars_vertex>
#include <fog_pars_vertex>
#include <morphtarget_pars_vertex>
#include <skinning_pars_vertex>
#include <logdepthbuf_pars_vertex>
#include <shadowmap_pars_vertex>
void main() {
	#include <batching_vertex>
	#include <beginnormal_vertex>
	#include <morphinstance_vertex>
	#include <morphnormal_vertex>
	#include <skinbase_vertex>
	#include <skinnormal_vertex>
	#include <defaultnormal_vertex>
	#include <begin_vertex>
	#include <morphtarget_vertex>
	#include <skinning_vertex>
	#include <project_vertex>
	#include <logdepthbuf_vertex>
	#include <worldpos_vertex>
	#include <shadowmap_vertex>
	#include <fog_vertex>
}`,Ql=`uniform vec3 color;
uniform float opacity;
#include <common>
#include <fog_pars_fragment>
#include <bsdfs>
#include <lights_pars_begin>
#include <logdepthbuf_pars_fragment>
#include <shadowmap_pars_fragment>
#include <shadowmask_pars_fragment>
void main() {
	#include <logdepthbuf_fragment>
	gl_FragColor = vec4( color, opacity * ( 1.0 - getShadowMask() ) );
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
	#include <fog_fragment>
	#include <premultiplied_alpha_fragment>
}`,Jl=`uniform float rotation;
uniform vec2 center;
#include <common>
#include <uv_pars_vertex>
#include <fog_pars_vertex>
#include <logdepthbuf_pars_vertex>
#include <clipping_planes_pars_vertex>
void main() {
	#include <uv_vertex>
	vec4 mvPosition = modelViewMatrix[ 3 ];
	vec2 scale = vec2( length( modelMatrix[ 0 ].xyz ), length( modelMatrix[ 1 ].xyz ) );
	#ifndef USE_SIZEATTENUATION
		bool isPerspective = isPerspectiveMatrix( projectionMatrix );
		if ( isPerspective ) scale *= - mvPosition.z;
	#endif
	vec2 alignedPosition = ( position.xy - ( center - vec2( 0.5 ) ) ) * scale;
	vec2 rotatedPosition;
	rotatedPosition.x = cos( rotation ) * alignedPosition.x - sin( rotation ) * alignedPosition.y;
	rotatedPosition.y = sin( rotation ) * alignedPosition.x + cos( rotation ) * alignedPosition.y;
	mvPosition.xy += rotatedPosition;
	gl_Position = projectionMatrix * mvPosition;
	#include <logdepthbuf_vertex>
	#include <clipping_planes_vertex>
	#include <fog_vertex>
}`,jl=`uniform vec3 diffuse;
uniform float opacity;
#include <common>
#include <uv_pars_fragment>
#include <map_pars_fragment>
#include <alphamap_pars_fragment>
#include <alphatest_pars_fragment>
#include <alphahash_pars_fragment>
#include <fog_pars_fragment>
#include <logdepthbuf_pars_fragment>
#include <clipping_planes_pars_fragment>
void main() {
	vec4 diffuseColor = vec4( diffuse, opacity );
	#include <clipping_planes_fragment>
	vec3 outgoingLight = vec3( 0.0 );
	#include <logdepthbuf_fragment>
	#include <map_fragment>
	#include <alphamap_fragment>
	#include <alphatest_fragment>
	#include <alphahash_fragment>
	outgoingLight = diffuseColor.rgb;
	#include <opaque_fragment>
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
	#include <fog_fragment>
}`,De={alphahash_fragment:Mo,alphahash_pars_fragment:xo,alphamap_fragment:To,alphamap_pars_fragment:Ao,alphatest_fragment:Ro,alphatest_pars_fragment:Co,aomap_fragment:bo,aomap_pars_fragment:Po,batching_pars_vertex:Lo,batching_vertex:Do,begin_vertex:Uo,beginnormal_vertex:wo,bsdfs:No,iridescence_fragment:Io,bumpmap_pars_fragment:yo,clipping_planes_fragment:Fo,clipping_planes_pars_fragment:Oo,clipping_planes_pars_vertex:Bo,clipping_planes_vertex:Go,color_fragment:Ho,color_pars_fragment:Vo,color_pars_vertex:Wo,color_vertex:ko,common:zo,defaultnormal_vertex:Xo,displacementmap_pars_vertex:Ko,displacementmap_vertex:qo,emissivemap_fragment:Yo,emissivemap_pars_fragment:Zo,colorspace_fragment:$o,colorspace_pars_fragment:Qo,envmap_fragment:Jo,envmap_common_pars_fragment:jo,envmap_pars_fragment:es,envmap_pars_vertex:ts,envmap_physical_pars_fragment:us,envmap_vertex:ns,fog_vertex:is,fog_pars_vertex:rs,fog_fragment:as,fog_pars_fragment:os,gradientmap_pars_fragment:ss,lightmap_pars_fragment:ls,lights_lambert_fragment:cs,lights_lambert_pars_fragment:fs,lights_pars_begin:ds,lights_toon_fragment:ps,lights_toon_pars_fragment:hs,lights_phong_fragment:ms,lights_phong_pars_fragment:_s,lights_physical_fragment:gs,lights_physical_pars_fragment:vs,lights_fragment_begin:Ss,lights_fragment_maps:Es,lights_fragment_end:Ms,lightprobes_pars_fragment:xs,logdepthbuf_fragment:Ts,logdepthbuf_pars_fragment:As,logdepthbuf_pars_vertex:Rs,logdepthbuf_vertex:Cs,map_fragment:bs,map_pars_fragment:Ps,map_particle_fragment:Ls,map_particle_pars_fragment:Ds,metalnessmap_fragment:Us,metalnessmap_pars_fragment:ws,morphinstance_vertex:Ns,morphcolor_vertex:Is,morphnormal_vertex:ys,morphtarget_pars_vertex:Fs,morphtarget_vertex:Os,normal_fragment_begin:Bs,normal_fragment_maps:Gs,normal_pars_fragment:Hs,normal_pars_vertex:Vs,normal_vertex:Ws,normalmap_pars_fragment:ks,clearcoat_normal_fragment_begin:zs,clearcoat_normal_fragment_maps:Xs,clearcoat_pars_fragment:Ks,iridescence_pars_fragment:qs,opaque_fragment:Ys,packing:Zs,premultiplied_alpha_fragment:$s,project_vertex:Qs,dithering_fragment:Js,dithering_pars_fragment:js,roughnessmap_fragment:el,roughnessmap_pars_fragment:tl,shadowmap_pars_fragment:nl,shadowmap_pars_vertex:il,shadowmap_vertex:rl,shadowmask_pars_fragment:al,skinbase_vertex:ol,skinning_pars_vertex:sl,skinning_vertex:ll,skinnormal_vertex:cl,specularmap_fragment:fl,specularmap_pars_fragment:dl,tonemapping_fragment:ul,tonemapping_pars_fragment:pl,transmission_fragment:hl,transmission_pars_fragment:ml,uv_pars_fragment:_l,uv_pars_vertex:gl,uv_vertex:vl,worldpos_vertex:Sl,background_vert:El,background_frag:Ml,backgroundCube_vert:xl,backgroundCube_frag:Tl,cube_vert:Al,cube_frag:Rl,depth_vert:Cl,depth_frag:bl,distance_vert:Pl,distance_frag:Ll,equirect_vert:Dl,equirect_frag:Ul,linedashed_vert:wl,linedashed_frag:Nl,meshbasic_vert:Il,meshbasic_frag:yl,meshlambert_vert:Fl,meshlambert_frag:Ol,meshmatcap_vert:Bl,meshmatcap_frag:Gl,meshnormal_vert:Hl,meshnormal_frag:Vl,meshphong_vert:Wl,meshphong_frag:kl,meshphysical_vert:zl,meshphysical_frag:Xl,meshtoon_vert:Kl,meshtoon_frag:ql,points_vert:Yl,points_frag:Zl,shadow_vert:$l,shadow_frag:Ql,sprite_vert:Jl,sprite_frag:jl},de={common:{diffuse:{value:new Ye(16777215)},opacity:{value:1},map:{value:null},mapTransform:{value:new Oe},alphaMap:{value:null},alphaMapTransform:{value:new Oe},alphaTest:{value:0}},specularmap:{specularMap:{value:null},specularMapTransform:{value:new Oe}},envmap:{envMap:{value:null},envMapRotation:{value:new Oe},reflectivity:{value:1},ior:{value:1.5},refractionRatio:{value:.98},dfgLUT:{value:null}},aomap:{aoMap:{value:null},aoMapIntensity:{value:1},aoMapTransform:{value:new Oe}},lightmap:{lightMap:{value:null},lightMapIntensity:{value:1},lightMapTransform:{value:new Oe}},bumpmap:{bumpMap:{value:null},bumpMapTransform:{value:new Oe},bumpScale:{value:1}},normalmap:{normalMap:{value:null},normalMapTransform:{value:new Oe},normalScale:{value:new ht(1,1)}},displacementmap:{displacementMap:{value:null},displacementMapTransform:{value:new Oe},displacementScale:{value:1},displacementBias:{value:0}},emissivemap:{emissiveMap:{value:null},emissiveMapTransform:{value:new Oe}},metalnessmap:{metalnessMap:{value:null},metalnessMapTransform:{value:new Oe}},roughnessmap:{roughnessMap:{value:null},roughnessMapTransform:{value:new Oe}},gradientmap:{gradientMap:{value:null}},fog:{fogDensity:{value:25e-5},fogNear:{value:1},fogFar:{value:2e3},fogColor:{value:new Ye(16777215)}},lights:{ambientLightColor:{value:[]},lightProbe:{value:[]},sunLights:{value:[],properties:{direction:{},color:{}}},sunLightShadows:{value:[],properties:{shadowIntensity:1,shadowBias:{},shadowNormalBias:{},shadowRadius:{},shadowMapSize:{}}},sunShadowMatrix:{value:[]},sunShadowCascade:{value:[]},directionalLights:{value:[],properties:{direction:{},color:{}}},directionalLightShadows:{value:[],properties:{shadowIntensity:1,shadowBias:{},shadowNormalBias:{},shadowRadius:{},shadowMapSize:{}}},directionalShadowMatrix:{value:[]},spotLights:{value:[],properties:{color:{},position:{},direction:{},distance:{},coneCos:{},penumbraCos:{},decay:{}}},spotLightShadows:{value:[],properties:{shadowIntensity:1,shadowBias:{},shadowNormalBias:{},shadowRadius:{},shadowMapSize:{}}},spotLightMap:{value:[]},spotLightMatrix:{value:[]},pointLights:{value:[],properties:{color:{},position:{},decay:{},distance:{}}},pointLightShadows:{value:[],properties:{shadowIntensity:1,shadowBias:{},shadowNormalBias:{},shadowRadius:{},shadowMapSize:{},shadowCameraNear:{},shadowCameraFar:{}}},pointShadowMatrix:{value:[]},hemisphereLights:{value:[],properties:{direction:{},skyColor:{},groundColor:{}}},rectAreaLights:{value:[],properties:{color:{},position:{},width:{},height:{}}},ltc_1:{value:null},ltc_2:{value:null},probesSH:{value:[]},probesMin:{value:[]},probesMax:{value:[]},probesResolution:{value:[]}},points:{diffuse:{value:new Ye(16777215)},opacity:{value:1},size:{value:1},scale:{value:1},map:{value:null},alphaMap:{value:null},alphaMapTransform:{value:new Oe},alphaTest:{value:0},uvTransform:{value:new Oe}},sprite:{diffuse:{value:new Ye(16777215)},opacity:{value:1},center:{value:new ht(.5,.5)},rotation:{value:0},map:{value:null},mapTransform:{value:new Oe},alphaMap:{value:null},alphaMapTransform:{value:new Oe},alphaTest:{value:0}}},Pt={basic:{uniforms:vt([de.common,de.specularmap,de.envmap,de.aomap,de.lightmap,de.fog]),vertexShader:De.meshbasic_vert,fragmentShader:De.meshbasic_frag},lambert:{uniforms:vt([de.common,de.specularmap,de.envmap,de.aomap,de.lightmap,de.emissivemap,de.bumpmap,de.normalmap,de.displacementmap,de.fog,de.lights,{emissive:{value:new Ye(0)},envMapIntensity:{value:1}}]),vertexShader:De.meshlambert_vert,fragmentShader:De.meshlambert_frag},phong:{uniforms:vt([de.common,de.specularmap,de.envmap,de.aomap,de.lightmap,de.emissivemap,de.bumpmap,de.normalmap,de.displacementmap,de.fog,de.lights,{emissive:{value:new Ye(0)},specular:{value:new Ye(1118481)},shininess:{value:30},envMapIntensity:{value:1}}]),vertexShader:De.meshphong_vert,fragmentShader:De.meshphong_frag},standard:{uniforms:vt([de.common,de.envmap,de.aomap,de.lightmap,de.emissivemap,de.bumpmap,de.normalmap,de.displacementmap,de.roughnessmap,de.metalnessmap,de.fog,de.lights,{emissive:{value:new Ye(0)},roughness:{value:1},metalness:{value:0},envMapIntensity:{value:1}}]),vertexShader:De.meshphysical_vert,fragmentShader:De.meshphysical_frag},toon:{uniforms:vt([de.common,de.aomap,de.lightmap,de.emissivemap,de.bumpmap,de.normalmap,de.displacementmap,de.gradientmap,de.fog,de.lights,{emissive:{value:new Ye(0)}}]),vertexShader:De.meshtoon_vert,fragmentShader:De.meshtoon_frag},matcap:{uniforms:vt([de.common,de.bumpmap,de.normalmap,de.displacementmap,de.fog,{matcap:{value:null}}]),vertexShader:De.meshmatcap_vert,fragmentShader:De.meshmatcap_frag},points:{uniforms:vt([de.points,de.fog]),vertexShader:De.points_vert,fragmentShader:De.points_frag},dashed:{uniforms:vt([de.common,de.fog,{scale:{value:1},dashSize:{value:1},totalSize:{value:2}}]),vertexShader:De.linedashed_vert,fragmentShader:De.linedashed_frag},depth:{uniforms:vt([de.common,de.displacementmap]),vertexShader:De.depth_vert,fragmentShader:De.depth_frag},normal:{uniforms:vt([de.common,de.bumpmap,de.normalmap,de.displacementmap,{opacity:{value:1}}]),vertexShader:De.meshnormal_vert,fragmentShader:De.meshnormal_frag},sprite:{uniforms:vt([de.sprite,de.fog]),vertexShader:De.sprite_vert,fragmentShader:De.sprite_frag},background:{uniforms:{uvTransform:{value:new Oe},t2D:{value:null},backgroundIntensity:{value:1}},vertexShader:De.background_vert,fragmentShader:De.background_frag},backgroundCube:{uniforms:{envMap:{value:null},backgroundBlurriness:{value:0},backgroundIntensity:{value:1},backgroundRotation:{value:new Oe}},vertexShader:De.backgroundCube_vert,fragmentShader:De.backgroundCube_frag},cube:{uniforms:{tCube:{value:null},tFlip:{value:-1},opacity:{value:1}},vertexShader:De.cube_vert,fragmentShader:De.cube_frag},equirect:{uniforms:{tEquirect:{value:null}},vertexShader:De.equirect_vert,fragmentShader:De.equirect_frag},distance:{uniforms:vt([de.common,de.displacementmap,{referencePosition:{value:new We},nearDistance:{value:1},farDistance:{value:1e3}}]),vertexShader:De.distance_vert,fragmentShader:De.distance_frag},shadow:{uniforms:vt([de.lights,de.fog,{color:{value:new Ye(0)},opacity:{value:1}}]),vertexShader:De.shadow_vert,fragmentShader:De.shadow_frag}};Pt.physical={uniforms:vt([Pt.standard.uniforms,{clearcoat:{value:0},clearcoatMap:{value:null},clearcoatMapTransform:{value:new Oe},clearcoatNormalMap:{value:null},clearcoatNormalMapTransform:{value:new Oe},clearcoatNormalScale:{value:new ht(1,1)},clearcoatRoughness:{value:0},clearcoatRoughnessMap:{value:null},clearcoatRoughnessMapTransform:{value:new Oe},diffuseRoughness:{value:0},diffuseRoughnessMap:{value:null},diffuseRoughnessMapTransform:{value:new Oe},dispersion:{value:0},retroreflectivity:{value:0},iridescence:{value:0},iridescenceMap:{value:null},iridescenceMapTransform:{value:new Oe},iridescenceIOR:{value:1.3},iridescenceThicknessMinimum:{value:100},iridescenceThicknessMaximum:{value:400},iridescenceThicknessMap:{value:null},iridescenceThicknessMapTransform:{value:new Oe},sheen:{value:0},sheenColor:{value:new Ye(0)},sheenColorMap:{value:null},sheenColorMapTransform:{value:new Oe},sheenRoughness:{value:1},sheenRoughnessMap:{value:null},sheenRoughnessMapTransform:{value:new Oe},transmission:{value:0},transmissionMap:{value:null},transmissionMapTransform:{value:new Oe},transmissionSamplerSize:{value:new ht},transmissionSamplerMap:{value:null},thickness:{value:0},thicknessMap:{value:null},thicknessMapTransform:{value:new Oe},attenuationDistance:{value:0},attenuationColor:{value:new Ye(0)},specularColor:{value:new Ye(1,1,1)},specularColorMap:{value:null},specularColorMapTransform:{value:new Oe},specularIntensity:{value:1},specularIntensityMap:{value:null},specularIntensityMapTransform:{value:new Oe},anisotropyVector:{value:new ht},anisotropyMap:{value:null},anisotropyMapTransform:{value:new Oe}}]),vertexShader:De.meshphysical_vert,fragmentShader:De.meshphysical_frag};const Nn={r:0,b:0,g:0},ec=new Yt,Ua=new Oe;Ua.set(-1,0,0,0,1,0,0,0,1);function tc(e,n,t,i,c,s){const u=new Ye(0);let m=c===!0?0:1,I,C,X=null,F=0,v=null;function M(g){let U=g.isScene===!0?g.background:null;if(U&&U.isTexture){const S=g.backgroundBlurriness>0;U=n.get(U,S)}return U}function L(g){let U=!1;const S=M(g);S===null?f(u,m):S&&S.isColor&&(f(S,1),U=!0);const R=e.xr.getEnvironmentBlendMode();R==="additive"?t.buffers.color.setClear(0,0,0,1,s):R==="alpha-blend"&&t.buffers.color.setClear(0,0,0,0,s),(e.autoClear||U)&&(t.buffers.depth.setTest(!0),t.buffers.depth.setMask(!0),t.buffers.color.setMask(!0),e.clear(e.autoClearColor,e.autoClearDepth,e.autoClearStencil))}function B(g,U){const S=M(U);S&&S.isCubeTexture?(C===void 0&&(C=new Gt(new Sn(1,1,1),new qt({name:"BackgroundCubeMaterial",uniforms:Vn(Pt.backgroundCube.uniforms),vertexShader:Pt.backgroundCube.vertexShader,fragmentShader:Pt.backgroundCube.fragmentShader,side:St,depthTest:!1,depthWrite:!1,fog:!1,allowOverride:!1})),C.geometry.deleteAttribute("normal"),C.geometry.deleteAttribute("uv"),C.onBeforeRender=function(R,x,o){this.matrixWorld.copyPosition(o.matrixWorld)},Object.defineProperty(C.material,"envMap",{get:function(){return this.uniforms.envMap.value}}),i.update(C)),C.material.uniforms.envMap.value=S,C.material.uniforms.backgroundBlurriness.value=U.backgroundBlurriness,C.material.uniforms.backgroundIntensity.value=U.backgroundIntensity,C.material.uniforms.backgroundRotation.value.setFromMatrix4(ec.makeRotationFromEuler(U.backgroundRotation)).transpose(),S.isRenderTargetTexture===!1&&C.material.uniforms.backgroundRotation.value.premultiply(Ua),C.material.toneMapped=Je.getTransfer(S.colorSpace)!==Xe,(X!==S||F!==S.version||v!==e.toneMapping)&&(C.material.needsUpdate=!0,X=S,F=S.version,v=e.toneMapping),C.layers.enableAll(),g.unshift(C,C.geometry,C.material,0,0,null)):S&&S.isTexture&&(I===void 0&&(I=new Gt(new Wn(2,2),new qt({name:"BackgroundMaterial",uniforms:Vn(Pt.background.uniforms),vertexShader:Pt.background.vertexShader,fragmentShader:Pt.background.fragmentShader,side:jt,depthTest:!1,depthWrite:!1,fog:!1,allowOverride:!1})),I.geometry.deleteAttribute("normal"),Object.defineProperty(I.material,"map",{get:function(){return this.uniforms.t2D.value}}),i.update(I)),I.material.uniforms.t2D.value=S,I.material.uniforms.backgroundIntensity.value=U.backgroundIntensity,I.material.toneMapped=Je.getTransfer(S.colorSpace)!==Xe,S.matrixAutoUpdate===!0&&S.updateMatrix(),I.material.uniforms.uvTransform.value.copy(S.matrix),(X!==S||F!==S.version||v!==e.toneMapping)&&(I.material.needsUpdate=!0,X=S,F=S.version,v=e.toneMapping),I.layers.enableAll(),g.unshift(I,I.geometry,I.material,0,0,null))}function f(g,U){g.getRGB(Nn,pr(e)),t.buffers.color.setClear(Nn.r,Nn.g,Nn.b,U,s)}function l(){C!==void 0&&(C.geometry.dispose(),C.material.dispose(),C=void 0),I!==void 0&&(I.geometry.dispose(),I.material.dispose(),I=void 0)}return{getClearColor:function(){return u},setClearColor:function(g,U=1){u.set(g),m=U,f(u,m)},getClearAlpha:function(){return m},setClearAlpha:function(g){m=g,f(u,m)},render:L,addToRenderList:B,dispose:l}}function nc(e,n){const t=e.getParameter(e.MAX_VERTEX_ATTRIBS),i={},c=v(null);let s=c,u=!1;function m(N,z,Y,T,Z){let $=!1;const y=F(N,T,Y,z);s!==y&&(s=y,C(s.object)),$=M(N,T,Y,Z),$&&L(N,T,Y,Z),Z!==null&&n.update(Z,e.ELEMENT_ARRAY_BUFFER),($||u)&&(u=!1,S(N,z,Y,T),Z!==null&&e.bindBuffer(e.ELEMENT_ARRAY_BUFFER,n.get(Z).buffer))}function I(){return e.createVertexArray()}function C(N){return e.bindVertexArray(N)}function X(N){return e.deleteVertexArray(N)}function F(N,z,Y,T){const Z=T.wireframe===!0;let $=i[z.id];$===void 0&&($={},i[z.id]=$);const y=N.isInstancedMesh===!0?N.id:0;let fe=$[y];fe===void 0&&(fe={},$[y]=fe);let K=fe[Y.id];K===void 0&&(K={},fe[Y.id]=K);let j=K[Z];return j===void 0&&(j=v(I()),K[Z]=j),j}function v(N){const z=[],Y=[],T=[];for(let Z=0;Z<t;Z++)z[Z]=0,Y[Z]=0,T[Z]=0;return{geometry:null,program:null,wireframe:!1,newAttributes:z,enabledAttributes:Y,attributeDivisors:T,object:N,attributes:{},index:null}}function M(N,z,Y,T){const Z=s.attributes,$=z.attributes;let y=0;const fe=Y.getAttributes();for(const K in fe)if(fe[K].location>=0){const ie=Z[K];let Ae=$[K];if(Ae===void 0&&(K==="instanceMatrix"&&N.instanceMatrix&&(Ae=N.instanceMatrix),K==="instanceColor"&&N.instanceColor&&(Ae=N.instanceColor)),ie===void 0||ie.attribute!==Ae||Ae&&ie.data!==Ae.data)return!0;y++}return s.attributesNum!==y||s.index!==T}function L(N,z,Y,T){const Z={},$=z.attributes;let y=0;const fe=Y.getAttributes();for(const K in fe)if(fe[K].location>=0){let ie=$[K];ie===void 0&&(K==="instanceMatrix"&&N.instanceMatrix&&(ie=N.instanceMatrix),K==="instanceColor"&&N.instanceColor&&(ie=N.instanceColor));const Ae={};Ae.attribute=ie,ie&&ie.data&&(Ae.data=ie.data),Z[K]=Ae,y++}s.attributes=Z,s.attributesNum=y,s.index=T}function B(){const N=s.newAttributes;for(let z=0,Y=N.length;z<Y;z++)N[z]=0}function f(N){l(N,0)}function l(N,z){const Y=s.newAttributes,T=s.enabledAttributes,Z=s.attributeDivisors;Y[N]=1,T[N]===0&&(e.enableVertexAttribArray(N),T[N]=1),Z[N]!==z&&(e.vertexAttribDivisor(N,z),Z[N]=z)}function g(){const N=s.newAttributes,z=s.enabledAttributes;for(let Y=0,T=z.length;Y<T;Y++)z[Y]!==N[Y]&&(e.disableVertexAttribArray(Y),z[Y]=0)}function U(N,z,Y,T,Z,$,y){y===!0?e.vertexAttribIPointer(N,z,Y,Z,$):e.vertexAttribPointer(N,z,Y,T,Z,$)}function S(N,z,Y,T){B();const Z=T.attributes,$=Y.getAttributes(),y=z.defaultAttributeValues;for(const fe in $){const K=$[fe];if(K.location>=0){let j=Z[fe];if(j===void 0&&(fe==="instanceMatrix"&&N.instanceMatrix&&(j=N.instanceMatrix),fe==="instanceColor"&&N.instanceColor&&(j=N.instanceColor)),j!==void 0){const ie=j.normalized,Ae=j.itemSize,Ne=n.get(j);if(Ne===void 0)continue;const W=Ne.buffer,he=Ne.type,Se=Ne.bytesPerElement,Re=he===e.INT||he===e.UNSIGNED_INT||j.gpuType===kn;if(j.isInterleavedBufferAttribute){const me=j.data,we=me.isInterleavedBuffer===!0?me.stride:me.itemSize,_t=j.offset;if(me.isInstancedInterleavedBuffer||me.isInstancedBufferAttribute){for(let Ce=0;Ce<K.locationSize;Ce++)l(K.location+Ce,me.meshPerAttribute);N.isInstancedMesh!==!0&&T._maxInstanceCount===void 0&&(T._maxInstanceCount=me.meshPerAttribute*me.count)}else for(let Ce=0;Ce<K.locationSize;Ce++)f(K.location+Ce);e.bindBuffer(e.ARRAY_BUFFER,W);for(let Ce=0;Ce<K.locationSize;Ce++)U(K.location+Ce,Ae/K.locationSize,he,ie,we*Se,(_t+Ae/K.locationSize*Ce)*Se,Re)}else{if(j.isInstancedBufferAttribute){for(let me=0;me<K.locationSize;me++)l(K.location+me,j.meshPerAttribute);N.isInstancedMesh!==!0&&T._maxInstanceCount===void 0&&(T._maxInstanceCount=j.meshPerAttribute*j.count)}else for(let me=0;me<K.locationSize;me++)f(K.location+me);e.bindBuffer(e.ARRAY_BUFFER,W);for(let me=0;me<K.locationSize;me++)U(K.location+me,Ae/K.locationSize,he,ie,Ae*Se,Ae/K.locationSize*me*Se,Re)}}else if(y!==void 0){const ie=y[fe];if(ie!==void 0)switch(ie.length){case 2:e.vertexAttrib2fv(K.location,ie);break;case 3:e.vertexAttrib3fv(K.location,ie);break;case 4:e.vertexAttrib4fv(K.location,ie);break;default:e.vertexAttrib1fv(K.location,ie)}}}}g()}function R(){q();for(const N in i){const z=i[N];for(const Y in z){const T=z[Y];for(const Z in T){const $=T[Z];for(const y in $)X($[y].object),delete $[y];delete T[Z]}}delete i[N]}}function x(N){if(i[N.id]===void 0)return;const z=i[N.id];for(const Y in z){const T=z[Y];for(const Z in T){const $=T[Z];for(const y in $)X($[y].object),delete $[y];delete T[Z]}}delete i[N.id]}function o(N){for(const z in i){const Y=i[z];for(const T in Y){const Z=Y[T];if(Z[N.id]===void 0)continue;const $=Z[N.id];for(const y in $)X($[y].object),delete $[y];delete Z[N.id]}}}function p(N){for(const z in i){const Y=i[z],T=N.isInstancedMesh===!0?N.id:0,Z=Y[T];if(Z!==void 0){for(const $ in Z){const y=Z[$];for(const fe in y)X(y[fe].object),delete y[fe];delete Z[$]}delete Y[T],Object.keys(Y).length===0&&delete i[z]}}}function q(){D(),u=!0,s!==c&&(s=c,C(s.object))}function D(){c.geometry=null,c.program=null,c.wireframe=!1}return{setup:m,reset:q,resetDefaultState:D,dispose:R,releaseStatesOfGeometry:x,releaseStatesOfObject:p,releaseStatesOfProgram:o,initAttributes:B,enableAttribute:f,disableUnusedAttributes:g}}function ic(e,n,t){let i;function c(I){i=I}function s(I,C){e.drawArrays(i,I,C),t.update(C,i,1)}function u(I,C,X){X!==0&&(e.drawArraysInstanced(i,I,C,X),t.update(C,i,X))}function m(I,C,X){if(X===0)return;n.get("WEBGL_multi_draw").multiDrawArraysWEBGL(i,I,0,C,0,X);let v=0;for(let M=0;M<X;M++)v+=C[M];t.update(v,i,1)}this.setMode=c,this.render=s,this.renderInstances=u,this.renderMultiDraw=m}function rc(e,n,t,i){let c;function s(){if(c!==void 0)return c;if(n.has("EXT_texture_filter_anisotropic")===!0){const o=n.get("EXT_texture_filter_anisotropic");c=e.getParameter(o.MAX_TEXTURE_MAX_ANISOTROPY_EXT)}else c=0;return c}function u(o){return!(o!==Ft&&i.convert(o)!==e.getParameter(e.IMPLEMENTATION_COLOR_READ_FORMAT))}function m(o){const p=o===Dt&&(n.has("EXT_color_buffer_half_float")||n.has("EXT_color_buffer_float"));return!(o!==Ct&&o!==yt&&!p&&i.convert(o)!==e.getParameter(e.IMPLEMENTATION_COLOR_READ_TYPE))}function I(o){if(o==="highp"){if(e.getShaderPrecisionFormat(e.VERTEX_SHADER,e.HIGH_FLOAT).precision>0&&e.getShaderPrecisionFormat(e.FRAGMENT_SHADER,e.HIGH_FLOAT).precision>0)return"highp";o="mediump"}return o==="mediump"&&e.getShaderPrecisionFormat(e.VERTEX_SHADER,e.MEDIUM_FLOAT).precision>0&&e.getShaderPrecisionFormat(e.FRAGMENT_SHADER,e.MEDIUM_FLOAT).precision>0?"mediump":"lowp"}let C=t.precision!==void 0?t.precision:"highp";const X=I(C);X!==C&&(He("WebGLRenderer:",C,"not supported, using",X,"instead."),C=X);const F=t.reversedDepthBuffer===!0&&n.has("EXT_clip_control"),v=t.logarithmicDepthBuffer===!0&&F===!1;t.reversedDepthBuffer===!0&&F===!1&&He("WebGLRenderer: Unable to use reversed depth buffer due to missing EXT_clip_control extension. Fallback to default depth buffer."),t.logarithmicDepthBuffer===!0&&F===!0&&He("WebGLRenderer: Logarithmic depth buffer is ignored when reversed depth buffer is enabled.");const M=e.getParameter(e.MAX_TEXTURE_IMAGE_UNITS),L=e.getParameter(e.MAX_VERTEX_TEXTURE_IMAGE_UNITS),B=e.getParameter(e.MAX_TEXTURE_SIZE),f=e.getParameter(e.MAX_CUBE_MAP_TEXTURE_SIZE),l=e.getParameter(e.MAX_VERTEX_ATTRIBS),g=e.getParameter(e.MAX_VERTEX_UNIFORM_VECTORS),U=e.getParameter(e.MAX_VARYING_VECTORS),S=e.getParameter(e.MAX_FRAGMENT_UNIFORM_VECTORS),R=e.getParameter(e.MAX_SAMPLES),x=e.getParameter(e.SAMPLES);return{isWebGL2:!0,getMaxAnisotropy:s,getMaxPrecision:I,textureFormatReadable:u,textureTypeReadable:m,precision:C,logarithmicDepthBuffer:v,reversedDepthBuffer:F,maxTextures:M,maxVertexTextures:L,maxTextureSize:B,maxCubemapSize:f,maxAttributes:l,maxVertexUniforms:g,maxVaryings:U,maxFragmentUniforms:S,maxSamples:R,samples:x}}function ac(e){const n=this;let t=null,i=0,c=!1,s=!1;const u=new hr,m=new Oe,I={value:null,needsUpdate:!1};this.uniform=I,this.numPlanes=0,this.numIntersection=0,this.init=function(F,v){const M=F.length!==0||v||i!==0||c;return c=v,i=F.length,M},this.beginShadows=function(){s=!0,X(null)},this.endShadows=function(){s=!1},this.setGlobalState=function(F,v){t=X(F,v,0)},this.setState=function(F,v,M){const L=F.clippingPlanes,B=F.clipIntersection,f=F.clipShadows,l=e.get(F);if(!c||L===null||L.length===0||s&&!f)s?X(null):C();else{const g=s?0:i,U=g*4;let S=l.clippingState||null;I.value=S,S=X(L,v,U,M);for(let R=0;R!==U;++R)S[R]=t[R];l.clippingState=S,this.numIntersection=B?this.numPlanes:0,this.numPlanes+=g}};function C(){I.value!==t&&(I.value=t,I.needsUpdate=i>0),n.numPlanes=i,n.numIntersection=0}function X(F,v,M,L){const B=F!==null?F.length:0;let f=null;if(B!==0){if(f=I.value,L!==!0||f===null){const l=M+B*4,g=v.matrixWorldInverse;m.getNormalMatrix(g),(f===null||f.length<l)&&(f=new Float32Array(l));for(let U=0,S=M;U!==B;++U,S+=4)u.copy(F[U]).applyMatrix4(g,m),u.normal.toArray(f,S),f[S+3]=u.constant}I.value=f,I.needsUpdate=!0}return n.numPlanes=B,n.numIntersection=0,f}}class In extends bt{constructor(n=1,t={}){super(n,n,t),this.isWebGLCubeRenderTarget=!0;const i={width:n,height:n,depth:1},c=[i,i,i,i,i,i];this.texture=new zn(c),this._setTextureOptions(t),this.texture.isRenderTargetTexture=!0}fromEquirectangularTexture(n,t){this.texture.type=t.type,this.texture.colorSpace=t.colorSpace,this.texture.generateMipmaps=t.generateMipmaps,this.texture.minFilter=t.minFilter,this.texture.magFilter=t.magFilter;const i={uniforms:{tEquirect:{value:null}},vertexShader:`

				varying vec3 vWorldDirection;

				vec3 transformDirection( in vec3 dir, in mat4 matrix ) {

					return normalize( ( matrix * vec4( dir, 0.0 ) ).xyz );

				}

				void main() {

					vWorldDirection = transformDirection( position, modelMatrix );

					#include <begin_vertex>
					#include <project_vertex>

				}
			`,fragmentShader:`

				uniform sampler2D tEquirect;

				varying vec3 vWorldDirection;

				#include <common>

				void main() {

					vec3 direction = normalize( vWorldDirection );

					vec2 sampleUV = equirectUv( direction );

					gl_FragColor = texture2D( tEquirect, sampleUV );

				}
			`},c=new Sn(5,5,5),s=new qt({name:"CubemapFromEquirect",uniforms:Vn(i.uniforms),vertexShader:i.vertexShader,fragmentShader:i.fragmentShader,side:St,blending:Zt});s.uniforms.tEquirect.value=t;const u=new Gt(c,s),m=t.minFilter;return t.minFilter===Ht&&(t.minFilter=At),new En(1,10,this).update(n,u),t.minFilter=m,u.geometry.dispose(),u.material.dispose(),this}clear(n,t=!0,i=!0,c=!0){const s=n.getRenderTarget();for(let u=0;u<6;u++)n.setRenderTarget(this,u),n.clear(t,i,c);n.setRenderTarget(s)}}const wa=256,oc=3,sc=20,Na=256,Ia=16,lc=3,cc=new We,fc=new Ye;class _n{constructor(n){this._renderer=n,this._cubeSize=0,this._sourceTarget=null,this._cubeCamera=new En(1,10,null),this._boxMesh=new Gt(new Sn(5,5,5),null),this._cubemapMaterial=null,this._equirectMaterial=null,this._blurMaterial=null,this._ggxMaterial=null,this._integrationMaterial=null}fromScene(n,t=0,i=.1,c=100,s={}){const{size:u=256,position:m=cc}=s,I=this._renderer;this._setSize(u);const C=this._allocateTarget(),X=this._getSourceTarget(!0);t>0&&(X.texture.mipmapsAutoUpdate=!1);const F=I.autoClear,v=I.autoClearColor,M=I.autoClearDepth,L=I.autoClearStencil,B=n.background;I.autoClear=!0,I.autoClearColor=!0,I.autoClearDepth=!0,I.autoClearStencil=!0,B===null&&(n.background=I.getClearColor(fc));const f=new En(i,c,X);return f.position.copy(m),f.update(I,n),I.autoClear=F,I.autoClearColor=v,I.autoClearDepth=M,I.autoClearStencil=L,n.background=B,t>0&&(X.texture.mipmapsAutoUpdate=!0,this._blur(C,t)),this._applyPMREM(C),C}fromEquirectangular(n,t=null){return this._fromTexture(n,t)}fromCubemap(n,t=null){return this._fromTexture(n,t)}compileCubemapShader(){this._cubemapMaterial===null&&(this._cubemapMaterial=Oa(),this._compileMaterial(this._cubemapMaterial))}compileEquirectangularShader(){this._equirectMaterial===null&&(this._equirectMaterial=Fa(),this._compileMaterial(this._equirectMaterial))}dispose(){this._sourceTarget!==null&&this._sourceTarget.dispose(),this._cubemapMaterial!==null&&this._cubemapMaterial.dispose(),this._equirectMaterial!==null&&this._equirectMaterial.dispose(),this._blurMaterial!==null&&this._blurMaterial.dispose(),this._ggxMaterial!==null&&this._ggxMaterial.dispose(),this._integrationMaterial!==null&&this._integrationMaterial.dispose(),this._boxMesh.geometry.dispose()}static lodToRoughness(n,t){return t>0?1-Math.sqrt(1-n/t):0}_setSize(n){this._cubeSize=Math.max(wa,mo(n))}_fromTexture(n,t){n.mapping===ln||n.mapping===en?this._setSize(n.image.length===0?wa:n.image[0].width||n.image[0].image.width):this._setSize(n.image.width/4);const i=t||this._allocateTarget();return this._textureToCubemap(n),this._applyPMREM(i),i}_allocateTarget(){const n=this._cubeSize,t=ya(n,!1,!1),i=Math.log2(n)-oc;for(let c=0;c<=i;c++)t.texture.mipmaps.push({width:n>>c,height:n>>c});return t.texture.name="PMREM",t.texture.isPMREMTexture=!0,t}_getSourceTarget(n=!1){const t=this._cubeSize,i=this._sourceTarget;return(i===null||i.width!==t||n&&i.depthBuffer===!1)&&(i!==null&&i.dispose(),this._sourceTarget=ya(t,!0,n)),this._sourceTarget}_compileMaterial(n){this._boxMesh.material=n,this._renderer.compile(this._boxMesh,this._cubeCamera.children[0])}_renderCube(n,t,i){const c=this._boxMesh,s=this._cubeCamera;c.material=i,s.renderTarget=n,s.activeMipmapLevel=t;const u=n.width>>t;n.viewport.set(0,0,u,u),s.update(this._renderer,c),n.viewport.set(0,0,n.width,n.height)}_textureToCubemap(n){let t;n.mapping===ln||n.mapping===en?(this._cubemapMaterial===null&&(this._cubemapMaterial=Oa()),t=this._cubemapMaterial,t.uniforms.flipEnvMap.value=n.isRenderTargetTexture===!1?-1:1):(this._equirectMaterial===null&&(this._equirectMaterial=Fa()),t=this._equirectMaterial),t.uniforms.envMap.value=n,this._renderCube(this._getSourceTarget(),0,t)}_applyPMREM(n){this._ggxMaterial===null&&(this._ggxMaterial=dc(),this._integrationMaterial=uc());const t=this._cubeSize,i=n.texture.mipmaps.length-1,c=this._ggxMaterial.uniforms;c.envMap.value=this._sourceTarget.texture;const s=this._integrationMaterial.uniforms;s.envMap.value=this._sourceTarget.texture,s.sourceLod.value=Math.log2(t/Ia);for(let u=0;u<=i;u++){const m=_n.lodToRoughness(u,i);if(u>i-lc)s.roughness.value=m,this._renderCube(n,u,this._integrationMaterial);else{const I=m>0?Math.log2(t)+.5*Math.log2(6/(Na*Math.pow(m,4)))+.5:0;c.roughness.value=m,c.lodBias.value=I,this._renderCube(n,u,this._ggxMaterial)}}}_blur(n,t){this._blurMaterial===null&&(this._blurMaterial=pc());const i=this._blurMaterial,c=i.uniforms,s=this._sourceTarget;c.sigma.value=Math.min(t,Math.PI)/Math.SQRT2,c.envMap.value=s.texture,this._renderCube(n,0,i),c.envMap.value=n.texture,this._renderCube(s,0,i)}}function ya(e,n,t){return new In(e,{minFilter:Ht,generateMipmaps:n,type:Dt,colorSpace:Xn,depthBuffer:t})}function pn(e,n,t){return new qt({name:e,uniforms:n,vertexShader:De.cube_vert,fragmentShader:t,side:St,blending:Zt,depthTest:!1,depthWrite:!1})}function dc(){return pn("PMREMGGXConvolution",{envMap:{value:null},roughness:{value:0},lodBias:{value:0}},`

		#define GGX_SAMPLES ${Na}u

		varying vec3 vWorldDirection;

		uniform samplerCube envMap;
		uniform float roughness;
		uniform float lodBias;

		#include <common>

		// Van der Corput radical inverse
		float radicalInverse_VdC( uint bits ) {

			bits = ( bits << 16u ) | ( bits >> 16u );
			bits = ( ( bits & 0x55555555u ) << 1u ) | ( ( bits & 0xAAAAAAAAu ) >> 1u );
			bits = ( ( bits & 0x33333333u ) << 2u ) | ( ( bits & 0xCCCCCCCCu ) >> 2u );
			bits = ( ( bits & 0x0F0F0F0Fu ) << 4u ) | ( ( bits & 0xF0F0F0F0u ) >> 4u );
			bits = ( ( bits & 0x00FF00FFu ) << 8u ) | ( ( bits & 0xFF00FF00u ) >> 8u );
			return float( bits ) * 2.3283064365386963e-10; // / 0x100000000

		}

		// Hammersley sequence
		vec2 hammersley( uint i, uint N ) {

			return vec2( float( i ) / float( N ), radicalInverse_VdC( i ) );

		}

		void main() {

			vec3 N = normalize( vWorldDirection );

			// For very low roughness, just sample the environment directly
			if ( roughness < 0.001 ) {

				gl_FragColor = vec4( textureLod( envMap, N, 0.0 ).rgb, 1.0 );
				return;

			}

			float alpha = roughness * roughness;
			float alpha2 = alpha * alpha;

			// Tangent space basis for VNDF sampling
			vec3 up = abs( N.z ) < 0.999 ? vec3( 0.0, 0.0, 1.0 ) : vec3( 1.0, 0.0, 0.0 );
			vec3 tangent = normalize( cross( up, N ) );
			vec3 bitangent = cross( N, tangent );

			vec3 prefilteredColor = vec3( 0.0 );
			float totalWeight = 0.0;

			for ( uint i = 0u; i < GGX_SAMPLES; i ++ ) {

				vec2 Xi = hammersley( i, GGX_SAMPLES );

				// With V = N, sample the reflected direction directly.
				float invQ = 1.0 / ( 1.0 - Xi.x + alpha2 * Xi.x );
				float NdotL = ( 1.0 - Xi.x - alpha2 * Xi.x ) * invQ;

				if ( NdotL > 0.0 ) {

					float phi = 2.0 * PI * Xi.y;
					float sinTheta = 2.0 * alpha * sqrt( Xi.x * ( 1.0 - Xi.x ) ) * invQ;
					vec3 L = N * NdotL + ( tangent * cos( phi ) + bitangent * sin( phi ) ) * sinTheta;

					// Match the source mip to the sample's solid angle. The terms
					// independent of the sample are precomputed in lodBias.
					float d = alpha2 * invQ;
					float lod = max( log2( d ) + lodBias, 0.0 );

					// Weight by NdotL for the split-sum approximation
					prefilteredColor += textureLod( envMap, L, lod ).rgb * NdotL;
					totalWeight += NdotL;

				}

			}

			gl_FragColor = vec4( prefilteredColor / totalWeight, 1.0 );

		}
	`)}function uc(){return pn("PMREMGGXIntegration",{envMap:{value:null},roughness:{value:0},sourceLod:{value:0},sourceSize:{value:Ia}},`

		varying vec3 vWorldDirection;

		uniform samplerCube envMap;
		uniform float roughness;
		uniform float sourceLod;
		uniform int sourceSize;

		void main() {

			vec3 N = normalize( vWorldDirection );

			float alpha = roughness * roughness;
			float alpha2 = alpha * alpha;

			float texelSize = 2.0 / float( sourceSize );

			vec3 prefilteredColor = vec3( 0.0 );
			float totalWeight = 0.0;

			// Pair opposite texels: only the one in N's hemisphere contributes.
			for ( int face = 0; face < 3; face ++ ) {

				for ( int y = 0; y < sourceSize; y ++ ) {

					for ( int x = 0; x < sourceSize; x ++ ) {

						vec2 uv = ( vec2( x, y ) + 0.5 ) * texelSize - 1.0;
						vec3 texelDirection = face == 0 ? vec3( 1.0, uv ) : ( face == 1 ? vec3( uv.x, 1.0, uv.y ) : vec3( uv, 1.0 ) );

						float invDistance = inversesqrt( 1.0 + dot( uv, uv ) );
						float NdotL = dot( N, texelDirection );
						texelDirection *= NdotL < 0.0 ? - 1.0 : 1.0;
						NdotL = abs( NdotL ) * invDistance;

						// With V = N, NdotH squared is ( 1 + NdotL ) / 2. Common factors
						// in the GGX distribution and texel solid angle cancel when normalized.
						float d = 1.0 + alpha2 + ( alpha2 - 1.0 ) * NdotL;
						float weight = NdotL * invDistance * invDistance * invDistance / ( d * d );

						prefilteredColor += textureLod( envMap, texelDirection, sourceLod ).rgb * weight;
						totalWeight += weight;

					}

				}

			}

			gl_FragColor = vec4( prefilteredColor / totalWeight, 1.0 );

		}
	`)}function pc(){return pn("PMREMSphericalGaussianBlur",{envMap:{value:null},sigma:{value:0}},`

		#define SAMPLES ${sc}
		#define GOLDEN_ANGLE 2.39996322973

		varying vec3 vWorldDirection;

		uniform samplerCube envMap;
		uniform float sigma;

		#include <common>

		void main() {

			vec3 outputDirection = normalize( vWorldDirection );

			vec3 up = abs( outputDirection.z ) < 0.999 ? vec3( 0.0, 0.0, 1.0 ) : vec3( 1.0, 0.0, 0.0 );
			vec3 tangent = normalize( cross( up, outputDirection ) );
			vec3 bitangent = cross( outputDirection, tangent );

			// Truncate the kernel at three standard deviations or at the antipode.
			float thetaMax = min( 3.0 * sigma, PI );
			float truncation = 1.0 - exp( - 0.5 * thetaMax * thetaMax / ( sigma * sigma ) );

			vec3 accumColor = vec3( 0.0 );
			float accumWeight = 0.0;

			for ( int i = 0; i < SAMPLES; i ++ ) {

				// Stratified inverse-CDF sampling of the Gaussian, placed on a golden-angle spiral.
				float stratum = ( float( i ) + 0.5 ) / float( SAMPLES );
				float theta = sigma * sqrt( - 2.0 * log( 1.0 - stratum * truncation ) );
				float phi = float( i ) * GOLDEN_ANGLE;

				vec3 offset = cos( phi ) * tangent + sin( phi ) * bitangent;
				vec3 sampleDirection = cos( theta ) * outputDirection + sin( theta ) * offset;

				// Correct the planar sample density to solid angle.
				float weight = sin( theta ) / theta;

				accumColor += weight * textureLod( envMap, sampleDirection, 0.0 ).rgb;
				accumWeight += weight;

			}

			gl_FragColor = vec4( accumColor / accumWeight, 1.0 );

		}
	`)}function Fa(){return pn("PMREMEquirectangularToCubemap",{envMap:{value:null}},`

		varying vec3 vWorldDirection;

		uniform sampler2D envMap;

		#include <common>

		void main() {

			// Average four subpixel samples to preserve small, bright features.
			vec3 direction = normalize( vWorldDirection );
			vec3 dx = dFdx( direction ) * 0.25;
			vec3 dy = dFdy( direction ) * 0.25;

			vec3 color = textureLod( envMap, equirectUv( normalize( direction - dx - dy ) ), 0.0 ).rgb;
			color += textureLod( envMap, equirectUv( normalize( direction + dx - dy ) ), 0.0 ).rgb;
			color += textureLod( envMap, equirectUv( normalize( direction - dx + dy ) ), 0.0 ).rgb;
			color += textureLod( envMap, equirectUv( normalize( direction + dx + dy ) ), 0.0 ).rgb;
			gl_FragColor = vec4( color * 0.25, 1.0 );

		}
	`)}function Oa(){return pn("PMREMCubemapToCubemap",{envMap:{value:null},flipEnvMap:{value:-1}},`

		varying vec3 vWorldDirection;

		uniform samplerCube envMap;
		uniform float flipEnvMap;

		void main() {

			gl_FragColor = vec4( textureCube( envMap, vec3( flipEnvMap * vWorldDirection.x, vWorldDirection.yz ) ).rgb, 1.0 );

		}
	`)}function hc(e){let n=new WeakMap,t=new WeakMap,i=null;function c(v,M=!1){return v==null?null:M?u(v):s(v)}function s(v){if(v&&v.isTexture){const M=v.mapping;if(M===Mn||M===xn)if(n.has(v)){const L=n.get(v).texture;return m(L,v.mapping)}else{const L=v.image;if(L&&L.height>0){const B=new In(L.height);return B.fromEquirectangularTexture(e,v),n.set(v,B),v.addEventListener("dispose",C),m(B.texture,v.mapping)}else return null}}return v}function u(v){if(v&&v.isTexture&&v.isPMREMTexture!==!0){const M=v.mapping,L=M===Mn||M===xn,B=M===ln||M===en;if(L||B){let f=t.get(v);const l=f!==void 0?f.texture.pmremVersion:0;if(v.isRenderTargetTexture&&v.pmremVersion!==l)return i===null&&(i=new _n(e)),f=L?i.fromEquirectangular(v,f):i.fromCubemap(v,f),f.texture.pmremVersion=v.pmremVersion,t.set(v,f),f.texture;if(f!==void 0)return f.texture;{const g=v.image;return L&&g&&g.height>0||B&&g&&I(g)?(i===null&&(i=new _n(e)),f=L?i.fromEquirectangular(v):i.fromCubemap(v),f.texture.pmremVersion=v.pmremVersion,t.set(v,f),v.addEventListener("dispose",X),f.texture):null}}}return v}function m(v,M){return M===Mn?v.mapping=ln:M===xn&&(v.mapping=en),v}function I(v){let M=0;const L=6;for(let B=0;B<L;B++)v[B]!==void 0&&M++;return M===L}function C(v){const M=v.target;M.removeEventListener("dispose",C);const L=n.get(M);L!==void 0&&(n.delete(M),L.dispose())}function X(v){const M=v.target;M.removeEventListener("dispose",X);const L=t.get(M);L!==void 0&&(t.delete(M),L.dispose())}function F(){n=new WeakMap,t=new WeakMap,i!==null&&(i.dispose(),i=null)}return{get:c,dispose:F}}function mc(e){const n={};function t(i){if(n[i]!==void 0)return n[i];const c=e.getExtension(i);return n[i]=c,c}return{has:function(i){return t(i)!==null},init:function(){t("EXT_color_buffer_float"),t("WEBGL_clip_cull_distance"),t("OES_texture_float_linear"),t("EXT_color_buffer_half_float"),t("WEBGL_multisampled_render_to_texture"),t("WEBGL_render_shared_exponent")},get:function(i){const c=t(i);return c===null&&mr("WebGLRenderer: "+i+" extension not supported."),c}}}function _c(e,n,t,i){const c={},s=new WeakMap,u=new FinalizationRegistry(L=>delete c[L]);function m(L){I(L.target)}function I(L){L.index!==null&&n.remove(L.index);for(const f in L.attributes)n.remove(L.attributes[f]);L.removeEventListener("dispose",m),delete c[L.id],u.unregister(L);const B=s.get(L);B&&(n.remove(B),s.delete(L)),i.releaseStatesOfGeometry(L),L.isInstancedBufferGeometry===!0&&delete L._maxInstanceCount,t.memory.geometries--}function C(L,B){return c[B.id]!==void 0||(B.addEventListener("dispose",m),c[B.id]=new WeakRef(B),u.register(B,B.id,B),t.memory.geometries++),B}function X(L){const B=L.attributes;for(const f in B)n.update(B[f],e.ARRAY_BUFFER)}function F(L){const B=L.index,f=L.attributes.position;let l=0;if(f===void 0)return;const g=B!==null?B.array.length:f.array.length/3-1,U=f.count>=65535?_r:gr,S=new U(Math.ceil(g/3)*6,1),R=S.array;if(B!==null){const o=B.array;l=B.version;for(let p=0,q=0;p<g;p+=3){const D=o[p+0],N=o[p+1],z=o[p+2];R[q++]=D,R[q++]=N,R[q++]=N,R[q++]=z,R[q++]=z,R[q++]=D}}else{l=f.version;for(let o=0,p=0;o<g;o+=3){const q=o+0,D=o+1,N=o+2;R[p++]=q,R[p++]=D,R[p++]=D,R[p++]=N,R[p++]=N,R[p++]=q}}S.version=l;const x=s.get(L);x&&n.remove(x),s.set(L,S)}function v(L){const B=s.get(L);if(B){const f=L.index;f!==null&&B.version<f.version&&F(L)}else F(L);return s.get(L)}function M(){for(const L in c){const B=c[L].deref();B!==void 0&&I(B)}}return{get:C,update:X,getWireframeAttribute:v,dispose:M}}function gc(e,n,t){let i;function c(F){i=F}let s,u;function m(F){s=F.type,u=F.bytesPerElement}function I(F,v){e.drawElements(i,v,s,F*u),t.update(v,i,1)}function C(F,v,M){M!==0&&(e.drawElementsInstanced(i,v,s,F*u,M),t.update(v,i,M))}function X(F,v,M){if(M===0)return;n.get("WEBGL_multi_draw").multiDrawElementsWEBGL(i,v,0,s,F,0,M);let B=0;for(let f=0;f<M;f++)B+=v[f];t.update(B,i,1)}this.setMode=c,this.setIndex=m,this.render=I,this.renderInstances=C,this.renderMultiDraw=X}function vc(e){const n={geometries:0,textures:0},t={frame:0,calls:0,triangles:0,points:0,lines:0};function i(s,u,m){switch(t.calls++,u){case e.TRIANGLES:t.triangles+=m*(s/3);break;case e.LINES:t.lines+=m*(s/2);break;case e.LINE_STRIP:t.lines+=m*(s-1);break;case e.LINE_LOOP:t.lines+=m*s;break;case e.POINTS:t.points+=m*s;break;default:Ze("WebGLInfo: Unknown draw mode:",u);break}}function c(){t.calls=0,t.triangles=0,t.points=0,t.lines=0}return{memory:n,render:t,programs:null,autoReset:!0,reset:c,update:i}}function Sc(e,n,t){const i=new WeakMap,c=new mt;function s(u,m,I){const C=u.morphTargetInfluences,X=m.morphAttributes.position||m.morphAttributes.normal||m.morphAttributes.color,F=X!==void 0?X.length:0;let v=i.get(m);if(v===void 0||v.count!==F){let D=function(){p.dispose(),i.delete(m),m.removeEventListener("dispose",D)};var M=D;v!==void 0&&v.texture.dispose();const L=m.morphAttributes.position!==void 0,B=m.morphAttributes.normal!==void 0,f=m.morphAttributes.color!==void 0,l=m.morphAttributes.position||[],g=m.morphAttributes.normal||[],U=m.morphAttributes.color||[];let S=0;L===!0&&(S=1),B===!0&&(S=2),f===!0&&(S=3);let R=m.attributes.position.count*S,x=1;R>n.maxTextureSize&&(x=Math.ceil(R/n.maxTextureSize),R=n.maxTextureSize);const o=new Float32Array(R*x*4*F),p=new Kn(o,R,x,F);p.type=yt,p.needsUpdate=!0;const q=S*4;for(let N=0;N<F;N++){const z=l[N],Y=g[N],T=U[N],Z=R*x*4*N;for(let $=0;$<z.count;$++){const y=$*q;L===!0&&(c.fromBufferAttribute(z,$),o[Z+y+0]=c.x,o[Z+y+1]=c.y,o[Z+y+2]=c.z,o[Z+y+3]=0),B===!0&&(c.fromBufferAttribute(Y,$),o[Z+y+4]=c.x,o[Z+y+5]=c.y,o[Z+y+6]=c.z,o[Z+y+7]=0),f===!0&&(c.fromBufferAttribute(T,$),o[Z+y+8]=c.x,o[Z+y+9]=c.y,o[Z+y+10]=c.z,o[Z+y+11]=T.itemSize===4?c.w:1)}}v={count:F,texture:p,size:new ht(R,x)},i.set(m,v),m.addEventListener("dispose",D)}if(u.isInstancedMesh===!0&&u.morphTexture!==null)I.getUniforms().setValue(e,"morphTexture",u.morphTexture,t);else{let L=0;for(let f=0;f<C.length;f++)L+=C[f];const B=m.morphTargetsRelative?1:1-L;I.getUniforms().setValue(e,"morphTargetBaseInfluence",B),I.getUniforms().setValue(e,"morphTargetInfluences",C)}I.getUniforms().setValue(e,"morphTargetsTexture",v.texture,t),I.getUniforms().setValue(e,"morphTargetsTextureSize",v.size)}return{update:s}}function Ec(e,n,t,i,c){let s=new WeakMap;function u(C){const X=c.render.frame,F=C.geometry,v=n.get(C,F);if(s.get(v)!==X&&(n.update(v),s.set(v,X)),C.isInstancedMesh&&(C.hasEventListener("dispose",I)===!1&&C.addEventListener("dispose",I),s.get(C)!==X&&(t.update(C.instanceMatrix,e.ARRAY_BUFFER),C.instanceColor!==null&&t.update(C.instanceColor,e.ARRAY_BUFFER),s.set(C,X))),C.isSkinnedMesh){const M=C.skeleton;s.get(M)!==X&&(M.update(),s.set(M,X))}return v}function m(){s=new WeakMap}function I(C){const X=C.target;X.removeEventListener("dispose",I),i.releaseStatesOfObject(X),t.remove(X.instanceMatrix),X.instanceColor!==null&&t.remove(X.instanceColor)}return{update:u,dispose:m}}const Mc={[ti]:"LINEAR_TONE_MAPPING",[ei]:"REINHARD_TONE_MAPPING",[jn]:"CINEON_TONE_MAPPING",[Jn]:"ACES_FILMIC_TONE_MAPPING",[Qn]:"AGX_TONE_MAPPING",[$n]:"NEUTRAL_TONE_MAPPING",[Zn]:"CUSTOM_TONE_MAPPING"};function xc(e,n,t,i,c,s){const u=new bt(n,t,{type:e,depthBuffer:c,stencilBuffer:s,samples:i?4:0,storeMultisampledDepthBuffer:!1,storeMultisampledStencilBuffer:!1,resolveDepthBuffer:!1,resolveStencilBuffer:!1});let m=null,I=null,C=null;const X=new qn;X.setAttribute("position",new Yn([-1,3,0,-1,-1,0,3,-1,0],3)),X.setAttribute("uv",new Yn([0,2,0,0,2,0],2));const F=new vr({uniforms:{tDiffuse:{value:null}},vertexShader:`
			precision highp float;

			uniform mat4 modelViewMatrix;
			uniform mat4 projectionMatrix;

			attribute vec3 position;
			attribute vec2 uv;

			varying vec2 vUv;

			void main() {
				vUv = uv;
				gl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );
			}`,fragmentShader:`
			precision highp float;

			uniform sampler2D tDiffuse;

			varying vec2 vUv;

			#include <tonemapping_pars_fragment>
			#include <colorspace_pars_fragment>

			void main() {
				gl_FragColor = texture2D( tDiffuse, vUv );

				#ifdef LINEAR_TONE_MAPPING
					gl_FragColor.rgb = LinearToneMapping( gl_FragColor.rgb );
				#elif defined( REINHARD_TONE_MAPPING )
					gl_FragColor.rgb = ReinhardToneMapping( gl_FragColor.rgb );
				#elif defined( CINEON_TONE_MAPPING )
					gl_FragColor.rgb = CineonToneMapping( gl_FragColor.rgb );
				#elif defined( ACES_FILMIC_TONE_MAPPING )
					gl_FragColor.rgb = ACESFilmicToneMapping( gl_FragColor.rgb );
				#elif defined( AGX_TONE_MAPPING )
					gl_FragColor.rgb = AgXToneMapping( gl_FragColor.rgb );
				#elif defined( NEUTRAL_TONE_MAPPING )
					gl_FragColor.rgb = NeutralToneMapping( gl_FragColor.rgb );
				#elif defined( CUSTOM_TONE_MAPPING )
					gl_FragColor.rgb = CustomToneMapping( gl_FragColor.rgb );
				#endif

				#ifdef SRGB_TRANSFER
					gl_FragColor = sRGBTransferOETF( gl_FragColor );
				#endif
			}`,depthTest:!1,depthWrite:!1}),v=new Gt(X,F),M=new Sr(-1,1,1,-1,0,1);let L=null,B=null,f=!1,l,g=null,U=[],S=!1;this.setSize=function(R,x){u.setSize(R,x),m!==null&&m.setSize(R,x),I!==null&&I.setSize(R,x);for(let o=0;o<U.length;o++){const p=U[o];p.setSize&&p.setSize(R,x)}},this.setEffects=function(R){U=R,S=U.length>0&&U[0].isRenderPass===!0;const x=u.width,o=u.height;U.length>0&&m===null&&(m=new bt(x,o,{type:Dt,depthBuffer:!1,stencilBuffer:!1}),I=new bt(x,o,{type:Dt,depthBuffer:!1,stencilBuffer:!1}));const p=c===!0&&U.some(q=>q.needsDepthTexture===!0);p!==(u.depthTexture!==null)&&(p===!0&&C===null&&(C=new tn(x,o),s===!0&&(C.format=Vt,C.type=$t)),u.dispose(),u.depthTexture=p?C:null,u.resolveDepthBuffer=p,u.storeMultisampledDepthBuffer=p);for(let q=0;q<U.length;q++){const D=U[q];D.setSize&&D.setSize(x,o),p&&D.needsDepthTexture===!0&&D.setDepthTexture&&D.setDepthTexture(C)}},this.begin=function(R,x){if(f||R.toneMapping===Ut&&U.length===0)return!1;if(g=x,x!==null){const o=x.width,p=x.height;(u.width!==o||u.height!==p)&&this.setSize(o,p)}return S===!1&&R.setRenderTarget(u),l=R.toneMapping,R.toneMapping=Ut,!0},this.hasRenderPass=function(){return S},this.end=function(R,x){R.toneMapping=l,f=!0;let o=u,p=m;for(let q=0;q<U.length;q++){const D=U[q];D.enabled!==!1&&(D.render(R,p,o,x),D.needsSwap!==!1&&(o=p,p=p===m?I:m))}if(L!==R.outputColorSpace||B!==R.toneMapping){L=R.outputColorSpace,B=R.toneMapping,F.defines={},Je.getTransfer(L)===Xe&&(F.defines.SRGB_TRANSFER="");const q=Mc[B];q&&(F.defines[q]=""),F.needsUpdate=!0}F.uniforms.tDiffuse.value=o.texture,R.setRenderTarget(g),R.render(v,M),g=null,f=!1},this.isCompositing=function(){return f},this.dispose=function(){u.dispose(),m!==null&&m.dispose(),I!==null&&I.dispose(),X.dispose(),F.dispose()}}const Ba=new Mr,Ga=new tn(1,1),Ha=new ii(1),Va=new Kn,Wa=new Er,ka=new zn,za=[],Xa=[],Ka=new Float32Array(16),qa=new Float32Array(9),Ya=new Float32Array(4);function on(e,n,t){const i=e[0];if(i<=0||i>0)return e;const c=n*t;let s=za[c];if(s===void 0&&(s=new Float32Array(c),za[c]=s),n!==0){i.toArray(s,0);for(let u=1,m=0;u!==n;++u)m+=t,e[u].toArray(s,m)}return s}function rt(e,n){if(e.length!==n.length)return!1;for(let t=0,i=e.length;t<i;t++)if(e[t]!==n[t])return!1;return!0}function at(e,n){for(let t=0,i=n.length;t<i;t++)e[t]=n[t]}function yn(e,n){let t=Xa[n];t===void 0&&(t=new Int32Array(n),Xa[n]=t);for(let i=0;i!==n;++i)t[i]=e.allocateTextureUnit();return t}function Tc(e,n){const t=this.cache;t[0]!==n&&(e.uniform1f(this.addr,n),t[0]=n)}function Ac(e,n){const t=this.cache;if(n.x!==void 0)(t[0]!==n.x||t[1]!==n.y)&&(e.uniform2f(this.addr,n.x,n.y),t[0]=n.x,t[1]=n.y);else{if(rt(t,n))return;e.uniform2fv(this.addr,n),at(t,n)}}function Rc(e,n){const t=this.cache;if(n.x!==void 0)(t[0]!==n.x||t[1]!==n.y||t[2]!==n.z)&&(e.uniform3f(this.addr,n.x,n.y,n.z),t[0]=n.x,t[1]=n.y,t[2]=n.z);else if(n.r!==void 0)(t[0]!==n.r||t[1]!==n.g||t[2]!==n.b)&&(e.uniform3f(this.addr,n.r,n.g,n.b),t[0]=n.r,t[1]=n.g,t[2]=n.b);else{if(rt(t,n))return;e.uniform3fv(this.addr,n),at(t,n)}}function Cc(e,n){const t=this.cache;if(n.x!==void 0)(t[0]!==n.x||t[1]!==n.y||t[2]!==n.z||t[3]!==n.w)&&(e.uniform4f(this.addr,n.x,n.y,n.z,n.w),t[0]=n.x,t[1]=n.y,t[2]=n.z,t[3]=n.w);else{if(rt(t,n))return;e.uniform4fv(this.addr,n),at(t,n)}}function bc(e,n){const t=this.cache,i=n.elements;if(i===void 0){if(rt(t,n))return;e.uniformMatrix2fv(this.addr,!1,n),at(t,n)}else{if(rt(t,i))return;Ya.set(i),e.uniformMatrix2fv(this.addr,!1,Ya),at(t,i)}}function Pc(e,n){const t=this.cache,i=n.elements;if(i===void 0){if(rt(t,n))return;e.uniformMatrix3fv(this.addr,!1,n),at(t,n)}else{if(rt(t,i))return;qa.set(i),e.uniformMatrix3fv(this.addr,!1,qa),at(t,i)}}function Lc(e,n){const t=this.cache,i=n.elements;if(i===void 0){if(rt(t,n))return;e.uniformMatrix4fv(this.addr,!1,n),at(t,n)}else{if(rt(t,i))return;Ka.set(i),e.uniformMatrix4fv(this.addr,!1,Ka),at(t,i)}}function Dc(e,n){const t=this.cache;t[0]!==n&&(e.uniform1i(this.addr,n),t[0]=n)}function Uc(e,n){const t=this.cache;if(n.x!==void 0)(t[0]!==n.x||t[1]!==n.y)&&(e.uniform2i(this.addr,n.x,n.y),t[0]=n.x,t[1]=n.y);else{if(rt(t,n))return;e.uniform2iv(this.addr,n),at(t,n)}}function wc(e,n){const t=this.cache;if(n.x!==void 0)(t[0]!==n.x||t[1]!==n.y||t[2]!==n.z)&&(e.uniform3i(this.addr,n.x,n.y,n.z),t[0]=n.x,t[1]=n.y,t[2]=n.z);else{if(rt(t,n))return;e.uniform3iv(this.addr,n),at(t,n)}}function Nc(e,n){const t=this.cache;if(n.x!==void 0)(t[0]!==n.x||t[1]!==n.y||t[2]!==n.z||t[3]!==n.w)&&(e.uniform4i(this.addr,n.x,n.y,n.z,n.w),t[0]=n.x,t[1]=n.y,t[2]=n.z,t[3]=n.w);else{if(rt(t,n))return;e.uniform4iv(this.addr,n),at(t,n)}}function Ic(e,n){const t=this.cache;t[0]!==n&&(e.uniform1ui(this.addr,n),t[0]=n)}function yc(e,n){const t=this.cache;if(n.x!==void 0)(t[0]!==n.x||t[1]!==n.y)&&(e.uniform2ui(this.addr,n.x,n.y),t[0]=n.x,t[1]=n.y);else{if(rt(t,n))return;e.uniform2uiv(this.addr,n),at(t,n)}}function Fc(e,n){const t=this.cache;if(n.x!==void 0)(t[0]!==n.x||t[1]!==n.y||t[2]!==n.z)&&(e.uniform3ui(this.addr,n.x,n.y,n.z),t[0]=n.x,t[1]=n.y,t[2]=n.z);else{if(rt(t,n))return;e.uniform3uiv(this.addr,n),at(t,n)}}function Oc(e,n){const t=this.cache;if(n.x!==void 0)(t[0]!==n.x||t[1]!==n.y||t[2]!==n.z||t[3]!==n.w)&&(e.uniform4ui(this.addr,n.x,n.y,n.z,n.w),t[0]=n.x,t[1]=n.y,t[2]=n.z,t[3]=n.w);else{if(rt(t,n))return;e.uniform4uiv(this.addr,n),at(t,n)}}function Fn(e){return e.version===0&&(e.compareFunction=ni,e.needsUpdate=!0),e}function Bc(e,n,t){const i=this.cache,c=t.allocateTextureUnit();i[0]!==c&&(e.uniform1i(this.addr,c),i[0]=c);let s;this.type===e.SAMPLER_2D_SHADOW?s=Fn(Ga):s=Ba,t.setTexture2D(n||s,c)}function Gc(e,n,t){const i=this.cache,c=t.allocateTextureUnit();i[0]!==c&&(e.uniform1i(this.addr,c),i[0]=c),t.setTexture3D(n||Wa,c)}function Hc(e,n,t){const i=this.cache,c=t.allocateTextureUnit();i[0]!==c&&(e.uniform1i(this.addr,c),i[0]=c);let s;this.type===e.SAMPLER_CUBE_SHADOW?s=Fn(Ha):s=ka,t.setTextureCube(n||s,c)}function Vc(e,n,t){const i=this.cache,c=t.allocateTextureUnit();i[0]!==c&&(e.uniform1i(this.addr,c),i[0]=c),t.setTexture2DArray(n||Va,c)}function Wc(e){switch(e){case 5126:return Tc;case 35664:return Ac;case 35665:return Rc;case 35666:return Cc;case 35674:return bc;case 35675:return Pc;case 35676:return Lc;case 5124:case 35670:return Dc;case 35667:case 35671:return Uc;case 35668:case 35672:return wc;case 35669:case 35673:return Nc;case 5125:return Ic;case 36294:return yc;case 36295:return Fc;case 36296:return Oc;case 35678:case 36198:case 36298:case 36306:case 35682:return Bc;case 35679:case 36299:case 36307:return Gc;case 35680:case 36300:case 36308:case 36293:return Hc;case 36289:case 36303:case 36311:case 36292:return Vc}}function kc(e,n){e.uniform1fv(this.addr,n)}function zc(e,n){const t=on(n,this.size,2);e.uniform2fv(this.addr,t)}function Xc(e,n){const t=on(n,this.size,3);e.uniform3fv(this.addr,t)}function Kc(e,n){const t=on(n,this.size,4);e.uniform4fv(this.addr,t)}function qc(e,n){const t=on(n,this.size,4);e.uniformMatrix2fv(this.addr,!1,t)}function Yc(e,n){const t=on(n,this.size,9);e.uniformMatrix3fv(this.addr,!1,t)}function Zc(e,n){const t=on(n,this.size,16);e.uniformMatrix4fv(this.addr,!1,t)}function $c(e,n){e.uniform1iv(this.addr,n)}function Qc(e,n){e.uniform2iv(this.addr,n)}function Jc(e,n){e.uniform3iv(this.addr,n)}function jc(e,n){e.uniform4iv(this.addr,n)}function ef(e,n){e.uniform1uiv(this.addr,n)}function tf(e,n){e.uniform2uiv(this.addr,n)}function nf(e,n){e.uniform3uiv(this.addr,n)}function rf(e,n){e.uniform4uiv(this.addr,n)}function af(e,n,t){const i=this.cache,c=n.length,s=yn(t,c);rt(i,s)||(e.uniform1iv(this.addr,s),at(i,s));let u;this.type===e.SAMPLER_2D_SHADOW?u=Fn(Ga):u=Ba;for(let m=0;m!==c;++m)t.setTexture2D(n[m]||u,s[m])}function of(e,n,t){const i=this.cache,c=n.length,s=yn(t,c);rt(i,s)||(e.uniform1iv(this.addr,s),at(i,s));for(let u=0;u!==c;++u)t.setTexture3D(n[u]||Wa,s[u])}function sf(e,n,t){const i=this.cache,c=n.length,s=yn(t,c);rt(i,s)||(e.uniform1iv(this.addr,s),at(i,s));let u;this.type===e.SAMPLER_CUBE_SHADOW?u=Fn(Ha):u=ka;for(let m=0;m!==c;++m)t.setTextureCube(n[m]||u,s[m])}function lf(e,n,t){const i=this.cache,c=n.length,s=yn(t,c);rt(i,s)||(e.uniform1iv(this.addr,s),at(i,s));for(let u=0;u!==c;++u)t.setTexture2DArray(n[u]||Va,s[u])}function cf(e){switch(e){case 5126:return kc;case 35664:return zc;case 35665:return Xc;case 35666:return Kc;case 35674:return qc;case 35675:return Yc;case 35676:return Zc;case 5124:case 35670:return $c;case 35667:case 35671:return Qc;case 35668:case 35672:return Jc;case 35669:case 35673:return jc;case 5125:return ef;case 36294:return tf;case 36295:return nf;case 36296:return rf;case 35678:case 36198:case 36298:case 36306:case 35682:return af;case 35679:case 36299:case 36307:return of;case 35680:case 36300:case 36308:case 36293:return sf;case 36289:case 36303:case 36311:case 36292:return lf}}class ff{constructor(n,t,i){this.id=n,this.addr=i,this.cache=[],this.type=t.type,this.setValue=Wc(t.type)}}class df{constructor(n,t,i){this.id=n,this.addr=i,this.cache=[],this.type=t.type,this.size=t.size,this.setValue=cf(t.type)}}class uf{constructor(n){this.id=n,this.seq=[],this.map={}}setValue(n,t,i){const c=this.seq;for(let s=0,u=c.length;s!==u;++s){const m=c[s];m.setValue(n,t[m.id],i)}}}const ji=/(\w+)(\])?(\[|\.)?/g;function Za(e,n){e.seq.push(n),e.map[n.id]=n}function pf(e,n,t){const i=e.name,c=i.length;for(ji.lastIndex=0;;){const s=ji.exec(i),u=ji.lastIndex;let m=s[1];const I=s[2]==="]",C=s[3];if(I&&(m=m|0),C===void 0||C==="["&&u+2===c){Za(t,C===void 0?new ff(m,e,n):new df(m,e,n));break}else{let F=t.map[m];F===void 0&&(F=new uf(m),Za(t,F)),t=F}}}class On{constructor(n,t){this.seq=[],this.map={};const i=n.getProgramParameter(t,n.ACTIVE_UNIFORMS);for(let u=0;u<i;++u){const m=n.getActiveUniform(t,u),I=n.getUniformLocation(t,m.name);pf(m,I,this)}const c=[],s=[];for(const u of this.seq)u.type===n.SAMPLER_2D_SHADOW||u.type===n.SAMPLER_CUBE_SHADOW||u.type===n.SAMPLER_2D_ARRAY_SHADOW?c.push(u):s.push(u);c.length>0&&(this.seq=c.concat(s))}setValue(n,t,i,c){const s=this.map[t];s!==void 0&&s.setValue(n,i,c)}setOptional(n,t,i){const c=t[i];c!==void 0&&this.setValue(n,i,c)}static upload(n,t,i,c){for(let s=0,u=t.length;s!==u;++s){const m=t[s],I=i[m.id];I.needsUpdate!==!1&&m.setValue(n,I.value,c)}}static seqWithValue(n,t){const i=[];for(let c=0,s=n.length;c!==s;++c){const u=n[c];u.id in t&&i.push(u)}return i}}function $a(e,n,t){const i=e.createShader(n);return e.shaderSource(i,t),e.compileShader(i),i}const hf=37297;let mf=0;function _f(e,n){const t=e.split(`
`),i=[],c=Math.max(n-6,0),s=Math.min(n+6,t.length);for(let u=c;u<s;u++){const m=u+1;i.push(`${m===n?">":" "} ${m}: ${t[u]}`)}return i.join(`
`)}const Qa=new Oe;function gf(e){Je._getMatrix(Qa,Je.workingColorSpace,e);const n=`mat3( ${Qa.elements.map(t=>t.toFixed(4))} )`;switch(Je.getTransfer(e)){case ai:return[n,"LinearTransferOETF"];case Xe:return[n,"sRGBTransferOETF"];default:return He("WebGLProgram: Unsupported color space: ",e),[n,"LinearTransferOETF"]}}function Ja(e,n,t){const i=e.getShaderParameter(n,e.COMPILE_STATUS),s=(e.getShaderInfoLog(n)||"").trim();if(i&&s==="")return"";const u=/ERROR: 0:(\d+)/.exec(s);if(u){const m=parseInt(u[1]);return t.toUpperCase()+`

`+s+`

`+_f(e.getShaderSource(n),m)}else return s}function vf(e,n){const t=gf(n);return[`vec4 ${e}( vec4 value ) {`,`	return ${t[1]}( vec4( ${t[0]} * value.rgb, value.a ) );`,"}"].join(`
`)}const Sf={[ti]:"Linear",[ei]:"Reinhard",[jn]:"Cineon",[Jn]:"ACESFilmic",[Qn]:"AgX",[$n]:"Neutral",[Zn]:"Custom"};function Ef(e,n){const t=Sf[n];return t===void 0?(He("WebGLProgram: Unsupported toneMapping:",n),"vec3 "+e+"( vec3 color ) { return LinearToneMapping( color ); }"):"vec3 "+e+"( vec3 color ) { return "+t+"ToneMapping( color ); }"}const Bn=new We;function Mf(){Je.getLuminanceCoefficients(Bn);const e=Bn.x.toFixed(4),n=Bn.y.toFixed(4),t=Bn.z.toFixed(4);return["float luminance( const in vec3 rgb ) {",`	const vec3 weights = vec3( ${e}, ${n}, ${t} );`,"	return dot( weights, rgb );","}"].join(`
`)}function xf(e){return[e.extensionClipCullDistance?"#extension GL_ANGLE_clip_cull_distance : require":"",e.extensionMultiDraw?"#extension GL_ANGLE_multi_draw : require":""].filter(hn).join(`
`)}function Tf(e){const n=[];for(const t in e){const i=e[t];i!==!1&&n.push("#define "+t+" "+i)}return n.join(`
`)}function Af(e,n){const t={},i=e.getProgramParameter(n,e.ACTIVE_ATTRIBUTES);for(let c=0;c<i;c++){const s=e.getActiveAttrib(n,c),u=s.name;let m=1;s.type===e.FLOAT_MAT2&&(m=2),s.type===e.FLOAT_MAT3&&(m=3),s.type===e.FLOAT_MAT4&&(m=4),t[u]={type:s.type,location:e.getAttribLocation(n,u),locationSize:m}}return t}function hn(e){return e!==""}function ja(e,n){const t=n.numSpotLightShadows+n.numSpotLightMaps-n.numSpotLightShadowsWithMaps;return e.replace(/NUM_SUN_LIGHTS/g,n.numSunLights).replace(/NUM_DIR_LIGHTS/g,n.numDirLights).replace(/NUM_SPOT_LIGHTS/g,n.numSpotLights).replace(/NUM_SPOT_LIGHT_MAPS/g,n.numSpotLightMaps).replace(/NUM_SPOT_LIGHT_COORDS/g,t).replace(/NUM_RECT_AREA_LIGHTS/g,n.numRectAreaLights).replace(/NUM_POINT_LIGHTS/g,n.numPointLights).replace(/NUM_HEMI_LIGHTS/g,n.numHemiLights).replace(/NUM_SUN_LIGHT_SHADOWS/g,n.numSunLightShadows).replace(/NUM_DIR_LIGHT_SHADOWS/g,n.numDirLightShadows).replace(/NUM_SPOT_LIGHT_SHADOWS_WITH_MAPS/g,n.numSpotLightShadowsWithMaps).replace(/NUM_SPOT_LIGHT_SHADOWS/g,n.numSpotLightShadows).replace(/NUM_POINT_LIGHT_SHADOWS/g,n.numPointLightShadows).replace(/NUM_LIGHT_PROBE_GRIDS/g,n.numLightProbeGrids)}function eo(e,n){return e.replace(/NUM_CLIPPING_PLANES/g,n.numClippingPlanes).replace(/UNION_CLIPPING_PLANES/g,n.numClippingPlanes-n.numClipIntersection)}const Rf=/^[ \t]*#include +<([\w\d./]+)>/gm;function er(e){return e.replace(Rf,Cf)}function Cf(e,n){const t=De[n];if(t===void 0)throw new Error("THREE.WebGLProgram: Can not resolve #include <"+n+">");return er(t)}const bf=/#pragma unroll_loop_start\s+for\s*\(\s*int\s+i\s*=\s*(\d+)\s*;\s*i\s*<\s*(\d+)\s*;\s*i\s*\+\+\s*\)\s*{([\s\S]+?)}\s+#pragma unroll_loop_end/g;function to(e){return e.replace(bf,Pf)}function Pf(e,n,t,i){let c="";for(let s=parseInt(n);s<parseInt(t);s++)c+=i.replace(/\[\s*i\s*\]/g,"[ "+s+" ]").replace(/UNROLLED_LOOP_INDEX/g,s);return c}function no(e){let n=`precision ${e.precision} float;
	precision ${e.precision} int;
	precision ${e.precision} sampler2D;
	precision ${e.precision} samplerCube;
	precision ${e.precision} sampler3D;
	precision ${e.precision} sampler2DArray;
	precision ${e.precision} sampler2DShadow;
	precision ${e.precision} samplerCubeShadow;
	precision ${e.precision} sampler2DArrayShadow;
	precision ${e.precision} isampler2D;
	precision ${e.precision} isampler3D;
	precision ${e.precision} isamplerCube;
	precision ${e.precision} isampler2DArray;
	precision ${e.precision} usampler2D;
	precision ${e.precision} usampler3D;
	precision ${e.precision} usamplerCube;
	precision ${e.precision} usampler2DArray;
	`;return e.precision==="highp"?n+=`
#define HIGH_PRECISION`:e.precision==="mediump"?n+=`
#define MEDIUM_PRECISION`:e.precision==="lowp"&&(n+=`
#define LOW_PRECISION`),n}const Lf={[cn]:"SHADOWMAP_TYPE_PCF",[nn]:"SHADOWMAP_TYPE_VSM"};function Df(e){return Lf[e.shadowMapType]||"SHADOWMAP_TYPE_BASIC"}const Uf={[en]:"ENVMAP_MODE_REFRACTION"};function wf(e){return e.envMap===!1?"ENVMAP_MODE_REFLECTION":Uf[e.envMapMode]||"ENVMAP_MODE_REFLECTION"}const Nf={[Ar]:"ENVMAP_BLENDING_MULTIPLY",[Tr]:"ENVMAP_BLENDING_MIX",[xr]:"ENVMAP_BLENDING_ADD"};function If(e){return e.envMap===!1?"ENVMAP_BLENDING_NONE":Nf[e.combine]||"ENVMAP_BLENDING_NONE"}function yf(e,n,t,i){const c=e.getContext(),s=t.defines;let u=t.vertexShader,m=t.fragmentShader;const I=Df(t),C=t.envMapPMREM===!0?"ENVMAP_TYPE_PMREM":"ENVMAP_TYPE_CUBE",X=wf(t),F=If(t),v=xf(t),M=Tf(s),L=c.createProgram();let B,f,l=t.glslVersion?"#version "+t.glslVersion+`
`:"";t.isRawShaderMaterial?(B=["#define SHADER_TYPE "+t.shaderType,"#define SHADER_NAME "+t.shaderName,M].filter(hn).join(`
`),B.length>0&&(B+=`
`),f=["#define SHADER_TYPE "+t.shaderType,"#define SHADER_NAME "+t.shaderName,M].filter(hn).join(`
`),f.length>0&&(f+=`
`)):(B=[no(t),"#define SHADER_TYPE "+t.shaderType,"#define SHADER_NAME "+t.shaderName,M,t.extensionClipCullDistance?"#define USE_CLIP_DISTANCE":"",t.batching?"#define USE_BATCHING":"",t.batchingColor?"#define USE_BATCHING_COLOR":"",t.instancing?"#define USE_INSTANCING":"",t.instancingColor?"#define USE_INSTANCING_COLOR":"",t.instancingMorph?"#define USE_INSTANCING_MORPH":"",t.useFog&&t.fog?"#define USE_FOG":"",t.useFog&&t.fogExp2?"#define FOG_EXP2":"",t.map?"#define USE_MAP":"",t.envMap?"#define USE_ENVMAP":"",t.envMap?"#define "+X:"",t.lightMap?"#define USE_LIGHTMAP":"",t.aoMap?"#define USE_AOMAP":"",t.bumpMap?"#define USE_BUMPMAP":"",t.normalMap?"#define USE_NORMALMAP":"",t.normalMapObjectSpace?"#define USE_NORMALMAP_OBJECTSPACE":"",t.normalMapTangentSpace?"#define USE_NORMALMAP_TANGENTSPACE":"",t.displacementMap?"#define USE_DISPLACEMENTMAP":"",t.emissiveMap?"#define USE_EMISSIVEMAP":"",t.anisotropy?"#define USE_ANISOTROPY":"",t.anisotropyMap?"#define USE_ANISOTROPYMAP":"",t.clearcoatMap?"#define USE_CLEARCOATMAP":"",t.clearcoatRoughnessMap?"#define USE_CLEARCOAT_ROUGHNESSMAP":"",t.clearcoatNormalMap?"#define USE_CLEARCOAT_NORMALMAP":"",t.diffuseRoughnessMap?"#define USE_DIFFUSE_ROUGHNESSMAP":"",t.iridescenceMap?"#define USE_IRIDESCENCEMAP":"",t.iridescenceThicknessMap?"#define USE_IRIDESCENCE_THICKNESSMAP":"",t.specularMap?"#define USE_SPECULARMAP":"",t.specularColorMap?"#define USE_SPECULAR_COLORMAP":"",t.specularIntensityMap?"#define USE_SPECULAR_INTENSITYMAP":"",t.roughnessMap?"#define USE_ROUGHNESSMAP":"",t.metalnessMap?"#define USE_METALNESSMAP":"",t.alphaMap?"#define USE_ALPHAMAP":"",t.alphaHash?"#define USE_ALPHAHASH":"",t.transmission?"#define USE_TRANSMISSION":"",t.transmissionMap?"#define USE_TRANSMISSIONMAP":"",t.thicknessMap?"#define USE_THICKNESSMAP":"",t.sheenColorMap?"#define USE_SHEEN_COLORMAP":"",t.sheenRoughnessMap?"#define USE_SHEEN_ROUGHNESSMAP":"",t.mapUv?"#define MAP_UV "+t.mapUv:"",t.alphaMapUv?"#define ALPHAMAP_UV "+t.alphaMapUv:"",t.lightMapUv?"#define LIGHTMAP_UV "+t.lightMapUv:"",t.aoMapUv?"#define AOMAP_UV "+t.aoMapUv:"",t.emissiveMapUv?"#define EMISSIVEMAP_UV "+t.emissiveMapUv:"",t.bumpMapUv?"#define BUMPMAP_UV "+t.bumpMapUv:"",t.normalMapUv?"#define NORMALMAP_UV "+t.normalMapUv:"",t.displacementMapUv?"#define DISPLACEMENTMAP_UV "+t.displacementMapUv:"",t.metalnessMapUv?"#define METALNESSMAP_UV "+t.metalnessMapUv:"",t.roughnessMapUv?"#define ROUGHNESSMAP_UV "+t.roughnessMapUv:"",t.anisotropyMapUv?"#define ANISOTROPYMAP_UV "+t.anisotropyMapUv:"",t.clearcoatMapUv?"#define CLEARCOATMAP_UV "+t.clearcoatMapUv:"",t.clearcoatNormalMapUv?"#define CLEARCOAT_NORMALMAP_UV "+t.clearcoatNormalMapUv:"",t.clearcoatRoughnessMapUv?"#define CLEARCOAT_ROUGHNESSMAP_UV "+t.clearcoatRoughnessMapUv:"",t.diffuseRoughnessMapUv?"#define DIFFUSE_ROUGHNESSMAP_UV "+t.diffuseRoughnessMapUv:"",t.iridescenceMapUv?"#define IRIDESCENCEMAP_UV "+t.iridescenceMapUv:"",t.iridescenceThicknessMapUv?"#define IRIDESCENCE_THICKNESSMAP_UV "+t.iridescenceThicknessMapUv:"",t.sheenColorMapUv?"#define SHEEN_COLORMAP_UV "+t.sheenColorMapUv:"",t.sheenRoughnessMapUv?"#define SHEEN_ROUGHNESSMAP_UV "+t.sheenRoughnessMapUv:"",t.specularMapUv?"#define SPECULARMAP_UV "+t.specularMapUv:"",t.specularColorMapUv?"#define SPECULAR_COLORMAP_UV "+t.specularColorMapUv:"",t.specularIntensityMapUv?"#define SPECULAR_INTENSITYMAP_UV "+t.specularIntensityMapUv:"",t.transmissionMapUv?"#define TRANSMISSIONMAP_UV "+t.transmissionMapUv:"",t.thicknessMapUv?"#define THICKNESSMAP_UV "+t.thicknessMapUv:"",t.vertexTangents&&t.flatShading===!1?"#define USE_TANGENT":"",t.vertexNormals?"#define HAS_NORMAL":"",t.vertexColors?"#define USE_COLOR":"",t.vertexAlphas?"#define USE_COLOR_ALPHA":"",t.vertexUv1s?"#define USE_UV1":"",t.vertexUv2s?"#define USE_UV2":"",t.vertexUv3s?"#define USE_UV3":"",t.pointsUvs?"#define USE_POINTS_UV":"",t.flatShading?"#define FLAT_SHADED":"",t.skinning?"#define USE_SKINNING":"",t.morphTargets?"#define USE_MORPHTARGETS":"",t.morphNormals&&t.flatShading===!1?"#define USE_MORPHNORMALS":"",t.morphColors?"#define USE_MORPHCOLORS":"",t.morphTargetsCount>0?"#define MORPHTARGETS_TEXTURE_STRIDE "+t.morphTextureStride:"",t.morphTargetsCount>0?"#define MORPHTARGETS_COUNT "+t.morphTargetsCount:"",t.doubleSided?"#define DOUBLE_SIDED":"",t.flipSided?"#define FLIP_SIDED":"",t.shadowMapEnabled?"#define USE_SHADOWMAP":"",t.shadowMapEnabled?"#define "+I:"",t.sizeAttenuation?"#define USE_SIZEATTENUATION":"",t.numLightProbes>0?"#define USE_LIGHT_PROBES":"",t.logarithmicDepthBuffer?"#define USE_LOGARITHMIC_DEPTH_BUFFER":"",t.reversedDepthBuffer?"#define USE_REVERSED_DEPTH_BUFFER":"","uniform mat4 modelMatrix;","uniform mat4 modelViewMatrix;","uniform mat4 projectionMatrix;","uniform mat4 viewMatrix;","uniform mat3 normalMatrix;","uniform vec3 cameraPosition;","uniform bool isOrthographic;","#ifdef USE_INSTANCING","	attribute mat4 instanceMatrix;","#endif","#ifdef USE_INSTANCING_COLOR","	attribute vec3 instanceColor;","#endif","#ifdef USE_INSTANCING_MORPH","	uniform sampler2D morphTexture;","#endif","attribute vec3 position;","attribute vec3 normal;","attribute vec2 uv;","#ifdef USE_UV1","	attribute vec2 uv1;","#endif","#ifdef USE_UV2","	attribute vec2 uv2;","#endif","#ifdef USE_UV3","	attribute vec2 uv3;","#endif","#ifdef USE_TANGENT","	attribute vec4 tangent;","#endif","#if defined( USE_COLOR_ALPHA )","	attribute vec4 color;","#elif defined( USE_COLOR )","	attribute vec3 color;","#endif","#ifdef USE_SKINNING","	attribute vec4 skinIndex;","	attribute vec4 skinWeight;","#endif",`
`].filter(hn).join(`
`),f=[no(t),"#define SHADER_TYPE "+t.shaderType,"#define SHADER_NAME "+t.shaderName,M,t.useFog&&t.fog?"#define USE_FOG":"",t.useFog&&t.fogExp2?"#define FOG_EXP2":"",t.alphaToCoverage?"#define ALPHA_TO_COVERAGE":"",t.map?"#define USE_MAP":"",t.matcap?"#define USE_MATCAP":"",t.envMap?"#define USE_ENVMAP":"",t.envMap?"#define "+C:"",t.envMap?"#define "+X:"",t.envMap?"#define "+F:"",t.envMapPMREM?"#define ENVMAP_MAX_LOD "+t.envMapMaxLod+".0":"",t.envMapPMREM?"#define ENVMAP_SIZE "+t.envMapSize+".0":"",t.lightMap?"#define USE_LIGHTMAP":"",t.aoMap?"#define USE_AOMAP":"",t.bumpMap?"#define USE_BUMPMAP":"",t.normalMap?"#define USE_NORMALMAP":"",t.normalMapObjectSpace?"#define USE_NORMALMAP_OBJECTSPACE":"",t.normalMapTangentSpace?"#define USE_NORMALMAP_TANGENTSPACE":"",t.packedNormalMap?"#define USE_PACKED_NORMALMAP":"",t.emissiveMap?"#define USE_EMISSIVEMAP":"",t.anisotropy?"#define USE_ANISOTROPY":"",t.anisotropyMap?"#define USE_ANISOTROPYMAP":"",t.clearcoat?"#define USE_CLEARCOAT":"",t.clearcoatMap?"#define USE_CLEARCOATMAP":"",t.clearcoatRoughnessMap?"#define USE_CLEARCOAT_ROUGHNESSMAP":"",t.clearcoatNormalMap?"#define USE_CLEARCOAT_NORMALMAP":"",t.diffuseRoughness?"#define USE_DIFFUSE_ROUGHNESS":"",t.diffuseRoughnessMap?"#define USE_DIFFUSE_ROUGHNESSMAP":"",t.dispersion?"#define USE_DISPERSION":"",t.retroreflection?"#define USE_RETROREFLECTION":"",t.iridescence?"#define USE_IRIDESCENCE":"",t.iridescenceMap?"#define USE_IRIDESCENCEMAP":"",t.iridescenceThicknessMap?"#define USE_IRIDESCENCE_THICKNESSMAP":"",t.specularMap?"#define USE_SPECULARMAP":"",t.specularColorMap?"#define USE_SPECULAR_COLORMAP":"",t.specularIntensityMap?"#define USE_SPECULAR_INTENSITYMAP":"",t.roughnessMap?"#define USE_ROUGHNESSMAP":"",t.metalnessMap?"#define USE_METALNESSMAP":"",t.alphaMap?"#define USE_ALPHAMAP":"",t.alphaTest?"#define USE_ALPHATEST":"",t.alphaHash?"#define USE_ALPHAHASH":"",t.sheen?"#define USE_SHEEN":"",t.sheenColorMap?"#define USE_SHEEN_COLORMAP":"",t.sheenRoughnessMap?"#define USE_SHEEN_ROUGHNESSMAP":"",t.transmission?"#define USE_TRANSMISSION":"",t.transmissionMap?"#define USE_TRANSMISSIONMAP":"",t.thicknessMap?"#define USE_THICKNESSMAP":"",t.vertexTangents&&t.flatShading===!1?"#define USE_TANGENT":"",t.vertexColors||t.instancingColor?"#define USE_COLOR":"",t.vertexAlphas||t.batchingColor?"#define USE_COLOR_ALPHA":"",t.vertexUv1s?"#define USE_UV1":"",t.vertexUv2s?"#define USE_UV2":"",t.vertexUv3s?"#define USE_UV3":"",t.pointsUvs?"#define USE_POINTS_UV":"",t.gradientMap?"#define USE_GRADIENTMAP":"",t.flatShading?"#define FLAT_SHADED":"",t.doubleSided?"#define DOUBLE_SIDED":"",t.flipSided?"#define FLIP_SIDED":"",t.shadowMapEnabled?"#define USE_SHADOWMAP":"",t.shadowMapEnabled?"#define "+I:"",t.premultipliedAlpha?"#define PREMULTIPLIED_ALPHA":"",t.numLightProbes>0?"#define USE_LIGHT_PROBES":"",t.decodeVideoTexture?"#define DECODE_VIDEO_TEXTURE":"",t.decodeVideoTextureEmissive?"#define DECODE_VIDEO_TEXTURE_EMISSIVE":"",t.logarithmicDepthBuffer?"#define USE_LOGARITHMIC_DEPTH_BUFFER":"",t.reversedDepthBuffer?"#define USE_REVERSED_DEPTH_BUFFER":"","uniform mat4 viewMatrix;","uniform vec3 cameraPosition;","uniform bool isOrthographic;",t.toneMapping!==Ut?"#define TONE_MAPPING":"",t.toneMapping!==Ut?De.tonemapping_pars_fragment:"",t.toneMapping!==Ut?Ef("toneMapping",t.toneMapping):"",t.dithering?"#define DITHERING":"",t.opaque?"#define OPAQUE":"",De.colorspace_pars_fragment,vf("linearToOutputTexel",t.outputColorSpace),Mf(),t.useDepthPacking?"#define DEPTH_PACKING "+t.depthPacking:"",`
`].filter(hn).join(`
`)),u=er(u),u=ja(u,t),u=eo(u,t),m=er(m),m=ja(m,t),m=eo(m,t),u=to(u),m=to(m),t.isRawShaderMaterial!==!0&&(l=`#version 300 es
`,B=[v,"#define attribute in","#define varying out","#define texture2D texture"].join(`
`)+`
`+B,f=["#define varying in",t.glslVersion===ri?"":"layout(location = 0) out highp vec4 pc_fragColor;",t.glslVersion===ri?"":"#define gl_FragColor pc_fragColor","#define gl_FragDepthEXT gl_FragDepth","#define texture2D texture","#define textureCube texture","#define texture2DProj textureProj","#define texture2DLodEXT textureLod","#define texture2DProjLodEXT textureProjLod","#define textureCubeLodEXT textureLod","#define texture2DGradEXT textureGrad","#define texture2DProjGradEXT textureProjGrad","#define textureCubeGradEXT textureGrad"].join(`
`)+`
`+f);const g=l+B+u,U=l+f+m,S=$a(c,c.VERTEX_SHADER,g),R=$a(c,c.FRAGMENT_SHADER,U);c.attachShader(L,S),c.attachShader(L,R),t.index0AttributeName!==void 0?c.bindAttribLocation(L,0,t.index0AttributeName):t.hasPositionAttribute===!0&&c.bindAttribLocation(L,0,"position"),c.linkProgram(L);function x(D){if(e.debug.checkShaderErrors){const N=c.getProgramInfoLog(L)||"",z=c.getShaderInfoLog(S)||"",Y=c.getShaderInfoLog(R)||"",T=N.trim(),Z=z.trim(),$=Y.trim();let y=!0,fe=!0;if(c.getProgramParameter(L,c.LINK_STATUS)===!1)if(y=!1,typeof e.debug.onShaderError=="function")e.debug.onShaderError(c,L,S,R);else{const K=Ja(c,S,"vertex"),j=Ja(c,R,"fragment");Ze("WebGLProgram: Shader Error "+c.getError()+" - VALIDATE_STATUS "+c.getProgramParameter(L,c.VALIDATE_STATUS)+`

Material Name: `+D.name+`
Material Type: `+D.type+`

Program Info Log: `+T+`
`+K+`
`+j)}else T!==""?He("WebGLProgram: Program Info Log:",T):(Z===""||$==="")&&(fe=!1);fe&&(D.diagnostics={runnable:y,programLog:T,vertexShader:{log:Z,prefix:B},fragmentShader:{log:$,prefix:f}})}c.deleteShader(S),c.deleteShader(R),o=new On(c,L),p=Af(c,L)}let o;this.getUniforms=function(){return o===void 0&&x(this),o};let p;this.getAttributes=function(){return p===void 0&&x(this),p};let q=t.rendererExtensionParallelShaderCompile===!1;return this.isReady=function(){return q===!1&&(q=c.getProgramParameter(L,hf)),q},this.destroy=function(){i.releaseStatesOfProgram(this),c.deleteProgram(L),this.program=void 0},this.type=t.shaderType,this.name=t.shaderName,this.id=mf++,this.cacheKey=n,this.usedTimes=1,this.program=L,this.vertexShader=S,this.fragmentShader=R,this}let Ff=0;class Of{constructor(){this.shaderCache=new Map,this.materialCache=new Map}update(n,t,i){const c=this._getShaderCacheForMaterial(n);return c.has(t)===!1&&(c.add(t),t.usedTimes++),c.has(i)===!1&&(c.add(i),i.usedTimes++),this}remove(n){const t=this.materialCache.get(n);for(const i of t)i.usedTimes--,i.usedTimes===0&&this.shaderCache.delete(i.code);return this.materialCache.delete(n),this}getVertexShaderStage(n){return this._getShaderStage(n.vertexShader)}getFragmentShaderStage(n){return this._getShaderStage(n.fragmentShader)}dispose(){this.shaderCache.clear(),this.materialCache.clear()}_getShaderCacheForMaterial(n){const t=this.materialCache;let i=t.get(n);return i===void 0&&(i=new Set,t.set(n,i)),i}_getShaderStage(n){const t=this.shaderCache;let i=t.get(n);return i===void 0&&(i=new Bf(n),t.set(n,i)),i}}class Bf{constructor(n){this.id=Ff++,this.code=n,this.usedTimes=0}}function Gf(e){return e===Qt||e===Tn||e===An}function Hf(e,n,t,i,c,s){const u=new br,m=new Of,I=new Set,C=[],X=new Map,F=i.logarithmicDepthBuffer,v={MeshDepthMaterial:"depth",MeshDistanceMaterial:"distance",MeshNormalMaterial:"normal",MeshBasicMaterial:"basic",MeshLambertMaterial:"lambert",MeshPhongMaterial:"phong",MeshToonMaterial:"toon",MeshStandardMaterial:"physical",MeshPhysicalMaterial:"physical",MeshMatcapMaterial:"matcap",LineBasicMaterial:"basic",LineDashedMaterial:"dashed",PointsMaterial:"points",ShadowMaterial:"shadow",SpriteMaterial:"sprite"};function M(o){return I.add(o),o===0?"uv":`uv${o}`}function L(o,p,q,D,N,z){const Y=D.fog,T=N.geometry,Z=o.isMeshStandardMaterial||o.isMeshLambertMaterial||o.isMeshPhongMaterial?D.environment:null,$=o.isMeshStandardMaterial||o.isMeshLambertMaterial&&!o.envMap||o.isMeshPhongMaterial&&!o.envMap,y=n.get(o.envMap||Z,$),fe=v[o.type];let K=i.precision;o.precision!==null&&(K=i.getMaxPrecision(o.precision),K!==o.precision&&He("WebGLProgram.getParameters:",o.precision,"not supported, using",K,"instead."));const j=T.morphAttributes.position||T.morphAttributes.normal||T.morphAttributes.color,ie=j!==void 0?j.length:0;let Ae=0;T.morphAttributes.position!==void 0&&(Ae=1),T.morphAttributes.normal!==void 0&&(Ae=2),T.morphAttributes.color!==void 0&&(Ae=3);let Ne,W,he,Se;if(fe){const ue=Pt[fe];Ne=ue.vertexShader,W=ue.fragmentShader}else{Ne=o.vertexShader,W=o.fragmentShader;const ue=m.getVertexShaderStage(o),Ve=m.getFragmentShaderStage(o);m.update(o,ue,Ve),he=ue.id,Se=Ve.id}const Re=e.getRenderTarget(),me=e.state.buffers.depth.getReversed(),we=N.isInstancedMesh===!0,_t=N.isBatchedMesh===!0,Ce=!!o.map,qe=!!o.matcap,ke=!!y,Ie=ke&&y.isPMREMTexture===!0,ct=!!o.aoMap,nt=!!o.lightMap,gt=!!o.bumpMap&&o.wireframe===!1,je=!!o.normalMap,Lt=!!o.displacementMap,ot=!!o.emissiveMap,Et=!!o.metalnessMap,Mt=!!o.roughnessMap,xt=o.anisotropy>0,E=o.clearcoat>0,Tt=o.diffuseRoughness>0,$e=o.dispersion>0,Qe=o.retroreflectivity>0,ce=o.iridescence>0,it=o.sheen>0,Te=o.transmission>0,be=xt&&!!o.anisotropyMap,d=E&&!!o.clearcoatMap,r=E&&!!o.clearcoatNormalMap,b=E&&!!o.clearcoatRoughnessMap,Q=Tt&&!!o.diffuseRoughnessMap,k=ce&&!!o.iridescenceMap,te=ce&&!!o.iridescenceThicknessMap,se=it&&!!o.sheenColorMap,G=it&&!!o.sheenRoughnessMap,H=!!o.specularMap,re=!!o.specularColorMap,h=!!o.specularIntensityMap,V=Te&&!!o.transmissionMap,O=Te&&!!o.thicknessMap,ae=!!o.gradientMap,ee=!!o.alphaMap,ne=o.alphaTest>0,Me=!!o.alphaHash,Ue=!!o.extensions;let J=Ut;o.toneMapped&&(Re===null||Re.isXRRenderTarget===!0)&&(J=e.toneMapping);const xe=Ie?y.isCompressedCubeTexture?y.image[0].mipmaps:y.mipmaps:null,Le={shaderID:fe,shaderType:o.type,shaderName:o.name,vertexShader:Ne,fragmentShader:W,defines:o.defines,customVertexShaderID:he,customFragmentShaderID:Se,isRawShaderMaterial:o.isRawShaderMaterial===!0,glslVersion:o.glslVersion,precision:K,batching:_t,batchingColor:_t&&N._colorsTexture!==null,instancing:we,instancingColor:we&&N.instanceColor!==null,instancingMorph:we&&N.morphTexture!==null,outputColorSpace:Re===null?e.outputColorSpace:Re.isXRRenderTarget===!0?Re.texture.colorSpace:Je.workingColorSpace,alphaToCoverage:!!o.alphaToCoverage,map:Ce,matcap:qe,envMap:ke,envMapMode:ke&&y.mapping,envMapPMREM:Ie,envMapMaxLod:Ie?xe.length-1:null,envMapSize:Ie?xe[0].width:null,aoMap:ct,lightMap:nt,bumpMap:gt,normalMap:je,displacementMap:Lt,emissiveMap:ot,normalMapObjectSpace:je&&o.normalMapType===Cr,normalMapTangentSpace:je&&o.normalMapType===oi,packedNormalMap:je&&o.normalMapType===oi&&Gf(o.normalMap.format),metalnessMap:Et,roughnessMap:Mt,anisotropy:xt,anisotropyMap:be,clearcoat:E,clearcoatMap:d,clearcoatNormalMap:r,clearcoatRoughnessMap:b,diffuseRoughness:Tt,diffuseRoughnessMap:Q,dispersion:$e,retroreflection:Qe,iridescence:ce,iridescenceMap:k,iridescenceThicknessMap:te,sheen:it,sheenColorMap:se,sheenRoughnessMap:G,specularMap:H,specularColorMap:re,specularIntensityMap:h,transmission:Te,transmissionMap:V,thicknessMap:O,gradientMap:ae,opaque:o.transparent===!1&&o.blending===fn&&o.alphaToCoverage===!1,alphaMap:ee,alphaTest:ne,alphaHash:Me,combine:o.combine,mapUv:Ce&&M(o.map.channel),aoMapUv:ct&&M(o.aoMap.channel),lightMapUv:nt&&M(o.lightMap.channel),bumpMapUv:gt&&M(o.bumpMap.channel),normalMapUv:je&&M(o.normalMap.channel),displacementMapUv:Lt&&M(o.displacementMap.channel),emissiveMapUv:ot&&M(o.emissiveMap.channel),metalnessMapUv:Et&&M(o.metalnessMap.channel),roughnessMapUv:Mt&&M(o.roughnessMap.channel),anisotropyMapUv:be&&M(o.anisotropyMap.channel),clearcoatMapUv:d&&M(o.clearcoatMap.channel),clearcoatNormalMapUv:r&&M(o.clearcoatNormalMap.channel),clearcoatRoughnessMapUv:b&&M(o.clearcoatRoughnessMap.channel),diffuseRoughnessMapUv:Q&&M(o.diffuseRoughnessMap.channel),iridescenceMapUv:k&&M(o.iridescenceMap.channel),iridescenceThicknessMapUv:te&&M(o.iridescenceThicknessMap.channel),sheenColorMapUv:se&&M(o.sheenColorMap.channel),sheenRoughnessMapUv:G&&M(o.sheenRoughnessMap.channel),specularMapUv:H&&M(o.specularMap.channel),specularColorMapUv:re&&M(o.specularColorMap.channel),specularIntensityMapUv:h&&M(o.specularIntensityMap.channel),transmissionMapUv:V&&M(o.transmissionMap.channel),thicknessMapUv:O&&M(o.thicknessMap.channel),alphaMapUv:ee&&M(o.alphaMap.channel),vertexTangents:!!T.attributes.tangent&&(je||xt),vertexNormals:!!T.attributes.normal,vertexColors:o.vertexColors,vertexAlphas:o.vertexColors===!0&&!!T.attributes.color&&T.attributes.color.itemSize===4,pointsUvs:N.isPoints===!0&&!!T.attributes.uv&&(Ce||ee),fog:!!Y,useFog:o.fog===!0,fogExp2:!!Y&&Y.isFogExp2,flatShading:o.wireframe===!1&&(o.flatShading===!0||T.attributes.normal===void 0&&je===!1&&(o.isMeshLambertMaterial||o.isMeshPhongMaterial||o.isMeshStandardMaterial||o.isMeshPhysicalMaterial)),sizeAttenuation:o.sizeAttenuation===!0,logarithmicDepthBuffer:F,reversedDepthBuffer:me,skinning:N.isSkinnedMesh===!0,hasPositionAttribute:T.attributes.position!==void 0,morphTargets:T.morphAttributes.position!==void 0,morphNormals:T.morphAttributes.normal!==void 0,morphColors:T.morphAttributes.color!==void 0,morphTargetsCount:ie,morphTextureStride:Ae,numSunLights:p.sun.length,numDirLights:p.directional.length,numPointLights:p.point.length,numSpotLights:p.spot.length,numSpotLightMaps:p.spotLightMap.length,numRectAreaLights:p.rectArea.length,numHemiLights:p.hemi.length,numSunLightShadows:p.sunShadowMap.length,numDirLightShadows:p.directionalShadowMap.length,numPointLightShadows:p.pointShadowMap.length,numSpotLightShadows:p.spotShadowMap.length,numSpotLightShadowsWithMaps:p.numSpotLightShadowsWithMaps,numLightProbes:p.numLightProbes,numLightProbeGrids:z.filter(ue=>ue.texture!==null).length,numClippingPlanes:s.numPlanes,numClipIntersection:s.numIntersection,dithering:o.dithering,shadowMapEnabled:e.shadowMap.enabled&&q.length>0,shadowMapType:e.shadowMap.type,toneMapping:J,decodeVideoTexture:Ce&&o.map.isVideoTexture===!0&&Je.getTransfer(o.map.colorSpace)===Xe,decodeVideoTextureEmissive:ot&&o.emissiveMap.isVideoTexture===!0&&Je.getTransfer(o.emissiveMap.colorSpace)===Xe,premultipliedAlpha:o.premultipliedAlpha,doubleSided:o.side===wt,flipSided:o.side===St,useDepthPacking:o.depthPacking>=0,depthPacking:o.depthPacking||0,index0AttributeName:o.index0AttributeName,extensionClipCullDistance:Ue&&o.extensions.clipCullDistance===!0&&t.has("WEBGL_clip_cull_distance"),extensionMultiDraw:(Ue&&o.extensions.multiDraw===!0||_t)&&t.has("WEBGL_multi_draw"),rendererExtensionParallelShaderCompile:t.has("KHR_parallel_shader_compile"),customProgramCacheKey:o.customProgramCacheKey()};return Le.vertexUv1s=I.has(1),Le.vertexUv2s=I.has(2),Le.vertexUv3s=I.has(3),I.clear(),Le}function B(o){const p=[];if(o.shaderID?p.push(o.shaderID):(p.push(o.customVertexShaderID),p.push(o.customFragmentShaderID)),o.defines!==void 0)for(const q in o.defines)p.push(q),p.push(o.defines[q]);return o.isRawShaderMaterial===!1&&(f(p,o),l(p,o),p.push(e.outputColorSpace)),p.push(o.customProgramCacheKey),p.join()}function f(o,p){o.push(p.precision),o.push(p.outputColorSpace),o.push(p.envMapMode),o.push(p.envMapPMREM),o.push(p.envMapMaxLod),o.push(p.envMapSize),o.push(p.mapUv),o.push(p.alphaMapUv),o.push(p.lightMapUv),o.push(p.aoMapUv),o.push(p.bumpMapUv),o.push(p.normalMapUv),o.push(p.displacementMapUv),o.push(p.emissiveMapUv),o.push(p.metalnessMapUv),o.push(p.roughnessMapUv),o.push(p.anisotropyMapUv),o.push(p.clearcoatMapUv),o.push(p.clearcoatNormalMapUv),o.push(p.clearcoatRoughnessMapUv),o.push(p.diffuseRoughnessMapUv),o.push(p.iridescenceMapUv),o.push(p.iridescenceThicknessMapUv),o.push(p.sheenColorMapUv),o.push(p.sheenRoughnessMapUv),o.push(p.specularMapUv),o.push(p.specularColorMapUv),o.push(p.specularIntensityMapUv),o.push(p.transmissionMapUv),o.push(p.thicknessMapUv),o.push(p.combine),o.push(p.fogExp2),o.push(p.sizeAttenuation),o.push(p.morphTargetsCount),o.push(p.numSunLights),o.push(p.numDirLights),o.push(p.numPointLights),o.push(p.numSpotLights),o.push(p.numSpotLightMaps),o.push(p.numHemiLights),o.push(p.numRectAreaLights),o.push(p.numSunLightShadows),o.push(p.numDirLightShadows),o.push(p.numPointLightShadows),o.push(p.numSpotLightShadows),o.push(p.numSpotLightShadowsWithMaps),o.push(p.numLightProbes),o.push(p.numLightProbeGrids),o.push(p.shadowMapType),o.push(p.toneMapping),o.push(p.numClippingPlanes),o.push(p.numClipIntersection),o.push(p.depthPacking)}function l(o,p){u.disableAll(),p.instancing&&u.enable(0),p.instancingColor&&u.enable(1),p.instancingMorph&&u.enable(2),p.matcap&&u.enable(3),p.envMap&&u.enable(4),p.normalMapObjectSpace&&u.enable(5),p.normalMapTangentSpace&&u.enable(6),p.clearcoat&&u.enable(7),p.iridescence&&u.enable(8),p.alphaTest&&u.enable(9),p.vertexColors&&u.enable(10),p.vertexAlphas&&u.enable(11),p.vertexUv1s&&u.enable(12),p.vertexUv2s&&u.enable(13),p.vertexUv3s&&u.enable(14),p.vertexTangents&&u.enable(15),p.anisotropy&&u.enable(16),p.alphaHash&&u.enable(17),p.batching&&u.enable(18),p.dispersion&&u.enable(19),p.retroreflection&&u.enable(24),p.diffuseRoughness&&u.enable(25),p.batchingColor&&u.enable(20),p.gradientMap&&u.enable(21),p.packedNormalMap&&u.enable(22),p.vertexNormals&&u.enable(23),o.push(u.mask),u.disableAll(),p.fog&&u.enable(0),p.useFog&&u.enable(1),p.flatShading&&u.enable(2),p.logarithmicDepthBuffer&&u.enable(3),p.reversedDepthBuffer&&u.enable(4),p.skinning&&u.enable(5),p.morphTargets&&u.enable(6),p.morphNormals&&u.enable(7),p.morphColors&&u.enable(8),p.premultipliedAlpha&&u.enable(9),p.shadowMapEnabled&&u.enable(10),p.doubleSided&&u.enable(11),p.flipSided&&u.enable(12),p.useDepthPacking&&u.enable(13),p.dithering&&u.enable(14),p.transmission&&u.enable(15),p.sheen&&u.enable(16),p.opaque&&u.enable(17),p.pointsUvs&&u.enable(18),p.decodeVideoTexture&&u.enable(19),p.decodeVideoTextureEmissive&&u.enable(20),p.alphaToCoverage&&u.enable(21),p.numLightProbeGrids>0&&u.enable(22),p.hasPositionAttribute&&u.enable(23),o.push(u.mask)}function g(o){const p=v[o.type];let q;if(p){const D=Pt[p];q=Rr.clone(D.uniforms)}else q=o.uniforms;return q}function U(o,p){let q=X.get(p);return q!==void 0?++q.usedTimes:(q=new yf(e,p,o,c),C.push(q),X.set(p,q)),q}function S(o){if(--o.usedTimes===0){const p=C.indexOf(o);C[p]=C[C.length-1],C.pop(),X.delete(o.cacheKey),o.destroy()}}function R(o){m.remove(o)}function x(){m.dispose()}return{getParameters:L,getProgramCacheKey:B,getUniforms:g,acquireProgram:U,releaseProgram:S,releaseShaderCache:R,programs:C,dispose:x}}function Vf(){let e=new WeakMap;function n(u){return e.has(u)}function t(u){let m=e.get(u);return m===void 0&&(m={},e.set(u,m)),m}function i(u){e.delete(u)}function c(u,m,I){e.get(u)[m]=I}function s(){e=new WeakMap}return{has:n,get:t,remove:i,update:c,dispose:s}}function Wf(e,n){return e.groupOrder!==n.groupOrder?e.groupOrder-n.groupOrder:e.renderOrder!==n.renderOrder?e.renderOrder-n.renderOrder:e.material.id!==n.material.id?e.material.id-n.material.id:e.materialVariant!==n.materialVariant?e.materialVariant-n.materialVariant:e.z!==n.z?e.z-n.z:e.id-n.id}function io(e,n){return e.groupOrder!==n.groupOrder?e.groupOrder-n.groupOrder:e.renderOrder!==n.renderOrder?e.renderOrder-n.renderOrder:e.z!==n.z?n.z-e.z:e.id-n.id}function ro(){const e=[];let n=0;const t=[],i=[],c=[];function s(){n=0,t.length=0,i.length=0,c.length=0}function u(v){let M=0;return v.isInstancedMesh&&(M+=2),v.isSkinnedMesh&&(M+=1),M}function m(v,M,L,B,f,l){let g=e[n];return g===void 0?(g={id:v.id,object:v,geometry:M,material:L,materialVariant:u(v),groupOrder:B,renderOrder:v.renderOrder,z:f,group:l},e[n]=g):(g.id=v.id,g.object=v,g.geometry=M,g.material=L,g.materialVariant=u(v),g.groupOrder=B,g.renderOrder=v.renderOrder,g.z=f,g.group=l),n++,g}function I(v,M,L,B,f,l,g){g.reversedDepth===!0&&(f=-f);const U=m(v,M,L,B,f,l);L.transmission>0?i.push(U):L.transparent===!0?c.push(U):t.push(U)}function C(v,M,L,B,f,l){const g=m(v,M,L,B,f,l);L.transmission>0?i.unshift(g):L.transparent===!0?c.unshift(g):t.unshift(g)}function X(v,M){t.length>1&&t.sort(v||Wf),i.length>1&&i.sort(M||io),c.length>1&&c.sort(M||io)}function F(){for(let v=n,M=e.length;v<M;v++){const L=e[v];if(L.id===null)break;L.id=null,L.object=null,L.geometry=null,L.material=null,L.group=null}}return{opaque:t,transmissive:i,transparent:c,init:s,push:I,unshift:C,finish:F,sort:X}}function kf(){let e=new WeakMap;function n(i,c){const s=e.get(i);let u;return s===void 0?(u=new ro,e.set(i,[u])):c>=s.length?(u=new ro,s.push(u)):u=s[c],u}function t(){e=new WeakMap}return{get:n,dispose:t}}function zf(){const e={};return{get:function(n){if(e[n.id]!==void 0)return e[n.id];let t;switch(n.type){case"SunLight":case"DirectionalLight":t={direction:new We,color:new Ye};break;case"SpotLight":t={position:new We,direction:new We,color:new Ye,distance:0,coneCos:0,penumbraCos:0,decay:0};break;case"PointLight":t={position:new We,color:new Ye,distance:0,decay:0};break;case"HemisphereLight":t={direction:new We,skyColor:new Ye,groundColor:new Ye};break;case"RectAreaLight":t={color:new Ye,position:new We,halfWidth:new We,halfHeight:new We};break}return e[n.id]=t,t}}}function Xf(){const e={};return{get:function(n){if(e[n.id]!==void 0)return e[n.id];let t;switch(n.type){case"SunLight":case"DirectionalLight":t={shadowIntensity:1,shadowBias:0,shadowNormalBias:0,shadowRadius:1,shadowMapSize:new ht};break;case"SpotLight":t={shadowIntensity:1,shadowBias:0,shadowNormalBias:0,shadowRadius:1,shadowMapSize:new ht};break;case"PointLight":t={shadowIntensity:1,shadowBias:0,shadowNormalBias:0,shadowRadius:1,shadowMapSize:new ht,shadowCameraNear:1,shadowCameraFar:1e3};break}return e[n.id]=t,t}}}let Kf=0;function qf(e,n){return(n.castShadow?2:0)-(e.castShadow?2:0)+(n.map?1:0)-(e.map?1:0)}function Yf(e){const n=new zf,t=Xf(),i={version:0,hash:{sunLength:-1,directionalLength:-1,pointLength:-1,spotLength:-1,rectAreaLength:-1,hemiLength:-1,numSunShadows:-1,numDirectionalShadows:-1,numPointShadows:-1,numSpotShadows:-1,numSpotMaps:-1,numLightProbes:-1},ambient:[0,0,0],probe:[],sun:[],sunShadow:[],sunShadowMap:[],sunShadowMatrix:[],sunShadowCascade:[],directional:[],directionalShadow:[],directionalShadowMap:[],directionalShadowMatrix:[],spot:[],spotLightMap:[],spotShadow:[],spotShadowMap:[],spotLightMatrix:[],rectArea:[],rectAreaLTC1:null,rectAreaLTC2:null,point:[],pointShadow:[],pointShadowMap:[],pointShadowMatrix:[],hemi:[],numSpotLightShadowsWithMaps:0,numLightProbes:0};for(let C=0;C<9;C++)i.probe.push(new We);const c=new We,s=new Yt,u=new Yt;function m(C){let X=0,F=0,v=0;for(let z=0;z<9;z++)i.probe[z].set(0,0,0);let M=0,L=0,B=0,f=0,l=0,g=0,U=0,S=0,R=0,x=0,o=0,p=0,q=0,D=0;C.sort(qf);for(let z=0,Y=C.length;z<Y;z++){const T=C[z],Z=T.color,$=T.intensity,y=T.distance;let fe=null;if(T.shadow&&T.shadow.map&&(T.shadow.map.texture.format===Qt?fe=T.shadow.map.texture:fe=T.shadow.map.depthTexture||T.shadow.map.texture),T.isAmbientLight)X+=Z.r*$,F+=Z.g*$,v+=Z.b*$;else if(T.isLightProbe){for(let K=0;K<9;K++)i.probe[K].addScaledVector(T.sh.coefficients[K],$);D++}else if(T.isSunLight){const K=n.get(T);if(K.color.copy(T.color).multiplyScalar(T.intensity),T.castShadow){const j=T.shadow,ie=t.get(T);ie.shadowIntensity=j.intensity,ie.shadowBias=j.bias,ie.shadowNormalBias=j.normalBias,ie.shadowRadius=j.radius,ie.shadowMapSize.copy(j.mapSize).multiply(j.getFrameExtents()),i.sunShadow[L]=ie,i.sunShadowMap[L]=fe;const Ae=j.getViewportCount();for(let Ne=0;Ne<Ae;Ne++)i.sunShadowMatrix[B+Ne]=j.getMatrix(Ne),i.sunShadowCascade[B+Ne]=j._cascadeData[Ne];B+=Ae,L++}i.sun[M]=K,M++}else if(T.isDirectionalLight){const K=n.get(T);if(K.color.copy(T.color).multiplyScalar(T.intensity),T.castShadow){const j=T.shadow,ie=t.get(T);ie.shadowIntensity=j.intensity,ie.shadowBias=j.bias,ie.shadowNormalBias=j.normalBias,ie.shadowRadius=j.radius,ie.shadowMapSize=j.mapSize,i.directionalShadow[f]=ie,i.directionalShadowMap[f]=fe,i.directionalShadowMatrix[f]=T.shadow.matrix,R++}i.directional[f]=K,f++}else if(T.isSpotLight){const K=n.get(T);K.position.setFromMatrixPosition(T.matrixWorld),K.color.copy(Z).multiplyScalar($),K.distance=y,K.coneCos=Math.cos(T.angle),K.penumbraCos=Math.cos(T.angle*(1-T.penumbra)),K.decay=T.decay,i.spot[g]=K;const j=T.shadow;if(T.map&&(i.spotLightMap[p]=T.map,p++,j.updateMatrices(T),T.castShadow&&q++),i.spotLightMatrix[g]=j.matrix,T.castShadow){const ie=t.get(T);ie.shadowIntensity=j.intensity,ie.shadowBias=j.bias,ie.shadowNormalBias=j.normalBias,ie.shadowRadius=j.radius,ie.shadowMapSize=j.mapSize,i.spotShadow[g]=ie,i.spotShadowMap[g]=fe,o++}g++}else if(T.isRectAreaLight){const K=n.get(T);K.color.copy(Z).multiplyScalar($),K.halfWidth.set(T.width*.5,0,0),K.halfHeight.set(0,T.height*.5,0),i.rectArea[U]=K,U++}else if(T.isPointLight){const K=n.get(T);if(K.color.copy(T.color).multiplyScalar(T.intensity),K.distance=T.distance,K.decay=T.decay,T.castShadow){const j=T.shadow,ie=t.get(T);ie.shadowIntensity=j.intensity,ie.shadowBias=j.bias,ie.shadowNormalBias=j.normalBias,ie.shadowRadius=j.radius,ie.shadowMapSize=j.mapSize,ie.shadowCameraNear=j.camera.near,ie.shadowCameraFar=j.camera.far,i.pointShadow[l]=ie,i.pointShadowMap[l]=fe,i.pointShadowMatrix[l]=T.shadow.matrix,x++}i.point[l]=K,l++}else if(T.isHemisphereLight){const K=n.get(T);K.skyColor.copy(T.color).multiplyScalar($),K.groundColor.copy(T.groundColor).multiplyScalar($),i.hemi[S]=K,S++}}U>0&&(e.has("OES_texture_float_linear")===!0?(i.rectAreaLTC1=de.LTC_FLOAT_1,i.rectAreaLTC2=de.LTC_FLOAT_2):(i.rectAreaLTC1=de.LTC_HALF_1,i.rectAreaLTC2=de.LTC_HALF_2)),i.ambient[0]=X,i.ambient[1]=F,i.ambient[2]=v;const N=i.hash;(N.sunLength!==M||N.directionalLength!==f||N.pointLength!==l||N.spotLength!==g||N.rectAreaLength!==U||N.hemiLength!==S||N.numSunShadows!==L||N.numDirectionalShadows!==R||N.numPointShadows!==x||N.numSpotShadows!==o||N.numSpotMaps!==p||N.numLightProbes!==D)&&(i.sun.length=M,i.directional.length=f,i.spot.length=g,i.rectArea.length=U,i.point.length=l,i.hemi.length=S,i.sunShadow.length=L,i.sunShadowMap.length=L,i.sunShadowMatrix.length=B,i.sunShadowCascade.length=B,i.directionalShadow.length=R,i.directionalShadowMap.length=R,i.directionalShadowMatrix.length=R,i.pointShadow.length=x,i.pointShadowMap.length=x,i.pointShadowMatrix.length=x,i.spotShadow.length=o,i.spotShadowMap.length=o,i.spotLightMatrix.length=o+p-q,i.spotLightMap.length=p,i.numSpotLightShadowsWithMaps=q,i.numLightProbes=D,N.sunLength=M,N.directionalLength=f,N.pointLength=l,N.spotLength=g,N.rectAreaLength=U,N.hemiLength=S,N.numSunShadows=L,N.numDirectionalShadows=R,N.numPointShadows=x,N.numSpotShadows=o,N.numSpotMaps=p,N.numLightProbes=D,i.version=Kf++)}function I(C,X){let F=0,v=0,M=0,L=0,B=0,f=0;const l=X.matrixWorldInverse;for(let g=0,U=C.length;g<U;g++){const S=C[g];if(S.isSunLight){const R=i.sun[F];R.direction.setFromMatrixPosition(S.matrixWorld),R.direction.transformDirection(l),F++}else if(S.isDirectionalLight){const R=i.directional[v];R.direction.setFromMatrixPosition(S.matrixWorld),c.setFromMatrixPosition(S.target.matrixWorld),R.direction.sub(c),R.direction.transformDirection(l),v++}else if(S.isSpotLight){const R=i.spot[L];R.position.setFromMatrixPosition(S.matrixWorld),R.position.applyMatrix4(l),R.direction.setFromMatrixPosition(S.matrixWorld),c.setFromMatrixPosition(S.target.matrixWorld),R.direction.sub(c),R.direction.transformDirection(l),L++}else if(S.isRectAreaLight){const R=i.rectArea[B];R.position.setFromMatrixPosition(S.matrixWorld),R.position.applyMatrix4(l),u.identity(),s.copy(S.matrixWorld),s.premultiply(l),u.extractRotation(s),R.halfWidth.set(S.width*.5,0,0),R.halfHeight.set(0,S.height*.5,0),R.halfWidth.applyMatrix4(u),R.halfHeight.applyMatrix4(u),B++}else if(S.isPointLight){const R=i.point[M];R.position.setFromMatrixPosition(S.matrixWorld),R.position.applyMatrix4(l),M++}else if(S.isHemisphereLight){const R=i.hemi[f];R.direction.setFromMatrixPosition(S.matrixWorld),R.direction.transformDirection(l),f++}}}return{setup:m,setupView:I,state:i}}function ao(e){const n=new Yf(e),t=[],i=[],c=[];function s(F){X.camera=F,t.length=0,i.length=0,c.length=0}function u(F){F.isLightProbeGrid?c.push(F):t.push(F)}function m(F){i.push(F)}function I(){n.setup(t),c.sort((F,v)=>F.priority-v.priority||F.id-v.id)}function C(F){n.setupView(t,F)}const X={lightsArray:t,shadowsArray:i,lightProbeGridArray:c,camera:null,lights:n,transmissionRenderTarget:{},textureUnits:0};return{init:s,state:X,setupLights:I,setupLightsView:C,pushLight:u,pushShadow:m}}function Zf(e){let n=new WeakMap;function t(c,s=0){const u=n.get(c);let m;return u===void 0?(m=new ao(e),n.set(c,[m])):s>=u.length?(m=new ao(e),u.push(m)):m=u[s],m}function i(){n=new WeakMap}return{get:t,dispose:i}}const $f=`void main() {
	gl_Position = vec4( position, 1.0 );
}`,Qf=`uniform sampler2D shadow_pass;
uniform vec2 resolution;
uniform float radius;
void main() {
	const float samples = float( VSM_SAMPLES );
	float mean = 0.0;
	float squared_mean = 0.0;
	float uvStride = samples <= 1.0 ? 0.0 : 2.0 / ( samples - 1.0 );
	float uvStart = samples <= 1.0 ? 0.0 : - 1.0;
	for ( float i = 0.0; i < samples; i ++ ) {
		float uvOffset = uvStart + i * uvStride;
		#ifdef HORIZONTAL_PASS
			vec2 distribution = texture2D( shadow_pass, ( gl_FragCoord.xy + vec2( uvOffset, 0.0 ) * radius ) / resolution ).rg;
			mean += distribution.x;
			squared_mean += distribution.y * distribution.y + distribution.x * distribution.x;
		#else
			float depth = texture2D( shadow_pass, ( gl_FragCoord.xy + vec2( 0.0, uvOffset ) * radius ) / resolution ).r;
			mean += depth;
			squared_mean += depth * depth;
		#endif
	}
	mean = mean / samples;
	squared_mean = squared_mean / samples;
	float std_dev = sqrt( max( 0.0, squared_mean - mean * mean ) );
	gl_FragColor = vec4( mean, std_dev, 0.0, 1.0 );
}`,Jf=[new We(1,0,0),new We(-1,0,0),new We(0,1,0),new We(0,-1,0),new We(0,0,1),new We(0,0,-1)],jf=[new We(0,-1,0),new We(0,-1,0),new We(0,0,1),new We(0,0,-1),new We(0,-1,0),new We(0,-1,0)],oo=new Yt,mn=new We,tr=new We;function ed(e,n,t){let i=new ci;const c=new ht,s=new ht,u=new mt,m=new Pr,I=new Lr,C={},X=t.maxTextureSize,F={[jt]:St,[St]:jt,[wt]:wt},v=new qt({defines:{VSM_SAMPLES:8},uniforms:{shadow_pass:{value:null},resolution:{value:new ht},radius:{value:4}},vertexShader:$f,fragmentShader:Qf}),M=v.clone();M.defines.HORIZONTAL_PASS=1;const L=new qn;L.setAttribute("position",new Dr(new Float32Array([-1,-1,.5,3,-1,.5,-1,3,.5]),3));const B=new Gt(L,v),f=this;this.enabled=!1,this.autoUpdate=!0,this.needsUpdate=!1,this.type=cn;let l=this.type;this.render=function(x,o,p){if(f.enabled===!1||f.autoUpdate===!1&&f.needsUpdate===!1||x.length===0)return;this.type===Ur&&(He("WebGLShadowMap: PCFSoftShadowMap has been removed. Using PCFShadowMap instead."),this.type=cn);const q=e.getRenderTarget(),D=e.getActiveCubeFace(),N=e.getActiveMipmapLevel(),z=e.state;z.setBlending(Zt),z.buffers.depth.getReversed()===!0?z.buffers.color.setClear(0,0,0,0):z.buffers.color.setClear(1,1,1,1),z.buffers.depth.setTest(!0),z.setScissorTest(!1);const Y=l!==this.type;Y&&o.traverse(function(T){T.material&&(Array.isArray(T.material)?T.material.forEach(Z=>Z.needsUpdate=!0):T.material.needsUpdate=!0)});for(let T=0,Z=x.length;T<Z;T++){const $=x[T],y=$.shadow;if(y===void 0){He("WebGLShadowMap:",$,"has no shadow.");continue}if(y.autoUpdate===!1&&y.needsUpdate===!1)continue;c.copy(y.mapSize);const fe=y.getFrameExtents();c.multiply(fe),s.copy(y.mapSize),(c.x>X||c.y>X)&&(c.x>X&&(s.x=Math.floor(X/fe.x),c.x=s.x*fe.x,y.mapSize.x=s.x),c.y>X&&(s.y=Math.floor(X/fe.y),c.y=s.y*fe.y,y.mapSize.y=s.y));const K=e.state.buffers.depth.getReversed();if(y.camera._reversedDepth=K,y.map===null||Y===!0){if(y.map!==null&&(y.map.depthTexture!==null&&(y.map.depthTexture.dispose(),y.map.depthTexture=null),y.map.dispose()),this.type===nn){if($.isPointLight){He("WebGLShadowMap: VSM shadow maps are not supported for PointLights. Use PCF or BasicShadowMap instead.");continue}y.map=new bt(c.x,c.y,{format:Qt,type:Dt,minFilter:At,magFilter:At,generateMipmaps:!1}),y.map.texture.name=$.name+".shadowMap",y.map.depthTexture=new tn(c.x,c.y,yt),y.map.depthTexture.name=$.name+".shadowMapDepth",y.map.depthTexture.format=rn,y.map.depthTexture.compareFunction=null,y.map.depthTexture.minFilter=Wt,y.map.depthTexture.magFilter=Wt}else $.isPointLight?(y.map=new In(c.x),y.map.depthTexture=new ii(c.x,kt)):(y.map=new bt(c.x,c.y),y.map.depthTexture=new tn(c.x,c.y,kt)),y.map.depthTexture.name=$.name+".shadowMap",y.map.depthTexture.format=rn,this.type===cn?(y.map.depthTexture.compareFunction=K?si:li,y.map.depthTexture.minFilter=At,y.map.depthTexture.magFilter=At):(y.map.depthTexture.compareFunction=null,y.map.depthTexture.minFilter=Wt,y.map.depthTexture.magFilter=Wt);y.camera.updateProjectionMatrix()}y.map.isWebGLCubeRenderTarget!==!0&&(y.map.width!==c.x||y.map.height!==c.y)&&y.map.setSize(c.x,c.y);const j=y.map.isWebGLCubeRenderTarget?6:y.getViewportCount();$.isPointLight!==!0&&y.updateMatrices($,p);const ie=(y.camera.layers.mask&4294967294)!==0?y.camera.layers:p.layers;for(let Ae=0;Ae<j;Ae++){const Ne=y.getCamera(Ae);if($.isPointLight){const W=y.camera,he=y.matrix,Se=$.distance||W.far;Se!==W.far&&(W.far=Se,W.updateProjectionMatrix()),mn.setFromMatrixPosition($.matrixWorld),W.position.copy(mn),tr.copy(W.position),tr.add(Jf[Ae]),W.up.copy(jf[Ae]),W.lookAt(tr),W.updateMatrixWorld(),he.makeTranslation(-mn.x,-mn.y,-mn.z),oo.multiplyMatrices(W.projectionMatrix,W.matrixWorldInverse),y._frustum.setFromProjectionMatrix(oo,W.coordinateSystem,W.reversedDepth)}if(y.map.isWebGLCubeRenderTarget)e.setRenderTarget(y.map,Ae),e.clear();else{Ae===0&&(e.setRenderTarget(y.map),e.clear());const W=y.getViewport(Ae);u.set(s.x*W.x,s.y*W.y,s.x*W.z,s.y*W.w),z.viewport(u)}i=y.getFrustum(Ae),S(o,p,Ne,$,ie,this.type)}y.isPointLightShadow!==!0&&this.type===nn&&g(y,p),y.needsUpdate=!1}l=this.type,f.needsUpdate=!1,e.setRenderTarget(q,D,N)};function g(x,o){const p=n.update(B);v.defines.VSM_SAMPLES!==x.blurSamples&&(v.defines.VSM_SAMPLES=x.blurSamples,M.defines.VSM_SAMPLES=x.blurSamples,v.needsUpdate=!0,M.needsUpdate=!0),x.mapPass===null?x.mapPass=new bt(c.x,c.y,{format:Qt,type:Dt}):(x.mapPass.width!==x.map.width||x.mapPass.height!==x.map.height)&&x.mapPass.setSize(x.map.width,x.map.height),v.uniforms.shadow_pass.value=x.map.depthTexture,v.uniforms.resolution.value.set(x.map.width,x.map.height),v.uniforms.radius.value=x.radius,e.setRenderTarget(x.mapPass),e.clear(),e.renderBufferDirect(o,null,p,v,B,null),M.uniforms.shadow_pass.value=x.mapPass.texture,M.uniforms.resolution.value.set(x.map.width,x.map.height),M.uniforms.radius.value=x.radius,e.setRenderTarget(x.map),e.clear(),e.renderBufferDirect(o,null,p,M,B,null)}function U(x,o,p,q){let D=null;const N=p.isPointLight===!0?x.customDistanceMaterial:x.customDepthMaterial;if(N!==void 0)D=N;else if(D=p.isPointLight===!0?I:m,e.localClippingEnabled&&o.clipShadows===!0&&Array.isArray(o.clippingPlanes)&&o.clippingPlanes.length!==0||o.displacementMap&&o.displacementScale!==0||o.alphaMap&&o.alphaTest>0||o.map&&o.alphaTest>0||o.alphaToCoverage===!0){const z=D.uuid,Y=o.uuid;let T=C[z];T===void 0&&(T={},C[z]=T);let Z=T[Y];Z===void 0&&(Z=D.clone(),T[Y]=Z,o.addEventListener("dispose",R)),D=Z}if(D.visible=o.visible,D.wireframe=o.wireframe,q===nn?D.side=o.shadowSide!==null?o.shadowSide:o.side:D.side=o.shadowSide!==null?o.shadowSide:F[o.side],D.alphaMap=o.alphaMap,D.alphaTest=o.alphaToCoverage===!0?.5:o.alphaTest,D.map=o.map,D.clipShadows=o.clipShadows,D.clippingPlanes=o.clippingPlanes,D.clipIntersection=o.clipIntersection,D.displacementMap=o.displacementMap,D.displacementScale=o.displacementScale,D.displacementBias=o.displacementBias,D.wireframeLinewidth=o.wireframeLinewidth,D.linewidth=o.linewidth,p.isPointLight===!0&&D.isMeshDistanceMaterial===!0){const z=e.properties.get(D);z.light=p}return D}function S(x,o,p,q,D,N){if(x.visible===!1)return;if(x.layers.test(D)&&(x.isMesh||x.isLine||x.isPoints)&&(x.castShadow||x.receiveShadow&&N===nn)&&(!x.frustumCulled||x.intersectsFrustum(i))){x.modelViewMatrix.multiplyMatrices(p.matrixWorldInverse,x.matrixWorld);const T=n.update(x),Z=x.material;if(Array.isArray(Z)){const $=T.groups;for(let y=0,fe=$.length;y<fe;y++){const K=$[y],j=Z[K.materialIndex];if(j&&j.visible){const ie=U(x,j,q,N);x.onBeforeShadow(e,x,o,p,T,ie,K),e.renderBufferDirect(p,null,T,ie,x,K),x.onAfterShadow(e,x,o,p,T,ie,K)}}}else if(Z.visible){const $=U(x,Z,q,N);x.onBeforeShadow(e,x,o,p,T,$,null),e.renderBufferDirect(p,null,T,$,x,null),x.onAfterShadow(e,x,o,p,T,$,null)}}const Y=x.children;for(let T=0,Z=Y.length;T<Z;T++)S(Y[T],o,p,q,D,N)}function R(x){x.target.removeEventListener("dispose",R);for(const p in C){const q=C[p],D=x.target.uuid;D in q&&(q[D].dispose(),delete q[D])}}}function td(e,n){function t(){let h=!1;const V=new mt;let O=null;const ae=new mt(0,0,0,0);return{setMask:function(ee){O!==ee&&!h&&(e.colorMask(ee,ee,ee,ee),O=ee)},setLocked:function(ee){h=ee},setClear:function(ee,ne,Me,Ue,J){J===!0&&(ee*=Ue,ne*=Ue,Me*=Ue),V.set(ee,ne,Me,Ue),ae.equals(V)===!1&&(e.clearColor(ee,ne,Me,Ue),ae.copy(V))},reset:function(){h=!1,O=null,ae.set(-1,0,0,0)}}}function i(){let h=!1,V=!1,O=null,ae=null,ee=null;return{setReversed:function(ne){if(V!==ne){const Me=n.get("EXT_clip_control");ne?Me.clipControlEXT(Me.LOWER_LEFT_EXT,Me.ZERO_TO_ONE_EXT):Me.clipControlEXT(Me.LOWER_LEFT_EXT,Me.NEGATIVE_ONE_TO_ONE_EXT),V=ne;const Ue=ee;ee=null,this.setClear(Ue)}},getReversed:function(){return V},setTest:function(ne){ne?me(e.DEPTH_TEST):we(e.DEPTH_TEST)},setMask:function(ne){O!==ne&&!h&&(e.depthMask(ne),O=ne)},setFunc:function(ne){if(V&&(ne=_o[ne]),ae!==ne){switch(ne){case ta:e.depthFunc(e.NEVER);break;case ea:e.depthFunc(e.ALWAYS);break;case jr:e.depthFunc(e.LESS);break;case fi:e.depthFunc(e.LEQUAL);break;case Jr:e.depthFunc(e.EQUAL);break;case Qr:e.depthFunc(e.GEQUAL);break;case $r:e.depthFunc(e.GREATER);break;case Zr:e.depthFunc(e.NOTEQUAL);break;default:e.depthFunc(e.LEQUAL)}ae=ne}},setLocked:function(ne){h=ne},setClear:function(ne){ee!==ne&&(ee=ne,V&&(ne=1-ne),e.clearDepth(ne))},reset:function(){h=!1,O=null,ae=null,ee=null,V=!1}}}function c(){let h=!1,V=null,O=null,ae=null,ee=null,ne=null,Me=null,Ue=null,J=null;return{setTest:function(xe){h||(xe?me(e.STENCIL_TEST):we(e.STENCIL_TEST))},setMask:function(xe){V!==xe&&!h&&(e.stencilMask(xe),V=xe)},setFunc:function(xe,Le,ue){(O!==xe||ae!==Le||ee!==ue)&&(e.stencilFunc(xe,Le,ue),O=xe,ae=Le,ee=ue)},setOp:function(xe,Le,ue){(ne!==xe||Me!==Le||Ue!==ue)&&(e.stencilOp(xe,Le,ue),ne=xe,Me=Le,Ue=ue)},setLocked:function(xe){h=xe},setClear:function(xe){J!==xe&&(e.clearStencil(xe),J=xe)},reset:function(){h=!1,V=null,O=null,ae=null,ee=null,ne=null,Me=null,Ue=null,J=null}}}const s=new t,u=new i,m=new c;let I={};const C=new WeakMap;let X={},F={},v={},M=new WeakMap,L=[],B=null,f=!1,l=null,g=null,U=null,S=null,R=null,x=null,o=null,p=new Ye(0,0,0),q=0,D=!1,N=null,z=null,Y=null,T=null,Z=null;const $=e.getParameter(e.MAX_COMBINED_TEXTURE_IMAGE_UNITS);let y=!1,fe=0;const K=e.getParameter(e.VERSION);K.indexOf("WebGL")!==-1?(fe=parseFloat(/^WebGL (\d)/.exec(K)[1]),y=fe>=1):K.indexOf("OpenGL ES")!==-1&&(fe=parseFloat(/^OpenGL ES (\d)/.exec(K)[1]),y=fe>=2);let j=null,ie={};const Ae=e.getParameter(e.SCISSOR_BOX),Ne=e.getParameter(e.VIEWPORT),W=new mt().fromArray(Ae),he=new mt().fromArray(Ne);function Se(h,V,O,ae){const ee=new Uint8Array(4),ne=e.createTexture();e.bindTexture(h,ne),e.texParameteri(h,e.TEXTURE_MIN_FILTER,e.NEAREST),e.texParameteri(h,e.TEXTURE_MAG_FILTER,e.NEAREST);for(let Me=0;Me<O;Me++)h===e.TEXTURE_3D||h===e.TEXTURE_2D_ARRAY?e.texImage3D(V,0,e.RGBA,1,1,ae,0,e.RGBA,e.UNSIGNED_BYTE,ee):e.texImage2D(V+Me,0,e.RGBA,1,1,0,e.RGBA,e.UNSIGNED_BYTE,ee);return ne}const Re={};Re[e.TEXTURE_2D]=Se(e.TEXTURE_2D,e.TEXTURE_2D,1),Re[e.TEXTURE_CUBE_MAP]=Se(e.TEXTURE_CUBE_MAP,e.TEXTURE_CUBE_MAP_POSITIVE_X,6),Re[e.TEXTURE_2D_ARRAY]=Se(e.TEXTURE_2D_ARRAY,e.TEXTURE_2D_ARRAY,1,1),Re[e.TEXTURE_3D]=Se(e.TEXTURE_3D,e.TEXTURE_3D,1,1),s.setClear(0,0,0,1),u.setClear(1),m.setClear(0),me(e.DEPTH_TEST),u.setFunc(fi),gt(!1),je(di),me(e.CULL_FACE),ct(Zt);function me(h){X[h]!==!0&&(e.enable(h),X[h]=!0)}function we(h){X[h]!==!1&&(e.disable(h),X[h]=!1)}function _t(h,V){return v[h]!==V?(e.bindFramebuffer(h,V),v[h]=V,h===e.DRAW_FRAMEBUFFER&&(v[e.FRAMEBUFFER]=V),h===e.FRAMEBUFFER&&(v[e.DRAW_FRAMEBUFFER]=V),!0):!1}function Ce(h,V){let O=L,ae=!1;if(h){O=M.get(V),O===void 0&&(O=[],M.set(V,O));const ee=h.textures;if(O.length!==ee.length||O[0]!==e.COLOR_ATTACHMENT0){for(let ne=0,Me=ee.length;ne<Me;ne++)O[ne]=e.COLOR_ATTACHMENT0+ne;O.length=ee.length,ae=!0}}else O[0]!==e.BACK&&(O[0]=e.BACK,ae=!0);ae&&e.drawBuffers(O)}function qe(h){return B!==h?(e.useProgram(h),B=h,!0):!1}const ke={[an]:e.FUNC_ADD,[Nr]:e.FUNC_SUBTRACT,[wr]:e.FUNC_REVERSE_SUBTRACT};ke[aa]=e.MIN,ke[oa]=e.MAX;const Ie={[Yr]:e.ZERO,[qr]:e.ONE,[Kr]:e.SRC_COLOR,[Xr]:e.SRC_ALPHA,[zr]:e.SRC_ALPHA_SATURATE,[kr]:e.DST_COLOR,[Wr]:e.DST_ALPHA,[Vr]:e.ONE_MINUS_SRC_COLOR,[Hr]:e.ONE_MINUS_SRC_ALPHA,[Gr]:e.ONE_MINUS_DST_COLOR,[Br]:e.ONE_MINUS_DST_ALPHA,[Or]:e.CONSTANT_COLOR,[Fr]:e.ONE_MINUS_CONSTANT_COLOR,[yr]:e.CONSTANT_ALPHA,[Ir]:e.ONE_MINUS_CONSTANT_ALPHA};function ct(h,V,O,ae,ee,ne,Me,Ue,J,xe){if(h===Zt){f===!0&&(we(e.BLEND),f=!1);return}if(f===!1&&(me(e.BLEND),f=!0),h!==ra){if(h!==l||xe!==D){if((g!==an||R!==an)&&(e.blendEquation(e.FUNC_ADD),g=an,R=an),xe)switch(h){case fn:e.blendFuncSeparate(e.ONE,e.ONE_MINUS_SRC_ALPHA,e.ONE,e.ONE_MINUS_SRC_ALPHA);break;case hi:e.blendFunc(e.ONE,e.ONE);break;case pi:e.blendFuncSeparate(e.ZERO,e.ONE_MINUS_SRC_COLOR,e.ZERO,e.ONE);break;case ui:e.blendFuncSeparate(e.DST_COLOR,e.ONE_MINUS_SRC_ALPHA,e.ZERO,e.ONE);break;default:Ze("WebGLState: Invalid blending: ",h);break}else switch(h){case fn:e.blendFuncSeparate(e.SRC_ALPHA,e.ONE_MINUS_SRC_ALPHA,e.ONE,e.ONE_MINUS_SRC_ALPHA);break;case hi:e.blendFuncSeparate(e.SRC_ALPHA,e.ONE,e.ONE,e.ONE);break;case pi:Ze("WebGLState: SubtractiveBlending requires material.premultipliedAlpha = true");break;case ui:Ze("WebGLState: MultiplyBlending requires material.premultipliedAlpha = true");break;default:Ze("WebGLState: Invalid blending: ",h);break}U=null,S=null,x=null,o=null,p.set(0,0,0),q=0,l=h,D=xe}return}ee=ee||V,ne=ne||O,Me=Me||ae,(V!==g||ee!==R)&&(e.blendEquationSeparate(ke[V],ke[ee]),g=V,R=ee),(O!==U||ae!==S||ne!==x||Me!==o)&&(e.blendFuncSeparate(Ie[O],Ie[ae],Ie[ne],Ie[Me]),U=O,S=ae,x=ne,o=Me),(Ue.equals(p)===!1||J!==q)&&(e.blendColor(Ue.r,Ue.g,Ue.b,J),p.copy(Ue),q=J),l=h,D=!1}function nt(h,V){h.side===wt?we(e.CULL_FACE):me(e.CULL_FACE);let O=h.side===St;V&&(O=!O),gt(O),h.blending===fn&&h.transparent===!1?ct(Zt):ct(h.blending,h.blendEquation,h.blendSrc,h.blendDst,h.blendEquationAlpha,h.blendSrcAlpha,h.blendDstAlpha,h.blendColor,h.blendAlpha,h.premultipliedAlpha),u.setFunc(h.depthFunc),u.setTest(h.depthTest),u.setMask(h.depthWrite),s.setMask(h.colorWrite);const ae=h.stencilWrite;m.setTest(ae),ae&&(m.setMask(h.stencilWriteMask),m.setFunc(h.stencilFunc,h.stencilRef,h.stencilFuncMask),m.setOp(h.stencilFail,h.stencilZFail,h.stencilZPass)),ot(h.polygonOffset,h.polygonOffsetFactor,h.polygonOffsetUnits),h.alphaToCoverage===!0?me(e.SAMPLE_ALPHA_TO_COVERAGE):we(e.SAMPLE_ALPHA_TO_COVERAGE)}function gt(h){N!==h&&(h?e.frontFace(e.CW):e.frontFace(e.CCW),N=h)}function je(h){h!==na?(me(e.CULL_FACE),h!==z&&(h===di?e.cullFace(e.BACK):h===ia?e.cullFace(e.FRONT):e.cullFace(e.FRONT_AND_BACK))):we(e.CULL_FACE),z=h}function Lt(h){h!==Y&&(y&&e.lineWidth(h),Y=h)}function ot(h,V,O){h?(me(e.POLYGON_OFFSET_FILL),(T!==V||Z!==O)&&(T=V,Z=O,u.getReversed()&&(V=-V,O=-O),e.polygonOffset(V,O))):we(e.POLYGON_OFFSET_FILL)}function Et(h){h?me(e.SCISSOR_TEST):we(e.SCISSOR_TEST)}function Mt(h){h===void 0&&(h=e.TEXTURE0+$-1),j!==h&&(e.activeTexture(h),j=h)}function xt(h,V,O){O===void 0&&(j===null?O=e.TEXTURE0+$-1:O=j);let ae=ie[O];ae===void 0&&(ae={type:void 0,texture:void 0},ie[O]=ae),(ae.type!==h||ae.texture!==V)&&(j!==O&&(e.activeTexture(O),j=O),e.bindTexture(h,V||Re[h]),ae.type=h,ae.texture=V)}function E(){const h=ie[j];h!==void 0&&h.type!==void 0&&(e.bindTexture(h.type,null),h.type=void 0,h.texture=void 0)}function Tt(){try{e.compressedTexImage2D(...arguments)}catch(h){Ze("WebGLState:",h)}}function $e(){try{e.compressedTexImage3D(...arguments)}catch(h){Ze("WebGLState:",h)}}function Qe(){try{e.texSubImage2D(...arguments)}catch(h){Ze("WebGLState:",h)}}function ce(){try{e.texSubImage3D(...arguments)}catch(h){Ze("WebGLState:",h)}}function it(){try{e.compressedTexSubImage2D(...arguments)}catch(h){Ze("WebGLState:",h)}}function Te(){try{e.compressedTexSubImage3D(...arguments)}catch(h){Ze("WebGLState:",h)}}function be(){try{e.texStorage2D(...arguments)}catch(h){Ze("WebGLState:",h)}}function d(){try{e.texStorage3D(...arguments)}catch(h){Ze("WebGLState:",h)}}function r(){try{e.texImage2D(...arguments)}catch(h){Ze("WebGLState:",h)}}function b(){try{e.texImage3D(...arguments)}catch(h){Ze("WebGLState:",h)}}function Q(h){return F[h]!==void 0?F[h]:e.getParameter(h)}function k(h,V){F[h]!==V&&(e.pixelStorei(h,V),F[h]=V)}function te(h){W.equals(h)===!1&&(e.scissor(h.x,h.y,h.z,h.w),W.copy(h))}function se(h){he.equals(h)===!1&&(e.viewport(h.x,h.y,h.z,h.w),he.copy(h))}function G(h,V){let O=C.get(V);O===void 0&&(O=new WeakMap,C.set(V,O));let ae=O.get(h);ae===void 0&&(ae=e.getUniformBlockIndex(V,h.name),e.uniformBlockBinding(V,ae,ae),O.set(h,ae))}function H(h,V,O){const ee=C.get(V).get(h);I[ee]!==O&&(e.bindBufferBase(e.UNIFORM_BUFFER,ee,O),I[ee]=O)}function re(){e.disable(e.BLEND),e.disable(e.CULL_FACE),e.disable(e.DEPTH_TEST),e.disable(e.POLYGON_OFFSET_FILL),e.disable(e.SCISSOR_TEST),e.disable(e.STENCIL_TEST),e.disable(e.SAMPLE_ALPHA_TO_COVERAGE),e.blendEquation(e.FUNC_ADD),e.blendFunc(e.ONE,e.ZERO),e.blendFuncSeparate(e.ONE,e.ZERO,e.ONE,e.ZERO),e.blendColor(0,0,0,0),e.colorMask(!0,!0,!0,!0),e.clearColor(0,0,0,0),e.depthMask(!0),e.depthFunc(e.LESS),u.setReversed(!1),e.clearDepth(1),e.stencilMask(4294967295),e.stencilFunc(e.ALWAYS,0,4294967295),e.stencilOp(e.KEEP,e.KEEP,e.KEEP),e.clearStencil(0),e.cullFace(e.BACK),e.frontFace(e.CCW),e.polygonOffset(0,0),e.activeTexture(e.TEXTURE0),e.bindFramebuffer(e.FRAMEBUFFER,null),e.bindFramebuffer(e.DRAW_FRAMEBUFFER,null),e.bindFramebuffer(e.READ_FRAMEBUFFER,null),e.useProgram(null),e.lineWidth(1),e.scissor(0,0,e.canvas.width,e.canvas.height),e.viewport(0,0,e.canvas.width,e.canvas.height),e.pixelStorei(e.PACK_ALIGNMENT,4),e.pixelStorei(e.UNPACK_ALIGNMENT,4),e.pixelStorei(e.UNPACK_FLIP_Y_WEBGL,!1),e.pixelStorei(e.UNPACK_PREMULTIPLY_ALPHA_WEBGL,!1),e.pixelStorei(e.UNPACK_COLORSPACE_CONVERSION_WEBGL,e.BROWSER_DEFAULT_WEBGL),e.pixelStorei(e.PACK_ROW_LENGTH,0),e.pixelStorei(e.PACK_SKIP_PIXELS,0),e.pixelStorei(e.PACK_SKIP_ROWS,0),e.pixelStorei(e.UNPACK_ROW_LENGTH,0),e.pixelStorei(e.UNPACK_IMAGE_HEIGHT,0),e.pixelStorei(e.UNPACK_SKIP_PIXELS,0),e.pixelStorei(e.UNPACK_SKIP_ROWS,0),e.pixelStorei(e.UNPACK_SKIP_IMAGES,0),X={},F={},j=null,ie={},v={},I={},M=new WeakMap,L=[],B=null,f=!1,l=null,g=null,U=null,S=null,R=null,x=null,o=null,p=new Ye(0,0,0),q=0,D=!1,N=null,z=null,Y=null,T=null,Z=null,W.set(0,0,e.canvas.width,e.canvas.height),he.set(0,0,e.canvas.width,e.canvas.height),s.reset(),u.reset(),m.reset()}return{buffers:{color:s,depth:u,stencil:m},enable:me,disable:we,bindFramebuffer:_t,drawBuffers:Ce,useProgram:qe,setBlending:ct,setMaterial:nt,setFlipSided:gt,setCullFace:je,setLineWidth:Lt,setPolygonOffset:ot,setScissorTest:Et,activeTexture:Mt,bindTexture:xt,unbindTexture:E,compressedTexImage2D:Tt,compressedTexImage3D:$e,texImage2D:r,texImage3D:b,pixelStorei:k,getParameter:Q,updateUBOMapping:G,uniformBlockBinding:H,texStorage2D:be,texStorage3D:d,texSubImage2D:Qe,texSubImage3D:ce,compressedTexSubImage2D:it,compressedTexSubImage3D:Te,scissor:te,viewport:se,reset:re}}function nd(e,n,t,i,c,s,u){const m=n.has("WEBGL_multisampled_render_to_texture")?n.get("WEBGL_multisampled_render_to_texture"):null,I=typeof navigator>"u"?!1:/OculusBrowser/g.test(navigator.userAgent),C=new ht,X=new WeakMap,F=new Set;let v;const M=new WeakMap,L=new Set,B=new FinalizationRegistry(d=>L.delete(d));let f=!1;try{f=typeof OffscreenCanvas<"u"&&new OffscreenCanvas(1,1).getContext("2d")!==null}catch{}function l(d,r){return f?new OffscreenCanvas(d,r):go("canvas")}function g(d,r,b){let Q=1;const k=be(d);if((k.width>b||k.height>b)&&(Q=b/Math.max(k.width,k.height)),Q<1)if(typeof HTMLImageElement<"u"&&d instanceof HTMLImageElement||typeof HTMLCanvasElement<"u"&&d instanceof HTMLCanvasElement||typeof ImageBitmap<"u"&&d instanceof ImageBitmap||typeof VideoFrame<"u"&&d instanceof VideoFrame){const te=Math.floor(Q*k.width),se=Math.floor(Q*k.height);v===void 0&&(v=l(te,se));const G=r?l(te,se):v;return G.width=te,G.height=se,G.getContext("2d").drawImage(d,0,0,te,se),He("WebGLRenderer: Texture has been resized from ("+k.width+"x"+k.height+") to ("+te+"x"+se+")."),G}else return"data"in d&&He("WebGLRenderer: Image in DataTexture is too big ("+k.width+"x"+k.height+")."),d;return d}function U(d){return d.generateMipmaps}function S(d){e.generateMipmap(d)}function R(d){return d.isWebGLCubeRenderTarget?e.TEXTURE_CUBE_MAP:d.isWebGL3DRenderTarget?e.TEXTURE_3D:d.isWebGLArrayRenderTarget||d.isCompressedArrayTexture?e.TEXTURE_2D_ARRAY:e.TEXTURE_2D}function x(d,r,b,Q,k,te=!1){if(d!==null){if(e[d]!==void 0)return e[d];He("WebGLRenderer: Attempt to use non-existing WebGL internal format '"+d+"'")}let se;Q&&(se=n.get("EXT_texture_norm16"),se||He("WebGLRenderer: Unable to use normalized textures without EXT_texture_norm16 extension"));let G=r;if(r===e.RED&&(b===e.FLOAT&&(G=e.R32F),b===e.HALF_FLOAT&&(G=e.R16F),b===e.UNSIGNED_BYTE&&(G=e.R8),b===e.UNSIGNED_SHORT&&se&&(G=se.R16_EXT),b===e.SHORT&&se&&(G=se.R16_SNORM_EXT)),r===e.RED_INTEGER&&(b===e.UNSIGNED_BYTE&&(G=e.R8UI),b===e.UNSIGNED_SHORT&&(G=e.R16UI),b===e.UNSIGNED_INT&&(G=e.R32UI),b===e.BYTE&&(G=e.R8I),b===e.SHORT&&(G=e.R16I),b===e.INT&&(G=e.R32I)),r===e.RG&&(b===e.FLOAT&&(G=e.RG32F),b===e.HALF_FLOAT&&(G=e.RG16F),b===e.UNSIGNED_BYTE&&(G=e.RG8),b===e.UNSIGNED_SHORT&&se&&(G=se.RG16_EXT),b===e.SHORT&&se&&(G=se.RG16_SNORM_EXT)),r===e.RG_INTEGER&&(b===e.UNSIGNED_BYTE&&(G=e.RG8UI),b===e.UNSIGNED_SHORT&&(G=e.RG16UI),b===e.UNSIGNED_INT&&(G=e.RG32UI),b===e.BYTE&&(G=e.RG8I),b===e.SHORT&&(G=e.RG16I),b===e.INT&&(G=e.RG32I)),r===e.RGB_INTEGER&&(b===e.UNSIGNED_BYTE&&(G=e.RGB8UI),b===e.UNSIGNED_SHORT&&(G=e.RGB16UI),b===e.UNSIGNED_INT&&(G=e.RGB32UI),b===e.BYTE&&(G=e.RGB8I),b===e.SHORT&&(G=e.RGB16I),b===e.INT&&(G=e.RGB32I)),r===e.RGBA_INTEGER&&(b===e.UNSIGNED_BYTE&&(G=e.RGBA8UI),b===e.UNSIGNED_SHORT&&(G=e.RGBA16UI),b===e.UNSIGNED_INT&&(G=e.RGBA32UI),b===e.BYTE&&(G=e.RGBA8I),b===e.SHORT&&(G=e.RGBA16I),b===e.INT&&(G=e.RGBA32I)),r===e.RGB&&(b===e.UNSIGNED_SHORT&&se&&(G=se.RGB16_EXT),b===e.SHORT&&se&&(G=se.RGB16_SNORM_EXT),b===e.UNSIGNED_INT_5_9_9_9_REV&&(G=e.RGB9_E5),b===e.UNSIGNED_INT_10F_11F_11F_REV&&(G=e.R11F_G11F_B10F)),r===e.RGBA){const H=te?ai:Je.getTransfer(k);b===e.FLOAT&&(G=e.RGBA32F),b===e.HALF_FLOAT&&(G=e.RGBA16F),b===e.UNSIGNED_BYTE&&(G=H===Xe?e.SRGB8_ALPHA8:e.RGBA8),b===e.UNSIGNED_SHORT&&se&&(G=se.RGBA16_EXT),b===e.SHORT&&se&&(G=se.RGBA16_SNORM_EXT),b===e.UNSIGNED_SHORT_4_4_4_4&&(G=e.RGBA4),b===e.UNSIGNED_SHORT_5_5_5_1&&(G=e.RGB5_A1)}return(G===e.R16F||G===e.R32F||G===e.RG16F||G===e.RG32F||G===e.RGBA16F||G===e.RGBA32F)&&n.get("EXT_color_buffer_float"),G}function o(d,r){let b;return d?r===null||r===kt||r===$t?b=e.DEPTH24_STENCIL8:r===yt?b=e.DEPTH32F_STENCIL8:r===un&&(b=e.DEPTH24_STENCIL8,He("DepthTexture: 16 bit depth attachment is not supported with stencil. Using 24-bit attachment.")):r===null||r===kt||r===$t?b=e.DEPTH_COMPONENT24:r===yt?b=e.DEPTH_COMPONENT32F:r===un&&(b=e.DEPTH_COMPONENT16),b}function p(d,r){return U(d)===!0||d.isFramebufferTexture&&d.minFilter!==Wt&&d.minFilter!==At?Math.log2(Math.max(r.width,r.height))+1:d.mipmaps.length>0?d.mipmaps.length:d.isCompressedTexture&&Array.isArray(d.image)?r.mipmaps.length:1}function q(d){const r=new WeakRef(d);i.get(d).__ref=r,L.add(r),B.register(d,r,r)}function D(d){const r=i.get(d).__ref;r!==void 0&&(L.delete(r),B.unregister(r))}function N(d){Y(d.target)}function z(d){T(d.target)}function Y(d){d.removeEventListener("dispose",N),D(d),$(d),d.isVideoTexture&&X.delete(d),d.isHTMLTexture&&F.delete(d)}function T(d){d.removeEventListener("dispose",z),D(d),fe(d)}function Z(){for(const d of L){const r=d.deref();r!==void 0&&(r.isRenderTarget===!0?T(r):Y(r))}L.clear(),F.clear()}function $(d){const r=i.get(d);if(r.__webglInit===void 0)return;const b=d.source,Q=M.get(b);if(Q){const k=Q[r.__cacheKey];k.usedTimes--,k.usedTimes===0&&y(d),Object.keys(Q).length===0&&M.delete(b)}i.remove(d)}function y(d){const r=i.get(d);e.deleteTexture(r.__webglTexture);const b=d.source,Q=M.get(b);delete Q[r.__cacheKey],u.memory.textures--}function fe(d){const r=i.get(d);r.__depthDisposeCallback&&r.__depthDisposeCallback();const b=d.depthTexture;if(b&&b.renderTarget===d&&Y(b),d.isWebGLCubeRenderTarget)for(let k=0;k<6;k++){if(Array.isArray(r.__webglFramebuffer[k]))for(let te=0;te<r.__webglFramebuffer[k].length;te++)e.deleteFramebuffer(r.__webglFramebuffer[k][te]);else e.deleteFramebuffer(r.__webglFramebuffer[k]);r.__webglDepthbuffer&&e.deleteRenderbuffer(r.__webglDepthbuffer[k])}else{if(Array.isArray(r.__webglFramebuffer))for(let k=0;k<r.__webglFramebuffer.length;k++)e.deleteFramebuffer(r.__webglFramebuffer[k]);else e.deleteFramebuffer(r.__webglFramebuffer);if(r.__webglDepthbuffer&&e.deleteRenderbuffer(r.__webglDepthbuffer),r.__webglMultisampledFramebuffer&&e.deleteFramebuffer(r.__webglMultisampledFramebuffer),r.__webglColorRenderbuffer)for(let k=0;k<r.__webglColorRenderbuffer.length;k++)r.__webglColorRenderbuffer[k]&&e.deleteRenderbuffer(r.__webglColorRenderbuffer[k]);r.__webglDepthRenderbuffer&&e.deleteRenderbuffer(r.__webglDepthRenderbuffer)}const Q=d.textures;for(let k=0,te=Q.length;k<te;k++){const se=i.get(Q[k]);se.__webglTexture&&(e.deleteTexture(se.__webglTexture),u.memory.textures--),i.remove(Q[k])}i.remove(d)}let K=0;function j(){K=0}function ie(){return K}function Ae(d){K=d}function Ne(){const d=K;return d>=c.maxTextures&&He("WebGLTextures: Trying to use "+(d+1)+" texture units while this GPU supports only "+c.maxTextures),K+=1,d}function W(d){const r=[];return r.push(d.wrapS),r.push(d.wrapT),r.push(d.wrapR||0),r.push(d.magFilter),r.push(d.minFilter),r.push(d.anisotropy),r.push(d.internalFormat),r.push(d.format),r.push(d.type),r.push(d.generateMipmaps),r.push(d.mipmapsAutoUpdate),r.push(d.premultiplyAlpha),r.push(d.flipY),r.push(d.unpackAlignment),r.push(d.colorSpace),r.join()}function he(d,r){const b=i.get(d);if(d.isVideoTexture&&it(d),d.isRenderTargetTexture===!1&&d.isExternalTexture!==!0&&d.version>0&&b.__version!==d.version){const Q=d.image;if(Q===null)He("WebGLRenderer: Texture marked for update but no image data found.");else if(Q.complete===!1)He("WebGLRenderer: Texture marked for update but image is incomplete");else{nt(b,d,r);return}}else d.isExternalTexture&&(b.__webglTexture=d.sourceTexture?d.sourceTexture:null);t.bindTexture(e.TEXTURE_2D,b.__webglTexture,e.TEXTURE0+r)}function Se(d,r){const b=i.get(d);if(d.isRenderTargetTexture===!1&&d.version>0&&b.__version!==d.version){nt(b,d,r);return}else d.isExternalTexture&&(b.__webglTexture=d.sourceTexture?d.sourceTexture:null);t.bindTexture(e.TEXTURE_2D_ARRAY,b.__webglTexture,e.TEXTURE0+r)}function Re(d,r){const b=i.get(d);if(d.isRenderTargetTexture===!1&&d.version>0&&b.__version!==d.version){nt(b,d,r);return}t.bindTexture(e.TEXTURE_3D,b.__webglTexture,e.TEXTURE0+r)}function me(d,r){const b=i.get(d);if(d.version>0&&b.__version!==d.version){gt(b,d,r);return}t.bindTexture(e.TEXTURE_CUBE_MAP,b.__webglTexture,e.TEXTURE0+r)}const we={[la]:e.REPEAT,[Rn]:e.CLAMP_TO_EDGE,[sa]:e.MIRRORED_REPEAT},_t={[Wt]:e.NEAREST,[ca]:e.NEAREST_MIPMAP_NEAREST,[dn]:e.NEAREST_MIPMAP_LINEAR,[At]:e.LINEAR,[Cn]:e.LINEAR_MIPMAP_NEAREST,[Ht]:e.LINEAR_MIPMAP_LINEAR},Ce={[ha]:e.NEVER,[ni]:e.ALWAYS,[pa]:e.LESS,[li]:e.LEQUAL,[ua]:e.EQUAL,[si]:e.GEQUAL,[da]:e.GREATER,[fa]:e.NOTEQUAL};function qe(d,r){if(r.type===yt&&n.has("OES_texture_float_linear")===!1&&(r.magFilter===At||r.magFilter===Cn||r.magFilter===dn||r.magFilter===Ht||r.minFilter===At||r.minFilter===Cn||r.minFilter===dn||r.minFilter===Ht)&&He("WebGLRenderer: Unable to use linear filtering with floating point textures. OES_texture_float_linear not supported on this device."),e.texParameteri(d,e.TEXTURE_WRAP_S,we[r.wrapS]),e.texParameteri(d,e.TEXTURE_WRAP_T,we[r.wrapT]),(d===e.TEXTURE_3D||d===e.TEXTURE_2D_ARRAY)&&e.texParameteri(d,e.TEXTURE_WRAP_R,we[r.wrapR]),e.texParameteri(d,e.TEXTURE_MAG_FILTER,_t[r.magFilter]),e.texParameteri(d,e.TEXTURE_MIN_FILTER,_t[r.minFilter]),r.compareFunction&&(e.texParameteri(d,e.TEXTURE_COMPARE_MODE,e.COMPARE_REF_TO_TEXTURE),e.texParameteri(d,e.TEXTURE_COMPARE_FUNC,Ce[r.compareFunction])),n.has("EXT_texture_filter_anisotropic")===!0){if(r.magFilter===Wt||r.minFilter!==dn&&r.minFilter!==Ht||r.type===yt&&n.has("OES_texture_float_linear")===!1)return;if(r.anisotropy>1||i.get(r).__currentAnisotropy){const b=n.get("EXT_texture_filter_anisotropic");e.texParameterf(d,b.TEXTURE_MAX_ANISOTROPY_EXT,Math.min(r.anisotropy,c.getMaxAnisotropy())),i.get(r).__currentAnisotropy=r.anisotropy}}}function ke(d,r){let b=!1;d.__webglInit===void 0&&(d.__webglInit=!0,r.addEventListener("dispose",N),q(r));const Q=r.source;let k=M.get(Q);k===void 0&&(k={},M.set(Q,k));const te=W(r);if(te!==d.__cacheKey){k[te]===void 0&&(k[te]={texture:e.createTexture(),usedTimes:0},u.memory.textures++,b=!0),k[te].usedTimes++;const se=k[d.__cacheKey];se!==void 0&&(k[d.__cacheKey].usedTimes--,se.usedTimes===0&&y(r)),d.__cacheKey=te,d.__webglTexture=k[te].texture}return b}function Ie(d,r,b){return Math.floor(Math.floor(d/b)/r)}function ct(d,r,b,Q){const te=d.updateRanges;if(te.length===0)t.texSubImage2D(e.TEXTURE_2D,0,0,0,r.width,r.height,b,Q,r.data);else{te.sort((h,V)=>h.start-V.start);let se=0;for(let h=1;h<te.length;h++){const V=te[se],O=te[h],ae=V.start+V.count,ee=Ie(O.start,r.width,4),ne=Ie(V.start,r.width,4);O.start<=ae+1&&ee===ne&&Ie(O.start+O.count-1,r.width,4)===ee?V.count=Math.max(V.count,O.start+O.count-V.start):(++se,te[se]=O)}te.length=se+1;const G=t.getParameter(e.UNPACK_ROW_LENGTH),H=t.getParameter(e.UNPACK_SKIP_PIXELS),re=t.getParameter(e.UNPACK_SKIP_ROWS);t.pixelStorei(e.UNPACK_ROW_LENGTH,r.width);for(let h=0,V=te.length;h<V;h++){const O=te[h],ae=Math.floor(O.start/4),ee=Math.ceil(O.count/4),ne=ae%r.width,Me=Math.floor(ae/r.width),Ue=ee,J=1;t.pixelStorei(e.UNPACK_SKIP_PIXELS,ne),t.pixelStorei(e.UNPACK_SKIP_ROWS,Me),t.texSubImage2D(e.TEXTURE_2D,0,ne,Me,Ue,J,b,Q,r.data)}d.clearUpdateRanges(),t.pixelStorei(e.UNPACK_ROW_LENGTH,G),t.pixelStorei(e.UNPACK_SKIP_PIXELS,H),t.pixelStorei(e.UNPACK_SKIP_ROWS,re)}}function nt(d,r,b){let Q=e.TEXTURE_2D;(r.isDataArrayTexture||r.isCompressedArrayTexture)&&(Q=e.TEXTURE_2D_ARRAY),r.isData3DTexture&&(Q=e.TEXTURE_3D);const k=ke(d,r),te=r.source;t.bindTexture(Q,d.__webglTexture,e.TEXTURE0+b);const se=i.get(te);if(te.version!==se.__version||k===!0){if(t.activeTexture(e.TEXTURE0+b),(typeof ImageBitmap<"u"&&r.image instanceof ImageBitmap)===!1){const J=Je.getPrimaries(Je.workingColorSpace),xe=r.colorSpace===Jt?null:Je.getPrimaries(r.colorSpace),Le=r.colorSpace===Jt||J===xe?e.NONE:e.BROWSER_DEFAULT_WEBGL;t.pixelStorei(e.UNPACK_FLIP_Y_WEBGL,r.flipY),t.pixelStorei(e.UNPACK_PREMULTIPLY_ALPHA_WEBGL,r.premultiplyAlpha),t.pixelStorei(e.UNPACK_COLORSPACE_CONVERSION_WEBGL,Le)}t.pixelStorei(e.UNPACK_ALIGNMENT,r.unpackAlignment);let H=g(r.image,!1,c.maxTextureSize);H=Te(r,H);const re=s.convert(r.format,r.colorSpace),h=s.convert(r.type);let V=x(r.internalFormat,re,h,r.normalized,r.colorSpace,r.isVideoTexture);qe(Q,r);let O;const ae=r.mipmaps,ee=r.isVideoTexture!==!0,ne=se.__version===void 0||k===!0,Me=te.dataReady,Ue=p(r,H);if(r.isDepthTexture)V=o(r.format===Vt,r.type),ne&&(ee?t.texStorage2D(e.TEXTURE_2D,1,V,H.width,H.height):t.texImage2D(e.TEXTURE_2D,0,V,H.width,H.height,0,re,h,null));else if(r.isDataTexture)if(ae.length>0){ee&&ne&&t.texStorage2D(e.TEXTURE_2D,Ue,V,ae[0].width,ae[0].height);for(let J=0,xe=ae.length;J<xe;J++)O=ae[J],ee?Me&&t.texSubImage2D(e.TEXTURE_2D,J,0,0,O.width,O.height,re,h,O.data):t.texImage2D(e.TEXTURE_2D,J,V,O.width,O.height,0,re,h,O.data);r.generateMipmaps=!1}else ee?(ne&&t.texStorage2D(e.TEXTURE_2D,Ue,V,H.width,H.height),Me&&ct(r,H,re,h)):t.texImage2D(e.TEXTURE_2D,0,V,H.width,H.height,0,re,h,H.data);else if(r.isCompressedTexture)if(r.isCompressedArrayTexture){ee&&ne&&t.texStorage3D(e.TEXTURE_2D_ARRAY,Ue,V,ae[0].width,ae[0].height,H.depth);for(let J=0,xe=ae.length;J<xe;J++)if(O=ae[J],r.format!==Ft)if(re!==null)if(ee){if(Me)if(r.layerUpdates.size>0){const Le=ma(O.width,O.height,r.format,r.type);for(const ue of r.layerUpdates){const Ve=O.data.subarray(ue*Le/O.data.BYTES_PER_ELEMENT,(ue+1)*Le/O.data.BYTES_PER_ELEMENT);t.compressedTexSubImage3D(e.TEXTURE_2D_ARRAY,J,0,0,ue,O.width,O.height,1,re,Ve)}}else t.compressedTexSubImage3D(e.TEXTURE_2D_ARRAY,J,0,0,0,O.width,O.height,H.depth,re,O.data)}else t.compressedTexImage3D(e.TEXTURE_2D_ARRAY,J,V,O.width,O.height,H.depth,0,O.data,0,0);else He("WebGLRenderer: Attempt to load unsupported compressed texture format in .uploadTexture()");else ee?Me&&t.texSubImage3D(e.TEXTURE_2D_ARRAY,J,0,0,0,O.width,O.height,H.depth,re,h,O.data):t.texImage3D(e.TEXTURE_2D_ARRAY,J,V,O.width,O.height,H.depth,0,re,h,O.data);r.layerUpdates.size>0&&r.clearLayerUpdates()}else{ee&&ne&&t.texStorage2D(e.TEXTURE_2D,Ue,V,ae[0].width,ae[0].height);for(let J=0,xe=ae.length;J<xe;J++)O=ae[J],r.format!==Ft?re!==null?ee?Me&&t.compressedTexSubImage2D(e.TEXTURE_2D,J,0,0,O.width,O.height,re,O.data):t.compressedTexImage2D(e.TEXTURE_2D,J,V,O.width,O.height,0,O.data):He("WebGLRenderer: Attempt to load unsupported compressed texture format in .uploadTexture()"):ee?Me&&t.texSubImage2D(e.TEXTURE_2D,J,0,0,O.width,O.height,re,h,O.data):t.texImage2D(e.TEXTURE_2D,J,V,O.width,O.height,0,re,h,O.data)}else if(r.isDataArrayTexture)if(ee){if(ne&&t.texStorage3D(e.TEXTURE_2D_ARRAY,Ue,V,H.width,H.height,H.depth),Me)if(r.layerUpdates.size>0){const J=ma(H.width,H.height,r.format,r.type);for(const xe of r.layerUpdates){const Le=H.data.subarray(xe*J/H.data.BYTES_PER_ELEMENT,(xe+1)*J/H.data.BYTES_PER_ELEMENT);t.texSubImage3D(e.TEXTURE_2D_ARRAY,0,0,0,xe,H.width,H.height,1,re,h,Le)}r.clearLayerUpdates()}else t.texSubImage3D(e.TEXTURE_2D_ARRAY,0,0,0,0,H.width,H.height,H.depth,re,h,H.data)}else t.texImage3D(e.TEXTURE_2D_ARRAY,0,V,H.width,H.height,H.depth,0,re,h,H.data);else if(r.isData3DTexture)ee?(ne&&t.texStorage3D(e.TEXTURE_3D,Ue,V,H.width,H.height,H.depth),Me&&t.texSubImage3D(e.TEXTURE_3D,0,0,0,0,H.width,H.height,H.depth,re,h,H.data)):t.texImage3D(e.TEXTURE_3D,0,V,H.width,H.height,H.depth,0,re,h,H.data);else if(r.isFramebufferTexture){if(ne)if(ee)t.texStorage2D(e.TEXTURE_2D,Ue,V,H.width,H.height);else{let J=H.width,xe=H.height;for(let Le=0;Le<Ue;Le++)t.texImage2D(e.TEXTURE_2D,Le,V,J,xe,0,re,h,null),J>>=1,xe>>=1}}else if(r.isHTMLTexture){if("texElementImage2D"in e){const J=e.canvas;if(J.hasAttribute("layoutsubtree")||J.setAttribute("layoutsubtree","true"),H.parentNode!==J){J.appendChild(H),F.add(r),J.onpaint=xe=>{const Le=xe.changedElements;for(const ue of F)Le.includes(ue.image)&&(ue.needsUpdate=!0)},J.requestPaint();return}if(e.texElementImage2D.length===3)e.texElementImage2D(e.TEXTURE_2D,e.RGBA8,H);else{const Le=e.RGBA,ue=e.RGBA,Ve=e.UNSIGNED_BYTE;e.texElementImage2D(e.TEXTURE_2D,0,Le,ue,Ve,H)}e.texParameteri(e.TEXTURE_2D,e.TEXTURE_MIN_FILTER,e.LINEAR),e.texParameteri(e.TEXTURE_2D,e.TEXTURE_WRAP_S,e.CLAMP_TO_EDGE),e.texParameteri(e.TEXTURE_2D,e.TEXTURE_WRAP_T,e.CLAMP_TO_EDGE)}}else if(ae.length>0){if(ee&&ne){const J=be(ae[0]);t.texStorage2D(e.TEXTURE_2D,Ue,V,J.width,J.height)}for(let J=0,xe=ae.length;J<xe;J++)O=ae[J],ee?Me&&t.texSubImage2D(e.TEXTURE_2D,J,0,0,re,h,O):t.texImage2D(e.TEXTURE_2D,J,V,re,h,O);r.generateMipmaps=!1}else if(ee){if(ne){const J=be(H);t.texStorage2D(e.TEXTURE_2D,Ue,V,J.width,J.height)}Me&&t.texSubImage2D(e.TEXTURE_2D,0,0,0,re,h,H)}else t.texImage2D(e.TEXTURE_2D,0,V,re,h,H);U(r)&&r.mipmapsAutoUpdate===!0&&S(Q),se.__version=te.version,r.onUpdate&&r.onUpdate(r)}d.__version=r.version}function gt(d,r,b){if(r.image.length!==6)return;const Q=ke(d,r),k=r.source;t.bindTexture(e.TEXTURE_CUBE_MAP,d.__webglTexture,e.TEXTURE0+b);const te=i.get(k);if(k.version!==te.__version||Q===!0){t.activeTexture(e.TEXTURE0+b);const se=Je.getPrimaries(Je.workingColorSpace),G=r.colorSpace===Jt?null:Je.getPrimaries(r.colorSpace),H=r.colorSpace===Jt||se===G?e.NONE:e.BROWSER_DEFAULT_WEBGL;t.pixelStorei(e.UNPACK_FLIP_Y_WEBGL,r.flipY),t.pixelStorei(e.UNPACK_PREMULTIPLY_ALPHA_WEBGL,r.premultiplyAlpha),t.pixelStorei(e.UNPACK_ALIGNMENT,r.unpackAlignment),t.pixelStorei(e.UNPACK_COLORSPACE_CONVERSION_WEBGL,H);const re=r.isCompressedTexture||r.image[0].isCompressedTexture,h=r.image[0]&&r.image[0].isDataTexture,V=[];for(let ue=0;ue<6;ue++)!re&&!h?V[ue]=g(r.image[ue],!0,c.maxCubemapSize):V[ue]=h?r.image[ue].image:r.image[ue],V[ue]=Te(r,V[ue]);const O=V[0],ae=s.convert(r.format,r.colorSpace),ee=s.convert(r.type);let ne=x(r.internalFormat,ae,ee,r.normalized,r.colorSpace);const Me=r.isVideoTexture!==!0,Ue=te.__version===void 0||Q===!0,J=k.dataReady;let xe=p(r,O);qe(e.TEXTURE_CUBE_MAP,r);let Le;if(r.isDepthTexture){if(ne=o(r.format===Vt,r.type),Ue)if(Me)t.texStorage2D(e.TEXTURE_CUBE_MAP,1,ne,O.width,O.height);else for(let ue=0;ue<6;ue++)t.texImage2D(e.TEXTURE_CUBE_MAP_POSITIVE_X+ue,0,ne,O.width,O.height,0,ae,ee,null)}else if(re){Me&&Ue&&t.texStorage2D(e.TEXTURE_CUBE_MAP,xe,ne,O.width,O.height);for(let ue=0;ue<6;ue++){Le=V[ue].mipmaps;for(let Ve=0;Ve<Le.length;Ve++){const st=Le[Ve];r.format!==Ft?ae!==null?Me?J&&t.compressedTexSubImage2D(e.TEXTURE_CUBE_MAP_POSITIVE_X+ue,Ve,0,0,st.width,st.height,ae,st.data):t.compressedTexImage2D(e.TEXTURE_CUBE_MAP_POSITIVE_X+ue,Ve,ne,st.width,st.height,0,st.data):He("WebGLRenderer: Attempt to load unsupported compressed texture format in .setTextureCube()"):Me?J&&t.texSubImage2D(e.TEXTURE_CUBE_MAP_POSITIVE_X+ue,Ve,0,0,st.width,st.height,ae,ee,st.data):t.texImage2D(e.TEXTURE_CUBE_MAP_POSITIVE_X+ue,Ve,ne,st.width,st.height,0,ae,ee,st.data)}}}else{if(Le=r.mipmaps,Me&&Ue){Le.length>0&&xe++;const ue=be(V[0]);t.texStorage2D(e.TEXTURE_CUBE_MAP,xe,ne,ue.width,ue.height)}for(let ue=0;ue<6;ue++)if(h){Me?J&&t.texSubImage2D(e.TEXTURE_CUBE_MAP_POSITIVE_X+ue,0,0,0,V[ue].width,V[ue].height,ae,ee,V[ue].data):t.texImage2D(e.TEXTURE_CUBE_MAP_POSITIVE_X+ue,0,ne,V[ue].width,V[ue].height,0,ae,ee,V[ue].data);for(let Ve=0;Ve<Le.length;Ve++){const Ot=Le[Ve].image[ue].image;Me?J&&t.texSubImage2D(e.TEXTURE_CUBE_MAP_POSITIVE_X+ue,Ve+1,0,0,Ot.width,Ot.height,ae,ee,Ot.data):t.texImage2D(e.TEXTURE_CUBE_MAP_POSITIVE_X+ue,Ve+1,ne,Ot.width,Ot.height,0,ae,ee,Ot.data)}}else{Me?J&&t.texSubImage2D(e.TEXTURE_CUBE_MAP_POSITIVE_X+ue,0,0,0,ae,ee,V[ue]):t.texImage2D(e.TEXTURE_CUBE_MAP_POSITIVE_X+ue,0,ne,ae,ee,V[ue]);for(let Ve=0;Ve<Le.length;Ve++){const st=Le[Ve];Me?J&&t.texSubImage2D(e.TEXTURE_CUBE_MAP_POSITIVE_X+ue,Ve+1,0,0,ae,ee,st.image[ue]):t.texImage2D(e.TEXTURE_CUBE_MAP_POSITIVE_X+ue,Ve+1,ne,ae,ee,st.image[ue])}}}U(r)&&r.mipmapsAutoUpdate===!0&&S(e.TEXTURE_CUBE_MAP),te.__version=k.version,r.onUpdate&&r.onUpdate(r)}d.__version=r.version}function je(d,r,b,Q,k,te){const se=s.convert(b.format,b.colorSpace),G=s.convert(b.type),H=x(b.internalFormat,se,G,b.normalized,b.colorSpace),re=i.get(r),h=i.get(b);if(h.__renderTarget=r,!re.__hasExternalTextures){const V=Math.max(1,r.width>>te),O=Math.max(1,r.height>>te);k===e.TEXTURE_3D||k===e.TEXTURE_2D_ARRAY?t.texImage3D(k,te,H,V,O,r.depth,0,se,G,null):t.texImage2D(k,te,H,V,O,0,se,G,null)}t.bindFramebuffer(e.FRAMEBUFFER,d),ce(r)?m.framebufferTexture2DMultisampleEXT(e.FRAMEBUFFER,Q,k,h.__webglTexture,0,Qe(r)):(k===e.TEXTURE_2D||k>=e.TEXTURE_CUBE_MAP_POSITIVE_X&&k<=e.TEXTURE_CUBE_MAP_NEGATIVE_Z)&&e.framebufferTexture2D(e.FRAMEBUFFER,Q,k,h.__webglTexture,te),t.bindFramebuffer(e.FRAMEBUFFER,null)}function Lt(d,r,b){if(e.bindRenderbuffer(e.RENDERBUFFER,d),r.depthBuffer){const Q=r.depthTexture,k=Q&&Q.isDepthTexture?Q.type:null,te=o(r.stencilBuffer,k),se=r.stencilBuffer?e.DEPTH_STENCIL_ATTACHMENT:e.DEPTH_ATTACHMENT;ce(r)?m.renderbufferStorageMultisampleEXT(e.RENDERBUFFER,Qe(r),te,r.width,r.height):b?e.renderbufferStorageMultisample(e.RENDERBUFFER,Qe(r),te,r.width,r.height):e.renderbufferStorage(e.RENDERBUFFER,te,r.width,r.height),e.framebufferRenderbuffer(e.FRAMEBUFFER,se,e.RENDERBUFFER,d)}else{const Q=r.textures;for(let k=0;k<Q.length;k++){const te=Q[k],se=s.convert(te.format,te.colorSpace),G=s.convert(te.type),H=x(te.internalFormat,se,G,te.normalized,te.colorSpace);ce(r)?m.renderbufferStorageMultisampleEXT(e.RENDERBUFFER,Qe(r),H,r.width,r.height):b?e.renderbufferStorageMultisample(e.RENDERBUFFER,Qe(r),H,r.width,r.height):e.renderbufferStorage(e.RENDERBUFFER,H,r.width,r.height)}}e.bindRenderbuffer(e.RENDERBUFFER,null)}function ot(d,r,b){const Q=r.isWebGLCubeRenderTarget===!0;if(t.bindFramebuffer(e.FRAMEBUFFER,d),!(r.depthTexture&&r.depthTexture.isDepthTexture))throw new Error("THREE.WebGLTextures: renderTarget.depthTexture must be an instance of THREE.DepthTexture.");const k=i.get(r.depthTexture);k.__renderTarget=r;const te=Q?r.depthTexture.image[0]:r.depthTexture.image;(!k.__webglTexture||te.width!==r.width||te.height!==r.height)&&(te.width=r.width,te.height=r.height,r.depthTexture.needsUpdate=!0),Q?me(r.depthTexture,0):he(r.depthTexture,0);const se=k.__webglTexture,G=Qe(r),H=Q?e.TEXTURE_CUBE_MAP_POSITIVE_X+b:e.TEXTURE_2D,re=r.depthTexture.format===Vt?e.DEPTH_STENCIL_ATTACHMENT:e.DEPTH_ATTACHMENT;if(r.depthTexture.format===rn)ce(r)?m.framebufferTexture2DMultisampleEXT(e.FRAMEBUFFER,re,H,se,0,G):e.framebufferTexture2D(e.FRAMEBUFFER,re,H,se,0);else if(r.depthTexture.format===Vt)ce(r)?m.framebufferTexture2DMultisampleEXT(e.FRAMEBUFFER,re,H,se,0,G):e.framebufferTexture2D(e.FRAMEBUFFER,re,H,se,0);else throw new Error("THREE.WebGLTextures: Unknown depthTexture format.")}function Et(d){const r=i.get(d),b=d.isWebGLCubeRenderTarget===!0;if(r.__boundDepthTexture!==d.depthTexture){const Q=d.depthTexture;if(r.__depthDisposeCallback&&r.__depthDisposeCallback(),Q){const k=()=>{delete r.__boundDepthTexture,delete r.__depthDisposeCallback,Q.removeEventListener("dispose",k)};Q.addEventListener("dispose",k),r.__depthDisposeCallback=k}r.__boundDepthTexture=Q}if(d.depthTexture&&!r.__autoAllocateDepthBuffer)if(b)for(let Q=0;Q<6;Q++)ot(r.__webglFramebuffer[Q],d,Q);else d.texture.mipmaps.length>0?ot(r.__webglFramebuffer[0],d,0):ot(r.__webglFramebuffer,d,0);else if(b){r.__webglDepthbuffer=[];for(let Q=0;Q<6;Q++)if(t.bindFramebuffer(e.FRAMEBUFFER,r.__webglFramebuffer[Q]),r.__webglDepthbuffer[Q]===void 0)r.__webglDepthbuffer[Q]=e.createRenderbuffer(),Lt(r.__webglDepthbuffer[Q],d,!1);else{const k=d.stencilBuffer?e.DEPTH_STENCIL_ATTACHMENT:e.DEPTH_ATTACHMENT,te=r.__webglDepthbuffer[Q];e.bindRenderbuffer(e.RENDERBUFFER,te),e.framebufferRenderbuffer(e.FRAMEBUFFER,k,e.RENDERBUFFER,te)}}else if(d.texture.mipmaps.length>0?t.bindFramebuffer(e.FRAMEBUFFER,r.__webglFramebuffer[0]):t.bindFramebuffer(e.FRAMEBUFFER,r.__webglFramebuffer),r.__webglDepthbuffer===void 0)r.__webglDepthbuffer=e.createRenderbuffer(),Lt(r.__webglDepthbuffer,d,!1);else{const k=d.stencilBuffer?e.DEPTH_STENCIL_ATTACHMENT:e.DEPTH_ATTACHMENT,te=r.__webglDepthbuffer;e.bindRenderbuffer(e.RENDERBUFFER,te),e.framebufferRenderbuffer(e.FRAMEBUFFER,k,e.RENDERBUFFER,te)}t.bindFramebuffer(e.FRAMEBUFFER,null)}function Mt(d,r,b){const Q=i.get(d);r!==void 0&&je(Q.__webglFramebuffer,d,d.texture,e.COLOR_ATTACHMENT0,e.TEXTURE_2D,0),b!==void 0&&Et(d)}function xt(d){const r=d.texture,b=i.get(d),Q=i.get(r);d.addEventListener("dispose",z),q(d);const k=d.textures,te=d.isWebGLCubeRenderTarget===!0,se=k.length>1;if(se||(Q.__webglTexture===void 0&&(Q.__webglTexture=e.createTexture()),Q.__version=r.version,u.memory.textures++),te){b.__webglFramebuffer=[];for(let G=0;G<6;G++)if(r.mipmaps.length>0){b.__webglFramebuffer[G]=[];for(let H=0;H<r.mipmaps.length;H++)b.__webglFramebuffer[G][H]=e.createFramebuffer()}else b.__webglFramebuffer[G]=e.createFramebuffer()}else{if(r.mipmaps.length>0){b.__webglFramebuffer=[];for(let G=0;G<r.mipmaps.length;G++)b.__webglFramebuffer[G]=e.createFramebuffer()}else b.__webglFramebuffer=e.createFramebuffer();if(se)for(let G=0,H=k.length;G<H;G++){const re=i.get(k[G]);re.__webglTexture===void 0&&(re.__webglTexture=e.createTexture(),u.memory.textures++)}if(d.samples>0&&ce(d)===!1){b.__webglMultisampledFramebuffer=e.createFramebuffer(),b.__webglColorRenderbuffer=[],t.bindFramebuffer(e.FRAMEBUFFER,b.__webglMultisampledFramebuffer);for(let G=0;G<k.length;G++){const H=k[G];b.__webglColorRenderbuffer[G]=e.createRenderbuffer(),e.bindRenderbuffer(e.RENDERBUFFER,b.__webglColorRenderbuffer[G]);const re=s.convert(H.format,H.colorSpace),h=s.convert(H.type),V=x(H.internalFormat,re,h,H.normalized,H.colorSpace,d.isXRRenderTarget===!0),O=Qe(d);e.renderbufferStorageMultisample(e.RENDERBUFFER,O,V,d.width,d.height),e.framebufferRenderbuffer(e.FRAMEBUFFER,e.COLOR_ATTACHMENT0+G,e.RENDERBUFFER,b.__webglColorRenderbuffer[G])}e.bindRenderbuffer(e.RENDERBUFFER,null),d.depthBuffer&&(b.__webglDepthRenderbuffer=e.createRenderbuffer(),Lt(b.__webglDepthRenderbuffer,d,!0)),t.bindFramebuffer(e.FRAMEBUFFER,null)}}if(te){t.bindTexture(e.TEXTURE_CUBE_MAP,Q.__webglTexture),qe(e.TEXTURE_CUBE_MAP,r);for(let G=0;G<6;G++)if(r.mipmaps.length>0)for(let H=0;H<r.mipmaps.length;H++)je(b.__webglFramebuffer[G][H],d,r,e.COLOR_ATTACHMENT0,e.TEXTURE_CUBE_MAP_POSITIVE_X+G,H);else je(b.__webglFramebuffer[G],d,r,e.COLOR_ATTACHMENT0,e.TEXTURE_CUBE_MAP_POSITIVE_X+G,0);U(r)?S(e.TEXTURE_CUBE_MAP):r.mipmaps.length>0&&e.texParameteri(e.TEXTURE_CUBE_MAP,e.TEXTURE_MAX_LEVEL,r.mipmaps.length-1),t.unbindTexture()}else if(se){for(let G=0,H=k.length;G<H;G++){const re=k[G],h=i.get(re);let V=e.TEXTURE_2D;(d.isWebGL3DRenderTarget||d.isWebGLArrayRenderTarget)&&(V=d.isWebGL3DRenderTarget?e.TEXTURE_3D:e.TEXTURE_2D_ARRAY),t.bindTexture(V,h.__webglTexture),qe(V,re),je(b.__webglFramebuffer,d,re,e.COLOR_ATTACHMENT0+G,V,0),U(re)&&S(V)}t.unbindTexture()}else{let G=e.TEXTURE_2D;if((d.isWebGL3DRenderTarget||d.isWebGLArrayRenderTarget)&&(G=d.isWebGL3DRenderTarget?e.TEXTURE_3D:e.TEXTURE_2D_ARRAY),t.bindTexture(G,Q.__webglTexture),qe(G,r),r.mipmaps.length>0)for(let H=0;H<r.mipmaps.length;H++)je(b.__webglFramebuffer[H],d,r,e.COLOR_ATTACHMENT0,G,H);else je(b.__webglFramebuffer,d,r,e.COLOR_ATTACHMENT0,G,0);U(r)?S(G):r.mipmaps.length>0&&e.texParameteri(G,e.TEXTURE_MAX_LEVEL,r.mipmaps.length-1),t.unbindTexture()}d.depthBuffer&&Et(d)}function E(d){const r=d.textures;for(let b=0,Q=r.length;b<Q;b++){const k=r[b];if(U(k)&&k.mipmapsAutoUpdate===!0){const te=R(d),se=i.get(k).__webglTexture;t.bindTexture(te,se),S(te),t.unbindTexture()}}}const Tt=[];function $e(d){if(d.samples>0){if(ce(d)===!1){const r=d.textures,b=d.width,Q=d.height;let k=e.COLOR_BUFFER_BIT;const te=d.stencilBuffer?e.DEPTH_STENCIL_ATTACHMENT:e.DEPTH_ATTACHMENT,se=i.get(d),G=r.length>1;if(G)for(let re=0;re<r.length;re++)t.bindFramebuffer(e.FRAMEBUFFER,se.__webglMultisampledFramebuffer),e.framebufferRenderbuffer(e.FRAMEBUFFER,e.COLOR_ATTACHMENT0+re,e.RENDERBUFFER,null),t.bindFramebuffer(e.FRAMEBUFFER,se.__webglFramebuffer),e.framebufferTexture2D(e.DRAW_FRAMEBUFFER,e.COLOR_ATTACHMENT0+re,e.TEXTURE_2D,null,0);t.bindFramebuffer(e.READ_FRAMEBUFFER,se.__webglMultisampledFramebuffer),d.texture.mipmaps.length>0?t.bindFramebuffer(e.DRAW_FRAMEBUFFER,se.__webglFramebuffer[0]):t.bindFramebuffer(e.DRAW_FRAMEBUFFER,se.__webglFramebuffer);for(let re=0;re<r.length;re++){if(d.resolveDepthBuffer&&(d.depthBuffer&&(k|=e.DEPTH_BUFFER_BIT),d.stencilBuffer&&d.resolveStencilBuffer&&(k|=e.STENCIL_BUFFER_BIT)),G){e.framebufferRenderbuffer(e.READ_FRAMEBUFFER,e.COLOR_ATTACHMENT0,e.RENDERBUFFER,se.__webglColorRenderbuffer[re]);const h=i.get(r[re]).__webglTexture;e.framebufferTexture2D(e.DRAW_FRAMEBUFFER,e.COLOR_ATTACHMENT0,e.TEXTURE_2D,h,0)}e.blitFramebuffer(0,0,b,Q,0,0,b,Q,k,e.NEAREST),I===!0&&(Tt.length=0,Tt.push(e.COLOR_ATTACHMENT0+re),d.depthBuffer&&d.storeMultisampledDepthBuffer===!1&&Tt.push(te),e.invalidateFramebuffer(e.READ_FRAMEBUFFER,Tt))}if(t.bindFramebuffer(e.READ_FRAMEBUFFER,null),t.bindFramebuffer(e.DRAW_FRAMEBUFFER,null),G)for(let re=0;re<r.length;re++){t.bindFramebuffer(e.FRAMEBUFFER,se.__webglMultisampledFramebuffer),e.framebufferRenderbuffer(e.FRAMEBUFFER,e.COLOR_ATTACHMENT0+re,e.RENDERBUFFER,se.__webglColorRenderbuffer[re]);const h=i.get(r[re]).__webglTexture;t.bindFramebuffer(e.FRAMEBUFFER,se.__webglFramebuffer),e.framebufferTexture2D(e.DRAW_FRAMEBUFFER,e.COLOR_ATTACHMENT0+re,e.TEXTURE_2D,h,0)}t.bindFramebuffer(e.DRAW_FRAMEBUFFER,se.__webglMultisampledFramebuffer)}else if(d.depthBuffer&&d.storeMultisampledDepthBuffer===!1&&I){const r=d.stencilBuffer?e.DEPTH_STENCIL_ATTACHMENT:e.DEPTH_ATTACHMENT;e.invalidateFramebuffer(e.DRAW_FRAMEBUFFER,[r])}}}function Qe(d){return Math.min(c.maxSamples,d.samples)}function ce(d){const r=i.get(d);return d.samples>0&&n.has("WEBGL_multisampled_render_to_texture")===!0&&r.__useRenderToTexture!==!1}function it(d){const r=u.render.frame;X.get(d)!==r&&(X.set(d,r),d.update())}function Te(d,r){const b=d.colorSpace,Q=d.format,k=d.type;return d.isCompressedTexture===!0||d.isVideoTexture===!0||b!==Xn&&b!==Jt&&(Je.getTransfer(b)===Xe?(Q!==Ft||k!==Ct)&&He("WebGLTextures: sRGB encoded textures have to use RGBAFormat and UnsignedByteType."):Ze("WebGLTextures: Unsupported texture color space:",b)),r}function be(d){return typeof HTMLImageElement<"u"&&d instanceof HTMLImageElement?(C.width=d.naturalWidth||d.width,C.height=d.naturalHeight||d.height):typeof VideoFrame<"u"&&d instanceof VideoFrame?(C.width=d.displayWidth,C.height=d.displayHeight):(C.width=d.width,C.height=d.height),C}this.allocateTextureUnit=Ne,this.resetTextureUnits=j,this.getTextureUnits=ie,this.setTextureUnits=Ae,this.setTexture2D=he,this.setTexture2DArray=Se,this.setTexture3D=Re,this.setTextureCube=me,this.rebindTextures=Mt,this.setupRenderTarget=xt,this.updateRenderTargetMipmap=E,this.updateMultisampleRenderTarget=$e,this.setupDepthRenderbuffer=Et,this.setupFrameBufferTexture=je,this.useMultisampledRTT=ce,this.dispose=Z}function so(e,n){function t(i,c=Jt){let s;const u=Je.getTransfer(c);if(i===Ct)return e.UNSIGNED_BYTE;if(i===mi)return e.UNSIGNED_SHORT_4_4_4_4;if(i===_i)return e.UNSIGNED_SHORT_5_5_5_1;if(i===_a)return e.UNSIGNED_INT_5_9_9_9_REV;if(i===ga)return e.UNSIGNED_INT_10F_11F_11F_REV;if(i===va)return e.BYTE;if(i===Sa)return e.SHORT;if(i===un)return e.UNSIGNED_SHORT;if(i===kn)return e.INT;if(i===kt)return e.UNSIGNED_INT;if(i===yt)return e.FLOAT;if(i===Dt)return e.HALF_FLOAT;if(i===Ea)return e.ALPHA;if(i===Ma)return e.RGB;if(i===Ft)return e.RGBA;if(i===rn)return e.DEPTH_COMPONENT;if(i===Vt)return e.DEPTH_STENCIL;if(i===xa)return e.RED;if(i===gi)return e.RED_INTEGER;if(i===Qt)return e.RG;if(i===vi)return e.RG_INTEGER;if(i===Si)return e.RGBA_INTEGER;if(i===bn||i===Pn||i===Ln||i===Dn)if(u===Xe)if(s=n.get("WEBGL_compressed_texture_s3tc_srgb"),s!==null){if(i===bn)return s.COMPRESSED_SRGB_S3TC_DXT1_EXT;if(i===Pn)return s.COMPRESSED_SRGB_ALPHA_S3TC_DXT1_EXT;if(i===Ln)return s.COMPRESSED_SRGB_ALPHA_S3TC_DXT3_EXT;if(i===Dn)return s.COMPRESSED_SRGB_ALPHA_S3TC_DXT5_EXT}else return null;else if(s=n.get("WEBGL_compressed_texture_s3tc"),s!==null){if(i===bn)return s.COMPRESSED_RGB_S3TC_DXT1_EXT;if(i===Pn)return s.COMPRESSED_RGBA_S3TC_DXT1_EXT;if(i===Ln)return s.COMPRESSED_RGBA_S3TC_DXT3_EXT;if(i===Dn)return s.COMPRESSED_RGBA_S3TC_DXT5_EXT}else return null;if(i===Ei||i===Mi||i===xi||i===Ti)if(s=n.get("WEBGL_compressed_texture_pvrtc"),s!==null){if(i===Ei)return s.COMPRESSED_RGB_PVRTC_4BPPV1_IMG;if(i===Mi)return s.COMPRESSED_RGB_PVRTC_2BPPV1_IMG;if(i===xi)return s.COMPRESSED_RGBA_PVRTC_4BPPV1_IMG;if(i===Ti)return s.COMPRESSED_RGBA_PVRTC_2BPPV1_IMG}else return null;if(i===Ai||i===Ri||i===Ci||i===bi||i===Pi||i===Tn||i===Li)if(s=n.get("WEBGL_compressed_texture_etc"),s!==null){if(i===Ai||i===Ri)return u===Xe?s.COMPRESSED_SRGB8_ETC2:s.COMPRESSED_RGB8_ETC2;if(i===Ci)return u===Xe?s.COMPRESSED_SRGB8_ALPHA8_ETC2_EAC:s.COMPRESSED_RGBA8_ETC2_EAC;if(i===bi)return s.COMPRESSED_R11_EAC;if(i===Pi)return s.COMPRESSED_SIGNED_R11_EAC;if(i===Tn)return s.COMPRESSED_RG11_EAC;if(i===Li)return s.COMPRESSED_SIGNED_RG11_EAC}else return null;if(i===Di||i===Ui||i===wi||i===Ni||i===Ii||i===yi||i===Fi||i===Oi||i===Bi||i===Gi||i===Hi||i===Vi||i===Wi||i===ki)if(s=n.get("WEBGL_compressed_texture_astc"),s!==null){if(i===Di)return u===Xe?s.COMPRESSED_SRGB8_ALPHA8_ASTC_4x4_KHR:s.COMPRESSED_RGBA_ASTC_4x4_KHR;if(i===Ui)return u===Xe?s.COMPRESSED_SRGB8_ALPHA8_ASTC_5x4_KHR:s.COMPRESSED_RGBA_ASTC_5x4_KHR;if(i===wi)return u===Xe?s.COMPRESSED_SRGB8_ALPHA8_ASTC_5x5_KHR:s.COMPRESSED_RGBA_ASTC_5x5_KHR;if(i===Ni)return u===Xe?s.COMPRESSED_SRGB8_ALPHA8_ASTC_6x5_KHR:s.COMPRESSED_RGBA_ASTC_6x5_KHR;if(i===Ii)return u===Xe?s.COMPRESSED_SRGB8_ALPHA8_ASTC_6x6_KHR:s.COMPRESSED_RGBA_ASTC_6x6_KHR;if(i===yi)return u===Xe?s.COMPRESSED_SRGB8_ALPHA8_ASTC_8x5_KHR:s.COMPRESSED_RGBA_ASTC_8x5_KHR;if(i===Fi)return u===Xe?s.COMPRESSED_SRGB8_ALPHA8_ASTC_8x6_KHR:s.COMPRESSED_RGBA_ASTC_8x6_KHR;if(i===Oi)return u===Xe?s.COMPRESSED_SRGB8_ALPHA8_ASTC_8x8_KHR:s.COMPRESSED_RGBA_ASTC_8x8_KHR;if(i===Bi)return u===Xe?s.COMPRESSED_SRGB8_ALPHA8_ASTC_10x5_KHR:s.COMPRESSED_RGBA_ASTC_10x5_KHR;if(i===Gi)return u===Xe?s.COMPRESSED_SRGB8_ALPHA8_ASTC_10x6_KHR:s.COMPRESSED_RGBA_ASTC_10x6_KHR;if(i===Hi)return u===Xe?s.COMPRESSED_SRGB8_ALPHA8_ASTC_10x8_KHR:s.COMPRESSED_RGBA_ASTC_10x8_KHR;if(i===Vi)return u===Xe?s.COMPRESSED_SRGB8_ALPHA8_ASTC_10x10_KHR:s.COMPRESSED_RGBA_ASTC_10x10_KHR;if(i===Wi)return u===Xe?s.COMPRESSED_SRGB8_ALPHA8_ASTC_12x10_KHR:s.COMPRESSED_RGBA_ASTC_12x10_KHR;if(i===ki)return u===Xe?s.COMPRESSED_SRGB8_ALPHA8_ASTC_12x12_KHR:s.COMPRESSED_RGBA_ASTC_12x12_KHR}else return null;if(i===zi||i===Xi||i===Ki)if(s=n.get("EXT_texture_compression_bptc"),s!==null){if(i===zi)return u===Xe?s.COMPRESSED_SRGB_ALPHA_BPTC_UNORM_EXT:s.COMPRESSED_RGBA_BPTC_UNORM_EXT;if(i===Xi)return s.COMPRESSED_RGB_BPTC_SIGNED_FLOAT_EXT;if(i===Ki)return s.COMPRESSED_RGB_BPTC_UNSIGNED_FLOAT_EXT}else return null;if(i===qi||i===Yi||i===An||i===Zi)if(s=n.get("EXT_texture_compression_rgtc"),s!==null){if(i===qi)return s.COMPRESSED_RED_RGTC1_EXT;if(i===Yi)return s.COMPRESSED_SIGNED_RED_RGTC1_EXT;if(i===An)return s.COMPRESSED_RED_GREEN_RGTC2_EXT;if(i===Zi)return s.COMPRESSED_SIGNED_RED_GREEN_RGTC2_EXT}else return null;return i===$t?e.UNSIGNED_INT_24_8:e[i]!==void 0?e[i]:null}return{convert:t}}const id=`
void main() {

	gl_Position = vec4( position, 1.0 );

}`,rd=`
uniform sampler2DArray depthColor;
uniform float depthWidth;
uniform float depthHeight;

void main() {

	vec2 coord = vec2( gl_FragCoord.x / depthWidth, gl_FragCoord.y / depthHeight );

	if ( coord.x >= 1.0 ) {

		gl_FragDepth = texture( depthColor, vec3( coord.x - 1.0, coord.y, 1 ) ).r;

	} else {

		gl_FragDepth = texture( depthColor, vec3( coord.x, coord.y, 0 ) ).r;

	}

}`;class ad{constructor(){this.texture=null,this.mesh=null,this.depthNear=0,this.depthFar=0}init(n,t){if(this.texture===null){const i=new $i(n.texture);(n.depthNear!==t.depthNear||n.depthFar!==t.depthFar)&&(this.depthNear=n.depthNear,this.depthFar=n.depthFar),this.texture=i}}getMesh(n){if(this.texture!==null&&this.mesh===null){const t=n.cameras[0].viewport,i=new qt({vertexShader:id,fragmentShader:rd,uniforms:{depthColor:{value:this.texture},depthWidth:{value:t.z},depthHeight:{value:t.w}}});this.mesh=new Gt(new Wn(20,20),i)}return this.mesh}reset(){this.texture=null,this.mesh=null}getDepthTexture(){return this.texture}}class od extends Ta{constructor(n,t){super();const i=this;let c=null,s=1,u=null,m="local-floor",I=1,C=null,X=null,F=null,v=null,M=null,L=null;const B=typeof XRWebGLBinding<"u",f=new ad,l={},g=t.getContextAttributes();let U=null,S=null;const R=[],x=[],o=new ht;let p=null,q=null;const D=new Un;D.viewport=new mt;const N=new Un;N.viewport=new mt;const z=[D,N],Y=new Aa;let T=null,Z=null;this.cameraAutoUpdate=!0,this.enabled=!1,this.isPresenting=!1,this.getController=function(W){let he=R[W];return he===void 0&&(he=new wn,R[W]=he),he.getTargetRaySpace()},this.getControllerGrip=function(W){let he=R[W];return he===void 0&&(he=new wn,R[W]=he),he.getGripSpace()},this.getHand=function(W){let he=R[W];return he===void 0&&(he=new wn,R[W]=he),he.getHandSpace()};function $(W){const he=x.indexOf(W.inputSource);if(he===-1)return;const Se=R[he];Se!==void 0&&(Se.update(W.inputSource,W.frame,C||u),Se.dispatchEvent({type:W.type,data:W.inputSource}))}function y(){c.removeEventListener("select",$),c.removeEventListener("selectstart",$),c.removeEventListener("selectend",$),c.removeEventListener("squeeze",$),c.removeEventListener("squeezestart",$),c.removeEventListener("squeezeend",$),c.removeEventListener("end",y),c.removeEventListener("inputsourceschange",fe);for(let W=0;W<R.length;W++){const he=x[W];he!==null&&(x[W]=null,R[W].disconnect(he))}T=null,Z=null,f.reset();for(const W in l)delete l[W];if(n.setRenderTarget(U),M=null,v=null,F=null,c=null,S=null,Ne.stop(),i.isPresenting=!1,n.setPixelRatio(p),n.setSize(o.width,o.height,!1),q!==null){const W=q.camera;W.fov=q.fov,W.zoom=q.zoom,W.updateProjectionMatrix(),q=null}i.dispatchEvent({type:"sessionend"})}this.setFramebufferScaleFactor=function(W){s=W,i.isPresenting===!0&&He("WebXRManager: Cannot change framebuffer scale while presenting.")},this.setReferenceSpaceType=function(W){m=W,i.isPresenting===!0&&He("WebXRManager: Cannot change reference space type while presenting.")},this.getReferenceSpace=function(){return C||u},this.setReferenceSpace=function(W){C=W},this.getBaseLayer=function(){return v!==null?v:M},this.getBinding=function(){return F===null&&B&&(F=new XRWebGLBinding(c,t)),F},this.getFrame=function(){return L},this.getSession=function(){return c},this.setSession=async function(W){if(c=W,c!==null){if(U=n.getRenderTarget(),c.addEventListener("select",$),c.addEventListener("selectstart",$),c.addEventListener("selectend",$),c.addEventListener("squeeze",$),c.addEventListener("squeezestart",$),c.addEventListener("squeezeend",$),c.addEventListener("end",y),c.addEventListener("inputsourceschange",fe),g.xrCompatible!==!0&&await t.makeXRCompatible(),p=n.getPixelRatio(),n.getSize(o),B&&"createProjectionLayer"in XRWebGLBinding.prototype){let Se=null,Re=null,me=null;g.depth&&(me=g.stencil?t.DEPTH24_STENCIL8:t.DEPTH_COMPONENT24,Se=g.stencil?Vt:rn,Re=g.stencil?$t:kt);const we={colorFormat:t.RGBA8,depthFormat:me,scaleFactor:s};F=this.getBinding(),v=F.createProjectionLayer(we),c.updateRenderState({layers:[v]}),n.setPixelRatio(1),n.setSize(v.textureWidth,v.textureHeight,!1),S=new bt(v.textureWidth,v.textureHeight,{format:Ft,type:Ct,depthTexture:new tn(v.textureWidth,v.textureHeight,Re,void 0,void 0,void 0,void 0,void 0,void 0,Se),stencilBuffer:g.stencil,colorSpace:n.outputColorSpace,samples:g.antialias?4:0,resolveDepthBuffer:v.ignoreDepthValues===!1,resolveStencilBuffer:v.ignoreDepthValues===!1,storeMultisampledDepthBuffer:v.ignoreDepthValues===!1,storeMultisampledStencilBuffer:v.ignoreDepthValues===!1})}else{const Se={antialias:g.antialias,alpha:!0,depth:g.depth,stencil:g.stencil,framebufferScaleFactor:s};M=new XRWebGLLayer(c,t,Se),c.updateRenderState({baseLayer:M}),n.setPixelRatio(1),n.setSize(M.framebufferWidth,M.framebufferHeight,!1),S=new bt(M.framebufferWidth,M.framebufferHeight,{format:Ft,type:Ct,colorSpace:n.outputColorSpace,stencilBuffer:g.stencil,resolveDepthBuffer:M.ignoreDepthValues===!1,resolveStencilBuffer:M.ignoreDepthValues===!1,storeMultisampledDepthBuffer:M.ignoreDepthValues===!1,storeMultisampledStencilBuffer:M.ignoreDepthValues===!1})}S.isXRRenderTarget=!0,this.setFoveation(I),C=null,u=await c.requestReferenceSpace(m),Ne.setContext(c),Ne.start(),i.isPresenting=!0,i.dispatchEvent({type:"sessionstart"})}},this.getEnvironmentBlendMode=function(){if(c!==null)return c.environmentBlendMode},this.getDepthTexture=function(){return f.getDepthTexture()};function fe(W){for(let he=0;he<W.removed.length;he++){const Se=W.removed[he],Re=x.indexOf(Se);Re>=0&&(x[Re]=null,R[Re].disconnect(Se))}for(let he=0;he<W.added.length;he++){const Se=W.added[he];let Re=x.indexOf(Se);if(Re===-1){for(let we=0;we<R.length;we++)if(we>=x.length){x.push(Se),Re=we;break}else if(x[we]===null){x[we]=Se,Re=we;break}if(Re===-1)break}const me=R[Re];me&&me.connect(Se)}}function K(W,he){he===null?W.matrixWorld.copy(W.matrix):W.matrixWorld.multiplyMatrices(he.matrixWorld,W.matrix),W.matrixWorldInverse.copy(W.matrixWorld).invert()}this.updateCamera=function(W){if(c===null)return;let he=W.near,Se=W.far;f.texture!==null&&(f.depthNear>0&&(he=f.depthNear),f.depthFar>0&&(Se=f.depthFar)),Y.near=N.near=D.near=he,Y.far=N.far=D.far=Se,(T!==Y.near||Z!==Y.far)&&(c.updateRenderState({depthNear:Y.near,depthFar:Y.far}),T=Y.near,Z=Y.far),Y.layers.mask=W.layers.mask|6,D.layers.mask=Y.layers.mask&-5,N.layers.mask=Y.layers.mask&-3;const Re=W.parent,me=Y.cameras;K(Y,Re);for(let we=0;we<me.length;we++)K(me[we],Re);Y.projectionMatrix.copy(D.projectionMatrix),Y.projectionMatrixInverse.copy(D.projectionMatrixInverse),q===null&&W.isPerspectiveCamera&&(q={camera:W,fov:W.fov,zoom:W.zoom}),j(W,Y,Re)};function j(W,he,Se){Se===null?W.matrix.copy(he.matrixWorld):(W.matrix.copy(Se.matrixWorld),W.matrix.invert(),W.matrix.multiply(he.matrixWorld)),W.matrix.decompose(W.position,W.quaternion,W.scale),W.updateMatrixWorld(!0),W.projectionMatrix.copy(he.projectionMatrix),W.projectionMatrixInverse.copy(he.projectionMatrixInverse),W.isPerspectiveCamera&&(W.fov=vo*2*Math.atan(1/W.projectionMatrix.elements[5]),W.zoom=1)}this.getCamera=function(){return Y},this.getFoveation=function(){if(!(v===null&&M===null))return I},this.setFoveation=function(W){I=W,v!==null&&(v.fixedFoveation=W),M!==null&&M.fixedFoveation!==void 0&&(M.fixedFoveation=W)},this.hasDepthSensing=function(){return f.texture!==null},this.getDepthSensingMesh=function(){return f.getMesh(Y)},this.getCameraTexture=function(W){return l[W]};let ie=null;function Ae(W,he){if(X=he.getViewerPose(C||u),L=he,X!==null){const Se=X.views;M!==null&&(n.setRenderTargetFramebuffer(S,M.framebuffer),n.setRenderTarget(S));let Re=!1;Se.length!==Y.cameras.length&&(Y.cameras.length=0,Re=!0);for(let Ce=0;Ce<Se.length;Ce++){const qe=Se[Ce];let ke=null;if(M!==null)ke=M.getViewport(qe);else{const ct=F.getViewSubImage(v,qe);ke=ct.viewport,Ce===0&&(n.setRenderTargetTextures(S,ct.colorTexture,ct.depthStencilTexture),n.setRenderTarget(S))}let Ie=z[Ce];Ie===void 0&&(Ie=new Un,Ie.layers.enable(Ce),Ie.viewport=new mt,z[Ce]=Ie),Ie.matrix.fromArray(qe.transform.matrix),Ie.matrix.decompose(Ie.position,Ie.quaternion,Ie.scale),Ie.projectionMatrix.fromArray(qe.projectionMatrix),Ie.projectionMatrixInverse.copy(Ie.projectionMatrix).invert(),Ie.viewport.set(ke.x,ke.y,ke.width,ke.height),Ce===0&&(Y.matrix.copy(Ie.matrix),Y.matrix.decompose(Y.position,Y.quaternion,Y.scale)),Re===!0&&Y.cameras.push(Ie)}const me=c.enabledFeatures;if(me&&me.includes("depth-sensing")&&c.depthUsage=="gpu-optimized"&&B){F=i.getBinding();const Ce=F.getDepthInformation(Se[0]);Ce&&Ce.isValid&&Ce.texture&&f.init(Ce,c.renderState)}if(me&&me.includes("camera-access")&&B){n.state.unbindTexture(),F=i.getBinding();for(let Ce=0;Ce<Se.length;Ce++){const qe=Se[Ce].camera;if(qe){let ke=l[qe];ke||(ke=new $i,l[qe]=ke);const Ie=F.getCameraImage(qe);ke.sourceTexture=Ie}}}}for(let Se=0;Se<R.length;Se++){const Re=x[Se],me=R[Se];Re!==null&&me!==void 0&&me.update(Re,he,C||u)}ie&&ie(W,he),he.detectedPlanes&&i.dispatchEvent({type:"planesdetected",data:he}),L=null}const Ne=new Da;Ne.setAnimationLoop(Ae),this.setAnimationLoop=function(W){ie=W},this.dispose=function(){}}}const sd=new Yt,lo=new Oe;lo.set(-1,0,0,0,1,0,0,0,1);function ld(e,n){function t(f,l){f.matrixAutoUpdate===!0&&f.updateMatrix(),l.value.copy(f.matrix)}function i(f,l){l.color.getRGB(f.fogColor.value,pr(e)),l.isFog?(f.fogNear.value=l.near,f.fogFar.value=l.far):l.isFogExp2&&(f.fogDensity.value=l.density)}function c(f,l,g,U,S){l.isNodeMaterial?l.uniformsNeedUpdate=!1:l.isMeshBasicMaterial?s(f,l):l.isMeshLambertMaterial?(s(f,l),l.envMap&&(f.envMapIntensity.value=l.envMapIntensity)):l.isMeshToonMaterial?(s(f,l),F(f,l)):l.isMeshPhongMaterial?(s(f,l),X(f,l),l.envMap&&(f.envMapIntensity.value=l.envMapIntensity)):l.isMeshStandardMaterial?(s(f,l),v(f,l),l.isMeshPhysicalMaterial&&M(f,l,S)):l.isMeshMatcapMaterial?(s(f,l),L(f,l)):l.isMeshDepthMaterial?s(f,l):l.isMeshDistanceMaterial?(s(f,l),B(f,l)):l.isMeshNormalMaterial?s(f,l):l.isLineBasicMaterial?(u(f,l),l.isLineDashedMaterial&&m(f,l)):l.isPointsMaterial?I(f,l,g,U):l.isSpriteMaterial?C(f,l):l.isShadowMaterial?(f.color.value.copy(l.color),f.opacity.value=l.opacity):l.isShaderMaterial&&(l.uniformsNeedUpdate=!1)}function s(f,l){f.opacity.value=l.opacity,l.color&&f.diffuse.value.copy(l.color),l.emissive&&f.emissive.value.copy(l.emissive).multiplyScalar(l.emissiveIntensity),l.map&&(f.map.value=l.map,t(l.map,f.mapTransform)),l.alphaMap&&(f.alphaMap.value=l.alphaMap,t(l.alphaMap,f.alphaMapTransform)),l.bumpMap&&(f.bumpMap.value=l.bumpMap,t(l.bumpMap,f.bumpMapTransform),f.bumpScale.value=l.bumpScale,l.side===St&&(f.bumpScale.value*=-1)),l.normalMap&&(f.normalMap.value=l.normalMap,t(l.normalMap,f.normalMapTransform),f.normalScale.value.copy(l.normalScale),l.side===St&&f.normalScale.value.negate()),l.displacementMap&&(f.displacementMap.value=l.displacementMap,t(l.displacementMap,f.displacementMapTransform),f.displacementScale.value=l.displacementScale,f.displacementBias.value=l.displacementBias),l.emissiveMap&&(f.emissiveMap.value=l.emissiveMap,t(l.emissiveMap,f.emissiveMapTransform)),l.specularMap&&(f.specularMap.value=l.specularMap,t(l.specularMap,f.specularMapTransform)),l.alphaTest>0&&(f.alphaTest.value=l.alphaTest);const g=n.get(l),U=g.envMap,S=g.envMapRotation;U&&(f.envMap.value=U,f.envMapRotation.value.setFromMatrix4(sd.makeRotationFromEuler(S)).transpose(),U.isCubeTexture&&U.isRenderTargetTexture===!1&&f.envMapRotation.value.premultiply(lo),f.reflectivity.value=l.reflectivity,f.ior.value=l.ior,f.refractionRatio.value=l.refractionRatio),l.lightMap&&(f.lightMap.value=l.lightMap,f.lightMapIntensity.value=l.lightMapIntensity,t(l.lightMap,f.lightMapTransform)),l.aoMap&&(f.aoMap.value=l.aoMap,f.aoMapIntensity.value=l.aoMapIntensity,t(l.aoMap,f.aoMapTransform))}function u(f,l){f.diffuse.value.copy(l.color),f.opacity.value=l.opacity,l.map&&(f.map.value=l.map,t(l.map,f.mapTransform))}function m(f,l){f.dashSize.value=l.dashSize,f.totalSize.value=l.dashSize+l.gapSize,f.scale.value=l.scale}function I(f,l,g,U){f.diffuse.value.copy(l.color),f.opacity.value=l.opacity,f.size.value=l.size*g,f.scale.value=U*.5,l.map&&(f.map.value=l.map,t(l.map,f.uvTransform)),l.alphaMap&&(f.alphaMap.value=l.alphaMap,t(l.alphaMap,f.alphaMapTransform)),l.alphaTest>0&&(f.alphaTest.value=l.alphaTest)}function C(f,l){f.diffuse.value.copy(l.color),f.opacity.value=l.opacity,f.rotation.value=l.rotation,l.map&&(f.map.value=l.map,t(l.map,f.mapTransform)),l.alphaMap&&(f.alphaMap.value=l.alphaMap,t(l.alphaMap,f.alphaMapTransform)),l.alphaTest>0&&(f.alphaTest.value=l.alphaTest)}function X(f,l){f.specular.value.copy(l.specular),f.shininess.value=Math.max(l.shininess,1e-4)}function F(f,l){l.gradientMap&&(f.gradientMap.value=l.gradientMap)}function v(f,l){f.metalness.value=l.metalness,l.metalnessMap&&(f.metalnessMap.value=l.metalnessMap,t(l.metalnessMap,f.metalnessMapTransform)),f.roughness.value=l.roughness,l.roughnessMap&&(f.roughnessMap.value=l.roughnessMap,t(l.roughnessMap,f.roughnessMapTransform)),l.envMap&&(f.envMapIntensity.value=l.envMapIntensity)}function M(f,l,g){f.ior.value=l.ior,l.diffuseRoughness>0&&(f.diffuseRoughness.value=l.diffuseRoughness,l.diffuseRoughnessMap&&(f.diffuseRoughnessMap.value=l.diffuseRoughnessMap,t(l.diffuseRoughnessMap,f.diffuseRoughnessMapTransform))),l.sheen>0&&(f.sheenColor.value.copy(l.sheenColor).multiplyScalar(l.sheen),f.sheenRoughness.value=l.sheenRoughness,l.sheenColorMap&&(f.sheenColorMap.value=l.sheenColorMap,t(l.sheenColorMap,f.sheenColorMapTransform)),l.sheenRoughnessMap&&(f.sheenRoughnessMap.value=l.sheenRoughnessMap,t(l.sheenRoughnessMap,f.sheenRoughnessMapTransform))),l.clearcoat>0&&(f.clearcoat.value=l.clearcoat,f.clearcoatRoughness.value=l.clearcoatRoughness,l.clearcoatMap&&(f.clearcoatMap.value=l.clearcoatMap,t(l.clearcoatMap,f.clearcoatMapTransform)),l.clearcoatRoughnessMap&&(f.clearcoatRoughnessMap.value=l.clearcoatRoughnessMap,t(l.clearcoatRoughnessMap,f.clearcoatRoughnessMapTransform)),l.clearcoatNormalMap&&(f.clearcoatNormalMap.value=l.clearcoatNormalMap,t(l.clearcoatNormalMap,f.clearcoatNormalMapTransform),f.clearcoatNormalScale.value.copy(l.clearcoatNormalScale),l.side===St&&f.clearcoatNormalScale.value.negate())),l.dispersion>0&&(f.dispersion.value=l.dispersion),l.retroreflectivity>0&&(f.retroreflectivity.value=l.retroreflectivity),l.iridescence>0&&(f.iridescence.value=l.iridescence,f.iridescenceIOR.value=l.iridescenceIOR,f.iridescenceThicknessMinimum.value=l.iridescenceThicknessMinimum,f.iridescenceThicknessMaximum.value=l.iridescenceThicknessMaximum,l.iridescenceMap&&(f.iridescenceMap.value=l.iridescenceMap,t(l.iridescenceMap,f.iridescenceMapTransform)),l.iridescenceThicknessMap&&(f.iridescenceThicknessMap.value=l.iridescenceThicknessMap,t(l.iridescenceThicknessMap,f.iridescenceThicknessMapTransform))),l.transmission>0&&(f.transmission.value=l.transmission,f.transmissionSamplerMap.value=g.texture,f.transmissionSamplerSize.value.set(g.width,g.height),l.transmissionMap&&(f.transmissionMap.value=l.transmissionMap,t(l.transmissionMap,f.transmissionMapTransform)),f.thickness.value=l.thickness,l.thicknessMap&&(f.thicknessMap.value=l.thicknessMap,t(l.thicknessMap,f.thicknessMapTransform)),f.attenuationDistance.value=l.attenuationDistance,f.attenuationColor.value.copy(l.attenuationColor)),l.anisotropy>0&&(f.anisotropyVector.value.set(l.anisotropy*Math.cos(l.anisotropyRotation),l.anisotropy*Math.sin(l.anisotropyRotation)),l.anisotropyMap&&(f.anisotropyMap.value=l.anisotropyMap,t(l.anisotropyMap,f.anisotropyMapTransform))),f.specularIntensity.value=l.specularIntensity,f.specularColor.value.copy(l.specularColor),l.specularColorMap&&(f.specularColorMap.value=l.specularColorMap,t(l.specularColorMap,f.specularColorMapTransform)),l.specularIntensityMap&&(f.specularIntensityMap.value=l.specularIntensityMap,t(l.specularIntensityMap,f.specularIntensityMapTransform))}function L(f,l){l.matcap&&(f.matcap.value=l.matcap)}function B(f,l){const g=n.get(l).light;f.referencePosition.value.setFromMatrixPosition(g.matrixWorld),f.nearDistance.value=g.shadow.camera.near,f.farDistance.value=g.shadow.camera.far}return{refreshFogUniforms:i,refreshMaterialUniforms:c}}function cd(e,n,t,i){let c={},s={};function u(g,U){const S=U.program;i.uniformBlockBinding(g,S,c[g.id])}function m(g,U){let S=c[g.id];S===void 0&&(L(g),S=I(g),c[g.id]=S,g.addEventListener("dispose",f));const R=U.program;i.updateUBOMapping(g,R);const x=n.render.frame;s[g.id]!==x&&(C(g),s[g.id]=x)}function I(g){const U=e.createBuffer(),S=g.__size,R=g.usage;return e.bindBuffer(e.UNIFORM_BUFFER,U),e.bufferData(e.UNIFORM_BUFFER,S,R),e.bindBuffer(e.UNIFORM_BUFFER,null),U}function C(g){const U=c[g.id],S=g.uniforms,R=g.__cache;e.bindBuffer(e.UNIFORM_BUFFER,U);for(let x=0,o=S.length;x<o;x++){const p=S[x];if(Array.isArray(p))for(let q=0,D=p.length;q<D;q++)X(p[q],x,q,R);else X(p,x,0,R)}e.bindBuffer(e.UNIFORM_BUFFER,null)}function X(g,U,S,R){if(v(g,U,S,R)===!0){const x=g.__offset,o=g.value;if(Array.isArray(o)){let p=0;for(let q=0;q<o.length;q++){const D=o[q],N=B(D,!0);F(D,g.__data,p),p+=N.storage/Float32Array.BYTES_PER_ELEMENT}}else F(o,g.__data,0);e.bufferSubData(e.UNIFORM_BUFFER,x,g.__data)}}function F(g,U,S){typeof g=="number"||typeof g=="boolean"?U[S]=g:g.isMatrix3?(U[S]=g.elements[0],U[S+1]=g.elements[1],U[S+2]=g.elements[2],U[S+3]=0,U[S+4]=g.elements[3],U[S+5]=g.elements[4],U[S+6]=g.elements[5],U[S+7]=0,U[S+8]=g.elements[6],U[S+9]=g.elements[7],U[S+10]=g.elements[8],U[S+11]=0):ArrayBuffer.isView(g)?U.set(g.subarray(0,U.length-S),S):g.toArray(U,S)}function v(g,U,S,R){const x=g.value,o=U+"_"+S;if(Array.isArray(x)){R[o]===void 0&&(R[o]=[]);const p=R[o];let q=!1;for(let D=0;D<x.length;D++)M(x[D],p,D)===!0&&(q=!0);return q}return M(x,R,o)}function M(g,U,S){if(U[S]===void 0)return typeof g=="number"||typeof g=="boolean"?U[S]=g:ArrayBuffer.isView(g)?U[S]=g.slice():U[S]=g.clone(),!0;{const R=U[S];if(typeof g=="number"||typeof g=="boolean"){if(R!==g)return U[S]=g,!0}else{if(ArrayBuffer.isView(g))return!0;if(R.equals(g)===!1)return R.copy(g),!0}}return!1}function L(g){const U=g.uniforms;let S=0;const R=16;for(let o=0,p=U.length;o<p;o++){const q=Array.isArray(U[o])?U[o]:[U[o]];for(let D=0,N=q.length;D<N;D++){const z=q[D],Y=Array.isArray(z.value)?z.value:[z.value],T=Array.isArray(U[o])||Array.isArray(z.value);let Z=S;for(let y=0,fe=Y.length;y<fe;y++){const K=Y[y],j=B(K,T),ie=S%R,Ae=ie%j.boundary,Ne=ie+Ae;S+=Ae,Ne!==0&&R-Ne<j.storage&&(S+=R-Ne),y===0&&(Z=S),S+=j.storage}const $=z.type==="int"?Int32Array:z.type==="uint"?Uint32Array:Float32Array;z.__data=new $((S-Z)/Float32Array.BYTES_PER_ELEMENT),z.__offset=Z}}const x=S%R;return x>0&&(S+=R-x),g.__size=S,g.__cache={},this}function B(g,U){const S={boundary:0,storage:0};return typeof g=="number"||typeof g=="boolean"?(S.boundary=4,S.storage=4):g.isVector2?(S.boundary=8,S.storage=8):g.isVector3||g.isColor?(S.boundary=16,S.storage=12):g.isVector4?(S.boundary=16,S.storage=16):g.isMatrix3?(S.boundary=16,S.storage=48):g.isMatrix4?(S.boundary=16,S.storage=64):g.isTexture?He("WebGLRenderer: Texture samplers can not be part of an uniforms group."):ArrayBuffer.isView(g)?(S.boundary=16,S.storage=g.byteLength):He("WebGLRenderer: Unsupported uniform value type.",g),U===!0&&(S.boundary=16,S.storage=Math.ceil(S.storage/16)*16),S}function f(g){const U=g.target;U.removeEventListener("dispose",f),e.deleteBuffer(c[U.id]),delete c[U.id],delete s[U.id]}function l(){for(const g in c)e.deleteBuffer(c[g]);c={},s={}}return{bind:u,update:m,dispose:l}}const fd=new Uint16Array([12469,15057,12620,14925,13266,14620,13807,14376,14323,13990,14545,13625,14713,13328,14840,12882,14931,12528,14996,12233,15039,11829,15066,11525,15080,11295,15085,10976,15082,10705,15073,10495,13880,14564,13898,14542,13977,14430,14158,14124,14393,13732,14556,13410,14702,12996,14814,12596,14891,12291,14937,11834,14957,11489,14958,11194,14943,10803,14921,10506,14893,10278,14858,9960,14484,14039,14487,14025,14499,13941,14524,13740,14574,13468,14654,13106,14743,12678,14818,12344,14867,11893,14889,11509,14893,11180,14881,10751,14852,10428,14812,10128,14765,9754,14712,9466,14764,13480,14764,13475,14766,13440,14766,13347,14769,13070,14786,12713,14816,12387,14844,11957,14860,11549,14868,11215,14855,10751,14825,10403,14782,10044,14729,9651,14666,9352,14599,9029,14967,12835,14966,12831,14963,12804,14954,12723,14936,12564,14917,12347,14900,11958,14886,11569,14878,11247,14859,10765,14828,10401,14784,10011,14727,9600,14660,9289,14586,8893,14508,8533,15111,12234,15110,12234,15104,12216,15092,12156,15067,12010,15028,11776,14981,11500,14942,11205,14902,10752,14861,10393,14812,9991,14752,9570,14682,9252,14603,8808,14519,8445,14431,8145,15209,11449,15208,11451,15202,11451,15190,11438,15163,11384,15117,11274,15055,10979,14994,10648,14932,10343,14871,9936,14803,9532,14729,9218,14645,8742,14556,8381,14461,8020,14365,7603,15273,10603,15272,10607,15267,10619,15256,10631,15231,10614,15182,10535,15118,10389,15042,10167,14963,9787,14883,9447,14800,9115,14710,8665,14615,8318,14514,7911,14411,7507,14279,7198,15314,9675,15313,9683,15309,9712,15298,9759,15277,9797,15229,9773,15166,9668,15084,9487,14995,9274,14898,8910,14800,8539,14697,8234,14590,7790,14479,7409,14367,7067,14178,6621,15337,8619,15337,8631,15333,8677,15325,8769,15305,8871,15264,8940,15202,8909,15119,8775,15022,8565,14916,8328,14804,8009,14688,7614,14569,7287,14448,6888,14321,6483,14088,6171,15350,7402,15350,7419,15347,7480,15340,7613,15322,7804,15287,7973,15229,8057,15148,8012,15046,7846,14933,7611,14810,7357,14682,7069,14552,6656,14421,6316,14251,5948,14007,5528,15356,5942,15356,5977,15353,6119,15348,6294,15332,6551,15302,6824,15249,7044,15171,7122,15070,7050,14949,6861,14818,6611,14679,6349,14538,6067,14398,5651,14189,5311,13935,4958,15359,4123,15359,4153,15356,4296,15353,4646,15338,5160,15311,5508,15263,5829,15188,6042,15088,6094,14966,6001,14826,5796,14678,5543,14527,5287,14377,4985,14133,4586,13869,4257,15360,1563,15360,1642,15358,2076,15354,2636,15341,3350,15317,4019,15273,4429,15203,4732,15105,4911,14981,4932,14836,4818,14679,4621,14517,4386,14359,4156,14083,3795,13808,3437,15360,122,15360,137,15358,285,15355,636,15344,1274,15322,2177,15281,2765,15215,3223,15120,3451,14995,3569,14846,3567,14681,3466,14511,3305,14344,3121,14037,2800,13753,2467,15360,0,15360,1,15359,21,15355,89,15346,253,15325,479,15287,796,15225,1148,15133,1492,15008,1749,14856,1882,14685,1886,14506,1783,14324,1608,13996,1398,13702,1183]);let Nt=null;function dd(){return Nt===null&&(Nt=new Ra(fd,16,16,Qt,Dt),Nt.name="DFG_LUT",Nt.minFilter=At,Nt.magFilter=At,Nt.wrapS=Rn,Nt.wrapT=Rn,Nt.generateMipmaps=!1,Nt.needsUpdate=!0),Nt}class ud{constructor(n={}){const{canvas:t=Ca(),context:i=null,depth:c=!0,stencil:s=!1,alpha:u=!1,antialias:m=!1,premultipliedAlpha:I=!0,preserveDrawingBuffer:C=!1,powerPreference:X="default",failIfMajorPerformanceCaveat:F=!1,reversedDepthBuffer:v=!1,outputBufferType:M=Ct}=n;this.isWebGLRenderer=!0;let L;if(i!==null){if(typeof WebGLRenderingContext<"u"&&i instanceof WebGLRenderingContext)throw new Error("THREE.WebGLRenderer: WebGL 1 is not supported since r163.");L=i.getContextAttributes().alpha}else L=u;const B=M,f=new Set([Si,vi,gi]),l=new Set([Ct,kt,un,$t,mi,_i]),g=new Uint32Array(4),U=new Int32Array(4),S={textures:[],min:[],max:[],resolution:[]};let R=null,x=null;const o=[],p=[];let q=null;this.domElement=t,this.debug={checkShaderErrors:!0,diagnostics:{keywords:!1},onShaderError:null},this.autoClear=!0,this.autoClearColor=!0,this.autoClearDepth=!0,this.autoClearStencil=!0,this.sortObjects=!0,this.clippingPlanes=[],this.localClippingEnabled=!1,this.toneMapping=Ut,this.toneMappingExposure=1,this.transmissionResolutionScale=1;const D=this;let N=!1,z=null,Y=null,T=null,Z=null;this._outputColorSpace=ba;let $=0,y=0,fe=null,K=-1,j=null;const ie=new mt,Ae=new mt;let Ne=null;const W=new Ye(0);let he=0,Se=t.width,Re=t.height,me=1,we=null,_t=null;const Ce=new mt(0,0,Se,Re),qe=new mt(0,0,Se,Re);let ke=!1;const Ie=new ci,ct=new La;let nt=!1,gt=!1;const je=new Yt,Lt=new We,ot=new mt,Et={background:null,fog:null,environment:null,overrideMaterial:null,isScene:!0};let Mt=!1;function xt(){return fe===null?me:1}let E=i;function Tt(a,_){return t.getContext(a,_)}let $e,Qe,ce,it,Te,be,d,r,b,Q,k,te,se,G,H,re,h,V,O,ae,ee,ne,Me;try{const a={alpha:!0,depth:c,stencil:s,antialias:m,premultipliedAlpha:I,preserveDrawingBuffer:C,powerPreference:X,failIfMajorPerformanceCaveat:F};if("setAttribute"in t&&t.setAttribute("data-engine",`three.js r${Pa}`),t.addEventListener("webglcontextlost",xe,!1),t.addEventListener("webglcontextrestored",Le,!1),t.addEventListener("webglcontextcreationerror",ue,!1),E===null){const _="webgl2";if(E=Tt(_,a),E===null)throw Tt(_)?new Error("THREE.WebGLRenderer: Error creating WebGL context with your selected attributes."):new Error("THREE.WebGLRenderer: Error creating WebGL context.")}Ue()}catch(a){throw t.removeEventListener("webglcontextlost",xe,!1),t.removeEventListener("webglcontextrestored",Le,!1),t.removeEventListener("webglcontextcreationerror",ue,!1),Ze("WebGLRenderer: "+a.message),a}function Ue(){$e=new mc(E),$e.init(),ee=new so(E,$e),Qe=new rc(E,$e,n,ee),ce=new td(E,$e),Qe.reversedDepthBuffer&&v&&ce.buffers.depth.setReversed(!0),Y=E.createFramebuffer(),T=E.createFramebuffer(),Z=E.createFramebuffer(),it=new vc(E),Te=new Vf,be=new nd(E,$e,ce,Te,Qe,ee,it),d=new hc(D),r=new Eo(E),ne=new nc(E,r),b=new _c(E,r,it,ne),Q=new Ec(E,b,r,ne,it),V=new Sc(E,Qe,be),H=new ac(Te),k=new Hf(D,d,$e,Qe,ne,H),te=new ld(D,Te),se=new kf,G=new Zf($e),h=new tc(D,d,ce,Q,L,I),re=new ed(D,Q,Qe),Me=new cd(E,it,Qe,ce),O=new ic(E,$e,it),ae=new gc(E,$e,it),it.programs=k.programs,D.capabilities=Qe,D.extensions=$e,D.properties=Te,D.renderLists=se,D.shadowMap=re,D.state=ce,D.info=it}B!==Ct&&(q=new xc(B,t.width,t.height,m,c,s));const J=new od(D,E);this.xr=J,this.getContext=function(){return E},this.getContextAttributes=function(){return E.getContextAttributes()},this.forceContextLoss=function(){const a=$e.get("WEBGL_lose_context");a&&a.loseContext()},this.forceContextRestore=function(){const a=$e.get("WEBGL_lose_context");a&&a.restoreContext()},this.getPixelRatio=function(){return me},this.setPixelRatio=function(a){a!==void 0&&(me=a,this.setSize(Se,Re,!1))},this.getSize=function(a){return a.set(Se,Re)},this.setSize=function(a,_,w=!0){if(J.isPresenting){He("WebGLRenderer: Can't change size while VR device is presenting.");return}Se=a,Re=_,t.width=Math.floor(a*me),t.height=Math.floor(_*me),w===!0&&(t.style.width=a+"px",t.style.height=_+"px"),q!==null&&q.setSize(t.width,t.height),this.setViewport(0,0,a,_)},this.getDrawingBufferSize=function(a){return a.set(Se*me,Re*me).floor()},this.setDrawingBufferSize=function(a,_,w){Se=a,Re=_,me=w,t.width=Math.floor(a*w),t.height=Math.floor(_*w),this.setViewport(0,0,a,_)},this.setEffects=function(a){if(B===Ct){Ze("WebGLRenderer: setEffects() requires outputBufferType set to HalfFloatType or FloatType.");return}if(a){for(let _=0;_<a.length;_++)if(a[_].isOutputPass===!0){He("WebGLRenderer: OutputPass is not needed in setEffects(). Tone mapping and color space conversion are applied automatically.");break}}q.setEffects(a||[])},this.getCurrentViewport=function(a){return a.copy(ie)},this.getViewport=function(a){return a.copy(Ce)},this.setViewport=function(a,_,w,A){a.isVector4?Ce.set(a.x,a.y,a.z,a.w):Ce.set(a,_,w,A),ce.viewport(ie.copy(Ce).multiplyScalar(xt()).round())},this.getScissor=function(a){return a.copy(qe)},this.setScissor=function(a,_,w,A){a.isVector4?qe.set(a.x,a.y,a.z,a.w):qe.set(a,_,w,A),ce.scissor(Ae.copy(qe).multiplyScalar(xt()).round())},this.getScissorTest=function(){return ke},this.setScissorTest=function(a){ce.setScissorTest(ke=a)},this.setOpaqueSort=function(a){we=a},this.setTransparentSort=function(a){_t=a},this.getClearColor=function(a){return a.copy(h.getClearColor())},this.setClearColor=function(){h.setClearColor(...arguments)},this.getClearAlpha=function(){return h.getClearAlpha()},this.setClearAlpha=function(){h.setClearAlpha(...arguments)},this.clear=function(a=!0,_=!0,w=!0){let A=0;if(a){let P=!1;if(fe!==null){const le=fe.texture.format;P=f.has(le)}if(P){const le=fe.texture.type,_e=l.has(le),oe=h.getClearColor(),pe=h.getClearAlpha(),Ee=oe.r,Pe=oe.g,ye=oe.b;_e?(g[0]=Ee,g[1]=Pe,g[2]=ye,g[3]=pe,E.clearBufferuiv(E.COLOR,0,g)):(U[0]=Ee,U[1]=Pe,U[2]=ye,U[3]=pe,E.clearBufferiv(E.COLOR,0,U))}else A|=E.COLOR_BUFFER_BIT}_&&(A|=E.DEPTH_BUFFER_BIT,this.state.buffers.depth.setMask(!0)),w&&(A|=E.STENCIL_BUFFER_BIT,this.state.buffers.stencil.setMask(4294967295)),A!==0&&E.clear(A)},this.clearColor=function(){this.clear(!0,!1,!1)},this.clearDepth=function(){this.clear(!1,!0,!1)},this.clearStencil=function(){this.clear(!1,!1,!0)},this.setNodesHandler=function(a){a.setRenderer(this),z=a},this.dispose=function(){t.removeEventListener("webglcontextlost",xe,!1),t.removeEventListener("webglcontextrestored",Le,!1),t.removeEventListener("webglcontextcreationerror",ue,!1),h.dispose(),se.dispose(),G.dispose(),be.dispose(),Te.dispose(),d.dispose(),Q.dispose(),b.dispose(),ne.dispose(),Me.dispose(),k.dispose(),J.dispose(),J.removeEventListener("sessionstart",ir),J.removeEventListener("sessionend",rr),zt.stop()};function xe(a){a.preventDefault(),Qi("WebGLRenderer: Context Lost."),N=!0}function Le(){Qi("WebGLRenderer: Context Restored."),N=!1;const a=it.autoReset,_=re.enabled,w=re.autoUpdate,A=re.needsUpdate,P=re.type;Ue(),it.autoReset=a,re.enabled=_,re.autoUpdate=w,re.needsUpdate=A,re.type=P}function ue(a){Ze("WebGLRenderer: A WebGL context could not be created. Reason: ",a.statusMessage)}function Ve(a){const _=a.target;_.removeEventListener("dispose",Ve),st(_)}function st(a){Ot(a),Te.remove(a)}function Ot(a){const _=Te.get(a).programs;_!==void 0&&(_.forEach(function(w){k.releaseProgram(w)}),a.isShaderMaterial&&k.releaseShaderCache(a))}this.renderBufferDirect=function(a,_,w,A,P,le){_===null&&(_=Et);const _e=P.isMesh&&P.matrixWorld.determinantAffine()<0,oe=uo(a,_,w,A,P);ce.setMaterial(A,_e);let pe=w.index,Ee=1;if(A.wireframe===!0){if(pe=b.getWireframeAttribute(w),pe===void 0)return;Ee=2}const Pe=w.drawRange,ye=w.attributes.position;let ge=Pe.start*Ee,Fe=(Pe.start+Pe.count)*Ee;le!==null&&(ge=Math.max(ge,le.start*Ee),Fe=Math.min(Fe,(le.start+le.count)*Ee)),pe!==null?(ge=Math.max(ge,0),Fe=Math.min(Fe,pe.count)):ye!=null&&(ge=Math.max(ge,0),Fe=Math.min(Fe,ye.count));const tt=Fe-ge;if(tt<0||tt===1/0)return;ne.setup(P,A,oe,w,pe);let Ke,ze=O;if(pe!==null&&(Ke=r.get(pe),ze=ae,ze.setIndex(Ke)),P.isMesh)A.wireframe===!0?(ce.setLineWidth(A.wireframeLinewidth*xt()),ze.setMode(E.LINES)):ze.setMode(E.TRIANGLES);else if(P.isLine){let dt=A.linewidth;dt===void 0&&(dt=1),ce.setLineWidth(dt*xt()),P.isLineSegments?ze.setMode(E.LINES):P.isLineLoop?ze.setMode(E.LINE_LOOP):ze.setMode(E.LINE_STRIP)}else P.isPoints?ze.setMode(E.POINTS):P.isSprite&&ze.setMode(E.TRIANGLES);if(P.isBatchedMesh)if($e.get("WEBGL_multi_draw"))ze.renderMultiDraw(P._multiDrawStarts,P._multiDrawCounts,P._multiDrawCount);else{const dt=P._multiDrawStarts,ve=P._multiDrawCounts,pt=P._multiDrawCount,Ge=pe?r.get(pe).bytesPerElement:1,Xt=Te.get(A).currentProgram.getUniforms();for(let ut=0;ut<pt;ut++)Xt.setValue(E,"_gl_DrawID",ut),ze.render(dt[ut]/Ge,ve[ut])}else if(P.isInstancedMesh)ze.renderInstances(ge,tt,P.count);else if(w.isInstancedBufferGeometry){const dt=w._maxInstanceCount!==void 0?w._maxInstanceCount:1/0,ve=Math.min(w.instanceCount,dt);ze.renderInstances(ge,tt,ve)}else ze.render(ge,tt)};function nr(a,_,w,A){z!==null&&a.isNodeMaterial&&z.setObject(A,a),nt===!0&&H.setState(a,w,!1),a.transparent===!0&&a.side===wt&&a.forceSinglePass===!1?(a.side=St,a.needsUpdate=!0,sn(a,_,A),a.side=jt,a.needsUpdate=!0,sn(a,_,A),a.side=wt):sn(a,_,A)}this.compile=function(a,_,w=null){w===null&&(w=a),z!==null&&z.renderStart(a,_,w),x=G.get(w),x.init(_),p.push(x),w.traverseVisible(function(P){P.isLight&&P.layers.test(_.layers)&&(x.pushLight(P),P.castShadow&&x.pushShadow(P))}),a!==w&&a.traverseVisible(function(P){P.isLight&&P.layers.test(_.layers)&&(x.pushLight(P),P.castShadow&&x.pushShadow(P))}),x.setupLights(),z!==null&&z.updateLights(x.state.lightsArray),gt=this.localClippingEnabled,nt=H.init(this.clippingPlanes,gt),nt===!0&&H.setGlobalState(this.clippingPlanes,_),z!==null&&re.render(x.state.shadowsArray,w,_);const A=new Set;return a.traverse(function(P){if(!(P.isMesh||P.isPoints||P.isLine||P.isSprite))return;const le=P.material;if(le)if(Array.isArray(le))for(let _e=0;_e<le.length;_e++){const oe=le[_e];nr(oe,w,_,P),A.add(oe)}else nr(le,w,_,P),A.add(le)}),x=p.pop(),z!==null&&z.renderEnd(),A},this.compileAsync=function(a,_,w=null){const A=this.compile(a,_,w);return new Promise(P=>{function le(){if(A.forEach(function(_e){const pe=Te.get(_e).currentProgram;(pe===void 0||pe.isReady())&&A.delete(_e)}),A.size===0){P(a);return}setTimeout(le,10)}$e.get("KHR_parallel_shader_compile")!==null?le():setTimeout(le,10)})};let Gn=null;function co(a){Gn&&Gn(a)}function ir(){zt.stop()}function rr(){zt.start()}const zt=new Da;zt.setAnimationLoop(co),typeof self<"u"&&zt.setContext(self),this.setAnimationLoop=function(a){Gn=a,J.setAnimationLoop(a),a===null?zt.stop():zt.start()},J.addEventListener("sessionstart",ir),J.addEventListener("sessionend",rr),this.render=function(a,_){if(_!==void 0&&_.isCamera!==!0){Ze("WebGLRenderer.render: camera is not an instance of THREE.Camera.");return}if(N===!0)return;z!==null&&z.renderStart(a,_);const w=J.enabled===!0&&J.isPresenting===!0,A=q!==null&&(fe===null||w)&&q.begin(D,fe);if(a.matrixWorldAutoUpdate===!0&&a.updateMatrixWorld(),_.parent===null&&_.matrixWorldAutoUpdate===!0&&_.updateMatrixWorld(),J.enabled===!0&&J.isPresenting===!0&&(q===null||q.isCompositing()===!1)&&(J.cameraAutoUpdate===!0&&J.updateCamera(_),_=J.getCamera()),a.isScene===!0&&a.onBeforeRender(D,a,_,fe),x=G.get(a,p.length),x.init(_),x.state.textureUnits=be.getTextureUnits(),p.push(x),je.multiplyMatrices(_.projectionMatrix,_.matrixWorldInverse),_.isArrayCamera?ct.setFromArrayCamera(_):Ie.setFromProjectionMatrix(je,Ji,_.reversedDepth),gt=this.localClippingEnabled,nt=H.init(this.clippingPlanes,gt),R=se.get(a,o.length),R.init(),o.push(R),this.info.render.frame++,J.enabled===!0&&J.isPresenting===!0){const _e=D.xr.getDepthSensingMesh();_e!==null&&Hn(_e,_,-1/0,D.sortObjects)}Hn(a,_,0,D.sortObjects),R.finish(),z!==null&&z.updateLights(x.state.lightsArray),D.sortObjects===!0&&R.sort(we,_t),Mt=J.enabled===!1||J.isPresenting===!1||J.hasDepthSensing()===!1,Mt&&h.addToRenderList(R,a),this.info.autoReset===!0&&this.info.reset(),nt===!0&&H.beginShadows();const P=x.state.shadowsArray;if(re.render(P,a,_),nt===!0&&H.endShadows(),(A&&q.hasRenderPass())===!1){const _e=R.opaque,oe=R.transmissive;if(x.setupLights(),_.isArrayCamera){const pe=_.cameras;if(oe.length>0)for(let Ee=0,Pe=pe.length;Ee<Pe;Ee++){const ye=pe[Ee];or(_e,oe,a,ye)}Mt&&h.render(a);for(let Ee=0,Pe=pe.length;Ee<Pe;Ee++){const ye=pe[Ee];ar(R,a,ye,ye.viewport)}}else oe.length>0&&or(_e,oe,a,_),Mt&&h.render(a),ar(R,a,_)}fe!==null&&y===0&&(be.updateMultisampleRenderTarget(fe),be.updateRenderTargetMipmap(fe)),A&&q.end(D),a.isScene===!0&&a.onAfterRender(D,a,_),ne.resetDefaultState(),K=-1,j=null,p.pop(),p.length>0?(x=p[p.length-1],be.setTextureUnits(x.state.textureUnits),nt===!0&&H.setGlobalState(D.clippingPlanes,x.state.camera)):x=null,o.pop(),o.length>0?R=o[o.length-1]:R=null,z!==null&&z.renderEnd()};function Hn(a,_,w,A){if(a.visible===!1)return;if(a.layers.test(_.layers)){if(a.isGroup)w=a.renderOrder;else if(a.isLOD)a.autoUpdate===!0&&a.update(_);else if(a.isLight)x.pushLight(a),a.castShadow&&x.pushShadow(a);else if(a.isSprite){const _e=_.isArrayCamera?ct:Ie;if(!a.frustumCulled||a.intersectsFrustum(_e)){A&&ot.setFromMatrixPosition(a.matrixWorld).applyMatrix4(je);const oe=Q.update(a),pe=a.material;pe.visible&&R.push(a,oe,pe,w,ot.z,null,_)}}else if(a.isMesh||a.isLine||a.isPoints){const _e=_.isArrayCamera?ct:Ie;if(!a.frustumCulled||a.intersectsFrustum(_e)){const oe=Q.update(a),pe=a.material;if(A&&(a.boundingSphere!==void 0?(a.boundingSphere===null&&a.computeBoundingSphere(),ot.copy(a.boundingSphere.center)):(oe.boundingSphere===null&&oe.computeBoundingSphere(),ot.copy(oe.boundingSphere.center)),ot.applyMatrix4(a.matrixWorld).applyMatrix4(je)),Array.isArray(pe)){const Ee=oe.groups;for(let Pe=0,ye=Ee.length;Pe<ye;Pe++){const ge=Ee[Pe],Fe=pe[ge.materialIndex];Fe&&Fe.visible&&R.push(a,oe,Fe,w,ot.z,ge,_)}}else pe.visible&&R.push(a,oe,pe,w,ot.z,null,_)}}}const le=a.children;for(let _e=0,oe=le.length;_e<oe;_e++)Hn(le[_e],_,w,A)}function ar(a,_,w,A){const{opaque:P,transmissive:le,transparent:_e}=a;x.setupLightsView(w),nt===!0&&H.setGlobalState(D.clippingPlanes,w),A&&ce.viewport(ie.copy(A)),P.length>0&&gn(P,_,w),le.length>0&&gn(le,_,w),_e.length>0&&gn(_e,_,w),ce.buffers.depth.setTest(!0),ce.buffers.depth.setMask(!0),ce.buffers.color.setMask(!0),ce.setPolygonOffset(!1)}function or(a,_,w,A){if((w.isScene===!0?w.overrideMaterial:null)!==null)return;if(x.state.transmissionRenderTarget[A.id]===void 0){const ge=$e.has("EXT_color_buffer_half_float")||$e.has("EXT_color_buffer_float");x.state.transmissionRenderTarget[A.id]=new bt(1,1,{generateMipmaps:!0,type:ge?Dt:Ct,minFilter:Ht,samples:Math.max(4,Qe.samples),stencilBuffer:s,resolveDepthBuffer:!1,resolveStencilBuffer:!1,storeMultisampledDepthBuffer:!1,storeMultisampledStencilBuffer:!1,colorSpace:Je.workingColorSpace})}const le=x.state.transmissionRenderTarget[A.id],_e=A.viewport||ie;le.setSize(_e.z*D.transmissionResolutionScale,_e.w*D.transmissionResolutionScale);const oe=D.getRenderTarget(),pe=D.getActiveCubeFace(),Ee=D.getActiveMipmapLevel();D.setRenderTarget(le),D.getClearColor(W),he=D.getClearAlpha(),he<1&&D.setClearColor(16777215,.5),D.clear(),Mt&&h.render(w);const Pe=D.toneMapping;D.toneMapping=Ut;const ye=A.viewport;if(A.viewport!==void 0&&(A.viewport=void 0),x.setupLightsView(A),nt===!0&&H.setGlobalState(D.clippingPlanes,A),gn(a,w,A),be.updateMultisampleRenderTarget(le),be.updateRenderTargetMipmap(le),$e.has("WEBGL_multisampled_render_to_texture")===!1){let ge=!1;for(let Fe=0,tt=_.length;Fe<tt;Fe++){const Ke=_[Fe],{object:ze,geometry:dt,material:ve,group:pt}=Ke;if(ve.side===wt&&ze.layers.test(A.layers)){const Ge=ve.side;ve.side=St,ve.needsUpdate=!0,sr(ze,w,A,dt,ve,pt),ve.side=Ge,ve.needsUpdate=!0,ge=!0}}ge===!0&&(be.updateMultisampleRenderTarget(le),be.updateRenderTargetMipmap(le))}D.setRenderTarget(oe,pe,Ee),D.setClearColor(W,he),ye!==void 0&&(A.viewport=ye),D.toneMapping=Pe}function gn(a,_,w){const A=_.isScene===!0?_.overrideMaterial:null;for(let P=0,le=a.length;P<le;P++){const _e=a[P],{object:oe,geometry:pe,group:Ee}=_e;let Pe=_e.material;Pe.allowOverride===!0&&A!==null&&(Pe=A),oe.layers.test(w.layers)&&sr(oe,_,w,pe,Pe,Ee)}}function sr(a,_,w,A,P,le){z!==null&&P.isNodeMaterial&&z.setObject(a,P),a.onBeforeRender(D,_,w,A,P,le),a.modelViewMatrix.multiplyMatrices(w.matrixWorldInverse,a.matrixWorld),a.normalMatrix.getNormalMatrix(a.modelViewMatrix),P.onBeforeRender(D,_,w,A,a,le),P.transparent===!0&&P.side===wt&&P.forceSinglePass===!1?(P.side=St,P.needsUpdate=!0,D.renderBufferDirect(w,_,A,P,a,le),P.side=jt,P.needsUpdate=!0,D.renderBufferDirect(w,_,A,P,a,le),P.side=wt):D.renderBufferDirect(w,_,A,P,a,le),a.onAfterRender(D,_,w,A,P,le)}function lr(a){let _=0;for(const w of a)w.texture!==null&&_++;return _}function sn(a,_,w){_.isScene!==!0&&(_=Et);const A=Te.get(a),P=x.state.lights,le=x.state.shadowsArray,_e=P.state.version,oe=k.getParameters(a,P.state,le,_,w,x.state.lightProbeGridArray),pe=k.getProgramCacheKey(oe);let Ee=A.programs;A.environment=a.isMeshStandardMaterial||a.isMeshLambertMaterial||a.isMeshPhongMaterial?_.environment:null,A.fog=_.fog;const Pe=a.isMeshStandardMaterial||a.isMeshLambertMaterial&&!a.envMap||a.isMeshPhongMaterial&&!a.envMap;A.envMap=d.get(a.envMap||A.environment,Pe),A.envMapRotation=A.environment!==null&&a.envMap===null?_.environmentRotation:a.envMapRotation,Ee===void 0&&(a.addEventListener("dispose",Ve),Ee=new Map,A.programs=Ee,A.programVariants=new Map);let ye=Ee.get(pe);if(ye!==void 0){if(A.currentProgram===ye&&A.lightsStateVersion===_e)return fr(a,oe),ye}else oe.uniforms=k.getUniforms(a),z!==null&&a.isNodeMaterial&&z.build(a,w,oe),a.onBeforeCompile(oe,D),ye=k.acquireProgram(oe,pe),Ee.set(pe,ye),A.uniforms=oe.uniforms;const ge=A.uniforms;return(!a.isShaderMaterial&&!a.isRawShaderMaterial||a.clipping===!0)&&(ge.clippingPlanes=H.uniform),fr(a,oe),A.needsLights=ho(a),A.lightsStateVersion=_e,A.needsLights&&(ge.ambientLightColor.value=P.state.ambient,ge.lightProbe.value=P.state.probe,ge.sunLights.value=P.state.sun,ge.sunLightShadows.value=P.state.sunShadow,ge.directionalLights.value=P.state.directional,ge.directionalLightShadows.value=P.state.directionalShadow,ge.spotLights.value=P.state.spot,ge.spotLightShadows.value=P.state.spotShadow,ge.rectAreaLights.value=P.state.rectArea,ge.ltc_1.value=P.state.rectAreaLTC1,ge.ltc_2.value=P.state.rectAreaLTC2,ge.pointLights.value=P.state.point,ge.pointLightShadows.value=P.state.pointShadow,ge.hemisphereLights.value=P.state.hemi,ge.sunShadowMatrix.value=P.state.sunShadowMatrix,ge.sunShadowCascade.value=P.state.sunShadowCascade,ge.directionalShadowMatrix.value=P.state.directionalShadowMatrix,ge.spotLightMatrix.value=P.state.spotLightMatrix,ge.spotLightMap.value=P.state.spotLightMap,ge.pointShadowMatrix.value=P.state.pointShadowMatrix),A.numLightProbeGrids=lr(x.state.lightProbeGridArray),A.currentProgram=ye,A.uniformsList=null,ye}function fo(a,_,w,A,P){const le=Te.get(a),_e=le.currentProgram.id+","+A+","+P;let oe=le.programVariants.get(_e);return oe===void 0?(oe=sn(a,_,w),le.programVariants.set(_e,oe)):(le.currentProgram=oe,le.uniformsList=null,le.outputColorSpace=A,le.toneMapping=P),oe}function cr(a){if(a.uniformsList===null){const _=a.currentProgram.getUniforms();a.uniformsList=On.seqWithValue(_.seq,a.uniforms)}return a.uniformsList}function fr(a,_){const w=Te.get(a);w.outputColorSpace=_.outputColorSpace,w.batching=_.batching,w.batchingColor=_.batchingColor,w.instancing=_.instancing,w.instancingColor=_.instancingColor,w.instancingMorph=_.instancingMorph,w.skinning=_.skinning,w.morphTargets=_.morphTargets,w.morphNormals=_.morphNormals,w.morphColors=_.morphColors,w.morphTargetsCount=_.morphTargetsCount,w.numClippingPlanes=_.numClippingPlanes,w.numIntersection=_.numClipIntersection,w.vertexAlphas=_.vertexAlphas,w.vertexTangents=_.vertexTangents,w.toneMapping=_.toneMapping}function uo(a,_,w,A,P){_.isScene!==!0&&(_=Et),be.resetTextureUnits();const le=_.fog,_e=A.isMeshStandardMaterial||A.isMeshLambertMaterial||A.isMeshPhongMaterial?_.environment:null,oe=fe===null?D.outputColorSpace:fe.isXRRenderTarget===!0?fe.texture.colorSpace:Je.workingColorSpace,pe=A.isMeshStandardMaterial||A.isMeshLambertMaterial&&!A.envMap||A.isMeshPhongMaterial&&!A.envMap,Ee=d.get(A.envMap||_e,pe),Pe=A.vertexColors===!0&&!!w.attributes.color&&w.attributes.color.itemSize===4,ye=!!w.attributes.tangent&&(!!A.normalMap||A.anisotropy>0),ge=!!w.morphAttributes.position,Fe=!!w.morphAttributes.normal,tt=!!w.morphAttributes.color;let Ke=Ut;A.toneMapped&&(fe===null||fe.isXRRenderTarget===!0)&&(Ke=D.toneMapping);const ze=w.morphAttributes.position||w.morphAttributes.normal||w.morphAttributes.color,dt=ze!==void 0?ze.length:0,ve=Te.get(A),pt=x.state.lights;if(nt===!0&&(gt===!0||a!==j)){const et=a===j&&A.id===K;H.setState(A,a,et)}let Ge=!1,Xt=!1;A.version===ve.__version?ve.needsLights&&ve.lightsStateVersion!==pt.state.version||P.isBatchedMesh&&ve.batching===!1||!P.isBatchedMesh&&ve.batching===!0||P.isBatchedMesh&&ve.batchingColor===!0&&P._colorsTexture===null||P.isBatchedMesh&&ve.batchingColor===!1&&P._colorsTexture!==null||P.isInstancedMesh&&ve.instancing===!1||!P.isInstancedMesh&&ve.instancing===!0||P.isSkinnedMesh&&ve.skinning===!1||!P.isSkinnedMesh&&ve.skinning===!0||P.isInstancedMesh&&ve.instancingColor===!0&&P.instanceColor===null||P.isInstancedMesh&&ve.instancingColor===!1&&P.instanceColor!==null||P.isInstancedMesh&&ve.instancingMorph===!0&&P.morphTexture===null||P.isInstancedMesh&&ve.instancingMorph===!1&&P.morphTexture!==null||ve.envMap!==Ee||A.fog===!0&&ve.fog!==le||ve.numClippingPlanes!==void 0&&(ve.numClippingPlanes!==H.numPlanes||ve.numIntersection!==H.numIntersection)||ve.vertexAlphas!==Pe||ve.vertexTangents!==ye||ve.morphTargets!==ge||ve.morphNormals!==Fe||ve.morphColors!==tt||ve.morphTargetsCount!==dt||ve.numLightProbeGrids!==lr(x.state.lightProbeGridArray)?Ge=!0:(ve.outputColorSpace!==oe||ve.toneMapping!==Ke)&&(Xt=!0):(Ge=!0,ve.__version=A.version);let ut=ve.currentProgram;(Ge===!0||Xt===!0)&&(ut=Xt===!0?fo(A,_,P,oe,Ke):sn(A,_,P),z&&A.isNodeMaterial&&z.onUpdateProgram(A,ut,ve));let vn=!1,Bt=!1,Kt=!1;const Be=ut.getUniforms(),ft=ve.uniforms;if(ce.useProgram(ut.program)&&(vn=!0,Bt=!0,Kt=!0),A.id!==K&&(K=A.id,Bt=!0),vn||j!==a){ce.buffers.depth.getReversed()&&a.reversedDepth!==!0&&(a._reversedDepth=!0,a.updateProjectionMatrix()),Be.setValue(E,"projectionMatrix",a.projectionMatrix),Be.setValue(E,"viewMatrix",a.matrixWorldInverse);const lt=Be.map.cameraPosition;lt!==void 0&&lt.setValue(E,Lt.setFromMatrixPosition(a.matrixWorld)),Qe.logarithmicDepthBuffer&&Be.setValue(E,"logDepthBufFC",2/(Math.log(a.far+1)/Math.LN2)),(A.isMeshPhongMaterial||A.isMeshToonMaterial||A.isMeshLambertMaterial||A.isMeshBasicMaterial||A.isMeshStandardMaterial||A.isShaderMaterial)&&Be.setValue(E,"isOrthographic",a.isOrthographicCamera===!0),j!==a&&(j=a,Bt=!0,Kt=!0)}if(ve.needsLights&&(pt.state.sunShadowMap.length>0&&Be.setValue(E,"sunShadowMap",pt.state.sunShadowMap,be),pt.state.directionalShadowMap.length>0&&Be.setValue(E,"directionalShadowMap",pt.state.directionalShadowMap,be),pt.state.spotShadowMap.length>0&&Be.setValue(E,"spotShadowMap",pt.state.spotShadowMap,be),pt.state.pointShadowMap.length>0&&Be.setValue(E,"pointShadowMap",pt.state.pointShadowMap,be)),P.isSkinnedMesh){Be.setOptional(E,P,"bindMatrix"),Be.setOptional(E,P,"bindMatrixInverse");const et=P.skeleton;et&&(et.boneTexture===null&&et.computeBoneTexture(),Be.setValue(E,"boneTexture",et.boneTexture,be))}P.isBatchedMesh&&(Be.setOptional(E,P,"batchingTexture"),Be.setValue(E,"batchingTexture",P._matricesTexture,be),Be.setOptional(E,P,"batchingIdTexture"),Be.setValue(E,"batchingIdTexture",P._indirectTexture,be),Be.setOptional(E,P,"batchingColorTexture"),P._colorsTexture!==null&&Be.setValue(E,"batchingColorTexture",P._colorsTexture,be));const Rt=w.morphAttributes;if((Rt.position!==void 0||Rt.normal!==void 0||Rt.color!==void 0)&&V.update(P,w,ut),(Bt||ve.receiveShadow!==P.receiveShadow)&&(ve.receiveShadow=P.receiveShadow,Be.setValue(E,"receiveShadow",P.receiveShadow)),(A.isMeshStandardMaterial||A.isMeshLambertMaterial||A.isMeshPhongMaterial)&&A.envMap===null&&_.environment!==null&&(ft.envMapIntensity.value=_.environmentIntensity),ft.dfgLUT!==void 0&&(ft.dfgLUT.value=dd()),Bt){if(Be.setValue(E,"toneMappingExposure",D.toneMappingExposure),ve.needsLights&&po(ft,Kt),le&&A.fog===!0&&te.refreshFogUniforms(ft,le),te.refreshMaterialUniforms(ft,A,me,Re,x.state.transmissionRenderTarget[a.id]),ve.needsLights&&ve.numLightProbeGrids>0){const et=S;let lt=0;for(const It of x.state.lightProbeGridArray)It.texture!==null&&(et.textures[lt]=It.texture,et.min[lt]=It.boundingBox.min,et.max[lt]=It.boundingBox.max,et.resolution[lt]=It.resolution,lt++);for(const It in et)et[It].length=lt;ft.probesSH.value=et.textures,ft.probesMin.value=et.min,ft.probesMax.value=et.max,ft.probesResolution.value=et.resolution}On.upload(E,cr(ve),ft,be)}if(A.isShaderMaterial&&A.uniformsNeedUpdate===!0&&(On.upload(E,cr(ve),ft,be),A.uniformsNeedUpdate=!1),A.isSpriteMaterial&&Be.setValue(E,"center",P.center),Be.setValue(E,"modelViewMatrix",P.modelViewMatrix),Be.setValue(E,"normalMatrix",P.normalMatrix),Be.setValue(E,"modelMatrix",P.matrixWorld),A.uniformsGroups!==void 0){const et=A.uniformsGroups;for(let lt=0,It=et.length;lt<It;lt++){const ur=et[lt];Me.update(ur,ut),Me.bind(ur,ut)}}return ut}function po(a,_){a.ambientLightColor.needsUpdate=_,a.lightProbe.needsUpdate=_,a.sunLights.needsUpdate=_,a.sunLightShadows.needsUpdate=_,a.directionalLights.needsUpdate=_,a.directionalLightShadows.needsUpdate=_,a.pointLights.needsUpdate=_,a.pointLightShadows.needsUpdate=_,a.spotLights.needsUpdate=_,a.spotLightShadows.needsUpdate=_,a.rectAreaLights.needsUpdate=_,a.hemisphereLights.needsUpdate=_}function ho(a){return a.isMeshLambertMaterial||a.isMeshToonMaterial||a.isMeshPhongMaterial||a.isMeshStandardMaterial||a.isShadowMaterial||a.isShaderMaterial&&a.lights===!0}this.getActiveCubeFace=function(){return $},this.getActiveMipmapLevel=function(){return y},this.getRenderTarget=function(){return fe},this.setRenderTargetTextures=function(a,_,w){const A=Te.get(a);A.__autoAllocateDepthBuffer=a.resolveDepthBuffer===!1,A.__autoAllocateDepthBuffer===!1&&(A.__useRenderToTexture=!1),Te.get(a.texture).__webglTexture=_,Te.get(a.depthTexture).__webglTexture=A.__autoAllocateDepthBuffer?void 0:w,A.__hasExternalTextures=!0},this.setRenderTargetFramebuffer=function(a,_){const w=Te.get(a);w.__webglFramebuffer=_,w.__useDefaultFramebuffer=_===void 0},this.setRenderTarget=function(a,_=0,w=0){fe=a,$=_,y=w;let A=null,P=!1,le=!1;if(a){const oe=Te.get(a);if(oe.__useDefaultFramebuffer!==void 0){ce.bindFramebuffer(E.FRAMEBUFFER,oe.__webglFramebuffer),ie.copy(a.viewport),Ae.copy(a.scissor),Ne=a.scissorTest,ce.viewport(ie),ce.scissor(Ae),ce.setScissorTest(Ne),K=-1;return}else if(oe.__webglFramebuffer===void 0)be.setupRenderTarget(a);else if(oe.__hasExternalTextures)be.rebindTextures(a,Te.get(a.texture).__webglTexture,Te.get(a.depthTexture).__webglTexture);else if(a.depthBuffer){const Pe=a.depthTexture;if(oe.__boundDepthTexture!==Pe){if(Pe!==null&&Te.has(Pe)&&(a.width!==Pe.image.width||a.height!==Pe.image.height))throw new Error("THREE.WebGLRenderer: Attached DepthTexture is initialized to the incorrect size.");be.setupDepthRenderbuffer(a)}}const pe=a.texture;(pe.isData3DTexture||pe.isDataArrayTexture||pe.isCompressedArrayTexture)&&(le=!0);const Ee=Te.get(a).__webglFramebuffer;a.isWebGLCubeRenderTarget?(Array.isArray(Ee[_])?A=Ee[_][w]:A=Ee[_],P=!0):a.samples>0&&be.useMultisampledRTT(a)===!1?A=Te.get(a).__webglMultisampledFramebuffer:Array.isArray(Ee)?A=Ee[w]:A=Ee,ie.copy(a.viewport),Ae.copy(a.scissor),Ne=a.scissorTest}else ie.copy(Ce).multiplyScalar(me).floor(),Ae.copy(qe).multiplyScalar(me).floor(),Ne=ke;if(w!==0&&(A=Y),ce.bindFramebuffer(E.FRAMEBUFFER,A)&&ce.drawBuffers(a,A),ce.viewport(ie),ce.scissor(Ae),ce.setScissorTest(Ne),P){const oe=Te.get(a.texture);E.framebufferTexture2D(E.FRAMEBUFFER,E.COLOR_ATTACHMENT0,E.TEXTURE_CUBE_MAP_POSITIVE_X+_,oe.__webglTexture,w)}else if(le){const oe=_;for(let pe=0;pe<a.textures.length;pe++){const Ee=Te.get(a.textures[pe]);E.framebufferTextureLayer(E.FRAMEBUFFER,E.COLOR_ATTACHMENT0+pe,Ee.__webglTexture,w,oe)}}else if(a!==null&&w!==0){const oe=Te.get(a.texture);E.framebufferTexture2D(E.FRAMEBUFFER,E.COLOR_ATTACHMENT0,E.TEXTURE_2D,oe.__webglTexture,w)}K=-1};function dr(a){const _=Te.get(a);return(_.__readFormat!==a.format||_.__readType!==a.type)&&(_.__readFormat=a.format,_.__readType=a.type,_.__formatReadable=Qe.textureFormatReadable(a.format),_.__typeReadable=Qe.textureTypeReadable(a.type)),_}this.readRenderTargetPixels=function(a,_,w,A,P,le,_e,oe=0){if(!(a&&a.isWebGLRenderTarget)){Ze("WebGLRenderer.readRenderTargetPixels: renderTarget is not THREE.WebGLRenderTarget.");return}let pe=Te.get(a).__webglFramebuffer;if(a.isWebGLCubeRenderTarget&&_e!==void 0&&(pe=pe[_e],Array.isArray(pe)&&(pe=pe[0])),pe){ce.bindFramebuffer(E.FRAMEBUFFER,pe);try{const Ee=a.textures[oe],Pe=Ee.format,ye=Ee.type;a.textures.length>1&&E.readBuffer(E.COLOR_ATTACHMENT0+oe);const ge=dr(Ee);if(ge.__formatReadable===!1){Ze("WebGLRenderer.readRenderTargetPixels: renderTarget is not in RGBA or implementation defined format.");return}if(ge.__typeReadable===!1){Ze("WebGLRenderer.readRenderTargetPixels: renderTarget is not in UnsignedByteType or implementation defined type.");return}_>=0&&_<=a.width-A&&w>=0&&w<=a.height-P&&E.readPixels(_,w,A,P,ee.convert(Pe),ee.convert(ye),le)}finally{const Ee=fe!==null?Te.get(fe).__webglFramebuffer:null;ce.bindFramebuffer(E.FRAMEBUFFER,Ee)}}},this.readRenderTargetPixelsAsync=async function(a,_,w,A,P,le,_e,oe=0){if(!(a&&a.isWebGLRenderTarget))throw new Error("THREE.WebGLRenderer.readRenderTargetPixels: renderTarget is not THREE.WebGLRenderTarget.");let pe=Te.get(a).__webglFramebuffer;if(a.isWebGLCubeRenderTarget&&_e!==void 0&&(pe=pe[_e],Array.isArray(pe)&&(pe=pe[0])),pe)if(_>=0&&_<=a.width-A&&w>=0&&w<=a.height-P){ce.bindFramebuffer(E.FRAMEBUFFER,pe);const Ee=a.textures[oe],Pe=Ee.format,ye=Ee.type;a.textures.length>1&&E.readBuffer(E.COLOR_ATTACHMENT0+oe);const ge=dr(Ee);if(ge.__formatReadable===!1)throw new Error("THREE.WebGLRenderer.readRenderTargetPixelsAsync: renderTarget is not in RGBA or implementation defined format.");if(ge.__typeReadable===!1)throw new Error("THREE.WebGLRenderer.readRenderTargetPixelsAsync: renderTarget is not in UnsignedByteType or implementation defined type.");const Fe=E.createBuffer();E.bindBuffer(E.PIXEL_PACK_BUFFER,Fe),E.bufferData(E.PIXEL_PACK_BUFFER,le.byteLength,E.STREAM_READ),E.readPixels(_,w,A,P,ee.convert(Pe),ee.convert(ye),0),E.bindBuffer(E.PIXEL_PACK_BUFFER,null);const tt=fe!==null?Te.get(fe).__webglFramebuffer:null;ce.bindFramebuffer(E.FRAMEBUFFER,tt);const Ke=E.fenceSync(E.SYNC_GPU_COMMANDS_COMPLETE,0);return E.flush(),await So(E,Ke,4),E.bindBuffer(E.PIXEL_PACK_BUFFER,Fe),E.getBufferSubData(E.PIXEL_PACK_BUFFER,0,le),E.bindBuffer(E.PIXEL_PACK_BUFFER,null),E.deleteBuffer(Fe),E.deleteSync(Ke),le}else throw new Error("THREE.WebGLRenderer.readRenderTargetPixelsAsync: requested read bounds are out of range.")},this.copyFramebufferToTexture=function(a,_=null,w=0){const A=Math.pow(2,-w),P=Math.floor(a.image.width*A),le=Math.floor(a.image.height*A),_e=_!==null?_.x:0,oe=_!==null?_.y:0;be.setTexture2D(a,0),E.copyTexSubImage2D(E.TEXTURE_2D,w,0,0,_e,oe,P,le),ce.unbindTexture()},this.copyTextureToTexture=function(a,_,w=null,A=null,P=0,le=0){let _e,oe,pe,Ee,Pe,ye,ge,Fe,tt;const Ke=a.isCompressedTexture?a.mipmaps[le]:a.image;if(w!==null)_e=w.max.x-w.min.x,oe=w.max.y-w.min.y,pe=w.isBox3?w.max.z-w.min.z:1,Ee=w.min.x,Pe=w.min.y,ye=w.isBox3?w.min.z:0;else{const Be=Math.pow(2,-P);_e=Math.floor(Ke.width*Be),oe=Math.floor(Ke.height*Be),a.isDataArrayTexture?pe=Ke.depth:a.isData3DTexture?pe=Math.floor(Ke.depth*Be):pe=1,Ee=0,Pe=0,ye=0}A!==null?(ge=A.x,Fe=A.y,tt=A.z):(ge=0,Fe=0,tt=0);const ze=ee.convert(_.format),dt=ee.convert(_.type);let ve;_.isData3DTexture?(be.setTexture3D(_,0),ve=E.TEXTURE_3D):_.isDataArrayTexture||_.isCompressedArrayTexture?(be.setTexture2DArray(_,0),ve=E.TEXTURE_2D_ARRAY):(be.setTexture2D(_,0),ve=E.TEXTURE_2D),ce.activeTexture(E.TEXTURE0),ce.pixelStorei(E.UNPACK_FLIP_Y_WEBGL,_.flipY),ce.pixelStorei(E.UNPACK_PREMULTIPLY_ALPHA_WEBGL,_.premultiplyAlpha),ce.pixelStorei(E.UNPACK_ALIGNMENT,_.unpackAlignment);const pt=ce.getParameter(E.UNPACK_ROW_LENGTH),Ge=ce.getParameter(E.UNPACK_IMAGE_HEIGHT),Xt=ce.getParameter(E.UNPACK_SKIP_PIXELS),ut=ce.getParameter(E.UNPACK_SKIP_ROWS),vn=ce.getParameter(E.UNPACK_SKIP_IMAGES);ce.pixelStorei(E.UNPACK_ROW_LENGTH,Ke.width),ce.pixelStorei(E.UNPACK_IMAGE_HEIGHT,Ke.height),ce.pixelStorei(E.UNPACK_SKIP_PIXELS,Ee),ce.pixelStorei(E.UNPACK_SKIP_ROWS,Pe),ce.pixelStorei(E.UNPACK_SKIP_IMAGES,ye);const Bt=a.isDataArrayTexture||a.isData3DTexture,Kt=_.isDataArrayTexture||_.isData3DTexture;if(a.isDepthTexture){const Be=Te.get(a),ft=Te.get(_),Rt=Te.get(Be.__renderTarget),et=Te.get(ft.__renderTarget);ce.bindFramebuffer(E.READ_FRAMEBUFFER,Rt.__webglFramebuffer),ce.bindFramebuffer(E.DRAW_FRAMEBUFFER,et.__webglFramebuffer);for(let lt=0;lt<pe;lt++)Bt&&(E.framebufferTextureLayer(E.READ_FRAMEBUFFER,E.COLOR_ATTACHMENT0,Te.get(a).__webglTexture,P,ye+lt),E.framebufferTextureLayer(E.DRAW_FRAMEBUFFER,E.COLOR_ATTACHMENT0,Te.get(_).__webglTexture,le,tt+lt)),E.blitFramebuffer(Ee,Pe,Ee+_e,Pe+oe,ge,Fe,ge+_e,Fe+oe,E.DEPTH_BUFFER_BIT,E.NEAREST);ce.bindFramebuffer(E.READ_FRAMEBUFFER,null),ce.bindFramebuffer(E.DRAW_FRAMEBUFFER,null)}else if(P!==0||a.isRenderTargetTexture||Te.has(a)){const Be=Te.get(a),ft=Te.get(_);ce.bindFramebuffer(E.READ_FRAMEBUFFER,T),ce.bindFramebuffer(E.DRAW_FRAMEBUFFER,Z);for(let Rt=0;Rt<pe;Rt++)Bt?E.framebufferTextureLayer(E.READ_FRAMEBUFFER,E.COLOR_ATTACHMENT0,Be.__webglTexture,P,ye+Rt):E.framebufferTexture2D(E.READ_FRAMEBUFFER,E.COLOR_ATTACHMENT0,E.TEXTURE_2D,Be.__webglTexture,P),Kt?E.framebufferTextureLayer(E.DRAW_FRAMEBUFFER,E.COLOR_ATTACHMENT0,ft.__webglTexture,le,tt+Rt):E.framebufferTexture2D(E.DRAW_FRAMEBUFFER,E.COLOR_ATTACHMENT0,E.TEXTURE_2D,ft.__webglTexture,le),P!==0?E.blitFramebuffer(Ee,Pe,Ee+_e,Pe+oe,ge,Fe,ge+_e,Fe+oe,E.COLOR_BUFFER_BIT,E.NEAREST):Kt?E.copyTexSubImage3D(ve,le,ge,Fe,tt+Rt,Ee,Pe,_e,oe):E.copyTexSubImage2D(ve,le,ge,Fe,Ee,Pe,_e,oe);ce.bindFramebuffer(E.READ_FRAMEBUFFER,null),ce.bindFramebuffer(E.DRAW_FRAMEBUFFER,null)}else Kt?a.isDataTexture||a.isData3DTexture?E.texSubImage3D(ve,le,ge,Fe,tt,_e,oe,pe,ze,dt,Ke.data):_.isCompressedArrayTexture?E.compressedTexSubImage3D(ve,le,ge,Fe,tt,_e,oe,pe,ze,Ke.data):E.texSubImage3D(ve,le,ge,Fe,tt,_e,oe,pe,ze,dt,Ke):a.isDataTexture?E.texSubImage2D(E.TEXTURE_2D,le,ge,Fe,_e,oe,ze,dt,Ke.data):a.isCompressedTexture?E.compressedTexSubImage2D(E.TEXTURE_2D,le,ge,Fe,Ke.width,Ke.height,ze,Ke.data):E.texSubImage2D(E.TEXTURE_2D,le,ge,Fe,_e,oe,ze,dt,Ke);ce.pixelStorei(E.UNPACK_ROW_LENGTH,pt),ce.pixelStorei(E.UNPACK_IMAGE_HEIGHT,Ge),ce.pixelStorei(E.UNPACK_SKIP_PIXELS,Xt),ce.pixelStorei(E.UNPACK_SKIP_ROWS,ut),ce.pixelStorei(E.UNPACK_SKIP_IMAGES,vn),le===0&&_.generateMipmaps===!0&&_.mipmapsAutoUpdate===!0&&E.generateMipmap(ve),ce.unbindTexture()},this.initRenderTarget=function(a){Te.get(a).__webglFramebuffer===void 0&&be.setupRenderTarget(a)},this.initTexture=function(a){a.isCubeTexture?be.setTextureCube(a,0):a.isData3DTexture?be.setTexture3D(a,0):a.isDataArrayTexture||a.isCompressedArrayTexture?be.setTexture2DArray(a,0):be.setTexture2D(a,0),ce.unbindTexture()},this.resetState=function(){$=0,y=0,fe=null,ce.reset(),ne.reset()},typeof __THREE_DEVTOOLS__<"u"&&__THREE_DEVTOOLS__.dispatchEvent(new CustomEvent("observe",{detail:this}))}get coordinateSystem(){return Ji}get outputColorSpace(){return this._outputColorSpace}set outputColorSpace(n){this._outputColorSpace=n;const t=this.getContext();t.drawingBufferColorSpace=Je._getDrawingBufferColorSpace(n),t.unpackColorSpace=Je._getUnpackColorSpace()}}export{Jn as ACESFilmicToneMapping,an as AddEquation,xr as AddOperation,_d as AdditiveAnimationBlendMode,hi as AdditiveBlending,Qn as AgXToneMapping,Ea as AlphaFormat,ni as AlwaysCompare,ea as AlwaysDepth,gd as AlwaysStencilFunc,vd as AmbientLight,Sd as AnimationAction,Ed as AnimationClip,Md as AnimationLoader,xd as AnimationMixer,Td as AnimationObjectGroup,Ad as AnimationUtils,Rd as ArcCurve,Aa as ArrayCamera,Cd as ArrowHelper,bd as AttachedBindMode,Pd as Audio,Ld as AudioAnalyser,Dd as AudioContext,Ud as AudioListener,wd as AudioLoader,Nd as AxesHelper,St as BackSide,Id as BasicDepthPacking,yd as BasicShadowMap,Fd as BatchedMesh,Od as BezierInterpolant,Bd as Bone,Gd as BooleanKeyframeTrack,Hd as Box2,Vd as Box3,Wd as Box3Helper,Sn as BoxGeometry,kd as BoxHelper,Dr as BufferAttribute,qn as BufferGeometry,zd as BufferGeometryLoader,va as ByteType,Xd as Cache,Kd as Camera,qd as CameraHelper,Yd as CanvasTexture,Zd as CapsuleGeometry,$d as CatmullRomCurve3,jn as CineonToneMapping,Qd as CircleGeometry,Rn as ClampToEdgeWrapping,Jd as Clock,Ye as Color,jd as ColorKeyframeTrack,Je as ColorManagement,eu as Compatibility,tu as CompressedArrayTexture,nu as CompressedCubeTexture,iu as CompressedTexture,ru as CompressedTextureLoader,au as ConeGeometry,yr as ConstantAlphaFactor,Or as ConstantColorFactor,ou as Controls,En as CubeCamera,ii as CubeDepthTexture,ln as CubeReflectionMapping,en as CubeRefractionMapping,zn as CubeTexture,su as CubeTextureLoader,lu as CubicBezierCurve,cu as CubicBezierCurve3,fu as CubicInterpolant,di as CullFaceBack,ia as CullFaceFront,du as CullFaceFrontBack,na as CullFaceNone,uu as Curve,pu as CurvePath,ra as CustomBlending,Zn as CustomToneMapping,hu as CylinderGeometry,mu as Cylindrical,Er as Data3DTexture,Kn as DataArrayTexture,Ra as DataTexture,_u as DataTextureLoader,gu as DataUtils,vu as DecrementStencilOp,Su as DecrementWrapStencilOp,Eu as DefaultLoadingManager,rn as DepthFormat,Vt as DepthStencilFormat,tn as DepthTexture,Mu as DetachedBindMode,xu as DirectionalLight,Tu as DirectionalLightHelper,Au as DiscreteInterpolant,Ru as DodecahedronGeometry,wt as DoubleSide,Wr as DstAlphaFactor,kr as DstColorFactor,Cu as DynamicCopyUsage,bu as DynamicDrawUsage,Pu as DynamicReadUsage,Lu as EdgesGeometry,Du as EllipseCurve,ua as EqualCompare,Jr as EqualDepth,Uu as EqualStencilFunc,Mn as EquirectangularReflectionMapping,xn as EquirectangularRefractionMapping,wu as Euler,Ta as EventDispatcher,$i as ExternalTexture,Nu as ExtrudeGeometry,Iu as FileLoader,yu as Float16BufferAttribute,Yn as Float32BufferAttribute,yt as FloatType,Fu as Fog,Ou as FogExp2,Bu as FramebufferTexture,jt as FrontSide,ci as Frustum,La as FrustumArray,Gu as GLBufferAttribute,Hu as GLSL1,ri as GLSL3,da as GreaterCompare,$r as GreaterDepth,si as GreaterEqualCompare,Qr as GreaterEqualDepth,Vu as GreaterEqualStencilFunc,Wu as GreaterStencilFunc,ku as GridHelper,zu as Group,Xu as HTMLTexture,Dt as HalfFloatType,Ku as HemisphereLight,qu as HemisphereLightHelper,Yu as IcosahedronGeometry,Zu as ImageBitmapLoader,$u as ImageLoader,Qu as ImageUtils,Ju as IncrementStencilOp,ju as IncrementWrapStencilOp,ep as InstancedBufferAttribute,tp as InstancedBufferGeometry,np as InstancedInterleavedBuffer,ip as InstancedMesh,rp as Int16BufferAttribute,ap as Int32BufferAttribute,op as Int8BufferAttribute,kn as IntType,sp as InterleavedBuffer,lp as InterleavedBufferAttribute,cp as Interpolant,fp as InterpolateBezier,dp as InterpolateDiscrete,up as InterpolateLinear,pp as InterpolateSmooth,hp as InterpolationSamplingMode,mp as InterpolationSamplingType,_p as InvertStencilOp,gp as KeepStencilOp,vp as KeyframeTrack,Sp as LOD,Ep as LatheGeometry,br as Layers,pa as LessCompare,jr as LessDepth,li as LessEqualCompare,fi as LessEqualDepth,Mp as LessEqualStencilFunc,xp as LessStencilFunc,Tp as Light,Ap as LightProbe,Rp as LightShadow,Cp as Line,bp as Line3,Pp as LineBasicMaterial,Lp as LineCurve,Dp as LineCurve3,Up as LineDashedMaterial,wp as LineLoop,Np as LineSegments,At as LinearFilter,Ip as LinearInterpolant,yp as LinearMipMapLinearFilter,Fp as LinearMipMapNearestFilter,Ht as LinearMipmapLinearFilter,Cn as LinearMipmapNearestFilter,Xn as LinearSRGBColorSpace,ti as LinearToneMapping,ai as LinearTransfer,Op as Loader,Bp as LoaderUtils,Gp as LoadingManager,Hp as LoopOnce,Vp as LoopPingPong,Wp as LoopRepeat,kp as MOUSE,zp as Material,Xp as MaterialBlending,Kp as MaterialLoader,qp as MathUtils,Yp as Matrix2,Oe as Matrix3,Yt as Matrix4,oa as MaxEquation,Gt as Mesh,Zp as MeshBasicMaterial,Pr as MeshDepthMaterial,Lr as MeshDistanceMaterial,$p as MeshLambertMaterial,Qp as MeshMatcapMaterial,Jp as MeshNormalMaterial,jp as MeshPhongMaterial,eh as MeshPhysicalMaterial,th as MeshStandardMaterial,nh as MeshToonMaterial,aa as MinEquation,sa as MirroredRepeatWrapping,Tr as MixOperation,ui as MultiplyBlending,Ar as MultiplyOperation,Wt as NearestFilter,ih as NearestMipMapLinearFilter,rh as NearestMipMapNearestFilter,dn as NearestMipmapLinearFilter,ca as NearestMipmapNearestFilter,$n as NeutralToneMapping,ha as NeverCompare,ta as NeverDepth,ah as NeverStencilFunc,Zt as NoBlending,Jt as NoColorSpace,oh as NoNormalPacking,Ut as NoToneMapping,sh as NormalAnimationBlendMode,fn as NormalBlending,lh as NormalGAPacking,ch as NormalRGPacking,fa as NotEqualCompare,Zr as NotEqualDepth,fh as NotEqualStencilFunc,dh as NumberKeyframeTrack,uh as Object3D,ph as ObjectLoader,Cr as ObjectSpaceNormalMap,hh as OctahedronGeometry,qr as OneFactor,Ir as OneMinusConstantAlphaFactor,Fr as OneMinusConstantColorFactor,Br as OneMinusDstAlphaFactor,Gr as OneMinusDstColorFactor,Hr as OneMinusSrcAlphaFactor,Vr as OneMinusSrcColorFactor,Sr as OrthographicCamera,cn as PCFShadowMap,Ur as PCFSoftShadowMap,_n as PMREMGenerator,mh as Path,Un as PerspectiveCamera,hr as Plane,Wn as PlaneGeometry,_h as PlaneHelper,gh as PointLight,vh as PointLightHelper,Sh as Points,Eh as PointsMaterial,Mh as PolarGridHelper,xh as PolyhedronGeometry,Th as PositionalAudio,Ah as PropertyBinding,Rh as PropertyMixer,Ch as QuadraticBezierCurve,bh as QuadraticBezierCurve3,Ph as Quaternion,Lh as QuaternionKeyframeTrack,Dh as QuaternionLinearInterpolant,bi as R11_EAC_Format,An as RED_GREEN_RGTC2_Format,qi as RED_RGTC1_Format,Pa as REVISION,Tn as RG11_EAC_Format,Uh as RGBADepthPacking,Ft as RGBAFormat,Si as RGBAIntegerFormat,Vi as RGBA_ASTC_10x10_Format,Bi as RGBA_ASTC_10x5_Format,Gi as RGBA_ASTC_10x6_Format,Hi as RGBA_ASTC_10x8_Format,Wi as RGBA_ASTC_12x10_Format,ki as RGBA_ASTC_12x12_Format,Di as RGBA_ASTC_4x4_Format,Ui as RGBA_ASTC_5x4_Format,wi as RGBA_ASTC_5x5_Format,Ni as RGBA_ASTC_6x5_Format,Ii as RGBA_ASTC_6x6_Format,yi as RGBA_ASTC_8x5_Format,Fi as RGBA_ASTC_8x6_Format,Oi as RGBA_ASTC_8x8_Format,zi as RGBA_BPTC_Format,Ci as RGBA_ETC2_EAC_Format,Ti as RGBA_PVRTC_2BPPV1_Format,xi as RGBA_PVRTC_4BPPV1_Format,Pn as RGBA_S3TC_DXT1_Format,Ln as RGBA_S3TC_DXT3_Format,Dn as RGBA_S3TC_DXT5_Format,wh as RGBDepthPacking,Ma as RGBFormat,Nh as RGBIntegerFormat,Xi as RGB_BPTC_SIGNED_Format,Ki as RGB_BPTC_UNSIGNED_Format,Ai as RGB_ETC1_Format,Ri as RGB_ETC2_Format,Mi as RGB_PVRTC_2BPPV1_Format,Ei as RGB_PVRTC_4BPPV1_Format,bn as RGB_S3TC_DXT1_Format,Ih as RGDepthPacking,Qt as RGFormat,vi as RGIntegerFormat,vr as RawShaderMaterial,yh as Ray,Fh as Raycaster,Oh as RectAreaLight,xa as RedFormat,gi as RedIntegerFormat,ei as ReinhardToneMapping,Bh as RenderObjectRefreshType,Gh as RenderTarget,Hh as RenderTarget3D,la as RepeatWrapping,Vh as ReplaceStencilOp,wr as ReverseSubtractEquation,Wh as RingGeometry,Pi as SIGNED_R11_EAC_Format,Zi as SIGNED_RED_GREEN_RGTC2_Format,Yi as SIGNED_RED_RGTC1_Format,Li as SIGNED_RG11_EAC_Format,ba as SRGBColorSpace,Xe as SRGBTransfer,kh as Scene,De as ShaderChunk,Pt as ShaderLib,qt as ShaderMaterial,zh as ShadowMaterial,Xh as Shape,Kh as ShapeGeometry,qh as ShapePath,Yh as ShapeUtils,Sa as ShortType,Zh as Skeleton,$h as SkeletonHelper,Qh as SkinnedMesh,Jh as Source,jh as Sphere,em as SphereGeometry,tm as Spherical,nm as SphericalHarmonics3,im as SplineCurve,rm as SpotLight,am as SpotLightHelper,om as Sprite,sm as SpriteMaterial,Xr as SrcAlphaFactor,zr as SrcAlphaSaturateFactor,Kr as SrcColorFactor,lm as StaticCopyUsage,cm as StaticDrawUsage,fm as StaticReadUsage,dm as StereoCamera,um as StreamCopyUsage,pm as StreamDrawUsage,hm as StreamReadUsage,mm as StringKeyframeTrack,Nr as SubtractEquation,pi as SubtractiveBlending,_m as TOUCH,oi as TangentSpaceNormalMap,gm as TetrahedronGeometry,Mr as Texture,vm as TextureLoader,Sm as TextureSource,Em as TextureUtils,Mm as Timer,xm as TimestampQuery,Tm as TorusGeometry,Am as TorusKnotGeometry,Rm as Triangle,Cm as TriangleFanDrawMode,bm as TriangleStripDrawMode,Pm as TrianglesDrawMode,Lm as TubeGeometry,Dm as UVMapping,gr as Uint16BufferAttribute,_r as Uint32BufferAttribute,Um as Uint8BufferAttribute,wm as Uint8ClampedBufferAttribute,Nm as Uniform,Im as UniformsGroup,de as UniformsLib,Rr as UniformsUtils,Ct as UnsignedByteType,ga as UnsignedInt101111Type,$t as UnsignedInt248Type,_a as UnsignedInt5999Type,kt as UnsignedIntType,mi as UnsignedShort4444Type,_i as UnsignedShort5551Type,un as UnsignedShortType,nn as VSMShadowMap,ht as Vector2,We as Vector3,mt as Vector4,ym as VectorKeyframeTrack,Fm as VideoFrameTexture,Om as VideoTexture,Bm as WebGL3DRenderTarget,Gm as WebGLArrayRenderTarget,Ji as WebGLCoordinateSystem,In as WebGLCubeRenderTarget,bt as WebGLRenderTarget,ud as WebGLRenderer,so as WebGLUtils,Hm as WebGPUCoordinateSystem,wn as WebXRController,Vm as WireframeGeometry,Wm as WrapAroundEnding,km as ZeroCurvatureEnding,Yr as ZeroFactor,zm as ZeroSlopeEnding,Xm as ZeroStencilOp,Ca as createCanvasElement,Ze as error,Km as getConsoleFunction,Qi as log,qm as setConsoleFunction,He as warn,mr as warnOnce};
