var ST_HIGHLIGHTS = [
  ['Total Queries',     'total.num.queries',         'fa-solid fa-arrow-right-long', ''],
  ['Blocked (Trust+)',  'total.num.blacklist',       'fa-solid fa-ban',             'blocklist'],
  ['Cache Hits',        'total.num.cachehits',       'fa-solid fa-bolt',            ''],
  ['Cache Misses',      'total.num.cachemiss',       'fa-solid fa-magnifying-glass',''],
  ['Recursive Replies', 'total.num.recursivereplies','fa-solid fa-rotate',          ''],
  ['Prefetch',          'total.num.prefetch',        'fa-solid fa-forward',         ''],
  ['Uptime (s)',        'time.up',                   'fa-solid fa-clock',           '']
];

function stRender(data) {
  var html = '';
  $.each(ST_HIGHLIGHTS, function (i, h) {
    var val = (data.stats && data.stats[h[1]] != null) ? data.stats[h[1]] : '0';
    html += '<div class="tng-stat-card ' + h[3] + '">'
          + '<div class="tng-stat-head"><span class="tng-stat-icon"><i class="' + h[2] + '"></i></span>'
          + '<span class="tng-stat-name">' + h[0] + '</span></div>'
          + '<div class="tng-stat-value">' + $('<div>').text(val).html() + '</div>'
          + '</div>';
  });
  $('#st-cards').html(html);
  if (data.raw != null) $('#stats-raw').text(data.raw);
}

function stToggleRaw() {
  var pre = $('#stats-raw');
  pre.toggleClass('is-hidden');
  $('#st-toggle-btn').text(pre.hasClass('is-hidden') ? 'Tampilkan' : 'Sembunyikan');
}

function stRefresh() {
  $('#st-cards').addClass('di-loading');
  $.getJSON('stats_data.php', function (data) {
    if (data) {
      if (data.ok) stRender(data);
      else if (data.error) {
        $('#st-cards').html('<div class="di-card" style="grid-column:1/-1;border-left:3px solid #ff4d6d;padding:12px;color:#ffb3c0">'
          + $('<div>').text('Unbound stats error: ' + data.error).html()
          + '<br><small>Hints: sock /etc/unbound/run/unbound.sock, groups www-data, symlink /usr/local/etc/unbound/unbound.conf</small></div>');
        if (data.raw) $('#stats-raw').text(data.raw);
      }
    }
  }, 'json')
  .fail(function () { /* keep last good values */ })
  .always(function () { $('#st-cards').removeClass('di-loading'); });
}

$(function () { stRefresh(); });
