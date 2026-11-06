/**
 * PTS 24-hour visual story. Ported from the V0.2 timeline idea, adapted for V0.5.
 * Scene energy is illustrative; never interpreted as measured PV production.
 */
export function createEnergyTimeline({onHourChange,getCaption,onPlay}){
 let hour=12,playing=false,progress=0;
 let input=null,button=null;
 const day=h=>h>=6.25 && h<18.5;
 const format=h=>{
  const mins=Math.round(h*60);
  return String(Math.floor(mins/60)%24).padStart(2,'0')+':'+String(mins%60).padStart(2,'0');
 };
 function brightness(){
  if(!day(hour))return 0;
  return Math.max(.10,Math.min(1,(hour-6.25)/2.2,(18.5-hour)/2.2));
 }
 function paint(){
  if(!input)return;
  const daytime=day(hour);
  document.querySelector('#cycleStrip').classList.toggle('is-nighttime',!daytime);
  document.querySelector('#timelineReadout').textContent=format(hour);
  document.querySelector('#timelineStatus').textContent=getCaption(hour,daytime);
  input.value=String(hour);
  input.setAttribute('aria-valuetext',format(hour));
  input.style.setProperty('--cycle-percent',(hour/23.75*100).toFixed(1)+'%');
  document.querySelector('#timelinePlayIcon').textContent=playing?'Ⅱ':'▶';
  document.querySelector('#timelinePlayText').textContent=playing?'Tạm dừng':'Chạy chu kỳ';
  button.setAttribute('aria-pressed',String(playing));
  button.setAttribute('aria-label',playing?'Tạm dừng chu kỳ 24 giờ':'Tua chu kỳ 24 giờ');
 }
 function setHour(value,notify=false){
  const next=Math.round(Math.max(0,Math.min(23.75,Number(value)))*4)/4;
  if(!Number.isFinite(next))return;
  hour=next;
  if(notify)onHourChange(hour,day(hour));
  paint();
 }
 function stop(){playing=false;paint();}
 function init(){
  input=document.querySelector('#timelineSlider');
  button=document.querySelector('#timelinePlay');
  if(!input||!button)throw Error('PTS timeline UI not found');
  input.addEventListener('input',e=>{playing=false;progress=0;setHour(e.target.value,true);});
  button.addEventListener('click',()=>{
   if(!playing)onPlay();
   playing=!playing;
   progress=0;paint();
  });
  paint();
 }
 function tick(dt){
  if(!playing)return;
  progress+=dt*.6;
  if(progress<.25)return;
  const steps=Math.floor(progress/.25);
  progress-=steps*.25;
  let next=hour+steps*.25;
  if(next>=24)next%=24;
  setHour(next,true);
 }
 return {init,tick,setHour,stop,paint,brightness,isDay:day,get hour(){return hour;},get playing(){return playing;}};
}
