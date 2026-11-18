/* Grafik dashboard ADUKANAJA — JavaScript murni + SVG, tanpa library dan tanpa internet. */
(function () {
  var dataEl = document.getElementById('chart-data');
  if (!dataEl) return;
  var data = JSON.parse(dataEl.textContent);
  var NS = 'http://www.w3.org/2000/svg';
  var COLOR = { approved: '#16A34A', pending: '#F59E0B', rejected: '#DC2626' };
  var NAMA = { approved: 'Approved', pending: 'Pending', rejected: 'Rejected' };
  var ORDER = ['approved', 'pending', 'rejected']; // urutan tumpukan dari bawah ke atas

  /* ---------- helper ---------- */
  function svg(name, attrs, parent) {
    var n = document.createElementNS(NS, name);
    for (var k in attrs) n.setAttribute(k, attrs[k]);
    if (parent) parent.appendChild(n);
    return n;
  }
  function text(parent, x, y, str, attrs) {
    var t = svg('text', Object.assign({ x: x, y: y, fill: '#6B7BA0', 'font-size': 12 }, attrs || {}), parent);
    t.textContent = str;
    return t;
  }
  var tip = document.createElement('div');
  tip.className = 'chart-tip';
  tip.hidden = true;
  tip.setAttribute('role', 'status');
  document.body.appendChild(tip);

  function showTip(html, clientX, clientY) {
    tip.innerHTML = html;
    tip.hidden = false;
    var w = tip.offsetWidth, h = tip.offsetHeight;
    var x = Math.min(Math.max(8, clientX - w / 2), window.innerWidth - w - 8);
    var y = clientY - h - 14;
    if (y < 8) y = clientY + 18;
    tip.style.left = x + 'px';
    tip.style.top = y + 'px';
  }
  function hideTip() { tip.hidden = true; }
  function niceStep(max) {
    var steps = [1, 2, 5, 10, 20, 25, 50, 100, 200, 500, 1000];
    for (var i = 0; i < steps.length; i++) if (max / steps[i] <= 5) return steps[i];
    return 1000;
  }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

  /* ---------- 1. Batang bertumpuk per bulan ---------- */
  var barBox = document.getElementById('chart-bulan');
  var range = 6;

  function drawBars() {
    barBox.innerHTML = '';
    var rows = data.bulan.slice(-range);
    var W = Math.max(280, barBox.clientWidth), H = 250;
    var m = { l: 30, r: 8, t: 24, b: 28 };
    var totals = rows.map(function (r) { return r.pending + r.approved + r.rejected; });
    var max = Math.max(4, Math.max.apply(null, totals));
    var step = niceStep(max);
    var top = Math.ceil(max / step) * step;
    var iw = W - m.l - m.r, ih = H - m.t - m.b;
    var y = function (v) { return m.t + ih - (v / top) * ih; };

    var s = svg('svg', { viewBox: '0 0 ' + W + ' ' + H, width: W, height: H, role: 'img',
      'aria-label': 'Grafik batang jumlah pengaduan per bulan, ' + range + ' bulan terakhir' }, barBox);

    for (var v = 0; v <= top; v += step) {
      svg('line', { x1: m.l, x2: W - m.r, y1: y(v), y2: y(v), stroke: v === 0 ? '#C7D3EA' : '#E3E9F5', 'stroke-width': 1 }, s);
      text(s, m.l - 8, y(v) + 4, v, { 'text-anchor': 'end' });
    }

    var slot = iw / rows.length, bw = Math.min(46, slot * 0.58);
    rows.forEach(function (r, i) {
      var cx = m.l + slot * i + slot / 2, base = 0;
      var g = svg('g', { tabindex: 0, 'class': 'bar-g',
        'aria-label': r.penuh + ': total ' + totals[i] + ' (Approved ' + r.approved + ', Pending ' + r.pending + ', Rejected ' + r.rejected + ')' }, s);

      svg('rect', { x: cx - slot / 2, y: m.t, width: slot, height: ih, fill: 'transparent' }, g);
      ORDER.forEach(function (st) {
        var n = r[st];
        if (!n) return;
        var y1 = y(base + n), y0 = y(base);
        svg('rect', { x: cx - bw / 2, y: y1, width: bw, height: Math.max(1, y0 - y1 - 1), fill: COLOR[st], rx: 2, 'class': 'bar-seg' }, g);
        base += n;
      });
      if (totals[i] > 0) text(g, cx, y(totals[i]) - 6, totals[i], { 'text-anchor': 'middle', fill: '#0B1F4A', 'font-weight': 700 });
      text(g, cx, H - 8, r.label, { 'text-anchor': 'middle' });

      var html = '<strong>' + esc(r.penuh) + '</strong><br>Total: ' + totals[i] +
        ORDER.map(function (st) {
          return '<br><span class="dot" style="background:' + COLOR[st] + '"></span>' + NAMA[st] + ': ' + r[st];
        }).join('');
      g.addEventListener('mousemove', function (e) { showTip(html, e.clientX, e.clientY); });
      g.addEventListener('mouseleave', hideTip);
      g.addEventListener('focus', function () { var b = g.getBoundingClientRect(); showTip(html, b.left + b.width / 2, b.top + 20); });
      g.addEventListener('blur', hideTip);
    });
  }

  var seg = document.querySelectorAll('[data-range]');
  seg.forEach(function (b) {
    b.addEventListener('click', function () {
      range = parseInt(b.getAttribute('data-range'), 10);
      seg.forEach(function (x) { x.classList.toggle('on', x === b); x.setAttribute('aria-pressed', x === b); });
      drawBars();
    });
  });

  /* ---------- 2. Donut status ---------- */
  var donutBox = document.getElementById('chart-status');
  function drawDonut() {
    donutBox.innerHTML = '';
    var total = ORDER.reduce(function (a, k) { return a + data.status[k]; }, 0);
    var size = 168, R = 62, SW = 22, C = 2 * Math.PI * R;
    var wrap = document.createElement('div');
    wrap.className = 'donut-wrap';
    donutBox.appendChild(wrap);

    var s = svg('svg', { viewBox: '0 0 ' + size + ' ' + size, width: size, height: size, role: 'img',
      'aria-label': 'Grafik donat status pengaduan. Total ' + total }, wrap);
    svg('circle', { cx: size / 2, cy: size / 2, r: R, fill: 'none', stroke: '#E3E9F5', 'stroke-width': SW }, s);

    var offset = 0;
    if (total > 0) ORDER.forEach(function (st) {
      var n = data.status[st];
      if (!n) return;
      var len = (n / total) * C;
      var c = svg('circle', {
        cx: size / 2, cy: size / 2, r: R, fill: 'none', stroke: COLOR[st], 'stroke-width': SW,
        'stroke-dasharray': Math.max(0, len - 2) + ' ' + C, 'stroke-dashoffset': -offset,
        transform: 'rotate(-90 ' + size / 2 + ' ' + size / 2 + ')', 'class': 'donut-seg'
      }, s);
      var pct = Math.round(n / total * 100);
      var html = '<strong>' + NAMA[st] + '</strong><br>' + n + ' pengaduan (' + pct + '%)';
      c.addEventListener('mousemove', function (e) { showTip(html, e.clientX, e.clientY); });
      c.addEventListener('mouseleave', hideTip);
      offset += len;
    });
    text(s, size / 2, size / 2 + 6, total, { 'text-anchor': 'middle', 'font-size': 26, 'font-weight': 800, fill: '#0B1F4A' });
    text(s, size / 2, size / 2 + 24, 'total', { 'text-anchor': 'middle', 'font-size': 11 });

    var legend = document.createElement('ul');
    legend.className = 'legend';
    ORDER.forEach(function (st) {
      var n = data.status[st], pct = total ? Math.round(n / total * 100) : 0;
      var li = document.createElement('li');
      li.innerHTML = '<span class="dot" style="background:' + COLOR[st] + '"></span><span>' + NAMA[st] +
        '</span><strong>' + n + '</strong><small>' + pct + '%</small>';
      legend.appendChild(li);
    });
    wrap.appendChild(legend);
  }

  /* ---------- 3. Bar horizontal per kategori ---------- */
  var catBox = document.getElementById('chart-kategori');
  function drawCats() {
    catBox.innerHTML = '';
    if (!data.kategori.length) { catBox.innerHTML = '<p class="muted">Belum ada data kategori.</p>'; return; }
    var max = Math.max.apply(null, data.kategori.map(function (k) { return k.jumlah; }));
    data.kategori.forEach(function (k) {
      var row = document.createElement('div');
      row.className = 'hbar';
      row.innerHTML = '<span class="hbar-name">' + esc(k.nama) + '</span>' +
        '<span class="hbar-track"><span class="hbar-fill" style="width:' + Math.max(4, k.jumlah / max * 100) + '%"></span></span>' +
        '<strong class="hbar-val">' + k.jumlah + '</strong>';
      catBox.appendChild(row);
    });
  }

  /* ---------- gambar & responsif ---------- */
  function drawAll() { drawBars(); drawDonut(); drawCats(); }
  drawAll();
  var t;
  window.addEventListener('resize', function () { clearTimeout(t); t = setTimeout(drawBars, 120); });
})();
