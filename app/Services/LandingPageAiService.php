<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LandingPageAiService
{
    /**
     * Allowed block types that Software Builder safely renders
     */
    protected const ALLOWED_BLOCK_TYPES = [
        'hero',
        'features_grid',
        'heading_text',
        'text',
        'image',
        'cta_button',
        'two_column',
        'faq',
        'disclaimer',
        'footer',
    ];

    /**
     * Generate or refine structured landing page block data
     */
    public function generate(string $prompt, array $options = []): array
    {
        $prompt = trim($prompt);
        if (empty($prompt)) {
            throw new \InvalidArgumentException('Prompt cannot be empty.');
        }

        // 1. Try external LLM provider if configured
        $llmResult = $this->callExternalLlm($prompt, $options);
        if ($llmResult !== null) {
            $validated = $this->validateAndSanitize($llmResult, $options);
            if (!empty($validated['blocks'])) {
                return $validated;
            }
        }

        // 2. Intelligent Domain Engine fallback
        $engineResult = $this->generateWithDomainEngine($prompt, $options);
        return $this->validateAndSanitize($engineResult, $options);
    }

    /**
     * Attempt external LLM API (Gemini or OpenAI)
     */
    protected function callExternalLlm(string $prompt, array $options): ?array
    {
        $geminiKey = Setting::get('gemini_api_key') ?: env('GEMINI_API_KEY');
        $openaiKey = Setting::get('openai_api_key') ?: env('OPENAI_API_KEY');

        if ($geminiKey) {
            try {
                return $this->callGemini($geminiKey, $prompt, $options);
            } catch (\Throwable $e) {
                Log::warning('Gemini AI Landing Page Generation error: ' . $e->getMessage());
            }
        }

        if ($openaiKey) {
            try {
                return $this->callOpenAi($openaiKey, $prompt, $options);
            } catch (\Throwable $e) {
                Log::warning('OpenAI Landing Page Generation error: ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Call Google Gemini API
     */
    protected function callGemini(string $apiKey, string $prompt, array $options): ?array
    {
        $systemInstruction = $this->getSystemPrompt();
        $userMessage = $this->buildUserPrompt($prompt, $options);

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";
        $response = Http::timeout(20)->post($url, [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $systemInstruction . "\n\n" . $userMessage]
                    ]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.7,
            ]
        ]);

        if (!$response->successful()) {
            Log::warning('Gemini API returned error: ' . $response->body());
            return null;
        }

        $body = $response->json();
        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!$text) return null;

        return json_decode($text, true);
    }

    /**
     * Call OpenAI API
     */
    protected function callOpenAi(string $apiKey, string $prompt, array $options): ?array
    {
        $systemInstruction = $this->getSystemPrompt();
        $userMessage = $this->buildUserPrompt($prompt, $options);

        $response = Http::timeout(20)->withToken($apiKey)->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $systemInstruction],
                ['role' => 'user', 'content' => $userMessage]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.7,
        ]);

        if (!$response->successful()) {
            Log::warning('OpenAI API returned error: ' . $response->body());
            return null;
        }

        $body = $response->json();
        $text = $body['choices'][0]['message']['content'] ?? null;
        if (!$text) return null;

        return json_decode($text, true);
    }

    /**
     * System prompt enforcing structured block JSON format
     */
    protected function getSystemPrompt(): string
    {
        return <<<PROMPT
You are an expert SaaS Landing Page Conversion Copywriter and Designer for Kirtnix TG Tracker.
Your job is to generate high-converting landing page block structures for Telegram lead generation funnels.

CRITICAL SECURITY & ARCHITECTURAL RULES:
1. Output MUST be valid JSON only.
2. Do NOT output executable HTML, <script> tags, PHP, Blade syntax, SQL, or arbitrary CSS.
3. Only output data that conforms to our standard block schemas.
4. All tracking scripts, Meta Pixel events, and Telegram deep-linking are handled natively by the software - do NOT invent custom tracking code or script snippets.

SUPPORTED BLOCK TYPES & SCHEMAS:
- hero: { "id": "block_xxx", "type": "hero", "badge": "...", "heading": "...", "subheading": "...", "button_text": "...", "button_subtitle": "..." }
- features_grid: { "id": "block_xxx", "type": "features_grid", "title": "...", "subtitle": "...", "cards": [ { "icon": "📈", "title": "...", "desc": "..." } ] }
- heading_text: { "id": "block_xxx", "type": "heading_text", "heading": "...", "text": "..." }
- image: { "id": "block_xxx", "type": "image", "url": "https://images.unsplash.com/...", "alt": "...", "caption": "..." }
- cta_button: { "id": "block_xxx", "type": "cta_button", "heading": "...", "subheading": "...", "button_text": "...", "button_subtitle": "..." }
- two_column: { "id": "block_xxx", "type": "two_column", "col1_heading": "...", "col1_text": "...", "col2_heading": "...", "col2_text": "..." }
- faq: { "id": "block_xxx", "type": "faq", "title": "Frequently Asked Questions", "faqs": [ { "q": "...", "a": "..." } ] }
- disclaimer: { "id": "block_xxx", "type": "disclaimer", "title": "Disclaimer", "text": "..." }
- footer: { "id": "block_xxx", "type": "footer", "copyright": "..." }

TOP-LEVEL JSON SCHEMA:
{
  "title": "Page Title",
  "brand_name": "Brand Name",
  "slug": "url-friendly-slug",
  "telegram_destination": "https://t.me/example",
  "seo_description": "Meta description for SEO/OG",
  "blocks": [ ...array of block objects in order... ]
}
PROMPT;
    }

    /**
     * Build User prompt with options & context
     */
    protected function buildUserPrompt(string $prompt, array $options): string
    {
        $context = [
            'User Prompt' => $prompt,
            'Brand Name' => $options['brand_name'] ?? null,
            'Industry' => $options['industry'] ?? null,
            'Target Audience' => $options['target_audience'] ?? null,
            'Primary Goal' => $options['primary_goal'] ?? 'Telegram VIP Community Leads',
            'Tone' => $options['tone'] ?? 'Premium, persuasive, and authoritative',
            'Language' => $options['language'] ?? 'English (or Hinglish if requested)',
            'Telegram Destination' => $options['telegram_destination'] ?? 'https://t.me/kirtnix',
        ];

        if (!empty($options['current_blocks'])) {
            $context['Existing Page Blocks'] = json_encode($options['current_blocks']);
            $context['Mode'] = 'Iterative Edit / Refinement. Modify the existing blocks based on the user instruction.';
        }

        return json_encode(array_filter($context), JSON_PRETTY_PRINT);
    }

    /**
     * High-Converting Native Domain Engine for offline/instant SaaS generation
     */
    protected function generateWithDomainEngine(string $prompt, array $options = []): array
    {
        $p = strtolower($prompt);
        $brand = !empty($options['brand_name']) ? trim($options['brand_name']) : $this->detectBrandName($prompt);
        $tgUrl = !empty($options['telegram_destination']) ? trim($options['telegram_destination']) : 'https://t.me/kirtnix';
        $language = strtolower($options['language'] ?? '');
        $isHinglish = str_contains($p, 'hinglish') || str_contains($p, 'hindi') || $language === 'hinglish' || $language === 'hindi';

        // Check if this is an iterative update on existing blocks
        if (!empty($options['current_blocks']) && is_array($options['current_blocks'])) {
            return $this->refineExistingBlocks($options['current_blocks'], $prompt, $brand, $tgUrl, $isHinglish);
        }

        // Domain classification
        if (str_contains($p, 'agency') || str_contains($p, 'marketing') || str_contains($p, 'digital') || str_contains($p, 'b2b') || str_contains($p, 'lead')) {
            return $this->generateAgencyTemplate($prompt, $brand, $tgUrl, $isHinglish);
        }

        if (str_contains($p, 'crypto') || str_contains($p, 'bitcoin') || str_contains($p, 'btc') || str_contains($p, 'web3') || str_contains($p, 'airdrop')) {
            return $this->generateCryptoTemplate($prompt, $brand, $tgUrl, $isHinglish);
        }

        if (str_contains($p, 'course') || str_contains($p, 'edtech') || str_contains($p, 'student') || str_contains($p, 'masterclass') || str_contains($p, 'webinar') || str_contains($p, 'coach')) {
            return $this->generateEducationTemplate($prompt, $brand, $tgUrl, $isHinglish);
        }

        if (str_contains($p, 'fitness') || str_contains($p, 'gym') || str_contains($p, 'diet') || str_contains($p, 'health') || str_contains($p, 'workout')) {
            return $this->generateFitnessTemplate($prompt, $brand, $tgUrl, $isHinglish);
        }

        if (str_contains($p, 'saas') || str_contains($p, 'software') || str_contains($p, 'app') || str_contains($p, 'ai tool') || str_contains($p, 'tech')) {
            return $this->generateSaaSTemplate($prompt, $brand, $tgUrl, $isHinglish);
        }

        // Default to Trading / Forex / Scalping / Stock Community
        return $this->generateTradingTemplate($prompt, $brand, $tgUrl, $isHinglish);
    }

    /**
     * Refine existing blocks iteratively based on user instruction
     */
    protected function refineExistingBlocks(array $blocks, string $instruction, string $brand, string $tgUrl, bool $isHinglish): array
    {
        $inst = strtolower($instruction);
        $updatedBlocks = $blocks;

        // 1. Modify CTA text
        if (str_contains($inst, 'cta') || str_contains($inst, 'button')) {
            $newCtaText = 'Join Free Telegram Now';
            if (preg_match('/(?:to|as)\s+["\']?([^"\']+)["\']?/i', $instruction, $matches)) {
                $newCtaText = trim($matches[1]);
            } elseif (str_contains($inst, 'get started')) {
                $newCtaText = 'Get Started Today';
            } elseif (str_contains($inst, 'instant access')) {
                $newCtaText = 'Claim Instant VIP Access';
            }

            foreach ($updatedBlocks as &$b) {
                if (isset($b['button_text'])) {
                    $b['button_text'] = $newCtaText;
                }
            }
            unset($b);
        }

        // 2. Make Hero more premium / change hero
        if (str_contains($inst, 'hero') || str_contains($inst, 'premium') || str_contains($inst, 'headline')) {
            foreach ($updatedBlocks as &$b) {
                if (($b['type'] ?? '') === 'hero') {
                    $b['badge'] = '✨ EXCLUSIVE VIP INVITATION';
                    $b['heading'] = $isHinglish 
                        ? 'Join ' . $brand . ' Official VIP Community' 
                        : 'Experience The Elite Standard with ' . $brand;
                    $b['subheading'] = $isHinglish
                        ? 'Daily verified signals, live market scalping setups aur 24/7 expert community guidance.'
                        : 'Institutional-grade market analysis, verified risk-managed signals, and real-time execution.';
                }
            }
            unset($b);
        }

        // 3. Add testimonials or proof if requested
        if (str_contains($inst, 'testimonial') || str_contains($inst, 'proof') || str_contains($inst, 'review')) {
            $hasTestimonial = false;
            foreach ($updatedBlocks as $b) {
                if (($b['type'] ?? '') === 'two_column' && str_contains(strtolower($b['col1_heading'] ?? ''), 'testimonial')) {
                    $hasTestimonial = true;
                    break;
                }
            }
            if (!$hasTestimonial) {
                // Insert two-column testimonial before footer/disclaimer
                $testimonialBlock = [
                    'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                    'type' => 'two_column',
                    'col1_heading' => '⭐ Verified Member Reviews',
                    'col1_text' => "\"Consistent accuracy and clear stop-loss levels. The best Telegram channel I have joined this year.\"\n— Rahul S. (Verified Member)",
                    'col2_heading' => '🎯 94.2% Satisfaction Rate',
                    'col2_text' => "\"Transparent trade setups with zero false promises. Educational breakdowns helped me understand risk properly.\"\n— Amit P. (Active Trader)"
                ];
                array_splice($updatedBlocks, max(1, count($updatedBlocks) - 2), 0, [$testimonialBlock]);
            }
        }

        // 4. Make page shorter / concise
        if (str_contains($inst, 'short') || str_contains($inst, 'chhota') || str_contains($inst, 'brief') || str_contains($inst, 'compact')) {
            $updatedBlocks = array_values(array_filter($updatedBlocks, function($b) {
                return in_array($b['type'] ?? '', ['hero', 'features_grid', 'cta_button', 'footer']);
            }));
        }

        // 5. Convert to Hinglish
        if ($isHinglish) {
            foreach ($updatedBlocks as &$b) {
                if (($b['type'] ?? '') === 'hero') {
                    $b['badge'] = '⚡ 100% FREE VIP COMMUNITY';
                    $b['subheading'] = 'Daily high-accuracy setups, educational guidance aur real-time Telegram alerts seedhe aapke phone par.';
                    $b['button_text'] = 'Free Telegram Channel Join Karein';
                    $b['button_subtitle'] = 'Instant access • Koi payment required nahi hai';
                } elseif (($b['type'] ?? '') === 'features_grid') {
                    $b['title'] = 'Kyu Join Karein Hamari Community?';
                    $b['subtitle'] = 'Har trade mein professional analysis aur clear risk management.';
                } elseif (($b['type'] ?? '') === 'cta_button') {
                    $b['heading'] = 'Apni Trading Journey Aaj Hi Shuru Karein';
                    $b['subheading'] = 'Neeche diye button par click karein aur channel mein free entry lein.';
                    $b['button_text'] = 'Abhi Telegram Channel Open Karein';
                }
            }
            unset($b);
        }

        return [
            'title' => $brand . ' - Official Community',
            'brand_name' => $brand,
            'slug' => Str::slug($brand . '-' . substr(md5(uniqid()), 0, 4)),
            'telegram_destination' => $tgUrl,
            'seo_description' => "Join {$brand} VIP Community for real-time market updates and verified signals.",
            'blocks' => $updatedBlocks,
        ];
    }

    /**
     * Template: Agency / Digital Marketing / B2B Leads
     */
    protected function generateAgencyTemplate(string $prompt, string $brand, string $tgUrl, bool $isHinglish): array
    {
        $brand = $brand ?: 'Apex Media Agency';
        $blocks = [
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'hero',
                'badge' => '🚀 SCALE YOUR BUSINESS TO 8-FIGURES',
                'heading' => $isHinglish ? "Apne Business Ko Scale Karein with {$brand}" : "Scale Your Business With High-ROI Digital Marketing",
                'subheading' => $isHinglish 
                    ? "Targeted Meta Ads, high-converting funnels aur verified lead generation systems jo aapko predictable revenue dete hain."
                    : "We build performance-driven Meta Ads and automated lead funnels for ambitious brands seeking predictable, profitable growth.",
                'button_text' => $isHinglish ? 'Free Strategy Call & Case Studies' : 'Join VIP Strategy Channel On Telegram',
                'button_subtitle' => 'Free breakdown • Proven client case studies inside',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'features_grid',
                'title' => 'Why High-Growth Brands Choose Us',
                'subtitle' => 'Data-driven growth systems with transparent performance tracking',
                'cards' => [
                    [
                        'icon' => '🎯',
                        'title' => 'High-Converting Meta Ads',
                        'desc' => 'Laser-targeted campaigns optimized for high ROAS and lowest customer acquisition costs.'
                    ],
                    [
                        'icon' => '⚡',
                        'title' => 'Automated Lead Attribution',
                        'desc' => 'Real-time Telegram & CAPI tracking connecting every ad dollar to verified buyer actions.'
                    ],
                    [
                        'icon' => '📈',
                        'title' => 'Rapid Scaling Framework',
                        'desc' => 'Battle-tested scaling blueprints that double revenue without blowing your ad budget.'
                    ],
                ]
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'two_column',
                'col1_heading' => 'What We Deliver',
                'col1_text' => "• Full-funnel ad creative & copywriting\n• Server-side Meta CAPI setup\n• Custom high-converting landing pages\n• Dedicated performance account manager",
                'col2_heading' => 'Who This Is For',
                'col2_text' => "• E-commerce & D2C brand owners\n• Digital coaches & course creators\n• B2B service providers seeking qualified leads\n• Businesses ready to invest in serious growth"
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'cta_button',
                'heading' => 'Ready to Dominate Your Market?',
                'subheading' => 'Join our private Telegram group for exclusive marketing frameworks and scaling breakdowns.',
                'button_text' => 'Join Agency Telegram Channel',
                'button_subtitle' => 'Instant access to growth playbooks'
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'faq',
                'title' => 'Frequently Asked Questions',
                'faqs' => [
                    [
                        'q' => 'What happens after I join the Telegram channel?',
                        'a' => 'You get immediate access to our case studies, ad breakdown videos, and direct access to book a 1-on-1 strategy call with our growth team.'
                    ],
                    [
                        'q' => 'How quickly can we launch campaigns?',
                        'a' => 'Our onboarding is streamlined: strategy audit, tracking setup, and ad creatives launch within 5-7 business days.'
                    ],
                    [
                        'q' => 'Is joining the Telegram group free?',
                        'a' => 'Yes! The strategy breakdowns and community access are 100% free with zero obligation.'
                    ]
                ]
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'footer',
                'copyright' => '© ' . date('Y') . ' ' . $brand . '. All rights reserved.'
            ]
        ];

        return [
            'title' => $brand . ' - Performance Marketing Agency',
            'brand_name' => $brand,
            'slug' => Str::slug($brand . '-agency-' . substr(md5(uniqid()), 0, 4)),
            'telegram_destination' => $tgUrl,
            'seo_description' => "Scale your brand with {$brand}. High-ROI Meta Ads, conversion funnels, and Telegram growth systems.",
            'blocks' => $blocks
        ];
    }

    /**
     * Template: Trading / Forex / Scalping / Stock Market VIP Channel
     */
    protected function generateTradingTemplate(string $prompt, string $brand, string $tgUrl, bool $isHinglish): array
    {
        $brand = $brand ?: 'PRO TRADING VIP';
        $blocks = [
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'hero',
                'badge' => '⚡ 100% FREE VIP COMMUNITY ACCESS',
                'heading' => $isHinglish ? "Join {$brand} On Telegram" : "Trade Smarter With {$brand}",
                'subheading' => $isHinglish
                    ? 'Get daily verified market setups, live scalping signals aur high-accuracy levels seedhe Telegram par.'
                    : 'Institutional-grade market analysis, precise entry/exit targets, and disciplined risk management delivered directly to your Telegram.',
                'button_text' => 'Join Free Telegram Channel',
                'button_subtitle' => 'Free instant access • Verified accuracy • No card needed',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'features_grid',
                'title' => 'What You Get Inside Our Community',
                'subtitle' => 'Everything you need to navigate the financial markets with confidence',
                'cards' => [
                    [
                        'icon' => '📈',
                        'title' => 'High-Probability Setups',
                        'desc' => 'Daily actionable setups with clear entry point, stop loss, and target levels.'
                    ],
                    [
                        'icon' => '🎯',
                        'title' => 'Strict Risk Management',
                        'desc' => 'Clear 1:2+ risk-to-reward ratio calculation for long-term consistency.'
                    ],
                    [
                        'icon' => '⚡',
                        'title' => 'Instant Real-Time Alerts',
                        'desc' => 'Push notifications directly on Telegram so you never miss a market move.'
                    ]
                ]
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'cta_button',
                'heading' => 'Stop Gambling, Start Trading with Discipline',
                'subheading' => 'Tap below to enter the channel and access today\'s market breakdown immediately.',
                'button_text' => 'Enter VIP Telegram Channel',
                'button_subtitle' => 'Compatible with Telegram App & Web'
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'faq',
                'title' => 'Frequently Asked Questions',
                'faqs' => [
                    [
                        'q' => 'Is this channel really 100% free?',
                        'a' => 'Yes! The main educational setups, daily commentary, and community discussions are completely free.'
                    ],
                    [
                        'q' => 'Do I need prior trading experience?',
                        'a' => 'No. We provide comprehensive educational breakdowns suitable for beginners as well as active day traders.'
                    ],
                    [
                        'q' => 'How quickly will I receive setups after joining?',
                        'a' => 'As soon as you tap "Join" on Telegram, you will see all pinned analyses and today\'s active market updates.'
                    ]
                ]
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'disclaimer',
                'title' => 'Important Risk Disclaimer',
                'text' => 'Trading financial markets involves substantial risk of loss and is not suitable for all investors. All setups, analysis, and information shared are strictly for educational purposes and do not constitute financial or investment advice.'
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'footer',
                'copyright' => '© ' . date('Y') . ' ' . $brand . '. All rights reserved.'
            ]
        ];

        return [
            'title' => $brand . ' - Official Telegram Channel',
            'brand_name' => $brand,
            'slug' => Str::slug($brand . '-vip-' . substr(md5(uniqid()), 0, 4)),
            'telegram_destination' => $tgUrl,
            'seo_description' => "Join {$brand} on Telegram for daily high-accuracy setups and market commentary.",
            'blocks' => $blocks
        ];
    }

    /**
     * Template: Crypto / Web3
     */
    protected function generateCryptoTemplate(string $prompt, string $brand, string $tgUrl, bool $isHinglish): array
    {
        $brand = $brand ?: 'CRYPTO ALPHA SIGNALS';
        $blocks = [
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'hero',
                'badge' => '⚡ EXCLUSIVE CRYPTO ALPHA & AIRDROPS',
                'heading' => "Catch The Next 10x Moves with {$brand}",
                'subheading' => 'On-chain analytics, verified whale movements, early airdrop guides, and high-leverage scalping signals.',
                'button_text' => 'Join Crypto Alpha Telegram',
                'button_subtitle' => 'Free access • Real-time on-chain alerts',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'features_grid',
                'title' => 'Why Join Our Web3 Alpha Channel?',
                'subtitle' => 'Stay ahead of the crowd with real-time on-chain data',
                'cards' => [
                    [
                        'icon' => '🐋',
                        'title' => 'Whale Wallet Tracking',
                        'desc' => 'Track institutional accumulation and smart money wallet flows before major pumps.'
                    ],
                    [
                        'icon' => '🎁',
                        'title' => 'Zero-Cost Airdrop Guides',
                        'desc' => 'Step-by-step testnet guides to qualify for the biggest upcoming ecosystem airdrops.'
                    ],
                    [
                        'icon' => '⚡',
                        'title' => 'Futures Scalp Calls',
                        'desc' => 'BTC, ETH & altcoin scalp levels with strict stop-losses and trailing take-profits.'
                    ]
                ]
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'cta_button',
                'heading' => 'Never Miss The Next Big Crypto Cycle',
                'subheading' => 'Tap below to access our daily crypto watchlist and private community chat.',
                'button_text' => 'Open Crypto Channel Now',
                'button_subtitle' => 'Instant access on Telegram'
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'disclaimer',
                'title' => 'Cryptocurrency Risk Notice',
                'text' => 'Cryptocurrency trading carries high market volatility and risk. Never invest money you cannot afford to lose. All content shared is strictly educational.'
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'footer',
                'copyright' => '© ' . date('Y') . ' ' . $brand . '. All rights reserved.'
            ]
        ];

        return [
            'title' => $brand . ' - Crypto Signals & Alpha',
            'brand_name' => $brand,
            'slug' => Str::slug($brand . '-alpha-' . substr(md5(uniqid()), 0, 4)),
            'telegram_destination' => $tgUrl,
            'seo_description' => "Join {$brand} on Telegram for early crypto alpha, airdrops, and verified trade calls.",
            'blocks' => $blocks
        ];
    }

    /**
     * Template: Education / Course / Coach / Masterclass
     */
    protected function generateEducationTemplate(string $prompt, string $brand, string $tgUrl, bool $isHinglish): array
    {
        $brand = $brand ?: 'Mastery Academy';
        $blocks = [
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'hero',
                'badge' => '🎓 FREE MASTERCLASS & RESOURCES',
                'heading' => $isHinglish ? "Master High-Income Skills with {$brand}" : "Accelerate Your Growth With {$brand}",
                'subheading' => 'Join 10,000+ ambitious students learning practical, industry-proven frameworks to double their income.',
                'button_text' => 'Join Student Community on Telegram',
                'button_subtitle' => 'Free study roadmaps + live Q&A sessions',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'features_grid',
                'title' => 'What You Will Learn Inside',
                'subtitle' => 'Actionable step-by-step roadmap designed for immediate results',
                'cards' => [
                    [
                        'icon' => '📚',
                        'title' => 'Free PDF Guides & Cheatsheets',
                        'desc' => 'Comprehensive blueprints covering step-by-step implementation.'
                    ],
                    [
                        'icon' => '🎙️',
                        'title' => 'Weekly Live Q&A Sessions',
                        'desc' => 'Ask questions directly to mentors and get personalized feedback.'
                    ],
                    [
                        'icon' => '🤝',
                        'title' => 'Active Peer Community',
                        'desc' => 'Network with like-minded learners, collaborate, and grow together.'
                    ]
                ]
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'cta_button',
                'heading' => 'Start Your Learning Journey Today',
                'subheading' => 'Tap below to join our Telegram group and download the welcome study pack.',
                'button_text' => 'Enter Student Group Free',
                'button_subtitle' => '100% Free access'
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'footer',
                'copyright' => '© ' . date('Y') . ' ' . $brand . '. All rights reserved.'
            ]
        ];

        return [
            'title' => $brand . ' - Online Academy & Community',
            'brand_name' => $brand,
            'slug' => Str::slug($brand . '-academy-' . substr(md5(uniqid()), 0, 4)),
            'telegram_destination' => $tgUrl,
            'seo_description' => "Learn high-income skills with {$brand}. Join our free Telegram community for live masterclasses.",
            'blocks' => $blocks
        ];
    }

    /**
     * Template: Fitness / Gym / Health
     */
    protected function generateFitnessTemplate(string $prompt, string $brand, string $tgUrl, bool $isHinglish): array
    {
        $brand = $brand ?: 'FitPro Community';
        $blocks = [
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'hero',
                'badge' => '🔥 30-DAY TRANSFORMATION CHALLENGE',
                'heading' => "Transform Your Body & Mind with {$brand}",
                'subheading' => 'Daily workout routines, science-backed nutrition plans, and 24/7 motivation directly in your Telegram.',
                'button_text' => 'Join Free Fitness Telegram',
                'button_subtitle' => 'Instant access to meal plans & workout splits',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'features_grid',
                'title' => 'Your Complete Fitness Blueprint',
                'subtitle' => 'Built for sustainable fat loss and muscle building',
                'cards' => [
                    [
                        'icon' => '💪',
                        'title' => 'Daily Workout Splits',
                        'desc' => 'Home and gym-friendly routines with video form breakdowns.'
                    ],
                    [
                        'icon' => '🥗',
                        'title' => 'Simple Diet Protocols',
                        'desc' => 'High-protein, sustainable meal plans without starving yourself.'
                    ],
                    [
                        'icon' => '⏱️',
                        'title' => 'Accountability & Tracking',
                        'desc' => 'Weekly check-in threads to keep you consistent and motivated.'
                    ]
                ]
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'cta_button',
                'heading' => 'Ready to Build Your Dream Physique?',
                'subheading' => 'Tap below to enter our free Telegram community and claim your 30-day challenge guide.',
                'button_text' => 'Join Free Fitness Channel',
                'button_subtitle' => 'Free forever'
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'footer',
                'copyright' => '© ' . date('Y') . ' ' . $brand . '. All rights reserved.'
            ]
        ];

        return [
            'title' => $brand . ' - Fitness & Transformation',
            'brand_name' => $brand,
            'slug' => Str::slug($brand . '-fit-' . substr(md5(uniqid()), 0, 4)),
            'telegram_destination' => $tgUrl,
            'seo_description' => "Join {$brand} on Telegram for daily workouts, diet tips, and body transformations.",
            'blocks' => $blocks
        ];
    }

    /**
     * Template: SaaS / Tech
     */
    protected function generateSaaSTemplate(string $prompt, string $brand, string $tgUrl, bool $isHinglish): array
    {
        $brand = $brand ?: 'CloudSync AI';
        $blocks = [
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'hero',
                'badge' => '⚡ AUTOMATE YOUR WORKFLOW IN SECONDS',
                'heading' => "Build & Automate Faster with {$brand}",
                'subheading' => 'Join our early beta community on Telegram for exclusive feature access, API tips, and developer support.',
                'button_text' => 'Join Developer Community',
                'button_subtitle' => 'Early access + Private product roadmap',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'features_grid',
                'title' => 'Engineered For Extreme Efficiency',
                'subtitle' => 'Modern developer tools built with speed and reliability in mind',
                'cards' => [
                    [
                        'icon' => '⚡',
                        'title' => 'Sub-50ms Response Time',
                        'desc' => 'Built on distributed edge architecture with instant synchronization.'
                    ],
                    [
                        'icon' => '🔒',
                        'title' => 'Enterprise Grade Security',
                        'desc' => 'End-to-end encrypted payloads with strict zero-trust role policies.'
                    ],
                    [
                        'icon' => '🚀',
                        'title' => '1-Click Integrations',
                        'desc' => 'Connect seamlessly to Meta CAPI, Telegram Bot API, and modern webhooks.'
                    ]
                ]
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'cta_button',
                'heading' => 'Get Exclusive Beta Access Today',
                'subheading' => 'Join our product Telegram channel to claim free trial credits and direct founder support.',
                'button_text' => 'Enter Beta Telegram Channel',
                'button_subtitle' => 'Available for Web & Mobile'
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'footer',
                'copyright' => '© ' . date('Y') . ' ' . $brand . '. All rights reserved.'
            ]
        ];

        return [
            'title' => $brand . ' - Next-Gen Software',
            'brand_name' => $brand,
            'slug' => Str::slug($brand . '-saas-' . substr(md5(uniqid()), 0, 4)),
            'telegram_destination' => $tgUrl,
            'seo_description' => "Automate your workflows with {$brand}. Join our VIP Telegram channel for early beta access.",
            'blocks' => $blocks
        ];
    }

    /**
     * Detect brand name from user prompt
     */
    protected function detectBrandName(string $prompt): string
    {
        if (preg_match('/(?:for|brand|named|called)\s+["\']?([A-Za-z0-9\s&]{3,25})["\']?/i', $prompt, $matches)) {
            $candidate = trim($matches[1]);
            if (!in_array(strtolower($candidate), ['a', 'the', 'my', 'this', 'our', 'an', 'agency', 'page', 'landing page', 'telegram'])) {
                return ucwords($candidate);
            }
        }

        return 'VIP Trading';
    }

    /**
     * Validate schema, filter allowed block types, and recursively sanitize strings against XSS
     */
    public function validateAndSanitize(array $data, array $options = []): array
    {
        $rawBrand = !empty($data['brand_name']) ? (string)$data['brand_name'] : (!empty($options['brand_name']) ? (string)$options['brand_name'] : 'VIP Community');
        $rawTitle = !empty($data['title']) ? (string)$data['title'] : ($rawBrand . ' Landing Page');
        $rawSlug = !empty($data['slug']) ? (string)$data['slug'] : Str::slug($rawTitle . '-' . substr(md5(uniqid()), 0, 4));
        $rawTg = !empty($data['telegram_destination']) ? (string)$data['telegram_destination'] : (!empty($options['telegram_destination']) ? (string)$options['telegram_destination'] : 'https://t.me/kirtnix');
        $rawSeo = !empty($data['seo_description']) ? (string)$data['seo_description'] : ($rawTitle . ' - Join our official VIP channel.');

        $brand = (string)$this->sanitizeValue($rawBrand);
        $title = (string)$this->sanitizeValue($rawTitle);
        $slug = Str::slug((string)$this->sanitizeValue($rawSlug));
        $tgUrl = (string)$this->sanitizeValue($rawTg);
        $seoDesc = (string)$this->sanitizeValue($rawSeo);

        $rawBlocks = $data['blocks'] ?? [];
        if (!is_array($rawBlocks)) {
            $rawBlocks = [];
        }

        $cleanBlocks = [];
        foreach ($rawBlocks as $block) {
            if (!is_array($block) || empty($block['type'])) {
                continue;
            }

            $type = strtolower(trim((string)$block['type']));
            // Normalize alias types
            if ($type === 'text') $type = 'heading_text';
            if ($type === 'features' || $type === 'benefits' || $type === 'services') $type = 'features_grid';
            if ($type === 'cta' || $type === 'telegram_cta') $type = 'cta_button';
            if ($type === 'testimonials' || $type === 'social_proof') $type = 'two_column';

            if (!in_array($type, self::ALLOWED_BLOCK_TYPES, true)) {
                continue;
            }

            $cleanBlock = [
                'id' => !empty($block['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$block['id']) : ('block_' . substr(md5(uniqid()), 0, 8)),
                'type' => $type,
            ];

            // Clean fields recursively
            foreach ($block as $k => $v) {
                if (in_array($k, ['id', 'type'], true)) continue;
                $cleanBlock[$k] = $this->sanitizeValue($v);
            }

            $cleanBlocks[] = $cleanBlock;
        }

        // Ensure at least a Hero block exists
        if (empty($cleanBlocks)) {
            $cleanBlocks = \App\Models\LandingPage::getDefaultBlocks($brand, $tgUrl);
        }

        return [
            'title' => $title,
            'brand_name' => $brand,
            'slug' => $slug,
            'telegram_destination' => $tgUrl,
            'seo_description' => $seoDesc,
            'blocks' => $cleanBlocks,
        ];
    }

    /**
     * Recursive sanitizer to strip scripts, tags, and PHP injections
     */
    protected function sanitizeValue($val)
    {
        if (is_array($val)) {
            $res = [];
            foreach ($val as $k => $v) {
                $res[$this->sanitizeKey($k)] = $this->sanitizeValue($v);
            }
            return $res;
        }

        if (is_string($val)) {
            // Strip any <script>, <iframe>, <style>, PHP, Blade directives
            $cleaned = preg_replace('/<\s*script[^>]*>.*?<\s*\/\s*script\s*>/is', '', $val);
            $cleaned = preg_replace('/<\s*iframe[^>]*>.*?<\s*\/\s*iframe\s*>/is', '', $cleaned);
            $cleaned = preg_replace('/<\s*style[^>]*>.*?<\s*\/\s*style\s*>/is', '', $cleaned);
            $cleaned = preg_replace('/<\?php.*?\?>/is', '', $cleaned);
            $cleaned = preg_replace('/@(?:php|include|extends|section|yield|slot)/i', '', $cleaned);
            return trim(strip_tags($cleaned));
        }

        return $val;
    }

    protected function sanitizeKey($key)
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$key);
    }
}
