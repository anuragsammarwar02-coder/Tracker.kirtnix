<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\Client;
use App\Models\Campaign;
use App\Models\Cta;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LandingPageController extends Controller
{
    public function index(Request $request)
    {
        $query = LandingPage::with(['client', 'campaign', 'ctas'])->withCount(['views', 'clicks']);

        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('brand_name', 'like', "%{$search}%");
        }

        if ($clientId = $request->query('client_id')) {
            $query->where('client_id', $clientId);
        }

        $landingPages = $query->latest('id')->paginate(10)->withQueryString();
        $clients = Client::orderBy('company_name')->get();

        return view('landing_pages.index', compact('landingPages', 'clients'));
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
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:landing_pages,slug'],
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

        // Create Default Secondary CTA if text provided
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
            'slug' => ['required', 'string', 'max:255', 'unique:landing_pages,slug,' . $landingPage->id],
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

        $landingPage->update($validated);

        // Synchronize CTAs destination
        $landingPage->ctas()->update([
            'telegram_destination' => $landingPage->telegram_destination,
        ]);

        // Update primary CTA button text
        $primaryCta = $landingPage->ctas()->where('button_type', 'primary')->first();
        if ($primaryCta) {
            $primaryCta->update(['button_text' => $landingPage->primary_cta_text]);
        }

        return redirect()->route('landing-pages.show', $landingPage)
            ->with('success', "Landing Page {$landingPage->title} updated successfully.");
    }

    public function destroy(LandingPage $landingPage)
    {
        $landingPage->delete();
        return redirect()->route('landing-pages.index')
            ->with('success', 'Landing page archived.');
    }
}
