<?php
/**
 * Field definitions. One schema drives the public order form, the admin
 * edit screen and the client portal summary.
 *
 * Field keys: key, label, type (text|textarea|email|tel|url|number|date|color|select|radio|checkboxes|file),
 * options, required, help, placeholder, width (half|full), multiple (file), accept (file).
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
                ['key' => 'title', 'label' => __('Judul proyek', $t), 'type' => 'text', 'required' => true, 'placeholder' => __('mis. Poster promo Happy Hour Desember', $t)],
                ['key' => 'purpose', 'label' => __('Tujuan desain', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'promo' => __('Promosi / penjualan', $t), 'event' => __('Acara / event', $t), 'opening' => __('Grand opening', $t),
                    'menu_update' => __('Menu baru / perubahan harga', $t), 'branding' => __('Branding umum', $t), 'other' => __('Lainnya', $t),
                ]],
                ['key' => 'audience', 'label' => __('Target audiens', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. turis keluarga, warga lokal muda', $t)],
                ['key' => 'languages', 'label' => __('Bahasa di desain', $t), 'type' => 'checkboxes', 'options' => ['id' => __('Indonesia', $t), 'en' => __('English', $t), 'other' => __('Lainnya', $t)]],
                ['key' => 'languages_other', 'label' => __('Bahasa lain', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. 中文, Русский', $t)],
            ],
        ],
        'content' => [
            'title'  => __('Isi teks', $t),
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

        // ---------------------------------------------------------- per type
        'poster' => [
            'title' => __('Detail poster', $t), 'types' => ['poster'],
            'fields' => [
                ['key' => 'poster_size', 'label' => __('Ukuran', $t), 'type' => 'select', 'width' => 'half', 'required' => true, 'options' => [
                    'a4' => 'A4 (21 × 29,7 cm)', 'a3' => 'A3 (29,7 × 42 cm)', 'a2' => 'A2 (42 × 59,4 cm)', 'a1' => 'A1 (59,4 × 84,1 cm)', 'a0' => 'A0 (84,1 × 118,9 cm)',
                    '50x70' => '50 × 70 cm', 'ig_post' => __('Instagram post 1080 × 1350', $t), 'ig_story' => __('Instagram story 1080 × 1920', $t), 'custom' => __('Custom', $t),
                ]],
                $custom('poster_size_custom'),
                ['key' => 'poster_orientation', 'label' => __('Orientasi', $t), 'type' => 'radio', 'options' => ['portrait' => 'Portrait', 'landscape' => 'Landscape']],
                ['key' => 'poster_event_date', 'label' => __('Tanggal acara', $t), 'type' => 'date', 'width' => 'third'],
                ['key' => 'poster_event_time', 'label' => __('Jam', $t), 'type' => 'text', 'width' => 'third', 'placeholder' => '19.00 – 23.00 WITA'],
                ['key' => 'poster_event_place', 'label' => __('Lokasi acara', $t), 'type' => 'text', 'width' => 'third'],
                ['key' => 'poster_qr', 'label' => __('Tambahkan QR code di poster?', $t), 'type' => 'radio', 'options' => $yesNo],
                ['key' => 'poster_qr_url', 'label' => __('QR mengarah ke', $t), 'type' => 'url', 'width' => 'half', 'placeholder' => 'https://'],
                ['key' => 'poster_finish', 'label' => __('Finishing cetak', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'none' => __('Tanpa finishing', $t), 'glossy' => __('Glossy', $t), 'matte' => __('Doff / matte', $t), 'laminate' => __('Laminasi', $t), 'foamboard' => __('Ditempel foam board', $t),
                ]],
                $printQty('poster_qty'),
            ],
        ],
        'menu' => [
            'title' => __('Detail menu', $t), 'types' => ['menu'],
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
                ['key' => 'menu_categories', 'label' => __('Kategori menu', $t), 'type' => 'textarea', 'placeholder' => __('mis. Breakfast, Main course, Dessert, Coffee, Mocktail', $t)],
                ['key' => 'menu_items', 'label' => __('Daftar item', $t), 'type' => 'textarea', 'help' => __('Satu item per baris: Nama — deskripsi — harga. Atau unggah file di bawah.', $t), 'placeholder' => __("Nasi Campur Bali — ayam betutu, sate lilit, sambal matah — 65", $t)],
                ['key' => 'menu_file', 'label' => __('File daftar menu', $t), 'type' => 'file', 'multiple' => true, 'accept' => '.pdf,.xlsx,.xls,.csv,.docx,.doc,.txt,image/*'],
                ['key' => 'menu_currency', 'label' => __('Mata uang', $t), 'type' => 'radio', 'options' => ['idr' => 'IDR (Rp)', 'usd' => 'USD ($)', 'both' => __('Keduanya', $t), 'none' => __('Tanpa harga', $t)]],
                ['key' => 'menu_price_note', 'label' => __('Keterangan harga', $t), 'type' => 'radio', 'options' => [
                    'net' => __('Harga sudah termasuk pajak & service', $t), 'plus' => __('Harga belum termasuk pajak & service (++)', $t), 'none' => __('Tidak perlu keterangan', $t),
                ]],
                ['key' => 'menu_icons', 'label' => __('Ikon keterangan', $t), 'type' => 'checkboxes', 'options' => [
                    'vegan' => 'Vegan', 'vegetarian' => 'Vegetarian', 'gluten_free' => 'Gluten free', 'spicy' => __('Pedas', $t), 'halal' => 'Halal', 'nuts' => __('Mengandung kacang', $t), 'chef' => __('Rekomendasi chef', $t),
                ]],
                ['key' => 'menu_photos', 'label' => __('Foto makanan', $t), 'type' => 'radio', 'options' => [
                    'none' => __('Tanpa foto', $t), 'own' => __('Pakai foto dari kami (diunggah)', $t), 'shoot' => __('Perlu sesi foto', $t), 'stock' => __('Boleh foto stok / ilustrasi', $t),
                ]],
                ['key' => 'menu_material', 'label' => __('Material', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'paper' => __('Art paper', $t), 'laminate_matte' => __('Laminasi doff', $t), 'laminate_gloss' => __('Laminasi glossy', $t), 'pvc' => __('PVC / tahan air', $t),
                    'acrylic' => __('Akrilik', $t), 'wood' => __('Kayu / engraving', $t), 'digital' => __('Digital saja', $t),
                ]],
                $printQty('menu_qty'),
            ],
        ],
        'flyer' => [
            'title' => __('Detail flyer', $t), 'types' => ['flyer'],
            'fields' => [
                ['key' => 'flyer_size', 'label' => __('Ukuran', $t), 'type' => 'select', 'width' => 'half', 'required' => true, 'options' => [
                    'a5' => 'A5 (14,8 × 21 cm)', 'a4' => 'A4', 'dl' => 'DL (10 × 21 cm)', 'third_a4' => __('1/3 A4', $t),
                    'square' => __('Square 1080 × 1080 (digital)', $t), 'story' => __('Story 1080 × 1920 (digital)', $t), 'custom' => __('Custom', $t),
                ]],
                $custom('flyer_size_custom'),
                ['key' => 'flyer_sides', 'label' => __('Sisi cetak', $t), 'type' => 'radio', 'options' => ['one' => __('1 sisi', $t), 'two' => __('2 sisi (bolak-balik)', $t)]],
                ['key' => 'flyer_fold', 'label' => __('Lipatan', $t), 'type' => 'radio', 'options' => ['none' => __('Tanpa lipatan', $t), 'bi' => __('Lipat 2', $t), 'tri' => __('Lipat 3', $t)]],
                ['key' => 'flyer_channels', 'label' => __('Disebarkan lewat', $t), 'type' => 'checkboxes', 'options' => [
                    'print' => __('Cetak & dibagikan', $t), 'whatsapp' => 'WhatsApp', 'ig_feed' => 'Instagram feed', 'ig_story' => 'Instagram story', 'facebook' => 'Facebook', 'email' => 'Email',
                ]],
                ['key' => 'flyer_offer', 'label' => __('Detail promo / penawaran', $t), 'type' => 'textarea', 'placeholder' => __('mis. Diskon 20% semua treatment spa, Senin–Kamis', $t)],
                ['key' => 'flyer_period', 'label' => __('Masa berlaku', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. 1–31 Desember', $t)],
                ['key' => 'flyer_terms', 'label' => __('Syarat & ketentuan', $t), 'type' => 'textarea'],
                $printQty('flyer_qty'),
            ],
        ],
        'backdrop' => [
            'title' => __('Detail backdrop', $t), 'types' => ['backdrop'],
            'fields' => [
                ['key' => 'backdrop_width', 'label' => __('Lebar (meter)', $t), 'type' => 'number', 'width' => 'third', 'required' => true, 'step' => '0.1'],
                ['key' => 'backdrop_height', 'label' => __('Tinggi (meter)', $t), 'type' => 'number', 'width' => 'third', 'required' => true, 'step' => '0.1'],
                ['key' => 'backdrop_location', 'label' => __('Dipasang di', $t), 'type' => 'radio', 'width' => 'third', 'options' => ['indoor' => 'Indoor', 'outdoor' => 'Outdoor']],
                ['key' => 'backdrop_material', 'label' => __('Material', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'flexi' => __('Flexi / banner', $t), 'albatros' => 'Albatros', 'fabric' => __('Kain / fabric', $t), 'board' => __('Papan / rangka kayu', $t),
                    'led' => __('Layar LED / digital', $t), 'unknown' => __('Belum tahu, minta saran', $t),
                ]],
                ['key' => 'backdrop_event', 'label' => __('Nama acara', $t), 'type' => 'text', 'width' => 'half'],
                ['key' => 'backdrop_date', 'label' => __('Tanggal acara', $t), 'type' => 'date', 'width' => 'half'],
                ['key' => 'backdrop_venue', 'label' => __('Venue', $t), 'type' => 'text', 'width' => 'half'],
                ['key' => 'backdrop_elements', 'label' => __('Elemen di backdrop', $t), 'type' => 'checkboxes', 'options' => [
                    'sponsors' => __('Logo sponsor', $t), 'photobooth' => __('Area photo booth', $t), 'repeat' => __('Pola step-and-repeat', $t),
                    'names' => __('Nama mempelai / tokoh', $t), 'hashtag' => __('Hashtag acara', $t), 'qr' => 'QR code',
                ]],
                ['key' => 'backdrop_sponsor_logos', 'label' => __('Logo sponsor / partner', $t), 'type' => 'file', 'multiple' => true, 'accept' => 'image/*,application/pdf'],
                ['key' => 'backdrop_hashtag', 'label' => __('Hashtag', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => '#'],
                ['key' => 'backdrop_install', 'label' => __('Lingkup pekerjaan', $t), 'type' => 'radio', 'options' => [
                    'design' => __('File desain saja', $t), 'print' => __('Desain + cetak', $t), 'install' => __('Desain + cetak + pemasangan', $t),
                ]],
            ],
        ],
        'business_card' => [
            'title' => __('Detail kartu nama', $t), 'types' => ['business_card'],
            'fields' => [
                ['key' => 'card_people', 'label' => __('Data di kartu', $t), 'type' => 'textarea', 'required' => true,
                    'help' => __('Satu orang per baris: Nama | Jabatan | Telepon | Email. Pastikan setiap orang setuju datanya dicetak.', $t),
                    'placeholder' => __("Made Wirawan | General Manager | 0812 3456 7890 | made@contoh.com", $t)],
                ['key' => 'card_size', 'label' => __('Ukuran', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'std' => __('Standar 9 × 5,5 cm', $t), 'eu' => '8,5 × 5,5 cm', 'square' => __('Persegi 6,5 × 6,5 cm', $t), 'custom' => __('Custom', $t),
                ]],
                $custom('card_size_custom'),
                ['key' => 'card_sides', 'label' => __('Sisi cetak', $t), 'type' => 'radio', 'width' => 'half', 'options' => ['one' => __('1 sisi', $t), 'two' => __('2 sisi', $t)]],
                ['key' => 'card_orientation', 'label' => __('Orientasi', $t), 'type' => 'radio', 'width' => 'half', 'options' => ['landscape' => 'Landscape', 'portrait' => 'Portrait']],
                ['key' => 'card_paper', 'label' => __('Kertas', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'ac260' => 'Art carton 260 gsm', 'ac310' => 'Art carton 310 gsm', 'linen' => __('Linen / bertekstur', $t), 'kraft' => 'Kraft', 'fancy' => __('Fancy / premium paper', $t),
                ]],
                ['key' => 'card_finish', 'label' => __('Finishing', $t), 'type' => 'checkboxes', 'options' => [
                    'matte' => __('Laminasi doff', $t), 'gloss' => __('Laminasi glossy', $t), 'spot_uv' => 'Spot UV', 'emboss' => __('Emboss / deboss', $t),
                    'foil' => __('Foil emas / perak', $t), 'round' => __('Sudut membulat', $t),
                ]],
                ['key' => 'card_back', 'label' => __('Isi sisi belakang', $t), 'type' => 'textarea', 'placeholder' => __('mis. logo besar + alamat + jam buka', $t)],
                ['key' => 'card_qr', 'label' => __('QR code di kartu', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'none' => __('Tidak perlu', $t), 'website' => __('Ke website', $t), 'whatsapp' => __('Ke WhatsApp', $t), 'vcard' => __('Simpan kontak (vCard)', $t),
                ]],
                ['key' => 'card_boxes', 'label' => __('Jumlah box per orang', $t), 'type' => 'number', 'width' => 'half', 'help' => __('1 box = 100 kartu.', $t)],
            ],
        ],

        // ---------------------------------------------------------- shared
        'style' => [
            'title'  => __('Gaya & referensi', $t),
            'fields' => [
                ['key' => 'style', 'label' => __('Gaya yang diinginkan', $t), 'type' => 'checkboxes', 'options' => [
                    'minimal' => __('Minimalis', $t), 'elegant' => __('Elegan / mewah', $t), 'modern' => __('Modern', $t), 'bold' => __('Bold & berwarna', $t),
                    'tropical' => __('Tropis', $t), 'balinese' => __('Tradisional Bali', $t), 'playful' => __('Ceria / playful', $t), 'vintage' => __('Vintage / retro', $t), 'corporate' => __('Korporat', $t),
                ]],
                ['key' => 'use_brand', 'label' => __('Warna & logo', $t), 'type' => 'radio', 'options' => [
                    'brand' => __('Pakai warna dan logo brand', $t), 'free' => __('Bebas, boleh palet baru', $t),
                ]],
                ['key' => 'avoid', 'label' => __('Yang perlu dihindari', $t), 'type' => 'text', 'placeholder' => __('mis. warna merah, font dekoratif', $t)],
                ['key' => 'reference_links', 'label' => __('Link referensi', $t), 'type' => 'textarea', 'placeholder' => __('Satu link per baris (Instagram, Pinterest, website)', $t)],
                ['key' => 'reference_files', 'label' => __('Gambar referensi', $t), 'type' => 'file', 'multiple' => true, 'accept' => 'image/*,application/pdf'],
                ['key' => 'asset_files', 'label' => __('Foto & aset untuk dipakai', $t), 'type' => 'file', 'multiple' => true, 'accept' => 'image/*,application/pdf,.zip', 'help' => __('Foto produk, foto tempat, logo partner. Maks. 10 MB per file.', $t)],
            ],
        ],
        'output' => [
            'title'  => __('File, jadwal & anggaran', $t),
            'fields' => [
                ['key' => 'outputs', 'label' => __('File yang dibutuhkan', $t), 'type' => 'checkboxes', 'options' => [
                    'print_pdf' => __('PDF siap cetak', $t), 'png' => __('PNG / JPG untuk digital', $t), 'social' => __('Ukuran media sosial', $t), 'source' => __('File sumber (editable)', $t),
                ]],
                ['key' => 'concepts', 'label' => __('Jumlah alternatif desain', $t), 'type' => 'select', 'width' => 'third', 'options' => ['1' => '1', '2' => '2', '3' => '3']],
                ['key' => 'printing', 'label' => __('Cetak', $t), 'type' => 'select', 'width' => 'third', 'options' => [
                    'agency' => __('Diurus Lightandpulse', $t), 'self' => __('Saya cetak sendiri', $t), 'digital' => __('Hanya digital', $t),
                ]],
                ['key' => 'deadline', 'label' => __('Deadline', $t), 'type' => 'date', 'width' => 'third', 'required' => true],
                ['key' => 'priority', 'label' => __('Prioritas', $t), 'type' => 'radio', 'width' => 'half', 'options' => ['normal' => __('Normal', $t), 'express' => __('Express (biaya tambahan)', $t)]],
                ['key' => 'budget', 'label' => __('Perkiraan anggaran', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'lt500' => '< Rp 500.000', '500_1m' => 'Rp 500.000 – 1 juta', '1_3m' => 'Rp 1 – 3 juta', 'gt3m' => '> Rp 3 juta', 'unknown' => __('Belum tahu', $t),
                ]],
                ['key' => 'notes', 'label' => __('Catatan lain', $t), 'type' => 'textarea'],
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
    if ($f['type'] === 'date') {
        return date_i18n(get_option('date_format'), strtotime($value));
    }
    return is_array($value) ? implode(', ', $value) : (string) $value;
}
