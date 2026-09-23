/* Heart check form (patient/form.php): keeps the progress rail and the sticky
   submit bar up to date. The rail and bar are rendered by form.php; this only
   updates counts, ticks off finished sections and highlights the section in
   view. The form works the same without it. */
(function () {
  var form = document.querySelector('.form-main form');
  var rail = document.querySelector('.form-progress');
  if (!form || !rail) return;

  var groups = Array.prototype.slice.call(form.querySelectorAll('fieldset.field-group'));
  var total = form.querySelectorAll('.field input, .field select').length;
  var bar = rail.querySelector('.form-progress__bar i');

  // Rail link for each section, matched by the fieldset's id.
  var links = groups.map(function (g) {
    return rail.querySelector('a[data-group="' + g.id + '"]');
  });

  function isFilled(el) {
    return el.value !== '' && el.value != null;
  }

  function update() {
    var answered = 0;
    groups.forEach(function (g, i) {
      var inputs = g.querySelectorAll('.field input, .field select');
      var done = 0;
      inputs.forEach(function (el) {
        var ok = isFilled(el);
        el.closest('.field').classList.toggle('is-filled', ok);
        if (ok) done++;
      });
      answered += done;

      var complete = done === inputs.length;
      g.classList.toggle('is-done', complete);
      if (links[i]) {
        links[i].classList.toggle('is-done', complete);
        links[i].querySelector('[data-count]').textContent = done + '/' + inputs.length;
      }
    });

    document.querySelectorAll('[data-answered]').forEach(function (n) {
      n.textContent = answered;
    });
    bar.style.width = (total ? answered / total * 100 : 0) + '%';
  }

  // Highlight the section currently in the middle of the screen.
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        var idx = groups.indexOf(e.target);
        links.forEach(function (l, i) {
          if (l) l.classList.toggle('is-active', i === idx);
        });
      });
    }, { rootMargin: '-40% 0px -55% 0px' });
    groups.forEach(function (g) { io.observe(g); });
  }

  form.addEventListener('input', update);
  form.addEventListener('change', update);
  update();
})();
