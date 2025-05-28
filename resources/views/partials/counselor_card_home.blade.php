<img src="{{ $counselor['image'] }}" alt="Konselor" class="img-fluid rounded shadow-sm mb-2" style="height: 100px; width: 100px; object-fit: cover;">
<p class="mb-0 fw-semibold">{{ $counselor['name'] }}</p>
<small class="text-muted">
    @php
        $availabilityDays = [];
        if (!empty($counselor['availability']['day1']) && $counselor['availability']['day1'] !== 'Unknown') {
            $availabilityDays[] = $counselor['availability']['day1'];
        }
        if (!empty($counselor['availability']['day2']) && $counselor['availability']['day2'] !== 'Unknown') {
            $availabilityDays[] = $counselor['availability']['day2'];
        }
        if (!empty($counselor['availability']['day3']) && $counselor['availability']['day3'] !== 'Unknown') {
            $availabilityDays[] = $counselor['availability']['day3'];
        }
    @endphp
    {{ implode(', ', $availabilityDays) }}
</small><br>
<a href="{{ url('/counselor/' . $counselor['uid']) }}" class="text-primary small">Selengkapnya</a>