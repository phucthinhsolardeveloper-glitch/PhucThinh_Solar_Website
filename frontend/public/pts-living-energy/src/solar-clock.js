/**
 * Local solar-position preview (NOAA fractional-year approximation).
 * The timestamps are real. Model north orientation is not site-calibrated.
 * Do NOT interpret sun position as weather, measured irradiance or PV output.
 */
export const SOLAR_SITE=Object.freeze({name:'Pleiku · Gia Lai',lat:13.98,lon:108.0,timezone:'Asia/Ho_Chi_Minh',offsetHours:7});
const rad=Math.PI/180;
export function siteParts(date=new Date(),site=SOLAR_SITE){
 const formatter=new Intl.DateTimeFormat('en-GB',{timeZone:site.timezone,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',second:'2-digit',hourCycle:'h23'});
 const value=Object.fromEntries(formatter.formatToParts(date).filter(x=>x.type!=='literal').map(x=>[x.type,Number(x.value)]));
 return {...value,hourFraction:value.hour+value.minute/60+value.second/3600};
}
export function solarAt(date=new Date(),site=SOLAR_SITE){
 const p=siteParts(date,site);
 const first=Date.UTC(p.year,0,1),today=Date.UTC(p.year,p.month-1,p.day);
 const dayOfYear=Math.round((today-first)/86400000)+1;
 const days=new Date(Date.UTC(p.year,11,31)).getUTCDate()===31 && ((p.year%4===0&&p.year%100!==0)||p.year%400===0)?366:365;
 const gamma=2*Math.PI/days*(dayOfYear-1+(p.hourFraction-12)/24);
 const equation=229.18*(.000075+.001868*Math.cos(gamma)-.032077*Math.sin(gamma)-.014615*Math.cos(2*gamma)-.040849*Math.sin(2*gamma));
 const decl=.006918-.399912*Math.cos(gamma)+.070257*Math.sin(gamma)-.006758*Math.cos(2*gamma)+.000907*Math.sin(2*gamma)-.002697*Math.cos(3*gamma)+.00148*Math.sin(3*gamma);
 const offset=equation+4*site.lon-60*site.offsetHours;
 const minutes=((p.hourFraction*60+offset)%1440+1440)%1440;
 const ha=(minutes/4-180)*rad,lat=site.lat*rad;
 const zenith=Math.acos(Math.max(-1,Math.min(1,Math.sin(lat)*Math.sin(decl)+Math.cos(lat)*Math.cos(decl)*Math.cos(ha))));
 const altitude=90-zenith/rad;
 const azimuth=((Math.atan2(Math.sin(ha),Math.cos(ha)*Math.sin(lat)-Math.tan(decl)*Math.cos(lat))/rad+180)%360+360)%360;
 const daylight=altitude>-.833;
 const golden=daylight&&altitude<15;
 const brightness=daylight?Math.max(.055,Math.min(1,Math.sin(Math.max(0,altitude)*rad)/.62)):0;
 return {altitude,azimuth,daylight,golden,brightness,parts:p,site:site.name};
}
export function simulatedDate(hour,now=new Date(),site=SOLAR_SITE){
 const p=siteParts(now,site);
 const fixed=Math.min(23.75,Math.max(0,Number(hour)||0));
 // Calendar date at site's clock, converted to UTC without relying on OS timezone.
 return new Date(Date.UTC(p.year,p.month-1,p.day,0,0,0)-site.offsetHours*3600000+Math.round(fixed*3600000));
}
export const formatSolarTime=parts=>String(parts.hour).padStart(2,'0')+':'+String(parts.minute).padStart(2,'0');
