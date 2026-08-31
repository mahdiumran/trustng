// TRUST-NG DNS Inspector — render hasil dig per domain dengan status
var DI_LABEL = {
  resolved:    { text: 'Resolved',    icon: 'fa-circle-check' },
  blocked:     { text: 'Blocked',     icon: 'fa-shield-halved' },
  whitelisted: { text: 'Whitelisted', icon: 'fa-unlock' },
  noanswer:    { text: 'No Answer',   icon: 'fa-circle-xmark' },
  pending:     { text: 'Testing',     icon: 'fa-spinner fa-spin' }
};

function diEsc(s) {
  return String(s).replace(/[&<>"']/g, function (c) {
    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
  });
}

function diRow(r) {
  var recs = [];
  if (r.a && r.a.length) recs.push('A: ' + r.a.join(', '));
  if (r.aaaa && r.aaaa.length) recs.push('AAAA: ' + r.aaaa.join(', '));
  return '<div class="di-row">' +
    '<span class="di-dot ' + r.status + '"></span>' +
    '<span class="di-domain" title="' + diEsc(r.domain) + '">' + diEsc(r.domain) + '</span>' +
    '<span class="di-status ' + r.status + '"><i class="fa-solid ' + DI_LABEL[r.status].icon + '"></i> ' + DI_LABEL[r.status].text + '</span>' +
    '<span class="di-recs">' + diEsc(recs.join(' · ') || '—') + '</span>' +
    '</div>';
}

function diRender(data) {
  var box = document.getElementById('di-results');
  box.classList.remove('di-loading');
  if (!data || !data.ok || !data.results || !data.results.length) {
    box.innerHTML = '<div class="di-empty">Belum ada domain test. Tambahkan lewat tombol <b>Set Domain</b>.</div>';
    return;
  }
  var html = '';
  for (var i = 0; i < data.results.length; i++) html += diRow(data.results[i]);
  box.innerHTML = html;
}

function diRenderManual(data) {
  var box = document.getElementById('di-manual-results');
  box.classList.remove('di-loading');
  if (!data || !data.ok || !data.results || !data.results.length) {
    box.innerHTML = '<div class="di-empty">' + (data && data.error ? diEsc(data.error) : 'Belum ada hasil. Masukkan minimal satu domain.') + '</div>';
    return;
  }
  var html = '';
  for (var i = 0; i < data.results.length; i++) html += diRow(data.results[i]);
  box.innerHTML = html;
}

function diRunManual() {
  var box = document.getElementById('di-manual-results');
  var input = document.getElementById('diDomain');
  var val = input ? input.value : '';
  box.classList.add('di-loading');
  box.innerHTML = '<div class="di-empty">Mengetes resolusi…</div>';
  $.ajax({
    url: 'digtest_data.php?manual=1',
    method: 'POST',
    data: { domains: val },
    dataType: 'json'
  }).done(diRenderManual).fail(function () {
    box.classList.remove('di-loading');
    box.innerHTML = '<div class="di-empty">Gagal mengambil data.</div>';
  });
}

function diRun() {
  var box = document.getElementById('di-results');
  box.classList.add('di-loading');
  box.innerHTML = '<div class="di-empty">Mengetes resolusi…</div>';
  $.getJSON('digtest_data.php', diRender).fail(function () {
    box.classList.remove('di-loading');
    box.innerHTML = '<div class="di-empty">Gagal mengambil data.</div>';
  });
}

$(function () { diRunManual(); diRun(); });
