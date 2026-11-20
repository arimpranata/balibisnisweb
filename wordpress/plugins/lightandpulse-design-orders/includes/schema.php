<?php
/**
 * Field definitions. One schema drives the public order form, the admin
 * edit screen and the client portal summary.
 *
 * Field keys: key, label, type (text|textarea|email|tel|url|number|date|color|select|radio|checkboxes|file|repeater),
 * options, required, help, placeholder, width (half|third|full), multiple (file), accept (file),
 * columns, rows and add_label (repeater: a table the client fills row by row; each column
 * has key, label, type text|number|select, size, placeholder, options, required).
 * A group's or field's 'types' limits it to some design types; no 'types' means every type.
 */
defined('ABSPATH') || exit;

function lpdo_design_types(): array
{
    $t = 'lightandpulse-design-orders';
    return [
        'poster'        => ['label' => __('Poster', $t), 'desc' => __('Event, promo, standing frame', $t)],
        'flyer'         => ['label' => __('Flyer', $t), 'desc' => __('Promo bulanan, A5 / DL, cetak & digital', $t)],
        'menu'          => ['label' => __('Menu', $t), 'desc' => __('Food, drink, wine, set menu, spa', $t)],
        'banner'        => ['label' => __('Banner & signage', $t), 'desc' => __('X-banner, roll-up, spanduk, stiker', $t)],
        'backdrop'      => ['label' => __('Backdrop', $t), 'desc' => __('Acara, photo booth, panggung', $t)],
        'business_card' => ['label' => __('Kartu nama', $t), 'desc' => __('Untuk satu atau banyak orang', $t)],
        'greeting'      => ['label' => __('Kartu ucapan', $t), 'desc' => __('Natal, Tahun Baru, hari raya, e-card', $t)],
        'digital'       => ['label' => __('TV & media sosial', $t), 'desc' => __('Slide TV lobby, Instagram, web', $t)],
        'directory'     => ['label' => __('Room directory', $t), 'desc' => __('Info kamar, fasilitas, ekstensi', $t)],
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
                ['key' => 'phone', 'label' => __('Telepon kantor / reservasi', $t), 'type' => 'tel', 'width' => 'half', 'placeholder' => '+62 361 …'],
                ['key' => 'address', 'label' => __('Alamat bisnis (opsional)', $t), 'type' => 'textarea', 'help' => __('Untuk pengiriman hasil cetak atau tagihan.', $t)],
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
                ['key' => 'contact_print', 'label' => __('Kontak standar di desain', $t), 'type' => 'textarea', 'placeholder' => __("WhatsApp, telepon, email reservasi, website, alamat\nmis. +62 813 3874 6868 · fbsales@hotel.com · www.hotel.com · Jl. Danau Tamblingan 47, Sanur", $t), 'help' => __('Dipakai di setiap desain bila Anda memilih "Pakai kontak standar".', $t)],
                ['key' => 'brand_notes', 'label' => __('Catatan brand', $t), 'type' => 'textarea', 'placeholder' => __('Kepribadian brand, hal yang wajib atau dilarang, panduan brand yang sudah ada…', $t)],
            ],
        ],
    ];
}

/**
 * Order details. Stored on each order.
 *
 * Based on real hospitality work (event posters, monthly F&B flyers, drink
 * lists, banners, greeting cards, lobby TV slides, room directories): one
 * design often ships in several sizes, lists items with prices, and repeats
 * the venue's standard contact block.
 */
function lpdo_order_groups(): array
{
    $t = 'lightandpulse-design-orders';
    $printQty = fn(string $key, array $types = []) => ['key' => $key, 'label' => __('Jumlah cetak', $t), 'type' => 'number', 'width' => 'half', 'help' => __('Kosongkan bila hanya butuh file desain.', $t)] + ($types ? ['types' => $types] : []);
    $courses = [
        'starter' => __('Starter / appetizer', $t), 'soup' => __('Soup', $t), 'main' => __('Main course', $t), 'dessert' => __('Dessert', $t),
        'beverage' => __('Beverage', $t), 'other' => __('Lainnya', $t),
    ];

    return [
        'project' => [
            'title'  => __('Tentang proyek', $t),
            'fields' => [
                ['key' => 'title', 'label' => __('Judul proyek', $t), 'type' => 'text', 'required' => true, 'placeholder' => __('mis. Christmas Eve Dinner 2026, Happy Hour Agustus', $t)],
                ['key' => 'purpose', 'label' => __('Tujuan desain', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'promo' => __('Promo / happy hour', $t), 'event' => __('Acara / event', $t), 'seasonal' => __('Hari raya / musiman', $t),
                    'opening' => __('Opening / re-opening', $t), 'menu_update' => __('Menu baru / perubahan harga', $t), 'info' => __('Informasi tamu', $t),
                    'branding' => __('Branding umum', $t), 'other' => __('Lainnya', $t),
                ]],
                ['key' => 'outlet', 'label' => __('Outlet / venue', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. Starfish Beach Lounge, Junglia Restaurant', $t), 'help' => __('Isi bila desain untuk restoran, bar atau spa tertentu.', $t)],
                ['key' => 'outlet_logo', 'label' => __('Logo outlet / partner', $t), 'type' => 'file', 'multiple' => true, 'accept' => 'image/*,application/pdf', 'help' => __('Logo restoran/bar, sponsor atau brand partner (mis. Bintang, Coca-Cola).', $t)],
                ['key' => 'audience', 'label' => __('Target audiens', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. tamu hotel, turis keluarga, warga lokal', $t)],
                ['key' => 'languages', 'label' => __('Bahasa di desain', $t), 'type' => 'checkboxes', 'width' => 'half', 'options' => ['en' => 'English', 'id' => __('Indonesia', $t), 'other' => __('Lainnya', $t)]],
                ['key' => 'languages_other', 'label' => __('Bahasa lain', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. 中文, Русский', $t)],
            ],
        ],

        // ---------------------------------------------------------- poster & flyer
        'print_format' => [
            'title' => __('Ukuran & cetak', $t), 'types' => ['poster', 'flyer'],
            'intro' => __('Satu desain bisa dibuat dalam beberapa ukuran sekaligus.', $t),
            'fields' => [
                ['key' => 'sizes', 'label' => __('Ukuran yang dibutuhkan', $t), 'type' => 'checkboxes', 'options' => [
                    'a5' => 'A5', 'a4' => 'A4', 'a3' => 'A3', 'a2' => 'A2', 'dl' => 'DL 10 × 21 cm', 'frame' => __('Standing frame 67 × 92 cm', $t),
                    'ig_post' => __('Instagram post', $t), 'ig_story' => __('Instagram story', $t), 'tv' => __('Layar TV 16:9', $t), 'custom' => __('Ukuran lain', $t),
                ]],
                ['key' => 'sizes_custom', 'label' => __('Ukuran lain', $t), 'type' => 'text', 'placeholder' => __('mis. 50 × 70 cm, 21 × 21 cm', $t)],
                ['key' => 'orientation', 'label' => __('Orientasi', $t), 'type' => 'radio', 'width' => 'half', 'options' => ['portrait' => 'Portrait', 'landscape' => 'Landscape', 'square' => __('Persegi', $t)]],
                ['key' => 'flyer_sides', 'label' => __('Sisi cetak', $t), 'type' => 'radio', 'width' => 'half', 'types' => ['flyer'], 'options' => ['one' => __('1 sisi', $t), 'two' => __('2 sisi (bolak-balik)', $t)]],
                ['key' => 'flyer_fold', 'label' => __('Lipatan', $t), 'type' => 'radio', 'width' => 'half', 'types' => ['flyer'], 'options' => ['none' => __('Tanpa lipatan', $t), 'bi' => __('Lipat 2', $t), 'tri' => __('Lipat 3', $t)]],
                ['key' => 'poster_finish', 'label' => __('Finishing cetak', $t), 'type' => 'select', 'width' => 'half', 'types' => ['poster'], 'options' => [
                    'none' => __('Tanpa finishing', $t), 'glossy' => __('Glossy', $t), 'matte' => __('Doff / matte', $t), 'laminate' => __('Laminasi', $t), 'foamboard' => __('Ditempel foam board', $t),
                ]],
                ['key' => 'channels', 'label' => __('Dipakai di', $t), 'type' => 'checkboxes', 'options' => [
                    'print' => __('Cetak (di hotel/outlet)', $t), 'room' => __('Kamar tamu', $t), 'whatsapp' => 'WhatsApp', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'email' => 'Email', 'tv' => __('Layar TV', $t),
                ]],
                $printQty('print_qty'),
            ],
        ],
        'event' => [
            'title' => __('Acara', $t), 'types' => ['poster', 'flyer'],
            'intro' => __('Isi bila desain untuk acara. Satu desain bisa memuat beberapa acara (mis. Festive Season).', $t),
            'fields' => [
                ['key' => 'events', 'label' => __('Daftar acara', $t), 'type' => 'repeater', 'rows' => 1, 'add_label' => __('Tambah acara', $t), 'columns' => [
                    ['key' => 'name', 'label' => __('Nama acara', $t), 'type' => 'text', 'size' => 3, 'required' => true, 'placeholder' => __('mis. Christmas Eve Dinner', $t)],
                    ['key' => 'date', 'label' => __('Tanggal', $t), 'type' => 'text', 'size' => 2, 'placeholder' => __('24 Des 2026', $t)],
                    ['key' => 'time', 'label' => __('Jam', $t), 'type' => 'text', 'size' => 2, 'placeholder' => '19.00 – 21.30'],
                    ['key' => 'venue', 'label' => __('Tempat', $t), 'type' => 'text', 'size' => 2, 'placeholder' => __('mis. Wantilan', $t)],
                    ['key' => 'price', 'label' => __('Harga', $t), 'type' => 'text', 'size' => 2, 'placeholder' => '400.000++/pax'],
                    ['key' => 'desc', 'label' => __('Deskripsi singkat', $t), 'type' => 'text', 'size' => 4, 'placeholder' => __('mis. live acoustic, Balinese dance', $t)],
                ]],
                ['key' => 'inclusions', 'label' => __('Termasuk (inclusions)', $t), 'type' => 'textarea', 'placeholder' => __("Satu per baris, mis.\nSet menu Christmas Eve\nBalinese dance performance\nDoor prize: voucher spa", $t)],
                ['key' => 'set_menu', 'label' => __('Set menu acara (opsional)', $t), 'type' => 'repeater', 'rows' => 1, 'add_label' => __('Tambah hidangan', $t), 'columns' => [
                    ['key' => 'course', 'label' => __('Course', $t), 'type' => 'select', 'size' => 2, 'options' => $courses],
                    ['key' => 'name', 'label' => __('Nama hidangan', $t), 'type' => 'text', 'size' => 3, 'required' => true, 'placeholder' => __('mis. Junglia Crispy Duck', $t)],
                    ['key' => 'desc', 'label' => __('Deskripsi', $t), 'type' => 'text', 'size' => 5, 'placeholder' => __('bahan dan cara penyajian', $t)],
                ]],
                ['key' => 'price_basis', 'label' => __('Keterangan harga', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'plus' => __('++ (belum termasuk pajak & service)', $t), 'net' => __('Net (sudah termasuk)', $t), 'free' => __('Gratis / free entry', $t),
                ]],
                ['key' => 'kids_policy', 'label' => __('Ketentuan anak / grup', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. anak di bawah 7 tahun setengah harga', $t)],
            ],
        ],

        // ---------------------------------------------------------- menu
        'menu' => [
            'title' => __('Jenis & format menu', $t), 'types' => ['menu'],
            'fields' => [
                ['key' => 'menu_kind', 'label' => __('Jenis menu', $t), 'type' => 'select', 'width' => 'half', 'required' => true, 'options' => [
                    'food' => __('Food menu', $t), 'beverage' => __('Beverage / drink list', $t), 'wine' => __('Wine & spirits list', $t), 'set' => __('Set menu (acara)', $t),
                    'promo' => __('Happy hour / promo menu', $t), 'kids' => __('Kids menu', $t), 'ird' => __('In-room dining', $t), 'spa' => __('Spa menu / treatment list', $t),
                ]],
                ['key' => 'menu_format', 'label' => __('Format', $t), 'type' => 'select', 'width' => 'half', 'required' => true, 'options' => [
                    'single' => __('1 lembar', $t), 'bifold' => __('Lipat 2', $t), 'trifold' => __('Lipat 3', $t), 'booklet' => __('Buku menu (beberapa halaman)', $t),
                    'board' => __('Papan / menu board', $t), 'table_tent' => __('Table tent', $t), 'digital' => __('Digital / QR', $t),
                ]],
                ['key' => 'menu_size', 'label' => __('Ukuran', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'a4' => 'A4', 'a3' => 'A3', 'a5' => 'A5', '18x297' => '18 × 29,7 cm', '16x30' => '16 × 30 cm', '15x30' => '15 × 30 cm', 'dl' => 'DL 10 × 21 cm', 'custom' => __('Ukuran lain', $t),
                ]],
                ['key' => 'menu_size_custom', 'label' => __('Ukuran lain', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('lebar × tinggi', $t)],
                ['key' => 'menu_pages', 'label' => __('Jumlah halaman', $t), 'type' => 'number', 'width' => 'half'],
                ['key' => 'menu_material', 'label' => __('Material', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'paper' => __('Art paper', $t), 'laminate_matte' => __('Laminasi doff', $t), 'laminate_gloss' => __('Laminasi glossy', $t), 'pvc' => __('PVC / tahan air', $t),
                    'acrylic' => __('Akrilik', $t), 'wood' => __('Kayu / engraving', $t), 'leather' => __('Cover kulit / hard cover', $t), 'digital' => __('Digital saja', $t),
                ]],
                $printQty('menu_qty'),
            ],
        ],
        'menu_items' => [
            'title' => __('Daftar menu', $t), 'types' => ['menu'],
            'intro' => __('Satu baris untuk satu item. Kategori yang sama dikelompokkan di desain. Untuk wine/spirits, isi harga gelas dan botol.', $t),
            'fields' => [
                ['key' => 'menu_items', 'label' => __('Item menu', $t), 'type' => 'repeater', 'rows' => 3, 'add_label' => __('Tambah item menu', $t), 'columns' => [
                    ['key' => 'category', 'label' => __('Kategori', $t), 'type' => 'text', 'size' => 2, 'placeholder' => __('mis. Main course, Gin', $t)],
                    ['key' => 'name', 'label' => __('Nama menu', $t), 'type' => 'text', 'size' => 3, 'required' => true, 'placeholder' => __('mis. Nasi Campur Bali', $t)],
                    ['key' => 'ingredients', 'label' => __('Bahan / deskripsi', $t), 'type' => 'text', 'size' => 4, 'placeholder' => __('ayam betutu, sate lilit, sambal matah', $t)],
                    ['key' => 'price', 'label' => __('Harga', $t), 'type' => 'text', 'size' => 2, 'inputmode' => 'decimal', 'placeholder' => '65'],
                    ['key' => 'price2', 'label' => __('Harga 2 (botol)', $t), 'type' => 'text', 'size' => 2, 'inputmode' => 'decimal'],
                    ['key' => 'label', 'label' => __('Label', $t), 'type' => 'select', 'size' => 2, 'options' => [
                        'best' => __('Best seller', $t), 'chef' => __('Rekomendasi chef', $t), 'signature' => __('Signature', $t), 'new' => __('Baru', $t), 'spicy' => __('Pedas', $t),
                        'vegan' => 'Vegan', 'vegetarian' => 'Vegetarian', 'gf' => 'Gluten free', 'nuts' => __('Mengandung kacang', $t), 'seafood' => __('Seafood / prawn', $t), 'pork' => __('Mengandung babi', $t),
                    ]],
                ]],
                ['key' => 'menu_file', 'label' => __('Atau unggah daftar menu yang sudah ada', $t), 'type' => 'file', 'multiple' => true, 'accept' => '.pdf,.xlsx,.xls,.csv,.docx,.doc,.txt,image/*', 'help' => __('Excel, Word, PDF atau foto menu lama.', $t)],
                ['key' => 'menu_currency', 'label' => __('Mata uang', $t), 'type' => 'radio', 'width' => 'half', 'options' => ['idr' => 'IDR (Rp)', 'usd' => 'USD ($)', 'both' => __('Keduanya', $t), 'none' => __('Tanpa harga', $t)]],
                ['key' => 'menu_price_format', 'label' => __('Penulisan harga', $t), 'type' => 'radio', 'width' => 'half', 'options' => ['k' => __('Ribuan: 85 / 85K', $t), 'full' => __('Lengkap: 85.000', $t)]],
                ['key' => 'menu_price_note', 'label' => __('Keterangan pajak', $t), 'type' => 'radio', 'width' => 'half', 'options' => [
                    'plus' => __('Belum termasuk pajak & service (++)', $t), 'net' => __('Sudah termasuk (net)', $t), 'none' => __('Tidak perlu', $t),
                ]],
                ['key' => 'menu_tax', 'label' => __('Pajak & service (%)', $t), 'type' => 'number', 'width' => 'half', 'step' => '0.5', 'placeholder' => '21'],
                ['key' => 'menu_photos', 'label' => __('Foto makanan & minuman', $t), 'type' => 'radio', 'options' => [
                    'none' => __('Tanpa foto', $t), 'own' => __('Pakai foto dari kami', $t), 'shoot' => __('Perlu sesi foto', $t), 'stock' => __('Boleh foto stok / ilustrasi', $t),
                ]],
                ['key' => 'menu_photo_files', 'label' => __('Foto makanan & minuman', $t), 'type' => 'file', 'multiple' => true, 'accept' => 'image/*', 'help' => __('Beri nama file sesuai nama menu agar mudah dicocokkan, mis. nasi-campur.jpg.', $t)],
                ['key' => 'menu_extra', 'label' => __('Info tambahan di menu', $t), 'type' => 'textarea', 'placeholder' => __('Jam buka, info alergi, "prices are quoted in thousand IDR", tagline outlet…', $t)],
            ],
        ],

        // ---------------------------------------------------------- banner & signage
        'banner' => [
            'title' => __('Banner & signage', $t), 'types' => ['banner'],
            'intro' => __('Satu baris untuk satu media. Mis. X-banner, roll-up, stiker trolley atau pintu.', $t),
            'fields' => [
                ['key' => 'banner_pieces', 'label' => __('Media yang dibutuhkan', $t), 'type' => 'repeater', 'rows' => 1, 'required' => true, 'add_label' => __('Tambah media', $t), 'columns' => [
                    ['key' => 'kind', 'label' => __('Jenis', $t), 'type' => 'select', 'size' => 3, 'required' => true, 'options' => [
                        'xbanner' => 'X-banner 60 × 160', 'rollup' => 'Roll-up 80 × 200', 'rollup85' => 'Roll-up 85 × 200', 'frame' => __('Standing frame 67 × 92', $t),
                        'spanduk' => __('Spanduk / banner horizontal', $t), 'vertical' => __('Banner vertikal / umbul-umbul', $t), 'trolley' => __('Stiker trolley / booth', $t),
                        'door' => __('Stiker pintu / kaca', $t), 'signage' => __('Papan petunjuk / signage', $t), 'other' => __('Lainnya', $t),
                    ]],
                    ['key' => 'where', 'label' => __('Penempatan', $t), 'type' => 'text', 'size' => 3, 'placeholder' => __('mis. gelato trolley, pintu bar', $t)],
                    ['key' => 'w', 'label' => __('Lebar (cm)', $t), 'type' => 'number', 'size' => 1],
                    ['key' => 'h', 'label' => __('Tinggi (cm)', $t), 'type' => 'number', 'size' => 1],
                    ['key' => 'qty', 'label' => __('Jumlah', $t), 'type' => 'number', 'size' => 1],
                ]],
                ['key' => 'banner_material', 'label' => __('Material', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'flexi' => __('Flexi', $t), 'albatros' => 'Albatros', 'vinyl' => __('Stiker vinyl', $t), 'oneway' => __('One way vision (kaca)', $t),
                    'fabric' => __('Kain', $t), 'pvc' => __('PVC board / foam board', $t), 'unknown' => __('Belum tahu, minta saran', $t),
                ]],
                ['key' => 'banner_location', 'label' => __('Dipasang di', $t), 'type' => 'radio', 'width' => 'half', 'options' => ['indoor' => 'Indoor', 'outdoor' => 'Outdoor']],
                ['key' => 'banner_scope', 'label' => __('Lingkup pekerjaan', $t), 'type' => 'radio', 'options' => [
                    'design' => __('File desain saja', $t), 'print' => __('Desain + cetak', $t), 'install' => __('Desain + cetak + pemasangan', $t),
                ]],
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
                ['key' => 'card_size_custom', 'label' => __('Ukuran custom', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('lebar × tinggi', $t)],
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

        // ---------------------------------------------------------- greeting card
        'greeting' => [
            'title' => __('Kartu ucapan', $t), 'types' => ['greeting'],
            'fields' => [
                ['key' => 'greeting_occasion', 'label' => __('Momen', $t), 'type' => 'select', 'width' => 'half', 'required' => true, 'options' => [
                    'christmas' => __('Natal', $t), 'newyear' => __('Tahun Baru', $t), 'christmas_newyear' => __('Natal & Tahun Baru', $t), 'nyepi' => __('Nyepi', $t),
                    'galungan' => __('Galungan & Kuningan', $t), 'eid' => __('Idul Fitri', $t), 'cny' => __('Imlek', $t), 'independence' => __('17 Agustus', $t),
                    'thanks' => __('Terima kasih / welcome', $t), 'birthday' => __('Ulang tahun tamu', $t), 'other' => __('Lainnya', $t),
                ]],
                ['key' => 'greeting_formats', 'label' => __('Format', $t), 'type' => 'checkboxes', 'width' => 'half', 'options' => [
                    'card_fold' => __('Kartu cetak lipat', $t), 'card_flat' => __('Kartu cetak 1 lembar', $t), 'ig_post' => __('Instagram post', $t), 'ig_story' => __('Instagram story', $t),
                    'wa' => __('Gambar WhatsApp', $t), 'email' => __('Email / e-card', $t),
                ]],
                ['key' => 'greeting_headline', 'label' => __('Ucapan utama', $t), 'type' => 'text', 'placeholder' => __('mis. Merry Christmas and a Happy New Year', $t)],
                ['key' => 'greeting_message', 'label' => __('Isi pesan', $t), 'type' => 'textarea', 'placeholder' => __('mis. Dear Valued Guests, thank you for your trust and support throughout the year…', $t)],
                ['key' => 'greeting_signoff', 'label' => __('Penutup & pengirim', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('Warm regards, Hoshi Jungle Resort', $t)],
                ['key' => 'greeting_signature', 'label' => __('Tanda tangan / nama manajemen', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. General Manager & team', $t)],
                $printQty('greeting_qty'),
            ],
        ],

        // ---------------------------------------------------------- TV & social media
        'digital' => [
            'title' => __('Layar TV & media sosial', $t), 'types' => ['digital'],
            'fields' => [
                ['key' => 'digital_formats', 'label' => __('Format', $t), 'type' => 'checkboxes', 'options' => [
                    'tv_l' => __('TV landscape 1920 × 1080', $t), 'tv_p' => __('TV portrait 1080 × 1920', $t), 'ig_post' => __('Instagram post 1080 × 1350', $t), 'ig_story' => __('Story / reels 1080 × 1920', $t),
                    'fb' => __('Facebook post / cover', $t), 'web' => __('Banner website', $t), 'email' => __('Header email', $t), 'wa' => __('WhatsApp status / broadcast', $t),
                ]],
                ['key' => 'digital_screen', 'label' => __('Layar / lokasi tayang', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. TV lobby 55", TV restoran', $t)],
                ['key' => 'digital_motion', 'label' => __('Gerak', $t), 'type' => 'radio', 'width' => 'half', 'options' => ['static' => __('Gambar diam', $t), 'animated' => __('Animasi / video pendek', $t)]],
                ['key' => 'digital_slides', 'label' => __('Slide', $t), 'type' => 'repeater', 'rows' => 2, 'add_label' => __('Tambah slide', $t), 'columns' => [
                    ['key' => 'title', 'label' => __('Judul slide', $t), 'type' => 'text', 'size' => 3, 'required' => true, 'placeholder' => __('mis. Balinese Cultural Night Dinner', $t)],
                    ['key' => 'text', 'label' => __('Teks', $t), 'type' => 'text', 'size' => 5, 'placeholder' => __('mis. Every Thursday 7 PM at Wantilan', $t)],
                    ['key' => 'price', 'label' => __('Harga / info', $t), 'type' => 'text', 'size' => 2, 'placeholder' => '250.000 net'],
                    ['key' => 'secs', 'label' => __('Detik', $t), 'type' => 'number', 'size' => 1, 'placeholder' => '10'],
                ]],
            ],
        ],

        // ---------------------------------------------------------- room directory
        'directory' => [
            'title' => __('Room directory', $t), 'types' => ['directory'],
            'fields' => [
                ['key' => 'dir_format', 'label' => __('Format', $t), 'type' => 'select', 'width' => 'half', 'required' => true, 'options' => [
                    'booklet_a5' => __('Buku A5', $t), 'booklet_a4' => __('Buku / map A4', $t), 'flip' => __('Flip stand meja', $t), 'single' => __('1 lembar / papan', $t), 'digital' => __('Digital (PDF / QR)', $t),
                ]],
                ['key' => 'dir_pages', 'label' => __('Perkiraan jumlah halaman', $t), 'type' => 'number', 'width' => 'half'],
                ['key' => 'dir_sections', 'label' => __('Isi yang dibutuhkan', $t), 'type' => 'checkboxes', 'options' => [
                    'welcome' => __('Surat selamat datang', $t), 'facilities' => __('Fasilitas hotel', $t), 'dining' => __('Restoran & bar', $t), 'ird' => __('In-room dining', $t),
                    'spa' => __('Spa', $t), 'activities' => __('Aktivitas & tur', $t), 'housekeeping' => __('Housekeeping & laundry', $t), 'wifi' => 'Wi-Fi',
                    'phones' => __('Nomor telepon / ekstensi', $t), 'safety' => __('Keselamatan & evakuasi', $t), 'policy' => __('Kebijakan hotel', $t), 'map' => __('Peta resort', $t),
                ]],
                ['key' => 'dir_hours', 'label' => __('Fasilitas & jam buka', $t), 'type' => 'repeater', 'rows' => 2, 'add_label' => __('Tambah fasilitas', $t), 'columns' => [
                    ['key' => 'name', 'label' => __('Fasilitas', $t), 'type' => 'text', 'size' => 3, 'required' => true, 'placeholder' => __('mis. Wantilan Restaurant', $t)],
                    ['key' => 'hours', 'label' => __('Jam buka', $t), 'type' => 'text', 'size' => 2, 'placeholder' => '07.00 – 23.00'],
                    ['key' => 'where', 'label' => __('Lokasi', $t), 'type' => 'text', 'size' => 2, 'placeholder' => __('mis. tepi pantai', $t)],
                    ['key' => 'note', 'label' => __('Keterangan', $t), 'type' => 'text', 'size' => 3],
                ]],
                ['key' => 'dir_phones', 'label' => __('Nomor telepon / ekstensi', $t), 'type' => 'repeater', 'rows' => 2, 'add_label' => __('Tambah nomor', $t), 'columns' => [
                    ['key' => 'service', 'label' => __('Layanan', $t), 'type' => 'text', 'size' => 3, 'required' => true, 'placeholder' => __('mis. Reception', $t)],
                    ['key' => 'ext', 'label' => __('Ekstensi / nomor', $t), 'type' => 'text', 'size' => 2, 'placeholder' => '0'],
                ]],
                ['key' => 'dir_wifi', 'label' => __('Wi-Fi', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('Nama jaringan / password', $t)],
                ['key' => 'dir_files', 'label' => __('Teks atau brosur yang sudah ada', $t), 'type' => 'file', 'multiple' => true, 'accept' => '.pdf,.docx,.doc,.txt,image/*'],
            ],
        ],

        // ---------------------------------------------------------- items with prices (promos, banners, screens)
        'items' => [
            'title' => __('Promo & item yang ditampilkan', $t), 'types' => ['poster', 'flyer', 'banner', 'digital'],
            'fields' => [
                ['key' => 'promo_title', 'label' => __('Promo utama', $t), 'type' => 'text', 'placeholder' => __('mis. Buy 2 get 1 free for beverages', $t)],
                ['key' => 'items', 'label' => __('Item / aktivitas', $t), 'type' => 'repeater', 'rows' => 2, 'add_label' => __('Tambah item', $t), 'columns' => [
                    ['key' => 'category', 'label' => __('Kategori', $t), 'type' => 'text', 'size' => 2, 'placeholder' => __('mis. Signature cocktail', $t)],
                    ['key' => 'name', 'label' => __('Nama', $t), 'type' => 'text', 'size' => 3, 'required' => true, 'placeholder' => __('mis. Espresso Martini', $t)],
                    ['key' => 'desc', 'label' => __('Deskripsi / bahan', $t), 'type' => 'text', 'size' => 4, 'placeholder' => __('vodka, coffee, hazelnut', $t)],
                    ['key' => 'price', 'label' => __('Harga', $t), 'type' => 'text', 'size' => 2, 'inputmode' => 'decimal', 'placeholder' => '85K'],
                    ['key' => 'promo', 'label' => __('Harga promo', $t), 'type' => 'text', 'size' => 2, 'inputmode' => 'decimal'],
                ]],
                ['key' => 'promo_period', 'label' => __('Berlaku', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. Setiap hari 16.00 – 19.00, 1–31 Agustus', $t)],
                ['key' => 'promo_terms', 'label' => __('Syarat & ketentuan', $t), 'type' => 'text', 'width' => 'half'],
            ],
        ],

        // ---------------------------------------------------------- shared text
        'content' => [
            'title'  => __('Teks di desain', $t), 'types' => ['poster', 'flyer', 'banner', 'digital'],
            'intro'  => __('Tulis persis seperti yang ingin dicetak. Kami akan merapikan tata letaknya.', $t),
            'fields' => [
                ['key' => 'headline', 'label' => __('Judul utama (headline)', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. Festive Season', $t)],
                ['key' => 'subheadline', 'label' => __('Sub-judul', $t), 'type' => 'text', 'width' => 'half'],
                ['key' => 'body_text', 'label' => __('Teks isi', $t), 'type' => 'textarea'],
                ['key' => 'cta', 'label' => __('Ajakan (call to action)', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. Reserve your table now', $t)],
            ],
        ],
        'contact' => [
            'title'  => __('Kontak & QR di desain', $t), 'types' => ['poster', 'flyer', 'menu', 'banner', 'digital', 'greeting', 'backdrop'],
            'fields' => [
                ['key' => 'contact_mode', 'label' => __('Kontak yang dicetak', $t), 'type' => 'radio', 'options' => [
                    'standard' => __('Pakai kontak standar dari branding', $t), 'custom' => __('Kontak khusus untuk desain ini', $t), 'none' => __('Tanpa kontak', $t),
                ]],
                ['key' => 'contact_text', 'label' => __('Kontak khusus', $t), 'type' => 'textarea', 'placeholder' => __('WhatsApp, telepon, email, website, alamat persis seperti yang ingin tampil', $t)],
                ['key' => 'qr', 'label' => __('QR code', $t), 'type' => 'select', 'width' => 'half', 'options' => [
                    'none' => __('Tidak perlu', $t), 'whatsapp' => __('Ke WhatsApp reservasi', $t), 'website' => __('Ke website', $t), 'menu' => __('Ke menu digital', $t),
                    'maps' => __('Ke Google Maps', $t), 'instagram' => 'Instagram', 'custom' => __('Link lain', $t),
                ]],
                ['key' => 'qr_url', 'label' => __('Link QR', $t), 'type' => 'url', 'width' => 'half', 'placeholder' => 'https://'],
            ],
        ],

        // ---------------------------------------------------------- shared
        'style' => [
            'title'  => __('Gaya, referensi & file', $t),
            'fields' => [
                ['key' => 'style', 'label' => __('Gaya yang diinginkan', $t), 'type' => 'checkboxes', 'options' => [
                    'elegant' => __('Elegan / mewah', $t), 'minimal' => __('Minimalis', $t), 'modern' => __('Modern', $t), 'tropical' => __('Tropis', $t),
                    'balinese' => __('Tradisional Bali', $t), 'festive' => __('Festive / perayaan', $t), 'bold' => __('Bold & berwarna', $t), 'playful' => __('Ceria / playful', $t),
                    'vintage' => __('Vintage / retro', $t), 'dark' => __('Gelap & dramatis', $t), 'corporate' => __('Korporat', $t),
                ]],
                ['key' => 'use_brand', 'label' => __('Warna & logo', $t), 'type' => 'radio', 'options' => [
                    'brand' => __('Pakai warna dan logo brand', $t), 'outlet' => __('Pakai gaya outlet (lihat logo outlet)', $t), 'free' => __('Bebas, boleh palet baru', $t),
                ]],
                ['key' => 'previous', 'label' => __('Lanjutkan desain sebelumnya?', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. sama seperti flyer Juli, ganti isi saja', $t)],
                ['key' => 'avoid', 'label' => __('Yang perlu dihindari', $t), 'type' => 'text', 'width' => 'half', 'placeholder' => __('mis. warna merah, font dekoratif', $t)],
                ['key' => 'reference_links', 'label' => __('Link referensi', $t), 'type' => 'text', 'placeholder' => __('Canva, Instagram, Pinterest, website', $t)],
                ['key' => 'reference_files', 'label' => __('Gambar referensi', $t), 'type' => 'file', 'multiple' => true, 'accept' => 'image/*,application/pdf', 'help' => __('Desain yang Anda suka, atau desain lama yang ingin diperbarui.', $t)],
                ['key' => 'asset_files', 'label' => __('Foto & aset untuk dipakai', $t), 'type' => 'file', 'multiple' => true, 'accept' => 'image/*,application/pdf,.zip', 'help' => __('Foto makanan, minuman, kamar, venue, talent atau produk.', $t)],
                ['key' => 'notes', 'label' => __('Catatan lain', $t), 'type' => 'textarea', 'placeholder' => __('Hal lain yang perlu kami ketahui', $t)],
            ],
        ],
    ];
}

/** A field may be limited to some design types with its own 'types'. */
function lpdo_field_applies(array $f, string $type): bool
{
    return empty($f['types']) || in_array($type, $f['types'], true);
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
            if (lpdo_field_applies($f, $type)) {
                $out[$f['key']] = $f;
            }
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
