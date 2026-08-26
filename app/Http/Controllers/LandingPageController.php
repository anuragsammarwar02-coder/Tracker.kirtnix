<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\Client;
use App\Models\Campaign;
use App\Models\Cta;
use App\Services\VercelService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LandingPageController extends Controller
{
    public function __construct(protected VercelService $vercelService) {}

    public function index(Request $request)
    {
        $query = LandingPage::with(['client', 'campaign', 'ctas'])->withCount(['views', 'clicks']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('brand_name', 'like', "%{$search}%")
                  ->orWhere('external_url', 'like', "%{$search}%")
                  ->orWhere('vercel_project_name', 'like', "%{$search}%");
            });
        }

        if ($source = $request->query('source')) {
            if ($source !== 'all') {
                $query->where('page_source', $source);
            }
        }

        if ($clientId = $request->query('client_id')) {
            $query->where('client_id', $clientId);
        }

        $landingPages = $query->latest('id')->paginate(12)->withQueryString();
        $clients = Client::orderBy('company_name')->get();

        // Source counts for badge tabs
        $counts = [
            'all' => LandingPage::count(),
            'native' => LandingPage::where('page_source', 'native')->count(),
            'vercel' => LandingPage::where('page_source', 'vercel')->count(),
            'netlify' => LandingPage::where('page_source', 'netlify')->count(),
            'html_upload' => LandingPage::where('page_source', 'html_upload')->count(),
        ];

        $currentSource = $request->query('source', 'all');
        $viewMode = $request->query('view', 'grid'); // default to modern card grid as per screenshot

        return view('landing_pages.index', compact('landingPages', 'clients', 'counts', 'currentSource', 'viewMode'));
    }

    public function create(Request $request)
    {
        $clients = Client::where('status', 'active')->orderBy('company_name')->get();
        $campaigns = Campaign::where('status', 'active')->orderBy('name')->get();
        $selectedClientId = $request->query('client_id');

        return view('landing_pages.create', compact('clients', 'campaigns', 'selectedClientId'));
    }

    public function store(Request $request)
    {
        if ($request->filled('slug')) {
            LandingPage::withTrashed()->where('slug', trim($request->slug))->whereNotNull('deleted_at')->forceDelete();
        }

        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('landing_pages', 'slug')->whereNull('deleted_at')],
            'template_type' => ['required', 'in:forex_focus,gujarati_trader,custom'],
            'brand_name' => ['required', 'string', 'max:255'],
            'brand_tagline' => ['nullable', 'string', 'max:255'],
            'brand_logo_url' => ['nullable', 'string'],
            'badge_text' => ['nullable', 'string', 'max:255'],
            'hero_heading' => ['required', 'string'],
            'hero_subheading' => ['nullable', 'string'],
            'hero_video_url' => ['nullable', 'string'],
            'hero_image_url' => ['nullable', 'string'],
            'features_json' => ['nullable', 'array'],
            'about_heading' => ['nullable', 'string', 'max:255'],
            'about_text' => ['nullable', 'string'],
            'disclaimer_text' => ['nullable', 'string'],
            'footer_text' => ['nullable', 'string', 'max:255'],
            'primary_cta_text' => ['required', 'string', 'max:255'],
            'secondary_cta_text' => ['nullable', 'string', 'max:255'],
            'telegram_destination' => ['required', 'string'],
            'telegram_channel_username' => ['nullable', 'string'],
            'meta_pixel_id' => ['nullable', 'string'],
            'meta_access_token' => ['nullable', 'string'],
            'meta_test_event_code' => ['nullable', 'string'],
            'gtm_id' => ['nullable', 'string'],
            'custom_head_code' => ['nullable', 'string'],
            'custom_css' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['page_source'] = 'native';
        $validated['tracking_token'] = (string) Str::uuid();

        // Fallback default logo if empty
        if (empty($validated['brand_logo_url'])) {
            $client = Client::find($validated['client_id']);
            $validated['brand_logo_url'] = $client?->logo_url ?? '/assets/branding/kirtnix-logo-dark-icon.png';
        }

        $landingPage = LandingPage::create($validated);

        // Create Default Primary CTA
        Cta::create([
            'landing_page_id' => $landingPage->id,
            'client_id' => $landingPage->client_id,
            'campaign_id' => $landingPage->campaign_id,
            'name' => 'Primary Hero CTA',
            'button_text' => $landingPage->primary_cta_text,
            'button_type' => 'primary',
            'tracking_token' => 'kx_' . Str::random(8),
            'telegram_destination' => $landingPage->telegram_destination,
            'direct_protocol' => 'auto',
            'is_active' => true,
        ]);

        if (!empty($landingPage->secondary_cta_text)) {
            Cta::create([
                'landing_page_id' => $landingPage->id,
                'client_id' => $landingPage->client_id,
                'campaign_id' => $landingPage->campaign_id,
                'name' => 'Secondary Footer CTA',
                'button_text' => $landingPage->secondary_cta_text,
                'button_type' => 'secondary',
                'tracking_token' => 'kx_' . Str::random(8),
                'telegram_destination' => $landingPage->telegram_destination,
                'direct_protocol' => 'auto',
                'is_active' => true,
            ]);
        }

        return redirect()->route('landing-pages.show', $landingPage)
            ->with('success', "Landing Page {$landingPage->title} created successfully!");
    }

    /**
     * Show external page import screen (Vercel, Netlify, HTML upload)
     */
    public function import(Request $request)
    {
        $clients = Client::where('status', 'active')->orderBy('company_name')->get();
        $vercelProjects = $this->vercelService->getProjects();
        $hasVercelToken = !empty($this->vercelService->getToken());
        $activeTab = $request->query('tab', 'vercel'); // vercel, html_upload, netlify
        $importedPage = null;

        if ($importedId = session('imported_page_id')) {
            $importedPage = LandingPage::find($importedId);
        }

        return view('landing_pages.import', compact('clients', 'vercelProjects', 'hasVercelToken', 'activeTab', 'importedPage'));
    }

    /**
     * Handle Import submission from Vercel, Netlify, or HTML Upload
     */
    public function storeImport(Request $request)
    {
        if ($request->filled('slug')) {
            LandingPage::withTrashed()->where('slug', trim($request->slug))->whereNotNull('deleted_at')->forceDelete();
        }

        $type = $request->input('import_type', 'vercel');

        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('landing_pages', 'slug')->whereNull('deleted_at')],
            'telegram_destination' => ['required', 'string'],
            'vercel_project_name' => ['nullable', 'string', 'max:255'],
            'production_domain' => ['nullable', 'string', 'max:255'],
            'external_url' => ['nullable', 'string', 'max:255'],
            'meta_pixel_id' => ['nullable', 'string'],
            'meta_access_token' => ['nullable', 'string'],
            'html_file' => ['nullable', 'file', 'mimes:html,htm,txt', 'max:5120'],
        ]);

        $trackingToken = (string) Str::uuid();
        $rawDomain = $request->input('production_domain') ?? $request->input('external_url') ?? ($validated['slug'] . '.vercel.app');
        $domain = preg_replace('#^https?://#', '', trim($rawDomain));
        $domain = rtrim($domain, '/');

        // Sanitize Vercel preview URLs to clean standard domain
        if ($type === 'vercel' && str_contains($domain, '.vercel.app')) {
            $projectName = $request->input('vercel_project_name') ?? $validated['slug'];
            if (!empty($projectName)) {
                $cleanPrj = strtolower(trim($projectName));
                if (str_contains($domain, '-projects.vercel.app') || str_contains($domain, '-anuragsammarwar') || str_contains($domain, '-')) {
                    $domain = $cleanPrj . '.vercel.app';
                }
            }
        }

        $fullUrl = 'https://' . $domain;

        $htmlContent = null;
        if ($request->hasFile('html_file')) {
            $htmlContent = file_get_contents($request->file('html_file')->getRealPath());
        }

        $client = Client::find($validated['client_id']);

        $landingPage = LandingPage::create([
            'client_id' => $validated['client_id'],
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'page_source' => $type,
            'external_url' => $fullUrl,
            'vercel_project_name' => $request->input('vercel_project_name') ?? $validated['title'],
            'tracking_token' => $trackingToken,
            'template_type' => 'custom',
            'brand_name' => $client->company_name ?? $validated['title'],
            'primary_cta_text' => 'Join Telegram Channel',
            'telegram_destination' => $validated['telegram_destination'],
            'meta_pixel_id' => $validated['meta_pixel_id'] ?? null,
            'meta_access_token' => $validated['meta_access_token'] ?? null,
            'html_content' => $htmlContent,
            'deployment_status' => 'published',
            'is_active' => true,
        ]);

        // Create tracking CTA for the external site
        Cta::create([
            'landing_page_id' => $landingPage->id,
            'client_id' => $landingPage->client_id,
            'name' => 'Imported External CTA',
            'button_text' => 'Join Telegram Channel',
            'button_type' => 'primary',
            'tracking_token' => 'kx_' . substr($trackingToken, 0, 8),
            'telegram_destination' => $landingPage->telegram_destination,
            'direct_protocol' => 'auto',
            'is_active' => true,
        ]);

        return redirect()->route('landing-pages.import', ['tab' => $type])
            ->with('imported_page_id', $landingPage->id)
            ->with('success', "Site '{$landingPage->title}' successfully imported from " . ucfirst($type) . "! Tracking script generated below.");
    }

    /**
     * Save Vercel API token in settings
     */
    public function saveVercelToken(Request $request)
    {
        $request->validate(['vercel_token' => ['required', 'string']]);
        $this->vercelService->setToken($request->input('vercel_token'));

        if ($request->wantsJson()) {
            $projects = $this->vercelService->getProjects();
            return response()->json(['success' => true, 'projects' => $projects]);
        }

        return redirect()->back()->with('success', 'Vercel API token connected successfully! Projects refreshed.');
    }

    public function show(LandingPage $landingPage)
    {
        $landingPage->load(['client', 'campaign', 'ctas.clicks']);

        $viewsCount = $landingPage->views()->count();
        $uniqueVisitors = $landingPage->views()->where('is_unique', true)->count();
        $clicksCount = $landingPage->clicks()->count();
        $uniqueClicks = $landingPage->clicks()->where('is_unique', true)->count();

        $ctr = $viewsCount > 0 ? round(($clicksCount / $viewsCount) * 100, 2) : 0;

        return view('landing_pages.show', compact(
            'landingPage',
            'viewsCount',
            'uniqueVisitors',
            'clicksCount',
            'uniqueClicks',
            'ctr'
        ));
    }

    public function edit(LandingPage $landingPage)
    {
        $clients = Client::orderBy('company_name')->get();
        $campaigns = Campaign::where('client_id', $landingPage->client_id)->orderBy('name')->get();

        return view('landing_pages.edit', compact('landingPage', 'clients', 'campaigns'));
    }

    public function update(Request $request, LandingPage $landingPage)
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('landing_pages', 'slug')->ignore($landingPage->id)->whereNull('deleted_at')],
            'template_type' => ['required', 'in:forex_focus,gujarati_trader,custom'],
            'brand_name' => ['required', 'string', 'max:255'],
            'brand_tagline' => ['nullable', 'string', 'max:255'],
            'brand_logo_url' => ['nullable', 'string'],
            'badge_text' => ['nullable', 'string', 'max:255'],
            'hero_heading' => ['nullable', 'string'],
            'hero_subheading' => ['nullable', 'string'],
            'hero_video_url' => ['nullable', 'string'],
            'hero_image_url' => ['nullable', 'string'],
            'features_json' => ['nullable', 'array'],
            'about_heading' => ['nullable', 'string', 'max:255'],
            'about_text' => ['nullable', 'string'],
            'disclaimer_text' => ['nullable', 'string'],
            'footer_text' => ['nullable', 'string', 'max:255'],
            'primary_cta_text' => ['required', 'string', 'max:255'],
            'secondary_cta_text' => ['nullable', 'string', 'max:255'],
            'telegram_destination' => ['required', 'string'],
            'telegram_channel_username' => ['nullable', 'string'],
            'meta_pixel_id' => ['nullable', 'string'],
            'meta_access_token' => ['nullable', 'string'],
            'meta_test_event_code' => ['nullable', 'string'],
            'gtm_id' => ['nullable', 'string'],
            'custom_head_code' => ['nullable', 'string'],
            'custom_css' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $landingPage->update($validated);

        $landingPage->ctas()->update([
            'telegram_destination' => $landingPage->telegram_destination,
        ]);

        $primaryCta = $landingPage->ctas()->where('button_type', 'primary')->first();
        if ($primaryCta) {
            $primaryCta->update(['button_text' => $landingPage->primary_cta_text]);
        }

        return redirect()->route('landing-pages.show', $landingPage)
            ->with('success', "Landing Page {$landingPage->title} updated successfully.");
    }

    public function destroy(LandingPage $landingPage)
    {
        $title = $landingPage->title;
        $lpId = $landingPage->id;

        \Illuminate\Support\Facades\DB::transaction(function () use ($landingPage, $lpId) {
            \App\Models\LandingPageView::where('landing_page_id', $lpId)->delete();
            \App\Models\CtaClick::where('landing_page_id', $lpId)->delete();
            \App\Models\TelegramInvite::where('landing_page_id', $lpId)->delete();
            \App\Models\Cta::where('landing_page_id', $lpId)->delete();
            \App\Models\TelegramChannel::where('landing_page_id', $lpId)->update(['landing_page_id' => null]);
            $landingPage->forceDelete();
        });

        return redirect()->route('landing-pages.index')
            ->with('success', "Landing page '{$title}' and its tracking data deleted successfully.");
    }
}
