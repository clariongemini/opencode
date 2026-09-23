/* Kamelya Admin — API istemcisi + sayfa modülleri. */
(function () {
  var kok = document.querySelector('main.icerik');
  if (!kok) return;
  var API = kok.getAttribute('data-api');
  var DILLER = (kok.getAttribute('data-diller') || 'tr').split(',');
  var SAYFA = kok.getAttribute('data-sayfa');

  function jeton() { try { return localStorage.getItem('kamelya_token') || ''; } catch (e) { return ''; } }
  function csrf() { try { return localStorage.getItem('kamelya_csrf') || ''; } catch (e) { return ''; } }

  function api(yol, secenek) {
    secenek = secenek || {};
    var basliklar = { 'Accept': 'application/json', 'Authorization': 'Bearer ' + jeton() };
    var govde;
    if (secenek.formData) {
      govde = secenek.formData;
    } else if (secenek.govde !== undefined) {
      basliklar['Content-Type'] = 'application/json';
      govde = JSON.stringify(secenek.govde);
    }
    if ((secenek.yontem || 'GET') !== 'GET' && csrf()) basliklar['X-CSRF-Token'] = csrf();
    return fetch(API + yol, { method: secenek.yontem || 'GET', headers: basliklar, body: govde })
      .then(function (y) {
        if (y.status === 401) { window.location.href = '/index.php'; throw new Error('yetki'); }
        return y.json();
      });
  }

  function esc(s) {
    return String(s === null || s === undefined ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function uyari(msj, tur) {
    var alan = document.getElementById('panel-uyari');
    if (alan) alan.innerHTML = '<div class="uyari ' + (tur || 'uyari-hata') + '">' + esc(msj) + '</div>';
  }

  function slugUret(metin) {
    var harita = { 'ç': 'c', 'ğ': 'g', 'ı': 'i', 'ö': 'o', 'ş': 's', 'ü': 'u' };
    return (metin || '').toLowerCase().split('').map(function (h) { return harita[h] || h; }).join('')
      .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').substring(0, 220) || 'kayit';
  }

  /* B.2 demo içerik rozeti — ayarlar.demo_icerik='1' ise görünür (salt admin). */
  api('/admin/ayarlar').then(function (g) {
    var demo = (g.data || []).some(function (a) {
      return a.anahtar === 'demo_icerik' && String(a.deger) === '1';
    });
    if (demo) {
      var rozet = document.getElementById('demo-icerik-rozet');
      if (rozet) rozet.hidden = false;
    }
  }).catch(function () {});

  /* Dil sekmeli çeviri alanları üretir. alanlar: [{ad, etiket, tur}] */
  function dilSekmeler(kapId, icerikId, alanlar, onDeger) {
    var kap = document.getElementById(kapId), ice = document.getElementById(icerikId);
    if (!kap || !ice) return;
    onDeger = onDeger || {};
    DILLER.forEach(function (d, i) {
      var b = document.createElement('button');
      b.type = 'button'; b.textContent = d.toUpperCase();
      if (i === 0) b.className = 'aktif';
      b.addEventListener('click', function () {
        kap.querySelectorAll('button').forEach(function (x) { x.className = ''; });
        b.className = 'aktif';
        ice.querySelectorAll('.sekme').forEach(function (x) { x.className = 'sekme'; });
        document.getElementById(icerikId + '-' + d).className = 'sekme aktif';
      });
      kap.appendChild(b);
      var s = document.createElement('div');
      s.className = 'sekme' + (i === 0 ? ' aktif' : '');
      s.id = icerikId + '-' + d;
      var rtl = d === 'ar' ? ' dir="rtl"' : '';
      var html = '';
      alanlar.forEach(function (a) {
        var val = esc(((onDeger[d] || {})[a.ad]) || '');
        if (a.tur === 'alan') html += '<div class="form-alan"><label class="etiket">' + a.etiket + ' (' + d + ')</label><textarea class="input" data-dil="' + d + '" data-alan="' + a.ad + '"' + rtl + '>' + val + '</textarea></div>';
        else html += '<div class="form-alan"><label class="etiket">' + a.etiket + ' (' + d + ')</label><input class="input" data-dil="' + d + '" data-alan="' + a.ad + '" value="' + val + '"' + rtl + '></div>';
      });
      s.innerHTML = html;
      ice.appendChild(s);
    });
    /* Başlık yazılınca slug otomatik dolsun. */
    ice.addEventListener('input', function (o) {
      var g = o.target;
      if (g.getAttribute('data-alan') === 'baslik') {
        var slug = ice.querySelector('[data-dil="' + g.getAttribute('data-dil') + '"][data-alan="slug"]');
        if (slug && !slug.value) slug.value = slugUret(g.value);
      }
    });
  }

  function cevirileriTopla(icerikId, alanlar) {
    var ice = document.getElementById(icerikId);
    var cikti = {};
    DILLER.forEach(function (d) {
      var kayit = {};
      alanlar.forEach(function (a) {
        var el = ice.querySelector('[data-dil="' + d + '"][data-alan="' + a.ad + '"]');
        kayit[a.ad] = el ? el.value : '';
      });
      var dolu = Object.keys(kayit).some(function (k) { return (kayit[k] || '').trim() !== ''; });
      if (dolu) cikti[d] = kayit;
    });
    return cikti;
  }

  function sorgu(ad) { return new URLSearchParams(window.location.search).get(ad); }

  var URUN_ALANLAR = [
    { ad: 'baslik', etiket: 'Başlık' }, { ad: 'slug', etiket: 'Slug' },
    { ad: 'kisa_aciklama', etiket: 'Kısa Açıklama', tur: 'alan' },
    { ad: 'detayli_aciklama', etiket: 'Detaylı Açıklama', tur: 'alan' },
    { ad: 'seo_baslik', etiket: 'SEO Başlık' }, { ad: 'seo_aciklama', etiket: 'SEO Açıklama' },
    { ad: 'cati_tipi_aciklama', etiket: 'Çatı Açıklaması', tur: 'alan' },
    { ad: 'korkuluk_aciklama', etiket: 'Korkuluk Açıklaması', tur: 'alan' }
  ];
  var BLOG_ALANLAR = [
    { ad: 'baslik', etiket: 'Başlık' }, { ad: 'slug', etiket: 'Slug' },
    { ad: 'ozet', etiket: 'Özet', tur: 'alan' }, { ad: 'icerik', etiket: 'İçerik', tur: 'alan' },
    { ad: 'seo_baslik', etiket: 'SEO Başlık' }, { ad: 'seo_aciklama', etiket: 'SEO Açıklama' }
  ];

  /* ---------- Sayfa modülleri ---------- */

  if (SAYFA === 'dashboard') {
    Promise.all([
      api('/admin/urunler?per_page=1'), api('/admin/blog-yazilari?per_page=1'),
      api('/admin/talepler?per_page=1'), api('/admin/audit-logs?per_page=1')
    ]).then(function (r) {
      document.getElementById('ozet').innerHTML =
        '<ul><li>Ürünler: ' + (r[0].meta.total || 0) + '</li>' +
        '<li>Blog yazıları: ' + (r[1].meta.total || 0) + '</li>' +
        '<li>Talepler: ' + (r[2].meta.total || 0) + '</li>' +
        '<li>Audit kayıtları: ' + (r[3].meta.total || 0) + '</li></ul>';
    }).catch(function () { uyari('Özet yüklenemedi.'); });
  }

  if (SAYFA === 'urunler') {
    var yukleUrun = function () {
      var q = document.getElementById('urun-arama').value;
      api('/admin/urunler?per_page=50' + (q ? '&q=' + encodeURIComponent(q) : '')).then(function (g) {
        document.getElementById('urun-satirlar').innerHTML = (g.data || []).map(function (u) {
          return '<tr><td>' + u.id + '</td><td>' + esc(u.urun_kodu) + '</td><td>' + esc(u.baslik_tr || '-') + '</td>' +
            '<td>' + (u.aktif == 1 ? 'aktif' : 'pasif') + '</td>' +
            '<td><a href="/panel.php?sayfa=urun-form&id=' + u.id + '">Düzenle</a> ' +
            '<button data-sil="' + u.id + '">Sil</button></td></tr>';
        }).join('');
        document.querySelectorAll('[data-sil]').forEach(function (b) {
          b.addEventListener('click', function () {
            if (!confirm('Silinsin mi? (soft delete)')) return;
            api('/admin/urunler/' + b.getAttribute('data-sil'), { yontem: 'DELETE' }).then(yukleUrun);
          });
        });
      }).catch(function () { uyari('Liste yüklenemedi.'); });
    };
    document.getElementById('urun-ara').addEventListener('click', yukleUrun);
    yukleUrun();
  }

  if (SAYFA === 'urun-form') {
    var duzenId = sorgu('id');
    ['model', 'malzeme', 'kullanim_amaci'].forEach(function (tur) {
      api('/admin/kategoriler?tur=' + tur).then(function (g) {
        var sel = document.getElementById(tur === 'model' ? 'u-model' : (tur === 'malzeme' ? 'u-malzeme' : 'u-kullanim'));
        sel.innerHTML = (g.data || []).map(function (k) { return '<option value="' + k.id + '">' + esc(k.kod) + '</option>'; }).join('');
      });
    });
    var tamamla = function (mevcut) {
      dilSekmeler('u-sekmeler', 'u-sekmeler-icerik', URUN_ALANLAR, (mevcut && mevcut.ceviriler) || {});
      if (mevcut) {
        document.getElementById('u-id').value = mevcut.id;
        document.getElementById('u-kod').value = mevcut.urun_kodu || '';
        document.getElementById('u-cati').value = mevcut.cati_tipi || '';
        document.getElementById('u-korkuluk').value = mevcut.korkuluk_malzeme || '';
        document.getElementById('u-korkuluk-yukseklik').value = mevcut.korkuluk_yukseklik_cm || '';
      }
      gorselListele(mevcut && mevcut.id ? mevcut.id : null, (mevcut && mevcut.resimler) || []);
    };
    var gorselListele = function (urunId, resimler) {
      var alan = document.getElementById('u-gorseller');
      if (!alan) return;
      alan.innerHTML = (resimler || []).map(function (r) {
        return '<p>#' + r.id + ' [' + esc(r.tur || 'normal') + '] ' + esc(r.dosya_yolu || '') +
          (r.kapak_mi == 1 ? ' (kapak)' : '') +
          ' <button data-gorsel-sil="' + r.id + '" type="button">Sil</button></p>';
      }).join('') || '<p>Görsel yok.</p>';
      alan.querySelectorAll('[data-gorsel-sil]').forEach(function (b) {
        b.addEventListener('click', function () {
          if (!confirm('Görsel silinsin mi?')) return;
          api('/admin/upload/' + b.getAttribute('data-gorsel-sil'), { yontem: 'DELETE' }).then(function () {
            if (urunId) api('/admin/urunler/' + urunId).then(function (g) { gorselListele(urunId, (g.data && g.data.resimler) || []); });
          });
        });
      });
    };
    var gorselBtn = document.getElementById('u-gorsel-yukle');
    if (gorselBtn) gorselBtn.addEventListener('click', function () {
      var id = document.getElementById('u-id').value;
      var dosya = document.getElementById('u-dosya').files[0];
      if (!id) { uyari('Önce ürünü kaydedin.'); return; }
      if (!dosya) { uyari('Dosya seçin.'); return; }
      var fd = new FormData();
      fd.append('file', dosya);
      fd.append('hedef', 'urun');
      fd.append('hedef_id', id);
      fd.append('tur', document.getElementById('u-tur').value);
      api('/admin/upload', { yontem: 'POST', formData: fd }).then(function (g) {
        if (!g.success) { uyari('Yükleme başarısız.'); return; }
        api('/admin/urunler/' + id).then(function (d) { gorselListele(id, (d.data && d.data.resimler) || []); });
      }).catch(function () { uyari('Yükleme başarısız.'); });
    });
    if (duzenId) {
      api('/admin/urunler/' + duzenId).then(function (g) { tamamla(g.data); })
        .catch(function () { uyari('Kayıt yüklenemedi.'); });
    } else { tamamla(null); }
    document.getElementById('urun-formu').addEventListener('submit', function (o) {
      o.preventDefault();
      var veri = {
        urun_kodu: document.getElementById('u-kod').value,
        model_kategori_id: parseInt(document.getElementById('u-model').value, 10),
        malzeme_kategori_id: parseInt(document.getElementById('u-malzeme').value, 10),
        kullanim_kategori_id: document.getElementById('u-kullanim').value || null,
        cati_tipi: document.getElementById('u-cati').value || null,
        korkuluk_malzeme: document.getElementById('u-korkuluk').value || null,
        korkuluk_yukseklik_cm: document.getElementById('u-korkuluk-yukseklik').value || null,
        ceviriler: cevirileriTopla('u-sekmeler-icerik', URUN_ALANLAR)
      };
      var id = document.getElementById('u-id').value;
      var yol = id ? '/admin/urunler/' + id : '/admin/urunler';
      var istek = api(yol, { yontem: id ? 'PUT' : 'POST', govde: veri }).then(function (g) {
        if (!g.success) { uyari('Kaydet başarısız.'); return null; }
        return g;
      }).then(function (g) {
        if (g === null) return;
        var kayitId = id || (g.data && g.data.id);
        var videoUrl = document.getElementById('u-video').value.trim();
        if (videoUrl !== '' && kayitId) {
          return api('/admin/urunler/' + kayitId + '/video', { yontem: 'POST', govde: { video_url: videoUrl } }).then(function () {
            window.location.href = '/panel.php?sayfa=urunler';
          });
        }

        window.location.href = '/panel.php?sayfa=urunler';
      }).catch(function () { uyari('Kaydet başarısız.'); });
    });
  }

  if (SAYFA === 'kategoriler') {
    var yukleKat = function () {
      var tur = document.getElementById('kat-tur').value;
      api('/admin/kategoriler' + (tur ? '?tur=' + tur : '')).then(function (g) {
        document.getElementById('kat-satirlar').innerHTML = (g.data || []).map(function (k) {
          return '<tr><td>' + k.id + '</td><td>' + esc(k.tur) + '</td><td>' + esc(k.kod) + '</td><td>' + k.sira + '</td><td>' + (k.aktif == 1 ? 'aktif' : 'pasif') + '</td></tr>';
        }).join('');
      }).catch(function () { uyari('Liste yüklenemedi.'); });
    };
    document.getElementById('kat-filtrele').addEventListener('click', yukleKat);
    yukleKat();
  }

  if (SAYFA === 'blog') {
    api('/admin/blog-yazilari?per_page=50').then(function (g) {
      document.getElementById('blog-satirlar').innerHTML = (g.data || []).map(function (y) {
        return '<tr><td>' + y.id + '</td><td>' + esc(y.baslik_tr || '-') + '</td><td>' + esc(y.yayin_durumu) + '</td>' +
          '<td><a href="/panel.php?sayfa=blog-form&id=' + y.id + '">Düzenle</a> ' +
          '<button data-sil="' + y.id + '">Sil</button></td></tr>';
      }).join('');
      document.querySelectorAll('[data-sil]').forEach(function (b) {
        b.addEventListener('click', function () {
          if (!confirm('Silinsin mi? (soft delete)')) return;
          api('/admin/blog-yazilari/' + b.getAttribute('data-sil'), { yontem: 'DELETE' })
            .then(function () { window.location.reload(); });
        });
      });
    }).catch(function () { uyari('Liste yüklenemedi.'); });
  }

  if (SAYFA === 'blog-form') {
    var bId = sorgu('id');
    var bDoldur = function (mevcut) {
      dilSekmeler('b-sekmeler', 'b-sekmeler-icerik', BLOG_ALANLAR, (mevcut && mevcut.ceviriler) || {});
      if (mevcut) {
        document.getElementById('b-id').value = mevcut.id;
        document.getElementById('b-durum').value = mevcut.yayin_durumu || 'taslak';
      }
    };
    if (bId) {
      api('/admin/blog-yazilari/' + bId).then(function (g) { bDoldur(g.data); })
        .catch(function () { uyari('Kayıt yüklenemedi.'); });
    } else { bDoldur(null); }
    document.getElementById('b-onizle').addEventListener('click', function () {
      var slugEl = document.querySelector('[data-dil="tr"][data-alan="slug"]');
      var slug = slugEl && slugEl.value ? slugEl.value : '';
      if (!slug) { uyari('Önce TR slug girin.'); return; }
      window.open('/blog/' + encodeURIComponent(slug), '_blank');
    });
    document.getElementById('blog-formu').addEventListener('submit', function (o) {
      o.preventDefault();
      var veri = {
        yayin_durumu: document.getElementById('b-durum').value,
        ceviriler: cevirileriTopla('b-sekmeler-icerik', BLOG_ALANLAR)
      };
      var id = document.getElementById('b-id').value;
      api(id ? '/admin/blog-yazilari/' + id : '/admin/blog-yazilari', { yontem: id ? 'PUT' : 'POST', govde: veri })
        .then(function (g) {
          if (!g.success) { uyari('Kaydet başarısız.'); return; }
          window.location.href = '/panel.php?sayfa=blog';
        }).catch(function () { uyari('Kaydet başarısız.'); });
    });
  }

  if (SAYFA === 'sss') {
    dilSekmeler('s-sekmeler', 's-sekmeler-icerik', [
      { ad: 'soru', etiket: 'Soru' }, { ad: 'cevap', etiket: 'Cevap', tur: 'alan' }
    ], {});
    var yukleSss = function () {
      api('/admin/sss-sorulari').then(function (g) {
        document.getElementById('sss-satirlar').innerHTML = (g.data || []).map(function (s) {
          return '<tr><td>' + s.id + '</td><td>' + esc(s.soru_tr || '-') + '</td><td>' + s.sira +
            ' <button data-sira="' + s.id + '" data-deger="' + s.sira + '">değiştir</button></td><td>' + (s.aktif == 1 ? 'aktif' : 'pasif') + '</td>' +
            '<td><button data-sil="' + s.id + '">Pasifleştir</button></td></tr>';
        }).join('');
        document.querySelectorAll('[data-sira]').forEach(function (b) {
          b.addEventListener('click', function () {
            var yeni = prompt('Yeni sıra:', b.getAttribute('data-deger'));
            if (yeni === null) return;
            api('/admin/sss-sorulari/' + b.getAttribute('data-sira'), { yontem: 'PUT', govde: { sira: parseInt(yeni, 10) || 0 } }).then(yukleSss);
          });
        });
        document.querySelectorAll('[data-sil]').forEach(function (b) {
          b.addEventListener('click', function () {
            api('/admin/sss-sorulari/' + b.getAttribute('data-sil'), { yontem: 'DELETE' }).then(yukleSss);
          });
        });
      }).catch(function () { uyari('Liste yüklenemedi.'); });
    };
    document.getElementById('sss-formu').addEventListener('submit', function (o) {
      o.preventDefault();
      api('/admin/sss-sorulari', {
        yontem: 'POST',
        govde: {
          sayfa_kapsami: document.getElementById('s-kapsam').value,
          sira: parseInt(document.getElementById('s-sira').value, 10) || 0,
          ceviriler: cevirileriTopla('s-sekmeler-icerik', [{ ad: 'soru' }, { ad: 'cevap' }])
        }
      }).then(function (g) {
        if (!g.success) { uyari('Kaydet başarısız.'); return; }
        yukleSss();
      }).catch(function () { uyari('Kaydet başarısız.'); });
    });
    yukleSss();
  }

  if (SAYFA === 'galeri') {
    var yukleGaleri = function () {
      api('/admin/galeri').then(function (g) {
        var kokApi = API.replace('/api/v1', '');
        document.getElementById('galeri-alan').innerHTML = (g.data || []).map(function (r) {
          var ad = String(r.dosya_yolu || '').split('/').pop();
          return '<div class="card"><img src="' + kokApi + '/api/v1/dosyalar/galeri/' + encodeURIComponent(ad) +
            '" alt="' + esc(r.baslik) + '" loading="lazy"><div class="card-govde"><p>' + esc(r.baslik) + '</p>' +
            '<button data-sil="' + r.id + '">Pasifleştir</button></div></div>';
        }).join('');
        document.querySelectorAll('[data-sil]').forEach(function (b) {
          b.addEventListener('click', function () {
            api('/admin/galeri/' + b.getAttribute('data-sil'), { yontem: 'DELETE' }).then(yukleGaleri);
          });
        });
      }).catch(function () { uyari('Galeri yüklenemedi.'); });
    };
    document.getElementById('galeri-formu').addEventListener('submit', function (o) {
      o.preventDefault();
      var fd = new FormData();
      fd.append('hedef', 'galeri');
      fd.append('hedef_id', '0');
      fd.append('baslik', document.getElementById('g-baslik').value);
      fd.append('proje_hikayesi', document.getElementById('g-hikaye').value);
      var dosya = document.getElementById('g-dosya').files[0];
      if (!dosya) { uyari('Dosya seçin.'); return; }
      fd.append('file', dosya);
      api('/admin/upload', { yontem: 'POST', formData: fd }).then(function (g) {
        if (!g.success) { uyari('Yükleme başarısız.'); return; }
        document.getElementById('galeri-form-sonuc').innerHTML = '<div class="uyari uyari-basarili">Yüklendi.</div>';
        yukleGaleri();
      }).catch(function () { uyari('Yükleme başarısız.'); });
    });
    yukleGaleri();
  }

  if (SAYFA === 'ayarlar') {
    var eslesme = { 'site_adi': 'a-site', 'iletisim_telefonu': 'a-tel', 'whatsapp_numarasi': 'a-wa', 'varsayilan_dil': 'a-dil', 'bildirim_epostalari': 'a-bildirim', 'bildirim_iletisim': 'a-bildirim-acik', 'garanti_suresi': 'a-garanti-sure', 'instagram_hesap': 'a-instagram', 'instagram_embed_aktif': 'a-instagram-acik' };
    var garantiAlanlar = [{ ad: 'kapsam', etiket: 'Kapsam', tur: 'alan' }, { ad: 'istisna', etiket: 'İstisnalar', tur: 'alan' }];
    var odemeAlanlar = [{ ad: 'not', etiket: 'Ödeme Notu', tur: 'alan' }];
    dilSekmeler('g-sekmeler', 'g-sekmeler-icerik', garantiAlanlar, {});
    dilSekmeler('o-sekmeler', 'o-sekmeler-icerik', odemeAlanlar, {});
    var bankalar = [];
    var bankaCiz = function () {
      document.getElementById('banka-satirlar').innerHTML = bankalar.map(function (b, i) {
        return '<p>' + esc(b.banka || '') + ' — ' + esc(b.iban || '') +
          ' <button data-banka-sil="' + i + '" type="button">Sil</button></p>';
      }).join('') || '<p>Banka yok.</p>';
      document.querySelectorAll('[data-banka-sil]').forEach(function (x) {
        x.addEventListener('click', function () {
          bankalar.splice(parseInt(x.getAttribute('data-banka-sil'), 10), 1);
          bankaCiz();
        });
      });
    };
    var bankaEkleBtn = document.getElementById('b-banka-ekle');
    if (bankaEkleBtn) bankaEkleBtn.addEventListener('click', function () {
      var iban = document.getElementById('b-yeni-iban').value.trim();
      if (!iban) return;
      bankalar.push({
        banka: document.getElementById('b-yeni-banka').value,
        iban: iban,
        hesap_sahibi: document.getElementById('b-yeni-sahip').value,
        sube: document.getElementById('b-yeni-sube').value
      });
      bankaCiz();
    });
    var sekmeliOku = function (icerikId, alanAd) {
      var cikti = {};
      DILLER.forEach(function (d) {
        var el = document.querySelector('#' + icerikId + ' [data-dil="' + d + '"][data-alan="' + alanAd + '"]');
        if (el && el.value.trim() !== '') cikti[d] = el.value;
      });
      return cikti;
    };
    var sekmeliYaz = function (icerikId, alanAd, veri) {
      DILLER.forEach(function (d) {
        var el = document.querySelector('#' + icerikId + ' [data-dil="' + d + '"][data-alan="' + alanAd + '"]');
        if (el) el.value = (veri || {})[d] || '';
      });
    };
    api('/admin/ayarlar').then(function (g) {
      var harita = {};
      (g.data || []).forEach(function (a) { harita[a.anahtar] = a.deger || ''; });
      Object.keys(eslesme).forEach(function (k) {
        var el = document.getElementById(eslesme[k]);
        if (!el) return;
        if (el.type === 'checkbox') el.checked = (harita[k] === '1');
        else el.value = harita[k] || '';
      });
      try {
        var kapsami = JSON.parse(harita['garanti_kapsami'] || '{}');
        var istisna = JSON.parse(harita['garanti_istisnalari'] || '{}');
        DILLER.forEach(function (d) {
          var k = document.querySelector('[data-dil="' + d + '"][data-alan="kapsam"]');
          var s = document.querySelector('[data-dil="' + d + '"][data-alan="istisna"]');
          if (k) k.value = kapsami[d] || '';
          if (s) s.value = istisna[d] || '';
        });
        sekmeliYaz('o-sekmeler-icerik', 'not', JSON.parse(harita['odeme_notu'] || '{}'));
        try {
          var gonderiler = JSON.parse(harita['instagram_gonderiler'] || '[]');
          document.getElementById('a-instagram-gonderiler').value = (Array.isArray(gonderiler) ? gonderiler : []).join('\n');
        } catch (e2) {}
        bankalar = JSON.parse(harita['banka_hesaplari'] || '[]');
        if (!Array.isArray(bankalar)) bankalar = [];
        bankaCiz();
      } catch (e) { uyari('Kayıtlı JSON okunamadı, sıfırdan girin.'); }
    }).catch(function () { uyari('Ayarlar yüklenemedi.'); });
    document.getElementById('ayar-formu').addEventListener('submit', function (o) {
      o.preventDefault();
      var degerler = {};
      Object.keys(eslesme).forEach(function (k) {
        var el = document.getElementById(eslesme[k]);
        degerler[k] = el.type === 'checkbox' ? (el.checked ? '1' : '0') : el.value;
      });
      var kapsami = {}, istisna = {};
      DILLER.forEach(function (d) {
        var k = document.querySelector('[data-dil="' + d + '"][data-alan="kapsam"]');
        var s = document.querySelector('[data-dil="' + d + '"][data-alan="istisna"]');
        if (k && k.value.trim() !== '') kapsami[d] = k.value;
        if (s && s.value.trim() !== '') istisna[d] = s.value;
      });
      degerler['garanti_kapsami'] = JSON.stringify(kapsami);
      degerler['garanti_istisnalari'] = JSON.stringify(istisna);
      degerler['odeme_notu'] = JSON.stringify(sekmeliOku('o-sekmeler-icerik', 'not'));
      degerler['banka_hesaplari'] = JSON.stringify(bankalar);
      degerler['instagram_gonderiler'] = JSON.stringify(
        document.getElementById('a-instagram-gonderiler').value.split('\n').map(function (s) { return s.trim(); }).filter(Boolean)
      );
      api('/admin/ayarlar', { yontem: 'PUT', govde: { degerler: degerler } }).then(function (g) {
        document.getElementById('ayar-form-sonuc').innerHTML = g.success
          ? '<div class="uyari uyari-basarili">Kaydedildi.</div>'
          : '<div class="uyari uyari-hata">Kaydet başarısız.</div>';
      }).catch(function () { uyari('Kaydet başarısız.'); });
    });
  }

  /* ---------- Talepler (tür sekmeli) ---------- */

  if (SAYFA === 'talepler') {
    var yukleTalep = function (tur) {
      api('/admin/talepler?tur=' + encodeURIComponent(tur) + '&limit=50').then(function (g) {
        document.getElementById('talep-satirlar').innerHTML = ((g.data || []).map(function (t) {
          return '<tr><td>' + t.id + '</td><td>' + esc(t.tur || 'teklif') + '</td><td>' + esc(t.ad_soyad || '') +
            '</td><td>' + esc(t.telefon || '') + '</td><td>' + esc(t.durum || '') + '</td>' +
            '<td><button data-tdetay=\'' + JSON.stringify(t).replace(/'/g, '&#39;') + '\'>Detay</button> ' +
            '<select data-durum="' + t.id + '">' +
            ['yeni', 'arandi', 'kesif', 'teklif', 'kazanildi', 'kaybedildi'].map(function (d) {
              return '<option value="' + d + '"' + (d === t.durum ? ' selected' : '') + '>' + d + '</option>';
            }).join('') + '</select></td></tr>';
        }).join('') || '<tr><td colspan="6">Kayıt yok.</td></tr>');
        document.querySelectorAll('[data-tdetay]').forEach(function (b) {
          b.addEventListener('click', function () {
            var t = JSON.parse(b.getAttribute('data-tdetay'));
            document.getElementById('talep-modal-baslik').textContent = '#' + t.id + ' ' + (t.ad_soyad || '');
            var satirlar = ['Telefon: ' + (t.telefon || '-'), 'E-posta: ' + (t.eposta || '-'),
              'Şehir: ' + (t.sehir || '-'), 'Durum: ' + (t.durum || ''), 'Tür: ' + (t.tur || '')];
            if (t.mesaj) satirlar.push('Mesaj: ' + t.mesaj);
            document.getElementById('talep-modal-detay').innerHTML =
              '<ul><li>' + satirlar.map(esc).join('</li><li>') + '</li></ul>';
            document.getElementById('talep-modal-yanit').innerHTML =
              '<a class="btn btn-birincil" href="mailto:' + encodeURIComponent(t.eposta || '') +
              '?subject=' + encodeURIComponent('Kamelya talebiniz #' + t.id) + '">Yanıtla</a> ';
            document.getElementById('talep-modal').hidden = false;
          });
        });
        document.querySelectorAll('[data-durum]').forEach(function (s) {
          s.addEventListener('change', function () {
            api('/admin/talepler/' + s.getAttribute('data-durum') + '/durum', { yontem: 'PUT', govde: { durum: s.value } })
              .then(function (g) {
                if (!g.success) uyari('Durum güncellenemedi.');
              })
              .catch(function () { uyari('Durum güncellenemedi.'); });
          });
        });
      }).catch(function () { uyari('Liste yüklenemedi.'); });
    };
    var aktifTur = 'teklif';
    document.querySelectorAll('#talep-sekmeler button').forEach(function (b) {
      b.addEventListener('click', function () {
        document.querySelectorAll('#talep-sekmeler button').forEach(function (x) { x.className = ''; });
        b.className = 'aktif';
        aktifTur = b.getAttribute('data-tur');
        yukleTalep(aktifTur);
      });
    });
    document.getElementById('talep-modal-kapat').addEventListener('click', function () {
      document.getElementById('talep-modal').hidden = true;
    });
    yukleTalep(aktifTur);
  }

  /* ---------- Sertifika / Ekip / Bülten ---------- */

  if (SAYFA === 'sertifikalar') {
    var yukleSertifika = function () {
      api('/admin/sertifikalar').then(function (g) {
        document.getElementById('sertifika-satirlar').innerHTML = ((g.data || []).map(function (s) {
          return '<tr><td>' + s.id + '</td><td>' + esc(s.baslik) + '</td><td>' + esc(s.kurum) + '</td><td>' +
            esc(s.belge_no || '-') + '</td><td><button data-sil="' + s.id + '">Pasifleştir</button></td></tr>';
        }).join('') || '<tr><td colspan="5">Kayıt yok.</td></tr>');
        document.querySelectorAll('[data-sil]').forEach(function (b) {
          b.addEventListener('click', function () {
            api('/admin/sertifikalar/' + b.getAttribute('data-sil'), { yontem: 'DELETE' }).then(yukleSertifika);
          });
        });
      }).catch(function () { uyari('Liste yüklenemedi.'); });
    };
    document.getElementById('sertifika-formu').addEventListener('submit', function (o) {
      o.preventDefault();
      api('/admin/sertifikalar', {
        yontem: 'POST',
        govde: {
          baslik: document.getElementById('s-baslik').value,
          kurum: document.getElementById('s-kurum').value,
          belge_no: document.getElementById('s-belge').value || null,
          gecerlilik_tarihi: document.getElementById('s-gecerlilik').value || null
        }
      }).then(function (g) {
        if (!g.success) { uyari('Kaydet başarısız.'); return; }
        document.getElementById('sertifika-formu').reset();
        yukleSertifika();
      }).catch(function () { uyari('Kaydet başarısız.'); });
    });
    yukleSertifika();
  }

  if (SAYFA === 'ekip') {
    var yukleEkip = function () {
      api('/admin/ekip').then(function (g) {
        document.getElementById('ekip-satirlar').innerHTML = ((g.data || []).map(function (u) {
          return '<tr><td>' + u.id + '</td><td>' + esc(u.ad_soyad) + '</td><td>' + esc(u.unvan || '-') + '</td><td>' +
            (u.public_goster == 1 ? 'evet' : 'hayır') + '</td>' +
            '<td><button data-duzenle=\'' + JSON.stringify(u).replace(/'/g, '&#39;') + '\'>Düzenle</button></td></tr>';
        }).join('') || '<tr><td colspan="5">Kayıt yok.</td></tr>');
        document.querySelectorAll('[data-duzenle]').forEach(function (b) {
          b.addEventListener('click', function () {
            var u = JSON.parse(b.getAttribute('data-duzenle'));
            document.getElementById('e-id').value = u.id;
            document.getElementById('e-unvan').value = u.unvan || '';
            document.getElementById('e-uzmanlik').value = u.uzmanlik_alani || '';
            document.getElementById('e-biyografi').value = u.biyografi || '';
            document.getElementById('e-public').checked = u.public_goster == 1;
          });
        });
      }).catch(function () { uyari('Liste yüklenemedi.'); });
    };
    document.getElementById('ekip-formu').addEventListener('submit', function (o) {
      o.preventDefault();
      var id = document.getElementById('e-id').value;
      if (!id) { uyari('Önce listeden seçin.'); return; }
      api('/admin/ekip/' + id, {
        yontem: 'PUT',
        govde: {
          unvan: document.getElementById('e-unvan').value,
          uzmanlik_alani: document.getElementById('e-uzmanlik').value,
          biyografi: document.getElementById('e-biyografi').value,
          public_goster: document.getElementById('e-public').checked ? 1 : 0
        }
      }).then(function (g) {
        document.getElementById('ekip-sonuc').innerHTML = g.success
          ? '<div class="uyari uyari-basarili">Kaydedildi.</div>'
          : '<div class="uyari uyari-hata">Kaydet başarısız.</div>';
        yukleEkip();
      }).catch(function () { uyari('Kaydet başarısız.'); });
    });
    yukleEkip();
  }

  if (SAYFA === 'bulten') {
    api('/admin/bulten?per_page=50').then(function (g) {
      document.getElementById('bulten-satirlar').innerHTML = ((g.data || []).map(function (a) {
        return '<tr><td>' + a.id + '</td><td>' + esc(a.eposta) + '</td><td>' + esc(a.ad_soyad || '-') +
          '</td><td>' + esc(a.durum) + '</td></tr>';
      }).join('') || '<tr><td colspan="4">Kayıt yok.</td></tr>');
    }).catch(function () { uyari('Liste yüklenemedi.'); });
    document.getElementById('bulten-csv').href = '#';
    document.getElementById('bulten-csv').addEventListener('click', function (o) {
      o.preventDefault();
      fetch(API + '/admin/bulten?format=csv', { headers: { 'Authorization': 'Bearer ' + jeton() } })
        .then(function (y) { return y.blob(); })
        .then(function (b) {
          var u = URL.createObjectURL(b);
          var a = document.createElement('a');
          a.href = u; a.download = 'bulten-aboneleri.csv';
          a.click();
          URL.revokeObjectURL(u);
        })
        .catch(function () { uyari('CSV indirilemedi.'); });
    });
    document.getElementById('bulten-toplu-formu').addEventListener('submit', function (o) {
      o.preventDefault();
      api('/admin/bulten/toplu', {
        yontem: 'POST',
        govde: { konu: document.getElementById('b-konu').value, govde: document.getElementById('b-govde').value }
      }).then(function (g) {
        document.getElementById('bulten-sonuc').innerHTML = g.success
          ? '<div class="uyari uyari-basarili">' + g.data.kuyruga_eklenen + ' alıcı kuyruğa eklendi.</div>'
          : '<div class="uyari uyari-hata">Gönderim başarısız.</div>';
      }).catch(function () { uyari('Gönderim başarısız.'); });
    });
  }

  /* ---------- Kampanyalar ---------- */

  if (SAYFA === 'kampanyalar') {
    var yukleKampanya = function () {
      api('/admin/kampanyalar').then(function (g) {
        document.getElementById('kampanya-satirlar').innerHTML = ((g.data || []).map(function (k) {
          return '<tr><td>' + k.id + '</td><td>' + esc(k.kod) + '</td><td>' +
            esc((k.baslangic || '').slice(0, 10)) + ' → ' + esc((k.bitis || '').slice(0, 10)) +
            '</td><td>' + (k.aktif == 1 ? 'aktif' : 'pasif') + '</td>' +
            '<td><button data-sil="' + k.id + '">Sil</button></td></tr>';
        }).join('') || '<tr><td colspan="5">Kayıt yok.</td></tr>');
        document.querySelectorAll('[data-sil]').forEach(function (b) {
          b.addEventListener('click', function () {
            if (!confirm('Silinsin mi?')) return;
            api('/admin/kampanyalar/' + b.getAttribute('data-sil'), { yontem: 'DELETE' }).then(yukleKampanya);
          });
        });
      }).catch(function () { uyari('Liste yüklenemedi.'); });
    };
    dilSekmeler('k-sekmeler', 'k-sekmeler-icerik', [
      { ad: 'baslik', etiket: 'Başlık' }, { ad: 'aciklama', etiket: 'Açıklama', tur: 'alan' }, { ad: 'cta_metni', etiket: 'CTA' }
    ], {});
    document.getElementById('kampanya-formu').addEventListener('submit', function (o) {
      o.preventDefault();
      var veri = {
        kod: document.getElementById('k-kod').value,
        indirim_orani: document.getElementById('k-oran').value || null,
        indirim_tipi: document.getElementById('k-tip').value,
        baslangic: document.getElementById('k-bas').value.replace('T', ' ') + ':00',
        bitis: document.getElementById('k-bit').value.replace('T', ' ') + ':00',
        ceviriler: cevirileriTopla('k-sekmeler-icerik', [{ ad: 'baslik' }, { ad: 'aciklama' }, { ad: 'cta_metni' }])
      };
      api('/admin/kampanyalar', { yontem: 'POST', govde: veri }).then(function (g) {
        if (!g.success) { uyari('Kaydet başarısız.'); return; }
        document.getElementById('kampanya-formu').reset();
        yukleKampanya();
      }).catch(function () { uyari('Kaydet başarısız.'); });
    });
    yukleKampanya();
  }

  /* ---------- Özellik toggle ---------- */

  if (SAYFA === 'ozellikler') {
    var yukleOzellik = function () {
      api('/admin/ozellikler').then(function (g) {
        var ciz = function (dugumler, derinlik) {
          return '<ul>' + dugumler.map(function (d) {
            var rozet = d.zorunlu == 1 ? ' (zorunlu)' : '';
            var durum = d.etkin_aktif == 1 ? 'açık' : 'kapalı';
            var buton = d.zorunlu == 1 ? '' : ' <button data-toggle="' + d.anahtar + '" data-durum="' + d.aktif + '">' +
              (d.aktif == 1 ? 'Kapat' : 'Aç') + '</button>';
            var toplu = (d.cocuklar && d.cocuklar.length > 0)
              ? ' <button data-toplu-ac="' + d.anahtar + '">Tümünü Aç</button>' +
                ' <button data-toplu-kapat="' + d.anahtar + '">Tümünü Kapat</button>'
              : '';
            return '<li><strong>' + esc(d.baslik) + '</strong> <small>' + esc(d.anahtar) + rozet + ' — ' + durum + '</small>' +
              buton + toplu + ciz(d.cocuklar || [], derinlik + 1) + '</li>';
          }).join('') + '</ul>';
        };
        document.getElementById('ozellik-agac').innerHTML = ciz(g.data || [], 0);
        document.querySelectorAll('[data-toggle]').forEach(function (b) {
          b.addEventListener('click', function () {
            var acik = b.getAttribute('data-durum') == '1';
            api('/admin/ozellikler/' + b.getAttribute('data-toggle'), { yontem: 'PUT', govde: { aktif: !acik } })
              .then(function (g) {
                if (!g.success) { uyari('Değişmedi.'); return; }
                yukleOzellik();
              })
              .catch(function () { uyari('Değişmedi.'); });
          });
        });
        ['data-toplu-ac', 'data-toplu-kapat'].forEach(function (nitelik) {
          document.querySelectorAll('[' + nitelik + ']').forEach(function (b) {
            b.addEventListener('click', function () {
              var acik = nitelik === 'data-toplu-ac';
              api('/admin/ozellikler/' + b.getAttribute(nitelik) + '/cocuklar', { yontem: 'PUT', govde: { aktif: acik } })
                .then(function (g) {
                  if (!g.success) { uyari('Değişmedi.'); return; }
                  yukleOzellik();
                })
                .catch(function () { uyari('Değişmedi.'); });
            });
          });
        });
      }).catch(function () { uyari('Ağaç yüklenemedi.'); });
    };
    yukleOzellik();
  }

  /* ---------- Şehirler ---------- */

  if (SAYFA === 'sehirler') {
    api('/admin/sehirler').then(function (g) {
      var sec = document.getElementById('se-sehir');
      document.getElementById('sehir-satirlar').innerHTML = ((g.data || []).map(function (s) {
        var o = document.createElement('option');
        o.value = s.id; o.textContent = s.ad + ' (' + s.kod + ')';
        sec.appendChild(o);
        return '<tr><td>' + s.id + '</td><td>' + esc(s.kod) + '</td><td>' + esc(s.ad) + '</td><td>' +
          (s.aktif == 1 ? 'aktif' : 'pasif') + '</td></tr>';
      }).join('') || '<tr><td colspan="4">Kayıt yok.</td></tr>');
    }).catch(function () { uyari('Liste yüklenemedi.'); });
    document.getElementById('sehir-icerik-formu').addEventListener('submit', function (o) {
      o.preventDefault();
      var id = document.getElementById('se-sehir').value;
      if (!id) { uyari('Şehir seçin.'); return; }
      api('/admin/sehirler/' + id + '/icerik', {
        yontem: 'PUT',
        govde: {
          dil_kodu: document.getElementById('se-dil').value,
          slug: document.getElementById('se-slug').value,
          seo_baslik: document.getElementById('se-baslik').value,
          seo_aciklama: document.getElementById('se-aciklama').value,
          icerik: document.getElementById('se-icerik').value
        }
      }).then(function (g) {
        document.getElementById('sehir-sonuc').innerHTML = g.success
          ? '<div class="uyari uyari-basarili">Kaydedildi.</div>'
          : '<div class="uyari uyari-hata">Kaydet başarısız.</div>';
      }).catch(function () { uyari('Kaydet başarısız.'); });
    });
  }

  /* ---------- Atölye ---------- */

  if (SAYFA === 'atolye') {
    var yukleAtolye = function () {
      api('/admin/atolye').then(function (g) {
        var kokApi = API.replace('/api/v1', '');
        document.getElementById('atolye-alan').innerHTML = ((g.data || []).map(function (f) {
          var ad = String(f.dosya_yolu || '').split('/').pop();
          return '<div class="card"><img src="' + kokApi + '/api/v1/dosyalar/atolye/' + encodeURIComponent(ad) +
            '" alt="' + esc(f.baslik) + '" loading="lazy"><div class="card-govde"><p>' + esc(f.baslik) + '</p>' +
            '<button data-sil="' + f.id + '">Sil</button></div></div>';
        }).join('') || '<p>Kayıt yok.</p>');
        document.querySelectorAll('[data-sil]').forEach(function (b) {
          b.addEventListener('click', function () {
            if (!confirm('Silinsin mi? (dosya + kayıt)')) return;
            api('/admin/atolye/' + b.getAttribute('data-sil'), { yontem: 'DELETE' }).then(yukleAtolye);
          });
        });
      }).catch(function () { uyari('Liste yüklenemedi.'); });
    };
    document.getElementById('atolye-formu').addEventListener('submit', function (o) {
      o.preventDefault();
      var fd = new FormData();
      var dosya = document.getElementById('a-dosya').files[0];
      if (!dosya) { uyari('Dosya seçin.'); return; }
      fd.append('file', dosya);
      fd.append('baslik', document.getElementById('a-baslik').value);
      fd.append('aciklama', document.getElementById('a-aciklama').value);
      api('/admin/atolye', { yontem: 'POST', formData: fd }).then(function (g) {
        if (!g.success) { uyari('Yükleme başarısız.'); return; }
        document.getElementById('atolye-sonuc').innerHTML = '<div class="uyari uyari-basarili">Yüklendi.</div>';
        yukleAtolye();
      }).catch(function () { uyari('Yükleme başarısız.'); });
    });
    yukleAtolye();
  }

  /* ---------- Sanal tur / Dönüşüm / Video / Sıcaklık ---------- */

  if (SAYFA === 'sanal-turlar') {
    var yukleTur = function () {
      api('/admin/sanal-tur').then(function (g) {
        document.getElementById('st-satirlar').innerHTML = ((g.data || []).map(function (t) {
          return '<tr><td>' + t.id + '</td><td>' + esc(t.baslik) + '</td><td>' +
            '<button data-sil="' + t.id + '">Sil</button></td></tr>';
        }).join('') || '<tr><td colspan="3">Kayıt yok.</td></tr>');
        document.querySelectorAll('[data-sil]').forEach(function (b) {
          b.addEventListener('click', function () {
            if (!confirm('Silinsin mi?')) return;
            api('/admin/sanal-tur/' + b.getAttribute('data-sil'), { yontem: 'DELETE' }).then(yukleTur);
          });
        });
      }).catch(function () { uyari('Liste yüklenemedi.'); });
    };
    document.getElementById('st-formu').addEventListener('submit', function (o) {
      o.preventDefault();
      if (!document.getElementById('st-url').value.trim()) { uyari('Embed URL zorunlu.'); return; }
      api('/admin/sanal-tur', {
        yontem: 'POST',
        govde: { baslik: document.getElementById('st-baslik').value, aciklama: '', embed_url: document.getElementById('st-url').value }
      }).then(function (g) {
        if (!g.success) { uyari('Kaydet başarısız.'); return; }
        document.getElementById('st-formu').reset();
        yukleTur();
      }).catch(function () { uyari('Kaydet başarısız.'); });
    });
    yukleTur();
  }

  if (SAYFA === 'donusumler') {
    var yukleDonusum = function () {
      api('/admin/donusumler').then(function (g) {
        document.getElementById('dn-satirlar').innerHTML = ((g.data || []).map(function (d) {
          return '<tr><td>' + d.id + '</td><td>' + esc(d.baslik) + '</td><td>' +
            '<button data-sil="' + d.id + '">Sil</button></td></tr>';
        }).join('') || '<tr><td colspan="3">Kayıt yok.</td></tr>');
        document.querySelectorAll('[data-sil]').forEach(function (b) {
          b.addEventListener('click', function () {
            if (!confirm('Silinsin mi?')) return;
            api('/admin/donusumler/' + b.getAttribute('data-sil'), { yontem: 'DELETE' }).then(yukleDonusum);
          });
        });
      }).catch(function () { uyari('Liste yüklenemedi.'); });
    };
    document.getElementById('dn-formu').addEventListener('submit', function (o) {
      o.preventDefault();
      var o1 = document.getElementById('dn-once').files[0];
      var o2 = document.getElementById('dn-sonra').files[0];
      if (!o1 || !o2) { uyari('İki görsel de gerekli.'); return; }
      var yukleDosya = function (dosya) {
        var fd = new FormData();
        fd.append('file', dosya);
        fd.append('hedef', 'donusum');
        fd.append('hedef_id', '0');
        return api('/admin/upload', { yontem: 'POST', formData: fd }).then(function (g) {
          if (!g.success) throw new Error('yukleme');
          return g.data.dosya_yolu;
        });
      };
      yukleDosya(o1).then(function (yol1) {
        return yukleDosya(o2).then(function (yol2) {
          return api('/admin/donusumler', {
            yontem: 'POST',
            govde: { baslik: document.getElementById('dn-baslik').value, oncesi_gorsel: yol1, sonrasi_gorsel: yol2 }
          });
        });
      }).then(function (g) {
        if (!g || !g.success) { uyari('Kaydet başarısız.'); return; }
        yukleDonusum();
      }).catch(function () { uyari('Kaydet başarısız.'); });
    });
    yukleDonusum();
  }

  if (SAYFA === 'video-referanslar') {
    var yukleVideo = function () {
      api('/admin/video-referanslar').then(function (g) {
        document.getElementById('vr-satirlar').innerHTML = ((g.data || []).map(function (v) {
          return '<tr><td>' + v.id + '</td><td>' + esc(v.musteri_adi) + '</td><td>' +
            '<button data-sil="' + v.id + '">Sil</button></td></tr>';
        }).join('') || '<tr><td colspan="3">Kayıt yok.</td></tr>');
        document.querySelectorAll('[data-sil]').forEach(function (b) {
          b.addEventListener('click', function () {
            if (!confirm('Silinsin mi?')) return;
            api('/admin/video-referanslar/' + b.getAttribute('data-sil'), { yontem: 'DELETE' }).then(yukleVideo);
          });
        });
      }).catch(function () { uyari('Liste yüklenemedi.'); });
    };
    document.getElementById('vr-formu').addEventListener('submit', function (o) {
      o.preventDefault();
      api('/admin/video-referanslar', {
        yontem: 'POST',
        govde: { musteri_adi: document.getElementById('vr-ad').value, video_url: document.getElementById('vr-url').value }
      }).then(function (g) {
        if (!g.success) { uyari('Kaydet başarısız.'); return; }
        document.getElementById('vr-formu').reset();
        yukleVideo();
      }).catch(function () { uyari('Kaydet başarısız.'); });
    });
    yukleVideo();
  }

  if (SAYFA === 'sicaklik-formulu') {
    document.getElementById('sicaklik-formu-admin').addEventListener('submit', function (o) {
      o.preventDefault();
      var ham = document.getElementById('s-json').value;
      try { JSON.parse(ham); } catch (e) { uyari('Geçersiz JSON.'); return; }
      api('/admin/ayarlar', { yontem: 'PUT', govde: { degerler: { sicaklik_formulu_katsayilari: ham } } }).then(function (g) {
        document.getElementById('sicaklik-sonuc').innerHTML = g.success
          ? '<div class="uyari uyari-basarili">Kaydedildi.</div>'
          : '<div class="uyari uyari-hata">Kaydet başarısız.</div>';
      }).catch(function () { uyari('Kaydet başarısız.'); });
    });
  }

  /* ---------- Yorum moderasyonu ---------- */

  if (SAYFA === 'yorumlar') {
    var yukleYorum = function () {
      var durum = document.getElementById('yorum-durum').value;
      api('/admin/yorumlar?durum=' + encodeURIComponent(durum) + '&limit=50').then(function (g) {
        document.getElementById('yorum-satirlar').innerHTML = ((g.data || []).map(function (y) {
          return '<tr><td>' + y.id + '</td><td>' + esc(y.musteri_adi) + '<br><small>' + esc(y.eposta || '') + '</small></td>' +
            '<td>' + y.puan + '</td><td>' + esc(String(y.yorum || '').slice(0, 80)) + '</td><td>' + esc(y.durum) + '</td>' +
            '<td>' + (y.one_cikan == 1 ? 'evet' : 'hayır') + '</td><td>' +
            '<button data-onayla="' + y.id + '">Onayla</button> ' +
            '<button data-reddet="' + y.id + '">Reddet</button> ' +
            '<button data-one-cikan="' + y.id + '">Öne Çıkar</button> ' +
            '<button data-sil="' + y.id + '">Sil</button></td></tr>';
        }).join('') || '<tr><td colspan="7">Kuyruk boş.</td></tr>');
        document.querySelectorAll('[data-onayla]').forEach(function (b) {
          b.addEventListener('click', function () {
            api('/admin/yorumlar/' + b.getAttribute('data-onayla') + '/onayla', { yontem: 'PUT', govde: {} }).then(yukleYorum);
          });
        });
        document.querySelectorAll('[data-reddet]').forEach(function (b) {
          b.addEventListener('click', function () {
            var sebep = prompt('Red sebebi (zorunlu):', '');
            if (sebep === null || sebep.trim() === '') return;
            api('/admin/yorumlar/' + b.getAttribute('data-reddet') + '/reddet', { yontem: 'PUT', govde: { red_sebebi: sebep } }).then(yukleYorum);
          });
        });
        document.querySelectorAll('[data-one-cikan]').forEach(function (b) {
          b.addEventListener('click', function () {
            api('/admin/yorumlar/' + b.getAttribute('data-one-cikan') + '/one-cikan', { yontem: 'PUT', govde: {} }).then(yukleYorum);
          });
        });
        document.querySelectorAll('[data-sil]').forEach(function (b) {
          b.addEventListener('click', function () {
            if (!confirm('Silinsin mi? (soft delete)')) return;
            api('/admin/yorumlar/' + b.getAttribute('data-sil'), { yontem: 'DELETE' }).then(yukleYorum);
          });
        });
      }).catch(function () { uyari('Kuyruk yüklenemedi.'); });
    };
    document.getElementById('yorum-filtrele').addEventListener('click', yukleYorum);
    yukleYorum();
  }

  var cikis = document.getElementById('cikis');
  if (cikis) {
    cikis.addEventListener('click', function () {
      try { localStorage.removeItem('kamelya_token'); localStorage.removeItem('kamelya_csrf'); } catch (e) {}
      window.location.href = '/index.php';
    });
  }

  /* ---------- Takvim ---------- */

  var GECIS = {
    'bekliyor': ['devam_ediyor', 'iptal'],
    'devam_ediyor': ['tamamlandi', 'iptal'],
    'tamamlandi': [],
    'iptal': ['bekliyor']
  };

  function isEtiket(is) {
    var saat = String(is.randevu_tarihi || '').slice(11, 16);
    var span = document.createElement('span');
    span.className = 'is is-' + (is.tur || 'gorusme');
    span.textContent = saat + ' ' + (is.ad_soyad || '').slice(0, 14);
    span.setAttribute('data-is', JSON.stringify(is));
    return span;
  }

  if (SAYFA === 'takvim') {
    var yil = new Date().getFullYear(), ay = new Date().getMonth() + 1;
    var AY_ADLARI = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];

    var ekipYukle = function () {
      api('/admin/takvim/ekip-uyeleri').then(function (g) {
        var sec = document.getElementById('takvim-ekip');
        var rsec = document.getElementById('r-ekip');
        (g.data || []).forEach(function (e) {
          [sec, rsec].forEach(function (s) {
            if (!s) return;
            var o = document.createElement('option');
            o.value = e.id; o.textContent = e.ad_soyad;
            s.appendChild(o);
          });
        });
      });
    };

    var takvimYukle = function () {
      var q = '?yil=' + yil + '&ay=' + ay +
        '&tur=' + encodeURIComponent(document.getElementById('takvim-tur').value) +
        '&ekip=' + encodeURIComponent(document.getElementById('takvim-ekip').value) +
        '&sehir=' + encodeURIComponent(document.getElementById('takvim-sehir').value);
      api('/admin/takvim/aylik' + q).then(function (g) {
        var d = g.data || {};
        document.getElementById('takvim-baslik').textContent = AY_ADLARI[d.ay] + ' ' + d.yil;
        var alan = document.getElementById('takvim-alan');
        alan.innerHTML = '';
        var bugun = new Date().toISOString().slice(0, 10);
        for (var i = 1; i < d.ilk_gun; i++) {
          alan.appendChild(document.createElement('div'));
        }
        for (var gun = 1; gun <= d.gun_sayisi; gun++) {
          (function (gunNo) {
            var tarih = d.yil + '-' + String(d.ay).padStart(2, '0') + '-' + String(gunNo).padStart(2, '0');
            var hucre = document.createElement('div');
            hucre.className = 'takvim-gun' + (tarih === bugun ? ' bugun' : '');
            var baslik = document.createElement('div');
            baslik.className = 'numara';
            baslik.textContent = gunNo;
            hucre.appendChild(baslik);
            var isler = ((d.gunler || {})[tarih]) || [];
            isler.slice(0, 3).forEach(function (is) { hucre.appendChild(isEtiket(is)); });
            if (isler.length > 3) {
              var daha = document.createElement('span');
              daha.textContent = '+' + (isler.length - 3) + ' daha';
              hucre.appendChild(daha);
            }
            hucre.addEventListener('click', function () { gunAc(tarih); });
            alan.appendChild(hucre);
          })(gun);
        }
        alan.querySelectorAll('[data-is]').forEach(function (el) {
          el.addEventListener('click', function (o) {
            o.stopPropagation();
            isAc(JSON.parse(el.getAttribute('data-is')));
          });
        });
      }).catch(function () { uyari('Takvim yüklenemedi.'); });

      api('/admin/takvim/ekip-yuku?baslangic=' + yil + '-' + String(ay).padStart(2, '0') + '-01&bitis=' + yil + '-' + String(ay).padStart(2, '0') + '-28')
        .then(function (g) {
          document.getElementById('ekip-yuku').innerHTML = '<ul>' + ((g.data || []).map(function (e) {
            var cls = e.asiri_yuk ? ' class="asiri"' : '';
            return '<li' + cls + '>' + esc(e.ekip_adi || ('#' + e.ekip_uyesi_id)) + ': ' + e.is_sayisi +
              ' iş, ' + e.toplam_saat + ' sa (haftalık ~' + e.haftalik_saat + ' sa)' +
              (e.asiri_yuk ? ' — AŞIRI YÜK' : '') + '</li>';
          }).join('') || '<li>Yok.</li>') + '</ul>';
        }).catch(function () {});
    };

    var gunAc = function (tarih) {
      document.getElementById('gun-modal-baslik').textContent = tarih;
      document.getElementById('gun-modal').hidden = false;
      api('/admin/takvim/gun/' + tarih).then(function (g) {
        document.getElementById('gun-modal-liste').innerHTML = ((g.data || []).map(function (is) {
          return '<p><span class="is is-' + (is.tur || 'gorusme') + '">' + String(is.randevu_tarihi).slice(11, 16) +
            ' ' + esc(is.ad_soyad || '') + ' (' + esc(is.durum || '') + ')</span> ' +
            '<button data-detay=\'' + JSON.stringify(is).replace(/'/g, '&#39;') + '\'>Detay</button></p>';
        }).join('') || '<p>İş yok.</p>');
        document.querySelectorAll('[data-detay]').forEach(function (b) {
          b.addEventListener('click', function () { isAc(JSON.parse(b.getAttribute('data-detay'))); });
        });
      });
      document.getElementById('gun-modal-yeni').onclick = function () {
        document.getElementById('gun-modal').hidden = true;
        randevuFormAc(null, tarih);
      };
    };

    var isAc = function (is) {
      document.getElementById('is-modal-baslik').textContent = (is.ad_soyad || '') + ' — ' + (is.tur || '');
      document.getElementById('is-modal-detay').innerHTML =
        '<p>' + esc(is.randevu_tarihi || '') + ' · ' + esc(is.durum || '') + ' · ' + esc(is.telefon || '') + '</p>' +
        '<p>Ekip: ' + esc(is.ekip_adi || '-') + ' · Öncelik: ' + esc(is.oncelik || 'normal') + '</p>' +
        '<p>' + esc(is.adres || '') + '</p>';
      var gecerli = GECIS[is.durum] || [];
      document.getElementById('is-modal-durum').innerHTML = gecerli.map(function (d) {
        return '<button data-durum="' + d + '" data-id="' + is.id + '">' + d + '</button> ';
      }).join('') + '<button data-duzenle="' + is.id + '">Düzenle</button>';
      document.querySelectorAll('[data-durum]').forEach(function (b) {
        b.addEventListener('click', function () {
          var govde = { durum: b.getAttribute('data-durum') };
          if (govde.durum === 'tamamlandi') {
            var not = prompt('Tamamlanma notu (opsiyonel):', '');
            if (not !== null) govde.tamamlanma_notu = not;
          }
          api('/admin/takvim/randevu/' + b.getAttribute('data-id') + '/durum', { yontem: 'PATCH', govde: govde })
            .then(function (g) {
              if (!g.success) { uyari('Durum değişmedi.'); return; }
              document.getElementById('is-modal').hidden = true;
              takvimYukle();
            });
        });
      });
      var dz = document.querySelector('[data-duzenle]');
      if (dz) dz.addEventListener('click', function () { randevuFormAc(is.id, null); });
      document.getElementById('is-modal').hidden = false;
    };

    var randevuFormAc = function (id, tarih) {
      document.getElementById('r-id').value = id || '';
      if (tarih) document.getElementById('r-tarih').value = tarih + 'T10:00';
      document.getElementById('is-modal').hidden = false;
    };

    document.getElementById('randevu-formu').addEventListener('submit', function (o) {
      o.preventDefault();
      var tur = 'gorusme';
      document.querySelectorAll('[name=tur]').forEach(function (r) { if (r.checked) tur = r.value; });
      var veri = {
        tur: tur,
        randevu_tarihi: document.getElementById('r-tarih').value.replace('T', ' '),
        sure_dakika: parseInt(document.getElementById('r-sure').value, 10),
        ad_soyad: document.getElementById('r-ad').value,
        telefon: document.getElementById('r-tel').value,
        adres: document.getElementById('r-adres').value,
        ekip_uyesi_id: document.getElementById('r-ekip').value || null,
        oncelik: document.getElementById('r-oncelik').value,
        notlar: document.getElementById('r-not').value
      };
      var id = document.getElementById('r-id').value;
      api(id ? '/admin/takvim/randevu/' + id : '/admin/takvim/randevu', { yontem: id ? 'PUT' : 'POST', govde: veri })
        .then(function (g) {
          if (!g.success) { uyari('Kaydet başarısız.'); return; }
          document.getElementById('is-modal').hidden = true;
          takvimYukle();
        }).catch(function () { uyari('Kaydet başarısız.'); });
    });

    document.getElementById('takvim-onceki').addEventListener('click', function () {
      ay--; if (ay < 1) { ay = 12; yil--; } takvimYukle();
    });
    document.getElementById('takvim-sonraki').addEventListener('click', function () {
      ay++; if (ay > 12) { ay = 1; yil++; } takvimYukle();
    });
    document.getElementById('takvim-bugun').addEventListener('click', function () {
      var s = new Date(); yil = s.getFullYear(); ay = s.getMonth() + 1; takvimYukle();
    });
    ['takvim-tur', 'takvim-ekip', 'takvim-sehir'].forEach(function (id) {
      document.getElementById(id).addEventListener('change', takvimYukle);
    });
    document.getElementById('gun-modal-kapat').addEventListener('click', function () {
      document.getElementById('gun-modal').hidden = true;
    });
    document.getElementById('is-modal-kapat').addEventListener('click', function () {
      document.getElementById('is-modal').hidden = true;
    });

    ekipYukle();
    takvimYukle();
  }

  /* ---------- Dashboard widget'ları ---------- */

  if (SAYFA === 'dashboard') {
    api('/admin/takvim/yaklasan?gun=7').then(function (g) {
      var oncelik = { montaj: 0, kesif: 1, uretim: 2, teslim: 3, gorusme: 4 };
      var isler = (g.data || []).slice().sort(function (a, b) {
        return (oncelik[a.tur] ?? 9) - (oncelik[b.tur] ?? 9);
      });
      document.getElementById('widget-hafta').innerHTML = '<ul>' + (isler.map(function (is) {
        return '<li><a href="/panel.php?sayfa=takvim">' + esc(String(is.randevu_tarihi).slice(0, 16)) +
          ' · ' + esc(is.tur || '') + ' · ' + esc(is.ad_soyad || '') + ' · ' + esc(is.ekip_adi || '-') + '</a></li>';
      }).join('') || '<li>Yok.</li>') + '</ul>';
    }).catch(function () {});

    var bugun = new Date().toISOString().slice(0, 10);
    api('/admin/takvim/gun/' + bugun).then(function (g) {
      document.getElementById('widget-bugun').innerHTML = '<ul>' + (((g.data || []).map(function (is) {
        var gecerli = (GECIS[is.durum] || []).map(function (d) {
          return '<button data-wdurum="' + d + '" data-wid="' + is.id + '">' + d + '</button>';
        }).join(' ');
        return '<li>' + esc(String(is.randevu_tarihi).slice(11, 16)) + ' ' + esc(is.ad_soyad || '') +
          ' (' + esc(is.durum || '') + ') ' + gecerli + '</li>';
      }).join('')) || '<li>Yok.</li>') + '</ul>';
      document.querySelectorAll('[data-wdurum]').forEach(function (b) {
        b.addEventListener('click', function () {
          api('/admin/takvim/randevu/' + b.getAttribute('data-wid') + '/durum',
            { yontem: 'PATCH', govde: { durum: b.getAttribute('data-wdurum') } })
            .then(function () { window.location.reload(); });
        });
      });
    }).catch(function () {});
  }
/* ---------- Audit logları ---------- */
  if (SAYFA === 'audit-logs') {
    var sayfa = 1, limit = 20;
    var yukleAudit = function () {
      var q = '?page=' + sayfa + '&per_page=' + limit;
      var islem = document.getElementById('audit-islem').value;
      var varlik = document.getElementById('audit-varlik').value;
      if (islem) q += '&islem=' + encodeURIComponent(islem);
      if (varlik) q += '&varlik_tipi=' + encodeURIComponent(varlik);
      api('/admin/audit-logs' + q).then(function (g) {
        document.getElementById('audit-satirlar').innerHTML = ((g.data || []).map(function (a) {
          return '<tr><td>' + a.id + '</td><td>' + esc(a.created_at) + '</td><td>' + esc(a.kullanici_id || '-') + '</td><td>' + esc(a.islem) + '</td><td>' + esc(a.varlik_tipi) + '</td><td>' + esc(a.varlik_id || '-') + '</td><td>' + esc(a.ip_adresi) + '</td><td><pre class="json-mini">' + esc(a.eski_deger ? JSON.stringify(JSON.parse(a.eski_deger), null, 2) : '-') + '</pre></td><td><pre class="json-mini">' + esc(a.yeni_deger ? JSON.stringify(JSON.parse(a.yeni_deger), null, 2) : '-') + '</pre></td></tr>';
        }).join('') || '<tr><td colspan="9">Kayıt yok.</td></tr>');
        var toplam = g.meta && g.meta.total ? g.meta.total : 0;
        var toplamSayfa = Math.ceil(toplam / limit);
        var sayfalama = document.getElementById('audit-sayfalama');
        sayfalama.innerHTML = '';
        if (toplamSayfa > 1) {
          for (var i = 1; i <= toplamSayfa; i++) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = i;
            if (i === sayfa) btn.className = 'aktif';
            btn.addEventListener('click', function (p) { return function () { sayfa = p; yukleAudit(); }; }(i));
            sayfalama.appendChild(btn);
          }
        }
      }).catch(function () { uyari('Liste yüklenemedi.'); });
    };
    document.getElementById('audit-filtrele').addEventListener('click', function () {
      sayfa = 1; limit = parseInt(document.getElementById('audit-limit').value, 10); yukleAudit();
    });
    document.getElementById('audit-limit').addEventListener('change', function () { limit = parseInt(this.value, 10); sayfa = 1; yukleAudit(); });
    api('/admin/audit-logs?per_page=100').then(function (g) {
      var tipler = {};
      (g.data || []).forEach(function (a) { tipler[a.varlik_tipi] = true; });
      var sel = document.getElementById('audit-varlik');
      Object.keys(tipler).sort().forEach(function (t) {
        var o = document.createElement('option');
        o.value = t; o.textContent = t; sel.appendChild(o);
      });
    });
    yukleAudit();
  }
})();
