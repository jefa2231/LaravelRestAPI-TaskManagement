/* Minimal offline syntax highlighter: PHP, JS, JSON, Bash, HTTP, plain. */
(function () {
  const KEYWORDS = {
    php: ['public','private','protected','function','return','class','extends','implements','use','namespace','new','fn','static','array','int','string','bool','float','null','true','false','if','else','elseif','foreach','as','throw','match','readonly','enum','case','default','void','self','parent','try','catch','finally','instanceof','declare','strict_types','const','abstract','final','interface','trait'],
    js: ['import','from','export','const','let','var','function','return','async','await','if','else','throw','new','class','extends','try','catch','finally','typeof','instanceof','null','undefined','true','false','of','in','for','while','default','case','switch','break','continue','static','get','set','this','void'],
    bash: ['curl','php','npm','node','docker','composer','cd','export','echo','artisan','run','install','serve','make']
  };

  function esc(s) {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function wrap(cls, text) {
    return '<span class="tok-' + cls + '">' + text + '</span>';
  }

  function highlightJson(src) {
    const re = /("(?:\\.|[^"\\])*")(\s*:)?|\b(true|false|null)\b|(-?\b\d+(?:\.\d+)?\b)/g;
    return esc(src).replace(re, (m, str, colon, lit, num) => {
      if (str) return colon ? wrap('k', str) + colon : wrap('s', str);
      if (lit) return wrap('k', lit);
      if (num) return wrap('n', num);
      return m;
    });
  }

  function highlightCode(src, lang) {
    if (lang === 'json') return highlightJson(src);

    if (lang === 'http') {
      return esc(src)
        .replace(/^([A-Z]+)(\s+)(\S+)(\s+)(HTTP\/[\d.]+)?/gm, (m, a, b, c, d, e) =>
          wrap('k', a) + b + wrap('s', c) + d + (e ? wrap('n', e) : ''))
        .replace(/^([A-Za-z-]+):/gm, (m, h) => wrap('k', h) + ':');
    }

    if (lang === 'text' || lang === 'plain' || lang === 'tree') {
      return esc(src)
        .replace(/^(#.*|  #.*)$/gm, (m) => wrap('c', m))
        .replace(/(#\s[^\n]*)$/gm, (m) => wrap('c', m));
    }

    const kw = (KEYWORDS[lang] || []).join('|');
    // Tujuh grup, berurutan: komentar, string, variabel, keyword, member, angka, pemanggilan.
    const pattern = [
      /(\/\/[^\n]*|\/\*[\s\S]*?\*\/|#[^\n]*)/.source,
      /("(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'|`(?:\\.|[^`\\])*`)/.source,
      /(\$[A-Za-z_]\w*)/.source,
      kw ? '\\b(' + kw + ')\\b' : '()',
      /(-&gt;\w+|->\w+|::\w+)/.source,
      /(\b\d+(?:\.\d+)?\b)/.source,
      /(\b[A-Za-z_]\w*(?=\s*\())/.source
    ];
    const re = new RegExp(pattern.join('|'), 'g');

    return esc(src).replace(re, (m, comment, str, variable, keyword, member, number, call) => {
      if (comment) return wrap('c', comment);
      if (str) return wrap('s', str);
      if (variable) return wrap('v', variable);
      if (keyword) return wrap('k', keyword);
      if (member) return wrap('a', member);
      if (number) return wrap('n', number);
      if (call) return wrap('f', call);
      return m;
    });
  }

  function run() {
    document.querySelectorAll('pre[data-lang]').forEach((pre) => {
      const lang = pre.getAttribute('data-lang');
      const source = pre.textContent.replace(/^\n+|\n+$/g, '');
      pre.innerHTML = '<code>' + highlightCode(source, lang) + '</code>';
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
