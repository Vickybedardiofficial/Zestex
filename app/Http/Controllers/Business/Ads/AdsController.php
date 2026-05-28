<?php
/*
|--------------------------------------------------------------------------
| Zestex - Social Network Platform.
|--------------------------------------------------------------------------
| Based on: Zestex - The Social Network Web Application.
|--------------------------------------------------------------------------
| Author: Vicky Bedardi Yadav
|--------------------------------------------------------------------------
| Branded by: Vicky Bedardi Yadav
| E-mail: vicktbedardi9@gmail.com
|--------------------------------------------------------------------------
| Copyright (c) Flip Basket Pvt Ltd. All rights reserved.
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers\Business\Ads;

use App\Enums\Ad\AdStatus;
use Illuminate\Http\Request;
use App\Actions\Ad\DeleteAdAction;
use App\Http\Controllers\Controller;

class AdsController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->string('type', 'all')->toString();
        if (!in_array($type, ['all', 'active', 'archived'], true)) {
            $type = 'all';
        }

        $adsQuery = me()->advertising()->excludeDraft()->with(['user', 'media']);
        if ($type === 'active') {
            $adsQuery->where('status', AdStatus::PUBLISHED);
        } elseif ($type === 'archived') {
            $adsQuery->whereIn('status', [AdStatus::COMPLETED, AdStatus::PAUSED]);
        }

        $adsList = $adsQuery->latest('id')->paginate(10);

        return view('business::ads.index.index', [
            'type' => $type,
            'adsList' => $adsList,
        ]);
    }

    public function create()
    {
        return view('business::ads.create', [
            'adData' => $this->fetchOrInitializeDraftAd()
        ]);
    }

    public function edit($adId)
    {
        $adData = me()->advertising()->excludeDraft()->with(['media'])->findOrFail($adId);

        return view('business::ads.edit', [
            'adData' => $adData
        ]);
    }

    private function fetchOrInitializeDraftAd()
    {
        $adData = me()->advertising()->where('status', AdStatus::DRAFT)->first();

        if (empty($adData)) {
            me()->advertising()->create([
                'status' => AdStatus::DRAFT
            ]);

            return me()->advertising()->where('status', AdStatus::DRAFT)->first();
        }

        return $adData;
    }

    public function show($adId)
    {
        $adData = me()->advertising()->excludeDraft()->with(['media'])->findOrFail($adId);

        return view('business::ads.show.index', [
            'adData' => $adData
        ]);
    }

    public function destroy($adId)
    {
        $adData = me()->advertising()->findOrFail($adId);

        (new DeleteAdAction($adData))->execute();

        return redirect()->route('business.ads.index');
    }

    public function pause($adId)
    {
        $adData = me()->advertising()->findOrFail($adId);

        if ($adData->status->isPublished()) {
            $adData->update(['status' => AdStatus::PAUSED]);
        }

        return redirect()->route('business.ads.show', $adId);
    }

    public function publish($adId)
    {
        $adData = me()->advertising()->findOrFail($adId);

        if ($adData->status->isPaused()) {
            $adData->update(['status' => AdStatus::PUBLISHED]);
        }

        return redirect()->route('business.ads.show', $adId);
    }
}
