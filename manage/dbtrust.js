// TRUST-NG DB Trust+ — render hasil pencarian (keyword / domain) via AJAX
var DB_MODE = 'keyword';

function dbSetMode(m) {
  DB_MODE = m;
  $('.db-mode').removeClass('active');
  $('.db-mode[data-mode="' + m + '"]').addClass('active');
  $('#db-mode-label').text('Mode: ' + (m === 'domain' ? 'Domain' : 'Keyword'));
  $('#db-hint').text(m === 'domain'
    ? 'Cek apakah domain (atau induknya) terdaftar di blocklist CDB.'
    : 'Cari kata kunci di dalam daftar Trust+ (maks 500 hasil).');
  $('#db-results').html('<div class="di-empty">Masukkan kata kunci atau domain lalu tekan Cari.</div>');
}

function dbEsc(s) {
  return String(s).replace(/[&<>"']/g, function (c) {
    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
  });
}

function dbRenderKeyword(data) {
  var box = $('#db-results');
  if (!data.ok) { box.html('<div class="di-empty">' + dbEsc(data.error || 'Gagal') + '</div>'); return; }
  if (!data.results.length) { box.html('<div class="di-empty">Tidak ada hasil untuk "' + dbEsc(data.query) + '".</div>'); return; }
  var html = '<div class="di-server" style="margin-bottom:8px">' + data.total + ' hasil (maks 500)</div>';
  for (var i = 0; i < data.results.length; i++) {
    html += '<div class="di-row"><span class="di-dot"></span>' +
      '<span class="di-domain" title="' + dbEsc(data.results[i]) + '">' + dbEsc(data.results[i]) + '</span>' +
      '<span class="di-status match">MATCH</span></div>';
  }
  box.html(html);
}

function dbRenderDomain(data) {
  var box = $('#db-results');
  if (!data.ok) { box.html('<div class="di-empty">' + dbEsc(data.error || 'Gagal') + '</div>'); return; }
  var st = data.found ? 'found' : 'notfound';
  var stTxt = data.found ? 'DITEMUKAN' : 'TIDAK DITEMUKAN';
  var recs = [];
  if (data.a && data.a.length) recs.push('A: ' + data.a.join(', '));
  if (data.aaaa && data.aaaa.length) recs.push('AAAA: ' + data.aaaa.join(', '));
  var html = '<div class="di-row"><span class="di-dot ' + st + '"></span>' +
    '<span class="di-domain" style="width:auto">' + dbEsc(data.query) + '</span>' +
    '<span class="di-status ' + st + '">' + stTxt + '</span>' +
    '<span class="di-recs">';
  if (data.found) html += 'cocok: ' + dbEsc(data.matched);
  else html += 'tidak ada di blocklist';
  if (recs.length) html += ' &middot; ' + dbEsc(recs.join(' &middot; '));
  html += '</span></div>';
  box.html(html);
}

function dbRun() {
  var q = $('#dbQ').val();
  var box = $('#db-results');
  box.html('<div class="di-empty">Mencari…</div>');
  $.ajax({
    url: 'dbtrust_data.php',
    method: 'POST',
    data: { mode: DB_MODE, q: q },
    dataType: 'json'
  }).done(function (data) {
    if (DB_MODE === 'domain') dbRenderDomain(data); else dbRenderKeyword(data);
  }).fail(function () {
    box.html('<div class="di-empty">Gagal mengambil data.</div>');
  });
}

$(function () { /* mode default keyword; jalankan saat user menekan Cari */ });
