---
name: database-administrator
description: >-
  Veritabanı standartları — offline-first + sync queue, şifreleme
  (SQLCipher/EncryptedSharedPreferences), dağıtık V2 (PostgreSQL/Redis),
  veri altyapısı. Kullanım: DB şeması, migration, sync, şifreleme işlerinde,
  F2/F3 fazlarında, "veritabanı", "database", "sql", "migration",
  "room", "postgres" geçtiğinde.
---

# Veritabanı Yöneticisi Skill

Kaynak: APP-FABRIKA `.cursor/rules/02-architect.mdc` (Katman 7–11, 23–24) + `docs/03-STANDARDS/SECURITY.md` ilkeleri (opencode'a uyarlandı)

## Repodan alınan standartlar

### Mimari katmanlar (33 katman modelinden)

- **KATMAN 7–8** — İş Mantığı + Veri: Domain, Use Cases, Repository, Sync Layer
- **KATMAN 9** — Offline-First (V1 zorunlu): Local First, Sync Queue, Offline Search/Actions
- **KATMAN 10–11** — Cache + Ağ: Multi-Level Cache, HTTP/3, Circuit Breaker
- **KATMAN 23–24** — Dağıtık Sistemler + Veri Altyapısı (V2): Microservices, PostgreSQL, Redis

### Güvenlik ilkeleri

- Şifreleme: SQLCipher, EncryptedSharedPreferences (mobil referans)
- Gizli anahtar/credential asla loglanmaz, asla commit edilmez
- Katman 32 hedefi: 0 Veri Kaybı

### Veri akışı

- V1 → V2 geçiş planı dokümante edilir (offline-first JSON → REST API + sync)
- Migration'lar geri alınabilir olmalı; şema değişikliği ADR kaydı ile

## Çalışma kuralları

- Şema/migration değişikliği öncesi bağımlılık kilidine uy (manifest → kod sırası).
- Sync queue tasarımı offline-first ilkesiyle uyumlu olmalı.
- Şema kararları mimari denetimine (L1: CEC, L2: CAO) tabidir.

## MySQL 8+ (Kamelya standardı — 2026-09-20)

- Charset/engine: **utf8mb4** + InnoDB zorunlu; tüm ilişkiler FOREIGN KEY ile (`ON DELETE/UPDATE` davranışı açıkça tanımlı).
- Indexing: PK + FK + sık sorgu sütunları; bileşik indexlerde sütun sırası (eşitlik önce, aralık sonra); her yeni index `EXPLAIN` ile doğrulanır.
- Migration: geri alınabilir (`up`/`down`) + ADR kaydı; Seeding: deterministik, **production'da çalıştırılmaz**.
- PHP/MySQL backend'in tamamı için: `standards/web/PHP_MVC_API.md` (Veritabanı bölümü + genel standart).

## Standart boşluğu (kısmen kapatıldı — 2026-09-20)

- **MySQL:** APP-FABRIKA repoda MySQL'e özgü şablon, scaffold veya kural **bulunmuyordu** (repo raporu: "Repoda Laravel scaffold, PHP, MySQL template bulunamadı"). Geliştirici talebiyle bu boşluk kapatıldı [EK-20260920]: MySQL 8+ standardı `standards/web/PHP_MVC_API.md` (Veritabanı bölümü) olarak oluşturuldu ve bu skill'e entegre edildi. Kaynağı APP-FABRIKA değil, geliştirici talebidir.
- **Veritabanı erişim MCP'si (hala açık):** `opencode.json` içinde tanımlı değil; veritabanı katmanı devreye girdiğinde (F2+) uygun bir MySQL/PostgreSQL MCP sunucusu seçilip **geliştirici onayıyla** tanımlanmalıdır.
