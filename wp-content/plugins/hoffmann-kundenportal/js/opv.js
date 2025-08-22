(function(){
  const EUR = new Intl.NumberFormat('de-DE',{style:'currency',currency:'EUR'});
  const fmtDate = d => new Date(d).toLocaleDateString('de-DE');
  const addDays = (d,days)=>{const t=new Date(d); t.setDate(t.getDate()+days); return t;};
  let DATA = [];
  const state = { q:'', from:'', to:'', customer:'', ptype:'', status:'' };
  function money(n){return EUR.format(n||0)}
  function within(d, from, to){ const t = +new Date(d); return (!from || t >= +new Date(from)) && (!to || t <= +new Date(to)+86400000-1); }
  function daysUntil(date){ const diff = (+new Date(date) - Date.now())/(1000*60*60*24); return Math.floor(diff); }
  function statusOf(row){
    const open = Math.max((row.gross||0)-(row.paid||0),0);
    if(open<=0 || !row.due) return '';
    const days = daysUntil(row.due);
    if(isNaN(days)) return '';
    if(days < 0) return 'overdue';
    if(days <= 7) return 'due7';
    return '';
  }
  function buildFilters(){
    const cSel = document.getElementById('opv-customer');
    const pSel = document.getElementById('opv-ptype');
    const customers = Array.from(new Set(DATA.map(r=>r.customer))).sort();
    const types = Array.from(new Set(DATA.map(r=>r.type))).sort();
    customers.forEach(c=>{ const o=document.createElement('option'); o.value=c; o.textContent=c; cSel.appendChild(o); });
    types.forEach(t=>{ const o=document.createElement('option'); o.value=t; o.textContent=t; pSel.appendChild(o); });
  }
  function getFiltered(){
    let rows = DATA.map(r=> ({...r, open: Math.max((r.gross||0)-(r.paid||0),0), stat: statusOf(r)}));
    rows = rows.filter(r=>{
      const txt = (r.customer+" "+r.inv+" "+r.type).toLowerCase();
      const matches = !state.q || txt.includes(state.q.toLowerCase());
      const inRange = within(r.date, state.from, state.to);
      const cMatch = !state.customer || r.customer===state.customer;
      const tMatch = !state.ptype || r.type===state.ptype;
      const sMatch = !state.status || r.stat===state.status;
      return matches && inRange && cMatch && tMatch && sMatch;
    });
    rows.sort((a,b)=> +new Date(b.date) - +new Date(a.date));
    return rows;
  }
  function render(){
    const rows = getFiltered();
    const tb = document.querySelector('#opv-tbl tbody');
    tb.innerHTML = '';
    let grossSum=0, paidSum=0, openSum=0, overdueSum=0;
    const byCustomer = new Map();
    rows.forEach(r=>{
      grossSum += r.gross; paidSum += r.paid; openSum += r.open;
      if(r.stat==='overdue') overdueSum += r.open;
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${r.customer}</td>
        <td>${r.inv}</td>
        <td>${fmtDate(r.date)}</td>
        <td>${r.type}</td>
        <td class="right">${money(r.gross)}</td>
        <td class="right">${money(r.paid)}</td>
        <td class="right">${money(r.open)}</td>
        <td>${r.stat==='overdue'?'<span class="pill danger">Überfällig</span>':(r.stat==='due7'?'<span class="pill warn">≤ 7 Tage</span>':'')}</td>`;
      tb.appendChild(tr);
      const t = byCustomer.get(r.customer) || {gross:0, paid:0, open:0};
      t.gross += r.gross; t.paid += r.paid; t.open += r.open; byCustomer.set(r.customer, t);
    });
    document.getElementById('opv-rowsum').textContent = `${rows.length} Rechnungen angezeigt`;
    document.getElementById('opv-kpi-gross').textContent = money(grossSum);
    document.getElementById('opv-kpi-paid').textContent = money(paidSum);
    document.getElementById('opv-kpi-open').textContent = money(openSum);
    document.getElementById('opv-kpi-overdue').textContent = money(overdueSum);
    const tp = document.getElementById('opv-totalsByCustomer');
    tp.innerHTML = '';
    Array.from(byCustomer.entries()).sort((a,b)=> b[1].open - a[1].open).forEach(([name,t])=>{
      const row = document.createElement('div'); row.className='total-row';
      row.innerHTML = `<span class="name">${name}</span><span class="muted">Gesamt: <strong>${money(t.gross)}</strong> · Gezahlt: <strong>${money(t.paid)}</strong> · Offen: <strong>${money(t.open)}</strong></span>`;
      tp.appendChild(row);
    });
  }
  function exportCSV(){
    const rows = getFiltered();
    const header = ['Kunde','Rechnungsnr','Datum','Produkttyp','Betrag Gesamt (EUR)','Bisher gezahlt (EUR)','Noch zu zahlen (EUR)','Status'];
    const out = [header.join(';')].concat(rows.map(r=>[
      r.customer, r.inv, r.date, r.type, r.gross.toFixed(2).replace('.',','), r.paid.toFixed(2).replace('.',','), r.open.toFixed(2).replace('.',','), r.stat
    ].join(';'))).join('\n');
    const blob = new Blob([out],{type:'text/csv;charset=utf-8;'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href = url; a.download = 'offene_posten.csv'; a.click(); URL.revokeObjectURL(url);
  }
  function exportPDF(){
    const { jsPDF } = window.jspdf; const doc = new jsPDF({unit:'pt',format:'a4'});
    doc.setFontSize(14); doc.text('Offene Posten – Übersicht', 40, 40);
    doc.setFontSize(10);
    let y=64; doc.text('Kunde',40,y); doc.text('Rechnungsnr',170,y); doc.text('Datum',270,y); doc.text('Typ',330,y); doc.text('Gesamt',400,y); doc.text('Gezahlt',470,y); doc.text('Offen',530,y);
    y+=12;
    getFiltered().forEach(r=>{ if(y>780){ doc.addPage(); y=40; }
      doc.text(r.customer,40,y); doc.text(r.inv,170,y); doc.text(fmtDate(r.date),270,y); doc.text(r.type,330,y);
      doc.text((r.gross).toFixed(2)+' €',400,y,{align:'right'});
      doc.text((r.paid).toFixed(2)+' €',470,y,{align:'right'});
      doc.text((r.open).toFixed(2)+' €',530,y,{align:'right'}); y+=12; });
    doc.save('offene_posten.pdf');
  }
  function bindEvents(){
    document.getElementById('opv-q').addEventListener('input', e=>{state.q=e.target.value; render();});
    document.getElementById('opv-from').addEventListener('change', e=>{state.from=e.target.value; render();});
    document.getElementById('opv-to').addEventListener('change', e=>{state.to=e.target.value; render();});
    document.getElementById('opv-customer').addEventListener('change', e=>{state.customer=e.target.value; render();});
    document.getElementById('opv-ptype').addEventListener('change', e=>{state.ptype=e.target.value; render();});
    document.getElementById('opv-status').addEventListener('change', e=>{state.status=e.target.value; render();});
    document.getElementById('opv-reset').addEventListener('click', ()=>{
      state.q='';state.from='';state.to='';state.customer='';state.ptype='';state.status='';
      document.getElementById('opv-q').value=''; document.getElementById('opv-from').value=''; document.getElementById('opv-to').value='';
      document.getElementById('opv-customer').value=''; document.getElementById('opv-ptype').value=''; document.getElementById('opv-status').value='';
      render();
    });
    document.getElementById('opv-exportCsv').addEventListener('click', exportCSV);
    document.getElementById('opv-exportPdf').addEventListener('click', exportPDF);
  }
  function fetchData(){
    const url = 'https://dashboard.hoffmann-hd.de/wp-content/uploads/json/offene_posten.json';
    fetch(url)
      .then(res => res.json())
      .then(json => {
        DATA = [];
        Object.entries(json).forEach(([customer,rows])=>{
          rows.forEach(r=>{
            DATA.push({
              customer,
              inv: r['Rechnungsnr'],
              date: r['Datum'],
              type: r['Produkttyp'],
              gross: parseFloat(r['Betrag Gesamt'])||0,
              paid: parseFloat(r['Bisher gezahlt'])||0,
              due: r['Faelligkeit'] || r['Fälligkeit'] || ''
            });
          });
        });
        buildFilters();
        bindEvents();
        render();
      })
      .catch(err=>console.error('OPV load error', err));
  }
  document.addEventListener('DOMContentLoaded', fetchData);
})();
