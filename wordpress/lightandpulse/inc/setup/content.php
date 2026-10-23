<?php
/**
 * Starter content for lightandpulse.com: pages, sections and copy.
 *
 * Copy is deliberately factual (services, process, how you work). There are
 * no client names, testimonials, prices or statistics. Add those in
 * Elementor once you have them.
 */
defined('ABSPATH') || exit;

function lightandpulse_eyebrow(string $text): array
{
    return ['text', '<p>' . esc_html($text) . '</p>', 'lp-eyebrow'];
}

function lightandpulse_checklist(array $items): string
{
    return '<ul class="lp-checklist">' . implode('', array_map(fn($i) => '<li>' . $i . '</li>', $items)) . '</ul>';
}

/** Small dark page header used on inner pages. */
function lightandpulse_page_hero(string $eyebrow, string $title, string $intro): array
{
    return [
        'class' => 'lp-band lp-page-hero',
        'cols'  => [[
            lightandpulse_eyebrow($eyebrow),
            ['heading', $title, 'h1', 'lp-display'],
            ['text', '<p>' . $intro . '</p>', 'lp-lead'],
        ]],
    ];
}

function lightandpulse_cta_band(string $title, string $text, string $button, string $url = '/contact/#contact-form'): array
{
    return [
        'class' => 'lp-band lp-cta',
        'sizes' => [62, 38],
        'cols'  => [
            [['heading', $title, 'h2', 'lp-h2'], ['text', '<p>' . $text . '</p>', 'lp-lead']],
            [['button', $button, $url, 'lp-btn-primary lp-btn-lg']],
        ],
    ];
}

/**
 * Decorative ASCII "pulse line" for the hero, in the spirit of OpenClaw's ASCII art.
 * Drawn from a heartbeat curve, so the output is the same every time.
 */
function lightandpulse_ascii_pulse(int $cols = 150, int $rows = 13): string
{
    $ramp = ' .:-=+*x#%@';
    $mid = ($rows - 1) / 2;
    $curve = function (float $t) use ($mid): float {
        $g = fn(float $c, float $w) => exp(-(($t - $c) / $w) ** 2);
        return $mid - 5.6 * $g(0.5, 0.011) + 4.2 * $g(0.523, 0.011) - 1.4 * $g(0.44, 0.02) - 1.0 * $g(0.6, 0.026) + 0.35 * sin($t * 46);
    };
    $lines = [];
    for ($r = 0; $r < $rows; $r++) {
        $line = '';
        for ($x = 0; $x < $cols; $x++) {
            $t = $x / ($cols - 1);
            // Distance from this cell to the curve segment around x (keeps steep spikes continuous).
            $y1 = $curve(max(0, ($x - 0.5) / ($cols - 1)));
            $y2 = $curve(min(1, ($x + 0.5) / ($cols - 1)));
            [$lo, $hi] = [min($y1, $y2), max($y1, $y2)];
            $dist = $r < $lo ? $lo - $r : ($r > $hi ? $r - $hi : 0);
            $edge = sin(M_PI * $t) ** 0.7;
            $glow = max(0, 1 - $dist / 2.4) ** 1.8;
            // Sparse static "noise" so the band reads as a field, not a single line.
            $h = ($x * 2654435761 + $r * 40503) % 1000;
            $noise = $h < 45 ? 0.1 + ($h % 3) * 0.05 : 0;
            $v = min(1, ($glow + $noise) * $edge);
            $line .= $ramp[(int) round($v * (strlen($ramp) - 1))];
        }
        $lines[] = rtrim($line);
    }
    return '<pre class="lp-ascii" aria-hidden="true">' . esc_html(implode("\n", $lines)) . '</pre>';
}

/** All starter pages, keyed by slug. */
function lightandpulse_starter_pages(): array
{
    $contact = '/contact/';
    $check = '/contact/#contact-form';

    return [
        // ------------------------------------------------------------------ HOME
        'home' => [
            'title'    => 'Home',
            'sections' => [
                [
                    'class' => 'lp-band lp-hero',
                    'cols'  => [[
                        ['html', lightandpulse_ascii_pulse()],
                        lightandpulse_eyebrow('Digital agency from Bali · since 2012'),
                        ['heading', 'Brightens up <em>your brand.</em>', 'h1', 'lp-display'],
                        ['text', '<p>Lightandpulse is a digital agency from Bali, Indonesia. Since 2012 we’ve combined web development, graphic design and digital marketing to help Bali businesses look their best and win more direct bookings and enquiries.</p>', 'lp-lead'],
                        ['button', 'Start a project', $contact, 'lp-btn-primary lp-btn-lg'],
                        ['button', 'Free website check', $check, 'lp-btn-ghost lp-btn-lg'],
                        ['text', '<p>Chat with us on WhatsApp in Bahasa Indonesia or English.</p>', 'lp-note'],
                    ]],
                ],
                [
                    'class' => 'lp-band lp-stack-strip',
                    'cols'  => [[
                        ['text', '<p class="lp-stack-label">Web development · Graphic design · Digital marketing</p><ul class="lp-stack"><li>WordPress</li><li>Elementor</li><li>WooCommerce</li><li>Brand identity</li><li>Google Business Profile</li><li>Google &amp; Meta Ads</li><li>WhatsApp Business</li><li>Midtrans · Xendit</li></ul>'],
                    ]],
                ],
                [
                    'class' => 'lp-band lp-sec-head',
                    'cols'  => [[
                        lightandpulse_eyebrow('What we do'),
                        ['heading', 'Web development, graphic design <em>and digital marketing.</em>', 'h2', 'lp-h2'],
                        ['text', '<p>Websites that mix current technology with simple design, brand work from identity to promotional kits, and advertising through online channels. Everything your brand needs, from one agency.</p>', 'lp-lead'],
                    ]],
                ],
                [
                    'class' => 'lp-band lp-grid-band',
                    'cols'  => [
                        [['card', 'globe', 'Web development', 'Fast, mobile-first WordPress websites built with Elementor: current technology, simple design, easy for you to update.'],
                         ['card', 'calendar', 'Direct booking', 'Booking engines and enquiry forms that cut OTA commission and send guests straight to you.'],
                         ['card', 'cart', 'WooCommerce stores', 'Sell at home and abroad with QRIS, bank transfer and cards, plus automatic shipping rates.']],
                        [['card', 'palette', 'Graphic design', 'Brand identity, from logo, colours and type to the guidelines that keep your brand consistent.'],
                         ['card', 'layers', 'Promotional kits', 'Menus, brochures, signage and social media templates designed to match your brand.'],
                         ['card', 'pen', 'Redesigns', 'Turn a dated or Wix-built site into a modern WordPress site that matches your brand.']],
                        [['card', 'megaphone', 'Digital marketing', 'Advertising through online channels: Google and Meta ads aimed at the travellers and locals you want.'],
                         ['card', 'search', 'Local SEO', 'Show up when people search Google and Google Maps for what you offer in your area.'],
                         ['card', 'shield', 'Care plans', 'Updates, backups, security monitoring and small edits every month, handled for you.']],
                    ],
                ],
                [
                    // Shows the latest works once some exist; the band hides itself while empty.
                    'class' => 'lp-band lp-works-band',
                    'cols'  => [[['shortcode', '[lightandpulse_works_section limit="6"]']]],
                ],
                [
                    'class' => 'lp-band lp-soft lp-industries',
                    'sizes' => [42, 58],
                    'cols'  => [
                        [lightandpulse_eyebrow('Who we build for'), ['heading', 'Made for the way <em>Bali</em> does business.', 'h2', 'lp-h2'],
                         ['text', '<p>Most of our work is for hospitality, wellness and creative businesses that win customers from Google Maps, Instagram and WhatsApp. We build sites that connect all three.</p>']],
                        [['text', '<ul class="lp-chips"><li>Villas &amp; boutique hotels</li><li>Restaurants, cafés &amp; beach clubs</li><li>Spas, yoga &amp; retreats</li><li>Surf, dive &amp; tour operators</li><li>Fashion, jewelry &amp; handicraft</li><li>Real estate &amp; property</li><li>Clinics &amp; wellness</li><li>Coworking &amp; education</li></ul>']],
                    ],
                ],
                [
                    'class' => 'lp-band lp-sec-head',
                    'cols'  => [[lightandpulse_eyebrow('How it works'), ['heading', 'A clear process, <em>first chat to launch.</em>', 'h2', 'lp-h2']]],
                ],
                [
                    'class' => 'lp-band lp-grid-band lp-steps',
                    'cols'  => [
                        [['text', '<span class="lp-step-num">01</span><h3>Discover</h3><p>A short call or WhatsApp chat about your goals, customers and budget. You get a clear proposal and timeline.</p>', 'lp-step']],
                        [['text', '<span class="lp-step-num">02</span><h3>Design</h3><p>We design the key pages in your brand and refine them with you before anything is built.</p>', 'lp-step']],
                        [['text', '<span class="lp-step-num">03</span><h3>Build</h3><p>We build in WordPress and Elementor, connect booking or payments, and tune it for speed and search.</p>', 'lp-step']],
                        [['text', '<span class="lp-step-num">04</span><h3>Launch &amp; care</h3><p>We launch, show your team how to update content, and stay on hand with a monthly care plan.</p>', 'lp-step']],
                    ],
                ],
                [
                    'class' => 'lp-band lp-soft lp-why',
                    'sizes' => [45, 55],
                    'cols'  => [
                        [lightandpulse_eyebrow('Why Lightandpulse'), ['heading', 'A Bali agency since 2012, <em>still answering on WhatsApp.</em>', 'h2', 'lp-h2']],
                        [['text', lightandpulse_checklist([
                            '<b>Since 2012.</b> We started with a small office and team in Denpasar, and have been building for Bali businesses ever since.',
                            '<b>Developers with a design eye.</b> The people who build your website also care how it looks and reads.',
                            '<b>Same care for every project.</b> From small to large, the same approach and the same focus on quality of service.',
                            '<b>WhatsApp-first.</b> Quick answers from the people working on your project, no ticket queues.',
                            '<b>Bahasa Indonesia and English.</b> For you, your team and your international customers.',
                            '<b>You own everything.</b> Your domain, website and content stay in your name. No lock-in.',
                            '<b>Built on WordPress.</b> The world’s most widely used website platform, so your site is never a dead end.',
                        ])]],
                    ],
                ],
                lightandpulse_cta_band('Is your website <em>costing you bookings?</em>', 'Send us your website, or your Google Maps listing if you don’t have a site yet. We’ll reply with a free, practical check of speed, mobile, search and booking flow.', 'Get my free website check'),
            ],
        ],

        // ------------------------------------------------------------------ SERVICES
        'services' => [
            'title'    => 'Services',
            'sections' => [
                lightandpulse_page_hero('Services', 'Web development, design <em>and marketing.</em>', 'Three services that work together: websites built on WordPress, Elementor and WooCommerce, graphic design from identity to promotional kits, and advertising through online channels.'),
                ['class' => 'lp-band lp-service', 'id' => 'websites', 'sizes' => [45, 55], 'cols' => [
                    [['card', 'globe', 'WordPress websites', 'A new website designed around how your customers find and choose you.', 'lp-card-plain']],
                    [['text', lightandpulse_checklist(['Custom design in your brand, built with Elementor', 'Mobile-first and fast on Indonesian mobile networks', 'WhatsApp button, enquiry forms and Google Maps', 'English and Bahasa Indonesia (more languages on request)', 'Training so your team can edit pages and post updates'])]],
                ]],
                ['class' => 'lp-band lp-soft lp-service', 'id' => 'booking', 'sizes' => [45, 55], 'cols' => [
                    [['card', 'calendar', 'Direct booking', 'Take reservations on your own site and keep the commission.', 'lp-card-plain']],
                    [['text', lightandpulse_checklist(['Booking engines for villas, hotels, tours and classes', 'Availability calendars and instant enquiries', 'Payments by card, bank transfer or QRIS', 'Confirmation emails and WhatsApp notifications', 'Connect your channel manager where supported'])]],
                ]],
                ['class' => 'lp-band lp-service', 'id' => 'woocommerce', 'sizes' => [45, 55], 'cols' => [
                    [['card', 'cart', 'WooCommerce stores', 'Sell products online, in Indonesia and abroad.', 'lp-card-plain']],
                    [['text', lightandpulse_checklist(['Product catalogue, variations and stock', 'Indonesian payment gateways (Midtrans, Xendit) and international cards', 'Local and international shipping rates', 'Order emails, invoices and customer accounts', 'Wholesale or B2B price lists for exporters'])]],
                ]],
                ['class' => 'lp-band lp-soft lp-service', 'id' => 'graphic-design', 'sizes' => [45, 55], 'cols' => [
                    [['card', 'palette', 'Graphic design', 'From brand identity to promotional kits, so your business looks the same everywhere.', 'lp-card-plain']],
                    [['text', lightandpulse_checklist(['Logo and brand identity', 'Colour, type and brand guidelines', 'Menus, brochures and price lists', 'Signage, banners and print-ready files', 'Social media templates'])]],
                ]],
                ['class' => 'lp-band lp-service', 'id' => 'digital-marketing', 'sizes' => [45, 55], 'cols' => [
                    [['card', 'megaphone', 'Digital marketing', 'Advertising through online channels, aimed at the customers you want.', 'lp-card-plain']],
                    [['text', lightandpulse_checklist(['Google Ads and Google Maps promotion', 'Meta ads on Facebook and Instagram', 'Campaigns for high and low season', 'Conversion tracking from click to enquiry', 'A clear monthly report'])]],
                ]],
                ['class' => 'lp-band lp-service', 'id' => 'redesign', 'sizes' => [45, 55], 'cols' => [
                    [['card', 'pen', 'Redesign & migration', 'Give an older site, or one on Wix or Blogger, a modern WordPress home.', 'lp-card-plain']],
                    [['text', lightandpulse_checklist(['New design with your existing content moved across', 'Redirects so you keep your Google rankings', 'Faster hosting setup and image optimisation', 'Clean, editable Elementor pages instead of hard-coded templates'])]],
                ]],
                ['class' => 'lp-band lp-soft lp-service', 'id' => 'seo', 'sizes' => [45, 55], 'cols' => [
                    [['card', 'search', 'Local SEO', 'Be found by travellers and locals searching near you.', 'lp-card-plain']],
                    [['text', lightandpulse_checklist(['Google Business Profile setup and optimisation', 'Page titles, descriptions and structured data', 'Speed and Core Web Vitals improvements', 'Content plan for the searches your customers make'])]],
                ]],
                ['class' => 'lp-band lp-service', 'id' => 'care', 'sizes' => [45, 55], 'cols' => [
                    [['card', 'shield', 'Care plans', 'Keep your website secure, backed up and up to date every month.', 'lp-card-plain']],
                    [['text', lightandpulse_checklist(['WordPress, theme and plugin updates', 'Daily backups and uptime monitoring', 'Security scanning and fixes', 'Small content edits each month', 'A short monthly report'])]],
                ]],
                lightandpulse_cta_band('Not sure <em>what you need?</em>', 'Tell us about your business and we’ll suggest the simplest setup that gets you more enquiries.', 'Talk to us', $contact),
            ],
        ],

        // ------------------------------------------------------------------ PACKAGES
        'packages' => [
            'title'    => 'Packages',
            'sections' => [
                lightandpulse_page_hero('Packages', 'Pick a starting point. <em>We’ll tailor it.</em>', 'Every business is different, so we quote after a short chat. Tell us what you need and you’ll get a clear, fixed quote.'),
                ['class' => 'lp-band lp-grid-band lp-packages', 'cols' => [
                    [['text', '<h3>Starter website</h3><p>For new businesses that need a professional presence fast.</p>' . lightandpulse_checklist(['Up to 5 pages', 'Mobile-first design', 'WhatsApp button and enquiry form', 'Google Business Profile setup', 'Basic SEO']), 'lp-package']],
                    [['text', '<h3>Business website</h3><p>For established businesses that want more enquiries and bookings.</p>' . lightandpulse_checklist(['Up to 12 pages', 'Custom Elementor design', 'Blog or news section', 'English and Bahasa Indonesia', 'Booking or enquiry system', 'Analytics setup']), 'lp-package lp-package-featured']],
                    [['text', '<h3>Online store</h3><p>For brands that sell products in Indonesia and abroad.</p>' . lightandpulse_checklist(['WooCommerce store', 'Product catalogue setup', 'Local and international payments', 'Shipping rates and order emails', 'Customer accounts']), 'lp-package']],
                    [['text', '<h3>Care plan</h3><p>For any WordPress site, including ones we didn’t build.</p>' . lightandpulse_checklist(['Monthly updates', 'Daily backups', 'Security and uptime monitoring', 'Small edits each month', 'Monthly report']), 'lp-package']],
                ]],
                ['class' => 'lp-band lp-center-band', 'cols' => [[['button', 'Ask for a quote', $contact, 'lp-btn-primary lp-btn-lg']]]],
                lightandpulse_cta_band('Already have <em>a website?</em>', 'We can check it for free and tell you what would make the biggest difference.', 'Get my free website check'),
            ],
        ],

        // ------------------------------------------------------------------ WORKS
        'works' => [
            'title'    => 'Works',
            'sections' => [
                lightandpulse_page_hero('Works', 'Websites we’ve <em>built and launched.</em>', 'A selection of WordPress, Elementor and WooCommerce projects for businesses in Bali and beyond. Each card opens the live website.'),
                ['class' => 'lp-band lp-works-page', 'cols' => [[['shortcode', '[lightandpulse_works filter="yes"]']]]],
                lightandpulse_cta_band('Want your business <em>on this page?</em>', 'Tell us what you’re planning and we’ll show you how we’d approach it.', 'Start a project', '/contact/'),
            ],
        ],

        // ------------------------------------------------------------------ ABOUT
        'about' => [
            'title'    => 'About',
            'sections' => [
                lightandpulse_page_hero('About', 'Your future <em>digital agency.</em>', 'Established in 2012, Lightandpulse offers the most appropriate and conceptually-based services in web development, graphic design and digital marketing.'),
                ['class' => 'lp-band', 'sizes' => [42, 58], 'cols' => [
                    [lightandpulse_eyebrow('Our story'), ['heading', 'From a small office <em>in Denpasar.</em>', 'h2', 'lp-h2']],
                    [['text', '<p>Lightandpulse is a digital agency from Bali, Indonesia. We started in 2012 with a small office and a small team in Denpasar, building websites for local businesses.</p><p>Today we’re a team of developers with a design eye: the people who build your website also care how your brand looks and reads, online and in print.</p><p>Small projects or large ones, we work the same way, with the same focus on quality of service.</p>']],
                ]],
                ['class' => 'lp-band lp-sec-head', 'cols' => [[lightandpulse_eyebrow('What we do'), ['heading', 'Three services, <em>one brand.</em>', 'h2', 'lp-h2']]]],
                ['class' => 'lp-band lp-grid-band', 'cols' => [
                    [['card', 'globe', 'Web development', 'A mix of current technology and simple design: WordPress, Elementor and WooCommerce websites that are fast and easy to run.']],
                    [['card', 'palette', 'Graphic design', 'From brand identity to promotional kits, so your business looks consistent everywhere it appears.']],
                    [['card', 'megaphone', 'Digital marketing', 'Advertising through online channels, aimed at the customers you want and measured from click to enquiry.']],
                ]],
                lightandpulse_cta_band('Let’s brighten up <em>your brand.</em>', 'Tell us where you are today and where you want to be.', 'Start a project', $contact),
            ],
        ],

        // ------------------------------------------------------------------ CONTACT
        'contact' => [
            'title'    => 'Contact',
            'sections' => [
                lightandpulse_page_hero('Contact', 'Let’s talk about <em>your website.</em>', 'Send a message and we’ll reply on WhatsApp or email, usually within one working day.'),
                ['class' => 'lp-band lp-contact', 'id' => 'contact-form', 'sizes' => [38, 62], 'cols' => [
                    [['heading', 'Reach us directly', 'h2', 'lp-h3'], ['shortcode', '[lightandpulse_contact_info]'], ['text', '<p>Want a free website check? Add your website address (or Google Maps link) in the form and we’ll take a look.</p>', 'lp-note']],
                    [['form', 'Send message']],
                ]],
            ],
        ],

        // ------------------------------------------------------------------ BLOG
        'insights' => [
            'title'    => 'Insights',
            'sections' => null, // posts page: WordPress lists your blog posts here
        ],
    ];
}
