/* Öncesi/sonrası kaydırıcı — vanilla, fare + dokunmatik. */
(function () {
  document.querySelectorAll('[data-oncesi-sonrasi]').forEach(function (kap) {
    var once = kap.querySelector('img:first-child');
    if (!once) return;
    var tutamac = document.createElement('div');
    tutamac.className = 'os-tutamac';
    tutamac.setAttribute('role', 'slider');
    tutamac.setAttribute('tabindex', '0');
    kap.appendChild(tutamac);

    var konumla = function (istemciX) {
      var kutu = kap.getBoundingClientRect();
      var oran = Math.min(1, Math.max(0, (istemciX - kutu.left) / kutu.width));
      once.style.clipPath = 'inset(0 ' + ((1 - oran) * 100) + '% 0 0)';
      tutamac.style.left = (oran * 100) + '%';
    };

    var surukle = false;
    kap.addEventListener('pointerdown', function (o) { surukle = true; konumla(o.clientX); });
    window.addEventListener('pointermove', function (o) { if (surukle) konumla(o.clientX); });
    window.addEventListener('pointerup', function () { surukle = false; });
    konumla(kap.getBoundingClientRect().left + kap.getBoundingClientRect().width / 2);
  });
})();
