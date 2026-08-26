@props(['experiences'])

@php
    $approximateCoordinateIdentities = [
        'Pesta Sebauh 2026|2026-07-03|2026-07-12',
        'Challenge Malaysia|2026-10-17|2026-10-18',
        'KOKOL Ultra 2026|2026-10-23|2026-10-25',
    ];

    $mapMarkers = $experiences->map(function ($experience) use ($approximateCoordinateIdentities) {
        $imagePath = is_string($experience->image_url)
            ? ltrim(str_replace('\\', '/', trim($experience->image_url)), '/')
            : null;
        $isExternalImage = filled($imagePath)
            && (str_starts_with(strtolower($imagePath), 'http://')
                || str_starts_with(strtolower($imagePath), 'https://'));
        $isSafeRelativePath = filled($imagePath)
            && !str_contains($imagePath, '../')
            && !$isExternalImage;
        $imageSource = $isExternalImage
            ? $imagePath
            : ($isSafeRelativePath && is_file(public_path($imagePath)) ? asset($imagePath) : null);
        $coordinateIdentity = implode('|', [
            $experience->experiences_name,
            $experience->start_date?->format('Y-m-d'),
            $experience->end_date?->format('Y-m-d'),
        ]);

        return [
            'id' => $experience->experiences_id,
            'name' => $experience->experiences_name,
            'latitude' => (float) $experience->latitude,
            'longitude' => (float) $experience->longitude,
            'startDate' => $experience->start_date?->format('d M Y'),
            'startDateSort' => $experience->start_date?->format('Y-m-d'),
            'endDate' => $experience->end_date?->format('d M Y'),
            'location' => $experience->location_name,
            'shortDescription' => $experience->short_description,
            'imageUrl' => $imageSource,
            'externalImage' => $isExternalImage,
            'detailsUrl' => route('experiences.show', $experience),
            'categoryName' => $experience->category?->category_name,
            'typeName' => $experience->type?->type_name,
            'coordinatePrecision' => in_array($coordinateIdentity, $approximateCoordinateIdentities, true)
                ? 'approximate'
                : 'exact',
        ];
    })->values();
@endphp

<script>
    window.livingHeritageExperienceMarkers = {{ Illuminate\Support\Js::from($mapMarkers) }};
</script>
