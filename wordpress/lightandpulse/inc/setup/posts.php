<?php
/**
 * Starter blog posts for the Insights page, written as normal WordPress
 * blocks so they can be edited in the block editor (or rebuilt in Elementor).
 */
defined('ABSPATH') || exit;

/** Block markup helpers. */
function lightandpulse_b_h(string $text, int $level = 2): string
{
    return "<!-- wp:heading" . ($level !== 2 ? ' {"level":' . $level . '}' : '') . " -->\n<h$level class=\"wp-block-heading\">$text</h$level>\n<!-- /wp:heading -->\n\n";
}

function lightandpulse_b_p(string $html): string
{
    return "<!-- wp:paragraph -->\n<p>$html</p>\n<!-- /wp:paragraph -->\n\n";
}

function lightandpulse_b_list(array $items): string
{
    $li = implode('', array_map(fn($i) => "<!-- wp:list-item -->\n<li>$i</li>\n<!-- /wp:list-item -->\n", $items));
    return "<!-- wp:list -->\n<ul class=\"wp-block-list\">$li</ul>\n<!-- /wp:list -->\n\n";
}

function lightandpulse_b_quote(string $html): string
{
    return "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><!-- wp:paragraph -->\n<p>$html</p>\n<!-- /wp:paragraph --></blockquote>\n<!-- /wp:quote -->\n\n";
}

/** Starter posts, keyed by slug. */
function lightandpulse_starter_posts(): array
{
    $contact = esc_url(home_url('/contact/'));
    $services = esc_url(home_url('/services/'));

    $body = lightandpulse_b_p('Artificial intelligence used to sound like something for big tech companies in Jakarta or Singapore. Not anymore. The same tools are now cheap, available in Bahasa Indonesia and English, and built into apps Bali businesses already use every day: WhatsApp, Google, Instagram, booking systems and accounting software.')
        . lightandpulse_b_p('For villas, restaurants, spas, tour operators and creative studios across Bali, the question is no longer <em>whether</em> to use AI, but <em>where it actually helps</em>, and where a human touch still matters more. Here is a practical look at both.')

        . lightandpulse_b_h('1. Faster replies to guests, in their language')
        . lightandpulse_b_p('Most enquiries in Bali arrive on WhatsApp and Instagram, often at night because guests are in another time zone. AI assistants can draft replies to common questions (prices, availability, directions, what to bring) in English, Bahasa Indonesia and the languages your guests write in, so your team only has to check and send.')
        . lightandpulse_b_list([
            '<strong>Start with an FAQ.</strong> Write down the 20 questions you answer most. That list is what any assistant learns from.',
            '<strong>Keep a person in the loop.</strong> Let AI draft, and let your team approve, especially for bookings, payments and complaints.',
            '<strong>Hand over clearly.</strong> When a question is unusual, the guest should reach a real person quickly.',
        ])

        . lightandpulse_b_h('2. Websites that speak more languages')
        . lightandpulse_b_p('Bali welcomes guests from Australia, Europe, India, China, Korea, Russia and across Indonesia. AI translation makes a second or third language on your website realistic for a small business. Use it for a first draft, then have a native speaker review the pages that sell: your homepage, rooms or menu, and prices.')

        . lightandpulse_b_h('3. Content and photos, without losing what makes you local')
        . lightandpulse_b_p('AI can help write room descriptions, social media captions, menus and blog outlines, and it can clean up photos: better light, straighter lines, removed clutter. Two rules keep it honest:')
        . lightandpulse_b_list([
            '<strong>Never invent what guests will see.</strong> Do not generate a pool, a view or a dish you do not have. Disappointed guests leave reviews.',
            '<strong>Add the details only you know.</strong> The family story behind the warung, the name of the rice field, the ceremony the village holds each year. That is what makes content worth reading, and what AI cannot make up.',
        ])

        . lightandpulse_b_h('4. Answering reviews, every single one')
        . lightandpulse_b_p('Replying to Google and TripAdvisor reviews matters for trust and for search, but it takes time. AI can draft a personal reply from each review in seconds; your manager adds one specific line and posts it. Negative reviews should always get a human reply.')

        . lightandpulse_b_h('5. Being found in AI search')
        . lightandpulse_b_p('More and more travellers ask ChatGPT, Gemini or Google’s AI answers things like <em>“best quiet villa in Ubud for a family”</em> or <em>“vegan café near Canggu beach”</em>. These tools build their answers from information they can find and trust online. To be part of those answers:')
        . lightandpulse_b_list([
            'Keep your <strong>Google Business Profile</strong> complete and up to date: hours, photos, categories, and replies to reviews.',
            'Make your <strong>website</strong> clear about what you offer, where you are, and who it is for, in plain text, not only in images.',
            'Add <strong>structured data</strong> (schema) so search engines understand your business, rooms, menu or tours.',
            'Make sure the site is <strong>fast and mobile-friendly</strong>. Most people searching in Bali are on a phone.',
        ])

        . lightandpulse_b_h('6. Less time on the back office')
        . lightandpulse_b_p('AI features in spreadsheets, accounting and booking tools can summarise a month of bookings, spot slow weeks ahead, read supplier invoices and draft reports. None of this replaces your bookkeeper, but it gives small teams back hours every week.')

        . lightandpulse_b_h('Respect privacy, and respect Bali')
        . lightandpulse_b_p('Two things deserve care before you connect AI tools to your business:')
        . lightandpulse_b_list([
            '<strong>Personal data.</strong> Indonesia’s Personal Data Protection Law (UU PDP, Law No. 27 of 2022) applies to guest names, passport details, phone numbers and messages. Only put personal data into tools you trust, tell guests how you use their data, and do not paste passport scans or payment details into a public chatbot.',
            '<strong>Culture.</strong> Ceremonies, temples and sacred places are not decoration. Do not generate images of rituals, and use real, respectful photography and words when you write about Balinese culture.',
        ])

        . lightandpulse_b_h('Where to start this month')
        . lightandpulse_b_list([
            'Write your top 20 guest questions and answers. Use them to draft WhatsApp replies with AI, and keep a person checking.',
            'Pick your most important page and have it reviewed for clarity, speed and mobile, so both people and AI search understand it.',
            'Answer your last 10 Google reviews, with AI drafts and a personal line in each.',
        ])
        . lightandpulse_b_quote('AI works best as a quiet assistant behind the scenes, so your team has more time for the part guests remember: real Balinese hospitality.')

        . lightandpulse_b_h('How Lightandpulse can help')
        . lightandpulse_b_p('Since 2012 we have built websites, brands and digital marketing for Bali businesses. Today that includes making your website ready for AI search, connecting it with WhatsApp, adding the languages your guests speak, and setting up structured data and a complete Google Business Profile. <a href="' . $services . '">See our services</a> or <a href="' . $contact . '">send us a message</a> for a free website check.');

    return [
        'implementation-of-ai-in-bali' => [
            'title'    => 'Implementation of AI in Bali: practical uses for local businesses',
            'excerpt'  => 'Where AI actually helps villas, restaurants, spas and tour operators in Bali today, from WhatsApp replies and translations to being found in AI search, and where a human touch still matters more.',
            'category' => 'Artificial intelligence',
            'tags'     => ['AI', 'Bali', 'Tourism', 'Small business', 'SEO'],
            'image'    => 'assets/images/insights-ai-in-bali.jpg',
            'image_alt'=> 'Implementation of AI in Bali: Lightandpulse Insights cover with an ASCII pulse line',
            'content'  => $body,
        ],
    ];
}

/** Create (or, with $overwrite, update) the starter posts. Returns log lines. */
function lightandpulse_create_starter_posts(bool $overwrite): array
{
    $log = [];
    // Move WordPress's untouched "Hello world!" sample post to the trash (it can be restored from there).
    $hello = get_page_by_path('hello-world', OBJECT, 'post');
    if ($hello && $hello->post_status === 'publish' && str_contains($hello->post_content, 'Welcome to WordPress.')) {
        wp_trash_post($hello->ID);
        $log[] = __('Moved the sample “Hello world!” post to the trash.', 'lightandpulse');
    }
    foreach (lightandpulse_starter_posts() as $slug => $post) {
        $existing = get_page_by_path($slug, OBJECT, 'post');
        if ($existing && !$overwrite) {
            $log[] = sprintf(__('Kept existing post: %s', 'lightandpulse'), $post['title']);
            if (!has_post_thumbnail($existing->ID) && lightandpulse_set_starter_image($existing->ID, $post)) {
                $log[] = sprintf(__('Added the featured image to: %s', 'lightandpulse'), $post['title']);
            }
            continue;
        }
        $cat = term_exists($post['category'], 'category') ?: wp_insert_term($post['category'], 'category');
        $postarr = [
            'ID'            => $existing ? $existing->ID : 0,
            'post_type'     => 'post',
            'post_status'   => 'publish',
            'post_name'     => $slug,
            'post_title'    => $post['title'],
            'post_excerpt'  => $post['excerpt'],
            'post_content'  => $post['content'],
            'post_category' => is_wp_error($cat) ? [] : [(int) $cat['term_id']],
            'tags_input'    => $post['tags'],
            'comment_status'=> 'closed',
        ];
        $id = $existing ? wp_update_post(wp_slash($postarr), true) : wp_insert_post(wp_slash($postarr), true);
        if (is_wp_error($id)) {
            $log[] = sprintf(__('Could not save post %1$s: %2$s', 'lightandpulse'), $post['title'], $id->get_error_message());
            continue;
        }
        $log[] = sprintf($existing ? __('Updated post: %s', 'lightandpulse') : __('Published post: %s', 'lightandpulse'), $post['title']);
        if (!has_post_thumbnail($id)) {
            lightandpulse_set_starter_image((int) $id, $post);
        }
    }
    return $log;
}

/**
 * Copy a featured image that ships with the theme into the Media Library
 * (once; later runs reuse the same attachment) and set it on the post.
 */
function lightandpulse_set_starter_image(int $postId, array $post): bool
{
    if (empty($post['image'])) {
        return false;
    }
    $file = LIGHTANDPULSE_DIR . '/' . $post['image'];
    if (!is_readable($file)) {
        return false;
    }
    $known = (array) get_option('lightandpulse_starter_media', []);
    $attId = (int) ($known[$post['image']] ?? 0);
    if (!$attId || get_post_type($attId) !== 'attachment') {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        // media_handle_sideload moves the file, so give it a temporary copy.
        $tmp = wp_tempnam(basename($file));
        if (!$tmp || !copy($file, $tmp)) {
            return false;
        }
        $attId = media_handle_sideload(['name' => basename($file), 'tmp_name' => $tmp], $postId, $post['title']);
        if (is_wp_error($attId)) {
            @unlink($tmp);
            return false;
        }
        update_post_meta($attId, '_wp_attachment_image_alt', $post['image_alt'] ?? $post['title']);
        $known[$post['image']] = $attId;
        update_option('lightandpulse_starter_media', $known, false);
    }
    return (bool) set_post_thumbnail($postId, $attId);
}
