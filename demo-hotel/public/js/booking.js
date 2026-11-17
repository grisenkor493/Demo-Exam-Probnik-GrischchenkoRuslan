// Календарь занятости. Проверка данных выполняется на сервере.
const byId=id=>document.getElementById(id);
const cal=byId('cal'),note=byId('calnote'),i1=byId('validationCustom04'),i2=byId('validationCustom05');
const busy=JSON.parse(cal.dataset.busy),today=cal.dataset.today;
const toDmy=s=>s?s.split('-').reverse().join('.'):'';
const toIso=s=>{const m=/^(\d{2})\.(\d{2})\.(\d{4})$/.exec(s||'');if(!m)return null;const d=new Date(+m[3],+m[2]-1,+m[1]);
  return(d.getFullYear()==+m[3]&&d.getMonth()==+m[2]-1&&d.getDate()==+m[1])?`${m[3]}-${m[2]}-${m[1]}`:null};
const iso=(y,m,d)=>`${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
const isBusy=s=>busy.some(([a,b])=>a<=s&&s<b);
const clash=(a,b)=>busy.some(([x,y])=>x<b&&y>a);   // есть ли занятые ночи в периоде [a,b)
let start=null,end=null;const cells=[];
const t=new Date(today+'T00:00:00');
for(let k=0;k<3;k++){
  const y=new Date(t.getFullYear(),t.getMonth()+k,1),Y=y.getFullYear(),M=y.getMonth();
  const box=document.createElement('div');box.className='cal';
  const h=document.createElement('h4');h.textContent=y.toLocaleDateString('ru-RU',{month:'long',year:'numeric'});box.append(h);
  const g=document.createElement('div');g.className='g';
  ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'].forEach(n=>{const s=document.createElement('span');s.className='wd';s.textContent=n;g.append(s)});
  for(let i=0;i<(y.getDay()+6)%7;i++)g.append(document.createElement('span'));
  for(let d=1;d<=new Date(Y,M+1,0).getDate();d++){
    const b=document.createElement('button'),k2=iso(Y,M,d);b.type='button';b.textContent=d;b.dataset.d=k2;
    if(k2<today){b.className='past';b.disabled=true}
    else{if(isBusy(k2)){b.className='busy';b.title='Занято'}b.addEventListener('click',()=>pick(k2))}
    g.append(b);cells.push(b)}
  box.append(g);cal.append(box)}
const say=m=>{note.textContent=m};
function paint(){cells.forEach(b=>{const d=b.dataset.d;b.classList.toggle('sel',d===start||d===end);b.classList.toggle('mid',!!(start&&end&&d>start&&d<end))})}
function fill(){i1.value=toDmy(start);i2.value=toDmy(end);paint()}
function pick(d){
  if(!start||end){ if(isBusy(d)){say('Эта ночь занята — выберите другую дату заезда.');return} start=d;end=null;say('Теперь выберите дату выезда.')}
  else if(d<=start){ if(isBusy(d)){say('Эта ночь занята.');return} start=d;say('Теперь выберите дату выезда.')}
  else if(clash(start,d)){say('В выбранном периоде есть занятые ночи. Выберите другие даты.');start=null}
  else{end=d;say('Даты выбраны. Заполните контакты и отправьте заявку.')}
  fill()}
function sync(){                         // ручной ввод в полях type=date
  start=toIso(i1.value);const e=toIso(i2.value);end=(start&&e&&e>start)?e:null;paint();
  if(start&&isBusy(start))say('Ночь заезда занята.');else if(end&&clash(start,end))say('В выбранном периоде есть занятые ночи.');else say('Нажмите на дату заезда, затем на дату выезда или введите даты вручную. Красным отмечены занятые ночи.')}
i1.addEventListener('change',sync);i2.addEventListener('change',sync);sync();
