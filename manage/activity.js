// TRUST-NG Activity Log — render status + riwayat update blocklist
function actEsc(s) {
  return String(s).replace(/[&<>"']/g, function (c) {
    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
  });
}
function actBadge(status) {
  var cls = status === 'OK' ? 'ok' : (status === 'FAIL' ? 'fail' : 'pending');
  return '<span class="di-status ' + cls + '">' + actEsc(status) + '</span>';
}
function actRenderStatus(d) {
  if (!d || !d.status) return;
  $('#st-count').text(d.status.count);
  $('#st-update').text(d.status.last_update || '—');
  $('#st-health').text(d.status.last_health || '—');
  $('#st-next').text(d.status.next_run || '—');
}
function actRenderHistory(d) {
  var box = $('#act-rows');
  if (!d || !d.history || !d.history.length) {
    box.html('<div class="di-empty">Belum ada riwayat update. Jalankan update-blocklist untuk mencatat.</div>');
    return;
  }
  var html = '';
  for (var i = 0; i < d.history.length; i++) {
    var h = d.history[i];
    html += '<div class="di-row"><span class="di-dot ' + (h.status === 'OK' ? 'ok' : 'fail') + '"></span>' +
      '<span class="di-domain" style="width:160px">' + actEsc(h.ts) + '</span>' +
      actBadge(h.status) +
      '<span class="di-recs">domain: ' + actEsc(h.domains) + ' &middot; build: ' + actEsc(h.build) + ' &middot; health: ' + actEsc(h.health) + '</span></div>';
  }
  box.html(html);
}
function actRun() {
  $.getJSON('activity_data.php', function (d) {
    $('#act-last').text('update terakhir: ' + (d.status ? d.status.last_update : '-'));
    actRenderStatus(d);
    actRenderHistory(d);
  }).fail(function () {
    $('#act-rows').html('<div class="di-empty">Gagal mengambil data.</div>');
  });
}
$(function () { actRun(); setInterval(actRun, 10000); });
