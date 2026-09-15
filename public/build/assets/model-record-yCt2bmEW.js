/**
 * @license
 * Copyright 2010-2026 Three.js Authors
 * SPDX-License-Identifier: MIT
 */const Vi={ROTATE:0,DOLLY:1,PAN:2},zi={ROTATE:0,PAN:1,DOLLY_PAN:2,DOLLY_ROTATE:3},Oh=0,rl=1,Bh=2,dr=1,zh=2,hs=3,ni=0,ke=1,Xe=2,In=0,Gi=1,al=2,ol=3,ll=4,kh=5,di=100,Vh=101,Gh=102,Hh=103,Wh=104,Xh=200,qh=201,Yh=202,$h=203,Pa=204,Da=205,Kh=206,Zh=207,Jh=208,jh=209,Qh=210,tu=211,eu=212,nu=213,iu=214,La=0,Ia=1,Na=2,Xi=3,Ua=4,Fa=5,Oa=6,Ba=7,bo=0,su=1,ru=2,Mn=0,Dc=1,Lc=2,Ic=3,Pr=4,Nc=5,Uc=6,Fc=7,Oc=300,_i=301,qi=302,Fr=303,Or=304,Dr=306,pi=1e3,ln=1001,xs=1002,Ee=1003,au=1004,Ds=1005,ye=1006,Br=1007,Ln=1008,qe=1009,Bc=1010,zc=1011,vs=1012,Eo=1013,yn=1014,cn=1015,Un=1016,To=1017,Ao=1018,Ms=1020,kc=35902,Vc=35899,Gc=1021,Hc=1022,hn=1023,Fn=1026,mi=1027,wo=1028,Ro=1029,xi=1030,Co=1031,Po=1033,fr=33776,pr=33777,mr=33778,gr=33779,za=35840,ka=35841,Va=35842,Ga=35843,Ha=36196,Wa=37492,Xa=37496,qa=37488,Ya=37489,vr=37490,$a=37491,Ka=37808,Za=37809,Ja=37810,ja=37811,Qa=37812,to=37813,eo=37814,no=37815,io=37816,so=37817,ro=37818,ao=37819,oo=37820,lo=37821,co=36492,ho=36494,uo=36495,fo=36283,po=36284,Mr=36285,mo=36286,ou=3200,Sr=0,lu=1,ti="",Fe="srgb",yr="srgb-linear",br="linear",re="srgb",yi=7680,cl=519,cu=512,hu=513,uu=514,Do=515,du=516,fu=517,Lo=518,pu=519,hl=35044,ul="300 es",vn=2e3,Ss=2001;function mu(i){for(let t=i.length-1;t>=0;--t)if(i[t]>=65535)return!0;return!1}function ys(i){return document.createElementNS("http://www.w3.org/1999/xhtml",i)}function gu(){const i=ys("canvas");return i.style.display="block",i}const dl={};function fl(...i){const t="THREE."+i.shift();console.log(t,...i)}function Wc(i){const t=i[0];if(typeof t=="string"&&t.startsWith("TSL:")){const e=i[1];e&&e.isStackTrace?i[0]+=" "+e.getLocation():i[1]='Stack trace not available. Enable "THREE.Node.captureStackTrace" to capture stack traces.'}return i}function Ut(...i){i=Wc(i);const t="THREE."+i.shift();{const e=i[0];e&&e.isStackTrace?console.warn(e.getError(t)):console.warn(t,...i)}}function ee(...i){i=Wc(i);const t="THREE."+i.shift();{const e=i[0];e&&e.isStackTrace?console.error(e.getError(t)):console.error(t,...i)}}function Hi(...i){const t=i.join(" ");t in dl||(dl[t]=!0,Ut(...i))}function _u(i,t,e){return new Promise(function(n,s){function r(){switch(i.clientWaitSync(t,i.SYNC_FLUSH_COMMANDS_BIT,0)){case i.WAIT_FAILED:s();break;case i.TIMEOUT_EXPIRED:setTimeout(r,e);break;default:n()}}setTimeout(r,e)})}const xu={[La]:Ia,[Na]:Oa,[Ua]:Ba,[Xi]:Fa,[Ia]:La,[Oa]:Na,[Ba]:Ua,[Fa]:Xi};class si{addEventListener(t,e){this._listeners===void 0&&(this._listeners={});const n=this._listeners;n[t]===void 0&&(n[t]=[]),n[t].indexOf(e)===-1&&n[t].push(e)}hasEventListener(t,e){const n=this._listeners;return n===void 0?!1:n[t]!==void 0&&n[t].indexOf(e)!==-1}removeEventListener(t,e){const n=this._listeners;if(n===void 0)return;const s=n[t];if(s!==void 0){const r=s.indexOf(e);r!==-1&&s.splice(r,1)}}dispatchEvent(t){const e=this._listeners;if(e===void 0)return;const n=e[t.type];if(n!==void 0){t.target=this;const s=n.slice(0);for(let r=0,a=s.length;r<a;r++)s[r].call(this,t);t.target=null}}}const Le=["00","01","02","03","04","05","06","07","08","09","0a","0b","0c","0d","0e","0f","10","11","12","13","14","15","16","17","18","19","1a","1b","1c","1d","1e","1f","20","21","22","23","24","25","26","27","28","29","2a","2b","2c","2d","2e","2f","30","31","32","33","34","35","36","37","38","39","3a","3b","3c","3d","3e","3f","40","41","42","43","44","45","46","47","48","49","4a","4b","4c","4d","4e","4f","50","51","52","53","54","55","56","57","58","59","5a","5b","5c","5d","5e","5f","60","61","62","63","64","65","66","67","68","69","6a","6b","6c","6d","6e","6f","70","71","72","73","74","75","76","77","78","79","7a","7b","7c","7d","7e","7f","80","81","82","83","84","85","86","87","88","89","8a","8b","8c","8d","8e","8f","90","91","92","93","94","95","96","97","98","99","9a","9b","9c","9d","9e","9f","a0","a1","a2","a3","a4","a5","a6","a7","a8","a9","aa","ab","ac","ad","ae","af","b0","b1","b2","b3","b4","b5","b6","b7","b8","b9","ba","bb","bc","bd","be","bf","c0","c1","c2","c3","c4","c5","c6","c7","c8","c9","ca","cb","cc","cd","ce","cf","d0","d1","d2","d3","d4","d5","d6","d7","d8","d9","da","db","dc","dd","de","df","e0","e1","e2","e3","e4","e5","e6","e7","e8","e9","ea","eb","ec","ed","ee","ef","f0","f1","f2","f3","f4","f5","f6","f7","f8","f9","fa","fb","fc","fd","fe","ff"],ps=Math.PI/180,go=180/Math.PI;function Ts(){const i=Math.random()*4294967295|0,t=Math.random()*4294967295|0,e=Math.random()*4294967295|0,n=Math.random()*4294967295|0;return(Le[i&255]+Le[i>>8&255]+Le[i>>16&255]+Le[i>>24&255]+"-"+Le[t&255]+Le[t>>8&255]+"-"+Le[t>>16&15|64]+Le[t>>24&255]+"-"+Le[e&63|128]+Le[e>>8&255]+"-"+Le[e>>16&255]+Le[e>>24&255]+Le[n&255]+Le[n>>8&255]+Le[n>>16&255]+Le[n>>24&255]).toLowerCase()}function jt(i,t,e){return Math.max(t,Math.min(e,i))}function vu(i,t){return(i%t+t)%t}function zr(i,t,e){return(1-e)*i+e*t}function Ji(i,t){switch(t.constructor){case Float32Array:return i;case Uint32Array:return i/4294967295;case Uint16Array:return i/65535;case Uint8Array:return i/255;case Int32Array:return Math.max(i/2147483647,-1);case Int16Array:return Math.max(i/32767,-1);case Int8Array:return Math.max(i/127,-1);default:throw new Error("THREE.MathUtils: Invalid component type.")}}function Oe(i,t){switch(t.constructor){case Float32Array:return i;case Uint32Array:return Math.round(i*4294967295);case Uint16Array:return Math.round(i*65535);case Uint8Array:return Math.round(i*255);case Int32Array:return Math.round(i*2147483647);case Int16Array:return Math.round(i*32767);case Int8Array:return Math.round(i*127);default:throw new Error("THREE.MathUtils: Invalid component type.")}}const Mu={DEG2RAD:ps},Xo=class Xo{constructor(t=0,e=0){this.x=t,this.y=e}get width(){return this.x}set width(t){this.x=t}get height(){return this.y}set height(t){this.y=t}set(t,e){return this.x=t,this.y=e,this}setScalar(t){return this.x=t,this.y=t,this}setX(t){return this.x=t,this}setY(t){return this.y=t,this}setComponent(t,e){switch(t){case 0:this.x=e;break;case 1:this.y=e;break;default:throw new Error("THREE.Vector2: index is out of range: "+t)}return this}getComponent(t){switch(t){case 0:return this.x;case 1:return this.y;default:throw new Error("THREE.Vector2: index is out of range: "+t)}}clone(){return new this.constructor(this.x,this.y)}copy(t){return this.x=t.x,this.y=t.y,this}add(t){return this.x+=t.x,this.y+=t.y,this}addScalar(t){return this.x+=t,this.y+=t,this}addVectors(t,e){return this.x=t.x+e.x,this.y=t.y+e.y,this}addScaledVector(t,e){return this.x+=t.x*e,this.y+=t.y*e,this}sub(t){return this.x-=t.x,this.y-=t.y,this}subScalar(t){return this.x-=t,this.y-=t,this}subVectors(t,e){return this.x=t.x-e.x,this.y=t.y-e.y,this}multiply(t){return this.x*=t.x,this.y*=t.y,this}multiplyScalar(t){return this.x*=t,this.y*=t,this}divide(t){return this.x/=t.x,this.y/=t.y,this}divideScalar(t){return this.multiplyScalar(1/t)}applyMatrix3(t){const e=this.x,n=this.y,s=t.elements;return this.x=s[0]*e+s[3]*n+s[6],this.y=s[1]*e+s[4]*n+s[7],this}min(t){return this.x=Math.min(this.x,t.x),this.y=Math.min(this.y,t.y),this}max(t){return this.x=Math.max(this.x,t.x),this.y=Math.max(this.y,t.y),this}clamp(t,e){return this.x=jt(this.x,t.x,e.x),this.y=jt(this.y,t.y,e.y),this}clampScalar(t,e){return this.x=jt(this.x,t,e),this.y=jt(this.y,t,e),this}clampLength(t,e){const n=this.length();return this.divideScalar(n||1).multiplyScalar(jt(n,t,e))}floor(){return this.x=Math.floor(this.x),this.y=Math.floor(this.y),this}ceil(){return this.x=Math.ceil(this.x),this.y=Math.ceil(this.y),this}round(){return this.x=Math.round(this.x),this.y=Math.round(this.y),this}roundToZero(){return this.x=Math.trunc(this.x),this.y=Math.trunc(this.y),this}negate(){return this.x=-this.x,this.y=-this.y,this}dot(t){return this.x*t.x+this.y*t.y}cross(t){return this.x*t.y-this.y*t.x}lengthSq(){return this.x*this.x+this.y*this.y}length(){return Math.sqrt(this.x*this.x+this.y*this.y)}manhattanLength(){return Math.abs(this.x)+Math.abs(this.y)}normalize(){return this.divideScalar(this.length()||1)}angle(){return Math.atan2(-this.y,-this.x)+Math.PI}angleTo(t){const e=Math.sqrt(this.lengthSq()*t.lengthSq());if(e===0)return Math.PI/2;const n=this.dot(t)/e;return Math.acos(jt(n,-1,1))}distanceTo(t){return Math.sqrt(this.distanceToSquared(t))}distanceToSquared(t){const e=this.x-t.x,n=this.y-t.y;return e*e+n*n}manhattanDistanceTo(t){return Math.abs(this.x-t.x)+Math.abs(this.y-t.y)}setLength(t){return this.normalize().multiplyScalar(t)}lerp(t,e){return this.x+=(t.x-this.x)*e,this.y+=(t.y-this.y)*e,this}lerpVectors(t,e,n){return this.x=t.x+(e.x-t.x)*n,this.y=t.y+(e.y-t.y)*n,this}equals(t){return t.x===this.x&&t.y===this.y}fromArray(t,e=0){return this.x=t[e],this.y=t[e+1],this}toArray(t=[],e=0){return t[e]=this.x,t[e+1]=this.y,t}fromBufferAttribute(t,e){return this.x=t.getX(e),this.y=t.getY(e),this}rotateAround(t,e){const n=Math.cos(e),s=Math.sin(e),r=this.x-t.x,a=this.y-t.y;return this.x=r*n-a*s+t.x,this.y=r*s+a*n+t.y,this}random(){return this.x=Math.random(),this.y=Math.random(),this}*[Symbol.iterator](){yield this.x,yield this.y}};Xo.prototype.isVector2=!0;let zt=Xo;class On{constructor(t=0,e=0,n=0,s=1){this.isQuaternion=!0,this._x=t,this._y=e,this._z=n,this._w=s}static slerpFlat(t,e,n,s,r,a,o){let c=n[s+0],l=n[s+1],h=n[s+2],d=n[s+3],u=r[a+0],p=r[a+1],g=r[a+2],v=r[a+3];if(d!==v||c!==u||l!==p||h!==g){let m=c*u+l*p+h*g+d*v;m<0&&(u=-u,p=-p,g=-g,v=-v,m=-m);let f=1-o;if(m<.9995){const E=Math.acos(m),w=Math.sin(E);f=Math.sin(f*E)/w,o=Math.sin(o*E)/w,c=c*f+u*o,l=l*f+p*o,h=h*f+g*o,d=d*f+v*o}else{c=c*f+u*o,l=l*f+p*o,h=h*f+g*o,d=d*f+v*o;const E=1/Math.sqrt(c*c+l*l+h*h+d*d);c*=E,l*=E,h*=E,d*=E}}t[e]=c,t[e+1]=l,t[e+2]=h,t[e+3]=d}static multiplyQuaternionsFlat(t,e,n,s,r,a){const o=n[s],c=n[s+1],l=n[s+2],h=n[s+3],d=r[a],u=r[a+1],p=r[a+2],g=r[a+3];return t[e]=o*g+h*d+c*p-l*u,t[e+1]=c*g+h*u+l*d-o*p,t[e+2]=l*g+h*p+o*u-c*d,t[e+3]=h*g-o*d-c*u-l*p,t}get x(){return this._x}set x(t){this._x=t,this._onChangeCallback()}get y(){return this._y}set y(t){this._y=t,this._onChangeCallback()}get z(){return this._z}set z(t){this._z=t,this._onChangeCallback()}get w(){return this._w}set w(t){this._w=t,this._onChangeCallback()}set(t,e,n,s){return this._x=t,this._y=e,this._z=n,this._w=s,this._onChangeCallback(),this}clone(){return new this.constructor(this._x,this._y,this._z,this._w)}copy(t){return this._x=t.x,this._y=t.y,this._z=t.z,this._w=t.w,this._onChangeCallback(),this}setFromEuler(t,e=!0){const n=t._x,s=t._y,r=t._z,a=t._order,o=Math.cos,c=Math.sin,l=o(n/2),h=o(s/2),d=o(r/2),u=c(n/2),p=c(s/2),g=c(r/2);switch(a){case"XYZ":this._x=u*h*d+l*p*g,this._y=l*p*d-u*h*g,this._z=l*h*g+u*p*d,this._w=l*h*d-u*p*g;break;case"YXZ":this._x=u*h*d+l*p*g,this._y=l*p*d-u*h*g,this._z=l*h*g-u*p*d,this._w=l*h*d+u*p*g;break;case"ZXY":this._x=u*h*d-l*p*g,this._y=l*p*d+u*h*g,this._z=l*h*g+u*p*d,this._w=l*h*d-u*p*g;break;case"ZYX":this._x=u*h*d-l*p*g,this._y=l*p*d+u*h*g,this._z=l*h*g-u*p*d,this._w=l*h*d+u*p*g;break;case"YZX":this._x=u*h*d+l*p*g,this._y=l*p*d+u*h*g,this._z=l*h*g-u*p*d,this._w=l*h*d-u*p*g;break;case"XZY":this._x=u*h*d-l*p*g,this._y=l*p*d-u*h*g,this._z=l*h*g+u*p*d,this._w=l*h*d+u*p*g;break;default:Ut("Quaternion: .setFromEuler() encountered an unknown order: "+a)}return e===!0&&this._onChangeCallback(),this}setFromAxisAngle(t,e){const n=e/2,s=Math.sin(n);return this._x=t.x*s,this._y=t.y*s,this._z=t.z*s,this._w=Math.cos(n),this._onChangeCallback(),this}setFromRotationMatrix(t){const e=t.elements,n=e[0],s=e[4],r=e[8],a=e[1],o=e[5],c=e[9],l=e[2],h=e[6],d=e[10],u=n+o+d;if(u>0){const p=.5/Math.sqrt(u+1);this._w=.25/p,this._x=(h-c)*p,this._y=(r-l)*p,this._z=(a-s)*p}else if(n>o&&n>d){const p=2*Math.sqrt(1+n-o-d);this._w=(h-c)/p,this._x=.25*p,this._y=(s+a)/p,this._z=(r+l)/p}else if(o>d){const p=2*Math.sqrt(1+o-n-d);this._w=(r-l)/p,this._x=(s+a)/p,this._y=.25*p,this._z=(c+h)/p}else{const p=2*Math.sqrt(1+d-n-o);this._w=(a-s)/p,this._x=(r+l)/p,this._y=(c+h)/p,this._z=.25*p}return this._onChangeCallback(),this}setFromUnitVectors(t,e){let n=t.dot(e)+1;return n<1e-8?(n=0,Math.abs(t.x)>Math.abs(t.z)?(this._x=-t.y,this._y=t.x,this._z=0,this._w=n):(this._x=0,this._y=-t.z,this._z=t.y,this._w=n)):(this._x=t.y*e.z-t.z*e.y,this._y=t.z*e.x-t.x*e.z,this._z=t.x*e.y-t.y*e.x,this._w=n),this.normalize()}angleTo(t){return 2*Math.acos(Math.abs(jt(this.dot(t),-1,1)))}rotateTowards(t,e){const n=this.angleTo(t);if(n===0)return this;const s=Math.min(1,e/n);return this.slerp(t,s),this}identity(){return this.set(0,0,0,1)}invert(){return this.conjugate()}conjugate(){return this._x*=-1,this._y*=-1,this._z*=-1,this._onChangeCallback(),this}dot(t){return this._x*t._x+this._y*t._y+this._z*t._z+this._w*t._w}lengthSq(){return this._x*this._x+this._y*this._y+this._z*this._z+this._w*this._w}length(){return Math.sqrt(this._x*this._x+this._y*this._y+this._z*this._z+this._w*this._w)}normalize(){let t=this.length();return t===0?(this._x=0,this._y=0,this._z=0,this._w=1):(t=1/t,this._x=this._x*t,this._y=this._y*t,this._z=this._z*t,this._w=this._w*t),this._onChangeCallback(),this}multiply(t){return this.multiplyQuaternions(this,t)}premultiply(t){return this.multiplyQuaternions(t,this)}multiplyQuaternions(t,e){const n=t._x,s=t._y,r=t._z,a=t._w,o=e._x,c=e._y,l=e._z,h=e._w;return this._x=n*h+a*o+s*l-r*c,this._y=s*h+a*c+r*o-n*l,this._z=r*h+a*l+n*c-s*o,this._w=a*h-n*o-s*c-r*l,this._onChangeCallback(),this}slerp(t,e){let n=t._x,s=t._y,r=t._z,a=t._w,o=this.dot(t);o<0&&(n=-n,s=-s,r=-r,a=-a,o=-o);let c=1-e;if(o<.9995){const l=Math.acos(o),h=Math.sin(l);c=Math.sin(c*l)/h,e=Math.sin(e*l)/h,this._x=this._x*c+n*e,this._y=this._y*c+s*e,this._z=this._z*c+r*e,this._w=this._w*c+a*e,this._onChangeCallback()}else this._x=this._x*c+n*e,this._y=this._y*c+s*e,this._z=this._z*c+r*e,this._w=this._w*c+a*e,this.normalize();return this}slerpQuaternions(t,e,n){return this.copy(t).slerp(e,n)}random(){const t=2*Math.PI*Math.random(),e=2*Math.PI*Math.random(),n=Math.random(),s=Math.sqrt(1-n),r=Math.sqrt(n);return this.set(s*Math.sin(t),s*Math.cos(t),r*Math.sin(e),r*Math.cos(e))}equals(t){return t._x===this._x&&t._y===this._y&&t._z===this._z&&t._w===this._w}fromArray(t,e=0){return this._x=t[e],this._y=t[e+1],this._z=t[e+2],this._w=t[e+3],this._onChangeCallback(),this}toArray(t=[],e=0){return t[e]=this._x,t[e+1]=this._y,t[e+2]=this._z,t[e+3]=this._w,t}fromBufferAttribute(t,e){return this._x=t.getX(e),this._y=t.getY(e),this._z=t.getZ(e),this._w=t.getW(e),this._onChangeCallback(),this}toJSON(){return this.toArray()}_onChange(t){return this._onChangeCallback=t,this}_onChangeCallback(){}*[Symbol.iterator](){yield this._x,yield this._y,yield this._z,yield this._w}}const qo=class qo{constructor(t=0,e=0,n=0){this.x=t,this.y=e,this.z=n}set(t,e,n){return n===void 0&&(n=this.z),this.x=t,this.y=e,this.z=n,this}setScalar(t){return this.x=t,this.y=t,this.z=t,this}setX(t){return this.x=t,this}setY(t){return this.y=t,this}setZ(t){return this.z=t,this}setComponent(t,e){switch(t){case 0:this.x=e;break;case 1:this.y=e;break;case 2:this.z=e;break;default:throw new Error("THREE.Vector3: index is out of range: "+t)}return this}getComponent(t){switch(t){case 0:return this.x;case 1:return this.y;case 2:return this.z;default:throw new Error("THREE.Vector3: index is out of range: "+t)}}clone(){return new this.constructor(this.x,this.y,this.z)}copy(t){return this.x=t.x,this.y=t.y,this.z=t.z,this}add(t){return this.x+=t.x,this.y+=t.y,this.z+=t.z,this}addScalar(t){return this.x+=t,this.y+=t,this.z+=t,this}addVectors(t,e){return this.x=t.x+e.x,this.y=t.y+e.y,this.z=t.z+e.z,this}addScaledVector(t,e){return this.x+=t.x*e,this.y+=t.y*e,this.z+=t.z*e,this}sub(t){return this.x-=t.x,this.y-=t.y,this.z-=t.z,this}subScalar(t){return this.x-=t,this.y-=t,this.z-=t,this}subVectors(t,e){return this.x=t.x-e.x,this.y=t.y-e.y,this.z=t.z-e.z,this}multiply(t){return this.x*=t.x,this.y*=t.y,this.z*=t.z,this}multiplyScalar(t){return this.x*=t,this.y*=t,this.z*=t,this}multiplyVectors(t,e){return this.x=t.x*e.x,this.y=t.y*e.y,this.z=t.z*e.z,this}applyEuler(t){return this.applyQuaternion(pl.setFromEuler(t))}applyAxisAngle(t,e){return this.applyQuaternion(pl.setFromAxisAngle(t,e))}applyMatrix3(t){const e=this.x,n=this.y,s=this.z,r=t.elements;return this.x=r[0]*e+r[3]*n+r[6]*s,this.y=r[1]*e+r[4]*n+r[7]*s,this.z=r[2]*e+r[5]*n+r[8]*s,this}applyNormalMatrix(t){return this.applyMatrix3(t).normalize()}applyMatrix4(t){const e=this.x,n=this.y,s=this.z,r=t.elements,a=1/(r[3]*e+r[7]*n+r[11]*s+r[15]);return this.x=(r[0]*e+r[4]*n+r[8]*s+r[12])*a,this.y=(r[1]*e+r[5]*n+r[9]*s+r[13])*a,this.z=(r[2]*e+r[6]*n+r[10]*s+r[14])*a,this}applyQuaternion(t){const e=this.x,n=this.y,s=this.z,r=t.x,a=t.y,o=t.z,c=t.w,l=2*(a*s-o*n),h=2*(o*e-r*s),d=2*(r*n-a*e);return this.x=e+c*l+a*d-o*h,this.y=n+c*h+o*l-r*d,this.z=s+c*d+r*h-a*l,this}project(t){return this.applyMatrix4(t.matrixWorldInverse).applyMatrix4(t.projectionMatrix)}unproject(t){return this.applyMatrix4(t.projectionMatrixInverse).applyMatrix4(t.matrixWorld)}transformDirection(t){const e=this.x,n=this.y,s=this.z,r=t.elements;return this.x=r[0]*e+r[4]*n+r[8]*s,this.y=r[1]*e+r[5]*n+r[9]*s,this.z=r[2]*e+r[6]*n+r[10]*s,this.normalize()}divide(t){return this.x/=t.x,this.y/=t.y,this.z/=t.z,this}divideScalar(t){return this.multiplyScalar(1/t)}min(t){return this.x=Math.min(this.x,t.x),this.y=Math.min(this.y,t.y),this.z=Math.min(this.z,t.z),this}max(t){return this.x=Math.max(this.x,t.x),this.y=Math.max(this.y,t.y),this.z=Math.max(this.z,t.z),this}clamp(t,e){return this.x=jt(this.x,t.x,e.x),this.y=jt(this.y,t.y,e.y),this.z=jt(this.z,t.z,e.z),this}clampScalar(t,e){return this.x=jt(this.x,t,e),this.y=jt(this.y,t,e),this.z=jt(this.z,t,e),this}clampLength(t,e){const n=this.length();return this.divideScalar(n||1).multiplyScalar(jt(n,t,e))}floor(){return this.x=Math.floor(this.x),this.y=Math.floor(this.y),this.z=Math.floor(this.z),this}ceil(){return this.x=Math.ceil(this.x),this.y=Math.ceil(this.y),this.z=Math.ceil(this.z),this}round(){return this.x=Math.round(this.x),this.y=Math.round(this.y),this.z=Math.round(this.z),this}roundToZero(){return this.x=Math.trunc(this.x),this.y=Math.trunc(this.y),this.z=Math.trunc(this.z),this}negate(){return this.x=-this.x,this.y=-this.y,this.z=-this.z,this}dot(t){return this.x*t.x+this.y*t.y+this.z*t.z}lengthSq(){return this.x*this.x+this.y*this.y+this.z*this.z}length(){return Math.sqrt(this.x*this.x+this.y*this.y+this.z*this.z)}manhattanLength(){return Math.abs(this.x)+Math.abs(this.y)+Math.abs(this.z)}normalize(){return this.divideScalar(this.length()||1)}setLength(t){return this.normalize().multiplyScalar(t)}lerp(t,e){return this.x+=(t.x-this.x)*e,this.y+=(t.y-this.y)*e,this.z+=(t.z-this.z)*e,this}lerpVectors(t,e,n){return this.x=t.x+(e.x-t.x)*n,this.y=t.y+(e.y-t.y)*n,this.z=t.z+(e.z-t.z)*n,this}cross(t){return this.crossVectors(this,t)}crossVectors(t,e){const n=t.x,s=t.y,r=t.z,a=e.x,o=e.y,c=e.z;return this.x=s*c-r*o,this.y=r*a-n*c,this.z=n*o-s*a,this}projectOnVector(t){const e=t.lengthSq();if(e===0)return this.set(0,0,0);const n=t.dot(this)/e;return this.copy(t).multiplyScalar(n)}projectOnPlane(t){return kr.copy(this).projectOnVector(t),this.sub(kr)}reflect(t){return this.sub(kr.copy(t).multiplyScalar(2*this.dot(t)))}angleTo(t){const e=Math.sqrt(this.lengthSq()*t.lengthSq());if(e===0)return Math.PI/2;const n=this.dot(t)/e;return Math.acos(jt(n,-1,1))}distanceTo(t){return Math.sqrt(this.distanceToSquared(t))}distanceToSquared(t){const e=this.x-t.x,n=this.y-t.y,s=this.z-t.z;return e*e+n*n+s*s}manhattanDistanceTo(t){return Math.abs(this.x-t.x)+Math.abs(this.y-t.y)+Math.abs(this.z-t.z)}setFromSpherical(t){return this.setFromSphericalCoords(t.radius,t.phi,t.theta)}setFromSphericalCoords(t,e,n){const s=Math.sin(e)*t;return this.x=s*Math.sin(n),this.y=Math.cos(e)*t,this.z=s*Math.cos(n),this}setFromCylindrical(t){return this.setFromCylindricalCoords(t.radius,t.theta,t.y)}setFromCylindricalCoords(t,e,n){return this.x=t*Math.sin(e),this.y=n,this.z=t*Math.cos(e),this}setFromMatrixPosition(t){const e=t.elements;return this.x=e[12],this.y=e[13],this.z=e[14],this}setFromMatrixScale(t){const e=this.setFromMatrixColumn(t,0).length(),n=this.setFromMatrixColumn(t,1).length(),s=this.setFromMatrixColumn(t,2).length();return this.x=e,this.y=n,this.z=s,this}setFromMatrixColumn(t,e){return this.fromArray(t.elements,e*4)}setFromMatrix3Column(t,e){return this.fromArray(t.elements,e*3)}setFromEuler(t){return this.x=t._x,this.y=t._y,this.z=t._z,this}setFromColor(t){return this.x=t.r,this.y=t.g,this.z=t.b,this}equals(t){return t.x===this.x&&t.y===this.y&&t.z===this.z}fromArray(t,e=0){return this.x=t[e],this.y=t[e+1],this.z=t[e+2],this}toArray(t=[],e=0){return t[e]=this.x,t[e+1]=this.y,t[e+2]=this.z,t}fromBufferAttribute(t,e){return this.x=t.getX(e),this.y=t.getY(e),this.z=t.getZ(e),this}random(){return this.x=Math.random(),this.y=Math.random(),this.z=Math.random(),this}randomDirection(){const t=Math.random()*Math.PI*2,e=Math.random()*2-1,n=Math.sqrt(1-e*e);return this.x=n*Math.cos(t),this.y=e,this.z=n*Math.sin(t),this}*[Symbol.iterator](){yield this.x,yield this.y,yield this.z}};qo.prototype.isVector3=!0;let I=qo;const kr=new I,pl=new On,Yo=class Yo{constructor(t,e,n,s,r,a,o,c,l){this.elements=[1,0,0,0,1,0,0,0,1],t!==void 0&&this.set(t,e,n,s,r,a,o,c,l)}set(t,e,n,s,r,a,o,c,l){const h=this.elements;return h[0]=t,h[1]=s,h[2]=o,h[3]=e,h[4]=r,h[5]=c,h[6]=n,h[7]=a,h[8]=l,this}identity(){return this.set(1,0,0,0,1,0,0,0,1),this}copy(t){const e=this.elements,n=t.elements;return e[0]=n[0],e[1]=n[1],e[2]=n[2],e[3]=n[3],e[4]=n[4],e[5]=n[5],e[6]=n[6],e[7]=n[7],e[8]=n[8],this}extractBasis(t,e,n){return t.setFromMatrix3Column(this,0),e.setFromMatrix3Column(this,1),n.setFromMatrix3Column(this,2),this}setFromMatrix4(t){const e=t.elements;return this.set(e[0],e[4],e[8],e[1],e[5],e[9],e[2],e[6],e[10]),this}multiply(t){return this.multiplyMatrices(this,t)}premultiply(t){return this.multiplyMatrices(t,this)}multiplyMatrices(t,e){const n=t.elements,s=e.elements,r=this.elements,a=n[0],o=n[3],c=n[6],l=n[1],h=n[4],d=n[7],u=n[2],p=n[5],g=n[8],v=s[0],m=s[3],f=s[6],E=s[1],w=s[4],y=s[7],b=s[2],M=s[5],A=s[8];return r[0]=a*v+o*E+c*b,r[3]=a*m+o*w+c*M,r[6]=a*f+o*y+c*A,r[1]=l*v+h*E+d*b,r[4]=l*m+h*w+d*M,r[7]=l*f+h*y+d*A,r[2]=u*v+p*E+g*b,r[5]=u*m+p*w+g*M,r[8]=u*f+p*y+g*A,this}multiplyScalar(t){const e=this.elements;return e[0]*=t,e[3]*=t,e[6]*=t,e[1]*=t,e[4]*=t,e[7]*=t,e[2]*=t,e[5]*=t,e[8]*=t,this}determinant(){const t=this.elements,e=t[0],n=t[1],s=t[2],r=t[3],a=t[4],o=t[5],c=t[6],l=t[7],h=t[8];return e*a*h-e*o*l-n*r*h+n*o*c+s*r*l-s*a*c}invert(){const t=this.elements,e=t[0],n=t[1],s=t[2],r=t[3],a=t[4],o=t[5],c=t[6],l=t[7],h=t[8],d=h*a-o*l,u=o*c-h*r,p=l*r-a*c,g=e*d+n*u+s*p;if(g===0)return this.set(0,0,0,0,0,0,0,0,0);const v=1/g;return t[0]=d*v,t[1]=(s*l-h*n)*v,t[2]=(o*n-s*a)*v,t[3]=u*v,t[4]=(h*e-s*c)*v,t[5]=(s*r-o*e)*v,t[6]=p*v,t[7]=(n*c-l*e)*v,t[8]=(a*e-n*r)*v,this}transpose(){let t;const e=this.elements;return t=e[1],e[1]=e[3],e[3]=t,t=e[2],e[2]=e[6],e[6]=t,t=e[5],e[5]=e[7],e[7]=t,this}getNormalMatrix(t){return this.setFromMatrix4(t).invert().transpose()}transposeIntoArray(t){const e=this.elements;return t[0]=e[0],t[1]=e[3],t[2]=e[6],t[3]=e[1],t[4]=e[4],t[5]=e[7],t[6]=e[2],t[7]=e[5],t[8]=e[8],this}setUvTransform(t,e,n,s,r,a,o){const c=Math.cos(r),l=Math.sin(r);return this.set(n*c,n*l,-n*(c*a+l*o)+a+t,-s*l,s*c,-s*(-l*a+c*o)+o+e,0,0,1),this}scale(t,e){return Hi("Matrix3: .scale() is deprecated. Use .makeScale() instead."),this.premultiply(Vr.makeScale(t,e)),this}rotate(t){return Hi("Matrix3: .rotate() is deprecated. Use .makeRotation() instead."),this.premultiply(Vr.makeRotation(-t)),this}translate(t,e){return Hi("Matrix3: .translate() is deprecated. Use .makeTranslation() instead."),this.premultiply(Vr.makeTranslation(t,e)),this}makeTranslation(t,e){return t.isVector2?this.set(1,0,t.x,0,1,t.y,0,0,1):this.set(1,0,t,0,1,e,0,0,1),this}makeRotation(t){const e=Math.cos(t),n=Math.sin(t);return this.set(e,-n,0,n,e,0,0,0,1),this}makeScale(t,e){return this.set(t,0,0,0,e,0,0,0,1),this}equals(t){const e=this.elements,n=t.elements;for(let s=0;s<9;s++)if(e[s]!==n[s])return!1;return!0}fromArray(t,e=0){for(let n=0;n<9;n++)this.elements[n]=t[n+e];return this}toArray(t=[],e=0){const n=this.elements;return t[e]=n[0],t[e+1]=n[1],t[e+2]=n[2],t[e+3]=n[3],t[e+4]=n[4],t[e+5]=n[5],t[e+6]=n[6],t[e+7]=n[7],t[e+8]=n[8],t}clone(){return new this.constructor().fromArray(this.elements)}};Yo.prototype.isMatrix3=!0;let Ht=Yo;const Vr=new Ht,ml=new Ht().set(.4123908,.3575843,.1804808,.212639,.7151687,.0721923,.0193308,.1191948,.9505322),gl=new Ht().set(3.2409699,-1.5373832,-.4986108,-.9692436,1.8759675,.0415551,.0556301,-.203977,1.0569715);function Su(){const i={enabled:!0,workingColorSpace:yr,spaces:{},convert:function(s,r,a){return this.enabled===!1||r===a||!r||!a||(this.spaces[r].transfer===re&&(s.r=Nn(s.r),s.g=Nn(s.g),s.b=Nn(s.b)),this.spaces[r].primaries!==this.spaces[a].primaries&&(s.applyMatrix3(this.spaces[r].toXYZ),s.applyMatrix3(this.spaces[a].fromXYZ)),this.spaces[a].transfer===re&&(s.r=Wi(s.r),s.g=Wi(s.g),s.b=Wi(s.b))),s},workingToColorSpace:function(s,r){return this.convert(s,this.workingColorSpace,r)},colorSpaceToWorking:function(s,r){return this.convert(s,r,this.workingColorSpace)},getPrimaries:function(s){return this.spaces[s].primaries},getTransfer:function(s){return s===ti?br:this.spaces[s].transfer},getToneMappingMode:function(s){return this.spaces[s].outputColorSpaceConfig.toneMappingMode||"standard"},getLuminanceCoefficients:function(s,r=this.workingColorSpace){return s.fromArray(this.spaces[r].luminanceCoefficients)},define:function(s){Object.assign(this.spaces,s)},_getMatrix:function(s,r,a){return s.copy(this.spaces[r].toXYZ).multiply(this.spaces[a].fromXYZ)},_getDrawingBufferColorSpace:function(s){return this.spaces[s].outputColorSpaceConfig.drawingBufferColorSpace},_getUnpackColorSpace:function(s=this.workingColorSpace){return this.spaces[s].workingColorSpaceConfig.unpackColorSpace},fromWorkingColorSpace:function(s,r){return Hi("ColorManagement: .fromWorkingColorSpace() has been renamed to .workingToColorSpace()."),i.workingToColorSpace(s,r)},toWorkingColorSpace:function(s,r){return Hi("ColorManagement: .toWorkingColorSpace() has been renamed to .colorSpaceToWorking()."),i.colorSpaceToWorking(s,r)}},t=[.64,.33,.3,.6,.15,.06],e=[.2126,.7152,.0722],n=[.3127,.329];return i.define({[yr]:{primaries:t,whitePoint:n,transfer:br,toXYZ:ml,fromXYZ:gl,luminanceCoefficients:e,workingColorSpaceConfig:{unpackColorSpace:Fe},outputColorSpaceConfig:{drawingBufferColorSpace:Fe}},[Fe]:{primaries:t,whitePoint:n,transfer:re,toXYZ:ml,fromXYZ:gl,luminanceCoefficients:e,outputColorSpaceConfig:{drawingBufferColorSpace:Fe}}}),i}const Qt=Su();function Nn(i){return i<.04045?i*.0773993808:Math.pow(i*.9478672986+.0521327014,2.4)}function Wi(i){return i<.0031308?i*12.92:1.055*Math.pow(i,.41666)-.055}let bi;class yu{static getDataURL(t,e="image/png"){if(/^data:/i.test(t.src)||typeof HTMLCanvasElement>"u")return t.src;let n;if(t instanceof HTMLCanvasElement)n=t;else{bi===void 0&&(bi=ys("canvas")),bi.width=t.width,bi.height=t.height;const s=bi.getContext("2d");t instanceof ImageData?s.putImageData(t,0,0):s.drawImage(t,0,0,t.width,t.height),n=bi}return n.toDataURL(e)}static sRGBToLinear(t){if(typeof HTMLImageElement<"u"&&t instanceof HTMLImageElement||typeof HTMLCanvasElement<"u"&&t instanceof HTMLCanvasElement||typeof ImageBitmap<"u"&&t instanceof ImageBitmap){const e=ys("canvas");e.width=t.width,e.height=t.height;const n=e.getContext("2d");n.drawImage(t,0,0,t.width,t.height);const s=n.getImageData(0,0,t.width,t.height),r=s.data;for(let a=0;a<r.length;a++)r[a]=Nn(r[a]/255)*255;return n.putImageData(s,0,0),e}else if(t.data){const e=t.data.slice(0);for(let n=0;n<e.length;n++)e instanceof Uint8Array||e instanceof Uint8ClampedArray?e[n]=Math.floor(Nn(e[n]/255)*255):e[n]=Nn(e[n]);return{data:e,width:t.width,height:t.height}}else return Ut("ImageUtils.sRGBToLinear(): Unsupported image type. No color space conversion applied."),t}}let bu=0;class Io{constructor(t=null){this.isSource=!0,Object.defineProperty(this,"id",{value:bu++}),this.uuid=Ts(),this.data=t,this.dataReady=!0,this.version=0}getSize(t){const e=this.data;return typeof HTMLVideoElement<"u"&&e instanceof HTMLVideoElement?t.set(e.videoWidth,e.videoHeight,0):typeof VideoFrame<"u"&&e instanceof VideoFrame?t.set(e.displayWidth,e.displayHeight,0):e!==null?t.set(e.width,e.height,e.depth||0):t.set(0,0,0),t}set needsUpdate(t){t===!0&&this.version++}toJSON(t){const e=t===void 0||typeof t=="string";if(!e&&t.images[this.uuid]!==void 0)return t.images[this.uuid];const n={uuid:this.uuid,url:""},s=this.data;if(s!==null){let r;if(Array.isArray(s)){r=[];for(let a=0,o=s.length;a<o;a++)s[a].isDataTexture?r.push(Gr(s[a].image)):r.push(Gr(s[a]))}else r=Gr(s);n.url=r}return e||(t.images[this.uuid]=n),n}}function Gr(i){return typeof HTMLImageElement<"u"&&i instanceof HTMLImageElement||typeof HTMLCanvasElement<"u"&&i instanceof HTMLCanvasElement||typeof ImageBitmap<"u"&&i instanceof ImageBitmap?yu.getDataURL(i):i.data?{data:Array.from(i.data),width:i.width,height:i.height,type:i.data.constructor.name}:(Ut("Texture: Unable to serialize Texture."),{})}let Eu=0;const Hr=new I;class Ne extends si{constructor(t=Ne.DEFAULT_IMAGE,e=Ne.DEFAULT_MAPPING,n=ln,s=ln,r=ye,a=Ln,o=hn,c=qe,l=Ne.DEFAULT_ANISOTROPY,h=ti){super(),this.isTexture=!0,Object.defineProperty(this,"id",{value:Eu++}),this.uuid=Ts(),this.name="",this.source=new Io(t),this.mipmaps=[],this.mapping=e,this.channel=0,this.wrapS=n,this.wrapT=s,this.magFilter=r,this.minFilter=a,this.anisotropy=l,this.format=o,this.internalFormat=null,this.type=c,this.offset=new zt(0,0),this.repeat=new zt(1,1),this.center=new zt(0,0),this.rotation=0,this.matrixAutoUpdate=!0,this.matrix=new Ht,this.generateMipmaps=!0,this.premultiplyAlpha=!1,this.flipY=!0,this.unpackAlignment=4,this.colorSpace=h,this.userData={},this.updateRanges=[],this.version=0,this.onUpdate=null,this.renderTarget=null,this.isRenderTargetTexture=!1,this.isArrayTexture=!!(t&&t.depth&&t.depth>1),this.pmremVersion=0,this.normalized=!1}get width(){return this.source.getSize(Hr).x}get height(){return this.source.getSize(Hr).y}get depth(){return this.source.getSize(Hr).z}get image(){return this.source.data}set image(t){this.source.data=t}updateMatrix(){this.matrix.setUvTransform(this.offset.x,this.offset.y,this.repeat.x,this.repeat.y,this.rotation,this.center.x,this.center.y)}addUpdateRange(t,e){this.updateRanges.push({start:t,count:e})}clearUpdateRanges(){this.updateRanges.length=0}clone(){return new this.constructor().copy(this)}copy(t){return this.name=t.name,this.source=t.source,this.mipmaps=t.mipmaps.slice(0),this.mapping=t.mapping,this.channel=t.channel,this.wrapS=t.wrapS,this.wrapT=t.wrapT,this.magFilter=t.magFilter,this.minFilter=t.minFilter,this.anisotropy=t.anisotropy,this.format=t.format,this.internalFormat=t.internalFormat,this.type=t.type,this.normalized=t.normalized,this.offset.copy(t.offset),this.repeat.copy(t.repeat),this.center.copy(t.center),this.rotation=t.rotation,this.matrixAutoUpdate=t.matrixAutoUpdate,this.matrix.copy(t.matrix),this.generateMipmaps=t.generateMipmaps,this.premultiplyAlpha=t.premultiplyAlpha,this.flipY=t.flipY,this.unpackAlignment=t.unpackAlignment,this.colorSpace=t.colorSpace,this.renderTarget=t.renderTarget,this.isRenderTargetTexture=t.isRenderTargetTexture,this.isArrayTexture=t.isArrayTexture,this.userData=JSON.parse(JSON.stringify(t.userData)),this.needsUpdate=!0,this}setValues(t){for(const e in t){const n=t[e];if(n===void 0){Ut(`Texture.setValues(): parameter '${e}' has value of undefined.`);continue}const s=this[e];if(s===void 0){Ut(`Texture.setValues(): property '${e}' does not exist.`);continue}s&&n&&s.isVector2&&n.isVector2||s&&n&&s.isVector3&&n.isVector3||s&&n&&s.isMatrix3&&n.isMatrix3?s.copy(n):this[e]=n}}toJSON(t){const e=t===void 0||typeof t=="string";if(!e&&t.textures[this.uuid]!==void 0)return t.textures[this.uuid];const n={metadata:{version:4.7,type:"Texture",generator:"Texture.toJSON"},uuid:this.uuid,name:this.name,image:this.source.toJSON(t).uuid,mapping:this.mapping,channel:this.channel,repeat:[this.repeat.x,this.repeat.y],offset:[this.offset.x,this.offset.y],center:[this.center.x,this.center.y],rotation:this.rotation,wrap:[this.wrapS,this.wrapT],format:this.format,internalFormat:this.internalFormat,type:this.type,normalized:this.normalized,colorSpace:this.colorSpace,minFilter:this.minFilter,magFilter:this.magFilter,anisotropy:this.anisotropy,flipY:this.flipY,generateMipmaps:this.generateMipmaps,premultiplyAlpha:this.premultiplyAlpha,unpackAlignment:this.unpackAlignment};return Object.keys(this.userData).length>0&&(n.userData=this.userData),e||(t.textures[this.uuid]=n),n}dispose(){this.dispatchEvent({type:"dispose"})}transformUv(t){if(this.mapping!==Oc)return t;if(t.applyMatrix3(this.matrix),t.x<0||t.x>1)switch(this.wrapS){case pi:t.x=t.x-Math.floor(t.x);break;case ln:t.x=t.x<0?0:1;break;case xs:Math.abs(Math.floor(t.x)%2)===1?t.x=Math.ceil(t.x)-t.x:t.x=t.x-Math.floor(t.x);break}if(t.y<0||t.y>1)switch(this.wrapT){case pi:t.y=t.y-Math.floor(t.y);break;case ln:t.y=t.y<0?0:1;break;case xs:Math.abs(Math.floor(t.y)%2)===1?t.y=Math.ceil(t.y)-t.y:t.y=t.y-Math.floor(t.y);break}return this.flipY&&(t.y=1-t.y),t}set needsUpdate(t){t===!0&&(this.version++,this.source.needsUpdate=!0)}set needsPMREMUpdate(t){t===!0&&this.pmremVersion++}}Ne.DEFAULT_IMAGE=null;Ne.DEFAULT_MAPPING=Oc;Ne.DEFAULT_ANISOTROPY=1;const $o=class $o{constructor(t=0,e=0,n=0,s=1){this.x=t,this.y=e,this.z=n,this.w=s}get width(){return this.z}set width(t){this.z=t}get height(){return this.w}set height(t){this.w=t}set(t,e,n,s){return this.x=t,this.y=e,this.z=n,this.w=s,this}setScalar(t){return this.x=t,this.y=t,this.z=t,this.w=t,this}setX(t){return this.x=t,this}setY(t){return this.y=t,this}setZ(t){return this.z=t,this}setW(t){return this.w=t,this}setComponent(t,e){switch(t){case 0:this.x=e;break;case 1:this.y=e;break;case 2:this.z=e;break;case 3:this.w=e;break;default:throw new Error("THREE.Vector4: index is out of range: "+t)}return this}getComponent(t){switch(t){case 0:return this.x;case 1:return this.y;case 2:return this.z;case 3:return this.w;default:throw new Error("THREE.Vector4: index is out of range: "+t)}}clone(){return new this.constructor(this.x,this.y,this.z,this.w)}copy(t){return this.x=t.x,this.y=t.y,this.z=t.z,this.w=t.w!==void 0?t.w:1,this}add(t){return this.x+=t.x,this.y+=t.y,this.z+=t.z,this.w+=t.w,this}addScalar(t){return this.x+=t,this.y+=t,this.z+=t,this.w+=t,this}addVectors(t,e){return this.x=t.x+e.x,this.y=t.y+e.y,this.z=t.z+e.z,this.w=t.w+e.w,this}addScaledVector(t,e){return this.x+=t.x*e,this.y+=t.y*e,this.z+=t.z*e,this.w+=t.w*e,this}sub(t){return this.x-=t.x,this.y-=t.y,this.z-=t.z,this.w-=t.w,this}subScalar(t){return this.x-=t,this.y-=t,this.z-=t,this.w-=t,this}subVectors(t,e){return this.x=t.x-e.x,this.y=t.y-e.y,this.z=t.z-e.z,this.w=t.w-e.w,this}multiply(t){return this.x*=t.x,this.y*=t.y,this.z*=t.z,this.w*=t.w,this}multiplyScalar(t){return this.x*=t,this.y*=t,this.z*=t,this.w*=t,this}applyMatrix4(t){const e=this.x,n=this.y,s=this.z,r=this.w,a=t.elements;return this.x=a[0]*e+a[4]*n+a[8]*s+a[12]*r,this.y=a[1]*e+a[5]*n+a[9]*s+a[13]*r,this.z=a[2]*e+a[6]*n+a[10]*s+a[14]*r,this.w=a[3]*e+a[7]*n+a[11]*s+a[15]*r,this}divide(t){return this.x/=t.x,this.y/=t.y,this.z/=t.z,this.w/=t.w,this}divideScalar(t){return this.multiplyScalar(1/t)}setAxisAngleFromQuaternion(t){this.w=2*Math.acos(t.w);const e=Math.sqrt(1-t.w*t.w);return e<1e-4?(this.x=1,this.y=0,this.z=0):(this.x=t.x/e,this.y=t.y/e,this.z=t.z/e),this}setAxisAngleFromRotationMatrix(t){let e,n,s,r;const c=t.elements,l=c[0],h=c[4],d=c[8],u=c[1],p=c[5],g=c[9],v=c[2],m=c[6],f=c[10];if(Math.abs(h-u)<.01&&Math.abs(d-v)<.01&&Math.abs(g-m)<.01){if(Math.abs(h+u)<.1&&Math.abs(d+v)<.1&&Math.abs(g+m)<.1&&Math.abs(l+p+f-3)<.1)return this.set(1,0,0,0),this;e=Math.PI;const w=(l+1)/2,y=(p+1)/2,b=(f+1)/2,M=(h+u)/4,A=(d+v)/4,x=(g+m)/4;return w>y&&w>b?w<.01?(n=0,s=.707106781,r=.707106781):(n=Math.sqrt(w),s=M/n,r=A/n):y>b?y<.01?(n=.707106781,s=0,r=.707106781):(s=Math.sqrt(y),n=M/s,r=x/s):b<.01?(n=.707106781,s=.707106781,r=0):(r=Math.sqrt(b),n=A/r,s=x/r),this.set(n,s,r,e),this}let E=Math.sqrt((m-g)*(m-g)+(d-v)*(d-v)+(u-h)*(u-h));return Math.abs(E)<.001&&(E=1),this.x=(m-g)/E,this.y=(d-v)/E,this.z=(u-h)/E,this.w=Math.acos((l+p+f-1)/2),this}setFromMatrixPosition(t){const e=t.elements;return this.x=e[12],this.y=e[13],this.z=e[14],this.w=e[15],this}min(t){return this.x=Math.min(this.x,t.x),this.y=Math.min(this.y,t.y),this.z=Math.min(this.z,t.z),this.w=Math.min(this.w,t.w),this}max(t){return this.x=Math.max(this.x,t.x),this.y=Math.max(this.y,t.y),this.z=Math.max(this.z,t.z),this.w=Math.max(this.w,t.w),this}clamp(t,e){return this.x=jt(this.x,t.x,e.x),this.y=jt(this.y,t.y,e.y),this.z=jt(this.z,t.z,e.z),this.w=jt(this.w,t.w,e.w),this}clampScalar(t,e){return this.x=jt(this.x,t,e),this.y=jt(this.y,t,e),this.z=jt(this.z,t,e),this.w=jt(this.w,t,e),this}clampLength(t,e){const n=this.length();return this.divideScalar(n||1).multiplyScalar(jt(n,t,e))}floor(){return this.x=Math.floor(this.x),this.y=Math.floor(this.y),this.z=Math.floor(this.z),this.w=Math.floor(this.w),this}ceil(){return this.x=Math.ceil(this.x),this.y=Math.ceil(this.y),this.z=Math.ceil(this.z),this.w=Math.ceil(this.w),this}round(){return this.x=Math.round(this.x),this.y=Math.round(this.y),this.z=Math.round(this.z),this.w=Math.round(this.w),this}roundToZero(){return this.x=Math.trunc(this.x),this.y=Math.trunc(this.y),this.z=Math.trunc(this.z),this.w=Math.trunc(this.w),this}negate(){return this.x=-this.x,this.y=-this.y,this.z=-this.z,this.w=-this.w,this}dot(t){return this.x*t.x+this.y*t.y+this.z*t.z+this.w*t.w}lengthSq(){return this.x*this.x+this.y*this.y+this.z*this.z+this.w*this.w}length(){return Math.sqrt(this.x*this.x+this.y*this.y+this.z*this.z+this.w*this.w)}manhattanLength(){return Math.abs(this.x)+Math.abs(this.y)+Math.abs(this.z)+Math.abs(this.w)}normalize(){return this.divideScalar(this.length()||1)}setLength(t){return this.normalize().multiplyScalar(t)}lerp(t,e){return this.x+=(t.x-this.x)*e,this.y+=(t.y-this.y)*e,this.z+=(t.z-this.z)*e,this.w+=(t.w-this.w)*e,this}lerpVectors(t,e,n){return this.x=t.x+(e.x-t.x)*n,this.y=t.y+(e.y-t.y)*n,this.z=t.z+(e.z-t.z)*n,this.w=t.w+(e.w-t.w)*n,this}equals(t){return t.x===this.x&&t.y===this.y&&t.z===this.z&&t.w===this.w}fromArray(t,e=0){return this.x=t[e],this.y=t[e+1],this.z=t[e+2],this.w=t[e+3],this}toArray(t=[],e=0){return t[e]=this.x,t[e+1]=this.y,t[e+2]=this.z,t[e+3]=this.w,t}fromBufferAttribute(t,e){return this.x=t.getX(e),this.y=t.getY(e),this.z=t.getZ(e),this.w=t.getW(e),this}random(){return this.x=Math.random(),this.y=Math.random(),this.z=Math.random(),this.w=Math.random(),this}*[Symbol.iterator](){yield this.x,yield this.y,yield this.z,yield this.w}};$o.prototype.isVector4=!0;let pe=$o;class Tu extends si{constructor(t=1,e=1,n={}){super(),n=Object.assign({generateMipmaps:!1,internalFormat:null,minFilter:ye,depthBuffer:!0,stencilBuffer:!1,resolveDepthBuffer:!0,resolveStencilBuffer:!0,depthTexture:null,samples:0,count:1,depth:1,multiview:!1,useArrayDepthTexture:!1},n),this.isRenderTarget=!0,this.width=t,this.height=e,this.depth=n.depth,this.scissor=new pe(0,0,t,e),this.scissorTest=!1,this.viewport=new pe(0,0,t,e),this.textures=[];const s={width:t,height:e,depth:n.depth},r=new Ne(s),a=n.count;for(let o=0;o<a;o++)this.textures[o]=r.clone(),this.textures[o].isRenderTargetTexture=!0,this.textures[o].renderTarget=this;this._setTextureOptions(n),this.depthBuffer=n.depthBuffer,this.stencilBuffer=n.stencilBuffer,this.resolveDepthBuffer=n.resolveDepthBuffer,this.resolveStencilBuffer=n.resolveStencilBuffer,this._depthTexture=null,this.depthTexture=n.depthTexture,this.samples=n.samples,this.multiview=n.multiview,this.useArrayDepthTexture=n.useArrayDepthTexture}_setTextureOptions(t={}){const e={minFilter:ye,generateMipmaps:!1,flipY:!1,internalFormat:null};t.mapping!==void 0&&(e.mapping=t.mapping),t.wrapS!==void 0&&(e.wrapS=t.wrapS),t.wrapT!==void 0&&(e.wrapT=t.wrapT),t.wrapR!==void 0&&(e.wrapR=t.wrapR),t.magFilter!==void 0&&(e.magFilter=t.magFilter),t.minFilter!==void 0&&(e.minFilter=t.minFilter),t.format!==void 0&&(e.format=t.format),t.type!==void 0&&(e.type=t.type),t.anisotropy!==void 0&&(e.anisotropy=t.anisotropy),t.colorSpace!==void 0&&(e.colorSpace=t.colorSpace),t.flipY!==void 0&&(e.flipY=t.flipY),t.generateMipmaps!==void 0&&(e.generateMipmaps=t.generateMipmaps),t.internalFormat!==void 0&&(e.internalFormat=t.internalFormat);for(let n=0;n<this.textures.length;n++)this.textures[n].setValues(e)}get texture(){return this.textures[0]}set texture(t){this.textures[0]=t}set depthTexture(t){this._depthTexture!==null&&(this._depthTexture.renderTarget=null),t!==null&&(t.renderTarget=this),this._depthTexture=t}get depthTexture(){return this._depthTexture}setSize(t,e,n=1){if(this.width!==t||this.height!==e||this.depth!==n){this.width=t,this.height=e,this.depth=n;for(let s=0,r=this.textures.length;s<r;s++)this.textures[s].image.width=t,this.textures[s].image.height=e,this.textures[s].image.depth=n,this.textures[s].isData3DTexture!==!0&&(this.textures[s].isArrayTexture=this.textures[s].image.depth>1);this.dispose()}this.viewport.set(0,0,t,e),this.scissor.set(0,0,t,e)}clone(){return new this.constructor().copy(this)}copy(t){this.width=t.width,this.height=t.height,this.depth=t.depth,this.scissor.copy(t.scissor),this.scissorTest=t.scissorTest,this.viewport.copy(t.viewport),this.textures.length=0;for(let e=0,n=t.textures.length;e<n;e++){this.textures[e]=t.textures[e].clone(),this.textures[e].isRenderTargetTexture=!0,this.textures[e].renderTarget=this;const s=Object.assign({},t.textures[e].image);this.textures[e].source=new Io(s)}return this.depthBuffer=t.depthBuffer,this.stencilBuffer=t.stencilBuffer,this.resolveDepthBuffer=t.resolveDepthBuffer,this.resolveStencilBuffer=t.resolveStencilBuffer,t.depthTexture!==null&&(this.depthTexture=t.depthTexture.clone()),this.samples=t.samples,this.multiview=t.multiview,this.useArrayDepthTexture=t.useArrayDepthTexture,this}dispose(){this.dispatchEvent({type:"dispose"})}}class Sn extends Tu{constructor(t=1,e=1,n={}){super(t,e,n),this.isWebGLRenderTarget=!0}}class Xc extends Ne{constructor(t=null,e=1,n=1,s=1){super(null),this.isDataArrayTexture=!0,this.image={data:t,width:e,height:n,depth:s},this.magFilter=Ee,this.minFilter=Ee,this.wrapR=ln,this.generateMipmaps=!1,this.flipY=!1,this.unpackAlignment=1,this.layerUpdates=new Set}addLayerUpdate(t){this.layerUpdates.add(t)}clearLayerUpdates(){this.layerUpdates.clear()}}class Au extends Ne{constructor(t=null,e=1,n=1,s=1){super(null),this.isData3DTexture=!0,this.image={data:t,width:e,height:n,depth:s},this.magFilter=Ee,this.minFilter=Ee,this.wrapR=ln,this.generateMipmaps=!1,this.flipY=!1,this.unpackAlignment=1}}const Cr=class Cr{constructor(t,e,n,s,r,a,o,c,l,h,d,u,p,g,v,m){this.elements=[1,0,0,0,0,1,0,0,0,0,1,0,0,0,0,1],t!==void 0&&this.set(t,e,n,s,r,a,o,c,l,h,d,u,p,g,v,m)}set(t,e,n,s,r,a,o,c,l,h,d,u,p,g,v,m){const f=this.elements;return f[0]=t,f[4]=e,f[8]=n,f[12]=s,f[1]=r,f[5]=a,f[9]=o,f[13]=c,f[2]=l,f[6]=h,f[10]=d,f[14]=u,f[3]=p,f[7]=g,f[11]=v,f[15]=m,this}identity(){return this.set(1,0,0,0,0,1,0,0,0,0,1,0,0,0,0,1),this}clone(){return new Cr().fromArray(this.elements)}copy(t){const e=this.elements,n=t.elements;return e[0]=n[0],e[1]=n[1],e[2]=n[2],e[3]=n[3],e[4]=n[4],e[5]=n[5],e[6]=n[6],e[7]=n[7],e[8]=n[8],e[9]=n[9],e[10]=n[10],e[11]=n[11],e[12]=n[12],e[13]=n[13],e[14]=n[14],e[15]=n[15],this}copyPosition(t){const e=this.elements,n=t.elements;return e[12]=n[12],e[13]=n[13],e[14]=n[14],this}setFromMatrix3(t){const e=t.elements;return this.set(e[0],e[3],e[6],0,e[1],e[4],e[7],0,e[2],e[5],e[8],0,0,0,0,1),this}extractBasis(t,e,n){return this.determinantAffine()===0?(t.set(1,0,0),e.set(0,1,0),n.set(0,0,1),this):(t.setFromMatrixColumn(this,0),e.setFromMatrixColumn(this,1),n.setFromMatrixColumn(this,2),this)}makeBasis(t,e,n){return this.set(t.x,e.x,n.x,0,t.y,e.y,n.y,0,t.z,e.z,n.z,0,0,0,0,1),this}extractRotation(t){if(t.determinantAffine()===0)return this.identity();const e=this.elements,n=t.elements,s=1/Ei.setFromMatrixColumn(t,0).length(),r=1/Ei.setFromMatrixColumn(t,1).length(),a=1/Ei.setFromMatrixColumn(t,2).length();return e[0]=n[0]*s,e[1]=n[1]*s,e[2]=n[2]*s,e[3]=0,e[4]=n[4]*r,e[5]=n[5]*r,e[6]=n[6]*r,e[7]=0,e[8]=n[8]*a,e[9]=n[9]*a,e[10]=n[10]*a,e[11]=0,e[12]=0,e[13]=0,e[14]=0,e[15]=1,this}makeRotationFromEuler(t){const e=this.elements,n=t.x,s=t.y,r=t.z,a=Math.cos(n),o=Math.sin(n),c=Math.cos(s),l=Math.sin(s),h=Math.cos(r),d=Math.sin(r);if(t.order==="XYZ"){const u=a*h,p=a*d,g=o*h,v=o*d;e[0]=c*h,e[4]=-c*d,e[8]=l,e[1]=p+g*l,e[5]=u-v*l,e[9]=-o*c,e[2]=v-u*l,e[6]=g+p*l,e[10]=a*c}else if(t.order==="YXZ"){const u=c*h,p=c*d,g=l*h,v=l*d;e[0]=u+v*o,e[4]=g*o-p,e[8]=a*l,e[1]=a*d,e[5]=a*h,e[9]=-o,e[2]=p*o-g,e[6]=v+u*o,e[10]=a*c}else if(t.order==="ZXY"){const u=c*h,p=c*d,g=l*h,v=l*d;e[0]=u-v*o,e[4]=-a*d,e[8]=g+p*o,e[1]=p+g*o,e[5]=a*h,e[9]=v-u*o,e[2]=-a*l,e[6]=o,e[10]=a*c}else if(t.order==="ZYX"){const u=a*h,p=a*d,g=o*h,v=o*d;e[0]=c*h,e[4]=g*l-p,e[8]=u*l+v,e[1]=c*d,e[5]=v*l+u,e[9]=p*l-g,e[2]=-l,e[6]=o*c,e[10]=a*c}else if(t.order==="YZX"){const u=a*c,p=a*l,g=o*c,v=o*l;e[0]=c*h,e[4]=v-u*d,e[8]=g*d+p,e[1]=d,e[5]=a*h,e[9]=-o*h,e[2]=-l*h,e[6]=p*d+g,e[10]=u-v*d}else if(t.order==="XZY"){const u=a*c,p=a*l,g=o*c,v=o*l;e[0]=c*h,e[4]=-d,e[8]=l*h,e[1]=u*d+v,e[5]=a*h,e[9]=p*d-g,e[2]=g*d-p,e[6]=o*h,e[10]=v*d+u}return e[3]=0,e[7]=0,e[11]=0,e[12]=0,e[13]=0,e[14]=0,e[15]=1,this}makeRotationFromQuaternion(t){return this.compose(wu,t,Ru)}lookAt(t,e,n){const s=this.elements;return Ge.subVectors(t,e),Ge.lengthSq()===0&&(Ge.z=1),Ge.normalize(),Hn.crossVectors(n,Ge),Hn.lengthSq()===0&&(Math.abs(n.z)===1?Ge.x+=1e-4:Ge.z+=1e-4,Ge.normalize(),Hn.crossVectors(n,Ge)),Hn.normalize(),Ls.crossVectors(Ge,Hn),s[0]=Hn.x,s[4]=Ls.x,s[8]=Ge.x,s[1]=Hn.y,s[5]=Ls.y,s[9]=Ge.y,s[2]=Hn.z,s[6]=Ls.z,s[10]=Ge.z,this}multiply(t){return this.multiplyMatrices(this,t)}premultiply(t){return this.multiplyMatrices(t,this)}multiplyMatrices(t,e){const n=t.elements,s=e.elements,r=this.elements,a=n[0],o=n[4],c=n[8],l=n[12],h=n[1],d=n[5],u=n[9],p=n[13],g=n[2],v=n[6],m=n[10],f=n[14],E=n[3],w=n[7],y=n[11],b=n[15],M=s[0],A=s[4],x=s[8],T=s[12],N=s[1],D=s[5],B=s[9],$=s[13],J=s[2],z=s[6],H=s[10],W=s[14],tt=s[3],Q=s[7],ht=s[11],ut=s[15];return r[0]=a*M+o*N+c*J+l*tt,r[4]=a*A+o*D+c*z+l*Q,r[8]=a*x+o*B+c*H+l*ht,r[12]=a*T+o*$+c*W+l*ut,r[1]=h*M+d*N+u*J+p*tt,r[5]=h*A+d*D+u*z+p*Q,r[9]=h*x+d*B+u*H+p*ht,r[13]=h*T+d*$+u*W+p*ut,r[2]=g*M+v*N+m*J+f*tt,r[6]=g*A+v*D+m*z+f*Q,r[10]=g*x+v*B+m*H+f*ht,r[14]=g*T+v*$+m*W+f*ut,r[3]=E*M+w*N+y*J+b*tt,r[7]=E*A+w*D+y*z+b*Q,r[11]=E*x+w*B+y*H+b*ht,r[15]=E*T+w*$+y*W+b*ut,this}multiplyScalar(t){const e=this.elements;return e[0]*=t,e[4]*=t,e[8]*=t,e[12]*=t,e[1]*=t,e[5]*=t,e[9]*=t,e[13]*=t,e[2]*=t,e[6]*=t,e[10]*=t,e[14]*=t,e[3]*=t,e[7]*=t,e[11]*=t,e[15]*=t,this}determinant(){const t=this.elements,e=t[0],n=t[4],s=t[8],r=t[12],a=t[1],o=t[5],c=t[9],l=t[13],h=t[2],d=t[6],u=t[10],p=t[14],g=t[3],v=t[7],m=t[11],f=t[15],E=c*p-l*u,w=o*p-l*d,y=o*u-c*d,b=a*p-l*h,M=a*u-c*h,A=a*d-o*h;return e*(v*E-m*w+f*y)-n*(g*E-m*b+f*M)+s*(g*w-v*b+f*A)-r*(g*y-v*M+m*A)}determinantAffine(){const t=this.elements,e=t[0],n=t[4],s=t[8],r=t[1],a=t[5],o=t[9],c=t[2],l=t[6],h=t[10];return e*(a*h-o*l)-n*(r*h-o*c)+s*(r*l-a*c)}transpose(){const t=this.elements;let e;return e=t[1],t[1]=t[4],t[4]=e,e=t[2],t[2]=t[8],t[8]=e,e=t[6],t[6]=t[9],t[9]=e,e=t[3],t[3]=t[12],t[12]=e,e=t[7],t[7]=t[13],t[13]=e,e=t[11],t[11]=t[14],t[14]=e,this}setPosition(t,e,n){const s=this.elements;return t.isVector3?(s[12]=t.x,s[13]=t.y,s[14]=t.z):(s[12]=t,s[13]=e,s[14]=n),this}invert(){const t=this.elements,e=t[0],n=t[1],s=t[2],r=t[3],a=t[4],o=t[5],c=t[6],l=t[7],h=t[8],d=t[9],u=t[10],p=t[11],g=t[12],v=t[13],m=t[14],f=t[15],E=e*o-n*a,w=e*c-s*a,y=e*l-r*a,b=n*c-s*o,M=n*l-r*o,A=s*l-r*c,x=h*v-d*g,T=h*m-u*g,N=h*f-p*g,D=d*m-u*v,B=d*f-p*v,$=u*f-p*m,J=E*$-w*B+y*D+b*N-M*T+A*x;if(J===0)return this.set(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);const z=1/J;return t[0]=(o*$-c*B+l*D)*z,t[1]=(s*B-n*$-r*D)*z,t[2]=(v*A-m*M+f*b)*z,t[3]=(u*M-d*A-p*b)*z,t[4]=(c*N-a*$-l*T)*z,t[5]=(e*$-s*N+r*T)*z,t[6]=(m*y-g*A-f*w)*z,t[7]=(h*A-u*y+p*w)*z,t[8]=(a*B-o*N+l*x)*z,t[9]=(n*N-e*B-r*x)*z,t[10]=(g*M-v*y+f*E)*z,t[11]=(d*y-h*M-p*E)*z,t[12]=(o*T-a*D-c*x)*z,t[13]=(e*D-n*T+s*x)*z,t[14]=(v*w-g*b-m*E)*z,t[15]=(h*b-d*w+u*E)*z,this}scale(t){const e=this.elements,n=t.x,s=t.y,r=t.z;return e[0]*=n,e[4]*=s,e[8]*=r,e[1]*=n,e[5]*=s,e[9]*=r,e[2]*=n,e[6]*=s,e[10]*=r,e[3]*=n,e[7]*=s,e[11]*=r,this}getMaxScaleOnAxis(){const t=this.elements,e=t[0]*t[0]+t[1]*t[1]+t[2]*t[2],n=t[4]*t[4]+t[5]*t[5]+t[6]*t[6],s=t[8]*t[8]+t[9]*t[9]+t[10]*t[10];return Math.sqrt(Math.max(e,n,s))}makeTranslation(t,e,n){return t.isVector3?this.set(1,0,0,t.x,0,1,0,t.y,0,0,1,t.z,0,0,0,1):this.set(1,0,0,t,0,1,0,e,0,0,1,n,0,0,0,1),this}makeRotationX(t){const e=Math.cos(t),n=Math.sin(t);return this.set(1,0,0,0,0,e,-n,0,0,n,e,0,0,0,0,1),this}makeRotationY(t){const e=Math.cos(t),n=Math.sin(t);return this.set(e,0,n,0,0,1,0,0,-n,0,e,0,0,0,0,1),this}makeRotationZ(t){const e=Math.cos(t),n=Math.sin(t);return this.set(e,-n,0,0,n,e,0,0,0,0,1,0,0,0,0,1),this}makeRotationAxis(t,e){const n=Math.cos(e),s=Math.sin(e),r=1-n,a=t.x,o=t.y,c=t.z,l=r*a,h=r*o;return this.set(l*a+n,l*o-s*c,l*c+s*o,0,l*o+s*c,h*o+n,h*c-s*a,0,l*c-s*o,h*c+s*a,r*c*c+n,0,0,0,0,1),this}makeScale(t,e,n){return this.set(t,0,0,0,0,e,0,0,0,0,n,0,0,0,0,1),this}makeShear(t,e,n,s,r,a){return this.set(1,n,r,0,t,1,a,0,e,s,1,0,0,0,0,1),this}compose(t,e,n){const s=this.elements,r=e._x,a=e._y,o=e._z,c=e._w,l=r+r,h=a+a,d=o+o,u=r*l,p=r*h,g=r*d,v=a*h,m=a*d,f=o*d,E=c*l,w=c*h,y=c*d,b=n.x,M=n.y,A=n.z;return s[0]=(1-(v+f))*b,s[1]=(p+y)*b,s[2]=(g-w)*b,s[3]=0,s[4]=(p-y)*M,s[5]=(1-(u+f))*M,s[6]=(m+E)*M,s[7]=0,s[8]=(g+w)*A,s[9]=(m-E)*A,s[10]=(1-(u+v))*A,s[11]=0,s[12]=t.x,s[13]=t.y,s[14]=t.z,s[15]=1,this}decompose(t,e,n){const s=this.elements;t.x=s[12],t.y=s[13],t.z=s[14];const r=this.determinantAffine();if(r===0)return n.set(1,1,1),e.identity(),this;let a=Ei.set(s[0],s[1],s[2]).length();const o=Ei.set(s[4],s[5],s[6]).length(),c=Ei.set(s[8],s[9],s[10]).length();r<0&&(a=-a),nn.copy(this);const l=1/a,h=1/o,d=1/c;return nn.elements[0]*=l,nn.elements[1]*=l,nn.elements[2]*=l,nn.elements[4]*=h,nn.elements[5]*=h,nn.elements[6]*=h,nn.elements[8]*=d,nn.elements[9]*=d,nn.elements[10]*=d,e.setFromRotationMatrix(nn),n.x=a,n.y=o,n.z=c,this}makePerspective(t,e,n,s,r,a,o=vn,c=!1){const l=this.elements,h=2*r/(e-t),d=2*r/(n-s),u=(e+t)/(e-t),p=(n+s)/(n-s);let g,v;if(c)g=r/(a-r),v=a*r/(a-r);else if(o===vn)g=-(a+r)/(a-r),v=-2*a*r/(a-r);else if(o===Ss)g=-a/(a-r),v=-a*r/(a-r);else throw new Error("THREE.Matrix4.makePerspective(): Invalid coordinate system: "+o);return l[0]=h,l[4]=0,l[8]=u,l[12]=0,l[1]=0,l[5]=d,l[9]=p,l[13]=0,l[2]=0,l[6]=0,l[10]=g,l[14]=v,l[3]=0,l[7]=0,l[11]=-1,l[15]=0,this}makeOrthographic(t,e,n,s,r,a,o=vn,c=!1){const l=this.elements,h=2/(e-t),d=2/(n-s),u=-(e+t)/(e-t),p=-(n+s)/(n-s);let g,v;if(c)g=1/(a-r),v=a/(a-r);else if(o===vn)g=-2/(a-r),v=-(a+r)/(a-r);else if(o===Ss)g=-1/(a-r),v=-r/(a-r);else throw new Error("THREE.Matrix4.makeOrthographic(): Invalid coordinate system: "+o);return l[0]=h,l[4]=0,l[8]=0,l[12]=u,l[1]=0,l[5]=d,l[9]=0,l[13]=p,l[2]=0,l[6]=0,l[10]=g,l[14]=v,l[3]=0,l[7]=0,l[11]=0,l[15]=1,this}equals(t){const e=this.elements,n=t.elements;for(let s=0;s<16;s++)if(e[s]!==n[s])return!1;return!0}fromArray(t,e=0){for(let n=0;n<16;n++)this.elements[n]=t[n+e];return this}toArray(t=[],e=0){const n=this.elements;return t[e]=n[0],t[e+1]=n[1],t[e+2]=n[2],t[e+3]=n[3],t[e+4]=n[4],t[e+5]=n[5],t[e+6]=n[6],t[e+7]=n[7],t[e+8]=n[8],t[e+9]=n[9],t[e+10]=n[10],t[e+11]=n[11],t[e+12]=n[12],t[e+13]=n[13],t[e+14]=n[14],t[e+15]=n[15],t}};Cr.prototype.isMatrix4=!0;let se=Cr;const Ei=new I,nn=new se,wu=new I(0,0,0),Ru=new I(1,1,1),Hn=new I,Ls=new I,Ge=new I,_l=new se,xl=new On;class Bn{constructor(t=0,e=0,n=0,s=Bn.DEFAULT_ORDER){this.isEuler=!0,this._x=t,this._y=e,this._z=n,this._order=s}get x(){return this._x}set x(t){this._x=t,this._onChangeCallback()}get y(){return this._y}set y(t){this._y=t,this._onChangeCallback()}get z(){return this._z}set z(t){this._z=t,this._onChangeCallback()}get order(){return this._order}set order(t){this._order=t,this._onChangeCallback()}set(t,e,n,s=this._order){return this._x=t,this._y=e,this._z=n,this._order=s,this._onChangeCallback(),this}clone(){return new this.constructor(this._x,this._y,this._z,this._order)}copy(t){return this._x=t._x,this._y=t._y,this._z=t._z,this._order=t._order,this._onChangeCallback(),this}setFromRotationMatrix(t,e=this._order,n=!0){const s=t.elements,r=s[0],a=s[4],o=s[8],c=s[1],l=s[5],h=s[9],d=s[2],u=s[6],p=s[10];switch(e){case"XYZ":this._y=Math.asin(jt(o,-1,1)),Math.abs(o)<.9999999?(this._x=Math.atan2(-h,p),this._z=Math.atan2(-a,r)):(this._x=Math.atan2(u,l),this._z=0);break;case"YXZ":this._x=Math.asin(-jt(h,-1,1)),Math.abs(h)<.9999999?(this._y=Math.atan2(o,p),this._z=Math.atan2(c,l)):(this._y=Math.atan2(-d,r),this._z=0);break;case"ZXY":this._x=Math.asin(jt(u,-1,1)),Math.abs(u)<.9999999?(this._y=Math.atan2(-d,p),this._z=Math.atan2(-a,l)):(this._y=0,this._z=Math.atan2(c,r));break;case"ZYX":this._y=Math.asin(-jt(d,-1,1)),Math.abs(d)<.9999999?(this._x=Math.atan2(u,p),this._z=Math.atan2(c,r)):(this._x=0,this._z=Math.atan2(-a,l));break;case"YZX":this._z=Math.asin(jt(c,-1,1)),Math.abs(c)<.9999999?(this._x=Math.atan2(-h,l),this._y=Math.atan2(-d,r)):(this._x=0,this._y=Math.atan2(o,p));break;case"XZY":this._z=Math.asin(-jt(a,-1,1)),Math.abs(a)<.9999999?(this._x=Math.atan2(u,l),this._y=Math.atan2(o,r)):(this._x=Math.atan2(-h,p),this._y=0);break;default:Ut("Euler: .setFromRotationMatrix() encountered an unknown order: "+e)}return this._order=e,n===!0&&this._onChangeCallback(),this}setFromQuaternion(t,e,n){return _l.makeRotationFromQuaternion(t),this.setFromRotationMatrix(_l,e,n)}setFromVector3(t,e=this._order){return this.set(t.x,t.y,t.z,e)}reorder(t){return xl.setFromEuler(this),this.setFromQuaternion(xl,t)}equals(t){return t._x===this._x&&t._y===this._y&&t._z===this._z&&t._order===this._order}fromArray(t){return this._x=t[0],this._y=t[1],this._z=t[2],t[3]!==void 0&&(this._order=t[3]),this._onChangeCallback(),this}toArray(t=[],e=0){return t[e]=this._x,t[e+1]=this._y,t[e+2]=this._z,t[e+3]=this._order,t}_onChange(t){return this._onChangeCallback=t,this}_onChangeCallback(){}*[Symbol.iterator](){yield this._x,yield this._y,yield this._z,yield this._order}}Bn.DEFAULT_ORDER="XYZ";class No{constructor(){this.mask=1}set(t){this.mask=(1<<t|0)>>>0}enable(t){this.mask|=1<<t|0}enableAll(){this.mask=-1}toggle(t){this.mask^=1<<t|0}disable(t){this.mask&=~(1<<t|0)}disableAll(){this.mask=0}test(t){return(this.mask&t.mask)!==0}isEnabled(t){return(this.mask&(1<<t|0))!==0}}let Cu=0;const vl=new I,Ti=new On,Tn=new se,Is=new I,ji=new I,Pu=new I,Du=new On,Ml=new I(1,0,0),Sl=new I(0,1,0),yl=new I(0,0,1),bl={type:"added"},Lu={type:"removed"},Ai={type:"childadded",child:null},Wr={type:"childremoved",child:null};class Te extends si{constructor(){super(),this.isObject3D=!0,Object.defineProperty(this,"id",{value:Cu++}),this.uuid=Ts(),this.name="",this.type="Object3D",this.parent=null,this.children=[],this.up=Te.DEFAULT_UP.clone();const t=new I,e=new Bn,n=new On,s=new I(1,1,1);function r(){n.setFromEuler(e,!1)}function a(){e.setFromQuaternion(n,void 0,!1)}e._onChange(r),n._onChange(a),Object.defineProperties(this,{position:{configurable:!0,enumerable:!0,value:t},rotation:{configurable:!0,enumerable:!0,value:e},quaternion:{configurable:!0,enumerable:!0,value:n},scale:{configurable:!0,enumerable:!0,value:s},modelViewMatrix:{value:new se},normalMatrix:{value:new Ht}}),this.matrix=new se,this.matrixWorld=new se,this.matrixAutoUpdate=Te.DEFAULT_MATRIX_AUTO_UPDATE,this.matrixWorldAutoUpdate=Te.DEFAULT_MATRIX_WORLD_AUTO_UPDATE,this.matrixWorldNeedsUpdate=!1,this.layers=new No,this.visible=!0,this.castShadow=!1,this.receiveShadow=!1,this.frustumCulled=!0,this.renderOrder=0,this.animations=[],this.customDepthMaterial=void 0,this.customDistanceMaterial=void 0,this.static=!1,this.userData={},this.pivot=null}onBeforeShadow(){}onAfterShadow(){}onBeforeRender(){}onAfterRender(){}applyMatrix4(t){this.matrixAutoUpdate&&this.updateMatrix(),this.matrix.premultiply(t),this.matrix.decompose(this.position,this.quaternion,this.scale)}applyQuaternion(t){return this.quaternion.premultiply(t),this}setRotationFromAxisAngle(t,e){this.quaternion.setFromAxisAngle(t,e)}setRotationFromEuler(t){this.quaternion.setFromEuler(t,!0)}setRotationFromMatrix(t){this.quaternion.setFromRotationMatrix(t)}setRotationFromQuaternion(t){this.quaternion.copy(t)}rotateOnAxis(t,e){return Ti.setFromAxisAngle(t,e),this.quaternion.multiply(Ti),this}rotateOnWorldAxis(t,e){return Ti.setFromAxisAngle(t,e),this.quaternion.premultiply(Ti),this}rotateX(t){return this.rotateOnAxis(Ml,t)}rotateY(t){return this.rotateOnAxis(Sl,t)}rotateZ(t){return this.rotateOnAxis(yl,t)}translateOnAxis(t,e){return vl.copy(t).applyQuaternion(this.quaternion),this.position.add(vl.multiplyScalar(e)),this}translateX(t){return this.translateOnAxis(Ml,t)}translateY(t){return this.translateOnAxis(Sl,t)}translateZ(t){return this.translateOnAxis(yl,t)}localToWorld(t){return this.updateWorldMatrix(!0,!1),t.applyMatrix4(this.matrixWorld)}worldToLocal(t){return this.updateWorldMatrix(!0,!1),t.applyMatrix4(Tn.copy(this.matrixWorld).invert())}lookAt(t,e,n){t.isVector3?Is.copy(t):Is.set(t,e,n);const s=this.parent;this.updateWorldMatrix(!0,!1),ji.setFromMatrixPosition(this.matrixWorld),this.isCamera||this.isLight?Tn.lookAt(ji,Is,this.up):Tn.lookAt(Is,ji,this.up),this.quaternion.setFromRotationMatrix(Tn),s&&(Tn.extractRotation(s.matrixWorld),Ti.setFromRotationMatrix(Tn),this.quaternion.premultiply(Ti.invert()))}add(t){if(arguments.length>1){for(let e=0;e<arguments.length;e++)this.add(arguments[e]);return this}return t===this?(ee("Object3D.add: object can't be added as a child of itself.",t),this):(t&&t.isObject3D?(t.removeFromParent(),t.parent=this,this.children.push(t),t.dispatchEvent(bl),Ai.child=t,this.dispatchEvent(Ai),Ai.child=null):ee("Object3D.add: object not an instance of THREE.Object3D.",t),this)}remove(t){if(arguments.length>1){for(let n=0;n<arguments.length;n++)this.remove(arguments[n]);return this}const e=this.children.indexOf(t);return e!==-1&&(t.parent=null,this.children.splice(e,1),t.dispatchEvent(Lu),Wr.child=t,this.dispatchEvent(Wr),Wr.child=null),this}removeFromParent(){const t=this.parent;return t!==null&&t.remove(this),this}clear(){return this.remove(...this.children)}attach(t){return this.updateWorldMatrix(!0,!1),Tn.copy(this.matrixWorld).invert(),t.parent!==null&&(t.parent.updateWorldMatrix(!0,!1),Tn.multiply(t.parent.matrixWorld)),t.applyMatrix4(Tn),t.removeFromParent(),t.parent=this,this.children.push(t),t.updateWorldMatrix(!1,!0),t.dispatchEvent(bl),Ai.child=t,this.dispatchEvent(Ai),Ai.child=null,this}getObjectById(t){return this.getObjectByProperty("id",t)}getObjectByName(t){return this.getObjectByProperty("name",t)}getObjectByProperty(t,e){if(this[t]===e)return this;for(let n=0,s=this.children.length;n<s;n++){const a=this.children[n].getObjectByProperty(t,e);if(a!==void 0)return a}}getObjectsByProperty(t,e,n=[]){this[t]===e&&n.push(this);const s=this.children;for(let r=0,a=s.length;r<a;r++)s[r].getObjectsByProperty(t,e,n);return n}getWorldPosition(t){return this.updateWorldMatrix(!0,!1),t.setFromMatrixPosition(this.matrixWorld)}getWorldQuaternion(t){return this.updateWorldMatrix(!0,!1),this.matrixWorld.decompose(ji,t,Pu),t}getWorldScale(t){return this.updateWorldMatrix(!0,!1),this.matrixWorld.decompose(ji,Du,t),t}getWorldDirection(t){this.updateWorldMatrix(!0,!1);const e=this.matrixWorld.elements;return t.set(e[8],e[9],e[10]).normalize()}raycast(){}traverse(t){t(this);const e=this.children;for(let n=0,s=e.length;n<s;n++)e[n].traverse(t)}traverseVisible(t){if(this.visible===!1)return;t(this);const e=this.children;for(let n=0,s=e.length;n<s;n++)e[n].traverseVisible(t)}traverseAncestors(t){const e=this.parent;e!==null&&(t(e),e.traverseAncestors(t))}updateMatrix(){this.matrix.compose(this.position,this.quaternion,this.scale);const t=this.pivot;if(t!==null){const e=t.x,n=t.y,s=t.z,r=this.matrix.elements;r[12]+=e-r[0]*e-r[4]*n-r[8]*s,r[13]+=n-r[1]*e-r[5]*n-r[9]*s,r[14]+=s-r[2]*e-r[6]*n-r[10]*s}this.matrixWorldNeedsUpdate=!0}updateMatrixWorld(t){this.matrixAutoUpdate&&this.updateMatrix(),(this.matrixWorldNeedsUpdate||t)&&(this.matrixWorldAutoUpdate===!0&&(this.parent===null?this.matrixWorld.copy(this.matrix):this.matrixWorld.multiplyMatrices(this.parent.matrixWorld,this.matrix)),this.matrixWorldNeedsUpdate=!1,t=!0);const e=this.children;for(let n=0,s=e.length;n<s;n++)e[n].updateMatrixWorld(t)}updateWorldMatrix(t,e,n=!1){const s=this.parent;if(t===!0&&s!==null&&s.updateWorldMatrix(!0,!1),this.matrixAutoUpdate&&this.updateMatrix(),(this.matrixWorldNeedsUpdate||n)&&(this.matrixWorldAutoUpdate===!0&&(this.parent===null?this.matrixWorld.copy(this.matrix):this.matrixWorld.multiplyMatrices(this.parent.matrixWorld,this.matrix)),this.matrixWorldNeedsUpdate=!1,n=!0),e===!0){const r=this.children;for(let a=0,o=r.length;a<o;a++)r[a].updateWorldMatrix(!1,!0,n)}}toJSON(t){const e=t===void 0||typeof t=="string",n={};e&&(t={geometries:{},materials:{},textures:{},images:{},shapes:{},skeletons:{},animations:{},nodes:{}},n.metadata={version:4.7,type:"Object",generator:"Object3D.toJSON"});const s={};s.uuid=this.uuid,s.type=this.type,this.name!==""&&(s.name=this.name),this.castShadow===!0&&(s.castShadow=!0),this.receiveShadow===!0&&(s.receiveShadow=!0),this.visible===!1&&(s.visible=!1),this.frustumCulled===!1&&(s.frustumCulled=!1),this.renderOrder!==0&&(s.renderOrder=this.renderOrder),this.static!==!1&&(s.static=this.static),Object.keys(this.userData).length>0&&(s.userData=this.userData),s.layers=this.layers.mask,s.matrix=this.matrix.toArray(),s.up=this.up.toArray(),this.pivot!==null&&(s.pivot=this.pivot.toArray()),this.matrixAutoUpdate===!1&&(s.matrixAutoUpdate=!1),this.morphTargetDictionary!==void 0&&(s.morphTargetDictionary=Object.assign({},this.morphTargetDictionary)),this.morphTargetInfluences!==void 0&&(s.morphTargetInfluences=this.morphTargetInfluences.slice()),this.isInstancedMesh&&(s.type="InstancedMesh",s.count=this.count,s.instanceMatrix=this.instanceMatrix.toJSON(),this.instanceColor!==null&&(s.instanceColor=this.instanceColor.toJSON())),this.isBatchedMesh&&(s.type="BatchedMesh",s.perObjectFrustumCulled=this.perObjectFrustumCulled,s.sortObjects=this.sortObjects,s.drawRanges=this._drawRanges,s.reservedRanges=this._reservedRanges,s.geometryInfo=this._geometryInfo.map(o=>({...o,boundingBox:o.boundingBox?o.boundingBox.toJSON():void 0,boundingSphere:o.boundingSphere?o.boundingSphere.toJSON():void 0})),s.instanceInfo=this._instanceInfo.map(o=>({...o})),s.availableInstanceIds=this._availableInstanceIds.slice(),s.availableGeometryIds=this._availableGeometryIds.slice(),s.nextIndexStart=this._nextIndexStart,s.nextVertexStart=this._nextVertexStart,s.geometryCount=this._geometryCount,s.maxInstanceCount=this._maxInstanceCount,s.maxVertexCount=this._maxVertexCount,s.maxIndexCount=this._maxIndexCount,s.geometryInitialized=this._geometryInitialized,s.matricesTexture=this._matricesTexture.toJSON(t),s.indirectTexture=this._indirectTexture.toJSON(t),this._colorsTexture!==null&&(s.colorsTexture=this._colorsTexture.toJSON(t)),this.boundingSphere!==null&&(s.boundingSphere=this.boundingSphere.toJSON()),this.boundingBox!==null&&(s.boundingBox=this.boundingBox.toJSON()));function r(o,c){return o[c.uuid]===void 0&&(o[c.uuid]=c.toJSON(t)),c.uuid}if(this.isScene)this.background&&(this.background.isColor?s.background=this.background.toJSON():this.background.isTexture&&(s.background=this.background.toJSON(t).uuid)),this.environment&&this.environment.isTexture&&this.environment.isRenderTargetTexture!==!0&&(s.environment=this.environment.toJSON(t).uuid);else if(this.isMesh||this.isLine||this.isPoints){s.geometry=r(t.geometries,this.geometry);const o=this.geometry.parameters;if(o!==void 0&&o.shapes!==void 0){const c=o.shapes;if(Array.isArray(c))for(let l=0,h=c.length;l<h;l++){const d=c[l];r(t.shapes,d)}else r(t.shapes,c)}}if(this.isSkinnedMesh&&(s.bindMode=this.bindMode,s.bindMatrix=this.bindMatrix.toArray(),this.skeleton!==void 0&&(r(t.skeletons,this.skeleton),s.skeleton=this.skeleton.uuid)),this.material!==void 0)if(Array.isArray(this.material)){const o=[];for(let c=0,l=this.material.length;c<l;c++)o.push(r(t.materials,this.material[c]));s.material=o}else s.material=r(t.materials,this.material);if(this.children.length>0){s.children=[];for(let o=0;o<this.children.length;o++)s.children.push(this.children[o].toJSON(t).object)}if(this.animations.length>0){s.animations=[];for(let o=0;o<this.animations.length;o++){const c=this.animations[o];s.animations.push(r(t.animations,c))}}if(e){const o=a(t.geometries),c=a(t.materials),l=a(t.textures),h=a(t.images),d=a(t.shapes),u=a(t.skeletons),p=a(t.animations),g=a(t.nodes);o.length>0&&(n.geometries=o),c.length>0&&(n.materials=c),l.length>0&&(n.textures=l),h.length>0&&(n.images=h),d.length>0&&(n.shapes=d),u.length>0&&(n.skeletons=u),p.length>0&&(n.animations=p),g.length>0&&(n.nodes=g)}return n.object=s,n;function a(o){const c=[];for(const l in o){const h=o[l];delete h.metadata,c.push(h)}return c}}clone(t){return new this.constructor().copy(this,t)}copy(t,e=!0){if(this.name=t.name,this.up.copy(t.up),this.position.copy(t.position),this.rotation.order=t.rotation.order,this.quaternion.copy(t.quaternion),this.scale.copy(t.scale),this.pivot=t.pivot!==null?t.pivot.clone():null,this.matrix.copy(t.matrix),this.matrixWorld.copy(t.matrixWorld),this.matrixAutoUpdate=t.matrixAutoUpdate,this.matrixWorldAutoUpdate=t.matrixWorldAutoUpdate,this.matrixWorldNeedsUpdate=t.matrixWorldNeedsUpdate,this.layers.mask=t.layers.mask,this.visible=t.visible,this.castShadow=t.castShadow,this.receiveShadow=t.receiveShadow,this.frustumCulled=t.frustumCulled,this.renderOrder=t.renderOrder,this.static=t.static,this.animations=t.animations.slice(),this.userData=JSON.parse(JSON.stringify(t.userData)),e===!0)for(let n=0;n<t.children.length;n++){const s=t.children[n];this.add(s.clone())}return this}}Te.DEFAULT_UP=new I(0,1,0);Te.DEFAULT_MATRIX_AUTO_UPDATE=!0;Te.DEFAULT_MATRIX_WORLD_AUTO_UPDATE=!0;class $e extends Te{constructor(){super(),this.isGroup=!0,this.type="Group"}}const Iu={type:"move"};class Xr{constructor(){this._targetRay=null,this._grip=null,this._hand=null}getHandSpace(){return this._hand===null&&(this._hand=new $e,this._hand.matrixAutoUpdate=!1,this._hand.visible=!1,this._hand.joints={},this._hand.inputState={pinching:!1}),this._hand}getTargetRaySpace(){return this._targetRay===null&&(this._targetRay=new $e,this._targetRay.matrixAutoUpdate=!1,this._targetRay.visible=!1,this._targetRay.hasLinearVelocity=!1,this._targetRay.linearVelocity=new I,this._targetRay.hasAngularVelocity=!1,this._targetRay.angularVelocity=new I),this._targetRay}getGripSpace(){return this._grip===null&&(this._grip=new $e,this._grip.matrixAutoUpdate=!1,this._grip.visible=!1,this._grip.hasLinearVelocity=!1,this._grip.linearVelocity=new I,this._grip.hasAngularVelocity=!1,this._grip.angularVelocity=new I,this._grip.eventsEnabled=!1),this._grip}dispatchEvent(t){return this._targetRay!==null&&this._targetRay.dispatchEvent(t),this._grip!==null&&this._grip.dispatchEvent(t),this._hand!==null&&this._hand.dispatchEvent(t),this}connect(t){if(t&&t.hand){const e=this._hand;if(e)for(const n of t.hand.values())this._getHandJoint(e,n)}return this.dispatchEvent({type:"connected",data:t}),this}disconnect(t){return this.dispatchEvent({type:"disconnected",data:t}),this._targetRay!==null&&(this._targetRay.visible=!1),this._grip!==null&&(this._grip.visible=!1),this._hand!==null&&(this._hand.visible=!1),this}update(t,e,n){let s=null,r=null,a=null;const o=this._targetRay,c=this._grip,l=this._hand;if(t&&e.session.visibilityState!=="visible-blurred"){if(l&&t.hand){a=!0;for(const v of t.hand.values()){const m=e.getJointPose(v,n),f=this._getHandJoint(l,v);m!==null&&(f.matrix.fromArray(m.transform.matrix),f.matrix.decompose(f.position,f.rotation,f.scale),f.matrixWorldNeedsUpdate=!0,f.jointRadius=m.radius),f.visible=m!==null}const h=l.joints["index-finger-tip"],d=l.joints["thumb-tip"],u=h.position.distanceTo(d.position),p=.02,g=.005;l.inputState.pinching&&u>p+g?(l.inputState.pinching=!1,this.dispatchEvent({type:"pinchend",handedness:t.handedness,target:this})):!l.inputState.pinching&&u<=p-g&&(l.inputState.pinching=!0,this.dispatchEvent({type:"pinchstart",handedness:t.handedness,target:this}))}else c!==null&&t.gripSpace&&(r=e.getPose(t.gripSpace,n),r!==null&&(c.matrix.fromArray(r.transform.matrix),c.matrix.decompose(c.position,c.rotation,c.scale),c.matrixWorldNeedsUpdate=!0,r.linearVelocity?(c.hasLinearVelocity=!0,c.linearVelocity.copy(r.linearVelocity)):c.hasLinearVelocity=!1,r.angularVelocity?(c.hasAngularVelocity=!0,c.angularVelocity.copy(r.angularVelocity)):c.hasAngularVelocity=!1,c.eventsEnabled&&c.dispatchEvent({type:"gripUpdated",data:t,target:this})));o!==null&&(s=e.getPose(t.targetRaySpace,n),s===null&&r!==null&&(s=r),s!==null&&(o.matrix.fromArray(s.transform.matrix),o.matrix.decompose(o.position,o.rotation,o.scale),o.matrixWorldNeedsUpdate=!0,s.linearVelocity?(o.hasLinearVelocity=!0,o.linearVelocity.copy(s.linearVelocity)):o.hasLinearVelocity=!1,s.angularVelocity?(o.hasAngularVelocity=!0,o.angularVelocity.copy(s.angularVelocity)):o.hasAngularVelocity=!1,this.dispatchEvent(Iu)))}return o!==null&&(o.visible=s!==null),c!==null&&(c.visible=r!==null),l!==null&&(l.visible=a!==null),this}_getHandJoint(t,e){if(t.joints[e.jointName]===void 0){const n=new $e;n.matrixAutoUpdate=!1,n.visible=!1,t.joints[e.jointName]=n,t.add(n)}return t.joints[e.jointName]}}const qc={aliceblue:15792383,antiquewhite:16444375,aqua:65535,aquamarine:8388564,azure:15794175,beige:16119260,bisque:16770244,black:0,blanchedalmond:16772045,blue:255,blueviolet:9055202,brown:10824234,burlywood:14596231,cadetblue:6266528,chartreuse:8388352,chocolate:13789470,coral:16744272,cornflowerblue:6591981,cornsilk:16775388,crimson:14423100,cyan:65535,darkblue:139,darkcyan:35723,darkgoldenrod:12092939,darkgray:11119017,darkgreen:25600,darkgrey:11119017,darkkhaki:12433259,darkmagenta:9109643,darkolivegreen:5597999,darkorange:16747520,darkorchid:10040012,darkred:9109504,darksalmon:15308410,darkseagreen:9419919,darkslateblue:4734347,darkslategray:3100495,darkslategrey:3100495,darkturquoise:52945,darkviolet:9699539,deeppink:16716947,deepskyblue:49151,dimgray:6908265,dimgrey:6908265,dodgerblue:2003199,firebrick:11674146,floralwhite:16775920,forestgreen:2263842,fuchsia:16711935,gainsboro:14474460,ghostwhite:16316671,gold:16766720,goldenrod:14329120,gray:8421504,green:32768,greenyellow:11403055,grey:8421504,honeydew:15794160,hotpink:16738740,indianred:13458524,indigo:4915330,ivory:16777200,khaki:15787660,lavender:15132410,lavenderblush:16773365,lawngreen:8190976,lemonchiffon:16775885,lightblue:11393254,lightcoral:15761536,lightcyan:14745599,lightgoldenrodyellow:16448210,lightgray:13882323,lightgreen:9498256,lightgrey:13882323,lightpink:16758465,lightsalmon:16752762,lightseagreen:2142890,lightskyblue:8900346,lightslategray:7833753,lightslategrey:7833753,lightsteelblue:11584734,lightyellow:16777184,lime:65280,limegreen:3329330,linen:16445670,magenta:16711935,maroon:8388608,mediumaquamarine:6737322,mediumblue:205,mediumorchid:12211667,mediumpurple:9662683,mediumseagreen:3978097,mediumslateblue:8087790,mediumspringgreen:64154,mediumturquoise:4772300,mediumvioletred:13047173,midnightblue:1644912,mintcream:16121850,mistyrose:16770273,moccasin:16770229,navajowhite:16768685,navy:128,oldlace:16643558,olive:8421376,olivedrab:7048739,orange:16753920,orangered:16729344,orchid:14315734,palegoldenrod:15657130,palegreen:10025880,paleturquoise:11529966,palevioletred:14381203,papayawhip:16773077,peachpuff:16767673,peru:13468991,pink:16761035,plum:14524637,powderblue:11591910,purple:8388736,rebeccapurple:6697881,red:16711680,rosybrown:12357519,royalblue:4286945,saddlebrown:9127187,salmon:16416882,sandybrown:16032864,seagreen:3050327,seashell:16774638,sienna:10506797,silver:12632256,skyblue:8900331,slateblue:6970061,slategray:7372944,slategrey:7372944,snow:16775930,springgreen:65407,steelblue:4620980,tan:13808780,teal:32896,thistle:14204888,tomato:16737095,turquoise:4251856,violet:15631086,wheat:16113331,white:16777215,whitesmoke:16119285,yellow:16776960,yellowgreen:10145074},Wn={h:0,s:0,l:0},Ns={h:0,s:0,l:0};function qr(i,t,e){return e<0&&(e+=1),e>1&&(e-=1),e<1/6?i+(t-i)*6*e:e<1/2?t:e<2/3?i+(t-i)*6*(2/3-e):i}class Ft{constructor(t,e,n){return this.isColor=!0,this.r=1,this.g=1,this.b=1,this.set(t,e,n)}set(t,e,n){if(e===void 0&&n===void 0){const s=t;s&&s.isColor?this.copy(s):typeof s=="number"?this.setHex(s):typeof s=="string"&&this.setStyle(s)}else this.setRGB(t,e,n);return this}setScalar(t){return this.r=t,this.g=t,this.b=t,this}setHex(t,e=Fe){return t=Math.floor(t),this.r=(t>>16&255)/255,this.g=(t>>8&255)/255,this.b=(t&255)/255,Qt.colorSpaceToWorking(this,e),this}setRGB(t,e,n,s=Qt.workingColorSpace){return this.r=t,this.g=e,this.b=n,Qt.colorSpaceToWorking(this,s),this}setHSL(t,e,n,s=Qt.workingColorSpace){if(t=vu(t,1),e=jt(e,0,1),n=jt(n,0,1),e===0)this.r=this.g=this.b=n;else{const r=n<=.5?n*(1+e):n+e-n*e,a=2*n-r;this.r=qr(a,r,t+1/3),this.g=qr(a,r,t),this.b=qr(a,r,t-1/3)}return Qt.colorSpaceToWorking(this,s),this}setStyle(t,e=Fe){function n(r){r!==void 0&&parseFloat(r)<1&&Ut("Color: Alpha component of "+t+" will be ignored.")}let s;if(s=/^(\w+)\(([^\)]*)\)/.exec(t)){let r;const a=s[1],o=s[2];switch(a){case"rgb":case"rgba":if(r=/^\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*(\d*\.?\d+)\s*)?$/.exec(o))return n(r[4]),this.setRGB(Math.min(255,parseInt(r[1],10))/255,Math.min(255,parseInt(r[2],10))/255,Math.min(255,parseInt(r[3],10))/255,e);if(r=/^\s*(\d+)\%\s*,\s*(\d+)\%\s*,\s*(\d+)\%\s*(?:,\s*(\d*\.?\d+)\s*)?$/.exec(o))return n(r[4]),this.setRGB(Math.min(100,parseInt(r[1],10))/100,Math.min(100,parseInt(r[2],10))/100,Math.min(100,parseInt(r[3],10))/100,e);break;case"hsl":case"hsla":if(r=/^\s*(\d*\.?\d+)\s*,\s*(\d*\.?\d+)\%\s*,\s*(\d*\.?\d+)\%\s*(?:,\s*(\d*\.?\d+)\s*)?$/.exec(o))return n(r[4]),this.setHSL(parseFloat(r[1])/360,parseFloat(r[2])/100,parseFloat(r[3])/100,e);break;default:Ut("Color: Unknown color model "+t)}}else if(s=/^\#([A-Fa-f\d]+)$/.exec(t)){const r=s[1],a=r.length;if(a===3)return this.setRGB(parseInt(r.charAt(0),16)/15,parseInt(r.charAt(1),16)/15,parseInt(r.charAt(2),16)/15,e);if(a===6)return this.setHex(parseInt(r,16),e);Ut("Color: Invalid hex color "+t)}else if(t&&t.length>0)return this.setColorName(t,e);return this}setColorName(t,e=Fe){const n=qc[t.toLowerCase()];return n!==void 0?this.setHex(n,e):Ut("Color: Unknown color "+t),this}clone(){return new this.constructor(this.r,this.g,this.b)}copy(t){return this.r=t.r,this.g=t.g,this.b=t.b,this}copySRGBToLinear(t){return this.r=Nn(t.r),this.g=Nn(t.g),this.b=Nn(t.b),this}copyLinearToSRGB(t){return this.r=Wi(t.r),this.g=Wi(t.g),this.b=Wi(t.b),this}convertSRGBToLinear(){return this.copySRGBToLinear(this),this}convertLinearToSRGB(){return this.copyLinearToSRGB(this),this}getHex(t=Fe){return Qt.workingToColorSpace(Ie.copy(this),t),Math.round(jt(Ie.r*255,0,255))*65536+Math.round(jt(Ie.g*255,0,255))*256+Math.round(jt(Ie.b*255,0,255))}getHexString(t=Fe){return("000000"+this.getHex(t).toString(16)).slice(-6)}getHSL(t,e=Qt.workingColorSpace){Qt.workingToColorSpace(Ie.copy(this),e);const n=Ie.r,s=Ie.g,r=Ie.b,a=Math.max(n,s,r),o=Math.min(n,s,r);let c,l;const h=(o+a)/2;if(o===a)c=0,l=0;else{const d=a-o;switch(l=h<=.5?d/(a+o):d/(2-a-o),a){case n:c=(s-r)/d+(s<r?6:0);break;case s:c=(r-n)/d+2;break;case r:c=(n-s)/d+4;break}c/=6}return t.h=c,t.s=l,t.l=h,t}getRGB(t,e=Qt.workingColorSpace){return Qt.workingToColorSpace(Ie.copy(this),e),t.r=Ie.r,t.g=Ie.g,t.b=Ie.b,t}getStyle(t=Fe){Qt.workingToColorSpace(Ie.copy(this),t);const e=Ie.r,n=Ie.g,s=Ie.b;return t!==Fe?`color(${t} ${e.toFixed(3)} ${n.toFixed(3)} ${s.toFixed(3)})`:`rgb(${Math.round(e*255)},${Math.round(n*255)},${Math.round(s*255)})`}offsetHSL(t,e,n){return this.getHSL(Wn),this.setHSL(Wn.h+t,Wn.s+e,Wn.l+n)}add(t){return this.r+=t.r,this.g+=t.g,this.b+=t.b,this}addColors(t,e){return this.r=t.r+e.r,this.g=t.g+e.g,this.b=t.b+e.b,this}addScalar(t){return this.r+=t,this.g+=t,this.b+=t,this}sub(t){return this.r=Math.max(0,this.r-t.r),this.g=Math.max(0,this.g-t.g),this.b=Math.max(0,this.b-t.b),this}multiply(t){return this.r*=t.r,this.g*=t.g,this.b*=t.b,this}multiplyScalar(t){return this.r*=t,this.g*=t,this.b*=t,this}lerp(t,e){return this.r+=(t.r-this.r)*e,this.g+=(t.g-this.g)*e,this.b+=(t.b-this.b)*e,this}lerpColors(t,e,n){return this.r=t.r+(e.r-t.r)*n,this.g=t.g+(e.g-t.g)*n,this.b=t.b+(e.b-t.b)*n,this}lerpHSL(t,e){this.getHSL(Wn),t.getHSL(Ns);const n=zr(Wn.h,Ns.h,e),s=zr(Wn.s,Ns.s,e),r=zr(Wn.l,Ns.l,e);return this.setHSL(n,s,r),this}setFromVector3(t){return this.r=t.x,this.g=t.y,this.b=t.z,this}applyMatrix3(t){const e=this.r,n=this.g,s=this.b,r=t.elements;return this.r=r[0]*e+r[3]*n+r[6]*s,this.g=r[1]*e+r[4]*n+r[7]*s,this.b=r[2]*e+r[5]*n+r[8]*s,this}equals(t){return t.r===this.r&&t.g===this.g&&t.b===this.b}fromArray(t,e=0){return this.r=t[e],this.g=t[e+1],this.b=t[e+2],this}toArray(t=[],e=0){return t[e]=this.r,t[e+1]=this.g,t[e+2]=this.b,t}fromBufferAttribute(t,e){return this.r=t.getX(e),this.g=t.getY(e),this.b=t.getZ(e),this}toJSON(){return this.getHex()}*[Symbol.iterator](){yield this.r,yield this.g,yield this.b}}const Ie=new Ft;Ft.NAMES=qc;class Yc extends Te{constructor(){super(),this.isScene=!0,this.type="Scene",this.background=null,this.environment=null,this.fog=null,this.backgroundBlurriness=0,this.backgroundIntensity=1,this.backgroundRotation=new Bn,this.environmentIntensity=1,this.environmentRotation=new Bn,this.overrideMaterial=null,typeof __THREE_DEVTOOLS__<"u"&&__THREE_DEVTOOLS__.dispatchEvent(new CustomEvent("observe",{detail:this}))}copy(t,e){return super.copy(t,e),t.background!==null&&(this.background=t.background.clone()),t.environment!==null&&(this.environment=t.environment.clone()),t.fog!==null&&(this.fog=t.fog.clone()),this.backgroundBlurriness=t.backgroundBlurriness,this.backgroundIntensity=t.backgroundIntensity,this.backgroundRotation.copy(t.backgroundRotation),this.environmentIntensity=t.environmentIntensity,this.environmentRotation.copy(t.environmentRotation),t.overrideMaterial!==null&&(this.overrideMaterial=t.overrideMaterial.clone()),this.matrixAutoUpdate=t.matrixAutoUpdate,this}toJSON(t){const e=super.toJSON(t);return this.fog!==null&&(e.object.fog=this.fog.toJSON()),this.backgroundBlurriness>0&&(e.object.backgroundBlurriness=this.backgroundBlurriness),this.backgroundIntensity!==1&&(e.object.backgroundIntensity=this.backgroundIntensity),e.object.backgroundRotation=this.backgroundRotation.toArray(),this.environmentIntensity!==1&&(e.object.environmentIntensity=this.environmentIntensity),e.object.environmentRotation=this.environmentRotation.toArray(),e}}const sn=new I,An=new I,Yr=new I,wn=new I,wi=new I,Ri=new I,El=new I,$r=new I,Kr=new I,Zr=new I,Jr=new pe,jr=new pe,Qr=new pe;class en{constructor(t=new I,e=new I,n=new I){this.a=t,this.b=e,this.c=n}static getNormal(t,e,n,s){s.subVectors(n,e),sn.subVectors(t,e),s.cross(sn);const r=s.lengthSq();return r>0?s.multiplyScalar(1/Math.sqrt(r)):s.set(0,0,0)}static getBarycoord(t,e,n,s,r){sn.subVectors(s,e),An.subVectors(n,e),Yr.subVectors(t,e);const a=sn.dot(sn),o=sn.dot(An),c=sn.dot(Yr),l=An.dot(An),h=An.dot(Yr),d=a*l-o*o;if(d===0)return r.set(0,0,0),null;const u=1/d,p=(l*c-o*h)*u,g=(a*h-o*c)*u;return r.set(1-p-g,g,p)}static containsPoint(t,e,n,s){return this.getBarycoord(t,e,n,s,wn)===null?!1:wn.x>=0&&wn.y>=0&&wn.x+wn.y<=1}static getInterpolation(t,e,n,s,r,a,o,c){return this.getBarycoord(t,e,n,s,wn)===null?(c.x=0,c.y=0,"z"in c&&(c.z=0),"w"in c&&(c.w=0),null):(c.setScalar(0),c.addScaledVector(r,wn.x),c.addScaledVector(a,wn.y),c.addScaledVector(o,wn.z),c)}static getInterpolatedAttribute(t,e,n,s,r,a){return Jr.setScalar(0),jr.setScalar(0),Qr.setScalar(0),Jr.fromBufferAttribute(t,e),jr.fromBufferAttribute(t,n),Qr.fromBufferAttribute(t,s),a.setScalar(0),a.addScaledVector(Jr,r.x),a.addScaledVector(jr,r.y),a.addScaledVector(Qr,r.z),a}static isFrontFacing(t,e,n,s){return sn.subVectors(n,e),An.subVectors(t,e),sn.cross(An).dot(s)<0}set(t,e,n){return this.a.copy(t),this.b.copy(e),this.c.copy(n),this}setFromPointsAndIndices(t,e,n,s){return this.a.copy(t[e]),this.b.copy(t[n]),this.c.copy(t[s]),this}setFromAttributeAndIndices(t,e,n,s){return this.a.fromBufferAttribute(t,e),this.b.fromBufferAttribute(t,n),this.c.fromBufferAttribute(t,s),this}clone(){return new this.constructor().copy(this)}copy(t){return this.a.copy(t.a),this.b.copy(t.b),this.c.copy(t.c),this}getArea(){return sn.subVectors(this.c,this.b),An.subVectors(this.a,this.b),sn.cross(An).length()*.5}getMidpoint(t){return t.addVectors(this.a,this.b).add(this.c).multiplyScalar(1/3)}getNormal(t){return en.getNormal(this.a,this.b,this.c,t)}getPlane(t){return t.setFromCoplanarPoints(this.a,this.b,this.c)}getBarycoord(t,e){return en.getBarycoord(t,this.a,this.b,this.c,e)}getInterpolation(t,e,n,s,r){return en.getInterpolation(t,this.a,this.b,this.c,e,n,s,r)}containsPoint(t){return en.containsPoint(t,this.a,this.b,this.c)}isFrontFacing(t){return en.isFrontFacing(this.a,this.b,this.c,t)}intersectsBox(t){return t.intersectsTriangle(this)}closestPointToPoint(t,e){const n=this.a,s=this.b,r=this.c;let a,o;wi.subVectors(s,n),Ri.subVectors(r,n),$r.subVectors(t,n);const c=wi.dot($r),l=Ri.dot($r);if(c<=0&&l<=0)return e.copy(n);Kr.subVectors(t,s);const h=wi.dot(Kr),d=Ri.dot(Kr);if(h>=0&&d<=h)return e.copy(s);const u=c*d-h*l;if(u<=0&&c>=0&&h<=0)return a=c/(c-h),e.copy(n).addScaledVector(wi,a);Zr.subVectors(t,r);const p=wi.dot(Zr),g=Ri.dot(Zr);if(g>=0&&p<=g)return e.copy(r);const v=p*l-c*g;if(v<=0&&l>=0&&g<=0)return o=l/(l-g),e.copy(n).addScaledVector(Ri,o);const m=h*g-p*d;if(m<=0&&d-h>=0&&p-g>=0)return El.subVectors(r,s),o=(d-h)/(d-h+(p-g)),e.copy(s).addScaledVector(El,o);const f=1/(m+v+u);return a=v*f,o=u*f,e.copy(n).addScaledVector(wi,a).addScaledVector(Ri,o)}equals(t){return t.a.equals(this.a)&&t.b.equals(this.b)&&t.c.equals(this.c)}}class Ke{constructor(t=new I(1/0,1/0,1/0),e=new I(-1/0,-1/0,-1/0)){this.isBox3=!0,this.min=t,this.max=e}set(t,e){return this.min.copy(t),this.max.copy(e),this}setFromArray(t){this.makeEmpty();for(let e=0,n=t.length;e<n;e+=3)this.expandByPoint(rn.fromArray(t,e));return this}setFromBufferAttribute(t){this.makeEmpty();for(let e=0,n=t.count;e<n;e++)this.expandByPoint(rn.fromBufferAttribute(t,e));return this}setFromPoints(t){this.makeEmpty();for(let e=0,n=t.length;e<n;e++)this.expandByPoint(t[e]);return this}setFromCenterAndSize(t,e){const n=rn.copy(e).multiplyScalar(.5);return this.min.copy(t).sub(n),this.max.copy(t).add(n),this}setFromObject(t,e=!1){return this.makeEmpty(),this.expandByObject(t,e)}clone(){return new this.constructor().copy(this)}copy(t){return this.min.copy(t.min),this.max.copy(t.max),this}makeEmpty(){return this.min.x=this.min.y=this.min.z=1/0,this.max.x=this.max.y=this.max.z=-1/0,this}isEmpty(){return this.max.x<this.min.x||this.max.y<this.min.y||this.max.z<this.min.z}getCenter(t){return this.isEmpty()?t.set(0,0,0):t.addVectors(this.min,this.max).multiplyScalar(.5)}getSize(t){return this.isEmpty()?t.set(0,0,0):t.subVectors(this.max,this.min)}expandByPoint(t){return this.min.min(t),this.max.max(t),this}expandByVector(t){return this.min.sub(t),this.max.add(t),this}expandByScalar(t){return this.min.addScalar(-t),this.max.addScalar(t),this}expandByObject(t,e=!1){t.updateWorldMatrix(!1,!1);const n=t.geometry;if(n!==void 0){const r=n.getAttribute("position");if(e===!0&&r!==void 0&&t.isInstancedMesh!==!0)for(let a=0,o=r.count;a<o;a++)t.isMesh===!0?t.getVertexPosition(a,rn):rn.fromBufferAttribute(r,a),rn.applyMatrix4(t.matrixWorld),this.expandByPoint(rn);else t.boundingBox!==void 0?(t.boundingBox===null&&t.computeBoundingBox(),Us.copy(t.boundingBox)):(n.boundingBox===null&&n.computeBoundingBox(),Us.copy(n.boundingBox)),Us.applyMatrix4(t.matrixWorld),this.union(Us)}const s=t.children;for(let r=0,a=s.length;r<a;r++)this.expandByObject(s[r],e);return this}containsPoint(t){return t.x>=this.min.x&&t.x<=this.max.x&&t.y>=this.min.y&&t.y<=this.max.y&&t.z>=this.min.z&&t.z<=this.max.z}containsBox(t){return this.min.x<=t.min.x&&t.max.x<=this.max.x&&this.min.y<=t.min.y&&t.max.y<=this.max.y&&this.min.z<=t.min.z&&t.max.z<=this.max.z}getParameter(t,e){return e.set((t.x-this.min.x)/(this.max.x-this.min.x),(t.y-this.min.y)/(this.max.y-this.min.y),(t.z-this.min.z)/(this.max.z-this.min.z))}intersectsBox(t){return t.max.x>=this.min.x&&t.min.x<=this.max.x&&t.max.y>=this.min.y&&t.min.y<=this.max.y&&t.max.z>=this.min.z&&t.min.z<=this.max.z}intersectsSphere(t){return this.clampPoint(t.center,rn),rn.distanceToSquared(t.center)<=t.radius*t.radius}intersectsPlane(t){let e,n;return t.normal.x>0?(e=t.normal.x*this.min.x,n=t.normal.x*this.max.x):(e=t.normal.x*this.max.x,n=t.normal.x*this.min.x),t.normal.y>0?(e+=t.normal.y*this.min.y,n+=t.normal.y*this.max.y):(e+=t.normal.y*this.max.y,n+=t.normal.y*this.min.y),t.normal.z>0?(e+=t.normal.z*this.min.z,n+=t.normal.z*this.max.z):(e+=t.normal.z*this.max.z,n+=t.normal.z*this.min.z),e<=-t.constant&&n>=-t.constant}intersectsTriangle(t){if(this.isEmpty())return!1;this.getCenter(Qi),Fs.subVectors(this.max,Qi),Ci.subVectors(t.a,Qi),Pi.subVectors(t.b,Qi),Di.subVectors(t.c,Qi),Xn.subVectors(Pi,Ci),qn.subVectors(Di,Pi),ai.subVectors(Ci,Di);let e=[0,-Xn.z,Xn.y,0,-qn.z,qn.y,0,-ai.z,ai.y,Xn.z,0,-Xn.x,qn.z,0,-qn.x,ai.z,0,-ai.x,-Xn.y,Xn.x,0,-qn.y,qn.x,0,-ai.y,ai.x,0];return!ta(e,Ci,Pi,Di,Fs)||(e=[1,0,0,0,1,0,0,0,1],!ta(e,Ci,Pi,Di,Fs))?!1:(Os.crossVectors(Xn,qn),e=[Os.x,Os.y,Os.z],ta(e,Ci,Pi,Di,Fs))}clampPoint(t,e){return e.copy(t).clamp(this.min,this.max)}distanceToPoint(t){return this.clampPoint(t,rn).distanceTo(t)}getBoundingSphere(t){return this.isEmpty()?t.makeEmpty():(this.getCenter(t.center),t.radius=this.getSize(rn).length()*.5),t}intersect(t){return this.min.max(t.min),this.max.min(t.max),this.isEmpty()&&this.makeEmpty(),this}union(t){return this.min.min(t.min),this.max.max(t.max),this}applyMatrix4(t){return this.isEmpty()?this:(Rn[0].set(this.min.x,this.min.y,this.min.z).applyMatrix4(t),Rn[1].set(this.min.x,this.min.y,this.max.z).applyMatrix4(t),Rn[2].set(this.min.x,this.max.y,this.min.z).applyMatrix4(t),Rn[3].set(this.min.x,this.max.y,this.max.z).applyMatrix4(t),Rn[4].set(this.max.x,this.min.y,this.min.z).applyMatrix4(t),Rn[5].set(this.max.x,this.min.y,this.max.z).applyMatrix4(t),Rn[6].set(this.max.x,this.max.y,this.min.z).applyMatrix4(t),Rn[7].set(this.max.x,this.max.y,this.max.z).applyMatrix4(t),this.setFromPoints(Rn),this)}translate(t){return this.min.add(t),this.max.add(t),this}equals(t){return t.min.equals(this.min)&&t.max.equals(this.max)}toJSON(){return{min:this.min.toArray(),max:this.max.toArray()}}fromJSON(t){return this.min.fromArray(t.min),this.max.fromArray(t.max),this}}const Rn=[new I,new I,new I,new I,new I,new I,new I,new I],rn=new I,Us=new Ke,Ci=new I,Pi=new I,Di=new I,Xn=new I,qn=new I,ai=new I,Qi=new I,Fs=new I,Os=new I,oi=new I;function ta(i,t,e,n,s){for(let r=0,a=i.length-3;r<=a;r+=3){oi.fromArray(i,r);const o=s.x*Math.abs(oi.x)+s.y*Math.abs(oi.y)+s.z*Math.abs(oi.z),c=t.dot(oi),l=e.dot(oi),h=n.dot(oi);if(Math.max(-Math.max(c,l,h),Math.min(c,l,h))>o)return!1}return!0}const Se=new I,Bs=new zt;let Nu=0;class Pe extends si{constructor(t,e,n=!1){if(super(),Array.isArray(t))throw new TypeError("THREE.BufferAttribute: array should be a Typed Array.");this.isBufferAttribute=!0,Object.defineProperty(this,"id",{value:Nu++}),this.name="",this.array=t,this.itemSize=e,this.count=t!==void 0?t.length/e:0,this.normalized=n,this.usage=hl,this.updateRanges=[],this.gpuType=cn,this.version=0}onUploadCallback(){}set needsUpdate(t){t===!0&&this.version++}setUsage(t){return this.usage=t,this}addUpdateRange(t,e){this.updateRanges.push({start:t,count:e})}clearUpdateRanges(){this.updateRanges.length=0}copy(t){return this.name=t.name,this.array=new t.array.constructor(t.array),this.itemSize=t.itemSize,this.count=t.count,this.normalized=t.normalized,this.usage=t.usage,this.gpuType=t.gpuType,this}copyAt(t,e,n){t*=this.itemSize,n*=e.itemSize;for(let s=0,r=this.itemSize;s<r;s++)this.array[t+s]=e.array[n+s];return this}copyArray(t){return this.array.set(t),this}applyMatrix3(t){if(this.itemSize===2)for(let e=0,n=this.count;e<n;e++)Bs.fromBufferAttribute(this,e),Bs.applyMatrix3(t),this.setXY(e,Bs.x,Bs.y);else if(this.itemSize===3)for(let e=0,n=this.count;e<n;e++)Se.fromBufferAttribute(this,e),Se.applyMatrix3(t),this.setXYZ(e,Se.x,Se.y,Se.z);return this}applyMatrix4(t){for(let e=0,n=this.count;e<n;e++)Se.fromBufferAttribute(this,e),Se.applyMatrix4(t),this.setXYZ(e,Se.x,Se.y,Se.z);return this}applyNormalMatrix(t){for(let e=0,n=this.count;e<n;e++)Se.fromBufferAttribute(this,e),Se.applyNormalMatrix(t),this.setXYZ(e,Se.x,Se.y,Se.z);return this}transformDirection(t){for(let e=0,n=this.count;e<n;e++)Se.fromBufferAttribute(this,e),Se.transformDirection(t),this.setXYZ(e,Se.x,Se.y,Se.z);return this}set(t,e=0){return this.array.set(t,e),this}getComponent(t,e){let n=this.array[t*this.itemSize+e];return this.normalized&&(n=Ji(n,this.array)),n}setComponent(t,e,n){return this.normalized&&(n=Oe(n,this.array)),this.array[t*this.itemSize+e]=n,this}getX(t){let e=this.array[t*this.itemSize];return this.normalized&&(e=Ji(e,this.array)),e}setX(t,e){return this.normalized&&(e=Oe(e,this.array)),this.array[t*this.itemSize]=e,this}getY(t){let e=this.array[t*this.itemSize+1];return this.normalized&&(e=Ji(e,this.array)),e}setY(t,e){return this.normalized&&(e=Oe(e,this.array)),this.array[t*this.itemSize+1]=e,this}getZ(t){let e=this.array[t*this.itemSize+2];return this.normalized&&(e=Ji(e,this.array)),e}setZ(t,e){return this.normalized&&(e=Oe(e,this.array)),this.array[t*this.itemSize+2]=e,this}getW(t){let e=this.array[t*this.itemSize+3];return this.normalized&&(e=Ji(e,this.array)),e}setW(t,e){return this.normalized&&(e=Oe(e,this.array)),this.array[t*this.itemSize+3]=e,this}setXY(t,e,n){return t*=this.itemSize,this.normalized&&(e=Oe(e,this.array),n=Oe(n,this.array)),this.array[t+0]=e,this.array[t+1]=n,this}setXYZ(t,e,n,s){return t*=this.itemSize,this.normalized&&(e=Oe(e,this.array),n=Oe(n,this.array),s=Oe(s,this.array)),this.array[t+0]=e,this.array[t+1]=n,this.array[t+2]=s,this}setXYZW(t,e,n,s,r){return t*=this.itemSize,this.normalized&&(e=Oe(e,this.array),n=Oe(n,this.array),s=Oe(s,this.array),r=Oe(r,this.array)),this.array[t+0]=e,this.array[t+1]=n,this.array[t+2]=s,this.array[t+3]=r,this}onUpload(t){return this.onUploadCallback=t,this}clone(){return new this.constructor(this.array,this.itemSize).copy(this)}toJSON(){const t={itemSize:this.itemSize,type:this.array.constructor.name,array:Array.from(this.array),normalized:this.normalized};return this.name!==""&&(t.name=this.name),this.usage!==hl&&(t.usage=this.usage),t}dispose(){this.dispatchEvent({type:"dispose"})}}class $c extends Pe{constructor(t,e,n){super(new Uint16Array(t),e,n)}}class Kc extends Pe{constructor(t,e,n){super(new Uint32Array(t),e,n)}}class ae extends Pe{constructor(t,e,n){super(new Float32Array(t),e,n)}}const Uu=new Ke,ts=new I,ea=new I;class vi{constructor(t=new I,e=-1){this.isSphere=!0,this.center=t,this.radius=e}set(t,e){return this.center.copy(t),this.radius=e,this}setFromPoints(t,e){const n=this.center;e!==void 0?n.copy(e):Uu.setFromPoints(t).getCenter(n);let s=0;for(let r=0,a=t.length;r<a;r++)s=Math.max(s,n.distanceToSquared(t[r]));return this.radius=Math.sqrt(s),this}copy(t){return this.center.copy(t.center),this.radius=t.radius,this}isEmpty(){return this.radius<0}makeEmpty(){return this.center.set(0,0,0),this.radius=-1,this}containsPoint(t){return t.distanceToSquared(this.center)<=this.radius*this.radius}distanceToPoint(t){return t.distanceTo(this.center)-this.radius}intersectsSphere(t){const e=this.radius+t.radius;return t.center.distanceToSquared(this.center)<=e*e}intersectsBox(t){return t.intersectsSphere(this)}intersectsPlane(t){return Math.abs(t.distanceToPoint(this.center))<=this.radius}clampPoint(t,e){const n=this.center.distanceToSquared(t);return e.copy(t),n>this.radius*this.radius&&(e.sub(this.center).normalize(),e.multiplyScalar(this.radius).add(this.center)),e}getBoundingBox(t){return this.isEmpty()?(t.makeEmpty(),t):(t.set(this.center,this.center),t.expandByScalar(this.radius),t)}applyMatrix4(t){return this.center.applyMatrix4(t),this.radius=this.radius*t.getMaxScaleOnAxis(),this}translate(t){return this.center.add(t),this}expandByPoint(t){if(this.isEmpty())return this.center.copy(t),this.radius=0,this;ts.subVectors(t,this.center);const e=ts.lengthSq();if(e>this.radius*this.radius){const n=Math.sqrt(e),s=(n-this.radius)*.5;this.center.addScaledVector(ts,s/n),this.radius+=s}return this}union(t){return t.isEmpty()?this:this.isEmpty()?(this.copy(t),this):(this.center.equals(t.center)===!0?this.radius=Math.max(this.radius,t.radius):(ea.subVectors(t.center,this.center).setLength(t.radius),this.expandByPoint(ts.copy(t.center).add(ea)),this.expandByPoint(ts.copy(t.center).sub(ea))),this)}equals(t){return t.center.equals(this.center)&&t.radius===this.radius}clone(){return new this.constructor().copy(this)}toJSON(){return{radius:this.radius,center:this.center.toArray()}}fromJSON(t){return this.radius=t.radius,this.center.fromArray(t.center),this}}let Fu=0;const Je=new se,na=new Te,Li=new I,He=new Ke,es=new Ke,Re=new I;class _e extends si{constructor(){super(),this.isBufferGeometry=!0,Object.defineProperty(this,"id",{value:Fu++}),this.uuid=Ts(),this.name="",this.type="BufferGeometry",this.index=null,this.indirect=null,this.indirectOffset=0,this.attributes={},this.morphAttributes={},this.morphTargetsRelative=!1,this.groups=[],this.boundingBox=null,this.boundingSphere=null,this.drawRange={start:0,count:1/0},this.userData={},this._transformed=!1}getIndex(){return this.index}setIndex(t){return Array.isArray(t)?this.index=new(mu(t)?Kc:$c)(t,1):this.index=t,this}setIndirect(t,e=0){return this.indirect=t,this.indirectOffset=e,this}getIndirect(){return this.indirect}getAttribute(t){return this.attributes[t]}setAttribute(t,e){return this.attributes[t]=e,this}deleteAttribute(t){return delete this.attributes[t],this}hasAttribute(t){return this.attributes[t]!==void 0}addGroup(t,e,n=0){this.groups.push({start:t,count:e,materialIndex:n})}clearGroups(){this.groups=[]}setDrawRange(t,e){this.drawRange.start=t,this.drawRange.count=e}applyMatrix4(t){const e=this.attributes.position;e!==void 0&&(e.applyMatrix4(t),e.needsUpdate=!0);const n=this.attributes.normal;if(n!==void 0){const r=new Ht().getNormalMatrix(t);n.applyNormalMatrix(r),n.needsUpdate=!0}const s=this.attributes.tangent;return s!==void 0&&(s.transformDirection(t),s.needsUpdate=!0),this.boundingBox!==null&&this.computeBoundingBox(),this.boundingSphere!==null&&this.computeBoundingSphere(),this._transformed=!0,this}applyQuaternion(t){return Je.makeRotationFromQuaternion(t),this.applyMatrix4(Je),this}rotateX(t){return Je.makeRotationX(t),this.applyMatrix4(Je),this}rotateY(t){return Je.makeRotationY(t),this.applyMatrix4(Je),this}rotateZ(t){return Je.makeRotationZ(t),this.applyMatrix4(Je),this}translate(t,e,n){return Je.makeTranslation(t,e,n),this.applyMatrix4(Je),this}scale(t,e,n){return Je.makeScale(t,e,n),this.applyMatrix4(Je),this}lookAt(t){return na.lookAt(t),na.updateMatrix(),this.applyMatrix4(na.matrix),this}center(){return this.computeBoundingBox(),this.boundingBox.getCenter(Li).negate(),this.translate(Li.x,Li.y,Li.z),this}setFromPoints(t){const e=this.getAttribute("position");if(e===void 0){const n=[];for(let s=0,r=t.length;s<r;s++){const a=t[s];n.push(a.x,a.y,a.z||0)}this.setAttribute("position",new ae(n,3))}else{const n=Math.min(t.length,e.count);for(let s=0;s<n;s++){const r=t[s];e.setXYZ(s,r.x,r.y,r.z||0)}t.length>e.count&&Ut("BufferGeometry: Buffer size too small for points data. Use .dispose() and create a new geometry."),e.needsUpdate=!0}return this}computeBoundingBox(){this.boundingBox===null&&(this.boundingBox=new Ke);const t=this.attributes.position,e=this.morphAttributes.position;if(t&&t.isGLBufferAttribute){ee("BufferGeometry.computeBoundingBox(): GLBufferAttribute requires a manual bounding box.",this),this.boundingBox.set(new I(-1/0,-1/0,-1/0),new I(1/0,1/0,1/0));return}if(t!==void 0){if(this.boundingBox.setFromBufferAttribute(t),e)for(let n=0,s=e.length;n<s;n++){const r=e[n];He.setFromBufferAttribute(r),this.morphTargetsRelative?(Re.addVectors(this.boundingBox.min,He.min),this.boundingBox.expandByPoint(Re),Re.addVectors(this.boundingBox.max,He.max),this.boundingBox.expandByPoint(Re)):(this.boundingBox.expandByPoint(He.min),this.boundingBox.expandByPoint(He.max))}}else this.boundingBox.makeEmpty();(isNaN(this.boundingBox.min.x)||isNaN(this.boundingBox.min.y)||isNaN(this.boundingBox.min.z))&&ee('BufferGeometry.computeBoundingBox(): Computed min/max have NaN values. The "position" attribute is likely to have NaN values.',this)}computeBoundingSphere(){this.boundingSphere===null&&(this.boundingSphere=new vi);const t=this.attributes.position,e=this.morphAttributes.position;if(t&&t.isGLBufferAttribute){ee("BufferGeometry.computeBoundingSphere(): GLBufferAttribute requires a manual bounding sphere.",this),this.boundingSphere.set(new I,1/0);return}if(t){const n=this.boundingSphere.center;if(He.setFromBufferAttribute(t),e)for(let r=0,a=e.length;r<a;r++){const o=e[r];es.setFromBufferAttribute(o),this.morphTargetsRelative?(Re.addVectors(He.min,es.min),He.expandByPoint(Re),Re.addVectors(He.max,es.max),He.expandByPoint(Re)):(He.expandByPoint(es.min),He.expandByPoint(es.max))}He.getCenter(n);let s=0;for(let r=0,a=t.count;r<a;r++)Re.fromBufferAttribute(t,r),s=Math.max(s,n.distanceToSquared(Re));if(e)for(let r=0,a=e.length;r<a;r++){const o=e[r],c=this.morphTargetsRelative;for(let l=0,h=o.count;l<h;l++)Re.fromBufferAttribute(o,l),c&&(Li.fromBufferAttribute(t,l),Re.add(Li)),s=Math.max(s,n.distanceToSquared(Re))}this.boundingSphere.radius=Math.sqrt(s),isNaN(this.boundingSphere.radius)&&ee('BufferGeometry.computeBoundingSphere(): Computed radius is NaN. The "position" attribute is likely to have NaN values.',this)}}computeTangents(){const t=this.index,e=this.attributes;if(t===null||e.position===void 0||e.normal===void 0||e.uv===void 0){ee("BufferGeometry: .computeTangents() failed. Missing required attributes (index, position, normal or uv)");return}const n=e.position,s=e.normal,r=e.uv;let a=this.getAttribute("tangent");(a===void 0||a.count!==n.count)&&(a=new Pe(new Float32Array(4*n.count),4),this.setAttribute("tangent",a));const o=[],c=[];for(let x=0;x<n.count;x++)o[x]=new I,c[x]=new I;const l=new I,h=new I,d=new I,u=new zt,p=new zt,g=new zt,v=new I,m=new I;function f(x,T,N){l.fromBufferAttribute(n,x),h.fromBufferAttribute(n,T),d.fromBufferAttribute(n,N),u.fromBufferAttribute(r,x),p.fromBufferAttribute(r,T),g.fromBufferAttribute(r,N),h.sub(l),d.sub(l),p.sub(u),g.sub(u);const D=1/(p.x*g.y-g.x*p.y);isFinite(D)&&(v.copy(h).multiplyScalar(g.y).addScaledVector(d,-p.y).multiplyScalar(D),m.copy(d).multiplyScalar(p.x).addScaledVector(h,-g.x).multiplyScalar(D),o[x].add(v),o[T].add(v),o[N].add(v),c[x].add(m),c[T].add(m),c[N].add(m))}let E=this.groups;E.length===0&&(E=[{start:0,count:t.count}]);for(let x=0,T=E.length;x<T;++x){const N=E[x],D=N.start,B=N.count;for(let $=D,J=D+B;$<J;$+=3)f(t.getX($+0),t.getX($+1),t.getX($+2))}const w=new I,y=new I,b=new I,M=new I;function A(x){b.fromBufferAttribute(s,x),M.copy(b);const T=o[x];w.copy(T),w.sub(b.multiplyScalar(b.dot(T))).normalize(),y.crossVectors(M,T);const D=y.dot(c[x])<0?-1:1;a.setXYZW(x,w.x,w.y,w.z,D)}for(let x=0,T=E.length;x<T;++x){const N=E[x],D=N.start,B=N.count;for(let $=D,J=D+B;$<J;$+=3)A(t.getX($+0)),A(t.getX($+1)),A(t.getX($+2))}this._transformed=!0}computeVertexNormals(){const t=this.index,e=this.getAttribute("position");if(e!==void 0){let n=this.getAttribute("normal");if(n===void 0||n.count!==e.count)n=new Pe(new Float32Array(e.count*3),3),this.setAttribute("normal",n);else for(let u=0,p=n.count;u<p;u++)n.setXYZ(u,0,0,0);const s=new I,r=new I,a=new I,o=new I,c=new I,l=new I,h=new I,d=new I;if(t)for(let u=0,p=t.count;u<p;u+=3){const g=t.getX(u+0),v=t.getX(u+1),m=t.getX(u+2);s.fromBufferAttribute(e,g),r.fromBufferAttribute(e,v),a.fromBufferAttribute(e,m),h.subVectors(a,r),d.subVectors(s,r),h.cross(d),o.fromBufferAttribute(n,g),c.fromBufferAttribute(n,v),l.fromBufferAttribute(n,m),o.add(h),c.add(h),l.add(h),n.setXYZ(g,o.x,o.y,o.z),n.setXYZ(v,c.x,c.y,c.z),n.setXYZ(m,l.x,l.y,l.z)}else for(let u=0,p=e.count;u<p;u+=3)s.fromBufferAttribute(e,u+0),r.fromBufferAttribute(e,u+1),a.fromBufferAttribute(e,u+2),h.subVectors(a,r),d.subVectors(s,r),h.cross(d),n.setXYZ(u+0,h.x,h.y,h.z),n.setXYZ(u+1,h.x,h.y,h.z),n.setXYZ(u+2,h.x,h.y,h.z);this.normalizeNormals(),n.needsUpdate=!0}}normalizeNormals(){const t=this.attributes.normal;for(let e=0,n=t.count;e<n;e++)Re.fromBufferAttribute(t,e),Re.normalize(),t.setXYZ(e,Re.x,Re.y,Re.z)}toNonIndexed(){function t(o,c){const l=o.array,h=o.itemSize,d=o.normalized,u=new l.constructor(c.length*h);let p=0,g=0;for(let v=0,m=c.length;v<m;v++){o.isInterleavedBufferAttribute?p=c[v]*o.data.stride+o.offset:p=c[v]*h;for(let f=0;f<h;f++)u[g++]=l[p++]}return new Pe(u,h,d)}if(this.index===null)return Ut("BufferGeometry.toNonIndexed(): BufferGeometry is already non-indexed."),this;const e=new _e,n=this.index.array,s=this.attributes;for(const o in s){const c=s[o],l=t(c,n);e.setAttribute(o,l)}const r=this.morphAttributes;for(const o in r){const c=[],l=r[o];for(let h=0,d=l.length;h<d;h++){const u=l[h],p=t(u,n);c.push(p)}e.morphAttributes[o]=c}e.morphTargetsRelative=this.morphTargetsRelative;const a=this.groups;for(let o=0,c=a.length;o<c;o++){const l=a[o];e.addGroup(l.start,l.count,l.materialIndex)}return e}toJSON(){const t={metadata:{version:4.7,type:"BufferGeometry",generator:"BufferGeometry.toJSON"}};if(t.uuid=this.uuid,t.type=this.parameters!==void 0&&this._transformed===!0?"BufferGeometry":this.type,this.name!==""&&(t.name=this.name),Object.keys(this.userData).length>0&&(t.userData=this.userData),this.parameters!==void 0&&this._transformed!==!0){const c=this.parameters;for(const l in c)c[l]!==void 0&&(t[l]=c[l]);return t}t.data={attributes:{}};const e=this.index;e!==null&&(t.data.index={type:e.array.constructor.name,array:Array.prototype.slice.call(e.array)});const n=this.attributes;for(const c in n){const l=n[c];t.data.attributes[c]=l.toJSON(t.data)}const s={};let r=!1;for(const c in this.morphAttributes){const l=this.morphAttributes[c],h=[];for(let d=0,u=l.length;d<u;d++){const p=l[d];h.push(p.toJSON(t.data))}h.length>0&&(s[c]=h,r=!0)}r&&(t.data.morphAttributes=s,t.data.morphTargetsRelative=this.morphTargetsRelative);const a=this.groups;a.length>0&&(t.data.groups=JSON.parse(JSON.stringify(a)));const o=this.boundingSphere;return o!==null&&(t.data.boundingSphere=o.toJSON()),t}clone(){return new this.constructor().copy(this)}copy(t){this.index=null,this.attributes={},this.morphAttributes={},this.groups=[],this.boundingBox=null,this.boundingSphere=null;const e={};this.name=t.name;const n=t.index;n!==null&&this.setIndex(n.clone());const s=t.attributes;for(const l in s){const h=s[l];this.setAttribute(l,h.clone(e))}const r=t.morphAttributes;for(const l in r){const h=[],d=r[l];for(let u=0,p=d.length;u<p;u++)h.push(d[u].clone(e));this.morphAttributes[l]=h}this.morphTargetsRelative=t.morphTargetsRelative;const a=t.groups;for(let l=0,h=a.length;l<h;l++){const d=a[l];this.addGroup(d.start,d.count,d.materialIndex)}const o=t.boundingBox;o!==null&&(this.boundingBox=o.clone());const c=t.boundingSphere;return c!==null&&(this.boundingSphere=c.clone()),this.drawRange.start=t.drawRange.start,this.drawRange.count=t.drawRange.count,this.userData=t.userData,this._transformed=t._transformed,this}dispose(){this.dispatchEvent({type:"dispose"})}}let Ou=0;class bn extends si{constructor(){super(),this.isMaterial=!0,Object.defineProperty(this,"id",{value:Ou++}),this.uuid=Ts(),this.name="",this.type="Material",this.blending=Gi,this.side=ni,this.vertexColors=!1,this.opacity=1,this.transparent=!1,this.alphaHash=!1,this.blendSrc=Pa,this.blendDst=Da,this.blendEquation=di,this.blendSrcAlpha=null,this.blendDstAlpha=null,this.blendEquationAlpha=null,this.blendColor=new Ft(0,0,0),this.blendAlpha=0,this.depthFunc=Xi,this.depthTest=!0,this.depthWrite=!0,this.stencilWriteMask=255,this.stencilFunc=cl,this.stencilRef=0,this.stencilFuncMask=255,this.stencilFail=yi,this.stencilZFail=yi,this.stencilZPass=yi,this.stencilWrite=!1,this.clippingPlanes=null,this.clipIntersection=!1,this.clipShadows=!1,this.shadowSide=null,this.colorWrite=!0,this.precision=null,this.polygonOffset=!1,this.polygonOffsetFactor=0,this.polygonOffsetUnits=0,this.dithering=!1,this.alphaToCoverage=!1,this.premultipliedAlpha=!1,this.forceSinglePass=!1,this.allowOverride=!0,this.visible=!0,this.toneMapped=!0,this.userData={},this.version=0,this._alphaTest=0}get alphaTest(){return this._alphaTest}set alphaTest(t){this._alphaTest>0!=t>0&&this.version++,this._alphaTest=t}onBeforeRender(){}onBeforeCompile(){}customProgramCacheKey(){return this.onBeforeCompile.toString()}setValues(t){if(t!==void 0)for(const e in t){const n=t[e];if(n===void 0){Ut(`Material: parameter '${e}' has value of undefined.`);continue}const s=this[e];if(s===void 0){Ut(`Material: '${e}' is not a property of THREE.${this.type}.`);continue}s&&s.isColor?s.set(n):s&&s.isVector2&&n&&n.isVector2||s&&s.isEuler&&n&&n.isEuler||s&&s.isVector3&&n&&n.isVector3?s.copy(n):this[e]=n}}toJSON(t){const e=t===void 0||typeof t=="string";e&&(t={textures:{},images:{}});const n={metadata:{version:4.7,type:"Material",generator:"Material.toJSON"}};n.uuid=this.uuid,n.type=this.type,this.name!==""&&(n.name=this.name),this.color&&this.color.isColor&&(n.color=this.color.getHex()),this.roughness!==void 0&&(n.roughness=this.roughness),this.metalness!==void 0&&(n.metalness=this.metalness),this.sheen!==void 0&&(n.sheen=this.sheen),this.sheenColor&&this.sheenColor.isColor&&(n.sheenColor=this.sheenColor.getHex()),this.sheenRoughness!==void 0&&(n.sheenRoughness=this.sheenRoughness),this.emissive&&this.emissive.isColor&&(n.emissive=this.emissive.getHex()),this.emissiveIntensity!==void 0&&this.emissiveIntensity!==1&&(n.emissiveIntensity=this.emissiveIntensity),this.specular&&this.specular.isColor&&(n.specular=this.specular.getHex()),this.specularIntensity!==void 0&&(n.specularIntensity=this.specularIntensity),this.specularColor&&this.specularColor.isColor&&(n.specularColor=this.specularColor.getHex()),this.shininess!==void 0&&(n.shininess=this.shininess),this.clearcoat!==void 0&&(n.clearcoat=this.clearcoat),this.clearcoatRoughness!==void 0&&(n.clearcoatRoughness=this.clearcoatRoughness),this.clearcoatMap&&this.clearcoatMap.isTexture&&(n.clearcoatMap=this.clearcoatMap.toJSON(t).uuid),this.clearcoatRoughnessMap&&this.clearcoatRoughnessMap.isTexture&&(n.clearcoatRoughnessMap=this.clearcoatRoughnessMap.toJSON(t).uuid),this.clearcoatNormalMap&&this.clearcoatNormalMap.isTexture&&(n.clearcoatNormalMap=this.clearcoatNormalMap.toJSON(t).uuid,n.clearcoatNormalScale=this.clearcoatNormalScale.toArray()),this.sheenColorMap&&this.sheenColorMap.isTexture&&(n.sheenColorMap=this.sheenColorMap.toJSON(t).uuid),this.sheenRoughnessMap&&this.sheenRoughnessMap.isTexture&&(n.sheenRoughnessMap=this.sheenRoughnessMap.toJSON(t).uuid),this.dispersion!==void 0&&(n.dispersion=this.dispersion),this.iridescence!==void 0&&(n.iridescence=this.iridescence),this.iridescenceIOR!==void 0&&(n.iridescenceIOR=this.iridescenceIOR),this.iridescenceThicknessRange!==void 0&&(n.iridescenceThicknessRange=this.iridescenceThicknessRange),this.iridescenceMap&&this.iridescenceMap.isTexture&&(n.iridescenceMap=this.iridescenceMap.toJSON(t).uuid),this.iridescenceThicknessMap&&this.iridescenceThicknessMap.isTexture&&(n.iridescenceThicknessMap=this.iridescenceThicknessMap.toJSON(t).uuid),this.anisotropy!==void 0&&(n.anisotropy=this.anisotropy),this.anisotropyRotation!==void 0&&(n.anisotropyRotation=this.anisotropyRotation),this.anisotropyMap&&this.anisotropyMap.isTexture&&(n.anisotropyMap=this.anisotropyMap.toJSON(t).uuid),this.map&&this.map.isTexture&&(n.map=this.map.toJSON(t).uuid),this.matcap&&this.matcap.isTexture&&(n.matcap=this.matcap.toJSON(t).uuid),this.alphaMap&&this.alphaMap.isTexture&&(n.alphaMap=this.alphaMap.toJSON(t).uuid),this.lightMap&&this.lightMap.isTexture&&(n.lightMap=this.lightMap.toJSON(t).uuid,n.lightMapIntensity=this.lightMapIntensity),this.aoMap&&this.aoMap.isTexture&&(n.aoMap=this.aoMap.toJSON(t).uuid,n.aoMapIntensity=this.aoMapIntensity),this.bumpMap&&this.bumpMap.isTexture&&(n.bumpMap=this.bumpMap.toJSON(t).uuid,n.bumpScale=this.bumpScale),this.normalMap&&this.normalMap.isTexture&&(n.normalMap=this.normalMap.toJSON(t).uuid,n.normalMapType=this.normalMapType,n.normalScale=this.normalScale.toArray()),this.displacementMap&&this.displacementMap.isTexture&&(n.displacementMap=this.displacementMap.toJSON(t).uuid,n.displacementScale=this.displacementScale,n.displacementBias=this.displacementBias),this.roughnessMap&&this.roughnessMap.isTexture&&(n.roughnessMap=this.roughnessMap.toJSON(t).uuid),this.metalnessMap&&this.metalnessMap.isTexture&&(n.metalnessMap=this.metalnessMap.toJSON(t).uuid),this.emissiveMap&&this.emissiveMap.isTexture&&(n.emissiveMap=this.emissiveMap.toJSON(t).uuid),this.specularMap&&this.specularMap.isTexture&&(n.specularMap=this.specularMap.toJSON(t).uuid),this.specularIntensityMap&&this.specularIntensityMap.isTexture&&(n.specularIntensityMap=this.specularIntensityMap.toJSON(t).uuid),this.specularColorMap&&this.specularColorMap.isTexture&&(n.specularColorMap=this.specularColorMap.toJSON(t).uuid),this.envMap&&this.envMap.isTexture&&(n.envMap=this.envMap.toJSON(t).uuid,this.combine!==void 0&&(n.combine=this.combine)),this.envMapRotation!==void 0&&(n.envMapRotation=this.envMapRotation.toArray()),this.envMapIntensity!==void 0&&(n.envMapIntensity=this.envMapIntensity),this.reflectivity!==void 0&&(n.reflectivity=this.reflectivity),this.refractionRatio!==void 0&&(n.refractionRatio=this.refractionRatio),this.gradientMap&&this.gradientMap.isTexture&&(n.gradientMap=this.gradientMap.toJSON(t).uuid),this.transmission!==void 0&&(n.transmission=this.transmission),this.transmissionMap&&this.transmissionMap.isTexture&&(n.transmissionMap=this.transmissionMap.toJSON(t).uuid),this.thickness!==void 0&&(n.thickness=this.thickness),this.thicknessMap&&this.thicknessMap.isTexture&&(n.thicknessMap=this.thicknessMap.toJSON(t).uuid),this.attenuationDistance!==void 0&&this.attenuationDistance!==1/0&&(n.attenuationDistance=this.attenuationDistance),this.attenuationColor!==void 0&&(n.attenuationColor=this.attenuationColor.getHex()),this.size!==void 0&&(n.size=this.size),this.shadowSide!==null&&(n.shadowSide=this.shadowSide),this.sizeAttenuation!==void 0&&(n.sizeAttenuation=this.sizeAttenuation),this.blending!==Gi&&(n.blending=this.blending),this.side!==ni&&(n.side=this.side),this.vertexColors===!0&&(n.vertexColors=!0),this.opacity<1&&(n.opacity=this.opacity),this.transparent===!0&&(n.transparent=!0),this.blendSrc!==Pa&&(n.blendSrc=this.blendSrc),this.blendDst!==Da&&(n.blendDst=this.blendDst),this.blendEquation!==di&&(n.blendEquation=this.blendEquation),this.blendSrcAlpha!==null&&(n.blendSrcAlpha=this.blendSrcAlpha),this.blendDstAlpha!==null&&(n.blendDstAlpha=this.blendDstAlpha),this.blendEquationAlpha!==null&&(n.blendEquationAlpha=this.blendEquationAlpha),this.blendColor&&this.blendColor.isColor&&(n.blendColor=this.blendColor.getHex()),this.blendAlpha!==0&&(n.blendAlpha=this.blendAlpha),this.depthFunc!==Xi&&(n.depthFunc=this.depthFunc),this.depthTest===!1&&(n.depthTest=this.depthTest),this.depthWrite===!1&&(n.depthWrite=this.depthWrite),this.colorWrite===!1&&(n.colorWrite=this.colorWrite),this.stencilWriteMask!==255&&(n.stencilWriteMask=this.stencilWriteMask),this.stencilFunc!==cl&&(n.stencilFunc=this.stencilFunc),this.stencilRef!==0&&(n.stencilRef=this.stencilRef),this.stencilFuncMask!==255&&(n.stencilFuncMask=this.stencilFuncMask),this.stencilFail!==yi&&(n.stencilFail=this.stencilFail),this.stencilZFail!==yi&&(n.stencilZFail=this.stencilZFail),this.stencilZPass!==yi&&(n.stencilZPass=this.stencilZPass),this.stencilWrite===!0&&(n.stencilWrite=this.stencilWrite),this.rotation!==void 0&&this.rotation!==0&&(n.rotation=this.rotation),this.polygonOffset===!0&&(n.polygonOffset=!0),this.polygonOffsetFactor!==0&&(n.polygonOffsetFactor=this.polygonOffsetFactor),this.polygonOffsetUnits!==0&&(n.polygonOffsetUnits=this.polygonOffsetUnits),this.linewidth!==void 0&&this.linewidth!==1&&(n.linewidth=this.linewidth),this.dashSize!==void 0&&(n.dashSize=this.dashSize),this.gapSize!==void 0&&(n.gapSize=this.gapSize),this.scale!==void 0&&(n.scale=this.scale),this.dithering===!0&&(n.dithering=!0),this.alphaTest>0&&(n.alphaTest=this.alphaTest),this.alphaHash===!0&&(n.alphaHash=!0),this.alphaToCoverage===!0&&(n.alphaToCoverage=!0),this.premultipliedAlpha===!0&&(n.premultipliedAlpha=!0),this.forceSinglePass===!0&&(n.forceSinglePass=!0),this.allowOverride===!1&&(n.allowOverride=!1),this.wireframe===!0&&(n.wireframe=!0),this.wireframeLinewidth>1&&(n.wireframeLinewidth=this.wireframeLinewidth),this.wireframeLinecap!=="round"&&(n.wireframeLinecap=this.wireframeLinecap),this.wireframeLinejoin!=="round"&&(n.wireframeLinejoin=this.wireframeLinejoin),this.flatShading===!0&&(n.flatShading=!0),this.visible===!1&&(n.visible=!1),this.toneMapped===!1&&(n.toneMapped=!1),this.fog===!1&&(n.fog=!1),Object.keys(this.userData).length>0&&(n.userData=this.userData);function s(r){const a=[];for(const o in r){const c=r[o];delete c.metadata,a.push(c)}return a}if(e){const r=s(t.textures),a=s(t.images);r.length>0&&(n.textures=r),a.length>0&&(n.images=a)}return n}fromJSON(t,e){if(t.uuid!==void 0&&(this.uuid=t.uuid),t.name!==void 0&&(this.name=t.name),t.color!==void 0&&this.color!==void 0&&this.color.setHex(t.color),t.roughness!==void 0&&(this.roughness=t.roughness),t.metalness!==void 0&&(this.metalness=t.metalness),t.sheen!==void 0&&(this.sheen=t.sheen),t.sheenColor!==void 0&&(this.sheenColor=new Ft().setHex(t.sheenColor)),t.sheenRoughness!==void 0&&(this.sheenRoughness=t.sheenRoughness),t.emissive!==void 0&&this.emissive!==void 0&&this.emissive.setHex(t.emissive),t.specular!==void 0&&this.specular!==void 0&&this.specular.setHex(t.specular),t.specularIntensity!==void 0&&(this.specularIntensity=t.specularIntensity),t.specularColor!==void 0&&this.specularColor!==void 0&&this.specularColor.setHex(t.specularColor),t.shininess!==void 0&&(this.shininess=t.shininess),t.clearcoat!==void 0&&(this.clearcoat=t.clearcoat),t.clearcoatRoughness!==void 0&&(this.clearcoatRoughness=t.clearcoatRoughness),t.dispersion!==void 0&&(this.dispersion=t.dispersion),t.iridescence!==void 0&&(this.iridescence=t.iridescence),t.iridescenceIOR!==void 0&&(this.iridescenceIOR=t.iridescenceIOR),t.iridescenceThicknessRange!==void 0&&(this.iridescenceThicknessRange=t.iridescenceThicknessRange),t.transmission!==void 0&&(this.transmission=t.transmission),t.thickness!==void 0&&(this.thickness=t.thickness),t.attenuationDistance!==void 0&&(this.attenuationDistance=t.attenuationDistance),t.attenuationColor!==void 0&&this.attenuationColor!==void 0&&this.attenuationColor.setHex(t.attenuationColor),t.anisotropy!==void 0&&(this.anisotropy=t.anisotropy),t.anisotropyRotation!==void 0&&(this.anisotropyRotation=t.anisotropyRotation),t.fog!==void 0&&(this.fog=t.fog),t.flatShading!==void 0&&(this.flatShading=t.flatShading),t.blending!==void 0&&(this.blending=t.blending),t.combine!==void 0&&(this.combine=t.combine),t.side!==void 0&&(this.side=t.side),t.shadowSide!==void 0&&(this.shadowSide=t.shadowSide),t.opacity!==void 0&&(this.opacity=t.opacity),t.transparent!==void 0&&(this.transparent=t.transparent),t.alphaTest!==void 0&&(this.alphaTest=t.alphaTest),t.alphaHash!==void 0&&(this.alphaHash=t.alphaHash),t.depthFunc!==void 0&&(this.depthFunc=t.depthFunc),t.depthTest!==void 0&&(this.depthTest=t.depthTest),t.depthWrite!==void 0&&(this.depthWrite=t.depthWrite),t.colorWrite!==void 0&&(this.colorWrite=t.colorWrite),t.blendSrc!==void 0&&(this.blendSrc=t.blendSrc),t.blendDst!==void 0&&(this.blendDst=t.blendDst),t.blendEquation!==void 0&&(this.blendEquation=t.blendEquation),t.blendSrcAlpha!==void 0&&(this.blendSrcAlpha=t.blendSrcAlpha),t.blendDstAlpha!==void 0&&(this.blendDstAlpha=t.blendDstAlpha),t.blendEquationAlpha!==void 0&&(this.blendEquationAlpha=t.blendEquationAlpha),t.blendColor!==void 0&&this.blendColor!==void 0&&this.blendColor.setHex(t.blendColor),t.blendAlpha!==void 0&&(this.blendAlpha=t.blendAlpha),t.stencilWriteMask!==void 0&&(this.stencilWriteMask=t.stencilWriteMask),t.stencilFunc!==void 0&&(this.stencilFunc=t.stencilFunc),t.stencilRef!==void 0&&(this.stencilRef=t.stencilRef),t.stencilFuncMask!==void 0&&(this.stencilFuncMask=t.stencilFuncMask),t.stencilFail!==void 0&&(this.stencilFail=t.stencilFail),t.stencilZFail!==void 0&&(this.stencilZFail=t.stencilZFail),t.stencilZPass!==void 0&&(this.stencilZPass=t.stencilZPass),t.stencilWrite!==void 0&&(this.stencilWrite=t.stencilWrite),t.wireframe!==void 0&&(this.wireframe=t.wireframe),t.wireframeLinewidth!==void 0&&(this.wireframeLinewidth=t.wireframeLinewidth),t.wireframeLinecap!==void 0&&(this.wireframeLinecap=t.wireframeLinecap),t.wireframeLinejoin!==void 0&&(this.wireframeLinejoin=t.wireframeLinejoin),t.rotation!==void 0&&(this.rotation=t.rotation),t.linewidth!==void 0&&(this.linewidth=t.linewidth),t.dashSize!==void 0&&(this.dashSize=t.dashSize),t.gapSize!==void 0&&(this.gapSize=t.gapSize),t.scale!==void 0&&(this.scale=t.scale),t.polygonOffset!==void 0&&(this.polygonOffset=t.polygonOffset),t.polygonOffsetFactor!==void 0&&(this.polygonOffsetFactor=t.polygonOffsetFactor),t.polygonOffsetUnits!==void 0&&(this.polygonOffsetUnits=t.polygonOffsetUnits),t.dithering!==void 0&&(this.dithering=t.dithering),t.alphaToCoverage!==void 0&&(this.alphaToCoverage=t.alphaToCoverage),t.premultipliedAlpha!==void 0&&(this.premultipliedAlpha=t.premultipliedAlpha),t.forceSinglePass!==void 0&&(this.forceSinglePass=t.forceSinglePass),t.allowOverride!==void 0&&(this.allowOverride=t.allowOverride),t.visible!==void 0&&(this.visible=t.visible),t.toneMapped!==void 0&&(this.toneMapped=t.toneMapped),t.userData!==void 0&&(this.userData=t.userData),t.vertexColors!==void 0&&(typeof t.vertexColors=="number"?this.vertexColors=t.vertexColors>0:this.vertexColors=t.vertexColors),t.size!==void 0&&(this.size=t.size),t.sizeAttenuation!==void 0&&(this.sizeAttenuation=t.sizeAttenuation),t.map!==void 0&&(this.map=e[t.map]||null),t.matcap!==void 0&&(this.matcap=e[t.matcap]||null),t.alphaMap!==void 0&&(this.alphaMap=e[t.alphaMap]||null),t.bumpMap!==void 0&&(this.bumpMap=e[t.bumpMap]||null),t.bumpScale!==void 0&&(this.bumpScale=t.bumpScale),t.normalMap!==void 0&&(this.normalMap=e[t.normalMap]||null),t.normalMapType!==void 0&&(this.normalMapType=t.normalMapType),t.normalScale!==void 0){let n=t.normalScale;Array.isArray(n)===!1&&(n=[n,n]),this.normalScale=new zt().fromArray(n)}return t.displacementMap!==void 0&&(this.displacementMap=e[t.displacementMap]||null),t.displacementScale!==void 0&&(this.displacementScale=t.displacementScale),t.displacementBias!==void 0&&(this.displacementBias=t.displacementBias),t.roughnessMap!==void 0&&(this.roughnessMap=e[t.roughnessMap]||null),t.metalnessMap!==void 0&&(this.metalnessMap=e[t.metalnessMap]||null),t.emissiveMap!==void 0&&(this.emissiveMap=e[t.emissiveMap]||null),t.emissiveIntensity!==void 0&&(this.emissiveIntensity=t.emissiveIntensity),t.specularMap!==void 0&&(this.specularMap=e[t.specularMap]||null),t.specularIntensityMap!==void 0&&(this.specularIntensityMap=e[t.specularIntensityMap]||null),t.specularColorMap!==void 0&&(this.specularColorMap=e[t.specularColorMap]||null),t.envMap!==void 0&&(this.envMap=e[t.envMap]||null),t.envMapRotation!==void 0&&this.envMapRotation.fromArray(t.envMapRotation),t.envMapIntensity!==void 0&&(this.envMapIntensity=t.envMapIntensity),t.reflectivity!==void 0&&(this.reflectivity=t.reflectivity),t.refractionRatio!==void 0&&(this.refractionRatio=t.refractionRatio),t.lightMap!==void 0&&(this.lightMap=e[t.lightMap]||null),t.lightMapIntensity!==void 0&&(this.lightMapIntensity=t.lightMapIntensity),t.aoMap!==void 0&&(this.aoMap=e[t.aoMap]||null),t.aoMapIntensity!==void 0&&(this.aoMapIntensity=t.aoMapIntensity),t.gradientMap!==void 0&&(this.gradientMap=e[t.gradientMap]||null),t.clearcoatMap!==void 0&&(this.clearcoatMap=e[t.clearcoatMap]||null),t.clearcoatRoughnessMap!==void 0&&(this.clearcoatRoughnessMap=e[t.clearcoatRoughnessMap]||null),t.clearcoatNormalMap!==void 0&&(this.clearcoatNormalMap=e[t.clearcoatNormalMap]||null),t.clearcoatNormalScale!==void 0&&(this.clearcoatNormalScale=new zt().fromArray(t.clearcoatNormalScale)),t.iridescenceMap!==void 0&&(this.iridescenceMap=e[t.iridescenceMap]||null),t.iridescenceThicknessMap!==void 0&&(this.iridescenceThicknessMap=e[t.iridescenceThicknessMap]||null),t.transmissionMap!==void 0&&(this.transmissionMap=e[t.transmissionMap]||null),t.thicknessMap!==void 0&&(this.thicknessMap=e[t.thicknessMap]||null),t.anisotropyMap!==void 0&&(this.anisotropyMap=e[t.anisotropyMap]||null),t.sheenColorMap!==void 0&&(this.sheenColorMap=e[t.sheenColorMap]||null),t.sheenRoughnessMap!==void 0&&(this.sheenRoughnessMap=e[t.sheenRoughnessMap]||null),this}clone(){return new this.constructor().copy(this)}copy(t){this.name=t.name,this.blending=t.blending,this.side=t.side,this.vertexColors=t.vertexColors,this.opacity=t.opacity,this.transparent=t.transparent,this.blendSrc=t.blendSrc,this.blendDst=t.blendDst,this.blendEquation=t.blendEquation,this.blendSrcAlpha=t.blendSrcAlpha,this.blendDstAlpha=t.blendDstAlpha,this.blendEquationAlpha=t.blendEquationAlpha,this.blendColor.copy(t.blendColor),this.blendAlpha=t.blendAlpha,this.depthFunc=t.depthFunc,this.depthTest=t.depthTest,this.depthWrite=t.depthWrite,this.stencilWriteMask=t.stencilWriteMask,this.stencilFunc=t.stencilFunc,this.stencilRef=t.stencilRef,this.stencilFuncMask=t.stencilFuncMask,this.stencilFail=t.stencilFail,this.stencilZFail=t.stencilZFail,this.stencilZPass=t.stencilZPass,this.stencilWrite=t.stencilWrite;const e=t.clippingPlanes;let n=null;if(e!==null){const s=e.length;n=new Array(s);for(let r=0;r!==s;++r)n[r]=e[r].clone()}return this.clippingPlanes=n,this.clipIntersection=t.clipIntersection,this.clipShadows=t.clipShadows,this.shadowSide=t.shadowSide,this.colorWrite=t.colorWrite,this.precision=t.precision,this.polygonOffset=t.polygonOffset,this.polygonOffsetFactor=t.polygonOffsetFactor,this.polygonOffsetUnits=t.polygonOffsetUnits,this.dithering=t.dithering,this.alphaTest=t.alphaTest,this.alphaHash=t.alphaHash,this.alphaToCoverage=t.alphaToCoverage,this.premultipliedAlpha=t.premultipliedAlpha,this.forceSinglePass=t.forceSinglePass,this.allowOverride=t.allowOverride,this.visible=t.visible,this.toneMapped=t.toneMapped,this.userData=JSON.parse(JSON.stringify(t.userData)),this}dispose(){this.dispatchEvent({type:"dispose"})}set needsUpdate(t){t===!0&&this.version++}}const Cn=new I,ia=new I,zs=new I,Yn=new I,sa=new I,ks=new I,ra=new I;class As{constructor(t=new I,e=new I(0,0,-1)){this.origin=t,this.direction=e}set(t,e){return this.origin.copy(t),this.direction.copy(e),this}copy(t){return this.origin.copy(t.origin),this.direction.copy(t.direction),this}at(t,e){return e.copy(this.origin).addScaledVector(this.direction,t)}lookAt(t){return this.direction.copy(t).sub(this.origin).normalize(),this}recast(t){return this.origin.copy(this.at(t,Cn)),this}closestPointToPoint(t,e){e.subVectors(t,this.origin);const n=e.dot(this.direction);return n<0?e.copy(this.origin):e.copy(this.origin).addScaledVector(this.direction,n)}distanceToPoint(t){return Math.sqrt(this.distanceSqToPoint(t))}distanceSqToPoint(t){const e=Cn.subVectors(t,this.origin).dot(this.direction);return e<0?this.origin.distanceToSquared(t):(Cn.copy(this.origin).addScaledVector(this.direction,e),Cn.distanceToSquared(t))}distanceSqToSegment(t,e,n,s){ia.copy(t).add(e).multiplyScalar(.5),zs.copy(e).sub(t).normalize(),Yn.copy(this.origin).sub(ia);const r=t.distanceTo(e)*.5,a=-this.direction.dot(zs),o=Yn.dot(this.direction),c=-Yn.dot(zs),l=Yn.lengthSq(),h=Math.abs(1-a*a);let d,u,p,g;if(h>0)if(d=a*c-o,u=a*o-c,g=r*h,d>=0)if(u>=-g)if(u<=g){const v=1/h;d*=v,u*=v,p=d*(d+a*u+2*o)+u*(a*d+u+2*c)+l}else u=r,d=Math.max(0,-(a*u+o)),p=-d*d+u*(u+2*c)+l;else u=-r,d=Math.max(0,-(a*u+o)),p=-d*d+u*(u+2*c)+l;else u<=-g?(d=Math.max(0,-(-a*r+o)),u=d>0?-r:Math.min(Math.max(-r,-c),r),p=-d*d+u*(u+2*c)+l):u<=g?(d=0,u=Math.min(Math.max(-r,-c),r),p=u*(u+2*c)+l):(d=Math.max(0,-(a*r+o)),u=d>0?r:Math.min(Math.max(-r,-c),r),p=-d*d+u*(u+2*c)+l);else u=a>0?-r:r,d=Math.max(0,-(a*u+o)),p=-d*d+u*(u+2*c)+l;return n&&n.copy(this.origin).addScaledVector(this.direction,d),s&&s.copy(ia).addScaledVector(zs,u),p}intersectSphere(t,e){Cn.subVectors(t.center,this.origin);const n=Cn.dot(this.direction),s=Cn.dot(Cn)-n*n,r=t.radius*t.radius;if(s>r)return null;const a=Math.sqrt(r-s),o=n-a,c=n+a;return c<0?null:o<0?this.at(c,e):this.at(o,e)}intersectsSphere(t){return t.radius<0?!1:this.distanceSqToPoint(t.center)<=t.radius*t.radius}distanceToPlane(t){const e=t.normal.dot(this.direction);if(e===0)return t.distanceToPoint(this.origin)===0?0:null;const n=-(this.origin.dot(t.normal)+t.constant)/e;return n>=0?n:null}intersectPlane(t,e){const n=this.distanceToPlane(t);return n===null?null:this.at(n,e)}intersectsPlane(t){const e=t.distanceToPoint(this.origin);return e===0||t.normal.dot(this.direction)*e<0}intersectBox(t,e){let n,s,r,a,o,c;const l=1/this.direction.x,h=1/this.direction.y,d=1/this.direction.z,u=this.origin;return l>=0?(n=(t.min.x-u.x)*l,s=(t.max.x-u.x)*l):(n=(t.max.x-u.x)*l,s=(t.min.x-u.x)*l),h>=0?(r=(t.min.y-u.y)*h,a=(t.max.y-u.y)*h):(r=(t.max.y-u.y)*h,a=(t.min.y-u.y)*h),n>a||r>s||((r>n||isNaN(n))&&(n=r),(a<s||isNaN(s))&&(s=a),d>=0?(o=(t.min.z-u.z)*d,c=(t.max.z-u.z)*d):(o=(t.max.z-u.z)*d,c=(t.min.z-u.z)*d),n>c||o>s)||((o>n||n!==n)&&(n=o),(c<s||s!==s)&&(s=c),s<0)?null:this.at(n>=0?n:s,e)}intersectsBox(t){return this.intersectBox(t,Cn)!==null}intersectTriangle(t,e,n,s,r){sa.subVectors(e,t),ks.subVectors(n,t),ra.crossVectors(sa,ks);let a=this.direction.dot(ra),o;if(a>0){if(s)return null;o=1}else if(a<0)o=-1,a=-a;else return null;Yn.subVectors(this.origin,t);const c=o*this.direction.dot(ks.crossVectors(Yn,ks));if(c<0)return null;const l=o*this.direction.dot(sa.cross(Yn));if(l<0||c+l>a)return null;const h=-o*Yn.dot(ra);return h<0?null:this.at(h/a,r)}applyMatrix4(t){return this.origin.applyMatrix4(t),this.direction.transformDirection(t),this}equals(t){return t.origin.equals(this.origin)&&t.direction.equals(this.direction)}clone(){return new this.constructor().copy(this)}}class Zc extends bn{constructor(t){super(),this.isMeshBasicMaterial=!0,this.type="MeshBasicMaterial",this.color=new Ft(16777215),this.map=null,this.lightMap=null,this.lightMapIntensity=1,this.aoMap=null,this.aoMapIntensity=1,this.specularMap=null,this.alphaMap=null,this.envMap=null,this.envMapRotation=new Bn,this.combine=bo,this.reflectivity=1,this.refractionRatio=.98,this.wireframe=!1,this.wireframeLinewidth=1,this.wireframeLinecap="round",this.wireframeLinejoin="round",this.fog=!0,this.setValues(t)}copy(t){return super.copy(t),this.color.copy(t.color),this.map=t.map,this.lightMap=t.lightMap,this.lightMapIntensity=t.lightMapIntensity,this.aoMap=t.aoMap,this.aoMapIntensity=t.aoMapIntensity,this.specularMap=t.specularMap,this.alphaMap=t.alphaMap,this.envMap=t.envMap,this.envMapRotation.copy(t.envMapRotation),this.combine=t.combine,this.reflectivity=t.reflectivity,this.refractionRatio=t.refractionRatio,this.wireframe=t.wireframe,this.wireframeLinewidth=t.wireframeLinewidth,this.wireframeLinecap=t.wireframeLinecap,this.wireframeLinejoin=t.wireframeLinejoin,this.fog=t.fog,this}}const Tl=new se,li=new As,Vs=new vi,Al=new I,Gs=new I,Hs=new I,Ws=new I,aa=new I,Xs=new I,wl=new I,qs=new I;class Me extends Te{constructor(t=new _e,e=new Zc){super(),this.isMesh=!0,this.type="Mesh",this.geometry=t,this.material=e,this.morphTargetDictionary=void 0,this.morphTargetInfluences=void 0,this.count=1,this.updateMorphTargets()}copy(t,e){return super.copy(t,e),t.morphTargetInfluences!==void 0&&(this.morphTargetInfluences=t.morphTargetInfluences.slice()),t.morphTargetDictionary!==void 0&&(this.morphTargetDictionary=Object.assign({},t.morphTargetDictionary)),this.material=Array.isArray(t.material)?t.material.slice():t.material,this.geometry=t.geometry,this}updateMorphTargets(){const e=this.geometry.morphAttributes,n=Object.keys(e);if(n.length>0){const s=e[n[0]];if(s!==void 0){this.morphTargetInfluences=[],this.morphTargetDictionary={};for(let r=0,a=s.length;r<a;r++){const o=s[r].name||String(r);this.morphTargetInfluences.push(0),this.morphTargetDictionary[o]=r}}}}getVertexPosition(t,e){const n=this.geometry,s=n.attributes.position,r=n.morphAttributes.position,a=n.morphTargetsRelative;e.fromBufferAttribute(s,t);const o=this.morphTargetInfluences;if(r&&o){Xs.set(0,0,0);for(let c=0,l=r.length;c<l;c++){const h=o[c],d=r[c];h!==0&&(aa.fromBufferAttribute(d,t),a?Xs.addScaledVector(aa,h):Xs.addScaledVector(aa.sub(e),h))}e.add(Xs)}return e}raycast(t,e){const n=this.geometry,s=this.material,r=this.matrixWorld;s!==void 0&&(n.boundingSphere===null&&n.computeBoundingSphere(),Vs.copy(n.boundingSphere),Vs.applyMatrix4(r),li.copy(t.ray).recast(t.near),!(Vs.containsPoint(li.origin)===!1&&(li.intersectSphere(Vs,Al)===null||li.origin.distanceToSquared(Al)>(t.far-t.near)**2))&&(Tl.copy(r).invert(),li.copy(t.ray).applyMatrix4(Tl),!(n.boundingBox!==null&&li.intersectsBox(n.boundingBox)===!1)&&this._computeIntersections(t,e,li)))}_computeIntersections(t,e,n){let s;const r=this.geometry,a=this.material,o=r.index,c=r.attributes.position,l=r.attributes.uv,h=r.attributes.uv1,d=r.attributes.normal,u=r.groups,p=r.drawRange;if(o!==null)if(Array.isArray(a))for(let g=0,v=u.length;g<v;g++){const m=u[g],f=a[m.materialIndex],E=Math.max(m.start,p.start),w=Math.min(o.count,Math.min(m.start+m.count,p.start+p.count));for(let y=E,b=w;y<b;y+=3){const M=o.getX(y),A=o.getX(y+1),x=o.getX(y+2);s=Ys(this,f,t,n,l,h,d,M,A,x),s&&(s.faceIndex=Math.floor(y/3),s.face.materialIndex=m.materialIndex,e.push(s))}}else{const g=Math.max(0,p.start),v=Math.min(o.count,p.start+p.count);for(let m=g,f=v;m<f;m+=3){const E=o.getX(m),w=o.getX(m+1),y=o.getX(m+2);s=Ys(this,a,t,n,l,h,d,E,w,y),s&&(s.faceIndex=Math.floor(m/3),e.push(s))}}else if(c!==void 0)if(Array.isArray(a))for(let g=0,v=u.length;g<v;g++){const m=u[g],f=a[m.materialIndex],E=Math.max(m.start,p.start),w=Math.min(c.count,Math.min(m.start+m.count,p.start+p.count));for(let y=E,b=w;y<b;y+=3){const M=y,A=y+1,x=y+2;s=Ys(this,f,t,n,l,h,d,M,A,x),s&&(s.faceIndex=Math.floor(y/3),s.face.materialIndex=m.materialIndex,e.push(s))}}else{const g=Math.max(0,p.start),v=Math.min(c.count,p.start+p.count);for(let m=g,f=v;m<f;m+=3){const E=m,w=m+1,y=m+2;s=Ys(this,a,t,n,l,h,d,E,w,y),s&&(s.faceIndex=Math.floor(m/3),e.push(s))}}}}function Bu(i,t,e,n,s,r,a,o){let c;if(t.side===ke?c=n.intersectTriangle(a,r,s,!0,o):c=n.intersectTriangle(s,r,a,t.side===ni,o),c===null)return null;qs.copy(o),qs.applyMatrix4(i.matrixWorld);const l=e.ray.origin.distanceTo(qs);return l<e.near||l>e.far?null:{distance:l,point:qs.clone(),object:i}}function Ys(i,t,e,n,s,r,a,o,c,l){i.getVertexPosition(o,Gs),i.getVertexPosition(c,Hs),i.getVertexPosition(l,Ws);const h=Bu(i,t,e,n,Gs,Hs,Ws,wl);if(h){const d=new I;en.getBarycoord(wl,Gs,Hs,Ws,d),s&&(h.uv=en.getInterpolatedAttribute(s,o,c,l,d,new zt)),r&&(h.uv1=en.getInterpolatedAttribute(r,o,c,l,d,new zt)),a&&(h.normal=en.getInterpolatedAttribute(a,o,c,l,d,new I),h.normal.dot(n.direction)>0&&h.normal.multiplyScalar(-1));const u={a:o,b:c,c:l,normal:new I,materialIndex:0};en.getNormal(Gs,Hs,Ws,u.normal),h.face=u,h.barycoord=d}return h}class Jc extends Ne{constructor(t=null,e=1,n=1,s,r,a,o,c,l=Ee,h=Ee,d,u){super(null,a,o,c,l,h,s,r,d,u),this.isDataTexture=!0,this.image={data:t,width:e,height:n},this.generateMipmaps=!1,this.flipY=!1,this.unpackAlignment=1}}class Rl extends Pe{constructor(t,e,n,s=1){super(t,e,n),this.isInstancedBufferAttribute=!0,this.meshPerAttribute=s}copy(t){return super.copy(t),this.meshPerAttribute=t.meshPerAttribute,this}toJSON(){const t=super.toJSON();return t.meshPerAttribute=this.meshPerAttribute,t.isInstancedBufferAttribute=!0,t}}const Ii=new se,Cl=new se,$s=[],Pl=new Ke,zu=new se,ns=new Me,is=new vi;class Dl extends Me{constructor(t,e,n){super(t,e),this.isInstancedMesh=!0,this.instanceMatrix=new Rl(new Float32Array(n*16),16),this.instanceColor=null,this.morphTexture=null,this.count=n,this.boundingBox=null,this.boundingSphere=null;for(let s=0;s<n;s++)this.setMatrixAt(s,zu)}computeBoundingBox(){const t=this.geometry,e=this.count;this.boundingBox===null&&(this.boundingBox=new Ke),t.boundingBox===null&&t.computeBoundingBox(),this.boundingBox.makeEmpty();for(let n=0;n<e;n++)this.getMatrixAt(n,Ii),Pl.copy(t.boundingBox).applyMatrix4(Ii),this.boundingBox.union(Pl)}computeBoundingSphere(){const t=this.geometry,e=this.count;this.boundingSphere===null&&(this.boundingSphere=new vi),t.boundingSphere===null&&t.computeBoundingSphere(),this.boundingSphere.makeEmpty();for(let n=0;n<e;n++)this.getMatrixAt(n,Ii),is.copy(t.boundingSphere).applyMatrix4(Ii),this.boundingSphere.union(is)}copy(t,e){return super.copy(t,e),this.instanceMatrix.copy(t.instanceMatrix),t.morphTexture!==null&&(this.morphTexture=t.morphTexture.clone()),t.instanceColor!==null&&(this.instanceColor=t.instanceColor.clone()),this.count=t.count,t.boundingBox!==null&&(this.boundingBox=t.boundingBox.clone()),t.boundingSphere!==null&&(this.boundingSphere=t.boundingSphere.clone()),this}getColorAt(t,e){return this.instanceColor===null?e.setRGB(1,1,1):e.fromArray(this.instanceColor.array,t*3)}getMatrixAt(t,e){return e.fromArray(this.instanceMatrix.array,t*16)}getMorphAt(t,e){const n=e.morphTargetInfluences,s=this.morphTexture.source.data.data,r=n.length+1,a=t*r+1;for(let o=0;o<n.length;o++)n[o]=s[a+o]}raycast(t,e){const n=this.matrixWorld,s=this.count;if(ns.geometry=this.geometry,ns.material=this.material,ns.material!==void 0&&(this.boundingSphere===null&&this.computeBoundingSphere(),is.copy(this.boundingSphere),is.applyMatrix4(n),t.ray.intersectsSphere(is)!==!1))for(let r=0;r<s;r++){this.getMatrixAt(r,Ii),Cl.multiplyMatrices(n,Ii),ns.matrixWorld=Cl,ns.raycast(t,$s);for(let a=0,o=$s.length;a<o;a++){const c=$s[a];c.instanceId=r,c.object=this,e.push(c)}$s.length=0}}setColorAt(t,e){return this.instanceColor===null&&(this.instanceColor=new Rl(new Float32Array(this.instanceMatrix.count*3).fill(1),3)),e.toArray(this.instanceColor.array,t*3),this}setMatrixAt(t,e){return e.toArray(this.instanceMatrix.array,t*16),this}setMorphAt(t,e){const n=e.morphTargetInfluences,s=n.length+1;this.morphTexture===null&&(this.morphTexture=new Jc(new Float32Array(s*this.count),s,this.count,wo,cn));const r=this.morphTexture.source.data.data;let a=0;for(let l=0;l<n.length;l++)a+=n[l];const o=this.geometry.morphTargetsRelative?1:1-a,c=s*t;return r[c]=o,r.set(n,c+1),this}updateMorphTargets(){}dispose(){this.dispatchEvent({type:"dispose"}),this.morphTexture!==null&&(this.morphTexture.dispose(),this.morphTexture=null)}}const oa=new I,ku=new I,Vu=new Ht;class Qn{constructor(t=new I(1,0,0),e=0){this.isPlane=!0,this.normal=t,this.constant=e}set(t,e){return this.normal.copy(t),this.constant=e,this}setComponents(t,e,n,s){return this.normal.set(t,e,n),this.constant=s,this}setFromNormalAndCoplanarPoint(t,e){return this.normal.copy(t),this.constant=-e.dot(this.normal),this}setFromCoplanarPoints(t,e,n){const s=oa.subVectors(n,e).cross(ku.subVectors(t,e)).normalize();return this.setFromNormalAndCoplanarPoint(s,t),this}copy(t){return this.normal.copy(t.normal),this.constant=t.constant,this}normalize(){const t=1/this.normal.length();return this.normal.multiplyScalar(t),this.constant*=t,this}negate(){return this.constant*=-1,this.normal.negate(),this}distanceToPoint(t){return this.normal.dot(t)+this.constant}distanceToSphere(t){return this.distanceToPoint(t.center)-t.radius}projectPoint(t,e){return e.copy(t).addScaledVector(this.normal,-this.distanceToPoint(t))}intersectLine(t,e,n=!0){const s=t.delta(oa),r=this.normal.dot(s);if(r===0)return this.distanceToPoint(t.start)===0?e.copy(t.start):null;const a=-(t.start.dot(this.normal)+this.constant)/r;return n===!0&&(a<0||a>1)?null:e.copy(t.start).addScaledVector(s,a)}intersectsLine(t){const e=this.distanceToPoint(t.start),n=this.distanceToPoint(t.end);return e<0&&n>0||n<0&&e>0}intersectsBox(t){return t.intersectsPlane(this)}intersectsSphere(t){return t.intersectsPlane(this)}coplanarPoint(t){return t.copy(this.normal).multiplyScalar(-this.constant)}applyMatrix4(t,e){const n=e||Vu.getNormalMatrix(t),s=this.coplanarPoint(oa).applyMatrix4(t),r=this.normal.applyMatrix3(n).normalize();return this.constant=-s.dot(r),this}translate(t){return this.constant-=t.dot(this.normal),this}equals(t){return t.normal.equals(this.normal)&&t.constant===this.constant}clone(){return new this.constructor().copy(this)}}const ci=new vi,Gu=new zt(.5,.5),Ks=new I;class Uo{constructor(t=new Qn,e=new Qn,n=new Qn,s=new Qn,r=new Qn,a=new Qn){this.planes=[t,e,n,s,r,a]}set(t,e,n,s,r,a){const o=this.planes;return o[0].copy(t),o[1].copy(e),o[2].copy(n),o[3].copy(s),o[4].copy(r),o[5].copy(a),this}copy(t){const e=this.planes;for(let n=0;n<6;n++)e[n].copy(t.planes[n]);return this}setFromProjectionMatrix(t,e=vn,n=!1){const s=this.planes,r=t.elements,a=r[0],o=r[1],c=r[2],l=r[3],h=r[4],d=r[5],u=r[6],p=r[7],g=r[8],v=r[9],m=r[10],f=r[11],E=r[12],w=r[13],y=r[14],b=r[15];if(s[0].setComponents(l-a,p-h,f-g,b-E).normalize(),s[1].setComponents(l+a,p+h,f+g,b+E).normalize(),s[2].setComponents(l+o,p+d,f+v,b+w).normalize(),s[3].setComponents(l-o,p-d,f-v,b-w).normalize(),n)s[4].setComponents(c,u,m,y).normalize(),s[5].setComponents(l-c,p-u,f-m,b-y).normalize();else if(s[4].setComponents(l-c,p-u,f-m,b-y).normalize(),e===vn)s[5].setComponents(l+c,p+u,f+m,b+y).normalize();else if(e===Ss)s[5].setComponents(c,u,m,y).normalize();else throw new Error("THREE.Frustum.setFromProjectionMatrix(): Invalid coordinate system: "+e);return this}intersectsObject(t){if(t.boundingSphere!==void 0)t.boundingSphere===null&&t.computeBoundingSphere(),ci.copy(t.boundingSphere).applyMatrix4(t.matrixWorld);else{const e=t.geometry;e.boundingSphere===null&&e.computeBoundingSphere(),ci.copy(e.boundingSphere).applyMatrix4(t.matrixWorld)}return this.intersectsSphere(ci)}intersectsSprite(t){ci.center.set(0,0,0);const e=Gu.distanceTo(t.center);return ci.radius=.7071067811865476+e,ci.applyMatrix4(t.matrixWorld),this.intersectsSphere(ci)}intersectsSphere(t){const e=this.planes,n=t.center,s=-t.radius;for(let r=0;r<6;r++)if(e[r].distanceToPoint(n)<s)return!1;return!0}intersectsBox(t){const e=this.planes;for(let n=0;n<6;n++){const s=e[n];if(Ks.x=s.normal.x>0?t.max.x:t.min.x,Ks.y=s.normal.y>0?t.max.y:t.min.y,Ks.z=s.normal.z>0?t.max.z:t.min.z,s.distanceToPoint(Ks)<0)return!1}return!0}containsPoint(t){const e=this.planes;for(let n=0;n<6;n++)if(e[n].distanceToPoint(t)<0)return!1;return!0}clone(){return new this.constructor().copy(this)}}class gi extends bn{constructor(t){super(),this.isLineBasicMaterial=!0,this.type="LineBasicMaterial",this.color=new Ft(16777215),this.map=null,this.linewidth=1,this.linecap="round",this.linejoin="round",this.fog=!0,this.setValues(t)}copy(t){return super.copy(t),this.color.copy(t.color),this.map=t.map,this.linewidth=t.linewidth,this.linecap=t.linecap,this.linejoin=t.linejoin,this.fog=t.fog,this}}const Er=new I,Tr=new I,Ll=new se,ss=new As,Zs=new vi,la=new I,Il=new I;class Hu extends Te{constructor(t=new _e,e=new gi){super(),this.isLine=!0,this.type="Line",this.geometry=t,this.material=e,this.morphTargetDictionary=void 0,this.morphTargetInfluences=void 0,this.updateMorphTargets()}copy(t,e){return super.copy(t,e),this.material=Array.isArray(t.material)?t.material.slice():t.material,this.geometry=t.geometry,this}computeLineDistances(){const t=this.geometry;if(t.index===null){const e=t.attributes.position,n=[0];for(let s=1,r=e.count;s<r;s++)Er.fromBufferAttribute(e,s-1),Tr.fromBufferAttribute(e,s),n[s]=n[s-1],n[s]+=Er.distanceTo(Tr);t.setAttribute("lineDistance",new ae(n,1))}else Ut("Line.computeLineDistances(): Computation only possible with non-indexed BufferGeometry.");return this}raycast(t,e){const n=this.geometry,s=this.matrixWorld,r=t.params.Line.threshold,a=n.drawRange;if(n.boundingSphere===null&&n.computeBoundingSphere(),Zs.copy(n.boundingSphere),Zs.applyMatrix4(s),Zs.radius+=r,t.ray.intersectsSphere(Zs)===!1)return;Ll.copy(s).invert(),ss.copy(t.ray).applyMatrix4(Ll);const o=r/((this.scale.x+this.scale.y+this.scale.z)/3),c=o*o,l=this.isLineSegments?2:1,h=n.index,u=n.attributes.position;if(h!==null){const p=Math.max(0,a.start),g=Math.min(h.count,a.start+a.count);for(let v=p,m=g-1;v<m;v+=l){const f=h.getX(v),E=h.getX(v+1),w=Js(this,t,ss,c,f,E,v);w&&e.push(w)}if(this.isLineLoop){const v=h.getX(g-1),m=h.getX(p),f=Js(this,t,ss,c,v,m,g-1);f&&e.push(f)}}else{const p=Math.max(0,a.start),g=Math.min(u.count,a.start+a.count);for(let v=p,m=g-1;v<m;v+=l){const f=Js(this,t,ss,c,v,v+1,v);f&&e.push(f)}if(this.isLineLoop){const v=Js(this,t,ss,c,g-1,p,g-1);v&&e.push(v)}}}updateMorphTargets(){const e=this.geometry.morphAttributes,n=Object.keys(e);if(n.length>0){const s=e[n[0]];if(s!==void 0){this.morphTargetInfluences=[],this.morphTargetDictionary={};for(let r=0,a=s.length;r<a;r++){const o=s[r].name||String(r);this.morphTargetInfluences.push(0),this.morphTargetDictionary[o]=r}}}}}function Js(i,t,e,n,s,r,a){const o=i.geometry.attributes.position;if(Er.fromBufferAttribute(o,s),Tr.fromBufferAttribute(o,r),e.distanceSqToSegment(Er,Tr,la,Il)>n)return;la.applyMatrix4(i.matrixWorld);const l=t.ray.origin.distanceTo(la);if(!(l<t.near||l>t.far))return{distance:l,point:Il.clone().applyMatrix4(i.matrixWorld),index:a,face:null,faceIndex:null,barycoord:null,object:i}}const Nl=new I,Ul=new I;class bs extends Hu{constructor(t,e){super(t,e),this.isLineSegments=!0,this.type="LineSegments"}computeLineDistances(){const t=this.geometry;if(t.index===null){const e=t.attributes.position,n=[];for(let s=0,r=e.count;s<r;s+=2)Nl.fromBufferAttribute(e,s),Ul.fromBufferAttribute(e,s+1),n[s]=s===0?0:n[s-1],n[s+1]=n[s]+Nl.distanceTo(Ul);t.setAttribute("lineDistance",new ae(n,1))}else Ut("LineSegments.computeLineDistances(): Computation only possible with non-indexed BufferGeometry.");return this}}class us extends bn{constructor(t){super(),this.isPointsMaterial=!0,this.type="PointsMaterial",this.color=new Ft(16777215),this.map=null,this.alphaMap=null,this.size=1,this.sizeAttenuation=!0,this.fog=!0,this.setValues(t)}copy(t){return super.copy(t),this.color.copy(t.color),this.map=t.map,this.alphaMap=t.alphaMap,this.size=t.size,this.sizeAttenuation=t.sizeAttenuation,this.fog=t.fog,this}}const Fl=new se,_o=new As,js=new vi,Qs=new I;class ca extends Te{constructor(t=new _e,e=new us){super(),this.isPoints=!0,this.type="Points",this.geometry=t,this.material=e,this.morphTargetDictionary=void 0,this.morphTargetInfluences=void 0,this.updateMorphTargets()}copy(t,e){return super.copy(t,e),this.material=Array.isArray(t.material)?t.material.slice():t.material,this.geometry=t.geometry,this}raycast(t,e){const n=this.geometry,s=this.matrixWorld,r=t.params.Points.threshold,a=n.drawRange;if(n.boundingSphere===null&&n.computeBoundingSphere(),js.copy(n.boundingSphere),js.applyMatrix4(s),js.radius+=r,t.ray.intersectsSphere(js)===!1)return;Fl.copy(s).invert(),_o.copy(t.ray).applyMatrix4(Fl);const o=r/((this.scale.x+this.scale.y+this.scale.z)/3),c=o*o,l=n.index,d=n.attributes.position;if(l!==null){const u=Math.max(0,a.start),p=Math.min(l.count,a.start+a.count);for(let g=u,v=p;g<v;g++){const m=l.getX(g);Qs.fromBufferAttribute(d,m),Ol(Qs,m,c,s,t,e,this)}}else{const u=Math.max(0,a.start),p=Math.min(d.count,a.start+a.count);for(let g=u,v=p;g<v;g++)Qs.fromBufferAttribute(d,g),Ol(Qs,g,c,s,t,e,this)}}updateMorphTargets(){const e=this.geometry.morphAttributes,n=Object.keys(e);if(n.length>0){const s=e[n[0]];if(s!==void 0){this.morphTargetInfluences=[],this.morphTargetDictionary={};for(let r=0,a=s.length;r<a;r++){const o=s[r].name||String(r);this.morphTargetInfluences.push(0),this.morphTargetDictionary[o]=r}}}}}function Ol(i,t,e,n,s,r,a){const o=_o.distanceSqToPoint(i);if(o<e){const c=new I;_o.closestPointToPoint(i,c),c.applyMatrix4(n);const l=s.ray.origin.distanceTo(c);if(l<s.near||l>s.far)return;r.push({distance:l,distanceToRay:Math.sqrt(o),point:c,index:t,face:null,faceIndex:null,barycoord:null,object:a})}}class jc extends Ne{constructor(t=[],e=_i,n,s,r,a,o,c,l,h){super(t,e,n,s,r,a,o,c,l,h),this.isCubeTexture=!0,this.flipY=!1}get images(){return this.image}set images(t){this.image=t}}class Yi extends Ne{constructor(t,e,n=yn,s,r,a,o=Ee,c=Ee,l,h=Fn,d=1){if(h!==Fn&&h!==mi)throw new Error("THREE.DepthTexture: format must be either THREE.DepthFormat or THREE.DepthStencilFormat");const u={width:t,height:e,depth:d};super(u,s,r,a,o,c,h,n,l),this.isDepthTexture=!0,this.flipY=!1,this.generateMipmaps=!1,this.compareFunction=null}copy(t){return super.copy(t),this.source=new Io(Object.assign({},t.image)),this.compareFunction=t.compareFunction,this}toJSON(t){const e=super.toJSON(t);return this.compareFunction!==null&&(e.compareFunction=this.compareFunction),e}}class Wu extends Yi{constructor(t,e=yn,n=_i,s,r,a=Ee,o=Ee,c,l=Fn){const h={width:t,height:t,depth:1},d=[h,h,h,h,h,h];super(t,t,e,n,s,r,a,o,c,l),this.image=d,this.isCubeDepthTexture=!0,this.isCubeTexture=!0}get images(){return this.image}set images(t){this.image=t}}class Qc extends Ne{constructor(t=null){super(),this.sourceTexture=t,this.isExternalTexture=!0}copy(t){return super.copy(t),this.sourceTexture=t.sourceTexture,this}}class zn extends _e{constructor(t=1,e=1,n=1,s=1,r=1,a=1){super(),this.type="BoxGeometry",this.parameters={width:t,height:e,depth:n,widthSegments:s,heightSegments:r,depthSegments:a};const o=this;s=Math.floor(s),r=Math.floor(r),a=Math.floor(a);const c=[],l=[],h=[],d=[];let u=0,p=0;g("z","y","x",-1,-1,n,e,t,a,r,0),g("z","y","x",1,-1,n,e,-t,a,r,1),g("x","z","y",1,1,t,n,e,s,a,2),g("x","z","y",1,-1,t,n,-e,s,a,3),g("x","y","z",1,-1,t,e,n,s,r,4),g("x","y","z",-1,-1,t,e,-n,s,r,5),this.setIndex(c),this.setAttribute("position",new ae(l,3)),this.setAttribute("normal",new ae(h,3)),this.setAttribute("uv",new ae(d,2));function g(v,m,f,E,w,y,b,M,A,x,T){const N=y/A,D=b/x,B=y/2,$=b/2,J=M/2,z=A+1,H=x+1;let W=0,tt=0;const Q=new I;for(let ht=0;ht<H;ht++){const ut=ht*D-$;for(let St=0;St<z;St++){const Bt=St*N-B;Q[v]=Bt*E,Q[m]=ut*w,Q[f]=J,l.push(Q.x,Q.y,Q.z),Q[v]=0,Q[m]=0,Q[f]=M>0?1:-1,h.push(Q.x,Q.y,Q.z),d.push(St/A),d.push(1-ht/x),W+=1}}for(let ht=0;ht<x;ht++)for(let ut=0;ut<A;ut++){const St=u+ut+z*ht,Bt=u+ut+z*(ht+1),Yt=u+(ut+1)+z*(ht+1),Vt=u+(ut+1)+z*ht;c.push(St,Bt,Vt),c.push(Bt,Yt,Vt),tt+=6}o.addGroup(p,tt,T),p+=tt,u+=W}}copy(t){return super.copy(t),this.parameters=Object.assign({},t.parameters),this}static fromJSON(t){return new zn(t.width,t.height,t.depth,t.widthSegments,t.heightSegments,t.depthSegments)}}const tr=new I,er=new I,ha=new I,nr=new en;class th extends _e{constructor(t=null,e=1){if(super(),this.type="EdgesGeometry",this.parameters={geometry:t,thresholdAngle:e},t!==null){const s=Math.pow(10,4),r=Math.cos(ps*e),a=t.getIndex(),o=t.getAttribute("position"),c=a?a.count:o.count,l=[0,0,0],h=["a","b","c"],d=new Array(3),u={},p=[];for(let g=0;g<c;g+=3){a?(l[0]=a.getX(g),l[1]=a.getX(g+1),l[2]=a.getX(g+2)):(l[0]=g,l[1]=g+1,l[2]=g+2);const{a:v,b:m,c:f}=nr;if(v.fromBufferAttribute(o,l[0]),m.fromBufferAttribute(o,l[1]),f.fromBufferAttribute(o,l[2]),nr.getNormal(ha),d[0]=`${Math.round(v.x*s)},${Math.round(v.y*s)},${Math.round(v.z*s)}`,d[1]=`${Math.round(m.x*s)},${Math.round(m.y*s)},${Math.round(m.z*s)}`,d[2]=`${Math.round(f.x*s)},${Math.round(f.y*s)},${Math.round(f.z*s)}`,!(d[0]===d[1]||d[1]===d[2]||d[2]===d[0]))for(let E=0;E<3;E++){const w=(E+1)%3,y=d[E],b=d[w],M=nr[h[E]],A=nr[h[w]],x=`${y}_${b}`,T=`${b}_${y}`;T in u&&u[T]?(ha.dot(u[T].normal)<=r&&(p.push(M.x,M.y,M.z),p.push(A.x,A.y,A.z)),u[T]=null):x in u||(u[x]={index0:l[E],index1:l[w],normal:ha.clone()})}}for(const g in u)if(u[g]){const{index0:v,index1:m}=u[g];tr.fromBufferAttribute(o,v),er.fromBufferAttribute(o,m),p.push(tr.x,tr.y,tr.z),p.push(er.x,er.y,er.z)}this.setAttribute("position",new ae(p,3))}}copy(t){return super.copy(t),this.parameters=Object.assign({},t.parameters),this}}class ws extends _e{constructor(t=1,e=1,n=1,s=1){super(),this.type="PlaneGeometry",this.parameters={width:t,height:e,widthSegments:n,heightSegments:s};const r=t/2,a=e/2,o=Math.floor(n),c=Math.floor(s),l=o+1,h=c+1,d=t/o,u=e/c,p=[],g=[],v=[],m=[];for(let f=0;f<h;f++){const E=f*u-a;for(let w=0;w<l;w++){const y=w*d-r;g.push(y,-E,0),v.push(0,0,1),m.push(w/o),m.push(1-f/c)}}for(let f=0;f<c;f++)for(let E=0;E<o;E++){const w=E+l*f,y=E+l*(f+1),b=E+1+l*(f+1),M=E+1+l*f;p.push(w,y,M),p.push(y,b,M)}this.setIndex(p),this.setAttribute("position",new ae(g,3)),this.setAttribute("normal",new ae(v,3)),this.setAttribute("uv",new ae(m,2))}copy(t){return super.copy(t),this.parameters=Object.assign({},t.parameters),this}static fromJSON(t){return new ws(t.width,t.height,t.widthSegments,t.heightSegments)}}class Fo extends _e{constructor(t=1,e=32,n=16,s=0,r=Math.PI*2,a=0,o=Math.PI){super(),this.type="SphereGeometry",this.parameters={radius:t,widthSegments:e,heightSegments:n,phiStart:s,phiLength:r,thetaStart:a,thetaLength:o},e=Math.max(3,Math.floor(e)),n=Math.max(2,Math.floor(n));const c=Math.min(a+o,Math.PI);let l=0;const h=[],d=new I,u=new I,p=[],g=[],v=[],m=[];for(let f=0;f<=n;f++){const E=[],w=f/n,y=a+w*o,b=t*Math.cos(y),M=Math.sqrt(t*t-b*b);let A=0;f===0&&a===0?A=.5/e:f===n&&c===Math.PI&&(A=-.5/e);for(let x=0;x<=e;x++){const T=x/e,N=s+T*r;d.x=-M*Math.cos(N),d.y=b,d.z=M*Math.sin(N),g.push(d.x,d.y,d.z),u.copy(d).normalize(),v.push(u.x,u.y,u.z),m.push(T+A,1-w),E.push(l++)}h.push(E)}for(let f=0;f<n;f++)for(let E=0;E<e;E++){const w=h[f][E+1],y=h[f][E],b=h[f+1][E],M=h[f+1][E+1];(f!==0||a>0)&&p.push(w,y,M),(f!==n-1||c<Math.PI)&&p.push(y,b,M)}this.setIndex(p),this.setAttribute("position",new ae(g,3)),this.setAttribute("normal",new ae(v,3)),this.setAttribute("uv",new ae(m,2))}copy(t){return super.copy(t),this.parameters=Object.assign({},t.parameters),this}static fromJSON(t){return new Fo(t.radius,t.widthSegments,t.heightSegments,t.phiStart,t.phiLength,t.thetaStart,t.thetaLength)}}function $i(i){const t={};for(const e in i){t[e]={};for(const n in i[e]){const s=i[e][n];if(Bl(s))s.isRenderTargetTexture?(Ut("UniformsUtils: Textures of render targets cannot be cloned via cloneUniforms() or mergeUniforms()."),t[e][n]=null):t[e][n]=s.clone();else if(Array.isArray(s))if(Bl(s[0])){const r=[];for(let a=0,o=s.length;a<o;a++)r[a]=s[a].clone();t[e][n]=r}else t[e][n]=s.slice();else t[e][n]=s}}return t}function Ue(i){const t={};for(let e=0;e<i.length;e++){const n=$i(i[e]);for(const s in n)t[s]=n[s]}return t}function Bl(i){return i&&(i.isColor||i.isMatrix3||i.isMatrix4||i.isVector2||i.isVector3||i.isVector4||i.isTexture||i.isQuaternion)}function Xu(i){const t=[];for(let e=0;e<i.length;e++)t.push(i[e].clone());return t}function eh(i){const t=i.getRenderTarget();return t===null?i.outputColorSpace:t.isXRRenderTarget===!0?t.texture.colorSpace:Qt.workingColorSpace}const qu={clone:$i,merge:Ue};var Yu=`void main() {
	gl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );
}`,$u=`void main() {
	gl_FragColor = vec4( 1.0, 0.0, 0.0, 1.0 );
}`;class En extends bn{constructor(t){super(),this.isShaderMaterial=!0,this.type="ShaderMaterial",this.defines={},this.uniforms={},this.uniformsGroups=[],this.vertexShader=Yu,this.fragmentShader=$u,this.linewidth=1,this.wireframe=!1,this.wireframeLinewidth=1,this.fog=!1,this.lights=!1,this.clipping=!1,this.forceSinglePass=!0,this.extensions={clipCullDistance:!1,multiDraw:!1},this.defaultAttributeValues={color:[1,1,1],uv:[0,0],uv1:[0,0]},this.index0AttributeName=void 0,this.uniformsNeedUpdate=!1,this.glslVersion=null,t!==void 0&&this.setValues(t)}copy(t){return super.copy(t),this.fragmentShader=t.fragmentShader,this.vertexShader=t.vertexShader,this.uniforms=$i(t.uniforms),this.uniformsGroups=Xu(t.uniformsGroups),this.defines=Object.assign({},t.defines),this.wireframe=t.wireframe,this.wireframeLinewidth=t.wireframeLinewidth,this.fog=t.fog,this.lights=t.lights,this.clipping=t.clipping,this.extensions=Object.assign({},t.extensions),this.glslVersion=t.glslVersion,this.defaultAttributeValues=Object.assign({},t.defaultAttributeValues),this.index0AttributeName=t.index0AttributeName,this.uniformsNeedUpdate=t.uniformsNeedUpdate,this}toJSON(t){const e=super.toJSON(t);e.glslVersion=this.glslVersion,e.uniforms={};for(const s in this.uniforms){const a=this.uniforms[s].value;a&&a.isTexture?e.uniforms[s]={type:"t",value:a.toJSON(t).uuid}:a&&a.isColor?e.uniforms[s]={type:"c",value:a.getHex()}:a&&a.isVector2?e.uniforms[s]={type:"v2",value:a.toArray()}:a&&a.isVector3?e.uniforms[s]={type:"v3",value:a.toArray()}:a&&a.isVector4?e.uniforms[s]={type:"v4",value:a.toArray()}:a&&a.isMatrix3?e.uniforms[s]={type:"m3",value:a.toArray()}:a&&a.isMatrix4?e.uniforms[s]={type:"m4",value:a.toArray()}:e.uniforms[s]={value:a}}Object.keys(this.defines).length>0&&(e.defines=this.defines),e.vertexShader=this.vertexShader,e.fragmentShader=this.fragmentShader,e.lights=this.lights,e.clipping=this.clipping;const n={};for(const s in this.extensions)this.extensions[s]===!0&&(n[s]=!0);return Object.keys(n).length>0&&(e.extensions=n),e}fromJSON(t,e){if(super.fromJSON(t,e),t.uniforms!==void 0)for(const n in t.uniforms){const s=t.uniforms[n];switch(this.uniforms[n]={},s.type){case"t":this.uniforms[n].value=e[s.value]||null;break;case"c":this.uniforms[n].value=new Ft().setHex(s.value);break;case"v2":this.uniforms[n].value=new zt().fromArray(s.value);break;case"v3":this.uniforms[n].value=new I().fromArray(s.value);break;case"v4":this.uniforms[n].value=new pe().fromArray(s.value);break;case"m3":this.uniforms[n].value=new Ht().fromArray(s.value);break;case"m4":this.uniforms[n].value=new se().fromArray(s.value);break;default:this.uniforms[n].value=s.value}}if(t.defines!==void 0&&(this.defines=t.defines),t.vertexShader!==void 0&&(this.vertexShader=t.vertexShader),t.fragmentShader!==void 0&&(this.fragmentShader=t.fragmentShader),t.glslVersion!==void 0&&(this.glslVersion=t.glslVersion),t.extensions!==void 0)for(const n in t.extensions)this.extensions[n]=t.extensions[n];return t.lights!==void 0&&(this.lights=t.lights),t.clipping!==void 0&&(this.clipping=t.clipping),this}}class Ku extends En{constructor(t){super(t),this.isRawShaderMaterial=!0,this.type="RawShaderMaterial"}}class Ki extends bn{constructor(t){super(),this.isMeshStandardMaterial=!0,this.type="MeshStandardMaterial",this.defines={STANDARD:""},this.color=new Ft(16777215),this.roughness=1,this.metalness=0,this.map=null,this.lightMap=null,this.lightMapIntensity=1,this.aoMap=null,this.aoMapIntensity=1,this.emissive=new Ft(0),this.emissiveIntensity=1,this.emissiveMap=null,this.bumpMap=null,this.bumpScale=1,this.normalMap=null,this.normalMapType=Sr,this.normalScale=new zt(1,1),this.displacementMap=null,this.displacementScale=1,this.displacementBias=0,this.roughnessMap=null,this.metalnessMap=null,this.alphaMap=null,this.envMap=null,this.envMapRotation=new Bn,this.envMapIntensity=1,this.wireframe=!1,this.wireframeLinewidth=1,this.wireframeLinecap="round",this.wireframeLinejoin="round",this.flatShading=!1,this.fog=!0,this.setValues(t)}copy(t){return super.copy(t),this.defines={STANDARD:""},this.color.copy(t.color),this.roughness=t.roughness,this.metalness=t.metalness,this.map=t.map,this.lightMap=t.lightMap,this.lightMapIntensity=t.lightMapIntensity,this.aoMap=t.aoMap,this.aoMapIntensity=t.aoMapIntensity,this.emissive.copy(t.emissive),this.emissiveMap=t.emissiveMap,this.emissiveIntensity=t.emissiveIntensity,this.bumpMap=t.bumpMap,this.bumpScale=t.bumpScale,this.normalMap=t.normalMap,this.normalMapType=t.normalMapType,this.normalScale.copy(t.normalScale),this.displacementMap=t.displacementMap,this.displacementScale=t.displacementScale,this.displacementBias=t.displacementBias,this.roughnessMap=t.roughnessMap,this.metalnessMap=t.metalnessMap,this.alphaMap=t.alphaMap,this.envMap=t.envMap,this.envMapRotation.copy(t.envMapRotation),this.envMapIntensity=t.envMapIntensity,this.wireframe=t.wireframe,this.wireframeLinewidth=t.wireframeLinewidth,this.wireframeLinecap=t.wireframeLinecap,this.wireframeLinejoin=t.wireframeLinejoin,this.flatShading=t.flatShading,this.fog=t.fog,this}}class ds extends bn{constructor(t){super(),this.isMeshPhongMaterial=!0,this.type="MeshPhongMaterial",this.color=new Ft(16777215),this.specular=new Ft(1118481),this.shininess=30,this.map=null,this.lightMap=null,this.lightMapIntensity=1,this.aoMap=null,this.aoMapIntensity=1,this.emissive=new Ft(0),this.emissiveIntensity=1,this.emissiveMap=null,this.bumpMap=null,this.bumpScale=1,this.normalMap=null,this.normalMapType=Sr,this.normalScale=new zt(1,1),this.displacementMap=null,this.displacementScale=1,this.displacementBias=0,this.specularMap=null,this.alphaMap=null,this.envMap=null,this.envMapRotation=new Bn,this.combine=bo,this.reflectivity=1,this.envMapIntensity=1,this.refractionRatio=.98,this.wireframe=!1,this.wireframeLinewidth=1,this.wireframeLinecap="round",this.wireframeLinejoin="round",this.flatShading=!1,this.fog=!0,this.setValues(t)}copy(t){return super.copy(t),this.color.copy(t.color),this.specular.copy(t.specular),this.shininess=t.shininess,this.map=t.map,this.lightMap=t.lightMap,this.lightMapIntensity=t.lightMapIntensity,this.aoMap=t.aoMap,this.aoMapIntensity=t.aoMapIntensity,this.emissive.copy(t.emissive),this.emissiveMap=t.emissiveMap,this.emissiveIntensity=t.emissiveIntensity,this.bumpMap=t.bumpMap,this.bumpScale=t.bumpScale,this.normalMap=t.normalMap,this.normalMapType=t.normalMapType,this.normalScale.copy(t.normalScale),this.displacementMap=t.displacementMap,this.displacementScale=t.displacementScale,this.displacementBias=t.displacementBias,this.specularMap=t.specularMap,this.alphaMap=t.alphaMap,this.envMap=t.envMap,this.envMapRotation.copy(t.envMapRotation),this.combine=t.combine,this.reflectivity=t.reflectivity,this.envMapIntensity=t.envMapIntensity,this.refractionRatio=t.refractionRatio,this.wireframe=t.wireframe,this.wireframeLinewidth=t.wireframeLinewidth,this.wireframeLinecap=t.wireframeLinecap,this.wireframeLinejoin=t.wireframeLinejoin,this.flatShading=t.flatShading,this.fog=t.fog,this}}class Zu extends bn{constructor(t){super(),this.isMeshDepthMaterial=!0,this.type="MeshDepthMaterial",this.depthPacking=ou,this.map=null,this.alphaMap=null,this.displacementMap=null,this.displacementScale=1,this.displacementBias=0,this.wireframe=!1,this.wireframeLinewidth=1,this.setValues(t)}copy(t){return super.copy(t),this.depthPacking=t.depthPacking,this.map=t.map,this.alphaMap=t.alphaMap,this.displacementMap=t.displacementMap,this.displacementScale=t.displacementScale,this.displacementBias=t.displacementBias,this.wireframe=t.wireframe,this.wireframeLinewidth=t.wireframeLinewidth,this}}class Ju extends bn{constructor(t){super(),this.isMeshDistanceMaterial=!0,this.type="MeshDistanceMaterial",this.map=null,this.alphaMap=null,this.displacementMap=null,this.displacementScale=1,this.displacementBias=0,this.setValues(t)}copy(t){return super.copy(t),this.map=t.map,this.alphaMap=t.alphaMap,this.displacementMap=t.displacementMap,this.displacementScale=t.displacementScale,this.displacementBias=t.displacementBias,this}}const ms={enabled:!1,files:{},add:function(i,t){this.enabled!==!1&&(zl(i)||(this.files[i]=t))},get:function(i){if(this.enabled!==!1&&!zl(i))return this.files[i]},remove:function(i){delete this.files[i]},clear:function(){this.files={}}};function zl(i){try{const t=i.slice(i.indexOf(":")+1);return new URL(t).protocol==="blob:"}catch{return!1}}class ju{constructor(t,e,n){const s=this;let r=!1,a=0,o=0,c;const l=[];this.onStart=void 0,this.onLoad=t,this.onProgress=e,this.onError=n,this._abortController=null,this.itemStart=function(h){o++,r===!1&&s.onStart!==void 0&&s.onStart(h,a,o),r=!0},this.itemEnd=function(h){a++,s.onProgress!==void 0&&s.onProgress(h,a,o),a===o&&(r=!1,s.onLoad!==void 0&&s.onLoad())},this.itemError=function(h){s.onError!==void 0&&s.onError(h)},this.resolveURL=function(h){return h=h.normalize("NFC"),c?c(h):h},this.setURLModifier=function(h){return c=h,this},this.addHandler=function(h,d){return l.push(h,d),this},this.removeHandler=function(h){const d=l.indexOf(h);return d!==-1&&l.splice(d,2),this},this.getHandler=function(h){for(let d=0,u=l.length;d<u;d+=2){const p=l[d],g=l[d+1];if(p.global&&(p.lastIndex=0),p.test(h))return g}return null},this.abort=function(){return this.abortController.abort(),this._abortController=null,this}}get abortController(){return this._abortController||(this._abortController=new AbortController),this._abortController}}const Qu=new ju;class ii{constructor(t){this.manager=t!==void 0?t:Qu,this.crossOrigin="anonymous",this.withCredentials=!1,this.path="",this.resourcePath="",this.requestHeader={},typeof __THREE_DEVTOOLS__<"u"&&__THREE_DEVTOOLS__.dispatchEvent(new CustomEvent("observe",{detail:this}))}load(){}loadAsync(t,e){const n=this;return new Promise(function(s,r){n.load(t,s,e,r)})}parse(){}setCrossOrigin(t){return this.crossOrigin=t,this}setWithCredentials(t){return this.withCredentials=t,this}setPath(t){return this.path=t,this}setResourcePath(t){return this.resourcePath=t,this}setRequestHeader(t){return this.requestHeader=t,this}abort(){return this}}ii.DEFAULT_MATERIAL_NAME="__DEFAULT";const Pn={};class td extends Error{constructor(t,e){super(t),this.response=e}}class Oo extends ii{constructor(t){super(t),this.mimeType="",this.responseType="",this._abortController=new AbortController}load(t,e,n,s){t===void 0&&(t=""),this.path!==void 0&&(t=this.path+t),t=this.manager.resolveURL(t);const r=ms.get(`file:${t}`);if(r!==void 0){this.manager.itemStart(t),setTimeout(()=>{e&&e(r),this.manager.itemEnd(t)},0);return}if(Pn[t]!==void 0){Pn[t].push({onLoad:e,onProgress:n,onError:s});return}Pn[t]=[],Pn[t].push({onLoad:e,onProgress:n,onError:s});const a=new Request(t,{headers:new Headers(this.requestHeader),credentials:this.withCredentials?"include":"same-origin",signal:typeof AbortSignal.any=="function"?AbortSignal.any([this._abortController.signal,this.manager.abortController.signal]):this._abortController.signal}),o=this.mimeType,c=this.responseType;fetch(a).then(l=>{if(l.status===200||l.status===0){if(l.status===0&&Ut("FileLoader: HTTP Status 0 received."),typeof ReadableStream>"u"||l.body===void 0||l.body.getReader===void 0)return l;const h=Pn[t],d=l.body.getReader(),u=l.headers.get("X-File-Size")||l.headers.get("Content-Length"),p=u?parseInt(u):0,g=p!==0;let v=0;const m=new ReadableStream({start(f){E();function E(){d.read().then(({done:w,value:y})=>{if(w)f.close();else{v+=y.byteLength;const b=new ProgressEvent("progress",{lengthComputable:g,loaded:v,total:p});for(let M=0,A=h.length;M<A;M++){const x=h[M];x.onProgress&&x.onProgress(b)}f.enqueue(y),E()}},w=>{f.error(w)})}}});return new Response(m)}else throw new td(`fetch for "${l.url}" responded with ${l.status}: ${l.statusText}`,l)}).then(l=>{switch(c){case"arraybuffer":return l.arrayBuffer();case"blob":return l.blob();case"document":return l.text().then(h=>new DOMParser().parseFromString(h,o));case"json":return l.json();default:if(o==="")return l.text();{const d=/charset="?([^;"\s]*)"?/i.exec(o),u=d&&d[1]?d[1].toLowerCase():void 0,p=new TextDecoder(u);return l.arrayBuffer().then(g=>p.decode(g))}}}).then(l=>{ms.add(`file:${t}`,l);const h=Pn[t];delete Pn[t];for(let d=0,u=h.length;d<u;d++){const p=h[d];p.onLoad&&p.onLoad(l)}}).catch(l=>{const h=Pn[t];if(h===void 0)throw this.manager.itemError(t),l;delete Pn[t];for(let d=0,u=h.length;d<u;d++){const p=h[d];p.onError&&p.onError(l)}this.manager.itemError(t)}).finally(()=>{this.manager.itemEnd(t)}),this.manager.itemStart(t)}setResponseType(t){return this.responseType=t,this}setMimeType(t){return this.mimeType=t,this}abort(){return this._abortController.abort(),this._abortController=new AbortController,this}}const Ni=new WeakMap;class ed extends ii{constructor(t){super(t)}load(t,e,n,s){this.path!==void 0&&(t=this.path+t),t=this.manager.resolveURL(t);const r=this,a=ms.get(`image:${t}`);if(a!==void 0){if(a.complete===!0)r.manager.itemStart(t),setTimeout(function(){e&&e(a),r.manager.itemEnd(t)},0);else{let d=Ni.get(a);d===void 0&&(d=[],Ni.set(a,d)),d.push({onLoad:e,onError:s})}return a}const o=ys("img");function c(){h(),e&&e(this);const d=Ni.get(this)||[];for(let u=0;u<d.length;u++){const p=d[u];p.onLoad&&p.onLoad(this)}Ni.delete(this),r.manager.itemEnd(t)}function l(d){h(),s&&s(d),ms.remove(`image:${t}`);const u=Ni.get(this)||[];for(let p=0;p<u.length;p++){const g=u[p];g.onError&&g.onError(d)}Ni.delete(this),r.manager.itemError(t),r.manager.itemEnd(t)}function h(){o.removeEventListener("load",c,!1),o.removeEventListener("error",l,!1)}return o.addEventListener("load",c,!1),o.addEventListener("error",l,!1),t.slice(0,5)!=="data:"&&this.crossOrigin!==void 0&&(o.crossOrigin=this.crossOrigin),ms.add(`image:${t}`,o),r.manager.itemStart(t),o.src=t,o}}class nd extends ii{constructor(t){super(t)}load(t,e,n,s){const r=new Ne,a=new ed(this.manager);return a.setCrossOrigin(this.crossOrigin),a.setPath(this.path),a.load(t,function(o){r.image=o,r.needsUpdate=!0,e!==void 0&&e(r)},n,s),r}}class nh extends Te{constructor(t,e=1){super(),this.isLight=!0,this.type="Light",this.color=new Ft(t),this.intensity=e}dispose(){this.dispatchEvent({type:"dispose"})}copy(t,e){return super.copy(t,e),this.color.copy(t.color),this.intensity=t.intensity,this}toJSON(t){const e=super.toJSON(t);return e.object.color=this.color.getHex(),e.object.intensity=this.intensity,e}}class ih extends nh{constructor(t,e,n){super(t,n),this.isHemisphereLight=!0,this.type="HemisphereLight",this.position.copy(Te.DEFAULT_UP),this.updateMatrix(),this.groundColor=new Ft(e)}copy(t,e){return super.copy(t,e),this.groundColor.copy(t.groundColor),this}toJSON(t){const e=super.toJSON(t);return e.object.groundColor=this.groundColor.getHex(),e}}const ua=new se,kl=new I,Vl=new I;class id{constructor(t){this.camera=t,this.intensity=1,this.bias=0,this.biasNode=null,this.normalBias=0,this.radius=1,this.blurSamples=8,this.mapSize=new zt(512,512),this.mapType=qe,this.map=null,this.mapPass=null,this.matrix=new se,this.autoUpdate=!0,this.needsUpdate=!1,this._frustum=new Uo,this._frameExtents=new zt(1,1),this._viewportCount=1,this._viewports=[new pe(0,0,1,1)]}getViewportCount(){return this._viewportCount}getFrustum(){return this._frustum}updateMatrices(t){const e=this.camera,n=this.matrix;kl.setFromMatrixPosition(t.matrixWorld),e.position.copy(kl),Vl.setFromMatrixPosition(t.target.matrixWorld),e.lookAt(Vl),e.updateMatrixWorld(),ua.multiplyMatrices(e.projectionMatrix,e.matrixWorldInverse),this._frustum.setFromProjectionMatrix(ua,e.coordinateSystem,e.reversedDepth),e.coordinateSystem===Ss||e.reversedDepth?n.set(.5,0,0,.5,0,.5,0,.5,0,0,1,0,0,0,0,1):n.set(.5,0,0,.5,0,.5,0,.5,0,0,.5,.5,0,0,0,1),n.multiply(ua)}getViewport(t){return this._viewports[t]}getFrameExtents(){return this._frameExtents}dispose(){this.map&&this.map.dispose(),this.mapPass&&this.mapPass.dispose()}copy(t){return this.camera=t.camera.clone(),this.intensity=t.intensity,this.bias=t.bias,this.radius=t.radius,this.autoUpdate=t.autoUpdate,this.needsUpdate=t.needsUpdate,this.normalBias=t.normalBias,this.blurSamples=t.blurSamples,this.mapSize.copy(t.mapSize),this.biasNode=t.biasNode,this}clone(){return new this.constructor().copy(this)}toJSON(){const t={};return this.intensity!==1&&(t.intensity=this.intensity),this.bias!==0&&(t.bias=this.bias),this.normalBias!==0&&(t.normalBias=this.normalBias),this.radius!==1&&(t.radius=this.radius),(this.mapSize.x!==512||this.mapSize.y!==512)&&(t.mapSize=this.mapSize.toArray()),t.camera=this.camera.toJSON(!1).object,delete t.camera.matrix,t}}const ir=new I,sr=new On,pn=new I;class sh extends Te{constructor(){super(),this.isCamera=!0,this.type="Camera",this.matrixWorldInverse=new se,this.projectionMatrix=new se,this.projectionMatrixInverse=new se,this.coordinateSystem=vn,this._reversedDepth=!1}get reversedDepth(){return this._reversedDepth}copy(t,e){return super.copy(t,e),this.matrixWorldInverse.copy(t.matrixWorldInverse),this.projectionMatrix.copy(t.projectionMatrix),this.projectionMatrixInverse.copy(t.projectionMatrixInverse),this.coordinateSystem=t.coordinateSystem,this}getWorldDirection(t){return super.getWorldDirection(t).negate()}updateMatrixWorld(t){super.updateMatrixWorld(t),this.matrixWorld.decompose(ir,sr,pn),pn.x===1&&pn.y===1&&pn.z===1?this.matrixWorldInverse.copy(this.matrixWorld).invert():this.matrixWorldInverse.compose(ir,sr,pn.set(1,1,1)).invert()}updateWorldMatrix(t,e,n=!1){super.updateWorldMatrix(t,e,n),this.matrixWorld.decompose(ir,sr,pn),pn.x===1&&pn.y===1&&pn.z===1?this.matrixWorldInverse.copy(this.matrixWorld).invert():this.matrixWorldInverse.compose(ir,sr,pn.set(1,1,1)).invert()}clone(){return new this.constructor().copy(this)}}const $n=new I,Gl=new zt,Hl=new zt;class We extends sh{constructor(t=50,e=1,n=.1,s=2e3){super(),this.isPerspectiveCamera=!0,this.type="PerspectiveCamera",this.fov=t,this.zoom=1,this.near=n,this.far=s,this.focus=10,this.aspect=e,this.view=null,this.filmGauge=35,this.filmOffset=0,this.updateProjectionMatrix()}copy(t,e){return super.copy(t,e),this.fov=t.fov,this.zoom=t.zoom,this.near=t.near,this.far=t.far,this.focus=t.focus,this.aspect=t.aspect,this.view=t.view===null?null:Object.assign({},t.view),this.filmGauge=t.filmGauge,this.filmOffset=t.filmOffset,this}setFocalLength(t){const e=.5*this.getFilmHeight()/t;this.fov=go*2*Math.atan(e),this.updateProjectionMatrix()}getFocalLength(){const t=Math.tan(ps*.5*this.fov);return .5*this.getFilmHeight()/t}getEffectiveFOV(){return go*2*Math.atan(Math.tan(ps*.5*this.fov)/this.zoom)}getFilmWidth(){return this.filmGauge*Math.min(this.aspect,1)}getFilmHeight(){return this.filmGauge/Math.max(this.aspect,1)}getViewBounds(t,e,n){$n.set(-1,-1,.5).applyMatrix4(this.projectionMatrixInverse),e.set($n.x,$n.y).multiplyScalar(-t/$n.z),$n.set(1,1,.5).applyMatrix4(this.projectionMatrixInverse),n.set($n.x,$n.y).multiplyScalar(-t/$n.z)}getViewSize(t,e){return this.getViewBounds(t,Gl,Hl),e.subVectors(Hl,Gl)}setViewOffset(t,e,n,s,r,a){this.aspect=t/e,this.view===null&&(this.view={enabled:!0,fullWidth:1,fullHeight:1,offsetX:0,offsetY:0,width:1,height:1}),this.view.enabled=!0,this.view.fullWidth=t,this.view.fullHeight=e,this.view.offsetX=n,this.view.offsetY=s,this.view.width=r,this.view.height=a,this.updateProjectionMatrix()}clearViewOffset(){this.view!==null&&(this.view.enabled=!1),this.updateProjectionMatrix()}updateProjectionMatrix(){const t=this.near;let e=t*Math.tan(ps*.5*this.fov)/this.zoom,n=2*e,s=this.aspect*n,r=-.5*s;const a=this.view;if(this.view!==null&&this.view.enabled){const c=a.fullWidth,l=a.fullHeight;r+=a.offsetX*s/c,e-=a.offsetY*n/l,s*=a.width/c,n*=a.height/l}const o=this.filmOffset;o!==0&&(r+=t*o/this.getFilmWidth()),this.projectionMatrix.makePerspective(r,r+s,e,e-n,t,this.far,this.coordinateSystem,this.reversedDepth),this.projectionMatrixInverse.copy(this.projectionMatrix).invert()}toJSON(t){const e=super.toJSON(t);return e.object.fov=this.fov,e.object.zoom=this.zoom,e.object.near=this.near,e.object.far=this.far,e.object.focus=this.focus,e.object.aspect=this.aspect,this.view!==null&&(e.object.view=Object.assign({},this.view)),e.object.filmGauge=this.filmGauge,e.object.filmOffset=this.filmOffset,e}}class Bo extends sh{constructor(t=-1,e=1,n=1,s=-1,r=.1,a=2e3){super(),this.isOrthographicCamera=!0,this.type="OrthographicCamera",this.zoom=1,this.view=null,this.left=t,this.right=e,this.top=n,this.bottom=s,this.near=r,this.far=a,this.updateProjectionMatrix()}copy(t,e){return super.copy(t,e),this.left=t.left,this.right=t.right,this.top=t.top,this.bottom=t.bottom,this.near=t.near,this.far=t.far,this.zoom=t.zoom,this.view=t.view===null?null:Object.assign({},t.view),this}setViewOffset(t,e,n,s,r,a){this.view===null&&(this.view={enabled:!0,fullWidth:1,fullHeight:1,offsetX:0,offsetY:0,width:1,height:1}),this.view.enabled=!0,this.view.fullWidth=t,this.view.fullHeight=e,this.view.offsetX=n,this.view.offsetY=s,this.view.width=r,this.view.height=a,this.updateProjectionMatrix()}clearViewOffset(){this.view!==null&&(this.view.enabled=!1),this.updateProjectionMatrix()}updateProjectionMatrix(){const t=(this.right-this.left)/(2*this.zoom),e=(this.top-this.bottom)/(2*this.zoom),n=(this.right+this.left)/2,s=(this.top+this.bottom)/2;let r=n-t,a=n+t,o=s+e,c=s-e;if(this.view!==null&&this.view.enabled){const l=(this.right-this.left)/this.view.fullWidth/this.zoom,h=(this.top-this.bottom)/this.view.fullHeight/this.zoom;r+=l*this.view.offsetX,a=r+l*this.view.width,o-=h*this.view.offsetY,c=o-h*this.view.height}this.projectionMatrix.makeOrthographic(r,a,o,c,this.near,this.far,this.coordinateSystem,this.reversedDepth),this.projectionMatrixInverse.copy(this.projectionMatrix).invert()}toJSON(t){const e=super.toJSON(t);return e.object.zoom=this.zoom,e.object.left=this.left,e.object.right=this.right,e.object.top=this.top,e.object.bottom=this.bottom,e.object.near=this.near,e.object.far=this.far,this.view!==null&&(e.object.view=Object.assign({},this.view)),e}}class sd extends id{constructor(){super(new Bo(-5,5,5,-5,.5,500)),this.isDirectionalLightShadow=!0}}class gs extends nh{constructor(t,e){super(t,e),this.isDirectionalLight=!0,this.type="DirectionalLight",this.position.copy(Te.DEFAULT_UP),this.updateMatrix(),this.target=new Te,this.shadow=new sd}dispose(){super.dispose(),this.shadow.dispose()}copy(t){return super.copy(t),this.target=t.target.clone(),this.shadow=t.shadow.clone(),this}toJSON(t){const e=super.toJSON(t);return e.object.shadow=this.shadow.toJSON(),e.object.target=this.target.uuid,e}}const Ui=-90,Fi=1;class rd extends Te{constructor(t,e,n){super(),this.type="CubeCamera",this.renderTarget=n,this.coordinateSystem=null,this.activeMipmapLevel=0;const s=new We(Ui,Fi,t,e);s.layers=this.layers,this.add(s);const r=new We(Ui,Fi,t,e);r.layers=this.layers,this.add(r);const a=new We(Ui,Fi,t,e);a.layers=this.layers,this.add(a);const o=new We(Ui,Fi,t,e);o.layers=this.layers,this.add(o);const c=new We(Ui,Fi,t,e);c.layers=this.layers,this.add(c);const l=new We(Ui,Fi,t,e);l.layers=this.layers,this.add(l)}updateCoordinateSystem(){const t=this.coordinateSystem,e=this.children.concat(),[n,s,r,a,o,c]=e;for(const l of e)this.remove(l);if(t===vn)n.up.set(0,1,0),n.lookAt(1,0,0),s.up.set(0,1,0),s.lookAt(-1,0,0),r.up.set(0,0,-1),r.lookAt(0,1,0),a.up.set(0,0,1),a.lookAt(0,-1,0),o.up.set(0,1,0),o.lookAt(0,0,1),c.up.set(0,1,0),c.lookAt(0,0,-1);else if(t===Ss)n.up.set(0,-1,0),n.lookAt(-1,0,0),s.up.set(0,-1,0),s.lookAt(1,0,0),r.up.set(0,0,1),r.lookAt(0,1,0),a.up.set(0,0,-1),a.lookAt(0,-1,0),o.up.set(0,-1,0),o.lookAt(0,0,1),c.up.set(0,-1,0),c.lookAt(0,0,-1);else throw new Error("THREE.CubeCamera.updateCoordinateSystem(): Invalid coordinate system: "+t);for(const l of e)this.add(l),l.updateMatrixWorld()}update(t,e){this.parent===null&&this.updateMatrixWorld();const{renderTarget:n,activeMipmapLevel:s}=this;this.coordinateSystem!==t.coordinateSystem&&(this.coordinateSystem=t.coordinateSystem,this.updateCoordinateSystem());const[r,a,o,c,l,h]=this.children,d=t.getRenderTarget(),u=t.getActiveCubeFace(),p=t.getActiveMipmapLevel(),g=t.xr.enabled;t.xr.enabled=!1;const v=n.texture.generateMipmaps;n.texture.generateMipmaps=!1;let m=!1;t.isWebGLRenderer===!0?m=t.state.buffers.depth.getReversed():m=t.reversedDepthBuffer,t.setRenderTarget(n,0,s),m&&t.autoClear===!1&&t.clearDepth(),t.render(e,r),t.setRenderTarget(n,1,s),m&&t.autoClear===!1&&t.clearDepth(),t.render(e,a),t.setRenderTarget(n,2,s),m&&t.autoClear===!1&&t.clearDepth(),t.render(e,o),t.setRenderTarget(n,3,s),m&&t.autoClear===!1&&t.clearDepth(),t.render(e,c),t.setRenderTarget(n,4,s),m&&t.autoClear===!1&&t.clearDepth(),t.render(e,l),n.texture.generateMipmaps=v,t.setRenderTarget(n,5,s),m&&t.autoClear===!1&&t.clearDepth(),t.render(e,h),t.setRenderTarget(d,u,p),t.xr.enabled=g,n.texture.needsPMREMUpdate=!0}}class ad extends We{constructor(t=[]){super(),this.isArrayCamera=!0,this.isMultiViewCamera=!1,this.cameras=t}}const Wl=new se;class od{constructor(t,e,n=0,s=1/0){this.ray=new As(t,e),this.near=n,this.far=s,this.camera=null,this.layers=new No,this.params={Mesh:{},Line:{threshold:1},LOD:{},Points:{threshold:1},Sprite:{}}}set(t,e){this.ray.set(t,e)}setFromCamera(t,e){e.isPerspectiveCamera?(this.ray.origin.setFromMatrixPosition(e.matrixWorld),this.ray.direction.set(t.x,t.y,.5).unproject(e).sub(this.ray.origin).normalize(),this.camera=e):e.isOrthographicCamera?(this.ray.origin.set(t.x,t.y,e.projectionMatrix.elements[14]).unproject(e),this.ray.direction.set(0,0,-1).transformDirection(e.matrixWorld),this.camera=e):ee("Raycaster: Unsupported camera type: "+e.type)}setFromXRController(t){return Wl.identity().extractRotation(t.matrixWorld),this.ray.origin.setFromMatrixPosition(t.matrixWorld),this.ray.direction.set(0,0,-1).applyMatrix4(Wl),this}intersectObject(t,e=!0,n=[]){return xo(t,this,n,e),n.sort(Xl),n}intersectObjects(t,e=!0,n=[]){for(let s=0,r=t.length;s<r;s++)xo(t[s],this,n,e);return n.sort(Xl),n}}function Xl(i,t){return i.distance-t.distance}function xo(i,t,e,n){let s=!0;if(i.layers.test(t.layers)&&i.raycast(t,e)===!1&&(s=!1),s===!0&&n===!0){const r=i.children;for(let a=0,o=r.length;a<o;a++)xo(r[a],t,e,!0)}}class ql{constructor(t=1,e=0,n=0){this.radius=t,this.phi=e,this.theta=n}set(t,e,n){return this.radius=t,this.phi=e,this.theta=n,this}copy(t){return this.radius=t.radius,this.phi=t.phi,this.theta=t.theta,this}makeSafe(){return this.phi=jt(this.phi,1e-6,Math.PI-1e-6),this}setFromVector3(t){return this.setFromCartesianCoords(t.x,t.y,t.z)}setFromCartesianCoords(t,e,n){return this.radius=Math.sqrt(t*t+e*e+n*n),this.radius===0?(this.theta=0,this.phi=0):(this.theta=Math.atan2(t,n),this.phi=Math.acos(jt(e/this.radius,-1,1))),this}clone(){return new this.constructor().copy(this)}}const Ko=class Ko{constructor(t,e,n,s){this.elements=[1,0,0,1],t!==void 0&&this.set(t,e,n,s)}identity(){return this.set(1,0,0,1),this}fromArray(t,e=0){for(let n=0;n<4;n++)this.elements[n]=t[n+e];return this}set(t,e,n,s){const r=this.elements;return r[0]=t,r[2]=e,r[1]=n,r[3]=s,this}};Ko.prototype.isMatrix2=!0;let Yl=Ko;class ld extends si{constructor(t,e=null){super(),this.object=t,this.domElement=e,this.enabled=!0,this.state=-1,this.keys={},this.mouseButtons={LEFT:null,MIDDLE:null,RIGHT:null},this.touches={ONE:null,TWO:null}}connect(t){if(t===void 0){Ut("Controls: connect() now requires an element.");return}this.domElement!==null&&this.disconnect(),this.domElement=t}disconnect(){}dispose(){}update(){}}function $l(i,t,e,n){const s=cd(n);switch(e){case Gc:return i*t;case wo:return i*t/s.components*s.byteLength;case Ro:return i*t/s.components*s.byteLength;case xi:return i*t*2/s.components*s.byteLength;case Co:return i*t*2/s.components*s.byteLength;case Hc:return i*t*3/s.components*s.byteLength;case hn:return i*t*4/s.components*s.byteLength;case Po:return i*t*4/s.components*s.byteLength;case fr:case pr:return Math.floor((i+3)/4)*Math.floor((t+3)/4)*8;case mr:case gr:return Math.floor((i+3)/4)*Math.floor((t+3)/4)*16;case ka:case Ga:return Math.max(i,16)*Math.max(t,8)/4;case za:case Va:return Math.max(i,8)*Math.max(t,8)/2;case Ha:case Wa:case qa:case Ya:return Math.floor((i+3)/4)*Math.floor((t+3)/4)*8;case Xa:case vr:case $a:return Math.floor((i+3)/4)*Math.floor((t+3)/4)*16;case Ka:return Math.floor((i+3)/4)*Math.floor((t+3)/4)*16;case Za:return Math.floor((i+4)/5)*Math.floor((t+3)/4)*16;case Ja:return Math.floor((i+4)/5)*Math.floor((t+4)/5)*16;case ja:return Math.floor((i+5)/6)*Math.floor((t+4)/5)*16;case Qa:return Math.floor((i+5)/6)*Math.floor((t+5)/6)*16;case to:return Math.floor((i+7)/8)*Math.floor((t+4)/5)*16;case eo:return Math.floor((i+7)/8)*Math.floor((t+5)/6)*16;case no:return Math.floor((i+7)/8)*Math.floor((t+7)/8)*16;case io:return Math.floor((i+9)/10)*Math.floor((t+4)/5)*16;case so:return Math.floor((i+9)/10)*Math.floor((t+5)/6)*16;case ro:return Math.floor((i+9)/10)*Math.floor((t+7)/8)*16;case ao:return Math.floor((i+9)/10)*Math.floor((t+9)/10)*16;case oo:return Math.floor((i+11)/12)*Math.floor((t+9)/10)*16;case lo:return Math.floor((i+11)/12)*Math.floor((t+11)/12)*16;case co:case ho:case uo:return Math.ceil(i/4)*Math.ceil(t/4)*16;case fo:case po:return Math.ceil(i/4)*Math.ceil(t/4)*8;case Mr:case mo:return Math.ceil(i/4)*Math.ceil(t/4)*16}throw new Error(`Unable to determine texture byte length for ${e} format.`)}function cd(i){switch(i){case qe:case Bc:return{byteLength:1,components:1};case vs:case zc:case Un:return{byteLength:2,components:1};case To:case Ao:return{byteLength:2,components:4};case yn:case Eo:case cn:return{byteLength:4,components:1};case kc:case Vc:return{byteLength:4,components:3}}throw new Error(`THREE.TextureUtils: Unknown texture type ${i}.`)}typeof __THREE_DEVTOOLS__<"u"&&__THREE_DEVTOOLS__.dispatchEvent(new CustomEvent("register",{detail:{revision:"185"}}));typeof window<"u"&&(window.__THREE__?Ut("WARNING: Multiple instances of Three.js being imported."):window.__THREE__="185");/**
 * @license
 * Copyright 2010-2026 Three.js Authors
 * SPDX-License-Identifier: MIT
 */function rh(){let i=null,t=!1,e=null,n=null;function s(r,a){e(r,a),n=i.requestAnimationFrame(s)}return{start:function(){t!==!0&&e!==null&&i!==null&&(n=i.requestAnimationFrame(s),t=!0)},stop:function(){i!==null&&i.cancelAnimationFrame(n),t=!1},setAnimationLoop:function(r){e=r},setContext:function(r){i=r}}}function hd(i){const t=new WeakMap;function e(o,c){const l=o.array,h=o.usage,d=l.byteLength,u=i.createBuffer();i.bindBuffer(c,u),i.bufferData(c,l,h),o.onUploadCallback();let p;if(l instanceof Float32Array)p=i.FLOAT;else if(typeof Float16Array<"u"&&l instanceof Float16Array)p=i.HALF_FLOAT;else if(l instanceof Uint16Array)o.isFloat16BufferAttribute?p=i.HALF_FLOAT:p=i.UNSIGNED_SHORT;else if(l instanceof Int16Array)p=i.SHORT;else if(l instanceof Uint32Array)p=i.UNSIGNED_INT;else if(l instanceof Int32Array)p=i.INT;else if(l instanceof Int8Array)p=i.BYTE;else if(l instanceof Uint8Array)p=i.UNSIGNED_BYTE;else if(l instanceof Uint8ClampedArray)p=i.UNSIGNED_BYTE;else throw new Error("THREE.WebGLAttributes: Unsupported buffer data format: "+l);return{buffer:u,type:p,bytesPerElement:l.BYTES_PER_ELEMENT,version:o.version,size:d}}function n(o,c,l){const h=c.array,d=c.updateRanges;if(i.bindBuffer(l,o),d.length===0)i.bufferSubData(l,0,h);else{d.sort((p,g)=>p.start-g.start);let u=0;for(let p=1;p<d.length;p++){const g=d[u],v=d[p];v.start<=g.start+g.count+1?g.count=Math.max(g.count,v.start+v.count-g.start):(++u,d[u]=v)}d.length=u+1;for(let p=0,g=d.length;p<g;p++){const v=d[p];i.bufferSubData(l,v.start*h.BYTES_PER_ELEMENT,h,v.start,v.count)}c.clearUpdateRanges()}c.onUploadCallback()}function s(o){return o.isInterleavedBufferAttribute&&(o=o.data),t.get(o)}function r(o){o.isInterleavedBufferAttribute&&(o=o.data);const c=t.get(o);c&&(i.deleteBuffer(c.buffer),t.delete(o))}function a(o,c){if(o.isInterleavedBufferAttribute&&(o=o.data),o.isGLBufferAttribute){const h=t.get(o);(!h||h.version<o.version)&&t.set(o,{buffer:o.buffer,type:o.type,bytesPerElement:o.elementSize,version:o.version});return}const l=t.get(o);if(l===void 0)t.set(o,e(o,c));else if(l.version<o.version){if(l.size!==o.array.byteLength)throw new Error("THREE.WebGLAttributes: The size of the buffer attribute's array buffer does not match the original size. Resizing buffer attributes is not supported.");n(l.buffer,o,c),l.version=o.version}}return{get:s,remove:r,update:a}}var ud=`#ifdef USE_ALPHAHASH
	if ( diffuseColor.a < getAlphaHashThreshold( vPosition ) ) discard;
#endif`,dd=`#ifdef USE_ALPHAHASH
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
#endif`,fd=`#ifdef USE_ALPHAMAP
	diffuseColor.a *= texture2D( alphaMap, vAlphaMapUv ).g;
#endif`,pd=`#ifdef USE_ALPHAMAP
	uniform sampler2D alphaMap;
#endif`,md=`#ifdef USE_ALPHATEST
	#ifdef ALPHA_TO_COVERAGE
	diffuseColor.a = smoothstep( alphaTest, alphaTest + fwidth( diffuseColor.a ), diffuseColor.a );
	if ( diffuseColor.a == 0.0 ) discard;
	#else
	if ( diffuseColor.a < alphaTest ) discard;
	#endif
#endif`,gd=`#ifdef USE_ALPHATEST
	uniform float alphaTest;
#endif`,_d=`#ifdef USE_AOMAP
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
#endif`,xd=`#ifdef USE_AOMAP
	uniform sampler2D aoMap;
	uniform float aoMapIntensity;
#endif`,vd=`#ifdef USE_BATCHING
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
#endif`,Md=`#ifdef USE_BATCHING
	mat4 batchingMatrix = getBatchingMatrix( getIndirectIndex( gl_DrawID ) );
#endif`,Sd=`vec3 transformed = vec3( position );
#ifdef USE_ALPHAHASH
	vPosition = vec3( position );
#endif`,yd=`vec3 objectNormal = vec3( normal );
#ifdef USE_TANGENT
	vec3 objectTangent = vec3( tangent.xyz );
#endif`,bd=`float G_BlinnPhong_Implicit( ) {
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
} // validated`,Ed=`#ifdef USE_IRIDESCENCE
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
#endif`,Td=`#ifdef USE_BUMPMAP
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
#endif`,Ad=`#if NUM_CLIPPING_PLANES > 0
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
#endif`,wd=`#if NUM_CLIPPING_PLANES > 0
	varying vec3 vClipPosition;
	uniform vec4 clippingPlanes[ NUM_CLIPPING_PLANES ];
#endif`,Rd=`#if NUM_CLIPPING_PLANES > 0
	varying vec3 vClipPosition;
#endif`,Cd=`#if NUM_CLIPPING_PLANES > 0
	vClipPosition = - mvPosition.xyz;
#endif`,Pd=`#if defined( USE_COLOR ) || defined( USE_COLOR_ALPHA )
	diffuseColor *= vColor;
#endif`,Dd=`#if defined( USE_COLOR ) || defined( USE_COLOR_ALPHA )
	varying vec4 vColor;
#endif`,Ld=`#if defined( USE_COLOR ) || defined( USE_COLOR_ALPHA ) || defined( USE_INSTANCING_COLOR ) || defined( USE_BATCHING_COLOR )
	varying vec4 vColor;
#endif`,Id=`#if defined( USE_COLOR ) || defined( USE_COLOR_ALPHA ) || defined( USE_INSTANCING_COLOR ) || defined( USE_BATCHING_COLOR )
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
#endif`,Nd=`#define PI 3.141592653589793
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
} // validated`,Ud=`#ifdef ENVMAP_TYPE_CUBE_UV
	#define cubeUV_minMipLevel 4.0
	#define cubeUV_minTileSize 16.0
	float getFace( vec3 direction ) {
		vec3 absDirection = abs( direction );
		float face = - 1.0;
		if ( absDirection.x > absDirection.z ) {
			if ( absDirection.x > absDirection.y )
				face = direction.x > 0.0 ? 0.0 : 3.0;
			else
				face = direction.y > 0.0 ? 1.0 : 4.0;
		} else {
			if ( absDirection.z > absDirection.y )
				face = direction.z > 0.0 ? 2.0 : 5.0;
			else
				face = direction.y > 0.0 ? 1.0 : 4.0;
		}
		return face;
	}
	vec2 getUV( vec3 direction, float face ) {
		vec2 uv;
		if ( face == 0.0 ) {
			uv = vec2( direction.z, direction.y ) / abs( direction.x );
		} else if ( face == 1.0 ) {
			uv = vec2( - direction.x, - direction.z ) / abs( direction.y );
		} else if ( face == 2.0 ) {
			uv = vec2( - direction.x, direction.y ) / abs( direction.z );
		} else if ( face == 3.0 ) {
			uv = vec2( - direction.z, direction.y ) / abs( direction.x );
		} else if ( face == 4.0 ) {
			uv = vec2( - direction.x, direction.z ) / abs( direction.y );
		} else {
			uv = vec2( direction.x, direction.y ) / abs( direction.z );
		}
		return 0.5 * ( uv + 1.0 );
	}
	vec3 bilinearCubeUV( sampler2D envMap, vec3 direction, float mipInt ) {
		float face = getFace( direction );
		float filterInt = max( cubeUV_minMipLevel - mipInt, 0.0 );
		mipInt = max( mipInt, cubeUV_minMipLevel );
		float faceSize = exp2( mipInt );
		highp vec2 uv = getUV( direction, face ) * ( faceSize - 2.0 ) + 1.0;
		if ( face > 2.0 ) {
			uv.y += faceSize;
			face -= 3.0;
		}
		uv.x += face * faceSize;
		uv.x += filterInt * 3.0 * cubeUV_minTileSize;
		uv.y += 4.0 * ( exp2( CUBEUV_MAX_MIP ) - faceSize );
		uv.x *= CUBEUV_TEXEL_WIDTH;
		uv.y *= CUBEUV_TEXEL_HEIGHT;
		#ifdef texture2DGradEXT
			return texture2DGradEXT( envMap, uv, vec2( 0.0 ), vec2( 0.0 ) ).rgb;
		#else
			return texture2D( envMap, uv ).rgb;
		#endif
	}
	#define cubeUV_r0 1.0
	#define cubeUV_m0 - 2.0
	#define cubeUV_r1 0.8
	#define cubeUV_m1 - 1.0
	#define cubeUV_r4 0.4
	#define cubeUV_m4 2.0
	#define cubeUV_r5 0.305
	#define cubeUV_m5 3.0
	#define cubeUV_r6 0.21
	#define cubeUV_m6 4.0
	float roughnessToMip( float roughness ) {
		float mip = 0.0;
		if ( roughness >= cubeUV_r1 ) {
			mip = ( cubeUV_r0 - roughness ) * ( cubeUV_m1 - cubeUV_m0 ) / ( cubeUV_r0 - cubeUV_r1 ) + cubeUV_m0;
		} else if ( roughness >= cubeUV_r4 ) {
			mip = ( cubeUV_r1 - roughness ) * ( cubeUV_m4 - cubeUV_m1 ) / ( cubeUV_r1 - cubeUV_r4 ) + cubeUV_m1;
		} else if ( roughness >= cubeUV_r5 ) {
			mip = ( cubeUV_r4 - roughness ) * ( cubeUV_m5 - cubeUV_m4 ) / ( cubeUV_r4 - cubeUV_r5 ) + cubeUV_m4;
		} else if ( roughness >= cubeUV_r6 ) {
			mip = ( cubeUV_r5 - roughness ) * ( cubeUV_m6 - cubeUV_m5 ) / ( cubeUV_r5 - cubeUV_r6 ) + cubeUV_m5;
		} else {
			mip = - 2.0 * log2( 1.16 * roughness );		}
		return mip;
	}
	vec4 textureCubeUV( sampler2D envMap, vec3 sampleDir, float roughness ) {
		float mip = clamp( roughnessToMip( roughness ), cubeUV_m0, CUBEUV_MAX_MIP );
		float mipF = fract( mip );
		float mipInt = floor( mip );
		vec3 color0 = bilinearCubeUV( envMap, sampleDir, mipInt );
		if ( mipF == 0.0 ) {
			return vec4( color0, 1.0 );
		} else {
			vec3 color1 = bilinearCubeUV( envMap, sampleDir, mipInt + 1.0 );
			return vec4( mix( color0, color1, mipF ), 1.0 );
		}
	}
#endif`,Fd=`vec3 transformedNormal = objectNormal;
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
#endif`,Od=`#ifdef USE_DISPLACEMENTMAP
	uniform sampler2D displacementMap;
	uniform float displacementScale;
	uniform float displacementBias;
#endif`,Bd=`#ifdef USE_DISPLACEMENTMAP
	transformed += normalize( objectNormal ) * ( texture2D( displacementMap, vDisplacementMapUv ).x * displacementScale + displacementBias );
#endif`,zd=`#ifdef USE_EMISSIVEMAP
	vec4 emissiveColor = texture2D( emissiveMap, vEmissiveMapUv );
	#ifdef DECODE_VIDEO_TEXTURE_EMISSIVE
		emissiveColor = sRGBTransferEOTF( emissiveColor );
	#endif
	totalEmissiveRadiance *= emissiveColor.rgb;
#endif`,kd=`#ifdef USE_EMISSIVEMAP
	uniform sampler2D emissiveMap;
#endif`,Vd="gl_FragColor = linearToOutputTexel( gl_FragColor );",Gd=`vec4 LinearTransferOETF( in vec4 value ) {
	return value;
}
vec4 sRGBTransferEOTF( in vec4 value ) {
	return vec4( mix( pow( value.rgb * 0.9478672986 + vec3( 0.0521327014 ), vec3( 2.4 ) ), value.rgb * 0.0773993808, vec3( lessThanEqual( value.rgb, vec3( 0.04045 ) ) ) ), value.a );
}
vec4 sRGBTransferOETF( in vec4 value ) {
	return vec4( mix( pow( value.rgb, vec3( 0.41666 ) ) * 1.055 - vec3( 0.055 ), value.rgb * 12.92, vec3( lessThanEqual( value.rgb, vec3( 0.0031308 ) ) ) ), value.a );
}`,Hd=`#ifdef USE_ENVMAP
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
#endif`,Wd=`#ifdef USE_ENVMAP
	uniform float envMapIntensity;
	uniform mat3 envMapRotation;
	#ifdef ENVMAP_TYPE_CUBE
		uniform samplerCube envMap;
	#else
		uniform sampler2D envMap;
	#endif
#endif`,Xd=`#ifdef USE_ENVMAP
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
#endif`,qd=`#ifdef USE_ENVMAP
	#if defined( USE_BUMPMAP ) || defined( USE_NORMALMAP ) || defined( PHONG ) || defined( LAMBERT )
		#define ENV_WORLDPOS
	#endif
	#ifdef ENV_WORLDPOS
		
		varying vec3 vWorldPosition;
	#else
		varying vec3 vReflect;
		uniform float refractionRatio;
	#endif
#endif`,Yd=`#ifdef USE_ENVMAP
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
#endif`,$d=`#ifdef USE_FOG
	vFogDepth = - mvPosition.z;
#endif`,Kd=`#ifdef USE_FOG
	varying float vFogDepth;
#endif`,Zd=`#ifdef USE_FOG
	#ifdef FOG_EXP2
		float fogFactor = 1.0 - exp( - fogDensity * fogDensity * vFogDepth * vFogDepth );
	#else
		float fogFactor = smoothstep( fogNear, fogFar, vFogDepth );
	#endif
	gl_FragColor.rgb = mix( gl_FragColor.rgb, fogColor, fogFactor );
#endif`,Jd=`#ifdef USE_FOG
	uniform vec3 fogColor;
	varying float vFogDepth;
	#ifdef FOG_EXP2
		uniform float fogDensity;
	#else
		uniform float fogNear;
		uniform float fogFar;
	#endif
#endif`,jd=`#ifdef USE_GRADIENTMAP
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
}`,Qd=`#ifdef USE_LIGHTMAP
	uniform sampler2D lightMap;
	uniform float lightMapIntensity;
#endif`,tf=`LambertMaterial material;
material.diffuseColor = diffuseColor.rgb;
material.specularStrength = specularStrength;`,ef=`varying vec3 vViewPosition;
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
#define RE_IndirectDiffuse		RE_IndirectDiffuse_Lambert`,nf=`uniform bool receiveShadow;
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
#include <lightprobes_pars_fragment>`,sf=`#ifdef USE_ENVMAP
	vec3 getIBLIrradiance( const in vec3 normal ) {
		#ifdef ENVMAP_TYPE_CUBE_UV
			vec3 worldNormal = transformNormalByInverseViewMatrix( normal, viewMatrix );
			vec4 envMapColor = textureCubeUV( envMap, envMapRotation * worldNormal, 1.0 );
			return PI * envMapColor.rgb * envMapIntensity;
		#else
			return vec3( 0.0 );
		#endif
	}
	vec3 getIBLRadiance( const in vec3 viewDir, const in vec3 normal, const in float roughness ) {
		#ifdef ENVMAP_TYPE_CUBE_UV
			vec3 reflectVec = reflect( - viewDir, normal );
			reflectVec = normalize( mix( reflectVec, normal, pow4( roughness ) ) );
			reflectVec = transformDirectionByInverseViewMatrix( reflectVec, viewMatrix );
			vec4 envMapColor = textureCubeUV( envMap, envMapRotation * reflectVec, roughness );
			return envMapColor.rgb * envMapIntensity;
		#else
			return vec3( 0.0 );
		#endif
	}
	#ifdef USE_ANISOTROPY
		vec3 getIBLAnisotropyRadiance( const in vec3 viewDir, const in vec3 normal, const in float roughness, const in vec3 bitangent, const in float anisotropy ) {
			#ifdef ENVMAP_TYPE_CUBE_UV
				vec3 bentNormal = cross( bitangent, viewDir );
				bentNormal = normalize( cross( bentNormal, bitangent ) );
				bentNormal = normalize( mix( bentNormal, normal, pow2( pow2( 1.0 - anisotropy * ( 1.0 - roughness ) ) ) ) );
				return getIBLRadiance( viewDir, bentNormal, roughness );
			#else
				return vec3( 0.0 );
			#endif
		}
	#endif
#endif`,rf=`ToonMaterial material;
material.diffuseColor = diffuseColor.rgb;`,af=`varying vec3 vViewPosition;
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
#define RE_IndirectDiffuse		RE_IndirectDiffuse_Toon`,of=`BlinnPhongMaterial material;
material.diffuseColor = diffuseColor.rgb;
material.specularColor = specular;
material.specularShininess = shininess;
material.specularStrength = specularStrength;`,lf=`varying vec3 vViewPosition;
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
#define RE_IndirectDiffuse		RE_IndirectDiffuse_BlinnPhong`,cf=`PhysicalMaterial material;
material.diffuseColor = diffuseColor.rgb;
material.diffuseContribution = diffuseColor.rgb * ( 1.0 - metalnessFactor );
material.metalness = metalnessFactor;
vec3 dxy = max( abs( dFdx( nonPerturbedNormal ) ), abs( dFdy( nonPerturbedNormal ) ) );
float geometryRoughness = max( max( dxy.x, dxy.y ), dxy.z );
material.roughness = max( roughnessFactor, 0.0525 );material.roughness += geometryRoughness;
material.roughness = min( material.roughness, 1.0 );
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
	material.clearcoat = saturate( material.clearcoat );	material.clearcoatRoughness = max( material.clearcoatRoughness, 0.0525 );
	material.clearcoatRoughness += geometryRoughness;
	material.clearcoatRoughness = min( material.clearcoatRoughness, 1.0 );
#endif
#ifdef USE_DISPERSION
	material.dispersion = dispersion;
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
#endif`,hf=`uniform sampler2D dfgLUT;
struct PhysicalMaterial {
	vec3 diffuseColor;
	vec3 diffuseContribution;
	vec3 specularColor;
	vec3 specularColorBlended;
	float roughness;
	float metalness;
	float specularF90;
	float dispersion;
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
		vec3 iridescenceF0;
		vec3 iridescenceFresnelDielectric;
		vec3 iridescenceFresnelMetallic;
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
		float roughness = material.clearcoatRoughness;
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
	float roughness = material.roughness;
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
		float V = V_GGX_SmithCorrelated_Anisotropic( material.alphaT, alpha, dotTV, dotBV, dotTL, dotBL, dotNV, dotNL );
		float D = D_GGX_Anisotropic( material.alphaT, alpha, dotNH, dotTH, dotBH );
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
void computeMultiscatteringIridescence( const in vec3 normal, const in vec3 viewDir, const in vec3 specularColor, const in float specularF90, const in float iridescence, const in vec3 iridescenceF0, const in float roughness, inout vec3 singleScatter, inout vec3 multiScatter ) {
#else
void computeMultiscattering( const in vec3 normal, const in vec3 viewDir, const in vec3 specularColor, const in float specularF90, const in float roughness, inout vec3 singleScatter, inout vec3 multiScatter ) {
#endif
	float dotNV = saturate( dot( normal, viewDir ) );
	vec2 fab = texture2D( dfgLUT, vec2( roughness, dotNV ) ).rg;
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
vec3 BRDF_GGX_Multiscatter( const in vec3 lightDir, const in vec3 viewDir, const in vec3 normal, const in PhysicalMaterial material ) {
	vec3 singleScatter = BRDF_GGX( lightDir, viewDir, normal, material );
	float dotNL = saturate( dot( normal, lightDir ) );
	float dotNV = saturate( dot( normal, viewDir ) );
	vec2 dfgV = texture2D( dfgLUT, vec2( material.roughness, dotNV ) ).rg;
	vec2 dfgL = texture2D( dfgLUT, vec2( material.roughness, dotNL ) ).rg;
	vec3 FssEss_V = material.specularColorBlended * dfgV.x + material.specularF90 * dfgV.y;
	vec3 FssEss_L = material.specularColorBlended * dfgL.x + material.specularF90 * dfgL.y;
	float Ess_V = dfgV.x + dfgV.y;
	float Ess_L = dfgL.x + dfgL.y;
	float Ems_V = 1.0 - Ess_V;
	float Ems_L = 1.0 - Ess_L;
	vec3 Favg = material.specularColorBlended + ( 1.0 - material.specularColorBlended ) * 0.047619;
	vec3 Fms = FssEss_V * FssEss_L * Favg / ( 1.0 - Ems_V * Ems_L * Favg + EPSILON );
	float compensationFactor = Ems_V * Ems_L;
	vec3 multiScatter = Fms * compensationFactor;
	return singleScatter + multiScatter;
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
	reflectedLight.directSpecular += irradiance * BRDF_GGX_Multiscatter( directLight.direction, geometryViewDir, geometryNormal, material );
	reflectedLight.directDiffuse += irradiance * BRDF_Lambert( material.diffuseContribution );
}
void RE_IndirectDiffuse_Physical( const in vec3 irradiance, const in vec3 geometryPosition, const in vec3 geometryNormal, const in vec3 geometryViewDir, const in vec3 geometryClearcoatNormal, const in PhysicalMaterial material, inout ReflectedLight reflectedLight ) {
	vec3 diffuse = irradiance * BRDF_Lambert( material.diffuseContribution );
	#ifdef USE_SHEEN
		float sheenAlbedo = IBLSheenBRDF( geometryNormal, geometryViewDir, material.sheenRoughness );
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
		computeMultiscatteringIridescence( geometryNormal, geometryViewDir, material.specularColor, material.specularF90, material.iridescence, material.iridescenceFresnelDielectric, material.roughness, singleScatteringDielectric, multiScatteringDielectric );
		computeMultiscatteringIridescence( geometryNormal, geometryViewDir, material.diffuseColor, material.specularF90, material.iridescence, material.iridescenceFresnelMetallic, material.roughness, singleScatteringMetallic, multiScatteringMetallic );
	#else
		computeMultiscattering( geometryNormal, geometryViewDir, material.specularColor, material.specularF90, material.roughness, singleScatteringDielectric, multiScatteringDielectric );
		computeMultiscattering( geometryNormal, geometryViewDir, material.diffuseColor, material.specularF90, material.roughness, singleScatteringMetallic, multiScatteringMetallic );
	#endif
	vec3 singleScattering = mix( singleScatteringDielectric, singleScatteringMetallic, material.metalness );
	vec3 multiScattering = mix( multiScatteringDielectric, multiScatteringMetallic, material.metalness );
	vec3 totalScatteringDielectric = singleScatteringDielectric + multiScatteringDielectric;
	vec3 diffuse = material.diffuseContribution * ( 1.0 - totalScatteringDielectric );
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
}`,uf=`
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
		material.iridescenceFresnelDielectric = evalIridescence( 1.0, material.iridescenceIOR, dotNVi, material.iridescenceThickness, material.specularColor );
		material.iridescenceFresnelMetallic = evalIridescence( 1.0, material.iridescenceIOR, dotNVi, material.iridescenceThickness, material.diffuseColor );
		material.iridescenceFresnel = mix( material.iridescenceFresnelDielectric, material.iridescenceFresnelMetallic, material.metalness );
		material.iridescenceF0 = Schlick_to_F0( material.iridescenceFresnel, 1.0, dotNVi );
	}
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
	#ifdef USE_LIGHT_PROBES_GRID
		vec3 probeWorldPos = ( ( vec4( geometryPosition, 1.0 ) - viewMatrix[ 3 ] ) * viewMatrix ).xyz;
		vec3 probeWorldNormal = transformNormalByInverseViewMatrix( geometryNormal, viewMatrix );
		irradiance += getLightProbeGridIrradiance( probeWorldPos, probeWorldNormal );
	#endif
#endif
#if defined( RE_IndirectSpecular )
	vec3 radiance = vec3( 0.0 );
	vec3 clearcoatRadiance = vec3( 0.0 );
#endif`,df=`#if defined( RE_IndirectDiffuse )
	#ifdef USE_LIGHTMAP
		vec4 lightMapTexel = texture2D( lightMap, vLightMapUv );
		vec3 lightMapIrradiance = lightMapTexel.rgb * lightMapIntensity;
		irradiance += lightMapIrradiance;
	#endif
	#if defined( USE_ENVMAP ) && defined( ENVMAP_TYPE_CUBE_UV )
		#if defined( STANDARD ) || defined( LAMBERT ) || defined( PHONG )
			iblIrradiance += getIBLIrradiance( geometryNormal );
		#endif
	#endif
#endif
#if defined( USE_ENVMAP ) && defined( RE_IndirectSpecular )
	#ifdef USE_ANISOTROPY
		radiance += getIBLAnisotropyRadiance( geometryViewDir, geometryNormal, material.roughness, material.anisotropyB, material.anisotropy );
	#else
		radiance += getIBLRadiance( geometryViewDir, geometryNormal, material.roughness );
	#endif
	#ifdef USE_CLEARCOAT
		clearcoatRadiance += getIBLRadiance( geometryViewDir, geometryClearcoatNormal, material.clearcoatRoughness );
	#endif
#endif`,ff=`#if defined( RE_IndirectDiffuse )
	#if defined( LAMBERT ) || defined( PHONG )
		irradiance += iblIrradiance;
	#endif
	RE_IndirectDiffuse( irradiance, geometryPosition, geometryNormal, geometryViewDir, geometryClearcoatNormal, material, reflectedLight );
#endif
#if defined( RE_IndirectSpecular )
	RE_IndirectSpecular( radiance, iblIrradiance, clearcoatRadiance, geometryPosition, geometryNormal, geometryViewDir, geometryClearcoatNormal, material, reflectedLight );
#endif`,pf=`#ifdef USE_LIGHT_PROBES_GRID
uniform highp sampler3D probesSH;
uniform vec3 probesMin;
uniform vec3 probesMax;
uniform vec3 probesResolution;
vec3 getLightProbeGridIrradiance( vec3 worldPos, vec3 worldNormal ) {
	vec3 res = probesResolution;
	vec3 gridRange = probesMax - probesMin;
	vec3 resMinusOne = res - 1.0;
	vec3 probeSpacing = gridRange / resMinusOne;
	vec3 samplePos = worldPos + worldNormal * probeSpacing * 0.5;
	vec3 uvw = clamp( ( samplePos - probesMin ) / gridRange, 0.0, 1.0 );
	uvw = uvw * resMinusOne / res + 0.5 / res;
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
#endif`,mf=`#if defined( USE_LOGARITHMIC_DEPTH_BUFFER )
	gl_FragDepth = vIsPerspective == 0.0 ? gl_FragCoord.z : log2( vFragDepth ) * logDepthBufFC * 0.5;
#endif`,gf=`#if defined( USE_LOGARITHMIC_DEPTH_BUFFER )
	uniform float logDepthBufFC;
	varying float vFragDepth;
	varying float vIsPerspective;
#endif`,_f=`#ifdef USE_LOGARITHMIC_DEPTH_BUFFER
	varying float vFragDepth;
	varying float vIsPerspective;
#endif`,xf=`#ifdef USE_LOGARITHMIC_DEPTH_BUFFER
	vFragDepth = 1.0 + gl_Position.w;
	vIsPerspective = float( isPerspectiveMatrix( projectionMatrix ) );
#endif`,vf=`#ifdef USE_MAP
	vec4 sampledDiffuseColor = texture2D( map, vMapUv );
	#ifdef DECODE_VIDEO_TEXTURE
		sampledDiffuseColor = sRGBTransferEOTF( sampledDiffuseColor );
	#endif
	diffuseColor *= sampledDiffuseColor;
#endif`,Mf=`#ifdef USE_MAP
	uniform sampler2D map;
#endif`,Sf=`#if defined( USE_MAP ) || defined( USE_ALPHAMAP )
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
#endif`,yf=`#if defined( USE_POINTS_UV )
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
#endif`,bf=`float metalnessFactor = metalness;
#ifdef USE_METALNESSMAP
	vec4 texelMetalness = texture2D( metalnessMap, vMetalnessMapUv );
	metalnessFactor *= texelMetalness.b;
#endif`,Ef=`#ifdef USE_METALNESSMAP
	uniform sampler2D metalnessMap;
#endif`,Tf=`#ifdef USE_INSTANCING_MORPH
	float morphTargetInfluences[ MORPHTARGETS_COUNT ];
	float morphTargetBaseInfluence = texelFetch( morphTexture, ivec2( 0, gl_InstanceID ), 0 ).r;
	for ( int i = 0; i < MORPHTARGETS_COUNT; i ++ ) {
		morphTargetInfluences[i] =  texelFetch( morphTexture, ivec2( i + 1, gl_InstanceID ), 0 ).r;
	}
#endif`,Af=`#if defined( USE_MORPHCOLORS )
	vColor *= morphTargetBaseInfluence;
	for ( int i = 0; i < MORPHTARGETS_COUNT; i ++ ) {
		#if defined( USE_COLOR_ALPHA )
			if ( morphTargetInfluences[ i ] != 0.0 ) vColor += getMorph( gl_VertexID, i, 2 ) * morphTargetInfluences[ i ];
		#elif defined( USE_COLOR )
			if ( morphTargetInfluences[ i ] != 0.0 ) vColor += getMorph( gl_VertexID, i, 2 ).rgb * morphTargetInfluences[ i ];
		#endif
	}
#endif`,wf=`#ifdef USE_MORPHNORMALS
	objectNormal *= morphTargetBaseInfluence;
	for ( int i = 0; i < MORPHTARGETS_COUNT; i ++ ) {
		if ( morphTargetInfluences[ i ] != 0.0 ) objectNormal += getMorph( gl_VertexID, i, 1 ).xyz * morphTargetInfluences[ i ];
	}
#endif`,Rf=`#ifdef USE_MORPHTARGETS
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
#endif`,Cf=`#ifdef USE_MORPHTARGETS
	transformed *= morphTargetBaseInfluence;
	for ( int i = 0; i < MORPHTARGETS_COUNT; i ++ ) {
		if ( morphTargetInfluences[ i ] != 0.0 ) transformed += getMorph( gl_VertexID, i, 0 ).xyz * morphTargetInfluences[ i ];
	}
#endif`,Pf=`float faceDirection = gl_FrontFacing ? 1.0 : - 1.0;
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
vec3 nonPerturbedNormal = normal;`,Df=`#ifdef USE_NORMALMAP_OBJECTSPACE
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
#endif`,Lf=`#ifndef FLAT_SHADED
	varying vec3 vNormal;
	#ifdef USE_TANGENT
		varying vec3 vTangent;
		varying vec3 vBitangent;
	#endif
#endif`,If=`#ifndef FLAT_SHADED
	varying vec3 vNormal;
	#ifdef USE_TANGENT
		varying vec3 vTangent;
		varying vec3 vBitangent;
	#endif
#endif`,Nf=`#ifndef FLAT_SHADED
	vNormal = normalize( transformedNormal );
	#ifdef USE_TANGENT
		vTangent = normalize( transformedTangent );
		vBitangent = normalize( cross( vNormal, vTangent ) * tangent.w );
		#ifdef FLIP_SIDED
			vBitangent = - vBitangent;
		#endif
	#endif
#endif`,Uf=`#ifdef USE_NORMALMAP
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
#endif`,Ff=`#ifdef USE_CLEARCOAT
	vec3 clearcoatNormal = nonPerturbedNormal;
#endif`,Of=`#ifdef USE_CLEARCOAT_NORMALMAP
	vec3 clearcoatMapN = texture2D( clearcoatNormalMap, vClearcoatNormalMapUv ).xyz * 2.0 - 1.0;
	clearcoatMapN.xy *= clearcoatNormalScale;
	clearcoatNormal = normalize( tbn2 * clearcoatMapN );
#endif`,Bf=`#ifdef USE_CLEARCOATMAP
	uniform sampler2D clearcoatMap;
#endif
#ifdef USE_CLEARCOAT_NORMALMAP
	uniform sampler2D clearcoatNormalMap;
	uniform vec2 clearcoatNormalScale;
#endif
#ifdef USE_CLEARCOAT_ROUGHNESSMAP
	uniform sampler2D clearcoatRoughnessMap;
#endif`,zf=`#ifdef USE_IRIDESCENCEMAP
	uniform sampler2D iridescenceMap;
#endif
#ifdef USE_IRIDESCENCE_THICKNESSMAP
	uniform sampler2D iridescenceThicknessMap;
#endif`,kf=`#ifdef OPAQUE
diffuseColor.a = 1.0;
#endif
#ifdef USE_TRANSMISSION
diffuseColor.a *= material.transmissionAlpha;
#endif
gl_FragColor = vec4( outgoingLight, diffuseColor.a );`,Vf=`vec3 packNormalToRGB( const in vec3 normal ) {
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
}`,Gf=`#ifdef PREMULTIPLIED_ALPHA
	gl_FragColor.rgb *= gl_FragColor.a;
#endif`,Hf=`vec4 mvPosition = vec4( transformed, 1.0 );
#ifdef USE_BATCHING
	mvPosition = batchingMatrix * mvPosition;
#endif
#ifdef USE_INSTANCING
	mvPosition = instanceMatrix * mvPosition;
#endif
mvPosition = modelViewMatrix * mvPosition;
gl_Position = projectionMatrix * mvPosition;`,Wf=`#ifdef DITHERING
	gl_FragColor.rgb = dithering( gl_FragColor.rgb );
#endif`,Xf=`#ifdef DITHERING
	vec3 dithering( vec3 color ) {
		float grid_position = rand( gl_FragCoord.xy );
		vec3 dither_shift_RGB = vec3( 0.25 / 255.0, -0.25 / 255.0, 0.25 / 255.0 );
		dither_shift_RGB = mix( 2.0 * dither_shift_RGB, -2.0 * dither_shift_RGB, grid_position );
		return color + dither_shift_RGB;
	}
#endif`,qf=`float roughnessFactor = roughness;
#ifdef USE_ROUGHNESSMAP
	vec4 texelRoughness = texture2D( roughnessMap, vRoughnessMapUv );
	roughnessFactor *= texelRoughness.g;
#endif`,Yf=`#ifdef USE_ROUGHNESSMAP
	uniform sampler2D roughnessMap;
#endif`,$f=`#if NUM_SPOT_LIGHT_COORDS > 0
	varying vec4 vSpotLightCoord[ NUM_SPOT_LIGHT_COORDS ];
#endif
#if NUM_SPOT_LIGHT_MAPS > 0
	uniform sampler2D spotLightMap[ NUM_SPOT_LIGHT_MAPS ];
#endif
#ifdef USE_SHADOWMAP
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
			shadowCoord.z += shadowBias;
			bool inFrustum = shadowCoord.x >= 0.0 && shadowCoord.x <= 1.0 && shadowCoord.y >= 0.0 && shadowCoord.y <= 1.0;
			bool frustumTest = inFrustum && shadowCoord.z <= 1.0;
			if ( frustumTest ) {
				vec2 texelSize = vec2( 1.0 ) / shadowMapSize;
				float radius = shadowRadius * texelSize.x;
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
	#if NUM_POINT_LIGHT_SHADOWS > 0
	#if defined( SHADOWMAP_TYPE_PCF )
	float getPointShadow( samplerCubeShadow shadowMap, vec2 shadowMapSize, float shadowIntensity, float shadowBias, float shadowRadius, vec4 shadowCoord, float shadowCameraNear, float shadowCameraFar ) {
		float shadow = 1.0;
		vec3 lightToPosition = shadowCoord.xyz;
		vec3 bd3D = normalize( lightToPosition );
		vec3 absVec = abs( lightToPosition );
		float viewSpaceZ = max( max( absVec.x, absVec.y ), absVec.z );
		if ( viewSpaceZ - shadowCameraFar <= 0.0 && viewSpaceZ - shadowCameraNear >= 0.0 ) {
			#ifdef USE_REVERSED_DEPTH_BUFFER
				float dp = ( shadowCameraNear * ( shadowCameraFar - viewSpaceZ ) ) / ( viewSpaceZ * ( shadowCameraFar - shadowCameraNear ) );
				dp -= shadowBias;
			#else
				float dp = ( shadowCameraFar * ( viewSpaceZ - shadowCameraNear ) ) / ( viewSpaceZ * ( shadowCameraFar - shadowCameraNear ) );
				dp += shadowBias;
			#endif
			float texelSize = shadowRadius / shadowMapSize.x;
			vec3 absDir = abs( bd3D );
			vec3 tangent = absDir.x > absDir.z ? vec3( 0.0, 1.0, 0.0 ) : vec3( 1.0, 0.0, 0.0 );
			tangent = normalize( cross( bd3D, tangent ) );
			vec3 bitangent = cross( bd3D, tangent );
			float phi = interleavedGradientNoise( gl_FragCoord.xy ) * PI2;
			vec2 sample0 = vogelDiskSample( 0, 5, phi );
			vec2 sample1 = vogelDiskSample( 1, 5, phi );
			vec2 sample2 = vogelDiskSample( 2, 5, phi );
			vec2 sample3 = vogelDiskSample( 3, 5, phi );
			vec2 sample4 = vogelDiskSample( 4, 5, phi );
			shadow = (
				texture( shadowMap, vec4( bd3D + ( tangent * sample0.x + bitangent * sample0.y ) * texelSize, dp ) ) +
				texture( shadowMap, vec4( bd3D + ( tangent * sample1.x + bitangent * sample1.y ) * texelSize, dp ) ) +
				texture( shadowMap, vec4( bd3D + ( tangent * sample2.x + bitangent * sample2.y ) * texelSize, dp ) ) +
				texture( shadowMap, vec4( bd3D + ( tangent * sample3.x + bitangent * sample3.y ) * texelSize, dp ) ) +
				texture( shadowMap, vec4( bd3D + ( tangent * sample4.x + bitangent * sample4.y ) * texelSize, dp ) )
			) * 0.2;
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
#endif`,Kf=`#if NUM_SPOT_LIGHT_COORDS > 0
	uniform mat4 spotLightMatrix[ NUM_SPOT_LIGHT_COORDS ];
	varying vec4 vSpotLightCoord[ NUM_SPOT_LIGHT_COORDS ];
#endif
#ifdef USE_SHADOWMAP
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
#endif`,Zf=`#if ( defined( USE_SHADOWMAP ) && ( NUM_DIR_LIGHT_SHADOWS > 0 || NUM_POINT_LIGHT_SHADOWS > 0 ) ) || ( NUM_SPOT_LIGHT_COORDS > 0 )
	#ifdef HAS_NORMAL
		vec3 shadowWorldNormal = transformNormalByInverseViewMatrix( transformedNormal, viewMatrix );
	#else
		vec3 shadowWorldNormal = vec3( 0.0 );
	#endif
	vec4 shadowWorldPosition;
#endif
#if defined( USE_SHADOWMAP )
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
#endif`,Jf=`float getShadowMask() {
	float shadow = 1.0;
	#ifdef USE_SHADOWMAP
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
}`,jf=`#ifdef USE_SKINNING
	mat4 boneMatX = getBoneMatrix( skinIndex.x );
	mat4 boneMatY = getBoneMatrix( skinIndex.y );
	mat4 boneMatZ = getBoneMatrix( skinIndex.z );
	mat4 boneMatW = getBoneMatrix( skinIndex.w );
#endif`,Qf=`#ifdef USE_SKINNING
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
#endif`,tp=`#ifdef USE_SKINNING
	vec4 skinVertex = bindMatrix * vec4( transformed, 1.0 );
	vec4 skinned = vec4( 0.0 );
	skinned += boneMatX * skinVertex * skinWeight.x;
	skinned += boneMatY * skinVertex * skinWeight.y;
	skinned += boneMatZ * skinVertex * skinWeight.z;
	skinned += boneMatW * skinVertex * skinWeight.w;
	transformed = ( bindMatrixInverse * skinned ).xyz;
#endif`,ep=`#ifdef USE_SKINNING
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
#endif`,np=`float specularStrength;
#ifdef USE_SPECULARMAP
	vec4 texelSpecular = texture2D( specularMap, vSpecularMapUv );
	specularStrength = texelSpecular.r;
#else
	specularStrength = 1.0;
#endif`,ip=`#ifdef USE_SPECULARMAP
	uniform sampler2D specularMap;
#endif`,sp=`#if defined( TONE_MAPPING )
	gl_FragColor.rgb = toneMapping( gl_FragColor.rgb );
#endif`,rp=`#ifndef saturate
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
vec3 CustomToneMapping( vec3 color ) { return color; }`,ap=`#ifdef USE_TRANSMISSION
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
#endif`,op=`#ifdef USE_TRANSMISSION
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
#endif`,lp=`#if defined( USE_UV ) || defined( USE_ANISOTROPY )
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
#endif`,cp=`#if defined( USE_UV ) || defined( USE_ANISOTROPY )
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
#endif`,hp=`#if defined( USE_UV ) || defined( USE_ANISOTROPY )
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
#endif`,up=`#if defined( USE_ENVMAP ) || defined( DISTANCE ) || defined ( USE_SHADOWMAP ) || defined ( USE_TRANSMISSION ) || NUM_SPOT_LIGHT_COORDS > 0
	vec4 worldPosition = vec4( transformed, 1.0 );
	#ifdef USE_BATCHING
		worldPosition = batchingMatrix * worldPosition;
	#endif
	#ifdef USE_INSTANCING
		worldPosition = instanceMatrix * worldPosition;
	#endif
	worldPosition = modelMatrix * worldPosition;
#endif`;const dp=`varying vec2 vUv;
uniform mat3 uvTransform;
void main() {
	vUv = ( uvTransform * vec3( uv, 1 ) ).xy;
	gl_Position = vec4( position.xy, 1.0, 1.0 );
}`,fp=`uniform sampler2D t2D;
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
}`,pp=`varying vec3 vWorldDirection;
#include <common>
void main() {
	vWorldDirection = transformDirection( position, modelMatrix );
	#include <begin_vertex>
	#include <project_vertex>
	gl_Position.z = gl_Position.w;
}`,mp=`#ifdef ENVMAP_TYPE_CUBE
	uniform samplerCube envMap;
#elif defined( ENVMAP_TYPE_CUBE_UV )
	uniform sampler2D envMap;
#endif
uniform float backgroundBlurriness;
uniform float backgroundIntensity;
uniform mat3 backgroundRotation;
varying vec3 vWorldDirection;
#include <cube_uv_reflection_fragment>
void main() {
	#ifdef ENVMAP_TYPE_CUBE
		vec4 texColor = textureCube( envMap, backgroundRotation * vWorldDirection );
	#elif defined( ENVMAP_TYPE_CUBE_UV )
		vec4 texColor = textureCubeUV( envMap, backgroundRotation * vWorldDirection, backgroundBlurriness );
	#else
		vec4 texColor = vec4( 0.0, 0.0, 0.0, 1.0 );
	#endif
	texColor.rgb *= backgroundIntensity;
	gl_FragColor = texColor;
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
}`,gp=`varying vec3 vWorldDirection;
#include <common>
void main() {
	vWorldDirection = transformDirection( position, modelMatrix );
	#include <begin_vertex>
	#include <project_vertex>
	gl_Position.z = gl_Position.w;
}`,_p=`uniform samplerCube tCube;
uniform float tFlip;
uniform float opacity;
varying vec3 vWorldDirection;
void main() {
	vec4 texColor = textureCube( tCube, vec3( tFlip * vWorldDirection.x, vWorldDirection.yz ) );
	gl_FragColor = texColor;
	gl_FragColor.a *= opacity;
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
}`,xp=`#include <common>
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
}`,vp=`#if DEPTH_PACKING == 3200
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
}`,Mp=`#define DISTANCE
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
}`,Sp=`#define DISTANCE
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
}`,yp=`varying vec3 vWorldDirection;
#include <common>
void main() {
	vWorldDirection = transformDirection( position, modelMatrix );
	#include <begin_vertex>
	#include <project_vertex>
}`,bp=`uniform sampler2D tEquirect;
varying vec3 vWorldDirection;
#include <common>
void main() {
	vec3 direction = normalize( vWorldDirection );
	vec2 sampleUV = equirectUv( direction );
	gl_FragColor = texture2D( tEquirect, sampleUV );
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
}`,Ep=`uniform float scale;
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
}`,Tp=`uniform vec3 diffuse;
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
}`,Ap=`#include <common>
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
}`,wp=`uniform vec3 diffuse;
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
}`,Rp=`#define LAMBERT
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
}`,Cp=`#define LAMBERT
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
#include <cube_uv_reflection_fragment>
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
}`,Pp=`#define MATCAP
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
}`,Dp=`#define MATCAP
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
}`,Lp=`#define NORMAL
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
}`,Ip=`#define NORMAL
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
}`,Np=`#define PHONG
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
}`,Up=`#define PHONG
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
#include <cube_uv_reflection_fragment>
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
}`,Fp=`#define STANDARD
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
}`,Op=`#define STANDARD
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
#ifdef USE_DISPERSION
	uniform float dispersion;
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
#include <cube_uv_reflection_fragment>
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
}`,Bp=`#define TOON
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
}`,zp=`#define TOON
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
}`,kp=`uniform float size;
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
}`,Vp=`uniform vec3 diffuse;
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
}`,Gp=`#include <common>
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
}`,Hp=`uniform vec3 color;
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
}`,Wp=`uniform float rotation;
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
}`,Xp=`uniform vec3 diffuse;
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
}`,$t={alphahash_fragment:ud,alphahash_pars_fragment:dd,alphamap_fragment:fd,alphamap_pars_fragment:pd,alphatest_fragment:md,alphatest_pars_fragment:gd,aomap_fragment:_d,aomap_pars_fragment:xd,batching_pars_vertex:vd,batching_vertex:Md,begin_vertex:Sd,beginnormal_vertex:yd,bsdfs:bd,iridescence_fragment:Ed,bumpmap_pars_fragment:Td,clipping_planes_fragment:Ad,clipping_planes_pars_fragment:wd,clipping_planes_pars_vertex:Rd,clipping_planes_vertex:Cd,color_fragment:Pd,color_pars_fragment:Dd,color_pars_vertex:Ld,color_vertex:Id,common:Nd,cube_uv_reflection_fragment:Ud,defaultnormal_vertex:Fd,displacementmap_pars_vertex:Od,displacementmap_vertex:Bd,emissivemap_fragment:zd,emissivemap_pars_fragment:kd,colorspace_fragment:Vd,colorspace_pars_fragment:Gd,envmap_fragment:Hd,envmap_common_pars_fragment:Wd,envmap_pars_fragment:Xd,envmap_pars_vertex:qd,envmap_physical_pars_fragment:sf,envmap_vertex:Yd,fog_vertex:$d,fog_pars_vertex:Kd,fog_fragment:Zd,fog_pars_fragment:Jd,gradientmap_pars_fragment:jd,lightmap_pars_fragment:Qd,lights_lambert_fragment:tf,lights_lambert_pars_fragment:ef,lights_pars_begin:nf,lights_toon_fragment:rf,lights_toon_pars_fragment:af,lights_phong_fragment:of,lights_phong_pars_fragment:lf,lights_physical_fragment:cf,lights_physical_pars_fragment:hf,lights_fragment_begin:uf,lights_fragment_maps:df,lights_fragment_end:ff,lightprobes_pars_fragment:pf,logdepthbuf_fragment:mf,logdepthbuf_pars_fragment:gf,logdepthbuf_pars_vertex:_f,logdepthbuf_vertex:xf,map_fragment:vf,map_pars_fragment:Mf,map_particle_fragment:Sf,map_particle_pars_fragment:yf,metalnessmap_fragment:bf,metalnessmap_pars_fragment:Ef,morphinstance_vertex:Tf,morphcolor_vertex:Af,morphnormal_vertex:wf,morphtarget_pars_vertex:Rf,morphtarget_vertex:Cf,normal_fragment_begin:Pf,normal_fragment_maps:Df,normal_pars_fragment:Lf,normal_pars_vertex:If,normal_vertex:Nf,normalmap_pars_fragment:Uf,clearcoat_normal_fragment_begin:Ff,clearcoat_normal_fragment_maps:Of,clearcoat_pars_fragment:Bf,iridescence_pars_fragment:zf,opaque_fragment:kf,packing:Vf,premultiplied_alpha_fragment:Gf,project_vertex:Hf,dithering_fragment:Wf,dithering_pars_fragment:Xf,roughnessmap_fragment:qf,roughnessmap_pars_fragment:Yf,shadowmap_pars_fragment:$f,shadowmap_pars_vertex:Kf,shadowmap_vertex:Zf,shadowmask_pars_fragment:Jf,skinbase_vertex:jf,skinning_pars_vertex:Qf,skinning_vertex:tp,skinnormal_vertex:ep,specularmap_fragment:np,specularmap_pars_fragment:ip,tonemapping_fragment:sp,tonemapping_pars_fragment:rp,transmission_fragment:ap,transmission_pars_fragment:op,uv_pars_fragment:lp,uv_pars_vertex:cp,uv_vertex:hp,worldpos_vertex:up,background_vert:dp,background_frag:fp,backgroundCube_vert:pp,backgroundCube_frag:mp,cube_vert:gp,cube_frag:_p,depth_vert:xp,depth_frag:vp,distance_vert:Mp,distance_frag:Sp,equirect_vert:yp,equirect_frag:bp,linedashed_vert:Ep,linedashed_frag:Tp,meshbasic_vert:Ap,meshbasic_frag:wp,meshlambert_vert:Rp,meshlambert_frag:Cp,meshmatcap_vert:Pp,meshmatcap_frag:Dp,meshnormal_vert:Lp,meshnormal_frag:Ip,meshphong_vert:Np,meshphong_frag:Up,meshphysical_vert:Fp,meshphysical_frag:Op,meshtoon_vert:Bp,meshtoon_frag:zp,points_vert:kp,points_frag:Vp,shadow_vert:Gp,shadow_frag:Hp,sprite_vert:Wp,sprite_frag:Xp},Mt={common:{diffuse:{value:new Ft(16777215)},opacity:{value:1},map:{value:null},mapTransform:{value:new Ht},alphaMap:{value:null},alphaMapTransform:{value:new Ht},alphaTest:{value:0}},specularmap:{specularMap:{value:null},specularMapTransform:{value:new Ht}},envmap:{envMap:{value:null},envMapRotation:{value:new Ht},reflectivity:{value:1},ior:{value:1.5},refractionRatio:{value:.98},dfgLUT:{value:null}},aomap:{aoMap:{value:null},aoMapIntensity:{value:1},aoMapTransform:{value:new Ht}},lightmap:{lightMap:{value:null},lightMapIntensity:{value:1},lightMapTransform:{value:new Ht}},bumpmap:{bumpMap:{value:null},bumpMapTransform:{value:new Ht},bumpScale:{value:1}},normalmap:{normalMap:{value:null},normalMapTransform:{value:new Ht},normalScale:{value:new zt(1,1)}},displacementmap:{displacementMap:{value:null},displacementMapTransform:{value:new Ht},displacementScale:{value:1},displacementBias:{value:0}},emissivemap:{emissiveMap:{value:null},emissiveMapTransform:{value:new Ht}},metalnessmap:{metalnessMap:{value:null},metalnessMapTransform:{value:new Ht}},roughnessmap:{roughnessMap:{value:null},roughnessMapTransform:{value:new Ht}},gradientmap:{gradientMap:{value:null}},fog:{fogDensity:{value:25e-5},fogNear:{value:1},fogFar:{value:2e3},fogColor:{value:new Ft(16777215)}},lights:{ambientLightColor:{value:[]},lightProbe:{value:[]},directionalLights:{value:[],properties:{direction:{},color:{}}},directionalLightShadows:{value:[],properties:{shadowIntensity:1,shadowBias:{},shadowNormalBias:{},shadowRadius:{},shadowMapSize:{}}},directionalShadowMatrix:{value:[]},spotLights:{value:[],properties:{color:{},position:{},direction:{},distance:{},coneCos:{},penumbraCos:{},decay:{}}},spotLightShadows:{value:[],properties:{shadowIntensity:1,shadowBias:{},shadowNormalBias:{},shadowRadius:{},shadowMapSize:{}}},spotLightMap:{value:[]},spotLightMatrix:{value:[]},pointLights:{value:[],properties:{color:{},position:{},decay:{},distance:{}}},pointLightShadows:{value:[],properties:{shadowIntensity:1,shadowBias:{},shadowNormalBias:{},shadowRadius:{},shadowMapSize:{},shadowCameraNear:{},shadowCameraFar:{}}},pointShadowMatrix:{value:[]},hemisphereLights:{value:[],properties:{direction:{},skyColor:{},groundColor:{}}},rectAreaLights:{value:[],properties:{color:{},position:{},width:{},height:{}}},ltc_1:{value:null},ltc_2:{value:null},probesSH:{value:null},probesMin:{value:new I},probesMax:{value:new I},probesResolution:{value:new I}},points:{diffuse:{value:new Ft(16777215)},opacity:{value:1},size:{value:1},scale:{value:1},map:{value:null},alphaMap:{value:null},alphaMapTransform:{value:new Ht},alphaTest:{value:0},uvTransform:{value:new Ht}},sprite:{diffuse:{value:new Ft(16777215)},opacity:{value:1},center:{value:new zt(.5,.5)},rotation:{value:0},map:{value:null},mapTransform:{value:new Ht},alphaMap:{value:null},alphaMapTransform:{value:new Ht},alphaTest:{value:0}}},gn={basic:{uniforms:Ue([Mt.common,Mt.specularmap,Mt.envmap,Mt.aomap,Mt.lightmap,Mt.fog]),vertexShader:$t.meshbasic_vert,fragmentShader:$t.meshbasic_frag},lambert:{uniforms:Ue([Mt.common,Mt.specularmap,Mt.envmap,Mt.aomap,Mt.lightmap,Mt.emissivemap,Mt.bumpmap,Mt.normalmap,Mt.displacementmap,Mt.fog,Mt.lights,{emissive:{value:new Ft(0)},envMapIntensity:{value:1}}]),vertexShader:$t.meshlambert_vert,fragmentShader:$t.meshlambert_frag},phong:{uniforms:Ue([Mt.common,Mt.specularmap,Mt.envmap,Mt.aomap,Mt.lightmap,Mt.emissivemap,Mt.bumpmap,Mt.normalmap,Mt.displacementmap,Mt.fog,Mt.lights,{emissive:{value:new Ft(0)},specular:{value:new Ft(1118481)},shininess:{value:30},envMapIntensity:{value:1}}]),vertexShader:$t.meshphong_vert,fragmentShader:$t.meshphong_frag},standard:{uniforms:Ue([Mt.common,Mt.envmap,Mt.aomap,Mt.lightmap,Mt.emissivemap,Mt.bumpmap,Mt.normalmap,Mt.displacementmap,Mt.roughnessmap,Mt.metalnessmap,Mt.fog,Mt.lights,{emissive:{value:new Ft(0)},roughness:{value:1},metalness:{value:0},envMapIntensity:{value:1}}]),vertexShader:$t.meshphysical_vert,fragmentShader:$t.meshphysical_frag},toon:{uniforms:Ue([Mt.common,Mt.aomap,Mt.lightmap,Mt.emissivemap,Mt.bumpmap,Mt.normalmap,Mt.displacementmap,Mt.gradientmap,Mt.fog,Mt.lights,{emissive:{value:new Ft(0)}}]),vertexShader:$t.meshtoon_vert,fragmentShader:$t.meshtoon_frag},matcap:{uniforms:Ue([Mt.common,Mt.bumpmap,Mt.normalmap,Mt.displacementmap,Mt.fog,{matcap:{value:null}}]),vertexShader:$t.meshmatcap_vert,fragmentShader:$t.meshmatcap_frag},points:{uniforms:Ue([Mt.points,Mt.fog]),vertexShader:$t.points_vert,fragmentShader:$t.points_frag},dashed:{uniforms:Ue([Mt.common,Mt.fog,{scale:{value:1},dashSize:{value:1},totalSize:{value:2}}]),vertexShader:$t.linedashed_vert,fragmentShader:$t.linedashed_frag},depth:{uniforms:Ue([Mt.common,Mt.displacementmap]),vertexShader:$t.depth_vert,fragmentShader:$t.depth_frag},normal:{uniforms:Ue([Mt.common,Mt.bumpmap,Mt.normalmap,Mt.displacementmap,{opacity:{value:1}}]),vertexShader:$t.meshnormal_vert,fragmentShader:$t.meshnormal_frag},sprite:{uniforms:Ue([Mt.sprite,Mt.fog]),vertexShader:$t.sprite_vert,fragmentShader:$t.sprite_frag},background:{uniforms:{uvTransform:{value:new Ht},t2D:{value:null},backgroundIntensity:{value:1}},vertexShader:$t.background_vert,fragmentShader:$t.background_frag},backgroundCube:{uniforms:{envMap:{value:null},backgroundBlurriness:{value:0},backgroundIntensity:{value:1},backgroundRotation:{value:new Ht}},vertexShader:$t.backgroundCube_vert,fragmentShader:$t.backgroundCube_frag},cube:{uniforms:{tCube:{value:null},tFlip:{value:-1},opacity:{value:1}},vertexShader:$t.cube_vert,fragmentShader:$t.cube_frag},equirect:{uniforms:{tEquirect:{value:null}},vertexShader:$t.equirect_vert,fragmentShader:$t.equirect_frag},distance:{uniforms:Ue([Mt.common,Mt.displacementmap,{referencePosition:{value:new I},nearDistance:{value:1},farDistance:{value:1e3}}]),vertexShader:$t.distance_vert,fragmentShader:$t.distance_frag},shadow:{uniforms:Ue([Mt.lights,Mt.fog,{color:{value:new Ft(0)},opacity:{value:1}}]),vertexShader:$t.shadow_vert,fragmentShader:$t.shadow_frag}};gn.physical={uniforms:Ue([gn.standard.uniforms,{clearcoat:{value:0},clearcoatMap:{value:null},clearcoatMapTransform:{value:new Ht},clearcoatNormalMap:{value:null},clearcoatNormalMapTransform:{value:new Ht},clearcoatNormalScale:{value:new zt(1,1)},clearcoatRoughness:{value:0},clearcoatRoughnessMap:{value:null},clearcoatRoughnessMapTransform:{value:new Ht},dispersion:{value:0},iridescence:{value:0},iridescenceMap:{value:null},iridescenceMapTransform:{value:new Ht},iridescenceIOR:{value:1.3},iridescenceThicknessMinimum:{value:100},iridescenceThicknessMaximum:{value:400},iridescenceThicknessMap:{value:null},iridescenceThicknessMapTransform:{value:new Ht},sheen:{value:0},sheenColor:{value:new Ft(0)},sheenColorMap:{value:null},sheenColorMapTransform:{value:new Ht},sheenRoughness:{value:1},sheenRoughnessMap:{value:null},sheenRoughnessMapTransform:{value:new Ht},transmission:{value:0},transmissionMap:{value:null},transmissionMapTransform:{value:new Ht},transmissionSamplerSize:{value:new zt},transmissionSamplerMap:{value:null},thickness:{value:0},thicknessMap:{value:null},thicknessMapTransform:{value:new Ht},attenuationDistance:{value:0},attenuationColor:{value:new Ft(0)},specularColor:{value:new Ft(1,1,1)},specularColorMap:{value:null},specularColorMapTransform:{value:new Ht},specularIntensity:{value:1},specularIntensityMap:{value:null},specularIntensityMapTransform:{value:new Ht},anisotropyVector:{value:new zt},anisotropyMap:{value:null},anisotropyMapTransform:{value:new Ht}}]),vertexShader:$t.meshphysical_vert,fragmentShader:$t.meshphysical_frag};const rr={r:0,b:0,g:0},qp=new se,ah=new Ht;ah.set(-1,0,0,0,1,0,0,0,1);function Yp(i,t,e,n,s,r){const a=new Ft(0);let o=s===!0?0:1,c,l,h=null,d=0,u=null;function p(E){let w=E.isScene===!0?E.background:null;if(w&&w.isTexture){const y=E.backgroundBlurriness>0;w=t.get(w,y)}return w}function g(E){let w=!1;const y=p(E);y===null?m(a,o):y&&y.isColor&&(m(y,1),w=!0);const b=i.xr.getEnvironmentBlendMode();b==="additive"?e.buffers.color.setClear(0,0,0,1,r):b==="alpha-blend"&&e.buffers.color.setClear(0,0,0,0,r),(i.autoClear||w)&&(e.buffers.depth.setTest(!0),e.buffers.depth.setMask(!0),e.buffers.color.setMask(!0),i.clear(i.autoClearColor,i.autoClearDepth,i.autoClearStencil))}function v(E,w){const y=p(w);y&&(y.isCubeTexture||y.mapping===Dr)?(l===void 0&&(l=new Me(new zn(1,1,1),new En({name:"BackgroundCubeMaterial",uniforms:$i(gn.backgroundCube.uniforms),vertexShader:gn.backgroundCube.vertexShader,fragmentShader:gn.backgroundCube.fragmentShader,side:ke,depthTest:!1,depthWrite:!1,fog:!1,allowOverride:!1})),l.geometry.deleteAttribute("normal"),l.geometry.deleteAttribute("uv"),l.onBeforeRender=function(b,M,A){this.matrixWorld.copyPosition(A.matrixWorld)},Object.defineProperty(l.material,"envMap",{get:function(){return this.uniforms.envMap.value}}),n.update(l)),l.material.uniforms.envMap.value=y,l.material.uniforms.backgroundBlurriness.value=w.backgroundBlurriness,l.material.uniforms.backgroundIntensity.value=w.backgroundIntensity,l.material.uniforms.backgroundRotation.value.setFromMatrix4(qp.makeRotationFromEuler(w.backgroundRotation)).transpose(),y.isCubeTexture&&y.isRenderTargetTexture===!1&&l.material.uniforms.backgroundRotation.value.premultiply(ah),l.material.toneMapped=Qt.getTransfer(y.colorSpace)!==re,(h!==y||d!==y.version||u!==i.toneMapping)&&(l.material.needsUpdate=!0,h=y,d=y.version,u=i.toneMapping),l.layers.enableAll(),E.unshift(l,l.geometry,l.material,0,0,null)):y&&y.isTexture&&(c===void 0&&(c=new Me(new ws(2,2),new En({name:"BackgroundMaterial",uniforms:$i(gn.background.uniforms),vertexShader:gn.background.vertexShader,fragmentShader:gn.background.fragmentShader,side:ni,depthTest:!1,depthWrite:!1,fog:!1,allowOverride:!1})),c.geometry.deleteAttribute("normal"),Object.defineProperty(c.material,"map",{get:function(){return this.uniforms.t2D.value}}),n.update(c)),c.material.uniforms.t2D.value=y,c.material.uniforms.backgroundIntensity.value=w.backgroundIntensity,c.material.toneMapped=Qt.getTransfer(y.colorSpace)!==re,y.matrixAutoUpdate===!0&&y.updateMatrix(),c.material.uniforms.uvTransform.value.copy(y.matrix),(h!==y||d!==y.version||u!==i.toneMapping)&&(c.material.needsUpdate=!0,h=y,d=y.version,u=i.toneMapping),c.layers.enableAll(),E.unshift(c,c.geometry,c.material,0,0,null))}function m(E,w){E.getRGB(rr,eh(i)),e.buffers.color.setClear(rr.r,rr.g,rr.b,w,r)}function f(){l!==void 0&&(l.geometry.dispose(),l.material.dispose(),l=void 0),c!==void 0&&(c.geometry.dispose(),c.material.dispose(),c=void 0)}return{getClearColor:function(){return a},setClearColor:function(E,w=1){a.set(E),o=w,m(a,o)},getClearAlpha:function(){return o},setClearAlpha:function(E){o=E,m(a,o)},render:g,addToRenderList:v,dispose:f}}function $p(i,t){const e=i.getParameter(i.MAX_VERTEX_ATTRIBS),n={},s=u(null);let r=s,a=!1;function o(D,B,$,J,z){let H=!1;const W=d(D,J,$,B);r!==W&&(r=W,l(r.object)),H=p(D,J,$,z),H&&g(D,J,$,z),z!==null&&t.update(z,i.ELEMENT_ARRAY_BUFFER),(H||a)&&(a=!1,y(D,B,$,J),z!==null&&i.bindBuffer(i.ELEMENT_ARRAY_BUFFER,t.get(z).buffer))}function c(){return i.createVertexArray()}function l(D){return i.bindVertexArray(D)}function h(D){return i.deleteVertexArray(D)}function d(D,B,$,J){const z=J.wireframe===!0;let H=n[B.id];H===void 0&&(H={},n[B.id]=H);const W=D.isInstancedMesh===!0?D.id:0;let tt=H[W];tt===void 0&&(tt={},H[W]=tt);let Q=tt[$.id];Q===void 0&&(Q={},tt[$.id]=Q);let ht=Q[z];return ht===void 0&&(ht=u(c()),Q[z]=ht),ht}function u(D){const B=[],$=[],J=[];for(let z=0;z<e;z++)B[z]=0,$[z]=0,J[z]=0;return{geometry:null,program:null,wireframe:!1,newAttributes:B,enabledAttributes:$,attributeDivisors:J,object:D,attributes:{},index:null}}function p(D,B,$,J){const z=r.attributes,H=B.attributes;let W=0;const tt=$.getAttributes();for(const Q in tt)if(tt[Q].location>=0){const ut=z[Q];let St=H[Q];if(St===void 0&&(Q==="instanceMatrix"&&D.instanceMatrix&&(St=D.instanceMatrix),Q==="instanceColor"&&D.instanceColor&&(St=D.instanceColor)),ut===void 0||ut.attribute!==St||St&&ut.data!==St.data)return!0;W++}return r.attributesNum!==W||r.index!==J}function g(D,B,$,J){const z={},H=B.attributes;let W=0;const tt=$.getAttributes();for(const Q in tt)if(tt[Q].location>=0){let ut=H[Q];ut===void 0&&(Q==="instanceMatrix"&&D.instanceMatrix&&(ut=D.instanceMatrix),Q==="instanceColor"&&D.instanceColor&&(ut=D.instanceColor));const St={};St.attribute=ut,ut&&ut.data&&(St.data=ut.data),z[Q]=St,W++}r.attributes=z,r.attributesNum=W,r.index=J}function v(){const D=r.newAttributes;for(let B=0,$=D.length;B<$;B++)D[B]=0}function m(D){f(D,0)}function f(D,B){const $=r.newAttributes,J=r.enabledAttributes,z=r.attributeDivisors;$[D]=1,J[D]===0&&(i.enableVertexAttribArray(D),J[D]=1),z[D]!==B&&(i.vertexAttribDivisor(D,B),z[D]=B)}function E(){const D=r.newAttributes,B=r.enabledAttributes;for(let $=0,J=B.length;$<J;$++)B[$]!==D[$]&&(i.disableVertexAttribArray($),B[$]=0)}function w(D,B,$,J,z,H,W){W===!0?i.vertexAttribIPointer(D,B,$,z,H):i.vertexAttribPointer(D,B,$,J,z,H)}function y(D,B,$,J){v();const z=J.attributes,H=$.getAttributes(),W=B.defaultAttributeValues;for(const tt in H){const Q=H[tt];if(Q.location>=0){let ht=z[tt];if(ht===void 0&&(tt==="instanceMatrix"&&D.instanceMatrix&&(ht=D.instanceMatrix),tt==="instanceColor"&&D.instanceColor&&(ht=D.instanceColor)),ht!==void 0){const ut=ht.normalized,St=ht.itemSize,Bt=t.get(ht);if(Bt===void 0)continue;const Yt=Bt.buffer,Vt=Bt.type,C=Bt.bytesPerElement,P=Vt===i.INT||Vt===i.UNSIGNED_INT||ht.gpuType===Eo;if(ht.isInterleavedBufferAttribute){const U=ht.data,q=U.stride,Z=ht.offset;if(U.isInstancedInterleavedBuffer){for(let V=0;V<Q.locationSize;V++)f(Q.location+V,U.meshPerAttribute);D.isInstancedMesh!==!0&&J._maxInstanceCount===void 0&&(J._maxInstanceCount=U.meshPerAttribute*U.count)}else for(let V=0;V<Q.locationSize;V++)m(Q.location+V);i.bindBuffer(i.ARRAY_BUFFER,Yt);for(let V=0;V<Q.locationSize;V++)w(Q.location+V,St/Q.locationSize,Vt,ut,q*C,(Z+St/Q.locationSize*V)*C,P)}else{if(ht.isInstancedBufferAttribute){for(let U=0;U<Q.locationSize;U++)f(Q.location+U,ht.meshPerAttribute);D.isInstancedMesh!==!0&&J._maxInstanceCount===void 0&&(J._maxInstanceCount=ht.meshPerAttribute*ht.count)}else for(let U=0;U<Q.locationSize;U++)m(Q.location+U);i.bindBuffer(i.ARRAY_BUFFER,Yt);for(let U=0;U<Q.locationSize;U++)w(Q.location+U,St/Q.locationSize,Vt,ut,St*C,St/Q.locationSize*U*C,P)}}else if(W!==void 0){const ut=W[tt];if(ut!==void 0)switch(ut.length){case 2:i.vertexAttrib2fv(Q.location,ut);break;case 3:i.vertexAttrib3fv(Q.location,ut);break;case 4:i.vertexAttrib4fv(Q.location,ut);break;default:i.vertexAttrib1fv(Q.location,ut)}}}}E()}function b(){T();for(const D in n){const B=n[D];for(const $ in B){const J=B[$];for(const z in J){const H=J[z];for(const W in H)h(H[W].object),delete H[W];delete J[z]}}delete n[D]}}function M(D){if(n[D.id]===void 0)return;const B=n[D.id];for(const $ in B){const J=B[$];for(const z in J){const H=J[z];for(const W in H)h(H[W].object),delete H[W];delete J[z]}}delete n[D.id]}function A(D){for(const B in n){const $=n[B];for(const J in $){const z=$[J];if(z[D.id]===void 0)continue;const H=z[D.id];for(const W in H)h(H[W].object),delete H[W];delete z[D.id]}}}function x(D){for(const B in n){const $=n[B],J=D.isInstancedMesh===!0?D.id:0,z=$[J];if(z!==void 0){for(const H in z){const W=z[H];for(const tt in W)h(W[tt].object),delete W[tt];delete z[H]}delete $[J],Object.keys($).length===0&&delete n[B]}}}function T(){N(),a=!0,r!==s&&(r=s,l(r.object))}function N(){s.geometry=null,s.program=null,s.wireframe=!1}return{setup:o,reset:T,resetDefaultState:N,dispose:b,releaseStatesOfGeometry:M,releaseStatesOfObject:x,releaseStatesOfProgram:A,initAttributes:v,enableAttribute:m,disableUnusedAttributes:E}}function Kp(i,t,e){let n;function s(c){n=c}function r(c,l){i.drawArrays(n,c,l),e.update(l,n,1)}function a(c,l,h){h!==0&&(i.drawArraysInstanced(n,c,l,h),e.update(l,n,h))}function o(c,l,h){if(h===0)return;t.get("WEBGL_multi_draw").multiDrawArraysWEBGL(n,c,0,l,0,h);let u=0;for(let p=0;p<h;p++)u+=l[p];e.update(u,n,1)}this.setMode=s,this.render=r,this.renderInstances=a,this.renderMultiDraw=o}function Zp(i,t,e,n){let s;function r(){if(s!==void 0)return s;if(t.has("EXT_texture_filter_anisotropic")===!0){const A=t.get("EXT_texture_filter_anisotropic");s=i.getParameter(A.MAX_TEXTURE_MAX_ANISOTROPY_EXT)}else s=0;return s}function a(A){return!(A!==hn&&n.convert(A)!==i.getParameter(i.IMPLEMENTATION_COLOR_READ_FORMAT))}function o(A){const x=A===Un&&(t.has("EXT_color_buffer_half_float")||t.has("EXT_color_buffer_float"));return!(A!==qe&&n.convert(A)!==i.getParameter(i.IMPLEMENTATION_COLOR_READ_TYPE)&&A!==cn&&!x)}function c(A){if(A==="highp"){if(i.getShaderPrecisionFormat(i.VERTEX_SHADER,i.HIGH_FLOAT).precision>0&&i.getShaderPrecisionFormat(i.FRAGMENT_SHADER,i.HIGH_FLOAT).precision>0)return"highp";A="mediump"}return A==="mediump"&&i.getShaderPrecisionFormat(i.VERTEX_SHADER,i.MEDIUM_FLOAT).precision>0&&i.getShaderPrecisionFormat(i.FRAGMENT_SHADER,i.MEDIUM_FLOAT).precision>0?"mediump":"lowp"}let l=e.precision!==void 0?e.precision:"highp";const h=c(l);h!==l&&(Ut("WebGLRenderer:",l,"not supported, using",h,"instead."),l=h);const d=e.logarithmicDepthBuffer===!0,u=e.reversedDepthBuffer===!0&&t.has("EXT_clip_control");e.reversedDepthBuffer===!0&&u===!1&&Ut("WebGLRenderer: Unable to use reversed depth buffer due to missing EXT_clip_control extension. Fallback to default depth buffer.");const p=i.getParameter(i.MAX_TEXTURE_IMAGE_UNITS),g=i.getParameter(i.MAX_VERTEX_TEXTURE_IMAGE_UNITS),v=i.getParameter(i.MAX_TEXTURE_SIZE),m=i.getParameter(i.MAX_CUBE_MAP_TEXTURE_SIZE),f=i.getParameter(i.MAX_VERTEX_ATTRIBS),E=i.getParameter(i.MAX_VERTEX_UNIFORM_VECTORS),w=i.getParameter(i.MAX_VARYING_VECTORS),y=i.getParameter(i.MAX_FRAGMENT_UNIFORM_VECTORS),b=i.getParameter(i.MAX_SAMPLES),M=i.getParameter(i.SAMPLES);return{isWebGL2:!0,getMaxAnisotropy:r,getMaxPrecision:c,textureFormatReadable:a,textureTypeReadable:o,precision:l,logarithmicDepthBuffer:d,reversedDepthBuffer:u,maxTextures:p,maxVertexTextures:g,maxTextureSize:v,maxCubemapSize:m,maxAttributes:f,maxVertexUniforms:E,maxVaryings:w,maxFragmentUniforms:y,maxSamples:b,samples:M}}function Jp(i){const t=this;let e=null,n=0,s=!1,r=!1;const a=new Qn,o=new Ht,c={value:null,needsUpdate:!1};this.uniform=c,this.numPlanes=0,this.numIntersection=0,this.init=function(d,u){const p=d.length!==0||u||n!==0||s;return s=u,n=d.length,p},this.beginShadows=function(){r=!0,h(null)},this.endShadows=function(){r=!1},this.setGlobalState=function(d,u){e=h(d,u,0)},this.setState=function(d,u,p){const g=d.clippingPlanes,v=d.clipIntersection,m=d.clipShadows,f=i.get(d);if(!s||g===null||g.length===0||r&&!m)r?h(null):l();else{const E=r?0:n,w=E*4;let y=f.clippingState||null;c.value=y,y=h(g,u,w,p);for(let b=0;b!==w;++b)y[b]=e[b];f.clippingState=y,this.numIntersection=v?this.numPlanes:0,this.numPlanes+=E}};function l(){c.value!==e&&(c.value=e,c.needsUpdate=n>0),t.numPlanes=n,t.numIntersection=0}function h(d,u,p,g){const v=d!==null?d.length:0;let m=null;if(v!==0){if(m=c.value,g!==!0||m===null){const f=p+v*4,E=u.matrixWorldInverse;o.getNormalMatrix(E),(m===null||m.length<f)&&(m=new Float32Array(f));for(let w=0,y=p;w!==v;++w,y+=4)a.copy(d[w]).applyMatrix4(E,o),a.normal.toArray(m,y),m[y+3]=a.constant}c.value=m,c.needsUpdate=!0}return t.numPlanes=v,t.numIntersection=0,m}}const ei=4,Kl=[.125,.215,.35,.446,.526,.582],fi=20,jp=256,rs=new Bo,Zl=new Ft;let da=null,fa=0,pa=0,ma=!1;const Qp=new I;class Jl{constructor(t){this._renderer=t,this._pingPongRenderTarget=null,this._lodMax=0,this._cubeSize=0,this._sizeLods=[],this._sigmas=[],this._lodMeshes=[],this._backgroundBox=null,this._cubemapMaterial=null,this._equirectMaterial=null,this._blurMaterial=null,this._ggxMaterial=null}fromScene(t,e=0,n=.1,s=100,r={}){const{size:a=256,position:o=Qp}=r;da=this._renderer.getRenderTarget(),fa=this._renderer.getActiveCubeFace(),pa=this._renderer.getActiveMipmapLevel(),ma=this._renderer.xr.enabled,this._renderer.xr.enabled=!1,this._setSize(a);const c=this._allocateTargets();return c.depthBuffer=!0,this._sceneToCubeUV(t,n,s,c,o),e>0&&this._blur(c,0,0,e),this._applyPMREM(c),this._cleanup(c),c}fromEquirectangular(t,e=null){return this._fromTexture(t,e)}fromCubemap(t,e=null){return this._fromTexture(t,e)}compileCubemapShader(){this._cubemapMaterial===null&&(this._cubemapMaterial=tc(),this._compileMaterial(this._cubemapMaterial))}compileEquirectangularShader(){this._equirectMaterial===null&&(this._equirectMaterial=Ql(),this._compileMaterial(this._equirectMaterial))}dispose(){this._dispose(),this._cubemapMaterial!==null&&this._cubemapMaterial.dispose(),this._equirectMaterial!==null&&this._equirectMaterial.dispose(),this._backgroundBox!==null&&(this._backgroundBox.geometry.dispose(),this._backgroundBox.material.dispose())}_setSize(t){this._lodMax=Math.floor(Math.log2(t)),this._cubeSize=Math.pow(2,this._lodMax)}_dispose(){this._blurMaterial!==null&&this._blurMaterial.dispose(),this._ggxMaterial!==null&&this._ggxMaterial.dispose(),this._pingPongRenderTarget!==null&&this._pingPongRenderTarget.dispose();for(let t=0;t<this._lodMeshes.length;t++)this._lodMeshes[t].geometry.dispose()}_cleanup(t){this._renderer.setRenderTarget(da,fa,pa),this._renderer.xr.enabled=ma,t.scissorTest=!1,Oi(t,0,0,t.width,t.height)}_fromTexture(t,e){t.mapping===_i||t.mapping===qi?this._setSize(t.image.length===0?16:t.image[0].width||t.image[0].image.width):this._setSize(t.image.width/4),da=this._renderer.getRenderTarget(),fa=this._renderer.getActiveCubeFace(),pa=this._renderer.getActiveMipmapLevel(),ma=this._renderer.xr.enabled,this._renderer.xr.enabled=!1;const n=e||this._allocateTargets();return this._textureToCubeUV(t,n),this._applyPMREM(n),this._cleanup(n),n}_allocateTargets(){const t=3*Math.max(this._cubeSize,112),e=4*this._cubeSize,n={magFilter:ye,minFilter:ye,generateMipmaps:!1,type:Un,format:hn,colorSpace:yr,depthBuffer:!1},s=jl(t,e,n);if(this._pingPongRenderTarget===null||this._pingPongRenderTarget.width!==t||this._pingPongRenderTarget.height!==e){this._pingPongRenderTarget!==null&&this._dispose(),this._pingPongRenderTarget=jl(t,e,n);const{_lodMax:r}=this;({lodMeshes:this._lodMeshes,sizeLods:this._sizeLods,sigmas:this._sigmas}=tm(r)),this._blurMaterial=nm(r,t,e),this._ggxMaterial=em(r,t,e)}return s}_compileMaterial(t){const e=new Me(new _e,t);this._renderer.compile(e,rs)}_sceneToCubeUV(t,e,n,s,r){const c=new We(90,1,e,n),l=[1,-1,1,1,1,1],h=[1,1,1,-1,-1,-1],d=this._renderer,u=d.autoClear,p=d.toneMapping;d.getClearColor(Zl),d.toneMapping=Mn,d.autoClear=!1,d.state.buffers.depth.getReversed()&&(d.setRenderTarget(s),d.clearDepth(),d.setRenderTarget(null)),this._backgroundBox===null&&(this._backgroundBox=new Me(new zn,new Zc({name:"PMREM.Background",side:ke,depthWrite:!1,depthTest:!1})));const v=this._backgroundBox,m=v.material;let f=!1;const E=t.background;E?E.isColor&&(m.color.copy(E),t.background=null,f=!0):(m.color.copy(Zl),f=!0);for(let w=0;w<6;w++){const y=w%3;y===0?(c.up.set(0,l[w],0),c.position.set(r.x,r.y,r.z),c.lookAt(r.x+h[w],r.y,r.z)):y===1?(c.up.set(0,0,l[w]),c.position.set(r.x,r.y,r.z),c.lookAt(r.x,r.y+h[w],r.z)):(c.up.set(0,l[w],0),c.position.set(r.x,r.y,r.z),c.lookAt(r.x,r.y,r.z+h[w]));const b=this._cubeSize;Oi(s,y*b,w>2?b:0,b,b),d.setRenderTarget(s),f&&d.render(v,c),d.render(t,c)}d.toneMapping=p,d.autoClear=u,t.background=E}_textureToCubeUV(t,e){const n=this._renderer,s=t.mapping===_i||t.mapping===qi;s?(this._cubemapMaterial===null&&(this._cubemapMaterial=tc()),this._cubemapMaterial.uniforms.flipEnvMap.value=t.isRenderTargetTexture===!1?-1:1):this._equirectMaterial===null&&(this._equirectMaterial=Ql());const r=s?this._cubemapMaterial:this._equirectMaterial,a=this._lodMeshes[0];a.material=r;const o=r.uniforms;o.envMap.value=t;const c=this._cubeSize;Oi(e,0,0,3*c,2*c),n.setRenderTarget(e),n.render(a,rs)}_applyPMREM(t){const e=this._renderer,n=e.autoClear;e.autoClear=!1;const s=this._lodMeshes.length;for(let r=1;r<s;r++)this._applyGGXFilter(t,r-1,r);e.autoClear=n}_applyGGXFilter(t,e,n){const s=this._renderer,r=this._pingPongRenderTarget,a=this._ggxMaterial,o=this._lodMeshes[n];o.material=a;const c=a.uniforms,l=n/(this._lodMeshes.length-1),h=e/(this._lodMeshes.length-1),d=Math.sqrt(l*l-h*h),u=0+l*1.25,p=d*u,{_lodMax:g}=this,v=this._sizeLods[n],m=3*v*(n>g-ei?n-g+ei:0),f=4*(this._cubeSize-v);c.envMap.value=t.texture,c.roughness.value=p,c.mipInt.value=g-e,Oi(r,m,f,3*v,2*v),s.setRenderTarget(r),s.render(o,rs),c.envMap.value=r.texture,c.roughness.value=0,c.mipInt.value=g-n,Oi(t,m,f,3*v,2*v),s.setRenderTarget(t),s.render(o,rs)}_blur(t,e,n,s,r){const a=this._pingPongRenderTarget;this._halfBlur(t,a,e,n,s,"latitudinal",r),this._halfBlur(a,t,n,n,s,"longitudinal",r)}_halfBlur(t,e,n,s,r,a,o){const c=this._renderer,l=this._blurMaterial;a!=="latitudinal"&&a!=="longitudinal"&&ee("blur direction must be either latitudinal or longitudinal!");const h=3,d=this._lodMeshes[s];d.material=l;const u=l.uniforms,p=this._sizeLods[n]-1,g=isFinite(r)?Math.PI/(2*p):2*Math.PI/(2*fi-1),v=r/g,m=isFinite(r)?1+Math.floor(h*v):fi;m>fi&&Ut(`sigmaRadians, ${r}, is too large and will clip, as it requested ${m} samples when the maximum is set to ${fi}`);const f=[];let E=0;for(let A=0;A<fi;++A){const x=A/v,T=Math.exp(-x*x/2);f.push(T),A===0?E+=T:A<m&&(E+=2*T)}for(let A=0;A<f.length;A++)f[A]=f[A]/E;u.envMap.value=t.texture,u.samples.value=m,u.weights.value=f,u.latitudinal.value=a==="latitudinal",o&&(u.poleAxis.value=o);const{_lodMax:w}=this;u.dTheta.value=g,u.mipInt.value=w-n;const y=this._sizeLods[s],b=3*y*(s>w-ei?s-w+ei:0),M=4*(this._cubeSize-y);Oi(e,b,M,3*y,2*y),c.setRenderTarget(e),c.render(d,rs)}}function tm(i){const t=[],e=[],n=[];let s=i;const r=i-ei+1+Kl.length;for(let a=0;a<r;a++){const o=Math.pow(2,s);t.push(o);let c=1/o;a>i-ei?c=Kl[a-i+ei-1]:a===0&&(c=0),e.push(c);const l=1/(o-2),h=-l,d=1+l,u=[h,h,d,h,d,d,h,h,d,d,h,d],p=6,g=6,v=3,m=2,f=1,E=new Float32Array(v*g*p),w=new Float32Array(m*g*p),y=new Float32Array(f*g*p);for(let M=0;M<p;M++){const A=M%3*2/3-1,x=M>2?0:-1,T=[A,x,0,A+2/3,x,0,A+2/3,x+1,0,A,x,0,A+2/3,x+1,0,A,x+1,0];E.set(T,v*g*M),w.set(u,m*g*M);const N=[M,M,M,M,M,M];y.set(N,f*g*M)}const b=new _e;b.setAttribute("position",new Pe(E,v)),b.setAttribute("uv",new Pe(w,m)),b.setAttribute("faceIndex",new Pe(y,f)),n.push(new Me(b,null)),s>ei&&s--}return{lodMeshes:n,sizeLods:t,sigmas:e}}function jl(i,t,e){const n=new Sn(i,t,e);return n.texture.mapping=Dr,n.texture.name="PMREM.cubeUv",n.scissorTest=!0,n}function Oi(i,t,e,n,s){i.viewport.set(t,e,n,s),i.scissor.set(t,e,n,s)}function em(i,t,e){return new En({name:"PMREMGGXConvolution",defines:{GGX_SAMPLES:jp,CUBEUV_TEXEL_WIDTH:1/t,CUBEUV_TEXEL_HEIGHT:1/e,CUBEUV_MAX_MIP:`${i}.0`},uniforms:{envMap:{value:null},roughness:{value:0},mipInt:{value:0}},vertexShader:Lr(),fragmentShader:`

			precision highp float;
			precision highp int;

			varying vec3 vOutputDirection;

			uniform sampler2D envMap;
			uniform float roughness;
			uniform float mipInt;

			#define ENVMAP_TYPE_CUBE_UV
			#include <cube_uv_reflection_fragment>

			#define PI 3.14159265359

			// Van der Corput radical inverse
			float radicalInverse_VdC(uint bits) {
				bits = (bits << 16u) | (bits >> 16u);
				bits = ((bits & 0x55555555u) << 1u) | ((bits & 0xAAAAAAAAu) >> 1u);
				bits = ((bits & 0x33333333u) << 2u) | ((bits & 0xCCCCCCCCu) >> 2u);
				bits = ((bits & 0x0F0F0F0Fu) << 4u) | ((bits & 0xF0F0F0F0u) >> 4u);
				bits = ((bits & 0x00FF00FFu) << 8u) | ((bits & 0xFF00FF00u) >> 8u);
				return float(bits) * 2.3283064365386963e-10; // / 0x100000000
			}

			// Hammersley sequence
			vec2 hammersley(uint i, uint N) {
				return vec2(float(i) / float(N), radicalInverse_VdC(i));
			}

			// GGX VNDF importance sampling (Eric Heitz 2018)
			// "Sampling the GGX Distribution of Visible Normals"
			// https://jcgt.org/published/0007/04/01/
			vec3 importanceSampleGGX_VNDF(vec2 Xi, vec3 V, float roughness) {
				float alpha = roughness * roughness;

				// Section 4.1: Orthonormal basis
				vec3 T1 = vec3(1.0, 0.0, 0.0);
				vec3 T2 = cross(V, T1);

				// Section 4.2: Parameterization of projected area
				float r = sqrt(Xi.x);
				float phi = 2.0 * PI * Xi.y;
				float t1 = r * cos(phi);
				float t2 = r * sin(phi);
				float s = 0.5 * (1.0 + V.z);
				t2 = (1.0 - s) * sqrt(1.0 - t1 * t1) + s * t2;

				// Section 4.3: Reprojection onto hemisphere
				vec3 Nh = t1 * T1 + t2 * T2 + sqrt(max(0.0, 1.0 - t1 * t1 - t2 * t2)) * V;

				// Section 3.4: Transform back to ellipsoid configuration
				return normalize(vec3(alpha * Nh.x, alpha * Nh.y, max(0.0, Nh.z)));
			}

			void main() {
				vec3 N = normalize(vOutputDirection);
				vec3 V = N; // Assume view direction equals normal for pre-filtering

				vec3 prefilteredColor = vec3(0.0);
				float totalWeight = 0.0;

				// For very low roughness, just sample the environment directly
				if (roughness < 0.001) {
					gl_FragColor = vec4(bilinearCubeUV(envMap, N, mipInt), 1.0);
					return;
				}

				// Tangent space basis for VNDF sampling
				vec3 up = abs(N.z) < 0.999 ? vec3(0.0, 0.0, 1.0) : vec3(1.0, 0.0, 0.0);
				vec3 tangent = normalize(cross(up, N));
				vec3 bitangent = cross(N, tangent);

				for(uint i = 0u; i < uint(GGX_SAMPLES); i++) {
					vec2 Xi = hammersley(i, uint(GGX_SAMPLES));

					// For PMREM, V = N, so in tangent space V is always (0, 0, 1)
					vec3 H_tangent = importanceSampleGGX_VNDF(Xi, vec3(0.0, 0.0, 1.0), roughness);

					// Transform H back to world space
					vec3 H = normalize(tangent * H_tangent.x + bitangent * H_tangent.y + N * H_tangent.z);
					vec3 L = normalize(2.0 * dot(V, H) * H - V);

					float NdotL = max(dot(N, L), 0.0);

					if(NdotL > 0.0) {
						// Sample environment at fixed mip level
						// VNDF importance sampling handles the distribution filtering
						vec3 sampleColor = bilinearCubeUV(envMap, L, mipInt);

						// Weight by NdotL for the split-sum approximation
						// VNDF PDF naturally accounts for the visible microfacet distribution
						prefilteredColor += sampleColor * NdotL;
						totalWeight += NdotL;
					}
				}

				if (totalWeight > 0.0) {
					prefilteredColor = prefilteredColor / totalWeight;
				}

				gl_FragColor = vec4(prefilteredColor, 1.0);
			}
		`,blending:In,depthTest:!1,depthWrite:!1})}function nm(i,t,e){const n=new Float32Array(fi),s=new I(0,1,0);return new En({name:"SphericalGaussianBlur",defines:{n:fi,CUBEUV_TEXEL_WIDTH:1/t,CUBEUV_TEXEL_HEIGHT:1/e,CUBEUV_MAX_MIP:`${i}.0`},uniforms:{envMap:{value:null},samples:{value:1},weights:{value:n},latitudinal:{value:!1},dTheta:{value:0},mipInt:{value:0},poleAxis:{value:s}},vertexShader:Lr(),fragmentShader:`

			precision mediump float;
			precision mediump int;

			varying vec3 vOutputDirection;

			uniform sampler2D envMap;
			uniform int samples;
			uniform float weights[ n ];
			uniform bool latitudinal;
			uniform float dTheta;
			uniform float mipInt;
			uniform vec3 poleAxis;

			#define ENVMAP_TYPE_CUBE_UV
			#include <cube_uv_reflection_fragment>

			vec3 getSample( float theta, vec3 axis ) {

				float cosTheta = cos( theta );
				// Rodrigues' axis-angle rotation
				vec3 sampleDirection = vOutputDirection * cosTheta
					+ cross( axis, vOutputDirection ) * sin( theta )
					+ axis * dot( axis, vOutputDirection ) * ( 1.0 - cosTheta );

				return bilinearCubeUV( envMap, sampleDirection, mipInt );

			}

			void main() {

				vec3 axis = latitudinal ? poleAxis : cross( poleAxis, vOutputDirection );

				if ( all( equal( axis, vec3( 0.0 ) ) ) ) {

					axis = vec3( vOutputDirection.z, 0.0, - vOutputDirection.x );

				}

				axis = normalize( axis );

				gl_FragColor = vec4( 0.0, 0.0, 0.0, 1.0 );
				gl_FragColor.rgb += weights[ 0 ] * getSample( 0.0, axis );

				for ( int i = 1; i < n; i++ ) {

					if ( i >= samples ) {

						break;

					}

					float theta = dTheta * float( i );
					gl_FragColor.rgb += weights[ i ] * getSample( -1.0 * theta, axis );
					gl_FragColor.rgb += weights[ i ] * getSample( theta, axis );

				}

			}
		`,blending:In,depthTest:!1,depthWrite:!1})}function Ql(){return new En({name:"EquirectangularToCubeUV",uniforms:{envMap:{value:null}},vertexShader:Lr(),fragmentShader:`

			precision mediump float;
			precision mediump int;

			varying vec3 vOutputDirection;

			uniform sampler2D envMap;

			#include <common>

			void main() {

				vec3 outputDirection = normalize( vOutputDirection );
				vec2 uv = equirectUv( outputDirection );

				gl_FragColor = vec4( texture2D ( envMap, uv ).rgb, 1.0 );

			}
		`,blending:In,depthTest:!1,depthWrite:!1})}function tc(){return new En({name:"CubemapToCubeUV",uniforms:{envMap:{value:null},flipEnvMap:{value:-1}},vertexShader:Lr(),fragmentShader:`

			precision mediump float;
			precision mediump int;

			uniform float flipEnvMap;

			varying vec3 vOutputDirection;

			uniform samplerCube envMap;

			void main() {

				gl_FragColor = textureCube( envMap, vec3( flipEnvMap * vOutputDirection.x, vOutputDirection.yz ) );

			}
		`,blending:In,depthTest:!1,depthWrite:!1})}function Lr(){return`

		precision mediump float;
		precision mediump int;

		attribute float faceIndex;

		varying vec3 vOutputDirection;

		// RH coordinate system; PMREM face-indexing convention
		vec3 getDirection( vec2 uv, float face ) {

			uv = 2.0 * uv - 1.0;

			vec3 direction = vec3( uv, 1.0 );

			if ( face == 0.0 ) {

				direction = direction.zyx; // ( 1, v, u ) pos x

			} else if ( face == 1.0 ) {

				direction = direction.xzy;
				direction.xz *= -1.0; // ( -u, 1, -v ) pos y

			} else if ( face == 2.0 ) {

				direction.x *= -1.0; // ( -u, v, 1 ) pos z

			} else if ( face == 3.0 ) {

				direction = direction.zyx;
				direction.xz *= -1.0; // ( -1, v, -u ) neg x

			} else if ( face == 4.0 ) {

				direction = direction.xzy;
				direction.xy *= -1.0; // ( -u, -1, v ) neg y

			} else if ( face == 5.0 ) {

				direction.z *= -1.0; // ( u, v, -1 ) neg z

			}

			return direction;

		}

		void main() {

			vOutputDirection = getDirection( uv, faceIndex );
			gl_Position = vec4( position, 1.0 );

		}
	`}class oh extends Sn{constructor(t=1,e={}){super(t,t,e),this.isWebGLCubeRenderTarget=!0;const n={width:t,height:t,depth:1},s=[n,n,n,n,n,n];this.texture=new jc(s),this._setTextureOptions(e),this.texture.isRenderTargetTexture=!0}fromEquirectangularTexture(t,e){this.texture.type=e.type,this.texture.colorSpace=e.colorSpace,this.texture.generateMipmaps=e.generateMipmaps,this.texture.minFilter=e.minFilter,this.texture.magFilter=e.magFilter;const n={uniforms:{tEquirect:{value:null}},vertexShader:`

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
			`},s=new zn(5,5,5),r=new En({name:"CubemapFromEquirect",uniforms:$i(n.uniforms),vertexShader:n.vertexShader,fragmentShader:n.fragmentShader,side:ke,blending:In});r.uniforms.tEquirect.value=e;const a=new Me(s,r),o=e.minFilter;return e.minFilter===Ln&&(e.minFilter=ye),new rd(1,10,this).update(t,a),e.minFilter=o,a.geometry.dispose(),a.material.dispose(),this}clear(t,e=!0,n=!0,s=!0){const r=t.getRenderTarget();for(let a=0;a<6;a++)t.setRenderTarget(this,a),t.clear(e,n,s);t.setRenderTarget(r)}}function im(i){let t=new WeakMap,e=new WeakMap,n=null;function s(u,p=!1){return u==null?null:p?a(u):r(u)}function r(u){if(u&&u.isTexture){const p=u.mapping;if(p===Fr||p===Or)if(t.has(u)){const g=t.get(u).texture;return o(g,u.mapping)}else{const g=u.image;if(g&&g.height>0){const v=new oh(g.height);return v.fromEquirectangularTexture(i,u),t.set(u,v),u.addEventListener("dispose",l),o(v.texture,u.mapping)}else return null}}return u}function a(u){if(u&&u.isTexture){const p=u.mapping,g=p===Fr||p===Or,v=p===_i||p===qi;if(g||v){let m=e.get(u);const f=m!==void 0?m.texture.pmremVersion:0;if(u.isRenderTargetTexture&&u.pmremVersion!==f)return n===null&&(n=new Jl(i)),m=g?n.fromEquirectangular(u,m):n.fromCubemap(u,m),m.texture.pmremVersion=u.pmremVersion,e.set(u,m),m.texture;if(m!==void 0)return m.texture;{const E=u.image;return g&&E&&E.height>0||v&&E&&c(E)?(n===null&&(n=new Jl(i)),m=g?n.fromEquirectangular(u):n.fromCubemap(u),m.texture.pmremVersion=u.pmremVersion,e.set(u,m),u.addEventListener("dispose",h),m.texture):null}}}return u}function o(u,p){return p===Fr?u.mapping=_i:p===Or&&(u.mapping=qi),u}function c(u){let p=0;const g=6;for(let v=0;v<g;v++)u[v]!==void 0&&p++;return p===g}function l(u){const p=u.target;p.removeEventListener("dispose",l);const g=t.get(p);g!==void 0&&(t.delete(p),g.dispose())}function h(u){const p=u.target;p.removeEventListener("dispose",h);const g=e.get(p);g!==void 0&&(e.delete(p),g.dispose())}function d(){t=new WeakMap,e=new WeakMap,n!==null&&(n.dispose(),n=null)}return{get:s,dispose:d}}function sm(i){const t={};function e(n){if(t[n]!==void 0)return t[n];const s=i.getExtension(n);return t[n]=s,s}return{has:function(n){return e(n)!==null},init:function(){e("EXT_color_buffer_float"),e("WEBGL_clip_cull_distance"),e("OES_texture_float_linear"),e("EXT_color_buffer_half_float"),e("WEBGL_multisampled_render_to_texture"),e("WEBGL_render_shared_exponent")},get:function(n){const s=e(n);return s===null&&Hi("WebGLRenderer: "+n+" extension not supported."),s}}}function rm(i,t,e,n){const s={},r=new WeakMap;function a(d){const u=d.target;u.index!==null&&t.remove(u.index);for(const g in u.attributes)t.remove(u.attributes[g]);u.removeEventListener("dispose",a),delete s[u.id];const p=r.get(u);p&&(t.remove(p),r.delete(u)),n.releaseStatesOfGeometry(u),u.isInstancedBufferGeometry===!0&&delete u._maxInstanceCount,e.memory.geometries--}function o(d,u){return s[u.id]===!0||(u.addEventListener("dispose",a),s[u.id]=!0,e.memory.geometries++),u}function c(d){const u=d.attributes;for(const p in u)t.update(u[p],i.ARRAY_BUFFER)}function l(d){const u=[],p=d.index,g=d.attributes.position;let v=0;if(g===void 0)return;if(p!==null){const E=p.array;v=p.version;for(let w=0,y=E.length;w<y;w+=3){const b=E[w+0],M=E[w+1],A=E[w+2];u.push(b,M,M,A,A,b)}}else{const E=g.array;v=g.version;for(let w=0,y=E.length/3-1;w<y;w+=3){const b=w+0,M=w+1,A=w+2;u.push(b,M,M,A,A,b)}}const m=new(g.count>=65535?Kc:$c)(u,1);m.version=v;const f=r.get(d);f&&t.remove(f),r.set(d,m)}function h(d){const u=r.get(d);if(u){const p=d.index;p!==null&&u.version<p.version&&l(d)}else l(d);return r.get(d)}return{get:o,update:c,getWireframeAttribute:h}}function am(i,t,e){let n;function s(d){n=d}let r,a;function o(d){r=d.type,a=d.bytesPerElement}function c(d,u){i.drawElements(n,u,r,d*a),e.update(u,n,1)}function l(d,u,p){p!==0&&(i.drawElementsInstanced(n,u,r,d*a,p),e.update(u,n,p))}function h(d,u,p){if(p===0)return;t.get("WEBGL_multi_draw").multiDrawElementsWEBGL(n,u,0,r,d,0,p);let v=0;for(let m=0;m<p;m++)v+=u[m];e.update(v,n,1)}this.setMode=s,this.setIndex=o,this.render=c,this.renderInstances=l,this.renderMultiDraw=h}function om(i){const t={geometries:0,textures:0},e={frame:0,calls:0,triangles:0,points:0,lines:0};function n(r,a,o){switch(e.calls++,a){case i.TRIANGLES:e.triangles+=o*(r/3);break;case i.LINES:e.lines+=o*(r/2);break;case i.LINE_STRIP:e.lines+=o*(r-1);break;case i.LINE_LOOP:e.lines+=o*r;break;case i.POINTS:e.points+=o*r;break;default:ee("WebGLInfo: Unknown draw mode:",a);break}}function s(){e.calls=0,e.triangles=0,e.points=0,e.lines=0}return{memory:t,render:e,programs:null,autoReset:!0,reset:s,update:n}}function lm(i,t,e){const n=new WeakMap,s=new pe;function r(a,o,c){const l=a.morphTargetInfluences,h=o.morphAttributes.position||o.morphAttributes.normal||o.morphAttributes.color,d=h!==void 0?h.length:0;let u=n.get(o);if(u===void 0||u.count!==d){let T=function(){A.dispose(),n.delete(o),o.removeEventListener("dispose",T)};u!==void 0&&u.texture.dispose();const p=o.morphAttributes.position!==void 0,g=o.morphAttributes.normal!==void 0,v=o.morphAttributes.color!==void 0,m=o.morphAttributes.position||[],f=o.morphAttributes.normal||[],E=o.morphAttributes.color||[];let w=0;p===!0&&(w=1),g===!0&&(w=2),v===!0&&(w=3);let y=o.attributes.position.count*w,b=1;y>t.maxTextureSize&&(b=Math.ceil(y/t.maxTextureSize),y=t.maxTextureSize);const M=new Float32Array(y*b*4*d),A=new Xc(M,y,b,d);A.type=cn,A.needsUpdate=!0;const x=w*4;for(let N=0;N<d;N++){const D=m[N],B=f[N],$=E[N],J=y*b*4*N;for(let z=0;z<D.count;z++){const H=z*x;p===!0&&(s.fromBufferAttribute(D,z),M[J+H+0]=s.x,M[J+H+1]=s.y,M[J+H+2]=s.z,M[J+H+3]=0),g===!0&&(s.fromBufferAttribute(B,z),M[J+H+4]=s.x,M[J+H+5]=s.y,M[J+H+6]=s.z,M[J+H+7]=0),v===!0&&(s.fromBufferAttribute($,z),M[J+H+8]=s.x,M[J+H+9]=s.y,M[J+H+10]=s.z,M[J+H+11]=$.itemSize===4?s.w:1)}}u={count:d,texture:A,size:new zt(y,b)},n.set(o,u),o.addEventListener("dispose",T)}if(a.isInstancedMesh===!0&&a.morphTexture!==null)c.getUniforms().setValue(i,"morphTexture",a.morphTexture,e);else{let p=0;for(let v=0;v<l.length;v++)p+=l[v];const g=o.morphTargetsRelative?1:1-p;c.getUniforms().setValue(i,"morphTargetBaseInfluence",g),c.getUniforms().setValue(i,"morphTargetInfluences",l)}c.getUniforms().setValue(i,"morphTargetsTexture",u.texture,e),c.getUniforms().setValue(i,"morphTargetsTextureSize",u.size)}return{update:r}}function cm(i,t,e,n,s){let r=new WeakMap;function a(l){const h=s.render.frame,d=l.geometry,u=t.get(l,d);if(r.get(u)!==h&&(t.update(u),r.set(u,h)),l.isInstancedMesh&&(l.hasEventListener("dispose",c)===!1&&l.addEventListener("dispose",c),r.get(l)!==h&&(e.update(l.instanceMatrix,i.ARRAY_BUFFER),l.instanceColor!==null&&e.update(l.instanceColor,i.ARRAY_BUFFER),r.set(l,h))),l.isSkinnedMesh){const p=l.skeleton;r.get(p)!==h&&(p.update(),r.set(p,h))}return u}function o(){r=new WeakMap}function c(l){const h=l.target;h.removeEventListener("dispose",c),n.releaseStatesOfObject(h),e.remove(h.instanceMatrix),h.instanceColor!==null&&e.remove(h.instanceColor)}return{update:a,dispose:o}}const hm={[Dc]:"LINEAR_TONE_MAPPING",[Lc]:"REINHARD_TONE_MAPPING",[Ic]:"CINEON_TONE_MAPPING",[Pr]:"ACES_FILMIC_TONE_MAPPING",[Uc]:"AGX_TONE_MAPPING",[Fc]:"NEUTRAL_TONE_MAPPING",[Nc]:"CUSTOM_TONE_MAPPING"};function um(i,t,e,n,s,r){const a=new Sn(t,e,{type:i,depthBuffer:s,stencilBuffer:r,samples:n?4:0,depthTexture:s?new Yi(t,e):void 0}),o=new Sn(t,e,{type:Un,depthBuffer:!1,stencilBuffer:!1}),c=new _e;c.setAttribute("position",new ae([-1,3,0,-1,-1,0,3,-1,0],3)),c.setAttribute("uv",new ae([0,2,0,0,2,0],2));const l=new Ku({uniforms:{tDiffuse:{value:null}},vertexShader:`
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
			}`,depthTest:!1,depthWrite:!1}),h=new Me(c,l),d=new Bo(-1,1,1,-1,0,1);let u=null,p=null,g=!1,v,m=null,f=[],E=!1;this.setSize=function(w,y){a.setSize(w,y),o.setSize(w,y);for(let b=0;b<f.length;b++){const M=f[b];M.setSize&&M.setSize(w,y)}},this.setEffects=function(w){f=w,E=f.length>0&&f[0].isRenderPass===!0;const y=a.width,b=a.height;for(let M=0;M<f.length;M++){const A=f[M];A.setSize&&A.setSize(y,b)}},this.begin=function(w,y){if(g||w.toneMapping===Mn&&f.length===0)return!1;if(m=y,y!==null){const b=y.width,M=y.height;(a.width!==b||a.height!==M)&&this.setSize(b,M)}return E===!1&&w.setRenderTarget(a),v=w.toneMapping,w.toneMapping=Mn,!0},this.hasRenderPass=function(){return E},this.end=function(w,y){w.toneMapping=v,g=!0;let b=a,M=o;for(let A=0;A<f.length;A++){const x=f[A];if(x.enabled!==!1&&(x.render(w,M,b,y),x.needsSwap!==!1)){const T=b;b=M,M=T}}if(u!==w.outputColorSpace||p!==w.toneMapping){u=w.outputColorSpace,p=w.toneMapping,l.defines={},Qt.getTransfer(u)===re&&(l.defines.SRGB_TRANSFER="");const A=hm[p];A&&(l.defines[A]=""),l.needsUpdate=!0}l.uniforms.tDiffuse.value=b.texture,w.setRenderTarget(m),w.render(h,d),m=null,g=!1},this.isCompositing=function(){return g},this.dispose=function(){a.depthTexture&&a.depthTexture.dispose(),a.dispose(),o.dispose(),c.dispose(),l.dispose()}}const lh=new Ne,vo=new Yi(1,1),ch=new Xc,hh=new Au,uh=new jc,ec=[],nc=[],ic=new Float32Array(16),sc=new Float32Array(9),rc=new Float32Array(4);function Zi(i,t,e){const n=i[0];if(n<=0||n>0)return i;const s=t*e;let r=ec[s];if(r===void 0&&(r=new Float32Array(s),ec[s]=r),t!==0){n.toArray(r,0);for(let a=1,o=0;a!==t;++a)o+=e,i[a].toArray(r,o)}return r}function Ae(i,t){if(i.length!==t.length)return!1;for(let e=0,n=i.length;e<n;e++)if(i[e]!==t[e])return!1;return!0}function we(i,t){for(let e=0,n=t.length;e<n;e++)i[e]=t[e]}function Ir(i,t){let e=nc[t];e===void 0&&(e=new Int32Array(t),nc[t]=e);for(let n=0;n!==t;++n)e[n]=i.allocateTextureUnit();return e}function dm(i,t){const e=this.cache;e[0]!==t&&(i.uniform1f(this.addr,t),e[0]=t)}function fm(i,t){const e=this.cache;if(t.x!==void 0)(e[0]!==t.x||e[1]!==t.y)&&(i.uniform2f(this.addr,t.x,t.y),e[0]=t.x,e[1]=t.y);else{if(Ae(e,t))return;i.uniform2fv(this.addr,t),we(e,t)}}function pm(i,t){const e=this.cache;if(t.x!==void 0)(e[0]!==t.x||e[1]!==t.y||e[2]!==t.z)&&(i.uniform3f(this.addr,t.x,t.y,t.z),e[0]=t.x,e[1]=t.y,e[2]=t.z);else if(t.r!==void 0)(e[0]!==t.r||e[1]!==t.g||e[2]!==t.b)&&(i.uniform3f(this.addr,t.r,t.g,t.b),e[0]=t.r,e[1]=t.g,e[2]=t.b);else{if(Ae(e,t))return;i.uniform3fv(this.addr,t),we(e,t)}}function mm(i,t){const e=this.cache;if(t.x!==void 0)(e[0]!==t.x||e[1]!==t.y||e[2]!==t.z||e[3]!==t.w)&&(i.uniform4f(this.addr,t.x,t.y,t.z,t.w),e[0]=t.x,e[1]=t.y,e[2]=t.z,e[3]=t.w);else{if(Ae(e,t))return;i.uniform4fv(this.addr,t),we(e,t)}}function gm(i,t){const e=this.cache,n=t.elements;if(n===void 0){if(Ae(e,t))return;i.uniformMatrix2fv(this.addr,!1,t),we(e,t)}else{if(Ae(e,n))return;rc.set(n),i.uniformMatrix2fv(this.addr,!1,rc),we(e,n)}}function _m(i,t){const e=this.cache,n=t.elements;if(n===void 0){if(Ae(e,t))return;i.uniformMatrix3fv(this.addr,!1,t),we(e,t)}else{if(Ae(e,n))return;sc.set(n),i.uniformMatrix3fv(this.addr,!1,sc),we(e,n)}}function xm(i,t){const e=this.cache,n=t.elements;if(n===void 0){if(Ae(e,t))return;i.uniformMatrix4fv(this.addr,!1,t),we(e,t)}else{if(Ae(e,n))return;ic.set(n),i.uniformMatrix4fv(this.addr,!1,ic),we(e,n)}}function vm(i,t){const e=this.cache;e[0]!==t&&(i.uniform1i(this.addr,t),e[0]=t)}function Mm(i,t){const e=this.cache;if(t.x!==void 0)(e[0]!==t.x||e[1]!==t.y)&&(i.uniform2i(this.addr,t.x,t.y),e[0]=t.x,e[1]=t.y);else{if(Ae(e,t))return;i.uniform2iv(this.addr,t),we(e,t)}}function Sm(i,t){const e=this.cache;if(t.x!==void 0)(e[0]!==t.x||e[1]!==t.y||e[2]!==t.z)&&(i.uniform3i(this.addr,t.x,t.y,t.z),e[0]=t.x,e[1]=t.y,e[2]=t.z);else{if(Ae(e,t))return;i.uniform3iv(this.addr,t),we(e,t)}}function ym(i,t){const e=this.cache;if(t.x!==void 0)(e[0]!==t.x||e[1]!==t.y||e[2]!==t.z||e[3]!==t.w)&&(i.uniform4i(this.addr,t.x,t.y,t.z,t.w),e[0]=t.x,e[1]=t.y,e[2]=t.z,e[3]=t.w);else{if(Ae(e,t))return;i.uniform4iv(this.addr,t),we(e,t)}}function bm(i,t){const e=this.cache;e[0]!==t&&(i.uniform1ui(this.addr,t),e[0]=t)}function Em(i,t){const e=this.cache;if(t.x!==void 0)(e[0]!==t.x||e[1]!==t.y)&&(i.uniform2ui(this.addr,t.x,t.y),e[0]=t.x,e[1]=t.y);else{if(Ae(e,t))return;i.uniform2uiv(this.addr,t),we(e,t)}}function Tm(i,t){const e=this.cache;if(t.x!==void 0)(e[0]!==t.x||e[1]!==t.y||e[2]!==t.z)&&(i.uniform3ui(this.addr,t.x,t.y,t.z),e[0]=t.x,e[1]=t.y,e[2]=t.z);else{if(Ae(e,t))return;i.uniform3uiv(this.addr,t),we(e,t)}}function Am(i,t){const e=this.cache;if(t.x!==void 0)(e[0]!==t.x||e[1]!==t.y||e[2]!==t.z||e[3]!==t.w)&&(i.uniform4ui(this.addr,t.x,t.y,t.z,t.w),e[0]=t.x,e[1]=t.y,e[2]=t.z,e[3]=t.w);else{if(Ae(e,t))return;i.uniform4uiv(this.addr,t),we(e,t)}}function wm(i,t,e){const n=this.cache,s=e.allocateTextureUnit();n[0]!==s&&(i.uniform1i(this.addr,s),n[0]=s);let r;this.type===i.SAMPLER_2D_SHADOW?(vo.compareFunction=e.isReversedDepthBuffer()?Lo:Do,r=vo):r=lh,e.setTexture2D(t||r,s)}function Rm(i,t,e){const n=this.cache,s=e.allocateTextureUnit();n[0]!==s&&(i.uniform1i(this.addr,s),n[0]=s),e.setTexture3D(t||hh,s)}function Cm(i,t,e){const n=this.cache,s=e.allocateTextureUnit();n[0]!==s&&(i.uniform1i(this.addr,s),n[0]=s),e.setTextureCube(t||uh,s)}function Pm(i,t,e){const n=this.cache,s=e.allocateTextureUnit();n[0]!==s&&(i.uniform1i(this.addr,s),n[0]=s),e.setTexture2DArray(t||ch,s)}function Dm(i){switch(i){case 5126:return dm;case 35664:return fm;case 35665:return pm;case 35666:return mm;case 35674:return gm;case 35675:return _m;case 35676:return xm;case 5124:case 35670:return vm;case 35667:case 35671:return Mm;case 35668:case 35672:return Sm;case 35669:case 35673:return ym;case 5125:return bm;case 36294:return Em;case 36295:return Tm;case 36296:return Am;case 35678:case 36198:case 36298:case 36306:case 35682:return wm;case 35679:case 36299:case 36307:return Rm;case 35680:case 36300:case 36308:case 36293:return Cm;case 36289:case 36303:case 36311:case 36292:return Pm}}function Lm(i,t){i.uniform1fv(this.addr,t)}function Im(i,t){const e=Zi(t,this.size,2);i.uniform2fv(this.addr,e)}function Nm(i,t){const e=Zi(t,this.size,3);i.uniform3fv(this.addr,e)}function Um(i,t){const e=Zi(t,this.size,4);i.uniform4fv(this.addr,e)}function Fm(i,t){const e=Zi(t,this.size,4);i.uniformMatrix2fv(this.addr,!1,e)}function Om(i,t){const e=Zi(t,this.size,9);i.uniformMatrix3fv(this.addr,!1,e)}function Bm(i,t){const e=Zi(t,this.size,16);i.uniformMatrix4fv(this.addr,!1,e)}function zm(i,t){i.uniform1iv(this.addr,t)}function km(i,t){i.uniform2iv(this.addr,t)}function Vm(i,t){i.uniform3iv(this.addr,t)}function Gm(i,t){i.uniform4iv(this.addr,t)}function Hm(i,t){i.uniform1uiv(this.addr,t)}function Wm(i,t){i.uniform2uiv(this.addr,t)}function Xm(i,t){i.uniform3uiv(this.addr,t)}function qm(i,t){i.uniform4uiv(this.addr,t)}function Ym(i,t,e){const n=this.cache,s=t.length,r=Ir(e,s);Ae(n,r)||(i.uniform1iv(this.addr,r),we(n,r));let a;this.type===i.SAMPLER_2D_SHADOW?a=vo:a=lh;for(let o=0;o!==s;++o)e.setTexture2D(t[o]||a,r[o])}function $m(i,t,e){const n=this.cache,s=t.length,r=Ir(e,s);Ae(n,r)||(i.uniform1iv(this.addr,r),we(n,r));for(let a=0;a!==s;++a)e.setTexture3D(t[a]||hh,r[a])}function Km(i,t,e){const n=this.cache,s=t.length,r=Ir(e,s);Ae(n,r)||(i.uniform1iv(this.addr,r),we(n,r));for(let a=0;a!==s;++a)e.setTextureCube(t[a]||uh,r[a])}function Zm(i,t,e){const n=this.cache,s=t.length,r=Ir(e,s);Ae(n,r)||(i.uniform1iv(this.addr,r),we(n,r));for(let a=0;a!==s;++a)e.setTexture2DArray(t[a]||ch,r[a])}function Jm(i){switch(i){case 5126:return Lm;case 35664:return Im;case 35665:return Nm;case 35666:return Um;case 35674:return Fm;case 35675:return Om;case 35676:return Bm;case 5124:case 35670:return zm;case 35667:case 35671:return km;case 35668:case 35672:return Vm;case 35669:case 35673:return Gm;case 5125:return Hm;case 36294:return Wm;case 36295:return Xm;case 36296:return qm;case 35678:case 36198:case 36298:case 36306:case 35682:return Ym;case 35679:case 36299:case 36307:return $m;case 35680:case 36300:case 36308:case 36293:return Km;case 36289:case 36303:case 36311:case 36292:return Zm}}class jm{constructor(t,e,n){this.id=t,this.addr=n,this.cache=[],this.type=e.type,this.setValue=Dm(e.type)}}class Qm{constructor(t,e,n){this.id=t,this.addr=n,this.cache=[],this.type=e.type,this.size=e.size,this.setValue=Jm(e.type)}}class tg{constructor(t){this.id=t,this.seq=[],this.map={}}setValue(t,e,n){const s=this.seq;for(let r=0,a=s.length;r!==a;++r){const o=s[r];o.setValue(t,e[o.id],n)}}}const ga=/(\w+)(\])?(\[|\.)?/g;function ac(i,t){i.seq.push(t),i.map[t.id]=t}function eg(i,t,e){const n=i.name,s=n.length;for(ga.lastIndex=0;;){const r=ga.exec(n),a=ga.lastIndex;let o=r[1];const c=r[2]==="]",l=r[3];if(c&&(o=o|0),l===void 0||l==="["&&a+2===s){ac(e,l===void 0?new jm(o,i,t):new Qm(o,i,t));break}else{let d=e.map[o];d===void 0&&(d=new tg(o),ac(e,d)),e=d}}}class _r{constructor(t,e){this.seq=[],this.map={};const n=t.getProgramParameter(e,t.ACTIVE_UNIFORMS);for(let a=0;a<n;++a){const o=t.getActiveUniform(e,a),c=t.getUniformLocation(e,o.name);eg(o,c,this)}const s=[],r=[];for(const a of this.seq)a.type===t.SAMPLER_2D_SHADOW||a.type===t.SAMPLER_CUBE_SHADOW||a.type===t.SAMPLER_2D_ARRAY_SHADOW?s.push(a):r.push(a);s.length>0&&(this.seq=s.concat(r))}setValue(t,e,n,s){const r=this.map[e];r!==void 0&&r.setValue(t,n,s)}setOptional(t,e,n){const s=e[n];s!==void 0&&this.setValue(t,n,s)}static upload(t,e,n,s){for(let r=0,a=e.length;r!==a;++r){const o=e[r],c=n[o.id];c.needsUpdate!==!1&&o.setValue(t,c.value,s)}}static seqWithValue(t,e){const n=[];for(let s=0,r=t.length;s!==r;++s){const a=t[s];a.id in e&&n.push(a)}return n}}function oc(i,t,e){const n=i.createShader(t);return i.shaderSource(n,e),i.compileShader(n),n}const ng=37297;let ig=0;function sg(i,t){const e=i.split(`
`),n=[],s=Math.max(t-6,0),r=Math.min(t+6,e.length);for(let a=s;a<r;a++){const o=a+1;n.push(`${o===t?">":" "} ${o}: ${e[a]}`)}return n.join(`
`)}const lc=new Ht;function rg(i){Qt._getMatrix(lc,Qt.workingColorSpace,i);const t=`mat3( ${lc.elements.map(e=>e.toFixed(4))} )`;switch(Qt.getTransfer(i)){case br:return[t,"LinearTransferOETF"];case re:return[t,"sRGBTransferOETF"];default:return Ut("WebGLProgram: Unsupported color space: ",i),[t,"LinearTransferOETF"]}}function cc(i,t,e){const n=i.getShaderParameter(t,i.COMPILE_STATUS),r=(i.getShaderInfoLog(t)||"").trim();if(n&&r==="")return"";const a=/ERROR: 0:(\d+)/.exec(r);if(a){const o=parseInt(a[1]);return e.toUpperCase()+`

`+r+`

`+sg(i.getShaderSource(t),o)}else return r}function ag(i,t){const e=rg(t);return[`vec4 ${i}( vec4 value ) {`,`	return ${e[1]}( vec4( value.rgb * ${e[0]}, value.a ) );`,"}"].join(`
`)}const og={[Dc]:"Linear",[Lc]:"Reinhard",[Ic]:"Cineon",[Pr]:"ACESFilmic",[Uc]:"AgX",[Fc]:"Neutral",[Nc]:"Custom"};function lg(i,t){const e=og[t];return e===void 0?(Ut("WebGLProgram: Unsupported toneMapping:",t),"vec3 "+i+"( vec3 color ) { return LinearToneMapping( color ); }"):"vec3 "+i+"( vec3 color ) { return "+e+"ToneMapping( color ); }"}const ar=new I;function cg(){Qt.getLuminanceCoefficients(ar);const i=ar.x.toFixed(4),t=ar.y.toFixed(4),e=ar.z.toFixed(4);return["float luminance( const in vec3 rgb ) {",`	const vec3 weights = vec3( ${i}, ${t}, ${e} );`,"	return dot( weights, rgb );","}"].join(`
`)}function hg(i){return[i.extensionClipCullDistance?"#extension GL_ANGLE_clip_cull_distance : require":"",i.extensionMultiDraw?"#extension GL_ANGLE_multi_draw : require":""].filter(fs).join(`
`)}function ug(i){const t=[];for(const e in i){const n=i[e];n!==!1&&t.push("#define "+e+" "+n)}return t.join(`
`)}function dg(i,t){const e={},n=i.getProgramParameter(t,i.ACTIVE_ATTRIBUTES);for(let s=0;s<n;s++){const r=i.getActiveAttrib(t,s),a=r.name;let o=1;r.type===i.FLOAT_MAT2&&(o=2),r.type===i.FLOAT_MAT3&&(o=3),r.type===i.FLOAT_MAT4&&(o=4),e[a]={type:r.type,location:i.getAttribLocation(t,a),locationSize:o}}return e}function fs(i){return i!==""}function hc(i,t){const e=t.numSpotLightShadows+t.numSpotLightMaps-t.numSpotLightShadowsWithMaps;return i.replace(/NUM_DIR_LIGHTS/g,t.numDirLights).replace(/NUM_SPOT_LIGHTS/g,t.numSpotLights).replace(/NUM_SPOT_LIGHT_MAPS/g,t.numSpotLightMaps).replace(/NUM_SPOT_LIGHT_COORDS/g,e).replace(/NUM_RECT_AREA_LIGHTS/g,t.numRectAreaLights).replace(/NUM_POINT_LIGHTS/g,t.numPointLights).replace(/NUM_HEMI_LIGHTS/g,t.numHemiLights).replace(/NUM_DIR_LIGHT_SHADOWS/g,t.numDirLightShadows).replace(/NUM_SPOT_LIGHT_SHADOWS_WITH_MAPS/g,t.numSpotLightShadowsWithMaps).replace(/NUM_SPOT_LIGHT_SHADOWS/g,t.numSpotLightShadows).replace(/NUM_POINT_LIGHT_SHADOWS/g,t.numPointLightShadows)}function uc(i,t){return i.replace(/NUM_CLIPPING_PLANES/g,t.numClippingPlanes).replace(/UNION_CLIPPING_PLANES/g,t.numClippingPlanes-t.numClipIntersection)}const fg=/^[ \t]*#include +<([\w\d./]+)>/gm;function Mo(i){return i.replace(fg,mg)}const pg=new Map;function mg(i,t){let e=$t[t];if(e===void 0){const n=pg.get(t);if(n!==void 0)e=$t[n],Ut('WebGLRenderer: Shader chunk "%s" has been deprecated. Use "%s" instead.',t,n);else throw new Error("THREE.WebGLProgram: Can not resolve #include <"+t+">")}return Mo(e)}const gg=/#pragma unroll_loop_start\s+for\s*\(\s*int\s+i\s*=\s*(\d+)\s*;\s*i\s*<\s*(\d+)\s*;\s*i\s*\+\+\s*\)\s*{([\s\S]+?)}\s+#pragma unroll_loop_end/g;function dc(i){return i.replace(gg,_g)}function _g(i,t,e,n){let s="";for(let r=parseInt(t);r<parseInt(e);r++)s+=n.replace(/\[\s*i\s*\]/g,"[ "+r+" ]").replace(/UNROLLED_LOOP_INDEX/g,r);return s}function fc(i){let t=`precision ${i.precision} float;
	precision ${i.precision} int;
	precision ${i.precision} sampler2D;
	precision ${i.precision} samplerCube;
	precision ${i.precision} sampler3D;
	precision ${i.precision} sampler2DArray;
	precision ${i.precision} sampler2DShadow;
	precision ${i.precision} samplerCubeShadow;
	precision ${i.precision} sampler2DArrayShadow;
	precision ${i.precision} isampler2D;
	precision ${i.precision} isampler3D;
	precision ${i.precision} isamplerCube;
	precision ${i.precision} isampler2DArray;
	precision ${i.precision} usampler2D;
	precision ${i.precision} usampler3D;
	precision ${i.precision} usamplerCube;
	precision ${i.precision} usampler2DArray;
	`;return i.precision==="highp"?t+=`
#define HIGH_PRECISION`:i.precision==="mediump"?t+=`
#define MEDIUM_PRECISION`:i.precision==="lowp"&&(t+=`
#define LOW_PRECISION`),t}const xg={[dr]:"SHADOWMAP_TYPE_PCF",[hs]:"SHADOWMAP_TYPE_VSM"};function vg(i){return xg[i.shadowMapType]||"SHADOWMAP_TYPE_BASIC"}const Mg={[_i]:"ENVMAP_TYPE_CUBE",[qi]:"ENVMAP_TYPE_CUBE",[Dr]:"ENVMAP_TYPE_CUBE_UV"};function Sg(i){return i.envMap===!1?"ENVMAP_TYPE_CUBE":Mg[i.envMapMode]||"ENVMAP_TYPE_CUBE"}const yg={[qi]:"ENVMAP_MODE_REFRACTION"};function bg(i){return i.envMap===!1?"ENVMAP_MODE_REFLECTION":yg[i.envMapMode]||"ENVMAP_MODE_REFLECTION"}const Eg={[bo]:"ENVMAP_BLENDING_MULTIPLY",[su]:"ENVMAP_BLENDING_MIX",[ru]:"ENVMAP_BLENDING_ADD"};function Tg(i){return i.envMap===!1?"ENVMAP_BLENDING_NONE":Eg[i.combine]||"ENVMAP_BLENDING_NONE"}function Ag(i){const t=i.envMapCubeUVHeight;if(t===null)return null;const e=Math.log2(t)-2,n=1/t;return{texelWidth:1/(3*Math.max(Math.pow(2,e),112)),texelHeight:n,maxMip:e}}function wg(i,t,e,n){const s=i.getContext(),r=e.defines;let a=e.vertexShader,o=e.fragmentShader;const c=vg(e),l=Sg(e),h=bg(e),d=Tg(e),u=Ag(e),p=hg(e),g=ug(r),v=s.createProgram();let m,f,E=e.glslVersion?"#version "+e.glslVersion+`
`:"";e.isRawShaderMaterial?(m=["#define SHADER_TYPE "+e.shaderType,"#define SHADER_NAME "+e.shaderName,g].filter(fs).join(`
`),m.length>0&&(m+=`
`),f=["#define SHADER_TYPE "+e.shaderType,"#define SHADER_NAME "+e.shaderName,g].filter(fs).join(`
`),f.length>0&&(f+=`
`)):(m=[fc(e),"#define SHADER_TYPE "+e.shaderType,"#define SHADER_NAME "+e.shaderName,g,e.extensionClipCullDistance?"#define USE_CLIP_DISTANCE":"",e.batching?"#define USE_BATCHING":"",e.batchingColor?"#define USE_BATCHING_COLOR":"",e.instancing?"#define USE_INSTANCING":"",e.instancingColor?"#define USE_INSTANCING_COLOR":"",e.instancingMorph?"#define USE_INSTANCING_MORPH":"",e.useFog&&e.fog?"#define USE_FOG":"",e.useFog&&e.fogExp2?"#define FOG_EXP2":"",e.map?"#define USE_MAP":"",e.envMap?"#define USE_ENVMAP":"",e.envMap?"#define "+h:"",e.lightMap?"#define USE_LIGHTMAP":"",e.aoMap?"#define USE_AOMAP":"",e.bumpMap?"#define USE_BUMPMAP":"",e.normalMap?"#define USE_NORMALMAP":"",e.normalMapObjectSpace?"#define USE_NORMALMAP_OBJECTSPACE":"",e.normalMapTangentSpace?"#define USE_NORMALMAP_TANGENTSPACE":"",e.displacementMap?"#define USE_DISPLACEMENTMAP":"",e.emissiveMap?"#define USE_EMISSIVEMAP":"",e.anisotropy?"#define USE_ANISOTROPY":"",e.anisotropyMap?"#define USE_ANISOTROPYMAP":"",e.clearcoatMap?"#define USE_CLEARCOATMAP":"",e.clearcoatRoughnessMap?"#define USE_CLEARCOAT_ROUGHNESSMAP":"",e.clearcoatNormalMap?"#define USE_CLEARCOAT_NORMALMAP":"",e.iridescenceMap?"#define USE_IRIDESCENCEMAP":"",e.iridescenceThicknessMap?"#define USE_IRIDESCENCE_THICKNESSMAP":"",e.specularMap?"#define USE_SPECULARMAP":"",e.specularColorMap?"#define USE_SPECULAR_COLORMAP":"",e.specularIntensityMap?"#define USE_SPECULAR_INTENSITYMAP":"",e.roughnessMap?"#define USE_ROUGHNESSMAP":"",e.metalnessMap?"#define USE_METALNESSMAP":"",e.alphaMap?"#define USE_ALPHAMAP":"",e.alphaHash?"#define USE_ALPHAHASH":"",e.transmission?"#define USE_TRANSMISSION":"",e.transmissionMap?"#define USE_TRANSMISSIONMAP":"",e.thicknessMap?"#define USE_THICKNESSMAP":"",e.sheenColorMap?"#define USE_SHEEN_COLORMAP":"",e.sheenRoughnessMap?"#define USE_SHEEN_ROUGHNESSMAP":"",e.mapUv?"#define MAP_UV "+e.mapUv:"",e.alphaMapUv?"#define ALPHAMAP_UV "+e.alphaMapUv:"",e.lightMapUv?"#define LIGHTMAP_UV "+e.lightMapUv:"",e.aoMapUv?"#define AOMAP_UV "+e.aoMapUv:"",e.emissiveMapUv?"#define EMISSIVEMAP_UV "+e.emissiveMapUv:"",e.bumpMapUv?"#define BUMPMAP_UV "+e.bumpMapUv:"",e.normalMapUv?"#define NORMALMAP_UV "+e.normalMapUv:"",e.displacementMapUv?"#define DISPLACEMENTMAP_UV "+e.displacementMapUv:"",e.metalnessMapUv?"#define METALNESSMAP_UV "+e.metalnessMapUv:"",e.roughnessMapUv?"#define ROUGHNESSMAP_UV "+e.roughnessMapUv:"",e.anisotropyMapUv?"#define ANISOTROPYMAP_UV "+e.anisotropyMapUv:"",e.clearcoatMapUv?"#define CLEARCOATMAP_UV "+e.clearcoatMapUv:"",e.clearcoatNormalMapUv?"#define CLEARCOAT_NORMALMAP_UV "+e.clearcoatNormalMapUv:"",e.clearcoatRoughnessMapUv?"#define CLEARCOAT_ROUGHNESSMAP_UV "+e.clearcoatRoughnessMapUv:"",e.iridescenceMapUv?"#define IRIDESCENCEMAP_UV "+e.iridescenceMapUv:"",e.iridescenceThicknessMapUv?"#define IRIDESCENCE_THICKNESSMAP_UV "+e.iridescenceThicknessMapUv:"",e.sheenColorMapUv?"#define SHEEN_COLORMAP_UV "+e.sheenColorMapUv:"",e.sheenRoughnessMapUv?"#define SHEEN_ROUGHNESSMAP_UV "+e.sheenRoughnessMapUv:"",e.specularMapUv?"#define SPECULARMAP_UV "+e.specularMapUv:"",e.specularColorMapUv?"#define SPECULAR_COLORMAP_UV "+e.specularColorMapUv:"",e.specularIntensityMapUv?"#define SPECULAR_INTENSITYMAP_UV "+e.specularIntensityMapUv:"",e.transmissionMapUv?"#define TRANSMISSIONMAP_UV "+e.transmissionMapUv:"",e.thicknessMapUv?"#define THICKNESSMAP_UV "+e.thicknessMapUv:"",e.vertexTangents&&e.flatShading===!1?"#define USE_TANGENT":"",e.vertexNormals?"#define HAS_NORMAL":"",e.vertexColors?"#define USE_COLOR":"",e.vertexAlphas?"#define USE_COLOR_ALPHA":"",e.vertexUv1s?"#define USE_UV1":"",e.vertexUv2s?"#define USE_UV2":"",e.vertexUv3s?"#define USE_UV3":"",e.pointsUvs?"#define USE_POINTS_UV":"",e.flatShading?"#define FLAT_SHADED":"",e.skinning?"#define USE_SKINNING":"",e.morphTargets?"#define USE_MORPHTARGETS":"",e.morphNormals&&e.flatShading===!1?"#define USE_MORPHNORMALS":"",e.morphColors?"#define USE_MORPHCOLORS":"",e.morphTargetsCount>0?"#define MORPHTARGETS_TEXTURE_STRIDE "+e.morphTextureStride:"",e.morphTargetsCount>0?"#define MORPHTARGETS_COUNT "+e.morphTargetsCount:"",e.doubleSided?"#define DOUBLE_SIDED":"",e.flipSided?"#define FLIP_SIDED":"",e.shadowMapEnabled?"#define USE_SHADOWMAP":"",e.shadowMapEnabled?"#define "+c:"",e.sizeAttenuation?"#define USE_SIZEATTENUATION":"",e.numLightProbes>0?"#define USE_LIGHT_PROBES":"",e.logarithmicDepthBuffer?"#define USE_LOGARITHMIC_DEPTH_BUFFER":"",e.reversedDepthBuffer?"#define USE_REVERSED_DEPTH_BUFFER":"","uniform mat4 modelMatrix;","uniform mat4 modelViewMatrix;","uniform mat4 projectionMatrix;","uniform mat4 viewMatrix;","uniform mat3 normalMatrix;","uniform vec3 cameraPosition;","uniform bool isOrthographic;","#ifdef USE_INSTANCING","	attribute mat4 instanceMatrix;","#endif","#ifdef USE_INSTANCING_COLOR","	attribute vec3 instanceColor;","#endif","#ifdef USE_INSTANCING_MORPH","	uniform sampler2D morphTexture;","#endif","attribute vec3 position;","attribute vec3 normal;","attribute vec2 uv;","#ifdef USE_UV1","	attribute vec2 uv1;","#endif","#ifdef USE_UV2","	attribute vec2 uv2;","#endif","#ifdef USE_UV3","	attribute vec2 uv3;","#endif","#ifdef USE_TANGENT","	attribute vec4 tangent;","#endif","#if defined( USE_COLOR_ALPHA )","	attribute vec4 color;","#elif defined( USE_COLOR )","	attribute vec3 color;","#endif","#ifdef USE_SKINNING","	attribute vec4 skinIndex;","	attribute vec4 skinWeight;","#endif",`
`].filter(fs).join(`
`),f=[fc(e),"#define SHADER_TYPE "+e.shaderType,"#define SHADER_NAME "+e.shaderName,g,e.useFog&&e.fog?"#define USE_FOG":"",e.useFog&&e.fogExp2?"#define FOG_EXP2":"",e.alphaToCoverage?"#define ALPHA_TO_COVERAGE":"",e.map?"#define USE_MAP":"",e.matcap?"#define USE_MATCAP":"",e.envMap?"#define USE_ENVMAP":"",e.envMap?"#define "+l:"",e.envMap?"#define "+h:"",e.envMap?"#define "+d:"",u?"#define CUBEUV_TEXEL_WIDTH "+u.texelWidth:"",u?"#define CUBEUV_TEXEL_HEIGHT "+u.texelHeight:"",u?"#define CUBEUV_MAX_MIP "+u.maxMip+".0":"",e.lightMap?"#define USE_LIGHTMAP":"",e.aoMap?"#define USE_AOMAP":"",e.bumpMap?"#define USE_BUMPMAP":"",e.normalMap?"#define USE_NORMALMAP":"",e.normalMapObjectSpace?"#define USE_NORMALMAP_OBJECTSPACE":"",e.normalMapTangentSpace?"#define USE_NORMALMAP_TANGENTSPACE":"",e.packedNormalMap?"#define USE_PACKED_NORMALMAP":"",e.emissiveMap?"#define USE_EMISSIVEMAP":"",e.anisotropy?"#define USE_ANISOTROPY":"",e.anisotropyMap?"#define USE_ANISOTROPYMAP":"",e.clearcoat?"#define USE_CLEARCOAT":"",e.clearcoatMap?"#define USE_CLEARCOATMAP":"",e.clearcoatRoughnessMap?"#define USE_CLEARCOAT_ROUGHNESSMAP":"",e.clearcoatNormalMap?"#define USE_CLEARCOAT_NORMALMAP":"",e.dispersion?"#define USE_DISPERSION":"",e.iridescence?"#define USE_IRIDESCENCE":"",e.iridescenceMap?"#define USE_IRIDESCENCEMAP":"",e.iridescenceThicknessMap?"#define USE_IRIDESCENCE_THICKNESSMAP":"",e.specularMap?"#define USE_SPECULARMAP":"",e.specularColorMap?"#define USE_SPECULAR_COLORMAP":"",e.specularIntensityMap?"#define USE_SPECULAR_INTENSITYMAP":"",e.roughnessMap?"#define USE_ROUGHNESSMAP":"",e.metalnessMap?"#define USE_METALNESSMAP":"",e.alphaMap?"#define USE_ALPHAMAP":"",e.alphaTest?"#define USE_ALPHATEST":"",e.alphaHash?"#define USE_ALPHAHASH":"",e.sheen?"#define USE_SHEEN":"",e.sheenColorMap?"#define USE_SHEEN_COLORMAP":"",e.sheenRoughnessMap?"#define USE_SHEEN_ROUGHNESSMAP":"",e.transmission?"#define USE_TRANSMISSION":"",e.transmissionMap?"#define USE_TRANSMISSIONMAP":"",e.thicknessMap?"#define USE_THICKNESSMAP":"",e.vertexTangents&&e.flatShading===!1?"#define USE_TANGENT":"",e.vertexColors||e.instancingColor?"#define USE_COLOR":"",e.vertexAlphas||e.batchingColor?"#define USE_COLOR_ALPHA":"",e.vertexUv1s?"#define USE_UV1":"",e.vertexUv2s?"#define USE_UV2":"",e.vertexUv3s?"#define USE_UV3":"",e.pointsUvs?"#define USE_POINTS_UV":"",e.gradientMap?"#define USE_GRADIENTMAP":"",e.flatShading?"#define FLAT_SHADED":"",e.doubleSided?"#define DOUBLE_SIDED":"",e.flipSided?"#define FLIP_SIDED":"",e.shadowMapEnabled?"#define USE_SHADOWMAP":"",e.shadowMapEnabled?"#define "+c:"",e.premultipliedAlpha?"#define PREMULTIPLIED_ALPHA":"",e.numLightProbes>0?"#define USE_LIGHT_PROBES":"",e.numLightProbeGrids>0?"#define USE_LIGHT_PROBES_GRID":"",e.decodeVideoTexture?"#define DECODE_VIDEO_TEXTURE":"",e.decodeVideoTextureEmissive?"#define DECODE_VIDEO_TEXTURE_EMISSIVE":"",e.logarithmicDepthBuffer?"#define USE_LOGARITHMIC_DEPTH_BUFFER":"",e.reversedDepthBuffer?"#define USE_REVERSED_DEPTH_BUFFER":"","uniform mat4 viewMatrix;","uniform vec3 cameraPosition;","uniform bool isOrthographic;",e.toneMapping!==Mn?"#define TONE_MAPPING":"",e.toneMapping!==Mn?$t.tonemapping_pars_fragment:"",e.toneMapping!==Mn?lg("toneMapping",e.toneMapping):"",e.dithering?"#define DITHERING":"",e.opaque?"#define OPAQUE":"",$t.colorspace_pars_fragment,ag("linearToOutputTexel",e.outputColorSpace),cg(),e.useDepthPacking?"#define DEPTH_PACKING "+e.depthPacking:"",`
`].filter(fs).join(`
`)),a=Mo(a),a=hc(a,e),a=uc(a,e),o=Mo(o),o=hc(o,e),o=uc(o,e),a=dc(a),o=dc(o),e.isRawShaderMaterial!==!0&&(E=`#version 300 es
`,m=[p,"#define attribute in","#define varying out","#define texture2D texture"].join(`
`)+`
`+m,f=["#define varying in",e.glslVersion===ul?"":"layout(location = 0) out highp vec4 pc_fragColor;",e.glslVersion===ul?"":"#define gl_FragColor pc_fragColor","#define gl_FragDepthEXT gl_FragDepth","#define texture2D texture","#define textureCube texture","#define texture2DProj textureProj","#define texture2DLodEXT textureLod","#define texture2DProjLodEXT textureProjLod","#define textureCubeLodEXT textureLod","#define texture2DGradEXT textureGrad","#define texture2DProjGradEXT textureProjGrad","#define textureCubeGradEXT textureGrad"].join(`
`)+`
`+f);const w=E+m+a,y=E+f+o,b=oc(s,s.VERTEX_SHADER,w),M=oc(s,s.FRAGMENT_SHADER,y);s.attachShader(v,b),s.attachShader(v,M),e.index0AttributeName!==void 0?s.bindAttribLocation(v,0,e.index0AttributeName):e.hasPositionAttribute===!0&&s.bindAttribLocation(v,0,"position"),s.linkProgram(v);function A(D){if(i.debug.checkShaderErrors){const B=s.getProgramInfoLog(v)||"",$=s.getShaderInfoLog(b)||"",J=s.getShaderInfoLog(M)||"",z=B.trim(),H=$.trim(),W=J.trim();let tt=!0,Q=!0;if(s.getProgramParameter(v,s.LINK_STATUS)===!1)if(tt=!1,typeof i.debug.onShaderError=="function")i.debug.onShaderError(s,v,b,M);else{const ht=cc(s,b,"vertex"),ut=cc(s,M,"fragment");ee("WebGLProgram: Shader Error "+s.getError()+" - VALIDATE_STATUS "+s.getProgramParameter(v,s.VALIDATE_STATUS)+`

Material Name: `+D.name+`
Material Type: `+D.type+`

Program Info Log: `+z+`
`+ht+`
`+ut)}else z!==""?Ut("WebGLProgram: Program Info Log:",z):(H===""||W==="")&&(Q=!1);Q&&(D.diagnostics={runnable:tt,programLog:z,vertexShader:{log:H,prefix:m},fragmentShader:{log:W,prefix:f}})}s.deleteShader(b),s.deleteShader(M),x=new _r(s,v),T=dg(s,v)}let x;this.getUniforms=function(){return x===void 0&&A(this),x};let T;this.getAttributes=function(){return T===void 0&&A(this),T};let N=e.rendererExtensionParallelShaderCompile===!1;return this.isReady=function(){return N===!1&&(N=s.getProgramParameter(v,ng)),N},this.destroy=function(){n.releaseStatesOfProgram(this),s.deleteProgram(v),this.program=void 0},this.type=e.shaderType,this.name=e.shaderName,this.id=ig++,this.cacheKey=t,this.usedTimes=1,this.program=v,this.vertexShader=b,this.fragmentShader=M,this}let Rg=0;class Cg{constructor(){this.shaderCache=new Map,this.materialCache=new Map}update(t,e,n){const s=this._getShaderCacheForMaterial(t);return s.has(e)===!1&&(s.add(e),e.usedTimes++),s.has(n)===!1&&(s.add(n),n.usedTimes++),this}remove(t){const e=this.materialCache.get(t);for(const n of e)n.usedTimes--,n.usedTimes===0&&this.shaderCache.delete(n.code);return this.materialCache.delete(t),this}getVertexShaderStage(t){return this._getShaderStage(t.vertexShader)}getFragmentShaderStage(t){return this._getShaderStage(t.fragmentShader)}dispose(){this.shaderCache.clear(),this.materialCache.clear()}_getShaderCacheForMaterial(t){const e=this.materialCache;let n=e.get(t);return n===void 0&&(n=new Set,e.set(t,n)),n}_getShaderStage(t){const e=this.shaderCache;let n=e.get(t);return n===void 0&&(n=new Pg(t),e.set(t,n)),n}}class Pg{constructor(t){this.id=Rg++,this.code=t,this.usedTimes=0}}function Dg(i){return i===xi||i===vr||i===Mr}function Lg(i,t,e,n,s,r){const a=new No,o=new Cg,c=new Set,l=[],h=new Map,d=n.logarithmicDepthBuffer;let u=n.precision;const p={MeshDepthMaterial:"depth",MeshDistanceMaterial:"distance",MeshNormalMaterial:"normal",MeshBasicMaterial:"basic",MeshLambertMaterial:"lambert",MeshPhongMaterial:"phong",MeshToonMaterial:"toon",MeshStandardMaterial:"physical",MeshPhysicalMaterial:"physical",MeshMatcapMaterial:"matcap",LineBasicMaterial:"basic",LineDashedMaterial:"dashed",PointsMaterial:"points",ShadowMaterial:"shadow",SpriteMaterial:"sprite"};function g(x){return c.add(x),x===0?"uv":`uv${x}`}function v(x,T,N,D,B,$){const J=D.fog,z=B.geometry,H=x.isMeshStandardMaterial||x.isMeshLambertMaterial||x.isMeshPhongMaterial?D.environment:null,W=x.isMeshStandardMaterial||x.isMeshLambertMaterial&&!x.envMap||x.isMeshPhongMaterial&&!x.envMap,tt=t.get(x.envMap||H,W),Q=tt&&tt.mapping===Dr?tt.image.height:null,ht=p[x.type];x.precision!==null&&(u=n.getMaxPrecision(x.precision),u!==x.precision&&Ut("WebGLProgram.getParameters:",x.precision,"not supported, using",u,"instead."));const ut=z.morphAttributes.position||z.morphAttributes.normal||z.morphAttributes.color,St=ut!==void 0?ut.length:0;let Bt=0;z.morphAttributes.position!==void 0&&(Bt=1),z.morphAttributes.normal!==void 0&&(Bt=2),z.morphAttributes.color!==void 0&&(Bt=3);let Yt,Vt,C,P;if(ht){const wt=gn[ht];Yt=wt.vertexShader,Vt=wt.fragmentShader}else{Yt=x.vertexShader,Vt=x.fragmentShader;const wt=o.getVertexShaderStage(x),me=o.getFragmentShaderStage(x);o.update(x,wt,me),C=wt.id,P=me.id}const U=i.getRenderTarget(),q=i.state.buffers.depth.getReversed(),Z=B.isInstancedMesh===!0,V=B.isBatchedMesh===!0,at=!!x.map,st=!!x.matcap,rt=!!tt,lt=!!x.aoMap,ft=!!x.lightMap,ct=!!x.bumpMap&&x.wireframe===!1,te=!!x.normalMap,Gt=!!x.displacementMap,Jt=!!x.emissiveMap,Kt=!!x.metalnessMap,It=!!x.roughnessMap,L=x.anisotropy>0,ne=x.clearcoat>0,kt=x.dispersion>0,R=x.iridescence>0,_=x.sheen>0,k=x.transmission>0,G=L&&!!x.anisotropyMap,j=ne&&!!x.clearcoatMap,dt=ne&&!!x.clearcoatNormalMap,mt=ne&&!!x.clearcoatRoughnessMap,et=R&&!!x.iridescenceMap,it=R&&!!x.iridescenceThicknessMap,gt=_&&!!x.sheenColorMap,Pt=_&&!!x.sheenRoughnessMap,vt=!!x.specularMap,_t=!!x.specularColorMap,Nt=!!x.specularIntensityMap,Ot=k&&!!x.transmissionMap,Wt=k&&!!x.thicknessMap,F=!!x.gradientMap,pt=!!x.alphaMap,nt=x.alphaTest>0,xt=!!x.alphaHash,Et=!!x.extensions;let ot=Mn;x.toneMapped&&(U===null||U.isXRRenderTarget===!0)&&(ot=i.toneMapping);const Ct={shaderID:ht,shaderType:x.type,shaderName:x.name,vertexShader:Yt,fragmentShader:Vt,defines:x.defines,customVertexShaderID:C,customFragmentShaderID:P,isRawShaderMaterial:x.isRawShaderMaterial===!0,glslVersion:x.glslVersion,precision:u,batching:V,batchingColor:V&&B._colorsTexture!==null,instancing:Z,instancingColor:Z&&B.instanceColor!==null,instancingMorph:Z&&B.morphTexture!==null,outputColorSpace:U===null?i.outputColorSpace:U.isXRRenderTarget===!0?U.texture.colorSpace:Qt.workingColorSpace,alphaToCoverage:!!x.alphaToCoverage,map:at,matcap:st,envMap:rt,envMapMode:rt&&tt.mapping,envMapCubeUVHeight:Q,aoMap:lt,lightMap:ft,bumpMap:ct,normalMap:te,displacementMap:Gt,emissiveMap:Jt,normalMapObjectSpace:te&&x.normalMapType===lu,normalMapTangentSpace:te&&x.normalMapType===Sr,packedNormalMap:te&&x.normalMapType===Sr&&Dg(x.normalMap.format),metalnessMap:Kt,roughnessMap:It,anisotropy:L,anisotropyMap:G,clearcoat:ne,clearcoatMap:j,clearcoatNormalMap:dt,clearcoatRoughnessMap:mt,dispersion:kt,iridescence:R,iridescenceMap:et,iridescenceThicknessMap:it,sheen:_,sheenColorMap:gt,sheenRoughnessMap:Pt,specularMap:vt,specularColorMap:_t,specularIntensityMap:Nt,transmission:k,transmissionMap:Ot,thicknessMap:Wt,gradientMap:F,opaque:x.transparent===!1&&x.blending===Gi&&x.alphaToCoverage===!1,alphaMap:pt,alphaTest:nt,alphaHash:xt,combine:x.combine,mapUv:at&&g(x.map.channel),aoMapUv:lt&&g(x.aoMap.channel),lightMapUv:ft&&g(x.lightMap.channel),bumpMapUv:ct&&g(x.bumpMap.channel),normalMapUv:te&&g(x.normalMap.channel),displacementMapUv:Gt&&g(x.displacementMap.channel),emissiveMapUv:Jt&&g(x.emissiveMap.channel),metalnessMapUv:Kt&&g(x.metalnessMap.channel),roughnessMapUv:It&&g(x.roughnessMap.channel),anisotropyMapUv:G&&g(x.anisotropyMap.channel),clearcoatMapUv:j&&g(x.clearcoatMap.channel),clearcoatNormalMapUv:dt&&g(x.clearcoatNormalMap.channel),clearcoatRoughnessMapUv:mt&&g(x.clearcoatRoughnessMap.channel),iridescenceMapUv:et&&g(x.iridescenceMap.channel),iridescenceThicknessMapUv:it&&g(x.iridescenceThicknessMap.channel),sheenColorMapUv:gt&&g(x.sheenColorMap.channel),sheenRoughnessMapUv:Pt&&g(x.sheenRoughnessMap.channel),specularMapUv:vt&&g(x.specularMap.channel),specularColorMapUv:_t&&g(x.specularColorMap.channel),specularIntensityMapUv:Nt&&g(x.specularIntensityMap.channel),transmissionMapUv:Ot&&g(x.transmissionMap.channel),thicknessMapUv:Wt&&g(x.thicknessMap.channel),alphaMapUv:pt&&g(x.alphaMap.channel),vertexTangents:!!z.attributes.tangent&&(te||L),vertexNormals:!!z.attributes.normal,vertexColors:x.vertexColors,vertexAlphas:x.vertexColors===!0&&!!z.attributes.color&&z.attributes.color.itemSize===4,pointsUvs:B.isPoints===!0&&!!z.attributes.uv&&(at||pt),fog:!!J,useFog:x.fog===!0,fogExp2:!!J&&J.isFogExp2,flatShading:x.wireframe===!1&&(x.flatShading===!0||z.attributes.normal===void 0&&te===!1&&(x.isMeshLambertMaterial||x.isMeshPhongMaterial||x.isMeshStandardMaterial||x.isMeshPhysicalMaterial)),sizeAttenuation:x.sizeAttenuation===!0,logarithmicDepthBuffer:d,reversedDepthBuffer:q,skinning:B.isSkinnedMesh===!0,hasPositionAttribute:z.attributes.position!==void 0,morphTargets:z.morphAttributes.position!==void 0,morphNormals:z.morphAttributes.normal!==void 0,morphColors:z.morphAttributes.color!==void 0,morphTargetsCount:St,morphTextureStride:Bt,numDirLights:T.directional.length,numPointLights:T.point.length,numSpotLights:T.spot.length,numSpotLightMaps:T.spotLightMap.length,numRectAreaLights:T.rectArea.length,numHemiLights:T.hemi.length,numDirLightShadows:T.directionalShadowMap.length,numPointLightShadows:T.pointShadowMap.length,numSpotLightShadows:T.spotShadowMap.length,numSpotLightShadowsWithMaps:T.numSpotLightShadowsWithMaps,numLightProbes:T.numLightProbes,numLightProbeGrids:$.length,numClippingPlanes:r.numPlanes,numClipIntersection:r.numIntersection,dithering:x.dithering,shadowMapEnabled:i.shadowMap.enabled&&N.length>0,shadowMapType:i.shadowMap.type,toneMapping:ot,decodeVideoTexture:at&&x.map.isVideoTexture===!0&&Qt.getTransfer(x.map.colorSpace)===re,decodeVideoTextureEmissive:Jt&&x.emissiveMap.isVideoTexture===!0&&Qt.getTransfer(x.emissiveMap.colorSpace)===re,premultipliedAlpha:x.premultipliedAlpha,doubleSided:x.side===Xe,flipSided:x.side===ke,useDepthPacking:x.depthPacking>=0,depthPacking:x.depthPacking||0,index0AttributeName:x.index0AttributeName,extensionClipCullDistance:Et&&x.extensions.clipCullDistance===!0&&e.has("WEBGL_clip_cull_distance"),extensionMultiDraw:(Et&&x.extensions.multiDraw===!0||V)&&e.has("WEBGL_multi_draw"),rendererExtensionParallelShaderCompile:e.has("KHR_parallel_shader_compile"),customProgramCacheKey:x.customProgramCacheKey()};return Ct.vertexUv1s=c.has(1),Ct.vertexUv2s=c.has(2),Ct.vertexUv3s=c.has(3),c.clear(),Ct}function m(x){const T=[];if(x.shaderID?T.push(x.shaderID):(T.push(x.customVertexShaderID),T.push(x.customFragmentShaderID)),x.defines!==void 0)for(const N in x.defines)T.push(N),T.push(x.defines[N]);return x.isRawShaderMaterial===!1&&(f(T,x),E(T,x),T.push(i.outputColorSpace)),T.push(x.customProgramCacheKey),T.join()}function f(x,T){x.push(T.precision),x.push(T.outputColorSpace),x.push(T.envMapMode),x.push(T.envMapCubeUVHeight),x.push(T.mapUv),x.push(T.alphaMapUv),x.push(T.lightMapUv),x.push(T.aoMapUv),x.push(T.bumpMapUv),x.push(T.normalMapUv),x.push(T.displacementMapUv),x.push(T.emissiveMapUv),x.push(T.metalnessMapUv),x.push(T.roughnessMapUv),x.push(T.anisotropyMapUv),x.push(T.clearcoatMapUv),x.push(T.clearcoatNormalMapUv),x.push(T.clearcoatRoughnessMapUv),x.push(T.iridescenceMapUv),x.push(T.iridescenceThicknessMapUv),x.push(T.sheenColorMapUv),x.push(T.sheenRoughnessMapUv),x.push(T.specularMapUv),x.push(T.specularColorMapUv),x.push(T.specularIntensityMapUv),x.push(T.transmissionMapUv),x.push(T.thicknessMapUv),x.push(T.combine),x.push(T.fogExp2),x.push(T.sizeAttenuation),x.push(T.morphTargetsCount),x.push(T.morphAttributeCount),x.push(T.numDirLights),x.push(T.numPointLights),x.push(T.numSpotLights),x.push(T.numSpotLightMaps),x.push(T.numHemiLights),x.push(T.numRectAreaLights),x.push(T.numDirLightShadows),x.push(T.numPointLightShadows),x.push(T.numSpotLightShadows),x.push(T.numSpotLightShadowsWithMaps),x.push(T.numLightProbes),x.push(T.shadowMapType),x.push(T.toneMapping),x.push(T.numClippingPlanes),x.push(T.numClipIntersection),x.push(T.depthPacking)}function E(x,T){a.disableAll(),T.instancing&&a.enable(0),T.instancingColor&&a.enable(1),T.instancingMorph&&a.enable(2),T.matcap&&a.enable(3),T.envMap&&a.enable(4),T.normalMapObjectSpace&&a.enable(5),T.normalMapTangentSpace&&a.enable(6),T.clearcoat&&a.enable(7),T.iridescence&&a.enable(8),T.alphaTest&&a.enable(9),T.vertexColors&&a.enable(10),T.vertexAlphas&&a.enable(11),T.vertexUv1s&&a.enable(12),T.vertexUv2s&&a.enable(13),T.vertexUv3s&&a.enable(14),T.vertexTangents&&a.enable(15),T.anisotropy&&a.enable(16),T.alphaHash&&a.enable(17),T.batching&&a.enable(18),T.dispersion&&a.enable(19),T.batchingColor&&a.enable(20),T.gradientMap&&a.enable(21),T.packedNormalMap&&a.enable(22),T.vertexNormals&&a.enable(23),x.push(a.mask),a.disableAll(),T.fog&&a.enable(0),T.useFog&&a.enable(1),T.flatShading&&a.enable(2),T.logarithmicDepthBuffer&&a.enable(3),T.reversedDepthBuffer&&a.enable(4),T.skinning&&a.enable(5),T.morphTargets&&a.enable(6),T.morphNormals&&a.enable(7),T.morphColors&&a.enable(8),T.premultipliedAlpha&&a.enable(9),T.shadowMapEnabled&&a.enable(10),T.doubleSided&&a.enable(11),T.flipSided&&a.enable(12),T.useDepthPacking&&a.enable(13),T.dithering&&a.enable(14),T.transmission&&a.enable(15),T.sheen&&a.enable(16),T.opaque&&a.enable(17),T.pointsUvs&&a.enable(18),T.decodeVideoTexture&&a.enable(19),T.decodeVideoTextureEmissive&&a.enable(20),T.alphaToCoverage&&a.enable(21),T.numLightProbeGrids>0&&a.enable(22),T.hasPositionAttribute&&a.enable(23),x.push(a.mask)}function w(x){const T=p[x.type];let N;if(T){const D=gn[T];N=qu.clone(D.uniforms)}else N=x.uniforms;return N}function y(x,T){let N=h.get(T);return N!==void 0?++N.usedTimes:(N=new wg(i,T,x,s),l.push(N),h.set(T,N)),N}function b(x){if(--x.usedTimes===0){const T=l.indexOf(x);l[T]=l[l.length-1],l.pop(),h.delete(x.cacheKey),x.destroy()}}function M(x){o.remove(x)}function A(){o.dispose()}return{getParameters:v,getProgramCacheKey:m,getUniforms:w,acquireProgram:y,releaseProgram:b,releaseShaderCache:M,programs:l,dispose:A}}function Ig(){let i=new WeakMap;function t(a){return i.has(a)}function e(a){let o=i.get(a);return o===void 0&&(o={},i.set(a,o)),o}function n(a){i.delete(a)}function s(a,o,c){i.get(a)[o]=c}function r(){i=new WeakMap}return{has:t,get:e,remove:n,update:s,dispose:r}}function Ng(i,t){return i.groupOrder!==t.groupOrder?i.groupOrder-t.groupOrder:i.renderOrder!==t.renderOrder?i.renderOrder-t.renderOrder:i.material.id!==t.material.id?i.material.id-t.material.id:i.materialVariant!==t.materialVariant?i.materialVariant-t.materialVariant:i.z!==t.z?i.z-t.z:i.id-t.id}function pc(i,t){return i.groupOrder!==t.groupOrder?i.groupOrder-t.groupOrder:i.renderOrder!==t.renderOrder?i.renderOrder-t.renderOrder:i.z!==t.z?t.z-i.z:i.id-t.id}function mc(){const i=[];let t=0;const e=[],n=[],s=[];function r(){t=0,e.length=0,n.length=0,s.length=0}function a(u){let p=0;return u.isInstancedMesh&&(p+=2),u.isSkinnedMesh&&(p+=1),p}function o(u,p,g,v,m,f){let E=i[t];return E===void 0?(E={id:u.id,object:u,geometry:p,material:g,materialVariant:a(u),groupOrder:v,renderOrder:u.renderOrder,z:m,group:f},i[t]=E):(E.id=u.id,E.object=u,E.geometry=p,E.material=g,E.materialVariant=a(u),E.groupOrder=v,E.renderOrder=u.renderOrder,E.z=m,E.group=f),t++,E}function c(u,p,g,v,m,f){const E=o(u,p,g,v,m,f);g.transmission>0?n.push(E):g.transparent===!0?s.push(E):e.push(E)}function l(u,p,g,v,m,f){const E=o(u,p,g,v,m,f);g.transmission>0?n.unshift(E):g.transparent===!0?s.unshift(E):e.unshift(E)}function h(u,p,g){e.length>1&&e.sort(u||Ng),n.length>1&&n.sort(p||pc),s.length>1&&s.sort(p||pc),g&&(e.reverse(),n.reverse(),s.reverse())}function d(){for(let u=t,p=i.length;u<p;u++){const g=i[u];if(g.id===null)break;g.id=null,g.object=null,g.geometry=null,g.material=null,g.group=null}}return{opaque:e,transmissive:n,transparent:s,init:r,push:c,unshift:l,finish:d,sort:h}}function Ug(){let i=new WeakMap;function t(n,s){const r=i.get(n);let a;return r===void 0?(a=new mc,i.set(n,[a])):s>=r.length?(a=new mc,r.push(a)):a=r[s],a}function e(){i=new WeakMap}return{get:t,dispose:e}}function Fg(){const i={};return{get:function(t){if(i[t.id]!==void 0)return i[t.id];let e;switch(t.type){case"DirectionalLight":e={direction:new I,color:new Ft};break;case"SpotLight":e={position:new I,direction:new I,color:new Ft,distance:0,coneCos:0,penumbraCos:0,decay:0};break;case"PointLight":e={position:new I,color:new Ft,distance:0,decay:0};break;case"HemisphereLight":e={direction:new I,skyColor:new Ft,groundColor:new Ft};break;case"RectAreaLight":e={color:new Ft,position:new I,halfWidth:new I,halfHeight:new I};break}return i[t.id]=e,e}}}function Og(){const i={};return{get:function(t){if(i[t.id]!==void 0)return i[t.id];let e;switch(t.type){case"DirectionalLight":e={shadowIntensity:1,shadowBias:0,shadowNormalBias:0,shadowRadius:1,shadowMapSize:new zt};break;case"SpotLight":e={shadowIntensity:1,shadowBias:0,shadowNormalBias:0,shadowRadius:1,shadowMapSize:new zt};break;case"PointLight":e={shadowIntensity:1,shadowBias:0,shadowNormalBias:0,shadowRadius:1,shadowMapSize:new zt,shadowCameraNear:1,shadowCameraFar:1e3};break}return i[t.id]=e,e}}}let Bg=0;function zg(i,t){return(t.castShadow?2:0)-(i.castShadow?2:0)+(t.map?1:0)-(i.map?1:0)}function kg(i){const t=new Fg,e=Og(),n={version:0,hash:{directionalLength:-1,pointLength:-1,spotLength:-1,rectAreaLength:-1,hemiLength:-1,numDirectionalShadows:-1,numPointShadows:-1,numSpotShadows:-1,numSpotMaps:-1,numLightProbes:-1},ambient:[0,0,0],probe:[],directional:[],directionalShadow:[],directionalShadowMap:[],directionalShadowMatrix:[],spot:[],spotLightMap:[],spotShadow:[],spotShadowMap:[],spotLightMatrix:[],rectArea:[],rectAreaLTC1:null,rectAreaLTC2:null,point:[],pointShadow:[],pointShadowMap:[],pointShadowMatrix:[],hemi:[],numSpotLightShadowsWithMaps:0,numLightProbes:0};for(let l=0;l<9;l++)n.probe.push(new I);const s=new I,r=new se,a=new se;function o(l){let h=0,d=0,u=0;for(let T=0;T<9;T++)n.probe[T].set(0,0,0);let p=0,g=0,v=0,m=0,f=0,E=0,w=0,y=0,b=0,M=0,A=0;l.sort(zg);for(let T=0,N=l.length;T<N;T++){const D=l[T],B=D.color,$=D.intensity,J=D.distance;let z=null;if(D.shadow&&D.shadow.map&&(D.shadow.map.texture.format===xi?z=D.shadow.map.texture:z=D.shadow.map.depthTexture||D.shadow.map.texture),D.isAmbientLight)h+=B.r*$,d+=B.g*$,u+=B.b*$;else if(D.isLightProbe){for(let H=0;H<9;H++)n.probe[H].addScaledVector(D.sh.coefficients[H],$);A++}else if(D.isDirectionalLight){const H=t.get(D);if(H.color.copy(D.color).multiplyScalar(D.intensity),D.castShadow){const W=D.shadow,tt=e.get(D);tt.shadowIntensity=W.intensity,tt.shadowBias=W.bias,tt.shadowNormalBias=W.normalBias,tt.shadowRadius=W.radius,tt.shadowMapSize=W.mapSize,n.directionalShadow[p]=tt,n.directionalShadowMap[p]=z,n.directionalShadowMatrix[p]=D.shadow.matrix,E++}n.directional[p]=H,p++}else if(D.isSpotLight){const H=t.get(D);H.position.setFromMatrixPosition(D.matrixWorld),H.color.copy(B).multiplyScalar($),H.distance=J,H.coneCos=Math.cos(D.angle),H.penumbraCos=Math.cos(D.angle*(1-D.penumbra)),H.decay=D.decay,n.spot[v]=H;const W=D.shadow;if(D.map&&(n.spotLightMap[b]=D.map,b++,W.updateMatrices(D),D.castShadow&&M++),n.spotLightMatrix[v]=W.matrix,D.castShadow){const tt=e.get(D);tt.shadowIntensity=W.intensity,tt.shadowBias=W.bias,tt.shadowNormalBias=W.normalBias,tt.shadowRadius=W.radius,tt.shadowMapSize=W.mapSize,n.spotShadow[v]=tt,n.spotShadowMap[v]=z,y++}v++}else if(D.isRectAreaLight){const H=t.get(D);H.color.copy(B).multiplyScalar($),H.halfWidth.set(D.width*.5,0,0),H.halfHeight.set(0,D.height*.5,0),n.rectArea[m]=H,m++}else if(D.isPointLight){const H=t.get(D);if(H.color.copy(D.color).multiplyScalar(D.intensity),H.distance=D.distance,H.decay=D.decay,D.castShadow){const W=D.shadow,tt=e.get(D);tt.shadowIntensity=W.intensity,tt.shadowBias=W.bias,tt.shadowNormalBias=W.normalBias,tt.shadowRadius=W.radius,tt.shadowMapSize=W.mapSize,tt.shadowCameraNear=W.camera.near,tt.shadowCameraFar=W.camera.far,n.pointShadow[g]=tt,n.pointShadowMap[g]=z,n.pointShadowMatrix[g]=D.shadow.matrix,w++}n.point[g]=H,g++}else if(D.isHemisphereLight){const H=t.get(D);H.skyColor.copy(D.color).multiplyScalar($),H.groundColor.copy(D.groundColor).multiplyScalar($),n.hemi[f]=H,f++}}m>0&&(i.has("OES_texture_float_linear")===!0?(n.rectAreaLTC1=Mt.LTC_FLOAT_1,n.rectAreaLTC2=Mt.LTC_FLOAT_2):(n.rectAreaLTC1=Mt.LTC_HALF_1,n.rectAreaLTC2=Mt.LTC_HALF_2)),n.ambient[0]=h,n.ambient[1]=d,n.ambient[2]=u;const x=n.hash;(x.directionalLength!==p||x.pointLength!==g||x.spotLength!==v||x.rectAreaLength!==m||x.hemiLength!==f||x.numDirectionalShadows!==E||x.numPointShadows!==w||x.numSpotShadows!==y||x.numSpotMaps!==b||x.numLightProbes!==A)&&(n.directional.length=p,n.spot.length=v,n.rectArea.length=m,n.point.length=g,n.hemi.length=f,n.directionalShadow.length=E,n.directionalShadowMap.length=E,n.pointShadow.length=w,n.pointShadowMap.length=w,n.spotShadow.length=y,n.spotShadowMap.length=y,n.directionalShadowMatrix.length=E,n.pointShadowMatrix.length=w,n.spotLightMatrix.length=y+b-M,n.spotLightMap.length=b,n.numSpotLightShadowsWithMaps=M,n.numLightProbes=A,x.directionalLength=p,x.pointLength=g,x.spotLength=v,x.rectAreaLength=m,x.hemiLength=f,x.numDirectionalShadows=E,x.numPointShadows=w,x.numSpotShadows=y,x.numSpotMaps=b,x.numLightProbes=A,n.version=Bg++)}function c(l,h){let d=0,u=0,p=0,g=0,v=0;const m=h.matrixWorldInverse;for(let f=0,E=l.length;f<E;f++){const w=l[f];if(w.isDirectionalLight){const y=n.directional[d];y.direction.setFromMatrixPosition(w.matrixWorld),s.setFromMatrixPosition(w.target.matrixWorld),y.direction.sub(s),y.direction.transformDirection(m),d++}else if(w.isSpotLight){const y=n.spot[p];y.position.setFromMatrixPosition(w.matrixWorld),y.position.applyMatrix4(m),y.direction.setFromMatrixPosition(w.matrixWorld),s.setFromMatrixPosition(w.target.matrixWorld),y.direction.sub(s),y.direction.transformDirection(m),p++}else if(w.isRectAreaLight){const y=n.rectArea[g];y.position.setFromMatrixPosition(w.matrixWorld),y.position.applyMatrix4(m),a.identity(),r.copy(w.matrixWorld),r.premultiply(m),a.extractRotation(r),y.halfWidth.set(w.width*.5,0,0),y.halfHeight.set(0,w.height*.5,0),y.halfWidth.applyMatrix4(a),y.halfHeight.applyMatrix4(a),g++}else if(w.isPointLight){const y=n.point[u];y.position.setFromMatrixPosition(w.matrixWorld),y.position.applyMatrix4(m),u++}else if(w.isHemisphereLight){const y=n.hemi[v];y.direction.setFromMatrixPosition(w.matrixWorld),y.direction.transformDirection(m),v++}}}return{setup:o,setupView:c,state:n}}function gc(i){const t=new kg(i),e=[],n=[],s=[];function r(u){d.camera=u,e.length=0,n.length=0,s.length=0}function a(u){e.push(u)}function o(u){n.push(u)}function c(u){s.push(u)}function l(){t.setup(e)}function h(u){t.setupView(e,u)}const d={lightsArray:e,shadowsArray:n,lightProbeGridArray:s,camera:null,lights:t,transmissionRenderTarget:{},textureUnits:0};return{init:r,state:d,setupLights:l,setupLightsView:h,pushLight:a,pushShadow:o,pushLightProbeGrid:c}}function Vg(i){let t=new WeakMap;function e(s,r=0){const a=t.get(s);let o;return a===void 0?(o=new gc(i),t.set(s,[o])):r>=a.length?(o=new gc(i),a.push(o)):o=a[r],o}function n(){t=new WeakMap}return{get:e,dispose:n}}const Gg=`void main() {
	gl_Position = vec4( position, 1.0 );
}`,Hg=`uniform sampler2D shadow_pass;
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
}`,Wg=[new I(1,0,0),new I(-1,0,0),new I(0,1,0),new I(0,-1,0),new I(0,0,1),new I(0,0,-1)],Xg=[new I(0,-1,0),new I(0,-1,0),new I(0,0,1),new I(0,0,-1),new I(0,-1,0),new I(0,-1,0)],_c=new se,as=new I,_a=new I;function qg(i,t,e){let n=new Uo;const s=new zt,r=new zt,a=new pe,o=new Zu,c=new Ju,l={},h=e.maxTextureSize,d={[ni]:ke,[ke]:ni,[Xe]:Xe},u=new En({defines:{VSM_SAMPLES:8},uniforms:{shadow_pass:{value:null},resolution:{value:new zt},radius:{value:4}},vertexShader:Gg,fragmentShader:Hg}),p=u.clone();p.defines.HORIZONTAL_PASS=1;const g=new _e;g.setAttribute("position",new Pe(new Float32Array([-1,-1,.5,3,-1,.5,-1,3,.5]),3));const v=new Me(g,u),m=this;this.enabled=!1,this.autoUpdate=!0,this.needsUpdate=!1,this.type=dr;let f=this.type;this.render=function(M,A,x){if(m.enabled===!1||m.autoUpdate===!1&&m.needsUpdate===!1||M.length===0)return;this.type===zh&&(Ut("WebGLShadowMap: PCFSoftShadowMap has been deprecated. Using PCFShadowMap instead."),this.type=dr);const T=i.getRenderTarget(),N=i.getActiveCubeFace(),D=i.getActiveMipmapLevel(),B=i.state;B.setBlending(In),B.buffers.depth.getReversed()===!0?B.buffers.color.setClear(0,0,0,0):B.buffers.color.setClear(1,1,1,1),B.buffers.depth.setTest(!0),B.setScissorTest(!1);const $=f!==this.type;$&&A.traverse(function(J){J.material&&(Array.isArray(J.material)?J.material.forEach(z=>z.needsUpdate=!0):J.material.needsUpdate=!0)});for(let J=0,z=M.length;J<z;J++){const H=M[J],W=H.shadow;if(W===void 0){Ut("WebGLShadowMap:",H,"has no shadow.");continue}if(W.autoUpdate===!1&&W.needsUpdate===!1)continue;s.copy(W.mapSize);const tt=W.getFrameExtents();s.multiply(tt),r.copy(W.mapSize),(s.x>h||s.y>h)&&(s.x>h&&(r.x=Math.floor(h/tt.x),s.x=r.x*tt.x,W.mapSize.x=r.x),s.y>h&&(r.y=Math.floor(h/tt.y),s.y=r.y*tt.y,W.mapSize.y=r.y));const Q=i.state.buffers.depth.getReversed();if(W.camera._reversedDepth=Q,W.map===null||$===!0){if(W.map!==null&&(W.map.depthTexture!==null&&(W.map.depthTexture.dispose(),W.map.depthTexture=null),W.map.dispose()),this.type===hs){if(H.isPointLight){Ut("WebGLShadowMap: VSM shadow maps are not supported for PointLights. Use PCF or BasicShadowMap instead.");continue}W.map=new Sn(s.x,s.y,{format:xi,type:Un,minFilter:ye,magFilter:ye,generateMipmaps:!1}),W.map.texture.name=H.name+".shadowMap",W.map.depthTexture=new Yi(s.x,s.y,cn),W.map.depthTexture.name=H.name+".shadowMapDepth",W.map.depthTexture.format=Fn,W.map.depthTexture.compareFunction=null,W.map.depthTexture.minFilter=Ee,W.map.depthTexture.magFilter=Ee}else H.isPointLight?(W.map=new oh(s.x),W.map.depthTexture=new Wu(s.x,yn)):(W.map=new Sn(s.x,s.y),W.map.depthTexture=new Yi(s.x,s.y,yn)),W.map.depthTexture.name=H.name+".shadowMap",W.map.depthTexture.format=Fn,this.type===dr?(W.map.depthTexture.compareFunction=Q?Lo:Do,W.map.depthTexture.minFilter=ye,W.map.depthTexture.magFilter=ye):(W.map.depthTexture.compareFunction=null,W.map.depthTexture.minFilter=Ee,W.map.depthTexture.magFilter=Ee);W.camera.updateProjectionMatrix()}const ht=W.map.isWebGLCubeRenderTarget?6:1;for(let ut=0;ut<ht;ut++){if(W.map.isWebGLCubeRenderTarget)i.setRenderTarget(W.map,ut),i.clear();else{ut===0&&(i.setRenderTarget(W.map),i.clear());const St=W.getViewport(ut);a.set(r.x*St.x,r.y*St.y,r.x*St.z,r.y*St.w),B.viewport(a)}if(H.isPointLight){const St=W.camera,Bt=W.matrix,Yt=H.distance||St.far;Yt!==St.far&&(St.far=Yt,St.updateProjectionMatrix()),as.setFromMatrixPosition(H.matrixWorld),St.position.copy(as),_a.copy(St.position),_a.add(Wg[ut]),St.up.copy(Xg[ut]),St.lookAt(_a),St.updateMatrixWorld(),Bt.makeTranslation(-as.x,-as.y,-as.z),_c.multiplyMatrices(St.projectionMatrix,St.matrixWorldInverse),W._frustum.setFromProjectionMatrix(_c,St.coordinateSystem,St.reversedDepth)}else W.updateMatrices(H);n=W.getFrustum(),y(A,x,W.camera,H,this.type)}W.isPointLightShadow!==!0&&this.type===hs&&E(W,x),W.needsUpdate=!1}f=this.type,m.needsUpdate=!1,i.setRenderTarget(T,N,D)};function E(M,A){const x=t.update(v);u.defines.VSM_SAMPLES!==M.blurSamples&&(u.defines.VSM_SAMPLES=M.blurSamples,p.defines.VSM_SAMPLES=M.blurSamples,u.needsUpdate=!0,p.needsUpdate=!0),M.mapPass===null&&(M.mapPass=new Sn(s.x,s.y,{format:xi,type:Un})),u.uniforms.shadow_pass.value=M.map.depthTexture,u.uniforms.resolution.value=M.mapSize,u.uniforms.radius.value=M.radius,i.setRenderTarget(M.mapPass),i.clear(),i.renderBufferDirect(A,null,x,u,v,null),p.uniforms.shadow_pass.value=M.mapPass.texture,p.uniforms.resolution.value=M.mapSize,p.uniforms.radius.value=M.radius,i.setRenderTarget(M.map),i.clear(),i.renderBufferDirect(A,null,x,p,v,null)}function w(M,A,x,T){let N=null;const D=x.isPointLight===!0?M.customDistanceMaterial:M.customDepthMaterial;if(D!==void 0)N=D;else if(N=x.isPointLight===!0?c:o,i.localClippingEnabled&&A.clipShadows===!0&&Array.isArray(A.clippingPlanes)&&A.clippingPlanes.length!==0||A.displacementMap&&A.displacementScale!==0||A.alphaMap&&A.alphaTest>0||A.map&&A.alphaTest>0||A.alphaToCoverage===!0){const B=N.uuid,$=A.uuid;let J=l[B];J===void 0&&(J={},l[B]=J);let z=J[$];z===void 0&&(z=N.clone(),J[$]=z,A.addEventListener("dispose",b)),N=z}if(N.visible=A.visible,N.wireframe=A.wireframe,T===hs?N.side=A.shadowSide!==null?A.shadowSide:A.side:N.side=A.shadowSide!==null?A.shadowSide:d[A.side],N.alphaMap=A.alphaMap,N.alphaTest=A.alphaToCoverage===!0?.5:A.alphaTest,N.map=A.map,N.clipShadows=A.clipShadows,N.clippingPlanes=A.clippingPlanes,N.clipIntersection=A.clipIntersection,N.displacementMap=A.displacementMap,N.displacementScale=A.displacementScale,N.displacementBias=A.displacementBias,N.wireframeLinewidth=A.wireframeLinewidth,N.linewidth=A.linewidth,x.isPointLight===!0&&N.isMeshDistanceMaterial===!0){const B=i.properties.get(N);B.light=x}return N}function y(M,A,x,T,N){if(M.visible===!1)return;if(M.layers.test(A.layers)&&(M.isMesh||M.isLine||M.isPoints)&&(M.castShadow||M.receiveShadow&&N===hs)&&(!M.frustumCulled||n.intersectsObject(M))){M.modelViewMatrix.multiplyMatrices(x.matrixWorldInverse,M.matrixWorld);const $=t.update(M),J=M.material;if(Array.isArray(J)){const z=$.groups;for(let H=0,W=z.length;H<W;H++){const tt=z[H],Q=J[tt.materialIndex];if(Q&&Q.visible){const ht=w(M,Q,T,N);M.onBeforeShadow(i,M,A,x,$,ht,tt),i.renderBufferDirect(x,null,$,ht,M,tt),M.onAfterShadow(i,M,A,x,$,ht,tt)}}}else if(J.visible){const z=w(M,J,T,N);M.onBeforeShadow(i,M,A,x,$,z,null),i.renderBufferDirect(x,null,$,z,M,null),M.onAfterShadow(i,M,A,x,$,z,null)}}const B=M.children;for(let $=0,J=B.length;$<J;$++)y(B[$],A,x,T,N)}function b(M){M.target.removeEventListener("dispose",b);for(const x in l){const T=l[x],N=M.target.uuid;N in T&&(T[N].dispose(),delete T[N])}}}function Yg(i,t){function e(){let F=!1;const pt=new pe;let nt=null;const xt=new pe(0,0,0,0);return{setMask:function(Et){nt!==Et&&!F&&(i.colorMask(Et,Et,Et,Et),nt=Et)},setLocked:function(Et){F=Et},setClear:function(Et,ot,Ct,wt,me){me===!0&&(Et*=wt,ot*=wt,Ct*=wt),pt.set(Et,ot,Ct,wt),xt.equals(pt)===!1&&(i.clearColor(Et,ot,Ct,wt),xt.copy(pt))},reset:function(){F=!1,nt=null,xt.set(-1,0,0,0)}}}function n(){let F=!1,pt=!1,nt=null,xt=null,Et=null;return{setReversed:function(ot){if(pt!==ot){const Ct=t.get("EXT_clip_control");ot?Ct.clipControlEXT(Ct.LOWER_LEFT_EXT,Ct.ZERO_TO_ONE_EXT):Ct.clipControlEXT(Ct.LOWER_LEFT_EXT,Ct.NEGATIVE_ONE_TO_ONE_EXT),pt=ot;const wt=Et;Et=null,this.setClear(wt)}},getReversed:function(){return pt},setTest:function(ot){ot?U(i.DEPTH_TEST):q(i.DEPTH_TEST)},setMask:function(ot){nt!==ot&&!F&&(i.depthMask(ot),nt=ot)},setFunc:function(ot){if(pt&&(ot=xu[ot]),xt!==ot){switch(ot){case La:i.depthFunc(i.NEVER);break;case Ia:i.depthFunc(i.ALWAYS);break;case Na:i.depthFunc(i.LESS);break;case Xi:i.depthFunc(i.LEQUAL);break;case Ua:i.depthFunc(i.EQUAL);break;case Fa:i.depthFunc(i.GEQUAL);break;case Oa:i.depthFunc(i.GREATER);break;case Ba:i.depthFunc(i.NOTEQUAL);break;default:i.depthFunc(i.LEQUAL)}xt=ot}},setLocked:function(ot){F=ot},setClear:function(ot){Et!==ot&&(Et=ot,pt&&(ot=1-ot),i.clearDepth(ot))},reset:function(){F=!1,nt=null,xt=null,Et=null,pt=!1}}}function s(){let F=!1,pt=null,nt=null,xt=null,Et=null,ot=null,Ct=null,wt=null,me=null;return{setTest:function(ue){F||(ue?U(i.STENCIL_TEST):q(i.STENCIL_TEST))},setMask:function(ue){pt!==ue&&!F&&(i.stencilMask(ue),pt=ue)},setFunc:function(ue,un,dn){(nt!==ue||xt!==un||Et!==dn)&&(i.stencilFunc(ue,un,dn),nt=ue,xt=un,Et=dn)},setOp:function(ue,un,dn){(ot!==ue||Ct!==un||wt!==dn)&&(i.stencilOp(ue,un,dn),ot=ue,Ct=un,wt=dn)},setLocked:function(ue){F=ue},setClear:function(ue){me!==ue&&(i.clearStencil(ue),me=ue)},reset:function(){F=!1,pt=null,nt=null,xt=null,Et=null,ot=null,Ct=null,wt=null,me=null}}}const r=new e,a=new n,o=new s,c=new WeakMap,l=new WeakMap;let h={},d={},u={},p=new WeakMap,g=[],v=null,m=!1,f=null,E=null,w=null,y=null,b=null,M=null,A=null,x=new Ft(0,0,0),T=0,N=!1,D=null,B=null,$=null,J=null,z=null;const H=i.getParameter(i.MAX_COMBINED_TEXTURE_IMAGE_UNITS);let W=!1,tt=0;const Q=i.getParameter(i.VERSION);Q.indexOf("WebGL")!==-1?(tt=parseFloat(/^WebGL (\d)/.exec(Q)[1]),W=tt>=1):Q.indexOf("OpenGL ES")!==-1&&(tt=parseFloat(/^OpenGL ES (\d)/.exec(Q)[1]),W=tt>=2);let ht=null,ut={};const St=i.getParameter(i.SCISSOR_BOX),Bt=i.getParameter(i.VIEWPORT),Yt=new pe().fromArray(St),Vt=new pe().fromArray(Bt);function C(F,pt,nt,xt){const Et=new Uint8Array(4),ot=i.createTexture();i.bindTexture(F,ot),i.texParameteri(F,i.TEXTURE_MIN_FILTER,i.NEAREST),i.texParameteri(F,i.TEXTURE_MAG_FILTER,i.NEAREST);for(let Ct=0;Ct<nt;Ct++)F===i.TEXTURE_3D||F===i.TEXTURE_2D_ARRAY?i.texImage3D(pt,0,i.RGBA,1,1,xt,0,i.RGBA,i.UNSIGNED_BYTE,Et):i.texImage2D(pt+Ct,0,i.RGBA,1,1,0,i.RGBA,i.UNSIGNED_BYTE,Et);return ot}const P={};P[i.TEXTURE_2D]=C(i.TEXTURE_2D,i.TEXTURE_2D,1),P[i.TEXTURE_CUBE_MAP]=C(i.TEXTURE_CUBE_MAP,i.TEXTURE_CUBE_MAP_POSITIVE_X,6),P[i.TEXTURE_2D_ARRAY]=C(i.TEXTURE_2D_ARRAY,i.TEXTURE_2D_ARRAY,1,1),P[i.TEXTURE_3D]=C(i.TEXTURE_3D,i.TEXTURE_3D,1,1),r.setClear(0,0,0,1),a.setClear(1),o.setClear(0),U(i.DEPTH_TEST),a.setFunc(Xi),ct(!1),te(rl),U(i.CULL_FACE),lt(In);function U(F){h[F]!==!0&&(i.enable(F),h[F]=!0)}function q(F){h[F]!==!1&&(i.disable(F),h[F]=!1)}function Z(F,pt){return u[F]!==pt?(i.bindFramebuffer(F,pt),u[F]=pt,F===i.DRAW_FRAMEBUFFER&&(u[i.FRAMEBUFFER]=pt),F===i.FRAMEBUFFER&&(u[i.DRAW_FRAMEBUFFER]=pt),!0):!1}function V(F,pt){let nt=g,xt=!1;if(F){nt=p.get(pt),nt===void 0&&(nt=[],p.set(pt,nt));const Et=F.textures;if(nt.length!==Et.length||nt[0]!==i.COLOR_ATTACHMENT0){for(let ot=0,Ct=Et.length;ot<Ct;ot++)nt[ot]=i.COLOR_ATTACHMENT0+ot;nt.length=Et.length,xt=!0}}else nt[0]!==i.BACK&&(nt[0]=i.BACK,xt=!0);xt&&i.drawBuffers(nt)}function at(F){return v!==F?(i.useProgram(F),v=F,!0):!1}const st={[di]:i.FUNC_ADD,[Vh]:i.FUNC_SUBTRACT,[Gh]:i.FUNC_REVERSE_SUBTRACT};st[Hh]=i.MIN,st[Wh]=i.MAX;const rt={[Xh]:i.ZERO,[qh]:i.ONE,[Yh]:i.SRC_COLOR,[Pa]:i.SRC_ALPHA,[Qh]:i.SRC_ALPHA_SATURATE,[Jh]:i.DST_COLOR,[Kh]:i.DST_ALPHA,[$h]:i.ONE_MINUS_SRC_COLOR,[Da]:i.ONE_MINUS_SRC_ALPHA,[jh]:i.ONE_MINUS_DST_COLOR,[Zh]:i.ONE_MINUS_DST_ALPHA,[tu]:i.CONSTANT_COLOR,[eu]:i.ONE_MINUS_CONSTANT_COLOR,[nu]:i.CONSTANT_ALPHA,[iu]:i.ONE_MINUS_CONSTANT_ALPHA};function lt(F,pt,nt,xt,Et,ot,Ct,wt,me,ue){if(F===In){m===!0&&(q(i.BLEND),m=!1);return}if(m===!1&&(U(i.BLEND),m=!0),F!==kh){if(F!==f||ue!==N){if((E!==di||b!==di)&&(i.blendEquation(i.FUNC_ADD),E=di,b=di),ue)switch(F){case Gi:i.blendFuncSeparate(i.ONE,i.ONE_MINUS_SRC_ALPHA,i.ONE,i.ONE_MINUS_SRC_ALPHA);break;case al:i.blendFunc(i.ONE,i.ONE);break;case ol:i.blendFuncSeparate(i.ZERO,i.ONE_MINUS_SRC_COLOR,i.ZERO,i.ONE);break;case ll:i.blendFuncSeparate(i.DST_COLOR,i.ONE_MINUS_SRC_ALPHA,i.ZERO,i.ONE);break;default:ee("WebGLState: Invalid blending: ",F);break}else switch(F){case Gi:i.blendFuncSeparate(i.SRC_ALPHA,i.ONE_MINUS_SRC_ALPHA,i.ONE,i.ONE_MINUS_SRC_ALPHA);break;case al:i.blendFuncSeparate(i.SRC_ALPHA,i.ONE,i.ONE,i.ONE);break;case ol:ee("WebGLState: SubtractiveBlending requires material.premultipliedAlpha = true");break;case ll:ee("WebGLState: MultiplyBlending requires material.premultipliedAlpha = true");break;default:ee("WebGLState: Invalid blending: ",F);break}w=null,y=null,M=null,A=null,x.set(0,0,0),T=0,f=F,N=ue}return}Et=Et||pt,ot=ot||nt,Ct=Ct||xt,(pt!==E||Et!==b)&&(i.blendEquationSeparate(st[pt],st[Et]),E=pt,b=Et),(nt!==w||xt!==y||ot!==M||Ct!==A)&&(i.blendFuncSeparate(rt[nt],rt[xt],rt[ot],rt[Ct]),w=nt,y=xt,M=ot,A=Ct),(wt.equals(x)===!1||me!==T)&&(i.blendColor(wt.r,wt.g,wt.b,me),x.copy(wt),T=me),f=F,N=!1}function ft(F,pt){F.side===Xe?q(i.CULL_FACE):U(i.CULL_FACE);let nt=F.side===ke;pt&&(nt=!nt),ct(nt),F.blending===Gi&&F.transparent===!1?lt(In):lt(F.blending,F.blendEquation,F.blendSrc,F.blendDst,F.blendEquationAlpha,F.blendSrcAlpha,F.blendDstAlpha,F.blendColor,F.blendAlpha,F.premultipliedAlpha),a.setFunc(F.depthFunc),a.setTest(F.depthTest),a.setMask(F.depthWrite),r.setMask(F.colorWrite);const xt=F.stencilWrite;o.setTest(xt),xt&&(o.setMask(F.stencilWriteMask),o.setFunc(F.stencilFunc,F.stencilRef,F.stencilFuncMask),o.setOp(F.stencilFail,F.stencilZFail,F.stencilZPass)),Jt(F.polygonOffset,F.polygonOffsetFactor,F.polygonOffsetUnits),F.alphaToCoverage===!0?U(i.SAMPLE_ALPHA_TO_COVERAGE):q(i.SAMPLE_ALPHA_TO_COVERAGE)}function ct(F){D!==F&&(F?i.frontFace(i.CW):i.frontFace(i.CCW),D=F)}function te(F){F!==Oh?(U(i.CULL_FACE),F!==B&&(F===rl?i.cullFace(i.BACK):F===Bh?i.cullFace(i.FRONT):i.cullFace(i.FRONT_AND_BACK))):q(i.CULL_FACE),B=F}function Gt(F){F!==$&&(W&&i.lineWidth(F),$=F)}function Jt(F,pt,nt){F?(U(i.POLYGON_OFFSET_FILL),(J!==pt||z!==nt)&&(J=pt,z=nt,a.getReversed()&&(pt=-pt),i.polygonOffset(pt,nt))):q(i.POLYGON_OFFSET_FILL)}function Kt(F){F?U(i.SCISSOR_TEST):q(i.SCISSOR_TEST)}function It(F){F===void 0&&(F=i.TEXTURE0+H-1),ht!==F&&(i.activeTexture(F),ht=F)}function L(F,pt,nt){nt===void 0&&(ht===null?nt=i.TEXTURE0+H-1:nt=ht);let xt=ut[nt];xt===void 0&&(xt={type:void 0,texture:void 0},ut[nt]=xt),(xt.type!==F||xt.texture!==pt)&&(ht!==nt&&(i.activeTexture(nt),ht=nt),i.bindTexture(F,pt||P[F]),xt.type=F,xt.texture=pt)}function ne(){const F=ut[ht];F!==void 0&&F.type!==void 0&&(i.bindTexture(F.type,null),F.type=void 0,F.texture=void 0)}function kt(){try{i.compressedTexImage2D(...arguments)}catch(F){ee("WebGLState:",F)}}function R(){try{i.compressedTexImage3D(...arguments)}catch(F){ee("WebGLState:",F)}}function _(){try{i.texSubImage2D(...arguments)}catch(F){ee("WebGLState:",F)}}function k(){try{i.texSubImage3D(...arguments)}catch(F){ee("WebGLState:",F)}}function G(){try{i.compressedTexSubImage2D(...arguments)}catch(F){ee("WebGLState:",F)}}function j(){try{i.compressedTexSubImage3D(...arguments)}catch(F){ee("WebGLState:",F)}}function dt(){try{i.texStorage2D(...arguments)}catch(F){ee("WebGLState:",F)}}function mt(){try{i.texStorage3D(...arguments)}catch(F){ee("WebGLState:",F)}}function et(){try{i.texImage2D(...arguments)}catch(F){ee("WebGLState:",F)}}function it(){try{i.texImage3D(...arguments)}catch(F){ee("WebGLState:",F)}}function gt(F){return d[F]!==void 0?d[F]:i.getParameter(F)}function Pt(F,pt){d[F]!==pt&&(i.pixelStorei(F,pt),d[F]=pt)}function vt(F){Yt.equals(F)===!1&&(i.scissor(F.x,F.y,F.z,F.w),Yt.copy(F))}function _t(F){Vt.equals(F)===!1&&(i.viewport(F.x,F.y,F.z,F.w),Vt.copy(F))}function Nt(F,pt){let nt=l.get(pt);nt===void 0&&(nt=new WeakMap,l.set(pt,nt));let xt=nt.get(F);xt===void 0&&(xt=i.getUniformBlockIndex(pt,F.name),nt.set(F,xt))}function Ot(F,pt){const xt=l.get(pt).get(F);c.get(pt)!==xt&&(i.uniformBlockBinding(pt,xt,F.__bindingPointIndex),c.set(pt,xt))}function Wt(){i.disable(i.BLEND),i.disable(i.CULL_FACE),i.disable(i.DEPTH_TEST),i.disable(i.POLYGON_OFFSET_FILL),i.disable(i.SCISSOR_TEST),i.disable(i.STENCIL_TEST),i.disable(i.SAMPLE_ALPHA_TO_COVERAGE),i.blendEquation(i.FUNC_ADD),i.blendFunc(i.ONE,i.ZERO),i.blendFuncSeparate(i.ONE,i.ZERO,i.ONE,i.ZERO),i.blendColor(0,0,0,0),i.colorMask(!0,!0,!0,!0),i.clearColor(0,0,0,0),i.depthMask(!0),i.depthFunc(i.LESS),a.setReversed(!1),i.clearDepth(1),i.stencilMask(4294967295),i.stencilFunc(i.ALWAYS,0,4294967295),i.stencilOp(i.KEEP,i.KEEP,i.KEEP),i.clearStencil(0),i.cullFace(i.BACK),i.frontFace(i.CCW),i.polygonOffset(0,0),i.activeTexture(i.TEXTURE0),i.bindFramebuffer(i.FRAMEBUFFER,null),i.bindFramebuffer(i.DRAW_FRAMEBUFFER,null),i.bindFramebuffer(i.READ_FRAMEBUFFER,null),i.useProgram(null),i.lineWidth(1),i.scissor(0,0,i.canvas.width,i.canvas.height),i.viewport(0,0,i.canvas.width,i.canvas.height),i.pixelStorei(i.PACK_ALIGNMENT,4),i.pixelStorei(i.UNPACK_ALIGNMENT,4),i.pixelStorei(i.UNPACK_FLIP_Y_WEBGL,!1),i.pixelStorei(i.UNPACK_PREMULTIPLY_ALPHA_WEBGL,!1),i.pixelStorei(i.UNPACK_COLORSPACE_CONVERSION_WEBGL,i.BROWSER_DEFAULT_WEBGL),i.pixelStorei(i.PACK_ROW_LENGTH,0),i.pixelStorei(i.PACK_SKIP_PIXELS,0),i.pixelStorei(i.PACK_SKIP_ROWS,0),i.pixelStorei(i.UNPACK_ROW_LENGTH,0),i.pixelStorei(i.UNPACK_IMAGE_HEIGHT,0),i.pixelStorei(i.UNPACK_SKIP_PIXELS,0),i.pixelStorei(i.UNPACK_SKIP_ROWS,0),i.pixelStorei(i.UNPACK_SKIP_IMAGES,0),h={},d={},ht=null,ut={},u={},p=new WeakMap,g=[],v=null,m=!1,f=null,E=null,w=null,y=null,b=null,M=null,A=null,x=new Ft(0,0,0),T=0,N=!1,D=null,B=null,$=null,J=null,z=null,Yt.set(0,0,i.canvas.width,i.canvas.height),Vt.set(0,0,i.canvas.width,i.canvas.height),r.reset(),a.reset(),o.reset()}return{buffers:{color:r,depth:a,stencil:o},enable:U,disable:q,bindFramebuffer:Z,drawBuffers:V,useProgram:at,setBlending:lt,setMaterial:ft,setFlipSided:ct,setCullFace:te,setLineWidth:Gt,setPolygonOffset:Jt,setScissorTest:Kt,activeTexture:It,bindTexture:L,unbindTexture:ne,compressedTexImage2D:kt,compressedTexImage3D:R,texImage2D:et,texImage3D:it,pixelStorei:Pt,getParameter:gt,updateUBOMapping:Nt,uniformBlockBinding:Ot,texStorage2D:dt,texStorage3D:mt,texSubImage2D:_,texSubImage3D:k,compressedTexSubImage2D:G,compressedTexSubImage3D:j,scissor:vt,viewport:_t,reset:Wt}}function $g(i,t,e,n,s,r,a){const o=t.has("WEBGL_multisampled_render_to_texture")?t.get("WEBGL_multisampled_render_to_texture"):null,c=typeof navigator>"u"?!1:/OculusBrowser/g.test(navigator.userAgent),l=new zt,h=new WeakMap,d=new Set;let u;const p=new WeakMap;let g=!1;try{g=typeof OffscreenCanvas<"u"&&new OffscreenCanvas(1,1).getContext("2d")!==null}catch{}function v(R,_){return g?new OffscreenCanvas(R,_):ys("canvas")}function m(R,_,k){let G=1;const j=kt(R);if((j.width>k||j.height>k)&&(G=k/Math.max(j.width,j.height)),G<1)if(typeof HTMLImageElement<"u"&&R instanceof HTMLImageElement||typeof HTMLCanvasElement<"u"&&R instanceof HTMLCanvasElement||typeof ImageBitmap<"u"&&R instanceof ImageBitmap||typeof VideoFrame<"u"&&R instanceof VideoFrame){const dt=Math.floor(G*j.width),mt=Math.floor(G*j.height);u===void 0&&(u=v(dt,mt));const et=_?v(dt,mt):u;return et.width=dt,et.height=mt,et.getContext("2d").drawImage(R,0,0,dt,mt),Ut("WebGLRenderer: Texture has been resized from ("+j.width+"x"+j.height+") to ("+dt+"x"+mt+")."),et}else return"data"in R&&Ut("WebGLRenderer: Image in DataTexture is too big ("+j.width+"x"+j.height+")."),R;return R}function f(R){return R.generateMipmaps}function E(R){i.generateMipmap(R)}function w(R){return R.isWebGLCubeRenderTarget?i.TEXTURE_CUBE_MAP:R.isWebGL3DRenderTarget?i.TEXTURE_3D:R.isWebGLArrayRenderTarget||R.isCompressedArrayTexture?i.TEXTURE_2D_ARRAY:i.TEXTURE_2D}function y(R,_,k,G,j,dt=!1){if(R!==null){if(i[R]!==void 0)return i[R];Ut("WebGLRenderer: Attempt to use non-existing WebGL internal format '"+R+"'")}let mt;G&&(mt=t.get("EXT_texture_norm16"),mt||Ut("WebGLRenderer: Unable to use normalized textures without EXT_texture_norm16 extension"));let et=_;if(_===i.RED&&(k===i.FLOAT&&(et=i.R32F),k===i.HALF_FLOAT&&(et=i.R16F),k===i.UNSIGNED_BYTE&&(et=i.R8),k===i.UNSIGNED_SHORT&&mt&&(et=mt.R16_EXT),k===i.SHORT&&mt&&(et=mt.R16_SNORM_EXT)),_===i.RED_INTEGER&&(k===i.UNSIGNED_BYTE&&(et=i.R8UI),k===i.UNSIGNED_SHORT&&(et=i.R16UI),k===i.UNSIGNED_INT&&(et=i.R32UI),k===i.BYTE&&(et=i.R8I),k===i.SHORT&&(et=i.R16I),k===i.INT&&(et=i.R32I)),_===i.RG&&(k===i.FLOAT&&(et=i.RG32F),k===i.HALF_FLOAT&&(et=i.RG16F),k===i.UNSIGNED_BYTE&&(et=i.RG8),k===i.UNSIGNED_SHORT&&mt&&(et=mt.RG16_EXT),k===i.SHORT&&mt&&(et=mt.RG16_SNORM_EXT)),_===i.RG_INTEGER&&(k===i.UNSIGNED_BYTE&&(et=i.RG8UI),k===i.UNSIGNED_SHORT&&(et=i.RG16UI),k===i.UNSIGNED_INT&&(et=i.RG32UI),k===i.BYTE&&(et=i.RG8I),k===i.SHORT&&(et=i.RG16I),k===i.INT&&(et=i.RG32I)),_===i.RGB_INTEGER&&(k===i.UNSIGNED_BYTE&&(et=i.RGB8UI),k===i.UNSIGNED_SHORT&&(et=i.RGB16UI),k===i.UNSIGNED_INT&&(et=i.RGB32UI),k===i.BYTE&&(et=i.RGB8I),k===i.SHORT&&(et=i.RGB16I),k===i.INT&&(et=i.RGB32I)),_===i.RGBA_INTEGER&&(k===i.UNSIGNED_BYTE&&(et=i.RGBA8UI),k===i.UNSIGNED_SHORT&&(et=i.RGBA16UI),k===i.UNSIGNED_INT&&(et=i.RGBA32UI),k===i.BYTE&&(et=i.RGBA8I),k===i.SHORT&&(et=i.RGBA16I),k===i.INT&&(et=i.RGBA32I)),_===i.RGB&&(k===i.UNSIGNED_SHORT&&mt&&(et=mt.RGB16_EXT),k===i.SHORT&&mt&&(et=mt.RGB16_SNORM_EXT),k===i.UNSIGNED_INT_5_9_9_9_REV&&(et=i.RGB9_E5),k===i.UNSIGNED_INT_10F_11F_11F_REV&&(et=i.R11F_G11F_B10F)),_===i.RGBA){const it=dt?br:Qt.getTransfer(j);k===i.FLOAT&&(et=i.RGBA32F),k===i.HALF_FLOAT&&(et=i.RGBA16F),k===i.UNSIGNED_BYTE&&(et=it===re?i.SRGB8_ALPHA8:i.RGBA8),k===i.UNSIGNED_SHORT&&mt&&(et=mt.RGBA16_EXT),k===i.SHORT&&mt&&(et=mt.RGBA16_SNORM_EXT),k===i.UNSIGNED_SHORT_4_4_4_4&&(et=i.RGBA4),k===i.UNSIGNED_SHORT_5_5_5_1&&(et=i.RGB5_A1)}return(et===i.R16F||et===i.R32F||et===i.RG16F||et===i.RG32F||et===i.RGBA16F||et===i.RGBA32F)&&t.get("EXT_color_buffer_float"),et}function b(R,_){let k;return R?_===null||_===yn||_===Ms?k=i.DEPTH24_STENCIL8:_===cn?k=i.DEPTH32F_STENCIL8:_===vs&&(k=i.DEPTH24_STENCIL8,Ut("DepthTexture: 16 bit depth attachment is not supported with stencil. Using 24-bit attachment.")):_===null||_===yn||_===Ms?k=i.DEPTH_COMPONENT24:_===cn?k=i.DEPTH_COMPONENT32F:_===vs&&(k=i.DEPTH_COMPONENT16),k}function M(R,_){return f(R)===!0||R.isFramebufferTexture&&R.minFilter!==Ee&&R.minFilter!==ye?Math.log2(Math.max(_.width,_.height))+1:R.mipmaps!==void 0&&R.mipmaps.length>0?R.mipmaps.length:R.isCompressedTexture&&Array.isArray(R.image)?_.mipmaps.length:1}function A(R){const _=R.target;_.removeEventListener("dispose",A),T(_),_.isVideoTexture&&h.delete(_),_.isHTMLTexture&&d.delete(_)}function x(R){const _=R.target;_.removeEventListener("dispose",x),D(_)}function T(R){const _=n.get(R);if(_.__webglInit===void 0)return;const k=R.source,G=p.get(k);if(G){const j=G[_.__cacheKey];j.usedTimes--,j.usedTimes===0&&N(R),Object.keys(G).length===0&&p.delete(k)}n.remove(R)}function N(R){const _=n.get(R);i.deleteTexture(_.__webglTexture);const k=R.source,G=p.get(k);delete G[_.__cacheKey],a.memory.textures--}function D(R){const _=n.get(R);if(R.depthTexture&&(R.depthTexture.dispose(),n.remove(R.depthTexture)),R.isWebGLCubeRenderTarget)for(let G=0;G<6;G++){if(Array.isArray(_.__webglFramebuffer[G]))for(let j=0;j<_.__webglFramebuffer[G].length;j++)i.deleteFramebuffer(_.__webglFramebuffer[G][j]);else i.deleteFramebuffer(_.__webglFramebuffer[G]);_.__webglDepthbuffer&&i.deleteRenderbuffer(_.__webglDepthbuffer[G])}else{if(Array.isArray(_.__webglFramebuffer))for(let G=0;G<_.__webglFramebuffer.length;G++)i.deleteFramebuffer(_.__webglFramebuffer[G]);else i.deleteFramebuffer(_.__webglFramebuffer);if(_.__webglDepthbuffer&&i.deleteRenderbuffer(_.__webglDepthbuffer),_.__webglMultisampledFramebuffer&&i.deleteFramebuffer(_.__webglMultisampledFramebuffer),_.__webglColorRenderbuffer)for(let G=0;G<_.__webglColorRenderbuffer.length;G++)_.__webglColorRenderbuffer[G]&&i.deleteRenderbuffer(_.__webglColorRenderbuffer[G]);_.__webglDepthRenderbuffer&&i.deleteRenderbuffer(_.__webglDepthRenderbuffer)}const k=R.textures;for(let G=0,j=k.length;G<j;G++){const dt=n.get(k[G]);dt.__webglTexture&&(i.deleteTexture(dt.__webglTexture),a.memory.textures--),n.remove(k[G])}n.remove(R)}let B=0;function $(){B=0}function J(){return B}function z(R){B=R}function H(){const R=B;return R>=s.maxTextures&&Ut("WebGLTextures: Trying to use "+R+" texture units while this GPU supports only "+s.maxTextures),B+=1,R}function W(R){const _=[];return _.push(R.wrapS),_.push(R.wrapT),_.push(R.wrapR||0),_.push(R.magFilter),_.push(R.minFilter),_.push(R.anisotropy),_.push(R.internalFormat),_.push(R.format),_.push(R.type),_.push(R.generateMipmaps),_.push(R.premultiplyAlpha),_.push(R.flipY),_.push(R.unpackAlignment),_.push(R.colorSpace),_.join()}function tt(R,_){const k=n.get(R);if(R.isVideoTexture&&L(R),R.isRenderTargetTexture===!1&&R.isExternalTexture!==!0&&R.version>0&&k.__version!==R.version){const G=R.image;if(G===null)Ut("WebGLRenderer: Texture marked for update but no image data found.");else if(G.complete===!1)Ut("WebGLRenderer: Texture marked for update but image is incomplete");else{q(k,R,_);return}}else R.isExternalTexture&&(k.__webglTexture=R.sourceTexture?R.sourceTexture:null);e.bindTexture(i.TEXTURE_2D,k.__webglTexture,i.TEXTURE0+_)}function Q(R,_){const k=n.get(R);if(R.isRenderTargetTexture===!1&&R.version>0&&k.__version!==R.version){q(k,R,_);return}else R.isExternalTexture&&(k.__webglTexture=R.sourceTexture?R.sourceTexture:null);e.bindTexture(i.TEXTURE_2D_ARRAY,k.__webglTexture,i.TEXTURE0+_)}function ht(R,_){const k=n.get(R);if(R.isRenderTargetTexture===!1&&R.version>0&&k.__version!==R.version){q(k,R,_);return}e.bindTexture(i.TEXTURE_3D,k.__webglTexture,i.TEXTURE0+_)}function ut(R,_){const k=n.get(R);if(R.isCubeDepthTexture!==!0&&R.version>0&&k.__version!==R.version){Z(k,R,_);return}e.bindTexture(i.TEXTURE_CUBE_MAP,k.__webglTexture,i.TEXTURE0+_)}const St={[pi]:i.REPEAT,[ln]:i.CLAMP_TO_EDGE,[xs]:i.MIRRORED_REPEAT},Bt={[Ee]:i.NEAREST,[au]:i.NEAREST_MIPMAP_NEAREST,[Ds]:i.NEAREST_MIPMAP_LINEAR,[ye]:i.LINEAR,[Br]:i.LINEAR_MIPMAP_NEAREST,[Ln]:i.LINEAR_MIPMAP_LINEAR},Yt={[cu]:i.NEVER,[pu]:i.ALWAYS,[hu]:i.LESS,[Do]:i.LEQUAL,[uu]:i.EQUAL,[Lo]:i.GEQUAL,[du]:i.GREATER,[fu]:i.NOTEQUAL};function Vt(R,_){if(_.type===cn&&t.has("OES_texture_float_linear")===!1&&(_.magFilter===ye||_.magFilter===Br||_.magFilter===Ds||_.magFilter===Ln||_.minFilter===ye||_.minFilter===Br||_.minFilter===Ds||_.minFilter===Ln)&&Ut("WebGLRenderer: Unable to use linear filtering with floating point textures. OES_texture_float_linear not supported on this device."),i.texParameteri(R,i.TEXTURE_WRAP_S,St[_.wrapS]),i.texParameteri(R,i.TEXTURE_WRAP_T,St[_.wrapT]),(R===i.TEXTURE_3D||R===i.TEXTURE_2D_ARRAY)&&i.texParameteri(R,i.TEXTURE_WRAP_R,St[_.wrapR]),i.texParameteri(R,i.TEXTURE_MAG_FILTER,Bt[_.magFilter]),i.texParameteri(R,i.TEXTURE_MIN_FILTER,Bt[_.minFilter]),_.compareFunction&&(i.texParameteri(R,i.TEXTURE_COMPARE_MODE,i.COMPARE_REF_TO_TEXTURE),i.texParameteri(R,i.TEXTURE_COMPARE_FUNC,Yt[_.compareFunction])),t.has("EXT_texture_filter_anisotropic")===!0){if(_.magFilter===Ee||_.minFilter!==Ds&&_.minFilter!==Ln||_.type===cn&&t.has("OES_texture_float_linear")===!1)return;if(_.anisotropy>1||n.get(_).__currentAnisotropy){const k=t.get("EXT_texture_filter_anisotropic");i.texParameterf(R,k.TEXTURE_MAX_ANISOTROPY_EXT,Math.min(_.anisotropy,s.getMaxAnisotropy())),n.get(_).__currentAnisotropy=_.anisotropy}}}function C(R,_){let k=!1;R.__webglInit===void 0&&(R.__webglInit=!0,_.addEventListener("dispose",A));const G=_.source;let j=p.get(G);j===void 0&&(j={},p.set(G,j));const dt=W(_);if(dt!==R.__cacheKey){j[dt]===void 0&&(j[dt]={texture:i.createTexture(),usedTimes:0},a.memory.textures++,k=!0),j[dt].usedTimes++;const mt=j[R.__cacheKey];mt!==void 0&&(j[R.__cacheKey].usedTimes--,mt.usedTimes===0&&N(_)),R.__cacheKey=dt,R.__webglTexture=j[dt].texture}return k}function P(R,_,k){return Math.floor(Math.floor(R/k)/_)}function U(R,_,k,G){const dt=R.updateRanges;if(dt.length===0)e.texSubImage2D(i.TEXTURE_2D,0,0,0,_.width,_.height,k,G,_.data);else{dt.sort((Pt,vt)=>Pt.start-vt.start);let mt=0;for(let Pt=1;Pt<dt.length;Pt++){const vt=dt[mt],_t=dt[Pt],Nt=vt.start+vt.count,Ot=P(_t.start,_.width,4),Wt=P(vt.start,_.width,4);_t.start<=Nt+1&&Ot===Wt&&P(_t.start+_t.count-1,_.width,4)===Ot?vt.count=Math.max(vt.count,_t.start+_t.count-vt.start):(++mt,dt[mt]=_t)}dt.length=mt+1;const et=e.getParameter(i.UNPACK_ROW_LENGTH),it=e.getParameter(i.UNPACK_SKIP_PIXELS),gt=e.getParameter(i.UNPACK_SKIP_ROWS);e.pixelStorei(i.UNPACK_ROW_LENGTH,_.width);for(let Pt=0,vt=dt.length;Pt<vt;Pt++){const _t=dt[Pt],Nt=Math.floor(_t.start/4),Ot=Math.ceil(_t.count/4),Wt=Nt%_.width,F=Math.floor(Nt/_.width),pt=Ot,nt=1;e.pixelStorei(i.UNPACK_SKIP_PIXELS,Wt),e.pixelStorei(i.UNPACK_SKIP_ROWS,F),e.texSubImage2D(i.TEXTURE_2D,0,Wt,F,pt,nt,k,G,_.data)}R.clearUpdateRanges(),e.pixelStorei(i.UNPACK_ROW_LENGTH,et),e.pixelStorei(i.UNPACK_SKIP_PIXELS,it),e.pixelStorei(i.UNPACK_SKIP_ROWS,gt)}}function q(R,_,k){let G=i.TEXTURE_2D;(_.isDataArrayTexture||_.isCompressedArrayTexture)&&(G=i.TEXTURE_2D_ARRAY),_.isData3DTexture&&(G=i.TEXTURE_3D);const j=C(R,_),dt=_.source;e.bindTexture(G,R.__webglTexture,i.TEXTURE0+k);const mt=n.get(dt);if(dt.version!==mt.__version||j===!0){if(e.activeTexture(i.TEXTURE0+k),(typeof ImageBitmap<"u"&&_.image instanceof ImageBitmap)===!1){const nt=Qt.getPrimaries(Qt.workingColorSpace),xt=_.colorSpace===ti?null:Qt.getPrimaries(_.colorSpace),Et=_.colorSpace===ti||nt===xt?i.NONE:i.BROWSER_DEFAULT_WEBGL;e.pixelStorei(i.UNPACK_FLIP_Y_WEBGL,_.flipY),e.pixelStorei(i.UNPACK_PREMULTIPLY_ALPHA_WEBGL,_.premultiplyAlpha),e.pixelStorei(i.UNPACK_COLORSPACE_CONVERSION_WEBGL,Et)}e.pixelStorei(i.UNPACK_ALIGNMENT,_.unpackAlignment);let it=m(_.image,!1,s.maxTextureSize);it=ne(_,it);const gt=r.convert(_.format,_.colorSpace),Pt=r.convert(_.type);let vt=y(_.internalFormat,gt,Pt,_.normalized,_.colorSpace,_.isVideoTexture);Vt(G,_);let _t;const Nt=_.mipmaps,Ot=_.isVideoTexture!==!0,Wt=mt.__version===void 0||j===!0,F=dt.dataReady,pt=M(_,it);if(_.isDepthTexture)vt=b(_.format===mi,_.type),Wt&&(Ot?e.texStorage2D(i.TEXTURE_2D,1,vt,it.width,it.height):e.texImage2D(i.TEXTURE_2D,0,vt,it.width,it.height,0,gt,Pt,null));else if(_.isDataTexture)if(Nt.length>0){Ot&&Wt&&e.texStorage2D(i.TEXTURE_2D,pt,vt,Nt[0].width,Nt[0].height);for(let nt=0,xt=Nt.length;nt<xt;nt++)_t=Nt[nt],Ot?F&&e.texSubImage2D(i.TEXTURE_2D,nt,0,0,_t.width,_t.height,gt,Pt,_t.data):e.texImage2D(i.TEXTURE_2D,nt,vt,_t.width,_t.height,0,gt,Pt,_t.data);_.generateMipmaps=!1}else Ot?(Wt&&e.texStorage2D(i.TEXTURE_2D,pt,vt,it.width,it.height),F&&U(_,it,gt,Pt)):e.texImage2D(i.TEXTURE_2D,0,vt,it.width,it.height,0,gt,Pt,it.data);else if(_.isCompressedTexture)if(_.isCompressedArrayTexture){Ot&&Wt&&e.texStorage3D(i.TEXTURE_2D_ARRAY,pt,vt,Nt[0].width,Nt[0].height,it.depth);for(let nt=0,xt=Nt.length;nt<xt;nt++)if(_t=Nt[nt],_.format!==hn)if(gt!==null)if(Ot){if(F)if(_.layerUpdates.size>0){const Et=$l(_t.width,_t.height,_.format,_.type);for(const ot of _.layerUpdates){const Ct=_t.data.subarray(ot*Et/_t.data.BYTES_PER_ELEMENT,(ot+1)*Et/_t.data.BYTES_PER_ELEMENT);e.compressedTexSubImage3D(i.TEXTURE_2D_ARRAY,nt,0,0,ot,_t.width,_t.height,1,gt,Ct)}_.clearLayerUpdates()}else e.compressedTexSubImage3D(i.TEXTURE_2D_ARRAY,nt,0,0,0,_t.width,_t.height,it.depth,gt,_t.data)}else e.compressedTexImage3D(i.TEXTURE_2D_ARRAY,nt,vt,_t.width,_t.height,it.depth,0,_t.data,0,0);else Ut("WebGLRenderer: Attempt to load unsupported compressed texture format in .uploadTexture()");else Ot?F&&e.texSubImage3D(i.TEXTURE_2D_ARRAY,nt,0,0,0,_t.width,_t.height,it.depth,gt,Pt,_t.data):e.texImage3D(i.TEXTURE_2D_ARRAY,nt,vt,_t.width,_t.height,it.depth,0,gt,Pt,_t.data)}else{Ot&&Wt&&e.texStorage2D(i.TEXTURE_2D,pt,vt,Nt[0].width,Nt[0].height);for(let nt=0,xt=Nt.length;nt<xt;nt++)_t=Nt[nt],_.format!==hn?gt!==null?Ot?F&&e.compressedTexSubImage2D(i.TEXTURE_2D,nt,0,0,_t.width,_t.height,gt,_t.data):e.compressedTexImage2D(i.TEXTURE_2D,nt,vt,_t.width,_t.height,0,_t.data):Ut("WebGLRenderer: Attempt to load unsupported compressed texture format in .uploadTexture()"):Ot?F&&e.texSubImage2D(i.TEXTURE_2D,nt,0,0,_t.width,_t.height,gt,Pt,_t.data):e.texImage2D(i.TEXTURE_2D,nt,vt,_t.width,_t.height,0,gt,Pt,_t.data)}else if(_.isDataArrayTexture)if(Ot){if(Wt&&e.texStorage3D(i.TEXTURE_2D_ARRAY,pt,vt,it.width,it.height,it.depth),F)if(_.layerUpdates.size>0){const nt=$l(it.width,it.height,_.format,_.type);for(const xt of _.layerUpdates){const Et=it.data.subarray(xt*nt/it.data.BYTES_PER_ELEMENT,(xt+1)*nt/it.data.BYTES_PER_ELEMENT);e.texSubImage3D(i.TEXTURE_2D_ARRAY,0,0,0,xt,it.width,it.height,1,gt,Pt,Et)}_.clearLayerUpdates()}else e.texSubImage3D(i.TEXTURE_2D_ARRAY,0,0,0,0,it.width,it.height,it.depth,gt,Pt,it.data)}else e.texImage3D(i.TEXTURE_2D_ARRAY,0,vt,it.width,it.height,it.depth,0,gt,Pt,it.data);else if(_.isData3DTexture)Ot?(Wt&&e.texStorage3D(i.TEXTURE_3D,pt,vt,it.width,it.height,it.depth),F&&e.texSubImage3D(i.TEXTURE_3D,0,0,0,0,it.width,it.height,it.depth,gt,Pt,it.data)):e.texImage3D(i.TEXTURE_3D,0,vt,it.width,it.height,it.depth,0,gt,Pt,it.data);else if(_.isFramebufferTexture){if(Wt)if(Ot)e.texStorage2D(i.TEXTURE_2D,pt,vt,it.width,it.height);else{let nt=it.width,xt=it.height;for(let Et=0;Et<pt;Et++)e.texImage2D(i.TEXTURE_2D,Et,vt,nt,xt,0,gt,Pt,null),nt>>=1,xt>>=1}}else if(_.isHTMLTexture){if("texElementImage2D"in i){const nt=i.canvas;if(nt.hasAttribute("layoutsubtree")||nt.setAttribute("layoutsubtree","true"),it.parentNode!==nt){nt.appendChild(it),d.add(_),nt.onpaint=xt=>{const Et=xt.changedElements;for(const ot of d)Et.includes(ot.image)&&(ot.needsUpdate=!0)},nt.requestPaint();return}if(i.texElementImage2D.length===3)i.texElementImage2D(i.TEXTURE_2D,i.RGBA8,it);else{const Et=i.RGBA,ot=i.RGBA,Ct=i.UNSIGNED_BYTE;i.texElementImage2D(i.TEXTURE_2D,0,Et,ot,Ct,it)}i.texParameteri(i.TEXTURE_2D,i.TEXTURE_MIN_FILTER,i.LINEAR),i.texParameteri(i.TEXTURE_2D,i.TEXTURE_WRAP_S,i.CLAMP_TO_EDGE),i.texParameteri(i.TEXTURE_2D,i.TEXTURE_WRAP_T,i.CLAMP_TO_EDGE)}}else if(Nt.length>0){if(Ot&&Wt){const nt=kt(Nt[0]);e.texStorage2D(i.TEXTURE_2D,pt,vt,nt.width,nt.height)}for(let nt=0,xt=Nt.length;nt<xt;nt++)_t=Nt[nt],Ot?F&&e.texSubImage2D(i.TEXTURE_2D,nt,0,0,gt,Pt,_t):e.texImage2D(i.TEXTURE_2D,nt,vt,gt,Pt,_t);_.generateMipmaps=!1}else if(Ot){if(Wt){const nt=kt(it);e.texStorage2D(i.TEXTURE_2D,pt,vt,nt.width,nt.height)}F&&e.texSubImage2D(i.TEXTURE_2D,0,0,0,gt,Pt,it)}else e.texImage2D(i.TEXTURE_2D,0,vt,gt,Pt,it);f(_)&&E(G),mt.__version=dt.version,_.onUpdate&&_.onUpdate(_)}R.__version=_.version}function Z(R,_,k){if(_.image.length!==6)return;const G=C(R,_),j=_.source;e.bindTexture(i.TEXTURE_CUBE_MAP,R.__webglTexture,i.TEXTURE0+k);const dt=n.get(j);if(j.version!==dt.__version||G===!0){e.activeTexture(i.TEXTURE0+k);const mt=Qt.getPrimaries(Qt.workingColorSpace),et=_.colorSpace===ti?null:Qt.getPrimaries(_.colorSpace),it=_.colorSpace===ti||mt===et?i.NONE:i.BROWSER_DEFAULT_WEBGL;e.pixelStorei(i.UNPACK_FLIP_Y_WEBGL,_.flipY),e.pixelStorei(i.UNPACK_PREMULTIPLY_ALPHA_WEBGL,_.premultiplyAlpha),e.pixelStorei(i.UNPACK_ALIGNMENT,_.unpackAlignment),e.pixelStorei(i.UNPACK_COLORSPACE_CONVERSION_WEBGL,it);const gt=_.isCompressedTexture||_.image[0].isCompressedTexture,Pt=_.image[0]&&_.image[0].isDataTexture,vt=[];for(let ot=0;ot<6;ot++)!gt&&!Pt?vt[ot]=m(_.image[ot],!0,s.maxCubemapSize):vt[ot]=Pt?_.image[ot].image:_.image[ot],vt[ot]=ne(_,vt[ot]);const _t=vt[0],Nt=r.convert(_.format,_.colorSpace),Ot=r.convert(_.type),Wt=y(_.internalFormat,Nt,Ot,_.normalized,_.colorSpace),F=_.isVideoTexture!==!0,pt=dt.__version===void 0||G===!0,nt=j.dataReady;let xt=M(_,_t);Vt(i.TEXTURE_CUBE_MAP,_);let Et;if(gt){F&&pt&&e.texStorage2D(i.TEXTURE_CUBE_MAP,xt,Wt,_t.width,_t.height);for(let ot=0;ot<6;ot++){Et=vt[ot].mipmaps;for(let Ct=0;Ct<Et.length;Ct++){const wt=Et[Ct];_.format!==hn?Nt!==null?F?nt&&e.compressedTexSubImage2D(i.TEXTURE_CUBE_MAP_POSITIVE_X+ot,Ct,0,0,wt.width,wt.height,Nt,wt.data):e.compressedTexImage2D(i.TEXTURE_CUBE_MAP_POSITIVE_X+ot,Ct,Wt,wt.width,wt.height,0,wt.data):Ut("WebGLRenderer: Attempt to load unsupported compressed texture format in .setTextureCube()"):F?nt&&e.texSubImage2D(i.TEXTURE_CUBE_MAP_POSITIVE_X+ot,Ct,0,0,wt.width,wt.height,Nt,Ot,wt.data):e.texImage2D(i.TEXTURE_CUBE_MAP_POSITIVE_X+ot,Ct,Wt,wt.width,wt.height,0,Nt,Ot,wt.data)}}}else{if(Et=_.mipmaps,F&&pt){Et.length>0&&xt++;const ot=kt(vt[0]);e.texStorage2D(i.TEXTURE_CUBE_MAP,xt,Wt,ot.width,ot.height)}for(let ot=0;ot<6;ot++)if(Pt){F?nt&&e.texSubImage2D(i.TEXTURE_CUBE_MAP_POSITIVE_X+ot,0,0,0,vt[ot].width,vt[ot].height,Nt,Ot,vt[ot].data):e.texImage2D(i.TEXTURE_CUBE_MAP_POSITIVE_X+ot,0,Wt,vt[ot].width,vt[ot].height,0,Nt,Ot,vt[ot].data);for(let Ct=0;Ct<Et.length;Ct++){const me=Et[Ct].image[ot].image;F?nt&&e.texSubImage2D(i.TEXTURE_CUBE_MAP_POSITIVE_X+ot,Ct+1,0,0,me.width,me.height,Nt,Ot,me.data):e.texImage2D(i.TEXTURE_CUBE_MAP_POSITIVE_X+ot,Ct+1,Wt,me.width,me.height,0,Nt,Ot,me.data)}}else{F?nt&&e.texSubImage2D(i.TEXTURE_CUBE_MAP_POSITIVE_X+ot,0,0,0,Nt,Ot,vt[ot]):e.texImage2D(i.TEXTURE_CUBE_MAP_POSITIVE_X+ot,0,Wt,Nt,Ot,vt[ot]);for(let Ct=0;Ct<Et.length;Ct++){const wt=Et[Ct];F?nt&&e.texSubImage2D(i.TEXTURE_CUBE_MAP_POSITIVE_X+ot,Ct+1,0,0,Nt,Ot,wt.image[ot]):e.texImage2D(i.TEXTURE_CUBE_MAP_POSITIVE_X+ot,Ct+1,Wt,Nt,Ot,wt.image[ot])}}}f(_)&&E(i.TEXTURE_CUBE_MAP),dt.__version=j.version,_.onUpdate&&_.onUpdate(_)}R.__version=_.version}function V(R,_,k,G,j,dt){const mt=r.convert(k.format,k.colorSpace),et=r.convert(k.type),it=y(k.internalFormat,mt,et,k.normalized,k.colorSpace),gt=n.get(_),Pt=n.get(k);if(Pt.__renderTarget=_,!gt.__hasExternalTextures){const vt=Math.max(1,_.width>>dt),_t=Math.max(1,_.height>>dt);j===i.TEXTURE_3D||j===i.TEXTURE_2D_ARRAY?e.texImage3D(j,dt,it,vt,_t,_.depth,0,mt,et,null):e.texImage2D(j,dt,it,vt,_t,0,mt,et,null)}e.bindFramebuffer(i.FRAMEBUFFER,R),It(_)?o.framebufferTexture2DMultisampleEXT(i.FRAMEBUFFER,G,j,Pt.__webglTexture,0,Kt(_)):(j===i.TEXTURE_2D||j>=i.TEXTURE_CUBE_MAP_POSITIVE_X&&j<=i.TEXTURE_CUBE_MAP_NEGATIVE_Z)&&i.framebufferTexture2D(i.FRAMEBUFFER,G,j,Pt.__webglTexture,dt),e.bindFramebuffer(i.FRAMEBUFFER,null)}function at(R,_,k){if(i.bindRenderbuffer(i.RENDERBUFFER,R),_.depthBuffer){const G=_.depthTexture,j=G&&G.isDepthTexture?G.type:null,dt=b(_.stencilBuffer,j),mt=_.stencilBuffer?i.DEPTH_STENCIL_ATTACHMENT:i.DEPTH_ATTACHMENT;It(_)?o.renderbufferStorageMultisampleEXT(i.RENDERBUFFER,Kt(_),dt,_.width,_.height):k?i.renderbufferStorageMultisample(i.RENDERBUFFER,Kt(_),dt,_.width,_.height):i.renderbufferStorage(i.RENDERBUFFER,dt,_.width,_.height),i.framebufferRenderbuffer(i.FRAMEBUFFER,mt,i.RENDERBUFFER,R)}else{const G=_.textures;for(let j=0;j<G.length;j++){const dt=G[j],mt=r.convert(dt.format,dt.colorSpace),et=r.convert(dt.type),it=y(dt.internalFormat,mt,et,dt.normalized,dt.colorSpace);It(_)?o.renderbufferStorageMultisampleEXT(i.RENDERBUFFER,Kt(_),it,_.width,_.height):k?i.renderbufferStorageMultisample(i.RENDERBUFFER,Kt(_),it,_.width,_.height):i.renderbufferStorage(i.RENDERBUFFER,it,_.width,_.height)}}i.bindRenderbuffer(i.RENDERBUFFER,null)}function st(R,_,k){const G=_.isWebGLCubeRenderTarget===!0;if(e.bindFramebuffer(i.FRAMEBUFFER,R),!(_.depthTexture&&_.depthTexture.isDepthTexture))throw new Error("THREE.WebGLTextures: renderTarget.depthTexture must be an instance of THREE.DepthTexture.");const j=n.get(_.depthTexture);if(j.__renderTarget=_,(!j.__webglTexture||_.depthTexture.image.width!==_.width||_.depthTexture.image.height!==_.height)&&(_.depthTexture.image.width=_.width,_.depthTexture.image.height=_.height,_.depthTexture.needsUpdate=!0),G){if(j.__webglInit===void 0&&(j.__webglInit=!0,_.depthTexture.addEventListener("dispose",A)),j.__webglTexture===void 0){j.__webglTexture=i.createTexture(),e.bindTexture(i.TEXTURE_CUBE_MAP,j.__webglTexture),Vt(i.TEXTURE_CUBE_MAP,_.depthTexture);const gt=r.convert(_.depthTexture.format),Pt=r.convert(_.depthTexture.type);let vt;_.depthTexture.format===Fn?vt=i.DEPTH_COMPONENT24:_.depthTexture.format===mi&&(vt=i.DEPTH24_STENCIL8);for(let _t=0;_t<6;_t++)i.texImage2D(i.TEXTURE_CUBE_MAP_POSITIVE_X+_t,0,vt,_.width,_.height,0,gt,Pt,null)}}else tt(_.depthTexture,0);const dt=j.__webglTexture,mt=Kt(_),et=G?i.TEXTURE_CUBE_MAP_POSITIVE_X+k:i.TEXTURE_2D,it=_.depthTexture.format===mi?i.DEPTH_STENCIL_ATTACHMENT:i.DEPTH_ATTACHMENT;if(_.depthTexture.format===Fn)It(_)?o.framebufferTexture2DMultisampleEXT(i.FRAMEBUFFER,it,et,dt,0,mt):i.framebufferTexture2D(i.FRAMEBUFFER,it,et,dt,0);else if(_.depthTexture.format===mi)It(_)?o.framebufferTexture2DMultisampleEXT(i.FRAMEBUFFER,it,et,dt,0,mt):i.framebufferTexture2D(i.FRAMEBUFFER,it,et,dt,0);else throw new Error("THREE.WebGLTextures: Unknown depthTexture format.")}function rt(R){const _=n.get(R),k=R.isWebGLCubeRenderTarget===!0;if(_.__boundDepthTexture!==R.depthTexture){const G=R.depthTexture;if(_.__depthDisposeCallback&&_.__depthDisposeCallback(),G){const j=()=>{delete _.__boundDepthTexture,delete _.__depthDisposeCallback,G.removeEventListener("dispose",j)};G.addEventListener("dispose",j),_.__depthDisposeCallback=j}_.__boundDepthTexture=G}if(R.depthTexture&&!_.__autoAllocateDepthBuffer)if(k)for(let G=0;G<6;G++)st(_.__webglFramebuffer[G],R,G);else{const G=R.texture.mipmaps;G&&G.length>0?st(_.__webglFramebuffer[0],R,0):st(_.__webglFramebuffer,R,0)}else if(k){_.__webglDepthbuffer=[];for(let G=0;G<6;G++)if(e.bindFramebuffer(i.FRAMEBUFFER,_.__webglFramebuffer[G]),_.__webglDepthbuffer[G]===void 0)_.__webglDepthbuffer[G]=i.createRenderbuffer(),at(_.__webglDepthbuffer[G],R,!1);else{const j=R.stencilBuffer?i.DEPTH_STENCIL_ATTACHMENT:i.DEPTH_ATTACHMENT,dt=_.__webglDepthbuffer[G];i.bindRenderbuffer(i.RENDERBUFFER,dt),i.framebufferRenderbuffer(i.FRAMEBUFFER,j,i.RENDERBUFFER,dt)}}else{const G=R.texture.mipmaps;if(G&&G.length>0?e.bindFramebuffer(i.FRAMEBUFFER,_.__webglFramebuffer[0]):e.bindFramebuffer(i.FRAMEBUFFER,_.__webglFramebuffer),_.__webglDepthbuffer===void 0)_.__webglDepthbuffer=i.createRenderbuffer(),at(_.__webglDepthbuffer,R,!1);else{const j=R.stencilBuffer?i.DEPTH_STENCIL_ATTACHMENT:i.DEPTH_ATTACHMENT,dt=_.__webglDepthbuffer;i.bindRenderbuffer(i.RENDERBUFFER,dt),i.framebufferRenderbuffer(i.FRAMEBUFFER,j,i.RENDERBUFFER,dt)}}e.bindFramebuffer(i.FRAMEBUFFER,null)}function lt(R,_,k){const G=n.get(R);_!==void 0&&V(G.__webglFramebuffer,R,R.texture,i.COLOR_ATTACHMENT0,i.TEXTURE_2D,0),k!==void 0&&rt(R)}function ft(R){const _=R.texture,k=n.get(R),G=n.get(_);R.addEventListener("dispose",x);const j=R.textures,dt=R.isWebGLCubeRenderTarget===!0,mt=j.length>1;if(mt||(G.__webglTexture===void 0&&(G.__webglTexture=i.createTexture()),G.__version=_.version,a.memory.textures++),dt){k.__webglFramebuffer=[];for(let et=0;et<6;et++)if(_.mipmaps&&_.mipmaps.length>0){k.__webglFramebuffer[et]=[];for(let it=0;it<_.mipmaps.length;it++)k.__webglFramebuffer[et][it]=i.createFramebuffer()}else k.__webglFramebuffer[et]=i.createFramebuffer()}else{if(_.mipmaps&&_.mipmaps.length>0){k.__webglFramebuffer=[];for(let et=0;et<_.mipmaps.length;et++)k.__webglFramebuffer[et]=i.createFramebuffer()}else k.__webglFramebuffer=i.createFramebuffer();if(mt)for(let et=0,it=j.length;et<it;et++){const gt=n.get(j[et]);gt.__webglTexture===void 0&&(gt.__webglTexture=i.createTexture(),a.memory.textures++)}if(R.samples>0&&It(R)===!1){k.__webglMultisampledFramebuffer=i.createFramebuffer(),k.__webglColorRenderbuffer=[],e.bindFramebuffer(i.FRAMEBUFFER,k.__webglMultisampledFramebuffer);for(let et=0;et<j.length;et++){const it=j[et];k.__webglColorRenderbuffer[et]=i.createRenderbuffer(),i.bindRenderbuffer(i.RENDERBUFFER,k.__webglColorRenderbuffer[et]);const gt=r.convert(it.format,it.colorSpace),Pt=r.convert(it.type),vt=y(it.internalFormat,gt,Pt,it.normalized,it.colorSpace,R.isXRRenderTarget===!0),_t=Kt(R);i.renderbufferStorageMultisample(i.RENDERBUFFER,_t,vt,R.width,R.height),i.framebufferRenderbuffer(i.FRAMEBUFFER,i.COLOR_ATTACHMENT0+et,i.RENDERBUFFER,k.__webglColorRenderbuffer[et])}i.bindRenderbuffer(i.RENDERBUFFER,null),R.depthBuffer&&(k.__webglDepthRenderbuffer=i.createRenderbuffer(),at(k.__webglDepthRenderbuffer,R,!0)),e.bindFramebuffer(i.FRAMEBUFFER,null)}}if(dt){e.bindTexture(i.TEXTURE_CUBE_MAP,G.__webglTexture),Vt(i.TEXTURE_CUBE_MAP,_);for(let et=0;et<6;et++)if(_.mipmaps&&_.mipmaps.length>0)for(let it=0;it<_.mipmaps.length;it++)V(k.__webglFramebuffer[et][it],R,_,i.COLOR_ATTACHMENT0,i.TEXTURE_CUBE_MAP_POSITIVE_X+et,it);else V(k.__webglFramebuffer[et],R,_,i.COLOR_ATTACHMENT0,i.TEXTURE_CUBE_MAP_POSITIVE_X+et,0);f(_)&&E(i.TEXTURE_CUBE_MAP),e.unbindTexture()}else if(mt){for(let et=0,it=j.length;et<it;et++){const gt=j[et],Pt=n.get(gt);let vt=i.TEXTURE_2D;(R.isWebGL3DRenderTarget||R.isWebGLArrayRenderTarget)&&(vt=R.isWebGL3DRenderTarget?i.TEXTURE_3D:i.TEXTURE_2D_ARRAY),e.bindTexture(vt,Pt.__webglTexture),Vt(vt,gt),V(k.__webglFramebuffer,R,gt,i.COLOR_ATTACHMENT0+et,vt,0),f(gt)&&E(vt)}e.unbindTexture()}else{let et=i.TEXTURE_2D;if((R.isWebGL3DRenderTarget||R.isWebGLArrayRenderTarget)&&(et=R.isWebGL3DRenderTarget?i.TEXTURE_3D:i.TEXTURE_2D_ARRAY),e.bindTexture(et,G.__webglTexture),Vt(et,_),_.mipmaps&&_.mipmaps.length>0)for(let it=0;it<_.mipmaps.length;it++)V(k.__webglFramebuffer[it],R,_,i.COLOR_ATTACHMENT0,et,it);else V(k.__webglFramebuffer,R,_,i.COLOR_ATTACHMENT0,et,0);f(_)&&E(et),e.unbindTexture()}R.depthBuffer&&rt(R)}function ct(R){const _=R.textures;for(let k=0,G=_.length;k<G;k++){const j=_[k];if(f(j)){const dt=w(R),mt=n.get(j).__webglTexture;e.bindTexture(dt,mt),E(dt),e.unbindTexture()}}}const te=[],Gt=[];function Jt(R){if(R.samples>0){if(It(R)===!1){const _=R.textures,k=R.width,G=R.height;let j=i.COLOR_BUFFER_BIT;const dt=R.stencilBuffer?i.DEPTH_STENCIL_ATTACHMENT:i.DEPTH_ATTACHMENT,mt=n.get(R),et=_.length>1;if(et)for(let gt=0;gt<_.length;gt++)e.bindFramebuffer(i.FRAMEBUFFER,mt.__webglMultisampledFramebuffer),i.framebufferRenderbuffer(i.FRAMEBUFFER,i.COLOR_ATTACHMENT0+gt,i.RENDERBUFFER,null),e.bindFramebuffer(i.FRAMEBUFFER,mt.__webglFramebuffer),i.framebufferTexture2D(i.DRAW_FRAMEBUFFER,i.COLOR_ATTACHMENT0+gt,i.TEXTURE_2D,null,0);e.bindFramebuffer(i.READ_FRAMEBUFFER,mt.__webglMultisampledFramebuffer);const it=R.texture.mipmaps;it&&it.length>0?e.bindFramebuffer(i.DRAW_FRAMEBUFFER,mt.__webglFramebuffer[0]):e.bindFramebuffer(i.DRAW_FRAMEBUFFER,mt.__webglFramebuffer);for(let gt=0;gt<_.length;gt++){if(R.resolveDepthBuffer&&(R.depthBuffer&&(j|=i.DEPTH_BUFFER_BIT),R.stencilBuffer&&R.resolveStencilBuffer&&(j|=i.STENCIL_BUFFER_BIT)),et){i.framebufferRenderbuffer(i.READ_FRAMEBUFFER,i.COLOR_ATTACHMENT0,i.RENDERBUFFER,mt.__webglColorRenderbuffer[gt]);const Pt=n.get(_[gt]).__webglTexture;i.framebufferTexture2D(i.DRAW_FRAMEBUFFER,i.COLOR_ATTACHMENT0,i.TEXTURE_2D,Pt,0)}i.blitFramebuffer(0,0,k,G,0,0,k,G,j,i.NEAREST),c===!0&&(te.length=0,Gt.length=0,te.push(i.COLOR_ATTACHMENT0+gt),R.depthBuffer&&R.resolveDepthBuffer===!1&&(te.push(dt),Gt.push(dt),i.invalidateFramebuffer(i.DRAW_FRAMEBUFFER,Gt)),i.invalidateFramebuffer(i.READ_FRAMEBUFFER,te))}if(e.bindFramebuffer(i.READ_FRAMEBUFFER,null),e.bindFramebuffer(i.DRAW_FRAMEBUFFER,null),et)for(let gt=0;gt<_.length;gt++){e.bindFramebuffer(i.FRAMEBUFFER,mt.__webglMultisampledFramebuffer),i.framebufferRenderbuffer(i.FRAMEBUFFER,i.COLOR_ATTACHMENT0+gt,i.RENDERBUFFER,mt.__webglColorRenderbuffer[gt]);const Pt=n.get(_[gt]).__webglTexture;e.bindFramebuffer(i.FRAMEBUFFER,mt.__webglFramebuffer),i.framebufferTexture2D(i.DRAW_FRAMEBUFFER,i.COLOR_ATTACHMENT0+gt,i.TEXTURE_2D,Pt,0)}e.bindFramebuffer(i.DRAW_FRAMEBUFFER,mt.__webglMultisampledFramebuffer)}else if(R.depthBuffer&&R.resolveDepthBuffer===!1&&c){const _=R.stencilBuffer?i.DEPTH_STENCIL_ATTACHMENT:i.DEPTH_ATTACHMENT;i.invalidateFramebuffer(i.DRAW_FRAMEBUFFER,[_])}}}function Kt(R){return Math.min(s.maxSamples,R.samples)}function It(R){const _=n.get(R);return R.samples>0&&t.has("WEBGL_multisampled_render_to_texture")===!0&&_.__useRenderToTexture!==!1}function L(R){const _=a.render.frame;h.get(R)!==_&&(h.set(R,_),R.update())}function ne(R,_){const k=R.colorSpace,G=R.format,j=R.type;return R.isCompressedTexture===!0||R.isVideoTexture===!0||k!==yr&&k!==ti&&(Qt.getTransfer(k)===re?(G!==hn||j!==qe)&&Ut("WebGLTextures: sRGB encoded textures have to use RGBAFormat and UnsignedByteType."):ee("WebGLTextures: Unsupported texture color space:",k)),_}function kt(R){return typeof HTMLImageElement<"u"&&R instanceof HTMLImageElement?(l.width=R.naturalWidth||R.width,l.height=R.naturalHeight||R.height):typeof VideoFrame<"u"&&R instanceof VideoFrame?(l.width=R.displayWidth,l.height=R.displayHeight):(l.width=R.width,l.height=R.height),l}this.allocateTextureUnit=H,this.resetTextureUnits=$,this.getTextureUnits=J,this.setTextureUnits=z,this.setTexture2D=tt,this.setTexture2DArray=Q,this.setTexture3D=ht,this.setTextureCube=ut,this.rebindTextures=lt,this.setupRenderTarget=ft,this.updateRenderTargetMipmap=ct,this.updateMultisampleRenderTarget=Jt,this.setupDepthRenderbuffer=rt,this.setupFrameBufferTexture=V,this.useMultisampledRTT=It,this.isReversedDepthBuffer=function(){return e.buffers.depth.getReversed()}}function Kg(i,t){function e(n,s=ti){let r;const a=Qt.getTransfer(s);if(n===qe)return i.UNSIGNED_BYTE;if(n===To)return i.UNSIGNED_SHORT_4_4_4_4;if(n===Ao)return i.UNSIGNED_SHORT_5_5_5_1;if(n===kc)return i.UNSIGNED_INT_5_9_9_9_REV;if(n===Vc)return i.UNSIGNED_INT_10F_11F_11F_REV;if(n===Bc)return i.BYTE;if(n===zc)return i.SHORT;if(n===vs)return i.UNSIGNED_SHORT;if(n===Eo)return i.INT;if(n===yn)return i.UNSIGNED_INT;if(n===cn)return i.FLOAT;if(n===Un)return i.HALF_FLOAT;if(n===Gc)return i.ALPHA;if(n===Hc)return i.RGB;if(n===hn)return i.RGBA;if(n===Fn)return i.DEPTH_COMPONENT;if(n===mi)return i.DEPTH_STENCIL;if(n===wo)return i.RED;if(n===Ro)return i.RED_INTEGER;if(n===xi)return i.RG;if(n===Co)return i.RG_INTEGER;if(n===Po)return i.RGBA_INTEGER;if(n===fr||n===pr||n===mr||n===gr)if(a===re)if(r=t.get("WEBGL_compressed_texture_s3tc_srgb"),r!==null){if(n===fr)return r.COMPRESSED_SRGB_S3TC_DXT1_EXT;if(n===pr)return r.COMPRESSED_SRGB_ALPHA_S3TC_DXT1_EXT;if(n===mr)return r.COMPRESSED_SRGB_ALPHA_S3TC_DXT3_EXT;if(n===gr)return r.COMPRESSED_SRGB_ALPHA_S3TC_DXT5_EXT}else return null;else if(r=t.get("WEBGL_compressed_texture_s3tc"),r!==null){if(n===fr)return r.COMPRESSED_RGB_S3TC_DXT1_EXT;if(n===pr)return r.COMPRESSED_RGBA_S3TC_DXT1_EXT;if(n===mr)return r.COMPRESSED_RGBA_S3TC_DXT3_EXT;if(n===gr)return r.COMPRESSED_RGBA_S3TC_DXT5_EXT}else return null;if(n===za||n===ka||n===Va||n===Ga)if(r=t.get("WEBGL_compressed_texture_pvrtc"),r!==null){if(n===za)return r.COMPRESSED_RGB_PVRTC_4BPPV1_IMG;if(n===ka)return r.COMPRESSED_RGB_PVRTC_2BPPV1_IMG;if(n===Va)return r.COMPRESSED_RGBA_PVRTC_4BPPV1_IMG;if(n===Ga)return r.COMPRESSED_RGBA_PVRTC_2BPPV1_IMG}else return null;if(n===Ha||n===Wa||n===Xa||n===qa||n===Ya||n===vr||n===$a)if(r=t.get("WEBGL_compressed_texture_etc"),r!==null){if(n===Ha||n===Wa)return a===re?r.COMPRESSED_SRGB8_ETC2:r.COMPRESSED_RGB8_ETC2;if(n===Xa)return a===re?r.COMPRESSED_SRGB8_ALPHA8_ETC2_EAC:r.COMPRESSED_RGBA8_ETC2_EAC;if(n===qa)return r.COMPRESSED_R11_EAC;if(n===Ya)return r.COMPRESSED_SIGNED_R11_EAC;if(n===vr)return r.COMPRESSED_RG11_EAC;if(n===$a)return r.COMPRESSED_SIGNED_RG11_EAC}else return null;if(n===Ka||n===Za||n===Ja||n===ja||n===Qa||n===to||n===eo||n===no||n===io||n===so||n===ro||n===ao||n===oo||n===lo)if(r=t.get("WEBGL_compressed_texture_astc"),r!==null){if(n===Ka)return a===re?r.COMPRESSED_SRGB8_ALPHA8_ASTC_4x4_KHR:r.COMPRESSED_RGBA_ASTC_4x4_KHR;if(n===Za)return a===re?r.COMPRESSED_SRGB8_ALPHA8_ASTC_5x4_KHR:r.COMPRESSED_RGBA_ASTC_5x4_KHR;if(n===Ja)return a===re?r.COMPRESSED_SRGB8_ALPHA8_ASTC_5x5_KHR:r.COMPRESSED_RGBA_ASTC_5x5_KHR;if(n===ja)return a===re?r.COMPRESSED_SRGB8_ALPHA8_ASTC_6x5_KHR:r.COMPRESSED_RGBA_ASTC_6x5_KHR;if(n===Qa)return a===re?r.COMPRESSED_SRGB8_ALPHA8_ASTC_6x6_KHR:r.COMPRESSED_RGBA_ASTC_6x6_KHR;if(n===to)return a===re?r.COMPRESSED_SRGB8_ALPHA8_ASTC_8x5_KHR:r.COMPRESSED_RGBA_ASTC_8x5_KHR;if(n===eo)return a===re?r.COMPRESSED_SRGB8_ALPHA8_ASTC_8x6_KHR:r.COMPRESSED_RGBA_ASTC_8x6_KHR;if(n===no)return a===re?r.COMPRESSED_SRGB8_ALPHA8_ASTC_8x8_KHR:r.COMPRESSED_RGBA_ASTC_8x8_KHR;if(n===io)return a===re?r.COMPRESSED_SRGB8_ALPHA8_ASTC_10x5_KHR:r.COMPRESSED_RGBA_ASTC_10x5_KHR;if(n===so)return a===re?r.COMPRESSED_SRGB8_ALPHA8_ASTC_10x6_KHR:r.COMPRESSED_RGBA_ASTC_10x6_KHR;if(n===ro)return a===re?r.COMPRESSED_SRGB8_ALPHA8_ASTC_10x8_KHR:r.COMPRESSED_RGBA_ASTC_10x8_KHR;if(n===ao)return a===re?r.COMPRESSED_SRGB8_ALPHA8_ASTC_10x10_KHR:r.COMPRESSED_RGBA_ASTC_10x10_KHR;if(n===oo)return a===re?r.COMPRESSED_SRGB8_ALPHA8_ASTC_12x10_KHR:r.COMPRESSED_RGBA_ASTC_12x10_KHR;if(n===lo)return a===re?r.COMPRESSED_SRGB8_ALPHA8_ASTC_12x12_KHR:r.COMPRESSED_RGBA_ASTC_12x12_KHR}else return null;if(n===co||n===ho||n===uo)if(r=t.get("EXT_texture_compression_bptc"),r!==null){if(n===co)return a===re?r.COMPRESSED_SRGB_ALPHA_BPTC_UNORM_EXT:r.COMPRESSED_RGBA_BPTC_UNORM_EXT;if(n===ho)return r.COMPRESSED_RGB_BPTC_SIGNED_FLOAT_EXT;if(n===uo)return r.COMPRESSED_RGB_BPTC_UNSIGNED_FLOAT_EXT}else return null;if(n===fo||n===po||n===Mr||n===mo)if(r=t.get("EXT_texture_compression_rgtc"),r!==null){if(n===fo)return r.COMPRESSED_RED_RGTC1_EXT;if(n===po)return r.COMPRESSED_SIGNED_RED_RGTC1_EXT;if(n===Mr)return r.COMPRESSED_RED_GREEN_RGTC2_EXT;if(n===mo)return r.COMPRESSED_SIGNED_RED_GREEN_RGTC2_EXT}else return null;return n===Ms?i.UNSIGNED_INT_24_8:i[n]!==void 0?i[n]:null}return{convert:e}}const Zg=`
void main() {

	gl_Position = vec4( position, 1.0 );

}`,Jg=`
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

}`;class jg{constructor(){this.texture=null,this.mesh=null,this.depthNear=0,this.depthFar=0}init(t,e){if(this.texture===null){const n=new Qc(t.texture);(t.depthNear!==e.depthNear||t.depthFar!==e.depthFar)&&(this.depthNear=t.depthNear,this.depthFar=t.depthFar),this.texture=n}}getMesh(t){if(this.texture!==null&&this.mesh===null){const e=t.cameras[0].viewport,n=new En({vertexShader:Zg,fragmentShader:Jg,uniforms:{depthColor:{value:this.texture},depthWidth:{value:e.z},depthHeight:{value:e.w}}});this.mesh=new Me(new ws(20,20),n)}return this.mesh}reset(){this.texture=null,this.mesh=null}getDepthTexture(){return this.texture}}class Qg extends si{constructor(t,e){super();const n=this;let s=null,r=1,a=null,o="local-floor",c=1,l=null,h=null,d=null,u=null,p=null,g=null;const v=typeof XRWebGLBinding<"u",m=new jg,f={},E=e.getContextAttributes();let w=null,y=null;const b=[],M=[],A=new zt;let x=null;const T=new We;T.viewport=new pe;const N=new We;N.viewport=new pe;const D=[T,N],B=new ad;let $=null,J=null;this.cameraAutoUpdate=!0,this.enabled=!1,this.isPresenting=!1,this.getController=function(C){let P=b[C];return P===void 0&&(P=new Xr,b[C]=P),P.getTargetRaySpace()},this.getControllerGrip=function(C){let P=b[C];return P===void 0&&(P=new Xr,b[C]=P),P.getGripSpace()},this.getHand=function(C){let P=b[C];return P===void 0&&(P=new Xr,b[C]=P),P.getHandSpace()};function z(C){const P=M.indexOf(C.inputSource);if(P===-1)return;const U=b[P];U!==void 0&&(U.update(C.inputSource,C.frame,l||a),U.dispatchEvent({type:C.type,data:C.inputSource}))}function H(){s.removeEventListener("select",z),s.removeEventListener("selectstart",z),s.removeEventListener("selectend",z),s.removeEventListener("squeeze",z),s.removeEventListener("squeezestart",z),s.removeEventListener("squeezeend",z),s.removeEventListener("end",H),s.removeEventListener("inputsourceschange",W);for(let C=0;C<b.length;C++){const P=M[C];P!==null&&(M[C]=null,b[C].disconnect(P))}$=null,J=null,m.reset();for(const C in f)delete f[C];t.setRenderTarget(w),p=null,u=null,d=null,s=null,y=null,Vt.stop(),n.isPresenting=!1,t.setPixelRatio(x),t.setSize(A.width,A.height,!1),n.dispatchEvent({type:"sessionend"})}this.setFramebufferScaleFactor=function(C){r=C,n.isPresenting===!0&&Ut("WebXRManager: Cannot change framebuffer scale while presenting.")},this.setReferenceSpaceType=function(C){o=C,n.isPresenting===!0&&Ut("WebXRManager: Cannot change reference space type while presenting.")},this.getReferenceSpace=function(){return l||a},this.setReferenceSpace=function(C){l=C},this.getBaseLayer=function(){return u!==null?u:p},this.getBinding=function(){return d===null&&v&&(d=new XRWebGLBinding(s,e)),d},this.getFrame=function(){return g},this.getSession=function(){return s},this.setSession=async function(C){if(s=C,s!==null){if(w=t.getRenderTarget(),s.addEventListener("select",z),s.addEventListener("selectstart",z),s.addEventListener("selectend",z),s.addEventListener("squeeze",z),s.addEventListener("squeezestart",z),s.addEventListener("squeezeend",z),s.addEventListener("end",H),s.addEventListener("inputsourceschange",W),E.xrCompatible!==!0&&await e.makeXRCompatible(),x=t.getPixelRatio(),t.getSize(A),v&&"createProjectionLayer"in XRWebGLBinding.prototype){let U=null,q=null,Z=null;E.depth&&(Z=E.stencil?e.DEPTH24_STENCIL8:e.DEPTH_COMPONENT24,U=E.stencil?mi:Fn,q=E.stencil?Ms:yn);const V={colorFormat:e.RGBA8,depthFormat:Z,scaleFactor:r};d=this.getBinding(),u=d.createProjectionLayer(V),s.updateRenderState({layers:[u]}),t.setPixelRatio(1),t.setSize(u.textureWidth,u.textureHeight,!1),y=new Sn(u.textureWidth,u.textureHeight,{format:hn,type:qe,depthTexture:new Yi(u.textureWidth,u.textureHeight,q,void 0,void 0,void 0,void 0,void 0,void 0,U),stencilBuffer:E.stencil,colorSpace:t.outputColorSpace,samples:E.antialias?4:0,resolveDepthBuffer:u.ignoreDepthValues===!1,resolveStencilBuffer:u.ignoreDepthValues===!1})}else{const U={antialias:E.antialias,alpha:!0,depth:E.depth,stencil:E.stencil,framebufferScaleFactor:r};p=new XRWebGLLayer(s,e,U),s.updateRenderState({baseLayer:p}),t.setPixelRatio(1),t.setSize(p.framebufferWidth,p.framebufferHeight,!1),y=new Sn(p.framebufferWidth,p.framebufferHeight,{format:hn,type:qe,colorSpace:t.outputColorSpace,stencilBuffer:E.stencil,resolveDepthBuffer:p.ignoreDepthValues===!1,resolveStencilBuffer:p.ignoreDepthValues===!1})}y.isXRRenderTarget=!0,this.setFoveation(c),l=null,a=await s.requestReferenceSpace(o),Vt.setContext(s),Vt.start(),n.isPresenting=!0,n.dispatchEvent({type:"sessionstart"})}},this.getEnvironmentBlendMode=function(){if(s!==null)return s.environmentBlendMode},this.getDepthTexture=function(){return m.getDepthTexture()};function W(C){for(let P=0;P<C.removed.length;P++){const U=C.removed[P],q=M.indexOf(U);q>=0&&(M[q]=null,b[q].disconnect(U))}for(let P=0;P<C.added.length;P++){const U=C.added[P];let q=M.indexOf(U);if(q===-1){for(let V=0;V<b.length;V++)if(V>=M.length){M.push(U),q=V;break}else if(M[V]===null){M[V]=U,q=V;break}if(q===-1)break}const Z=b[q];Z&&Z.connect(U)}}const tt=new I,Q=new I;function ht(C,P,U){tt.setFromMatrixPosition(P.matrixWorld),Q.setFromMatrixPosition(U.matrixWorld);const q=tt.distanceTo(Q),Z=P.projectionMatrix.elements,V=U.projectionMatrix.elements,at=Z[14]/(Z[10]-1),st=Z[14]/(Z[10]+1),rt=(Z[9]+1)/Z[5],lt=(Z[9]-1)/Z[5],ft=(Z[8]-1)/Z[0],ct=(V[8]+1)/V[0],te=at*ft,Gt=at*ct,Jt=q/(-ft+ct),Kt=Jt*-ft;if(P.matrixWorld.decompose(C.position,C.quaternion,C.scale),C.translateX(Kt),C.translateZ(Jt),C.matrixWorld.compose(C.position,C.quaternion,C.scale),C.matrixWorldInverse.copy(C.matrixWorld).invert(),Z[10]===-1)C.projectionMatrix.copy(P.projectionMatrix),C.projectionMatrixInverse.copy(P.projectionMatrixInverse);else{const It=at+Jt,L=st+Jt,ne=te-Kt,kt=Gt+(q-Kt),R=rt*st/L*It,_=lt*st/L*It;C.projectionMatrix.makePerspective(ne,kt,R,_,It,L),C.projectionMatrixInverse.copy(C.projectionMatrix).invert()}}function ut(C,P){P===null?C.matrixWorld.copy(C.matrix):C.matrixWorld.multiplyMatrices(P.matrixWorld,C.matrix),C.matrixWorldInverse.copy(C.matrixWorld).invert()}this.updateCamera=function(C){if(s===null)return;let P=C.near,U=C.far;m.texture!==null&&(m.depthNear>0&&(P=m.depthNear),m.depthFar>0&&(U=m.depthFar)),B.near=N.near=T.near=P,B.far=N.far=T.far=U,($!==B.near||J!==B.far)&&(s.updateRenderState({depthNear:B.near,depthFar:B.far}),$=B.near,J=B.far),B.layers.mask=C.layers.mask|6,T.layers.mask=B.layers.mask&-5,N.layers.mask=B.layers.mask&-3;const q=C.parent,Z=B.cameras;ut(B,q);for(let V=0;V<Z.length;V++)ut(Z[V],q);Z.length===2?ht(B,T,N):B.projectionMatrix.copy(T.projectionMatrix),St(C,B,q)};function St(C,P,U){U===null?C.matrix.copy(P.matrixWorld):(C.matrix.copy(U.matrixWorld),C.matrix.invert(),C.matrix.multiply(P.matrixWorld)),C.matrix.decompose(C.position,C.quaternion,C.scale),C.updateMatrixWorld(!0),C.projectionMatrix.copy(P.projectionMatrix),C.projectionMatrixInverse.copy(P.projectionMatrixInverse),C.isPerspectiveCamera&&(C.fov=go*2*Math.atan(1/C.projectionMatrix.elements[5]),C.zoom=1)}this.getCamera=function(){return B},this.getFoveation=function(){if(!(u===null&&p===null))return c},this.setFoveation=function(C){c=C,u!==null&&(u.fixedFoveation=C),p!==null&&p.fixedFoveation!==void 0&&(p.fixedFoveation=C)},this.hasDepthSensing=function(){return m.texture!==null},this.getDepthSensingMesh=function(){return m.getMesh(B)},this.getCameraTexture=function(C){return f[C]};let Bt=null;function Yt(C,P){if(h=P.getViewerPose(l||a),g=P,h!==null){const U=h.views;p!==null&&(t.setRenderTargetFramebuffer(y,p.framebuffer),t.setRenderTarget(y));let q=!1;U.length!==B.cameras.length&&(B.cameras.length=0,q=!0);for(let st=0;st<U.length;st++){const rt=U[st];let lt=null;if(p!==null)lt=p.getViewport(rt);else{const ct=d.getViewSubImage(u,rt);lt=ct.viewport,st===0&&(t.setRenderTargetTextures(y,ct.colorTexture,ct.depthStencilTexture),t.setRenderTarget(y))}let ft=D[st];ft===void 0&&(ft=new We,ft.layers.enable(st),ft.viewport=new pe,D[st]=ft),ft.matrix.fromArray(rt.transform.matrix),ft.matrix.decompose(ft.position,ft.quaternion,ft.scale),ft.projectionMatrix.fromArray(rt.projectionMatrix),ft.projectionMatrixInverse.copy(ft.projectionMatrix).invert(),ft.viewport.set(lt.x,lt.y,lt.width,lt.height),st===0&&(B.matrix.copy(ft.matrix),B.matrix.decompose(B.position,B.quaternion,B.scale)),q===!0&&B.cameras.push(ft)}const Z=s.enabledFeatures;if(Z&&Z.includes("depth-sensing")&&s.depthUsage=="gpu-optimized"&&v){d=n.getBinding();const st=d.getDepthInformation(U[0]);st&&st.isValid&&st.texture&&m.init(st,s.renderState)}if(Z&&Z.includes("camera-access")&&v){t.state.unbindTexture(),d=n.getBinding();for(let st=0;st<U.length;st++){const rt=U[st].camera;if(rt){let lt=f[rt];lt||(lt=new Qc,f[rt]=lt);const ft=d.getCameraImage(rt);lt.sourceTexture=ft}}}}for(let U=0;U<b.length;U++){const q=M[U],Z=b[U];q!==null&&Z!==void 0&&Z.update(q,P,l||a)}Bt&&Bt(C,P),P.detectedPlanes&&n.dispatchEvent({type:"planesdetected",data:P}),g=null}const Vt=new rh;Vt.setAnimationLoop(Yt),this.setAnimationLoop=function(C){Bt=C},this.dispose=function(){}}}const t_=new se,dh=new Ht;dh.set(-1,0,0,0,1,0,0,0,1);function e_(i,t){function e(m,f){m.matrixAutoUpdate===!0&&m.updateMatrix(),f.value.copy(m.matrix)}function n(m,f){f.color.getRGB(m.fogColor.value,eh(i)),f.isFog?(m.fogNear.value=f.near,m.fogFar.value=f.far):f.isFogExp2&&(m.fogDensity.value=f.density)}function s(m,f,E,w,y){f.isNodeMaterial?f.uniformsNeedUpdate=!1:f.isMeshBasicMaterial?r(m,f):f.isMeshLambertMaterial?(r(m,f),f.envMap&&(m.envMapIntensity.value=f.envMapIntensity)):f.isMeshToonMaterial?(r(m,f),d(m,f)):f.isMeshPhongMaterial?(r(m,f),h(m,f),f.envMap&&(m.envMapIntensity.value=f.envMapIntensity)):f.isMeshStandardMaterial?(r(m,f),u(m,f),f.isMeshPhysicalMaterial&&p(m,f,y)):f.isMeshMatcapMaterial?(r(m,f),g(m,f)):f.isMeshDepthMaterial?r(m,f):f.isMeshDistanceMaterial?(r(m,f),v(m,f)):f.isMeshNormalMaterial?r(m,f):f.isLineBasicMaterial?(a(m,f),f.isLineDashedMaterial&&o(m,f)):f.isPointsMaterial?c(m,f,E,w):f.isSpriteMaterial?l(m,f):f.isShadowMaterial?(m.color.value.copy(f.color),m.opacity.value=f.opacity):f.isShaderMaterial&&(f.uniformsNeedUpdate=!1)}function r(m,f){m.opacity.value=f.opacity,f.color&&m.diffuse.value.copy(f.color),f.emissive&&m.emissive.value.copy(f.emissive).multiplyScalar(f.emissiveIntensity),f.map&&(m.map.value=f.map,e(f.map,m.mapTransform)),f.alphaMap&&(m.alphaMap.value=f.alphaMap,e(f.alphaMap,m.alphaMapTransform)),f.bumpMap&&(m.bumpMap.value=f.bumpMap,e(f.bumpMap,m.bumpMapTransform),m.bumpScale.value=f.bumpScale,f.side===ke&&(m.bumpScale.value*=-1)),f.normalMap&&(m.normalMap.value=f.normalMap,e(f.normalMap,m.normalMapTransform),m.normalScale.value.copy(f.normalScale),f.side===ke&&m.normalScale.value.negate()),f.displacementMap&&(m.displacementMap.value=f.displacementMap,e(f.displacementMap,m.displacementMapTransform),m.displacementScale.value=f.displacementScale,m.displacementBias.value=f.displacementBias),f.emissiveMap&&(m.emissiveMap.value=f.emissiveMap,e(f.emissiveMap,m.emissiveMapTransform)),f.specularMap&&(m.specularMap.value=f.specularMap,e(f.specularMap,m.specularMapTransform)),f.alphaTest>0&&(m.alphaTest.value=f.alphaTest);const E=t.get(f),w=E.envMap,y=E.envMapRotation;w&&(m.envMap.value=w,m.envMapRotation.value.setFromMatrix4(t_.makeRotationFromEuler(y)).transpose(),w.isCubeTexture&&w.isRenderTargetTexture===!1&&m.envMapRotation.value.premultiply(dh),m.reflectivity.value=f.reflectivity,m.ior.value=f.ior,m.refractionRatio.value=f.refractionRatio),f.lightMap&&(m.lightMap.value=f.lightMap,m.lightMapIntensity.value=f.lightMapIntensity,e(f.lightMap,m.lightMapTransform)),f.aoMap&&(m.aoMap.value=f.aoMap,m.aoMapIntensity.value=f.aoMapIntensity,e(f.aoMap,m.aoMapTransform))}function a(m,f){m.diffuse.value.copy(f.color),m.opacity.value=f.opacity,f.map&&(m.map.value=f.map,e(f.map,m.mapTransform))}function o(m,f){m.dashSize.value=f.dashSize,m.totalSize.value=f.dashSize+f.gapSize,m.scale.value=f.scale}function c(m,f,E,w){m.diffuse.value.copy(f.color),m.opacity.value=f.opacity,m.size.value=f.size*E,m.scale.value=w*.5,f.map&&(m.map.value=f.map,e(f.map,m.uvTransform)),f.alphaMap&&(m.alphaMap.value=f.alphaMap,e(f.alphaMap,m.alphaMapTransform)),f.alphaTest>0&&(m.alphaTest.value=f.alphaTest)}function l(m,f){m.diffuse.value.copy(f.color),m.opacity.value=f.opacity,m.rotation.value=f.rotation,f.map&&(m.map.value=f.map,e(f.map,m.mapTransform)),f.alphaMap&&(m.alphaMap.value=f.alphaMap,e(f.alphaMap,m.alphaMapTransform)),f.alphaTest>0&&(m.alphaTest.value=f.alphaTest)}function h(m,f){m.specular.value.copy(f.specular),m.shininess.value=Math.max(f.shininess,1e-4)}function d(m,f){f.gradientMap&&(m.gradientMap.value=f.gradientMap)}function u(m,f){m.metalness.value=f.metalness,f.metalnessMap&&(m.metalnessMap.value=f.metalnessMap,e(f.metalnessMap,m.metalnessMapTransform)),m.roughness.value=f.roughness,f.roughnessMap&&(m.roughnessMap.value=f.roughnessMap,e(f.roughnessMap,m.roughnessMapTransform)),f.envMap&&(m.envMapIntensity.value=f.envMapIntensity)}function p(m,f,E){m.ior.value=f.ior,f.sheen>0&&(m.sheenColor.value.copy(f.sheenColor).multiplyScalar(f.sheen),m.sheenRoughness.value=f.sheenRoughness,f.sheenColorMap&&(m.sheenColorMap.value=f.sheenColorMap,e(f.sheenColorMap,m.sheenColorMapTransform)),f.sheenRoughnessMap&&(m.sheenRoughnessMap.value=f.sheenRoughnessMap,e(f.sheenRoughnessMap,m.sheenRoughnessMapTransform))),f.clearcoat>0&&(m.clearcoat.value=f.clearcoat,m.clearcoatRoughness.value=f.clearcoatRoughness,f.clearcoatMap&&(m.clearcoatMap.value=f.clearcoatMap,e(f.clearcoatMap,m.clearcoatMapTransform)),f.clearcoatRoughnessMap&&(m.clearcoatRoughnessMap.value=f.clearcoatRoughnessMap,e(f.clearcoatRoughnessMap,m.clearcoatRoughnessMapTransform)),f.clearcoatNormalMap&&(m.clearcoatNormalMap.value=f.clearcoatNormalMap,e(f.clearcoatNormalMap,m.clearcoatNormalMapTransform),m.clearcoatNormalScale.value.copy(f.clearcoatNormalScale),f.side===ke&&m.clearcoatNormalScale.value.negate())),f.dispersion>0&&(m.dispersion.value=f.dispersion),f.iridescence>0&&(m.iridescence.value=f.iridescence,m.iridescenceIOR.value=f.iridescenceIOR,m.iridescenceThicknessMinimum.value=f.iridescenceThicknessRange[0],m.iridescenceThicknessMaximum.value=f.iridescenceThicknessRange[1],f.iridescenceMap&&(m.iridescenceMap.value=f.iridescenceMap,e(f.iridescenceMap,m.iridescenceMapTransform)),f.iridescenceThicknessMap&&(m.iridescenceThicknessMap.value=f.iridescenceThicknessMap,e(f.iridescenceThicknessMap,m.iridescenceThicknessMapTransform))),f.transmission>0&&(m.transmission.value=f.transmission,m.transmissionSamplerMap.value=E.texture,m.transmissionSamplerSize.value.set(E.width,E.height),f.transmissionMap&&(m.transmissionMap.value=f.transmissionMap,e(f.transmissionMap,m.transmissionMapTransform)),m.thickness.value=f.thickness,f.thicknessMap&&(m.thicknessMap.value=f.thicknessMap,e(f.thicknessMap,m.thicknessMapTransform)),m.attenuationDistance.value=f.attenuationDistance,m.attenuationColor.value.copy(f.attenuationColor)),f.anisotropy>0&&(m.anisotropyVector.value.set(f.anisotropy*Math.cos(f.anisotropyRotation),f.anisotropy*Math.sin(f.anisotropyRotation)),f.anisotropyMap&&(m.anisotropyMap.value=f.anisotropyMap,e(f.anisotropyMap,m.anisotropyMapTransform))),m.specularIntensity.value=f.specularIntensity,m.specularColor.value.copy(f.specularColor),f.specularColorMap&&(m.specularColorMap.value=f.specularColorMap,e(f.specularColorMap,m.specularColorMapTransform)),f.specularIntensityMap&&(m.specularIntensityMap.value=f.specularIntensityMap,e(f.specularIntensityMap,m.specularIntensityMapTransform))}function g(m,f){f.matcap&&(m.matcap.value=f.matcap)}function v(m,f){const E=t.get(f).light;m.referencePosition.value.setFromMatrixPosition(E.matrixWorld),m.nearDistance.value=E.shadow.camera.near,m.farDistance.value=E.shadow.camera.far}return{refreshFogUniforms:n,refreshMaterialUniforms:s}}function n_(i,t,e,n){let s={},r={},a=[];const o=i.getParameter(i.MAX_UNIFORM_BUFFER_BINDINGS);function c(y,b){const M=b.program;n.uniformBlockBinding(y,M)}function l(y,b){let M=s[y.id];M===void 0&&(m(y),M=h(y),s[y.id]=M,y.addEventListener("dispose",E));const A=b.program;n.updateUBOMapping(y,A);const x=t.render.frame;r[y.id]!==x&&(u(y),r[y.id]=x)}function h(y){const b=d();y.__bindingPointIndex=b;const M=i.createBuffer(),A=y.__size,x=y.usage;return i.bindBuffer(i.UNIFORM_BUFFER,M),i.bufferData(i.UNIFORM_BUFFER,A,x),i.bindBuffer(i.UNIFORM_BUFFER,null),i.bindBufferBase(i.UNIFORM_BUFFER,b,M),M}function d(){for(let y=0;y<o;y++)if(a.indexOf(y)===-1)return a.push(y),y;return ee("WebGLRenderer: Maximum number of simultaneously usable uniforms groups reached."),0}function u(y){const b=s[y.id],M=y.uniforms,A=y.__cache;i.bindBuffer(i.UNIFORM_BUFFER,b);for(let x=0,T=M.length;x<T;x++){const N=M[x];if(Array.isArray(N))for(let D=0,B=N.length;D<B;D++)p(N[D],x,D,A);else p(N,x,0,A)}i.bindBuffer(i.UNIFORM_BUFFER,null)}function p(y,b,M,A){if(v(y,b,M,A)===!0){const x=y.__offset,T=y.value;if(Array.isArray(T)){let N=0;for(let D=0;D<T.length;D++){const B=T[D],$=f(B);g(B,y.__data,N),typeof B!="number"&&typeof B!="boolean"&&!B.isMatrix3&&!ArrayBuffer.isView(B)&&(N+=$.storage/Float32Array.BYTES_PER_ELEMENT)}}else g(T,y.__data,0);i.bufferSubData(i.UNIFORM_BUFFER,x,y.__data)}}function g(y,b,M){typeof y=="number"||typeof y=="boolean"?b[0]=y:y.isMatrix3?(b[0]=y.elements[0],b[1]=y.elements[1],b[2]=y.elements[2],b[3]=0,b[4]=y.elements[3],b[5]=y.elements[4],b[6]=y.elements[5],b[7]=0,b[8]=y.elements[6],b[9]=y.elements[7],b[10]=y.elements[8],b[11]=0):ArrayBuffer.isView(y)?b.set(new y.constructor(y.buffer,y.byteOffset,b.length)):y.toArray(b,M)}function v(y,b,M,A){const x=y.value,T=b+"_"+M;if(A[T]===void 0)return typeof x=="number"||typeof x=="boolean"?A[T]=x:ArrayBuffer.isView(x)?A[T]=x.slice():A[T]=x.clone(),!0;{const N=A[T];if(typeof x=="number"||typeof x=="boolean"){if(N!==x)return A[T]=x,!0}else{if(ArrayBuffer.isView(x))return!0;if(N.equals(x)===!1)return N.copy(x),!0}}return!1}function m(y){const b=y.uniforms;let M=0;const A=16;for(let T=0,N=b.length;T<N;T++){const D=Array.isArray(b[T])?b[T]:[b[T]];for(let B=0,$=D.length;B<$;B++){const J=D[B],z=Array.isArray(J.value)?J.value:[J.value];for(let H=0,W=z.length;H<W;H++){const tt=z[H],Q=f(tt),ht=M%A,ut=ht%Q.boundary,St=ht+ut;M+=ut,St!==0&&A-St<Q.storage&&(M+=A-St),J.__data=new Float32Array(Q.storage/Float32Array.BYTES_PER_ELEMENT),J.__offset=M,M+=Q.storage}}}const x=M%A;return x>0&&(M+=A-x),y.__size=M,y.__cache={},this}function f(y){const b={boundary:0,storage:0};return typeof y=="number"||typeof y=="boolean"?(b.boundary=4,b.storage=4):y.isVector2?(b.boundary=8,b.storage=8):y.isVector3||y.isColor?(b.boundary=16,b.storage=12):y.isVector4?(b.boundary=16,b.storage=16):y.isMatrix3?(b.boundary=48,b.storage=48):y.isMatrix4?(b.boundary=64,b.storage=64):y.isTexture?Ut("WebGLRenderer: Texture samplers can not be part of an uniforms group."):ArrayBuffer.isView(y)?(b.boundary=16,b.storage=y.byteLength):Ut("WebGLRenderer: Unsupported uniform value type.",y),b}function E(y){const b=y.target;b.removeEventListener("dispose",E);const M=a.indexOf(b.__bindingPointIndex);a.splice(M,1),i.deleteBuffer(s[b.id]),delete s[b.id],delete r[b.id]}function w(){for(const y in s)i.deleteBuffer(s[y]);a=[],s={},r={}}return{bind:c,update:l,dispose:w}}const i_=new Uint16Array([12469,15057,12620,14925,13266,14620,13807,14376,14323,13990,14545,13625,14713,13328,14840,12882,14931,12528,14996,12233,15039,11829,15066,11525,15080,11295,15085,10976,15082,10705,15073,10495,13880,14564,13898,14542,13977,14430,14158,14124,14393,13732,14556,13410,14702,12996,14814,12596,14891,12291,14937,11834,14957,11489,14958,11194,14943,10803,14921,10506,14893,10278,14858,9960,14484,14039,14487,14025,14499,13941,14524,13740,14574,13468,14654,13106,14743,12678,14818,12344,14867,11893,14889,11509,14893,11180,14881,10751,14852,10428,14812,10128,14765,9754,14712,9466,14764,13480,14764,13475,14766,13440,14766,13347,14769,13070,14786,12713,14816,12387,14844,11957,14860,11549,14868,11215,14855,10751,14825,10403,14782,10044,14729,9651,14666,9352,14599,9029,14967,12835,14966,12831,14963,12804,14954,12723,14936,12564,14917,12347,14900,11958,14886,11569,14878,11247,14859,10765,14828,10401,14784,10011,14727,9600,14660,9289,14586,8893,14508,8533,15111,12234,15110,12234,15104,12216,15092,12156,15067,12010,15028,11776,14981,11500,14942,11205,14902,10752,14861,10393,14812,9991,14752,9570,14682,9252,14603,8808,14519,8445,14431,8145,15209,11449,15208,11451,15202,11451,15190,11438,15163,11384,15117,11274,15055,10979,14994,10648,14932,10343,14871,9936,14803,9532,14729,9218,14645,8742,14556,8381,14461,8020,14365,7603,15273,10603,15272,10607,15267,10619,15256,10631,15231,10614,15182,10535,15118,10389,15042,10167,14963,9787,14883,9447,14800,9115,14710,8665,14615,8318,14514,7911,14411,7507,14279,7198,15314,9675,15313,9683,15309,9712,15298,9759,15277,9797,15229,9773,15166,9668,15084,9487,14995,9274,14898,8910,14800,8539,14697,8234,14590,7790,14479,7409,14367,7067,14178,6621,15337,8619,15337,8631,15333,8677,15325,8769,15305,8871,15264,8940,15202,8909,15119,8775,15022,8565,14916,8328,14804,8009,14688,7614,14569,7287,14448,6888,14321,6483,14088,6171,15350,7402,15350,7419,15347,7480,15340,7613,15322,7804,15287,7973,15229,8057,15148,8012,15046,7846,14933,7611,14810,7357,14682,7069,14552,6656,14421,6316,14251,5948,14007,5528,15356,5942,15356,5977,15353,6119,15348,6294,15332,6551,15302,6824,15249,7044,15171,7122,15070,7050,14949,6861,14818,6611,14679,6349,14538,6067,14398,5651,14189,5311,13935,4958,15359,4123,15359,4153,15356,4296,15353,4646,15338,5160,15311,5508,15263,5829,15188,6042,15088,6094,14966,6001,14826,5796,14678,5543,14527,5287,14377,4985,14133,4586,13869,4257,15360,1563,15360,1642,15358,2076,15354,2636,15341,3350,15317,4019,15273,4429,15203,4732,15105,4911,14981,4932,14836,4818,14679,4621,14517,4386,14359,4156,14083,3795,13808,3437,15360,122,15360,137,15358,285,15355,636,15344,1274,15322,2177,15281,2765,15215,3223,15120,3451,14995,3569,14846,3567,14681,3466,14511,3305,14344,3121,14037,2800,13753,2467,15360,0,15360,1,15359,21,15355,89,15346,253,15325,479,15287,796,15225,1148,15133,1492,15008,1749,14856,1882,14685,1886,14506,1783,14324,1608,13996,1398,13702,1183]);let mn=null;function s_(){return mn===null&&(mn=new Jc(i_,16,16,xi,Un),mn.name="DFG_LUT",mn.minFilter=ye,mn.magFilter=ye,mn.wrapS=ln,mn.wrapT=ln,mn.generateMipmaps=!1,mn.needsUpdate=!0),mn}class fh{constructor(t={}){const{canvas:e=gu(),context:n=null,depth:s=!0,stencil:r=!1,alpha:a=!1,antialias:o=!1,premultipliedAlpha:c=!0,preserveDrawingBuffer:l=!1,powerPreference:h="default",failIfMajorPerformanceCaveat:d=!1,reversedDepthBuffer:u=!1,outputBufferType:p=qe}=t;this.isWebGLRenderer=!0;let g;if(n!==null){if(typeof WebGLRenderingContext<"u"&&n instanceof WebGLRenderingContext)throw new Error("THREE.WebGLRenderer: WebGL 1 is not supported since r163.");g=n.getContextAttributes().alpha}else g=a;const v=p,m=new Set([Po,Co,Ro]),f=new Set([qe,yn,vs,Ms,To,Ao]),E=new Uint32Array(4),w=new Int32Array(4),y=new I;let b=null,M=null;const A=[],x=[];let T=null;this.domElement=e,this.debug={checkShaderErrors:!0,onShaderError:null},this.autoClear=!0,this.autoClearColor=!0,this.autoClearDepth=!0,this.autoClearStencil=!0,this.sortObjects=!0,this.clippingPlanes=[],this.localClippingEnabled=!1,this.toneMapping=Mn,this.toneMappingExposure=1,this.transmissionResolutionScale=1;const N=this;let D=!1,B=null,$=null,J=null,z=null;this._outputColorSpace=Fe;let H=0,W=0,tt=null,Q=-1,ht=null;const ut=new pe,St=new pe;let Bt=null;const Yt=new Ft(0);let Vt=0,C=e.width,P=e.height,U=1,q=null,Z=null;const V=new pe(0,0,C,P),at=new pe(0,0,C,P);let st=!1;const rt=new Uo;let lt=!1,ft=!1;const ct=new se,te=new I,Gt=new pe,Jt={background:null,fog:null,environment:null,overrideMaterial:null,isScene:!0};let Kt=!1;function It(){return tt===null?U:1}let L=n;function ne(S,O){return e.getContext(S,O)}try{const S={alpha:!0,depth:s,stencil:r,antialias:o,premultipliedAlpha:c,preserveDrawingBuffer:l,powerPreference:h,failIfMajorPerformanceCaveat:d};if("setAttribute"in e&&e.setAttribute("data-engine","three.js r185"),e.addEventListener("webglcontextlost",me,!1),e.addEventListener("webglcontextrestored",ue,!1),e.addEventListener("webglcontextcreationerror",un,!1),L===null){const O="webgl2";if(L=ne(O,S),L===null)throw ne(O)?new Error("THREE.WebGLRenderer: Error creating WebGL context with your selected attributes."):new Error("THREE.WebGLRenderer: Error creating WebGL context.")}}catch(S){throw ee("WebGLRenderer: "+S.message),S}let kt,R,_,k,G,j,dt,mt,et,it,gt,Pt,vt,_t,Nt,Ot,Wt,F,pt,nt,xt,Et,ot;function Ct(){kt=new sm(L),kt.init(),xt=new Kg(L,kt),R=new Zp(L,kt,t,xt),_=new Yg(L,kt),R.reversedDepthBuffer&&u&&_.buffers.depth.setReversed(!0),$=L.createFramebuffer(),J=L.createFramebuffer(),z=L.createFramebuffer(),k=new om(L),G=new Ig,j=new $g(L,kt,_,G,R,xt,k),dt=new im(N),mt=new hd(L),Et=new $p(L,mt),et=new rm(L,mt,k,Et),it=new cm(L,et,mt,Et,k),F=new lm(L,R,j),Nt=new Jp(G),gt=new Lg(N,dt,kt,R,Et,Nt),Pt=new e_(N,G),vt=new Ug,_t=new Vg(kt),Wt=new Yp(N,dt,_,it,g,c),Ot=new qg(N,it,R),ot=new n_(L,k,R,_),pt=new Kp(L,kt,k),nt=new am(L,kt,k),k.programs=gt.programs,N.capabilities=R,N.extensions=kt,N.properties=G,N.renderLists=vt,N.shadowMap=Ot,N.state=_,N.info=k}Ct(),v!==qe&&(T=new um(v,e.width,e.height,o,s,r));const wt=new Qg(N,L);this.xr=wt,this.getContext=function(){return L},this.getContextAttributes=function(){return L.getContextAttributes()},this.forceContextLoss=function(){const S=kt.get("WEBGL_lose_context");S&&S.loseContext()},this.forceContextRestore=function(){const S=kt.get("WEBGL_lose_context");S&&S.restoreContext()},this.getPixelRatio=function(){return U},this.setPixelRatio=function(S){S!==void 0&&(U=S,this.setSize(C,P,!1))},this.getSize=function(S){return S.set(C,P)},this.setSize=function(S,O,K=!0){if(wt.isPresenting){Ut("WebGLRenderer: Can't change size while VR device is presenting.");return}C=S,P=O,e.width=Math.floor(S*U),e.height=Math.floor(O*U),K===!0&&(e.style.width=S+"px",e.style.height=O+"px"),T!==null&&T.setSize(e.width,e.height),this.setViewport(0,0,S,O)},this.getDrawingBufferSize=function(S){return S.set(C*U,P*U).floor()},this.setDrawingBufferSize=function(S,O,K){C=S,P=O,U=K,e.width=Math.floor(S*K),e.height=Math.floor(O*K),this.setViewport(0,0,S,O)},this.setEffects=function(S){if(v===qe){ee("WebGLRenderer: setEffects() requires outputBufferType set to HalfFloatType or FloatType.");return}if(S){for(let O=0;O<S.length;O++)if(S[O].isOutputPass===!0){Ut("WebGLRenderer: OutputPass is not needed in setEffects(). Tone mapping and color space conversion are applied automatically.");break}}T.setEffects(S||[])},this.getCurrentViewport=function(S){return S.copy(ut)},this.getViewport=function(S){return S.copy(V)},this.setViewport=function(S,O,K,X){S.isVector4?V.set(S.x,S.y,S.z,S.w):V.set(S,O,K,X),_.viewport(ut.copy(V).multiplyScalar(U).round())},this.getScissor=function(S){return S.copy(at)},this.setScissor=function(S,O,K,X){S.isVector4?at.set(S.x,S.y,S.z,S.w):at.set(S,O,K,X),_.scissor(St.copy(at).multiplyScalar(U).round())},this.getScissorTest=function(){return st},this.setScissorTest=function(S){_.setScissorTest(st=S)},this.setOpaqueSort=function(S){q=S},this.setTransparentSort=function(S){Z=S},this.getClearColor=function(S){return S.copy(Wt.getClearColor())},this.setClearColor=function(){Wt.setClearColor(...arguments)},this.getClearAlpha=function(){return Wt.getClearAlpha()},this.setClearAlpha=function(){Wt.setClearAlpha(...arguments)},this.clear=function(S=!0,O=!0,K=!0){let X=0;if(S){let Y=!1;if(tt!==null){const bt=tt.texture.format;Y=m.has(bt)}if(Y){const bt=tt.texture.type,At=f.has(bt),yt=Wt.getClearColor(),Rt=Wt.getClearAlpha(),Dt=yt.r,Xt=yt.g,Zt=yt.b;At?(E[0]=Dt,E[1]=Xt,E[2]=Zt,E[3]=Rt,L.clearBufferuiv(L.COLOR,0,E)):(w[0]=Dt,w[1]=Xt,w[2]=Zt,w[3]=Rt,L.clearBufferiv(L.COLOR,0,w))}else X|=L.COLOR_BUFFER_BIT}O&&(X|=L.DEPTH_BUFFER_BIT,this.state.buffers.depth.setMask(!0)),K&&(X|=L.STENCIL_BUFFER_BIT,this.state.buffers.stencil.setMask(4294967295)),X!==0&&L.clear(X)},this.clearColor=function(){this.clear(!0,!1,!1)},this.clearDepth=function(){this.clear(!1,!0,!1)},this.clearStencil=function(){this.clear(!1,!1,!0)},this.setNodesHandler=function(S){S.setRenderer(this),B=S},this.dispose=function(){e.removeEventListener("webglcontextlost",me,!1),e.removeEventListener("webglcontextrestored",ue,!1),e.removeEventListener("webglcontextcreationerror",un,!1),Wt.dispose(),vt.dispose(),_t.dispose(),G.dispose(),dt.dispose(),it.dispose(),Et.dispose(),ot.dispose(),gt.dispose(),wt.dispose(),wt.removeEventListener("sessionstart",Jo),wt.removeEventListener("sessionend",jo),ri.stop()};function me(S){S.preventDefault(),fl("WebGLRenderer: Context Lost."),D=!0}function ue(){fl("WebGLRenderer: Context Restored."),D=!1;const S=k.autoReset,O=Ot.enabled,K=Ot.autoUpdate,X=Ot.needsUpdate,Y=Ot.type;Ct(),k.autoReset=S,Ot.enabled=O,Ot.autoUpdate=K,Ot.needsUpdate=X,Ot.type=Y}function un(S){ee("WebGLRenderer: A WebGL context could not be created. Reason: ",S.statusMessage)}function dn(S){const O=S.target;O.removeEventListener("dispose",dn),Ph(O)}function Ph(S){Dh(S),G.remove(S)}function Dh(S){const O=G.get(S).programs;O!==void 0&&(O.forEach(function(K){gt.releaseProgram(K)}),S.isShaderMaterial&&gt.releaseShaderCache(S))}this.renderBufferDirect=function(S,O,K,X,Y,bt){O===null&&(O=Jt);const At=Y.isMesh&&Y.matrixWorld.determinantAffine()<0,yt=Nh(S,O,K,X,Y);_.setMaterial(X,At);let Rt=K.index,Dt=1;if(X.wireframe===!0){if(Rt=et.getWireframeAttribute(K),Rt===void 0)return;Dt=2}const Xt=K.drawRange,Zt=K.attributes.position;let Lt=Xt.start*Dt,oe=(Xt.start+Xt.count)*Dt;bt!==null&&(Lt=Math.max(Lt,bt.start*Dt),oe=Math.min(oe,(bt.start+bt.count)*Dt)),Rt!==null?(Lt=Math.max(Lt,0),oe=Math.min(oe,Rt.count)):Zt!=null&&(Lt=Math.max(Lt,0),oe=Math.min(oe,Zt.count));const xe=oe-Lt;if(xe<0||xe===1/0)return;Et.setup(Y,X,yt,K,Rt);let ge,ce=pt;if(Rt!==null&&(ge=mt.get(Rt),ce=nt,ce.setIndex(ge)),Y.isMesh)X.wireframe===!0?(_.setLineWidth(X.wireframeLinewidth*It()),ce.setMode(L.LINES)):ce.setMode(L.TRIANGLES);else if(Y.isLine){let De=X.linewidth;De===void 0&&(De=1),_.setLineWidth(De*It()),Y.isLineSegments?ce.setMode(L.LINES):Y.isLineLoop?ce.setMode(L.LINE_LOOP):ce.setMode(L.LINE_STRIP)}else Y.isPoints?ce.setMode(L.POINTS):Y.isSprite&&ce.setMode(L.TRIANGLES);if(Y.isBatchedMesh)if(kt.get("WEBGL_multi_draw"))ce.renderMultiDraw(Y._multiDrawStarts,Y._multiDrawCounts,Y._multiDrawCount);else{const De=Y._multiDrawStarts,Tt=Y._multiDrawCounts,Ve=Y._multiDrawCount,ie=Rt?mt.get(Rt).bytesPerElement:1,Ze=G.get(X).currentProgram.getUniforms();for(let fn=0;fn<Ve;fn++)Ze.setValue(L,"_gl_DrawID",fn),ce.render(De[fn]/ie,Tt[fn])}else if(Y.isInstancedMesh)ce.renderInstances(Lt,xe,Y.count);else if(K.isInstancedBufferGeometry){const De=K._maxInstanceCount!==void 0?K._maxInstanceCount:1/0,Tt=Math.min(K.instanceCount,De);ce.renderInstances(Lt,xe,Tt)}else ce.render(Lt,xe)};function Zo(S,O,K){S.transparent===!0&&S.side===Xe&&S.forceSinglePass===!1?(S.side=ke,S.needsUpdate=!0,Ps(S,O,K),S.side=ni,S.needsUpdate=!0,Ps(S,O,K),S.side=Xe):Ps(S,O,K)}this.compile=function(S,O,K=null){K===null&&(K=S),M=_t.get(K),M.init(O),x.push(M),K.traverseVisible(function(Y){Y.isLight&&Y.layers.test(O.layers)&&(M.pushLight(Y),Y.castShadow&&M.pushShadow(Y))}),S!==K&&S.traverseVisible(function(Y){Y.isLight&&Y.layers.test(O.layers)&&(M.pushLight(Y),Y.castShadow&&M.pushShadow(Y))}),M.setupLights();const X=new Set;return S.traverse(function(Y){if(!(Y.isMesh||Y.isPoints||Y.isLine||Y.isSprite))return;const bt=Y.material;if(bt)if(Array.isArray(bt))for(let At=0;At<bt.length;At++){const yt=bt[At];Zo(yt,K,Y),X.add(yt)}else Zo(bt,K,Y),X.add(bt)}),M=x.pop(),X},this.compileAsync=function(S,O,K=null){const X=this.compile(S,O,K);return new Promise(Y=>{function bt(){if(X.forEach(function(At){G.get(At).currentProgram.isReady()&&X.delete(At)}),X.size===0){Y(S);return}setTimeout(bt,10)}kt.get("KHR_parallel_shader_compile")!==null?bt():setTimeout(bt,10)})};let Nr=null;function Lh(S){Nr&&Nr(S)}function Jo(){ri.stop()}function jo(){ri.start()}const ri=new rh;ri.setAnimationLoop(Lh),typeof self<"u"&&ri.setContext(self),this.setAnimationLoop=function(S){Nr=S,wt.setAnimationLoop(S),S===null?ri.stop():ri.start()},wt.addEventListener("sessionstart",Jo),wt.addEventListener("sessionend",jo),this.render=function(S,O){if(O!==void 0&&O.isCamera!==!0){ee("WebGLRenderer.render: camera is not an instance of THREE.Camera.");return}if(D===!0)return;B!==null&&B.renderStart(S,O);const K=wt.enabled===!0&&wt.isPresenting===!0,X=T!==null&&(tt===null||K)&&T.begin(N,tt);if(S.matrixWorldAutoUpdate===!0&&S.updateMatrixWorld(),O.parent===null&&O.matrixWorldAutoUpdate===!0&&O.updateMatrixWorld(),wt.enabled===!0&&wt.isPresenting===!0&&(T===null||T.isCompositing()===!1)&&(wt.cameraAutoUpdate===!0&&wt.updateCamera(O),O=wt.getCamera()),S.isScene===!0&&S.onBeforeRender(N,S,O,tt),M=_t.get(S,x.length),M.init(O),M.state.textureUnits=j.getTextureUnits(),x.push(M),ct.multiplyMatrices(O.projectionMatrix,O.matrixWorldInverse),rt.setFromProjectionMatrix(ct,vn,O.reversedDepth),ft=this.localClippingEnabled,lt=Nt.init(this.clippingPlanes,ft),b=vt.get(S,A.length),b.init(),A.push(b),wt.enabled===!0&&wt.isPresenting===!0){const At=N.xr.getDepthSensingMesh();At!==null&&Ur(At,O,-1/0,N.sortObjects)}Ur(S,O,0,N.sortObjects),b.finish(),N.sortObjects===!0&&b.sort(q,Z,O.reversedDepth),Kt=wt.enabled===!1||wt.isPresenting===!1||wt.hasDepthSensing()===!1,Kt&&Wt.addToRenderList(b,S),this.info.render.frame++,this.info.autoReset===!0&&this.info.reset(),lt===!0&&Nt.beginShadows();const Y=M.state.shadowsArray;if(Ot.render(Y,S,O),lt===!0&&Nt.endShadows(),(X&&T.hasRenderPass())===!1){const At=b.opaque,yt=b.transmissive;if(M.setupLights(),O.isArrayCamera){const Rt=O.cameras;if(yt.length>0)for(let Dt=0,Xt=Rt.length;Dt<Xt;Dt++){const Zt=Rt[Dt];tl(At,yt,S,Zt)}Kt&&Wt.render(S);for(let Dt=0,Xt=Rt.length;Dt<Xt;Dt++){const Zt=Rt[Dt];Qo(b,S,Zt,Zt.viewport)}}else yt.length>0&&tl(At,yt,S,O),Kt&&Wt.render(S),Qo(b,S,O)}tt!==null&&W===0&&(j.updateMultisampleRenderTarget(tt),j.updateRenderTargetMipmap(tt)),X&&T.end(N),S.isScene===!0&&S.onAfterRender(N,S,O),Et.resetDefaultState(),Q=-1,ht=null,x.pop(),x.length>0?(M=x[x.length-1],j.setTextureUnits(M.state.textureUnits),lt===!0&&Nt.setGlobalState(N.clippingPlanes,M.state.camera)):M=null,A.pop(),A.length>0?b=A[A.length-1]:b=null,B!==null&&B.renderEnd()};function Ur(S,O,K,X){if(S.visible===!1)return;if(S.layers.test(O.layers)){if(S.isGroup)K=S.renderOrder;else if(S.isLOD)S.autoUpdate===!0&&S.update(O);else if(S.isLightProbeGrid)M.pushLightProbeGrid(S);else if(S.isLight)M.pushLight(S),S.castShadow&&M.pushShadow(S);else if(S.isSprite){if(!S.frustumCulled||rt.intersectsSprite(S)){X&&Gt.setFromMatrixPosition(S.matrixWorld).applyMatrix4(ct);const At=it.update(S),yt=S.material;yt.visible&&b.push(S,At,yt,K,Gt.z,null)}}else if((S.isMesh||S.isLine||S.isPoints)&&(!S.frustumCulled||rt.intersectsObject(S))){const At=it.update(S),yt=S.material;if(X&&(S.boundingSphere!==void 0?(S.boundingSphere===null&&S.computeBoundingSphere(),Gt.copy(S.boundingSphere.center)):(At.boundingSphere===null&&At.computeBoundingSphere(),Gt.copy(At.boundingSphere.center)),Gt.applyMatrix4(S.matrixWorld).applyMatrix4(ct)),Array.isArray(yt)){const Rt=At.groups;for(let Dt=0,Xt=Rt.length;Dt<Xt;Dt++){const Zt=Rt[Dt],Lt=yt[Zt.materialIndex];Lt&&Lt.visible&&b.push(S,At,Lt,K,Gt.z,Zt)}}else yt.visible&&b.push(S,At,yt,K,Gt.z,null)}}const bt=S.children;for(let At=0,yt=bt.length;At<yt;At++)Ur(bt[At],O,K,X)}function Qo(S,O,K,X){const{opaque:Y,transmissive:bt,transparent:At}=S;M.setupLightsView(K),lt===!0&&Nt.setGlobalState(N.clippingPlanes,K),X&&_.viewport(ut.copy(X)),Y.length>0&&Cs(Y,O,K),bt.length>0&&Cs(bt,O,K),At.length>0&&Cs(At,O,K),_.buffers.depth.setTest(!0),_.buffers.depth.setMask(!0),_.buffers.color.setMask(!0),_.setPolygonOffset(!1)}function tl(S,O,K,X){if((K.isScene===!0?K.overrideMaterial:null)!==null)return;if(M.state.transmissionRenderTarget[X.id]===void 0){const Lt=kt.has("EXT_color_buffer_half_float")||kt.has("EXT_color_buffer_float");M.state.transmissionRenderTarget[X.id]=new Sn(1,1,{generateMipmaps:!0,type:Lt?Un:qe,minFilter:Ln,samples:Math.max(4,R.samples),stencilBuffer:r,resolveDepthBuffer:!1,resolveStencilBuffer:!1,colorSpace:Qt.workingColorSpace})}const bt=M.state.transmissionRenderTarget[X.id],At=X.viewport||ut;bt.setSize(At.z*N.transmissionResolutionScale,At.w*N.transmissionResolutionScale);const yt=N.getRenderTarget(),Rt=N.getActiveCubeFace(),Dt=N.getActiveMipmapLevel();N.setRenderTarget(bt),N.getClearColor(Yt),Vt=N.getClearAlpha(),Vt<1&&N.setClearColor(16777215,.5),N.clear(),Kt&&Wt.render(K);const Xt=N.toneMapping;N.toneMapping=Mn;const Zt=X.viewport;if(X.viewport!==void 0&&(X.viewport=void 0),M.setupLightsView(X),lt===!0&&Nt.setGlobalState(N.clippingPlanes,X),Cs(S,K,X),j.updateMultisampleRenderTarget(bt),j.updateRenderTargetMipmap(bt),kt.has("WEBGL_multisampled_render_to_texture")===!1){let Lt=!1;for(let oe=0,xe=O.length;oe<xe;oe++){const ge=O[oe],{object:ce,geometry:De,material:Tt,group:Ve}=ge;if(Tt.side===Xe&&ce.layers.test(X.layers)){const ie=Tt.side;Tt.side=ke,Tt.needsUpdate=!0,el(ce,K,X,De,Tt,Ve),Tt.side=ie,Tt.needsUpdate=!0,Lt=!0}}Lt===!0&&(j.updateMultisampleRenderTarget(bt),j.updateRenderTargetMipmap(bt))}N.setRenderTarget(yt,Rt,Dt),N.setClearColor(Yt,Vt),Zt!==void 0&&(X.viewport=Zt),N.toneMapping=Xt}function Cs(S,O,K){const X=O.isScene===!0?O.overrideMaterial:null;for(let Y=0,bt=S.length;Y<bt;Y++){const At=S[Y],{object:yt,geometry:Rt,group:Dt}=At;let Xt=At.material;Xt.allowOverride===!0&&X!==null&&(Xt=X),yt.layers.test(K.layers)&&el(yt,O,K,Rt,Xt,Dt)}}function el(S,O,K,X,Y,bt){S.onBeforeRender(N,O,K,X,Y,bt),S.modelViewMatrix.multiplyMatrices(K.matrixWorldInverse,S.matrixWorld),S.normalMatrix.getNormalMatrix(S.modelViewMatrix),Y.onBeforeRender(N,O,K,X,S,bt),Y.transparent===!0&&Y.side===Xe&&Y.forceSinglePass===!1?(Y.side=ke,Y.needsUpdate=!0,N.renderBufferDirect(K,O,X,Y,S,bt),Y.side=ni,Y.needsUpdate=!0,N.renderBufferDirect(K,O,X,Y,S,bt),Y.side=Xe):N.renderBufferDirect(K,O,X,Y,S,bt),S.onAfterRender(N,O,K,X,Y,bt)}function Ps(S,O,K){O.isScene!==!0&&(O=Jt);const X=G.get(S),Y=M.state.lights,bt=M.state.shadowsArray,At=Y.state.version,yt=gt.getParameters(S,Y.state,bt,O,K,M.state.lightProbeGridArray),Rt=gt.getProgramCacheKey(yt);let Dt=X.programs;X.environment=S.isMeshStandardMaterial||S.isMeshLambertMaterial||S.isMeshPhongMaterial?O.environment:null,X.fog=O.fog;const Xt=S.isMeshStandardMaterial||S.isMeshLambertMaterial&&!S.envMap||S.isMeshPhongMaterial&&!S.envMap;X.envMap=dt.get(S.envMap||X.environment,Xt),X.envMapRotation=X.environment!==null&&S.envMap===null?O.environmentRotation:S.envMapRotation,Dt===void 0&&(S.addEventListener("dispose",dn),Dt=new Map,X.programs=Dt);let Zt=Dt.get(Rt);if(Zt!==void 0){if(X.currentProgram===Zt&&X.lightsStateVersion===At)return il(S,yt),Zt}else yt.uniforms=gt.getUniforms(S),B!==null&&S.isNodeMaterial&&B.build(S,K,yt),S.onBeforeCompile(yt,N),Zt=gt.acquireProgram(yt,Rt),Dt.set(Rt,Zt),X.uniforms=yt.uniforms;const Lt=X.uniforms;return(!S.isShaderMaterial&&!S.isRawShaderMaterial||S.clipping===!0)&&(Lt.clippingPlanes=Nt.uniform),il(S,yt),X.needsLights=Fh(S),X.lightsStateVersion=At,X.needsLights&&(Lt.ambientLightColor.value=Y.state.ambient,Lt.lightProbe.value=Y.state.probe,Lt.directionalLights.value=Y.state.directional,Lt.directionalLightShadows.value=Y.state.directionalShadow,Lt.spotLights.value=Y.state.spot,Lt.spotLightShadows.value=Y.state.spotShadow,Lt.rectAreaLights.value=Y.state.rectArea,Lt.ltc_1.value=Y.state.rectAreaLTC1,Lt.ltc_2.value=Y.state.rectAreaLTC2,Lt.pointLights.value=Y.state.point,Lt.pointLightShadows.value=Y.state.pointShadow,Lt.hemisphereLights.value=Y.state.hemi,Lt.directionalShadowMatrix.value=Y.state.directionalShadowMatrix,Lt.spotLightMatrix.value=Y.state.spotLightMatrix,Lt.spotLightMap.value=Y.state.spotLightMap,Lt.pointShadowMatrix.value=Y.state.pointShadowMatrix),X.lightProbeGrid=M.state.lightProbeGridArray.length>0,X.currentProgram=Zt,X.uniformsList=null,Zt}function nl(S){if(S.uniformsList===null){const O=S.currentProgram.getUniforms();S.uniformsList=_r.seqWithValue(O.seq,S.uniforms)}return S.uniformsList}function il(S,O){const K=G.get(S);K.outputColorSpace=O.outputColorSpace,K.batching=O.batching,K.batchingColor=O.batchingColor,K.instancing=O.instancing,K.instancingColor=O.instancingColor,K.instancingMorph=O.instancingMorph,K.skinning=O.skinning,K.morphTargets=O.morphTargets,K.morphNormals=O.morphNormals,K.morphColors=O.morphColors,K.morphTargetsCount=O.morphTargetsCount,K.numClippingPlanes=O.numClippingPlanes,K.numIntersection=O.numClipIntersection,K.vertexAlphas=O.vertexAlphas,K.vertexTangents=O.vertexTangents,K.toneMapping=O.toneMapping}function Ih(S,O){if(S.length===0)return null;if(S.length===1)return S[0].texture!==null?S[0]:null;y.setFromMatrixPosition(O.matrixWorld);for(let K=0,X=S.length;K<X;K++){const Y=S[K];if(Y.texture!==null&&Y.boundingBox.containsPoint(y))return Y}return null}function Nh(S,O,K,X,Y){O.isScene!==!0&&(O=Jt),j.resetTextureUnits();const bt=O.fog,At=X.isMeshStandardMaterial||X.isMeshLambertMaterial||X.isMeshPhongMaterial?O.environment:null,yt=tt===null?N.outputColorSpace:tt.isXRRenderTarget===!0?tt.texture.colorSpace:Qt.workingColorSpace,Rt=X.isMeshStandardMaterial||X.isMeshLambertMaterial&&!X.envMap||X.isMeshPhongMaterial&&!X.envMap,Dt=dt.get(X.envMap||At,Rt),Xt=X.vertexColors===!0&&!!K.attributes.color&&K.attributes.color.itemSize===4,Zt=!!K.attributes.tangent&&(!!X.normalMap||X.anisotropy>0),Lt=!!K.morphAttributes.position,oe=!!K.morphAttributes.normal,xe=!!K.morphAttributes.color;let ge=Mn;X.toneMapped&&(tt===null||tt.isXRRenderTarget===!0)&&(ge=N.toneMapping);const ce=K.morphAttributes.position||K.morphAttributes.normal||K.morphAttributes.color,De=ce!==void 0?ce.length:0,Tt=G.get(X),Ve=M.state.lights;if(lt===!0&&(ft===!0||S!==ht)){const de=S===ht&&X.id===Q;Nt.setState(X,S,de)}let ie=!1;X.version===Tt.__version?(Tt.needsLights&&Tt.lightsStateVersion!==Ve.state.version||Tt.outputColorSpace!==yt||Y.isBatchedMesh&&Tt.batching===!1||!Y.isBatchedMesh&&Tt.batching===!0||Y.isBatchedMesh&&Tt.batchingColor===!0&&Y.colorTexture===null||Y.isBatchedMesh&&Tt.batchingColor===!1&&Y.colorTexture!==null||Y.isInstancedMesh&&Tt.instancing===!1||!Y.isInstancedMesh&&Tt.instancing===!0||Y.isSkinnedMesh&&Tt.skinning===!1||!Y.isSkinnedMesh&&Tt.skinning===!0||Y.isInstancedMesh&&Tt.instancingColor===!0&&Y.instanceColor===null||Y.isInstancedMesh&&Tt.instancingColor===!1&&Y.instanceColor!==null||Y.isInstancedMesh&&Tt.instancingMorph===!0&&Y.morphTexture===null||Y.isInstancedMesh&&Tt.instancingMorph===!1&&Y.morphTexture!==null||Tt.envMap!==Dt||X.fog===!0&&Tt.fog!==bt||Tt.numClippingPlanes!==void 0&&(Tt.numClippingPlanes!==Nt.numPlanes||Tt.numIntersection!==Nt.numIntersection)||Tt.vertexAlphas!==Xt||Tt.vertexTangents!==Zt||Tt.morphTargets!==Lt||Tt.morphNormals!==oe||Tt.morphColors!==xe||Tt.toneMapping!==ge||Tt.morphTargetsCount!==De||!!Tt.lightProbeGrid!=M.state.lightProbeGridArray.length>0)&&(ie=!0):(ie=!0,Tt.__version=X.version);let Ze=Tt.currentProgram;ie===!0&&(Ze=Ps(X,O,Y),B&&X.isNodeMaterial&&B.onUpdateProgram(X,Ze,Tt));let fn=!1,kn=!1,Mi=!1;const he=Ze.getUniforms(),ve=Tt.uniforms;if(_.useProgram(Ze.program)&&(fn=!0,kn=!0,Mi=!0),X.id!==Q&&(Q=X.id,kn=!0),Tt.needsLights){const de=Ih(M.state.lightProbeGridArray,Y);Tt.lightProbeGrid!==de&&(Tt.lightProbeGrid=de,kn=!0)}if(fn||ht!==S){_.buffers.depth.getReversed()&&S.reversedDepth!==!0&&(S._reversedDepth=!0,S.updateProjectionMatrix()),he.setValue(L,"projectionMatrix",S.projectionMatrix),he.setValue(L,"viewMatrix",S.matrixWorldInverse);const Gn=he.map.cameraPosition;Gn!==void 0&&Gn.setValue(L,te.setFromMatrixPosition(S.matrixWorld)),R.logarithmicDepthBuffer&&he.setValue(L,"logDepthBufFC",2/(Math.log(S.far+1)/Math.LN2)),(X.isMeshPhongMaterial||X.isMeshToonMaterial||X.isMeshLambertMaterial||X.isMeshBasicMaterial||X.isMeshStandardMaterial||X.isShaderMaterial)&&he.setValue(L,"isOrthographic",S.isOrthographicCamera===!0),ht!==S&&(ht=S,kn=!0,Mi=!0)}if(Tt.needsLights&&(Ve.state.directionalShadowMap.length>0&&he.setValue(L,"directionalShadowMap",Ve.state.directionalShadowMap,j),Ve.state.spotShadowMap.length>0&&he.setValue(L,"spotShadowMap",Ve.state.spotShadowMap,j),Ve.state.pointShadowMap.length>0&&he.setValue(L,"pointShadowMap",Ve.state.pointShadowMap,j)),Y.isSkinnedMesh){he.setOptional(L,Y,"bindMatrix"),he.setOptional(L,Y,"bindMatrixInverse");const de=Y.skeleton;de&&(de.boneTexture===null&&de.computeBoneTexture(),he.setValue(L,"boneTexture",de.boneTexture,j))}Y.isBatchedMesh&&(he.setOptional(L,Y,"batchingTexture"),he.setValue(L,"batchingTexture",Y._matricesTexture,j),he.setOptional(L,Y,"batchingIdTexture"),he.setValue(L,"batchingIdTexture",Y._indirectTexture,j),he.setOptional(L,Y,"batchingColorTexture"),Y._colorsTexture!==null&&he.setValue(L,"batchingColorTexture",Y._colorsTexture,j));const Vn=K.morphAttributes;if((Vn.position!==void 0||Vn.normal!==void 0||Vn.color!==void 0)&&F.update(Y,K,Ze),(kn||Tt.receiveShadow!==Y.receiveShadow)&&(Tt.receiveShadow=Y.receiveShadow,he.setValue(L,"receiveShadow",Y.receiveShadow)),(X.isMeshStandardMaterial||X.isMeshLambertMaterial||X.isMeshPhongMaterial)&&X.envMap===null&&O.environment!==null&&(ve.envMapIntensity.value=O.environmentIntensity),ve.dfgLUT!==void 0&&(ve.dfgLUT.value=s_()),kn){if(he.setValue(L,"toneMappingExposure",N.toneMappingExposure),Tt.needsLights&&Uh(ve,Mi),bt&&X.fog===!0&&Pt.refreshFogUniforms(ve,bt),Pt.refreshMaterialUniforms(ve,X,U,P,M.state.transmissionRenderTarget[S.id]),Tt.needsLights&&Tt.lightProbeGrid){const de=Tt.lightProbeGrid;ve.probesSH.value=de.texture,ve.probesMin.value.copy(de.boundingBox.min),ve.probesMax.value.copy(de.boundingBox.max),ve.probesResolution.value.copy(de.resolution)}_r.upload(L,nl(Tt),ve,j)}if(X.isShaderMaterial&&X.uniformsNeedUpdate===!0&&(_r.upload(L,nl(Tt),ve,j),X.uniformsNeedUpdate=!1),X.isSpriteMaterial&&he.setValue(L,"center",Y.center),he.setValue(L,"modelViewMatrix",Y.modelViewMatrix),he.setValue(L,"normalMatrix",Y.normalMatrix),he.setValue(L,"modelMatrix",Y.matrixWorld),X.uniformsGroups!==void 0){const de=X.uniformsGroups;for(let Gn=0,Si=de.length;Gn<Si;Gn++){const sl=de[Gn];ot.update(sl,Ze),ot.bind(sl,Ze)}}return Ze}function Uh(S,O){S.ambientLightColor.needsUpdate=O,S.lightProbe.needsUpdate=O,S.directionalLights.needsUpdate=O,S.directionalLightShadows.needsUpdate=O,S.pointLights.needsUpdate=O,S.pointLightShadows.needsUpdate=O,S.spotLights.needsUpdate=O,S.spotLightShadows.needsUpdate=O,S.rectAreaLights.needsUpdate=O,S.hemisphereLights.needsUpdate=O}function Fh(S){return S.isMeshLambertMaterial||S.isMeshToonMaterial||S.isMeshPhongMaterial||S.isMeshStandardMaterial||S.isShadowMaterial||S.isShaderMaterial&&S.lights===!0}this.getActiveCubeFace=function(){return H},this.getActiveMipmapLevel=function(){return W},this.getRenderTarget=function(){return tt},this.setRenderTargetTextures=function(S,O,K){const X=G.get(S);X.__autoAllocateDepthBuffer=S.resolveDepthBuffer===!1,X.__autoAllocateDepthBuffer===!1&&(X.__useRenderToTexture=!1),G.get(S.texture).__webglTexture=O,G.get(S.depthTexture).__webglTexture=X.__autoAllocateDepthBuffer?void 0:K,X.__hasExternalTextures=!0},this.setRenderTargetFramebuffer=function(S,O){const K=G.get(S);K.__webglFramebuffer=O,K.__useDefaultFramebuffer=O===void 0},this.setRenderTarget=function(S,O=0,K=0){tt=S,H=O,W=K;let X=null,Y=!1,bt=!1;if(S){const yt=G.get(S);if(yt.__useDefaultFramebuffer!==void 0){_.bindFramebuffer(L.FRAMEBUFFER,yt.__webglFramebuffer),ut.copy(S.viewport),St.copy(S.scissor),Bt=S.scissorTest,_.viewport(ut),_.scissor(St),_.setScissorTest(Bt),Q=-1;return}else if(yt.__webglFramebuffer===void 0)j.setupRenderTarget(S);else if(yt.__hasExternalTextures)j.rebindTextures(S,G.get(S.texture).__webglTexture,G.get(S.depthTexture).__webglTexture);else if(S.depthBuffer){const Xt=S.depthTexture;if(yt.__boundDepthTexture!==Xt){if(Xt!==null&&G.has(Xt)&&(S.width!==Xt.image.width||S.height!==Xt.image.height))throw new Error("THREE.WebGLRenderer: Attached DepthTexture is initialized to the incorrect size.");j.setupDepthRenderbuffer(S)}}const Rt=S.texture;(Rt.isData3DTexture||Rt.isDataArrayTexture||Rt.isCompressedArrayTexture)&&(bt=!0);const Dt=G.get(S).__webglFramebuffer;S.isWebGLCubeRenderTarget?(Array.isArray(Dt[O])?X=Dt[O][K]:X=Dt[O],Y=!0):S.samples>0&&j.useMultisampledRTT(S)===!1?X=G.get(S).__webglMultisampledFramebuffer:Array.isArray(Dt)?X=Dt[K]:X=Dt,ut.copy(S.viewport),St.copy(S.scissor),Bt=S.scissorTest}else ut.copy(V).multiplyScalar(U).floor(),St.copy(at).multiplyScalar(U).floor(),Bt=st;if(K!==0&&(X=$),_.bindFramebuffer(L.FRAMEBUFFER,X)&&_.drawBuffers(S,X),_.viewport(ut),_.scissor(St),_.setScissorTest(Bt),Y){const yt=G.get(S.texture);L.framebufferTexture2D(L.FRAMEBUFFER,L.COLOR_ATTACHMENT0,L.TEXTURE_CUBE_MAP_POSITIVE_X+O,yt.__webglTexture,K)}else if(bt){const yt=O;for(let Rt=0;Rt<S.textures.length;Rt++){const Dt=G.get(S.textures[Rt]);L.framebufferTextureLayer(L.FRAMEBUFFER,L.COLOR_ATTACHMENT0+Rt,Dt.__webglTexture,K,yt)}}else if(S!==null&&K!==0){const yt=G.get(S.texture);L.framebufferTexture2D(L.FRAMEBUFFER,L.COLOR_ATTACHMENT0,L.TEXTURE_2D,yt.__webglTexture,K)}Q=-1},this.readRenderTargetPixels=function(S,O,K,X,Y,bt,At,yt=0){if(!(S&&S.isWebGLRenderTarget)){ee("WebGLRenderer.readRenderTargetPixels: renderTarget is not THREE.WebGLRenderTarget.");return}let Rt=G.get(S).__webglFramebuffer;if(S.isWebGLCubeRenderTarget&&At!==void 0&&(Rt=Rt[At]),Rt){_.bindFramebuffer(L.FRAMEBUFFER,Rt);try{const Dt=S.textures[yt],Xt=Dt.format,Zt=Dt.type;if(S.textures.length>1&&L.readBuffer(L.COLOR_ATTACHMENT0+yt),!R.textureFormatReadable(Xt)){ee("WebGLRenderer.readRenderTargetPixels: renderTarget is not in RGBA or implementation defined format.");return}if(!R.textureTypeReadable(Zt)){ee("WebGLRenderer.readRenderTargetPixels: renderTarget is not in UnsignedByteType or implementation defined type.");return}O>=0&&O<=S.width-X&&K>=0&&K<=S.height-Y&&L.readPixels(O,K,X,Y,xt.convert(Xt),xt.convert(Zt),bt)}finally{const Dt=tt!==null?G.get(tt).__webglFramebuffer:null;_.bindFramebuffer(L.FRAMEBUFFER,Dt)}}},this.readRenderTargetPixelsAsync=async function(S,O,K,X,Y,bt,At,yt=0){if(!(S&&S.isWebGLRenderTarget))throw new Error("THREE.WebGLRenderer.readRenderTargetPixels: renderTarget is not THREE.WebGLRenderTarget.");let Rt=G.get(S).__webglFramebuffer;if(S.isWebGLCubeRenderTarget&&At!==void 0&&(Rt=Rt[At]),Rt)if(O>=0&&O<=S.width-X&&K>=0&&K<=S.height-Y){_.bindFramebuffer(L.FRAMEBUFFER,Rt);const Dt=S.textures[yt],Xt=Dt.format,Zt=Dt.type;if(S.textures.length>1&&L.readBuffer(L.COLOR_ATTACHMENT0+yt),!R.textureFormatReadable(Xt))throw new Error("THREE.WebGLRenderer.readRenderTargetPixelsAsync: renderTarget is not in RGBA or implementation defined format.");if(!R.textureTypeReadable(Zt))throw new Error("THREE.WebGLRenderer.readRenderTargetPixelsAsync: renderTarget is not in UnsignedByteType or implementation defined type.");const Lt=L.createBuffer();L.bindBuffer(L.PIXEL_PACK_BUFFER,Lt),L.bufferData(L.PIXEL_PACK_BUFFER,bt.byteLength,L.STREAM_READ),L.readPixels(O,K,X,Y,xt.convert(Xt),xt.convert(Zt),0);const oe=tt!==null?G.get(tt).__webglFramebuffer:null;_.bindFramebuffer(L.FRAMEBUFFER,oe);const xe=L.fenceSync(L.SYNC_GPU_COMMANDS_COMPLETE,0);return L.flush(),await _u(L,xe,4),L.bindBuffer(L.PIXEL_PACK_BUFFER,Lt),L.getBufferSubData(L.PIXEL_PACK_BUFFER,0,bt),L.deleteBuffer(Lt),L.deleteSync(xe),bt}else throw new Error("THREE.WebGLRenderer.readRenderTargetPixelsAsync: requested read bounds are out of range.")},this.copyFramebufferToTexture=function(S,O=null,K=0){const X=Math.pow(2,-K),Y=Math.floor(S.image.width*X),bt=Math.floor(S.image.height*X),At=O!==null?O.x:0,yt=O!==null?O.y:0;j.setTexture2D(S,0),L.copyTexSubImage2D(L.TEXTURE_2D,K,0,0,At,yt,Y,bt),_.unbindTexture()},this.copyTextureToTexture=function(S,O,K=null,X=null,Y=0,bt=0){let At,yt,Rt,Dt,Xt,Zt,Lt,oe,xe;const ge=S.isCompressedTexture?S.mipmaps[bt]:S.image;if(K!==null)At=K.max.x-K.min.x,yt=K.max.y-K.min.y,Rt=K.isBox3?K.max.z-K.min.z:1,Dt=K.min.x,Xt=K.min.y,Zt=K.isBox3?K.min.z:0;else{const ve=Math.pow(2,-Y);At=Math.floor(ge.width*ve),yt=Math.floor(ge.height*ve),S.isDataArrayTexture?Rt=ge.depth:S.isData3DTexture?Rt=Math.floor(ge.depth*ve):Rt=1,Dt=0,Xt=0,Zt=0}X!==null?(Lt=X.x,oe=X.y,xe=X.z):(Lt=0,oe=0,xe=0);const ce=xt.convert(O.format),De=xt.convert(O.type);let Tt;O.isData3DTexture?(j.setTexture3D(O,0),Tt=L.TEXTURE_3D):O.isDataArrayTexture||O.isCompressedArrayTexture?(j.setTexture2DArray(O,0),Tt=L.TEXTURE_2D_ARRAY):(j.setTexture2D(O,0),Tt=L.TEXTURE_2D),_.activeTexture(L.TEXTURE0),_.pixelStorei(L.UNPACK_FLIP_Y_WEBGL,O.flipY),_.pixelStorei(L.UNPACK_PREMULTIPLY_ALPHA_WEBGL,O.premultiplyAlpha),_.pixelStorei(L.UNPACK_ALIGNMENT,O.unpackAlignment);const Ve=_.getParameter(L.UNPACK_ROW_LENGTH),ie=_.getParameter(L.UNPACK_IMAGE_HEIGHT),Ze=_.getParameter(L.UNPACK_SKIP_PIXELS),fn=_.getParameter(L.UNPACK_SKIP_ROWS),kn=_.getParameter(L.UNPACK_SKIP_IMAGES);_.pixelStorei(L.UNPACK_ROW_LENGTH,ge.width),_.pixelStorei(L.UNPACK_IMAGE_HEIGHT,ge.height),_.pixelStorei(L.UNPACK_SKIP_PIXELS,Dt),_.pixelStorei(L.UNPACK_SKIP_ROWS,Xt),_.pixelStorei(L.UNPACK_SKIP_IMAGES,Zt);const Mi=S.isDataArrayTexture||S.isData3DTexture,he=O.isDataArrayTexture||O.isData3DTexture;if(S.isDepthTexture){const ve=G.get(S),Vn=G.get(O),de=G.get(ve.__renderTarget),Gn=G.get(Vn.__renderTarget);_.bindFramebuffer(L.READ_FRAMEBUFFER,de.__webglFramebuffer),_.bindFramebuffer(L.DRAW_FRAMEBUFFER,Gn.__webglFramebuffer);for(let Si=0;Si<Rt;Si++)Mi&&(L.framebufferTextureLayer(L.READ_FRAMEBUFFER,L.COLOR_ATTACHMENT0,G.get(S).__webglTexture,Y,Zt+Si),L.framebufferTextureLayer(L.DRAW_FRAMEBUFFER,L.COLOR_ATTACHMENT0,G.get(O).__webglTexture,bt,xe+Si)),L.blitFramebuffer(Dt,Xt,At,yt,Lt,oe,At,yt,L.DEPTH_BUFFER_BIT,L.NEAREST);_.bindFramebuffer(L.READ_FRAMEBUFFER,null),_.bindFramebuffer(L.DRAW_FRAMEBUFFER,null)}else if(Y!==0||S.isRenderTargetTexture||G.has(S)){const ve=G.get(S),Vn=G.get(O);_.bindFramebuffer(L.READ_FRAMEBUFFER,J),_.bindFramebuffer(L.DRAW_FRAMEBUFFER,z);for(let de=0;de<Rt;de++)Mi?L.framebufferTextureLayer(L.READ_FRAMEBUFFER,L.COLOR_ATTACHMENT0,ve.__webglTexture,Y,Zt+de):L.framebufferTexture2D(L.READ_FRAMEBUFFER,L.COLOR_ATTACHMENT0,L.TEXTURE_2D,ve.__webglTexture,Y),he?L.framebufferTextureLayer(L.DRAW_FRAMEBUFFER,L.COLOR_ATTACHMENT0,Vn.__webglTexture,bt,xe+de):L.framebufferTexture2D(L.DRAW_FRAMEBUFFER,L.COLOR_ATTACHMENT0,L.TEXTURE_2D,Vn.__webglTexture,bt),Y!==0?L.blitFramebuffer(Dt,Xt,At,yt,Lt,oe,At,yt,L.COLOR_BUFFER_BIT,L.NEAREST):he?L.copyTexSubImage3D(Tt,bt,Lt,oe,xe+de,Dt,Xt,At,yt):L.copyTexSubImage2D(Tt,bt,Lt,oe,Dt,Xt,At,yt);_.bindFramebuffer(L.READ_FRAMEBUFFER,null),_.bindFramebuffer(L.DRAW_FRAMEBUFFER,null)}else he?S.isDataTexture||S.isData3DTexture?L.texSubImage3D(Tt,bt,Lt,oe,xe,At,yt,Rt,ce,De,ge.data):O.isCompressedArrayTexture?L.compressedTexSubImage3D(Tt,bt,Lt,oe,xe,At,yt,Rt,ce,ge.data):L.texSubImage3D(Tt,bt,Lt,oe,xe,At,yt,Rt,ce,De,ge):S.isDataTexture?L.texSubImage2D(L.TEXTURE_2D,bt,Lt,oe,At,yt,ce,De,ge.data):S.isCompressedTexture?L.compressedTexSubImage2D(L.TEXTURE_2D,bt,Lt,oe,ge.width,ge.height,ce,ge.data):L.texSubImage2D(L.TEXTURE_2D,bt,Lt,oe,At,yt,ce,De,ge);_.pixelStorei(L.UNPACK_ROW_LENGTH,Ve),_.pixelStorei(L.UNPACK_IMAGE_HEIGHT,ie),_.pixelStorei(L.UNPACK_SKIP_PIXELS,Ze),_.pixelStorei(L.UNPACK_SKIP_ROWS,fn),_.pixelStorei(L.UNPACK_SKIP_IMAGES,kn),bt===0&&O.generateMipmaps&&L.generateMipmap(Tt),_.unbindTexture()},this.initRenderTarget=function(S){G.get(S).__webglFramebuffer===void 0&&j.setupRenderTarget(S)},this.initTexture=function(S){S.isCubeTexture?j.setTextureCube(S,0):S.isData3DTexture?j.setTexture3D(S,0):S.isDataArrayTexture||S.isCompressedArrayTexture?j.setTexture2DArray(S,0):j.setTexture2D(S,0),_.unbindTexture()},this.resetState=function(){H=0,W=0,tt=null,_.reset(),Et.reset()},typeof __THREE_DEVTOOLS__<"u"&&__THREE_DEVTOOLS__.dispatchEvent(new CustomEvent("observe",{detail:this}))}get coordinateSystem(){return vn}get outputColorSpace(){return this._outputColorSpace}set outputColorSpace(t){this._outputColorSpace=t;const e=this.getContext();e.drawingBufferColorSpace=Qt._getDrawingBufferColorSpace(t),e.unpackColorSpace=Qt._getUnpackColorSpace()}}class Y0{constructor(){this.renderer=new fh({antialias:!0,alpha:!0}),this.renderer.setPixelRatio(1),this.renderer.setScissorTest(!0),this.renderer.toneMapping=Pr,this.renderer.toneMappingExposure=1.05,this.renderer.setSize(1,1,!1),this.pixelRatio=Math.min(window.devicePixelRatio||1,2),this.width=1,this.height=1,this.views=new Set,this.observer="IntersectionObserver"in window?new IntersectionObserver(t=>t.forEach(e=>{const n=[...this.views].find(s=>s.canvas===e.target);n&&(n.visible=e.isIntersecting,n.needsRender=n.needsRender||e.isIntersecting)}),{rootMargin:"200px"}):null,this.renderer.setAnimationLoop(()=>this.tick())}register(t){var e;return t.context=t.canvas.getContext("2d"),t.visible=!0,t.needsRender=!0,this.views.add(t),(e=this.observer)==null||e.observe(t.canvas),t}unregister(t){var e;t&&((e=this.observer)==null||e.unobserve(t.canvas),this.views.delete(t))}invalidate(t){t&&(t.needsRender=!0)}invalidateAll(){this.views.forEach(t=>{t.needsRender=!0})}ensureSize(t,e){t<=this.width&&e<=this.height||(this.width=Math.max(this.width,t),this.height=Math.max(this.height,e),this.renderer.setSize(this.width,this.height,!1))}tick(){this.views.forEach(t=>{var s,r;if(!t.visible||!t.canvas.isConnected)return;const e=((s=t.controls)==null?void 0:s.update())===!0,n=((r=t.isAnimating)==null?void 0:r.call(t))===!0;!e&&!n&&!t.needsRender||(this.draw(t),t.needsRender=!1)})}draw(t){const e=t.canvas.getBoundingClientRect(),n=Math.max(1,Math.floor(e.width*this.pixelRatio)),s=Math.max(1,Math.floor(e.height*this.pixelRatio));if(n<=1||s<=1)return;if((t.canvas.width!==n||t.canvas.height!==s)&&(t.canvas.width=n,t.canvas.height=s),this.ensureSize(n,s),t.camera.isPerspectiveCamera){const a=n/s;t.camera.aspect!==a&&(t.camera.aspect=a,t.camera.updateProjectionMatrix())}const r=this.height-s;this.renderer.setViewport(0,r,n,s),this.renderer.setScissor(0,r,n,s),this.renderer.render(t.scene,t.camera),t.context.clearRect(0,0,n,s),t.context.drawImage(this.renderer.domElement,0,0,n,s,0,0,n,s)}dispose(){var t;this.renderer.setAnimationLoop(null),(t=this.observer)==null||t.disconnect(),this.views.clear(),this.renderer.dispose()}}const xc={type:"change"},zo={type:"start"},ph={type:"end"},or=new As,vc=new Qn,r_=Math.cos(70*Mu.DEG2RAD),be=new I,Be=2*Math.PI,le={NONE:-1,ROTATE:0,DOLLY:1,PAN:2,TOUCH_ROTATE:3,TOUCH_PAN:4,TOUCH_DOLLY_PAN:5,TOUCH_DOLLY_ROTATE:6},xa=1e-6;class a_ extends ld{constructor(t,e=null){super(t,e),this.state=le.NONE,this.target=new I,this.cursor=new I,this.minDistance=0,this.maxDistance=1/0,this.minZoom=0,this.maxZoom=1/0,this.minTargetRadius=0,this.maxTargetRadius=1/0,this.minPolarAngle=0,this.maxPolarAngle=Math.PI,this.minAzimuthAngle=-1/0,this.maxAzimuthAngle=1/0,this.enableDamping=!1,this.dampingFactor=.05,this.enableZoom=!0,this.zoomSpeed=1,this.enableRotate=!0,this.rotateSpeed=1,this.keyRotateSpeed=1,this.enablePan=!0,this.panSpeed=1,this.screenSpacePanning=!0,this.keyPanSpeed=7,this.zoomToCursor=!1,this.autoRotate=!1,this.autoRotateSpeed=2,this.keys={LEFT:"ArrowLeft",UP:"ArrowUp",RIGHT:"ArrowRight",BOTTOM:"ArrowDown"},this.mouseButtons={LEFT:Vi.ROTATE,MIDDLE:Vi.DOLLY,RIGHT:Vi.PAN},this.touches={ONE:zi.ROTATE,TWO:zi.DOLLY_PAN},this.target0=this.target.clone(),this.position0=this.object.position.clone(),this.zoom0=this.object.zoom,this._cursorStyle="auto",this._domElementKeyEvents=null,this._lastPosition=new I,this._lastQuaternion=new On,this._lastTargetPosition=new I,this._quat=new On().setFromUnitVectors(t.up,new I(0,1,0)),this._quatInverse=this._quat.clone().invert(),this._spherical=new ql,this._sphericalDelta=new ql,this._scale=1,this._panOffset=new I,this._rotateStart=new zt,this._rotateEnd=new zt,this._rotateDelta=new zt,this._panStart=new zt,this._panEnd=new zt,this._panDelta=new zt,this._dollyStart=new zt,this._dollyEnd=new zt,this._dollyDelta=new zt,this._dollyDirection=new I,this._mouse=new zt,this._performCursorZoom=!1,this._pointers=[],this._pointerPositions={},this._controlActive=!1,this._onPointerMove=l_.bind(this),this._onPointerDown=o_.bind(this),this._onPointerUp=c_.bind(this),this._onContextMenu=g_.bind(this),this._onMouseWheel=d_.bind(this),this._onKeyDown=f_.bind(this),this._onTouchStart=p_.bind(this),this._onTouchMove=m_.bind(this),this._onMouseDown=h_.bind(this),this._onMouseMove=u_.bind(this),this._interceptControlDown=__.bind(this),this._interceptControlUp=x_.bind(this),this.domElement!==null&&this.connect(this.domElement),this.update()}set cursorStyle(t){this._cursorStyle=t,t==="grab"?this.domElement.style.cursor="grab":this.domElement.style.cursor="auto"}get cursorStyle(){return this._cursorStyle}connect(t){super.connect(t),this.domElement.addEventListener("pointerdown",this._onPointerDown),this.domElement.addEventListener("pointercancel",this._onPointerUp),this.domElement.addEventListener("contextmenu",this._onContextMenu),this.domElement.addEventListener("wheel",this._onMouseWheel,{passive:!1}),this.domElement.getRootNode().addEventListener("keydown",this._interceptControlDown,{passive:!0,capture:!0}),this.domElement.style.touchAction="none"}disconnect(){this.domElement.removeEventListener("pointerdown",this._onPointerDown),this.domElement.ownerDocument.removeEventListener("pointermove",this._onPointerMove),this.domElement.ownerDocument.removeEventListener("pointerup",this._onPointerUp),this.domElement.removeEventListener("pointercancel",this._onPointerUp),this.domElement.removeEventListener("wheel",this._onMouseWheel),this.domElement.removeEventListener("contextmenu",this._onContextMenu),this.stopListenToKeyEvents(),this.domElement.getRootNode().removeEventListener("keydown",this._interceptControlDown,{capture:!0}),this.domElement.style.touchAction=""}dispose(){this.disconnect()}getPolarAngle(){return this._spherical.phi}getAzimuthalAngle(){return this._spherical.theta}getDistance(){return this.object.position.distanceTo(this.target)}listenToKeyEvents(t){t.addEventListener("keydown",this._onKeyDown),this._domElementKeyEvents=t}stopListenToKeyEvents(){this._domElementKeyEvents!==null&&(this._domElementKeyEvents.removeEventListener("keydown",this._onKeyDown),this._domElementKeyEvents=null)}saveState(){this.target0.copy(this.target),this.position0.copy(this.object.position),this.zoom0=this.object.zoom}reset(){this.target.copy(this.target0),this.object.position.copy(this.position0),this.object.zoom=this.zoom0,this.object.updateProjectionMatrix(),this.dispatchEvent(xc),this.update(),this.state=le.NONE}pan(t,e){this._pan(t,e),this.update()}dollyIn(t){this._dollyIn(t),this.update()}dollyOut(t){this._dollyOut(t),this.update()}rotateLeft(t){this._rotateLeft(t),this.update()}rotateUp(t){this._rotateUp(t),this.update()}update(t=null){const e=this.object.position;be.copy(e).sub(this.target),be.applyQuaternion(this._quat),this._spherical.setFromVector3(be),this.autoRotate&&this.state===le.NONE&&this._rotateLeft(this._getAutoRotationAngle(t)),this.enableDamping?(this._spherical.theta+=this._sphericalDelta.theta*this.dampingFactor,this._spherical.phi+=this._sphericalDelta.phi*this.dampingFactor):(this._spherical.theta+=this._sphericalDelta.theta,this._spherical.phi+=this._sphericalDelta.phi);let n=this.minAzimuthAngle,s=this.maxAzimuthAngle;isFinite(n)&&isFinite(s)&&(n<-Math.PI?n+=Be:n>Math.PI&&(n-=Be),s<-Math.PI?s+=Be:s>Math.PI&&(s-=Be),n<=s?this._spherical.theta=Math.max(n,Math.min(s,this._spherical.theta)):this._spherical.theta=this._spherical.theta>(n+s)/2?Math.max(n,this._spherical.theta):Math.min(s,this._spherical.theta)),this._spherical.phi=Math.max(this.minPolarAngle,Math.min(this.maxPolarAngle,this._spherical.phi)),this._spherical.makeSafe(),this.enableDamping===!0?this.target.addScaledVector(this._panOffset,this.dampingFactor):this.target.add(this._panOffset),this.target.sub(this.cursor),this.target.clampLength(this.minTargetRadius,this.maxTargetRadius),this.target.add(this.cursor);let r=!1;if(this.zoomToCursor&&this._performCursorZoom||this.object.isOrthographicCamera)this._spherical.radius=this._clampDistance(this._spherical.radius);else{const a=this._spherical.radius;this._spherical.radius=this._clampDistance(this._spherical.radius*this._scale),r=a!=this._spherical.radius}if(be.setFromSpherical(this._spherical),be.applyQuaternion(this._quatInverse),e.copy(this.target).add(be),this.object.lookAt(this.target),this.enableDamping===!0?(this._sphericalDelta.theta*=1-this.dampingFactor,this._sphericalDelta.phi*=1-this.dampingFactor,this._panOffset.multiplyScalar(1-this.dampingFactor)):(this._sphericalDelta.set(0,0,0),this._panOffset.set(0,0,0)),this.zoomToCursor&&this._performCursorZoom){let a=null;if(this.object.isPerspectiveCamera){const o=be.length();a=this._clampDistance(o*this._scale);const c=o-a;this.object.position.addScaledVector(this._dollyDirection,c),this.object.updateMatrixWorld(),r=!!c}else if(this.object.isOrthographicCamera){const o=new I(this._mouse.x,this._mouse.y,0);o.unproject(this.object);const c=this.object.zoom;this.object.zoom=Math.max(this.minZoom,Math.min(this.maxZoom,this.object.zoom/this._scale)),this.object.updateProjectionMatrix(),r=c!==this.object.zoom;const l=new I(this._mouse.x,this._mouse.y,0);l.unproject(this.object),this.object.position.sub(l).add(o),this.object.updateMatrixWorld(),a=be.length()}else console.warn("WARNING: OrbitControls.js encountered an unknown camera type - zoom to cursor disabled."),this.zoomToCursor=!1;a!==null&&(this.screenSpacePanning?this.target.set(0,0,-1).transformDirection(this.object.matrix).multiplyScalar(a).add(this.object.position):(or.origin.copy(this.object.position),or.direction.set(0,0,-1).transformDirection(this.object.matrix),Math.abs(this.object.up.dot(or.direction))<r_?this.object.lookAt(this.target):(vc.setFromNormalAndCoplanarPoint(this.object.up,this.target),or.intersectPlane(vc,this.target))))}else if(this.object.isOrthographicCamera){const a=this.object.zoom;this.object.zoom=Math.max(this.minZoom,Math.min(this.maxZoom,this.object.zoom/this._scale)),a!==this.object.zoom&&(this.object.updateProjectionMatrix(),r=!0)}return this._scale=1,this._performCursorZoom=!1,r||this._lastPosition.distanceToSquared(this.object.position)>xa||8*(1-this._lastQuaternion.dot(this.object.quaternion))>xa||this._lastTargetPosition.distanceToSquared(this.target)>xa?(this.dispatchEvent(xc),this._lastPosition.copy(this.object.position),this._lastQuaternion.copy(this.object.quaternion),this._lastTargetPosition.copy(this.target),!0):!1}_getAutoRotationAngle(t){return t!==null?Be/60*this.autoRotateSpeed*t:Be/60/60*this.autoRotateSpeed}_getZoomScale(t){const e=Math.abs(t*.01);return Math.pow(.95,this.zoomSpeed*e)}_rotateLeft(t){this._sphericalDelta.theta-=t}_rotateUp(t){this._sphericalDelta.phi-=t}_panLeft(t,e){be.setFromMatrixColumn(e,0),be.multiplyScalar(-t),this._panOffset.add(be)}_panUp(t,e){this.screenSpacePanning===!0?be.setFromMatrixColumn(e,1):(be.setFromMatrixColumn(e,0),be.crossVectors(this.object.up,be)),be.multiplyScalar(t),this._panOffset.add(be)}_pan(t,e){const n=this.domElement;if(this.object.isPerspectiveCamera){const s=this.object.position;be.copy(s).sub(this.target);let r=be.length();r*=Math.tan(this.object.fov/2*Math.PI/180),this._panLeft(2*t*r/n.clientHeight,this.object.matrix),this._panUp(2*e*r/n.clientHeight,this.object.matrix)}else this.object.isOrthographicCamera?(this._panLeft(t*(this.object.right-this.object.left)/this.object.zoom/n.clientWidth,this.object.matrix),this._panUp(e*(this.object.top-this.object.bottom)/this.object.zoom/n.clientHeight,this.object.matrix)):(console.warn("WARNING: OrbitControls.js encountered an unknown camera type - pan disabled."),this.enablePan=!1)}_dollyOut(t){this.object.isPerspectiveCamera||this.object.isOrthographicCamera?this._scale/=t:(console.warn("WARNING: OrbitControls.js encountered an unknown camera type - dolly/zoom disabled."),this.enableZoom=!1)}_dollyIn(t){this.object.isPerspectiveCamera||this.object.isOrthographicCamera?this._scale*=t:(console.warn("WARNING: OrbitControls.js encountered an unknown camera type - dolly/zoom disabled."),this.enableZoom=!1)}_updateZoomParameters(t,e){if(!this.zoomToCursor)return;this._performCursorZoom=!0;const n=this.domElement.getBoundingClientRect(),s=t-n.left,r=e-n.top,a=n.width,o=n.height;this._mouse.x=s/a*2-1,this._mouse.y=-(r/o)*2+1,this._dollyDirection.set(this._mouse.x,this._mouse.y,1).unproject(this.object).sub(this.object.position).normalize()}_clampDistance(t){return Math.max(this.minDistance,Math.min(this.maxDistance,t))}_handleMouseDownRotate(t){this._rotateStart.set(t.clientX,t.clientY)}_handleMouseDownDolly(t){this._updateZoomParameters(t.clientX,t.clientX),this._dollyStart.set(t.clientX,t.clientY)}_handleMouseDownPan(t){this._panStart.set(t.clientX,t.clientY)}_handleMouseMoveRotate(t){this._rotateEnd.set(t.clientX,t.clientY),this._rotateDelta.subVectors(this._rotateEnd,this._rotateStart).multiplyScalar(this.rotateSpeed);const e=this.domElement;this._rotateLeft(Be*this._rotateDelta.x/e.clientHeight),this._rotateUp(Be*this._rotateDelta.y/e.clientHeight),this._rotateStart.copy(this._rotateEnd),this.update()}_handleMouseMoveDolly(t){this._dollyEnd.set(t.clientX,t.clientY),this._dollyDelta.subVectors(this._dollyEnd,this._dollyStart),this._dollyDelta.y>0?this._dollyOut(this._getZoomScale(this._dollyDelta.y)):this._dollyDelta.y<0&&this._dollyIn(this._getZoomScale(this._dollyDelta.y)),this._dollyStart.copy(this._dollyEnd),this.update()}_handleMouseMovePan(t){this._panEnd.set(t.clientX,t.clientY),this._panDelta.subVectors(this._panEnd,this._panStart).multiplyScalar(this.panSpeed),this._pan(this._panDelta.x,this._panDelta.y),this._panStart.copy(this._panEnd),this.update()}_handleMouseWheel(t){this._updateZoomParameters(t.clientX,t.clientY),t.deltaY<0?this._dollyIn(this._getZoomScale(t.deltaY)):t.deltaY>0&&this._dollyOut(this._getZoomScale(t.deltaY)),this.update()}_handleKeyDown(t){let e=!1;switch(t.code){case this.keys.UP:t.ctrlKey||t.metaKey||t.shiftKey?this.enableRotate&&this._rotateUp(Be*this.keyRotateSpeed/this.domElement.clientHeight):this.enablePan&&this._pan(0,this.keyPanSpeed),e=!0;break;case this.keys.BOTTOM:t.ctrlKey||t.metaKey||t.shiftKey?this.enableRotate&&this._rotateUp(-Be*this.keyRotateSpeed/this.domElement.clientHeight):this.enablePan&&this._pan(0,-this.keyPanSpeed),e=!0;break;case this.keys.LEFT:t.ctrlKey||t.metaKey||t.shiftKey?this.enableRotate&&this._rotateLeft(Be*this.keyRotateSpeed/this.domElement.clientHeight):this.enablePan&&this._pan(this.keyPanSpeed,0),e=!0;break;case this.keys.RIGHT:t.ctrlKey||t.metaKey||t.shiftKey?this.enableRotate&&this._rotateLeft(-Be*this.keyRotateSpeed/this.domElement.clientHeight):this.enablePan&&this._pan(-this.keyPanSpeed,0),e=!0;break}e&&(t.preventDefault(),this.update())}_handleTouchStartRotate(t){if(this._pointers.length===1)this._rotateStart.set(t.pageX,t.pageY);else{const e=this._getSecondPointerPosition(t),n=.5*(t.pageX+e.x),s=.5*(t.pageY+e.y);this._rotateStart.set(n,s)}}_handleTouchStartPan(t){if(this._pointers.length===1)this._panStart.set(t.pageX,t.pageY);else{const e=this._getSecondPointerPosition(t),n=.5*(t.pageX+e.x),s=.5*(t.pageY+e.y);this._panStart.set(n,s)}}_handleTouchStartDolly(t){const e=this._getSecondPointerPosition(t),n=t.pageX-e.x,s=t.pageY-e.y,r=Math.sqrt(n*n+s*s);this._dollyStart.set(0,r)}_handleTouchStartDollyPan(t){this.enableZoom&&this._handleTouchStartDolly(t),this.enablePan&&this._handleTouchStartPan(t)}_handleTouchStartDollyRotate(t){this.enableZoom&&this._handleTouchStartDolly(t),this.enableRotate&&this._handleTouchStartRotate(t)}_handleTouchMoveRotate(t){if(this._pointers.length==1)this._rotateEnd.set(t.pageX,t.pageY);else{const n=this._getSecondPointerPosition(t),s=.5*(t.pageX+n.x),r=.5*(t.pageY+n.y);this._rotateEnd.set(s,r)}this._rotateDelta.subVectors(this._rotateEnd,this._rotateStart).multiplyScalar(this.rotateSpeed);const e=this.domElement;this._rotateLeft(Be*this._rotateDelta.x/e.clientHeight),this._rotateUp(Be*this._rotateDelta.y/e.clientHeight),this._rotateStart.copy(this._rotateEnd)}_handleTouchMovePan(t){if(this._pointers.length===1)this._panEnd.set(t.pageX,t.pageY);else{const e=this._getSecondPointerPosition(t),n=.5*(t.pageX+e.x),s=.5*(t.pageY+e.y);this._panEnd.set(n,s)}this._panDelta.subVectors(this._panEnd,this._panStart).multiplyScalar(this.panSpeed),this._pan(this._panDelta.x,this._panDelta.y),this._panStart.copy(this._panEnd)}_handleTouchMoveDolly(t){const e=this._getSecondPointerPosition(t),n=t.pageX-e.x,s=t.pageY-e.y,r=Math.sqrt(n*n+s*s);this._dollyEnd.set(0,r),this._dollyDelta.set(0,Math.pow(this._dollyEnd.y/this._dollyStart.y,this.zoomSpeed)),this._dollyOut(this._dollyDelta.y),this._dollyStart.copy(this._dollyEnd);const a=(t.pageX+e.x)*.5,o=(t.pageY+e.y)*.5;this._updateZoomParameters(a,o)}_handleTouchMoveDollyPan(t){this.enableZoom&&this._handleTouchMoveDolly(t),this.enablePan&&this._handleTouchMovePan(t)}_handleTouchMoveDollyRotate(t){this.enableZoom&&this._handleTouchMoveDolly(t),this.enableRotate&&this._handleTouchMoveRotate(t)}_addPointer(t){this._pointers.push(t.pointerId)}_removePointer(t){delete this._pointerPositions[t.pointerId];for(let e=0;e<this._pointers.length;e++)if(this._pointers[e]==t.pointerId){this._pointers.splice(e,1);return}}_isTrackingPointer(t){for(let e=0;e<this._pointers.length;e++)if(this._pointers[e]==t.pointerId)return!0;return!1}_trackPointer(t){let e=this._pointerPositions[t.pointerId];e===void 0&&(e=new zt,this._pointerPositions[t.pointerId]=e),e.set(t.pageX,t.pageY)}_getSecondPointerPosition(t){const e=t.pointerId===this._pointers[0]?this._pointers[1]:this._pointers[0];return this._pointerPositions[e]}_customWheelEvent(t){const e=t.deltaMode,n={clientX:t.clientX,clientY:t.clientY,deltaY:t.deltaY};switch(e){case 1:n.deltaY*=16;break;case 2:n.deltaY*=100;break}return t.ctrlKey&&!this._controlActive&&(n.deltaY*=10),n}}function o_(i){this.enabled!==!1&&(this._pointers.length===0&&(this.domElement.setPointerCapture(i.pointerId),this.domElement.ownerDocument.addEventListener("pointermove",this._onPointerMove),this.domElement.ownerDocument.addEventListener("pointerup",this._onPointerUp)),!this._isTrackingPointer(i)&&(this._addPointer(i),i.pointerType==="touch"?this._onTouchStart(i):this._onMouseDown(i),this._cursorStyle==="grab"&&(this.domElement.style.cursor="grabbing")))}function l_(i){this.enabled!==!1&&(i.pointerType==="touch"?this._onTouchMove(i):this._onMouseMove(i))}function c_(i){switch(this._removePointer(i),this._pointers.length){case 0:this.domElement.releasePointerCapture(i.pointerId),this.domElement.ownerDocument.removeEventListener("pointermove",this._onPointerMove),this.domElement.ownerDocument.removeEventListener("pointerup",this._onPointerUp),this.dispatchEvent(ph),this.state=le.NONE,this._cursorStyle==="grab"&&(this.domElement.style.cursor="grab");break;case 1:const t=this._pointers[0],e=this._pointerPositions[t];this._onTouchStart({pointerId:t,pageX:e.x,pageY:e.y});break}}function h_(i){let t;switch(i.button){case 0:t=this.mouseButtons.LEFT;break;case 1:t=this.mouseButtons.MIDDLE;break;case 2:t=this.mouseButtons.RIGHT;break;default:t=-1}switch(t){case Vi.DOLLY:if(this.enableZoom===!1)return;this._handleMouseDownDolly(i),this.state=le.DOLLY;break;case Vi.ROTATE:if(i.ctrlKey||i.metaKey||i.shiftKey){if(this.enablePan===!1)return;this._handleMouseDownPan(i),this.state=le.PAN}else{if(this.enableRotate===!1)return;this._handleMouseDownRotate(i),this.state=le.ROTATE}break;case Vi.PAN:if(i.ctrlKey||i.metaKey||i.shiftKey){if(this.enableRotate===!1)return;this._handleMouseDownRotate(i),this.state=le.ROTATE}else{if(this.enablePan===!1)return;this._handleMouseDownPan(i),this.state=le.PAN}break;default:this.state=le.NONE}this.state!==le.NONE&&this.dispatchEvent(zo)}function u_(i){switch(this.state){case le.ROTATE:if(this.enableRotate===!1)return;this._handleMouseMoveRotate(i);break;case le.DOLLY:if(this.enableZoom===!1)return;this._handleMouseMoveDolly(i);break;case le.PAN:if(this.enablePan===!1)return;this._handleMouseMovePan(i);break}}function d_(i){this.enabled===!1||this.enableZoom===!1||this.state!==le.NONE||(i.preventDefault(),this.dispatchEvent(zo),this._handleMouseWheel(this._customWheelEvent(i)),this.dispatchEvent(ph))}function f_(i){this.enabled!==!1&&this._handleKeyDown(i)}function p_(i){switch(this._trackPointer(i),this._pointers.length){case 1:switch(this.touches.ONE){case zi.ROTATE:if(this.enableRotate===!1)return;this._handleTouchStartRotate(i),this.state=le.TOUCH_ROTATE;break;case zi.PAN:if(this.enablePan===!1)return;this._handleTouchStartPan(i),this.state=le.TOUCH_PAN;break;default:this.state=le.NONE}break;case 2:switch(this.touches.TWO){case zi.DOLLY_PAN:if(this.enableZoom===!1&&this.enablePan===!1)return;this._handleTouchStartDollyPan(i),this.state=le.TOUCH_DOLLY_PAN;break;case zi.DOLLY_ROTATE:if(this.enableZoom===!1&&this.enableRotate===!1)return;this._handleTouchStartDollyRotate(i),this.state=le.TOUCH_DOLLY_ROTATE;break;default:this.state=le.NONE}break;default:this.state=le.NONE}this.state!==le.NONE&&this.dispatchEvent(zo)}function m_(i){switch(this._trackPointer(i),this.state){case le.TOUCH_ROTATE:if(this.enableRotate===!1)return;this._handleTouchMoveRotate(i),this.update();break;case le.TOUCH_PAN:if(this.enablePan===!1)return;this._handleTouchMovePan(i),this.update();break;case le.TOUCH_DOLLY_PAN:if(this.enableZoom===!1&&this.enablePan===!1)return;this._handleTouchMoveDollyPan(i),this.update();break;case le.TOUCH_DOLLY_ROTATE:if(this.enableZoom===!1&&this.enableRotate===!1)return;this._handleTouchMoveDollyRotate(i),this.update();break;default:this.state=le.NONE}}function g_(i){this.enabled!==!1&&i.preventDefault()}function __(i){i.key==="Control"&&(this._controlActive=!0,this.domElement.getRootNode().addEventListener("keyup",this._interceptControlUp,{passive:!0,capture:!0}))}function x_(i){i.key==="Control"&&(this._controlActive=!1,this.domElement.getRootNode().removeEventListener("keyup",this._interceptControlUp,{passive:!0,capture:!0}))}class v_ extends ii{constructor(t){super(t)}load(t,e,n,s){const r=this,a=new Oo(this.manager);a.setPath(this.path),a.setResponseType("arraybuffer"),a.setRequestHeader(this.requestHeader),a.setWithCredentials(this.withCredentials),a.load(t,function(o){try{e(r.parse(o))}catch(c){s?s(c):console.error(c),r.manager.itemError(t)}},n,s)}parse(t){function e(l){const h=new DataView(l),d=32/8*3+32/8*3*3+16/8,u=h.getUint32(80,!0);if(80+32/8+u*d===h.byteLength)return!0;const g=[115,111,108,105,100];for(let v=0;v<5;v++)if(n(g,h,v))return!1;return!0}function n(l,h,d){for(let u=0,p=l.length;u<p;u++)if(l[u]!==h.getUint8(d+u))return!1;return!0}function s(l){const h=new DataView(l),d=h.getUint32(80,!0);let u,p,g,v=!1,m,f,E,w,y;for(let D=0;D<70;D++)h.getUint32(D,!1)==1129270351&&h.getUint8(D+4)==82&&h.getUint8(D+5)==61&&(v=!0,m=new Float32Array(d*3*3),f=h.getUint8(D+6)/255,E=h.getUint8(D+7)/255,w=h.getUint8(D+8)/255,y=h.getUint8(D+9)/255);const b=84,M=50,A=new _e,x=new Float32Array(d*3*3),T=new Float32Array(d*3*3),N=new Ft;for(let D=0;D<d;D++){const B=b+D*M,$=h.getFloat32(B,!0),J=h.getFloat32(B+4,!0),z=h.getFloat32(B+8,!0);if(v){const H=h.getUint16(B+48,!0);(H&32768)===0?(u=(H&31)/31,p=(H>>5&31)/31,g=(H>>10&31)/31):(u=f,p=E,g=w)}for(let H=1;H<=3;H++){const W=B+H*12,tt=D*3*3+(H-1)*3;x[tt]=h.getFloat32(W,!0),x[tt+1]=h.getFloat32(W+4,!0),x[tt+2]=h.getFloat32(W+8,!0),T[tt]=$,T[tt+1]=J,T[tt+2]=z,v&&(N.setRGB(u,p,g,Fe),m[tt]=N.r,m[tt+1]=N.g,m[tt+2]=N.b)}}return A.setAttribute("position",new Pe(x,3)),A.setAttribute("normal",new Pe(T,3)),v&&(A.setAttribute("color",new Pe(m,3)),A.hasColors=!0,A.alpha=y),A}function r(l){const h=new _e,d=/solid([\s\S]*?)endsolid/g,u=/facet([\s\S]*?)endfacet/g,p=/solid\s(.+)/;let g=0;const v=/[\s]+([+-]?(?:\d*)(?:\.\d*)?(?:[eE][+-]?\d+)?)/.source,m=new RegExp("vertex"+v+v+v,"g"),f=new RegExp("normal"+v+v+v,"g"),E=[],w=[],y=[],b=new I;let M,A=0,x=0,T=0;for(;(M=d.exec(l))!==null;){x=T;const N=M[0],D=(M=p.exec(N))!==null?M[1]:"";for(y.push(D);(M=u.exec(N))!==null;){let J=0,z=0;const H=M[0];for(;(M=f.exec(H))!==null;)b.x=parseFloat(M[1]),b.y=parseFloat(M[2]),b.z=parseFloat(M[3]),z++;for(;(M=m.exec(H))!==null;)E.push(parseFloat(M[1]),parseFloat(M[2]),parseFloat(M[3])),w.push(b.x,b.y,b.z),J++,T++;z!==1&&console.error("THREE.STLLoader: Something isn't right with the normal of face number "+g),J!==3&&console.error("THREE.STLLoader: Something isn't right with the vertices of face number "+g),g++}const B=x,$=T-x;h.userData.groupNames=y,h.addGroup(B,$,A),A++}return h.setAttribute("position",new ae(E,3)),h.setAttribute("normal",new ae(w,3)),h}function a(l){return typeof l!="string"?new TextDecoder().decode(l):l}function o(l){if(typeof l=="string"){const h=new Uint8Array(l.length);for(let d=0;d<l.length;d++)h[d]=l.charCodeAt(d)&255;return h.buffer||h}else return l}const c=o(t);return e(c)?s(c):r(a(t))}}const M_=/^[og]\s*(.+)?/,S_=/^mtllib /,y_=/^usemtl /,b_=/^usemap /,Mc=/\s+/,Sc=new I,va=new I,yc=new I,bc=new I,je=new I,lr=new Ft;function E_(){const i={objects:[],object:{},vertices:[],normals:[],colors:[],uvs:[],materials:{},materialLibraries:[],startObject:function(t,e){if(this.object&&this.object.fromDeclaration===!1){this.object.name=t,this.object.fromDeclaration=e!==!1;return}const n=this.object&&typeof this.object.currentMaterial=="function"?this.object.currentMaterial():void 0;if(this.object&&typeof this.object._finalize=="function"&&this.object._finalize(!0),this.object={name:t||"",fromDeclaration:e!==!1,geometry:{vertices:[],normals:[],colors:[],uvs:[],hasUVIndices:!1},materials:[],smooth:!0,startMaterial:function(s,r){const a=this._finalize(!1);a&&(a.inherited||a.groupCount<=0)&&this.materials.splice(a.index,1);const o={index:this.materials.length,name:s||"",mtllib:Array.isArray(r)&&r.length>0?r[r.length-1]:"",smooth:a!==void 0?a.smooth:this.smooth,groupStart:a!==void 0?a.groupEnd:0,groupEnd:-1,groupCount:-1,inherited:!1,clone:function(c){const l={index:typeof c=="number"?c:this.index,name:this.name,mtllib:this.mtllib,smooth:this.smooth,groupStart:0,groupEnd:-1,groupCount:-1,inherited:!1};return l.clone=this.clone.bind(l),l}};return this.materials.push(o),o},currentMaterial:function(){if(this.materials.length>0)return this.materials[this.materials.length-1]},_finalize:function(s){const r=this.currentMaterial();if(r&&r.groupEnd===-1&&(r.groupEnd=this.geometry.vertices.length/3,r.groupCount=r.groupEnd-r.groupStart,r.inherited=!1),s&&this.materials.length>1)for(let a=this.materials.length-1;a>=0;a--)this.materials[a].groupCount<=0&&this.materials.splice(a,1);return s&&this.materials.length===0&&this.materials.push({name:"",smooth:this.smooth}),r}},n&&n.name&&typeof n.clone=="function"){const s=n.clone(0);s.inherited=!0,this.object.materials.push(s)}this.objects.push(this.object)},finalize:function(){this.object&&typeof this.object._finalize=="function"&&this.object._finalize(!0)},parseVertexIndex:function(t,e){const n=parseInt(t,10);return(n>=0?n-1:n+e/3)*3},parseNormalIndex:function(t,e){const n=parseInt(t,10);return(n>=0?n-1:n+e/3)*3},parseUVIndex:function(t,e){const n=parseInt(t,10);return(n>=0?n-1:n+e/2)*2},addVertex:function(t,e,n){const s=this.vertices,r=this.object.geometry.vertices;r.push(s[t+0],s[t+1],s[t+2]),r.push(s[e+0],s[e+1],s[e+2]),r.push(s[n+0],s[n+1],s[n+2])},addVertexPoint:function(t){const e=this.vertices;this.object.geometry.vertices.push(e[t+0],e[t+1],e[t+2])},addVertexLine:function(t){const e=this.vertices;this.object.geometry.vertices.push(e[t+0],e[t+1],e[t+2])},addNormal:function(t,e,n){const s=this.normals,r=this.object.geometry.normals;r.push(s[t+0],s[t+1],s[t+2]),r.push(s[e+0],s[e+1],s[e+2]),r.push(s[n+0],s[n+1],s[n+2])},addFaceNormal:function(t,e,n){const s=this.vertices,r=this.object.geometry.normals;Sc.fromArray(s,t),va.fromArray(s,e),yc.fromArray(s,n),je.subVectors(yc,va),bc.subVectors(Sc,va),je.cross(bc),je.normalize(),r.push(je.x,je.y,je.z),r.push(je.x,je.y,je.z),r.push(je.x,je.y,je.z)},addColor:function(t,e,n){const s=this.colors,r=this.object.geometry.colors;s[t]!==void 0&&r.push(s[t+0],s[t+1],s[t+2]),s[e]!==void 0&&r.push(s[e+0],s[e+1],s[e+2]),s[n]!==void 0&&r.push(s[n+0],s[n+1],s[n+2])},addUV:function(t,e,n){const s=this.uvs,r=this.object.geometry.uvs;r.push(s[t+0],s[t+1]),r.push(s[e+0],s[e+1]),r.push(s[n+0],s[n+1])},addDefaultUV:function(){const t=this.object.geometry.uvs;t.push(0,0),t.push(0,0),t.push(0,0)},addUVLine:function(t){const e=this.uvs;this.object.geometry.uvs.push(e[t+0],e[t+1])},addFace:function(t,e,n,s,r,a,o,c,l){const h=this.vertices.length;let d=this.parseVertexIndex(t,h),u=this.parseVertexIndex(e,h),p=this.parseVertexIndex(n,h);if(this.addVertex(d,u,p),this.addColor(d,u,p),o!==void 0&&o!==""){const g=this.normals.length;d=this.parseNormalIndex(o,g),u=this.parseNormalIndex(c,g),p=this.parseNormalIndex(l,g),this.addNormal(d,u,p)}else this.addFaceNormal(d,u,p);if(s!==void 0&&s!==""){const g=this.uvs.length;d=this.parseUVIndex(s,g),u=this.parseUVIndex(r,g),p=this.parseUVIndex(a,g),this.addUV(d,u,p),this.object.geometry.hasUVIndices=!0}else this.addDefaultUV()},addPointGeometry:function(t){this.object.geometry.type="Points";const e=this.vertices.length;for(let n=0,s=t.length;n<s;n++){const r=this.parseVertexIndex(t[n],e);this.addVertexPoint(r),this.addColor(r)}},addLineGeometry:function(t,e){this.object.geometry.type="Line";const n=this.vertices.length,s=this.uvs.length;for(let r=0,a=t.length;r<a;r++)this.addVertexLine(this.parseVertexIndex(t[r],n));for(let r=0,a=e.length;r<a;r++)this.addUVLine(this.parseUVIndex(e[r],s))}};return i.startObject("",!1),i}class T_ extends ii{constructor(t){super(t),this.materials=null}load(t,e,n,s){const r=this,a=new Oo(this.manager);a.setPath(this.path),a.setRequestHeader(this.requestHeader),a.setWithCredentials(this.withCredentials),a.load(t,function(o){try{e(r.parse(o))}catch(c){s?s(c):console.error(c),r.manager.itemError(t)}},n,s)}setMaterials(t){return this.materials=t,this}parse(t){const e=new E_;t.indexOf(`\r
`)!==-1&&(t=t.replace(/\r\n/g,`
`)),t.indexOf(`\\
`)!==-1&&(t=t.replace(/\\\n/g,""));const n=t.split(`
`);let s=[];for(let o=0,c=n.length;o<c;o++){const l=n[o].trimStart();if(l.length===0)continue;const h=l.charAt(0);if(h!=="#")if(h==="v"){const d=l.split(Mc);switch(d[0]){case"v":e.vertices.push(parseFloat(d[1]),parseFloat(d[2]),parseFloat(d[3])),d.length>=7?(lr.setRGB(parseFloat(d[4]),parseFloat(d[5]),parseFloat(d[6]),Fe),e.colors.push(lr.r,lr.g,lr.b)):e.colors.push(void 0,void 0,void 0);break;case"vn":e.normals.push(parseFloat(d[1]),parseFloat(d[2]),parseFloat(d[3]));break;case"vt":e.uvs.push(parseFloat(d[1]),parseFloat(d[2]));break}}else if(h==="f"){const u=l.slice(1).trim().split(Mc),p=[];for(let v=0,m=u.length;v<m;v++){const f=u[v];if(f.length>0){const E=f.split("/");p.push(E)}}const g=p[0];for(let v=1,m=p.length-1;v<m;v++){const f=p[v],E=p[v+1];e.addFace(g[0],f[0],E[0],g[1],f[1],E[1],g[2],f[2],E[2])}}else if(h==="l"){const d=l.substring(1).trim().split(" ");let u=[];const p=[];if(l.indexOf("/")===-1)u=d;else for(let g=0,v=d.length;g<v;g++){const m=d[g].split("/");m[0]!==""&&u.push(m[0]),m[1]!==""&&p.push(m[1])}e.addLineGeometry(u,p)}else if(h==="p"){const u=l.slice(1).trim().split(" ");e.addPointGeometry(u)}else if((s=M_.exec(l))!==null){const d=(" "+s[0].slice(1).trim()).slice(1);e.startObject(d)}else if(y_.test(l))e.object.startMaterial(l.substring(7).trim(),e.materialLibraries);else if(S_.test(l))e.materialLibraries.push(l.substring(7).trim());else if(b_.test(l))console.warn('THREE.OBJLoader: Rendering identifier "usemap" not supported. Textures must be defined in MTL files.');else if(h==="s"){if(s=l.split(" "),s.length>1){const u=s[1].trim().toLowerCase();e.object.smooth=u!=="0"&&u!=="off"}else e.object.smooth=!0;const d=e.object.currentMaterial();d&&(d.smooth=e.object.smooth)}else{if(l==="\0")continue;console.warn('THREE.OBJLoader: Unexpected line: "'+l+'"')}}e.finalize();const r=new $e;if(r.materialLibraries=[].concat(e.materialLibraries),!(e.objects.length===1&&e.objects[0].geometry.vertices.length===0)===!0)for(let o=0,c=e.objects.length;o<c;o++){const l=e.objects[o],h=l.geometry,d=l.materials,u=h.type==="Line",p=h.type==="Points";let g=!1;if(h.vertices.length===0)continue;const v=new _e;v.setAttribute("position",new ae(h.vertices,3)),h.normals.length>0&&v.setAttribute("normal",new ae(h.normals,3)),h.colors.length>0&&(g=!0,v.setAttribute("color",new ae(h.colors,3))),h.hasUVIndices===!0&&v.setAttribute("uv",new ae(h.uvs,2));const m=[];for(let E=0,w=d.length;E<w;E++){const y=d[E],b=y.name+"_"+y.smooth+"_"+g;let M=e.materials[b];if(this.materials!==null){if(M=this.materials.create(y.name),u&&M&&!(M instanceof gi)){const A=new gi;bn.prototype.copy.call(A,M),A.color.copy(M.color),M=A}else if(p&&M&&!(M instanceof us)){const A=new us({size:10,sizeAttenuation:!1});bn.prototype.copy.call(A,M),A.color.copy(M.color),A.map=M.map,M=A}}M===void 0&&(u?M=new gi:p?M=new us({size:1,sizeAttenuation:!1}):M=new ds,M.name=y.name,M.flatShading=!y.smooth,M.vertexColors=g,e.materials[b]=M),m.push(M)}let f;if(m.length>1){for(let E=0,w=d.length;E<w;E++){const y=d[E];v.addGroup(y.groupStart,y.groupCount,E)}u?f=new bs(v,m):p?f=new ca(v,m):f=new Me(v,m)}else u?f=new bs(v,m[0]):p?f=new ca(v,m[0]):f=new Me(v,m[0]);f.name=l.name,r.add(f)}else if(e.vertices.length>0){const o=new us({size:1,sizeAttenuation:!1}),c=new _e;c.setAttribute("position",new ae(e.vertices,3)),e.colors.length>0&&e.colors[0]!==void 0&&(c.setAttribute("color",new ae(e.colors,3)),o.vertexColors=!0);const l=new ca(c,o);r.add(l)}return r}}/*!
fflate - fast JavaScript compression/decompression
<https://101arrowz.github.io/fflate>
Licensed under MIT. https://github.com/101arrowz/fflate/blob/master/LICENSE
version 0.8.2
*/var Ye=Uint8Array,ki=Uint16Array,A_=Int32Array,mh=new Ye([0,0,0,0,0,0,0,0,1,1,1,1,2,2,2,2,3,3,3,3,4,4,4,4,5,5,5,5,0,0,0,0]),gh=new Ye([0,0,0,0,1,1,2,2,3,3,4,4,5,5,6,6,7,7,8,8,9,9,10,10,11,11,12,12,13,13,0,0]),w_=new Ye([16,17,18,0,8,7,9,6,10,5,11,4,12,3,13,2,14,1,15]),_h=function(i,t){for(var e=new ki(31),n=0;n<31;++n)e[n]=t+=1<<i[n-1];for(var s=new A_(e[30]),n=1;n<30;++n)for(var r=e[n];r<e[n+1];++r)s[r]=r-e[n]<<5|n;return{b:e,r:s}},xh=_h(mh,2),vh=xh.b,R_=xh.r;vh[28]=258,R_[258]=28;var C_=_h(gh,0),P_=C_.b,So=new ki(32768);for(var fe=0;fe<32768;++fe){var Kn=(fe&43690)>>1|(fe&21845)<<1;Kn=(Kn&52428)>>2|(Kn&13107)<<2,Kn=(Kn&61680)>>4|(Kn&3855)<<4,So[fe]=((Kn&65280)>>8|(Kn&255)<<8)>>1}var _s=(function(i,t,e){for(var n=i.length,s=0,r=new ki(t);s<n;++s)i[s]&&++r[i[s]-1];var a=new ki(t);for(s=1;s<t;++s)a[s]=a[s-1]+r[s-1]<<1;var o;if(e){o=new ki(1<<t);var c=15-t;for(s=0;s<n;++s)if(i[s])for(var l=s<<4|i[s],h=t-i[s],d=a[i[s]-1]++<<h,u=d|(1<<h)-1;d<=u;++d)o[So[d]>>c]=l}else for(o=new ki(n),s=0;s<n;++s)i[s]&&(o[s]=So[a[i[s]-1]++]>>15-i[s]);return o}),Rs=new Ye(288);for(var fe=0;fe<144;++fe)Rs[fe]=8;for(var fe=144;fe<256;++fe)Rs[fe]=9;for(var fe=256;fe<280;++fe)Rs[fe]=7;for(var fe=280;fe<288;++fe)Rs[fe]=8;var Mh=new Ye(32);for(var fe=0;fe<32;++fe)Mh[fe]=5;var D_=_s(Rs,9,1),L_=_s(Mh,5,1),Ma=function(i){for(var t=i[0],e=1;e<i.length;++e)i[e]>t&&(t=i[e]);return t},an=function(i,t,e){var n=t/8|0;return(i[n]|i[n+1]<<8)>>(t&7)&e},Sa=function(i,t){var e=t/8|0;return(i[e]|i[e+1]<<8|i[e+2]<<16)>>(t&7)},I_=function(i){return(i+7)/8|0},ko=function(i,t,e){return(t==null||t<0)&&(t=0),(e==null||e>i.length)&&(e=i.length),new Ye(i.subarray(t,e))},N_=["unexpected EOF","invalid block type","invalid length/literal","invalid distance","stream finished","no stream handler",,"no callback","invalid UTF-8 data","extra field too long","date not in range 1980-2099","filename too long","stream finishing","invalid zip data"],tn=function(i,t,e){var n=new Error(t||N_[i]);if(n.code=i,Error.captureStackTrace&&Error.captureStackTrace(n,tn),!e)throw n;return n},U_=function(i,t,e,n){var s=i.length,r=n?n.length:0;if(!s||t.f&&!t.l)return e||new Ye(0);var a=!e,o=a||t.i!=2,c=t.i;a&&(e=new Ye(s*3));var l=function(V){var at=e.length;if(V>at){var st=new Ye(Math.max(at*2,V));st.set(e),e=st}},h=t.f||0,d=t.p||0,u=t.b||0,p=t.l,g=t.d,v=t.m,m=t.n,f=s*8;do{if(!p){h=an(i,d,1);var E=an(i,d+1,3);if(d+=3,E)if(E==1)p=D_,g=L_,v=9,m=5;else if(E==2){var M=an(i,d,31)+257,A=an(i,d+10,15)+4,x=M+an(i,d+5,31)+1;d+=14;for(var T=new Ye(x),N=new Ye(19),D=0;D<A;++D)N[w_[D]]=an(i,d+D*3,7);d+=A*3;for(var B=Ma(N),$=(1<<B)-1,J=_s(N,B,1),D=0;D<x;){var z=J[an(i,d,$)];d+=z&15;var w=z>>4;if(w<16)T[D++]=w;else{var H=0,W=0;for(w==16?(W=3+an(i,d,3),d+=2,H=T[D-1]):w==17?(W=3+an(i,d,7),d+=3):w==18&&(W=11+an(i,d,127),d+=7);W--;)T[D++]=H}}var tt=T.subarray(0,M),Q=T.subarray(M);v=Ma(tt),m=Ma(Q),p=_s(tt,v,1),g=_s(Q,m,1)}else tn(1);else{var w=I_(d)+4,y=i[w-4]|i[w-3]<<8,b=w+y;if(b>s){c&&tn(0);break}o&&l(u+y),e.set(i.subarray(w,b),u),t.b=u+=y,t.p=d=b*8,t.f=h;continue}if(d>f){c&&tn(0);break}}o&&l(u+131072);for(var ht=(1<<v)-1,ut=(1<<m)-1,St=d;;St=d){var H=p[Sa(i,d)&ht],Bt=H>>4;if(d+=H&15,d>f){c&&tn(0);break}if(H||tn(2),Bt<256)e[u++]=Bt;else if(Bt==256){St=d,p=null;break}else{var Yt=Bt-254;if(Bt>264){var D=Bt-257,Vt=mh[D];Yt=an(i,d,(1<<Vt)-1)+vh[D],d+=Vt}var C=g[Sa(i,d)&ut],P=C>>4;C||tn(3),d+=C&15;var Q=P_[P];if(P>3){var Vt=gh[P];Q+=Sa(i,d)&(1<<Vt)-1,d+=Vt}if(d>f){c&&tn(0);break}o&&l(u+131072);var U=u+Yt;if(u<Q){var q=r-Q,Z=Math.min(Q,U);for(q+u<0&&tn(3);u<Z;++u)e[u]=n[q+u]}for(;u<U;++u)e[u]=e[u-Q]}}t.l=p,t.p=St,t.b=u,t.f=h,p&&(h=1,t.m=v,t.d=g,t.n=m)}while(!h);return u!=e.length&&a?ko(e,0,u):e.subarray(0,u)},F_=new Ye(0),xn=function(i,t){return i[t]|i[t+1]<<8},on=function(i,t){return(i[t]|i[t+1]<<8|i[t+2]<<16|i[t+3]<<24)>>>0},ya=function(i,t){return on(i,t)+on(i,t+4)*4294967296};function O_(i,t){return U_(i,{i:2},t&&t.out,t&&t.dictionary)}var yo=typeof TextDecoder<"u"&&new TextDecoder,B_=0;try{yo.decode(F_,{stream:!0}),B_=1}catch{}var z_=function(i){for(var t="",e=0;;){var n=i[e++],s=(n>127)+(n>223)+(n>239);if(e+s>i.length)return{s:t,r:ko(i,e-1)};s?s==3?(n=((n&15)<<18|(i[e++]&63)<<12|(i[e++]&63)<<6|i[e++]&63)-65536,t+=String.fromCharCode(55296|n>>10,56320|n&1023)):s&1?t+=String.fromCharCode((n&31)<<6|i[e++]&63):t+=String.fromCharCode((n&15)<<12|(i[e++]&63)<<6|i[e++]&63):t+=String.fromCharCode(n)}};function k_(i,t){if(t){for(var e="",n=0;n<i.length;n+=16384)e+=String.fromCharCode.apply(null,i.subarray(n,n+16384));return e}else{if(yo)return yo.decode(i);var s=z_(i),r=s.s,e=s.r;return e.length&&tn(8),r}}var V_=function(i,t){return t+30+xn(i,t+26)+xn(i,t+28)},G_=function(i,t,e){var n=xn(i,t+28),s=k_(i.subarray(t+46,t+46+n),!(xn(i,t+8)&2048)),r=t+46+n,a=on(i,t+20),o=e&&a==4294967295?H_(i,r):[a,on(i,t+24),on(i,t+42)],c=o[0],l=o[1],h=o[2];return[xn(i,t+10),c,l,s,r+xn(i,t+30)+xn(i,t+32),h]},H_=function(i,t){for(;xn(i,t)!=1;t+=4+xn(i,t+2));return[ya(i,t+12),ya(i,t+4),ya(i,t+20)]};function W_(i,t){for(var e={},n=i.length-22;on(i,n)!=101010256;--n)(!n||i.length-n>65558)&&tn(13);var s=xn(i,n+8);if(!s)return{};var r=on(i,n+16),a=r==4294967295||s==65535;if(a){var o=on(i,n-12);a=on(i,o)==101075792,a&&(s=on(i,o+32),r=on(i,o+48))}for(var c=0;c<s;++c){var l=G_(i,r,a),h=l[0],d=l[1],u=l[2],p=l[3],g=l[4],v=l[5],m=V_(i,v);r=g,h?h==8?e[p]=O_(i.subarray(m,m+d),{out:new Ye(u)}):tn(14,"unknown compression type "+h):e[p]=ko(i,m,m+d)}return e}const ba=Fe;class X_ extends ii{constructor(t){super(t),this.availableExtensions=[]}load(t,e,n,s){const r=this,a=new Oo(r.manager);a.setPath(r.path),a.setResponseType("arraybuffer"),a.setRequestHeader(r.requestHeader),a.setWithCredentials(r.withCredentials),a.load(t,function(o){try{e(r.parse(o))}catch(c){s?s(c):console.error(c),r.manager.itemError(t)}},n,s)}parse(t){const e=this,n=new nd(this.manager);function s(C){let P=null,U=null,q,Z;const V=[],at=[];let st;const rt={},lt={},ft={},ct=new TextDecoder;try{P=W_(new Uint8Array(C))}catch(It){if(It instanceof ReferenceError)return console.error("THREE.3MFLoader: fflate missing and file is compressed."),null}let te=null;for(U in P)U.match(/\_rels\/.rels$/)?q=U:U.match(/3D\/_rels\/.*\.model\.rels$/)?Z=U:U.match(/^3D\/[^\/]*\.model$/)?te=U:U.match(/^3D\/.*\/.*\.model$/)?V.push(U):U.match(/^3D\/Textures?\/.*/)&&at.push(U);if(V.push(te),q===void 0)throw new Error("THREE.ThreeMFLoader: Cannot find relationship file `rels` in 3MF archive.");const Gt=P[q],Jt=ct.decode(Gt),Kt=r(Jt);if(Z){const It=P[Z],L=ct.decode(It);st=r(L)}for(let It=0;It<V.length;It++){const L=V[It],ne=P[L],kt=ct.decode(ne),R=new DOMParser().parseFromString(kt,"application/xml");R.documentElement.nodeName.toLowerCase()!=="model"&&console.error("THREE.3MFLoader: Error loading 3MF - no 3MF document found: ",L);const _=R.querySelector("model"),k={};for(let j=0;j<_.attributes.length;j++){const dt=_.attributes[j];dt.name.match(/^xmlns:(.+)$/)&&(k[dt.value]=RegExp.$1)}const G=M(_);G.xml=_,0<Object.keys(k).length&&(G.extensions=k),rt[L]=G}for(let It=0;It<at.length;It++){const L=at[It];ft[L]=P[L].buffer}return{rels:Kt,modelRels:st,model:rt,printTicket:lt,texture:ft}}function r(C){const P=[],q=new DOMParser().parseFromString(C,"application/xml").querySelectorAll("Relationship");for(let Z=0;Z<q.length;Z++){const V=q[Z],at={target:V.getAttribute("Target"),id:V.getAttribute("Id"),type:V.getAttribute("Type")};P.push(at)}return P}function a(C){const P={};for(let U=0;U<C.length;U++){const q=C[U],Z=q.getAttribute("name");0<=["Title","Designer","Description","Copyright","LicenseTerms","Rating","CreationDate","ModificationDate"].indexOf(Z)&&(P[Z]=q.textContent)}return P}function o(C){const P={id:C.getAttribute("id"),basematerials:[]},U=C.querySelectorAll("base");for(let q=0;q<U.length;q++){const Z=U[q],V=g(Z);V.index=q,P.basematerials.push(V)}return P}function c(C){return{id:C.getAttribute("id"),path:C.getAttribute("path"),contenttype:C.getAttribute("contenttype"),tilestyleu:C.getAttribute("tilestyleu"),tilestylev:C.getAttribute("tilestylev"),filter:C.getAttribute("filter")}}function l(C){const P={id:C.getAttribute("id"),texid:C.getAttribute("texid"),displaypropertiesid:C.getAttribute("displaypropertiesid")},U=C.querySelectorAll("tex2coord"),q=[];for(let Z=0;Z<U.length;Z++){const V=U[Z],at=V.getAttribute("u"),st=V.getAttribute("v");q.push(parseFloat(at),parseFloat(st))}return P.uvs=new Float32Array(q),P}function h(C){const P={id:C.getAttribute("id"),displaypropertiesid:C.getAttribute("displaypropertiesid")},U=C.querySelectorAll("color"),q=[],Z=new Ft;for(let V=0;V<U.length;V++){const st=U[V].getAttribute("color");Z.setStyle(st.substring(0,7),ba),q.push(Z.r,Z.g,Z.b)}return P.colors=new Float32Array(q),P}function d(C){const P=C.children,U={};for(let q=0;q<P.length;q++){const Z={type:P[q].nodeName.substring(2)};for(let V=0;V<P[q].attributes.length;V++){const at=P[q].attributes[V];at.specified&&(Z[at.name]=at.value)}U[P[q].getAttribute("identifier")]=Z}return U}function u(C){const P={id:C.getAttribute("id"),displayname:C.getAttribute("displayname")},U=C.children,q={};for(let Z=0;Z<U.length;Z++){const V=U[Z];if(V.nodeName==="i:in"||V.nodeName==="i:out")q[V.nodeName==="i:in"?"inputs":"outputs"]=d(V);else{const at=V.children,st={op:V.nodeName.substring(2),identifier:V.getAttribute("identifier")};for(let rt=0;rt<at.length;rt++)st[at[rt].nodeName.substring(2)]=d(at[rt]);q[st.identifier]=st}}return P.operations=q,P}function p(C){const P={id:C.getAttribute("id")},U=C.querySelectorAll("pbmetallic"),q=[];for(let Z=0;Z<U.length;Z++){const V=U[Z];q.push({name:V.getAttribute("name"),metallicness:parseFloat(V.getAttribute("metallicness")),roughness:parseFloat(V.getAttribute("roughness"))})}return P.data=q,P}function g(C){const P={};return P.name=C.getAttribute("name"),P.displaycolor=C.getAttribute("displaycolor"),P.displaypropertiesid=C.getAttribute("displaypropertiesid"),P}function v(C){const P={},U=[],q=C.querySelectorAll("vertices vertex");for(let st=0;st<q.length;st++){const rt=q[st],lt=rt.getAttribute("x"),ft=rt.getAttribute("y"),ct=rt.getAttribute("z");U.push(parseFloat(lt),parseFloat(ft),parseFloat(ct))}P.vertices=new Float32Array(U);const Z=[],V=[],at=C.querySelectorAll("triangles triangle");for(let st=0;st<at.length;st++){const rt=at[st],lt=rt.getAttribute("v1"),ft=rt.getAttribute("v2"),ct=rt.getAttribute("v3"),te=rt.getAttribute("p1"),Gt=rt.getAttribute("p2"),Jt=rt.getAttribute("p3"),Kt=rt.getAttribute("pid"),It={};It.v1=parseInt(lt,10),It.v2=parseInt(ft,10),It.v3=parseInt(ct,10),V.push(It.v1,It.v2,It.v3),te&&(It.p1=parseInt(te,10)),Gt&&(It.p2=parseInt(Gt,10)),Jt&&(It.p3=parseInt(Jt,10)),Kt&&(It.pid=Kt),0<Object.keys(It).length&&Z.push(It)}return P.triangleProperties=Z,P.triangles=new Uint32Array(V),P}function m(C){const P=[],U=C.querySelectorAll("component");for(let q=0;q<U.length;q++){const Z=U[q],V=f(Z);P.push(V)}return P}function f(C){const P={};P.objectId=C.getAttribute("objectid");const U=C.getAttribute("transform");return U&&(P.transform=E(U)),P}function E(C){const P=[];C.split(" ").forEach(function(q){P.push(parseFloat(q))});const U=new se;return U.set(P[0],P[3],P[6],P[9],P[1],P[4],P[7],P[10],P[2],P[5],P[8],P[11],0,0,0,1),U}function w(C){const P={type:C.getAttribute("type")},U=C.getAttribute("id");U&&(P.id=U);const q=C.getAttribute("pid");q&&(P.pid=q);const Z=C.getAttribute("pindex");Z&&(P.pindex=Z);const V=C.getAttribute("thumbnail");V&&(P.thumbnail=V);const at=C.getAttribute("partnumber");at&&(P.partnumber=at);const st=C.getAttribute("name");st&&(P.name=st);const rt=C.querySelector("mesh");rt&&(P.mesh=v(rt));const lt=C.querySelector("components");return lt&&(P.components=m(lt)),P}function y(C){const P={};P.basematerials={};const U=C.querySelectorAll("basematerials");for(let lt=0;lt<U.length;lt++){const ft=U[lt],ct=o(ft);P.basematerials[ct.id]=ct}P.texture2d={};const q=C.querySelectorAll("texture2d");for(let lt=0;lt<q.length;lt++){const ft=q[lt],ct=c(ft);P.texture2d[ct.id]=ct}P.colorgroup={};const Z=C.querySelectorAll("colorgroup");for(let lt=0;lt<Z.length;lt++){const ft=Z[lt],ct=h(ft);P.colorgroup[ct.id]=ct}const V=C.querySelectorAll("implicitfunction");V.length>0&&(P.implicitfunction={});for(let lt=0;lt<V.length;lt++){const ft=V[lt],ct=u(ft);P.implicitfunction[ct.id]=ct}P.pbmetallicdisplayproperties={};const at=C.querySelectorAll("pbmetallicdisplayproperties");for(let lt=0;lt<at.length;lt++){const ft=at[lt],ct=p(ft);P.pbmetallicdisplayproperties[ct.id]=ct}P.texture2dgroup={};const st=C.querySelectorAll("texture2dgroup");for(let lt=0;lt<st.length;lt++){const ft=st[lt],ct=l(ft);P.texture2dgroup[ct.id]=ct}P.object={};const rt=C.querySelectorAll("object");for(let lt=0;lt<rt.length;lt++){const ft=rt[lt],ct=w(ft);P.object[ct.id]=ct}return P}function b(C){const P=[],U=C.querySelectorAll("item");for(let q=0;q<U.length;q++){const Z=U[q],V={objectId:Z.getAttribute("objectid")},at=Z.getAttribute("transform");at&&(V.transform=E(at)),P.push(V)}return P}function M(C){const P={unit:C.getAttribute("unit")||"millimeter"},U=C.querySelectorAll("metadata");U&&(P.metadata=a(U));const q=C.querySelector("resources");q&&(P.resources=y(q));const Z=C.querySelector("build");return Z&&(P.build=b(Z)),P}function A(C,P,U,q){const Z=C.texid,at=U.resources.texture2d[Z];if(at){const st=q[at.path],rt=at.contenttype,lt=new Blob([st],{type:rt}),ft=URL.createObjectURL(lt),ct=n.load(ft,function(){URL.revokeObjectURL(ft)});switch(ct.colorSpace=ba,at.tilestyleu){case"wrap":ct.wrapS=pi;break;case"mirror":ct.wrapS=xs;break;case"none":case"clamp":ct.wrapS=ln;break;default:ct.wrapS=pi}switch(at.tilestylev){case"wrap":ct.wrapT=pi;break;case"mirror":ct.wrapT=xs;break;case"none":case"clamp":ct.wrapT=ln;break;default:ct.wrapT=pi}switch(at.filter){case"auto":ct.magFilter=ye,ct.minFilter=Ln;break;case"linear":ct.magFilter=ye,ct.minFilter=ye,ct.generateMipmaps=!1;break;case"nearest":ct.magFilter=Ee,ct.minFilter=Ee,ct.generateMipmaps=!1;break;default:ct.magFilter=ye,ct.minFilter=Ln}return ct}else return null}function x(C,P,U,q,Z,V,at){const st=at.pindex,rt={};for(let ct=0,te=P.length;ct<te;ct++){const Gt=P[ct],Jt=Gt.p1!==void 0?Gt.p1:st;rt[Jt]===void 0&&(rt[Jt]=[]),rt[Jt].push(Gt)}const lt=Object.keys(rt),ft=[];for(let ct=0,te=lt.length;ct<te;ct++){const Gt=lt[ct],Jt=rt[Gt],Kt=C.basematerials[Gt],It=W(Kt,q,Z,V,at,tt),L=new _e,ne=[],kt=U.vertices;for(let _=0,k=Jt.length;_<k;_++){const G=Jt[_];ne.push(kt[G.v1*3+0]),ne.push(kt[G.v1*3+1]),ne.push(kt[G.v1*3+2]),ne.push(kt[G.v2*3+0]),ne.push(kt[G.v2*3+1]),ne.push(kt[G.v2*3+2]),ne.push(kt[G.v3*3+0]),ne.push(kt[G.v3*3+1]),ne.push(kt[G.v3*3+2])}L.setAttribute("position",new ae(ne,3));const R=new Me(L,It);ft.push(R)}return ft}function T(C,P,U,q,Z,V,at){const st=new _e,rt=[],lt=[],ft=U.vertices,ct=C.uvs;for(let Kt=0,It=P.length;Kt<It;Kt++){const L=P[Kt];rt.push(ft[L.v1*3+0]),rt.push(ft[L.v1*3+1]),rt.push(ft[L.v1*3+2]),rt.push(ft[L.v2*3+0]),rt.push(ft[L.v2*3+1]),rt.push(ft[L.v2*3+2]),rt.push(ft[L.v3*3+0]),rt.push(ft[L.v3*3+1]),rt.push(ft[L.v3*3+2]),lt.push(ct[L.p1*2+0]),lt.push(ct[L.p1*2+1]),lt.push(ct[L.p2*2+0]),lt.push(ct[L.p2*2+1]),lt.push(ct[L.p3*2+0]),lt.push(ct[L.p3*2+1])}st.setAttribute("position",new ae(rt,3)),st.setAttribute("uv",new ae(lt,2));const te=W(C,q,Z,V,at,A),Gt=new ds({map:te,flatShading:!0});return new Me(st,Gt)}function N(C,P,U,q){const Z=new _e,V=[],at=[],st=U.vertices,rt=C.colors;for(let ct=0,te=P.length;ct<te;ct++){const Gt=P[ct],Jt=Gt.v1,Kt=Gt.v2,It=Gt.v3;V.push(st[Jt*3+0]),V.push(st[Jt*3+1]),V.push(st[Jt*3+2]),V.push(st[Kt*3+0]),V.push(st[Kt*3+1]),V.push(st[Kt*3+2]),V.push(st[It*3+0]),V.push(st[It*3+1]),V.push(st[It*3+2]);const L=Gt.p1!==void 0?Gt.p1:q.pindex,ne=Gt.p2!==void 0?Gt.p2:L,kt=Gt.p3!==void 0?Gt.p3:L;at.push(rt[L*3+0]),at.push(rt[L*3+1]),at.push(rt[L*3+2]),at.push(rt[ne*3+0]),at.push(rt[ne*3+1]),at.push(rt[ne*3+2]),at.push(rt[kt*3+0]),at.push(rt[kt*3+1]),at.push(rt[kt*3+2])}Z.setAttribute("position",new ae(V,3)),Z.setAttribute("color",new ae(at,3));const lt=new ds({vertexColors:!0,flatShading:!0});return new Me(Z,lt)}function D(C){const P=new _e;P.setIndex(new Pe(C.triangles,1)),P.setAttribute("position",new Pe(C.vertices,3));const U=new ds({name:ii.DEFAULT_MATERIAL_NAME,color:16777215,flatShading:!0});return new Me(P,U)}function B(C,P,U,q,Z,V){const at=Object.keys(C),st=[];for(let rt=0,lt=at.length;rt<lt;rt++){const ft=at[rt],ct=C[ft];switch($(ft,q)){case"material":const Gt=q.resources.basematerials[ft],Jt=x(Gt,ct,P,U,q,Z,V);for(let L=0,ne=Jt.length;L<ne;L++)st.push(Jt[L]);break;case"texture":const Kt=q.resources.texture2dgroup[ft];st.push(T(Kt,ct,P,U,q,Z,V));break;case"vertexColors":const It=q.resources.colorgroup[ft];st.push(N(It,ct,P,V));break;case"default":st.push(D(P));break;default:console.error("THREE.3MFLoader: Unsupported resource type.")}}if(V.name)for(let rt=0;rt<st.length;rt++)st[rt].name=V.name;return st}function $(C,P){return P.resources.texture2dgroup[C]!==void 0?"texture":P.resources.basematerials[C]!==void 0?"material":P.resources.colorgroup[C]!==void 0?"vertexColors":C==="default"?"default":void 0}function J(C,P){const U={},q=C.triangleProperties,Z=P.pid;for(let V=0,at=q.length;V<at;V++){const st=q[V];let rt=st.pid!==void 0?st.pid:Z;rt===void 0&&(rt="default"),U[rt]===void 0&&(U[rt]=[]),U[rt].push(st)}return U}function z(C,P,U,q,Z){const V=new $e,at=J(C,Z),st=B(at,C,P,U,q,Z);for(let rt=0,lt=st.length;rt<lt;rt++)V.add(st[rt]);return V}function H(C,P,U){if(!C)return;const q=[],Z=Object.keys(C);for(let V=0;V<Z.length;V++){const at=Z[V];for(let st=0;st<e.availableExtensions.length;st++){const rt=e.availableExtensions[st];rt.ns===at&&q.push(rt)}}for(let V=0;V<q.length;V++){const at=q[V];at.apply(U,C[at.ns],P)}}function W(C,P,U,q,Z,V){return C.build!==void 0||(C.build=V(C,P,U,q,Z)),C.build}function tt(C,P,U){let q;const Z=C.displaypropertiesid,V=U.resources.pbmetallicdisplayproperties;if(Z!==null&&V[Z]!==void 0){const lt=V[Z].data[C.index];q=new Ki({flatShading:!0,roughness:lt.roughness,metalness:lt.metallicness})}else q=new ds({flatShading:!0});q.name=C.name;const at=C.displaycolor,st=at.substring(0,7);return q.color.setStyle(st,ba),at.length===9&&(q.opacity=parseInt(at.charAt(7)+at.charAt(8),16)/255),q}function Q(C,P,U,q){const Z=new $e;for(let V=0;V<C.length;V++){const at=C[V];let st=P[at.objectId];st===void 0&&(ht(at.objectId,P,U,q),st=P[at.objectId]);const rt=st.clone(),lt=at.transform;lt&&rt.applyMatrix4(lt),Z.add(rt)}return Z}function ht(C,P,U,q){const Z=U.resources.object[C];if(Z.mesh){const V=Z.mesh,at=U.extensions,st=U.xml;H(at,V,st),P[Z.id]=W(V,P,U,q,Z,z)}else{const V=Z.components;P[Z.id]=W(V,P,U,q,Z,Q)}Z.name&&(P[Z.id].name=Z.name),U.resources.implicitfunction&&console.warn("THREE.ThreeMFLoader: Implicit Functions are implemented in data-only.",U.resources.implicitfunction)}function ut(C){const P=C.model,U=C.modelRels,q={},Z=Object.keys(P),V={};if(U)for(let at=0,st=U.length;at<st;at++){const rt=U[at],lt=rt.target.substring(1);C.texture[lt]&&(V[rt.target]=C.texture[lt])}for(let at=0;at<Z.length;at++){const st=Z[at],rt=P[st],lt=Object.keys(rt.resources.object);for(let ft=0;ft<lt.length;ft++){const ct=lt[ft];ht(ct,q,rt,V)}}return q}function St(C){for(let P=0;P<C.length;P++){const U=C[P];if(U.target.split(".").pop().toLowerCase()==="model")return U}}function Bt(C,P){const U=new $e,q=St(P.rels),Z=P.model[q.target.substring(1)].build;for(let V=0;V<Z.length;V++){const at=Z[V],st=C[at.objectId].clone(),rt=at.transform;rt&&st.applyMatrix4(rt),U.add(st)}return U}const Yt=s(t),Vt=ut(Yt);return Bt(Vt,Yt)}addExtension(t){this.availableExtensions.push(t)}}const hi="pass",ui="warn",Jn="fail",Bi="skip",q_="ready",Y_="warning",$_="not_printable";function K_(i){const t=[];return i.traverse(e=>{var n,s;e.isMesh&&((s=(n=e.geometry)==null?void 0:n.getAttribute("position"))!=null&&s.count)&&t.push(e)}),t}function Z_(i,{maxTrianglesForTopology:t=4e5,applyWorldMatrix:e=!1}={}){const n=K_(i);let s=0,r=0;n.forEach(b=>{const M=b.geometry.getAttribute("position");r+=M.count,s+=b.geometry.index?b.geometry.index.count/3:M.count/3}),s=Math.round(s);const a={triangles:s,vertices:r,signedVolumeMm3:0,volumeMm3:0,surfaceAreaMm2:0,degenerateFaces:0,weldedVertices:0,boundaryEdges:0,nonManifoldEdges:0,inconsistentEdges:0,holeCount:0,topologyAnalyzed:s>0&&s<=t,isWatertight:!1};if(s===0)return a;const o=[0,0,0],c=[0,0,0],l=[0,0,0],h=b=>{n.forEach(M=>{const A=M.geometry,x=A.getAttribute("position"),T=A.index,N=T?T.count:x.count,D=M.matrixWorld,B=e;for(let $=0;$<N;$+=3){const J=T?T.getX($):$,z=T?T.getX($+1):$+1,H=T?T.getX($+2):$+2;Ea(x,J,o,D,B),Ea(x,z,c,D,B),Ea(x,H,l,D,B),b(o,c,l,J,z,H,M)}})};if(h((b,M,A)=>{a.signedVolumeMm3+=(b[0]*(M[1]*A[2]-M[2]*A[1])-b[1]*(M[0]*A[2]-M[2]*A[0])+b[2]*(M[0]*A[1]-M[1]*A[0]))/6;const x=M[0]-b[0],T=M[1]-b[1],N=M[2]-b[2],D=A[0]-b[0],B=A[1]-b[1],$=A[2]-b[2],J=T*$-N*B,z=N*D-x*$,H=x*B-T*D,W=Math.sqrt(J*J+z*z+H*H)/2;a.surfaceAreaMm2+=W,W<1e-10&&(a.degenerateFaces+=1)}),a.volumeMm3=Math.abs(a.signedVolumeMm3),!a.topologyAnalyzed)return a;const d=new Map,u=1e4,p=b=>`${Math.round(b[0]*u)},${Math.round(b[1]*u)},${Math.round(b[2]*u)}`,g=b=>{const M=p(b);let A=d.get(M);return A===void 0&&(A=d.size,d.set(M,A)),A},v=new Int32Array(s*3);let m=0;h((b,M,A)=>{v[m++]=g(b),v[m++]=g(M),v[m++]=g(A)}),a.weldedVertices=d.size;const f=d.size+1,E=new Map,w=(b,M)=>{if(b===M)return;const A=Math.min(b,M),x=Math.max(b,M),T=A*f+x,N=b===A?1:-1,D=E.get(T);D===void 0?E.set(T,{count:1,direction:N,a:A,b:x}):(D.count+=1,D.direction+=N)};for(let b=0;b<v.length;b+=3){const M=v[b],A=v[b+1],x=v[b+2];w(M,A),w(A,x),w(x,M)}const y=[];return E.forEach(b=>{b.count===1?(a.boundaryEdges+=1,y.push(b)):b.count>2?a.nonManifoldEdges+=1:b.direction!==0&&(a.inconsistentEdges+=1)}),a.holeCount=J_(y),a.isWatertight=a.boundaryEdges===0&&a.nonManifoldEdges===0,a}function Ea(i,t,e,n,s){if(e[0]=i.getX(t),e[1]=i.getY(t),e[2]=i.getZ(t),s){const[r,a,o]=e,c=n.elements,l=c[3]*r+c[7]*a+c[11]*o+c[15]||1;e[0]=(c[0]*r+c[4]*a+c[8]*o+c[12])/l,e[1]=(c[1]*r+c[5]*a+c[9]*o+c[13])/l,e[2]=(c[2]*r+c[6]*a+c[10]*o+c[14])/l}}function J_(i){if(i.length===0)return 0;const t=new Map,e=(r,a)=>{t.has(r)||t.set(r,[]),t.get(r).push(a)};i.forEach(r=>{e(r.a,r.b),e(r.b,r.a)});const n=new Set;let s=0;return t.forEach((r,a)=>{if(n.has(a))return;s+=1;const o=[a];for(n.add(a);o.length;){const c=o.pop();(t.get(c)??[]).forEach(l=>{n.has(l)||(n.add(l),o.push(l))})}}),s}function j_(i,t,e,n){const s=[],r=new Intl.NumberFormat("id-ID");i.topologyAnalyzed?i.isWatertight?s.push({id:"watertight",label:"Mesh tertutup (watertight)",status:hi,message:"Seluruh tepi tersambung rapat. Model membentuk volume padat yang siap di-slice."}):s.push({id:"watertight",label:"Mesh tertutup (watertight)",status:Jn,message:"Mesh belum tertutup sempurna sehingga slicer tidak dapat menentukan bagian dalam dan luar part."}):s.push({id:"watertight",label:"Mesh tertutup (watertight)",status:Bi,message:`Model memiliki ${r.format(i.triangles)} segitiga sehingga analisis topologi dilewati agar browser tetap responsif. Tim kami akan memeriksanya secara manual.`}),i.topologyAnalyzed?i.boundaryEdges===0?s.push({id:"holes",label:"Lubang pada permukaan",status:hi,message:"Tidak ditemukan tepi terbuka pada permukaan model."}):s.push({id:"holes",label:"Lubang pada permukaan",status:Jn,message:`Ditemukan ${r.format(i.holeCount)} lubang (${r.format(i.boundaryEdges)} tepi terbuka). Tutup lubang tersebut di software CAD atau perbaiki dengan mesh repair sebelum dicetak.`}):s.push({id:"holes",label:"Lubang pada permukaan",status:Bi,message:"Tidak diperiksa karena jumlah segitiga melebihi batas analisis otomatis."}),i.topologyAnalyzed?i.nonManifoldEdges===0?s.push({id:"non-manifold",label:"Non-manifold edge",status:hi,message:"Setiap tepi dipakai tepat oleh dua muka, geometrinya bersih."}):s.push({id:"non-manifold",label:"Non-manifold edge",status:Jn,message:`Ditemukan ${r.format(i.nonManifoldEdges)} tepi yang dipakai lebih dari dua muka. Biasanya muncul akibat permukaan bertumpuk atau body yang belum di-merge.`}):s.push({id:"non-manifold",label:"Non-manifold edge",status:Bi,message:"Tidak diperiksa karena jumlah segitiga melebihi batas analisis otomatis."}),i.topologyAnalyzed?i.inconsistentEdges===0&&i.signedVolumeMm3>=0?s.push({id:"normals",label:"Arah normal muka",status:hi,message:"Orientasi seluruh muka konsisten menghadap ke luar."}):i.inconsistentEdges>0?s.push({id:"normals",label:"Arah normal muka",status:ui,message:`Ditemukan ${r.format(i.inconsistentEdges)} tepi dengan orientasi muka berlawanan. Sebagian normal kemungkinan terbalik. Umumnya masih dapat diperbaiki otomatis oleh slicer, namun sebaiknya dibetulkan lebih dulu.`}):s.push({id:"normals",label:"Arah normal muka",status:ui,message:"Seluruh normal tampak menghadap ke dalam. Model kemungkinan ter-invert; balik arah normalnya sebelum dicetak."}):s.push({id:"normals",label:"Arah normal muka",status:Bi,message:"Tidak diperiksa karena jumlah segitiga melebihi batas analisis otomatis."});const a=Math.min(t.x,t.y,t.z),o=Math.max(t.x,t.y,t.z);if(a<n.minDimension?s.push({id:"size",label:"Ukuran model",status:Jn,message:`Sisi terkecil hanya ${a.toFixed(2)} mm, di bawah batas minimum ${n.minDimension} mm. Perbesar model atau pastikan satuan file sudah benar.`}):a<n.warnDimension?s.push({id:"size",label:"Ukuran model",status:ui,message:`Sisi terkecil ${a.toFixed(2)} mm tergolong sangat tipis. Fitur setipis ini rawan patah pada FDM. Pertimbangkan SLA untuk hasil lebih baik.`}):s.push({id:"size",label:"Ukuran model",status:hi,message:`Dimensi terbesar ${o.toFixed(1)} mm dan terkecil ${a.toFixed(1)} mm berada pada rentang yang wajar.`}),e){const h=e.buildVolume,d=t.y,u=[t.x,t.z],p=[h.x,h.y],g=d<=h.z,v=u[0]<=p[0]&&u[1]<=p[1]||u[0]<=p[1]&&u[1]<=p[0],m=g&&v,f=[t.x,t.y,t.z].sort((A,x)=>x-A),E=[h.x,h.y,h.z].sort((A,x)=>x-A),w=f.every((A,x)=>A<=E[x]),y=Math.max(d/h.z,Math.min(Math.max(u[0]/p[0],u[1]/p[1]),Math.max(u[0]/p[1],u[1]/p[0])))*100,b=`${t.x.toFixed(0)} × ${t.z.toFixed(0)} mm tapak, tinggi ${d.toFixed(0)} mm`,M=`${h.x} × ${h.y} × ${h.z} mm`;!m&&w?s.push({id:"build-volume",label:`Area cetak ${e.code}`,status:Jn,message:`Pada orientasi sekarang (${b}) model tidak muat di area cetak ${M}, ${g?"karena tapaknya terlalu lebar":"karena terlalu tinggi"}. Model ini masih muat bila diputar. Coba ubah orientasinya pada panel Orientasi Model.`}):m?y>90?s.push({id:"build-volume",label:`Area cetak ${e.code}`,status:ui,message:`Model memakai ${y.toFixed(0)}% area cetak ${M} pada orientasi sekarang. Masih muat, tetapi ruang untuk support sangat terbatas.`}):s.push({id:"build-volume",label:`Area cetak ${e.code}`,status:hi,message:`Pada orientasi sekarang model muat di area cetak ${M} (memakai ${y.toFixed(0)}%).`}):s.push({id:"build-volume",label:`Area cetak ${e.code}`,status:Jn,message:`Model ${b} melebihi area cetak ${M} pada orientasi apa pun. Pilih teknologi lain, perkecil skala, atau pecah model menjadi beberapa bagian.`})}i.degenerateFaces>0&&s.push({id:"degenerate",label:"Muka degenerate",status:ui,message:`${r.format(i.degenerateFaces)} segitiga memiliki luas nol. Biasanya sisa proses ekspor dan dapat dibersihkan otomatis oleh slicer.`});const c=s.some(h=>h.status===Jn),l=s.some(h=>h.status===ui||h.status===Bi);return{checks:s,status:c?$_:l?Y_:q_}}const os={PASS:hi,WARN:ui,FAIL:Jn,SKIP:Bi},Ce=i=>Math.round(i*100)/100;function Q_(i){var b,M,A,x,T;const t=String(i.technology??"").toUpperCase(),e=((M=(b=i.pricing)==null?void 0:b.formulas)==null?void 0:M[t])??{},n=Math.max(1,Number(i.quantity)||1),s=(Number(i.minutes)||0)/60,r=((x=(A=i.pricing)==null?void 0:A.machines)==null?void 0:x[i.printerKey])??null,a=r?Number(r.cost)||0:Number(e.machineCost)||0,o=(Number(i.totalWeightG)||0)*n,c=Number(i.materialPricePerGram)||Number(e.materialPricePerG)||0,l=e0(i.dimensions,((T=i.pricing)==null?void 0:T.packaging)??[]),h=(l?l.price:Number(e.packagingCost)||0)*n,d=t0(i.dimensions,i.cost??{}),u=Number(e.overtimeCost)||0,p=Number(e.riskPercent)||0,g=Number(e.profitPercent)||0,v=s*a,m=o*c,f=v+m,E=f*(p/100),w=f+E+h+u,y=w*(g/100);return{technology:t,machine_time_hours:Ce(s),machine_cost:Ce(a),machine_source:r?r.name:null,material_qty_g:Ce(o),material_price_per_g:Ce(c),risk_percent:p,profit_percent:g,packaging_source:l?l.label:null,quantity:n,largest_dimension_mm:Ce(d.largestMm),basic_fee_label:d.label,material_cost:Ce(m),machine_operational_cost:Ce(v),hpp:Ce(f),risk_cost:Ce(E),subtotal_hpp_risk:Ce(f+E),packaging:Ce(h),overtime:Ce(u),subtotal:Ce(w),profit:Ce(y),basic_fee:Ce(d.fee),selling_price:Ce(w+y+d.fee),total:Ce(w+y+d.fee)}}function t0(i,t={}){var s;const e=((s=t.basicFee)==null?void 0:s.tiers)??[],n=Math.max(0,Number((i==null?void 0:i.x)??0)||0,Number((i==null?void 0:i.y)??0)||0,Number((i==null?void 0:i.z)??0)||0);for(const r of e){if(r.belowMm!=null){if(n<Number(r.belowMm))return{largestMm:n,label:r.label??null,fee:Number(r.fee)||0};continue}if(r.upToMm!=null){if(n<=Number(r.upToMm))return{largestMm:n,label:r.label??null,fee:Number(r.fee)||0};continue}return{largestMm:n,label:r.label??null,fee:Number(r.fee)||0}}return{largestMm:n,label:null,fee:0}}function e0(i,t){if(!i||!t.length)return null;const e=[(Number(i.x)||0)/10,(Number(i.y)||0)/10,(Number(i.z)||0)/10].sort((n,s)=>s-n);return e[0]<=0?null:t.find(n=>{const s=n.sides??[];return s.length===3&&s[0]>=e[0]&&s[1]>=e[1]&&s[2]>=e[2]})??null}const n0=new Intl.NumberFormat("id-ID",{style:"currency",currency:"IDR",maximumFractionDigits:0}),i0=new Intl.NumberFormat("id-ID",{maximumFractionDigits:2});function Sh(i,t,e,n=1,s=null,r=null,a={}){var W,tt,Q;if(!i||!t)return null;const o=Math.max(1,Number(n)||1),c=Es(a.scale,1,.05,10),l=Math.max(0,Number(e)||0)*c**3,h=Math.max(0,Number(a.surfaceAreaCm2)||0)*c**2,d=Number((r==null?void 0:r.timeMultiplier)??1)||1,u=Number((r==null?void 0:r.materialMultiplier)??1)||1,p=Es(a.infillDensity,Number(i.defaultInfill??1),0,1),g=((W=a.patterns)==null?void 0:W[a.infillPattern])??null,v=r0(i,a.hollow,l,h);let m,f;v.enabled?(m=l>0?v.volumeCm3/l:0,f=v.volumeCm3*u):(m=s0(i,p,g),f=l*m*u);const E=f*t.density,w=Math.max(0,Number((s==null?void 0:s.volumeCm3)??0)),y=Math.max(0,Number((s==null?void 0:s.weightG)??0)),b=!!(s!=null&&s.enabled),M=f+w,A=E+y,x=Math.max(.1,Number(((tt=a.printer)==null?void 0:tt.speedFactor)??1)||1),T=1+((Number((g==null?void 0:g.timeMultiplier)??1)||1)-1)*p,N=i.throughput*x,D=N>0?M/N*d*(v.enabled?1:T):0,B=i.setupHours+D*o,$=((Q=a.finishings)==null?void 0:Q[a.finishing])??null,J=Math.max(0,Number(($==null?void 0:$.hoursPerUnit)??0)||0)*o,z=Math.max(1,Math.round((B+J)*60)),H=Q_({technology:i.code,materialPricePerGram:t.pricePerGram,printerKey:a.printerKey??null,quantity:o,totalWeightG:A,minutes:z,dimensions:a.dimensions??null,pricing:a.pricing??{},cost:a.cost??{}});return{scale:c,modelVolumeCm3:l,surfaceAreaCm2:h,infillDensity:p,fillFactor:m,hollowEnabled:v.enabled,hollowWallThicknessMm:v.wallThicknessMm,hollowSavedCm3:v.enabled?l-v.volumeCm3:0,materialVolumeCm3:f,weightG:E,supportEnabled:b,supportVolumeCm3:w,supportWeightG:y,totalMaterialVolumeCm3:M,totalWeightG:A,finishing:a.finishing??null,finishingMinutes:Math.round(J*60),largestDimensionMm:H.largest_dimension_mm,basicFee:H.basic_fee,basicFeeLabel:H.basic_fee_label,unitMinutes:Math.max(1,Math.round(D*60)),totalMinutes:z,totalCost:H.selling_price,breakdown:H}}function s0(i,t,e){const n=Es(i.shellRatio,1,0,1),s=Number((e==null?void 0:e.materialMultiplier)??1)||1;return Math.min(1,n+(1-n)*t*s)}function r0(i,t,e,n){const s=!!(t!=null&&t.enabled)&&!!i.allowsHollow&&n>0&&e>0,r=Es(t==null?void 0:t.wallThicknessMm,2,.1,20);if(!s)return{enabled:!1,volumeCm3:e,wallThicknessMm:null};const a=n*r/10,o=Math.max(0,Number((t==null?void 0:t.drainHoles)??2)||0),c=Es(t==null?void 0:t.drainDiameterMm,3.5,.1,50),l=o*Math.PI*(c/2)**2*r/1e3;return{enabled:!0,volumeCm3:Math.max(0,Math.min(e,a-l)),wallThicknessMm:r}}function Es(i,t,e,n){const s=Number(i);return Number.isFinite(s)?Math.min(n,Math.max(e,s)):t}function a0(i){return n0.format(Math.round(i))}function qt(i,t=2){return new Intl.NumberFormat("id-ID",{minimumFractionDigits:t,maximumFractionDigits:t}).format(i)}function ls(i){return i0.format(i)}function Ta(i,t=0){return`${qt(i*100,t)}%`}let Ar={unit:"Hari Kerja",tiers:[{name:"Express",maxMinutes:1200,minDays:1,maxDays:1},{name:"Standard",maxMinutes:null,minDays:3,maxDays:5}]};function $0(i){var t;(t=i==null?void 0:i.tiers)!=null&&t.length&&(Ar={unit:i.unit??Ar.unit,tiers:i.tiers})}function o0(i){const t=Math.max(0,Number(i)||0),e=Ar.tiers;return e.find(n=>n.maxMinutes===null||t<=n.maxMinutes)??e[e.length-1]}function Ec(i){const t=o0(i),e=`${t.minDays===t.maxDays?t.minDays:`${t.minDays}–${t.maxDays}`} ${Ar.unit}`;return t.name?`${t.name} — ${e}`:e}const l0={infill:.3,heightInfluence:.25,maxAspect:3,defaultType:"normal",types:{}};function yh(i,t,e,n,s={}){var m,f;const r={...l0,...s.config??{}},a=wr(i),o=!!s.enabled&&a,c=s.type??r.defaultType,l=Number(((f=(m=r.types)==null?void 0:m[c])==null?void 0:f.multiplier)??1),h=h0(n,r);if(!o)return{enabled:!1,required:a,measured:!1,grossVolumeCm3:0,volumeCm3:0,weightG:0,aspectMultiplier:h};const d=Number(s.measuredVolumeCm3??NaN),u=Number.isFinite(d)&&d>=0,p=Number((i==null?void 0:i.supportFactor)??0),g=u?d/Number(r.infill):Math.max(0,e)*p*l*h,v=u?d:g*Number(r.infill);return{enabled:!0,required:a,measured:u,grossVolumeCm3:g,volumeCm3:v,weightG:v*Math.max(0,Number((t==null?void 0:t.density)??0)),aspectMultiplier:h}}function wr(i){return Number((i==null?void 0:i.supportFactor)??0)>0}function c0(i){return wr(i)?null:(i==null?void 0:i.supportNote)??null}function h0(i,t){const e=Number((i==null?void 0:i.y)??0),n=Math.max(Number((i==null?void 0:i.x)??0),Number((i==null?void 0:i.z)??0));return e<=0||n<=0?1:1+Math.min(e/n,Number(t.maxAspect))*Number(t.heightInfluence)}const Tc=.01;function K0(i){return Object.entries(i.technologies??{}).map(([t,e])=>({code:t,label:e.label??t,name:e.name,description:e.description??null}))}function u0(i,t){var e,n;return((n=(e=i.technologies)==null?void 0:e[t])==null?void 0:n.materials)??[]}function Z0(i,t,e){return u0(i,t).find(n=>n.name===e)??null}function Ac(i){return(i==null?void 0:i.label)??(i==null?void 0:i.name)??"-"}function Aa(i){return[Number((i==null?void 0:i.x)??0),Number((i==null?void 0:i.y)??0),Number((i==null?void 0:i.z)??0)].sort((t,e)=>e-t)}function J0(i,t){if(!i||!t)return null;const e=Aa(i);if(t.maxSize){const s=Aa(t.maxSize);if(e.some((r,a)=>r>s[a]+Tc))return{type:"max",limit:t.maxSize}}const n=[t.minSize,t.minSizeSlender].filter(Boolean);return n.length&&!n.some(r=>{const a=Aa(r);return e.every((o,c)=>o+Tc>=a[c])})?{type:"min",limit:t.minSize??n[0]}:null}function d0(i,t,e){var o,c,l;const n=(((c=(o=i.technologies)==null?void 0:o[t])==null?void 0:c.materials)??[]).find(h=>h.name===e),s=(n==null?void 0:n.colors)??[],r=((l=i.materialColors)==null?void 0:l.options)??{};return(s.length?s.filter(h=>h in r):Object.keys(r)).map(h=>({key:h,...r[h]}))}function j0(i){var t;return Object.entries(((t=i.finishing)==null?void 0:t.options)??{}).map(([e,n])=>({key:e,...n}))}const f0="painting";function p0(i){return i==="putih"}function m0(i){const t=i.inputs??{},e=i.payload??{},n=e.model_stats??{},s=Number(t.geometryVolumeCm3??n.volume_cm3??e.model_volume_cm3??0),r=Number(t.surfaceAreaCm2??n.surface_area_cm2??0),a=t.dimensions??n.dimensions??null,o=t.measuredSupportVolumeCm3??e.support_volume_cm3??null;return{geometryVolumeCm3:Number.isFinite(s)?s:0,surfaceAreaCm2:Number.isFinite(r)?r:0,dimensions:a,measuredSupportVolumeCm3:o===null?null:Number(o)}}function Q0(i){var e,n,s,r,a,o,c,l,h,d;const t=((e=i.state)==null?void 0:e.settings)??{};return{technology:t.technology??((n=i.summary)==null?void 0:n.technology),material:t.material??((s=i.summary)==null?void 0:s.material),color:t.color??((r=i.summary)==null?void 0:r.color),finishing:t.finishing??((a=i.summary)==null?void 0:a.finishing)??"none",quantity:Math.max(1,Number(t.quantity??((o=i.summary)==null?void 0:o.quantity)??1)),support:!!t.support,hollow:{enabled:!!((c=t.hollow)!=null&&c.enabled),wallThicknessMm:Number(((l=t.hollow)==null?void 0:l.wallThicknessMm)??2),drainDiameterMm:Number(((h=t.hollow)==null?void 0:h.drainDiameterMm)??3.5),drainPosition:((d=t.hollow)==null?void 0:d.drainPosition)??"bottom"}}}function g0(i,t){var e,n;return!!((n=(e=i.technologies)==null?void 0:e[t])!=null&&n.allowsHollow)}function bh(i,t){var e,n;return Number(((n=(e=i.technologies)==null?void 0:e[t])==null?void 0:n.supportFactor)??0)>0}function tx(i,t){var e,n;return bh(i,t)?null:((n=(e=i.technologies)==null?void 0:e[t])==null?void 0:n.supportNote)??null}function ex(i,t,e){var f,E,w,y,b,M,A,x,T,N,D,B,$,J;const n=(f=t.technologies)==null?void 0:f[e.technology];if(!n)return null;const s=(n.materials??[]).find(z=>z.name===e.material);if(!s)return null;const r=((E=i.state)==null?void 0:E.settings)??{},a=m0(i),o=Math.max(1,Number(e.quantity)||1),c=d0(t,e.technology,e.material).map(z=>z.key),l=c.includes(e.color)?e.color:c[0]??e.color;let h=(y=(w=t.finishing)==null?void 0:w.options)!=null&&y[e.finishing]?e.finishing:((b=t.finishing)==null?void 0:b.default)??"none";h===f0&&!p0(l)&&(h=((M=t.finishing)==null?void 0:M.default)??"none");const d=Number(r.scale??1)||1,u=a.geometryVolumeCm3,p=!!e.support&&bh(t,e.technology),g={...r.hollow??{},...e.hollow??{},enabled:!!((A=e.hollow)!=null&&A.enabled)&&g0(t,e.technology)},v=yh(n,s,u*d**3,a.dimensions??null,{enabled:p,config:t.support,measuredVolumeCm3:a.measuredSupportVolumeCm3??void 0}),m=Sh(n,s,u,o,v,((x=t.resolutions)==null?void 0:x[r.resolution])??null,{scale:d,surfaceAreaCm2:Number(a.surfaceAreaCm2??0),dimensions:a.dimensions??null,infillDensity:r.infillDensity,infillPattern:r.infillPattern,patterns:(T=t.infill)==null?void 0:T.patterns,hollow:{...g,drainHoles:((N=t.hollow)==null?void 0:N.drainCount)??2},printer:((B=t.printers)==null?void 0:B[(D=i.state)==null?void 0:D.printer])??null,printerKey:(($=i.state)==null?void 0:$.printer)??null,finishing:h,finishings:(J=t.finishing)==null?void 0:J.options,cost:t.cost,pricing:t.pricing});return m?{...i,state:{...i.state,settings:{...r,technology:e.technology,material:e.material,color:l,finishing:h,quantity:o,support:p,hollow:g}},payload:{...i.payload,technology:e.technology,material:e.material,material_color:l,finishing:h,quantity:o,support_enabled:v.enabled,support_volume_cm3:v.enabled?v.volumeCm3:null,hollow_enabled:m.hollowEnabled,hollow_wall_thickness_mm:m.hollowWallThicknessMm,estimate:{totalWeightG:m.totalWeightG,totalMinutes:m.totalMinutes,totalCost:m.totalCost,breakdown:m.breakdown}},summary:{...i.summary,volumeCm3:m.modelVolumeCm3,weightG:m.totalWeightG*o,minutes:m.totalMinutes,cost:m.totalCost,technology:e.technology,material:e.material,color:l,finishing:h,quantity:o,support:v.enabled,hollow:m.hollowEnabled}}:null}const _0=["stl","stp","step","obj","3mf"],nx="STL, STP, STEP, OBJ, 3MF",x0=["stp","step"],v0={STL:"Stereolithography",OBJ:"Wavefront",STP:"STEP CAD",STEP:"STEP CAD","3MF":"3D Manufacturing Format"},wc="/vendor/occt";function Rr(i){var t;return((t=String(i??"").split(".").pop())==null?void 0:t.toLowerCase())??""}function ix(i){return _0.includes(Rr(i))}function sx(i){return x0.includes(Rr(i))}let cs=null;function M0(){return cs||(cs=new Promise((i,t)=>{const e=document.createElement("script");e.src=`${wc}/occt-import-js.js`,e.async=!0,e.onload=()=>{if(typeof window.occtimportjs!="function"){t(new Error("Pustaka STEP gagal dimuat."));return}window.occtimportjs({locateFile:n=>`${wc}/${n}`}).then(i).catch(t)},e.onerror=()=>t(new Error("Pustaka STEP tidak dapat diunduh.")),document.head.appendChild(e)}).catch(i=>{throw cs=null,i}),cs)}async function rx(i){var n;const e=(await M0()).ReadStepFile(new Uint8Array(i),null);if(!(e!=null&&e.success)||!((n=e.meshes)!=null&&n.length))throw new Error("Isi berkas STEP tidak dapat dibaca.");return S0(e.meshes)}function S0(i){var r,a,o;const t=[];for(const c of i){const l=(a=(r=c.attributes)==null?void 0:r.position)==null?void 0:a.array;if(!(l!=null&&l.length))continue;const h=((o=c.index)==null?void 0:o.array)??null,d=h?h.length:l.length/3;for(let u=0;u<d;u+=3){const p=[];for(let g=0;g<3;g++){const v=(h?h[u+g]:u+g)*3;p.push([l[v],l[v+1],l[v+2]])}t.push(p)}}if(t.length===0)throw new Error("Berkas STEP tidak memuat solid yang dapat dicetak.");const e=new ArrayBuffer(84+t.length*50),n=new DataView(e);n.setUint32(80,t.length,!0);let s=84;for(const[c,l,h]of t){n.setFloat32(s,0,!0),n.setFloat32(s+4,0,!0),n.setFloat32(s+8,0,!0),s+=12;for(const[d,u,p]of[c,l,h])n.setFloat32(s,d,!0),n.setFloat32(s+4,u,!0),n.setFloat32(s+8,p,!0),s+=12;n.setUint16(s,0,!0),s+=2}return e}const Rc={overhangAngleDeg:45,gridSizeMm:4,maxCellsPerAxis:44,pillarShrink:.62,minPillarHeightMm:.8,plateToleranceMm:.4,baseHeightMm:.8,baseExpand:1.3,color:"#5AD4DE",opacity:.52,infill:.3};function Eh(i={}){const t={...Rc};return Object.entries(i).forEach(([e,n])=>{if(n!=null){if(typeof Rc[e]=="number"){const s=Number(n);Number.isFinite(s)&&(t[e]=s);return}t[e]=n}}),t}function y0(i={}){const t=Eh(i);return new Ki({color:new Ft(t.color),transparent:!0,opacity:t.opacity,roughness:.35,metalness:.05,depthWrite:!1,side:Xe})}function b0(i,t={}){const e=Eh(t),n=new $e;n.name="support-structure";const s={group:n,pillarCount:0,grossVolumeCm3:0,materialVolumeCm3:0,overhangFaces:0,cellSizeMm:e.gridSizeMm},r=[];if(i.updateMatrixWorld(!0),i.traverse(Q=>{var ht,ut;Q.isMesh&&((ut=(ht=Q.geometry)==null?void 0:ht.getAttribute("position"))!=null&&ut.count)&&r.push(Q)}),r.length===0)return s;const a=new Ke().setFromObject(i,!0),o=a.getSize(new I);if(o.x<=0||o.z<=0)return s;const c=Math.max(o.x,o.z),l=Math.max(e.gridSizeMm,c/e.maxCellsPerAxis),h=Math.cos(e.overhangAngleDeg*Math.PI/180),d=new Map;let u=0;const p=new I,g=new I,v=new I,m=new I,f=new I,E=new I;if(r.forEach(Q=>{const ht=Q.geometry.getAttribute("position"),ut=Q.geometry.index,St=ut?ut.count:ht.count,Bt=Q.matrixWorld;for(let Yt=0;Yt<St;Yt+=3){const Vt=ut?ut.getX(Yt):Yt,C=ut?ut.getX(Yt+1):Yt+1,P=ut?ut.getX(Yt+2):Yt+2;p.fromBufferAttribute(ht,Vt).applyMatrix4(Bt),g.fromBufferAttribute(ht,C).applyMatrix4(Bt),v.fromBufferAttribute(ht,P).applyMatrix4(Bt),m.subVectors(g,p),f.subVectors(v,p),E.crossVectors(m,f).normalize(),!(-E.y<=h)&&(u+=1,T0(p,g,v,a.min,l,(U,q,Z)=>{const V=U*1e5+q,at=d.get(V);(at===void 0||Z<at.y)&&d.set(V,{cx:U,cz:q,y:Z})}))}}),d.size===0)return{...s,overhangFaces:u,cellSizeMm:l};const w=new od,y=new I(0,-1,0),b=new I,M=[];let A=0;if(d.forEach(({cx:Q,cz:ht,y:ut})=>{if(ut-a.min.y<=e.plateToleranceMm)return;const St=a.min.x+(Q+.5)*l,Bt=a.min.z+(ht+.5)*l;b.set(St,ut-.05,Bt),w.set(b,y),w.far=ut-a.min.y;const Yt=w.intersectObjects(r,!1),Vt=Yt.length>0?Yt[0].point.y:a.min.y,C=ut-Vt;if(C<e.minPillarHeightMm)return;const P=l*e.pillarShrink;M.push({x:St,z:Bt,baseY:Vt,height:C,width:P}),A+=P*P*C}),M.length===0)return{...s,overhangFaces:u,cellSizeMm:l};const x=y0(e),T=new zn(1,1,1),N=new Dl(T,x,M.length);N.name="support-pillars";const D=M.filter(Q=>Q.baseY-a.min.y<.01),B=new zn(1,1,1),$=D.length>0?new Dl(B,x,D.length):null;$&&($.name="support-base");const J=new se,z=new I,H=new I,W=new On;if(M.forEach((Q,ht)=>{z.set(Q.x,Q.baseY+Q.height/2,Q.z),H.set(Q.width,Q.height,Q.width),N.setMatrixAt(ht,J.compose(z,W,H))}),N.instanceMatrix.needsUpdate=!0,n.add(N),$){const Q=l*e.pillarShrink*e.baseExpand;D.forEach((ht,ut)=>{z.set(ht.x,a.min.y+e.baseHeightMm/2,ht.z),H.set(Q,e.baseHeightMm,Q),$.setMatrixAt(ut,J.compose(z,W,H)),A+=Q*Q*e.baseHeightMm}),$.instanceMatrix.needsUpdate=!0,n.add($)}const tt=A/1e3;return{group:n,pillarCount:M.length,grossVolumeCm3:tt,materialVolumeCm3:tt*e.infill,overhangFaces:u,cellSizeMm:l}}const E0=4e4;function T0(i,t,e,n,s,r){const a=Math.min(i.x,t.x,e.x),o=Math.max(i.x,t.x,e.x),c=Math.min(i.z,t.z,e.z),l=Math.max(i.z,t.z,e.z),h=Math.floor((a-n.x)/s),d=Math.floor((o-n.x)/s),u=Math.floor((c-n.z)/s),p=Math.floor((l-n.z)/s),g=(t.z-e.z)*(i.x-e.x)+(e.x-t.x)*(i.z-e.z);if(Math.abs(g)<1e-9)return;const v=(d-h+1)*(p-u+1);let m=!1;if(v<=E0)for(let f=h;f<=d;f+=1)for(let E=u;E<=p;E+=1){const w=n.x+(f+.5)*s,y=n.z+(E+.5)*s,b=((t.z-e.z)*(w-e.x)+(e.x-t.x)*(y-e.z))/g,M=((e.z-i.z)*(w-e.x)+(i.x-e.x)*(y-e.z))/g,A=1-b-M;b<-1e-6||M<-1e-6||A<-1e-6||(r(f,E,b*i.y+M*t.y+A*e.y),m=!0)}if(!m){const f=(i.x+t.x+e.x)/3,E=(i.z+t.z+e.z)/3,w=(i.y+t.y+e.y)/3;r(Math.floor((f-n.x)/s),Math.floor((E-n.z)/s),w)}}function A0(i){i&&(i.traverse(t=>{var e,n;(t.isMesh||t.isInstancedMesh)&&((e=t.geometry)==null||e.dispose(),Array.isArray(t.material)?t.material.forEach(s=>s.dispose()):(n=t.material)==null||n.dispose())}),i.removeFromParent())}const w0=15854568,R0=14208456,C0=12167587,wa=9774877,Ra={x:12597547,y:3112250,z:3104696};function P0(i){return[10,20,25,50].find(e=>i/e<=30)??100}class D0{constructor(t,e){this.scene=t,this.group=new $e,this.group.name="build-plate",this.scene.add(this.group),this.gridVisible=!0,this.axesVisible=!1,this.volume=null,this.setVolume(e)}get size(){return{width:this.volume.x,depth:this.volume.y,height:this.volume.z}}get bounds(){const{width:t,depth:e,height:n}=this.size;return{minX:-t/2,maxX:t/2,minZ:-e/2,maxZ:e/2,maxY:n}}setVolume(t){const e={x:Math.max(1,Number(t==null?void 0:t.x)||1),y:Math.max(1,Number(t==null?void 0:t.y)||1),z:Math.max(1,Number(t==null?void 0:t.z)||1)};this.volume&&e.x===this.volume.x&&e.y===this.volume.y&&e.z===this.volume.z||(this.volume=e,this.rebuild())}rebuild(){this.disposeChildren();const{width:t,depth:e,height:n}=this.size;this.buildPlateSurface(t,e),this.buildGrid(t,e),this.buildVolumeBox(t,e,n),this.buildAxes(t,e,n),this.buildOrigin(t,e),this.setGridVisible(this.gridVisible),this.setAxesVisible(this.axesVisible)}buildPlateSurface(t,e){const n=new ws(t,e);n.rotateX(-Math.PI/2),this.plate=new Me(n,new Ki({color:w0,roughness:.95,metalness:0,side:Xe})),this.plate.position.y=-.05,this.plate.name="build-plate-surface",this.group.add(this.plate)}buildGrid(t,e){const n=P0(Math.max(t,e)),s=[],r=[],a=t/2,o=e/2,c=(l,h,d,u,p)=>l.push(h,0,d,u,0,p);for(let l=0;l<=a+1e-6;l+=n){const h=Math.round(l/n)%5===0?r:s;c(h,l,-o,l,o),l>0&&c(h,-l,-o,-l,o)}for(let l=0;l<=o+1e-6;l+=n){const h=Math.round(l/n)%5===0?r:s;c(h,-a,l,a,l),l>0&&c(h,-a,-l,a,-l)}this.gridLines=[this.createLines(s,R0,.5),this.createLines(r,C0,.85)].filter(Boolean),this.gridLines.forEach(l=>this.group.add(l))}buildVolumeBox(t,e,n){const s=new zn(t,n,e);s.translate(0,n/2,0),this.volumeBox=new bs(new th(s),new gi({color:wa,transparent:!0,opacity:.55})),this.volumeBox.name="build-volume-box",this.group.add(this.volumeBox),s.dispose()}buildAxes(t,e,n){const s={x:t/2,y:Math.min(n,Math.max(t,e)/2),z:e/2};this.axes=new $e,this.axes.name="build-plate-axes",[[Ra.x,[0,0,0,s.x,0,0]],[Ra.y,[0,0,0,0,s.y,0]],[Ra.z,[0,0,0,0,0,s.z]]].forEach(([a,o])=>{const c=this.createLines(o,a,1);c&&this.axes.add(c)}),this.group.add(this.axes)}buildOrigin(t,e){const n=Math.max(1.2,Math.min(t,e)/90);this.origin=new Me(new Fo(n,20,14),new Ki({color:wa,roughness:.4,metalness:.1})),this.origin.name="build-plate-origin",this.origin.position.set(0,0,0),this.group.add(this.origin)}createLines(t,e,n){if(t.length===0)return null;const s=new _e;return s.setAttribute("position",new ae(t,3)),new bs(s,new gi({color:e,transparent:!0,opacity:n}))}setGridVisible(t){this.gridVisible=t,this.plate.visible=t,this.gridLines.forEach(e=>{e.visible=t})}setAxesVisible(t){this.axesVisible=t,this.axes.visible=t,this.origin.visible=t}setExceeded(t){this.volumeBox.material.color.set(t?12597547:wa),this.volumeBox.material.opacity=t?.9:.55}disposeChildren(){[...this.group.children].forEach(t=>{var e;(e=t.traverse)==null||e.call(t,n=>{var s,r;(s=n.geometry)==null||s.dispose(),Array.isArray(n.material)?n.material.forEach(a=>a.dispose()):(r=n.material)==null||r.dispose()}),this.group.remove(t)})}dispose(){this.disposeChildren(),this.group.removeFromParent()}}function L0(i,t){const n={left:i.min.x<t.minX-.01,right:i.max.x>t.maxX+.01,front:i.min.z<t.minZ-.01,back:i.max.z>t.maxZ+.01,top:i.max.y>t.maxY+.01},s=Object.values(n).some(Boolean),r=[],a=(h,d,u,p,g,v)=>{const m=d-h,f=p-u,E=v-g;m>.01&&f>.01&&E>.01&&r.push({center:{x:(h+d)/2,y:(u+p)/2,z:(g+v)/2},size:{x:m,y:f,z:E}})};n.top&&a(i.min.x,i.max.x,t.maxY,i.max.y,i.min.z,i.max.z);const o=Math.min(i.max.y,t.maxY);n.left&&a(i.min.x,Math.min(i.max.x,t.minX),i.min.y,o,i.min.z,i.max.z),n.right&&a(Math.max(i.min.x,t.maxX),i.max.x,i.min.y,o,i.min.z,i.max.z);const c=Math.max(i.min.x,t.minX),l=Math.min(i.max.x,t.maxX);return n.front&&l>c&&a(c,l,i.min.y,o,i.min.z,Math.min(i.max.z,t.minZ)),n.back&&l>c&&a(c,l,i.min.y,o,Math.max(i.min.z,t.maxZ),i.max.z),{exceeds:s,violations:n,regions:r}}function I0(i){const t=new $e;if(t.name="build-volume-exceeded",i.length===0)return t;const e=new Ki({color:12597547,transparent:!0,opacity:.28,roughness:.6,depthWrite:!1,side:Xe}),n=new gi({color:12597547,transparent:!0,opacity:.95});return i.forEach(s=>{const r=new zn(s.size.x,s.size.y,s.size.z),a=new Me(r,e);a.position.set(s.center.x,s.center.y,s.center.z),t.add(a);const o=new bs(new th(r),n);o.position.copy(a.position),t.add(o)}),t}function Cc(i){i&&(i.traverse(t=>{var e,n;(e=t.geometry)==null||e.dispose(),Array.isArray(t.material)?t.material.forEach(s=>s.dispose()):(n=t.material)==null||n.dispose()}),i.removeFromParent())}const Zn={MATERIAL:"material",OVERHANG:"overhang",THICKNESS:"thickness"};function Vo(i){const t=[];return i.updateMatrixWorld(!0),i.traverse(e=>{var n,s;e.isMesh&&((s=(n=e.geometry)==null?void 0:n.getAttribute("position"))!=null&&s.count)&&t.push(e)}),t}function Th(i){const t=i.getAttribute("position").count;let e=i.getAttribute("color");return(!e||e.count!==t)&&(e=new Pe(new Float32Array(t*3).fill(1),3),i.setAttribute("color",e)),e}function Go(i,t){const e=i.geometry,n=e.getAttribute("position"),s=e.index,r=s?s.count:n.count,a=i.matrixWorld,o=a.determinant()<0,c=new I,l=new I,h=new I,d=new I,u=new I,p=new I;for(let g=0;g<r;g+=3){const v=s?s.getX(g):g,m=s?s.getX(g+1):g+1,f=s?s.getX(g+2):g+2;c.fromBufferAttribute(n,v).applyMatrix4(a),l.fromBufferAttribute(n,m).applyMatrix4(a),h.fromBufferAttribute(n,f).applyMatrix4(a),d.subVectors(l,c),u.subVectors(h,c),p.crossVectors(d,u);const E=p.length();E<1e-12||(p.divideScalar(E),o&&p.negate(),t(c,l,h,p,[v,m,f]))}}function Ah(i,t,e,n,s){t.forEach(r=>{s[r]!==void 0&&s[r]>=n||(s[r]=n,i.setXYZ(r,e.r,e.g,e.b))})}function N0(i,t={}){var c,l,h;const e=Number(t.safeDeg??45),n=Number(t.warnDeg??60),s={safe:new Ft(((c=t.colors)==null?void 0:c.safe)??"#3FA45B"),warn:new Ft(((l=t.colors)==null?void 0:l.warn)??"#E0A82E"),critical:new Ft(((h=t.colors)==null?void 0:h.critical)??"#C0392B")},r=Math.sin(e*Math.PI/180),a=Math.sin(n*Math.PI/180),o={safe:0,warn:0,critical:0,faces:0};return Vo(i).forEach(d=>{const u=Th(d.geometry),p={};Go(d,(g,v,m,f,E)=>{const w=Math.max(0,-f.y);let y="safe",b=0;w>=a?(y="critical",b=2):w>=r&&(y="warn",b=1),o[y]+=1,o.faces+=1,Ah(u,E,s[y],b,p)}),u.needsUpdate=!0,Ho(d,!0)}),o}function U0(i,t={}){var d,u;const e=Math.max(.01,Number(t.minMm??1)),n=Math.max(200,Number(t.maxSamples??2e4)),s={safe:new Ft(((d=t.colors)==null?void 0:d.safe)??"#3FA45B"),thin:new Ft(((u=t.colors)==null?void 0:u.thin)??"#C0392B")},r=Vo(i),a=Wo.fromMeshes(r),o={minMm:1/0,thinFaces:0,faces:0,sampled:!1,measured:0};if(!a)return{...o,minMm:0};const c=Math.max(1,Math.ceil(a.triangleCount/n));o.sampled=c>1;const l=new I,h=new I;return r.forEach(p=>{const g=Th(p.geometry),v={};let m=0,f=a.diagonal;Go(p,(E,w,y,b,M)=>{if(m%c===0){l.copy(E).add(w).add(y).divideScalar(3),h.copy(b).negate(),l.addScaledVector(h,a.epsilon);const x=a.raycast(l,h,a.diagonal);f=x===null?a.diagonal:x+a.epsilon,o.measured+=1}m+=1,o.faces+=1,o.minMm=Math.min(o.minMm,f);const A=f<e;A&&(o.thinFaces+=1),Ah(g,M,A?s.thin:s.safe,A?1:0,v)}),g.needsUpdate=!0,Ho(p,!0)}),Number.isFinite(o.minMm)||(o.minMm=0),o}function F0(i){Vo(i).forEach(t=>{t.geometry.deleteAttribute("color"),Ho(t,!1)})}function Ho(i,t){(Array.isArray(i.material)?i.material:[i.material]).forEach(n=>{n&&(n.vertexColors=t,n.needsUpdate=!0)})}class Wo{static fromMeshes(t){const e=[],n=new Ke,s=new I;return t.forEach(r=>{Go(r,(a,o,c)=>{e.push(a.x,a.y,a.z,o.x,o.y,o.z,c.x,c.y,c.z),n.expandByPoint(s.copy(a)),n.expandByPoint(s.copy(o)),n.expandByPoint(s.copy(c))})}),e.length>0?new Wo(new Float32Array(e),n):null}constructor(t,e){this.vertices=t,this.triangleCount=t.length/9;const n=e.getSize(new I);this.min=e.min.clone(),this.diagonal=Math.max(n.length(),.001),this.epsilon=Math.max(this.diagonal*1e-5,1e-4);const s=Math.min(64,Math.max(6,Math.round(Math.cbrt(this.triangleCount)*2)));this.cellSize={x:Math.max(n.x/s,this.diagonal/512),y:Math.max(n.y/s,this.diagonal/512),z:Math.max(n.z/s,this.diagonal/512)},this.resolution=s,this.cells=new Map,this.build()}cellIndex(t,e){const n=Math.floor((t-this.min[e])/this.cellSize[e]);return Math.min(this.resolution,Math.max(0,n))}key(t,e,n){const s=this.resolution+1;return(t*s+e)*s+n}build(){for(let t=0;t<this.triangleCount;t+=1){const e=t*9;let n=1/0,s=1/0,r=1/0,a=-1/0,o=-1/0,c=-1/0;for(let v=0;v<3;v+=1){const m=this.vertices[e+v*3],f=this.vertices[e+v*3+1],E=this.vertices[e+v*3+2];n=Math.min(n,m),a=Math.max(a,m),s=Math.min(s,f),o=Math.max(o,f),r=Math.min(r,E),c=Math.max(c,E)}const l=this.cellIndex(n,"x"),h=this.cellIndex(a,"x"),d=this.cellIndex(s,"y"),u=this.cellIndex(o,"y"),p=this.cellIndex(r,"z"),g=this.cellIndex(c,"z");for(let v=l;v<=h;v+=1)for(let m=d;m<=u;m+=1)for(let f=p;f<=g;f+=1){const E=this.key(v,m,f),w=this.cells.get(E);w===void 0?this.cells.set(E,[t]):w.push(t)}}}raycast(t,e,n){const s=Math.max(this.cellSize.x,this.cellSize.y,this.cellSize.z)*2;let r=Math.min(s,n);for(;r<=n;){const a=this.raycastSegment(t,e,r);if(a!==null)return a;if(r>=n)return null;r=Math.min(r*2,n)}return null}raycastSegment(t,e,n){const s=t.x+e.x*n,r=t.y+e.y*n,a=t.z+e.z*n,o=this.cellIndex(Math.min(t.x,s),"x"),c=this.cellIndex(Math.max(t.x,s),"x"),l=this.cellIndex(Math.min(t.y,r),"y"),h=this.cellIndex(Math.max(t.y,r),"y"),d=this.cellIndex(Math.min(t.z,a),"z"),u=this.cellIndex(Math.max(t.z,a),"z");let p=null;const g=new Set;for(let v=o;v<=c;v+=1)for(let m=l;m<=h;m+=1)for(let f=d;f<=u;f+=1){const E=this.cells.get(this.key(v,m,f));if(E!==void 0)for(let w=0;w<E.length;w+=1){const y=E[w];if(g.has(y))continue;g.add(y);const b=this.intersectTriangle(y,t,e);b!==null&&b<=n&&(p===null||b<p)&&(p=b)}}return p}intersectTriangle(t,e,n){const s=t*9,r=this.vertices,a=r[s+3]-r[s],o=r[s+4]-r[s+1],c=r[s+5]-r[s+2],l=r[s+6]-r[s],h=r[s+7]-r[s+1],d=r[s+8]-r[s+2],u=n.y*d-n.z*h,p=n.z*l-n.x*d,g=n.x*h-n.y*l,v=a*u+o*p+c*g;if(Math.abs(v)<1e-12)return null;const m=1/v,f=e.x-r[s],E=e.y-r[s+1],w=e.z-r[s+2],y=(f*u+E*p+w*g)*m;if(y<-1e-6||y>1+1e-6)return null;const b=E*c-w*o,M=w*a-f*c,A=f*o-E*a,x=(n.x*b+n.y*M+n.z*A)*m;if(x<-1e-6||y+x>1+1e-6)return null;const T=(l*b+h*M+d*A)*m;return T>1e-9?T:null}}const O0=9774877,Pc="#B8452F",B0=10,z0=400,Qe=(i,t="flex")=>i&&(i.style.display=t),ze=i=>i&&(i.style.display="none");class ax{constructor({root:t,file:e,buffer:n,bufferFormat:s,config:r,renderer:a,onChange:o,onRemove:c,state:l}){var h,d;this.root=t,this.file=e,this.config=r,this.renderer=a,this.onChange=o??(()=>{}),this.onRemove=c??(()=>{}),this.format=Rr(e.name).toUpperCase(),this.bufferFormat=s??null,this.printerKey=r.defaultPrinter??Object.keys(r.printers??{})[0]??"ender3",this.customVolume={...((d=(h=r.printers)==null?void 0:h[r.customPrinterKey])==null?void 0:d.buildVolume)??{x:300,y:300,z:300}},this.displayMode="solid",this.viewMode=Zn.MATERIAL,this.autoRotate=!1,this.objectRotateMode=!1,this.supportVisible=!0,this.orientation={x:0,y:0,z:0,mirror:{x:1,y:1,z:1}},this.settings=this.defaultSettings(),this.restore(l),this.metrics=null,this.dimensions=null,this.boundingBox=null,this.worldBox=new Ke,this.analysis=null,this.estimate=null,this.fit=null,this.painted=null,this.support=null,this.supportStats=null,this.supportGroup=null,this.exceedGroup=null,this.cacheElements(),this.initScene(),this.buildModel(n),this.bindPrinter(),this.bindToolbar(),this.bindOrientation(),this.bindScale(),this.bindProduction(),this.bindInfill(),this.bindHollow(),this.bindColors(),this.bindFinishing(),this.renderPrinter(),this.syncControls(),this.runAnalysis(),this.refreshEstimate(),this.frameBuildPlate()}cacheElements(){const t=e=>this.root.querySelector(e);this.canvas=t("[data-card-canvas]"),this.titleEl=t("[data-card-title]"),this.fileNameEl=t("[data-card-file]"),this.printerSelect=t("[data-printer-select]"),this.printerNote=t("[data-printer-note]"),this.printerCustomPanel=t("[data-printer-custom]"),this.printerCustomInputs=this.root.querySelectorAll("[data-printer-custom-axis]"),this.buildWarning=t("[data-build-warning]"),this.buildWarningDetail=t("[data-build-warning-detail]"),this.buildUsageBar=t("[data-build-usage-bar]"),this.analysisList=t("[data-analysis-list]"),this.analysisLegend=t("[data-analysis-legend]"),this.technologySelect=t("[data-technology-select]"),this.materialSelect=t("[data-material-select]"),this.technologyDescription=t("[data-technology-description]"),this.quantityInput=t("[data-quantity-input]"),this.supportCheckbox=t("[data-support-toggle]"),this.supportNoteEl=t("[data-support-note]"),this.supportRow=t("[data-support-row]"),this.resolutionInputs=this.root.querySelectorAll("[data-resolution-option]"),this.resolutionNotice=t("[data-resolution-notice]"),this.scaleInput=t("[data-scale-input]"),this.scaleSlider=t("[data-scale-slider]"),this.infillDensityInputs=this.root.querySelectorAll("[data-infill-density]"),this.infillPatternInputs=this.root.querySelectorAll("[data-infill-pattern]"),this.infillNote=t("[data-infill-note]"),this.hollowField=t("[data-hollow-field]"),this.hollowToggle=t("[data-hollow-toggle]"),this.hollowSettings=t("[data-hollow-settings]"),this.hollowWall=t("[data-hollow-wall]"),this.hollowDrain=t("[data-hollow-drain]"),this.hollowPosition=t("[data-hollow-position]"),this.colorSwatches=this.root.querySelectorAll("[data-material-color]"),this.finishingSelect=t("[data-finishing-select]"),this.finishingNote=t("[data-finishing-note]")}restore(t){var e;!t||typeof t!="object"||(t.printer&&((e=this.config.printers)!=null&&e[t.printer])&&(this.printerKey=t.printer),t.customVolume&&(this.customVolume={...this.customVolume,...t.customVolume}),t.settings&&(this.settings={...this.settings,...t.settings,hollow:{...this.settings.hollow,...t.settings.hollow??{}}}),t.orientation&&(this.orientation={...this.orientation,...t.orientation,mirror:{...this.orientation.mirror,...t.orientation.mirror??{}}}),t.viewMode&&(this.viewMode=t.viewMode),t.displayMode&&(this.displayMode=t.displayMode))}snapshot(){return{printer:this.printerKey,customVolume:{...this.customVolume},viewMode:this.viewMode,displayMode:this.displayMode,orientation:{x:this.orientation.x,y:this.orientation.y,z:this.orientation.z,mirror:{...this.orientation.mirror}},settings:{...this.settings,hollow:{...this.settings.hollow}}}}defaultSettings(){var s,r,a,o,c,l,h,d,u,p,g,v;const e=Object.keys(this.config.technologies??{})[0]??"FDM",n=(s=this.config.technologies)==null?void 0:s[e];return{technology:e,material:((a=(r=n==null?void 0:n.materials)==null?void 0:r[0])==null?void 0:a.name)??"",quantity:1,resolution:this.config.defaultResolution??"0.25",support:!!((o=this.config.support)!=null&&o.defaultEnabled),scale:1,infillDensity:Number((n==null?void 0:n.defaultInfill)??1),infillPattern:((c=this.config.infill)==null?void 0:c.defaultPattern)??"grid",color:((l=this.config.materialColors)==null?void 0:l.default)??"merah",finishing:((h=this.config.finishing)==null?void 0:h.default)??"none",hollow:{enabled:!1,wallThicknessMm:Number(((u=(d=this.config.hollow)==null?void 0:d.wallThickness)==null?void 0:u.default)??2),drainDiameterMm:Number(((g=(p=this.config.hollow)==null?void 0:p.drainDiameter)==null?void 0:g.default)??3.5),drainPosition:((v=this.config.hollow)==null?void 0:v.defaultDrainPosition)??"bottom"}}}initScene(){this.scene=new Yc,this.scene.background=new Ft(16315892),this.camera=new We(45,1,.1,5e3),this.camera.position.set(120,90,160),this.controls=new a_(this.camera,this.canvas),this.controls.enableDamping=!0,this.controls.dampingFactor=.08,this.controls.rotateSpeed=.85,this.controls.panSpeed=.8,this.controls.zoomSpeed=.9,this.controls.screenSpacePanning=!0,this.controls.minDistance=1,this.controls.maxDistance=4e3,this.scene.add(new ih(16777215,14274507,1.15));const t=new gs(16777215,2.1);t.position.set(1,1.6,1.2),this.scene.add(t);const e=new gs(16770782,.7);e.position.set(-1.4,.5,-.8),this.scene.add(e);const n=new gs(O0,1.1);n.position.set(-.6,-1,1.4),this.scene.add(n),this.plate=new D0(this.scene,this.buildVolume()),this.view=this.renderer.register({canvas:this.canvas,scene:this.scene,camera:this.camera,controls:this.controls,isAnimating:()=>this.autoRotate}),this.controls.addEventListener("change",()=>this.renderer.invalidate(this.view))}invalidate(){this.controls.autoRotate=this.autoRotate,this.controls.autoRotateSpeed=1.6,this.renderer.invalidate(this.view)}buildModel(t){const e=this.colorHex(this.settings.color),n=this.bufferFormat??Rr(this.file.name);n==="obj"?this.object=this.buildFromObj(t,e):n==="3mf"?this.object=this.buildFrom3mf(t,e):this.object=this.buildFromStl(t,e);const r=new Ke().setFromObject(this.object).getCenter(new I);this.object.position.sub(r),this.pivot=new $e,this.pivot.add(this.object),this.scene.add(this.pivot),this.applyOrientation({refresh:!1})}buildFromStl(t,e){const n=new v_().parse(t);if(!n.getAttribute("position")||n.getAttribute("position").count===0)throw new Error("Geometri STL kosong");return n.getAttribute("normal")||n.computeVertexNormals(),new Me(n,this.createMaterial(e))}buildFromObj(t,e){const n=new TextDecoder().decode(t);return this.dressGroup(new T_().parse(n),e,"OBJ")}buildFrom3mf(t,e){return this.dressGroup(new X_().parse(t),e,"3MF")}dressGroup(t,e,n){let s=!1;if(t.traverse(r=>{var a,o;r.isMesh&&((o=(a=r.geometry)==null?void 0:a.getAttribute("position"))!=null&&o.count&&(s=!0),r.geometry.getAttribute("normal")||r.geometry.computeVertexNormals(),r.material=this.createMaterial(e))}),!s)throw new Error(`Tidak ada mesh pada file ${n}`);return t}createMaterial(t){const e=this.displayMode==="transparent";return new Ki({color:new Ft(t??Pc),roughness:.52,metalness:.18,wireframe:this.displayMode==="wireframe",transparent:e,opacity:e?.38:1,depthWrite:!e,side:Xe})}colorHex(t){var e,n,s;return((s=(n=(e=this.config.materialColors)==null?void 0:e.options)==null?void 0:n[t])==null?void 0:s.hex)??Pc}printerConfig(){var t;return((t=this.config.printers)==null?void 0:t[this.printerKey])??{name:"Printer",buildVolume:{x:220,y:220,z:250},speedFactor:1,rateFactor:1}}isCustomPrinter(){return!!this.printerConfig().custom}buildVolume(){const t=this.printerConfig();if(!t.custom)return{...t.buildVolume};const e=this.config.customPrinterLimits??{min:50,max:1e3},n=(s,r)=>{const a=Number(s);return Number.isFinite(a)?Math.min(e.max,Math.max(e.min,a)):r};return{x:n(this.customVolume.x,t.buildVolume.x),y:n(this.customVolume.y,t.buildVolume.y),z:n(this.customVolume.z,t.buildVolume.z)}}bindPrinter(){var t;(t=this.printerSelect)==null||t.addEventListener("change",()=>{this.printerKey=this.printerSelect.value,this.renderPrinter(),this.applyPrinter()}),this.printerCustomInputs.forEach(e=>{e.addEventListener("input",()=>{this.customVolume[e.dataset.printerCustomAxis]=e.value,this.applyPrinter()})})}renderPrinter(){const t=this.printerConfig();this.printerSelect&&(this.printerSelect.value=this.printerKey),this.printerNote&&(this.printerNote.textContent=t.note??""),this.isCustomPrinter()?Qe(this.printerCustomPanel,"block"):ze(this.printerCustomPanel)}applyPrinter(){this.plate.setVolume(this.buildVolume()),this.applyOrientation(),this.frameBuildPlate()}applyOrientation({refresh:t=!0,precise:e=!0}={}){if(!this.pivot)return;const{x:n,y:s,z:r,mirror:a}=this.orientation,o=Math.max(.01,Number(this.settings.scale)||1),c=u=>u*Math.PI/180;this.pivot.position.set(0,0,0),this.pivot.rotation.set(c(n),c(s),c(r)),this.pivot.scale.set(a.x*o,a.y*o,a.z*o),this.pivot.updateMatrixWorld(!0);const l=new Ke().setFromObject(this.pivot,e),h=l.getSize(new I),d=l.getCenter(new I);this.pivot.position.x-=d.x,this.pivot.position.z-=d.z,this.pivot.position.y-=l.min.y,this.pivot.updateMatrixWorld(!0),this.dimensions={x:h.x,y:h.y,z:h.z},this.boundingBox={min:{x:-h.x/2,y:0,z:-h.z/2},max:{x:h.x/2,y:h.y,z:h.z/2}},this.worldBox.setFromCenterAndSize(new I(0,h.y/2,0),h),this.refreshFit(),this.invalidate(),t&&(this.repaint(),this.refreshAnalysis(),this.refreshEstimate(),this.renderStats(),this.renderOrientationInputs())}bindOrientation(){var t,e;this.root.querySelectorAll("[data-rotate-step]").forEach(n=>{n.addEventListener("click",()=>{this.orientation[n.dataset.axis]=cr(this.orientation[n.dataset.axis]+Number(n.dataset.rotateStep)),this.applyOrientation()})}),this.root.querySelectorAll("[data-rotate-slider]").forEach(n=>{n.addEventListener("input",()=>{this.orientation[n.dataset.rotateSlider]=cr(Number(n.value)||0),this.applyOrientation()})}),this.root.querySelectorAll("[data-mirror]").forEach(n=>{n.addEventListener("click",()=>{this.orientation.mirror[n.dataset.mirror]*=-1,this.applyOrientation()})}),(t=this.root.querySelector('[data-action="reset-orientation"]'))==null||t.addEventListener("click",()=>{this.orientation={x:0,y:0,z:0,mirror:{x:1,y:1,z:1}},this.applyOrientation()}),this.objectRotateToggle=this.root.querySelector('[data-action="rotate-object-mode"]'),(e=this.objectRotateToggle)==null||e.addEventListener("click",()=>{this.objectRotateMode=!this.objectRotateMode,this.objectRotateToggle.setAttribute("aria-pressed",String(this.objectRotateMode)),this.controls.enableRotate=!this.objectRotateMode,this.canvas.style.cursor=this.objectRotateMode?"grab":""}),this.bindObjectDrag()}bindObjectDrag(){let t=!1,e=0,n=0;this.canvas.addEventListener("pointerdown",r=>{!this.objectRotateMode||r.button!==0||(t=!0,e=r.clientX,n=r.clientY,this.canvas.style.cursor="grabbing",this.canvas.setPointerCapture(r.pointerId))}),this.canvas.addEventListener("pointermove",r=>{t&&(this.orientation.y=cr(this.orientation.y+(r.clientX-e)*.6),this.orientation.x=cr(this.orientation.x+(r.clientY-n)*.6),e=r.clientX,n=r.clientY,this.applyOrientation({refresh:!1,precise:!1}),this.renderOrientationInputs())});const s=r=>{if(t){t=!1,this.canvas.style.cursor=this.objectRotateMode?"grab":"";try{this.canvas.releasePointerCapture(r.pointerId)}catch{}this.applyOrientation()}};this.canvas.addEventListener("pointerup",s),this.canvas.addEventListener("pointercancel",s)}renderOrientationInputs(){["x","y","z"].forEach(t=>{const e=this.root.querySelector(`[data-rotate-slider="${t}"]`),n=this.root.querySelector(`[data-rotate-value="${t}"]`),s=this.root.querySelector(`[data-mirror="${t}"]`);e&&(e.value=String(Math.round(this.orientation[t]))),n&&(n.textContent=`${Math.round(this.orientation[t])}°`),s&&s.setAttribute("aria-pressed",String(this.orientation.mirror[t]===-1))})}refreshFit(){Cc(this.exceedGroup),this.exceedGroup=null,this.fit=L0(this.worldBox,this.plate.bounds),this.fit.exceeds&&(this.exceedGroup=I0(this.fit.regions),this.scene.add(this.exceedGroup)),this.plate.setExceeded(this.fit.exceeds),this.renderBuildInfo()}renderBuildInfo(){var r;const t=this.plate.size,e=t.width*t.depth*t.height;this.setText('[data-build="printer"]',`${qt(t.width,0)} × ${qt(t.depth,0)} × ${qt(t.height,0)} mm`),this.setText('[data-build="model"]',this.dimensions?`${qt(this.dimensions.x,0)} × ${qt(this.dimensions.z,0)} × ${qt(this.dimensions.y,0)} mm`:"-");const n=this.dimensions?this.dimensions.x*this.dimensions.y*this.dimensions.z:0,s=e>0?n/e:0;if(this.setText('[data-build="usage"]',Ta(Math.min(s,9.99),s<.1?1:0)),this.buildUsageBar&&(this.buildUsageBar.style.width=`${Math.min(100,s*100)}%`,this.buildUsageBar.classList.toggle("bg-brand-600",s<=1),this.buildUsageBar.classList.toggle("bg-red-600",s>1)),!!this.buildWarning){if(!((r=this.fit)!=null&&r.exceeds)){ze(this.buildWarning);return}this.buildWarningDetail&&(this.buildWarningDetail.textContent=`${this.file.name} melewati area cetak ${qt(t.width,0)} × ${qt(t.depth,0)} × ${qt(t.height,0)} mm.`),Qe(this.buildWarning,"block")}}bindScale(){var e,n;const t=s=>this.setScale(s);(e=this.scaleInput)==null||e.addEventListener("input",()=>t(this.scaleInput.value)),(n=this.scaleSlider)==null||n.addEventListener("input",()=>t(this.scaleSlider.value)),this.root.querySelectorAll("[data-scale-step]").forEach(s=>{s.addEventListener("click",()=>{t(Math.round(this.settings.scale*100)+Number(s.dataset.scaleStep))})}),this.root.querySelectorAll("[data-scale-preset]").forEach(s=>{s.addEventListener("click",()=>t(s.dataset.scalePreset))})}setScale(t){const e=Math.min(z0,Math.max(B0,Math.round(Number(t)||100)));this.settings.scale=e/100,this.renderScaleInputs(),this.applyOrientation()}renderScaleInputs(){const t=Math.round(this.settings.scale*100);this.scaleInput&&document.activeElement!==this.scaleInput&&(this.scaleInput.value=String(t)),this.scaleSlider&&(this.scaleSlider.value=String(t)),this.root.querySelectorAll("[data-scale-preset]").forEach(e=>{e.setAttribute("aria-pressed",String(Number(e.dataset.scalePreset)===t))})}bindInfill(){this.infillDensityInputs.forEach(t=>{t.addEventListener("change",()=>{this.settings.infillDensity=Number(t.value),this.refreshEstimate()})}),this.infillPatternInputs.forEach(t=>{t.addEventListener("change",()=>{this.settings.infillPattern=t.value,this.refreshEstimate()})})}renderInfillInputs(){var n;const t=Number(this.settings.infillDensity);this.infillDensityInputs.forEach(s=>{s.checked=Math.abs(Number(s.value)-t)<1e-6}),this.infillPatternInputs.forEach(s=>{s.checked=s.value===this.settings.infillPattern});const e=((n=this.technology())==null?void 0:n.infillNote)??null;this.infillNote&&(this.infillNote.textContent=e??"",e?Qe(this.infillNote,"block"):ze(this.infillNote))}bindHollow(){var t,e;(t=this.hollowToggle)==null||t.addEventListener("change",()=>{this.settings.hollow.enabled=this.hollowToggle.checked,this.renderHollowInputs(),this.refreshEstimate()}),[[this.hollowWall,"wallThicknessMm"],[this.hollowDrain,"drainDiameterMm"]].forEach(([n,s])=>{n==null||n.addEventListener("input",()=>{this.settings.hollow[s]=Number(n.value),this.renderHollowInputs(),this.refreshEstimate()})}),(e=this.hollowPosition)==null||e.addEventListener("change",()=>{this.settings.hollow.drainPosition=this.hollowPosition.value,this.renderHollowInputs(),this.refreshEstimate()})}syncHollowAvailability(){var e;const t=!!((e=this.technology())!=null&&e.allowsHollow);t?Qe(this.hollowField,"block"):ze(this.hollowField),t||(this.settings.hollow.enabled=!1)}renderHollowInputs(){var e,n,s;const t=this.settings.hollow;this.hollowToggle&&(this.hollowToggle.checked=!!t.enabled,t.enabled?Qe(this.hollowSettings,"block"):ze(this.hollowSettings),this.hollowWall&&(this.hollowWall.value=String(t.wallThicknessMm)),this.hollowDrain&&(this.hollowDrain.value=String(t.drainDiameterMm)),this.hollowPosition&&(this.hollowPosition.value=t.drainPosition),this.setText('[data-hollow-value="wall"]',`${qt(t.wallThicknessMm,1)} mm`),this.setText('[data-hollow-value="drain"]',`${qt(t.drainDiameterMm,1)} mm`),this.setText("[data-hollow-position-note]",((s=(n=(e=this.hollowPosition)==null?void 0:e.selectedOptions)==null?void 0:n[0])==null?void 0:s.dataset.description)??""),this.renderHollowSaving())}renderHollowSaving(){var n;const t=this.root.querySelector("[data-hollow-saving]");if(!t)return;if(!((n=this.estimate)!=null&&n.hollowEnabled)){t.textContent="Aktifkan untuk melihat berapa banyak resin yang dihemat.";return}const e=this.estimate.modelVolumeCm3>0?this.estimate.hollowSavedCm3/this.estimate.modelVolumeCm3:0;t.textContent=`Menghemat ${qt(this.estimate.hollowSavedCm3,2)} cm³ resin (${Ta(e,0)} dari volume padat) pada dinding ${qt(this.estimate.hollowWallThicknessMm??0,1)} mm.`}bindColors(){this.colorSwatches.forEach(t=>{t.addEventListener("click",()=>{this.settings.color=t.dataset.materialColor,this.renderColorInputs(),this.applyAppearance(),this.onChange(this)})})}availableColors(){var e,n;const t=((e=this.material())==null?void 0:e.colors)??[];return t.length?t:Object.keys(((n=this.config.materialColors)==null?void 0:n.options)??{})}renderColorInputs(){const t=this.availableColors();this.colorSwatches.forEach(e=>{const n=e.dataset.materialColor,s=t.includes(n);e.style.display=s?"":"none",e.setAttribute("aria-pressed",String(s&&n===this.settings.color))})}syncColorAvailability(){var e;const t=this.availableColors();if(!t.includes(this.settings.color)){const n=(e=this.config.materialColors)==null?void 0:e.default;this.settings.color=t.includes(n)?n:t[0],this.applyAppearance()}this.renderColorInputs()}bindFinishing(){var t;(t=this.finishingSelect)==null||t.addEventListener("change",()=>{this.settings.finishing=this.finishingSelect.value,this.renderFinishingNote(),this.refreshEstimate()})}renderFinishingInputs(){this.finishingSelect&&(this.finishingSelect.value=this.settings.finishing),this.renderFinishingNote()}renderFinishingNote(){var e,n;if(!this.finishingNote)return;const t=(n=(e=this.config.finishing)==null?void 0:e.options)==null?void 0:n[this.settings.finishing];this.finishingNote.textContent=(t==null?void 0:t.description)??"Pengerjaan setelah part selesai dicetak."}bindToolbar(){var t,e,n,s,r,a,o;(t=this.root.querySelector('[data-action="reset"]'))==null||t.addEventListener("click",()=>this.frameBuildPlate()),(e=this.root.querySelector('[data-action="focus"]'))==null||e.addEventListener("click",()=>this.focusModel()),(n=this.root.querySelector('[data-action="rotate"]'))==null||n.addEventListener("click",c=>{this.autoRotate=!this.autoRotate,c.currentTarget.setAttribute("aria-pressed",String(this.autoRotate)),this.invalidate()}),(s=this.root.querySelector('[data-action="grid"]'))==null||s.addEventListener("click",c=>{this.plate.setGridVisible(!this.plate.gridVisible),c.currentTarget.setAttribute("aria-pressed",String(this.plate.gridVisible)),this.invalidate()}),(r=this.root.querySelector('[data-action="axis"]'))==null||r.addEventListener("click",c=>{this.plate.setAxesVisible(!this.plate.axesVisible),c.currentTarget.setAttribute("aria-pressed",String(this.plate.axesVisible)),this.invalidate()}),(a=this.root.querySelector('[data-action="support-visibility"]'))==null||a.addEventListener("click",c=>{this.supportVisible=!this.supportVisible,c.currentTarget.setAttribute("aria-pressed",String(this.supportVisible)),this.supportGroup&&(this.supportGroup.visible=this.supportVisible),this.invalidate()}),(o=this.root.querySelector('[data-action="remove"]'))==null||o.addEventListener("click",()=>this.onRemove(this)),this.root.querySelectorAll("[data-mode]").forEach(c=>{c.addEventListener("click",()=>this.setDisplayMode(c.dataset.mode))}),this.root.querySelectorAll("[data-view-mode]").forEach(c=>{c.addEventListener("click",()=>this.setViewMode(c.dataset.viewMode))}),this.root.querySelectorAll("[data-view]").forEach(c=>{c.addEventListener("click",()=>this.applyViewPreset(c.dataset.view))})}setDisplayMode(t){this.displayMode=t,this.root.querySelectorAll("[data-mode]").forEach(e=>{e.setAttribute("aria-pressed",String(e.dataset.mode===t))}),this.applyAppearance()}setViewMode(t){this.viewMode!==t&&(this.viewMode=t,this.root.querySelectorAll("[data-view-mode]").forEach(e=>{e.setAttribute("aria-pressed",String(e.dataset.viewMode===t))}),this.repaint())}repaint(){var e,n,s,r,a,o,c;if(!this.object)return;F0(this.object),this.painted=null;const t=this.config.analysis??{};if(this.viewMode===Zn.OVERHANG)this.painted={mode:Zn.OVERHANG,...N0(this.object,{safeDeg:(e=t.overhang)==null?void 0:e.safeDeg,warnDeg:(n=t.overhang)==null?void 0:n.warnDeg,colors:(s=t.overhang)==null?void 0:s.colors})};else if(this.viewMode===Zn.THICKNESS){const l=Number(((r=t.wallThickness)==null?void 0:r.maxTriangles)??25e4);if((((a=this.metrics)==null?void 0:a.triangles)??0)>l)this.painted={mode:Zn.THICKNESS,skipped:!0};else{const h=this.minWallThickness();this.painted={mode:Zn.THICKNESS,minWallMm:h,...U0(this.object,{minMm:h,colors:(o=t.wallThickness)==null?void 0:o.colors,maxSamples:(c=t.wallThickness)==null?void 0:c.maxSamples})}}}this.applyAppearance(),this.renderAnalysisLegend()}minWallThickness(){var t;return Number(((t=this.technology())==null?void 0:t.minWallThicknessMm)??1)}applyAppearance(){if(!this.object)return;const t=!!this.painted&&!this.painted.skipped,e=this.displayMode==="transparent",n=t?"#FFFFFF":this.colorHex(this.settings.color);this.object.traverse(s=>{s.isMesh&&(s.material.color.set(n),s.material.wireframe=this.displayMode==="wireframe",s.material.transparent=e,s.material.opacity=e?.38:1,s.material.depthWrite=!e,s.material.needsUpdate=!0)}),this.invalidate()}renderAnalysisLegend(){var c,l,h,d,u,p,g;if(!this.analysisLegend)return;if(this.viewMode===Zn.MATERIAL){ze(this.analysisLegend);return}const t=this.config.analysis??{},e=this.analysisLegend.querySelector("[data-analysis-legend-title]"),n=this.analysisLegend.querySelector("[data-analysis-legend-items]"),s=this.analysisLegend.querySelector("[data-analysis-legend-note]");let r,a,o="";if(this.viewMode===Zn.OVERHANG){const v=((c=t.overhang)==null?void 0:c.safeDeg)??45,m=((l=t.overhang)==null?void 0:l.warnDeg)??60,f=((h=t.overhang)==null?void 0:h.colors)??{};r="Overhang Analysis",a=[[f.safe??"#3FA45B",`Aman: di bawah ${v}°`],[f.warn??"#E0A82E",`Mungkin perlu support: ${v}° sampai ${m}°`],[f.critical??"#C0392B",`Wajib support: di atas ${m}°`]],o="Sudut diukur dari bidang tegak: dinding tegak 0°, langit-langit mendatar 90°."}else{const v=((d=t.wallThickness)==null?void 0:d.colors)??{},m=this.minWallThickness();r="Wall Thickness Analysis",a=[[v.safe??"#3FA45B",`Aman: ${qt(m,1)} mm ke atas`],[v.thin??"#C0392B",`Terlalu tipis: di bawah ${qt(m,1)} mm`]],(u=this.painted)!=null&&u.skipped?o="Model ini terlalu rapat untuk diukur seketika, jadi pewarnaannya dilewati.":(p=this.painted)!=null&&p.sampled?o=`Diukur dari ${ls(this.painted.measured)} titik sampel; dinding tertipis ${qt(this.painted.minMm,2)} mm.`:((g=this.painted)==null?void 0:g.minMm)!==void 0&&(o=`Dinding tertipis yang terukur ${qt(this.painted.minMm,2)} mm.`)}e&&(e.textContent=r),n&&(n.innerHTML=a.map(([v,m])=>`
                        <li class="flex items-center gap-2 text-[0.65rem] font-semibold text-white">
                            <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-sm" style="background-color: ${V0(v)}"></span>
                            ${jn(m)}
                        </li>
                    `).join("")),s&&(s.textContent=o),Qe(this.analysisLegend,"block")}frameBuildPlate(){const{width:t,depth:e,height:n}=this.plate.size,s=new I(t,n,e);this.homeTarget=new I(0,n/4,0),this.homeDirection=new I(.85,.62,1).normalize(),this.frameRadius=Math.max(s.length()/2,.5),this.frameDistance=this.frameRadius/Math.sin(this.camera.fov*Math.PI/360)*1.15,this.camera.near=Math.max(this.frameDistance/1e3,.01),this.camera.far=this.frameDistance*100,this.applyViewDirection(this.homeDirection)}focusModel(){if(!this.dimensions)return;const t=new I(this.dimensions.x,this.dimensions.y,this.dimensions.z);this.homeTarget=new I(0,t.y/2,0),this.frameRadius=Math.max(t.length()/2,.5),this.frameDistance=this.frameRadius/Math.sin(this.camera.fov*Math.PI/360)*1.25,this.camera.near=Math.max(this.frameDistance/1e3,.01),this.camera.far=Math.max(this.frameDistance*100,this.plate.size.width*20),this.applyViewDirection(this.homeDirection??new I(.85,.62,1).normalize())}applyViewDirection(t){!this.homeTarget||!t||(this.homeDirection=t.clone().normalize(),this.camera.position.copy(this.homeDirection).multiplyScalar(this.frameDistance).add(this.homeTarget),this.camera.updateProjectionMatrix(),this.controls.target.copy(this.homeTarget),this.controls.update(),this.invalidate())}applyViewPreset(t){const n={front:[0,0,1],back:[0,0,-1],right:[1,0,0],left:[-1,0,0],top:[1e-4,1,1e-4],bottom:[1e-4,-1,1e-4],isometric:[.85,.62,1]}[t];n&&this.applyViewDirection(new I(...n))}bindProduction(){var t,e,n,s;this.populateTechnologies(),(t=this.technologySelect)==null||t.addEventListener("change",()=>{var r;this.settings.technology=this.technologySelect.value,this.populateMaterials(),this.settings.material=this.materialSelect.value,this.settings.infillDensity=Number(((r=this.technology())==null?void 0:r.defaultInfill)??1),this.renderTechnologyDescription(),this.syncSupportAvailability(),this.syncHollowAvailability(),this.syncColorAvailability(),this.renderInfillInputs(),this.renderHollowInputs(),this.renderResolutionNotice(),this.refreshAnalysis(),this.refreshEstimate(),this.repaint()}),(e=this.materialSelect)==null||e.addEventListener("change",()=>{this.settings.material=this.materialSelect.value,this.syncColorAvailability(),this.refreshEstimate()}),(n=this.quantityInput)==null||n.addEventListener("input",()=>{this.settings.quantity=Math.max(1,parseInt(this.quantityInput.value,10)||1),this.refreshEstimate()}),(s=this.supportCheckbox)==null||s.addEventListener("change",()=>{this.settings.support=!!(this.supportCheckbox.checked&&!this.supportCheckbox.disabled),this.refreshEstimate()}),this.resolutionInputs.forEach(r=>{r.addEventListener("change",()=>{this.settings.resolution=r.value,this.renderResolutionNotice(),this.refreshEstimate()})})}syncControls(){this.titleEl&&(this.titleEl.textContent=`Printer ${this.position??1}`),this.fileNameEl&&(this.fileNameEl.textContent=this.file.name),this.technologySelect&&(this.technologySelect.value=this.settings.technology,this.populateMaterials(),this.materialSelect.value=this.settings.material),this.quantityInput&&(this.quantityInput.value=String(this.settings.quantity)),this.resolutionInputs.forEach(t=>{t.checked=t.value===this.settings.resolution}),this.supportCheckbox&&(this.supportCheckbox.checked=!!this.settings.support),this.renderScaleInputs(),this.renderInfillInputs(),this.syncColorAvailability(),this.renderFinishingInputs(),this.renderTechnologyDescription(),this.syncSupportAvailability(),this.syncHollowAvailability(),this.renderHollowInputs(),this.renderResolutionNotice(),this.renderOrientationInputs()}populateTechnologies(){this.technologySelect&&(this.technologySelect.innerHTML=Object.keys(this.config.technologies??{}).map(t=>`<option value="${t}">${t} (${jn(this.config.technologies[t].name)})</option>`).join(""),this.populateMaterials())}populateMaterials(){var t;this.materialSelect&&(this.materialSelect.innerHTML=(((t=this.technology())==null?void 0:t.materials)??[]).map(e=>`<option value="${jn(e.name)}">${jn(Ac(e))}</option>`).join(""))}renderTechnologyDescription(){const t=this.technology();if(!this.technologyDescription||!t)return;const e=t.buildVolume;this.technologyDescription.innerHTML=`
            <p class="text-sm leading-relaxed text-ink-600">${jn(t.description)}</p>
            <p class="mt-3 text-xs font-semibold uppercase tracking-[0.12em] text-ink-400">
                Kapasitas teknologi ${e.x} × ${e.y} × ${e.z} mm
            </p>
        `}syncSupportAvailability(){var n;if(!this.supportCheckbox)return;const t=this.technology(),e=wr(t);if(this.supportCheckbox.disabled=!e,e||(this.supportCheckbox.checked=!1,this.settings.support=!1),(n=this.supportCheckbox.closest("[data-support-field]"))==null||n.classList.toggle("opacity-50",!e),this.supportNoteEl){const s=c0(t);this.supportNoteEl.textContent=s??"",s?Qe(this.supportNoteEl,"block"):ze(this.supportNoteEl)}}technology(){var e;const t=(e=this.config.technologies)==null?void 0:e[this.settings.technology];return t?{...t,code:this.settings.technology}:null}material(){var t,e;return((e=(t=this.technology())==null?void 0:t.materials)==null?void 0:e.find(n=>n.name===this.settings.material))??null}resolution(){var t;return((t=this.config.resolutions)==null?void 0:t[this.settings.resolution])??null}renderResolutionNotice(){if(!this.resolutionNotice)return;const t=this.technology(),e=t==null?void 0:t.layerHeightRange,n=this.resolution();if(!e||!n){ze(this.resolutionNotice);return}const s=Number(n.layerHeight),r=Number(e.min),a=Number(e.max);if(s>=r&&s<=a){ze(this.resolutionNotice);return}const o=l=>qt(l,l*100%1===0?2:3),c=r===a?`tetap ${o(r)} mm`:`${o(r)} – ${o(a)} mm`;this.resolutionNotice.textContent=`Tebal lapisan ${t.code} ${c}. Pilihan ini di luar rentang tersebut, tim kami akan menyesuaikannya saat produksi.`,Qe(this.resolutionNotice,"block")}rebuildSupportVisual(){var n,s;if(this.detachSupport(),!this.object||!this.supportEnabled()){this.supportStats=null,this.renderSupportStats();return}const t=((n=this.config.support)==null?void 0:n.visual)??{},e=b0(this.pivot,{overhangAngleDeg:t.overhangAngleDeg,gridSizeMm:t.gridSizeMm,maxCellsPerAxis:t.maxCellsPerAxis,pillarShrink:t.pillarShrink,minPillarHeightMm:t.minPillarHeightMm,plateToleranceMm:t.plateToleranceMm,baseHeightMm:t.baseHeightMm,baseExpand:t.baseExpand,color:t.color,opacity:t.opacity,infill:(s=this.config.support)==null?void 0:s.infill});this.supportGroup=e.group,this.supportGroup.visible=this.supportVisible,this.supportStats=e,this.scene.add(this.supportGroup),this.renderSupportStats()}detachSupport(){this.supportGroup&&(this.scene.remove(this.supportGroup),A0(this.supportGroup),this.supportGroup=null)}supportEnabled(){return!!this.settings.support&&wr(this.technology())}renderSupportStats(){const t=this.root.querySelector("[data-support-stats]"),e=this.root.querySelector("[data-support-legend]"),n=this.root.querySelector("[data-support-empty]"),s=this.root.querySelector('[data-action="support-visibility"]');if(!t)return;const r=this.supportStats;if(!r||r.pillarCount===0){ze(t),ze(e),r?Qe(n,"block"):ze(n),s&&(s.disabled=!0);return}ze(n),Qe(e,"block"),this.setText('[data-support-stat="support-pillars"]',ls(r.pillarCount)),this.setText('[data-support-stat="support-overhang"]',ls(r.overhangFaces)),this.setText('[data-support-stat="support-grid"]',`${qt(r.cellSizeMm,1)} mm`),Qe(t,"block"),s&&(s.disabled=!1,s.setAttribute("aria-pressed",String(this.supportVisible)))}runAnalysis(){var t;this.metrics=Z_(this.object,{maxTrianglesForTopology:((t=this.config.limits)==null?void 0:t.maxTrianglesFullAnalysis)??4e5}),this.refreshAnalysis(),this.renderStats()}refreshAnalysis(){var n,s;if(!this.metrics||!this.dimensions)return;const t=this.technology(),e=this.plate.size;this.analysis=j_(this.metrics,this.dimensions,t?{...t,code:this.printerConfig().name,buildVolume:{x:e.width,y:e.depth,z:e.height}}:null,{minDimension:((n=this.config.limits)==null?void 0:n.minDimensionMm)??2,warnDimension:((s=this.config.limits)==null?void 0:s.warnDimensionMm)??5}),this.renderAnalysis()}renderAnalysis(){if(!this.analysisList||!this.analysis)return;const t={[os.PASS]:{dot:"bg-emerald-500",chip:"bg-emerald-50 text-emerald-700 border-emerald-200",label:"Aman"},[os.WARN]:{dot:"bg-amber-500",chip:"bg-amber-50 text-amber-700 border-amber-200",label:"Perlu perbaikan"},[os.FAIL]:{dot:"bg-brand-600",chip:"bg-brand-50 text-brand-700 border-brand-200",label:"Bermasalah"},[os.SKIP]:{dot:"bg-ink-300",chip:"bg-ink-50 text-ink-500 border-ink-200",label:"Dilewati"}};this.analysisList.innerHTML=this.analysis.checks.map(e=>{const n=t[e.status]??t[os.SKIP];return`
                    <li class="flex gap-3 rounded-xl border border-ink-100 bg-white p-4">
                        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full ${n.dot}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-bold text-ink-900">${jn(e.label)}</p>
                                <span class="rounded-full border px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-[0.1em] ${n.chip}">${n.label}</span>
                            </div>
                            <p class="mt-1.5 text-xs leading-relaxed text-ink-500">${jn(e.message)}</p>
                        </div>
                    </li>
                `}).join("")}renderStats(){if(!this.metrics||!this.dimensions)return;const{x:t,y:e,z:n}=this.dimensions,s=this.settings.scale,r=this.metrics.volumeMm3/1e3*s**3;this.setStat("name",this.file.name),this.setStat("format",`${this.format} (${v0[this.format]??"Model 3D"})`),this.setStat("size",k0(this.file.size)),this.setStat("vertices",ls(this.metrics.vertices)),this.setStat("triangles",ls(this.metrics.triangles)),this.setStat("dimensions",`${qt(t,2)} × ${qt(e,2)} × ${qt(n,2)} mm`+(s!==1?` (skala ${Math.round(s*100)}%)`:"")),this.setStat("bounding-box",`min (${qt(this.boundingBox.min.x,1)}, ${qt(this.boundingBox.min.y,1)}, ${qt(this.boundingBox.min.z,1)}) sampai max (${qt(this.boundingBox.max.x,1)}, ${qt(this.boundingBox.max.y,1)}, ${qt(this.boundingBox.max.z,1)}) mm`),this.setStat("surface-area",`${qt(this.metrics.surfaceAreaMm2/100*s**2,2)} cm²`);const a=this.root.querySelector("[data-volume-note]");this.metrics.topologyAnalyzed&&!this.metrics.isWatertight?(this.setStat("volume",`± ${qt(r,3)} cm³`),Qe(a,"block")):(this.setStat("volume",`${qt(r,3)} cm³`),ze(a))}refreshEstimate(){var r,a,o,c;const t=this.technology(),e=this.material();if(!this.metrics||!t||!e)return;const n=this.metrics.volumeMm3/1e3,s=this.settings.scale;this.rebuildSupportVisual(),this.support=yh(t,e,n*s**3,this.dimensions,{enabled:this.supportEnabled(),config:this.config.support,measuredVolumeCm3:(r=this.supportStats)==null?void 0:r.materialVolumeCm3}),this.estimate=Sh(t,e,n,this.settings.quantity,this.support,this.resolution(),{scale:s,surfaceAreaCm2:this.metrics.surfaceAreaMm2/100,dimensions:this.dimensions,infillDensity:this.settings.infillDensity,infillPattern:this.settings.infillPattern,patterns:(a=this.config.infill)==null?void 0:a.patterns,hollow:{...this.settings.hollow,drainHoles:((o=this.config.hollow)==null?void 0:o.drainCount)??2},printer:this.printerConfig(),printerKey:this.printerKey,finishing:this.settings.finishing,finishings:(c=this.config.finishing)==null?void 0:c.options,cost:this.config.cost,pricing:this.config.pricing}),this.renderEstimate(),this.onChange(this)}renderEstimate(){var s;if(!this.estimate)return;const t=this.technology(),e=this.resolution(),n=this.estimate;this.setEstimate("technology",`${t.code} (${t.name})`),this.setEstimate("material",Ac(this.material()??{name:this.settings.material})),this.setEstimate("quality",(e==null?void 0:e.quality)??"-"),this.setEstimate("volume",`${qt(n.totalMaterialVolumeCm3,2)} cm³`),this.setEstimate("weight",`${qt(n.weightG,1)} gram`),this.setEstimate("support-weight",`${qt(n.supportWeightG,1)} gram`),this.setEstimate("total-weight",`${qt(n.totalWeightG,1)} gram`),this.setEstimate("time",Ec(n.totalMinutes)),this.setEstimate("cost",a0(n.totalCost)),this.setEstimate("quantity",`${this.settings.quantity} pcs`),this.setEstimate("support",this.supportEnabled()?"Ya":"Tidak"),(s=this.supportRow)==null||s.classList.toggle("opacity-40",n.supportWeightG<=0),this.setText("[data-infill-fill]",n.hollowEnabled?"Hollow":Ta(n.fillFactor,n.fillFactor<.1?1:0)),this.renderHollowSaving(),this.renderScaleResults(),this.renderStats()}renderScaleResults(){if(!this.dimensions||!this.estimate)return;const{x:t,y:e,z:n}=this.dimensions;this.setText('[data-scale-result="dimensions"]',`${qt(t,1)} × ${qt(n,1)} × ${qt(e,1)} mm`),this.setText('[data-scale-result="volume"]',`${qt(this.estimate.modelVolumeCm3,2)} cm³`),this.setText('[data-scale-result="weight"]',`${qt(this.estimate.totalWeightG,1)} gram`),this.setText('[data-scale-result="time"]',Ec(this.estimate.totalMinutes))}setPosition(t){this.position=t,this.titleEl&&(this.titleEl.textContent=`Printer ${t}`)}isReady(){return!!(this.metrics&&this.estimate)}setText(t,e){const n=this.root.querySelector(t);n&&(n.textContent=e)}setStat(t,e){this.setText(`[data-stat="${t}"]`,e)}setEstimate(t,e){this.setText(`[data-estimate="${t}"]`,e)}payload(){var e,n,s,r;const t=this.plate.size;return{file:this.file,format:this.format,printer:this.printerKey,printer_name:this.printerConfig().name,build_volume:{x:t.width,y:t.depth,z:t.height},technology:this.settings.technology,material:this.settings.material,quantity:this.settings.quantity,resolution:this.settings.resolution,support_enabled:this.supportEnabled(),support_volume_cm3:((e=this.support)==null?void 0:e.volumeCm3)??null,model_volume_cm3:this.metrics.volumeMm3/1e3,scale_percent:Math.round(this.settings.scale*100),infill_density:this.settings.infillDensity,infill_pattern:this.settings.infillPattern,material_color:this.settings.color,finishing:this.settings.finishing,hollow_enabled:this.settings.hollow.enabled,hollow_wall_thickness_mm:this.settings.hollow.wallThicknessMm,hollow_drain_diameter_mm:this.settings.hollow.drainDiameterMm,hollow_drain_position:this.settings.hollow.drainPosition,fits_build_volume:!((n=this.fit)!=null&&n.exceeds),analysis_status:((s=this.analysis)==null?void 0:s.status)??"warning",analysis:((r=this.analysis)==null?void 0:r.checks)??[],estimate:{totalWeightG:this.estimate.totalWeightG,totalMinutes:this.estimate.totalMinutes,totalCost:this.estimate.totalCost,breakdown:this.estimate.breakdown},model_stats:{vertices:this.metrics.vertices,triangles:this.metrics.triangles,dimensions:this.dimensions,bounding_box:this.boundingBox,volume_cm3:this.metrics.volumeMm3/1e3,surface_area_cm2:this.metrics.surfaceAreaMm2/100,scaled_volume_cm3:this.estimate.modelVolumeCm3,watertight:this.metrics.isWatertight,holes:this.metrics.holeCount,non_manifold_edges:this.metrics.nonManifoldEdges,inconsistent_edges:this.metrics.inconsistentEdges,topology_analyzed:this.metrics.topologyAnalyzed,printer:{key:this.printerKey,name:this.printerConfig().name,build_volume:{x:t.width,y:t.depth,z:t.height}},orientation:{rotation_deg:{x:Math.round(this.orientation.x),y:Math.round(this.orientation.y),z:Math.round(this.orientation.z)},mirrored:Object.entries(this.orientation.mirror).filter(([,a])=>a===-1).map(([a])=>a.toUpperCase())}}}}dispose(){var t;this.renderer.unregister(this.view),this.controls.dispose(),this.detachSupport(),Cc(this.exceedGroup),this.plate.dispose(),(t=this.object)==null||t.traverse(e=>{e.isMesh&&(e.geometry.dispose(),Array.isArray(e.material)?e.material.forEach(n=>n.dispose()):e.material.dispose())}),this.scene.remove(this.pivot),this.root.remove()}}function cr(i){return(i%360+360)%360}function k0(i){if(i<1024)return`${i} B`;const t=["KB","MB","GB"];let e=i/1024,n=0;for(;e>=1024&&n<t.length-1;)e/=1024,n+=1;return`${e.toFixed(e<10?1:0)} ${t[n]}`}function jn(i){return String(i).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;")}function V0(i){return jn(i).replace(/'/g,"&#39;")}const G0="nusama3d",H0=1,_n="models",W0="nusama3d-models";let hr=null;function xr(){return hr||(hr=new Promise((i,t)=>{const e=indexedDB.open(G0,H0);e.onupgradeneeded=()=>{const n=e.result;n.objectStoreNames.contains(_n)||n.createObjectStore(_n,{keyPath:"id"}).createIndex("position","position")},e.onsuccess=()=>i(e.result),e.onerror=()=>t(e.error??new Error("IndexedDB tidak dapat dibuka"))}),hr)}async function ur(i,t){const e=await xr();return new Promise((n,s)=>{const r=e.transaction(_n,i),a=r.objectStore(_n);let o;try{o=t(a)}catch(c){s(c);return}r.oncomplete=()=>n(o),r.onerror=()=>s(r.error??new Error("Transaksi IndexedDB gagal")),r.onabort=()=>s(r.error??new Error("Transaksi IndexedDB dibatalkan"))})}function Ca(i){return new Promise((t,e)=>{i.onsuccess=()=>t(i.result),i.onerror=()=>e(i.error)})}const ox={async all(){const i=await xr();return(await Ca(i.transaction(_n,"readonly").objectStore(_n).getAll())).sort((e,n)=>(e.position??0)-(n.position??0))},async find(i){const t=await xr();return Ca(t.transaction(_n,"readonly").objectStore(_n).get(i))},async count(){const i=await xr();return Ca(i.transaction(_n,"readonly").objectStore(_n).count())},async put(i){return await ur("readwrite",t=>t.put(i)),i},async remove(i){await ur("readwrite",t=>t.delete(i))},async clear(){await ur("readwrite",i=>i.clear())},async reorder(){const i=await this.all();return await ur("readwrite",t=>{i.forEach((e,n)=>{t.put({...e,position:n+1})})}),i.map((t,e)=>({...t,position:e+1}))}};function lx(i){if(typeof BroadcastChannel>"u")return{post:()=>{},close:()=>{}};const t=new BroadcastChannel(W0);return i&&t.addEventListener("message",e=>i(e.data)),{post:e=>t.postMessage(e),close:()=>t.close()}}function cx(){return typeof crypto<"u"&&typeof crypto.randomUUID=="function"?crypto.randomUUID():`m-${Date.now().toString(36)}-${Math.random().toString(36).slice(2,10)}`}const wh=640,Rh=480,Ch=15986412;let Dn=null;function X0(){return Dn||(Dn=new fh({antialias:!0,preserveDrawingBuffer:!0}),Dn.setPixelRatio(1),Dn.setSize(wh,Rh,!1),Dn.setClearColor(Ch,1),Dn.toneMapping=Pr,Dn.toneMappingExposure=1.05,Dn)}function q0(i){if(!i)return"";const t=i.parent,e=new Yc;e.background=new Ft(Ch),e.add(new ih(16777215,14274507,1.2));const n=new gs(16777215,2);n.position.set(1,1.5,1.2),e.add(n);const s=new gs(16770782,.65);s.position.set(-1.4,.6,-.9),e.add(s);try{e.add(i);const r=new Ke().setFromObject(i);if(r.isEmpty())return"";const a=r.getSize(new I),o=r.getCenter(new I),c=Math.max(a.length()/2,.001),l=new We(38,wh/Rh,.1,c*100),h=new I(1,.72,1).normalize(),d=c/Math.sin(l.fov*Math.PI/360)*1.12;l.position.copy(o).addScaledVector(h,d),l.lookAt(o),l.updateProjectionMatrix();const u=X0();return u.render(e,l),u.domElement.toDataURL("image/png")}catch(r){return console.error(r),""}finally{t?t.add(i):e.remove(i)}}function hx(i,{id:t,position:e,name:n,size:s}){var o,c,l,h,d,u,p,g,v;const r=i.payload(),a=i.settings.quantity;return delete r.file,{id:t,position:e,name:n,size:s,format:i.format,thumbnail:q0(i.pivot),state:i.snapshot(),payload:r,inputs:{geometryVolumeCm3:(((o=i.metrics)==null?void 0:o.volumeMm3)??0)/1e3,surfaceAreaCm2:(((c=i.metrics)==null?void 0:c.surfaceAreaMm2)??0)/100,dimensions:i.dimensions?{...i.dimensions}:null,measuredSupportVolumeCm3:((l=i.supportStats)==null?void 0:l.materialVolumeCm3)??null},summary:{dimensions:i.dimensions?{...i.dimensions}:null,volumeCm3:((h=i.estimate)==null?void 0:h.modelVolumeCm3)??0,weightG:(((d=i.estimate)==null?void 0:d.totalWeightG)??0)*a,minutes:((u=i.estimate)==null?void 0:u.totalMinutes)??0,cost:((p=i.estimate)==null?void 0:p.totalCost)??0,technology:i.settings.technology,material:i.settings.material,color:i.settings.color,finishing:i.settings.finishing,quantity:a,analysisStatus:((g=i.analysis)==null?void 0:g.status)??"warning",fits:!((v=i.fit)!=null&&v.exceeds)}}}export{a0 as A,ls as B,ax as P,Y0 as S,$0 as a,rx as b,lx as c,nx as d,Rr as e,cx as f,ex as g,u0 as h,ix as i,Z0 as j,K0 as k,Ac as l,ox as m,sx as n,f0 as o,p0 as p,j0 as q,qt as r,Q0 as s,hx as t,d0 as u,J0 as v,bh as w,tx as x,g0 as y,Ec as z};
