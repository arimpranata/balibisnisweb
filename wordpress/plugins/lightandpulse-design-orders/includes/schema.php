<?php
/**
 * Field definitions. One schema drives the public order form, the admin
 * edit screen and the client portal summary.
 *
 * Field keys: key, label, type (text|textarea|email|tel|url|number|date|color|select|radio|checkboxes|file|repeater),
 * options, required, help, placeholder, width (half|third|full), multiple (file), accept (file),
 * columns, rows and add_label (repeater: a table the client fills row by row; each column
 * has key, label, type text|number|select, size, placeholder, options, required).
 * A group's 'types' limits it to some design types; no 'types' means every type.
 */
defined('ABSPATH') || exit;

function lpdo_design_types(): array
{
    return [
        'poster'        => ['label' => __('Poster', 'lightandpulse-design-orders'), 'desc' => __('Promosi, acara, informasi', 'lightandpulse-design-orders')],
        'menu'          => ['label' => __('Menu', 'lightandpulse-design-orders'), 'desc' => __('Restoran, kafe, bar, spa', 'lightandpulse-design-orders')],
        'flyer'         => ['label' => __('Flyer', 'lightandpulse-design-orders'), 'desc' => __('Brosur promo, cetak & digital', 'lightandpulse-design-orders')],
        'backdrop'      => ['label' => __('Backdrop', 'lightandpulse-design-orders'), 'desc' => __('Acara, photo booth, panggung', 'lightandpulse-design-orders')],
        'business_card' => ['label' => __('Kartu nama', 'lightandpulse-design-orders'), 'desc' => __('Untuk satu atau banyak orang', 'lightandpulse-design-orders')],
    ];
}

function lpdo_statuses(): array
{
    return [
        'new'      => __('Baru', 'lightandpulse-design-orders'),
        'review'   => __('Sedang ditinjau', 'lightandpulse-design-orders'),
        'design'   => __('Dalam proses desain', 'lightandpulse-design-orders'),
        'proof'    => __('Menunggu persetujuan', 'lightandpulse-design-orders'),
        'revision' => __('Revisi', 'lightandpulse-design-orders'),
        'approved' => __('Disetujui', 'lightandpulse-design-orders'),
        'done'     => __('Selesai', 'lightandpulse-design-orders'),
    ];
}

/** Client (business) details and branding. Stored on the client record. */
function lpdo_client_fields(): array
{
    $t = 'lightandpulse-design-orders';
    return [
        'contact' => [
            'title'  => __('Data bisnis & kontak', $t),
            'fields' => [
                ['key' => 'business_name', 'label' => __('Nama bisnis / brand', $t), 'type' => 'text', 'required' => true, 'width' => 'half'],
                ['key' => 'contact_name', 'label' => __('Nama kontak', $t), 'type' => 'text', 'required' => true, 'width' => 'half'],
                ['key' => 'whatsapp', 'label' => __('WhatsApp', $t), 'type' => 'tel', 'required' => true, 'width' => 'half', 'placeholder' => '0812…'],
                ['key' => 'email', 'label' => __('Email', $t), 'type' => 'email', 'required' => true, 'width' => 'half', 'help' => __('Link halaman klien dan file desain dikirim ke sini.', $t)],
                ['key' => 'instagram', 'label' => __('Instagram', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => '@namabisnis'],
                ['key' => 'website', 'label' => __('Website', $t), 'type' => 'url', 'width' => 'half', 'placeholder' => 'https://'],
                ['key' => 'address', 'label' => __('Alamat bisnis (opsional)', $t), 'type' => 'textarea', 'help' => __('Isi hanya bila alamat perlu dicetak di desain.', $t)],
            ],
        ],
        'brand' => [
            'title'  => __('Branding', $t),
            'intro'  => __('Halaman klien Anda akan memakai logo dan warna ini.', $t),
            'fields' => [
                ['key' => 'logo', 'label' => __('Logo', $t), 'type' => 'file', 'accept' => 'image/png,image/jpeg,image/webp,application/pdf', 'help' => __('PNG transparan atau PDF paling baik. Maks. 10 MB.', $t)],
                ['key' => 'color_primary', 'label' => __('Warna utama', $t), 'type' => 'color', 'width' => 'third', 'default' => '#1f2937'],
                ['key' => 'color_secondary', 'label' => __('Warna kedua', $t), 'type' => 'color', 'width' => 'third', 'default' => '#f5f5f4'],
                ['key' => 'color_accent', 'label' => __('Warna aksen', $t), 'type' => 'color', 'width' => 'third', 'default' => '#ff6a3d'],
                ['key' => 'brand_font', 'label' => __('Font brand (bila ada)', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. Montserrat, Playfair Display', $t)],
                ['key' => 'tagline', 'label' => __('Tagline', $t), 'type' => 'text', 'width' => 'half'],
                ['key' => 'brand_notes', 'label' => __('Catatan brand', $t), 'type' => 'textarea', 'placeholder' => __('Kepribadian brand, hal yang wajib atau dilarang, panduan brand yang sudah ada…', $t)],
            ],
        ],
    ];
}

/** Order details. Stored on each order. */
function lpdo_order_groups(): array
{
    $t = 'lightandpulse-design-orders';
    $yesNo = ['yes' => __('Ya', $t), 'no' => __('Tidak', $t)];
    $printQty = fn(string $key) => ['key' => $key, 'label' => __('Jumlah cetak', $t), 'type' => 'number', 'width' => 'half', 'help' => __('Kosongkan bila hanya butuh file desain.', $t)];
    $custom = fn(string $key) => ['key' => $key, 'label' => __('Ukuran custom', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('lebar × tinggi, mis. 60 × 90 cm', $t)];

    return [
        'project' => [
            'title'  => __('Tentang proyek', $t),
            'fields' => [
                ['key' => 'title', 'label' => __('Judul proyek', $t), 'type' => 'text', 'required' => true, 'placeholder' => __('mis. Menu baru restoran, musim hujan 2026', $t)],
                ['key' => 'purpose', 'label' => __('Tujuan desain', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'promo' => __('Promosi / penjualan', $t), 'event' => __('Acara / event', $t), 'opening' => __('Grand opening', $t),
                    'menu_update' => __('Menu baru / perubahan harga', $t), 'branding' => __('Branding umum', $t), 'other' => __('Lainnya', $t),
                ]],
                ['key' => 'audience', 'label' => __('Target audiens', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. turis keluarga, warga lokal muda', $t)],
                ['key' => 'languages', 'label' => __('Bahasa di desain', $t), 'type' => 'checkboxes', 'options' => ['id' => __('Indonesia', $t), 'en' => __('English', $t), 'other' => __('Lainnya', $t)]],
                ['key' => 'languages_other', 'label' => __('Bahasa lain', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. 中文, Русский', $t)],
            ],
        ],

        // ---------------------------------------------------------- poster
        'poster' => [
            'title' => __('Ukuran & format poster', $t), 'types' => ['poster'],
            'fields' => [
                ['key' => 'poster_size', 'label' => __('Ukuran', $t), 'type' => 'select', 'width' => 'half', 'required' => true, 'options' => [
                    'a4' => 'A4 (21 × 29,7 cm)', 'a3' => 'A3 (29,7 × 42 cm)', 'a2' => 'A2 (42 × 59,4 cm)', 'a1' => 'A1 (59,4 × 84,1 cm)', 'a0' => 'A0 (84,1 × 118,9 cm)',
                    '50x70' => '50 × 70 cm', 'ig_post' => __('Instagram post 1080 × 1350', $t), 'ig_story' => __('Instagram story 1080 × 1920', $t), 'custom' => __('Custom', $t),
                ]],
                $custom('poster_size_custom'),
                ['key' => 'poster_orientation', 'label' => __('Orientasi', $t), 'type' => 'radio', 'width' => 'half', 'options' => ['portrait' => 'Portrait', 'landscape' => 'Landscape']],
                ['key' => 'poster_finish', 'label' => __('Finishing cetak', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'none' => __('Tanpa finishing', $t), 'glossy' => __('Glossy', $t), 'matte' => __('Doff / matte', $t), 'laminate' => __('Laminasi', $t), 'foamboard' => __('Ditempel foam board', $t),
                ]],
                $printQty('poster_qty'),
            ],
        ],
        'poster_event' => [
            'title' => __('Informasi acara', $t), 'types' => ['poster'],
            'intro' => __('Isi bila poster untuk acara. Lewati bila tidak perlu.', $t),
            'fields' => [
                ['key' => 'poster_event_name', 'label' => __('Nama acara', $t), 'type' => 'text', 'width' => 'half'],
                ['key' => 'poster_event_place', 'label' => __('Lokasi', $t), 'type' => 'text', 'width' => 'half'],
                ['key' => 'poster_event_date', 'label' => __('Tanggal', $t), 'type' => 'date', 'width' => 'third'],
                ['key' => 'poster_event_time', 'label' => __('Jam', $t), 'type' => 'text', 'width' => 'third', 'placeholder' => '19.00 – 23.00 WITA'],
                ['key' => 'poster_ticket', 'label' => __('Harga tiket / HTM', $t), 'type' => 'text', 'width' => 'third', 'placeholder' => __('mis. Rp 150.000 / gratis', $t)],
                ['key' => 'poster_lineup', 'label' => __('Susunan acara / pengisi acara', $t), 'type' => 'repeater', 'rows' => 2, 'add_label' => __('Tambah baris acara', $t), 'columns' => [
                    ['key' => 'time', 'label' => __('Jam', $t), 'type' => 'text', 'size' => 1, 'placeholder' => '19.00'],
                    ['key' => 'act', 'label' => __('Acara / pengisi', $t), 'type' => 'text', 'size' => 3, 'required' => true, 'placeholder' => __('mis. DJ Komang', $t)],
                    ['key' => 'note', 'label' => __('Keterangan', $t), 'type' => 'text', 'size' => 2, 'placeholder' => __('mis. live acoustic', $t)],
                ]],
                ['key' => 'poster_qr', 'label' => __('Tambahkan QR code di poster?', $t), 'type' => 'radio', 'width' => 'half', 'options' => $yesNo],
                ['key' => 'poster_qr_url', 'label' => __('QR mengarah ke', $t), 'type' => 'url', 'width' => 'half', 'placeholder' => 'https://'],
            ],
        ],

        // ---------------------------------------------------------- menu
        'menu' => [
            'title' => __('Format menu', $t), 'types' => ['menu'],
            'fields' => [
                ['key' => 'menu_format', 'label' => __('Format', $t), 'type' => 'select', 'width' => 'half', 'required' => true, 'options' => [
                    'single' => __('1 halaman', $t), 'bifold' => __('Lipat 2 (bi-fold)', $t), 'trifold' => __('Lipat 3 (tri-fold)', $t), 'booklet' => __('Buku menu', $t),
                    'board' => __('Papan / menu board', $t), 'table_tent' => __('Table tent', $t), 'digital' => __('Menu digital / QR', $t),
                ]],
                ['key' => 'menu_size', 'label' => __('Ukuran', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'a4' => 'A4', 'a3' => 'A3', 'a5' => 'A5', 'dl' => 'DL (10 × 21 cm)', 'custom' => __('Custom', $t),
                ]],
                $custom('menu_size_custom'),
                ['key' => 'menu_pages', 'label' => __('Jumlah halaman', $t), 'type' => 'number', 'width' => 'half'],
                ['key' => 'menu_material', 'label' => __('Material', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'paper' => __('Art paper', $t), 'laminate_matte' => __('Laminasi doff', $t), 'laminate_gloss' => __('Laminasi glossy', $t), 'pvc' => __('PVC / tahan air', $t),
                    'acrylic' => __('Akrilik', $t), 'wood' => __('Kayu / engraving', $t), 'digital' => __('Digital saja', $t),
                ]],
                $printQty('menu_qty'),
            ],
        ],
        'menu_items' => [
            'title' => __('Daftar menu', $t), 'types' => ['menu'],
            'intro' => __('Satu baris untuk satu item. Kategori yang sama akan dikelompokkan di desain.', $t),
            'fields' => [
                ['key' => 'menu_items', 'label' => __('Item menu', $t), 'type' => 'repeater', 'rows' => 3, 'add_label' => __('Tambah item menu', $t), 'columns' => [
                    ['key' => 'category', 'label' => __('Kategori', $t), 'type' => 'text', 'size' => 2, 'placeholder' => __('mis. Main course', $t)],
                    ['key' => 'name', 'label' => __('Nama menu', $t), 'type' => 'text', 'size' => 3, 'required' => true, 'placeholder' => __('mis. Nasi Campur Bali', $t)],
                    ['key' => 'ingredients', 'label' => __('Bahan / deskripsi', $t), 'type' => 'text', 'size' => 4, 'placeholder' => __('ayam betutu, sate lilit, sambal matah', $t)],
                    ['key' => 'price', 'label' => __('Harga', $t), 'type' => 'text', 'size' => 2, 'inputmode' => 'decimal', 'placeholder' => '65.000'],
                    ['key' => 'label', 'label' => __('Label', $t), 'type' => 'select', 'size' => 2, 'options' => [
                        'best' => __('Best seller', $t), 'chef' => __('Rekomendasi chef', $t), 'new' => __('Baru', $t), 'spicy' => __('Pedas', $t),
                        'vegan' => 'Vegan', 'vegetarian' => 'Vegetarian', 'gluten_free' => 'Gluten free', 'nuts' => __('Mengandung kacang', $t),
                    ]],
                ]],
                ['key' => 'menu_file', 'label' => __('Atau unggah daftar menu yang sudah ada', $t), 'type' => 'file', 'multiple' => true, 'accept' => '.pdf,.xlsx,.xls,.csv,.docx,.doc,.txt,image/*', 'help' => __('Excel, Word, PDF atau foto menu lama.', $t)],
                ['key' => 'menu_currency', 'label' => __('Mata uang', $t), 'type' => 'radio', 'width' => 'half', 'options' => ['idr' => 'IDR (Rp)', 'usd' => 'USD ($)', 'both' => __('Keduanya', $t), 'none' => __('Tanpa harga', $t)]],
                ['key' => 'menu_price_format', 'label' => __('Penulisan harga', $t), 'type' => 'radio', 'width' => 'half', 'options' => ['full' => '65.000', 'short' => '65', 'k' => '65K']],
                ['key' => 'menu_price_note', 'label' => __('Keterangan pajak', $t), 'type' => 'radio', 'options' => [
                    'net' => __('Sudah termasuk pajak & service', $t), 'plus' => __('Belum termasuk pajak & service (++)', $t), 'none' => __('Tidak perlu keterangan', $t),
                ]],
                ['key' => 'menu_photos', 'label' => __('Foto makanan', $t), 'type' => 'radio', 'options' => [
                    'none' => __('Tanpa foto', $t), 'own' => __('Pakai foto dari kami', $t), 'shoot' => __('Perlu sesi foto', $t), 'stock' => __('Boleh foto stok / ilustrasi', $t),
                ]],
                ['key' => 'menu_photo_files', 'label' => __('Foto makanan & minuman', $t), 'type' => 'file', 'multiple' => true, 'accept' => 'image/*', 'help' => __('Beri nama file sesuai nama menu agar mudah dicocokkan, mis. nasi-campur.jpg.', $t)],
                ['key' => 'menu_extra', 'label' => __('Info tambahan di menu', $t), 'type' => 'textarea', 'placeholder' => __('Jam buka, password Wi-Fi, info alergi, catatan "harga dapat berubah"…', $t)],
            ],
        ],

        // ---------------------------------------------------------- flyer
        'flyer' => [
            'title' => __('Format flyer', $t), 'types' => ['flyer'],
            'fields' => [
                ['key' => 'flyer_size', 'label' => __('Ukuran', $t), 'type' => 'select', 'width' => 'half', 'required' => true, 'options' => [
                    'a5' => 'A5 (14,8 × 21 cm)', 'a4' => 'A4', 'dl' => 'DL (10 × 21 cm)', 'third_a4' => __('1/3 A4', $t),
                    'square' => __('Square 1080 × 1080 (digital)', $t), 'story' => __('Story 1080 × 1920 (digital)', $t), 'custom' => __('Custom', $t),
                ]],
                $custom('flyer_size_custom'),
                ['key' => 'flyer_sides', 'label' => __('Sisi cetak', $t), 'type' => 'radio', 'width' => 'half', 'options' => ['one' => __('1 sisi', $t), 'two' => __('2 sisi (bolak-balik)', $t)]],
                ['key' => 'flyer_fold', 'label' => __('Lipatan', $t), 'type' => 'radio', 'width' => 'half', 'options' => ['none' => __('Tanpa lipatan', $t), 'bi' => __('Lipat 2', $t), 'tri' => __('Lipat 3', $t)]],
                ['key' => 'flyer_channels', 'label' => __('Disebarkan lewat', $t), 'type' => 'checkboxes', 'options' => [
                    'print' => __('Cetak & dibagikan', $t), 'whatsapp' => 'WhatsApp', 'ig_feed' => 'Instagram feed', 'ig_story' => 'Instagram story', 'facebook' => 'Facebook', 'email' => 'Email',
                ]],
                $printQty('flyer_qty'),
            ],
        ],
        'flyer_offer' => [
            'title' => __('Promo & penawaran', $t), 'types' => ['flyer'],
            'fields' => [
                ['key' => 'flyer_offer', 'label' => __('Promo utama', $t), 'type' => 'text', 'placeholder' => __('mis. Diskon 20% semua treatment spa', $t)],
                ['key' => 'flyer_items', 'label' => __('Produk / layanan yang ditampilkan', $t), 'type' => 'repeater', 'rows' => 2, 'add_label' => __('Tambah produk', $t), 'columns' => [
                    ['key' => 'name', 'label' => __('Produk / layanan', $t), 'type' => 'text', 'size' => 3, 'required' => true, 'placeholder' => __('mis. Balinese massage 60 menit', $t)],
                    ['key' => 'price', 'label' => __('Harga normal', $t), 'type' => 'text', 'size' => 2, 'inputmode' => 'decimal', 'placeholder' => '350.000'],
                    ['key' => 'promo', 'label' => __('Harga promo', $t), 'type' => 'text', 'size' => 2, 'inputmode' => 'decimal', 'placeholder' => '280.000'],
                    ['key' => 'note', 'label' => __('Keterangan', $t), 'type' => 'text', 'size' => 3, 'placeholder' => __('mis. termasuk welcome drink', $t)],
                ]],
                ['key' => 'flyer_period', 'label' => __('Masa berlaku', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. 1–31 Desember', $t)],
                ['key' => 'flyer_code', 'label' => __('Kode promo', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. HUJAN20', $t)],
                ['key' => 'flyer_terms', 'label' => __('Syarat & ketentuan', $t), 'type' => 'textarea'],
            ],
        ],

        // ---------------------------------------------------------- shared text (poster & flyer)
        'content' => [
            'title'  => __('Teks di desain', $t), 'types' => ['poster', 'flyer'],
            'intro'  => __('Tulis persis seperti yang ingin dicetak. Kami akan merapikan tata letaknya.', $t),
            'fields' => [
                ['key' => 'headline', 'label' => __('Judul utama (headline)', $t), 'type' => 'text', 'width' => 'half'],
                ['key' => 'subheadline', 'label' => __('Sub-judul', $t), 'type' => 'text', 'width' => 'half'],
                ['key' => 'body_text', 'label' => __('Teks isi', $t), 'type' => 'textarea'],
                ['key' => 'cta', 'label' => __('Ajakan (call to action)', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. Reservasi via WhatsApp', $t)],
                ['key' => 'contact_show', 'label' => __('Kontak yang ditampilkan', $t), 'type' => 'checkboxes', 'options' => [
                    'whatsapp' => 'WhatsApp', 'phone' => __('Telepon', $t), 'email' => 'Email', 'instagram' => 'Instagram',
                    'website' => 'Website', 'address' => __('Alamat', $t), 'maps' => __('QR Google Maps', $t),
                ]],
                ['key' => 'contact_text', 'label' => __('Teks kontak yang dicetak', $t), 'type' => 'textarea', 'placeholder' => __('Nomor, akun, alamat persis seperti yang ingin tampil', $t)],
            ],
        ],

        // ---------------------------------------------------------- backdrop
        'backdrop' => [
            'title' => __('Ukuran & material backdrop', $t), 'types' => ['backdrop'],
            'fields' => [
                ['key' => 'backdrop_width', 'label' => __('Lebar (meter)', $t), 'type' => 'number', 'width' => 'third', 'required' => true, 'step' => '0.1'],
                ['key' => 'backdrop_height', 'label' => __('Tinggi (meter)', $t), 'type' => 'number', 'width' => 'third', 'required' => true, 'step' => '0.1'],
                ['key' => 'backdrop_location', 'label' => __('Dipasang di', $t), 'type' => 'radio', 'width' => 'third', 'options' => ['indoor' => 'Indoor', 'outdoor' => 'Outdoor']],
                ['key' => 'backdrop_material', 'label' => __('Material', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'flexi' => __('Flexi / banner', $t), 'albatros' => 'Albatros', 'fabric' => __('Kain / fabric', $t), 'board' => __('Papan / rangka kayu', $t),
                    'led' => __('Layar LED / digital', $t), 'unknown' => __('Belum tahu, minta saran', $t),
                ]],
                ['key' => 'backdrop_install', 'label' => __('Lingkup pekerjaan', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'design' => __('File desain saja', $t), 'print' => __('Desain + cetak', $t), 'install' => __('Desain + cetak + pemasangan', $t),
                ]],
            ],
        ],
        'backdrop_event' => [
            'title' => __('Acara & isi backdrop', $t), 'types' => ['backdrop'],
            'fields' => [
                ['key' => 'backdrop_event', 'label' => __('Nama acara', $t), 'type' => 'text', 'width' => 'half'],
                ['key' => 'backdrop_venue', 'label' => __('Venue', $t), 'type' => 'text', 'width' => 'half'],
                ['key' => 'backdrop_date', 'label' => __('Tanggal acara', $t), 'type' => 'date', 'width' => 'half'],
                ['key' => 'backdrop_headline', 'label' => __('Teks utama', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. Welcome to Wedding of Putu & Sarah', $t)],
                ['key' => 'backdrop_elements', 'label' => __('Elemen di backdrop', $t), 'type' => 'checkboxes', 'options' => [
                    'sponsors' => __('Logo sponsor', $t), 'photobooth' => __('Area photo booth', $t), 'repeat' => __('Pola step-and-repeat', $t),
                    'names' => __('Nama mempelai / tokoh', $t), 'hashtag' => __('Hashtag acara', $t), 'qr' => 'QR code',
                ]],
                ['key' => 'backdrop_names', 'label' => __('Nama mempelai / tokoh', $t), 'type' => 'text', 'placeholder' => __('Tulis persis seperti yang ingin dicetak', $t)],
                ['key' => 'backdrop_hashtag', 'label' => __('Hashtag', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => '#'],
                ['key' => 'backdrop_sponsors', 'label' => __('Daftar sponsor / partner', $t), 'type' => 'repeater', 'rows' => 2, 'add_label' => __('Tambah sponsor', $t), 'columns' => [
                    ['key' => 'name', 'label' => __('Nama sponsor', $t), 'type' => 'text', 'size' => 3, 'required' => true],
                    ['key' => 'tier', 'label' => __('Kategori', $t), 'type' => 'select', 'size' => 2, 'options' => [
                        'main' => __('Sponsor utama', $t), 'support' => __('Sponsor pendukung', $t), 'media' => __('Media partner', $t), 'venue' => __('Venue / host', $t),
                    ]],
                ]],
                ['key' => 'backdrop_sponsor_logos', 'label' => __('Logo sponsor / partner', $t), 'type' => 'file', 'multiple' => true, 'accept' => 'image/*,application/pdf', 'help' => __('Beri nama file sesuai nama sponsor.', $t)],
            ],
        ],

        // ---------------------------------------------------------- business card
        'business_card' => [
            'title' => __('Data di kartu nama', $t), 'types' => ['business_card'],
            'intro' => __('Satu baris untuk satu orang. Pastikan setiap orang setuju datanya dicetak.', $t),
            'fields' => [
                ['key' => 'card_people', 'label' => __('Orang', $t), 'type' => 'repeater', 'rows' => 1, 'required' => true, 'add_label' => __('Tambah orang', $t), 'columns' => [
                    ['key' => 'name', 'label' => __('Nama lengkap', $t), 'type' => 'text', 'size' => 3, 'required' => true],
                    ['key' => 'title', 'label' => __('Jabatan', $t), 'type' => 'text', 'size' => 3],
                    ['key' => 'phone', 'label' => __('Telepon / WA', $t), 'type' => 'text', 'size' => 2, 'inputmode' => 'tel'],
                    ['key' => 'email', 'label' => __('Email', $t), 'type' => 'text', 'size' => 3, 'inputmode' => 'email'],
                ]],
                ['key' => 'card_shared', 'label' => __('Info yang sama di semua kartu', $t), 'type' => 'textarea', 'placeholder' => __('Alamat kantor, website, Instagram…', $t)],
            ],
        ],
        'business_card_print' => [
            'title' => __('Ukuran & cetak kartu', $t), 'types' => ['business_card'],
            'fields' => [
                ['key' => 'card_size', 'label' => __('Ukuran', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'std' => __('Standar 9 × 5,5 cm', $t), 'eu' => '8,5 × 5,5 cm', 'square' => __('Persegi 6,5 × 6,5 cm', $t), 'custom' => __('Custom', $t),
                ]],
                $custom('card_size_custom'),
                ['key' => 'card_sides', 'label' => __('Sisi cetak', $t), 'type' => 'radio', 'width' => 'half', 'options' => ['one' => __('1 sisi', $t), 'two' => __('2 sisi', $t)]],
                ['key' => 'card_orientation', 'label' => __('Orientasi', $t), 'type' => 'radio', 'width' => 'half', 'options' => ['landscape' => 'Landscape', 'portrait' => 'Portrait']],
                ['key' => 'card_back', 'label' => __('Isi sisi belakang', $t), 'type' => 'text', 'placeholder' => __('mis. logo besar + alamat + jam buka', $t)],
                ['key' => 'card_paper', 'label' => __('Kertas', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'ac260' => 'Art carton 260 gsm', 'ac310' => 'Art carton 310 gsm', 'linen' => __('Linen / bertekstur', $t), 'kraft' => 'Kraft', 'fancy' => __('Fancy / premium paper', $t),
                ]],
                ['key' => 'card_qr', 'label' => __('QR code di kartu', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'none' => __('Tidak perlu', $t), 'website' => __('Ke website', $t), 'whatsapp' => __('Ke WhatsApp', $t), 'vcard' => __('Simpan kontak (vCard)', $t),
                ]],
                ['key' => 'card_finish', 'label' => __('Finishing', $t), 'type' => 'checkboxes', 'options' => [
                    'matte' => __('Laminasi doff', $t), 'gloss' => __('Laminasi glossy', $t), 'spot_uv' => 'Spot UV', 'emboss' => __('Emboss / deboss', $t),
                    'foil' => __('Foil emas / perak', $t), 'round' => __('Sudut membulat', $t),
                ]],
                ['key' => 'card_boxes', 'label' => __('Jumlah box per orang', $t), 'type' => 'number', 'width' => 'half', 'help' => __('1 box = 100 kartu.', $t)],
            ],
        ],

        // ---------------------------------------------------------- shared
        'style' => [
            'title'  => __('Gaya, referensi & file', $t),
            'fields' => [
                ['key' => 'style', 'label' => __('Gaya yang diinginkan', $t), 'type' => 'checkboxes', 'options' => [
                    'minimal' => __('Minimalis', $t), 'elegant' => __('Elegan / mewah', $t), 'modern' => __('Modern', $t), 'bold' => __('Bold & berwarna', $t),
                    'tropical' => __('Tropis', $t), 'balinese' => __('Tradisional Bali', $t), 'playful' => __('Ceria / playful', $t), 'vintage' => __('Vintage / retro', $t), 'corporate' => __('Korporat', $t),
                ]],
                ['key' => 'use_brand', 'label' => __('Warna & logo', $t), 'type' => 'radio', 'options' => [
                    'brand' => __('Pakai warna dan logo brand', $t), 'free' => __('Bebas, boleh palet baru', $t),
                ]],
                ['key' => 'avoid', 'label' => __('Yang perlu dihindari', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. warna merah, font dekoratif', $t)],
                ['key' => 'reference_links', 'label' => __('Link referensi', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('Instagram, Pinterest, website', $t)],
                ['key' => 'reference_files', 'label' => __('Gambar referensi', $t), 'type' => 'file', 'multiple' => true, 'accept' => 'image/*,application/pdf', 'help' => __('Desain yang Anda suka, dari mana saja.', $t)],
                ['key' => 'asset_files', 'label' => __('Foto & aset untuk dipakai', $t), 'type' => 'file', 'multiple' => true, 'accept' => 'image/*,application/pdf,.zip', 'help' => __('Foto produk, foto tempat, logo partner.', $t)],
                ['key' => 'notes', 'label' => __('Catatan lain', $t), 'type' => 'textarea', 'placeholder' => __('Hal lain yang perlu kami ketahui', $t)],
            ],
        ],
    ];
}

/** Flat list of order fields that apply to a design type. */
function lpdo_fields_for_type(string $type): array
{
    $out = [];
    foreach (lpdo_order_groups() as $group) {
        if (!empty($group['types']) && !in_array($type, $group['types'], true)) {
            continue;
        }
        foreach ($group['fields'] as $f) {
            $out[$f['key']] = $f;
        }
    }
    return $out;
}

/** Sanitise one submitted value according to its field definition. */
function lpdo_sanitize_value(array $f, $raw)
{
    switch ($f['type']) {
        case 'textarea':
            return sanitize_textarea_field((string) $raw);
        case 'email':
            return sanitize_email((string) $raw);
        case 'url':
            return esc_url_raw((string) $raw);
        case 'number':
            return is_numeric($raw) ? (string) (0 + $raw) : '';
        case 'date':
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $raw) ? (string) $raw : '';
        case 'color':
            return sanitize_hex_color((string) $raw) ?: '';
        case 'select':
        case 'radio':
            return isset($f['options'][(string) $raw]) ? (string) $raw : '';
        case 'checkboxes':
            return array_values(array_intersect(array_map('strval', (array) $raw), array_map('strval', array_keys($f['options']))));
        case 'repeater':
            $rows = [];
            foreach (array_slice(is_array($raw) ? $raw : [], 0, 200) as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $clean = [];
                foreach ($f['columns'] as $col) {
                    $clean[$col['key']] = lpdo_sanitize_value($col, $row[$col['key']] ?? '');
                }
                if (implode('', $clean) !== '') {
                    $rows[] = $clean;
                }
            }
            return $rows;
        default:
            return sanitize_text_field((string) $raw);
    }
}

/** Human-readable value for summaries. */
function lpdo_display_value(array $f, $value): string
{
    if ($value === '' || $value === [] || $value === null) {
        return '';
    }
    if (in_array($f['type'], ['select', 'radio'], true)) {
        return (string) ($f['options'][$value] ?? $value);
    }
    if ($f['type'] === 'checkboxes') {
        return implode(', ', array_map(fn($v) => $f['options'][$v] ?? $v, (array) $value));
    }
    if ($f['type'] === 'repeater') {
        $lines = [];
        foreach ((array) $value as $i => $row) {
            $cells = [];
            foreach ($f['columns'] as $col) {
                $v = lpdo_display_value($col, $row[$col['key']] ?? '');
                if ($v !== '') {
                    $cells[] = $v;
                }
            }
            $lines[] = ($i + 1) . '. ' . implode(' — ', $cells);
        }
        return implode("\n", $lines);
    }
    if ($f['type'] === 'date') {
        return date_i18n(get_option('date_format'), strtotime($value));
    }
    return is_array($value) ? implode(', ', $value) : (string) $value;
}

/** Problems with a value beyond "required": rows of a table missing a required column. */
function lpdo_validate_value(array $f, $value): array
{
    $errors = [];
    if ($f['type'] === 'repeater') {
        foreach ((array) $value as $i => $row) {
            foreach ($f['columns'] as $col) {
                if (!empty($col['required']) && ($row[$col['key']] ?? '') === '') {
                    /* translators: 1: table label, 2: row number, 3: column label */
                    $errors[] = sprintf(__('%1$s, baris %2$d: %3$s wajib diisi.', 'lightandpulse-design-orders'), $f['label'], $i + 1, $col['label']);
                }
            }
        }
    }
    return $errors;
}

/** A filled-in table as HTML (portal brief, admin). */
function lpdo_repeater_table(array $f, $rows): string
{
    $rows = (array) $rows;
    if (!$rows) {
        return '';
    }
    $cols = array_values(array_filter($f['columns'], function ($col) use ($rows) {
        foreach ($rows as $r) {
            if (($r[$col['key']] ?? '') !== '') {
                return true;
            }
        }
        return false;
    }));
    $html = '<div class="lpdo-table-wrap"><table class="lpdo-table"><thead><tr><th scope="col">#</th>';
    foreach ($cols as $col) {
        $html .= '<th scope="col">' . esc_html($col['label']) . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach ($rows as $i => $r) {
        $html .= '<tr><td>' . ($i + 1) . '</td>';
        foreach ($cols as $col) {
            $html .= '<td>' . esc_html(lpdo_display_value($col, $r[$col['key']] ?? '')) . '</td>';
        }
        $html .= '</tr>';
    }
    return $html . '</tbody></table></div>';
}
