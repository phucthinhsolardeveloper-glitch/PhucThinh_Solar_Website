/**
 * Conceptual one-line energy-path explainer; not a construction wiring diagram.
 * It deliberately avoids a universal grid -> inverter -> all loads topology.
 */
const scenarios = {
  day: {
    title:'BAN NGÀY · PV CẤP TẢI',
    detail:'DC từ mái PV được inverter chuyển sang AC cấp phụ tải. Lưới kết nối nhưng không mô phỏng nhập điện.',
    active:'pv', grid:'KẾT NỐI · KHÔNG NHẬP ĐIỆN', storage:'DỰ PHÒNG / CÓ THỂ SẠC THEO CẤU HÌNH',
    pv:'PV cấp điện cho phụ tải qua inverter.',
  },
  night_storage:{
    title:'BAN ĐÊM · PIN CẤP TẢI',
    detail:'Nguồn DC từ pin đi qua inverter để cấp tải AC; lưới còn kết nối nhưng không mô phỏng nhập điện.',
    active:'battery',grid:'KẾT NỐI · KHÔNG NHẬP ĐIỆN',storage:'ĐANG CẤP TẢI',pv:'KHÔNG CÓ NẮNG',
  },
  night_grid:{
    title:'BAN ĐÊM · ĐIỆN LƯỚI CẤP TẢI',
    detail:'AC từ cột điện đi qua dây cấp, công tơ và khâu đóng cắt vào phân phối điện. Kết nối inverter tùy thiết kế.',
    active:'grid',grid:'ĐANG NHẬP ĐIỆN LƯỚI',storage:'KHÔNG SỬ DỤNG',pv:'KHÔNG CÓ NẮNG',
  },
  backup:{
    title:'MẤT LƯỚI · CHỈ TẢI ƯU TIÊN',
    detail:'Nguồn lưới cách ly. Pin qua inverter và ngõ backup tương thích cấp riêng nhánh tải ưu tiên; tải thường không được mô phỏng cấp điện.',
    active:'battery',grid:'ĐÃ CÁCH LY · KHÔNG CẤP ĐIỆN',storage:'ĐANG CẤP TẢI ƯU TIÊN',pv:'KHÔNG MẶC ĐỊNH PHÁT KHI MẤT LƯỚI',
  }
};
export function createPowerMap(root,onFocus){
 if(!root)return {render:()=>{}};
 root.addEventListener('click',event=>{
  const button=event.target.closest('[data-power-focus]');
  if(!button || !root.contains(button) || button.disabled)return;
  onFocus(button.dataset.powerFocus);
 });
 const storageDestination=root.querySelector('#storageDestination');
 const storageTransfer=root.querySelector('#storageTransfer');
 const storageLink=root.querySelector('#storageLink');
 const status=root.querySelector('#powerMapStatus');
 const copy=root.querySelector('#powerMapCopy');
 const labels={pv:root.querySelector('#pvLaneState'),grid:root.querySelector('#gridLaneState'),battery:root.querySelector('#batteryLaneState')};
 function render(id,batteryPresent,backupDaylight){
  const entry=scenarios[id]||scenarios.day;
  root.dataset.scenario=id;
  root.dataset.activeLane=entry.active;
  root.dataset.batteryPresent=String(batteryPresent);
  status.textContent=entry.title;
  copy.textContent=entry.detail;
  labels.pv.textContent=id==='backup'&&backupDaylight?'CÓ NẮNG · KHÔNG MẶC ĐỊNH PHÁT':entry.pv;
  labels.grid.textContent=entry.grid;
  labels.battery.textContent=batteryPresent?entry.storage:'KHÔNG CÓ PIN TRONG KỊCH BẢN';
  const backup=id==='backup';
  storageTransfer.textContent=backup?'Ngõ AC backup':'Ngõ ra AC';
  storageTransfer.dataset.powerFocus=backup?'prioritypanel':'main';
  storageDestination.textContent=backup?'Tải ưu tiên':'Tải trong nhà';
  storageDestination.dataset.powerFocus=backup?'priority':'house';
  storageLink.textContent=backup?'AC BACKUP':'AC';
  const batteryLane=root.querySelector('[data-power-lane="battery"]');
  batteryLane.dataset.unavailable=String(!batteryPresent);
  batteryLane.querySelectorAll('button').forEach(button=>{button.disabled=!batteryPresent;});
  root.querySelectorAll('[data-power-lane]').forEach(lane=>{
   const current=lane.dataset.powerLane===entry.active;
   lane.classList.toggle('is-flowing',current);
   lane.setAttribute('aria-current',current?'true':'false');
  });
 }
 return {render};
}
