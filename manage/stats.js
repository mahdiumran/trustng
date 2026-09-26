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
    html += '<div class="tng-status-card ' + h[3] + '">'
          + '<div class="tng-status-icon"><i class="' + h[2] + '" aria-hidden="true"></i></div>'
          + '<div class="tng-status-info"><span class="tng-status-name">' + h[0] + '</span>'
          + '<span class="tng-status-val">' + $('<div>').text(val).html() + '</span></div>'
          + '</div>';
  });
  $('#st-cards').html(html);
  if (data.raw != null) $('#stats-raw').text(data.raw);
}

function stToggleRaw() {
  var pre = $('#stats-raw');
  pre.toggleClass('stats-raw');
  $('#st-toggle-btn').text(pre.hasClass('stats-raw') ? 'Tampilkan' : 'Sembunyikan');
}

function stRefresh() {
  $.getJSON('stats_data.php', function (data) {
    if (data) {
      if (data.ok) stRender(data);
      else if (data.error) {
        $('#st-cards').html('');
        $('#st-error').html('<div class="notice notice-critical" role="status">'
          + '<i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><div><strong>Unbound stats error</strong>'
          + '<p>' + $('<div>').text(data.error).html() + '</p></div></div>');
        if (data.raw) $('#stats-raw').text(data.raw);
      }
    }
  }, 'json')
  .fail(function () { /* keep last good values */ });
}

$(function () { stRefresh(); });
