---
version: trustng-clean-control-plane
name: TRUST-NG DNS Control
source: m2c/DESIGN.md
colors:
  primary: '#006D36'
  on-primary: '#FFFFFF'
  primary-container: '#4ADE80'
  on-primary-container: '#005E2D'
  accent: '#BBF7D0'
  background: '#F9F9FF'
  surface-lowest: '#FFFFFF'
  surface-low: '#F1F3FF'
  surface: '#E9EDFF'
  surface-high: '#E1E8FD'
  text-primary: '#111827'
  text-secondary: '#4B5563'
  border: '#E5E7EB'
  outline: '#6D7B6D'
  success: '#006D36'
  success-container: '#B4F0C9'
  warning: '#B45309'
  warning-container: '#FDE68A'
  critical: '#BA1A1A'
  critical-container: '#FFDAD6'
  info: '#31694B'
  info-container: '#97D1AC'
typography:
  headline:
    fontFamily: Inter
    fontWeight: 600
  body:
    fontFamily: Space Grotesk
    fontWeight: 400
  label:
    fontFamily: JetBrains Mono
    fontWeight: 600
spacing:
  base: 8px
  gap: 16px
  card-padding: 24px
  page-padding: 24px
  sidebar-expanded: 232px
  sidebar-collapsed: 72px
  topbar: 64px
rounded:
  control: 8px
  card: 8px
  modal: 12px
  pill: 9999px
---

# TRUST-NG Clean Control Plane

## Prinsip

1. Light-first, tenang, dan padat informasi.
2. Data DNS menjadi fokus; dekorasi tidak boleh mengganggu pemindaian operasional.
3. Warna selalu disertai teks atau ikon yang memiliki label.
4. Semua nilai visual berasal dari token, bukan nilai acak per halaman.
5. Aksi tulis menampilkan hasil yang jelas; aksi berisiko membutuhkan konfirmasi.
6. Seluruh fungsi, field POST, endpoint, dan alur konfigurasi DNS tetap kompatibel.

## Bahasa Visual

TRUST-NG menggunakan bahasa visual M2C yang juga dipakai Ingat.in: permukaan terang bertingkat, aksen hijau fungsional, border tipis, tipografi terstruktur, dan ruang yang cukup. Hindari neon, gradient dekoratif, background grid, glow, dan glassmorphism berlebihan.

## Tema

Tersedia Light, Dark, dan System. Light adalah palet sumber. Dark memetakan ulang token semantik, bukan membalik warna. Pilihan disimpan di browser dan perubahan preferensi sistem diterapkan secara langsung ketika mode System aktif.

## Tipografi

- Inter: heading, judul halaman, angka metrik utama.
- Space Grotesk: body, navigasi, form, tabel, tombol.
- JetBrains Mono: IP, domain, port, timestamp, nilai resolver, dan label teknis.

Skala utama: 32px headline besar, 24px judul halaman, 20px judul section, 16px body utama, 14px body ringkas, 12px label, dan 10px kicker.

## App Shell

- Sidebar desktop 232px dan rail 72px ketika diciutkan.
- Topbar sticky 64px.
- Konten maksimum 1480px dengan padding 24px.
- Di bawah 821px, sidebar berubah menjadi drawer dan tersedia bottom navigation untuk tujuan utama.
- Status resolver, tema, identitas sesi, dan logout konsisten pada shell.

## Komponen

### Page Header

Kicker uppercase, judul, deskripsi ringkas, dan action group yang dapat membungkus pada layar kecil.

### Card

Surface putih/lowest, border 1px, radius 8px, shadow rendah. Padding standar 24px dan 16px untuk panel padat.

### Form

Label berada di atas kontrol. Tinggi kontrol minimum 44px. Hint dan error memiliki area tersendiri. Field teknis memakai font mono. Tombol primer hijau gelap dengan teks putih; aksi sekunder menggunakan surface dan border; aksi destruktif menggunakan critical.

### Table

Header tonal, divider tipis, hover halus, wrapper overflow untuk data lebar. IP, domain, port, TTL, dan waktu menggunakan font mono.

### Badge

Bentuk pill dengan label teks. Tone: success, warning, critical, info, dan neutral.

### Dialog dan Toast

Dialog konfirmasi memakai backdrop gelap, judul ringkas, konsekuensi, tombol Batal dan aksi. Toast berada di kanan atas, memiliki ikon, judul, detail, dan tombol tutup.

### States

Loading menampilkan spinner dan teks. Empty state menampilkan ikon, judul, deskripsi, dan optional action. Error state memakai critical container dan optional retry. System action screen memakai progress, hasil, serta tujuan berikutnya yang jelas.

### Login

Desktop memakai dua panel: panel brand inverse di kiri dan form terfokus di kanan. Mobile ditumpuk dengan panel brand pendek. Error, setup pertama, lockout, busy state, dan trust signals harus terlihat jelas. Animasi dekoratif wajib menghormati reduced motion.

## Dashboard

Metrik utama memakai grid empat kolom, status layanan dan resource memakai kartu padat, chart memakai token tema, dan analytics turun menjadi satu kolom pada tablet. Nilai yang tidak tersedia ditampilkan sebagai em dash dengan penjelasan.

## Responsive

- 1280px+: grid penuh.
- 821–1279px: sidebar rail dan grid 2 kolom.
- 561–820px: drawer, bottom navigation, card 1–2 kolom.
- <=560px: semua form dan metrik menjadi satu kolom; action memenuhi lebar bila perlu.

## Accessibility

- Focus ring terlihat pada seluruh kontrol.
- Target sentuh minimum 44px.
- Icon-only control memiliki accessible name.
- Drawer, menu tema, dialog, dan toast memakai state ARIA yang sesuai.
- Status tidak mengandalkan warna saja.
- `prefers-reduced-motion` menonaktifkan animasi non-esensial.
